<?php

namespace App\Helpers;

use App\Models\Client;
use App\Models\Country;
use App\Models\ModelChange;
use App\Models\ModelChangeDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ChangeLogger
{
    private static function restricted()
    {
        return [
            "id",
            "created_at",
            "updated_at",
            "rejected_at",
            "approved_at",
            "updated_by",
            "rejected_by",
            "approved_by",
            "remember_token"
        ];
    }

    public static function log($model, $title, $url)
    {
        try {
            $original = $model->getOriginal();
            $input = $model->getAttributes();

            // check if there is a change in model
            if (!self::isChanged($model)) {
                return false;
            }

            // create details
            foreach ($original as $key => $value) {

                // if ($key == 'service_fee_client_account_id') {
                //     Log::info('Processing service_fee_client_account_id ' . __LINE__);
                // }
                // Log::info('Processing ' . $key . ' | ' . __LINE__);

                if (!in_array($key, self::restricted())) {
                    // if ($key == 'service_fee_client_account_id') {
                    //     Log::info('Processing service_fee_client_account_id ' . __LINE__);
                    // }
                    if ($value != $input[$key]) {
                        // if ($key == 'service_fee_client_account_id') {
                        //     Log::info('Processing service_fee_client_account_id ' . __LINE__);
                        // }

                        // special check for date check, without time
                        if (in_array($key, ["transaction_date"])) {

                            if (self::checkDateEquality($value, $input[$key])) {
                                continue;
                            }
                        }

                        // create meta data
                        $change = new ModelChange();

                        $change->model = get_class($model);
                        $change->title = $title;
                        $change->url = $url;
                        $change->row_id = $model->id;
                        //if(!empty(Auth::user()))
                        $change->maker_id = Auth::user()->id;

                        $change->checker_id = self::getCheckerId();
                        $change->status = "Pending";

                        // $change->save();
                        if ($change->save()) {
                            // if ($key == 'service_fee_client_account_id') {
                            //     Log::info('Model Change saved for service_fee_client_account_id ' . __LINE__);
                            // }
                            // Log::info('Model Change saved for ' . $key . ' | ' . __LINE__);
                        }

                        // create change details
                        $details = new ModelChangeDetail();

                        $details->change_id = $change->id;
                        $details->key = $key;
                        $details->old_value = $value;
                        $details->new_value = $input[$key];

                        // $details->save();
                        if ($details->save()) {
                            // if ($key == 'service_fee_client_account_id') {
                            //     Log::info('Model Change Details saved for service_fee_client_account_id ' . __LINE__);
                            // }
                            // Log::info('Model Change Details saved for ' . $key . ' | ' . __LINE__);
                        }
                    }
                }
            }

            // Notify change
            EmailNotification::notifyModelChange($change);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception Logged at ' . __METHOD__ . ' | Line: ' . __LINE__);
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            return false;
        }
    }

    private static function isChanged($model)
    {
        $original = $model->getOriginal();
        $input = $model->getAttributes();

        foreach ($original as $key => $value) {
            if (!in_array($key, self::restricted())) {
                if ($value != $input[$key]) {

                    // special check for date, without time
                    if (in_array($key, ["transaction_date"])) {
                        $result = self::checkDateEquality($value, $input[$key]);
                        if ($result) {
                            continue;
                        }
                    }

                    return true;
                }
            }
        }

        return false;
    }

    public static function checkDateEquality($old_date, $new_date)
    {
        $old = Carbon::createFromFormat("Y-m-d H:i:s", $old_date);
        $new = Carbon::createFromFormat("Y-m-d H:i:s", (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $new_date) === 1) ? $new_date  : $new_date . " 00:00:00");

        return $old->eq($new);
    }

    public static function get($model)
    {
        if ($model) {
            $changes = ModelChange::where("model", get_class($model))
                ->where("row_id", $model->id)
                ->orderBy("created_at", "DESC")
                ->get();
            return ($changes->count() > 0) ? $changes : null;
        } else {
            return null;
        }
    }

    public static function deleteChanges($model)
    {
        $modelChangeIds = ModelChange::select('id')
            ->where("model", get_class($model))
            ->where("row_id", $model->id)
            ->pluck('id')
            ->toArray();

        DB::transaction(function () use ($modelChangeIds) {
            ModelChangeDetail::whereIn('change_id', $modelChangeIds)->delete();
            ModelChange::whereIn('id', $modelChangeIds)->delete();
        });
    }

    public static function getUsingCreatedAt($model)
    {
        if ($model) {
            $datetime = Carbon::parse(ModelChange::select('created_at')
                ->where("model", get_class($model))
                ->where("row_id", $model->id)
                ->orderBy("created_at", "DESC")
                ->first()
                ->created_at)->format('Y-m-d H:i');

            $changes = ModelChange::where("model", get_class($model))
                ->where("row_id", $model->id)
                ->whereRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:%i") = ?', [$datetime])
                ->orderBy("created_at", "DESC")
                ->get();

            return ($changes->count() > 0) ? $changes : null;
        } else {
            return null;
        }
    }



    public static function transform($detail)
    {
        switch ($detail['key']) {

            case 'nationality_id':
                $detail['old_value'] = self::getCountryNameById($detail['old_value']);
                $detail['new_value'] = self::getCountryNameById($detail['new_value']);
                break;
            case 'profile_photo_url':
                if ($detail['old_value']) {
                    $detail['old_value'] = self::getURL($detail['old_value']);
                }
                if ($detail['new_value']) {
                    $detail['new_value'] = self::getURL($detail['new_value']);
                }
                break;
            case 'transaction_date':
                $detail['old_value'] = Carbon::parse($detail['old_value'])->format("d/m/Y");
                $detail['new_value'] = Carbon::parse($detail['new_value'])->format("d/m/Y");
                break;
            case 'currency_id':
                $detail['old_value'] = Common::getCurrencyShort($detail['old_value']);
                $detail['new_value'] = Common::getCurrencyShort($detail['new_value']);
                break;
            case 'account_id':
                $detail['old_value'] = Common::getAccountNumber($detail['old_value']);
                $detail['new_value'] = Common::getAccountNumber($detail['new_value']);
                break;
        }

        $detail['key'] = ucwords(str_replace("_", " ", $detail['key']));
        return $detail;
    }

    private static function getCountryNameById($id)
    {
        $country = Country::find($id);
        return $country->nicename;
    }

    private static function getURL($url)
    {
        return "<a href='" . $url . "' target='_blank'>View File</a>";
    }

    private static function getCheckerId()
    {
        if (Auth::user()->manager_id > 0) {
            return Auth::user()->manager_id;
        }

        if (Auth::user()->hasRole("client")) {
            $manager = User::role('operations manager')->first();
            if ($manager) {
                return $manager->id;
            }
        }
    }
}
