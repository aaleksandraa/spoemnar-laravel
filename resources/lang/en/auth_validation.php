<?php

return [
    'generic_failed' => 'Validation failed.',

    'email' => [
        'required' => 'Email address is required.',
        'email' => 'Please enter a valid email address.',
        'unique' => 'This email address is already registered.',
        'max' => 'Email address may not be greater than 255 characters.',
    ],

    'password' => [
        'required' => 'Password is required.',
        'min' => 'Password must be at least 12 characters long.',
        'regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one digit, and one special character.',
    ],

    'password_confirmation' => [
        'required' => 'Password confirmation is required.',
        'same' => 'Password confirmation does not match.',
    ],

    'token' => [
        'required' => 'Reset token is required.',
    ],

    'full_name' => [
        'string' => 'Full name must be a text value.',
        'max' => 'Full name may not be greater than 255 characters.',
    ],

    'locale' => [
        'in' => 'Selected language is not supported.',
    ],

    'account' => [
        'current_password_required' => 'Your current password is required when changing your email or password.',
        'current_password_invalid' => 'The current password you entered is incorrect.',
        'new_password_same' => 'Your new password must be different from the current password.',
    ],

    'security' => [
        'bot_detected' => 'Registration request was blocked by the anti-spam check.',
        'disposable_email' => 'Disposable email addresses are not allowed for registration.',
        'invalid_form' => 'Registration form verification failed. Please refresh the page and try again.',
        'invalid_signature' => 'Registration form verification failed. Please refresh the page and try again.',
        'too_fast' => 'Please wait a few seconds before submitting the registration form.',
        'expired' => 'This registration form has expired. Please refresh the page and try again.',
        'captcha_required' => 'Please complete the security check before creating an account.',
        'captcha_failed' => 'Security check failed. Please try again.',
        'captcha_unavailable' => 'Security check is temporarily unavailable. Please try again in a moment.',
    ],
];
