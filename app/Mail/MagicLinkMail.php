<?php

namespace App\Mail;

class MagicLinkMail extends BaseMailable
{
    public $templateKey = 'magic_link';
    public $signedUrl;

    public function __construct(string $signedUrl, array $options = [])
    {
        // For MagicLinkMail, the signed URL is passed as the first parameter.
        // We initialize the base class with null user context.
        parent::__construct(null, $options);
        $this->signedUrl = $signedUrl;
    }

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => 'User',
            'name' => 'User',
            'invite_link' => $this->signedUrl,
            'magic_link' => $this->signedUrl,
            'confirm_url' => $this->signedUrl,
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Your Magic Login Link';
    }
}
