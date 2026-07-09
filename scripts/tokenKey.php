<?php
	define('IS_SCRIPT',1);
	define('ROOT_PATH', dirname(__FILE__,2));
	require_once __DIR__ . '/../vendor/autoload.php';

	use RC\FileOperator;
	class TokenKeyFileOperator {
		use FileOperator;
	}
	$types = ['RS256','RS384','RS512','ES256','ES384','EDDSA'];
	$sslDir = ROOT_PATH . '/ssl';

	$usage = "Usage: php ./scripts/tokenKey.php <algorithm> [opensslConfig]\nCommands: \nRS256\t\tGenerate RS256 private and public keys.\nRS384\t\tGenerate RS384 private and public keys.\nRS512\t\tGenerate RS512 private and public keys.\nES256\t\tGenerate ES256 private and public keys.\nES384\t\tGenerate ES384 private and public keys.\nEDDSA\t\tGenerate EdDSA(Ed25519) private and public keys.\n";

	$fail = function($message){
		fwrite(STDERR, $message . "\n");
		exit(1);
	};

	$opensslErrors = function(){
		$errors = [];
		while($error = openssl_error_string()){
			$errors[] = $error;
		}
		return $errors ? implode("\n", $errors) : 'unknown openssl error';
	};

	$writeKeyPair = function($signer, $privateKey, $publicKey) use ($sslDir, $fail){
		if(!is_dir($sslDir) && !TokenKeyFileOperator::mkdir($sslDir, 0755, true)){
			$fail('Create ssl directory failed: ' . $sslDir);
		}

		try {
			$rand = random_int(100000, 999999);
		} catch (\Throwable $e) {
			$rand = mt_rand(100000, 999999);
		}

		$privatePath = $sslDir . '/' . $signer . '_' . $rand . '.key';
		$publicPath = $sslDir . '/' . $signer . '_' . $rand . '.pub';
		if(TokenKeyFileOperator::write($privatePath, $privateKey, true) === false){
			$fail('Write private key failed: ' . $privatePath);
		}
		if(TokenKeyFileOperator::write($publicPath, $publicKey, true) === false){
			@unlink($privatePath);
			$fail('Write public key failed: ' . $publicPath);
		}
		@chmod($privatePath, 0600);
		@chmod($publicPath, 0644);

		exit("Generate " . $signer . " private and public keys success.\nprivateKey\t\t" . $privatePath . "\npublicKey\t\t" . $publicPath . "\n");
	};

	if(!isset($argv[1]) || in_array($argv[1], ['help', '--help', '-h'], true)){
		exit($usage);
	}

	$signer = strtoupper($argv[1]);
	if(!in_array($signer, $types, true)){
		$fail("Unsupported algorithm: " . $argv[1] . "\n" . $usage);
	}

	$configFile = $argv[2] ?? null;
	if($configFile !== null && (!is_file($configFile) || !is_readable($configFile))){
		$fail('OpenSSL config file is not readable: ' . $configFile);
	}

	switch($signer){
		case 'RS256':
		case 'RS384':
		case 'RS512':
		case 'ES256':
		case 'ES384':
			if(!extension_loaded('openssl')){
				$fail('OpenSSL extension is not loaded');
			}

			$digestMap = [
				'RS256' => 'sha256',
				'RS384' => 'sha384',
				'RS512' => 'sha512',
				'ES256' => 'sha256',
				'ES384' => 'sha384',
			];
			$config = [
				'digest_alg' => $digestMap[$signer],
				'config' => $configFile,
			];
			if($configFile === null){
				unset($config['config']);
			}
			if(strpos($signer, 'RS') === 0){
				$config['private_key_type'] = OPENSSL_KEYTYPE_RSA;
				$config['private_key_bits'] = [
					'RS256' => 2048,
					'RS384' => 3072,
					'RS512' => 4096,
				][$signer];
			}else{
				$config['private_key_type'] = OPENSSL_KEYTYPE_EC;
				$config['curve_name'] = [
					'ES256' => 'prime256v1',
					'ES384' => 'secp384r1',
				][$signer];
			}

			$res = openssl_pkey_new($config);
			if($res === false){
				$fail('Generate key failed: ' . $opensslErrors());
			}
			if(!openssl_pkey_export($res, $privateKey, null, $config)){
				$fail('Export private key failed: ' . $opensslErrors());
			}
			$details = openssl_pkey_get_details($res);
			if($details === false || empty($details['key'])){
				$fail('Export public key failed: ' . $opensslErrors());
			}
			$writeKeyPair($signer, $privateKey, $details['key']);
		case 'EDDSA':
			if(!function_exists('sodium_crypto_sign_keypair')){
				$fail('Sodium extension is not loaded');
			}
			$keyPair = sodium_crypto_sign_keypair();
			$privateKey = base64_encode(sodium_crypto_sign_secretkey($keyPair));
			$publicKey = base64_encode(sodium_crypto_sign_publickey($keyPair));
			$writeKeyPair($signer, $privateKey, $publicKey);
	}