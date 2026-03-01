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
];
