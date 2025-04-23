<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OutageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization will be handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location_id' => 'required|exists:locations,id',
            'is_holiday' => 'required|boolean',
        ];

        // For updates, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return str_replace('required', 'sometimes|required', $rule);
            }, $rules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'location_id.required' => 'A location is required to fetch weather data.',
            'location_id.exists' => 'The selected location is invalid.',
            'start_time.required' => 'Please specify when the outage started.',
            'end_time.required' => 'Please specify when the outage ended.',
            'end_time.after' => 'The end time must be after the start time.',
        ];
    }
}