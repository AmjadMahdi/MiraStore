<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('shein.index'), 'priority' => '0.9'],
        ];

        User::query()
            ->where('role', 'vendor')
            ->where('is_active', true)
            ->where('application_status', 'approved')
            ->whereHas('products', fn ($query) => $query->where('status', 'approved'))
            ->each(function (User $vendor) use (&$urls) {
                $urls[] = ['loc' => route('store.show', $vendor), 'priority' => '0.7'];
            });

        Product::query()
            ->where('status', 'approved')
            ->with('vendor')
            ->whereHas('vendor', fn ($query) => $query->where('is_active', true))
            ->each(function (Product $product) use (&$urls) {
                $urls[] = [
                    'loc' => route('store.product', [$product->vendor, $product]),
                    'lastmod' => $product->updated_at->toAtomString(),
                    'priority' => '0.8',
                ];
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
