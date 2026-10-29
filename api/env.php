<?php
declare(strict_types=1);

/**
 * Laadt een .env-bestand (KEY=VALUE per lijn) in getenv()/$_ENV, als de key
 * nog niet elders (bv. via Plesk's PHP-instellingen) gezet is.
 * Overgenomen van de Pretty Nice Dough-backend; enkel de variabelenaam voor
 * het pad verschilt (PIANOC_ENV_FILE).
 */
function loadEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

/**
 * Zoekt het .env-bestand op de gebruikelijke plaatsen: het pad in
 * PIANOC_ENV_FILE (te zetten in Plesk, PHP-instellingen, "Additional
 * configuration directives": env[PIANOC_ENV_FILE] = /volledig/pad/.env),
 * anders één niveau boven de site-root, anders in de repo-root zelf
 * (die laatste wordt door .htaccess geweigerd voor directe toegang).
 */
function loadPianoCEnv(): void
{
    foreach (array_filter([
        env('PIANOC_ENV_FILE'),
        dirname(__DIR__, 2) . '/.env',
        dirname(__DIR__) . '/.env',
    ]) as $candidate) {
        loadEnv($candidate);
    }
}
