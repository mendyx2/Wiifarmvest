<?php

namespace App\Http\Controllers\User;

use App\Models\Pin;
use App\Models\Form;
use App\Models\User;
use App\Models\BvLog;
use App\Models\Page;
use App\Models\Order;
use App\Models\Matrix;
use App\Models\Deposit;
use App\Models\Product;
use App\Models\Category;
use App\Models\Stockist;
use App\Constants\Status;
use App\Lib\FormProcessor;
use App\Models\Withdrawal;
use App\Models\DeviceToken;
use App\Models\Transaction;
use App\Models\ProductStatePrice;
use App\Models\Project;
use App\Models\Rinvestment;
use App\Models\Stockist_store;
use App\Models\UserStageProgress;
use App\Services\MatrixPlacementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Lib\GoogleAuthenticator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Support\FormPresenter;
use App\Support\PaymentPresenter;
use App\Support\ShopPresenter;
use App\Models\GatewayCurrency;
use App\Models\NotificationLog;
use App\Models\GuarantorRequest;
use App\Models\WelcomePackage;
use App\Models\Useridcard;
use App\Services\AutoshipService;
use Illuminate\Support\Str;



class UserController extends Controller
{
    protected $matrixService;
    
    public function __construct(MatrixPlacementService $matrixService)
    {
        $this->matrixService = $matrixService;
    }

    public function home()
    {
        // Read-only: this page used to insert an "Announcement: New Feature"
        // row into user_flashcard_preferences on every visit (see notes Step 75).
        $user   = auth()->user();
        $userId = $user->id;

        $idCard   = Useridcard::where('user_id', $userId)->first();
        $matrix   = Matrix::where('user_id', $userId)->first();
        $inMatrix = Matrix::where('user_id', $userId)->where('is_active', 1)->exists();

        $totalDividends = $user->shtransactions()->where('status', 'completed')->sum('amount');
        $totalShares    = Rinvestment::where('user_id', $userId)->where('status', 1)->sum('units');

        $adminNotices = NotificationLog::where('user_id', $userId)->where('is_admin_notice', true)->where('user_read', false);
        $adminNoticeCount = (clone $adminNotices)->count();

        $pvLeft  = (float) ($matrix->pv_left_pairing ?? 0);
        $pvRight = (float) ($matrix->pv_right_pairing ?? 0);
        $pvMax   = max($pvLeft, $pvRight, 1);

        $isNewUser = (int) $user->kv === Status::KYC_UNVERIFIED;

        return Inertia::render('User/Dashboard', [
            'card' => [
                'balance'  => showAmount($user->balance),
                'masked'   => gs('cur_sym') . ' ••••••',
                // Last 4 characters of the "WCS<id>" card number.
                'last4'    => $idCard ? str_pad(substr('WCS' . $idCard->id, -4), 4, '0', STR_PAD_LEFT) : '0000',
                'holder'   => strtoupper($user->fullname),
                'validThru'=> now()->format('m/y'),
                'visa'     => showAmount($user->visa ?? 0),
            ],
            // Admin-written HTML from General Settings (the Blade page echoed it as is).
            'notices'    => array_values(array_filter([gs('notice'), gs('free_user_notice')])),
            'noticeTime' => now()->format('M d · h:i A'),
            'stats' => [
                'totalDeposit'     => showAmount(Deposit::where('user_id', $userId)->where('status', 1)->sum('amount')),
                'totalWithdraw'    => showAmount(Withdrawal::where('user_id', $userId)->where('status', 1)->sum('amount')),
                'completeWithdraw' => Withdrawal::where('user_id', $userId)->where('status', 1)->count(),
                'referralEarnings' => showAmount(Transaction::where('user_id', $userId)->where('remark', 'referral_commission')->where('trx_type', '+')->sum('amount')),
                'totalRef'         => User::where('ref_by', $userId)->count(),
                'dividends'        => showAmount($totalDividends),
                'shares'           => number_format((float) $totalShares),
            ],
            'project' => $user->project ? [
                'title' => $user->project->title,
                'color' => $user->project->color,
                'icon'  => $user->project->icon,
            ] : null,
            'earnings' => [
                'productWallet' => showAmount($user->product_wallet ?? 0),
                'direct'        => showAmount($user->direct_bonus ?? 0),
                'indirect'      => showAmount($user->indirect_bonus ?? 0),
                'upgrade'       => showAmount($user->upgrade_bonus ?? 0),
                'unilevel'      => showAmount($user->unilevel_bonus ?? 0),
                'matching'      => showAmount($user->matching_bonus ?? 0),
                'keyIn'         => showAmount($user->key_in_bonus ?? 0),
            ],
            'adminNotice' => $adminNoticeCount ? [
                'count'   => $adminNoticeCount,
                'subject' => (clone $adminNotices)->latest()->value('subject') ?: 'You have a new notice',
            ] : null,
            'welcomePackages' => WelcomePackage::where('user_id', $userId)->pending()->latest()->get()
                ->map(fn ($wp) => [
                    'code'   => $wp->code,
                    'label'  => ucfirst($wp->source) . ' Welcome Package — ' . showAmount($wp->amount),
                ])->values(),
            'acb'      => $user->isAcb() ? showAmount($user->acb ?? 0) : null,
            'autoship' => (float) ($user->autoship ?? 0) > 0 ? [
                'amount' => showAmount($user->autoship),
                'minPv'  => AutoshipService::MIN_PV,
            ] : null,
            'pv' => $matrix ? [
                'position' => $matrix->position ? ucfirst($matrix->position) : null,
                'positionKey' => $matrix->position,
                'left'     => number_format($pvLeft, 2),
                'right'    => number_format($pvRight, 2),
                'leftPct'  => min(100, (int) round($pvLeft / $pvMax * 100)),
                'rightPct' => min(100, (int) round($pvRight / $pvMax * 100)),
            ] : null,
            'recentTransactions' => Transaction::where('user_id', $userId)->latest()->limit(8)->get()
                ->map(fn ($trx) => [
                    'id'      => $trx->id,
                    'details' => Str::limit($trx->details, 32),
                    'date'    => $trx->created_at->format('M d, Y · h:i A'),
                    'credit'  => $trx->trx_type === '+',
                    'amount'  => $trx->trx_type . showAmount($trx->amount),
                ])->values(),
            'portfolio' => [
                'shares'       => number_format((float) $totalShares),
                'shareCredits' => showAmount($user->shares ?? 0),
                'dividends'    => showAmount($totalDividends),
            ],
            'status' => [
                'active'          => (int) $user->status === 1,
                'kyc'             => match ((int) $user->kv) { Status::KYC_VERIFIED => 'verified', Status::KYC_PENDING => 'pending', default => 'unverified' },
                'kycRejection'    => $isNewUser ? ($user->kyc_rejection_reason ?: null) : null,
                'pendingWithdraw' => Withdrawal::where('user_id', $userId)->where('status', 2)->count(),
                'inMatrix'        => $inMatrix,
            ],
            'flashcards' => $user->pendingFlashcards()->map(fn ($fc) => [
                'id'          => $fc->id,
                'type'        => $fc->type === 'image' ? 'image' : 'video',
                'url'         => $fc->file_url,
                'title'       => $fc->title,
                'description' => $fc->description,
            ])->values(),
            'isStockist' => $user->stockist()->exists(),
            'urls' => [
                'deposit'       => route('user.deposit.index'),
                'withdraw'      => route('user.withdraw.history'),
                'transactions'  => route('user.transactions'),
                'portfolio'     => route('user.investment.portfolio'),
                'products'      => route('user.products'),
                'referrals'     => route('user.my.ref'),
                'tree'          => route('user.my.tree'),
                'stages'        => route('user.my.stages'),
                'epin'          => route('user.epin.recharge'),
                'profile'       => route('user.profile.setting'),
                'notifications' => route('user.notifications'),
                'acb'           => route('user.acb'),
                'bonusTransfer' => route('user.bonus.transfer.index'),
                'kyc'           => route('user.kyc.form'),
                'flashcardDismiss' => route('user.flashcard.dismiss', ['id' => '__ID__']),
            ],
        ])->withViewData(['pageTitle' => 'Dashboard']);
    }

    public function products($categoryId = null)
    {
        $pageTitle = "Products";
        $products  = Product::query();
        if ($categoryId) {
            $products = $products->where('category_id', $categoryId);
        }
        $products   = $products->active()->with('category')->hasCategory()
            ->withMin('statePrices as state_price_min', 'price')
            ->withMax('statePrices as state_price_max', 'price')
            ->paginate(getPaginate(16));
        $categories = Category::active()->hasActiveProduct()->get()->take(5);

        return Inertia::render('User/Shop/Products', [
            'products'   => $products->through(fn ($product) => ShopPresenter::card($product)),
            'categories' => $categories->map(fn ($category) => [
                'id'   => $category->id,
                'name' => __($category->name),
                'url'  => route('user.products', $category->id),
            ])->values(),
            'categoryId' => $categoryId ? (int) $categoryId : null,
            'allUrl'     => route('user.products'),
        ])->withViewData(compact('pageTitle'));
    }

    public function productDetails($id)
    {
        $pageTitle = "Product Details";
        $product   = Product::active()->hasCategory()->with(['category', 'images'])->findOrFail($id);
        // One price per state; the page switches between them without asking the server.
        $statePrices = ProductStatePrice::where('product_id', $id)->with('state')->get()->unique('state_id')->values();

        $relates = Product::active()->hasCategory()->where('category_id', $product->category_id)->where('id', '!=', $product->id)
            ->withMin('statePrices as state_price_min', 'price')
            ->withMax('statePrices as state_price_max', 'price')
            ->latest()->limit(10)->get();

        $prices = $statePrices->pluck('price')->map(fn ($p) => (float) $p);
        $url    = url()->current();

        return Inertia::render('User/Shop/ProductDetail', [
            'product' => [
                'id'          => $product->id,
                'name'        => __($product->name),
                'sku'         => $product->sku,
                'category'    => $product->category ? __($product->category->name) : null,
                'categoryUrl' => route('user.products', $product->category_id),
                'inStock'     => $product->quantity > 0,
                'images'      => collect([$product->thumbnail])
                    ->merge($product->images?->pluck('name') ?? [])
                    ->map(fn ($file) => ShopPresenter::image($file))->values(),
                'keywords'       => array_values(array_filter((array) $product->meta_keyword)),
                'specifications' => collect((array) $product->specifications)
                    ->map(fn ($spec) => ['name' => (string) @$spec['name'], 'value' => (string) @$spec['value']])->values(),
                // Written by the admin in a rich-text editor: cleaned before it is shown.
                'description' => FormPresenter::safeHtml($product->description),
                // Until a state is chosen: the range across states.
                'priceRange' => ShopPresenter::price($prices->min() ?? $product->price, $prices->max()),
            ],
            'states' => $statePrices->map(fn ($record) => [
                'id'    => $record->id,
                'name'  => (string) @$record->state->name,
                'price' => ShopPresenter::price($record->price),
            ]),
            'relates' => $relates->map(fn ($related) => ShopPresenter::card($related)),
            'share'   => [
                'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url),
                'twitter'  => 'https://x.com/intent/tweet?text=' . urlencode(__($product->name)) . '&url=' . urlencode($url),
                'linkedin' => 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($url) . '&title=' . urlencode(__($product->name))
                    . '&summary=' . urlencode(strLimit(strip_tags((string) $product->meta_description), 100)),
            ],
            'cartCount'   => auth()->user()->cart->items()->count(),
            'addToCartUrl' => route('user.cart.add', $product->id),
            'cartUrl'     => route('user.cart.index'),
            'shapeImage'  => asset(activeTemplate(true) . 'images/shape/blob1.png'),
        ])->withViewData(compact('pageTitle'));
    }

    public function depositHistory(Request $request)
    {
        $pageTitle = 'Deposit History';
        $deposits  = auth()->user()->deposits()->searchable(['trx'])->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate())->withQueryString();

        // Migrated to React + Inertia (resources/js/Pages/User/DepositHistory.tsx).
        // Was the Blade view Template::user.deposit_history (deleted)
        return Inertia::render('User/DepositHistory', [
            'deposits' => $deposits->through(fn ($deposit) => [
                'id'            => $deposit->id,
                'trx'           => $deposit->trx,
                'gateway'       => $deposit->method_code < 5000 ? (string) @$deposit->gateway->name : 'Google Pay',
                'date'          => showDateTime($deposit->created_at),
                'ago'           => diffForHumans($deposit->created_at),
                'total'         => showAmount($deposit->amount + $deposit->charge),
                'amount'        => showAmount($deposit->amount),
                'charge'        => showAmount($deposit->charge),
                'rate'          => showAmount(1) . ' = ' . showAmount($deposit->rate, currencyFormat: false) . ' ' . $deposit->method_currency,
                'finalAmount'   => showAmount($deposit->final_amount, currencyFormat: false) . ' ' . $deposit->method_currency,
                'status'        => PaymentPresenter::depositStatus($deposit),
                'manual'        => PaymentPresenter::isManual($deposit),
                'details'       => PaymentPresenter::isManual($deposit) ? PaymentPresenter::details($deposit->detail) : [],
                'adminFeedback' => $deposit->status == Status::PAYMENT_REJECT ? $deposit->admin_feedback : null,
            ]),
            'search'     => (string) $request->search,
            'depositUrl' => route('user.deposit.index'),
        ])->withViewData(compact('pageTitle'));
    }


    public function show2faForm()
    {
        $ga        = new GoogleAuthenticator();
        $user      = auth()->user();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $pageTitle = '2FA Security';

        // Migrated to React + Inertia (resources/js/Pages/User/TwoFactor.tsx).
        // Was the Blade view Template::user.twofactor (deleted)
        return Inertia::render('User/TwoFactor', [
            'enabled'    => (bool) $user->ts,
            'secret'     => $user->ts ? null : $secret,
            'qrCodeUrl'  => $user->ts ? null : $qrCodeUrl,
            'enableUrl'  => route('user.twofactor.enable'),
            'disableUrl' => route('user.twofactor.disable'),
        ])->withViewData(compact('pageTitle'));
    }




    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'key'  => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts  = Status::ENABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator activated successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }



    public function disable2fa(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts  = Status::DISABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator deactivated successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }



    public function transactions()
    {
        $pageTitle    = 'Transactions';

        $remarks      = Transaction::where('user_id', auth()->id())->distinct('remark')->orderBy('remark')->whereNotNull('remark')->get('remark');

        $transactions = Transaction::where('user_id', auth()->id())->searchable(['trx'])->filter(['trx_type', 'remark'])->orderBy('id', 'desc')->paginate(getPaginate())->withQueryString();

        // Migrated to React + Inertia (resources/js/Pages/User/Transactions.tsx).
        // Was the Blade view Template::user.transactions (deleted)
        return Inertia::render('User/Transactions', [
            'transactions' => $transactions->through(fn ($trx) => [
                'id'          => $trx->id,
                'trx'         => $trx->trx,
                'isCredit'    => $trx->trx_type == '+',
                'details'     => __($trx->details),
                'amount'      => showAmount($trx->amount),
                'charge'      => $trx->charge > 0 ? showAmount($trx->charge) : null,
                'postBalance' => showAmount($trx->post_balance),
                'ago'         => diffForHumans($trx->created_at),
                'date'        => showDateTime($trx->created_at, 'd M Y'),
                'dayMonth'    => showDateTime($trx->created_at, 'd M'),
                'year'        => showDateTime($trx->created_at, 'Y'),
            ]),
            'remarks' => $remarks->map(fn ($r) => ['value' => $r->remark, 'label' => keyToTitle($r->remark)])->values(),
            'filters' => [
                'search'   => (string) request('search', ''),
                'trx_type' => (string) request('trx_type', ''),
                'remark'   => (string) request('remark', ''),
            ],
        ])->withViewData(compact('pageTitle'));
    }


    public function kycForm()
    {
        $user = auth()->user();
        if ($user->kv == Status::KYC_PENDING) {
            $notify[] = ['error', 'Your KYC is under review'];
            return to_route('user.home')->withNotify($notify);
        }
        if ($user->kv == Status::KYC_VERIFIED) {
            $notify[] = ['error', 'You are already KYC verified'];
            return to_route('user.home')->withNotify($notify);
        }
        $pageTitle        = 'KYC Verification';
        $form             = Form::where('act', 'kyc')->first();
        $guarantorRequest = GuarantorRequest::where('user_id', $user->id)->with('guarantor')->latest()->first();
        $guarantor        = $guarantorRequest?->guarantor;

        // Migrated to React + Inertia (resources/js/Pages/User/KycForm.tsx).
        // Was the Blade view Template::user.kyc.form (deleted)
        return Inertia::render('User/KycForm', [
            'rejectionReason' => $user->kyc_rejection_reason,
            'fields'          => FormPresenter::fields($form?->id),
            'nin'             => (string) $user->nin,
            'idCardType'      => (string) $user->id_card_type,
            'hasIdCard'       => (bool) $user->id_card_image,
            // The guarantor already nominated (pre-selected, as in the Blade view).
            'guarantor' => $guarantor ? [
                'id'       => $guarantor->id,
                'name'     => trim($guarantor->firstname . ' ' . $guarantor->lastname),
                'username' => $guarantor->username,
            ] : null,
            'guarantorStatus' => $guarantorRequest?->status,
            'submitUrl'       => route('user.kyc.submit'),
            'searchUrl'       => route('user.kyc.user.search'),
        ])->withViewData(compact('pageTitle'));
    }

    public function kycData()
    {
        $user             = auth()->user();
        $pageTitle        = 'KYC Status';
        $guarantorRequest = GuarantorRequest::where('user_id', $user->id)->with('guarantor')->latest()->first();

        $state = match (true) {
            $user->kv == Status::KYC_VERIFIED => 'verified',
            $user->kv == Status::KYC_PENDING  => 'pending',
            (bool) $user->kyc_rejection_reason => 'rejected',
            default                            => 'empty',
        };
        $attachment = function (string $path) {
            $hash = encrypt($path);
            return [
                'view'     => route('user.view.attachment', $hash),
                'download' => route('user.download.attachment', $hash),
                'isImage'  => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']),
            ];
        };
        $kycRows = [];
        foreach ($user->kyc_data ?? [] as $val) {
            if (!$val->value) continue;
            $kycRows[] = [
                'label' => __($val->name),
                'value' => $val->type == 'file' ? null : ($val->type == 'checkbox' ? implode(', ', (array) $val->value) : __($val->value)),
                'file'  => $val->type == 'file' ? $attachment(getFilePath('verify') . '/' . $val->value) : null,
            ];
        }
        $guarantor = $guarantorRequest?->guarantor;

        // Migrated to React + Inertia (resources/js/Pages/User/KycInfo.tsx).
        // Was the Blade view Template::user.kyc.info (deleted)
        return Inertia::render('User/KycInfo', [
            'state'           => $state,
            'rejectionReason' => $user->kyc_rejection_reason,
            'submitted'       => $user->kyc_data || $user->nin || $user->kv != Status::KYC_UNVERIFIED,
            'kycRows'         => $kycRows,
            'nin'             => $user->nin,
            'idCardType'      => $user->id_card_type,
            'idCard'          => $user->id_card_image ? $attachment(getFilePath('verify') . '/id_cards/' . $user->id_card_image) : null,
            'guarantor' => $guarantor ? [
                'name'        => trim($guarantor->firstname . ' ' . $guarantor->lastname),
                'username'    => $guarantor->username,
                'status'      => $guarantorRequest->status,
                'respondedAt' => $guarantorRequest->responded_at?->format('d M Y, g:i A'),
            ] : null,
            'formUrl' => route('user.kyc.form'),
        ])->withViewData(compact('pageTitle'));
    }

    public function kycSubmit(Request $request)
    {
        $user = auth()->user();

        // V1: Block resubmission once pending or verified — form GET already redirects,
        // but a direct POST would bypass that check without this guard.
        if ($user->kv === Status::KYC_PENDING) {
            $notify[] = ['error', 'Your KYC is currently under review and cannot be modified.'];
            return to_route('user.home')->withNotify($notify);
        }
        if ($user->kv === Status::KYC_VERIFIED) {
            $notify[] = ['error', 'Your KYC is already verified.'];
            return to_route('user.home')->withNotify($notify);
        }

        $form          = Form::where('act', 'kyc')->firstOrFail();
        $formData      = $form->form_data;
        $formProcessor = new FormProcessor();

        $validationRule = $formProcessor->valueValidation($formData);

        // Check the NIN in the same form it is saved in (trimmed, upper case), so
        // the unique rule below also holds on a case-sensitive database column.
        if (is_string($request->nin)) {
            $request->merge(['nin' => strtoupper(trim($request->nin))]);
        }

        // V3: NIN must be unique across all users (ignore own record) — prevents
        // two accounts claiming the same national identity number.
        $validationRule['nin'] = [
            'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/',
            Rule::unique('users', 'nin')->ignore($user->id),
        ];

        $validationRule['id_card_type'] = ['required', Rule::in(['driving_license', 'international_passport', 'nin'])];
        $validationRule['id_card_image'] = $user->id_card_image
            ? 'nullable|image|mimes:jpg,jpeg,png|max:2048'
            : 'required|image|mimes:jpg,jpeg,png|max:2048';
        $validationRule['guarantor_id'] = [
            'required', 'integer', 'exists:users,id',
            Rule::notIn([$user->id]),
        ];

        $request->validate($validationRule);

        $userData = $formProcessor->processFormData($request, $formData);

        // Upload new ID card image if provided
        $newIdCardFilename = null;
        if ($request->hasFile('id_card_image')) {
            $newIdCardFilename = fileUploader(
                $request->file('id_card_image'),
                getFilePath('verify') . '/id_cards'
            );
        }

        // V6: Delete old files only AFTER new data is ready — prevents data loss
        // if an earlier step threw an exception.
        foreach (@$user->kyc_data ?? [] as $kycField) {
            if ($kycField->type == 'file') {
                fileManager()->removeFile(getFilePath('verify') . '/' . $kycField->value);
            }
        }
        if ($newIdCardFilename && $user->id_card_image) {
            fileManager()->removeFile(getFilePath('verify') . '/id_cards/' . $user->id_card_image);
        }

        $user->kyc_data             = $userData;
        $user->nin                  = strtoupper(trim($request->nin));
        $user->id_card_type         = $request->id_card_type;
        $user->kyc_rejection_reason = null;
        $user->kv                   = Status::KYC_PENDING;
        if ($newIdCardFilename) {
            $user->id_card_image = $newIdCardFilename;
        }
        $user->save();

        // Upsert guarantor request
        $guarantorId = (int) $request->guarantor_id;
        $existing    = GuarantorRequest::where('user_id', $user->id)->latest()->first();

        if ($existing && $existing->guarantor_id === $guarantorId) {
            if ($existing->isDeclined()) {
                $existing->status       = 'pending';
                $existing->responded_at = null;
                $existing->save();
            }
        } else {
            GuarantorRequest::where('user_id', $user->id)->delete();
            GuarantorRequest::create([
                'user_id'      => $user->id,
                'guarantor_id' => $guarantorId,
                'status'       => 'pending',
            ]);
        }

        $notify[] = ['success', 'KYC data submitted successfully. Awaiting review.'];
        return to_route('user.home')->withNotify($notify);
    }

    public function guarantorRequests()
    {
        $user      = auth()->user();
        $pageTitle = 'Guarantor Requests';
        $incoming  = GuarantorRequest::where('guarantor_id', $user->id)
                        ->with('user')
                        ->latest()
                        ->paginate(getPaginate());

        // Migrated to React + Inertia (resources/js/Pages/User/GuarantorRequests.tsx).
        // Was the Blade view Template::user.kyc.guarantor_requests (deleted)
        return Inertia::render('User/GuarantorRequests', [
            // Only the requester's name and username: never their KYC details.
            'requests' => $incoming->through(fn ($req) => [
                'id'           => $req->id,
                'name'         => $req->user ? trim($req->user->firstname . ' ' . $req->user->lastname) : 'Unknown User',
                'username'     => $req->user?->username,
                'status'       => $req->status,
                'requested'    => $req->created_at->diffForHumans(),
                'responded'    => $req->responded_at?->diffForHumans(),
                'respondedAt'  => $req->responded_at?->format('d M Y, g:i A'),
                'acceptUrl'    => route('user.guarantor.accept', $req->id),
                'declineUrl'   => route('user.guarantor.decline', $req->id),
            ]),
        ])->withViewData(compact('pageTitle'));
    }

    public function guarantorAccept($id)
    {
        // V2: Only allow responding to pending requests — prevents toggling
        // between accepted/declined after an initial response.
        $req = GuarantorRequest::where('guarantor_id', auth()->id())
                 ->where('id', $id)
                 ->where('status', 'pending')
                 ->firstOrFail();

        $req->status       = 'accepted';
        $req->responded_at = now();
        $req->save();

        $notify[] = ['success', 'You have accepted the guarantor request.'];
        return back()->withNotify($notify);
    }

    public function guarantorDecline($id)
    {
        // V2: Same pending-only guard as guarantorAccept.
        $req = GuarantorRequest::where('guarantor_id', auth()->id())
                 ->where('id', $id)
                 ->where('status', 'pending')
                 ->firstOrFail();

        $req->status       = 'declined';
        $req->responded_at = now();
        $req->save();

        $notify[] = ['warning', 'You have declined the guarantor request.'];
        return back()->withNotify($notify);
    }

    public function userSearch(Request $request)
    {
        // V5: Throttle — max 30 searches per minute per user to prevent enumeration.
        $key = 'kyc-search:' . auth()->id();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);

        $q = trim($request->q ?? '');
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        // V4: Escape SQL LIKE metacharacters so % and _ in user input are treated
        // as literals, not wildcards. This prevents overly broad result sets.
        $safe = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);

        $results = User::where('id', '!=', auth()->id())
            ->where(function ($query) use ($safe) {
                $query->where('username', 'like', "%{$safe}%")
                      ->orWhere('firstname', 'like', "%{$safe}%")
                      ->orWhere('lastname', 'like', "%{$safe}%");
            })
            ->where('status', 1)
            ->limit(10)
            ->get(['id', 'firstname', 'lastname', 'username']);

        return response()->json($results->map(fn ($u) => [
            'id'       => $u->id,
            'name'     => trim($u->firstname . ' ' . $u->lastname),
            'username' => $u->username,
        ]));
    }

    public function userData()
    {
        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $general = gs();

        $paysAcc     = GatewayCurrency::where('method_code', 107)->where('currency', 'NGN')->first();
        $paystackAcc = json_decode($paysAcc->gateway_parameter);

        $trx = getTrx(20);
        $user->trx = $trx;
        $user->save();

        $project  = $user->project_id ? Project::find($user->project_id) : null;
        $regAmount = $project ? (float) $project->amount : (float) $general->registration_fee;

        $data             = [];
        $data['key']      = $paystackAcc->public_key;
        $data['email']    = $user->email;
        $data['amount']   = $regAmount * 100;
        $data['currency'] = 'NGN';
        $data['ref']      = $trx;

        $pageTitle = 'Payment Page';
        return view('Template::user.user_data', compact('pageTitle', 'user', 'data'));
    }

    public function addDeviceToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            return ['success' => true, 'message' => 'Already exists'];
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = Status::NO;
        $deviceToken->save();

        return ['success' => true, 'message' => 'Token saved successfully'];
    }

    public function downloadAttachment($fileHash)
    {
        try {
            $filePath = decrypt($fileHash);
        } catch (\Exception $e) {
            abort(403, 'Invalid file reference');
        }

        // Resolve relative to public/ (where uploaded assets live)
        $absolutePath = public_path($filePath);
        $realPath     = realpath($absolutePath);
        $allowedBase  = realpath(public_path());

        if (!$realPath || !$allowedBase || !str_starts_with($realPath, $allowedBase . DIRECTORY_SEPARATOR)) {
            abort(403, 'Access denied');
        }

        if (!file_exists($realPath)) {
            $notify[] = ['error', 'File does not exist'];
            return back()->withNotify($notify);
        }

        $extension = pathinfo($realPath, PATHINFO_EXTENSION);
        $filename  = slug(gs('site_name')) . '-attachment.' . $extension;

        return response()->download($realPath, $filename);
    }


 

    
    public function notifications()
    {
        $user          = auth()->user();
        $pageTitle     = 'Notifications';
        $notifications = NotificationLog::where('user_id', $user->id)
                            ->with('notifyingAdmin')
                            ->orderBy('id', 'desc')
                            ->paginate(getPaginate());

        // Map before marking as read, so this page still highlights what was unread.
        $notifications->through(fn ($notif) => [
            'id'        => $notif->id,
            'subject'   => $notif->subject ?: 'Notification',
            'message'   => strip_tags($notif->message),
            'type'      => $notif->notification_type,
            'unread'    => !$notif->user_read,
            'adminNote' => (bool) $notif->is_admin_notice,
            'from'      => $notif->notifyingAdmin ? ($notif->notifyingAdmin->name ?? $notif->notifyingAdmin->username) : null,
            'time'      => $notif->created_at->format('M d, Y · h:i A'),
        ]);

        NotificationLog::where('user_id', $user->id)->where('user_read', false)->update(['user_read' => true]);

        // Migrated to React + Inertia (resources/js/Pages/User/Notifications.tsx).
        // Was the Blade view Template::user.notifications (deleted)
        return Inertia::render('User/Notifications', compact('notifications'))->withViewData(compact('pageTitle'));
    }

    public function markNotificationsRead()
    {
        $user = auth()->user();
        NotificationLog::where('user_id', $user->id)->where('user_read', false)->update(['user_read' => true]);
        return response()->json(['success' => true]);
    }
}
