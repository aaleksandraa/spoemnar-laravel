# Google Search Console Setup

Datum: 2026-04-16

Ovaj projekat koristi:

- lokalizovane URL-ove: `/bs/...`, `/sr/...`, `/hr/...`, `/de/...`, `/en/...`, `/it/...`
- sitemap index: `/sitemap.xml`
- locale sitemapove: `/sitemap-bs.xml`, `/sitemap-sr.xml`, `/sitemap-hr.xml`, `/sitemap-de.xml`, `/sitemap-en.xml`, `/sitemap-it.xml`
- robots fajl: `/robots.txt`

## Kratak odgovor

Ne treba poseban GSC property po jeziku i ne treba rucno slati svaki locale sitemap.

Preporucena postavka je:

1. jedan `Domain property` za cijelu domenu
2. u taj property poslati samo `https://VAS-DOMEN/sitemap.xml`
3. locale performanse pratiti kroz filtere po putanji, npr. `Page contains /de/`

Razlog:

- `/sitemap.xml` je sitemap index i vec upucuje na sve locale sitemapove
- svi jezici su na istoj domeni i razlikuju se po path prefiksu
- GSC iz jednog property-ja normalno prati sve locale URL-ove

## Kada ima smisla dodati dodatni property

Dodatni `URL-prefix` property ima smisla samo ako zelite odvojene interne dashboarde, na primjer:

- `https://vas-domena.com/de/`
- `https://vas-domena.com/en/`

To nije tehnicka potreba za indeksaciju. To je samo reporting odluka.

## Preporucena GSC konfiguracija

### 1. Canonical host

Prije povezivanja odlucite koji je glavni host:

- `https://vas-domena.com`
- ili `https://www.vas-domena.com`

Sve ostalo mora 301 na taj host. GSC treba postaviti na isti canonical host koji aplikacija stvarno koristi.

### 2. Dodavanje property-ja

Preporuka: `Domain property`

Koraci:

1. Otvorite `https://search.google.com/search-console`
2. Kliknite `Add property`
3. Izaberite `Domain`
4. Unesite domenu bez protokola, npr. `vas-domena.com`
5. Dodajte DNS TXT zapis koji Google trazi
6. Ostavite TXT zapis trajno

Ako ne mozete raditi DNS verifikaciju, fallback je:

- `URL-prefix property` za `https://vas-domena.com/`

I to je i dalje dovoljno. Nije potrebno dodavati poseban property za `/de/`, `/en/`, itd.

## Sitemap pravilo za ovaj projekat

Predaje se samo:

- `sitemap.xml`

Ne treba posebno slati:

- `sitemap-bs.xml`
- `sitemap-sr.xml`
- `sitemap-hr.xml`
- `sitemap-de.xml`
- `sitemap-en.xml`
- `sitemap-it.xml`

Mozete ih otvoriti i provjeriti rucno, ali ih ne trebate pojedinacno submitovati ako je index sitemap ispravan.

## Sta provjeriti prije submit-a

Obavezno testirati:

1. `https://VAS-DOMEN/sitemap.xml` vraca `200`
2. `https://VAS-DOMEN/robots.txt` vraca `200`
3. `robots.txt` sadrzi:
   `Sitemap: https://VAS-DOMEN/sitemap.xml`
4. locale home stranice rade:
   `/bs/`, `/sr/`, `/hr/`, `/de/`, `/en/`, `/it/`
5. locale profile stranice rade:
   `/bs/profil/{slug}`, `/de/profil/{slug}`, itd.
6. stari URL-ovi ili `?lang=` varijante rade 301 na locale path

## Submit sitemapa

U GSC:

1. otvorite property
2. otvorite `Sitemaps`
3. u polje `Add a new sitemap` upisite `sitemap.xml`
4. kliknite `Submit`

Ocekivano stanje:

- status `Success`
- `Last read` se osvjezava
- broj otkrivenih URL-ova raste vremenom

## Kako pratiti jezike u jednom property-ju

U `Search results` reportu koristite filter po stranici:

- `Page contains /bs/`
- `Page contains /sr/`
- `Page contains /hr/`
- `Page contains /de/`
- `Page contains /en/`
- `Page contains /it/`

Tako dobijete prakticno zaseban SEO pregled po jeziku bez dodatnih property-ja.

Pratite po locale segmentu:

- clicks
- impressions
- CTR
- average position

## Kako pratiti da se svaki profil indeksira

Za reprezentativni uzorak profila radite `URL Inspection`:

1. provjerite nekoliko novijih i nekoliko starijih javnih profila
2. provjerite profil na vise locale putanja, npr.:
   `/bs/profil/{slug}`
   `/de/profil/{slug}`
   `/en/profil/{slug}`
3. potvrdite:
   - `URL is on Google` ili makar da nema crawl blokadu
   - `Crawled as Googlebot smartphone`
   - `User-declared canonical` odgovara stvarnom locale URL-u
   - nema `noindex`

Ako profil nije indeksiran:

- provjerite da je `is_public = true`
- provjerite da postoji u locale sitemapu
- provjerite da nije blokiran `robots.txt`
- provjerite da vraca `200`
- po potrebi kliknite `Request indexing`

## Hreflang i jezici

Za ovaj setup je bitno da svaka locale stranica ima:

- self canonical na svoj locale URL
- alternate hreflang linkove za sve podrzane jezike
- `x-default` na primarni default locale URL

Za profile to znaci da jedan memorial moze imati vise locale URL-ova:

- `/bs/profil/ime.prezime`
- `/de/profil/ime.prezime`
- `/en/profil/ime.prezime`

To nije duplikat problem ako su canonical i hreflang konzistentni.

## Operativna rutina nakon deploy-a

Poslije svakog SEO ili routing deploy-a:

1. otvoriti `sitemap.xml`
2. otvoriti po jedan locale sitemap
3. provjeriti 2-3 locale home URL-a
4. provjeriti 2-3 profile URL-a
5. provjeriti redirect:
   `/about?lang=de` -> `/de/about`

## Brzi checklist

- `curl -I https://VAS-DOMEN/sitemap.xml`
- `curl -I https://VAS-DOMEN/robots.txt`
- `curl -I https://VAS-DOMEN/de/`
- `curl -I https://VAS-DOMEN/de/search`
- `curl -I https://VAS-DOMEN/de/profil/slug`
- `curl -I "https://VAS-DOMEN/about?lang=de"`

Ocekivanje:

- sitemap i robots: `200`
- javne locale stranice i profili: `200`
- `?lang=` varijante: `301` na kanonski locale URL
