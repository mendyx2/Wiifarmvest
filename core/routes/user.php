<?php

use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')->name('user.')->middleware(['guest', 'XssSanitizer'])->group(function () { 

    Route::controller('LoginController')->group(function () {
        Route::get('/login', 'showLoginForm')->name('login');
        Route::post('/login', 'login');
        Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
    });

    Route::controller('RegisterController')->middleware(['guest'])->group(function () {
        Route::get('register', 'showRegistrationForm')->name('register');
        // Removed duplicate page: old sign-up links (with ?ref=) land on the register page.
        Route::get('signup', fn () => to_route('user.register', request()->query()))->name('register1');
        Route::post('register', 'register');
        // Live checks on the register page (replaces check-user and the /check/* routes).
        Route::get('register/check', 'check')->name('register.check')->middleware('throttle:30,1');
    }); 

    Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
        Route::get('reset', 'showLinkRequestForm')->name('request');
        Route::post('email', 'sendResetCodeEmail')->name('email')->middleware('throttle:5,1');
        Route::get('code-verify', 'codeVerify')->name('code.verify');
        Route::post('verify-code', 'verifyCode')->name('verify.code')->middleware('throttle:10,1');
    });

    Route::controller('ResetPasswordController')->group(function () {
        Route::post('password/reset', 'reset')->name('password.update')->middleware('throttle:10,1');
        Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
    });
});

Route::middleware(['auth', 'XssSanitizer'])->name('user.')->group(function () {
    Route::get('user-data', 'User\UserController@userData')->name('data');
    // Removed duplicate page: for section-2 members it redirected to itself (an endless loop).
    Route::get('user-data1', fn () => to_route('user.data'))->name('data1');
    // POST user-data-submit removed: no page posted to it, and its code could not run
    // (it used a variable that was only set when a parent was given). See notes Step 86.

    //authorization
    Route::middleware('registration.complete')->namespace('User')->controller('AuthorizationController')->group(function () {
        Route::get('authorization', 'authorizeForm')->name('authorization');
        Route::get('resend-verify/{type}', 'sendVerifyCode')->name('send.verify.code')->middleware('throttle:6,1');
        // Throttled: the codes are six digits.
        Route::post('verify-email', 'emailVerification')->name('verify.email')->middleware('throttle:10,1');
        Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile')->middleware('throttle:10,1');
        Route::post('verify-g2fa', 'g2faVerification')->name('2fa.verify')->middleware('throttle:10,1');
    }); 

    //['middleware' => ['XssSanitizer']],
    Route::middleware(['check.status', 'registration.complete'])->group(function () {


         
        Route::namespace('User')->group(function () {

            Route::controller('UserController')->group(function () {
               
                Route::get('dashboard', 'home')->name('home');
                Route::get('notifications', 'notifications')->name('notifications');
                Route::post('notifications/mark-read', 'markNotificationsRead')->name('notifications.mark.read');
                // Removed duplicate page: searching a member's tree is on the My Tree page.
                Route::get('downline', fn () => to_route('user.my.tree'))->name('downline');
               
                

                Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
                Route::get('view-attachment/{file_hash}', 'viewAttachment')->name('view.attachment');

                //2FA
                Route::get('twofactor', 'show2faForm')->name('twofactor');
                Route::post('twofactor/enable', 'create2fa')->name('twofactor.enable');
                Route::post('twofactor/disable', 'disable2fa')->name('twofactor.disable');

                // Removed duplicate page: old URL redirects to the main page.
                Route::get('twofactor1', fn () => to_route('user.twofactor', request()->query()))->name('twofactor1');


                //KYC
                Route::get('kyc-form', 'kycForm')->name('kyc.form');
                Route::get('kyc-data', 'kycData')->name('kyc.data');
                Route::post('kyc-submit', 'kycSubmit')->name('kyc.submit');
                Route::get('kyc/user-search', 'userSearch')->name('kyc.user.search');

                // Guarantor
                Route::get('guarantor-requests', 'guarantorRequests')->name('guarantor.requests');
                Route::post('guarantor-requests/{id}/accept', 'guarantorAccept')->name('guarantor.accept');
                Route::post('guarantor-requests/{id}/decline', 'guarantorDecline')->name('guarantor.decline');

                //Report
                Route::get('deposit/history', 'depositHistory')->name('deposit.history');
                // Removed duplicate page. Kept as a redirect: older deposits store this URL as their success/failed URL.
                Route::get('deposit/history1', fn () => to_route('user.deposit.history', request()->query()))->name('deposit.history1');

                Route::get('transactions', 'transactions')->name('transactions');
                Route::get('transactions1', fn () => to_route('user.transactions', request()->query()))->name('transactions1'); // removed duplicate page

                //Balance Transfer
                // Removed: this "balance transfer" form posted to a GET-only URL and no code ever
                // moved the money. Bonus Transfer is the transfer page (notes Step 82).
                Route::get('transfer', fn () => to_route('user.bonus.transfer.index'))->name('balance.transfer');
                Route::post('add-device-token', 'addDeviceToken')->name('add.device.token');
                

                Route::get('orders1', fn () => to_route('user.orders.index'))->name('orders1'); // removed duplicate page
                

                  //product
                Route::get('/products/{catId?}', 'products')->name('products');
                Route::get('/product/{id}/{slug}', 'productDetails')->name('product.details');
                Route::get('/products1/{catId?}', fn ($catId = null) => to_route('user.products', $catId))->name('products1'); // removed duplicate page
                Route::get('/product1/{id}/{slug}', fn ($id, $slug) => to_route('user.product.details', [$id, $slug]))->name('product.details1'); // removed duplicate page
            });
            
            
            // FlashcardController routes
            Route::controller('FlashcardController')->group(function () {
                
                Route::post('/flashcard/{id}/dismiss',  'dismiss')
                    ->name('flashcard.dismiss');
                // GET /flashcards (flashcards.index) removed: FlashcardController has no
                // index() method, so the URL was a server error (notes Step 80).

            }); 

            Route::get('/pp/products', fn () => to_route('user.products'))->name('products.index'); // removed duplicate page

          

            // Order routes
            Route::controller('OrderController')->group(function () {
                
                Route::get('/checkout', 'checkout')->name('checkout');
                Route::post('/process-payment', 'processPayment')->name('process.payment');

                Route::get('/orders', 'index')->name('orders.index');
                Route::get('/orders/{order}',  'show')->name('orders.show');

            });

             // Cart routes
            Route::controller('CartController')->group(function (){
                Route::get('/cart', 'index')->name('cart.index');
                Route::post('/cart/add/{product}', 'addToCart')->name('cart.add');
                Route::put('/cart/update/{cartItem}',  'updateCart')->name('cart.update');
                Route::delete('/cart/remove/{cartItem}', 'removeFromCart')->name('cart.remove');
            });

          
            // Stockist routes
            Route::controller('StockistController')->group(function (){
              
                
                // Invoice redemption is done on the stockist dashboard (the old /redeem page had no view).
                Route::get('/redeem', fn () => to_route('user.stockist.dashboard'))->name('redeem.form');

                Route::get('/find-stockist', 'findStockist')->name('stockist.find');


                

            });

           

            // Stockist dashboard routes (protected by auth and stockist middleware)
            Route::controller('StockistController')->middleware(['stockist'])->prefix('stockist')->name('stockist.')->group(function () {
                //stockist profile
                Route::get('/profile',  'profile')->name('profile');
                Route::post('/profile/basic-info', 'updateBasicInfo')->name('profile.basic-info');
                Route::post('/profile/location', 'updateLocation')->name('profile.location');
                Route::post('/profile/opening-hours', 'updateOpeningHours')->name('profile.opening-hours');
                Route::post('/profile/services', 'updateServices')->name('profile.services');

                   
                // Stockists are created by the admin; the old self-setup page could not be reached.
                Route::get('/setup', fn () => to_route('user.stockist.dashboard'))->name('setup');
                
               
                
                
                Route::get('/dashboard', 'dashboard')->name('dashboard');

                
                Route::post('/process-redemption', 'processRedemption')->name('process.redemption');
                Route::get('/history', 'redemptionHistory')->name('history');
                Route::get('/redemptions', 'redemptionsReport')->name('redemptions');
            });


           
    
            // Product Catalog & Cart

            Route::controller('StockistInventoryController')->middleware(['stockist'])->prefix('stockist')->name('stockist.')->group(function () {

                 Route::get('/inventory',  'dashboard')->name('inventory.dashboard');

                // Ordering from the company: Mega stockists only (Minis buy from a Mega)
                Route::middleware('stockist.mega')->group(function () {
                    Route::get('/inventory/catalog',  'productsCatalog')->name('inventory.catalog');
                    Route::post('/inventory/cart/add', 'addToCart')->name('inventory.cart.add');
                    Route::post('/inventory/cart/update', 'updateCart')->name('inventory.cart.update');
                    Route::get('/inventory/checkout', 'checkout')->name('inventory.checkout');
                    Route::post('/inventory/order/place', 'placeOrder')->name('inventory.order.place');
                });

                // Order history stays open to every stockist (a Mini's earlier company orders)
                Route::get('/inventory/orders', 'orderHistory')->name('inventory.orders');
                Route::get('/inventory/orders/{order}', 'orderDetails')->name('inventory.order.details');
            });

            // Point of Sale
            Route::controller('StockistPosController')->middleware(['stockist'])->prefix('stockist/pos')->name('stockist.pos.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/checkout', 'checkout')->name('checkout');
            });

            // Stock transfers: Mini stockists buy from a Mega stockist, who confirms
            Route::controller('StockTransferController')->middleware(['stockist'])->prefix('stockist/transfers')->name('stockist.transfers.')->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/new', 'create')->name('create');
                Route::post('/', 'store')->name('store')->middleware('throttle:10,1');
                Route::get('/{id}', 'show')->name('show')->whereNumber('id');
                Route::post('/{id}/confirm', 'confirm')->name('confirm')->whereNumber('id')->middleware('throttle:20,1');
                Route::post('/{id}/decline', 'decline')->name('decline')->whereNumber('id')->middleware('throttle:20,1');
                Route::post('/{id}/cancel', 'cancel')->name('cancel')->whereNumber('id')->middleware('throttle:20,1');
            });

            // Welcome Back Package redemption
            Route::controller('StockistWelcomePackageController')->middleware(['stockist'])->prefix('stockist/welcome-pack')->name('stockist.welcome-pack.')->group(function () {
                Route::get('/', 'index')->name('index'); // ?code= verifies a code; redeeming happens in the POS
            });

            // Affiliate Program — every member gets this, no extra gating middleware
            Route::controller('AffiliateController')->prefix('affiliate')->name('affiliate.')->group(function () {
                Route::get('/', 'dashboard')->name('dashboard');
                Route::get('/orders', 'orders')->name('orders');
                Route::get('/ads', 'ads')->name('ads');
                Route::post('/ads/{ad}/share', 'adShared')->name('ads.share')->whereNumber('ad')->middleware('throttle:60,1');
            });

            // Redeem Affiliate Products — separate from the internal repurchase
            // redemption flow above (no unilevel/PV logic; single-shot pickup)
            Route::controller('StockistAffiliateRedemptionController')->middleware(['stockist'])->prefix('stockist/affiliate')->name('stockist.affiliate.')->group(function () { 
                Route::get('/', 'redeemForm')->name('redeem'); // ?code= verifies an order
                Route::post('/confirm', 'confirm')->name('confirm');
            });

            //Profile setting
            Route::controller('ProfileController')->group(function () {
                Route::get('profile-setting', 'profile')->name('profile.setting');
                Route::post('profile-setting', 'submitProfile'); 
                Route::get('change-password', 'changePassword')->name('change.password');
                Route::post('change-password', 'submitPassword');

                // Removed duplicate pages: old URLs redirect to the main pages.
                Route::get('profile-setting1', fn () => to_route('user.profile.setting', request()->query()))->name('profile.setting1');
                Route::get('change-password1', fn () => to_route('user.change.password', request()->query()))->name('change.password1');
            }); 

            //E-pin Recharge
            Route::controller('EpinController')->group(function () {

                Route::get('/e-pin/recharge', 'epin')->name('epin.recharge');
                Route::get('/e-pin/recharge/log', 'epinRechargeLog')->name('recharge.log');
               
                Route::post('/pin/generate', 'pinGenerate')->name('pin.generate');
            });
 

            // Withdraw
            Route::controller('WithdrawController')->prefix('withdraw')->name('withdraw')->group(function () {
                //Route::middleware('kyc')->group(function () {
                    Route::get('/', 'withdrawMoney');
                    Route::post('/', 'withdrawStore')->name('.money');
                    Route::get('preview', 'withdrawPreview')->name('.preview');
                    Route::post('preview', 'withdrawSubmit')->name('.submit');
                //});
                Route::get('history', 'withdrawLog')->name('.history');

            });




            // Removed duplicate withdraw flow (withdraw1/*): old URLs redirect to the main flow.
            Route::prefix('withdraw1')->name('withdraw1')->group(function () {
                Route::get('/now', fn () => to_route('user.withdraw', request()->query()))->name('.now');
                Route::get('preview1', fn () => to_route('user.withdraw.preview', request()->query()))->name('.preview1');
                Route::get('history1', fn () => to_route('user.withdraw.history', request()->query()))->name('.history1');
            });

            //MatricController.php
            Route::controller('MatrixController')->prefix('matrix')->name('matrix.')->group(function () {

                Route::post('/activate', 'activateMatrix')->name('activate');
            });

            Route::controller('DistributorController')->prefix('add-distributor')->name('distributor.')->group(function () {
                Route::get('/',              'showForm')->name('index');
                Route::post('/',             'store')->name('store');
                Route::get('/check',         'check')->name('check'); // live username / parent-slot checks
            });
           
            

            Route::controller('PlanController')->prefix('plan')->name('plan.')->group(function () {
                // planIndex never existed; the old plan page was the same thing as the portfolio (Buy Shares, subscriptions, receipts).
                Route::get('/', fn () => to_route('user.investment.portfolio'))->name('index'); // removed duplicate page
               // Route::get('/investment', 'planIndex1')->name('investment');
               // Route::get('/reservation', 'reservation')->name('reservation');
                //Route::post('/', 'planStore')->name('purchase');
                //Route::post('/', 'reserveStore')->name('reserve');
                Route::post('/generate-pdf', 'generatePdf')->name('payment-receipt.generate-pdf');
                
                Route::get('/portfolio', 'investmentPortfolio')->name('portfolio');
                Route::get('/{investmentId}/details', 'investmentDetails')->name('details');
            });

            Route::controller('PlanController')->group(function () {
                // Removed duplicate page (buying is the Buy Shares pop-up on the portfolio).
                Route::get('/shares', fn () => to_route('user.investment.portfolio'))->name('shares');
                Route::post('/shares', 'sharesStore')->name('shares.purchase');

                Route::get('investment/portfolio', 'investmentPortfolio')->name('investment.portfolio');
                Route::get('investment/{investmentId}/details', 'investmentDetails')->name('investment.details');

                Route::get('bv-log', 'bvlog')->name('bv.log');
                Route::get('pv-log', 'pvlog')->name('pv.log');
                Route::get('awards', 'myAwards')->name('awards');
                Route::get('repurchase-award', 'repurchaseAward')->name('repurchase.award');
                Route::get('acb', [\App\Http\Controllers\User\AcbController::class, 'index'])->name('acb');
                Route::get('my-tree', 'myTree')->name('my.tree');

                Route::get('my-stages', 'myTreeStages')->name('my.stages');

                Route::get('my-tree/{id}', 'myTreeStage')->name('my.treeStage');
                Route::get('referrals', 'myRefLog')->name('my.ref');
                Route::get('referrals1', fn () => to_route('user.my.ref', request()->query()))->name('my.ref1'); // removed duplicate page
                Route::get('binary-summery', 'binarySummery')->name('binary.summery');
                Route::get('binary-list', 'binaryList')->name('binary.list');
                Route::get('tree/{user}', 'otherTree')->name('other.tree');
                Route::get('tree/search', 'otherTree')->name('other.tree.search');
            });

            // Bonus Transfer
            Route::controller('BonusTransferController')->prefix('bonus-transfer')->name('bonus.transfer.')->group(function () {
                Route::get('/',               'index')->name('index');
                Route::post('/transfer',      'transfer')->name('submit');
                Route::post('/transfer-all',  'transferAll')->name('all');
                // GET /check-purchase removed: the page receives the autoship status with its props.
            });

            // My Project  
            Route::controller('ProjectController')->prefix('my-project')->name('project.')->group(function () {
                Route::get('/',         'index')->name('index');
                Route::post('/upgrade', 'upgrade')->name('upgrade');
            });

            
        });

        // Payment
        Route::prefix('deposit')->name('deposit.')->controller('Gateway\PaymentController')->group(function () {
            Route::any('/', 'deposit')->name('index');
            

            Route::post('insert', 'depositInsert')->name('insert');
            Route::get('confirm', 'depositConfirm')->name('confirm');
            Route::get('confirm1', fn () => to_route('user.deposit.confirm', request()->query()))->name('confirm1'); // removed duplicate flow
            Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
            Route::get('manual1', fn () => to_route('user.deposit.manual.confirm', request()->query()))->name('manual.confirm1'); // removed duplicate flow
            Route::post('manual', 'manualDepositUpdate')->name('manual.update');
        });
    });
});
