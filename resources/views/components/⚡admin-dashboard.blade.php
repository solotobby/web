<?php

use App\Mail\CreatorLoginLink;
use App\Mail\CreatorNewLetterMail;
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
        ];
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Executive Navigation Tabs Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#1e293b] pb-4">
        <nav class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <button 
                wire:click="setTab('overview')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'overview' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Command Center</span>
            </button>

            <button 
                wire:click="setTab('creators')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'creators' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span>Creators</span>
                <span class="px-1.5 py-0.2 rounded-md {{ $tab === 'creators' ? 'bg-[#090d16]/30 text-[#090d16]' : 'bg-[#1e293b] text-[#cbd5e1]' }} text-[11px] font-bold">
                    {{ $creators->count() }}
                </span>
            </button>

            <button 
                wire:click="setTab('prospects')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'prospects' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                </svg>
                <span>Outreach CRM</span>
                <span class="px-1.5 py-0.2 rounded-md {{ $tab === 'prospects' ? 'bg-[#090d16]/30 text-[#090d16]' : 'bg-[#1e293b] text-[#cbd5e1]' }} text-[11px] font-bold">
                    {{ $totalProspectsCount }}
                </span>
            </button>

            <button 
                wire:click="setTab('letters')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'letters' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <span>Fan Letters</span>
                <span class="px-1.5 py-0.2 rounded-md {{ $tab === 'letters' ? 'bg-[#090d16]/30 text-[#090d16]' : 'bg-[#1e293b] text-[#cbd5e1]' }} text-[11px] font-bold">
                    {{ $totalLetters }}
                </span>
            </button>

            <button 
                wire:click="setTab('payments')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'payments' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Ledger & Payouts</span>
            </button>

            <button 
                wire:click="setTab('milestones')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'milestones' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                </svg>
                <span>Milestones</span>
                <span class="px-1.5 py-0.2 rounded-md {{ $tab === 'milestones' ? 'bg-[#090d16]/30 text-[#090d16]' : 'bg-[#1e293b] text-[#cbd5e1]' }} text-[11px] font-bold">
                    {{ $milestones->count() }}
                </span>
            </button>

            <button 
                wire:click="setTab('system')" 
                class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-mono font-medium transition-all flex items-center gap-2 cursor-pointer {{ $tab === 'system' ? 'bg-[#10b981] text-[#090d16] font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)]' : 'bg-[#111827] text-[#94a3b8] hover:text-white hover:bg-[#161f30]' }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Diagnostics</span>
            </button>
        </nav>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#10b981]/10 text-[#34d399] border border-[#10b981]/20 font-mono text-[11px] font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-[#10b981] animate-pulse"></span>
                <span>GMV ${{ number_format($totalGmvCents / 100, 2) }}</span>
            </span>
        </div>
    </div>

    <!-- Generated Studio Login Toast -->
    @if($generatedLoginUrl)
        <div class="p-4 rounded-2xl bg-[#064e3b]/30 border border-[#059669]/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="text-xs font-mono font-bold text-[#34d399] flex items-center gap-2">
                    <span>🔑 Direct Impersonation Session Generated for {{ $generatedCreatorName }}</span>
                </div>
                <div class="text-xs text-[#94a3b8] font-mono break-all select-all">
                    {{ $generatedLoginUrl }}
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ $generatedLoginUrl }}" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-xs transition-colors flex items-center gap-1.5">
                    <span>Enter Studio ↗</span>
                </a>
                <button wire:click="$set('generatedLoginUrl', '')" class="px-3 py-1.5 rounded-xl border border-[#1e293b] text-[#94a3b8] hover:text-white text-xs font-mono">
                    Dismiss
                </button>
            </div>
        </div>
    @endif

    <!-- TAB 1: COMMAND CENTER / OVERVIEW -->
    @if($tab === 'overview')
        <div class="space-y-8">
            <!-- 6 High-Density Executive Financial Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- GMV Card -->
                <div class="p-5 rounded-2xl bg-[#0e1626] border border-[#1e293b] relative overflow-hidden group hover:border-[#10b981]/40 transition-colors">
                    <div class="flex items-center justify-between text-xs font-mono text-[#64748b] mb-1">
                        <span>GROSS VOLUME (GMV)</span>
                        <span class="text-[#10b981]">Total Processed</span>
                    </div>
                    <div class="font-mono text-3xl font-extrabold text-white tracking-tight">
                        ${{ number_format($totalGmvCents / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-[#94a3b8] font-mono mt-2 flex items-center gap-1.5">
                        <span class="text-[#34d399] font-semibold">100% Volume</span>
                        <span>across all fan letters</span>
                    </div>
                </div>

                <!-- Platform Take Revenue (20%) -->
                <div class="p-5 rounded-2xl bg-[#0e1626] border border-[#1e293b] relative overflow-hidden group hover:border-[#10b981]/40 transition-colors">
                    <div class="flex items-center justify-between text-xs font-mono text-[#64748b] mb-1">
                        <span>PLATFORM NET (20%)</span>
                        <span class="text-[#34d399]">Retained Fee</span>
                    </div>
                    <div class="font-mono text-3xl font-extrabold text-[#34d399] tracking-tight">
                        ${{ number_format($totalPlatformRevenueCents / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-[#94a3b8] font-mono mt-2">
                        FanVault direct protocol revenue
                    </div>
                </div>

                <!-- Creator Payout Liability (80%) -->
                <div class="p-5 rounded-2xl bg-[#0e1626] border border-[#1e293b] relative overflow-hidden group hover:border-[#38bdf8]/40 transition-colors">
                    <div class="flex items-center justify-between text-xs font-mono text-[#64748b] mb-1">
                        <span>CREATOR SHARE (80%)</span>
                        <span class="text-[#38bdf8]">Earnings Pool</span>
                    </div>
                    <div class="font-mono text-3xl font-extrabold text-[#38bdf8] tracking-tight">
                        ${{ number_format($totalCreatorCutCents / 100, 2) }}
                    </div>
                    <div class="text-[11px] text-[#94a3b8] font-mono mt-2 flex items-center justify-between">
                        <span class="text-[#10b981]">Settled: ${{ number_format($totalPaidPayoutsCents / 100, 2) }}</span>
                        <span class="text-[#f59e0b]">Pending: ${{ number_format($totalPendingPayoutsCents / 100, 2) }}</span>
                    </div>
                </div>

                <!-- 10M Capsule Capacity -->
                <div class="p-5 rounded-2xl bg-[#0e1626] border border-[#1e293b] relative overflow-hidden group hover:border-[#fde047]/40 transition-colors">
                    <div class="flex items-center justify-between text-xs font-mono text-[#64748b] mb-1">
                        <span>CAPSULE CAPACITY</span>
                        <span class="text-[#fde047]">{{ Capsule::TOTAL_CAP / 1000000 }}M Max</span>
                    </div>
                    <div class="font-mono text-3xl font-extrabold text-white tracking-tight">
                        {{ Capsule::formatNumber($totalLetters) }}
                    </div>
                    <!-- Visual Progress Bar -->
                    <div class="w-full bg-[#1e293b] rounded-full h-1.5 mt-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-[#10b981] to-[#34d399] h-1.5 rounded-full" style="width: {{ max(1, min(100, ($totalLetters / 10000000) * 100)) }}%"></div>
                    </div>
                    <div class="text-[11px] text-[#94a3b8] font-mono mt-1.5 flex items-center justify-between">
                        <span>{{ Capsule::formatNumber(Capsule::TOTAL_CAP - $totalLetters) }} Remaining</span>
                        <span class="text-[#fde047] font-semibold">{{ $foundingCount }}/1,000 Founding</span>
                    </div>
                </div>
            </div>

            <!-- Two-Column Operational Grids -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Column 1: Live Letters Stream (2 Cols) -->
                <div class="lg:col-span-2 rounded-2xl bg-[#0e1626] border border-[#1e293b] p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-[#1e293b] pb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#10b981] animate-pulse"></span>
                            <h2 class="text-sm font-mono font-bold text-white uppercase tracking-wider">
                                Live Capsule Sealed Stream
                            </h2>
                        </div>
                        <button wire:click="setTab('letters')" class="text-xs font-mono text-[#10b981] hover:underline cursor-pointer">
                            View All {{ $totalLetters }} Letters ➔
                        </button>
                    </div>

                    <div class="divide-y divide-[#1e293b]/60">
                        @forelse($recentLetters as $rl)
                            <div class="py-3 flex items-center justify-between gap-4 hover:bg-[#111827]/40 px-2 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="font-mono text-xs font-bold px-2 py-1 rounded bg-[#1e293b] text-[#34d399] shrink-0">
                                        #{{ Capsule::formatNumber($rl->number) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-white truncate flex items-center gap-2">
                                            <span>{{ $rl->name }}</span>
                                            @if($rl->location)
                                                <span class="text-xs font-normal text-[#64748b]">({{ $rl->location }})</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-[#94a3b8] truncate flex items-center gap-1.5">
                                            @if($rl->creator)
                                                <span class="text-[#38bdf8] font-medium">@ {{ $rl->creator->name }}</span>
                                                <span>·</span>
                                            @else
                                                <span class="text-[#94a3b8]">FanVault Community</span>
                                                <span>·</span>
                                            @endif
                                            <span class="italic text-[#64748b]">“{{ Str::limit($rl->teaser, 45) }}”</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-mono font-bold text-[#10b981]">
                                            +${{ number_format(($rl->referral?->amount_cents ?? 500) / 100, 2) }}
                                        </div>
                                        <div class="text-[10px] text-[#64748b] font-mono">
                                            {{ $rl->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <button 
                                        wire:click="inspectLetter('{{ $rl->id }}')" 
                                        class="px-2.5 py-1 rounded-lg bg-[#1e293b] hover:bg-[#334155] text-xs font-mono text-[#cbd5e1] hover:text-white transition-colors cursor-pointer"
                                    >
                                        Inspect
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-xs font-mono text-[#64748b]">
                                No sealed fan letters recorded yet.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Column 2: Top Creators Leaderboard (1 Col) -->
                <div class="rounded-2xl bg-[#0e1626] border border-[#1e293b] p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-[#1e293b] pb-3">
                        <h2 class="text-sm font-mono font-bold text-white uppercase tracking-wider">
                            Top Creator Vaults
                        </h2>
                        <button wire:click="setTab('creators')" class="text-xs font-mono text-[#10b981] hover:underline cursor-pointer">
                            Directory ➔
                        </button>
                    </div>

                    <div class="space-y-3">
                        @foreach($creators->take(5) as $top)
                            <div class="p-3 rounded-xl bg-[#111827] border border-[#1e293b] flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-lg bg-[#10b981]/20 text-[#34d399] font-mono font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($top->name, 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-white truncate">{{ $top->name }}</div>
                                        <div class="text-[11px] text-[#64748b] font-mono truncate">{{ $top->handle }} · {{ $top->platform }}</div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs font-mono font-bold text-[#34d399]">
                                        {{ $top->postcards_count }} letters
                                    </div>
                                    <div class="text-[10px] font-mono text-[#94a3b8]">
                                        ${{ number_format(($top->total_earnings_cents ?? 0) / 100, 2) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Quick System Actions Box -->
                    <div class="mt-6 pt-4 border-t border-[#1e293b] space-y-2">
                        <div class="text-xs font-mono font-bold text-[#64748b] uppercase tracking-wider mb-2">
                            Quick Operations
                        </div>
                        <button wire:click="settleAllReferrals" class="w-full py-2 px-3 rounded-xl bg-[#1e293b] hover:bg-[#334155] text-xs font-mono font-semibold text-[#cbd5e1] hover:text-white transition-colors flex items-center justify-between cursor-pointer">
                            <span>Settle All Pending Referrals</span>
                            <span class="text-[#34d399]">${{ number_format($totalPendingPayoutsCents / 100, 2) }}</span>
                        </button>
                        <button wire:click="setTab('system')" class="w-full py-2 px-3 rounded-xl bg-[#1e293b] hover:bg-[#334155] text-xs font-mono font-semibold text-[#cbd5e1] hover:text-white transition-colors flex items-center justify-between cursor-pointer">
                            <span>Transactional Email Diagnostics</span>
                            <span class="text-[#38bdf8]">Test ➔</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Outreach Pipeline Summary Banner -->
            <div class="rounded-2xl bg-gradient-to-r from-[#0e1626] via-[#131d31] to-[#0e1626] border border-[#1e293b] p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#10b981]/10 text-[#34d399] border border-[#10b981]/20 flex items-center justify-center text-lg shrink-0">
                        🎯
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-bold text-white">Creator Outreach Pipeline & Marketing CRM</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#10b981]/20 text-[#34d399] text-[10px] font-mono font-bold">{{ $totalProspectsCount }} Creators</span>
                        </div>
                        <p class="text-xs text-[#94a3b8] mt-0.5">
                            {{ $priorityACount }} Priority A targets across Gaming ({{ $gamingCount }}), Tech ({{ $techCount }}), Lifestyle ({{ $lifestyleCount }}), and Travel ({{ $travelCount }}).
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button wire:click="setTab('prospects')" class="px-4 py-2 rounded-xl bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                        <span>Open Outreach CRM</span>
                        <span>➔</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 2: CREATORS DIRECTORY -->
    @if($tab === 'creators')
        <div class="space-y-6">
            <!-- Filter & Search Controls -->
            <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="w-full sm:w-80 relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchCreators" 
                        placeholder="Search creators by name, handle, email..." 
                        class="w-full px-4 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-sm text-white placeholder-[#64748b] font-mono focus:outline-none focus:border-[#10b981]"
                    />
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select wire:model.live="filterPlatform" class="px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]">
                        <option value="all">All Platforms</option>
                        <option value="YouTube">YouTube</option>
                        <option value="Twitch">Twitch</option>
                        <option value="TikTok">TikTok</option>
                        <option value="Kick">Kick</option>
                        <option value="Instagram">Instagram</option>
                    </select>

                    <button wire:click="settleAllReferrals" class="px-4 py-2.5 rounded-xl bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-xs transition-colors shrink-0 cursor-pointer">
                        Settle All Payouts
                    </button>
                </div>
            </div>

            <!-- Creators Table -->
            <div class="rounded-2xl bg-[#0e1626] border border-[#1e293b] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-[#111827] text-[#94a3b8] uppercase tracking-wider border-b border-[#1e293b]">
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
                        <tbody class="divide-y divide-[#1e293b]">
                            @forelse($creators as $c)
                                <tr class="hover:bg-[#111827]/50 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-[#10b981]/20 text-[#34d399] font-mono font-bold text-sm flex items-center justify-center shrink-0">
                                                {{ substr($c->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-white text-sm font-sans">{{ $c->name }}</div>
                                                <div class="text-[#64748b] text-[11px]">{{ $c->handle }} · {{ $c->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-md bg-[#1e293b] text-[#cbd5e1] text-[11px] font-semibold">
                                            {{ $c->platform ?: 'YouTube' }}
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <a href="{{ url('/with/'.$c->slug) }}" target="_blank" class="text-[#38bdf8] hover:underline flex items-center gap-1">
                                            <span>/with/{{ $c->slug }}</span>
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </td>
                                    <td class="p-4 font-bold text-white">
                                        ${{ number_format(($c->min_seal_price_cents ?? 500) / 100, 2) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="px-2 py-0.5 rounded bg-[#1e293b] text-white font-bold">
                                            {{ $c->milestones_count }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="px-2 py-0.5 rounded bg-[#10b981]/20 text-[#34d399] font-bold">
                                            {{ $c->postcards_count }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right font-bold text-[#34d399]">
                                        ${{ number_format(($c->total_earnings_cents ?? 0) / 100, 2) }}
                                    </td>
                                    <td class="p-4 text-right">
                                        @if(($c->pending_earnings_cents ?? 0) > 0)
                                            <button wire:click="markCreatorPaid('{{ $c->id }}')" class="font-bold text-[#f59e0b] hover:text-[#fbbf24] hover:underline cursor-pointer">
                                                ${{ number_format(($c->pending_earnings_cents ?? 0) / 100, 2) }} (Settle)
                                            </button>
                                        @else
                                            <span class="text-[#64748b]">✓ Settled</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                wire:click="generateStudioLogin('{{ $c->id }}')" 
                                                class="px-2.5 py-1.5 rounded-lg bg-[#10b981]/20 text-[#34d399] hover:bg-[#10b981]/30 font-bold transition-colors cursor-pointer"
                                                title="Open Studio"
                                            >
                                                Studio ➔
                                            </button>
                                            <button 
                                                wire:click="openEditCreator('{{ $c->id }}')" 
                                                class="px-2 py-1.5 rounded-lg bg-[#1e293b] text-[#94a3b8] hover:text-white hover:bg-[#334155] transition-colors cursor-pointer"
                                                title="Edit Settings"
                                            >
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-8 text-center text-[#64748b]">
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

    <!-- TAB 3: FAN LETTERS & CAPSULES -->
    @if($tab === 'letters')
        <div class="space-y-6">
            <!-- Filter Controls -->
            <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="w-full sm:w-80 relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchLetters" 
                        placeholder="Search fan name, location, teaser, #..." 
                        class="w-full px-4 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-sm text-white placeholder-[#64748b] font-mono focus:outline-none focus:border-[#10b981]"
                    />
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select wire:model.live="filterLetterCreator" class="px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]">
                        <option value="all">All Destinations</option>
                        <option value="community">Community Vault</option>
                        @foreach($creators as $cr)
                            <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterLetterTier" class="px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]">
                        <option value="all">All Tiers</option>
                        <option value="founding">Founding Only</option>
                        <option value="archival">Archival Only</option>
                    </select>
                </div>
            </div>

            <!-- Letters Table -->
            <div class="rounded-2xl bg-[#0e1626] border border-[#1e293b] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-[#111827] text-[#94a3b8] uppercase tracking-wider border-b border-[#1e293b]">
                            <tr>
                                <th class="p-4">Capsule #</th>
                                <th class="p-4">Fan</th>
                                <th class="p-4">Target Creator / Milestone</th>
                                <th class="p-4">Teaser Preview</th>
                                <th class="p-4 text-center">Amount</th>
                                <th class="p-4 text-center">Tier</th>
                                <th class="p-4">Sealed At</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b]">
                            @forelse($letters as $l)
                                <tr class="hover:bg-[#111827]/50 transition-colors">
                                    <td class="p-4 font-bold text-[#34d399]">
                                        #{{ Capsule::formatNumber($l->number) }}
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-white text-sm font-sans">{{ $l->name }}</div>
                                        <div class="text-[#64748b] text-[11px]">{{ $l->location ?: 'Global' }} · {{ $l->envelope?->email }}</div>
                                    </td>
                                    <td class="p-4">
                                        @if($l->creator)
                                            <div class="font-bold text-[#38bdf8]">{{ $l->creator->name }}</div>
                                            <div class="text-[#64748b] text-[11px]">🎯 {{ $l->milestone?->title ?? 'Milestone' }}</div>
                                        @else
                                            <span class="text-[#94a3b8]">FanVault Community</span>
                                        @endif
                                    </td>
                                    <td class="p-4 max-w-xs">
                                        <div class="truncate italic text-white/90">
                                            “{{ $l->teaser ?: 'Wish you were here.' }}”
                                        </div>
                                    </td>
                                    <td class="p-4 text-center font-bold text-[#10b981]">
                                        ${{ number_format(($l->referral?->amount_cents ?? 500) / 100, 2) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $l->founding ? 'bg-[#fde047]/20 text-[#fde047] border border-[#fde047]/30' : 'bg-[#10b981]/20 text-[#34d399]' }}">
                                            {{ $l->founding ? '🏛️ Founding' : '🌿 Archival' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-[#64748b]">
                                        {{ $l->sealed_at?->format('M d, Y H:i') ?? $l->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button 
                                                wire:click="inspectLetter('{{ $l->id }}')" 
                                                class="px-2.5 py-1.5 rounded-lg bg-[#10b981]/20 text-[#34d399] hover:bg-[#10b981]/30 font-bold transition-colors cursor-pointer"
                                            >
                                                Inspect
                                            </button>
                                            <a 
                                                href="{{ route('message', $l) }}" 
                                                target="_blank" 
                                                class="px-2 py-1.5 rounded-lg bg-[#1e293b] text-[#94a3b8] hover:text-white transition-colors"
                                                title="View Public Pass"
                                            >
                                                ↗
                                            </a>
                                            <button 
                                                wire:click="deletePostcard('{{ $l->id }}')" 
                                                wire:confirm="Are you sure you want to delete/archive capsule #{{ $l->number }}?" 
                                                class="px-2 py-1.5 rounded-lg bg-[#ef4444]/10 text-[#f87171] hover:bg-[#ef4444]/20 transition-colors cursor-pointer"
                                                title="Delete Capsule"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-[#64748b]">
                                        No fan letters matching your search.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 4: FINANCIAL LEDGER & PAYOUTS -->
    @if($tab === 'payments')
        <div class="space-y-6">
            <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono font-bold text-white uppercase">Referral Payout Ledger</span>
                    <span class="px-2 py-0.5 rounded-md bg-[#10b981]/20 text-[#34d399] font-mono text-xs font-bold">
                        80% Creator / 20% Platform
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <select wire:model.live="filterPaymentStatus" class="px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]">
                        <option value="all">All Payout Statuses</option>
                        <option value="pending">Pending Settlement</option>
                        <option value="paid">Settled / Paid</option>
                    </select>

                    <button wire:click="settleAllReferrals" class="px-3.5 py-2 rounded-xl bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-xs transition-colors cursor-pointer">
                        Settle All Pending
                    </button>
                </div>
            </div>

            <div class="rounded-2xl bg-[#0e1626] border border-[#1e293b] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-[#111827] text-[#94a3b8] uppercase tracking-wider border-b border-[#1e293b]">
                            <tr>
                                <th class="p-4">Transaction ID</th>
                                <th class="p-4">Postcard</th>
                                <th class="p-4">Beneficiary Creator</th>
                                <th class="p-4 text-right">Gross Amount</th>
                                <th class="p-4 text-right">Platform (20%)</th>
                                <th class="p-4 text-right">Creator Share (80%)</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-right">Created At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b]">
                            @forelse($referrals as $ref)
                                <tr class="hover:bg-[#111827]/50 transition-colors">
                                    <td class="p-4 font-bold text-[#94a3b8]">
                                        ref_{{ $ref->id }}
                                    </td>
                                    <td class="p-4">
                                        @if($ref->postcard)
                                            <a href="{{ route('message', $ref->postcard) }}" target="_blank" class="text-[#38bdf8] hover:underline">
                                                #{{ Capsule::formatNumber($ref->postcard->number) }} ({{ $ref->postcard->name }})
                                            </a>
                                        @else
                                            <span class="text-[#64748b]">N/A</span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-bold text-white">
                                        {{ $ref->creator?->name ?? 'Unknown Creator' }}
                                    </td>
                                    <td class="p-4 text-right font-bold text-white">
                                        ${{ number_format($ref->amount_cents / 100, 2) }}
                                    </td>
                                    <td class="p-4 text-right font-bold text-[#34d399]">
                                        ${{ number_format(($ref->amount_cents - $ref->cut_cents) / 100, 2) }}
                                    </td>
                                    <td class="p-4 text-right font-bold text-[#38bdf8]">
                                        ${{ number_format($ref->cut_cents / 100, 2) }}
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $ref->status === 'paid' ? 'bg-[#10b981]/20 text-[#34d399]' : 'bg-[#f59e0b]/20 text-[#fbbf24]' }}">
                                            {{ strtoupper($ref->status) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right text-[#64748b]">
                                        {{ $ref->created_at->format('M d, Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-[#64748b]">
                                        No financial ledger records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 5: MILESTONES -->
    @if($tab === 'milestones')
        <div class="space-y-6">
            <div class="rounded-2xl bg-[#0e1626] border border-[#1e293b] p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-[#1e293b] pb-3">
                    <h2 class="text-sm font-mono font-bold text-white uppercase tracking-wider">
                        Active Creator Milestones Directory
                    </h2>
                    <span class="text-xs font-mono text-[#34d399]">
                        Total Active: {{ $milestones->where('is_active', true)->count() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($milestones as $m)
                        <div class="p-5 rounded-xl bg-[#111827] border border-[#1e293b] space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono {{ $m->is_active ? 'bg-[#10b981]/20 text-[#34d399]' : 'bg-[#1e293b] text-[#94a3b8]' }}">
                                        {{ $m->is_active ? '● LIVE TARGET' : 'PAST / INACTIVE' }}
                                    </span>
                                    <h3 class="font-bold text-white text-sm mt-1.5">{{ $m->title }}</h3>
                                    <p class="text-xs text-[#38bdf8] font-mono">@ {{ $m->creator?->name }}</p>
                                </div>
                            </div>

                            @if($m->description)
                                <p class="text-xs text-[#94a3b8] italic">“{{ $m->description }}”</p>
                            @endif

                            <div class="pt-2 border-t border-[#1e293b] flex items-center justify-between text-xs font-mono text-[#64748b]">
                                <span>Unlock Date:</span>
                                <span class="text-white font-bold">{{ $m->formattedUnlockDate() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 py-8 text-center text-xs font-mono text-[#64748b]">
                            No milestones created yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 6: SYSTEM & DIAGNOSTICS -->
    @if($tab === 'system')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- System Telemetry Card -->
            <div class="p-6 rounded-2xl bg-[#0e1626] border border-[#1e293b] space-y-4">
                <h2 class="text-sm font-mono font-bold text-white uppercase tracking-wider border-b border-[#1e293b] pb-3">
                    System Telemetry & Architecture
                </h2>

                <div class="space-y-3 font-mono text-xs">
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">Environment</span>
                        <span class="px-2 py-0.5 rounded bg-[#10b981]/20 text-[#34d399] font-bold uppercase">{{ app()->environment() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">PHP Version</span>
                        <span class="text-white font-bold">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">Laravel Version</span>
                        <span class="text-white font-bold">{{ app()->version() }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">Active Mail Transport</span>
                        <span class="px-2 py-0.5 rounded bg-[#38bdf8]/20 text-[#38bdf8] font-bold">{{ config('mail.default') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">Default From Address</span>
                        <span class="text-white font-bold">{{ config('mail.from.address') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">Stripe API Status</span>
                        <span class="text-white font-bold">{{ config('services.stripe.secret') ? 'Configured (Live)' : 'Demo Sandbox Mode' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-[#1e293b]/60">
                        <span class="text-[#94a3b8]">Database Driver</span>
                        <span class="text-white font-bold">{{ config('database.default') }}</span>
                    </div>
                </div>
            </div>

            <!-- Transactional Email Dispatcher Test -->
            <div class="p-6 rounded-2xl bg-[#0e1626] border border-[#1e293b] space-y-4">
                <h2 class="text-sm font-mono font-bold text-white uppercase tracking-wider border-b border-[#1e293b] pb-3">
                    Transactional Email Live Tester
                </h2>

                <p class="text-xs text-[#94a3b8]">
                    Send a test branded transactional email to any address using the current transport driver (<code class="text-[#34d399]">{{ config('mail.default') }}</code>).
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-mono text-[#94a3b8] mb-1">Destination Email Address</label>
                        <input 
                            type="email" 
                            wire:model="testEmailAddress" 
                            placeholder="you@example.com" 
                            class="w-full px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-white text-xs font-mono focus:outline-none focus:border-[#10b981]"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-mono text-[#94a3b8] mb-1">Email Template Type</label>
                        <select wire:model="testEmailType" class="w-full px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-white text-xs font-mono focus:outline-none focus:border-[#10b981]">
                            <option value="fan">Fan Capsule Sealed Confirmation & Receipt</option>
                            <option value="creator">Creator New Letter & Earnings Alert</option>
                            <option value="login">Creator Studio Magic Login Link</option>
                        </select>
                    </div>

                    <button 
                        wire:click="sendTestEmail" 
                        class="w-full py-2.5 px-4 rounded-xl bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-xs transition-colors flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>Dispatch Test Email</span>
                        <span>➔</span>
                    </button>

                    @if($testEmailResult)
                        <div class="p-3.5 rounded-xl {{ $testEmailSuccess ? 'bg-[#064e3b]/30 border border-[#059669]/40 text-[#34d399]' : 'bg-[#7f1d1d]/30 border border-[#dc2626]/40 text-[#fca5a5]' }} text-xs font-mono">
                            {{ $testEmailResult }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- TAB 6: CREATOR OUTREACH CRM & PROSPECT PIPELINE -->
    @if($tab === 'prospects')
        <div class="space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#10b981] animate-pulse"></span>
                        <h1 class="text-xl sm:text-2xl font-bold font-mono text-white tracking-tight">
                            Creator Outreach Pipeline & CRM
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#10b981]/20 text-[#34d399] font-mono text-xs font-bold border border-[#10b981]/30">
                            500 Potential Creators
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-[#94a3b8] mt-1">
                        Seeded database mirroring 500 creator targets with tailored outreach angles, email subjects, and contact routes for marketing acquisition.
                    </p>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <button 
                        wire:click="exportProspectsCsv" 
                        class="px-4 py-2 rounded-xl bg-[#1e293b] hover:bg-[#334155] border border-[#334155] text-xs font-mono font-bold text-white transition-colors flex items-center gap-2 cursor-pointer shadow-xs"
                    >
                        <svg class="w-4 h-4 text-[#34d399]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Export CSV (500)</span>
                    </button>
                </div>
            </div>

            <!-- 5 Outreach Metric Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] hover:border-[#10b981]/40 transition-colors">
                    <div class="text-[11px] font-mono text-[#64748b] uppercase">Total Pipeline</div>
                    <div class="text-2xl font-extrabold font-mono text-white mt-1">{{ number_format($totalProspectsCount) }}</div>
                    <div class="text-[10px] text-[#94a3b8] font-mono mt-1">Seeded Prospects</div>
                </div>

                <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] hover:border-[#34d399]/40 transition-colors">
                    <div class="text-[11px] font-mono text-[#34d399] uppercase">Priority A Tier</div>
                    <div class="text-2xl font-extrabold font-mono text-[#34d399] mt-1">{{ number_format($priorityACount) }}</div>
                    <div class="text-[10px] text-[#94a3b8] font-mono mt-1">{{ round(($priorityACount / max(1, $totalProspectsCount)) * 100) }}% High Leverage</div>
                </div>

                <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] hover:border-[#38bdf8]/40 transition-colors">
                    <div class="text-[11px] font-mono text-[#38bdf8] uppercase">Email Enriched</div>
                    <div class="text-2xl font-extrabold font-mono text-[#38bdf8] mt-1">{{ number_format($hasEmailCount) }}</div>
                    <div class="text-[10px] text-[#94a3b8] font-mono mt-1">Ready for Direct Send</div>
                </div>

                <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] hover:border-[#fbbf24]/40 transition-colors">
                    <div class="text-[11px] font-mono text-[#fbbf24] uppercase">Pipeline Active</div>
                    <div class="text-2xl font-extrabold font-mono text-[#fbbf24] mt-1">{{ number_format($contactedCount) }}</div>
                    <div class="text-[10px] text-[#94a3b8] font-mono mt-1">Contacted / In Progress</div>
                </div>

                <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] hover:border-[#a855f7]/40 transition-colors col-span-2 sm:col-span-1">
                    <div class="text-[11px] font-mono text-[#a855f7] uppercase">Onboarded</div>
                    <div class="text-2xl font-extrabold font-mono text-[#a855f7] mt-1">{{ number_format($onboardedCount) }}</div>
                    <div class="text-[10px] text-[#94a3b8] font-mono mt-1">Vaults Active</div>
                </div>
            </div>

            <!-- Filters & Search Controls -->
            <div class="p-4 rounded-2xl bg-[#0e1626] border border-[#1e293b] space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search Input (2 cols on large) -->
                    <div class="lg:col-span-2 relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchProspects" 
                            placeholder="Search creator, angle, subject, or email..." 
                            class="w-full px-4 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-white placeholder-[#64748b] focus:outline-none focus:border-[#10b981]"
                        />
                    </div>

                    <!-- Speciality Filter -->
                    <div>
                        <select 
                            wire:model.live="filterProspectSpeciality" 
                            class="w-full px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]"
                        >
                            <option value="all">All Niches ({{ $totalProspectsCount }})</option>
                            <option value="Gaming">Gaming ({{ $gamingCount }})</option>
                            <option value="Technology">Technology ({{ $techCount }})</option>
                            <option value="Lifestyle">Lifestyle ({{ $lifestyleCount }})</option>
                            <option value="Travel">Travel ({{ $travelCount }})</option>
                        </select>
                    </div>

                    <!-- Priority Filter -->
                    <div>
                        <select 
                            wire:model.live="filterProspectPriority" 
                            class="w-full px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]"
                        >
                            <option value="all">All Priorities</option>
                            <option value="A">Priority A (High)</option>
                            <option value="B">Priority B</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <select 
                            wire:model.live="filterProspectStatus" 
                            class="w-full px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]"
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

                    <!-- Email Filter & Per Page -->
                    <div class="flex items-center gap-2">
                        <select 
                            wire:model.live="filterProspectEmail" 
                            class="w-full px-3 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]"
                        >
                            <option value="all">All Email Status</option>
                            <option value="has_email">Has Email Only</option>
                            <option value="needs_enrichment">Needs Enrichment</option>
                        </select>

                        <select 
                            wire:model.live="prospectsPerPage" 
                            class="w-20 px-2 py-2.5 rounded-xl bg-[#090d16] border border-[#1e293b] text-xs font-mono text-[#cbd5e1] focus:outline-none focus:border-[#10b981]"
                            title="Rows per page"
                        >
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <!-- Quick Active Filter Tags -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-[#1e293b]/60 text-xs font-mono text-[#64748b]">
                    <div class="flex flex-wrap items-center gap-2">
                        <span>Showing {{ $filteredProspectsCount }} of {{ $totalProspectsCount }} prospects</span>
                        @if($searchProspects || $filterProspectSpeciality !== 'all' || $filterProspectPriority !== 'all' || $filterProspectStatus !== 'all' || $filterProspectEmail !== 'all')
                            <button 
                                wire:click="$set('searchProspects', ''); $set('filterProspectSpeciality', 'all'); $set('filterProspectPriority', 'all'); $set('filterProspectStatus', 'all'); $set('filterProspectEmail', 'all');" 
                                class="text-[#34d399] hover:underline cursor-pointer"
                            >
                                (Reset Filters)
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span>Page {{ $prospectsPage }} of {{ $totalProspectsPages }}</span>
                    </div>
                </div>
            </div>

            <!-- High-Density Mirroring Table -->
            <div class="rounded-2xl bg-[#0e1626] border border-[#1e293b] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-[#111827] text-[#94a3b8] uppercase text-[11px] border-b border-[#1e293b]">
                            <tr>
                                <th class="p-3.5 w-14 text-center">#</th>
                                <th class="p-3.5 min-w-[160px]">Creator</th>
                                <th class="p-3.5 w-28">Speciality</th>
                                <th class="p-3.5 w-16 text-center">Priority</th>
                                <th class="p-3.5 min-w-[180px]">Email & Contact</th>
                                <th class="p-3.5 min-w-[260px]">Outreach Angle & Personalisation</th>
                                <th class="p-3.5 min-w-[220px]">Email Subject</th>
                                <th class="p-3.5 w-36">Status</th>
                                <th class="p-3.5 w-24 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b]">
                            @forelse($prospects as $p)
                                <tr class="hover:bg-[#111827]/60 transition-colors group">
                                    <!-- Prospect # -->
                                    <td class="p-3.5 text-center text-[#94a3b8] font-bold">
                                        #{{ $p->prospect_number }}
                                    </td>

                                    <!-- Creator Name & URL -->
                                    <td class="p-3.5">
                                        <div class="font-bold text-white text-sm group-hover:text-[#34d399] transition-colors flex items-center gap-1.5">
                                            <span>{{ $p->creator }}</span>
                                            @if($p->contact_url)
                                                <a 
                                                    href="{{ $p->contact_url }}" 
                                                    target="_blank" 
                                                    class="text-[#64748b] hover:text-[#38bdf8] transition-colors"
                                                    title="Open YouTube / Contact Search"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-[#64748b] truncate mt-0.5">
                                            {{ $p->contact_type ?: 'YouTube Business Enquiry' }}
                                        </div>
                                    </td>

                                    <!-- Speciality -->
                                    <td class="p-3.5">
                                        <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $p->specialityBadgeClass() }}">
                                            {{ $p->speciality }}
                                        </span>
                                    </td>

                                    <!-- Recommended Priority -->
                                    <td class="p-3.5 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded-md text-[11px] font-bold border {{ $p->priorityBadgeClass() }}">
                                            {{ $p->recommended_priority }}
                                        </span>
                                    </td>

                                    <!-- Email & Status -->
                                    <td class="p-3.5">
                                        @if($p->hasEmail())
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[#34d399] font-bold truncate max-w-[150px] select-all">
                                                    {{ $p->effectiveEmail() }}
                                                </span>
                                                <button 
                                                    type="button" 
                                                    onclick="navigator.clipboard.writeText('{{ $p->effectiveEmail() }}'); alert('Copied {{ $p->effectiveEmail() }}');" 
                                                    class="text-[#64748b] hover:text-white"
                                                    title="Copy Email"
                                                >
                                                    📋
                                                </button>
                                            </div>
                                            <div class="text-[10px] text-[#94a3b8]">Verified Address</div>
                                        @else
                                            <div class="text-[#f59e0b] text-[11px] flex items-center gap-1">
                                                <span>Needs Enrichment</span>
                                            </div>
                                            <div class="text-[10px] text-[#64748b] truncate max-w-[170px]" title="{{ $p->email_status }}">
                                                {{ Str::limit($p->email_status, 28) }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Primary Outreach Angle & Note -->
                                    <td class="p-3.5 text-[#cbd5e1] max-w-xs">
                                        <div class="line-clamp-2 text-xs leading-relaxed" title="{{ $p->primary_outreach_angle }}">
                                            {{ $p->primary_outreach_angle }}
                                        </div>
                                        @if($p->personalisation_note && $p->personalisation_note !== $p->primary_outreach_angle)
                                            <div class="text-[10px] text-[#64748b] italic mt-1 truncate" title="{{ $p->personalisation_note }}">
                                                Note: {{ $p->personalisation_note }}
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Email Subject -->
                                    <td class="p-3.5 max-w-xs">
                                        <div class="flex items-center justify-between gap-1 bg-[#090d16] p-1.5 rounded-lg border border-[#1e293b]">
                                            <span class="text-[#94a3b8] text-[11px] truncate select-all" title="{{ $p->email_subject }}">
                                                {{ $p->email_subject }}
                                            </span>
                                            <button 
                                                type="button" 
                                                onclick="navigator.clipboard.writeText('{{ addslashes($p->email_subject) }}'); alert('Copied subject line!');" 
                                                class="text-[#64748b] hover:text-[#34d399] shrink-0 text-xs px-1"
                                                title="Copy Subject"
                                            >
                                                📋
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Status (Live Dropdown) -->
                                    <td class="p-3.5">
                                        <select 
                                            wire:change="updateProspectStatus('{{ $p->id }}', $event.target.value)" 
                                            class="w-full px-2 py-1.5 rounded-lg bg-[#090d16] border border-[#1e293b] text-[11px] font-bold focus:outline-none focus:border-[#10b981] {{ $p->statusBadgeClass() }}"
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
                                    <td class="p-3.5 text-right">
                                        <button 
                                            wire:click="inspectProspect('{{ $p->id }}')" 
                                            class="px-2.5 py-1.5 rounded-lg bg-[#10b981]/20 hover:bg-[#10b981]/30 text-[#34d399] font-bold transition-colors cursor-pointer text-xs"
                                        >
                                            Inspect ➔
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="p-12 text-center text-[#64748b]">
                                        <div class="text-base mb-1">🔍 No creator prospects found matching your search.</div>
                                        <p class="text-xs">Try clearing your filters or search query.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($totalProspectsPages > 1)
                    <div class="p-4 bg-[#111827] border-t border-[#1e293b] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-mono">
                        <div class="text-[#94a3b8]">
                            Showing {{ ($prospectsPage - 1) * $prospectsPerPage + 1 }} - {{ min($filteredProspectsCount, $prospectsPage * $prospectsPerPage) }} of {{ $filteredProspectsCount }} prospects
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button 
                                wire:click="previousProspectsPage" 
                                @if($prospectsPage <= 1) disabled @endif
                                class="px-3 py-1.5 rounded-lg bg-[#1e293b] text-[#cbd5e1] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                Previous
                            </button>

                            <div class="px-3 py-1.5 rounded-lg bg-[#090d16] text-[#34d399] font-bold border border-[#1e293b]">
                                {{ $prospectsPage }} / {{ $totalProspectsPages }}
                            </div>

                            <button 
                                wire:click="nextProspectsPage({{ $totalProspectsPages }})" 
                                @if($prospectsPage >= $totalProspectsPages) disabled @endif
                                class="px-3 py-1.5 rounded-lg bg-[#1e293b] text-[#cbd5e1] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- INSPECT & OUTREACH PROSPECT MODAL -->
    @if($inspectedProspect)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-3xl bg-[#0e1626] border border-[#1e293b] rounded-2xl p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-[#1e293b] pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold text-[#34d399]">
                                PROSPECT #{{ $inspectedProspect->prospect_number }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $inspectedProspect->priorityBadgeClass() }}">
                                Priority {{ $inspectedProspect->recommended_priority }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $inspectedProspect->specialityBadgeClass() }}">
                                {{ $inspectedProspect->speciality }}
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold text-white mt-1.5 flex items-center gap-2">
                            <span>{{ $inspectedProspect->creator }}</span>
                            @if($inspectedProspect->contact_url)
                                <a 
                                    href="{{ $inspectedProspect->contact_url }}" 
                                    target="_blank" 
                                    class="text-xs px-2.5 py-1 rounded-lg bg-[#1e293b] text-[#38bdf8] hover:bg-[#334155] font-mono font-medium flex items-center gap-1"
                                >
                                    <span>Search Channel</span>
                                    <span>↗</span>
                                </a>
                            @endif
                        </h2>
                    </div>
                    <button wire:click="closeInspectProspect" class="text-gray-400 hover:text-white text-lg font-mono cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Complete 13-Column Breakdown Mirroring Dataset -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs font-mono">
                    <div class="p-3 rounded-xl bg-[#111827] border border-[#1e293b]">
                        <span class="text-[#64748b] block text-[10px] uppercase">Primary Outreach Angle</span>
                        <p class="text-[#e2e8f0] font-sans text-xs mt-1 leading-relaxed">
                            {{ $inspectedProspect->primary_outreach_angle }}
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-[#111827] border border-[#1e293b]">
                        <span class="text-[#64748b] block text-[10px] uppercase">Personalisation Note</span>
                        <p class="text-[#cbd5e1] font-sans text-xs mt-1 leading-relaxed">
                            {{ $inspectedProspect->personalisation_note ?: 'No extra personalisation notes.' }}
                        </p>
                    </div>

                    <div class="p-3 rounded-xl bg-[#111827] border border-[#1e293b]">
                        <span class="text-[#64748b] block text-[10px] uppercase">Contact Route & Type</span>
                        <div class="text-white font-bold mt-1">{{ $inspectedProspect->contact_type ?: 'YouTube Business Enquiry' }}</div>
                        <div class="text-[10px] text-[#94a3b8] mt-0.5">{{ $inspectedProspect->email_status }}</div>
                    </div>

                    <div class="p-3 rounded-xl bg-[#111827] border border-[#1e293b]">
                        <span class="text-[#64748b] block text-[10px] uppercase">Email Subject Line</span>
                        <div class="flex items-center justify-between gap-1 mt-1">
                            <span class="text-[#34d399] font-bold truncate select-all">{{ $inspectedProspect->email_subject }}</span>
                            <button 
                                type="button" 
                                onclick="navigator.clipboard.writeText('{{ addslashes($inspectedProspect->email_subject) }}'); alert('Subject copied!');" 
                                class="text-xs px-2 py-0.5 rounded bg-[#1e293b] text-white hover:bg-[#334155]"
                            >
                                Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ready-to-Send Outreach Pitch Box -->
                <div class="p-4 rounded-xl bg-[#090d16] border border-[#10b981]/30 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-mono uppercase text-[#34d399] font-bold flex items-center gap-1.5">
                            <span>⚡ Ready-to-Send Pitch Draft</span>
                        </span>
                        <button 
                            type="button" 
                            onclick="navigator.clipboard.writeText('Subject: {{ addslashes($inspectedProspect->email_subject) }}\n\nHi {{ addslashes(explode(' ', $inspectedProspect->creator)[0]) }},\n\n{{ addslashes($inspectedProspect->primary_outreach_angle) }}\n\nWe built FanVault (https://getfanvault.com) — digital time capsules where fans seal messages, memories, and predictions for your next milestone, unlocked live on stream.\n\nWould love to set up a private capsule for your next milestone: https://getfanvault.com/with/{{ Str::slug($inspectedProspect->creator) }}\n\nBest,\nFanVault Creator Partnerships'); alert('Complete outreach pitch copied to clipboard!');" 
                            class="px-2.5 py-1 rounded-lg bg-[#10b981] hover:bg-[#059669] text-[#090d16] font-mono font-bold text-xs cursor-pointer transition-colors"
                        >
                            📋 Copy Full Email Pitch
                        </button>
                    </div>

                    <div class="bg-[#111827] p-3.5 rounded-lg text-xs font-mono text-[#cbd5e1] space-y-2 select-all leading-relaxed">
                        <div class="text-[#94a3b8] font-bold">Subject: {{ $inspectedProspect->email_subject }}</div>
                        <div class="text-white">Hi {{ explode(' ', $inspectedProspect->creator)[0] }},</div>
                        <div>{{ $inspectedProspect->primary_outreach_angle }}</div>
                        <div class="text-[#94a3b8]">We built FanVault (https://getfanvault.com) — digital time capsules where your fans seal messages, memories, and predictions for your upcoming milestone, opened live on your reveal stream.</div>
                        <div class="text-[#34d399]">Would love to get a time capsule opened for your community: https://getfanvault.com/with/{{ Str::slug($inspectedProspect->creator) }}</div>
                    </div>
                </div>

                <!-- Update Prospect Details Form -->
                <div class="p-4 rounded-xl bg-[#111827] border border-[#1e293b] space-y-3">
                    <div class="text-xs font-mono font-bold text-white uppercase tracking-wider">
                        Update Marketing & Enrichment Details
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-mono text-[#94a3b8] mb-1">Direct Contact Email</label>
                            <input 
                                type="email" 
                                wire:model="editProspectEmail" 
                                placeholder="creator@business.com" 
                                class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white text-xs font-mono focus:outline-none focus:border-[#10b981]"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-[#94a3b8] mb-1">Pipeline Status</label>
                            <select 
                                wire:model="editProspectStatus" 
                                class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white text-xs font-mono focus:outline-none focus:border-[#10b981]"
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
                        <label class="block text-xs font-mono text-[#94a3b8] mb-1">Internal Notes & History</label>
                        <textarea 
                            wire:model="editProspectNotes" 
                            rows="2" 
                            placeholder="Add notes (e.g., outreach date, channel response, manager contact details)..." 
                            class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white text-xs font-mono focus:outline-none focus:border-[#10b981]"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button 
                            type="button" 
                            wire:click="updateProspectStatus('{{ $inspectedProspect->id }}', 'Contacted')" 
                            class="px-3 py-1.5 rounded-lg bg-[#f59e0b]/20 hover:bg-[#f59e0b]/30 text-[#fbbf24] text-xs font-mono font-bold transition-colors cursor-pointer"
                        >
                            ✓ Mark Contacted (Stamp Now)
                        </button>

                        <div class="flex items-center gap-2">
                            <button 
                                wire:click="closeInspectProspect" 
                                class="px-3.5 py-1.5 rounded-xl bg-[#1e293b] text-xs font-mono text-[#cbd5e1] hover:text-white"
                            >
                                Close
                            </button>
                            <button 
                                wire:click="saveProspectDetails" 
                                class="px-4 py-1.5 rounded-xl bg-[#10b981] hover:bg-[#059669] text-xs font-mono font-bold text-[#090d16] transition-colors cursor-pointer"
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- INSPECT LETTER MODAL -->
    @if($inspectedPostcard)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-2xl bg-[#0e1626] border border-[#1e293b] rounded-2xl p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between border-b border-[#1e293b] pb-4">
                    <div>
                        <span class="font-mono text-xs font-bold text-[#34d399]">
                            CAPSULE NO. #{{ Capsule::formatNumber($inspectedPostcard->number) }}
                        </span>
                        <h2 class="text-xl font-bold text-white mt-1">
                            Letter from {{ $inspectedPostcard->name }}
                        </h2>
                        <div class="text-xs text-[#64748b] font-mono">
                            Sealed: {{ $inspectedPostcard->sealed_at?->format('F d, Y · H:i:s T') ?? $inspectedPostcard->created_at->format('F d, Y') }}
                        </div>
                    </div>
                    <button wire:click="closeInspect" class="text-gray-400 hover:text-white text-lg font-mono cursor-pointer">
                        ✕
                    </button>
                </div>

                <!-- Teaser & Secret Letter -->
                <div class="space-y-4">
                    <div class="p-3.5 rounded-xl bg-[#111827] border border-[#1e293b]">
                        <span class="text-[11px] font-mono uppercase text-[#64748b] font-bold">Public Teaser</span>
                        <p class="italic text-sm text-[#cbd5e1] mt-1">
                            “{{ $inspectedPostcard->teaser }}”
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-[#090d16] border border-[#10b981]/30">
                        <span class="text-[11px] font-mono uppercase text-[#34d399] font-bold">Private Sealed Letter</span>
                        <p class="text-sm text-white mt-2 font-serif leading-relaxed whitespace-pre-wrap">
                            {{ $inspectedPostcard->envelope?->letter ?: 'No full letter content recorded.' }}
                        </p>
                    </div>

                    @if($inspectedPostcard->envelope?->photo_path)
                        <div class="p-3 rounded-xl bg-[#111827] border border-[#1e293b]">
                            <span class="text-[11px] font-mono uppercase text-[#64748b] font-bold mb-2 block">Attached Fan Photo</span>
                            <img src="{{ asset('storage/' . $inspectedPostcard->envelope->photo_path) }}" alt="Attached Portrait" class="max-h-48 rounded-lg object-cover" />
                        </div>
                    @endif

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                        <div class="p-3 rounded-lg bg-[#111827]">
                            <span class="text-[#64748b] block">Fan Email</span>
                            <span class="text-white font-bold">{{ $inspectedPostcard->envelope?->email ?: 'N/A' }}</span>
                        </div>
                        <div class="p-3 rounded-lg bg-[#111827]">
                            <span class="text-[#64748b] block">Contribution Amount</span>
                            <span class="text-[#10b981] font-bold">${{ number_format(($inspectedPostcard->referral?->amount_cents ?? 500) / 100, 2) }}</span>
                        </div>
                        <div class="p-3 rounded-lg bg-[#111827]">
                            <span class="text-[#64748b] block">Destination Creator</span>
                            <span class="text-[#38bdf8] font-bold">{{ $inspectedPostcard->creator?->name ?? 'Community Vault' }}</span>
                        </div>
                        <div class="p-3 rounded-lg bg-[#111827]">
                            <span class="text-[#64748b] block">Milestone Target</span>
                            <span class="text-white font-bold">{{ $inspectedPostcard->milestone?->title ?? 'General Milestone' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-[#1e293b] flex items-center justify-between">
                    <a href="{{ route('message', $inspectedPostcard) }}" target="_blank" class="text-xs font-mono text-[#38bdf8] hover:underline">
                        Open Public Keepsake Pass ↗
                    </a>
                    <button wire:click="closeInspect" class="px-4 py-2 rounded-xl bg-[#1e293b] hover:bg-[#334155] text-xs font-mono text-white transition-colors cursor-pointer">
                        Close Inspector
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- EDIT CREATOR MODAL -->
    @if($editCreatorId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
            <div class="w-full max-w-md bg-[#0e1626] border border-[#1e293b] rounded-2xl p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-[#1e293b] pb-3">
                    <h3 class="text-sm font-mono font-bold text-white uppercase">Edit Creator Vault Settings</h3>
                    <button wire:click="closeEditCreator" class="text-gray-400 hover:text-white font-mono cursor-pointer">✕</button>
                </div>

                <div class="space-y-3 font-mono text-xs">
                    <div>
                        <label class="block text-[#94a3b8] mb-1">Creator Name</label>
                        <input type="text" wire:model="editCreatorName" class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white focus:outline-none focus:border-[#10b981]" />
                    </div>

                    <div>
                        <label class="block text-[#94a3b8] mb-1">Handle</label>
                        <input type="text" wire:model="editCreatorHandle" class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white focus:outline-none focus:border-[#10b981]" />
                    </div>

                    <div>
                        <label class="block text-[#94a3b8] mb-1">Platform</label>
                        <select wire:model="editCreatorPlatform" class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white focus:outline-none focus:border-[#10b981]">
                            <option value="YouTube">YouTube</option>
                            <option value="Twitch">Twitch</option>
                            <option value="TikTok">TikTok</option>
                            <option value="Kick">Kick</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Podcast">Podcast</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[#94a3b8] mb-1">Minimum Seal Price ($ USD)</label>
                        <input type="number" min="1" max="100" wire:model="editCreatorMinPrice" class="w-full px-3 py-2 rounded-xl bg-[#090d16] border border-[#1e293b] text-white focus:outline-none focus:border-[#10b981]" />
                    </div>
                </div>

                <div class="pt-3 border-t border-[#1e293b] flex items-center justify-end gap-2">
                    <button wire:click="closeEditCreator" class="px-3.5 py-2 rounded-xl bg-[#1e293b] text-xs font-mono text-[#cbd5e1] hover:text-white">
                        Cancel
                    </button>
                    <button wire:click="saveCreator" class="px-4 py-2 rounded-xl bg-[#10b981] hover:bg-[#059669] text-xs font-mono font-bold text-[#090d16] transition-colors cursor-pointer">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
