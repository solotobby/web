<?php

use App\Models\CapsuleFollower;
use App\Models\Creator;
use App\Models\Milestone;
use App\Support\Capsule;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Community Time Capsule')]
class extends Component
{
    public string $slug = '';
    public ?string $milestoneId = null;
    public int $selectedAmount = 5;
    public ?string $customAmount = null;
    public string $selectedType = 'message';
    public string $followerEmail = '';
    public bool $followSuccess = false;

    public function selectType(string $type): void
    {
        if (in_array($type, ['message', 'prediction', 'memory', 'photo'], true)) {
            $this->selectedType = $type;
        }
    }

    public function followCapsule(): void
    {
        $this->validate([
            'followerEmail' => 'required|email|max:190',
        ]);

        $creator = Creator::query()->where('slug', $this->slug)->firstOrFail();

        CapsuleFollower::firstOrCreate([
            'creator_id' => $creator->id,
            'email' => strtolower(trim($this->followerEmail)),
        ], [
            'milestone_id' => $this->milestoneId ?: $creator->activeMilestone()?->id,
        ]);

        $this->followSuccess = true;
        $this->followerEmail = '';
    }

    public function mount(string $slug): void
    {
        $this->slug = Capsule::slugify($slug);
        $creator = Creator::query()->where('slug', $this->slug)->first();
        if ($creator) {
            session(['ref_slug' => $creator->slug]);
            $this->selectedAmount = (int) ($creator->minPriceDollars() ?: 5);
        }

        if (request()->query('milestone')) {
            $this->milestoneId = (string) request()->query('milestone');
        }

        if (request()->query('type') && in_array(request()->query('type'), ['message', 'prediction', 'memory', 'photo'], true)) {
            $this->selectedType = (string) request()->query('type');
        }

        if (request()->query('amount')) {
            $amt = (int) request()->query('amount');
            $min = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
            if ($amt >= $min) {
                $this->selectedAmount = min(1000, $amt);
            }
        }
    }

    public function setAmount(int $amt): void
    {
        $creator = Creator::query()->where('slug', $this->slug)->first();
        $min = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
        $this->selectedAmount = max($min, $amt);
        $this->customAmount = null;
    }

    public function updatedCustomAmount(): void
    {
        if (is_numeric($this->customAmount)) {
            $val = (int) $this->customAmount;
            $creator = Creator::query()->where('slug', $this->slug)->first();
            $min = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
            if ($val >= $min) {
                $this->selectedAmount = min(1000, $val);
            }
        }
    }

    public function selectMilestone(string $id): void
    {
        $this->milestoneId = $id;
    }

    public function rendering($view): void
    {
        $creator = Creator::query()->where('slug', $this->slug)->first();
        if ($creator) {
            $active = $this->milestoneId 
                ? $creator->milestones()->where('id', $this->milestoneId)->first() 
                : $creator->activeMilestone();

            $milestoneTitle = $active?->title ?? $creator->milestone_title ?? 'Community Milestone';
            $unlockDate = $active?->formattedUnlockDate() ?? $creator->formattedUnlockDate();

            $view->layoutData([
                'title' => $creator->name . '’s Time Capsule (' . $milestoneTitle . ') — FanVault',
                'description' => 'Leave a message, memory, or prediction in ' . $creator->name . '’s ' . $milestoneTitle . ' Time Capsule. Sealed until the milestone reveal stream.',
                'canonicalUrl' => route('with', $creator->slug),
                'ogTitle' => $creator->name . '’s Time Capsule (' . $milestoneTitle . ')',
                'ogDescription' => 'Leave memories & predictions for ' . $creator->name . '. Sealed until the milestone stream: ' . $unlockDate . '.',
                'ogUrl' => route('with', $creator->slug),
                'ogImage' => route('og.creator', $creator->slug),
                'schemaJson' => \App\Support\Seo::toJson(\App\Support\Seo::creatorSchema($creator)),
            ]);
        }
    }

    public function with(): array
    {
        $creator = Creator::query()->where('slug', $this->slug)->first();
        $letters = collect();
        $milestones = collect();
        $activeMilestone = null;
        $daysUntil = null;
        $followersCount = 0;

        if ($creator) {
            $milestones = $creator->milestones()->withCount('postcards')->get();

            if ($this->milestoneId) {
                $activeMilestone = $milestones->where('id', $this->milestoneId)->first();
            }

            if (! $activeMilestone) {
                $activeMilestone = $milestones->where('is_active', true)->first() ?: $milestones->first();
            }

            $lettersQuery = $creator->postcards()->with('envelope')->latest('sealed_at');
            if ($activeMilestone && $milestones->count() > 1) {
                $letters = $lettersQuery->where(function ($q) use ($activeMilestone) {
                    $q->where('milestone_id', $activeMilestone->id)->orWhereNull('milestone_id');
                })->take(18)->get();
            } else {
                $letters = $lettersQuery->take(18)->get();
            }

            $targetDate = $activeMilestone && $activeMilestone->unlock_date 
                ? $activeMilestone->unlock_date->startOfDay() 
                : ($creator->unlock_date ? $creator->unlock_date->startOfDay() : Carbon::parse('2028-01-01'));

            $daysUntil = max(0, (int) now()->diffInDays($targetDate, false));
            $followersCount = $creator->followers()->count();
        }

        $minDollars = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
        $tierName = match(true) {
            $this->selectedAmount >= 50 => '👑 VIP Vault Patron',
            $this->selectedAmount >= 25 => '✨ Superfan Booster',
            $this->selectedAmount >= 10 => '⭐ Channel Supporter',
            default => '🛡️ Standard Keepsake',
        };
        $tierPerk = match(true) {
            $this->selectedAmount >= 50 => 'Royal Obsidian foil certificate + Stream shoutout & pinned recognition',
            $this->selectedAmount >= 25 => 'Gold holographic digital foil + Stream highlight callout during live reveal',
            $this->selectedAmount >= 10 => 'Bronze metallic foil pass + Priority queue in stream reader',
            default => 'Permanent encrypted archival storage + Numbered keepsake certificate',
        };

        return [
            'creator' => $creator,
            'milestones' => $milestones,
            'activeMilestone' => $activeMilestone,
            'letters' => $letters,
            'daysUntil' => $daysUntil,
            'link' => url('/with/'.$this->slug),
            'minDollars' => $minDollars,
            'tierName' => $tierName,
            'tierPerk' => $tierPerk,
            'selectedType' => $this->selectedType,
            'followersCount' => $followersCount,
        ];
    }
};
?>

@if(! $creator)
  <section class="max-w-2xl mx-auto px-4 py-16 text-center">
    <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] mb-3">
      📬 Creator Vault
    </span>
    <h1 class="font-serif text-3xl sm:text-4xl font-bold text-[#0f172a] mb-3">
      Vault Not Found
    </h1>
    <p class="text-[#475569] mb-6">
      This creator hasn’t launched a community time vault yet, or the link has changed.
    </p>
    <a href="{{ route('creators') }}" class="inline-flex items-center px-6 py-3 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm shadow-[0_4px_16px_rgba(6,78,59,0.3)] transition-all">
      Browse Active Creator Vaults
    </a>
  </section>
@else
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10" x-data="{ copied: false, copy() { navigator.clipboard.writeText(window.location.href); this.copied = true; setTimeout(() => this.copied = false, 2500); } }">
    <!-- Hero Profile Box -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.04)] mb-10 relative overflow-hidden">
      <!-- Top Badges -->
      <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-2">
          @foreach($creator->platformsList() as $plt)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
              <span>
                @switch($plt)
                  @case('YouTube') 📺 @break
                  @case('Twitch') 👾 @break
                  @case('Podcast') 🎙️ @break
                  @case('TikTok') 🎵 @break
                  @case('Kick') ⚡ @break
                  @case('Instagram') 📸 @break
                  @case('X / Twitter') 𝕏 @break
                  @case('Substack') ✍️ @break
                  @default 🌐
                @endswitch
              </span>
              {{ $plt }}
            </span>
          @endforeach
          <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-mono font-bold bg-[#f5f4ee] text-[#334155] border border-[#e7e5df]">
            {{ $creator->handle }}
          </span>
        </div>

        <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-[#fefce8] text-[#854d0e] border border-[#fde047]">
          🏆 {{ $activeMilestone?->title ?? $creator->milestone_title ?? 'Community Milestone' }}
        </span>
      </div>

      <!-- Main Headline & Bio -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
        <div class="lg:col-span-8 space-y-4">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] text-xs font-mono font-medium">
            <span class="w-2 h-2 rounded-full bg-[#047857] animate-pulse"></span>
            <span>🔒 SEALED · Unsealed live when {{ $creator->name }} reaches {{ $activeMilestone?->title ?? $creator->milestone_title ?? 'Milestone' }}</span>
          </div>

          <h1 class="font-serif text-3xl sm:text-5xl font-normal text-[#0f172a] leading-tight">
            {{ $creator->name }}’s <br class="hidden sm:inline">
            <span class="italic text-[#047857]">{{ $activeMilestone?->title ?? $creator->milestone_title ?? 'Community' }} Time Capsule</span>.
          </h1>

          <p class="font-serif italic text-base sm:text-lg text-[#334155] leading-relaxed bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-2xl">
            “{{ ($activeMilestone?->description) ?: ($creator->bio ?: 'Leave a message, prediction, or memory for our milestone stream. I will unseal the capsule and read my favorites live on video!') }}”
          </p>

          <!-- Milestone Condition & Progress -->
          <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] my-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs font-mono mb-1.5">
              <span class="text-[#047857] font-bold">🎯 Milestone Goal: {{ $activeMilestone?->title ?? $creator->milestone_title ?? 'Milestone Stream' }}</span>
              <span class="text-[#64748b]">Target: {{ $activeMilestone?->formattedUnlockDate() ?? $creator->formattedUnlockDate() }}</span>
            </div>
            <div class="text-xs text-[#334155] font-medium flex items-center justify-between">
              <span><strong>{{ $letters->count() }}</strong> contributions sealed so far</span>
              <span class="text-[#047857] font-mono">🔔 {{ $followersCount }} following</span>
            </div>
          </div>

          <!-- Multiple Topics & Milestones Selection Ribbon (if creator has multiple) -->
          @if($milestones->count() > 1)
            <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] my-3">
              <span class="block text-xs font-bold uppercase tracking-wider text-[#047857] mb-2 font-mono">
                🎯 Active Topics & Milestones ({{ $milestones->count() }}) — Choose a Topic to Answer:
              </span>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($milestones as $m)
                  <button 
                    type="button" 
                    wire:click="selectMilestone('{{ $m->id }}')" 
                    class="p-2.5 rounded-xl text-left transition-all border {{ ($activeMilestone && $activeMilestone->id === $m->id) ? 'bg-white border-2 border-[#047857] shadow-xs' : 'bg-white/60 hover:bg-white border-[#e7e5df]' }}"
                  >
                    <div class="flex items-center justify-between text-xs font-bold mb-0.5">
                      <span class="{{ ($activeMilestone && $activeMilestone->id === $m->id) ? 'text-[#064e3b]' : 'text-[#0f172a]' }} truncate">
                        {{ $m->title }}
                      </span>
                      <span class="font-mono text-[11px] text-[#047857] shrink-0 ml-1">
                        {{ $m->postcards_count }} sealed
                      </span>
                    </div>
                    <div class="text-[11px] text-[#64748b] flex items-center justify-between">
                      <span>{{ $m->formattedUnlockDate() }}</span>
                      @if($m->daysRemaining() !== null)
                        <span class="font-mono text-[#64748b]">({{ $m->daysRemaining() }}d)</span>
                      @endif
                    </div>
                  </button>
                @endforeach
              </div>
            </div>
          @endif

          <!-- Contribution Type Picker -->
          <div class="my-4">
            <span class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#047857] mb-2">
              What would you like to leave for {{ explode(' ', $creator->name)[0] }}?
            </span>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button type="button" wire:click="selectType('message')" class="p-3 rounded-2xl border text-left transition-all {{ $selectedType === 'message' ? 'bg-[#ecfdf5] border-2 border-[#047857] shadow-xs' : 'bg-white border-[#e7e5df] hover:bg-[#faf9f5]' }}">
                <span class="text-base block mb-0.5">💌</span>
                <strong class="text-xs block text-[#0f172a]">A Message</strong>
                <span class="text-[10px] text-[#64748b] block mt-0.5">Gratitude & advice</span>
              </button>
              <button type="button" wire:click="selectType('prediction')" class="p-3 rounded-2xl border text-left transition-all {{ $selectedType === 'prediction' ? 'bg-[#ecfdf5] border-2 border-[#047857] shadow-xs' : 'bg-white border-[#e7e5df] hover:bg-[#faf9f5]' }}">
                <span class="text-base block mb-0.5">🔮</span>
                <strong class="text-xs block text-[#0f172a]">A Prediction</strong>
                <span class="text-[10px] text-[#64748b] block mt-0.5">Channel forecast</span>
              </button>
              <button type="button" wire:click="selectType('memory')" class="p-3 rounded-2xl border text-left transition-all {{ $selectedType === 'memory' ? 'bg-[#ecfdf5] border-2 border-[#047857] shadow-xs' : 'bg-white border-[#e7e5df] hover:bg-[#faf9f5]' }}">
                <span class="text-base block mb-0.5">❤️</span>
                <strong class="text-xs block text-[#0f172a]">A Memory</strong>
                <span class="text-[10px] text-[#64748b] block mt-0.5">Favorite moment</span>
              </button>
              <button type="button" wire:click="selectType('photo')" class="p-3 rounded-2xl border text-left transition-all {{ $selectedType === 'photo' ? 'bg-[#ecfdf5] border-2 border-[#047857] shadow-xs' : 'bg-white border-[#e7e5df] hover:bg-[#faf9f5]' }}">
                <span class="text-base block mb-0.5">📸</span>
                <strong class="text-xs block text-[#0f172a]">Photo / Note</strong>
                <span class="text-[10px] text-[#64748b] block mt-0.5">Fan art or relic</span>
              </button>
            </div>
          </div>

          <!-- Choose How Much to Give / Contribution Selector -->
          <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-4 sm:p-5 my-3 shadow-2xs">
            <div class="flex items-center justify-between gap-2 mb-2">
              <span class="block text-xs font-bold uppercase tracking-wider text-[#047857] font-mono">
                🎁 Choose How Much You'd Like to Give {{ $creator->name }}:
              </span>
              <span class="text-xs font-mono font-bold text-[#064e3b] bg-[#ecfdf5] border border-[#a7f3d0] px-3 py-1 rounded-full">
                ${{ number_format($selectedAmount, 2) }}
              </span>
            </div>

            <p class="text-xs text-[#64748b] mb-3">
              Your contribution directly supports {{ $creator->name }}. Generous superfans add booster amounts for unsealing stream recognition perks.
            </p>

            <!-- Quick Amount Chips -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
              <button 
                type="button" 
                wire:click="setAmount({{ $minDollars }})" 
                class="p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 {{ $selectedAmount === $minDollars && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]' }}"
              >
                <span class="block text-[10px] uppercase font-mono opacity-80">Standard</span>
                <strong class="text-sm sm:text-base font-mono font-bold">${{ $minDollars }}</strong>
                <span class="text-[10px] block opacity-75 mt-0.5">Floor</span>
              </button>

              @if($minDollars < 10)
                <button 
                  type="button" 
                  wire:click="setAmount(10)" 
                  class="p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 {{ $selectedAmount === 10 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]' }}"
                >
                  <span class="block text-[10px] uppercase font-mono opacity-80">Supporter</span>
                  <strong class="text-sm sm:text-base font-mono font-bold">$10</strong>
                  <span class="text-[10px] block opacity-75 mt-0.5">Bronze Foil</span>
                </button>
              @endif

              <button 
                type="button" 
                wire:click="setAmount(25)" 
                class="p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 {{ $selectedAmount === 25 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#fefce8] text-[#0f172a] border-[#e7e5df]' }}"
              >
                <span class="block text-[10px] uppercase font-mono opacity-80 text-amber-500 {{ $selectedAmount === 25 && ! $customAmount ? 'text-amber-200' : '' }}">Superfan</span>
                <strong class="text-sm sm:text-base font-mono font-bold">$25</strong>
                <span class="text-[10px] block opacity-75 mt-0.5 text-amber-600 {{ $selectedAmount === 25 && ! $customAmount ? 'text-amber-200' : '' }}">★ Gold Foil</span>
              </button>

              <button 
                type="button" 
                wire:click="setAmount(50)" 
                class="p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 {{ $selectedAmount === 50 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#faf5ff] text-[#0f172a] border-[#e7e5df]' }}"
              >
                <span class="block text-[10px] uppercase font-mono opacity-80 text-purple-500 {{ $selectedAmount === 50 && ! $customAmount ? 'text-purple-200' : '' }}">VIP Patron</span>
                <strong class="text-sm sm:text-base font-mono font-bold">$50</strong>
                <span class="text-[10px] block opacity-75 mt-0.5 text-purple-600 {{ $selectedAmount === 50 && ! $customAmount ? 'text-purple-200' : '' }}">👑 VIP Crown</span>
              </button>
            </div>

            <!-- Custom amount input & dynamic perk banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-[#e7e5df]">
              <div class="text-xs text-[#334155]">
                <strong class="text-[#047857]">{{ $tierName }}:</strong> {{ $tierPerk }}
              </div>

              <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-[#64748b] whitespace-nowrap">Or custom:</span>
                <div class="relative w-32">
                  <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">$</span>
                  <input 
                    type="number" 
                    min="{{ $minDollars }}" 
                    max="1000" 
                    placeholder="{{ $minDollars }}+" 
                    wire:model.live.debounce.300ms="customAmount"
                    class="w-full pl-6 pr-2 py-1.5 bg-white border border-[#e7e5df] focus:border-[#047857] rounded-xl text-xs font-mono outline-none shadow-2xs"
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Follow Capsule / Notify Me When It Opens Widget -->
          <div class="bg-gradient-to-br from-[#ecfdf5] to-[#f0fdf4] border border-[#a7f3d0] rounded-2xl p-4 sm:p-5 my-4">
            <div class="flex items-start gap-3">
              <div class="w-9 h-9 rounded-xl bg-white border border-[#a7f3d0] flex items-center justify-center text-base shrink-0 shadow-2xs">
                🔔
              </div>
              <div class="flex-1">
                <h3 class="text-xs sm:text-sm font-bold text-[#064e3b]">
                  Follow {{ $creator->name }}'s Time Capsule
                </h3>
                <p class="text-xs text-[#064e3b]/80 mt-0.5 leading-relaxed">
                  Be the first to know when {{ $creator->name }} unseals this Time Capsule live on stream. We'll send you the livestream broadcast link the moment it opens!
                </p>

                @if($followSuccess)
                  <div class="mt-2.5 text-xs font-semibold text-[#047857] bg-white px-3.5 py-2 rounded-xl border border-[#a7f3d0] flex items-center gap-1.5">
                    <span>✓</span>
                    <span>You are following this Time Capsule! We'll alert you the moment {{ $creator->name }} unseals it.</span>
                  </div>
                @else
                  <form wire:submit.prevent="followCapsule" class="mt-2.5 flex flex-col sm:flex-row items-center gap-2">
                    <input 
                      type="email" 
                      wire:model="followerEmail" 
                      placeholder="Enter your email for opening alerts..." 
                      required 
                      class="w-full sm:w-64 px-3.5 py-2 rounded-xl bg-white border border-[#a7f3d0] text-xs text-[#0f172a] placeholder-[#94a3b8] outline-none focus:border-[#047857]"
                    >
                    <button 
                      type="submit" 
                      class="w-full sm:w-auto px-4 py-2 rounded-xl bg-[#064e3b] hover:bg-[#047857] text-white text-xs font-bold transition-all shadow-xs shrink-0"
                    >
                      Notify Me When It Opens
                    </button>
                  </form>
                @endif
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
            <a 
              href="{{ route('seal', array_filter(['ref' => $creator->slug, 'milestone' => $activeMilestone?->id, 'amount' => $selectedAmount, 'type' => $selectedType])) }}" 
              class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all"
            >
              Leave {{ match($selectedType) { 'prediction' => 'a Prediction', 'memory' => 'a Memory', 'photo' => 'a Photo', default => 'a Message' } }} (${{ $selectedAmount }})
            </a>
            <button type="button" @click="copy()" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 sm:py-4 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm hover:-translate-y-0.5 transition-all shadow-xs">
              <span x-show="!copied">Share Capsule Link</span>
              <span x-show="copied" style="display:none;">✓ Copied to Clipboard!</span>
            </button>
          </div>
        </div>

        <!-- Right Side Stat Summary Card -->
        <aside class="lg:col-span-4 bg-white border border-[#e7e5df] rounded-3xl p-6 text-center shadow-xs space-y-4">
          <div>
            <span class="block text-xs font-mono uppercase tracking-wider text-[#64748b] mb-1">
              {{ $activeMilestone ? $activeMilestone->title : 'Time Capsule Archive' }}
            </span>
            <strong class="font-serif text-4xl sm:text-5xl font-normal text-[#047857] block my-1">
              {{ $activeMilestone ? $activeMilestone->postcards()->count() : $creator->postcards()->count() }}
            </strong>
            <span class="text-xs font-medium text-[#64748b] block">community contributions sealed</span>
            <div class="flex items-center justify-center gap-1.5 text-[10px] font-mono text-[#064e3b] mt-2">
              <span class="bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]">💌 Messages</span>
              <span class="bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]">🔮 Predictions</span>
              <span class="bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]">❤️ Memories</span>
            </div>
          </div>

          <div class="pt-3 border-t border-[#e7e5df]">
            <span class="block text-[11px] font-mono uppercase tracking-wider text-[#64748b] mb-0.5">Opening Stream</span>
            <strong class="text-sm font-semibold text-[#0f172a] block">
              {{ $activeMilestone ? $activeMilestone->formattedUnlockDate() : $creator->formattedUnlockDate() }}
            </strong>
            @if($daysUntil !== null)
              <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]/70 font-mono">
                {{ $daysUntil }} days remaining
              </span>
            @endif
            <div class="text-[11px] text-[#047857] font-mono mt-1">
              🔔 {{ $followersCount }} fans awaiting reveal
            </div>
          </div>

          <div class="pt-3 border-t border-[#e7e5df] text-[11px] text-[#64748b]">
            From ${{ $creator->minPriceDollars() }} · Set by {{ $creator->name }} · Direct support for {{ $creator->name }}
          </div>
        </aside>
      </div>
    </section>

    <!-- Community Fan Wall for this Creator -->
    <section class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-[#e7e5df] pb-4">
        <div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]/70 mb-2">
            Public Sealed Teasers
          </span>
          <h2 class="font-serif text-2xl sm:text-3xl font-normal text-[#0f172a]">
            Inside {{ $creator->name }}’s Time Capsule
          </h2>
          <p class="text-xs sm:text-sm text-[#64748b] mt-1">
            @if($activeMilestone)
              Showing contributions for <strong>{{ $activeMilestone->title }}</strong>. The full contents remain sealed until the reveal stream.
            @else
              Read what community members wrote today. The full contents remain sealed until the reveal stream.
            @endif
          </p>
        </div>

        <a href="{{ route('seal', array_filter(['ref' => $creator->slug, 'milestone' => $activeMilestone?->id])) }}" class="inline-flex items-center gap-1 text-xs sm:text-sm font-semibold text-[#047857] hover:underline shrink-0">
          Leave something →
        </a>
      </div>

      @if($letters->isEmpty())
        <div class="bg-white border border-[#e7e5df] rounded-3xl p-8 sm:p-12 text-center my-6 shadow-2xs">
          <h3 class="font-serif text-xl sm:text-2xl font-normal text-[#0f172a] mb-2">
            This Time Capsule is waiting for its first contribution.
          </h3>
          <p class="text-xs sm:text-sm text-[#64748b] max-w-md mx-auto mb-6">
            Be the founding community member to seal a message for {{ $creator->name }}’s {{ $activeMilestone ? $activeMilestone->title : 'milestone' }}.
          </p>
          <a href="{{ route('seal', array_filter(['ref' => $creator->slug, 'milestone' => $activeMilestone?->id, 'amount' => $selectedAmount])) }}" class="inline-flex items-center px-6 py-3 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm shadow-[0_2px_10px_rgba(6,78,59,0.25)] transition-all">
            Leave the First Contribution (${{ $selectedAmount }})
          </a>
        </div>
      @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          @foreach($letters as $msg)
            <article class="bg-white border border-[#e7e5df] hover:border-[#047857]/30 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]/70">
                    No. {{ \App\Support\Capsule::formatNumber($msg->number) }}
                  </span>
                  <span class="text-[11px] font-mono text-[#64748b]">
                    {{ $msg->sealed_at->format('j M Y') }}
                  </span>
                </div>

                <blockquote class="font-serif italic text-base sm:text-lg text-[#0f172a] leading-relaxed my-3">
                  “{{ $msg->teaser ?: 'Wish you were here for the milestone!' }}”
                </blockquote>
              </div>

              <div class="pt-3 border-t border-[#e7e5df] mt-3 flex items-center justify-between text-xs">
                <div>
                  <span class="font-semibold text-[#0f172a] block">{{ $msg->name }}</span>
                  <span class="text-[#64748b]">{{ $msg->location }}</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-[#faf9f5] text-[#064e3b] border border-[#e7e5df]">
                  Sealed
                </span>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </section>
  </div>
@endif
