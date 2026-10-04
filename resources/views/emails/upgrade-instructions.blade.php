<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchoolShare — Upgrade Instructions</title>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a;padding:40px 16px;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:linear-gradient(135deg,#1e3a8a,#1e293b);border-radius:16px 16px 0 0;padding:40px 32px;text-align:center;">
                        <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:800;letter-spacing:-0.5px;">
                            School<span style="color:#60a5fa;font-weight:400;">Share</span>
                        </h1>
                        <p style="margin:8px 0 0;color:#94a3b8;font-size:14px;">by EthioNext</p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background:#1e293b;padding:36px 32px;">

                        <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 16px;">
                            Hi <strong style="color:#f8fafc;">{{ $user->name }}</strong>,
                        </p>
                        <p style="color:#cbd5e1;font-size:16px;line-height:1.6;margin:0 0 24px;">
                            Thank you for your interest in upgrading to the
                            <strong style="color:#60a5fa;">{{ $upgradeRequest->formattedPlan() }}</strong> plan!
                            Below are your complete payment instructions.
                        </p>

                        {{-- Plan Summary Card --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a;border-radius:12px;border:1px solid #334155;margin-bottom:28px;">
                            <tr>
                                <td style="padding:20px 24px;">
                                    <p style="margin:0 0 8px;color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;">Your Selected Plan</p>
                                    <p style="margin:0 0 4px;color:#f8fafc;font-size:22px;font-weight:700;">{{ $upgradeRequest->formattedPlan() }}</p>
                                    @if($upgradeRequest->requested_plan !== 'custom')
                                    <p style="margin:0;color:#60a5fa;font-size:16px;font-weight:600;">{{ $upgradeRequest->formattedPrice() }}</p>
                                    @if($upgradeRequest->billing_cycle === 'yearly')
                                    <p style="margin:4px 0 0;color:#10b981;font-size:13px;">🎉 You save 400 ETB with the yearly plan!</p>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                        </table>

                        @if($upgradeRequest->requested_plan === 'custom')
                        {{-- Custom Plan --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#1e3a8a;border-radius:12px;border:1px solid #3b82f6;margin-bottom:28px;">
                            <tr>
                                <td style="padding:20px 24px;">
                                    <p style="margin:0 0 8px;color:#93c5fd;font-size:14px;font-weight:600;">📞 Contact Us for Custom Pricing</p>
                                    <p style="margin:0 0 12px;color:#bfdbfe;font-size:14px;line-height:1.6;">
                                        For the Custom / White-label plan, please reach out directly so we can discuss your specific needs and provide a tailored quote.
                                    </p>
                                    <p style="margin:0;color:#f8fafc;font-size:18px;font-weight:700;">📱 0992194042</p>
                                    <p style="margin:4px 0 0;color:#93c5fd;font-size:13px;">WhatsApp / Call / Telebirr</p>
                                </td>
                            </tr>
                        </table>

                        @else
                        {{-- Step-by-step payment instructions --}}
                        <p style="color:#f8fafc;font-size:17px;font-weight:700;margin:0 0 16px;">📋 How to Complete Your Payment</p>

                        {{-- Step 1 --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                            <tr>
                                <td style="vertical-align:top;padding-right:16px;width:36px;">
                                    <div style="width:32px;height:32px;background:#2563eb;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;text-align:center;line-height:32px;">1</div>
                                </td>
                                <td>
                                    <p style="margin:4px 0 4px;color:#f8fafc;font-size:15px;font-weight:600;">Send the payment to one of these accounts</p>
                                    <p style="margin:0;color:#94a3b8;font-size:14px;">Choose either Telebirr or CBE — whichever is most convenient for you.</p>
                                </td>
                            </tr>
                        </table>

                        {{-- Payment Accounts --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                {{-- Telebirr --}}
                                <td width="48%" style="background:#0f172a;border:1px solid #334155;border-radius:10px;padding:16px 20px;vertical-align:top;">
                                    <p style="margin:0 0 4px;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">💛 Telebirr</p>
                                    <p style="margin:0 0 2px;color:#f8fafc;font-size:20px;font-weight:700;letter-spacing:1px;">0992194042</p>
                                    <p style="margin:0;color:#94a3b8;font-size:13px;">Account Name: <strong style="color:#cbd5e1;">Mahi</strong></p>
                                </td>
                                <td width="4%"></td>
                                {{-- CBE --}}
                                <td width="48%" style="background:#0f172a;border:1px solid #334155;border-radius:10px;padding:16px 20px;vertical-align:top;">
                                    <p style="margin:0 0 4px;color:#94a3b8;font-size:11px;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">🏦 CBE (Commercial Bank)</p>
                                    <p style="margin:0 0 2px;color:#f8fafc;font-size:18px;font-weight:700;letter-spacing:1px;">1000305157566</p>
                                    <p style="margin:0;color:#94a3b8;font-size:13px;">Account Name: <strong style="color:#cbd5e1;">Mahi Zeki Mukhtar</strong></p>
                                </td>
                            </tr>
                        </table>

                        {{-- Step 2 --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                            <tr>
                                <td style="vertical-align:top;padding-right:16px;width:36px;">
                                    <div style="width:32px;height:32px;background:#2563eb;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;text-align:center;line-height:32px;">2</div>
                                </td>
                                <td>
                                    <p style="margin:4px 0 4px;color:#f8fafc;font-size:15px;font-weight:600;">Take a screenshot of your payment confirmation</p>
                                    <p style="margin:0;color:#94a3b8;font-size:14px;">Make sure the screenshot clearly shows: the amount, date, and your transaction ID or reference number.</p>
                                </td>
                            </tr>
                        </table>

                        {{-- Step 3 --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                            <tr>
                                <td style="vertical-align:top;padding-right:16px;width:36px;">
                                    <div style="width:32px;height:32px;background:#2563eb;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;text-align:center;line-height:32px;">3</div>
                                </td>
                                <td>
                                    <p style="margin:4px 0 4px;color:#f8fafc;font-size:15px;font-weight:600;">Send your screenshot to our payment email</p>
                                    <p style="margin:0 0 8px;color:#94a3b8;font-size:14px;">Email your payment screenshot to:</p>
                                    <a href="mailto:payment@ethionext.com.et" style="display:inline-block;background:#2563eb;color:#fff;font-size:16px;font-weight:700;padding:10px 20px;border-radius:8px;text-decoration:none;">
                                        📧 payment@ethionext.com.et
                                    </a>
                                    <p style="margin:10px 0 0;color:#94a3b8;font-size:13px;">
                                        In the email subject, please include: <strong style="color:#cbd5e1;">Your name + "{{ $upgradeRequest->formattedPlan() }} Upgrade"</strong>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        {{-- What Happens Next --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#064e3b;border-radius:12px;border:1px solid #059669;margin-bottom:28px;">
                            <tr>
                                <td style="padding:20px 24px;">
                                    <p style="margin:0 0 8px;color:#6ee7b7;font-size:14px;font-weight:600;">✅ What Happens After You Send the Screenshot?</p>
                                    <ul style="margin:0;padding-left:20px;color:#a7f3d0;font-size:14px;line-height:1.8;">
                                        <li>Our team will review your payment within <strong>24–48 hours</strong>.</li>
                                        <li>Once verified, your account will be <strong>automatically upgraded</strong>.</li>
                                        <li>You will receive a confirmation email and see your plan updated in your dashboard.</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                        {{-- Amount Reminder --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#1e3a8a;border-radius:12px;border:1px solid #3b82f6;margin-bottom:16px;">
                            <tr>
                                <td style="padding:16px 24px;">
                                    <p style="margin:0;color:#bfdbfe;font-size:14px;">
                                        💡 <strong>Reminder:</strong> Please send exactly
                                        <strong style="color:#f8fafc;font-size:16px;"> {{ $upgradeRequest->billing_cycle === 'yearly' ? '2,000' : '200' }} ETB</strong>
                                        for the {{ $upgradeRequest->billing_cycle }} plan.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        @endif

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#0f172a;border-radius:0 0 16px 16px;padding:24px 32px;text-align:center;border-top:1px solid #1e293b;">
                        <p style="margin:0 0 8px;color:#64748b;font-size:12px;">
                            Questions? Reply to this email or contact us at
                            <a href="mailto:payment@ethionext.com.et" style="color:#60a5fa;text-decoration:none;">payment@ethionext.com.et</a>
                        </p>
                        <p style="margin:0;color:#475569;font-size:11px;">
                            © {{ date('Y') }} SchoolShare by EthioNext. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
