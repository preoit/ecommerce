export const panel='rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900';
export const button='rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50';
export function Field({label,children}) { return <label className="block min-w-0 text-sm capitalize"><span className="mb-1.5 block text-slate-500">{label}</span><span className="[&>input]:w-full [&>select]:w-full [&>input]:rounded-lg [&>select]:rounded-lg">{children}</span></label>; }
export async function api(url,data) {
    const token=document.cookie.split('; ').find(v=>v.startsWith('XSRF-TOKEN='))?.split('=').slice(1).join('=');
    const response=await fetch(url,{method:data===undefined?'GET':'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-XSRF-TOKEN':decodeURIComponent(token||'')},...(data===undefined?{}:{body:JSON.stringify(data)})});
    const body=await response.json(); if(!response.ok)throw new Error(body.errors?Object.values(body.errors).flat().join(' '):body.message||'Request failed.'); return body;
}
