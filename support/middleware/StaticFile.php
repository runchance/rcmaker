<?php
namespace support\middleware;
class StaticFile{
	public function handle($request, callable $next)
    {

    	if($request::path()=='/1.jpg'){
    		//return response('file not allow to access',403,[]);
    		//return response()->download(public_path().'/1.jpg','1.jpg');
    		//return response()->file(public_path().'/test.css');
    	}
        return $next($request);
    }
}
?>