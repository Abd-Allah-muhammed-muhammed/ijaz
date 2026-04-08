import useCarTypesQuery from "@/hooks/use-car-types-query";
import Select, {SingleValue} from "react-select";
import {SelectOption} from "@/types";
import {useTranslation} from "react-i18next";
import {useEffect} from "react";
import {reactSelectStyles} from "./react-select-styles";

type Props = {
  carBrandId?: number | string;
  value: SelectOption | null;
  onChange: (value: SelectOption | null) => void;
  placeholder?: string;
  isDisabled?: boolean;
  isClearable?: boolean;
}

const CarTypesSelect = ({carBrandId, value, onChange, placeholder, isDisabled = false, isClearable = true}: Props) => {
  const query = useCarTypesQuery(carBrandId);
  const {t} = useTranslation();

  useEffect(() => {
    if (onChange) {
      onChange(null);
    }
  }, [carBrandId]);

  if (query.isError) {
    return (
      <div className="text-danger">
        {t('error_loading_data')}
      </div>
    )
  }

  return (
    <Select
      styles={reactSelectStyles}
      isLoading={query.isLoading}
      options={query.data || []}
      placeholder={placeholder || t('choose')}
      loadingMessage={() => t('loading')}
      noOptionsMessage={() => t('no_options')}
      value={value}
      onChange={(selectedOption: SingleValue<SelectOption>) => {
        if (onChange) {
          onChange(selectedOption);
        }
      }}
      isDisabled={isDisabled || !carBrandId}
      isClearable={isClearable}
    />
  )
}

export default CarTypesSelect;
