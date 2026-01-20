<?php
	use App\Lib\Response;

	$app->group('/entrada/', function () {
		$this->get('', function($request, $response, $arguments) {
			return $response->withHeader('Content-type', 'text/html')->write('Soy ruta de entrada');
		});

        // Obtener todas las entrada
		$this->get('getAll/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->entrada->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Obtener entradas entre fechas
		$this->get('getByDate/{inicio}/{fin}', function($request, $response, $arguments) {
			$resultado = $this->model->entrada->getByDate($arguments['inicio'], $arguments['fin']);
			foreach ($resultado->result as $ent) {
				$ent->valor = number_format(floatval($ent->valor),2);
			}
			return $response->withJson($resultado);
		});

		// Cancelar entrada
		$this->put('cancel/{entrada}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$infoEntrada = $this->model->entrada->get($arguments['entrada'])->result->fecha;
            $fechaEntrada = substr($infoEntrada, 0,10);
            $detEntrad = $this->model->det_entrada->getByEntrada($arguments['entrada']);
			$count=count($detEntrad);
			for($x=0;$x<$count;$x++){
				$cant = $detEntrad[$x]->cantidad;
				$prod = $detEntrad[$x]->fk_producto;
				$stockrest = $this->model->producto->stockRest($cant, $prod);
				if(!$stockrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($stockrest); 
				}
				$entradasrest = $this->model->kardex->entradasRest($cant, $prod, $fechaEntrada);
				if(!$entradasrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($entradasrest); 
				}
				$inicialfinalrest = $this->model->kardex->inicialfinalRest($cant, $prod, $fechaEntrada);
				/* if(!$inicialfinalrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($inicialfinalrest); 
				} */
				$this->model->kardex->arreglaKardex($prod, $fechaEntrada);
				//$this->model->kardex->arreglaKardex($prod, $fechaEntrada);
			}
			$CancelaEntrada=$this->model->entrada->del($arguments['entrada']);
			if($CancelaEntrada){
				$seg_log = $this->model->seg_log->add('Cancelar entrada',$arguments['entrada'], 'entrada'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($CancelaEntrada);
		});

		// Agregar entrada
		$this->post('add/', function($request, $response, $arguments) {
			$this->response = new Response();
			$this->response->state = $this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
            $fecha=date("Y-m-d");
			$data = [
				'fk_proveedor'=>$parsedBody['fk_proveedor'],
				'nota_entrada'=>$parsedBody['nota_entrada'], 
				'referencia'=>$parsedBody['referencia'], 
				'peso_total'=>$parsedBody['peso_total'], 
				'tipo'=>$parsedBody['tipo']
			];
            if(isset($parsedBody['valor'])) { $data['valor'] = $parsedBody['valor']; }
			$resEntrada = $this->model->entrada->add($data);
			$fk_entrada = $resEntrada->result;
			if($fk_entrada){
				$seg_log = $this->model->seg_log->add('Agregar entrada',$fk_entrada, 'entrada'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
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

                $edit_producto = $this->model->producto->stockSum($_cantidad, $_fk_producto);


                if($edit_producto->response){
                    $edit_kardex = $this->model->kardex->entradasSum($_cantidad, $_fk_producto, $fecha);
                    if($edit_kardex->response){
                        $edit_next_kardex = $this->model->kardex->inicialfinalSum($_cantidad, $_fk_producto, $fecha);
                        if($edit_next_kardex->response){
                            $dataDetalleKardex = [
                                'fk_entrada'=>$fk_entrada,
                                'fk_producto'=>$_fk_producto, 
                                'cantidad'=>$_cantidad, 
                                'peso'=>$_peso
                            ];
							$this->model->kardex->arreglaKardex($_fk_producto, $fecha);
                            $add_detalle = $this->model->det_entrada->add($dataDetalleKardex);
                            if($add_detalle->response) {
                                //$this->model->kardex->arreglaKardex($_fk_producto, $fecha);
								$seg_log = $this->model->seg_log->add('Agregar información entrada', $add_detalle->result, 'entrada', 1);
								if(!$seg_log->response) {
									$seg_log->state = $this->model->transaction->regresaTransaccion(); 
									return $response->withJson($seg_log);
								}
                            }else{
                                $this->model->transaction->regresaTransaccion();
                                return $response->withJson($add_detalle); 
                            }
                        }else{
                            $this->model->transaction->regresaTransaccion();
                            return $response->withJson($edit_next_kardex); 
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
			return $response->withJson($resEntrada);
		});

		// Editar entrada
		$this->post('edit/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$entrada_id = $arguments['id'];
			$dataEntrada = [ 
				'fk_proveedor'=>$parsedBody['fk_proveedor'],
				'nota_entrada'=>$parsedBody['nota_entrada'], 
				'referencia'=>$parsedBody['referencia'], 
				'peso_total'=>$parsedBody['peso_total'],
				'tipo'=>$parsedBody['tipo'],
                'valor'=>$parsedBody['valor']
			];
			$entrada = $this->model->entrada->edit($dataEntrada, $entrada_id);

			$detalles = $parsedBody['detalles'];
            foreach($detalles as $detalle) {
				$dataDetEntrada = [
					
					'cantidad'=>$detalle['cantidad'], 
					'peso'=>$detalle['peso']
				];
                $entrada = $this->model->det_entrada->editByEntrada($dataDetEntrada, $arguments['id'], $detalle['id_producto']);
			}

			if($entrada->response) {
				$seg_log = $this->model->seg_log->add('Actualización información entrada', $entrada_id, 'entrada', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$entrada->SetResponse(true, 'Entrada actualizada');
			}else{
				$entrada->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($entrada); 
			}
			$entrada->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($entrada);
		});

		// Editar estado de entrada
		$this->post('editEstado/{id}', function($request, $response, $arguments) {
            require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$parsedBody = $request->getParsedBody();
			$entrada_id = $arguments['id'];
			$dataEntrada = [
				'estado'=>$parsedBody['estado']
			];
			$entrada = $this->model->entrada->editEstado($dataEntrada, $entrada_id);
			if($entrada->response) {
				$seg_log = $this->model->seg_log->add('Actualización información entrada', $entrada_id, 'entrada', 1);
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
				$entrada->SetResponse(true, 'Entrada actualizada');
			}else{
				$entrada->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($entrada); 
			}
			$entrada->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($entrada);
		});

        // Obtener entrada por id
		$this->get('get/{id}', function($request, $response, $arguments) {
			$resultado = $this->model->entrada->get($arguments['id']);
			$resultado->detalles = $this->model->det_entrada->getByEntrada($arguments['id']);
			return $response->withJson($resultado);
		});

		// Eliminar entrada
		$this->put('del/{id}', function($request, $response, $arguments) {
			$this->model->transaction->iniciaTransaccion();
			$entrada_id = $arguments['id'];
			$del_entrada = $this->model->entrada->del($entrada_id); 
			if($del_entrada->response) {	
				$seg_log = $this->model->seg_log->add('Baja de entrada', $entrada_id, 'entrada'); 
				if(!$seg_log->response) {
					$seg_log->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($seg_log);
				}
			} else { $del_entrada->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_entrada); 
			}
			$del_entrada->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_entrada);
		});

		// Buscar entrada
		$this->get('find/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->entrada->find($arguments['busqueda']));
		});

		// Buscar det_entrada
		$this->get('findDetEntrada/{busqueda}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada->find($arguments['busqueda']));
		});

		// Obtener todas las det_entrada
		$this->get('getAllDetEntradas/{pagina}/{limite}[/{busqueda}]', function($request, $response, $arguments) {
			$arguments['busqueda'] = isset($arguments['busqueda'])? $arguments['busqueda']: null;
			return $response->withJson($this->model->det_entrada->getAll($arguments['pagina'], $arguments['limite'], $arguments['busqueda']));
		});

        // Agregar detalle de entrada
		$this->post('addDetalle/', function($request, $response, $arguments) {
            $parsedBody = $request->getParsedBody();
			$this->model->transaction->iniciaTransaccion();
            $dataDetalles = [
				'fk_entrada'=>$parsedBody['fk_entrada'],
				'fk_producto'=>$parsedBody['fk_producto'], 
				'cantidad'=>$parsedBody['cantidad'], 
				'peso'=>$parsedBody['peso']
			];
			$add = $this->model->det_entrada->add($dataDetalles);
			if($add){
				$seg_log = $this->model->seg_log->add('Agregar detalle entrada',$parsedBody['fk_entrada'], 'det_entrada'); 
						if(!$seg_log->response) {
							$this->model->transaction->regresaTransaccion(); 
							return $response->withJson($seg_log);
						}
			}
			$this->model->transaction->confirmaTransaccion();
			return $response->withJson($add);
		});

        // Obtener det_entrada por entrada
		$this->get('getDetalles/{entrada}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada->get($arguments['entrada']));
		});

		// Obtener det_entrada por nota_entrada
		$this->get('getByNota/{fk_proveedor}/{inicio}/{fin}/{nota_entrada}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada->getByNota($arguments['fk_proveedor'], $arguments['inicio'], $arguments['fin'], $arguments['nota_entrada']));
		});

		// Eliminar entrada
		$this->put('delDetalleEntrada/{id}', function($request, $response, $arguments) {
			require_once './core/defines.php';
			$this->model->transaction->iniciaTransaccion();
			$del_det_entrada = $this->model->det_entrada->getById($arguments['id']);
			$cant = $del_det_entrada->cantidad;
			$fecha = substr($del_det_entrada->fecha,0,10);
			$entrada = $del_det_entrada->fk_entrada;
			$prod = $del_det_entrada->fk_producto;
			$del_detentrada = $this->model->det_entrada->del($arguments['id']);
			if($del_detentrada->response){
				$stockrest = $this->model->producto->stockRest($cant, $prod);
				if(!$stockrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($stockrest); 
				}
				$entradasrest = $this->model->kardex->entradasRest($cant, $prod, $fecha);
				if(!$entradasrest->response) {
					$this->model->transaction->regresaTransaccion();
					return $response->withJson($entradasrest); 
				}
				$this->model->kardex->arreglaKardex($prod, $fecha);
				//$this->model->kardex->arreglaKardex($prod, $fecha);
				$PesoTotal = $this->model->det_entrada->getPesoTotal($entrada)->peso_total;
				$data = [
					'peso_total'=>$PesoTotal
				];
				$edit = $this->model->entrada->edit($data, $entrada);
				if($edit->response) {
					$seg_log = $this->model->seg_log->add('Baja de det_entrada', $arguments['id'], 'det_entrada'); 
					if(!$seg_log->response) {
						$seg_log->state = $this->model->transaction->regresaTransaccion(); 
						return $response->withJson($seg_log);
					}
				}else{
					$del_detentrada->state = $this->model->transaction->regresaTransaccion(); 
					return $response->withJson($del_detentrada); 
				}
			}else{
				$del_detentrada->state = $this->model->transaction->regresaTransaccion(); 
				return $response->withJson($del_detentrada); 
			}
			$del_detentrada->result = null;
			$del_detentrada->state = $this->model->transaction->confirmaTransaccion();
			return $response->withJson($del_detentrada);
		});

		// Obtener reporte por clave de proveedor entre fechas
		$this->get('rpt/{inicio}/{fin}/{cve_prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada->getRpt($arguments['inicio'], $arguments['fin'], $arguments['cve_prov']));
		});

		// Obtener reporte por clave de proveedor entre fechas (pdf)
		$this->get('rpt/print/{inicio}/{fin}/{cve_prov}', function($request, $response, $arguments) {
			$entradas = $this->model->det_entrada->getRpt($arguments['inicio'], $arguments['fin'], $arguments['cve_prov']);
			$titulo = "CONCENTRADO DE ENTRADAS";
			$params = array('vista' => $titulo);
        	$params['registros'] = $entradas;
			$params['cveprov'] = $arguments['cve_prov'];
			$params['finicio'] = $arguments['inicio'];
			$params['ffin'] = $arguments['fin'];
			return $this->view->render($response, 'rptEntradas.php', $params);
		});

		// Obtener reporte por proveedor entre fechas
		$this->get('rptProv/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']));
		});

		// Obtener reporte por proveedor entre fechas (pdf)
		$this->get('rptProv/print/{inicio}/{fin}/{prov}', function($request, $response, $arguments) {
			$entradas = $this->model->det_entrada->getRptProv($arguments['inicio'], $arguments['fin'], $arguments['prov']);
			$titulo = "ENTRADAS A ALMACEN POR DONADOR";
			$params = array('vista' => $titulo);
        	$params['registros'] = $entradas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];

			return $this->view->render($response, 'rptEntradasProv.php', $params);
		});

		// Obtener reporte por clave entre fechas
		$this->get('rptCve/{inicio}/{fin}/{cve_prov}', function($request, $response, $arguments) {
			return $response->withJson($this->model->det_entrada->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve_prov']));
		});

		// Obtener reporte por clave entre fechas (pdf)
		$this->get('rptCve/print/{inicio}/{fin}/{cve_prov}', function($request, $response, $arguments) {
			$entradas = $this->model->det_entrada->getRptCve($arguments['inicio'], $arguments['fin'], $arguments['cve_prov']);
			$titulo = "ENTRADAS A ALMACEN POR CLAVE DE DONADOR";
			$params = array('vista' => $titulo);
        	$params['registros'] = $entradas;
			$params['inicio'] = $arguments['inicio'];
			$params['fin'] = $arguments['fin'];
			$params['clave'] = $arguments['cve_prov'];
			return $this->view->render($response, 'rptEntradasCve.php', $params);
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->entrada->findBy($args['f'], $args['v'])));			
		});
	});
?>