@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>We regret to inform you that your account has been suspended by our administration team.</p>
    <p><strong>Reason for suspension:</strong> {{ $reason }}</p>
    <p>As a result, your active sessions have been terminated, and your access to the platform has been restricted. If you believe this is an error or would like to appeal, please contact our support team.</p>
@endsection
