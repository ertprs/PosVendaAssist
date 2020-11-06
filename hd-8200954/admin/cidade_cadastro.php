<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_debug = "";

if (strlen($_GET["cidade_sla"]) > 0) {
	$cidade_sla = trim($_GET["cidade_sla"]);
}

if (strlen($_POST["cidade_sla"]) > 0) {
	$cidade_sla = trim($_POST["cidade_sla"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($cidade_sla) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_cidade_sla
			WHERE  tbl_cidade_sla.fabrica    = $login_fabrica
			AND    tbl_cidade_sla.cidade_sla = $cidade_sla;";
	$res = @pg_exec ($con,$sql);

	$msg_erro = pg_errormessage($con);

	if (strpos ($msg_erro,'tbl_cidade_sla') > 0) $msg_erro = "Esta cidade não pode ser excluida";
	if (strpos ($msg_erro,'cidade_sla_fk') > 0) $msg_erro = "Esta cidade não pode ser excluida";
	
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg=Excluído com Sucesso");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		
		$cidade_sla  = $_POST["cidade_sla"];
		$cidade      = $_POST["cidade"];
		$estado      = $_POST["estado"];
		$hora        = $_POST["hora"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	
	if (strlen($_POST["cidade_sla"]) > 0) {
		$aux_cidade_sla = "'". trim($_POST["cidade_sla"]) ."'";
	}else{
		$cidade_sla = "";
	}

	if (strlen($_POST["cidade"]) > 0) {
		$aux_cidade = "'". trim($_POST["cidade"]) ."'";
	}else{
		$msg_erro = "Informe a cidade.";
	}

	if (strlen($_POST["estado"]) > 0) {
		$aux_estado = "'". trim($_POST["estado"]) ."'";
	}else{
		$msg_erro = "Informe o estado.";
	}
	
	if (strlen($_POST["hora"]) > 0) {
		$aux_hora = "'". trim($_POST["hora"]) ."'";
	}else{
		$msg_erro = "Informe as horas.";
	}


	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($cidade_sla) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_cidade_sla (
						fabrica ,
						cidade  ,
						estado  ,
						hora
					) VALUES (
						$login_fabrica,
						$aux_cidade   ,
						$aux_estado   ,
						$aux_hora
					);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_cidade_sla')");
			$cidade_sla  = pg_result ($res,0,0);

			/* Verifica Cidade */

			$cidade = $aux_cidade;
			$estado = $aux_estado;

			$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(fn_retira_especiais(nome), 'LATIN9')) = UPPER(TO_ASCII(fn_retira_especiais('{$cidade}'), 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) == 0){

				$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(TO_ASCII(fn_retira_especiais(cidade), 'LATIN9')) = UPPER(TO_ASCII(fn_retira_especiais('{$cidade}'), 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){

					$cidade = pg_fetch_result($res, 0, 'cidade');
					$estado = pg_fetch_result($res, 0, 'estado');

					$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
					$res = pg_query($con, $sql);

				}

			}

			/* Fim - Verifica Cidade */

			$msgs = "Gravado com Sucesso!";

		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_cidade_sla SET
					cidade  = $aux_cidade,
					estado  = $aux_estado,
					hora  	= $aux_hora
			WHERE  tbl_cidade_sla.fabrica    = $login_fabrica
			AND    tbl_cidade_sla.cidade_sla = $cidade_sla";

			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$x_defeito_constatado = $defeito_constatado;
			
			$msgs = "Atualizado com Sucesso!";
		}

		if(strpos($msg_erro, 'duplicate key violates unique constraint "tbl_defeito_constatado_codigo"'))
			$msg_erro= "O código digitado já esta cadastrado em outro defeito";

	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=".$msgs."");
		exit;
	}else{
		$cidade_sla  = $_POST["cidade_sla"];
		$cidade      = $_POST["cidade"];
		$estado      = $_POST["estado"];
		$hora        = $_POST["hora"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($cidade_sla) > 0) {

	$sql = "SELECT  tbl_cidade_sla.cidade_sla,
					tbl_cidade_sla.cidade    ,
					tbl_cidade_sla.estado    ,
					tbl_cidade_sla.hora      
			FROM    tbl_cidade_sla
			WHERE   tbl_cidade_sla.fabrica = $login_fabrica
			AND     tbl_cidade_sla.cidade_sla = $cidade_sla			
			ORDER BY tbl_cidade_sla.estado, tbl_cidade_sla.cidade;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$cidade_sla   = trim(pg_result($res,0,cidade_sla));
		$cidade       = trim(pg_result($res,0,cidade));
		$estado       = trim(pg_result($res,0,estado));
		$hora         = trim(pg_result($res,0,hora));
	}
}
	$msg = $_GET['msg'];
	$layout_menu = "cadastro";
	$title = "CADASTRAMENTO DE CIDADES";
	include 'cabecalho.php';
	
?>

<style type='text/css'>
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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

.espaco{
	padding-left: 70px;
}
</style>

<form name="frm_defeito_constatado" method="post" action="<?=$PHP_SELF?>">
<input type="hidden" name="cidade_sla" value="<?=$cidade_sla ?>">

<table width='700' border='0'  align='center' cellpadding='3' cellspacing='0' class='formulario'>
	<?php if (strlen($msg_erro) > 0) { ?>
		<tr class='msg_erro'>
			<td colspan='3'><?php echo $msg_erro; ?></td>
		</tr>
	<?php } ?>

	<?php if (strlen($msg) > 0) { ?>
		<tr class='sucesso'>
			<td colspan='3'><?php echo $msg; ?></td>
		</tr>
	<?php } ?>
<tr class='titulo_tabela'>
	<td colspan='3' >Cadastro</td>
</tr>

<tr>
	<td align='left' width='400' class='espaco'>Cidade (*)<br><input class='frm' type='text' name='cidade' value='<?=$cidade?>' size='50' maxlength='20'></td>
	<td align='left' width='100'>Estado (*)<br><input class='frm' type='text' name='estado' value='<?=$estado?>' size='5' maxlength='2'></td>
	<td align='left'>Hora (*)<br><input class='frm' type='text' name='hora' value='<?=$hora?>' size='5' ></td>
</tr>

<tr><td colspan='3'>&nbsp;</td></tr>

<tr>
	<td colspan='3' align='center'>
		<input type='hidden' name='btnacao' value=''>
		<input type="button" value="Gravar" ONCLICK="javascript: if (document.frm_defeito_constatado.btnacao.value == '' ) { document.frm_defeito_constatado.btnacao.value='gravar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
		<input type="button" value="Apagar" ONCLICK="javascript: if (document.frm_defeito_constatado.btnacao.value == '' ) { document.frm_defeito_constatado.btnacao.value='deletar' ; document.frm_defeito_constatado.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Informação" border='0' >
		<input type="button" value="Limpar" ONCLICK="javascript: if (document.frm_defeito_constatado.btnacao.value == '' ) { document.frm_defeito_constatado.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' >
	</td>
</tr>

<tr><td colspan='3'>&nbsp;</td></tr>

</table>
</form>

<br>



<?php
if (strlen ($cidade_sla) == 0) {
	echo "<br><br><center><font size='2'><b>Relação de Cidades</b><BR>
	<I>Para efetuar alterações, clique na descrição da cidade.</i></font>
	</center>";

	$sql = "SELECT  tbl_cidade_sla.cidade_sla,
					tbl_cidade_sla.cidade    ,
					tbl_cidade_sla.estado    ,
					tbl_cidade_sla.hora      
			FROM    tbl_cidade_sla
			WHERE   tbl_cidade_sla.fabrica = $login_fabrica			
			ORDER BY tbl_cidade_sla.estado, tbl_cidade_sla.cidade;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table  align='center' width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'>\n";
		echo "<tr class='titulo_coluna'>";
		echo "<td nowrap><b>Cidade</b></td>";
		echo "<td nowrap>Estado</td>";
		echo "<td align='left'>Hora</td>";
		echo "</tr>";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){

			$cidade_sla   = trim(pg_result($res,$x,cidade_sla));
			$cidade       = trim(pg_result($res,$x,cidade));
			$estado       = trim(pg_result($res,$x,estado));
			$hora         = trim(pg_result($res,$x,hora));

			$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr bgcolor='$cor'>";
			echo "<td nowrap><a href='$PHP_SELF?cidade_sla=$cidade_sla'>$cidade</a></td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?cidade_sla=$cidade_sla'>$estado</a></td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?cidade_sla=$cidade_sla'>$hora</a></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}

echo "<br>";

include "rodape.php";
?>
