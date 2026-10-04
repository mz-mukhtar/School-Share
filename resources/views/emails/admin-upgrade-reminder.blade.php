<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Upgrade Request — SchoolShare Admin</title>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a;padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#7c2d12,#1e293b);border-radius:16px 16px 0 0;padding:32px;text-align:center;">
                        <p style="margin:0 0 6px;font-size:28px;">🔔</p>
                        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">New Upgrade Request</h1>
                        <p style="margin:6px 0 0;color:#94a3b8;font-size:14px;">SchoolShare Admin Notification</p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background:#1e293b;padding:32px;">
                        <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 24px;">
                            A user has submitted an upgrade request on SchoolShare. Here are the details:
                        </p>

                        {{-- Details table --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a;border-radius:10px;border:1px solid #334155;margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 20px;border-bottom:1px solid #1e293b;">
                                    <p style="margin:0;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;">User</p>
                                    <p style="margin:4px 0 0;color:#f8fafc;font-size:16px;font-weight:600;">{{ $upgradeRequest->user->name }}</p>
                                    <p style="margin:2px 0 0;color:#60a5fa;font-size:14px;">{{ $upgradeRequest->user->email }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 20px;border-bottom:1px solid #1e293b;">
                                    <p style="margin:0;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;">Requested Plan</p>
                                    <p style="margin:4px 0 0;color:#f8fafc;font-size:16px;font-weight:600;">{{ $upgradeRequest->formattedPlan() }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 20px;border-bottom:1px solid #1e293b;">
                                    <p style="margin:0;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;">Billing Cycle</p>
                                    <p style="margin:4px 0 0;color:#f8fafc;font-size:16px;font-weight:600;">{{ ucfirst($upgradeRequest->billing_cycle) }}</p>
                                    @if($upgradeRequest->requested_plan !== 'custom')
                                    <p style="margin:2px 0 0;color:#60a5fa;font-size:14px;">{{ $upgradeRequest->formattedPrice() }}</p>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 20px;">
                                    <p style="margin:0;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;">Requested At</p>
                                    <p style="margin:4px 0 0;color:#f8fafc;font-size:16px;font-weight:600;">{{ $upgradeRequest->created_at->format('D, d M Y — H:i') }}</p>
                                </td>
                            </tr>
                        </table>

                        <p style="color:#cbd5e1;font-size:14px;line-height:1.6;margin:0 0 20px;">
                            The user has been sent payment instructions to <strong style="color:#f8fafc;">{{ $upgradeRequest->user->email }}</strong>.
                            Once you receive their payment screenshot at
                            <a href="mailto:payment@ethionext.com.et" style="color:#60a5fa;">payment@ethionext.com.et</a>,
                            you can manually upgrade their account from the admin panel.
                        </p>

                        {{-- CTA Button --}}
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center">
                                    <a href="{{ url('/admin/upgrades') }}" style="display:inline-block;background:#2563eb;color:#fff;font-size:15px;font-weight:700;padding:14px 32px;border-radius:10px;text-decoration:none;">
                                        🛠 Go to Admin Panel
                                    </a>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#0f172a;border-radius:0 0 16px 16px;padding:20px 32px;text-align:center;border-top:1px solid #1e293b;">
                        <p style="margin:0;color:#475569;font-size:12px;">
                            This is an automated admin notification from SchoolShare.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
