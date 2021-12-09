<?php
namespace support\middleware;
class AuthCheck{
    public function handle($request, callable $next)
    {
        if (!session('userinfo')) {
            return redirect('/user/login');
        }
        return $next($request);
    }
}