<?php

use App\Models\Creator;
use App\Models\Postcard;
use App\Models\Referral;
use App\Models\Stat;
use App\Support\Capsule;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('FanVault — Milestone Time Capsules & Fan Mail Vaults for Creators')]
class extends Component
{
    public function rendering($view): void
    {
        $faqs = [
            'What is FanVault?' => 'FanVault is a digital milestone time capsule and fan mail platform for creators. Fans write heartfelt letters, milestone predictions, and memories today, and creators unseal and read them live on milestone celebration streams.',
            'How does pricing work for creators?' => 'Creators set their own seal amount instead of being locked into a rigid $5 fee. Fans contribute at the creator’s chosen level with optional booster tips, and creators receive direct payouts via Stripe.',
            'When are the sealed letters unlocked?' => 'Letters stay encrypted and private until the creator reaches their milestone target date and unlocks the vault during their live broadcast stream.',
            'How does FanVault replace a physical PO Box?' => 'FanVault gives creators all the emotion and connection of fan mail with zero physical mail sorting, zero storage clutter, and full global access for international fans.',
        ];

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => array_merge(
                \App\Support\Seo::websiteSchema()['@graph'],
                [\App\Support\Seo::faqSchema($faqs)]
            ),
        ];

        $view->layoutData([
            'title' => 'FanVault — Milestone Time Capsules & Fan Mail Vaults for Creators',
            'description' => 'The modern digital time capsule and fan mail platform for creator milestones. Fans seal letters & predictions; creators unlock and read them live on stream.',
            'canonicalUrl' => route('home'),
            'ogUrl' => route('home'),
            'schemaJson' => \App\Support\Seo::toJson($schema),
        ]);
    }
    public function with(): array
    {
        $count = (int) (Stat::query()->value('sealed_count') ?? Postcard::query()->count());
        $latest = Postcard::query()->with('creator')->orderByDesc('number')->limit(6)->get();
        $creators = Creator::query()
            ->whereNotNull('milestone_title')
            ->withCount('postcards')
            ->orderByDesc('postcards_count')
            ->limit(6)
            ->get();
        $creatorsCount = Creator::query()->count();
        $creatorLettersCount = Postcard::query()->whereNotNull('creator_id')->count();
        $totalPaidCents = (int) Referral::query()->sum('cut_cents');
        $foundingLeft = max(0, Capsule::FOUNDING_CAP - min($count, Capsule::FOUNDING_CAP));
        $fill = max(2, min(100, (min($count, Capsule::FOUNDING_CAP) / Capsule::FOUNDING_CAP) * 100));

        return compact(
            'count', 'latest', 'creators', 'creatorsCount',
            'creatorLettersCount', 'totalPaidCents', 'foundingLeft', 'fill'
        );
    }
};
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
  <!-- Hero Section -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
    <div class="lg:col-span-7 space-y-4 sm:space-y-6 text-center lg:text-left">
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium shadow-2xs">
        <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
        <span>FanVault Protocol</span>
        <span class="text-[#64748b]">·</span>
        <span class="text-[#475569]">Community Time Capsules for Creators</span>
      </div>
      
      <div>
        <h1 class="font-serif text-3xl sm:text-5xl lg:text-6xl font-normal tracking-tight text-[#0f172a] leading-[1.12]">
          The milestone vault for creators & their <em class="italic text-[#047857] font-semibold">communities</em>.
        </h1>
        <p class="font-serif italic text-lg sm:text-xl text-[#047857] mt-3 font-normal">
          Fans seal letters & predictions today — unsealed live on stream tomorrow.
        </p>
      </div>

      <p class="text-base sm:text-lg text-[#475569] leading-relaxed max-w-xl mx-auto lg:mx-0">
        Creators set their own seal amount instead of being locked to a fixed $5 fee. Fans contribute at your chosen amount, with optional booster tips ($10, $25, $50) for superfans eager to back the journey. Unseal letters live on stream with direct Stripe payouts.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
        <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:shadow-[0_4px_16px_rgba(6,78,59,0.35)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
          Explore Creator Vaults
        </a>
        <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 sm:py-4 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-xs">
          Launch Your Vault (Free)
        </a>
      </div>

      <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-1.5 text-xs text-[#64748b] pt-1">
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Creator-Defined Pricing</span>
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>No fixed $5 fee · Flexible amounts</span>
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Unsealed live on stream</span>
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Zero PO Box clutter</span>
      </div>
    </div>

    <!-- Hero Archival Pass Card -->
    <aside class="lg:col-span-5">
      <div class="relative bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.04)] text-center overflow-hidden">
        <!-- Minimal Badge -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f8fafc] text-[#475569] border border-[#e2e8f0] text-[10px] font-mono uppercase tracking-wider mb-4">
          Official Community Vault Pass
        </div>
        
        <div class="my-3">
          <div class="font-serif text-4xl sm:text-5xl font-normal text-[#0f172a] tracking-tight">
            {{ number_format($count) }}
          </div>
          <div class="text-xs sm:text-sm text-[#64748b] font-medium mt-1">
            Community letters sealed across all vaults
          </div>
        </div>

        <!-- Creator Payout Highlight Box (Creator Sets Amount) -->
        <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-4 my-5 text-left">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-[#064e3b] flex items-center gap-1.5">
              Creator-Set Pricing
            </span>
            <span class="text-xs font-mono font-bold text-[#047857] bg-[#ecfdf5] px-2 py-0.5 rounded-full border border-[#a7f3d0]">
              Custom Amount & Tips
            </span>
          </div>
          <div class="text-[11px] text-[#64748b] mt-1.5 leading-relaxed">
            Set your own seal amount instead of a rigid fixed $5 fee, plus optional superfan boosters. Automatic direct Stripe payouts when you unseal your vault.
          </div>
        </div>

        <!-- Founding Progress Bar -->
        <div class="pt-2 border-t border-[#f1f0eb]">
          <div class="flex justify-between items-center text-xs font-medium mb-2">
            <span class="text-[#0f172a]">Founding Ledger Capacity</span>
            <span class="text-[#047857] font-mono text-[11px]">{{ number_format($foundingLeft) }} passes remaining</span>
          </div>
          <div class="h-2 w-full bg-[#f1f0eb] rounded-full overflow-hidden p-0.5">
            <div class="h-full bg-[#047857] rounded-full transition-all duration-500" style="width: {{ $fill }}%"></div>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-[#f1f0eb] text-xs text-[#64748b]">
          Looking to write a personal capsule? 
          <a href="{{ route('seal') }}" class="text-[#047857] font-semibold hover:underline">
            Write a letter to 2050 →
          </a>
        </div>
      </div>
    </aside>
  </div>

  <!-- Featured Creator Community Vaults -->
  <section class="my-14 sm:my-20">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
      <div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-semibold uppercase tracking-wider mb-2">
          Live Creator Vaults
        </div>
        <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a]">
          Join an upcoming milestone celebration
        </h2>
        <p class="text-xs sm:text-sm text-[#64748b] mt-1">
          Lock in your letter, memory, or prediction before the vault unsealing stream!
        </p>
      </div>
      <a href="{{ route('creators') }}" class="inline-flex items-center gap-1 px-4.5 py-2 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm shadow-xs transition-all">
        View All Vaults ({{ $creatorsCount }}) →
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($creators as $c)
        @php
          $daysLeft = $c->unlock_date ? max(0, (int) now()->diffInDays($c->unlock_date, false)) : null;
        @endphp
        <div class="bg-white border border-[#e7e5df] hover:border-[#047857]/30 rounded-3xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between gap-3 mb-4">
              <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-[#064e3b] text-white flex items-center justify-center font-bold text-base shadow-2xs">
                  {{ strtoupper(substr($c->name, 0, 1)) }}
                </div>
                <div>
                  <h3 class="font-serif text-lg font-bold text-[#0f172a] leading-tight">
                    {{ $c->name }}
                  </h3>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs text-[#64748b] font-mono">{{ $c->handle }}</span>
                    <span class="text-[10px] font-medium uppercase px-2 py-0.5 rounded-full bg-[#f1f5f9] text-[#475569]">
                      {{ $c->platform }}
                    </span>
                  </div>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#ecfdf5] text-[#064e3b] text-xs font-mono font-medium rounded-full border border-[#a7f3d0]/70">
                {{ $c->postcards_count }} sealed
              </span>
            </div>

            <!-- Milestone Details -->
            <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-3.5 mb-4">
              <div class="text-[11px] font-mono uppercase font-semibold text-[#64748b]">Milestone Goal</div>
              <div class="text-sm font-semibold text-[#0f172a] mt-0.5">
                {{ $c->milestone_title ?: 'Community Milestone Vault' }}
              </div>
              @if($c->bio)
                <div class="text-xs text-[#64748b] italic mt-1 line-clamp-2">
                  “{{ $c->bio }}”
                </div>
              @endif
            </div>
          </div>

          <!-- Bottom Action & Countdown -->
          <div class="pt-3 border-t border-[#f1f0eb] space-y-3">
            <div class="flex items-center justify-between text-xs">
              <span class="text-[#64748b]">Target Unsealing:</span>
              <strong class="text-[#047857] font-semibold">
                {{ $c->formattedUnlockDate() }}
                @if($daysLeft !== null)
                  <span class="text-xs font-mono text-[#64748b]">({{ $daysLeft }}d)</span>
                @endif
              </strong>
            </div>

            <a href="{{ route('with', $c->slug) }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-full bg-[#ecfdf5] hover:bg-[#064e3b] text-[#064e3b] hover:text-white font-medium text-xs sm:text-sm border border-[#a7f3d0]/70 hover:border-[#064e3b] transition-all">
              Seal Letter for {{ explode(' ', $c->name)[0] }} (From ${{ $c->minPriceDollars() }})
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <!-- Interactive PWYW Economics Simulator -->
  <section class="my-14 sm:my-20 bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs"
    x-data="{
      communitySize: 1000,
      floor: 3,
      get avgGift() {
        const f = Number(this.floor);
        return (0.60 * f) + (0.25 * Math.max(10, f)) + (0.10 * Math.max(25, f)) + (0.05 * Math.max(50, f));
      },
      get grossPool() {
        return Math.round(this.communitySize * this.avgGift);
      },
      get creatorPayout() {
        return Math.round(this.grossPool * 0.80);
      },
      get flatPayout() {
        return Math.round(this.communitySize * 1.50);
      },
      get multiplier() {
        return (this.creatorPayout / Math.max(1, this.flatPayout)).toFixed(1);
      }
    }"
  >
    <div class="max-w-3xl mx-auto text-center mb-8 sm:mb-10">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-mono uppercase tracking-wider mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
        Interactive Vault Economics
      </div>
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        Why creator-set pricing changes everything
      </h2>
      <p class="text-sm sm:text-base text-[#64748b] leading-relaxed">
        Choose your own seal amount instead of a rigid fixed $5 fee. Superfans can add booster tips to back your milestone. See how flexible contributions scale your celebration revenue.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
      <!-- Interactive Controls (Left 6 cols) -->
      <div class="lg:col-span-6 space-y-6 bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 sm:p-7">
        <!-- Community Size Slider -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-mono font-semibold text-[#475569] uppercase tracking-wider">
              Participating Fans / Letters:
            </label>
            <span class="font-mono text-base font-bold text-[#064e3b]" x-text="Number(communitySize).toLocaleString() + ' fans'"></span>
          </div>
          <input 
            type="range" 
            min="100" 
            max="5000" 
            step="100" 
            x-model="communitySize"
            class="w-full accent-[#064e3b] cursor-pointer"
          >
          <div class="flex justify-between text-[11px] font-mono text-[#94a3b8] mt-1">
            <span>100 letters</span>
            <span>1,000 letters</span>
            <span>5,000 letters</span>
          </div>
        </div>

        <!-- Floor Price Selector -->
        <div>
          <label class="block text-xs font-mono font-semibold text-[#475569] uppercase tracking-wider mb-2">
            Your Minimum Floor (Set by You):
          </label>
          <div class="grid grid-cols-3 gap-2">
            <button 
              type="button" 
              @click="floor = 3"
              class="py-2.5 px-3 rounded-2xl border text-center transition-all duration-200"
              :class="floor === 3 ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-2xs font-semibold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]'"
            >
              <span class="block text-[10px] uppercase font-mono opacity-80">Recommended</span>
              <span class="text-sm font-bold font-mono">$3 Floor</span>
            </button>
            <button 
              type="button" 
              @click="floor = 5"
              class="py-2.5 px-3 rounded-2xl border text-center transition-all duration-200"
              :class="floor === 5 ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-2xs font-semibold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]'"
            >
              <span class="block text-[10px] uppercase font-mono opacity-80">Standard</span>
              <span class="text-sm font-bold font-mono">$5 Floor</span>
            </button>
            <button 
              type="button" 
              @click="floor = 10"
              class="py-2.5 px-3 rounded-2xl border text-center transition-all duration-200"
              :class="floor === 10 ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-2xs font-semibold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]'"
            >
              <span class="block text-[10px] uppercase font-mono opacity-80">Premium</span>
              <span class="text-sm font-bold font-mono">$10 Floor</span>
            </button>
          </div>
        </div>

        <!-- Real World Distribution Insight -->
        <div class="pt-4 border-t border-[#e7e5df] text-xs text-[#64748b] space-y-2">
          <div class="flex items-center justify-between font-mono text-[11px]">
            <span class="text-[#334155] font-semibold">Empirical Tipping Mix:</span>
            <span class="text-[#047857] font-semibold" x-text="'Est. Avg: $' + avgGift.toFixed(2) + ' / fan'"></span>
          </div>
          <div class="h-2.5 w-full flex rounded-full overflow-hidden bg-[#e2e8f0]">
            <div class="bg-[#047857] transition-all duration-300" style="width: 60%" title="60% Floor"></div>
            <div class="bg-[#059669] transition-all duration-300" style="width: 25%" title="25% $10 Supporters"></div>
            <div class="bg-[#ca8a04] transition-all duration-300" style="width: 10%" title="10% $25 Superfans"></div>
            <div class="bg-[#b45309] transition-all duration-300" style="width: 5%" title="5% $50 VIP Patrons"></div>
          </div>
          <div class="flex flex-wrap items-center justify-between text-[10px] text-[#64748b] font-mono pt-0.5">
            <span>60% Floor</span>
            <span>25% Supporter ($10)</span>
            <span>10% Superfan ($25)</span>
            <span>5% VIP ($50)</span>
          </div>
        </div>
      </div>

      <!-- Calculated Results Display (Right 6 cols) -->
      <div class="lg:col-span-6 bg-white border border-[#a7f3d0] rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(4,120,87,0.06)] flex flex-col justify-between space-y-6">
        <div>
          <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857] block mb-1">
            Your Estimated Creator Earnings
          </span>
          <div class="font-serif text-4xl sm:text-5xl font-normal text-[#064e3b] tracking-tight">
            $<span x-text="creatorPayout.toLocaleString()"></span>
          </div>
          <p class="text-xs text-[#64748b] mt-1.5">
            Total Vault Pool: $<span x-text="grossPool.toLocaleString()"></span> · Direct automated Stripe payouts
          </p>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-[#f1f0eb]">
          <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-3.5">
            <span class="block text-[11px] font-mono text-[#64748b]">Old Flat Fee:</span>
            <strong class="text-sm sm:text-base font-mono text-[#334155] block mt-0.5">$<span x-text="flatPayout.toLocaleString()"></span></strong>
            <span class="text-[10px] text-[#94a3b8] block">Flat $1.50 cap</span>
          </div>

          <div class="bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl p-3.5">
            <span class="block text-[11px] font-mono text-[#064e3b]">PWYW Advantage:</span>
            <strong class="text-sm sm:text-base font-mono text-[#047857] block mt-0.5" x-text="multiplier + '× More' "></strong>
            <span class="text-[10px] text-[#064e3b] block">With booster tips</span>
          </div>
        </div>

        <div class="text-xs text-[#475569] leading-relaxed pt-1">
          💡 <strong>Why creators love setting their own price:</strong> You're never locked into a flat $5 fee. Choose the exact amount that fits your audience, while superfans can add $10, $25, or $50 booster tips—unlocking thousands of dollars in celebration support.
        </div>
      </div>
    </div>
  </section>

  <!-- How FanVault Works for Creators & Communities -->
  <section class="my-14 sm:my-20 bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs">
    <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-12">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f8fafc] border border-[#e2e8f0] text-[#475569] text-xs font-mono uppercase tracking-wider mb-2">
        Platform Architecture
      </div>
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        How FanVault works in 3 easy steps
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        Transform passive subscribers into deeply invested community co-creators.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 shadow-2xs">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-widest uppercase mb-3">STAGE 01</div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2">
          Creator Sets Milestone & Amount
        </h3>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          Announce your goal (100k subscribers, 5th anniversary, or 500th episode) and choose your own seal amount instead of a fixed $5 fee. Get your branded <code class="bg-white px-1.5 py-0.5 rounded text-[#064e3b] font-mono text-xs border border-[#e7e5df]">/with/you</code> link for descriptions and bio.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 shadow-2xs">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-widest uppercase mb-3">STAGE 02</div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2">
          Community Seals with Boosters
        </h3>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          Fans seal meaningful letters, photos, and wild predictions at the amount you set (instead of a fixed $5 fee). Superfans add $10, $25, or $50 booster tips, with direct payouts sent to your Stripe.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 shadow-2xs">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-widest uppercase mb-3">STAGE 03</div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2">
          Unsealed Live on Stream
        </h3>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          When the milestone is achieved, switch into broadcast <strong>Stream Mode</strong>. Read letters live, react to hilarious predictions, and celebrate together!
        </p>
      </div>
    </div>
  </section>

  <!-- Physical PO Box vs FanVault Comparison -->
  <section class="my-14 sm:my-20">
    <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-10">
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        Why creators are replacing physical PO Boxes
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        Physical mail is broken. FanVault gives you modern fan engagement with zero clutter.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
      <!-- Old PO Box -->
      <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-8 shadow-xs">
        <div class="text-xs font-mono uppercase tracking-wider text-[#94a3b8] font-semibold mb-2">
          Traditional Setup
        </div>
        <div class="text-lg font-serif font-bold text-[#334155] mb-4">
          The Physical PO Box
        </div>
        <ul class="space-y-3 text-xs sm:text-sm text-[#64748b]">
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>Costs $250–$500/year to rent a physical box.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>Living room clutter and cardboard boxes everywhere.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>International fans pay $20+ in shipping or cannot send mail.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>Zero revenue — 100% expense and logistical headache.</span>
          </li>
        </ul>
      </div>

      <!-- FanVault -->
      <div class="bg-white border border-[#a7f3d0] rounded-3xl p-6 sm:p-8 shadow-xs relative">
        <div class="text-xs font-mono uppercase tracking-wider text-[#047857] font-semibold mb-2">
          Modern Alternative
        </div>
        <div class="text-lg font-serif font-bold text-[#064e3b] mb-4">
          The FanVault Solution
        </div>
        <ul class="space-y-3 text-xs sm:text-sm text-[#334155]">
          <li class="flex items-start gap-2.5">
            <span class="text-[#047857] font-bold">✓</span>
            <span><strong>100% Free to setup</strong> in under 60 seconds.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#047857] font-bold">✓</span>
            <span><strong>Zero clutter</strong> — beautiful digital archive and stream reader.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#047857] font-bold">✓</span>
            <span><strong>Global access</strong> — accessible pricing set by you lets global fans participate.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#047857] font-bold">✓</span>
            <span><strong>Creator-set pricing & tips</strong> — choose your own amount instead of a fixed $5 fee. A typical 1,000-letter vault generates $4,000–$6,500+ directly to your Stripe account.</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- Voices on the Wall -->
  <section class="my-14 sm:my-20">
    <div class="text-center max-w-2xl mx-auto mb-6 sm:mb-8">
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        Voices sealed in the vaults
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        Real community letters and milestone predictions sealed across all vaults right now.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      @foreach($latest as $msg)
        <x-voice-card :msg="$msg" />
      @endforeach
    </div>

    <div class="text-center mt-8 sm:mt-10">
      <a href="{{ route('explore') }}" class="inline-flex items-center gap-1.5 px-6 py-3 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm hover:-translate-y-0.5 transition-all shadow-xs">
        Browse all {{ number_format($count) }} letters on the public wall →
      </a>
    </div>
  </section>

  <!-- Bottom CTA: Launch or Explore -->
  <div class="bg-white border border-[#e7e5df] rounded-3xl p-8 sm:p-14 text-center my-12 sm:my-18 shadow-[0_4px_24px_rgba(0,0,0,0.03)] relative overflow-hidden">
    <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2 sm:mb-3">
      Ready to celebrate your next milestone?
    </h2>
    <p class="text-sm sm:text-base text-[#64748b] max-w-xl mx-auto mb-6">
      Launch your community milestone vault in 60 seconds. Paste your link in your next video description, and let your superfans build an unforgettable celebration with you.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
      <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
        Launch Your Creator Vault (Free)
      </a>
      <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 sm:py-4 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 transition-all shadow-xs">
        Browse Active Creator Vaults
      </a>
    </div>
  </div>
</section>
