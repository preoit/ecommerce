import { Link, usePage } from '@inertiajs/react';
import { Heart, PackageCheck, ShieldCheck, ShoppingBag } from 'lucide-react';

export default function AuthLayout({ children, portal = 'customer' }) {
    const { website } = usePage().props;
    const websiteName = website?.name || 'Commerce';
    const websiteLogo = website?.logo || null;
    const admin = portal === 'admin';

    const brand = <Link key={`${websiteName}-${websiteLogo}`} href="/" className="flex w-fit items-center text-xl font-bold text-slate-950">
        {websiteLogo
            ? <img src={websiteLogo} alt={`${websiteName} logo`} className="h-14 w-[116px] object-contain" />
            : <span>{websiteName}</span>}
    </Link>;

    if (admin) return <main className="relative grid min-h-screen place-items-center overflow-hidden bg-slate-950 px-4 py-10 text-slate-700">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(124,58,237,.22),transparent_34%),radial-gradient(circle_at_bottom_left,rgba(59,130,246,.14),transparent_30%)]" />
        <section className="relative z-10 w-full max-w-[430px] overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/30">
            <div className="flex items-center justify-between border-b border-slate-100 px-8 py-5">{brand}<span className="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-white"><ShieldCheck className="size-3.5"/>Admin portal</span></div>
            <div className="px-8 py-9 sm:px-10">{children}</div>
            <p className="border-t border-slate-100 bg-slate-50 px-8 py-4 text-center text-xs text-slate-400">Authorised staff access only</p>
        </section>
    </main>;

    return (
        <main className="relative grid min-h-screen place-items-center overflow-hidden bg-gradient-to-br from-violet-50 via-white to-blue-50 px-4 py-8 text-[#4a495a]">
            <div className="absolute -left-24 top-12 size-72 rounded-full bg-violet-200/30 blur-3xl" />
            <div className="absolute -right-24 bottom-12 size-80 rounded-full bg-blue-200/30 blur-3xl" />
            <section className="relative z-10 grid w-full max-w-[920px] overflow-hidden rounded-3xl border border-white bg-white shadow-[0_24px_70px_rgba(76,29,149,.14)] lg:grid-cols-[.9fr_1.1fr]">
                <aside className="relative hidden overflow-hidden bg-gradient-to-br from-violet-700 via-violet-600 to-indigo-600 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                    <div className="absolute -right-20 -top-20 size-56 rounded-full border-[36px] border-white/10" />
                    <div className="relative"><span className="grid size-12 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/20"><ShoppingBag className="size-6"/></span><h2 className="mt-7 text-3xl font-black leading-tight">Your shopping,<br/>all in one place.</h2><p className="mt-4 max-w-xs text-sm leading-6 text-violet-100">Track orders, save delivery addresses and manage your account easily.</p></div>
                    <div className="relative space-y-3 text-sm font-semibold"><p className="flex items-center gap-3"><PackageCheck className="size-5 text-violet-200"/>Track every order</p><p className="flex items-center gap-3"><Heart className="size-5 text-violet-200"/>Keep your wishlist saved</p><p className="flex items-center gap-3"><ShieldCheck className="size-5 text-violet-200"/>Secure customer account</p></div>
                </aside>
                <div className="px-7 py-8 sm:px-12 sm:py-10"><div className="mb-7 flex items-center justify-between">{brand}<span className="rounded-full bg-violet-50 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-violet-700">Customer account</span></div>{children}</div>
            </section>
        </main>
    );
}
