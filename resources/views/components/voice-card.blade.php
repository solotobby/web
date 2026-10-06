@props(['msg', 'tone' => '', 'badge' => ''])
@php
  use App\Support\Capsule;
  $line = trim($msg->teaser ?: '') ?: Capsule::makeTeaser($msg->envelope->letter ?? '');
  $type = $msg->envelope?->predictions['type'] ?? 'message';
@endphp
<div class="group relative bg-white border border-[#e7e5df] hover:border-[#047857]/40 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between overflow-hidden text-left">
  <!-- Top Bar: Archive Number, Type & Sealed Status -->
  <div class="flex items-center justify-between gap-2 pt-1">
    <div class="flex items-center gap-1.5 flex-wrap">
      <span class="inline-flex items-center px-2.5 py-0.5 bg-[#ecfdf5] text-[#064e3b] font-mono text-xs font-semibold rounded-full border border-[#a7f3d0]/70">
        No. {{ Capsule::formatNumber($msg->number) }}
      </span>
      @if($msg->creator)
        <span class="inline-flex items-center px-2 py-0.5 bg-[#faf9f5] text-[#064e3b] border border-[#e7e5df] text-[11px] font-medium rounded-full truncate max-w-[130px]">
          {{ $msg->creator->name }}
        </span>
      @endif
      @switch($type)
        @case('prediction')
          <span class="inline-flex items-center px-2 py-0.5 bg-purple-50 text-purple-700 font-mono text-[10px] font-semibold rounded-full border border-purple-200">
            🔮 Prediction
          </span>
          @break
        @case('memory')
          <span class="inline-flex items-center px-2 py-0.5 bg-rose-50 text-rose-700 font-mono text-[10px] font-semibold rounded-full border border-rose-200">
            ❤️ Memory
          </span>
          @break
      @endswitch
    </div>

    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] text-[10px] font-mono font-semibold rounded-full" title="You can see the teaser. You can't see the message.">
      <span>🔒</span> Public Teaser
    </span>
  </div>

  <!-- Teaser Quote in Prestigious Editorial Serif -->
  <blockquote class="my-3 text-base sm:text-lg font-serif italic text-[#0f172a] leading-relaxed group-hover:text-[#047857] transition-colors">
    <a href="{{ route('message', $msg) }}">“{{ $line }}”</a>
  </blockquote>

  <!-- Encryption Assurance Note -->
  <div class="flex items-center gap-1.5 text-[10px] font-mono text-[#64748b] mb-3">
    <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
    <span>Teaser preview · Full message encrypted until reveal</span>
  </div>

  <!-- Archive Meta Info -->
  <div class="mt-auto space-y-1.5 pt-3 border-t border-[#f1f0eb]">
    @if($msg->creator && $msg->creator->milestone_title)
      <div class="flex items-center justify-between gap-1 text-xs font-medium text-[#047857]">
        <span class="truncate">Capsule: {{ $msg->creator->milestone_title }}</span>
        <span class="text-[10px] font-mono text-[#64748b] shrink-0">{{ $msg->creator->formattedUnlockDate() }}</span>
      </div>
    @else
      <div class="flex items-center gap-1.5 text-xs font-medium text-[#047857]">
        Unlock: {{ Capsule::formatDay($msg->addressed_to->toDateString()) }}
      </div>
    @endif
    <div class="text-xs text-[#475569] font-medium truncate">
      {{ $msg->name }} <span class="text-[#94a3b8]">·</span> <span class="text-[#64748b]">{{ $msg->location }}</span>
    </div>
    <div class="flex items-center justify-between gap-1 text-[11px] font-medium pt-1 border-t border-dashed border-[#f1f0eb]">
      <a href="{{ route('message', $msg) }}" class="text-[#64748b] hover:text-[#047857] transition-colors">
        Inspect pass →
      </a>
      @if($msg->creator)
        <a href="{{ route('with', $msg->creator->slug) }}" class="text-[#047857] font-semibold hover:underline">
          Leave yours →
        </a>
      @endif
    </div>
  </div>
</div>
