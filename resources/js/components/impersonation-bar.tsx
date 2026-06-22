import React from 'react';
import { usePage, router } from '@inertiajs/react';
import { AlertCircle, LogOut } from 'lucide-react';
import { stop } from '@/routes/admin/impersonation';

export function ImpersonationBar() {
  const { is_impersonating, impersonating_as } = usePage<any>().props;

  if (!is_impersonating) {
    return null;
  }

  const stopImpersonating = () => {
    router.post(stop().url, {}, {
      onSuccess: () => {
        // Redirect back to admin dashboard
        window.location.href = '/admin/users';
      }
    });
  };

  return (
    <>
      <style>{`
        body {
          padding-top: 40px !important;
        }
        [data-slot="sidebar"] > div.fixed {
          top: 40px !important;
          height: calc(100vh - 40px) !important;
        }
        .fixed.inset-y-0.z-10 {
          top: 40px !important;
          height: calc(100vh - 40px) !important;
        }
        div:has(> [data-sidebar="sidebar"]) {
          top: 40px !important;
          height: calc(100vh - 40px) !important;
        }
        [data-slot="sidebar"] > div.relative {
          height: calc(100vh - 40px) !important;
        }
        [data-slot="sidebar-inset"] {
          min-height: calc(100vh - 40px) !important;
        }
        header.fixed.top-0 {
          top: 40px !important;
        }
      `}</style>
      <div className="bg-amber-600 border-b border-amber-700 text-white px-4 h-10 fixed top-0 inset-x-0 z-50 flex items-center">
        <div className="mx-auto flex w-full max-w-7xl items-center justify-between">
          <div className="flex items-center gap-2">
            <AlertCircle className="h-5 w-5 animate-pulse" />
            <span className="text-sm font-medium">
              Impersonation mode: You are currently viewing as <strong>{impersonating_as || 'User'}</strong>
            </span>
          </div>
          <button
            onClick={stopImpersonating}
            className="inline-flex items-center gap-1.5 rounded bg-amber-800/80 hover:bg-amber-900 px-3 py-1.5 text-xs font-bold uppercase tracking-wider transition-colors focus:outline-none cursor-pointer"
          >
            <LogOut className="h-3.5 w-3.5" />
            Exit
          </button>
        </div>
      </div>
    </>
  );
}

export default ImpersonationBar;
