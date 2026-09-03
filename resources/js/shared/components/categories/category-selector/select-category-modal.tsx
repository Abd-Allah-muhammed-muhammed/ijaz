import { useEffect, useState } from 'react'
import { createPortal } from 'react-dom'
import { Modal } from 'react-bootstrap'
import { useTranslation } from 'react-i18next'
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { faXmark } from '@fortawesome/free-solid-svg-icons/faXmark'
import {
  CategoryPicker,
  type CategoryPickerSelection,
} from '@/shared/components/categories/category-picker'
import type { Category } from '@/shared/types/models'

type Props = {
  show: boolean
  handleClose: () => void
  submitCallback: (data: Data[]) => void
  provider_type_id?: string
}

export type Data = {
  category: Category | null
  skills: { value: string; label: string }[]
}

const modalsRoot = document.getElementById('root-modals') || document.body

export const SelectCategoryModal = ({
  show,
  handleClose,
  submitCallback,
  provider_type_id,
}: Props) => {
  const { t } = useTranslation()
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const T = t as (key: string) => string
  const [selection, setSelection] = useState<CategoryPickerSelection[]>([])

  useEffect(() => {
    if (!show) {
      setSelection([])
    }
  }, [show])

  return createPortal(
    <Modal
      tabIndex={-1}
      aria-hidden="false"
      dialogClassName="modal-dialog modal-dialog-centered mw-900px"
      show={show}
      onHide={handleClose}
      backdrop={true}
    >
      <div className="modal-header">
        <h2>{T('choose_selected_categories')}</h2>
        <button
          type="button"
          className="btn btn-sm btn-icon btn-active-color-danger"
          onClick={handleClose}
          aria-label={T('close')}
        >
          <FontAwesomeIcon icon={faXmark} size="2xl" />
        </button>
      </div>
      <div className="modal-body py-lg-10 px-lg-10">
        {show ? (
          <CategoryPicker
            provider_type_id={provider_type_id}
            value={selection}
            onChange={setSelection}
          />
        ) : null}
      </div>
      <div className="modal-footer">
        <button type="button" className="btn btn-light" onClick={handleClose}>
          {T('cancel')}
        </button>
        <button
          type="button"
          className="btn btn-primary"
          disabled={selection.length === 0}
          data-pan="select-category-step-1-submit-btn"
          onClick={() => {
            submitCallback(
              selection.map((item) => ({
                category: {
                  id: item.id,
                  title: item.title,
                  icon: item.icon,
                } as Category,
                skills: [],
              })),
            )
          }}
        >
          {T('submit')}
        </button>
      </div>
    </Modal>,
    modalsRoot,
  )
}
