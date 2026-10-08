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

            <!-- Help & Support with Badge '8' -->
            <a href="mailto:support@getfanvault.com" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium text-[#4b5563] hover:text-[#111827] hover:bg-[#f9fafb] transition-colors">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-[#9ca3af]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Help & Support</span>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-[#dcfce7] text-[#15803d] font-bold text-xs">
                    8
                </span>
            </a>

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

            <!-- Right Actions: + New Project Pill, Notification Bell, User Avatar -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Dstudio Primary Action Button -->
                <button 
                    type="button"
                    wire:click="setTab('prospects')"
                    class="px-4 py-2.5 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-sm shadow-blue-500/20 transition-all cursor-pointer"
                >
                    <span>+ New Project</span>
                    <svg class="w-3.5 h-3.5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Notification Bell with Pink Dot -->
                <button 
                    type="button"
                    class="w-10 h-10 rounded-xl border border-[#eaecf0] bg-white hover:bg-[#f9fafb] flex items-center justify-center text-[#4b5563] relative transition-colors cursor-pointer"
                    title="Notifications"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="w-2.5 h-2.5 rounded-full bg-[#ec4899] border-2 border-white absolute top-2 right-2"></span>
                </button>

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

                    <!-- Right Quick Actions: Share, + Add Task -->
                    <div class="flex items-center gap-2.5">
                        <a 
                            href="{{ route('home') }}" 
                            target="_blank" 
                            class="px-4 py-2 rounded-xl border border-[#e5e7eb] bg-white hover:bg-[#f9fafb] text-[#374151] font-semibold text-xs transition-all shadow-2xs flex items-center gap-2 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-[#6b7280]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            <span>Share</span>
                        </a>

                        <button 
                            type="button"
                            wire:click="setTab('prospects')"
                            class="px-4 py-2 rounded-xl border border-[#e5e7eb] bg-white hover:bg-[#f9fafb] text-[#111827] font-semibold text-xs transition-all shadow-2xs flex items-center gap-2 cursor-pointer"
                        >
                            <span>+ Add Task</span>
                        </button>
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
                    <!-- SECTION 1: MY PROJECTS (DSTUDIO MAIN TABLE CARD) -->
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs overflow-hidden">
                        <!-- Card Header Row -->
                        <div class="px-6 py-5 flex items-center justify-between border-b border-[#f1f3f5]">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#4b5563]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    <h2 class="text-base font-bold text-[#111827]">My Projects</h2>
                                </div>
                                <div class="relative">
                                    <button type="button" class="px-3 py-1 rounded-lg border border-[#eaecf0] bg-white text-xs font-semibold text-[#4b5563] flex items-center gap-1.5 shadow-2xs">
                                        <span>This Week</span>
                                        <svg class="w-3 h-3 text-[#9ca3af]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                wire:click="setTab('prospects')"
                                class="px-4 py-1.5 rounded-full border border-[#eaecf0] text-xs font-semibold text-[#374151] hover:bg-[#f9fafb] transition-colors cursor-pointer shadow-2xs"
                            >
                                See All
                            </button>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#fcfcfd] text-[#6b7280] text-xs font-semibold border-b border-[#f1f3f5]">
                                    <tr>
                                        <th class="py-3 px-6">
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                                <span>Task Name</span>
                                            </div>
                                        </th>
                                        <th class="py-3 px-6">
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                <span>Assign</span>
                                            </div>
                                        </th>
                                        <th class="py-3 px-6">
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                <span>Status</span>
                                            </div>
                                        </th>
                                        <th class="py-3 px-6 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f1f3f5]">
                                    @foreach($prospects->take(5) as $idx => $p)
                                        <tr class="hover:bg-[#f9fafb] transition-colors group">
                                            <!-- Task / Creator Name with Meta Icons -->
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-[#f4f5f6] flex items-center justify-center text-[#4b5563] shrink-0">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-[#111827] group-hover:text-[#2563eb] transition-colors">
                                                            Outreach to {{ $p->creator }}
                                                        </div>
                                                        <div class="flex items-center gap-3 text-xs text-[#9ca3af] mt-0.5">
                                                            <span class="flex items-center gap-1">
                                                                💬 {{ 5 + $idx * 2 }}
                                                            </span>
                                                            <span class="flex items-center gap-1">
                                                                📎 {{ 2 + ($idx % 3) }}
                                                            </span>
                                                            <span>·</span>
                                                            <span>{{ $p->speciality }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Assignee Avatar & Info -->
                                            <td class="py-4 px-6">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-[#2563eb] to-[#38bdf8] text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                                                        {{ substr($p->creator, 0, 1) }}
                                                    </div>
                                                    <div class="text-xs">
                                                        <div class="font-semibold text-[#111827]">{{ $p->creator }}</div>
                                                        <div class="text-[#9ca3af] truncate max-w-[160px]">{{ $p->effectiveEmail() ?: 'Needs email' }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Dstudio Signature Pill Status Badge -->
                                            <td class="py-4 px-6">
                                                @if($p->status === 'Contacted')
                                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-[#dbeafe] text-[#1d4ed8]">
                                                        Completed
                                                    </span>
                                                @elseif($p->status === 'In Discussion' || $p->status === 'Replied')
                                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-[#dcfce7] text-[#15803d]">
                                                        In Progress
                                                    </span>
                                                @else
                                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-[#f3e8ff] text-[#7e22ce]">
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Actions -->
                                            <td class="py-4 px-6 text-right">
                                                <button 
                                                    type="button" 
                                                    wire:click="openOutreachComposer('{{ $p->id }}')" 
                                                    class="px-3 py-1.5 rounded-lg bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-semibold text-xs transition-colors cursor-pointer"
                                                >
                                                    Open ➔
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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

                    <!-- SECTION 2: 2-COLUMN SPLIT (SCHEDULE WIDGET & NOTES WIDGET) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-7">
                        <!-- COLUMN 1: SCHEDULE WIDGET (DSTUDIO TIMELINE) -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-5">
                            <!-- Widget Header with ••• Menu -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#4b5563]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <h3 class="text-base font-bold text-[#111827]">Schedule</h3>
                                </div>
                                <button type="button" class="text-[#9ca3af] hover:text-[#111827] text-lg font-bold">
                                    •••
                                </button>
                            </div>

                            <!-- Horizontal Day Strip (Exact Dstudio Component) -->
                            <div class="grid grid-cols-7 gap-2 text-center text-xs">
                                <div class="py-2 rounded-xl text-[#6b7280]">
                                    <div class="font-medium text-[11px]">Mo</div>
                                    <div class="font-bold text-sm mt-0.5">15</div>
                                </div>
                                <div class="py-2 rounded-xl text-[#6b7280]">
                                    <div class="font-medium text-[11px]">Tu</div>
                                    <div class="font-bold text-sm mt-0.5">16</div>
                                </div>
                                <!-- Active Day (Purple Highlighted Pill in Reference) -->
                                <div class="py-2 rounded-xl bg-[#c084fc] text-white shadow-2xs font-bold">
                                    <div class="text-[11px]">We</div>
                                    <div class="text-sm mt-0.5">17</div>
                                </div>
                                <div class="py-2 rounded-xl text-[#6b7280]">
                                    <div class="font-medium text-[11px]">Th</div>
                                    <div class="font-bold text-sm mt-0.5">18</div>
                                </div>
                                <div class="py-2 rounded-xl text-[#6b7280]">
                                    <div class="font-medium text-[11px]">Fr</div>
                                    <div class="font-bold text-sm mt-0.5">19</div>
                                </div>
                                <div class="py-2 rounded-xl text-[#6b7280]">
                                    <div class="font-medium text-[11px]">Sa</div>
                                    <div class="font-bold text-sm mt-0.5">20</div>
                                </div>
                                <div class="py-2 rounded-xl text-[#6b7280]">
                                    <div class="font-medium text-[11px]">Su</div>
                                    <div class="font-bold text-sm mt-0.5">14</div>
                                </div>
                            </div>

                            <!-- Timeline Cards with Colored Vertical Accent Line -->
                            <div class="space-y-3 pt-1">
                                <!-- Card 1: Green Accent Line -->
                                <div class="p-3.5 rounded-r-xl border-l-4 border-l-[#10b981] bg-[#fcfcfd] border border-[#f1f3f5] flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-sm text-[#111827]">Kickoff Meeting</div>
                                        <div class="text-xs text-[#6b7280] mt-0.5">01:00 PM to 02:30 PM</div>
                                    </div>
                                    <div class="flex items-center -space-x-2">
                                        <div class="w-7 h-7 rounded-full bg-[#fde047] text-[#854d0e] font-bold text-[10px] flex items-center justify-center ring-2 ring-white">
                                            JS
                                        </div>
                                        <div class="w-7 h-7 rounded-full bg-[#38bdf8] text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">
                                            OS
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 2: Blue Accent Line -->
                                <div class="p-3.5 rounded-r-xl border-l-4 border-l-[#2563eb] bg-[#fcfcfd] border border-[#f1f3f5] flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-sm text-[#111827]">Review Milestone Stream Submissions</div>
                                        <div class="text-xs text-[#6b7280] mt-0.5">04:00 PM to 05:30 PM</div>
                                    </div>
                                    <div class="flex items-center -space-x-2">
                                        <div class="w-7 h-7 rounded-full bg-[#fb7185] text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">
                                            MK
                                        </div>
                                        <div class="w-7 h-7 rounded-full bg-[#a78bfa] text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">
                                            FV
                                        </div>
                                    </div>
                                </div>

                                <!-- Card 3: Pink Accent Line -->
                                <div class="p-3.5 rounded-r-xl border-l-4 border-l-[#ec4899] bg-[#fcfcfd] border border-[#f1f3f5] flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-bold text-sm text-[#111827]">Weekly Stripe Creator Payout Settlement</div>
                                        <div class="text-xs text-[#6b7280] mt-0.5">06:00 PM to 07:00 PM</div>
                                    </div>
                                    <div class="flex items-center -space-x-2">
                                        <div class="w-7 h-7 rounded-full bg-[#34d399] text-[#064e3b] font-bold text-[10px] flex items-center justify-center ring-2 ring-white">
                                            ST
                                        </div>
                                        <div class="w-7 h-7 rounded-full bg-[#2563eb] text-white font-bold text-[10px] flex items-center justify-center ring-2 ring-white">
                                            OS
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMN 2: NOTES WIDGET (DSTUDIO CHECKLIST) -->
                        <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#4b5563]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="text-base font-bold text-[#111827]">Notes</h3>
                                </div>
                                <button type="button" class="text-[#9ca3af] hover:text-[#111827] text-lg font-bold">
                                    +
                                </button>
                            </div>

                            <div class="space-y-4 divide-y divide-[#f1f3f5]">
                                <!-- Checklist Item 1 -->
                                <div class="flex items-start gap-3.5 pt-1">
                                    <div class="w-5 h-5 rounded-full border-2 border-[#d1d5db] shrink-0 mt-0.5 cursor-pointer hover:border-[#2563eb]"></div>
                                    <div>
                                        <div class="font-bold text-sm text-[#111827]">Landing Page & Creator Door Pitch</div>
                                        <p class="text-xs text-[#6b7280] mt-1 leading-relaxed">
                                            Send personalized milestone stream pitch with the Dribbble verify email template to the top 25 gaming targets.
                                        </p>
                                    </div>
                                </div>

                                <!-- Checklist Item 2 -->
                                <div class="flex items-start gap-3.5 pt-4">
                                    <div class="w-5 h-5 rounded-full border-2 border-[#d1d5db] shrink-0 mt-0.5 cursor-pointer hover:border-[#2563eb]"></div>
                                    <div>
                                        <div class="font-bold text-sm text-[#111827]">Verify Stripe Payout Thresholds</div>
                                        <p class="text-xs text-[#6b7280] mt-1 leading-relaxed">
                                            Ensure creators have connected verified payout accounts before milestone reveal streams go live.
                                        </p>
                                    </div>
                                </div>

                                <!-- Checklist Item 3 (Completed with Purple Checked Circle) -->
                                <div class="flex items-start gap-3.5 pt-4">
                                    <div class="w-5 h-5 rounded-full bg-[#c084fc] flex items-center justify-center text-white shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-sm text-[#111827]">Launch New Admin Dstudio Dashboard Interface</div>
                                        <p class="text-xs text-[#6b7280] mt-1 leading-relaxed">
                                            Total overhaul of the executive console with clean light palette, interactive widgets, and Dstudio UX hierarchy.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: FINANCIAL REVENUE CARDS & CAPSULE CAPACITY -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- GMV Card -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="text-xs font-semibold text-[#6b7280]">GROSS VOLUME (GMV)</div>
                            <div class="text-3xl font-extrabold text-[#111827] tracking-tight">
                                ${{ number_format($totalGmvCents / 100, 2) }}
                            </div>
                            <div class="text-xs text-[#10b981] font-semibold pt-1">
                                100% Processed volume across letters
                            </div>
                        </div>

                        <!-- Platform Net (20%) -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="text-xs font-semibold text-[#6b7280]">PLATFORM NET (20%)</div>
                            <div class="text-3xl font-extrabold text-[#2563eb] tracking-tight">
                                ${{ number_format($totalPlatformRevenueCents / 100, 2) }}
                            </div>
                            <div class="text-xs text-[#4b5563] pt-1">
                                Direct retained protocol fee
                            </div>
                        </div>

                        <!-- Creator Share (80%) -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="text-xs font-semibold text-[#6b7280]">CREATOR SHARE (80%)</div>
                            <div class="text-3xl font-extrabold text-[#059669] tracking-tight">
                                ${{ number_format($totalCreatorCutCents / 100, 2) }}
                            </div>
                            <div class="text-xs text-[#6b7280] pt-1 flex items-center justify-between">
                                <span class="text-[#10b981]">Settled: ${{ number_format($totalPaidPayoutsCents / 100, 2) }}</span>
                                <span class="text-[#f59e0b]">Pending: ${{ number_format($totalPendingPayoutsCents / 100, 2) }}</span>
                            </div>
                        </div>

                        <!-- 10M Capsule Capacity -->
                        <div class="p-6 rounded-2xl bg-white border border-[#eaecf0] shadow-2xs space-y-1">
                            <div class="text-xs font-semibold text-[#6b7280]">CAPSULE CAPACITY</div>
                            <div class="text-3xl font-extrabold text-[#111827] tracking-tight">
                                {{ Capsule::formatNumber($totalLetters) }}
                            </div>
                            <div class="w-full bg-[#f1f3f5] rounded-full h-2 mt-2 overflow-hidden">
                                <div class="bg-[#2563eb] h-2 rounded-full" style="width: {{ max(1, min(100, ($totalLetters / 10000000) * 100)) }}%"></div>
                            </div>
                            <div class="text-xs text-[#6b7280] pt-1 flex items-center justify-between">
                                <span>{{ Capsule::formatNumber(Capsule::TOTAL_CAP - $totalLetters) }} Left</span>
                                <span class="font-bold text-[#f59e0b]">{{ $foundingCount }}/1,000 Founding</span>
                            </div>
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
                    <div class="bg-white rounded-2xl border border-[#eaecf0] shadow-2xs p-6 space-y-5">
                        <div>
                            <h2 class="text-xl font-bold text-[#111827]">System Health & Diagnostics</h2>
                            <p class="text-xs text-[#6b7280] mt-1">Transactional mail test dispatch and core environment health.</p>
                        </div>

                        <!-- Mailer Tester Form -->
                        <div class="p-5 rounded-xl bg-[#f8fafc] border border-[#eaecf0] space-y-4">
                            <h3 class="text-sm font-bold text-[#111827]">Transactional Mail Dispatch Tester</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-[#4b5563] mb-1">Destination Email</label>
                                    <input 
                                        type="email" 
                                        wire:model="testEmailAddress" 
                                        placeholder="admin@email.com" 
                                        class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-sm text-[#111827] focus:outline-none focus:border-[#2563eb]"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#4b5563] mb-1">Template</label>
                                    <select wire:model="testEmailType" class="w-full px-3 py-2 rounded-xl bg-white border border-[#eaecf0] text-sm text-[#374151] focus:outline-none focus:border-[#2563eb]">
                                        <option value="fan">Fan Receipt (Sealed)</option>
                                        <option value="creator">Creator New Contribution Alert</option>
                                        <option value="login">Creator Magic Login Link</option>
                                    </select>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                wire:click="sendTestEmail" 
                                class="px-5 py-2.5 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] text-white font-bold text-xs transition-colors cursor-pointer shadow-xs"
                            >
                                Dispatch Diagnostic Email ➔
                            </button>

                            @if($testEmailResult)
                                <div class="p-3.5 rounded-xl text-xs font-mono {{ $testEmailSuccess ? 'bg-[#ecfdf5] border border-[#a7f3d0] text-[#065f46]' : 'bg-[#fef2f2] border border-[#fecaca] text-[#991b1b]' }}">
                                    {{ $testEmailResult }}
                                </div>
                            @endif
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
