<?php

namespace App\Mail;

class OtpMail extends BaseMailable
{
    public $templateKey = 'otp';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'otp_code' => $this->options['code'] ?? $this->options['otp_code'] ?? '000000',
            'code' => $this->options['code'] ?? $this->options['otp_code'] ?? '000000',
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Your Verification Code';
    }
}
