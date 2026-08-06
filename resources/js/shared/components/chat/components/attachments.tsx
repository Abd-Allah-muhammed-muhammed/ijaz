import { ConversationAttachment } from '@/shared/types/models';
import React from 'react';
import { KTIcon } from '@/vendor/metronic/helpers';
import { url as appUrl } from '@/shared/helpers/general';
import {
  attachmentDisplayName,
  isImageAttachment,
  isPdfAttachment,
} from '@/shared/components/chat/components/chat-attachment-utils';

type Props = {
  attachments: ConversationAttachment[];
};

/**
 * Message-bubble attachment cards — mirrors Provider Order Show media rows
 * (pdf.svg / doc.svg + filename + size + download).
 */
const Attachments = ({ attachments }: Props) => {
  return (
    <div className="d-flex flex-column w-100 gap-2 mb-2 min-w-0">
      {attachments.map((attachment) => {
        const name = attachmentDisplayName(attachment);

        if (isImageAttachment(attachment)) {
          return (
            <a
              key={attachment.id}
              href={attachment.url}
              target="_blank"
              rel="noreferrer"
              className="d-block min-w-0"
            >
              <img
                src={attachment.url}
                alt={name}
                className="rounded w-100"
                style={{
                  maxHeight: 240,
                  objectFit: 'cover',
                  display: 'block',
                }}
              />
            </a>
          );
        }

        return (
          <div
            key={attachment.id}
            className="d-flex align-items-center min-w-0 bg-white bg-opacity-25 rounded p-2"
          >
            <div className="symbol symbol-30px me-3 flex-shrink-0">
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
                className="fs-6 fw-bold text-gray-900 text-hover-primary text-break d-block"
                href={attachment.url}
                target="_blank"
                rel="noreferrer"
              >
                {name}
              </a>
              {attachment.size ? (
                <div className="text-gray-500 fs-8">{attachment.size}</div>
              ) : null}
            </div>
            <a
              href={attachment.url}
              target="_blank"
              rel="noreferrer"
              className="btn btn-clean btn-sm btn-icon btn-icon-primary btn-active-light-primary ms-2 flex-shrink-0"
              aria-label="download"
            >
              <KTIcon iconName="arrow-down" className="fs-1" />
            </a>
          </div>
        );
      })}
    </div>
  );
};

export default Attachments;
