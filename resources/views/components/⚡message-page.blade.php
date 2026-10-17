<?php

use App\Models\Postcard;
use App\Support\Capsule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Community Fan Capsule Pass')]
class extends Component
{
    public Postcard $postcard;

    public function mount(Postcard $postcard): void
    {
        $this->postcard = $postcard->loadMissing(['envelope', 'creator', 'milestone']);
    }

    public function rendering($view): void
    {
        $creator = $this->postcard->creator;
        $title = $creator 
            ? 'Fan Pass No. ' . Capsule::formatNumber($this->postcard->number) . ' — ' . $creator->name . ' Vault'
            : 'Fan Pass No. ' . Capsule::formatNumber($this->postcard->number) . ' — FanVault';
        $desc = $creator
            ? '“' . ($this->postcard->teaser ?: 'Wish you were here.') . '” — Letter from ' . $this->postcard->name . ' sealed in ' . $creator->name . '’s community vault.'
            : '“' . ($this->postcard->teaser ?: 'Wish you were here.') . '” — From ' . $this->postcard->name . ' (' . $this->postcard->location . ').';

        $view->layoutData([
            'title' => $title,
            'description' => $desc,
            'canonicalUrl' => route('message', $this->postcard),
            'ogTitle' => $title,
            'ogDescription' => $desc,
            'ogImage' => route('og.postcard', $this->postcard),
            'ogUrl' => route('message', $this->postcard),
            'schemaJson' => \App\Support\Seo::toJson(\App\Support\Seo::messageSchema($this->postcard)),
        ]);
    }

    public function with(): array
    {
        $authored = session('authored', []);
        $mine = in_array($this->postcard->id, $authored, true);
        if (! $mine && ($claim = request()->query('claim'))) {
            $expectedHash = $this->postcard->envelope?->claim_token_hash;
            if ($expectedHash && hash_equals($expectedHash, hash('sha256', (string) $claim))) {
                $mine = true;
                $authored[] = $this->postcard->id;
                session([
                    'authored' => array_values(array_unique($authored)),
                    "claim.{$this->postcard->id}" => (string) $claim,
                ]);
            }
        }
        $env = $mine ? $this->postcard->envelope : null;
        $creator = $this->postcard->creator;
        $milestone = $this->postcard->milestone;

        if ($mine) {
            $heading = $creator 
                ? '🔒 It’s sealed in ' . $creator->name . '’s Time Capsule!' 
                : '🔒 Your message is officially sealed!';
        } else {
            $heading = $creator 
                ? 'Sealed for ' . $creator->name . '’s ' . ($milestone?->title ?? 'Time Capsule') . ' 🎯'
                : 'Sealed in the Community Time Capsule 🔒';
        }

        if ($creator) {
            $handle = ltrim($creator->handle ?: $creator->slug, '@');
            $milestoneName = $milestone?->title ?? $creator->milestone_title ?? 'Time Capsule';
            $shareText = 'I just left a sealed message in @' . $handle . '’s ' . $milestoneName . ' 🔒';
        } else {
            $shareText = '“' . ($this->postcard->teaser ?: 'Locked in!') . '” — Sealed in FanVault Time Capsule 🔒';
        }

        return [
            'msg' => $this->postcard,
            'mine' => $mine,
            'env' => $env,
            'creator' => $creator,
            'heading' => $heading,
            'shareText' => $shareText,
        ];
    }
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12" x-data="{ copied: false, copy() { navigator.clipboard.writeText(window.location.href); this.copied = true; setTimeout(() => this.copied = false, 2500); } }">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start mb-10">
    <!-- Official Fan Vault Keepsake Certificate Pass -->
    <article class="airmail-top bg-white border-2 border-dashed border-[#a7f3d0] rounded-xl p-6 sm:p-7 shadow-xs relative overflow-hidden flex flex-col justify-between">
      <div class="flex items-start justify-between gap-2 mb-4 pt-1">
        <div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
            <span>🔒</span> {{ $mine ? 'Your Sealed Contribution' : 'Community Capsule Pass' }}
          </span>
          <h2 class="font-mono text-2xl font-bold text-[#0f172a] mt-2">
            No. {{ \App\Support\Capsule::formatNumber($msg->number) }}
          </h2>
        </div>
        <div class="text-right">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-medium {{ $msg->founding ? 'bg-[#fefce8] text-[#854d0e] border border-[#fde047]' : 'bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]' }}">
            {{ $msg->founding ? '🏛️ Founding Pass' : '🌿 Archival Seal' }}
          </span>
        </div>
      </div>

      <blockquote class="font-serif italic text-lg sm:text-xl text-[#0f172a] leading-relaxed my-4">
        “{{ $mine && $env ? $env->letter : $msg->teaser }}”
      </blockquote>

      @if($creator)
        <!-- Creator Community Vault Target Box -->
        <div class="bg-[#f0fdf4] border border-[#bbf7d0] rounded-lg p-3.5 my-3">
          <div class="flex items-center justify-between text-[11px] uppercase tracking-wider font-mono font-medium text-[#047857] mb-1">
            <span>Destination Capsule</span>
            <span>{{ $creator->platform }}</span>
          </div>
          <div class="text-sm font-bold text-[#0f172a] flex items-center gap-1.5">
            <span>🎯</span> {{ $msg->milestone?->title ?? ($creator->milestone_title ?: 'Community Time Capsule') }}
          </div>
          <div class="text-xs text-[#475569] mt-1">
            Creator: <strong class="text-[#0f172a]">{{ $creator->name }}</strong> ({{ $creator->handle }}) · Unsealing Stream: <strong class="text-[#047857]">{{ $msg->milestone?->formattedUnlockDate() ?? $creator->formattedUnlockDate() }}</strong>
          </div>
        </div>
      @else
        <div class="bg-[#fbfaf6] border border-[#e7e5df] rounded-lg p-3.5 my-3">
          <span class="block text-[11px] uppercase tracking-wider font-mono font-medium text-[#64748b]">Unlock Date</span>
          <strong class="text-sm sm:text-base text-[#047857] font-bold">🗓️ {{ \App\Support\Capsule::formatDay($msg->addressed_to->toDateString()) }}</strong>
        </div>
      @endif

      <div class="flex items-center justify-between pt-4 border-t border-[#e7e5df] mt-4">
        <div>
          <p class="text-xs sm:text-sm font-bold text-[#0f172a]">From {{ $msg->name }}</p>
          <p class="text-xs text-[#64748b] font-mono mt-0.5">📍 {{ $msg->location }} · Sealed {{ $msg->sealed_at->format('j M Y') }}</p>
        </div>
        <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
          <span>🔒</span> Sealed Until Stream
        </div>
      </div>
    </article>

    <!-- Side Tools & Share Bar -->
    <div class="space-y-5">
      <div>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] leading-tight mb-2 tracking-tight">
          {{ $heading }}
        </h1>
        <p class="text-xs sm:text-sm text-[#475569] leading-relaxed">
          @if($mine)
            Your message is now safely preserved in the encrypted vault. You won’t be able to read it again until the creator unseals the capsule live on stream.
          @else
            The single teaser line is etched on the public archive wall. The full letter, photo, and predictions stay encrypted in the vault until reveal day.
          @endif
        </p>
      </div>

      <div class="space-y-2.5 pt-1">
        <button type="button" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm shadow-xs hover:shadow-md transition-all cursor-pointer" @click="copy()">
          <span x-show="!copied">📋 Copy Share Link</span>
          <span x-show="copied" style="display:none;">✓ Link Copied to Clipboard!</span>
        </button>

        <a class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white hover:bg-[#fbfaf6] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-all shadow-xs" target="_blank" rel="noopener noreferrer" href="https://twitter.com/intent/tweet?text={{ urlencode($shareText . ' ' . route('message', $msg)) }}">
          Share on X (Twitter)
        </a>

        <a class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white hover:bg-[#fbfaf6] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-all shadow-xs" target="_blank" rel="noopener noreferrer" href="https://api.whatsapp.com/send?text={{ urlencode($shareText . ' ' . route('message', $msg)) }}">
          Share on WhatsApp
        </a>

        @if($creator)
          <a class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#ecfdf5] hover:bg-[#d1fae5] border border-[#a7f3d0] text-[#064e3b] font-medium text-xs sm:text-sm transition-all" href="{{ route('with', $creator->slug) }}">
            📬 Back to {{ $creator->name }}'s Time Capsule
          </a>
        @endif

        <a class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#fbfaf6] hover:bg-[#f5f4ee] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-all shadow-xs" href="{{ route('seal', $creator ? ['ref' => $creator->slug] : []) }}">
          ✍️ Seal Another Message
        </a>
      </div>

      <!-- Point 10: Anticipation Countdown Card -->
      @if($creator)
        @php
          $targetDate = $msg->milestone?->unlock_date ?: $creator->unlock_date;
          $daysLeft = $targetDate ? max(0, (int) now()->diffInDays($targetDate, false)) : null;
          $unlockDateStr = $msg->milestone?->formattedUnlockDate() ?? $creator->formattedUnlockDate();
        @endphp
        <div class="p-4 rounded-xl bg-[#ecfdf5] border border-[#a7f3d0] space-y-2">
          <div class="flex items-center justify-between text-xs font-mono">
            <span class="text-[#064e3b] font-bold">⏳ Anticipation Countdown</span>
            @if($daysLeft !== null)
              <span class="bg-white px-2 py-0.5 rounded-md text-[#047857] font-medium border border-[#a7f3d0]">{{ $daysLeft }} days until reveal</span>
            @endif
          </div>
          <p class="text-xs text-[#064e3b]/90 leading-relaxed">
            This keepsake will be opened during <strong>{{ $creator->name }}'s</strong> live unsealing ceremony on <strong>{{ $unlockDateStr }}</strong>.
          </p>
          <a href="{{ route('with', $creator->slug) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-[#047857] hover:underline">
            <span>🔔 Follow {{ $creator->name }}'s Time Capsule for opening alerts →</span>
          </a>
        </div>
      @endif

      <!-- Nominate Another Creator Loop -->
      <div class="mt-4 p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df] text-xs space-y-1.5">
        <div class="font-bold text-[#064e3b] flex items-center gap-1.5">
          <span>✨</span> Does another creator deserve a Time Capsule?
        </div>
        <p class="text-[#64748b]">
          Nominate your favorite YouTuber, streamer, or podcaster approaching a milestone.
        </p>
        <a href="{{ route('nominate') }}" class="inline-flex items-center gap-1 text-[#047857] font-semibold hover:underline mt-1">
          Nominate a creator →
        </a>
      </div>
    </div>
  </div>

  <!-- Envelope Area -->
  @if($mine && $env)
    <section class="bg-[#f0fdf4] border border-[#a7f3d0] rounded-xl p-6 sm:p-10 text-center my-8 shadow-xs">
      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-[#047857] text-white">
        Your Private Letter
      </span>
      <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#064e3b] my-2">
        Preserved in the Encrypted Vault
      </h2>
      <p class="font-serif italic text-base sm:text-lg text-[#0f172a] max-w-xl mx-auto my-4 leading-relaxed">
        “{{ $env->letter }}”
      </p>
      @if($env->photo_path)
        <img class="max-w-[280px] mx-auto rounded-lg shadow-sm border border-[#a7f3d0]" src="{{ asset('storage/'.$env->photo_path) }}" alt="Portrait">
      @endif
    </section>
  @else
    <section class="bg-white border-2 border-dashed border-[#d6d3cb] rounded-xl p-6 sm:p-10 text-center my-8 shadow-xs">
      <div class="text-3xl mb-2">🔒 📬 🎬</div>
      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
        Community Vault Encrypted
      </span>
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] my-2">
        Sealed Until the Milestone Broadcast
      </h2>
      <p class="text-xs sm:text-sm text-[#475569] max-w-md mx-auto leading-relaxed">
        The full letter, portrait, and predictions are locked safely inside the encrypted vault. Only the author and the creator during their unsealing stream can reveal them.
      </p>
    </section>
  @endif
</div>

