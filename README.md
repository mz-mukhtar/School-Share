# SchoolShare — Git for Everyone

<p align="center">
  <img src="public/images/logo.png" alt="SchoolShare by EthioNext" width="200"/>
</p>

<p align="center">
  <strong>Version control for students, teachers, and creators — no terminal required.</strong><br/>
  Upload your work. Save checkpoints. Download anywhere. Collaborate with teammates.
</p>

<p align="center">
  <a href="https://ethionext.com.et">🌐 Live App</a> ·
  <a href="https://github.com/mz-mukhtar/School-Share">📦 GitHub</a> ·
  <a href="#self-hosting">🖥️ Self-Host</a> ·
  <a href="LICENSE.md">📄 License</a>
</p>

---

## ✨ What is SchoolShare?

SchoolShare is a **simplified version control system** inspired by Git, designed for high school students and anyone who needs to track their work — essays, presentations, PDFs, images, code, and more.

No command line. No technical knowledge needed. Just:

1. **Upload** your files and write a short checkpoint message ("Added chapter 2")
2. **Access** your work from any device — school computer, home laptop, phone
3. **Track history** — see every version you've ever saved, and restore any of them
4. **Collaborate** — invite teammates to your project

> SchoolShare is developed and maintained by **EthioNext** (ethionext.com.et)

---

## 🚀 Features

| Feature | Description |
|---|---|
| 📁 **Projects** | Create a project (like a repository) for any subject |
| 📤 **Checkpoints** | Upload files + write a message = a saved snapshot |
| 📥 **Download Anywhere** | Download your current or any past version as a ZIP |
| 🕒 **Version History** | Visual timeline of all checkpoints |
| 🔀 **Restore** | Go back to any previous checkpoint instantly |
| 👁️ **In-Browser Viewer** | View files without downloading (PDFs, images, docs, code) |
| ✏️ **In-Browser Editor** | Edit text files directly in the browser |
| 🔍 **Diff View** | See exactly what changed between versions |
| 👥 **Collaboration** | Invite classmates/teammates to your project |
| 🔒 **Private Projects** | Keep personal work private |
| 📊 **Storage Tracker** | See how much of your 1 GB quota you've used |
| 📂 **Works With Any File** | .docx, .pptx, .pdf, .png, .mp4, .py, .html — everything |

---

## 🗂️ Supported File Types

| Type | Extensions | View in Browser |
|---|---|---|
| Documents | .docx, .odt, .rtf | ✅ Google Docs Viewer |
| Presentations | .pptx, .odp | ✅ Google Docs Viewer |
| Spreadsheets | .xlsx, .ods | ✅ Google Docs Viewer |
| PDF | .pdf | ✅ PDF.js viewer |
| Images | .jpg, .png, .gif, .svg, .webp | ✅ Native |
| Video | .mp4, .webm | ✅ HTML5 player |
| Audio | .mp3, .wav | ✅ HTML5 player |
| Code/Text | .txt, .md, .py, .js, .html, .php … | ✅ CodeMirror (editable) |
| Other | .zip, .exe, etc. | ⬇️ Download to view |

---

## 🛠️ Tech Stack

- **Backend**: PHP 8.1+ / Laravel 11
- **Database**: MySQL 8
- **File Storage**: Local filesystem
- **Frontend**: Laravel Blade + Bootstrap 5 + Alpine.js
- **Code Editor**: CodeMirror 6
- **PDF Viewer**: PDF.js (Mozilla)
- **Office Viewer**: Google Docs Viewer
- **Auth**: Laravel Breeze

---

## ⚙️ Requirements

- PHP 8.1 or higher (8.2+ recommended)
- Composer 2.x
- MySQL 5.7+ or MariaDB 10.3+
- PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `zip`
- Web server: Apache (recommended for cPanel) or Nginx

---

## 🖥️ Quick Start (Local Development)

```bash
# 1. Clone the repository
git clone https://github.com/mz-mukhtar/School-Share.git
cd School-Share

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your database in .env
# DB_DATABASE=schoolshare
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# 6. Run database migrations
php artisan migrate

# 7. Create storage symlink
php artisan storage:link

# 8. Start development server
php artisan serve
```

Visit `http://localhost:8000` — done! ✅

---

## 🖥️ cPanel Deployment (Production)

See the full guide in [SELF_HOSTING.md](SELF_HOSTING.md).

**Quick summary:**
1. Upload files to your cPanel hosting
2. Set document root → `public/` folder
3. Create MySQL database via cPanel MySQL Wizard
4. Configure `.env` with production values
5. Run `composer install --no-dev` via SSH/Terminal
6. Run `php artisan migrate --force`
7. Set folder permissions: `storage/` and `bootstrap/cache/` → 755

---

## 📁 Project Structure

```
schoolshare/
├── app/
│   ├── Http/Controllers/    # Request handling
│   ├── Http/Middleware/     # Auth, branding, quota checks
│   ├── Models/              # Eloquent models
│   └── Services/            # Business logic
├── database/migrations/     # Database schema
├── resources/views/         # Blade templates (HTML)
├── routes/web.php           # All URL routes
├── storage/app/uploads/     # Student files (auto-created)
├── config/schoolshare.php   # App configuration
├── docs/                    # Technical documentation
└── public/                  # Web root (point server here)
```

---

## 🔐 Limits (Free Plan)

| Resource | Limit |
|---|---|
| Projects | 3 |
| Storage per user | 1 GB total |
| Max file size | 100 MB per file |
| Collaborators per project | 5 |

See [pricing](https://ethionext.com.et/pricing) to upgrade.

---

## 🤝 Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) first.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'feat: add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

---

## 🛡️ Security

Found a security issue? Please **do not** open a public GitHub issue.
Instead, email: **mahizeki037@gmail.com**

See [SECURITY.md](SECURITY.md) for our full vulnerability reporting policy.

---

## 📄 License

SchoolShare uses a **dual license**:

- **Community Edition**: Free to use, modify, and self-host — EthioNext branding must remain
- **Commercial / White-Label**: Paid license removes branding requirement

See [LICENSE.md](LICENSE.md) for the full license text.

---

## 👤 Developer & Contact

<table>
  <tr>
    <td><strong>Developer</strong></td>
    <td>Mahi Zeki Mukhtar</td>
  </tr>
  <tr>
    <td><strong>Email</strong></td>
    <td><a href="mailto:mahizeki037@gmail.com">mahizeki037@gmail.com</a></td>
  </tr>
  <tr>
    <td><strong>Phone</strong></td>
    <td>+251 992 194 042</td>
  </tr>
  <tr>
    <td><strong>Website</strong></td>
    <td><a href="https://ethionext.com.et">ethionext.com.et</a></td>
  </tr>
  <tr>
    <td><strong>GitHub</strong></td>
    <td><a href="https://github.com/mz-mukhtar/School-Share">mz-mukhtar/School-Share</a></td>
  </tr>
</table>

---

## 🌍 About EthioNext

EthioNext is a technology initiative dedicated to building practical software tools for Ethiopian students, schools, and communities.

Visit [ethionext.com.et](https://ethionext.com.et) to learn more.

---

<p align="center">
  Made with ❤️ in Ethiopia by <strong>EthioNext</strong><br/>
  <a href="https://ethionext.com.et">ethionext.com.et</a>
</p>
