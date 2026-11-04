<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use OSS\OssClient;

class DatabaseBackUp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will create full db backup, zip it and will store it in s3';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            Log::info('Backup Started');

            $filename = "backup-" . date('Y-m-d-h-i-s') . ".sql";

            if (!Storage::exists("backups")) {
                Storage::makeDirectory("backups");
            }

            $regex = "/[][$#^{}!@%&*()]/i";
            $password = env("DB_PASSWORD");
            $passwordArray = str_split($password);

            foreach ($passwordArray as $key => $value) {
                if (preg_match($regex, $value)) {
                    $passwordArray[$key] = '\\' . $value;
                    // die($passwordArray[$key]);
                }
            }

            $password = implode("", $passwordArray);

            $path = storage_path() . "/app/backups/" . $filename;

            // Log::info('Path: ' . $path);

            $command = "mariadb-dump --ssl=0 -u" . env("DB_USERNAME") . " -p" . $password . " -h " . env("DB_HOST") . " " . env("DB_DATABASE") . " > " . $path;

            exec($command, $output, $returnVar);

            $returnVar = NULL;
            $output  = NULL;
            $contents = file_get_contents($path);


            switch (env("BUCKET_PROVIDER")) {
                case 'S3':

                    // Log::info('Storage Provider: S3');

                    $s3 = Storage::disk('s3');
                    $s3->put('/backups/' . $filename, $contents);
                    break;
                case 'OSS':

                    // Log::info('Storage Provider: OSS');

                    $accessKeyId = env("OSS_ACCESS_KEY_ID");
                    $accessKeySecret = env("OSS_ACCESS_KEY_SECRET");
                    $endpoint = env("OSS_ENDPOINT");
                    $bucket = env("OSS_BACKUPS_BUCKET");

                    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                    $result = $ossClient->putObject($bucket, 'backups/' . $filename, $contents);

                    // Log::info('Bucket: ' . $bucket . ' | URL: ' . $result['oss-request-url']);
                    break;
                default:
                    break;
            }

            // delete local backup file
            Storage::deleteDirectory("backups");

            // Log::info('backups directory deleted successfully!');

            print("backup done...");
            Log::info('Backup Done');

            // keep only last 3 backups and delete the rest
            self::deleteExtraBackups();
        } catch (\Exception $e) {
            print('Exception Thrown:');
            print($e->getMessage());
            Log::error('Exception thrown: ' . $e->getMessage());
        }
    }

    protected function deleteExtraBackups()
    {
        // Log::info('Deleting Extra Backup files.');

        print("deleting extra backups...");
        switch (env("BUCKET_PROVIDER")) {
            case 'S3':
                $files = Storage::disk('s3')->allFiles('backups');
                break;
            case 'OSS':
                $accessKeyId = env("OSS_ACCESS_KEY_ID");
                $accessKeySecret = env("OSS_ACCESS_KEY_SECRET");
                $endpoint = env("OSS_ENDPOINT");
                $bucket = env("OSS_BACKUPS_BUCKET");

                $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
                $files = $ossClient->listObjects($bucket, ['prefix' => 'backups/'])->getObjectList();
                break;
            default:
                break;
        }

        rsort($files);
        $total_files = count($files);

        for ($i = 24; $i < $total_files; $i++) {
            if ($files[$i] != "") {

                switch (env("BUCKET_PROVIDER")) {
                    case 'S3':
                        Storage::disk("s3")->delete($files[$i]);
                        break;
                    case 'OSS':

                        # if first character is "/" then remove it, oss doesn't support object path starting with "/"
                        # if ($files[$i][0] == "/") {
                        #     $files[$i] = substr($files[$i], 1, strlen($files[$i]));
                        # }

                        $ossClient->deleteObject($bucket, $files[$i]->getKey());
                        break;
                    default:
                        break;
                }
            }
        }

        // Log::info('File deletion completed!');
    }
}
