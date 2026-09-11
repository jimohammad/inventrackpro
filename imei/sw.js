/* Customer warranty PWA — scoped to /imei only. */
'use strict';

var CACHE_NAME = 'iqbal-warranty-v2';
var PRECACHE = [
  '/imei',
  '/imei/manifest.webmanifest',
  '/imei/icons/icon-192.png',
  '/imei/icons/icon-512.png',
  '/imei/icons/icon-512-maskable.png',
  '/imei/icons/apple-touch-icon.png'
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

function isUnderImeiScope(url) {
  return url.pathname === '/imei'
    || url.pathname === '/imei/'
    || url.pathname.indexOf('/imei/') === 0;
}

function isLiveLookup(url) {
  if (url.searchParams.has('imei')) {
    return true;
  }
  // Pretty path: /imei/{serial}
  return /^\/imei\/[^/]+\/?$/.test(url.pathname)
    && url.pathname.indexOf('/imei/icons/') !== 0
    && url.pathname !== '/imei/sw.js'
    && url.pathname !== '/imei/manifest.webmanifest';
}

function offlineLookupResponse() {
  return new Response(
    '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui;padding:2rem;text-align:center"><h1>You are offline</h1><p>Connect to the internet to check warranty.</p><p><a href="/imei">Back</a></p></body></html>',
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

  if (url.origin !== self.location.origin || !isUnderImeiScope(url)) {
    return;
  }

  // Warranty lookups must always be fresh from the server
  if (isLiveLookup(url)) {
    event.respondWith(
      fetch(request).catch(function () {
        return offlineLookupResponse();
      })
    );
    return;
  }

  // App shell + static PWA assets — cache first
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
      }).catch(function () {
        if (url.pathname === '/imei' || url.pathname === '/imei/') {
          return caches.match('/imei');
        }
        return Response.error();
      });
    })
  );
});
