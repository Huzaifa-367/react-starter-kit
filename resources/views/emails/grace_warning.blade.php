@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>We attempted to renew your subscription, but the payment failed. As a result, your account has entered a grace period.</p>
    <p>You have <strong>{{ $grace_days_left }} days</strong> left to update your payment details before your access to premium features is suspended and you are downgraded.</p>
    <div class="button-container">
        <a href="{{ url('/billing') }}" class="button">Update Payment Method</a>
    </div>
    <p>If you have already updated your details, we will attempt the charge again shortly.</p>
    <p>Thank you!</p>
@endsection
