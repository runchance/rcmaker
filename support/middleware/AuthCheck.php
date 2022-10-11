<?php
namespace support\middleware;
class AuthCheck{
    public function handle($request, callable $next)
    {
    	$session = $request->session();
        if (!$session->get('userinfo')) {
            return $request->redirect('/user/login');
        }
        return $next($request);
    }
}