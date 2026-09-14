import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import {api,panel} from './ui';
export default function CourierSummary() {
    const [data,setData]=useState(null);
    useEffect(()=>{let active=true;api(route('couriers.index')).then(d=>active&&setData(d)).catch(()=>{});return()=>{active=false;};},[]);
    return <section className={panel}><div className="mb-4 flex justify-between"><h2>Courier Overview</h2><Link className="text-sm text-violet-600" href={route('couriers.index')}>Statistics & date filters →</Link></div>{data?<div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-7">{[['Total',data.total],['Pending',data.counts.pending||0],['In Transit',(data.counts.in_transit||0)+(data.counts.picked||0)],['Delivered',data.counts.delivered||0],['Returned',data.counts.returned||0],['Total COD','৳'+data.cod],['Collected COD',data.collectionVerified?'৳'+data.collected:'Not reconciled']].map(([label,value])=><div key={label}><p className="text-xs text-slate-500">{label}</p><b className="mt-1 block">{value}</b></div>)}</div>:<p className="text-sm text-slate-500">Open courier statistics to view bookings.</p>}</section>;
}
