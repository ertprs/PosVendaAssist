<?php
if (!is_object($e)) {
    $e = json_decode(mb_convert_encoding('"code":503,"msg":"Erro não reconhecido"', 'utf8', 'HTML-ENTITIES,Latin1'));
}


header("HTTP/1.1 {$e->getCode()} {$e->getMessage()}");
if ($_serverEnvironment == 'development' and strlen($erro_banco = pg_last_error($con))) {
    $errorMsg = "<h2>PostgreSQL informa:</h2></main><main><div class='desc'><h4>$erro_banco</h4><hr /><pre style='max-height:60%;overflow-y:auto'>$sql</pre></div></main><main>";
}
$retorno = $e->url ? : $_SERVER['PHP_SELF'];

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="iso-8859-1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1,  user-scalable=no">
	<title>PosVenda &ndash; Erro detectado</title>
	<link rel="stylesheet" type="text/css" href="https://www.telecontrol.com.br/404/style.css">
	<link href='http://fonts.googleapis.com/css?family=Roboto:500,100,300,400' rel='stylesheet' type='text/css'>
	<link rel="shortcut icon" href="https://www.telecontrol.com.br/images/favicon.png" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
</head>
<body>
	<header>
		<h4><?=$e->getCode()?></h4>
		<span><?=mb_convert_encoding($e->getMessage(), 'HTML-ENTITIES', 'utf8,latin1')?></span>
	</header>
	<main>
		<div class="phrase"><?=$errorMsg?></div>
		<div class="desc"><a class="btn" href="<?=$_SERVER['PHP_SELF']?>">Voltar à tela</a></div>
	</main>
	<footer>
		<div class="logo">
			<a href="https://www.telecontrol.com.br/"><img src="https://www.telecontrol.com.br/images/logo.png" alt="Telecontrol"></a>
		</div>
		<span>Deus é o provedor.</span>
	</footer>
</body>
</html>
