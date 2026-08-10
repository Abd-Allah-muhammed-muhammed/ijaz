import {FC} from 'react'
import NotificationController from '@/actions/App/Http/Controllers/Dashboard/NotificationController'
import {HeaderNotificationsInbox} from '@/shared/notifications/HeaderNotificationsInbox'

export const ADMIN_NOTIFICATION_RECEIVED_EVENT = 'admin-notification-received'

const HeaderNotificationsMenu: FC = () => (
  <HeaderNotificationsInbox
    receivedEventName={ADMIN_NOTIFICATION_RECEIVED_EVENT}
    enableDesktopAlerts={false}
    endpoints={{
      listUrl: (query) => NotificationController.index.url({query}),
      unreadCountUrl: () => NotificationController.unreadCount.url(),
      markAsReadUrl: (id) => NotificationController.markAsRead.url(id),
      markAllAsReadUrl: () => NotificationController.markAllAsRead.url(),
    }}
  />
)

export {HeaderNotificationsMenu}
