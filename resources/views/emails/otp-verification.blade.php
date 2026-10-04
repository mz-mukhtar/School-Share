<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your SchoolShare Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#0f172a;font-family:'Segoe UI',Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#0f172a;min-height:100vh;">
        <tr>
            <td align="center" style="padding:48px 16px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#1e293b;border-radius:20px 20px 0 0;padding:32px 40px 24px;border-bottom:1px solid #2d3748;text-align:center;">
                            <div style="font-size:1.5rem;font-weight:800;color:#2563eb;letter-spacing:-0.5px;">
                                School<span style="color:#e2e8f0;font-weight:400;">Share</span>
                            </div>
                            <div style="font-size:0.78rem;color:#94a3b8;margin-top:4px;">by EthioNext · schoolshare.ethionext.com.et</div>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background:#1e293b;padding:32px 40px;">

                            {{-- Icon --}}
                            <div style="text-align:center;margin-bottom:24px;">
                                <div style="display:inline-block;background:linear-gradient(135deg,rgba(37,99,235,.2),rgba(6,182,212,.2));border-radius:16px;padding:18px;font-size:2.2rem;">
                                    🔐
                                </div>
                            </div>

                            <h1 style="color:#e2e8f0;font-size:1.35rem;font-weight:700;margin:0 0 8px;text-align:center;">
                                Verify your email address
                            </h1>
                            <p style="color:#94a3b8;font-size:0.875rem;text-align:center;margin:0 0 28px;line-height:1.6;">
                                Hi {{ $userName }}, enter the 6-digit code below to activate your SchoolShare account.
                            </p>

                            {{-- OTP Code --}}
                            <div style="background:#0f172a;border:2px solid #2563eb;border-radius:16px;padding:24px;text-align:center;margin-bottom:28px;">
                                <div style="letter-spacing:0.35em;font-size:2.6rem;font-weight:900;color:#e2e8f0;font-variant-numeric:tabular-nums;">
                                    {{ chunk_split($otp, 3, ' ') }}
                                </div>
                                <div style="font-size:0.78rem;color:#94a3b8;margin-top:8px;">
                                    This code expires in <strong style="color:#e2e8f0;">15 minutes</strong>
                                </div>
                            </div>

                            {{-- Tips --}}
                            <div style="background:#0f172a;border-radius:12px;padding:16px 20px;margin-bottom:28px;">
                                <div style="font-size:0.8rem;color:#94a3b8;line-height:1.7;">
                                    <div style="color:#e2e8f0;font-weight:600;margin-bottom:6px;">💡 Tips</div>
                                    <div>• Check your spam/junk folder if you don't see this email.</div>
                                    <div>• If the code expired, click "Resend code" on the verification page.</div>
                                    <div>• Never share this code with anyone.</div>
                                </div>
                            </div>

                            <p style="color:#64748b;font-size:0.75rem;text-align:center;margin:0;">
                                If you didn't create an account on SchoolShare, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#0f172a;border-radius:0 0 20px 20px;padding:20px 40px;border-top:1px solid #1e293b;text-align:center;">
                            <div style="font-size:0.72rem;color:#475569;line-height:1.6;">
                                SchoolShare by EthioNext · <a href="https://schoolshare.ethionext.com.et" style="color:#475569;">schoolshare.ethionext.com.et</a><br>
                                Developed by Mahi Zeki Mukhtar · <a href="mailto:mahizeki037@gmail.com" style="color:#475569;">mahizeki037@gmail.com</a>
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
