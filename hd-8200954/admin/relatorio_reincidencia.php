<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";
$titulo = "Relatório de Reincidência";
$title = "Relatório de Reincidência";

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$posto        = $_GET['posto'];
$sql = "SELECT 	tbl_os.os                                                       ,
				tbl_os.sua_os                                                   ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura     ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento ,
				tbl_os.serie                                                    ,
				tbl_produto.descricao as produto_descricao
		from tbl_os
		JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
		join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_produto on tbl_os.produto = tbl_produto.produto";

if($login_fabrica == 14 ) {
	$sql.=" JOIN tbl_os_extra USING(os) 
			JOIN tbl_extrato USING(extrato) ";
}		

$sql.=" WHERE tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida is not true
		and tbl_os.os_reincidente is true ";
if(strlen($posto)>0) $sql .=" AND tbl_posto.posto = '$posto' ";
if($login_fabrica == 14 ) {
	$sql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
} else {
	$sql .= " AND   tbl_os.validada BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
}

$res = pg_exec ($con,$sql);

$qtde_os = pg_numrows ($res);
if($qtde_os>0){

	echo "<BR><table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 10px'>";
	echo "<tr>";
	echo "<td><font color='#FFFFFF'><B>OS</B></FONT></td>";
	echo "<td><font color='#FFFFFF'><B>Abertura</B></FONT></td>";
	echo "<td><font color='#FFFFFF'><B>Fechamento</B></FONT></td>";
	echo "<td><font color='#FFFFFF'><B>Produto</B></FONT></td>";
	echo "<td><font color='#FFFFFF'><B>Série</B></FONT></td>";
	echo "</tr>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os = pg_result ($res,$i,os);
		$sua_os         = pg_result ($res,$i,sua_os);
		$data_abertura    = pg_result ($res,$i,data_abertura);
		$data_fechamento    = pg_result ($res,$i,data_fechamento);
		$serie    = pg_result ($res,$i,serie);
		$produto_descricao    = pg_result ($res,$i,produto_descricao);


		$cor = "#efeeea"; 
		if ($i % 2 == 0) $cor = '#d2d7e1';
		echo "<tr bgcolor='$cor'>";
		echo "<td align='center'><a href='os_press.php?os=$os' target='blank'>$sua_os</td>";
		echo "<td align='center'>$data_abertura</td>";
		echo "<td align='center'>$data_fechamento</td>";
		echo "<td align='left'>$produto_descricao</td>";
		echo "<td align='left'>$serie</td>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table><BR>";

}else{
echo "nenhuma OS";
}
exit;

}

include 'cabecalho.php';
include "javascript_pesquisas.php"; 


?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
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
	
function mostraOS(posto,data_inicial,data_final){

	if (document.getElementById('dados_' + posto)){
		var style2 = document.getElementById('dados_' + posto); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaOS(posto,data_inicial,data_final);
		}
	}
}
var http3 = new Array();
function retornaOS(posto,data_inicial,data_final){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "relatorio_reincidencia.php?ajax=true&posto=" + posto + "&data_inicial=" + data_inicial + "&data_final="+ data_final;
	http3[curDateTime].open('get',url);
	
	var campo = document.getElementById('dados_'+posto);

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}
</script>
<?
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

echo "<form name='frm_consulta' method='post' action='$PHP_SELF'>";
?>
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> Relatório de reincidência </td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td> * Mês</td>
		<td> * Ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Posto</td>
		<td>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
<td  colspan='2' align='center'><input type='submit' name='btn_acao' value='Exibir'></td>
</tr>
</table>
<?

$btn_acao= $_POST['btn_acao'];
if (strlen($btn_acao)>0){

$mes= $_POST['mes'];
$ano= $_POST['ano'];

$codigo_posto= $_POST['codigo_posto'];
$posto_nome= $_POST['posto_nome'];

//tratamento de datas
$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes, 1, $ano));
$data_final = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));


#$data_inicial = '2006-08-22 00:00:00';
#$data_final   = '2006-08-22 23:59:59';
$sql = "SELECT 	tbl_posto_fabrica.codigo_posto , 
				tbl_posto.nome, 
				count(os) as qtde
		from tbl_os
		JOIN tbl_posto on tbl_posto.posto = tbl_os.posto
		join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica";
if($login_fabrica == 14 ) {
	$sql.=" JOIN tbl_os_extra USING(os) 
			JOIN tbl_extrato USING(extrato) ";
}		

$sql.=" where tbl_os.fabrica = $login_fabrica
		and tbl_os.excluida is not true
		and tbl_os.os_reincidente is true ";

if(strlen($codigo_posto)>0) $sql .=" AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";

if($login_fabrica == 14 ) {
	$sql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
} else {
	$sql .= " AND   tbl_os.validada BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
}
$sql.="	GROUP BY tbl_posto_fabrica.codigo_posto , 
				 tbl_posto.nome
		ORDER BY qtde DESC";

if(strlen($codigo_posto)>0){ 
	$posto .=" AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
}

$sql = "SELECT os
	INTO TEMP tmp_rr_$login_admin
	FROM tbl_os_extra
	JOIN tbl_extrato  USING(extrato)
	WHERE tbl_extrato.fabrica = $login_fabrica
	AND   tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59';

	CREATE INDEX tmp_rr_os_$login_admin ON tmp_rr_$login_admin(os);

	SELECT 	tbl_posto_fabrica.codigo_posto ,
		tbl_posto.posto,
		tbl_posto.nome, 
		count(tbl_os.os) as qtde
	FROM tbl_os
	JOIN tbl_posto             ON tbl_posto.posto = tbl_os.posto
	JOIN tbl_posto_fabrica     ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
	JOIN tbl_produto           ON tbl_os.produto  = tbl_produto.produto
	JOIN tmp_rr_$login_admin X ON X.os = tbl_os.os
	WHERE tbl_os.fabrica = $login_fabrica
	AND   tbl_os.excluida       IS NOT TRUE 
	AND   tbl_os.os_reincidente IS     TRUE 
	$posto
	GROUP BY tbl_posto_fabrica.codigo_posto , 
		 tbl_posto.nome,
		tbl_posto.posto
	ORDER BY qtde DESC";

	$res = pg_exec ($con,$sql);

	$qtde_os = pg_numrows ($res);
	if($qtde_os>0){

	echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' bgcolor='#596D9B' align='center' style='font-family: verdana; font-size: 11px'>";
	echo "<tr>";
	echo "<td><font color='#FFFFFF'><B>Código Posto</B></FONT></td>";
	echo "<td><font color='#FFFFFF'><B>Nome do Posto</B></FONT></td>";
	echo "<td><font color='#FFFFFF'><B>Quantidade</B></FONT></td>";
	echo "</tr>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$codigo_posto = pg_result ($res,$i,codigo_posto);
		$nome         = pg_result ($res,$i,nome);
		$posto        = pg_result ($res,$i,posto);
		$qtde         = pg_result ($res,$i,qtde);

		$cor = "#efeeea"; 
		if ($i % 2 == 0) $cor = '#d2d7e1';
		echo "<tr bgcolor='$cor'>";
		echo "<td align='left'>";
		echo "<a href=\"javascript:mostraOS($posto,'$data_inicial','$data_final');\">$codigo_posto</a>";
		echo "<div id='dados_$posto' style='position:absolute; display:none; border: 1px solid #949494;background-color: #b8b7af;width:500px;'></div>";
		echo "</td>";
		echo "<td align='left'>$nome</td>";
		echo "<td align='center'>$qtde</td>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	}else{
	echo "<center>Nenhum resultado encontrado</center>";
	}
}


include "rodape.php";


?>

