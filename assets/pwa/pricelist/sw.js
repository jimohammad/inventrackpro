/* Customer pricelist PWA — scope /pricelist only. Never cache HTML (prices are timed). */
'use strict';

var CACHE_NAME = 'iqbal-pricelist-v1';
var PRECACHE = [
  '/assets/pwa/pricelist/manifest.webmanifest',
  '/assets/pwa/pricelist/icons/icon-192.png',
  '/assets/pwa/pricelist/icons/icon-512.png',
  '/assets/pwa/pricelist/icons/icon-512-maskable.png',
  '/assets/pwa/pricelist/icons/apple-touch-icon.png'
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(PRECACHE);
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.map(function (key) {
        if (key !== CACHE_NAME) {
          return caches.delete(key);
        }
      }));
    }).then(function () {
      return self.clients.claim();
    })
  );
});

function offlinePage() {
  return new Response(
    '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui;padding:2rem;text-align:center;background:#c8e6c9"><h1>You are offline</h1><p>Connect to the internet to view the pricelist.</p><p><a href="/pricelist">Retry</a></p></body></html>',
    { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
  );
}

self.addEventListener('fetch', function (event) {
  var request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  var url;
  try {
    url = new URL(request.url);
  } catch (e) {
    return;
  }

  if (url.origin !== self.location.origin) {
    return;
  }

  // Static PWA assets — cache first
  if (url.pathname.indexOf('/assets/pwa/pricelist/') === 0) {
    event.respondWith(
      caches.match(request).then(function (cached) {
        if (cached) {
          return cached;
        }
        return fetch(request).then(function (response) {
          if (response && response.status === 200 && response.type === 'basic') {
            var clone = response.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              cache.put(request, clone);
            });
          }
          return response;
        });
      })
    );
    return;
  }

  // Pricelist page + status checks — always network (never cache prices)
  if (
    url.pathname === '/pricelist'
    || url.pathname === '/pricelist/'
    || url.pathname === '/pricelist.php'
  ) {
    event.respondWith(
      fetch(request).catch(function () {
        return offlinePage();
      })
    );
  }
});
