import StorefrontLayout from '@/app/layouts/StorefrontLayout';

export default function CheckoutLayout({ children }) {
    return <StorefrontLayout><main className="mx-auto max-w-[1400px] px-4 py-8 sm:px-6 sm:py-10 lg:px-8">{children}</main></StorefrontLayout>;
}
