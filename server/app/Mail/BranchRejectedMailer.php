<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BranchRejectedMailer extends Mailable
{
    public function __construct(
        public string $recipientName,
        public string $branchName,
        public string $agencyName,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AMUMA branch request was not approved'
        );
    }

    public function content(): Content
    {
        $reason = nl2br(e($this->reason));

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
                                    <p style='margin:0;font-size:11px;color:#94a3b8;letter-spacing:6px;text-transform:uppercase;'>AMUMA</p>
                                </td>
                            </tr>

                            <tr>
                                <td style='background:#ffffff;border-radius:20px;overflow:hidden;'>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:40px 48px 28px;border-bottom:1px solid #f1f5f9;'>
                                                <p style='margin:0 0 8px;display:inline-block;padding:4px 12px;border-radius:999px;background:#FEE2E2;color:#B91C1C;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;'>Not approved</p>
                                                <p style='margin:12px 0 6px;font-size:22px;font-weight:700;color:#0f172a;'>Branch request declined</p>
                                                <p style='margin:0;font-size:14px;color:#64748b;line-height:1.7;'>
                                                    Hi <strong style='color:#0f172a;'>{$this->recipientName}</strong> — the request to add <strong style='color:#0f172a;'>{$this->branchName}</strong> under <strong style='color:#0f172a;'>{$this->agencyName}</strong> was reviewed and could not be approved.
                                                </p>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style='padding:28px 48px;'>
                                                <p style='margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#94a3b8;'>Reason</p>
                                                <div style='padding:16px 18px;border-radius:12px;background:#fef2f2;border:1px solid #fee2e2;font-size:14px;color:#0f172a;line-height:1.7;'>
                                                    {$reason}
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style='padding:0 48px 40px;'>
                                                <p style='margin:0;font-size:13px;color:#64748b;line-height:1.7;'>
                                                    You can correct the details above and submit this branch again. If you believe this was a mistake, reply to this email and our team will take another look.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>

                            <tr>
                                <td align='center' style='padding-top:24px;'>
                                    <p style='margin:0;font-size:11px;color:#94a3b8;'>AMUMA Care — this is an automated message.</p>
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
