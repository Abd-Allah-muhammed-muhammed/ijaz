import ReactQuill from 'react-quill-new';
import 'react-quill-new/dist/quill.snow.css';
import {
  getPageContentEditorModules,
  isPageContentRtlLocale,
  normalizeEditorHtml,
  PAGE_CONTENT_FORMATS,
} from './page-content-editor';

type Props = {
  locale: string;
  value: string;
  placeholder?: string;
  onChange: (html: string) => void;
};

export default function PageContentEditor({
  locale,
  value,
  placeholder,
  onChange,
}: Props) {
  const rtl = isPageContentRtlLocale(locale);

  return (
    <div
      className="page-content-editor"
      dir={rtl ? 'rtl' : 'ltr'}
      data-locale={locale}
      data-testid={`page-content-editor-${locale}`}
    >
      <ReactQuill
        theme="snow"
        value={value || ''}
        placeholder={placeholder}
        modules={getPageContentEditorModules()}
        formats={[...PAGE_CONTENT_FORMATS]}
        onChange={(html) => {
          onChange(normalizeEditorHtml(html));
        }}
      />
    </div>
  );
}
