<?php

namespace App\Mail;

class SubscriptionRenewedMail extends BaseMailable
{
    public $templateKey = 'subscription_renewed';

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
        return 'Subscription Successfully Renewed!';
    }
}
