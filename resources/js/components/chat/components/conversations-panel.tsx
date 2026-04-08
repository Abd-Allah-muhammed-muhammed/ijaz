import {Conversation as Chat} from "@/types/models";
import Conversation from "@/components/chat/components/conversation";
import {KTIcon, toAbsoluteUrl} from "@/_metronic/helpers";
import ConversationsList from "@/components/chat/components/conversations-list";
import {useTranslation} from "react-i18next";
import {ChangeEvent} from "react";
import { Card } from "react-bootstrap";

type Props = {
  searchCallback?: (e: ChangeEvent<HTMLInputElement>) => void
};
const ConversationsPanel = ({searchCallback}: Props) => {
  const { t } = useTranslation();
  return (
    <div className='card card-flush h-100'>
      <div className='card-header pt-7'>
        <form className='w-100 position-relative' autoComplete='off'>
          <KTIcon
            iconName='magnifier'
            className='fs-2 text-lg-1 text-gray-500 position-absolute top-50 ms-5 translate-middle-y'
          />
          <input
            type='search'
            autoComplete='off'
            onChange={searchCallback}
            className='form-control form-control-solid px-15'
            name='search'
            placeholder={ t('Search by phone...')}
          />
        </form>
      </div>
      <Card.Body className='pt-5'>
        <ConversationsList />
      </Card.Body>
    </div>
  );
}
export  default ConversationsPanel
