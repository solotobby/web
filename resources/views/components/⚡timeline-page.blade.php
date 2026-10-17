<?php

use App\Models\Postcard;
use App\Support\Capsule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Milestone Timeline — Journey to 2050 — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Milestone Timeline — Journey to 2050 — FanVault',
            'description' => 'Chronological countdown of upcoming community milestone reveal dates and digital time capsule unlock streams.',
            'canonicalUrl' => route('timeline'),
            'ogUrl' => route('timeline'),
        ]);
    }
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

<div>
  <!-- Full-Bleed Flat Architectural Banner Hero -->
  <section class="relative bg-[#03261d] text-white border-b border-emerald-900/60 overflow-hidden">
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: linear-gradient(to right, #ffffff 1px, transparent 1px), linear-gradient(to bottom, #ffffff 1px, transparent 1px); background-size: 32px 32px;"></div>

    <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-8">
        <div class="max-w-2xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-emerald-950/80 border border-emerald-700/50 text-emerald-300 font-mono text-[11px] font-medium mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
            <span>CHRONOLOGICAL ARCHIVE · 2026 — 2050</span>
          </div>
          <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-white mb-3 leading-[1.12]">
            The journey across decades.
          </h1>
          <p class="text-sm sm:text-base text-emerald-100/80 leading-relaxed">
            Each year is an archival epoch. Each date represents a morning addressed with fan letters and future predictions. Explore the timeline below.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
          <a href="{{ route('seal') }}" class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-[#03261d] font-semibold text-xs sm:text-sm shadow-xs transition-all cursor-pointer">
            Preserve a Morning →
          </a>
          @if($randomDay)
            <a href="{{ route('timeline.day', $randomDay) }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-emerald-900/50 hover:bg-emerald-900 border border-emerald-700/50 text-emerald-100 font-medium text-xs sm:text-sm transition-all">
              Random Date
            </a>
          @endif
          <a href="{{ route('timeline.day', '2050-01-01') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg bg-emerald-900/50 hover:bg-emerald-900 border border-emerald-700/50 text-emerald-100 font-medium text-xs sm:text-sm transition-all font-mono">
            2050 Terminus
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Timeline Body -->
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Timeline Year Ribbon -->
    <div class="mb-8">
      <div class="text-xs font-mono font-medium text-[#64748b] mb-3 uppercase tracking-wider">
        Select an epoch to explore:
      </div>
      <div class="flex items-center gap-1.5 overflow-x-auto pb-3 pt-1 no-scrollbar sm:flex-wrap">
        @foreach($years as $y)
          @php($n = $countInYear($y))
          <a href="{{ route('timeline.year', $y) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg font-mono text-xs whitespace-nowrap transition-all {{ $y === $selectedYear ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'border border-[#e7e5df] bg-white text-[#475569] hover:border-[#047857]/40 hover:text-[#064e3b]' }}">
            <span>{{ $y }}</span>
            @if($n)
              <span class="text-[10px] {{ $y === $selectedYear ? 'text-white/80' : 'text-[#64748b]' }}">({{ $n }})</span>
            @endif
          </a>
        @endforeach
      </div>
    </div>

    <!-- Days in selected year -->
    @if($daysThisYear->isNotEmpty())
      <div class="bg-white border border-[#e7e5df] rounded-xl p-5 sm:p-7 mb-8 shadow-xs">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-4 pb-3 border-b border-[#e7e5df]">
          <h3 class="font-serif font-bold text-base sm:text-lg text-[#0f172a] flex items-center gap-2">
            <span>📅</span> Mornings claimed in {{ $selectedYear }}
          </h3>
          <span class="inline-flex items-center px-2.5 py-1 bg-[#ecfdf5] text-[#047857] border border-[#a7f3d0] rounded-md text-xs font-mono font-medium">
            {{ $daysThisYear->count() }} {{ $daysThisYear->count() === 1 ? 'morning' : 'mornings' }} with letters
          </span>
        </div>

        <div class="flex flex-wrap gap-2">
          @foreach($daysThisYear as $d => $list)
            <a href="{{ route('timeline.day', $d) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $d === $selectedDay ? 'border border-[#047857] bg-[#ecfdf5] text-[#064e3b] font-bold shadow-xs' : 'border border-[#e7e5df] bg-[#fbfaf6] hover:bg-white text-[#0f172a] hover:border-[#a7f3d0]' }}">
              <span>{{ \App\Support\Capsule::formatDay($d) }}</span>
              <span class="text-[11px] font-mono {{ $d === $selectedDay ? 'text-[#064e3b]/80' : 'text-[#64748b]' }}">({{ $list->count() }})</span>
            </a>
          @endforeach
        </div>
      </div>
    @else
      <div class="text-center py-12 px-4 bg-white border border-dashed border-[#e7e5df] rounded-xl mb-8">
        <p class="text-sm sm:text-base text-[#475569]">No capsules have been addressed to {{ $selectedYear }} yet.</p>
        <a href="{{ route('seal') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-xs sm:text-sm shadow-xs transition-all">
          Be the first to claim a day in {{ $selectedYear }} →
        </a>
      </div>
    @endif

    <!-- Postcards for Selected Day -->
    @if($selectedDay && $chorus->isNotEmpty())
      <div class="mt-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 pb-4 border-b border-[#e7e5df]">
          <div>
            <div class="flex items-center gap-2 mb-1.5">
              <span class="inline-flex items-center px-2 py-0.5 bg-[#fefce8] text-[#854d0e] rounded-md text-xs font-mono font-medium border border-[#fde047]">
                Archive Station
              </span>
              <span class="text-xs font-mono text-[#64748b]">{{ \App\Support\Capsule::untilLabel($selectedDay) }}</span>
            </div>
            <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] tracking-tight">
              {{ \App\Support\Capsule::formatDay($selectedDay) }}
            </h2>
            <p class="text-xs sm:text-sm text-[#475569] mt-1">
              {{ $chorus->count() }} {{ $chorus->count() === 1 ? 'letter' : 'letters' }} waiting in the archival vault
            </p>
          </div>
          <a href="{{ route('seal') }}?day={{ $selectedDay }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-xs sm:text-sm shadow-xs transition-all cursor-pointer">
            Address this morning too →
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
</div>


