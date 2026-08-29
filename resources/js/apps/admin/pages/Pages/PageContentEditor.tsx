import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
  faBold,
  faItalic,
  faLink,
  faListOl,
  faListUl,
} from '@fortawesome/free-solid-svg-icons';
import { EditorContent, useEditor } from '@tiptap/react';
import clsx from 'clsx';
import { useEffect, useState, type ChangeEvent, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
  getPageContentEditorExtensions,
  isPageContentRtlLocale,
  normalizeEditorHtml,
  PAGE_CONTENT_HEADING_LEVELS,
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
  children,
}: {
  active?: boolean;
  label: string;
  onClick: () => void;
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
      </div>

      <EditorContent editor={editor} className="page-content-editor__content" />
    </div>
  );
}
