@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>Welcome! Your subscription to the plan <strong>{{ $plan_name }}</strong> has been successfully activated.</p>
    <p>You now have full access to all the features and entitlements of this plan. You can start exploring your dashboard now.</p>
    <div class="button-container">
        <a href="{{ url('/dashboard') }}" class="button">Go to Dashboard</a>
    </div>
    <p>Thank you for choosing {{ $appName }}!</p>
@endsection
