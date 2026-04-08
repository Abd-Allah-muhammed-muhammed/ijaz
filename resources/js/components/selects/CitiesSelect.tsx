import useCitiesQuery from "@/hooks/use-cities-query";
import Select, {SingleValue} from "react-select";
import {SelectOption} from "@/types";
import {useTranslation} from "react-i18next";
import {useEffect} from "react";
import {reactSelectStyles} from "./react-select-styles";

type Props = {
  regionId?: number | string;
  value: SelectOption | null;
  onChange: (value: SelectOption | null) => void;
  placeholder?: string;
  isDisabled?: boolean;
  isClearable?: boolean;
}

const CitiesSelect = ({regionId, value, onChange, placeholder, isDisabled = false, isClearable = true}: Props) => {
  const query = useCitiesQuery(regionId);
  const {t} = useTranslation();

  useEffect(() => {
    if (onChange) {
      onChange(null);
    }
  }, [regionId]);

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
      isDisabled={isDisabled}
      isClearable={isClearable}
    />
  )
}

export default CitiesSelect;
