@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>Good news! Your subscription to the plan <strong>{{ $plan_name }}</strong> has been successfully renewed.</p>
    <p>Your payment was processed, and your access is fully active for the next billing cycle. No further action is required.</p>
    <div class="button-container">
        <a href="{{ url('/dashboard') }}" class="button">Go to Dashboard</a>
    </div>
    <p>Thank you for staying with us!</p>
@endsection
