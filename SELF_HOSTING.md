# Self-Hosting Guide — SchoolShare on cPanel

> **Note**: This guide covers deploying SchoolShare on cPanel shared/VPS hosting.
> A Commercial License is required to self-host without EthioNext branding.
> Community self-hosting (with branding) is free.
> See [LICENSE.md](LICENSE.md) and [ethionext.com.et/pricing](https://ethionext.com.et/pricing).

---

## Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.1 | 8.2+ |
| MySQL / MariaDB | 5.7 / 10.3 | MySQL 8.0 |
| Disk space | 2 GB | 10 GB+ |
| RAM | 512 MB | 1 GB+ |
| PHP extensions | pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, zip | All of the minimum |
| OPcache | Recommended | Enabled |

---

## Step 1 — Prepare Your cPanel Account

### 1.1 Create a MySQL Database

1. Log in to your cPanel dashboard
2. Go to **MySQL Databases**
3. Create a new database (e.g., `yourusername_schoolshare`)
4. Create a new database user with a strong password
5. Add the user to the database with **All Privileges**
6. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### 1.2 Enable OPcache (highly recommended)

1. In cPanel, go to **PHP Selector** or **MultiPHP INI Editor**
2. Select your PHP version (8.1 or 8.2)
3. Enable `opcache.enable = 1`
4. Set `opcache.memory_consumption = 128`

### 1.3 Set PHP Version

1. In cPanel, go to **PHP Selector** or **MultiPHP Manager**
2. Set your domain to use **PHP 8.1** or **8.2**

---

## Step 2 — Upload the Files

### Option A — Git (via SSH, recommended)

If your cPanel plan includes SSH access:

```bash
ssh yourusername@yourdomain.com

# Clone into a temporary directory
git clone https://github.com/mz-mukhtar/School-Share.git /home/yourusername/schoolshare_temp

# Or pull if already cloned
cd /home/yourusername/schoolshare_temp && git pull
```

### Option B — File Manager or FTP

1. Download the latest release ZIP from GitHub
2. Upload via cPanel **File Manager** or FTP (FileZilla recommended)
3. Extract to `/home/yourusername/schoolshare/`

---

## Step 3 — Set the Document Root

1. In cPanel, go to **Domains** (or **Addon Domains** / **Subdomains**)
2. Edit your domain or subdomain
3. Set the **Document Root** to:
   ```
   /home/yourusername/schoolshare/public
   ```
   ⚠️ It must point to the `public/` subfolder, not the root of the project.

---

## Step 4 — Install Composer Dependencies

Via SSH (recommended):
```bash
cd /home/yourusername/schoolshare
composer install --no-dev --optimize-autoloader
```

Via cPanel **Terminal** (if available):
```bash
cd ~/schoolshare
composer install --no-dev --optimize-autoloader
```

> If Composer is not available, download `composer.phar` first:
> ```bash
> curl -sS https://getcomposer.org/installer | php
> php composer.phar install --no-dev --optimize-autoloader
> ```

---

## Step 5 — Configure the Environment

```bash
# Copy the example env file
cp .env.example .env

# Edit with your database credentials
nano .env
```

Set these values in `.env`:

```ini
APP_NAME="SchoolShare"
APP_ENV=production
APP_KEY=            # Will be generated in next step
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=yourusername_schoolshare
DB_USERNAME=yourusername_dbuser
DB_PASSWORD=your_strong_password

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="SchoolShare"

SCHOOLSHARE_MAX_FILE_MB=100
SCHOOLSHARE_MAX_STORAGE_GB=1
SCHOOLSHARE_LICENSE_KEY=    # Leave blank for Community Edition (branding shown)
```

---

## Step 6 — Generate App Key & Migrate Database

```bash
# Generate the application encryption key
php artisan key:generate

# Run database migrations (creates all tables)
php artisan migrate --force

# Create storage symlink
php artisan storage:link
```

---

## Step 7 — Set Folder Permissions

```bash
# Storage and cache directories need write access
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

---

## Step 8 — Optimize for Production

```bash
# Cache configuration, routes, and views for speed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Run these after every deployment/update.

---

## Step 9 — Configure .htaccess (Apache)

Laravel ships with a `public/.htaccess` file that handles URL routing.
Make sure **mod_rewrite** is enabled in your Apache/cPanel configuration.

If you see "404 Not Found" for all routes, try this in your cPanel:
1. Go to **Apache Handlers** or **Module Configuration**
2. Ensure `mod_rewrite` is enabled

If cPanel doesn't allow you to enable mod_rewrite, contact your hosting provider.

---

## Step 10 — Test Your Installation

1. Visit `https://yourdomain.com` → You should see the SchoolShare landing page
2. Visit `https://yourdomain.com/register` → Create an account
3. Upload a test file → Verify it appears in the project

---

## Updating SchoolShare

```bash
cd /home/yourusername/schoolshare

# Pull latest changes
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations
php artisan migrate --force

# Re-cache for production
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Troubleshooting

### "500 Internal Server Error"
- Check `storage/logs/laravel.log` for the error
- Make sure `APP_DEBUG=false` in production (don't expose errors publicly)
- Verify file permissions: `storage/` and `bootstrap/cache/` must be writable

### "419 Page Expired" (CSRF error)
- Run `php artisan config:cache` again
- Check your `APP_URL` matches the actual URL you're accessing

### Files not uploading
- Check `SCHOOLSHARE_MAX_FILE_MB` in `.env`
- Check PHP `upload_max_filesize` and `post_max_size` in cPanel PHP settings
- In cPanel: **PHP Selector** → Edit directives → set both to `100M` (or higher)

### Emails not sending
- Use cPanel email accounts with SMTP settings from cPanel Webmail
- Verify MAIL_* settings in `.env`
- Test with `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@email.com')->subject('test'));`

### "Branding Required" page is shown
- You are running SchoolShare without a valid Commercial License
- Either keep the EthioNext branding (Community Edition) or purchase a license:
  Contact mahizeki037@gmail.com

---

## Licensing for Self-Hosting

| Scenario | License Needed | Branding |
|---|---|---|
| Personal/educational use, EthioNext branding kept | Community (free) | Required |
| Removing EthioNext branding | Commercial | Optional |
| Selling as a service under your own brand | Commercial (SaaS tier) | Optional |

Purchase a Commercial License: **mahizeki037@gmail.com** | **+251 992 194 042**

---

## Support

- 📧 mahizeki037@gmail.com
- 📞 +251 992 194 042
- 🌐 https://ethionext.com.et
- 🐙 https://github.com/mz-mukhtar/School-Share/issues

---

*SchoolShare by EthioNext — ethionext.com.et*
