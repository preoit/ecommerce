<?php
namespace App\Modules\Courier\Services;
interface CourierServiceInterface {
    public function createParcel(array $data): array;
    public function cancelParcel(string $id): array;
    public function trackParcel(string $id): array;
    public function getStatus(string $id): array;
    public function calculateDeliveryCharge(array $data): array;
    public function testConnection(): array;
    public function locations(string $type, ?int $parent): array;
    public function parcelRules(): array;
}
