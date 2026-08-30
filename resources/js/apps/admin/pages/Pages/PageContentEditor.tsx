import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faAlignCenter,
  faAlignJustify,
  faAlignLeft,
  faAlignRight,
  faBold,
  faCode,
  faItalic,
  faLink,
  faListOl,
  faListUl,
  faImage,
  faMinus,
  faQuoteRight,
  faStrikethrough,
  faTable,
  faUnderline,
  faUpload,
} from '@fortawesome/free-solid-svg-icons';
import { uploadContentImage } from '@/actions/Modules/Cms/Http/Controllers/Dashboard/PageController';
import { EditorContent, useEditor } from '@tiptap/react';
import clsx from 'clsx';
import { useEffect, useRef, useState, type ChangeEvent, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import apiClient from '@/shared/lib/api-client';
import {
  buildPageContentImageHtml,
  getPageContentEditorExtensions,
  isPageContentRtlLocale,
  normalizeEditorHtml,
  PAGE_CONTENT_HEADING_LEVELS,
  PAGE_CONTENT_LOGO_HTML,
  PAGE_CONTENT_TEXT_COLORS,
  uploadPageContentImage,
} from './page-content-editor';
import './page-content-editor.css';

type Props = {
  locale: string;
  value: string;
  placeholder?: string;
  onChange: (html: string) => void;
};

type BlockType = 'paragraph' | `heading-${(typeof PAGE_CONTENT_HEADING_LEVELS)[number]}`;

function ToolbarButton({
  active,
  label,
  onClick,
  disabled,
  children,
}: {
  active?: boolean;
  label: string;
  onClick: () => void;
  disabled?: boolean;
  children: ReactNode;
}) {
  return (
    <button
      type="button"
      className={clsx(
        'btn btn-sm rounded-pill px-3 py-1 d-inline-flex align-items-center justify-content-center',
        active ? 'btn-primary' : 'btn-light text-gray-700 btn-active-light-primary',
      )}
      aria-label={label}
      title={label}
      aria-pressed={active}
      disabled={disabled}
      onClick={onClick}
    >
      {children}
    </button>
  );
}

export default function PageContentEditor({
  locale,
  value,
  placeholder,
  onChange,
}: Props) {
  const { t } = useTranslation();
  const rtl = isPageContentRtlLocale(locale);
  const [linkUrl, setLinkUrl] = useState('');
  const [linkOpen, setLinkOpen] = useState(false);
  const [uploadingImage, setUploadingImage] = useState(false);
  const [imageUploadError, setImageUploadError] = useState<string | null>(null);
  const imageInputRef = useRef<HTMLInputElement>(null);

  const editor = useEditor({
    extensions: getPageContentEditorExtensions(placeholder),
    content: value || '',
    immediatelyRender: false,
    editorProps: {
      attributes: {
        class: 'page-content-editor__prose form-control',
        dir: rtl ? 'rtl' : 'ltr',
      },
    },
    onUpdate: ({ editor: current }) => {
      onChange(normalizeEditorHtml(current.getHTML()));
    },
  });

  useEffect(() => {
    if (!editor) {
      return;
    }

    const current = normalizeEditorHtml(editor.getHTML());
    const next = normalizeEditorHtml(value || '');

    if (current !== next) {
      editor.commands.setContent(next || '', { emitUpdate: false });
    }
  }, [editor, value]);

  useEffect(() => {
    if (!editor) {
      return;
    }

    editor.setOptions({
      editorProps: {
        attributes: {
          class: 'page-content-editor__prose form-control',
          dir: rtl ? 'rtl' : 'ltr',
        },
      },
    });
  }, [editor, rtl]);

  if (!editor) {
    return null;
  }

  const currentBlock = ((): BlockType => {
    for (const level of PAGE_CONTENT_HEADING_LEVELS) {
      if (editor.isActive('heading', { level })) {
        return `heading-${level}`;
      }
    }

    return 'paragraph';
  })();

  const applyBlock = (block: BlockType) => {
    if (block === 'paragraph') {
      editor.chain().focus().setParagraph().run();
      return;
    }

    const level = Number(block.replace('heading-', '')) as (typeof PAGE_CONTENT_HEADING_LEVELS)[number];
    editor.chain().focus().toggleHeading({ level }).run();
  };

  const applyLink = () => {
    const href = linkUrl.trim();

    if (!href) {
      editor.chain().focus().extendMarkRange('link').unsetLink().run();
      setLinkOpen(false);
      setLinkUrl('');
      return;
    }

    const withProtocol = /^https?:\/\//i.test(href) ? href : `https://${href}`;
    editor.chain().focus().extendMarkRange('link').setLink({ href: withProtocol }).run();
    setLinkOpen(false);
    setLinkUrl('');
  };

  const handleImageFileSelected = async (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    event.target.value = '';

    if (!file) {
      return;
    }

    setImageUploadError(null);
    setUploadingImage(true);

    try {
      const uploaded = await uploadPageContentImage(
        file,
        async (url, body) => {
          const { data } = await apiClient.post(url, body);
          return data;
        },
        uploadContentImage.url(),
      );

      editor
        .chain()
        .focus()
        .insertContent(buildPageContentImageHtml(uploaded.url, file.name.replace(/\.[^.]+$/, '')))
        .run();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Image upload failed.';
      setImageUploadError(message);
    } finally {
      setUploadingImage(false);
    }
  };

  return (
    <div
      className="page-content-editor border border-gray-300 rounded-3 overflow-hidden bg-white"
      dir={rtl ? 'rtl' : 'ltr'}
      data-locale={locale}
      data-testid={`page-content-editor-${locale}`}
    >
      <div
        className="page-content-editor__toolbar d-flex flex-wrap align-items-center gap-2 p-3 border-bottom border-gray-200 bg-light"
        data-testid={`page-content-editor-toolbar-${locale}`}
      >
        <select
          className="form-select form-select-sm w-auto rounded-pill fw-semibold"
          aria-label="Block type"
          value={currentBlock}
          onChange={(e: ChangeEvent<HTMLSelectElement>) => {
            applyBlock(e.target.value as BlockType);
          }}
        >
          <option value="paragraph">Paragraph</option>
          {PAGE_CONTENT_HEADING_LEVELS.map((level) => (
            <option key={level} value={`heading-${level}`}>
              Heading {level}
            </option>
          ))}
        </select>

        <ToolbarButton
          label="Bold"
          active={editor.isActive('bold')}
          onClick={() => editor.chain().focus().toggleBold().run()}
        >
          <FontAwesomeIcon icon={faBold} />
        </ToolbarButton>
        <ToolbarButton
          label="Italic"
          active={editor.isActive('italic')}
          onClick={() => editor.chain().focus().toggleItalic().run()}
        >
          <FontAwesomeIcon icon={faItalic} />
        </ToolbarButton>
        <ToolbarButton
          label="Underline"
          active={editor.isActive('underline')}
          onClick={() => editor.chain().focus().toggleUnderline().run()}
        >
          <FontAwesomeIcon icon={faUnderline} />
        </ToolbarButton>
        <ToolbarButton
          label="Strikethrough"
          active={editor.isActive('strike')}
          onClick={() => editor.chain().focus().toggleStrike().run()}
        >
          <FontAwesomeIcon icon={faStrikethrough} />
        </ToolbarButton>
        <ToolbarButton
          label="Code"
          active={editor.isActive('code')}
          onClick={() => editor.chain().focus().toggleCode().run()}
        >
          <FontAwesomeIcon icon={faCode} />
        </ToolbarButton>
        <ToolbarButton
          label="Blockquote"
          active={editor.isActive('blockquote')}
          onClick={() => editor.chain().focus().toggleBlockquote().run()}
        >
          <FontAwesomeIcon icon={faQuoteRight} />
        </ToolbarButton>
        <ToolbarButton
          label="Horizontal rule"
          onClick={() => editor.chain().focus().setHorizontalRule().run()}
        >
          <FontAwesomeIcon icon={faMinus} />
        </ToolbarButton>
        <ToolbarButton
          label="Bullet list"
          active={editor.isActive('bulletList')}
          onClick={() => editor.chain().focus().toggleBulletList().run()}
        >
          <FontAwesomeIcon icon={faListUl} />
        </ToolbarButton>
        <ToolbarButton
          label="Ordered list"
          active={editor.isActive('orderedList')}
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
        >
          <FontAwesomeIcon icon={faListOl} />
        </ToolbarButton>
        <ToolbarButton
          label="Align left"
          active={editor.isActive({ textAlign: 'left' })}
          onClick={() => editor.chain().focus().setTextAlign('left').run()}
        >
          <FontAwesomeIcon icon={faAlignLeft} />
        </ToolbarButton>
        <ToolbarButton
          label="Align center"
          active={editor.isActive({ textAlign: 'center' })}
          onClick={() => editor.chain().focus().setTextAlign('center').run()}
        >
          <FontAwesomeIcon icon={faAlignCenter} />
        </ToolbarButton>
        <ToolbarButton
          label="Align right"
          active={editor.isActive({ textAlign: 'right' })}
          onClick={() => editor.chain().focus().setTextAlign('right').run()}
        >
          <FontAwesomeIcon icon={faAlignRight} />
        </ToolbarButton>
        <ToolbarButton
          label="Justify"
          active={editor.isActive({ textAlign: 'justify' })}
          onClick={() => editor.chain().focus().setTextAlign('justify').run()}
        >
          <FontAwesomeIcon icon={faAlignJustify} />
        </ToolbarButton>
        <label className="d-inline-flex align-items-center gap-1 btn btn-sm btn-light rounded-pill px-3 py-1 mb-0">
          <span className="fw-semibold small">Color</span>
          <input
            type="color"
            className="form-control form-control-color border-0 p-0"
            style={{ width: '1.75rem', height: '1.75rem' }}
            aria-label="Text color"
            value={
              typeof editor.getAttributes('textStyle').color === 'string'
                ? editor.getAttributes('textStyle').color
                : '#00686D'
            }
            list="page-content-text-colors"
            onChange={(e) => editor.chain().focus().setColor(e.target.value).run()}
          />
          <datalist id="page-content-text-colors">
            {PAGE_CONTENT_TEXT_COLORS.map((color) => (
              <option key={color} value={color} />
            ))}
          </datalist>
        </label>
        <ToolbarButton
          label="Insert table"
          onClick={() =>
            editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
          }
        >
          <FontAwesomeIcon icon={faTable} />
        </ToolbarButton>
        <ToolbarButton
          label="Link"
          active={editor.isActive('link') || linkOpen}
          onClick={() => {
            if (editor.isActive('link')) {
              const attrs = editor.getAttributes('link');
              setLinkUrl(typeof attrs.href === 'string' ? attrs.href : '');
            }
            setLinkOpen((open) => !open);
          }}
        >
          <FontAwesomeIcon icon={faLink} />
        </ToolbarButton>
        <ToolbarButton
          label="Insert Logo"
          onClick={() => {
            editor.chain().focus().insertContent(PAGE_CONTENT_LOGO_HTML).run();
          }}
        >
          <FontAwesomeIcon icon={faImage} />
          <span className="ms-1 fw-semibold d-none d-md-inline">Logo</span>
        </ToolbarButton>
        <ToolbarButton
          label="Insert Image"
          disabled={uploadingImage}
          onClick={() => imageInputRef.current?.click()}
        >
          <FontAwesomeIcon icon={faUpload} spin={uploadingImage} />
          <span className="ms-1 fw-semibold d-none d-md-inline">
            {uploadingImage ? 'Uploading…' : 'Image'}
          </span>
        </ToolbarButton>
        <input
          ref={imageInputRef}
          type="file"
          accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
          className="d-none"
          data-testid={`page-content-image-input-${locale}`}
          onChange={handleImageFileSelected}
        />

        {linkOpen && (
          <div className="d-flex flex-wrap align-items-center gap-2 w-100 mt-1">
            <input
              type="url"
              className="form-control form-control-sm flex-grow-1"
              placeholder="https://"
              value={linkUrl}
              dir="ltr"
              onChange={(e) => setLinkUrl(e.target.value)}
              onKeyDown={(e) => {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  applyLink();
                }
              }}
            />
            <button type="button" className="btn btn-sm btn-primary rounded-pill px-3" onClick={applyLink}>
              {t('save')}
            </button>
            <button
              type="button"
              className="btn btn-sm btn-light rounded-pill px-3"
              onClick={() => {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                setLinkOpen(false);
                setLinkUrl('');
              }}
            >
              {t('remove')}
            </button>
          </div>
        )}

        {imageUploadError && (
          <div
            className="alert alert-danger py-2 px-3 mb-0 w-100"
            role="alert"
            data-testid={`page-content-image-upload-error-${locale}`}
          >
            {imageUploadError}
          </div>
        )}
      </div>

      <EditorContent editor={editor} className="page-content-editor__content" />
    </div>
  );
}
