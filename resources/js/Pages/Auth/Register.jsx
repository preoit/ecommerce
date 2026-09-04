import InputError from '@/Components/InputError';
import AuthLayout from '@/app/layouts/AuthLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

const inputClass = 'mt-1 h-10 w-full rounded-md border-slate-300 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#6c5ce7] focus:ring-[#6c5ce7]';

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', phone: '', password: '' });
    const submit = (event) => { event.preventDefault(); post(route('register'), { onFinish: () => reset('password') }); };
    const passwordInput = (id, label, visible, setVisible, value, onChange, error) => <div className="mt-4"><label htmlFor={id} className="text-sm font-medium text-[#36354a]">{label}</label><div className="relative"><input id={id} type={visible ? 'text' : 'password'} value={value} autoComplete="new-password" onChange={onChange} required className={`${inputClass} pr-10`} style={{ fontSize: '18px' }} placeholder="••••••••••••" /><button type="button" onClick={() => setVisible((current) => !current)} className="absolute right-2 top-3 text-slate-500" aria-label="Show or hide password">{visible ? <EyeOff className="size-5" /> : <Eye className="size-5" />}</button></div><InputError className="mt-1" message={error} /></div>;

    return <AuthLayout><Head title="Create account" />
        <h1 className="text-2xl font-medium text-[#36354a]">Adventure starts here 🚀</h1><p className="mt-1 text-sm text-slate-500">Make your store management easy and fun!</p>
        <form className="mt-6" onSubmit={submit}>
            <label htmlFor="name" className="text-sm font-medium text-[#36354a]">Full Name</label><input id="name" name="name" value={data.name} autoComplete="name" autoFocus required onChange={(event) => setData('name', event.target.value)} className={inputClass} placeholder="Enter your full name" /><InputError className="mt-1" message={errors.name} />
            <div className="mt-4"><label htmlFor="phone" className="text-sm font-medium text-[#36354a]">Phone Number</label><input id="phone" type="tel" name="phone" value={data.phone} autoComplete="tel" inputMode="tel" required onChange={(event) => setData('phone', event.target.value)} className={inputClass} placeholder="Enter your phone number" /><InputError className="mt-1" message={errors.phone} /></div>
            {passwordInput('password', 'Password', showPassword, setShowPassword, data.password, (event) => setData('password', event.target.value), errors.password)}
            <label className="mt-5 flex items-start gap-2 text-sm text-slate-600"><input type="checkbox" required className="mt-0.5 size-4 rounded border-slate-300 text-[#6c5ce7] focus:ring-[#6c5ce7]" />I agree to <a href="#terms" className="font-medium text-[#6c5ce7]">privacy policy & terms</a></label>
            <button disabled={processing} className="mt-6 h-10 w-full rounded-md bg-[#6c5ce7] text-sm font-semibold text-white shadow-md shadow-violet-200 transition hover:bg-[#5d4ed6] disabled:opacity-60">{processing ? 'Creating account…' : 'Sign up'}</button>
        </form>
        <p className="mt-6 text-center text-sm text-slate-600">Already have an account? <Link href={route('login')} className="font-medium text-[#6c5ce7]">Sign in instead</Link></p>
    </AuthLayout>;
}
