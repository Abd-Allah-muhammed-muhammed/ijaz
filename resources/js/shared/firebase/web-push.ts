import {initializeApp, type FirebaseApp} from 'firebase/app'
import {getMessaging, getToken, isSupported, type Messaging} from 'firebase/messaging'
import AdminDeviceTokenController from '@/actions/App/Http/Controllers/Dashboard/DeviceTokenController'
import ProviderDeviceTokenController from '@/actions/App/Http/Controllers/Provider/DeviceTokenController'
import apiClient from '@/shared/lib/api-client'
import {getDesktopNotificationPermission} from '@/shared/notifications/desktop-notification'

type FirebaseWebConfig = {
  apiKey: string
  authDomain: string
  projectId: string
  messagingSenderId: string
  appId: string
  vapidKey: string
}

let app: FirebaseApp | null = null
let messaging: Messaging | null = null
let registrationInFlight: Promise<string | null> | null = null

function readFirebaseWebConfig(): FirebaseWebConfig | null {
  const apiKey = String(import.meta.env.VITE_FIREBASE_API_KEY ?? '').trim()
  const authDomain = String(import.meta.env.VITE_FIREBASE_AUTH_DOMAIN ?? '').trim()
  const projectId = String(import.meta.env.VITE_FIREBASE_PROJECT_ID ?? '').trim()
  const messagingSenderId = String(import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID ?? '').trim()
  const appId = String(import.meta.env.VITE_FIREBASE_APP_ID ?? '').trim()
  const vapidKey = String(import.meta.env.VITE_FIREBASE_VAPID_KEY ?? '').trim()

  if (!apiKey || !projectId || !messagingSenderId || !appId || !vapidKey) {
    return null
  }

  return {apiKey, authDomain, projectId, messagingSenderId, appId, vapidKey}
}

export function isWebPushConfigured(): boolean {
  return readFirebaseWebConfig() !== null
}

async function getFirebaseMessaging(): Promise<Messaging | null> {
  const config = readFirebaseWebConfig()
  if (!config) {
    return null
  }

  if (!(await isSupported())) {
    return null
  }

  if (!app) {
    app = initializeApp({
      apiKey: config.apiKey,
      authDomain: config.authDomain || undefined,
      projectId: config.projectId,
      messagingSenderId: config.messagingSenderId,
      appId: config.appId,
    })
  }

  if (!messaging) {
    messaging = getMessaging(app)
  }

  return messaging
}

/**
 * After Notification permission is granted (same gesture as Desktop Notifications),
 * obtain an FCM registration token and POST it to the given device-token endpoint.
 *
 * No-ops when VITE_FIREBASE_* is empty (scaffolded until env is filled + rebuilt).
 */
export async function registerWebPush(registerUrl: string): Promise<string | null> {
  if (getDesktopNotificationPermission() !== 'granted') {
    return null
  }

  if (registrationInFlight) {
    return registrationInFlight
  }

  registrationInFlight = (async () => {
    try {
      const config = readFirebaseWebConfig()
      if (!config) {
        return null
      }

      const firebaseMessaging = await getFirebaseMessaging()
      if (!firebaseMessaging) {
        return null
      }

      const serviceWorkerRegistration = await navigator.serviceWorker.register(
        '/firebase-messaging-sw.js',
        {scope: '/'},
      )

      const token = await getToken(firebaseMessaging, {
        vapidKey: config.vapidKey,
        serviceWorkerRegistration,
      })

      if (!token) {
        return null
      }

      await apiClient.post(registerUrl, {token})

      return token
    } catch (error) {
      console.warn('[web-push] registration failed', error)
      return null
    } finally {
      registrationInFlight = null
    }
  })()

  return registrationInFlight
}

export async function registerProviderWebPush(): Promise<string | null> {
  return registerWebPush(ProviderDeviceTokenController.store.url())
}

export async function registerAdminWebPush(): Promise<string | null> {
  return registerWebPush(AdminDeviceTokenController.store.url())
}
