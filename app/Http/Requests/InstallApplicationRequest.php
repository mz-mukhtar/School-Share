<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class InstallApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:100'],
            'site_url' => ['required', 'url:https', 'max:255'],
            'db_connection' => ['required', 'in:mysql,mariadb'],
            'db_host' => ['required', 'string', 'max:255', 'regex:/\A[a-zA-Z0-9.:-]+\z/'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:64', 'regex:/\A[a-zA-Z0-9_]+\z/'],
            'db_username' => ['required', 'string', 'max:47', 'regex:/\A[a-zA-Z0-9_]+\z/'],
            'db_password' => ['required', 'string', 'max:1024'],
            'mail_scheme' => ['required', 'in:smtp,smtps'],
            'mail_host' => ['required', 'string', 'max:255', 'regex:/\A[a-zA-Z0-9.-]+\z/'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:1024'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'lowercase', 'max:255'],
            'admin_password' => ['required', 'string', 'max:1024', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->only(array_keys($this->rules())) as $name => $value) {
                if (is_string($value) && preg_match('/[\x00-\x1f\x7f]/', $value)) {
                    $validator->errors()->add($name, 'Control characters are not allowed in installation settings.');
                }
            }
            $siteUrl = $this->input('site_url');
            $url = is_string($siteUrl) ? parse_url($siteUrl) : false;
            if (is_array($url) && (isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment']) || ! in_array($url['path'] ?? '', ['', '/'], true))) {
                $validator->errors()->add('site_url', 'Use the HTTPS site origin without a path, credentials, query, or fragment.');
            }
        }];
    }
}
