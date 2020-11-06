<!DOCTYPE html />
<?php

	include '../admin/dbconfig.php';
	include '../admin/includes/dbconnect-inc.php';
	include '../admin/funcoes.php';

	### CONFIGURA AMBIENTE DEVEL, PRODUÇÃO ###
	$ambiente = '1'; // 1 = producao, 2 = devel
	
	if ($ambiente === '1') {
		$host = "http://posvenda.telecontrol.com.br/assist/";
	} else if ($ambiente === '2') {
		$host = "http://novodevel.telecontrol.com.br/~bicalleto/PosVenda/";
	}
?>

<style type="text/css">
	body{
		font: 15px arial;
	}

	.boxcript{
		border: 1px solid #999;
		background-color: #f5f5f5;
		padding: 20px;
	}
</style>

<?php

	$cod_fabrica2 	= $_POST['cod_fabrica'];
	$nome_fabrica2 	= $_POST['nome_fabrica'];
	$tipo 			= $_POST['tipo'];
	$tipo_pesquisa  = $_POST['tipo_pesquisa'];
	
?>

<form method="POST" action="cript.php">

	<h1>Criptografador - Assistencia Técnica</h1>

	<storng>Código da Fabrica</strong> <br />
	<input type="text" name="cod_fabrica" value="<?=$cod_fabrica2?>" /> <br /> <br />

	<storng>Nome da Fabrica</strong> <br />
	<input type="text" name="nome_fabrica" value="<?=$nome_fabrica2?>" /> <br /> <br />

	<storng>Tipo de Assistência Técnica</strong> <br />
	<select name="tipo"> 
		<option value="comum">Comum</comum>
		<option value="google_maps">Com Google Maps</comum>
	</select>
	<br /> <br />

	<storng>Tipo de Pesquisa</strong> <span style="color: red; ">* Liberar a flag 'usaTipoPesquisa'</span> <br />
	<select name="tipo_pesquisa"> 
		<option value="1">Ambos</comum>
		<option value="2">Mapa de Rede</comum>
		<option value="3">Consulta OS</comum>
	</select>
	<br /> <br />


	<input type="submit" value="Obter Criptografia" />

</form>

<?php

	if(isset($_POST['cod_fabrica']) && isset($_POST['nome_fabrica'])){

		echo "<br /> <br />";

		echo "<div class='boxcript'>";

			$cod_fabrica = $_POST['cod_fabrica'];
			$cod_fabrica2 = $_POST['cod_fabrica'];
			$nome_fabrica = $_POST['nome_fabrica'];
			$nome_fabrica2 = $_POST['nome_fabrica'];

			$url_tipo = ($tipo == "google_maps") ? "_maps" : "";

			switch ($tipo_pesquisa) {
				case '1': $tipo_pesquisa = 'all';    break;
				case '2': $tipo_pesquisa = 'bymapa'; break;
				case '3': $tipo_pesquisa = 'byos';   break;
				default:  $tipo_pesquisa = 'all';    break;
			}

			$tipo_pesquisa = base64_encode(trim($tipo_pesquisa));
			$cod_fabrica = base64_encode(trim($cod_fabrica));
			$nome_fabrica = base64_encode(trim($nome_fabrica));
			$token = base64_encode(trim("telecontrolNetworking".$nome_fabrica2."assistenciaTecnica".$cod_fabrica2));

			echo "<strong>Código da Fabrica</strong>: ".$cod_fabrica."<br /> <br />";

			echo "<strong>Nome da Fabrica</strong>: ".$nome_fabrica."<br /> <br />";

			echo "<strong>Token</strong>: ".$token."<br /> <br />";

			echo "<strong>URL</strong>: <br /> <textarea cols=100 rows=5>".$host."externos/assistencia_tecnica".$url_tipo.".php?cf=".$cod_fabrica."&nf=".$nome_fabrica."&tk=".$token."&getby=".$tipo_pesquisa."</textarea>";

		echo "</div>";

	}

?>