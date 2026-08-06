import {ConversationAttachment} from "@/shared/types/models";
import React from "react";
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFile } from '@fortawesome/free-solid-svg-icons';
import { url as appUrl } from '@/shared/helpers/general';

type Props = {
  attachments: ConversationAttachment[]
}

function isImageAttachment(attachment: ConversationAttachment): boolean {
  return attachment.type === 'image'
    || Boolean(attachment.mime_type?.startsWith('image/'));
}

function isPdfAttachment(attachment: ConversationAttachment): boolean {
  const name = displayName(attachment).toLowerCase();
  return attachment.type === 'pdf'
    || attachment.extension === 'pdf'
    || name.endsWith('.pdf')
    || Boolean(attachment.mime_type?.includes('pdf'));
}

function displayName(attachment: ConversationAttachment): string {
  return attachment.file_name || attachment.filename || attachment.name || 'file';
}

const Attachments = ({attachments}: Props) => {
  return (
    <div className="d-flex w-100 flex-wrap">
      {attachments.map((attachment) => (
        <div className="col-6 p-1 flex-grow-1" key={attachment.id}>
          <div className="bg-white shadow-sm w-100 h-100 p-2 bg-opacity-25">
            {isImageAttachment(attachment) ? (
              <a href={attachment.url} target="_blank" rel="noreferrer">
                <img
                  src={attachment.url}
                  alt={displayName(attachment)}
                  className="w-100 rounded"
                  style={{ maxHeight: 240, objectFit: 'cover' }}
                />
              </a>
            ) : (
              <div className="d-flex align-items-center gap-3 h-100">
                <div className="symbol symbol-40px">
                  <img
                    alt=""
                    src={appUrl(
                      isPdfAttachment(attachment)
                        ? '/media/svg/files/pdf.svg'
                        : '/media/svg/files/doc.svg',
                    )}
                  />
                </div>
                <div className="fw-semibold flex-grow-1 min-w-0">
                  <a
                    className="fs-6 fw-bold text-gray-900 text-hover-primary text-break"
                    href={attachment.url}
                    target="_blank"
                    rel="noreferrer"
                  >
                    {displayName(attachment)}
                  </a>
                  {attachment.size ? (
                    <div className="text-gray-500 fs-8">{attachment.size}</div>
                  ) : null}
                </div>
                <a
                  href={attachment.url}
                  target="_blank"
                  rel="noreferrer"
                  className="btn btn-icon btn-sm btn-active-light-primary"
                  aria-label="download"
                >
                  <FontAwesomeIcon icon={faFile} />
                </a>
              </div>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}


export default Attachments
