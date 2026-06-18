<?php

namespace App\Mail;

class AccountSuspendedMail extends BaseMailable
{
    public $templateKey = 'account_suspended';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'reason' => $this->options['reason'] ?? $this->user->suspended_reason ?? 'Violation of terms',
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Your Account Has Been Suspended';
    }
}
