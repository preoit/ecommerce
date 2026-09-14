<?php
namespace App\Modules\Courier\Services;
use App\Modules\Courier\Models\Courier;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
abstract class Provider implements CourierServiceInterface {
    public function __construct(protected Courier $courier) {}
    public static function resolve(Courier $courier): CourierServiceInterface {
        $definition = config('couriers.providers.'.$courier->slug);
        $url = $definition[$courier->sandbox_mode ? 'sandbox' : 'live'] ?? null;
        if (!$url || rtrim($courier->api_url,'/') !== $url) throw ValidationException::withMessages(['courier'=>'Unsupported API URL or mode.']);
        foreach ($definition['fields'] as $field) if (blank($courier->credentials[$field] ?? null)) throw ValidationException::withMessages(['courier'=>'Complete courier credentials in Settings.']);
        return new $definition['service']($courier);
    }
    protected function http() { return Http::acceptJson()->asJson()->connectTimeout(5)->timeout(25)->withoutRedirecting(); }
    protected function response($response): array {
        if (!$response->successful() || !is_array($response->json())) throw new \RuntimeException('Courier request failed (HTTP '.$response->status().'). Check credentials and parcel details.');
        return $response->json();
    }
    public function cancelParcel(string $id): array { throw ValidationException::withMessages(['courier'=>'Cancellation is not available through this integration. Request cancellation in the courier merchant panel, then refresh status.']); }
    public function calculateDeliveryCharge(array $data): array { throw ValidationException::withMessages(['courier'=>'Live rate lookup is unavailable. The confirmed courier charge will appear when supplied by the provider.']); }
    public function trackParcel(string $id): array { return $this->getStatus($id); }
    public function locations(string $type, ?int $parent): array { return []; }
    public function parcelRules(): array { return ['city_id'=>'nullable|integer|min:1','zone_id'=>'nullable|integer|min:1','area_id'=>'nullable|integer|min:1']; }
}
