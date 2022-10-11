<?php
namespace support\middleware;
use RC\Helper\Throttler;
use RC\Container;
class ThrottlerMiddleware{
    public function handle($request, callable $next)
    {
    	static $throttler;
    	$throttler = $throttler ?? Container::make(Throttler::class, [cache()]);
        $key = $request->ip();
    	$capacity = 60; // The number of requests the "bucket" can hold
        $seconds = 60; // The time it takes the "bucket" to completely refill
        $cost = 1; // The number of tokens this action uses.
        if ($throttler->check($key, $capacity, $seconds, $cost) === false) {
        	return $request->response(json_encode(['success' => false, 'msg' => '请求此时太频繁'], JSON_UNESCAPED_UNICODE),429,['Content-Type' => 'application/json']);
        }
        return $next($request);
    }
}