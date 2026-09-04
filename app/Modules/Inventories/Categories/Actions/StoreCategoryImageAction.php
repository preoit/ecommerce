<?php

namespace App\Modules\Inventories\Categories\Actions;

use Illuminate\Http\UploadedFile;

class StoreCategoryImageAction
{
    public function execute(?UploadedFile $image): ?string
    {
        return $image?->store('categories', 'public');
    }
}
