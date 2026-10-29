<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

class MailException extends RuntimeException
{
}

/**
 * Mails vertrekken rechtstreeks via de mailserver van de hosting (PHP's
 * mail()), zonder extern account. Overgenomen van Pretty Nice Dough.
 *
 * Vereist dat de hosting correct is ingesteld om als pianocatering.be te
 * mogen mailen (SPF, DKIM en DMARC via het hostingpaneel), anders belanden
 * mails in spam. Zie README-DEPLOY.md.
 */

const PIANOC_DEFAULT_FROM_EMAIL = 'info@pianocatering.be';
const PIANOC_DEFAULT_FROM_NAME = 'Piano C Catering';
const PIANOC_DEFAULT_NOTIFY = 'viktor@prettynicedough.be';

function mailSafeHeaderValue(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

function mailEncodeHeader(string $value): string
{
    return mb_encode_mimeheader(mailSafeHeaderValue($value), 'UTF-8', 'B', "\r\n");
}

function mailFromEmail(): string
{
    return env('MAIL_FROM_EMAIL', PIANOC_DEFAULT_FROM_EMAIL) ?: PIANOC_DEFAULT_FROM_EMAIL;
}

function mailFromName(): string
{
    return env('MAIL_FROM_NAME', PIANOC_DEFAULT_FROM_NAME) ?: PIANOC_DEFAULT_FROM_NAME;
}

/**
 * Stuurt één HTML-mail. Reply-To valt terug op het afzenderadres.
 */
function sendMail(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $replyToEmail = null,
    ?string $replyToName = null
): void {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        throw new MailException("Ongeldig ontvanger-adres: {$toEmail}");
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: quoted-printable';
    $headers[] = 'From: ' . mailEncodeHeader(mailFromName()) . ' <' . mailFromEmail() . '>';
    if ($replyToEmail && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
        $replyToName ??= $replyToEmail;
        $headers[] = 'Reply-To: ' . mailEncodeHeader($replyToName) . ' <' . $replyToEmail . '>';
    }

    $to = mailEncodeHeader($toName) . ' <' . $toEmail . '>';
    $encodedSubject = mailEncodeHeader($subject);
    $encodedBody = quoted_printable_encode($htmlBody);

    error_clear_last();
    $ok = @mail($to, $encodedSubject, $encodedBody, implode("\r\n", $headers));
    if (!$ok) {
        $error = error_get_last();
        throw new MailException('mail() gaf false terug: ' . ($error['message'] ?? 'onbekende fout'));
    }
}

/**
 * Ontvangers van de interne meldmails: komma-gescheiden lijst in
 * NOTIFY_EMAILS (.env). Leeg = enkel viktor@prettynicedough.be, want daar
 * leest Sofie de aanvragen voor het Klantenbord.
 *
 * @return string[]
 */
function notifyRecipients(): array
{
    $raw = (string) env('NOTIFY_EMAILS', '');
    $emails = [];
    foreach (explode(',', $raw) as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            $emails[strtolower($candidate)] ??= $candidate;
        }
    }
    return $emails ? array_values($emails) : [PIANOC_DEFAULT_NOTIFY];
}

/**
 * Interne meldmail naar alle ontvangers, elk apart. Reply-To = de klant.
 *
 * @return array<string,string> mislukte ontvangers: adres => fout (leeg = alles verstuurd)
 */
function sendNotifyMail(string $subject, string $htmlBody, ?string $replyToEmail, ?string $replyToName): array
{
    $failures = [];
    foreach (notifyRecipients() as $toEmail) {
        try {
            sendMail($toEmail, 'Piano C', $subject, $htmlBody, $replyToEmail, $replyToName);
        } catch (MailException $e) {
            $failures[$toEmail] = $e->getMessage();
        }
    }
    return $failures;
}
