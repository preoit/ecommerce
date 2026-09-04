import { Link, usePage } from '@inertiajs/react';

export default function AuthLayout({ children }) {
    const { website } = usePage().props;
    const websiteName = website?.name || 'Commerce';
    const websiteLogo = website?.logo || null;

    return (
        <main className="relative grid min-h-screen place-items-center overflow-hidden bg-[#f8f7fa] px-4 py-8 text-[#4a495a]">
            <div className="absolute left-[calc(50%-275px)] top-[10%] size-36 rounded-2xl border border-violet-200 bg-violet-50/60" />
            <div className="absolute left-[calc(50%-360px)] top-[14%] h-52 w-48 rounded-lg bg-violet-100/70" />
            <div className="absolute bottom-[8%] left-[calc(50%+110px)] h-44 w-40 rounded-2xl border-2 border-dashed border-violet-100" />
            <div className="absolute bottom-[10%] left-[calc(50%+130px)] h-36 w-28 rounded-xl bg-violet-100/60" />
            <section className="relative z-10 w-full max-w-[460px] rounded-lg bg-white px-8 py-12 shadow-[0_7px_20px_rgba(44,32,66,.14)] sm:px-12">
                <Link key={`${websiteName}-${websiteLogo}`} href="/" className="mx-auto mb-7 flex w-fit items-center text-xl font-bold text-[#2e2d42]">
                    {websiteLogo
                        ? <img src={websiteLogo} alt={`${websiteName} logo`} className="h-16 w-[120px] object-contain" />
                        : <span>{websiteName}</span>}
                </Link>
                {children}
            </section>
        </main>
    );
}
