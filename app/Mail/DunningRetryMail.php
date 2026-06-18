<?php

namespace App\Mail;

class DunningRetryMail extends BaseMailable
{
    public $templateKey = 'dunning_retry';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Payment Retry Scheduled';
    }
}
