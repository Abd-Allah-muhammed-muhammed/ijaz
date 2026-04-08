import { useTranslation } from 'react-i18next';
import { ProviderStatusEnum } from "@/Enums/Providers";
import { Link, router, useForm } from "@inertiajs/react";
import ProviderController from "@/actions/App/Http/Controllers/Dashboard/ProviderController";
import { KTIcon } from "@/_metronic/helpers";
import { Provider } from "@/types/models";
import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import ActionButton from "@/components/action-button";

type Props = {
  provider: Provider
}

const ProviderMenu = ({ provider }: Props) => {
  const { t } = useTranslation();
  const swal = withReactContent(Swal)
  const statusForm = useForm<{
    status: string,
    block_days: number | null,
    block_reason: string | null,
  }>({
    status: provider?.status?.value as string || ProviderStatusEnum.Pending,
    block_days: null,
    block_reason: null,
  })

  return (
    <div className='menu menu-sub menu-sub-dropdown w-250px py-4 shadow-sm border-0 rounded-3' data-kt-menu='true'>
      {/* Edit Action */}
      <div className='menu-item px-3'>
        <Link
          href={ProviderController.edit(provider.id as number).url}
          className='menu-link px-3 d-flex align-items-center'
        >
          <span className='menu-icon me-2'>
            <KTIcon iconName='pencil' className='fs-3 text-primary' />
          </span>
          <span className='menu-title fw-bold text-gray-800'>{t('edit')}</span>
        </Link>
      </div>

      {/* Delete Action */}
      <div className='menu-item px-3'>
        <a
          href='#'
          onClick={(e) => {
            e.preventDefault();
            swal.fire({
              title: t('are_you_sure'),
              text: t('delete_warning_text'),
              icon: 'warning',
              showCancelButton: true,
              cancelButtonText: t('cancel'),
              confirmButtonText: t('yes_delete'),
              customClass: {
                confirmButton: 'btn btn-danger btn-sm',
                cancelButton: 'btn btn-light btn-sm'
              },
              buttonsStyling: false
            }).then((result) => {
              if (result.isConfirmed) {
                router.delete(ProviderController.destroy(provider.id as number).url);
              }
            });
          }}
          className='menu-link px-3 d-flex align-items-center'
        >
          <span className='menu-icon me-2'>
            <KTIcon iconName='trash' className='fs-3 text-danger' />
          </span>
          <span className='menu-title fw-bold text-danger'>{t('delete')}</span>
        </a>
      </div>

      <div className='separator my-3 opacity-75'></div>

      {/* Status Management */}
      <div className='px-7 py-3'>
        <div className='fs-9 text-muted fw-bolder text-uppercase mb-3 ls-1'>{t('status')}</div>

        <div className='mb-4'>
          <select
            className='form-select form-select-solid form-select-sm fw-bold border-0 bg-light'
            defaultValue={statusForm.data.status}
            onChange={(e) => {
              statusForm.setData('status', e.target.value)
            }}
          >
            {Object.values(ProviderStatusEnum).map((status) => (
              <option key={'status-' + status} value={status}>
                {t(status)}
              </option>
            ))}
          </select>
        </div>

        {statusForm.data.status === ProviderStatusEnum.Blocked && (
          <div className='mb-4 animate__animated animate__fadeIn'>
            <div className='d-flex flex-column gap-3'>
              <input
                type="number"
                className="form-control form-control-sm form-control-solid fs-7 h-35px"
                defaultValue={statusForm.data.block_days?.toString()}
                onChange={(e) => {
                  statusForm.setData('block_days', Number(e.target.value));
                }}
                placeholder={t('block_days')}
              />
              <textarea
                className="form-control form-control-sm form-control-solid fs-7"
                rows={2}
                defaultValue={statusForm.data.block_reason?.toString()}
                onChange={(e) => {
                  statusForm.setData('block_reason', e.target.value);
                }}
                placeholder={t('block_reason')}
              />
            </div>
          </div>
        )}

        <div className='d-flex justify-content-end'>
          <ActionButton
            isProcessing={statusForm.processing}
            className="btn btn-primary btn-sm w-100 fs-8 fw-bold h-35px"
            onClick={() => {
              statusForm.submit(ProviderController.updateStatus(provider.id as number), {
                preserveScroll: true,
                preserveState: true,
              })
            }}
            text={t('save')}
          />
        </div>
      </div>
    </div>
  )
};

export default ProviderMenu;
