<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OwnCompany;
use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Rules\SupportingDocumentsRule;

class OwnCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $company = OwnCompany::first();

        if ($company) {
            return view("settings.own-company.view", compact('company'));
        }

        return view("settings.own-company.new");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'chairperson_email' => 'required',
            'poc_name' => 'required',
            'poc_email' => 'required',
            'support_team_emails' => 'required',
            'country' => 'required',
            'address' => 'required',
            'license_number' => 'required',
            'contact_number' => 'required',
            'max_file_size' => 'required|integer',
            'tech_email' => 'required',
            'app_store_url' => 'required',
            'play_store_url' => 'required',
            'logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'client_portal_light_logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'client_portal_dark_logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'client_manual' => [new SupportingDocumentsRule, 'mimes:pdf'],
            'favicon' => [new SupportingDocumentsRule, 'mimes:png,svg'],
            'stamp' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'watermark' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'login_page_image' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg,gif'],
            'theme' => 'nullable|string',
            'card_colors' => 'nullable|json',
            'chart_color' => 'nullable|string'
        ]);

        $company = new OwnCompany();

        $company->name = $request->get('name');
        $company->short_name = $request->get('short_name');
        $company->chairperson_email = $request->get('chairperson_email');
        $company->poc_name = $request->get('poc_name');
        $company->poc_email = $request->get('poc_email');
        $company->support_team_emails = $request->get('support_team_emails');
        $company->country = $request->get('country');
        $company->address = $request->get('address');
        $company->license_number = $request->get('license_number');
        $company->contact_number = $request->get('contact_number');
        $company->account_number_prefix = $request->get('account_number_prefix');
        $company->max_file_size = $request->get('max_file_size');
        $company->tech_email = $request->get('tech_email');
        $company->app_store_url = $request->get('app_store_url');
        $company->play_store_url = $request->get('play_store_url');
        $company->find_us = $request->get('find_us');
        $company->theme = $request->get('theme');
        $company->card_colors = $request->get('card_colors');
        $company->chart_color = $request->get('chart_color');
        $company->maintenance_fee_job_enabled = $request->get('maintenance_fee_job_enabled') ? true : false;

        if ($request->hasFile('logo')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('logo'))) {
                return FileFirewall::logBadFileException($request->file('logo'));
            }

            $company->logo = S3Helper::fileUpload($request->file('logo'), 'company-logo');
        }

        if ($request->hasFile('client_portal_light_logo')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('client_portal_light_logo'))) {
                return FileFirewall::logBadFileException($request->file('client_portal_light_logo'));
            }

            $company->client_portal_light_logo = S3Helper::fileUpload($request->file('client_portal_light_logo'), 'company-client_portal_light_logo');
        }

        if ($request->hasFile('client_portal_dark_logo')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('client_portal_dark_logo'))) {
                return FileFirewall::logBadFileException($request->file('client_portal_dark_logo'));
            }

            $company->client_portal_dark_logo = S3Helper::fileUpload($request->file('client_portal_dark_logo'), 'company-client_portal_dark_logo');
        }

        if ($request->hasFile('client_manual')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('client_manual'))) {
                return FileFirewall::logBadFileException($request->file('client_manual'));
            }

            $company->client_manual = S3Helper::fileUpload($request->file('client_manual'), 'company-client_manual');
        }

        if ($request->hasFile('favicon')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('favicon'))) {
                return FileFirewall::logBadFileException($request->file('favicon'));
            }

            $company->favicon = S3Helper::fileUpload($request->file('favicon'), 'company-favicon');
        }

        if ($request->hasFile('stamp')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('stamp'))) {
                return FileFirewall::logBadFileException($request->file('stamp'));
            }

            $company->stamp = S3Helper::fileUpload($request->file('stamp'), 'company-stamp');
        }

        if ($request->hasFile('watermark')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('watermark'))) {
                return FileFirewall::logBadFileException($request->file('watermark'));
            }

            $company->watermark = S3Helper::fileUpload($request->file('watermark'), 'company-watermark');
        }

        if ($request->hasFile('login_page_image')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('login_page_image'))) {
                return FileFirewall::logBadFileException($request->file('login_page_image'));
            }

            $company->login_page_image = S3Helper::fileUpload($request->file('login_page_image'), 'company-login_page_image');
        }

        $company->save();

        return redirect('/settings/own-company-details')->with('success', 'Company updated successfully');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $company = OwnCompany::find($id);

        return view("settings.own-company.edit", compact('company'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'chairperson_email' => 'required',
            'poc_name' => 'required',
            'poc_email' => 'required',
            'support_team_emails' => 'required',
            'country' => 'required',
            'address' => 'required',
            'license_number' => 'required',
            'contact_number' => 'required',
            'max_file_size' => 'required|integer',
            'tech_email' => 'required',
            'app_store_url' => 'required',
            'play_store_url' => 'required',
            'terms_and_conditions_url' => 'required',
            'logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'client_portal_light_logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'client_portal_dark_logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'client_manual' => [new SupportingDocumentsRule, 'mimes:pdf'],
            'favicon' => [new SupportingDocumentsRule, 'mimes:png,svg'],
            'stamp' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'watermark' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
            'login_page_image' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg,gif'],
            'theme' => 'nullable|string',
            'card_colors' => 'nullable|json',
            'chart_color' => 'nullable|string'
        ]);

        $company = OwnCompany::find($request->get('id'));

        if ($company) {
            $company->name = $request->get('name');
            // $company->short_name = $request->get('short_name');
            $company->chairperson_email = $request->get('chairperson_email');
            $company->poc_name = $request->get('poc_name');
            $company->poc_email = $request->get('poc_email');
            $company->support_team_emails = $request->get('support_team_emails');
            $company->country = $request->get('country');
            $company->address = $request->get('address');
            $company->license_number = $request->get('license_number');
            $company->contact_number = $request->get('contact_number');
            $company->account_number_prefix = $request->get('account_number_prefix');
            $company->max_file_size = $request->get('max_file_size');
            $company->tech_email = $request->get('tech_email');
            $company->app_store_url = $request->get('app_store_url');
            $company->play_store_url = $request->get('play_store_url');
            $company->terms_and_conditions_url = $request->get('terms_and_conditions_url');
            $company->find_us = $request->get('find_us');
            $company->theme = $request->get('theme');
            $company->card_colors = $request->get('card_colors');
            $company->chart_color = $request->get('chart_color');
            $company->show_introducer_field = $request->get('show_introducer_field') ? true : false;
            $company->maintenance_fee_job_enabled = $request->get('maintenance_fee_job_enabled') ? true : false;

            if ($request->hasFile('logo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('logo'))) {
                    return FileFirewall::logBadFileException($request->file('logo'));
                }

                if ($company->logo) {
                    S3Helper::fileDelete($company->logo);
                }

                $company->logo = S3Helper::fileUpload($request->file('logo'), 'company-logo');
            }

            if ($request->hasFile('client_portal_light_logo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('client_portal_light_logo'))) {
                    return FileFirewall::logBadFileException($request->file('client_portal_light_logo'));
                }

                if ($company->client_portal_light_logo) {
                    S3Helper::fileDelete($company->client_portal_light_logo);
                }

                $company->client_portal_light_logo = S3Helper::fileUpload($request->file('client_portal_light_logo'), 'company-client_portal_light_logo');
            }

            if ($request->hasFile('client_portal_dark_logo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('client_portal_dark_logo'))) {
                    return FileFirewall::logBadFileException($request->file('client_portal_dark_logo'));
                }

                if ($company->client_portal_dark_logo) {
                    S3Helper::fileDelete($company->client_portal_dark_logo);
                }

                $company->client_portal_dark_logo = S3Helper::fileUpload($request->file('client_portal_dark_logo'), 'company-client_portal_dark_logo');
            }

            if ($request->hasFile('client_manual')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('client_manual'))) {
                    return FileFirewall::logBadFileException($request->file('client_manual'));
                }

                if ($company->client_manual) {
                    S3Helper::fileDelete($company->client_manual);
                }

                $company->client_manual = S3Helper::fileUpload($request->file('client_manual'), 'company-client_manual');
            }

            if ($request->hasFile('favicon')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('favicon'))) {
                    return FileFirewall::logBadFileException($request->file('favicon'));
                }

                if ($company->favicon) {
                    S3Helper::fileDelete($company->favicon);
                }

                $company->favicon = S3Helper::fileUpload($request->file('favicon'), 'company-favicon');
            }

            if ($request->hasFile('stamp')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('stamp'))) {
                    return FileFirewall::logBadFileException($request->file('stamp'));
                }

                if ($company->stamp) {
                    S3Helper::fileDelete($company->stamp);
                }

                $company->stamp = S3Helper::fileUpload($request->file('stamp'), 'company-stamp');
            }

            if ($request->hasFile('watermark')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('watermark'))) {
                    return FileFirewall::logBadFileException($request->file('watermark'));
                }

                if ($company->watermark) {
                    S3Helper::fileDelete($company->watermark);
                }

                $company->watermark = S3Helper::fileUpload($request->file('watermark'), 'company-watermark');
            }

            if ($request->hasFile('login_page_image')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('login_page_image'))) {
                    return FileFirewall::logBadFileException($request->file('login_page_image'));
                }

                if ($company->login_page_image) {
                    S3Helper::fileDelete($company->login_page_image);
                }

                $company->login_page_image = S3Helper::fileUpload($request->file('login_page_image'), 'company-login_page_image');
            }

            $company->save();

            return redirect('/settings/own-company-details')->with('success', 'Company updated successfully');
        }

        return redirect("settings/own-company/edit/" . $request->get('id'))->with('error', 'Error occured, please try again.');
    }
}
