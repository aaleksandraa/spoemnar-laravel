# SEO Production Checklist (BiH / Srbija / Hrvatska / Italija)

Datum verzije: 2026-02-25

## 1. URL i lokalizacija

- [x] Locale URL struktura koristi prefikse: `/bs/...`, `/sr/...`, `/hr/...`, `/de/...`, `/en/...`, `/it/...`.
- [x] Root `/` radi redirect na detektovani locale.
- [x] Automatski 301 canonical redirect radi za `?lang=` na odgovarajuci `/{locale}/...` URL.
- [x] Legacy rute bez locale prefiksa imaju 301 redirect na locale rute.
- [x] Internal linkovi (header/footer/CTA/forms) koriste locale-aware `route(...)` URL-ove.
- [ ] Potvrditi da produkcioni web server ne uvodi dodatne redirect chain-ove (max 1 redirect).

## 2. Indexability i crawl

- [x] `robots.txt` postoji i javno je dostupan.
- [x] `sitemap.xml` postoji i javno je dostupan.
- [x] `sitemap.xml` sadrzi locale URL-ove za staticke i memorial stranice.
- [x] `lastmod` za staticke stranice se racuna iz stvarnih timestampova view/routes/lang fajlova.
- [x] `lastmod` za memorial profile dolazi iz `updated_at`.
- [x] Canonical tag je postavljen po trenutnom locale URL-u.
- [x] `hreflang` alternate tagovi postoje za sve podrzane jezike + `x-default`.
- [x] Login/Register stranice su `noindex,follow`.
- [ ] Potvrditi da admin/private rute imaju `noindex` ili nisu javno dostupne.
- [ ] Potvrditi da nema blokade bitnih URL-ova u `robots.txt`.

## 3. Meta i on-page SEO

- [x] Default `title`, `description` i `keywords` postoje za sve jezike.
- [ ] Dodati jedinstven `meta description` za svaku kljucnu landing stranicu po jeziku (ako treba detaljnije od default vrijednosti).
- [ ] Dodati jedinstven OG image po stranici gdje je vazno za dijeljenje.
- [ ] Potvrditi da svi memorial profili imaju smislen `title` i opis (ime/prezime + osnovni kontekst).
- [ ] Potvrditi da svi `img` elementi imaju kvalitetan `alt` tekst.

## 4. Structured data

- [ ] Dodati `Organization` schema za brend.
- [ ] Dodati `WebSite` schema sa `SearchAction` ako postoji pretraga.
- [ ] Dodati `BreadcrumbList` na relevantnim stranicama.
- [ ] Dodati schema za memorial profile (npr. `Person`) gdje je semanticki prikladno.
- [ ] Validirati schema markup preko Rich Results Test.

## 5. Tehnicki SEO i performanse

- [ ] U produkciji forsirati HTTPS i jedan canonical host (`www` ili non-`www`) sa 301.
- [ ] Ukloniti soft-404 stranice i osigurati pravi HTTP 404 za nepostojece URL-ove.
- [ ] Uvesti cache zaglavlja za staticki assets (dug cache + hashirani fajlovi).
- [ ] Provjeriti Core Web Vitals (LCP, INP, CLS) za mobilne uredjaje.
- [ ] Kompresija i format slika (WebP/AVIF gdje moguce).
- [ ] Lazy-load slika ispod folda.

## 6. Regionalni SEO (BiH/SRB/HR)

- [x] Hreflang mapiranje koristi regionalne kodove (`bs-BA`, `sr-RS`, `hr-HR`, `it-IT`, `de-DE`, `en-US`).
- [ ] U Google Search Console dodati property za domen i predati `sitemap.xml`.
- [ ] U Bing Webmaster Tools predati `sitemap.xml`.
- [ ] Definisati prioritetne lokalne keyword klastere po trzistu (BiH, Srbija, Hrvatska).
- [ ] Pripremiti lokalizovane landing stranice za najvaznije termine (ako poslovno relevantno).

## 7. Monitoring i QA prije release-a

- [ ] Crawl test (Screaming Frog ili slicno) bez 4xx/5xx na bitnim URL-ovima.
- [ ] Provjera canonical/hreflang konzistentnosti (self-canonical i reciprocity).
- [ ] Provjera redirect pravila za primjere:
- [ ] `/about?lang=sr` -> `301` -> `/sr/about`
- [ ] `/bs/profil/ime-prezime?lang=hr` -> `301` -> `/hr/profil/ime-prezime`
- [ ] `/contact?lang=bs&ref=ad` -> `301` -> `/bs/contact?ref=ad`
- [ ] Rucna provjera indexability u browseru i preko HTTP header alata.
- [ ] Postaviti alerting za 5xx i nagli rast 404.

## 8. Operativne komande za brzu provjeru

- `php artisan route:list --path=language`
- `php artisan route:list --path=sitemap`
- `php artisan route:list --path=robots`
- `php artisan view:cache`
- `curl -I https://VAS-DOMEN/sitemap.xml`
- `curl -I "https://VAS-DOMEN/about?lang=sr"`
