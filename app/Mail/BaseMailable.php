<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Support\HtmlString;

abstract class BaseMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $options;
    public $templateKey;

    /**
     * Create a new message instance.
     */
    public function __construct($user, array $options = [])
    {
        $this->user = $user;
        $this->options = $options;
        $this->queue = 'emails';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Get branding settings
        $appName = Setting::get('brand_app_name', config('app.name', 'SaaS App'));
        $logoUrl = Setting::get('brand_logo_url', '/images/logo.png');
        $supportEmail = Setting::get('brand_support_email', 'support@example.com');
        $footerText = Setting::get('brand_footer_text', '© ' . date('Y') . ' ' . $appName);
        $primaryColor = Setting::get('brand_primary_color', '#4F46E5');

        // Build variables array for parsing
        $variables = $this->getTemplateVariables();

        // 1. Try to load template from DB
        $template = EmailTemplate::where('key', $this->templateKey)->active()->first();

        if ($template) {
            $subject = $template->subject;
            $html = $template->body_html;

            // Replace variables
            foreach ($variables as $key => $val) {
                $html = str_replace(
                    ['{' . $key . '}', '{{' . $key . '}}', '{{ $' . $key . ' }}'],
                    (string)$val,
                    $html
                );
                $subject = str_replace(
                    ['{' . $key . '}', '{{' . $key . '}}', '{{ $' . $key . ' }}'],
                    (string)$val,
                    $subject
                );
            }

            $this->subject($subject);

            return $this->view('emails.database_fallback', [
                'body_html' => new HtmlString($html),
                'appName' => $appName,
                'logoUrl' => $logoUrl,
                'supportEmail' => $supportEmail,
                'footerText' => $footerText,
                'primaryColor' => $primaryColor,
            ]);
        }

        // 2. Fallback to Blade template
        $this->subject($this->getFallbackSubject());
        
        return $this->view('emails.' . $this->templateKey, array_merge($variables, [
            'appName' => $appName,
            'logoUrl' => $logoUrl,
            'supportEmail' => $supportEmail,
            'footerText' => $footerText,
            'primaryColor' => $primaryColor,
        ]));
    }

    /**
     * Get the variables to replace in the template.
     */
    abstract protected function getTemplateVariables(): array;

    /**
     * Get the fallback subject line.
     */
    abstract protected function getFallbackSubject(): string;
}
