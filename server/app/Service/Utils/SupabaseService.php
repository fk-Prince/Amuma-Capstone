<?php

namespace App\Service\Utils;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Http;

class SupabaseService
{
    public function __construct() {}

    public static function store(UploadedFile $image): array
    {
        $filePath = Str::uuid() . '.' . $image->getClientOriginalExtension();
        $bucket = env('SUPABASE_BUCKET');

        $response = Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        ])->attach(
            'file',
            file_get_contents($image->getRealPath()),
            $image->getClientOriginalName()
        )->post(env('SUPABASE_URL') . "/storage/v1/object/{$bucket}/{$filePath}");

        if (! $response->successful()) {
            throw new Exception('Upload failed: ' . $response->body());
        }

        return [
            'path' => $filePath,
            'url' => env('SUPABASE_URL') . "/storage/v1/object/public/{$bucket}/{$filePath}",
        ];
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'image' => 'required|file|max:5120',
    //     ]);

    //     $file = $request->file('image');

    //     $filePath = Str::uuid() . '.' . $file->getClientOriginalExtension();

    //     $bucket = env('SUPABASE_BUCKET'); // uploads

    //     $response = Http::withOptions([
    //         'verify' => false,
    //     ])->withHeaders([
    //         'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
    //         'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
    //     ])->attach(
    //         'file',
    //         file_get_contents($file),
    //         $file->getClientOriginalName()
    //     )->post(env('SUPABASE_URL') . "/storage/v1/object/$bucket/$filePath");

    //     if (!$response->successful()) {
    //         return response()->json([
    //             'error' => 'Upload failed',
    //             'details' => $response->json()
    //         ], 500);
    //     }

    //     $publicUrl = env('SUPABASE_URL')
    //         . "/storage/v1/object/public/$bucket/$filePath";

    //     return response()->json([
    //         'path' => $filePath,
    //         'url' => $publicUrl
    //     ]);
    // }

    // public function store(UploadedFile $image): array
    // {
    //     $file = $image->file('image');
    //     $filePath = Str::uuid() . '.' .  $file->getClientOriginalExtension();
    //     $bucket = env('SUPABASE_BUCKET');

    //     $response = Http::withOptions([
    //         'verify' => false,
    //     ])->withHeaders([
    //         'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
    //         'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
    //     ])->attach(
    //         'file',
    //         file_get_contents($image->getRealPath()),
    //         $image->getClientOriginalName()
    //     )->post(env('SUPABASE_URL') . "/storage/v1/object/{$bucket}/{$filePath}");

    //     if (! $response->successful()) {
    //         throw new Exception(
    //             'Upload failed: ' . $response->body()
    //         );
    //     }

    //     return [
    //         'path' => $filePath,
    //         'url' => env('SUPABASE_URL') . "/storage/v1/object/public/{$bucket}/{$filePath}",
    //     ];
    // }
}
