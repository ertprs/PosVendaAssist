<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
//ESTÁ EM TESTE PARA A TECTOY 27/09/06 TAKASHI
if($login_fabrica=='6'){
include "linha_cadastro_new.php";
exit;
}
include 'funcoes.php';


$linha = trim($_REQUEST["linha"]);
if (isset($linha{0})) {
	$sql = "SELECT linha FROM tbl_linha WHERE linha = '".$linha."' AND fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$linha = trim(pg_result($res,0,linha));
	} else {
		$linha = "";
	}
}

if (isset($_REQUEST["btn_gravar"]{0}) and isset($linha{0})) {
	
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	//EXCLUI TODOS OS REGISTROS
	$sql = "DELETE FROM tbl_linha_solucao
			WHERE  linha = $linha;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	foreach ($_REQUEST['chk'] as $solucao) {
		$sql = "INSERT INTO tbl_linha_solucao (
					linha,
					solucao
				) VALUES (
					$linha,
					$solucao
				)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con)."<br/>";
	}

	if (isset($msg_erro{0})) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


$msg = $_GET['msg'];
$visual_black = "manutencao-admin";
$layout_menu = "cadastro";
$title = "CADASTRO DE LINHA DE PRODUTO";
if(!isset($semcab))include 'cabecalho.php';
?>

<style type="text/css">
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

</style>

<form name="frm_linha" method="post" action="<? echo $PHP_SELF;if(isset($semcab))echo "?semcab=yes"; ?>">
	<?php if (strlen($msg_erro) > 0) { ?>
		<table width="700" border="0" cellpadding="2" cellspacing="1" class="msg_erro" align='center'>
			<tr>
				<td><?echo $msg_erro;?></td>
			</tr>
		</table>
	<? } ?>
	
	<?php if (strlen($msg) > 0) { ?>
		<table width="700" border="0" cellpadding="2" cellspacing="1" class="sucesso" align='center'>
			<tr>
				<td><?echo $msg;?></td>
			</tr>
		</table>
	<? } ?>

	<table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario" >
		<tr>
			<td valign="top" align="center">
				<table class="formulario" align='center' width='700' border='0'>
					<tr class="titulo_tabela">
						<td colspan='4'>Parâmetros de Pesquisa</td>
					</tr>
					<tr>
						<td nowrap align="center" style='padding:10px 0 10px 0;'>Código da Linha
							<select name="linha" onchange="javascript: if (this.value != '') {submit();}" class="frm">
								<option value=''>Selecione</option>
								<?php
								$sql =	"SELECT   tbl_linha.*
											FROM      tbl_linha
											WHERE     fabrica = $login_fabrica
											AND       ativo is true
											ORDER BY  nome;";
								$res = @pg_exec ($con,$sql);
									
								for ($i = 0 ; $i < @pg_numrows($res) ; $i++) {
									$aux_linha = pg_result($res,$i,linha);
									$aux_nome  = pg_result($res,$i,nome);
									echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
								}
								?>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

	<center>
	<input type='hidden' name='btnacao' value=''>
</form>
<br />

<?php
if(isset($linha{0})) {

	$sql = "SELECT tbl_linha_solucao.solucao as linha_solucao,
					tbl_solucao.solucao,
					tbl_solucao.descricao
			FROM  tbl_solucao
			LEFT  JOIN tbl_linha_solucao ON tbl_linha_solucao.solucao = tbl_solucao.solucao AND tbl_linha_solucao.linha = $linha
			WHERE tbl_solucao.fabrica = $login_fabrica
			AND   tbl_solucao.ativo IS TRUE
			ORDER BY tbl_solucao.descricao;";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0){
		echo "<form name='form_solucao' action='".$PHP_SELF."' method='post'>";
			echo "<input type='hidden' name='solucao' value='$solucao'>";
			echo "<input type='hidden' name='linha' value='$linha'>";
			echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>";
				echo "<tr class='titulo_coluna'>";
					echo "<td colspan='3'><b>Soluções</b></td>";
				echo "</tr>";

				for ($i = 0 ; $i < @pg_numrows($res) ; $i++){
					$solucao       = trim(@pg_result($res,$i,solucao));
					$descricao     = trim(@pg_result($res,$i,descricao));
					$linha_solucao = trim(@pg_result($res,$i,linha_solucao));
					
					$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

					echo "<tr bgcolor='$cor'>";
						echo "<td align='left'><input type='checkbox' name='chk[]' value='$solucao'"; if (isset($linha_solucao{0})) echo " checked "; echo "> &nbsp; $descricao</td>";
					echo "</tr>";
				}
			echo "</table>";

			echo "<table width='700' border='0' cellpadding='2' cellspacing='1' class='titulo'  align='center'>";
				echo "<tr><td align='center'><input type='submit' name='btn_gravar' value='Gravar'></td></tr>";
			echo "</table>";
		echo "</form>";



	}else if (pg_numrows($res) == 0){
		echo "<font size='2' face='Verdana, Tahoma, Arial' color='#D9E2EF'><b>NENHUMA SOLUÇÃO CADASTRADA<b></font>";
	}
}


echo "<br>";

if(!isset($semcab))include "rodape.php";
?>