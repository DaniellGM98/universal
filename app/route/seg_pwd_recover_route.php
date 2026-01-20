<?php
	use App\Lib\Response;

	// Grupo bajo la ruta seg_pwd_recover
	$app->group('/seg_pwd_recover/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de seg_pwd_recover');
		});

		 // Ruta para obtener un registro por medio del ID 
		$this->get('get/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_pwd_recover->get($arguments['id']));
		});

		 // Ruta para obtener un registro de la base de datos por medio de la clave generada
		$this->get('getByCodigo/{codigo}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_pwd_recover->getByCodigo($arguments['codigo']));
		});

		// Ruta para obtener las solicitudes de restablecimiento de contraseña por cliente
		$this->get('getByCliente/{page}/{limit}/{fk_cliente}[/{since}/{to}]', function ($request, $response, $arguments) {
			$arguments['since'] = isset($arguments['since'])? $arguments['since']: null;
			$arguments['to'] = isset($arguments['to'])? $arguments['to']: null;
			return $response->withJson($this->model->seg_pwd_recover->getByCliente($arguments['page'], $arguments['limit'], $arguments['fk_cliente'], $arguments['since'], $arguments['to']));
		});

		// Ruta para agregar un nuevo registro a la base de datos
		$this->post('add/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_pwd_recover->add($request->getParsedBody()));
		});

		// Ruta para modificar un registro de la base de datos mediante el ID
		$this->put('edit/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_pwd_recover->edit($request->getParsedBody(), $arguments['id']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->seg_pwd_recover->findBy($args['f'], $args['v'])));			
		});
	});
?>