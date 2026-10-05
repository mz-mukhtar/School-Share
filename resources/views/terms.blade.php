<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SchoolShare Terms of Service — Rules, limits, and your rights when using SchoolShare by EthioNext.">
    <title>Terms of Service · SchoolShare by EthioNext</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --ss-primary:  #2563eb;
            --ss-dark:     #0a0f1e;
            --ss-dark-2:   #111827;
            --ss-dark-3:   #1e293b;
            --ss-text:     #f1f5f9;
            --ss-muted:    #94a3b8;
            --ss-border:   rgba(255,255,255,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--ss-dark); color: var(--ss-text);
            line-height: 1.7;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--ss-dark); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }

        /* Topbar */
        .ss-topbar {
            background: rgba(10,15,30,.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--ss-border);
            padding: .9rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            position: sticky; top: 0; z-index: 100;
        }
        .ss-brand { font-weight: 900; font-size: 1.2rem; color: #fff; text-decoration: none; }
        .ss-brand .accent { color: var(--ss-primary); }
        .ss-back {
            margin-left: auto; display: flex; align-items: center; gap: .4rem;
            color: var(--ss-muted); text-decoration: none; font-size: .875rem;
            transition: color .15s;
        }
        .ss-back:hover { color: var(--ss-primary); }

        /* Page layout */
        .terms-wrap {
            max-width: 760px; margin: 0 auto;
            padding: 4rem 1.5rem 6rem;
        }
        .terms-hero {
            margin-bottom: 3rem; padding-bottom: 2rem;
            border-bottom: 1px solid var(--ss-border);
        }
        .terms-label {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .1em; color: var(--ss-primary); margin-bottom: .6rem;
        }
        .terms-hero h1 {
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            font-weight: 900; letter-spacing: -.03em; margin-bottom: .75rem;
        }
        .terms-meta {
            font-size: .82rem; color: var(--ss-muted);
            display: flex; gap: 1.5rem; flex-wrap: wrap; align-items: center;
        }
        .terms-meta span { display: flex; align-items: center; gap: .35rem; }

        /* TOC */
        .toc-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 14px; padding: 1.5rem;
            margin-bottom: 3rem;
        }
        .toc-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--ss-muted); margin-bottom: 1rem; }
        .toc-list { list-style: none; counter-reset: toc-count; }
        .toc-list li { counter-increment: toc-count; margin-bottom: .4rem; }
        .toc-list a {
            display: flex; align-items: center; gap: .6rem;
            color: var(--ss-muted); text-decoration: none;
            font-size: .875rem; padding: .3rem .5rem; border-radius: 7px;
            transition: color .15s, background .15s;
        }
        .toc-list a::before {
            content: counter(toc-count, decimal-leading-zero);
            font-size: .7rem; font-weight: 700; color: var(--ss-primary);
            min-width: 22px;
        }
        .toc-list a:hover { color: var(--ss-text); background: rgba(255,255,255,.05); }

        /* Sections */
        .terms-section { margin-bottom: 3rem; }
        .terms-section h2 {
            font-size: 1.3rem; font-weight: 800; margin-bottom: 1rem;
            padding-bottom: .6rem; border-bottom: 1px solid var(--ss-border);
            display: flex; align-items: center; gap: .55rem;
        }
        .terms-section h2 i { color: var(--ss-primary); font-size: 1.1rem; }
        .terms-section h3 { font-size: 1rem; font-weight: 700; margin: 1.25rem 0 .5rem; }
        .terms-section p { color: var(--ss-muted); margin-bottom: .85rem; font-size: .9rem; }
        .terms-section ul {
            color: var(--ss-muted); font-size: .9rem;
            padding-left: 0; list-style: none; margin-bottom: .85rem;
        }
        .terms-section ul li {
            padding: .3rem 0 .3rem 1.4rem; position: relative;
        }
        .terms-section ul li::before {
            content: '·'; color: var(--ss-primary);
            position: absolute; left: 0; font-size: 1.2rem; line-height: 1.4;
        }

        /* Callout boxes */
        .callout {
            border-radius: 10px; padding: 1rem 1.25rem;
            margin: 1.25rem 0; font-size: .875rem;
            display: flex; gap: .75rem; align-items: flex-start;
        }
        .callout i { font-size: 1.1rem; flex-shrink: 0; margin-top: .1rem; }
        .callout-info { background: rgba(37,99,235,.1); border: 1px solid rgba(37,99,235,.25); color: #93c5fd; }
        .callout-warn { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.25); color: #fcd34d; }
        .callout-danger { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }

        /* Contact box */
        .contact-card {
            background: var(--ss-dark-2);
            border: 1px solid var(--ss-border);
            border-radius: 14px; padding: 1.5rem;
            margin-top: 1.5rem;
        }
        .contact-row {
            display: flex; align-items: center; gap: .75rem;
            padding: .5rem 0; border-bottom: 1px solid var(--ss-border);
            font-size: .875rem; color: var(--ss-muted);
        }
        .contact-row:last-child { border-bottom: none; padding-bottom: 0; }
        .contact-row i { color: var(--ss-primary); width: 20px; text-align: center; }
        .contact-row a { color: var(--ss-muted); text-decoration: none; }
        .contact-row a:hover { color: var(--ss-primary); }

        /* Back to top */
        .back-top {
            display: inline-flex; align-items: center; gap: .4rem;
            color: var(--ss-muted); text-decoration: none; font-size: .82rem;
            margin-top: 2rem; transition: color .15s;
        }
        .back-top:hover { color: var(--ss-primary); }
    </style>
</head>
<body>

{{-- Topbar --}}
<header class="ss-topbar">
    <a href="/" class="ss-brand"><span class="accent">School</span>Share</a>
    <a href="/" class="ss-back">
        <i class="bi bi-arrow-left"></i> Back to home
    </a>
</header>

<main class="terms-wrap">
    {{-- Hero --}}
    <div class="terms-hero">
        <div class="terms-label">Legal</div>
        <h1>Terms of Service</h1>
        <div class="terms-meta">
            <span><i class="bi bi-calendar3"></i> Effective: October 4, 2026</span>
            <span><i class="bi bi-building"></i> SchoolShare by EthioNext</span>
            <span><i class="bi bi-geo-alt"></i> Ethiopia</span>
        </div>
    </div>

    <div class="callout callout-info">
        <i class="bi bi-info-circle-fill"></i>
        <div>
            <strong>Plain-English first.</strong> We've written these terms to be readable.
            Each section starts with a short, plain-English summary in <em>italics</em>, then the formal text.
            If anything is unclear, email us — we'll explain it.
        </div>
    </div>

    {{-- Table of Contents --}}
    <div class="toc-card">
        <div class="toc-title">Table of Contents</div>
        <ol class="toc-list">
            <li><a href="#s1">Who we are & what this is</a></li>
            <li><a href="#s2">Your account</a></li>
            <li><a href="#s3">What you can upload</a></li>
            <li><a href="#s4">Storage limits & quotas</a></li>
            <li><a href="#s5">Your content — ownership & privacy</a></li>
            <li><a href="#s6">What we may do with your content</a></li>
            <li><a href="#s7">What you must not do</a></li>
            <li><a href="#s8">Plans & payments</a></li>
            <li><a href="#s9">Source licensing & branding</a></li>
            <li><a href="#s10">Service availability</a></li>
            <li><a href="#s11">Termination</a></li>
            <li><a href="#s12">Liability</a></li>
            <li><a href="#s13">Changes to these terms</a></li>
            <li><a href="#s14">Contact us</a></li>
        </ol>
    </div>

    {{-- SECTIONS --}}

    <div class="terms-section" id="s1">
        <h2><i class="bi bi-building"></i> 1. Who we are &amp; what this is</h2>
        <p><em>SchoolShare is a file version-control platform built for students by EthioNext. By using it, you agree to these terms.</em></p>
        <p>
            SchoolShare ("the Service") is operated by <strong>EthioNext</strong>, developed by Mahi Zeki Mukhtar,
            Ethiopia. The Service allows students and individuals to store, organize, version, and access files from any device —
            similar to how software developers use Git, but without any command-line interface.
        </p>
        <p>
            By creating an account or using the Service, you ("User", "you") agree to be bound by these Terms of Service.
            If you do not agree, you must not use SchoolShare.
        </p>
    </div>

    <div class="terms-section" id="s2">
        <h2><i class="bi bi-person-check"></i> 2. Your account</h2>
        <p><em>You must register with accurate info. Keep your password safe. You're responsible for anything that happens under your account.</em></p>
        <ul>
            <li>You must provide an accurate name and email address when registering.</li>
            <li>You must be at least 13 years old to create an account. If you are under 18, you confirm that a parent or guardian has reviewed and agreed to these terms on your behalf.</li>
            <li>You are responsible for keeping your password confidential. Do not share it with anyone.</li>
            <li>If you believe your account has been compromised, contact us immediately at <a href="mailto:mahizeki037@gmail.com">mahizeki037@gmail.com</a>.</li>
            <li>Each person may create one (1) account. Creating multiple accounts to circumvent limits is prohibited.</li>
        </ul>
    </div>

    <div class="terms-section" id="s3">
        <h2><i class="bi bi-cloud-upload"></i> 3. What you can upload</h2>
        <p><em>You can upload almost any kind of file — documents, presentations, images, code. Just keep it legal and school-appropriate.</em></p>
        <p>SchoolShare is designed for educational and personal work. You may upload:</p>
        <ul>
            <li>Documents (Word, PDF, text files, Markdown)</li>
            <li>Presentations (PowerPoint, Google Slides exports)</li>
            <li>Spreadsheets (Excel, CSV)</li>
            <li>Images (JPG, PNG, SVG, GIF, WebP)</li>
            <li>Audio &amp; video files</li>
            <li>Source code and programming files</li>
            <li>Any other file type within the size limit</li>
        </ul>
        <div class="callout callout-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>You must <strong>NOT</strong> upload: malware, exploits, pirated commercial software, content that violates copyright without permission, adult content, or any material illegal under Ethiopian law or the laws of your country.</div>
        </div>
    </div>

    <div class="terms-section" id="s4">
        <h2><i class="bi bi-hdd"></i> 4. Storage limits &amp; quotas</h2>
        <p><em>Free users get 3 GB total and can store up to 100 MB per file across 15 projects. These limits may change in the future.</em></p>
        <ul>
            <li><strong>Max file size:</strong> 100 MB per individual file.</li>
            <li><strong>Total storage:</strong> 3 GB per user account (free plan).</li>
            <li><strong>Max projects:</strong> 15 active projects per account (free plan).</li>
            <li>If you exceed your storage quota, uploads will be blocked until you free up space.</li>
            <li>We reserve the right to adjust these limits with 30 days' notice to active users.</li>
        </ul>
        <div class="callout callout-warn">
            <i class="bi bi-exclamation-circle-fill"></i>
            <div>Need more storage or projects? Contact us at <a href="mailto:mahizeki037@gmail.com">mahizeki037@gmail.com</a> to inquire about Pro or White-label plans.</div>
        </div>
    </div>

    <div class="terms-section" id="s5">
        <h2><i class="bi bi-shield-lock"></i> 5. Your content — ownership &amp; privacy</h2>
        <p><em>You retain ownership of your content. The current application protects private projects with owner/collaborator access checks, but this page is not a substitute for a host's privacy notice or data-processing terms.</em></p>
        <ul>
            <li><strong>You own your content.</strong> Uploading files to SchoolShare does not transfer any intellectual property rights to EthioNext.</li>
            <li>Private projects are available through the application only to their owner and current collaborators.</li>
            <li>Public projects are available to authenticated, OTP-verified users. The current routes do not provide guest or search-engine access to project pages.</li>
            <li>We do not sell your content, share it with advertisers, or use it to train AI models.</li>
            <li>We may access files only when necessary to provide technical support, if required by law, or to investigate a reported Terms violation.</li>
        </ul>
    </div>

    <div class="terms-section" id="s6">
        <h2><i class="bi bi-server"></i> 6. What we may do with your content</h2>
        <p><em>A deployment operator may need to store, back up, and transmit files to operate the service. Obtain the operator's privacy and retention terms before using a hosted deployment.</em></p>
        <p>
            By uploading content, you grant the deployment operator a limited, non-exclusive, royalty-free license to store, replicate
            (for backup), and transmit your content solely for the purpose of providing the SchoolShare service to you.
            Deletion and retention must follow the deployment operator's published policy and backup procedures.
        </p>
        <p>The current application uses external browser assets and may direct Office-document previews to Google Docs Viewer. Review the deployment operator's privacy notice before uploading sensitive content.</p>
    </div>

    <div class="terms-section" id="s7">
        <h2><i class="bi bi-ban"></i> 7. What you must not do</h2>
        <p><em>Don't abuse the service, don't harm other users, and don't break the law.</em></p>
        <p>You agree not to:</p>
        <ul>
            <li>Upload or share illegal content of any kind.</li>
            <li>Attempt to gain unauthorized access to other users' accounts or projects.</li>
            <li>Use the service to distribute malware, phishing content, or spam.</li>
            <li>Attempt to reverse-engineer, scrape, or stress-test the platform without written permission.</li>
            <li>Impersonate another person or misrepresent your affiliation.</li>
            <li>Use the service to harass, bully, or threaten other users.</li>
            <li>Create multiple accounts to circumvent storage or project limits.</li>
            <li>Use automation (bots, scripts) to bulk-upload or bulk-download without prior written approval.</li>
        </ul>
        <p>
            Violations may result in immediate account suspension without notice.
        </p>
    </div>

    <div class="terms-section" id="s8">
        <h2><i class="bi bi-credit-card"></i> 8. Plans &amp; payments</h2>
        <p><em>The free plan is free, always. Paid plans are negotiated directly with the developer.</em></p>
        <ul>
            <li>The <strong>Student (Free)</strong> plan requires no payment and has no time limit.</li>
            <li>The <strong>Pro</strong> and <strong>White-label</strong> plans are priced individually — contact us for a quote.</li>
            <li>All payments are handled directly between the buyer and EthioNext. No third-party payment processor stores your card data on our behalf.</li>
            <li>Refunds for paid plans will be handled on a case-by-case basis. Contact <a href="mailto:mahizeki037@gmail.com">mahizeki037@gmail.com</a>.</li>
            <li>We reserve the right to change pricing for paid plans with 30 days' advance notice.</li>
        </ul>
    </div>

    <div class="terms-section" id="s9">
        <h2><i class="bi bi-github"></i> 9. Source licensing &amp; branding</h2>
        <p><em>The source is distributed under the terms in the repository license. It is not described here as OSI open source because the current SchoolShare Community License contains non-commercial restrictions.</em></p>
        <ul>
            <li>Read the repository <a href="{{ config('schoolshare.branding.github_url') }}/blob/main/LICENSE.md" target="_blank">LICENSE.md</a> before using, distributing, or self-hosting the source. The copyright holder must resolve the repository's SCL-versus-Composer-MIT metadata conflict before distribution.</li>
            <li>You may fork, modify, and self-host SchoolShare for personal or educational use for free, provided the EthioNext branding and credit remain visible in the UI.</li>
            <li>Removing or replacing EthioNext branding requires the rights specified by the repository license. The current white-label configuration check only requires an <code>SS-</code>-prefixed value; it does not validate a commercial entitlement.</li>
            <li>Selling the software or offering it as a commercial product without a license is prohibited.</li>
        </ul>
    </div>

    <div class="terms-section" id="s10">
        <h2><i class="bi bi-cloud-check"></i> 10. Service availability</h2>
        <p><em>We aim for high uptime, but we can't guarantee 100%. We're not responsible for data loss caused by factors outside our control.</em></p>
        <ul>
            <li>We aim to maintain the Service available 99% of the time but do not provide a formal SLA for the free plan.</li>
            <li>Scheduled maintenance will be announced in advance where possible.</li>
            <li>We strongly recommend that you keep local copies of important files. SchoolShare is a version-control assistant, not a sole backup solution.</li>
            <li>We are not liable for data loss caused by server failures, natural disasters, cyberattacks, or other force-majeure events.</li>
        </ul>
    </div>

    <div class="terms-section" id="s11">
        <h2><i class="bi bi-door-open"></i> 11. Termination</h2>
        <p><em>You can delete your account anytime. We can suspend or close accounts that violate these terms.</em></p>
        <ul>
            <li>You may delete your account at any time from your profile settings. Deletion is permanent and irreversible.</li>
            <li>We may suspend or terminate accounts that violate these Terms, with or without prior notice depending on severity.</li>
            <li>If your account is terminated by us for a violation, you may not create a new account without written permission.</li>
            <li>Upon account deletion, your files will be permanently removed from our servers within 30 days.</li>
        </ul>
    </div>

    <div class="terms-section" id="s12">
        <h2><i class="bi bi-shield-exclamation"></i> 12. Liability</h2>
        <p><em>SchoolShare is provided "as is". We are not liable for damages arising from your use of the service.</em></p>
        <p>
            To the maximum extent permitted by applicable law, EthioNext and its developer shall not be liable for
            any indirect, incidental, special, or consequential damages including but not limited to loss of data,
            loss of revenue, or loss of business arising from your use of — or inability to use — SchoolShare.
        </p>
        <p>
            Total liability in any circumstance is limited to the amount you paid for the Service in the 3 months
            preceding the claim (or zero for free-plan users).
        </p>
    </div>

    <div class="terms-section" id="s13">
        <h2><i class="bi bi-arrow-repeat"></i> 13. Changes to these terms</h2>
        <p><em>If we update these terms significantly, we'll tell you by email or by showing a notice when you log in. Continued use means you accept the new terms.</em></p>
        <ul>
            <li>We reserve the right to update these Terms at any time.</li>
            <li>For material changes, we will notify you via the email on your account at least 14 days before the change takes effect.</li>
            <li>The "Effective" date at the top of this page will be updated for every revision.</li>
            <li>Continued use of the Service after changes take effect constitutes acceptance of the new Terms.</li>
        </ul>
    </div>

    <div class="terms-section" id="s14">
        <h2><i class="bi bi-envelope-heart"></i> 14. Contact us</h2>
        <p><em>Questions? We're real people — just email or call.</em></p>
        <p>If you have any questions, concerns, or requests regarding these Terms of Service, please contact:</p>
        <div class="contact-card">
            <div class="contact-row">
                <i class="bi bi-person-fill"></i>
                <span>Mahi Zeki Mukhtar — Developer &amp; Founder, EthioNext</span>
            </div>
            <div class="contact-row">
                <i class="bi bi-envelope-fill"></i>
                <a href="mailto:mahizeki037@gmail.com">mahizeki037@gmail.com</a>
            </div>
            <div class="contact-row">
                <i class="bi bi-telephone-fill"></i>
                <a href="tel:+251992194042">+251 992 194 042</a>
            </div>
            <div class="contact-row">
                <i class="bi bi-globe2"></i>
                <a href="https://ethionext.com.et" target="_blank">ethionext.com.et</a>
            </div>
            <div class="contact-row">
                <i class="bi bi-github"></i>
                <a href="{{ config('schoolshare.branding.github_url') }}" target="_blank">github.com/mz-mukhtar/School-Share</a>
            </div>
        </div>
    </div>

    <a href="#" class="back-top"><i class="bi bi-arrow-up-circle"></i> Back to top</a>
</main>

<footer style="background:var(--ss-dark-2);border-top:1px solid var(--ss-border);padding:1.5rem;text-align:center;">
    <p style="font-size:.78rem;color:var(--ss-muted);">
        © {{ date('Y') }} EthioNext · SchoolShare · All rights reserved &nbsp;·&nbsp;
        <a href="/" style="color:var(--ss-muted);text-decoration:none;">Home</a>
    </p>
</footer>

</body>
</html>
