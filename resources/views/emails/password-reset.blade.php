<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Reset Request</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,.08);">

    <!-- Header -->
    <tr>
        <td style="background:linear-gradient(135deg,#1e293b,#0f172a);padding:32px;text-align:center;">
            <img src="{{ asset('images/gmb-logo.png') }}" alt="GMB" width="52" height="52" style="display:block;margin:0 auto 12px;object-fit:contain;">
            <div style="color:#ffffff;font-size:1.15rem;font-weight:700;letter-spacing:.02em;">GMB ICT Register</div>
            <div style="color:#94a3b8;font-size:.8rem;margin-top:4px;">Grain Marketing Board — ICT Department</div>
        </td>
    </tr>

    <!-- Body -->
    <tr>
        <td style="padding:32px 32px 8px;">
            <h1 style="margin:0 0 4px;font-size:1.25rem;color:#0f172a;">Password Reset Request</h1>
            <p style="margin:0 0 20px;font-size:.9rem;color:#64748b;">Hello {{ $user->firstname }},</p>

            <p style="margin:0 0 16px;font-size:.9rem;line-height:1.6;color:#334155;">
                We received a request to reset the password for your <strong>ICT Register System</strong> account
                (<strong>{{ $user->username }}</strong>). If you made this request, use the one-time code below or
                click the button to go straight to the reset page. If you did not request a password reset, you can
                safely ignore this email — your password will remain unchanged.
            </p>

            <!-- OTP -->
            <div style="margin:24px 0;text-align:center;">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px;">Your one-time verification code</div>
                <div style="display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:14px 28px;font-size:1.8rem;font-weight:700;letter-spacing:.35em;color:#0f172a;">
                    {{ $otp }}
                </div>
                <div style="font-size:.78rem;color:#94a3b8;margin-top:10px;">
                    <i>This code expires in {{ $expiryMinutes }} minutes.</i>
                </div>
            </div>

            <!-- Reset button -->
            <div style="text-align:center;margin:28px 0;">
                <a href="{{ $resetUrl }}"
                   style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:600;font-size:.9rem;padding:13px 32px;border-radius:8px;">
                    Reset My Password
                </a>
            </div>

            <p style="margin:0 0 8px;font-size:.78rem;color:#94a3b8;text-align:center;">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $resetUrl }}" style="color:#2563eb;word-break:break-all;">{{ $resetUrl }}</a>
            </p>
        </td>
    </tr>

    <!-- Security notice -->
    <tr>
        <td style="padding:8px 32px 28px;">
            <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;font-size:.8rem;color:#92400e;line-height:1.6;">
                <strong>Security notice:</strong> Never share this code or reset link with anyone, including
                ICT staff. GMB ICT Department will never ask you for your password or this code by phone,
                email, or chat. This request was made from IP address <strong>{{ $ipAddress }}</strong> on
                {{ $requestedAt }}. If this wasn't you, please contact the ICT Department immediately.
            </div>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="padding:18px 32px;border-top:1px solid #f1f5f9;text-align:center;">
            <div style="font-size:.75rem;color:#94a3b8;">
                This is an automated message from the <strong style="color:#475569;">GMB ICT Register</strong> system — please do not reply to this email.
            </div>
            <div style="font-size:.72rem;color:#cbd5e1;margin-top:6px;">
                &copy; {{ date('Y') }} Grain Marketing Board Zimbabwe — GMB ICT Department
            </div>
        </td>
    </tr>

</table>
</td>
</tr>
</table>
</body>
</html>
