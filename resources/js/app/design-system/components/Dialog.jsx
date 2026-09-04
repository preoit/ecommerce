import { X } from 'lucide-react';
import Modal from '@/Components/Modal';
import IconButton from './IconButton';

export default function Dialog({ open, onClose, title, description, children, maxWidth = '2xl' }) {
    return (
        <Modal show={open} onClose={onClose} maxWidth={maxWidth} closeable>
            <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div className="min-w-0">
                    <h2 className="text-lg font-bold text-slate-950">{title}</h2>
                    {description && <p className="mt-1 text-sm leading-5 text-slate-600">{description}</p>}
                </div>
                <IconButton icon={X} label="Close dialog" onClick={onClose} className="-mr-2 -mt-2" />
            </div>
            {children}
        </Modal>
    );
}
