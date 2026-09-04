export default function PageHeader({ eyebrow, title, description, actions }) {
    return (
        <div className="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                {eyebrow && <p className="text-xs font-bold uppercase text-brand-700">{eyebrow}</p>}
                <h1 className="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">{title}</h1>
                {description && <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
        </div>
    );
}
