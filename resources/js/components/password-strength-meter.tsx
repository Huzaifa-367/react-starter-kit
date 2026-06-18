import React from 'react';
import { Check, X } from 'lucide-react';

interface PasswordStrengthMeterProps {
  password?: string;
}

export function PasswordStrengthMeter({ password = '' }: PasswordStrengthMeterProps) {
  const requirements = [
    { label: 'At least 8 characters', test: (val: string) => val.length >= 8 },
    { label: 'Contains uppercase letter', test: (val: string) => /[A-Z]/.test(val) },
    { label: 'Contains lowercase letter', test: (val: string) => /[a-z]/.test(val) },
    { label: 'Contains a number', test: (val: string) => /[0-9]/.test(val) },
    { label: 'Contains special character', test: (val: string) => /[^A-Za-z0-9]/.test(val) },
  ];

  const metCount = requirements.filter(req => req.test(password)).length;
  
  const getStrengthLabel = () => {
    if (password.length === 0) return 'Too short';
    if (metCount <= 2) return 'Weak';
    if (metCount <= 4) return 'Medium';
    return 'Strong';
  };

  const getStrengthColor = () => {
    if (password.length === 0) return 'bg-gray-200 dark:bg-gray-800';
    if (metCount <= 2) return 'bg-rose-500';
    if (metCount <= 4) return 'bg-amber-500';
    return 'bg-emerald-500';
  };

  const strengthPercentage = password.length === 0 ? 0 : (metCount / requirements.length) * 100;

  return (
    <div className="mt-2 space-y-2">
      {/* Strength Bar */}
      <div>
        <div className="flex justify-between text-[11px] font-semibold mb-1">
          <span className="text-gray-500">Password Strength</span>
          <span className={
            metCount <= 2 ? 'text-rose-500' : metCount <= 4 ? 'text-amber-500' : 'text-emerald-500'
          }>
            {getStrengthLabel()}
          </span>
        </div>
        <div className="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
          <div
            className={`h-full transition-all duration-300 ${getStrengthColor()}`}
            style={{ width: `${strengthPercentage}%` }}
          />
        </div>
      </div>

      {/* Requirements List */}
      {password.length > 0 && (
        <ul className="text-[11px] space-y-1 text-gray-500 grid grid-cols-1 sm:grid-cols-2 gap-x-2">
          {requirements.map((req, idx) => {
            const isMet = req.test(password);
            return (
              <li key={idx} className="flex items-center gap-1">
                {isMet ? (
                  <Check className="h-3.5 w-3.5 text-emerald-500 flex-shrink-0" />
                ) : (
                  <X className="h-3.5 w-3.5 text-gray-300 dark:text-gray-700 flex-shrink-0" />
                )}
                <span className={isMet ? 'text-gray-700 dark:text-gray-300' : ''}>
                  {req.label}
                </span>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}

export default PasswordStrengthMeter;
