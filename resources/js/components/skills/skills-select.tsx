import useGetSkills from "@/hooks/use-getSkills";
import Select from "react-select";
import {trans} from "@/hooks/use-translation";
import {useEffect} from "react";


type Props = {
  categoryId: number;
  values: { label: string; value: string }[];
  setValues: (values: { label: string; value: string }[]) => void;
}
const SkillsSelect = ({categoryId, values, setValues}: Props) => {
  const query = useGetSkills(categoryId);

  if (query.isError) {
    console.error('Error fetching skills:', query.error);
    return (
      <div className="text-red-500">
        Something went wrong while fetching skills. Please try again later.
      </div>
    )
  }
  useEffect(() => {
    setValues([]);
  }, [categoryId]);
  return (
    <Select
      isMulti
      isLoading={query.isLoading}
      options={query.data || []}
      placeholder={trans('choose')}
      classNamePrefix="select"
      loadingMessage={() => trans('loading')}
      noOptionsMessage={() => trans('no_options')}
      value={values}
      onChange={(selectedOptions) => {
        setValues(selectedOptions as { label: string; value: string }[]);
      }
      }
    />
  )
}
export default SkillsSelect
