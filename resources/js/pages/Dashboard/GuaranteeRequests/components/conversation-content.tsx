import React, { useEffect, useState } from 'react';
import { ConversationMessage, GuaranteeRequest } from '@/types/models';
import { useQuery } from '@tanstack/react-query';
import MessageIn from "@/components/chat/components/message-in";
import InfiniteScroll from 'react-infinite-scroll-component';
import { ApiResponse, ConversationMessagePaginationResource } from '@/types/api';
import GuaranteeRequestController from '@/actions/App/Http/Controllers/Dashboard/GuaranteeRequestController';
import axios from '@/helpers/axios';
import { useTranslation } from 'react-i18next';

type props = {
  item: GuaranteeRequest
}

const ConversationContent = ({item} : props) => {
  const [messages, setMessages] = useState<ConversationMessage[]>([]);
  const [hasMore, setHasMore] = useState<boolean>(true);
  const [page, setPage] = useState<number>(0);
  const { t } = useTranslation();

  const fetchConversationData = async (item: GuaranteeRequest, signal: AbortSignal): Promise<ConversationMessagePaginationResource> => {
    // @ts-expect-error: Method generated dynamically by Wayfinder
    const url = GuaranteeRequestController.conversationMessages(item.id as string, {query: {page: page + 1}}).url;
    const { data } = await axios.get<ApiResponse<ConversationMessagePaginationResource>>(url, { signal });
    return data.data;
  };

  const getMessages = useQuery<ConversationMessagePaginationResource>({
      queryKey: ['guarantee-request', 'conversations', item.id],
      queryFn: ({ signal }) => fetchConversationData(item, signal),
      staleTime: 1000 * 1, // 1 minute
      refetchOnWindowFocus: false,
      refetchOnReconnect: false,
      refetchOnMount: false,
    });

  const getNewMessages = async () => {
    await getMessages.refetch().then(res => {
      if(res.data?.items.length) {
        setMessages(prev => [...prev, ...res.data?.items as ConversationMessage[]]);
        setHasMore(res.data?.paginate?.has_more_pages as boolean);
        setPage(res.data?.paginate?.current_page as number);
      } else {
        setHasMore(false);
      }
    });
  };

  useEffect(() => {
    getNewMessages();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div
      id="scrollableDiv"
      style={{
        maxHeight: 500,
        overflow: 'auto',
        display: 'flex',
        flexDirection: 'column-reverse', // Key for top loading
      }}
    >
      <InfiniteScroll
        dataLength={messages?.length || 0}
        next={getNewMessages}
        style={{ display: 'flex', flexDirection: 'column-reverse' }}
        inverse={true} // Enable top loading
        hasMore={hasMore || false}
        loader={<h4>{t('loading')}</h4>}
        scrollableTarget="scrollableDiv"
      >
        {messages?.map((message) => <MessageIn conversationMessage={message} key={message.id + (new Date()).getMilliseconds()}/>)}
      </InfiniteScroll>
    </div>
  );
};
export default ConversationContent
