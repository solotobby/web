<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Data & Account Deletion Procedure — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Data & Account Deletion Procedure — FanVault',
            'description' => 'Step-by-step instructions for fans and creators to request full personal data erasure, letter removal, or account closure on FanVault.',
            'canonicalUrl' => route('data-deletion'),
            'ogUrl' => route('data-deletion'),
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
      <span class="text-xs font-mono text-[#047857] font-semibold">Deletion Procedure</span>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
      🗑️ GDPR Art. 17 & CCPA Erasure Procedure
    </span>
  </div>

  <!-- Hero Header -->
  <header class="mb-12">
    <h1 class="font-serif text-3xl sm:text-5xl font-bold text-[#0f172a] tracking-tight mb-4">
      Data & Account Deletion Procedure
    </h1>
    <p class="text-base sm:text-lg text-[#475569] leading-relaxed max-w-3xl">
      You have the absolute right to request the complete deletion of your personal data, sealed letters, and accounts from FanVault. We provide a straightforward, verified procedure with no hidden hurdles or cancellation fees.
    </p>
  </header>

  <!-- 3-Step Overview Box -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs mb-10">
    <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-6">
      How the Deletion Process Works
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="w-8 h-8 rounded-full bg-[#064e3b] text-white flex items-center justify-center font-bold text-sm font-mono">1</div>
        <strong class="block text-sm font-bold text-[#0f172a]">Submit Request</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Send an erasure request via email to <code>privacy@getfanvault.com</code> or follow the direct procedures below.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="w-8 h-8 rounded-full bg-[#064e3b] text-white flex items-center justify-center font-bold text-sm font-mono">2</div>
        <strong class="block text-sm font-bold text-[#0f172a]">Security Verification</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          We send a one-click confirmation link to the email associated with the letter or vault to prevent unauthorized deletion.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="w-8 h-8 rounded-full bg-[#064e3b] text-white flex items-center justify-center font-bold text-sm font-mono">3</div>
        <strong class="block text-sm font-bold text-[#0f172a]">Permanent Purging</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Within 30 days, your records are permanently purged from all production tables and secondary caches.
        </p>
      </div>
    </div>
  </section>

  <!-- Detailed Procedures -->
  <article class="prose prose-slate max-w-none space-y-10 text-sm sm:text-base text-[#334155] leading-relaxed">

    <!-- Procedure for Fans -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4 flex items-center gap-2">
        <span>A. Procedure for Fans (Letter Authors)</span>
      </h2>
      <p class="mb-4">
        If you previously sealed a letter or prediction into a creator’s vault and wish to remove it:
      </p>
      
      <div class="bg-[#faf9f5] border border-[#e7e5df] rounded-2xl p-5 mb-5 space-y-3 text-xs sm:text-sm text-[#475569]">
        <strong class="text-[#0f172a] block text-sm font-bold">Options Available to Fans:</strong>
        <ul class="list-disc list-inside space-y-1.5">
          <li><strong>Full Letter Purging:</strong> Permanently removes the text of your letter, email address, author name, and commemorative pass from the creator’s vault.</li>
          <li><strong>Author Anonymization:</strong> Keeps your contribution in the creator’s milestone count, but strips your name, location, and email, replacing it with “Anonymous Superfan”.</li>
        </ul>
      </div>

      <div class="p-5 rounded-2xl bg-[#f5f4ee] border border-[#e7e5df]">
        <strong class="text-[#0f172a] block text-sm font-bold mb-2">How to Submit:</strong>
        <p class="text-xs sm:text-sm text-[#475569] mb-3">
          Send an email to <a href="mailto:privacy@getfanvault.com" class="text-[#047857] font-bold hover:underline">privacy@getfanvault.com</a> with the following details:
        </p>
        <pre class="bg-white p-4 rounded-xl border border-[#e7e5df] text-xs font-mono text-[#0f172a] overflow-x-auto">
Subject: Fan Letter Deletion Request - [Your Author Name]
Body:
- Email address used when sealing: [you@example.com]
- Creator vault name: [e.g. Maya Lin]
- Approximate date sealed: [e.g. September 2026]
- Action requested: [Full Purge / Anonymization]</pre>
      </div>
    </section>

    <!-- Procedure for Creators -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4 flex items-center gap-2">
        <span>B. Procedure for Creators</span>
      </h2>
      <p class="mb-4">
        If you are a content creator and wish to close your creator vault and delete your account:
      </p>
      <div class="space-y-4 text-xs sm:text-sm text-[#475569]">
        <p>
          1. <strong>Payout Settlement:</strong> Before closing, ensure all pending payouts in your Creator Studio earnings ledger have been settled to your Stripe Connect bank account.
        </p>
        <p>
          2. <strong>Vault Deactivation Request:</strong> Email <a href="mailto:privacy@getfanvault.com" class="text-[#047857] font-bold hover:underline">privacy@getfanvault.com</a> from your verified creator email address.
        </p>
        <p>
          3. <strong>Immediate Effects:</strong> Your public door (<code>/with/{slug}</code>) is instantly deactivated, preventing any new letters or contributions.
        </p>
        <p>
          4. <strong>Archive & Deletion:</strong> Your bio, channel handles, connected platform links, and milestones are permanently removed from our active database.
        </p>
      </div>
    </section>

    <!-- SLAs and Exceptions -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        C. Timelines & Statutory Exceptions
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm mb-4">
        <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold mb-1">⏱️ 48-Hour Acknowledgment</strong>
          <p class="text-[#475569]">Our privacy team will acknowledge your verified request within two business days.</p>
        </div>
        <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold mb-1">📅 30-Day Eradication</strong>
          <p class="text-[#475569]">All requested records are completely sanitized from production systems within thirty days.</p>
        </div>
      </div>
      <div class="p-4 rounded-xl bg-amber-50/60 border border-amber-200 text-xs sm:text-sm text-amber-900">
        <strong>Statutory Financial Records Hold:</strong> Under international tax, accounting, and anti-fraud regulations, tokenized payment transaction logs (e.g., Stripe payment intent IDs, dollar amounts, and timestamps) are retained for 7 years as non-personal audit records. Your private letter text and identity are completely purged.
      </div>
    </section>

    <!-- Support Box -->
    <section class="bg-gradient-to-br from-[#064e3b] to-[#047857] text-white rounded-3xl p-6 sm:p-9 shadow-md">
      <h3 class="font-serif text-xl sm:text-2xl font-bold mb-2">Need Immediate Assistance?</h3>
      <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed mb-4">
        Our dedicated compliance officers are available to assist with data erasure inquiries or questions regarding our procedures:
      </p>
      <div class="text-xs font-mono bg-white/10 px-3.5 py-2 rounded-xl backdrop-blur-xs w-fit">
        Privacy & Erasure Desk: <a href="mailto:privacy@getfanvault.com" class="text-white font-bold underline">privacy@getfanvault.com</a>
      </div>
    </section>

  </article>

  <!-- Related Policies Bar -->
  <footer class="mt-12 pt-8 border-t border-[#e7e5df] flex flex-wrap items-center justify-between gap-4 text-xs text-[#64748b]">
    <span class="font-medium">Related Legal Documentation:</span>
    <div class="flex flex-wrap gap-4 font-semibold text-[#047857]">
      <a href="{{ route('privacy') }}" class="hover:underline">Privacy Policy</a>
      <span>·</span>
      <a href="{{ route('terms') }}" class="hover:underline">Terms of Service</a>
      <span>·</span>
      <a href="{{ route('data-retention') }}" class="hover:underline">Data Retention</a>
      <span>·</span>
      <a href="{{ route('security') }}" class="hover:underline">Security Disclosure</a>
    </div>
  </footer>
</div>
