import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { KTIcon } from '@/vendor/metronic/helpers';
import SectionCard from './section-card';
import {
  STACKED_DATA_TABLE_CARD_CLASS,
  STACKED_DATA_TABLE_BODY_CLASS,
  STACKED_DATA_TABLE_HEADER_CLASS,
  STACKED_DATA_TABLE_HEADER_ROW_CLASS,
  STACKED_DATA_TABLE_DESKTOP_ROW_CLASS,
  STACKED_DATA_TABLE_MOBILE_ROW_CLASS,
  STACKED_DATA_TABLE_SEPARATOR_CLASS,
  STACKED_DATA_TABLE_META_SEPARATOR,
  type StackedDataTableProps,
} from './types';

function columnShellClass(widthClassName: string | undefined, grow: boolean | undefined): string {
  if (grow) {
    return 'flex-grow-1 min-w-0';
  }

  return [widthClassName, 'min-w-0'].filter(Boolean).join(' ');
}

export default function StackedDataTable<T>({
  rows,
  columns,
  getRowKey,
  emptyState,
  mobileHref,
  mobileTrailing,
  className,
  toolbar,
}: StackedDataTableProps<T>) {
  const titleColumns = columns.filter((column) => column.mobile === 'title');
  const badgeColumns = columns.filter((column) => column.mobile === 'badge');
  const metaColumns = columns.filter((column) => column.mobile === 'meta');

  const cardClass = [STACKED_DATA_TABLE_CARD_CLASS, className].filter(Boolean).join(' ');

  return (
    <SectionCard
      className={cardClass}
      bodyClassName={STACKED_DATA_TABLE_BODY_CLASS}
      header={
        <div className={STACKED_DATA_TABLE_HEADER_ROW_CLASS}>
          {columns.map((column) => (
            <span
              key={column.id}
              className={[
                columnShellClass(column.widthClassName, column.grow),
                column.align === 'end' ? 'text-end' : null,
                column.headerClassName,
              ]
                .filter(Boolean)
                .join(' ')}
            >
              {column.header}
            </span>
          ))}
        </div>
      }
      headerClassName={STACKED_DATA_TABLE_HEADER_CLASS}
    >
      {toolbar ? <div className="px-4 px-lg-6 pt-4 pb-2">{toolbar}</div> : null}

      {rows.length === 0 ? (
        <div className="px-4 px-lg-6 py-6">{emptyState}</div>
      ) : (
        rows.map((row, index) => {
          const key = getRowKey(row);
          const href = mobileHref?.(row);
          const trailing =
            mobileTrailing?.(row) ??
            (href ? (
              <KTIcon
                iconName="arrow-right"
                className="fs-2 text-gray-500 flex-shrink-0"
                aria-hidden="true"
              />
            ) : null);

          const mobileBody = (
            <>
              <div className="flex-grow-1 min-w-0">
                <div className="d-flex align-items-center justify-content-between gap-2 mb-1">
                  <span className="fw-semibold fs-6 text-gray-800 text-truncate">
                    {titleColumns.map((column) => (
                      <Fragment key={column.id}>{column.cell(row)}</Fragment>
                    ))}
                  </span>
                  {badgeColumns.length > 0 ? (
                    <span className="d-flex align-items-center gap-1 flex-shrink-0 flex-wrap justify-content-end">
                      {badgeColumns.map((column) => (
                        <Fragment key={column.id}>{column.cell(row)}</Fragment>
                      ))}
                    </span>
                  ) : null}
                </div>
                {metaColumns.length > 0 ? (
                  <div className="d-flex flex-wrap align-items-center column-gap-2 row-gap-1 fs-8 text-muted">
                    {metaColumns.map((column, metaIndex) => (
                      <Fragment key={column.id}>
                        {metaIndex > 0 ? (
                          <span aria-hidden="true">{STACKED_DATA_TABLE_META_SEPARATOR}</span>
                        ) : null}
                        {column.cell(row)}
                      </Fragment>
                    ))}
                  </div>
                ) : null}
              </div>
              {trailing}
            </>
          );

          return (
            <div key={key}>
              {href ? (
                <Link
                  href={href}
                  className={`${STACKED_DATA_TABLE_MOBILE_ROW_CLASS} text-decoration-none`}
                >
                  {mobileBody}
                </Link>
              ) : (
                <div className={STACKED_DATA_TABLE_MOBILE_ROW_CLASS}>{mobileBody}</div>
              )}

              <div className={STACKED_DATA_TABLE_DESKTOP_ROW_CLASS}>
                {columns.map((column) => (
                  <div
                    key={column.id}
                    className={[
                      columnShellClass(column.widthClassName, column.grow),
                      column.align === 'end' ? 'text-end' : null,
                      column.cellClassName,
                    ]
                      .filter(Boolean)
                      .join(' ')}
                  >
                    {column.cell(row)}
                  </div>
                ))}
              </div>

              {index !== rows.length - 1 ? (
                <div className={STACKED_DATA_TABLE_SEPARATOR_CLASS} />
              ) : null}
            </div>
          );
        })
      )}
    </SectionCard>
  );
}
