import ProviderLayout from "@/layouts/provider/ProviderLayout";
import {Content} from "@/_metronic/layout/components/content";
import ConversationsPanel from "@/components/chat/components/conversations-panel";
import {Conversation} from '@/types/models';
import {useEffect} from "react";
import {PaginationResource} from "@/types";
import {Head, usePage} from "@inertiajs/react";
import ConversationContent from "@/components/chat/components/conversation-content";
import {useConversations} from "@/store/use-chat";
import { useTranslation } from 'react-i18next';

type Props = {
  rows: PaginationResource<Conversation>,
};
const Index = ({rows}: Props) => {
  const { t } = useTranslation();
  const {
    setConversations,
    setCurrentSocketId,
    currentConversation,
    setCurrentConversation
  } = useConversations()
  const user = usePage().props.auth.user
  const current_conversation = usePage().props.current_conversation as Conversation;

  useEffect(() => {
    setCurrentSocketId(user.socket_id)
    setConversations(rows.data)
    if (current_conversation) {
      setCurrentConversation(current_conversation);
    }
  }, []);

  return (
    <Content>
      <Head title={t('conversations')} />
      <div className='d-flex flex-column flex-lg-row' style={{height: 'calc(100vh - calc(60px + 74px + 30px))'}}>
        {/*<div className='d-flex flex-column flex-lg-row'>*/}
        <div className='flex-column flex-lg-row-auto w-100 w-lg-300px w-xl-400px mb-10 mb-lg-0 '>
          <ConversationsPanel
            searchCallback={(e) => {
              // Handle search input change
              const searchValue = e.target.value;
              // You can implement the search logic here, e.g., filter conversations based on searchValue
              console.log("Search value:", searchValue);
            }}
          />
        </div>

        <div className='flex-lg-row-fluid ms-lg-7 ms-xl-10 h-100'>
          {currentConversation && (
            <ConversationContent/>
          )}
        </div>
      </div>
    </Content>
  )
}


Index.layout = (page: any) => {
  return <ProviderLayout {...page.props}>{page}</ProviderLayout>
}
export default Index
