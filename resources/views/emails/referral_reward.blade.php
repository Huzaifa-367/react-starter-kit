@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>Great news! One of your referrals has successfully subscribed to a paid plan.</p>
    <p>As a thank you, we have credited your account with a referral reward of <strong>{{ $reward_type === 'credit' ? '$' . $reward_value : $reward_value . '% off' }}</strong>.</p>
    <p>This reward has been automatically applied to your account or next billing cycle.</p>
    <div class="button-container">
        <a href="{{ url('/dashboard') }}" class="button">Go to Dashboard</a>
    </div>
    <p>Thank you for sharing {{ $appName }} with others!</p>
@endsection
