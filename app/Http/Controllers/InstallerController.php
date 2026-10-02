<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstallApplicationRequest;
use App\Support\Installer\InstallerService;
use App\Support\Installer\ServerRequirements;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstallerController extends Controller
{
    public function index(Request $request, ServerRequirements $requirements): View
    {
        return view('installer.index', [
            'requirements' => $requirements->check(),
            'defaultUrl' => $request->getSchemeAndHttpHost(),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function testDatabase(Request $request, InstallerService $installer): JsonResponse
    {
        $data = $request->validate(InstallApplicationRequest::databaseRules());
        $result = $installer->testDatabase($data);

        return response()->json([
            'message' => $result['tables'] > 0
                ? "Connection successful. {$result['tables']} existing table(s) detected; installation requires an empty database."
                : 'Connection successful. The database is empty and ready.',
            'tables' => $result['tables'],
        ]);
    }

    public function store(
        InstallApplicationRequest $request,
        InstallerService $installer,
        ServerRequirements $requirements,
    ): View {
        if (! $requirements->check()['passed']) {
            throw ValidationException::withMessages([
                'requirements' => 'Resolve all failed server requirements before continuing.',
            ]);
        }

        @set_time_limit(300);
        $data = $request->validated();
        $result = $installer->install($data);

        return view('installer.complete', [
            'adminEmail' => $data['admin_email'],
            'warnings' => $result['warnings'],
        ]);
    }
}
