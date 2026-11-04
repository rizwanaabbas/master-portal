<?php

namespace App\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class SupportingDocumentsRule implements Rule
{
    protected $maxSize;
    protected $errorMessage;
    protected $statusCode;
    protected $throwError;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($throwError = false)
    {
        $this->maxSize = env('MAX_FILE_SIZE');
        $this->errorMessage = '';
        $this->statusCode = 200;
        $this->throwError = $throwError;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (is_array($value)) {
            foreach ($value as $file) {
                $isValid = $this->validateFile($file);

                if (!$isValid) {
                    return $isValid;
                }
            }
        } else {
            $isValid = $this->validateFile($value);

            if (!$isValid) {
                return $isValid;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessage;
    }

    private function validateFile($file)
    {
        if (!$file || !is_object($file) || !method_exists($file, 'getError')) {
            return true;
        }

        if ($file->getError()) {
            switch ($file->getError()) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->errorMessage = 'The file (' . $file->getClientOriginalName() . ') size exceeds the maximum file upload limit (' . ($this->maxSize / 1024 / 1024) . 'MB)'; // 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                    $this->statusCode = 413;
                    break;

                case UPLOAD_ERR_FORM_SIZE:
                    $this->errorMessage = 'The file (' . $file->getClientOriginalName() . ') size exceeds the maximum file upload limit (' . ($this->maxSize / 1024 / 1024) . 'MB)'; // 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                    $this->statusCode = 413;
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $this->errorMessage = 'The file (' . $file->getClientOriginalName() . ') was only partially uploaded.';
                    $this->statusCode = 400;
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $this->errorMessage = 'No file was uploaded.';
                    $this->statusCode = 400;
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->errorMessage = 'The file (' . $file->getClientOriginalName() . ') upload has been stopped! Please contact our team to help you upload the file.'; // 'Missing a temporary folder.';
                    $this->statusCode = 500;
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $this->errorMessage = 'The file (' . $file->getClientOriginalName() . ') upload has been stopped! Please contact our team to help you upload the file.'; // 'Failed to write file to disk.';
                    $this->statusCode = 500;
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $this->errorMessage = 'The file (' . $file->getClientOriginalName() . ') upload has been stopped! Please contact our team to help you upload the file.'; // 'A PHP extension stopped the file upload.';
                    $this->statusCode = 500;
                    break;

                default:
                    $this->errorMessage = 'An unknown error occurred during the file upload.';
                    $this->statusCode = 500;
                    break;
            }

            if ($this->throwError) {
                throw new Exception($this->errorMessage, $this->statusCode);
            } else {
                return false;
            }
        }

        // $imagecontents = file_get_contents($file);

        // if (strpos($imagecontents, '<script>') !== false) {
        //     $this->errorMessage = 'Something went wrong, please try again later.';
        //     $this->statusCode = 500;

        //     if ($this->throwError) {
        //         throw new Exception($this->errorMessage, $this->statusCode);
        //     } else {
        //         return false;
        //     }
        // }

        // return true;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getPathname());
        $hasError = false;

        switch ($mime) {
            case 'image/svg+xml':
            case 'image/svg':
                // SVG is XML — getimagesize() often fails even for valid files.
                $contents = (string) file_get_contents($file->getPathname());
                if ($contents === '' || stripos($contents, '<svg') === false) {
                    $this->errorMessage = 'Something went wrong, please try again later.';
                    $this->statusCode = 500;
                    Log::error('Invalid SVG file. Line: ' . __LINE__ . ' File: ' . $file->getClientOriginalName());

                    if ($this->throwError) {
                        throw new Exception($this->errorMessage, $this->statusCode);
                    }

                    return false;
                }

                return true;

            case 'image/bmp':
            case 'image/jpg':
            case 'image/jpeg':
            case 'image/png':
            case 'image/gif':
            case 'image/webp':
                // Validate raster image structure
                if (@getimagesize($file->getPathname()) === false) {
                    $this->errorMessage = 'Something went wrong, please try again later.';
                    $this->statusCode = 500;
                    Log::error('Invalid image file structure. Line: ' . __LINE__ . ' File: ' . $file->getClientOriginalName());

                    if ($this->throwError) {
                        throw new Exception($this->errorMessage, $this->statusCode);
                    }

                    return false;
                }

                return true;

            case 'application/pdf':
                $header = file_get_contents($file->getPathname(), false, null, 0, 5);

                if (strpos($header, '%PDF-') !== 0) {
                    $hasError = true;

                    $this->errorMessage = 'Something went wrong, please try again later.';
                    $this->statusCode = 500;

                    Log::error('Invalid PDF header. Line: ' . __LINE__ . ' File: ' . $file->getClientOriginalName());
                }

                return true;

            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $zip = new \ZipArchive;

                if ($zip->open($file->getPathname()) === true) {
                    $isValid = $zip->locateName('[Content_Types].xml') !== false;
                    $zip->close();

                    if (!$isValid) {
                        $hasError = true;

                        $this->errorMessage = 'Something went wrong, please try again later.';
                        $this->statusCode = 500;

                        Log::error('Invalid DOCX/XLSX structure. Line: ' . __LINE__ . ' File: ' . $file->getClientOriginalName());
                    }

                    return true;
                }

                $hasError = true;

                $this->errorMessage = 'Something went wrong, please try again later.';
                $this->statusCode = 500;

                Log::error('Not a valid ZIP-based document. Line: ' . __LINE__ . ' File: ' . $file->getClientOriginalName());

            default:
                $hasError = true;

                $this->errorMessage = 'Something went wrong, please try again later.';
                $this->statusCode = 500;

                Log::error('Unsupported file type: ' . $mime . ' | Line: ' . __LINE__ . ' File: ' . $file->getClientOriginalName());
        }

        if ($hasError) {
            if ($this->throwError) {
                throw new Exception($this->errorMessage, $this->statusCode);
            } else {
                return false;
            }
        }

        return true;
    }
}
