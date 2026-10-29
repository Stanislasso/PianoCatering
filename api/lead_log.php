<?php
declare(strict_types=1);

/**
 * Vangnet: elke aanvraag (geslaagd of mislukt) wordt weggeschreven naar één
 * CSV-bestand op de server, één niveau boven de site-root (dus buiten de
 * webroot). Mocht hij toch in de webroot belanden, dan weigert .htaccess
 * directe toegang. Overgenomen van Pretty Nice Dough.
 *
 * Downloaden en openen in Numbers, Excel of Google Sheets geeft de volledige
 * leadlijst, ook als een mail ooit niet aankwam.
 */
function leadLogPath(): string
{
    return env('PIANOC_LEAD_LOG') ?: dirname(__DIR__, 2) . '/aanvragen-fallback.csv';
}

function logLead(string $bron, array $input, string $status, string $detail = '', ?float $totaalExclBtw = null, ?float $prijsPp = null): void
{
    $logPath = leadLogPath();
    $isNew = !is_file($logPath);
    $fh = @fopen($logPath, 'a');
    if ($fh === false) {
        return;
    }
    // Expliciete escape-parameter: PHP 8.4 geeft anders een deprecation per rij.
    if ($isNew) {
        fputcsv($fh, [
            'tijdstip', 'bron', 'status', 'detail', 'naam', 'email', 'telefoon', 'datum_event',
            'aantal_personen', 'locatie', 'afstand_km', 'items', 'lang', 'prijs_pp_excl_btw', 'totaal_excl_btw',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'ruwe_data',
        ], ',', '"', '');
    }
    fputcsv($fh, [
        date('c'),
        $bron,
        $status,
        $detail,
        $input['naam'] ?? '',
        $input['email'] ?? '',
        $input['telefoon'] ?? '',
        $input['datum'] ?? '',
        $input['aantal_personen'] ?? '',
        $input['locatie'] ?? '',
        $input['afstand_km'] ?? '',
        is_array($input['items'] ?? null) ? implode('|', $input['items']) : ($input['items'] ?? ''),
        $input['lang'] ?? '',
        $prijsPp !== null ? number_format($prijsPp, 2, ',', '') : '',
        $totaalExclBtw !== null ? number_format($totaalExclBtw, 2, ',', '') : '',
        $input['utm_source'] ?? '',
        $input['utm_medium'] ?? '',
        $input['utm_campaign'] ?? '',
        $input['utm_term'] ?? '',
        json_encode($input, JSON_UNESCAPED_UNICODE),
    ], ',', '"', '');
    fclose($fh);
}
