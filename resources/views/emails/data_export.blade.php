@extends('emails.layout')

@section('content')
    <h2>Hello {{ $user_name }},</h2>
    <p>Your requested personal data export has been processed successfully.</p>
    <p>Please click the button below to download your data file:</p>
    <div class="button-container">
        <a href="{{ $download_url }}" class="button">Download GDPR JSON Export</a>
    </div>
    <p>This download link is valid for 24 hours. For security reasons, the file will be deleted after that time.</p>
    <p>Thank you!</p>
@endsection
