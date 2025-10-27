@component('mail::message')
{!! '
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center" style="padding-bottom:16px;">
      <img src="https://samsglobal.co.uk/img/logo.png" alt="SAMS Global" width="140" style="display:block;">
    </td>
  </tr>
</table>
' !!}

# Password Reset Request

Hi {{ $user->name ?? 'there' }},

A password-reset request was received for your SAMS Global account.
Click the button below to set a new password:

{{-- Custom styled white button with blue text --}}
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:24px 0;">
  <tr>
    <td align="center">
      <a href="{{ $url }}"
         style="
           background-color:#ffffff;
           color:#282560;
           font-weight:600;
           border:2px solid #282560;
           text-decoration:none;
           padding:10px 22px;
           border-radius:6px;
           display:inline-block;
           font-size:15px;
         ">
        Reset Password
      </a>
    </td>
  </tr>
</table>

If the button above doesn’t work, copy and paste this link into your browser:

<p style="word-break:break-all; margin-top:4px;">
  <a href="{{ $url }}" style="color:#282560;">{{ $url }}</a>
</p>

This link will expire in **60 minutes**.
If this request wasn’t made, you can safely ignore this email.

Thanks,
**The SAMS Global Team**

<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">

<p style="font-size:13px; line-height:1.6; color:#555; text-align:center;">
  <strong>SAMS Global Solutions Ltd.</strong><br>
  The Workstation, 15 Paternoster Row,<br>
  Sheffield, S1 2BX, United Kingdom.<br>
  <a href="mailto:contact@samsglobal.co.uk" style="color:#282560;">contact@samsglobal.co.uk</a> |
  <a href="tel:+441142725444" style="color:#282560;">+44 (0) 1142 725 444</a>
</p>

<p style="font-size:11px;color:#999;text-align:center;margin-top:8px;">
  This message was sent automatically by SAMS Global. Do not reply to this email.<br>
  © 2025 SAMS Global. All rights reserved.
</p>
@endcomponent
