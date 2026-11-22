<?php

namespace App\Http\Requests;

use App\Services\Alerts\AlertEvaluationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // доступ обмежує auth:sanctum на маршруті
    }

    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'parameter' => ['required', Rule::in(array_keys(AlertEvaluationService::PARAMETERS))],
            'operator' => ['required', Rule::in(AlertEvaluationService::OPERATORS)],
            'threshold_value' => ['required', 'numeric', 'between:-999999.99,999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}