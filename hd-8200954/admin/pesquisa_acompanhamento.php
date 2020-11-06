<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

include 'funcoes.php';

$title = "Pesquisa Acompanhamento";

$estado  = str_replace ("'","",$estado);
$estado  = strtoupper($_GET['estado']);
$cidade  = strtoupper($_GET['cidade']);
$join = "";

$cond_1 = " 1=1 ";
if(strlen($cidade)>0){
	$cond_1 = " tbl_posto.cidade ILIKE '%$cidade%' ";
}

$posto = $_GET['posto'];
if(strlen($posto) == 0){
	$posto = $_POST['posto'];
}

$btn_acao = $_POST['btn_acao'];

if($btn_acao == 'gravar'){

	$posto = $_POST['posto'];
	if(strlen($posto) == 0){
		$posto = $_GET['posto'];
	}

	$contato = $_POST['contato'];
	if(strlen($contato) == 0){
		$msg_erro = "Digite o contato!";
	}

	$linha_atende = trim($_POST['linha_atendimento']);
	if(strlen($linha_atende) == 0){
		$msg_erro = "Digite a linha de atendimento!";
	}

	$data_pesquisa = $_POST['data_pesquisa'];
	if(strlen($data_pesquisa) == 0){
		$msg_erro = "Digite a data!";
	}else{
		$aux_data_pesquisa = fnc_formata_data_pg($data_pesquisa);
	}

	if (strlen ($aux_data_pesquisa) <> 12) {
		$msg_erro = " Digite a data de abertura da OS.";
	}else{
		$aux_data_pesquisa = str_replace("'","",$aux_data_pesquisa);
	}


	if(strlen($msg_erro) == 0){

		$sql = "INSERT INTO tbl_posto_pesquisa ( posto, data,admin, contato, linha_atende) values ($posto,'$aux_data_pesquisa',$login_admin,'$contato', '$linha_atende');";
		$res = pg_exec($con,$sql);

		$msg_erro = pg_errormessage($con);

		$res = pg_exec ($con,"SELECT CURRVAL ('seq_posto_pesquisa')");
		$posto_pesquisa  = pg_result ($res,0,0);

		if(strlen($msg_erro) == 0){
			$sql = "SELECT pesquisa_posto, titulo FROM tbl_pesquisa_posto WHERE fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) > 0){
				for($i=1;$i<pg_numrows($res) + 1;$i++){
					$aux_descricao = $descricao;
					$aux_pesquisa_posto = pg_result($res,$i-1,pesquisa_posto);
					$aux_titulo_rec     = pg_result($res,$i-1,titulo);
					$reclamacao = $_POST['reclamacao_'.$i];

					echo "$aux_pesquisa_posto - $reclamacao <br>";

					if($aux_pesquisa_posto == $reclamacao){
						$seleciona = 't';
					}else{
						$seleciona = 'f';
					}
					if(strlen($msg_erro) == 0){
						$sql2 = "INSERT INTO tbl_posto_pesquisa_item (posto_pesquisa,
																	seleciona       ,
																	titulo          ,
																	descricao
															) VALUES (
																	$posto_pesquisa     ,
																	'$seleciona'        ,
																	'$aux_titulo_rec'   ,
																	'$aux_descricao'
															)";
						$res2 = pg_exec($con,$sql2);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
			if(strlen($msg_erro) == 0){
				header("Location: $PHP_SELF?ok=ok");
				exit;
			}
		}
	}
}

if(strlen($estado) > 0) {
	echo "<td>";
	echo "<b>";
	if ($estado == "AC") echo "Acre";
	if ($estado == "AL") echo "Alagoas";
	if ($estado == "AM") echo "Amazonas";
	if ($estado == "AP") echo "Amapá";
	if ($estado == "BA") echo "Bahia";
	if ($estado == "CE") echo "Ceará";
	if ($estado == "DF") echo "Distrito Federal";
	if ($estado == "ES") echo "Espírito Santo";
	if ($estado == "GO") echo "Goiás";
	if ($estado == "MA") echo "Maranhão";
	if ($estado == "MG") echo "Minas Gerais";
	if ($estado == "MS") echo "Mato Grosso do Sul";
	if ($estado == "MT") echo "Mato Grosso";
	if ($estado == "PA") echo "Pará";
	if ($estado == "PB") echo "Paraíba";
	if ($estado == "PE") echo "Pernambuco";
	if ($estado == "PI") echo "Piauí";
	if ($estado == "PR") echo "Paraná";
	if ($estado == "RJ") echo "Rio de Janeiro";
	if ($estado == "RN") echo "Rio Grande do Norte";
	if ($estado == "RO") echo "Rondônia";
	if ($estado == "RR") echo "Roraima";
	if ($estado == "RS") echo "Rio Grande do Sul";
	if ($estado == "SC") echo "Santa Catarina";
	if ($estado == "SE") echo "Sergipe";
	if ($estado == "SP") echo "São Paulo";
	if ($estado == "TO") echo "Tocantins";
	echo "</b>";
	if(strlen($cidade)>0 and $cidade<>"NULL"){
		echo "<BR><B>$cidade</b>";

	}
}
if($login_fabrica <>'56' AND $login_fabrica <>'57' AND $login_fabrica <>'58'){
	$sql_add1 = " AND tbl_posto.posto not in(6359,20462) ";
}

if(strlen($estado)>0){

	echo "<table align='center'>";
	$sql = "SELECT
				tbl_posto.posto                                  ,
				tbl_posto_fabrica.contato_endereco AS endereco   ,
				tbl_posto_fabrica.contato_numero   AS numero     ,
				tbl_posto.nome                                   ,
				tbl_posto_fabrica.contato_cidade   AS cidade     ,
				tbl_posto_fabrica.contato_estado   AS estado     ,
				tbl_posto_fabrica.contato_bairro   AS bairro     ,
				tbl_posto.fone                                   ,
				tbl_posto_fabrica.nome_fantasia                  ,
				tbl_posto_fabrica.codigo_posto                   ,
				tbl_posto_fabrica.credenciamento
			FROM   tbl_posto
			JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
			JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			$join
			WHERE   tbl_posto_fabrica.fabrica = '$login_fabrica'
			AND tbl_posto.estado ILIKE '%$estado%'
			and $cond_1
			$sql_add1
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			ORDER BY tbl_posto.cidade, tbl_posto.nome";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res) == 0){
		$sql = "SELECT
					tbl_posto.posto                 ,
					tbl_posto.endereco              ,
					tbl_posto.numero                ,
					tbl_posto.nome                  ,
					tbl_posto.cidade                ,
					tbl_posto.estado                ,
					tbl_posto.bairro                ,
					tbl_posto.fone                  ,
					tbl_posto.nome_fantasia         ,
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto_fabrica.credenciamento
				FROM   tbl_posto
				JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
				JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
				WHERE   tbl_posto_fabrica.fabrica = '$login_fabrica'
				AND tbl_posto.estado ILIKE '%$estado%'
				$sql_add1
				AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
				ORDER BY tbl_posto.cidade, tbl_posto.nome";
		$res = pg_exec ($con,$sql);
	}
#echo "$sql";
	if(pg_numrows($res) > 0){
		echo "<br>";
		echo "<table border='0' cellspacing='0' cellpadding='0'>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp;Posto cadastrado dentro de 30 dias";
		echo "</b></font></td><BR>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "<center>$mensagem</center>";
		echo "<table width='700' align='center' style='border:1px #75706A solid;height:22px;'>";
		echo "<tr align='center' bgcolor='#eeeeff' >";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Código</b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Nome do Posto </b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Endereço </b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Cidade </b></td>";
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Telefone </b></td>";
		// HD 15868 17922
		if($login_fabrica== 25 or $login_fabrica == 45){
		echo "<td style='border:1px #75706A solid;height:22px;' class='fontenormal'><b> Linha </b></td>";
		}
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto          = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$bairro         = trim(pg_result($res,$i,bairro));
			$nome_fantasia  = trim(pg_result($res,$i,nome_fantasia));
			$endereco       = trim(pg_result($res,$i,endereco));
			$numero         = trim(pg_result($res,$i,numero));
			$fone           = trim(pg_result($res,$i,fone));

			$cor = '#eeeeff';
			if ($i % 2 == 0) $cor = '#ffffff';

			$tdcolor="";
			$sqlx="SELECT * from tbl_posto_pesquisa where posto = $posto and CURRENT_DATE - interval '30 days' < data ";
			$resx=pg_exec($con,$sqlx);
			if(pg_numrows($resx) > 0) {
				$tdcolor=" bgcolor='#FF0000' ";
			}

			echo "<tr bgcolor='$cor' style='border:1px #75706A solid;height:22px; font-size: 10px' class='fontenormal'>";
			$end_completo= $endereco . ", " . $numero  . " - " . $bairro;

			echo "<INPUT TYPE='hidden' NAME='posto' value='$posto'>";

			echo "<td $tdcolor><b><FONT SIZE='-3'>";
			echo "<a href='$PHP_SELF?posto=$posto'><b>$codigo_posto</b></a>";
			echo "</b></FONT></td>";

			echo "<td ><b><FONT SIZE='-3'>";
			echo "<a href='$PHP_SELF?posto=$posto'><b>$nome</b></a>";
			echo "</b></FONT></td>";

			echo "<td><b>";
			echo $endereco . ", " . $numero  . " - " . $bairro;
			echo "</b></td>";

			echo "<td nowrap><b>";
			echo $cidade ;
			echo "</b></td>";

			echo "<td nowrap><b>";
			echo $fone ;
			echo "</b></td>";
			//HD 15868
			echo "</tr>";
		}
	}else{
		echo "<p class='fontenormal'> Nenhuma Assistência Técnica encontrada. </p>";
	}
echo "</table>";
echo "<br><br>";
exit;
}

?>

<style type="text/css">

body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.fonte {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color: #999999;
	text-decoration: none;
}
.linkk {font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #999999;
	text-decoration: none;
}

.fontenormal {	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #6A6A6A;
	text-decoration: none;
	font-style: normal;
	line-height: 23px;
	font-weight: normal;
}
.style1 {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #666666; text-decoration: none; }
.stylebordo {font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #663300; text-decoration: none;
}
v\:* {      behavior:url(#default#VML);    }
</style>

<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script type="text/javascript" src="js/firebug.js"></script>
<script type="text/javascript" src="js/jquery-1.1.2.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">

<script type="text/javascript">

var http1 = new Array();
function mostraPostos(fabrica,estado){

	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();

	url = "pesquisa_acompanhamento_teste.php?fabrica="+fabrica+"&estado="+estado;
	http1[curDateTime].open('get',url);

	var campo = document.getElementById('div_defeitos');
//alert(natureza);
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http1[curDateTime].readyState == 4){
			if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
				var results = http1[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";

			}
		}
	}
	http1[curDateTime].send(null);
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

</script>

<?
$layout_menu = "callcenter";
$title = "Cadastro de Reclamações";
include 'cabecalho.php';


$ok = $_GET['ok'];

if(strlen($ok) > 0){
	echo "<center><p style='font-size: 14px; color: #330000'><b>Cadastro efetuado com sucesso</b></p></center>";
}

if(strlen($posto) == 0){
?>

	<body>
	<br>

	<table width='700' align='center' cellspacing='0' cellpadding='0'>
	<tr>
		<td background="cadence_bg_int.gif" align='center'>
			<map name="FPMap0">
			<area href="<? echo "http://www.telecontrol.com.br";?>" shape="rect" coords="30, 50, 230, 115">
			</map>
			<table width='670' align='center' border='0'>
			<FORM METHOD=POST ACTION="<?echo "$PHP_SELF?fabrica=$login_fabrica&estado=$estado"?>">
			<tr>
				<td>
				<map name="Map2">
				<area shape="poly" coords="122,238,142,221,164,232,148,262" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','rs')">
				<area shape="poly" coords="143,214,172,215,169,235,143,219" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','sc')">
				<area shape="poly" coords="138,202,148,191,166,192,175,207,171,214,139,213" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','pr')">
				<area shape="poly" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','sp')">


				<area shape="poly" coords="136,195,156,171,138,159,124,159,117,182" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$$login_fabrica?>','ms')">
				<area shape="poly" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','mt')">
				<area shape="poly" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','ro')">
				<area shape="poly" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','ac')">
				<area shape="poly" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','am')">
				<area shape="poly" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','rr')">
				<area shape="poly" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','pa')">
				<area shape="poly" coords="145,25,153,23,157,13,164,29,153,41" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','ap')">
				<area shape="poly" coords="196,50,185,72,194,90,212,82,215,59" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','ma')">

				<area shape="poly" coords="179,83,165,120,189,128,185,101" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','to')">
				<area shape="poly" coords="159,166,148,157,165,131,188,136,170,151" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','go')">
				<area shape="poly" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','pi')">
				<area shape="poly" coords="206,201,202,190,214,189,218,181,226,187" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','rj')">
				<area shape="poly" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','mg')">
				<area shape="poly" coords="236,167,228,162,221,177,226,183" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','es')">
				<area shape="poly" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','ba')">
				<area shape="poly" coords="230,59,235,86,241,86,252,70,239,61" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','ce')">
				<area shape="poly" coords="250,108,248,113,251,118,257,113,252,109" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','se')">

				<area shape="poly" coords="266,102,258,104,251,102,260,110,266,104" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','al')">
				<area shape="poly" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','pe')">
				<area shape="poly" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','pb')">
				<area shape="poly" coords="256,73,249,81,256,80,257,83,270,82,265,76" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','rn')">
				<area shape="poly" coords="168,162,171,153,183,149,182,161" style='cursor: pointer'
				href="javascript: mostraPostos('<?=$login_fabrica?>','df')">
				</map>
				<img src="/mapa_rede/cadence_mapa.gif" usemap="#Map2" border="0">
				</td>
			</tr>
			</form>
		  </table>
		</td>
	</tr>
	<?
	echo "<tr>";
	echo "<td colspan='2' height='100' align='center'>";
	echo "<div id='div_defeitos' style='display:inline; Position:relative;width:100%'>";
	echo "</td>";
	echo "</tr>";
	echo "<br><br>";


}else{

	if(strlen($msg_erro) > 0){
		echo "<center><p style='font-size: 12px; color: #FF0000'><b>$msg_erro</b></p></center>";
	}

	$sql = "SELECT tbl_posto_fabrica.posto             ,
					tbl_posto_fabrica.contato_nome     ,
					tbl_posto.fone                     ,
					tbl_posto.nome                     ,
					tbl_posto_fabrica.contato_email    ,
					to_char(current_date,'DD/MM/YYYY') as data_pesquisa
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				AND tbl_posto.posto = $posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica;
			";
//	echo "$sql";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){

		$posto         = pg_result($res,0,posto);
		$posto_nome    = pg_result($res,0,nome);
		$posto_contato = pg_result($res,0,contato_nome);
		$posto_fone    = pg_result($res,0,fone);
		$posto_email   = pg_result($res,0,contato_email);
		$data_pesquisa = pg_result($res,0,data_pesquisa);

		echo "<br><br>";
		echo "<FORM METHOD=POST name='frm_pesquisa' ACTION='$PHP_SELF'>";
		echo "<INPUT TYPE='hidden' NAME='posto' value='$posto'>";
		echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='font-size:12px'>";
		echo "<tr>";
			echo "<td aling='center'>";
			echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";

			echo "<tr>";
				echo "<td align='center' colspan='4'><strong>POSTO AUTORIZADO</strong></td>";
			echo "</tr>";

			echo "<tr>";
				echo "<td nowrap width='80'>Razão Social:</td> ";
				echo "<td><b>$posto_nome</b></td>";
				echo "<td width='80'>Contato: </td>";
				echo "<td><INPUT TYPE='text' NAME='contato' size='20'></td>";
			echo "</tr>";

			echo "<tr>";
				echo "<td>Telefone:</td>";
				echo "<td><b>$posto_fone</b></td>";
				echo "<td>Email:</td>";
				echo "<td><b>$posto_email</b></td>";
			echo "</tr>";

			echo "<tr>";
				echo "<td colspan='1' width='80'>Linha de Atendimento:</td>";
				echo "<td colspan='4'><INPUT TYPE='text' NAME='linha_atendimento' size='50'></td>";
			echo "</tr>";

			echo "<tr>";
				echo "<td colspan='1'>Data:</td>";
				echo "<td colspan='3'><INPUT TYPE='text' NAME='data_pesquisa' size='11' value='$data_pesquisa'></td>";
			echo "</tr>";

			echo "</table>";
			echo "</td>";
echo "<br>";
			echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>";
				echo "<tr>";
					echo "<td align='right' width='20'></td>";
					echo "<td align='right' width='55'>";
						echo "<img src='imagens/ajuda_call.png' align='absmiddle' >";
					echo "</td>";
					echo "<td align='center'>";
						echo "A DYNACOM está fazendo um trabalho de acompanhamento das assistências técnicas afim de identificar as principais carências e necessidades em nosso atendimento para com os postos autorizados. Há algum problema ocorrendo em que possamos ajudá-los, como por exemplo...(<i>ler opções abaixo</i>)";
					echo "</td>";
					echo "<td align='right' width='20'></td>";
				echo"</tr>";
			echo "</table>";

		$sql = "SELECT pesquisa_posto, titulo
					FROM tbl_pesquisa_posto
					WHERE fabrica = $login_fabrica;
				";
		$res = pg_exec($con,$sql);

		echo "<table width='700' border='0' align='center' cellpadding='2' cellspacing='2' style='font-size:12px'>";
		echo "<tr>";
		echo "<td aling='center'>";


		if(pg_numrows($res) > 0){
			echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
			echo "<tr>";
			for($i=1;$i<pg_numrows($res) +1 ;$i++){
				$pesquisa_posto = @pg_result($res,$i-1,pesquisa_posto);
				$reclamacao     = @pg_result($res,$i-1,titulo);
					echo "<td nowrap><INPUT TYPE='checkbox' NAME='reclamacao_$i' value='$pesquisa_posto'></td> ";
					echo "<td><b>$reclamacao</b></td>";
					if( $i% 2==0){ echo "</tr><tr>"; }
			}

			echo "</tr>";
			echo "<tr>";
				echo "<td>Descrição</td> ";
				echo "<td colspan='3'><TEXTAREA NAME='descricao' ROWS='3' COLS='50'></TEXTAREA></td>";
			echo "</tr>";
			echo "</table>";
			}
		}
		echo "</td>";
		echo "</table>";
		echo "</td>";

	echo "</td>";
	echo "<tr>";
	echo "<td>";

		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<center>";
			echo "<img src='imagens_admin/btn_gravar.gif' onclick=\"javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='gravar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar formulário' border='0' style='cursor:pointer;'>";
		echo "</center>";

	echo "</td>";
	echo "</tr>";

	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";
echo "<br>";

}


include "rodape.php";
?>
</table>
</body>
</html>