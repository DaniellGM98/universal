<?php
	use App\Lib\Response;

	//header('Access-Control-Allow-Origin: *');
	//header('Access-Control-Allow-Headers: *');
 
	$app->group('/seg_accion/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de seg_accion');
		});

        // Ruta para obtener los datos de seg_accion por medio del ID
		$this->get('get/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_accion->get($arguments['id']));
		});

        // Ruta para obtener los datos de los seg_accion
		$this->get('getAll/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_accion->getAll());
		});

        // Ruta para obtener los datos de los seg_accion mediante el ID del modulo
		$this->get('getByModulo/{fk_seg_modulo}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_accion->getByModulo($arguments['fk_seg_modulo']));
		});

        // Ruta para agregar un seg_accion
		$this->post('add/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_accion->add($request->getParsedBody()));
		});

        // Ruta para modificar un seg_accion
		$this->put('edit/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_accion->edit($request->getParsedBody(), $arguments['id']));
		});

		// Ruta para dar de baja un seg_accion
		$this->put('del/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_accion->del($arguments['id']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->seg_accion->findBy($args['f'], $args['v'])));			
		});
	});
?>