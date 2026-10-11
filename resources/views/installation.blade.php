<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Install SchoolShare</title>
    <style>
        :root { color-scheme: dark; font-family: system-ui, sans-serif; background: #0f172a; color: #e2e8f0; }
        body { margin: 0; padding: 2rem 1rem; } main { max-width: 880px; margin: auto; }
        h1 { margin-bottom: .4rem; } p { line-height: 1.6; } .muted { color: #a7b4c9; }
        fieldset, .notice { border: 1px solid #334155; border-radius: 12px; padding: 1.3rem; margin: 1.5rem 0; background: #1e293b; }
        legend { font-weight: 700; padding: 0 .4rem; } label { display: block; margin: 1rem 0 .35rem; }
        input, select { box-sizing: border-box; width: 100%; padding: .75rem; background: #0f172a; border: 1px solid #64748b; border-radius: 6px; color: #f8fafc; font: inherit; }
        input:focus, select:focus { outline: 2px solid #60a5fa; outline-offset: 2px; }
        button { background: #2563eb; color: white; border: 0; border-radius: 6px; padding: .9rem 1.4rem; font: inherit; cursor: pointer; }
        button:disabled { opacity: .5; cursor: not-allowed; } .error, .failed { color: #fca5a5; } .passed { color: #86efac; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0 1.5rem; }
        li { margin: .4rem 0; } code { overflow-wrap: anywhere; } a { color: #93c5fd; }
    </style>
</head>
<body><main>
    <h1>Install SchoolShare</h1>
    <p class="muted">Fresh installation · PHP 8.5 · MySQL 8.4 or MariaDB 10.11.19+ / 11.4</p>
    @if ($errors->any())
        <div class="notice error" role="alert"><h2>Setup needs attention</h2><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @if ($started)
        <div class="notice" role="alert"><h2>Installation has already started</h2>
            <p>Setup cannot run again. If installation was interrupted, preserve the private configuration and lock files. Ask an operator to inspect or restore this dedicated database. Do not delete tables or remove the installation markers.</p>
        </div>
    @elseif (! $unlocked)
        <p>Before entering credentials, unlock setup using the token in your private <code>schoolshare/setup-token.txt</code> file. Read it through cPanel File Manager, not through a public link.</p>
        <form method="POST" action="{{ route('installation.unlock') }}">
            @csrf
            <fieldset><legend>1. Unlock private setup</legend>
                <label for="setup_token">One-time setup token</label>
                <input id="setup_token" name="setup_token" type="password" required minlength="64" maxlength="64" autocomplete="off">
            </fieldset>
            <button type="submit">Unlock setup</button>
        </form>
    @else
        <fieldset><legend>2. Hosting requirements</legend>
            <ul>@foreach ($requirements as $label => $passed)<li class="{{ $passed ? 'passed' : 'failed' }}">{{ $passed ? 'Pass' : 'Fix required' }} — {{ $label }}</li>@endforeach</ul>
            <p class="muted">Do not use permissions such as 777. Ask your host to correct ownership, enable missing extensions, or allow the avatar storage link.</p>
        </fieldset>
        <form method="POST" action="{{ route('installation.store') }}">
            @csrf
            <fieldset><legend>3. Site</legend><div class="grid">
                <div><label for="site_name">Site name</label><input id="site_name" name="site_name" value="{{ old('site_name', 'SchoolShare') }}" required maxlength="100"></div>
                <div><label for="site_url">HTTPS site address</label><input id="site_url" name="site_url" type="url" value="{{ old('site_url', request()->getSchemeAndHttpHost()) }}" placeholder="https://schoolshare.example" required maxlength="255"></div>
            </div></fieldset>
            <fieldset><legend>4. Empty database created in cPanel</legend>
                <p class="muted">Create the database and user in cPanel Database Wizard first. Grant the user the required database privileges. Enter the full names including any account prefix. Existing tables or views will not be overwritten.</p>
                <div class="grid">
                    <div><label for="db_connection">Database engine</label><select id="db_connection" name="db_connection"><option value="mysql" @selected(old('db_connection') === 'mysql')>MySQL 8.4</option><option value="mariadb" @selected(old('db_connection') === 'mariadb')>MariaDB 10.11.19+ / 11.4</option></select></div>
                    <div><label for="db_host">Database host</label><input id="db_host" name="db_host" value="{{ old('db_host', 'localhost') }}" required maxlength="255"></div>
                    <div><label for="db_port">Database port</label><input id="db_port" name="db_port" type="number" min="1" max="65535" value="{{ old('db_port', 3306) }}" required></div>
                    <div><label for="db_database">Full database name</label><input id="db_database" name="db_database" value="{{ old('db_database') }}" placeholder="account_schoolshare" required maxlength="64"></div>
                    <div><label for="db_username">Full database username</label><input id="db_username" name="db_username" value="{{ old('db_username') }}" placeholder="account_schooluser" required maxlength="47"></div>
                    <div><label for="db_password">Database password</label><input id="db_password" name="db_password" type="password" required maxlength="1024" autocomplete="off"></div>
                </div>
            </fieldset>
            <fieldset><legend>5. Production SMTP</legend>
                <p class="muted">Use your mail provider's settings. STARTTLS is required for SMTP; SMTPS uses implicit TLS. Credentials are never redisplayed. Mailbox verification happens after installation.</p>
                <div class="grid">
                    <div><label for="mail_host">SMTP host</label><input id="mail_host" name="mail_host" value="{{ old('mail_host') }}" required maxlength="255"></div>
                    <div><label for="mail_scheme">Encryption</label><select id="mail_scheme" name="mail_scheme"><option value="smtps" @selected(old('mail_scheme', 'smtps') === 'smtps')>SMTPS / implicit TLS</option><option value="smtp" @selected(old('mail_scheme') === 'smtp')>SMTP / required STARTTLS</option></select></div>
                    <div><label for="mail_port">SMTP port</label><input id="mail_port" name="mail_port" type="number" min="1" max="65535" value="{{ old('mail_port', 465) }}" required></div>
                    <div><label for="mail_username">SMTP username</label><input id="mail_username" name="mail_username" value="{{ old('mail_username') }}" maxlength="255" autocomplete="off"></div>
                    <div><label for="mail_password">SMTP password</label><input id="mail_password" name="mail_password" type="password" maxlength="1024" autocomplete="off"></div>
                    <div><label for="mail_from_address">Sender email</label><input id="mail_from_address" name="mail_from_address" type="email" value="{{ old('mail_from_address') }}" required maxlength="255"></div>
                </div>
            </fieldset>
            <fieldset><legend>6. First administrator</legend><div class="grid">
                <div><label for="admin_name">Admin name</label><input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required maxlength="255"></div>
                <div><label for="admin_email">Admin email</label><input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required maxlength="255"></div>
                <div><label for="admin_password">Admin password</label><input id="admin_password" name="admin_password" type="password" required minlength="12" maxlength="1024" autocomplete="new-password"></div>
                <div><label for="admin_password_confirmation">Confirm admin password</label><input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required minlength="12" maxlength="1024" autocomplete="new-password"></div>
            </div><p class="muted">Use at least 12 characters with uppercase, lowercase, a number, and a symbol. The administrator must verify their email before accessing the admin panel.</p></fieldset>
            <p>Installing creates the tables and first administrator, then permanently locks setup. Keep this page open until it finishes. If the request times out, do not submit it again.</p>
            <button type="submit" @disabled(in_array(false, $requirements, true))>Install SchoolShare</button>
        </form>
    @endif
    <p class="muted">SchoolShare by EthioNext · Keep private configuration, backups, and setup tokens outside every website document root.</p>
</main></body>
</html>
