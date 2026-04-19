<?php

return [
    'registration' => [
        'subject' => 'Welcome to Spomenar',
        'greeting' => 'Hello :name,',
        'line_1' => 'Your registration on Spomenar was successful.',
        'line_2' => 'You can now create and manage memorials from your account.',
        'cta' => 'Open dashboard',
        'footer' => 'If you did not create this account, please contact support.',
    ],
    'password_reset' => [
        'subject' => 'Password reset request',
        'greeting' => 'Hello :name,',
        'line_1' => 'We received a request to reset your password.',
        'line_2' => 'Click the button below to set a new password.',
        'line_3' => 'If you did not request a password reset, you can ignore this email.',
        'cta' => 'Reset password',
    ],
    'tribute_notification' => [
        'subject' => 'New memory message for :memorial',
        'greeting' => 'Hello :name,',
        'line_1' => 'A new memory message was left on the memorial ":memorial".',
        'line_2' => 'The details of the new message are below.',
        'author_name' => 'Sender',
        'author_email' => 'Email',
        'message_label' => 'Message',
        'cta' => 'Open memorial',
        'footer' => 'You received this notification because you are the owner of this memorial.',
    ],
];
