<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckStatus;
use App\Http\Middleware\Demo;
use App\Http\Middleware\KycMiddleware;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\RedirectIfAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RedirectIfNotAdmin;
use App\Http\Middleware\RegistrationStep;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laramin\Utility\VugiChugi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using:function(){
            Route::namespace('App\Http\Controllers')->middleware([VugiChugi::mdNm()])->group(function(){
                Route::middleware(['web'])
                    ->namespace('Admin')
                    ->prefix('admin')
                    ->name('admin.')
                    ->group(base_path('routes/admin.php'));

                    Route::middleware(['web','maintenance'])
                    ->namespace('Gateway')
                    ->prefix('ipn')
                    ->name('ipn.')
                    ->group(base_path('routes/ipn.php'));

                Route::middleware(['web','maintenance'])->prefix('user')->group(base_path('routes/user.php'));
                Route::middleware(['web','maintenance'])->group(base_path('routes/web.php'));

            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('web',[
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\LanguageMiddleware::class,
            \App\Http\Middleware\ActiveTemplateMiddleware::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            'admin' => RedirectIfNotAdmin::class,
            'admin.guest' => RedirectIfAdmin::class,

            'check.status' => CheckStatus::class,
            'demo' => Demo::class,
            'kyc' => KycMiddleware::class,
            'registration.complete' => RegistrationStep::class,
            'maintenance' => MaintenanceMode::class,
            'XssSanitizer' => \App\Http\Middleware\XssSanitization::class,

            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
            
            'admin.role' => \App\Http\Middleware\AdminRoleMiddleware::class,
            'admin.permission' => \App\Http\Middleware\AdminPermissionMiddleware::class,
            'admin.action.log' => \App\Http\Middleware\AdminActionLogMiddleware::class,
            'admin.access' => \App\Http\Middleware\AdminAccessMiddleware::class,

            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'stockist' => \App\Http\Middleware\StockistMiddleware::class,
            'stockist.mega' => \App\Http\Middleware\MegaStockistMiddleware::class,
            'cron.secret' => \App\Http\Middleware\CronSecret::class,
            'affiliate.track' => \App\Http\Middleware\TrackAffiliateClick::class,
        ]);

        $middleware->validateCsrfTokens(
            except: ['user/deposit','ipn*']
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function () {
            if (request()->is('api/*')) {
                return true;
            }
        });

        // Any unmatched route (or abort(404)/findOrFail()) redirects into the
        // app instead of showing a bare 404 page — admin section keeps the
        // admin guard/login, everything else uses the user guard/login.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            $notify = [['error', 'Page not found.']];

            if ($request->is('admin/*')) {
                return auth('admin')->check()
                    ? redirect()->route('admin.dashboard')->withNotify($notify)
                    : redirect()->route('admin.login')->withNotify($notify);
            }

            return auth()->check()
                ? redirect()->route('user.home')->withNotify($notify)
                : redirect()->route('user.login')->withNotify($notify);
        });
        $exceptions->respond(function (Response $response) {
            if ($response->getStatusCode() === 401) {
                if (request()->is('api/*')) {
                    $notify[] = 'Unauthorized request';
                    return response()->json([
                        'remark' => 'unauthenticated',
                        'status' => 'error',
                        'message' => ['error' => $notify]
                    ]);
                }
            }

            return $response;
        });
    })->create();
