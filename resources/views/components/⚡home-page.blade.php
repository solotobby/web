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
#[Title('FanVault — The Creator Time Capsule')]
class extends Component
{
    public function rendering($view): void
    {
        $faqItems = $this->faqItems();
        $faqs = array_combine(array_column($faqItems, 'q'), array_column($faqItems, 'a'));

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => array_merge(
                \App\Support\Seo::websiteSchema()['@graph'],
                [\App\Support\Seo::faqSchema($faqs)]
            ),
        ];

        $view->layoutData([
            'title' => 'FanVault — The Creator Time Capsule',
            'description' => 'Where creators preserve the moments, memories, predictions and messages from their community — sealed today, opened when the moment arrives.',
            'canonicalUrl' => route('home'),
            'ogUrl' => route('home'),
            'schemaJson' => \App\Support\Seo::toJson($schema),
        ]);
    }

    public function faqItems(): array
    {
        return [
            [
                'category' => 'fans',
                'q' => 'What is FanVault?',
                'a' => 'FanVault is the time capsule for creators and their communities. Fans leave messages, memories, and predictions for the future, sealed until a creator milestone or celebration arrives.',
            ],
            [
                'category' => 'fans',
                'q' => 'How does sealing a message work?',
                'a' => 'You write your message, pick an optional photo or prediction, and set a 1-line public teaser. Only your teaser is visible on the public community wall today. Your full message, predictions, and photos stay strictly encrypted until the stream reveal.',
            ],
            [
                'category' => 'fans',
                'q' => 'When are the sealed messages unlocked?',
                'a' => 'Capsules stay encrypted and private until the creator reaches their milestone target date and unlocks the vault during their live broadcast stream.',
            ],
            [
                'category' => 'fans',
                'q' => 'What can fans leave inside a time capsule?',
                'a' => 'Fans can leave heartfelt letters, wild milestone predictions, community memories, and keepsake photos — sealed until the unsealing ceremony.',
            ],
            [
                'category' => 'creators',
                'q' => 'How does pricing work for creators?',
                'a' => 'Creators set their own seal amount instead of being locked into a rigid fee. Fans contribute at the creator’s chosen level with optional booster tips, and creators receive direct payouts via Stripe.',
            ],
            [
                'category' => 'creators',
                'q' => 'Does FanVault replace a physical PO Box?',
                'a' => 'Yes. FanVault delivers all the warmth and emotion of fan mail with zero physical mail sorting, zero storage clutter, and full global access for international fans.',
            ],
            [
                'category' => 'creators',
                'q' => 'Can creators run multiple time capsules?',
                'a' => 'Yes. Creators can launch dedicated vaults for upcoming subscriber milestones (e.g. 100K or 1M subs), podcast season finales, gaming marathons, annual anniversaries, or special community events.',
            ],
            [
                'category' => 'security',
                'q' => 'Is fan data and payment information secure?',
                'a' => 'All card transactions are tokenized through Stripe (PCI-DSS Level 1 compliant). Sealed letters are encrypted at rest with AES-256 and transmitted exclusively over secure TLS 1.3.',
            ],
        ];
    }

    public function with(): array
    {
        $faqItems = $this->faqItems();
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
        $uniquePeople = (int) Postcard::query()->whereNotNull('name')->where('name', '!=', '')->distinct('name')->count('name');
        if ($uniquePeople === 0 && $count > 0) {
            $uniquePeople = min($count, 62);
        }

        return compact(
            'count', 'uniquePeople', 'latest', 'creators', 'creatorsCount',
            'creatorLettersCount', 'totalPaidCents', 'foundingLeft', 'fill', 'faqItems'
        );
    }
};
?>

<div class="space-y-16 sm:space-y-24 pb-16">
  <!-- 1. FLAT ARCHITECTURAL HERO (No curves, edge-to-edge flat dark emerald canvas) -->
  <section class="relative w-full bg-[#03261d] text-white border-b border-emerald-900/60 overflow-hidden">
    <!-- Subtle architectural grid lines (Linear/Stripe style) -->
    <div class="absolute inset-0 opacity-[0.04] pointer-events-none bg-[linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
    
    <!-- Delicate stationary light gradient, static & elegant -->
    <div class="absolute top-0 right-1/4 w-[600px] h-[300px] bg-emerald-500/10 blur-[120px] pointer-events-none"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 sm:pt-24 pb-16 sm:pb-24 relative z-10">
      <div class="max-w-3xl mx-auto text-center space-y-6">
        <!-- Eyebrow Badge (Flat, crisp, understated) -->
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded text-xs font-mono text-emerald-300">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          <span class="font-semibold uppercase tracking-wider text-[11px]">The Time Capsule for Creators</span>
          <span class="text-white/20">/</span>
          <span class="text-white/70">FanVault Protocol</span>
        </div>

        <!-- Headline: Sharp, high-contrast, editorial Fraunces font -->
        <h1 class="font-serif text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-white leading-[1.12]">
          Turn Community Moments into Sealed Keepsakes
        </h1>

        <!-- Subtitle: Crisp and clear -->
        <p class="text-base sm:text-lg text-emerald-100/80 leading-relaxed max-w-2xl mx-auto font-sans font-light">
          A permanent digital archive for creator milestones. Fans leave letters, memories, and predictions today — unsealed live on stream when the moment arrives.
        </p>

        <!-- Flat Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 rounded-lg bg-emerald-400 hover:bg-emerald-300 text-[#03261d] font-bold text-sm transition-all shadow-sm">
            <span>Create Your Time Capsule (Free)</span>
            <span>→</span>
          </a>
          <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/15 text-white font-medium text-sm transition-all">
            Explore Active Capsules
          </a>
        </div>
      </div>

      <!-- Hero Visual Showcase: Flat, High-Tech Interactive Ledger Deck -->
      <div x-data="{ tab: 'sealed', sec: 42, init() { setInterval(() => { this.sec = this.sec > 0 ? this.sec - 1 : 59 }, 1000) } }" class="mt-12 sm:mt-16 max-w-3xl mx-auto">
        <!-- Outer Flat Frame -->
        <div class="border border-emerald-900/80 bg-[#021c15] rounded-xl shadow-2xl overflow-hidden">
          
          <!-- Frame Top Bar: Tabs & Live System Status -->
          <div class="flex flex-wrap items-center justify-between border-b border-emerald-900/60 bg-[#01140f] px-4 py-2.5 gap-2 text-xs font-mono">
            <div class="flex items-center gap-2">
              <button @click="tab = 'sealed'" :class="tab === 'sealed' ? 'bg-white/10 text-emerald-300 border-white/20' : 'text-slate-400 hover:text-white border-transparent'" class="px-3 py-1 rounded border transition-colors flex items-center gap-1.5 text-xs font-semibold">
                <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span>01 / SEALED VAULT</span>
              </button>
              <button @click="tab = 'stream'" :class="tab === 'stream' ? 'bg-white/10 text-emerald-300 border-white/20' : 'text-slate-400 hover:text-white border-transparent'" class="px-3 py-1 rounded border transition-colors flex items-center gap-1.5 text-xs font-semibold">
                <svg class="w-3.5 h-3.5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>02 / OBS STREAM REVEAL</span>
              </button>
            </div>
            <div class="flex items-center gap-2 text-[11px] text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
              <span class="tracking-widest">END-TO-END ENCRYPTED</span>
            </div>
          </div>

          <!-- TAB 1: SEALED VAULT PASS -->
          <div x-show="tab === 'sealed'" class="p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-emerald-900/40 text-xs font-mono">
              <div class="text-slate-400">
                LEDGER PASS: <span class="text-emerald-300 font-bold">#00010482</span>
              </div>
              <div class="text-slate-400 flex items-center gap-2">
                <span>HASH:</span>
                <span class="text-slate-300 bg-white/5 px-2 py-0.5 rounded border border-white/10 text-[11px]">SHA-256: 8f4e2...e9a1</span>
              </div>
            </div>

            <!-- Authentic Sealed Postcard Layout -->
            <div class="bg-[#faf9f5] text-[#0f172a] rounded-lg p-5 sm:p-6 space-y-4 border border-[#e7e5df]">
              <div class="flex items-center justify-between text-xs font-mono text-[#64748b]">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-[#ecfdf5] text-[#064e3b] font-bold rounded border border-[#a7f3d0]">
                  COMMUNITY PREDICTION
                </span>
                <span>DESTINATION: 100K STREAM</span>
              </div>

              <blockquote class="font-serif italic text-base sm:text-xl text-[#0f172a] leading-relaxed">
                “When you open this at 100k subs, you'll be streaming full-time from your new Tokyo studio!”
              </blockquote>

              <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-200 text-xs text-[#64748b]">
                <div class="font-mono">
                  Sealed by <strong class="text-[#0f172a]">@alex_m</strong> · Kyoto, Japan
                </div>
                <div class="font-mono text-[#047857] font-semibold flex items-center gap-1">
                  <span>🔒 Sealed & Encrypted</span>
                </div>
              </div>
            </div>

            <!-- Ticking Countdown Module -->
            <div class="flex flex-wrap items-center justify-between gap-4 pt-2 text-xs font-mono">
              <div class="text-slate-400">
                UNSEAL COUNTDOWN:
              </div>
              <div class="flex items-center gap-2">
                <span class="bg-white/5 px-2.5 py-1 rounded border border-white/10 text-emerald-300 font-bold">048 DAYS</span>
                <span class="text-slate-600">:</span>
                <span class="bg-white/5 px-2.5 py-1 rounded border border-white/10 text-emerald-300 font-bold">14 HOURS</span>
                <span class="text-slate-600">:</span>
                <span class="bg-white/5 px-2.5 py-1 rounded border border-white/10 text-emerald-300 font-bold">22 MINS</span>
                <span class="text-slate-600">:</span>
                <span class="bg-white/5 px-2.5 py-1 rounded border border-white/10 text-amber-300 font-bold" x-text="(sec < 10 ? '0' : '') + sec + ' SECS'">42 SECS</span>
              </div>
            </div>
          </div>

          <!-- TAB 2: OBS STREAM REVEAL -->
          <div x-show="tab === 'stream'" x-cloak class="p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-emerald-900/40 text-xs font-mono">
              <div class="flex items-center gap-2 text-rose-400 font-bold">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>OBS STUDIO DOCK · 1080p60 PRESENTATION</span>
              </div>
              <span class="text-slate-400">CUE CARD 42 / 100</span>
            </div>

            <div class="bg-slate-900 text-white rounded-lg p-5 sm:p-6 space-y-3 border border-emerald-500/30">
              <div class="flex items-center justify-between text-xs font-mono text-emerald-400">
                <span>REVEALED LIVE ON CAMERA</span>
                <span class="text-amber-400 font-bold">★ STARRED ENTRY</span>
              </div>
              <p class="font-serif italic text-lg sm:text-2xl text-emerald-50 leading-relaxed">
                “Been here since stream #3 when only 8 people watched. Thank you for making lonely nights brighter.”
              </p>
              <div class="flex items-center justify-between text-xs text-slate-400 pt-3 border-t border-slate-800 font-mono">
                <span>Written 2 years ago by @sarah_k</span>
                <span class="text-emerald-400">Stripe Payout Settled</span>
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-slate-400 pt-2">
              <span>Hotkeys: [Space] Reveal · [→] Next Letter</span>
              <span class="text-emerald-300 font-semibold">Zero Spoilers Before Stream Broadcast</span>
            </div>
          </div>

          <!-- Bottom Ledger Metrics Bar -->
          <div class="border-t border-emerald-900/60 bg-[#01140f] px-6 py-3 flex flex-wrap items-center justify-between gap-3 text-xs font-mono text-emerald-200">
            <div class="flex items-center gap-4">
              <span><strong class="text-white">{{ number_format($count) }}</strong> Letters Sealed</span>
              <span class="text-emerald-800">/</span>
              <span><strong class="text-white">{{ number_format($uniquePeople) }}</strong> Fans Joined</span>
            </div>
            <div class="text-slate-400">
              Automatic Stripe Direct Transfers (80/20)
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- 2. THREE KEY VALUE PROPS: Flat Architectural Cards with Crisp Borders -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-xl mx-auto mb-10 sm:mb-12 space-y-2">
      <h2 class="font-serif text-2xl sm:text-4xl font-bold text-[#0f172a] tracking-tight">
        Why Creators & Fans Love FanVault
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        The digital alternative to physical fan mail — built for live celebration broadcasts.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
      <!-- Feature 1 -->
      <div class="bg-white border border-[#e7e5df] hover:border-[#047857] rounded-xl p-6 sm:p-8 transition-colors flex flex-col justify-between">
        <div class="space-y-4">
          <div class="w-12 h-12 rounded-lg bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-[#047857]">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
            Zero Spoilers Until Reveal
          </h3>
          <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
            Every submission stays cryptographically sealed. Nobody — not even the creator — can peek early until the milestone stream unboxing.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-[#f1f0eb] font-mono text-[11px] text-[#047857] font-semibold">
          Cryptographically Vaulted
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="bg-white border border-[#e7e5df] hover:border-[#047857] rounded-xl p-6 sm:p-8 transition-colors flex flex-col justify-between">
        <div class="space-y-4">
          <div class="w-12 h-12 rounded-lg bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-[#047857]">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
            Unboxed Live on Stream
          </h3>
          <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
            Broadcast Stream Mode gives you high-contrast cue cards in OBS to reveal letters, react to wild predictions, and celebrate on camera.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-[#f1f0eb] font-mono text-[11px] text-[#047857] font-semibold">
          OBS Studio Compatible
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="bg-white border border-[#e7e5df] hover:border-[#047857] rounded-xl p-6 sm:p-8 transition-colors flex flex-col justify-between">
        <div class="space-y-4">
          <div class="w-12 h-12 rounded-lg bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-[#047857]">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
          </div>
          <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a]">
            Zero Clutter, Direct Payouts
          </h3>
          <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
            Replaces physical PO Box hassle. International fans join with zero shipping friction, supporting at your chosen price with Stripe payouts.
          </p>
        </div>
        <div class="mt-6 pt-4 border-t border-[#f1f0eb] font-mono text-[11px] text-[#047857] font-semibold">
          Direct Stripe Integration
        </div>
      </div>
    </div>
  </section>

  <!-- 2.5 BUILT FOR EVERY KIND OF CREATOR: Interactive Personas Showcase -->
  <x-creator-personas />

  <!-- 3. HOW THE TIME CAPSULE WORKS: Clean Flat 3-Step Ceremony -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-[#f7f6f0] border border-[#e7e5df] rounded-xl p-6 sm:p-10 lg:p-12">
      <div class="text-center max-w-xl mx-auto mb-10 sm:mb-12 space-y-2">
        <h2 class="font-serif text-2xl sm:text-4xl font-bold text-[#0f172a] tracking-tight">
          How the Time Capsule Works
        </h2>
        <p class="text-xs sm:text-sm text-[#64748b]">
          A straightforward three-step protocol from creation to broadcast.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
        <!-- Step 1 -->
        <div class="bg-white border border-[#e7e5df] rounded-lg p-6 space-y-3">
          <div class="font-mono text-xs font-bold text-[#047857] tracking-wider uppercase">
            01 / SET MILESTONE
          </div>
          <h3 class="font-serif text-lg font-bold text-[#0f172a]">
            Define Your Target
          </h3>
          <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
            Choose an upcoming milestone: a 100K sub stream, channel birthday, or anniversary. Set your seal price and get your personal link.
          </p>
        </div>

        <!-- Step 2 -->
        <div class="bg-white border border-[#e7e5df] rounded-lg p-6 space-y-3">
          <div class="font-mono text-xs font-bold text-[#047857] tracking-wider uppercase">
            02 / VAULT ENTRIES
          </div>
          <h3 class="font-serif text-lg font-bold text-[#0f172a]">
            Fans Leave Messages
          </h3>
          <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
            Fans write personal memories and future predictions. Every entry is encrypted and securely locked inside the vault until reveal day.
          </p>
        </div>

        <!-- Step 3 -->
        <div class="bg-white border border-[#e7e5df] rounded-lg p-6 space-y-3">
          <div class="font-mono text-xs font-bold text-[#047857] tracking-wider uppercase">
            03 / BROADCAST
          </div>
          <h3 class="font-serif text-lg font-bold text-[#0f172a]">
            Unseal Live on Stream
          </h3>
          <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
            When the milestone arrives, launch OBS Stream Mode. Reveal the letters live on camera and react to predictions with your chat.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. ACTIVE CREATOR TIME CAPSULES -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a]">
          Active Creator Time Capsules
        </h2>
        <p class="text-xs sm:text-sm text-[#64748b] mt-1">
          Milestones waiting to be opened. Seal your message before the vault locks.
        </p>
      </div>
      <a href="{{ route('creators') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-colors">
        Browse All ({{ $creatorsCount }}) →
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($creators as $c)
        @php
          $daysLeft = $c->daysUntilUnlock();
          $progress = $c->unlockProgress();
        @endphp
        <div class="bg-white border border-[#e7e5df] hover:border-[#047857] rounded-xl p-6 transition-colors flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between gap-3 mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#064e3b] text-white flex items-center justify-center font-bold text-sm shadow-2xs">
                  {{ strtoupper(substr($c->name, 0, 1)) }}
                </div>
                <div>
                  <h3 class="font-serif text-base font-bold text-[#0f172a] leading-tight">
                    {{ $c->name }}
                  </h3>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs text-[#64748b] font-mono">{{ $c->handle }}</span>
                    <span class="text-[10px] font-medium uppercase px-2 py-0.5 rounded bg-[#f1f5f9] text-[#475569]">
                      {{ $c->platform }}
                    </span>
                  </div>
                </div>
              </div>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#ecfdf5] text-[#064e3b] text-xs font-mono font-medium rounded border border-[#a7f3d0]">
                🔒 {{ $c->postcards_count }} sealed
              </span>
            </div>

            <!-- Milestone Goal -->
            <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-lg p-3.5 mb-4">
              <div class="text-[11px] font-mono uppercase font-semibold text-[#64748b]">Destination</div>
              <div class="text-sm font-semibold text-[#0f172a] mt-0.5">
                {{ $c->milestone_title ?: 'Community Milestone' }}
              </div>
            </div>
          </div>

          <!-- Bottom Action & Countdown -->
          <div class="pt-3 border-t border-[#f1f0eb] space-y-2.5">
            <div class="flex items-center justify-between text-xs">
              <span class="text-[#64748b]">Unsealing Ceremony:</span>
              <strong class="text-[#047857] font-semibold">
                {{ $c->formattedUnlockDate() }}
                @if($daysLeft !== null)
                  <span class="text-[10px] font-mono text-[#064e3b] bg-[#ecfdf5] px-1.5 py-0.5 rounded border border-[#a7f3d0]">({{ $daysLeft }}d)</span>
                @endif
              </strong>
            </div>

            <a href="{{ route('with', $c->slug) }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-[#ecfdf5] hover:bg-[#064e3b] text-[#064e3b] hover:text-white font-medium text-xs sm:text-sm border border-[#a7f3d0] hover:border-[#064e3b] transition-colors">
              Leave a Message for {{ explode(' ', $c->name)[0] }} →
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <!-- 5. THE COMMUNITY ARCHIVE (Recent Teaser Wall) -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="text-center max-w-xl mx-auto">
      <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-2">
        The Community Archive
      </h2>
      <p class="text-xs sm:text-sm text-[#64748b]">
        You can see the teaser line. The full message remains strictly encrypted until the live stream unboxing.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      @foreach($latest as $msg)
        <x-voice-card :msg="$msg" />
      @endforeach
    </div>

    <div class="text-center pt-2">
      <a href="{{ route('explore') }}" class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-lg bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-colors">
        Browse all {{ number_format($count) }} sealed contributions on the Archive Wall →
      </a>
    </div>
  </section>

  <!-- 6. FREQUENTLY ASKED QUESTIONS -->
  <section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ active: null, tab: 'all' }">
    <div class="text-center max-w-2xl mx-auto space-y-3">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] font-mono text-xs font-medium">
        <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
        <span>FREQUENTLY ASKED QUESTIONS</span>
      </div>
      <h2 class="font-serif text-3xl sm:text-4xl font-bold tracking-tight text-[#0f172a]">
        Everything you need to know.
      </h2>
      <p class="text-sm sm:text-base text-[#64748b] leading-relaxed">
        Got questions about how Creator Time Capsules work, stream reveals, or payouts? Here are the answers.
      </p>

      <!-- Category Filter Pills -->
      <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
        <button 
          type="button" 
          @click="tab = 'all'" 
          :class="tab === 'all' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3.5 py-1.5 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          All ({{ count($faqItems) }})
        </button>
        <button 
          type="button" 
          @click="tab = 'fans'" 
          :class="tab === 'fans' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3.5 py-1.5 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          For Fans
        </button>
        <button 
          type="button" 
          @click="tab = 'creators'" 
          :class="tab === 'creators' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3.5 py-1.5 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          For Creators
        </button>
        <button 
          type="button" 
          @click="tab = 'security'" 
          :class="tab === 'security' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3.5 py-1.5 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          Security & Payments
        </button>
      </div>
    </div>

    <!-- Accordion Items -->
    <div class="space-y-3">
      @foreach($faqItems as $i => $item)
        <div 
          x-show="tab === 'all' || tab === '{{ $item['category'] }}'" 
          x-transition
          class="bg-white border border-[#e7e5df] hover:border-[#a7f3d0] rounded-xl overflow-hidden transition-all shadow-xs"
        >
          <button 
            type="button" 
            @click="active = (active === {{ $i }} ? null : {{ $i }})" 
            class="w-full text-left px-5 sm:px-6 py-4 sm:py-5 flex items-center justify-between gap-4 cursor-pointer focus:outline-none"
            :aria-expanded="active === {{ $i }}"
          >
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-md bg-[#faf9f5] border border-[#e7e5df] text-[#064e3b] font-mono text-xs flex items-center justify-center shrink-0">
                {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
              </span>
              <span class="font-serif font-bold text-base sm:text-lg text-[#0f172a] leading-snug">
                {{ $item['q'] }}
              </span>
            </div>
            <span 
              class="w-7 h-7 rounded-lg bg-[#faf9f5] border border-[#e7e5df] text-[#047857] flex items-center justify-center shrink-0 transition-transform duration-200"
              :class="active === {{ $i }} ? 'rotate-180 bg-[#ecfdf5] border-[#a7f3d0]' : ''"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </span>
          </button>
          
          <div 
            x-show="active === {{ $i }}" 
            x-cloak 
            class="px-5 sm:px-6 pb-5 pt-1 text-sm text-[#475569] leading-relaxed border-t border-[#f1f0eb]"
          >
            <p>{{ $item['a'] }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Still have questions footer box -->
    <div class="p-6 rounded-xl bg-[#faf9f5] border border-[#e7e5df] flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="text-center sm:text-left">
        <h4 class="font-serif text-base font-bold text-[#0f172a]">Still have a question?</h4>
        <p class="text-xs sm:text-sm text-[#64748b] mt-0.5">We're happy to walk you through setting up your community time capsule.</p>
      </div>
      <div class="flex items-center gap-2.5 shrink-0">
        <a href="{{ route('creators.join') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white text-xs font-medium transition-all shadow-xs">
          Open a Vault →
        </a>
        <a href="mailto:support@getfanvault.com" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-white hover:bg-[#f5f4ee] border border-[#e7e5df] text-[#0f172a] text-xs font-medium transition-all shadow-xs">
          Contact Team
        </a>
      </div>
    </div>
  </section>

  <!-- 7. FLAT BOTTOM CALL TO ACTION -->
  <section class="w-full bg-[#03261d] text-white border-t border-b border-emerald-900/60 py-16 sm:py-20 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto text-center space-y-5">
      <h2 class="font-serif text-2xl sm:text-4xl font-bold leading-tight">
        Preserve your community's journey.
      </h2>
      <p class="text-sm sm:text-base text-emerald-100/80 leading-relaxed max-w-xl mx-auto font-light">
        Launch your Creator Time Capsule in 60 seconds. Free to set up, permanent archival, and built for stream reveals.
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
        <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-lg bg-emerald-400 hover:bg-emerald-300 text-[#03261d] font-bold text-sm sm:text-base transition-colors shadow-sm">
          Create Your Time Capsule (Free)
        </a>
        <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/15 text-white font-semibold text-sm sm:text-base transition-colors">
          Browse Active Capsules
        </a>
      </div>
    </div>
  </section>
</div>
