<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadHelper
{
    /**
     * Upload image to AWS S3
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param string $visibility
     * @return array
     */
    public static function uploadImageToS3($file, $folder = 'products', $visibility = 'public')
    {
        try {
            // Validate file
            if (!$file || !$file->isValid()) {
                throw new \Exception('Invalid file');
            }

            // Validate image type
            $allowedMimes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
                throw new \Exception('Invalid image format. Allowed: ' . implode(', ', $allowedMimes));
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::random(20) . '_' . time() . '.' . $extension;
            $filePath = $folder . '/' . $filename;

            // Store file in S3
            $path = Storage::disk('s3')->put($filePath, file_get_contents($file), $visibility);

            // Get full URL
            $url = Storage::disk('s3')->url($filePath);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $filename,
                'url' => $url,
                'original_name' => $file->getClientOriginalName(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'file_path' => null,
                'url' => null,
            ];
        }
    }

    /**
     * Delete image from AWS S3
     *
     * @param string $filePath
     * @return array
     */
    public static function deleteImageFromS3($filePath)
    {
        try {
            if (Storage::disk('s3')->exists($filePath)) {
                Storage::disk('s3')->delete($filePath);
                return [
                    'success' => true,
                    'message' => 'File deleted successfully'
                ];
            }

            return [
                'success' => false,
                'error' => 'File not found'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload multiple images to S3
     *
     * @param array $files
     * @param string $folder
     * @param string $visibility
     * @return array
     */
    public static function uploadMultipleImagesToS3($files, $folder = 'products', $visibility = 'public')
    {
        $results = [];

        foreach ($files as $file) {
            $results[] = self::uploadImageToS3($file, $folder, $visibility);
        }

        return $results;
    }
}
