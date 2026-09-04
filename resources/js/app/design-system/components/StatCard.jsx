export default function StatCard({ icon: Icon, label, value, change }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <p className="text-sm font-medium text-slate-600">{label}</p>
                    <p className="mt-2 text-2xl font-bold text-slate-950">{value}</p>
                    <p className="mt-2 truncate text-xs text-slate-500">{change}</p>
                </div>
                <span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                    <Icon className="size-5" aria-hidden="true" />
                </span>
            </div>
        </article>
    );
}
