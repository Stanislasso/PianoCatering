<?php
declare(strict_types=1);

/**
 * Endpoint voor het formulier op offerte-aanvraag.html (NL, EN, IT).
 * Ontvangt multipart/form-data of x-www-form-urlencoded, antwoordt in JSON.
 * De pagina stuurt bij succes door naar bedankt.html.
 */

header('Content-Type: application/json; charset=utf-8');

set_exception_handler(static function (Throwable $e): void {
    error_log('aanvraag.php fatal: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Interne fout. Probeer het later opnieuw of mail naar info@pianocatering.be.']);
    exit;
});

require_once __DIR__ . '/bootstrap.php';

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'error' => 'Methode niet toegestaan.']);
}

// Honeypot: echte gebruikers laten dit veld leeg.
if (trim((string) ($_POST['website'] ?? '')) !== '' || trim((string) ($_POST['_honey'] ?? '')) !== '') {
    respond(400, ['ok' => false, 'error' => 'Aanvraag geweigerd.']);
}

if (!pianocRateLimitOk((string) ($_SERVER['REMOTE_ADDR'] ?? 'onbekend'))) {
    header('Retry-After: 3600');
    respond(429, ['ok' => false, 'error' => 'Te veel aanvragen vanaf dit adres. Probeer het over een uur opnieuw of mail naar info@pianocatering.be.']);
}

$norm = pianocNormaliseer($_POST);
if ($norm['fouten']) {
    respond(422, ['ok' => false, 'error' => implode(' ', $norm['fouten'])]);
}

$result = pianocVerwerk($norm['input']);

if (!$result['ok']) {
    respond($result['status'], ['ok' => false, 'lead_captured' => true, 'referentie' => $result['referentie'], 'error' => $result['fout']]);
}

if (!$result['mail_ok']) {
    // De klantmail faalde, maar de aanvraag is gelogd en intern gemeld: de
    // klant mag gewoon naar de bedankpagina, het team volgt manueel op.
    respond(200, ['ok' => true, 'referentie' => $result['referentie'], 'klantmail_verstuurd' => false, 'prijs_pp_excl_btw' => $result['prijs']['prijs_pp_excl_btw'], 'totaal_excl_btw' => $result['prijs']['totaal_excl_btw']]);
}

respond(200, [
    'ok' => true,
    'referentie' => $result['referentie'],
    'klantmail_verstuurd' => true,
    'afstand_km' => $result['afstand_km'],
    'prijs_pp_excl_btw' => $result['prijs']['prijs_pp_excl_btw'],
    'totaal_excl_btw' => $result['prijs']['totaal_excl_btw'],
    'totaal_incl_btw' => $result['prijs']['totaal_incl_btw'],
]);
