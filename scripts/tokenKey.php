<?php
	define('IS_SCRIPT',1);
	define('ROOT_PATH', dirname(__FILE__,2));
	require_once __DIR__ . '/../vendor/autoload.php';

	use RC\FileOperator;
	$types = ['RS256','RS384','RS512','ES256','ES384','ES512','EDDSA'];

	if(!isset($argv[1]) || !in_array(strtoupper($argv[1]),$types)){
		$usage = "Usage: php yourfile <command> [mode]\nCommands: \nRS256\t\tGenerate RS256 private and public keys.\nRS384\t\tGenerate RS384 private and public keys.\nRS512\t\tGenerate RS512 private and public keys.\nES256\t\tGenerate ES256 private and public keys.\nES384\t\tGenerate ES384 private and public keys.\nEDDSA\t\tGenerate ed25519-1 private and public keys\n";
		exit($usage);
	}
	$configRS = array(
	    "digest_alg" => "sha256",
	    "private_key_bits" => 2048,
	    "private_key_type" => OPENSSL_KEYTYPE_RSA,
	    'config' => $argv[2] ?? null
	);
	$rand = rand(100000,999999);
	$signer = strtoupper($argv[1]);
	switch($signer){
		case 'RS256': case 'RS384': case 'RS512';
			if($signer=='RS384'){
				$configRS['digest_alg'] = 'sha384';
			}
			if($signer=='RS512'){
				$configRS['digest_alg'] = 'sha512';
			}
			$res = openssl_pkey_new($configRS);
    		openssl_pkey_export($res, $private_key, null, $configRS);
   			$details = openssl_pkey_get_details($res);
    		$public_key = $details['key'];
    		$privatePath = ROOT_PATH.'/ssl/'.$signer.'_'.$rand.'.key';
    		$publicPath = ROOT_PATH.'/ssl/'.$signer.'_'.$rand.'.pub';
    		FileOperator::write($privatePath,$private_key,true);
    		FileOperator::write($publicPath,$public_key,true);
    		exit("Generate ".$signer." private and public keys success.\nprivateKey\t\t$privatePath\npublicKey\t\t$publicPath\n");
		break;
		case 'ES256': case 'ES384': case 'ES512':
			$configES = $configRS;
			$configES['private_key_type'] = OPENSSL_KEYTYPE_EC;
			$configES['curve_name'] = 'prime256v1';
			if($signer=='RS384'){
				$configES['digest_alg'] = 'sha384';
			}
			if($signer=='RS512'){
				$configES['digest_alg'] = 'sha512';
			}
			$res = openssl_pkey_new($configES);
    		openssl_pkey_export($res, $private_key, null, $configES);
   			$details = openssl_pkey_get_details($res);
    		$public_key = $details['key'];
    		$privatePath = ROOT_PATH.'/ssl/'.$signer.'_'.$rand.'.key';
    		$publicPath = ROOT_PATH.'/ssl/'.$signer.'_'.$rand.'.pub';
    		FileOperator::write($privatePath,$private_key,true);
    		FileOperator::write($publicPath,$public_key,true);
    		exit("Generate ".$signer." private and public keys success.\nprivateKey\t\t$privatePath\npublicKey\t\t$publicPath\n");	
		break;
		case 'EDDSA':
			$keyPair = sodium_crypto_sign_keypair();
			$private_key = base64_encode(sodium_crypto_sign_secretkey($keyPair));
            $public_key = base64_encode(sodium_crypto_sign_publickey($keyPair));
            $privatePath = ROOT_PATH.'/ssl/'.$signer.'_'.$rand.'.key';
    		$publicPath = ROOT_PATH.'/ssl/'.$signer.'_'.$rand.'.pub';
    		FileOperator::write($privatePath,$private_key,true);
    		FileOperator::write($publicPath,$public_key,true);
    		exit("Generate ".$signer." private and public keys success.\nprivateKey\t\t$privatePath\npublicKey\t\t$publicPath\n");
		break;
	}
?>