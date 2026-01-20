<?php
	namespace App\Lib;

	use PDOException;

	class Transaction {
		private $db;
		
		public function __CONSTRUCT($db) {
			$this->db = $db;
		}
	
		/*** Método Inicia Transacción ***/
		public function iniciaTransaccion() {
			try{
				$re = $this->db->getPdo()->beginTransaction();
				//$re = new \Envms\FluentPDO\Literal('beginTransaction()');  
			}catch(PDOException $e) {
				return $e;
			}

			return $re;
		}// fin Inicia Transacción 


	/*** 
		**** Método confirma Transacción 
		***/
		public function confirmaTransaccion() {
		try{  
				$re = $this->db->getPdo()->commit();
			}catch (PDOException $e) {
				return $e;
			}

			return $re;

												
		}// fin confirma Transacción 



		/*** 
		**** Método regresa Transacción 
		***/
		public function regresaTransaccion() {
			try{
				$re = $this->db->getPdo()->rollBack();
			}catch (PDOException $e) {
				return $e;
			}
			
			return $re;

												
		}// fin regresa Transacción 


	
	}//fin class TransactionModel
?>