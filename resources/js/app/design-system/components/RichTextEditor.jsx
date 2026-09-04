import { useEffect, useState } from 'react';
import { EditorContent, useEditor } from '@tiptap/react';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo2,
    Strikethrough,
    Undo2,
    Unlink,
} from 'lucide-react';

function ToolbarButton({ label, icon: Icon, active = false, disabled = false, onClick }) {
    return (
        <button
            type="button"
            aria-label={label}
            title={label}
            aria-pressed={active}
            disabled={disabled}
            onClick={onClick}
            className={`grid size-8 shrink-0 place-items-center rounded-md transition-colors disabled:cursor-not-allowed disabled:opacity-40 ${
                active ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }`}
        >
            <Icon className="size-4" aria-hidden="true" />
        </button>
    );
}

export default function RichTextEditor({ id, value = '', onChange, placeholder = 'Write a description...' }) {
    const [, setSelectionVersion] = useState(0);
    const [linkDialog, setLinkDialog] = useState(false);
    const [linkUrl, setLinkUrl] = useState('');
    const [linkFollow, setLinkFollow] = useState('dofollow');
    const [linkNewTab, setLinkNewTab] = useState(false);
    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({ link: false }),
            Link.configure({ openOnClick: false, autolink: true, defaultProtocol: 'https' }),
            Placeholder.configure({ placeholder }),
        ],
        content: value,
        editorProps: {
            attributes: {
                id,
                class: 'rich-text-content min-h-36 px-3 py-3 text-sm text-slate-800 focus:outline-none',
            },
        },
        onUpdate: ({ editor: currentEditor }) => {
            onChange(currentEditor.isEmpty ? '' : currentEditor.getHTML());
        },
        onSelectionUpdate: () => setSelectionVersion((version) => version + 1),
    });

    useEffect(() => {
        if (!editor || editor.getHTML() === value) return;
        editor.commands.setContent(value || '', { emitUpdate: false });
    }, [editor, value]);

    if (!editor) return null;

    const activeHeading = [1, 2, 3, 4, 5, 6].find((level) => editor.isActive('heading', { level }));

    const changeHeading = (event) => {
        const level = Number(event.target.value);

        if (level === 0) {
            editor.chain().focus().setParagraph().run();
            return;
        }

        editor.chain().focus().setHeading({ level }).run();
    };

    const editLink = () => {
        const currentUrl = editor.getAttributes('link').href || '';
        setLinkUrl(currentUrl);
        setLinkFollow(editor.getAttributes('link').rel?.includes('nofollow') ? 'nofollow' : 'dofollow');
        setLinkNewTab(editor.getAttributes('link').target === '_blank');
        setLinkDialog(true);
    };

    const saveLink = () => {
        if (linkUrl.trim() === '') editor.chain().focus().extendMarkRange('link').unsetLink().run();
        else editor.chain().focus().extendMarkRange('link').setLink({ href: linkUrl.trim(), rel: linkFollow === 'nofollow' ? 'nofollow' : null, target: linkNewTab ? '_blank' : null }).run();
        setLinkDialog(false);
    };

    return (
        <><div className="overflow-hidden rounded-md border border-slate-300 bg-white shadow-sm focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500">
            <div className="flex min-h-11 flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
                <ToolbarButton label="Bold" icon={Bold} active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()} />
                <ToolbarButton label="Italic" icon={Italic} active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()} />
                <ToolbarButton label="Strikethrough" icon={Strikethrough} active={editor.isActive('strike')} onClick={() => editor.chain().focus().toggleStrike().run()} />
                <select
                    aria-label="Text style"
                    title="Text style"
                    value={activeHeading || 0}
                    onChange={changeHeading}
                    className="h-8 rounded-md border-slate-300 bg-white py-0 pl-2 pr-7 text-xs font-medium text-slate-700 focus:border-brand-500 focus:ring-brand-500"
                >
                    <option value="0">Paragraph</option>
                    <option value="1">Title (Heading 1)</option>
                    <option value="2">Heading (Heading 2)</option>
                    <option value="3">Subheading (Heading 3)</option>
                    <option value="4">Heading 4</option>
                    <option value="5">Heading 5</option>
                    <option value="6">Heading 6</option>
                </select>
                <span className="mx-1 h-6 w-px bg-slate-200" aria-hidden="true" />
                <ToolbarButton label="Bullet list" icon={List} active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()} />
                <ToolbarButton label="Numbered list" icon={ListOrdered} active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()} />
                <ToolbarButton label="Quote" icon={Quote} active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()} />
                <ToolbarButton label="Add or edit link" icon={LinkIcon} active={editor.isActive('link')} onClick={editLink} />
                <ToolbarButton label="Remove link" icon={Unlink} disabled={!editor.isActive('link')} onClick={() => editor.chain().focus().unsetLink().run()} />
                <span className="mx-1 h-6 w-px bg-slate-200" aria-hidden="true" />
                <ToolbarButton label="Undo" icon={Undo2} disabled={!editor.can().chain().focus().undo().run()} onClick={() => editor.chain().focus().undo().run()} />
                <ToolbarButton label="Redo" icon={Redo2} disabled={!editor.can().chain().focus().redo().run()} onClick={() => editor.chain().focus().redo().run()} />
            </div>
            <EditorContent editor={editor} />
        </div>{linkDialog && <div className="fixed inset-0 z-[100] grid place-items-center bg-slate-950/55 p-4"><div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><div className="flex items-center gap-3"><span className="grid size-10 place-items-center rounded-xl bg-violet-100 text-violet-600"><LinkIcon className="size-5" /></span><div><h3 className="text-lg font-bold text-slate-900">Add or edit link</h3><p className="text-sm text-slate-500">Link the selected text to a page, product, or external website.</p></div></div><label className="mt-6 block text-sm font-semibold text-slate-700">Destination URL<input autoFocus value={linkUrl} onChange={(event) => setLinkUrl(event.target.value)} onKeyDown={(event) => { if (event.key === 'Enter') saveLink(); }} placeholder="https://example.com" className="mt-2 h-11 w-full rounded-lg border-slate-300 px-3 text-sm focus:border-violet-500 focus:ring-violet-500" /></label><p className="mt-2 text-xs text-slate-500">Use a full URL, for example: https://example.com/page</p><div className="mt-5 grid gap-4 sm:grid-cols-2"><label className="text-sm font-semibold text-slate-700">Link type<select value={linkFollow} onChange={(event) => setLinkFollow(event.target.value)} className="mt-2 h-10 w-full rounded-lg border-slate-300 text-sm"><option value="dofollow">Dofollow</option><option value="nofollow">Nofollow</option></select></label><label className="mt-7 flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" checked={linkNewTab} onChange={(event) => setLinkNewTab(event.target.checked)} className="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />Open in new tab</label></div><p className="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">Use nofollow for sponsored, affiliate, or untrusted links. External links can be opened in a new tab.</p><div className="mt-6 flex justify-end gap-3"><button type="button" onClick={() => setLinkDialog(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancel</button><button type="button" onClick={saveLink} className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white">Save link</button></div></div></div>}</>
    );
}
