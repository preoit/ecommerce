import StorefrontLayout from '@/app/layouts/StorefrontLayout';

export default function CheckoutLayout({ children }) {
    return <StorefrontLayout><main className="mx-auto max-w-[1180px] px-4 py-10 sm:px-6 lg:px-8">{children}</main></StorefrontLayout>;
}
