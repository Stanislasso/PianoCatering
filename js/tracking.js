/*
 * Piano C Catering: meting en cookietoestemming, op elke pagina geladen vanuit <head>.
 *
 * Eén bestand voor:
 *  1. Consent Mode v2 (standaard alles geweigerd, update na keuze in de banner)
 *  2. GA4 en de Google Ads-tag (gtag.js, zonder Tag Manager, zoals bij Pretty Nice Dough)
 *  3. Klik-events click_phone, click_email, click_whatsapp (zelfde namen als PND)
 *  4. UTM-parameters bewaren voor het offerteformulier
 *  5. Lead en Ads-conversie op de bedankpagina's
 *  6. Intern verkeer markeren via ?intern=1 (uitzetten met ?intern=0)
 *  7. De cookiebanner zelf (NL, EN, IT) en de link "Cookie-instellingen" in de footer
 *
 * De drie ID's hieronder zijn de enige plek waar ze staan. Vul ze in zodra de
 * GA4-property en het Ads-account van Piano C bestaan (zie README-DEPLOY.md).
 */
(function () {
  'use strict';

  var GA4_ID = 'G-XXXXXXXXXX';          // TODO: meet-ID van de GA4-webstream Piano C
  var ADS_ID = 'AW-XXXXXXXXXX';         // TODO: conversie-ID van het Google Ads-account Piano C
  var ADS_LABEL_LEAD = 'XXXXXXXXXXXXX'; // TODO: label van de conversieactie "Offerteaanvraag Piano C" (hoofdlettergevoelig)
  var LEAD_VALUE = 350;                 // conversiewaarde van een offerteaanvraag, euro excl. btw
  var IDS_SET = !/X{6,}/.test(GA4_ID + ADS_ID + ADS_LABEL_LEAD);

  var lang = (document.documentElement.lang || 'nl').slice(0, 2).toLowerCase();
  if (!/^(nl|en|it)$/.test(lang)) { lang = 'nl'; }

  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* privémodus of geblokkeerd */ } },
    del: function (k) { try { localStorage.removeItem(k); } catch (e) { /* idem */ } }
  };
  var session = {
    get: function (k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* idem */ } }
  };

  window.dataLayer = window.dataLayer || [];
  function gtag() { window.dataLayer.push(arguments); }
  window.gtag = gtag;

  /* 1. Consent Mode v2: standaard geweigerd, vóór gtag.js laadt. */
  var CONSENT_KEY = 'pianoc_consent';
  var consent = store.get(CONSENT_KEY); // 'granted', 'denied' of null (nog geen keuze)
  gtag('consent', 'default', {
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
    analytics_storage: 'denied',
    functionality_storage: 'granted',
    security_storage: 'granted',
    wait_for_update: 500
  });
  function consentState(granted) {
    var v = granted ? 'granted' : 'denied';
    return { ad_storage: v, ad_user_data: v, ad_personalization: v, analytics_storage: v };
  }
  if (consent === 'granted') { gtag('consent', 'update', consentState(true)); }

  /* 6. Intern verkeer: ?intern=1 zet een vlag, ?intern=0 haalt ze weg. */
  var params = new URLSearchParams(window.location.search);
  if (params.get('intern') === '1') { store.set('pianoc_intern', '1'); }
  if (params.get('intern') === '0') { store.del('pianoc_intern'); }
  var intern = store.get('pianoc_intern') === '1';

  /* 4. UTM-parameters (en gclid) bewaren voor de duur van de sessie. */
  var UTM_KEY = 'pianoc_utm';
  var utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid'];
  var utm = {};
  try { utm = JSON.parse(session.get(UTM_KEY) || '{}') || {}; } catch (e) { utm = {}; }
  var utmFresh = false;
  utmKeys.forEach(function (k) {
    var v = params.get(k);
    if (v) { utm[k] = String(v).slice(0, 150); utmFresh = true; }
  });
  if (utmFresh) { session.set(UTM_KEY, JSON.stringify(utm)); }

  /* 2. GA4 en Ads configureren en gtag.js laden. */
  gtag('js', new Date());
  var config = { brand: 'pianoc', lang: lang };
  if (intern) { config.traffic_type = 'internal'; }
  gtag('config', GA4_ID, config);
  gtag('config', ADS_ID, { allow_enhanced_conversions: true });
  if (IDS_SET) {
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA4_ID);
    document.head.appendChild(s);
  } else if (window.console) {
    console.info('Piano C tracking: meet-ID\'s nog niet ingevuld in js/tracking.js, gtag.js wordt niet geladen.');
  }

  /* 5. Bedankpagina: lead voor GA4 en conversie voor Ads (eenmalig per sessie). */
  if (/(^|\/)bedankt\.html$/.test(window.location.pathname)) {
    var leadKey = 'pianoc_lead_sent';
    if (session.get(leadKey) !== '1') {
      session.set(leadKey, '1');
      gtag('event', 'generate_lead', { lead_type: 'catering', brand: 'pianoc', lang: lang, value: LEAD_VALUE, currency: 'EUR' });
      gtag('event', 'conversion', { send_to: ADS_ID + '/' + ADS_LABEL_LEAD, value: LEAD_VALUE, currency: 'EUR' });
    }
  }

  /* 3. Klik-events op telefoon, mail en WhatsApp. */
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) { return; }
    var href = a.getAttribute('href') || '';
    var name = null;
    if (/^tel:/i.test(href)) { name = 'click_phone'; }
    else if (/^mailto:/i.test(href)) { name = 'click_email'; }
    else if (/wa\.me|whatsapp\.com/i.test(href)) { name = 'click_whatsapp'; }
    if (name) {
      gtag('event', name, { link_url: href.split('?')[0], page_path: window.location.pathname, brand: 'pianoc', lang: lang });
    }
  }, true);

  /* 7. Cookiebanner en de link "Cookie-instellingen". */
  var T = {
    nl: {
      text: 'We gebruiken cookies om te meten hoe onze site gebruikt wordt en of onze advertenties werken. Geen advertenties van derden.',
      accept: 'Accepteren', decline: 'Weigeren', privacy: 'Privacy', settings: 'Cookie-instellingen', label: 'Cookievoorkeuren'
    },
    en: {
      text: 'We use cookies to measure how our site is used and whether our ads work. No third-party advertising.',
      accept: 'Accept', decline: 'Decline', privacy: 'Privacy', settings: 'Cookie settings', label: 'Cookie preferences'
    },
    it: {
      text: 'Usiamo i cookie per misurare come viene usato il sito e se le nostre inserzioni funzionano. Nessuna pubblicità di terzi.',
      accept: 'Accetta', decline: 'Rifiuta', privacy: 'Privacy', settings: 'Impostazioni cookie', label: 'Preferenze cookie'
    }
  }[lang];

  function setConsent(granted) {
    store.set(CONSENT_KEY, granted ? 'granted' : 'denied');
    gtag('consent', 'update', consentState(granted));
    gtag('event', 'consent_choice', { choice: granted ? 'granted' : 'denied', brand: 'pianoc', lang: lang });
    closeBanner();
  }

  var banner = null;
  function closeBanner() {
    if (banner && banner.parentNode) { banner.parentNode.removeChild(banner); }
    banner = null;
  }
  function openBanner() {
    if (banner) { return; }
    if (!document.getElementById('pianoc-consent-style')) {
      var css = document.createElement('style');
      css.id = 'pianoc-consent-style';
      css.textContent =
        '#pianoc-consent{position:fixed;left:16px;right:16px;bottom:calc(16px + env(safe-area-inset-bottom,0px));z-index:9999;max-width:560px;margin:0 auto;' +
        'background:var(--espresso,#4A342A);color:var(--cream,#F4F1EC);border-radius:14px;padding:18px 20px;box-shadow:0 12px 40px rgba(42,29,22,.35);' +
        'font-family:var(--font-body,"Hanken Grotesk",-apple-system,sans-serif);font-size:.95rem;line-height:1.5}' +
        '#pianoc-consent p{margin:0 0 14px}' +
        '#pianoc-consent .row{display:flex;flex-wrap:wrap;gap:10px;align-items:center}' +
        '#pianoc-consent button{font:inherit;font-weight:600;border-radius:999px;padding:11px 20px;cursor:pointer;min-height:44px;border:1px solid var(--cream,#F4F1EC)}' +
        '#pianoc-consent .accept{background:var(--terracotta,#C0562E);border-color:var(--terracotta,#C0562E);color:#fff}' +
        '#pianoc-consent .decline{background:transparent;color:var(--cream,#F4F1EC)}' +
        '#pianoc-consent a{color:var(--cream,#F4F1EC);margin-left:auto;font-size:.88rem}' +
        '@media(max-width:480px){#pianoc-consent button{flex:1 1 0}#pianoc-consent a{margin-left:0;flex-basis:100%}}';
      document.head.appendChild(css);
    }
    banner = document.createElement('div');
    banner.id = 'pianoc-consent';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-live', 'polite');
    banner.setAttribute('aria-label', T.label);
    var p = document.createElement('p');
    p.textContent = T.text;
    var row = document.createElement('div');
    row.className = 'row';
    var accept = document.createElement('button');
    accept.type = 'button'; accept.className = 'accept'; accept.textContent = T.accept;
    accept.addEventListener('click', function () { setConsent(true); });
    var decline = document.createElement('button');
    decline.type = 'button'; decline.className = 'decline'; decline.textContent = T.decline;
    decline.addEventListener('click', function () { setConsent(false); });
    var link = document.createElement('a');
    link.href = 'privacy.html'; link.textContent = T.privacy;
    row.appendChild(accept); row.appendChild(decline); row.appendChild(link);
    banner.appendChild(p); banner.appendChild(row);
    document.body.appendChild(banner);
  }
  window.pianocOpenConsent = openBanner;

  function onReady(fn) {
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fn); } else { fn(); }
  }
  onReady(function () {
    if (consent === null) { openBanner(); }
    var links = document.querySelectorAll('[data-cookie-settings]');
    for (var i = 0; i < links.length; i++) {
      if (!links[i].textContent.trim()) { links[i].textContent = T.settings; }
      links[i].addEventListener('click', function (e) { e.preventDefault(); openBanner(); });
    }
  });
})();
