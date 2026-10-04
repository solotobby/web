@props(['msg', 'tone' => '', 'badge' => ''])
@php
  use App\Support\Capsule;
  $line = trim($msg->teaser ?: '') ?: Capsule::makeTeaser($msg->envelope->letter ?? '');
@endphp
<a href="{{ route('message', $msg) }}" class="group relative bg-white border border-[#e7e5df] hover:border-[#047857]/30 rounded-3xl p-5 sm:p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between overflow-hidden text-left">
  <!-- Top Bar: Archive Number & Stamp -->
  <div class="flex items-center justify-between gap-2 pt-1">
    <span class="inline-flex items-center px-2.5 py-0.5 bg-[#ecfdf5] text-[#064e3b] font-mono text-xs font-semibold rounded-full border border-[#a7f3d0]/70">
      No. {{ Capsule::formatNumber($msg->number) }}
    </span>
    @if($msg->creator)
      <span class="inline-flex items-center px-2.5 py-0.5 bg-[#faf9f5] text-[#064e3b] border border-[#e7e5df] text-[11px] font-medium rounded-full truncate max-w-[150px]">
        {{ $msg->creator->name }}
      </span>
    @elseif($badge || $msg->founding)
      <span class="inline-flex items-center px-2.5 py-0.5 bg-[#fefce8] text-[#854d0e] border border-[#fde047] text-[11px] font-medium rounded-full">
        Founding Pass
      </span>
    @else
      <span class="text-[10px] font-mono font-medium text-[#64748b] tracking-wider uppercase">
        Community Vault
      </span>
    @endif
  </div>

  <!-- Teaser Quote in Prestigious Editorial Serif -->
  <blockquote class="my-4 text-base sm:text-lg font-serif italic text-[#0f172a] leading-relaxed group-hover:text-[#047857] transition-colors">
    “{{ $line }}”
  </blockquote>

  <!-- Archive Meta Info -->
  <div class="mt-auto space-y-1.5 pt-3 border-t border-[#f1f0eb]">
    @if($msg->creator && $msg->creator->milestone_title)
      <div class="flex items-center gap-1.5 text-xs font-medium text-[#047857] truncate">
        Vault: {{ $msg->creator->milestone_title }}
      </div>
    @else
      <div class="flex items-center gap-1.5 text-xs font-medium text-[#047857]">
        Unlock: {{ Capsule::formatDay($msg->addressed_to->toDateString()) }}
      </div>
    @endif
    <div class="text-xs text-[#475569] font-medium truncate">
      {{ $msg->name }} <span class="text-[#94a3b8]">·</span> <span class="text-[#64748b]">{{ $msg->location }}</span>
    </div>
    <div class="flex items-center justify-between gap-1 text-[11px] text-[#64748b] font-medium pt-1">
      <span>Sealed in archive</span>
      <span class="text-[#047857] font-semibold group-hover:translate-x-0.5 transition-transform">Inspect pass →</span>
    </div>
  </div>
</a>

