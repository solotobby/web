<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Security Architecture & Vulnerability Disclosure — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Security & Vulnerability Disclosure — FanVault',
            'description' => 'Explore FanVault’s security architecture, 256-bit encryption safeguards, Stripe PCI compliance, and our Responsible Vulnerability Disclosure Program.',
            'canonicalUrl' => route('security'),
            'ogUrl' => route('security'),
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
      <span class="text-xs font-mono text-[#047857] font-semibold">Security & Disclosure</span>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
      🛡️ 256-Bit Encrypted Vault Protocol · Responsible Disclosure
    </span>
  </div>

  <!-- Hero Header -->
  <header class="mb-12">
    <h1 class="font-serif text-3xl sm:text-5xl font-bold text-[#0f172a] tracking-tight mb-4">
      Security & Vulnerability Disclosure
    </h1>
    <p class="text-base sm:text-lg text-[#475569] leading-relaxed max-w-3xl">
      FanVault protects heartfelt memories, milestone letters, and creator financial ledgers. We maintain defense-in-depth security standards and actively welcome collaboration with the global security research community.
    </p>
  </header>

  <!-- Security Pillars Grid -->
  <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs mb-10">
    <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-6">
      Core Security Controls
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="text-2xl">🔒</div>
        <strong class="block text-sm font-bold text-[#0f172a]">256-Bit Encryption</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Letters and sensitive database records are encrypted with AES-256 at rest, and all web traffic is forced through TLS 1.3 with strict HSTS.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="text-2xl">💳</div>
        <strong class="block text-sm font-bold text-[#0f172a]">PCI-DSS Level 1 via Stripe</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Payment card data is tokenized straight in your browser. FanVault servers never view, touch, or store raw credit card numbers.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="text-2xl">🔑</div>
        <strong class="block text-sm font-bold text-[#0f172a]">Passwordless Magic Links</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Creators authenticate via cryptographically random, single-use magic login tokens that expire in 30 minutes, preventing credential stuffing.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="text-2xl">🎫</div>
        <strong class="block text-sm font-bold text-[#0f172a]">SHA-256 Token Hashing</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Fan letter claim keys are hashed using SHA-256 before storage. Even in the event of an internal audit, secrets remain irreversible.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="text-2xl">🛡️</div>
        <strong class="block text-sm font-bold text-[#0f172a]">Defense-in-Depth</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Strict CSRF verification, prepared SQL statements, XSS auto-escaping in Blade templates, and rate-limiting on sensitive endpoints.
        </p>
      </div>

      <div class="p-4 rounded-2xl bg-[#faf9f5] border border-[#e7e5df] space-y-2">
        <div class="text-2xl">☁️</div>
        <strong class="block text-sm font-bold text-[#0f172a]">Isolated Cloud Infrastructure</strong>
        <p class="text-xs text-[#64748b] leading-relaxed">
          Hosted on dedicated AWS EC2 infrastructure with automated security patches, encrypted snapshots, and isolated production environments.
        </p>
      </div>
    </div>
  </section>

  <!-- Vulnerability Disclosure Program -->
  <article class="prose prose-slate max-w-none space-y-10 text-sm sm:text-base text-[#334155] leading-relaxed">

    <!-- Section 1: Policy Overview -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        Responsible Vulnerability Disclosure Program (VDP)
      </h2>
      <p class="mb-4">
        We believe responsible disclosure is vital to a safe internet. If you are an independent security researcher and discover a vulnerability in our application, we encourage you to report it to us immediately.
      </p>
      
      <div class="space-y-4 text-xs sm:text-sm">
        <div class="p-4 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="text-[#047857] block font-bold mb-1">Our Safe Harbor Commitment:</strong>
          <p class="text-[#475569]">
            If you conduct your research in good faith, avoid violating the privacy of other users, do not disrupt platform availability, and give us reasonable time to resolve the issue before public disclosure, <strong>FanVault will not pursue legal action against you or seek law enforcement intervention</strong>.
          </p>
        </div>
      </div>
    </section>

    <!-- Scope -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        Scope & Boundaries
      </h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
        <div class="border border-emerald-200 bg-emerald-50/40 rounded-2xl p-4">
          <strong class="text-[#064e3b] font-bold block mb-2">✓ In Scope:</strong>
          <ul class="list-disc list-inside space-y-1 text-[#334155]">
            <li><code>getfanvault.com</code> and primary web application</li>
            <li>Authentication bypasses & privilege escalation</li>
            <li>SQL injection & data exfiltration risks</li>
            <li>Stored or reflected Cross-Site Scripting (XSS)</li>
            <li>Stripe webhook signature bypasses</li>
            <li>Unauthenticated access to sealed letters</li>
          </ul>
        </div>

        <div class="border border-red-200 bg-red-50/40 rounded-2xl p-4">
          <strong class="text-red-800 font-bold block mb-2">✕ Out of Scope:</strong>
          <ul class="list-disc list-inside space-y-1 text-[#475569]">
            <li>Volumetric Denial of Service (DDoS) attacks</li>
            <li>Spamming or automated brute-forcing</li>
            <li>Social engineering or phishing of staff</li>
            <li>Third-party services (e.g. Stripe, AWS endpoints)</li>
            <li>Missing DNSSEC or cosmetic HTTP headers without exploit</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- How to Report -->
    <section class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        How to Submit a Security Report
      </h2>
      <p class="mb-4">
        Please submit all vulnerability disclosures directly to our Security Operations Team at <a href="mailto:security@getfanvault.com" class="text-[#047857] font-bold hover:underline">security@getfanvault.com</a>.
      </p>

      <div class="p-4 rounded-xl bg-[#f5f4ee] border border-[#e7e5df] text-xs font-mono text-[#0f172a] mb-4 space-y-1">
        <div><strong>Please include in your report:</strong></div>
        <div>1. A descriptive title and vulnerability category (e.g., IDOR, SQLi, Auth Bypass)</div>
        <div>2. Detailed step-by-step reproduction instructions or a minimal Proof of Concept (PoC)</div>
        <div>3. Potential impact assessment and affected endpoints</div>
        <div>4. Your preferred name or handle for security acknowledgment</div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs sm:text-sm mt-5">
        <div class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold">24 Hours</strong>
          <span class="text-[#64748b]">Initial receipt acknowledgment</span>
        </div>
        <div class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold">72 Hours</strong>
          <span class="text-[#64748b]">Engineering triage & severity rating</span>
        </div>
        <div class="p-3.5 rounded-xl bg-[#faf9f5] border border-[#e7e5df]">
          <strong class="block text-[#047857] font-bold">Continuous</strong>
          <span class="text-[#64748b]">Status updates until patch deployment</span>
        </div>
      </div>
    </section>

    <!-- Researcher Hall of Fame -->
    <section class="bg-gradient-to-br from-[#064e3b] to-[#047857] text-white rounded-3xl p-6 sm:p-9 shadow-md">
      <h3 class="font-serif text-xl sm:text-2xl font-bold mb-2">Researcher Hall of Fame</h3>
      <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed mb-4">
        We publicly acknowledge researchers who responsibly disclose verified security vulnerabilities:
      </p>
      <div class="text-xs font-mono bg-white/10 px-3.5 py-2 rounded-xl backdrop-blur-xs w-fit">
        Security Operations Desk: <a href="mailto:security@getfanvault.com" class="text-white font-bold underline">security@getfanvault.com</a>
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
      <a href="{{ route('data-deletion') }}" class="hover:underline">Deletion Procedure</a>
    </div>
  </footer>
</div>
