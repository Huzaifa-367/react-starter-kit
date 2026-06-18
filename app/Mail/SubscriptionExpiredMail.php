<?php

namespace App\Mail;

class SubscriptionExpiredMail extends BaseMailable
{
    public $templateKey = 'subscription_expired';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Your Subscription Has Expired';
    }
}
