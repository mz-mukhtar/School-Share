# Security Policy

## Supported Versions

| Version | Supported |
|---|---|
| Latest (main branch) | ✅ Yes |
| Older tagged releases | ⚠️ Best-effort |

We recommend always running the latest version of SchoolShare.

---

## Reporting a Vulnerability

**Please do NOT report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability, please report it privately so we can
fix it before it is publicly known.

### Contact

**Mahi Zeki Mukhtar** — Lead Developer & Security Contact
- 📧 **Email**: mahizeki037@gmail.com *(preferred)*
- 📞 **Phone**: +251 992 194 042
- 🌐 **Website**: https://ethionext.com.et

### What to Include in Your Report

Please provide as much detail as possible:

1. **Description** — What is the vulnerability?
2. **Severity** — How serious do you think it is? (Critical / High / Medium / Low)
3. **Affected Component** — Which part of the app is affected?
4. **Steps to Reproduce** — Detailed steps to trigger the vulnerability
5. **Proof of Concept** — Code, screenshot, or video demonstrating the issue
6. **Suggested Fix** — If you have one (optional but very helpful)
7. **Your Contact Info** — So we can credit you (optional)

### What Happens Next

1. **Acknowledgement** — We will acknowledge your report within **48 hours**
2. **Investigation** — We will investigate and assess the severity
3. **Fix** — We will develop and test a patch
4. **Disclosure** — We will release the fix and, with your permission,
   credit you in the release notes
5. **Timeline** — We aim to resolve critical issues within **7 days**,
   and other issues within **30 days**

---

## Scope

### In Scope (please report these)

- Authentication bypass or privilege escalation
- SQL injection
- Remote code execution (RCE)
- Cross-site scripting (XSS) with real impact
- Cross-site request forgery (CSRF) bypasses
- Path traversal / arbitrary file read
- Insecure direct object references (users accessing other users' files)
- Storage quota bypass
- Branding license system bypass

### Out of Scope

- Issues that require physical access to the server
- Social engineering attacks
- Spam or abuse of the platform
- Missing security headers that have no practical exploit
- Rate limiting on non-sensitive endpoints
- Issues in third-party libraries (report those to the library maintainer)

---

## Responsible Disclosure

We believe in responsible disclosure and will:

- Work with you to understand and fix the issue
- Not pursue legal action against good-faith security researchers
- Credit you in our security advisory (if you wish)
- Aim to fix critical vulnerabilities before public disclosure

Please give us a reasonable time to fix the issue before disclosing publicly.

---

## Known Security Measures

SchoolShare implements the following security measures:

- **CSRF protection** on all forms (Laravel's built-in)
- **File type validation** — server-side MIME type checking
- **Path traversal prevention** — filenames are sanitized before storage
- **Authentication** — all project/file routes require login
- **Project access control** — private projects are never accessible without permission
- **SQL injection prevention** — all queries use Laravel's Eloquent ORM (parameterized)
- **XSS prevention** — all output is escaped by default in Blade templates
- **Rate limiting** — upload and login endpoints are rate-limited
- **Storage quota** — enforced server-side, not just client-side

---

*SchoolShare by EthioNext — ethionext.com.et*
*Developed by Mahi Zeki Mukhtar — mahizeki037@gmail.com*
