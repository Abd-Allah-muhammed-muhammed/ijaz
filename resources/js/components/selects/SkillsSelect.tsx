import useGetSkills from "@/hooks/use-getSkills";
import Select, {MultiValue} from "react-select";
import {SelectOption} from "@/types";
import {useTranslation} from "react-i18next";
import {useEffect} from "react";
import {reactSelectMultiStyles} from "./react-select-styles";

type Props = {
  categoryId: number | string;
  values: SelectOption[];
  setValues: (values: SelectOption[]) => void;
  placeholder?: string;
  isDisabled?: boolean;
}

const SkillsSelect = ({categoryId, values, setValues, placeholder, isDisabled = false}: Props) => {
  const query = useGetSkills(categoryId);
  const {t} = useTranslation();

  useEffect(() => {
    setValues([]);
  }, [categoryId]);

  if (query.isError) {
    return (
      <div className="text-danger">
        {t('error_loading_data')}
      </div>
    )
  }

  return (
    <Select
      isMulti
      styles={reactSelectMultiStyles}
      isLoading={query.isLoading}
      options={query.data || []}
      placeholder={placeholder || t('choose')}
      loadingMessage={() => t('loading')}
      noOptionsMessage={() => t('no_options')}
      value={values}
      onChange={(selectedOptions: MultiValue<SelectOption>) => {
        setValues(selectedOptions as SelectOption[]);
      }}
      isDisabled={isDisabled || !categoryId}
    />
  )
}

export default SkillsSelect;
