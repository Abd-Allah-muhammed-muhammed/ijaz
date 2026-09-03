import { KTIcon } from '@/vendor/metronic/helpers';
import {
  PAGE_FILTER_BAR_CLASS,
  PAGE_FILTER_DATE_CLASS,
  PAGE_FILTER_SEARCH_ICON_CLASS,
  PAGE_FILTER_SEARCH_INPUT_CLASS,
  PAGE_FILTER_SELECT_CLASS,
  type PageFilterBarProps,
  type PageFilterField,
} from './types';

function SearchFilterField({
  field,
  onFilterChange,
}: {
  field: PageFilterField;
  onFilterChange: PageFilterBarProps['onFilterChange'];
}) {
  return (
    <div className="d-flex align-items-center position-relative my-1">
      <KTIcon iconName="magnifier" className={PAGE_FILTER_SEARCH_ICON_CLASS} />
      <input
        type="text"
        name={field.name}
        defaultValue={field.value ?? ''}
        data-kt-user-table-filter="search"
        className={field.className ?? PAGE_FILTER_SEARCH_INPUT_CLASS}
        placeholder={field.placeholder}
        onKeyDown={(event) => {
          if (event.key === 'Enter') {
            onFilterChange(field.name, event.currentTarget.value);
          }
        }}
      />
    </div>
  );
}

function SelectFilterField({
  field,
  onFilterChange,
}: {
  field: PageFilterField;
  onFilterChange: PageFilterBarProps['onFilterChange'];
}) {
  return (
    <div className={field.widthClassName ?? 'w-200px'}>
      <select
        name={field.name}
        data-control="select2"
        data-hide-search="true"
        className={field.className ?? PAGE_FILTER_SELECT_CLASS}
        defaultValue={field.value ?? ''}
        onChange={(event) => onFilterChange(field.name, event.target.value)}
      >
        {(field.options ?? []).map((option) => (
          <option key={`${field.name}-${option.value}`} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
}

function DateFilterField({
  field,
  onFilterChange,
}: {
  field: PageFilterField;
  onFilterChange: PageFilterBarProps['onFilterChange'];
}) {
  return (
    <div className={field.widthClassName ?? 'w-150px'}>
      <input
        type="date"
        name={field.name}
        className={field.className ?? PAGE_FILTER_DATE_CLASS}
        placeholder={field.placeholder}
        defaultValue={field.value ?? ''}
        onChange={(event) => onFilterChange(field.name, event.target.value)}
      />
    </div>
  );
}

export default function PageFilterBar({
  filters,
  onFilterChange,
  className,
}: PageFilterBarProps) {
  const searchFields = filters.filter((field) => field.type === 'search');
  const controlFields = filters.filter((field) => field.type !== 'search');
  const rootClass = [PAGE_FILTER_BAR_CLASS, className].filter(Boolean).join(' ');

  return (
    <div className={rootClass}>
      <h3 className="fw-bolder my-2">
        {searchFields.map((field) => (
          <SearchFilterField
            key={field.name}
            field={field}
            onFilterChange={onFilterChange}
          />
        ))}
      </h3>

      {controlFields.length > 0 ? (
        <div className="d-flex align-items-center my-2 gap-2">
          {controlFields.map((field) => {
            if (field.type === 'select') {
              return (
                <SelectFilterField
                  key={field.name}
                  field={field}
                  onFilterChange={onFilterChange}
                />
              );
            }

            if (field.type === 'date') {
              return (
                <DateFilterField
                  key={field.name}
                  field={field}
                  onFilterChange={onFilterChange}
                />
              );
            }

            return null;
          })}
        </div>
      ) : null}
    </div>
  );
}
