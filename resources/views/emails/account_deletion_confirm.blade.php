@extends('emails.layout')

@section('content')
    <h2>Hello,</h2>
    <p>We received a request to delete your account. This action is permanent and will remove all of your projects and data after 30 days.</p>
    <p>To confirm this deletion, please click the button below:</p>
    <div class="button-container">
        <a href="{{ $confirm_url }}" class="button" style="background-color: #ef4444;">Confirm Account Deletion</a>
    </div>
    <p>This confirmation link is valid for 24 hours. If you did not request this, please contact support immediately.</p>
    <p>Thank you,</p>
@endsection
