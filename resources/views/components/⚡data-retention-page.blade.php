<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Data Retention Policy — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Data Retention Policy — FanVault',
            'description' => 'Understand FanVault’s data retention schedules, cryptographic holding periods, financial record keeping, and automated deletion routines.',
            'canonicalUrl' => route('data-retention'),
            'ogUrl' => route('data-retention'),
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
      <span class="text-xs font-mono text-[#047857] font-semibold">Data Retention Policy</span>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
      🗄️ Retention Schedules · Effective October 2026
    </span>
  </div>

  <!-- Hero Header -->
  <header class="mb-12">
    <h1 class="font-serif text-3xl sm:text-5xl font-bold text-[#0f172a] tracking-tight mb-4">
      Data Retention Policy
    </h1>
    <p class="text-base sm:text-lg text-[#475569] leading-relaxed max-w-3xl">
      This Data Retention Policy outlines how long FanVault preserves information, the legal requirements governing our schedules, and how we securely sanitize and destroy expired records.
    </p>
  </header>

  <!-- Summary Table of Retention Periods -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-8 shadow-xs mb-10 overflow-x-auto">
    <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
      Data Retention Matrix
    </h2>
    <table class="w-full text-left text-xs sm:text-sm border-collapse">
      <thead>
        <tr class="border-b border-[#e7e5df] text-[#64748b] font-mono uppercase text-[11px]">
          <th class="py-3 pr-4">Data Classification</th>
          <th class="py-3 px-4">Primary Purpose</th>
          <th class="py-3 px-4">Retention Period</th>
          <th class="py-3 pl-4">Destruction Protocol</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[#f1f5f9] text-[#334155]">
        <tr>
          <td class="py-3.5 pr-4 font-bold text-[#0f172a]">Sealed Fan Letters & Predictions</td>
          <td class="py-3.5 px-4 text-[#475569]">Vault delivery for milestone celebration stream</td>
          <td class="py-3.5 px-4 font-mono font-semibold text-[#047857]">Duration of Vault + Fan Archive</td>
          <td class="py-3.5 pl-4 text-xs text-[#64748b]">Cryptographic deletion on verified fan request</td>
        </tr>
        <tr>
          <td class="py-3.5 pr-4 font-bold text-[#0f172a]">Financial & Ledger Records</td>
          <td class="py-3.5 px-4 text-[#475569]">Tax compliance, AML checks, audit trails</td>
          <td class="py-3.5 px-4 font-mono font-semibold text-[#047857]">7 Years (Statutory)</td>
          <td class="py-3.5 pl-4 text-xs text-[#64748b]">Automated cryptographic scrubbing post-statutory period</td>
        </tr>
        <tr>
          <td class="py-3.5 pr-4 font-bold text-[#0f172a]">Creator Studio & Account Data</td>
          <td class="py-3.5 px-4 text-[#475569]">Channel profile, milestone configuration</td>
          <td class="py-3.5 px-4 font-mono font-semibold text-[#047857]">Active Account Lifetime</td>
          <td class="py-3.5 pl-4 text-xs text-[#64748b]">Immediate upon creator closure request</td>
        </tr>
        <tr>
          <td class="py-3.5 pr-4 font-bold text-[#0f172a]">Magic Login Links & Tokens</td>
          <td class="py-3.5 px-4 text-[#475569]">Passwordless creator studio authentication</td>
          <td class="py-3.5 px-4 font-mono font-semibold text-[#047857]">30 Minutes</td>
          <td class="py-3.5 pl-4 text-xs text-[#64748b]">One-time use consumption & daily table purge</td>
        </tr>
        <tr>
          <td class="py-3.5 pr-4 font-bold text-[#0f172a]">Server & Application Logs</td>
          <td class="py-3.5 px-4 text-[#475569]">Security incident response & DDoS prevention</td>
          <td class="py-3.5 px-4 font-mono font-semibold text-[#047857]">90 Days</td>
          <td class="py-3.5 pl-4 text-xs text-[#64748b]">Automated rolling log rotation & permanent deletion</td>
        </tr>
        <tr>
          <td class="py-3.5 pr-4 font-bold text-[#0f172a]">Encrypted Database Backups</td>
          <td class="py-3.5 px-4 text-[#475569]">Disaster recovery & business continuity</td>
          <td class="py-3.5 px-4 font-mono font-semibold text-[#047857]">30 Days</td>
          <td class="py-3.5 pl-4 text-xs text-[#64748b]">Overwritten via rolling point-in-time backup snapshots</td>
        </tr>
      </tbody>
    </table>
  </section>

  <!-- Detailed Sections -->
  <article class="prose prose-slate max-w-none space-y-10 text-sm sm:text-base text-[#334155] leading-relaxed">

    <!-- Section 1 -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        1. Purpose & Guiding Principles
      </h2>
      <p class="mb-4">
        FanVault adheres to the principle of <strong>Data Minimization</strong>. We retain personal data only for as long as necessary to fulfill the purpose for which it was originally collected, or as strictly mandated by legal, accounting, and regulatory statutes.
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs sm:text-sm">
        <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold mb-1">Minimization</strong>
          <span class="text-[#64748b]">We never collect extraneous demographic or tracking data.</span>
        </div>
        <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold mb-1">Security at Rest</strong>
          <span class="text-[#64748b]">All retained data is safeguarded with 256-bit AES encryption.</span>
        </div>
        <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold mb-1">User Control</strong>
          <span class="text-[#64748b]">Users retain rights to request accelerated deletion anytime.</span>
        </div>
      </div>
    </section>

    <!-- Section 2 -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        2. Fan Letter & Keepsake Lifecycle
      </h2>
      <p class="mb-4">
        The core of FanVault is a community time capsule. When a fan seals a letter:
      </p>
      <ol class="space-y-3 text-xs sm:text-sm text-[#475569] list-decimal list-inside">
        <li><strong>Pre-Reveal State:</strong> The full letter body is sealed and locked in our database. It cannot be viewed publicly and remains held for the scheduled milestone reveal.</li>
        <li><strong>Stream Reveal:</strong> On the creator’s milestone stream date, the letter becomes unsealed for the creator’s broadcast view.</li>
        <li><strong>Fan Archive:</strong> The fan retains perpetual private access to their authored letter and commemorative pass using their unique claim token.</li>
        <li><strong>Deletion on Demand:</strong> If a fan wishes to delete their letter before or after the stream reveal, they may request immediate purging via our <a href="{{ route('data-deletion') }}" class="text-[#047857] font-bold underline">Deletion Procedure</a>.</li>
      </ol>
    </section>

    <!-- Section 3 -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        3. Financial Records & Statutory Legal Holds
      </h2>
      <p class="mb-4">
        To comply with United States Internal Revenue Service (IRS), European Union Value-Added Tax (VAT), and global Anti-Money Laundering (AML) laws, FanVault is legally obligated to retain transactional accounting records for <strong>seven (7) years</strong> from the date of transaction.
      </p>
      <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200 text-xs sm:text-sm text-amber-900">
        <strong>Privacy Isolation:</strong> While financial ledger records (transaction ID, currency, timestamp, amount) must be retained for 7 years, any associated fan personal letters or messages can still be scrubbed or anonymized upon request without violating tax record requirements.
      </div>
    </section>

    <!-- Section 4 -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        4. Cryptographic Sanitization & Deletion
      </h2>
      <p class="mb-4">
        When records reach their retention expiration date or when a verified user deletion request is fulfilled:
      </p>
      <ul class="space-y-2 text-xs sm:text-sm text-[#475569] list-disc list-inside">
        <li>The specific database records are hard-deleted using atomic database operations.</li>
        <li>Cached copies in application and view caches are evicted immediately.</li>
        <li>Automated disaster recovery backups expire and roll off within thirty (30) days.</li>
      </ul>
    </section>

    <!-- Contact Box -->
    <section class="bg-gradient-to-br from-[#064e3b] to-[#047857] text-white rounded-3xl p-6 sm:p-9 shadow-md">
      <h3 class="font-serif text-xl sm:text-2xl font-bold mb-2">Data Retention Questions</h3>
      <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed mb-4">
        For inquiries regarding our storage schedules or to request a retention audit for your data, please contact:
      </p>
      <div class="text-xs font-mono bg-white/10 px-3.5 py-2 rounded-xl backdrop-blur-xs w-fit">
        Compliance Team: <a href="mailto:privacy@getfanvault.com" class="text-white font-bold underline">privacy@getfanvault.com</a>
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
      <a href="{{ route('data-deletion') }}" class="hover:underline">Deletion Procedure</a>
      <span>·</span>
      <a href="{{ route('security') }}" class="hover:underline">Security Disclosure</a>
    </div>
  </footer>
</div>
