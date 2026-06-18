import React, { useState, useEffect } from 'react';
usePage;
import { usePage } from '@inertiajs/react';
import { X, Info, AlertTriangle, CheckCircle, AlertOctagon } from 'lucide-react';

interface AnnouncementProps {
  text: string | null;
  type: 'info' | 'warning' | 'success' | 'error';
  active: boolean;
  dismissible: boolean;
}

export function AnnouncementBanner() {
  const { announcement } = usePage<any>().props;
  
  if (!announcement || !announcement.active || !announcement.text) {
    return null;
  }

  const [visible, setVisible] = useState(true);
  const storageKey = `dismissed_announcement_${encodeURIComponent(announcement.text)}`;

  useEffect(() => {
    const isDismissed = localStorage.getItem(storageKey);
    if (isDismissed) {
      setVisible(false);
    }
  }, [storageKey]);

  if (!visible) {
    return null;
  }

  const dismiss = () => {
    if (announcement.dismissible) {
      localStorage.setItem(storageKey, 'true');
      setVisible(false);
    }
  };

  const bannerStyles = {
    info: 'bg-indigo-50 border-indigo-200 text-indigo-800 dark:bg-indigo-950/30 dark:border-indigo-900 dark:text-indigo-300',
    warning: 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-950/30 dark:border-amber-900 dark:text-amber-300',
    success: 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-900 dark:text-emerald-300',
    error: 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/30 dark:border-rose-900 dark:text-rose-300',
  };

  const Icon = {
    info: Info,
    warning: AlertTriangle,
    success: CheckCircle,
    error: AlertOctagon,
  }[announcement.type] || Info;

  return (
    <div className={`border-b px-4 py-2.5 transition-all duration-300 ${bannerStyles[announcement.type] || bannerStyles.info}`}>
      <div className="mx-auto flex max-w-7xl items-center justify-between">
        <div className="flex flex-1 items-center justify-center sm:justify-start">
          <Icon className="h-5 w-5 flex-shrink-0 mr-2" />
          <span className="text-center text-sm font-medium">{announcement.text}</span>
        </div>
        {announcement.dismissible && (
          <button
            onClick={dismiss}
            type="button"
            className="ml-4 inline-flex flex-shrink-0 rounded-md p-1 hover:bg-black/5 focus:outline-none dark:hover:bg-white/5"
            aria-label="Dismiss banner"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>
    </div>
  );
}

export default AnnouncementBanner;
