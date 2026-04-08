import {ChangeEvent} from 'react'
import {ChatInner, Dropdown1} from '@/_metronic/partials'
import {ToolbarWrapper} from '@/_metronic/layout/components/toolbar'
import {Content} from '@/_metronic/layout/components/content'
import {Conversation} from "@/types/models";
import ConversationsPanel from "@/components/chat/components/conversations-panel";

type Props = {
  conversations: Conversation[],
  currentSocketId: string
  searchCallback?: (e: ChangeEvent<HTMLInputElement>) => void
};
const Chat = ({conversations, currentSocketId, searchCallback}: Props) => {
  return (
    <>
      <ToolbarWrapper/>
      <Content>
        <div className='d-flex flex-column flex-lg-row'>
          <div className='flex-column flex-lg-row-auto w-100 w-lg-300px w-xl-400px mb-10 mb-lg-0'>
            <ConversationsPanel conversations={conversations} currentSocketId={currentSocketId}
                                searchCallback={searchCallback}/>
          </div>

          <div className='flex-lg-row-fluid ms-lg-7 ms-xl-10'>
            <div className='card' >
              <div className='card-header' id='kt_chat_messenger_header'>
                <div className='card-title'>
                  <div className='symbol-group symbol-hover'></div>
                  <div className='d-flex justify-content-center flex-column me-3'>
                    <a
                      href='#'
                      className='fs-4 fw-bolder text-gray-900 text-hover-primary me-1 mb-2 lh-1'
                    >
                      Brian Cox
                    </a>

                    <div className='mb-0 lh-1'>
                      <span className='badge badge-success badge-circle w-10px h-10px me-1'></span>
                      <span className='fs-7 fw-bold text-gray-500'>Active</span>
                    </div>
                  </div>
                </div>

                <div className='card-toolbar'>
                  <div className='me-n3'>
                    <button
                      className='btn btn-sm btn-icon btn-active-light-primary'
                      data-kt-menu-trigger='click'
                      data-kt-menu-placement='bottom-end'
                      data-kt-menu-flip='top-end'
                    >
                      <i className='bi bi-three-dots fs-2'></i>
                    </button>
                    <Dropdown1/>
                  </div>
                </div>
              </div>
              <ChatInner/>
            </div>
          </div>
        </div>
      </Content>
    </>
  )
}

export {Chat}
