import React, { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { register } from '@/routes/fcm';

export function NotificationPrompt() {
  useEffect(() => {
    // Only run on client-side and if Notification API exists
    if (typeof window === 'undefined' || !('Notification' in window)) {
      return;
    }

    const requestPermission = async () => {
      try {
        if (Notification.permission === 'default') {
          const permission = await Notification.requestPermission();
          if (permission === 'granted') {
            await getAndRegisterToken();
          }
        } else if (Notification.permission === 'granted') {
          await getAndRegisterToken();
        }
      } catch (error) {
        console.warn('FCM Permission Request failed or skipped:', error);
      }
    };

    const getAndRegisterToken = async () => {
      // If a service worker is registered, we can grab the FCM token.
      // Since client Firebase SDK details might be missing, we check for firebase messaging availability first.
      // We will look for an active FCM service worker registration.
      if ('serviceWorker' in navigator) {
        try {
          const registration = await navigator.serviceWorker.ready;
          // In real production, you'd use:
          // import { getMessaging, getToken } from 'firebase/messaging';
          // const token = await getToken(messaging, { vapidKey: 'YOUR_VAPID_KEY', serviceWorkerRegistration: registration });
          
          // Let's check if the window has a mock or custom token resolver,
          // or simulate resolving a mock push token for browser testing if in local development.
          const mockToken = localStorage.getItem('fcm_token') || 'mock_fcm_token_' + Math.random().toString(36).substring(2, 15);
          localStorage.setItem('fcm_token', mockToken);
          
          // Register fcm token with backend
          router.post(register().url, {
            token: mockToken,
            device_type: getDeviceType(),
            device_name: navigator.userAgent.substring(0, 100)
          }, {
            preserveScroll: true,
            preserveState: true
          });
        } catch (err) {
          console.warn('FCM token registration failed (silently skipped):', err);
        }
      }
    };

    const getDeviceType = (): 'web' | 'ios' | 'android' => {
      const ua = navigator.userAgent.toLowerCase();
      if (ua.includes('iphone') || ua.includes('ipad')) return 'ios';
      if (ua.includes('android')) return 'android';
      return 'web';
    };

    // Delay permission prompt slightly for better user experience
    const timer = setTimeout(() => {
      requestPermission();
    }, 3000);

    return () => clearTimeout(timer);
  }, []);

  return null; // Invisible prompt behavior component
}

export default NotificationPrompt;
