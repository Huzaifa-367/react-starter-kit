<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProgress extends Model
{
    use HasFactory;

    protected $table = 'onboarding_progress';

    protected $fillable = [
        'user_id',
        'step_email_verified',
        'step_plan_selected',
        'step_profile_completed',
        'step_notifications_enabled',
        'step_first_project',
        'completed_at',
        'dismissed_at',
    ];

    protected $casts = [
        'step_email_verified' => 'boolean',
        'step_plan_selected' => 'boolean',
        'step_profile_completed' => 'boolean',
        'step_notifications_enabled' => 'boolean',
        'step_first_project' => 'boolean',
        'completed_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completionPercentage(): int
    {
        $steps = [
            $this->step_email_verified,
            $this->step_plan_selected,
            $this->step_profile_completed,
            $this->step_notifications_enabled,
            $this->step_first_project,
        ];

        $completed = count(array_filter($steps));

        return (int) (($completed / count($steps)) * 100);
    }

    public function isComplete(): bool
    {
        return $this->step_email_verified &&
               $this->step_plan_selected &&
               $this->step_profile_completed &&
               $this->step_notifications_enabled &&
               $this->step_first_project;
    }
}
