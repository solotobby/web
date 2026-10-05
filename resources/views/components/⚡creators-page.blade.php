<?php

use App\Models\Creator;
use App\Models\Postcard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Creator Vault Directory — FanVault')]
class extends Component
{
    public function with(): array
    {
        $creators = Creator::query()
            ->withCount('postcards')
            ->orderByDesc('postcards_count')
            ->get();

        $totalLetters = Postcard::whereNotNull('creator_id')->count();

        return [
            'creators' => $creators,
            'totalLetters' => $totalLetters,
        ];
    }
};
?>

<section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
  <!-- Hero Section -->
  <div class="text-center max-w-3xl mx-auto mb-10 sm:mb-14">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium mb-3">
      Creator Vaults · Digital Fan Mail for Milestone Streams
    </span>
    <h1 class="font-serif text-3xl sm:text-5xl font-normal tracking-tight text-[#0f172a] mb-4 leading-tight">
      A time vault for your next <br class="hidden sm:inline">
      <em class="italic text-[#047857]">milestone stream</em>.
    </h1>
    <p class="text-sm sm:text-base md:text-lg text-[#64748b] leading-relaxed mb-6">
      Fans seal private letters, milestone predictions, and heartfelt memories starting at the amount you set (instead of a fixed $5 fee). Generous superfans add booster tips ($10, $25, $50+). You receive direct payouts and unseal the vault live on stream!
    </p>

    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
      <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 transition-all">
        Create Your Vault (60s)
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
          Featured Community Vaults
        </h2>
      </div>
      <span class="text-xs sm:text-sm text-[#64748b]">
        <strong>{{ $totalLetters }}</strong> fan letters sealed across {{ $creators->count() }} vaults
      </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      @forelse($creators as $creator)
        <a href="{{ route('with', $creator->slug) }}" class="group bg-white border border-[#e7e5df] hover:border-[#047857]/30 rounded-3xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between">
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
              {{ $creator->milestone_title ?? 'Community Milestone Vault' }}
            </p>

            <p class="text-xs text-[#64748b] leading-relaxed line-clamp-2 mb-4">
              {{ $creator->bio ?: 'A community vault sealed for our upcoming milestone celebration stream!' }}
            </p>
          </div>

          <div class="pt-3 border-t border-[#e7e5df] mt-2 flex items-center justify-between">
            <span class="text-xs font-mono font-medium text-[#047857]">
              {{ $creator->postcards_count }} letters sealed
            </span>
            <span class="text-xs font-medium text-[#475569] group-hover:text-[#047857] transition-colors">
              Visit Vault →
            </span>
          </div>
        </a>
      @empty
        <div class="col-span-3 bg-white border border-[#e7e5df] rounded-3xl p-8 text-center text-[#64748b]">
          No creator vaults launched yet. Be the first!
        </div>
      @endforelse
    </div>
  </div>

  <!-- Why Creators Love FanVault (3 Pillars & PWYW Economics) -->
  <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs mb-10">
    <div class="text-center max-w-2xl mx-auto mb-8">
      <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857] block mb-1">The Economics</span>
      <h3 class="font-serif text-2xl sm:text-3xl font-normal text-[#0f172a]">
        Why creators choose FanVault
      </h3>
      <p class="text-xs sm:text-sm text-[#64748b] mt-1">
        Creators set their own seal amount instead of being locked into a fixed $5 fee. Flexible amounts remove friction for casual fans while letting superfans tip generously.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">Pillar 01</div>
        <h4 class="font-bold text-base text-[#0f172a] mb-1">Zero Inventory or Shipping</h4>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          No manufacturing, warehouse fees, packaging, or lost parcel tickets. Drop your vault link in bio and let your community build your vault digitally.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#a7f3d0] rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all relative overflow-hidden">
        <div class="absolute -top-3 -right-3 w-12 h-12 bg-[#ecfdf5] rounded-full blur-sm"></div>
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">Pillar 02 · Custom Pricing</div>
        <h4 class="font-bold text-base text-[#0f172a] mb-1">Creator-Defined Pricing & Tips</h4>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Set your own seal amount instead of a rigid fixed $5 fee. Fans contribute at your chosen level or tip $10, $25, or $50 to champion your channel—yielding significantly higher earnings than flat-rate platforms.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-2xl p-5 shadow-2xs hover:shadow-xs transition-all">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-wider uppercase mb-2">Pillar 03</div>
        <h4 class="font-bold text-base text-[#0f172a] mb-1">Guaranteed Milestone Content</h4>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Unsealing 100+ heartfelt letters, predictions, and childhood memories creates an unforgettable, highly shareable live stream or YouTube milestone video.
        </p>
      </div>
    </div>

    <!-- Enlightening PWYW Economics Breakdown Card -->
    <div class="rounded-2xl bg-gradient-to-br from-[#f8fafc] via-[#f1f5f9] to-[#ecfdf5] border border-[#e2e8f0] p-5 sm:p-7">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-[#cbd5e1]/60">
        <div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-[#064e3b] text-white mb-1.5">
            ⚡ Custom Pricing & Tips Math
          </span>
          <h4 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
            How Creator-Set Pricing & Booster Tips Unlock Higher Earnings
          </h4>
          <p class="text-xs sm:text-sm text-[#475569]">
            Fixed $5 fees leave money on the table. Setting your own amount and letting superfans add booster tips allows your community to support you at every level.
          </p>
        </div>
        <div class="shrink-0 text-left md:text-right">
          <span class="text-xs font-mono text-[#64748b] block">Typical 500-Letter Community</span>
          <span class="font-mono text-2xl font-bold text-[#047857]">~$2,650 Estimated Earnings</span>
          <span class="text-[11px] text-[#059669] font-medium block">vs. $750 on old flat $1.50 models (+253%)</span>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
        <div class="bg-white/80 rounded-xl p-3.5 border border-white/60 shadow-2xs">
          <span class="text-xs font-mono font-bold text-[#047857] block mb-1">1. Frictionless Entry ($3 Floor)</span>
          <p class="text-xs text-[#64748b] leading-relaxed">
            Students and casual viewers can seal an official letter without financial hesitation. Participation stays high.
          </p>
        </div>
        <div class="bg-white/80 rounded-xl p-3.5 border border-white/60 shadow-2xs">
          <span class="text-xs font-mono font-bold text-[#047857] block mb-1">2. Superfan Booster Tiers ($10–$50)</span>
          <p class="text-xs text-[#64748b] leading-relaxed">
            20–30% of contributors voluntarily tip higher for custom foils, stream shoutouts, and VIP status on your reveal day.
          </p>
        </div>
        <div class="bg-white/80 rounded-xl p-3.5 border border-white/60 shadow-2xs">
          <span class="text-xs font-mono font-bold text-[#047857] block mb-1">3. Direct Stripe Payouts</span>
          <p class="text-xs text-[#64748b] leading-relaxed">
            Funds accrue safely in your studio ledger and transfer automatically when your milestone unseals on stream.
          </p>
        </div>
      </div>
    </div>
</section>

