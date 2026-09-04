<?php

namespace App\Modules\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Services\ModulePageService;
use Illuminate\Http\Request;
use Inertia\Response;

class ModulePageController extends Controller
{
    public function __construct(private readonly ModulePageService $modules) {}

    public function __invoke(Request $request): Response
    {
        return $this->modules->page($request);
    }
}
