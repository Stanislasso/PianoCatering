<?php
declare(strict_types=1);

/**
 * HTML-mailtemplates van Piano C Catering, in NL, EN en IT.
 * Tabelgebaseerde opmaak (betrouwbaar in mailclients) in de huisstijl van
 * de site: espresso, cream, terracotta, olijf.
 *
 * Drie mails:
 *  - klantmail: de richtprijs, de keuzes nog eens bevestigd, en als
 *    een blok over de keuken van Piano A (restaurantkwaliteit).
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
function emailDatumLang(string $rawDate, string $lang, bool $metJaar = true): string
{
    $ts = strtotime($rawDate) ?: time();
    $maanden = [
        'nl' => ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
        'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        'it' => ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'],
    ];
    $m = $maanden[$lang][(int) date('n', $ts) - 1] ?? $maanden['nl'][(int) date('n', $ts) - 1];
    return date('j', $ts) . ' ' . $m . ($metJaar ? ' ' . date('Y', $ts) : '');
}

/**
 * Teksten per taal, op één plaats zodat NL, EN en IT altijd gelijk lopen.
 */
function pianocT(string $lang): array
{
    $t = [
        'nl' => [
            'titel' => 'Je offerte',
            'groet' => 'Dag %s,',
            'intro' => 'Bedankt voor je aanvraag. Hieronder vind je je offerte voor je feest op <strong>%s</strong>, met de keuzes die je op onze site maakte. Alles wat we serveren is exact wat je in Piano A op je bord krijgt: hetzelfde deeg, dezelfde toppings, dezelfde recepten uit Puglia.',
            'details' => 'Wat we noteerden',
            'datum' => 'Datum',
            'personen' => 'Aantal personen',
            'locatie' => 'Locatie',
            'afstand' => 'Afstand vanaf Piano A',
            'afstand_waarde' => '%s km (enkele rit)',
            'prijsopbouw' => 'Wat we voor je verzorgen',
            'lijn_opstart' => 'Opstartkost',
            'lijn_transport' => 'Transport heen en terug (%s km boven de eerste 20 km)',
            'lijn_dienst' => '%s voor %d personen',
            'lijn_pizzabuffet' => 'Pizzabuffet',
            'opstart' => 'Opstart (%s verdeeld over %d personen)',
            'transport' => 'Transport heen en terug (%s km boven de eerste 20 km, %s totaal)',
            'korting' => 'Groepskorting (%d %%)',
            'pp' => 'Prijs per persoon',
            'totaal' => 'Totaal voor %d personen',
            'excl' => 'excl. btw',
            'btw' => 'Btw (12 %% op eten, 21 %% op drank)',
            'incl' => 'Totaal incl. btw',
            'extra' => 'extra drank, 30 %',
            'minimum' => 'We werken met een minimum van %d personen. Je aanvraag zat daar net onder, dus de opstartkost is verdeeld over %d personen.',
            'afstand_onbekend' => 'We konden de afstand naar je locatie nog niet automatisch berekenen. Transport zit dus nog niet in deze prijs; we rekenen dat na in ons voorstel (de eerste 20 km heen en terug zijn gratis, daarna €1,35 per km).',
            'proef_titel' => 'Kies je vijf uit een 40-tal pizza\'s',
            'proef_tekst' => 'Piano C is de catering van ons restaurant Piano A in de Ravenstraat in Leuven. Je kiest je vijf pizza\'s uit een 40-tal op onze kaart, met hetzelfde deeg en dezelfde toppings als in het restaurant, vers uit de houtoven. Twijfel je? Kom ze eerst proeven in Piano A.',
            'proef_knop' => 'Bekijk onze pizza\'s',
            'vervolg' => 'We mailen je binnen één werkdag om de datum te bevestigen en de details te overlopen. Laat ook gerust weten welke vijf pizza\'s je wil. Heb je intussen een vraag of wil je je datum al vastleggen? Antwoord gewoon op deze mail.',
            'antwoord' => 'Antwoord op deze mail',
            'antwoord_onderwerp' => 'Mijn offerte (ref. %s)',
            'ref' => 'Referentie',
            'groeten' => 'A presto,<br><span style="color:' . PIANOC_COLOR_OLIVE . '">Piano C Catering, de catering van Piano A</span>',
        ],
        'en' => [
            'titel' => 'Your quote',
            'groet' => 'Hi %s,',
            'intro' => 'Thank you for your request. Below is your quote for your event on <strong>%s</strong>, based on the choices you made on our site. Everything we serve is exactly what you get on your plate at Piano A: the same dough, the same toppings, the same recipes from Puglia.',
            'details' => 'What we noted',
            'datum' => 'Date',
            'personen' => 'Number of guests',
            'locatie' => 'Location',
            'afstand' => 'Distance from Piano A',
            'afstand_waarde' => '%s km (one way)',
            'prijsopbouw' => 'What we take care of',
            'lijn_opstart' => 'Setup',
            'lijn_transport' => 'Round-trip transport (%s km beyond the first 20 km)',
            'lijn_dienst' => '%s for %d guests',
            'lijn_pizzabuffet' => 'Pizza buffet',
            'opstart' => 'Setup (%s split across %d guests)',
            'transport' => 'Round-trip transport (%s km beyond the first 20 km, %s in total)',
            'korting' => 'Group discount (%d %%)',
            'pp' => 'Price per person',
            'totaal' => 'Total for %d guests',
            'excl' => 'excl. VAT',
            'btw' => 'VAT (12 %% on food, 21 %% on drinks)',
            'incl' => 'Total incl. VAT',
            'extra' => 'extra drink, 30 %',
            'minimum' => 'We work with a minimum of %d guests. Your request was just below that, so the setup cost is split across %d guests.',
            'afstand_onbekend' => 'We could not calculate the distance to your location automatically yet, so transport is not included in this quote. We will add it in our proposal (the first 20 km there and back are free, then €1.35 per km).',
            'proef_titel' => 'Choose your five from around 40 pizzas',
            'proef_tekst' => 'Piano C is the catering of our restaurant Piano A on Ravenstraat in Leuven. You choose your five pizzas from around 40 on our menu, with the same dough and toppings as in the restaurant, fresh from the wood-fired oven. Not sure? Come and taste them first at Piano A.',
            'proef_knop' => 'See our pizzas',
            'vervolg' => 'We will email you within one working day to confirm the date and go through the details. Feel free to tell us which five pizzas you would like. A question in the meantime, or want to secure your date? Just reply to this email.',
            'antwoord' => 'Reply to this email',
            'antwoord_onderwerp' => 'My quote (ref. %s)',
            'ref' => 'Reference',
            'groeten' => 'A presto,<br><span style="color:' . PIANOC_COLOR_OLIVE . '">Piano C Catering, the catering of Piano A</span>',
        ],
        'it' => [
            'titel' => 'Il vostro preventivo',
            'groet' => 'Ciao %s,',
            'intro' => 'Grazie per la richiesta. Qui sotto trovate il vostro preventivo per la festa del <strong>%s</strong>, con le scelte fatte sul nostro sito. Tutto ciò che serviamo è esattamente quello che trovate nel piatto da Piano A: lo stesso impasto, gli stessi ingredienti, le stesse ricette della Puglia.',
            'details' => 'Cosa abbiamo annotato',
            'datum' => 'Data',
            'personen' => 'Numero di persone',
            'locatie' => 'Luogo',
            'afstand' => 'Distanza da Piano A',
            'afstand_waarde' => '%s km (solo andata)',
            'prijsopbouw' => 'Di cosa ci occupiamo',
            'lijn_opstart' => 'Allestimento',
            'lijn_transport' => 'Trasporto andata e ritorno (%s km oltre i primi 20 km)',
            'lijn_dienst' => '%s per %d persone',
            'lijn_pizzabuffet' => 'Buffet di pizza',
            'opstart' => 'Allestimento (%s ripartiti su %d persone)',
            'transport' => 'Trasporto andata e ritorno (%s km oltre i primi 20 km, %s in totale)',
            'korting' => 'Sconto di gruppo (%d %%)',
            'pp' => 'Prezzo a persona',
            'totaal' => 'Totale per %d persone',
            'excl' => 'IVA esclusa',
            'btw' => 'IVA (12 %% sul cibo, 21 %% sulle bevande)',
            'incl' => 'Totale IVA inclusa',
            'extra' => 'bevanda extra, 30 %',
            'minimum' => 'Lavoriamo con un minimo di %d persone. La vostra richiesta era appena sotto, quindi il costo di allestimento è ripartito su %d persone.',
            'afstand_onbekend' => 'Non siamo ancora riusciti a calcolare automaticamente la distanza fino al vostro luogo, quindi il trasporto non è incluso in questo preventivo. Lo aggiungeremo nella nostra proposta (i primi 20 km tra andata e ritorno sono gratuiti, poi €1,35 al km).',
            'proef_titel' => 'Scegliete le vostre cinque tra una quarantina di pizze',
            'proef_tekst' => 'Piano C è il catering del nostro ristorante Piano A in Ravenstraat a Leuven. Scegliete le vostre cinque pizze tra la quarantina del nostro menu, con lo stesso impasto e gli stessi condimenti del ristorante, appena sfornate dal forno a legna. Avete dubbi? Venite prima ad assaggiarle da Piano A.',
            'proef_knop' => 'Vedi le nostre pizze',
            'vervolg' => 'Vi scriveremo entro un giorno lavorativo per confermare la data e rivedere i dettagli. Diteci pure quali cinque pizze desiderate. Avete una domanda o volete già fissare la data? Rispondete pure a questa e-mail.',
            'antwoord' => 'Rispondi a questa e-mail',
            'antwoord_onderwerp' => 'Il mio preventivo (rif. %s)',
            'ref' => 'Riferimento',
            'groeten' => 'A presto,<br><span style="color:' . PIANOC_COLOR_OLIVE . '">Piano C Catering, il catering di Piano A</span>',
        ],
    ];
    return $t[$lang] ?? $t['nl'];
}

function emailWrapper(string $title, string $bodyHtml): string
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
 * Prijsopbouw per persoon voor de interne mail, met de korting als aparte
 * regel, plus totaal en btw.
 */
function emailPrijsTabel(array $prijs, string $lang): string
{
    $t = pianocT($lang);
    $lijnen = $prijs['lijnen'];
    $rij = static fn(string $label, string $value, string $style = ''): string =>
        '<tr style="border-bottom:1px solid rgba(74,52,42,.14);">'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:8px 0;' . $style . '">' . $label . '</td>'
        . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:8px 0;text-align:right;white-space:nowrap;' . $style . '">' . $value . '</td>'
        . '</tr>';

    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:12px 0;">';
    foreach ($lijnen as $l) {
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

/**
 * Offerte voor de klant: elke dienst met zijn totaalbedrag, zonder prijs per
 * persoon en zonder aparte korting. Opstart en transport staan volledig op hun
 * regel, de korting zit in de bedragen van de gerechten en dranken, zodat de
 * regels exact optellen tot het totaal excl. btw.
 *
 * @return array<int,array{titel:string,detail:string,bedrag:float}>
 */
function emailOfferteLijnen(array $prijs, string $lang): array
{
    $t = pianocT($lang);
    $gasten = (int) $prijs['aantal_personen'];
    $vast = [];
    $groepen = [];
    foreach ($prijs['lijnen'] as $l) {
        if ($l['type'] === 'item') {
            $id = (string) $l['categorie'];
            $groepen[$id] ??= ['titel' => (string) $l['categorie_naam'], 'namen' => [], 'ids' => [], 'pp' => 0.0];
            array_push($groepen[$id]['namen'], ...explode(' + ', (string) $l['naam']));
            array_push($groepen[$id]['ids'], ...($l['ids'] ?? []));
            $groepen[$id]['pp'] += (float) $l['prijs_pp'];
        } elseif ($l['type'] === 'opstart') {
            $vast[] = ['titel' => $t['lijn_opstart'], 'detail' => '', 'bedrag' => round((float) $l['prijs_pp'] * $gasten, 2)];
        } elseif ($l['type'] === 'transport') {
            $vast[] = ['titel' => sprintf($t['lijn_transport'], number_format((float) $l['extra_km'], 1, ',', '')), 'detail' => '', 'bedrag' => round((float) $l['totaal'], 2)];
        }
    }

    $rest = (float) $prijs['totaal_excl_btw'] - array_sum(array_column($vast, 'bedrag'));
    $somPP = array_sum(array_column($groepen, 'pp'));
    $lijnen = [];
    $grootste = null;
    $verdeeld = 0.0;
    foreach ($groepen as $id => $g) {
        $bedrag = $somPP > 0 ? round($rest * $g['pp'] / $somPP, 2) : 0.0;
        $verdeeld += $bedrag;
        $titel = $g['ids'] === ['pizza'] ? $t['lijn_pizzabuffet'] : $g['titel'];
        $lijnen[] = ['titel' => sprintf($t['lijn_dienst'], $titel, $gasten), 'detail' => implode(', ', $g['namen']), 'bedrag' => $bedrag];
        if ($grootste === null || $bedrag > $lijnen[$grootste]['bedrag']) {
            $grootste = count($lijnen) - 1;
        }
    }
    if ($grootste !== null) {
        $lijnen[$grootste]['bedrag'] = round($lijnen[$grootste]['bedrag'] + $rest - $verdeeld, 2);
    }
    return array_merge($vast, $lijnen);
}

function emailOfferteTabel(array $prijs, string $lang): string
{
    $t = pianocT($lang);
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:12px 0;">';
    foreach (emailOfferteLijnen($prijs, $lang) as $l) {
        $detail = $l['detail'] !== '' ? '<br><span style="color:' . PIANOC_COLOR_OLIVE . ';font-size:12px;">' . emailEsc($l['detail']) . '</span>' : '';
        $html .= '<tr style="border-bottom:1px solid rgba(74,52,42,.14);">'
            . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:8px 12px 8px 0;">' . emailEsc($l['titel']) . $detail . '</td>'
            . '<td style="font-family:Arial,Helvetica,sans-serif;font-size:14px;padding:8px 0;text-align:right;white-space:nowrap;vertical-align:top;">' . pianocEuro($l['bedrag']) . '</td>'
            . '</tr>';
    }
    $html .= '<tr><td style="font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;padding:14px 0 4px;border-top:2px solid ' . PIANOC_COLOR_TERRACOTTA . ';">' . emailEsc(sprintf($t['totaal'], $prijs['aantal_personen'])) . ' <span style="color:' . PIANOC_COLOR_OLIVE . ';font-size:12px;font-weight:normal;">' . emailEsc($t['excl']) . '</span></td>'
        . '<td style="font-family:Georgia,serif;font-size:26px;font-weight:bold;color:' . PIANOC_COLOR_TERRACOTTA . ';text-align:right;padding:14px 0 4px;border-top:2px solid ' . PIANOC_COLOR_TERRACOTTA . ';white-space:nowrap;">' . pianocEuro($prijs['totaal_excl_btw']) . '</td></tr>';
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
 * Gekozen items per categorie, als aparte rijen voor de detailtabel:
 * voorgerecht, hoofdgerecht, dessert en drank elk op hun eigen regel.
 *
 * $lang: taal van de labels en namen. Null = de taal waarin de prijs is
 * berekend (de klantmail). 'nl' voor de interne mail, ook als de klant in
 * het Engels of Italiaans aanvroeg. $alleCategorieen: ook lege categorieën
 * tonen, met '(geen)', zodat je in één oogopslag ziet wat niet gekozen is.
 *
 * @return array<string,string> label => "item, item"
 */
function emailKeuzesRijen(array $prijs, ?string $lang = null, bool $alleCategorieen = false): array
{
    $config = function_exists('pianocConfig') ? pianocConfig() : null;
    $taal = $lang ?? (string) ($prijs['lang'] ?? 'nl');
    $cats = [];
    if ($config !== null) {
        foreach ($config['categorieen'] as $cat) {
            $cats[$cat['id']] = ['label' => (string) ($cat['naam'][$taal] ?? $cat['naam']['nl']), 'namen' => []];
        }
    }
    foreach ($prijs['lijnen'] as $l) {
        if ($l['type'] !== 'item') {
            continue;
        }
        $id = (string) $l['categorie'];
        if (!isset($cats[$id])) {
            $cats[$id] = ['label' => (string) $l['categorie_naam'], 'namen' => []];
        }
        if ($config !== null && !empty($l['ids'])) {
            foreach ($l['ids'] as $itemId) {
                $item = findItem($config, (string) $itemId);
                $cats[$id]['namen'][] = $item !== null ? itemNaam($item, $taal) : (string) $itemId;
            }
        } else {
            foreach (explode(' + ', (string) $l['naam']) as $naam) {
                $cats[$id]['namen'][] = $naam;
            }
        }
    }
    $rows = [];
    foreach ($cats as $cat) {
        if (!$cat['namen'] && !$alleCategorieen) {
            continue;
        }
        $rows[$cat['label']] = $cat['namen'] ? implode(', ', $cat['namen']) : '(geen)';
    }
    return $rows;
}

/**
 * Zelfde opsplitsing voor de fallbackmail, waar enkel de ruwe item-ids
 * bekend zijn (de prijsberekening is mislukt).
 *
 * @param string[] $ids
 * @return array<string,string>
 */
function emailItemsRijenFallback(array $ids): array
{
    if (!function_exists('pianocConfig')) {
        return ['Items' => implode(', ', $ids)];
    }
    $config = pianocConfig();
    $cats = [];
    foreach ($config['categorieen'] as $cat) {
        $cats[$cat['id']] = ['label' => (string) $cat['naam']['nl'], 'namen' => []];
    }
    $onbekend = [];
    foreach ($ids as $id) {
        $item = findItem($config, (string) $id);
        if ($item === null || !isset($cats[$item['categorie']])) {
            $onbekend[] = (string) $id;
            continue;
        }
        $cats[$item['categorie']]['namen'][] = itemNaam($item, 'nl');
    }
    $rows = [];
    foreach ($cats as $cat) {
        $rows[$cat['label']] = $cat['namen'] ? implode(', ', $cat['namen']) : '(geen)';
    }
    if ($onbekend) {
        $rows['Onbekende items'] = implode(', ', $onbekend);
    }
    return $rows;
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
    ] + emailKeuzesRijen($prijs) + [
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
    $html .= emailOfferteTabel($prijs, $lang);

    $html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;background:' . PIANOC_COLOR_ESPRESSO . ';border-radius:12px;"><tr><td style="padding:22px 24px;text-align:center;">'
        . '<p style="font-size:20px;color:' . PIANOC_COLOR_CREAM . ';margin:0 0 8px;">' . emailEsc($t['proef_titel']) . '</p>'
        . '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:rgba(244,241,236,.85);margin:0 0 16px;">' . emailEsc($t['proef_tekst']) . '</p>'
        . emailKnop(PIANOC_SITE . ($lang === 'nl' ? '' : '/' . $lang) . '/pizza-buffet.html#kaart', $t['proef_knop'], PIANOC_COLOR_TERRACOTTA)
        . '</td></tr></table>';

    $html .= $p(emailEsc($t['vervolg']));
    $mailUrl = 'mailto:' . PIANOC_MAIL . '?subject=' . rawurlencode(sprintf($t['antwoord_onderwerp'], $referentie));
    $html .= '<p style="text-align:center;margin:18px 0 22px;">' . emailKnop($mailUrl, $t['antwoord'], PIANOC_COLOR_OLIVE) . '</p>';
    $html .= $p($t['groeten']);

    return emailWrapper($t['titel'], $html);
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
        'Afstand' => $prijs['afstand_bekend'] ? number_format((float) $prijs['afstand_km'], 1, ',', '') . ' km enkele rit (bron: ' . ($input['afstand_bron'] ?? 'onbekend') . ')' : 'ONBEKEND, transport niet in de prijs',
    ] + emailKeuzesRijen($prijs, 'nl', true) + [
        'Richtprijs p.p. excl. btw' => pianocEuro($prijs['prijs_pp_excl_btw']),
        'Totaal excl. btw' => pianocEuro($prijs['totaal_excl_btw']),
        'Totaal incl. btw' => pianocEuro($prijs['totaal_incl_btw']),
        'Opmerkingen' => ($input['opmerkingen'] ?? '') !== '' ? $input['opmerkingen'] : '(geen)',
        'Taal website' => emailTaalLabel($input['lang'] ?? null),
        'Bron (UTM)' => $utm ? implode(', ', array_map(static fn($k, $v) => "{$k}={$v}", array_keys($utm), $utm)) : '(geen UTM)',
        'Pagina' => $input['pagina'] ?? '',
    ];

    $html = '<p style="font-family:Arial,Helvetica,sans-serif;font-size:14px;">Nieuwe aanvraag via pianocatering.be. De klant kreeg automatisch de richtprijs hieronder, met een blok over de keuken van Piano A. Antwoorden op deze mail gaat rechtstreeks naar de klant.</p>';
    $html .= '<table cellpadding="5" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;">';
    foreach ($rows as $label => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $html .= '<tr><td style="vertical-align:top;white-space:nowrap;"><strong>' . emailEsc($label) . '</strong></td><td>' . emailEsc((string) $value) . '</td></tr>';
    }
    $html .= '</table><h3 style="font-family:Arial,Helvetica,sans-serif;font-size:14px;margin:18px 0 0;">Prijsopbouw</h3>' . emailPrijsTabel($prijs, 'nl');

    return emailWrapper('Nieuwe offerte-aanvraag Piano C', $html);
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
    ] + emailItemsRijenFallback(array_map('strval', (array) ($input['items'] ?? []))) + [
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
    return emailWrapper('Aanvraag Piano C zonder prijsberekening', $html);
}
