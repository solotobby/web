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

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-14">
    <!-- Header -->
    <div class="text-center max-w-xl mx-auto mb-8 sm:mb-10">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#ecfdf5] border border-[#a7f3d0] text-[#064e3b] text-xs font-mono font-semibold uppercase tracking-wider mb-3">
            <span>✨</span> Fan-Powered Discovery
        </div>
        <h1 class="font-serif text-3xl sm:text-5xl font-normal text-[#0f172a] tracking-tight">
            Nominate a Creator for a <em class="italic text-[#047857]">Time Capsule</em>
        </h1>
        <p class="text-sm sm:text-base text-[#64748b] mt-3 leading-relaxed">
            Know a creator approaching 100K, 1M, a birthday, or a milestone? Nominate them, and our team will prepare a custom sealed time capsule invite for their community.
        </p>
    </div>

    @if($submitted)
        <div class="bg-white border border-[#a7f3d0] rounded-3xl p-8 sm:p-12 text-center shadow-[0_4px_24px_rgba(4,120,87,0.06)]">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-[#ecfdf5] text-[#047857] flex items-center justify-center text-3xl mb-4 border border-[#a7f3d0]">
                💌
            </div>
            <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#064e3b] mb-2">
                Nomination Received!
            </h2>
            <p class="text-sm sm:text-base text-[#475569] max-w-md mx-auto mb-4 leading-relaxed">
                Thank you for championing <strong>{{ $creatorName }}</strong>! We will reach out with a personal invitation so their community can begin sealing future memories.
            </p>

            <!-- Viral Invite Card -->
            <div class="my-6 p-5 sm:p-6 bg-[#faf9f5] border border-[#e7e5df] rounded-2xl text-left" x-data="{ copied: false, text: 'Hey {{ $creatorName }}, I nominated you for a FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: {{ url('/creators/join') }}' }">
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="text-base">🚀</span>
                    <h3 class="text-sm font-bold text-[#0f172a]">
                        Want to invite {{ $creatorName }} directly?
                    </h3>
                </div>
                <p class="text-xs text-[#64748b] mb-3">
                    Send them this quick shoutout on social media or in a video comment:
                </p>
                <div class="p-3 bg-white border border-[#e7e5df] rounded-xl text-xs font-mono text-[#334155] leading-relaxed mb-3 select-all">
                    Hey {{ $creatorName }}, I nominated you for a FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: {{ url('/creators/join') }}
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a 
                        href="https://twitter.com/intent/tweet?text={{ urlencode('Hey ' . $creatorName . ', I nominated you for a @FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: ' . url('/creators/join')) }}" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-black hover:bg-gray-800 text-white text-xs font-semibold transition-all shadow-xs"
                    >
                        <span>𝕏 Share on X</span>
                    </a>
                    <a 
                        href="https://wa.me/?text={{ urlencode('Hey ' . $creatorName . ', I nominated you for a FanVault Time Capsule! Your community can start sealing memories and predictions for your upcoming milestone: ' . url('/creators/join')) }}" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#25D366] hover:bg-[#1EBE5D] text-white text-xs font-semibold transition-all shadow-xs"
                    >
                        <span>WhatsApp</span>
                    </a>
                    <button 
                        type="button" 
                        @click="navigator.clipboard.writeText(text); copied = true; setTimeout(() => copied = false, 2500);" 
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] text-xs font-semibold transition-all shadow-xs"
                    >
                        <span x-show="!copied">📋 Copy Message</span>
                        <span x-show="copied" style="display:none;" class="text-[#047857] font-bold">✓ Copied!</span>
                    </button>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" wire:click="resetForm" class="px-6 py-3 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm transition-all shadow-xs">
                    Nominate Another Creator
                </button>
                <a href="{{ route('creators') }}" class="px-6 py-3 rounded-full bg-white hover:bg-[#faf9f5] border border-[#e7e5df] text-[#0f172a] font-medium text-sm transition-all">
                    Explore Time Capsules
                </a>
            </div>
        </div>
    @else
        <!-- Nomination Form Card -->
        <form wire:submit="submit" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-10 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
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
                            placeholder="e.g. Marques Brownlee, Ali Abdaal, PewDiePie" 
                            class="w-full px-4 py-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
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
                            class="w-full px-4 py-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all font-mono"
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
                                class="py-2 px-3 rounded-xl border text-xs font-medium text-center transition-all {{ $platform === $p ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-2xs font-semibold' : 'bg-[#faf9f5] hover:bg-white text-[#475569] border-[#e7e5df]' }}"
                            >
                                @switch($p)
                                    @case('YouTube') 📺 @break
                                    @case('Twitch') 👾 @break
                                    @case('TikTok') 🎵 @break
                                    @case('Podcast') 🎙️ @break
                                @endswitch
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
                        placeholder="e.g. Approaching 1 Million Subs, 5th Channel Anniversary, 100th Podcast Episode" 
                        class="w-full px-4 py-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                    >
                    @error('milestoneHint') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Reason / Why -->
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#475569] mb-1.5">
                        Why should their community have a Time Capsule? (Optional)
                    </label>
                    <textarea 
                        wire:model="reason" 
                        rows="3" 
                        placeholder="e.g. They have such a supportive community and love reading fan stories on stream. It would be amazing for their 500k milestone celebration!" 
                        class="w-full px-4 py-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                    ></textarea>
                    @error('reason') <span class="text-rose-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Nominator Info (Optional) -->
            <div class="pt-5 border-t border-[#f1f0eb] space-y-4">
                <span class="block text-xs font-mono uppercase tracking-wider font-semibold text-[#64748b]">
                    Your Details (Optional — we’ll notify you when their capsule goes live)
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <input 
                            type="text" 
                            wire:model="nominatorName" 
                            placeholder="Your Name (e.g. Sarah M.)" 
                            class="w-full px-4 py-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                        >
                    </div>
                    <div>
                        <input 
                            type="email" 
                            wire:model="nominatorEmail" 
                            placeholder="Your Email (for capsule updates)" 
                            class="w-full px-4 py-2.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df] focus:border-[#047857] text-sm text-[#0f172a] outline-none transition-all"
                        >
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button 
                    type="submit" 
                    class="w-full flex items-center justify-center gap-2 px-8 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:shadow-[0_4px_16px_rgba(6,78,59,0.35)] transition-all"
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
