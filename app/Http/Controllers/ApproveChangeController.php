<?php

namespace App\Http\Controllers;

use App\Helpers\EmailNotification;
use App\Models\ModelChange;
use Illuminate\Http\Request;

class ApproveChangeController extends Controller
{
    public function approve(Request $request)
    {
        $this->validate($request, [
            'change_ids' => 'required'
        ],[
            "change_ids.required" => "Change is not selected, please choose a change for approval"
        ]);

        if ($request->get("change_ids")){

            $changes = ModelChange::whereIn("id", $request->get("change_ids"))->get();

            $maker = [];
            foreach ($changes as $change){

                $model = app($change->model);

                // get record
                $row = $model->find ($change->row_id);

                $updates = [];
                foreach ($change->details as $detail){
                    $updates[$detail->key] = $detail->new_value;
                }

                if ($updates){
                    $row->update($updates);
                }

                $change->status = "Approved";
                $change->save();

                if (!in_array($change->maker_id, $maker)){
                    $maker[$change->maker_id][] = $change;
                }
            }
        }

        // notify maker about the about
        EmailNotification::notifyMaker($maker, "Approved");

        return redirect()->back()->with("success", "Changes has been approved");
    }

    public function reject(Request $request)
    {
        $this->validate($request, [
            'change_ids' => 'required'
        ],[
            "change_ids.required" => "Change is not selected, please choose a change for rejection"
        ]);

        if ($request->get("change_ids")){

            $changes = ModelChange::whereIn("id", $request->get("change_ids"))->get();

            $maker = [];
            foreach ($changes as $change){

                $change->status = "Rejected";
                $change->reject_reason = $request->get("reject_text");
                $change->save();

                if (!in_array($change->maker_id, $maker)){
                    $maker[$change->maker_id][] = $change;
                }
            }
        }

        // notify maker about the about
        EmailNotification::notifyMaker($maker, "Rejected");

        return redirect(url()->previous())->with("success", "Changes has been rejected successfully");
    }
}
