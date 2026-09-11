import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Compass, Home, Search, ShieldCheck } from 'lucide-react';

export default function ErrorPage({ status = 404 }) {
    const { auth, website } = usePage().props;
    const user = auth?.user;
    const destination = user ? (user.is_admin ? '/dashboard' : '/account') : '/';
    const destinationLabel = user ? (user.is_admin ? 'Admin dashboard' : 'My account') : 'Back to home';
    const websiteName = website?.name || 'ITTIBA';

    return <main className="relative grid min-h-screen place-items-center overflow-hidden bg-gradient-to-br from-violet-50 via-white to-blue-50 px-4 py-10 text-slate-900">
        <Head title="Page not found" />
        <div className="absolute -left-24 top-16 size-80 rounded-full bg-violet-200/40 blur-3xl" />
        <div className="absolute -right-24 bottom-10 size-80 rounded-full bg-sky-200/40 blur-3xl" />
        <section className="relative z-10 w-full max-w-3xl overflow-hidden rounded-[2rem] border border-white bg-white/95 shadow-[0_28px_80px_rgba(76,29,149,.14)]">
            <div className="grid md:grid-cols-[.8fr_1.2fr]">
                <div className="relative grid min-h-64 place-items-center overflow-hidden bg-gradient-to-br from-violet-700 to-indigo-600 p-8 text-white md:min-h-[470px]">
                    <div className="absolute -right-12 -top-12 size-48 rounded-full border-[30px] border-white/10" />
                    <div className="absolute -bottom-16 -left-12 size-56 rounded-full bg-white/10" />
                    <div className="relative text-center"><span className="mx-auto grid size-20 place-items-center rounded-3xl bg-white/15 ring-1 ring-white/25"><Compass className="size-10" /></span><p className="mt-7 text-7xl font-black tracking-tighter">{status}</p><p className="mt-2 text-sm font-bold uppercase tracking-[.22em] text-violet-200">Lost in the store</p></div>
                </div>
                <div className="flex flex-col justify-center p-7 sm:p-10 md:p-12">
                    <Link href="/" className="mb-8 flex w-fit items-center">{website?.logo ? <img src={website.logo} alt={`${websiteName} logo`} className="h-12 w-28 object-contain" /> : <b className="text-xl">{websiteName}</b>}</Link>
                    <span className="flex size-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-700"><Search className="size-5" /></span>
                    <h1 className="mt-5 text-3xl font-black tracking-tight sm:text-4xl">Page not found</h1>
                    <p className="mt-3 max-w-md text-sm leading-6 text-slate-500">The page may have been moved, removed, or the address might be incorrect. You can safely return and continue browsing.</p>
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row"><Link href={destination} className="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 text-sm font-bold text-white shadow-lg shadow-violet-200 transition hover:bg-violet-700"><Home className="size-4" />{destinationLabel}</Link><button type="button" onClick={()=>window.history.length > 1 ? window.history.back() : window.location.assign('/')} className="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"><ArrowLeft className="size-4" />Go back</button></div>
                    <p className="mt-8 flex items-center gap-2 border-t border-slate-100 pt-5 text-xs text-slate-400"><ShieldCheck className="size-4 text-emerald-500" />Your account and shopping data are safe.</p>
                </div>
            </div>
        </section>
    </main>;
}
