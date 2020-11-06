<?
//liberado tela nova 17/10 takashi
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";

include 'autentica_admin.php';

include 'funcoes.php';

$msg_debug = "";

if (strlen($_GET["causa_troca"])  > 0) $causa_troca = trim($_GET["causa_troca"]);
if (strlen($_POST["causa_troca"]) > 0) $causa_troca = trim($_POST["causa_troca"]);
$msg_sucesso = ( trim($_POST["msg_sucesso"]) ) ?  trim( $_POST["msg_sucesso"] ) : trim( $_GET["msg_sucesso"] )  ;

if (strlen($_POST["btnacao"])     > 0) $btnacao = trim($_POST["btnacao"]);

if ($btnacao == "deletar" and strlen($causa_troca) > 0 ) {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_causa_troca
			WHERE  tbl_causa_troca.fabrica     = $login_fabrica
			AND    tbl_causa_troca.causa_troca = $causa_troca;";
	$res = @pg_query ($con,$sql);

	$msg_erro = pg_errormessage($con);


	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg_sucesso=Gravado com Sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$msg_erro = "Causa da troca não pode ser apagada, já é usada em alguma OS";
		$causa_troca   = $_POST["causa_troca"];
		$descricao     = $_POST["descricao"];
		$codigo        = $_POST["codigo"];
		$ativo         = $_POST["ativo"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {

	if (strlen($_POST["descricao"]) > 0) $aux_descricao = "'". trim($_POST["descricao"]) ."'"; else $msg_erro  = "Informe a causa da troca.";
	if (strlen($_POST["ativo"])     > 0) $aux_ativo     = "'t'";                               else $aux_ativo = "'f'";
	if (strlen($_POST["codigo"])    > 0) $aux_codigo    = "'". trim($_POST["codigo"]) ."'";    else $aux_codigo = "null";
	if (strlen($_POST["tipo"])    > 0) $aux_tipo    = "'". trim($_POST["tipo"]) ."'";    else $aux_tipo = "''";
	if (strlen($_POST["codigo"]) > 0) $aux_descricao = "'". trim($_POST["descricao"]) ."'"; else $msg_erro  = "Informe o código";
	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");
		if (strlen($causa_troca) == 0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_causa_troca (
						fabrica  ,
						codigo   ,
						descricao,
						ativo	 ,
						tipo
					) VALUES (
						$login_fabrica,
						$aux_codigo   ,
						$aux_descricao,
						$aux_ativo    ,
						$aux_tipo
					);";
		$res = @pg_query($con,$sql);

		$msg_sucesso = "Gravado com Sucesso!";
		$msg_erro = pg_errormessage($con);

		$res = @pg_query ($con,"SELECT CURRVAL ('tbl_causa_troca_causa_troca_seq')");
		$x_causa_troca  = pg_result ($res,0,0);

		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE tbl_causa_troca SET
					codigo     = $aux_codigo   ,
					descricao  = $aux_descricao,
					ativo      = $aux_ativo	   ,
					tipo	   = $aux_tipo
			WHERE  tbl_causa_troca.fabrica     = $login_fabrica
			AND    tbl_causa_troca.causa_troca = $causa_troca";

			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			$x_causa_troca = $causa_troca;

		}

	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg_sucesso=Gravado com Sucesso!");
		exit;
	}else{
		$causa_troca    = $_POST["causa_troca"];
		$ativo          = $_POST["ativo"];
		$codigo         = $_POST["codigo"];
		$descricao      = $_POST["descricao"];
		$tipo           = $_POST["tipo"];

		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

###CARREGA REGISTRO
if (strlen($causa_troca) > 0) {

	$sql = "SELECT  tbl_causa_troca.codigo   ,
					tbl_causa_troca.descricao,
					tbl_causa_troca.tipo,
					tbl_causa_troca.ativo
			FROM    tbl_causa_troca
			WHERE   tbl_causa_troca.fabrica            = $login_fabrica
			AND     tbl_causa_troca.causa_troca = $causa_troca";

	$res = pg_query ($con,$sql);

	if (pg_numrows($res) > 0) {
		$ativo     = trim(pg_result($res,0,ativo));
		$codigo    = trim(pg_result($res,0,codigo));
		$descricao = trim(pg_result($res,0,descricao));
		$tipo	   = trim(pg_result($res,0,'tipo'));
	}
}
?>
<?
	$layout_menu = "cadastro";
	$title = "CADASTRO DE CAUSA DA TROCA DE PRODUTOS";
	include 'cabecalho.php';
?>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
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
.msg_sucesso{
	background-color: green;
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
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.espaco{padding-left:80px;}
</style>

<form name="frm_causa_troca" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="causa_troca" value="<? echo $causa_troca ?>">
<? if (strlen($msg_erro) > 0) { ?>

<table width='700px' align='center'>
	<tr>
		<td class='msg_erro' width='700px'><?=$msg_erro?></td>
	</tr>
</table>

<?
 } if (strlen($msg_sucesso) > 0) { ?>

<table align='center' width='700px'>
	<tr>
		<td class='msg_sucesso' width='700px'><?=$msg_sucesso ?></td>
	</tr>
</table>
<? }
echo "<table width='700px' cellpadding='3' cellspacing='0' align='center' class='formulario'>";
echo "<tr>";
echo "<td align='left' colspan='4' class='titulo_tabela'>Cadastro</td>";
echo "<tr>";
echo "<td>&nbsp;</td>";
echo "<td align='left' nowrap class='espaco'>Código *&nbsp;<input class='frm' type='text' name='codigo' value='$codigo' size='10' maxlength='5'></td>";
echo "<td align='left' nowrap>Descrição *&nbsp;<input class='frm' type='text' name='descricao' value='$descricao' size='30' maxlength='100'></td>";
echo "<td align='left' nowrap width='100'><label><input class='frm' type='checkbox' name='ativo' value='t' ";if($ativo == 't') echo "CHECKED"; echo ">&nbsp;Ativo</label></td>";
echo "</tr>";
if($login_fabrica==20){
	echo "<tr>";
	echo "<td></td><td align='left'>Descrição Espanhol(*)<BR><input type='text' name='descricao_es' value='$descricao_es' size='40' maxlength='50' class='frm'></td>";
	echo "</tr>";
}
if($login_fabrica==1){
	$array_tipo = array('T'=>'Todos','C'=>'Consumidor','R'=>'Revenda');
	echo "<tr>";
	echo "<td>&nbsp</td><td align='left' class='espaco'>Tipo *&nbsp;";
	echo "<select name='tipo' class='frm'>";
	foreach($array_tipo as $key => $value){
		echo "<option value='$key'";
		echo ($tipo == $key) ?" selected ":"";
		echo ">$value</option>";
	}
	echo "</select></td>";
	echo "</tr>";
}

?>


	<tr>
		<td colspan='4'>
			<br />
			<center>
				<input type='hidden' name='btnacao' value=''>
				<input type='button' value="Gravar" onclick="if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='gravar' ; document.frm_causa_troca.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" border='0' style='cursor:pointer;'>
				<input type='button' value="Apagar" onclick="if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='deletar' ; document.frm_causa_troca.submit() } else { alert ('Aguarde submissão') }" alt="Apagar Informação" border='0' style='cursor:pointer;'>
				<input type='button' value="Limpar" onclick="if (document.frm_causa_troca.btnacao.value == '' ) { document.frm_causa_troca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" açt="Limpar campos" border='0' style='cursor:pointer;'>
			</center>
			<br>
		</td>
	</tr>
</table>
</form>
<br>



<?
if (strlen ($causa_troca) == 0) {

	$sql = "SELECT  tbl_causa_troca.causa_troca,
				tbl_causa_troca.codigo,
				tbl_causa_troca.tipo,
				tbl_causa_troca.descricao
			FROM    tbl_causa_troca
			WHERE   tbl_causa_troca.fabrica = $login_fabrica
			ORDER BY  tbl_causa_troca.codigo;";

	$res = pg_query ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo '<table align="center" width="700" cellspacing="1" class="tabela">'."\n";
		echo "<tr>";
		echo "<tr class='titulo_tabela'><td colspan='100%'>Relação da Causa de Troca de Produtos</td></tr>";
		echo "<tr class='titulo_coluna'><td nowrap><b>Código</b></td>";
		echo "<td nowrap>Descrição</td>";
		echo ($login_fabrica == 1) ? '<td nowrap>Tipo</td>':'';
		echo "</tr>";

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){

			$causa_troca	= trim(pg_result($res,$x,causa_troca));
			$descricao	= trim(pg_result($res,$x,descricao));
			$codigo		= trim(pg_result($res,$x,codigo));
			$tipo		= trim(pg_result($res,$x,'tipo'));

			$cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='formulario' style='background-color: $cor'>";
			echo "<td nowrap><a href='$PHP_SELF?causa_troca=$causa_troca'>$codigo</a></td>";
			echo "<td nowrap align='left'><a href='$PHP_SELF?causa_troca=$causa_troca'>$descricao</a></td>";
			echo ($login_fabrica == 1) ? '<td nowrap>'.$array_tipo[$tipo].'</td>':'';
			echo "</tr>";
		}
		echo "</table>";
	}
}

echo "<br>";

include "rodape.php";
?>
