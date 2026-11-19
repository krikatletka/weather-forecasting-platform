<?php

namespace App\Http\Requests;

use App\Services\Alerts\AlertEvaluationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parameter' => ['sometimes', Rule::in(array_keys(AlertEvaluationService::PARAMETERS))],
            'operator' => ['sometimes', Rule::in(AlertEvaluationService::OPERATORS)],
            'threshold_value' => ['sometimes', 'numeric', 'between:-999999.99,999999.99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}