# Bugfix Requirements Document

## Introduction

Ova aplikacija za memorijale ima kritične sigurnosne propuste koji izlažu privatne podatke, omogućavaju brute-force napade, i ostavljaju sistem ranjivim na različite vrste napada. Problemi uključuju:

- Privatni memorijali dostupni preko javnog API-ja
- Email adrese izložene kroz API odgovore
- Nedostatak rate limiting zaštite na autentifikaciji
- Auth tokeni spremljeni u localStorage (XSS ranjivost)
- Javni tribute API bez anti-spam zaštite
- Nedostajući security headeri (CSP, HSTS, X-Frame-Options)
- Sanctum tokeni bez expiration perioda
- Preširoka CORS politika
- Kontakt forma bez rate limiting i sa PII logiranjem
- Nedostatak HTTPS forsiranja na aplikacijskom nivou

Ovi propusti predstavljaju ozbiljne sigurnosne rizike koji moraju biti sistemski riješeni.

## Bug Analysis

### Current Behavior (Defect)

#### 1. Privatni Memorijali Izloženi

1.1 WHEN neautentifikovani korisnik pozove GET /api/v1/memorials?isPublic=false THEN sistem vraća privatne memorijale u odgovoru

1.2 WHEN neautentifikovani korisnik pozove GET /api/v1/memorials/{slug} gdje je memorial.is_public=false THEN sistem vraća potpune podatke privatnog memorijala bez provjere

1.3 WHEN autentifikovani korisnik pozove GET /api/v1/memorials/{slug} za tuđi privatni memorial THEN sistem vraća podatke memorijala iako korisnik nije vlasnik

#### 2. Email Adrese Izložene

2.1 WHEN bilo koji korisnik pozove GET /api/v1/memorials/{slug} THEN MemorialResource vraća authorEmail u tributes arrayu

2.2 WHEN bilo koji korisnik pozove GET /api/v1/memorials/{memorial}/tributes THEN TributeController vraća author_email za sve tribute bez obzira na is_public status memorijala

2.3 WHEN neautentifikovani korisnik pristupi privatnom memorijalu preko tribute API-ja THEN sistem izlaže email adrese autora tributa

#### 3. Login/Register Bez Rate Limiting

3.1 WHEN napadač šalje više od 100 login zahtjeva u minuti na POST /api/v1/login THEN sistem procesira sve zahtjeve bez ograničenja

3.2 WHEN napadač šalje više od 50 register zahtjeva u minuti na POST /api/v1/register THEN sistem kreira naloge bez throttle zaštite

3.3 WHEN napadač šalje 1000 neuspješnih login pokušaja za isti email THEN sistem ne implementira lockout mehanizam

#### 4. Auth Token u localStorage

4.1 WHEN korisnik se uspješno prijavi THEN aplikacija sprema auth_token u localStorage (login.blade.php#L125)

4.2 WHEN XSS napad se izvrši na stranici THEN maliciozni JavaScript može pročitati localStorage.getItem('auth_token')

4.3 WHEN korisnik se registruje THEN aplikacija sprema token u localStorage (register.blade.php#L202)

#### 5. Tribute API Bez Anti-Spam Zaštite

5.1 WHEN napadač šalje 1000 tribute zahtjeva u minuti na POST /api/v1/memorials/{memorial}/tributes THEN sistem procesira sve zahtjeve bez rate limiting

5.2 WHEN bot šalje automatske tribute sa različitih IP adresa THEN sistem nema captcha ili honeypot zaštitu

5.3 WHEN spam tribute se šalje THEN sistem nema timestamp ili signature validaciju

#### 6. Nedostajući Security Headeri

6.1 WHEN bilo koji HTTP odgovor se šalje THEN nedostaje Content-Security-Policy header

6.2 WHEN HTTP odgovor se šalje THEN nedostaje Strict-Transport-Security (HSTS) header

6.3 WHEN HTTP odgovor se šalje THEN nedostaje X-Frame-Options header

6.4 WHEN HTTP odgovor se šalje THEN nedostaje X-Content-Type-Options header

#### 7. Sanctum Tokeni Bez Expiration

7.1 WHEN korisnik se prijavi i dobije Sanctum token THEN config/sanctum.php ima 'expiration' => null

7.2 WHEN token je kompromitovan THEN token ostaje validan beskonačno dok se ručno ne opozove

7.3 WHEN korisnik ne koristi aplikaciju 6 mjeseci THEN token i dalje ostaje aktivan

#### 8. Preširoka CORS Politika

8.1 WHEN CORS zahtjev se šalje THEN config/cors.php ima 'allowed_methods' => ['*']

8.2 WHEN CORS zahtjev se šalje THEN config/cors.php ima 'allowed_headers' => ['*']

8.3 WHEN CORS zahtjev se šalje sa credentials THEN kombinacija wildcard metoda/headera sa supports_credentials=true predstavlja sigurnosni rizik

#### 9. Kontakt Forma Bez Zaštite i Sa PII Logiranjem

9.1 WHEN napadač šalje 500 kontakt forma zahtjeva u minuti THEN ContactController procesira sve bez rate limiting

9.2 WHEN korisnik submituje kontakt formu THEN ContactController.php#L40 logira name, email, i subject u Log::info

9.3 WHEN log fajlovi se čuvaju THEN PII podaci (email, ime) ostaju trajno u logovima

#### 10. Nedostatak HTTPS Forsiranja

10.1 WHEN aplikacija radi u production okruženju THEN config/app.php ne forsira HTTPS na aplikacijskom nivou

10.2 WHEN lokalno okruženje ima APP_ENV=local THEN aplikacija radi preko HTTP protokola

10.3 WHEN HTTPS zaštita zavisi samo od infra konfiguracije THEN nema app-level fallback zaštite

### Expected Behavior (Correct)

#### 1. Privatni Memorijali Zaštićeni

2.1 WHEN neautentifikovani korisnik pozove GET /api/v1/memorials sa bilo kojim parametrom THEN sistem SHALL vratiti samo memorijale gdje is_public=true

2.2 WHEN neautentifikovani korisnik pozove GET /api/v1/memorials/{slug} gdje je memorial.is_public=false THEN sistem SHALL vratiti 404 Not Found

2.3 WHEN autentifikovani korisnik pozove GET /api/v1/memorials/{slug} za tuđi privatni memorial THEN sistem SHALL vratiti 403 Forbidden ili 404 Not Found

2.4 WHEN vlasnik memorijala pozove GET /api/v1/memorials/{slug} za svoj privatni memorial THEN sistem SHALL vratiti potpune podatke

#### 2. Email Adrese Zaštićene

2.5 WHEN bilo koji korisnik pozove GET /api/v1/memorials/{slug} THEN MemorialResource SHALL ukloniti authorEmail iz tributes arraya

2.6 WHEN neautentifikovani korisnik pozove GET /api/v1/memorials/{memorial}/tributes za privatni memorial THEN sistem SHALL vratiti 404 Not Found

2.7 WHEN autentifikovani korisnik pozove GET /api/v1/memorials/{memorial}/tributes za tuđi privatni memorial THEN sistem SHALL vratiti 403 Forbidden

2.8 WHEN vlasnik memorijala pozove GET /api/v1/memorials/{memorial}/tributes THEN sistem SHALL vratiti tribute sa email adresama

#### 3. Login/Register Sa Rate Limiting

2.9 WHEN korisnik šalje više od 5 neuspješnih login pokušaja u minuti THEN sistem SHALL primijeniti throttle i vratiti 429 Too Many Requests

2.10 WHEN korisnik šalje više od 3 register zahtjeva u minuti sa iste IP adrese THEN sistem SHALL primijeniti throttle i vratiti 429 Too Many Requests

2.11 WHEN korisnik ima 5 uzastopnih neuspješnih login pokušaja THEN sistem SHALL implementirati lockout od 1 minuta

#### 4. Auth Token u HttpOnly Cookie

2.12 WHEN korisnik se uspješno prijavi THEN aplikacija SHALL spremiti token u httpOnly, secure, sameSite cookie umjesto localStorage

2.13 WHEN XSS napad se izvrši THEN JavaScript NE SHALL moći pristupiti auth tokenu

2.14 WHEN korisnik se registruje THEN aplikacija SHALL koristiti isti httpOnly cookie mehanizam

#### 5. Tribute API Sa Anti-Spam Zaštitom

2.15 WHEN korisnik šalje više od 3 tribute zahtjeva u 10 minuta sa iste IP adrese THEN sistem SHALL primijeniti rate limiting

2.16 WHEN tribute zahtjev se šalje THEN sistem SHALL zahtijevati honeypot polje validaciju

2.17 WHEN tribute zahtjev se šalje THEN sistem SHALL validirati timestamp (ne stariji od 1 sat)

#### 6. Security Headeri Implementirani

2.18 WHEN bilo koji HTTP odgovor se šalje THEN sistem SHALL uključiti Content-Security-Policy header sa restriktivnom politikom

2.19 WHEN HTTP odgovor se šalje preko HTTPS THEN sistem SHALL uključiti Strict-Transport-Security header (max-age=31536000)

2.20 WHEN HTTP odgovor se šalje THEN sistem SHALL uključiti X-Frame-Options: DENY header

2.21 WHEN HTTP odgovor se šalje THEN sistem SHALL uključiti X-Content-Type-Options: nosniff header

#### 7. Sanctum Tokeni Sa Expiration

2.22 WHEN korisnik se prijavi THEN Sanctum token SHALL imati expiration period od 60 dana

2.23 WHEN token istekne THEN sistem SHALL vratiti 401 Unauthenticated

2.24 WHEN korisnik koristi aplikaciju THEN sistem SHALL omogućiti refresh token mehanizam

#### 8. Restriktivna CORS Politika

2.25 WHEN CORS zahtjev se šalje THEN sistem SHALL dozvoliti samo specifične HTTP metode (GET, POST, PUT, DELETE, OPTIONS)

2.26 WHEN CORS zahtjev se šalje THEN sistem SHALL dozvoliti samo potrebne headere (Content-Type, Authorization, Accept, X-Requested-With)

2.27 WHEN CORS zahtjev se šalje THEN sistem SHALL održati supports_credentials=true samo sa eksplicitnim origin listama

#### 9. Kontakt Forma Sa Zaštitom i Bez PII Logiranja

2.28 WHEN korisnik šalje kontakt formu THEN sistem SHALL primijeniti rate limit od 5 zahtjeva po satu po IP adresi

2.29 WHEN kontakt forma se submituje THEN sistem SHALL logirati samo non-PII podatke (timestamp, IP hash, success/failure)

2.30 WHEN kontakt forma se submituje THEN sistem SHALL ukloniti email i name iz Log::info poziva

#### 10. HTTPS Forsiranje Implementirano

2.31 WHEN aplikacija radi u production okruženju (APP_ENV=production) THEN sistem SHALL forsirati HTTPS redirekciju

2.32 WHEN HTTP zahtjev se šalje u production THEN sistem SHALL automatski redirektovati na HTTPS

2.33 WHEN aplikacija radi u local/testing okruženju THEN HTTPS forsiranje SHALL biti onemogućeno

### Unchanged Behavior (Regression Prevention)

#### Autentifikacija i Autorizacija

3.1 WHEN validan korisnik se prijavi sa ispravnim credentials THEN sistem SHALL CONTINUE TO vraćati user objekat i token

3.2 WHEN korisnik se registruje sa validnim podacima THEN sistem SHALL CONTINUE TO kreirati nalog i slati welcome email

3.3 WHEN autentifikovani korisnik pozove /api/v1/me THEN sistem SHALL CONTINUE TO vraćati user podatke sa profile i roles

#### Memorial CRUD Operacije

3.4 WHEN autentifikovani korisnik kreira memorial THEN sistem SHALL CONTINUE TO generisati slug i spremiti memorial

3.5 WHEN vlasnik memorijala ažurira svoj memorial THEN sistem SHALL CONTINUE TO dozvoliti update operaciju

3.6 WHEN vlasnik memorijala briše svoj memorial THEN sistem SHALL CONTINUE TO dozvoliti delete operaciju

3.7 WHEN admin korisnik upravlja bilo kojim memorijalima THEN sistem SHALL CONTINUE TO dozvoliti admin privilegije

#### Javni Memorijali

3.8 WHEN neautentifikovani korisnik traži javne memorijale (is_public=true) THEN sistem SHALL CONTINUE TO vraćati rezultate

3.9 WHEN bilo koji korisnik pristupi javnom memorijalu preko slug-a THEN sistem SHALL CONTINUE TO vraćati potpune podatke

3.10 WHEN javni memorial ima tribute THEN sistem SHALL CONTINUE TO prikazivati tribute (bez email adresa)

#### Tribute Funkcionalnost

3.11 WHEN korisnik submituje validan tribute za javni memorial THEN sistem SHALL CONTINUE TO kreirati tribute zapis

3.12 WHEN vlasnik memorijala briše tribute THEN sistem SHALL CONTINUE TO dozvoliti brisanje

3.13 WHEN admin briše tribute THEN sistem SHALL CONTINUE TO dozvoliti admin brisanje

#### Image i Video Upload

3.14 WHEN autentifikovani korisnik upload-uje slike za svoj memorial THEN sistem SHALL CONTINUE TO procesirati upload

3.15 WHEN autentifikovani korisnik dodaje YouTube video THEN sistem SHALL CONTINUE TO validirati i spremiti video

3.16 WHEN korisnik reorder-uje slike/videa THEN sistem SHALL CONTINUE TO ažurirati display_order

#### Search i Filtering

3.17 WHEN korisnik pretražuje javne memorijale THEN sistem SHALL CONTINUE TO vraćati filtrirane rezultate

3.18 WHEN korisnik filtrira po državi/mjestu rođenja/smrti THEN sistem SHALL CONTINUE TO primjenjivati filtere

3.19 WHEN korisnik sortira rezultate THEN sistem SHALL CONTINUE TO primjenjivati sort logiku

#### Location API

3.20 WHEN korisnik pozove GET /api/v1/locations/countries THEN sistem SHALL CONTINUE TO vraćati listu država

3.21 WHEN korisnik pozove GET /api/v1/locations/countries/{country}/places THEN sistem SHALL CONTINUE TO vraćati mjesta

#### Password Reset

3.22 WHEN korisnik zahtijeva password reset THEN sistem SHALL CONTINUE TO slati reset email sa tokenom

3.23 WHEN korisnik submituje validan reset token THEN sistem SHALL CONTINUE TO resetovati password

3.24 WHEN password se resetuje THEN sistem SHALL CONTINUE TO opozvati stare tokene

#### Admin Dashboard

3.25 WHEN admin pristupa dashboard-u THEN sistem SHALL CONTINUE TO prikazivati statistike

3.26 WHEN admin upravlja korisnicima THEN sistem SHALL CONTINUE TO dozvoliti role promjene i brisanje

3.27 WHEN admin upravlja settings THEN sistem SHALL CONTINUE TO dozvoliti ažuriranje hero settings i feature toggles

#### Contact Form

3.28 WHEN korisnik submituje validnu kontakt formu THEN sistem SHALL CONTINUE TO procesirati submission i prikazati success poruku

3.29 WHEN kontakt forma ima validation greške THEN sistem SHALL CONTINUE TO vraćati validation poruke

#### CORS za Legitimne Zahtjeve

3.30 WHEN frontend aplikacija sa FRONTEND_URL origin šalje API zahtjev THEN sistem SHALL CONTINUE TO dozvoliti CORS pristup

3.31 WHEN autentifikovani zahtjev sa credentials se šalje THEN sistem SHALL CONTINUE TO podržavati credentials

#### Session i Cookie Handling

3.32 WHEN korisnik koristi web rute THEN sistem SHALL CONTINUE TO održavati session state

3.33 WHEN CSRF token se validira na web formama THEN sistem SHALL CONTINUE TO provjeravati CSRF zaštitu
