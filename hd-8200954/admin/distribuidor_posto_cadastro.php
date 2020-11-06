<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {

	$distribuidor = trim($_POST['distribuidor']);

	if (strlen($distribuidor) > 0) {
		// grava posto_fabrica
		if (strlen($msg_erro) == 0){

			$msg_confirma = "";

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$check = $_POST['check_' . $i];
				$posto = $_POST['posto_' . $i];
				if (strlen($check) == 0 AND strlen($posto) > 0)
					$xdistribuidor = 'null';
				else
					$xdistribuidor = "'".$distribuidor."'";

				if(strlen($posto) > 0) {
					$sql = "SELECT  tbl_posto_fabrica.*,
									tbl_posto.nome
							FROM    tbl_posto_fabrica
							JOIN    tbl_posto USING(posto)
							WHERE   tbl_posto_fabrica.posto   = $posto
							AND     tbl_posto_fabrica.fabrica = $login_fabrica 
							AND     tbl_posto_fabrica.distribuidor IS NOT NULL
							AND     tbl_posto_fabrica.distribuidor <> $distribuidor";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (pg_numrows($res) > 0) 
						$msg_confirma .= "O posto ".pg_result($res,0,nome)." estava cadastrado em outro distribuidor<br>";
				}

				if(strlen($msg_erro) == 0) {
					$sql = "UPDATE tbl_posto_fabrica SET
								distribuidor = $xdistribuidor
							WHERE  posto   = $posto
							AND    fabrica = $login_fabrica ";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
		
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg_confirma = "Operação realizada com sucesso!!!";

			header ("Location: $PHP_SELF?msg_confirma=$msg_confirma");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


#--------- Pedido via Distribuidor --------------
$pedido_via_distribuidor = $_GET['pedido_via_distribuidor'];
if (strlen ($pedido_via_distribuidor) > 0) {
	$sql = "UPDATE tbl_posto_fabrica SET pedido_via_distribuidor = '$pedido_via_distribuidor'
			WHERE  tbl_posto_fabrica.distribuidor = $posto
			AND    tbl_posto_fabrica.fabrica      = $login_fabrica";
	$res = pg_exec ($con,$sql);
}



$visual_black = "manutencao-admin";

$title       = "Cadastro de Distribuidor e seus Postos Autorizados";
$cabecalho   = "Cadastro de Distribuidor e seus Postos Autorizados";
$layout_menu = "cadastro";
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
	text-align: center;
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
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} 
//echo $msg_debug;
?> 
<p>

<table align='center' border='0'>
<form name='frm_posto1' method='post' action='<? echo $PHP_SELF ?>'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>Selecione o distribuidor.</font>
<?
	$sql = "SELECT	tbl_posto.posto               ,
					tbl_posto.nome                ,
					tbl_posto_fabrica.codigo_posto
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN	tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE	tbl_posto_fabrica.fabrica   = $login_fabrica
			AND		tbl_tipo_posto.distribuidor is true	";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res) > 0){
		echo "<select name='distribuidor'>\n";
		echo "<option selected></option>\n";
		for($i = 0; $i < pg_numrows($res); $i++){
			echo "<option value='".pg_result($res,$i,posto)."'>".pg_result($res,$i,codigo_posto)." || ".pg_result($res,$i,nome)."</option>\n";
		}
		echo "</select>\n";
	}
?>
		<input type='hidden' name='btn_acao' value=''>
		<img src='imagens_admin/btn_continuar.gif' style='cursor: pointer;' onclick="javascript: if (document.frm_posto1.btn_acao.value == '' ) { document.frm_posto1.btn_acao.value='continuar' ; document.frm_posto1.submit() } else { alert ('Aguarde submissão') }" ALT='Continuar' border='0'>
	</td>
</tr>
</form>
</table>

<? 
	if($msg_confirma){
?>
<p>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_confirma; ?>
	</td>
</tr>
</table>
<?
	}
?>

<?
// se não, se for 2º passo, exibe os dados do distribuidor e seleciona os postos
if (strlen($distribuidor) > 0){

	$sql = "SELECT  tbl_posto_fabrica.codigo_posto        ,
					tbl_posto_fabrica.obs                 ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.endereco                    ,
					tbl_posto.numero                      ,
					tbl_posto.complemento                 ,
					tbl_posto.bairro                      ,
					tbl_posto.cep                         ,
					tbl_posto.cidade                      ,
					tbl_posto.estado                      ,
					tbl_posto.email                       ,
					tbl_posto.fone                        ,
					tbl_posto.fax                         ,
					tbl_posto.contato                     ,
					tbl_posto.nome_fantasia
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $distribuidor ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$codigo        = trim(pg_result($res,0,codigo_posto));
		$obs           = trim(pg_result($res,0,obs));
		$nome          = trim(pg_result($res,0,nome));
		$cnpj          = trim(pg_result($res,0,cnpj));
		if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		$ie            = trim(pg_result($res,0,ie));
		$endereco      = trim(pg_result($res,0,endereco));
		$endereco      = str_replace("\"","",$endereco);
		$numero        = trim(pg_result($res,0,numero));
		$complemento   = trim(pg_result($res,0,complemento));
		$bairro        = trim(pg_result($res,0,bairro));
		$cep           = trim(pg_result($res,0,cep));
		$cidade        = trim(pg_result($res,0,cidade));
		$estado        = trim(pg_result($res,0,estado));
		$email         = trim(pg_result($res,0,email));
		$fone          = trim(pg_result($res,0,fone));
		$fax           = trim(pg_result($res,0,fax));
		$contato       = trim(pg_result($res,0,contato));
		$nome_fantasia = trim(pg_result($res,0,nome_fantasia));
	}
?>
<br><br>
<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="0">
	<tr>
		<td colspan="5">
			<img src="imagens/cab_informacoescadastrais.gif">
		</td>
	</tr>
	<tr class="menu_top">
		<td>CNPJ/CPF</td>
		<td>I.E.</td>
		<td>RAZÃO SOCIAL</td>
		<td>NOME FANTASIA</td>
	</tr>
	<tr class="table_line">
		<td><? echo $cnpj ?></td>
		<td><? echo $ie ?></td>
		<td><? echo $nome ?></td>
		<td><? echo $nome_fantasia ?></td>
	</tr>
	<tr class="menu_top">
		<td>FONE</td>
		<td>FAX</td>
		<td>E-MAIL</td>
		<td>CONTATO</td>
	</tr>
	<tr class="table_line">
		<td><? echo $fone ?></td>
		<td><? echo $fax ?></td>
		<td><? echo $email ?></td>
		<td><? echo $contato ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">ENDEREÇO</td>
		<td>NÚMERO</td>
		<td>COMPLEMENTO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $endereco ?></td>
		<td><? echo $numero ?></td>
		<td><? echo $complemento ?></td>
	</tr>
	<tr class="menu_top">
		<td>BAIRRO</td>
		<td>CEP</td>
		<td>CIDADE</td>
		<td>ESTADO</td>
	</tr>
	<tr class="table_line">
		<td><? echo $bairro ?></td>
		<td><? echo $cep ?></td>
		<td><? echo $cidade ?></td>
		<td><? echo $estado ?></td>
	</tr>
</table>

<p>

<?
$result = pg_exec ($con,"SELECT *
	FROM tbl_posto_fabrica
	JOIN tbl_tipo_posto USING (tipo_posto)
	WHERE tbl_posto_fabrica.posto = $distribuidor
	AND   tbl_posto_fabrica.fabrica = $login_fabrica
	AND   tbl_tipo_posto.distribuidor IS TRUE");
if (pg_numrows ($result) > 0) {
	$result = pg_exec ($con,"SELECT pedido_via_distribuidor 
		FROM tbl_posto_fabrica 
		WHERE tbl_posto_fabrica.posto = $distribuidor
		AND   tbl_posto_fabrica.fabrica = $login_fabrica");
	if (pg_result ($result,0,0) == 't') {
		echo "<b>Os pedidos dos postos deste distribuidor não vão para a fábrica, e sim para o distribuidor</b>";
		echo "<br>";
		echo "<a href='$PHP_SELF?distribuidor=$distribuidor&pedido_via_distribuidor=f'>Clique aqui</a> para que os postos possam fazer pedidos diretos para a fábrica";
	}else{
		echo "<b>Os postos deste distribuidor podem fazer pedidos direto para a fábrica</b>";
		echo "<br>";
		echo "<a href='$PHP_SELF?distribuidor=$distribuidor&pedido_via_distribuidor=t'>Clique aqui</a> para fazer com que os pedidos fiquem no distribuidor";
	}
}
?>

<form name='frm_posto' method='post' action='<? echo $PHP_SELF ?>'>
<input type='hidden' name='distribuidor' value='<? echo $distribuidor ?>'>
<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="2">
	<tr class="menu_top">
		<td>POSTOS CREDENCIADOS</td>
	</tr>
	
	<?
	$sql = "SELECT 	tbl_posto.posto               ,
					tbl_posto.nome                ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_posto_fabrica.credenciamento
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica USING(posto)
			WHERE   tbl_posto_fabrica.fabrica        = $login_fabrica 
			AND     tbl_posto.estado                 = '$estado'
			AND     tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND     tbl_posto_fabrica.distribuidor NOTNULL
			ORDER BY tbl_posto_fabrica.credenciamento, lpad(tbl_posto_fabrica.codigo_posto,10,'0')";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$y=1;
		for($i=0; $i<pg_numrows($res); $i++){
			echo "<tr>\n";
			echo "<td align='left'>\n";
			
			$codigo_posto = trim(pg_result($res,$i,codigo_posto));
			// completa o campo alinhando
			$codigo_posto = str_replace(" ", "&nbsp;", str_pad($codigo_posto,8,' ',STR_PAD_LEFT)); 
			$posto        = trim(pg_result($res,$i,posto));
			$nome         = trim(pg_result($res,$i,nome));
			$distribuidor_cadastrado = trim(pg_result($res,$i,distribuidor));
			$credenciamento = trim(pg_result($res,$i,credenciamento));
			
			echo "<input type='checkbox' name='check_$i' value='t' style='background-color:$bg_posto[$i];' ";
			if ($distribuidor_cadastrado == $distribuidor) echo " checked ";
			echo ">$codigo_posto - $nome<br>\n";
			echo "<input type='hidden' name='posto_$i' value='$posto'>\n";
			echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
			
			echo "</td>\n";
			
			echo "</tr>\n";
		} // fim do for
		echo "<input type='hidden' name='qtde_item' value='$i'>\n";
	} // fim do if

?>

</table>

<br><br>

<center>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<!-- img src='imagens_admin/btn_apagar.gif' style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { if(confirm('Deseja realmente DESCREDENCIAR este POSTO?') == true) { document.frm_posto.btn_acao.value='descredenciar'; document.frm_posto.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Serviço" border='0' -->
<img src="imagens_admin/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
</center>
<?
// fecha
}
?>
<p>

<? include "rodape.php"; ?>