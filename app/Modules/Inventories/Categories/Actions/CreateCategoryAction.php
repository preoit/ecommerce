<?php

namespace App\Modules\Inventories\Categories\Actions;

use App\Modules\Inventories\Categories\DTOs\CategoryData;
use App\Modules\Inventories\Categories\Models\Category;
use Illuminate\Support\Facades\DB;

class CreateCategoryAction
{
    public function execute(CategoryData $data, ?string $imagePath): Category
    {
        return DB::transaction(fn (): Category => Category::query()->create([
            ...$data->toArray(),
            'image_path' => $imagePath,
        ]));
    }
}
