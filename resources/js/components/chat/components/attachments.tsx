import {ConversationAttachment} from "@/types/models";
import React from "react";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFile } from '@fortawesome/free-solid-svg-icons';

type  Props = {
  attachments: ConversationAttachment[]
}

const Attachments = ({attachments}: Props) => {
  return (
    <div className="d-flex w-100 flex-wrap">
      {attachments.map((attachment) => (
        <div className="col-6 p-1 flex-grow-1">
          <div className="bg-white shadow-sm w-100 h-100 p-2 bg-opacity-25">
            {attachment.type == "image" ?
              <a href={attachment.url} download>
                <img src={attachment.url} alt="Attachment" className="w-100"/>
              </a>
               :
              <div className="d-flex justify-content-center align-items-center h-100">
                <a href={attachment.url} download>
                  <FontAwesomeIcon icon={faFile} className="fs-4x" />
                </a>
              </div>
            }
          </div>
        </div>
      ))}
    </div>
  );
}


export default Attachments
