<?php
/**
 * Página para visualizar determinada foto de um produto.
 * Deve receber o ID da peca e o sufixo da foto a ser exibida.
 *
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
$admin_privilegios = "cadastros";
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
$servidor = $_SERVER[HTTP_HOST];
if($servidor == 'www.telecontrol.com.br' || $servidor == 'ww2.telecontrol.com.br' || $servidor == 'posvenda.telecontrol.com.br') {
	#include '/var/www/telecontrol/www/loja/bootstrap.php';
}else{
	include '../../LojaVirtual/bootstrap.php';
}

uses('PecaFoto'); // Inclui classe da Loja Virtual

$peca   = ( isset($_GET['peca']) ) ? $_GET['peca'] : 0 ;
$sufixo = ( isset($_GET['sufixo']) ) ? $_GET['sufixo'] : 0 ;
if ( empty($peca) || empty($sufixo) ) {
	die('Parâmetros inválidos');
}
$oFoto = PecaFoto::find($peca);

if($servidor == 'www.telecontrol.com.br' || $servidor == 'ww2.telecontrol.com.br' || $servidor == 'posvenda.telecontrol.com.br') {
	$path  = $oFoto->getFoto($sufixo);
	$url   = PecaFoto::corrigirCaminhoParaUrl($path);
}else{
	$path  = $oFoto->getFoto($sufixo);
	$exp = explode('/', $path);
	$imagem = $exp[count($exp)-1];
	$url = "http://192.168.0.199/~anderson/LojaVirtual/media/produtos/$imagem";	
}


if ( empty($path) || empty($url) ) {
	die('Foto não encontrada');
}
if ( isset($_GET['remover']) && $_GET['remover'] == 'true' ) {
	unlink($path);
	die('Imagem removida !');
}
?>
<html>
	<head>
		<title>Imagem da Loja Virtual</title>
	</head>
	<body>
		<div align="center">
			<img src="<?php echo $url; ?>" />
		</div>
		<div align="center">
			<a href="peca_cadastro_foto_lv.php?peca=<?php echo $peca; ?>&sufixo=<?php echo $sufixo; ?>&remover=true">Remover</a>
		</div>
	</body>
</html>
