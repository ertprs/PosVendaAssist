<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';
$grupo_empresa = $_GET['grupo_empresa'];
if(strlen($grupo_empresa) == 0) $grupo_empresa = $_POST['grupo_empresa'];

if ($btnacao == "deletar" and strlen($grupo_empresa) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_grupo_empresa
			WHERE  tbl_grupo_empresa.fabrica       = $login_fabrica
			AND    tbl_grupo_empresa.grupo_empresa = $grupo_empresa;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btnacao == "gravar") {
	if (strlen($_POST["nome_grupo"]) > 0) $aux_nome_grupo = "'" . trim($_POST["nome_grupo"]) . "'" ;
	else                                    $aux_numero_contrato = "null";
	if (strlen($_POST["descricao"]) > 0)         $aux_descricao         = "'". trim($_POST["descricao"]) ."'";
	else                                    $msg_erro         = "Favor informar o nome do contrato.";
	if (strlen($_POST["ativo"]) > 0)        $aux_ativo        = "'t'";
	else                                    $aux_ativo        = "'f'";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($grupo_empresa) == 0) {
			$sql = "INSERT INTO tbl_grupo_empresa (
						fabrica         ,
						nome_grupo      ,
						descricao       ,
						ativo
					) VALUES (
						$login_fabrica  ,
						$aux_nome_grupo ,
						$aux_descricao  ,
						$aux_ativo
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_grupo_empresa SET
					nome_grupo = $aux_nome_grupo,
					descricao  = $aux_descricao,
					ativo      = $aux_ativo
				WHERE   tbl_grupo_empresa.fabrica       = $login_fabrica
				AND     tbl_grupo_empresa.grupo_empresa = $grupo_empresa;";
		}
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if (strlen($grupo_empresa) > 0) {
	$sql =	"SELECT tbl_grupo_empresa.grupo_empresa    ,
				tbl_grupo_empresa.nome_grupo ,
				tbl_grupo_empresa.descricao       ,
				tbl_grupo_empresa.ativo
		FROM      tbl_grupo_empresa
		WHERE     tbl_grupo_empresa.fabrica = $login_fabrica
		AND       tbl_grupo_empresa.grupo_empresa   = $grupo_empresa;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$grupo_empresa   = trim(pg_result($res,0,grupo_empresa));
		$nome_grupo      = trim(pg_result($res,0,nome_grupo));
		$descricao       = trim(pg_result($res,0,descricao));
		$ativo           = trim(pg_result($res,0,ativo));
	}
}

$layout_menu = "cadastro";
$title = "Cadastro de Grupo de empresa";
if(!isset($semcab))include 'cabecalho.php';
?>

<style type="text/css">
	.Label{
	font-family: Verdana;
	font-size: 10px;
	}
	.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	}
	.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
	}
</style>

<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF;?>">
<input type="hidden" name="grupo_empresa" value="<? echo $grupo_empresa ?>">


<? if (strlen($msg_erro) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><?echo $msg_erro;?></td>
	</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="left">
		<table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='750' border='0'>
		<tr  bgcolor="#596D9B" >
			<td align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro de Grupo de empresa</font></td>
		</tr>
		<tr class='Label'>
			<td nowrap >Nome do Grupo</td>
			<td><input type="text" name="nome_grupo" value="<? echo $nome_grupo ?>" size="10" class="frm"></td>
			<td nowrap >Descrição do Grupo</td>
			<td><input type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50" class="frm"></td>
		</tr>
		<tr class='Label'>
			<td nowrap >Ativo</td>
			<td colspan='3'><input type='checkbox' name='ativo' id='ativo' value='TRUE' <?if($ativo == 't') echo "CHECKED";?>></td>
		</tr>
		</table>
	</td>
</tr>
</table>
<br />
<center>
<input type='hidden' name='btnacao' value=''>
<img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='gravar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" alt="Gravar formulário" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='deletar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" alt="Apagar contrato" style="cursor: pointer;">
<img border="0" src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_cadastro.btnacao.value == '' ) { document.frm_cadastro.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" alt="Limpar campos" style="cursor: pointer;">
</center>
</form>


<br>

<?
$sql = "SELECT    tbl_grupo_empresa.grupo_empresa ,
				  tbl_grupo_empresa.nome_grupo    ,
				  tbl_grupo_empresa.descricao     ,
				  tbl_grupo_empresa.ativo
		FROM      tbl_grupo_empresa
		WHERE     tbl_grupo_empresa.fabrica = $login_fabrica
		ORDER BY  tbl_grupo_empresa.descricao ASC,tbl_grupo_empresa.ativo DESC";

$res = @pg_exec ($con,$sql);

if(@pg_numrows($res) > 0) {
	echo "<table width='500' border='0' cellpadding='2' cellspacing='1' class='titulo' align='center' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
	echo "<tr bgcolor='#D9E2EF'>";
	echo "<td colspan='3'><b>RELAÇÃO DOS GRUPOS CADASTRADOS</b></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$grupo_empresa = trim(pg_result($res,$i,grupo_empresa));
		$nome_grupo    = trim(pg_result($res,$i,nome_grupo));
		$descricao     = trim(pg_result($res,$i,descricao));
		$ativo         = trim(pg_result($res,$i,ativo));

		$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

		if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
		else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";

		echo "<tr bgcolor='$cor' class='Label'>";
		echo "<td align='left' width='100'>$nome_grupo</td>";
		echo "<td align='left'><a href='$PHP_SELF?grupo_empresa=$grupo_empresa'>$descricao</a></td>";
		echo "<td align='left' width='60'>$ativo</td>";
		echo "</tr>";
	}
	echo "</table>";
}
echo "<br>";

include "rodape.php";
?>