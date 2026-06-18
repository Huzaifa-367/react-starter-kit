@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>This is to let you know that we have scheduled an automatic retry for your failed subscription payment.</p>
    <p>Please check your payment information and ensure that your default card has sufficient funds. You can update your payment method directly in your billing center.</p>
    <div class="button-container">
        <a href="{{ url('/billing') }}" class="button">Manage Billing</a>
    </div>
    <p>Thank you!</p>
@endsection
