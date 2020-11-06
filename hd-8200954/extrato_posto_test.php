<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "autentica_usuario_financeiro.php";

if ($login_e_distribuidor == 't') {
	header ("Location: new_extrato_distribuidor.php");
	exit;
}


$msg_erro = trim($_GET['msg_erro']);
if (strlen($msg_erro)==0)
	$msg_erro = trim($_POST['msg_erro']);

if (strlen($msg_erro)==0){$msg_erro="";}

if ($msg_erro=="405"){
	$msg_erro = "Para ver a mão de obra, é necessário preencher as notas de devolução";
}

#########################################################################
#########################################################################
##    LIBRAR PARA O POSTO 5419 NO EXTRATO DO MES 8                     ##
##                        MOTORMAQ COMERCIO E REPRESENTACOES LTDA      ##
#########################################################################
#########################################################################
/*
ESTES POSTOS ACESSARA A TELA NOVA DE DEVOLUÇÃO

Martello – 2073 - 595
Penha – 80039 - 1537
Janaína – 80330 - 1773
Bertolucci - 80568 - 7080
Tecservi – 80459 - 5037
NL – 80636 - 13951
Telecontrol – 93509 - 4311
A.Carneiro – 1256 - 564
-----Gaslar – 24091 - 1008----- nao mais
Centerservice 80150 - 1623
Visiontec -  80200 - 1664
*/

/*
 Nipon –           80437  - 2506
MR –              80539  - 6458
Bom Jesus –       80002  - 1511
Eletro Center –  601049  - 1870
Multitécnica –    38086  - 1266
Central B & B –   80540  - 6591
Edivideo –        80462  - 5496
Maria Suzana –    80685  - 14296
Moacir Florêncio  80492  - 6140
Luiz Claudio –    32051  - 1161
JC & M –          80424  - 1962
*/

$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664', 10 => '595');

$postos_permitidos_novo = array(0 => 'LIXO', 1 => '2506', 2 => '6458', 3 => '1511', 4 => '1870', 5 => '1266', 6 => '6591', 7 => '5496', 8 => '14296', 9 => '6140', 10 => '1161', 11 => '1962');

$postos_permitidos_novo_new = array (0 => 'LIXO',1 => '708', 2 => '710 ', 3 => '14119', 4 => '898', 5 => '6379', 6 => '5024', 7 => '388', 8 => '2508', 9 => '1172', 10 => '1261', 11 => '19724', 12 => '1523', 13 => '1567', 14 => '1581', 15 => '1713', 16 => '1740', 17 => '1752', 18 => '1754', 19 => '1766', 20 => '115', 21 => '1799', 22 => '1806', 23 => '1814', 24 => '1891', 25 => '6432', 26 => '6916', 27 => '6917', 28 => '7245', 29 => '7256', 30 => '13850', 31 => '4044', 32 => '14182', 33 => '14297', 34 => '14282', 35 => '14260', 36 => '18941', 37 => '18967', 38 => '5419');

$extrato = (trim ($_POST['extrato']));

if (strlen ($extrato) > 0) {
	if ($login_fabrica==3){
		if ($extrato>144000){
			if (array_search($login_posto, $postos_permitidos)>0){ //verifica se o posto tem permissao
				header ("Location: extrato_posto_devolucao_lgr.php?extrato=$extrato");
				exit();
			}
		}
		if ($extrato>148811){
			if (array_search($login_posto, $postos_permitidos_novo)>0){ //verifica se o posto tem permissao
				header ("Location: extrato_posto_devolucao_lgr.php?extrato=$extrato");
				exit();
			}
		}
		if ($extrato>176484){
			if (array_search($login_posto, $postos_permitidos_novo_new)>0){ //verifica se o posto tem permissao
				header ("Location: extrato_posto_devolucao_lgr.php?extrato=$extrato");
				exit();
			}
		}
		if ($extrato>185731){# liberado para toda a rede Solicitado por Sergio Mauricio
			header ("Location: extrato_posto_devolucao_lgr.php?extrato=$extrato");
			exit();
		}
	}
	header ("Location: extrato_posto_devolucao.php?extrato=$extrato");
	exit();
}



$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
?>
<style>
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
</style>
<?
if(strlen($msg)>0){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg</td>";
	echo "</tr>";
	echo "</table><br>";
	echo "<a href='os_extrato_senha.php?acao=alterar'>Alterar senha</a>";
	echo "&nbsp;&nbsp;<a href='os_extrato_senha.php?acao=libera'>Liberar tela</a>";
}else{
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='os_extrato_senha.php?acao=inserir' >Esta area não está protegida por senha! <br>Para inserir senha para Restrição do Extrato, clique aqui e saiba mais! </a></td>";
	echo "</tr>";
	echo "</table><br>";
}
?>
<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?
	$cond = "";
	if($login_fabrica==3){
		//$cond = " AND tbl_extrato.extrato < 425280 ";
	}

	$sql = "SELECT  tbl_extrato.extrato                                            ,
					date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
					to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
			FROM    tbl_extrato
			WHERE   tbl_extrato.posto = $login_posto
			AND     tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.data_geracao >= '2005-03-30'
			$cond
			ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";
	//echo $sql;

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<form name='frm_extrato' method='post' action='$PHP_SELF'>";
	echo "<select name='extrato' onchange='javascript:frm_extrato.submit()'>\n";
	echo "<option value=''></option>\n";
	
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$aux_extrato = trim(pg_result($res,$x,extrato));
		$aux_data    = trim(pg_result($res,$x,data));
		$aux_extr    = trim(pg_result($res,$x,data_extrato));
		$aux_peri    = trim(pg_result($res,$x,periodo));
		
		if (1==2 AND $login_fabrica == 3 AND $aux_extr > "2005-11-01" AND $login_posto <> 1053 AND $login_posto <> 1789) {
			echo "<option value=''>Calculando</option>\n";
		}else{
			echo "<option value='$aux_extrato'>$aux_data</option>\n";
		}
	}
	
	echo "</select>\n";
	echo "</form>";
}

?>

<p><p>

<? include "rodape.php"; ?>
