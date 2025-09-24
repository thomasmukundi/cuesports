<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /**
     * Get all regions
     */
    public function getRegions(): JsonResponse
    {
        try {
            $regions = DB::table('regions')
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($region) {
                    return [
                        'id' => $region->id,
                        'name' => $region->name
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $regions
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching regions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch regions'
            ], 500);
        }
    }

    /**
     * Get counties by region
     */
    public function getCountiesByRegion(Request $request): JsonResponse
    {
        $regionId = $request->query('region_id');
        
        if (!$regionId) {
            return response()->json([
                'success' => false,
                'message' => 'Region ID is required'
            ], 400);
        }

        try {
            $counties = DB::table('counties')
                ->select('id', 'name', 'region_id')
                ->where('region_id', $regionId)
                ->orderBy('name')
                ->get()
                ->map(function ($county) {
                    return [
                        'id' => $county->id,
                        'name' => $county->name,
                        'region_id' => $county->region_id
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $counties
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching counties for region ' . $regionId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch counties'
            ], 500);
        }
    }

    /**
     * Get communities by county
     */
    public function getCommunitiesByCounty(Request $request): JsonResponse
    {
        $countyId = $request->query('county_id');
        
        if (!$countyId) {
            return response()->json([
                'success' => false,
                'message' => 'County ID is required'
            ], 400);
        }

        try {
            $communities = DB::table('communities')
                ->select('id', 'name', 'county_id')
                ->where('county_id', $countyId)
                ->orderBy('name')
                ->get()
                ->map(function ($community) {
                    return [
                        'id' => $community->id,
                        'name' => $community->name,
                        'county_id' => $community->county_id
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $communities
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching communities for county ' . $countyId . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch communities'
            ], 500);
        }
    }

    /**
     * Get all counties (for reference)
     */
    public function getAllCounties(): JsonResponse
    {
        try {
            $counties = DB::table('counties')
                ->select('id', 'name', 'region_id')
                ->orderBy('name')
                ->get()
                ->map(function ($county) {
                    return [
                        'id' => $county->id,
                        'name' => $county->name,
                        'region_id' => $county->region_id
                    ];
                })
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => $counties
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching all counties: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch counties'
            ], 500);
        }
    }

}
