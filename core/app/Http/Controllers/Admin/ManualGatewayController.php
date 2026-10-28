<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Models\Gateway;
use App\Models\GatewayCurrency;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Payment Gateways → Manual (Pages/Admin/Gateways/Manual.tsx and the shared
 * Pages/Admin/Methods/Form.tsx): bank transfer and other methods an admin
 * confirms by hand, each with its own form for the member to fill in.
 */
class ManualGatewayController extends Controller
{
    public function index()
    {
        $pageTitle = 'Manual Gateways';

        return Inertia::render('Admin/Gateways/Manual', [
            'gateways' => Gateway::manual()->with('singleCurrency')->orderBy('id', 'desc')->get()->map(fn (Gateway $g) => [
                'id'       => $g->id,
                'name'     => (string) __($g->name),
                'image'    => getImage(getFilePath('gateway') . '/' . $g->image, getFileSize('gateway')),
                'currency' => (string) ($g->singleCurrency->currency ?? ''),
                'limits'   => $g->singleCurrency ? showAmount($g->singleCurrency->min_amount) . ' – ' . showAmount($g->singleCurrency->max_amount) : '—',
                'active'   => (int) $g->status === Status::ENABLE,
                'url'      => route('admin.gateway.manual.edit', $g->alias),
            ]),
            'urls' => [
                'tabs'   => ['automatic' => route('admin.gateway.automatic.index'), 'manual' => route('admin.gateway.manual.index')],
                'create' => route('admin.gateway.manual.create'),
                'status' => route('admin.gateway.manual.status', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function create()
    {
        return $this->formPage('Add Manual Gateway', null);
    }

    public function edit($alias)
    {
        $method = Gateway::manual()->with('singleCurrency', 'form')->where('alias', $alias)->firstOrFail();
        return $this->formPage('Edit Manual Gateway', $method);
    }

    private function formPage(string $pageTitle, ?Gateway $method)
    {
        $c = $method?->singleCurrency;

        return Inertia::render('Admin/Methods/Form', [
            'kind'   => 'gateway',
            'title'  => $pageTitle,
            'method' => [
                'name'           => (string) ($method->name ?? ''),
                'currency'       => (string) ($c->currency ?? ''),
                'rate'           => $c ? (string) getAmount($c->rate) : '',
                'min_limit'      => $c ? (string) getAmount($c->min_amount) : '',
                'max_limit'      => $c ? (string) getAmount($c->max_amount) : '',
                'fixed_charge'   => $c ? (string) getAmount($c->fixed_charge) : '',
                'percent_charge' => $c ? (string) getAmount($c->percent_charge) : '',
                'instruction'    => (string) ($method->description ?? ''),
            ],
            'image'      => $method ? getImage(getFilePath('gateway') . '/' . $method->image, getFileSize('gateway')) : '',
            'fields'     => FormProcessor::builderFields($method?->form),
            'currency'   => gs('cur_text'),
            'extensions' => (new FormProcessor())->supportedExt(),
            'urls'       => [
                'tabs'   => ['automatic' => route('admin.gateway.automatic.index'), 'manual' => route('admin.gateway.manual.index')],
                'submit' => $method ? route('admin.gateway.manual.update', $method->code) : route('admin.gateway.manual.store'),
                'back'   => route('admin.gateway.manual.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request)
    {
        $formProcessor = new FormProcessor();
        $this->validation($request, $formProcessor);

        $alias = $this->alias($request->name);
        if (Gateway::where('alias', $alias)->exists()) {
            return back()->withErrors(['name' => 'A gateway with this name already exists.']);
        }

        $filename = null;
        if ($request->hasFile('image')) {
            try {
                $filename = fileUploader($request->image, getFilePath('gateway'));
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Image could not be uploaded'];
                return back()->withNotify($notify);
            }
        }

        $method = DB::transaction(function () use ($request, $formProcessor, $alias, $filename) {
            $generate   = $formProcessor->generate('manual_deposit');
            $methodCode = max(1000, (int) Gateway::manual()->lockForUpdate()->max('code') + 1);

            $method                       = new Gateway();
            $method->code                 = $methodCode;
            $method->form_id              = @$generate->id ?? 0;
            $method->name                 = $request->name;
            $method->image                = $filename;
            $method->alias                = $alias;
            $method->status               = Status::ENABLE;
            $method->gateway_parameters   = json_encode([]);
            $method->supported_currencies = [];
            $method->crypto               = Status::DISABLE;
            $method->description          = $request->instruction;
            $method->save();

            $this->saveCurrency(new GatewayCurrency(), $method, $request);

            return $method;
        });

        $notify[] = ['success', $method->name . ' Manual gateway has been added.'];
        return to_route('admin.gateway.manual.edit', $method->alias)->withNotify($notify);
    }

    public function update(Request $request, $code)
    {
        $formProcessor = new FormProcessor();
        $this->validation($request, $formProcessor, true);

        $method = Gateway::manual()->where('code', $code)->firstOrFail();
        $alias  = $this->alias($request->name);
        if (Gateway::where('alias', $alias)->where('id', '!=', $method->id)->exists()) {
            return back()->withErrors(['name' => 'Another gateway already has this name.']);
        }

        $filename = $method->image;
        if ($request->hasFile('image')) {
            try {
                $filename = fileUploader($request->image, getFilePath('gateway'), old: $filename);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Image could not be uploaded'];
                return back()->withNotify($notify);
            }
        }

        DB::transaction(function () use ($request, $formProcessor, $method, $alias, $filename) {
            $generate            = $formProcessor->generate('manual_deposit', true, 'id', $method->form_id);
            $method->name        = $request->name;
            $method->image       = $filename;
            $method->alias       = $alias;
            $method->description = $request->instruction;
            $method->form_id     = @$generate->id ?? 0;
            $method->save();

            // A gateway without its currency row could not be used; one is created if missing
            $this->saveCurrency($method->singleCurrency ?? new GatewayCurrency(), $method, $request);
        });

        $notify[] = ['success', $method->name . ' manual gateway updated successfully'];
        return to_route('admin.gateway.manual.edit', [$method->alias])->withNotify($notify);
    }

    private function saveCurrency(GatewayCurrency $currency, Gateway $method, Request $request): void
    {
        $currency->name           = $request->name;
        $currency->gateway_alias  = $method->alias;
        $currency->currency       = $request->currency;
        $currency->symbol         = '';
        $currency->method_code    = $method->code;
        $currency->min_amount     = $request->min_limit;
        $currency->max_amount     = $request->max_limit;
        $currency->fixed_charge   = $request->fixed_charge;
        $currency->percent_charge = $request->percent_charge;
        $currency->rate           = $request->rate;
        $currency->save();
    }

    /** "Bank Transfer (GTB)" → "bank_transfer_gtb": the alias is used in URLs. */
    private function alias(string $name): string
    {
        return Str::slug($name, '_') ?: 'manual_' . Str::lower(Str::random(6));
    }

    private function validation($request, $formProcessor, $isUpdate = false)
    {
        $validation = [
            'name'           => 'required|string|max:40',
            'rate'           => 'required|numeric|gt:0',
            'currency'       => 'required|string|max:40',
            'min_limit'      => 'required|numeric|gt:0',
            'max_limit'      => 'required|numeric|gt:min_limit',
            'fixed_charge'   => 'required|numeric|gte:0',
            'percent_charge' => 'required|numeric|between:0,100',
            'image'          => [$isUpdate ? 'nullable' : 'required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'instruction'    => 'required|string',
        ];

        $generatorValidation = $formProcessor->generatorValidation();
        $request->validate(array_merge($validation, $generatorValidation['rules']), $generatorValidation['messages']);
    }

    public function status($id)
    {
        Gateway::manual()->findOrFail($id);
        return Gateway::changeStatus($id);
    }
}
