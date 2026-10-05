<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Envelope;
use App\Models\Postcard;
use App\Models\Referral;
use App\Models\Stat;
use App\Services\CreatorAuthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreatorFanVaultTest extends TestCase
{
    use RefreshDatabase;

    protected Creator $creator;

    protected function setUp(): void
    {
        parent::setUp();

        Stat::create([
            'id' => 1,
            'sealed_count' => 10,
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

    public function test_home_page_renders_fanvault_branding(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('FanVault');
        $response->assertSee('The milestone vault for creators');
        $response->assertSee('Maya Lin');
        $response->assertSee('100k Community Milestone Stream');
    }

    public function test_creators_directory_renders_active_vaults(): void
    {
        $response = $this->get('/creators');

        $response->assertStatus(200);
        $response->assertSee('Creator Vaults');
        $response->assertSee('Maya Lin');
        $response->assertSee('100k Community Milestone Stream');
        $response->assertSee('or');
        $response->assertSee('Register or Login with Email');
    }

    public function test_email_first_registration_creates_pending_creator(): void
    {
        $auth = app(CreatorAuthService::class);
        $result = $auth->sendLoginLink('brandnew@example.com');

        $this->assertTrue($result['sent']);
        $this->assertTrue($result['is_new']);

        $creator = Creator::where('email', 'brandnew@example.com')->first();
        $this->assertNotNull($creator);
        $this->assertTrue($creator->needsOnboarding());
    }

    public function test_magic_link_for_pending_creator_redirects_to_onboard(): void
    {
        $auth = app(CreatorAuthService::class);
        $result = $auth->sendLoginLink('pendingcreator@example.com');
        $loginUrl = $result['login_url'];

        // Extract token from login URL
        preg_match('#/creators/login/([A-Za-z0-9]+)#', $loginUrl, $matches);
        $token = $matches[1];

        $response = $this->get('/creators/login/' . $token);
        $response->assertRedirect('/creators/onboard');
    }

    public function test_creator_onboard_page_renders_prompts_for_authenticated_creator(): void
    {
        $pending = Creator::create([
            'id' => (string) Str::uuid(),
            'email' => 'streamer@example.com',
            'name' => '',
            'handle' => '',
            'slug' => 'streamer-' . Str::random(5),
            'platform' => 'YouTube',
            'joined_at' => now(),
        ]);

        $this->withSession([
            'creator_id' => $pending->id,
            'creator_slug' => $pending->slug,
        ]);

        $response = $this->get('/creators/onboard');
        $response->assertStatus(200);
        $response->assertSee('Set up your community vault');
        $response->assertSee('Creator or Channel Name');
        $response->assertSee('Channel Handle / Username');
        $response->assertSee('Primary Platform');
        $response->assertSee('Upcoming Milestone Celebration');
        $response->assertSee('Target Unlock / Stream Date');
    }

    public function test_creator_can_complete_onboard_form_and_reach_studio(): void
    {
        $pending = Creator::create([
            'id' => (string) Str::uuid(),
            'email' => 'finisher@example.com',
            'name' => '',
            'handle' => '',
            'slug' => 'finisher-' . Str::random(5),
            'platform' => 'YouTube',
            'joined_at' => now(),
        ]);

        $this->withSession([
            'creator_id' => $pending->id,
            'creator_slug' => $pending->slug,
        ]);

        \Livewire\Livewire::test('creator-onboard')
            ->set('name', 'Tech With Tim')
            ->set('handle', 'techwithtim')
            ->set('platform', 'YouTube')
            ->set('milestone_title', '500k Subscriber Stream')
            ->set('unlock_date', '2027-11-20')
            ->set('bio', 'Looking forward to opening this vault live!')
            ->call('save')
            ->assertRedirect(route('creators.studio'));

        $pending->refresh();
        $this->assertEquals('Tech With Tim', $pending->name);
        $this->assertEquals('@techwithtim', $pending->handle);
        $this->assertEquals('techwithtim', $pending->slug);
        $this->assertEquals('500k Subscriber Stream', $pending->milestone_title);
        $this->assertFalse($pending->needsOnboarding());
    }

    public function test_creator_door_renders_milestone_and_sets_referral_session(): void
    {
        $response = $this->get('/with/maya-in-tokyo');

        $response->assertStatus(200);
        $response->assertSee('Maya Lin');
        $response->assertSee('100k Community Milestone Stream');
        $response->assertSee('Late night gaming');
        $response->assertSessionHas('ref_slug', 'maya-in-tokyo');
    }

    public function test_creator_join_page_renders(): void
    {
        $response = $this->get('/creators/join');

        $response->assertStatus(200);
        $response->assertSee('Creator Vault Setup');
        $response->assertSee('Who is opening this vault?');
    }

    public function test_creator_studio_shows_lock_screen_when_unauthenticated(): void
    {
        $response = $this->get('/creators/studio');

        $response->assertStatus(200);
        $response->assertSee('Your studio is locked');
    }

    public function test_creator_studio_renders_when_authenticated(): void
    {
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        $response = $this->get('/creators/studio');

        $response->assertStatus(200);
        $response->assertSee('Maya Lin’s Studio');
        $response->assertSee('Stream Reader View');
        $response->assertSee('YouTube Bio Snippet');
    }

    public function test_seal_page_loads_with_creator_context(): void
    {
        $response = $this->get('/seal?ref=maya-in-tokyo');

        $response->assertStatus(200);
        $response->assertSee('Maya Lin');
        $response->assertSee('Drafting Letter');
        $response->assertDontSee('80%');
    }

    public function test_fan_wall_explore_page_renders(): void
    {
        $response = $this->get('/explore');

        $response->assertStatus(200);
        $response->assertSee('Public Community Fan Wall');
    }

    public function test_message_page_displays_creator_pass(): void
    {
        $postcard = Postcard::create([
            'id' => (string) Str::uuid(),
            'number' => 1,
            'name' => 'Alex Rivera',
            'location' => 'Kyoto, Japan',
            'teaser' => 'Can’t wait for your 100k milestone celebration!',
            'addressed_to' => Carbon::parse('2027-10-10'),
            'sealed_at' => now(),
            'founding' => true,
            'creator_id' => $this->creator->id,
            'seeded' => false,
        ]);

        Envelope::create([
            'postcard_id' => $postcard->id,
            'letter' => 'Dear Maya, thank you for all the cozy streams when I moved to Japan!',
            'email' => 'alex@example.com',
        ]);

        $response = $this->get('/m/' . $postcard->id);

        $response->assertStatus(200);
        $response->assertSee('Maya Lin');
        $response->assertSee('Alex Rivera');
        $response->assertSee('Can’t wait for your 100k milestone celebration!');
    }

    public function test_creator_can_manage_multiple_milestones_in_studio(): void
    {
        // Give creator an initial milestone
        $initial = $this->creator->milestones()->create([
            'title' => '100k Community Milestone Stream',
            'unlock_date' => Carbon::parse('2027-10-10'),
            'is_active' => true,
        ]);

        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        \Livewire\Livewire::test('creator-studio')
            ->set('newMilestoneTitle', '250k Sub Marathon')
            ->set('newMilestoneDate', '2028-05-15')
            ->set('newMilestoneDesc', 'Celebration with chat!')
            ->set('newMilestoneActive', true)
            ->call('addMilestone')
            ->assertSee('created successfully')
            ->assertSee('250k Sub Marathon');

        $this->assertDatabaseHas('milestones', [
            'creator_id' => $this->creator->id,
            'title' => '250k Sub Marathon',
            'is_active' => 1,
        ]);

        $this->creator->refresh();
        $this->assertEquals('250k Sub Marathon', $this->creator->milestone_title);

        // Switch active milestone back to initial
        \Livewire\Livewire::test('creator-studio')
            ->call('setActiveMilestone', $initial->id);

        $initial->refresh();
        $this->assertTrue((bool) $initial->is_active);
        $this->creator->refresh();
        $this->assertEquals('100k Community Milestone Stream', $this->creator->milestone_title);
    }

    public function test_creator_door_shows_multiple_milestones(): void
    {
        $m1 = $this->creator->milestones()->create([
            'title' => '100k Community Milestone Stream',
            'unlock_date' => Carbon::parse('2027-10-10'),
            'is_active' => true,
        ]);

        $m2 = $this->creator->milestones()->create([
            'title' => 'Twitch Partner Anniversary',
            'unlock_date' => Carbon::parse('2028-01-01'),
            'is_active' => false,
        ]);

        $response = $this->get('/with/maya-in-tokyo');
        $response->assertStatus(200);
        $response->assertSee('100k Community Milestone Stream');
        $response->assertSee('Twitch Partner Anniversary');
        $response->assertSee('Active Topics & Milestones', false);
        $response->assertSee('Choose a Topic to Answer:');

        // Target specific milestone via query param
        $responseParam = $this->get('/with/maya-in-tokyo?milestone=' . $m2->id);
        $responseParam->assertStatus(200);
        $responseParam->assertSee('Twitch Partner Anniversary');
    }

    public function test_fan_can_seal_letter_linked_to_specific_milestone(): void
    {
        $m1 = $this->creator->milestones()->create([
            'title' => '100k Community Milestone Stream',
            'unlock_date' => Carbon::parse('2027-10-10'),
            'is_active' => true,
        ]);

        $m2 = $this->creator->milestones()->create([
            'title' => 'Charity 24hr Stream',
            'unlock_date' => Carbon::parse('2028-06-30'),
            'is_active' => false,
        ]);

        $this->withSession(['ref_slug' => $this->creator->slug]);

        \Livewire\Livewire::test('seal-page')
            ->call('selectMilestone', $m2->id)
            ->set('name', 'Chloe Fan')
            ->set('location', 'Toronto, Canada')
            ->set('email', 'chloe@example.com')
            ->set('message', 'Thank you for raising money for pets!')
            ->set('teaser', 'Keep saving the animals!')
            ->call('seal');

        $postcard = Postcard::where('name', 'Chloe Fan')->first();
        $this->assertNotNull($postcard);
        $this->assertEquals($this->creator->id, $postcard->creator_id);
        $this->assertEquals($m2->id, $postcard->milestone_id);

        // Verify keepsake pass page displays the specific milestone
        $response = $this->get('/m/' . $postcard->id);
        $response->assertStatus(200);
        $response->assertSee('Charity 24hr Stream');
    }

    public function test_creator_studio_renders_proper_dashboard_with_sidebars_and_tabs(): void
    {
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        $response = $this->get('/creators/studio');

        $response->assertStatus(200);
        $response->assertSee('Maya Lin’s Studio');
        $response->assertSee('Overview');
        $response->assertSee('Topics & Milestones', false);
        $response->assertSee('Fan Letters');
        $response->assertSee('Stream Reader View');
        $response->assertSee('Earnings & Ledger', false);
        $response->assertSee('Vault Settings');
        $response->assertSee('View Public Door');
        $response->assertSee('Log Out');
        $response->assertSee('YouTube Bio Snippet');
    }

    public function test_creator_can_apply_starter_presets_for_topics_and_milestones(): void
    {
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        \Livewire\Livewire::test('creator-studio')
            ->call('applyPreset', 'feedback')
            ->assertSet('newMilestoneTitle', 'What do you love most about our content?')
            ->call('applyPreset', 'ama')
            ->assertSet('newMilestoneTitle', 'Ask Me Anything (Live Q&A Stream)')
            ->call('applyPreset', 'ideas')
            ->assertSet('newMilestoneTitle', 'What video or project should we make next?')
            ->call('addMilestone')
            ->assertSee('Topic/Milestone “What video or project should we make next?” created successfully!');

        $this->assertDatabaseHas('milestones', [
            'creator_id' => $this->creator->id,
            'title' => 'What video or project should we make next?',
        ]);
    }

    public function test_creator_can_update_vault_settings_from_dashboard(): void
    {
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        \Livewire\Livewire::test('creator-studio')
            ->set('editName', 'Maya Lin Gaming')
            ->set('editHandle', 'mayagaming')
            ->set('editPlatform', 'YouTube')
            ->set('editBio', 'New updated bio for cozy streaming!')
            ->call('updateSettings')
            ->assertSee('Vault profile settings saved successfully');

        $this->creator->refresh();
        $this->assertEquals('Maya Lin Gaming', $this->creator->name);
        $this->assertEquals('@mayagaming', $this->creator->handle);
        $this->assertEquals('mayagaming', $this->creator->slug);
        $this->assertEquals('YouTube', $this->creator->platform);
        $this->assertEquals('New updated bio for cozy streaming!', $this->creator->bio);
    }

    public function test_creator_can_configure_minimum_seal_price_in_settings(): void
    {
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        // Attempting less than $3 should fail
        \Livewire\Livewire::test('creator-studio')
            ->set('editName', 'Maya Lin')
            ->set('editHandle', 'mayaintokyo')
            ->set('editPlatform', 'YouTube')
            ->set('editMinPrice', 2)
            ->call('updateSettings')
            ->assertHasErrors(['editMinPrice']);

        // Setting $10 should succeed
        \Livewire\Livewire::test('creator-studio')
            ->set('editName', 'Maya Lin')
            ->set('editHandle', 'mayaintokyo')
            ->set('editPlatform', 'YouTube')
            ->set('editMinPrice', 10)
            ->call('updateSettings')
            ->assertHasNoErrors();

        $this->creator->refresh();
        $this->assertEquals(1000, $this->creator->min_seal_price_cents);
        $this->assertEquals('10', $this->creator->minPriceDollars());
    }

    public function test_fan_can_choose_custom_amount_and_creator_earns_80_percent(): void
    {
        $this->withSession(['ref_slug' => $this->creator->slug]);

        \Livewire\Livewire::test('seal-page')
            ->set('name', 'Big Supporter')
            ->set('location', 'San Francisco, CA')
            ->set('email', 'supporter@example.com')
            ->set('message', 'Keep up the amazing content! Here is a super boost for the stream!')
            ->set('teaser', 'Huge congratulations!')
            ->call('setAmount', 25)
            ->assertSet('sealAmount', 25)
            ->call('seal');

        $postcard = Postcard::where('name', 'Big Supporter')->first();
        $this->assertNotNull($postcard);

        $referral = \App\Models\Referral::where('postcard_id', $postcard->id)->first();
        $this->assertNotNull($referral);
        $this->assertEquals(2500, $referral->amount_cents);
        $this->assertEquals(2000, $referral->cut_cents); // 80% of $25.00 is $20.00

        // Check Creator Studio reflects $20
        $this->withSession([
            'creator_id' => $this->creator->id,
            'creator_slug' => $this->creator->slug,
        ]);

        $response = $this->get('/creators/studio');
        $response->assertStatus(200);
        $response->assertSee('+$20.00');
    }

    public function test_creator_door_allows_fan_to_select_amount_and_boosters(): void
    {
        $response = $this->get('/with/' . $this->creator->slug);
        $response->assertStatus(200);
        $response->assertSee('Choose How Much You\'d Like to Give', false);
        $response->assertSee('Superfan');
        $response->assertSee('$25');
        $response->assertSee('VIP Patron');
        $response->assertSee('$50');

        \Livewire\Livewire::test('creator-door', ['slug' => $this->creator->slug])
            ->assertSet('selectedAmount', 5)
            ->call('setAmount', 25)
            ->assertSet('selectedAmount', 25)
            ->assertSee('$25.00')
            ->set('customAmount', '75')
            ->assertSet('selectedAmount', 75);
    }

    public function test_seal_page_initializes_with_amount_query_parameter(): void
    {
        $response = $this->get('/seal?ref=' . $this->creator->slug . '&amount=50');
        $response->assertStatus(200);
        $response->assertSee('$50.00');
        $response->assertSee('VIP Vault Patron');

        \Livewire\Livewire::withQueryParams(['ref' => $this->creator->slug, 'amount' => 50])
            ->test('seal-page')
            ->assertSet('sealAmount', 50)
            ->call('addBooster', 10)
            ->assertSet('sealAmount', 60);
    }
}
