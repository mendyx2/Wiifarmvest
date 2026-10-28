<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\UserNotify;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, UserNotify;
 
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'ver_code', 'balance', 'kyc_data'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'visa'     => 'integer',
        'email_verified_at'       => 'datetime',
        'kyc_data'                => 'object',
        'ver_code_send_at'        => 'datetime',
        'notifications_read_at'   => 'datetime',
    ];

    // Flashcard (global, admin-created)
    public function viewedFlashcards()
    {
        return $this->belongsToMany(Flashcard::class, 'flashcard_views')
                    ->withPivot('viewed_at');
    }

    public function pendingFlashcards()
    {
        $viewedIds = $this->viewedFlashcards()->pluck('flashcard_id');
        return Flashcard::active()->whereNotIn('id', $viewedIds)->latest()->get();
    }


    public function loginLogs()
    {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('id', 'desc');
    }
    public function transactions_commission()
    {
        return $this->hasMany(Transaction::class)->where('bonus_type', 2);
    }
    public function gatewayCurrency()
    {
        return GatewayCurrency::where('method_code', $this->method_code)->where('currency', $this->method_currency)->first();
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }
 
    public function tickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
    public function rinvestment()
    {
        return $this->hasMany(Rinvestment::class, 'user_id');
    }

    public function shtransactions()
    {
        return $this->hasMany(Shtransaction::class);
    }

    public function investment()
    {
        return $this->hasOne(Investment::class, 'user_id');
    }

     //stockist    
    public function stockist()
    {
        return $this->hasOne(Stockist::class);
    }

    public function isStockist()
    {
        return !is_null($this->stockist);
    }

    public function useridcard()
    {
        return $this->hasOne(Useridcard::class);
    }

    

    public function fullname(): Attribute
    {
        return new Attribute( 
            get: fn () => $this->firstname . ' ' . $this->lastname,
        );
    }
   
    public function mobileNumber(): Attribute
    {
        return new Attribute(
            get: fn () => $this->dial_code . $this->mobile,
        );
    }

    // SCOPES
    public function scopeActive($query)
    {
        return $query->where('profile_complete', Status::ACTIVE)->where('status', Status::USER_ACTIVE)->where('ev', Status::VERIFIED)->where('sv', Status::VERIFIED);
    }

    public function scopePaidUser($query)
    {
        return $query->where('plan_id', '!=', 0);
    }
    public function scopeFreeUser($query)
    {
        return $query->where('plan_id', 0);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', Status::USER_BAN);
    }

    public function scopeEmailUnverified($query)
    {
        return $query->where('ev', Status::UNVERIFIED);
    }

    public function scopeMobileUnverified($query)
    {
        return $query->where('sv', Status::UNVERIFIED);
    }

    public function scopeKycUnverified($query)
    {
        return $query->where('kv', Status::KYC_UNVERIFIED);
    }

    public function scopeKycPending($query)
    {
        return $query->where('kv', Status::KYC_PENDING);
    }

    public function scopeEmailVerified($query)
    {
        return $query->where('ev', Status::VERIFIED);
    }

    public function scopeMobileVerified($query)
    {
        return $query->where('sv', Status::VERIFIED);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function userExtra()
    {
        return $this->hasOne(UserExtra::class);
    }

    /**
     * Every member gets a user_extras row (BV and left/right counters) as soon as
     * they exist. Sign-up never created one, and the admin user page reads
     * $user->userExtra->bv_left without a null check (notes Step 88).
     */
    protected static function booted(): void
    {
        static::created(fn (User $user) => UserExtra::firstOrCreate(['user_id' => $user->id]));
    }

    public function refBy()
    {
        return $this->belongsTo(User::class, 'ref_by');
    }

    public function sponsorChangeLogs()
    {
        return $this->hasMany(SponsorChangeLog::class);
    }

    public function acbUser()
    {
        return $this->hasOne(AcbUser::class);
    }

    public function isAcb(): bool
    {
        return $this->acbUser()->exists();
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
    
    public function matrices()
    {
        return $this->hasMany(Matrix::class);
    }
    public function matrix_parent()
    {
        return $this->belongsTo(Matrix::class, 'parent_id');
    }
    
    
    public function stageProgress()
    {
        return $this->hasMany(UserStageProgress::class);
    }
    
    public function getCurrentStage()
    {
        return $this->stageProgress()->where('is_completed', false)
            ->orderBy('stage_id')
            ->first();
    }

    //product new

    public function cart()
    {
        return $this->hasOne(Cart::class)->withDefault([
            'user_id' => $this->id,
            'total_amount' => 0
        ]);
    }

    public function orders(){
        return $this->hasMany(Order::class);
    }

    public function orderStatus(){
        return $this->hasMany(Order::class)->where('status', 1);
    }
    public function invoiceRedemptions(){
        return $this->hasMany(InvoiceRedemption::class);
    }

    public function getTotalInvoiceRedemptionAttribute()
    { 
        return $this->invoiceRedemptions->sum('total_amount') ?? 0;
    } 

    public function getTotalOrderAmountAttribute()
    {
        return $this->orderStatus->sum('total_amount') ?? 0;
    }

    public function getTotalInvestAttribute()
    {
        return $this->rinvestment()->sum('five') ?? 0;
    }


    public function getCartItemsCountAttribute()
    {
        return $this->cart->items->sum('quantity') ?? 0;
    }



    public function getCartItemCountAttribute()
    {
        return $this->cart->items->count() ?? 0;
    }

    public function getWalletBalanceAttribute(){
        return $this->balance ?? 0;
    }
    public function getRepurchaseBalanceAttribute(){
        return $this->product_wallet ?? 0;
    } 
    

    public function deductWallet($amount)
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            $this->save();
            return true;
        }
        return false;
    }
    
    public function addWallet($amount)
    {
        $this->balance += $amount;
        $this->save();
        return true;
       
    }
    public function addToStockistRebate($amount)
    {
        // Atomic SQL increment: two redemptions at once can't overwrite each
        // other's rebate the way "+= then save()" could
        $this->increment('stockist_rebate', $amount);
        return true;
    }

    public function productDeductWallet($amount)
    {
        if ($this->product_wallet >= $amount) {
            $this->product_wallet -= $amount;
            $this->save();
            return true;
        }
        return false;
    }

    public function getAffiliateBonusBalanceAttribute()
    {
        return $this->affiliate_bonus ?? 0;
    }

    public function addAffiliateBonus($amount)
    {
        $this->affiliate_bonus += $amount;
        $this->save();
        return true;
    }

    public function deductAffiliateBonus($amount)
    {
        if ($this->affiliate_bonus >= $amount) {
            $this->affiliate_bonus -= $amount;
            $this->save();
            return true;
        }
        return false;
    }

    public function affiliateOrders()
    {
        return $this->hasMany(\App\Models\AffiliateOrder::class, 'affiliate_user_id');
    }

    public function affiliateClicks()
    {
        return $this->hasMany(\App\Models\AffiliateClick::class, 'affiliate_user_id');
    }

    public function adShares()
    {
        return $this->hasMany(\App\Models\AdShare::class);
    }

    public function getOrCreateAffiliateCode(): string
    {
        if (!empty($this->affiliate_code)) {
            return $this->affiliate_code;
        }

        do {
            $code = strtoupper(\Illuminate\Support\Str::random(8));
        } while (static::where('affiliate_code', $code)->exists());

        $this->affiliate_code = $code;
        $this->save();

        return $code;
    }


   

}
