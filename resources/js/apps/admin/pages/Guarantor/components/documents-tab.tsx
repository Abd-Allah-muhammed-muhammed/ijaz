import { KTIcon } from '@/vendor/metronic/helpers';
import { useTranslation } from 'react-i18next';

export type MediaItem = {
  id?: string;
  uuid?: string;
  url: string;
  mime_type: string;
  collection_name?: string;
  file_name?: string;
  name?: string;
};

type CollectionDef = {
  key: string;
  labelKey: string;
};

type Props = {
  requestMedia?: MediaItem[];
  companyMedia?: MediaItem[];
  isCompany: boolean;
};

const REQUEST_COLLECTIONS: CollectionDef[] = [
  { key: 'requester_signature', labelKey: 'guarantor.documents_requester_signature' },
  { key: 'counterparty_signature', labelKey: 'guarantor.documents_counterparty_signature' },
  { key: 'files', labelKey: 'guarantor.documents_files' },
];

const REQUESTER_COLLECTIONS: CollectionDef[] = [
  { key: 'authorized_id', labelKey: 'guarantor.documents_authorized_id' },
  { key: 'contracts', labelKey: 'guarantor.documents_contracts' },
  { key: 'company_documents', labelKey: 'guarantor.documents_company_documents' },
  { key: 'requester_iban_certificate', labelKey: 'guarantor.documents_requester_iban_certificate' },
  { key: 'requester_cr_file', labelKey: 'guarantor.documents_requester_cr_file' },
  { key: 'requester_articles_of_association', labelKey: 'guarantor.documents_requester_articles_of_association' },
  { key: 'requester_national_address_file', labelKey: 'guarantor.documents_requester_national_address_file' },
];

const COUNTERPARTY_COLLECTIONS: CollectionDef[] = [
  { key: 'counterparty_iban_certificate', labelKey: 'guarantor.documents_counterparty_iban_certificate' },
  { key: 'counterparty_cr_file', labelKey: 'guarantor.documents_counterparty_cr_file' },
  { key: 'counterparty_articles_of_association', labelKey: 'guarantor.documents_counterparty_articles_of_association' },
  { key: 'counterparty_national_address_file', labelKey: 'guarantor.documents_counterparty_national_address_file' },
];

const isImage = (mime?: string) => Boolean(mime?.startsWith('image/'));
const isPdf = (mime?: string, name?: string) =>
  Boolean(mime?.includes('pdf') || name?.toLowerCase().endsWith('.pdf'));

const DocumentCard = ({ item }: { item: MediaItem }) => {
  const { t } = useTranslation();
  const label = item.file_name || item.name || t('download');

  return (
    <a
      href={item.url}
      target="_blank"
      rel="noreferrer"
      className="card border border-gray-200 shadow-xs rounded-4 text-decoration-none h-100 hover-elevate-up"
      style={{ width: 160, minHeight: 170 }}
    >
      <div className="card-body d-flex flex-column align-items-center justify-content-center p-4">
        {isImage(item.mime_type) ? (
          <img
            src={item.url}
            alt={label}
            className="rounded-3 border border-gray-100 mb-3 object-fit-cover"
            style={{ width: 96, height: 96 }}
          />
        ) : (
          <div className="symbol symbol-80px mb-3">
            <span className={`symbol-label rounded-3 ${isPdf(item.mime_type, label) ? 'bg-light-danger text-danger' : 'bg-light-primary text-primary'}`}>
              <KTIcon iconName={isPdf(item.mime_type, label) ? 'file' : 'folder'} className="fs-2x" />
            </span>
          </div>
        )}
        <span className="fw-semibold text-gray-800 fs-8 text-center text-truncate w-100" title={label}>
          {label}
        </span>
      </div>
    </a>
  );
};

const EmptyDocumentCard = ({ emptyLabel }: { emptyLabel: string }) => (
  <div
    className="rounded-4 border border-dashed border-gray-300 bg-light d-flex flex-column align-items-center justify-content-center text-center px-4"
    style={{ width: 160, minHeight: 170 }}
  >
    <KTIcon iconName="file-deleted" className="fs-2x text-gray-400 mb-2" />
    <span className="text-muted fs-8 fw-semibold">{emptyLabel}</span>
  </div>
);

const CollectionGroup = ({
  title,
  items,
  emptyLabel,
}: {
  title: string;
  items: MediaItem[];
  emptyLabel: string;
}) => (
  <div className="mb-6">
    <h5 className="fw-bold text-gray-800 mb-4">{title}</h5>
    <div className="d-flex flex-wrap gap-4">
      {items.length === 0 ? (
        <EmptyDocumentCard emptyLabel={emptyLabel} />
      ) : (
        items.map((item) => (
          <DocumentCard key={item.id ?? item.uuid ?? item.url} item={item} />
        ))
      )}
    </div>
  </div>
);

const DocumentsTab = ({ requestMedia = [], companyMedia = [], isCompany }: Props) => {
  const { t } = useTranslation();
  const emptyLabel = t('guarantor.not_uploaded');

  const filterBy = (media: MediaItem[], key: string) =>
    media.filter((item) => item.collection_name === key);

  return (
    <div className="d-flex flex-column gap-2">
      <div className="mb-4">
        <h3 className="fw-bolder text-gray-900 mb-5">{t('guarantor.request_documents')}</h3>
        {REQUEST_COLLECTIONS.map((collection) => (
          <CollectionGroup
            key={collection.key}
            title={t(collection.labelKey)}
            items={filterBy(requestMedia, collection.key)}
            emptyLabel={emptyLabel}
          />
        ))}
      </div>

      {isCompany && (
        <>
          <div>
            <h3 className="fw-bolder text-gray-900 mb-5">{t('guarantor.requester_documents_group')}</h3>
            {REQUESTER_COLLECTIONS.map((collection) => (
              <CollectionGroup
                key={collection.key}
                title={t(collection.labelKey)}
                items={filterBy(companyMedia, collection.key)}
                emptyLabel={emptyLabel}
              />
            ))}
          </div>

          <div>
            <h3 className="fw-bolder text-gray-900 mb-5">{t('guarantor.counterparty_documents_group')}</h3>
            {COUNTERPARTY_COLLECTIONS.map((collection) => (
              <CollectionGroup
                key={collection.key}
                title={t(collection.labelKey)}
                items={filterBy(companyMedia, collection.key)}
                emptyLabel={emptyLabel}
              />
            ))}
          </div>
        </>
      )}
    </div>
  );
};

export default DocumentsTab;
