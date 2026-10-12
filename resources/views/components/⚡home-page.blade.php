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
        $uniquePeople = (int) Postcard::query()->whereNotNull('name')->where('name', '!=', '')->distinct('name')->count('name');
        if ($uniquePeople === 0 && $count > 0) {
            $uniquePeople = min($count, 62);
        }

        return compact(
            'count', 'uniquePeople', 'latest', 'creators', 'creatorsCount',
            'creatorLettersCount', 'totalPaidCents', 'foundingLeft', 'fill'
        );
    }
};
?>

<div class="space-y-20 sm:space-y-28 pb-12">
  <!-- 1. HERO SECTION: Sleek, Giftpose-style dark emerald gradient hero -->
  <section class="relative -mt-6 sm:-mt-10 pt-16 sm:pt-24 pb-16 sm:pb-24 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-[#064e3b] via-[#043d2f] to-[#02241b] text-white rounded-3xl sm:rounded-[2.5rem] shadow-xl overflow-hidden">
    <!-- Ambient radial glow behind title -->
    <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(ellipse_at_65%_15%,rgba(52,211,153,0.18)_0%,transparent_60%)]" aria-hidden="true"></div>

    <div class="max-w-4xl mx-auto text-center relative z-10 space-y-6">
      <!-- Eyebrow Pill -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-emerald-300 text-xs font-mono uppercase tracking-wider shadow-sm">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>The Time Capsule for Creators</span>
      </div>

      <!-- Main Headline: Punchy, modern, non-wordy -->
      <h1 class="font-serif text-3xl sm:text-5xl lg:text-6xl font-bold tracking-tight text-white leading-[1.15] max-w-3xl mx-auto">
        Turn Community Moments into Sealed Keepsakes
      </h1>

      <!-- Succinct Subtitle -->
      <p class="text-base sm:text-lg md:text-xl text-emerald-100/90 leading-relaxed max-w-2xl mx-auto font-sans">
        Give your fans a place to leave letters, memories, and predictions for the future. Sealed today, unboxed live on stream when your milestone arrives.
      </p>

      <!-- Primary Action Buttons (Pill buttons with hover lift) -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
        <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 sm:py-4 rounded-full bg-emerald-400 hover:bg-emerald-300 text-[#064e3b] font-bold text-sm sm:text-base shadow-[0_4px_20px_rgba(52,211,153,0.35)] hover:-translate-y-0.5 transition-all">
          <span>Create Your Time Capsule (Free)</span>
          <span>→</span>
        </a>
        <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 sm:py-4 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/25 text-white font-semibold text-sm sm:text-base hover:-translate-y-0.5 transition-all">
          Explore Active Capsules
        </a>
      </div>

      <!-- Hero Visual Showcase: Floating Interactive Pass Card -->
      <div class="pt-8 sm:pt-12 max-w-2xl mx-auto">
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl sm:rounded-3xl p-5 sm:p-7 shadow-[0_20px_50px_rgba(0,0,0,0.4)] text-left space-y-4">
          <div class="flex items-center justify-between text-xs font-mono text-emerald-200">
            <span class="inline-flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-full border border-white/15">
              <span>🔒</span> Vault Status: Sealed & Encrypted
            </span>
            <span class="text-emerald-300 font-bold">● Active Ceremony</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <div class="bg-white/95 text-[#0f172a] rounded-2xl p-4 shadow-sm space-y-1">
              <span class="text-[11px] font-mono uppercase tracking-wider text-[#047857] font-bold">🔮 Sealed Prediction</span>
              <p class="font-serif italic text-sm text-[#0f172a] line-clamp-2">
                “When you open this at 100k subs, you'll be streaming full-time from Tokyo!”
              </p>
              <div class="flex items-center justify-between text-[10px] text-[#64748b] pt-1">
                <span>By @alex_m</span>
                <span class="text-amber-600 font-bold">⭐ Starred</span>
              </div>
            </div>

            <div class="bg-white/95 text-[#0f172a] rounded-2xl p-4 shadow-sm space-y-1">
              <span class="text-[11px] font-mono uppercase tracking-wider text-[#047857] font-bold">💌 Community Letter</span>
              <p class="font-serif italic text-sm text-[#0f172a] line-clamp-2">
                “Been here since stream #3. Thank you for making lonely nights brighter.”
              </p>
              <div class="flex items-center justify-between text-[10px] text-[#64748b] pt-1">
                <span>By @sarah_k</span>
                <span class="text-emerald-700 font-semibold">🔒 Encrypted</span>
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-3 pt-2 text-xs text-emerald-100 border-t border-white/15">
            <div class="flex items-center gap-4 font-mono text-[11px]">
              <span><strong>{{ number_format($count) }}</strong> Letters Sealed</span>
              <span>•</span>
              <span><strong>{{ number_format($uniquePeople) }}</strong> Fans Participated</span>
            </div>
            <div class="text-[11px] text-emerald-200">
              Unboxes live on YouTube & Twitch
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 2. THREE KEY VALUE PROPS: Transparent Feature Cards (Giftpose .feat-card style) -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-xl mx-auto mb-10 sm:mb-14">
      <h2 class="font-serif text-2xl sm:text-4xl font-bold text-[#0f172a] mb-3">
        Why Creators & Fans Love FanVault
      </h2>
      <p class="text-sm sm:text-base text-[#64748b]">
        The digital alternative to physical fan mail — built for live celebration broadcasts.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
      <!-- Card 1 -->
      <div class="bg-white hover:bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-3xl p-6 sm:p-8 shadow-xs hover:shadow-md hover:-translate-y-1.5 transition-all duration-200 text-center flex flex-col items-center">
        <div class="w-14 h-14 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-2xl mb-5 shadow-2xs">
          🔐
        </div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2.5">
          Zero Spoilers Until Reveal
        </h3>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Every submission stays cryptographically sealed. Nobody — not even the creator — can peek early until the milestone stream.
        </p>
      </div>

      <!-- Card 2 -->
      <div class="bg-white hover:bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-3xl p-6 sm:p-8 shadow-xs hover:shadow-md hover:-translate-y-1.5 transition-all duration-200 text-center flex flex-col items-center">
        <div class="w-14 h-14 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-2xl mb-5 shadow-2xs">
          🎬
        </div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2.5">
          Unboxed Live on Stream
        </h3>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Broadcast Stream Mode gives you high-contrast cue cards in OBS to reveal letters, react to wild predictions, and celebrate on camera.
        </p>
      </div>

      <!-- Card 3 -->
      <div class="bg-white hover:bg-[#faf9f5] border border-[#e7e5df] hover:border-[#047857]/40 rounded-3xl p-6 sm:p-8 shadow-xs hover:shadow-md hover:-translate-y-1.5 transition-all duration-200 text-center flex flex-col items-center">
        <div class="w-14 h-14 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-2xl mb-5 shadow-2xs">
          📬
        </div>
        <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-2.5">
          Zero Clutter, Direct Payouts
        </h3>
        <p class="text-xs sm:text-sm text-[#64748b] leading-relaxed">
          Replaces physical PO Box hassle. International fans join with zero shipping friction, supporting at your chosen price with Stripe payouts.
        </p>
      </div>
    </div>
  </section>

  <!-- 3. HOW IT WORKS: Numbered Steps (Giftpose .how-sec style) -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-[#f5fbf5] border border-[#d5ecd5] rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-12 lg:p-14">
      <div class="text-center max-w-xl mx-auto mb-10 sm:mb-14">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-[#a7f3d0] text-[#064e3b] text-xs font-mono uppercase tracking-wider mb-2">
          Simple 3-Step Ceremony
        </div>
        <h2 class="font-serif text-2xl sm:text-4xl font-bold text-[#0f172a]">
          How the Time Capsule Works
        </h2>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
        <!-- Steps List (Left 7 Cols) -->
        <div class="lg:col-span-7 space-y-6 sm:space-y-8">
          <!-- Step 1 -->
          <div class="flex items-start gap-4 sm:gap-5">
            <div class="w-10 h-10 rounded-full bg-[#047857] text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              1
            </div>
            <div>
              <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-1">
                Set Your Milestone Target
              </h3>
              <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
                Choose an upcoming milestone: a 100K sub stream, channel birthday, or anniversary. Share your personal vault link with your community.
              </p>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="flex items-start gap-4 sm:gap-5">
            <div class="w-10 h-10 rounded-full bg-[#047857] text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              2
            </div>
            <div>
              <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-1">
                Fans Leave Notes & Predictions
              </h3>
              <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
                Fans write personal messages, memories, and future predictions. Every entry is encrypted and safely stored in the vault.
              </p>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="flex items-start gap-4 sm:gap-5">
            <div class="w-10 h-10 rounded-full bg-[#047857] text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md">
              3
            </div>
            <div>
              <h3 class="font-serif text-lg sm:text-xl font-bold text-[#0f172a] mb-1">
                Unseal Live on Stream
              </h3>
              <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
                When the moment arrives, launch Stream Mode in OBS. Reveal the letters live on camera and react to predictions with your chat.
              </p>
            </div>
          </div>
        </div>

        <!-- Preview Card (Right 5 Cols) -->
        <div class="lg:col-span-5 bg-white border border-[#e7e5df] rounded-2xl sm:rounded-3xl p-6 shadow-sm space-y-4">
          <div class="flex items-center justify-between text-xs font-mono text-[#047857] pb-2 border-b border-[#f1f0eb]">
            <span class="font-bold">LIVE STREAM PRESENTER</span>
            <span>CARD #042</span>
          </div>
          <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-xl p-4 space-y-2">
            <div class="flex items-center justify-between text-[11px] font-mono text-[#64748b]">
              <span class="text-[#047857] font-bold">🔮 PREDICTION CARD</span>
              <span>Lagos, Nigeria</span>
            </div>
            <p class="font-serif italic text-base text-[#0f172a]">
              “I predict by episode 500, we'll have a global live meetup!”
            </p>
          </div>
          <div class="flex items-center justify-between text-xs text-[#64748b] pt-1">
            <span>Controls: [← Prev] [Next →]</span>
            <span class="text-amber-600 font-bold">⭐ Starred</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 4. ACTIVE TIME CAPSULES (Creator Showcase) -->
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
      <a href="{{ route('creators') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-full bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm shadow-xs transition-all">
        Browse All ({{ $creatorsCount }}) →
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($creators as $c)
        @php
          $daysLeft = $c->daysUntilUnlock();
          $progress = $c->unlockProgress();
        @endphp
        <div class="bg-white border border-[#e7e5df] hover:border-[#047857]/40 rounded-3xl p-6 shadow-xs hover:shadow-md hover:-translate-y-1 transition-all duration-200 flex flex-col justify-between">
          <div>
            <div class="flex items-start justify-between gap-3 mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#064e3b] text-white flex items-center justify-center font-bold text-sm shadow-2xs">
                  {{ strtoupper(substr($c->name, 0, 1)) }}
                </div>
                <div>
                  <h3 class="font-serif text-base font-bold text-[#0f172a] leading-tight">
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
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#ecfdf5] text-[#064e3b] text-xs font-mono font-medium rounded-full border border-[#a7f3d0]">
                🔒 {{ $c->postcards_count }} sealed
              </span>
            </div>

            <!-- Milestone Goal -->
            <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-3.5 mb-4">
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
                  <span class="text-[10px] font-mono text-[#064e3b] bg-[#ecfdf5] px-1.5 py-0.5 rounded-full border border-[#a7f3d0]">({{ $daysLeft }}d)</span>
                @endif
              </strong>
            </div>

            <a href="{{ route('with', $c->slug) }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-full bg-[#ecfdf5] hover:bg-[#064e3b] text-[#064e3b] hover:text-white font-medium text-xs sm:text-sm border border-[#a7f3d0] hover:border-[#064e3b] transition-all">
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
      <a href="{{ route('explore') }}" class="inline-flex items-center gap-1.5 px-6 py-2.5 rounded-full bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-all shadow-xs">
        Browse all {{ number_format($count) }} sealed contributions on the Archive Wall →
      </a>
    </div>
  </section>

  <!-- 6. BOTTOM CALL TO ACTION (Giftpose .dl-sec style) -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-gradient-to-br from-[#064e3b] via-[#043d2f] to-[#011a12] text-white rounded-3xl sm:rounded-[2.5rem] p-8 sm:p-14 text-center shadow-xl relative overflow-hidden">
      <!-- Glow -->
      <div class="absolute inset-0 pointer-events-none bg-[radial-gradient(circle_at_50%_0%,rgba(52,211,153,0.15)_0%,transparent_70%)]"></div>

      <div class="relative z-10 max-w-2xl mx-auto space-y-4">
        <h2 class="font-serif text-2xl sm:text-4xl font-bold leading-tight">
          Preserve your community's journey.
        </h2>
        <p class="text-sm sm:text-base text-emerald-100/80 leading-relaxed max-w-lg mx-auto">
          Launch your Creator Time Capsule in 60 seconds. Free to set up, permanent archival, and built for stream reveals.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-3">
          <a href="{{ route('creators.join') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-emerald-400 hover:bg-emerald-300 text-[#064e3b] font-bold text-sm sm:text-base shadow-md hover:-translate-y-0.5 transition-all">
            Create Your Time Capsule (Free)
          </a>
          <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white font-semibold text-sm sm:text-base hover:-translate-y-0.5 transition-all">
            Browse Active Capsules
          </a>
        </div>
      </div>
    </div>
  </section>
</div>
