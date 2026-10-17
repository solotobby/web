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
        $this->validate([
            'email' => 'required|email|max:190',
        ]);

        $result = $auth->sendLoginLink($this->email);

        if (! $result['sent']) {
            $this->error = 'Unable to send link. Please verify your email and try again.';

            return;
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
  <div class="bg-white border border-[#e7e5df] rounded-xl p-6 sm:p-9 shadow-xs">
    @if($mode === 'form')
      <div class="mb-4">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-mono font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
          <span class="w-1.5 h-1.5 rounded-full bg-[#047857]"></span>
          <span>Studio Access · Magic Link</span>
        </span>
      </div>

      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-2 tracking-tight">
        Sign in or register with email.
      </h1>
      <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
        Enter your channel email address. We’ll dispatch a secure, passwordless magic link to access your creator studio or set up your capsule.
      </p>

      @if($error)
        <div class="mb-5 p-3.5 rounded-lg bg-red-50 text-red-600 text-xs font-medium border border-red-200">
          ⚠️ {{ $error }}
        </div>
      @endif

      <div class="mb-6">
        <label for="access-email" class="block text-xs font-mono font-medium text-[#475569] uppercase tracking-wider mb-2">Email address</label>
        <input id="access-email" type="email" wire:model="email" autocomplete="email" placeholder="you@example.com" wire:keydown.enter="requestLink" class="w-full bg-[#fbfaf6] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-lg p-3 text-sm text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        @error('email') <div class="text-xs text-red-600 mt-1.5 font-medium">{{ $message }}</div> @enderror
      </div>

      <div class="pt-1">
        <button class="w-full inline-flex items-center justify-center px-6 py-3 rounded-lg bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm shadow-xs hover:shadow-md transition-all cursor-pointer" type="button" wire:click="requestLink" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="requestLink">Send Secure Magic Link →</span>
          <span wire:loading wire:target="requestLink">Sending Link…</span>
        </button>
      </div>
      <p class="text-xs text-[#64748b] mt-5 text-center">Prefer filling details first? <a href="{{ route('creators.join') }}" class="text-[#047857] font-semibold hover:underline">Complete setup wizard</a></p>
    @else
      <div class="text-center py-2">
        <div class="w-12 h-12 mx-auto mb-4 rounded-xl bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-xl text-[#064e3b]">
          ✉️
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-mono font-medium bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] mb-3">
          Check your inbox
        </span>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] mb-2 tracking-tight">
          Magic link dispatched!
        </h1>
        <p class="text-xs sm:text-sm text-[#475569] mb-6 leading-relaxed">
          @if($isNewCreator)
            A setup link has been sent to <strong class="text-[#064e3b] font-mono">{{ $email }}</strong>. Click it to set your channel name, milestone, and launch your community vault.
          @else
            A direct studio link has been dispatched to <strong class="text-[#064e3b] font-mono">{{ $email }}</strong>. It expires in 30 minutes and can only be used once.
          @endif
        </p>

        <div class="flex flex-col gap-2.5">
          <button class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-white hover:bg-[#fbfaf6] border border-[#e7e5df] text-[#0f172a] font-medium text-xs sm:text-sm transition-all shadow-xs cursor-pointer" type="button" wire:click="resetForm">
            Use a different email
          </button>
          <a class="w-full inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-[#fbfaf6] hover:bg-[#f5f4ee] border border-[#e7e5df] text-[#047857] font-medium text-xs sm:text-sm transition-all" href="{{ route('creators') }}">
            Back to Time Capsules
          </a>
        </div>
      </div>
    @endif
  </div>
</section>

