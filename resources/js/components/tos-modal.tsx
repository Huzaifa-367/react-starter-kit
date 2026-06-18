import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { ShieldCheck, ArrowRight, ExternalLink } from 'lucide-react';

export function TosModal() {
  const { tos } = usePage<any>().props;
  const [loading, setLoading] = useState(false);

  if (!tos || !tos.acceptance_required) {
    return null;
  }

  const handleAccept = () => {
    setLoading(true);
    router.post(route('terms.accept'), {}, {
      onFinish: () => setLoading(false)
    });
  };

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div className="w-full max-w-md rounded-2xl border border-gray-100 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-950 text-center animate-in fade-in zoom-in-95 duration-200">
        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400 mb-4">
          <ShieldCheck className="h-6 w-6" />
        </div>
        
        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">Terms of Service Update</h3>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
          We have updated our Terms of Service and Privacy Policy. Please review and accept them to continue using our services.
        </p>

        {/* Links */}
        <div className="flex justify-center gap-4 mb-6 text-xs font-semibold">
          <a
            href={tos.tos_url || '#'}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
          >
            Terms of Service
            <ExternalLink className="h-3.5 w-3.5" />
          </a>
          <span className="text-gray-300 dark:text-gray-700">|</span>
          <a
            href={tos.privacy_url || '#'}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
          >
            Privacy Policy
            <ExternalLink className="h-3.5 w-3.5" />
          </a>
        </div>

        {/* Action Button */}
        <button
          onClick={handleAccept}
          disabled={loading}
          className="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-sm text-white hover:bg-indigo-700 focus:outline-none disabled:opacity-50 transition-colors cursor-pointer"
        >
          {loading ? 'Processing...' : 'I Accept the Terms & Conditions'}
          {!loading && <ArrowRight className="h-4 w-4" />}
        </button>
      </div>
    </div>
  );
}

export default TosModal;
