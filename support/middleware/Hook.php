<?php
namespace support\middleware;
use RC\Container;
use RC\Http\Workerman\Response;
class Hook{
	use Container;
    public function handle($request, callable $next)
    {
    	if ($request::$app['controller']){
    		if ($request::$app['action'] === 'beforeAction' || $request::$app['action'] === 'afterAction') {
                return response('<h1>404 Not Found</h1>', 404);
            }
            $controller = Container::get($request::$app['class']);
            if (method_exists($controller, 'beforeAction')) {
                $before_response = call_user_func([$controller, 'beforeAction'], $request);
                if ($before_response instanceof Response) {
                    return $before_response;
                }
            }
            $response = $next($request);
            if (method_exists($controller, 'afterAction')) {
                $after_response = call_user_func([$controller, 'afterAction'], $request, $response);;
                if ($after_response instanceof Response) {
                    return $after_response;
                }
            }
            return $response;
    	}
    	return $next($request);
    }
}
?>