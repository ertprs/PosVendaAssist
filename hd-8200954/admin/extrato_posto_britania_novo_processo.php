<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

$extrato = (trim ($_POST['extrato']));
$posto = (trim ($_POST['posto']));

$somente_consulta= trim ($_GET['somente_consulta']);
if(strlen($somente_consulta)==0){
	$somente_consulta= trim ($_POST['somente_consulta']);
}

if (strlen ($extrato) > 0 ) {
	header ("Location: extrato_posto_devolucao_britania_lgr_novo_processo.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta");
	exit;
}


if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["codigo_posto"]) > 0) $codigo_posto = $_POST["codigo_posto"];
if (strlen($_GET["codigo_posto"])  > 0) $codigo_posto = $_GET["codigo_posto"];

$nome   = $_POST['nome'];
if (strlen($_GET['nome']) > 0) $nome = $_GET['nome'];

if($btnacao){

	
	$sql = "SELECT posto FROM tbl_posto_fabrica where fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) == 0){
		$msg_erro = "Posto não Encontrado";
	}

}

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS DO POSTO";

include "cabecalho.php";
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


.msg_erro{
	background-color:#FF0000;
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
</style>
<? include "javascript_pesquisas.php"; ?>
<script language="javascript" src="js/jquery.js"></script>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>




<p>
<center>
<?



?>
<table class='formulario' width='700' cellspacing='0'  cellpadding='0' align='center'>
	<? if(strlen($msg_erro) > 0){ ?>
		<tr class='msg_erro'><td><? echo $msg_erro; ?></td></tr>
	<? } ?>
	<tr >
		<td class="titulo_tabela" >Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td >
			<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='0' class='formulario'>
				<FORM METHOD='GET' NAME='frm_extrato' ACTION="<?=$PHP_SELF?>">
					<tr><td colspan='2'>&nbsp;</td></tr>
					<tr align='left'>
						<td align='right' style='padding-left:100px;'>Cod. Posto&nbsp;</td>
						<td><input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.nome, 'codigo')"></td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Nome do Posto&nbsp;</td>
						<td>
							<input type="text" name="nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.nome, 'nome');" <? } ?> value="<?echo $nome?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.nome, 'nome')">
						</td>
					</tr>
					<tr><td colspan='2' bgcolor="#D9E2EF">&nbsp;
					<?
						if(strlen($somente_consulta)> 0){
							echo "<INPUT TYPE='hidden' name='somente_consulta'value='sim' >";
						}
					?></td></tr>

					<tr><td colspan='2' align='center'><INPUT TYPE="submit" name='btnacao'value="Pesquisar" ></td></tr>
				</form>
			</TABLE>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>
<?
if (strlen ($codigo_posto) > 0 and strlen($msg_erro)==0) {

	echo "&nbsp;</td></tr>";
	echo "<tr><td bgcolor='#D9E2EF'>";
	
	$sql = "SELECT * FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto =  tbl_posto.posto
			WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto' 
			AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$posto = trim(pg_result($res,0,posto));

	$sql = "SELECT  tbl_extrato.extrato                                                ,
					date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato    ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data            ,
					to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo 
			FROM    tbl_extrato
			WHERE   tbl_extrato.posto = '$posto'
			AND     tbl_extrato.fabrica = '$login_fabrica'
			/* AND     tbl_extrato.aprovado IS NOT NULL */
			AND     tbl_extrato.data_geracao >= '2005-03-30'
			ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";
	$res = pg_exec ($con,$sql);
//echo $sql;
	if (pg_numrows($res) > 0) {
		echo "<center><font size='+1' face='arial'>Data do Extrato </font>";
		echo "<form name='frm_extrato_data' method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='posto' value='$posto'>";
		echo "<select name='extrato' onchange='javascript:frm_extrato_data.submit()'>\n";
		echo "<option value=''></option>\n";
		
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$aux_extrato = trim(pg_result($res,$x,extrato));
			$aux_data    = trim(pg_result($res,$x,data));
			$aux_extr    = trim(pg_result($res,$x,data_extrato));
			$aux_peri    = trim(pg_result($res,$x,periodo));
			
			if (1==2 AND $login_fabrica == 3 AND $aux_extr > "2005-11-01" AND $posto <> 1053 AND $posto <> 1789) {
				echo "<option value=''>Calculando</option>\n";
			}else{
				echo "<option value='$aux_extrato'>$aux_data</option>\n";
			}
		}
		echo $posto;
		echo "</select>\n";
		if(strlen($somente_consulta)> 0){
			echo "<INPUT TYPE='hidden' name='somente_consulta'value='sim' >";
		}
		echo "</form>";
	}
	else{
		echo "<center><font size='+1' face='arial'>Nemhum Resultado Encontrado </font>";
	}
}
?>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>&nbsp;</td></tr>

</table>
<p><p>

<? include "rodape.php"; ?>
