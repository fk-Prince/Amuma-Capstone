<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMailer extends Mailable
{
    public function __construct(
        public int $otp,
        public int $minutes = 5
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your AMUMA account');
    }

    public function content(): Content
    {
        $year = date('Y');
        $logoUrl = config('mail.logo_url');

        $logo = $logoUrl
            ? "<img src='{$logoUrl}' width='160' alt='AMUMA' style='display:block;width:160px;max-width:160px;height:auto;border:0;outline:none;text-decoration:none;font-size:22px;font-weight:800;color:#3182ED;'>"
            : "<p style='margin:0;font-size:22px;font-weight:800;letter-spacing:1px;color:#3182ED;'>AMUMA</p>";

        return new Content(
            htmlString: "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <meta name='color-scheme' content='light'>
            <meta name='supported-color-schemes' content='light'>
        </head>
        <body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' role='presentation' style='background:#f1f5f9;'>
                <tr>
                    <td align='center' style='padding:40px 16px;'>
                        <table width='100%' cellpadding='0' cellspacing='0' role='presentation' style='max-width:480px;'>

                            <tr>
                                <td align='center' style='padding-bottom:24px;'>
                                    {$logo}
                                </td>
                            </tr>

                            <tr>
                                <td style='background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:40px 40px 32px;'>
                                    <p style='margin:0 0 8px;font-size:22px;font-weight:700;color:#1A2133;'>Verify your email</p>
                                    <p style='margin:0;font-size:15px;line-height:24px;color:#475569;'>
                                        Enter this code in AMUMA to finish creating your account.
                                    </p>

                                    <table width='100%' cellpadding='0' cellspacing='0' role='presentation' style='margin:28px 0 12px;'>
                                        <tr>
                                            <td align='center' style='background:#EAF2FE;border-radius:12px;padding:24px 16px;'>
                                                <p style='margin:0;padding-left:10px;font-size:36px;line-height:40px;font-weight:700;letter-spacing:10px;color:#1650A6;'>{$this->otp}</p>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin:0;font-size:14px;line-height:22px;color:#64748b;text-align:center;'>
                                        This code expires in <strong style='color:#1A2133;'>{$this->minutes} minutes</strong>.
                                    </p>

                                    <table width='100%' cellpadding='0' cellspacing='0' role='presentation' style='margin-top:32px;'>
                                        <tr>
                                            <td style='border-top:1px solid #e2e8f0;padding-top:24px;'>
                                                <p style='margin:0 0 12px;font-size:13px;line-height:20px;color:#64748b;'>
                                                    <strong style='color:#1A2133;'>Keep this code private.</strong> AMUMA will never ask you for it by phone, chat, or email.
                                                </p>
                                                <p style='margin:0;font-size:13px;line-height:20px;color:#64748b;'>
                                                    Didn't try to create an AMUMA account? You can safely ignore this email.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td align='center' style='padding:24px 16px 0;'>
                                    <p style='margin:0;font-size:12px;line-height:18px;color:#94a3b8;'>© {$year} AMUMA. All rights reserved.</p>
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        "
        );
    }
}
