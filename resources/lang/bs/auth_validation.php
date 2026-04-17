<?php

return [
    'generic_failed' => 'Validacija nije uspjela.',

    'email' => [
        'required' => 'Email adresa je obavezna.',
        'email' => 'Unesite ispravnu email adresu.',
        'unique' => 'Ova email adresa je vec registrovana.',
        'max' => 'Email adresa ne smije imati vise od 255 karaktera.',
    ],

    'password' => [
        'required' => 'Lozinka je obavezna.',
        'min' => 'Lozinka mora imati najmanje 12 karaktera.',
        'regex' => 'Lozinka mora sadrzavati najmanje jedno veliko slovo, jedno malo slovo, jedan broj i jedan specijalni znak.',
    ],

    'password_confirmation' => [
        'required' => 'Potvrda lozinke je obavezna.',
        'same' => 'Potvrda lozinke se ne podudara.',
    ],

    'token' => [
        'required' => 'Reset token je obavezan.',
    ],

    'full_name' => [
        'string' => 'Ime i prezime mora biti tekst.',
        'max' => 'Ime i prezime ne smije imati vise od 255 karaktera.',
    ],

    'locale' => [
        'in' => 'Odabrani jezik nije podrzan.',
    ],

    'security' => [
        'bot_detected' => 'Registracija je blokirana antispam provjerom.',
        'disposable_email' => 'Privremene email adrese nisu dozvoljene za registraciju.',
        'invalid_form' => 'Sigurnosna provjera registracije nije uspjela. Osvjezite stranicu i pokusajte ponovo.',
        'invalid_signature' => 'Sigurnosna provjera registracije nije uspjela. Osvjezite stranicu i pokusajte ponovo.',
        'too_fast' => 'Sacekajte nekoliko sekundi prije slanja registracije.',
        'expired' => 'Forma za registraciju je istekla. Osvjezite stranicu i pokusajte ponovo.',
        'captcha_required' => 'Potvrdite sigurnosnu provjeru prije kreiranja naloga.',
        'captcha_failed' => 'Sigurnosna provjera nije uspjela. Pokusajte ponovo.',
        'captcha_unavailable' => 'Sigurnosna provjera je trenutno nedostupna. Pokusajte ponovo za koji trenutak.',
    ],
];
