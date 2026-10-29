# Piano C: backend en deploy

De site is statische HTML met één PHP-backend in `api/` voor het offerteformulier. Zelfde opzet als Pretty Nice Dough (zelfde hoster, Plesk, PHP 8.2 of hoger met curl en mbstring): geen Make, geen Brevo, geen database. Mails vertrekken via `mail()` van de hosting, elke aanvraag wordt ook in een CSV bewaard.

## Wat de backend doet

1. Het formulier op `offerte-aanvraag.html` (ook `en/` en `it/`) post met fetch naar `/api/aanvraag.php`. De pagina stuurt de gekozen items als ids mee (`items`), het aantal personen, datum, locatie, de afstand die de site zelf al berekende (`afstand_km`, enkel als vangnet), de contactgegevens en de UTM-parameters.
2. `api/bootstrap.php` valideert, zoekt de rijafstand vanaf Piano A op (Nominatim en OSRM, dezelfde gratis diensten als de site, of Google Distance Matrix als er een sleutel in `.env` staat) en rekent de prijs met `api/pricing.php` en `pricing-config.json`. De rekenregels en afrondingen zijn identiek aan het script op de pagina, zodat de mail exact de richtprijs toont die de klant zag.
3. De klant krijgt een mail met de richtprijs, de keuzes, de uitnodiging om eerst te proeven in Piano A en een WhatsApp-knop (`api/email_templates.php`, NL, EN, IT).
4. `NOTIFY_EMAILS` krijgt de interne melding "Nieuwe offerte-aanvraag Piano C [NL] (naam)" met alle gegevens in een tabel, Reply-To is de klant. Dit is wat Sofie leest om de lead in het Klantenbord te zetten, met merk Piano C.
5. `api/lead_log.php` schrijft elke aanvraag (ook mislukte) naar `aanvragen-fallback.csv`, één niveau boven de webroot.
6. `api/offerte.php` is hetzelfde proces als JSON-endpoint voor agents en tools, beschreven in `openapi.json`.

Lukt de afstand niet, dan gebruikt de server de afstand van de site; lukt ook dat niet, dan vertrekt de mail zonder transport met een duidelijke vermelding. Lukt de prijsberekening zelf niet (bv. geen hoofdgerecht), dan krijgt het team een fallbackmail met de ruwe gegevens en de klant een nette melding dat we persoonlijk antwoorden.

## Deploy (Plesk)

1. Upload de volledige repo naar de webroot (`httpdocs`), inclusief `api/`, `pricing-config.json`, `openapi.json` en `.htaccess`. Niet uploaden: `.env`, `README-DEPLOY.md`, `LEADFLOW-CHECKLIST.md`, `PROMPTS.md` (ze worden door `.htaccess` sowieso geweigerd).
2. Maak `.env` aan op basis van `.env.example` en zet hem één niveau boven de webroot (naast `httpdocs`). Werkt dat pad niet, zet dan in Plesk bij PHP-instellingen, "Additional configuration directives": `env[PIANOC_ENV_FILE] = /volledig/pad/.env`.
3. Controleer in Plesk dat PHP 8.2 of hoger actief is met curl en mbstring, en dat `mail()` via de lokale mailserver mag versturen (sendmail-pad ingevuld).
4. Mail: zet SPF, DKIM en DMARC voor pianocatering.be in Plesk (Mail, DKIM-ondertekening aan; SPF-record `v=spf1 a mx ~all` plus de hoster zijn include; DMARC `v=DMARC1; p=none; rua=mailto:info@pianocatering.be`). Zonder dit belanden klantmails in spam.
5. De bot-check van de hoster ("Please wait while your request is being verified") blokkeert de fetch van het formulier niet (die loopt binnen dezelfde site), maar wel Googlebot en externe tools die `api/offerte.php` aanspreken. Laat hem uitschakelen voor pianocatering.be, zoals bij prettynicedough.be.

## Testen na deploy

1. `https://www.pianocatering.be/pricing-config.json` moet JSON geven met header `Access-Control-Allow-Origin: *`.
2. `https://www.pianocatering.be/api/pricing.php` moet 403 geven (hulpbestand, geweigerd door `.htaccess`).
3. Stuur een testaanvraag via het formulier in NL, EN en IT met je eigen e-mailadres en een datum in 2030. Controleer: klantmail ontvangen (ook spam), interne melding ontvangen op elk adres uit `NOTIFY_EMAILS`, regel in `aanvragen-fallback.csv`, redirect naar `bedankt.html` in de juiste taal, en dat de richtprijs in de mail gelijk is aan wat de pagina toonde.
4. JSON-endpoint:

```bash
curl -s -X POST https://www.pianocatering.be/api/offerte.php -H 'Content-Type: application/json' -d '{"datum":"2030-06-20","aantal_personen":40,"locatie":"Herent","items":["pizza","cime-di-rapa","water"],"naam":"Test","email":"viktor@decroos.be","taal":"nl"}'
```

5. Verwijder de testregels uit de CSV, of zet "Test" als naam zodat ze herkenbaar blijven.

## Lokaal testen

```bash
php api/pricing_test.php
```

```bash
php -S localhost:8089 -t .
```

Open dan `http://localhost:8089/offerte-aanvraag.html`. Lokaal faalt `mail()` meestal; het formulier gaat dan toch naar de bedankpagina (de aanvraag is gelogd) en de CSV staat één map boven de repo.

## Meting: GA4, Google Ads en cookietoestemming

Alles zit in `js/tracking.js`, geladen in de head van elke pagina (30 pagina's). Geen Tag Manager, zelfde aanpak als Pretty Nice Dough. De cookiebanner (NL, EN, IT) en de link "Cookie-instellingen" in elke footer horen erbij.

### Eerst invullen

Bovenaan `js/tracking.js` staan drie placeholders. Zolang ze op X-en staan, laadt het script gtag.js niet (de events komen wel in `dataLayer`, handig om te testen):

| Variabele | Waar te vinden |
|---|---|
| `GA4_ID` | GA4 > Beheer > Gegevensstromen > webstream Piano C > Meet-ID (G-...) |
| `ADS_ID` | Google Ads > Doelen > Conversies > actie "Offerteaanvraag Piano C" > Tag instellen > conversie-ID (AW-...) |
| `ADS_LABEL_LEAD` | Zelfde scherm > conversielabel. Hoofdlettergevoelig: letterlijk overnemen (PND had hier een typfout, waardoor de telling maanden op nul stond) |

### Wat het script meet

- Pageviews op elke pagina, met de parameters `brand` = pianoc en `lang`.
- `click_phone`, `click_email`, `click_whatsapp` bij een klik op tel:, mailto: of wa.me, met `link_url` en `page_path`.
- Op `bedankt.html` (NL, EN, IT): `generate_lead` (lead_type catering, value 350, EUR) en de Ads-conversie met hetzelfde label, één keer per sessie.
- `consent_choice` (granted of denied) bij een klik in de banner.
- UTM-parameters en gclid worden bij het eerste bezoek in sessionStorage bewaard (`pianoc_utm`) en door het offerteformulier meegestuurd.
- `?intern=1` achter een URL zet een vlag in localStorage waardoor alle events `traffic_type` = internal meekrijgen; `?intern=0` haalt ze weg. Zet dit op elk toestel waarmee je zelf test.

### Consent Mode v2

Standaard staat alles op denied (ad_storage, ad_user_data, ad_personalization, analytics_storage). Na "Accepteren" gaat alles naar granted, na "Weigeren" blijft alles denied, de keuze staat in localStorage (`pianoc_consent`). Zonder keuze blijft de banner staan. Google ontvangt in de denied-stand enkel cookieloze pings, waarmee Ads conversies kan modelleren. De site werkt volledig zonder de banner.

### In GA4 aanklikken (property Piano C)

1. Beheer > Gegevensinstellingen > Gegevensbewaring: 14 maanden.
2. Beheer > Gegevensstromen > webstream > Verbeterde meting: aan, maar "Uitgaande klikken" mag uit (anders telt WhatsApp dubbel als `click`).
3. Beheer > Gebeurtenissen: `generate_lead`, `click_phone` en `click_email` markeren als belangrijke gebeurtenis (key event). `click_whatsapp` niet, zolang WhatsApp vooral na het formulier geklikt wordt; `consent_choice` nooit.
4. Beheer > Gegevensstromen > webstream > Tagconfiguratie > Intern verkeer definiëren: regel met parameter `traffic_type` gelijk aan `internal`, en het filter "Intern verkeer" activeren onder Gegevensfilters.
5. Beheer > Aangepaste definities: dimensies `brand` (parameter brand) en `lang` (parameter lang), bereik Gebeurtenis.
6. Koppelen aan het Ads-account Piano C (Beheer > Productkoppelingen > Google Ads).

### In Google Ads aanklikken (account Piano C)

1. Doelen > Conversies > Nieuwe conversieactie > Website > handmatig: "Offerteaanvraag Piano C", categorie Lead indienen, waarde 350 euro excl. btw voor elke conversie, telling Eén, klikvenster 30 dagen, primair. De tag staat al in `js/tracking.js`; enkel ID en label invullen.
2. Doelen > Conversies > Oproepen via advertenties: "Oproepen Piano C", minstens 60 seconden, primair, met een oproepextensie op het nummer.
3. Na een dag data in GA4: Doelen > Conversies > Importeren > GA4-gebeurtenissen `click_phone` en `click_email`, beide secundair, waarde 70 en 50 euro. `click_whatsapp`, `generate_lead` en `consent_choice` niet importeren (dubbel met de Ads-eigen conversie).
4. Instellingen > Verbeterde conversies: aan, via Google-tag (het script zet `allow_enhanced_conversions`). Het e-mailadres uit het formulier kan later gehasht meegestuurd worden op de bedankpagina; dat staat nog niet in de code.
5. Controle met Tag Assistant op een pagina met `?intern=1`: consent default denied, update na Accepteren, pageview, click_phone, en op bedankt.html de conversie.

## Aannames en open punten

- Transport wordt gerekend op de rijafstand in één richting, zoals de configurator op de site dat doet (20 km gratis, daarboven €1,35 per km, als totaalbedrag verdeeld over de gasten). PND rekent heen en terug. Bevestig met Piano A welke van de twee de bedoeling is; aanpassen kan in `pricing-config.json` en in het script op de drie offertepagina's.
- De groepskorting geldt op alles, ook op opstart, transport en drank, zoals de configurator. De btw wordt daarna evenredig berekend (12 % eten, 21 % drank).
- Onder 20 personen wordt de opstartkost over 20 verdeeld, maar de rest op het echte aantal; de site doet hetzelfde.
- Afzender van de mails is `info@pianocatering.be`. Wie die mailbox leest en wie van Piano A in `NOTIFY_EMAILS` komt, is nog te beslissen.
- `.htaccess` gaat uit van Apache achter de nginx-proxy van de hoster, zoals bij PND. Werkt `Require all denied` niet, verplaats dan de hulpbestanden buiten de webroot en pas de `require_once`-paden in `api/aanvraag.php` en `api/offerte.php` aan.
- Nominatim vraagt een geldige User-Agent en lage volumes; voor een cateringformulier is dat ruim voldoende. Bij groei: Google Distance Matrix-sleutel in `.env`.
