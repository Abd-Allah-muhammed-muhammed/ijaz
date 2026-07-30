import React, {useState} from "react";

type SupportingTypes = 'characters' | 'number' | 'alphanumeric';

type Props = {
  type?: SupportingTypes;
  pin?: boolean;
  length?: number;
  onChange?: (value: string) => void;
}
const OTP = ({type = 'number', length = 4, pin = false, onChange}: Props) => {
  const [value, setValue] = useState<string[]>(Array(length).fill(''));
  const [mask, setMask] = useState<string[]>(Array(length).fill(''))

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>, index: number) => {
    const element = e.target as HTMLInputElement;
    if (e.key === 'Backspace' && !mask[index] && index > 0) {
      const prevInput = element.previousSibling;
      if (prevInput) {
        (prevInput as HTMLInputElement).focus();
      }
      return;
    }
    e.currentTarget.setSelectionRange(1, 1); // Set cursor position to the end of the input
    if (e.key === 'ArrowLeft' && index > 0) {

      const prevInput = element.previousSibling;
      if (prevInput) {
        (prevInput as HTMLInputElement).focus();
      }
      return;
    }
    if (e.key === 'ArrowRight' && index < length - 1) {
      const nextInput = element.nextSibling;
      if (nextInput) {
        (nextInput as HTMLInputElement).focus();
      }
      return;
    }
  };
  const handelClick = (event: React.MouseEvent<HTMLInputElement>, index: number) => {
    event.currentTarget.setSelectionRange(1, 1); // Set cursor position to the end of the input
  }
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>, index: number) => {
    const newValue = [...value];
    const newMask = [...mask];
    const element = e.target as HTMLInputElement;
    const inputValue = element.value;
    if (type === 'number' && !/^\d*$/.test(inputValue)) {
      return; // Only allow numbers
    } else if (type === 'characters' && !/^[a-zA-Z]*$/.test(inputValue)) {
      return; // Only allow characters
    } else if (type === 'alphanumeric' && !/^[a-zA-Z0-9]*$/.test(inputValue)) {
      return; // Only allow alphanumeric characters
    }

    newValue[index] = inputValue.substring(inputValue.length - 1);
    newMask[index] = (pin && inputValue != '') ? '*' : inputValue.substring(inputValue.length - 1);

    setValue(newValue);
    setMask(newMask);

    if (onChange && newValue.every(v => v !== '') && newValue.length === length) {
      onChange(newValue.join(''));
    }

    if (inputValue && index < length - 1) {
      const nextInput = element.nextSibling;
      if (nextInput) {
        (nextInput as HTMLInputElement).focus();
      }
    }
  };
  return (
    <div className="d-flex flex-wrap flex-stack" style={{
      direction : 'ltr'
    }}>
      {mask.map((m, index) => (
        <input
          key={index}
          type="text"
          maxLength={1}
          className="form-control bg-transparent h-60px w-60px fs-2qx text-center mx-1 my-2"
          value={m}
          onChange={(e) => handleChange(e, index)}
          onKeyDown={(e) => handleKeyDown(e, index)}
          // style={{caretColor: 'transparent', color: 'transparent', textShadow: '0 0 0 #000'}}
          autoComplete="off"
          onClick={(e) => handelClick(e, index)}
        />
      ))}
    </div>
  );

}


export default OTP
