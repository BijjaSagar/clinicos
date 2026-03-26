<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class CreatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:15'],
            'sex' => ['nullable', 'in:M,F,O'],
            'dob' => ['nullable', 'date'],
            'age_years' => ['nullable', 'integer', 'min:0', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'blood_group' => ['nullable', 'string', 'max:5'],
            'phone_alt' => ['nullable', 'string', 'max:15'],
            'abha_id' => ['nullable', 'string', 'max:20'],
            'abha_address' => ['nullable', 'string', 'max:100'],
            'known_allergies' => ['nullable', 'string'],
            'chronic_conditions' => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'referred_by' => ['nullable', 'string', 'max:200'],
            'source' => ['nullable', 'in:walk_in,online_booking,referral,whatsapp,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Patient name is required.',
            'phone.required' => 'Phone number is required.',
            'sex.in' => 'Please select a valid gender (Male, Female, or Other).',
            'dob.date' => 'Please enter a valid date of birth.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
