<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\WebhookIpWhitelist;

class WebhookIpWhitelistController extends Controller
{
    public function index()
    {
        $webhookIpWhitelists = WebhookIpWhitelist::all();
        
        return view('settings.webhook-ip-whitelists.list', compact('webhookIpWhitelists'));
    }

    public function add()
    {
        return view('settings.webhook-ip-whitelists.new');
    }

    public function store(Request $request)
    {
        $request->validate([
            'service' => 'required|string|max:50',
            'ip_address' => 'required|ip',
            'whitelisted_for' => 'required|string|max:100',
            'description' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $existingEntry = WebhookIpWhitelist::where('service', $request->service)
                ->where('ip_address', $request->ip_address)
                ->first();

            if ($existingEntry) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->withErrors(['ip_address' => 'This IP address is already whitelisted for the specified service.']);
            }

            $whitelist = new WebhookIpWhitelist();
    
            $whitelist->service = $request->service;
            $whitelist->ip_address = $request->ip_address;
            $whitelist->whitelisted_for = $request->whitelisted_for;
            $whitelist->description = $request->description;
            $whitelist->is_active = $request->has('is_active') ? true : false;
            $whitelist->created_by = auth()->id();
    
            $whitelist->save();

            DB::commit();
            
            return redirect("/settings/webhook-ip-whitelists")->with("success", "Webhook IP Whitelist entry created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error occurred while saving webhook IP whitelist entry: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }

    }

    public function edit($id)
    {
        $webhookIpWhitelist = WebhookIpWhitelist::findOrFail($id);

        return view('settings.webhook-ip-whitelists.edit', compact('webhookIpWhitelist'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:webhook_ip_whitelists,id',
            'service' => 'required|string|max:50',
            'ip_address' => 'required|ip',
            'whitelisted_for' => 'required|string|max:100',
            'description' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $existingEntry = WebhookIpWhitelist::where('service', $request->service)
                ->where('ip_address', $request->ip_address)
                ->where('id', '!=', $request->id)
                ->first();

            if ($existingEntry) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['ip_address' => 'This IP address is already whitelisted for the specified service.']);
            }


            $webhookIpWhitelist = WebhookIpWhitelist::findOrFail($request->id);
        
            $webhookIpWhitelist->service = $request->service;
            $webhookIpWhitelist->ip_address = $request->ip_address;
            $webhookIpWhitelist->whitelisted_for = $request->whitelisted_for;
            $webhookIpWhitelist->description = $request->description;
            $webhookIpWhitelist->is_active = $request->has('is_active') ? true : false;
            $webhookIpWhitelist->updated_by = auth()->id();

            $webhookIpWhitelist->save();

            DB::commit();

            return redirect("/settings/webhook-ip-whitelists")->with("success", "Webhook IP Whitelist entry updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error occurred while updating webhook IP whitelist entry: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:webhook_ip_whitelists,id',
        ]);

        DB::beginTransaction();

        try {
            $webhookIpWhitelist = WebhookIpWhitelist::findOrFail($request->id);
            $webhookIpWhitelist->delete();

            DB::commit();

            return redirect("/settings/webhook-ip-whitelists")->with("success", "Webhook IP Whitelist entry deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error occurred while deleting webhook IP whitelist entry: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
