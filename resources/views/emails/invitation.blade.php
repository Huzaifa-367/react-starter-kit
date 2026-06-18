@extends('emails.layout')

@section('content')
    <h2>Hello!</h2>
    <p>You have been invited to join our platform. Click the button below to accept the invitation and set up your account:</p>
    <div class="button-container">
        <a href="{{ $invite_link }}" class="button">Accept Invitation</a>
    </div>
    <p>If you did not expect this invitation, you can ignore this email.</p>
    <p>Thank you!</p>
@endsection
