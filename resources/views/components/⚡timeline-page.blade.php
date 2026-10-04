<?php

use App\Models\Postcard;
use App\Support\Capsule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Timeline — Journey to 2050')]
class extends Component
{
    public ?string $year = null;

    public ?string $day = null;

    public function mount(?string $year = null, ?string $day = null): void
    {
        $this->year = $year;
        $this->day = $day ? Capsule::clampAddressDate($day) : null;
    }

    public function with(): array
    {
        $all = Postcard::query()->with('creator')->orderBy('addressed_to')->orderBy('number')->get();
        $groups = $all->groupBy(fn ($m) => $m->addressed_to->toDateString());
        $years = range(2026, 2050);

        $selectedYear = $this->year && preg_match('/^\d{4}$/', $this->year) ? (int) $this->year : 0;
        if ($this->day) {
            $selectedYear = (int) substr($this->day, 0, 4);
        }
        if ($selectedYear < 2026 || $selectedYear > 2050) {
            $selectedYear = $groups->has(Capsule::OPENING) ? 2050 : (int) ($groups->keys()->first() ? substr($groups->keys()->first(), 0, 4) : 2050);
        }

        $daysThisYear = $groups->filter(fn ($_, $d) => str_starts_with($d, (string) $selectedYear));
        $selectedDay = $this->day && str_starts_with($this->day, (string) $selectedYear)
            ? $this->day
            : ($daysThisYear->keys()->first() ?: '');
        $chorus = $selectedDay ? ($groups->get($selectedDay) ?? collect()) : collect();
        $peak = $daysThisYear->map->count()->max() ?: 0;
        $randomDay = $groups->keys()->shuffle()->first();
        $cities = $chorus->map(fn ($m) => trim(explode(',', $m->location)[0]))->unique()->values();
        $firstNumber = $chorus->min('number');
        $daysToEnd = max(0, Capsule::daysUntil(Capsule::OPENING));

        return [
            'years' => $years,
            'selectedYear' => $selectedYear,
            'daysThisYear' => $daysThisYear,
            'selectedDay' => $selectedDay,
            'chorus' => $chorus,
            'peak' => $peak,
            'randomDay' => $randomDay,
            'cities' => $cities,
            'firstNumber' => $firstNumber,
            'daysToEnd' => $daysToEnd,
            'countInYear' => function (int $y) use ($groups) {
                return $groups->filter(fn ($_, $d) => str_starts_with($d, (string) $y))->flatten(1)->count();
            },
        ];
    }
};
?>

<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-12">
  <!-- Header -->
  <div class="text-center max-w-2xl mx-auto mb-8 sm:mb-12">
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0]/80 text-[#064e3b] text-xs font-medium mb-3">
      The Chronological Archive · 2026 — 2050
    </span>
    <h1 class="font-serif text-3xl sm:text-5xl font-normal tracking-tight text-[#0f172a] mb-3">
      The journey across <em class="italic text-[#047857]">decades</em>.
    </h1>
    <p class="text-sm sm:text-base md:text-lg text-[#64748b] leading-relaxed">
      Each year is an epoch. Each day is an archival morning someone addressed a letter to. Walk across the chronology of 2050 below.
    </p>
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-6">
      <a href="{{ route('seal') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all">
        Preserve a Morning ($5)
      </a>
      @if($randomDay)
        <a href="{{ route('timeline.day', $randomDay) }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-xs">
          Random Date
        </a>
      @endif
      <a href="{{ route('timeline.day', '2050-01-01') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-medium text-sm sm:text-base hover:-translate-y-0.5 active:translate-y-0 transition-all shadow-xs">
        Terminus (2050)
      </a>
    </div>
  </div>

  <!-- Timeline Year Ribbon -->
  <div class="mb-8 sm:mb-12">
    <div class="text-xs font-mono font-semibold text-[#64748b] mb-2.5 uppercase tracking-wider">
      Select an epoch to explore:
    </div>
    <div class="flex items-center gap-2 overflow-x-auto pb-3 pt-1 no-scrollbar sm:flex-wrap">
      @foreach($years as $y)
        @php($n = $countInYear($y))
        <a href="{{ route('timeline.year', $y) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full font-medium text-xs sm:text-sm whitespace-nowrap transition-all {{ $y === $selectedYear ? 'bg-[#064e3b] text-white shadow-2xs font-semibold' : 'border border-[#e7e5df] bg-white text-[#475569] hover:border-[#047857]/40 hover:text-[#064e3b]' }}">
          <span>{{ $y }}</span>
          @if($n)
            <span class="text-[11px] {{ $y === $selectedYear ? 'text-white/80 font-mono' : 'text-[#64748b] font-mono' }}">({{ $n }})</span>
          @endif
        </a>
      @endforeach
    </div>
  </div>

  <!-- Days in selected year -->
  @if($daysThisYear->isNotEmpty())
    <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 sm:p-7 mb-8 sm:mb-12 shadow-sm">
      <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
        <h3 class="font-serif font-bold text-base sm:text-lg text-[#0f172a]">
          📅 Mornings claimed in {{ $selectedYear }}
        </h3>
        <span class="inline-flex items-center px-3 py-1 bg-[#ecfdf5] text-[#047857] border border-[#a7f3d0] rounded-full text-xs font-bold">
          {{ $daysThisYear->count() }} {{ $daysThisYear->count() === 1 ? 'morning' : 'mornings' }} with letters
        </span>
      </div>

      <div class="flex flex-wrap gap-2">
        @foreach($daysThisYear as $d => $list)
          <a href="{{ route('timeline.day', $d) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold transition-all {{ $d === $selectedDay ? 'border border-[#047857] bg-[#ecfdf5] text-[#064e3b] font-bold shadow-sm' : 'border border-[#e7e5df] bg-[#f5f4ee] hover:bg-white text-[#0f172a] hover:border-[#a7f3d0]' }}">
            <span>{{ \App\Support\Capsule::formatDay($d) }}</span>
            <span class="text-[11px] {{ $d === $selectedDay ? 'text-[#064e3b]/80' : 'text-[#64748b]' }}">({{ $list->count() }})</span>
          </a>
        @endforeach
      </div>
    </div>
  @else
    <div class="text-center py-10 px-4 bg-white/80 border border-dashed border-[#e7e5df] rounded-3xl mb-8 sm:mb-12">
      <p class="text-sm sm:text-base text-[#334155]">No capsules have been addressed to {{ $selectedYear }} yet!</p>
      <a href="{{ route('seal') }}" class="inline-flex items-center gap-2 mt-4 px-6 py-3 rounded-full bg-gradient-to-r from-[#064e3b] to-[#047857] text-white font-bold text-xs sm:text-sm shadow-sm transition-all">
        Be the first to claim a day in {{ $selectedYear }} →
      </a>
    </div>
  @endif

  <!-- Postcards for Selected Day -->
  @if($selectedDay && $chorus->isNotEmpty())
    <div class="mt-6 sm:mt-10">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <span class="inline-flex items-center px-2.5 py-0.5 bg-[#fefce8] text-[#854d0e] rounded-full text-xs font-bold mb-1 border border-[#fde047]">
            Archive Station
          </span>
          <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a]">
            {{ \App\Support\Capsule::formatDay($selectedDay) }}
          </h2>
          <p class="text-xs sm:text-sm text-[#334155]">
            {{ $chorus->count() }} {{ $chorus->count() === 1 ? 'letter' : 'letters' }} waiting · {{ \App\Support\Capsule::untilLabel($selectedDay) }}
          </p>
        </div>
        <a href="{{ route('seal') }}?day={{ $selectedDay }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] text-white font-bold text-xs sm:text-sm shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:-translate-y-0.5 transition-all">
          Address this morning too ($5) 🏛️
        </a>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @foreach($chorus as $msg)
          <x-voice-card :msg="$msg" :badge="$msg->number === $firstNumber ? 'First on this day' : ''" />
        @endforeach
      </div>
    </div>
  @endif
</section>

