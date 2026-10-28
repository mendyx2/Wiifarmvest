<?php 
use Illuminate\Support\Facades\Route;


Route::middleware('cron.secret')->group(function () {
    Route::get('cron', 'CronController@cron')->name('cron');
    Route::get('stageOne', 'CronController@stageOneComplete')->name('stageOneComplete');
    Route::get('stageOut', 'CronController@stageOut')->name('stageOut');

    // reconnect users to children in previous stages
    Route::get('recStart/{stage}', 'CronController@recFire')->name('recStart');
    Route::get('recon/{stage}', 'CronController@reconnectImmediate')->name('reconnectImme');
    // reconnect all downlines in a stage (active + inactive)
    Route::get('reconAll/{stage}', 'CronController@reconnectAll')->name('reconAll');
    // dispatch award payment processing jobs for all eligible users
    Route::get('paymentsDispatch', 'CronController@paymentsDispatch')->name('paymentsDispatch');
    
    
    // check and process award qualifications
    Route::get('awardCheck', 'CronController@awardCheck')->name('awardCheck');
    // retry missed ACB upline bonus payouts
    Route::get('acbProcess', 'CronController@acbProcess')->name('acbProcess');
    // dump database to storage/app/db_bk/ as a timestamped gzip-compressed SQL file
    Route::get('dbBackup', 'CronController@dbBackup')->name('dbBackup');
    // dispatch matching-bonus jobs for all eligible user matrices
    Route::get('matchingDispatch', 'CronController@matchingDispatch')->name('matchingDispatch');
    // sweep unclaimed autoship balances to admin (guarded internally to last day/hour of month)
    Route::get('autoshipSweep', 'CronController@autoshipSweep')->name('autoshipSweep');
    // clear application cache (config/route/view/application) after a deploy
    Route::get('clearCache', 'CronController@clearCache')->name('clearCache');
    // cancel stale unpaid/unpicked-up affiliate shop orders and release their reserved stock
    Route::get('affiliateOrdersExpire', 'CronController@affiliateOrdersExpire')->name('affiliateOrdersExpire');
});



// Public Affiliate Shop (guest-accessible, no auth): React pages in Layouts/PublicLayout.tsx
Route::namespace('Shop')->prefix('shop')->name('shop.')->middleware(['affiliate.track'])->group(function () {
    Route::get('/', 'ShopController@index')->name('index');
    Route::get('/product/{product}', 'ShopController@show')->name('product');
    Route::get('/img/{token}', 'ShopImageController@show')->name('image')->middleware('throttle:120,1');
    // Shared ads (Ad Manager): preview tags for social bots, redirect for people
    Route::get('/ad/{ad}', 'ShopAdController@show')->name('ad')->whereNumber('ad')->middleware('throttle:120,1');

    Route::get('/cart', 'ShopController@cart')->name('cart');
    Route::post('/cart/add', 'ShopCartController@add')->name('cart.add')->middleware('throttle:60,1');
    Route::post('/cart/update', 'ShopCartController@update')->name('cart.update')->middleware('throttle:60,1');
    Route::post('/cart/remove', 'ShopCartController@remove')->name('cart.remove')->middleware('throttle:60,1');

    Route::get('/checkout', 'ShopCheckoutController@index')->name('checkout');
    Route::post('/checkout', 'ShopCheckoutController@store')->name('checkout.store')->middleware('throttle:10,1');

    Route::get('/paystack/callback', 'ShopPaystackController@callback')->name('paystack.callback')->middleware('throttle:20,1');

    Route::get('/order/{orderCode}', 'ShopController@orderSuccess')->name('order.success');
    Route::get('/order/{orderCode}/pay', 'ShopCheckoutController@pay')->name('order.pay');
    Route::get('/order/{orderCode}/invoice', 'ShopController@orderInvoiceDownload')->name('order.invoice');
});


// User Support Ticket

Route::middleware(['auth', 'XssSanitizer'])->group(function () {
    Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group( function () { 
        Route::get('/', 'supportTicket')->name('index');
        Route::get('new', 'openSupportTicket')->name('open');
        Route::post('create', 'storeSupportTicket')->name('store');
        Route::get('view/{ticket}', 'viewTicket')->name('view');
        Route::post('reply/{id}', 'replyTicket')->name('reply');
        Route::post('close/{id}', 'closeTicket')->name('close');
        Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
    });
});

Route::controller('SiteController')->group( function () {
    
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');
    Route::get('agreement', 'aggreement')->name('agreement');
    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');
 
    Route::middleware(['auth', 'XssSanitizer'])->group(function () {
        // The member shop lives at user.products; these old public URLs forward there (removed duplicate pages)
        Route::get('/products/{catId?}', fn ($catId = null) => to_route('user.products', $catId))->name('products');
        Route::get('/product/{id}/{slug}', fn ($id, $slug) => to_route('user.product.details', [$id, $slug]))->name('product.details');
        Route::get('/products1/{catId?}', fn ($catId = null) => to_route('user.products', $catId))->name('products1');
        Route::get('/product1/{id}/{slug}', fn ($id, $slug) => to_route('user.product.details', [$id, $slug]))->name('product.details1');
    });

    // /check/referral, /check/matrixParent and /get/user/position removed: only the old
    // register page used them (now GET user/register/check). See notes Step 87.

    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::get('maintenance-mode', 'maintenance')->withoutMiddleware('maintenance')->name('maintenance');

    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home'); 
});
