<?php
	namespace App\Model;

	use PDOException;
	use App\Lib\Response;

	class OptativaModel {
		private $db;
		private $table = 'optativa';
		private $response;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
			$this->response = new Response();
		}

		// Obtener los datos de optativa por medio del ID
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

		// Obtener los datos de los optativa
		public function getAll() {
			$this->response->result = $this->db
				->from($this->table)
				->where('estado', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}

		// Agregar un optativa
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

		// Modificar un optativa
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

		// Dar de baja un optativa
		public function del($id) {
			try{
				$data['estado'] = 0;
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
				// ->where('estado', 1)
				->fetchAll();
			return $this->response->SetResponse(true);
		}
	}
?>