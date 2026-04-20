<?php

return [
    'generic_failed' => 'Validacija nije uspela.',

    'email' => [
        'required' => 'Email adresa je obavezna.',
        'email' => 'Unesite ispravnu email adresu.',
        'unique' => 'Ova email adresa je vec registrovana.',
        'max' => 'Email adresa ne sme imati vise od 255 karaktera.',
    ],

    'password' => [
        'required' => 'Lozinka je obavezna.',
        'min' => 'Lozinka mora imati najmanje 12 karaktera.',
        'regex' => 'Lozinka mora sadrzati najmanje jedno veliko slovo, jedno malo slovo, jednu cifru i jedan specijalni znak.',
    ],

    'password_confirmation' => [
        'required' => 'Potvrda lozinke je obavezna.',
        'same' => 'Potvrda lozinke se ne poklapa.',
    ],

    'token' => [
        'required' => 'Reset token je obavezan.',
    ],

    'full_name' => [
        'string' => 'Ime i prezime mora biti tekst.',
        'max' => 'Ime i prezime ne sme imati vise od 255 karaktera.',
    ],

    'locale' => [
        'in' => 'Izabrani jezik nije podrzan.',
    ],

    'account' => [
        'current_password_required' => 'Trenutna lozinka je obavezna kada menjate email ili lozinku.',
        'current_password_invalid' => 'Trenutna lozinka nije ispravna.',
        'new_password_same' => 'Nova lozinka mora biti drugacija od trenutne lozinke.',
    ],

    'security' => [
        'bot_detected' => 'Registracija je blokirana antispam proverom.',
        'disposable_email' => 'Privremene email adrese nisu dozvoljene za registraciju.',
        'invalid_form' => 'Sigurnosna provera registracije nije uspela. Osvezite stranicu i pokusajte ponovo.',
        'invalid_signature' => 'Sigurnosna provera registracije nije uspela. Osvezite stranicu i pokusajte ponovo.',
        'too_fast' => 'Sacekajte nekoliko sekundi pre slanja registracije.',
        'expired' => 'Forma za registraciju je istekla. Osvezite stranicu i pokusajte ponovo.',
        'captcha_required' => 'Potvrdite sigurnosnu proveru pre kreiranja naloga.',
        'captcha_failed' => 'Sigurnosna provera nije uspela. Pokusajte ponovo.',
        'captcha_unavailable' => 'Sigurnosna provera je trenutno nedostupna. Pokusajte ponovo za koji trenutak.',
    ],
];
