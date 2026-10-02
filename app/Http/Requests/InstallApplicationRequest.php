<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class InstallApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:80', 'not_regex:/[\r\n]/'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            ...self::databaseRules(),
            'store_name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'store_phone' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'store_email' => ['nullable', 'email:rfc', 'max:255'],
            'delivery_inside_dhaka' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'delivery_outside_dhaka' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'admin_name' => ['required', 'string', 'max:120', 'not_regex:/[\r\n]/'],
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'admin_phone' => ['nullable', 'regex:/^\+?[0-9]{10,15}$/'],
            'admin_password' => ['required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'terms' => ['accepted'],
        ];
    }

    /** @return array<string, mixed> */
    public static function databaseRules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_$-]+$/'],
            'db_username' => ['required', 'string', 'max:128', 'not_regex:/[\r\n]/'],
            'db_password' => ['nullable', 'string', 'max:1000', 'not_regex:/[\r\n]/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'app_url' => rtrim((string) $this->input('app_url'), '/'),
            'admin_email' => strtolower(trim((string) $this->input('admin_email'))),
            'store_email' => strtolower(trim((string) $this->input('store_email'))),
        ]);
    }
}
