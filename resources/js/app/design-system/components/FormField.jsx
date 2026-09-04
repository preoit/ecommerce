export default function FormField({ label, htmlFor, required = false, hint, error, children }) {
    return (
        <div>
            <label htmlFor={htmlFor} className="block text-sm font-semibold text-slate-800">
                {label}{required && <span className="ml-1 text-red-600" aria-hidden="true">*</span>}
            </label>
            {hint && <p className="mt-1 text-xs leading-5 text-slate-500">{hint}</p>}
            <div className="mt-2">{children}</div>
            {error && <p className="mt-1.5 text-sm font-medium text-red-600" role="alert">{error}</p>}
        </div>
    );
}
