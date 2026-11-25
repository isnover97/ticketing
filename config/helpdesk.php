<?php

return [
    'inbound_secret' => env('HELPDESK_INBOUND_SECRET'),
    'autorespond' => env('HELPDESK_AUTORESPOND', true),
    'email_url' => env('HELPDESK_EMAIL_URL') ?: env('APP_URL', 'http://localhost'),
];
