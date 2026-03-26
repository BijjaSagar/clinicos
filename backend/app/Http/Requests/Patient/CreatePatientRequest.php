<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePatientRequest extends FormRequest
{
    /**
     * Determine if the authenticated user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $clinicId = auth()->user()->clinic_id;

        return [
            // Core demographics
            'name'   => ['required', 'string', 'max:100'],
            'phone'  => [
                'required',
                'digits:10',
                Rule::unique('patients', 'phone')
                    ->where('clinic_id', $clinicId)
                    ->whereNull('deleted_at'),
            ],
            'dob'    => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],

            // Optional demographics
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])],
            'email'       => ['nullable', 'email', 'max:150'],

            // Address
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'pincode'       => ['nullable', 'digits:6'],

            // Medical
            'allergies'   => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:100'],
            'family_history' => ['nullable', 'string', 'max:2000'],

            // ABHA / national ID
            'abha_id'    => ['nullable', 'string', 'max:17', 'regex:/^\d{2}-\d{4}-\d{4}-\d{4}$/'],
            'abha_address' => ['nullable', 'string', 'max:100'],

            // Insurance
            'insurance_provider'      => ['nullable', 'string', 'max:100'],
            'insurance_policy_number' => ['nullable', 'string', 'max:100'],
            'insurance_expiry'        => ['nullable', 'date', 'after:today'],

            // Emergency contact
            'emergency_contact_name'         => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone'        => ['nullable', 'digits:10'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:50'],

            // ABHA initiation flag
            'initiate_abha' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required'         => 'Patient name is required.',
            'name.max'              => 'Patient name may not exceed 100 characters.',
            'phone.required'        => 'Mobile number is required.',
            'phone.digits'          => 'Mobile number must be exactly 10 digits.',
            'phone.unique'          => 'A patient with this mobile number is already registered at this clinic.',
            'dob.required'          => 'Date of birth is required.',
            'dob.before'            => 'Date of birth must be in the past.',
            'gender.required'       => 'Gender is required.',
            'gender.in'             => 'Gender must be one of: male, female, other.',
            'blood_group.in'        => 'Invalid blood group. Allowed: A+, A-, B+, B-, O+, O-, AB+, AB-.',
            'email.email'           => 'Please provide a valid email address.',
            'pincode.digits'        => 'Pincode must be exactly 6 digits.',
            'allergies.array'       => 'Allergies must be provided as a list.',
            'abha_id.regex'         => 'ABHA number must be in the format: 12-3456-7890-1234.',
            'insurance_expiry.after' => 'Insurance expiry date must be a future date.',
            'emergency_contact_phone.digits' => 'Emergency contact number must be exactly 10 digits.',
        ];
    }

    /**
     * Prepare the data for validation — trim strings, normalize phone.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => $this->filled('name')  ? trim($this->input('name'))  : $this->input('name'),
            'phone' => $this->filled('phone') ? preg_replace('/\D/', '', $this->input('phone')) : $this->input('phone'),
            'email' => $this->filled('email') ? strtolower(trim($this->input('email'))) : $this->input('email'),
        ]);
    }
}
