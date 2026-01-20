<?php
	use App\Lib\Response;

	$app->group('/salida/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de salida');			
		});

        // Obtener todas las salida
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->salida->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

		// Obtener todas las det_salida
		$this->get('getAllDetSalidas/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->det_salida->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Obtener salidas por fecha
		$this->get('getByDate/{inicio}/{final}', function($request, $response, $arguments) {
			return $response->withJson($this->model->salida->getByDate($arguments['inicio'], $arguments['final']));
		});

        // Cancelar salida
		$this->put('cancel/{salida}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$infoSalida = $this->model->salida->get($arguments['salida'])->result->fecha;
            $fechaSalida = substr($infoSalida, 0,10);
            $detSalid = $this->model->det_salida->getBySalida($arguments['salida'])->result;
			$count=count($detSalid);
			for($x=0;$x<$count;$x++){
				$cant = $detSalid[$x]->cantidad;
				$prod = $detSalid[$x]->fk_producto;
				$stocksum = $this->model->producto->stockSum($cant, $prod);
				if(!$stocksum->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($stocksum); 
				}
				$salidasrest = $this->model->kardex->salidasRest($cant, $prod, $fechaSalida);
				if(!$salidasrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($salidasrest); 
				}
				//$this->model->kardex->inicialfinalRest($cant, $prod, $fechaSalida);
				$this->model->kardex->arreglaKardex($prod, $fechaSalida);
			}
			$CancelaSalida=$this->model->salida->del($arguments['salida']);
			if($CancelaSalida){
				$seg_log = $this->model->seg_log->add('Cancelar salida',$arguments['salida'], 'salida'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($CancelaSalida);
		});

        // Agregar salida
		$this->post('add/', function($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$fecha=date("Y-m-d");
			$_fk_cliente = $parsedBody['fk_cliente'];
			$_cajero = $parsedBody['fk_cajero'];
			$_peso_total = $parsedBody['peso_total'];
			$data = [
				'fk_cliente'=>$_fk_cliente,
				'fk_cajero'=>$_cajero,
				'peso_total'=>$_peso_total,
				'total'=>$parsedBody['total'],
				'folio'=>$parsedBody['folio']
			];
			$resSalida =  $this->model->salida->add($data);
			$_fk_salida = $resSalida->result;
			if($_fk_salida){
				$seg_log = $this->model->seg_log->add('Agregar salida',$_fk_salida, 'salida'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			if(!$_fk_salida){
				$this->model->transaction->regresaTransaccion();
				return $response->withJson($_fk_salida);
			}
			$detalles = $parsedBody['detalles'];
			$cont=1;
			foreach($detalles as $detalle) {
				$_fk_producto = $detalle['id_producto'];
				if(intval($_fk_producto) <= 0){
					$respuesta=new Response();
					$respuesta->state=$this->model->transaction->regresaTransaccion(); 
					$respuesta->SetResponse(false,"Producto $cont incorrecto");
					return $response->withJson($respuesta);
				}
				$cont++;
                $_cantidad = $detalle['cantidad'];
				$_peso = $detalle['peso'];
				$_precio = $detalle['precio'];
				$_importe = $detalle['importe'];
				/* $produc = $this->model->producto->get($_fk_producto);
				if($produc->result->stock < $_cantidad){
					$this->model->transaction->regresaTransaccion();
					$produc->setResponse(false,'No hay suficiente stock para '.$produc->result->descripcion);
					unset($produc->result);
					return $response->withJson($produc);
				} */
				$stock = $this->model->kardex->kardexByDate($fecha, $_fk_producto);
                if($stock=='0'){
                    $_inicial = '0';
					$_final = '0';
					$_registroInit = $this->model->kardex->kardexInicial($fecha, $_fk_producto);
					if($_registroInit!='0'){
						$_inicial = $_registroInit;
					}
					$_registroFin = $this->model->kardex->kardexFinal($fecha, $_fk_producto);
					if($_registroFin!='0'){
						$_final = $_registroFin;
					}
                    if($_final == '0') { $_final = $_inicial; }
                    $dataKardex = [
                        'fk_producto'=>$_fk_producto,
                        'inicial'=>$_inicial,
                        'entradas'=>'0',
                        'salidas'=>'0',
                        'final'=>$_final
                    ];
                    $new_kardex = $this->model->kardex->add($dataKardex);
                    if(!$new_kardex->response) {
                        $this->model->transaction->regresaTransaccion();
                        return $response->withJson($new_kardex); 
                    }
                }
				$edit_producto = $this->model->producto->stockRest($_cantidad, $_fk_producto);
				if($edit_producto->response) {
					$edit_kardex = $this->model->kardex->salidasSum($_cantidad, $_fk_producto, $fecha);
					if($edit_kardex->response) {
						$dataDetSali = [
							'fk_salida'=>$_fk_salida,
							'fk_producto'=>$_fk_producto,
							'cantidad'=>$_cantidad,
							'peso'=>$_peso,
							'precio'=>$_precio,
							'importe'=>$_importe
						];
						$add_detalle = $this->model->det_salida->add($dataDetSali);
						if(!$add_detalle->response){
							$this->model->transaction->regresaTransaccion();
							return $response->withJson($add_detalle);
						}else{
							$edit_next_kardex = $this->model->kardex->inicialfinalRest($_cantidad, $_fk_producto, $fecha);
							if($edit_next_kardex->response) {
								$this->model->kardex->arreglaKardex($_fk_producto, $fecha);	
							}
						}
					}else{
						$this->model->transaction->regresaTransaccion();
						return $response->withJson($edit_kardex);
					}
				}else{
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($edit_producto);
				}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($resSalida);
		});

        // Editar salida
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$salida_id = $arguments['id'];
			$dataSalida = [
				'fk_cliente'=>$parsedBody['fk_cliente'],
				'fecha'=>$parsedBody['fecha'], 
				'peso_total'=>$parsedBody['peso_total']
			];
			$salida = $this->model->salida->edit($dataSalida, $salida_id);
			$detalles = $parsedBody['detalles'];
            foreach($detalles as $detalle) {
				$dataDetSalida = [
					'fk_producto'=>$detalle['id_producto'],
					'cantidad'=>$detalle['cantidad'], 
					'peso'=>$detalle['peso']
				];
                $salida = $this->model->det_salida->editBySalida($dataDetSalida, $arguments['id'], $detalle['id_producto']);
			}
			if($salida->response) {
				$seg_log = $this->model->seg_log->add('Actualización información salida', $salida_id, 'salida', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$salida->SetResponse(true, 'Salida actualizada');
			}else{
				$salida->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($salida); 
			}
			$salida->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($salida);
		});

        // Editar estado de salida
		$this->post('editEstado/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$salida_id = $arguments['id'];
			$dataSalida = [
				'estado'=>$parsedBody['estado']
			];
			$salida = $this->model->salida->edit($dataSalida, $salida_id);
			if($salida->response) {
				$seg_log = $this->model->seg_log->add('Actualización información salida', $salida_id, 'salida', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$salida->SetResponse(true, 'Salida actualizada');
			}else{
				$salida->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($salida); 
			}
			$salida->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($salida);
		});

        // Obtener salida por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->salida->get($arguments['id']);
			$resultado->detalles = $this->model->det_salida->getBySalida($arguments['id'])->result;
			return $response->withJson($resultado);
		});

		// Obtener ultimo folio salida
		$this->get('getFolio/', function($request, $response, $arguments) {
			$resultado = $this->model->salida->getFolio()->folio;
			return $response->withJson($resultado);
		});

        // Eliminar salida
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$salida_id = $arguments['id'];
			$del_salida = $this->model->salida->del($salida_id);
			if($del_salida->response) {
				$seg_log = $this->model->seg_log->add('Baja de salida', $salida_id, 'salida'); 
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			}else{ $del_salida->state = $this->model->transaction->regresaTransaccion();
				return $response->withJson($del_salida);
			}
			$del_salida->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_salida);
		});

        // Buscar salida
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->salida->find($arguments['busqueda']));
		});

		// Agregar detalle de salida
		$this->post('addDetalle/', function($request, $response, $arguments) {
            $parsedBody = $request->getParsedBody();
            $dataDetalles = [
				'fk_salida'=>$parsedBody['fk_salida'],
				'fk_producto'=>$parsedBody['fk_producto'], 
				'cantidad'=>$parsedBody['cantidad'], 
				'peso'=>$parsedBody['peso']
			];
			$add = $this->model->det_salida->add($dataDetalles);
			if($add){
				$seg_log = $this->model->seg_log->add('Agregar detalle salida',$parsedBody['fk_salida'], 'det_salida'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			return $response->withJson($add);
		});

		// Obtener det_salida por salida
		$this->get('getDetalles/{salida}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida->getBySalida($arguments['salida']));
		});

		// Eliminar salida
		$this->put('delDetalleSalida/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$del_det_salida = $this->model->det_salida->getById($arguments['id']);
			$cant = $del_det_salida->cantidad;
			$fecha = substr($del_det_salida->fecha,0,10);
			$salida = $del_det_salida->fk_salida;
			$prod = $del_det_salida->fk_producto;
			$del_detsalida = $this->model->det_salida->del($arguments['id']);
			if($del_detsalida->response){
				$stocksum = $this->model->producto->stockSum($cant, $prod);
				if(!$stocksum->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($stocksum); 
				}
				$salidasrest = $this->model->kardex->salidasRest($cant, $prod, $fecha);
				if(!$salidasrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($salidasrest); 
				}
				//$ok=
				$this->model->kardex->arreglaKardex($prod, $fecha);
				$PesoTotal = $this->model->det_salida->getPesoTotal($salida)->peso_total;
				$Total = $this->model->det_salida->getTotal($salida)->total;
				$data = [
					'peso_total'=>$PesoTotal,
					'total'=>$Total
				];
				$edit = $this->model->salida->edit($data, $salida);
				if($edit->response) {
					$seg_log = $this->model->seg_log->add('Baja de det_salida', $arguments['id'], 'det_salida'); 
					if(!$seg_log->response) {
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
						return $response->withJson($seg_log);
					}
				}else{
					$this->model->transaction->regresaTransaccion(); 
					return $response->withJson($edit); 
				}
			}else{
				$this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_detsalida); 
			}	
			$del_detsalida->result = null; 
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_detsalida);
		});

		// Obtener reporte entre fechas
		$this->get('rpt/{inicio}/{fin}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida->getRpt($arguments['inicio'], $arguments['fin']));
		});

		// Obtener reporte entre fechas (pdf)
		$this->get('rpt/print/{inicio}/{fin}', function($request, $response, $arguments) {
			$salidas = $this->model->det_salida->getRpt($arguments['inicio'], $arguments['fin']);
			$titulo = "REPORTE DE SALIDAS";
			$params = array('vista' => $titulo);
        	$params['registros'] = $salidas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			return $this->view->render($response, 'rptSalidas.php', $params);
		});

		// Obtener reporte por proveedor entre fechas
		$this->get('rptProv/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']));
		});

		// Obtener reporte por proveedor entre fechas (pdf)
		$this->get('rptProv/print/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			$salidas = $this->model->det_salida->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']);
			$titulo = "SALIDAS DE ALMACEN POR PROVEEDOR";
			$params = array('vista' => $titulo);
        	$params['registros'] = $salidas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['prov'] = $arguments['prov'];
			return $this->view->render($response, 'rptSalidasProv.php', $params);
		});

		// Obtener reporte por clave entre fechas
		$this->get('rptCve/{inicio}/{fin}/{cve}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve']));
		});

		// Obtener reporte por clave entre fechas (pdf)
		$this->get('rptCve/print/{inicio}/{fin}/{cve}', function($request, $response, $arguments) {
			$salidas = $this->model->det_salida->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve']);
			$titulo = "SALIDAS DE ALMACEN POR CLAVE DE DONADOR";
			$params = array('vista' => $titulo);
        	$params['registros'] = $salidas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['cve'] = $arguments['cve'];
			return $this->view->render($response, 'rptSalidasCve.php', $params);
		});

		// Obtener reporte por cliente entre fechas
		$this->get('rptCli/{inicio}/{fin}/{cliente}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_salida->getRptCli($arguments['inicio'], $arguments['fin'], $arguments['cliente']));
		});

		// Obtener reporte por cliente entre fechas (pdf)
		$this->get('rptCli/print/{inicio}/{fin}/{cliente}', function($request, $response, $arguments) {
			$salidas = $this->model->det_salida->getRptCli($arguments['inicio'], $arguments['fin'], $arguments['cliente']);
			$titulo = "SALIDAS DE ALMACEN POR CLIENTE";
			$params = array('vista' => $titulo);
        	$params['registros'] = $salidas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['cliente'] = $arguments['cliente'];
			return $this->view->render($response, 'rptSalidasCli.php', $params);
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->salida->findBy($args['f'], $args['v'])));			
		});

	});
?>