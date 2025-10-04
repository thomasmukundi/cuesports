<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    /**
     * Debug storage configuration and file existence
     */
    public function debugStorage(Request $request)
    {
        $path = $request->get('path', 'profile_images/profile_2409_1758884288.jpg');
        
        $debug = [
            'default_disk' => config('filesystems.default'),
            'path_requested' => $path,
            'timestamp' => now()->toISOString(),
        ];
        
        try {
            $defaultDisk = config('filesystems.default', 'public');
            $debug['default_disk_config'] = config("filesystems.disks.{$defaultDisk}");
            
            // Check if file exists on default disk
            $debug['file_exists_on_default'] = Storage::disk($defaultDisk)->exists($path);
            
            // Try to get URL
            if ($debug['file_exists_on_default']) {
                $debug['storage_url'] = Storage::disk($defaultDisk)->url($path);
            }
            
            // List files in profile_images directory
            try {
                $debug['files_in_profile_images'] = Storage::disk($defaultDisk)->files('profile_images');
            } catch (\Exception $e) {
                $debug['profile_images_error'] = $e->getMessage();
            }
            
            // Check public disk as well
            if ($defaultDisk !== 'public') {
                $debug['file_exists_on_public'] = Storage::disk('public')->exists($path);
                if ($debug['file_exists_on_public']) {
                    $debug['public_storage_url'] = Storage::disk('public')->url($path);
                }
            }
            
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['trace'] = $e->getTraceAsString();
        }
        
        Log::info('Storage debug info', $debug);
        
        return response()->json([
            'success' => true,
            'debug' => $debug
        ]);
    }
    
    /**
     * Test the /storage/{path} route redirect
     */
    public function testStorageRedirect(Request $request)
    {
        $path = $request->get('path', 'profile_images/profile_2409_1758884288.jpg');
        
        $debug = [
            'path' => $path,
            'default_disk' => config('filesystems.default'),
            'timestamp' => now()->toISOString(),
        ];
        
        try {
            $default = config('filesystems.default', 'public');
            
            if ($default !== 'public') {
                $url = Storage::disk($default)->url($path);
                $debug['would_redirect_to'] = $url;
                $debug['redirect_logic'] = 'Should redirect to S3 URL';
            } else {
                $debug['redirect_logic'] = 'Would abort(404) - using public disk';
            }
            
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
        }
        
        return response()->json([
            'success' => true,
            'debug' => $debug
        ]);
    }
}
