import React, { useState, useEffect, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import { Bell, Check, CheckSquare, MessageSquare } from 'lucide-react';

interface Notification {
  id: number;
  type: string;
  title: string;
  body: string;
  action_url: string | null;
  read_at: string | null;
  created_at: string;
}

export function NotificationBell() {
  const { unread_notifications } = usePage<any>().props;
  const [isOpen, setIsOpen] = useState(false);
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const fetchNotifications = async () => {
    setLoading(true);
    try {
      const response = await fetch('/notifications', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      });
      if (response.ok) {
        const data = await response.json();
        // Pagination data envelope (data.data) or simple array
        setNotifications(data.data || data);
      }
    } catch (error) {
      console.error('Failed to load notifications:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen) {
      fetchNotifications();
    }
  }, [isOpen]);

  // Handle click outside to close
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const markAsRead = (id: number) => {
    router.post(route('notifications.read', id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        setNotifications(prev =>
          prev.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n)
        );
      }
    });
  };

  const markAllRead = () => {
    router.post(route('notifications.read-all'), {}, {
      preserveScroll: true,
      onSuccess: () => {
        setNotifications(prev =>
          prev.map(n => ({ ...n, read_at: new Date().toISOString() }))
        );
      }
    });
  };

  return (
    <div className="relative" ref={containerRef}>
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        aria-label="View notifications"
      >
        <Bell className="h-6 w-6" />
        {unread_notifications > 0 && (
          <span className="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white ring-2 ring-white dark:ring-gray-900">
            {unread_notifications > 9 ? '9+' : unread_notifications}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-2.5 w-80 rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5 focus:outline-none dark:border-gray-800 dark:bg-gray-950 z-50 overflow-hidden">
          <div className="flex items-center justify-between border-b border-gray-100 dark:border-gray-900 px-4 py-3">
            <span className="font-semibold text-sm text-gray-900 dark:text-white">Notifications</span>
            {unread_notifications > 0 && (
              <button
                onClick={markAllRead}
                className="flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 focus:outline-none"
              >
                <CheckSquare className="h-3.5 w-3.5" />
                Mark all read
              </button>
            )}
          </div>

          <div className="max-h-96 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-900/50">
            {loading ? (
              <div className="px-4 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
                Loading notifications...
              </div>
            ) : notifications.length === 0 ? (
              <div className="px-4 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                <MessageSquare className="mx-auto h-8 w-8 mb-2 text-gray-300 dark:text-gray-700" />
                No notifications yet.
              </div>
            ) : (
              notifications.map(item => (
                <div
                  key={item.id}
                  className={`flex flex-col gap-1 p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors ${!item.read_at ? 'bg-indigo-50/20 dark:bg-indigo-950/5' : ''}`}
                >
                  <div className="flex items-start justify-between gap-2">
                    <span className="font-semibold text-xs text-gray-900 dark:text-white">{item.title}</span>
                    {!item.read_at && (
                      <button
                        onClick={() => markAsRead(item.id)}
                        className="rounded p-0.5 text-gray-400 hover:bg-gray-150 hover:text-indigo-600 dark:hover:bg-gray-800 dark:hover:text-indigo-400 focus:outline-none"
                        title="Mark as read"
                      >
                        <Check className="h-3.5 w-3.5" />
                      </button>
                    )}
                  </div>
                  <p className="text-xs text-gray-600 dark:text-gray-400">{item.body}</p>
                  <span className="text-[10px] text-gray-400 mt-1">
                    {new Date(item.created_at).toLocaleDateString()}
                  </span>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default NotificationBell;
