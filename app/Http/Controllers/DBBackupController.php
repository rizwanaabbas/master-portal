<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use OSS\OssClient;

class DBBackupController extends Controller
{
    public function listBackups()
    {
        switch (env("BUCKET_PROVIDER")) {
            case 'S3':
                $files = Storage::disk('s3')->allFiles('backups');
                rsort($files);
                break;
            case 'OSS':
                $accessKeyId = env("OSS_ACCESS_KEY_ID");
                $accessKeySecret = env("OSS_ACCESS_KEY_SECRET");
                $endpoint = env("OSS_ENDPOINT");
                $bucket = env("OSS_BACKUPS_BUCKET");

                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                $files = $ossClient->listObjects($bucket, ['prefix' => 'backups/'])->getObjectList();
                rsort($files);
                break;
            default:
                $files = [];
                break;
        }

        return view("data-management.db-backup-list", compact('files'));
    }

    public function create()
    {
        Artisan::call('db:backup');
        return redirect("/data-management/backups")->with("success", "Database file created successfully");
    }

    public function restore(Request $request)
    {
        return "operation disabled";
        $file = Storage::disk("s3")->get($request->get("file_name"));

        Storage::disk("local")->put("backups/tmp.sql", $file);

        $path = storage_path() . "/app/backups/tmp.sql";

        // restore database file
        $command = "mysql -u" . env("DB_USERNAME") . " -p" . env("DB_PASSWORD") . " -h " . env("DB_HOST") . " " . env("DB_DATABASE") . " < " . $path;
        exec($command);

        // delete local database file
        Storage::disk("local")->delete("backups/tmp.sql");
        return redirect("/data-management/backups")->with("success", "Database file restored successfully");
    }

    public function delete(Request $request)
    {
        Storage::disk("s3")->delete($request->get("id"));
        return redirect("/data-management/backups")->with("success", "Database file deleted successfully");
    }
}
