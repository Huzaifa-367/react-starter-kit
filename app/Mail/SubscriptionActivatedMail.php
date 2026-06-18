<?php

namespace App\Mail;

class SubscriptionActivatedMail extends BaseMailable
{
    public $templateKey = 'subscription_activated';

    protected function getTemplateVariables(): array
    {
        $sub = $this->user->getActiveSubscription();
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'plan_name' => $this->options['plan_name'] ?? $sub?->plan?->name ?? 'Free Plan',
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Subscription Activated!';
    }
}
