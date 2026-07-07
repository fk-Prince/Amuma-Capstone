<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class sample extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|max:5120',
        ]);

        $file = $request->file('image');

        $filePath = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $bucket = env('SUPABASE_BUCKET');

        $response = Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        ])->attach(
            'file',
            file_get_contents($file),
            $file->getClientOriginalName()
        )->post(env('SUPABASE_URL') . "/storage/v1/object/$bucket/$filePath");

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Upload failed',
                'details' => $response->json()
            ], 500);
        }

        $publicUrl = env('SUPABASE_URL')
            . "/storage/v1/object/public/$bucket/$filePath";

        return response()->json([
            'path' => $filePath,
            'url' => $publicUrl
        ]);
    }
}
