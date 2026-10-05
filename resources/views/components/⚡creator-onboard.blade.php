<?php

use App\Models\Creator;
use App\Services\CreatorAuthService;
use App\Support\Capsule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Complete Your Community Vault Setup — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Complete Your Vault Setup — FanVault',
            'robots' => 'noindex, nofollow',
        ]);
    }
    public string $email = '';

    public string $name = '';

    public string $handle = '';

    public string $platform = 'YouTube';

    public string $milestone_title = '';

    public string $unlock_date = '';

    public string $bio = '';

    public string $error = '';

    public function mount(CreatorAuthService $auth): void
    {
        $creator = $auth->current();

        if (! $creator) {
            $this->redirect(route('creators.access'), navigate: true);

            return;
        }

        if (! $creator->needsOnboarding()) {
            $this->redirect(route('creators.studio'), navigate: true);

            return;
        }

        $this->email = $creator->email;
        $this->name = $creator->name ?? '';
        $this->handle = ltrim($creator->handle ?? '', '@');
        $this->platform = $creator->platform ?: 'YouTube';
        $this->milestone_title = $creator->milestone_title ?? '';
        $this->unlock_date = $creator->unlock_date ? $creator->unlock_date->toDateString() : now()->addYears(2)->format('Y-01-01');
        $this->bio = $creator->bio ?? '';
    }

    public function save(CreatorAuthService $auth): mixed
    {
        $this->error = '';
        $creator = $auth->current();

        if (! $creator) {
            $this->redirect(route('creators.access'), navigate: true);

            return null;
        }

        $this->validate([
            'name' => 'required|string|max:120',
            'handle' => 'required|string|max:80',
            'platform' => 'required|string|max:40',
            'milestone_title' => 'required|string|max:120',
            'unlock_date' => 'required|date',
            'bio' => 'nullable|string|max:400',
        ]);

        $slug = Capsule::slugify($this->handle);
        if ($slug === '') {
            $this->addError('handle', 'Choose a handle with letters or numbers.');

            return null;
        }

        $taken = Creator::query()
            ->where('slug', $slug)
            ->where('id', '!=', $creator->id)
            ->exists();

        if ($taken) {
            $this->addError('handle', 'That handle is already taken by another creator.');

            return null;
        }

        $creator->update([
            'name' => trim($this->name),
            'handle' => '@' . ltrim(trim($this->handle), '@'),
            'slug' => $slug,
            'platform' => $this->platform,
            'milestone_title' => trim($this->milestone_title),
            'unlock_date' => $this->unlock_date,
            'bio' => trim($this->bio) ?: 'Leave a letter or prediction for our milestone stream. I will unseal the vault and read my favorites live!',
        ]);

        $milestone = $creator->milestones()->first();
        if ($milestone) {
            $milestone->update([
                'title' => trim($this->milestone_title),
                'unlock_date' => $this->unlock_date,
                'description' => trim($this->bio),
                'is_active' => true,
            ]);
        } else {
            $creator->milestones()->create([
                'title' => trim($this->milestone_title),
                'unlock_date' => $this->unlock_date,
                'description' => trim($this->bio),
                'is_active' => true,
            ]);
        }

        session([
            'creator_id' => $creator->id,
            'creator_slug' => $creator->slug,
            'creator_just_onboarded' => true,
        ]);

        return $this->redirect(route('creators.studio'), navigate: true);
    }

    public function slugPreview(): string
    {
        $raw = trim($this->handle) !== '' ? $this->handle : $this->name;

        return Capsule::slugify($raw) ?: 'your-channel';
    }
};
?>

<section class="max-w-xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
  <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
    <!-- Header -->
    <div class="flex items-center justify-between gap-2 pb-4 mb-6 border-b border-[#e7e5df]">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
        🎉 Magic Link Verified · Complete Setup
      </span>
      <span class="text-xs font-mono text-[#047857] font-semibold">
        ✓ {{ $email }}
      </span>
    </div>

    <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-1">
      Set up your community vault.
    </h1>
    <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
      Welcome to FanVault! Tell your audience who you are and what upcoming milestone you’re celebrating together.
    </p>

    @if($error)
      <div class="mb-5 p-3.5 rounded-2xl bg-red-50 text-red-600 text-xs font-bold border border-red-200">
        ⚠️ {{ $error }}
      </div>
    @endif

    <!-- Channel Name -->
    <div class="mb-5">
      <label for="onboard-name" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
        Creator or Channel Name <span class="text-red-500">*</span>
      </label>
      <input 
        id="onboard-name" 
        wire:model="name" 
        autocomplete="name" 
        placeholder="e.g. MKBHD, Lex Fridman, Maya in Tokyo" 
        class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none"
      >
      @error('name') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
    </div>

    <!-- Channel Handle / Username -->
    <div class="mb-5">
      <label for="onboard-handle" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
        Channel Handle / Username <span class="text-red-500">*</span>
      </label>
      <div class="relative flex items-center">
        <span class="absolute left-4 text-[#64748b] font-mono text-sm font-bold">@</span>
        <input 
          id="onboard-handle" 
          wire:model.live="handle" 
          autocomplete="username" 
          placeholder="mkbhd or techreview" 
          class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 pl-9 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none font-mono"
        >
      </div>
      <div class="text-xs text-[#64748b] mt-1.5 flex items-center gap-1">
        <span>🔗 Your public vault link:</span>
        <strong class="text-[#047857] font-mono">/with/{{ $this->slugPreview() }}</strong>
      </div>
      @error('handle') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
    </div>

    <!-- Platform Selector -->
    <div class="mb-5">
      <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
        Primary Platform <span class="text-red-500">*</span>
      </label>
      <div class="grid grid-cols-3 gap-2">
        @foreach(['YouTube' => '📺', 'Twitch' => '👾', 'TikTok' => '🎵', 'Podcast' => '🎙️', 'Substack' => '✍️', 'Kick' => '⚡'] as $plt => $icon)
          <button 
            type="button" 
            class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all flex items-center justify-center gap-1.5 {{ $platform === $plt ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-xs' : 'border border-[#e7e5df] bg-[#f5f4ee] text-[#475569] hover:bg-white' }}"
            wire:click="$set('platform', '{{ $plt }}')"
          >
            <span>{{ $icon }}</span>
            <span>{{ $plt }}</span>
          </button>
        @endforeach
      </div>
      @error('platform') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
    </div>

    <!-- Milestone Title -->
    <div class="mb-5">
      <label for="onboard-milestone" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
        Upcoming Milestone Celebration <span class="text-red-500">*</span>
      </label>
      <input 
        id="onboard-milestone" 
        wire:model="milestone_title" 
        placeholder="e.g. 100k Community Milestone Stream, 5-Year Anniversary, Episode #500" 
        class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none"
      >
      <p class="text-xs text-[#64748b] mt-1">This is the celebration your community is locking letters for.</p>
      @error('milestone_title') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
    </div>

    <!-- Target Unlock Date -->
    <div class="mb-5">
      <label for="onboard-date" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
        Target Unlock / Stream Date <span class="text-red-500">*</span>
      </label>
      <input 
        id="onboard-date" 
        type="date" 
        wire:model="unlock_date" 
        class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none"
      >
      <p class="text-xs text-[#64748b] mt-1">Approximate date when you'll unseal the vault live on stream. You can adjust this later in Studio.</p>
      @error('unlock_date') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
    </div>

    <!-- Community Prompt / Bio Quote -->
    <div class="mb-6">
      <label for="onboard-bio" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
        Community Welcome Prompt (Optional)
      </label>
      <textarea 
        id="onboard-bio" 
        wire:model="bio" 
        rows="3" 
        placeholder="What would you tell me on stream? Write your letter or milestone prediction below! I will read my favorites live on broadcast." 
        class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none"
      ></textarea>
      @error('bio') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
    </div>

    <!-- Action Buttons -->
    <div class="pt-2">
      <button 
        class="w-full inline-flex items-center justify-center gap-2 px-8 py-4 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all" 
        type="button" 
        wire:click="save"
        wire:loading.attr="disabled"
      >
        <span wire:loading.remove wire:target="save">🚀 Launch Community Vault & Enter Studio</span>
        <span wire:loading wire:target="save">Saving Vault…</span>
      </button>
    </div>
  </div>
</section>
