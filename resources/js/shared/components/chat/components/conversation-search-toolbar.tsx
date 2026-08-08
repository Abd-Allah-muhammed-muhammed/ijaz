import React from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from 'react-bootstrap';
import { KTIcon } from '@/vendor/metronic/helpers';

export type ConversationSearchToolbarProps = {
  searchOpen: boolean;
  searchInput: string;
  onSearchInputChange: (value: string) => void;
  onToggleSearch: () => void;
  /** Optional close (X) control for Provider inbox. */
  showCloseButton?: boolean;
  onClose?: () => void;
};

/**
 * Search toggle + input (+ optional close). Rendered inside whatever header
 * owns the conversation chrome — default ConversationHeader or a page headerSlot.
 */
const ConversationSearchToolbar = ({
  searchOpen,
  searchInput,
  onSearchInputChange,
  onToggleSearch,
  showCloseButton = false,
  onClose,
}: ConversationSearchToolbarProps) => {
  const { t } = useTranslation();

  return (
    <div className='d-flex align-items-center gap-1 me-n1'>
      {searchOpen ? (
        <div className='d-flex align-items-center position-relative me-2' style={{ minWidth: 160, maxWidth: 240 }}>
          <KTIcon iconName='magnifier' className='fs-3 position-absolute ms-3 text-gray-500' />
          <input
            type='search'
            className='form-control form-control-sm form-control-solid ps-10'
            placeholder={t('Search messages', { defaultValue: 'Search messages' })}
            value={searchInput}
            autoFocus
            onChange={(e) => onSearchInputChange(e.target.value)}
            aria-label={t('Search messages', { defaultValue: 'Search messages' })}
          />
        </div>
      ) : null}
      <Button
        variant={searchOpen ? 'light-primary' : 'outline-secondary'}
        size='sm'
        className='btn-icon'
        aria-label={searchOpen
          ? t('Close search', { defaultValue: 'Close search' })
          : t('Search messages', { defaultValue: 'Search messages' })}
        onClick={onToggleSearch}
      >
        <KTIcon iconName='magnifier' className="fs-2" />
      </Button>
      {showCloseButton && onClose ? (
        <Button variant={'outline-secondary'} size='sm' onClick={onClose}>
          <KTIcon iconName='cross' className="fs-2" />
        </Button>
      ) : null}
    </div>
  );
};

export default ConversationSearchToolbar;
