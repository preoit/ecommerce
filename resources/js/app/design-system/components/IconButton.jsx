import { cn } from '@/app/utils/cn';

export default function IconButton({ icon: Icon, label, className, iconClassName, ...props }) {
    return (
        <button
            type="button"
            className={cn('inline-flex size-10 shrink-0 items-center justify-center rounded-md text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 active:bg-slate-200', className)}
            aria-label={label}
            title={label}
            {...props}
        >
            <Icon className={cn('size-5', iconClassName)} aria-hidden="true" />
        </button>
    );
}
