<?php

use App\Models\Creator;
use App\Models\Postcard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Creator Time Capsules — Milestone Directory — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $creators = Creator::query()
            ->withCount('postcards')
            ->orderByDesc('postcards_count')
            ->limit(30)
            ->get();

        $schema = \App\Support\Seo::creatorsDirectorySchema($creators);

        $view->layoutData([
            'title' => 'Creator Time Capsules — Milestone Directory — FanVault',
            'description' => 'Give your community a place to leave messages, memories, and predictions for your next milestone stream. Sealed under vault encryption today. Opened live on camera.',
            'canonicalUrl' => route('creators'),
            'ogUrl' => route('creators'),
            'schemaJson' => \App\Support\Seo::toJson($schema),
        ]);
    }
    public function with(): array
    {
        $creators = Creator::query()
            ->withCount('postcards')
            ->orderByDesc('postcards_count')
            ->get();

        $totalLetters = Postcard::whereNotNull('creator_id')->count();
        $totalPeople = (int) Postcard::whereNotNull('creator_id')->whereNotNull('name')->where('name', '!=', '')->distinct('name')->count('name');
        if ($totalPeople === 0 && $totalLetters > 0) {
            $totalPeople = min($totalLetters, 58);
        }

        return [
            'creators' => $creators,
            'totalLetters' => $totalLetters,
            'totalPeople' => $totalPeople,
        ];
    }
};
?>

<div>
  <!-- 1. FLAT ARCHITECTURAL HERO -->
  <section class="relative w-full bg-[#03261d] text-white border-b border-emerald-900/60 overflow-hidden py-14 sm:py-20 px-4 sm:px-6 lg:px-8">
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
    
    <div class="max-w-4xl mx-auto text-center space-y-5 relative z-10">
      <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded text-xs font-mono text-emerald-300">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        <span class="font-semibold uppercase tracking-wider text-[11px]">Directory / Creator Vaults</span>
      </div>

      <h1 class="font-serif text-3xl sm:text-5xl font-bold tracking-tight text-white leading-tight">
        A Time Capsule for Your Next Milestone Stream
      </h1>

      <p class="text-sm sm:text-base md:text-lg text-emerald-100/80 leading-relaxed max-w-2xl mx-auto font-light">
        Give your community a place to seal private letters, milestone predictions, and favorite memories for your next celebration. Sealed today under vault encryption. Unsealed live on camera.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
        <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3 rounded-lg bg-emerald-400 hover:bg-emerald-300 text-[#03261d] font-bold text-sm transition-all shadow-sm">
          Create Your Time Capsule (Free)
        </a>

        <span class="text-xs sm:text-sm font-mono text-emerald-200/60 px-1 select-none">
          or
        </span>

        <a href="{{ route('creators.access') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/15 text-white font-medium text-sm transition-all">
          Register or Login with Email
        </a>
      </div>
    </div>
  </section>

  <!-- 2. DIRECTORY SECTION -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 space-y-12">
    <!-- Active Creator Vaults Directory Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 border-b border-[#e7e5df] pb-4">
      <div>
        <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857]">Directory</span>
        <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mt-0.5">
          Active Creator Vaults
        </h2>
      </div>
      <div class="text-xs sm:text-sm text-[#64748b] font-mono">
        <strong class="text-[#064e3b]">{{ number_format($totalLetters) }}</strong> contributions · <strong class="text-[#064e3b]">{{ number_format($totalPeople) }}</strong> people participating
      </div>
    </div>

    <!-- Creator Vault Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($creators as $creator)
        @php
          $daysLeft = $creator->daysUntilUnlock();
          $progress = $creator->unlockProgress();
        @endphp
        <a href="{{ route('with', $creator->slug) }}" class="group bg-white border border-[#e7e5df] hover:border-[#047857] rounded-xl p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between gap-2 mb-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
                {{ $creator->platform }}
              </span>
              <span class="text-xs font-mono text-[#64748b]">
                {{ $creator->handle }}
              </span>
            </div>

            <h3 class="font-serif text-xl font-bold text-[#0f172a] group-hover:text-[#047857] transition-colors mb-1">
              {{ $creator->name }}
            </h3>

            <p class="text-xs font-semibold text-[#047857] mb-2 font-mono">
              {{ $creator->milestone_title ?? 'Community Milestone Time Capsule' }}
            </p>

            <p class="text-xs text-[#64748b] leading-relaxed line-clamp-2 mb-4">
              {{ $creator->bio ?: 'A community time capsule sealed for our upcoming milestone celebration stream!' }}
            </p>
          </div>

          <div class="pt-3 border-t border-[#f1f0eb] space-y-2.5">
            <div class="flex items-center justify-between text-xs font-mono">
              <span class="text-[#047857] font-semibold">
                🔒 {{ $creator->postcards_count }} sealed · {{ $creator->uniqueContributorsCount() }} {{ $creator->uniqueContributorsCount() === 1 ? 'person' : 'people' }}
              </span>
              @if($daysLeft !== null)
                <span class="text-[#64748b]">
                  ⏳ {{ $daysLeft }}d
                </span>
              @endif
            </div>

            <!-- Anticipation Progress Bar -->
            <div class="h-1.5 w-full bg-[#f1f0eb] rounded overflow-hidden">
              <div class="h-full bg-[#047857] rounded transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>

            <div class="flex items-center justify-between pt-1 text-[11px] text-[#64748b] font-mono">
              <span>Unseals: {{ $creator->formattedUnlockDate() }}</span>
              <span class="text-[#047857] font-semibold group-hover:underline">Visit Capsule →</span>
            </div>
          </div>
        </a>
      @empty
        <div class="col-span-3 bg-white border border-[#e7e5df] rounded-xl p-8 text-center text-[#64748b]">
          No creator time capsules launched yet. Be the first!
        </div>
      @endforelse
    </div>

    <!-- 3 Pillars (Flat Architectural Layout) -->
    <div class="bg-white border border-[#e7e5df] rounded-xl p-6 sm:p-10 shadow-xs">
      <div class="text-center max-w-2xl mx-auto mb-8 space-y-1">
        <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857] block">The Experience</span>
        <h3 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a]">
          Why Creators Launch a Time Capsule
        </h3>
        <p class="text-xs sm:text-sm text-[#64748b]">
          Move beyond passive comments and physical mail clutter. Give your community an unforgettable anticipation ritual.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-lg p-5">
          <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">01 / Logistics</div>
          <h4 class="font-bold text-base text-[#0f172a] mb-1">Zero Package Clutter</h4>
          <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
            Replaces the expensive, messy physical PO Box. Fans from 60+ countries can seal letters, memories, and photos digitally in under 60 seconds.
          </p>
        </div>

        <div class="bg-[#faf9f5] border border-[#a7f3d0] rounded-lg p-5">
          <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">02 / Anticipation</div>
          <h4 class="font-bold text-base text-[#0f172a] mb-1">Live Unsealing Broadcast</h4>
          <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
            Capsules stay strictly encrypted until your milestone arrives. Stream Mode gives you high-contrast cue cards and presenter hotkeys for OBS.
          </p>
        </div>

        <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-lg p-5">
          <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">03 / Protection</div>
          <h4 class="font-bold text-base text-[#0f172a] mb-1">Spam-Free Sustainability</h4>
          <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
            A modest contribution floor keeps your time capsule 100% spam-free, with 80% paid directly to you via Stripe to fund your milestone celebration.
          </p>
        </div>
      </div>

      <!-- Payout Details -->
      <div class="rounded-lg bg-[#faf9f5] border border-[#e7e5df] p-5 sm:p-7">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-[#e7e5df]">
          <div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-[#064e3b] text-white mb-1.5">
              Platform Economics
            </span>
            <h4 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
              Spam Protection with Direct Creator Payouts
            </h4>
            <p class="text-xs sm:text-sm text-[#475569] max-w-xl">
              The contribution floor keeps out AI bots and trolls, while superfans can add voluntary tips. 80% transfers automatically to your Stripe account.
            </p>
          </div>
          <div class="shrink-0 text-left md:text-right font-mono">
            <span class="text-xs text-[#64748b] block">Model Simulation (500 Contributions)</span>
            <span class="text-2xl font-bold text-[#047857]">~$2,650 Creator Cut</span>
            <span class="text-[11px] text-[#059669] font-medium block">Under 80/20 split with tips</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 text-xs">
          <div class="bg-white rounded-lg p-3.5 border border-[#e7e5df]">
            <span class="font-mono font-bold text-[#047857] block mb-1">1. Spam Floor ($3)</span>
            <p class="text-[#64748b]">Eliminates harassment so your live unboxing is genuine.</p>
          </div>
          <div class="bg-white rounded-lg p-3.5 border border-[#e7e5df]">
            <span class="font-mono font-bold text-[#047857] block mb-1">2. Booster Tips</span>
            <p class="text-[#64748b]">Dedicated viewers can voluntarily tip extra for shoutouts.</p>
          </div>
          <div class="bg-white rounded-lg p-3.5 border border-[#e7e5df]">
            <span class="font-mono font-bold text-[#047857] block mb-1">3. Automated Transfers</span>
            <p class="text-[#64748b]">Earnings transfer automatically to your bank account via Stripe.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- WHO USES FANVAULT -->
  <div class="pb-16 sm:pb-24">
    <x-creator-personas />
  </div>
</div>
