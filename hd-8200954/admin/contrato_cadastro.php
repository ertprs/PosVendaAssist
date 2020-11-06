<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include 'funcoes.php';
$contrato = $_GET['contrato'];
if(strlen($contrato) == 0) $contrato = $_POST['contrato'];

if ($btnacao == "deletar" and strlen($contrato) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_contrato
			WHERE  tbl_contrato.fabrica = $login_fabrica
			AND    tbl_contrato.contrato   = $contrato;";
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
	if (strlen($_POST["numero_contrato"]) > 0) $aux_numero_contrato = "'" . trim($_POST["numero_contrato"]) . "'" ;
	else                                    $aux_numero_contrato = "null";
	if (strlen($_POST["descricao"]) > 0)         $aux_descricao         = "'". trim($_POST["descricao"]) ."'";
	else                                    $msg_erro         = "Favor informar o nome do contrato.";
	if (strlen($_POST["ativo"]) > 0)        $aux_ativo        = "'t'";
	else                                    $aux_ativo        = "'f'";

	if (strlen($_POST["codigo_representante"]) > 0) $aux_codigo_representante = "'" . trim($_POST["codigo_representante"]) . "'" ;

	if (strlen($_POST["canal_venda"]) > 0)  $aux_canal_venda  = trim($_POST["canal_venda"]) ;
	else                                    $aux_canal_venda = "null";

	$sql =	"SELECT representante,
					nome
			 FROM   tbl_representante
			 WHERE  codigo = $aux_codigo_representante;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$aux_representante = trim(pg_result($res,0,representante));
		$nome_representante= trim(pg_result($res,0,nome));
	}else {
		$aux_representante = " null ";
		$nome_representante= "";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($contrato) == 0) {
			$sql = "INSERT INTO tbl_contrato (
						fabrica             ,
						numero_contrato     ,
						descricao           ,
						representante       ,
						canal_venda         ,
						ativo
					) VALUES (
						$login_fabrica      ,
						$aux_numero_contrato,
						$aux_descricao      ,
						$aux_representante  ,
						$aux_canal_venda         ,
						$aux_ativo
					);";
		}else{
			###ALTERA REGISTRO
			$sql = "UPDATE  tbl_contrato SET
					numero_contrato = $aux_numero_contrato,
					descricao       = $aux_descricao,
					representante   = $aux_representante,
					canal_venda     = $aux_canal_venda,
					ativo           = $aux_ativo
				WHERE   tbl_contrato.fabrica    =	$login_fabrica
				AND     tbl_contrato.contrato   = $contrato;";
		}
		$res = @pg_exec ($con,$sql);
		//echo "upd: $sql";
		//print_r($_POST);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg=Contrato gravado com sucesso!");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if (strlen($contrato) > 0) {
	$sql =	"SELECT tbl_contrato.contrato    ,
				tbl_contrato.numero_contrato ,
				tbl_contrato.descricao       ,
				tbl_contrato.representante   ,
				tbl_contrato.canal_venda     ,
				tbl_contrato.ativo           ,
				tbl_representante.codigo as codigo_representante,
				tbl_representante.nome as nome_representante
		FROM      tbl_contrato
		LEFT JOIN      tbl_representante using(representante)
		WHERE     tbl_contrato.fabrica = $login_fabrica
		AND       tbl_contrato.contrato   = $contrato;";
	$res = @pg_exec ($con,$sql);


	if (pg_numrows($res) > 0) {
		$contrato        = trim(pg_result($res,0,contrato));
		$numero_contrato = trim(pg_result($res,0,numero_contrato));
		$descricao       = trim(pg_result($res,0,descricao));
		$representante   = trim(pg_result($res,0,representante));
		$canal_venda     = trim(pg_result($res,0,canal_venda));
		$codigo_representante= trim(pg_result($res,0,codigo_representante));
		$nome_representante= trim(pg_result($res,0,nome_representante));
		$ativo           = trim(pg_result($res,0,ativo));
	}
}

$layout_menu = "cadastro";
$title = "Cadastro de Contrato";
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
	.ok{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #0000bb;
	}
</style>

<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF;?>">
<input type="hidden" name="contrato" value="<? echo $contrato ?>">


<? if (strlen($_GET['msg']) > 0) { ?>
<table width="600" border="0" cellpadding="2" cellspacing="1" class="ok" align='center'>
	<tr>
		<td><?echo $msg;?></td>
	</tr>
</table>
<? } ?>

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
			<td align='left' colspan='4'><font size='2' color='#ffffff'>Cadastro de Contrato</font></td>
		</tr>
		<tr class='Label'>
			<td nowrap >Número do contrato</td>
			<td><input type="text" name="numero_contrato" value="<? echo $numero_contrato ?>" size="10" class="frm"></td>
			<td nowrap >Descrição do contrato</td>
			<td><input type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50" class="frm"></td>
		</tr>


		<tr class='Label'>
			<td nowrap >Código Representante</td>
			<td>
				<input type="text" name="codigo_representante" value="<? echo $codigo_representante?>" size="30" maxlength="50" class="frm">
			</td>
			<td nowrap >Canal de Venda</td>
			<td>
				<?
				$sql = "SELECT  canal_venda,
								codigo,
								descricao
						FROM    tbl_canal_venda
						ORDER BY codigo;";
				$res1 = @pg_exec ($con,$sql);

				if (pg_numrows($res1) > 0) {
					echo "<select name='canal_venda' >\n";
					echo "<option value=''></option>";

					for ($i = 0 ; $i < pg_numrows ($res1) ; $i++){
						$aux_canal_venda = trim(pg_result($res1,$i,canal_venda));
						$aux_codigo      = trim(pg_result($res1,$i,codigo));
						$aux_descricao   = trim(pg_result($res1,$i,descricao));

						echo "<option value='$aux_canal_venda'";
						if ($aux_canal_venda == $canal_venda) echo " selected";
						echo ">$aux_codigo - $aux_descricao</option>\n";
					}
					echo "</select>";
				}
				?>
			</td>
		</tr>
		<?if(strlen($nome_representante)>0){?>
			<tr class='Label'>
				<td nowrap >Nome representante</td>
				<td colspan = '3'>
				<? echo $nome_representante?>
				</td>
			</tr>
		<?}?>
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
$sql = "SELECT    tbl_contrato.contrato        ,
				  tbl_contrato.numero_contrato ,
				  tbl_contrato.descricao       ,
				  tbl_contrato.ativo
		FROM      tbl_contrato
		WHERE     tbl_contrato.fabrica = $login_fabrica
		ORDER BY  tbl_contrato.descricao ASC,tbl_contrato.ativo DESC";

$res = @pg_exec ($con,$sql);

if(@pg_numrows($res) > 0) {
	echo "<table width='500' border='0' cellpadding='2' cellspacing='1' class='titulo' align='center' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
	echo "<tr bgcolor='#D9E2EF'>";
	echo "<td colspan='3'><b>RELAÇÃO DOS CONTRATOS CADASTRADOS</b></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$contrato         = trim(pg_result($res,$i,contrato));
		$numero_contrato  = trim(pg_result($res,$i,numero_contrato));
		$descricao        = trim(pg_result($res,$i,descricao));
		$ativo            = trim(pg_result($res,$i,ativo));

		$cor = ($i % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

		if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
		else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";

		echo "<tr bgcolor='$cor' class='Label'>";
		echo "<td align='left' width='100'>$numero_contrato</td>";
		echo "<td align='left'><a href='$PHP_SELF?contrato=$contrato'>$descricao</a></td>";
		echo "<td align='left' width='60'>$ativo</td>";
		echo "</tr>";
	}
	echo "</table>";
}
echo "<br>";

include "rodape.php";
?>