<?php

namespace App\Http\Controllers\User\Auth;

use App\Models\Pin;
use App\Models\User;
use App\Lib\Intended;
use App\Constants\Status;
use App\Models\UserExtra;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Models\Matrix;
use App\Models\Rmatrix;
use App\Models\Transaction;
use App\Models\MatrixStage;
use App\Models\UserStageProgress;
use App\Services\MatrixPlacementService;
use Illuminate\Support\Facades\DB;

use Illuminate\Validation\Rule;
class RegisterController extends Controller
{

    use RegistersUsers;
    protected $MatrixPlacementService;

    public function __construct(MatrixPlacementService $matrixPlacementService)
    {
        parent::__construct();
        $this->matrixPlacementService = $matrixPlacementService;
    }
    

    public function showRegistrationForm(Request $request)
    {
        $pageTitle = "Register"; 
         $refUser = null; 
        if ($request->ref) {
            $refUser = User::where('username', $request->ref)->first();
            if ($refUser == null) {
                $notify[] = ['error', 'Invalid Referral link.'];
                return redirect()->route('home')->withNotify($notify);
            }

            $refUser = User::where('username', $request->ref)->where('status', Status::USER_ACTIVE)->first();

            if ($refUser == null) {
                $notify[] = ['error', 'Your Referral is not active. pls contact  Admin or your Referral'];
                return redirect()->route('home')->withNotify($notify);
            }
            
        } 

        

        
        $info       = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $projects   = \App\Models\Project::active()->get();

        Intended::identifyRoute();

        return view('Template::user.auth.register', compact('pageTitle', 'refUser', 'countries', 'mobileCode', 'projects'));
    }


    protected function validator(array $data)
    {

        $passwordValidation = Password::min(6);

        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $agree = 'nullable';
        if (gs('agree')) {
            $agree = 'required'; 
        }

        $countryData  = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));

        
        

            $validate     = Validator::make($data, [
                'firstname' => 'required',
                'lastname'  => 'required',
                'username'     => 'required|unique:users|min:6',
                'country_code' => 'required|in:' . $countryCodes,
                'country'      => 'required|in:' . $countries,
                'mobile_code'  => 'required|in:' . $mobileCodes,
            
                // 'activation_pin'     => 'required|exists:pins,pin',
                'email'     => 'required|string|email',
                'password'  => ['required', 'confirmed', $passwordValidation],
                'captcha'   => 'sometimes|required',
                'agree'     => $agree
            ], [
                'firstname.required' => 'The first name field is required',
                'lastname.required' => 'The last name field is required'
            ]);


       
        return $validate;
    }
    public function register(Request $request)
    {

        $this->validator($request->all())->validate();
        $general = gs();
        $trx = getTrx(20);

        if (preg_match("/[^A-Za-z0-9_]/", trim($request->username))) {
            $notify[] = ['info', 'Username can contain only Big/small letters, numbers and underscore.'];
            $notify[] = ['error', 'No special character or space.'];
            return back()->withNotify($notify)->withInput($request->all());
        }
         
        if($request->parent){
            
                if (preg_match("/[^a-zA-Z0-9_]/", $request->parent)) {
               
                    $notify[] = ['error', 'No special character or space  in Parent name.'];
                    return back()->withNotify($notify)->withInput($request->all());
                }
        }

        if($request->position){
            if (preg_match("/[^a-z]/", $request->position)) {
           
                $notify[] = ['error', 'Position is invalid.'];
                return back()->withNotify($notify)->withInput($request->all());
            }
        }

        if ($request->referBy) {
                // code...
            if (preg_match("/[^a-zA-Z0-9_]/", $request->referBy)) {
           
                $notify[] = ['error', 'No special character or space  in username.'];
                    return back()->withNotify($notify)->withInput($request->all());
            }


            // check is the palcement username is your downline as Binary matrix does not allow board crossing.
            $referBy = User::where('username', $request->referBy)->first();

            //checkDownline_new(Matrix $sponsor_matrix, Matrix $user_placement_matrix)
            if ($request->parent && $referBy) {
                $parent = User::where('username', $request->parent)->first();
                if ($parent) {
                    if(!checkDownline($referBy->id,  $parent->id)){
                        $notify[] = ['error', 'Parent must be under Sponsor tree. No Tree crossing'];
                        return back()->withNotify($notify)->withInput($request->all());
                    }
                }
            }
        }

        if (!gs('registration')) {
            return back();
        }

        $request->session()->regenerateToken();

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        if(!$request->referBy){
            $us = 1;
            
        }else{
            $userCheck = User::where('username', $request->referBy)->first();
            //$pos = getPosition($userCheck->id, $data['position']);
            if($userCheck){
                $us = $userCheck->id;
            }else{
                $notify[] = ['error', 'The Referral is not in the system.'];
                return back()->withNotify($notify)->withInput($request->all());
            }
           
        }
        //dd($request);
        $position = '';

        DB::beginTransaction();

        try {
            $user  = new User();
            // checking if parent exist

 
           if($request->parent){
                 $parent = User::where('username', $request->parent)->first();      
                if(!$parent){
                        $notify[] = ['error', 'Parent is not in the network..'];
                    return back()->withNotify($notify)->withInput($request->all());
                }

                // later check if this parent is still in stage one
                 $user_parent_matrix = Matrix::where('user_id', $parent->id)->where($request->position, 0)->where('stage_id', 1)->where('is_active', 1)->first();
                 //dd($user_matrix);

                 // check if the parent is in the matrix before assignment to new user

                if(!$user_parent_matrix){
                    $notify[] = ['error', 'Chose another position. Parent '.$request->position.' is taken'];
                    return back()->withNotify($notify)->withInput($request->all());
                }
                
                // all check has been completed move to assignment 
                //dd($user_parent_matrix);

                $user->parent    = $request->parent;
                $user->position  = $request->position;// NOTE: to be crab later

                $parent_id = $parent->id; //set parent ID   


                if($user_parent_matrix){

                    //User Create
                    $user->country_code = $request->country_code;
                    $user->mobile       = $request->mobile;
                    $user->address      = $request->address;
                    $user->city         = $request->city;
                    $user->state        = $request->state;
                    $user->country_name = @$request->country;
                    $user->dial_code    = $request->mobile_code;
                    $user->section      = 1;

                    $user->trx          =    $trx;
                    $user->username     = $request->username;
                    $user->ref_by       = $us;
                    $user->pos_id       = 1;
                    $user->email     = $request->email;
                    $user->firstname = $request->firstname; 
                    $user->lastname  =  $request->lastname;
                    // $user->pin  = $data['activation_pin'];
                    $user->password  = Hash::make($request->password);
                    $user->kv = Status::NO;
                    $user->ev = Status::YES;
                    $user->sv = Status::YES;
                    $user->ts = Status::DISABLE;
                    $user->tv = Status::ENABLE;
                    $user->project_id = $request->project_id ?: null;

                    $user->save();
                    //dd($user_parent_matrix);
                  //$user->profile_complete = Status::YES;
                  $rmatrix  = new Rmatrix();
                    if($request->position == 'left'){
                       // $user_parent_matrix->left = $user->id;
                        //$user_parent_matrix->save();
                        $position = 'left';
                        $rmatrix->position = 'left';
                    }
                    if($request->position == 'right'){
                       //$user_parent_matrix->right = $user->id;
                        //$user_parent_matrix->save();
                        $position = 'right';
                        $rmatrix->position = 'right';

                    }
                    
                   // dd($position);
                   
                   $rmatrix->user_id = $user->id;
                   $rmatrix->parent_id = $parent_id;
                   $rmatrix->stage_id = 1;
                   $rmatrix->save();
                   
                   
                   //dd($position);
                      
                }else{
                   $rmatrix  = new Rmatrix();
                   $rmatrix->user_id = $user->id;
                   $rmatrix->parent_id = $parent_id;
                   $rmatrix->position = $position;
                   $rmatrix->stage_id = 1;
                   $rmatrix->save();
                } 
            

            }else{
                
                 $parent_id = 0; //set parent ID
                //User Create
                $user->country_code = $request->country_code;
                $user->mobile       = $request->mobile;
                $user->address      = $request->address;
                $user->city         = $request->city;
                $user->state        = $request->state;
                $user->country_name = @$request->country;
                $user->dial_code    = $request->mobile_code;
                $user->section      = 1;
                $user->trx          =    $trx;
                $user->username     = $request->username;
                $user->ref_by       = $us;
                $user->pos_id       = 1;
                $user->email     = $request->email;
                $user->firstname = $request->firstname; 
                $user->lastname  =  $request->lastname;
                // $user->pin  = $data['activation_pin'];
                $user->password  = Hash::make($request->password);
                //$user->kv = gs('kv') ? Status::NO : Status::YES;
                //$user->ev = gs('ev') ? Status::NO : Status::YES;
                //$user->sv = gs('sv') ? Status::NO : Status::YES;
                $user->kv = Status::NO;
                $user->ev = Status::YES;
                $user->sv = Status::YES;
                $user->ts = Status::DISABLE;
                $user->status = Status::ACTIVE;
                $user->tv = Status::ENABLE;
                $user->project_id = $request->project_id ?: null;
                //$user->profile_complete = Status::YES;
                $user->save();

                $position = 0;
                $rmatrix  = new Rmatrix();
                $rmatrix->user_id = $user->id;
                $rmatrix->parent_id = $parent_id;
                $rmatrix->position = $position;
                $rmatrix->stage_id = 1;
                $rmatrix->save();
                
                
            }

                $matrixRoot = $parent_id;
                // build this soo to allow the system place user in any position that is free under the user.
                //$placement = $this->findPlacementNode($matrixRoot, $stage);

                


                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                //throw $e;
                $notify[] = ['error', 'Please refresh your browser and try again'];
                return back()->withNotify($notify)->withInput($request->all());
            }
         
        $this->guard()->login($user);
        return to_route('user.home');

        //return $this->registered($request, $user) ?: redirect($this->redirectPath());
    }


    protected function create(array $data)
    {
        
        if($data['referBy'] == '' || $data['referBy'] == null){
            $us = 1;
            
        }else{
            $userCheck = User::where('username', $data['referBy'])->first();
            //$pos = getPosition($userCheck->id, $data['position']);
            $us = $userCheck->id;
        }
        
        DB::beginTransaction();
            try {
                if($user_parent_matrix){
                    if($request->position === 'left'){
                        $user_parent_matrix->left = $user->id;
                        $user_parent_matrix->save();
                    }
                    if($request->position === 'right'){
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
                //referralStageMAtrix($user->id, $details, 1, 1);

                DB::commit();
            } catch (\Throwable $e) {
                    DB::rollBack();
                    throw $e;
            }
        
        

        //User Create
        $user            = new User();
        $user->username     = $request->username;
        $user->ref_by       = $us;
        $user->pos_id       = 1;
        $user->email     = strtolower($data['email']);
        $user->firstname = $data['firstname'];
        $user->lastname  = $data['lastname'];
        // $user->pin  = $data['activation_pin'];
        $user->password  = Hash::make($data['password']);
        $user->kv = Status::NO;
        $user->ev = Status::YES;
        $user->sv = Status::YES;
        $user->ts = Status::DISABLE;
        $user->tv = Status::ENABLE;
        $user->profile_complete = Status::YES;
        $user->save();

        $details = 'Bonus gotten from username: '.$user->username;
               // referralStageMAtrix($user->id, $details, 1, 1);


        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New member registered';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();


        //Login Log Create
        $ip        = getRealIP();
        $exist     = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();

        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();


        return $user;
    }

    public function checkUser(Request $request)
    {
        $exist['data'] = false;
        $exist['type'] = null;
        if ($request->email) { 
            $exist['data'] = User::where('email', $request->email)->exists();
            $exist['type'] = 'email';
            $exist['field'] = 'Email';
        }
        if ($request->mobile) {
            $exist['data'] = User::where('mobile', $request->mobile)->where('dial_code', $request->mobile_code)->exists();
            $exist['type'] = 'mobile';
            $exist['field'] = 'Mobile';
        }
        if ($request->username) {
            $exist['data'] = User::where('username', $request->username)->exists();
            $exist['type'] = 'username';
            $exist['field'] = 'Username';
        }
        return response($exist);
    }

    public function registered(Request $request, $user)
    {
        $user_extras = new UserExtra();
        $user_extras->user_id = $user->id;
        $user_extras->save();
        updateFreeCount($user->id);
        return to_route('user.home');
    }
}
