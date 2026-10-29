<?php
declare(strict_types=1);

/**
 * JSON-endpoint voor offerte-aanvragen door tools en agents (bv. Sofie bij
 * een aanvraag via mail of telefoon), naast het formulier (aanvraag.php).
 * Zelfde prijslogica, zelfde mails; beschreven in /openapi.json.
 *
 * POST application/json -> {"status":"ok","referentie":...,"prijs_pp_excl_btw":...,"totaal_excl_btw":...,"btw":...,"totaal_incl_btw":...,"details":{...}}
 * Fout                  -> HTTP 4xx/5xx met {"status":"error","fouten":[...]}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

set_exception_handler(static function (Throwable $e): void {
    error_log('offerte.php fatal: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'fouten' => ['Interne fout. Probeer het later opnieuw of mail naar info@pianocatering.be.']]);
    exit;
});

require_once __DIR__ . '/bootstrap.php';

function offerteRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function offerteFout(int $status, array $fouten): void
{
    offerteRespond($status, ['status' => 'error', 'fouten' => array_values($fouten)]);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($method !== 'POST') {
    header('Allow: POST, OPTIONS');
    offerteFout(405, ['Gebruik POST met Content-Type: application/json.']);
}
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
if (!str_starts_with($contentType, 'application/json')) {
    offerteFout(415, ['Content-Type moet application/json zijn.']);
}
if (!pianocRateLimitOk((string) ($_SERVER['REMOTE_ADDR'] ?? 'onbekend'))) {
    header('Retry-After: 3600');
    offerteFout(429, ['Te veel aanvragen vanaf dit adres. Probeer het over een uur opnieuw of mail naar info@pianocatering.be.']);
}

$raw = file_get_contents('php://input', false, null, 0, 65536);
$body = json_decode($raw === false ? '' : $raw, true);
if (!is_array($body)) {
    offerteFout(400, ['Body is geen geldig JSON-object.']);
}
if (trim((string) ($body['website'] ?? '')) !== '') {
    offerteFout(400, ['Aanvraag geweigerd.']);
}

$norm = pianocNormaliseer($body);
if ($norm['fouten']) {
    offerteFout(400, $norm['fouten']);
}

$result = pianocVerwerk($norm['input']);

if (!$result['ok']) {
    offerteRespond($result['status'], ['status' => 'error', 'fouten' => [$result['fout']], 'lead_captured' => true, 'referentie' => $result['referentie']]);
}

$prijs = $result['prijs'];
offerteRespond(200, [
    'status' => 'ok',
    'referentie' => $result['referentie'],
    'prijs_pp_excl_btw' => $prijs['prijs_pp_excl_btw'],
    'totaal_excl_btw' => $prijs['totaal_excl_btw'],
    'btw' => $prijs['btw_totaal'],
    'totaal_incl_btw' => $prijs['totaal_incl_btw'],
    'details' => [
        'datum' => $norm['input']['datum'],
        'locatie' => $norm['input']['locatie'],
        'afstand_km' => $result['afstand_km'],
        'aantal_personen' => $prijs['aantal_personen'],
        'minimum_toegepast' => $prijs['minimum_toegepast'],
        'min_gasten' => $prijs['min_gasten'],
        'korting_percentage' => $prijs['korting_percentage'],
        'opstartkost' => $prijs['opstartkost'],
        'transportkost' => $prijs['transportkost'],
        'lijnen' => $prijs['lijnen'],
        'klantmail_verstuurd' => $result['mail_ok'],
    ],
]);
