<?php
	namespace App\Model;
	use PDOException;
	use App\Lib\Response;
	use App\Lib\JWT;
    use Envms\FluentPDO\Literal;

class SegSesionModel {
		private $db;
		private $table = 'seg_sesion';
		private $response;
		
		public function __CONSTRUCT($db) {
			require_once './core/defines.php';
			$this->db = $db;
			$this->response = new Response();
		}

		public function crearToken($usuario) {
			$JWT = new JWT();
			$datos = [
				'nbf' => time(),
				'aud' => SITE_NAME,
				'id' => $usuario->id,
			];
			return $JWT->crearToken(json_encode($datos));
		}

		public function add($data) {
			try {
				$sesion = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($sesion != 0) {
					$getSesion = $this->get($sesion)->result;
					$_SESSION['token'] = $getSesion->token;
					$_SESSION['id_sesion'] = $getSesion->id;
					$this->response->SetResponse(true, 'Id del registro: '.$sesion);
					$this->response->result = $sesion;
				} else { $this->response->SetResponse(false, 'No se inserto el registro'); }
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, 'catch: Add model seg_sesion');
			}
			return $this->response;
		}

		public function get($id) {
			$this->response->result = $this->db
				->from($this->table)
				->where('id', $id)
				->fetch();
			if($this->response->result)	{ return $this->response->SetResponse(true); }
			else { return $this->response->SetResponse(false, 'No existe el registro'); }
		}

		public function getByToken($token) {
			$this->response->result = $this->db
				->from($this->table)
				->where('token', $token)
				->fetch();
			if($this->response->result)	{ return $this->response->SetResponse(true); }
			else { return $this->response->SetResponse(false, 'No existe el registro'); }
		}

		public function edit($data, $id) {
			try {
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) {
					$sesion = $this->get($id)->result;
					$_SESSION['token'] = $sesion->token;
					$_SESSION['id_sesion'] = $sesion->id;
					$this->response->SetResponse(true, "Id actualizado: $id");
				} else { $this->response->SetResponse(false, 'No se edito el registro'); }
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, 'catch: Edit model seg_sesion');
			}
			return $this->response;
		}

		public function logout() {
			if(isset($_SESSION['id_sesion'])) {
				$data = [
					'status' => 0,
					'fin' => date('Y-m-d H:i:s'),
				];
				$this->response = $this->edit($data, $_SESSION['id_sesion']);
			}
			unset($_SESSION['sucursal']);
			unset($_SESSION['usuario']);
			unset($_SESSION['token']);
			unset($_SESSION['id_sesion']);
			session_unset(); 
			session_regenerate_id(true);
			session_destroy();
			return $this->response;
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
	}
?>