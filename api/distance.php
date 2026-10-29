<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

class DistanceLookupException extends RuntimeException
{
}

/**
 * Rijafstand in km (één richting) tussen het vertrekpunt en de locatie van
 * de klant, via dezelfde gratis diensten als de configurator op de site
 * (Nominatim voor het geocoderen, OSRM voor de route). Zo komt de server op
 * exact dezelfde kilometers uit als wat de klant op de site zag.
 *
 * Staat GOOGLE_MAPS_API_KEY in .env, dan wordt eerst Google Distance Matrix
 * geprobeerd (betrouwbaarder bij hoge volumes); valt die weg, dan OSRM.
 *
 * Geeft de afstand terug afgerond op 0,1 km, zoals de configurator.
 */
function getDistanceKm(string $vertrekpunt, string $bestemming): float
{
    $errors = [];
    if (env('GOOGLE_MAPS_API_KEY')) {
        try {
            return round(distanceViaGoogle($vertrekpunt, $bestemming), 1);
        } catch (DistanceLookupException $e) {
            $errors[] = 'google: ' . $e->getMessage();
        }
    }
    try {
        return round(distanceViaOsrm($vertrekpunt, $bestemming), 1);
    } catch (DistanceLookupException $e) {
        $errors[] = 'osrm: ' . $e->getMessage();
    }
    throw new DistanceLookupException(implode(' | ', $errors));
}

function httpGetJson(string $url, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
        CURLOPT_USERAGENT => 'PianoCCatering/1.0 (info@pianocatering.be)',
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($response === false) {
        throw new DistanceLookupException("Onbereikbaar: {$curlError}");
    }
    if ($status >= 400) {
        throw new DistanceLookupException("HTTP {$status}");
    }
    $data = json_decode((string) $response, true);
    if (!is_array($data)) {
        throw new DistanceLookupException('Geen geldige JSON.');
    }
    return $data;
}

/** @return array{lat:float,lon:float} */
function geocodeNominatim(string $query): array
{
    $q = preg_match('/belgi/i', $query) ? $query : $query . ', Belgium';
    $data = httpGetJson(
        'https://nominatim.openstreetmap.org/search?' . http_build_query(['q' => $q, 'format' => 'json', 'limit' => 1]),
        ['Accept-Language: nl']
    );
    if (!$data || !isset($data[0]['lat'], $data[0]['lon'])) {
        throw new DistanceLookupException("Locatie niet gevonden: {$query}");
    }
    return ['lat' => (float) $data[0]['lat'], 'lon' => (float) $data[0]['lon']];
}

function distanceViaOsrm(string $vertrekpunt, string $bestemming): float
{
    $from = geocodeNominatim($vertrekpunt);
    $to = geocodeNominatim($bestemming);
    $data = httpGetJson(sprintf(
        'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F?overview=false',
        $from['lon'],
        $from['lat'],
        $to['lon'],
        $to['lat']
    ));
    if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0]['distance'])) {
        throw new DistanceLookupException('Geen route gevonden.');
    }
    return (float) $data['routes'][0]['distance'] / 1000;
}

function distanceViaGoogle(string $vertrekpunt, string $bestemming): float
{
    $data = httpGetJson('https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
        'origins' => $vertrekpunt,
        'destinations' => $bestemming,
        'units' => 'metric',
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ]));
    if (($data['status'] ?? 'UNKNOWN') !== 'OK') {
        throw new DistanceLookupException('Status ' . ($data['status'] ?? 'UNKNOWN'));
    }
    $element = $data['rows'][0]['elements'][0] ?? null;
    if (!$element || ($element['status'] ?? '') !== 'OK') {
        throw new DistanceLookupException('Kon geen route naar dit adres vinden.');
    }
    return $element['distance']['value'] / 1000;
}
