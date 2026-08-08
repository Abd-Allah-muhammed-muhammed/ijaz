import ProviderLayout from '@/apps/provider/layouts/ProviderLayout';
import { Content } from '@/vendor/metronic/layout/components/content';
import { ConversationContent, ConversationsPanel, useConversations } from '@/shared/chat';
import { Conversation } from '@/shared/types/models';
import { useEffect } from 'react';
import { PaginationResource } from '@/shared/types';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { KTIcon } from '@/vendor/metronic/helpers';

type Props = {
  rows: PaginationResource<Conversation>;
};

const Index = ({ rows }: Props) => {
  const { t } = useTranslation();
  const {
    setConversations,
    setCurrentSocketId,
    currentConversation,
    setCurrentConversation,
  } = useConversations();
  const user = usePage().props.auth.user;
  const current_conversation = usePage().props.current_conversation as Conversation;

  useEffect(() => {
    setCurrentSocketId(user.socket_id);
    setConversations(rows.data);
    if (current_conversation) {
      setCurrentConversation(current_conversation);
    }
  }, []);

  return (
    <Content>
      <Head title={t('conversations')} />
      <div className="d-flex flex-column flex-lg-row" style={{ height: 'calc(100vh - calc(60px + 74px + 30px))' }}>
        <div className="flex-column flex-lg-row-auto w-100 w-lg-300px w-xl-400px mb-10 mb-lg-0 ">
          <ConversationsPanel />
        </div>

        <div className="flex-lg-row-fluid ms-lg-7 ms-xl-10 h-100">
          {currentConversation ? (
            <ConversationContent />
          ) : (
            <div className="card border-0 shadow-sm h-100">
              <div className="card-body py-20 text-center d-flex flex-column align-items-center justify-content-center">
                <KTIcon iconName="message-text-2" className="fs-5x mb-5 text-gray-300" />
                <p className="text-muted fw-semibold fs-5 mb-0">{t('select_a_conversation')}</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </Content>
  );
};

Index.layout = (page: any) => {
  return <ProviderLayout {...page.props}>{page}</ProviderLayout>;
};

export default Index;
