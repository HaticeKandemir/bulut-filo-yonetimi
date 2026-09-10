<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateVehicleRequest extends FormRequest
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
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'plate' => ['required', 'string', 'max:20'],
            'status' => ['required', new Enum(VehicleStatus::class)],
        ];
    }
}
