import {ChangeEvent, EventHandler, HTMLProps, useRef, useState} from 'react';
import {url as AppUrl} from '@/helpers/general';

type Props = HTMLProps<HTMLImageElement> & {
  url?: string;
  callback: (e: ChangeEvent<HTMLInputElement>) => void;
};
export default function ImageInput({url, callback, ...props}: Props) {
  const localRef = useRef<HTMLInputElement>(null);
  const [currentUrl, setCurrentUrl] = useState(url || AppUrl('/media/avatars/blank.png'));
  const onChange: EventHandler<ChangeEvent<HTMLInputElement>> = (
    event: ChangeEvent<HTMLInputElement>,
  ) => {
    const reader = new FileReader();
    reader.onload = () => {
      if (reader.readyState === 2) {
        setCurrentUrl(reader.result as string);
      }
    };
    reader.readAsDataURL(event.target.files![0]);
    if (callback) {
      callback(event);
    }
  };
  return (
    <>
      <img
        style={{
          maxHeight: '100%',
          maxWidth: '100%',
        }}
        {...props}

        src={currentUrl}
        alt="input image"
        onClick={() => {
          if (props.disabled) return;
          localRef.current?.click();
        }}
      />
      <input
        onChange={onChange}
        className="d-none"
        ref={localRef}
        type="file"
        accept="image/*"
      />
    </>
  );
}
