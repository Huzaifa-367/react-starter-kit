<?php

namespace App\Mail;

class AccountDeletionConfirmMail extends BaseMailable
{
    public $templateKey = 'account_deletion_confirm';
    public $confirmUrl;

    public function __construct(string $confirmUrl, array $options = [])
    {
        // For AccountDeletionConfirmMail, the confirmation link is the first argument.
        parent::__construct(null, $options);
        $this->confirmUrl = $confirmUrl;
    }

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => 'User',
            'name' => 'User',
            'confirm_url' => $this->confirmUrl,
            'invite_link' => $this->confirmUrl,
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Confirm Account Deletion';
    }
}
