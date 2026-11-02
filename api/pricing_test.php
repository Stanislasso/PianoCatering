<?php
declare(strict_types=1);

/**
 * Controleert dat pricing.php exact hetzelfde geeft als de configurator op
 * offerte-aanvraag.html. De verwachte waarden zijn met de hand berekend
 * volgens de regels van het script op die pagina en daarna gecontroleerd in
 * de browser (hidden fields prijs_pp en prijs_totaal).
 *
 * Draaien: php api/pricing_test.php
 */

require __DIR__ . '/pricing.php';

$config = loadPricingConfig(__DIR__ . '/../pricing-config.json');

$scenarios = [
    [
        'naam' => '1. 30 personen, enkel pizza, binnen gratis zone',
        'input' => ['aantal_personen' => 30, 'items' => ['pizza'], 'afstand_km' => 8.0],
        // pizza 24 + opstart 200/30 = 30,6667 -> pp 30,67, totaal 920,00
        'pp' => 30.67, 'totaal' => 920.00,
    ],
    [
        'naam' => '2. 40 personen, pizza + cime di rapa, charcuterie, tiramisu, water + wijn',
        'input' => ['aantal_personen' => 40, 'items' => ['charcuterie', 'pizza', 'cime-di-rapa', 'tiramisu', 'water', 'wijn'], 'afstand_km' => 8.0],
        // hoofd: pizza 23 (staffel 35+) en 24, gemiddelde 23,5 x 1,25 = 29,38; aperitief 15; dessert 10;
        // drank: water 5 + wijn 10 = 15; opstart 5. Som 74,38, korting 3 % -> pp 72,15, totaal 2885,94
        'pp' => 72.15, 'totaal' => 2885.94, 'btw' => 398.69,
    ],
    [
        'naam' => '3. 100 personen, pizza + parmigiana + kreeft, 35 km (50 km transport)',
        'input' => ['aantal_personen' => 100, 'items' => ['pizza', 'parmigiana', 'pasta-kreeft'], 'afstand_km' => 35.0],
        // hoofd: (21+25+30)/3 = 25,3333 x 1,30 = 32,93; opstart 2; transport 50 x 1,35 = 67,50 / 100 = 0,675
        // som 35,605, korting 12 % -> 31,3324 -> pp 31,33, totaal 3133,24
        'pp' => 31.33, 'totaal' => 3133.24,
    ],
    [
        'naam' => '4. 15 personen (onder minimum 20), enkel pizza',
        'input' => ['aantal_personen' => 15, 'items' => ['pizza'], 'afstand_km' => 0.0],
        // opstart verdeeld over 20: 10; pizza 24 -> pp 34,00, totaal 15 x 34 = 510,00
        'pp' => 34.00, 'totaal' => 510.00, 'minimum' => true,
    ],
    [
        'naam' => '5. 55 personen, pizza + alle vijf de dranken',
        'input' => ['aantal_personen' => 55, 'items' => ['pizza', 'water', 'frisdrank', 'bier-pils', 'wijn', 'prosecco'], 'afstand_km' => null],
        // pizza 23; drank: water 5 + prosecco 12 + wijn 3 + bier 2,25 + fris 1,5 = 23,75; opstart 200/55 = 3,6364
        // som 50,3864, korting 6 % -> 47,3632 -> pp 47,36; totaal 2604,975 ligt in binaire notatie
        // net onder ,975, dus toFixed(2) geeft 2604,97 (gecontroleerd in de browser). PHP's round()
        // zou 2604,98 geven, daarom jsToFixed2().
        'pp' => 47.36, 'totaal' => 2604.97,
    ],
    [
        'naam' => '6. 20 personen, alles aangevinkt (3 voorgerechten, 5 hoofd, 4 desserts, 5 dranken), 60 km',
        'input' => ['aantal_personen' => 20, 'items' => ['charcuterie', 'vitello', 'carpaccio', 'pizza', 'parmigiana', 'cime-di-rapa', 'pasta-kreeft', 'cacio-e-pepe', 'tiramisu', 'panna-cotta', 'chocolademousse', 'cheesecake', 'water', 'frisdrank', 'bier-pils', 'wijn', 'prosecco'], 'afstand_km' => 60.0],
        // aperitief: (15+10+10)/3 = 11,6667 x 1,30 = 15,17; hoofd: (24+25+24+30+26)/5 = 25,8 x 1,38 = 35,60
        // dessert: 10 x 1,35 = 13,50; drank 23,75; opstart 10; transport 100 x 1,35 = 135 / 20 = 6,75
        // som 104,77, geen korting -> pp 104,77, totaal 2095,40
        'pp' => 104.77, 'totaal' => 2095.40,
    ],
    [
        'naam' => '7. 75 personen, enkel cacio e pepe (geen pizza), exact 10 km (grens gratis transport)',
        'input' => ['aantal_personen' => 75, 'items' => ['cacio-e-pepe'], 'afstand_km' => 10.0],
        // 26 + opstart 2,6667 = 28,6667, korting 9 % -> 26,0867 -> pp 26,09, totaal 1956,50
        'pp' => 26.09, 'totaal' => 1956.50,
    ],
    [
        'naam' => '8. 20 personen, enkel pizza, 30 km enkele rit (60 km heen en terug, 40 km transport)',
        'input' => ['aantal_personen' => 20, 'items' => ['pizza'], 'afstand_km' => 30.0],
        // pizza 24 + opstart 10 + transport 40 x 1,35 = 54 / 20 = 2,70 -> pp 36,70, totaal 734,00
        'pp' => 36.70, 'totaal' => 734.00,
    ],
];

$fails = 0;
foreach ($scenarios as $s) {
    $r = calculatePrice($config, $s['input'] + ['lang' => 'nl']);
    $ok = abs($r['prijs_pp_excl_btw'] - $s['pp']) < 0.001 && abs($r['totaal_excl_btw'] - $s['totaal']) < 0.001;
    if ($ok && isset($s['btw'])) {
        $ok = abs($r['btw_totaal'] - $s['btw']) < 0.01;
    }
    if ($ok && isset($s['minimum'])) {
        $ok = $r['minimum_toegepast'] === $s['minimum'];
    }
    printf("%-4s %-90s pp %.2f / totaal %.2f (kreeg %.2f / %.2f, btw %.2f)\n", $ok ? 'OK' : 'FAIL', $s['naam'], $s['pp'], $s['totaal'], $r['prijs_pp_excl_btw'], $r['totaal_excl_btw'], $r['btw_totaal']);
    if (!$ok) {
        $fails++;
        print_r($r);
    }
}

// Foutpaden
try {
    calculatePrice($config, ['aantal_personen' => 30, 'items' => ['tiramisu']]);
    echo "FAIL zonder hoofdgerecht moet een fout geven\n";
    $fails++;
} catch (InvalidArgumentException $e) {
    echo "OK   zonder hoofdgerecht: " . $e->getMessage() . "\n";
}
try {
    calculatePrice($config, ['aantal_personen' => 30, 'items' => ['pizza', 'bestaat-niet']]);
    echo "FAIL onbekend item moet een fout geven\n";
    $fails++;
} catch (InvalidArgumentException $e) {
    echo "OK   onbekend item: " . $e->getMessage() . "\n";
}

echo "\n" . ($fails === 0 ? "Alle scenario's kloppen." : "{$fails} scenario('s) FAILED.") . "\n";
exit($fails === 0 ? 0 : 1);
