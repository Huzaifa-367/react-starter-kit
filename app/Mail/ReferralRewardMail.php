<?php

namespace App\Mail;

class ReferralRewardMail extends BaseMailable
{
    public $templateKey = 'referral_reward';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'reward_value' => $this->options['reward_value'] ?? '10',
            'reward_type' => $this->options['reward_type'] ?? 'credit',
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Referral Reward Earned!';
    }
}
