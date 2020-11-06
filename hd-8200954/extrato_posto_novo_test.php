<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor == 't') {
	header ("Location: new_extrato_distribuidor.php");
	exit;
}


$sql = "SELECT senha_financeiro,tbl_posto.posto
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_posto_fabrica.posto = $login_posto
		AND senha_financeiro IS NOT NULL
		AND length(senha_financeiro) > 0";
$res = pg_exec ($con,$sql);


if (pg_numrows($res) > 0) {
	if($acessa_extrato=='SIM'){
		$msg="Area Restrita Para Pessoal Autorizado";
	}
	else{
		header ("Location: os_extrato_senha_financeiro.php");
		exit;
	}
}




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
NOVOS POSTOS 
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

/*
NOVISSIMOS POSTOS
 posto | codigo_posto |                    nome
-------+--------------+---------------------------------------------
   708 | 10484        | CEP CENTRO ELETRONICO PASQUAL LTDA
   710 | 10493        | CENTROLAR-CENTRO TEC.LAR AS.ELETROD.LTDA
 14119 | 17261        | ELETRICA MORO LTDA
   898 | 18465        | ELETROPI  ELETRONICA PIAUI LTDA
  6379 | 20136        | FERNANDO MENDES
  5024 | 21916        | GISELE S. ABREU - ME
   388 | 28009        | JOSE CARLOS DA SILVA COMERCIAL ME
  2508 | 30128        | LEOMAU COM.DE MAT.ELET.LTDA.
  1172 | 32490        | LUIZ AUGUSTO DE AVILA KRAUSE
  1261 | 36553        | MONTERRAZO COM. E SERVIÇOS LTDA
 19724 | 5485         | RR BOMB-AUT COM.MAQ.PECAS.ASSIST.TEC LTDA
  1523 | 80020        | ELETRONICA ROLANDIA LTDA
  1567 | 80076        | ELETRONICA MINAS GERAIS LTDA.
  1581 | 80093        | STILLGUACU COMPONENTES ELETRONICOS ME
  1713 | 80258        | SURNICHE & BARBOSA LTDA. - ME
  1740 | 80291        | MARIO LUIZ BARROS MANGAS-ME
  1752 | 80305        | NTEK MERCANTIL LTDA ME
  1754 | 80308        | ELETRONICA ZILLMER LTDA
  1766 | 80321        | CAMP TEC ASSISTENCIA E COMERCIO LTDA
   115 | 80339        | A.S.TEIXEIRA E CIA LTDA-ME
  1799 | 80359        | DARIO ANTONIO GRIBLER
  1806 | 80366        | ELETRONICA QUEOPS LTDA.
  1814 | 80376        | PC SERVICE INFORMATICA LTDA
  1891 | 80430        | ROGERIO DE SERVI FERRAZ
  6432 | 80533        | FKA PESSOA ME
  6916 | 80556        | MARCUS AURELIO PINHEIRO MENDES
  6917 | 80557        | PAINELTECK  ELETRONICA AUDIO VISUAL LTDA
  7245 | 80581        | LABORATÓRIO TV COR 2001 LTDA
  7256 | 80583        | ELETRÔNICA RADIAL LTDA
 13850 | 80612        | SECULOS COM. E REPARAÇÃO DE ELETRODOM. LTDA
  4044 | 80615        | ELETRO-ELETRONICA NEWS LTDA
 14182 | 80672        | FOCO LAZER COMERCIO E SERVIÇOS LTDA-ME
 14297 | 80687        | TN2 SOLUTION COM.SERV.EQUIP.ELET.LTDA ME
 14282 | 80697        | PLAYSOUND  RIO SERVIÇOS  ELETRONICOS LTDA
 14260 | 80710        | ORLANDO BENICIO TAVARES FILHO ME
 18941 | 80736        | CICERO MADUREIRA TAVARES & CIA LTDA
 18967 | 80746        | BG COMERCIO ASSIT.TEC.ELETRODOM.LTDA
*/

$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664', 10 => '595');

$postos_permitidos_novo = array(0 => 'LIXO', 1 => '2506', 2 => '6458', 3 => '1511', 4 => '1870', 5 => '1266', 6 => '6591', 7 => '5496', 8 => '14296', 9 => '6140', 10 => '1161', 11 => '1962');

$postos_permitidos_novo_new = array (0 => 'LIXO',1 => '708', 2 => '710 ', 3 => '14119', 4 => '898', 5 => '6379', 6 => '5024', 7 => '388', 8 => '2508', 9 => '1172', 10 => '1261', 11 => '19724', 12 => '1523', 13 => '1567', 14 => '1581', 15 => '1713', 16 => '1740', 17 => '1752', 18 => '1754', 19 => '1766', 20 => '115', 21 => '1799', 22 => '1806', 23 => '1814', 24 => '1891', 25 => '6432', 26 => '6916', 27 => '6917', 28 => '7245', 29 => '7256', 30 => '13850', 31 => '4044', 32 => '14182', 33 => '14297', 34 => '14282', 35 => '14260', 36 => '18941', 37 => '18967', 38 => '5419');



$extrato = (trim ($_POST['extrato']));

if (strlen ($extrato) > 0) {
	if ($extrato>144000){
		if (array_search($login_posto, $postos_permitidos)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_lgr_test_igor.php?extrato=$extrato");
			exit();
		}
	}
	if ($extrato>148811){
		if (array_search($login_posto, $postos_permitidos_novo)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_lgr_test_igor.php?extrato=$extrato");
			exit();
		}
	}
	if ($extrato>176484){
		if (array_search($login_posto, $postos_permitidos_novo_new)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_lgr_test_igor.php?extrato=$extrato");
			exit();
		}
	}
	if ($extrato>185731){# liberado para toda a rede Solicitado por Sergio Mauricio 31/08/2007 - Fabio
		header ("Location: extrato_posto_devolucao_lgr_test_igor.php?extrato=$extrato");
		exit();
	}
	header ("Location: extrato_posto_devolucao.php?extrato=$extrato");
	exit;
}



$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";
/*
if ($login_fabrica == 3 and $ip <> "201.76.80.178") {
echo "Tela em manutenção!";
exit;
}
*/
?>
<style type="text/css">

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.TituloConsulta {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #D9E2EF;
}
.Mensagem{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#D9E2EF;
	font-weight: bold;
}
.Erro{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>
<style>
.Mensagem{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#D9E2EF;
	font-weight: bold;
}
</style>
<?
if(strlen($msg)>0){
	echo "<span class='Mensagem'><img src='imagens/cadeado1.jpg' align='absmiddle'>$msg</span>";
}

?>

<br>

<table style='font-family: verdana; font-size: 10px; color:#A8A7AD' width='200' align='center'>
<tr>
<td><? echo"<a href='$PHP_SELF?acao=alterarsenha'>Alterar senha</a>"; ?></td>
<td><? echo"<a href='$PHP_SELF?acao=liberartela'>Liberar tela</a>"; ?></td>
</tr>
</table>

<?
$acao= $_GET['acao'];
$btn_alterar= $_POST['btn_alterar'];
if (strlen ($btn_alterar) > 0) {
$senha_nova= $_POST['senha_nova'];
$senha_nova2= $_POST['senha_nova2'];
	if($senha_nova == $senha_nova2){
		//faz o update para alterar senha
 	//fazer update setando a senha nova
 
		}else{
			echo "Senhas não conferem!";
	}

}

if($acao=='alterarsenha'){
echo "<FORM name='frm_alterar' METHOD='POST' ACTION='$PHP_SELF?acao=alterarsenha' align='center'>";
echo "<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>";
	echo "<tr >";
		echo "<td class='Titulo'>Alteração de Senha do Usuário</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td bgcolor='#F3F8FE'>";
			echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='2' CLASS='table_line' bgcolor='#F3F8FE'>";
				echo "<tr class='Conteudo' >";
					echo "<TD colspan='4' style='text-align: center;'>";
						echo "<br>Por favor entrar com a nova senha.";
					echo "</TD>";
				echo "</tr>";
				echo "<TR width='100%'  >";
					echo "<td colspan='2'  align='right' height='40'>Senha:&nbsp;</td>";
					echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' ></td>";
				echo "</tr>";
				echo "<TR width='100%'  >";
					echo "<td colspan='2'  align='right' height='40'>Repetir Senha:&nbsp;</td>";
					echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' ></td>";
				echo "</tr>";
				echo "<tr class='Conteudo' >";
					echo "<TD colspan='4' style='text-align: center;'>";
						echo "<br><input type='submit' name='btn_alterar' value='Alterar Senha'>";
					echo "</TD>";
				echo "</tr>";
			echo "</table>";
		echo "</td>";
	echo "</tr>";
echo "</table>";
echo "</form>";

//alterar senha
}
elseif($acao=='liberartela'){
//alterar senha
echo "<script>alert('Acesso liberado!'); history.go(-1);</script>";
//fazer update setando senha financeiro com null		 
}else{



?>

<p>
<center>
<font size='+1' face='arial'>Data do Extrato</font>
<?
	$sql = "SELECT  tbl_extrato.extrato                                            ,
					date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
					to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
			FROM    tbl_extrato
			WHERE   tbl_extrato.posto = $login_posto
			AND     tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.data_geracao >= '2005-03-30'
			ORDER   BY  to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";

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
}
?>

<p><p>

<? include "rodape.php"; ?>