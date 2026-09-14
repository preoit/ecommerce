<?php
namespace App\Modules\Courier\Services;
class SteadfastCourierService extends Provider {
    private function request(string $method, string $path, array $data=[]): array {
        $result=$this->response($this->http()->withHeaders(['Api-Key'=>$this->courier->credentials['api_key'],'Secret-Key'=>$this->courier->credentials['secret_key']])->send($method,$this->courier->api_url.'/'.$path,['json'=>$data]));
        if (isset($result['status']) && (int)$result['status']!==200) throw new \RuntimeException('Steadfast rejected the request. Check credentials, invoice and recipient details.');
        return $result;
    }
    public function createParcel(array $d): array {
        $response=$this->request('POST','create_order',['invoice'=>$d['reference'],'recipient_name'=>$d['recipient_name'],'recipient_phone'=>$d['phone'],'recipient_address'=>$d['address'],'cod_amount'=>$d['cod_amount'],'note'=>$d['instruction']??'']);
        return ['consignment_id'=>$response['consignment']['consignment_id']??null,'courier_order_id'=>$response['consignment']['invoice']??null,'tracking_code'=>$response['consignment']['tracking_code']??null,'delivery_charge'=>null,'raw'=>$response];
    }
    public function getStatus(string $id): array { return $this->request('GET','status_by_cid/'.rawurlencode($id)); }
    public function testConnection(): array { $this->request('GET','get_balance'); return ['message'=>'Steadfast connection successful.']; }
}
