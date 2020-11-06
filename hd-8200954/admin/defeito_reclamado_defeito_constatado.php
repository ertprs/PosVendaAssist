<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen(trim($_GET["defeito_reclamado"])) > 0) $defeito_reclamado = trim($_GET["defeito_reclamado"]);

if (strlen(trim($_POST["defeito_reclamado"])) > 0) $defeito_reclamado = trim($_POST["defeito_reclamado"]);

if (strlen($_POST["qtde_item"]) > 0) {
	$qtde_item = trim($_POST["qtde_item"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "gravar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	if (strlen($msg_erro) == 0){
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$novo                         = $_POST['novo_'.$i];
			$defeito_reclamado_constatado = $_POST['defeito_reclamado_constatado_'.$i];
			$defeito_constatado           = $_POST['defeito_constatado_'.$i];
//echo $novo." - ".$defeito_reclamado_constatado." - ".$defeito_constatado."<br><br>";
			if ($novo == 'f' and strlen($defeito_constatado) == 0) {
				$sql = "DELETE FROM tbl_defeito_reclamado_constatado
						WHERE       defeito_reclamado_constatado = $defeito_reclamado_constatado";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
//echo $sql."<br><br>";
			}
			
			if (strlen ($msg_erro) == 0) {
				if (strlen($defeito_constatado) > 0) {
					if ($novo == 't'){
						$sql =	"INSERT INTO tbl_defeito_reclamado_constatado (
									defeito_reclamado  ,
									defeito_constatado 
								) VALUES (
									$defeito_reclamado  ,
									$defeito_constatado 
								);";
					}else{
						$sql =	"UPDATE tbl_defeito_reclamado_constatado SET
										defeito_constatado = $defeito_constatado ,
										defeito_reclamado  = $defeito_reclamado  
								WHERE defeito_reclamado_constatado = $defeito_reclamado_constatado;";
					}
//echo $sql."<br><br>";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
	}
//exit;
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$layout_menu = "cadastro";
$title = "CADASTRAMENTO DE CAUSAS DE DEFEITO POR DEFEITO";
include 'cabecalho.php';
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
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

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

10:21 30/07/2010
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
	font: 12px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<body>

<div id="wrapper">

<form name="frm_defeito" method="post" action="<? $PHP_SELF ?>">
<input type='hidden' name='defeito_reclamado' value='<? echo $defeito_reclamado ?>'>
<input type='hidden' name='btnacao' value=''>

<? if (strlen($msg_erro) > 0) { ?>

<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?  }  ?>

<?php
	$msg_sucesso = $_GET['msg'];
	if( strlen( $msg_sucesso ) > 0  && strlen( $msg_erro ) == 0)
	{

?>
<table width='700px' class='msg_sucesso' align='center'>
	<tr>
		<td> <?php echo $msg_sucesso ?> </td>
	</tr>
</table>
<?
	}
?>

<br>

<? 
if (strlen($defeito_reclamado) == 0) {
	echo "<div class='texto_avulso'>Clique na Descrição para Consultar o \"Defeito Reclamado\"</div><br />\n";
}else{

	$sql =	"SELECT tbl_defeito_reclamado.descricao
			FROM    tbl_defeito_reclamado
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_linha.fabrica = $login_fabrica
			AND     tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
	$res = pg_exec($con,$sql);
	$descricao = trim(pg_result($res,0,descricao));

	echo "<table class='formulario' width='700px' align='center'>
			  <tr >
				<td class='titulo_coluna' width='100%'>Clique na Descrição para Consultar o \"Defeito Reclamado\" \"$descricao\"</td>\n
			  </tr>";
		 

	$sql =	"SELECT defeito_constatado,
					codigo       ,
					descricao
			FROM    tbl_defeito_constatado
			WHERE   fabrica = $login_fabrica
			ORDER BY codigo;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table width='700px' align='center' border='0' cellspacing='1' cellpadding='2' class='formulario'>\n";
		echo "<tr>\n";
		echo "<td align='left'>\n";

		$y = 1;

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
			$codigo             = trim(pg_result($res,$i,codigo));
			$descricao          = trim(pg_result($res,$i,descricao));

			if (strlen($defeito_constatado) > 0) {
				$sql =	"SELECT defeito_reclamado_constatado ,
								defeito_reclamado            ,
								defeito_constatado           
						FROM    tbl_defeito_reclamado_constatado
						WHERE   defeito_reclamado = $defeito_reclamado
						AND     defeito_constatado = $defeito_constatado";
				$res2 = pg_exec($con,$sql);

				if (pg_numrows($res2) > 0) {
					$novo               = 'f';
					$xdefeito_reclamado_constatado  = pg_result($res2,0,defeito_reclamado_constatado);
					$xdefeito_reclamado             = pg_result($res2,0,defeito_reclamado);
					$xdefeito_constatado            = pg_result($res2,0,defeito_constatado);
				}else{
					$novo                          = 't';
					$xdefeito_reclamado_constatado = "";
					$xdefeito_reclamado            = "";
					$xdefeito_constatado           = "";
				}
			}else{
				$novo                          = 't';
				$xdefeito_reclamado_constatado = "";
				$xdefeito_reclamado            = "";
				$xdefeito_constatado           = "";
			}

			$resto = $y % 2;
			$y++;

			if ($xdefeito_constatado == $defeito_constatado) $check = " checked";
			else                                             $check = "";

			echo "";
			echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
			echo "<input type='hidden' name='defeito_reclamado_constatado_$i' value='$xdefeito_reclamado_constatado'>\n";
			echo "<input type='checkbox' name='defeito_constatado_$i' value='$defeito_constatado' $check>\n</TD>\n";
			echo "<TD align='left' nowrap>$codigo </TD>\n";
			echo "<TD align='left' nowrap>$descricao";

			if($resto == 0){
				echo "</td>\n</tr>\n";
				echo "<tr>\n<td align='left'>\n";
			}else{
				echo "</td>\n";
				echo "<td align='left'>\n";
			}

		}
		echo "</table>\n";
	}

echo "<input type='hidden' name='qtde_item' value='$i'>\n";
?>

<br>

<center>
<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='gravar' ; document.frm_defeito.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<IMG SRC="imagens/btn_voltar.gif" ONCLICK="javascript: if (document.frm_defeito.btnacao.value == '' ) { document.frm_defeito.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
</center>
<br />
</form>

<?
}

$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado               ,
				tbl_defeito_reclamado.descricao                       ,
				tbl_defeito_reclamado.ativo                           ,
				tbl_linha.nome                          AS linha_nome 
		FROM    tbl_defeito_reclamado
		JOIN    tbl_linha USING (linha)
		WHERE   tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_linha.nome, tbl_defeito_reclamado.descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='700px' align='center' border='0' cellspacing='1' cellpadding='2' class='tabela'>\n";
	echo "<tr>\n";
	echo "<td colspan='3' class='titulo_tabela'>Relação dos Defeitos Reclamados</td>\n";
	echo "</tr>\n";
	echo "<tr class='titulo_coluna' align='left'> \n";
	echo "<td align='left'>Linha</td>\n";
	echo "<td align='left'>Defeito Reclamado</td>\n";
	echo "<td align='center'>Situação</td>\n";
	echo "</tr>\n";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
		$defeito_reclamado = trim(pg_result($res,$x,defeito_reclamado));
		$descricao         = trim(pg_result($res,$x,descricao));
		$ativo             = pg_result($res,$x,ativo);
		$linha_nome        = trim(pg_result($res,$x,linha_nome));

		$cor = ( $x%2 == 0 ) ? '#F7F5F0' : '#F1F4FA';

		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td align='left' nowrap>$linha_nome</td>\n";
		echo "<td align='left' nowrap><a href='$PHP_SELF?defeito_reclamado=$defeito_reclamado'>$descricao</a></td>\n";
		echo "<td align='center' nowrap>";
			if ($ativo == 't') echo "Ativo";
			else               echo "Inativo";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}else{
	echo "<p align='center'><b>NÃO HÁ DEFEITOS RECLAMADOS</b></p>\n";
}
?>

</div>

</body>

</html>
