@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>We hope you are enjoying your trial of <strong>{{ $plan_name }}</strong>!</p>
    <p>This is a quick reminder that your trial is ending in <strong>{{ $ends_in }}</strong>. To keep uninterrupted access to all of our features, please verify or update your billing details.</p>
    <div class="button-container">
        <a href="{{ url('/billing') }}" class="button">Manage Billing</a>
    </div>
    <p>Thank you for being part of {{ $appName }}!</p>
@endsection
