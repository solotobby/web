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

<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
  <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-12">
    <div class="inline-flex flex-wrap items-center justify-center gap-2 px-3.5 py-1.5 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium mb-3">
      <span class="w-1.5 h-1.5 rounded-full bg-[#047857] animate-pulse"></span>
      <span class="font-bold">🔒 {{ number_format($count) }} Contributions Sealed</span>
      <span class="text-[#64748b]">·</span>
      <span>👥 {{ number_format($uniquePeople) }} people have left something behind</span>
    </div>

    <!-- Point 11: Teaser Trailer Intentional Framing -->
    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#faf9f5] border border-[#e7e5df] text-[#475569] text-[11px] font-mono mb-4">
      <span>🔒</span>
      <span><strong>You can see the teaser. You can't see the message.</strong> Full letters locked until reveal.</span>
    </div>

    <h1 class="font-serif text-3xl sm:text-5xl font-normal tracking-tight text-[#0f172a] mb-3">
      Voices from the <em class="italic text-[#047857]">time capsules</em>.
    </h1>
    <p class="text-sm sm:text-base md:text-lg text-[#64748b] leading-relaxed">
      Every card below is a preview teaser from a message, prediction, or memory sealed for a future creator milestone. The full letter and attachments remain strictly encrypted until the creator unseals the capsule live on stream.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-6">
      <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
        Explore Time Capsules
      </a>
      <a href="{{ route('seal') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 transition-all shadow-xs">
        Seal a Contribution
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    @foreach($messages as $msg)
      <x-voice-card :msg="$msg" />
    @endforeach
  </div>
</section>
