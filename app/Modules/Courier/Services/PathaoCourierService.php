<?php
namespace App\Modules\Courier\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
class PathaoCourierService extends Provider {
    public function parcelRules(): array { return ['city_id'=>'required|integer|min:1','zone_id'=>'required|integer|min:1','area_id'=>'required|integer|min:1']; }
    private function token(): string {
        $key = 'courier-token:'.$this->courier->id.':'.hash('sha256',json_encode($this->courier->credentials).$this->courier->api_url);
        $encrypted = Cache::get($key);
        if ($encrypted) return Crypt::decryptString($encrypted);
        $data = $this->response($this->http()->post($this->courier->api_url.'/aladdin/api/v1/external/login', array_intersect_key($this->courier->credentials,array_flip(['client_id','client_secret']))));
        if (empty($data['access_token'])) throw new \RuntimeException('Pathao authentication failed. Check client credentials.');
        Cache::put($key,Crypt::encryptString($data['access_token']),max(30,min(3600,(int)($data['expires_in']??3600)-60)));
        return $data['access_token'];
    }
    private function request(string $method, string $path, array $data = []): array {
        return $this->response($this->http()->withToken($this->token())->send($method,$this->courier->api_url.'/aladdin/api/v1/'.$path,['json'=>$data]));
    }
    public function createParcel(array $d): array {
        $response=$this->request('POST','orders',['store_id'=>(int)$this->courier->credentials['store_id'],'merchant_order_id'=>$d['reference'],'recipient_name'=>$d['recipient_name'],'recipient_phone'=>$d['phone'],'recipient_address'=>$d['address'],'recipient_city'=>(int)$d['city_id'],'recipient_zone'=>(int)$d['zone_id'],'recipient_area'=>(int)$d['area_id'],'delivery_type'=>(int)$d['delivery_type'],'item_type'=>2,'item_quantity'=>$d['quantity'],'item_weight'=>(float)$d['weight'],'amount_to_collect'=>(float)$d['cod_amount'],'special_instruction'=>$d['instruction']??'','item_description'=>$d['description']]);
        return ['consignment_id'=>$response['data']['consignment_id']??null,'courier_order_id'=>$response['data']['merchant_order_id']??null,'tracking_code'=>$response['data']['consignment_id']??null,'delivery_charge'=>$response['data']['delivery_fee']??null,'raw'=>$response];
    }
    public function getStatus(string $id): array { return $this->request('GET','orders/'.rawurlencode($id).'/info'); }
    public function testConnection(): array { $this->request('GET','stores'); return ['message'=>'Pathao connection successful.']; }
    public function locations(string $type, ?int $parent): array {
        $path=match($type) {'cities'=>'countries/1/city-list','zones'=>'cities/'.(int)$parent.'/zone-list','areas'=>'zones/'.(int)$parent.'/area-list','stores'=>'stores',default=>throw new \InvalidArgumentException('Invalid location type')};
        return $this->request('GET',$path)['data']['data']??[];
    }
}
