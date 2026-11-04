<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ClientCompanyFile;
use App\Models\ClientIndividualFile;
use App\Models\User;
use App\Helpers\S3Helper;
use Illuminate\Support\Facades\File;

class MoveFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:files-to-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'The command will move all of the client uploaded documents and users profile photos to s3 bucket and will replace the existing local-server-url of the file with s3-url in the associated table column.';

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
        $companyFiles = ClientCompanyFile::all();
        $individualFiles = ClientIndividualFile::all();
        $users = User::all();

        // Company Onboarding files Transfer
        $this->transferFiles($companyFiles, "Company Onboarding", "client-onboarding-files", "client");

        // Individual Onboarding files Transfer
        self::transferFiles($individualFiles, "Individual Onboarding", "client-onboarding-files", "client");

        // User Profile Photos Transfer
        self::transferFiles($users, "Profile Photo", "profile-photos", "user");
    }

    protected function transferFiles($entities, $transferType, $parentFolder, $entityType)
    {
        $haveFailedTransfers = false;
        $failedTransfersArray = [];
        $transferedFiles = 0;

        echo "\nMoving " . $transferType . " files to s3-Bucket\n";
        echo "\n---------------------------------------------------------------\n\n";

        foreach ($entities as $entity) {
            $url = "";

            if ($entityType == "client") {
                $url = $entity->url;
            } else {
                $url = $entity->profile_photo_url;
            }

            if ($url !== null || $url !== "" || "s3.amazonaws.com" !== parse_url($url)["host"]) {
                if (File::exists(public_path(parse_url($url)["path"]))) {

                    if ($entityType == "client") {
                        $entity->previous_url = $url;
                        $entity->url = S3Helper::moveS3($url, $parentFolder);
                    } else {
                        $entity->previous_photo_url = $url;
                        $entity->profile_photo_url = S3Helper::moveS3($url, $parentFolder);
                    }

                    $entity->save();
                    echo "Entity ID: " . $entity->id . " | File URL: " . $url . "\n";
                    $transferedFiles++;
                } else {

                    $haveFailedTransfers = true;
                    array_push($failedTransfersArray, "Entity ID: " . $entity->id . " | File URL: " . $url . "\n");
                }
            } else {

                $haveFailedTransfers = true;
                array_push($failedTransfersArray, "Entity ID: " . $entity->id . " do not have file url\n");
            }
        }

        echo "\n" . $transferedFiles . " " . $transferType . " files have been moved to s3-bucket successfully";
        echo "\n---------------------------------------------------------------\n\n";

        if ($haveFailedTransfers) {

            echo "\nCouldn't move the following " . count($failedTransfersArray) . " " . $transferType . " files to s3-bucket.\n";
            echo "\n\n---------------------------------------------------------------\n";

            foreach ($failedTransfersArray as $failedTransfer) {
                echo $failedTransfer;
            }
        }

        echo "\n---------------------------------------------------------------\n\n";
    }
}
