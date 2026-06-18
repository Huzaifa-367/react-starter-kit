@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>We received a request to verify your identity. Please use the following one-time verification code:</p>
    <div class="otp-box">{{ $otp_code }}</div>
    <p>This code is valid for 10 minutes. If you did not request this, you can safely ignore this email.</p>
@endsection
