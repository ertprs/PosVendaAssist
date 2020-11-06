<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["tipo_pedido"]) > 0) {
	$tipo_pedido = trim($_GET["tipo_pedido"]);
}

if (strlen($_POST["tipo_pedido"]) > 0) {
	$tipo_pedido = trim($_POST["tipo_pedido"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if (strlen($_POST["garantia_antecipada"]) > 0) {
	$garantia_antecipada = trim($_POST["garantia_antecipada"]);
}

if ($btnacao == "deletar" and strlen($tipo_pedido) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_tipo_pedido
			WHERE  fabrica = $login_fabrica
			AND    tipo_pedido = $tipo_pedido";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strpos ($msg_erro,'tipo_pedido_fk') > 0) $msg_erro = "Este tipo de pedido não pode ser excluido";
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$tipo_pedido = $_POST["tipo_pedido"];
		$codigo      = $_POST["codigo"];
		$descricao   = $_POST["descricao"];
		$garantia_antecipada = $_POST["garantia_antecipada"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["descricao"]) > 0) {
			$aux_descricao = "'". trim($_POST["descricao"]) ."'";
			$aux_descricao = str_replace('"', "'", $aux_descricao);
		}else{
			$aux_descricao = "null";
		}
	}
	$aux_garantia_antecipada = trim ($_POST ['garantia_antecipada']);
	if (strlen ($aux_garantia_antecipada) == 0) {
		$aux_garantia_antecipada = "null";
	}else{
		$aux_garantia_antecipada = "'" . $aux_garantia_antecipada . "'";
	}

	$aux_codigo = trim ($_POST ['codigo']);
	if (strlen ($aux_codigo) == 0) {
		$aux_codigo = "null";
	}else{
		$aux_codigo = "'" . $aux_codigo . "'";
	}

	if ($aux_descricao == "null") {
		$msg_erro = "Favor informar a descrição do Tipo de Pedido";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($tipo_pedido) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_tipo_pedido (
						fabrica,
						codigo,
						descricao,
						garantia_antecipada
					) VALUES (
						$login_fabrica,
						$aux_codigo   ,
						$aux_descricao,
						$aux_garantia_antecipada
					)";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_tipo_pedido SET
							codigo    =  $aux_codigo,
							descricao =  $aux_descricao,
							garantia_antecipada = $aux_garantia_antecipada
					WHERE  fabrica     = $login_fabrica
					AND    tipo_pedido = $tipo_pedido";
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
		$tipo_pedido   = $_POST["tipo_pedido"];
		$codigo        = $_POST["codigo"];
		$descricao     = $_POST["descricao"];
		$garantia_antecipada     = $_POST["garantia_antecipada"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($tipo_pedido) > 0 AND strlen ($msg_erro) == 0) {
	$sql = "SELECT  codigo,
					descricao,
						garantia_antecipada
			FROM    tbl_tipo_pedido
			WHERE   fabrica     = $login_fabrica
			AND     tipo_pedido = $tipo_pedido;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));
		$garantia_antecipada = trim(pg_result($res,0,garantia_antecipada));
	}
}
?>

<?
	$layout_menu = "cadastro";
	$title = "CADASTRAMENTO DE TIPO DE PEDIDOS";
	$msg = $_GET['msg'];
	include 'cabecalho.php';
?>

<style>
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

table.tabela tr td{
	font-family: verdana; 
	font-size: 11px; 
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
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
}

.subtitulo{

color: #7092BE
}


</style>

<div id='wrapper'>
<form name="frm_tipopedido" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="tipo_pedido" value="<? echo $tipo_pedido ?>">

<table width="700" class="formulario" border='0' cellspacing='0' cellpadding='5' align='center'>

<? if (strlen($msg_erro) > 0) { ?>
	<tr class="msg_erro">
		<td colspan='3'>
<?
	if (strpos ($msg_erro,'duplicate key') > 0) $msg_erro = "Código duplicado não permitido";
	echo $msg_erro; ?>
		</td>
	</tr>

 <? } ?>

<? if (strlen($msg) > 0) { ?>
	<tr class="sucesso">
		<td colspan='3'>
			<? echo $msg; ?>
		</td>
	</tr>

 <? } ?>
<tr class="titulo_tabela"><td colspan="3">Cadastrar Tipo de Pedido</td></tr>
<tr>
<td align='left' ><b><font style="margin-left:15%;">Descrição</font> </b></td>
<td align='left' ><b>Código do Tipo do Pedido </b></td>
<td align='left' ><b>Garantia Antecipada </b></td>

</tr>
<tr>
<td align='left'><font style="margin-left:15%;"><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"></font></td>
<td align='left'><input class='frm' type="text" name="codigo" value="<? echo $codigo ?>" size="20" maxlength="6"></td>
<td align='left'><INPUT TYPE="checkbox" NAME="garantia_antecipada" VALUE='t' <? if ($garantia_antecipada == 't'){ echo ' checked ';} ?> ></td>
</tr>
<input type='hidden' name='btnacao' value=''>
<tr>
<td colspan='3' align='center'><input type="image" SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_tipopedido.btnacao.value == '' ) { document.frm_tipopedido.btnacao.value='gravar' ; document.frm_tipopedido.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<input type="image" SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_tipopedido.btnacao.value == '' ) { document.frm_tipopedido.btnacao.value='deletar' ; document.frm_tipopedido.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar Informação" border='0' style="cursor:pointer;">
<input type="image" SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' style="cursor:pointer;"></td>
</tr>
</table>




<p>


<div id="wrapper">
	<b>Para efetuar alterações, clique na descrição do Tipo do Pedido.</b>
</div>

<?
$sql = "SELECT  tipo_pedido   ,
				descricao     ,
				codigo
		FROM    tbl_tipo_pedido
		WHERE   fabrica = $login_fabrica
		ORDER BY descricao";

$res0 = pg_exec ($con,$sql);

echo "<DIV id=\"wrapper\">";//e

echo "<BLOCKQUOTE>";

echo "<TABLE width='700' class='tabela' border='0' align='center'>	
	<tr class='titulo_tabela'> 
		<td colspan='2'>Relação dos Tipos de Pedido</td>
	</tr>";
echo "<tr class='titulo_coluna'><td>Descriçao</td><td>Código</td></tr>";
for ($y = 0 ; $y < pg_numrows($res0) ; $y++){
	$cor = "#F7F5F0"; 
		if ($y % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
	$tipo_pedido    = trim(pg_result($res0,$y,tipo_pedido));
	$codigo = trim(pg_result($res0,$y,codigo));
	$descricao      = trim(pg_result($res0,$y,descricao));

	echo "	<TR bgcolor='$cor'>";
	echo "		<TD width='150' align=\"left\">";
	echo "			<a href='$PHP_SELF?tipo_pedido=$tipo_pedido'>$descricao</a>";
	echo "		</TD>";

	echo "		<TD WIDTH=\"50\" align=\"left\">";
	echo "			<a href='$PHP_SELF?tipo_pedido=$tipo_pedido'>$codigo</a>";
	echo "		</TD>";

	echo "	</TR>";

}

	echo "</TABLE>";

// 	echo "<div id=\"middlecol\" align=\"left\">\n";
//	echo "<a href='$PHP_SELF?tipo_pedido=$tipo_pedido'>$descricao</a>\n";
//	echo "</div>";
//}

echo "</BLOCKQUOTE>";
//echo "</DIV>";

echo "</div>\n";

?>
<!-- </div> -->
</form>
</div>
<?
	include "rodape.php";
?>