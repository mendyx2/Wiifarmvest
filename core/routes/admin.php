<?php
 
use Illuminate\Support\Facades\Route;
Route::namespace('Auth')->group(function () {
    Route::middleware('admin.guest')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::get('/', 'showLoginForm')->name('login');
            Route::post('/', 'login'); // same URL as the GET route above, which owns the name
            Route::get('logout', 'logout')->middleware('admin')->withoutMiddleware('admin.guest')->name('logout');
        });
         

        // Admin Password Reset
        Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
            Route::get('reset', 'showLinkRequestForm')->name('reset');
            Route::post('reset', 'sendResetCodeEmail')->middleware('throttle:5,1');
            Route::get('code-verify', 'codeVerify')->name('code.verify');
            Route::post('verify-code', 'verifyCode')->name('verify.code')->middleware('throttle:10,1');
        });

        Route::controller('ResetPasswordController')->group(function () {
            Route::get('password/reset/{token}', 'showResetForm')->name('password.reset.form');
            Route::post('password/reset/change', 'reset')->name('password.change')->middleware('throttle:10,1');
        });
    });
}); 
// admin.access: deny-by-default permission check — see config/admin_access.php
Route::middleware(['admin','admin.access','XssSanitizer','admin.action.log'])->group(function () {
    Route::controller('AdminController')->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('dashboard1', 'parnerDashboard')->name('dashboard1');
        Route::get('chart/deposit-withdraw', 'depositAndWithdrawReport')->name('chart.deposit.withdraw');
        Route::get('chart/transaction', 'transactionReport')->name('chart.transaction');
        Route::get('profile', 'profile')->name('profile');
        Route::post('profile', 'profileUpdate')->name('profile.update');
        Route::get('password', 'password')->name('password'); 
        Route::post('password', 'passwordUpdate')->name('password.update');

        // Top-bar search (React admin layout): the pages this admin may open
        Route::get('search-index', 'searchIndex')->name('search.index');

        //Notification
        Route::get('notifications', 'notifications')->name('notifications');
        Route::get('notification/read/{id}', 'notificationRead')->name('notification.read');
        Route::get('notifications/read-all', 'readAllNotification')->name('notifications.read.all');
        Route::post('notifications/delete-all', 'deleteAllNotification')->name('notifications.delete.all');
        Route::post('notifications/delete-single/{id}', 'deleteSingleNotification')->name('notifications.delete.single');

        //Report Bugs
        Route::get('request-report', 'requestReport')->name('request.report');
        Route::post('request-report', 'reportSubmit');

        Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
        Route::get('view-attachment/{file_hash}', 'viewAttachment')->name('view.attachment');

        // Projects
        Route::controller(\App\Http\Controllers\Admin\AdminProjectController::class)
            ->prefix('projects')->name('projects.')->group(function () {
                Route::get('/',                'index')->name('index');
                Route::post('/',               'store')->name('store');
                Route::post('/{id}',           'store')->name('update');
                Route::delete('/{id}',         'destroy')->name('destroy');
                Route::post('/{id}/toggle',    'toggleStatus')->name('toggle');
            });

        // Unilevel
        Route::controller(\App\Http\Controllers\Admin\UnilevelController::class)
            ->prefix('unilevel')->name('unilevel.')->group(function () {
                // Generations CRUD
                Route::get('/generations',             'generationIndex')->name('generations.index');
                Route::post('/generations',            'generationStore')->name('generations.store');
                Route::post('/generations/{id}',       'generationStore')->name('generations.update');
                Route::delete('/generations/{id}',     'generationDestroy')->name('generations.destroy');
                Route::post('/generations/{id}/toggle','generationToggle')->name('generations.toggle');

                // Allocation
                Route::get('/allocate',                'allocationIndex')->name('allocate.index');
                Route::post('/allocate/{projectId}',   'allocationStore')->name('allocate.store');
            });

        //Route::resource('roles', RoleController::class);
        
    });

    

    Route::middleware(['admin.role:super-admin'])->group(function () {
        Route::resource('admins', \App\Http\Controllers\Admin\AdminUserController::class);
        Route::post('admins/{user}/toggle-status', [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])
            ->name('admins.toggle-status');
    
           
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
        //Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class);
    

    // Permissions Routes
   
        Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class);
        Route::get('permissions/bulk/create', [\App\Http\Controllers\Admin\PermissionController::class, 'bulkCreate'])
            ->name('permissions.bulk.create');
        Route::post('permissions/bulk/store', [\App\Http\Controllers\Admin\PermissionController::class, 'bulkStore'])
            ->name('permissions.bulk.store');
    });



    // Users Manager
    Route::controller('ManageUsersController')->name('users.')->prefix('users')->group(function () {
        Route::get('/', 'allUsers')->name('all');
        Route::get('active', 'activeUsers')->name('active');
        Route::get('banned', 'bannedUsers')->name('banned');
        Route::get('email-verified', 'emailVerifiedUsers')->name('email.verified');
        Route::get('email-unverified', 'emailUnverifiedUsers')->name('email.unverified');
        Route::get('mobile-unverified', 'mobileUnverifiedUsers')->name('mobile.unverified');
        Route::get('kyc-unverified', 'kycUnverifiedUsers')->name('kyc.unverified');
        Route::get('kyc-pending', 'kycPendingUsers')->name('kyc.pending');
        Route::get('mobile-verified', 'mobileVerifiedUsers')->name('mobile.verified');
        Route::get('with-balance', 'usersWithBalance')->name('with.balance');
        Route::get('paid', 'paidUsers')->name('paid');
        Route::get('free', 'freeUsers')->name('free');
        Route::get('stages', 'stages')->name('stages')->middleware('admin.role:super-admin'); 
  
        Route::get('detail/{id?}', 'detail')->name('detail'); 
        Route::get('kyc-data/{id}', 'kycDetails')->name('kyc.details');
        Route::post('kyc-approve/{id}', 'kycApprove')->name('kyc.approve');
        Route::post('kyc-reject/{id}', 'kycReject')->name('kyc.reject');
        Route::post('update/{id}', 'update')->name('update');
         
        Route::post('update_again/{id}', 'update_again')->name('update_again');
        Route::get('sponsor-history/{id}', 'sponsorHistory')->name('sponsor.history');
        Route::post('update_again1/{id}', 'update_again1')->name('update_again1');
        // change password
        Route::post('password/{id}', 'passwordUpdate')->name('update_password');

        Route::post('add-sub-balance/{id}', 'addSubBalance')->name('add.sub.balance')->middleware('admin.role:super-admin'); 
  ;
        Route::post('add-sub-balance-visa/{id}', 'addSubBalanceVisa')->name('add.sub.balance.visa')->middleware('admin.role:super-admin');
        Route::post('add-sub-balance-product-wallet/{id}', 'addSubBalanceProductWallet')->name('add.sub.balance.product.wallet')->middleware('admin.role:super-admin');
        Route::get('send-notification/{id}', 'showNotificationSingleForm')->name('notification.single');
        Route::post('send-notification/{id}', 'sendNotificationSingle'); // same URL as the GET route, which owns the name
        Route::get('login/{id}', 'login')->name('login');
        Route::post('status/{id}', 'status')->name('status');
        Route::post('withdrawal-block/{id}', 'toggleWithdrawalBlock')->name('withdrawal.block');

        Route::get('send-notification', 'showNotificationAllForm')->name('notification.all');
        Route::post('send-notification', 'sendNotificationAll')->name('notification.all.send');
        Route::get('list', 'list')->name('list');
        Route::get('count-by-segment/{methodName}', 'countBySegment')->name('segment.count');
        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log');

        Route::get('/tree/{id}', 'tree')->name('single.tree'); 
        Route::get('/user/tree/{user?}', 'otherTree')->name('other.tree');
        Route::get('/user/tree/search', 'otherTree')->name('other.tree.search');

        Route::get('referral/{id}', 'userRef')->name('referral');

        // matching bonus
        Route::post('matching-bonus/update', 'matchingUpdate')->name('matching-bonus.update');
        Route::post('buy-shares/{id}', 'buySharesForUser')->name('buy.shares');
        Route::post('ambassador/{id}', 'toggleAmbassador')->name('ambassador.toggle');

        // A member's pins: the pin list (PinController), under "view users" like the rest of this page
        Route::get('generate/pin/{id}', [\App\Http\Controllers\Admin\PinController::class, 'generatedBy'])->name('generate.pin');
        Route::get('used/pin/{id}', [\App\Http\Controllers\Admin\PinController::class, 'usedBy'])->name('used.pin');

    });

    // Deposit Gateway
    Route::name('gateway.')->prefix('gateway')->group(function () {
        // Automatic Gateway
        Route::controller('AutomaticGatewayController')->prefix('automatic')->name('automatic.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('edit/{alias}', 'edit')->name('edit');
            Route::post('update/{code}', 'update')->name('update');
            Route::post('remove/{id}', 'remove')->name('remove');
            Route::post('status/{id}', 'status')->name('status');
        });


        // Manual Methods
        Route::controller('ManualGatewayController')->prefix('manual')->name('manual.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('new', 'create')->name('create');
            Route::post('new', 'store')->name('store');
            Route::get('edit/{alias}', 'edit')->name('edit');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('status/{id}', 'status')->name('status');
        });
    });
    // Stockist Controller
    Route::controller('StockistController')->prefix('stockist')->name('stockist.')->group(function () {
        Route::get('/', 'allStockists')->name('index');
        Route::get('activeStockist', 'activeStockist')->name('activeStockist');
        Route::get('inactiveStockist', 'inactiveStockist')->name('inactiveStockist');
        // Old pages: redirect to the dashboard / details page.
        Route::get('detail/{id}', 'detail')->name('detail');

        // POST only: a GET route here used to change the status from a plain link.
        Route::post('status/{id}', 'changeUserStockistStatus')->name('changeUserStockistStatus');

        
   
        // the new stockist 

        Route::get('/stockist-dashboard',  'dashboard')->name('dashboard');
    
        // Stockist Activation
        Route::get('/stockist-activation', 'activationPage')->name('activation-page');
        Route::get('/search-user', 'searchUser')->name('search-user');
        Route::post('/activate-stockist', 'activateStockist')->name('activate');
        
        // Wallet Top-up
        Route::get('/stockist-wallet/{stockist}', 'walletTopUpPage')->name('wallet-page');
        Route::post('/topup-wallet/{stockist}', 'topUpWallet')->name('topup-wallet');
        Route::get('/stockist-details/{stockist}', 'showDetails')->name('details');

        // Redemption / accounting report
        Route::get('/report', 'redemptionReport')->name('report');
        Route::get('/report/{stockist}', 'redemptionReportShow')->name('report.show');

    });

    // Stock transfers between stockists (Mini buys from Mega)
    Route::controller('StockTransferController')->prefix('stockist/transfers')->name('stockist.transfers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{id}', 'show')->name('show')->whereNumber('id');
        Route::post('{id}/cancel', 'cancel')->name('cancel')->whereNumber('id');
    });

    // Stockist Orders Management
   Route::controller('StockistOrderController')->prefix('stockist-orders')->name('stockist-orders.')->group(function () {
       
        Route::get('/index',  'index')->name('index');
        Route::get('{order}', 'show')->name('show');
        Route::post('{order}/approve',  'approveOrder')->name('approve');
        Route::post('{order}/ship', 'shipOrder')->name('ship');
        Route::post('{order}/deliver', 'deliverOrder')->name('deliver');
        Route::post('{order}/process', 'processOrder')->name('processOrder');
        Route::post('{order}/cancel', 'cancelOrder')->name('cancel');

    });

    // Affiliate Program
    Route::controller('AffiliateController')->prefix('affiliate')->name('affiliate.')->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('orders', 'orders')->name('orders');
        Route::get('orders/{order}', 'orderShow')->name('order.show');
        Route::get('affiliates', 'affiliates')->name('affiliates');
        Route::get('funnel', 'funnel')->name('funnel');
        Route::get('audit', 'audit')->name('audit');
        Route::get('settings', 'settings')->name('settings');
        Route::post('settings', 'settingsUpdate')->name('settings.update');
    });

    // Ad Manager: media members share with their affiliate link
    Route::controller('AdController')->prefix('ads')->name('ads.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::get('{ad}/edit', 'edit')->name('edit');
        Route::post('{ad}/update', 'update')->name('update');
        Route::post('{ad}/status', 'status')->name('status');
        Route::post('{ad}/delete', 'destroy')->name('delete');
    });

    // Manage Sales: merged into Orders (Step 100); the old URLs still work.
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', fn (\Illuminate\Http\Request $request) => redirect()->route('admin.order.index', $request->only('search')))->name('index');
        Route::get('{id}', fn (int $id) => redirect()->route('admin.order.show', $id))->name('show')->whereNumber('id');
    });

    // DEPOSIT SYSTEM
    Route::controller('DepositController')->prefix('deposit')->name('deposit.')->group(function () {
        Route::get('all/{user_id?}', 'deposit')->name('list');
        Route::get('pending/{user_id?}', 'pending')->name('pending');
        Route::get('rejected/{user_id?}', 'rejected')->name('rejected');
        Route::get('approved/{user_id?}', 'approved')->name('approved');
        Route::get('successful/{user_id?}', 'successful')->name('successful');
        Route::get('initiated/{user_id?}', 'initiated')->name('initiated');
        Route::get('details/{id}', 'details')->name('details');
        Route::post('reject', 'reject')->name('reject');
        Route::post('approve/{id}', 'approve')->name('approve');
    });


    // WITHDRAW SYSTEM
    Route::name('withdraw.')->prefix('withdraw')->group(function () {

        Route::controller('WithdrawalController')->name('data.')->group(function () {

            Route::get('pending/{user_id?}', 'pending')->name('pending');
            Route::get('approved/{user_id?}', 'approved')->name('approved');
            Route::get('rejected/{user_id?}', 'rejected')->name('rejected');
            Route::get('all/{user_id?}', 'all')->name('all');
            Route::get('details/{id}', 'details')->name('details');
            Route::post('approve', 'approve')->name('approve');
            Route::post('reject', 'reject')->name('reject');
            // Bulk approve / reject from the list. "update-status" (any status, no refund)
            // and the "withdrawals" page (its view did not exist) were removed in Step 103.
            Route::post('bulk-action', 'bulkAction')->name('bulk-action');
        });


        // Withdraw Method
        Route::controller('WithdrawMethodController')->prefix('method')->name('method.')->group(function () {
            Route::get('/', 'methods')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('create', 'store')->name('store'); 
            Route::get('edit/{id}', 'edit')->name('edit');
            Route::post('edit/{id}', 'update')->name('update');
            Route::post('status/{id}', 'status')->name('status');
        });

    });

    // Report
    Route::controller('ReportController')->prefix('report')->name('report.')->group(function () {
        Route::get('financial', 'financial')->name('financial');
        Route::get('transaction/{user_id?}', 'transaction')->name('transaction');
        Route::get('login/history', 'loginHistory')->name('login.history');
        Route::get('login/ipHistory/{ip}', 'loginIpHistory')->name('login.ipHistory');
        Route::get('notification/history', 'notificationHistory')->name('notification.history');
        Route::get('email/detail/{id}', 'emailDetails')->name('email.details');
        Route::get('invest/{user_id?}', 'invest')->name('invest');
        Route::get('bv-log/{user_id?}', 'bvLog')->name('bvLog');
        Route::get('referral-commission/{user_id?}', 'refCom')->name('referral.commission');
        Route::get('stageout-commission/{user_id?}', 'stageOutCom')->name('stageOut.commission');
        Route::get('binary-commission/{user_id?}', 'binaryCom')->name('binary.commission');
        Route::get('stage-twice', 'stageTwice')->name('stage.twice');
        Route::post('stage-twice/download', 'stageTwiceDownload')->name('stage.twice.download');
        Route::post('stage-twice/refund/{id}', 'stageTwiceRefund')->name('stage.twice.refund');
    });


    // Admin Support
    Route::controller('SupportTicketController')->prefix('ticket')->name('ticket.')->group(function () {
        Route::get('/', 'tickets')->name('index');
        Route::get('pending', 'pendingTicket')->name('pending');
        Route::get('closed', 'closedTicket')->name('closed');
        Route::get('answered', 'answeredTicket')->name('answered');
        Route::get('view/{id}', 'ticketReply')->name('view');
        Route::post('reply/{id}', 'replyTicket')->name('reply');
        Route::post('close/{id}', 'closeTicket')->name('close');
        Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
        Route::post('delete/{id}', 'ticketDelete')->name('delete');
    });


    // Language Manager
    Route::controller('LanguageController')->prefix('language')->name('language.')->group(function () {
        Route::get('/', 'langManage')->name('manage');
        Route::post('/', 'langStore')->name('manage.store');
        Route::post('delete/{id}', 'langDelete')->name('manage.delete');
        Route::post('update/{id}', 'langUpdate')->name('manage.update');
        Route::get('edit/{id}', 'langEdit')->name('key');
        Route::post('import', 'langImport')->name('import.lang');
        Route::post('store/key/{id}', 'storeLanguageJson')->name('store.key');
        Route::post('delete/key/{id}', 'deleteLanguageJson')->name('delete.key');
        Route::post('update/key/{id}', 'updateLanguageJson')->name('update.key');
        Route::get('get-keys', 'getKeys')->name('get.key');
    });

    Route::controller('GeneralSettingController')->group(function () {

        Route::get('system-setting', 'systemSetting')->name('setting.system');

        // General Setting
        Route::get('general-setting', 'general')->name('setting.general');
        Route::post('general-setting', 'generalUpdate');

        //configuration
        Route::get('setting/system-configuration', 'systemConfiguration')->name('setting.system.configuration');
        Route::post('setting/system-configuration', 'systemConfigurationSubmit');

        // Logo-Icon
        Route::get('setting/logo-icon', 'logoIcon')->name('setting.logo.icon');
        Route::post('setting/logo-icon', 'logoIconUpdate'); // same URL as the GET route, which owns the name

        //Custom CSS
        Route::get('custom-css', 'customCss')->name('setting.custom.css');
        Route::post('custom-css', 'customCssSubmit');

        Route::get('sitemap', 'sitemap')->name('setting.sitemap');
        Route::post('sitemap', 'sitemapSubmit');

        Route::get('robot', 'robot')->name('setting.robot');
        Route::post('robot', 'robotSubmit');

        //Cookie
        Route::get('cookie', 'cookie')->name('setting.cookie');
        Route::post('cookie', 'cookieSubmit');

        //maintenance_mode
        Route::get('maintenance-mode', 'maintenanceMode')->name('maintenance.mode');
        Route::post('maintenance-mode', 'maintenanceModeSubmit');

        //Notice
        Route::get('notice', 'noticeIndex')->name('setting.notice');
        Route::post('notice/update', 'noticeUpdate')->name('setting.notice.update');
    });


    Route::controller('CronConfigurationController')->name('cron.')->prefix('cron')->group(function () {
        Route::get('index', 'cronJobs')->name('index');
        Route::post('store', 'cronJobStore')->name('store');
        Route::post('update', 'cronJobUpdate')->name('update');
        Route::post('delete/{id}', 'cronJobDelete')->name('delete');
        Route::get('schedule', 'schedule')->name('schedule');
        Route::post('schedule/store', 'scheduleStore')->name('schedule.store');
        Route::post('schedule/status/{id}', 'scheduleStatus')->name('schedule.status');
        Route::get('schedule/pause/{id}', 'schedulePause')->name('schedule.pause');
        Route::get('schedule/logs/{id}', 'scheduleLogs')->name('schedule.logs');
        Route::post('schedule/log/resolved/{id}', 'scheduleLogResolved')->name('schedule.log.resolved');
        Route::post('schedule/log/flush/{id}', 'logFlush')->name('log.flush');
    });


    //KYC setting
    Route::controller('KycController')->group(function () {
        Route::get('kyc-setting', 'setting')->name('kyc.setting');
        Route::post('kyc-setting', 'settingUpdate');
    });

    //Notification Setting
    Route::name('setting.notification.')->controller('NotificationController')->prefix('notification')->group(function () {
        //Template Setting
        Route::get('global/email', 'globalEmail')->name('global.email');
        Route::post('global/email/update', 'globalEmailUpdate')->name('global.email.update');

        Route::get('global/sms', 'globalSms')->name('global.sms');
        Route::post('global/sms/update', 'globalSmsUpdate')->name('global.sms.update');

        Route::get('global/push', 'globalPush')->name('global.push');
        Route::post('global/push/update', 'globalPushUpdate')->name('global.push.update');

        Route::get('templates', 'templates')->name('templates');
        Route::get('template/edit/{type}/{id}', 'templateEdit')->name('template.edit');
        Route::post('template/update/{type}/{id}', 'templateUpdate')->name('template.update');

        //Email Setting
        Route::get('email/setting', 'emailSetting')->name('email');
        Route::post('email/setting', 'emailSettingUpdate');
        Route::post('email/test', 'emailTest')->name('email.test');

        //SMS Setting
        Route::get('sms/setting', 'smsSetting')->name('sms');
        Route::post('sms/setting', 'smsSettingUpdate');
        Route::post('sms/test', 'smsTest')->name('sms.test');

        Route::get('notification/push/setting', 'pushSetting')->name('push');
        Route::post('notification/push/setting', 'pushSettingUpdate');
        Route::post('notification/push/setting/upload', 'pushSettingUpload')->name('push.upload');
        Route::get('notification/push/setting/download', 'pushSettingDownload')->name('push.download');
    });

    // Plugin
    Route::controller('ExtensionController')->prefix('extensions')->name('extensions.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('status/{id}', 'status')->name('status');
    });


    //System Information
    Route::controller('SystemController')->name('system.')->prefix('system')->group(function () {
        Route::get('info', 'systemInfo')->name('info');
        Route::get('server-info', 'systemServerInfo')->name('server.info');
        Route::get('optimize', 'optimize')->name('optimize');
        Route::get('optimize-clear', 'optimizeClear')->name('optimize.clear');
        Route::get('system-update', 'systemUpdate')->name('update');
        Route::post('system-update', 'systemUpdateProcess')->name('update.process');
        Route::get('system-update/log', 'systemUpdateLog')->name('update.log');

        Route::middleware('admin.role:super-admin')->group(function () {
            Route::get('action-log', 'adminActionLog')->name('action.log');
        });
    });


    // SEO
    Route::get('seo', 'FrontendController@seoEdit')->name('seo');
    
    // Flashcard Controller
    Route::controller('FlashcardController')->prefix('flashcard')->name('flashcard.')->group(function () {
        Route::get('/',            'index')->name('index');
        Route::post('store',       'store')->name('store');
        Route::patch('toggle/{id}','toggle')->name('toggle');
        Route::delete('{id}',      'destroy')->name('destroy');
    });

    // Pin Controller
    Route::controller('PinController')->prefix('pin')->name('pin.')->group(function () {
        Route::get('/', 'allPins')->name('index');
        Route::post('store', 'store')->name('store');
        Route::get('used', 'usedPins')->name('used');
        Route::get('unused', 'unusedPins')->name('unused');
        Route::get('generate', 'adminPins')->name('generate');
        Route::get('user/generate', 'userPins')->name('user');
    });

    // Frontend
    Route::name('frontend.')->prefix('frontend')->group(function () {

        Route::controller('FrontendController')->group(function () {
            Route::get('index', 'index')->name('index');
            Route::get('templates', 'templates')->name('templates');
            Route::post('templates', 'templatesActive')->name('templates.active');
            Route::get('frontend-sections/{key?}', 'frontendSections')->name('sections');
            Route::post('frontend-content/{key}', 'frontendContent')->name('sections.content');
            Route::get('frontend-element/{key}/{id?}', 'frontendElement')->name('sections.element');
            Route::get('frontend-slug-check/{key}/{id?}', 'frontendElementSlugCheck')->name('sections.element.slug.check');
            Route::get('frontend-element-seo/{key}/{id}', 'frontendSeo')->name('sections.element.seo');
            Route::post('frontend-element-seo/{key}/{id}', 'frontendSeoUpdate');
            Route::post('remove/{id}', 'remove')->name('remove');
        });

        // Page Builder
        Route::controller('PageBuilderController')->group(function () {
            Route::get('manage-pages', 'managePages')->name('manage.pages');
            Route::get('manage-pages/check-slug/{id?}', 'checkSlug')->name('manage.pages.check.slug');
            Route::post('manage-pages', 'managePagesSave')->name('manage.pages.save');
            Route::post('manage-pages/update', 'managePagesUpdate')->name('manage.pages.update');
            Route::post('manage-pages/delete/{id}', 'managePagesDelete')->name('manage.pages.delete');
            Route::get('manage-section/{id}', 'manageSection')->name('manage.section');
            Route::post('manage-section/{id}', 'manageSectionUpdate')->name('manage.section.update');

            Route::get('manage-seo/{id}', 'manageSeo')->name('manage.pages.seo');
            Route::post('manage-seo/{id}', 'manageSeoStore');
        });
    });

    //Plan 
    Route::controller('PlanController')->name('plan.')->prefix('plan')->group(function () {
        Route::get('index', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::get('users/{id?}', 'userPlan')->name('userPlan');
        Route::post('status/{id}', 'status')->name('status');
        Route::post('statusUser/{id}', 'statusUser')->name('statusUser');
    });

    //Category
    Route::controller('CategoryController')->name('category.')->prefix('category')->group(function () {
        Route::get('index', 'index')->name('index');
        Route::post('store/{id?}', 'store')->name('store');
        Route::post('status/{id}', 'status')->name('status');

    });

    //Product
    Route::controller('ProductController')->name('product.')->prefix('product')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/edit/{id}', 'edit')->name('edit');
        Route::post('/update/{id}', 'update')->name('update');
        Route::post('status/{id}', 'status')->name('status');
        Route::post('feature/{id}', 'feature')->name('feature');
        Route::post('/{id}/state-price', 'storeStatePrice')->name('state-price.store');
        Route::delete('/{id}/state-price/{priceId}', 'destroyStatePrice')->name('state-price.destroy');
    });

    // States
    Route::controller(\App\Http\Controllers\Admin\StateController::class)->name('state.')->prefix('state')->group(function () {
        Route::get('/',          'index')->name('index');
        Route::post('/',         'store')->name('store');
        Route::post('/{id}',     'store')->name('update');
        Route::delete('/{id}',   'destroy')->name('destroy');
    });

    //Order
    Route::controller('OrderController')->name('order.')->prefix('order')->group(function () {
        Route::get('detail/{id}', 'show')->name('show')->whereNumber('id');
        Route::get('/{user_id?}', 'index')->name('index')->whereNumber('user_id');
        Route::post('status/{id}', 'status')->name('status');
    });


    // Stockist Order Request (sorder) and Supplies: the stockist ordering system before Stockist Orders.
    // Nothing creates these records any more, and "Supplies" acted on member orders by mistake (Step 101).
    Route::get('sorder/{user_id?}', fn () => redirect()->route('admin.stockist-orders.index'))->name('sorder.index');
    Route::get('supply/{user_id?}', fn () => redirect()->route('admin.stockist-orders.index'))->name('supply.index');

    // Savings Management
    Route::middleware('admin.role:super-admin')->controller('AdminSavingsController')->prefix('savings')->name('savings.')->group(function () {
        Route::get('/',                          'index')->name('index');
        Route::get('/settings',                  'settings')->name('settings');
        Route::post('/settings',                 'storeSetting')->name('settings.store');
        Route::post('/settings/{id}',            'updateSetting')->name('settings.update');
        Route::get('/show/{id}',                 'show')->name('show');
        Route::get('/cycles',                    'cycles')->name('cycles');
        Route::post('/cycles/{id?}',             'storeCycle')->name('cycles.store');
        Route::post('/cycles/{id}/close',        'closeCycle')->name('cycles.close');
        Route::post('/cycles/{id}/mature',       'matureCycle')->name('cycles.mature');
        Route::post('/transfer/{id}',            'transferToBalance')->name('transfer');
    });

    // Loan Management
    Route::middleware('admin.role:super-admin')->controller('AdminLoanController')->prefix('loans')->name('loans.')->group(function () {
        Route::get('/',                          'index')->name('index');
        Route::get('/pending',                   'pending')->name('pending');
        Route::get('/show/{id}',                 'show')->name('show');
        Route::get('/products',                  'products')->name('products');
        Route::post('/products/{id?}',           'storeProduct')->name('products.store');
        Route::post('/products/toggle/{id}',     'toggleProduct')->name('products.toggle');
        Route::post('/approve/{id}',             'approve')->name('approve');
        Route::post('/reject/{id}',              'reject')->name('reject');
    });

    //Dividend Management - Super Admin Only
    Route::middleware('admin.role:super-admin')->controller('DividendController')->prefix('dividend')->name('dividend.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('store', 'store')->name('store');
        Route::post('buy-shares', 'buyShares')->name('buy-shares');
        Route::post('cancel/{batchId}', 'cancel')->name('cancel');
        Route::post('reverse/{batchId}', 'reverse')->name('reverse');
        Route::get('history', 'history')->name('history');
        Route::get('investors', 'investors')->name('investors');
        Route::get('detail/{batchId}', 'detail')->name('detail');
        Route::get('detail/{batchId}/search', 'searchTransactions')->name('detail.search');
        Route::get('user-dividends/{userId}', 'userDividends')->name('user.dividends');
    });

    // Repurchase (Unilevel) Awards
    Route::controller(\App\Http\Controllers\Admin\RepurchaseAwardController::class)->prefix('repurchase-award')->name('repurchase-award.')->group(function () {
        Route::get('/',                    'index')->name('index');
        Route::post('/',                   'store')->name('store');
        Route::post('/{id}',               'store')->name('update');
        Route::delete('/{id}',             'destroy')->name('destroy');
        Route::post('/{id}/toggle',        'toggle')->name('toggle');
        Route::get('/{id}/qualified',              'qualifiedUsers')->name('qualified');
        Route::post('/{awardId}/credit/{userId}',  'creditUser')->name('credit');
    });

    // ACT (Achievers Celebrated Bonus) member management
    Route::controller(\App\Http\Controllers\Admin\AdminAcbController::class)->prefix('acb')->name('acb.')->group(function () {
        Route::get('/',                   'index')->name('index');
        Route::post('/store',             'store')->name('store');
        Route::delete('/{acbUser}',       'destroy')->name('destroy');
    });

    // Autoship sweep — unclaimed month-end balances
    Route::controller(\App\Http\Controllers\Admin\AdminAutoshipController::class)->prefix('autoship')->name('autoship.')->group(function () {
        Route::get('/', 'index')->name('index');
    });

    // Direct notices — admin-composed personal notices sent to a single user
    Route::controller(\App\Http\Controllers\Admin\AdminNoticeController::class)->prefix('notices')->name('notices.')->group(function () {
        Route::get('/',        'index')->name('index');
        Route::get('/create',  'create')->name('create');
        Route::post('/',       'store')->name('store');
    });

    // Key-in bonus — 2% registration bonus paid to the sponsor who keyed in the signup
    Route::controller(\App\Http\Controllers\Admin\AdminKeyInBonusController::class)->prefix('key-in-bonus')->name('key-in-bonus.')->group(function () {
        Route::get('/', 'index')->name('index');
    });

    // Welcome Back Packages — registration/upgrade cash-back held as redeemable codes
    Route::controller(\App\Http\Controllers\Admin\AdminWelcomePackageController::class)->prefix('welcome-pack')->name('welcome-pack.')->group(function () {
        Route::get('/', 'index')->name('index');
    });

    // PV Logs
    Route::get('pv-logs', [\App\Http\Controllers\Admin\AdminPvLogController::class, 'index'])->name('pv-logs.index');

    // Award Management
    Route::controller(\App\Http\Controllers\Admin\AwardController::class)->prefix('awards')->name('awards.')->group(function () {
        Route::get('/',                'index')->name('index');
        Route::get('/create',          'create')->name('create');
        Route::post('/store',          'store')->name('store');
        Route::get('/edit/{award}',    'edit')->name('edit');
        Route::post('/update/{award}', 'update')->name('update');
        Route::post('/toggle/{award}', 'toggle')->name('toggle');
        Route::delete('/{award}',      'destroy')->name('destroy');
        Route::get('/payments',        'payments')->name('payments');
        Route::post('/pay/{userAward}','pay')->name('pay');
    });
});

