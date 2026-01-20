<?php
	namespace App\Lib;

	use Jose\Component\Core\AlgorithmManager;
	use Jose\Component\Core\JWK;
	use Jose\Component\Checker;
	use Jose\Component\Checker\AlgorithmChecker;
	use Jose\Component\Checker\ClaimCheckerManager;
	use Jose\Component\Checker\HeaderCheckerManager;
	use Jose\Component\Checker\HeaderCheckerManagerFactory;
	use Jose\Component\Checker\InvalidClaimException;
	use Jose\Component\Checker\MissingMandatoryClaimException;
	use Jose\Component\Encryption\JWEBuilder;
	use Jose\Component\Encryption\JWEDecrypter;
	use Jose\Component\Encryption\JWELoader;
	use Jose\Component\Encryption\JWETokenSupport;
	use Jose\Component\Encryption\Algorithm\ContentEncryption\A256CBCHS512;
	use Jose\Component\Encryption\Algorithm\KeyEncryption\A256KW;
	use Jose\Component\Encryption\Compression\CompressionMethodManager;
	use Jose\Component\Encryption\Compression\Deflate;
	use Jose\Component\Encryption\Serializer\CompactSerializer;
	use Jose\Component\Encryption\Serializer\JWESerializerManager;
	
	require_once './core/defines.php';

	class JWT {
		private $algCifradoClaves;
		private $algCifradoDatos;
		private $metodoCompresion;
		private $constructorJWE;
		private $JWK;

		public function __construct() {
			$this->algCifradoClaves = new AlgorithmManager([new A256KW()]);
			$this->algCifradoDatos = new AlgorithmManager([new A256CBCHS512()]);
			$this->metodoCompresion = new CompressionMethodManager([new Deflate()]);
			$this->constructorJWE = new JWEBuilder($this->algCifradoClaves, $this->algCifradoDatos, $this->metodoCompresion);
			$this->JWK = new JWK(['kty' => json_decode($_SESSION['clave'])->kty, 'k' => json_decode($_SESSION['clave'])->k]);
		}

		public function crearToken($datos) {
			$JWE = $this->constructorJWE
				->create() // We want to create a new JWE
				->withPayload($datos) // We set the payload
				->withSharedProtectedHeader([
					'alg' => 'A256KW', // Key Encryption Algorithm
					'enc' => 'A256CBC-HS512', // Content Encryption Algorithm
					'zip' => 'DEF' // We enable the compression (irrelevant as the payload is small, just for the example).
				])
				->addRecipient($this->JWK) // We add a recipient (a shared key or public key).
				->build(); // We build it

			$serializador = new CompactSerializer(); // The serializer
			return $serializador->serialize($JWE, 0); // We serialize the recipient at index 0 (we only have one recipient).
		}

		public function verificadorEncabezado() {
			return new HeaderCheckerManager([
				new AlgorithmChecker(['A256KW']), // We check the header 'alg'
			], [
				new JWETokenSupport(), // Adds JSE token type support
			]);
		}

		public function verificadorDatos($JWE) {
			try {
				$resultado = false;
				$verificadorDatos = new ClaimCheckerManager([
					new Checker\NotBeforeChecker(),
					// new Checker\ExpirationTimeChecker(),
					new Checker\AudienceChecker(SITE_NAME),
				]);

				if(!isset($_SESSION)) { session_start(); }
				$claims = json_decode($JWE->getPayload(), true);
				// if($verificadorDatos->check($claims, ['nbf', 'exp', 'aud', 'id_usuario'])) {
				if($verificadorDatos->check($claims, ['nbf', 'aud', 'id'])) {
					if($claims['id'] == $_SESSION['usuario']->id) {
						$settings = require __DIR__ . '/../../src/settings.php';
						$app = new \Slim\App($settings);
						require __DIR__ . '/../../src/dependencies.php';
						$token = $container->model->seg_sesion->getByToken($_SESSION['token'])->result;
						if($token->estado == 1) {
							$resultado = true;
						}
					}
				}

				return $resultado;

			} catch(InvalidClaimException | MissingMandatoryClaimException $ex) {
				return false;
			}
		}

		public function descifrar($token) {
			$resultado = false;

			// The serializer manager. We only use the JWE Compact Serialization Mode.
			$serializador = new JWESerializerManager([new CompactSerializer()]);
			$desencriptador = new JWEDecrypter($this->algCifradoClaves, $this->algCifradoDatos, $this->metodoCompresion);
			$verificadorEncabezado = $this->verificadorEncabezado();

			$cargadorJWE = new JWELoader($serializador, $desencriptador, $verificadorEncabezado);
			
			$recipient = 0;
			$JWE = $cargadorJWE->loadAndDecryptWithKey($token, $this->JWK, $recipient);

			if($this->verificadorDatos($JWE)) {
				$resultado = $desencriptador->decryptUsingKey($JWE, $this->JWK, 0);
			}
			return $resultado;
		}
	}
?>