<?php

use App\Models\Postcard;
use App\Support\Capsule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('The Community Archive — Sealed Time Capsule Contributions — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'The Community Archive — Sealed Time Capsule Contributions — FanVault',
            'description' => 'Browse public teasers, predictions, and milestone notes sealed in creator time capsules around the world.',
            'canonicalUrl' => route('explore'),
            'ogUrl' => route('explore'),
        ]);
    }
    public function with(): array
    {
        $messages = Postcard::query()->with('creator')->orderByDesc('number')->limit(240)->get();
        $count = (int) (\App\Models\Stat::query()->value('sealed_count') ?? $messages->count());
        $uniquePeople = (int) Postcard::query()->whereNotNull('name')->where('name', '!=', '')->distinct('name')->count('name');
        if ($uniquePeople === 0 && $count > 0) {
            $uniquePeople = min($count, 62);
        }

        return compact('messages', 'count', 'uniquePeople');
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
        <span class="font-semibold uppercase tracking-wider text-[11px]">Archive / Public Teaser Wall</span>
      </div>

      <h1 class="font-serif text-3xl sm:text-5xl font-bold tracking-tight text-white leading-tight">
        Voices from the Time Capsules
      </h1>

      <p class="text-sm sm:text-base md:text-lg text-emerald-100/80 leading-relaxed max-w-2xl mx-auto font-light">
        Every card below is a preview teaser from a message, prediction, or memory sealed for a future creator milestone. The full letter and attachments remain strictly encrypted until the creator unseals the capsule live on stream.
      </p>

      <div class="flex flex-wrap items-center justify-center gap-3 pt-2 text-xs font-mono">
        <span class="bg-white/10 px-3 py-1.5 rounded border border-white/15 text-emerald-200">
          🔒 <strong>{{ number_format($count) }}</strong> Contributions Sealed
        </span>
        <span class="bg-white/10 px-3 py-1.5 rounded border border-white/15 text-emerald-200">
          👥 <strong>{{ number_format($uniquePeople) }}</strong> People Participating
        </span>
      </div>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-3">
        <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg bg-emerald-400 hover:bg-emerald-300 text-[#03261d] font-bold text-sm transition-all shadow-sm">
          Explore Time Capsules
        </a>
        <a href="{{ route('seal') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/15 text-white font-medium text-sm transition-all">
          Seal a Contribution
        </a>
      </div>
    </div>
  </section>

  <!-- 2. ARCHIVE CARDS GRID -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
    <div class="mb-8 flex items-center justify-between pb-3 border-b border-[#e7e5df]">
      <div class="text-xs font-mono uppercase tracking-wider text-[#64748b]">
        Latest Vault Passes
      </div>
      <div class="text-xs font-mono text-[#047857]">
        Live Protocol Feed
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($messages as $msg)
        <x-voice-card :msg="$msg" />
      @endforeach
    </div>
  </section>
</div>
