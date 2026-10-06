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

<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
  <!-- Hero Section -->
  <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14">
    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium mb-3 shadow-2xs">
      <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
      <span class="font-semibold">The Creator Time Capsule</span>
      <span class="text-[#64748b]">·</span>
      <span>Anticipation for Your Next Milestone Stream</span>
    </div>

    <h1 class="font-serif text-3xl sm:text-5xl font-normal tracking-tight text-[#0f172a] mb-4 leading-tight">
      A time capsule for your next <br class="hidden sm:inline">
      <em class="italic text-[#047857]">milestone stream</em>.
    </h1>
    <p class="text-sm sm:text-base md:text-lg text-[#64748b] leading-relaxed mb-6 max-w-2xl mx-auto">
      Give your community a home to seal private letters, milestone predictions, and favorite memories for your next celebration. Sealed today under vault encryption. Unsealed live with your fans when the moment arrives.
    </p>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
      <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 transition-all">
        Create Your Time Capsule (Free)
      </a>

      <span class="text-xs sm:text-sm font-serif italic text-[#64748b] px-1 select-none">
        or
      </span>

      <a href="{{ route('creators.access') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 transition-all shadow-xs">
        Register or Login with Email
      </a>
    </div>
  </div>

  <!-- Active Creator Vaults Directory -->
  <div class="space-y-6 mb-16">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-[#e7e5df] pb-4">
      <div>
        <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857]">Directory</span>
        <h2 class="font-serif text-2xl sm:text-3xl font-normal text-[#0f172a] mt-0.5">
          Active Time Capsules
        </h2>
      </div>
      <div class="text-xs sm:text-sm text-[#64748b] font-mono">
        <strong class="text-[#064e3b]">{{ $totalLetters }}</strong> contributions · <strong class="text-[#064e3b]">{{ $totalPeople }}</strong> people participating
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      @forelse($creators as $creator)
        @php
          $daysLeft = $creator->daysUntilUnlock();
          $progress = $creator->unlockProgress();
        @endphp
        <a href="{{ route('with', $creator->slug) }}" class="group bg-white border border-[#e7e5df] hover:border-[#047857]/40 rounded-3xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between gap-2 mb-3">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]/70">
                {{ $creator->platform }}
              </span>
              <span class="text-xs font-mono text-[#64748b]">
                {{ $creator->handle }}
              </span>
            </div>

            <h3 class="font-serif text-xl font-bold text-[#0f172a] group-hover:text-[#047857] transition-colors mb-1">
              {{ $creator->name }}
            </h3>

            <p class="text-xs font-semibold text-[#047857] mb-2">
              {{ $creator->milestone_title ?? 'Community Milestone Time Capsule' }}
            </p>

            <p class="text-xs text-[#64748b] leading-relaxed line-clamp-2 mb-4">
              {{ $creator->bio ?: 'A community time capsule sealed for our upcoming milestone celebration stream!' }}
            </p>
          </div>

          <div class="pt-3 border-t border-[#e7e5df] mt-2 space-y-2">
            <!-- Point 9: Contributions + Unique People -->
            <div class="flex items-center justify-between text-xs">
              <span class="font-mono font-medium text-[#047857]">
                🔒 {{ $creator->postcards_count }} sealed · {{ $creator->uniqueContributorsCount() }} {{ $creator->uniqueContributorsCount() === 1 ? 'person' : 'people' }}
              </span>
              @if($daysLeft !== null)
                <span class="font-mono text-[11px] text-[#64748b]">
                  ⏳ {{ $daysLeft }}d remaining
                </span>
              @endif
            </div>

            <!-- Point 10: Anticipation Progress Bar -->
            <div class="h-1.5 w-full bg-[#f1f0eb] rounded-full overflow-hidden">
              <div class="h-full bg-[#047857] rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
            </div>

            <div class="flex items-center justify-between pt-1 text-[11px] text-[#64748b]">
              <span>Unseals: {{ $creator->formattedUnlockDate() }}</span>
              <span class="text-[#047857] font-semibold group-hover:underline">Visit Capsule →</span>
            </div>
          </div>
        </a>
      @empty
        <div class="col-span-3 bg-white border border-[#e7e5df] rounded-3xl p-8 text-center text-[#64748b]">
          No creator time capsules launched yet. Be the first!
        </div>
      @endforelse
    </div>
  </div>

  <!-- Why Creators Choose FanVault (3 Pillars) -->
  <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs mb-10">
    <div class="text-center max-w-2xl mx-auto mb-8">
      <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857] block mb-1">The Experience</span>
      <h3 class="font-serif text-2xl sm:text-3xl font-normal text-[#0f172a]">
        Why creators launch a Time Capsule
      </h3>
      <p class="text-xs sm:text-sm text-[#64748b] mt-1">
        Move beyond passive comments and physical mail clutter. Give your community an unforgettable anticipation ritual.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">Pillar 01 · Community</div>
        <h4 class="font-bold text-base text-[#0f172a] mb-1">Zero Logistics or Package Clutter</h4>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Replaces the expensive, messy physical PO Box. Fans from 60+ countries can seal letters, memories, and photos digitally in under 60 seconds.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#a7f3d0] rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">Pillar 02 · Anticipation</div>
        <h4 class="font-bold text-base text-[#0f172a] mb-1">The Live Unsealing Ceremony</h4>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Capsules stay strictly encrypted until your milestone arrives. Stream Mode gives you high-contrast cue cards and presenter hotkeys for an unforgettable broadcast.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">Pillar 03 · Protection</div>
        <h4 class="font-bold text-base text-[#0f172a] mb-1">Spam-Free Creator Sustainability</h4>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          A modest contribution floor keeps your time capsule 100% spam-free, with 80% paid directly to you via Stripe to fund your milestone celebration.
        </p>
      </div>
    </div>

    <!-- Creator Sustainability & Model Simulation (Toned down, quiet reassurance) -->
    <div class="rounded-2xl bg-gradient-to-br from-[#f8fafc] via-[#f1f5f9] to-[#ecfdf5] border border-[#e2e8f0] p-5 sm:p-7">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-[#cbd5e1]/60">
        <div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-[#064e3b] text-white mb-1.5">
            🛡️ Platform Sustainability (The Added Benefit)
          </span>
          <h4 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
            Spam Protection with Direct Creator Payouts
          </h4>
          <p class="text-xs sm:text-sm text-[#475569]">
            The main reason to use FanVault is the emotional community moment. But as a natural benefit, the modest contribution floor weeds out trolls and bots, while superfans can add voluntary booster tips. 80% transfers directly to your Stripe account.
          </p>
        </div>
        <div class="shrink-0 text-left md:text-right">
          <span class="text-xs font-mono text-[#64748b] block">Model Simulation (500 Contributions)</span>
          <span class="font-mono text-2xl font-bold text-[#047857]">~$2,650 Projected Creator Cut</span>
          <span class="text-[11px] text-[#059669] font-medium block">Under 80/20 split with voluntary tips</span>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
        <div class="bg-white/80 rounded-xl p-3.5 border border-white/60 shadow-2xs">
          <span class="text-xs font-mono font-bold text-[#047857] block mb-1">1. Spam Protection ($3 Floor)</span>
          <p class="text-xs text-[#64748b] leading-relaxed">
            Eliminates AI spam, bot attacks, and harassment so your live unsealing ceremony is genuine and celebratory.
          </p>
        </div>
        <div class="bg-white/80 rounded-xl p-3.5 border border-white/60 shadow-2xs">
          <span class="text-xs font-mono font-bold text-[#047857] block mb-1">2. Superfan Boosters ($10–$50)</span>
          <p class="text-xs text-[#64748b] leading-relaxed">
            Dedicated viewers can voluntarily tip extra for foil certificates, stream shoutouts, and VIP recognition.
          </p>
        </div>
        <div class="bg-white/80 rounded-xl p-3.5 border border-white/60 shadow-2xs">
          <span class="text-xs font-mono font-bold text-[#047857] block mb-1">3. Automated Stripe Payouts</span>
          <p class="text-xs text-[#64748b] leading-relaxed">
            Earnings accrue transparently in your Creator Studio and transfer automatically to your bank account.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

