import {PaginationResource} from "@/types";
import {Model} from "@/types/models";
import {KTCardBody, KTIcon} from "@/_metronic/helpers";
import {Table as BTTable} from "react-bootstrap";
import {ReactElement} from "react";
import Pagination from "./partials/Pagination";
import {useTranslation} from "react-i18next";
import {ActionCell} from "@/components/Table/index";

type TableHeader<T extends Model> = {
  title: string
  property: string,
  render?: (row: T) => ReactElement | string | number | boolean
}
type TableAction<T extends Model> = {
  ele: (row: T) => ReactElement
  show: boolean,
}

type Props<T extends Model> = {
  /** The name of the table */
  name:string
  /** The rows to be displayed in the table */
  rows: PaginationResource<T>
  /** The headers of the table */
  headers: TableHeader<T>[],
  /** The actions to be displayed in the table */
  actions?: TableAction<T>[],
  /** The add button to be displayed in the table */
  addButton?: ReactElement,
  /** The search input to be displayed in the table */
  search?: {
    value: string
    callback: (value: string) => void
  },
  only? : string[]
}
export default function Table<T extends Model>(
  {
    name,
    rows,
    headers,
    actions = [],
    addButton,
    search,
    only
  }: Props<T>
) {
  actions = actions.filter(action => action.show)
  const {t} = useTranslation()
  // const [selected, setSelected] = useState<Set<number>>(new Set())
  return (
    <>
      <div className='card-header border-0 pt-6'>
        <div className='card-title'>
          {/* begin::Search */}
          <div className='d-flex align-items-center position-relative my-1'>
            <KTIcon iconName='magnifier' className='fs-1 position-absolute ms-6'/>
            <input
              type='text'
              defaultValue={search?.value}
              data-kt-user-table-filter='search'
              className='form-control form-control-solid w-250px ps-14'
              placeholder='Search'
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  search?.callback?.(e.currentTarget.value)
                }
              }}
            />
          </div>
          {/* end::Search */}
        </div>
        <div className='card-toolbar'>
          {/*{selected.length > 0 ? <UsersListGrouping /> : <UsersListToolbar />}*/}
          <div className='d-flex justify-content-end' data-kt-user-table-toolbar='base'>
            {addButton}
          </div>
        </div>
      </div>
      <KTCardBody className='py-4'>
        <BTTable responsive>
          <thead>
          <tr className='min-w-125px cursor-pointer'>
            {headers.map(header => (
              <th key={`${name}-table-${header.title}`} className='text-start text-muted fw-bolder fs-7 text-uppercase gs-0'>
                {header.title}
              </th>
            ))}
            {actions?.length > 0 && (
              <th  className='text-end text-muted fw-bolder fs-7 text-uppercase gs-0'>
                {t('actions')}
              </th>
            )}
          </tr>
          </thead>
          <tbody>
          {rows.data.length > 0
            ? (<>{rows.data.map((row: T, index: number) => (
                <tr key={`${name}-table-row-${index}`}>
                  {headers.map(header => (
                    <td
                      key={`${name}-table-${header.property}-${row.id}`}
                    >
                      {header.render ? header.render(row) : row[header.property] as unknown as string}
                    </td>
                  ))}
                  {actions?.length > 0 && (
                    <td className='text-end  fw-bolder  gs-0'>
                      <ActionCell key={`${name}-table-action-cell-${row.id}`}>
                        {actions.map((action, index) => (
                          <div key={`${name}-table-action-btn-${index}-${row.id}`} className='menu-item px-3'>
                            {action.ele(row)}
                          </div>
                        ))}
                      </ActionCell>
                    </td>
                  )}
                </tr>
              ))}
              </>
            )
            : (
              <tr>
                <td colSpan={headers.length}>
                  <div className='d-flex text-center w-100 align-content-center justify-content-center'>
                    No matching records found
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </BTTable>
        <Pagination only={only} paginationMeta={rows.meta} preserveScroll/>
      </KTCardBody>
    </>
  )
}
