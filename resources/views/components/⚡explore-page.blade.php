<?php

use App\Models\Postcard;
use App\Support\Capsule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('The Fan Wall — Public Community Letters & Predictions — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'The Fan Wall — Public Community Letters & Predictions — FanVault',
            'description' => 'Browse public teasers and milestone predictions sealed by fans across creator community vaults around the world.',
            'canonicalUrl' => route('explore'),
            'ogUrl' => route('explore'),
        ]);
    }
    public function with(): array
    {
        $messages = Postcard::query()->with('creator')->orderByDesc('number')->limit(240)->get();
        $count = (int) (\App\Models\Stat::query()->value('sealed_count') ?? $messages->count());

        return compact('messages', 'count');
    }
};
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
  <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-12">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium mb-3">
      Public Community Fan Wall · {{ number_format($count) }} Letters Sealed
    </span>
    <h1 class="font-serif text-3xl sm:text-5xl font-normal tracking-tight text-[#0f172a] mb-3">
      Teasers from the <em class="italic text-[#047857]">community vaults</em>.
    </h1>
    <p class="text-sm sm:text-base md:text-lg text-[#64748b] leading-relaxed">
      Every card below has a private letter sealed in an active milestone vault. Read their public teaser lines, or seal your own letter for an upcoming stream.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-6">
      <a href="{{ route('creators') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
        Explore Creator Vaults
      </a>
      <a href="{{ route('seal') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 transition-all shadow-xs">
        Seal a Letter
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    @foreach($messages as $msg)
      <x-voice-card :msg="$msg" />
    @endforeach
  </div>
</section>
