import React from 'react';
import { usePage, router } from '@inertiajs/react';
import { AlertCircle, LogOut } from 'lucide-react';

export function ImpersonationBar() {
  const { is_impersonating, impersonating_as } = usePage<any>().props;

  if (!is_impersonating) {
    return null;
  }

  const stopImpersonating = () => {
    router.post(route('admin.impersonation.stop'), {}, {
      onSuccess: () => {
        // Redirect back to admin dashboard
        window.location.href = '/admin/users';
      }
    });
  };

  return (
    <div className="bg-amber-600 border-b border-amber-700 text-white px-4 py-2 relative z-50">
      <div className="mx-auto flex max-w-7xl items-center justify-between">
        <div className="flex items-center gap-2">
          <AlertCircle className="h-5 w-5 animate-pulse" />
          <span className="text-sm font-medium">
            Impersonation mode: You are currently viewing as <strong>{impersonating_as || 'User'}</strong>
          </span>
        </div>
        <button
          onClick={stopImpersonating}
          className="inline-flex items-center gap-1.5 rounded bg-amber-800/80 hover:bg-amber-900 px-3 py-1 text-xs font-bold uppercase tracking-wider transition-colors focus:outline-none"
        >
          <LogOut className="h-3.5 w-3.5" />
          Exit
        </button>
      </div>
    </div>
  );
}

export default ImpersonationBar;
