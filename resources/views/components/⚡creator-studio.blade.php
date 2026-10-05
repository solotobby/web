<?php

use App\Models\Creator;
use App\Models\Milestone;
use App\Support\Capsule;
use App\Services\CreatorAuthService;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Creator Studio & Live Stream Reader — FanVault')]
class extends Component
{
    public string $tab = 'overview';

    public string $newMilestoneTitle = '';
    public string $newMilestoneDate = '';
    public string $newMilestoneDesc = '';
    public bool $newMilestoneActive = false;
    public string $milestoneSuccess = '';
    public string $milestoneError = '';
    public string $selectedMilestoneFilter = 'all';

    public string $searchLetters = '';
    public string $filterLetterMilestone = 'all';

    public string $editName = '';
    public string $editHandle = '';
    public string $editPlatform = 'YouTube';
    public string $editBio = '';
    public int $editMinPrice = 5;
    public string $settingsSuccess = '';
    public string $settingsError = '';

    public function rendering($view): void
    {
        $creator = app(CreatorAuthService::class)->current();
        if ($creator) {
            $view->layout('layouts.studio');
            $view->layoutData([
                'title' => $creator->name . '’s Studio — FanVault',
            ]);
        } else {
            $view->layout('layouts.app');
            $view->layoutData([
                'title' => 'Creator Studio & Live Stream Reader — FanVault',
            ]);
        }
    }

    public function mount(CreatorAuthService $auth): void
    {
        $creator = $auth->current();
        if ($creator && $creator->needsOnboarding()) {
            $this->redirect(route('creators.onboard'), navigate: true);
        }

        if ($requestedTab = request()->query('tab')) {
            if (in_array($requestedTab, ['overview', 'milestones', 'letters', 'stream', 'earnings', 'settings'], true)) {
                $this->tab = $requestedTab;
            }
        }

        if (! $this->newMilestoneDate) {
            $this->newMilestoneDate = now()->addMonths(6)->format('Y-m-d');
        }

        if ($creator) {
            $this->editName = $creator->name;
            $this->editHandle = ltrim($creator->handle ?: $creator->slug, '@');
            $this->editPlatform = $creator->platform ?: 'YouTube';
            $this->editBio = $creator->bio ?: '';
            $this->editMinPrice = (int) ($creator->minPriceDollars() ?: 5);
        }
    }

    public function setTab(string $newTab): void
    {
        if (in_array($newTab, ['overview', 'milestones', 'letters', 'stream', 'earnings', 'settings'], true)) {
            $this->tab = $newTab;
        }
    }

    public function updateSettings(CreatorAuthService $auth): void
    {
        $creator = $auth->current();
        if (! $creator) return;

        $this->settingsError = '';
        $this->settingsSuccess = '';

        $this->validate([
            'editName' => 'required|string|max:120',
            'editHandle' => 'required|string|max:80',
            'editPlatform' => 'required|string|in:YouTube,Twitch,TikTok,Podcast,Substack,Kick',
            'editBio' => 'nullable|string|max:600',
            'editMinPrice' => 'required|numeric|min:3|max:1000',
        ]);

        $handle = ltrim(trim($this->editHandle), '@');
        $slug = Capsule::slugify($handle);

        if ($slug !== $creator->slug) {
            $exists = Creator::query()->where('slug', $slug)->where('id', '!=', $creator->id)->exists();
            if ($exists) {
                $slug .= '-' . Str::random(4);
            }
        }

        $creator->update([
            'name' => trim($this->editName),
            'handle' => '@' . $handle,
            'slug' => $slug,
            'platform' => $this->editPlatform,
            'bio' => trim($this->editBio),
            'min_seal_price_cents' => (int) round($this->editMinPrice * 100),
        ]);

        session(['creator_slug' => $creator->slug]);
        $this->settingsSuccess = 'Vault profile settings saved successfully! 🎉';
    }

    public function applyPreset(string $preset): void
    {
        if ($preset === 'feedback') {
            $this->newMilestoneTitle = 'What do you love most about our content?';
            $this->newMilestoneDesc = 'Tell me what videos or streams you enjoy the most, favorite moments, or what makes you smile!';
        } elseif ($preset === 'ama') {
            $this->newMilestoneTitle = 'Ask Me Anything (Live Q&A Stream)';
            $this->newMilestoneDesc = 'Drop your burning questions, curiosities, or personal advice topics for me to answer live on stream!';
        } elseif ($preset === 'ideas') {
            $this->newMilestoneTitle = 'What video or project should we make next?';
            $this->newMilestoneDesc = 'Share your creative ideas, video concepts, challenges, or games you want to see me tackle.';
        } elseif ($preset === 'milestone') {
            $this->newMilestoneTitle = '100k Community Milestone Stream';
            $this->newMilestoneDesc = 'Celebrate our journey together! Leave a memory or prediction for where our community will be next.';
        } elseif ($preset === 'predictions') {
            $this->newMilestoneTitle = 'Predict our channel future in 2 years!';
            $this->newMilestoneDesc = 'Where will our channel, community, and tech be by 2028? Leave your wildest predictions to unseal!';
        }
    }

    public function addMilestone(CreatorAuthService $auth): void
    {
        $creator = $auth->current();
        if (! $creator) return;

        $this->milestoneError = '';
        $this->milestoneSuccess = '';

        $this->validate([
            'newMilestoneTitle' => 'required|string|max:120',
            'newMilestoneDate' => 'required|date',
            'newMilestoneDesc' => 'nullable|string|max:400',
        ]);

        if ($this->newMilestoneActive) {
            $creator->milestones()->update(['is_active' => false]);
        }

        $isFirst = $creator->milestones()->count() === 0;
        $milestone = $creator->milestones()->create([
            'title' => trim($this->newMilestoneTitle),
            'unlock_date' => $this->newMilestoneDate,
            'description' => trim($this->newMilestoneDesc),
            'is_active' => $this->newMilestoneActive || $isFirst,
        ]);

        if ($milestone->is_active) {
            $creator->update([
                'milestone_title' => $milestone->title,
                'unlock_date' => $milestone->unlock_date,
            ]);
        }

        $this->newMilestoneTitle = '';
        $this->newMilestoneDesc = '';
        $this->newMilestoneActive = false;
        $this->milestoneSuccess = "Topic/Milestone “{$milestone->title}” created successfully! 🎉";
    }

    public function setActiveMilestone(string $milestoneId, CreatorAuthService $auth): void
    {
        $creator = $auth->current();
        if (! $creator) return;

        $milestone = $creator->milestones()->where('id', $milestoneId)->first();
        if ($milestone) {
            $creator->milestones()->update(['is_active' => false]);
            $milestone->update(['is_active' => true]);
            $creator->update([
                'milestone_title' => $milestone->title,
                'unlock_date' => $milestone->unlock_date,
            ]);
            $this->milestoneSuccess = "“{$milestone->title}” is now your featured topic on your public door!";
        }
    }

    public function deleteMilestone(string $milestoneId, CreatorAuthService $auth): void
    {
        $creator = $auth->current();
        if (! $creator) return;

        $milestone = $creator->milestones()->where('id', $milestoneId)->first();
        if ($milestone) {
            if ($milestone->postcards()->count() > 0) {
                $this->milestoneError = 'Cannot delete a topic or milestone that already contains fan letters.';
                return;
            }
            $title = $milestone->title;
            $wasActive = $milestone->is_active;
            $milestone->delete();

            if ($wasActive && $next = $creator->milestones()->first()) {
                $next->update(['is_active' => true]);
                $creator->update([
                    'milestone_title' => $next->title,
                    'unlock_date' => $next->unlock_date,
                ]);
            }

            $this->milestoneSuccess = "Topic/Milestone “{$title}” removed.";
        }
    }

    public function logout(CreatorAuthService $auth): mixed
    {
        $auth->logout();

        return $this->redirect(route('creators'), navigate: true);
    }

    public function with(): array
    {
        $creator = app(CreatorAuthService::class)->current();
        $ledger = collect();
        $inboxLetters = collect();
        $milestones = collect();
        $totalLetters = 0;
        $paidN = 0;
        $pendingN = 0;
        $paidCents = 0;
        $pendingCents = 0;

        if ($creator) {
            $milestones = $creator->milestones()->withCount('postcards')->get();
            $totalLetters = $creator->postcards()->count();

            // Stream Reader Query
            $streamQuery = $creator->postcards()->with(['referral', 'milestone', 'envelope'])->orderByDesc('sealed_at');
            if ($this->selectedMilestoneFilter !== 'all') {
                $streamQuery->where('milestone_id', $this->selectedMilestoneFilter);
            }
            $ledger = $streamQuery->limit(100)->get();

            // Fan Mail Inbox Query with search and filter
            $inboxQuery = $creator->postcards()->with(['milestone', 'envelope'])->orderByDesc('sealed_at');
            if ($this->filterLetterMilestone !== 'all') {
                $inboxQuery->where('milestone_id', $this->filterLetterMilestone);
            }
            if (trim($this->searchLetters) !== '') {
                $term = '%' . trim($this->searchLetters) . '%';
                $inboxQuery->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                      ->orWhere('location', 'like', $term)
                      ->orWhere('teaser', 'like', $term);
                });
            }
            $inboxLetters = $inboxQuery->limit(100)->get();

            // Calculate financials
            $allPostcards = $creator->postcards()->with('referral')->get();
            foreach ($allPostcards as $row) {
                $cents = (int) ($row->referral->cut_cents ?? Capsule::CREATOR_CUT_CENTS);
                if (($row->referral->status ?? 'pending') === 'paid') {
                    $paidN++;
                    $paidCents += $cents;
                } else {
                    $pendingN++;
                    $pendingCents += $cents;
                }
            }
        }

        $activeMilestone = $creator ? ($creator->activeMilestone() ?? $milestones->first()) : null;

        return [
            'creator' => $creator,
            'milestones' => $milestones,
            'activeMilestone' => $activeMilestone,
            'totalLetters' => $totalLetters,
            'ledger' => $ledger,
            'inboxLetters' => $inboxLetters,
            'paid' => $paidCents / 100,
            'pending' => $pendingCents / 100,
            'paidN' => $paidN,
            'pendingN' => $pendingN,
            'link' => $creator ? url('/with/'.$creator->slug) : '',
        ];
    }
};
?>

@if(! $creator)
  <section class="max-w-md mx-auto px-4 py-16 text-center">
    <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] mb-3 font-mono">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
        </svg>
        <span>Creator Studio</span>
      </span>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-2">
        Your studio is locked.
      </h1>
      <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
        Set up a vault, or log in with the email you registered — no password required.
      </p>
      <div class="flex flex-col gap-2.5">
        <a href="{{ route('creators.join') }}" class="w-full inline-flex items-center justify-center px-6 py-3 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm shadow-[0_4px_16px_rgba(6,78,59,0.3)] transition-all">
          Create Your Vault (Free)
        </a>
        <a href="{{ route('creators.access') }}" class="w-full inline-flex items-center justify-center px-6 py-3 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-bold text-sm transition-all shadow-xs">
          Open with Email
        </a>
      </div>
    </div>
  </section>
@else
  <!-- Authenticated Creator Dashboard Shell with Sidebar -->
  <div 
    class="min-h-screen flex bg-[#faf9f5]" 
    x-data="{
      tab: '{{ $tab }}',
      mobileNav: false,
      copiedDoor: false,
      copiedBio: false,
      setTab(t) {
        this.tab = t;
        this.mobileNav = false;
        $wire.set('tab', t);
        const url = new URL(window.location);
        url.searchParams.set('tab', t);
        window.history.replaceState({}, '', url);
      },
      async copyBioSnippet() {
        const snippet = '📬 Leave a message in our {{ addslashes($activeMilestone?->title ?? $creator->milestone_title ?? 'Community Milestone') }} Time Vault: {{ $link }}';
        try {
          await navigator.clipboard.writeText(snippet);
          this.copiedBio = true;
          setTimeout(() => this.copiedBio = false, 2000);
        } catch(e) {}
      },
      async copyDoorLink() {
        try {
          await navigator.clipboard.writeText('{{ $link }}');
          this.copiedDoor = true;
          setTimeout(() => this.copiedDoor = false, 2000);
        } catch(e) {}
      }
    }"
  >
    <!-- MOBILE DRAWER BACKDROP & SIDEBAR -->
    <div 
      x-show="mobileNav" 
      class="fixed inset-0 z-50 lg:hidden flex" 
      style="display:none;"
      x-transition:enter="transition-opacity ease-linear duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition-opacity ease-linear duration-300"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
    >
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black/40 backdrop-blur-xs" @click="mobileNav = false"></div>

      <!-- Mobile Slide-out Drawer -->
      <div 
        class="relative w-72 max-w-[80vw] bg-white h-full flex flex-col justify-between shadow-2xl border-r border-[#e7e5df] p-4 sm:p-5 z-10"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
      >
        <div class="flex-1 overflow-y-auto">
          <!-- Mobile Brand & Close -->
          <div class="flex items-center justify-between pb-4 border-b border-[#e7e5df]">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-[#0f172a]">
              <span class="w-8 h-8 rounded-lg bg-[#064e3b] text-white flex items-center justify-center font-mono font-bold text-xs tracking-tight shadow-2xs">FV</span>
              <div class="leading-none">
                <span class="tracking-tight font-serif text-base font-bold">FanVault</span>
                <span class="text-[10px] text-[#64748b] font-medium block mt-0.5">Studio</span>
              </div>
            </a>
            <button type="button" @click="mobileNav = false" class="p-1.5 rounded-lg text-[#64748b] hover:text-[#0f172a] hover:bg-[#f1f0eb] transition-colors" aria-label="Close navigation">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Mobile Nav Items with Minimalist Icons -->
          <nav class="space-y-1 mt-4">
            <button type="button" @click="setTab('overview')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" :class="tab === 'overview' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'">
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'overview' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                  <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                  <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                  <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                <span>Overview</span>
              </span>
            </button>

            <button type="button" @click="setTab('milestones')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" :class="tab === 'milestones' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'">
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'milestones' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V12" />
                </svg>
                <span>Topics & Milestones</span>
              </span>
              <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded-full" :class="tab === 'milestones' ? 'bg-white text-[#064e3b] shadow-2xs' : 'bg-[#f1f0eb] text-[#64748b]'">
                {{ $milestones->count() }}
              </span>
            </button>

            <button type="button" @click="setTab('letters')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" :class="tab === 'letters' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'">
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'letters' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <span>Fan Letters</span>
              </span>
              <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded-full" :class="tab === 'letters' ? 'bg-white text-[#064e3b] shadow-2xs' : 'bg-[#f1f0eb] text-[#64748b]'">
                {{ $totalLetters }}
              </span>
            </button>

            <button type="button" @click="setTab('stream')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" :class="tab === 'stream' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'">
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'stream' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <span>Stream Reader View</span>
              </span>
              <span class="text-[10px] font-mono font-medium px-1.5 py-0.5 rounded bg-[#f1f5f9] text-[#475569]">OBS</span>
            </button>

            <button type="button" @click="setTab('earnings')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" :class="tab === 'earnings' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'">
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'earnings' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Earnings & Ledger</span>
              </span>
              <span class="text-xs font-mono font-semibold text-[#047857]">
                ${{ number_format($paid + $pending, 0) }}
              </span>
            </button>

            <button type="button" @click="setTab('settings')" class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" :class="tab === 'settings' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'">
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'settings' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Vault Settings</span>
              </span>
            </button>
          </nav>
        </div>

        <!-- Mobile Profile Dock -->
        <div class="pt-3 border-t border-[#e7e5df] space-y-2">
          <div class="flex items-center justify-between p-2 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-7 h-7 rounded-full bg-[#064e3b] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
                {{ mb_strtoupper(mb_substr($creator->name, 0, 1)) }}
              </div>
              <div class="truncate">
                <span class="block text-xs font-semibold text-[#0f172a] truncate">{{ $creator->name }}</span>
                <span class="block text-[10px] text-[#64748b] truncate">@{{ $creator->handle }}</span>
              </div>
            </div>
            <button type="button" @click="setTab('settings')" title="Vault Settings" class="p-1 rounded text-[#64748b] hover:text-[#0f172a]">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>

          <a href="{{ route('with', $creator->slug) }}" target="_blank" class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-[#064e3b] bg-[#ecfdf5] border border-[#a7f3d0] transition-colors">
            <span>View Public Door</span>
            <span>↗</span>
          </a>
          <button type="button" wire:click="logout" class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
            Log Out
          </button>
        </div>
      </div>
    </div>

    <!-- DESKTOP PERSISTENT LEFT SIDEBAR -->
    <aside class="hidden lg:flex flex-col w-64 xl:w-72 bg-white border-r border-[#e7e5df] h-screen sticky top-0 shrink-0 z-40">
      <!-- Sidebar Brand Header -->
      <div class="p-4 sm:p-5 border-b border-[#e7e5df] flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
          <span class="w-8 h-8 rounded-lg bg-[#064e3b] text-white flex items-center justify-center font-mono font-bold text-xs tracking-tight shadow-xs transition-transform group-hover:scale-105">
            FV
          </span>
          <div class="leading-none">
            <span class="font-serif font-bold text-base text-[#0f172a] block tracking-tight">FanVault</span>
            <span class="text-[10px] text-[#64748b] font-medium block mt-1">Creator Studio</span>
          </div>
        </a>
        <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded-full bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]/80">
          v2.4
        </span>
      </div>

      <!-- Sidebar Navigation Menu Links -->
      <div class="flex-1 overflow-y-auto px-3.5 py-4 space-y-5">
        <div>
          <span class="block px-3 text-[10px] font-bold uppercase tracking-wider text-[#94a3b8] mb-2 font-mono">
            Vault Hub
          </span>
          <nav class="space-y-1">
            <!-- 1. Overview -->
            <button 
              type="button" 
              @click="setTab('overview')" 
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" 
              :class="tab === 'overview' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'"
            >
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'overview' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                  <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                  <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                  <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                <span>Overview</span>
              </span>
            </button>

            <!-- 2. Topics & Milestones -->
            <button 
              type="button" 
              @click="setTab('milestones')" 
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" 
              :class="tab === 'milestones' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'"
            >
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'milestones' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V12" />
                </svg>
                <span>Topics & Milestones</span>
              </span>
              <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded-full" :class="tab === 'milestones' ? 'bg-white text-[#064e3b] shadow-2xs' : 'bg-[#f1f0eb] text-[#64748b]'">
                {{ $milestones->count() }}
              </span>
            </button>

            <!-- 3. Fan Mail & Letters -->
            <button 
              type="button" 
              @click="setTab('letters')" 
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" 
              :class="tab === 'letters' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'"
            >
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'letters' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <span>Fan Letters</span>
              </span>
              <span class="text-[11px] font-mono font-medium px-2 py-0.5 rounded-full" :class="tab === 'letters' ? 'bg-white text-[#064e3b] shadow-2xs' : 'bg-[#f1f0eb] text-[#64748b]'">
                {{ $totalLetters }}
              </span>
            </button>

            <!-- 4. Stream Mode -->
            <button 
              type="button" 
              @click="setTab('stream')" 
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" 
              :class="tab === 'stream' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'"
            >
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'stream' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <span>Stream Reader View</span>
              </span>
              <span class="text-[10px] font-mono font-medium px-1.5 py-0.5 rounded bg-[#f1f5f9] text-[#475569]">OBS</span>
            </button>
          </nav>
        </div>

        <div>
          <span class="block px-3 text-[10px] font-bold uppercase tracking-wider text-[#94a3b8] mb-2 font-mono">
            Administration
          </span>
          <nav class="space-y-1">
            <!-- 5. Earnings -->
            <button 
              type="button" 
              @click="setTab('earnings')" 
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" 
              :class="tab === 'earnings' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'"
            >
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'earnings' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Earnings & Ledger</span>
              </span>
              <span class="text-xs font-mono font-semibold text-[#047857]">
                ${{ number_format($paid + $pending, 0) }}
              </span>
            </button>

            <!-- 6. Settings -->
            <button 
              type="button" 
              @click="setTab('settings')" 
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-all text-left" 
              :class="tab === 'settings' ? 'bg-[#ecfdf5] text-[#064e3b] font-semibold border border-[#a7f3d0]/80 shadow-2xs' : 'text-[#475569] hover:bg-[#faf9f5] hover:text-[#0f172a]'"
            >
              <span class="flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 transition-colors" :class="tab === 'settings' ? 'text-[#064e3b]' : 'text-[#64748b]'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Vault Settings</span>
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Elegant Minimalist Profile & Settings Dock -->
      <div class="p-3.5 border-t border-[#e7e5df] bg-[#faf9f5]/70 space-y-2.5">
        <!-- Creator Profile Card -->
        <div class="flex items-center justify-between p-2 rounded-2xl bg-white border border-[#e7e5df] shadow-2xs">
          <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-8 h-8 rounded-full bg-[#064e3b] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs">
              {{ mb_strtoupper(mb_substr($creator->name, 0, 1)) }}
            </div>
            <div class="truncate">
              <span class="block text-xs font-semibold text-[#0f172a] truncate leading-tight">{{ $creator->name }}</span>
              <span class="block text-[11px] text-[#64748b] truncate leading-tight mt-0.5">@{{ $creator->handle }} · {{ $creator->platform }}</span>
            </div>
          </div>
          <button 
            type="button" 
            @click="setTab('settings')" 
            title="Vault Settings" 
            class="p-1.5 rounded-lg text-[#64748b] hover:text-[#0f172a] hover:bg-[#f1f0eb] transition-colors shrink-0 ml-1"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>
        </div>

        <!-- Quick Actions -->
        <div class="space-y-1">
          <a 
            href="{{ route('with', $creator->slug) }}" 
            target="_blank" 
            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium text-[#0f172a] bg-white hover:bg-[#ecfdf5] hover:text-[#064e3b] border border-[#e7e5df] hover:border-[#a7f3d0] shadow-2xs transition-all"
          >
            <span class="flex items-center gap-2">
              <svg class="w-3.5 h-3.5 text-[#64748b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>View Public Door</span>
            </span>
            <svg class="w-3 h-3 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
            </svg>
          </a>

          <div class="flex items-center justify-between pt-1 px-1 text-xs">
            <button 
              type="button" 
              @click="copyDoorLink()" 
              class="text-[#64748b] hover:text-[#047857] font-medium flex items-center gap-1.5 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
              </svg>
              <span x-show="!copiedDoor">Copy Link</span>
              <span x-show="copiedDoor" class="text-[#047857] font-semibold" style="display:none;">✓ Copied</span>
            </button>
            <button 
              type="button" 
              wire:click="logout" 
              class="text-[#94a3b8] hover:text-red-600 font-medium flex items-center gap-1 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
              </svg>
              <span>Log Out</span>
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- MAIN DASHBOARD CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen overflow-x-hidden">
      <!-- TOP DASHBOARD STICKY HEADER BAR -->
      <header class="h-16 border-b border-[#e7e5df] bg-white/90 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <!-- Mobile Toggle Button -->
          <button 
            type="button" 
            @click="mobileNav = true" 
            class="lg:hidden p-2 rounded-xl bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] hover:bg-[#f1f5f9]"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Breadcrumbs Title -->
          <div class="flex items-center gap-2 text-xs sm:text-sm">
            <span class="text-[#64748b] hidden sm:inline">Creator Studio</span>
            <span class="text-[#cbd5e1] hidden sm:inline">/</span>
            <span class="font-bold text-[#0f172a] capitalize">
              <span x-show="tab === 'overview'">Overview</span>
              <span x-show="tab === 'milestones'">Topics & Milestones</span>
              <span x-show="tab === 'letters'">Fan Letters</span>
              <span x-show="tab === 'stream'">Stream Reader View</span>
              <span x-show="tab === 'earnings'">Earnings & Ledger</span>
              <span x-show="tab === 'settings'">Vault Settings</span>
            </span>
          </div>
        </div>

        <!-- Top Right Actions -->
        <div class="flex items-center gap-2 sm:gap-3">
          @if($activeMilestone && $activeMilestone->daysRemaining() !== null)
            <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fefce8] border border-[#fde047] text-xs font-bold text-[#854d0e] font-mono">
              <svg class="w-3.5 h-3.5 text-[#854d0e]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ $activeMilestone->daysRemaining() }}d to {{ $activeMilestone->title }}</span>
            </div>
          @endif

          <button 
            type="button" 
            @click="copyBioSnippet()" 
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-xs font-bold text-[#0f172a] shadow-2xs transition-all"
          >
            <span x-show="!copiedBio" class="inline-flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-[#64748b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
              </svg>
              <span class="hidden md:inline">Copy Bio Snippet</span>
            </span>
            <span x-show="copiedBio" class="text-[#047857] inline-flex items-center gap-1" style="display:none;">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
              <span>Copied!</span>
            </span>
          </button>

          <a 
            href="{{ route('with', $creator->slug) }}" 
            target="_blank" 
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-[#ecfdf5] hover:bg-[#d1fae5] border border-[#a7f3d0] text-xs font-bold text-[#064e3b] transition-all"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="hidden sm:inline">Preview Door</span>
          </a>
        </div>
      </header>

      <!-- DASHBOARD BODY CONTAINER -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto space-y-6">
        <!-- GLOBAL DASHBOARD ALERTS -->
        @if(session('creator_just_onboarded'))
          <div class="p-4 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] text-xs sm:text-sm font-semibold flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
              <span class="w-6 h-6 rounded-full bg-white border border-[#a7f3d0] text-[#064e3b] flex items-center justify-center font-bold text-xs shrink-0">✓</span>
              <span>Your community milestone vault is live at <a href="{{ route('with', $creator->slug) }}" class="underline font-bold font-mono">/with/{{ $creator->slug }}</a>! Copy your bio snippet below to share with your fans.</span>
            </div>
          </div>
        @endif

        @if($milestoneSuccess)
          <div class="p-4 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] text-xs sm:text-sm font-semibold flex items-center justify-between gap-2 shadow-xs">
            <span class="flex items-center gap-2">
              <span class="w-5 h-5 rounded-full bg-white border border-[#a7f3d0] flex items-center justify-center font-bold text-[11px] shrink-0">✓</span>
              <span>{{ $milestoneSuccess }}</span>
            </span>
            <button type="button" wire:click="$set('milestoneSuccess', '')" class="text-xs font-bold underline">Dismiss</button>
          </div>
        @endif

        @if($milestoneError)
          <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm font-semibold flex items-center justify-between gap-2 shadow-xs">
            <span class="flex items-center gap-2">
              <svg class="w-4 h-4 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
              <span>{{ $milestoneError }}</span>
            </span>
            <button type="button" wire:click="$set('milestoneError', '')" class="text-xs font-bold underline">Dismiss</button>
          </div>
        @endif

        @if($settingsSuccess)
          <div class="p-4 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] text-xs sm:text-sm font-semibold flex items-center justify-between gap-2 shadow-xs">
            <span class="flex items-center gap-2">
              <span class="w-5 h-5 rounded-full bg-white border border-[#a7f3d0] flex items-center justify-center font-bold text-[11px] shrink-0">✓</span>
              <span>{{ $settingsSuccess }}</span>
            </span>
            <button type="button" wire:click="$set('settingsSuccess', '')" class="text-xs font-bold underline">Dismiss</button>
          </div>
        @endif

        <!-- ========================================== -->
        <!-- VIEW 1: OVERVIEW COMMAND CENTER -->
        <!-- ========================================== -->
        <div x-show="tab === 'overview'" class="space-y-6">
          <!-- Top Welcome Card -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <div class="flex items-center gap-2 mb-1.5">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
                  {{ $creator->platform }} Creator Vault
                </span>
                <span class="text-xs font-mono font-bold text-[#64748b]">
                  {{ $creator->handle }}
                </span>
              </div>
              <h1 class="font-serif text-3xl sm:text-4xl font-bold text-[#0f172a]">
                {{ $creator->name }}’s Studio
              </h1>
              <p class="text-xs sm:text-sm text-[#475569] mt-0.5">
                {{ $creator->milestone_title ?? 'Community Milestone' }} · Target Reveal: {{ $creator->formattedUnlockDate() }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button type="button" @click="setTab('milestones')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] text-white text-xs font-bold shadow-xs hover:shadow-md transition-all">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                <span>Add Topic or Milestone</span>
              </button>
              <button type="button" @click="setTab('stream')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-xs font-bold text-[#0f172a] shadow-2xs transition-all">
                <svg class="w-3.5 h-3.5 text-[#64748b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <span>Stream Mode</span>
              </button>
            </div>
          </div>

          <!-- 4 KPI Metrics Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <!-- 1. Total Letters -->
            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Letters in Vault</span>
              <div class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a]">
                {{ $totalLetters }}
              </div>
              <span class="flex items-center gap-1.5 text-[11px] text-[#047857] font-medium mt-1">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <span>Superfan sealed messages</span>
              </span>
            </div>

            <!-- 2. Active Topics & Milestones -->
            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Topics & Milestones</span>
              <div class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a]">
                {{ $milestones->count() }}
              </div>
              <span class="flex items-center gap-1.5 text-[11px] text-[#047857] font-medium mt-1">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V12" />
                </svg>
                <span>Active prompts & celebrations</span>
              </span>
            </div>

            <!-- 3. Accrued Payout -->
            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Accrued Payout</span>
              <div class="font-serif text-2xl sm:text-3xl font-bold text-[#047857]">
                ${{ number_format($paid + $pending, 2) }}
              </div>
              <span class="flex items-center gap-1.5 text-[11px] text-[#047857] font-medium mt-1">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
                <span>Direct creator payouts on all seals & tips</span>
              </span>
            </div>

            <!-- 4. Next Reveal Target -->
            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Next Reveal Target</span>
              <div class="text-sm sm:text-base font-bold text-[#0f172a] truncate">
                {{ $activeMilestone?->formattedUnlockDate() ?? $creator->formattedUnlockDate() }}
              </div>
              <span class="flex items-center gap-1.5 text-[11px] text-[#b45309] font-mono mt-1">
                @if($activeMilestone && $activeMilestone->daysRemaining() !== null)
                  <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ $activeMilestone->daysRemaining() }} days left</span>
                @else
                  <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
                  <span>Live broadcast</span>
                @endif
              </span>
            </div>
          </div>

          <!-- YouTube Bio Snippet Quick-Copy Card -->
          <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] flex items-center justify-center">
                  <svg class="w-4 h-4 text-[#064e3b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                  </svg>
                </span>
                <h3 class="font-serif font-bold text-base sm:text-lg text-[#0f172a]">
                  YouTube Bio Snippet & Fan Link
                </h3>
              </div>
              <span class="text-xs font-bold text-[#047857] bg-[#ecfdf5] px-2.5 py-1 rounded-full border border-[#a7f3d0]">
                Copy & Paste in Bio
              </span>
            </div>
            <p class="text-xs sm:text-sm text-[#475569] mb-4">
              Add this one-liner to your video descriptions, Twitch panels, or pinned comments to invite your community to seal letters for your milestone stream!
            </p>
            <div class="flex flex-col sm:flex-row gap-2">
              <input 
                type="text" 
                readonly 
                value="📬 Leave a message in our {{ $activeMilestone?->title ?? $creator->milestone_title ?? 'Community Milestone' }} Time Vault: {{ $link }}" 
                class="flex-1 bg-[#faf9f5] border border-[#e7e5df] rounded-2xl px-4 py-3 text-xs sm:text-sm font-mono text-[#0f172a] outline-none"
              >
              <button 
                type="button" 
                @click="copyBioSnippet()" 
                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] text-white font-bold text-xs sm:text-sm shadow-xs hover:shadow-md transition-all shrink-0"
              >
                <span x-show="!copiedBio" class="inline-flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                  </svg>
                  <span>Copy Snippet</span>
                </span>
                <span x-show="copiedBio" class="inline-flex items-center gap-1.5" style="display:none;">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                  <span>Copied to Clipboard!</span>
                </span>
              </button>
            </div>
          </div>

          <!-- Featured Milestone Spotlight -->
          @if($activeMilestone)
            <div class="bg-gradient-to-r from-[#f0fdf4] to-[#ecfdf5] border border-[#a7f3d0] rounded-3xl p-5 sm:p-6 shadow-xs">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2.5">
                  <span class="w-8 h-8 rounded-xl bg-white border border-[#a7f3d0] flex items-center justify-center text-[#064e3b] shadow-2xs">
                    <svg class="w-4 h-4 text-[#064e3b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V12" />
                    </svg>
                  </span>
                  <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#064e3b] font-mono">Current Featured Topic or Milestone</span>
                    <h4 class="font-serif font-bold text-base sm:text-lg text-[#0f172a]">{{ $activeMilestone->title }}</h4>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-[#a7f3d0] text-xs font-mono font-bold text-[#047857]">
                    <svg class="w-3.5 h-3.5 text-[#047857]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <span>Unlocks {{ $activeMilestone->formattedUnlockDate() }}</span>
                  </span>
                  <button type="button" @click="setTab('milestones')" class="text-xs font-bold text-[#064e3b] underline">
                    Manage
                  </button>
                </div>
              </div>
              @if($activeMilestone->description)
                <p class="text-xs sm:text-sm text-[#334155] italic bg-white/70 rounded-xl p-3 border border-[#a7f3d0]/60">
                  “{{ $activeMilestone->description }}”
                </p>
              @endif
            </div>
          @endif

          <!-- Recent Fan Letters Preview -->
          <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 sm:p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="font-serif text-lg font-bold text-[#0f172a]">Recent Fan Messages</h3>
                <p class="text-xs text-[#64748b]">Latest public teaser quotes sealed by your community</p>
              </div>
              <button type="button" @click="setTab('letters')" class="text-xs font-bold text-[#047857] hover:underline">
                View All Letters ({{ $totalLetters }}) →
              </button>
            </div>

            @if($ledger->count() === 0)
              <div class="text-center py-8 text-[#64748b] text-xs sm:text-sm border-2 border-dashed border-[#e7e5df] rounded-2xl flex flex-col items-center justify-center gap-2">
                <svg class="w-6 h-6 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <span>No fan letters sealed yet. Copy your bio snippet above to get your first message!</span>
              </div>
            @else
              <div class="divide-y divide-[#e7e5df]">
                @foreach($ledger->take(5) as $letter)
                  <div class="py-3 flex items-start justify-between gap-4">
                    <div class="space-y-1">
                      <p class="font-serif italic text-xs sm:text-sm text-[#0f172a]">“{{ $letter->teaser }}”</p>
                      <div class="flex items-center gap-2 text-[11px] text-[#64748b]">
                        <span class="font-bold text-[#334155]">{{ $letter->name }}</span>
                        <span>·</span>
                        <span>{{ $letter->location }}</span>
                        <span>·</span>
                        <span>{{ $letter->sealed_at->diffForHumans() }}</span>
                      </div>
                    </div>
                    <span class="shrink-0 text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
                      {{ $letter->milestone?->title ?? 'General Vault' }}
                    </span>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 2: TOPICS & MILESTONES MANAGER -->
        <!-- ========================================== -->
        <div x-show="tab === 'milestones'" style="display:none;" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#e7e5df]">
            <div>
              <h2 class="font-serif text-2xl font-bold text-[#0f172a]">Topics & Milestones</h2>
              <p class="text-xs sm:text-sm text-[#64748b]">Run multiple prompts, follower questions, AMAs, or subscriber milestones simultaneously. Pick which one is featured on your public door.</p>
            </div>
            <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-white border border-[#e7e5df] text-[#047857]">
              {{ $milestones->count() }} Active
            </span>
          </div>

          <!-- Milestones Cards List -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($milestones as $m)
              <div class="bg-white border {{ $m->is_active ? 'border-2 border-[#047857] shadow-sm' : 'border-[#e7e5df]' }} rounded-3xl p-5 sm:p-6 flex flex-col justify-between">
                <div>
                  <div class="flex items-start justify-between gap-2 mb-2">
                    <div>
                      @if($m->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] mb-1 font-mono">
                          <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
                          <span>Featured on Public Door</span>
                        </span>
                      @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#f1f5f9] text-[#64748b] mb-1 font-mono">
                          Topic / Milestone
                        </span>
                      @endif
                      <h3 class="font-serif font-bold text-lg text-[#0f172a]">{{ $m->title }}</h3>
                    </div>
                    <span class="text-xs font-mono font-bold text-[#047857] bg-[#ecfdf5] px-2.5 py-1 rounded-full border border-[#a7f3d0] shrink-0">
                      {{ $m->postcards_count }} letters
                    </span>
                  </div>

                  <div class="text-xs text-[#64748b] space-y-1 mb-3">
                    <p class="flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-[#64748b] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                      </svg>
                      <span>Reveal Date: <strong class="text-[#0f172a]">{{ $m->formattedUnlockDate() }}</strong> ({{ $m->daysRemaining() !== null ? $m->daysRemaining() . ' days left' : 'Today' }})</span>
                    </p>
                    @if($m->description)
                      <p class="italic text-[#334155] bg-[#faf9f5] p-2.5 rounded-xl border border-[#e7e5df] mt-2">
                        “{{ $m->description }}”
                      </p>
                    @endif
                  </div>
                </div>

                <div class="pt-3 border-t border-[#e7e5df] flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2">
                    @if(! $m->is_active)
                      <button type="button" wire:click="setActiveMilestone('{{ $m->id }}')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#ecfdf5] hover:bg-[#d1fae5] text-[#064e3b] border border-[#a7f3d0] transition-colors">
                        Set as Featured
                      </button>
                    @endif
                    <button type="button" @click="navigator.clipboard.writeText('{{ url('/with/'.$creator->slug.'?milestone='.$m->id) }}'); alert('Direct topic/milestone link copied!');" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df] transition-colors">
                      <svg class="w-3.5 h-3.5 text-[#64748b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                      </svg>
                      <span>Copy Link</span>
                    </button>
                  </div>

                  @if($m->postcards_count === 0)
                    <button type="button" wire:click="deleteMilestone('{{ $m->id }}')" wire:confirm="Are you sure you want to remove this topic or milestone?" class="text-xs text-red-500 hover:text-red-700 font-semibold">
                      Delete
                    </button>
                  @endif
                </div>
              </div>
            @endforeach
          </div>

          <!-- Add New Milestone Form Card -->
          <div class="bg-gradient-to-b from-white to-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 sm:p-7 shadow-xs">
            <div class="flex items-center gap-2.5 mb-4">
              <span class="w-8 h-8 rounded-xl bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] flex items-center justify-center font-bold text-sm">
                <svg class="w-4 h-4 text-[#064e3b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
              </span>
              <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
                Add New Topic or Milestone
              </h3>
            </div>

            <!-- Quick 1-Click Starter Presets -->
            <div class="mb-5 p-4 rounded-2xl bg-white border border-[#e7e5df]">
              <div class="text-[11px] font-bold uppercase tracking-wider text-[#64748b] mb-2.5 font-mono flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-[#047857]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                </svg>
                <span>1-Click Starter Presets:</span>
              </div>
              <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="applyPreset('feedback')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#faf9f5] hover:bg-[#ecfdf5] text-[#334155] hover:text-[#064e3b] border border-[#e7e5df] hover:border-[#a7f3d0] transition-colors">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
                  <span>What do you love most?</span>
                </button>
                <button type="button" wire:click="applyPreset('ama')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#faf9f5] hover:bg-[#ecfdf5] text-[#334155] hover:text-[#064e3b] border border-[#e7e5df] hover:border-[#a7f3d0] transition-colors">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#2563eb]"></span>
                  <span>Ask Me Anything (Q&A)</span>
                </button>
                <button type="button" wire:click="applyPreset('ideas')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#faf9f5] hover:bg-[#ecfdf5] text-[#334155] hover:text-[#064e3b] border border-[#e7e5df] hover:border-[#a7f3d0] transition-colors">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#d97706]"></span>
                  <span>Next Video Ideas</span>
                </button>
                <button type="button" wire:click="applyPreset('milestone')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#faf9f5] hover:bg-[#ecfdf5] text-[#334155] hover:text-[#064e3b] border border-[#e7e5df] hover:border-[#a7f3d0] transition-colors">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#7c3aed]"></span>
                  <span>Milestone Celebration</span>
                </button>
                <button type="button" wire:click="applyPreset('predictions')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#faf9f5] hover:bg-[#ecfdf5] text-[#334155] hover:text-[#064e3b] border border-[#e7e5df] hover:border-[#a7f3d0] transition-colors">
                  <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span>
                  <span>Channel Predictions</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs font-bold text-[#0f172a] mb-1">
                  Topic or Milestone Title <span class="text-red-500">*</span>
                </label>
                <input 
                  type="text" 
                  wire:model="newMilestoneTitle" 
                  placeholder="e.g. What do you love most about our videos? or 100k Subs Celebration" 
                  class="w-full bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 outline-none"
                >
              </div>

              <div>
                <label class="block text-xs font-bold text-[#0f172a] mb-1">
                  Target Unlock / Broadcast Date <span class="text-red-500">*</span>
                </label>
                <input 
                  type="date" 
                  wire:model="newMilestoneDate" 
                  class="w-full bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3 text-base text-[#0f172a] focus:ring-2 focus:ring-[#047857]/20 outline-none"
                >
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-xs font-bold text-[#0f172a] mb-1">
                Community Welcome Prompt / Question (Optional)
              </label>
              <input 
                type="text" 
                wire:model="newMilestoneDesc" 
                placeholder="e.g. Tell me your favorite moments, what videos you want next, or ask anything for our stream!" 
                class="w-full bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 outline-none"
              >
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
              <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-[#475569]">
                <input type="checkbox" wire:model="newMilestoneActive" class="rounded text-[#047857] focus:ring-[#047857]">
                <span>Make this the active/featured topic on your public door</span>
              </label>

              <button 
                type="button" 
                wire:click="addMilestone" 
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-1.5 px-6 py-2.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] text-white font-bold text-xs sm:text-sm shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all shrink-0"
              >
                <span wire:loading.remove wire:target="addMilestone">+ Create Topic or Milestone</span>
                <span wire:loading wire:target="addMilestone">Saving…</span>
              </button>
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 3: FAN MAIL & LETTERS (INBOX) -->
        <!-- ========================================== -->
        <div x-show="tab === 'letters'" style="display:none;" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#e7e5df]">
            <div>
              <h2 class="font-serif text-2xl font-bold text-[#0f172a]">Sealed Fan Mail Inbox</h2>
              <p class="text-xs sm:text-sm text-[#64748b]">All fan letters and predictions sealed into your encrypted vaults.</p>
            </div>
            <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-white border border-[#e7e5df] text-[#047857]">
              {{ $totalLetters }} Letters Sealed
            </span>
          </div>

          <!-- Search & Filter Controls -->
          <div class="bg-white border border-[#e7e5df] rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4 text-[#94a3b8]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
              </span>
              <input 
                type="text" 
                wire:model.live.debounce.300ms="searchLetters" 
                placeholder="Search by fan name, location, or message teaser..." 
                class="w-full pl-9 pr-4 py-2 bg-[#faf9f5] border border-[#e7e5df] rounded-xl text-xs sm:text-sm text-[#0f172a] focus:bg-white focus:border-[#047857] outline-none"
              >
            </div>

            <div class="w-full sm:w-auto flex items-center gap-2">
              <span class="text-xs font-bold text-[#64748b] shrink-0">Topic:</span>
              <select wire:model.live="filterLetterMilestone" class="bg-[#faf9f5] border border-[#e7e5df] rounded-xl py-2 px-3 text-xs sm:text-sm text-[#0f172a] font-semibold outline-none w-full sm:w-auto">
                <option value="all">All Topics & Milestones ({{ $totalLetters }})</option>
                @foreach($milestones as $m)
                  <option value="{{ $m->id }}">{{ $m->title }} ({{ $m->postcards_count }})</option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- Letters List -->
          @if($inboxLetters->count() === 0)
            <div class="bg-white border-2 border-dashed border-[#e7e5df] rounded-3xl p-10 text-center text-[#64748b] flex flex-col items-center justify-center">
              <span class="w-10 h-10 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] flex items-center justify-center text-[#64748b] mb-3">
                <svg class="w-5 h-5 text-[#64748b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
              </span>
              <h3 class="font-serif font-bold text-base text-[#0f172a] mb-1">No fan letters found</h3>
              <p class="text-xs text-[#64748b] max-w-sm mx-auto">Share your vault link with your community to start receiving letters and predictions!</p>
            </div>
          @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              @foreach($inboxLetters as $letter)
                <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs flex flex-col justify-between">
                  <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                      <div>
                        <span class="text-xs font-bold text-[#0f172a] block">{{ $letter->name }}</span>
                        <span class="text-[11px] text-[#64748b] flex items-center gap-1.5 mt-0.5">
                          <svg class="w-3 h-3 text-[#94a3b8] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                          </svg>
                          <span>{{ $letter->location }} · Sealed {{ $letter->sealed_at->format('j M Y') }}</span>
                        </span>
                      </div>
                      <span class="text-[10px] font-mono font-bold px-2.5 py-0.5 rounded-full bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] shrink-0">
                        {{ $letter->milestone?->title ?? 'General Vault' }}
                      </span>
                    </div>

                    <blockquote class="font-serif italic text-sm text-[#0f172a] my-2 p-3 bg-[#faf9f5] rounded-xl border border-[#e7e5df]">
                      “{{ $letter->teaser }}”
                    </blockquote>
                  </div>

                  <div class="pt-3 border-t border-[#e7e5df] flex items-center justify-between text-[11px] text-[#64748b]">
                    <span class="flex items-center gap-1.5 font-mono text-[#047857] font-bold">
                      <svg class="w-3.5 h-3.5 text-[#047857] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                      </svg>
                      <span>Full letter sealed</span>
                    </span>
                    <a href="{{ route('message', $letter) }}" target="_blank" class="font-bold text-[#047857] hover:underline flex items-center gap-1">
                      <span>View Keepsake Pass</span>
                      <span>↗</span>
                    </a>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

        <!-- ========================================== -->
        <!-- VIEW 4: STREAM MODE (STREAM READER VIEW) -->
        <!-- ========================================== -->
        <div x-show="tab === 'stream'" style="display:none;" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#e7e5df]">
            <div>
              <h2 class="font-serif text-2xl font-bold text-[#0f172a]">Stream Reader View</h2>
              <p class="text-xs sm:text-sm text-[#64748b]">Presenter presentation deck designed for OBS capture, second monitors, or physical cue cards.</p>
            </div>
            <div class="flex items-center gap-2">
              <select wire:model.live="selectedMilestoneFilter" class="bg-white border border-[#a7f3d0] rounded-xl text-xs font-bold py-1.5 px-3 text-[#064e3b] outline-none">
                <option value="all">All Topics & Milestones ({{ $totalLetters }})</option>
                @foreach($milestones as $m)
                  <option value="{{ $m->id }}">{{ $m->title }} ({{ $m->postcards_count }})</option>
                @endforeach
              </select>
              <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-white border border-[#e7e5df] hover:bg-[#faf9f5] rounded-xl text-xs font-bold text-[#0f172a] transition-colors shrink-0 shadow-2xs">
                <svg class="w-3.5 h-3.5 text-[#64748b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                </svg>
                <span>Print Cue Cards</span>
              </button>
            </div>
          </div>

          <div class="bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl p-4 text-xs sm:text-sm text-[#064e3b] flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-white border border-[#a7f3d0] flex items-center justify-center shrink-0">
              <svg class="w-4 h-4 text-[#064e3b]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-7.5-3v3m3-3v3m-10.125-3h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125z" />
              </svg>
            </span>
            <span><strong>Stream Presentation Mode:</strong> Use these cards to read fan stories, teasers, and predictions on your live broadcast!</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($ledger->count() === 0)
              <div class="col-span-2 bg-white border-2 border-dashed border-[#e7e5df] rounded-3xl p-10 text-center text-[#64748b]">
                No fan letters to display for this milestone yet.
              </div>
            @else
              @foreach($ledger as $row)
                <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 sm:p-6 shadow-xs flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between text-xs text-[#64748b] mb-2 font-mono">
                      <span>Fan Letter No. {{ \App\Support\Capsule::formatNumber($row->number) }}</span>
                      <span class="text-[#047857] font-bold">{{ $row->milestone?->title ?? 'Milestone Stream' }}</span>
                    </div>

                    <blockquote class="font-serif italic text-base sm:text-lg text-[#0f172a] leading-relaxed my-3">
                      “{{ $row->teaser }}”
                    </blockquote>
                  </div>

                  <div class="pt-3 border-t border-[#e7e5df] flex items-center justify-between text-xs">
                    <div>
                      <strong class="text-[#0f172a]">{{ $row->name }}</strong>
                      <span class="text-[#64748b]">({{ $row->location }})</span>
                    </div>
                    <span class="text-xs font-mono text-[#64748b]">{{ $row->sealed_at->format('M Y') }}</span>
                  </div>
                </div>
              @endforeach
            @endif
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 5: EARNINGS & PAYOUT LEDGER -->
        <!-- ========================================== -->
        <div x-show="tab === 'earnings'" style="display:none;" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#e7e5df]">
            <div>
              <h2 class="font-serif text-2xl font-bold text-[#0f172a]">Earnings & Payout Ledger</h2>
              <p class="text-xs sm:text-sm text-[#64748b]">Direct creator payouts on every sealed fan letter and booster tip (from your ${{ number_format($creator->minPriceDollars() ?: 3, 2) }} floor up to $50+). Payouts transfer automatically via Stripe when you unseal your vault on stream.</p>
            </div>
            <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b]">
              Direct Creator Payouts
            </span>
          </div>

          <!-- 3 Revenue Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Total Creator Accrual</span>
              <div class="font-serif text-3xl font-bold text-[#047857]">
                ${{ number_format($paid + $pending, 2) }}
              </div>
              <span class="text-[11px] text-[#64748b] mt-1 block">From {{ $totalLetters }} letters sealed</span>
            </div>

            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Paid Out</span>
              <div class="font-serif text-3xl font-bold text-[#0f172a]">
                ${{ number_format($paid, 2) }}
              </div>
              <span class="text-[11px] text-[#047857] mt-1 block">{{ $paidN }} completed transfers</span>
            </div>

            <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 shadow-xs">
              <span class="text-xs font-bold uppercase tracking-wider text-[#64748b] block mb-1">Pending at Next Stream</span>
              <div class="font-serif text-3xl font-bold text-[#b45309]">
                ${{ number_format($pending, 2) }}
              </div>
              <span class="text-[11px] text-[#b45309] mt-1 block">{{ $pendingN }} held until reveal</span>
            </div>
          </div>

          <!-- Ledger Table -->
          <div class="bg-white border border-[#e7e5df] rounded-3xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-[#e7e5df] flex items-center justify-between">
              <h3 class="font-serif font-bold text-base text-[#0f172a]">Fan Contribution Ledger</h3>
              <span class="text-xs font-mono text-[#64748b]">Showing {{ $ledger->count() }} fan transactions</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-[#faf9f5] border-b border-[#e7e5df] text-xs font-bold text-[#64748b] uppercase tracking-wider">
                  <tr>
                    <th class="py-3 px-4 sm:px-6">Fan</th>
                    <th class="py-3 px-4 sm:px-6">Topic / Milestone</th>
                    <th class="py-3 px-4 sm:px-6">Their Message</th>
                    <th class="py-3 px-4 sm:px-6">Your Cut</th>
                    <th class="py-3 px-4 sm:px-6">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[#e7e5df]">
                  @if($ledger->count() === 0)
                    <tr>
                      <td colspan="5" class="py-10 text-center text-[#64748b] text-xs sm:text-sm">
                        No fan letters sealed through your door yet. Share your link to get started!
                      </td>
                    </tr>
                  @else
                    @foreach($ledger as $row)
                      <tr class="hover:bg-[#faf9f5]/50">
                        <td class="py-3 px-4 sm:px-6">
                          <strong class="block text-[#0f172a]">{{ $row->name }}</strong>
                          <span class="text-xs text-[#64748b]">{{ $row->location }}</span>
                        </td>
                        <td class="py-3 px-4 sm:px-6 font-semibold text-[#047857]">
                          {{ $row->milestone?->title ?? 'General Vault' }}
                        </td>
                        <td class="py-3 px-4 sm:px-6 max-w-xs truncate italic text-[#475569]">
                          “{{ $row->teaser }}”
                        </td>
                        <td class="py-3 px-4 sm:px-6 font-mono font-bold text-[#047857]">
                          +${{ number_format(($row->referral?->cut_cents ?? \App\Support\Capsule::calculateCreatorCut($row->referral?->amount_cents ?? 500)) / 100, 2) }}
                          <span class="block text-[10px] font-normal text-[#64748b]">(${{ number_format(($row->referral?->amount_cents ?? 500) / 100, 0) }} contribution)</span>
                        </td>
                        <td class="py-3 px-4 sm:px-6">
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ ($row->referral->status ?? 'pending') === 'paid' ? 'bg-[#ecfdf5] text-[#064e3b]' : 'bg-[#fefce8] text-[#854d0e]' }}">
                            {{ ucfirst($row->referral->status ?? 'pending') }}
                          </span>
                        </td>
                      </tr>
                    @endforeach
                  @endif
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ========================================== -->
        <!-- VIEW 6: VAULT SETTINGS & CHANNEL PROFILE -->
        <!-- ========================================== -->
        <div x-show="tab === 'settings'" style="display:none;" class="space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-2 border-b border-[#e7e5df]">
            <div>
              <h2 class="font-serif text-2xl font-bold text-[#0f172a]">Vault Profile & Settings</h2>
              <p class="text-xs sm:text-sm text-[#64748b]">Customize your public creator door, vanity link, and community welcome prompt.</p>
            </div>
          </div>

          <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-8 shadow-xs max-w-2xl">
            <div class="space-y-5">
              <div>
                <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
                  Creator or Channel Name <span class="text-red-500">*</span>
                </label>
                <input 
                  type="text" 
                  wire:model="editName" 
                  class="w-full bg-[#faf9f5] border border-[#e7e5df] focus:bg-white focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] outline-none"
                >
                @error('editName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
                    Channel Handle / Username <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold">@</span>
                    <input 
                      type="text" 
                      wire:model="editHandle" 
                      class="w-full pl-8 bg-[#faf9f5] border border-[#e7e5df] focus:bg-white focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] outline-none font-mono"
                    >
                  </div>
                  @error('editHandle') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                  <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
                    Primary Platform <span class="text-red-500">*</span>
                  </label>
                  <select wire:model="editPlatform" class="w-full bg-[#faf9f5] border border-[#e7e5df] focus:bg-white focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] outline-none">
                    <option value="YouTube">YouTube</option>
                    <option value="Twitch">Twitch</option>
                    <option value="TikTok">TikTok</option>
                    <option value="Podcast">Podcast</option>
                    <option value="Substack">Substack</option>
                    <option value="Kick">Kick</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
                  Minimum Fan Letter Price ($ USD) <span class="text-red-500">*</span>
                </label>
                <div class="relative max-w-xs">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                  <input 
                    type="number" 
                    min="3" 
                    max="1000" 
                    step="1"
                    wire:model="editMinPrice" 
                    class="w-full pl-8 pr-4 bg-[#faf9f5] border border-[#e7e5df] focus:bg-white focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] outline-none font-mono font-bold"
                  >
                </div>
                <span class="text-xs text-[#64748b] mt-1 block">
                  You set the amount for your fan letters (platform minimum is $3.00). Fans contribute at your chosen level or can add a booster/tip.
                </span>
                @error('editMinPrice') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
              </div>

              <div>
                <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
                  Community Welcome Prompt / Bio
                </label>
                <textarea 
                  wire:model="editBio" 
                  rows="3" 
                  placeholder="e.g. Late night gaming and cozy chats in Tokyo. Leave your favorite memory or milestone prediction!" 
                  class="w-full bg-[#faf9f5] border border-[#e7e5df] focus:bg-white focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] outline-none resize-none"
                ></textarea>
                <span class="text-xs text-[#64748b]">Shown prominently to your fans on your public vault door.</span>
              </div>

              <!-- Public Door Preview Box -->
              <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] flex items-center justify-between">
                <div>
                  <span class="block text-[11px] font-bold uppercase tracking-wider text-[#64748b] font-mono">Public Vanity URL</span>
                  <strong class="text-sm font-mono text-[#047857]">{{ url('/with/'.\App\Support\Capsule::slugify($editHandle ?: $creator->slug)) }}</strong>
                </div>
                <a href="{{ route('with', $creator->slug) }}" target="_blank" class="text-xs font-bold text-[#064e3b] underline">
                  Visit ↗
                </a>
              </div>

              <div class="pt-2">
                <button 
                  type="button" 
                  wire:click="updateSettings" 
                  wire:loading.attr="disabled"
                  class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm shadow-[0_4px_16px_rgba(6,78,59,0.3)] transition-all"
                >
                  <span wire:loading.remove wire:target="updateSettings">Save Profile Settings</span>
                  <span wire:loading wire:target="updateSettings">Saving…</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Elegant Minimalist Studio Footer -->
        <footer class="pt-8 pb-4 border-t border-[#e7e5df] mt-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#64748b]">
          <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-md bg-[#064e3b] text-white flex items-center justify-center font-mono font-bold text-[10px] tracking-tight">FV</span>
            <span class="font-semibold text-[#0f172a]">FanVault Studio</span>
            <span>·</span>
            <span>Creator Protocol v2.4</span>
            <span class="hidden sm:inline">·</span>
            <span class="hidden sm:inline">Secured via Stripe Connect</span>
          </div>
          <div class="flex items-center gap-3 font-mono text-[11px] text-[#64748b]">
            <a href="https://getfanvault.com" target="_blank" class="hover:text-[#047857] transition-colors">getfanvault.com</a>
            <span>·</span>
            <span class="inline-flex items-center gap-1.5 text-[#047857]">
              <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
              <span>Vault Protocol Active</span>
            </span>
          </div>
        </footer>
      </main>
    </div>
  </div>
@endif
