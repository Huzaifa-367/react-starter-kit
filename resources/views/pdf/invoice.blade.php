<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.5;
        }
        .invoice-container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 50px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            text-align: right;
            letter-spacing: -0.025em;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 40px;
        }
        .meta-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: bold;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .meta-value {
            font-size: 14px;
            color: #1f2937;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: bold;
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e5e7eb;
        }
        .items-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            color: #4b5563;
        }
        .totals-table {
            width: 100%;
            margin-top: 20px;
        }
        .totals-label {
            text-align: right;
            padding: 6px 16px;
            color: #6b7280;
        }
        .totals-value {
            text-align: right;
            padding: 6px 16px;
            font-weight: 600;
            color: #111827;
            width: 150px;
        }
        .totals-grand {
            font-size: 18px;
            color: #4F46E5;
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 9999px;
        }
        .badge-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-unpaid {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 60px;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    @if(!empty($branding['logo']))
                        <img src="{{ public_path($branding['logo']) }}" class="logo" alt="{{ $branding['name'] }}">
                    @else
                        <span class="company-name">{{ $branding['name'] }}</span>
                    @endif
                    <div style="color: #6b7280; margin-top: 5px;">
                        {!! nl2br(e($branding['address'])) !!}
                    </div>
                </td>
                <td style="vertical-align: top; text-align: right;">
                    <div class="invoice-title">INVOICE</div>
                    <div style="margin-top: 10px;">
                        <span class="badge {{ $status === 'paid' ? 'badge-paid' : 'badge-unpaid' }}">
                            {{ $status }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Metadata -->
        <table class="meta-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="meta-label">Billed To</div>
                    <div class="meta-value" style="font-weight: bold;">{{ $customer_name }}</div>
                    <div class="meta-value">{{ $customer_email }}</div>
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <table cellpadding="0" cellspacing="0" style="float: right;">
                        <tr>
                            <td style="padding: 0 15px 10px 0;"><div class="meta-label">Invoice Number</div><div class="meta-value">{{ $number }}</div></td>
                            <td style="padding: 0 0 10px 0; text-align: right;"><div class="meta-label">Date Issued</div><div class="meta-value">{{ $date }}</div></td>
                        </tr>
                        @if(!empty($due_date))
                        <tr>
                            <td style="padding: 0 15px 0 0;"><div class="meta-label">Due Date</div><div class="meta-value">{{ $due_date }}</div></td>
                            <td></td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <!-- Items -->
        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right; width: 150px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['description'] }}</td>
                    <td style="text-align: right; font-weight: 500; color: #111827;">{{ $currency }} ${{ $item['amount'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 60%;"></td>
                <td style="width: 40%;">
                    <table cellpadding="0" cellspacing="0" style="width: 100%;">
                        <tr>
                            <td class="totals-label">Subtotal</td>
                            <td class="totals-value">{{ $currency }} ${{ $subtotal }}</td>
                        </tr>
                        @if($tax > 0)
                        <tr>
                            <td class="totals-label">Tax</td>
                            <td class="totals-value">{{ $currency }} ${{ $tax }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="totals-label totals-grand">Total</td>
                            <td class="totals-value totals-grand">{{ $currency }} ${{ $total }}</td>
                        </tr>
                        <tr>
                            <td class="totals-label" style="font-weight: 500;">Amount Paid</td>
                            <td class="totals-value" style="font-weight: 500; color: #065f46;">{{ $currency }} ${{ $amount_paid }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            Thank you for your business. For support, please contact {{ \App\Models\Setting::get('brand_support_email', 'support@example.com') }}.
        </div>
    </div>
</body>
</html>
