<?php

namespace App\Service\External;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Http;

class SupabaseService
{

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
}
