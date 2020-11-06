<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


$label_defeito = "defeito";
if ($login_fabrica == 157) {
	$label_defeito = "motivo";
}

if (strlen($_GET["defeito"]) > 0) {
	$defeito = trim($_GET["defeito"]);
}

if (strlen($_POST["defeito"]) > 0) {
	$defeito = trim($_POST["defeito"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}


if ($btnacao == "deletar" and strlen($defeito) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_defeito
			WHERE  fabrica = $login_fabrica
			AND    defeito = $defeito;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'defeito_fk') > 0) $msg_erro = "Este {$label_defeito} não pode ser excluido";

	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$defeito        = $_POST["defeito"];
		$codigo_defeito = $_POST["codigo_defeito"];
		$descricao      = $_POST["descricao"];
		$ativo      = $_POST["ativo"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btnacao == "gravar") {
	$codigo_defeito = $_POST["codigo_defeito"];

	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["descricao"]) > 0) {
			$aux_descricao = "'". trim($_POST["descricao"]) ."'";
			$aux_descricao = str_replace('"', "'", $aux_descricao);
		}else{
			$aux_descricao = "null";
		}
	}
	
	//if(strlen($codigo_defeito)==0)
	//	$msg_erro = "Informe o Código do Defeito";

	if (strlen($_POST["ativo"]) > 0) {	
		$aux_ativo = $_POST["ativo"];
	}else{
		$aux_ativo = "f";
	}


	if ($aux_descricao == "null") {
		$msg_erro = "Favor informar a descrição do {$label_defeito}.";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($defeito) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_defeito (
						fabrica,
						codigo_defeito,
						descricao,
						ativo
					) VALUES (
						$login_fabrica,
						'$codigo_defeito',
						$aux_descricao,
						'$aux_ativo'
					)";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_defeito SET
							codigo_defeito = '$codigo_defeito',
							descricao      = $aux_descricao,
							ativo          = '$aux_ativo'
					WHERE  fabrica = $login_fabrica
					AND    defeito = $defeito";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$defeito        = $_POST["defeito"];
		$codigo_defeito = $_POST["codigo_defeito"];
		$descricao      = $_POST["descricao"];
		$ativo          = $_POST["ativo"];

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
	
}

###CARREGA REGISTRO
if (strlen($defeito) > 0) {
	$sql = "SELECT  codigo_defeito,
					descricao,
					ativo
			FROM    tbl_defeito
			WHERE   fabrica = $login_fabrica
			AND     defeito = $defeito;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$codigo_defeito = trim(pg_result($res,0,codigo_defeito));
		$descricao      = trim(pg_result($res,0,descricao));
		$ativo          = trim(pg_result($res,0,ativo));
	}
}
?>

<?
	$layout_menu = "cadastro";
	$title = "CADASTRAMENTO DE ".strtoupper($label_defeito);
	include 'cabecalho.php';

	$msg = $_GET['msg'];
?>

<style>
table{
	font: Verdana;
	font-size: 10px;
}
.pequeno{
	font: Verdana;
	font-size: 9px;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.espaco{
	padding-left:50px;
}
</style>

	<form name="frm_defeito" method="post" action="<? $PHP_SELF ?>">
		<input type="hidden" name="defeito" value="<? echo $defeito ?>">


<table border="0" cellspacing="1" cellpadding="1" width='700' align='center' class="formulario">
<? if (strlen($msg_erro) > 0) { ?>


	<? echo "<tr class='msg_erro'><td colspan='4'>". $msg_erro."</td></tr>"; ?>



<? } ?>

<? if (strlen($msg) > 0) { ?>


	<? echo "<tr class='sucesso'><td colspan='4'>". $msg."</td></tr>"; ?>



<? } ?>
	<tr class="titulo_tabela"><td colspan="4">Cadastro de <?php echo $label_defeito;?></td></tr>
	<tr><td  colspan="4">&nbsp;</td></tr>
	<tr>
		<td class="espaco"><b>Descrição do <?php echo $label_defeito;?></b></td>
		<td><b>Código do <?php echo $label_defeito;?></b></td>
		<td><b>Ativo</b></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class="espaco"><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50"></td>
		<td><input class='frm' type="text" name="codigo_defeito" value="<? echo $codigo_defeito ?>" size="13" maxlength="6"></td>
		<td><input type='checkbox' name='ativo'<? if ($ativo == 't' ) echo " checked "; echo " value='t'"; 	?> ></td>
		<td width="60">&nbsp;</td>
	</tr>
	
	<tr><td  colspan="4">&nbsp;</td></tr>
	<tr>
		<td colspan="4" align='center'>
			<input type='hidden' name='btnacao' value=''>
			<input type="button"  value="Gravar" ONCLICK="javascript: if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='gravar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' >
			<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='deletar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar Informação" border='0' >
			<input type="button" value="Limpar" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' >
		</td>
	</tr>
</table>

<BR>

<table cellspacing="1" class='tabela' cellpadding="3" width='700px' align='center' class="tabela">
	<tr class="titulo_tabela">
		<td colspan="3">Relação de <?php echo $label_defeito;?></td>
	</tr>
	<tr>
		<td colspan="3" class='subtitulo'>Para efetuar alterações, clique na descrição do <?php echo $label_defeito;?>.</td>
	</tr>
	<tr class="titulo_coluna">
		<td>Ativo?</td>
		<td>Código</td>
		<td>Descrição do <?php echo $label_defeito;?></td>

	</tr>

<?
$sql = "SELECT  defeito       ,
				descricao     ,
				codigo_defeito,
				ativo
		FROM    tbl_defeito
		WHERE   fabrica = $login_fabrica
		ORDER BY ativo desc, descricao";

$res0 = pg_exec ($con,$sql);


for ($y = 0 ; $y < pg_numrows($res0) ; $y++){
	$defeito        = trim(pg_result($res0,$y,defeito));
	$codigo_defeito = trim(pg_result($res0,$y,codigo_defeito));
	$descricao      = trim(pg_result($res0,$y,descricao));
	$ativo          = trim(pg_result($res0,$y,ativo));
	if($ativo=='t'){ $ativo="Sim"; }else{$ativo="<font color='#660000'>Não</font>";}
	if(($y%2)==0) $cor="#F7F5F0";else  $cor="#F1F4FA";

	echo "<tr bgcolor='$cor'>";
	echo "<td align='center'>$ativo</td>";
	echo "<td align='center'>$codigo_defeito</td>";
	echo "<td align='left'><b><a href='$PHP_SELF?defeito=$defeito'>$descricao</a></b></td>";
	echo "</tr>";
}

echo "</table>";

?>

</form>

<?
	include "rodape.php";
?>
</body>
</html>
