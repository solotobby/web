<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Postcard;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => 1.0,
            ],
            [
                'loc' => route('creators'),
                'lastmod' => now()->startOfDay()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => 0.9,
            ],
            [
                'loc' => route('explore'),
                'lastmod' => now()->startOfHour()->toAtomString(),
                'changefreq' => 'hourly',
                'priority' => 0.8,
            ],
            [
                'loc' => route('timeline'),
                'lastmod' => now()->startOfWeek()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => 0.7,
            ],
            [
                'loc' => route('seal'),
                'lastmod' => now()->startOfWeek()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => 0.8,
            ],
            [
                'loc' => route('creators.join'),
                'lastmod' => now()->startOfMonth()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => 0.8,
            ],
        ];

        // Creator Doors
        $creators = Creator::query()->whereNotNull('slug')->get();
        foreach ($creators as $creator) {
            $urls[] = [
                'loc' => route('with', $creator->slug),
                'lastmod' => ($creator->updated_at ?? now())->toAtomString(),
                'changefreq' => 'daily',
                'priority' => 0.9,
            ];
        }

        // Public Commemorative Passes (recent 500)
        $postcards = Postcard::query()->orderByDesc('number')->limit(500)->get();
        foreach ($postcards as $postcard) {
            $urls[] = [
                'loc' => route('message', $postcard->id),
                'lastmod' => ($postcard->updated_at ?? $postcard->sealed_at ?? now())->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => 0.6,
            ];
        }

        return response()
            ->view('sitemap', compact('urls'), 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
