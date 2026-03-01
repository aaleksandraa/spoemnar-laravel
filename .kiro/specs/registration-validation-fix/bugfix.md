# Bugfix Requirements Document

## Introduction

Korisnici ne mogu da se registruju jer dobijaju 422 Unprocessable Content grešku. Problem nastaje zbog neslaganja između frontend i backend validacije lozinke, što dovodi do toga da korisnici unose lozinke koje prolaze frontend validaciju ali padaju na backend-u. Dodatno, kada backend vrati validation errors, frontend ne prikazuje te greške korisniku, što ostavlja korisnika bez jasne informacije o tome šta je pogrešno.

Trenutno stanje:
- Frontend zahteva minimum 8 karaktera (minlength="8")
- Backend zahteva minimum 12 karaktera (min:12)
- Backend zahteva uppercase, lowercase, digit i special character (@$!%*?&)
- Frontend password strength checker ne proverava iste uslove
- Frontend ne prikazuje validation errors iz backend-a

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN korisnik unese lozinku sa 8-11 karaktera koja prolazi frontend validaciju THEN backend vraća 422 grešku ali frontend ne prikazuje specifičnu poruku o minimalnoj dužini

1.2 WHEN korisnik unese lozinku koja nema uppercase, lowercase, digit ili special character THEN backend vraća 422 grešku ali frontend ne prikazuje specifične zahteve za složenost lozinke

1.3 WHEN backend vrati 422 sa validation errors objektom THEN frontend prikazuje samo generičku poruku ili spaja sve greške u jedan string umesto da prikaže svaku grešku jasno

1.4 WHEN korisnik unosi lozinku THEN password strength checker koristi pravila (length >= 8) koja ne odgovaraju backend zahtevima (length >= 12)

1.5 WHEN korisnik unosi lozinku THEN password strength checker ne proverava obavezne zahteve za special character (@$!%*?&) koje backend zahteva

### Expected Behavior (Correct)

2.1 WHEN korisnik unese lozinku sa manje od 12 karaktera THEN frontend SHALL sprečiti submit i prikazati poruku "Lozinka mora imati najmanje 12 karaktera"

2.2 WHEN korisnik unese lozinku koja ne sadrži uppercase, lowercase, digit i special character (@$!%*?&) THEN frontend SHALL sprečiti submit i prikazati jasnu poruku o zahtevima za složenost

2.3 WHEN backend vrati 422 sa validation errors objektom THEN frontend SHALL prikazati svaku validation grešku jasno i čitljivo korisniku

2.4 WHEN korisnik unosi lozinku THEN password strength checker SHALL koristiti ista pravila kao backend (minimum 12 karaktera, uppercase, lowercase, digit, special character)

2.5 WHEN korisnik unosi lozinku THEN password strength checker SHALL prikazati koje zahteve lozinka ispunjava a koje ne (npr. "Nedostaje: veliko slovo, specijalni karakter")

### Unchanged Behavior (Regression Prevention)

3.1 WHEN korisnik unese validnu lozinku (12+ karaktera sa uppercase, lowercase, digit, special character) THEN sistem SHALL CONTINUE TO uspešno registrovati korisnika

3.2 WHEN korisnik unese email koji već postoji u bazi THEN sistem SHALL CONTINUE TO vratiti grešku "This email address is already registered"

3.3 WHEN korisnik unese lozinku i password_confirmation koje se ne poklapaju THEN frontend SHALL CONTINUE TO prikazati grešku "Lozinke se ne poklapaju"

3.4 WHEN registracija uspe THEN sistem SHALL CONTINUE TO poslati welcome email i preusmeriti korisnika na dashboard

3.5 WHEN korisnik unese nevažeću email adresu THEN sistem SHALL CONTINUE TO prikazati grešku o nevažećem email formatu
