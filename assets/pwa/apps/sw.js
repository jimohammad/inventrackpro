/* Unified customer PWA — hub at /apps; may navigate to /imei, /service, /pricelist. */
'use strict';

var CACHE_NAME = 'iqbal-apps-v1';
var PRECACHE = [
  '/apps',
  '/assets/pwa/apps/manifest.webmanifest',
  '/assets/pwa/apps/nav.js',
  '/assets/pwa/apps/icons/icon-192.png',
  '/assets/pwa/apps/icons/icon-512.png',
  '/assets/pwa/apps/icons/icon-512-maskable.png',
  '/assets/pwa/apps/icons/apple-touch-icon.png'
];

var PUBLIC_PREFIXES = ['/apps', '/imei', '/service', '/pricelist', '/assets/pwa/apps/'];

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

function isPublicPath(pathname) {
  for (var i = 0; i < PUBLIC_PREFIXES.length; i++) {
    var p = PUBLIC_PREFIXES[i];
    if (pathname === p || pathname === p + '/' || pathname.indexOf(p + '/') === 0 || pathname.indexOf(p + '?') === 0) {
      return true;
    }
  }
  // /pricelist.php legacy
  if (pathname === '/pricelist.php') {
    return true;
  }
  return false;
}

function offlineHub() {
  return caches.match('/apps').then(function (cached) {
    if (cached) {
      return cached;
    }
    return new Response(
      '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui;padding:2rem;text-align:center"><h1>You are offline</h1><p><a href="/apps">Open menu</a></p></body></html>',
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  });
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

  // Do not control ERP / staff pages
  if (!isPublicPath(url.pathname)) {
    return;
  }

  // Static hub assets — cache first
  if (url.pathname.indexOf('/assets/pwa/apps/') === 0) {
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

  // Hub shell — cache first
  if (url.pathname === '/apps' || url.pathname === '/apps/') {
    if (!url.search || url.search === '?utm_source=pwa') {
      event.respondWith(
        caches.match('/apps').then(function (cached) {
          var network = fetch(request).then(function (response) {
            if (response && response.status === 200 && response.type === 'basic') {
              var clone = response.clone();
              caches.open(CACHE_NAME).then(function (cache) {
                cache.put('/apps', clone);
              });
            }
            return response;
          }).catch(function () {
            return cached || offlineHub();
          });
          return cached || network;
        })
      );
      return;
    }
  }

  // Live pages (warranty / service / pricelist) — always network (prices & status must be fresh)
  event.respondWith(
    fetch(request).catch(function () {
      if (url.pathname === '/apps' || url.pathname === '/apps/') {
        return offlineHub();
      }
      return new Response(
        '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui;padding:2rem;text-align:center"><h1>You are offline</h1><p><a href="/apps">Back to menu</a></p></body></html>',
        { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
      );
    })
  );
});
