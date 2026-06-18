<?php

namespace App\Mail;

class GraceWarningMail extends BaseMailable
{
    public $templateKey = 'grace_warning';

    protected function getTemplateVariables(): array
    {
        $sub = $this->user->getActiveSubscription();
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'grace_days_left' => $this->options['grace_days_left'] ?? $this->options['days'] ?? $sub?->plan?->grace_days ?? 7,
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Payment Failed - Action Required to Keep Access';
    }
}
