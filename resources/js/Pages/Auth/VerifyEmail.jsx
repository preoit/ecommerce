import PrimaryButton from '@/Components/PrimaryButton';
import AuthLayout from '@/app/layouts/AuthLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { auth, errors } = usePage().props;
    const hasEmail = Boolean(auth?.user?.email);
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout>
            <Head title="Email Verification" />

            <div className="mb-4 text-sm text-gray-600">
                {hasEmail
                    ? "Verify your email address using the link we sent you. If you didn't receive it, you can request another below."
                    : 'Add an email address to your profile before requesting verification.'}
            </div>

            {errors?.email_verification && (
                <div className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700">
                    {errors.email_verification}
                </div>
            )}

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    {hasEmail ? (
                        <PrimaryButton disabled={processing}>
                            Resend Verification Email
                        </PrimaryButton>
                    ) : (
                        <Link
                            href={route(auth.user.is_admin ? 'admin.profile.edit' : 'account.profile.edit')}
                            className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500"
                        >
                            Add Email Address
                        </Link>
                    )}

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Log Out
                    </Link>
                </div>
            </form>
        </AuthLayout>
    );
}
