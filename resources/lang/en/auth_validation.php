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
];
