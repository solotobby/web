<?php

use App\Services\CreatorAuthService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Creator Studio Access — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Creator Studio Access — FanVault',
            'robots' => 'noindex, nofollow',
        ]);
    }
    public string $email = '';

    public string $mode = 'form'; // form | sent

    public bool $isNewCreator = false;

    public string $error = '';

    public string $devLoginUrl = '';

    public function mount(): void
    {
        if (session('creator_id')) {
            $this->redirect(route('creators.studio'), navigate: true);
        }

        if (session('creator_login_error')) {
            $this->error = (string) session('creator_login_error');
            session()->forget('creator_login_error');
        }
    }

    public function requestLink(CreatorAuthService $auth): void
    {
        $this->error = '';
        $this->devLoginUrl = '';
        $this->validate([
            'email' => 'required|email|max:190',
        ]);

        $result = $auth->sendLoginLink($this->email);

        if (! $result['sent']) {
            $this->error = 'Unable to send link. Please verify your email and try again.';

            return;
        }

        if (! empty($result['login_url'])) {
            $this->devLoginUrl = $result['login_url'];
        }

        $this->isNewCreator = ! empty($result['is_new']);
        $this->mode = 'sent';
    }

    public function resetForm(): void
    {
        $this->mode = 'form';
        $this->email = '';
        $this->error = '';
        $this->isNewCreator = false;
        $this->devLoginUrl = '';
        $this->resetValidation();
    }
};
?>

<section class="max-w-md mx-auto px-4 py-8 sm:py-16">
  <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-[0_8px_30px_rgb(0,0,0,0.04)]">
    @if($mode === 'form')
      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] mb-3">
        📬 Creator Studio · Passwordless Access
      </span>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-1">
        Sign in or register with email.
      </h1>
      <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
        Enter your email address. We’ll send you a passwordless magic link to log in or set up your creator vault. No password required!
      </p>

      @if($error)
        <div class="mb-5 p-3.5 rounded-2xl bg-red-50 text-red-600 text-xs font-bold border border-red-200">
          ⚠️ {{ $error }}
        </div>
      @endif

      <div class="mb-6">
        <label for="access-email" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">Email address</label>
        <input id="access-email" type="email" wire:model="email" autocomplete="email" placeholder="you@example.com" wire:keydown.enter="requestLink" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        @error('email') <div class="text-xs text-red-600 mt-1 font-semibold">{{ $message }}</div> @enderror
      </div>

      <div class="pt-2">
        <button class="w-full inline-flex items-center justify-center px-8 py-3.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-sm sm:text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 transition-all" type="button" wire:click="requestLink" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="requestLink">Email me a magic link ✉️</span>
          <span wire:loading wire:target="requestLink">Sending…</span>
        </button>
      </div>
      <p class="text-xs text-[#64748b] mt-4 text-center">Prefer filling details first? <a href="{{ route('creators.join') }}" class="text-[#047857] font-semibold hover:underline">Complete setup wizard</a></p>
    @else
      <div class="text-center">
        <div class="text-4xl mb-3">📬</div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] mb-2">
          Check your inbox
        </span>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-2">
          Magic link dispatched!
        </h1>
        <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
          @if($isNewCreator)
            A magic setup link has been sent to <strong class="text-[#047857]">{{ $email }}</strong>. Click it to set your channel name, milestone, and launch your community vault!
          @else
            A direct studio link has been dispatched to <strong class="text-[#047857]">{{ $email }}</strong>. It expires in 30 minutes and can only be used once.
          @endif
        </p>

        @if($devLoginUrl)
          <div class="bg-[#f5f4ee] border border-[#e7e5df] rounded-2xl p-4 mb-5 text-left text-xs">
            <span class="block text-[11px] font-bold uppercase tracking-wider text-[#64748b] mb-1">Local dev mail (Auto-Generated)</span>
            <strong class="font-mono text-[#047857] block break-all mb-2">{{ $devLoginUrl }}</strong>
            <a href="{{ $devLoginUrl }}" class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-[#064e3b] to-[#047857] text-white font-bold text-xs shadow-xs transition-all">
              {{ $isNewCreator ? 'Complete Vault Setup →' : 'Open Creator Studio →' }}
            </a>
          </div>
        @endif

        <div class="flex flex-col gap-2.5">
          <button class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#0f172a] font-bold text-xs sm:text-sm transition-all shadow-xs" type="button" wire:click="resetForm">
            Use a different email
          </button>
          <a class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-white hover:bg-[#f7f6f0] border border-[#e7e5df] text-[#047857] font-bold text-xs sm:text-sm transition-all shadow-xs" href="{{ route('creators') }}">
            Back to Creator Directory
          </a>
        </div>
      </div>
    @endif
  </div>
</section>
