<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Frequently Asked Questions — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $faqs = $this->faqArray();

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                \App\Support\Seo::faqSchema($faqs),
            ],
        ];

        $view->layoutData([
            'title' => 'Frequently Asked Questions — FanVault',
            'description' => 'Find answers to common questions about FanVault Creator Time Capsules, stream reveals, privacy encryption, and creator payouts.',
            'canonicalUrl' => route('faq'),
            'ogUrl' => route('faq'),
            'schemaJson' => \App\Support\Seo::toJson($schema),
        ]);
    }

    public function faqArray(): array
    {
        return [
            'What is FanVault?' => 'FanVault is the digital time capsule for creators and their communities. Fans leave heartfelt letters, wild milestone predictions, and keepsake photos — sealed today and revealed together live on stream.',
            'How does sealing a letter work?' => 'You write your message, pick an optional photo or prediction, and set a 1-line public teaser. Only your teaser appears on the public community wall today. Your complete letter, portrait, and predictions stay strictly encrypted in the vault until reveal day.',
            'When are the sealed messages unlocked?' => 'Capsules stay sealed and encrypted until the creator reaches their milestone target date or subscriber goal. During their live celebration broadcast stream, the creator unlocks the vault and reads community letters live on camera with OBS broadcast graphics.',
            'What can fans leave inside a time capsule?' => 'Fans can leave personal thank-you notes, record where they were in life when they started following the channel, upload photos, and cast milestone predictions (e.g., when the channel will hit 1M subs, game predictions, or life events).',
            'How do Creator Time Capsules work?' => 'Creators launch a dedicated time capsule in 60 seconds (for an upcoming 100K/1M sub milestone, anniversary, or series finale). Share your custom link with your community. Fans seal messages over time, and you get direct Stripe payouts and an interactive OBS unboxing studio for your reveal stream.',
            'Does FanVault replace a physical PO Box?' => 'Yes! Physical PO boxes require recurring rental fees, post office runs, hazardous physical parcel handling, and storage clutter. FanVault delivers all the emotional connection and tears of traditional fan mail with zero physical logistics, accessible to your global audience worldwide.',
            'How does pricing and payouts work for creators?' => 'Opening a vault is 100% free for creators. You set your own base contribution floor (such as $3, $5, or $10) to prevent spam. Fans can optionally add booster tips for special metallic foil certificates. Payouts route automatically and directly to your connected Stripe account.',
            'Can creators run multiple time capsules?' => 'Yes. Creators can create separate time capsules for upcoming subscriber milestones (e.g. 100K or 1M subs), podcast season finales, gaming marathons, annual anniversaries, or community celebrations.',
            'Is fan data and payment information secure?' => 'Yes. All card payments are tokenized through Stripe (PCI-DSS Level 1 compliant). Sealed fan letters and attached media are encrypted at rest with AES-256 and transmitted exclusively over secure TLS 1.3.',
        ];
    }

    public function with(): array
    {
        $faqItems = [
            [
                'category' => 'fans',
                'q' => 'What is FanVault?',
                'a' => 'FanVault is the digital time capsule built specifically for creators and their communities. Fans leave heartfelt letters, wild milestone predictions, and photos — sealed today and revealed together live on stream.',
            ],
            [
                'category' => 'fans',
                'q' => 'How does sealing a letter work?',
                'a' => 'You write your message, pick an optional photo or prediction, and set a 1-line public teaser. Only your teaser appears on the public community wall today. Your complete letter, portrait, and predictions stay strictly encrypted in the vault until reveal day.',
            ],
            [
                'category' => 'fans',
                'q' => 'When are the sealed messages unlocked?',
                'a' => 'Capsules stay sealed and encrypted until the creator reaches their milestone target date or subscriber goal. During their live celebration broadcast stream, the creator unlocks the vault and reads community letters live on camera with OBS broadcast graphics.',
            ],
            [
                'category' => 'fans',
                'q' => 'What can I put inside my sealed letter?',
                'a' => 'You can write personal thank-you notes, record where you were in life when you started following the channel, upload photos, and cast milestone predictions (e.g., when the channel will hit 1M subs, game predictions, or life events).',
            ],
            [
                'category' => 'creators',
                'q' => 'How do Creator Time Capsules work?',
                'a' => 'Creators launch a dedicated time capsule in 60 seconds (for an upcoming 100K/1M sub milestone, anniversary, or series finale). Share your custom link with your community. Fans seal messages over time, and you get direct Stripe payouts and an interactive OBS unboxing studio for your reveal stream.',
            ],
            [
                'category' => 'creators',
                'q' => 'Does FanVault replace a physical PO Box?',
                'a' => 'Yes! Physical PO boxes require recurring rental fees, post office runs, hazardous physical parcel handling, and storage clutter. FanVault delivers all the emotional connection and tears of traditional fan mail with zero physical logistics, accessible to your global audience worldwide.',
            ],
            [
                'category' => 'creators',
                'q' => 'How does pricing and payouts work for creators?',
                'a' => 'Opening a vault is 100% free for creators. You set your own base contribution floor (such as $3, $5, or $10) to prevent spam. Fans can optionally add booster tips for special metallic foil certificates. Payouts route automatically and directly to your connected Stripe account.',
            ],
            [
                'category' => 'creators',
                'q' => 'Can creators run multiple time capsules?',
                'a' => 'Yes. Creators can create separate time capsules for upcoming subscriber milestones (e.g. 100K or 1M subs), podcast season finales, gaming marathons, annual anniversaries, or community celebrations.',
            ],
            [
                'category' => 'security',
                'q' => 'Is fan data and payment information secure?',
                'a' => 'Yes. All card payments are tokenized through Stripe (PCI-DSS Level 1 compliant). Sealed fan letters and attached media are encrypted at rest with AES-256 and transmitted exclusively over secure TLS 1.3.',
            ],
        ];

        return [
            'faqItems' => $faqItems,
        ];
    }
};
?>

<div>
  <!-- Flat Architectural Header Banner -->
  <section class="relative w-full bg-[#03261d] text-white border-b border-emerald-900/60 overflow-hidden py-14 sm:py-20 px-4 sm:px-6 lg:px-8">
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

    <div class="max-w-3xl mx-auto text-center space-y-4 relative z-10">
      <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/5 border border-white/10 rounded-md text-xs font-mono text-emerald-300">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
        <span class="font-medium uppercase tracking-wider text-[11px]">Help & Documentation</span>
      </div>

      <h1 class="font-serif text-3xl sm:text-5xl font-bold tracking-tight text-white leading-tight">
        Frequently Asked Questions
      </h1>

      <p class="text-sm sm:text-base text-emerald-100/80 leading-relaxed max-w-xl mx-auto font-light">
        Everything you need to know about setting up a creator time capsule, sealing private fan letters, stream unboxings, and payouts.
      </p>
    </div>
  </section>

  <!-- FAQ Interactive Body -->
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16" x-data="{ active: 0, tab: 'all' }">
    <!-- Breadcrumb & Tag -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-8 pb-4 border-b border-[#e7e5df]">
      <div class="flex items-center gap-2 text-xs font-mono">
        <a href="{{ route('home') }}" class="text-[#64748b] hover:text-[#047857] transition-colors">Home</a>
        <span class="text-[#cbd5e1]">/</span>
        <span class="text-[#047857] font-semibold">FAQ</span>
      </div>

      <!-- Category Filter Pills -->
      <div class="flex flex-wrap items-center gap-2">
        <button 
          type="button" 
          @click="tab = 'all'" 
          :class="tab === 'all' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3 py-1 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          All ({{ count($faqItems) }})
        </button>
        <button 
          type="button" 
          @click="tab = 'fans'" 
          :class="tab === 'fans' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3 py-1 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          For Fans
        </button>
        <button 
          type="button" 
          @click="tab = 'creators'" 
          :class="tab === 'creators' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3 py-1 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          For Creators
        </button>
        <button 
          type="button" 
          @click="tab = 'security'" 
          :class="tab === 'security' ? 'bg-[#064e3b] text-white shadow-xs font-semibold' : 'bg-white hover:bg-[#faf9f5] text-[#475569] border border-[#e7e5df]'"
          class="px-3 py-1 rounded-lg text-xs font-mono transition-all cursor-pointer"
        >
          Security & Payments
        </button>
      </div>
    </div>

    <!-- Accordion List -->
    <div class="space-y-3.5 mb-12">
      @foreach($faqItems as $i => $item)
        <div 
          x-show="tab === 'all' || tab === '{{ $item['category'] }}'" 
          x-transition
          class="bg-white border border-[#e7e5df] hover:border-[#a7f3d0] rounded-xl overflow-hidden transition-all shadow-xs"
        >
          <button 
            type="button" 
            @click="active = (active === {{ $i }} ? null : {{ $i }})" 
            class="w-full text-left px-5 sm:px-6 py-4 sm:py-5 flex items-center justify-between gap-4 cursor-pointer focus:outline-none"
            :aria-expanded="active === {{ $i }}"
          >
            <div class="flex items-center gap-3">
              <span class="w-6 h-6 rounded-md bg-[#faf9f5] border border-[#e7e5df] text-[#064e3b] font-mono text-xs flex items-center justify-center shrink-0">
                {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
              </span>
              <span class="font-serif font-bold text-base sm:text-lg text-[#0f172a] leading-snug">
                {{ $item['q'] }}
              </span>
            </div>
            <span 
              class="w-7 h-7 rounded-lg bg-[#faf9f5] border border-[#e7e5df] text-[#047857] flex items-center justify-center shrink-0 transition-transform duration-200"
              :class="active === {{ $i }} ? 'rotate-180 bg-[#ecfdf5] border-[#a7f3d0]' : ''"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </span>
          </button>
          
          <div 
            x-show="active === {{ $i }}" 
            x-cloak 
            x-collapse
            class="px-5 sm:px-6 pb-5 pt-1 text-sm text-[#475569] leading-relaxed border-t border-[#f1f0eb]"
          >
            <p>{{ $item['a'] }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Quick Help Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-[#e7e5df]">
      <div class="p-5 sm:p-6 rounded-xl bg-[#faf9f5] border border-[#e7e5df] space-y-3">
        <div class="w-10 h-10 rounded-lg bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-lg text-[#064e3b]">
          🎬
        </div>
        <div>
          <h3 class="font-serif text-base font-bold text-[#0f172a]">Ready to open a vault?</h3>
          <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
            Create your community time capsule in under 60 seconds. Set your milestone date, price floor, and share your link.
          </p>
        </div>
        <a href="{{ route('creators.join') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#047857] hover:underline pt-1">
          Launch Time Capsule Wizard →
        </a>
      </div>

      <div class="p-5 sm:p-6 rounded-xl bg-[#faf9f5] border border-[#e7e5df] space-y-3">
        <div class="w-10 h-10 rounded-lg bg-[#ecfdf5] border border-[#a7f3d0] flex items-center justify-center text-lg text-[#064e3b]">
          💬
        </div>
        <div>
          <h3 class="font-serif text-base font-bold text-[#0f172a]">Need assistance or custom support?</h3>
          <p class="text-xs text-[#64748b] mt-1 leading-relaxed">
            Reach out to our team directly. We are always available to help creators prepare for high-volume milestone unboxing streams.
          </p>
        </div>
        <a href="mailto:support@getfanvault.com" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#047857] hover:underline pt-1">
          Email support@getfanvault.com →
        </a>
      </div>
    </div>
  </div>
</div>
