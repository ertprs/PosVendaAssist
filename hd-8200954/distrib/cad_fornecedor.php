<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";
$msg_ok = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];
if (strlen($_POST['btn_continua']) > 0) $btn_continua = $_POST['btn_continua'];
if ($btn_continua == "continua") {
	if (strlen($_POST['posto_codigo']) > 0){
		$posto_codigo = $_POST['posto_codigo'];
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$posto_codigo' and fabrica = 10;";
		$res = pg_exec($con,$sql);
		if(pg_numrows ($res) > 0){
			$posto             = trim(pg_result($res,0,posto));
		}
	}
}

if ($btn_acao == "gravar") {
	$posto              = trim($_POST['posto']);
	$fornecedor_distrib = trim($_POST['fornecedor_distrib']);
	$fornecedor_distrib_fabrica = trim($_POST['fornecedor_distrib_fabrica']);
	if(strlen($fornecedor_distrib_fabrica)==0){
		$msg_erro = "Favor escolher a Fábrica em que será dado a baixa dos pedidos";
	}
	if(strlen($msg_erro)==0){
		$sql = "SELECT posto FROM tbl_posto_extra WHERE posto = $posto;";
		$res = pg_exec($con,$sql);
		if(pg_numrows ($res) > 0){
			$sql = "UPDATE tbl_posto_extra SET
						fornecedor_distrib           = '$fornecedor_distrib',
						fornecedor_distrib_fabrica   = '$fornecedor_distrib_fabrica'
						WHERE tbl_posto_extra.posto  = $posto";
			$res = pg_exec($con,$sql);
			if (pg_errormessage ($con) > 0){
				$msg_erro = pg_errormessage ($con);
			}else{
				if($fornecedor_distrib == 't'){
					$msg_ok   = "Este posto foi marcado para ser fornecedor do DISTRIB";
				}else{
					$msg_ok   = "Este posto foi desmarcado para ser fornecedor do DISTRIB";
				}
			}
		}else{
			$sql = "INSERT tbl_posto_extra 
						(posto,fornecedor_distrib) values ($posto,TRUE);";
			$res = pg_exec($con,$sql);  
			if (pg_errormessage ($con) > 0){
				$msg_erro = pg_errormessage ($con);
			}else{
				$msg_ok   = "Este posto foi marcado para ser fornecedor do DISTRIB";
			}
		}
	}
}

#-------------------- Pesquisa Posto -----------------
if(strlen($posto)> 0){
	$sql = "SELECT  tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.nome                        ,
					tbl_posto.endereco                    ,
					tbl_posto.numero                      ,
					tbl_posto.complemento                 ,
					tbl_posto.bairro                      ,
					tbl_posto.cep                         ,
					tbl_posto.cidade                      ,
					tbl_posto.estado                      ,
					tbl_posto.fone                        ,
					tbl_posto.fax                         ,
					tbl_posto.contato                     ,
					tbl_posto.capital_interior            ,
					tbl_posto.nome_fantasia               ,
					tbl_posto.email                       ,
					tbl_posto_extra.fornecedor_distrib    ,
					tbl_posto_extra.fornecedor_distrib_fabrica
			FROM    tbl_posto
			LEFT JOIN tbl_posto_extra on tbl_posto_extra.posto = tbl_posto.posto
			WHERE   tbl_posto.posto = $posto;";
	$res = pg_exec ($con,$sql);
		
	if (@pg_numrows ($res) > 0) {
		$cnpj             = trim(pg_result($res,0,cnpj));
		if (strlen($cnpj) == 14) {
			$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		}
		if (strlen($cnpj) == 11) {
			$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		}
		$ie               = trim(pg_result($res,0,ie));
		$nome             = trim(pg_result($res,0,nome));
		$endereco         = trim(pg_result($res,0,endereco));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_result($res,0,numero));
		$complemento      = trim(pg_result($res,0,complemento));
		$bairro           = trim(pg_result($res,0,bairro));
		$cep              = trim(pg_result($res,0,cep));
		$cidade           = trim(pg_result($res,0,cidade));
		$estado           = trim(pg_result($res,0,estado));
		$fone             = trim(pg_result($res,0,fone));
		$fax              = trim(pg_result($res,0,fax));
		$contato          = trim(pg_result($res,0,contato));
		$capital_interior = trim(pg_result($res,0,capital_interior));
		$nome_fantasia    = trim(pg_result($res,0,nome_fantasia));
		$email            = trim(pg_result($res,0,email));
		$fornecedor_distrib = pg_result($res,0,fornecedor_distrib);
		$fornecedor_distrib_fabrica = pg_result($res,0,fornecedor_distrib_fabrica);
	}
}
$title = "Cadastro de Fornecedores para o DISTRIB";
include 'menu.php';

?>

<style type="text/css">
.text_curto {
	text-align: center;
	font-weight: bold;
	color: #000;
	background-color: #FF6666;
}
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

<script language='javascript'>
function fnc_pesquisa_posto(campo, campo2, tipo, fabrica) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_distrib.php?campo=" + xcampo.value + "&tipo=" + tipo + "&fabrica=" + fabrica ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<font color='RED'><b>$msg_erro</b></font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
if (strlen ($msg_ok) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<font color='GREEN'><b>$msg_ok</b></font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<p>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="6"class="menu_top">
			<font color='#36425C'><? echo "INFORMAÇÃO CADASTRAL";?>
		</td>
	</tr>
	<tr class="menu_top">
		<td>CNPJ</td>
		<td>Inscrição Estadual</td>
		<td>Fone</td>
		<td>Fax</td>
		<td>Contato</td>
	</tr>
	<tr class="table_line">
		<td><?echo $cnpj ?></td>
		<td><? echo $ie ?></td>
		<td><? echo $fone ?></td>
		<td><? echo $fax ?></td>
		<td><? echo $contato ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">Código</td>
		<td colspan="4">Razão Social</td>
	</tr>
	<tr class="table_line">
		
		<td colspan="2">
			<input type='hidden' name='posto' value='<? echo $posto;?>'>
			<? echo $posto ?>
		</td>
		<td colspan="3"><? echo $nome ?></td>
	</tr>
</table>

<br>

<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="2">Endereço</td>
		<td>Número</td>
		<td colspan="2">Complemento</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $endereco ?></td>
		<td><? echo $numero ?></td>
		<td colspan="2"><? echo $complemento ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">Bairro</td>
		<td>CEP</td>
		<td>Cidade</td>
		<td>Estado</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $bairro ?></td>
		<td><? echo $cep ?></td>
		<td><? echo $cidade ?></td>
		<td><? echo $estado ?></td>
	</tr>
</table>
<br>
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>Nome Fantasia</td>
		<td>Email</td>
		<td>Capital Interior</td>
	</tr>
	<tr class="table_line">
		<td><? echo $nome_fantasia ?></td>
		<td><? echo $email ?></td>
		<td><? echo $capital_interior ?></td>
	</tr>
</table>

<p>
<BR>
<? if(strlen($posto)>0){ ?>
<!-- ============================ Botoes de Acao ========================= -->
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td nowrap  class='nomes' align='center'>Posto é fornecedor:
			<label name='fornecedor_distrib' style='padding-left:30px'>Sim</label>
			<input type='radio' name='fornecedor_distrib' value='t' <?if ($fornecedor_distrib=='t') echo "checked"?>>
			<label name='fornecedor_distrib' style='padding-left:30px'>Não</label>
			<input type='radio' name='fornecedor_distrib' value='f' <?if ($fornecedor_distrib=='f') echo "checked"?>>
		</td>
		<td width="250">Fabrica
			<select name="fornecedor_distrib_fabrica" id="fornecedor_distrib_fabrica" class="fabrica">
				<option value="">Selecione uma Fabrica</option>
				<?php 
				$sql = "select fabrica,nome from tbl_fabrica where fabrica in ($telecontrol_distrib,11,172) order by nome";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0) {
					for($i=0;$i<pg_numrows($res);$i++){
						$cod_fabrica 	= pg_result($res,$i,fabrica);
						$nome_fabrica 	= pg_result($res,$i,nome);
						?>
						<option value="<?php echo $cod_fabrica;?>"
						<? if($fornecedor_distrib_fabrica==$cod_fabrica) { echo " SELECTED "; } ?>
						><?php echo $nome_fabrica;?></option>
						<?php 
					}
				}
				?>
			</select>
		</td>

	</tr>
	<tr>
		<td colspan='2'>
			<center>
			<INPUT TYPE="hidden" name="btn_acao" value="">
			<img src="../imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
			</center>
		</td>
	<tr>
</table>

<?}else{?>
	<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
		<tr class="menu_top">
			<td colspan='2'>Escolha o posto da fabrica TELECONTROL que deseja pesquisar para transformar em fornecedor</td>
		</tr>
		<tr>
			<td nowrap>
				<b class='nomes'>Código</b>
				<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" onkeypress="handleEnter(event,'codigo')">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_posto.posto_codigo,document.frm_posto.posto_nome,'codigo',10)">
			</td>
			<td nowrap>
				<b class='nomes'>Nome do Posto</b>
				<input class="frm" type="text" name="posto_nome" size="40" value="<? echo $posto_nome ?>" onKeyPress="handleEnter(event,'nome')">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.posto_codigo,document.frm_posto.posto_nome,'nome',10)" style="cursor:pointer;">
			</td>
		</tr>
	<tr>
		<td colspan='2' align='center'>
			<INPUT TYPE="hidden" name="btn_continua" value="">
			<img src="../imagens/btn_continuar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_continua.value == '' ) { document.frm_posto.btn_continua.value='continua' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Continua" border='0'>
		</td>
	<tr>
	</table>
<?}?>

</form>
<p>

<? include "rodape.php"; ?>
