<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';

if(strlen($login_unico)>0 AND $login_unico_master <>'t'){
	if($login_unico_distrib_total <>'t') { // HD 49866
		echo "<center><h1>Você não tem autorização para acessar este programa!</h1><br><br><a href='javascript:history.back();'>Voltar</a></center>";
		exit;
	}
}

$btn_acao = trim ($_POST['btn_acao']);
if (strlen ($btn_acao) > 0) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	#-------------- Confirma conferência atual ----------#
	$qtde_item   = $_POST['qtde_item'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$referencia       = $_POST['referencia_' . $i];
		$qtde             = $_POST['qtde_'  . $i];
		$localizacao      = $_POST['localizacao_'   . $i];
		$motivo           = $_POST['motivo_'   . $i];
		$troca            = $_POST['troca_'   . $i];
		$peca            = $_POST['peca_'   . $i];

		$localizacao = strtoupper (trim ($localizacao));
		$motivo      = trim ($motivo);
		if (strlen ($troca) == 0) $troca = 'f';

		//if (strlen (trim ($referencia)) < 6 AND strlen (trim ($referencia)) > 0) {
		//	$referencia = "000000" . trim ($referencia);
		//	$referencia = substr ($referencia,strlen ($referencia)-6);
		//}

		if (strlen ($referencia) > 0 and empty($peca)) {
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica IN (".implode(",", $fabricas).") AND ativo";
			//or fabrica = 10
			//echo $sql; exit;

			$res = pg_exec ($con,$sql);

			if(!valida_mascara_localizacao($localizacao)){
				$msg_erro .= "Localização $localizacao inválida. <br>";
			}

			if (pg_numrows ($res) == 0) {
				$msg_erro .= "Peça $referencia não existe";
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}else{
				$peca = pg_result ($res,0,0);
			}
		}

		if(strlen(trim($msg_erro))==0 and !empty($peca)){
				$sql = "INSERT INTO tbl_posto_estoque_acerto (posto, peca, qtde, troca, motivo, login_unico) VALUES ($login_posto, $peca, $qtde, '$troca', '$motivo', '$login_unico')";
				
				$res = pg_exec ($con,$sql);

				$sql_fab_peca = "SELECT fabrica, peca FROM tbl_peca WHERE peca = $peca";
				$res_fab_peca = pg_query($con, $sql_fab_peca);
				$fab_peca = pg_fetch_result($res_fab_peca, 0, 'fabrica');
				$ref_peca = pg_fetch_result($res_fab_peca, 0, 'referencia');

				if (in_array($fab_peca, [11,172])) {
					atualiza_localizacao_lenoxx($peca, $localizacao, $login_posto);
				} else {
					$sql = "SELECT * FROM tbl_posto_estoque_localizacao WHERE peca = $peca";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 0) {
						$sql = "INSERT INTO tbl_posto_estoque_localizacao (posto,peca,localizacao) VALUES ($login_posto, $peca, '$localizacao')";
						$res = pg_exec ($con,$sql);
					}else{
						if (strlen ($localizacao) > 0) {
							$sql = "SELECT * FROM tbl_posto_estoque_localizacao WHERE peca = $peca ";
							$res = pg_exec ($con,$sql);
							if (pg_numrows ($res) > 0) {
								$sql = "UPDATE tbl_posto_estoque_localizacao SET localizacao = '$localizacao',posto = $login_posto WHERE  peca = $peca ";
								$res = pg_exec ($con,$sql);
							}else{
								$sql = "INSERT INTO tbl_posto_estoque_localizacao (posto, peca, localizacao) VALUES ($login_posto, $peca, '$localizacao')";
								$res = pg_exec ($con,$sql);
							}
						}
					}
				}
			}
		}

	$res = pg_exec ($con,"COMMIT TRANSACTION");

	if(strlen(trim($msg_erro))==0){
		header ("Location: estoque_acerto.php");
		exit;
	}
}

$title = "Acerto de Peças do Estoque";
?>

<html>
<head>
<title><?php echo $title ?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<script type="text/javascript" src="../js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='../js/jquery.alphanumeric.js'></script>
<?include "javascript_calendario_new.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

</head>
<script type="text/javascript">
	
	function alteraMaiusculo(valor){
		var novoTexto = valor.value.toUpperCase();
		valor.value = novoTexto;
	}
$(document).ready(function (){
	

	function formatItem(row) {
		return row[0] + " - " + row[1] + " - " + row[2];
	}

	function formatResult(row) {
		return row[0];
	}

	$("input[name^='referencia']").autocomplete("peca_consulta_ajax.php?busca=codigo", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1]; return row[3];}
	});

	$("input[name^='referencia']").result(function(event, data, formatted) {
		$(this).val(data[1]) ;
		$(this).parent().children("input[name^='peca']").val(data[3]) ;
	});


});


</script>

<body>

<? include 'menu.php' ?>


<center><h1>Acerto de Peças do Estoque</h1></center>
<center><h3>Qtde negativa para diminuir saldo</h1></center>

<?php if(strlen(trim($msg_erro))>0){ ?>
	<div class='msg_erro' style='width:600px;'><?=$msg_erro?></div>
<?php } ?>

<p>

<table width='600' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Peça</td>
	<td align='center'>Descrição</td>
	<td align='center'>Qtde</td>
	<td align='center'>Localização</td>
	<td align='center'>Troca</td>
	<td align='center'>Motivo</td>
</tr>


<?

echo "<form method='post' action='$PHP_SELF' name='frm_nf_entrada_item'>";

$qtde_item = 20 ;
for ($i = 0 ; $i < $qtde_item ; $i++) {

	$referencia  = $_POST['referencia_' . $i];
	$qtde        = $_POST['qtde_' . $i];
	$localizacao = $_POST['localizacao_' . $i];
	$motivo      = $_POST['motivo_' . $i];
	$troca       = $_POST['troca_' . $i];

	$cor = "#FFFBF0";
	if ($i % 2 == 0) $cor = "#FFEECC";

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";

	echo "<td align='center' nowrap><input type='text' class='frm' name='referencia_$i'  value='$referencia'  size='10'><input type='hidden' name='peca_$i' ></td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";
	echo "<td align='center' nowrap><input type='text' class='frm' name='qtde_$i'  value='$qtde'  size='5'  maxlength='5' ></td>\n";
	echo "<td align='center' nowrap><input type='text' class='frm localizacao' title='Formato Válido: LL-LNN-LNN, LNN-LNN, LLL-LNNN, LNNN-LNN'  onkeyup='alteraMaiusculo(this)'  name='localizacao_$i'   value='$localizacao'   size='10' maxlength='15'></td>\n";
	echo "<td align='center' nowrap><input type='checkbox' name='troca_$i' " ;
	if (strlen ($troca) > 0) echo " checked ";
	echo " value='t' ></td>\n";
	echo "<td align='center' nowrap><input type='text' class='frm' name='motivo_$i'   value='$motivo'   size='20' maxlength='50'></td>\n";

	echo "</tr>\n";
}


echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";

echo "<input type='submit' name='btn_acao' value='Entrar Acertos !'>";

echo "</form>";
echo "</td>";
echo "</tr>";


echo "</table>\n";

?>

<p>

<? include "rodape.php"; ?>

</body>
