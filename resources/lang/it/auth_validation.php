<?php

return [
    'generic_failed' => 'Validazione non riuscita.',

    'email' => [
        'required' => 'L\'indirizzo email e obbligatorio.',
        'email' => 'Inserisci un indirizzo email valido.',
        'unique' => 'Questo indirizzo email e gia registrato.',
        'max' => 'L\'indirizzo email non puo superare 255 caratteri.',
    ],

    'password' => [
        'required' => 'La password e obbligatoria.',
        'min' => 'La password deve contenere almeno 12 caratteri.',
        'regex' => 'La password deve contenere almeno una lettera maiuscola, una minuscola, una cifra e un carattere speciale.',
    ],

    'password_confirmation' => [
        'required' => 'La conferma password e obbligatoria.',
        'same' => 'La conferma password non corrisponde.',
    ],

    'token' => [
        'required' => 'Il token di reset e obbligatorio.',
    ],

    'full_name' => [
        'string' => 'Il nome completo deve essere un testo.',
        'max' => 'Il nome completo non puo superare 255 caratteri.',
    ],

    'locale' => [
        'in' => 'La lingua selezionata non e supportata.',
    ],
];
