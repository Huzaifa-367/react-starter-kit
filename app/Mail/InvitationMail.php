<?php

namespace App\Mail;

class InvitationMail extends BaseMailable
{
    public $templateKey = 'invitation';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'invite_link' => $this->options['invite_link'] ?? $this->options['link'] ?? url('/register'),
        ];
    }

    protected function getFallbackSubject(): string
    {
        return "You've Been Invited!";
    }
}
