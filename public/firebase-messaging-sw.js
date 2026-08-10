/* eslint-disable no-undef */
/**
 * Firebase Cloud Messaging service worker (compat SDK).
 *
 * Served from the domain root so FCM can claim the default scope.
 * Config is fetched at runtime from /firebase-web-config (same public
 * VITE_FIREBASE_* values) because service workers cannot read Vite env.
 *
 * Keep importScripts version in sync with package.json "firebase"
 * (config/services.php → services.firebase.web.sdk_compat_version).
 */
importScripts('https://www.gstatic.com/firebasejs/12.17.1/firebase-app-compat.js')
importScripts('https://www.gstatic.com/firebasejs/12.17.1/firebase-messaging-compat.js')

self.addEventListener('install', (event) => {
  event.waitUntil(self.skipWaiting())
})

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim())
})

const firebaseReady = fetch('/firebase-web-config', {credentials: 'same-origin'})
  .then((response) => {
    if (!response.ok) {
      throw new Error('Failed to load Firebase web config')
    }

    return response.json()
  })
  .then((config) => {
    if (!config?.apiKey || !config?.projectId || !config?.messagingSenderId || !config?.appId) {
      throw new Error('Firebase web config is incomplete')
    }

    firebase.initializeApp({
      apiKey: config.apiKey,
      authDomain: config.authDomain || undefined,
      projectId: config.projectId,
      messagingSenderId: config.messagingSenderId,
      appId: config.appId,
    })

    // Registers the background message handler required by FCM.
    // Notification-payload messages are displayed by the browser when no page is focused.
    firebase.messaging()
  })
  .catch((error) => {
    console.error('[firebase-messaging-sw] init failed', error)
  })

// Ensure push events wait for Firebase init when the SW cold-starts.
self.addEventListener('push', (event) => {
  if (event.data && firebaseReady) {
    event.waitUntil(firebaseReady)
  }
})
