<?php

use App\Models\Payment;
use App\Services\MintPostcardService;
use App\Support\Capsule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

new
#[Layout('layouts.app')]
#[Title('Seal a Letter — FanVault')]
class extends Component
{
    use WithFileUploads;

    public function rendering($view): void
    {
        $creator = session('ref_slug') ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->first() : null;
        $title = $creator 
            ? 'Seal Letter for ' . $creator->name . ' — FanVault'
            : 'Seal an Archival Letter — FanVault';
        $desc = $creator
            ? 'Seal an encrypted fan letter, photo, or milestone prediction for ' . $creator->name . ' to be opened live on stream.'
            : 'Preserve your letter, memory, or prediction in the permanent encrypted digital time capsule.';

        $view->layoutData([
            'title' => $title,
            'description' => $desc,
            'canonicalUrl' => route('seal'),
            'ogTitle' => $title,
            'ogDescription' => $desc,
            'ogImage' => $creator ? route('og.creator', $creator->slug) : route('og.cover'),
        ]);
    }

    public int $step = 1;

    public string $name = '';

    public string $location = '';

    public string $message = '';

    public string $teaser = '';

    public string $email = '';

    public string $addressedTo = Capsule::OPENING;

    public string $mars = '';

    public string $jobs = '';

    public string $hundred = '';

    public $photo = null;

    public ?string $milestoneId = null;

    public int $sealAmount = 5;

    public ?string $customAmount = null;

    public bool $sealing = false;

    public string $error = '';

    public function setAmount(int $amt): void
    {
        $creator = session('ref_slug') ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->first() : null;
        $min = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
        $this->sealAmount = max($min, $amt);
        $this->customAmount = null;
    }

    public function addBooster(int $extra): void
    {
        $this->sealAmount = min(1000, $this->sealAmount + $extra);
        $this->customAmount = null;
    }

    public function updatedCustomAmount(): void
    {
        if (is_numeric($this->customAmount)) {
            $val = (int) $this->customAmount;
            $creator = session('ref_slug') ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->first() : null;
            $min = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
            if ($val >= $min) {
                $this->sealAmount = min(1000, $val);
            }
        }
    }

    public function mount(): void
    {
        if ($ref = request()->query('ref')) {
            session(['ref_slug' => \App\Support\Capsule::slugify((string) $ref)]);
        }

        $creator = session('ref_slug') ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->with('milestones')->first() : null;

        if ($creator) {
            $minPrice = (int) ($creator->minPriceDollars() ?: 5);
            $this->sealAmount = $minPrice;
            if ($reqAmt = request()->query('amount')) {
                $val = (int) $reqAmt;
                if ($val >= $minPrice) {
                    $this->sealAmount = min(1000, $val);
                }
            }

            $requestedMilestone = request()->query('milestone');
            if ($requestedMilestone) {
                $found = $creator->milestones->firstWhere('id', $requestedMilestone);
                if ($found) {
                    $this->milestoneId = $found->id;
                    if ($found->unlock_date) {
                        $this->addressedTo = $found->unlock_date->toDateString();
                    }
                }
            }
            if (! $this->milestoneId) {
                $active = $creator->activeMilestone() ?? $creator->milestones->first();
                if ($active) {
                    $this->milestoneId = $active->id;
                    if (! request()->query('day') && ! session('set_day') && $active->unlock_date) {
                        $this->addressedTo = $active->unlock_date->toDateString();
                    }
                }
            }
        } else {
            if ($reqAmt = request()->query('amount')) {
                $val = (int) $reqAmt;
                if ($val >= 3) {
                    $this->sealAmount = min(1000, $val);
                }
            }
        }

        if (request()->query('day')) {
            $this->addressedTo = Capsule::clampDraftDate((string) request()->query('day'));
        } elseif (session('set_day')) {
            $this->addressedTo = Capsule::clampDraftDate(session('set_day'));
            session()->forget('set_day');
        } elseif ($creator && ! $this->milestoneId && $creator->unlock_date) {
            $this->addressedTo = $creator->unlock_date->toDateString();
        }

        if (! $this->addressedTo) {
            $this->addressedTo = Capsule::OPENING;
        }
    }

    public function selectMilestone(string $id): void
    {
        $creator = session('ref_slug') ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->with('milestones')->first() : null;
        if ($creator) {
            $m = $creator->milestones->firstWhere('id', $id);
            if ($m) {
                $this->milestoneId = $m->id;
                if ($m->unlock_date) {
                    $this->addressedTo = $m->unlock_date->toDateString();
                }
            }
        }
    }

    public function next(): void
    {
        $this->error = '';
        if ($this->step === 1) {
            $this->validate([
                'message' => 'required|string|max:600',
                'teaser' => 'nullable|string|max:72',
            ]);
        }
        if ($this->step === 2) {
            $this->validate([
                'name' => 'required|string|max:120',
                'location' => 'required|string|max:120',
                'email' => 'required|email|max:190',
                'addressedTo' => 'required|date',
            ]);
            $this->addressedTo = Capsule::clampDraftDate($this->addressedTo);
        }
        $this->step = min(3, $this->step + 1);
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function setPrediction(string $key, string $value): void
    {
        if (in_array($key, ['mars', 'jobs', 'hundred'], true)) {
            $this->{$key} = $value;
        }
    }

    public function seal(MintPostcardService $mint): mixed
    {
        $creator = session('ref_slug') 
            ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->with('milestones')->first() 
            : null;
        $minDollars = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;

        $this->validate([
            'name' => 'required|string|max:120',
            'location' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'message' => 'required|string|max:600',
            'teaser' => 'nullable|string|max:72',
            'addressedTo' => 'required|date',
            'photo' => 'nullable|image|max:2048',
            'sealAmount' => "required|integer|min:{$minDollars}|max:1000",
        ]);

        $this->sealing = true;
        $this->error = '';
        $teaser = trim($this->teaser) !== '' ? $this->teaser : Capsule::makeTeaser($this->message);
        $photoPath = null;
        if ($this->photo) {
            $photoPath = $this->photo->store('portraits', 'public');
        }

        $amountCents = $this->sealAmount * 100;
        $claimToken = Str::random(64);
        $draft = [
            'name' => $this->name,
            'location' => $this->location,
            'letter' => $this->message,
            'teaser' => $teaser,
            'email' => $this->email,
            'addressed_to' => Capsule::clampDraftDate($this->addressedTo),
            'photo_path' => $photoPath,
            'predictions' => [
                'mars' => $this->mars,
                'jobs' => $this->jobs,
                'hundred' => $this->hundred,
            ],
            'creator_slug' => session('ref_slug'),
            'milestone_id' => $this->milestoneId,
            'amount_cents' => $amountCents,
            'claim_token_hash' => hash('sha256', $claimToken),
        ];

        $stripeKey = config('services.stripe.secret');
        if (! $stripeKey) {
            $result = $mint->mint($draft);
            $id = $result['postcard']->id;
            $authored = session('authored', []);
            $authored[] = $id;
            session(['authored' => array_values(array_unique($authored)), "claim.{$id}" => $claimToken]);

            return $this->redirect(route('message', $result['postcard']), navigate: true);
        }

        $activeMilestone = $creator && $this->milestoneId 
            ? $creator->milestones->firstWhere('id', $this->milestoneId) 
            : ($creator ? $creator->activeMilestone() : null);

        $productName = $creator 
            ? "FanVault — {$creator->name} Fan Letter"
            : 'FanVault — Sealed Community Letter';

        $productDesc = $creator
            ? 'Encrypted fan letter & keepsake pass for '.($activeMilestone?->title ?? $creator->name).'. Unsealed live on stream.'
            : 'Permanent community time capsule letter & collectible pass.';

        try {
            Stripe::setApiKey($stripeKey);
            $session = StripeSession::create([
                'mode' => 'payment',
                'customer_email' => $this->email,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $amountCents,
                        'product_data' => [
                            'name' => $productName,
                            'description' => $productDesc,
                        ],
                    ],
                ]],
                'success_url' => route('checkout.return').'?session_id={CHECKOUT_SESSION_ID}&claim='.$claimToken,
                'cancel_url' => route('seal', array_filter([
                    'ref' => session('ref_slug'),
                    'milestone' => $this->milestoneId,
                    'amount' => $this->sealAmount,
                ])),
                'metadata' => [
                    'kind' => 'fanvault_seal',
                    'creator_slug' => (string) session('ref_slug', ''),
                    'milestone_id' => (string) ($this->milestoneId ?? ''),
                ],
            ]);

            Payment::query()->create([
                'stripe_session_id' => $session->id,
                'amount_cents' => $amountCents,
                'currency' => 'usd',
                'status' => 'created',
                'draft' => $draft,
                'creator_slug' => session('ref_slug'),
            ]);

            return redirect()->away($session->url);
        } catch (\Throwable $e) {
            $this->sealing = false;
            $this->error = 'Unable to initialize Stripe checkout: '.$e->getMessage();

            return null;
        }
    }

    public function with(): array
    {
        $creator = session('ref_slug') ? \App\Models\Creator::query()->where('slug', session('ref_slug'))->with('milestones')->first() : null;
        $selectedMilestone = $creator && $this->milestoneId 
            ? $creator->milestones->firstWhere('id', $this->milestoneId) 
            : ($creator ? ($creator->activeMilestone() ?? $creator->milestones->first()) : null);
        $minDollars = $creator ? (int) ($creator->minPriceDollars() ?: 3) : 3;
        $creatorCutDollars = number_format(Capsule::calculateCreatorCut($this->sealAmount * 100) / 100, 2);
        $platformFeeDollars = number_format(($this->sealAmount * 100 - Capsule::calculateCreatorCut($this->sealAmount * 100)) / 100, 2);

        $tierName = match(true) {
            $this->sealAmount >= 50 => '👑 VIP Vault Patron',
            $this->sealAmount >= 25 => '✨ Superfan Booster Pass',
            $this->sealAmount >= 10 => '⭐ Channel Supporter Pass',
            default => '🛡️ Standard Keepsake Pass',
        };

        $tierPerk = match(true) {
            $this->sealAmount >= 50 => 'Royal Obsidian foil certificate + Guaranteed stream shoutout & pinned recognition',
            $this->sealAmount >= 25 => 'Gold holographic digital foil + Stream highlight callout during the live reveal',
            $this->sealAmount >= 10 => 'Bronze metallic foil pass + Priority placement in creator stream reader queue',
            default => 'Permanent encrypted archival storage + Official numbered keepsake certificate',
        };

        return [
            'liveTeaser' => trim($this->teaser) !== '' ? $this->teaser : Capsule::makeTeaser($this->message),
            'stripeOn' => (bool) config('services.stripe.secret'),
            'ref' => session('ref_slug'),
            'creator' => $creator,
            'selectedMilestone' => $selectedMilestone,
            'minDollars' => $minDollars,
            'creatorCutDollars' => $creatorCutDollars,
            'platformFeeDollars' => $platformFeeDollars,
            'tierName' => $tierName,
            'tierPerk' => $tierPerk,
        ];
    }
};
?>

<section class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
  <div class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-[0_4px_24px_rgba(0,0,0,0.03)] relative overflow-hidden">
    @if($error)
      <div class="mb-6 p-4 rounded-2xl bg-red-50 text-red-600 border border-red-200 text-xs sm:text-sm font-semibold flex items-center gap-2">
        ⚠️ {{ $error }}
      </div>
    @endif

    <!-- Stepper Header -->
    <div class="grid grid-cols-3 gap-2 pb-6 mb-6 sm:mb-8 border-b border-[#e7e5df]">
      <div class="flex items-center gap-2 {{ $step >= 1 ? 'text-[#064e3b] font-bold' : 'text-[#94a3b8] font-medium' }}">
        <span class="w-6 h-6 rounded-full flex items-center justify-center font-mono text-xs {{ $step >= 1 ? 'bg-[#064e3b] text-white font-bold shadow-2xs' : 'bg-[#f5f4ee] text-[#64748b]' }} shrink-0">1</span>
        <span class="text-xs sm:text-sm truncate">Letter</span>
      </div>
      <div class="flex items-center gap-2 {{ $step >= 2 ? 'text-[#064e3b] font-bold' : 'text-[#94a3b8] font-medium' }}">
        <span class="w-6 h-6 rounded-full flex items-center justify-center font-mono text-xs {{ $step >= 2 ? 'bg-[#064e3b] text-white font-bold shadow-2xs' : 'bg-[#f5f4ee] text-[#64748b]' }} shrink-0">2</span>
        <span class="text-xs sm:text-sm truncate">Details</span>
      </div>
      <div class="flex items-center gap-2 {{ $step === 3 ? 'text-[#064e3b] font-bold' : 'text-[#94a3b8] font-medium' }}">
        <span class="w-6 h-6 rounded-full flex items-center justify-center font-mono text-xs {{ $step === 3 ? 'bg-[#064e3b] text-white font-bold shadow-2xs' : 'bg-[#f5f4ee] text-[#64748b]' }} shrink-0">3</span>
        <span class="text-xs sm:text-sm truncate">Archive Seal</span>
      </div>
    </div>

    @if($step === 1)
      @if($creator)
        <div class="mb-6 p-4 rounded-2xl bg-[#ecfdf5] border border-[#a7f3d0]">
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <span class="w-10 h-10 rounded-xl bg-white border border-[#a7f3d0] flex items-center justify-center text-xs font-mono font-bold text-[#064e3b] shrink-0 shadow-2xs">FV</span>
              <div>
                <span class="block text-xs font-bold text-[#064e3b] uppercase tracking-wider">Writing to Creator Vault</span>
                <strong class="text-sm sm:text-base font-bold text-[#0f172a]">{{ $creator->name }} · {{ $selectedMilestone?->title ?? ($creator->milestone_title ?? 'Community Milestone') }}</strong>
              </div>
            </div>
            <span class="hidden sm:inline-block text-xs font-mono font-bold text-[#047857] bg-white px-2.5 py-1 rounded-full border border-[#a7f3d0]">
              Unlocks {{ $selectedMilestone?->formattedUnlockDate() ?? $creator->formattedUnlockDate() }}
            </span>
          </div>

          @if($creator->milestones && $creator->milestones->count() > 1)
            <div class="mt-3 pt-3 border-t border-[#a7f3d0]/60">
              <span class="block text-[11px] font-bold text-[#064e3b] uppercase tracking-wider mb-2">🎯 Select Topic or Milestone:</span>
              <div class="flex flex-wrap gap-2">
                @foreach($creator->milestones as $m)
                  <button type="button" wire:click="selectMilestone('{{ $m->id }}')" class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $milestoneId === $m->id ? 'bg-[#064e3b] text-white shadow-sm ring-2 ring-[#047857]/40' : 'bg-white hover:bg-[#d1fae5] text-[#064e3b] border border-[#a7f3d0]' }}">
                    <span>{{ $milestoneId === $m->id ? '✓' : '🎯' }}</span>
                    <span>{{ $m->title }}</span>
                    <span class="text-[10px] opacity-75 font-mono">({{ $m->formattedUnlockDate() }})</span>
                  </button>
                @endforeach
              </div>
            </div>
          @endif
        </div>
      @endif

      <div class="mb-6">
        <span class="inline-flex items-center px-3 py-1 bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] rounded-full text-xs font-bold mb-2">
          Step 1 of 3 · Drafting Letter
        </span>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] leading-tight">
          @if($creator)
            Write your letter to <em class="italic text-[#047857]">{{ $creator->name }}</em>
          @else
            Write your letter to <em class="italic text-[#047857]">the future</em>
          @endif
        </h1>
        <p class="font-serif italic text-base sm:text-lg text-[#047857] mt-1 font-normal">
          @if($creator)
            What would you say to {{ $creator->name }} on their milestone stream?
          @else
            What would you tell the world of the future?
          @endif
        </p>
        <p class="text-xs sm:text-sm text-[#475569] mt-1">
          @if($creator)
            Your letter and photos stay sealed inside {{ $creator->name }}’s encrypted vault until the reveal stream! One public teaser line will appear on the wall today.
          @else
            Type what you want preserved in the permanent archive. The full letter stays completely private until your destination date!
          @endif
        </p>
      </div>

      <!-- Step 1 Contribution Level Selector -->
      <div class="mb-6 p-4 sm:p-5 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] shadow-2xs">
        <div class="flex items-center justify-between gap-2 mb-2">
          <div>
            <span class="block text-xs font-bold uppercase tracking-wider text-[#047857] font-mono">
              {{ $creator ? "Choose How Much You'd Like to Give " . $creator->name : "Choose Contribution Amount" }}:
            </span>
            <span class="text-xs text-[#64748b]">
              {{ $creator ? "Base minimum \${$minDollars}.00 · Superfans add optional booster tips" : "Permanent archival preservation" }}
            </span>
          </div>
          <span class="font-mono font-bold text-sm sm:text-base text-[#064e3b] bg-[#ecfdf5] border border-[#a7f3d0] px-3 py-1 rounded-full shrink-0">
            ${{ number_format($sealAmount, 2) }}
          </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
          <button 
            type="button" 
            wire:click="setAmount({{ $minDollars }})" 
            class="p-2 sm:p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 {{ $sealAmount === $minDollars && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]' }}"
          >
            <span class="block text-[10px] uppercase font-mono opacity-80">Standard</span>
            <strong class="text-xs sm:text-sm font-mono font-bold">${{ $minDollars }}</strong>
          </button>

          @if($minDollars < 10)
            <button 
              type="button" 
              wire:click="setAmount(10)" 
              class="p-2 sm:p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 {{ $sealAmount === 10 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df]' }}"
            >
              <span class="block text-[10px] uppercase font-mono opacity-80">Supporter</span>
              <strong class="text-xs sm:text-sm font-mono font-bold">$10</strong>
            </button>
          @endif

          <button 
            type="button" 
            wire:click="setAmount(25)" 
            class="p-2 sm:p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 {{ $sealAmount === 25 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#fefce8] text-[#0f172a] border-[#e7e5df]' }}"
          >
            <span class="block text-[10px] uppercase font-mono opacity-80 text-amber-500 {{ $sealAmount === 25 && ! $customAmount ? 'text-amber-200' : '' }}">Superfan</span>
            <strong class="text-xs sm:text-sm font-mono font-bold">$25</strong>
          </button>

          <button 
            type="button" 
            wire:click="setAmount(50)" 
            class="p-2 sm:p-2.5 rounded-xl border text-center transition-all duration-200 transform active:scale-95 {{ $sealAmount === 50 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-xs ring-2 ring-[#047857]/20 font-bold' : 'bg-white hover:bg-[#faf5ff] text-[#0f172a] border-[#e7e5df]' }}"
          >
            <span class="block text-[10px] uppercase font-mono opacity-80 text-purple-500 {{ $sealAmount === 50 && ! $customAmount ? 'text-purple-200' : '' }}">VIP Patron</span>
            <strong class="text-xs sm:text-sm font-mono font-bold">$50</strong>
          </button>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-2.5 border-t border-[#e7e5df]">
          <span class="text-xs text-[#334155]">
            <strong class="text-[#047857]">{{ $tierName }}:</strong> {{ $tierPerk }}
          </span>
          <div class="flex items-center gap-1.5 shrink-0">
            <span class="text-xs text-[#64748b]">Custom:</span>
            <div class="relative w-28">
              <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">$</span>
              <input 
                type="number" 
                min="{{ $minDollars }}" 
                max="1000" 
                placeholder="{{ $minDollars }}+" 
                wire:model.live.debounce.300ms="customAmount"
                class="w-full pl-6 pr-2 py-1 bg-white border border-[#e7e5df] focus:border-[#047857] rounded-xl text-xs font-mono outline-none shadow-2xs"
              >
            </div>
          </div>
        </div>
      </div>

      <div class="mb-5">
        <label for="message" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Your Secret Letter (Sealed in archive until reveal date)
        </label>
        <textarea id="message" wire:model="message" maxlength="600" rows="5" placeholder="{{ $creator ? 'Dear ' . $creator->name . ', I’ve been watching your content since 2024 and it inspired me to...' : 'Dear future, today I am 28 years old and the world feels full of potential...' }}" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 sm:p-4 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none resize-none leading-relaxed"></textarea>
        <div class="flex justify-between items-center text-xs text-[#64748b] mt-1 px-1">
          <span>🔒 Stays strictly inside the sealed keepsake</span>
          <span><strong>{{ 600 - mb_strlen($message) }}</strong> chars left</span>
        </div>
      </div>

      <div class="mb-6">
        <label for="teaser" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          The Public Teaser Line (Shows on the public Archive Wall)
        </label>
        <input id="teaser" wire:model="teaser" maxlength="72" placeholder="{{ $creator ? 'e.g. Can’t wait to see where the channel is by ' . $creator->formattedUnlockDate() . '!' : 'e.g. If you can still hear the morning birds, we did something right.' }}" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 sm:p-4 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        <div class="flex flex-col sm:flex-row sm:justify-between text-xs text-[#64748b] mt-1 px-1 gap-1">
          <span>👀 One catchy sentence for the public wall (or leave blank to auto-generate)</span>
          <span class="sm:text-right"><strong>{{ 72 - mb_strlen($teaser) }}</strong> chars</span>
        </div>
      </div>

      <div class="pt-4 border-t border-[#e7e5df]">
        <button class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 sm:py-4 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all" wire:click="next" type="button">
          Continue with ${{ $sealAmount }} →
        </button>
      </div>

    @elseif($step === 2)
      <div class="mb-6">
        <span class="inline-flex items-center px-3 py-1 bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] rounded-full text-xs font-bold mb-2">
          Step 2 of 3 · Author & Predictions
        </span>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] leading-tight">
          Who is sending this keepsake? 📬
        </h1>
        <p class="text-xs sm:text-sm text-[#334155] mt-1">
          Give your future self a signature, pick a destination morning, and record your predictions.
        </p>
      </div>

      <div class="mb-5">
        <label for="name" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Your Name or Pen Name
        </label>
        <input id="name" wire:model="name" placeholder="e.g. Sarah Jenkins" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 sm:p-4 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
      </div>

      <div class="mb-5">
        <label for="location" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Where are you writing from? (City, Country)
        </label>
        <input id="location" wire:model="location" placeholder="e.g. Kyoto, Japan or London, UK" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 sm:p-4 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
      </div>

      <div class="mb-5">
        <label for="email" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Delivery Email Address for 2050
        </label>
        <input id="email" type="email" wire:model="email" placeholder="you@example.com" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 sm:p-4 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        <div class="text-xs text-[#64748b] mt-1 px-1">
          💌 We strictly protect your privacy. Used only to email your sealed keepsake on Jan 1, 2050.
        </div>
      </div>

      <div class="mb-5">
        <label for="addressedTo" class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          @if($creator && $selectedMilestone)
            Destination Reveal Date (Tied to {{ $selectedMilestone->title }})
          @else
            Addressed to Which Morning?
          @endif
        </label>
        <input id="addressedTo" type="date" wire:model="addressedTo" min="{{ now()->toDateString() }}" max="2050-01-01" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3.5 sm:p-4 text-base text-[#0f172a] placeholder-[#94a3b8] focus:ring-2 focus:ring-[#047857]/20 transition-all outline-none">
        <div class="text-xs text-[#64748b] mt-1 px-1">
          @if($creator && $selectedMilestone)
            🎯 Auto-locked to {{ $creator->name }}’s milestone date. You can also customize this if needed.
          @else
            🗓️ Pick any date between today and Jan 1, 2050 (birthday, anniversary, milestone).
          @endif
        </div>
      </div>

      <div class="mb-5">
        <label class="block text-xs sm:text-sm font-bold text-[#0f172a] mb-1.5">
          Optional Portrait Photo (Sealed in keepsake)
        </label>
        <input type="file" wire:model="photo" accept="image/*" class="w-full bg-[#f5f4ee] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-2xl p-3 text-sm text-[#0f172a] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#ecfdf5] file:text-[#064e3b] hover:file:bg-[#d1fae5] transition-all outline-none">
        <div class="text-xs text-[#64748b] mt-1 px-1">
          📷 Upload a snapshot of you today. Kept safe in the archive until 2050.
        </div>
      </div>

      <!-- Fun Prediction Chips -->
      <div class="bg-[#f5f4ee] border border-[#e7e5df] rounded-2xl p-4 sm:p-5 mb-6 space-y-4">
        <h4 class="font-serif font-bold text-sm text-[#0f172a] flex items-center gap-2">
          <span>🔮</span> 2050 Predictions (Sealed inside your keepsake)
        </h4>
        
        <div>
          <label class="block font-semibold text-xs text-[#334155] mb-1.5">🚀 Will humans live on Mars by 2050?</label>
          <div class="grid grid-cols-2 gap-2">
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $this->mars === 'yes' ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-sm' : 'border border-[#e7e5df] bg-white text-[#334155] hover:border-[#047857]/40' }}" wire:click="setPrediction('mars', 'yes')">👍 Definitely!</button>
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $this->mars === 'no' ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-sm' : 'border border-[#e7e5df] bg-white text-[#334155] hover:border-[#047857]/40' }}" wire:click="setPrediction('mars', 'no')">👎 No way</button>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-xs text-[#334155] mb-1.5">🤖 Will AI do most human jobs?</label>
          <div class="grid grid-cols-2 gap-2">
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $this->jobs === 'yes' ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-sm' : 'border border-[#e7e5df] bg-white text-[#334155] hover:border-[#047857]/40' }}" wire:click="setPrediction('jobs', 'yes')">🤖 AI runs it</button>
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $this->jobs === 'no' ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-sm' : 'border border-[#e7e5df] bg-white text-[#334155] hover:border-[#047857]/40' }}" wire:click="setPrediction('jobs', 'no')">👨‍💻 Humans win</button>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-xs text-[#334155] mb-1.5">🎂 Will most people live past 100?</label>
          <div class="grid grid-cols-2 gap-2">
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $this->hundred === 'yes' ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-sm' : 'border border-[#e7e5df] bg-white text-[#334155] hover:border-[#047857]/40' }}" wire:click="setPrediction('hundred', 'yes')">🎂 Easily</button>
            <button type="button" class="py-2.5 px-3 rounded-xl font-bold text-xs sm:text-sm transition-all {{ $this->hundred === 'no' ? 'border-2 border-[#047857] bg-[#ecfdf5] text-[#064e3b] shadow-sm' : 'border border-[#e7e5df] bg-white text-[#334155] hover:border-[#047857]/40' }}" wire:click="setPrediction('hundred', 'no')">⏳ Regular age</button>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between gap-3 pt-4 border-t border-[#e7e5df]">
        <button class="px-5 py-3 rounded-full bg-white hover:bg-[#f5f4ee] border border-[#e7e5df] text-[#334155] font-bold text-sm transition-all" wire:click="back" type="button">← Back</button>
        <button class="px-7 py-3.5 rounded-full bg-gradient-to-r from-[#064e3b] via-[#047857] to-[#059669] hover:from-[#022c22] hover:to-[#047857] text-white font-bold text-base shadow-[0_4px_16px_rgba(6,78,59,0.3)] hover:shadow-[0_6px_20px_rgba(6,78,59,0.4)] hover:-translate-y-0.5 active:translate-y-0 transition-all" wire:click="next" type="button">Review Your Keepsake (${{ $sealAmount }}) →</button>
      </div>

    @else
      <div class="mb-6">
        <span class="inline-flex items-center px-3 py-1 bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0] rounded-full text-xs font-bold mb-2">
          Step 3 of 3 · Final Verification
        </span>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#0f172a] leading-tight">
          Ready to seal your letter?
        </h1>
        <p class="text-xs sm:text-sm text-[#64748b] mt-1">
          Review what will be visible to the community today versus what stays locked in {{ $creator ? $creator->name . '’s vault' : 'the archive' }}.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-4 sm:p-5">
          <h4 class="text-xs font-mono font-semibold uppercase tracking-wider text-[#047857] mb-2">
            Visible on Public Wall
          </h4>
          <blockquote class="font-serif italic text-base text-[#0f172a] mb-3 leading-snug">
            “{{ $liveTeaser }}”
          </blockquote>
          <p class="font-semibold text-xs text-[#334155]">From {{ $name }} · {{ $location }}</p>
          <p class="text-xs text-[#047857] font-medium mt-0.5">
            Reveal: {{ $selectedMilestone?->formattedUnlockDate() ?? ($creator ? $creator->formattedUnlockDate() : \App\Support\Capsule::formatDay($addressedTo)) }}
            @if($selectedMilestone)
              · {{ $selectedMilestone->title }}
            @endif
          </p>
        </div>

        <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-4 sm:p-5">
          <h4 class="text-xs font-mono font-semibold uppercase tracking-wider text-[#064e3b] mb-2">
            Sealed in {{ $creator ? $creator->name . '’s Vault' : 'Archive' }}
          </h4>
          <blockquote class="text-xs sm:text-sm text-[#475569] italic mb-3 leading-relaxed line-clamp-3">
            “{{ $message }}”
          </blockquote>
          <p class="text-xs text-[#64748b]">
            {{ $photo ? 'Portrait / media attached. ' : '' }}
            Delivery email: <strong class="text-[#0f172a]">{{ $email }}</strong>
          </p>
        </div>
      </div>

      <!-- Contribution Amount Selection (PWYW with creator min) -->
      <div class="bg-white border border-[#e7e5df] rounded-3xl p-5 sm:p-6 mb-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4 pb-3 border-b border-[#e7e5df]">
          <div>
            <h4 class="font-serif font-bold text-base sm:text-lg text-[#0f172a]">
              {{ $creator ? 'Choose Your Letter Contribution' : 'Archival Sealing Fee' }}
            </h4>
            <p class="text-xs text-[#64748b]">
              {{ $creator ? "Set by {$creator->name} (minimum \${$minDollars}.00) · Direct creator patronage · Permanent archival ledger" : "Permanent archival ledger" }}
            </p>
          </div>
          <span class="inline-flex items-center gap-1 font-mono font-bold text-lg sm:text-xl text-[#064e3b] bg-[#ecfdf5] border border-[#a7f3d0]/80 px-3.5 py-1 rounded-2xl shrink-0 self-start sm:self-auto shadow-2xs">
            ${{ number_format($sealAmount, 2) }}
          </span>
        </div>

        <!-- Dynamic Tier Badge & Live Recognition Preview -->
        <div class="mb-5 p-4 rounded-2xl transition-all duration-300 {{ $sealAmount >= 50 ? 'bg-gradient-to-r from-purple-50 via-fuchsia-50 to-pink-50 border border-purple-200 text-purple-900 shadow-2xs' : ($sealAmount >= 25 ? 'bg-gradient-to-r from-amber-50 via-yellow-50 to-orange-50 border border-amber-200 text-amber-900 shadow-2xs' : ($sealAmount >= 10 ? 'bg-gradient-to-r from-blue-50 via-sky-50 to-indigo-50 border border-blue-200 text-blue-900 shadow-2xs' : 'bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 text-emerald-900 shadow-2xs')) }}">
          <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="font-bold text-xs sm:text-sm flex items-center gap-1.5 tracking-tight">
              <span>{{ $tierName }}</span>
            </span>
            <span class="text-[11px] font-mono font-semibold px-2.5 py-0.5 rounded-full bg-white/90 border border-current/20 shadow-2xs">
              ${{ number_format($sealAmount, 2) }} Contribution
            </span>
          </div>
          <p class="text-xs opacity-90 leading-relaxed font-normal">
            {{ $tierPerk }}
          </p>
        </div>

        @if($creator)
          <!-- Direct Creator Patronage Indicator -->
          <div class="mb-5 bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-3.5 sm:p-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <span class="w-2 h-2 rounded-full bg-[#047857]"></span>
              <span class="text-xs text-[#334155] font-medium">
                Direct patronage for <strong>{{ $creator->name }}</strong>
              </span>
            </div>
            <span class="text-xs font-mono font-bold text-[#047857] bg-[#ecfdf5] border border-[#a7f3d0] px-2.5 py-0.5 rounded-full">
              ${{ number_format($sealAmount, 2) }}
            </span>
          </div>
        @endif

        <!-- Quick Selector Chips with Micro-Animations -->
        <div class="space-y-3">
          <label class="block text-xs font-mono font-semibold text-[#475569] uppercase tracking-wider">
            Quick Select or Add Booster:
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <button 
              type="button" 
              wire:click="setAmount({{ $minDollars }})" 
              class="p-3 rounded-2xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 {{ $sealAmount === $minDollars && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-md ring-2 ring-[#047857]/30' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df] hover:border-[#a7f3d0]' }}"
            >
              <span class="block text-[10px] uppercase tracking-wider opacity-75 font-mono">Standard</span>
              <strong class="text-base sm:text-lg block font-mono font-bold">${{ $minDollars }}</strong>
              <span class="text-[10px] block opacity-80 mt-0.5">Floor</span>
            </button>

            @if($minDollars < 10)
              <button 
                type="button" 
                wire:click="setAmount(10)" 
                class="p-3 rounded-2xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 {{ $sealAmount === 10 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-md ring-2 ring-[#047857]/30' : 'bg-white hover:bg-[#ecfdf5] text-[#0f172a] border-[#e7e5df] hover:border-[#a7f3d0]' }}"
              >
                <span class="block text-[10px] uppercase tracking-wider opacity-75 font-mono">Supporter</span>
                <strong class="text-base sm:text-lg block font-mono font-bold">$10</strong>
                <span class="text-[10px] block opacity-80 mt-0.5">Bronze Foil</span>
              </button>
            @endif

            <button 
              type="button" 
              wire:click="setAmount(25)" 
              class="p-3 rounded-2xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 relative overflow-hidden {{ $sealAmount === 25 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-md ring-2 ring-[#047857]/30' : 'bg-white hover:bg-[#fefce8] text-[#0f172a] border-[#e7e5df] hover:border-[#fde047]' }}"
            >
              <span class="block text-[10px] uppercase tracking-wider opacity-75 font-mono">Superfan</span>
              <strong class="text-base sm:text-lg block font-mono font-bold">$25</strong>
              <span class="text-[10px] block opacity-80 mt-0.5 text-amber-600 font-semibold {{ $sealAmount === 25 && ! $customAmount ? 'text-amber-200' : '' }}">★ Gold Foil</span>
            </button>

            <button 
              type="button" 
              wire:click="setAmount(50)" 
              class="p-3 rounded-2xl border text-center transition-all duration-200 transform active:scale-95 hover:-translate-y-0.5 relative overflow-hidden {{ $sealAmount === 50 && ! $customAmount ? 'bg-[#064e3b] text-white border-[#064e3b] shadow-md ring-2 ring-[#047857]/30' : 'bg-white hover:bg-[#faf5ff] text-[#0f172a] border-[#e7e5df] hover:border-[#d8b4fe]' }}"
            >
              <span class="block text-[10px] uppercase tracking-wider opacity-75 font-mono">Patron</span>
              <strong class="text-base sm:text-lg block font-mono font-bold">$50</strong>
              <span class="text-[10px] block opacity-80 mt-0.5 text-purple-600 font-semibold {{ $sealAmount === 50 && ! $customAmount ? 'text-purple-200' : '' }}">👑 VIP Foil</span>
            </button>
          </div>

          <!-- Quick Booster Addons -->
          <div class="pt-2 flex items-center gap-2 flex-wrap">
            <span class="text-xs font-semibold text-[#64748b]">Quick booster tip:</span>
            <button type="button" wire:click="addBooster(5)" class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-[#f1f5f9] hover:bg-[#ecfdf5] text-[#0f172a] hover:text-[#064e3b] border border-[#e2e8f0] hover:border-[#a7f3d0] transition-all">
              +$5
            </button>
            <button type="button" wire:click="addBooster(10)" class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-[#f1f5f9] hover:bg-[#ecfdf5] text-[#0f172a] hover:text-[#064e3b] border border-[#e2e8f0] hover:border-[#a7f3d0] transition-all">
              +$10
            </button>
            <button type="button" wire:click="addBooster(25)" class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-[#f1f5f9] hover:bg-[#fefce8] text-[#0f172a] hover:text-[#854d0e] border border-[#e2e8f0] hover:border-[#fde047] transition-all">
              +$25 ★
            </button>
          </div>

          <!-- Custom Amount input -->
          <div class="pt-2">
            <label class="block text-xs font-semibold text-[#64748b] mb-1.5 flex items-center justify-between">
              <span>Or enter custom booster amount:</span>
              <span class="text-[11px] text-[#047857] font-mono">Direct creator support</span>
            </label>
            <div class="relative max-w-xs">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold">$</span>
              <input 
                type="number" 
                min="{{ $minDollars }}" 
                max="1000" 
                step="1"
                placeholder="e.g. 75 (min ${{ $minDollars }})" 
                wire:model.live.debounce.300ms="customAmount"
                class="w-full pl-8 pr-4 py-2.5 bg-[#f8fafc] hover:bg-white focus:bg-white border border-[#e7e5df] focus:border-[#047857] rounded-xl text-base text-[#0f172a] font-mono outline-none transition-all shadow-2xs focus:ring-2 focus:ring-[#047857]/20"
              >
            </div>
            @error('sealAmount') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
          </div>
        </div>
      </div>

      <p class="text-xs text-[#64748b] text-center mb-6">
        {{ $stripeOn ? '🔒 Secure 256-bit Stripe checkout. Your unique fan capsule number is minted immediately.' : '⚡ Demo Mode: Seals instantly and generates your collectible certificate!' }}
      </p>

      <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-3 pt-4 border-t border-[#e7e5df]">
        <button class="w-full sm:w-auto px-5 py-3 rounded-full bg-white hover:bg-[#f5f4ee] border border-[#e7e5df] text-[#475569] font-medium text-sm transition-all" wire:click="back" type="button" @disabled($sealing)>← Back</button>
        <button class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 sm:py-4 rounded-full bg-[#064e3b] hover:bg-[#047857] text-white font-medium text-sm sm:text-base shadow-[0_2px_12px_rgba(6,78,59,0.25)] hover:-translate-y-0.5 active:translate-y-0 transition-all" wire:click="seal" type="button" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="seal">{{ $stripeOn ? ($creator ? "Pay \${$sealAmount} & Seal in Vault" : "Pay \${$sealAmount} & Seal Letter") : ($creator ? "Seal in Vault (\${$sealAmount})" : "Seal My Letter (\${$sealAmount})") }}</span>
          <span wire:loading wire:target="seal">Sealing into vault… ⏳</span>
        </button>
      </div>
    @endif
  </div>
</section>

