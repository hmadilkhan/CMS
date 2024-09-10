<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRules;

class UpdateEmployeeRequest extends FormRequest
{
    public function rules()
    {
        $employeeId = $this->route('employee')->id;
        $userId = $this->input('user_id'); // Get the associated user ID

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Employee::class)->ignore($employeeId)
            ],
            'departments' => 'required|array',
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($userId)
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class)->ignore($userId)
            ],
            'password' => ['nullable', 'confirmed', PasswordRules::defaults()],
            'roles' => 'required',
            'file' => 'nullable|file|mimes:jpg,png,jpeg',
            'overwrite_base_price' => 'nullable|numeric',
            'overwrite_panel_price' => 'nullable|numeric',
        ];
    }

    public function authorize()
    {
        // You can implement authorization logic here if needed, or return true
        return true;
    }
}
