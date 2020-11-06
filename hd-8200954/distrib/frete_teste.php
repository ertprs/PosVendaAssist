<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

function calcula_frete($cep_origem,$cep_destino,$peso,$codigo_servico = null ){
	$url = "www.correios.com.br";
	$ip = gethostbyname($url);
	$fp = fsockopen($ip, 80, $errno, $errstr, 10);

	if ($codigo_servico == null){
		$cod_servico     = "40010"; #Código SEDEX
	}else{
		$cod_servico = $codigo_servico;
	}

	if (strlen($cep_origem)>0 AND strlen($cep_destino)>0 AND strlen($peso)>0){
		$correios = "http://www.correios.com.br/encomendas/precos/calculo.cfm?servico=".$cod_servico."&cepOrigem=".$cep_origem."&cepDestino=".$cep_destino."&peso=".$peso."&MaoPropria=N&avisoRecebimento=N&resposta=xml";

		echo $correios.'<BR><BR>';

		$correios_info = file($correios);
		print_r ($correios_info);
		foreach($correios_info as $info){
			$bsc = "/\<preco_postal>(.*)\<\/preco_postal>/";
			if(preg_match($bsc,$info,$tarifa)){
				$precofrete = $tarifa[1];
			}
		}
		return $precofrete;
	}else{
		return null;
	}
}

$valor_frete = calcula_frete('17519255','11013160','3');
echo $valor_frete;
?>

<html>
<head>
<title>Faturamento de Embarques</title>
</head>

<body>

<? include 'menu.php' ?>

<center><h1>Faturamento de Embarques</h1></center>

<p>


<p>

<? #include "rodape.php"; ?>

</body>
</html>
