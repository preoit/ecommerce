import { Link } from '@inertiajs/react';
import { ChevronRight, House, Tag } from 'lucide-react';

export default function ListingHero({ title, description, brandNames = [], parent, children }) {
    return <header>
        <nav aria-label="Breadcrumb" className="mb-5 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <Link href="/" aria-label="Home" className="inline-flex items-center rounded p-1 hover:text-violet-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-500"><House aria-hidden="true" className="size-4" /></Link><ChevronRight className="size-3 text-slate-300" />
            {parent && <><Link href={parent.href} className="hover:text-violet-600">{parent.name}</Link><ChevronRight className="size-3 text-slate-300" /></>}
            <span aria-current="page" className="font-medium text-slate-900 dark:text-slate-100">{title}</span>
        </nav>
        <div className="relative isolate overflow-hidden rounded-3xl px-6 py-8 shadow-lg sm:px-10 sm:py-10" style={{ background: 'linear-gradient(115deg, #2e1065, #5b21b6 55%, #7c3aed)', color: '#fff' }}>
            <div aria-hidden="true" className="pointer-events-none absolute inset-0 -z-10 opacity-20" style={{ backgroundImage: 'radial-gradient(rgba(255,255,255,.55) 1px, transparent 1px)', backgroundSize: '22px 22px' }} />
            <h1 className="break-words text-3xl font-bold leading-tight sm:text-4xl" style={{ color: '#fff' }}>{title}</h1>
            {description && <p className="mt-3 max-w-3xl text-sm leading-7 sm:text-base" style={{ color: '#ede9fe' }}>{description}</p>}
            {brandNames.length > 0 && <div className="mt-5 flex flex-wrap gap-2">{brandNames.map(name => <span key={name} className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-4 py-2 text-sm font-semibold"><Tag className="size-4 shrink-0" />{name}</span>)}</div>}
        </div>
        {children && <div className="mt-4 flex flex-wrap items-center gap-2">{children}</div>}
    </header>;
}
