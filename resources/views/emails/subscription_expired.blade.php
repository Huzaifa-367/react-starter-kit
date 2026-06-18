@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>Your subscription has expired, and your account has been downgraded to the Free plan.</p>
    <p>Your existing projects and data are preserved, but you won't be able to access premium features or create new items until you subscribe again.</p>
    <div class="button-container">
        <a href="{{ url('/pricing') }}" class="button">View Plans</a>
    </div>
    <p>We hope to see you back soon!</p>
@endsection
