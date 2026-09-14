import { useEffect, useState } from 'react';
import { api, Field } from './ui';
export default function LocationFields({courierId,value,onChange}) {
    const [cities,setCities]=useState([]),[zones,setZones]=useState([]),[areas,setAreas]=useState([]),[error,setError]=useState('');
    useEffect(()=>{let active=true;api(route('couriers.locations',courierId)+'?type=cities').then(v=>active&&setCities(v)).catch(e=>active&&setError(e.message));return()=>{active=false;};},[courierId]);
    useEffect(()=>{let active=true;setZones([]);if(value.city_id)api(route('couriers.locations',courierId)+'?type=zones&parent='+value.city_id).then(v=>active&&setZones(v)).catch(e=>active&&setError(e.message));return()=>{active=false;};},[courierId,value.city_id]);
    useEffect(()=>{let active=true;setAreas([]);if(value.zone_id)api(route('couriers.locations',courierId)+'?type=areas&parent='+value.zone_id).then(v=>active&&setAreas(v)).catch(e=>active&&setError(e.message));return()=>{active=false;};},[courierId,value.zone_id]);
    return <><div className="grid gap-2 sm:grid-cols-3">{[['City',cities,'city'],['Zone',zones,'zone'],['Area',areas,'area']].map(([label,options,key])=><Field key={key} label={label}><select required value={value[key+'_id']||''} onChange={e=>onChange({...value,[key+'_id']:e.target.value,...(key==='city'?{zone_id:'',area_id:''}:key==='zone'?{area_id:''}:{})})}><option value="">Select {label}</option>{options.map(item=><option key={item[key+'_id']} value={item[key+'_id']}>{item[key+'_name']}</option>)}</select></Field>)}</div>{error&&<p className="text-xs text-rose-600">{error}</p>}</>;
}
