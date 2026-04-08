import { useTranslation } from 'react-i18next';
import { Link, router, useForm } from '@inertiajs/react';
import UserController from "@/actions/App/Http/Controllers/Dashboard/UserController";
import { KTIcon } from "@/_metronic/helpers";
import { User } from "@/types/models";
import Swal from "sweetalert2";
import withReactContent from "sweetalert2-react-content";
import { UserStatusEnum } from '@/Enums/Users';
import ActionButton from '@/components/action-button';

type Props = {
  user: User
}

const UserMenu = ({ user }: Props) => {
  const { t } = useTranslation();
  const swal = withReactContent(Swal)
  const statusForm = useForm<{
    status: string,
    block_days: number | null,
    block_reason: string | null,
  }>({
    status: user?.status?.value || UserStatusEnum.Active,
    block_days: null,
    block_reason: null,
  })

  return (
    <div className='menu menu-sub menu-sub-dropdown w-250px py-4 shadow-sm border-0 rounded-3' data-kt-menu='true'>
      {/* View Action */}
      <div className='menu-item px-3'>
        <Link
          href={UserController.show({ user: { id: Number(user.id) } }).url}
          className='menu-link px-3 d-flex align-items-center'
        >
          <span className='menu-icon me-2'>
            <KTIcon iconName='eye' className='fs-3 text-primary' />
          </span>
          <span className='menu-title fw-bold text-gray-800'>{t('view')}</span>
        </Link>
      </div>

      {/* Edit Action */}
      <div className='menu-item px-3'>
        <Link
          href={UserController.edit(user.id as number).url}
          className='menu-link px-3 d-flex align-items-center'
        >
          <span className='menu-icon me-2'>
            <KTIcon iconName='pencil' className='fs-3 text-info' />
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
                router.delete(UserController.destroy(user.id as number).url);
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
            {Object.values(UserStatusEnum).map((status) => (
              <option key={'status-' + status} value={status}>
                {t(status)}
              </option>
            ))}
          </select>
        </div>

        {statusForm.data.status === UserStatusEnum.Blocked && (
          <div className='mb-4 animate__animated animate__fadeIn'>
            <div className='d-flex flex-column gap-3'>
              {user.blocked_until && (
                <div className='bg-light-danger p-2 rounded'>
                  <div className='fs-8 fw-bold text-danger'>{t('currently_blocked_until')}</div>
                  <div className='fs-8 text-gray-700'>{new Date(user.blocked_until).toLocaleDateString()}</div>
                </div>
              )}
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
              statusForm.submit(UserController.updateStatus(user.id as number), {
                preserveScroll: true,
                preserveState: true,
              })
            }}
            text={t('save_status')}
          />
        </div>
      </div>
    </div>
  )
};

export default UserMenu;
