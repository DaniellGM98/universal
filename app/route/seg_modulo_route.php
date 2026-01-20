<?php
	use App\Lib\Response;
	use App\Lib\MiddlewareToken;

	//header('Access-Control-Allow-Origin: *');
	//header('Access-Control-Allow-Headers: *');
 
	$app->group('/seg_modulo/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de seg_modulo');
		});

        // Ruta para obtener los datos de seg_modulo por medio del ID
		$this->get('get/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_modulo->get($arguments['id']));
		});

        // Ruta para obtener los datos de los seg_modulo
		$this->get('getAll/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_modulo->getAll());
		});

        // Ruta para agregar un seg_modulo
		$this->post('add/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_modulo->add($request->getParsedBody()));
		});
		
        // Ruta para modificar un seg_modulo
		$this->put('edit/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_modulo->edit($request->getParsedBody(), $arguments['id']));
		});

        // Ruta para dar de baja un seg_modulo
		$this->put('del/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_modulo->del($arguments['id']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->seg_modulo->findBy($args['f'], $args['v'])));			
		});
	})->add( new MiddlewareToken() );
?>