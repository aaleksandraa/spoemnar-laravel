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
];
