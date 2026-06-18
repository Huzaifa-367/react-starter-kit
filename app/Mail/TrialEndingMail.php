<?php

namespace App\Mail;

class TrialEndingMail extends BaseMailable
{
    public $templateKey = 'trial_ending';

    protected function getTemplateVariables(): array
    {
        $sub = $this->user->getActiveSubscription();
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'plan_name' => $this->options['plan_name'] ?? $sub?->plan?->name ?? 'Free Plan',
            'ends_in' => $this->options['ends_in'] ?? ($sub?->trial_ends_at ? $sub->trial_ends_at->diffForHumans() : '3 days'),
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Your Free Trial is Ending Soon';
    }
}
