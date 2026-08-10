import { isChatNotificationContextInactive } from '@/shared/chat/hooks/use-chat-notification-sound'
import { url as appUrl } from '@/shared/helpers/general'

const DEFAULT_PROMPT_DISMISSED_KEY = 'provider_desktop_notification_prompt_dismissed'

const DEFAULT_ICON_PATH = '/logo-success-no-bg.svg'

export type DesktopNotificationPermission = NotificationPermission | 'unsupported'

/**
 * True when the browser exposes the Notification API (Secure Context + support).
 * Failures never throw — callers treat "unsupported" as a no-op UI state.
 */
export function isDesktopNotificationSupported(): boolean {
  return typeof window !== 'undefined' && typeof Notification !== 'undefined'
}

export function getDesktopNotificationPermission(): DesktopNotificationPermission {
  if (!isDesktopNotificationSupported()) {
    return 'unsupported'
  }

  try {
    return Notification.permission
  } catch {
    return 'unsupported'
  }
}

/**
 * Explicit user gesture only — never call from mount / Echo handlers.
 */
export async function requestDesktopNotificationPermission(): Promise<DesktopNotificationPermission> {
  if (!isDesktopNotificationSupported()) {
    return 'unsupported'
  }

  try {
    return await Notification.requestPermission()
  } catch {
    try {
      return Notification.permission
    } catch {
      return 'unsupported'
    }
  }
}

export function isDesktopNotificationPromptDismissed(
  storageKey: string = DEFAULT_PROMPT_DISMISSED_KEY,
): boolean {
  try {
    return window.localStorage.getItem(storageKey) === '1'
  } catch {
    return false
  }
}

export function dismissDesktopNotificationPrompt(
  storageKey: string = DEFAULT_PROMPT_DISMISSED_KEY,
): void {
  try {
    window.localStorage.setItem(storageKey, '1')
  } catch {
    // Private mode / blocked storage — ignore.
  }
}

export type ShowDesktopNotificationOptions = {
  title: string
  body?: string
  tag?: string
  icon?: string
}

/**
 * Shows an OS desktop notification only when:
 * - Notification API is available
 * - permission is already `granted` (never prompts here)
 * - the browsing context is hidden/unfocused (same rules as chat sound)
 *
 * Does not play the in-app chat sound — the OS notification has its own alert
 * and stacking both is jarring for StatusChanged / DomainNotification events.
 */
export function showDesktopNotificationWhenInactive(
  options: ShowDesktopNotificationOptions,
): void {
  if (!isDesktopNotificationSupported()) {
    return
  }

  try {
    if (Notification.permission !== 'granted') {
      return
    }
  } catch {
    return
  }

  if (!isChatNotificationContextInactive()) {
    return
  }

  const title = (options.title ?? '').trim()
  if (title === '') {
    return
  }

  try {
    const notification = new Notification(title, {
      body: options.body ?? '',
      icon: options.icon ?? appUrl(DEFAULT_ICON_PATH),
      tag: options.tag ?? `provider-notification-${Date.now()}`,
    })

    notification.onclick = () => {
      try {
        window.focus()
        notification.close()
      } catch {
        // Ignore focus/close failures in locked-down embeds.
      }
    }
  } catch {
    // Constructor / permission race — never surface to the UI.
  }
}
