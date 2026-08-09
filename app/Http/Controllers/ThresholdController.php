<?php

namespace App\Http\Controllers;

use App\Models\WaterQualityThreshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ThresholdController extends Controller
{
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'thresholds' => ['required', 'array'],
            'thresholds.*.min_value' => ['nullable', 'numeric'],
            'thresholds.*.max_value' => ['nullable', 'numeric'],
            'thresholds.*.alert_enabled' => ['nullable', 'boolean'],
            'thresholds.*.warning_tolerance' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($validated['thresholds'] as $id => $values) {
            if (isset($values['min_value'], $values['max_value']) && (float) $values['min_value'] > (float) $values['max_value']) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nilai minimum tidak boleh lebih besar dari maksimum.',
                    ], 422);
                }

                return back()
                    ->withErrors(['thresholds' => 'Nilai minimum tidak boleh lebih besar dari maksimum.'])
                    ->withInput();
            }

            $updates = [];
            foreach (['min_value', 'max_value', 'warning_tolerance'] as $field) {
                if (array_key_exists($field, $values)) {
                    $updates[$field] = $values[$field] ?? 0;
                }
            }

            if (array_key_exists('alert_enabled', $values)) {
                $updates['alert_enabled'] = (bool) $values['alert_enabled'];
            }

            if ($updates === []) {
                continue;
            }

            WaterQualityThreshold::query()
                ->whereKey($id)
                ->update($updates);
        }

        WaterQualityThreshold::forgetThresholdCache();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan berhasil disimpan.',
            ]);
        }

        return back()->with('status', 'Pengaturan sensor berhasil diperbarui.');
    }
}
