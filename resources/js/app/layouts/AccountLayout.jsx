import StorefrontLayout from './StorefrontLayout';

export default function AccountLayout({ children }) {
    return <StorefrontLayout>
        <section className="min-h-[calc(100vh-130px)] bg-slate-50/80 py-6 sm:py-8 lg:py-10">
            <div className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
                {children}
            </div>
        </section>
    </StorefrontLayout>;
}