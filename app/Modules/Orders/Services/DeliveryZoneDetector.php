<?php

namespace App\Modules\Orders\Services;

class DeliveryZoneDetector
{
    private const DHAKA_AREAS = [
        'dhaka', 'ঢাকা', 'uttara', 'উত্তরা', 'mirpur', 'মিরপুর', 'dhanmondi', 'ধানমন্ডি',
        'gulshan', 'গুলশান', 'banani', 'বনানী', 'badda', 'বাড্ডা', 'mohakhali', 'মহাখালী',
        'mohammadpur', 'মোহাম্মদপুর', 'motijheel', 'মতিঝিল', 'jatrabari', 'যাত্রাবাড়ী',
        'bashundhara', 'বসুন্ধরা', 'rampura', 'রামপুরা', 'khilgaon', 'খিলগাঁও',
    ];

    public function detect(?string $district, ?string $city = null, ?string $address = null): string
    {
        $district = mb_strtolower(trim((string) $district));
        if ($district !== '') {
            return in_array($district, ['dhaka', 'ঢাকা'], true) ? 'inside_dhaka' : 'outside_dhaka';
        }

        $location = mb_strtolower(implode(' ', array_filter([$city, $address])));
        foreach (self::DHAKA_AREAS as $area) {
            if (str_contains($location, $area)) return 'inside_dhaka';
        }

        return 'outside_dhaka';
    }
}
