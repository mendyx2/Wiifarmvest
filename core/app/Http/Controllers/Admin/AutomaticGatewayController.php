<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Models\GatewayCurrency;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

/**
 * Payment Gateways → Automatic (Pages/Admin/Gateways/Automatic*.tsx): the
 * built-in gateways (Paystack, Flutterwave, ...), their API keys, and the
 * currencies each one accepts with limits, charges and rate.
 *
 * Secret keys are never sent to the browser: their fields are empty with a
 * note, and leaving one empty keeps the saved value.
 */
class AutomaticGatewayController extends Controller
{
    /** Parameter names that hold a secret (never "public_key"). */
    private const SECRET = '/secret|private|password|passphrase|token|hash|salt|signature|api_?key|access_?key|merchant_?key|ipn/i';

    private static function isSecret(string $key): bool
    {
        return !str_contains(strtolower($key), 'public') && (bool) preg_match(self::SECRET, $key);
    }

    public function index()
    {
        $pageTitle = 'Automatic Gateways';

        return Inertia::render('Admin/Gateways/Automatic', [
            'gateways' => Gateway::automatic()->withCount('currencies')->orderBy('alias')->get()->map(fn (Gateway $g) => [
                'id'        => $g->id,
                'name'      => (string) __($g->name),
                'supported' => count((array) $g->supported_currencies),
                'enabled'   => (int) $g->currencies_count,
                'active'    => (int) $g->status === Status::ENABLE,
                'url'       => route('admin.gateway.automatic.edit', $g->alias),
            ]),
            'urls' => $this->urls() + ['status' => route('admin.gateway.automatic.status', ':id')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function edit($alias)
    {
        $gateway    = Gateway::automatic()->with('currencies')->where('alias', $alias)->firstOrFail();
        $parameters = collect(json_decode($gateway->gateway_parameters));

        $param = fn ($key, $p, $value) => [
            'key'    => (string) $key,
            'title'  => (string) __($p->title ?? keyToTitle($key)),
            'secret' => self::isSecret((string) $key),
            // A secret's value stays on the server; the form only learns that one is saved
            'value'  => self::isSecret((string) $key) ? '' : (string) $value,
            'stored' => filled($value),
        ];

        return Inertia::render('Admin/Gateways/AutomaticEdit', [
            'gateway' => [
                'name'   => (string) __($gateway->name),
                'code'   => (string) $gateway->code,
                'image'  => getImage(getFilePath('gateway') . '/' . $gateway->image, getFileSize('gateway')),
                'crypto' => (int) $gateway->crypto === Status::ENABLE,
                // Callback / cron URLs the provider must be told about
                'extra'  => collect((array) ($gateway->extra ?? []))->map(fn ($e, $k) => ['key' => (string) $k, 'title' => (string) __($e->title ?? $k), 'url' => \Illuminate\Support\Facades\Route::has((string) ($e->value ?? '')) ? route($e->value) : '(route ' . ($e->value ?? '?') . ' not found)'])->values(),
            ],
            'global'     => $parameters->where('global', true)->map(fn ($p, $k) => $param($k, $p, $p->value ?? ''))->values(),
            'perCurrency' => $parameters->where('global', false)->map(fn ($p, $k) => ['key' => (string) $k, 'title' => (string) __($p->title ?? keyToTitle($k)), 'secret' => self::isSecret((string) $k)])->values(),
            'currencies' => $gateway->currencies->map(function (GatewayCurrency $c) use ($parameters) {
                $saved = (array) json_decode((string) $c->gateway_parameter, true);
                return [
                    'id'             => $c->id,
                    'currency'       => (string) $c->currency,
                    'symbol'         => (string) $c->symbol,
                    'name'           => (string) $c->name,
                    'min_amount'     => (string) getAmount($c->min_amount),
                    'max_amount'     => (string) getAmount($c->max_amount),
                    'fixed_charge'   => (string) getAmount($c->fixed_charge),
                    'percent_charge' => (string) getAmount($c->percent_charge),
                    'rate'           => (string) getAmount($c->rate),
                    'param'          => $parameters->where('global', false)->mapWithKeys(fn ($p, $k) => [$k => self::isSecret((string) $k) ? '' : (string) ($saved[$k] ?? '')])->all(),
                    'stored'         => $parameters->where('global', false)->mapWithKeys(fn ($p, $k) => [$k => filled($saved[$k] ?? null)])->all(),
                ];
            })->values(),
            // Currencies the gateway supports and that are not added yet
            'available' => collect((array) $gateway->supported_currencies)->except($gateway->currencies->pluck('currency')->all())->map(fn ($symbol, $code) => ['code' => (string) $code, 'symbol' => (string) $symbol])->values(),
            'siteCurrency' => gs('cur_text'),
            'urls'         => $this->urls() + [
                'update' => route('admin.gateway.automatic.update', $gateway->code),
                'remove' => route('admin.gateway.automatic.remove', ':id'),
            ],
        ])->withViewData(['pageTitle' => 'Update Gateway']);
    }

    public function update(Request $request, $code)
    {
        $gateway    = Gateway::automatic()->where('code', $code)->firstOrFail();
        $parameters = collect(json_decode($gateway->gateway_parameters));
        $existing   = $gateway->currencies()->get()->keyBy('currency');

        // Empty secrets keep the saved value: global ones from the gateway,
        // per-currency ones from that currency's saved row
        $global = (array) $request->input('global', []);
        foreach ($parameters->where('global', true) as $key => $p) {
            if (self::isSecret((string) $key) && blank($global[$key] ?? null) && filled($p->value ?? null)) {
                $global[$key] = $p->value;
            }
        }
        // Only a request that sends the currency list replaces it
        $sentCurrencies = $request->has('currency');
        $currencies     = array_values((array) $request->input('currency', []));
        foreach ($currencies as $i => $c) {
            $saved = (array) json_decode((string) ($existing[$c['currency'] ?? '']->gateway_parameter ?? '{}'), true);
            foreach ($parameters->where('global', false) as $key => $p) {
                if (self::isSecret((string) $key) && blank($c['param'][$key] ?? null) && filled($saved[$key] ?? null)) {
                    $currencies[$i]['param'][$key] = $saved[$key];
                }
            }
        }
        $request->merge(['global' => $global, 'currency' => $currencies]);

        $this->gatewayValidator($request)->validate();
        $this->gatewayCurrencyValidator($request, $gateway)->validate();

        foreach ($parameters->where('global', true) as $key => $p) {
            $parameters[$key]->value = $global[$key];
        }

        $filename = $gateway->image;
        if ($request->hasFile('image')) {
            try {
                $filename = fileUploader($request->image, getFilePath('gateway'), old: $filename);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Image could not be uploaded'];
                return back()->withNotify($notify);
            }
        }

        DB::transaction(function () use ($gateway, $parameters, $filename, $currencies, $code, $sentCurrencies) {
            // The alias names the code that processes payments
            // (Gateway\<Alias>\ProcessController). It used to be taken from a
            // hidden form field, so a changed field could break the gateway.
            $gateway->gateway_parameters = json_encode($parameters);
            $gateway->image              = $filename;
            $gateway->save();

            if (!$sentCurrencies) {
                return;
            }
            // The currencies are saved as a set: the ones sent replace the old ones
            $gateway->currencies()->delete();
            foreach ($currencies as $currency) {
                $param = [];
                foreach ($parameters->where('global', true) as $pkey => $p) {
                    $param[$pkey] = $p->value;
                }
                foreach ($parameters->where('global', false) as $pkey => $p) {
                    $param[$pkey] = $currency['param'][$pkey];
                }

                $gc                    = new GatewayCurrency();
                $gc->name              = $currency['name'];
                $gc->gateway_alias     = $gateway->alias;
                $gc->currency          = $currency['currency'];
                $gc->min_amount        = $currency['min_amount'];
                $gc->max_amount        = $currency['max_amount'];
                $gc->fixed_charge      = $currency['fixed_charge'];
                $gc->percent_charge    = $currency['percent_charge'];
                $gc->rate              = $currency['rate'];
                $gc->symbol            = $currency['symbol'];
                $gc->method_code       = $code;
                $gc->gateway_parameter = json_encode($param);
                $gc->save();
            }
        });

        $notify[] = ['success', $gateway->name . ' updated successfully'];
        return to_route('admin.gateway.automatic.edit', $gateway->alias)->withNotify($notify);
    }

    public function remove($id)
    {
        // Only a currency of an automatic gateway (manual ones have a single currency of their own)
        $gatewayCurrency = GatewayCurrency::where('method_code', '<', 1000)->findOrFail($id);
        $gatewayCurrency->delete();

        $notify[] = ['success', 'Gateway currency removed successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        Gateway::automatic()->findOrFail($id);
        return Gateway::changeStatus($id);
    }

    public function gatewayValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'image' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);
    }

    public function gatewayCurrencyValidator(Request $request, Gateway $gateway)
    {
        $customAttributes = [];
        $validationRule   = ['currency' => 'array'];

        $paramList = collect(json_decode($gateway->gateway_parameters));
        $supported = array_keys((array) $gateway->supported_currencies);

        foreach ($paramList->where('global', true) as $key => $pram) {
            $validationRule['global.' . $key]   = 'required|string|max:2000';
            $customAttributes['global.' . $key] = keyToTitle($key);
        }

        foreach ((array) $request->currency as $key => $currency) {
            $validationRule['currency.' . $key . '.currency']       = ['required', 'string', 'in:' . implode(',', $supported), function ($attribute, $value, $fail) use ($request) {
                if (count(array_keys(array_column((array) $request->currency, 'currency'), $value)) > 1) {
                    $fail($value . ' was added twice.');
                }
            }];
            $validationRule['currency.' . $key . '.symbol']         = 'required|string|max:40';
            $validationRule['currency.' . $key . '.name']           = 'required|string|max:255';
            $validationRule['currency.' . $key . '.min_amount']     = 'required|numeric|gt:0|lte:currency.' . $key . '.max_amount';
            $validationRule['currency.' . $key . '.max_amount']     = 'required|numeric|gt:0|gte:currency.' . $key . '.min_amount';
            $validationRule['currency.' . $key . '.fixed_charge']   = 'required|numeric|gte:0';
            $validationRule['currency.' . $key . '.percent_charge'] = 'required|numeric|gte:0|max:100';
            $validationRule['currency.' . $key . '.rate']           = 'required|numeric|gt:0';

            $label = ($currency['name'] ?? null) ?: $gateway->name . ' ' . ($currency['currency'] ?? '');
            foreach (['name', 'min_amount', 'max_amount', 'fixed_charge', 'percent_charge', 'rate', 'currency', 'symbol'] as $field) {
                $customAttributes['currency.' . $key . '.' . $field] = $label . ' ' . keyToTitle($field);
            }
            foreach ($paramList->where('global', false) as $paramKey => $param) {
                $validationRule['currency.' . $key . '.param.' . $paramKey]   = 'required|string|max:2000';
                $customAttributes['currency.' . $key . '.param.' . $paramKey] = $label . ' ' . keyToTitle($param->title);
            }
        }

        return Validator::make($request->all(), $validationRule, [], $customAttributes);
    }

    private function urls(): array
    {
        return ['tabs' => ['automatic' => route('admin.gateway.automatic.index'), 'manual' => route('admin.gateway.manual.index')]];
    }
}
