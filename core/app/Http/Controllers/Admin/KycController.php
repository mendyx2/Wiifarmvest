<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Lib\FormProcessor;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Settings → KYC Setting (Pages/Admin/Kyc/Setting.tsx): the form members fill in to be verified. */
class KycController extends Controller
{
    public function setting()
    {
        $pageTitle = 'KYC Setting';

        return Inertia::render('Admin/Kyc/Setting', [
            'fields'     => FormProcessor::builderFields(Form::where('act', 'kyc')->first()),
            'extensions' => (new FormProcessor())->supportedExt(),
            'url'        => route('admin.kyc.setting'),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function settingUpdate(Request $request)
    {
        $formProcessor       = new FormProcessor();
        $generatorValidation = $formProcessor->generatorValidation();
        $request->validate($generatorValidation['rules'], $generatorValidation['messages']);

        $formProcessor->generate('kyc', Form::where('act', 'kyc')->exists(), 'act');

        $notify[] = ['success', 'KYC data updated successfully'];
        return back()->withNotify($notify);
    }
}
