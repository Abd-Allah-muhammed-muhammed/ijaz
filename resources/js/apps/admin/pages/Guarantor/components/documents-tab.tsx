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
  { key: 'signature', labelKey: 'guarantor.documents_signature' },
  { key: 'files', labelKey: 'guarantor.documents_files' },
];

const COMPANY_COLLECTIONS: CollectionDef[] = [
  { key: 'authorized_id', labelKey: 'guarantor.documents_authorized_id' },
  { key: 'contracts', labelKey: 'guarantor.documents_contracts' },
  { key: 'iban_certificates', labelKey: 'guarantor.documents_iban_certificates' },
  { key: 'company_documents', labelKey: 'guarantor.documents_company_documents' },
];

const isImage = (mime?: string) => Boolean(mime?.startsWith('image/'));

const DocumentItem = ({ item }: { item: MediaItem }) => {
  const { t } = useTranslation();
  const label = item.file_name || item.name || t('download');

  if (isImage(item.mime_type)) {
    return (
      <a href={item.url} target="_blank" rel="noreferrer" className="d-inline-block">
        <img
          src={item.url}
          alt={label}
          className="rounded border"
          style={{ maxWidth: 120, maxHeight: 120, objectFit: 'cover' }}
        />
      </a>
    );
  }

  return (
    <a href={item.url} target="_blank" rel="noreferrer" className="btn btn-light-primary btn-sm">
      {label}
    </a>
  );
};

const CollectionGroup = ({
  title,
  items,
  emptyLabel,
}: {
  title: string;
  items: MediaItem[];
  emptyLabel: string;
}) => (
  <div className="mb-5">
    <h5 className="fw-bold mb-3">{title}</h5>
    {items.length === 0 ? (
      <p className="text-muted fst-italic mb-0">{emptyLabel}</p>
    ) : (
      <div className="d-flex flex-wrap gap-3">
        {items.map((item) => (
          <DocumentItem key={item.id ?? item.uuid ?? item.url} item={item} />
        ))}
      </div>
    )}
  </div>
);

const DocumentsTab = ({ requestMedia = [], companyMedia = [], isCompany }: Props) => {
  const { t } = useTranslation();
  const emptyLabel = t('guarantor.not_uploaded');

  const filterBy = (media: MediaItem[], key: string) =>
    media.filter((item) => item.collection_name === key);

  return (
    <div className="d-flex flex-column gap-8">
      <div>
        <h3 className="fw-bolder mb-5">{t('guarantor.request_documents')}</h3>
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
        <div>
          <h3 className="fw-bolder mb-5">{t('guarantor.company_documents_group')}</h3>
          {COMPANY_COLLECTIONS.map((collection) => (
            <CollectionGroup
              key={collection.key}
              title={t(collection.labelKey)}
              items={filterBy(companyMedia, collection.key)}
              emptyLabel={emptyLabel}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default DocumentsTab;
