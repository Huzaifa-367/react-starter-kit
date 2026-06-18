@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>This is a friendly reminder that your subscription to <strong>{{ $plan_name }}</strong> is scheduled to renew on <strong>{{ $renews_on }}</strong>.</p>
    <p>The renewal price of <strong>{{ $price }}</strong> will be automatically charged to your default payment method on file.</p>
    <p>If you'd like to make changes to your plan or update your billing method, please visit the billing center.</p>
    <div class="button-container">
        <a href="{{ url('/billing') }}" class="button">Manage Billing</a>
    </div>
    <p>Thank you for using {{ $appName }}!</p>
@endsection
