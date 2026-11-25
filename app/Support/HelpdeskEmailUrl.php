<?php

namespace App\Support;

class HelpdeskEmailUrl
{
    public static function route(string $name, mixed $parameters = []): string
    {
        $base = config('helpdesk.email_url');

        return rtrim($base, '/').route($name, $parameters, absolute: false);
    }
}
