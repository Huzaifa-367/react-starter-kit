<?php

namespace App\Mail;

class RenewalUpcomingMail extends BaseMailable
{
    public $templateKey = 'renewal_upcoming';

    protected function getTemplateVariables(): array
    {
        $sub = $this->user->getActiveSubscription();
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'plan_name' => $this->options['plan_name'] ?? $sub?->plan?->name ?? 'Free Plan',
            'renews_on' => $this->options['renews_on'] ?? ($sub?->ends_at ? $sub->ends_at->toDateString() : 'N/A'),
            'price' => $this->options['price'] ?? ($sub?->plan ? '$' . $sub->plan->price : '$0.00'),
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Upcoming Subscription Renewal';
    }
}
