<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingDecisionMailer extends Mailable
{
    public function __construct(
        public string $referenceId,
        public string $decision,
        public string $recipientName,
        public string $branchName,
        public ?string $patientName = null,
        public ?string $reason = null,
    ) {}

    private function isApproved(): bool
    {
        return $this->decision === 'approved';
    }

    public function envelope(): Envelope
    {
        $verb = $this->isApproved() ? 'approved' : 'declined';

        return new Envelope(
            subject: "Your booking {$this->referenceId} has been {$verb}"
        );
    }

    public function content(): Content
    {
        $approved = $this->isApproved();

        $accent = $approved ? '#0E7C7B' : '#b91c1c';
        $accentSoft = $approved ? '#e7f5f5' : '#fef2f2';
        $heading = $approved ? 'Booking approved' : 'Booking declined';

        $lead = $approved
            ? "Good news — your booking with <strong style='color:{$accent};'>{$this->branchName}</strong> has been approved."
            : "Your booking with <strong style='color:{$accent};'>{$this->branchName}</strong> could not be accepted.";

        $followUp = $approved
            ? 'The branch will be in touch with the next steps. You can view the details anytime from your AMUMA portal.'
            : 'If a payment was made, a refund has been requested and will be processed back to your original payment method.';

        $patientRow = $this->patientName
            ? "
            <tr>
                <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Patient</td>
                <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$this->patientName}</td>
            </tr>"
            : '';

        $reasonBlock = (!$approved && $this->reason)
            ? "
            <table width='100%' cellpadding='0' cellspacing='0' style='background:{$accentSoft};border-radius:10px;margin-top:20px;'>
                <tr>
                    <td style='padding:14px 18px;'>
                        <p style='margin:0 0 4px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{$accent};'>Reason</p>
                        <p style='margin:0;font-size:13px;color:#475569;line-height:1.6;'>{$this->reason}</p>
                    </td>
                </tr>
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
                                    <p style='margin:0;font-size:11px;color:#94a3b8;letter-spacing:6px;text-transform:uppercase;'>AMUMA</p>
                                </td>
                            </tr>

                            <tr>
                                <td style='background:#ffffff;border-radius:20px;overflow:hidden;'>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:40px 48px 28px;border-bottom:1px solid #f1f5f9;'>
                                                <p style='margin:0 0 8px;display:inline-block;padding:4px 12px;border-radius:999px;background:{$accentSoft};color:{$accent};font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;'>{$this->decision}</p>
                                                <p style='margin:12px 0 6px;font-size:22px;font-weight:700;color:#0f172a;'>{$heading}</p>
                                                <p style='margin:0;font-size:14px;color:#64748b;line-height:1.7;'>
                                                    Hi <strong style='color:#0f172a;'>{$this->recipientName}</strong> — {$lead}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td style='padding:28px 48px;'>
                                                <table width='100%' cellpadding='0' cellspacing='0'>
                                                    <tr>
                                                        <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Reference</td>
                                                        <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;font-family:\"Courier New\",monospace;'>{$this->referenceId}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding:6px 0;font-size:13px;color:#94a3b8;'>Branch</td>
                                                        <td align='right' style='padding:6px 0;font-size:13px;font-weight:600;color:#0f172a;'>{$this->branchName}</td>
                                                    </tr>
                                                    {$patientRow}
                                                </table>

                                                {$reasonBlock}

                                                <p style='margin:24px 0 0;font-size:13px;color:#64748b;line-height:1.7;'>{$followUp}</p>
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
