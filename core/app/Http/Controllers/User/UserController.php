<?php

namespace App\Http\Controllers\User;

use App\Models\Pin;
use App\Models\Form;
use App\Models\User;
use App\Models\BvLog;
use App\Models\Page;
use App\Models\Order;
use App\Models\Sorder;
use App\Models\Matrix;
use App\Models\Deposit;
use App\Models\Product;
use App\Models\Category;
use App\Models\Stockist;
use App\Constants\Status;
use App\Lib\FormProcessor;
use App\Models\Useridcard;
use App\Models\Withdrawal;
use App\Models\DeviceToken;
use App\Models\Transaction;
use App\Models\ProductStatePrice;
use App\Models\Project;
use App\Models\Rinvestment;
use App\Models\MatrixStage;
use App\Models\Stockist_store;
use App\Models\UserStageProgress;
use App\Services\MatrixPlacementService;
use App\Services\WalletService;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Lib\GoogleAuthenticator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Support\FormPresenter;
use App\Support\PaymentPresenter;
use App\Models\GatewayCurrency;
use App\Models\NotificationLog;
use App\Models\GuarantorRequest;
use App\Models\WelcomePackage;



class UserController extends Controller
{
    protected $matrixService;
    
    public function __construct(MatrixPlacementService $matrixService)
    {
        $this->matrixService = $matrixService;
    }
    public function downline(){
        
        $pageTitle  = 'User Downliner';

        
        $matric = Matrix::where('user_id', auth()->id())
            ->where('stage_id', 1)->first();
        
       // $total_Downline_count = $this->matrixService->countTotalDownliner($matric);
        //$total_Down = $total_Downline_count - 1;


        return view('Template::user.downline', compact('pageTitle'));
    
    }
    public function downline1(Request $request){

        
        $request->validate([
            'username'  => 'required',
        ]);

        $pageTitle  = 'User Downliner';

        $username = sanitizeInput($request->username);
        $user = User::where('username', $username)->first();
        if ($user) {
            $total_Downline_count = $this->matrixService->checkIfIdIs_A_Downline($user->id, auth()->id());
            if ($total_Downline_count) {
                $userID = $total_Downline_count->user_id;
                //dd($userID);
                $matric = Matrix::where('user_id', $userID)->first();
                $tree      = showMatrixTree($userID);

                return view('Template::user.downline1', compact('pageTitle',  'matric', 'tree'));


            }else{

                $notify[] = ['error', 'User is not in your tree'];
               return to_route('user.downline')->withNotify($notify);
            }

            
            
        }else {
            $notify[] = ['error', 'User does not exist'];
            return to_route('user.downline')->withNotify($notify);
        }
        
    }

    public function home()
    {   $user = User::find(auth()->id());
        
        if ($user->section == 1) {
            $currentUserMatrix = returnCurrentMatrixStage(auth()->id());

        }

        $user->flashcardPreferences()->create([
            'type' => 'image',
            'content_url' => asset('storage/announcements/new-feature.jpg'),
            'title' => 'Announcement: New Feature',
            'description' => 'We\'ve added a new dashboard feature!',
            'is_active' => true,
        ]);

            
        $pageTitle        = 'Dashboard';
        $totalDeposit     = Deposit::where('user_id', auth()->id())->where('status', 1)->sum('amount');
        $totalWithdraw    = Withdrawal::where('user_id', auth()->id())->where('status', 1)->sum('amount');
        $completeWithdraw = Withdrawal::where('user_id', auth()->id())->where('status', 1)->count();

        $total_ref = Transaction::where('user_id', auth()->id())->where('remark', 'referral_commission')->where('trx_type', '+')->sum('amount');

        $total_ref_debit = Transaction::where('user_id', auth()->id())->where('remark', 'referral_commission')->where('trx_type', '-')->sum('amount');

        $pendingWithdraw  = Withdrawal::where('user_id', auth()->id())->where('status', 2)->count();
        $totalRef         = User::where('ref_by', auth()->id())->count();
        $totalBvCut       = BvLog::where('user_id', auth()->id())->where('trx_type', '-')->sum('amount');

        $recentTransactions = Transaction::where('user_id', auth()->id())->latest()->limit(8)->get();
        $totalShares      = Rinvestment::where('user_id', auth()->id())->where('status', 1)->sum('units');
        $totalDividends   = auth()->user()->shtransactions()->where('status', 'completed')->sum('amount');
        $userMatrix       = Matrix::where('user_id', auth()->id())->first();

        $unreadAdminNoticeCount = NotificationLog::where('user_id', auth()->id())->where('is_admin_notice', true)->where('user_read', false)->count();
        $latestAdminNotice      = $unreadAdminNoticeCount > 0
            ? NotificationLog::where('user_id', auth()->id())->where('is_admin_notice', true)->where('user_read', false)->latest()->first()
            : null;

        $pendingWelcomePackages = WelcomePackage::where('user_id', auth()->id())->pending()->latest()->get();

        return view('Template::user.dashboard', compact('pageTitle', 'totalDeposit', 'totalWithdraw', 'completeWithdraw', 'pendingWithdraw', 'totalRef', 'totalBvCut', 'total_ref', 'total_ref_debit', 'recentTransactions', 'totalShares', 'totalDividends', 'userMatrix', 'unreadAdminNoticeCount', 'latestAdminNotice', 'pendingWelcomePackages'));
    }
    
    
    public function stockists()
    { 
        $pageTitle        = 'Stockists Dashboard';
        $wallet     = Stockist::where('user_id', auth()->id())->first()->wallet;

        //$stockist_store = Stockist_store::where('user_id', auth()->id())->with('product')->orderBy('id', 'desc')->get();

        $products    = Product::get();
  
        //$orders    = Order::where('user_id', auth()->user()->id)->with('product')->orderBy('id', 'desc')->paginate(getPaginate());

        return view('Template::user.stock', compact('pageTitle', 'wallet', 'products'));
    }
    public function products($categoryId = null)
    {
        $pageTitle = "Products";
        $products  = Product::query();
        if ($categoryId) {
            $products = $products->where('category_id', $categoryId);
        }
        $products    = $products->active()->with('category')->hasCategory()
            ->withMin('statePrices as state_price_min', 'price')
            ->withMax('statePrices as state_price_max', 'price')
            ->paginate(getPaginate(16));
        $categories  = Category::active()->hasActiveProduct()->get()->take(5);
        $sections    = Page::where('tempname', activeTemplate())->where('slug', 'products')->first();
        $seoContents = @$sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;

        return view('Template::products', compact('pageTitle', 'products', 'categories', 'categoryId', 'sections', 'seoContents', 'seoImage'));
    }

    public function products1($categoryId = null)
    {
        $pageTitle = "Products";
        $products  = Product::query();
        if ($categoryId) {
            $products = $products->where('category_id', $categoryId);
        }
        $products    = $products->active()->with('category')->hasCategory()
            ->withMin('statePrices as state_price_min', 'price')
            ->withMax('statePrices as state_price_max', 'price')
            ->paginate(getPaginate(16));
        $categories  = Category::active()->hasActiveProduct()->get()->take(5);
        $sections    = Page::where('tempname', activeTemplate())->where('slug', 'products')->first();
        $seoContents = @$sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;

        return view('Template::products1', compact('pageTitle', 'products', 'categories', 'categoryId', 'sections', 'seoContents', 'seoImage'));
    }

    public function productDetails($id)
    {
        $pageTitle = "Product Details";
        $product   = Product::active()->hasCategory()->findOrFail($id);
         $product_state_prices = ProductStatePrice::where('product_id', $id)->get();
         $product_state_prices = $product_state_prices->unique('state_id');
       

        //dd($product_state_prices);

        $relates   = Product::active()->hasCategory()->where('category_id', $product->category_id)->where('id', '!=', $product->id)
            ->withMin('statePrices as state_price_min', 'price')
            ->withMax('statePrices as state_price_max', 'price')
            ->latest()->limit(10)->get();

        $seoContents['social_title']       = $product->meta_title;
        $seoContents['keywords']           = $product->meta_keyword;
        $seoContents['description']        = strLimit(strip_tags($product->meta_description), 150);
        $seoContents['social_description'] = strLimit(strip_tags($product->meta_description), 150);
        $seoContents['image_size']         = getFileSize('products');
        $seoImage                          = getImage(getFilePath('products') . '/' . @$product->thumbnail, getFileSize('products'));
        return view('Template::product_detail', compact('pageTitle', 'product', 'relates', 'seoContents', 'seoImage', 'product_state_prices'));
    }

    public function searchState(Request $request){ 
        $request->validate([
            'product_state_id' => 'required|integer|exists:product_state_prices,id'
        ]);
        //'state_id' => 'required|string|exists:states,invoice_code'
        $product_state  = ProductStatePrice::find($request->product_state_id);
       
        return response()->json([
            'success' => true,
            'price' => showAmount($product_state->price),
            // Same markup as the page (amount + currency code), so the price keeps its currency
            'price_html' => \Illuminate\Support\Facades\Blade::render('<x-product.price :amount="$amount" />', ['amount' => (float) $product_state->price]),
            'product_state_id' => $product_state->id
        ]);
    }
    
    public function productDetails1($id)
    {
        $pageTitle = "Product Details";
        $product   = Product::active()->hasCategory()->findOrFail($id);
        
        

        $relates   = Product::active()->hasCategory()->where('category_id', $product->category_id)->where('id', '!=', $product->id)
            ->withMin('statePrices as state_price_min', 'price')
            ->withMax('statePrices as state_price_max', 'price')
            ->latest()->limit(10)->get();

        $seoContents['social_title']       = $product->meta_title;
        $seoContents['keywords']           = $product->meta_keyword;
        $seoContents['description']        = strLimit(strip_tags($product->meta_description), 150);
        $seoContents['social_description'] = strLimit(strip_tags($product->meta_description), 150);
        $seoContents['image_size']         = getFileSize('products');
        $seoImage                          = getImage(getFilePath('products') . '/' . @$product->thumbnail, getFileSize('products'));
        return view('Template::product_detail1', compact('pageTitle', 'product', 'relates', 'seoContents', 'seoImage'));
    }


    
    public function depositHistory(Request $request)
    {
        $pageTitle = 'Deposit History';
        $deposits  = auth()->user()->deposits()->searchable(['trx'])->with(['gateway'])->orderBy('id', 'desc')->paginate(getPaginate())->withQueryString();

        // Migrated to React + Inertia (resources/js/Pages/User/DepositHistory.tsx).
        // Legacy view: Template::user.deposit_history
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
        // Legacy view: Template::user.twofactor
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
        // Legacy view: Template::user.transactions (still used by Stockist/Epin logs)
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
        // Legacy view: Template::user.kyc.form
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
        // Legacy view: Template::user.kyc.info
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
        // Legacy view: Template::user.kyc.guarantor_requests
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
        if ($user->section == 2) {
            return to_route('user.data1');
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

    public function userDataSubmit(Request $request)
    {

        //dd($request);
        $user = auth()->user(); 


        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        /*
        $pin = Pin::where('pin', $user->pin)->where('status', Status::NO)->first();
        if(!$pin){
            $notify[] = ['error', 'Pin has been used, by another User.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

            */
        if($request->parent){

            $request->validate([

                'parent'       => 'required|string',
                'position'     => 'required|string|max:6',
                'username'     => 'required|unique:users|min:6',
            ]);


             $parent = User::where('username', $request->parent)->first();      

            if(!$parent){
                    $notify[] = ['error', 'Parent is not in the network..'];
                return back()->withNotify($notify)->withInput($request->all());
            }

            // later check if this parent is still in stage one
             $user_parent_matrix = Matrix::where('user_id', $parent->id)->where($request->position, 0)->first();
             //dd($user_matrix);

             // check if the parent is in the matrix before assignment to new user
            if(!$user_parent_matrix){
                $notify[] = ['error', 'Chose another position. Parent request->position is filled'];
                return back()->withNotify($notify)->withInput($request->all());
            }
              
            // all check has been completed move to assignment 

            $user->parent = $request->parent;
            $user->position = $request->position;// NOTE: to be crab later

            $parent_id = $parent->id; //set parent ID                          

        }else{
            $request->validate([
                'username'     => 'required|unique:users|min:6',
                
            ]);
             $parent_id = 0; //set parent ID
        }
 
         if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            $notify[] = ['info', 'Username can contain only small letters, numbers and underscore.'];
            $notify[] = ['error', 'No special character, space or capital letters in username.'];
            return back()->withNotify($notify)->withInput($request->all());
        }


        DB::beginTransaction();

            try {
                // $pin->status = Status::YES;
                // $pin->user_id = $user->id;
                // $pin->save(); 

                if($user_parent_matrix){
                    if($request->position == 'left'){
                        $user_parent_matrix->left = $user->id;
                        $user_parent_matrix->save();
                    }
                    if($request->position == 'right'){
                       $user_parent_matrix->right = $user->id;
                        $user_parent_matrix->save();
                    } 
                      
                }

                $matrixRoot = $parent_id;
                //$placement = $this->findPlacementNode($matrixRoot, $stage);
                Matrix::create([
                    'user_id' => $user->id,
                    'matrix_owner_id' => $matrixRoot,
                    'parent_id' => $parent_id,
                    'stage_id' => 1,
                    '$request->position' => $request->position,
                    'is_active' => 1
                ]);

                UserStageProgress::firstOrCreate([
                    'user_id' => $user->id,
                    'stage_id' => 1
                ]);

                $user->username     = $request->username;
                $user->profile_complete = Status::YES;
                $user->save();
                $details = 'Bonus gotten from username: '.$user->username;
                referralStageMAtrix($user->id, $details, 1, 1);


                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

       
        

        return to_route('user.home');
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


    public function checkCode(Request $request)
    {   
        $request->validate([
            'code'   => 'required|string|min:7'
            
        ]);

        $data = [];
        $dat= Order::where('order_code', $request->code)->where('status', 0)->first();

        if($dat){
            $product = Product::hasCategory()->active()->find($dat->product_id);
            $data['productId'] = $product->id;
            $data['price'] = showAmount($product->price);
            $data['name'] = $product->name;
            $data['description'] = $product->description;
            $data['qty'] = $dat->quantity;
            $data['userId'] = $dat->user_id;
            $data['status'] = $dat->status;
            $data['total'] = showAmount($product->price * $dat->quantity);
            $data['order_code'] = $dat->order_code;
        }

        return response()->json(['data' => $data]);
    } 

    /**
     * A stockist hands a paid order to the buyer: the stockist wallet is
     * credited, their store stock drops and the upline commissions are paid.
     *
     * All of that must happen once per order. The stockist and store rows are
     * locked and checked first (no writes yet, so an early return is safe),
     * then the order is claimed with a conditional UPDATE — pending →
     * delivered — and only the request that wins the claim goes on to pay.
     */
    public function purchaseDone(Request $request)
    {
        $request->validate([
            'userId'     => 'required|integer',
            'order_code' => 'required|string',
            'productId'  => 'required|integer',
        ]);

        $userId = auth()->id();

        $order            = Order::where('order_code', $request->order_code)->where('product_id', $request->productId)->where('user_id', $request->userId)->where('status', Status::ORDER_PENDING)->first();
        $product          = Product::hasCategory()->active()->find($request->productId);
        $user_distributor = User::find($request->userId);

        if (!$order || !$product || !$user_distributor) {
            $notify[] = ['error', 'Order not found or already delivered'];
            return back()->withNotify($notify);
        }

        $error = DB::transaction(function () use ($order, $product, $user_distributor, $userId) {
            $stockist = Stockist::where('user_id', $userId)->where('status', 1)->lockForUpdate()->first();
            if (!$stockist) {
                return 'Only an active stockist can deliver orders';
            }

            $stockist_store = Stockist_store::where('product_id', $product->id)->where('user_id', $userId)->lockForUpdate()->first();
            if (!$stockist_store || $stockist_store->quantity < $order->quantity) {
                return 'Store Quantity is not enough to process this transaction';
            }

            $claimed = Order::whereKey($order->id)
                ->where('status', Status::ORDER_PENDING)
                ->update(['status' => Status::ORDER_DELIVERED, 'stockist_user_id' => $userId]);

            if (!$claimed) {
                return 'This order has already been delivered';
            }

            $order->status           = Status::ORDER_DELIVERED;
            $order->stockist_user_id = $userId;
            $order->syncOriginal();

            $total = $order->price * $order->quantity;

            $stockist->wallet += $total;
            $stockist->save();

            stockistPurchase($userId, $total, $stockist->wallet, 'Credit on stockist wallet', 'stockist_sales', $order->trx);

            // reduce stockist store quantity on this particular product
            $stockist_store->quantity -= $order->quantity;
            $stockist_store->save();

            // Process the commission for the distributor upliner
            $this->processPurchaseCommision($product, $user_distributor, $order);

            return null;
        });

        if ($error) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Transaction successfully'];
        return back()->withNotify($notify);
    }
    // Process the purchase commission
    protected function processPurchaseCommision(Product $product, User $user_distributor, Order $order){
        // first we allocate the 40 of the interest dirstribute to the stockist.

        // Amount to distributor 
        $amount = $product->distributor_purchase * $order->quantity;

        $trx = $order->trx;
        $product_name = $product->name;


        //Credit the stockist user

        $stockist_user= User::find(auth()->id());

        if($stockist_user){
            
            $details1 = 'Stockist Product purchase commission recieved from product ( '.$product_name.' ) purchase by '.$user_distributor->username;

            purchaseCommision_distributor_stockist($stockist_user, $details1, 
                $amount, $trx, 40);
        }


        $user_1st_id = $user_distributor->ref_by;
        if($user_1st_id){
            $user= User::find($user_1st_id);
            $details1 = 'Product purchase commission (1st Gen ) recieved from product ( '.$product_name.' ) purchase by '.$user_distributor->username;

            purchaseCommision_distributor($user, $details1, 
                $amount, $trx, 24);
           
        

        // Second Generation/ parent referral
        $user_2nd = returnReferrerUser($user_1st_id);
        if($user_2nd){

          
            $details2 = 'Product purchase commission (2nd Gen ) recieved from product('.$product_name.') purchase by '.$user_distributor->username;
            purchaseCommision_distributor($user_2nd, $details2, 
                $amount, $trx, 20);
        // Third Generation/ parent referral
        $user_3rd = returnReferrerUser($user_2nd->id);
        if($user_3rd){
            $details3 = 'Product purchase commission (3rd Gen ) recieved from product('.$product_name.') purchase by '.$user_distributor->username;
            purchaseCommision_distributor($user_3rd, $details3, 
                $amount, $trx, 12);
       

        // Fourth Generation/ parent referral
        $user_4th = returnReferrerUser($user_3rd->id);
        if($user_4th){
            $details4 = 'Product purchase commission (4th Gen ) recieved from product('.$product_name.') purchase by '.$user_distributor->username;
            purchaseCommision_distributor($user_4th, $details4, 
                $amount, $trx, 4);

        } // Fourth Generation ends here

        }// Third Generation ends here

        }// Second Generation ends here

        }// first generation ends here

      
    }
    
    public function restockGoods(Request $request)
    {
        
        $request->validate([
            'prod_id'   => 'required|integer',
            'qty'   => 'required|string',
            'stock_id'   => 'required|string',
            
        ]);

        $product = Product::where('status', 1)->find($request->prod_id);
        

        if(!$product){
            $notify[] = ['error', 'Invalid request, Product not available'];
                return back()->withNotify($notify);
        }

        $stockist_store = Stockist_store::where('user_id', auth()->id())->where('product_id', $product->id)->first();
        if ($stockist_store) {
           if($stockist_store->quantity > 100){
                $notify[] = ['error', 'Order Decline. Your Store Quantity is still above 50 bags'];
                return back()->withNotify($notify);
            }

        }

        
        //Ensure no previous order exist.
        $s_order = Sorder::where('product_id', $product->id)->where('user_id', auth()->id())->where('status', 0)->first();

        if($s_order){
            $notify[] = ['error', 'You have an existing order'];
            return back()->withNotify($notify);
        }

        if ($stockist_store ) {
            DB::transaction(function () use ($stockist_store, $product, $request) {

                $sorder                     = new Sorder();
                $sorder->user_id            = auth()->id();
                $sorder->quantity           = $request->qty;
                $sorder->product_id         = $product->id;
                $sorder->stockist_store_id  = $stockist_store->id;
                $sorder->save();
 
            });
        }else{
            //create Stockist_store firstbefore making the order
            DB::transaction(function () use ($stockist_store, $product, $request) {
                $user_id = auth()->id();
                //$stockist= Stockist::where('user_id', $user_id)->where('status', 1)->first();
                
                $stockist_store  = new Stockist_store();
                $stockist_store->user_id    = $user_id;
                $stockist_store->product_id = $product->id;
                $stockist_store->status     = 1;
                $stockist_store->save();

                $sorder                     = new Sorder();
                $sorder->user_id            = $user_id;
                $sorder->quantity           = $request->qty;
                $sorder->product_id         = $product->id;
                $sorder->stockist_store_id  = $stockist_store->id;
                $sorder->save();

            }); 
        }

        $notify[] = ['success', 'Order Request successfully'];
            return back()->withNotify($notify);
    }

    /**
     * Buy a product from the Money Box balance.
     *
     * The product row is locked so two buyers can't both take the last units,
     * and the debit goes through WalletService so a parallel withdrawal or
     * purchase can't spend the same balance. Stock, money, ledger and order
     * all commit together or not at all.
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'quantity'   => 'required|integer|gt:0',
            'product_id' => 'required|integer|gt:0'
        ]);

        $user = auth()->user();

        try {
            $result = DB::transaction(function () use ($request, $user) {
                $product = Product::hasCategory()->active()->lockForUpdate()->find($request->product_id);

                if (!$product) {
                    return 'Product not found';
                }

                if ($request->quantity > $product->quantity) {
                    return 'Requested quantity is not available in stock';
                }

                $totalPrice = $product->price * $request->quantity;

                if ($totalPrice <= 0) {
                    return 'This product is not available for purchase';
                }

                $transaction = app(WalletService::class)->debit(
                    $user,
                    (float) $totalPrice,
                    'product_purchase',
                    $product->name . ' item purchase',
                );

                $product->quantity -= $request->quantity;
                $product->save();

                $order              = new Order();
                $order->user_id     = $user->id;
                $order->product_id  = $product->id;
                $order->quantity    = $request->quantity;
                $order->price       = $product->price;
                $order->total_price = $totalPrice;
                $order->trx         = $transaction->trx;
                $order->status      = 0;
                $order->order_code  = getTrx(10);
                $order->save();

                return compact('product', 'transaction', 'totalPrice');
            });
        } catch (InsufficientBalanceException) {
            $notify[] = ['error', 'Balance is not sufficient'];
            return back()->withNotify($notify);
        }

        if (is_string($result)) {
            $notify[] = ['error', $result];
            return back()->withNotify($notify);
        }

        ['product' => $product, 'transaction' => $transaction, 'totalPrice' => $totalPrice] = $result;

        notify($user, 'ORDER_PLACED', [
            'product_name' => $product->name,
            'quantity'     => $request->quantity,
            'price'        => showAmount($product->price, currencyFormat: false),
            'total_price'  => showAmount($totalPrice, currencyFormat: false),
            'trx'          => $transaction->trx,
        ]);

        $notify[] = ['success', 'Order placed successfully'];
        return back()->withNotify($notify);
    }

    public function purchase1(Request $request)
    {
        return $this->purchase($request);
    }

    public function indexTransfer()
    {
        $pageTitle = 'Balance Transfer';
        return view('Template::user.balanceTransfer', compact('pageTitle'));
    }

    public function searchUser(Request $request)
    {
        $transUser = User::where('username', $request->username)->orwhere('email', $request->username)->count();
        if ($transUser == 1) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }

    

    public function orders()
    {
        $pageTitle = 'Orders';
        $orders    = Order::where('user_id', auth()->user()->id)->with('product')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.orders', compact('pageTitle', 'orders'));
    }

    public function salesStockistOrders()
    {
        $pageTitle = 'Sales History';
        $orders    = Order::where('stockist_user_id', auth()->user()->id)->with('product')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.sales_orders', compact('pageTitle', 'orders'));
    }

    public function orders1()
    {
        $pageTitle = 'Orders';
        $orders    = Order::where('user_id', auth()->user()->id)->with('product')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.orders1', compact('pageTitle', 'orders'));
    }


    public function sorders()
    {
        $pageTitle = 'Stock Orders';
        $sorders    = Sorder::where('user_id', auth()->user()->id)->with('product')->orderBy('id', 'desc')->paginate(getPaginate());

        return view('Template::user.sorders', compact('pageTitle', 'sorders'));
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
        // Legacy view: Template::user.notifications
        return Inertia::render('User/Notifications', compact('notifications'))->withViewData(compact('pageTitle'));
    }

    public function markNotificationsRead()
    {
        $user = auth()->user();
        NotificationLog::where('user_id', $user->id)->where('user_read', false)->update(['user_read' => true]);
        return response()->json(['success' => true]);
    }
}
