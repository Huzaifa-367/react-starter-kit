<?php

namespace App\Mail;

class SubscriptionCanceledMail extends BaseMailable
{
    public $templateKey = 'subscription_canceled';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Subscription Canceled';
    }
}
