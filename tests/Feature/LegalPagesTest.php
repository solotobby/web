<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_page_renders_successfully(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('GDPR & CCPA Compliant', false);
        $response->assertSee('Information We Collect');
        $response->assertSee('Stripe');
        $response->assertSee('privacy@getfanvault.com');
    }

    public function test_terms_of_service_page_renders_successfully(): void
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertSee('Terms of Service');
        $response->assertSee('80% Creator Revenue Share');
        $response->assertSee('Custom Creator Pricing');
        $response->assertSee('Stripe Connect Payouts');
        $response->assertSee('Broadcast License');
    }

    public function test_data_retention_page_renders_successfully(): void
    {
        $response = $this->get('/data-retention');

        $response->assertStatus(200);
        $response->assertSee('Data Retention Policy');
        $response->assertSee('Data Retention Matrix');
        $response->assertSee('7 Years (Statutory)');
        $response->assertSee('30 Minutes');
        $response->assertSee('90 Days');
    }

    public function test_data_deletion_page_renders_successfully(): void
    {
        $response = $this->get('/data-deletion');

        $response->assertStatus(200);
        $response->assertSee('Data & Account Deletion Procedure');
        $response->assertSee('Procedure for Fans');
        $response->assertSee('Procedure for Creators');
        $response->assertSee('48-Hour Acknowledgment');
        $response->assertSee('30-Day Eradication');
        $response->assertSee('privacy@getfanvault.com');
    }

    public function test_security_disclosure_page_renders_successfully(): void
    {
        $response = $this->get('/security');

        $response->assertStatus(200);
        $response->assertSee('Security & Vulnerability Disclosure');
        $response->assertSee('256-Bit Encryption');
        $response->assertSee('PCI-DSS Level 1');
        $response->assertSee('Our Safe Harbor Commitment');
        $response->assertSee('security@getfanvault.com');
    }

    public function test_footer_contains_links_to_all_five_legal_pages(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('privacy'));
        $response->assertSee(route('terms'));
        $response->assertSee(route('data-retention'));
        $response->assertSee(route('data-deletion'));
        $response->assertSee(route('security'));
    }

    public function test_sitemap_xml_contains_all_five_legal_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertSee(route('privacy'), false);
        $response->assertSee(route('terms'), false);
        $response->assertSee(route('data-retention'), false);
        $response->assertSee(route('data-deletion'), false);
        $response->assertSee(route('security'), false);
    }
}
