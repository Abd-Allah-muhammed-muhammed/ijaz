import clsx from 'clsx'
import {FC, useCallback, useEffect, useState} from 'react'
import {useTranslation} from 'react-i18next'
import {KTIcon, toAbsoluteUrl} from '@/vendor/metronic/helpers'
import apiClient from '@/shared/lib/api-client'
import type {SingleApiResponse} from '@/shared/types/api'
import {
  dismissDesktopNotificationPrompt,
  getDesktopNotificationPermission,
  isDesktopNotificationPromptDismissed,
  requestDesktopNotificationPermission,
  type DesktopNotificationPermission,
} from '@/shared/notifications/desktop-notification'

export type InboxNotification = {
  id: string
  type: string
  title: string
  body: string
  read_at: string | null
  created_at: string | null
  created_at_iso: string | null
}

type NotificationListPayload = {
  items: InboxNotification[]
  total: number
  current_page: number
  last_page: number
  per_page: number
  has_more_pages: boolean
}

type UnreadCountPayload = {
  unread_count: number
}

export type NotificationInboxEndpoints = {
  listUrl: (query?: {per_page?: number}) => string
  unreadCountUrl: () => string
  markAsReadUrl: (id: string) => string
  markAllAsReadUrl: () => string
}

export type HeaderNotificationsInboxProps = {
  endpoints: NotificationInboxEndpoints
  receivedEventName: string
  enableDesktopAlerts?: boolean
  desktopPromptStorageKey?: string
  /** FCM web token registration after Notification permission is granted. */
  registerWebPush?: () => Promise<string | null>
}

const HeaderNotificationsInbox: FC<HeaderNotificationsInboxProps> = ({
  endpoints,
  receivedEventName,
  enableDesktopAlerts = false,
  desktopPromptStorageKey = 'provider_desktop_notification_prompt_dismissed',
  registerWebPush,
}) => {
  const {t} = useTranslation()
  const [unreadCount, setUnreadCount] = useState(0)
  const [items, setItems] = useState<InboxNotification[]>([])
  const [loading, setLoading] = useState(false)
  const [loaded, setLoaded] = useState(false)
  const [permission, setPermission] = useState<DesktopNotificationPermission>(() =>
    getDesktopNotificationPermission(),
  )
  const [promptDismissed, setPromptDismissed] = useState(() =>
    isDesktopNotificationPromptDismissed(desktopPromptStorageKey),
  )
  const [requestingPermission, setRequestingPermission] = useState(false)

  const refreshUnreadCount = useCallback(async () => {
    try {
      const {data} = await apiClient.get<SingleApiResponse<UnreadCountPayload>>(
        endpoints.unreadCountUrl(),
      )
      setUnreadCount(data.data.unread_count)
    } catch {
      // Keep last known count on transient failures.
    }
  }, [endpoints])

  const loadNotifications = useCallback(async () => {
    setLoading(true)
    try {
      const [{data: listResponse}, {data: countResponse}] = await Promise.all([
        apiClient.get<SingleApiResponse<NotificationListPayload>>(
          endpoints.listUrl({per_page: 20}),
        ),
        apiClient.get<SingleApiResponse<UnreadCountPayload>>(
          endpoints.unreadCountUrl(),
        ),
      ])
      setItems(listResponse.data.items)
      setUnreadCount(countResponse.data.unread_count)
      setLoaded(true)
    } catch {
      setLoaded(true)
    } finally {
      setLoading(false)
    }
  }, [endpoints])

  useEffect(() => {
    void refreshUnreadCount()

    const onReceived = () => {
      void refreshUnreadCount()
      if (loaded) {
        void loadNotifications()
      }
    }

    window.addEventListener(receivedEventName, onReceived)
    return () => {
      window.removeEventListener(receivedEventName, onReceived)
    }
  }, [loaded, loadNotifications, receivedEventName, refreshUnreadCount])

  useEffect(() => {
    if (!enableDesktopAlerts || !registerWebPush) {
      return
    }

    if (getDesktopNotificationPermission() === 'granted') {
      void registerWebPush()
    }
  }, [enableDesktopAlerts, registerWebPush])

  const handleMenuOpen = () => {
    setPermission(getDesktopNotificationPermission())
    if (!loaded && !loading) {
      void loadNotifications()
    }
  }

  const markAsRead = async (notification: InboxNotification) => {
    if (notification.read_at) {
      return
    }

    try {
      await apiClient.post(endpoints.markAsReadUrl(notification.id))
      setItems((prev) =>
        prev.map((item) =>
          item.id === notification.id ? {...item, read_at: new Date().toISOString()} : item,
        ),
      )
      setUnreadCount((count) => Math.max(0, count - 1))
    } catch {
      // Ignore — list will refresh on next open.
    }
  }

  const markAllAsRead = async () => {
    if (unreadCount === 0) {
      return
    }

    try {
      await apiClient.post(endpoints.markAllAsReadUrl())
      setItems((prev) =>
        prev.map((item) => ({...item, read_at: item.read_at ?? new Date().toISOString()})),
      )
      setUnreadCount(0)
    } catch {
      // Ignore — list will refresh on next open.
    }
  }

  const enableDesktopAlertsClick = async () => {
    setRequestingPermission(true)
    try {
      const next = await requestDesktopNotificationPermission()
      setPermission(next)
      if (next !== 'default') {
        dismissDesktopNotificationPrompt(desktopPromptStorageKey)
        setPromptDismissed(true)
      }
      if (next === 'granted') {
        void registerWebPush?.()
      }
    } finally {
      setRequestingPermission(false)
    }
  }

  const dismissOptIn = () => {
    dismissDesktopNotificationPrompt(desktopPromptStorageKey)
    setPromptDismissed(true)
  }

  const showDesktopOptIn = enableDesktopAlerts && permission === 'default' && !promptDismissed

  return (
    <>
      <div
        data-kt-menu-trigger="{default: 'click'}"
        data-kt-menu-attach='parent'
        data-kt-menu-placement='bottom-end'
        className='btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px position-relative'
        onClick={handleMenuOpen}
        role='button'
        aria-label={t('notifications')}
      >
        <KTIcon iconName='notification-on' className='fs-2'/>
        {unreadCount > 0 && (
          <span className='bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink'/>
        )}
        {unreadCount > 0 && (
          <span className='position-absolute top-0 start-100 translate-middle badge badge-circle badge-danger'>
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </div>

      <div
        className='menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg fw-bold w-350px w-lg-375px'
        data-kt-menu='true'
      >
        <div
          className='d-flex flex-column bgi-no-repeat rounded-top'
          style={{backgroundImage: `url('${toAbsoluteUrl('media/misc/menu-header-bg.jpg')}')`}}
        >
          <h3 className='text-gray-900 fw-bold px-9 mt-10 mb-6'>
            {t('notifications')}{' '}
            <span className='fs-8 text-muted ps-3'>
              {unreadCount} {t('unread')}
            </span>
          </h3>
        </div>

        {showDesktopOptIn && (
          <div className='px-8 py-4 border-bottom'>
            <div className='text-gray-800 fw-semibold fs-7 mb-1'>
              {t('enable_desktop_alerts')}
            </div>
            <div className='text-gray-700 fs-8 mb-3'>
              {t('enable_desktop_alerts_hint')}
            </div>
            <div className='d-flex flex-wrap gap-2'>
              <button
                type='button'
                className='btn btn-sm btn-primary'
                disabled={requestingPermission}
                onClick={() => {
                  void enableDesktopAlertsClick()
                }}
              >
                {t('enable_desktop_alerts')}
              </button>
              <button
                type='button'
                className='btn btn-sm btn-light'
                disabled={requestingPermission}
                onClick={dismissOptIn}
              >
                {t('not_now')}
              </button>
            </div>
          </div>
        )}

        {enableDesktopAlerts && permission === 'granted' && (
          <div className='px-8 py-3 border-bottom'>
            <span className='badge badge-light-success fs-8'>
              {t('desktop_alerts_enabled')}
            </span>
          </div>
        )}

        <div className='scroll-y mh-325px my-5 px-8'>
          {loading && !loaded && (
            <div className='text-center text-muted py-10'>{t('loading')}</div>
          )}

          {loaded && items.length === 0 && (
            <div className='text-center text-muted py-10'>{t('no_notifications')}</div>
          )}

          {items.map((notification) => (
            <button
              type='button'
              key={notification.id}
              className={clsx(
                'd-flex flex-stack py-4 w-100 text-start border-0 bg-transparent text-gray-800',
                !notification.read_at && 'bg-light-primary rounded px-2',
              )}
              onClick={() => {
                void markAsRead(notification)
              }}
            >
              <div className='d-flex align-items-center'>
                <div className='symbol symbol-35px me-4'>
                  <span
                    className={clsx(
                      'symbol-label',
                      notification.read_at ? 'bg-light-secondary' : 'bg-light-primary',
                    )}
                  >
                    <KTIcon
                      iconName='notification-on'
                      className={clsx('fs-2', notification.read_at ? 'text-gray-600' : 'text-primary')}
                    />
                  </span>
                </div>

                <div className='mb-0 me-2'>
                  <span className='fs-6 text-gray-800 text-hover-primary fw-bolder d-block'>
                    {notification.title}
                  </span>
                  <div className='text-gray-700 fs-7'>{notification.body}</div>
                </div>
              </div>

              <span className='badge badge-light fs-8 flex-shrink-0 ms-2 text-gray-700'>
                {notification.created_at}
              </span>
            </button>
          ))}
        </div>

        <div className='py-3 text-center border-top'>
          <button
            type='button'
            className='btn btn-sm btn-light-primary'
            disabled={unreadCount === 0}
            onClick={() => {
              void markAllAsRead()
            }}
          >
            {t('mark_all_as_read')}
          </button>
        </div>
      </div>
    </>
  )
}

export {HeaderNotificationsInbox}
