<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Invoice as StripeInvoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Download the specified invoice as a PDF.
     */
    public function download(Request $request, string $invoiceId)
    {
        $user = $request->user();

        // Get Stripe secret key
        $stripeSecret = Setting::get('stripe_secret') ?: env('STRIPE_SECRET');
        if (!$stripeSecret) {
            abort(500, 'Stripe is not configured.');
        }

        try {
            Stripe::setApiKey($stripeSecret);
            $invoice = StripeInvoice::retrieve($invoiceId);

            // Security check: ensure user owns this invoice
            if ($invoice->customer !== $user->stripe_id) {
                abort(403, 'Unauthorized.');
            }

            // Retrieve invoice lines and details
            $invoiceData = [
                'number' => $invoice->number,
                'date' => \Carbon\Carbon::createFromTimestamp($invoice->created)->toDateString(),
                'due_date' => $invoice->due_date ? \Carbon\Carbon::createFromTimestamp($invoice->due_date)->toDateString() : null,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'items' => [],
                'subtotal' => number_format($invoice->subtotal / 100, 2),
                'tax' => number_format($invoice->tax / 100, 2),
                'total' => number_format($invoice->total / 100, 2),
                'amount_paid' => number_format($invoice->amount_paid / 100, 2),
                'currency' => strtoupper($invoice->currency),
                'status' => $invoice->status,
                'branding' => [
                    'logo' => Setting::get('app_logo_url'),
                    'name' => Setting::get('app_name', 'Laravel SaaS'),
                    'address' => Setting::get('app_address', '123 SaaS St, Silicon Valley, CA'),
                ],
            ];

            foreach ($invoice->lines->data as $line) {
                $invoiceData['items'][] = [
                    'description' => $line->description ?? 'Subscription Service',
                    'amount' => number_format($line->amount / 100, 2),
                ];
            }

            // Render view to PDF using Dompdf.
            if (view()->exists('pdf.invoice')) {
                $pdf = Pdf::loadView('pdf.invoice', $invoiceData);
            } else {
                // Fallback basic HTML structure
                $html = $this->getFallbackHtml($invoiceData);
                $pdf = Pdf::loadHTML($html);
            }

            return $pdf->download("invoice_{$invoice->number}.pdf");

        } catch (\Exception $e) {
            Log::error("Invoice download failed: " . $e->getMessage());
            abort(500, "Failed to generate invoice: " . $e->getMessage());
        }
    }

    /**
     * Get basic fallback HTML for the invoice.
     */
    private function getFallbackHtml(array $data): string
    {
        $itemsHtml = '';
        foreach ($data['items'] as $item) {
            $itemsHtml .= "<tr>
                <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$item['description']}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>{$data['currency']} \${$item['amount']}</td>
            </tr>";
        }

        return "
        <html>
        <head>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #555; }
                .invoice-box { max-width: 800px; margin: auto; padding: 30px; font-size: 16px; line-height: 24px; }
                .title { font-size: 28px; font-weight: bold; color: #333; }
            </style>
        </head>
        <body>
            <div class='invoice-box'>
                <table cellpadding='0' cellspacing='0' style='width: 100%;'>
                    <tr>
                        <td>
                            <span class='title'>{$data['branding']['name']}</span><br>
                            {$data['branding']['address']}
                        </td>
                        <td style='text-align: right;'>
                            <strong>Invoice #:</strong> {$data['number']}<br>
                            <strong>Date:</strong> {$data['date']}
                        </td>
                    </tr>
                    <tr style='height: 40px;'><td colspan='2'></td></tr>
                    <tr>
                        <td>
                            <strong>Invoiced To:</strong><br>
                            {$data['customer_name']}<br>
                            {$data['customer_email']}
                        </td>
                        <td style='text-align: right;'>
                            <strong>Status:</strong> " . strtoupper($data['status']) . "
                        </td>
                    </tr>
                </table>
                <table style='width: 100%; margin-top: 40px; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #eee;'>
                            <th style='padding: 8px; text-align: left; border-bottom: 2px solid #ddd;'>Description</th>
                            <th style='padding: 8px; text-align: right; border-bottom: 2px solid #ddd;'>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>
                <table style='width: 100%; margin-top: 20px;'>
                    <tr>
                        <td></td>
                        <td style='width: 40%; text-align: right;'>
                            Subtotal: {$data['currency']} \${$data['subtotal']}<br>
                            Tax: {$data['currency']} \${$data['tax']}<br>
                            <strong>Total: {$data['currency']} \${$data['total']}</strong><br>
                            Amount Paid: {$data['currency']} \${$data['amount_paid']}
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>";
    }
}
