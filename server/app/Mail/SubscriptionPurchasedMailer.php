<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SubscriptionPurchasedMailer extends Mailable
{
    public function __construct(
        public string $recipientName,
        public string $planName,
        public string $branchName,
        public float $amount,
        public string $billingInterval,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AMUMA subscription request has been received'
        );
    }

    public function content(): Content
    {
        $formattedAmount = '₱' . number_format($this->amount, 2);

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
                                                <p style='margin:0 0 8px;display:inline-block;padding:4px 12px;border-radius:999px;background:#EAF2FE;color:#1E68D1;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;'>Payment received</p>
                                                <p style='margin:12px 0 6px;font-size:22px;font-weight:700;color:#0f172a;'>Subscription request received</p>
                                                <p style='margin:0;font-size:14px;color:#64748b;line-height:1.7;'>
                                                    Hi <strong style='color:#0f172a;'>{$this->recipientName}</strong> — we've received your payment for <strong style='color:#0f172a;'>{$this->branchName}</strong>.
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:28px 48px;'>
                                                <table width='100%' cellpadding='0' cellspacing='0'>
                                                    <tr>
                                                        <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Plan</td>
                                                        <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$this->planName}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Billing</td>
                                                        <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;text-transform:capitalize;'>{$this->billingInterval}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Amount paid</td>
                                                        <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$formattedAmount}</td>
                                                    </tr>
                                                </table>

                                                <table width='100%' cellpadding='0' cellspacing='0' style='background:#EAF2FE;border-radius:10px;margin-top:20px;'>
                                                    <tr>
                                                        <td style='padding:14px 18px;'>
                                                            <p style='margin:0;font-size:13px;color:#1E68D1;line-height:1.6;'>We&#39;ll verify your subscription and notify you of your request&#39;s status within 2-3 business days.</p>
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
