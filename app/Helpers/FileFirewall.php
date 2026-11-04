<?php

namespace App\Helpers;

class FileFirewall
{

    public static function isAllowedType($file)
    {
        $allowed_ext = ["pdf", "doc", "docx", "csv", "rft", "txt", "ppt", "pptx", "xls", "xlsx", "jpg", "jpeg", "png", "bmp", "gif", "svg"];
        $allowed_mimes = [
            "application/pdf",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/excel",
            "application/x-excel",
            "application/x-msexcel",
            "application/msword",
            "application/doc",
            "application/ms-doc",
            "text/csv",
            "application/vnd.ms-powerpoint",
            "application/rtf",
            "application/vnd.ms-excel",
            "image/jpeg",
            "image/pjpeg",
            "image/jpg",
            "image/gif",
            "image/png",
            "image/bmp",
            "image/svg",
            "image/svg+xml"
        ];

        $realMime = null;
        if (function_exists('finfo_open') && is_uploaded_file($file->getPathname())) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $realMime = finfo_file($finfo, $file->getPathname()) ?: null;
                finfo_close($finfo);
            }
        }

        $declaredMime = strtolower($file->getClientMimeType());

        // Prefer server-detected MIME; fall back to the client MIME if finfo is unavailable.
        $mimeToCheck = strtolower($realMime ?: $declaredMime);

        // check if file type is allowed
        if (!in_array($mimeToCheck, $allowed_mimes, true)) {
            return false;
        }

        if ($realMime && !in_array(strtolower($realMime), $allowed_mimes, true)) {
            return false;
        }

        // check if file extension is allowed
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowed_ext)) {
            return false;
        }

        if (\App\Modules\Security\Services\SecurityConfigService::banSvgUploads()) {
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = strtolower($realMime ?: $declaredMime);
            if ($ext === 'svg' || in_array($mime, ['image/svg', 'image/svg+xml'], true)) {
                return false;
            }
        }

        return true;
    }

    public static function logBadFileException($file)
    {
        return redirect(url()->previous())->with("error", "The file " . $file->getClientOriginalName() . " is not allowed.");
    }
}
