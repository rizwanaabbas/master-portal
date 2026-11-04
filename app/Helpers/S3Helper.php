<?php

namespace App\Helpers;

use Exception;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use OSS\OssClient;
use OSS\Core\OssException;
use Illuminate\Support\Facades\Log;

class S3Helper
{
    public static function fileUpload($file = null, $path, $content = null, $extension = "zip")
    {
        // Log::info("fileUpload called with file: " . ($file ? $file->getClientOriginalName() : "No file") . ", path: {$path}, content length: " . ($content ? strlen($content) : "No content") . ", extension: {$extension}");
        try {
            if ($file) {
                $filename = Str::uuid()->toString() . "." . $file->getClientOriginalExtension();
                $contents = file_get_contents($file->getRealPath());
                $fileExtension = $file->getClientOriginalExtension();
                $extensionsToBeCompressed = ['png', 'svg', 'jpg', 'gif', 'jpeg', 'webp', 'bmp', 'pdf'];

                if ($file && $file->getSize() > 5000000 && in_array($fileExtension, $extensionsToBeCompressed)) {
                    Log::info('---------------------------------------------------------------------------');
                    Log::info('Compressing the file');
                    Log::info('---------------------------------------------------------------------------');
                    $filePath = self::compressfile($file, $file->getClientOriginalExtension());

                    if ($filePath) {
                        $contents = file_get_contents($filePath);
                    }
                }
            } else {
                $filename = Str::uuid()->toString() . "." . $extension;
                $contents = $content;
            }

            // append file name with the provided path
            if (substr($path, strlen($path) - 1, strlen($path)) == "/") {
                $path = $path . $filename;
            } else {
                $path = $path . "/" . $filename;
            }

            switch (env("BUCKET_PROVIDER")) {
                case 'S3':
                    $s3 = Storage::disk('s3Docs');
                    $s3->put($path, $contents);
                    $s3->setVisibility($path, 'public');
                    return $s3->url($path);

                case 'OSS':
                    $accessKeyId = env("OSS_ACCESS_KEY_ID");
                    $accessKeySecret = env("OSS_ACCESS_KEY_SECRET");
                    $endpoint = env("OSS_ENDPOINT");
                    $endpointCustom = env("OSS_ENDPOINT_CUSTOM");
                    $bucket = env("OSS_BUCKET");

                    // Log::info("-------------------------------------");
                    // Log::info("OAKI: " . $accessKeyId . " | OAKS: " . $accessKeySecret . " | OEP: " . $endpoint . " | Bucket: " . $bucket);
                    // Log::info("-------------------------------------\n\n");

                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $result = $ossClient->putObject($bucket, $path, $contents);

                    Log::info("-------------------------------------");
                    Log::info("URL: " . $result['oss-request-url'] ?? "No URL");
                    Log::info("-------------------------------------\n\n");

                    // Log::info("-------------------------------------");
                    // Log::info("Custom URL: " . $endpointCustom . "/" . $path ?? "No URL");
                    // Log::info("-------------------------------------\n\n");

                    // return $result['oss-request-url'] ?? null;
                    return $endpointCustom ? $endpointCustom . "/" . $path : $result['oss-request-url'];

                default:
                    return null;
            }
            // Delete the temporarily created files and directory if exists
            if (File::exists(storage_path('app/uploaded_file'))) {
                File::deleteDirectory(storage_path('app/uploaded_file'));
            }
        } catch (Exception $e) {
            Log::error('File upload failed', [
                'message' => $e->getMessage(),
                'path' => $path,
            ]);

            return null;
        }
    }

    private static function detectBucketProvider($url)
    {
        if (strpos($url, "amazonaws.com") !== false) {
            return "S3";
        }

        if (strpos($url, env("OSS_ENDPOINT_CUSTOM")) !== false) {
            return "OSS";
        }

        return null;
    }

    public static function fileDelete($url)
    {
        $file_path = parse_url($url);

        # backward compatibality for AWS object delete
        $provider = self::detectBucketProvider($url);

        switch ($provider) {
            case 'S3':
                Storage::disk('s3Docs')->delete($file_path);
                return true;

            case 'OSS':
                $accessKeyId = env("OSS_ACCESS_KEY_ID");
                $accessKeySecret = env("OSS_ACCESS_KEY_SECRET");
                $endpoint = env("OSS_ENDPOINT");
                $bucket = env("OSS_BUCKET");

                # if first character is "/" then remove it, oss doesn't support object path starting with "/"
                if ($file_path["path"][0] == "/") {
                    $file_path["path"] = substr($file_path["path"], 1, strlen($file_path["path"]));
                }

                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                $result = $ossClient->deleteObject($bucket, $file_path["path"]);
                return true;
        }
    }

    public static function moveS3($url, $folder)
    {
        $file_path = parse_url($url);
        $s3 = Storage::disk('s3Docs');
        $s3->put($folder . $file_path['path'], fopen(public_path($file_path['path']), "r"));
        $s3->setVisibility($folder . $file_path['path'], 'public');

        return $s3->url($folder . $file_path['path']);
    }

    protected static function compressfile($file, $extension)
    {
        $imageSizeMapping = [
            30000000 => '70%',
            20000000 => '20%',
            10000000 => '40%',
            5000000 => '60%',
            'default' => '70%'
        ];

        $path = $file->store('uploaded_file');

        // dd(storage_path('app/' . $path));
        $originalFile = storage_path('app/' . $path); // $file->getPathname();
        $compressedFilePath = storage_path('app/uploaded_file/compressed_file.' . $extension);

        // Log::info('---------------------------------------------------------------------------');
        // Log::info('Target File: ' . $originalFile . ' | Compressed File: ' . $compressedFilePath);
        // Log::info('---------------------------------------------------------------------------');

        switch ($extension) {
            case 'pdf':
                // $compressionIndex = self::getCompressionPercentage($file->getSize(), $pdfSizeMapping);

                // Log::info('---------------------------------------------------------------------------');
                // Log::info('File Size: ' . $file->getSize() . ' | Compression Index: ' . $compressionIndex);
                // Log::info('---------------------------------------------------------------------------');

                $command = "gs \
                           -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite \
                           -dCompatibilityLevel=1.4 \
                           -dPDFSETTINGS=/screen \
                           -dEmbedAllFonts=false \
                           -dSubsetFonts=true \
                           -dColorImageDownsampleType=/Bicubic \
                           -dColorImageResolution=112 \
                           -dGrayImageDownsampleType=/Bicubic \
                           -dGrayImageResolution=112 \
                           -dMonoImageDownsampleType=/Bicubic \
                           -dMonoImageResolution=112 \
                           -sOutputFile=$compressedFilePath $originalFile";

                break;

            case 'jpg':
            case 'jpeg':
            case 'png':
                $compressionPercentage = self::getCompressionPercentage($file->getSize(), $imageSizeMapping);

                // Log::info('---------------------------------------------------------------------------');
                // Log::info('File Size: ' . $file->getSize() . ' | Compression Index: ' . $compressionPercentage);
                // Log::info('---------------------------------------------------------------------------');

                $command = "convert -resize $compressionPercentage $originalFile $compressedFilePath";
                break;

            default:
                return $originalFile;
        }
        // Run the Ghostscript command to compress the PDF

        // Log::info('---------------------------------------------------------------------------');
        // Log::info('File Compression Command: ' . $command);
        // Log::info('---------------------------------------------------------------------------');

        try {
            exec($command, $output, $returnVar);
        } catch (Exception $e) {
            Log::error('Failed to compress the PDF File.');
            Log::error($e->getMessage());
            Log::error($e->getTrace());

            return $originalFile;
        }

        // Log::info('---------------------------------------------------------------------------');
        // Log::info('Return Var: ' . $returnVar);
        // Log::info('---------------------------------------------------------------------------');

        if ($returnVar != 0) {
            // Log::error('---------------------------------------------------------------------------');
            // Log::error('Failed to compress the PDF File.');
            // Log::error('---------------------------------------------------------------------------');

            return $originalFile;
            // return response()->json(['error' => 'Failed to compress PDF'], 500);
        } else {
            // Log::info('---------------------------------------------------------------------------');
            // Log::info('File been compressed successfully! File Path: ' . $compressedFilePath);
            // Log::info('---------------------------------------------------------------------------');
        }

        return $compressedFilePath;

        // Return the compressed file as a response
        // return response()->download($$compressedFilePath)->deleteFileAfterSend(true);
    }

    private static function getCompressionPercentage($fileSize, $sizeMapping)
    {
        foreach ($sizeMapping as $size => $percentage) {
            if ($fileSize >= $size) {
                return $percentage;
            }
        }

        return $sizeMapping['default'];
    }
}
