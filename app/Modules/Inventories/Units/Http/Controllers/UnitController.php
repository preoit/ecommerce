<?php

namespace App\Modules\Inventories\Units\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventories\Units\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $units = Unit::query()->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))->orderBy('sort_order')->orderBy('name')->paginate(10)->withQueryString();
        $units->getCollection()->each(fn (Unit $unit) => $unit->setAttribute('children_count', 0)->setAttribute('products_count', 0)->setAttribute('depth', 0));
        return Inertia::render('app/modules/inventories/categories/pages/Index', ['categories' => $units, 'parentOptions' => [], 'filters' => ['search' => $search], 'resource' => 'units', 'entityLabel' => 'unit']);
    }

    public function store(Request $request): RedirectResponse { return $this->save($request); }
    public function update(Request $request, Unit $unit): RedirectResponse { return $this->save($request, $unit); }
    public function destroy(Unit $unit): RedirectResponse { Storage::disk('public')->delete($unit->image_path); $unit->delete(); return to_route('inventories.units.index')->with('success', 'Unit deleted successfully.'); }

    private function save(Request $request, ?Unit $unit = null): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:units,slug,'.($unit?->id ?? 'NULL')], 'short_description' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string'], 'image_path' => ['nullable', 'string', 'max:255']]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $unit ? $unit->update($data) : Unit::create($data);
        return to_route('inventories.units.index')->with('success', $unit ? 'Unit updated successfully.' : 'Unit created successfully.');
    }
}
