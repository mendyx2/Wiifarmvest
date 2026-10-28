<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\WithdrawMethod;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Withdrawals → Withdrawal Methods (Pages/Admin/Withdraw/Methods.tsx and the
 * shared Pages/Admin/Methods/Form.tsx): how members can be paid, with the
 * limits, charges and the form they fill in.
 */
class WithdrawMethodController extends Controller
{
    public function methods()
    {
        $pageTitle = 'Withdrawal Methods';

        return Inertia::render('Admin/Withdraw/Methods', [
            'methods' => WithdrawMethod::orderBy('name')->orderBy('id')->get()->map(fn (WithdrawMethod $m) => [
                'id'       => $m->id,
                'name'     => (string) __($m->name),
                'image'    => getImage(getFilePath('withdrawMethod') . '/' . $m->image, getFileSize('withdrawMethod')),
                'currency' => (string) $m->currency,
                'limits'   => showAmount($m->min_limit) . ' – ' . showAmount($m->max_limit),
                'charge'   => showAmount($m->fixed_charge) . ' + ' . getAmount($m->percent_charge) . '%',
                'active'   => (int) $m->status === Status::ENABLE,
                'url'      => route('admin.withdraw.method.edit', $m->id),
            ]),
            'urls' => [
                'create' => route('admin.withdraw.method.create'),
                'status' => route('admin.withdraw.method.status', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function create()
    {
        return $this->formPage('New Withdrawal Method', null);
    }

    public function edit($id)
    {
        return $this->formPage('Update Withdrawal Method', WithdrawMethod::with('form')->findOrFail($id));
    }

    private function formPage(string $pageTitle, ?WithdrawMethod $method)
    {
        return Inertia::render('Admin/Methods/Form', [
            'kind'   => 'withdraw',
            'title'  => $pageTitle,
            'method' => [
                'name'           => (string) ($method->name ?? ''),
                'currency'       => (string) ($method->currency ?? ''),
                'rate'           => $method ? (string) getAmount($method->rate) : '',
                'min_limit'      => $method ? (string) getAmount($method->min_limit) : '',
                'max_limit'      => $method ? (string) getAmount($method->max_limit) : '',
                'fixed_charge'   => $method ? (string) getAmount($method->fixed_charge) : '',
                'percent_charge' => $method ? (string) getAmount($method->percent_charge) : '',
                'instruction'    => (string) ($method->description ?? ''),
            ],
            'image'      => $method ? getImage(getFilePath('withdrawMethod') . '/' . $method->image, getFileSize('withdrawMethod')) : '',
            'fields'     => FormProcessor::builderFields($method?->form),
            'currency'   => gs('cur_text'),
            'extensions' => (new FormProcessor())->supportedExt(),
            'urls'       => [
                'tabs'   => null,
                'submit' => $method ? route('admin.withdraw.method.update', $method->id) : route('admin.withdraw.method.store'),
                'back'   => route('admin.withdraw.method.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function rules(bool $isUpdate): array
    {
        return [
            'name'           => 'required|string|max:40',
            'rate'           => 'required|numeric|gt:0',
            'currency'       => 'required|string|max:40',
            'fixed_charge'   => 'required|numeric|gte:0',
            'percent_charge' => 'required|numeric|between:0,100',
            'min_limit'      => 'required|numeric|gt:fixed_charge',
            'max_limit'      => 'required|numeric|gt:min_limit',
            'image'          => [$isUpdate ? 'nullable' : 'required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'instruction'    => 'required|string',
        ];
    }

    public function store(Request $request)
    {
        $formProcessor = new FormProcessor();
        $generator     = $formProcessor->generatorValidation();
        $request->validate(array_merge($this->rules(false), $generator['rules']), $generator['messages']);

        try {
            $filename = fileUploader($request->image, getFilePath('withdrawMethod'));
        } catch (\Exception $e) {
            report($e);
            $notify[] = ['error', 'Image could not be uploaded'];
            return back()->withNotify($notify);
        }

        $method = DB::transaction(function () use ($request, $formProcessor, $filename) {
            $generate      = $formProcessor->generate('withdraw_method');
            $method         = new WithdrawMethod();
            $method->image  = $filename;
            // On, like a new manual gateway (it was left to the column default)
            $method->status = Status::ENABLE;
            $this->fill($method, $request, $generate);
            return $method;
        });

        $notify[] = ['success', 'Withdraw method added successfully'];
        return to_route('admin.withdraw.method.edit', $method->id)->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $formProcessor = new FormProcessor();
        $generator     = $formProcessor->generatorValidation();
        $request->validate(array_merge($this->rules(true), $generator['rules']), $generator['messages']);

        $method = WithdrawMethod::findOrFail($id);
        if ($request->hasFile('image')) {
            try {
                $method->image = fileUploader($request->image, getFilePath('withdrawMethod'), old: $method->image);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Image could not be uploaded'];
                return back()->withNotify($notify);
            }
        }

        DB::transaction(fn () => $this->fill($method, $request, $formProcessor->generate('withdraw_method', true, 'id', $method->form_id)));

        $notify[] = ['success', 'Withdraw method updated successfully'];
        return back()->withNotify($notify);
    }

    private function fill(WithdrawMethod $method, Request $request, $form): void
    {
        $method->form_id        = @$form->id ?? 0;
        $method->name           = $request->name;
        $method->rate           = $request->rate;
        $method->min_limit      = $request->min_limit;
        $method->max_limit      = $request->max_limit;
        $method->fixed_charge   = $request->fixed_charge;
        $method->percent_charge = $request->percent_charge;
        $method->currency       = $request->currency;
        $method->description    = $request->instruction;
        $method->save();
    }

    public function status($id)
    {
        return WithdrawMethod::changeStatus($id);
    }
}
