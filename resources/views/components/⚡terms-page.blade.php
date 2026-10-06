<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Terms of Service — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Terms of Service — FanVault',
            'description' => 'Review the Terms of Service for FanVault, governing creator community vaults, sealed fan letter submissions, custom pricing, and Stripe payouts.',
            'canonicalUrl' => route('terms'),
            'ogUrl' => route('terms'),
        ]);
    }
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
  <!-- Top Breadcrumb & Badge -->
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-2">
      <a href="{{ route('home') }}" class="text-xs font-mono text-[#64748b] hover:text-[#047857] transition-colors">Home</a>
      <span class="text-xs text-[#cbd5e1]">/</span>
      <span class="text-xs font-mono text-[#047857] font-semibold">Terms of Service</span>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
      📜 Legal Agreement · Effective October 2026
    </span>
  </div>

  <!-- Hero Header -->
  <header class="mb-12">
    <h1 class="font-serif text-3xl sm:text-5xl font-bold text-[#0f172a] tracking-tight mb-4">
      Terms of Service
    </h1>
    <p class="text-base sm:text-lg text-[#475569] leading-relaxed max-w-3xl">
      These Terms of Service (“Terms”) constitute a legally binding agreement between you (“User”, “Creator”, or “Fan”) and FanVault (“FanVault”, “we”, “our”, or “us”). By visiting, registering for, or sealing a letter on FanVault, you agree to these Terms.
    </p>
  </header>

  <!-- Quick Policy Navigator -->
  <nav class="bg-[#f5f4ee] border border-[#e7e5df] rounded-2xl p-5 mb-12" aria-label="Terms sections">
    <div class="text-xs font-mono uppercase tracking-wider text-[#64748b] font-bold mb-3">Table of Contents</div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-xs">
      <a href="#service-description" class="text-[#047857] hover:underline">1. Description of Service</a>
      <a href="#financial-terms" class="text-[#047857] hover:underline">2. Financials & Creator Payouts</a>
      <a href="#user-conduct" class="text-[#047857] hover:underline">3. Content & Prohibited Conduct</a>
      <a href="#intellectual-property" class="text-[#047857] hover:underline">4. Intellectual Property Rights</a>
      <a href="#refund-policy" class="text-[#047857] hover:underline">5. Payments & Refund Policy</a>
      <a href="#liability-disclaimer" class="text-[#047857] hover:underline">6. Warranties & Liability</a>
    </div>
  </nav>

  <!-- Legal Articles -->
  <article class="prose prose-slate max-w-none space-y-10 text-sm sm:text-base text-[#334155] leading-relaxed">

    <!-- Section 1 -->
    <section id="service-description" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        1. Description of Service
      </h2>
      <p class="mb-4">
        FanVault provides digital milestone time capsules and community fan mail vaults for content creators across YouTube, Twitch, TikTok, Kick, podcasts, Substack, and social media platforms.
      </p>
      <ul class="space-y-2 text-xs sm:text-sm text-[#475569]">
        <li><strong>Creator Vaults:</strong> Online creators establish a public door link (<code>/with/{slug}</code>) to collect heartfelt letters, milestone predictions, and memories from their community.</li>
        <li><strong>Digital Sealing:</strong> Fans draft and seal private messages that remain securely locked in the vault until the creator’s target milestone celebration stream.</li>
        <li><strong>Stream Reveal & Reader:</strong> Creators unseal submissions on stream day using FanVault’s dedicated broadcast view to read and celebrate community letters live.</li>
      </ul>
    </section>

    <!-- Section 2 -->
    <section id="financial-terms" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        2. Economics, Pricing & Creator Payouts
      </h2>
      <p class="mb-4">
        FanVault operates an open, creator-first revenue model:
      </p>
      <div class="space-y-4 text-xs sm:text-sm">
        <div class="bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-xl">
          <strong class="block text-sm font-bold text-[#0f172a] mb-1">A. Custom Creator Pricing</strong>
          <p class="text-[#475569]">
            Creators specify their own minimum letter pricing (platform minimum is $3.00 USD). Fans may contribute at this base price or choose higher patronage levels and booster tips ($10, $25, $50+) to champion the channel.
          </p>
        </div>
        <div class="bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-xl">
          <strong class="block text-sm font-bold text-[#0f172a] mb-1">B. 80% Creator Revenue Share</strong>
          <p class="text-[#475569]">
            Creators earn <strong>80% of all gross contribution revenue</strong> from letters sealed into their vault. The remaining 20% platform fee funds 256-bit encrypted data storage, email delivery infrastructure, fraud monitoring, and maintenance.
          </p>
        </div>
        <div class="bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-xl">
          <strong class="block text-sm font-bold text-[#0f172a] mb-1">C. Stripe Connect Payouts</strong>
          <p class="text-[#475569]">
            Payouts are deposited directly to creators’ verified bank accounts via Stripe Connect. Creators are responsible for all applicable local taxes, income reporting, and compliance associated with their earnings.
          </p>
        </div>
      </div>
    </section>

    <!-- Section 3 -->
    <section id="user-conduct" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        3. Acceptable Use & Prohibited Content
      </h2>
      <p class="mb-4">
        FanVault is a positive space dedicated to celebrating creator milestones. You agree not to submit letters, comments, or profile information that contain:
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm text-[#475569]">
        <div class="p-3 border border-red-100 bg-red-50/50 rounded-xl">
          <strong class="text-red-700 block mb-0.5">✕ Hate Speech & Harassment</strong>
          Discriminatory remarks, bullying, threats of violence, or targeted defamation.
        </div>
        <div class="p-3 border border-red-100 bg-red-50/50 rounded-xl">
          <strong class="text-red-700 block mb-0.5">✕ Doxxing & Private Data</strong>
          Unauthorized disclosure of home addresses, phone numbers, or private personal data.
        </div>
        <div class="p-3 border border-red-100 bg-red-50/50 rounded-xl">
          <strong class="text-red-700 block mb-0.5">✕ Malicious Software</strong>
          Phishing links, malicious code, exploits, or automated bot scraping.
        </div>
        <div class="p-3 border border-red-100 bg-red-50/50 rounded-xl">
          <strong class="text-red-700 block mb-0.5">✕ Illegal Activity</strong>
          Content promoting illicit transactions, extortion, fraud, or child exploitation.
        </div>
      </div>
      <p class="mt-4 text-xs sm:text-sm text-[#475569]">
        FanVault and creators reserve the absolute right to redact, hide, or permanently purge any submission that violates these community standards without notice.
      </p>
    </section>

    <!-- Section 4 -->
    <section id="intellectual-property" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        4. Intellectual Property & Broadcast License
      </h2>
      <div class="space-y-3 text-xs sm:text-sm text-[#475569]">
        <p>
          <strong class="text-[#0f172a]">Your Content Ownership:</strong> You retain complete copyright ownership of the original text, stories, and predictions you author and submit to a vault.
        </p>
        <p>
          <strong class="text-[#0f172a]">Broadcast & Reading License:</strong> By sealing a letter into a creator’s vault, you grant the creator a royalty-free, perpetual, worldwide license to display, read aloud, quote, or feature your submitted letter and author pseudonym during their live broadcasts, milestone streams, videos, and social media recaps.
        </p>
        <p>
          <strong class="text-[#0f172a]">FanVault Intellectual Property:</strong> All trademarks, logos, system software, visual designs, and the time capsule protocol are the exclusive intellectual property of FanVault.
        </p>
      </div>
    </section>

    <!-- Section 5 -->
    <section id="refund-policy" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        5. Payments & Refund Policy
      </h2>
      <p class="mb-4">
        Contributions made through FanVault represent immediate digital patronage and the instant minting of a commemorative time capsule pass.
      </p>
      <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df] text-xs sm:text-sm text-[#475569] space-y-2">
        <p>
          <strong>All Sales Final:</strong> Due to the immediate allocation of funds to creators' ledgers and the irreversible generation of cryptographic vault passes, contributions are generally non-refundable.
        </p>
        <p>
          <strong>Exceptions & Inquiries:</strong> In cases of unauthorized transactions or billing errors, please contact <a href="mailto:support@getfanvault.com" class="text-[#047857] font-bold hover:underline">support@getfanvault.com</a> within 14 days of the transaction for prompt review.
        </p>
      </div>
    </section>

    <!-- Section 6 -->
    <section id="liability-disclaimer" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        6. Disclaimers of Warranties & Limitation of Liability
      </h2>
      <p class="text-xs sm:text-sm text-[#475569] mb-4">
        THE SERVICE IS PROVIDED “AS IS” AND “AS AVAILABLE” WITHOUT WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED. FANVAULT DISCLAIMS ALL WARRANTIES, INCLUDING MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.
      </p>
      <p class="text-xs sm:text-sm text-[#475569] mb-4">
        TO THE MAXIMUM EXTENT PERMITTED BY LAW, FANVAULT SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, NOR ANY LOSS OF PROFITS, DATA, OR GOODWILL ARISING FROM OR RELATING TO YOUR USE OF THE SERVICE.
      </p>
      <p class="text-xs sm:text-sm text-[#475569]">
        IN NO EVENT SHALL FANVAULT’S AGGREGATE LIABILITY EXCEED THE TOTAL FEES RECEIVED BY FANVAULT FROM YOU IN THE TWELVE (12) MONTHS PRECEDING THE CLAIM.
      </p>
    </section>

    <!-- Governing Law -->
    <section class="bg-gradient-to-br from-[#064e3b] to-[#047857] text-white rounded-3xl p-6 sm:p-9 shadow-md">
      <h3 class="font-serif text-xl sm:text-2xl font-bold mb-2">Governing Law & Disputes</h3>
      <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed mb-4">
        These Terms are governed by and construed in accordance with the laws of the State of Delaware, without regard to conflict of law principles. Any legal claim or dispute shall be resolved through binding arbitration or state/federal courts of competent jurisdiction.
      </p>
      <div class="text-xs font-mono bg-white/10 px-3.5 py-2 rounded-xl backdrop-blur-xs w-fit">
        Legal Contact: <a href="mailto:legal@getfanvault.com" class="text-white font-bold underline">legal@getfanvault.com</a>
      </div>
    </section>

  </article>

  <!-- Related Policies Bar -->
  <footer class="mt-12 pt-8 border-t border-[#e7e5df] flex flex-wrap items-center justify-between gap-4 text-xs text-[#64748b]">
    <span class="font-medium">Related Legal Documentation:</span>
    <div class="flex flex-wrap gap-4 font-semibold text-[#047857]">
      <a href="{{ route('privacy') }}" class="hover:underline">Privacy Policy</a>
      <span>·</span>
      <a href="{{ route('data-retention') }}" class="hover:underline">Data Retention</a>
      <span>·</span>
      <a href="{{ route('data-deletion') }}" class="hover:underline">Deletion Procedure</a>
      <span>·</span>
      <a href="{{ route('security') }}" class="hover:underline">Security Disclosure</a>
    </div>
  </footer>
</div>
