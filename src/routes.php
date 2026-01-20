<?php
	use Slim\App;
	use Slim\Http\Request;
	use Slim\Http\Response;

	return function (App $app) {
		$container = $app->getContainer();

		$app->get('/[{name}]', function (Request $request, Response $response, array $args) use ($container) {
			require_once './core/defines.php';
			
			$page = isset($args['name'])?$args['name']:'';
			$this->logger->info("Slim-Skeleton '/' ".(isset($args['name'])?$args['name']:''));
			// if(!isset($args['name'])) { $args['name'] = HOMEPAGE; }

			if($args['name'] == 'registro' || $page == '') {
				return $this->renderer->render($response, 'registro.phtml', $args);
			}
			
			if(!isset($_SESSION)) { session_start(); }
			if((isset($_SESSION['usuario']))) {
				//if($args['name'] == '') {
				//	return $this->view->render($response, 'notificaciones.phtml', $args);
				//}else{
					// $arrMod = array('usuarios'=>5, 'perfil'=>5, 'prod-terminado'=>20, 'prod-terminado' );
					$params = array('vista' => ucfirst($args['name']));
					try{
						// if(array_key_exists($args['name'], $arrMod)) {
							// $modulo = $arrMod[$args['name']];
							$user = $_SESSION['usuario']->id;
							// $perm = $this->model->usuario->getAcciones($user, $modulo);
							$perm = $this->model->usuario->getAcciones($user, 0);
							$arrPerm = getPermisos($perm);

						$params = array('vista' => ucfirst($args['name']), 'permisos' => $arrPerm, 'todo' => $this);
							// if($args['name']=='resultados') {
							// 	date_default_timezone_set('America/Mexico_City');

							// 	$params['permisos'] = array_merge($arrPerm, getPermisos($this->model->usuario->getAcciones($user, 4)));
							// }

							// if(in_array($modulo, $arrPerm))
							// if(hasPermission($modulo))
								return $this->view->render($response, "$args[name].phtml", $params);
							// else
							// 	return $this->renderer->render($response, '403.phtml', $params);
						// }
							
						return $this->view->render($response, '403.phtml', $params);
					} catch (Throwable | Exception $e) {
						return $this->renderer->render($response, '404.phtml', $params);
					}
				//}
				// return $this->renderer->render($response, "$args[name].phtml", $args);
			// }elseif($args['name']!='login' || $args['name']!='registro') {
			}elseif($args['name']!='login' /* || $args['name']!='registro' */) {
				return $this->response->withRedirect(URL_ROOT.'/login');
			}else {
				return $this->renderer->render($response, 'login.phtml', $args);
			}
		});
	};

	function getPermisos($arrPerm) {
		$res = array();
		foreach($arrPerm as $perm) {
			$res[] = $perm->id;
		}
		return $res;
	}

	function hasPermission($mod) {
		$hasPerm = false;
		foreach($_SESSION['permisos'] as $modulo) {
			if($modulo->id == $mod) {
				$hasPerm = true;
				break;
			}
		}
		return $hasPerm;
	}
?>