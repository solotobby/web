<?php

use App\Models\Creator;
use App\Support\Capsule;
use App\Services\CreatorAuthService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Create Your Creator Vault — FanVault')]
class extends Component
{
    public string $name = '';

    public string $handle = '';

    public string $email = '';

    public string $platform = 'YouTube';

    public string $milestone_title = '';

    public string $unlock_date = '';

    public string $bio = '';

    public int $step = 1;

    public string $error = '';

    public function mount(): void
    {
        if (session('creator_id')) {
            $this->redirect(route('creators.studio'), navigate: true);
        }

        if (! $this->unlock_date) {
            $this->unlock_date = now()->addYears(2)->format('Y-01-01');
        }
    }

    public function next(): void
    {
        $this->error = '';
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required|string|max:120',
                'handle' => 'required|string|max:80',
                'platform' => 'required|string|max:40',
            ]);

            $slug = Capsule::slugify($this->handle);
            if ($slug === '') {
                $this->addError('handle', 'Choose a handle with letters or numbers.');

                return;
            }
            if (Creator::query()->where('slug', $slug)->exists()) {
                $this->addError('handle', 'That handle is already taken by another creator.');

                return;
            }
        } elseif ($this->step === 2) {
            $this->validate([
                'milestone_title' => 'required|string|max:120',
                'unlock_date' => 'required|date',
                'bio' => 'nullable|string|max:400',
            ]);
        }

        $this->step = min(3, $this->step + 1);
    }

    public function back(): void
    {
        $this->error = '';
        $this->step = max(1, $this->step - 1);
    }

    public function join(CreatorAuthService $auth): mixed
    {
        $this->error = '';
        $this->validate([
            'name' => 'required|string|max:120',
            'handle' => 'required|string|max:80',
            'email' => 'required|email|max:190',
            'platform' => 'required|string|max:40',
            'milestone_title' => 'required|string|max:120',
            'unlock_date' => 'required|date',
            'bio' => 'nullable|string|max:400',
        ]);

        $slug = Capsule::slugify($this->handle);
        if ($slug === '') {
            $this->error = 'Choose a handle with letters or numbers.';
            $this->step = 1;

            return null;
        }

        if (Creator::query()->where('slug', $slug)->exists()) {
            $this->error = 'That handle is taken.';
            $this->step = 1;

            return null;
        }

        $email = strtolower(trim($this->email));
        if (Creator::query()->where('email', $email)->exists()) {
            $this->error = 'That email already has a vault. Open with email instead.';

            return null;
        }

        $creator = Creator::query()->create([
            'id' => (string) Str::uuid(),
            'name' => trim($this->name),
            'handle' => trim($this->handle),
            'slug' => $slug,
            'platform' => $this->platform,
            'milestone_title' => trim($this->milestone_title),
            'unlock_date' => $this->unlock_date,
            'bio' => trim($this->bio) ?: 'Leave a letter or prediction for our milestone stream. I will unseal the vault and read my favorites live!',
            'email' => $email,
            'joined_at' => now(),
        ]);

        $creator->milestones()->create([
            'title' => trim($this->milestone_title),
            'unlock_date' => $this->unlock_date,
            'description' => trim($this->bio),
            'is_active' => true,
        ]);

        $auth->login($creator);

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
    <!-- Stepper indicator -->
    <div class="flex items-center justify-between gap-2 pb-4 mb-6 border-b border-[#e7e5df]">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
        🎙️ Creator Vault Setup · Step 0{{ $step }} / 03
      </span>
      <span class="text-xs text-[#64748b] font-medium">Free forever · Keep 80% of all seals & tips</span>
    </div>

    @if($error)
      <div class="mb-5 p-3.5 rounded-2xl bg-red-50 text-red-600 text-xs font-bold border border-red-200">
        ⚠️ {{ $error }}
      </div>
    @endif

    @if($step === 1)
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-1">
        Who is opening this vault?
      </h1>
      <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
        Your channel or creator identity. This is what your fans will see on your public vault door.
      </p>

      <div class="mb-5">
        <label for="creator-name" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Creator or Channel Name
        </label>
        <input id="creator-name" wire:model="name" autocomplete="name" placeholder="e.g. MKBHD, Lex Fridman, Amara Okoye" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        @error('name') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="mb-5">
        <label for="creator-handle" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Channel Handle / Username
        </label>
        <input id="creator-handle" wire:model.live="handle" autocomplete="username" placeholder="e.g. mkbhd or techreview" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        <div class="text-xs text-[#64748b] mt-1">
          Your vault link · <strong class="text-[#047857]">/with/{{ $this->slugPreview() }}</strong>
        </div>
        @error('handle') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="mb-6">
        <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">Primary Platform</label>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
          @foreach(['YouTube','Twitch','TikTok','Podcast','Substack','Kick'] as $p)
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $platform === $p ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-xs' : 'border border-[#e7e5df] bg-[#f5f4ee] text-[#475569] hover:bg-white' }}" wire:click="$set('platform', '{{ $p }}')">
              @switch($p)
                @case('YouTube') 📺 @break
                @case('Twitch') 👾 @break
                @case('TikTok') 🎵 @break
                @case('Podcast') 🎙️ @break
                @case('Substack') ✍️ @break
                @case('Kick') ⚡ @break
              @endswitch
              {{ $p }}
            </button>
          @endforeach
        </div>
      </div>

      <div class="pt-2">
        <button class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm sm:text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 transition-all" type="button" wire:click="next">
          Next: Set Your Milestone →
        </button>
      </div>
      <p class="text-xs text-[#64748b] mt-4">Already have a vault? <a href="{{ route('creators.access') }}" class="text-[#047857] font-semibold hover:underline">Log in with email</a></p>

    @elseif($step === 2)
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-1">
        Your Milestone & Reveal Date
      </h1>
      <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
        What celebration will you unseal this vault for? When will you open it live on stream?
      </p>

      <div class="mb-5">
        <label for="milestone-title" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Milestone Event Title
        </label>
        <input id="milestone-title" wire:model="milestone_title" placeholder="e.g. 5-Year Channel Anniversary, 100k Subs Special, Episode 500" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        @error('milestone_title') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="mb-5">
        <label for="unlock-date" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Target Reveal / Stream Date
        </label>
        <input id="unlock-date" type="date" wire:model="unlock_date" min="{{ now()->addDays(7)->toDateString() }}" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        <div class="text-xs text-[#64748b] mt-1">
          Pick the target date you plan to unseal the vault. Fans’ letters will count down to this day.
        </div>
        @error('unlock_date') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="mb-6">
        <label for="creator-bio" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Prompt for Your Fans (Bio)
        </label>
        <textarea id="creator-bio" wire:model="bio" rows="3" placeholder="Leave a letter, prediction, or memory for our milestone stream. I will read my favorites live on video!" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none resize-none"></textarea>
        @error('bio') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="flex items-center justify-between gap-3 pt-2">
        <button class="px-6 py-3 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-bold text-sm transition-all" type="button" wire:click="back">← Back</button>
        <button class="px-8 py-3.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm sm:text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 transition-all" type="button" wire:click="next">
          Next: Email Access →
        </button>
      </div>

    @else
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-1">
        Your email is your studio key.
      </h1>
      <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
        No password to remember. Use this email whenever you need to open your Creator Studio — check letters, read submissions, and claim your payouts.
      </p>

      <div class="bg-[#f5f4ee] border border-[#e7e5df] rounded-2xl p-4 mb-5 text-center" aria-hidden="true">
        <span class="block text-xs text-[#64748b] uppercase tracking-wider font-semibold">{{ $name ?: 'Your Name' }}</span>
        <strong class="block font-mono text-base text-[#047857] my-1">/with/{{ $this->slugPreview() }}</strong>
        <em class="block text-xs not-italic text-[#475569]">{{ $milestone_title ?: 'Community Milestone' }} · {{ $platform }}</em>
      </div>

      <div class="mb-6">
        <label for="creator-email" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">Email address</label>
        <input id="creator-email" type="email" wire:model="email" autocomplete="email" placeholder="you@example.com" wire:keydown.enter="join" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        <div class="text-xs text-[#64748b] mt-1">
          We will send your studio magic links to this inbox.
        </div>
        @error('email') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="flex items-center justify-between gap-3 pt-2">
        <button class="px-6 py-3 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-bold text-sm transition-all" type="button" wire:click="back">← Back</button>
        <button class="px-8 py-3.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm sm:text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 transition-all" type="button" wire:click="join" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="join">Launch My Vault 📬</span>
          <span wire:loading wire:target="join">Launching…</span>
        </button>
      </div>
      <p class="text-xs text-[#64748b] text-center mt-6">Every fan who seals through your door credits you 80% of their contribution (minimum $3 floor, with superfan booster tips up to $50+).</p>
    @endif
  </div>
</section>

