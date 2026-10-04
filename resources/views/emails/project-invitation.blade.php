<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Invitation - SchoolShare</title>
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
                                    🤝
                                </div>
                            </div>

                            <h1 style="color:#e2e8f0;font-size:1.35rem;font-weight:700;margin:0 0 8px;text-align:center;">
                                You've been invited!
                            </h1>
                            
                            <p style="color:#94a3b8;font-size:0.95rem;text-align:center;margin:0 0 28px;line-height:1.6;">
                                Hi <strong style="color:#e2e8f0;">{{ $invitee->name }}</strong>,<br><br>
                                <strong style="color:#e2e8f0;">{{ $inviter->name }}</strong> has invited you to collaborate on the project 
                                <span style="background:rgba(37,99,235,0.15);color:#60a5fa;padding:2px 8px;border-radius:4px;font-family:monospace;font-size:0.9em;">{{ $project->name }}</span>.
                            </p>

                            {{-- Action Button --}}
                            <div style="text-align:center;margin-bottom:32px;">
                                <a href="{{ route('projects.show', $project) }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:1rem;padding:14px 28px;border-radius:10px;box-shadow:0 4px 14px rgba(37,99,235,0.4);">
                                    View Project Dashboard
                                </a>
                            </div>

                            {{-- What you can do --}}
                            <div style="background:#0f172a;border-radius:12px;padding:16px 20px;margin-bottom:12px;">
                                <div style="font-size:0.85rem;color:#94a3b8;line-height:1.7;">
                                    <div style="color:#e2e8f0;font-weight:600;margin-bottom:8px;">As a collaborator, you can:</div>
                                    <div>• <strong style="color:#cbd5e1;">View</strong> all project files and folders.</div>
                                    <div>• <strong style="color:#cbd5e1;">Upload</strong> new versions and checkpoints.</div>
                                    <div>• <strong style="color:#cbd5e1;">Edit</strong> code and text files directly in the browser.</div>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background:#0f172a;border-radius:0 0 20px 20px;padding:20px 40px;border-top:1px solid #1e293b;text-align:center;">
                            <div style="font-size:0.72rem;color:#475569;line-height:1.6;">
                                SchoolShare by EthioNext · <a href="https://schoolshare.ethionext.com.et" style="color:#475569;text-decoration:none;">schoolshare.ethionext.com.et</a><br>
                                Developed by Mahi Zeki Mukhtar
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
