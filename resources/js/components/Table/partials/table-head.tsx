import {KTIcon} from "@/_metronic/helpers";

export default function TableHead() {
  return (
    <div className='card-header border-0 pt-6'>
      <div className='card-title'>
        {/* begin::Search */}
        <div className='d-flex align-items-center position-relative my-1'>
          <KTIcon iconName='magnifier' className='fs-1 position-absolute ms-6'/>
          <input
            type='text'
            data-kt-user-table-filter='search'
            className='form-control form-control-solid w-250px ps-14'
            placeholder='Search user'
            // value={searchTerm}
            // onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
        {/* end::Search */}
      </div>
      <div className='card-toolbar'>
        {/*{selected.length > 0 ? <UsersListGrouping /> : <UsersListToolbar />}*/}
        <div className='d-flex justify-content-end' data-kt-user-table-toolbar='base'>
          <button type='button' className='btn btn-primary'>
            <KTIcon iconName='plus' className='fs-2'/>
            Add User
          </button>
        </div>
      </div>
    </div>
  )
}
