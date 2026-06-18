const CACHE_NAME = 'saas-starter-cache-v1';
const OFFLINE_URL = '/offline.html';

const ASSETS_TO_CACHE = [
  '/',
  '/favicon.ico',
  '/robots.txt',
  '/manifest.json',
  '/offline.html'
];

// Perform installation and pre-cache resources
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Clear old cache versions on activation
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Intercept fetch requests
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Skip webhooks, Stripe APIs, and Inertia backend posts
  if (
    url.pathname.startsWith('/stripe') ||
    url.pathname.startsWith('/api') ||
    url.pathname.startsWith('/admin')
  ) {
    return;
  }

  // Cache-first strategy for static files (assets, images, fonts)
  if (
    event.request.destination === 'image' ||
    event.request.destination === 'style' ||
    event.request.destination === 'script' ||
    event.request.destination === 'font' ||
    url.pathname.includes('/build/')
  ) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }

        return fetch(event.request).then((networkResponse) => {
          if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
            return networkResponse;
          }

          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });

          return networkResponse;
        }).catch(() => {
          // Silent fallback for images
          if (event.request.destination === 'image') {
            return new Response('<svg role="img" aria-label="Offline" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.5M5 12.5a10.94 10.94 0 0 1 5.83-2.84M8.66 8.66a15.86 15.86 0 0 1 6.68-1.5M16.14 4.86a20.48 20.48 0 0 1 3.86 1.14M4 6a20.66 20.66 0 0 1 3.06-1M12 20v-4M8.5 16.5L12 13m0 0l3.5 3.5"/></svg>', {
              headers: { 'Content-Type': 'image/svg+xml' }
            });
          }
        });
      })
    );
    return;
  }

  // Network-first strategy for standard web page navigation
  event.respondWith(
    fetch(event.request).then((response) => {
      // Dynamic response clone caching
      if (response.status === 200) {
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
      }
      return response;
    }).catch(() => {
      return caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        
        // Fallback for HTML layout files
        if (event.request.headers.get('accept').includes('text/html')) {
          return caches.match(OFFLINE_URL);
        }
      });
    })
  );
});
