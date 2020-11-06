<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";

$extrato = (trim ($_POST['extrato']));
$posto   = (trim ($_POST['posto']));

$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664', 10 => '595');

$postos_permitidos_novo = array(0 => 'LIXO', 1 => '2506', 2 => '6458', 3 => '1511', 4 => '1870', 5 => '1266', 6 => '6591', 7 => '5496', 8 => '14296', 9 => '6140', 10 => '1161', 11 => '1962');

$postos_permitidos_novo_new = array (0 => 'LIXO',1 => '708', 2 => '710 ', 3 => '14119', 4 => '898', 5 => '6379', 6 => '5024', 7 => '388', 8 => '2508', 9 => '1172', 10 => '1261', 11 => '19724', 12 => '1523', 13 => '1567', 14 => '1581', 15 => '1713', 16 => '1740', 17 => '1752', 18 => '1754', 19 => '1766', 20 => '115', 21 => '1799', 22 => '1806', 23 => '1814', 24 => '1891', 25 => '6432', 26 => '6916', 27 => '6917', 28 => '7245', 29 => '7256', 30 => '13850', 31 => '4044', 32 => '14182', 33 => '14297', 34 => '14282', 35 => '14260', 36 => '18941', 37 => '18967', 38 => '5419');

if (strlen ($extrato) > 0) {

	if ($extrato>144000){
		if (array_search($posto, $postos_permitidos)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_lgr_itens.php?extrato=$extrato&posto=$posto");
			exit();
		}
	}
	if ($extrato>148811){
		if (array_search($posto, $postos_permitidos_novo)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_lgr_itens.php?extrato=$extrato&posto=$posto");
			exit();
		}
	}
	if ($extrato>176484){
		if (array_search($posto, $postos_permitidos_novo_new)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_lgr_itens.php?extrato=$extrato&posto=$posto");
			exit();
		}
	}
	if ($extrato>185731){
		header ("Location: extrato_posto_devolucao_lgr_itens.php?extrato=$extrato");
		exit();
	}

	header ("Location: manutencao_logistica_reversa2.php?extrato=$extrato&posto=$posto");
	exit;
}


if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["codigo_posto"]) > 0) $codigo_posto = $_POST["codigo_posto"];
if (strlen($_GET["codigo_posto"])  > 0) $codigo_posto = $_GET["codigo_posto"];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];

$msg_erro = "";

if ($_GET['btnacao'] == 'Pesquisar') 
	if( empty($_GET['posto_nome']) && empty ($_GET['codigo_posto']) )
		$msg_erro = 'Escolha o Posto que deseja Pesquisar';


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
	font: bold 14px "Arial";
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
.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>
<? include "javascript_pesquisas.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<center>
<? if(!empty($msg_erro)) { ?>
<div class="msg_erro" style="width:700px;margin:auto;"><?=$msg_erro?></div>
<? } ?>
<table width='700' cellspacing='0'  cellpadding='0' align='center' class='formulario'>
	
	<tr >
		<td class="titulo_tabela" >Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td>
			<TABLE width='100%' align='center' border='0' cellspacing='1' cellpadding='0' class="formulario">
				<FORM METHOD='GET' NAME='frm_extrato' ACTION="<?=$PHP_SELF?>">
					<tr><td colspan='2' bgcolor="#D9E2EF">&nbsp;</td></tr>
					<tr>
						<td width="30%">&nbsp;</td>
						<td>
							<table>
								<tr>
									<td>
										Posto<br />
										<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
										<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'codigo')">
									</td>
									<td>
										Nome do Posto<br />
										<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
										<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.posto_nome, 'nome')">
									</td>
								</tr>
							</table>
							
					</tr>
					<tr><td colspan='2'>&nbsp;</td></tr>
					<tr><td colspan='2' align='center'><INPUT TYPE="submit" name='btnacao'value="Pesquisar" ></td></tr>
				</form>
			</TABLE>
		</td>
	</tr>
	<tr><td>
<?
if (strlen ($codigo_posto) > 0 && empty($msg_erro)) {

	echo "<hr /></td></tr>";
	
		$sql = "SELECT tbl_posto.posto FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto =  tbl_posto.posto
			WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'
			AND fabrica = $login_fabrica";

	$res = pg_query ($con,$sql);
	$posto = trim(pg_result($res,0,posto));

	$sql = "SELECT  tbl_extrato.extrato                                                ,
					date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato    ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data            ,
					to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo 
			FROM    tbl_extrato
			WHERE   tbl_extrato.posto = $posto
			AND     tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.aprovado IS NOT NULL
			AND     tbl_extrato.data_geracao >= '2005-03-30'
			ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";
	$res = pg_exec ($con,$sql);
	echo "<tr><td align='center'>";
	if (pg_numrows($res) > 0) {
		echo "Data do Extrato";
		echo "<form name='frm_extrato_data' method='post' action='$PHP_SELF'>";
		echo "<input type='hidden' name='posto' value='$posto'>";
		echo "<select name='extrato' onchange='javascript:frm_extrato_data.submit()' class='frm'>\n";
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
		echo "</form>";
	}
	else{
		echo "<center>Posto não Encontrado</center>";
	}
	
}
?>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>&nbsp;</td></tr>

</table>
<p><p>

<? include "rodape.php"; ?>
