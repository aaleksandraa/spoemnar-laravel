<?php

return [
    'generic_failed' => 'Validierung fehlgeschlagen.',

    'email' => [
        'required' => 'E-Mail-Adresse ist erforderlich.',
        'email' => 'Bitte geben Sie eine gueltige E-Mail-Adresse ein.',
        'unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
        'max' => 'Die E-Mail-Adresse darf nicht laenger als 255 Zeichen sein.',
    ],

    'password' => [
        'required' => 'Passwort ist erforderlich.',
        'min' => 'Das Passwort muss mindestens 12 Zeichen lang sein.',
        'regex' => 'Das Passwort muss mindestens einen Grossbuchstaben, einen Kleinbuchstaben, eine Zahl und ein Sonderzeichen enthalten.',
    ],

    'password_confirmation' => [
        'required' => 'Passwortbestaetigung ist erforderlich.',
        'same' => 'Passwortbestaetigung stimmt nicht ueberein.',
    ],

    'token' => [
        'required' => 'Reset-Token ist erforderlich.',
    ],

    'full_name' => [
        'string' => 'Der vollstaendige Name muss Text sein.',
        'max' => 'Der vollstaendige Name darf nicht laenger als 255 Zeichen sein.',
    ],

    'locale' => [
        'in' => 'Die ausgewaehlte Sprache wird nicht unterstuetzt.',
    ],
];
