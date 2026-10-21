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

        $weeklyGrowth = Shtransaction::where('user_id', $user->id)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->where('type', 'credit')
            ->completed()
            ->sum('amount');

        $historyLabels = [];
        $historyData = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $label = $day->format('D');
            $val = Shtransaction::where('user_id', $user->id)
                ->where('type', 'credit')
                ->completed()
                ->whereDate('created_at', $day->toDateString())
                ->sum('amount');

            $historyLabels[] = $label;
            $historyData[] = $val;
        }

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
                'amount' => $group->sum('amount')
            ])
            ->values();

        return view('Template::user.investment-portfolio', compact(
            'pageTitle',
            'investments',
            'totalUnits',
            'totalInvestmentValue', 
            'totalDividends',
            'dividendTransactions',
            'dividendsByPlan'
        ));
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

        return view('Template::user.investment-details', compact(
            'pageTitle',
            'investment',
            'dividends',
            'totalDividends'
        ));
    }

    public function myAwards()
    {
        $pageTitle  = 'My Awards';
        $user       = auth()->user();
        $allAwards  = Award::active()->orderBy('sort_order')->get();
        $earnedMap  = UserAward::where('user_id', $user->id)
                        ->get()
                        ->keyBy('award_id');

        // Same PV source used by CheckUserAwardJob to qualify awards.
        $matrix        = Matrix::where('user_id', $user->id)->where('stage_id', 1)->first();
        $currentLeftPv  = (float) ($matrix->pv_left  ?? 0);
        $currentRightPv = (float) ($matrix->pv_right ?? 0);
        $currentTotalPv = $currentLeftPv + $currentRightPv;

        return view('Template::user.awards', compact(
            'pageTitle', 'allAwards', 'earnedMap', 'currentLeftPv', 'currentRightPv', 'currentTotalPv'
        ));
    }

    public function repurchaseAward()
    {
        $pageTitle      = 'Repurchase (Unilevel) Award';
        $user           = auth()->user();
        $pvRecord       = RepurchasePv::where('user_id', $user->id)->first();
        $totalPv        = $pvRecord ? (float) $pvRecord->total_pv : 0;
        $awards         = RepurchaseAward::active()->get();
        $walletBalance  = (float) ($user->repurchase_award ?? 0);

        return view('Template::user.repurchase_award', compact('pageTitle', 'totalPv', 'awards', 'walletBalance'));
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

        $logs = $this->pvData($type)->where('user_id', $uid)->latest('id')->paginate(getPaginate());

        // Summary totals (always computed against the full user dataset)
        $totalLeft  = PvLog::leftPV()->where('user_id',  $uid)->sum('amount');
        $totalRight = PvLog::rightPV()->where('user_id', $uid)->sum('amount');
        $totalCut   = PvLog::cutPV()->where('user_id',   $uid)->sum('amount');
        $totalAll   = PvLog::paidPV()->where('user_id',  $uid)->sum('amount');

        return view('Template::user.pvLog', compact(
            'pageTitle', 'logs',
            'totalLeft', 'totalRight', 'totalCut', 'totalAll'
        ));
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

        $rinvest = Rinvestment::with('user')->find($request->rivest_id);
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

    

    public function shares()
    {
        $pageTitle = 'Shares Dashboard';
        $user      = auth()->user();

        $plans = Plan::orderBy('price', 'asc')->active()->get();

        $investments = Rinvestment::where('user_id', $user->id)
            ->with('plan')
            ->get();

        $totalUnits = $investments->sum('units');
        $totalValue = $investments->sum(fn($inv) => $inv->units * $inv->unit_cost);

        $weeklyGrowth = Shtransaction::where('user_id', $user->id)
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->where('type', 'credit')
            ->completed()
            ->sum('amount');

        $historyLabels = [];
        $historyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $historyLabels[] = $day->format('D');
            $historyData[] = Shtransaction::where('user_id', $user->id)
                ->where('type', 'credit')
                ->completed()
                ->whereDate('created_at', $day->toDateString())
                ->sum('amount');
        }

        $goalTarget = 50000;
        $goalPercent = $goalTarget > 0 ? min(100, ($totalValue / $goalTarget) * 100) : 0;

        return view('Template::user.shares', compact(
            'pageTitle',
            'plans',
            'investments',
            'totalUnits',
            'totalValue',
            'weeklyGrowth',
            'historyLabels',
            'historyData',
            'goalTarget',
            'goalPercent'
        ));
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

            $newInvestment = $rinvest->fresh();

            $notify[] = ['success', 'Purchased ' . $quantity . ' units of ' . $plan->name . ' successfully.'];

            if ($request->ajax()) {
                return response()->json([
                    "status" => "success",
                    "message" => "Purchased $quantity units of $plan->name successfully.",
                    "totalUnits" => $user->rinvestment()->sum('units'),
                    "totalValue" => showAmount($user->rinvestment()->sum(fn($i) => $i->units * $i->unit_cost)),
                    "newInvestment" => [
                        'plan_name' => $plan->name,
                        'units' => $quantity,
                        'unit_cost' => showAmount($plan->price),
                        'total' => showAmount($totalPayable),
                        'status' => 'Active',
                        'details_url' => route('plan.details', $newInvestment->id),
                    ],
                    "goalPercent" => min(100, ($user->rinvestment()->sum(fn($i) => $i->units * $i->unit_cost)/50000)*100),
                ]);
            }
        } catch (InsufficientBalanceException) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(["status" => "error", "message" => "Insufficient Balance."], 422);
            }
            $notify[] = ['error', 'Insufficient Balance.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(["status" => "error", "message" => "Error completing purchase."], 500);
            }
            $notify[] = ['error', 'Error completing purchase.'];
        }

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
        return view('Template::user.myRef', compact('pageTitle', 'logs'));
    }

 
    public function myTree()
    {  
       
        $tree      = showMatrixTree(auth()->user()->id);
        //dd($tree);
        $pageTitle = "My Tree";
        $user      = auth()->user();  
        return view('Template::user.myTree', compact('pageTitle', 'tree', 'user'));
    }

    public function myTreeStages()
    {     
        $stt = returnCurrentMatrixStage(auth()->user()->id);
        //dd($st); 
        if($stt->stage_id == 6){
            $stt = Matrix::where('user_id', auth()->user()->id)->where('stage_id', 5)->first();
        }
        $st = $stt->stage_id;
        // $tree      = showMatrixStageTree(auth()->user()->id, $st);

        //$tree      = showMatrixTree(auth()->user()->id);
        $tree      = showMatrixStageTree(auth()->user()->id, $st);
       
        //dd($tree);
        $pageTitle = "My Tree";
        
        $user      = auth()->user();
        return view('Template::user.myStages', compact('pageTitle', 'tree', 'user', 'st'));
    }
 
    public function myTreeStage($stage = 1)
    { 
        $st =  sanitizeInput($stage); 

        $tree      = showMatrixStageTree(auth()->user()->id, $st);
        //dd($tree);
        $pageTitle = "My Tree";  
        $user      = auth()->user();
        return view('Template::user.myStages', compact('pageTitle', 'tree', 'user', 'st'));
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
            return view('Template::user.myTree', compact('pageTitle', 'tree', 'user'));
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

        return view('Template::user.binaryList', compact('pageTitle', 'paginator', 'notInTree', 'searchUsername', 'total'));
    }


}
