<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // List of global middleware 
        // Add other middleware as needed
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
        ],
        // 'api' => [
        //     // API middleware can be defined here
        //     'throttle:api',
        // ],

        'api' => [
            //\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            //'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ],
      

    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'custom.token' => \App\Http\Middleware\CustomTokenValidation::class,
    ];
}
