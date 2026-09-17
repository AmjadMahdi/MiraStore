<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisit
{
    /**
     * Excluded as crawler/health-check endpoints rather than real visits.
     */
    protected const EXCLUDED_PATHS = ['robots.txt', 'sitemap.xml', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('get') && ! in_array($request->path(), self::EXCLUDED_PATHS, true)) {
            SiteVisit::create([
                'path' => $request->path(),
                'ip_address' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
