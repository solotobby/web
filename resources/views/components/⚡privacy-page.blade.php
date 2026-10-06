<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Privacy Policy — FanVault')]
class extends Component
{
    public function rendering($view): void
    {
        $view->layoutData([
            'title' => 'Privacy Policy — FanVault',
            'description' => 'Read how FanVault collects, protects, encrypts, and handles personal data for fans and creators under GDPR, CCPA, and global privacy standards.',
            'canonicalUrl' => route('privacy'),
            'ogUrl' => route('privacy'),
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
      <span class="text-xs font-mono text-[#047857] font-semibold">Privacy Policy</span>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#ecfdf5] text-[#064e3b] border border-[#a7f3d0]">
      🛡️ GDPR & CCPA Compliant · Last Updated October 2026
    </span>
  </div>

  <!-- Hero Header -->
  <header class="mb-12">
    <h1 class="font-serif text-3xl sm:text-5xl font-bold text-[#0f172a] tracking-tight mb-4">
      Privacy Policy
    </h1>
    <p class="text-base sm:text-lg text-[#475569] leading-relaxed max-w-3xl">
      FanVault (“we”, “our”, or “us”) provides digital milestone time capsules and fan mail vaults for online creators and their communities. We believe your memories, letters, and identity should remain private, encrypted, and handled with institutional rigor.
    </p>
  </header>

  <!-- Quick Policy Navigator -->
  <nav class="bg-[#f5f4ee] border border-[#e7e5df] rounded-2xl p-5 mb-12" aria-label="Privacy sections">
    <div class="text-xs font-mono uppercase tracking-wider text-[#64748b] font-bold mb-3">Quick Navigation</div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-xs">
      <a href="#info-we-collect" class="text-[#047857] hover:underline">1. Information We Collect</a>
      <a href="#how-we-use" class="text-[#047857] hover:underline">2. How We Use Your Data</a>
      <a href="#payment-data" class="text-[#047857] hover:underline">3. Payment & Stripe Tokenization</a>
      <a href="#subprocessors" class="text-[#047857] hover:underline">4. Sub-processors & Third Parties</a>
      <a href="#your-rights" class="text-[#047857] hover:underline">5. Your Privacy Rights (GDPR / CCPA)</a>
      <a href="#security-retention" class="text-[#047857] hover:underline">6. Security & Retention</a>
    </div>
  </nav>

  <!-- Main Legal Content -->
  <article class="prose prose-slate max-w-none space-y-10 text-sm sm:text-base text-[#334155] leading-relaxed">
    
    <!-- Section 1 -->
    <section id="info-we-collect" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4 flex items-center gap-2">
        <span>1. Information We Collect</span>
      </h2>
      <p class="mb-4">
        We collect only the minimum personal data required to provide our time capsule vault services, verify transactions, deliver email notifications, and facilitate creator payouts.
      </p>
      
      <div class="space-y-4">
        <div class="bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-xl">
          <strong class="block text-sm font-bold text-[#0f172a] mb-1">A. Information Provided by Fans</strong>
          <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm text-[#475569]">
            <li><strong>Author Name or Pseudonym:</strong> Displayed on commemorative passes and letter headers.</li>
            <li><strong>Email Address:</strong> Used solely to dispatch your sealed keepsake link, unique claim token, and milestone reveal alerts.</li>
            <li><strong>Letter Content & Predictions:</strong> The confidential text and message you seal into the creator’s vault.</li>
            <li><strong>Optional City / Location:</strong> Placed optionally on your digital postcard pass.</li>
            <li><strong>Contribution Amount:</strong> The dollar amount chosen to champion the creator’s milestone.</li>
          </ul>
        </div>

        <div class="bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-xl">
          <strong class="block text-sm font-bold text-[#0f172a] mb-1">B. Information Provided by Creators</strong>
          <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm text-[#475569]">
            <li><strong>Creator Name & Channel Handle:</strong> Public identifier for your vault URL (<code>/with/{slug}</code>).</li>
            <li><strong>Connected Platforms:</strong> Multi-platform links (YouTube, Twitch, TikTok, Kick, Podcast, Instagram, X/Twitter, Substack).</li>
            <li><strong>Creator Email Address:</strong> Used exclusively for magic login links and critical platform alerts.</li>
            <li><strong>Milestone Goals & Reveal Dates:</strong> Public target events and unlock schedules.</li>
            <li><strong>Stripe Connect Account Identifiers:</strong> Tokenized IDs required to deposit the 80% creator cut directly into your bank account.</li>
          </ul>
        </div>

        <div class="bg-[#faf9f5] border-l-2 border-[#047857] p-4 rounded-r-xl">
          <strong class="block text-sm font-bold text-[#0f172a] mb-1">C. Technical & Log Data</strong>
          <p class="text-xs sm:text-sm text-[#475569]">
            When you visit FanVault, our servers log standard HTTP request metadata including IP address, user-agent string, and timestamp. This data is used solely for DDoS prevention, rate-limiting, and fraud mitigation, and is automatically purged after 90 days.
          </p>
        </div>
      </div>
    </section>

    <!-- Section 2 -->
    <section id="how-we-use" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        2. How We Use Your Data & Legal Basis
      </h2>
      <p class="mb-4">
        Under the EU General Data Protection Regulation (GDPR) and UK Data Protection Act, our legal basis for collecting and processing your personal data is:
      </p>
      <ul class="space-y-3 text-xs sm:text-sm text-[#475569]">
        <li class="flex items-start gap-2.5">
          <span class="text-[#047857] font-bold text-base leading-none">✓</span>
          <div>
            <strong class="text-[#0f172a]">Performance of a Contract:</strong> Providing the digital time capsule service, locking letters until milestone dates, delivering your commemorative passes, and transferring creator earnings.
          </div>
        </li>
        <li class="flex items-start gap-2.5">
          <span class="text-[#047857] font-bold text-base leading-none">✓</span>
          <div>
            <strong class="text-[#0f172a]">Legitimate Business Interests:</strong> Maintaining platform stability, preventing fraudulent charges, debugging software errors, and enforcing our terms of service.
          </div>
        </li>
        <li class="flex items-start gap-2.5">
          <span class="text-[#047857] font-bold text-base leading-none">✓</span>
          <div>
            <strong class="text-[#0f172a]">Legal Compliance:</strong> Maintaining financial ledgers and accounting records as mandated by applicable tax and commercial authorities.
          </div>
        </li>
      </ul>
      <div class="mt-4 p-4 rounded-xl bg-amber-50/60 border border-amber-200 text-xs sm:text-sm text-amber-900">
        <strong>We Never Sell Data:</strong> FanVault has never sold, rented, or monetized personal information, letters, email addresses, or browsing history to third-party data brokers or advertisers, and will never do so.
      </div>
    </section>

    <!-- Section 3 -->
    <section id="payment-data" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        3. Payment Processing & Stripe Tokenization
      </h2>
      <p class="mb-4">
        All financial payments and card transactions are processed securely through <strong>Stripe, Inc.</strong>, a certified <strong>PCI-DSS Level 1 Service Provider</strong>.
      </p>
      <p class="text-xs sm:text-sm text-[#475569] mb-4">
        FanVault never views, handles, or stores raw payment card numbers, CVVs, or expiration dates on our application servers. Payment data is encrypted directly in your browser and transmitted straight to Stripe’s secure infrastructure. We receive only a tokenized payment reference (e.g., <code>cs_live_...</code>) and payment confirmation status.
      </p>
      <p class="text-xs sm:text-sm text-[#475569]">
        Creator payouts are governed by Stripe Connect terms. Creators maintain their own payout accounts with Stripe and are subject to Stripe’s identity verification procedures.
      </p>
    </section>

    <!-- Section 4 -->
    <section id="subprocessors" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        4. Sub-processors & Infrastructure Partners
      </h2>
      <p class="mb-4">
        We partner with world-class cloud service providers to maintain the security, redundancy, and speed of our services:
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm">
        <div class="border border-[#e7e5df] rounded-xl p-3.5 bg-[#faf9f5]">
          <strong class="block text-[#0f172a] font-bold">Stripe, Inc.</strong>
          <span class="text-[#64748b]">Payment processing, fraud mitigation & Stripe Connect creator payout routing.</span>
        </div>
        <div class="border border-[#e7e5df] rounded-xl p-3.5 bg-[#faf9f5]">
          <strong class="block text-[#0f172a] font-bold">Amazon Web Services (AWS)</strong>
          <span class="text-[#64748b]">Encrypted cloud computing hosting, managed database storage, and automated backups.</span>
        </div>
        <div class="border border-[#e7e5df] rounded-xl p-3.5 bg-[#faf9f5]">
          <strong class="block text-[#0f172a] font-bold">SendlyAI / Amazon SES</strong>
          <span class="text-[#64748b]">Transactional email infrastructure, encrypted SMTP delivery, and deliverability monitoring.</span>
        </div>
        <div class="border border-[#e7e5df] rounded-xl p-3.5 bg-[#faf9f5]">
          <strong class="block text-[#0f172a] font-bold">Cloudflare / Caddy</strong>
          <span class="text-[#64748b]">Automated TLS 1.3 encryption, DDoS mitigation, and edge traffic routing.</span>
        </div>
      </div>
    </section>

    <!-- Section 5 -->
    <section id="your-rights" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        5. Your Rights (GDPR & CCPA/CPRA Compliance)
      </h2>
      <p class="mb-4">
        Regardless of your geographic location, FanVault extends comprehensive privacy rights to all users:
      </p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs sm:text-sm">
        <div class="border border-[#e7e5df] rounded-2xl p-4">
          <strong class="block text-[#047857] font-bold mb-1">Right to Access & Portability</strong>
          <p class="text-[#475569]">You may request a machine-readable export of all letters, transactions, and account records associated with your email address.</p>
        </div>
        <div class="border border-[#e7e5df] rounded-2xl p-4">
          <strong class="block text-[#047857] font-bold mb-1">Right to Erasure ("Right to be Forgotten")</strong>
          <p class="text-[#475569]">You may request permanent deletion of your letters, author identity, or creator vault. See our <a href="{{ route('data-deletion') }}" class="text-[#047857] underline font-bold">Data Deletion Procedure</a>.</p>
        </div>
        <div class="border border-[#e7e5df] rounded-2xl p-4">
          <strong class="block text-[#047857] font-bold mb-1">Right to Rectification</strong>
          <p class="text-[#475569]">You may update your channel handle, connected platforms, milestone details, or correct inaccuracies anytime.</p>
        </div>
        <div class="border border-[#e7e5df] rounded-2xl p-4">
          <strong class="block text-[#047857] font-bold mb-1">Right to Object & Restrict</strong>
          <p class="text-[#475569]">You may withdraw consent for optional marketing communications or restrict processing of specific records.</p>
        </div>
      </div>
      <p class="mt-4 text-xs sm:text-sm text-[#475569]">
        To exercise any of these rights, simply email our Data Privacy Officer at <a href="mailto:privacy@getfanvault.com" class="text-[#047857] font-bold hover:underline">privacy@getfanvault.com</a>. We respond to verified requests within 48 business hours with zero processing fees.
      </p>
    </section>

    <!-- Section 6 -->
    <section id="security-retention" class="bg-white border border-[#e7e5df] rounded-3xl p-6 sm:p-9 shadow-xs">
      <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#0f172a] mb-4">
        6. Security Architecture & Retention
      </h2>
      <p class="mb-4">
        We employ technical safeguards including AES-256 database encryption at rest, mandatory TLS 1.3 transit encryption, cryptographic token hashing (SHA-256) for fan letter claim links, and short-lived 30-minute magic login sessions.
      </p>
      <p class="text-xs sm:text-sm text-[#475569] mb-4">
        For detailed lifecycle schedules regarding active letters, tax ledgers, and log rotation, please review our full <a href="{{ route('data-retention') }}" class="text-[#047857] font-bold underline">Data Retention Policy</a>.
      </p>
    </section>

    <!-- Contact & Legal Authority -->
    <section class="bg-gradient-to-br from-[#064e3b] to-[#047857] text-white rounded-3xl p-6 sm:p-9 shadow-md">
      <h3 class="font-serif text-xl sm:text-2xl font-bold mb-2">Questions or Concerns?</h3>
      <p class="text-xs sm:text-sm text-emerald-100 leading-relaxed mb-4">
        If you have questions regarding this Privacy Policy or wish to lodge a formal data inquiry, please contact our Legal & Compliance Team:
      </p>
      <div class="flex flex-wrap items-center gap-4 text-xs font-mono">
        <div class="bg-white/10 px-3.5 py-2 rounded-xl backdrop-blur-xs">
          Email: <a href="mailto:privacy@getfanvault.com" class="text-white font-bold underline">privacy@getfanvault.com</a>
        </div>
        <div class="bg-white/10 px-3.5 py-2 rounded-xl backdrop-blur-xs">
          Legal: <a href="mailto:legal@getfanvault.com" class="text-white font-bold underline">legal@getfanvault.com</a>
        </div>
      </div>
    </section>

  </article>

  <!-- Related Policies Bar -->
  <footer class="mt-12 pt-8 border-t border-[#e7e5df] flex flex-wrap items-center justify-between gap-4 text-xs text-[#64748b]">
    <span class="font-medium">Related Legal Documentation:</span>
    <div class="flex flex-wrap gap-4 font-semibold text-[#047857]">
      <a href="{{ route('terms') }}" class="hover:underline">Terms of Service</a>
      <span>·</span>
      <a href="{{ route('data-retention') }}" class="hover:underline">Data Retention</a>
      <span>·</span>
      <a href="{{ route('data-deletion') }}" class="hover:underline">Deletion Procedure</a>
      <span>·</span>
      <a href="{{ route('security') }}" class="hover:underline">Security Disclosure</a>
    </div>
  </footer>
</div>
