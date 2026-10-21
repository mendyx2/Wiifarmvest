<?php

namespace App\Http\Controllers\User;



use App\Models\Plan;
use App\Models\User;
use App\Models\BvLog;
use App\Models\Award;
use App\Models\UserAward;
use App\Models\RepurchasePv;
use App\Models\RepurchaseAward;
use App\Models\PvLog;
use App\Models\Matrix;
use App\Models\UserExtra;
use App\Models\Rinvestment;
use App\Models\Investment;
use App\Models\Shtransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\TreePresenter;
use App\Http\Controllers\Controller;
use App\Services\MatrixPlacementService;
use App\Services\WalletService;
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class PlanController extends Controller
{

    protected $matrixService;
    
    public function __construct(MatrixPlacementService $matrixService)
    {
        $this->matrixService = $matrixService;
    }
    /**
     * Show user investment portfolio
     */
    public function investmentPortfolio()
    {
        $pageTitle = 'My Shares Portfolio';
        $user = auth()->user();

        // Get all user investments
        $investments = Rinvestment::where('user_id', $user->id)
            ->with('plan', 'shtransactions')
            ->get();

        // Calculate totals
        $totalUnits = $investments->sum('units');
        $totalInvestmentValue = $investments->sum(fn($inv) => $inv->units * $inv->plan->price);

        // (A weekly-growth figure and a 7-day chart series were computed here with
        // 8 extra queries, but the page never used them; they were removed.)

        // Get all dividends for user
        $dividendTransactions = Shtransaction::where('user_id', $user->id)
            ->with('rinvestment.plan', 'dividendBatch')
            ->latest()
            ->paginate(10);

        $totalDividends = Shtransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->completed()
            ->sum('amount') ?? 0;

        // Get dividends grouped by plan
        $dividendsByPlan = Shtransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->completed()
            ->with('rinvestment.plan')
            ->get()
            ->groupBy(fn($t) => $t->rinvestment->plan->name)
            ->map(fn($group) => [
                'plan' => $group->first()->rinvestment->plan->name,
                'amount' => showAmount($group->sum('amount')),
            ])
            ->values();

        // Migrated to React + Inertia (resources/js/Pages/User/InvestmentPortfolio.tsx).
        // Legacy view: Template::user.investment-portfolio
        return Inertia::render('User/InvestmentPortfolio', [
            'totalUnits'      => (int) $totalUnits,
            'investmentValue' => showAmount($totalInvestmentValue),
            'totalDividends'  => showAmount($totalDividends),
            'investments'     => $investments->map(fn ($inv) => [
                'id'         => $inv->id,
                'plan'       => $inv->plan->name ?? 'N/A',
                'date'       => $inv->created_at->format('M d, Y - h:i'),
                'units'      => $inv->units,
                'unitCost'   => showAmount($inv->unit_cost),
                'totalValue' => showAmount($inv->units * $inv->unit_cost),
                'url'        => route('user.investment.details', $inv->id),
            ])->values(),
            'dividendsByPlan' => $dividendsByPlan,
            'dividends'       => self::dividendRows($dividendTransactions),
            // Buy Shares pop-up
            'plans' => Plan::active()->get()->map(fn ($plan) => [
                'id'         => $plan->id,
                'name'       => $plan->name,
                'price'      => (float) $plan->price,
                'priceLabel' => showAmount($plan->price),
            ])->values(),
            'balance'     => showAmount($user->balance),
            'purchaseUrl' => route('user.shares.purchase'),
        ])->withViewData(compact('pageTitle'));
    }

    /** Dividend transactions as the React pages show them. */
    private static function dividendRows($paginator)
    {
        return $paginator->through(fn ($t) => [
            'id'        => $t->id,
            'date'      => $t->created_at->format('M d, Y'),
            'time'      => $t->created_at->format('H:i'),
            'reference' => $t->reference,
            'plan'      => $t->rinvestment->plan->name ?? 'N/A',
            'units'     => $t->units_held,
            'perUnit'   => showAmount($t->amount_per_unit),
            'amount'    => showAmount($t->amount),
            'status'    => in_array($t->status, ['completed', 'pending'], true) ? $t->status : 'failed',
        ]);
    }

    /**
     * Show investment details
     */
    public function investmentDetails($investmentId)
    {
        $pageTitle = 'Investment Details';
        $user = auth()->user();

        // Get investment
        $investment = Rinvestment::where('user_id', $user->id)
            ->with('plan')
            ->findOrFail($investmentId);
            //dd($investment);

        // Get all dividends for this specific investment
        $dividends = Shtransaction::where('user_id', $user->id)
            ->where('rinvestment_id', $investmentId)
            ->with('rinvestment.plan', 'dividendBatch')
            ->latest()
            ->paginate(5);

        // Calculate total dividends for this investment
        $totalDividends = Shtransaction::where('user_id', $user->id)
            ->where('rinvestment_id', $investmentId)
            ->where('type', 'credit')
            ->completed()
            ->sum('amount') ?? 0;

        // ROI guarded against a zero amount (the Blade view divided by zero).
        $invested = (float) $investment->five;
        $roi      = $invested > 0 ? ($totalDividends / $invested) * 100 : 0;
        $recent   = $dividends->getCollection()->take(5);

        // Migrated to React + Inertia (resources/js/Pages/User/InvestmentDetails.tsx).
        // Legacy view: Template::user.investment-details
        return Inertia::render('User/InvestmentDetails', [
            'investment' => [
                'id'          => $investment->id,
                'plan'        => $investment->plan->name ?? 'Investment',
                'date'        => $investment->created_at->format('M d, Y H:i'),
                'units'       => $investment->units,
                'unitCost'    => showAmount($investment->unit_cost),
                'total'       => showAmount($investment->unit_cost * $investment->units),
                'invested'    => showAmount($invested),
                'totalReturn' => showAmount($invested + $totalDividends),
            ],
            'totalDividends' => showAmount($totalDividends),
            'roi'            => round($roi, 2),
            'dividends'      => self::dividendRows($dividends),
            'recent'         => $recent->map(fn ($d) => ['id' => $d->id, 'date' => $d->created_at->format('M d, Y'), 'reference' => $d->reference, 'amount' => showAmount($d->amount)])->values(),
            'moreCount'      => max(0, $dividends->total() - $recent->count()),
            // Receipt / certificate downloads are plain form posts (the reply is a PDF file).
            'pdfUrl'       => route('user.plan.payment-receipt.generate-pdf'),
            'portfolioUrl' => route('user.investment.portfolio'),
        ])->withViewData(compact('pageTitle'));
    }

    public function myAwards()
    {
        $pageTitle  = 'My Awards';
        $user       = auth()->user();
        $allAwards  = Award::active()->with('prerequisite')->orderBy('sort_order')->get();
        $earnedMap  = UserAward::where('user_id', $user->id)
                        ->get()
                        ->keyBy('award_id');

        // Same PV source used by CheckUserAwardJob to qualify awards.
        $matrix        = Matrix::where('user_id', $user->id)->where('stage_id', 1)->first();
        $currentLeftPv  = (float) ($matrix->pv_left  ?? 0);
        $currentRightPv = (float) ($matrix->pv_right ?? 0);
        $currentTotalPv = $currentLeftPv + $currentRightPv;

        $pct = fn ($have, $need) => $need > 0 ? min(100, round($have / $need * 100)) : 100;

        // Migrated to React + Inertia (resources/js/Pages/User/Awards.tsx).
        // Legacy view: Template::user.awards
        return Inertia::render('User/Awards', [
            'stats' => [
                'total'  => $allAwards->count(),
                'earned' => $earnedMap->count(),
                'paid'   => $earnedMap->where('status', 1)->count(),
            ],
            'awards' => $allAwards->map(function ($award) use ($earnedMap, $currentLeftPv, $currentRightPv, $currentTotalPv, $pct) {
                $earned = $earnedMap->get($award->id);
                $state  = !$earned ? 'locked' : ($earned->status == 1 ? 'paid' : 'earned');
                $locked = $state === 'locked';
                // Progress only means something while locked: pairing PV is used up
                // by matching payouts, so earned/paid awards always show complete.
                $leg = fn ($have, $need) => [
                    'text' => $locked ? number_format($have, 0) . ' / ' . number_format($need, 0) : number_format($need, 0),
                    'pct'  => $locked ? $pct($have, $need) : 100,
                ];
                return [
                    'id'           => $award->id,
                    'order'        => $award->sort_order,
                    'name'         => $award->name,
                    'description'  => \Illuminate\Support\Str::limit($award->description, 72) ?: 'No description provided.',
                    'image'        => $award->image_url,
                    'state'        => $state,
                    'total'        => $leg($currentTotalPv, $award->required_total_pv),
                    'left'         => $leg($currentLeftPv, $award->required_left_pv),
                    'right'        => $leg($currentRightPv, $award->required_right_pv),
                    'reward'       => showAmount($award->payment_amount),
                    'paidAmount'   => $state === 'paid' ? showAmount($earned->paid_amount ?? $award->payment_amount) : null,
                    'prerequisite' => $award->prerequisite?->name,
                    'date'         => $state === 'paid' ? ($earned->paid_at?->format('M d, Y') ?? '—') : ($earned ? ($earned->earned_at?->format('M d, Y') ?? '—') : null),
                ];
            })->values(),
        ])->withViewData(compact('pageTitle'));
    }

    public function repurchaseAward()
    {
        $pageTitle      = 'Repurchase (Unilevel) Award';
        $user           = auth()->user();
        $pvRecord       = RepurchasePv::where('user_id', $user->id)->first();
        $totalPv        = $pvRecord ? (float) $pvRecord->total_pv : 0;
        $awards         = RepurchaseAward::active()->get();
        $walletBalance  = (float) ($user->repurchase_award ?? 0);

        // Migrated to React + Inertia (resources/js/Pages/User/RepurchaseAward.tsx).
        // Legacy view: Template::user.repurchase_award
        return Inertia::render('User/RepurchaseAward', [
            'totalPv'       => number_format($totalPv, 2),
            'walletBalance' => '₦' . number_format($walletBalance, 2),
            'hasBalance'    => $walletBalance > 0,
            'awards'        => $awards->map(function ($award) use ($totalPv) {
                $required = (float) $award->required_pv;
                return [
                    'id'        => $award->id,
                    'title'     => $award->title,
                    'required'  => number_format($required, 2),
                    'qualified' => $totalPv >= $required,
                    'pct'       => $required > 0 ? min(100, round($totalPv / $required * 100, 1)) : 100,
                    'remaining' => number_format(max(0, $required - $totalPv), 2),
                    'reward'    => '₦' . number_format($award->amount, 2),
                ];
            })->values(),
            'transferUrl' => route('user.bonus.transfer.index'),
        ])->withViewData(compact('pageTitle'));
    }

    public function pvlog(Request $request){
        $uid  = auth()->id();
        $type = $request->type;

        $pageTitle = match($type) {
            'leftPV'  => 'Left PV Log',
            'rightPV' => 'Right PV Log',
            'cutPV'   => 'Cut PV Log',
            default   => 'PV Log',
        };  

        $logs = $this->pvData($type)->where('user_id', $uid)->latest('id')->paginate(getPaginate())->withQueryString();

        // Summary totals (always computed against the full user dataset)
        $totalLeft  = PvLog::leftPV()->where('user_id',  $uid)->sum('amount');
        $totalRight = PvLog::rightPV()->where('user_id', $uid)->sum('amount');
        $totalCut   = PvLog::cutPV()->where('user_id',   $uid)->sum('amount');
        $totalAll   = PvLog::paidPV()->where('user_id',  $uid)->sum('amount');

        $first = $logs->firstItem() ?? 1;

        // Migrated to React + Inertia (resources/js/Pages/User/PvLog.tsx).
        // Legacy view: Template::user.pvLog
        return Inertia::render('User/PvLog', [
            'logs' => $logs->through(function ($log) use (&$first) {
                $isCredit = $log->trx_type == '+';
                return [
                    'id'       => $log->id,
                    'number'   => $first++,
                    'details'  => $log->details ?: 'PV Transaction',
                    'isCredit' => $isCredit,
                    // "cut" = PV deducted, otherwise the leg it was added to
                    'kind'     => !$isCredit ? 'cut' : ($log->position == 1 ? 'left' : 'right'),
                    'amount'   => getAmount($log->amount),
                    'date'     => $log->created_at ? date('d M Y, g:i A', strtotime($log->created_at)) : '—',
                ];
            }),
            'type'   => in_array($type, ['leftPV', 'rightPV', 'cutPV'], true) ? $type : '',
            'totals' => [
                'left'  => getAmount($totalLeft),
                'right' => getAmount($totalRight),
                'cut'   => getAmount($totalCut),
                'paid'  => getAmount($totalAll),
            ],
        ])->withViewData(compact('pageTitle'));
    }
    protected function pvData($scope = null)
    {
        // $scope comes from ?type= in the URL. Only allow the real query scopes:
        // calling any other static method by name (e.g. ?type=truncate) would run it.
        if (in_array($scope, ['leftPV', 'rightPV', 'cutPV', 'paidPV'], true)) {
            $logs = PvLog::$scope();
        } else {
            $logs = PvLog::query();
        }
        return $logs;
    }



    public function show(User $user)
    {
        return response()->json($user);
    }

    public function generatePdf(Request $request){
        $validated = $request->validate([
            'rivest_id' => 'required|numeric',
        ]);

        // Only the logged-in user's own investments: the receipt shows the
        // owner's name, address, phone and email.
        $rinvest = Rinvestment::with('user')->where('user_id', auth()->id())->find($request->rivest_id);
        if (!$rinvest) {
            $notify[] = ['error', 'Cannot download this receipt CODE:FF-109 '];
            return back()->withNotify($notify);
        }
        //dd($rinvest);

        $imagePath = './assets/templates/basic/images/logo/Wordmark.png';
        $imageData = base64_encode(file_get_contents($imagePath));

        $user = $rinvest->user;
        $receipt_no = 'WF100'.$rinvest->id;
        
        // Calculate totals
        $totalLandArea = $rinvest->units;
        $totalAmount = $rinvest->units * $rinvest->unit_cost;

        $data = [
            'user' => $user,
            'rinvest' =>$rinvest,
            'receipt_no' => $receipt_no,
            'image' => $imageData,
        ];

        if ($request->check == 1) {
            $pdf = Pdf::loadView('Template::user.pdf', $data);
            return $pdf->download('payment-receipt-WF' . $rinvest->id. '24041988.pdf');
        }
        $pdf = Pdf::loadView('Template::user.pdf1', $data);
            return $pdf->download('provisional_certificate_of_allocation_of_fractional_farmland.pdf');
        
    }

    

    // remember to edit the one at admin/controller/ManageUsersController.php
    public function sharesStore(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer',
            'qtys' => 'required|integer|min:5',
        ], [
            'qtys.min' => 'Minimum purchase is 5 units.',
        ]);

        $plan = Plan::active()->find($request->plan_id);

        if (!$plan) {
            $notify[] = ['error', 'The selected plan does not exist or is unavailable.'];
            return back()->withNotify($notify);
        }

        $user = auth()->user();
        $quantity = (int) $request->qtys;
        $totalPayable = $plan->price * $quantity;

        if ($user->balance < $totalPayable) {
            $notify[] = ['error', 'Insufficient Balance.'];
            return back()->withNotify($notify);
        }

        $trx_no = getTrx();

        DB::beginTransaction();
        try {
            // Lock the plan so two buyers can't both take the last units
            $plan = Plan::lockForUpdate()->find($plan->id);
            $plan->quantity -= $quantity;
            if ($plan->quantity < 0) {
                DB::rollBack();
                $notify[] = ['error', 'Not enough units left in plan.'];
                return back()->withNotify($notify);
            }
            $plan->save();

            // Locked debit: re-checks the balance, so the early check above
            // is only a fast path for the common case
            app(WalletService::class)->debit(
                $user,
                (float) $totalPayable,
                'shares_purchase',
                'Purchased ' . $plan->name . ' shares',
                $trx_no,
            );

            $rinvest = new Rinvestment();
            $rinvest->five = $totalPayable;
            $rinvest->plan_id = $plan->id;
            $rinvest->user_id = $user->id;
            $rinvest->unit_cost = $plan->price;
            $rinvest->units = $quantity;
            $rinvest->trx = $trx_no;
            $rinvest->status = 1;
            $rinvest->save();


            //Process the share pass to upline up to 7th Gen
            $this->sharesUplinerCommision($user, $trx_no, $totalPayable);

            DB::commit();
        } catch (InsufficientBalanceException) {
            DB::rollBack();
            return $this->sharesPurchaseFailed('Insufficient Balance.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->sharesPurchaseFailed('Error completing purchase.');
        }

        // The purchase is committed from here on, so nothing below may report
        // it as failed. (The removed /user/shares page got a JSON reply built
        // inside the try block; a wrong route name in it made every purchase
        // there answer "Error completing purchase" after the money was taken.)
        $message = 'Purchased ' . $quantity . ' units of ' . $plan->name . ' successfully.';

        $notify[] = ['success', $message];
        return redirect()->route('user.plan.portfolio')->withNotify($notify);
    }


    private function sharesPurchaseFailed(string $message)
    {
        $notify[] = ['error', $message];
        return redirect()->route('user.plan.portfolio')->withNotify($notify);
    }
    protected function sharesUplinerCommision(User $user, $trx_no, $totalPayable)
    {
        $five_percentage = $totalPayable * 0.05;
        $refUser1st = User::find($user->ref_by); 
        if ($refUser1st) {

                //$this->referralLandComission($refUser1st, $details, $percentage, $trx_no);
                $details1 = "Land fractional referral bonus received from ".$user->username." 1st Gen";
                referralLandComission($refUser1st, $details1, 50, $five_percentage, $trx_no);

                //2nd referral
                $refUser_2nd = User::find($refUser1st->ref_by); 
                if ($refUser_2nd) {

                    $details2 = "Land fractional referral bonus received from ".$user->username." 2nd  Gen";

                    referralLandComission($refUser_2nd, $details2, 18, $five_percentage, $trx_no);
                    //3rd referral
                    $refUser_3rd = User::find($refUser_2nd->ref_by); 
                    if ($refUser_3rd) {

                        $details3 = "Land fractional referral bonus received from ".$user->username." 3rd  Gen";
                        referralLandComission($refUser_3rd, $details3, 12, $five_percentage, $trx_no);
                        //4th referral
                        $refUser_4th = User::find($refUser_3rd->ref_by); 
                        if ($refUser_4th) {

                            $details4 = "Land fractional referral bonus received from ".$user->username." 4th  Gen";
                            referralLandComission($refUser_4th, $details4, 10, $five_percentage, $trx_no);
                            //5th referral
                            $refUser_5th = User::find($refUser_4th->ref_by); 
                            if ($refUser_5th) {

                                $details5 = "Land fractional referral bonus received from ".$user->username." 5th  Gen";
                                referralLandComission($refUser_5th, $details5, 5, $five_percentage, $trx_no);
                                //6th referral
                                $refUser_6th = User::find($refUser_5th->ref_by); 
                                if ($refUser_6th) {

                                    $details6 = "Land fractional referral bonus received from ".$user->username." 6th  Gen";
                                    referralLandComission($refUser_6th, $details6, 3, $five_percentage, $trx_no);
                                    //7th referral
                                    $refUser_7th = User::find($refUser_6th->ref_by); 
                                    if ($refUser_7th) {

                                        $details7 = "Land fractional referral bonus received from ".$user->username." 7th  Gen";
                                        referralLandComission($refUser_7th, $details7, 2, $five_percentage, $trx_no); 
                                    }
                                }   
                            }                            
                        }                       
                    }  
                }
            }
    }

    function reservation()
    {
        $pageTitle = "Reserved";
        $rinvest = Rinvestment::where('user_id', auth()->id())->with('plan')->get();
        //dd($rinvest);
        $plans     = Plan::orderBy('price', 'asc')->active()->get();
        return view('Template::user.reservation', compact('pageTitle', 'plans', 'rinvest'));
    }
    
    function reserveStore(Request $request)
    {   
        //dd($request);

        $request->validate([
            'plan_id' => 'required|integer',
            'invest_amount' => 'required|numeric',
            'invest_amount_five' => 'nullable|numeric',
            'units' => 'required|numeric',
            'qtys' => 'required|integer|min:5',

        ], [
            'qtys.min' => 'The minimum purchase quantity is 5 units.',
        ]);

        if ($request->qtys < 5) {
            $notify[] = ['error', 'Minimum purchase is 5 units.'];
            return back()->withNotify($notify);
        }

        $plan = Plan::active()->where('id', $request->plan_id)->find($request->plan_id);


       //dd($request->qtys);

        if (!$plan) {
            $notify[] = ['error', 'The Project is currently unavailable'];
            return back()->withNotify($notify);
        }
 
        $user = auth()->user();
 
        // The unit price always comes from the plan, never from the request:
        // a client-sent price let users buy at any price (or a negative one,
        // which turned the debit into a credit).
        $total_payable = $plan->price * (int) $request->qtys;
        //Ten 10 to be divide on five levels
        $ten_per_of_unit = $plan->price * 0.10;
        $ten_percentage = $ten_per_of_unit * (int)$request->qtys;
        //dd($ten_percentage);

        if ($user->balance < $total_payable) {
            $notify[] = ['error', 'Insufficient Balance!! Please Fund Money Box '];
            //return back()->withNotify($notify);
            //route()
            
            return to_route('user.deposit.index')->withNotify($notify);
            
            
        }
        $trx_no = getTrx();

        //dd($plan->price);
        DB::beginTransaction();

        try {
            
            $plan = Plan::lockForUpdate()->find($plan->id);
            $plan->quantity -= $request->qtys;
            $plan->save();

            app(WalletService::class)->debit(
                $user,
                (float) $total_payable,
                'farm_project',
                'Purchased ' . $plan->name,
                $trx_no,
            );

            $user->plan_id       = $plan->id;
            $user->save();


            $rinvest               = new Rinvestment();
            $rinvest->five         = $total_payable;
            $rinvest->plan_id      = $plan->id;
            $rinvest->user_id      = $user->id;
            $rinvest->unit_cost    = $plan->price;
            $rinvest->units        = $request->qtys;
            $rinvest->trx          = $trx_no;
            $rinvest->status       = 1;
            $rinvest->save();

            $notify[] = ['success', 'Reserved ' . $plan->name . ' successfully'];

            //process referrals
            // 1st referral
            $refUser1st = User::find($user->ref_by); 
            if ($refUser1st) {

                //$this->referralLandComission($refUser1st, $details, $percentage, $trx_no);
                $details1 = "Land fractional referral bonus received from ".$user->username." 1st Gen";
                referralLandComission($refUser1st, $details1, 50, $ten_percentage, $trx_no);

                //2nd referral
                $refUser_2nd = User::find($refUser1st->ref_by); 
                if ($refUser_2nd) {

                    $details2 = "Land fractional referral bonus received from ".$user->username." 2nd  Gen";

                    referralLandComission($refUser_2nd, $details2, 18, $ten_percentage, $trx_no);
                    //3rd referral
                    $refUser_3rd = User::find($refUser_2nd->ref_by); 
                    if ($refUser_3rd) {

                        $details3 = "Land fractional referral bonus received from ".$user->username." 3rd  Gen";
                        referralLandComission($refUser_3rd, $details3, 12, $ten_percentage, $trx_no);
                        //4th referral
                        $refUser_4th = User::find($refUser_3rd->ref_by); 
                        if ($refUser_4th) {

                            $details4 = "Land fractional referral bonus received from ".$user->username." 4th  Gen";
                            referralLandComission($refUser_4th, $details4, 10, $ten_percentage, $trx_no);
                            //5th referral
                            $refUser_5th = User::find($refUser_4th->ref_by); 
                            if ($refUser_5th) {

                                $details5 = "Land fractional referral bonus received from ".$user->username." 5th  Gen";
                                referralLandComission($refUser_5th, $details5, 5, $ten_percentage, $trx_no);
                                //6th referral
                                $refUser_6th = User::find($refUser_5th->ref_by); 
                                if ($refUser_6th) {

                                    $details6 = "Land fractional referral bonus received from ".$user->username." 6th  Gen";
                                    referralLandComission($refUser_6th, $details6, 3, $ten_percentage, $trx_no);
                                    //7th referral
                                    $refUser_7th = User::find($refUser_6th->ref_by); 
                                    if ($refUser_7th) {

                                        $details7 = "Land fractional referral bonus received from ".$user->username." 7th  Gen";
                                        referralLandComission($refUser_7th, $details7, 2, $ten_percentage, $trx_no); 
                                    }
                                }   
                            }                            
                        }                       
                    }  
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            //throw $e;
            $notify[] = ['error', 'Error: ' . $e];
        }
        
        return redirect()->route('user.plan.portfolio')->withNotify($notify);
    }

    public function myRefLog()
    {
        $pageTitle = "My Referral";
        $logs      = User::where('ref_by', auth()->id())->latest()->paginate(getPaginate());
        $first     = $logs->firstItem() ?? 1;
        $refUrl    = route('user.register') . '?ref=' . auth()->user()->username;

        // Migrated to React + Inertia (resources/js/Pages/User/Referrals.tsx).
        // Legacy view: Template::user.myRef
        return Inertia::render('User/Referrals', [
            // Only name, username and join date of each referral: never their email, phone or balance.
            'referrals' => $logs->through(function ($ref) use (&$first) {
                return [
                    'id'       => $ref->id,
                    'number'   => $first++,
                    'fullname' => $ref->fullname,
                    'username' => $ref->username,
                    'joined'   => showDateTime($ref->created_at, 'd M Y'),
                    'ago'      => diffForHumans($ref->created_at),
                ];
            }),
            'refUrl'  => $refUrl,
            'refCode' => strtoupper(auth()->user()->username),
        ])->withViewData(compact('pageTitle'));
    }

 
    public function myTree()
    {  
       
        $tree      = showMatrixTree(auth()->user()->id);
        $pageTitle = "My Tree";
        return $this->renderTree($pageTitle, $tree, auth()->user());
    }

    /**
     * Genealogy tree page (own tree, or a downline member's tree).
     * Migrated to React + Inertia (resources/js/Pages/User/MyTree.tsx).
     * Legacy view: Template::user.myTree
     */
    private function renderTree(string $pageTitle, array $tree, User $owner)
    {
        return Inertia::render('User/MyTree', [
            'owner'     => ['fullname' => $owner->fullname, 'username' => $owner->username],
            'nodes'     => TreePresenter::nodes($tree, slots: true),
            'images'    => TreePresenter::images(),
            'searchUrl' => route('user.other.tree.search'),
        ])->withViewData(compact('pageTitle'));
    }

    /**
     * Stage tree page. Migrated to React + Inertia (resources/js/Pages/User/MyStages.tsx).
     * Legacy view: Template::user.myStages
     */
    private function renderStageTree(string $pageTitle, array $tree, $stage)
    {
        $user = auth()->user();
        return Inertia::render('User/MyStages', [
            'owner'     => ['fullname' => $user->fullname, 'username' => $user->username],
            'stage'     => (int) $stage,
            'stages'    => collect(range(1, 5))->map(fn ($s) => ['number' => $s, 'url' => route('user.my.treeStage', $s)]),
            'nodes'     => TreePresenter::nodes($tree, slots: false),
            'images'    => TreePresenter::images(),
            'searchUrl' => route('user.other.tree.search'),
        ])->withViewData(compact('pageTitle'));
    }

    public function myTreeStages()
    {     
        $stt = returnCurrentMatrixStage(auth()->user()->id);
        if ($stt && $stt->stage_id == 6) {
            $stt = Matrix::where('user_id', auth()->user()->id)->where('stage_id', 5)->first();
        }
        // returnCurrentMatrixStage() returns false for a user without an active
        // matrix row; the page used to crash on ->stage_id. Show stage 1 then.
        $st = $stt ? $stt->stage_id : 1;
        // $tree      = showMatrixStageTree(auth()->user()->id, $st);

        //$tree      = showMatrixTree(auth()->user()->id);
        $tree      = showMatrixStageTree(auth()->user()->id, $st);
        $pageTitle = "My Tree";
        return $this->renderStageTree($pageTitle, $tree, $st);
    }
 
    public function myTreeStage($stage = 1)
    { 
        $st =  sanitizeInput($stage); 

        $tree      = showMatrixStageTree(auth()->user()->id, $st);
        $pageTitle = "My Tree";
        return $this->renderStageTree($pageTitle, $tree, $st);
    }

     public function otherTree(Request $request, $username = null)
    {
        //dd($username);

 
        if ($request->username) {

            $request->validate([
                'username' => 'required|string|exists:users,username'
            ]);

            $user = User::where('username', $request->username)->first();

        } else {
            $username = sanitizeInput($username);
            $user = User::where('username', $username)->first();
        }

        if ($user) {
            // new ones

            $total_Downline_count = $this->matrixService->checkIfIdIs_A_Downline($user->id, auth()->id()); 

            if (!$total_Downline_count) {
               
                $notify[] = ['error', 'User is not in your tree'];
               return back()->withNotify($notify);
            }

            // end here
            $tree      = showMatrixTree($user->id);
            $pageTitle = "Tree of " . $user->fullname;
            return $this->renderTree($pageTitle, $tree, $user);
        }

        $notify[] = ['error', 'Tree Not Found !'];
        return back()->withNotify($notify);

    }

    public function binaryList(Request $request)
    {
        $pageTitle = 'Binary List';
        $user      = auth()->user();

        // One-query adjacency map from Matrix (stage 1): user_id -> {left, right}
        $allMatrices = Matrix::where('stage_id', 1)
            ->select('user_id', 'left', 'right')
            ->get()
            ->keyBy('user_id');

        // BFS via Matrix left/right fields — track level and leg
        $downlineMap = [];
        $queue       = new \SplQueue();
        $queue->enqueue(['id' => $user->id, 'level' => 0, 'leg' => null]);
        $visited = [$user->id => true];

        while (!$queue->isEmpty()) {
            $node   = $queue->dequeue();
            $matrix = $allMatrices->get($node['id']);
            if (!$matrix) continue;

            foreach (['left' => 'Left', 'right' => 'Right'] as $field => $label) {
                $cid = (int) $matrix->{$field};
                if ($cid <= 0 || isset($visited[$cid])) continue;
                $visited[$cid] = true;

                // Leg is fixed at the first level beneath the root
                $leg = $node['leg'] ?? $label;

                $downlineMap[$cid] = ['level' => $node['level'] + 1, 'leg' => $leg];
                $queue->enqueue(['id' => $cid, 'level' => $node['level'] + 1, 'leg' => $leg]);
            }
        }

        // Username search — must be a downline member
        $searchUsername = trim((string) $request->input('username', ''));
        $notInTree      = false;

        if ($searchUsername !== '') {
            $found = User::where('username', $searchUsername)->first();
            if (!$found || !array_key_exists($found->id, $downlineMap)) {
                $notInTree   = true;
                $downlineMap = [];
            } else {
                $downlineMap = [$found->id => $downlineMap[$found->id]];
            }
        }

        // Manual pagination over the collected ids
        $perPage     = 20;
        $currentPage = max(1, (int) $request->input('page', 1));
        $total       = count($downlineMap);
        $pageIds     = array_slice(array_keys($downlineMap), ($currentPage - 1) * $perPage, $perPage, true);

        $members = collect();

        if (!empty($pageIds)) {
            $usersById    = User::whereIn('id', $pageIds)->get()->keyBy('id');
            $matricesById = \App\Models\Matrix::whereIn('user_id', $pageIds)
                ->where('stage_id', 1)
                ->get()
                ->keyBy('user_id');

            // Collect the direct left/right child user-ids so we can resolve
            // their usernames in a single query instead of N+1 lookups.
            $childUserIds = $matricesById->flatMap(function ($m) {
                return collect([$m->left, $m->right])->filter(fn ($id) => (int) $id > 0);
            })->unique()->values()->all();

            $childUsers = $childUserIds
                ? User::whereIn('id', $childUserIds)->select('id', 'username')->get()->keyBy('id')
                : collect();

            $projectIds = $usersById->pluck('project_id')->filter()->unique();
            $projects   = \App\Models\Project::whereIn('id', $projectIds)->get()->keyBy('id');

            foreach ($pageIds as $uid) {
                $u = $usersById->get($uid);
                if (!$u) continue;

                $matrix = $matricesById->get($uid);

                // Resolve direct left / right occupants (null when slot is open)
                $u->_matrix     = $matrix;
                $u->_left_user  = $matrix && (int) $matrix->left  > 0 ? $childUsers->get($matrix->left)  : null;
                $u->_right_user = $matrix && (int) $matrix->right > 0 ? $childUsers->get($matrix->right) : null;
                $u->_level      = $downlineMap[$uid]['level'];
                $u->_leg        = $downlineMap[$uid]['leg'];
                $u->_project    = $projects->get($u->project_id);

                $members->push($u);
            }
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $members,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $first = $paginator->firstItem() ?? 1;
        $slot  = fn ($member, $child, $side) => $child
            ? ['username' => $child->username]
            : ['url' => route('user.distributor.index') . '?parent=' . urlencode($member->username) . '&position=' . $side];

        // Migrated to React + Inertia (resources/js/Pages/User/BinaryList.tsx).
        // Legacy view: Template::user.binaryList
        return Inertia::render('User/BinaryList', [
            'members' => $paginator->through(function ($m) use (&$first, $slot) {
                return [
                    'id'       => $m->id,
                    'number'   => $first++,
                    'fullname' => $m->fullname,
                    'username' => $m->username,
                    'level'    => $m->_level,
                    'leg'      => $m->_leg,
                    'plan'     => $m->_project->title ?? null,
                    'left'     => $slot($m, $m->_left_user, 'left'),
                    'right'    => $slot($m, $m->_right_user, 'right'),
                    'joined'   => showDateTime($m->created_at, 'd M Y'),
                    'ago'      => diffForHumans($m->created_at),
                ];
            }),
            'total'          => $total,
            'search'         => $searchUsername,
            'notInTree'      => $notInTree,
            'distributorUrl' => route('user.distributor.index'),
        ])->withViewData(compact('pageTitle'));
    }


}
