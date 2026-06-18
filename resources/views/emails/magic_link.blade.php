@extends('emails.layout')

@section('content')
    <h2>Hello,</h2>
    <p>You requested a magic link to sign in to your account. Click the button below to sign in automatically:</p>
    <div class="button-container">
        <a href="{{ $magic_link }}" class="button">Sign In Passwordless</a>
    </div>
    <p>This link is valid for 15 minutes. If you did not request this link, you can safely ignore this email.</p>
    <p>Thank you!</p>
@endsection
