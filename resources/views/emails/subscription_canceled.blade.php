@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>We are sorry to see you go. Your subscription has been canceled per your request.</p>
    <p>You will continue to have access to your premium features until the end of your current billing period. After that, your account will be downgraded to the Free starter plan.</p>
    <p>If you change your mind, you can resume your subscription at any time from your billing dashboard.</p>
    <div class="button-container">
        <a href="{{ url('/billing') }}" class="button">Go to Billing</a>
    </div>
    <p>Thank you for using {{ $appName }}!</p>
@endsection
