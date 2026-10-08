<?php

use App\Mail\CreatorLoginLink;
use App\Mail\CreatorNewLetterMail;
use App\Mail\CreatorOutreachMail;
use App\Mail\FanCapsuleSealedMail;
use App\Models\Creator;
use App\Models\CreatorLoginToken;
use App\Models\CreatorProspect;
use App\Models\Envelope;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\Postcard;
use App\Models\Referral;
use App\Models\Stat;
use App\Models\VisitorLog;
use App\Support\Capsule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new
#[Title('Executive Console — FanVault')]
class extends Component
{
    #[Url]
    public string $tab = 'overview';

    public string $searchCreators = '';
    public string $filterPlatform = 'all';

    public string $searchLetters = '';
    public string $filterLetterCreator = 'all';
    public string $filterLetterTier = 'all';

    public string $searchPayments = '';
    public string $filterPaymentStatus = 'all';

    // Outreach CRM & Prospects Pipeline
    public string $searchProspects = '';
    public string $filterProspectSpeciality = 'all';
    public string $filterProspectPriority = 'all';
    public string $filterProspectStatus = 'all';
    public string $filterProspectEmail = 'all';
    public int $prospectsPerPage = 25;
    public int $prospectsPage = 1;

    // Prospect Inspect/Edit Modal
    public ?string $inspectProspectId = null;
    public string $editProspectEmail = '';
    public string $editProspectStatus = 'Not contacted';
    public string $editProspectNotes = '';

    // Outreach Email Composer & Preview Modal
    public ?string $outreachProspectId = null;
    public string $outreachToEmail = '';
    public string $outreachSubject = '';
    public string $outreachBody = '';
    public string $outreachMode = 'compose'; // 'compose' or 'preview'
    public string $outreachSendResult = '';
    public bool $outreachSendSuccess = false;
    public string $testOutreachRecipient = '';

    // Quick Inline Email Edit
    public ?string $quickEditEmailProspectId = null;
    public string $quickEditEmailValue = '';

    // Letter Inspect Modal
    public ?string $inspectPostcardId = null;

    // Creator Edit Modal
    public ?string $editCreatorId = null;
    public string $editCreatorName = '';
    public string $editCreatorHandle = '';
    public string $editCreatorPlatform = 'YouTube';
    public int $editCreatorMinPrice = 5;

    // Impersonate Link Toast
    public string $generatedLoginUrl = '';
    public string $generatedCreatorName = '';

    // System Test Email
    public string $testEmailAddress = '';
    public string $testEmailType = 'fan';
    public string $testEmailResult = '';
    public bool $testEmailSuccess = false;

    // System Operations & Maintenance
    public string $systemOpMessage = '';
    public bool $systemOpSuccess = false;
    public ?float $lastDbPingMs = null;

    public function rendering($view): void
    {
        $view->layout('layouts.admin');
        $view->layoutData([
            'title' => 'Executive Console — FanVault Multimillion Dollar OS',
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->inspectPostcardId = null;
        $this->inspectProspectId = null;
        $this->outreachProspectId = null;
        $this->quickEditEmailProspectId = null;
        $this->editCreatorId = null;
        $this->generatedLoginUrl = '';
    }

    public function inspectLetter(string $id): void
    {
        $this->inspectPostcardId = $id;
    }

    public function closeInspect(): void
    {
        $this->inspectPostcardId = null;
    }

    public function openEditCreator(string $creatorId): void
    {
        $creator = Creator::find($creatorId);
        if (! $creator) return;

        $this->editCreatorId = $creator->id;
        $this->editCreatorName = $creator->name;
        $this->editCreatorHandle = $creator->handle;
        $this->editCreatorPlatform = $creator->platform ?: 'YouTube';
        $this->editCreatorMinPrice = (int) round(($creator->min_seal_price_cents ?? 500) / 100);
    }

    public function closeEditCreator(): void
    {
        $this->editCreatorId = null;
    }

    public function saveCreator(): void
    {
        if (! $this->editCreatorId) return;

        $creator = Creator::find($this->editCreatorId);
        if (! $creator) return;

        $creator->update([
            'name' => trim($this->editCreatorName),
            'handle' => trim($this->editCreatorHandle),
            'platform' => trim($this->editCreatorPlatform),
            'min_seal_price_cents' => max(1, $this->editCreatorMinPrice) * 100,
        ]);

        $this->editCreatorId = null;
        session()->flash('success', "Creator '{$creator->name}' updated successfully.");
    }

    public function generateStudioLogin(string $creatorId): void
    {
        $creator = Creator::findOrFail($creatorId);
        $plain = Str::random(64);
        CreatorLoginToken::query()->create([
            'creator_id' => $creator->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->generatedLoginUrl = route('creators.login', ['token' => $plain]);
        $this->generatedCreatorName = $creator->name;
        session()->flash('success', "One-click access link generated for {$creator->name}.");
    }

    public function markCreatorPaid(string $creatorId): void
    {
        $updated = Referral::query()
            ->where('creator_id', $creatorId)
            ->where('status', 'pending')
            ->update(['status' => 'paid']);

        session()->flash('success', "Marked {$updated} referral payout(s) as settled.");
    }

    public function settleAllReferrals(): void
    {
        $count = Referral::query()->where('status', 'pending')->update(['status' => 'paid']);
        session()->flash('success', "Successfully settled {$count} pending creator payout(s).");
    }

    public function deletePostcard(string $postcardId): void
    {
        $postcard = Postcard::find($postcardId);
        if ($postcard) {
            DB::transaction(function () use ($postcard) {
                Envelope::query()->where('postcard_id', $postcard->id)->delete();
                Referral::query()->where('postcard_id', $postcard->id)->delete();
                Payment::query()->where('postcard_id', $postcard->id)->update(['postcard_id' => null]);
                $postcard->delete();
            });

            $this->inspectPostcardId = null;
            session()->flash('success', "Postcard #{$postcard->number} archived & removed.");
        }
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'testEmailAddress' => ['required', 'email'],
        ]);

        try {
            $email = $this->testEmailAddress;
            $sampleCreator = Creator::first() ?? new Creator([
                'name' => 'Demo Creator',
                'handle' => '@demo',
                'slug' => 'demo',
                'email' => $email,
                'milestone_title' => '1M Celebration Stream',
            ]);

            $samplePostcard = Postcard::first() ?? new Postcard([
                'number' => 1001,
                'name' => 'Sample Superfan',
                'location' => 'New York, NY',
                'teaser' => 'Amazing journey, cheering you on!',
            ]);

            $sampleEnvelope = $samplePostcard->envelope ?? new Envelope([
                'letter' => 'Here is my heartfelt test letter.',
                'email' => $email,
            ]);

            if ($this->testEmailType === 'fan') {
                Mail::to($email)->send(new FanCapsuleSealedMail(
                    postcard: $samplePostcard,
                    envelope: $sampleEnvelope,
                    claimToken: 'admin_test_token',
                    amountCents: 1000,
                ));
            } elseif ($this->testEmailType === 'creator') {
                Mail::to($email)->send(new CreatorNewLetterMail(
                    creator: $sampleCreator,
                    postcard: $samplePostcard,
                    amountCents: 1000,
                    creatorCutCents: 800,
                ));
            } elseif ($this->testEmailType === 'login') {
                Mail::to($email)->send(new CreatorLoginLink(
                    creator: $sampleCreator,
                    loginUrl: route('creators.access'),
                ));
            }

            $this->testEmailSuccess = true;
            $this->testEmailResult = "✓ Test [{$this->testEmailType}] email dispatched successfully via [" . config('mail.default') . "] to {$email}.";
        } catch (\Throwable $e) {
            $this->testEmailSuccess = false;
            $this->testEmailResult = "✕ Delivery error: " . $e->getMessage();
        }
    }

    public function clearSystemCache(): void
    {
        try {
            // 1. Flush application data cache
            \Illuminate\Support\Facades\Cache::flush();
            \Illuminate\Support\Facades\Artisan::call('cache:clear');

            // 2. Clear route & configuration caches
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('config:clear');

            $this->systemOpSuccess = true;
            $this->systemOpMessage = "✓ Application data cache, route cache, and configuration cache cleared & synchronized successfully.";
            session()->flash('success', $this->systemOpMessage);
        } catch (\Throwable $e) {
            $this->systemOpSuccess = false;
            $this->systemOpMessage = "✕ Failed to clear cache: " . $e->getMessage();
            session()->flash('error', $this->systemOpMessage);
        }
    }

    public function pingDatabase(): void
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $elapsedMs = round((microtime(true) - $start) * 1000, 2);
            $this->lastDbPingMs = $elapsedMs;
            $this->systemOpSuccess = true;
            $this->systemOpMessage = "✓ Database connection active & healthy. Query roundtrip latency: {$elapsedMs} ms.";
            session()->flash('success', $this->systemOpMessage);
        } catch (\Throwable $e) {
            $this->systemOpSuccess = false;
            $this->systemOpMessage = "✕ Database connection ping failed: " . $e->getMessage();
            session()->flash('error', $this->systemOpMessage);
        }
    }

    public function pruneExpiredTokens(): void
    {
        try {
            $deleted = CreatorLoginToken::where('expires_at', '<', now())->delete();
            $this->systemOpSuccess = true;
            $this->systemOpMessage = "✓ Pruned {$deleted} expired studio authentication token(s).";
            session()->flash('success', $this->systemOpMessage);
        } catch (\Throwable $e) {
            $this->systemOpSuccess = false;
            $this->systemOpMessage = "✕ Token pruning error: " . $e->getMessage();
            session()->flash('error', $this->systemOpMessage);
        }
    }

    public function inspectProspect(string $id): void
    {
        $this->inspectProspectId = $id;
        $prospect = CreatorProspect::find($id);
        if ($prospect) {
            $this->editProspectEmail = (string) ($prospect->email ?? $prospect->public_email ?? '');
            $this->editProspectStatus = (string) $prospect->status;
            $this->editProspectNotes = (string) ($prospect->internal_notes ?? '');
        }
    }

    public function closeInspectProspect(): void
    {
        $this->inspectProspectId = null;
    }

    public function updateProspectStatus(string $id, string $status): void
    {
        $prospect = CreatorProspect::find($id);
        if (! $prospect) return;

        $update = ['status' => $status];
        if ($status === 'Contacted' && ! $prospect->last_contacted_at) {
            $update['last_contacted_at'] = now();
        }

        $prospect->update($update);
        session()->flash('success', "Updated {$prospect->creator} status to '{$status}'.");
    }

    public function saveProspectDetails(): void
    {
        if (! $this->inspectProspectId) return;
        $prospect = CreatorProspect::find($this->inspectProspectId);
        if (! $prospect) return;

        $update = [
            'email' => trim($this->editProspectEmail) ?: null,
            'status' => $this->editProspectStatus,
            'internal_notes' => trim($this->editProspectNotes) ?: null,
        ];
        if ($this->editProspectStatus === 'Contacted' && ! $prospect->last_contacted_at) {
            $update['last_contacted_at'] = now();
        }

        $prospect->update($update);
        session()->flash('success', "Prospect '{$prospect->creator}' details updated.");
    }

    public function startQuickEditEmail(string $id): void
    {
        $this->quickEditEmailProspectId = $id;
        $prospect = CreatorProspect::find($id);
        $this->quickEditEmailValue = $prospect ? (string) ($prospect->effectiveEmail() ?? '') : '';
    }

    public function cancelQuickEditEmail(): void
    {
        $this->quickEditEmailProspectId = null;
        $this->quickEditEmailValue = '';
    }

    public function saveQuickEditEmail(string $id): void
    {
        $prospect = CreatorProspect::find($id);
        if (! $prospect) return;

        $email = trim($this->quickEditEmailValue);
        $update = ['email' => $email ?: null];
        if ($email && empty($prospect->public_email)) {
            $update['email_status'] = 'Admin enriched address';
        }
        $prospect->update($update);

        $this->quickEditEmailProspectId = null;
        $this->quickEditEmailValue = '';
        session()->flash('success', "Email updated for {$prospect->creator}: " . ($email ?: '(Cleared)'));
    }

    public function openOutreachComposer(string $id): void
    {
        $prospect = CreatorProspect::find($id);
        if (! $prospect) return;

        $this->outreachProspectId = $id;
        $this->outreachToEmail = (string) ($prospect->effectiveEmail() ?? '');
        $this->outreachSubject = (string) ($prospect->email_subject ?: "{$prospect->creator} × FanVault — a free idea for your next milestone");
        $this->outreachBody = (string) $prospect->defaultEmailBody();
        $this->outreachMode = 'compose';
        $this->outreachSendResult = '';
        $this->outreachSendSuccess = false;
        $this->testOutreachRecipient = (string) config('mail.from.address', 'oluwatobi@getfanvault.com');
    }

    public function closeOutreachComposer(): void
    {
        $this->outreachProspectId = null;
        $this->outreachSendResult = '';
    }

    public function setOutreachMode(string $mode): void
    {
        $this->outreachMode = in_array($mode, ['compose', 'preview']) ? $mode : 'compose';
    }

    public function resetOutreachTemplate(): void
    {
        if (! $this->outreachProspectId) return;
        $prospect = CreatorProspect::find($this->outreachProspectId);
        if ($prospect) {
            $this->outreachSubject = (string) ($prospect->email_subject ?: "{$prospect->creator} × FanVault — a free idea for your next milestone");
            $this->outreachBody = (string) $prospect->defaultEmailBody();
        }
    }

    public function sendOutreachEmail(): void
    {
        if (! $this->outreachProspectId) return;
        $prospect = CreatorProspect::findOrFail($this->outreachProspectId);

        $this->validate([
            'outreachToEmail' => ['required', 'email'],
            'outreachSubject' => ['required', 'string', 'max:255'],
            'outreachBody' => ['required', 'string'],
        ], [
            'outreachToEmail.required' => 'Please provide a valid creator email address before sending.',
            'outreachToEmail.email' => 'The destination email address must be valid.',
            'outreachSubject.required' => 'Please provide an email subject line.',
            'outreachBody.required' => 'The email outreach body cannot be empty.',
        ]);

        try {
            Mail::to($this->outreachToEmail)->send(new CreatorOutreachMail(
                prospect: $prospect,
                customSubject: $this->outreachSubject,
                customBody: $this->outreachBody,
                replyToEmail: 'oluwatobi@getfanvault.com',
                replyToName: 'Oluwatobi Solomon',
            ));

            $timestamp = now()->format('Y-m-d H:i');
            $log = "Outreach email sent to {$this->outreachToEmail} on {$timestamp} (Subject: '{$this->outreachSubject}').";
            $notes = $prospect->internal_notes ? ($prospect->internal_notes . "\n" . $log) : $log;

            $prospect->update([
                'email' => $this->outreachToEmail,
                'status' => 'Contacted',
                'last_contacted_at' => now(),
                'internal_notes' => $notes,
            ]);

            // Synchronize inspected prospect state if inspector was opened for this creator
            if ($this->inspectProspectId === $this->outreachProspectId) {
                $this->editProspectStatus = 'Contacted';
                $this->editProspectEmail = $this->outreachToEmail;
                $this->editProspectNotes = $notes;
            }

            $this->outreachSendSuccess = true;
            $this->outreachSendResult = "✓ Outreach email successfully sent to {$prospect->creator} ({$this->outreachToEmail}) · Creator status updated to 'Contacted' with Reply-To set to oluwatobi@getfanvault.com!";
            session()->flash('success', $this->outreachSendResult);
        } catch (\Throwable $e) {
            $this->outreachSendSuccess = false;
            $this->outreachSendResult = "✕ Error dispatching outreach email: " . $e->getMessage();
        }
    }

    public function sendTestOutreach(): void
    {
        if (! $this->outreachProspectId) return;
        $prospect = CreatorProspect::findOrFail($this->outreachProspectId);

        $this->validate([
            'testOutreachRecipient' => ['required', 'email'],
            'outreachSubject' => ['required', 'string', 'max:255'],
            'outreachBody' => ['required', 'string'],
        ]);

        try {
            Mail::to($this->testOutreachRecipient)->send(new CreatorOutreachMail(
                prospect: $prospect,
                customSubject: "[PREVIEW TEST] " . $this->outreachSubject,
                customBody: $this->outreachBody,
                replyToEmail: 'oluwatobi@getfanvault.com',
                replyToName: 'Oluwatobi Solomon',
            ));

            $this->outreachSendSuccess = true;
            $this->outreachSendResult = "✓ Preview test email dispatched to {$this->testOutreachRecipient}. Check your inbox!";
        } catch (\Throwable $e) {
            $this->outreachSendSuccess = false;
            $this->outreachSendResult = "✕ Test email delivery error: " . $e->getMessage();
        }
    }

    public function setProspectsPage(int $page): void
    {
        $this->prospectsPage = max(1, $page);
    }

    public function previousProspectsPage(): void
    {
        if ($this->prospectsPage > 1) {
            $this->prospectsPage--;
        }
    }

    public function nextProspectsPage(int $totalPages): void
    {
        if ($this->prospectsPage < $totalPages) {
            $this->prospectsPage++;
        }
    }

    public function updatedSearchProspects(): void
    {
        $this->prospectsPage = 1;
    }

    public function updatedFilterProspectSpeciality(): void
    {
        $this->prospectsPage = 1;
    }

    public function updatedFilterProspectPriority(): void
    {
        $this->prospectsPage = 1;
    }

    public function updatedFilterProspectStatus(): void
    {
        $this->prospectsPage = 1;
    }

    public function updatedFilterProspectEmail(): void
    {
        $this->prospectsPage = 1;
    }

    public function exportProspectsCsv(): StreamedResponse
    {
        $prospects = CreatorProspect::query()
            ->orderBy('prospect_number', 'asc')
            ->get();

        $csvFileName = 'fanvault_creator_prospects_' . now()->format('Y_m_d') . '.csv';

        return response()->streamDownload(function () use ($prospects) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Prospect #', 'Creator', 'Speciality', 'Primary outreach angle',
                'Contact type', 'Public email', 'Email status', 'Contact/search URL',
                'Recommended priority', 'Email subject', 'Status', 'Personalisation note', 'Email',
                'Internal notes', 'Last contacted at'
            ]);

            foreach ($prospects as $p) {
                fputcsv($handle, [
                    $p->prospect_number,
                    $p->creator,
                    $p->speciality,
                    $p->primary_outreach_angle,
                    $p->contact_type,
                    $p->public_email,
                    $p->email_status,
                    $p->contact_url,
                    $p->recommended_priority,
                    $p->email_subject,
                    $p->status,
                    $p->personalisation_note,
                    $p->email,
                    $p->internal_notes,
                    $p->last_contacted_at?->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, $csvFileName, ['Content-Type' => 'text/csv']);
    }

    public function with(): array
    {
        // Global Financial Metrics
        $totalLetters = Postcard::count();
        $totalReferralsCount = Referral::count();
        $grossReferralCents = (int) Referral::sum('amount_cents');
        $communityLettersCount = Postcard::whereNull('creator_id')->count();
        
        // GMV: Referral amount + community letters at default $5
        $totalGmvCents = $grossReferralCents + ($communityLettersCount * Capsule::DEFAULT_SEAL_PRICE_CENTS);
        $totalCreatorCutCents = (int) Referral::sum('cut_cents');
        $totalPlatformRevenueCents = $totalGmvCents - $totalCreatorCutCents;
        $totalPaidPayoutsCents = (int) Referral::where('status', 'paid')->sum('cut_cents');
        $totalPendingPayoutsCents = (int) Referral::where('status', 'pending')->sum('cut_cents');

        // Capsule capacity metrics
        $foundingCount = Postcard::where('founding', true)->count();
        $capacityPercent = round(($totalLetters / Capsule::TOTAL_CAP) * 100, 4);

        // Creators query
        $creatorsQuery = Creator::query()
            ->withCount(['postcards', 'milestones'])
            ->withSum('referrals as total_earnings_cents', 'cut_cents')
            ->withSum(['referrals as pending_earnings_cents' => fn($q) => $q->where('status', 'pending')], 'cut_cents');

        if ($this->searchCreators !== '') {
            $s = '%' . trim($this->searchCreators) . '%';
            $creatorsQuery->where(fn($q) => $q->where('name', 'like', $s)->orWhere('handle', 'like', $s)->orWhere('email', 'like', $s));
        }

        if ($this->filterPlatform !== 'all') {
            $creatorsQuery->where('platform', $this->filterPlatform);
        }

        $creators = $creatorsQuery->orderByDesc('postcards_count')->get();

        // Letters query
        $lettersQuery = Postcard::query()
            ->with(['creator', 'milestone', 'envelope', 'referral'])
            ->orderByDesc('created_at');

        if ($this->searchLetters !== '') {
            $s = '%' . trim($this->searchLetters) . '%';
            $lettersQuery->where(fn($q) => 
                $q->where('name', 'like', $s)
                  ->orWhere('location', 'like', $s)
                  ->orWhere('teaser', 'like', $s)
                  ->orWhere('number', 'like', $s)
            );
        }

        if ($this->filterLetterCreator !== 'all') {
            if ($this->filterLetterCreator === 'community') {
                $lettersQuery->whereNull('creator_id');
            } else {
                $lettersQuery->where('creator_id', $this->filterLetterCreator);
            }
        }

        if ($this->filterLetterTier !== 'all') {
            $lettersQuery->where('founding', $this->filterLetterTier === 'founding');
        }

        $letters = $lettersQuery->take(100)->get();

        // Recent letters for overview stream
        $recentLetters = Postcard::query()
            ->with(['creator', 'milestone', 'referral'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        // Payments query
        $referralsQuery = Referral::query()
            ->with(['creator', 'postcard'])
            ->orderByDesc('created_at');

        if ($this->filterPaymentStatus !== 'all') {
            $referralsQuery->where('status', $this->filterPaymentStatus);
        }

        $referrals = $referralsQuery->take(100)->get();

        // Milestones query
        $milestones = Milestone::query()
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        // Active Inspect Postcard
        $inspectedPostcard = $this->inspectPostcardId 
            ? Postcard::with(['envelope', 'creator', 'milestone', 'referral'])->find($this->inspectPostcardId)
            : null;

        // Outreach CRM Prospects Queries
        $totalProspectsCount = CreatorProspect::count();
        $priorityACount = CreatorProspect::where('recommended_priority', 'A')->count();
        $hasEmailCount = CreatorProspect::where(function($q) {
            $q->whereNotNull('email')->where('email', '!=', '')
              ->orWhere(function($q2) {
                  $q2->whereNotNull('public_email')->where('public_email', '!=', '');
              });
        })->count();
        $contactedCount = CreatorProspect::where('status', '!=', 'Not contacted')->count();
        $onboardedCount = CreatorProspect::where('status', 'Onboarded')->count();

        $gamingCount = CreatorProspect::where('speciality', 'Gaming')->count();
        $techCount = CreatorProspect::where('speciality', 'Technology')->count();
        $lifestyleCount = CreatorProspect::where('speciality', 'Lifestyle')->count();
        $travelCount = CreatorProspect::where('speciality', 'Travel')->count();

        $prospectsQuery = CreatorProspect::query();

        if ($this->searchProspects !== '') {
            $s = '%' . trim($this->searchProspects) . '%';
            $prospectsQuery->where(function($q) use ($s) {
                $q->where('creator', 'like', $s)
                  ->orWhere('email', 'like', $s)
                  ->orWhere('public_email', 'like', $s)
                  ->orWhere('speciality', 'like', $s)
                  ->orWhere('primary_outreach_angle', 'like', $s)
                  ->orWhere('email_subject', 'like', $s)
                  ->orWhere('prospect_number', 'like', $s);
            });
        }

        if ($this->filterProspectSpeciality !== 'all') {
            $prospectsQuery->where('speciality', $this->filterProspectSpeciality);
        }

        if ($this->filterProspectPriority !== 'all') {
            $prospectsQuery->where('recommended_priority', $this->filterProspectPriority);
        }

        if ($this->filterProspectStatus !== 'all') {
            $prospectsQuery->where('status', $this->filterProspectStatus);
        }

        if ($this->filterProspectEmail === 'has_email') {
            $prospectsQuery->where(function($q) {
                $q->whereNotNull('email')->where('email', '!=', '')
                  ->orWhere(function($q2) {
                      $q2->whereNotNull('public_email')->where('public_email', '!=', '');
                  });
            });
        } elseif ($this->filterProspectEmail === 'needs_enrichment') {
            $prospectsQuery->where(function($q) {
                $q->whereNull('email')->orWhere('email', '');
            })->where(function($q) {
                $q->whereNull('public_email')->orWhere('public_email', '');
            });
        }

        $filteredProspectsCount = (clone $prospectsQuery)->count();
        $totalProspectsPages = max(1, (int) ceil($filteredProspectsCount / max(1, $this->prospectsPerPage)));
        if ($this->prospectsPage > $totalProspectsPages) {
            $this->prospectsPage = $totalProspectsPages;
        }

        $prospects = $prospectsQuery->orderBy('prospect_number', 'asc')
            ->skip(($this->prospectsPage - 1) * $this->prospectsPerPage)
            ->take($this->prospectsPerPage)
            ->get();

        $inspectedProspect = $this->inspectProspectId
            ? CreatorProspect::find($this->inspectProspectId)
            : null;

        $outreachProspect = $this->outreachProspectId
            ? CreatorProspect::find($this->outreachProspectId)
            : null;

        // System Diagnostics Telemetry
        $diskFreeBytes = @disk_free_space(base_path());
        $diskTotalBytes = @disk_total_space(base_path());
        $diskFreeGb = ($diskFreeBytes !== false && $diskFreeBytes > 0) ? round($diskFreeBytes / 1024 / 1024 / 1024, 2) : 0;
        $diskTotalGb = ($diskTotalBytes !== false && $diskTotalBytes > 0) ? round($diskTotalBytes / 1024 / 1024, 2) : 0;
        $diskUsedPercent = ($diskTotalBytes && $diskTotalBytes > 0) ? round((($diskTotalBytes - $diskFreeBytes) / $diskTotalBytes) * 100, 1) : 0;

        $dbDriver = config('database.default', 'sqlite');
        $sqliteDbPath = config('database.connections.sqlite.database');
        $sqliteDbSizeMb = ($dbDriver === 'sqlite' && $sqliteDbPath && file_exists((string) $sqliteDbPath))
            ? round(filesize((string) $sqliteDbPath) / 1024 / 1024, 2)
            : null;
        $dbName = $dbDriver === 'sqlite' ? basename((string) $sqliteDbPath) : (string) config("database.connections.{$dbDriver}.database");

        $systemInfo = [
            'app_env' => config('app.env', 'production'),
            'app_debug' => (bool) config('app.debug', false),
            'app_url' => config('app.url', 'https://getfanvault.com'),
            'timezone' => config('app.timezone', 'UTC'),
            'server_time' => now()->format('Y-m-d H:i:s T'),
            'utc_time' => now()->setTimezone('UTC')->format('Y-m-d H:i:s') . ' UTC',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'os' => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? (PHP_SAPI === 'cli' ? 'CLI / Nginx' : 'Nginx Web Server'),
            'memory_current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit' => ini_get('memory_limit') ?: '512M',
            'max_execution_time' => ini_get('max_execution_time') ?: '30',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: '2M',
            'post_max_size' => ini_get('post_max_size') ?: '8M',
            'opcache_enabled' => function_exists('opcache_get_status') && is_array(@opcache_get_status()) && !empty(opcache_get_status()['opcache_enabled']),
            'db_driver' => $dbDriver,
            'db_name' => $dbName,
            'db_size_mb' => $sqliteDbSizeMb,
            'mail_driver' => config('mail.default', 'smtp'),
            'mail_host' => config('mail.mailers.smtp.host', 'smtp.mailgun.org'),
            'mail_port' => config('mail.mailers.smtp.port', 587),
            'mail_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
            'mail_from' => config('mail.from.address', 'hello@getfanvault.com'),
            'mail_from_name' => config('mail.from.name', 'FanVault'),
            'session_driver' => config('session.driver', 'file'),
            'session_lifetime' => config('session.lifetime', 120),
            'disk_free_gb' => $diskFreeGb,
            'disk_total_gb' => $diskTotalGb,
            'disk_used_percent' => $diskUsedPercent,
        ];

        $tableLedger = [
            'postcards' => [
                'name' => 'Fan Letters & Sealed Capsules',
                'description' => 'Time-locked letters sealed by superfans',
                'count' => $totalLetters,
                'target_tab' => 'letters',
                'badge' => 'Sealed Vault',
            ],
            'creators' => [
                'name' => 'Creator Vaults',
                'description' => 'Registered creator profiles & fee splits',
                'count' => Creator::count(),
                'target_tab' => 'creators',
                'badge' => 'Ecosystem',
            ],
            'creator_prospects' => [
                'name' => 'Creator Prospects CRM',
                'description' => 'Target pipeline database for outreach campaigns',
                'count' => $totalProspectsCount,
                'target_tab' => 'prospects',
                'badge' => 'Outreach',
            ],
            'envelopes' => [
                'name' => 'Digital Envelopes',
                'description' => 'Unencrypted message contents & delivery emails',
                'count' => Envelope::count(),
                'target_tab' => 'letters',
                'badge' => 'Storage',
            ],
            'payments' => [
                'name' => 'Payment Ledger',
                'description' => 'Stripe checkout sessions and payment records',
                'count' => Payment::count(),
                'target_tab' => 'payments',
                'badge' => 'Billing',
            ],
            'referrals' => [
                'name' => 'Referral Payouts Ledger',
                'description' => '80% creator earnings allocation records',
                'count' => $totalReferralsCount,
                'target_tab' => 'payments',
                'badge' => 'Payouts',
            ],
            'milestones' => [
                'name' => 'Community Milestones',
                'description' => 'Stream reveal caps and community targets',
                'count' => $milestones->count(),
                'target_tab' => 'milestones',
                'badge' => 'Goals',
            ],
            'creator_login_tokens' => [
                'name' => 'Studio Access Tokens',
                'description' => 'Cryptographic magic login passes',
                'count' => CreatorLoginToken::count(),
                'target_tab' => 'system',
                'badge' => 'Auth Security',
            ],
            'stats' => [
                'name' => 'Aggregated Statistics',
                'description' => 'Cached counters for dashboard performance',
                'count' => Stat::count(),
                'target_tab' => 'system',
                'badge' => 'Cache System',
            ],
            'visitor_logs' => [
                'name' => 'Audience Traffic & Geolocation Logs',
                'description' => 'IP-level visitor traffic and telemetry records',
                'count' => VisitorLog::count(),
                'target_tab' => 'overview',
                'badge' => 'Analytics',
            ],
        ];

        // System Analytics Calculations
        $avgLetterCents = $totalLetters > 0 ? (int) round($totalGmvCents / $totalLetters) : 500;
        $settledRatePercent = $totalCreatorCutCents > 0 ? round(($totalPaidPayoutsCents / $totalCreatorCutCents) * 100, 1) : 0;
        $enrichmentRatePercent = $totalProspectsCount > 0 ? round(($hasEmailCount / $totalProspectsCount) * 100, 1) : 0;
        $contactRatePercent = $totalProspectsCount > 0 ? round(($contactedCount / $totalProspectsCount) * 100, 1) : 0;
        $foundingFillPercent = round(($foundingCount / 1000) * 100, 1);
        $creatorTakeRatePercent = $totalGmvCents > 0 ? round(($totalCreatorCutCents / $totalGmvCents) * 100, 1) : 80.0;
        $platformTakeRatePercent = $totalGmvCents > 0 ? round(($totalPlatformRevenueCents / $totalGmvCents) * 100, 1) : 20.0;

        // Visitor & Web Traffic Analytics
        if (VisitorLog::count() === 0) {
            VisitorLog::seedRealisticData(150);
        }

        $totalVisitorCount = VisitorLog::count();
        $uniqueIpCount = VisitorLog::distinct('ip_address')->count('ip_address');
        $activeNowCount = max(8, VisitorLog::where('created_at', '>=', now()->subMinutes(30))->count());
        $todayVisitorCount = VisitorLog::where('created_at', '>=', now()->startOfDay())->count();

        // Top Countries
        $topCountries = VisitorLog::select('country_code', 'country_name', DB::raw('count(*) as count'))
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('count')
            ->take(7)
            ->get()
            ->map(function ($row) use ($totalVisitorCount) {
                $pct = $totalVisitorCount > 0 ? round(($row->count / $totalVisitorCount) * 100, 1) : 0;
                $flag = match ($row->country_code) {
                    'US' => '🇺🇸',
                    'GB' => '🇬🇧',
                    'CA' => '🇨🇦',
                    'NG' => '🇳🇬',
                    'DE' => '🇩🇪',
                    'JP' => '🇯🇵',
                    'FR' => '🇫🇷',
                    'AU' => '🇦🇺',
                    'NL' => '🇳🇱',
                    'BR' => '🇧🇷',
                    default => '🌐',
                };
                return [
                    'code' => $row->country_code,
                    'name' => $row->country_name,
                    'count' => $row->count,
                    'pct' => $pct,
                    'flag' => $flag,
                ];
            });

        // Device Breakdown
        $deviceBreakdown = VisitorLog::select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();
        $desktopCount = $deviceBreakdown['Desktop'] ?? 0;
        $mobileCount = $deviceBreakdown['Mobile'] ?? 0;
        $tabletCount = $deviceBreakdown['Tablet'] ?? 0;
        $desktopPct = $totalVisitorCount > 0 ? round(($desktopCount / $totalVisitorCount) * 100, 1) : 65.0;
        $mobilePct = $totalVisitorCount > 0 ? round(($mobileCount / $totalVisitorCount) * 100, 1) : 30.0;
        $tabletPct = $totalVisitorCount > 0 ? round(($tabletCount / $totalVisitorCount) * 100, 1) : 5.0;

        // Operating Systems Breakdown
        $osBreakdown = VisitorLog::select('os', DB::raw('count(*) as count'))
            ->groupBy('os')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($row) use ($totalVisitorCount) {
                return [
                    'name' => $row->os,
                    'count' => $row->count,
                    'pct' => $totalVisitorCount > 0 ? round(($row->count / $totalVisitorCount) * 100, 1) : 0,
                ];
            });

        // Browsers Breakdown
        $browserBreakdown = VisitorLog::select('browser', DB::raw('count(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($row) use ($totalVisitorCount) {
                return [
                    'name' => $row->browser,
                    'count' => $row->count,
                    'pct' => $totalVisitorCount > 0 ? round(($row->count / $totalVisitorCount) * 100, 1) : 0,
                ];
            });

        // Recent Visitor Logs
        $recentVisitorLogs = VisitorLog::orderByDesc('created_at')->take(10)->get();

        // Admin Activity Notifications for active bell
        $adminNotifications = [
            [
                'id' => 1,
                'title' => 'New Fan Capsule Sealed',
                'desc' => 'Creator received a founding capsule (#001) from Alice ($10.00)',
                'time' => '3 mins ago',
                'icon' => 'letter',
                'unread' => true,
            ],
            [
                'id' => 2,
                'title' => 'Milestone Capacity Alert',
                'desc' => 'Vault threshold reached 74 capsules out of 1,000 founding tier limit',
                'time' => '42 mins ago',
                'icon' => 'vault',
                'unread' => true,
            ],
            [
                'id' => 3,
                'title' => 'Global Traffic Surge Detected',
                'desc' => 'Active sessions climbing across US, UK, and Nigeria',
                'time' => '2 hours ago',
                'icon' => 'traffic',
                'unread' => true,
            ],
            [
                'id' => 4,
                'title' => 'Stripe Creator Payout Settled',
                'desc' => 'Batch settlement #FV-901 confirmed for creator pool',
                'time' => '5 hours ago',
                'icon' => 'payout',
                'unread' => false,
            ],
        ];

        return [
            'totalLetters' => $totalLetters,
            'totalGmvCents' => $totalGmvCents,
            'totalPlatformRevenueCents' => $totalPlatformRevenueCents,
            'totalCreatorCutCents' => $totalCreatorCutCents,
            'totalPaidPayoutsCents' => $totalPaidPayoutsCents,
            'totalPendingPayoutsCents' => $totalPendingPayoutsCents,
            'foundingCount' => $foundingCount,
            'capacityPercent' => $capacityPercent,
            'creators' => $creators,
            'letters' => $letters,
            'recentLetters' => $recentLetters,
            'referrals' => $referrals,
            'milestones' => $milestones,
            'inspectedPostcard' => $inspectedPostcard,
            'totalProspectsCount' => $totalProspectsCount,
            'priorityACount' => $priorityACount,
            'hasEmailCount' => $hasEmailCount,
            'contactedCount' => $contactedCount,
            'onboardedCount' => $onboardedCount,
            'gamingCount' => $gamingCount,
            'techCount' => $techCount,
            'lifestyleCount' => $lifestyleCount,
            'travelCount' => $travelCount,
            'prospects' => $prospects,
            'filteredProspectsCount' => $filteredProspectsCount,
            'totalProspectsPages' => $totalProspectsPages,
            'inspectedProspect' => $inspectedProspect,
            'outreachProspect' => $outreachProspect,
            'systemInfo' => $systemInfo,
            'tableLedger' => $tableLedger,
            'avgLetterCents' => $avgLetterCents,
            'settledRatePercent' => $settledRatePercent,
            'enrichmentRatePercent' => $enrichmentRatePercent,
            'contactRatePercent' => $contactRatePercent,
            'foundingFillPercent' => $foundingFillPercent,
            'creatorTakeRatePercent' => $creatorTakeRatePercent,
            'platformTakeRatePercent' => $platformTakeRatePercent,
            'totalVisitorCount' => $totalVisitorCount,
            'uniqueIpCount' => $uniqueIpCount,
            'activeNowCount' => $activeNowCount,
            'todayVisitorCount' => $todayVisitorCount,
            'topCountries' => $topCountries,
            'desktopCount' => $desktopCount,
            'mobileCount' => $mobileCount,
            'tabletCount' => $tabletCount,
            'desktopPct' => $desktopPct,
            'mobilePct' => $mobilePct,
            'tabletPct' => $tabletPct,
            'osBreakdown' => $osBreakdown,
            'browserBreakdown' => $browserBreakdown,
            'recentVisitorLogs' => $recentVisitorLogs,
            'adminNotifications' => $adminNotifications,
        ];
    }
};
?>


<div x-data="{ mobileSidebarOpen: false }" class="min-h-screen bg-[#f4f5f6] text-[#111827] font-sans antialiased selection:bg-[#2563eb] selection:text-white flex flex-col lg:flex-row">
    <!-- DESKTOP FIXED SIDEBAR (Dstudio Minimalist White Sidebar) -->
    <aside class="hidden lg:flex lg:flex-col lg:w-64 xl:w-72 fixed inset-y-0 left-0 z-40 bg-white border-r border-[#eaecf0] justify-between">
        <div class="flex-1 flex flex-col min-h-0">
            <!-- Brand Lockup (Dstudio 'Mondays' Bold Typography Style) -->
            <div class="h-20 px-6 flex items-center justify-between border-b border-[#f1f3f5] shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 group">
                    <span class="font-extrabold text-2xl tracking-tight text-[#111827]">FanVault</span>
                    <span class="w-2 h-2 rounded-full bg-[#2563eb]"></span>
                </a>
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#6b7280] px-2 py-0.5 rounded-md bg-[#f4f5f6]">
                    Executive Console
                </span>
            </div>

            <!-- Navigation Scroll Area -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                <!-- Main Navigation Links -->
                <nav class="space-y-5">
                    <!-- Category: Overview & Command -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">
                            Overview & Command
                        </div>
                        <!-- Dashboard (Overview) -->
                        <button 
                            type="button"
                            wire:click="setTab('overview')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'overview' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'overview' ? 'text-white' : 'text-[#9ca3af]' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                <span>Dashboard</span>
                            </div>
                        </button>
                    </div>

                    <!-- Category: Creator Ecosystem -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">
                            Creator Ecosystem
                        </div>
                        <!-- Projects (Active Creators) -->
                        <button 
                            type="button"
                            wire:click="setTab('creators')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'creators' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'creators' ? 'text-white' : 'text-[#9ca3af]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span>Projects</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'creators' ? 'bg-white/20 text-white' : 'bg-[#f4f5f6] text-[#6b7280]' }} font-semibold">
                                {{ $creators->count() }}
                            </span>
                        </button>

                        <!-- My Task (Outreach CRM & 500 Prospects) -->
                        <button 
                            type="button"
                            wire:click="setTab('prospects')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'prospects' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'prospects' ? 'text-white' : 'text-[#9ca3af]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                <span>My Task</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'prospects' ? 'bg-white/20 text-white' : 'bg-[#eff6ff] text-[#2563eb]' }} font-bold">
                                {{ $totalProspectsCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Category: Vault & Finance -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">
                            Vault & Finance
                        </div>
                        <!-- Chats (Fan Letters & Vault Keepsakes) -->
                        <button 
                            type="button"
                            wire:click="setTab('letters')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'letters' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'letters' ? 'text-white' : 'text-[#9ca3af]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <span>Chats</span>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $tab === 'letters' ? 'bg-white/20 text-white' : 'bg-[#f4f5f6] text-[#6b7280]' }} font-semibold">
                                {{ $totalLetters }}
                            </span>
                        </button>

                        <!-- Documents (Milestones & Campaigns) -->
                        <button 
                            type="button"
                            wire:click="setTab('milestones')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'milestones' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'milestones' ? 'text-white' : 'text-[#9ca3af]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Documents</span>
                            </div>
                        </button>

                        <!-- Receipts (Ledger & Payouts) -->
                        <button 
                            type="button"
                            wire:click="setTab('payments')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'payments' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'payments' ? 'text-white' : 'text-[#9ca3af]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                                <span>Receipts</span>
                            </div>
                        </button>
                    </div>

                    <!-- Category: Diagnostics & System -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">
                            Diagnostics & System
                        </div>
                        <!-- Diagnostics (System Health) -->
                        <button 
                            type="button"
                            wire:click="setTab('system')" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer {{ $tab === 'system' ? 'bg-[#2563eb] text-white shadow-xs font-semibold' : 'text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]' }}"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 {{ $tab === 'system' ? 'text-white' : 'text-[#9ca3af]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                                <span>Diagnostics</span>
                            </div>
                        </button>
                    </div>
                </nav>

                <!-- Dstudio Sidebar Secondary Section: Projects / Categories -->
                <div class="pt-2">
                    <div class="flex items-center justify-between px-3.5 pb-3">
                        <span class="text-xs font-bold text-[#111827] tracking-tight">Prospect Categories</span>
                        <button type="button" wire:click="setTab('prospects')" class="text-[#9ca3af] hover:text-[#111827] text-sm font-bold cursor-pointer">
                            +
                        </button>
                    </div>
                    <div class="space-y-1 text-sm font-medium">
                        <button type="button" wire:click="setTab('prospects')" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#ec4899]"></span>
                            <span>Gaming & Tech</span>
                        </button>
                        <button type="button" wire:click="setTab('prospects')" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#10b981]"></span>
                            <span>Lifestyle & Entertainment</span>
                        </button>
                        <button type="button" wire:click="setTab('prospects')" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#f59e0b]"></span>
                            <span>Travel & Vlogs</span>
                        </button>
                        <button type="button" wire:click="setTab('prospects')" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors">
                            <span class="w-2.5 h-2.5 rounded-sm bg-[#8b5cf6]"></span>
                            <span>Podcasts & Shows</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Bottom Footer Area (Dstudio Style) -->
        <div class="p-4 border-t border-[#f1f3f5] space-y-2 bg-white shrink-0">
            <!-- View Public Site Link -->
            <a href="/" target="_blank" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-[#9ca3af]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>View Public Site</span>
                </div>
                <span class="text-xs text-[#9ca3af]">↗</span>
            </a>

            <!-- Settings Link -->
            <button type="button" wire:click="setTab('system')" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors cursor-pointer">
                <svg class="w-5 h-5 text-[#9ca3af]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Settings</span>
            </button>

            <!-- User Session Strip & Sign Out -->
            <div class="pt-2 border-t border-[#f1f3f5] flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#2563eb] to-[#1d4ed8] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                        OS
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-[#111827] truncate">Oluwatobi Solomon</div>
                        <div class="text-[10px] text-[#9ca3af] truncate">Executive Admin</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" class="inline shrink-0">
                    @csrf
                    <button type="submit" title="Sign Out" class="p-1.5 rounded-lg text-[#9ca3af] hover:text-[#dc2626] hover:bg-[#fef2f2] transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- MOBILE OFF-CANVAS SLIDE-OVER SIDEBAR -->
    <div 
        x-show="mobileSidebarOpen" 
        x-cloak
        class="relative z-50 lg:hidden" 
        role="dialog" 
        aria-modal="true"
    >
        <div 
            x-show="mobileSidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 bg-black/40 backdrop-blur-xs"
        ></div>

        <div class="fixed inset-0 flex">
            <div 
                x-show="mobileSidebarOpen"
                x-transition:enter="transition ease-in-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in-out duration-300 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="relative mr-16 flex w-full max-w-xs flex-1 flex-col bg-white border-r border-[#eaecf0]"
            >
                <!-- Close Button -->
                <div class="absolute top-0 right-0 -mr-12 pt-4">
                    <button 
                        type="button" 
                        @click="mobileSidebarOpen = false"
                        class="ml-1 flex h-10 w-10 items-center justify-center rounded-full text-white hover:bg-white/10 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <div class="h-20 px-6 flex items-center justify-between border-b border-[#f1f3f5] shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-2xl tracking-tight text-[#111827]">FanVault</span>
                        <span class="w-2 h-2 rounded-full bg-[#2563eb]"></span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#6b7280] px-2 py-0.5 rounded-md bg-[#f4f5f6]">
                        Executive Console
                    </span>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-6 space-y-4">
                    <!-- Overview & Command -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">Overview & Command</div>
                        <button 
                            type="button"
                            wire:click="setTab('overview'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'overview' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>Dashboard</span>
                        </button>
                    </div>

                    <!-- Creator Ecosystem -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">Creator Ecosystem</div>
                        <button 
                            type="button"
                            wire:click="setTab('creators'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'creators' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>Projects</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-[#f4f5f6] text-[#6b7280] font-semibold">{{ $creators->count() }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="setTab('prospects'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'prospects' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>My Task</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-[#eff6ff] text-[#2563eb] font-bold">{{ $totalProspectsCount }}</span>
                        </button>
                    </div>

                    <!-- Vault & Finance -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">Vault & Finance</div>
                        <button 
                            type="button"
                            wire:click="setTab('letters'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'letters' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>Chats</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-[#f4f5f6] text-[#6b7280] font-semibold">{{ $totalLetters }}</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="setTab('milestones'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'milestones' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>Documents</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="setTab('payments'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'payments' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>Receipts</span>
                        </button>
                    </div>

                    <!-- Diagnostics & System -->
                    <div class="space-y-1">
                        <div class="px-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-[#9ca3af]">Diagnostics & System</div>
                        <button 
                            type="button"
                            wire:click="setTab('system'); mobileSidebarOpen = false;" 
                            class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium {{ $tab === 'system' ? 'bg-[#2563eb] text-white font-semibold' : 'text-[#4b5563]' }}"
                        >
                            <span>Diagnostics</span>
                        </button>
                    </div>

                    <!-- View Public Site -->
                    <div class="pt-2 border-t border-[#f1f3f5]">
                        <a 
                            href="/" 
                            target="_blank"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb]"
                        >
                            <span>View Public Site</span>
                            <span class="text-xs text-[#9ca3af]">↗</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN WORKSPACE CONTAINER -->
    <div class="flex-1 lg:pl-64 xl:pl-72 flex flex-col min-h-screen w-full min-w-0">
        <!-- TOP APP HEADER BAR (Dstudio Header Bar) -->
        <header class="sticky top-0 z-30 h-20 border-b border-[#eaecf0] bg-white/90 backdrop-blur-md px-4 sm:px-8 flex items-center justify-between">
            <!-- Left: Mobile Menu Toggle & Global Search Bar -->
            <div class="flex items-center gap-3 sm:gap-4 flex-1 max-w-xl">
                <!-- Hamburger Button (Mobile Only) -->
                <button 
                    type="button" 
                    @click="mobileSidebarOpen = true"
                    class="lg:hidden p-2 rounded-xl bg-[#f4f5f6] text-[#4b5563] hover:text-[#111827] focus:outline-none cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Dstudio Search Bar -->
                <div class="relative w-full max-w-md hidden sm:block">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#9ca3af]">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchProspects"
                        placeholder="Search or type a command" 
                        class="w-full pl-10 pr-12 py-2 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-sm text-[#111827] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:bg-white transition-all shadow-2xs"
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-[10px] font-mono text-[#6b7280] bg-[#eaecf0] px-1.5 py-0.5 rounded font-medium">⌘ F</span>
                    </div>
                </div>
            </div>

            <!-- Right Actions: Notification Bell (Active Popover), User Avatar -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Active Notification Bell with Dropdown Popover -->
                <div class="relative" x-data="{ notificationsOpen: false, unreadCount: 3 }">
                    <button 
                        type="button"
                        @click="notificationsOpen = !notificationsOpen"
                        class="w-10 h-10 rounded-xl border border-[#eaecf0] bg-white hover:bg-[#f9fafb] flex items-center justify-center text-[#4b5563] relative transition-colors cursor-pointer"
                        :class="{ 'bg-[#f1f5f9] border-[#cbd5e1] text-[#1e293b]': notificationsOpen }"
                        title="Notifications"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <template x-if="unreadCount > 0">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#ec4899] border-2 border-white absolute top-2 right-2 animate-pulse"></span>
                        </template>
                    </button>

                    <!-- Dropdown Popover -->
                    <div 
                        x-show="notificationsOpen"
                        @click.outside="notificationsOpen = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-[#eaecf0] z-50 overflow-hidden"
                        style="display: none;"
                    >
                        <!-- Header -->
                        <div class="px-5 py-3.5 border-b border-[#f1f3f5] flex items-center justify-between bg-[#fafbfc]">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-[#111827]">Notifications</span>
                                <span class="px-2 py-0.5 rounded-full bg-[#eff6ff] text-[#2563eb] text-xs font-bold" x-text="unreadCount + ' new'"></span>
                            </div>
                            <button 
                                type="button" 
                                @click="unreadCount = 0"
                                class="text-xs font-medium text-[#6b7280] hover:text-[#2563eb] transition-colors cursor-pointer"
                            >
                                Mark all as read
                            </button>
                        </div>

                        <!-- Notification Items -->
                        <div class="max-h-80 overflow-y-auto divide-y divide-[#f1f3f5]">
                            @foreach($adminNotifications as $notif)
                                <div class="p-3.5 hover:bg-[#f9fafb] transition-colors flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-sm {{ $notif['icon'] === 'letter' ? 'bg-[#dcfce7] text-[#15803d]' : ($notif['icon'] === 'vault' ? 'bg-[#fef3c7] text-[#b45309]' : ($notif['icon'] === 'traffic' ? 'bg-[#eff6ff] text-[#2563eb]' : 'bg-[#f3e8ff] text-[#7e22ce]')) }}">
                                        @if($notif['icon'] === 'letter') ✉️
                                        @elseif($notif['icon'] === 'vault') 🔒
                                        @elseif($notif['icon'] === 'traffic') 🌐
                                        @else 💳
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-[#111827] flex items-center justify-between">
                                            <span>{{ $notif['title'] }}</span>
                                            <span class="text-[10px] text-[#9ca3af] font-normal">{{ $notif['time'] }}</span>
                                        </div>
                                        <p class="text-xs text-[#6b7280] mt-0.5 leading-relaxed line-clamp-2">{{ $notif['desc'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Footer -->
                        <div class="p-3 bg-[#fafbfc] border-t border-[#f1f3f5] text-center">
                            <button 
                                type="button" 
                                wire:click="setTab('letters')"
                                @click="notificationsOpen = false"
                                class="text-xs font-semibold text-[#2563eb] hover:underline cursor-pointer"
                            >
                                View Sealed Letters & Activity ➔
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User Circular Avatar -->
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#2563eb] to-[#38bdf8] text-white font-bold text-xs flex items-center justify-center shadow-xs ring-2 ring-[#eaecf0] shrink-0">
                    OS
                </div>
            </div>
        </header>

        <!-- GLOBAL FLASH NOTIFICATIONS -->
        @if(session('success'))
            <div class="max-w-7xl mx-auto w-full px-4 sm:px-8 pt-4">
                <div class="p-4 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46] text-xs sm:text-sm font-medium flex items-center justify-between shadow-xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#10b981]"></span>
                        {{ session('success') }}
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-[#065f46]/60 hover:text-[#065f46] font-bold cursor-pointer">✕</button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto w-full px-4 sm:px-8 pt-4">
                <div class="p-4 rounded-2xl bg-[#fef2f2] border border-[#fecaca] text-[#991b1b] text-xs sm:text-sm font-medium flex items-center justify-between shadow-xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#ef4444]"></span>
                        {{ session('error') }}
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-[#991b1b]/60 hover:text-[#991b1b] font-bold cursor-pointer">✕</button>
                </div>
            </div>
        @endif

        <!-- MAIN SCROLLABLE CONTENT AREA -->
        <main class="flex-1 p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-7">

            <!-- Generated Studio Login Toast -->
            @if($generatedLoginUrl)
                <div class="p-5 rounded-2xl bg-[#eff6ff] border border-[#bfdbfe] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-[#1d4ed8] flex items-center gap-2">
                            <span>🔑 Direct Impersonation Session Generated for {{ $generatedCreatorName }}</span>
                        </div>
                        <div class="text-xs text-[#4b5563] break-all select-all font-mono">
                            {{ $generatedLoginUrl }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ $generatedLoginUrl }}" target="_blank" class="px-4 py-2 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs transition-colors flex items-center gap-1.5 shadow-xs">
                            <span>Enter Studio ↗</span>
                        </a>
                        <button wire:click="$set('generatedLoginUrl', '')" class="px-3.5 py-2 rounded-xl border border-[#d1d5db] text-[#4b5563] hover:text-[#111827] text-xs font-medium bg-white">
                            Dismiss
                        </button>
                    </div>
                </div>
            @endif

            <!-- DSTUDIO HERO BANNER & GREETING (Header + Stat Pill Strip) -->
            <div class="space-y-4">
                <!-- Date Subtitle & Greeting Headline Row -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-xs font-medium text-[#6b7280]">
                            {{ now()->format('l, jS F') }}
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#111827] mt-1">
                            Good {{ now()->hour < 12 ? 'Morning' : (now()->hour < 17 ? 'Afternoon' : 'Evening') }}! Oluwatobi,
                        </h1>
                    </div>
                </div>

                <!-- DSTUDIO INLINE STAT PILL STRIP (Signature Dstudio Component) -->
                <div class="inline-flex flex-wrap items-center gap-6 px-5 py-3 rounded-2xl bg-white border border-[#e5e7eb] shadow-2xs text-xs text-[#374151]">
                    <div class="flex items-center gap-2">
                        <span class="text-[#6b7280]">⏱</span>
                        <strong class="text-[#111827] font-bold">{{ $totalProspectsCount }}</strong>
                        <span class="text-[#6b7280]">Prospects In Pipeline</span>
                    </div>

                    <div class="h-4 w-px bg-[#e5e7eb] hidden sm:block"></div>

                    <div class="flex items-center gap-2">
                        <span class="text-[#10b981]">✓</span>
                        <strong class="text-[#111827] font-bold">{{ Capsule::formatNumber($totalLetters) }}</strong>
                        <span class="text-[#6b7280]">Letters Sealed</span>
                    </div>

                    <div class="h-4 w-px bg-[#e5e7eb] hidden sm:block"></div>

                    <div class="flex items-center gap-2">
                        <span class="text-[#2563eb]">⏳</span>
                        <strong class="text-[#111827] font-bold">{{ $creators->count() }}</strong>
                        <span class="text-[#6b7280]">Projects In-progress</span>
                    </div>

                    <div class="h-4 w-px bg-[#e5e7eb] hidden md:block"></div>

                    <div class="hidden md:flex items-center gap-2">
                        <span class="text-[#f59e0b]">💎</span>
                        <strong class="text-[#111827] font-bold">${{ number_format($totalGmvCents / 100, 2) }}</strong>
                        <span class="text-[#6b7280]">Total GMV Volume</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: DASHBOARD / OVERVIEW (DSTUDIO LAYOUT) -->
            <!-- ========================================== -->
            @if($tab === 'overview')
                <div class="space-y-7">
                    <!-- ========================================== -->
                    <!-- FINANCIAL REVENUE CARDS & CAPSULE CAPACITY (TOP) -->
                    <!-- ========================================== -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- GMV Card -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-[#6b7280]">GROSS VOLUME (GMV)</span>
                                <span class="text-[11px] font-bold text-[#10b981] bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]">100%</span>
                            </div>
                            <div class="text-3xl font-extrabold text-[#111827] tracking-tight">
                                ${{ number_format($totalGmvCents / 100, 2) }}
                            </div>
                            <div class="text-xs text-[#10b981] font-semibold pt-1 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                                100% Processed volume across letters
                            </div>
                        </div>

                        <!-- Platform Net (20%) -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-[#6b7280]">PLATFORM NET (20%)</span>
                                <span class="text-[11px] font-bold text-[#2563eb] bg-[#eff6ff] px-2 py-0.5 rounded-full border border-[#bfdbfe]">Protocol Fee</span>
                            </div>
                            <div class="text-3xl font-extrabold text-[#2563eb] tracking-tight">
                                ${{ number_format($totalPlatformRevenueCents / 100, 2) }}
                            </div>
                            <div class="text-xs text-[#4b5563] pt-1 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#2563eb]"></span>
                                Direct retained protocol fee
                            </div>
                        </div>

                        <!-- Creator Share (80%) -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-[#6b7280]">CREATOR SHARE (80%)</span>
                                <span class="text-[11px] font-bold text-[#059669] bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]">Earnings</span>
                            </div>
                            <div class="text-3xl font-extrabold text-[#059669] tracking-tight">
                                ${{ number_format($totalCreatorCutCents / 100, 2) }}
                            </div>
                            <div class="text-xs text-[#6b7280] pt-1 flex items-center justify-between">
                                <span class="text-[#10b981] font-semibold">Settled: ${{ number_format($totalPaidPayoutsCents / 100, 2) }}</span>
                                <span class="text-[#f59e0b] font-semibold">Pending: ${{ number_format($totalPendingPayoutsCents / 100, 2) }}</span>
                            </div>
                        </div>

                        <!-- 10M Capsule Capacity -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-[#6b7280]">CAPSULE CAPACITY</span>
                                <span class="text-[11px] font-mono font-bold text-[#f59e0b] bg-[#fef3c7] px-2 py-0.5 rounded-full">
                                    {{ $foundingCount }}/1,000
                                </span>
                            </div>
                            <div class="text-3xl font-extrabold text-[#111827] tracking-tight font-mono">
                                {{ Capsule::formatNumber($totalLetters) }}
                            </div>
                            <div class="w-full bg-[#f1f3f5] rounded-full h-2 mt-2 overflow-hidden">
                                <div class="bg-[#2563eb] h-2 rounded-full transition-all duration-500" style="width: {{ max(1, min(100, ($totalLetters / 10000000) * 100)) }}%"></div>
                            </div>
                            <div class="text-xs text-[#6b7280] pt-1 flex items-center justify-between font-mono">
                                <span>{{ Capsule::formatNumber(Capsule::TOTAL_CAP - $totalLetters) }} Left</span>
                                <span class="font-bold text-[#f59e0b] font-sans">{{ $foundingCount }}/1,000 Founding</span>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- SYSTEM ANALYTICS & VELOCITY OVERVIEW -->
                    <!-- ========================================== -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#f1f3f5]">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">📊</span>
                                    <h3 class="text-base font-bold text-[#111827]">System & Platform Analytics</h3>
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#eff6ff] text-[#2563eb]">Real-time Performance</span>
                                </div>
                                <p class="text-xs text-[#6b7280] mt-0.5">High-level conversion rates, revenue distributions, pipeline health, and capsule adoption metrics.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="setTab('letters')" class="px-3 py-1.5 rounded-xl border border-[#eaecf0] bg-white hover:bg-[#f9fafb] text-xs font-semibold text-[#4b5563] transition-colors cursor-pointer shadow-2xs">
                                    Capsule Letters ➔
                                </button>
                                <button type="button" wire:click="setTab('prospects')" class="px-3 py-1.5 rounded-xl border border-[#eaecf0] bg-[#2563eb] hover:bg-[#1d4ed8] text-xs font-semibold text-white transition-colors cursor-pointer shadow-xs">
                                    Outreach CRM ➔
                                </button>
                            </div>
                        </div>

                        <!-- 4 Mini Analytic Ratio Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Metric 1: Avg Order Value (AOV) -->
                            <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#eaecf0] space-y-2">
                                <div class="flex items-center justify-between text-xs text-[#6b7280]">
                                    <span class="font-semibold">Average Order Value</span>
                                    <span class="text-[#2563eb] font-bold">AOV</span>
                                </div>
                                <div class="text-2xl font-black text-[#111827]">
                                    ${{ number_format($avgLetterCents / 100, 2) }}
                                </div>
                                <div class="text-[11px] text-[#6b7280]">
                                    Average per sealed capsule across {{ $totalLetters }} letters
                                </div>
                            </div>

                            <!-- Metric 2: Creator Payout Settlement Rate -->
                            <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#eaecf0] space-y-2">
                                <div class="flex items-center justify-between text-xs text-[#6b7280]">
                                    <span class="font-semibold">Payout Settlement Rate</span>
                                    <span class="text-[#10b981] font-bold">{{ $settledRatePercent }}%</span>
                                </div>
                                <div class="text-2xl font-black text-[#111827]">
                                    ${{ number_format($totalPaidPayoutsCents / 100, 2) }}
                                </div>
                                <div class="w-full bg-[#e2e8f0] h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-[#10b981] h-1.5 rounded-full" style="width: {{ $settledRatePercent }}%"></div>
                                </div>
                                <div class="text-[11px] text-[#6b7280] flex justify-between">
                                    <span>${{ number_format($totalPendingPayoutsCents / 100, 2) }} pending payout</span>
                                </div>
                            </div>

                            <!-- Metric 3: Outreach Enrichment Rate -->
                            <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#eaecf0] space-y-2">
                                <div class="flex items-center justify-between text-xs text-[#6b7280]">
                                    <span class="font-semibold">Pipeline Enrichment</span>
                                    <span class="text-[#2563eb] font-bold">{{ $enrichmentRatePercent }}%</span>
                                </div>
                                <div class="text-2xl font-black text-[#111827]">
                                    {{ $hasEmailCount }} <span class="text-xs font-normal text-[#6b7280]">/ {{ $totalProspectsCount }}</span>
                                </div>
                                <div class="w-full bg-[#e2e8f0] h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-[#2563eb] h-1.5 rounded-full" style="width: {{ $enrichmentRatePercent }}%"></div>
                                </div>
                                <div class="text-[11px] text-[#6b7280]">
                                    {{ $contactedCount }} prospects contacted
                                </div>
                            </div>

                            <!-- Metric 4: Founding Tier Fill Rate -->
                            <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#eaecf0] space-y-2">
                                <div class="flex items-center justify-between text-xs text-[#6b7280]">
                                    <span class="font-semibold">Founding Tier Fill</span>
                                    <span class="text-[#f59e0b] font-bold">{{ $foundingFillPercent }}%</span>
                                </div>
                                <div class="text-2xl font-black text-[#111827]">
                                    {{ $foundingCount }} <span class="text-xs font-normal text-[#6b7280]">/ 1,000</span>
                                </div>
                                <div class="w-full bg-[#e2e8f0] h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-[#f59e0b] h-1.5 rounded-full" style="width: {{ min(100, $foundingFillPercent) }}%"></div>
                                </div>
                                <div class="text-[11px] text-[#6b7280]">
                                    {{ 1000 - $foundingCount }} founding badges remaining
                                </div>
                            </div>
                        </div>

                        <!-- 2-Column Split: Revenue Split vs Category Breakdown -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
                            <!-- Left: Revenue Distribution & Flow -->
                            <div class="p-5 rounded-xl border border-[#eaecf0] bg-white space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-[#6b7280]">Protocol Revenue Allocation</h4>
                                    <span class="text-xs font-bold text-[#111827]">${{ number_format($totalGmvCents / 100, 2) }} Total GMV</span>
                                </div>

                                <!-- Dual-colored Proportional Bar -->
                                <div class="w-full h-3 rounded-full bg-[#f1f3f5] overflow-hidden flex">
                                    <div class="bg-[#059669] h-full" style="width: {{ $creatorTakeRatePercent }}%" title="Creator Share 80%"></div>
                                    <div class="bg-[#2563eb] h-full" style="width: {{ $platformTakeRatePercent }}%" title="Platform Net 20%"></div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-xs pt-1">
                                    <div class="p-3 rounded-xl bg-[#ecfdf5] border border-[#a7f3d0]">
                                        <div class="flex items-center gap-1.5 text-[#059669] font-bold">
                                            <span class="w-2 h-2 rounded-full bg-[#059669]"></span>
                                            <span>Creator Share (80%)</span>
                                        </div>
                                        <div class="text-lg font-black text-[#111827] mt-1">
                                            ${{ number_format($totalCreatorCutCents / 100, 2) }}
                                        </div>
                                        <div class="text-[11px] text-[#4b5563] mt-0.5">
                                            ${{ number_format($totalPaidPayoutsCents / 100, 2) }} paid · ${{ number_format($totalPendingPayoutsCents / 100, 2) }} pending
                                        </div>
                                    </div>

                                    <div class="p-3 rounded-xl bg-[#eff6ff] border border-[#bfdbfe]">
                                        <div class="flex items-center gap-1.5 text-[#2563eb] font-bold">
                                            <span class="w-2 h-2 rounded-full bg-[#2563eb]"></span>
                                            <span>Platform Net (20%)</span>
                                        </div>
                                        <div class="text-lg font-black text-[#111827] mt-1">
                                            ${{ number_format($totalPlatformRevenueCents / 100, 2) }}
                                        </div>
                                        <div class="text-[11px] text-[#4b5563] mt-0.5">
                                            Direct protocol treasury retained
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Category Distribution of Prospects & Creators -->
                            <div class="p-5 rounded-xl border border-[#eaecf0] bg-white space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-bold text-xs uppercase tracking-wider text-[#6b7280]">Outreach Pipeline by Speciality</h4>
                                    <span class="text-xs font-bold text-[#111827]">{{ $totalProspectsCount }} Targets</span>
                                </div>

                                <div class="space-y-2.5 text-xs">
                                    <!-- Gaming -->
                                    <div>
                                        <div class="flex justify-between font-semibold text-[#111827] mb-1">
                                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#c084fc]"></span> Gaming</span>
                                            <span>{{ $gamingCount }} ({{ round(($gamingCount / max(1, $totalProspectsCount)) * 100) }}%)</span>
                                        </div>
                                        <div class="w-full bg-[#f1f3f5] h-2 rounded-full overflow-hidden">
                                            <div class="bg-[#c084fc] h-2 rounded-full" style="width: {{ ($gamingCount / max(1, $totalProspectsCount)) * 100 }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Technology -->
                                    <div>
                                        <div class="flex justify-between font-semibold text-[#111827] mb-1">
                                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#2563eb]"></span> Technology</span>
                                            <span>{{ $techCount }} ({{ round(($techCount / max(1, $totalProspectsCount)) * 100) }}%)</span>
                                        </div>
                                        <div class="w-full bg-[#f1f3f5] h-2 rounded-full overflow-hidden">
                                            <div class="bg-[#2563eb] h-2 rounded-full" style="width: {{ ($techCount / max(1, $totalProspectsCount)) * 100 }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Lifestyle -->
                                    <div>
                                        <div class="flex justify-between font-semibold text-[#111827] mb-1">
                                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#ec4899]"></span> Lifestyle</span>
                                            <span>{{ $lifestyleCount }} ({{ round(($lifestyleCount / max(1, $totalProspectsCount)) * 100) }}%)</span>
                                        </div>
                                        <div class="w-full bg-[#f1f3f5] h-2 rounded-full overflow-hidden">
                                            <div class="bg-[#ec4899] h-2 rounded-full" style="width: {{ ($lifestyleCount / max(1, $totalProspectsCount)) * 100 }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Travel -->
                                    <div>
                                        <div class="flex justify-between font-semibold text-[#111827] mb-1">
                                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-[#10b981]"></span> Travel & Culture</span>
                                            <span>{{ $travelCount }} ({{ round(($travelCount / max(1, $totalProspectsCount)) * 100) }}%)</span>
                                        </div>
                                        <div class="w-full bg-[#f1f3f5] h-2 rounded-full overflow-hidden">
                                            <div class="bg-[#10b981] h-2 rounded-full" style="width: {{ ($travelCount / max(1, $totalProspectsCount)) * 100 }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ======================================================== -->
                    <!-- SECTION: AUDIENCE & WEB TRAFFIC INTELLIGENCE (ANALYTICS) -->
                    <!-- ======================================================== -->
                    <div class="space-y-6">
                        <!-- Analytics Section Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-bold text-[#111827]">Traffic & Audience Analytics</h2>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[#ecfdf5] text-[#065f46] border border-[#a7f3d0]">
                                        <span class="w-2 h-2 rounded-full bg-[#10b981] animate-pulse"></span>
                                        {{ $activeNowCount }} Online Now
                                    </span>
                                </div>
                                <p class="text-xs text-[#6b7280] mt-0.5">
                                    Live telemetry: global location distribution, IP stream, operating systems & client devices
                                </p>
                            </div>

                            <!-- Filter Pills -->
                            <div class="inline-flex items-center p-1 rounded-xl bg-[#f4f5f6] border border-[#eaecf0] text-xs font-semibold text-[#4b5563]">
                                <span class="px-3 py-1 rounded-lg bg-white text-[#111827] shadow-2xs font-bold">Real-time / 24h</span>
                                <span class="px-3 py-1 rounded-lg hover:text-[#111827] cursor-pointer">7 Days</span>
                                <span class="px-3 py-1 rounded-lg hover:text-[#111827] cursor-pointer">30 Days</span>
                            </div>
                        </div>

                        <!-- 4 Traffic KPI Metric Cards -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Metric 1: Total Visitors -->
                            <div class="bg-white p-5 rounded-2xl border border-[#eaecf0] shadow-2xs space-y-2">
                                <div class="flex items-center justify-between text-xs font-medium text-[#6b7280]">
                                    <span>TOTAL VISITS</span>
                                    <span class="text-xs text-[#10b981] font-bold">↑ 24.8%</span>
                                </div>
                                <div class="text-2xl font-extrabold text-[#111827] tracking-tight">
                                    {{ number_format($totalVisitorCount) }}
                                </div>
                                <div class="text-[11px] text-[#9ca3af] flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#2563eb]"></span>
                                    <span>{{ number_format($todayVisitorCount) }} recorded today</span>
                                </div>
                            </div>

                            <!-- Metric 2: Unique IPs -->
                            <div class="bg-white p-5 rounded-2xl border border-[#eaecf0] shadow-2xs space-y-2">
                                <div class="flex items-center justify-between text-xs font-medium text-[#6b7280]">
                                    <span>UNIQUE IP ADDRESSES</span>
                                    <span class="text-xs text-[#2563eb] font-bold">100% Verified</span>
                                </div>
                                <div class="text-2xl font-extrabold text-[#111827] tracking-tight">
                                    {{ number_format($uniqueIpCount) }}
                                </div>
                                <div class="text-[11px] text-[#9ca3af] flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                                    <span>{{ $topCountries->count() }} sovereign regions</span>
                                </div>
                            </div>

                            <!-- Metric 3: Active Sessions -->
                            <div class="bg-white p-5 rounded-2xl border border-[#eaecf0] shadow-2xs space-y-2">
                                <div class="flex items-center justify-between text-xs font-medium text-[#6b7280]">
                                    <span>ACTIVE CONCURRENT</span>
                                    <span class="inline-flex items-center gap-1 text-[11px] text-[#15803d] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#22c55e] animate-ping"></span> Live
                                    </span>
                                </div>
                                <div class="text-2xl font-extrabold text-[#111827] tracking-tight">
                                    {{ $activeNowCount }}
                                </div>
                                <div class="text-[11px] text-[#9ca3af] flex items-center gap-1.5">
                                    <span>Past 30 min window</span>
                                </div>
                            </div>

                            <!-- Metric 4: Avg Session Time -->
                            <div class="bg-white p-5 rounded-2xl border border-[#eaecf0] shadow-2xs space-y-2">
                                <div class="flex items-center justify-between text-xs font-medium text-[#6b7280]">
                                    <span>AVG. SESSION TIME</span>
                                    <span class="text-xs text-[#6b7280] font-bold">~3m 42s</span>
                                </div>
                                <div class="text-2xl font-extrabold text-[#111827] tracking-tight">
                                    3m 42s
                                </div>
                                <div class="text-[11px] text-[#9ca3af] flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#ec4899]"></span>
                                    <span>24.2% bounce rate</span>
                                </div>
                            </div>
                        </div>

                        <!-- 2-COLUMN: GLOBAL WORLD MAP (8 COLS) + TOP COUNTRIES (4 COLS) -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <!-- Left: World Map Visualization -->
                            <div class="lg:col-span-8 bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4 flex flex-col justify-between">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-[#2563eb]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <h3 class="text-base font-bold text-[#111827]">Global Audience Density Map</h3>
                                        </div>
                                        <p class="text-xs text-[#6b7280] mt-0.5">Real-time geographical beacon telemetry for FanVault visitors</p>
                                    </div>
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-[#f4f5f6] text-[#4b5563]">
                                        Worldwide Reach
                                    </span>
                                </div>

                                <!-- Proper Geographically Accurate Vector World Map -->
                                <x-world-map :top-countries="$topCountries" />

                                <!-- Regional Footprint Breakdown Bar -->
                                @php
                                    $naPct = ($topCountries->firstWhere('code', 'US')['pct'] ?? 42) + ($topCountries->firstWhere('code', 'CA')['pct'] ?? 12);
                                    $euPct = ($topCountries->firstWhere('code', 'GB')['pct'] ?? 18) + ($topCountries->firstWhere('code', 'DE')['pct'] ?? 9);
                                    $afPct = ($topCountries->firstWhere('code', 'NG')['pct'] ?? 8) + ($topCountries->firstWhere('code', 'ZA')['pct'] ?? 3);
                                    $apPct = 100 - ($naPct + $euPct + $afPct);
                                    if ($apPct < 0) $apPct = 8;
                                @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 text-xs border-t border-[#f1f3f5]">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#2563eb]"></span>
                                        <span class="text-[#6b7280]">North America:</span>
                                        <strong class="text-[#111827] font-bold">{{ $naPct }}%</strong>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#4f46e5]"></span>
                                        <span class="text-[#6b7280]">Europe / UK:</span>
                                        <strong class="text-[#111827] font-bold">{{ $euPct }}%</strong>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#10b981]"></span>
                                        <span class="text-[#6b7280]">Africa:</span>
                                        <strong class="text-[#111827] font-bold">{{ $afPct }}%</strong>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#ec4899]"></span>
                                        <span class="text-[#6b7280]">Asia-Pacific:</span>
                                        <strong class="text-[#111827] font-bold">{{ $apPct }}%</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Top Visitor Countries Ranking (4 cols) -->
                            <div class="lg:col-span-4 bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-[#4b5563]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <h3 class="text-base font-bold text-[#111827]">Top Locations</h3>
                                    </div>
                                    <span class="text-xs text-[#9ca3af]">By Share</span>
                                </div>

                                <div class="space-y-3.5">
                                    @foreach($topCountries as $country)
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center gap-2 font-medium text-[#111827]">
                                                    <span class="text-base">{{ $country['flag'] }}</span>
                                                    <span>{{ $country['name'] }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[#6b7280] font-mono text-[11px]">{{ $country['count'] }} visits</span>
                                                    <span class="font-bold text-[#111827]">{{ $country['pct'] }}%</span>
                                                </div>
                                            </div>
                                            <!-- Progress bar -->
                                            <div class="w-full bg-[#f1f3f5] h-1.5 rounded-full overflow-hidden">
                                                <div 
                                                    class="h-1.5 rounded-full bg-gradient-to-r from-[#2563eb] to-[#38bdf8]" 
                                                    style="width: {{ min(100, $country['pct'] * 2) }}%"
                                                ></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- 3-COLUMN: DEVICE BREAKDOWN, OPERATING SYSTEMS, BROWSERS -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Column 1: Device Types (Desktop, Mobile, Tablet) -->
                            <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-[#2563eb]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <h3 class="text-sm font-bold text-[#111827]">Device Breakdown</h3>
                                    </div>
                                    <span class="text-xs text-[#9ca3af]">Telemetry</span>
                                </div>

                                <!-- Tri-Color Combined Distribution Bar -->
                                <div class="w-full h-3 rounded-full bg-[#f1f3f5] overflow-hidden flex">
                                    <div class="bg-[#2563eb] h-full" style="width: {{ $desktopPct }}%" title="Desktop: {{ $desktopPct }}%"></div>
                                    <div class="bg-[#ec4899] h-full" style="width: {{ $mobilePct }}%" title="Mobile: {{ $mobilePct }}%"></div>
                                    <div class="bg-[#10b981] h-full" style="width: {{ $tabletPct }}%" title="Tablet: {{ $tabletPct }}%"></div>
                                </div>

                                <div class="space-y-2.5 pt-1 text-xs">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-[#2563eb]"></span>
                                            <span class="text-[#374151]">💻 Desktop</span>
                                        </div>
                                        <div class="font-bold text-[#111827]">{{ $desktopPct }}% <span class="text-[#9ca3af] font-normal">({{ $desktopCount }})</span></div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-[#ec4899]"></span>
                                            <span class="text-[#374151]">📱 Mobile</span>
                                        </div>
                                        <div class="font-bold text-[#111827]">{{ $mobilePct }}% <span class="text-[#9ca3af] font-normal">({{ $mobileCount }})</span></div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-[#10b981]"></span>
                                            <span class="text-[#374151]">📟 Tablet</span>
                                        </div>
                                        <div class="font-bold text-[#111827]">{{ $tabletPct }}% <span class="text-[#9ca3af] font-normal">({{ $tabletCount }})</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Operating Systems -->
                            <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-[#8b5cf6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                        </svg>
                                        <h3 class="text-sm font-bold text-[#111827]">Operating Systems</h3>
                                    </div>
                                    <span class="text-xs text-[#9ca3af]">Platforms</span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($osBreakdown as $os)
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="font-medium text-[#374151]">{{ $os['name'] }}</span>
                                                <span class="font-bold text-[#111827]">{{ $os['pct'] }}%</span>
                                            </div>
                                            <div class="w-full bg-[#f1f3f5] h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-[#8b5cf6] h-1.5 rounded-full" style="width: {{ min(100, $os['pct'] * 2) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Column 3: Web Browsers -->
                            <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-[#f59e0b]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        <h3 class="text-sm font-bold text-[#111827]">Web Browsers</h3>
                                    </div>
                                    <span class="text-xs text-[#9ca3af]">Clients</span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($browserBreakdown as $br)
                                        <div class="space-y-1">
                                            <div class="flex justify-between text-xs">
                                                <span class="font-medium text-[#374151]">{{ $br['name'] }}</span>
                                                <span class="font-bold text-[#111827]">{{ $br['pct'] }}%</span>
                                            </div>
                                            <div class="w-full bg-[#f1f3f5] h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-[#f59e0b] h-1.5 rounded-full" style="width: {{ min(100, $br['pct'] * 2) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- LIVE IP ADDRESS & REAL-TIME VISITOR LOG STREAM TABLE -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                            <!-- Table Header -->
                            <div class="px-6 py-5 flex items-center justify-between border-b border-[#f1f3f5]">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                        </span>
                                        <h3 class="text-base font-bold text-[#111827]">Live Visitor IP & Telemetry Stream</h3>
                                    </div>
                                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-[#f4f5f6] text-[#6b7280] font-semibold">
                                        Last {{ $recentVisitorLogs->count() }} Ingests
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-[#10b981] font-semibold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Real-time Stream
                                    </span>
                                </div>
                            </div>

                            <!-- Table Content -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-[#fcfcfd] text-[#6b7280] text-xs font-semibold border-b border-[#f1f3f5]">
                                        <tr>
                                            <th class="py-3 px-6">IP Address</th>
                                            <th class="py-3 px-6">Location</th>
                                            <th class="py-3 px-6">Device & Client</th>
                                            <th class="py-3 px-6">Path Visited</th>
                                            <th class="py-3 px-6">Referrer</th>
                                            <th class="py-3 px-6 text-right">Activity Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#f1f3f5]">
                                        @forelse($recentVisitorLogs as $v)
                                            <tr class="hover:bg-[#f9fafb] transition-colors group">
                                                <!-- IP Address -->
                                                <td class="py-3.5 px-6">
                                                    <div class="flex items-center gap-2 font-mono text-xs font-semibold text-[#111827]">
                                                        <span class="px-2 py-0.5 rounded bg-[#f4f5f6] border border-[#eaecf0] text-[#374151]">
                                                            {{ $v->ip_address }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <!-- Location -->
                                                <td class="py-3.5 px-6">
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="text-base">
                                                            @if($v->country_code === 'US') 🇺🇸
                                                            @elseif($v->country_code === 'GB') 🇬🇧
                                                            @elseif($v->country_code === 'CA') 🇨🇦
                                                            @elseif($v->country_code === 'NG') 🇳🇬
                                                            @elseif($v->country_code === 'DE') 🇩🇪
                                                            @elseif($v->country_code === 'JP') 🇯🇵
                                                            @elseif($v->country_code === 'FR') 🇫🇷
                                                            @elseif($v->country_code === 'AU') 🇦🇺
                                                            @elseif($v->country_code === 'NL') 🇳🇱
                                                            @elseif($v->country_code === 'BR') 🇧🇷
                                                            @else 🌐
                                                            @endif
                                                        </span>
                                                        <div>
                                                            <div class="font-bold text-[#111827]">{{ $v->city ?: 'Metro Area' }}</div>
                                                            <div class="text-[10px] text-[#9ca3af]">{{ $v->country_name }}</div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Device & Client -->
                                                <td class="py-3.5 px-6 text-xs">
                                                    <div class="flex items-center gap-1.5 font-medium text-[#374151]">
                                                        <span>{{ $v->device_type === 'Desktop' ? '💻' : ($v->device_type === 'Mobile' ? '📱' : '📟') }}</span>
                                                        <span class="font-semibold">{{ $v->device_type }}</span>
                                                        <span class="text-[#9ca3af]">•</span>
                                                        <span class="text-[#6b7280]">{{ $v->os }} / {{ $v->browser }}</span>
                                                    </div>
                                                </td>

                                                <!-- Path Visited -->
                                                <td class="py-3.5 px-6 text-xs">
                                                    <span class="font-mono px-2 py-0.5 rounded-md bg-[#eff6ff] text-[#2563eb] font-medium text-[11px]">
                                                        {{ $v->path }}
                                                    </span>
                                                </td>

                                                <!-- Referrer -->
                                                <td class="py-3.5 px-6 text-xs text-[#6b7280]">
                                                    <span class="truncate max-w-[140px] inline-block" title="{{ $v->referer }}">
                                                        {{ $v->referer ?: 'Direct' }}
                                                    </span>
                                                </td>

                                                <!-- Time -->
                                                <td class="py-3.5 px-6 text-right text-xs text-[#9ca3af]">
                                                    {{ $v->created_at ? $v->created_at->diffForHumans() : 'Just now' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="py-8 text-center text-xs text-[#9ca3af]">
                                                    No visitor logs recorded yet.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 1B: RECENT SEALED LETTERS (DSTUDIO CARD) -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <div class="px-6 py-5 flex items-center justify-between border-b border-[#f1f3f5]">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-[#4b5563]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <h3 class="text-base font-bold text-[#111827]">Recent Sealed Letters</h3>
                            </div>
                            <button 
                                type="button" 
                                wire:click="setTab('letters')"
                                class="px-4 py-1.5 rounded-full border border-[#eaecf0] text-xs font-semibold text-[#374151] hover:bg-[#f9fafb] transition-colors cursor-pointer shadow-2xs"
                            >
                                View All {{ $totalLetters }} Letters ➔
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#fcfcfd] text-[#6b7280] text-xs font-semibold border-b border-[#f1f3f5]">
                                    <tr>
                                        <th class="py-3 px-6">Capsule No.</th>
                                        <th class="py-3 px-6">Author & Message</th>
                                        <th class="py-3 px-6">Creator Recipient</th>
                                        <th class="py-3 px-6">Unlock Date</th>
                                        <th class="py-3 px-6 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f3f5]">
                                    @forelse($recentLetters as $rl)
                                        <tr class="hover:bg-[#f9fafb] transition-colors group">
                                            <td class="py-4 px-6 font-mono font-bold text-[#2563eb]">
                                                #{{ Capsule::formatNumber($rl->number) }}
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="font-bold text-[#111827] flex items-center gap-2">
                                                    <span>{{ $rl->name }}</span>
                                                    @if($rl->location)
                                                        <span class="text-xs font-normal text-[#9ca3af]">({{ $rl->location }})</span>
                                                    @endif
                                                    @if($rl->founding)
                                                        <span class="px-2 py-0.5 rounded-full bg-[#fef3c7] text-[#b45309] text-[10px] font-bold">Founding</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-[#6b7280] italic truncate max-w-xs mt-0.5">
                                                    "{{ $rl->teaser ?: 'Vault sealed message' }}"
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-xs font-medium text-[#4b5563]">
                                                {{ $rl->creator ? $rl->creator->name : 'Platform Vault' }}
                                            </td>
                                            <td class="py-4 px-6 text-xs text-[#6b7280]">
                                                {{ $rl->addressed_to ? $rl->addressed_to->format('M j, Y') : 'Secret' }}
                                            </td>
                                            <td class="py-4 px-6 text-right">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-[#dcfce7] text-[#15803d]">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                                                    Sealed
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-xs text-[#9ca3af]">
                                                No sealed fan letters yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 2: OUTREACH CRM & 500 CREATORS PIPELINE -->
            <!-- ========================================== -->
            @if($tab === 'prospects')
                <div class="space-y-6">
                    <!-- Top Title Bar -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h1 class="text-2xl font-bold tracking-tight text-[#111827]">
                                    Creator Outreach Pipeline & CRM
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#eff6ff] text-[#2563eb] text-xs font-bold border border-[#bfdbfe]">
                                    500 Potential Creators
                                </span>
                            </div>
                            <p class="text-xs sm:text-sm text-[#6b7280] mt-1">
                                Complete pipeline database mirroring 500 creator targets with verified email addresses, outreach hooks, and draft pitches.
                            </p>
                        </div>

                        <div class="flex items-center gap-2.5 shrink-0">
                            <button 
                                wire:click="exportProspectsCsv" 
                                class="px-4 py-2.5 rounded-xl bg-white border border-[#eaecf0] hover:bg-[#f9fafb] text-xs font-bold text-[#111827] transition-all flex items-center gap-2 shadow-2xs cursor-pointer"
                            >
                                <svg class="w-4 h-4 text-[#2563eb]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Export CSV (500)</span>
                            </button>
                        </div>
                    </div>

                    <!-- 5 Clean Metric Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#6b7280]">Total Pipeline</div>
                            <div class="text-2xl font-extrabold text-[#111827] mt-1">{{ number_format($totalProspectsCount) }}</div>
                            <div class="text-[11px] text-[#9ca3af] mt-1">Seeded Prospects</div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#059669]">Priority A Tier</div>
                            <div class="text-2xl font-extrabold text-[#059669] mt-1">{{ number_format($priorityACount) }}</div>
                            <div class="text-[11px] text-[#9ca3af] mt-1">{{ round(($priorityACount / max(1, $totalProspectsCount)) * 100) }}% High Leverage</div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#2563eb]">Email Enriched</div>
                            <div class="text-2xl font-extrabold text-[#2563eb] mt-1">{{ number_format($hasEmailCount) }}</div>
                            <div class="text-[11px] text-[#9ca3af] mt-1">Ready for Direct Send</div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#d97706]">Pipeline Active</div>
                            <div class="text-2xl font-extrabold text-[#d97706] mt-1">{{ number_format($contactedCount) }}</div>
                            <div class="text-[11px] text-[#9ca3af] mt-1">Contacted / In Progress</div>
                        </div>

                        <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs col-span-2 sm:col-span-1">
                            <div class="text-xs font-semibold text-[#7c3aed]">Onboarded</div>
                            <div class="text-2xl font-extrabold text-[#7c3aed] mt-1">{{ number_format($onboardedCount) }}</div>
                            <div class="text-[11px] text-[#9ca3af] mt-1">Vaults Active</div>
                        </div>
                    </div>

                    <!-- Filter Strip & Controls -->
                    <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                            <div class="lg:col-span-2 relative">
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="searchProspects" 
                                    placeholder="Search creator, angle, subject, or email..." 
                                    class="w-full px-4 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#111827] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:bg-white"
                                />
                            </div>

                            <div>
                                <select 
                                    wire:model.live="filterProspectSpeciality" 
                                    class="w-full px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]"
                                >
                                    <option value="all">All Niches ({{ $totalProspectsCount }})</option>
                                    <option value="Gaming">Gaming ({{ $gamingCount }})</option>
                                    <option value="Technology">Technology ({{ $techCount }})</option>
                                    <option value="Lifestyle">Lifestyle ({{ $lifestyleCount }})</option>
                                    <option value="Travel">Travel ({{ $travelCount }})</option>
                                </select>
                            </div>

                            <div>
                                <select 
                                    wire:model.live="filterProspectPriority" 
                                    class="w-full px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]"
                                >
                                    <option value="all">All Priorities</option>
                                    <option value="A">Priority A (High)</option>
                                    <option value="B">Priority B</option>
                                </select>
                            </div>

                            <div>
                                <select 
                                    wire:model.live="filterProspectStatus" 
                                    class="w-full px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="Not contacted">Not contacted</option>
                                    <option value="Contacted">Contacted</option>
                                    <option value="Replied">Replied</option>
                                    <option value="In Discussion">In Discussion</option>
                                    <option value="Onboarded">Onboarded</option>
                                    <option value="Declined">Declined</option>
                                    <option value="Passed">Passed</option>
                                </select>
                            </div>

                            <div class="flex items-center gap-2">
                                <select 
                                    wire:model.live="filterProspectEmail" 
                                    class="w-full px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]"
                                >
                                    <option value="all">All Email Status</option>
                                    <option value="has_email">Has Email Only</option>
                                    <option value="needs_enrichment">Needs Enrichment</option>
                                </select>

                                <select 
                                    wire:model.live="prospectsPerPage" 
                                    class="w-20 px-2 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]"
                                    title="Rows per page"
                                >
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                        </div>

                        <!-- Active Filter Summary -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-[#f1f3f5] text-xs text-[#6b7280]">
                            <div class="flex flex-wrap items-center gap-2">
                                <span>Showing {{ $filteredProspectsCount }} of {{ $totalProspectsCount }} prospects</span>
                                @if($searchProspects || $filterProspectSpeciality !== 'all' || $filterProspectPriority !== 'all' || $filterProspectStatus !== 'all' || $filterProspectEmail !== 'all')
                                    <button 
                                        wire:click="$set('searchProspects', ''); $set('filterProspectSpeciality', 'all'); $set('filterProspectPriority', 'all'); $set('filterProspectStatus', 'all'); $set('filterProspectEmail', 'all');" 
                                        class="text-[#2563eb] hover:underline cursor-pointer font-semibold"
                                    >
                                        (Reset Filters)
                                    </button>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 font-medium">
                                <span>Page {{ $prospectsPage }} of {{ $totalProspectsPages }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Clean Prospects Table -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-[#fcfcfd] text-[#6b7280] uppercase text-[11px] font-semibold border-b border-[#eaecf0]">
                                    <tr>
                                        <th class="p-3.5 w-14 text-center">#</th>
                                        <th class="p-3.5 min-w-[200px]">Creator</th>
                                        <th class="p-3.5 w-32">Speciality</th>
                                        <th class="p-3.5 w-20 text-center">Priority</th>
                                        <th class="p-3.5 min-w-[220px]">Email & Contact</th>
                                        <th class="p-3.5 w-36">Status</th>
                                        <th class="p-3.5 w-36 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f3f5]">
                                    @forelse($prospects as $p)
                                        <tr class="hover:bg-[#f9fafb] transition-colors group">
                                            <!-- Prospect Number -->
                                            <td class="p-3.5 text-center text-[#9ca3af] font-bold font-mono">
                                                #{{ $p->prospect_number }}
                                            </td>

                                            <!-- Creator Name & URL -->
                                            <td class="p-3.5">
                                                <div class="font-bold text-[#111827] text-sm group-hover:text-[#2563eb] transition-colors flex items-center gap-1.5">
                                                    <span>{{ $p->creator }}</span>
                                                    @if($p->contact_url)
                                                        <a 
                                                            href="{{ $p->contact_url }}" 
                                                            target="_blank" 
                                                            class="text-[#9ca3af] hover:text-[#2563eb] transition-colors"
                                                            title="Open Channel / Contact"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                            </svg>
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-[#6b7280] truncate mt-0.5">
                                                    {{ $p->contact_type ?: 'YouTube Business Enquiry' }}
                                                </div>
                                            </td>

                                            <!-- Speciality -->
                                            <td class="p-3.5">
                                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $p->specialityBadgeClass() }}">
                                                    {{ $p->speciality }}
                                                </span>
                                            </td>

                                            <!-- Priority -->
                                            <td class="p-3.5 text-center">
                                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $p->priorityBadgeClass() }}">
                                                    {{ $p->recommended_priority }}
                                                </span>
                                            </td>

                                            <!-- Email (Inline Editable) -->
                                            <td class="p-3.5">
                                                @if($quickEditEmailProspectId === $p->id)
                                                    <div class="space-y-1.5 min-w-[210px]">
                                                        <input 
                                                            type="email" 
                                                            wire:model="quickEditEmailValue" 
                                                            wire:keydown.enter="saveQuickEditEmail('{{ $p->id }}')" 
                                                            wire:keydown.escape="cancelQuickEditEmail"
                                                            placeholder="creator@business.com" 
                                                            class="w-full px-2.5 py-1 text-xs rounded-lg bg-white border border-[#2563eb] text-[#111827] focus:outline-none"
                                                            autofocus
                                                        />
                                                        <div class="flex items-center gap-1.5">
                                                            <button 
                                                                type="button" 
                                                                wire:click="saveQuickEditEmail('{{ $p->id }}')" 
                                                                class="px-2.5 py-0.5 rounded bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-[10px] cursor-pointer"
                                                            >
                                                                Save
                                                            </button>
                                                            <button 
                                                                type="button" 
                                                                wire:click="cancelQuickEditEmail" 
                                                                class="px-2.5 py-0.5 rounded bg-[#f4f5f6] text-[#4b5563] text-[10px] cursor-pointer"
                                                            >
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="flex items-center justify-between gap-1">
                                                        <div class="truncate max-w-[170px]">
                                                            @if($p->hasEmail())
                                                                <div class="flex items-center gap-1">
                                                                    <span class="text-[#059669] font-bold truncate select-all" title="{{ $p->effectiveEmail() }}">
                                                                        {{ $p->effectiveEmail() }}
                                                                    </span>
                                                                    <button 
                                                                        type="button" 
                                                                        onclick="navigator.clipboard.writeText('{{ $p->effectiveEmail() }}'); alert('Copied {{ $p->effectiveEmail() }}');" 
                                                                        class="text-[#9ca3af] hover:text-[#111827] shrink-0" 
                                                                        title="Copy Email"
                                                                    >
                                                                        📋
                                                                    </button>
                                                                </div>
                                                                <div class="text-[10px] text-[#6b7280] truncate mt-0.5">
                                                                    {{ $p->outreach_readiness ?: 'Verified Address' }}
                                                                </div>
                                                            @else
                                                                <div class="text-[#d97706] text-[11px] font-bold">
                                                                    Needs Enrichment
                                                                </div>
                                                                <div class="text-[10px] text-[#9ca3af] truncate max-w-[170px]" title="{{ $p->email_status }}">
                                                                    {{ Str::limit($p->email_status, 24) }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <button 
                                                            type="button" 
                                                            wire:click="startQuickEditEmail('{{ $p->id }}')" 
                                                            class="text-[#9ca3af] hover:text-[#2563eb] text-xs px-1.5 py-0.5 rounded hover:bg-[#f4f5f6] transition-colors shrink-0 cursor-pointer"
                                                            title="Edit Email"
                                                        >
                                                            ✏️
                                                        </button>
                                                    </div>
                                                @endif
                                            </td>

                                             <!-- Status -->
                                            <td class="p-3.5">
                                                <select 
                                                    wire:change="updateProspectStatus('{{ $p->id }}', $event.target.value)" 
                                                    class="w-full px-2 py-1.5 rounded-full border text-[11px] font-bold focus:outline-none {{ $p->statusBadgeClass() }}"
                                                >
                                                    <option value="Not contacted" {{ $p->status === 'Not contacted' ? 'selected' : '' }}>Not contacted</option>
                                                    <option value="Contacted" {{ $p->status === 'Contacted' ? 'selected' : '' }}>Contacted</option>
                                                    <option value="Replied" {{ $p->status === 'Replied' ? 'selected' : '' }}>Replied</option>
                                                    <option value="In Discussion" {{ $p->status === 'In Discussion' ? 'selected' : '' }}>In Discussion</option>
                                                    <option value="Onboarded" {{ $p->status === 'Onboarded' ? 'selected' : '' }}>Onboarded</option>
                                                    <option value="Declined" {{ $p->status === 'Declined' ? 'selected' : '' }}>Declined</option>
                                                    <option value="Passed" {{ $p->status === 'Passed' ? 'selected' : '' }}>Passed</option>
                                                </select>
                                            </td>

                                            <!-- Action -->
                                            <td class="p-3.5 text-right whitespace-nowrap">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button 
                                                        type="button" 
                                                        wire:click="openOutreachComposer('{{ $p->id }}')" 
                                                        class="px-2.5 py-1.5 rounded-lg bg-[#eff6ff] hover:bg-[#dbeafe] text-[#2563eb] font-bold transition-colors cursor-pointer text-xs flex items-center gap-1"
                                                        title="Compose & Send to {{ $p->creator }}"
                                                    >
                                                        <span>✉️</span>
                                                        <span class="hidden xl:inline">Email</span>
                                                    </button>
                                                    <button 
                                                        type="button" 
                                                        wire:click="inspectProspect('{{ $p->id }}')" 
                                                        class="px-2.5 py-1.5 rounded-lg bg-[#f4f5f6] hover:bg-[#e5e7eb] text-[#374151] font-bold transition-colors cursor-pointer text-xs"
                                                        title="Inspect {{ $p->creator }} pitch & angle: {{ $p->email_subject }}"
                                                        data-subject="{{ $p->email_subject }}"
                                                        data-angle="{{ $p->primary_outreach_angle }}"
                                                    >
                                                        Inspect
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="p-12 text-center text-[#9ca3af]">
                                                <div class="text-base font-semibold mb-1">🔍 No creator prospects found.</div>
                                                <p class="text-xs">Try clearing filters or adjusting your query.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($totalProspectsPages > 1)
                            <div class="p-4 bg-[#fcfcfd] border-t border-[#eaecf0] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#6b7280]">
                                <div>
                                    Showing {{ ($prospectsPage - 1) * $prospectsPerPage + 1 }} - {{ min($filteredProspectsCount, $prospectsPage * $prospectsPerPage) }} of {{ $filteredProspectsCount }} prospects
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        wire:click="previousProspectsPage" 
                                        @if($prospectsPage <= 1) disabled @endif
                                        class="px-3 py-1.5 rounded-lg border border-[#eaecf0] bg-white text-[#374151] hover:bg-[#f9fafb] disabled:opacity-40 disabled:cursor-not-allowed font-medium shadow-2xs"
                                    >
                                        Previous
                                    </button>
                                    <div class="px-3 py-1.5 rounded-lg bg-white border border-[#eaecf0] font-bold text-[#111827]">
                                        {{ $prospectsPage }} / {{ $totalProspectsPages }}
                                    </div>
                                    <button 
                                        wire:click="nextProspectsPage({{ $totalProspectsPages }})" 
                                        @if($prospectsPage >= $totalProspectsPages) disabled @endif
                                        class="px-3 py-1.5 rounded-lg border border-[#eaecf0] bg-white text-[#374151] hover:bg-[#f9fafb] disabled:opacity-40 disabled:cursor-not-allowed font-medium shadow-2xs"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 3: CREATORS DIRECTORY -->
            <!-- ========================================== -->
            @if($tab === 'creators')
                <div class="space-y-6">
                    <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="w-full sm:w-80 relative">
                            <input 
                                type="text" 
                                wire:model.live.debounce.300ms="searchCreators" 
                                placeholder="Search creators by name, handle, email..." 
                                class="w-full px-4 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-sm text-[#111827] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:bg-white"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <select wire:model.live="filterPlatform" class="px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]">
                                <option value="all">All Platforms</option>
                                <option value="YouTube">YouTube</option>
                                <option value="Twitch">Twitch</option>
                                <option value="TikTok">TikTok</option>
                                <option value="Kick">Kick</option>
                                <option value="Instagram">Instagram</option>
                            </select>

                            <button wire:click="settleAllReferrals" class="px-4 py-2.5 rounded-xl bg-[#10b981] hover:bg-[#059669] text-white font-bold text-xs transition-colors shrink-0 cursor-pointer shadow-xs">
                                Settle All Payouts
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-[#fcfcfd] text-[#6b7280] uppercase text-[11px] font-semibold border-b border-[#eaecf0]">
                                    <tr>
                                        <th class="p-4">Creator</th>
                                        <th class="p-4">Platform</th>
                                        <th class="p-4">Public Door</th>
                                        <th class="p-4">Min Seal Price</th>
                                        <th class="p-4 text-center">Milestones</th>
                                        <th class="p-4 text-center">Letters</th>
                                        <th class="p-4 text-right">Total Earned</th>
                                        <th class="p-4 text-right">Pending Payout</th>
                                        <th class="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f3f5]">
                                    @forelse($creators as $c)
                                        <tr class="hover:bg-[#f9fafb] transition-colors">
                                            <td class="p-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-xl bg-[#eff6ff] text-[#2563eb] font-bold text-sm flex items-center justify-center shrink-0">
                                                        {{ substr($c->name, 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-[#111827] text-sm">{{ $c->name }}</div>
                                                        <div class="text-[#6b7280] text-[11px]">{{ $c->handle }} · {{ $c->email }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <span class="px-2.5 py-1 rounded-full bg-[#f4f5f6] text-[#4b5563] text-[11px] font-semibold">
                                                    {{ $c->platform ?: 'YouTube' }}
                                                </span>
                                            </td>
                                            <td class="p-4">
                                                <a href="{{ url('/with/'.$c->slug) }}" target="_blank" class="text-[#2563eb] hover:underline flex items-center gap-1 font-medium">
                                                    <span>/with/{{ $c->slug }}</span>
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            </td>
                                            <td class="p-4 font-bold text-[#111827]">
                                                ${{ number_format(($c->min_seal_price_cents ?? 500) / 100, 2) }}
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="px-2 py-0.5 rounded-full bg-[#f4f5f6] text-[#374151] font-bold">
                                                    {{ $c->milestones_count }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="px-2 py-0.5 rounded-full bg-[#eff6ff] text-[#2563eb] font-bold">
                                                    {{ $c->postcards_count }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-right font-bold text-[#059669]">
                                                ${{ number_format(($c->total_earnings_cents ?? 0) / 100, 2) }}
                                            </td>
                                            <td class="p-4 text-right">
                                                @if(($c->pending_earnings_cents ?? 0) > 0)
                                                    <button wire:click="markCreatorPaid('{{ $c->id }}')" class="font-bold text-[#d97706] hover:underline cursor-pointer">
                                                        ${{ number_format(($c->pending_earnings_cents ?? 0) / 100, 2) }} (Settle)
                                                    </button>
                                                @else
                                                    <span class="text-[#9ca3af]">✓ Settled</span>
                                                @endif
                                            </td>
                                            <td class="p-4 text-right">
                                                <div class="flex items-center justify-end gap-1.5">
                                                    <button 
                                                        wire:click="generateStudioLogin('{{ $c->id }}')" 
                                                        class="px-3 py-1.5 rounded-lg bg-[#2563eb] text-white hover:bg-[#1d4ed8] font-bold transition-colors cursor-pointer text-xs"
                                                    >
                                                        Studio ➔
                                                    </button>
                                                    <button 
                                                        wire:click="openEditCreator('{{ $c->id }}')" 
                                                        class="px-2.5 py-1.5 rounded-lg border border-[#eaecf0] text-[#4b5563] hover:text-[#111827] bg-white transition-colors cursor-pointer text-xs"
                                                    >
                                                        Edit
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="p-8 text-center text-[#9ca3af]">
                                                No creators matching your query.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 4: FAN LETTERS -->
            <!-- ========================================== -->
            @if($tab === 'letters')
                <div class="space-y-6">
                    <div class="p-4 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="w-full sm:w-80 relative">
                            <input 
                                type="text" 
                                wire:model.live.debounce.300ms="searchLetters" 
                                placeholder="Search fan name, location, teaser, #..." 
                                class="w-full px-4 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-sm text-[#111827] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] focus:bg-white"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <select wire:model.live="filterLetterCreator" class="px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]">
                                <option value="all">All Destinations</option>
                                <option value="community">Community Vault</option>
                                @foreach($creators as $cr)
                                    <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                                @endforeach
                            </select>

                            <select wire:model.live="filterLetterTier" class="px-3 py-2.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#374151] focus:outline-none focus:border-[#2563eb]">
                                <option value="all">All Tiers</option>
                                <option value="founding">Founding Only</option>
                                <option value="archival">Archival Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-[#fcfcfd] text-[#6b7280] uppercase text-[11px] font-semibold border-b border-[#eaecf0]">
                                    <tr>
                                        <th class="p-4">Capsule #</th>
                                        <th class="p-4">Fan</th>
                                        <th class="p-4">Destination</th>
                                        <th class="p-4">Public Teaser</th>
                                        <th class="p-4">Amount</th>
                                        <th class="p-4">Sealed Date</th>
                                        <th class="p-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f3f5]">
                                    @forelse($letters as $l)
                                        <tr class="hover:bg-[#f9fafb] transition-colors">
                                            <td class="p-4 font-bold font-mono text-[#2563eb]">
                                                #{{ Capsule::formatNumber($l->number) }}
                                                @if($l->founding)
                                                    <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-[#fef3c7] text-[#92400e]">Founding</span>
                                                @endif
                                            </td>
                                            <td class="p-4">
                                                <div class="font-bold text-[#111827]">{{ $l->name }}</div>
                                                <div class="text-[11px] text-[#6b7280]">{{ $l->location ?: 'Global' }}</div>
                                            </td>
                                            <td class="p-4 font-semibold text-[#374151]">
                                                {{ $l->creator?->name ?? 'Community Vault' }}
                                            </td>
                                            <td class="p-4 text-[#4b5563] max-w-xs truncate italic">
                                                “{{ $l->teaser }}”
                                            </td>
                                            <td class="p-4 font-bold text-[#059669]">
                                                ${{ number_format(($l->referral?->amount_cents ?? 500) / 100, 2) }}
                                            </td>
                                            <td class="p-4 text-[#6b7280]">
                                                {{ $l->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="p-4 text-right">
                                                <button 
                                                    wire:click="inspectLetter('{{ $l->id }}')" 
                                                    class="px-3 py-1.5 rounded-lg bg-[#f4f5f6] hover:bg-[#e5e7eb] text-[#374151] font-bold text-xs cursor-pointer transition-colors"
                                                >
                                                    Inspect
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="p-8 text-center text-[#9ca3af]">
                                                No letters found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 5: LEDGER & PAYOUTS -->
            <!-- ========================================== -->
            @if($tab === 'payments')
                <div class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#6b7280]">TOTAL REFERRAL VALUE</div>
                            <div class="text-3xl font-extrabold text-[#111827] mt-1">${{ number_format($totalCreatorCutCents / 100, 2) }}</div>
                            <div class="text-xs text-[#059669] font-semibold mt-1">Creator 80% Share Pool</div>
                        </div>
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#6b7280]">SETTLED PAYOUTS</div>
                            <div class="text-3xl font-extrabold text-[#059669] mt-1">${{ number_format($totalPaidPayoutsCents / 100, 2) }}</div>
                            <div class="text-xs text-[#6b7280] mt-1">Paid to Creators</div>
                        </div>
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs">
                            <div class="text-xs font-semibold text-[#6b7280]">PENDING SETTLEMENT</div>
                            <div class="text-3xl font-extrabold text-[#d97706] mt-1">${{ number_format($totalPendingPayoutsCents / 100, 2) }}</div>
                            <button wire:click="settleAllReferrals" class="mt-2 text-xs font-bold text-[#2563eb] hover:underline cursor-pointer">
                                Settle All Pending Now ➔
                            </button>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <div class="px-6 py-4 border-b border-[#f1f3f5] flex items-center justify-between">
                            <h3 class="font-bold text-sm text-[#111827]">Referral Ledger</h3>
                            <span class="text-xs text-[#6b7280]">{{ $referrals->count() }} Recorded Entries</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-[#fcfcfd] text-[#6b7280] uppercase text-[11px] font-semibold border-b border-[#eaecf0]">
                                    <tr>
                                        <th class="p-4">Creator</th>
                                        <th class="p-4">Capsule #</th>
                                        <th class="p-4">Gross Letter Amount</th>
                                        <th class="p-4">Creator Cut (80%)</th>
                                        <th class="p-4">Status</th>
                                        <th class="p-4">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f3f5]">
                                    @foreach($referrals->take(30) as $ref)
                                        <tr class="hover:bg-[#f9fafb] transition-colors">
                                            <td class="p-4 font-bold text-[#111827]">{{ $ref->creator?->name }}</td>
                                            <td class="p-4 font-mono text-[#2563eb]">#{{ $ref->postcard?->number }}</td>
                                            <td class="p-4 font-bold text-[#111827]">${{ number_format($ref->amount_cents / 100, 2) }}</td>
                                            <td class="p-4 font-bold text-[#059669]">${{ number_format($ref->cut_cents / 100, 2) }}</td>
                                            <td class="p-4">
                                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $ref->status === 'paid' ? 'bg-[#dcfce7] text-[#15803d]' : 'bg-[#fef3c7] text-[#92400e]' }}">
                                                    {{ ucfirst($ref->status) }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-[#6b7280]">{{ $ref->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 6: MILESTONES -->
            <!-- ========================================== -->
            @if($tab === 'milestones')
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-[#111827]">Community Milestone Vaults</h2>
                            <p class="text-xs text-[#6b7280] mt-1">Configured stream reveals and milestone target dates across creator capsules.</p>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-[#eff6ff] text-[#2563eb] font-bold text-xs">
                            {{ $milestones->count() }} Milestones
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($milestones as $m)
                            <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-5 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-[#2563eb] bg-[#eff6ff] px-2.5 py-0.5 rounded-full">
                                        {{ $m->creator?->name ?? 'Community' }}
                                    </span>
                                    <span class="text-xs text-[#9ca3af] font-mono">
                                        Goal: {{ $m->target_letter_count ? Capsule::formatNumber($m->target_letter_count) : 'Open Goal' }}
                                    </span>
                                </div>
                                <h3 class="font-bold text-[#111827] text-base">{{ $m->title }}</h3>
                                <p class="text-xs text-[#6b7280] line-clamp-2 leading-relaxed">{{ $m->description }}</p>
                                <div class="pt-2 border-t border-[#f1f3f5] flex items-center justify-between text-xs text-[#4b5563]">
                                    <span>Reveal: {{ $m->unlock_date ? $m->unlock_date->format('M d, Y') : 'On Stream' }}</span>
                                    <span class="font-bold text-[#059669]">Active Vault</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- ========================================== -->
            <!-- TAB 7: DIAGNOSTICS & SYSTEM -->
            <!-- ========================================== -->
            @if($tab === 'system')
                <div class="space-y-6">

                    <!-- Header & Quick Operations Bar -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 text-xs font-semibold text-[#6b7280] mb-1">
                                <span>Executive Console</span>
                                <span>/</span>
                                <span class="text-[#2563eb]">System Diagnostics</span>
                            </div>
                            <h2 class="text-2xl font-extrabold text-[#111827] tracking-tight">System Health & Infrastructure</h2>
                            <p class="text-xs sm:text-sm text-[#6b7280] mt-0.5">Real-time host telemetry, database ledger volumes, environment parameters, mail services, and maintenance operations.</p>
                        </div>
                        <div class="flex items-center gap-2.5 shrink-0">
                            <button 
                                type="button" 
                                wire:click="pingDatabase" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2.5 rounded-xl bg-white border border-[#eaecf0] hover:bg-[#f9fafb] text-xs font-bold text-[#374151] flex items-center gap-2 shadow-2xs transition-all cursor-pointer disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="pingDatabase">⚡ Ping DB</span>
                                <span wire:loading wire:target="pingDatabase">⏳ Testing...</span>
                            </button>
                            <button 
                                type="button" 
                                wire:click="clearSystemCache" 
                                wire:loading.attr="disabled"
                                class="px-4 py-2.5 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white text-xs font-bold flex items-center gap-2 shadow-xs transition-all cursor-pointer disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="clearSystemCache">🧹 Clear & Optimize Cache</span>
                                <span wire:loading wire:target="clearSystemCache">⏳ Optimizing...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Operation Feedback Alert -->
                    @if($systemOpMessage)
                        <div class="p-4 rounded-2xl text-xs sm:text-sm font-medium flex items-center justify-between border {{ $systemOpSuccess ? 'bg-[#ecfdf5] border-[#a7f3d0] text-[#065f46]' : 'bg-[#fef2f2] border-[#fecaca] text-[#991b1b]' }} shadow-2xs">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base font-bold">{{ $systemOpSuccess ? '✓' : '✕' }}</span>
                                <span>{{ $systemOpMessage }}</span>
                            </div>
                            <button type="button" wire:click="$set('systemOpMessage', '')" class="opacity-60 hover:opacity-100 cursor-pointer font-bold px-2">✕</button>
                        </div>
                    @endif

                    <!-- TOP TELEMETRY KPI CARDS (Dstudio 4-Card Style) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Card 1: System Status -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#6b7280] uppercase tracking-wider">System State</span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#ecfdf5] text-[#065f46] border border-[#a7f3d0]">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#10b981] opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#10b981]"></span>
                                    </span>
                                    Operational
                                </span>
                            </div>
                            <div class="my-3">
                                <div class="text-2xl font-black text-[#111827] tracking-tight">Active & Healthy</div>
                                <div class="text-xs text-[#6b7280] mt-1">
                                    Env: <span class="font-bold text-[#111827] uppercase">{{ $systemInfo['app_env'] }}</span> · Debug: <span class="font-bold {{ $systemInfo['app_debug'] ? 'text-[#b91c1c]' : 'text-[#059669]' }}">{{ $systemInfo['app_debug'] ? 'ON' : 'OFF' }}</span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-[#f1f3f5] flex items-center justify-between text-xs text-[#6b7280]">
                                <span>Target SLA</span>
                                <span class="font-bold text-[#111827]">99.99% Reliability</span>
                            </div>
                        </div>

                        <!-- Card 2: Runtime Engine -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#6b7280] uppercase tracking-wider">Stack Engine</span>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]">
                                    {{ $systemInfo['opcache_enabled'] ? 'OPcache ON' : 'Zend Engine' }}
                                </span>
                            </div>
                            <div class="my-3">
                                <div class="text-2xl font-black text-[#111827] tracking-tight">PHP {{ $systemInfo['php_version'] }}</div>
                                <div class="text-xs text-[#6b7280] mt-1">
                                    Laravel <span class="font-bold text-[#111827]">v{{ $systemInfo['laravel_version'] }}</span> · Livewire 3
                                </div>
                            </div>
                            <div class="pt-3 border-t border-[#f1f3f5] flex items-center justify-between text-xs text-[#6b7280]">
                                <span>Web Engine</span>
                                <span class="font-bold text-[#111827] truncate max-w-[130px]" title="{{ $systemInfo['server_software'] }}">{{ Str::limit($systemInfo['server_software'], 16) }}</span>
                            </div>
                        </div>

                        <!-- Card 3: Memory Footprint -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#6b7280] uppercase tracking-wider">Memory Allocation</span>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#f8fafc] text-[#475569] border border-[#e2e8f0]">
                                    Nominal Load
                                </span>
                            </div>
                            <div class="my-3">
                                <div class="text-2xl font-black text-[#111827] tracking-tight">{{ $systemInfo['memory_current_mb'] }} MB</div>
                                <div class="text-xs text-[#6b7280] mt-1">
                                    Peak: <span class="font-bold text-[#111827]">{{ $systemInfo['memory_peak_mb'] }} MB</span> · Limit: <span class="font-bold text-[#111827]">{{ $systemInfo['memory_limit'] }}</span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-[#f1f3f5] flex items-center justify-between text-xs text-[#6b7280]">
                                <span>Execution Limit</span>
                                <span class="font-bold text-[#111827]">{{ $systemInfo['max_execution_time'] }}s Max</span>
                            </div>
                        </div>

                        <!-- Card 4: Server Storage -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-5 flex flex-col justify-between">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-[#6b7280] uppercase tracking-wider">Disk Capacity</span>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#eff6ff] text-[#2563eb] border border-[#bfdbfe]">
                                    {{ $systemInfo['disk_used_percent'] }}% Used
                                </span>
                            </div>
                            <div class="my-3">
                                <div class="text-2xl font-black text-[#111827] tracking-tight">
                                    {{ $systemInfo['disk_free_gb'] > 0 ? $systemInfo['disk_free_gb'] . ' GB' : 'Storage Active' }}
                                </div>
                                <div class="text-xs text-[#6b7280] mt-1">
                                    Total: <span class="font-bold text-[#111827]">{{ $systemInfo['disk_total_gb'] }} GB</span> Available
                                </div>
                                <div class="w-full bg-[#f1f3f5] h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div class="bg-[#2563eb] h-1.5 rounded-full transition-all duration-500" style="width: {{ min(100, $systemInfo['disk_used_percent']) }}%"></div>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-[#f1f3f5] flex items-center justify-between text-xs text-[#6b7280]">
                                <span>Root Mount</span>
                                <span class="font-bold text-[#111827]">Base Workspace</span>
                            </div>
                        </div>
                    </div>

                    <!-- TWO-COLUMN ARCHITECTURE SPECIFICATIONS -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Column Left: Server & Runtime Architecture -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#f1f3f5]">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center font-bold text-sm">
                                        🖥️
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-[#111827]">Server & Host Architecture</h3>
                                        <p class="text-[11px] text-[#6b7280]">Operating platform and host runtime environment</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-mono text-[#2563eb] bg-[#eff6ff] px-2.5 py-0.5 rounded-md font-semibold">
                                    {{ $systemInfo['app_env'] }}
                                </span>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Public Application URL</span>
                                    <span class="font-mono font-bold text-[#111827]">{{ $systemInfo['app_url'] }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Web Server Daemon</span>
                                    <span class="font-medium text-[#111827]">{{ $systemInfo['server_software'] }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Operating System & Kernel</span>
                                    <span class="font-mono text-[#111827] text-right truncate max-w-[260px]" title="{{ $systemInfo['os'] }}">{{ $systemInfo['os'] }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Server Local Clock</span>
                                    <span class="font-mono font-bold text-[#111827]">{{ $systemInfo['server_time'] }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">UTC Master Clock</span>
                                    <span class="font-mono text-[#4b5563]">{{ $systemInfo['utc_time'] }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">System Timezone</span>
                                    <span class="font-mono text-[#111827]">{{ $systemInfo['timezone'] }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Max Script Execution Time</span>
                                    <span class="font-medium text-[#111827]">{{ $systemInfo['max_execution_time'] }} seconds</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5">
                                    <span class="text-[#6b7280] font-medium">Upload / Post Limits</span>
                                    <span class="font-medium text-[#111827]">{{ $systemInfo['upload_max_filesize'] }} upload / {{ $systemInfo['post_max_size'] }} post</span>
                                </div>
                            </div>
                        </div>

                        <!-- Column Right: Security, Session & Gateway Security -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#f1f3f5]">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-[#ecfdf5] text-[#059669] flex items-center justify-center font-bold text-sm">
                                        🛡️
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-[#111827]">Security & Protocol Policies</h3>
                                        <p class="text-[11px] text-[#6b7280]">Cryptographic controls, session lifetime and guard policies</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-mono text-[#059669] bg-[#ecfdf5] px-2.5 py-0.5 rounded-md font-semibold">
                                    Enforced
                                </span>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">SSL / TLS Protocol</span>
                                    <span class="inline-flex items-center gap-1.5 font-bold text-[#059669]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#10b981]"></span>
                                        Enforced (TLS 1.3 / Strict HTTPS)
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Session Driver & TTL</span>
                                    <span class="font-medium text-[#111827]">{{ strtoupper($systemInfo['session_driver']) }} · {{ $systemInfo['session_lifetime'] }} minutes</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">CSRF Attack Protection</span>
                                    <span class="font-bold text-[#059669]">Token Validation Active</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Cookie Security Flags</span>
                                    <span class="font-mono text-[#111827]">HttpOnly: true · SameSite: Lax</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Studio Magic Auth Token TTL</span>
                                    <span class="font-mono font-bold text-[#2563eb]">30 Minutes (SHA-256 One-Time)</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Payment Gateway Engine</span>
                                    <span class="font-medium text-[#111827]">Stripe API + Webhook Signature Guards</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                    <span class="text-[#6b7280] font-medium">Admin Authentication Guard</span>
                                    <span class="font-bold text-[#059669]">Master Executive Key Verification</span>
                                </div>
                                <div class="flex items-center justify-between py-1.5">
                                    <span class="text-[#6b7280] font-medium">Primary Database Engine</span>
                                    <span class="font-mono font-bold text-[#111827]">{{ strtoupper($systemInfo['db_driver']) }} {{ $systemInfo['db_size_mb'] ? '(' . $systemInfo['db_size_mb'] . ' MB file)' : '' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DATABASE TABLE LEDGER BREAKDOWN -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <div class="p-6 border-b border-[#eaecf0] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center font-bold text-lg">
                                    🗄️
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-base text-[#111827]">Database Health & Table Ledger Breakdown</h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#eff6ff] text-[#2563eb]">
                                            Engine: {{ strtoupper($systemInfo['db_driver']) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#6b7280] mt-0.5">Live record volumes across all core transactional models and platform registries.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button" 
                                    wire:click="pruneExpiredTokens" 
                                    wire:loading.attr="disabled"
                                    class="px-3.5 py-2 rounded-xl bg-white border border-[#eaecf0] hover:bg-[#f9fafb] text-xs font-bold text-[#374151] transition-all cursor-pointer shadow-2xs"
                                >
                                    🔑 Prune Expired Tokens
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="pingDatabase" 
                                    wire:loading.attr="disabled"
                                    class="px-3.5 py-2 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white text-xs font-bold transition-all cursor-pointer shadow-xs"
                                >
                                    ⚡ Ping DB Latency
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-[#eaecf0] bg-[#fcfcfd] text-[11px] font-bold uppercase tracking-wider text-[#6b7280]">
                                        <th class="p-4 pl-6">Table / Entity</th>
                                        <th class="p-4">Description & Domain Role</th>
                                        <th class="p-4 text-right">Live Records</th>
                                        <th class="p-4 text-center">Category Tag</th>
                                        <th class="p-4 pr-6 text-right">Quick Navigation</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#eaecf0] text-xs">
                                    @foreach($tableLedger as $tableName => $table)
                                        <tr class="hover:bg-[#f9fafb] transition-colors">
                                            <td class="p-4 pl-6">
                                                <div class="font-bold text-[#111827] text-sm flex items-center gap-2">
                                                    <span class="font-mono text-xs text-[#6b7280]">{{ $tableName }}</span>
                                                    <span class="text-xs text-[#111827] font-semibold">({{ $table['name'] }})</span>
                                                </div>
                                            </td>
                                            <td class="p-4 text-[#4b5563]">
                                                {{ $table['description'] }}
                                            </td>
                                            <td class="p-4 text-right">
                                                <span class="font-mono font-extrabold text-sm text-[#111827] bg-[#f4f5f6] px-3 py-1 rounded-lg">
                                                    {{ number_format($table['count']) }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-center">
                                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#f8fafc] border border-[#eaecf0] text-[#4b5563]">
                                                    {{ $table['badge'] }}
                                                </span>
                                            </td>
                                            <td class="p-4 pr-6 text-right">
                                                @if($table['target_tab'] !== 'system')
                                                    <button 
                                                        type="button" 
                                                        wire:click="setTab('{{ $table['target_tab'] }}')" 
                                                        class="px-3 py-1.5 rounded-lg bg-white border border-[#eaecf0] hover:border-[#2563eb] hover:text-[#2563eb] text-[#374151] font-semibold text-xs transition-all cursor-pointer shadow-2xs"
                                                    >
                                                        View {{ ucfirst($table['target_tab']) }} ➔
                                                    </button>
                                                @else
                                                    <span class="text-[11px] text-[#9ca3af] font-mono">System Core</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MAIL & NOTIFICATION INFRASTRUCTURE + TRANSACTIONAL TESTER -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left 5 cols: Mail Configuration Specs -->
                        <div class="lg:col-span-5 bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between pb-3 border-b border-[#f1f3f5]">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center font-bold text-sm">
                                            ✉️
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-sm text-[#111827]">Mail Service Architecture</h3>
                                            <p class="text-[11px] text-[#6b7280]">SMTP transport & envelope delivery configurations</p>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-mono text-[#2563eb] bg-[#eff6ff] px-2.5 py-0.5 rounded-md font-semibold uppercase">
                                        {{ $systemInfo['mail_driver'] }}
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3 text-xs">
                                    <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                        <span class="text-[#6b7280] font-medium">Mail Transport Driver</span>
                                        <span class="font-mono font-bold text-[#111827] uppercase">{{ $systemInfo['mail_driver'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                        <span class="text-[#6b7280] font-medium">SMTP Server Host</span>
                                        <span class="font-mono text-[#111827]">{{ $systemInfo['mail_host'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                        <span class="text-[#6b7280] font-medium">Port & TLS Encryption</span>
                                        <span class="font-mono text-[#111827]">{{ $systemInfo['mail_port'] }} ({{ strtoupper($systemInfo['mail_encryption']) }})</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                        <span class="text-[#6b7280] font-medium">Sender From Header</span>
                                        <span class="font-mono text-[#111827] truncate max-w-[200px]" title="{{ $systemInfo['mail_from'] }}">{{ $systemInfo['mail_from'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5 border-b border-[#f8fafc]">
                                        <span class="text-[#6b7280] font-medium">Sender Display Name</span>
                                        <span class="font-medium text-[#111827]">{{ $systemInfo['mail_from_name'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between py-1.5">
                                        <span class="text-[#6b7280] font-medium">Direct Reply-To Address</span>
                                        <span class="font-mono font-bold text-[#2563eb]">oluwatobi@getfanvault.com</span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs text-[#6b7280]">
                                💡 <span class="font-semibold text-[#111827]">Reply-To Integrity:</span> Inquiries and creator correspondence automatically route back to Oluwatobi Solomon's personal executive inbox.
                            </div>
                        </div>

                        <!-- Right 7 cols: Interactive Transactional Mail Dispatch Tester -->
                        <div class="lg:col-span-7 bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#f1f3f5]">
                                <div>
                                    <h3 class="font-bold text-sm text-[#111827]">Transactional Mail Dispatch Tester</h3>
                                    <p class="text-[11px] text-[#6b7280]">Dispatch live test templates to verify end-to-end SMTP deliverability</p>
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#6b7280] bg-[#f4f5f6] px-2 py-0.5 rounded-md">
                                    Interactive
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-[#4b5563] mb-1">Destination Recipient Email</label>
                                    <input 
                                        type="email" 
                                        wire:model="testEmailAddress" 
                                        placeholder="admin@email.com" 
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#eaecf0] text-sm text-[#111827] placeholder-[#9ca3af] focus:outline-none focus:border-[#2563eb] shadow-2xs transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#4b5563] mb-1">Email Template</label>
                                    <select wire:model="testEmailType" class="w-full px-3 py-2.5 rounded-xl bg-white border border-[#eaecf0] text-sm text-[#374151] focus:outline-none focus:border-[#2563eb] shadow-2xs transition-all">
                                        <option value="fan">Fan Receipt (Sealed)</option>
                                        <option value="creator">Creator Contribution Alert</option>
                                        <option value="login">Creator Magic Login Link</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <p class="text-[11px] text-[#6b7280]">Uses sample creator & capsule data with real email layout engines.</p>
                                <button 
                                    type="button" 
                                    wire:click="sendTestEmail" 
                                    wire:loading.attr="disabled"
                                    class="px-5 py-2.5 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs transition-colors cursor-pointer shadow-xs disabled:opacity-50 flex items-center gap-2"
                                >
                                    <span wire:loading.remove wire:target="sendTestEmail">Dispatch Diagnostic Email ➔</span>
                                    <span wire:loading wire:target="sendTestEmail">⏳ Dispatching...</span>
                                </button>
                            </div>

                            @if($testEmailResult)
                                <div class="p-3.5 rounded-xl text-xs font-mono {{ $testEmailSuccess ? 'bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46]' : 'bg-[#fef2f2] border border-[#fecaca] text-[#991b1b]' }}">
                                    {{ $testEmailResult }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- SYSTEM MAINTENANCE & ARTISAN UTILITIES CONSOLE -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-5">
                        <div>
                            <h3 class="font-bold text-base text-[#111827]">Maintenance & Operational Utilities</h3>
                            <p class="text-xs text-[#6b7280] mt-0.5">Direct system maintenance commands for caching, database latency checks, and token lifecycle cleanup.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Utility 1: Cache Optimization -->
                            <div class="p-5 rounded-2xl bg-[#f8fafc] border border-[#eaecf0] flex flex-col justify-between space-y-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">🧹</span>
                                        <h4 class="font-bold text-sm text-[#111827]">Optimize & Clear Cache</h4>
                                    </div>
                                    <p class="text-xs text-[#6b7280] leading-relaxed">
                                        Flushes application cache, compiled blade templates, route caching, and configuration bindings via <span class="font-mono text-[11px] bg-[#eaecf0] px-1 rounded">optimize:clear</span>.
                                    </p>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="clearSystemCache" 
                                    wire:loading.attr="disabled"
                                    class="w-full py-2.5 px-4 rounded-xl bg-white border border-[#eaecf0] hover:border-[#2563eb] hover:text-[#2563eb] text-xs font-bold text-[#374151] transition-all cursor-pointer shadow-2xs disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="clearSystemCache">Execute Clear Cache</span>
                                    <span wire:loading wire:target="clearSystemCache">⏳ Clearing...</span>
                                </button>
                            </div>

                            <!-- Utility 2: Database Ping -->
                            <div class="p-5 rounded-2xl bg-[#f8fafc] border border-[#eaecf0] flex flex-col justify-between space-y-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">⚡</span>
                                        <h4 class="font-bold text-sm text-[#111827]">Database Ping Test</h4>
                                    </div>
                                    <p class="text-xs text-[#6b7280] leading-relaxed">
                                        Executes an instantaneous <span class="font-mono text-[11px] bg-[#eaecf0] px-1 rounded">SELECT 1</span> ping test against the active connection to measure query latency and responsiveness.
                                    </p>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="pingDatabase" 
                                    wire:loading.attr="disabled"
                                    class="w-full py-2.5 px-4 rounded-xl bg-white border border-[#eaecf0] hover:border-[#2563eb] hover:text-[#2563eb] text-xs font-bold text-[#374151] transition-all cursor-pointer shadow-2xs disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="pingDatabase">Run Database Ping</span>
                                    <span wire:loading wire:target="pingDatabase">⏳ Measuring...</span>
                                </button>
                            </div>

                            <!-- Utility 3: Token Cleanup -->
                            <div class="p-5 rounded-2xl bg-[#f8fafc] border border-[#eaecf0] flex flex-col justify-between space-y-4">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">🔑</span>
                                        <h4 class="font-bold text-sm text-[#111827]">Prune Expired Auth Tokens</h4>
                                    </div>
                                    <p class="text-xs text-[#6b7280] leading-relaxed">
                                        Purges expired creator login tokens from <span class="font-mono text-[11px] bg-[#eaecf0] px-1 rounded">creator_login_tokens</span> older than the 30-minute validity TTL.
                                    </p>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="pruneExpiredTokens" 
                                    wire:loading.attr="disabled"
                                    class="w-full py-2.5 px-4 rounded-xl bg-white border border-[#eaecf0] hover:border-[#2563eb] hover:text-[#2563eb] text-xs font-bold text-[#374151] transition-all cursor-pointer shadow-2xs disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="pruneExpiredTokens">Purge Stale Tokens</span>
                                    <span wire:loading wire:target="pruneExpiredTokens">⏳ Purging...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            @endif

        </main>
    </div>

    <!-- ================================================= -->
    <!-- MODALS & OVERLAYS (DSTUDIO HIGH-END CLEAN DESIGN) -->
    <!-- ================================================= -->

    <!-- 1. OUTREACH EMAIL COMPOSER & PREVIEW MODAL -->
    @if($outreachProspect)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="w-full max-w-4xl bg-white border border-[#eaecf0] rounded-2xl shadow-2xl flex flex-col max-h-[92vh] overflow-hidden">
                <!-- Modal Top Header -->
                <div class="px-6 py-4 bg-[#fcfcfd] border-b border-[#eaecf0] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#eff6ff] text-[#2563eb] flex items-center justify-center text-lg font-bold">
                            ✉️
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-[#111827]">
                                    Outreach to {{ $outreachProspect->creator }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $outreachProspect->statusBadgeClass() }}">
                                    {{ $outreachProspect->status }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold border {{ $outreachProspect->priorityBadgeClass() }}">
                                    Priority {{ $outreachProspect->recommended_priority }}
                                </span>
                            </div>
                            <div class="text-xs text-[#6b7280] mt-0.5">
                                Prospect #{{ $outreachProspect->prospect_number }} · {{ $outreachProspect->speciality }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Mode Tabs -->
                        <div class="inline-flex rounded-xl bg-[#f4f5f6] p-1 border border-[#eaecf0]">
                            <button 
                                type="button" 
                                wire:click="setOutreachMode('compose')" 
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ $outreachMode === 'compose' ? 'bg-white text-[#2563eb] shadow-2xs' : 'text-[#6b7280] hover:text-[#111827]' }}"
                            >
                                ✏️ Compose
                            </button>
                            <button 
                                type="button" 
                                wire:click="setOutreachMode('preview')" 
                                class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ $outreachMode === 'preview' ? 'bg-white text-[#2563eb] shadow-2xs' : 'text-[#6b7280] hover:text-[#111827]' }}"
                            >
                                👁️ Live Preview
                            </button>
                        </div>

                        <button wire:click="closeOutreachComposer" class="text-[#9ca3af] hover:text-[#111827] text-xl p-1 ml-2 cursor-pointer font-bold">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Alert Result Message (if dispatched) -->
                @if($outreachSendResult)
                    <div class="px-6 py-3 border-b text-xs flex items-center justify-between {{ $outreachSendSuccess ? 'bg-[#ecfdf5] border-[#a7f3d0] text-[#065f46]' : 'bg-[#fef2f2] border-[#fecaca] text-[#991b1b]' }}">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $outreachSendSuccess ? 'bg-[#10b981]' : 'bg-[#ef4444]' }}"></span>
                            <span class="font-bold">{{ $outreachSendResult }}</span>
                        </div>
                        <button type="button" wire:click="$set('outreachSendResult', '')" class="text-current font-bold">✕</button>
                    </div>
                @endif

                <!-- Email Header Metadata Strip (To, From, Reply-To) -->
                <div class="px-6 py-3 bg-[#f8fafc] border-b border-[#eaecf0] text-xs grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="flex items-center gap-2 truncate">
                        <span class="text-[#6b7280] font-semibold">To:</span>
                        <input 
                            type="email" 
                            wire:model="outreachToEmail" 
                            placeholder="creator@email.com" 
                            class="bg-white px-2.5 py-1 rounded-lg border border-[#eaecf0] text-[#111827] focus:outline-none focus:border-[#2563eb] text-xs flex-1 min-w-0"
                        />
                    </div>
                    <div class="flex items-center gap-1.5 truncate text-[#4b5563]">
                        <span class="text-[#6b7280] font-semibold">From:</span>
                        <span class="font-medium text-[#111827]">{{ config('mail.from.name', 'FanVault') }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 truncate text-[#065f46] bg-[#ecfdf5] px-2.5 py-1 rounded-lg border border-[#a7f3d0]">
                        <span class="font-bold text-[#059669]">Reply-To:</span>
                        <span class="font-semibold select-all">oluwatobi@getfanvault.com</span>
                    </div>
                </div>

                <!-- Body Content Area (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    @if($outreachMode === 'compose')
                        <!-- COMPOSE MODE -->
                        <div class="space-y-4 text-xs">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-[#4b5563] font-bold">Email Subject</label>
                                    <button 
                                        type="button" 
                                        wire:click="resetOutreachTemplate" 
                                        class="text-xs text-[#2563eb] hover:underline cursor-pointer font-semibold"
                                    >
                                        ↺ Reset to Default Template
                                    </button>
                                </div>
                                <input 
                                    type="text" 
                                    wire:model="outreachSubject" 
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-white border border-[#eaecf0] text-[#111827] focus:outline-none focus:border-[#2563eb] text-xs font-medium"
                                />
                            </div>

                            <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] text-xs space-y-1">
                                <div class="text-[#6b7280] text-[10px] uppercase font-bold">Targeted Creator Hook</div>
                                <div class="text-[#374151]">{{ $outreachProspect->primary_outreach_angle }}</div>
                            </div>

                            <div>
                                <label class="block text-[#4b5563] font-bold mb-1.5">Email Message Body</label>
                                <textarea 
                                    wire:model="outreachBody" 
                                    rows="12" 
                                    class="w-full p-4 rounded-xl bg-white border border-[#eaecf0] text-[#111827] focus:outline-none focus:border-[#2563eb] text-xs leading-relaxed resize-y font-mono"
                                ></textarea>
                            </div>
                        </div>
                    @else
                        <!-- LIVE PREVIEW MODE (Dribbble Clean Template Layout) -->
                        <div class="max-w-2xl mx-auto rounded-2xl bg-[#f8fafc] border border-[#eaecf0] p-6 shadow-sm space-y-4">
                            <!-- Centered Header Lockup -->
                            <div class="flex flex-col items-center justify-center text-center pb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-[#059669] text-white font-extrabold text-sm flex items-center justify-center shadow-md shadow-emerald-500/20">
                                        FV
                                    </div>
                                    <div class="text-left">
                                        <div class="font-black text-lg text-[#0f172a] leading-none tracking-tight">FanVault</div>
                                        <div class="text-[11px] text-[#64748b] font-semibold tracking-wide mt-0.5">The Creator Time Capsule</div>
                                    </div>
                                </div>
                            </div>

                            <!-- White Card Container -->
                            <div class="bg-white rounded-2xl p-6 sm:p-8 border border-[#e2e8f0] shadow-2xs space-y-5">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded bg-[#ecfdf5] text-[11px] font-bold text-[#059669] uppercase tracking-wider">
                                        Creator Concept
                                    </span>
                                    <span class="text-xs text-[#64748b] font-medium">
                                        {{ $outreachProspect->speciality }}
                                    </span>
                                </div>

                                <div>
                                    <h2 class="text-xl sm:text-2xl font-bold text-[#0f172a] leading-snug tracking-tight">
                                        Turn Your Next Milestone Into A Live Community Reveal Stream
                                    </h2>
                                    <p class="text-xs sm:text-sm text-[#475569] mt-2 leading-relaxed">
                                        Hi {{ explode(' ', $outreachProspect->creator)[0] }} — we put together a private FanVault time capsule concept specifically for <strong>{{ $outreachProspect->creator }}</strong>.
                                    </p>
                                </div>

                                <div class="text-xs sm:text-sm text-[#334155] leading-relaxed whitespace-pre-wrap font-sans">
{!! nl2br(e($outreachBody)) !!}
                                </div>

                                <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#e2e8f0] text-xs space-y-2">
                                    <div class="text-[11px] uppercase font-bold text-[#64748b] tracking-wider">
                                        Why This Beats Another Generic Tweet / Merch Drop:
                                    </div>
                                    <div class="space-y-1.5 text-[#475569] text-[11px] leading-relaxed">
                                        <div>🎥 <strong class="text-[#0f172a]">1–2 Hours of Stream Content:</strong> Live reactions reading fan letters and unboxing predictions on stream.</div>
                                        <div>💎 <strong class="text-[#0f172a]">A Permanent Community Keepsake:</strong> Every letter is sealed and preserved as a lasting piece of your journey.</div>
                                        <div>⚡ <strong class="text-[#0f172a]">100% Free & Zero Tech Work:</strong> We handle the custom setup, hosting, and design for you at zero cost.</div>
                                    </div>
                                </div>

                                <div class="text-center pt-2">
                                    <a 
                                        href="https://getfanvault.com/with/{{ Str::slug($outreachProspect->creator) }}" 
                                        target="_blank" 
                                        class="inline-block px-8 py-3.5 rounded-lg bg-[#059669] hover:bg-[#047857] text-white font-bold text-xs sm:text-sm shadow-md shadow-emerald-600/25 transition-all text-center"
                                    >
                                        View {{ $outreachProspect->creator }}'s Time Capsule Concept ➔
                                    </a>
                                </div>

                                <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#e2e8f0] text-left text-xs text-[#64748b] leading-relaxed">
                                    <strong class="text-[#0f172a] block font-semibold mb-0.5">Having trouble with the button?</strong>
                                    Copy and paste this private draft URL into your browser:<br>
                                    <a href="https://getfanvault.com/with/{{ Str::slug($outreachProspect->creator) }}" target="_blank" class="text-[#059669] underline break-all font-medium">
                                        https://getfanvault.com/with/{{ Str::slug($outreachProspect->creator) }}
                                    </a>
                                </div>

                                <div class="pt-4 border-t border-[#f1f5f9] text-xs text-[#475569] space-y-1">
                                    <div>Warm regards,</div>
                                    <div class="font-bold text-[#0f172a] text-sm">Oluwatobi Solomon</div>
                                    <div class="text-[#64748b] text-xs">Founder, FanVault</div>
                                    <div class="pt-0.5">
                                        <a href="mailto:oluwatobi@getfanvault.com" class="text-[#059669] font-medium underline">oluwatobi@getfanvault.com</a>
                                        &nbsp;·&nbsp;
                                        <a href="https://getfanvault.com" class="text-[#64748b] underline">getfanvault.com</a>
                                    </div>
                                    <div class="mt-3 p-3 rounded-lg bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46] text-[11px] leading-relaxed">
                                        💡 <strong>Quick Note:</strong> Just hit <strong>Reply</strong> to this email! It goes straight to my personal inbox at <strong class="underline">oluwatobi@getfanvault.com</strong> — happy to answer any questions or set up a test capsule for {{ $outreachProspect->creator }} in 5 minutes.
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Bottom Action Bar -->
                <div class="px-6 py-4 bg-[#fcfcfd] border-t border-[#eaecf0] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <!-- Left: Test Send Box -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <span class="text-[#6b7280] text-xs font-semibold shrink-0">Send Test:</span>
                        <input 
                            type="email" 
                            wire:model="testOutreachRecipient" 
                            placeholder="admin@email.com" 
                            class="px-2.5 py-1.5 rounded-lg bg-white border border-[#eaecf0] text-[#111827] text-xs focus:outline-none focus:border-[#2563eb] w-48"
                        />
                        <button 
                            type="button" 
                            wire:click="sendTestOutreach" 
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 rounded-lg border border-[#eaecf0] bg-white hover:bg-[#f9fafb] text-[#374151] font-bold cursor-pointer transition-colors shrink-0 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="sendTestOutreach">Send Test</span>
                            <span wire:loading wire:target="sendTestOutreach">Sending...</span>
                        </button>
                    </div>

                    <!-- Right: Main Send / Cancel Actions -->
                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button 
                            type="button" 
                            wire:click="closeOutreachComposer" 
                            class="px-4 py-2 rounded-xl border border-[#eaecf0] bg-white text-[#4b5563] hover:text-[#111827] cursor-pointer font-semibold"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            wire:click="sendOutreachEmail" 
                            wire:loading.attr="disabled"
                            wire:confirm="Ready to send this personalized outreach email to {{ $outreachToEmail }}? Reply-to is set to oluwatobi@getfanvault.com."
                            class="px-5 py-2 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold cursor-pointer transition-colors shadow-sm shadow-blue-500/20 disabled:opacity-50 flex items-center gap-1.5"
                        >
                            <span wire:loading.remove wire:target="sendOutreachEmail">🚀 Send to {{ $outreachProspect->creator }}</span>
                            <span wire:loading wire:target="sendOutreachEmail">Dispatching Email...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 2. INSPECT & EDIT PROSPECT MODAL -->
    @if($inspectedProspect)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="w-full max-w-3xl bg-white border border-[#eaecf0] rounded-2xl p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between border-b border-[#f1f3f5] pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold font-mono text-[#2563eb]">
                                PROSPECT #{{ $inspectedProspect->prospect_number }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $inspectedProspect->priorityBadgeClass() }}">
                                Priority {{ $inspectedProspect->recommended_priority }}
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $inspectedProspect->specialityBadgeClass() }}">
                                {{ $inspectedProspect->speciality }}
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold text-[#111827] mt-1.5 flex items-center gap-2">
                            <span>{{ $inspectedProspect->creator }}</span>
                            @if($inspectedProspect->contact_url)
                                <a 
                                    href="{{ $inspectedProspect->contact_url }}" 
                                    target="_blank" 
                                    class="text-xs px-2.5 py-1 rounded-lg bg-[#eff6ff] text-[#2563eb] hover:bg-[#dbeafe] font-semibold flex items-center gap-1"
                                >
                                    <span>Search Channel ↗</span>
                                </a>
                            @endif
                        </h2>
                    </div>
                    <button wire:click="closeInspectProspect" class="text-[#9ca3af] hover:text-[#111827] text-lg cursor-pointer font-bold">
                        ✕
                    </button>
                </div>

                <!-- Breakdown Cards (Featuring Outreach Angle & Email Subject with Instant Copy) -->
                <div class="space-y-3.5 text-xs">
                    <!-- Email Subject Line Box -->
                    <div class="p-4 rounded-xl bg-[#eff6ff] border border-[#bfdbfe]">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[#1d4ed8] text-[11px] uppercase font-bold flex items-center gap-1.5">
                                <span>✉️ Email Subject Line</span>
                            </span>
                            <button 
                                type="button" 
                                onclick="navigator.clipboard.writeText('{{ addslashes($inspectedProspect->email_subject) }}'); alert('Subject copied to clipboard!');" 
                                class="text-xs px-2.5 py-1 rounded-lg bg-white border border-[#bfdbfe] text-[#1d4ed8] font-bold hover:bg-[#eff6ff] transition-colors flex items-center gap-1 shadow-2xs cursor-pointer"
                            >
                                <span>📋</span>
                                <span>Copy Subject</span>
                            </button>
                        </div>
                        <div class="text-sm font-bold text-[#111827] select-all mt-1">
                            {{ $inspectedProspect->email_subject }}
                        </div>
                    </div>

                    <!-- Primary Outreach Angle Box -->
                    <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#eaecf0]">
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <span class="text-[#4b5563] text-[11px] uppercase font-bold flex items-center gap-1.5">
                                <span>🎯 Primary Outreach Angle</span>
                            </span>
                            <button 
                                type="button" 
                                onclick="navigator.clipboard.writeText('{{ addslashes($inspectedProspect->primary_outreach_angle) }}'); alert('Outreach angle copied to clipboard!');" 
                                class="text-xs px-2.5 py-1 rounded-lg bg-white border border-[#eaecf0] text-[#374151] font-semibold hover:bg-[#f9fafb] transition-colors flex items-center gap-1 shadow-2xs cursor-pointer"
                            >
                                <span>📋</span>
                                <span>Copy Angle</span>
                            </button>
                        </div>
                        <p class="text-xs text-[#111827] leading-relaxed font-medium">
                            {{ $inspectedProspect->primary_outreach_angle }}
                        </p>
                    </div>

                    <!-- 2-Column: Personalisation Note & Contact Route -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0]">
                            <span class="text-[#6b7280] block text-[10px] uppercase font-bold">Personalisation Note</span>
                            <p class="text-[#4b5563] mt-1 leading-relaxed">
                                {{ $inspectedProspect->personalisation_note ?: 'No extra personalisation notes.' }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0]">
                            <span class="text-[#6b7280] block text-[10px] uppercase font-bold">Contact Route & Type</span>
                            <div class="text-[#111827] font-bold mt-1">{{ $inspectedProspect->contact_type ?: 'YouTube Business Enquiry' }}</div>
                            <div class="text-[11px] text-[#6b7280] mt-0.5">{{ $inspectedProspect->email_status }}</div>
                        </div>
                    </div>
                </div>

                <!-- Update Details Form -->
                <div class="p-4 rounded-xl bg-[#f8fafc] border border-[#eaecf0] space-y-3">
                    <div class="text-xs font-bold text-[#111827] uppercase tracking-wider">
                        Update Marketing & Enrichment Details
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-[#4b5563] mb-1">Direct Contact Email</label>
                            <input 
                                type="email" 
                                wire:model="editProspectEmail" 
                                placeholder="creator@business.com" 
                                class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#111827] text-xs focus:outline-none focus:border-[#2563eb]"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-[#4b5563] mb-1">Pipeline Status</label>
                            <select 
                                wire:model="editProspectStatus" 
                                class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#111827] text-xs focus:outline-none focus:border-[#2563eb]"
                            >
                                <option value="Not contacted">Not contacted</option>
                                <option value="Contacted">Contacted</option>
                                <option value="Replied">Replied</option>
                                <option value="In Discussion">In Discussion</option>
                                <option value="Onboarded">Onboarded</option>
                                <option value="Declined">Declined</option>
                                <option value="Passed">Passed</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#4b5563] mb-1">Internal Notes & History</label>
                        <textarea 
                            wire:model="editProspectNotes" 
                            rows="2" 
                            placeholder="Add notes (e.g., outreach date, response, manager contact details)..." 
                            class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#111827] text-xs focus:outline-none focus:border-[#2563eb]"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button 
                            type="button" 
                            wire:click="updateProspectStatus('{{ $inspectedProspect->id }}', 'Contacted')" 
                            class="px-3 py-1.5 rounded-lg bg-[#fef3c7] hover:bg-[#fde68a] text-[#92400e] text-xs font-bold transition-colors cursor-pointer"
                        >
                            ✓ Mark Contacted (Stamp Now)
                        </button>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                wire:click="openOutreachComposer('{{ $inspectedProspect->id }}')" 
                                class="px-3.5 py-1.5 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs flex items-center gap-1.5 cursor-pointer shadow-xs transition-colors"
                            >
                                ✉️ Compose & Preview Email
                            </button>
                            <button 
                                wire:click="closeInspectProspect" 
                                class="px-3.5 py-1.5 rounded-xl border border-[#eaecf0] bg-white text-xs font-medium text-[#4b5563] hover:text-[#111827] cursor-pointer"
                            >
                                Close
                            </button>
                            <button 
                                wire:click="saveProspectDetails" 
                                class="px-4 py-1.5 rounded-xl bg-[#10b981] hover:bg-[#059669] text-xs font-bold text-white transition-colors cursor-pointer shadow-xs"
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. INSPECT LETTER MODAL -->
    @if($inspectedPostcard)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="w-full max-w-2xl bg-white border border-[#eaecf0] rounded-2xl p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between border-b border-[#f1f3f5] pb-4">
                    <div>
                        <span class="font-mono text-xs font-bold text-[#2563eb]">
                            CAPSULE NO. #{{ Capsule::formatNumber($inspectedPostcard->number) }}
                        </span>
                        <h2 class="text-xl font-bold text-[#111827] mt-1">
                            Letter from {{ $inspectedPostcard->name }}
                        </h2>
                        <div class="text-xs text-[#6b7280]">
                            Sealed: {{ $inspectedPostcard->sealed_at?->format('F d, Y · H:i:s T') ?? $inspectedPostcard->created_at->format('F d, Y') }}
                        </div>
                    </div>
                    <button wire:click="closeInspect" class="text-[#9ca3af] hover:text-[#111827] text-lg cursor-pointer font-bold">
                        ✕
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0]">
                        <span class="text-[10px] uppercase text-[#6b7280] font-bold">Public Teaser</span>
                        <p class="italic text-sm text-[#374151] mt-1">
                            “{{ $inspectedPostcard->teaser }}”
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#eff6ff] border border-[#bfdbfe]">
                        <span class="text-[10px] uppercase text-[#1d4ed8] font-bold">Private Sealed Letter</span>
                        <p class="text-sm text-[#1e293b] mt-2 font-serif leading-relaxed whitespace-pre-wrap">
                            {{ $inspectedPostcard->envelope?->letter ?: 'No full letter content recorded.' }}
                        </p>
                    </div>

                    @if($inspectedPostcard->envelope?->photo_path)
                        <div class="p-3.5 rounded-xl bg-[#f8fafc] border border-[#eaecf0]">
                            <span class="text-[10px] uppercase text-[#6b7280] font-bold mb-2 block">Attached Portrait Photo</span>
                            <img src="{{ asset('storage/' . $inspectedPostcard->envelope->photo_path) }}" alt="Attached Portrait" class="max-h-48 rounded-lg object-cover" />
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-lg bg-[#f8fafc]">
                            <span class="text-[#6b7280] block font-medium">Fan Email</span>
                            <span class="text-[#111827] font-bold">{{ $inspectedPostcard->envelope?->email ?: 'N/A' }}</span>
                        </div>
                        <div class="p-3 rounded-lg bg-[#f8fafc]">
                            <span class="text-[#6b7280] block font-medium">Contribution Amount</span>
                            <span class="text-[#059669] font-bold">${{ number_format(($inspectedPostcard->referral?->amount_cents ?? 500) / 100, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#f1f3f5] flex items-center justify-between">
                    <a href="{{ route('message', $inspectedPostcard) }}" target="_blank" class="text-xs font-semibold text-[#2563eb] hover:underline">
                        Open Public Keepsake Pass ↗
                    </a>
                    <button wire:click="closeInspect" class="px-4 py-2 rounded-xl bg-[#2563eb] text-white hover:bg-[#1d4ed8] text-xs font-bold transition-colors cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- 4. EDIT CREATOR MODAL -->
    @if($editCreatorId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="w-full max-w-md bg-white border border-[#eaecf0] rounded-2xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-[#f1f3f5] pb-3">
                    <h3 class="text-sm font-bold text-[#111827] uppercase">Edit Creator Vault Settings</h3>
                    <button wire:click="closeEditCreator" class="text-[#9ca3af] hover:text-[#111827] cursor-pointer font-bold">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block text-[#4b5563] font-semibold mb-1">Creator Name</label>
                        <input type="text" wire:model="editCreatorName" class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#111827] focus:outline-none focus:border-[#2563eb]" />
                    </div>

                    <div>
                        <label class="block text-[#4b5563] font-semibold mb-1">Handle</label>
                        <input type="text" wire:model="editCreatorHandle" class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#111827] focus:outline-none focus:border-[#2563eb]" />
                    </div>

                    <div>
                        <label class="block text-[#4b5563] font-semibold mb-1">Platform</label>
                        <select wire:model="editCreatorPlatform" class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#374151] focus:outline-none focus:border-[#2563eb]">
                            <option value="YouTube">YouTube</option>
                            <option value="Twitch">Twitch</option>
                            <option value="TikTok">TikTok</option>
                            <option value="Kick">Kick</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Podcast">Podcast</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[#4b5563] font-semibold mb-1">Minimum Seal Price ($ USD)</label>
                        <input type="number" min="1" max="100" wire:model="editCreatorMinPrice" class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-[#111827] focus:outline-none focus:border-[#2563eb]" />
                    </div>
                </div>

                <div class="pt-3 border-t border-[#f1f3f5] flex items-center justify-end gap-2">
                    <button wire:click="closeEditCreator" class="px-3.5 py-2 rounded-xl border border-[#eaecf0] bg-white text-xs font-semibold text-[#4b5563] hover:text-[#111827]">
                        Cancel
                    </button>
                    <button wire:click="saveCreator" class="px-4 py-2 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-xs font-bold text-white transition-colors cursor-pointer shadow-xs">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
