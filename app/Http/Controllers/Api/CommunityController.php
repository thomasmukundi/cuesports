<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\County;
use App\Models\Region;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    /**
     * Get all communities with region/county filtering
     */
    public function index(Request $request)
    {
        try {
            $query = Community::with(['county.region']);
            
            // Filter by region if provided
            if ($request->has('region_id')) {
                $query->whereHas('county', function ($q) use ($request) {
                    $q->where('region_id', $request->region_id);
                });
            }
            
            // Filter by county if provided
            if ($request->has('county_id')) {
                $query->where('county_id', $request->county_id);
            }
            
            // Search by name if provided
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            $communities = $query->orderBy('name', 'asc')->get();
            
            $communities = $communities->map(function ($community) {
                return [
                    'id' => $community->id,
                    'name' => $community->name,
                    'county' => [
                        'id' => $community->county->id,
                        'name' => $community->county->name,
                        'region' => [
                            'id' => $community->county->region->id,
                            'name' => $community->county->region->name,
                        ]
                    ],
                    'description' => $community->description ?? '',
                    'member_count' => $community->users()->count(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $communities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching communities: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get community details
     */
    public function show(Community $community)
    {
        try {
            $community->load(['county.region', 'users']);
            
            return response()->json([
                'success' => true,
                'community' => [
                    'id' => $community->id,
                    'name' => $community->name,
                    'description' => $community->description,
                    'county' => [
                        'id' => $community->county->id,
                        'name' => $community->county->name,
                        'region' => [
                            'id' => $community->county->region->id,
                            'name' => $community->county->region->name,
                        ]
                    ],
                    'member_count' => $community->users->count(),
                    'members' => $community->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'profile_image' => $user->profile_image,
                        ];
                    }),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching community details: ' . $e->getMessage()
            ], 500);
        }
    }
}
