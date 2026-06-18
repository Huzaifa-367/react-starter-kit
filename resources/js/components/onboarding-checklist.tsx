import React, { useState, useEffect } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { CheckCircle2, Circle, ArrowRight, X, Sparkles } from 'lucide-react';

interface OnboardingProgress {
  step_email_verified: boolean;
  step_plan_selected: boolean;
  step_profile_completed: boolean;
  step_notifications_enabled: boolean;
  step_first_project: boolean;
  dismissed_at: string | null;
}

export function OnboardingChecklist() {
  const { onboarding } = usePage<any>().props;

  if (!onboarding) {
    return null;
  }

  const [dismissed, setDismissed] = useState(false);
  const [showConfetti, setShowConfetti] = useState(false);

  const steps = [
    {
      key: 'step_email_verified',
      label: 'Verify Email Address',
      url: '/verify/otp?purpose=email_verify',
      done: onboarding.step_email_verified,
    },
    {
      key: 'step_plan_selected',
      label: 'Select Subscriptions Plan',
      url: '/pricing',
      done: onboarding.step_plan_selected,
    },
    {
      key: 'step_profile_completed',
      label: 'Complete Your Profile Info',
      url: '/settings/profile',
      done: onboarding.step_profile_completed,
    },
    {
      key: 'step_notifications_enabled',
      label: 'Enable Push Notifications',
      url: '#', // Handled by NotificationPrompt or button click
      done: onboarding.step_notifications_enabled,
    },
    {
      key: 'step_first_project',
      label: 'Create Your First Project',
      url: '/dashboard',
      done: onboarding.step_first_project,
    },
  ];

  const completedCount = steps.filter(s => s.done).length;
  const percentage = Math.round((completedCount / steps.length) * 100);

  useEffect(() => {
    const isDismissed = localStorage.getItem('onboarding_dismissed');
    if (isDismissed) {
      setDismissed(true);
    }
  }, []);

  // Trigger confetti if newly completed
  useEffect(() => {
    if (percentage === 100) {
      const shown = sessionStorage.getItem('confetti_shown');
      if (!shown) {
        setShowConfetti(true);
        sessionStorage.setItem('confetti_shown', 'true');
        const timer = setTimeout(() => setShowConfetti(false), 6000);
        return () => clearTimeout(timer);
      }
    }
  }, [percentage]);

  if (dismissed || (percentage === 100 && !showConfetti)) {
    return null;
  }

  const dismiss = () => {
    localStorage.setItem('onboarding_dismissed', 'true');
    setDismissed(true);
  };

  return (
    <div className="relative">
      {/* CSS Confetti Effect */}
      {showConfetti && (
        <div className="fixed inset-0 pointer-events-none z-50 overflow-hidden">
          {[...Array(50)].map((_, i) => {
            const left = Math.random() * 100;
            const size = Math.random() * 8 + 6;
            const delay = Math.random() * 4;
            const color = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-sky-500'][
              Math.floor(Math.random() * 5)
            ];
            return (
              <div
                key={i}
                className={`absolute animate-bounce rounded-full opacity-85 ${color}`}
                style={{
                  left: `${left}%`,
                  width: `${size}px`,
                  height: `${size}px`,
                  top: `-20px`,
                  animation: `fall 4s linear ${delay}s infinite`,
                }}
              />
            );
          })}
          <style>{`
            @keyframes fall {
              0% { transform: translateY(0) rotate(0deg); opacity: 1; }
              100% { transform: translateY(105vh) rotate(720deg); opacity: 0; }
            }
          `}</style>
        </div>
      )}

      {/* Checklist Card */}
      <div className="w-full max-w-md rounded-2xl border border-indigo-100 bg-white p-5 shadow-xl ring-1 ring-black/5 dark:border-gray-800 dark:bg-gray-950 transition-all">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <div className="rounded-lg bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
              <Sparkles className="h-5 w-5" />
            </div>
            <div>
              <h3 className="font-bold text-sm text-gray-900 dark:text-white">Getting Started</h3>
              <p className="text-xs text-gray-500">Complete these steps to set up your account</p>
            </div>
          </div>
          <button
            onClick={dismiss}
            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            title="Dismiss guide"
          >
            <X className="h-4.5 w-4.5" />
          </button>
        </div>

        {/* Progress bar */}
        <div className="mb-4">
          <div className="flex justify-between text-xs font-semibold mb-1">
            <span className="text-indigo-600 dark:text-indigo-400">{percentage}% completed</span>
            <span className="text-gray-500">{completedCount} of {steps.length} steps</span>
          </div>
          <div className="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
            <div
              className="h-full rounded-full bg-indigo-600 transition-all duration-500 ease-out"
              style={{ width: `${percentage}%` }}
            />
          </div>
        </div>

        {/* Steps checklist */}
        <div className="space-y-3">
          {steps.map(step => (
            <div
              key={step.key}
              className={`flex items-center justify-between p-2 rounded-xl transition-colors ${step.done ? 'bg-gray-50/50 dark:bg-gray-900/10' : 'hover:bg-indigo-50/5 dark:hover:bg-indigo-950/5'}`}
            >
              <div className="flex items-center gap-3">
                {step.done ? (
                  <CheckCircle2 className="h-5 w-5 text-emerald-500 flex-shrink-0" />
                ) : (
                  <Circle className="h-5 w-5 text-gray-300 dark:text-gray-700 flex-shrink-0" />
                )}
                <span
                  className={`text-xs font-medium ${step.done ? 'text-gray-400 line-through' : 'text-gray-700 dark:text-gray-300'}`}
                >
                  {step.label}
                </span>
              </div>
              {!step.done && (
                <Link
                  href={step.url}
                  className="flex items-center gap-0.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                  Start
                  <ArrowRight className="h-3.5 w-3.5" />
                </Link>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default OnboardingChecklist;
