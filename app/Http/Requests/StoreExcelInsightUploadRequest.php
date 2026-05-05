<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExcelInsightUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'extensions:xlsm', 'max:20480'],
            'tabs' => ['required', 'array', 'min:1'],
            'tabs.*' => ['required', Rule::in(config('excel_insights.allowed_sheets', []))],
        ];
    }
}
