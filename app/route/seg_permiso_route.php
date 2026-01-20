<?php
	use App\Lib\Response;

	//header('Access-Control-Allow-Origin: *');
	//header('Access-Control-Allow-Headers: *');
 
	$app->group('/seg_permiso/', function () {
		$this->get('', function ($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de seg_permiso');
		});

        // Ruta para eliminar un permiso
        $this->delete('del/{user}/{accion}', function ($reques, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->delUserAccion($arguments['user'], $arguments['accion']));
		});

        // Ruta para agregar un permiso
		$this->post('add/', function ($request, $response, $arguments) {
			$parsedBody = $request->getParsedBody();
			return $response->withJson($this->model->seg_permiso->add($parsedBody));
		});

        // Ruta para obtener los datos de seg_permiso por medio del ID
		$this->get('get/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->get($arguments['id']));
		});

        // Ruta para obtener los datos de los seg_permiso
		$this->get('getAll/', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->getAll());
		});

        // Ruta para dar de baja un seg_permiso
		$this->put('del/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->del($arguments['id']));
		});

		// Ruta para modificar un seg_permiso
		$this->put('edit/{id}', function ($request, $response, $arguments) {
			return $response->withJson($this->model->seg_permiso->edit($request->getParsedBody(), $arguments['id']));
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->seg_permiso->findBy($args['f'], $args['v'])));			
		});
	});
?>