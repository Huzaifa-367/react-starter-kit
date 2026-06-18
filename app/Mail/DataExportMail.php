<?php

namespace App\Mail;

class DataExportMail extends BaseMailable
{
    public $templateKey = 'data_export';

    protected function getTemplateVariables(): array
    {
        return [
            'user_name' => $this->user->name,
            'name' => $this->user->name,
            'invite_link' => $this->options['download_url'] ?? url('/'),
            'download_url' => $this->options['download_url'] ?? url('/'),
        ];
    }

    protected function getFallbackSubject(): string
    {
        return 'Your Personal Data Export is Ready';
    }
}
