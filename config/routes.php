<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\Core\Plugin;
use App\Middleware\JwtAuthMiddleware;


/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 * Cache: Routes are cached to improve performance, check the RoutingMiddleware
 * constructor in your `src/Application.php` file to change this behavior.
 *
 */
Router::defaultRouteClass(DashedRoute::class);

Router::scope('/', function (RouteBuilder $routes) {
    // Register scoped middleware for in scopes.
    $routes->registerMiddleware('csrf', new CsrfProtectionMiddleware([
        'httpOnly' => true
    ]));

    /**
     * Apply a middleware to the current route scope.
     * Requires middleware to be registered via `Application::routes()` with `registerMiddleware()`
     */
    // $routes->applyMiddleware('csrf');

    /**
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, src/Template/Pages/home.ctp)...
     */
    $routes->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

    /**
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    $routes->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *
     * ```
     * $routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);
     * $routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);
     * ```
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $routes->fallbacks(DashedRoute::class);
});


Router::prefix('api/v1', function (RouteBuilder $routes) {
    // Connect API actions here.
    $routes->registerMiddleware('jwt', new JwtAuthMiddleware());
    // Apply JWT to all routes using below function
    // $routes->applyMiddleware('jwt');

    // Specific JWT implementation to routes.
    $routes->connect('/', array('controller' => "Error"));

    // *********** User Routes  ************
    $routes->connect('/users/*', ['controller' => 'Error', 'action' => "hackerMessage"]);
    // Public Routes  
    $routes->connect('/users/login', ['controller' => 'Users', 'action' => 'login']);
    $routes->connect('/users/signup', ['controller' => 'Users', 'action' => 'signup']);
    $routes->connect('/users/forgot_password', ['controller' => 'Users', 'action' => 'forgotPassword']);
    $routes->connect('/users/verify_account', ['controller' => 'Users', 'action' => 'verifyAccount']);
    $routes->connect('/users/resend_otp', ['controller' => 'Users', 'action' => 'resendOtp']);
    $routes->connect('/users/reset_password', ['controller' => 'Users', 'action' => 'resetPassword']);
    //  Private Routes
    $routes->connect('/users/profile', ['controller' => 'Users', 'action' => 'profile'])->setMiddleware(['jwt']);
    $routes->connect('/users/change_password', ['controller' => 'Users', 'action' => 'changePassword'])->setMiddleware(['jwt']);
    
    
    // *********** Pages Routes ************
    $routes->connect('/pages/*', ['controller' => 'Error', 'action' => "hackerMessage"]);
    $routes->connect('/pages/about_us', ['controller' => "Pages", "action" => "aboutUs"])->setMiddleware(['jwt']);
    $routes->fallbacks(DashedRoute::class);
});


Router::prefix('admin', function ($routes) {
    $routes->registerMiddleware('csrf', new CsrfProtectionMiddleware([
        'httpOnly' => true
    ]));
    $routes->applyMiddleware('csrf');

    $routes->connect('/', array('controller' => 'Users', 'action' => 'login'));
    // $routes->connect('/changepassword', array('controller' => 'Users', 'action' => 'changepassword'));
    $routes->fallbacks(DashedRoute::class);
});



/**
 * Load all plugin routes.  See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
Plugin::routes();
