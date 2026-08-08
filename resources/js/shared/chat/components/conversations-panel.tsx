import { KTIcon } from '@/vendor/metronic/helpers';
import ConversationsList from '@/shared/chat/components/conversations-list';
import { useTranslation } from 'react-i18next';
import { Card } from 'react-bootstrap';

/**
 * Provider inbox sidebar: search field chrome + conversation list.
 *
 * Inbox search is not wired — ProviderChatIndexController has no search filter,
 * and the previous Index `searchCallback` stub was a no-op. The field stays for
 * layout parity until server-side search exists.
 */
const ConversationsPanel = () => {
  const { t } = useTranslation();

  return (
    <div className="card card-flush h-100">
      <div className="card-header pt-7">
        <form className="w-100 position-relative" autoComplete="off" onSubmit={(e) => e.preventDefault()}>
          <KTIcon
            iconName="magnifier"
            className="fs-2 text-lg-1 text-gray-500 position-absolute top-50 ms-5 translate-middle-y"
          />
          <input
            type="search"
            autoComplete="off"
            className="form-control form-control-solid px-15"
            name="search"
            placeholder={t('search_by_phone')}
          />
        </form>
      </div>
      <Card.Body className="pt-5">
        <ConversationsList />
      </Card.Body>
    </div>
  );
};

export default ConversationsPanel;
