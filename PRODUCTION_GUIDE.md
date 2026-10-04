# SchoolShare: Production & Cloudflare Guide

This guide outlines the recommended steps for deploying SchoolShare to a production environment (like cPanel) and configuring Cloudflare for caching, security, and performance.

## 1. Cloudflare Configuration

When deploying SchoolShare behind Cloudflare, you get free DDoS protection, SSL, and CDN capabilities.

### DNS Setup
1. Point your domain's nameservers to Cloudflare.
2. Ensure the `A` record for your domain points to your cPanel IP address, and the proxy status is **Proxied (Orange Cloud)**.

### SSL/TLS Settings
- Set SSL/TLS encryption mode to **Full** or **Full (strict)**.
- **Do not** use Flexible SSL, as this can cause redirect loops in Laravel.
- Enable **Always Use HTTPS** in the Edge Certificates tab.

### Caching Rules
By default, Cloudflare caches static assets (images, CSS, JS). However, Laravel handles dynamic content.
- Go to **Rules -> Page Rules**.
- Create a rule to bypass cache for dynamic routes:
  - URL: `*ethionext.com.et/*`
  - Setting: **Cache Level -> Bypass**
  *(Note: Cloudflare automatically bypasses cache for HTML, so this is just an extra precaution if you ever aggressively cache).*
- Go to **Caching -> Configuration**:
  - Set **Browser Cache TTL** to something reasonable (e.g., 4 hours).
  
### Security & WAF
- Enable **Bot Fight Mode**.
- Set the **Security Level** to Medium (or High if under attack).

---

## 2. Laravel Production Optimizations (cPanel)

Before announcing your launch, make sure these commands are run in your cPanel terminal:

```bash
# 1. Optimize Composer Autoloader
composer install --optimize-autoloader --no-dev

# 2. Cache Configuration
php artisan config:cache

# 3. Cache Routes
php artisan route:cache

# 4. Cache Views
php artisan view:cache
```

### Environment Variables (`.env`)
Ensure your `.env` file on cPanel has these settings:
```ini
APP_ENV=production
APP_DEBUG=false

# If you use Cloudflare, Laravel needs to trust its proxies to get the real user IP
TRUSTED_PROXIES=*
```

### Storage Link
Ensure your storage is correctly linked so public files can be served:
```bash
php artisan storage:link
```

---

## 3. Basic Security Audit Summary

We have reviewed the core mechanics of the application:
1. **Authentication:** Handled by Laravel Breeze (secure bcrypt hashing).
2. **OTP Verification:** Custom middleware `otp.verified` ensures users cannot access the dashboard or upload files until their email is verified.
3. **Admin Area:** Protected by `is_admin` middleware. Only manually designated users can access the dashboard.
4. **Rate Limiting:** File uploads (creating checkpoints) are rate-limited to 10 requests per minute to prevent malicious storage flooding.
5. **CSRF & XSS:** Laravel automatically protects all POST/PUT/DELETE forms with `@csrf`, and Blade `{{ }}` syntax escapes output to prevent XSS.
6. **File Security:** Uploads are restricted by size in PHP configuration and application validation logic. Users can only delete or modify their *own* projects and files.

Your application is structurally sound and ready for production!
