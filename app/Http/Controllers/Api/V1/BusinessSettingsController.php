<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateBusinessSettingsRequest;
use App\Http\Resources\Api\V1\BusinessSettingsResource;
use App\Models\BusinessSettings;
use Illuminate\Http\JsonResponse;

class BusinessSettingsController extends Controller
{
    /**
     * Get the business settings (singleton).
     */
    public function show(): BusinessSettingsResource|JsonResponse
    {
        $settings = BusinessSettings::getInstance();

        if (!$settings) {
            return response()->json([
                'message' => 'Business settings not configured.',
                'data' => null,
            ], 200);
        }

        return new BusinessSettingsResource($settings);
    }

    /**
     * Update or create the business settings (singleton pattern).
     */
    public function update(UpdateBusinessSettingsRequest $request): BusinessSettingsResource
    {
        $settings = BusinessSettings::getInstance();

        if ($settings) {
            $settings->update($request->validated());
        } else {
            $settings = BusinessSettings::create($request->validated());
        }

        return new BusinessSettingsResource($settings);
    }
}
