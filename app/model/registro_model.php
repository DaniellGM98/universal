<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class RegistroModel {
		private $db;
		private $table = 'registro';
		private $tblCod = 'codigo';
		private $tblOpt = 'optativa';
		private $tblEnc = 'encuesta';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener los datos de registro por medio del ID
		public function get($id) {
			$this->response->result = $this->db
				->from($this->table)
				->where('id', $id)
				->fetch();
			if($this->response->result) {
				$this->response->SetResponse(true);
			} else {
				$this->response->SetResponse(false, 'No existe el registro');
			}
			return $this->response;
		}

		// Obtener los datos de los registro
		public function getAll() {
			$this->response->result = $this->db
				->from($this->table)
				// ->select('optativa.nombre AS optativa')
				->where('registro.status', 1)
				->orderBy('fecha DESC')
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		// Obtener total de los registros
		public function totalRegistros() {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where('status', 1)
				->fetch();
			return $this->response->SetResponse(true);
		}

		// Obtener total checks de los registros
		public function totalChecks() {
			$this->response->result = $this->db
				->from($this->table)
				->select(null)->select('COUNT(*) Total')
				->where('status', 1)
				->where('!checkin', 'null')
				->fetch();
			return $this->response->SetResponse(true);
		}

		// Agregar un registro
		public function add($data) {
			try {
				$this->response->result = $this->db
					->insertInto($this->table, $data)
					->execute();
				if($this->response->result != 0)	$this->response->SetResponse(true, 'id del registro: '.$this->response->result);
				else { $this->response->SetResponse(false, 'No se inserto el registro'); }
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Add model $this->table");
			}
			return $this->response;
		}

		// Modificar un registro
		public function edit($data, $id) {
			try{
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { $this->response->SetResponse(true, "id actualizado: $id"); }
				else { $this->response->SetResponse(false, 'No se edito el registro'); }
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: Edit model $this->table");
			}
			return $this->response;
		}

		// Dar de baja un registro
		public function del($id) {
			try{
				$data['status'] = 0;
				$this->response->result = $this->db
					->update($this->table, $data)
					->where('id', $id)
					->execute();
				if($this->response->result!=0) { $this->response->SetResponse(true, "id baja: $id"); }    
				else { $this->response->SetResponse(false, 'no se dio de baja el registro'); }
			} catch(\PDOException $ex) {
				$this->response->errors = $ex;
				$this->response->SetResponse(false, "catch: del model $this->table");
			}
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