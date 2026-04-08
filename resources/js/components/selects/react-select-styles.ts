import {StylesConfig} from 'react-select';
import {SelectOption} from '@/types';

export const reactSelectStyles: StylesConfig<SelectOption, false> = {
  control: (base, state) => ({
    ...base,
    width: '100%',
    minHeight: '42px',
    fontSize: '1.1rem',
    fontWeight: 500,
    fontFamily: 'inherit',
    lineHeight: 1.5,
    color: 'var(--bs-gray-700)',
    backgroundColor: state.isDisabled ? 'var(--bs-gray-200)' : 'var(--bs-body-bg)',
    border: '1px solid var(--bs-gray-300)',
    borderRadius: '0.475rem',
    boxShadow: 'none',
    transition: 'border-color .15s ease-in-out, box-shadow .15s ease-in-out',
    cursor: 'pointer',
    '&:hover': {
      borderColor: 'var(--bs-gray-400)',
    },
    ...(state.isFocused && {
      borderColor: 'var(--bs-primary)',
      boxShadow: '0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25)',
    }),
  }),
  valueContainer: (base) => ({
    ...base,
    padding: '0.775rem 1rem',
    cursor: 'pointer',
  }),
  placeholder: (base) => ({
    ...base,
    color: 'var(--bs-gray-500)',
    margin: 0,
  }),
  singleValue: (base) => ({
    ...base,
    color: 'var(--bs-gray-800)',
    margin: 0,
  }),
  input: (base) => ({
    ...base,
    padding: 0,
    margin: 0,
    color: 'var(--bs-gray-800)',
  }),
  indicatorSeparator: () => ({
    display: 'none',
  }),
  dropdownIndicator: (base, state) => ({
    ...base,
    color: 'var(--bs-gray-500)',
    padding: '0 1rem',
    transition: 'transform 0.2s ease',
    transform: state.selectProps.menuIsOpen ? 'rotate(180deg)' : 'rotate(0deg)',
    '&:hover': {
      color: 'var(--bs-gray-700)',
    },
  }),
  clearIndicator: (base) => ({
    ...base,
    color: 'var(--bs-gray-500)',
    padding: '0 0.5rem',
    '&:hover': {
      color: 'var(--bs-danger)',
    },
  }),
  loadingIndicator: (base) => ({
    ...base,
    color: 'var(--bs-primary)',
  }),
  menu: (base) => ({
    ...base,
    border: 0,
    borderRadius: '0.475rem',
    padding: '0.5rem 0',
    backgroundColor: 'var(--bs-body-bg)',
    boxShadow: '0 0 50px 0 rgba(82, 63, 105, 0.15)',
    zIndex: 1050,
  }),
  menuList: (base) => ({
    ...base,
    padding: 0,
  }),
  option: (base, state) => ({
    ...base,
    cursor: 'pointer',
    color: state.isSelected ? 'var(--bs-primary)' : 'var(--bs-gray-700)',
    backgroundColor: state.isSelected
      ? 'var(--bs-primary-light)'
      : state.isFocused
        ? 'var(--bs-gray-100)'
        : 'transparent',
    padding: '0.75rem 1.25rem',
    transition: 'color 0.2s ease, background-color 0.2s ease',
    fontWeight: state.isSelected ? 600 : 500,
    '&:active': {
      backgroundColor: 'var(--bs-gray-200)',
    },
  }),
  multiValue: (base) => ({
    ...base,
    backgroundColor: 'var(--bs-gray-200)',
    borderRadius: '0.425rem',
  }),
  multiValueLabel: (base) => ({
    ...base,
    color: 'var(--bs-gray-700)',
    fontSize: '1rem',
    padding: '0.25rem 0.5rem',
  }),
  multiValueRemove: (base) => ({
    ...base,
    color: 'var(--bs-gray-500)',
    borderRadius: '0 0.425rem 0.425rem 0',
    '&:hover': {
      backgroundColor: 'var(--bs-danger)',
      color: 'var(--bs-white)',
    },
  }),
  noOptionsMessage: (base) => ({
    ...base,
    color: 'var(--bs-gray-500)',
    padding: '0.75rem 1.25rem',
  }),
  loadingMessage: (base) => ({
    ...base,
    color: 'var(--bs-gray-500)',
    padding: '0.75rem 1.25rem',
  }),
};

export const reactSelectMultiStyles: StylesConfig<SelectOption, true> = reactSelectStyles as StylesConfig<SelectOption, true>;
