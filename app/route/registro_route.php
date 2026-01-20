<?php
	use App\Lib\Response,
		PHPMailer\PHPMailer\PHPMailer,
		PHPMailer\PHPMailer\Exception,
		App\Lib\MiddlewareToken;
use Envms\FluentPDO\Literal;
error_reporting(0);

	$app->group('/registro/', function () {
		$this->get('', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'text/html')->write('Soy ruta de registro');
		});

        // Ruta para obtener los datos de registro por medio del ID
		$this->get('get/{id}', function ($req, $res, $args) {
			return $res->withJson($this->model->registro->get($args['id']));
		});

        // Ruta para obtener los datos de los registro
		$this->get('getAll/', function ($req, $res, $args) {
			$resultado = $this->model->registro->getAll();

			foreach ($resultado->result as $item) {
			    $item->color = $this->model->codigo->findBy('id', $item->codigo_id)->result[0]->color;
				$item->encuesta = $this->model->encuesta->findBy('registro_id', $item->id)->result;
				if($item->optativa != '0'){
					$opts = explode(',', $item->optativa);
					$optsInfo = $this->model->optativa->findBy('id', $opts)->result;
					$optsStr = array();
					foreach ($optsInfo as $opt) { $optsStr[] = $opt->nombre; }
					$item->optativa = implode(', ', $optsStr);
				}else{
					$item->optativa = '--';
				}
			}
			return $res->withJson($resultado);
		});

		// Ruta para obtener total de los registros
		$this->get('totalRegistros/', function ($req, $res, $args) {
			$resultado = $this->model->registro->totalRegistros();
			return $res->withJson($resultado);
		});

		// Ruta para obtener total checks de los registros
		$this->get('totalChecks/', function ($req, $res, $args) {
			$resultado = $this->model->registro->totalChecks();
			return $res->withJson($resultado);
		});

        // Ruta para agregar un registro
		$this->post('add/', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$data = $req->getParsedBody();
			$dataEnc = $data['encuesta'];
			unset($data['encuesta']);
			// $dataOptativa = $data['optInfo'];
			// unset($data['optInfo']);

			$code = $data['codigo'];
			$existe = $this->model->codigo->findBy('codigo', $code);
			$codigo = $existe->result[0];
			if($codigo->invitados > $codigo->usados){
				$data['codigo_id'] = $codigo->id;
				// $data['optativa_id'] = $data['optativa'];
				// checar usados de optativa
				// $opts = implode(',',$data['optativa']);
				// foreach ($data['optativa'] as $opt) {
				// 	$infoOpt = $this->model->optativa->get($opt)->result;
				// 	if($infoOpt->disponibles == $infoOpt->usados){
				// 		str_replace($opt,'',$opts);
				// 	}
				// }
				// if($opts == '' || $opts == ',') $data['optativa'] = '0';
				// else $data['optativa'] = $opts;
				// $opts = explode(',',$opts);

				$registro = $this->model->registro->add($data);
				if($registro->response){
					$idReg = $registro->result;
					$dataCode = array('usados' => new Literal('usados + 1'), 'registro' => new Literal('NOW()'));
					$codeEdit = $this->model->codigo->edit($dataCode, $codigo->id);
					if($codeEdit->response){
						// foreach ($opts as $opt) {
						// 	$dataOpt = array('usados' => new Literal('usados + 1'));
						// 	$this->model->optativa->edit($dataOpt, $opt);
						// }
						$dataEnc['registro_id'] = $idReg;
						$encuesta = $this->model->encuesta->add($dataEnc);
						if($encuesta->response){
							$qrCode = $idReg.'U'.$code.'W'.$encuesta->result;
							$registro->codigo = $qrCode;

							$fileUrl = 'data/qr/'.$qrCode.'.png';
							// $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs=400x400&chl='.urlencode($qrCode);
							$qrUrl = 'https://quickchart.io/qr?text='.urlencode($qrCode);
							$QR = file_get_contents($qrUrl);
							$file = fopen($fileUrl, 'w');
							fwrite($file, $QR);
							fclose($file);

							$to = $data['email'];
							// $to = 'isantos@ddsmedia.net';
							$subject = 'Registro Universal Workshop';
							$body = '<center>';
							$body .= '<img id="logo" src="'.URL_ROOT.'/assets/images/Universal2024.png'.'" alt="log" class="img-responsive img-thumbnail" style="max-width: 300px;">';
							$body .= '<h2 class="alert-heading">¡Listo! Has quedado confirmado al evento de <strong>Universal U&U Bogotá 2026</strong></h2>';

							$body .= '<p>Este es tu código el cual deberás presentar el día del evento.</p>';
							$body .= '<h2 class="alert-heading"><strong>IMPORTANTE:</strong></h2>';
							$body .= '<p><strong>Presenta</strong> este código QR el día del evento. El cual será el acceso personal para ingresar.</p>';

							$body .= '<h1 class="display-5">'.$qrCode.'</h1>';
							$body .= '<img id="imgQR" src="'.URL_ROOT.'/data/qr/'.$qrCode.'.png'.'" alt="qr" class="img-responsive img-thumbnail" style="max-width: 300px;">';

							$body .= '<br><p>Te invitamos a que <span style="background-color: yellow;"><strong>descargues</strong></span> este PDF. El cual contiene tu acceso para el evento.</p>';
							$body .= '<a href="'.URL_ROOT.'/registro/imprimir/'.$qrCode.'/'.$idReg.'" class="btn btn-sm waves-effect waves-light" style="border: 2px solid #000000; background-color: yellow; font-weight: bold; color: #000000; text-decoration: none;" id="btnDescargar"><span class="fa fa-qrcode"></span> Descargar PDF</a>';

							$body .= '<p>Te esperamos el día <strong>Viernes 27 de febrero de 2026</strong> en el Recinto Cine Colombia Titán Plaza ubicada en Carrera 72 # 80-94 Centro Comercial Titán Plaza, Bogotá, Colombia.</p>';

							// $body .= '<p>Recuerda que eres un invitado especial por lo que tu entrenamiento continuará en una sala VIP</p>';
							// $body .= '<p><strong>Duración: 30 minutos | Inicio: 11:00 am</strong></p>';

							//$validCodes = ["PRESSCOL25", "PRESSMX25", "PRESSGDL25", "PRESSMTY25"];
							//$programaEspecial = false;

							// Verifica que $codigo tiene el campo correcto
							// if (!isset($codigo->codigo)) {
							// 	error_log("Error: el código no tiene un valor válido.");
							// } else {
							// 	$codigoTexto = (string) $codigo->codigo; // Convertirlo a string por seguridad
							// 	error_log("Código extraído: " . $codigoTexto);

							// 	foreach ($validCodes as $code) {
							// 		if (strpos($codigoTexto, $code) !== false) { 
							// 			$programaEspecial = true;
							// 			break;
							// 		}
							// 	}
							// }

							// Si encontró un código válido, muestra el programa con entrevistas
							//if ($programaEspecial) {
								// $body .= '<p>Programa <br><br>
								// 	8:00 am Registro <br>
								// 	8:00 am Desayuno tipo bufet <br>
								// 	9:00 am Presentación Universal Destinations & Experiences<br>
								// 	Entrevistas a medios<br>
								// 	Fin del evento
								// </p>';
							//} else { 
								// Si no encontró un código válido, muestra el programa estándar
								$body .= '<p>Programa <br><br>
									8:00 am Registro <br>
									8:00 am Desayuno estilo networking<br>
									9:00 am Presentación Universal Orlando Resort<br>
									Fin del evento
								</p>';
							//}						

							// 			$body .= '<p>Programa <br><br>
							// 			8:00 am Registro <br>
							// 			8:00 am Desayuno tipo bufet <br>
							// 			9:00 am Presentación Universal Destinations & Experiences<br>
							// // 			10:00 am Sesiones con operadores <br>
							// // 			11:00 am Optativa Super Nintendo World<br>
							// // 			11:30 am Optativa Tips de Expertos<br>
							// 			Fin del evento
							// 		</p>';

							// if(count($opts) > 0){
							// 	$textoOpt = '';
							// 	foreach ($dataOptativa as $optInfo) {
							// 		if(in_array($optInfo['id'], $opts)){
							// 			$textoOpt .= '<br><strong>'.$optInfo['title'].'</strong>. <strong>'.$optInfo['info'].'</strong>';
							// 		}
							// 	}
							// 	if($textoOpt != ''){
							// 		$body .= '<p>La optativa en la que te registraste es ';
							// 		$body .= $textoOpt;
							// 		$body .= '</p>';
							// 	}
							// }

							$body .= '<p>Correo registrado <strong>'.$data['email'].'</strong></p>';
							$body .= '<hr>';
							
							// if ($programaEspecial) {
							// 	$body .= '<br><br>';								
							// 	$body .= '</center>';
							// }else{
								$body .= '<h4>¡Aún hay más!</h4>';
								$body .= '<p>Presenta tu certificado vigente* de entrenamientos de <strong>Universal Orlando Resort</strong> impreso y recibe una sorpresa el día del evento.<br>';
								$body .= '<p class="alert-heading" style="font-weight: bold;>
									No olvides seguirnos en nuestra red oficial para agentes de viajes en <br>Instagram
										<a href="https://www.instagram.com/universalpartnerslatino/?hl=en"
											target="_blank"
											style="text-decoration: underline;">
											@UniversalPartnersLatino
										</a>.
									</p>';
								$body .= 'Si aún no cuentas con ellos regístrate en: <br>';
								$body .= '<a href="https://www.universalpartnercommunity.com/s/login/SelfRegister?language=es" target="_blank">https://www.universalpartnercommunity.com/s/login/SelfRegister?language=es</a>';
								$body .= '<br><br>';
								$body .= '<small>*Para tener los certificados debes concluir los entrenamientos que hay en Universal Partner Community</small>';
								$body .= '</p>';
								$body .= '</center>';
							// }

							$sent = sendMailSMTP($to, $subject, $body, '', array($fileUrl));
							$registro->sent = $sent;
							
							$this->model->transaction->confirmaTransaccion();
							return $res->withJson($registro);
						}else{
							$this->model->transaction->regresaTransaccion();
							return $res->withJson($encuesta);
						}
						/* if($optEdit->response){
						}else{
							$this->model->transaction->regresaTransaccion();
							return $res->withJson($optEdit);
						} */
					}else{
						$this->model->transaction->regresaTransaccion();
						return $res->withJson($codeEdit);
					}
				}else{
					$this->model->transaction->regresaTransaccion();
					return $res->withJson($registro);
				}
			}else{
				$this->model->transaction->regresaTransaccion();
				return $res->withJson($existe->setResponse(false, 'Este código ya ha sido redimido anteriormente'));
			}
		});

		// Ruta para agregar un registro
		$this->post('add2/', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$data = $req->getParsedBody();
			$dataEnc = $data['encuesta'];
			unset($data['encuesta']);
			$dataOptativa = $data['optInfo'];
			unset($data['optInfo']);

			$code = $data['codigo'];
			$existe = $this->model->codigo->findBy('codigo', $code);
			$codigo = $existe->result[0];
			if($codigo->invitados > $codigo->usados){
				$data['codigo_id'] = $codigo->id;
				// $data['optativa_id'] = $data['optativa'];
				// checar usados de optativa
				$opts = implode(',',$data['optativa']);
				foreach ($data['optativa'] as $opt) {
					$infoOpt = $this->model->optativa->get($opt)->result;
					if($infoOpt->disponibles == $infoOpt->usados){
						str_replace($opt,'',$opts);
					}
				}
				if($opts == '' || $opts == ',') $data['optativa'] = '0';
				else $data['optativa'] = $opts;
				$opts = explode(',',$opts);

				$registro = $this->model->registro->add($data);
				if($registro->response){
					$idReg = $registro->result;
					$dataCode = array('usados' => new Literal('usados + 1'), 'registro' => new Literal('NOW()'));
					$codeEdit = $this->model->codigo->edit($dataCode, $codigo->id);
					if($codeEdit->response){
						foreach ($opts as $opt) {
							$dataOpt = array('usados' => new Literal('usados + 1'));
							$this->model->optativa->edit($dataOpt, $opt);
						}
						$dataEnc['registro_id'] = $idReg;
						$encuesta = $this->model->encuesta->add($dataEnc);
						if($encuesta->response){
							$qrCode = $idReg.'U'.$code.'W'.$encuesta->result;
							$registro->codigo = $qrCode;

							$fileUrl = 'data/qr/'.$qrCode.'.png';
							// $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs=400x400&chl='.urlencode($qrCode);
							$qrUrl = 'https://quickchart.io/qr?text='.urlencode($qrCode);
							$QR = file_get_contents($qrUrl);
							$file = fopen($fileUrl, 'w');
							fwrite($file, $QR);
							fclose($file);

							$to = $data['email'];
							// $to = 'isantos@ddsmedia.net';
							$subject = 'Registro Universal Workshop';
							$body = '<center>';
							$body .= '<img id="logo" src="'.URL_ROOT.'/assets/images/Universal2024.png'.'" alt="log" class="img-responsive img-thumbnail" style="max-width: 300px;">';
							$body .= '<h2 class="alert-heading">¡Listo! Has quedado confirmado al evento de <strong>Universal Workshop Zapopan, Jal.</strong></h2>';
							$body .= '<p>Programa <br><br>
										8:00 am Registro <br>
										8:00 am Desayuno tipo bufet <br>
										9:00 am Presentación Universal Destinations & Experiences<br>
										10:00 am Sesiones con operadores <br>
										11:00 am Optativa Super Nintendo World<br>
										11:30 am Optativa Tips de Expertos<br>
										Fin del evento
									</p>';
							$body .= '<p>Te invitamos a que descargues este PDF. El cual contiene tu acceso para el evento.</p>';
							$body .= '<a href="'.URL_ROOT.'/registro/imprimir/'.$qrCode.'/'.$idReg.'" class="btn btn-sm waves-effect waves-light" style="border: 2px solid #1e3365" id="btnDescargar"><span class="fa fa-qrcode"></span> Descargar PDF</a>';
							$body .= '<h1 class="display-5">'.$qrCode.'</h1>';
							$body .= '<img id="imgQR" src="'.URL_ROOT.'/data/qr/'.$qrCode.'.png'.'" alt="qr" class="img-responsive img-thumbnail" style="max-width: 300px;">';
							$body .= '<p>Te esperamos el día <strong>Jueves 18 de abril</strong> en Cinemex Plaza Patria, ubicado en Av. Patria 1950 colonia Jacarandas, Avenida Americas y Avila Camacho, Plaza Patria, 45160 Zapopan, Jal. a las 08:00 am.</p>';
							if(count($opts) > 0){
								$textoOpt = '';
								foreach ($dataOptativa as $optInfo) {
									if(in_array($optInfo['id'], $opts)){
										$textoOpt .= '<br><strong>'.$optInfo['title'].'</strong>. <strong>'.$optInfo['info'].'</strong>';
									}
								}
								if($textoOpt != ''){
									$body .= '<p>La optativa en la que te registraste es ';
									$body .= $textoOpt;
									$body .= '</p>';
								}
							}
							$body .= '<p>Correo registrado <strong>'.$data['email'].'</strong></p>';
							$body .= '<hr>';
							$body .= '<h4>¡Aún hay más!</h4>';
							$body .= '<p>Presenta tu certificado vigente* de entrenamientos de <strong>Universal Orlando Resort y Universal Studios Hollywood</strong> impreso y recibe una sorpresa el día del evento.<br>';
							$body .= 'Si aun no cuentas con ellos regístrate en: <br>';
							$body .= '<a href="https://www.universalpartnercommunity.com/s/login/SelfRegister?language=es" target="_blank">https://www.universalpartnercommunity.com/s/login/SelfRegister?language=es</a>';
							$body .= '<br><br>';
							$body .= '<small>*Para tener los certificados debes concluir los entrenamientos que hay en Universal Partner Community</small>';
							$body .= '</p>';
							$body .= '</center>';
							$sent = sendMailSMTP($to, $subject, $body, '', array($fileUrl));
							$registro->sent = $sent;

							// WhatsApp..

							$params['header'] = 'https://universal.clase.digital/assets/images/Universal2024.png';
							$params['telefono'] = '7711617545';
							$params['body'] = '
*'.$data['nombre'].'*, has quedado *confirmado* al evento de *Universal Workshop Zapopan, Jal.* 
		
*Programa*

*8:00 am* Registro
*8:00 am* Desayuno tipo bufet
*9:00 am* Presentación Universal Destinations & Experiences
*10:00 am* Sesiones con operadores
*11:00 am* Optativa Super Nintendo World
*11:30 am* Optativa Tips de Expertos
Fin del evento

Te esperamos el día *Jueves 18 de abril* en *Cinemex Plaza Patria*, ubicado en Av. Patria 1950 colonia Jacarandas, Avenida Americas y Avila Camacho, Plaza Patria, 45160 Zapopan, Jal. a las *08:00 am*.

*¡Aún hay más!*
Presenta tu certificado vigente de entrenamientos de Universal Destinations & Experiences impreso y recibe una sorpresa el día del evento.

Para tener los certificados debes concluir los entrenamientos que hay en Universal Partner Community
		
';
							$params['codigo'] = ''.$qrCode;
							$params['id'] = ''.$idReg;
							$this->view->render($res, 'registroWA.php', $params);

							// ..WhatsApp
							
							$this->model->transaction->confirmaTransaccion();
							return $res->withJson($registro);
						}else{
							$this->model->transaction->regresaTransaccion();
							return $res->withJson($encuesta);
						}
						/* if($optEdit->response){
						}else{
							$this->model->transaction->regresaTransaccion();
							return $res->withJson($optEdit);
						} */
					}else{
						$this->model->transaction->regresaTransaccion();
						return $res->withJson($codeEdit);
					}
				}else{
					$this->model->transaction->regresaTransaccion();
					return $res->withJson($registro);
				}
			}else{
				$this->model->transaction->regresaTransaccion();
				return $res->withJson($existe->setResponse(false, 'Este código ya ha sido redimido anteriormente'));
			}
		});

		$this->put('checkin/{id}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();
			$info = $this->model->registro->get($args['id']);

			if($info->response){
				if($info->result->checkin == null){
					$info = $info->result;
					$data = array('checkin' => new Literal('NOW()'));
					$codeEdit = $this->model->codigo->edit($data, $info->codigo_id);
					if($codeEdit->response){
						$resultado = $this->model->registro->edit($data, $args['id']);
						if($resultado->response){
							$resultado->checkin = date('Y-m-d H:i:s');
							$resultado->setResponse(true, $info->nombre.', Bienvenido(a) a Universal Workshop ,'.$info->color);
							$resultado->state = $this->model->transaction->confirmaTransaccion(); 
						}else{
							$resultado->state = $this->model->transaction->regresaTransaccion();
							return $res->withJson($resultado->setResponse(false,'Ocurrio algo extraño. Vuelve a intentar'));
						}
					}else{
						$codeEdit->state = $this->model->transaction->regresaTransaccion();
						return $res->withJson($codeEdit->setResponse(false,'Ocurrio algo extraño. Vuelve a intentar'));
					}
				}else{
					$info->state = $this->model->transaction->regresaTransaccion();
					return $res->withJson($info->setResponse(false, 'Ya se registró el CheckIn anteriormente'));
				}
			}else{
				$info->state = $this->model->transaction->regresaTransaccion();
				return $res->withJson($info->setResponse(false, 'QR incorrecto'));
			}
			return $res->withJson($resultado);
		});
		
		// Ruta para reenviar email
		$this->put('reenviar/{id}', function ($req, $res, $args) {
			$this->model->transaction->iniciaTransaccion();

			$info = $this->model->registro->get($args['id']);
			$info2 = $this->model->encuesta->getByRegistro($args['id'])->result;
			
			$qrCode = $args['id'].'U'.$info->result->codigo.'W'.$info2->id;
			

			// 			$optativa = $info->result->optativa;
			// 			if($optativa != "0" || $optativa != ""){
			// 				$textoOpt = "";
			// 				if(strpos($optativa, '1') >= 0){

			// 					$info3 = $this->model->optativa->get("1")->result;

			// 					$textoOpt .= '<br><strong>'.$info3->nombre.'.</strong>. <strong>Duración: '.$info3->duracion.' | Inicia: '+substr($info3->inicio,5)+' am</strong>';
			// 				}

			// 				if(strpos($optativa, '2') >= 0){

			// 					$info4 = $this->model->optativa->get("2")->result;

			// 					$textoOpt .= '<br><strong>'.$info4->nombre.'.</strong>. <strong>Duración: '.$info4->duracion.' | Inicia: '+substr($info4->inicio,5)+' am</strong>';
			// 				}
			// 			}
			//print_r($textoOpt); exit;
			

			$fileUrl = 'data/qr/'.$qrCode.'.png';
			// $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chld=H|1&chs=400x400&chl='.urlencode($qrCode);
			$qrUrl = 'https://quickchart.io/qr?text='.urlencode($qrCode);
			$QR = file_get_contents($qrUrl);
			$file = fopen($fileUrl, 'w');
			fwrite($file, $QR);
			fclose($file);

			$to = $info->result->email;
			$subject = 'Registro Universal Workshop';
			$body = '<center>';
			$body .= '<img id="logo" src="'.URL_ROOT.'/assets/images/Universal2024.png'.'" alt="log" class="img-responsive img-thumbnail" style="max-width: 300px;">';
			$body .= '<h2 class="alert-heading">¡Listo! Has quedado confirmado al evento de <strong>Universal U&U Bogotá 2026</strong></h2>';

			$body .= '<p>Este es tu código el cual deberás presentar el día del evento.</p>';
			$body .= '<h2 class="alert-heading"><strong>IMPORTANTE:</strong></h2>';
			$body .= '<p><strong>Presenta</strong> este código QR el día del evento. El cual será el acceso personal para ingresar.</p>';

			$body .= '<h1 class="display-5">'.$qrCode.'</h1>';
			$body .= '<img id="imgQR" src="'.URL_ROOT.'/data/qr/'.$qrCode.'.png'.'" alt="qr" class="img-responsive img-thumbnail" style="max-width: 300px;">';

			$body .= '<br><p>Te invitamos a que <span style="background-color: yellow;"><strong>descargues</strong></span> este PDF. El cual contiene tu acceso para el evento.</p>';
			$body .= '<a href="'.URL_ROOT.'/registro/imprimir/'.$qrCode.'/'.$idReg.'" class="btn btn-sm waves-effect waves-light" style="border: 2px solid #000000; background-color: yellow; font-weight: bold; color: #000000; text-decoration: none;" id="btnDescargar"><span class="fa fa-qrcode"></span> Descargar PDF</a>';

			$body .= '<p>Te esperamos el día <strong>Viernes 27 de febrero de 2026</strong> en el Recinto Cine Colombia Titán Plaza ubicada en Carrera 72 # 80-94 Centro Comercial Titán Plaza, Bogotá, Colombia.</p>';

			// $body .= '<p>Recuerda que eres un invitado especial por lo que tu entrenamiento continuará en una sala VIP</p>';
			// $body .= '<p><strong>Duración: 30 minutos | Inicio: 11:00 am</strong></p>';

			// $validCodes = ["PRESSCOL25", "PRESSMX25", "PRESSGDL25", "PRESSMTY25"];
			// $programaEspecial = false;

			// Verificar si la cadena contiene alguno de los códigos válidos
			// foreach ($validCodes as $code) {
			// 	if (strpos($codigo, $code) !== false) { 
			// 		$programaEspecial = true;
			// 		break;
			// 	}
			// }

			// Si encontró un código válido, muestra el programa con entrevistas
			// if ($programaEspecial) {
			// 	$body .= '<p>Programa <br><br>
			// 		8:00 am Registro <br>
			// 		8:00 am Desayuno tipo bufet <br>
			// 		9:00 am Presentación Universal Destinations & Experiences<br>
			// 		Entrevistas a medios<br>
			// 		Fin del evento
			// 	</p>';
			// } else { 
				// Si no encontró un código válido, muestra el programa estándar
				$body .= '<p>Programa <br><br>
					8:00 am Registro <br>
					8:00 am Desayuno estilo networking<br>
					9:00 am Presentación Universal Orlando Resort<br>
					Fin del evento
				</p>';
			// }


			// 			$body .= '<p>Programa <br><br>
			// 			8:00 am Registro <br>
			// 			8:00 am Desayuno tipo bufet <br>
			// 			9:00 am Presentación Universal Destinations & Experiences<br>
			// // 			10:00 am Sesiones con operadores <br>
			// // 			11:00 am Optativa Super Nintendo World<br>
			// // 			11:30 am Optativa Tips de Expertos<br>
			// 			Fin del evento
			// 		</p>';



			// if(count($opts) > 0){
			// 	$textoOpt = '';
			// 	foreach ($dataOptativa as $optInfo) {
			// 		if(in_array($optInfo['id'], $opts)){
			// 			$textoOpt .= '<br><strong>'.$optInfo['title'].'</strong>. <strong>'.$optInfo['info'].'</strong>';
			// 		}
			// 	}
			// 	if($textoOpt != ''){
			// 		$body .= '<p>La optativa en la que te registraste es ';
			// 		$body .= $textoOpt;
			// 		$body .= '</p>';
			// 	}
			// }

			$body .= '<p>Correo registrado <strong>'.$data['email'].'</strong></p>';
			$body .= '<hr>';
			// if ($programaEspecial) {
			// 	$body .= '<br><br>';								
			// 	$body .= '</center>';
			// }else{
				$body .= '<h4>¡Aún hay más!</h4>';
				$body .= '<p>Presenta tu certificado vigente* de entrenamientos de <strong>Universal Orlando Resort</strong> impreso y recibe una sorpresa el día del evento.<br>';
				$body .= '<p class="alert-heading" style="font-weight: bold;>
				No olvides seguirnos en nuestra red oficial para agentes de viajes en <br>Instagram
					<a href="https://www.instagram.com/universalpartnerslatino/?hl=en"
						target="_blank"
						style="text-decoration: underline;">
						@UniversalPartnersLatino
					</a>.
				</p>';
				$body .= 'Si aún no cuentas con ellos regístrate en: <br>';
				$body .= '<a href="https://www.universalpartnercommunity.com/s/login/SelfRegister?language=es" target="_blank">https://www.universalpartnercommunity.com/s/login/SelfRegister?language=es</a>';
				$body .= '<br><br>';
				$body .= '<small>*Para tener los certificados debes concluir los entrenamientos que hay en Universal Partner Community</small>';
				$body .= '</p>';
				$body .= '</center>';
			// }
			$sent = sendMailSMTP($to, $subject, $body, '', array($fileUrl));
			$info->sent = $sent;

			$info->result = null;
			$this->model->transaction->confirmaTransaccion();
			return $res->withJson($info);
		});
		
        // Ruta para modificar un registro
		$this->put('edit/{id}', function ($req, $res, $args) {
			return $res->withJson($this->model->registro->edit($req->getParsedBody(), $args['id']));
		});

        // Ruta para dar de baja un registro
		$this->put('del/{id}', function ($req, $res, $args) {
			$info = $this->model->registro->get($args['id']);
			if($info->response){
			    $codigo = $info->result->codigo;
			    // habilitar codigo borrado 
    			$data = array('usados' => new Literal('usados - 1'));
    			$edit = $this->model->codigo->editByCodigo($data, $codigo);
    			if($edit->response){
    			    $del = $this->model->registro->del($args['id']);
    			    if($edit->response){
    			        return $res->withJson($del);
    			    }else{
    			        return $res->withJson($existe->setResponse(false, 'No se pudo dar de baja el registro'));
    			    }
    			}else{
    			    return $res->withJson($existe->setResponse(false, 'No se pudo dar de baja el registro'));
    			}
			}else{
			    return $res->withJson($existe->setResponse(false, 'No se pudo dar de baja el registro'));
			}
		});

		// Ruta para buscar
		$this->get('findBy/{f}/{v}', function ($req, $res, $args) {
			return $res->withHeader('Content-type', 'application/json')
				->write(json_encode($this->model->registro->findBy($args['f'], $args['v'])));			
		});


		$this->get('checkCode/{code}', function($req, $res, $args){
			$existe = $this->model->codigo->findBy('codigo', $args['code']);
			if($existe->response){
				if(count($existe->result) > 0){
					$codigo = $existe->result[0];
					if($codigo->invitados > $codigo->usados){
						return $res->withJson($existe);
					}else{
						// return $res->withJson($existe->setResponse(false, 'Este código ya cuenta con su capacidad máxima de invitados'));
						// return $res->withJson($existe->setResponse(false, 'Este código ya ha sido redimido anteriormente'));
						return $res->withJson($existe->setResponse(false, 'Este código ya cuenta con su capacidad máxima de invitados'));
					}
				}else{
					return $res->withJson($existe->setResponse(false, 'Código Inválido'));
				}
			}else{
				return $res->withJson($existe->setResponse(false, 'Ocurrio un error'));
			}
		});

		$this->get('checkOpt/{opt}', function($req, $res, $args){
			$existe = $this->model->optativa->findBy('id', $args['opt']);
			if($existe->response){
				$optativa = $existe->result[0];
				if($optativa->disponibles > $optativa->usados){
					return $res->withJson($existe);
				}else{
					return $res->withJson($existe->setResponse(false, 'Esta optativa ha llegado a su límite de aforo'));
				}
			}else{
				return $res->withJson($existe->setResponse(false, 'Ocurrio un error'));
			}
		});

		$this->get('imprimir/{codigo}/{id}', function($req, $res, $args){
			$registro = $this->model->registro->get($args['id'])->result;
			$params['correo'] = $registro->email;
			// $optativa = $this->model->optativa->get($registro->optativa_id)->result;
			$arrOpts = array();
			$opts = explode(',', $registro->optativa);
			$optativas = $this->model->optativa->findBy('id', $opts)->result;
			foreach ($optativas as $optativa) {
				$inicio = substr($optativa->inicio,0,-3);
				$arrOpts[] = $optativa->nombre.'. Duración: '.$optativa->duracion.' | Inicia: '.$inicio.' am';
			}
			// $params['optativa'] = $optativa->nombre.'. Duración: '.$optativa->duracion.' | Inicia: '.$optativa->inicio; 
			$params['optativas'] = $arrOpts; 
			$params['codigo'] = $args['codigo'];

			return $this->view->render($res, 'pdf.phtml', $params);
		});

		// Obtener WhatsApp
		$this->put('registroWA/{id}', function($request, $response, $arguments) {
			
			$info = $this->model->registro->get($arguments['id']);
			$info2 = $this->model->encuesta->getByRegistro($arguments['id'])->result;
			
			$qrCode = $arguments['id'].'U'.$info->result->codigo.'W'.$info2->id;

			$params['header'] = 'https://universal.clase.digital/assets/images/Universal2024.png';
        	$params['telefono'] = '7711617545';
			$params['body'] = '
*'.$info->result->nombre.'*, has quedado *confirmado* al evento de *Universal Workshop Zapopan, Jal.* 
		
*Programa*

*8:00 am* Registro
*8:00 am* Desayuno tipo bufet
*9:00 am* Presentación Universal Destinations & Experiences
*10:00 am* Sesiones con operadores
*11:00 am* Optativa Super Nintendo World
*11:30 am* Optativa Tips de Expertos
Fin del evento

Te esperamos el día *Jueves 18 de abril* en *Cinemex Plaza Patria*, ubicado en Av. Patria 1950 colonia Jacarandas, Avenida Americas y Avila Camacho, Plaza Patria, 45160 Zapopan, Jal. a las *08:00 am*.

*¡Aún hay más!*
Presenta tu certificado vigente de entrenamientos de Universal Destinations & Experiences impreso y recibe una sorpresa el día del evento.

Para tener los certificados debes concluir los entrenamientos que hay en Universal Partner Community
		
';
			$params['codigo'] = ''.$qrCode;
			$params['id'] = ''.$arguments['id'];
			$this->view->render($response, 'registroWA.php', $params);
			return 'ok';
		});

	});//->add( new MiddlewareToken() );

	function sendMailSMTP($to, $subject, $body, $cc, $files=[]){
		if (!isset($_SESSION)) session_start();
		$disc = "<br><br><br><small>======================================================<br>";
		$disc .="Este correo fue enviado desde una cuenta no monitoreada. Por favor no responda este correo.</small>";
		$body = $body.$disc;

		$mail = new PHPMailer;

		// $mail->SMTPDebug = 2;
		$mail->isSMTP();
		$mail->SMTPOptions = array(
			'ssl'=> array(
				'verify_peer' => false,
				'verify_peer_name'=> false,
				'allow_self_signed' => true
			)
		);
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = 'tls';
		$mail->Host = 'smtp.gmail.com';
		$mail->Username = $_SESSION['mail_username'];
		$mail->Password = $_SESSION['mail_pwd'];
		$mail->Port = 587;
		$mail->Mailer = 'mail';

		$mail->setFrom($_SESSION['mail_username'], 'Universal Workshop');
		$mail->setFrom('notifica@clase.digital', 'Universal Workshop');

		$mail->addAddress($to);
		if($cc != '') $mail->AddCC($cc);

		$mail->isHTML(true);
		$mail->CharSet = 'UTF-8';

		$mail->Subject = $subject;
		$mail->Body = $body;

		for($x=0;$x<count($files);$x++){
			$filename = explode('/', $files[$x]);
			$filename = $filename[count($filename)-1];

			$mail->AddAttachment($files[$x],$filename);
		}


		if(!$mail->send()){
			return "Mailer Error: " . $mail->ErrorInfo;
			//return "FALSE";
		}else{
			//return "Message has been sent successfully";
			return "TRUE";
		}
	}
?>