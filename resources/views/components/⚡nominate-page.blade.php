<?php

use App\Models\CreatorNomination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Nominate a Creator for a FanVault Time Capsule — FanVault')]
class extends Component
{
    public string $creatorName = '';
    public string $handleOrUrl = '';
    public string $platform = 'YouTube';
    public string $milestoneHint = '';
    public string $reason = '';
    public string $nominatorName = '';
    public string $nominatorEmail = '';
    public bool $submitted = false;

    public function mount(): void
    {
        if (request()->query('creator')) {
            $this->creatorName = (string) request()->query('creator');
        }
        if (request()->query('handle')) {
            $this->handleOrUrl = (string) request()->query('handle');
        }
    }

    public function submit(): void
    {
        $this->validate([
            'creatorName' => 'required|string|max:120',
            'handleOrUrl' => 'required|string|max:190',
            'platform' => 'required|string|max:50',
            'milestoneHint' => 'nullable|string|max:120',
            'reason' => 'nullable|string|max:600',
            'nominatorName' => 'nullable|string|max:100',
            'nominatorEmail' => 'nullable|email|max:190',
        ]);

        CreatorNomination::create([
            'creator_name' => trim($this->creatorName),
            'handle_or_url' => trim($this->handleOrUrl),
            'platform' => trim($this->platform),
            'milestone_hint' => trim($this->milestoneHint),
            'reason' => trim($this->reason),
            'nominator_name' => trim($this->nominatorName),
            'nominator_email' => strtolower(trim($this->nominatorEmail)),
            'status' => 'pending',
        ]);

        $this->submitted = true;
    }

    public function resetForm(): void
    {
        $this->reset(['creatorName', 'handleOrUrl', 'milestoneHint', 'reason', 'nominatorName', 'nominatorEmail']);
        $this->platform = 'YouTube';
        $this->submitted = false;
    }
};
?>

<div>
    <!-- Flat Architectural Header Banner -->
    <section class="relative w-full bg-[#03261d] text-white border-b border-emerald-900/60 overflow-hidden py-14 sm:py-20 px-4 sm:px-6 lg:px-8">
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>
        
        <div class="max-w-3xl mx-auto text-center space-y-4 relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded text-xs font-mono text-emerald-300">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span class="font-semibold uppercase tracking-wider text-[11px]">Community Discovery</span>
            </div>

            <h1 class="font-serif text-3xl sm:text-5xl font-bold tracking-tight text-white leading-tight">
                Nominate a Creator for a Time Capsule
            </h1>

            <p class="text-sm sm:text-base text-emerald-100/80 leading-relaxed max-w-xl mx-auto font-light">
                Know a creator approaching a subscriber milestone, anniversary, or celebration? Nominate them, and we will prepare a sealed time capsule invitation for their community.
            </p>
        </div>
    </section>

    <!-- Content / Form Section -->
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        @if($submitted)
            <div class="bg-white border border-[#a7f3d0] rounded-xl p-8 sm:p-12 text-center shadow-sm">
                <div class="w-14 h-14 mx-auto rounded-lg bg-[#ecfdf5] text-[#047857] flex items-center justify-center text-2xl mb-4 border border-[#a7f3d0]">
                    💌
                </div>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#064e3b] mb-2">
                    Nomination Received!
                </h2>
                <p class="text-sm sm:text-base text-[#475569] max-w-md mx-auto mb-4 leading-relaxed">
                    Thank you for championing <strong>{{ $creatorName }}</strong>! We will reach out with a personal invitation so their community can begin sealing future memories.
                </p>

                <!-- Share Card -->
                <div class="my-6 p-5 sm:p-6 bg-[#faf9f5] border border-[#e7e5df] rounded-lg text-left" x-data="{ copied: false, text: 'Hey {{ $creatorName }}, I nominated you for a FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: {{ url('/creators/join') }}' }">
                    <h3 class="text-sm font-bold text-[#0f172a] mb-1">
                        Want to invite {{ $creatorName }} directly?
                    </h3>
                    <p class="text-xs text-[#64748b] mb-3">
                        Send them this quick shoutout on social media or in a video comment:
                    </p>
                    <div class="p-3 bg-white border border-[#e7e5df] rounded text-xs font-mono text-[#334155] leading-relaxed mb-3 select-all">
                        Hey {{ $creatorName }}, I nominated you for a FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: {{ url('/creators/join') }}
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a 
                            href="https://twitter.com/intent/tweet?text={{ urlencode('Hey ' . $creatorName . ', I nominated you for a @FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: ' . url('/creators/join')) }}" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-black hover:bg-gray-800 text-white text-xs font-semibold transition-all"
                        >
                            <span>Share on X</span>
                        </a>
                        <button 
                            type="button" 
                            @click="navigator.clipboard.writeText(text); copied = true; setTimeout(() => copied = false, 2500);" 
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] text-xs font-semibold transition-all"
                        >
                            <span x-show="!copied">Copy Message</span>
                            <span x-show="copied" style="display:none;" class="text-[#047857] font-bold">✓ Copied!</span>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <button type="button" wire:click="resetForm" class="px-6 py-2.5 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm transition-all">
                        Nominate Another Creator
                    </button>
                    <a href="{{ route('creators') }}" class="px-6 py-2.5 rounded-lg bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-sm transition-all">
                        Explore Time Capsules
                    </a>
                </div>
            </div>
        @else
            <!-- Nomination Form Card -->
            <form wire:submit="submit" class="bg-white border border-[#e7e5df] rounded-xl p-6 sm:p-10 shadow-xs space-y-6">
                <!-- Creator Info -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#475569] mb-1.5">
                                Creator or Channel Name <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                wire:model="creatorName" 
                                placeholder="e.g. Marques Brownlee, PewDiePie" 
                                class="w-full px-4 py-2.5 rounded-lg bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                                required
                            >
                            @error('creatorName') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#475569] mb-1.5">
                                Channel Handle or Link <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                wire:model="handleOrUrl" 
                                placeholder="e.g. @mkbhd or youtube.com/@mkbhd" 
                                class="w-full px-4 py-2.5 rounded-lg bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all font-mono"
                                required
                            >
                            @error('handleOrUrl') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Platform Buttons -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#475569] mb-1.5">
                            Primary Platform
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach(['YouTube', 'Twitch', 'TikTok', 'Podcast'] as $p)
                                <button 
                                    type="button" 
                                    wire:click="$set('platform', '{{ $p }}')"
                                    class="py-2 px-3 rounded-lg border text-xs font-medium text-center transition-all {{ $platform === $p ? 'bg-[#064e3b] text-white border-[#064e3b] font-semibold' : 'bg-[#faf9f5] hover:bg-white text-[#475569] border-[#e7e5df]' }}"
                                >
                                    {{ $p }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Milestone Hint -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#475569] mb-1.5">
                            Upcoming Milestone or Event (Optional)
                        </label>
                        <input 
                            type="text" 
                            wire:model="milestoneHint" 
                            placeholder="e.g. Approaching 1 Million Subs, 5th Channel Anniversary" 
                            class="w-full px-4 py-2.5 rounded-lg bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                        >
                        @error('milestoneHint') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Reason -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#475569] mb-1.5">
                            Why should their community have a Time Capsule? (Optional)
                        </label>
                        <textarea 
                            wire:model="reason" 
                            rows="3" 
                            placeholder="e.g. They have a supportive community and love reading fan stories on stream." 
                            class="w-full px-4 py-2.5 rounded-lg bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                        ></textarea>
                        @error('reason') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Nominator Info (Optional) -->
                <div class="pt-5 border-t border-[#f1f0eb] space-y-4">
                    <span class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#64748b]">
                        Your Details (Optional)
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <input 
                                type="text" 
                                wire:model="nominatorName" 
                                placeholder="Your Name" 
                                class="w-full px-4 py-2.5 rounded-lg bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                            >
                        </div>
                        <div>
                            <input 
                                type="email" 
                                wire:model="nominatorEmail" 
                                placeholder="Your Email" 
                                class="w-full px-4 py-2.5 rounded-lg bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                            >
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full flex items-center justify-center gap-2 px-8 py-3.5 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white font-semibold text-sm transition-all shadow-sm"
                    >
                        <span>Nominate Creator for Time Capsule</span>
                        <span>→</span>
                    </button>
                    <p class="text-center text-[11px] text-[#64748b] mt-3 font-mono">
                        Free community initiative · Zero obligation for the nominated creator
                    </p>
                </div>
            </form>
        @endif
    </div>
</div>
