<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$data_inicial = $_GET['data_inicial'];


$data_final   = $_GET['data_final'];
$peca         = $_GET['peca'];
$estado       = $_GET['estado'];
$tipo_os      = $_GET['tipo_os'];
$pagamento    = $_GET['pagamento'];
$pais         = $_GET['pais'];
$origem       = $_GET['origem'];
$posto        = $_GET['posto'];

if($login_fabrica == 20 and $pais !='BR'){
	$sql = "SELECT tbl_peca_idioma.descricao, referencia FROM tbl_peca  LEFT JOIN tbl_peca_idioma ON tbl_peca.peca = tbl_peca_idioma.peca and tbl_peca_idioma.idioma='ES' WHERE tbl_peca.peca = $peca";
}else{
	$sql = "SELECT descricao, referencia FROM tbl_peca WHERE peca = $peca";
}

if($ip=='200.228.76.93') echo "sql: $sql";
$res = pg_exec($con,$sql);
$descricao_peca = pg_result($res,0,descricao);
$referencia     = pg_result($res,0,referencia);

$aux_data_inicial = substr($data_inicial,8,2)."/".substr($data_inicial,5,2)."/".substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)."/".substr($data_final,5,2)."/".substr($data_final,0,4);

$title = "DEFEITOS DA PEÇA";

?>
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style type="text/css">

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	text-align: left;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	text-align: center;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo20 {
	color: #000000;
	text-align: left;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

<script language="JavaScript">
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
function MostraOs(abre_os,defeito){
//al/ert(abre_os + data_inicial + data_final + peca + defeito);
//alert(abre_os);
	if (document.getElementById){

		var style2 = document.getElementById(abre_os); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
//alert(abre_os + defeito);
			style2.style.display = "block";
			retornaAtencao(abre_os,defeito);
		}
	}
}
var http3 = new Array();
function retornaAtencao(abre_os,defeito){
	var data_inicial = document.frm_fcr.data_inicial.value;
	var data_final   = document.frm_fcr.data_final.value; 
	var peca         = document.frm_fcr.peca.value; 
	var pais         = document.frm_fcr.pais.value; 
	var posto        = document.frm_fcr.posto.value; 
	var curDateTime = new Date();
	//alert('inicio '+data_inicial+'final '+data_final+'defeito '+defeito+'peca '+peca );
	http3[curDateTime] = createRequestObject();
	url = "ajax_fcr_defeitos_os.php?peca="+ peca +"&pais="+ pais +"&origem="+ origem +"&posto="+ posto +"&defeito="+ defeito +"&data_inicial=" + data_inicial + "&data_final="+data_final+"&tipo_os=<?echo $tipo_os;?>";
//alert(url);
	http3[curDateTime].open('get',url);
	var abre_os = document.getElementById(abre_os); 
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			abre_os.innerHTML = "<font size='1'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				abre_os.innerHTML   = results;
			}else {
				abre_os.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);
	
}
</script>
</HEAD>

<BODY>

<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." até ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<table align = 'center'>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PEÇA: <b><?echo $referencia;?> - <? echo  $descricao_peca;?></b></TD>
		</TR>
	</table>
</TABLE>
<BR>
<?

/* antes alteracao takashi 20-09
$sql = "SELECT	count(*) AS ocorrencia     ,
					tbl_defeito.descricao AS descricao_defeito
			FROM    tbl_os
			JOIN    tbl_os_produto USING (os) 
			JOIN    tbl_os_item    USING (os_produto) 
			JOIN    tbl_defeito    USING (defeito) 
			LEFT JOIN tbl_os_status   ON tbl_os_status.os    = tbl_os.os
			WHERE   tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'
			AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_os_item.peca = $peca
			GROUP BY tbl_defeito.descricao
			ORDER BY ocorrencia DESC
		";
*/
$cond       = "1=1";
$cond_pais  = "1=1";
$cond_3     = "1=1";
$cond_origem= "1=1";

if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen (trim($origem))   > 0) $cond_origem = " tbl_produto.origem     = '$origem' ";

if(strlen($pagamento)>0) $data_consulta = " tbl_extrato_extra.exportado ";
else                     $data_consulta = " tbl_os.data_abertura ";



if ($login_fabrica == 20  AND strlen($pais)> 0) {
	if($pais <> 'BR')	$data_consulta = " tbl_extrato.data_geracao ";
	$cond_pais= " tbl_posto.pais = '$pais' ";
}


if(strlen($tipo_os)>0) $cond = " tbl_os.consumidor_revenda = '$tipo_os' ";

$sql = "SELECT	count(*) AS ocorrencia     ,
				tbl_defeito.descricao AS descricao_defeito,
				tbl_defeito.defeito
		FROM    tbl_os
		JOIN    tbl_os_produto USING (os) 
		JOIN    tbl_os_item    USING (os_produto) 
		JOIN    tbl_defeito    USING (defeito) ";
if($login_fabrica==14){ 
	$sql .=" 
	join tbl_os_extra on tbl_os.os=tbl_os_extra.os
	join tbl_extrato using(extrato) "; 
}

if($login_fabrica == 20 ){
	$sql .= "
		JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
		JOIN tbl_os_extra on tbl_os.os=tbl_os_extra.os
		JOIN tbl_extrato       USING(extrato)
		JOIN tbl_extrato_extra USING(extrato)";
}
$sql .= "
	LEFT JOIN tbl_os_status   ON tbl_os_status.os    = tbl_os.os";

if($login_fabrica == 20 ){
	$sql .= " JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto and $cond_3";
}

if($login_fabrica==14 ){ 
	$sql .=" WHERE tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
}else{
	$sql .=" WHERE   $data_consulta BETWEEN '$data_inicial' AND '$data_final' ";
}
$sql .= "  
AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
AND     tbl_os.fabrica = $login_fabrica
AND     tbl_os_item.peca = $peca
AND     $cond
AND     $cond_pais
AND     $cond_origem
GROUP BY tbl_defeito.descricao, tbl_defeito.defeito
ORDER BY ocorrencia DESC
";
//echo $sql;
if($ip == "201.92.1.225"){
	echo "sql: $sql dat: $data_inicial";
}
if($ip=='200.228.76.93') echo "sql: $sql"; 
//exit;
	$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<br>";
	echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='1' align = 'center'>";
	echo "<TR>";
	echo "<TD class='titChamada10'>DEFEITO</TD>";
	echo "<TD class='titChamada10'>OCORRÊNCIAS</TD>";
	echo "<TD class='titChamada10'>%</TD>";
	echo "</TR>";
echo "<form name='frm_fcr' method='post'>";
echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
echo "<input type='hidden' name='data_final' value='$data_final'>";
echo "<input type='hidden' name='peca' value='$peca'>";
echo "<input type='hidden' name='pais' value='$pais'>";
echo "<input type='hidden' name='posto' value='$posto'>";
echo "</form>";
	$total_ocorrencia = 0;
	for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
	}
	
	for ($i = 0;$i < pg_numrows($res);$i++){
		$descricao_defeito    = trim(pg_result($res,$i,descricao_defeito));
		$defeito    = trim(pg_result($res,$i,defeito));
		$ocorrencia = trim(pg_result($res,$i,ocorrencia));

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}
			
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='left'><a href='javascript: MostraOs(\"abre_os_$i\",$defeito);'>$descricao_defeito</a></TD>";
		echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
		echo "	<TD class='conteudo10' align='right'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "</TR>";
		echo "<TR><TD colspan=3 align='center'><div id='abre_os_$i' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4; width:452px'></div></td></tr>";

	}
	echo "</table>";
}else{
	echo "<br>";
	echo "Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
}
?>
<br>

<hr  width = "600">

<br>
<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14'><B>PRODUTOS ONDE ESTA PEÇA É USADA</B></TD>
	</TR>
</table>

<?


//TRADUÇÃO PARA A BOSCH QUANDO PAIS FOR DA AMÉRICA LATINA
if($login_fabrica == 20 and $pais !='BR'){
	$descricao_produto= " tbl_produto_idioma.descricao ";
	$join_produto = " LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma='ES' ";
}else{
	$descricao_produto= " tbl_produto.descricao ";
	$join_produto = " ";
}


$sql = "SELECT tbl_produto.referencia,
               $descricao_produto ,
			   count(*) as qtde
		FROM (
				SELECT  tbl_os.os ,
						tbl_os.produto
				FROM    tbl_os 
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item    USING (os_produto)";
			if($login_fabrica==14){ $sql .=" 
			join tbl_os_extra on tbl_os.os=tbl_os_extra.os
			join tbl_extrato using(extrato) "; 
			}
			if($login_fabrica == 20 AND strlen($pagamento)>0){

				$sql .= "
					JOIN tbl_os_extra on tbl_os.os=tbl_os_extra.os
					JOIN tbl_extrato       USING(extrato)
					JOIN tbl_extrato_extra USING(extrato)";
			}
			$sql .= "
				LEFT JOIN tbl_os_status ON tbl_os_status.os    = tbl_os.os
				WHERE   tbl_os_item.peca = $peca
				AND     tbl_os.fabrica   = $login_fabrica
				AND     $cond
				AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)";
			if(strlen($posto) >0 ) $sql .=" AND tbl_os.posto = $posto";
			if($login_fabrica==14){ $sql .=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";}else{
				$sql .= " and $data_consulta BETWEEN '$data_inicial' AND '$data_final'";}
			$sql .= " GROUP BY tbl_os.os, tbl_os.produto
		) a 
		JOIN tbl_produto USING (produto)
		$join_produto
		WHERE $cond_origem
		GROUP BY tbl_produto.referencia, $descricao_produto
		ORDER BY qtde DESC";


/* antes da alteracao do takashi 20/09
$sql = "SELECT tbl_produto.referencia,
               tbl_produto.descricao ,
			   count(*) as qtde
		FROM (
				SELECT  tbl_os.os ,
						tbl_os.produto
				FROM    tbl_os 
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item    USING (os_produto)
				LEFT JOIN tbl_os_status ON tbl_os_status.os    = tbl_os.os
				WHERE   tbl_os_item.peca = $peca
				AND     tbl_os.fabrica   = $login_fabrica
				AND    (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
				AND     tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'
				GROUP BY tbl_os.os, tbl_os.produto
		) a JOIN tbl_produto USING (produto)
		GROUP BY tbl_produto.referencia, tbl_produto.descricao
		ORDER BY qtde DESC";

*/
if($ip=='200.228.76.93') echo "sql: $sql"; //exit;
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<br>";
	echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
	echo "<TR>";
	echo "<TD class='titChamada10'>REFERÊNCIA</TD>";
	echo "<TD class='titChamada10'>DESCRIÇÃO</TD>";
	echo "<TD class='titChamada10'>QTDE</TD>";
	echo "<TD class='titChamada10'>%</TD>";
	echo "</TR>";

	$total_ocorrencia = 0;
	for ($x = 0; $x < pg_numrows($res); $x++) {
		$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,qtde);
	}
	
	for ($i = 0;$i < pg_numrows($res);$i++){
		$referencia    = trim(pg_result($res,$i,referencia));
		$descricao     = trim(pg_result($res,$i,descricao));
		$qtde          = trim(pg_result($res,$i,qtde));

		if ($total_ocorrencia > 0) {
			$porcentagem = (($qtde * 100) / $total_ocorrencia);
		}
			
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='left'>$referencia</TD>";
		echo "	<TD class='conteudo20' align='left'>$descricao</TD>";
		echo "	<TD class='conteudo10' align='center'>$qtde</TD>";
		echo "	<TD class='conteudo10' align='right'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "</TR>";
	}
	echo "</table>";
}else{
	echo "<br>";
	echo "Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado</b>";
}
?>

</BODY>
</HTML>
