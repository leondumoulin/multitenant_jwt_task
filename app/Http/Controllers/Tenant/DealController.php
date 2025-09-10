<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDealRequest;
use App\Http\Requests\Tenant\UpdateDealRequest;
use App\Http\Resources\Tenant\DealResource;
use App\Models\Tenant\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;

class DealController extends Controller
{
    private DealService $dealService;

    public function __construct(DealService $dealService)
    {
        $this->dealService = $dealService;
    }

    public function index(): JsonResponse
    {
        $deals = $this->dealService->getAllDeals();

        return response()->json([
            'success' => true,
            'data' => DealResource::collection($deals)
        ]);
    }

    public function store(StoreDealRequest $request): JsonResponse
    {
        try {
            $deal = $this->dealService->createDeal($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Deal created successfully',
                'data' => new DealResource($deal)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Deal $deal): JsonResponse
    {
        try {
            $deal = $this->dealService->getDealById($deal->id);

            if (!$deal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new DealResource($deal)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve deal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateDealRequest $request, Deal $deal): JsonResponse
    {
        try {
            $deal = $this->dealService->updateDeal($deal, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Deal updated successfully',
                'data' => new DealResource($deal)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update deal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Deal $deal): JsonResponse
    {
        try {
            $this->dealService->deleteDeal($deal);

            return response()->json([
                'success' => true,
                'message' => 'Deal deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete deal: ' . $e->getMessage()
            ], 500);
        }
    }
}
