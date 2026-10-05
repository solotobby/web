<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Postcard;
use App\Models\Stat;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected Creator $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Stat::create([
            'id' => 1,
            'sealed_count' => 50,
            'founding_count' => 10,
        ]);

        $this->creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Maya Lin',
            'handle' => '@mayaintokyo',
            'slug' => 'maya-in-tokyo',
            'platform' => 'Twitch',
            'email' => 'maya@example.com',
            'bio' => 'Late night gaming and cozy chats in Tokyo.',
            'milestone_title' => '100k Community Milestone Stream',
            'unlock_date' => Carbon::parse('2027-10-10'),
            'avatar_url' => null,
            'joined_at' => now(),
        ]);
    }

    public function test_sitemap_xml_renders_valid_response(): void
    {
        Postcard::create([
            'id' => (string) Str::uuid(),
            'number' => 1,
            'name' => 'Alex Rivera',
            'location' => 'Kyoto, Japan',
            'teaser' => 'Can’t wait for your 100k milestone celebration!',
            'addressed_to' => Carbon::parse('2027-10-10'),
            'sealed_at' => now(),
            'founding' => true,
            'creator_id' => $this->creator->id,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee(route('home'), false);
        $response->assertSee(route('creators'), false);
        $response->assertSee(route('explore'), false);
        $response->assertSee(route('with', $this->creator->slug), false);
    }

    public function test_homepage_contains_seo_metadata_and_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<link rel="canonical" href="' . route('home') . '">', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<script type="application/ld+json">', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"@type":"FAQPage"', false);
    }

    public function test_creator_door_contains_rich_creator_seo_and_json_ld(): void
    {
        $response = $this->get('/with/' . $this->creator->slug);

        $response->assertStatus(200);
        $response->assertSee('<link rel="canonical" href="' . route('with', $this->creator->slug) . '">', false);
        $response->assertSee('<meta property="og:image" content="' . route('og.creator', $this->creator->slug) . '">', false);
        $response->assertSee('"@type":"ProfilePage"', false);
        $response->assertSee('"@type":"Person"', false);
        $response->assertSee('"@type":"Event"', false);
        $response->assertSee('Maya Lin', false);
    }

    public function test_og_cover_image_generates_successfully(): void
    {
        $response = $this->get('/og/cover.png');

        $response->assertStatus(200);
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
    }

    public function test_og_creator_image_generates_successfully(): void
    {
        $response = $this->get('/og/creator/' . $this->creator->slug . '.png');

        $response->assertStatus(200);
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
    }

    public function test_creator_studio_has_noindex_robots_tag(): void
    {
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        $response = $this->get('/creators/studio');

        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_creator_access_has_noindex_robots_tag(): void
    {
        $response = $this->get('/creators/access');

        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_robots_txt_exists_and_contains_sitemap_directive(): void
    {
        $content = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /creators/studio', $content);
        $this->assertStringContainsString('Sitemap: https://getfanvault.com/sitemap.xml', $content);
    }

    public function test_webmanifest_exists_and_valid(): void
    {
        $manifestPath = public_path('site.webmanifest');
        $this->assertFileExists($manifestPath);

        $json = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($json);
        $this->assertEquals('FanVault', $json['name']);
        $this->assertEquals('#047857', $json['theme_color']);
    }
}
