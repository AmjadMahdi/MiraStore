<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /vendor/dashboard',
            'Disallow: /vendor/products',
            'Disallow: /vendor/analytics',
            'Disallow: /vendor/status',
            'Disallow: /login',
            'Disallow: /cart',
            'Disallow: /shein/shared',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
