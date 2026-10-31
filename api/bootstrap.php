<?php
declare(strict_types=1);

/**
 * Gedeelde opzet voor de twee endpoints (aanvraag.php voor het formulier,
 * offerte.php voor agents en tools): omgeving, validatie, rate limit,
 * prijsberekening en het versturen van de mails. Beide endpoints doen
 * daardoor exact hetzelfde, enkel de invoer- en uitvoervorm verschilt.
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/pricing.php';
require_once __DIR__ . '/distance.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email_templates.php';
require_once __DIR__ . '/lead_log.php';

loadPianoCEnv();

const PIANOC_BRON = 'pianoc-catering';
const PIANOC_MAX_PER_UUR = 10;
const PIANOC_TALEN = ['nl', 'en', 'it'];

function pianocConfig(): array
{
    static $config = null;
    return $config ??= loadPricingConfig(__DIR__ . '/../pricing-config.json');
}

/**
 * Max PIANOC_MAX_PER_UUR aanvragen per IP per uur (bestand in de tijdelijke
 * map van de server). Geeft false als de limiet bereikt is.
 */
function pianocRateLimitOk(string $ip): bool
{
    $dir = sys_get_temp_dir() . '/pianoc_offerte_rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . '/' . hash('sha256', $ip) . '.json';
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return true; // geen opslag beschikbaar: liever doorlaten dan een echte aanvraag blokkeren
    }
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $tijden = array_values(array_filter(
        (array) json_decode($raw ?: '[]', true),
        static fn($t): bool => is_int($t) && $t > time() - 3600
    ));
    $ok = count($tijden) < PIANOC_MAX_PER_UUR;
    if ($ok) {
        $tijden[] = time();
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($tijden));
    flock($fh, LOCK_UN);
    fclose($fh);
    return $ok;
}

function pianocLang(?string $raw): string
{
    $raw = strtolower(trim((string) $raw));
    return in_array($raw, PIANOC_TALEN, true) ? $raw : 'nl';
}

/**
 * Klantvriendelijke boodschap als de prijs niet berekend kon worden maar de
 * aanvraag wel is vastgelegd en het team verwittigd is.
 */
function pianocFallbackMessage(string $lang): string
{
    return match ($lang) {
        'en' => 'The automatic price calculation hit a snag, but your request has been saved. We will get back to you personally as soon as possible.',
        'it' => 'Il calcolo automatico del prezzo ha avuto un problema, ma la vostra richiesta è stata registrata. Vi ricontatteremo personalmente al più presto.',
        default => 'De automatische prijsberekening liep even vast, maar je aanvraag is geregistreerd. We nemen zo snel mogelijk persoonlijk contact met je op.',
    };
}

/**
 * Normaliseert de velden van formulier of JSON naar één invoervorm.
 *
 * @param array<string,mixed> $raw
 * @return array{input:array<string,mixed>,fouten:string[]}
 */
function pianocNormaliseer(array $raw): array
{
    $str = static fn(string $k, int $max): string => mb_substr(trim((string) ($raw[$k] ?? '')), 0, $max);
    $fouten = [];

    $naam = $str('naam', 120);
    if ($naam === '') {
        $fouten[] = 'naam is verplicht.';
    }
    $email = $str('email', 200);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fouten[] = 'email is geen geldig e-mailadres.';
    }
    $datum = $str('datum', 10);
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $datum);
    if (!$d || $d->format('Y-m-d') !== $datum) {
        $fouten[] = 'datum moet het formaat YYYY-MM-DD hebben.';
    } elseif ($d < new DateTimeImmutable('today')) {
        $fouten[] = 'datum mag niet in het verleden liggen.';
    }
    $aantal = filter_var($raw['aantal_personen'] ?? null, FILTER_VALIDATE_INT);
    if ($aantal === false || $aantal < 1 || $aantal > 2000) {
        $fouten[] = 'aantal_personen moet een geheel getal van minstens 1 zijn.';
    }
    $locatie = $str('locatie', 300);
    if ($locatie === '') {
        $fouten[] = 'locatie (adres of gemeente) is verplicht.';
    }

    $itemsRaw = $raw['items'] ?? [];
    if (is_string($itemsRaw)) {
        $itemsRaw = preg_split('/[,|]/', $itemsRaw) ?: [];
    }
    $items = [];
    foreach ((array) $itemsRaw as $id) {
        $id = strtolower(trim((string) $id));
        if ($id !== '' && preg_match('/^[a-z0-9-]{1,40}$/', $id)) {
            $items[] = $id;
        }
    }
    $items = array_values(array_unique($items));
    if (!$items) {
        $fouten[] = 'items is verplicht: minstens één hoofdgerecht (bv. pizza).';
    } else {
        foreach ($items as $id) {
            if (findItem(pianocConfig(), $id) === null) {
                $fouten[] = "items bevat een onbekend id: {$id}.";
            }
        }
    }

    $afstandRaw = trim((string) ($raw['afstand_km'] ?? ''));
    $afstandHint = $afstandRaw !== '' && is_numeric($afstandRaw) ? round(max(0.0, (float) $afstandRaw), 1) : null;

    $input = [
        'naam' => $naam,
        'email' => $email,
        'telefoon' => $str('telefoon', 40),
        'datum' => $datum,
        'aantal_personen' => (int) $aantal,
        'locatie' => $locatie,
        'items' => $items,
        'opmerkingen' => $str('opmerkingen', 2000),
        'lang' => pianocLang($raw['lang'] ?? $raw['taal'] ?? null),
        'afstand_km_hint' => $afstandHint,
        'utm_source' => $str('utm_source', 100),
        'utm_medium' => $str('utm_medium', 100),
        'utm_campaign' => $str('utm_campaign', 150),
        'utm_term' => $str('utm_term', 150),
        'pagina' => $str('pagina', 300),
    ];
    return ['input' => $input, 'fouten' => $fouten];
}

/**
 * Berekent de prijs, verstuurt klantmail en interne melding, logt de lead.
 *
 * @return array{ok:bool,status:int,referentie:string,prijs:?array,afstand_km:?float,mail_ok:bool,fout:?string,lead_captured:bool}
 */
function pianocVerwerk(array $input): array
{
    $config = pianocConfig();
    $lang = $input['lang'];
    $referentie = 'PC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $tag = '[' . strtoupper($lang) . ']';

    // Afstand: eerst zelf opzoeken (zelfde diensten als de site), anders de
    // waarde die de site al berekend had, anders zonder transport en met
    // een vlag in de interne mail.
    $afstandKm = null;
    $afstandBron = 'onbekend';
    try {
        $afstandKm = getDistanceKm($config['transport']['vertrekpunt'], $input['locatie']);
        $afstandBron = 'server';
    } catch (DistanceLookupException $e) {
        if ($input['afstand_km_hint'] !== null) {
            $afstandKm = $input['afstand_km_hint'];
            $afstandBron = 'site';
        }
        logLead(PIANOC_BRON, $input, 'warning', 'afstand_lookup_failed: ' . $e->getMessage());
    }
    $input['afstand_km'] = $afstandKm;
    $input['afstand_bron'] = $afstandBron;

    try {
        $prijs = calculatePrice($config, [
            'aantal_personen' => $input['aantal_personen'],
            'items' => $input['items'],
            'afstand_km' => $afstandKm,
            'lang' => $lang,
        ]);
    } catch (InvalidArgumentException $e) {
        logLead(PIANOC_BRON, $input, 'failed', 'pricing_failed: ' . $e->getMessage());
        foreach (sendNotifyMail(
            'Offerte-aanvraag Piano C zonder prijsberekening ' . $tag . ' (' . $input['naam'] . ')',
            buildFallbackEmailHtml($input, $e->getMessage()),
            $input['email'],
            $input['naam']
        ) as $notifyEmail => $notifyError) {
            logLead(PIANOC_BRON, $input, 'failed', 'fallback_notificatie_failed (' . $notifyEmail . '): ' . $notifyError);
        }
        return ['ok' => false, 'status' => 422, 'referentie' => $referentie, 'prijs' => null, 'afstand_km' => $afstandKm, 'mail_ok' => false, 'fout' => pianocFallbackMessage($lang), 'lead_captured' => true];
    }

    $mailOk = true;
    try {
        sendMail(
            $input['email'],
            $input['naam'],
            match ($lang) {
                'en' => 'Your celebration with the kitchen of Piano A, our proposal',
                'it' => 'La vostra festa con la cucina di Piano A, la nostra proposta',
                default => 'Je feest met de keuken van Piano A, ons voorstel',
            },
            buildKlantEmailHtml($input, $prijs, $lang, $referentie),
            mailFromEmail(),
            mailFromName()
        );
    } catch (MailException $e) {
        $mailOk = false;
        logLead(PIANOC_BRON, $input, 'failed', 'klantmail_failed: ' . $e->getMessage());
    }

    foreach (sendNotifyMail(
        'Nieuwe offerte-aanvraag Piano C ' . $tag . ' (' . $input['naam'] . ')',
        buildInterneEmailHtml($input, $prijs, $referentie),
        $input['email'],
        $input['naam']
    ) as $notifyEmail => $notifyError) {
        logLead(PIANOC_BRON, $input, 'failed', 'interne_notificatie_failed (' . $notifyEmail . '): ' . $notifyError);
    }

    logLead(PIANOC_BRON, $input, $mailOk ? 'ok' : 'mail_failed', $referentie, $prijs['totaal_excl_btw'], $prijs['prijs_pp_excl_btw']);

    return ['ok' => true, 'status' => 200, 'referentie' => $referentie, 'prijs' => $prijs, 'afstand_km' => $afstandKm, 'mail_ok' => $mailOk, 'fout' => null, 'lead_captured' => true];
}
