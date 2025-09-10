<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreActivityRequest;
use App\Http\Requests\Tenant\UpdateActivityRequest;
use App\Http\Resources\Tenant\ActivityResource;
use App\Models\Tenant\Activity;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    private ActivityService $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function index(): JsonResponse
    {
        $activities = $this->activityService->getAllActivities();

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities)
        ]);
    }

    public function store(StoreActivityRequest $request): JsonResponse
    {
        try {
            $activity = $this->activityService->createActivity($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Activity created successfully',
                'data' => new ActivityResource($activity)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create activity: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Activity $activity): JsonResponse
    {
        try {
            $activity = $this->activityService->getActivityById($activity->id);

            if (!$activity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Activity not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new ActivityResource($activity)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve activity: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        try {
            $activity = $this->activityService->updateActivity($activity, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Activity updated successfully',
                'data' => new ActivityResource($activity)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Activity $activity): JsonResponse
    {
        try {
            $this->activityService->deleteActivity($activity);

            return response()->json([
                'success' => true,
                'message' => 'Activity deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete activity: ' . $e->getMessage()
            ], 500);
        }
    }
}
