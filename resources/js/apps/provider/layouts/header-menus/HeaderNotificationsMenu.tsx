import {FC} from 'react'
import NotificationController from '@/actions/App/Http/Controllers/Provider/NotificationController'
import {registerProviderWebPush} from '@/shared/firebase/web-push'
import {HeaderNotificationsInbox} from '@/shared/notifications/HeaderNotificationsInbox'

export const PROVIDER_NOTIFICATION_RECEIVED_EVENT = 'provider-notification-received'

const HeaderNotificationsMenu: FC = () => (
  <HeaderNotificationsInbox
    receivedEventName={PROVIDER_NOTIFICATION_RECEIVED_EVENT}
    enableDesktopAlerts
    desktopPromptStorageKey='provider_desktop_notification_prompt_dismissed'
    registerWebPush={registerProviderWebPush}
    endpoints={{
      listUrl: (query) => NotificationController.index.url({query}),
      unreadCountUrl: () => NotificationController.unreadCount.url(),
      markAsReadUrl: (id) => NotificationController.markAsRead.url(id),
      markAllAsReadUrl: () => NotificationController.markAllAsRead.url(),
    }}
  />
)

export {HeaderNotificationsMenu}
