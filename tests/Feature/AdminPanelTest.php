<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Milestone;
use App\Models\Postcard;
use App\Models\Referral;
use App\Support\Capsule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['app.admin_key' => 'secret-master-passcode-2026']);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_login_screen_renders_successfully(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('FanVault Executive Console');
        $response->assertSee('Master Executive Passcode');
    }

    public function test_admin_login_fails_with_invalid_passcode(): void
    {
        $response = $this->post('/admin/login', [
            'passcode' => 'wrong-passcode',
        ]);

        $response->assertSessionHasErrors('passcode');
        $this->assertFalse(session('admin_authenticated', false));
    }

    public function test_admin_login_succeeds_with_valid_passcode(): void
    {
        $response = $this->post('/admin/login', [
            'passcode' => 'secret-master-passcode-2026',
        ]);

        $response->assertRedirect('/admin');
        $this->assertTrue(session('admin_authenticated'));
        $this->assertNotNull(session('admin_logged_in_at'));
    }

    public function test_authenticated_admin_can_view_dashboard_and_financial_metrics(): void
    {
        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Top Gamer',
            'handle' => '@topgamer',
            'slug' => 'topgamer',
            'email' => 'gamer@example.com',
            'platform' => 'YouTube',
            'min_seal_price_cents' => 1000,
        ]);

        $postcard = Postcard::create([
            'id' => (string) Str::uuid(),
            'number' => 101,
            'name' => 'Alice Supporter',
            'location' => 'Los Angeles, CA',
            'teaser' => 'Keep up the amazing streams!',
            'creator_id' => $creator->id,
            'addressed_to' => Carbon::parse('2027-10-10'),
            'sealed_at' => now(),
            'founding' => true,
            'seeded' => false,
        ]);

        Referral::create([
            'postcard_id' => $postcard->id,
            'creator_id' => $creator->id,
            'amount_cents' => 1000,
            'cut_cents' => 800,
            'status' => 'pending',
        ]);

        $response = $this->withSession(['admin_authenticated' => true])
            ->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Executive Console');
        $response->assertSee('GROSS VOLUME (GMV)');
        $response->assertSee('PLATFORM NET (20%)');
        $response->assertSee('CREATOR SHARE (80%)');
        $response->assertSee('Alice Supporter');
        $response->assertSee('#' . Capsule::formatNumber(101));
    }

    public function test_admin_logout_clears_session(): void
    {
        $response = $this->withSession(['admin_authenticated' => true])
            ->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertFalse(session('admin_authenticated', false));
    }

    public function test_admin_can_generate_studio_login_and_settle_payouts(): void
    {
        $creator = Creator::create([
            'id' => (string) Str::uuid(),
            'name' => 'Gamer Star',
            'handle' => '@gamerstar',
            'slug' => 'gamerstar',
            'email' => 'gamer@example.com',
        ]);

        $postcard = Postcard::create([
            'id' => (string) Str::uuid(),
            'number' => 202,
            'name' => 'Fan Bob',
            'location' => 'Chicago',
            'teaser' => 'Love your content',
            'creator_id' => $creator->id,
            'addressed_to' => Carbon::parse('2027-10-10'),
            'sealed_at' => now(),
            'founding' => false,
            'seeded' => false,
        ]);

        $ref = Referral::create([
            'postcard_id' => $postcard->id,
            'creator_id' => $creator->id,
            'amount_cents' => 500,
            'cut_cents' => 400,
            'status' => 'pending',
        ]);

        \Livewire\Livewire::test('admin-dashboard')
            ->call('generateStudioLogin', $creator->id)
            ->assertSee('Direct Impersonation Session Generated')
            ->call('markCreatorPaid', $creator->id);

        $ref->refresh();
        $this->assertEquals('paid', $ref->status);
    }

    public function test_authenticated_admin_can_view_outreach_crm_and_seeded_prospects(): void
    {
        $this->seed(\Database\Seeders\CreatorProspectSeeder::class);
        $this->assertEquals(500, \App\Models\CreatorProspect::count());

        $response = $this->withSession(['admin_authenticated' => true])
            ->get('/admin?tab=prospects');

        $response->assertStatus(200);
        $response->assertSee('Creator Outreach Pipeline & CRM', false);
        $response->assertSee('500 Potential Creators');
        $response->assertSee('Jacksepticeye');
        $response->assertSee('Gaming');
        $response->assertSee('Jacksepticeye × FanVault — your next milestone', false);
    }

    public function test_admin_can_filter_and_search_prospects_in_crm(): void
    {
        $this->seed(\Database\Seeders\CreatorProspectSeeder::class);

        \Livewire\Livewire::test('admin-dashboard')
            ->set('tab', 'prospects')
            ->set('searchProspects', 'Unbox Therapy')
            ->assertSee('Unbox Therapy')
            ->assertSee('Technology')
            ->set('searchProspects', '')
            ->set('filterProspectSpeciality', 'Travel')
            ->assertSee('Travel')
            ->assertDontSee('Jacksepticeye');
    }

    public function test_admin_can_update_prospect_status_and_enrich_details(): void
    {
        $this->seed(\Database\Seeders\CreatorProspectSeeder::class);
        $prospect = \App\Models\CreatorProspect::where('prospect_number', 1)->first();
        $this->assertNotNull($prospect);

        \Livewire\Livewire::test('admin-dashboard')
            ->set('tab', 'prospects')
            ->call('inspectProspect', $prospect->id)
            ->set('editProspectEmail', 'jack@directagency.com')
            ->set('editProspectStatus', 'Contacted')
            ->set('editProspectNotes', 'Sent email pitch via agent')
            ->call('saveProspectDetails');

        $prospect->refresh();
        $this->assertEquals('jack@directagency.com', $prospect->email);
        $this->assertEquals('Contacted', $prospect->status);
        $this->assertEquals('Sent email pitch via agent', $prospect->internal_notes);
        $this->assertNotNull($prospect->last_contacted_at);
    }

    public function test_admin_can_export_prospects_csv(): void
    {
        $this->seed(\Database\Seeders\CreatorProspectSeeder::class);

        $component = \Livewire\Livewire::test('admin-dashboard');
        $response = $component->call('exportProspectsCsv');

        $this->assertNotNull($response);
    }
}

