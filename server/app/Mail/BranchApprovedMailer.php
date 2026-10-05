<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BranchApprovedMailer extends Mailable
{
    public function __construct(
        public string $recipientName,
        public string $branchName,
        public string $agencyName,
        public ?string $planName = null,
        public ?string $planType = null,
        public ?string $validUntil = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AMUMA subscription has been approved'
        );
    }

    public function content(): Content
    {
        $year = date('Y');
        $logoUrl = config('mail.logo_url');

        $logo = $logoUrl
            ? "<img src='{$logoUrl}' width='160' alt='AMUMA' style='display:block;width:160px;max-width:160px;height:auto;border:0;outline:none;text-decoration:none;font-size:22px;font-weight:800;color:#3182ED;'>"
            : "<p style='margin:0;font-size:22px;font-weight:800;letter-spacing:1px;color:#3182ED;'>AMUMA</p>";

        $details = [
            'Plan' => $this->planName,
            'Type' => $this->planType
                ? (strtolower($this->planType) === 'sme' ? 'SME' : ucfirst($this->planType))
                : null,
            'Valid until' => $this->validUntil,
        ];

        $rows = '';

        foreach (array_filter($details) as $label => $value) {
            $value = e($value);
            $rows .= "
                                                    <tr>
                                                        <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>{$label}</td>
                                                        <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$value}</td>
                                                    </tr>";
        }

        $detailsTable = $rows
            ? "<table width='100%' cellpadding='0' cellspacing='0'>{$rows}
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
                                                <p style='margin:0 0 8px;display:inline-block;padding:4px 12px;border-radius:999px;background:#EAF2FE;color:#1E68D1;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;'>Approved</p>
                                                <p style='margin:12px 0 6px;font-size:22px;font-weight:700;color:#0f172a;'>Subscription approved</p>
                                                <p style='margin:0;font-size:14px;color:#64748b;line-height:1.7;'>
                                                    Hi <strong style='color:#0f172a;'>{$this->recipientName}</strong> — your subscription for <strong style='color:#0f172a;'>{$this->branchName}</strong> has been approved and is now active.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:28px 48px;'>
                                                {$detailsTable}

                                                <table width='100%' cellpadding='0' cellspacing='0' style='background:#EAF2FE;border-radius:10px;margin-top:20px;'>
                                                    <tr>
                                                        <td style='padding:14px 18px;'>
                                                            <p style='margin:0;font-size:13px;color:#1E68D1;line-height:1.6;'>You can now sign in to AMUMA and start setting up your branch.</p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                </td>
                            </tr>

                            <tr>
                                <td align='center' style='padding:28px 0 0;'>
                                    <p style='margin:0;font-size:12px;color:#94a3b8;'>© {$year} AMUMA. All rights reserved.</p>
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
