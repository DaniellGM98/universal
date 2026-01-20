<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use Slim\Http\UploadedFile;
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	require '../vendor/autoload.php';

	class UsuarioModel {
		private $db;
		private $table = 'seg_usuario';
		private $tableP = 'seg_permiso';
		private $tableA = 'seg_accion';
		private $tableM = 'seg_modulo';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
			require_once './core/defines.php';
		}

		// Agregar usuario
		public function add($data){
			$data['password'] = strrev(md5(sha1($data['password'])));
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try{
				$resultado = $this->db
									->insertInto($this->table, $data)
									->execute();
			}catch(\PDOException $ex){
				$this->response->result = $resultado;
				$this->response->errors = $ex;
				return $this->response->SetResponse(false, 'catch: Add model usuario');	
			}
			if($resultado!=0){
				$this->response->result = $resultado;
				return $this->response->SetResponse(true, 'Id del registro: '.$resultado);    
			}else{
				$this->response->result = $resultado;
				return $this->response->SetResponse(false, 'No se inserto el registro');
			}	
		}

        // Obtener todos los usuarios
		public function getAll($pagina, $limite, $usuario_tipo_id, $busqueda) {
			$inicial = $pagina * $limite;
			$busqueda = $busqueda==null ? "_" : $busqueda;
			$usuario = $this->db
				->from($this->table)
                ->select(null)->select("id, nombre, apellidos, email, username, CONCAT_WS(' ',
				nombre, apellidos) as nomcom, status, telefono, ultimo_acceso")
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("status", 1)
				->limit("$inicial, $limite")
				->orderBy('apellidos ASC, nombre ASC')
				->fetchAll();
			if($usuario) {
                unset($usuario->password);
            }
			$this->response->result = $usuario;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("status", 1)
				->fetch()
				->Total;
			return $this->response->SetResponse(true);
		}

		// Editar usuario
		public function edit($data, $id) {
			if(isset($data['password'])) $data['password'] = strrev(md5(sha1($data['password'])));
			$data['fecha_modificacion'] = date('Y-m-d H:i:s');
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					$this->response->SetResponse(true, 'Id actualizado: '.$id); 
				} else { 
					$this->response->SetResponse(false, 'No se edito el registro'); 
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model $this->table");
			}
			return $this->response;
		}

		// Eliminar usuario
		public function del($id){
			$set = array('status' => 0,'fecha_modificacion' => date("Y-m-d H:i:s"));
			$this->response->result = $this->db
				->update($this->table)
				->set($set)
				->where('id', $id)
				->execute();
			if($id!=0){
				return $this->response->SetResponse(true, "Id baja: $id");
			}else{
				return $this->response->SetResponse(true, "Id incorrecto");
			}
		}

		// Obtener usuario por id
		public function get($id) {
			$usuario = $this->db
				->from($this->table)
				//->select(null)->select("id, nombre, apellidos, email, tipo, username")
				->where('id', $id)
				->fetch();
			if($usuario) {
				unset($usuario->contrasena);
				$this->response->result = $usuario;
				$this->response->SetResponse(true);
			}
			else	$this->response->SetResponse(false, 'No existe el registro');
			return $this->response;
		}

		// Buscar usuario
		public function find($busqueda, $usuario_tipo=0) {
			$usuarios = $this->db
				->from($this->table)
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("status", 1)
				->fetchAll();
			foreach($usuarios as $usuario) { unset($usuario->contrasena); }
			$this->response->result = $usuarios;
			$this->response->total = $this->db
				->from($this->table)
				->select(null)->select("COUNT(*) AS total")
				->where("CONCAT_WS(' ', nombre, apellidos, email, username) LIKE '%$busqueda%'")
				->where("status", 1)
				->fetch()
				->total;
			return $this->response->SetResponse(true);
		}

		// inicio de sesión
		public function login($username, $password) {
			$password = strrev(md5(sha1($password)));
			$usuario = $this->db
				->from($this->table)
				->where('email', $username)
				->where('password', $password)
				->where('status', 1)
				->fetch();
			if(is_object($usuario)) {
				unset($usuario->password);
				$this->ultimoAcceso($usuario->id);
				$newModulos = array();
				$newModulos = $this->getPermisos($usuario->id);
				$this->addSessionLogin($usuario, $newModulos);
				$this->response->SetResponse(true, 'Acceso correcto');
			} else {
				$this->response->SetResponse(false, 'Verifica tus datos');
			}
			$this->response->result = $usuario;
			return $this->response;
		}

		// Modificar ultimo acceso
		public function ultimoAcceso($id) {
			date_default_timezone_set('America/Mexico_City');
			$data['ultimo_acceso'] = date("Y-m-d H:i:s");
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { 
					$this->response->SetResponse(true, 'Id actualizado: '.$id);
				} else { 
					$this->response->SetResponse(false, 'No se edito el registro');
				}
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model $this->table");
			}
			return $this->response;
		}

		// Obtener permisos
		public function getPermisos($usuario){
			$newModulos = array();
			$modulos = $this->getModulos();
			foreach ($modulos as $modulo) {
				$acciones = $this->getAcciones($usuario, $modulo->id);
				$contador = count($acciones);
				$accionesUrl = 0;
				if($contador>0){
					$modulo->acciones = $acciones;
					foreach ($acciones as $accion)
						if($accion->url != '') $accionesUrl++;
					$newModulos[] = $modulo;  
				}	
				$modulo->accionesUrl = $accionesUrl;
			}
			return $newModulos;
		}

		// Obtener modulos de permisos
		public function getModulos(){
			return $this->db
				->from($this->tableM)
				->where('status', 1)
				->orderBy('orden')
				->fetchAll();
		}

		// Obtener acciones de permisos
		public function getAcciones($usuario_id, $seg_modulo_id){
			return $this->db
				->from($this->tableP)
				->select(null)->select("DISTINCT $this->tableA.id, $this->tableA.nombre, $this->tableA.url, $this->tableM.id_html, $this->tableA.icono")
				->innerJoin("$this->tableA on $this->tableA.id = $this->tableP.seg_accion_id")
				->innerJoin("$this->tableM on $this->tableM.id = $this->tableA.seg_modulo_id")
				->where("$this->tableP.usuario_id", $usuario_id)
				->where(intval($seg_modulo_id)>0? "$this->tableA.seg_modulo_id = $seg_modulo_id": "TRUE")
				->where("$this->tableA.status", 1)
				->fetchAll();
		}

		// Agregar session
		public function addSessionLogin($usuario, $permisos){
			$browser = $_SERVER['HTTP_USER_AGENT'];
			$ipAddr = $_SERVER['REMOTE_ADDR'];
			if (!isset($_SESSION)) { session_start(); }
			$_SESSION['ip']  = $ipAddr;
			$_SESSION['navegador']  = $browser;
			$_SESSION['usuario']  = $usuario;
			$_SESSION['permisos']  = $permisos;
		}

		// find by field = value
		public function findBy($field, $value){
			$this->response->result = $this->db
				->from($this->table)
				->where($field, $value)
				->where('status', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		public function changePassword($data, $id) {
			$old_password = strrev(md5(sha1($data['old_password'])));
			$password['password'] = strrev(md5(sha1($data['new_password'])));
			$this->response->result = $this->db
				->update($this->table, $password)
				->where('id', $id)
				->where('password', $old_password)
				->execute();

			if($this->response->result == '1') { $this->response->SetResponse(true, 'contraseña actualizada'); }
			else { $this->response->SetResponse(false, 'Verifica la contraseña actual'); }

			return $this->response;
		}

		function moveUploadedFile($directory, UploadedFile $uploadedFile, $filename) {
			$uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

			return $filename;
		}

	}
?>