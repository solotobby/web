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
        $faqs = [
            'What is FanVault?' => 'FanVault is the time capsule for creators and their communities. Fans leave messages, memories, and predictions for the future, sealed until a creator milestone or celebration arrives.',
            'When are the sealed messages unlocked?' => 'Capsules stay encrypted and private until the creator reaches their milestone target date and unlocks the vault during their live broadcast stream.',
            'What can fans leave inside a time capsule?' => 'Fans can leave heartfelt letters, wild milestone predictions, community memories, and keepsake photos — sealed until the unsealing ceremony.',
            'How does pricing work for creators?' => 'Creators set their own seal amount instead of being locked into a rigid fee. Fans contribute at the creator’s chosen level with optional booster tips, and creators receive direct payouts via Stripe.',
            'Does FanVault replace a physical PO Box?' => 'Yes. FanVault delivers all the warmth and emotion of fan mail with zero physical mail sorting, zero storage clutter, and full global access for international fans.',
        ];

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

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12 space-y-16 sm:space-y-24">
  <!-- 1. HERO SECTION: The Creator Time Capsule -->
  <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
    <div class="lg:col-span-7 space-y-5 sm:space-y-6 text-center lg:text-left">
      <!-- Eyebrow Badge -->
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium shadow-2xs">
        <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
        <span class="font-semibold">The Creator Time Capsule</span>
        <span class="text-[#64748b]">·</span>
        <span class="text-[#475569]">Powered by FanVault</span>
      </div>
      
      <!-- Main Emotional Headline -->
      <div>
        <h1 class="font-serif text-3xl sm:text-5xl lg:text-6xl font-normal tracking-tight text-[#0f172a] leading-[1.12]">
          The Time Capsule for <em class="italic text-[#047857] font-semibold">Creators</em>.
        </h1>
        <p class="text-lg sm:text-xl md:text-2xl text-[#334155] font-serif italic mt-3 sm:mt-4 leading-snug">
          Give your community a place to leave messages, memories and predictions for the future.
        </p>
        <p class="text-base sm:text-lg font-bold text-[#064e3b] mt-2">
          Seal them today. Open them when the moment arrives.
        </p>
      </div>

      <!-- Tagline Pill Badges -->
      <div class="flex flex-wrap items-center justify-center lg:justify-start gap-1.5 text-xs font-mono text-[#475569]">
        <span class="px-2.5 py-1 rounded-full bg-white border border-[#e7e5df] shadow-2xs">🎉 Milestones</span>
        <span class="px-2.5 py-1 rounded-full bg-white border border-[#e7e5df] shadow-2xs">🎂 Birthdays</span>
        <span class="px-2.5 py-1 rounded-full bg-white border border-[#e7e5df] shadow-2xs">❤️ Anniversaries</span>
        <span class="px-2.5 py-1 rounded-full bg-white border border-[#e7e5df] shadow-2xs">🔮 Predictions</span>
        <span class="px-2.5 py-1 rounded-full bg-white border border-[#e7e5df] shadow-2xs">📸 Memories</span>
      </div>

      <!-- Explanatory Subtext -->
      <p class="text-sm sm:text-base text-[#64748b] leading-relaxed max-w-xl mx-auto lg:mx-0">
        A digital, permanent alternative to fan mail. Fans contribute meaningful memories and predictions at your chosen amount. When your milestone arrives, unseal the capsule live on stream and create an unforgettable broadcast.
      </p>

      <!-- Primary CTAs -->
      <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3 pt-2">
        <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:shadow-[0_4px_16px_rgba(6,78,59,0.35)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
          Create Your Time Capsule (Free)
        </a>
        <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 sm:py-4 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-xs">
          Explore Time Capsules
        </a>
      </div>

      <!-- Trust signals -->
      <div class="flex flex-wrap items-center justify-center lg:justify-start gap-x-4 gap-y-1.5 text-xs text-[#64748b] pt-1">
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Encrypted until reveal</span>
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Unsealed live on stream</span>
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Creator-defined pricing</span>
        <span class="flex items-center gap-1.5"><span class="w-1 h-1 rounded-full bg-[#047857]"></span>Zero physical clutter</span>
      </div>
    </div>

    <!-- Hero Archival Pass Card -->
    <aside class="lg:col-span-5">
      <div class="relative bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.04)] text-center overflow-hidden">
        <!-- Minimal Badge -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f8fafc] text-[#475569] border border-[#e2e8f0] text-[10px] font-mono uppercase tracking-wider mb-4">
          Official Community Archive Pass
        </div>
        
        <div class="my-3">
          <div class="font-serif text-4xl sm:text-5xl font-normal text-[#0f172a] tracking-tight">
            {{ number_format($count) }}
          </div>
          <div class="text-xs sm:text-sm text-[#64748b] font-medium mt-1">
            Community contributions sealed for the future
          </div>
        </div>

        <!-- Capsule Breakdown Features -->
        <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-4 my-5 text-left space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="text-[#064e3b] font-semibold flex items-center gap-1.5">
              <span>💌</span> Letters & Notes
            </span>
            <span class="font-mono text-[11px] text-[#64748b]">Private until open</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-[#064e3b] font-semibold flex items-center gap-1.5">
              <span>🔮</span> Future Predictions
            </span>
            <span class="font-mono text-[11px] text-[#64748b]">Locked in today</span>
          </div>
          <div class="flex items-center justify-between text-xs">
            <span class="text-[#064e3b] font-semibold flex items-center gap-1.5">
              <span>❤️</span> Community Memories
            </span>
            <span class="font-mono text-[11px] text-[#64748b]">Permanent archive</span>
          </div>
        </div>

        <!-- Capacity Indicator -->
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
          Have a creator you follow? 
          <a href="{{ route('nominate') }}" class="text-[#047857] font-semibold hover:underline">
            Nominate them for a Time Capsule →
          </a>
        </div>
      </div>
    </aside>
  </section>

  <!-- 2. EXPLORE TIME CAPSULES: Featured Creator Time Capsules -->
  <section class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-semibold uppercase tracking-wider mb-2">
          Active Time Capsules
        </div>
        <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a]">
          Moments waiting to be opened
        </h2>
        <p class="text-xs sm:text-sm text-[#64748b] mt-1">
          Seal your message, memory, or prediction before the creator unlocks the vault live on stream.
        </p>
      </div>
      <a href="{{ route('creators') }}" class="inline-flex items-center gap-1 px-4.5 py-2 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm shadow-xs transition-all">
        Browse All Capsules ({{ $creatorsCount }}) →
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
                🔒 {{ $c->postcards_count }} sealed
              </span>
            </div>

            <!-- Milestone Capsule Goal -->
            <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-3.5 mb-4">
              <div class="text-[11px] font-mono uppercase font-semibold text-[#64748b]">Capsule Destination</div>
              <div class="text-sm font-semibold text-[#0f172a] mt-0.5">
                {{ $c->milestone_title ?: 'Community Milestone Capsule' }}
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
              <span class="text-[#64748b]">Unsealing Date:</span>
              <strong class="text-[#047857] font-semibold">
                {{ $c->formattedUnlockDate() }}
                @if($daysLeft !== null)
                  <span class="text-xs font-mono text-[#64748b]">({{ $daysLeft }}d remaining)</span>
                @endif
              </strong>
            </div>

            <a href="{{ route('with', $c->slug) }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-full bg-[#ecfdf5] hover:bg-[#064e3b] text-[#064e3b] hover:text-white font-medium text-xs sm:text-sm border border-[#a7f3d0]/70 hover:border-[#064e3b] transition-all">
              Leave Something for {{ explode(' ', $c->name)[0] }} →
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <!-- 3. THE CEREMONY JOURNEY: How the Time Capsule Works -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs">
    <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-12">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f8fafc] border border-[#e2e8f0] text-[#475569] text-xs font-mono uppercase tracking-wider mb-2">
        The Capsule Lifecycle
      </div>
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        How the Creator Time Capsule works
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        From anticipation today to the live broadcast opening tomorrow.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 shadow-2xs">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-widest uppercase mb-3">STAGE 01 · CREATE</div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2">
          Create Your Time Capsule
        </h3>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          Choose a future moment: a 100K subscriber milestone, a birthday, an anniversary, or community predictions. Get your personal link to share in your video descriptions and bio.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 shadow-2xs">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-widest uppercase mb-3">STAGE 02 · SEAL</div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2">
          Fans Leave Memories & Predictions
        </h3>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          Fans write personal messages, milestone predictions, and heartfelt memories. Each submission is locked under vault encryption—nobody, not even the creator, can read them early.
        </p>
      </div>

      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 shadow-2xs">
        <div class="text-xs font-mono font-semibold text-[#047857] tracking-widest uppercase mb-3">STAGE 03 · UNLOCK</div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2">
          Open Live on Stream
        </h3>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          When the moment arrives, launch Stream Mode. Reveal the letters live on camera, react to hilarious predictions, and turn community anticipation into legendary content.
        </p>
      </div>
    </div>
  </section>

  <!-- 4. STREAM MODE SPOTLIGHT: The Unsealing Experience -->
  <section class="bg-gradient-to-br from-[#064e3b] via-[#047857] to-[#022c22] rounded-3xl p-8 sm:p-12 text-white shadow-xl relative overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
      <div class="lg:col-span-7 space-y-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white text-xs font-mono uppercase tracking-wider">
          <span>🎬</span> OBS & Stream Deck Ready
        </div>
        <h2 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal leading-tight">
          The opening is the <em class="italic font-semibold text-emerald-300">killer event</em>.
        </h2>
        <p class="text-sm sm:text-base text-emerald-100/90 leading-relaxed max-w-xl">
          Don't just open fan mail off camera. FanVault's broadcast <strong>Stream Mode</strong> gives you high-contrast cue cards, unsealing animations, and presenter hotkeys built specifically for live stream captures on YouTube and Twitch.
        </p>
        <div class="flex flex-wrap gap-4 pt-2 text-xs font-mono text-emerald-200">
          <span class="flex items-center gap-1.5">✓ Fullscreen OBS Display</span>
          <span class="flex items-center gap-1.5">✓ One-Click Star & Favourites</span>
          <span class="flex items-center gap-1.5">✓ Prediction Reveal Cards</span>
        </div>
      </div>

      <div class="lg:col-span-5 bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-6 text-left space-y-3 shadow-2xl">
        <div class="flex items-center justify-between text-xs font-mono text-emerald-300">
          <span>LIVE STREAM READER</span>
          <span>CARD 042 / 438</span>
        </div>
        <div class="p-4 bg-white/95 text-[#0f172a] rounded-xl shadow-inner space-y-2">
          <div class="flex items-center justify-between text-[11px] font-mono text-[#047857]">
            <span class="font-bold">🔮 PREDICTION</span>
            <span>From Marcus (UK)</span>
          </div>
          <p class="font-serif italic text-base text-[#0f172a]">
            “I predict when you open this at 100K subs, you'll be doing full-time content and living in Tokyo!”
          </p>
        </div>
        <div class="flex items-center justify-between text-xs text-white/80 pt-1">
          <span>Controls: [← Prev] [Next →]</span>
          <span class="text-amber-300">⭐ Starred Favorite</span>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. THE COMMUNITY ARCHIVE (Fan Wall 2.0) -->
  <section class="space-y-6">
    <div class="text-center max-w-2xl mx-auto">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-semibold uppercase tracking-wider mb-2">
        The Community Archive
      </div>
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        A glimpse into what fans are leaving behind
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        Real teaser lines from letters sealed across creator capsules worldwide. The full messages remain sealed until reveal day.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      @foreach($latest as $msg)
        <x-voice-card :msg="$msg" />
      @endforeach
    </div>

    <div class="text-center pt-2">
      <a href="{{ route('explore') }}" class="inline-flex items-center gap-1.5 px-6 py-3 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm hover:-translate-y-0.5 transition-all shadow-xs">
        Browse all {{ number_format($count) }} sealed contributions on the Archive Wall →
      </a>
    </div>
  </section>

  <!-- 6. AND YES, IT REPLACES YOUR PO BOX (Comparison Section) -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs">
    <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-10">
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        And yes, it replaces your PO Box.
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        Physical mail is expensive, cluttered, and localized. FanVault gives creators genuine connection without the logistical headache.
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
            <span>Costs $250–$500/year just to rent a physical box.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>Cardboard boxes and clutter taking over your apartment.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>International fans pay expensive postage or cannot participate.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#94a3b8] font-bold">—</span>
            <span>Zero revenue — 100% expense and time drain.</span>
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
            <span><strong>Zero clutter</strong> — encrypted digital archive and broadcast stream reader.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#047857] font-bold">✓</span>
            <span><strong>Global access</strong> — international fans participate instantly from anywhere in the world.</span>
          </li>
          <li class="flex items-start gap-2.5">
            <span class="text-[#047857] font-bold">✓</span>
            <span><strong>Creator-defined pricing</strong> — choose your own amount with direct Stripe payouts.</span>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- 7. CREATOR ECONOMICS SIMULATOR -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-xs"
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
        Creator Economics Simulator
      </div>
      <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2">
        Model your milestone revenue
      </h2>
      <p class="text-sm sm:text-base text-[#64748b] leading-relaxed">
        Simulate your projected earnings based on community size and chosen price floor under FanVault’s transparent 80/20 revenue model. Direct automated payouts via Stripe.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center max-w-5xl mx-auto">
      <!-- Interactive Controls -->
      <div class="lg:col-span-6 space-y-6 bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 sm:p-7">
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-xs font-mono font-semibold text-[#475569] uppercase tracking-wider">
              Participating Fans:
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
            <span>100 fans</span>
            <span>1,000 fans</span>
            <span>5,000 fans</span>
          </div>
        </div>

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

        <div class="pt-4 border-t border-[#e7e5df] text-xs text-[#64748b] space-y-2">
          <div class="flex items-center justify-between font-mono text-[11px]">
            <span class="text-[#334155] font-semibold">Tipping Distribution:</span>
            <span class="text-[#047857] font-semibold" x-text="'Est. Avg: $' + avgGift.toFixed(2) + ' / fan'"></span>
          </div>
          <div class="h-2.5 w-full flex rounded-full overflow-hidden bg-[#e2e8f0]">
            <div class="bg-[#047857]" style="width: 60%"></div>
            <div class="bg-[#059669]" style="width: 25%"></div>
            <div class="bg-[#ca8a04]" style="width: 10%"></div>
            <div class="bg-[#b45309]" style="width: 5%"></div>
          </div>
          <div class="flex flex-wrap items-center justify-between text-[10px] text-[#64748b] font-mono pt-0.5">
            <span>60% Floor</span>
            <span>25% $10 Supporter</span>
            <span>10% $25 Superfan</span>
            <span>5% $50 VIP</span>
          </div>
        </div>
      </div>

      <!-- Calculated Results Display -->
      <div class="lg:col-span-6 bg-white border border-[#a7f3d0] rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(4,120,87,0.06)] flex flex-col justify-between space-y-6">
        <div>
          <span class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857] block mb-1">
            Simulated Creator Share (80%)
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
            <span class="block text-[11px] font-mono text-[#64748b]">Old Flat Model:</span>
            <strong class="text-sm sm:text-base font-mono text-[#334155] block mt-0.5">$<span x-text="flatPayout.toLocaleString()"></span></strong>
            <span class="text-[10px] text-[#94a3b8] block">Rigid flat cap</span>
          </div>

          <div class="bg-[#ecfdf5] border border-[#a7f3d0] rounded-2xl p-3.5">
            <span class="block text-[11px] font-mono text-[#064e3b]">Flexible Advantage:</span>
            <strong class="text-sm sm:text-base font-mono text-[#047857] block mt-0.5" x-text="multiplier + '× More' "></strong>
            <span class="text-[10px] text-[#064e3b] block">With booster tips</span>
          </div>
        </div>

        <div class="text-xs text-[#475569] leading-relaxed pt-1 space-y-2">
          <p>
            💡 <strong>Fair economics that respect your audience:</strong> Choose the price that feels right for your community, while superfans can add $10, $25, or $50 booster tips to back the milestone.
          </p>
          <p class="text-[11px] text-[#94a3b8] font-mono">
            ※ Model simulation for planning purposes under the 80/20 platform split. Actual proceeds depend on community participation and voluntary tips.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- 8. NOMINATE A CREATOR CALLOUT -->
  <section class="bg-[#faf9f5] border border-[#e7e5df] rounded-3xl p-6 sm:p-10 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xs">
    <div class="space-y-1 text-center sm:text-left">
      <span class="text-xs font-mono uppercase tracking-wider font-semibold text-[#047857]">
        ✨ Organic Community Discovery
      </span>
      <h3 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a]">
        Know a creator approaching a milestone?
      </h3>
      <p class="text-xs sm:text-sm text-[#64748b] max-w-lg">
        Nominate your favorite YouTube, Twitch, or podcast creator. We’ll build a customized time capsule invite for their community.
      </p>
    </div>
    <a href="{{ route('nominate') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-xs sm:text-sm shrink-0 shadow-xs transition-all">
      <span>Nominate a Creator</span>
      <span>→</span>
    </a>
  </section>

  <!-- 9. BOTTOM FINAL CTA -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-8 sm:p-14 text-center shadow-[0_4px_24px_rgba(0,0,0,0.03)] relative overflow-hidden">
    <h2 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-normal text-[#0f172a] mb-2 sm:mb-3">
      Preserve your community's journey.
    </h2>
    <p class="text-sm sm:text-base text-[#64748b] max-w-xl mx-auto mb-6">
      Launch your Creator Time Capsule in 60 seconds. Paste your link in your next video description, and let your superfans build an unforgettable celebration with you.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
      <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
        Create Your Time Capsule (Free)
      </a>
      <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 sm:py-4 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 transition-all shadow-xs">
        Browse Active Capsules
      </a>
    </div>
  </section>
</div>
