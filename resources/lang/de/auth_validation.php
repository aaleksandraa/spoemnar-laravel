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

    'security' => [
        'bot_detected' => 'Die Registrierung wurde durch die Anti-Spam-Pruefung blockiert.',
        'disposable_email' => 'Temporare E-Mail-Adressen sind fuer die Registrierung nicht erlaubt.',
        'invalid_form' => 'Die Sicherheitspruefung der Registrierung ist fehlgeschlagen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.',
        'invalid_signature' => 'Die Sicherheitspruefung der Registrierung ist fehlgeschlagen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.',
        'too_fast' => 'Bitte warten Sie ein paar Sekunden, bevor Sie das Registrierungsformular absenden.',
        'expired' => 'Dieses Registrierungsformular ist abgelaufen. Bitte laden Sie die Seite neu und versuchen Sie es erneut.',
        'captcha_required' => 'Bitte schliessen Sie die Sicherheitspruefung ab, bevor Sie ein Konto erstellen.',
        'captcha_failed' => 'Die Sicherheitspruefung ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
        'captcha_unavailable' => 'Die Sicherheitspruefung ist derzeit nicht verfuegbar. Bitte versuchen Sie es in einem Moment erneut.',
    ],
];
