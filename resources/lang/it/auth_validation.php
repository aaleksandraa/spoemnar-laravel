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

    'account' => [
        'current_password_required' => 'La password attuale e obbligatoria quando cambi email o password.',
        'current_password_invalid' => 'La password attuale inserita non e corretta.',
        'new_password_same' => 'La nuova password deve essere diversa da quella attuale.',
    ],

    'security' => [
        'bot_detected' => 'La registrazione e stata bloccata dal controllo antispam.',
        'disposable_email' => 'Gli indirizzi email temporanei non sono consentiti per la registrazione.',
        'invalid_form' => 'La verifica di sicurezza della registrazione non e riuscita. Aggiorna la pagina e riprova.',
        'invalid_signature' => 'La verifica di sicurezza della registrazione non e riuscita. Aggiorna la pagina e riprova.',
        'too_fast' => 'Attendi qualche secondo prima di inviare il modulo di registrazione.',
        'expired' => 'Questo modulo di registrazione e scaduto. Aggiorna la pagina e riprova.',
        'captcha_required' => 'Completa il controllo di sicurezza prima di creare un account.',
        'captcha_failed' => 'Il controllo di sicurezza non e riuscito. Riprova.',
        'captcha_unavailable' => 'Il controllo di sicurezza non e al momento disponibile. Riprova tra poco.',
    ],
];
