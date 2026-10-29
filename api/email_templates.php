<?php
declare(strict_types=1);

/**
 * HTML-mailtemplates van Piano C Catering, in NL, EN en IT.
 * Tabelgebaseerde opmaak (betrouwbaar in mailclients) in de huisstijl van
 * de site: espresso, cream, terracotta, olijf.
 *
 * Drie mails:
 *  - klantmail: de richtprijs, de keuzes nog eens bevestigd, en als
 *    vervolgstap de uitnodiging om eerst te komen proeven in Piano A.
 *  - interne melding: alle gegevens in een tabel, zelfde opzet als bij
 *    Pretty Nice Dough zodat Sofie ze op dezelfde manier leest.
 *  - fallback: als de prijs niet berekend kon worden.
 */

const PIANOC_COLOR_ESPRESSO = '#4A342A';
const PIANOC_COLOR_ESPRESSO_DEEP = '#2A1D16';
const PIANOC_COLOR_CREAM = '#F4F1EC';
const PIANOC_COLOR_CREAM_SOFT = '#EBE5DA';
const PIANOC_COLOR_TERRACOTTA = '#C0562E';
const PIANOC_COLOR_OLIVE = '#74764E';
const PIANOC_PIANO_A_URL = 'https://pianoa.piano-ab.be';
const PIANOC_MAIL = 'info@pianocatering.be';
const PIANOC_SITE = 'https://www.pianocatering.be';

function emailEsc(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function pianocEuro(float $v): string
{
    return '€' . number_format($v, 2, ',', '.');
}

function emailTaalLabel(?string $lang): string
{
    return match ($lang) {
        'en' => 'Engels (EN)',
        'it' => 'Italiaans (IT)',
        default => 'Nederlands (NL)',
    };
}

/**
 * "5 juni 2027": dag in cijfers, maand voluit in de taal, jaartal voluit.
 */
function emailDatumLang(string $rawDate, string $lang): string
{
    $ts = strtotime($rawDate) ?: time();
    $maanden = [
        'nl' => ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'it' => ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'],
    ];
    $m = $maanden[$lang][(int) date('n', $ts) - 1] ?? $maanden['nl'][(int) date('n', $ts) - 1];
    return date('j', $ts) . ' ' . $m . ' ' . date('Y', $ts);
}

/**
 * Teksten per taal, op één plaats zodat NL, EN en IT altijd gelijk lopen.
 */
function pianocT(string $lang): array
{
    $t = [
        'nl' => [
            'titel' => 'Je richtprijs',
            'groet' => 'Dag %s,',
            'intro' => 'Bedankt voor je aanvraag. Hieronder vind je de richtprijs voor je feest op <strong>%s</strong>, met de keuzes die je op onze site maakte. Alles wat we serveren is exact wat je in Piano A op je bord krijgt: hetzelfde deeg, dezelfde toppings, dezelfde recepten uit Puglia.',
            'details' => 'Wat we noteerden',
            'datum' => 'Datum',
            'personen' => 'Aantal personen',
            'locatie' => 'Locatie',
            'afstand' => 'Afstand vanaf Piano A',
            'afstand_waarde' => '%s km',
            'keuzes' => 'Je keuzes',
            'prijsopbouw' => 'Prijs per persoon',
            'opstart' => 'Opstart (%s verdeeld over %d personen)',
            'transport' => 'Transport (%s km boven de gratis zone, %s totaal)',
            'korting' => 'Groepskorting (%d %%)',
            'pp' => 'Richtprijs per persoon',
            'totaal' => 'Totaal voor %d personen',
            'excl' => 'excl. btw',
            'btw' => 'Btw (12 %% op eten, 21 %% op drank)',
            'incl' => 'Totaal incl. btw',
            'extra' => 'extra drank, 30 %',
            'minimum' => 'We werken met een minimum van %d personen. Je aanvraag zat daar net onder, dus de opstartkost is verdeeld over %d personen.',
            'afstand_onbekend' => 'We konden de afstand naar je locatie nog niet automatisch berekenen. Transport zit dus nog niet in deze richtprijs; we rekenen dat na in ons voorstel (gratis binnen 20 km rond Leuven, daarbuiten €1,35 per extra km).',
            'proef_titel' => 'Kom eerst proeven in Piano A',
            'proef_tekst' => 'Twijfel je nog? Kom langs in Piano A, Ravenstraat 38 in Leuven. Wat je daar proeft, is exact wat je gasten op je feest krijgen. Reserveer een tafel en zeg erbij dat je voor een feest komt proeven, dan nemen we de tijd om je menu samen te stellen.',
            'proef_knop' => 'Reserveer in Piano A',
            'vervolg' => 'Dit is een richtprijs. We mailen je binnen één werkdag om de datum te bevestigen en de details te overlopen. Heb je intussen een vraag of wil je je datum al vastleggen? Antwoord gewoon op deze mail.',
            'antwoord' => 'Antwoord op deze mail',
            'antwoord_onderwerp' => 'Mijn richtprijs (ref. %s)',
            'ref' => 'Referentie',
            'groeten' => 'A presto,<br>Alessandro, Andrea en Mattia<br><span style="color:' . PIANOC_COLOR_OLIVE . '">Piano C Catering, de catering van Piano A</span>',
            'footer' => 'Piano C Catering is de catering-extensie van restaurant Piano A, Ravenstraat 38, 3000 Leuven. Uitgebaat door Kumbaya BV, BE0793492662. Prijzen excl. btw.',
        ],
        'en' => [
            'titel' => 'Your quote',
            'groet' => 'Hi %s,',
            'intro' => 'Thank you for your request. Below is the indicative price for your event on <strong>%s</strong>, based on the choices you made on our site. Everything we serve is exactly what you get on your plate at Piano A: the same dough, the same toppings, the same recipes from Puglia.',
            'details' => 'What we noted',
            'datum' => 'Date',
            'personen' => 'Number of guests',
            'locatie' => 'Location',
            'afstand' => 'Distance from Piano A',
            'afstand_waarde' => '%s km',
            'keuzes' => 'Your choices',
            'prijsopbouw' => 'Price per person',
            'opstart' => 'Setup (%s split across %d guests)',
            'transport' => 'Transport (%s km beyond the free zone, %s in total)',
            'korting' => 'Group discount (%d %%)',
            'pp' => 'Indicative price per person',
            'totaal' => 'Total for %d guests',
            'excl' => 'excl. VAT',
            'btw' => 'VAT (12 %% on food, 21 %% on drinks)',
            'incl' => 'Total incl. VAT',
            'extra' => 'extra drink, 30 %',
            'minimum' => 'We work with a minimum of %d guests. Your request was just below that, so the setup cost is split across %d guests.',
            'afstand_onbekend' => 'We could not calculate the distance to your location automatically yet, so transport is not included in this quote. We will add it in our proposal (free within 20 km of Leuven, €1.35 per extra km beyond that).',
            'proef_titel' => 'Taste it first at Piano A',
            'proef_tekst' => 'Not sure yet? Come by Piano A, Ravenstraat 38 in Leuven. What you taste there is exactly what your guests will get at your event. Book a table and mention you are tasting for an event, and we will take the time to build your menu together.',
            'proef_knop' => 'Book at Piano A',
            'vervolg' => 'This is an indicative price. We will email you within one working day to confirm the date and go through the details. A question in the meantime, or want to secure your date? Just reply to this email.',
            'antwoord' => 'Reply to this email',
            'antwoord_onderwerp' => 'My quote (ref. %s)',
            'ref' => 'Reference',
            'groeten' => 'A presto,<br>Alessandro, Andrea and Mattia<br><span style="color:' . PIANOC_COLOR_OLIVE . '">Piano C Catering, the catering of Piano A</span>',
            'footer' => 'Piano C Catering is the catering extension of restaurant Piano A, Ravenstraat 38, 3000 Leuven, Belgium. Operated by Kumbaya BV, BE0793492662. Prices excl. VAT.',
        ],
        'it' => [
            'titel' => 'Il vostro preventivo',
            'groet' => 'Ciao %s,',
            'intro' => 'Grazie per la richiesta. Qui sotto trovate il prezzo indicativo per la vostra festa del <strong>%s</strong>, con le scelte fatte sul nostro sito. Tutto ciò che serviamo è esattamente quello che trovate nel piatto da Piano A: lo stesso impasto, gli stessi ingredienti, le stesse ricette della Puglia.',
            'details' => 'Cosa abbiamo annotato',
            'datum' => 'Data',
            'personen' => 'Numero di persone',
            'locatie' => 'Luogo',
            'afstand' => 'Distanza da Piano A',
            'afstand_waarde' => '%s km',
            'keuzes' => 'Le vostre scelte',
            'prijsopbouw' => 'Prezzo a persona',
            'opstart' => 'Allestimento (%s ripartiti su %d persone)',
            'transport' => 'Trasporto (%s km oltre la zona gratuita, %s in totale)',
            'korting' => 'Sconto di gruppo (%d %%)',
            'pp' => 'Prezzo indicativo a persona',
            'totaal' => 'Totale per %d persone',
            'excl' => 'IVA esclusa',
            'btw' => 'IVA (12 %% sul cibo, 21 %% sulle bevande)',
            'incl' => 'Totale IVA inclusa',
            'extra' => 'bevanda extra, 30 %',
            'minimum' => 'Lavoriamo con un minimo di %d persone. La vostra richiesta era appena sotto, quindi il costo di allestimento è ripartito su %d persone.',
            'afstand_onbekend' => 'Non siamo ancora riusciti a calcolare automaticamente la distanza fino al vostro luogo, quindi il trasporto non è incluso in questo preventivo. Lo aggiungeremo nella nostra proposta (gratuito entro 20 km da Leuven, oltre €1,35 per km extra).',
            'proef_titel' => 'Venite prima ad assaggiare da Piano A',
            'proef_tekst' => 'Avete ancora dubbi? Passate da Piano A, Ravenstraat 38 a Leuven. Quello che assaggiate lì è esattamente quello che i vostri ospiti riceveranno alla festa. Prenotate un tavolo e dite che venite ad assaggiare per una festa, così prendiamo il tempo per comporre insieme il vostro menù.',
            'proef_knop' => 'Prenotate da Piano A',
            'vervolg' => 'Questo è un prezzo indicativo. Vi scriveremo entro un giorno lavorativo per confermare la data e rivedere i dettagli. Avete una domanda o volete già fissare la data? Rispondete pure a questa e-mail.',
            'antwoord' => 'Rispondi a questa e-mail',
            'antwoord_onderwerp' => 'Il mio preventivo (rif. %s)',
            'ref' => 'Riferimento',
            'groeten' => 'A presto,<br>Alessandro, Andrea e Mattia<br><span style="color:' . PIANOC_COLOR_OLIVE . '">Piano C Catering, il catering di Piano A</span>',
            'footer' => 'Piano C Catering è l\'estensione catering del ristorante Piano A, Ravenstraat 38, 3000 Leuven, Belgio. Gestito da Kumbaya BV, BE0793492662. Prezzi IVA esclusa.',
        ],
    ];
    return $t[$lang] ?? $t['nl'];
}

function emailWrapper(string $title, string $bodyHtml, string $footer): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:' . PIANOC_COLOR_CREAM_SOFT . ';font-family:Georgia,\'Times New Roman\',serif;color:' . PIANOC_COLOR_ESPRESSO . ';">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . PIANOC_COLOR_CREAM_SOFT . ';padding:24px 0;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:' . PIANOC_COLOR_CREAM . ';border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:' . PIANOC_COLOR_ESPRESSO_DEEP . ';padding:26px 28px;text-align:center;">'
        . '<span style="font-size:26px;font-weight:bold;letter-spacing:.02em;color:' . PIANOC_COLOR_CREAM . ';">Piano C</span><br>'
        . '<span style="font-family:\'Courier New\',Courier,monospace;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:' . PIANOC_COLOR_TERRACOTTA . ';">Apulian Catering · from Puglia with love</span>'
        . '</td></tr>'
        . '<tr><td style="padding:28px;">'
        . '<h1 style="font-size:24px;font-weight:normal;margin:0 0 16px;color:' . PIANOC_COLOR_ESPRESSO . ';">' . emailEsc($title) . '</h1>'
        . $bodyHtml
        . '</td></tr>'
        . '<tr><td style="background:' . PIANOC_COLOR_ESPRESSO_DEEP . ';padding:18px 28px;text-align:center;color:rgba(244,241,236,0.7);font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;">'
        . emailEsc($footer) . '<br><a href="' . PIANOC_SITE . '" style="color:' . PIANOC_COLOR_CREAM . ';">pianocatering.be</a> · <a href="mailto:info@pianocatering.be" style="color:' . PIANOC_COLOR_CREAM . ';">info@pianocatering.be</a>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

function emailDetailsTabel(array $rows): string
{
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;background:' . PIANOC_COLOR_CREAM_SOFT . ';border-radius:12px;">'
        . '<tr><td style="padding:16px 18px;">';
    foreach ($rows as $label => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:5px;">'
            . '<tr>'
            . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:' . PIANOC_COLOR_OLIVE . ';width:42%;vertical-align:top;">' . emailEsc($label) . '</td>'
            . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;">' . emailEsc((string) $value) . '</td>'
            . '</tr></table>';
    }
    $html .= '</td></tr></table>';
    return $html;
}

/**
 * Prijsopbouw per persoon, zoals het paneel op de site, plus totaal en btw.
 */
function emailPrijsTabel(array $prijs, string $lang): string
{
    $t = pianocT($lang);
    $rij = static fn(string $label, string $value, string $style = ''): string =>
        '<tr style="border-bottom:1px solid rgba(74,52,42,.14);">'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:8px 0;' . $style . '">' . $label . '</td>'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:8px 0;text-align:right;white-space:nowrap;' . $style . '">' . $value . '</td>'
        . '</tr>';

    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:12px 0;">';
    foreach ($prijs['lijnen'] as $l) {
        if ($l['type'] === 'item') {
            $label = emailEsc($l['naam']);
            if (!empty($l['extra'])) {
                $label .= ' <span style="color:' . PIANOC_COLOR_OLIVE . ';font-size:12px;">(' . emailEsc($t['extra']) . ')</span>';
            } elseif ($l['opslag'] > 0) {
                $label .= ' <span style="color:' . PIANOC_COLOR_OLIVE . ';font-size:12px;">(+' . (int) round($l['opslag'] * 100) . ' %)</span>';
            }
            $html .= $rij($label, pianocEuro($l['prijs_pp']));
        } elseif ($l['type'] === 'opstart') {
            $html .= $rij(emailEsc(sprintf($t['opstart'], pianocEuro($l['totaal']), $l['verdeeld_over'])), pianocEuro($l['prijs_pp']));
        } elseif ($l['type'] === 'transport') {
            $html .= $rij(emailEsc(sprintf($t['transport'], number_format($l['extra_km'], 1, ',', ''), pianocEuro($l['totaal']))), pianocEuro($l['prijs_pp']));
        }
    }
    if ($prijs['korting_percentage'] > 0) {
        $html .= $rij(emailEsc(sprintf($t['korting'], (int) round($prijs['korting_percentage'] * 100))), '−' . pianocEuro($prijs['korting_pp']), 'color:' . PIANOC_COLOR_OLIVE . ';');
    }
    $html .= '<tr><td style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;padding:14px 0 4px;border-top:2px solid ' . PIANOC_COLOR_TERRACOTTA . ';">' . emailEsc($t['pp']) . '</td>'
        . '<td style="font-family:Georgia,serif;font-size:26px;font-weight:bold;color:' . PIANOC_COLOR_TERRACOTTA . ';text-align:right;padding:14px 0 4px;border-top:2px solid ' . PIANOC_COLOR_TERRACOTTA . ';white-space:nowrap;">' . pianocEuro($prijs['prijs_pp_excl_btw']) . '</td></tr>';
    $html .= '<tr><td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:4px 0;">' . emailEsc(sprintf($t['totaal'], $prijs['aantal_personen'])) . ' <span style="color:' . PIANOC_COLOR_OLIVE . ';font-size:12px;">' . emailEsc($t['excl']) . '</span></td>'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;text-align:right;padding:4px 0;white-space:nowrap;">' . pianocEuro($prijs['totaal_excl_btw']) . '</td></tr>';
    $html .= '<tr><td style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:' . PIANOC_COLOR_OLIVE . ';padding:2px 0;">' . emailEsc(sprintf($t['btw'])) . '</td>'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:' . PIANOC_COLOR_OLIVE . ';text-align:right;padding:2px 0;">' . pianocEuro($prijs['btw_totaal']) . '</td></tr>';
    $html .= '<tr><td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;padding:2px 0 8px;">' . emailEsc($t['incl']) . '</td>'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;text-align:right;padding:2px 0 8px;">' . pianocEuro($prijs['totaal_incl_btw']) . '</td></tr>';
    $html .= '</table>';
    return $html;
}

function emailKnop(string $url, string $label, string $kleur): string
{
    return '<a href="' . emailEsc($url) . '" style="display:inline-block;background:' . $kleur . ';color:' . PIANOC_COLOR_CREAM . ';text-decoration:none;font-family:Arial,Helvetica,sans-serif;font-weight:bold;font-size:14px;padding:13px 26px;border-radius:999px;">' . emailEsc($label) . '</a>';
}

/**
 * Lijst van de gekozen items per categorie, voor de detailtabel.
 */
function emailKeuzesTekst(array $prijs): string
{
    $delen = [];
    foreach ($prijs['lijnen'] as $l) {
        if ($l['type'] === 'item') {
            $delen[$l['categorie_naam']][] = $l['naam'];
        }
    }
    $out = [];
    foreach ($delen as $cat => $namen) {
        $out[] = $cat . ': ' . implode(', ', $namen);
    }
    return implode(' · ', $out);
}

// ─── Klantmail ───────────────────────────────────────────────────────────
function buildKlantEmailHtml(array $input, array $prijs, string $lang, string $referentie): string
{
    $t = pianocT($lang);
    $naam = emailEsc((string) ($input['naam'] ?? ''));
    $datum = emailDatumLang((string) ($input['datum'] ?? ''), $lang);

    $rows = [
        $t['datum'] => $datum,
        $t['personen'] => (string) $prijs['aantal_personen'],
        $t['locatie'] => $input['locatie'] ?? '',
        $t['afstand'] => $prijs['afstand_bekend'] ? sprintf($t['afstand_waarde'], number_format((float) $prijs['afstand_km'], 1, ',', '')) : null,
        $t['keuzes'] => emailKeuzesTekst($prijs),
        $t['ref'] => $referentie,
    ];

    $p = static fn(string $s): string => '<p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;margin:0 0 14px;">' . $s . '</p>';
    $html = $p(sprintf($t['groet'], $naam)) . $p(sprintf($t['intro'], emailEsc($datum)));
    $html .= '<h2 style="font-size:12px;font-family:\'Courier New\',Courier,monospace;letter-spacing:.14em;text-transform:uppercase;color:' . PIANOC_COLOR_TERRACOTTA . ';margin:22px 0 4px;">' . emailEsc($t['details']) . '</h2>';
    $html .= emailDetailsTabel($rows);

    if ($prijs['minimum_toegepast']) {
        $html .= '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;background:' . PIANOC_COLOR_CREAM_SOFT . ';border-left:4px solid ' . PIANOC_COLOR_OLIVE . ';padding:12px 16px;margin:0 0 14px;">' . emailEsc(sprintf($t['minimum'], $prijs['min_gasten'], $prijs['aantal_gefactureerd_opstart'])) . '</p>';
    }
    if (!$prijs['afstand_bekend']) {
        $html .= '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;background:' . PIANOC_COLOR_CREAM_SOFT . ';border-left:4px solid ' . PIANOC_COLOR_TERRACOTTA . ';padding:12px 16px;margin:0 0 14px;">' . emailEsc($t['afstand_onbekend']) . '</p>';
    }

    $html .= '<h2 style="font-size:12px;font-family:\'Courier New\',Courier,monospace;letter-spacing:.14em;text-transform:uppercase;color:' . PIANOC_COLOR_TERRACOTTA . ';margin:22px 0 4px;">' . emailEsc($t['prijsopbouw']) . '</h2>';
    $html .= emailPrijsTabel($prijs, $lang);

    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;background:' . PIANOC_COLOR_ESPRESSO . ';border-radius:12px;"><tr><td style="padding:22px 24px;text-align:center;">'
        . '<p style="font-size:20px;color:' . PIANOC_COLOR_CREAM . ';margin:0 0 8px;">' . emailEsc($t['proef_titel']) . '</p>'
        . '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:rgba(244,241,236,.85);margin:0 0 16px;">' . emailEsc($t['proef_tekst']) . '</p>'
        . emailKnop(PIANOC_PIANO_A_URL, $t['proef_knop'], PIANOC_COLOR_TERRACOTTA)
        . '</td></tr></table>';

    $html .= $p(emailEsc($t['vervolg']));
    $mailUrl = 'mailto:' . PIANOC_MAIL . '?subject=' . rawurlencode(sprintf($t['antwoord_onderwerp'], $referentie));
    $html .= '<p style="text-align:center;margin:18px 0 22px;">' . emailKnop($mailUrl, $t['antwoord'], PIANOC_COLOR_OLIVE) . '</p>';
    $html .= $p($t['groeten']);

    return emailWrapper($t['titel'], $html, $t['footer']);
}

// ─── Interne melding (voor Viktor, Sofie en Piano A) ──────────────────────
function buildInterneEmailHtml(array $input, array $prijs, string $referentie): string
{
    $utm = array_filter([
        'source' => $input['utm_source'] ?? '',
        'medium' => $input['utm_medium'] ?? '',
        'campaign' => $input['utm_campaign'] ?? '',
        'term' => $input['utm_term'] ?? '',
    ]);
    $rows = [
        'Merk' => 'Piano C',
        'Referentie' => $referentie,
        'Naam' => $input['naam'] ?? '',
        'E-mail' => $input['email'] ?? '',
        'Telefoon' => ($input['telefoon'] ?? '') !== '' ? $input['telefoon'] : '(niet ingevuld)',
        'Datum event' => emailDatumLang((string) ($input['datum'] ?? ''), 'nl'),
        'Aantal personen' => (string) $prijs['aantal_personen'] . ($prijs['minimum_toegepast'] ? ' (onder het minimum van ' . $prijs['min_gasten'] . ', opstart verdeeld over ' . $prijs['aantal_gefactureerd_opstart'] . ')' : ''),
        'Locatie' => $input['locatie'] ?? '',
        'Afstand' => $prijs['afstand_bekend'] ? number_format((float) $prijs['afstand_km'], 1, ',', '') . ' km (bron: ' . ($input['afstand_bron'] ?? 'onbekend') . ')' : 'ONBEKEND, transport niet in de prijs',
        'Keuzes' => emailKeuzesTekst($prijs),
        'Richtprijs p.p. excl. btw' => pianocEuro($prijs['prijs_pp_excl_btw']),
        'Totaal excl. btw' => pianocEuro($prijs['totaal_excl_btw']),
        'Totaal incl. btw' => pianocEuro($prijs['totaal_incl_btw']),
        'Opmerkingen' => ($input['opmerkingen'] ?? '') !== '' ? $input['opmerkingen'] : '(geen)',
        'Taal website' => emailTaalLabel($input['lang'] ?? null),
        'Bron (UTM)' => $utm ? implode(', ', array_map(static fn($k, $v) => "{$k}={$v}", array_keys($utm), $utm)) : '(geen UTM)',
        'Pagina' => $input['pagina'] ?? '',
    ];

    $html = '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;">Nieuwe aanvraag via pianocatering.be. De klant kreeg automatisch de richtprijs hieronder, met de uitnodiging om te komen proeven in Piano A. Antwoorden op deze mail gaat rechtstreeks naar de klant.</p>';
    $html .= '<table cellpadding="5" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;">';
    foreach ($rows as $label => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $html .= '<tr><td style="vertical-align:top;white-space:nowrap;"><strong>' . emailEsc($label) . '</strong></td><td>' . emailEsc((string) $value) . '</td></tr>';
    }
    $html .= '</table><h3 style="font-family:Arial,Helvetica,sans-serif;font-size:14px;margin:18px 0 0;">Prijsopbouw</h3>' . emailPrijsTabel($prijs, 'nl');

    return emailWrapper('Nieuwe offerte-aanvraag Piano C', $html, pianocT('nl')['footer']);
}

// ─── Fallback: prijs kon niet berekend worden ─────────────────────────────
function buildFallbackEmailHtml(array $input, string $reden): string
{
    $rows = [
        'Merk' => 'Piano C',
        'Naam' => $input['naam'] ?? '',
        'E-mail' => $input['email'] ?? '',
        'Telefoon' => ($input['telefoon'] ?? '') !== '' ? $input['telefoon'] : '(niet ingevuld)',
        'Datum event' => emailDatumLang((string) ($input['datum'] ?? ''), 'nl'),
        'Aantal personen' => (string) ($input['aantal_personen'] ?? ''),
        'Locatie' => $input['locatie'] ?? '',
        'Items' => implode(', ', (array) ($input['items'] ?? [])),
        'Opmerkingen' => ($input['opmerkingen'] ?? '') !== '' ? $input['opmerkingen'] : '(geen)',
        'Taal website' => emailTaalLabel($input['lang'] ?? null),
    ];
    $html = '<p style="font-family:Arial,Helvetica,sans-serif;color:' . PIANOC_COLOR_TERRACOTTA . ';font-weight:bold;">De automatische prijsberekening is mislukt. Deze klant kreeg GEEN richtprijs en wacht op een persoonlijk antwoord.</p>';
    $html .= '<p style="font-family:Arial,Helvetica,sans-serif;color:' . PIANOC_COLOR_OLIVE . ';">Reden: ' . emailEsc($reden) . '</p>';
    $html .= '<table cellpadding="5" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr><td style="vertical-align:top;"><strong>' . emailEsc($label) . '</strong></td><td>' . emailEsc((string) $value) . '</td></tr>';
    }
    $html .= '</table>';
    return emailWrapper('Aanvraag Piano C zonder prijsberekening', $html, pianocT('nl')['footer']);
}
