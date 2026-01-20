<?php
	//use Slim\App;

	//return function (App $app) {
		$container = $app->getContainer();

		// view renderer
		$container['renderer'] = function ($c) {
			$settings = $c->get('settings')['renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// rpt renderer
		$container['rpt_renderer'] = function ($c) {
			$settings = $c->get('settings')['rpt_renderer'];
			return new \Slim\Views\PhpRenderer($settings['template_path']);
		};

		// monolog
		$container['logger'] = function ($c) {
			$settings = $c->get('settings')['logger'];
			$logger = new \Monolog\Logger($settings['name']);
			$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
			$logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
			return $logger;
		};

		// Database
			$container['db'] = function($c) {
				$connectionString = $c->get('settings')['connectionString'];
				
				$pdo = new PDO($connectionString['dns'], $connectionString['user'], $connectionString['pass']);

				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

				return new \Envms\FluentPDO\Query($pdo);
				
			};
			
			// Register component view 
			$container['view'] = function ($container) {
				return new \Slim\Views\PhpRenderer('../templates/');
			};

			// Models
			$container['model'] = function($c) {
				return (object)[

					'usuario' => new App\Model\UsuarioModel($c->db),
					'seg_sesion' => new App\Model\SegSesionModel($c->db),
					'seg_log' => new App\Model\SegLogModel($c->db),
					'seg_permiso' => new App\Model\SegPermisoModel($c->db), //
					'seg_modulo' => new App\Model\SegModuloModel($c->db), //
					'seg_accion' => new App\Model\SegAccionModel($c->db), //
					'seg_pwd_recover' => new App\Model\SegPwdRecoverModel($c->db), //

					'transaction' => new App\Lib\Transaction($c->db),
					
					'registro' => new App\Model\RegistroModel($c->db), 
					'codigo' => new App\Model\CodigoModel($c->db), 
					'optativa' => new App\Model\OptativaModel($c->db), 
					'encuesta' => new App\Model\EncuestaModel($c->db), 
					
				];
			};
	//};
?>