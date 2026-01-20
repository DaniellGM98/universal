<?php
	namespace App\Lib;
	use App\Lib\JWT;

	class MiddlewareToken {
		public function __invoke($request, $response, $next) {
			if(!isset($_SESSION)) { session_start(); }
			if(isset($_SESSION['token'])) {
				$JWT = new JWT();
				if($JWT->descifrar($_SESSION['token'])) {
					$response = $next($request, $response);
				}else{
					return $response->withRedirect(URL_ROOT.'/login');
				}
			}else{
				return $response->withRedirect(URL_ROOT.'/login');
			}
			return $response;
		}
	}
?>