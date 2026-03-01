# Google Search Console Setup (Detaljno)

Datum: 2026-02-25

Ovaj vodi koristi trenutnu strukturu sajta:

- locale URL: `/bs/...`, `/sr/...`, `/hr/...`, `/de/...`, `/en/...`, `/it/...`
- glavni sitemap: `/sitemap.xml`
- robots: `/robots.txt`

## 1. Priprema prije povezivanja

- Potvrdi da produkcija koristi jedan canonical host:
- primjer: uvijek `https://example.com` (bez `www`) ili uvijek `https://www.example.com`
- Potvrdi da `https://example.com/sitemap.xml` vraca HTTP `200`.
- Potvrdi da `https://example.com/robots.txt` vraca HTTP `200` i sadrzi `Sitemap:`.
- Potvrdi da locale URL-ovi rade:
- `https://example.com/bs/`
- `https://example.com/sr/`
- `https://example.com/hr/`
- `https://example.com/it/`
- `https://example.com/de/`
- `https://example.com/en/`

## 2. Dodavanje property-ja u Search Console

Preporuka: dodaj `Domain property` (pokriva sve poddomene i protokole).

Koraci:

1. Otvori Search Console: `https://search.google.com/search-console`
2. Klikni `Add property`.
3. Izaberi `Domain`.
4. Unesi domen bez protokola, npr. `example.com`.
5. Google ce dati DNS TXT zapis za verifikaciju.

Alternativa (ako ne mozes DNS): `URL-prefix property` za `https://example.com/`.

## 3. Verifikacija vlasnistva

Za `Domain property`:

1. U DNS panelu dodaj TXT record koji je dao Google.
2. Sacekaj propagaciju (nekad 5-10 min, nekad duze).
3. U Search Console klikni `Verify`.

Savjet:

- Zadrzi TXT record trajno (ne brisi ga nakon verifikacije).
- Ako koristis vise DNS zona (Cloudflare + registrar), dodaj u aktivnu zonu.

## 4. Predaja sitemap-a

1. U Search Console otvori `Sitemaps`.
2. U polje `Add a new sitemap` upisi: `sitemap.xml`
3. Klikni `Submit`.
4. Status treba biti `Success`.

Sta provjeriti nakon slanja:

- `Discovered URLs` raste kroz vrijeme.
- Nema gresaka tipa `Could not fetch`.
- `Last read` se redovno osvjezava.

## 5. URL Inspection workflow (obavezno nakon deploy-a)

Provjeri kljucne URL-ove:

- Pocetne locale stranice:
- `/bs/`, `/sr/`, `/hr/`, `/it/`, `/de/`, `/en/`
- Kljucne staticke:
- `/bs/about`, `/sr/about`, `/hr/about`, `/it/about`
- Memorijal stranice:
- nekoliko stvarnih `/bs/profil/{slug}` i `/it/profil/{slug}`

Za svaki URL:

1. Zalijepi URL u `URL Inspection`.
2. Provjeri:
- `URL is on Google`
- `Crawled as Googlebot smartphone`
- `User-declared canonical` = `Google-selected canonical`
3. Ako nije indeksirano: `Request Indexing`.

## 6. Praćenje indeksacije

U reportu `Page indexing` prati:

- `Indexed` trend (treba rasti)
- `Not indexed` razloge:
- `Crawled - currently not indexed`
- `Discovered - currently not indexed`
- `Duplicate without user-selected canonical`
- `Alternate page with proper canonical tag`

Kako reagovati:

- Za `Duplicate without user-selected canonical`: provjeri canonical i interne linkove.
- Za `Discovered/Crawled not indexed`: ojacaj internal linking i sadrzaj na tim URL-ovima.
- Za 404/soft404: popravi rute ili uradi 301 na najrelevantniju stranicu.

## 7. Praćenje performansi (SEO po trzistima)

U reportu `Search results`:

1. Uključi dimenzije:
- `Queries`
- `Pages`
- `Countries`
- `Devices`

2. Napravi filtere po locale putanji:
- Page contains `/bs/`
- Page contains `/sr/`
- Page contains `/hr/`
- Page contains `/it/`
- Page contains `/de/`
- Page contains `/en/`

3. Prati metrike po locale segmentu:
- Clicks
- Impressions
- CTR
- Average position

4. Prioritet:
- prvo popravljaj stranice sa visokim impressions i niskim CTR
- zatim stranice sa pozicijom 8-20 (najbrzi rast uz on-page optimizaciju)

## 8. Hreflang i regionalni signal

Posto koristis locale URL-ove, provjeri:

- Svaka stranica ima self canonical.
- Svaka stranica ima `alternate hreflang` za sve podrzane jezike.
- `x-default` pokazuje na primarni default URL (`/bs/...`).
- Locale set ukljucuje i `it-IT`.

Ako Google pogresno rankira jezik:

- provjeri reciprocity hreflang linkova
- provjeri da locale URL-ovi imaju dovoljno internog linkanja
- provjeri da canonical ne pokazuje na drugi jezik

## 9. Tehnicki monitoring

Redovno prati:

- `Manual actions` (mora biti bez akcija)
- `Security issues` (mora biti bez problema)
- `Core Web Vitals` (mobilni prioritet)
- `Crawl stats` (stabilan crawl bez skokova 5xx)

## 10. Operativni ritam (preporuka)

Nakon svakog deploy-a:

1. `sitemap.xml` status = success
2. 5-10 URL Inspection provjera za najvaznije locale URL-ove
3. provjera 301 pravila (`?lang=` -> `/{locale}/...`)

Sedmicno:

1. pregled `Page indexing` gresaka
2. pregled `Search results` po locale filterima
3. lista top 20 URL-ova sa padom CTR ili pozicije

Mjesecno:

1. update SEO naslov/description za stranice sa velikim impressions
2. technical cleanup (404, redirect chain, canonical mismatch)
3. osvjezavanje interne link strukture prema URL-ovima sa najvecom poslovnom vrijednoscu

## 11. Brzi test checklist (copy/paste)

- `curl -I https://VAS-DOMEN/sitemap.xml`
- `curl -I https://VAS-DOMEN/robots.txt`
- `curl -I "https://VAS-DOMEN/about?lang=it"` (ocekuj 301 na `/it/about`)
- `curl -I "https://VAS-DOMEN/bs/profil/slug?lang=sr"` (ocekuj 301 na `/sr/profil/slug`)

