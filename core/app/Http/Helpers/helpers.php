<?php

use Carbon\Carbon;
use App\Lib\Captcha;
use App\Models\Pin;
use App\Models\Plan;
use App\Models\User;
use App\Models\PvLog;
use App\Models\BvLog;
use App\Notify\Notify;
use App\Models\Matrix;
use App\Models\Rmatrix;
use App\Models\Product;
use App\Models\Project;
use App\Models\Stockist;
use App\Models\Sorder;
use App\Models\Supply;
use App\Models\UserLogin;
use App\Models\Useridcard;
use App\Models\MatrixStage;
use App\Lib\ClientInfo;
use App\Lib\CurlRequest;
use App\Lib\FileManager;
use App\Models\Frontend;
use App\Constants\Status;
use App\Models\Extension;
use App\Models\UserExtra;
use Illuminate\Support\Str; 
use App\Models\Stockist_store;
use App\Models\Stockist_store_record; 
use App\Models\GeneralSetting;
use Laramin\Utility\VugiChugi;
use App\Lib\GoogleAuthenticator;
use App\Services\MatrixPlacementService2;
use App\Models\Transaction;
use App\Models\Stransaction;
use App\Models\StageOut;
use App\Models\Commission;
use App\Models\Referral;
use App\Models\Loan;
use App\Models\SavingsProduct;
use Illuminate\Support\Facades\Cache;
use App\Models\AdminNotification;
use App\Models\ProductStatePrice;
use App\Models\UserStageProgress;
use App\Models\WelcomePackage;
use App\Services\WelcomePackageService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function newTransaction(User $user, $details, $remark, $amount, $trx_type = '+', $trx = 'null', $bonus_type = 1, $charge = 0, $field_post_balance = null){


    $field_post_balance = $field_post_balance ?? null;


    $trx = $trx ?? getTrx(10);
    $transaction               = new Transaction();
    $transaction->user_id      = $user->id;
    $transaction->amount       = $amount;
    $transaction->charge       = $charge;
    $transaction->trx_type     = $trx_type;
    $transaction->details      = $details;
    $transaction->remark       = $remark;
    $transaction->trx          = $trx;
    $transaction->post_balance = $user->$field_post_balance;
    $transaction->bonus_type   = $bonus_type;
    $transaction->save();
}

//
function rDollar()
{   
    return 600;    
}

function returnTheReferrerUser(User $user)
{
    if ($user) {
        if ($user->ref_by) {
            return User::find($user->ref_by);
        }
    }
    return false;    
}


function returnReferrerUser($user_id)
{
    $user  = User::find($user_id);
    if ($user) {
        if ($user->ref_by) {
            return User::find($user->ref_by);
        }
    }
    return false;    
}


function checkIfUserIsInMatrix($user_id){
   
   $check =  Matrix::where('user_id', auth()->id())->where('is_active', 1)->first();
   
        return $check;
}

function checkIfUserIsInMatrix_new($user_id, $stage_id){
    
   $check =  Matrix::where('user_id', $user_id)->where('stage_id', $stage_id)->first();
   
        return $check;
}


// return stockist

function getStockistId($user_id){

    $stockist = Stockist::where('user_id', $user_id)->first();

        if($stockist){
           return $stockist->id;
        }
      return false;
}
function getProductStatePrice($id){
    return ProductStatePrice::findOrFail($id);
}


function productPurchase(User $user, $trxx, $amount){
        $trx               = new Transaction();
        $trx->user_id      = $user->id;
        $trx->amount       = $amount;
        $trx->trx_type     = '-';
        $trx->details      = 'Shopping transaction';
        $trx->remark       = 'shopping';
        $trx->trx          = $trxx;
        $trx->post_balance = $user->product_wallet;
        $trx->save();

        notify($user, 'PROJECT_PURCHASED', [
            'amount'       => showAmount($amount, currencyFormat: false),
            'trx'          => $trxx,
            'post_balance' => showAmount($user->product_wallet, currencyFormat: false),
        ]);
}

function numerals($amount)
{
    $decimal = 2; 
    $separate = true; 
    $exceptZeros = true; 
    $separator = ',';
    
    $printAmount = number_format($amount, $decimal, '.', $separator);
    if ($exceptZeros) {
        $exp = explode('.', $printAmount);
        if ($exp[1] * 1 == 0) {
            $printAmount = $exp[0];
        } else {
            $printAmount = rtrim($printAmount, '0');
        }
    }
    return $printAmount;
}

function returnUserDetailsr($user_id, $value = null)
{
    $user  = User::find($user_id);
    if ($user) {
        if($value)
        return $user->$value;
        return $user;
    }
    return false;    
}

function passwordReset($user, $code)
{

    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = env('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = env('MAIL_USERNAME');
        $mail->Password = env('MAIL_PASSWORD');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
        $mail->Port = env('MAIL_PORT', 465);
        
        // Recipients
        $mail->setFrom('no-reply@support.wiifarmcoop.org', 'WiiFarm Coorpperative Society');
        $mail->addAddress($user->email, $user->fullname);
        
        // Prepare template variables
        $templateData = [
            'user' => $user,
            'code' => $code,
        ];
 
        // Render Blade template 
        $htmlContent = view('email.forgot_password', $templateData)->render();
        
        // Set email content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to WiiFarm Coorpperative Society';
        $mail->Body = $htmlContent;
        $mail->AltBody = strip_tags($htmlContent); // Plain text version
        
        $mail->send();
        return 'Email sent successfully';

    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}


function sendEmailWithPhpTemplate($user)
{

    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = env('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = env('MAIL_USERNAME');
        $mail->Password = env('MAIL_PASSWORD');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
        $mail->Port = env('MAIL_PORT', 465);
        
        // Recipients
        $mail->setFrom('no-reply@support.wiifarmcoop.org', 'WiiFarm Coorpperative Society');
        $mail->addAddress($user->email, $user->fullname);
        
        // Prepare template variables
        $templateData = [
            'user' => $user,
        ];

        // Render Blade template
        $htmlContent = view('email.welcome_message', $templateData)->render();
        
        // Set email content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to WiiFarm Coorpperative Society';
        $mail->Body = $htmlContent;
        $mail->AltBody = strip_tags($htmlContent); // Plain text version
        
        $mail->send();
        return 'Email sent successfully';

    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function systemDetails()
{
    // $system['name']          = 'WiiFarm';
    // $system['version']       = '2.0';
    // $system['build_version'] = '5.0.9';
    $system['name']          = 'WiiFarm';
    $system['version']       = 'Cooperative';
    $system['build_version'] = '';
    return $system;
}


function slug($string)
{
    return Str::slug($string);
}


function verificationCode($length)
{
    if ($length == 0) return 0;
    $min = pow(10, $length - 1);
    $max = (int) ($min - 1) . '9';
    return random_int($min, $max);
}


function getNumber($length = 8)
{
    $characters       = '1234567890';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function activeTemplate($asset = false)
{
    $template = session('template') ?? gs('active_template');
    if ($asset) return 'assets/templates/' . $template . '/';
    return 'templates.' . $template . '.';
}

function activeTemplateName()
{
    $template = session('template') ?? gs('active_template');
    return $template;
}

function siteLogo($type = null)
{
    $name = $type ? "/logo_$type.png" : '/logo.png';
    return getImage(getFilePath('logoIcon') . $name);
}

function siteFavicon()
{
    return getImage(getFilePath('logoIcon') . '/favicon.png');
}

function loadReCaptcha()
{
    return Captcha::reCaptcha();
}

function loadCustomCaptcha($width = '100%', $height = 46, $bgColor = '#003')
{
    return Captcha::customCaptcha($width, $height, $bgColor);
}

function verifyCaptcha()
{
    return Captcha::verify();
}

function loadExtension($key)
{
    $extension = Extension::where('act', $key)->where('status', Status::ENABLE)->first();
    return $extension ? $extension->generateScript(): '';
}

function getTrx($length = 12)
{
    $characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
    $charactersLength = strlen($characters);
    $randomString     = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function getAmount($amount, $length = 2)
{
    $amount = round($amount ?? 0, $length);
    return $amount + 0;
}

function showAmount($amount, $decimal = 2, $separate = true, $exceptZeros = false, $currencyFormat = true)
{
    $separator = '';
    if ($separate) {
        $separator = ',';
    }
    $printAmount = number_format($amount, $decimal, '.', $separator);
    if ($exceptZeros) {
        $exp = explode('.', $printAmount);
        if ($exp[1] * 1 == 0) {
            $printAmount = $exp[0];
        } else {
            $printAmount = rtrim($printAmount, '0');
        }
    }
    // if ($currencyFormat) {
    //     if (gs('currency_format') == Status::CUR_BOTH) {
    //         return gs('cur_sym') . $printAmount . ' ' . __(gs('cur_text'));
    //     } elseif (gs('currency_format') == Status::CUR_TEXT) {
    //         return $printAmount . ' ' . __(gs('cur_text'));
    //     } else {
    //         return gs('cur_sym') . $printAmount;
    //     }
    // }

    if ($currencyFormat) {
        if (gs('currency_format') == Status::CUR_BOTH) {
            return gs('cur_sym') . $printAmount;
        } elseif (gs('currency_format') == Status::CUR_TEXT) {
            return $printAmount . ' ' . __(gs('cur_text'));
        } else {
            return gs('cur_sym') . $printAmount;
        }
    }
    return $printAmount;
}
 

function removeElement($array, $value)
{
    return array_diff($array, (is_array($value) ? $value : array($value)));
}

function cryptoQR($wallet)
{
    return "https://api.qrserver.com/v1/create-qr-code/?data=$wallet&size=300x300&ecc=m";
}

function keyToTitle($text)
{
    return ucfirst(preg_replace("/[^A-Za-z0-9 ]/", ' ', $text));
}


function titleToKey($text)
{
    return strtolower(str_replace(' ', '_', $text));
}


function strLimit($title = null, $length = 10)
{
    return Str::limit($title, $length);
}


function getIpInfo()
{
    $ipInfo = ClientInfo::ipInfo();
    return $ipInfo;
}


function osBrowser()
{
    $osBrowser = ClientInfo::osBrowser();
    return $osBrowser;
}


function getTemplates()
{
    $param['purchasecode'] = env("PURCHASECODE");
    $param['website']      = @$_SERVER['HTTP_HOST'] . @$_SERVER['REQUEST_URI'] . ' - ' . env("APP_URL");
    $url                   = VugiChugi::gttmp() . systemDetails()['name'];
    $response              = CurlRequest::curlPostContent($url, $param);
    if ($response) {
        return $response;
    } else {
        return null;
    }
}


function getPageSections($arr = false)
{
    $jsonUrl  = resource_path('views/') . str_replace('.', '/', activeTemplate()) . 'sections.json';
    $sections = json_decode(file_get_contents($jsonUrl));
    if ($arr) {
        $sections = json_decode(file_get_contents($jsonUrl), true);
        ksort($sections);
    }
    return $sections;
}


function getImage($image, $size = null, $defaultUser = false)
{
    $clean = '';
    if (file_exists($image) && is_file($image)) {
        return asset($image) . $clean;
    }
    if ($defaultUser) {
        return asset('assets/images/default-user.png');
    }
    if ($size) {
        return route('placeholder.image', $size);
    }
    return asset('assets/images/default.png');
}

function getImage_tree($image, $size = null, $defaultUser = false, $no = 0)
{
     /*$clean = '';
    if (file_exists($image) && is_file($image)) {
        return asset($image) . $clean;
    }
    */

    if ($no) {
        return asset($image) . $clean;
    }

    if ($defaultUser) {
        return asset('assets/images/default-user.png');
    }
    if ($size) {
        return route('placeholder.image', $size);
    }
    return asset('assets/images/default.png');
    
    
}


function notify($user, $templateName, $shortCodes = null, $sendVia = null, $createLog = true, $pushImage = null, $adminId = null)
{
    $globalShortCodes = [
        'site_name'       => gs('site_name'),
        'site_currency'   => gs('cur_text'),
        'currency_symbol' => gs('cur_sym'),
    ];

    if (gettype($user) == 'array') {
        $user = (object) $user;
    }

    $shortCodes = array_merge($shortCodes ?? [], $globalShortCodes);

    $notify               = new Notify($sendVia);
    $notify->templateName = $templateName;
    $notify->shortCodes   = $shortCodes;
    $notify->user         = $user;
    $notify->createLog    = $createLog;
    $notify->pushImage    = $pushImage;
    $notify->adminId      = $adminId;
    $notify->userColumn   = isset($user->id) ? $user->getForeignKey() : 'user_id';
    $notify->send();
}

function getPaginate($paginate = null)
{
    if (!$paginate) {
        $paginate = gs('paginate_number');
    }
    return $paginate;
}

function paginateLinks($data)
{
    return $data->appends(request()->all())->links();
}


function menuActive($routeName, $type = null, $param = null)
{
    if     ($type == 3) $class = 'side-menu--open';
    elseif ($type == 2) $class = 'sidebar-submenu__open';
    else   $class              = 'active';

    if (is_array($routeName)) {
        foreach ($routeName as $key => $value) {
            if (request()->routeIs($value)) return $class;
        }
    } elseif (request()->routeIs($routeName)) {
        if ($param) {
            $routeParam = array_values(@request()->route()->parameters ?? []);
            if (strtolower(@$routeParam[0]) == strtolower($param)) return $class;
            else return;
        }
        return $class;
    }
}


function fileUploader($file, $location, $size = null, $old = null, $thumb = null, $filename = null)
{
    $fileManager           = new FileManager($file);
    $fileManager->path     = $location;
    $fileManager->size     = $size;
    $fileManager->old      = $old;
    $fileManager->thumb    = $thumb;
    $fileManager->filename = $filename;
    $fileManager->upload();
    return $fileManager->filename;
}

function fileManager()
{
    return new FileManager();
}

function getFilePath($key)
{
    return fileManager()->$key()->path;
}

function getFileSize($key)
{
    return fileManager()->$key()->size;
}

function getThumbSize($key)
{
    return fileManager()->$key()->thumb;
}

function getFileExt($key)
{
    return fileManager()->$key()->extensions;
}

function diffForHumans($date)
{
    $lang = session()->get('lang');
    Carbon::setlocale($lang);
    return Carbon::parse($date)->diffForHumans();
}


function showDateTime($date, $format = 'Y-m-d h:i A')
{
    if (!$date) {
        return '-';
    }
    $lang = session()->get('lang');
    Carbon::setlocale($lang ?? 'en');

    return Carbon::parse($date)->translatedFormat($format);
}


function getContent($dataKeys, $singleQuery = false, $limit = null, $orderById = false)
{

    $templateName = activeTemplateName();
    if ($singleQuery) {
        $content = Frontend::where('tempname', $templateName)->where('data_keys', $dataKeys)->orderBy('id', 'desc')->first();
    } else {
        $article = Frontend::where('tempname', $templateName);
        $article->when($limit != null, function ($q) use ($limit) {
            return $q->limit($limit);
        });
        if ($orderById) {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id')->get();
        } else {
            $content = $article->where('data_keys', $dataKeys)->orderBy('id', 'desc')->get();
        }
    }
    return $content;
}

function verifyG2fa($user, $code, $secret = null)
{
    $authenticator = new GoogleAuthenticator();
    if (!$secret) {
        $secret = $user->tsc;
    }
    $oneCode  = $authenticator->getCode($secret);
    $userCode = $code;
    if ($oneCode == $userCode) {
        $user->tv = Status::YES;
        $user->save();
        return true;
    } else {
        return false;
    }
}


function urlPath($routeName, $routeParam = null)
{
    if ($routeParam == null) {
        $url = route($routeName);
    } else {
        $url = route($routeName, $routeParam);
    }
    $basePath = route('home');
    $path     = str_replace($basePath, '', $url);
    return $path;
}


function showMobileNumber($number)
{
    $length = strlen($number);
    return substr_replace($number, '***', 2, $length - 4);
}

function showEmailAddress($email)
{
    $endPosition = strpos($email, '@') - 1;
    return substr_replace($email, '***', 1, $endPosition);
}


function getRealIP()
{
    $ip = $_SERVER["REMOTE_ADDR"];
      //Deep detect ip
    if (filter_var(@$_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    }
    if (filter_var(@$_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    if (filter_var(@$_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if ($ip == '::1') {
        $ip = '127.0.0.1';
    }

    return $ip;
}


function appendQuery($key, $value)
{
    return request()->fullUrlWithQuery([$key => $value]);
}

function dateSort($a, $b)
{
    return strtotime($a) - strtotime($b);
}

function dateSorting($arr)
{
    usort($arr, "dateSort");
    return $arr;
}
 
 // return stockist
function returnStockist($id = null)
{   

    $stock = null;
    if ($id == null) {
        // code...
        $stock = Stockist::all();
    }elseif($id){
        $stock = Stockist::where('user_id', $id)->first();
    }
    return $stock;
}

// return Stockist order
function returnStockistOrder($id = null)
{   

    $order = null;
    if ($id == null) {
        // code...
        $order = Sorder::all();
    }elseif($id){
        $order = Sorder::where('user_id', $id)->get();
    }
    return $order;
}


function gs($key = null) 
{   
    $general = Cache::get('GeneralSetting');
    //dd($general);
    if (!$general) {
        $general = GeneralSetting::first();
        Cache::put('GeneralSetting', $general);
    }
    if ($key) return @$general->$key;
    return $general;
}



function isImage($string)
{
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif');
    $fileExtension     = pathinfo($string, PATHINFO_EXTENSION);
    if (in_array($fileExtension, $allowedExtensions)) {
        return true;
    } else {
        return false;
    }
}

function isHtml($string)
{
    if (preg_match('/<.*?>/', $string)) {
        return true;
    } else {
        return false;
    }
}


function convertToReadableSize($size)
{
    preg_match('/^(\d+)([KMG])$/', $size, $matches);
    $size = (int)$matches[1];
    $unit = $matches[2];

    if ($unit == 'G') {
        return $size . 'GB';
    }

    if ($unit == 'M') {
        return $size . 'MB';
    }

    if ($unit == 'K') {
        return $size . 'KB';
    }

    return $size . $unit;
}


function frontendImage($sectionName, $image, $size = null, $seo = false)
{
    if ($seo) {
        return getImage('assets/images/frontend/' . $sectionName . '/seo/' . $image, $size);
    }
    return getImage('assets/images/frontend/' . $sectionName . '/' . $image, $size);
}


function shortDescription($string, $length = 120)
{
    return Illuminate\Support\Str::limit($string, $length);
}

function updateBV($id, $bv, $details)
{
    // Walks the old users.pos_id tree. New members all get pos_id = 1, so a
    // member whose pos_id points back to someone already visited would loop
    // forever: stop on a repeat (notes Step 88).
    $seen = [];
    while ($id != "" && $id != "0" && !isset($seen[$id])) {
        $seen[$id] = true;
        if (isUserExists($id)) {
            $posId = getPositionId($id);
            if ($posId == "0") {
                break;
            }
            $posUser = User::find($posId);
            if ($posUser->plan_id) {
                $position       = getPositionLocation($id);
                $extra          = UserExtra::firstOrCreate(['user_id' => $posId]);
                $bvLog          = new BvLog();
                $bvLog->user_id = $posId;

                if ($position == 1) {
                    $extra->bv_left  += $bv;
                    $bvLog->position  = '1';
                } else {
                    $extra->bv_right += $bv;
                    $bvLog->position  = '2';
                }
                $extra->save();
                $bvLog->amount   = $bv;
                $bvLog->trx_type = '+';
                $bvLog->details  = $details;
                $bvLog->save();
            }
            $id = $posId;
        } else {
            break;
        }
    }
}


function isUserExists($id)
{
    $user = User::find($id);
    if ($user) {
        return true;
    } else {
        return false;
    }
}

function getPositionId($id)
{
    $user = User::find($id);

    if ($user) {
        return $user->pos_id;
    } else {
        return 0;
    }
}

function getPositionLocation($id)
{
    $user = User::find($id);
    if ($user) {
        return $user->position;
    } else {
        return 0;
    }
}

function getPosition($parentid, $position)
{
    $childid = getTreeChildId($parentid, $position);

    if ($childid != "-1") {
        $id = $childid;
    } else {
        $id = $parentid;
    }
    while ($id != "" || $id != "0") {
        if (isUserExists($id)) {
            $nextchildid = getTreeChildId($id, $position);
            if ($nextchildid == "-1") {
                break;
            } else {
                $id = $nextchildid;
            }
        } else break;
    }

    $res['pos_id']   = $id;
    $res['position'] = $position;
    return $res;
}

function getTreeChildId($parentid, $position)
{
    $cou = User::where('pos_id', $parentid)->where('position', $position)->count();
    $cid = User::where('pos_id', $parentid)->where('position', $position)->first();
    if ($cou == 1) {
        return $cid->id;
    } else {
        return -1;
    }
}

function mlmPositions()
{
    return array(
        '1' => 'Left',
        '2' => 'Right',
    );
}

function updateFreeCount($id)
{
    // Walks the old users.pos_id tree. New members all get pos_id = 1, so a
    // member whose pos_id points back to someone already visited would loop
    // forever: stop on a repeat (notes Step 88).
    $seen = [];
    while ($id != "" && $id != "0" && !isset($seen[$id])) {
        $seen[$id] = true;
        if (isUserExists($id)) {
            $posid = getPositionId($id);
            if ($posid == "0") {
                break;
            }
            $position = getPositionLocation($id);

            $extra = UserExtra::firstOrCreate(['user_id' => $posid]);

            if ($position == 1) {
                $extra->free_left += 1;
            } else {
                $extra->free_right += 1;
            }
            $extra->save();

            $id = $posid;
        } else {
            break;
        }
    }
}


function showTreePage($id)
{
    $res      = array_fill_keys(array('b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'), null);
    $res['a'] = User::find($id);

    $res['b'] = getPositionUser($id, 1);
    if ($res['b']) {
        $res['d'] = getPositionUser($res['b']->id, 1);
        $res['e'] = getPositionUser($res['b']->id, 2);
    }
    if ($res['d']) {
        $res['h'] = getPositionUser($res['d']->id, 1);
        $res['i'] = getPositionUser($res['d']->id, 2);
    }
    if ($res['e']) {
        $res['j'] = getPositionUser($res['e']->id, 1);
        $res['k'] = getPositionUser($res['e']->id, 2);
    }
    $res['c'] = getPositionUser($id, 2);
    if ($res['c']) {
        $res['f'] = getPositionUser($res['c']->id, 1);
        $res['g'] = getPositionUser($res['c']->id, 2);
    }
    if ($res['f']) {
        $res['l'] = getPositionUser($res['f']->id, 1);
        $res['m'] = getPositionUser($res['f']->id, 2);
    }
    if ($res['g']) {
        $res['n'] = getPositionUser($res['g']->id, 1);
        $res['o'] = getPositionUser($res['g']->id, 2);
    }
    return $res;
}


function showMatrixTree($id)
{
    $res      = array_fill_keys(array('b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'), null);
    $res['a'] = User::find($id);

    $res['b'] = getUserMatrixPosition1($id, 'left');
 
    if ($res['b']) {
        $res['d'] = getUserMatrixPosition1($res['b']->id, 'left');
        $res['e'] = getUserMatrixPosition1($res['b']->id, 'right');
    }
    if ($res['d']) {
        $res['h'] = getUserMatrixPosition1($res['d']->id, 'left');
        $res['i'] = getUserMatrixPosition1($res['d']->id, 'right');
    }
    if ($res['e']) {
        $res['j'] = getUserMatrixPosition1($res['e']->id, 'left');
        $res['k'] = getUserMatrixPosition1($res['e']->id, 'right');
    }
    $res['c'] = getUserMatrixPosition1($id, 'right');
    if ($res['c']) {
        $res['f'] = getUserMatrixPosition1($res['c']->id, 'left');
        $res['g'] = getUserMatrixPosition1($res['c']->id, 'right');
    }
    if ($res['f']) {
        $res['l'] = getUserMatrixPosition1($res['f']->id, 'left');
        $res['m'] = getUserMatrixPosition1($res['f']->id, 'right');
    }
    if ($res['g']) {
        $res['n'] = getUserMatrixPosition1($res['g']->id, 'left');
        $res['o'] = getUserMatrixPosition1($res['g']->id, 'right');
    }
    return $res;
}

function showMatrixStageTree($id, $stage = 1)
{
    $res      = array_fill_keys(array('b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o'), null);
    $res['a'] = User::find($id); 

    $res['b'] = getUserMatrixPosition1($id, 'left', $stage);
 
    if ($res['b']) {
        $res['d'] = getUserMatrixPosition1($res['b']->id, 'left', $stage);
        $res['e'] = getUserMatrixPosition1($res['b']->id, 'right', $stage);
    }
    if ($res['d']) {
        $res['h'] = getUserMatrixPosition1($res['d']->id, 'left', $stage);
        $res['i'] = getUserMatrixPosition1($res['d']->id, 'right', $stage);
    }
    if ($res['e']) {
        $res['j'] = getUserMatrixPosition1($res['e']->id, 'left', $stage);
        $res['k'] = getUserMatrixPosition1($res['e']->id, 'right', $stage);
    }
    $res['c'] = getUserMatrixPosition1($id, 'right', $stage);
    if ($res['c']) {
        $res['f'] = getUserMatrixPosition1($res['c']->id, 'left', $stage);
        $res['g'] = getUserMatrixPosition1($res['c']->id, 'right', $stage);
    }
    if ($res['f']) {
        $res['l'] = getUserMatrixPosition1($res['f']->id, 'left', $stage);
        $res['m'] = getUserMatrixPosition1($res['f']->id, 'right', $stage);
    }
    if ($res['g']) {
        $res['n'] = getUserMatrixPosition1($res['g']->id, 'left', $stage);
        $res['o'] = getUserMatrixPosition1($res['g']->id, 'right', $stage);
    }
    return $res;
}
function showMatrixTwo($id, $stage = 1)
{
    $res      = array_fill_keys(array('b', 'c', 'd', 'e', 'f', 'g'), null);

    $res['a'] = User::find($id);

    $res['b'] = getUserMatrixPosition1($id, 'left', $stage);
 
    if ($res['b']) {
        $res['d'] = getUserMatrixPosition1($res['b']->id, 'left', $stage);
        $res['e'] = getUserMatrixPosition1($res['b']->id, 'right', $stage);
    }
   
   
    $res['c'] = getUserMatrixPosition1($id, 'right');
    if ($res['c']) {
        $res['f'] = getUserMatrixPosition1($res['c']->id, 'left', $stage);
        $res['g'] = getUserMatrixPosition1($res['c']->id, 'right', $stage);
    }
    
    return $res;
}

function getUserMatrixPosition($id, $position)
{
    return User::where('pos_id', $id)->where('position', $position)->first();
}
function getUserDetails($id)
{   
    return User::find($id);
}

function getUserById($id)
{   
    return User::find($id);
}



function getUserMatrixPosition1($id, $position, $stage = 1)
{   

    if($position == 'left'){

        $user_left_id = Matrix::where('user_id', $id)->where('stage_id', $stage)->first();

        if($user_left_id){
           return User::where('id', $user_left_id->left)->first();
        }
            return null;

    }

     $user_right_id = Matrix::where('user_id', $id)->where('stage_id', $stage)->first();

        if($user_right_id){
            //dd($user_right_id->left);
           return User::where('id', $user_right_id->right)->first();
        }
            return null;

}

function getPositionUser($id, $position)
{
    return User::where('pos_id', $id)->where('position', $position)->first();
}

function getPositionMatrix($id, $stage_id)
{   
    return Matrix::where('user_id', $id)->where('stage_id', $stage_id)->first();
}

// function getRightPositionMatrix($id, $stage_id, $pos)
// {   
//     return Matrix::where('user_id', $id)->where('right', $position)->first();
// }

function showSingleUserinTree_new($user, $gg = null)
{
    $res = '';

    if ($user) {

        $stage_name = getMatrixStage($user->id)?->stage?->name;
        //dd($stage_name);

        $userType = $stage_name;
        $stShow   = $stage_name;


        //$img   = getImage('assets/images/user/profile/' . $user->image, '120x120', true);
        //$img   = getImage_tree('assets/images/user/profile/' . $user->image, '120x120', true);

        $img   = asset('assets/images/active-user-avatar.png');

        $refby = getUserById($user->ref_by)->username ?? '';

        $userMatrix = Matrix::where('user_id', $user->id)->where('stage_id', 1)->first();
        $pvLeft     = (float) ($userMatrix->pv_left_pairing  ?? 0);
        $pvRight    = (float) ($userMatrix->pv_right_pairing ?? 0);

        $pvLeftrank     = (float) ($userMatrix->pv_left  ?? 0);
        $pvRightrank    = (float) ($userMatrix->pv_right ?? 0);

        if (auth()->guard('admin')->user()) {

            $hisTree = route('admin.users.other.tree', $user->username);

        } else {
            $hisTree = route('user.other.tree', $user->username);
        }

        $extraData  = " data-name=\" Username: $user->username\"";
        $extraData .= " data-treeurl=\"$hisTree\"";
        $extraData .= " data-status=\"$stShow\"";

        $extraData .= " data-image=\"$img\"";
        $extraData .= " data-refby=\"$refby\"";
        $extraData .= " data-pvleft=\"$pvLeft\"";
        $extraData .= " data-pvright=\"$pvRight\"";
        $extraData .= " data-pvleftrank=\"$pvLeftrank\"";
        $extraData .= " data-pvrightrank=\"$pvRightrank\"";

        $res       .= "<div class=\"user showDetails\" type=\"button\" $extraData>";
        $res       .= "<img src=\"$img\" alt=\"*\"  class=\"$userType\">";
        $res       .= "<p class=\"user-name\">$user->username</p>";
    } else {
        $img = getImage_tree('assets/images/user/profile/', '120x120', true);

        $res .= "<div class=\"user\" type=\"button\">";
        $res .= "<img src=\"$img\" alt=\"*\"  class=\"no-user\">";
        $res .= "<p class=\"user-name\">No user</p>";
    }

    $res .= " </div>";
    $res .= " <span class=\"line\"></span>";

    return $res;
}

function showSingleUserinTree_new_admin($user, $gg = null)
{
    $res = '';

    if ($user) {

        $stage_name = getMatrixStage($user->id)->stage->name;
        $userType = $stage_name;
        $stShow   = $stage_name;
        

        //$img   = getImage('assets/images/user/profile/' . $user->image, '120x120', true);
        //$img   = getImage_tree('assets/images/user/profile/' . $user->image, '120x120', true);

        $img   = asset('assets/images/active-user-avatar.png');

        $refby = getUserById($user->ref_by)->username ?? '';

 
        if (auth()->guard('admin')->user()) {

            $hisTree = route('admin.users.other.tree', $user->username);

        } else {
            $hisTree = route('user.other.tree', $user->username);
        }

        $extraData  = " data-name=\" Username: $user->username\"";
        $extraData .= " data-treeurl=\"$hisTree\"";
        $extraData .= " data-status=\"$stShow\"";
        
        $extraData .= " data-image=\"$img\""; 
        $extraData .= " data-refby=\"$refby\"";
        
        $res       .= "<div class=\"user showDetails\" type=\"button\" $extraData>";
        $res       .= "<img src=\"$img\" alt=\"*\"  class=\"$userType\">";
        $res       .= "<p class=\"user-name\">$user->username</p>";
    } else {
        $img = getImage_tree('assets/images/user/profile/', '120x120', true);

        $res .= "<div class=\"user\" type=\"button\">";
        $res .= "<img src=\"$img\" alt=\"*\"  class=\"no-user\">";
        $res .= "<p class=\"user-name\">No user</p>";
    }

    $res .= " </div>";
    $res .= " <span class=\"line\"></span>";

    return $res;
}

function showSingleUserinTree($user)
{
    $res = '';
    if ($user) {
        if ($user->plan_id == 0) {
            $userType = "free-user";
            $stShow   = "Free";
            $planName = '';
        } else {
            $userType = "paid-user";
            $stShow   = "Paid";
            $planName = $user->plan->name;
        }
        $img   = getImage('assets/images/user/profile/' . $user->image, '120x120', true);
        $refby = getUserById($user->ref_id)->fullname ?? '';
        if (auth()->guard('admin')->user()) {
            $hisTree = route('admin.users.other.tree', $user->username);
        } else {
            $hisTree = route('user.other.tree', $user->username);
        }

        $extraData  = " data-name=\"$user->fullname\"";
        $extraData .= " data-treeurl=\"$hisTree\"";
        $extraData .= " data-status=\"$stShow\"";
        $extraData .= " data-plan=\"$planName\"";
        $extraData .= " data-image=\"$img\""; 
        $extraData .= " data-refby=\"$refby\"";
        $extraData .= " data-lpaid=\"" . @$user->userExtra->paid_left . "\"";
        $extraData .= " data-rpaid=\"" . @$user->userExtra->paid_right . "\"";
        $extraData .= " data-lfree=\"" . @$user->userExtra->free_left . "\"";
        $extraData .= " data-rfree=\"" . @$user->userExtra->free_right . "\"";
        $extraData .= " data-lbv=\"" . getAmount(@$user->userExtra->bv_left) . "\"";
        $extraData .= " data-rbv=\"" . getAmount(@$user->userExtra->bv_right) . "\"";
        $res       .= "<div class=\"user showDetails\" type=\"button\" $extraData>";
        $res       .= "<img src=\"$img\" alt=\"*\"  class=\"$userType\">";
        $res       .= "<p class=\"user-name\">$user->username</p>";
    } else {
        $img = getImage('assets/images/user/profile/', '120x120', true);

        $res .= "<div class=\"user\" type=\"button\">";
        $res .= "<img src=\"$img\" alt=\"*\"  class=\"no-user\">";
        $res .= "<p class=\"user-name\">No user</p>";
    }

    $res .= " </div>";
    $res .= " <span class=\"line\"></span>";

    return $res;
}




function updatePaidCount($id)
{
    // Walks the old users.pos_id tree. New members all get pos_id = 1, so a
    // member whose pos_id points back to someone already visited would loop
    // forever: stop on a repeat (notes Step 88).
    $seen = [];
    while ($id != "" && $id != "0" && !isset($seen[$id])) {
        $seen[$id] = true;
        if (isUserExists($id)) {
            $posid = getPositionId($id);
            if ($posid == "0") {
                break;
            }
            $position = getPositionLocation($id);
            $extra    = UserExtra::firstOrCreate(['user_id' => $posid]);

            if ($position == 1) {
                $extra->free_left -= 1;
                $extra->paid_left += 1;
            } else {
                $extra->free_right -= 1;
                $extra->paid_right += 1;
            }
            $extra->save();
            $id = $posid;
        } else {
            break;
        }
    }
}

function treeComission($id, $amount, $details)
{
    while ($id != "" || $id != "0") {
        if (isUserExists($id)) {
            $posid = getPositionId($id);
            if ($posid == "0") {
                break;
            }

            $posUser = User::find($posid);
            if ($posUser->plan_id != 0) {

                $posUser->balance          += $amount;
                $posUser->total_binary_com += $amount;
                $posUser->save();

                $transaction               = new Transaction();
                $transaction->amount       = $posUser->id;
                $transaction->user_id      = $amount;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = $details;
                $transaction->remark       = 'binary_commission';
                $transaction->trx          = getTrx();
                $transaction->post_balance = $posUser->balance;
                $transaction->save();
            }
            $id = $posid;
        } else {
            break;
        }
    }
}

// check user current parent on the matrix table and return the id
function parentInMatrix($user_id, $stage_id){
    $tt = Matrix::where('user_id', $user_id)->where('stage_id', $stage_id)->first();

        if($tt){
            $user = User::find($tt->parent_id);
            if ($user) {
                return $user;
            }
           return false;
        }
      return false;
}


function returnMatrixStageSetting($stage_num){
    $mt = MatrixStage::find($stage_num);
    if($mt)
        return $mt;
    return 0;
}

function returnMatrixStageNumber($stage){

     $mt = Matrix::where('user_id', auth()->id())->where('stage_id', $stage)->first();

        if($mt){
           return true;
        }
      return false;
}


function returnStockist_store($id){

    $stockist_store = Stockist_store::where('user_id', auth()->id())->where('product_id', $id)->first();

        if($stockist_store){
           return $stockist_store;
        }
      return false;
}



function returnMatrixStageNumberUnknown($id, $stage, $return_value){

     $mt = Matrix::where('user_id', $id)->where('stage_id', $stage)->first();

        if($mt){
           return $mt->$return_value;
        }
      return false;
}


function returnCurrentMatrixStage($id){
    
     $mt = Matrix::where('user_id', $id)->where('is_active', true)->first();
     //dd($mt);
        if($mt){
           return $mt;
        }
      return false;
}
function returnMatrix($id){

     $mt = Matrix::where('user_id', $id)->where('is_active', true)->first();

        if($mt){
           return $mt;
        }
      return false;
}
 
function returnJustMatrixOnStage($id, $stage_id){

     $matx = Matrix::where('user_id', $id)->where('stage_id', $stage_id)->first();

        if($matx){
           return $matx;
        }
      return false;
}

function returnJustMatrixOnStageCheck($id, $stage_id){

     $matx = Matrix::where('user_id', $id)->where('stage_id', $stage_id)->first();

        if($matx){
           return true;
        }
      return false;
}

function registrationTransaction($user_id, $details)
{
    $transaction               = new Transaction();
    $transaction->user_id      = $user_id;
    $transaction->amount       = gs()->registration_fee;
    $transaction->charge       = 0;
    $transaction->trx_type     = '-';
    $transaction->details      = $details;
    $transaction->remark       = 'registration_fee';
    $transaction->trx          = getTrx();
    $transaction->bonus_type   = 4;
    $transaction->post_balance = gs()->registration_fee;
    $transaction->save();

}

function stockistPurchase_commission(User $user, $details, $amount, $percentage, $trx, $remark)
{   
    $amount_value = ($amount * ($percentage / 100));

    $user->balance            += $amount_value;
    $user->save();

    $transaction               = new Transaction();
    $transaction->user_id      = $user->id;
    $transaction->amount       = $amount_value;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = $remark;
    $transaction->trx          = $trx;
    $transaction->bonus_type   = 6;
    $transaction->post_balance = $user->balance;
    $transaction->save();

}

//balance_Transaction($user->id, $details, $amount, $remark, $trx, $post_balance);

function balance_Transaction($user_id, $details, $amount, $remark, $trx, $post_balance)
{
    $transaction               = new Transaction();
    $transaction->user_id      = $user_id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '-';
    $transaction->details      = $details;
    $transaction->remark       = $remark;
    $transaction->trx          = $trx;
    $transaction->bonus_type   = 8;
    $transaction->post_balance = $post_balance;
    $transaction->save();

}


function balance_TransactionReturn($user_id, $details, $amount, $remark, $trx, $post_balance)
{
    $transaction               = new Transaction();
    $transaction->user_id      = $user_id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = $remark;
    $transaction->trx          = $trx;
    $transaction->bonus_type   = 9;
    $transaction->post_balance = $post_balance;
    $transaction->save();

}

function stockistTransaction(Stockist $stock, User $user, $trxx, $amount)
{
    //dd($user->balance);
        $trx               = new Stransaction();
        $trx->user_id      = $user->id;
        $trx->amount       = $amount;
        $trx->trx_type     = '+';
        $trx->details      = 'Stockist Redemption';
        $trx->remark       = 'stockist_redemption';
        $trx->trx          = $trxx;
        $trx->post_balance = $user->stockist_rebate;
        $trx->save();

        notify($user, 'PROJECT_PURCHASED', [
            'shopping'         => 'Products redemption',
            'amount'       => showAmount($amount, currencyFormat: false),
            'trx'          => $trxx,
            'post_balance' => showAmount($stock->wallet, currencyFormat: false),
        ]);
}

 

function stockistActivation($user_id, $amount, $post_balance, $details, $remark, $trx){
  
    $transaction               = new Transaction();
    $transaction->user_id      = $user_id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '-';
    $transaction->details      = $details;
    $transaction->remark       = $remark;
    $transaction->trx          = $trx;
    $transaction->bonus_type   = 6;
    $transaction->post_balance = $post_balance;
    $transaction->save();

}

function stockistRebateTransaction($amount, $trxx)
{       $user              = auth()->user();

        $trx               = new Transaction();
        $trx->user_id      = $user->id;
        $trx->amount       = $amount;
        $trx->trx_type     = '+';
        $trx->details      = 'Stockist Rebate bonus';
        $trx->remark       = 'Stockist_rebate';
        $trx->trx          = $trxx;
        $trx->post_balance = $user->stockist_rebate;
        $trx->save();
}

function unilevelBonusTransaction($user_id, $user_unilevel_bonus, $amount, $details, $trx){
    //Bonus gotten when the user re-purchase a product

    $transaction               = new Transaction();
    $transaction->user_id      = $user_id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = 'unilevel_bonus';
    $transaction->trx          = $trx;
    $transaction->post_balance = $user_unilevel_bonus;
    $transaction->save();
}



function stockistPurchase($user_id, $amount, $post_balance, $details, $remark, $trx){
  
    $transaction               = new Transaction();
    $transaction->user_id      = $user_id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = $remark;
    $transaction->trx          = $trx;
    $transaction->bonus_type   = 10;
    $transaction->post_balance = $post_balance;
    $transaction->save();

}

function referralComission($user_id, $details)
{
    $user  = User::find($user_id);
    $refer = User::find($user->ref_by);
    if ($refer) {
        $plan = Plan::find($refer->plan_id);
        if ($plan) {
            $amount                = $plan->ref_com;
            $refer->balance       += $amount;
            $refer->total_ref_com += $amount;
            $refer->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $refer->id;
            $transaction->amount       = $amount;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $details;
            $transaction->remark       = 'referral_commission';
            $transaction->trx          = getTrx();
            $transaction->post_balance = $refer->balance;
            $transaction->save();

            notify($refer, 'REFERRAL_COMMISSION', [
                'trx'          => $transaction->trx,
                'amount'       => showAmount($amount, currencyFormat: false),
                'username'     => $user->username,
                'post_balance' => showAmount($refer->balance, currencyFormat: false),
            ]);
        }
    }
}


function purchaseCommision_distributor_stockist(User $user, $details, $amount, $trxx, $percentage){

    $bonus =  ($amount * ($percentage / 100));
    $user->balance          += $bonus;
    $user->save();
            
    $transaction               = new Transaction();
    $transaction->user_id      = $user->id;
    $transaction->amount       = $bonus;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = 'stockist_commission';
    $transaction->trx          = $trxx;
    $transaction->post_balance = $user->balance;
    $transaction->bonus_type   = 7;
    $transaction->save();
} 

function purchase_userCommision(User $user, $details, $amount, $trxx){

    
    $user->balance          += $amount;
    $user->save();
            
    $transaction               = new Transaction();
    $transaction->user_id      = $user->id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = 'product_purchase';
    $transaction->trx          = $trxx;
    $transaction->post_balance = $user->balance;
    $transaction->bonus_type   = 7;
    $transaction->save();
}


function purchaseCommision_distributor(User $user, $details, $amount, $trxx, $percentage){

    $bonus =  ($amount * ($percentage / 100));
    $user->balance          += $bonus;
    $user->save();
            
    $transaction               = new Transaction();
    $transaction->user_id      = $user->id;
    $transaction->amount       = $bonus;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = 'product_purchase';
    $transaction->trx          = $trxx;
    $transaction->post_balance = $user->balance;
    $transaction->bonus_type   = 7;
    $transaction->save();
}
  
function stockist_commision(User $user, $amount, $trxx, $details, $qty){
    
    $amt = $amount * $qty;
    $user->balance          += $amt;
    $user->save();
            
    $transaction               = new Transaction();
    $transaction->user_id      = $user->id;
    $transaction->amount       = $amount;
    $transaction->charge       = 0;
    $transaction->trx_type     = '+';
    $transaction->details      = $details;
    $transaction->remark       = 'stockist_bonus';
    $transaction->trx          = $trxx;
    $transaction->post_balance = $user->balance;
    $transaction->bonus_type   = 7;
    $transaction->save();
}

function purchaseCommision($user_ID, $details, $bonus, $trxx){

    
        $user= User::find($user_ID);

        if ($user) {
           
            $user->balance          += $bonus;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $bonus;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $details;
            $transaction->remark       = 'product_purchase';
            $transaction->trx          = $trxx;
            $transaction->post_balance = $user->balance;
            $transaction->bonus_type   = 4;
            $transaction->save();

            notify($user, 'PURCHASE_COMMISSION', [
                'trx'          => $trxx,
                'amount'       => showAmount($bonus, currencyFormat: false),
                'username'     => $user->username,
                'post_balance' => showAmount($user->balance, currencyFormat: false),
            ]);
        }
    
}

function complete_registration(User $user, $details){
     
    
    $parent = User::find($user->ref_by); 
   
    $parent_id = 0;
    $pos = 0;
    $user->status = Status::USER_ACTIVE;
    $user->profile_complete = Status::YES;
    $user->save();
 
    Useridcard::create([
       'user_id' => $user->id
    ]);
     

    $details = 'Referral bonus gotten from username: '.$user->username;
    referralStageMAtrix_food($user->id, $details, 1, 1);


    $adminNotification            = new AdminNotification();
    $adminNotification->user_id   = $user->id;
    $adminNotification->title     = 'New member registered';
    $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
    $adminNotification->save();


        // check if the parent is in the matrix and free before assignment to new user
           
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

    // Record registration transac
    $details_reg = 'Registration money ';
    registrationTransaction($user, $details_reg);
     sendEmailWithPhpTemplate($user);
}


 
function complete_registration_pin(User $user, Rmatrix $rmatrix, $user_parent_mat, $pin = null)
{
    DB::beginTransaction();
 
    try {
 
        //$user->profile_complete = Status::YES;
        $parent_id = 0;
        $parent = User::find($user->ref_by); 
    
        if($user_parent_mat){
            if($user_parent_mat->left == 0 ){
                $user_parent_mat->left = $user->id;
                $parent_id = $user_parent_mat->user_id;
                $user_parent_mat->save();
                $position = 'left';
            }elseif($user_parent_mat->right == 0){
                $user_parent_mat->right = $user->id;
                $parent_id = $user_parent_mat->user_id;
                $user_parent_mat->save();
                $position = 'right';      
            }        
        }else{
            $user_parent_matrix = Matrix::where('user_id', $rmatrix->parent_id)->where('stage_id', 1)->where('is_active', 1)->first();

            // check if the parent is in the Matrix before assignment to new user
            if (!$user_parent_matrix) {
                $parent_id = 0;
                $pos = 0;
                $position = 'root';
            }else{
                if($user_parent_matrix->left == 0 && $rmatrix->position == 'left'){
                $user_parent_matrix->left = $user->id;
                $parent_id = $user_parent_matrix->user_id;
                $user_parent_matrix->save();
                $position = 'left';

                }elseif($user_parent_matrix->right == 0 && $rmatrix->position == 'right'){
                    $user_parent_matrix->right = $user->id;
                    $parent_id = $user_parent_matrix->user_id;
                    $user_parent_matrix->save();
                    $position = 'right';        
                }
            }
        }
        //if Pin stats is 1. its means the pin is 9000. this user paid in full
        /*
        if($pin->stats == 0) {
            //$user->balance -= 2000; 
        }
        */
        $user->status = Status::USER_ACTIVE;
        $user->profile_complete = Status::YES;
        $user->save();


        if ($pin instanceof Pin) {
            $pin->status = Status::YES;
            $pin->user_id = $user->id;
            $pin->save();
        }

        $matrix            = new Matrix();
        $matrix->user_id   = $user->id;
        $matrix->parent_id = $parent_id;
        $matrix->position  = $position;
        $matrix->stage_id  = 1;
        $matrix->is_active  = 1;
        $matrix->save();

        Useridcard::create([
        'user_id' => $user->id
        ]);

        UserStageProgress::firstOrCreate([
            'user_id' => $user->id,
            'stage_id' => 1
        ]);

        // get the project the user is subscribe to

        $project = $user->project;
        $pv = $project->pv;

        $details = 'direct bonus gotten from username: '.$user->username;
        // the section process the direct bonus as set by the admin in 
       

        directBonus($user, $details, $project->direct_commission, $user->trx);


        $user1 = User::find($user->ref_by);

        $inDirect_bonus_details = 'Indirect bonus gotten from username: '.$user->username. ' Subscribing to '.$project->title;

        
        indirectBonus($user1, $pv, $inDirect_bonus_details, $trx);

        $dets = $user->username . ' Subscribed to ' . $user->project->title . ' Project.'; 
        // Next we distribute the PV along the user Tree.

        updatePV($user, $dets, $pv);

        // Cash back is credited to product_wallet AFTER payment is confirmed (visa/Paystack settled)

        $project = $user->project;
        $trx = $user->trx;
        $det_cashback = $project->title . ' subscription cash back';

        processCashBack($user, $project, $trx, $det_cashback);


        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New member registered';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();


        // check if the parent is in the matrix and free before assignment to new user

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
        // Record registration transac
        $details_reg = 'Registration money ';

        registrationTransaction($user->id, $details_reg);
        sendEmailWithPhpTemplate($user);

        DB::commit();

    } catch (\Throwable $e) {
        DB::rollBack();
        throw $e;
    }

    return to_route('user.home');
}

/**
 * Optimized genealogy check for production at scale (millions of users).
 * 
 * Checks if parent_id exists in the downline (descendants) of sponsor_id
 * using an iterative BFS approach to avoid recursion stack overflow.
 * 
 * @param int $sponsor_id      The root user ID to search downline from
 * @param int $parent_id       The user ID to search for in the downline
 * @return bool                True if parent_id is in sponsor_id's downline, false otherwise
 * 
 * Performance:
 * - Time: O(n) worst-case (entire tree), O(1) best-case (found immediately)
 * - Space: O(w) where w = max width of tree (typically 2 for binary matrix)
 * - DB queries: O(log n) average case with optimal indexing
 * 
 * Requires index on: (stage_id, user_id, left, right) for optimal performance
 */
function checkDownline($sponsor_id, $parent_id){
    // === VALIDATION ===
    $sponsor_id = (int) $sponsor_id;
    $parent_id = (int) $parent_id;
    if ($sponsor_id === $parent_id) {
        // code...
        return true;
    }
    
    // Quick exits for invalid inputs
    if ($sponsor_id <= 0 || $parent_id <= 0) {
        return false;
    }

    
    // === STAGE 1 ONLY ===
    $stageId = 1;
    
    // === GET ROOT USER'S MATRIX ENTRY ===
    $rootMatrix = Matrix::where('stage_id', $stageId)
        ->where('user_id', $sponsor_id)
        ->select('id', 'user_id', 'left', 'right')
        ->first();
    
    if (!$rootMatrix) {
        return false; // Root user has no matrix in this stage
    }
    
    // === ITERATIVE BFS (BREADTH-FIRST SEARCH) ===
    // I am Using array queue to prevents stack overflow on deep trees
    $queue = [];
    $visited = [];
    
    // Start with root's immediate children
    if ($rootMatrix->left > 0) {
        $queue[] = $rootMatrix->left;
    }
    if ($rootMatrix->right > 0) {
        $queue[] = $rootMatrix->right;
    }
    
    // Early exit if parent is a direct child
    if (in_array($parent_id, $queue)) {
        return true;
    }
    
    // === PROCESS QUEUE LEVEL BY LEVEL ===
    // Process in batches to reduce number of database queries
    $batchSize = 100; // Process up to 100 nodes per query
    
    while (!empty($queue)) {
        // Extract batch from queue
        $batch = array_splice($queue, 0, $batchSize);
        $batchKeys = array_flip($batch); // Convert to assoc for faster lookup
        
        // Mark batch as visited
        $visited = array_merge($visited, $batchKeys);
        
        // === BATCH QUERY: Get all children for the batch ===
        $children = Matrix::where('stage_id', $stageId)
            ->whereIn('user_id', $batch)
            ->select('user_id', 'left', 'right')
            ->get();
        
        foreach ($children as $matrix) {
            // Check left child
            if ($matrix->left > 0) {
                if ($matrix->left == $parent_id) {
                    return true; // FOUND!
                }
                // Add to queue if not visited (avoid cycles)
                if (!isset($visited[$matrix->left])) {
                    $queue[] = $matrix->left;
                }
            }
            
            // Check right child
            if ($matrix->right > 0) {
                if ($matrix->right === $parent_id) {
                    return true; // FOUND!
                }
                // Add to queue if not visited (avoid cycles)
                if (!isset($visited[$matrix->right])) {
                    $queue[] = $matrix->right;
                }
            }
        }
    }
    
    // Not found in downline
    return false;
}
function upLinePvOnUpgrade($user, $pvAmount, $details){
    /* This function simply allocate pv to a single parent. 
        check if the parent exist and detect the leg to allocate the pv. 
        then  call pvlog function for documentation. 
    */

    if ($user->ref_by > 0) {

        $userMatrix = Matrix::where('stage_id', 1)->where('user_id', $user->id)->first();

        if (!$userMatrix) {
            return;
        }

        $childId  = $user->id;
        $parentId = (int) $userMatrix->parent_id;

        if($parentId > 0) {
            $parentMatrix = Matrix::where('stage_id', 1)->where('user_id', $parentId)->first();
            if ($parentMatrix) {            
                if ((int) $parentMatrix->left == $childId) {
                    $parentMatrix->pv_left         += $pvAmount;
                    $parentMatrix->pv_left_pairing += $pvAmount;
                    $position                = 1;
                } else {
                    $parentMatrix->pv_right         += $pvAmount;
                    $parentMatrix->pv_right_pairing += $pvAmount;
                    $position                 = 2;
                }
                $parentMatrix->save();
                pvLog($parentMatrix->user_id, $pvAmount, $position, '+', $details);

            }

        }
    }
 }
 
 function updatePV(User $user, $details, $pv){

        $user_m = Matrix::where('stage_id', 1)->where('user_id', $user->id)->first();
        
        // User has no stage-1 matrix record — nothing to propagate
        if (!$user_m || !$user_m->parent_id) {
            return;
        }
        
        $user_child = $user->id;
        $user_id    = $user_m->parent_id;

        while ($user_id) {

            $user_matrix = Matrix::where('stage_id', 1)->where('user_id', $user_id)->first();

            if (!$user_matrix) {
                break;
            }

            if ($user_matrix->left == $user_child) {
                $user_matrix->pv_left         += $pv;
                $user_matrix->pv_left_pairing += $pv;
                $position = 1;
            } else {
                $user_matrix->pv_right         += $pv;
                $user_matrix->pv_right_pairing += $pv;
                $position = 2;
            }
            $user_matrix->save();

            pvLog($user_matrix->user_id, $pv, $position, '+', $details);
            
            $user_child = $user_matrix->user_id;
            $user_id    = $user_matrix->parent_id;
        }

}
//This is called in stockistController when a user bought products
function updateProductPV(User $user, Product $product, $quantity, $details){

    $pv = (float)$product->pv * $quantity;
    
    if ($pv <= 0) {
         return;
    }
   

    $user_m = Matrix::where('user_id', $user->id)->where('stage_id', 1)->first();

    // User has no stage-1 matrix record — nothing to propagate
    if (!$user_m || !$user_m->parent_id) {
        return;
    }
   
    $user_child = $user->id;
    $user_id    = $user_m->parent_id;

    while ($user_id) {
        $user_matrix = Matrix::where('user_id', $user_id)->where('stage_id', 1)->first();
        if (!$user_matrix) {
            break;
        }
        if ($user_matrix->left == $user_child) {
            $user_matrix->pv_left         += $pv;
            $user_matrix->pv_left_pairing += $pv;
            $position = 1;
        } else {
            $user_matrix->pv_right         += $pv;
            $user_matrix->pv_right_pairing += $pv;
            $position = 2;
        }
        $user_matrix->save();

        pvLog($user_matrix->user_id, $pv, $position, '+', $details);

        $user_child = $user_matrix->user_id;
        $user_id    = $user_matrix->parent_id;
    }

}

 function pvLog($user_id, $pv, $position, $trx_type, $details){
    
    $user = User::where('id', $user_id)->where('project_id', '>', 0)->first();
    if ($user) {
        $pvLog  = new PvLog();
        $pvLog->user_id = $user_id;
        $pvLog->position = (int)$position;
        $pvLog->amount   += $pv;
        $pvLog->trx_type = $trx_type;
        $pvLog->details  = $details;
        $pvLog->save();
        //dd($pvLog);
    }
 }

//direct bonus during registration
function  directBonus(User $user, $details, $direct_commission, $trx = null){
   // dd($direct_commission);
    
    $trx = $trx ?? getTrx();
    $user_to_credit = User::lockForUpdate()->find($user->ref_by);
    // The direct referal set for this project can be gotten from $user->project->direct_commission;
    if ($user_to_credit) {
        if ($direct_commission > 0) {
            
            $user_to_credit->direct_bonus += (float)$direct_commission;
            $user_to_credit->save();

            $remark = 'direct_commission';
            $bonus_type = 16;
            $charge = 0;
            
            newTransaction($user_to_credit, $details, $remark, $direct_commission, '+', $trx, $bonus_type, $charge, 'direct_bonus');

            // Debit from isolated Member Compensation Reserve Ledger
            try {
                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    (float)$direct_commission,
                    $trx,
                    $details,
                    'CRP',
                    'direct_bonus',
                    $user_to_credit->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit direct bonus: ' . $e->getMessage());
            }
        }
    }
}


//upgrade bonus during package upgrade
function  upgradeBonus(User $user, $details, $direct_commission, $trx = null){
   // dd($direct_commission);
    
    $trx = $trx ?? getTrx();
    $user_to_credit = User::lockForUpdate()->find($user->ref_by);
    if ($user_to_credit) {
        if ($direct_commission > 0) {
            
            $user_to_credit->upgrade_bonus += (float)$direct_commission;
            $user_to_credit->save();

            $remark = 'upgrade_commission';
            $bonus_type = 16;
            $charge = 0;
            
            newTransaction($user_to_credit, $details, $remark, $direct_commission, '+', $trx, $bonus_type, $charge, 'upgrade_bonus');

            // Debit from isolated Member Compensation Reserve Ledger
            try {
                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    (float)$direct_commission,
                    $trx,
                    $details,
                    'CRP',
                    'upgrade_bonus',
                    $user_to_credit->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit upgrade bonus: ' . $e->getMessage());
            }
        }
    }
}

// Indirect Referral Bonus: 13.00% spread across 1st to 10th generation per Proposed Plan
function indirectBonus(User $user, $pv, $details, $trx = null) {
    $trx = $trx ?? getTrx();
    $remark = 'Indirect_bonus_commission';
    $bonus_type = 17;
    $charge = 0;
    $dollar = rDollar();

    // Total base Naira volume = PV * ₦600/PV
    $baseNaira = (float)$pv * (float)$dollar;

    // Rates across 10 generations per Proposed Compensation Plan (Page 2)
    $rates = [
        1 => 0.03, // 1st Generation: 3% (e.g. 450 PV -> ₦8,100)
        2 => 0.02, // 2nd Generation: 2% (e.g. 450 PV -> ₦5,400)
        3 => 0.01, // 3rd Generation: 1% (e.g. 450 PV -> ₦2,700)
        4 => 0.01, // 4th Generation: 1%
        5 => 0.01, // 5th Generation: 1%
        6 => 0.01, // 6th Generation: 1%
        7 => 0.01, // 7th Generation: 1%
        8 => 0.01, // 8th Generation: 1%
        9 => 0.01, // 9th Generation: 1%
        10 => 0.01, // 10th Generation: 1%
    ];

    $currentUser = $user;
    for ($gen = 1; $gen <= 10; $gen++) {
        if (!$currentUser || !$currentUser->ref_by) {
            break;
        }

        $upline = User::lockForUpdate()->find($currentUser->ref_by);
        if (!$upline) {
            break;
        }

        $rate = $rates[$gen] ?? 0.0;
        $bonusAmount = round($baseNaira * $rate, 2);

        if ($bonusAmount > 0) {
            $upline->indirect_bonus += $bonusAmount;
            $upline->save();

            newTransaction(
                $upline,
                $details . ' (Gen ' . $gen . ' - ' . ($rate * 100) . '%)',
                $remark,
                $bonusAmount,
                '+',
                $trx,
                $bonus_type,
                $charge,
                'indirect_bonus'
            );

            // Debit from isolated Member Compensation Reserve Ledger
            try {
                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    $bonusAmount,
                    $trx,
                    'Indirect Referral Bonus to ' . $upline->username . ' (Gen ' . $gen . ')',
                    'CRP',
                    'indirect_bonus',
                    $upline->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit indirect bonus: ' . $e->getMessage());
            }
        }

        $currentUser = $upline;
    }
}

// Upgrade Indirect Bonus: 2% to 2nd generation, 1% each to 3rd through 10th generation
function upgradeIndirectBonus(User $sponsor, $pvDiff, $details, $trx = null) {
    $trx = $trx ?? getTrx();
    $remark = 'upgrade_indirect_commission';
    $bonus_type = 17;
    $charge = 0;
    $dollar = rDollar();
    $baseNaira = (float)$pvDiff * (float)$dollar;

    // Rates for generations 2 to 10 per Proposed Compensation Plan (Page 3)
    $rates = [
        2 => 0.02, // 2nd Generation: 2%
        3 => 0.01, // 3rd to 10th Generation: 1% each
        4 => 0.01,
        5 => 0.01,
        6 => 0.01,
        7 => 0.01,
        8 => 0.01,
        9 => 0.01,
        10 => 0.01,
    ];

    $currentUser = $sponsor;
    for ($gen = 2; $gen <= 10; $gen++) {
        if (!$currentUser || !$currentUser->ref_by) {
            break;
        }

        $upline = User::lockForUpdate()->find($currentUser->ref_by);
        if (!$upline) {
            break;
        }

        $rate = $rates[$gen] ?? 0.0;
        $bonusAmount = round($baseNaira * $rate, 2);

        if ($bonusAmount > 0) {
            $upline->indirect_bonus += $bonusAmount;
            $upline->save();

            newTransaction(
                $upline,
                $details . ' (Gen ' . $gen . ' - ' . ($rate * 100) . '%)',
                $remark,
                $bonusAmount,
                '+',
                $trx,
                $bonus_type,
                $charge,
                'indirect_bonus'
            );

            // Debit from isolated Member Compensation Reserve Ledger
            try {
                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    $bonusAmount,
                    $trx,
                    'Upgrade Indirect Bonus to ' . $upline->username . ' (Gen ' . $gen . ')',
                    'CRP',
                    'upgrade_indirect_bonus',
                    $upline->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit upgrade indirect bonus: ' . $e->getMessage());
            }
        }

        $currentUser = $upline;
    }
}

/**
 * Create a Welcome Back Package holding the registration cash-back amount,
 * in place of an instant product_wallet credit. The amount stays locked
 * until a stockist redeems the package code in person.
 *
 * @param  User   $user     The subscribing / registering user
 * @param  Project $project The project just subscribed to (amount = cash-back value)
 * @param  string $trx      Ledger reference for this registration
 * @param  string $details  Human-readable context for the package
 *
 * @see \App\Services\WelcomePackageService
 */
function processCashBack(User $user, Project $project, string $trx, string $details): void
{
    app(WelcomePackageService::class)->createForUser(
        $user,
        (float) $project->amount,
        WelcomePackage::SOURCE_REGISTRATION,
        $trx,
        $details
    );
}

/**
 * Create a Welcome Back Package holding the upgrade cash-back difference,
 * in place of an instant product_wallet credit. See processCashBack().
 *
 * @param  User   $user         The upgrading user
 * @param  float  $cashBackDiff New cash-back minus previously received cash-back
 * @param  string $details      Human-readable context for the package
 * @param  string $trx          Ledger reference for this upgrade
 *
 * @see WelcomePackageService
 */
function processUpgradeCashBack(User $user, float $cashBackDiff, string $details, string $trx): void
{
    app(WelcomePackageService::class)->createForUser(
        $user,
        $cashBackDiff,
        WelcomePackage::SOURCE_UPGRADE,
        $trx,
        $details
    );
}


function referralStageMAtrix($user_id, $details=null, $stage=1, $bonus_type=5, $trx = null)
{
    $trx =   $trx ?? getTrx();

    $user  = User::find($user_id);

    $user_to_credit = User::find($user->ref_by);
   
    if ($user_to_credit) {
        $mstage= MatrixStage::where('level', $stage)->first();

        if ($mstage) {
            $amount                = $mstage->joining;
            $remark = 'referral_commission';
            
            creditSavingsAccount($user_to_credit, $amount, $trx, $details, $remark);
        }
    }
}
function referralStageMAtrix_food($user_id, $details=null, $stage=1, $bonus_type=5)
{
    $user  = User::find($user_id);
    $user_to_credit = User::find($user->ref_by);
   
    if ($user_to_credit) { 

        $mstage= MatrixStage::where('level', $stage)->first();

        if ($mstage) {
            $amount = 900;
            $remark = 'referral_commission';

            creditSavingsAccount($user_to_credit, $amount, $trx, $details, $remark);
            //$user_to_credit->balance       += $amount;
            //$user_to_credit->save();
            /*
            $transaction               = new Transaction();
            $transaction->user_id      = $user_to_credit->id;
            $transaction->amount       = $amount;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $details;
            $transaction->remark       = 'referral_commission';
            $transaction->trx          = $user->trx;
            $transaction->post_balance = $user_to_credit->balance;
            $transaction->bonus_type   = $bonus_type;
            $transaction->save();

            notify($user_to_credit, 'REFERRAL_COMMISSION', [
                'trx'          => $user->trx,
                'amount'       => showAmount($amount, currencyFormat: false),
                'username'     => $user->username,
                'post_balance' => showAmount($user_to_credit->balance, currencyFormat: false),
            ]);
            */
        }
    }
}

 function promotionCommisionMatrix(User $user_to_credit, $trx, $details=null, $stage=1, $bonus_type=1)
    { 
        if ($user_to_credit) {
            $mstage= MatrixStage::where('level', $stage)->first();
            if ($mstage) {
                $amount = $mstage->price;
                $remark = 'stageOut_commission';
                creditSavingsAccount($user_to_credit, $amount, $trx, $details, $remark);
            }
        }
    }

function joiningCommisionMatrix($user_to_credit, $details=null, $stage=1, $bonus_type=1)
{   


    if ($user_to_credit) {
        $mstage= MatrixStage::where('level', $stage)->first();

        

        if ($mstage) {
            $amount                = $mstage->joining;

            $user_to_credit->balance       += $amount;
            $user_to_credit->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user_to_credit->id;
            $transaction->amount       = $amount;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $details;
            $transaction->remark       = 'joining_commission';
            $transaction->trx          = getTrx();
            $transaction->post_balance = $user_to_credit->balance;
            $transaction->bonus_type   = $bonus_type;
            $transaction->save();

            notify($user_to_credit, 'REFERRAL_COMMISSION', [
                'trx'          => $transaction->trx,
                'amount'       => showAmount($amount, currencyFormat: false),
                'username'     => $user_to_credit->username,
                'post_balance' => showAmount($user_to_credit->balance, currencyFormat: false),
            ]);
        }
    }
}




function sanitizeRequest(Request $request, array $rules = []) {
    $data = $request->all();

    if (!empty($rules)) {
        foreach ($rules as $field => $rule) {
            if (is_array($rule)) {
                // Apply multiple rules (e.g., trim and lowercase)
                $sanitizedValue = $data[$field];
                foreach ($rule as $individualRule) {
                    switch ($individualRule) {
                        case 'trim':
                            $sanitizedValue = trim($sanitizedValue);
                            break;
                        case 'lowercase':
                            $sanitizedValue = strtolower($sanitizedValue);
                            break;
                        case 'uppercase':
                            $sanitizedValue = strtoupper($sanitizedValue);
                            break;
                        case 'strip_tags':
                            $sanitizedValue = strip_tags($sanitizedValue);
                            break;
                        case 'htmlentities':
                            $sanitizedValue = htmlentities($sanitizedValue, ENT_QUOTES, 'UTF-8');
                            break;
                        // Add more rules as needed
                    }
                }
                $data[$field] = $sanitizedValue;
            } elseif (is_string($rule)) {
                // Apply a single rule (e.g., trim)
                switch ($rule) {
                    case 'trim':
                        $data[$field] = trim($data[$field]);
                        break;
                    case 'lowercase':
                        $data[$field] = strtolower($data[$field]);
                        break;
                    case 'uppercase':
                        $data[$field] = strtoupper($data[$field]);
                        break;
                    case 'strip_tags':
                        $data[$field] = strip_tags($data[$field]);
                        break;
                    case 'htmlentities':
                        $data[$field] = htmlentities($data[$field], ENT_QUOTES, 'UTF-8');
                        break;
                    // Add more rules as needed
                }
            }
        }
    }

    return $data;
}

function sanInput($var) {
        $vars = strip_tags($vars);
        $vars = trim($var);
        $vars = preg_replace('/\s+/', '', $vars);
        $vars = str_replace(' ', '', $vars);
        $vars = strtolower($vars);
        //$vars = htmlentities($vars, ENT_QUOTES, 'UTF-8');
        return $vars;  
    
}
function sanitizeInput($vars) {
        $vars = strip_tags($vars);
        $vars = trim($vars);
        $vars = preg_replace('/\s+/', '', $vars);
        $vars = str_replace(' ', '', $vars);
        $vars = strtolower($vars);
        //$vars = htmlentities($vars, ENT_QUOTES, 'UTF-8');
        return $vars;  
    
}



function retrunUserIDcard($user_id){
    
    return Useridcard::where('user_id', $user_id)->first();
   
}
             
function getMatrixPosition(Matrix $matrix, $user_id){

    if ($matrix->left == $user_id) {
          return 'left';  
     } else return 'right'; 

}

function getParent($user_id, $stage_id){
    
   $check =  Matrix::where('user_id', $user_id)->where('stage_id', $stage_id)->first();
   
        return $check;
}

function getMatrixStage($user_id){
    
   $check =  UserStageProgress::where('user_id', $user_id)->where('is_completed', false)->first();

   //dd($check);
        return $check;
}

function createBVLog($user_id, $lr, $amount, $details)
{
    $bvlog           = new BvLog();
    $bvlog->user_id  = $user_id;
    $bvlog->position = $lr;
    $bvlog->amount   = $amount;
    $bvlog->trx_type = '-';
    $bvlog->details  = $details;
    $bvlog->save();
}

function referralLandComission(User $user, $details, $percentage, $amount, $trx_no)
{   
    $amount_value = ($amount * ($percentage / 100));

    if ($user && $amount_value) {
        $remark = 'Shares_commission';
        
        creditSavingsAccount($user, $amount_value, $trx_no, $details, $remark);
            
         
           /*
            $user->balance  +=  $amount_value;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount_value;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $details;
            $transaction->remark       = 'Shares_commission';
            $transaction->trx          = $trx_no;
            $transaction->post_balance = $user->balance;
            $transaction->save();

                notify($user, 'Shares_commission', [
                    'trx'          => $trx_no,
                    'amount'       => showAmount($amount, currencyFormat: false),
                    'username'     => $user->username,
                    'post_balance' => showAmount($user->balance, currencyFormat: false),
                ]);

                //Fraction_land_commission
            */
           
    }
}

//2130
/**
 * Returns a deterministic, visually distinct hex colour for a given unilevel generation number (1–15).
 * Used in Blade views; safe to call from any context that loads helpers.
 */
function ulGenColor(int $number): string
{
    $palette = [
        1  => '#0D5C2E', 2  => '#16A34A', 3  => '#2563EB', 4  => '#7C3AED',
        5  => '#DB2777', 6  => '#EA580C', 7  => '#D97706', 8  => '#059669',
        9  => '#0891B2', 10 => '#4F46E5', 11 => '#9333EA', 12 => '#E11D48',
        13 => '#B45309', 14 => '#0F766E', 15 => '#1D4ED8',
    ];
    return $palette[$number] ?? '#6B7280';
}

function creditSavingsAccount(User $user, $amount, string $trx = null, $details= null, $remark= null)
{ 
    //creditSavingsAccount($user, $amount, $trx, $details, $remark);
    $trx = $trx ?? getTrx();

    $activeLoan = Loan::where('user_id', $user->id)
                      ->whereIn('status', ['active', 'at_risk'])
                      ->exists();

    // Walk through all active non-farm savings plans and pick the first one
    // that still has room (target savings must not have reached their target).
    $validSaving = SavingsProduct::where('user_id', $user->id)
        ->where('status', 'active')
        ->where('type', '!=', 'farm')
        ->get()
        ->first(function ($saving) {
            if ($saving->type === 'target' && $saving->target_amount > 0) {
                return $saving->balance < $saving->target_amount;
            }
                return true; // fixed savings: no cap to check
        });

    if ($validSaving) {
        $userShare    = round($amount * 0.90, 2);
        $serviceShare = round($amount - $userShare, 2);

        // 90% → Money Box, 10% → savings plan (savings_wallet mirrors total locked)
        
        //Updating the user table saving section
        $user->balance        += $userShare;
        $user->savings_wallet += $serviceShare;
        $user->save();

        // adding the money to the SavingsProduct
        $validSaving->balance   += $serviceShare;
        $validSaving->principal += $serviceShare;
        $validSaving->save();

        $txnUser               = new Transaction();
        $txnUser->user_id      = $user->id;
        $txnUser->amount       = $userShare;
        $txnUser->post_balance = $user->balance;
        $txnUser->charge       = 0;
        $txnUser->trx_type     = '+';
        $txnUser->details      = 'Savings credit (90%) to Money Box — savings: ' . $validSaving->name   .' FROM '. $details;
        $txnUser->trx          = $trx;
        $txnUser->remark       = 'savings_credit';
        $txnUser->save();

        $txnService               = new Transaction();
        $txnService->user_id      = $user->id;
        $txnService->amount       = $serviceShare;
        $txnService->post_balance = $user->balance;
        $txnService->charge       = 0;
        $txnService->trx_type     = '+';
        $txnService->details      = 'Service charge (10%) credited to savings: ' . $validSaving->name . ' (ref: ' . $validSaving->reference . ')'  .' FROM '. $details;
        $txnService->trx          = $trx;
        $txnService->remark       = 'savings_service_fee';
        $txnService->save();

        notify($user, $remark, [
            'amount'         => showAmount($amount, currencyFormat: false),
            'user_amount'    => showAmount($userShare, currencyFormat: false),
            'service_amount' => showAmount($serviceShare, currencyFormat: false),
            'savings_name'   => $validSaving->name,
            'trx'            => $trx,
            'post_balance'   => showAmount($user->balance, currencyFormat: false),
        ]);

    }else{

        // No valid savings plan (none exists or all targets reached): full amount to Money Box
        $user->balance += $amount;
        $user->save();

        $txn               = new Transaction();
        $txn->user_id      = $user->id;
        $txn->amount       = $amount;
        $txn->post_balance = $user->balance;
        $txn->charge       = 0;
        $txn->trx_type     = '+';
        $txn->details      =  $details;
        $txn->trx          = $trx;
        $txn->remark       = 'savings_credit';
        $txn->save();

        notify($user, $remark, [
            'amount'       => showAmount($amount, currencyFormat: false),
            'trx'          => $trx,
            'post_balance' => showAmount($user->balance, currencyFormat: false),
        ]);

    }
}
