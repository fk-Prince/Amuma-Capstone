<?php

namespace App\Service\External;


use App\Exceptions\ExternalServiceException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::warning('Supabase upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ExternalServiceException::thirdParty();
        }

        return [
            'path' => $filePath,
            'url' => env('SUPABASE_URL') . "/storage/v1/object/public/{$bucket}/{$filePath}",
        ];
    }
}
