<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSensorReadingRequest extends FormRequest
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
            'device_code' => ['nullable', 'string', 'max:80'],
            'device_id' => ['nullable', 'string', 'max:80'],
            'kode_perangkat' => ['nullable', 'string', 'max:80'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'nama_perangkat' => ['nullable', 'string', 'max:120'],
            'ph' => ['required', 'numeric', 'between:0,14'],
            'suhu' => ['nullable', 'numeric', 'between:0,80'],
            'temperature' => ['nullable', 'numeric', 'between:0,80'],
            'temperature_celsius' => ['nullable', 'numeric', 'between:0,80'],
            'tds' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'tds_ppm' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'wifi_rssi' => ['nullable', 'integer', 'between:-120,0'],
            'rssi' => ['nullable', 'integer', 'between:-120,0'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'uptime' => ['nullable', 'integer', 'min:0'],
            'firmware_version' => ['nullable', 'string', 'max:80'],
            'sensor_status' => ['nullable', 'string', 'max:80'],
            'power_status' => ['nullable', 'string', 'max:80'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('suhu') === null
                && $this->input('temperature') === null
                && $this->input('temperature_celsius') === null) {
                $validator->errors()->add('temperature_celsius', 'Field suhu atau temperature_celsius wajib diisi.');
            }

            if ($this->input('tds') === null && $this->input('tds_ppm') === null) {
                $validator->errors()->add('tds_ppm', 'Field tds atau tds_ppm wajib diisi.');
            }
        });
    }
}
