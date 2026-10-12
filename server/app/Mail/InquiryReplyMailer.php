<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InquiryReplyMailer extends Mailable
{
    public function __construct(
        public string $recipientName,
        public string $branchName,
        public ?string $branchEmail,
        public ?string $branchPhone,
        public string $originalMessage,
        public string $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Re: Your inquiry to {$this->branchName}",
            replyTo: $this->branchEmail ? [new Address($this->branchEmail, $this->branchName)] : [],
        );
    }

    public function content(): Content
    {
        $accent = '#3182ED';
        $accentSoft = '#eaf2fd';

        $recipient = e($this->recipientName);
        $branch = e($this->branchName);
        $reply = nl2br(e($this->reply));
        $original = nl2br(e($this->originalMessage));

        $logoUrl = config('mail.logo_url');

        $logo = $logoUrl
            ? "<img src='{$logoUrl}' width='160' alt='AMUMA' style='display:block;width:160px;max-width:160px;height:auto;border:0;outline:none;text-decoration:none;font-size:22px;font-weight:800;color:#3182ED;'>"
            : "<p style='margin:0;font-size:22px;font-weight:800;letter-spacing:1px;color:#3182ED;'>AMUMA</p>";

        $contactRows = '';

        if ($this->branchPhone) {
            $phone = e($this->branchPhone);
            $contactRows .= "
                <tr>
                    <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Phone</td>
                    <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$phone}</td>
                </tr>";
        }

        if ($this->branchEmail) {
            $email = e($this->branchEmail);
            $contactRows .= "
                <tr>
                    <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Email</td>
                    <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$email}</td>
                </tr>";
        }

        $contactBlock = $contactRows
            ? "
            <table width='100%' cellpadding='0' cellspacing='0' style='margin-top:24px;border-top:1px solid #f1f5f9;padding-top:12px;'>
                {$contactRows}
            </table>"
            : '';

        return new Content(
            htmlString: "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                    <td align='center' style='padding:48px 20px;'>
                        <table width='520' cellpadding='0' cellspacing='0'>

                            <tr>
                                <td align='center' style='padding-bottom:28px;'>
                                    {$logo}
                                </td>
                            </tr>

                            <tr>
                                <td style='background:#ffffff;border-radius:20px;overflow:hidden;'>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:40px 48px 28px;border-bottom:1px solid #f1f5f9;'>
                                                <p style='margin:0 0 8px;display:inline-block;padding:4px 12px;border-radius:999px;background:{$accentSoft};color:{$accent};font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;'>Inquiry reply</p>
                                                <p style='margin:12px 0 6px;font-size:22px;font-weight:700;color:#0f172a;'>{$branch} replied to your inquiry</p>
                                                <p style='margin:0;font-size:14px;color:#64748b;line-height:1.7;'>
                                                    Hi <strong style='color:#0f172a;'>{$recipient}</strong> — thank you for reaching out. Here is their response.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:28px 48px;'>
                                                <p style='margin:0;font-size:14px;color:#0f172a;line-height:1.8;'>{$reply}</p>

                                                <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8fafc;border-radius:10px;margin-top:24px;'>
                                                    <tr>
                                                        <td style='padding:14px 18px;'>
                                                            <p style='margin:0 0 4px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#94a3b8;'>Your message</p>
                                                            <p style='margin:0;font-size:13px;color:#64748b;line-height:1.6;'>{$original}</p>
                                                        </td>
                                                    </tr>
                                                </table>

                                                {$contactBlock}
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>

                            <tr>
                                <td align='center' style='padding:28px 0 0;'>
                                    <p style='margin:0;font-size:12px;color:#94a3b8;'>© 2026 AMUMA. All rights reserved.</p>
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
