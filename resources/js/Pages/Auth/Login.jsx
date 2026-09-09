import InputError from '@/Components/InputError';
import AuthLayout from '@/app/layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

const inputClass = 'mt-1 h-10 w-full rounded-md border-slate-300 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#6c5ce7] focus:ring-[#6c5ce7]';

const FacebookIcon = () => <svg viewBox="0 0 24 24" className="size-4 fill-current" aria-hidden="true"><path d="M14 8.5V6.8c0-.8.5-1 1-1h2.8V2.1L15.2 2C11.8 2 10 4 10 6.4v2.1H7v4h3V22h4v-9.5h3.3l.6-4H14Z" /></svg>;
const GmailIcon = () => <svg viewBox="0 0 24 24" className="size-[18px]" aria-hidden="true"><path fill="#4285F4" d="M3 5.2 7 8.3V19H4a2 2 0 0 1-2-2V6.5c0-.6.4-1.1 1-1.3Z" /><path fill="#34A853" d="M17 8.3 21 5.2c.6.2 1 .7 1 1.3V17a2 2 0 0 1-2 2h-3V8.3Z" /><path fill="#EA4335" d="M3 5.2C3.5 4.8 4.2 4.9 4.7 5.3L12 10.8l7.3-5.5c.5-.4 1.2-.5 1.7-.1L12 12 3 5.2Z" /><path fill="#FBBC04" d="M17 8.3 21 5.2V19h-4V8.3Z" /></svg>;
const LinkedinIcon = () => <svg viewBox="0 0 24 24" className="size-4 fill-current" aria-hidden="true"><path d="M5.2 7.4A2.2 2.2 0 1 0 5.2 3a2.2 2.2 0 0 0 0 4.4ZM3.3 21h3.8V9H3.3v12ZM9.4 9H13v1.6h.1c.5-.9 1.7-2 3.6-2 3.9 0 4.6 2.5 4.6 5.9V21h-3.8v-5.8c0-1.4 0-3.2-2-3.2s-2.3 1.5-2.3 3.1V21H9.4V9Z" /></svg>;
const TwitterIcon = () => <svg viewBox="0 0 24 24" className="size-4 fill-current" aria-hidden="true"><path d="M21.5 6.1c-.7.3-1.5.5-2.3.6a4 4 0 0 0 1.8-2.2c-.8.5-1.7.8-2.6 1a4 4 0 0 0-6.9 3.7A11.4 11.4 0 0 1 3.2 5c-1.3 2.3-.1 5.2 2 6a4 4 0 0 1-1.8-.5c0 2 1.4 3.7 3.3 4.1-.6.2-1.2.2-1.8.1.5 1.7 2.1 2.9 3.9 2.9a8.1 8.1 0 0 1-5 1.7c-.3 0-.6 0-1-.1A11.4 11.4 0 0 0 20.3 9.1v-.5c.8-.6 1.4-1.4 2-2.3-.7.3-1.5.5-2.3.6.8-.5 1.4-1.2 1.7-2.1" /></svg>;

export default function Login({ status, canResetPassword, portal = 'customer', loginRoute = 'customer.login.store', registerRoute = 'customer.register' }) {
    const [visible, setVisible] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ email: '', password: '', remember: false });
    const submit = (event) => { event.preventDefault(); post(route(loginRoute), { onFinish: () => reset('password') }); };

    return <AuthLayout><Head title="Log in" />
        <h1 className="text-2xl font-medium text-[#36354a]">{portal === 'admin' ? 'Admin sign in' : 'Customer sign in'}</h1><p className="mt-1 text-sm text-slate-500">{portal === 'admin' ? 'Sign in to manage the ecommerce dashboard.' : 'Sign in to track orders and manage your account.'}</p>
        {status && <div className="mt-5 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{status}</div>}
        <form className="mt-7" onSubmit={submit}>
            <label htmlFor="email" className="text-sm font-medium text-[#36354a]">Email or Phone Number</label><input id="email" type="text" name="email" value={data.email} autoComplete="username" autoFocus onChange={(event) => setData('email', event.target.value)} className={inputClass} placeholder="Enter your email or phone number" /><InputError className="mt-1" message={errors.email} />
            <div className="mt-5"><label htmlFor="password" className="text-sm font-medium text-[#36354a]">Password</label><div className="relative"><input id="password" type={visible ? 'text' : 'password'} name="password" value={data.password} autoComplete="current-password" onChange={(event) => setData('password', event.target.value)} className={`${inputClass} pr-10`} style={{ fontSize: '18px' }} placeholder="••••••••••••" /><button type="button" onClick={() => setVisible((value) => !value)} className="absolute right-2 top-3 text-slate-500" aria-label="Show or hide password">{visible ? <EyeOff className="size-5" /> : <Eye className="size-5" />}</button></div><InputError className="mt-1" message={errors.password} /></div>
            <div className="mt-6 flex items-center justify-between"><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={data.remember} onChange={(event) => setData('remember', event.target.checked)} className="size-4 rounded border-slate-300 text-[#6c5ce7] focus:ring-[#6c5ce7]" /> Remember Me</label>{canResetPassword && <Link href={route('password.request')} className="text-sm font-medium text-[#6c5ce7] hover:text-violet-700">Forgot Password?</Link>}</div>
            <button disabled={processing} className="mt-7 h-10 w-full rounded-md bg-[#6c5ce7] text-sm font-semibold text-white shadow-md shadow-violet-200 transition hover:bg-[#5d4ed6] disabled:opacity-60">{processing ? 'Logging in…' : 'Login'}</button>
        </form>
        {registerRoute && <p className="mt-6 text-center text-sm text-slate-600">New customer? <Link href={route(registerRoute)} className="font-medium text-[#6c5ce7]">Create an account</Link></p>}
        <div className="my-7 flex items-center gap-4 text-sm text-slate-500"><span className="h-px flex-1 bg-slate-200" />or<span className="h-px flex-1 bg-slate-200" /></div><div className="flex justify-center gap-3"><button type="button" className="grid size-9 place-items-center rounded-full border border-blue-100 bg-blue-50 text-[#1877f2] transition hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-100" aria-label="Continue with Facebook"><FacebookIcon /></button><button type="button" className="grid size-9 place-items-center rounded-full border border-red-100 bg-red-50 transition hover:-translate-y-0.5 hover:border-red-200 hover:bg-red-100" aria-label="Continue with Gmail"><GmailIcon /></button><button type="button" className="grid size-9 place-items-center rounded-full border border-sky-100 bg-sky-50 text-[#0a66c2] transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-100" aria-label="Continue with LinkedIn"><LinkedinIcon /></button><button type="button" className="grid size-9 place-items-center rounded-full border border-sky-100 bg-sky-50 text-[#1da1f2] transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-sky-100" aria-label="Continue with Twitter"><TwitterIcon /></button></div>
    </AuthLayout>;
}
