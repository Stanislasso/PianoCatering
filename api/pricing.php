<?php
declare(strict_types=1);

/**
 * Prijsberekening Piano C Catering.
 *
 * Dit is de serverversie van de configurator op offerte-aanvraag.html. De
 * rekenregels en de volgorde van de bewerkingen zijn bewust identiek aan het
 * JavaScript op die pagina, inclusief de afrondingen, zodat de richtprijs
 * die de klant op de site zag exact terugkomt in de mail.
 *
 * Prijzen zijn per persoon en exclusief btw (12 % eten, 21 % drank).
 */

function loadPricingConfig(string $path): array
{
    $json = file_get_contents($path);
    if ($json === false) {
        throw new RuntimeException("Kan pricing-config.json niet lezen: {$path}");
    }
    $config = json_decode($json, true);
    if (!is_array($config)) {
        throw new RuntimeException('pricing-config.json bevat ongeldige JSON.');
    }
    return $config;
}

function findItem(array $config, string $id): ?array
{
    foreach ($config['items'] as $item) {
        if (($item['id'] ?? null) === $id) {
            return $item;
        }
    }
    return null;
}

/**
 * Naam van een item in de gevraagde taal (valt terug op NL).
 */
function itemNaam(array $item, string $lang): string
{
    return (string) ($item['naam'][$lang] ?? $item['naam']['nl'] ?? $item['id']);
}

/**
 * Zelfde afronding als Math.round(x*100)/100 in JavaScript: half naar boven,
 * op de dubbele-precisie waarde zelf (dus zonder de "pre-rounding" van PHP's
 * round()).
 */
function jsRound2(float $x): float
{
    return floor($x * 100 + 0.5) / 100;
}

/**
 * Zelfde resultaat als Number.prototype.toFixed(2): afronden op basis van de
 * exacte binaire waarde, bij een exacte tie naar boven. PHP's number_format
 * en round() doen "pre-rounding" en geven op randgevallen (bv. 47,705) een
 * andere cent. Die randgevallen zijn zeldzaam, maar de mail moet exact
 * tonen wat de klant op de site zag.
 */
function jsToFixed2(float $x): float
{
    $s = sprintf('%.20F', $x);
    [$int, $frac] = array_pad(explode('.', $s, 2), 2, '');
    $frac = str_pad($frac, 20, '0');
    $keep = (int) ($int . substr($frac, 0, 2));
    $rest = substr($frac, 2);
    if ($rest !== '' && $rest[0] >= '5') {
        $keep += 1;
    }
    return $keep / 100;
}

function pizzaPrijs(array $config, int $guests): float
{
    foreach ($config['pizza_staffel'] as $t) {
        if ($guests >= (int) $t['min']) {
            return (float) $t['prijs_pp'];
        }
    }
    return 24.0;
}

function combiOpslag(array $config, int $n): float
{
    if ($n < 2) {
        return 0.0;
    }
    $key = (string) min($n, 6);
    return (float) ($config['combi_opslag'][$key] ?? 0.40);
}

function groepskorting(array $config, int $guests): float
{
    foreach ($config['groepskorting'] as $t) {
        if ($guests >= (int) $t['min']) {
            return (float) $t['korting'];
        }
    }
    return 0.0;
}

/**
 * Transportkost (totaalbedrag) voor een rijafstand in km, zoals de
 * configurator: enkel de km boven de gratis zone, afgerond op de cent.
 */
function transportTotaal(array $config, ?float $afstandKm): float
{
    if ($afstandKm === null) {
        return 0.0;
    }
    $gratis = (float) $config['transport']['gratis_km'];
    if ($afstandKm <= $gratis) {
        return 0.0;
    }
    return jsRound2(($afstandKm - $gratis) * (float) $config['transport']['prijs_per_km']);
}

/**
 * Prijs van één categorie (niet drank): één keuze = die prijs, meerdere
 * keuzes = gemiddelde plus combi-opslag.
 *
 * @param array<int,array{item:array,prijs:float}> $items
 */
function prijsCategorie(array $config, array $items): array
{
    if (!$items) {
        return ['totaal' => 0.0, 'lijnen' => []];
    }
    if (count($items) === 1) {
        return ['totaal' => $items[0]['prijs'], 'lijnen' => [['items' => [$items[0]['item']], 'prijs_pp' => $items[0]['prijs'], 'opslag' => 0.0]]];
    }
    $n = count($items);
    $sum = 0.0;
    foreach ($items as $i) {
        $sum += $i['prijs'];
    }
    $avg = $sum / $n;
    $opslag = combiOpslag($config, $n);
    $value = jsRound2($avg * (1 + $opslag));
    return ['totaal' => $value, 'lijnen' => [['items' => array_column($items, 'item'), 'prijs_pp' => $value, 'opslag' => $opslag]]];
}

/**
 * Prijs van de drankcategorie: water altijd volledig, de duurste andere
 * drank volledig, elke extra drank voor 30 %.
 *
 * @param array<int,array{item:array,prijs:float}> $items
 */
function prijsDrank(array $config, array $items): array
{
    if (!$items) {
        return ['totaal' => 0.0, 'lijnen' => []];
    }
    $waterId = (string) $config['drank']['water_id'];
    $extra = (float) $config['drank']['extra_aandeel'];
    $water = null;
    $others = [];
    foreach ($items as $i) {
        if ($i['item']['id'] === $waterId) {
            $water = $i;
        } else {
            $others[] = $i;
        }
    }
    $totaal = 0.0;
    $lijnen = [];
    if ($water) {
        $totaal += $water['prijs'];
        $lijnen[] = ['items' => [$water['item']], 'prijs_pp' => $water['prijs'], 'opslag' => 0.0];
    }
    if (!$others) {
        return ['totaal' => jsRound2($totaal), 'lijnen' => $lijnen];
    }
    if (count($others) === 1) {
        $totaal += $others[0]['prijs'];
        $lijnen[] = ['items' => [$others[0]['item']], 'prijs_pp' => $others[0]['prijs'], 'opslag' => 0.0];
        return ['totaal' => jsRound2($totaal), 'lijnen' => $lijnen];
    }
    // Stabiele sortering op prijs, aflopend (zoals Array.prototype.sort in moderne browsers).
    usort($others, static fn(array $a, array $b): int => $b['prijs'] <=> $a['prijs']);
    $totaal += $others[0]['prijs'];
    $lijnen[] = ['items' => [$others[0]['item']], 'prijs_pp' => $others[0]['prijs'], 'opslag' => 0.0];
    for ($i = 1, $n = count($others); $i < $n; $i++) {
        $contrib = jsRound2($others[$i]['prijs'] * $extra);
        $totaal += $contrib;
        $lijnen[] = ['items' => [$others[$i]['item']], 'prijs_pp' => $contrib, 'opslag' => $extra, 'extra' => true];
    }
    return ['totaal' => jsRound2($totaal), 'lijnen' => $lijnen];
}

/**
 * Berekent de volledige prijsopbouw voor een aanvraag.
 *
 * $input:
 *   aantal_personen: int
 *   items: string[]   ids uit pricing-config.json, in de volgorde van de configurator
 *   afstand_km: float|null   rijafstand in één richting, al afgerond op 0,1 km
 *   lang: 'nl'|'en'|'it'   enkel voor de namen in de lijnen
 *
 * Geeft terug: lijnen per categorie, prijs per persoon en totaal excl. btw
 * (beide exact zoals de configurator ze toont), de btw-opsplitsing en het
 * totaal incl. btw, plus de vlaggen voor het minimum en de afstand.
 */
function calculatePrice(array $config, array $input): array
{
    $lang = in_array($input['lang'] ?? 'nl', ['nl', 'en', 'it'], true) ? ($input['lang'] ?? 'nl') : 'nl';
    $guests = max(1, (int) ($input['aantal_personen'] ?? 0));
    $minGasten = (int) $config['min_gasten'];
    $billable = max($guests, $minGasten);
    $afstandKm = isset($input['afstand_km']) && $input['afstand_km'] !== null && $input['afstand_km'] !== ''
        ? max(0.0, (float) $input['afstand_km'])
        : null;

    // Items opzoeken, dubbels negeren, onbekende ids weigeren. De volgorde is
    // die van pricing-config.json (= de volgorde op de pagina), zodat ook de
    // optelvolgorde dezelfde is als in de configurator.
    $gevraagd = array_unique(array_map('strval', (array) ($input['items'] ?? [])));
    foreach ($gevraagd as $id) {
        if (findItem($config, $id) === null) {
            throw new InvalidArgumentException("Onbekend item: {$id}");
        }
    }
    $gekozen = [];
    foreach ($config['items'] as $item) {
        if (!in_array($item['id'], $gevraagd, true)) {
            continue;
        }
        $prijs = isset($item['staffel']) ? pizzaPrijs($config, $guests) : (float) $item['prijs_pp'];
        $gekozen[] = ['item' => $item, 'prijs' => $prijs];
    }

    $perCategorie = [];
    foreach ($config['categorieen'] as $cat) {
        $perCategorie[$cat['id']] = array_values(array_filter($gekozen, static fn(array $g): bool => $g['item']['categorie'] === $cat['id']));
    }
    $verplichtOntbreekt = [];
    foreach ($config['categorieen'] as $cat) {
        if (!empty($cat['verplicht']) && !$perCategorie[$cat['id']]) {
            $verplichtOntbreekt[] = $cat['id'];
        }
    }
    if ($verplichtOntbreekt) {
        throw new InvalidArgumentException('Kies minstens één hoofdgerecht.');
    }

    // Zelfde volgorde van optellen als de configurator: categorieën in
    // DOM-volgorde, dan opstart, dan transport.
    $foodPP = 0.0;   // alles wat onder 12 % valt, per persoon, vóór korting
    $drinksPP = 0.0; // alles wat onder 21 % valt, per persoon, vóór korting
    $somPP = 0.0;    // exact de "food"-variabele uit het script (alles samen)
    $lijnen = [];
    foreach ($config['categorieen'] as $cat) {
        $items = $perCategorie[$cat['id']];
        if (!$items) {
            continue;
        }
        $r = $cat['id'] === 'drank' ? prijsDrank($config, $items) : prijsCategorie($config, $items);
        $somPP += $r['totaal'];
        if ($cat['btw_categorie'] === 'drinks') {
            $drinksPP += $r['totaal'];
        } else {
            $foodPP += $r['totaal'];
        }
        foreach ($r['lijnen'] as $l) {
            $lijnen[] = [
                'type' => 'item',
                'categorie' => $cat['id'],
                'categorie_naam' => (string) ($cat['naam'][$lang] ?? $cat['naam']['nl']),
                'naam' => implode(' + ', array_map(static fn(array $it): string => itemNaam($it, $lang), $l['items'])),
                'ids' => array_column($l['items'], 'id'),
                'prijs_pp' => $l['prijs_pp'],
                'opslag' => $l['opslag'],
                'extra' => !empty($l['extra']),
                'btw_categorie' => $cat['btw_categorie'],
            ];
        }
    }

    $opstartTotaal = (float) $config['opstartkost'];
    $opstartPP = $opstartTotaal / $billable;
    $somPP += $opstartPP;
    $foodPP += $opstartPP;
    $lijnen[] = ['type' => 'opstart', 'naam' => 'opstart', 'prijs_pp' => $opstartPP, 'totaal' => $opstartTotaal, 'verdeeld_over' => $billable, 'btw_categorie' => 'food'];

    $transportTotaal = transportTotaal($config, $afstandKm);
    $transportPP = 0.0;
    if ($transportTotaal > 0) {
        $transportPP = $transportTotaal / $guests;
        $somPP += $transportPP;
        $foodPP += $transportPP;
        $lijnen[] = [
            'type' => 'transport',
            'naam' => 'transport',
            'prijs_pp' => $transportPP,
            'totaal' => $transportTotaal,
            'afstand_km' => $afstandKm,
            'extra_km' => $afstandKm - (float) $config['transport']['gratis_km'],
            'btw_categorie' => 'food',
        ];
    }

    $korting = groepskorting($config, $guests);
    $kortingPP = $somPP * $korting;
    $perPersoon = $somPP - $kortingPP;
    $totaalExcl = $perPersoon * $guests;

    // Btw-opsplitsing: de korting geldt op alles, dus evenredig op eten en drank.
    $foodExcl = $foodPP * (1 - $korting) * $guests;
    $drinksExcl = $drinksPP * (1 - $korting) * $guests;
    $btwFood = $foodExcl * (float) $config['btw']['food'];
    $btwDrinks = $drinksExcl * (float) $config['btw']['drinks'];

    return [
        'lang' => $lang,
        'aantal_personen' => $guests,
        'aantal_gefactureerd_opstart' => $billable,
        'minimum_toegepast' => $guests < $minGasten,
        'min_gasten' => $minGasten,
        'afstand_km' => $afstandKm,
        'afstand_bekend' => $afstandKm !== null,
        'lijnen' => $lijnen,
        'korting_percentage' => $korting,
        'korting_pp' => $kortingPP,
        'prijs_pp_excl_btw' => jsToFixed2($perPersoon),
        'totaal_excl_btw' => jsToFixed2($totaalExcl),
        'subtotaal_food_excl_btw' => round($foodExcl, 2),
        'subtotaal_drinks_excl_btw' => round($drinksExcl, 2),
        'btw_food' => round($btwFood, 2),
        'btw_drinks' => round($btwDrinks, 2),
        'btw_totaal' => round($btwFood + $btwDrinks, 2),
        'totaal_incl_btw' => round($totaalExcl + $btwFood + $btwDrinks, 2),
        'opstartkost' => $opstartTotaal,
        'transportkost' => $transportTotaal,
    ];
}
