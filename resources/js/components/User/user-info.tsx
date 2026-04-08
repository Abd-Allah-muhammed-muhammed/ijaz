import {toAbsoluteUrl} from "@/_metronic/helpers";
import clsx from "clsx";
import {UserWithAvatar} from "@/types/models";
import {useMemo} from "react";


type Props = {
  user: UserWithAvatar
}

export default function UserInfo({user}: Props) {

  const stats = useMemo(() => {
    return ['primary', 'success', 'danger', 'warning', 'info'];
  }, [])
  const state = useMemo(() => {
    return stats[Math.floor(Math.random() * stats.length)];
  }, [stats])


  return (
    <div className='d-flex align-items-center'>
      {/* begin:: Avatar */}
      <div className='symbol symbol-circle symbol-50px overflow-hidden me-3'>
        <a href='#'>
          {user.image ? (
            <div className='symbol-label'>
              <img src={user.image} alt={user.name} className='w-100'/>
            </div>
          ) : (
            <div
              className={clsx(
                'symbol-label fs-3',
                `bg-light-${state}`,
                `text-${state}`
              )}
            >
              {user.name[0].toUpperCase()}
            </div>
          )}
        </a>
      </div>
      <div className='d-flex flex-column'>
        <a href='#' className='text-gray-800 text-hover-primary mb-1'>
          {user.name}
        </a>
        <span>{user.email}</span>
      </div>
    </div>
  );
}
