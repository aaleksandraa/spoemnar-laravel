<?php

return [
    'generic_failed' => 'Validacija nije uspjela.',

    'email' => [
        'required' => 'Email adresa je obavezna.',
        'email' => 'Unesite ispravnu email adresu.',
        'unique' => 'Ova email adresa je vec registrirana.',
        'max' => 'Email adresa ne smije imati vise od 255 znakova.',
    ],

    'password' => [
        'required' => 'Lozinka je obavezna.',
        'min' => 'Lozinka mora imati najmanje 12 znakova.',
        'regex' => 'Lozinka mora sadrzavati najmanje jedno veliko slovo, jedno malo slovo, jednu znamenku i jedan specijalni znak.',
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
        'max' => 'Ime i prezime ne smije imati vise od 255 znakova.',
    ],

    'locale' => [
        'in' => 'Odabrani jezik nije podrzan.',
    ],

    'account' => [
        'current_password_required' => 'Trenutna lozinka je obavezna kada mijenjate email ili lozinku.',
        'current_password_invalid' => 'Trenutna lozinka nije ispravna.',
        'new_password_same' => 'Nova lozinka mora biti drugacija od trenutne lozinke.',
    ],

    'security' => [
        'bot_detected' => 'Registracija je blokirana antispam provjerom.',
        'disposable_email' => 'Privremene email adrese nisu dozvoljene za registraciju.',
        'invalid_form' => 'Sigurnosna provjera registracije nije uspjela. Osvjezite stranicu i pokusajte ponovno.',
        'invalid_signature' => 'Sigurnosna provjera registracije nije uspjela. Osvjezite stranicu i pokusajte ponovno.',
        'too_fast' => 'Pricekajte nekoliko sekundi prije slanja registracije.',
        'expired' => 'Obrazac za registraciju je istekao. Osvjezite stranicu i pokusajte ponovno.',
        'captcha_required' => 'Potvrdite sigurnosnu provjeru prije kreiranja racuna.',
        'captcha_failed' => 'Sigurnosna provjera nije uspjela. Pokusajte ponovno.',
        'captcha_unavailable' => 'Sigurnosna provjera je trenutno nedostupna. Pokusajte ponovno za koji trenutak.',
    ],
];
