# Contributing to SchoolShare

Thank you for your interest in contributing to SchoolShare! 🎉

SchoolShare is an open project by EthioNext, and we welcome contributions from
developers, designers, students, and educators around the world.

Please read this guide before submitting issues or pull requests.

---

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Ways to Contribute](#ways-to-contribute)
- [Getting Started](#getting-started)
- [Branch Naming](#branch-naming)
- [Commit Messages](#commit-messages)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)
- [Contact](#contact)

---

## Code of Conduct

By participating in this project, you agree to abide by the
[Code of Conduct](CODE_OF_CONDUCT.md). Please read it before contributing.

---

## Ways to Contribute

- 🐛 **Report bugs** — open a GitHub issue
- 💡 **Suggest features** — open a GitHub issue with the `enhancement` label
- 📖 **Improve documentation** — fix typos, add examples, improve clarity
- 🎨 **Design** — improve the UI, suggest better UX flows
- 🌐 **Translate** — help translate the UI to other languages
- 🔧 **Fix bugs** — pick an open issue and submit a PR
- ✨ **Build features** — check the [ROADMAP.md](ROADMAP.md) for planned features

---

## Getting Started

### 1. Fork the Repository

Click "Fork" on [github.com/mz-mukhtar/School-Share](https://github.com/mz-mukhtar/School-Share)

### 2. Clone Your Fork

```bash
git clone https://github.com/YOUR_USERNAME/School-Share.git
cd School-Share
```

### 3. Set Up the Project

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure database in .env, then run migrations
php artisan migrate

# Create storage link
php artisan storage:link

# Start dev server
php artisan serve
```

### 4. Create a Branch

```bash
git checkout -b feature/your-feature-name
```

---

## Branch Naming

Use the following prefixes:

| Prefix | Use for |
|---|---|
| `feature/` | New features |
| `fix/` | Bug fixes |
| `docs/` | Documentation updates |
| `refactor/` | Code refactoring (no behavior change) |
| `style/` | Formatting, whitespace, UI tweaks |
| `chore/` | Build system, dependencies, CI |

**Examples:**
```
feature/in-browser-pdf-viewer
fix/storage-quota-not-updating
docs/improve-self-hosting-guide
```

---

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>: <short description>

[optional body]

[optional footer]
```

**Types:**

| Type | When to use |
|---|---|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Formatting (no logic change) |
| `refactor` | Code restructure (no behavior change) |
| `perf` | Performance improvement |
| `test` | Adding or updating tests |
| `chore` | Build, deps, config changes |

**Examples:**
```
feat: add PDF.js viewer for checkpoint files
fix: storage quota not updating after file delete
docs: add cPanel deployment troubleshooting steps
perf: add database index on checkpoint_files.content_hash
```

---

## Pull Request Process

1. **Ensure your branch is up to date** with `main`:
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Test your changes** thoroughly before submitting.

3. **Update documentation** if your PR changes behavior or adds features.

4. **Update CHANGELOG.md** — add an entry under `[Unreleased]`.

5. **Open the Pull Request** with:
   - A clear title (following commit message format)
   - A description of what was changed and why
   - Screenshots if the PR affects the UI
   - Reference to any related issues: `Closes #123`

6. **Wait for review** — a maintainer will review within a few days.

7. **Address feedback** promptly and politely.

---

## Coding Standards

### PHP / Laravel

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- Use Laravel conventions: resourceful controllers, Eloquent models, service classes
- Keep controllers thin — business logic belongs in `app/Services/`
- Always validate requests using Form Request classes (`app/Http/Requests/`)
- Use `select()` in queries — don't `SELECT *` on large tables
- Always use eager loading (`with()`) to prevent N+1 queries

### Blade Templates

- Keep Blade templates simple — avoid PHP logic in views
- Use components and partials for reusable UI elements
- All pages must include the `layouts.app` or `layouts.guest` layout

### JavaScript (Alpine.js)

- Keep Alpine.js components small and focused
- Avoid heavy JavaScript — use server-side rendering where possible
- Don't introduce a build step (no Webpack/Vite) unless discussed

### CSS (Bootstrap 5)

- Use Bootstrap utility classes where possible
- Custom CSS goes in `public/css/app.css`
- Avoid inline styles

### Security

- Never trust user input — always validate, sanitize, and escape
- Never store user files outside `storage/app/uploads/`
- Always check file MIME type server-side (not just client-side)
- Use Laravel's built-in CSRF protection (always use `@csrf` in forms)
- File download routes must check project access permissions

---

## Reporting Bugs

Use the GitHub Issues tab with the `bug` label.

**Include:**
- PHP and Laravel version
- Steps to reproduce the bug
- Expected behavior
- Actual behavior
- Error message or stack trace (if any)
- Browser/OS (for UI bugs)

For **security vulnerabilities**, please do NOT open a public issue.
See [SECURITY.md](SECURITY.md) for responsible disclosure.

---

## Suggesting Features

Use the GitHub Issues tab with the `enhancement` label.

**Include:**
- What problem does this solve?
- Who benefits from this feature?
- Describe the ideal user experience
- Any alternative approaches you considered

Large features should be discussed in an issue before starting a PR.

---

## Contact

**Mahi Zeki Mukhtar** — Project maintainer
- 📧 mahizeki037@gmail.com
- 📞 +251 992 194 042
- 🌐 https://ethionext.com.et
- 🐙 https://github.com/mz-mukhtar

---

Thank you for helping make SchoolShare better for students everywhere! 🌍
