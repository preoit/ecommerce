import { Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { cn } from '@/app/utils/cn';

const variants = {
    primary: 'border-transparent bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800 focus-visible:ring-brand-500',
    secondary: 'border-slate-300 bg-white text-slate-800 hover:bg-slate-50 active:bg-slate-100 focus-visible:ring-brand-500',
    ghost: 'border-transparent bg-transparent text-slate-700 hover:bg-slate-100 active:bg-slate-200 focus-visible:ring-brand-500',
    danger: 'border-transparent bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus-visible:ring-red-500',
};

const sizes = {
    sm: 'h-8 gap-1.5 px-3 text-xs',
    md: 'h-10 gap-2 px-4 text-sm',
    lg: 'h-12 gap-2 px-5 text-base',
};

export default function Button({
    as = 'button',
    href,
    icon: Icon,
    loading = false,
    variant = 'primary',
    size = 'md',
    className,
    children,
    disabled,
    ...props
}) {
    const classes = cn(
        'inline-flex shrink-0 items-center justify-center rounded-md border font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
        variants[variant],
        sizes[size],
        className,
    );
    const content = (
        <>
            {loading ? <LoaderCircle className="size-4 animate-spin" aria-hidden="true" /> : Icon ? <Icon className="size-4" aria-hidden="true" /> : null}
            <span>{children}</span>
        </>
    );

    if (href) {
        return <Link href={href} className={classes} {...props}>{content}</Link>;
    }

    const Component = as;
    return <Component className={classes} disabled={disabled || loading} {...props}>{content}</Component>;
}
