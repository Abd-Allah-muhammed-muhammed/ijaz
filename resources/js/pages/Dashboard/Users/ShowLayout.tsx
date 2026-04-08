import {ToolbarWrapper} from "@/_metronic/layout/components/toolbar";
import {Content} from "@/_metronic/layout/components/content";
import {KTIcon} from "@/_metronic/helpers";
import {ReactElement} from "react";
import {User} from "@/types/models";
import { useTranslation } from 'react-i18next';
import RatingStars from '@/components/RatingStars';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEnvelope, faGlobe, faPhone } from '@fortawesome/free-solid-svg-icons';

type Props = {
  children: ReactElement
  user: User
}

const AccountLayout = ({children, user}: Props) => {
  const { t } = useTranslation();

  return (
    <>
      <ToolbarWrapper/>
      <Content>
        <div className='card mb-5 mb-xl-10'>
          <div className='card-body pt-9 pb-0'>
            <div className='d-flex flex-wrap flex-sm-nowrap mb-3'>
              <div className='me-7 mb-4'>
                <div className='symbol symbol-100px symbol-lg-160px symbol-fixed position-relative'>
                  <img src={user.image} alt={user.f_name[0].toUpperCase() + user.l_name[0].toUpperCase()}/>
                  <div
                    className='position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border-4 border-white h-20px w-20px'></div>
                </div>
              </div>

              <div className='flex-grow-1'>
                <div className='d-flex justify-content-between align-items-start flex-wrap mb-2'>
                  <div className='d-flex flex-column'>
                    <div className='d-flex align-items-center mb-2'>
                      <a href='#' className='text-gray-800 text-hover-primary fs-2 fw-bolder me-1'>
                        {user.name}
                      </a>
                      <a href='#'>
                        <KTIcon iconName='verify' className='fs-1 text-primary'/>
                      </a>
                    </div>

                    <div className='d-flex flex-wrap fw-bold fs-6 mb-4 pe-2'>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2'
                      >
                        <FontAwesomeIcon icon={faEnvelope}  className='fs-4 me-1'/>
                        {user.email}
                      </a>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2'
                      >
                        <FontAwesomeIcon icon={faPhone} className='fs-4 me-1'/>
                        {user.phone}
                      </a>
                      <a
                        href='#'
                        className='d-flex align-items-center text-gray-500 text-hover-primary mb-2'
                      >
                        <FontAwesomeIcon icon={faGlobe} className='fs-4 me-1'/>
                        {user.nationality?.name}
                      </a>
                    </div>
                  </div>
                </div>

                <div className='d-flex flex-wrap flex-stack'>
                  <div className='d-flex flex-column flex-grow-1 pe-8'>
                    <div className='d-flex flex-wrap'>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.balance || 0}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('balance')}</div>
                      </div>

                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.total_earning || 0}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('total_earning')}</div>
                      </div>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.total_spent || 0}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('total_spent')}</div>
                      </div>

                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.credit || 0}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('credit')}</div>
                      </div>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.pending_credit || 0}</div>
                        </div>

                        <div className='fw-bold fs-6 text-gray-500'>{t('pending_credit')}</div>
                      </div>

                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.debit || 0}</div>
                        </div>
                        <div className='fw-bold fs-6 text-gray-500'>{t('debit')}</div>
                      </div>
                      <div className='border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3'>
                        <div className='d-flex align-items-center'>
                          <div className='fs-2 fw-bolder'>{user.wallet?.pending_debit || 0}</div>
                        </div>
                        <div className='fw-bold fs-6 text-gray-500'>{t('pending_debit')}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Content>
      {children}
    </>
  );
}

export default AccountLayout
