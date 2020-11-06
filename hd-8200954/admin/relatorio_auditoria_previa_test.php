<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}



# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

//include "gera_relatorio_pararelo_include.php";


include 'funcoes.php';

$msg_erro = "";

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen($btn_acao) > 0 ){

	if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
	if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);

	if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);

	if (strlen(trim($_POST["data_tipo"])) > 0) $data_tipo = trim($_POST["data_tipo"]);
	if (strlen(trim($_GET["data_tipo"])) > 0)  $data_tipo = trim($_GET["data_tipo"]);

	if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);
	
	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);

	if (strlen(trim($_POST["tipo"])) > 0) $tipo = trim($_POST["tipo"]);
	if (strlen(trim($_GET["tipo"])) > 0)  $tipo = trim($_GET["tipo"]);


	if (strlen($data_inicial) > 0) {
		$x_data_inicial = fnc_formata_data_pg($data_inicial);
		$x_data_inicial = str_replace("'", "", $x_data_inicial);
		$dia_inicial    = substr($x_data_inicial, 8, 2);
		$mes_inicial    = substr($x_data_inicial, 5, 2);
		$ano_inicial    = substr($x_data_inicial, 0, 4);
		$x_data_inicial = $ano_inicial ."-". $mes_inicial ."-". $dia_inicial. " 00:00:00";
	}else{
		$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
	}

	if (strlen($data_final) > 0) {
		$x_data_final = fnc_formata_data_pg($data_final);
		$x_data_final = str_replace("'", "", $x_data_final);
		$dia_final    = substr($x_data_final, 8, 2);
		$mes_final    = substr($x_data_final, 5, 2);
		$ano_final    = substr($x_data_final, 0, 4);
		$x_data_final   = $ano_final ."-". $mes_final ."-". $dia_final . " 23:59:59";
	}else{
		$msg_erro .= " Preencha o campo Data Final para realizar a pesquisa. ";
	}

	if(strlen($x_data_final)>0 AND strlen($x_data_inicial)>0){
		$sql = "select '$x_data_final'::date - '$x_data_inicial'::date ";
		$res = pg_query($con,$sql);
		# HD 33792 - Francisco Ambrozio
		#   Aumentado período para 180 dias para a Britânia
		if ($login_fabrica == 3){
			if(pg_fetch_result($res,0,0)>180)$msg_erro .= "Período não pode ser maior que 180 dias";
		}else{
			if(pg_fetch_result($res,0,0)>30)$msg_erro .= "Período não pode ser maior que 30 dias";
		}
	}
}


$layout_menu = "gerencia";
$title = "Relatório de OSs que sofreram auditoria prévia";

include "cabecalho.php";
?>
<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #596D9B
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[2];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>
<p>


<? 
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	//include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	//include "gera_relatorio_pararelo_verifica.php";
}
?>


<? if (strlen($msg_erro) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<br>
<? } ?>


<form name='frm_relatorio' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='400' align='center' border='0' cellspacing='2' cellpadding='2' bgcolor='#d9e2ef'>
<tr class='topo'>
	<td colspan='4'>OS auditadas</td>
</tr>
<TR class='menu_top'>
    <TD style="width: 10px">&nbsp;</TD>
	<TD align='left'><input type='radio' name='data_tipo' value='digitacao' <?if(strlen($data_tipo)==0 or $data_tipo =='digitacao') echo "CHECKED"; ?>>Data Digitação</TD>
    <TD align='left'><input type='radio' name='data_tipo' value='auditoria' <?if($data_tipo =='auditoria') echo "CHECKED"; ?>>Data Auditoria(Liberada)</TD>
    <TD style="width: 10px">&nbsp;</TD>
  </TR>
<TR class='menu_top'>
    <TD style="width: 10px">&nbsp;</TD>
	<TD align='left'>Data Inicial</TD>
    <TD align='left'>Data Final</TD>
    <TD style="width: 10px">&nbsp;</TD>
  </TR>
  	<tr class="menu_top">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); ?>" class="frm">
		</td>
		<td>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); ?>"  class="frm">
		</td>
		<td width="10">&nbsp;</td>
	</tr>

<tr class='menu_top'>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left'>Código do Posto</td>
	<td align='left'>Nome do Posto</td>
<TD style="width: 10px">&nbsp;</TD>
</tr>
<tr>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left'><input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="20" value="<? echo $posto_codigo ?>"><img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'codigo')">
	</td>
	<td align='left'><input class="frm" type="text" name="posto_nome" id="posto_nome" size="20" value="<? echo $posto_nome ?>" ><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;">
	</td>
<TD style="width: 10px">&nbsp;</TD>
</tr>
<tr class='menu_top'>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left'>Tipo:</td>
<TD style="width: 10px">&nbsp;</TD>
</tr>

<tr class='menu_top'>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left' colspan=2>
		<input type='radio' name='tipo' <?if (strlen($tipo)==0 or $tipo=='auditoria') echo 'CHECKED';?> value='auditoria'>Auditoria &nbsp;&nbsp;
		<input type='radio' name='tipo' <?if ($tipo=='intervencao') echo 'CHECKED';?> value='intervencao'>Intervenção&nbsp;&nbsp;
		<input type='radio' name='tipo' <?if ($tipo=='ambas') echo 'CHECKED';?> value='ambas'>Ambas</td>
<TD style="width: 10px">&nbsp;</TD>
</tr>

<tr>
<TD colspan='4'><center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_relatorio.btn_acao.value == '' ) { document.frm_relatorio.btn_acao.value='continuar' ; document.frm_relatorio.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
</center></TD>

</table>


<br>



</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){
	flush();

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_query($con,$sqlPosto);
		if (pg_num_rows($res) == 1){
			$posto = pg_fetch_result($res,0,0);
		}
	}
	$cond_1 = "1=1";
	if(strlen($posto)>0) $cond_1 = " tbl_posto.posto = $posto ";
	if ($data_tipo=='digitacao') {
		$cond_2 = " AND tbl_os.data_digitacao between '$x_data_inicial' and '$x_data_final' ";
	}elseif($data_tipo=='auditoria'){
		$cond_2 = " AND tbl_os_auditar.liberado_data between '$x_data_inicial' and '$x_data_final' ";
	}

	// IGOR HD 3612 - Adicionado o distinct pois estava duplicando

	$sql = "SELECT distinct
				tbl_os.os                                       ,
				tbl_os.sua_os                                   ,
				tbl_os.consumidor_revenda as consumidor_revenda	,
				tbl_posto_fabrica.codigo_posto                  ,
				tbl_posto.nome                                  ,
				tbl_os_auditar.descricao as tipo_auditoria      ,
				tbl_os_auditar.cancelada AS os_cancelada        ,
				tbl_admin.login                                 ,
				tbl_os_extra.mao_de_obra_desconto               ,
				tbl_os_extra.mao_de_obra AS mo_extrato          ,
				tbl_os.mao_de_obra                              ,
				tbl_os_extra.status_os                          ,
				tbl_status_os.descricao as situacao             ,
				tbl_os_extra.extrato                            ,
				tbl_produto.referencia as produto_referencia    ,
				tbl_produto.descricao  as produto_descricao     ,
				tbl_os.serie                                    ,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') as data_geracao,
				to_char(tbl_os_auditar.liberado_data,'DD/MM/YYYY') as data_liberacao,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao,
				(SELECT tbl_os_status.observacao FROM tbl_os_status
				 WHERE ";
				
				if($tipo == 'auditoria'){
						$sql .= "tbl_os.os = tbl_os_status.os AND tbl_os_status.status_os in (67,70)";
				}
				if($tipo == 'intervencao'){
						$sql .= "tbl_os.os = tbl_os_status.os AND tbl_os_status.status_os in (72)";
				}
				if($tipo == 'ambas'){
						$sql .= "tbl_os.os = tbl_os_status.os AND tbl_os_status.status_os in (67,70,72)";
				}
		$sql .=		"
				 ORDER BY data DESC limit 1 ) AS observacao
		FROM  tbl_os_auditar
		JOIN  tbl_os using(os)
		JOIN  tbl_os_extra using(os)
		LEFT JOIN  tbl_extrato  on tbl_extrato.extrato = tbl_os_extra.extrato
		JOIN  tbl_posto on tbl_posto.posto = tbl_os.posto
		JOIN  tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT  JOIN tbl_admin ON tbl_os_auditar.admin = tbl_admin.admin
		LEFT  JOIN  tbl_status_os on tbl_os_extra.status_os = tbl_status_os.status_os
		JOIN  tbl_produto on tbl_os.produto = tbl_produto.produto
		WHERE tbl_os.fabrica = $login_fabrica
		/* AND   tbl_os.auditar is true HD 153238 todos que estao neste processo deverao aparecer */
		AND   $cond_1
		$cond_2
		ORDER by tbl_os.sua_os, tbl_posto_fabrica.codigo_posto desc";
	echo nl2br($sql);

	$res                  = pg_query($con,$sql);
	$qtde_registros       = pg_num_rows($res);
	$numero_max_registros = 5000;//voltar para 500 depois do teste

	if ($qtde_registros > 0) {

		$data = date ("dmY");

		if ($login_fabrica == 3){
			$extensao = "zip";
		}else{
			$extensao = "xls";
		}

		echo "<p id='id_download' style='display:none'><img src='imagens/excell.gif'> 
		<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>
		<a href='xls/relatorio_auditoria_previa-$login_fabrica-$login_admin.$extensao'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL </font></a>
		</p>";

		#Não mostra quando tem mais de 500 linhas  - Fabio
		if ($qtde_registros < $numero_max_registros){
			echo "<table width='700' align='center'  border='0' cellspacing='1' cellpadding='2' bgcolor='#596D9B'  style='font-family: verdana; font-size: 10px'>";
			echo "<tr class='menu_top2'>";
			echo "<td nowrap><font color='#FFFFFF'>OS</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>EXTRATO</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>CÓDIGO POSTO</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>NOME POSTO</FONT></td>";/*hd 4790 011007 takashi*/
			echo "<td nowrap><font color='#FFFFFF'>TIPO</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>REFERÊNCIA</FONT></td>";/*hd 4790 011007 takashi*/
			echo "<td nowrap><font color='#FFFFFF'>DESCRIÇÃO</FONT></td>";/*hd 4790 011007 takashi*/
			if($login_fabrica==3){
				echo "<td nowrap><font color='#FFFFFF'>SÉRIE</FONT></td>";
			}
			echo "<td nowrap><font color='#FFFFFF'>MÃO-DE-OBRA</FONT></td>";/*hd 4790 011007 takashi*/
			echo "<td nowrap><font color='#FFFFFF'>TIPO AUDITORIA</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>DATA DIGITAÇÃO</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>DATA AUDITORIA(Liberada)</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>ADMIN</FONT></td>";
			echo "<td nowrap><font color='#FFFFFF'>SITUAÇÃO</FONT></td>";
			echo "</tr>";
		}else{
			echo "O relatório foi gerado no modo excel! Motivo: retorno de linhas superior ao funcionamento estável do navegador!";
		}


		echo `rm /tmp/assist/relatorio_auditoria_previa-$login_fabrica-$login_admin.htm`;

		$fp = fopen ("/tmp/assist/relatorio_auditoria_previa-$login_fabrica-$login_admin.htm","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE OSS QUE SOFRERAM AUDITORIA PRÉVIA - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>");
		fputs ($fp,"<tr class='Titulo'>");
		fputs ($fp,"<td >OS</td>");
		fputs ($fp,"<td >EXTRATO POSTO</td>");
		fputs ($fp,"<td >CÓDIGO POSTO</td>");
		fputs ($fp,"<td >NOME POSTO</td>");
		fputs ($fp,"<td >TIPO</td>");
		fputs ($fp,"<td >REFERÊNCIA</td>");
		fputs ($fp,"<td >DESCRIÇÃO</td>");
		if($login_fabrica==3){
			fputs ($fp,"<td >SÉRIE</td>");
		}
		fputs ($fp,"<td >MÃO-DE-OBRA</td>");
		fputs ($fp,"<td >TIPO AUDITORIA</td>");
		fputs ($fp,"<td >DATA DIGITAÇÃO</td>");
		fputs ($fp,"<td >DATA AUDITORIA(Liberada)</td>");
		fputs ($fp,"<td >ADMIN</td>");
		fputs ($fp,"<td >SITUAÇÃO<br>OS</td>");
		fputs ($fp,"</tr>");

		for ($i = 0 ; $i < $qtde_registros ; $i++) {
			$os                   = pg_fetch_result($res,$i,os);
			$sua_os               = pg_fetch_result($res,$i,sua_os);
			$codigo_posto         = pg_fetch_result($res,$i,codigo_posto);
			$nome_posto           = pg_fetch_result($res,$i,nome);
			$consumidor_revenda   = pg_fetch_result($res,$i,consumidor_revenda);
			$tipo_auditoria       = pg_fetch_result($res,$i,tipo_auditoria);
			$login                = pg_fetch_result($res,$i,login);
			$mao_de_obra_desconto = pg_fetch_result($res,$i,mao_de_obra_desconto);
			$mao_de_obra          = pg_fetch_result($res,$i,mao_de_obra);
			$mo_extrato           = pg_fetch_result($res,$i,mo_extrato);
			$data_liberacao       = pg_fetch_result($res,$i,data_liberacao);
			$observacao           = pg_fetch_result($res,$i,observacao);
			$situacao             = pg_fetch_result($res,$i,situacao);
			$status_os            = pg_fetch_result($res,$i,status_os);
			$extrato              = pg_fetch_result($res,$i,extrato);
			$data_geracao         = pg_fetch_result($res,$i,data_geracao);
			$os_cancelada         = pg_fetch_result($res,$i,os_cancelada);
			$produto_referencia   = pg_fetch_result($res,$i,produto_referencia);/*hd 4790 011007 takashi*/
			$produto_descricao    = pg_fetch_result($res,$i,produto_descricao);/*hd 4790 011007 takashi*/
		//	$produto_descricao    = substr($produto_descricao,0,15);/*hd 4790 011007 takashi*/
			$serie                = pg_fetch_result($res,$i,serie);
			$data_digitacao       = pg_fetch_result($res,$i,data_digitacao);


			if($consumidor_revenda=="C"){
			$consumidor_revenda="OS Consumidor";
			}
			
			if($consumidor_revenda=="R"){
			$consumidor_revenda="OS Revenda";
			}

			if(strlen($data_geracao)>0 and strlen($situacao) == 0)  {
				$situacao = "OS Aprovada";
			}
			
			# 190664
			$mao_de_obra = ($mo_extrato > 0) ? $mo_extrato : $mao_de_obra;

			if(strlen($mao_de_obra)==0){
				$mao_de_obra_desconto = 0;
			}

			if(strlen($mao_de_obra_desconto)==0){
				$mao_de_obra_desconto = 0;
			}

			$temp = ($mao_de_obra - $mao_de_obra_desconto );
			
			if(strlen(trim($observacao)) == 0) {
				$observacao = $tipo_auditoria;
			}
			
			if($status_os == '' and strlen($data_liberacao)>0 )     $situacao = "OS Aprovada";

			// HD 190664 quando o valor de tbl_os.mao_de_obra estar nulo, tambem tem que ver se tbl_os_extra.mao_de_obra estar nulo ou não, a função fn_auditoria_previa_admin na os_press.php quando faz, seta valor na tbl_os_extra.mao_de_obra_desconto, então quando a situação é OS Aprovada sem Mão-de-obra, esse campo não pode ser nulo.

			//IGOR HD 3612- estava com 	if($status_os==19 and $temp==0) 
			// Quando o desconto era de 20 e a mao de obra era 10, aparecia errado pois estava negativo
			// Quando o desconto era 10 e a mao de obra é 0, entao temp fica como 10 - ERRADO pois não entra na condição
			if ($status_os == 19 and $mao_de_obra <= 0) {
				$situacao = "OS Aprovada sem Mão-de-obra";
			} else {
				if ($status_os == 19 and $temp <= 0) {
					$situacao = "OS Aprovada sem Mão-de-obra";
				}
			}

			//echo "<br>1º OS: $sua_os - mao_de_obra_desconto :$mao_de_obra_desconto - mao_de_obra:$mao_de_obra- status_os:$status_os - temp:$temp - situacao:$situacao";

			if($os_cancelada == 't') $situacao = "OS Cancelada";
			//$situacao.= " - STATUS: $status_os -mo: $mao_de_obra - desc: mao_de_obra_desconto - tmp: $temp";
			if($situacao <> 'OS Aprovada') {
				$mao_de_obra = 0;
			}

			$cor =  ($i % 2 == 0) ? '#efeeea' : '#d2d7e1';

			//echo "<br> 2º OS: $sua_os - status_os:$status_os - temp:$temp - situacao:$situacao <br>";

			#Não mostra quando tem mais de 500 linhas  - Fabio
			if ($qtde_registros < $numero_max_registros){
				echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td nowrap align='center'><a href='os_press.php?os=$os'>$sua_os</a></td>";
				echo "<td nowrap align='left'>$data_geracao</td>";
				echo "<td nowrap align='left'>$codigo_posto</td>";
				echo "<td nowrap align='left'>$nome_posto</td>";/*hd 4790 011007 takashi*/
				echo "<td nowrap align='left'>$consumidor_revenda</td>";
				echo "<td nowrap align='left'>$produto_referencia</td>";/*hd 4790 011007 takashi*/
				echo "<td nowrap align='left'>$produto_descricao</td>";/*hd 4790 011007 takashi*/
				if($login_fabrica==3){
					echo "<td nowrap align='left'>$serie</td>";
				}
				echo "<td nowrap align='center'>R$".number_format($mao_de_obra,2,',','.')."</td>";/*hd 4790 011007 takashi*/
				echo "<td nowrap align='center'>$observacao</td>";
				echo "<td nowrap align='center'>$data_digitacao</td>";
				echo "<td nowrap align='center'>$data_liberacao</td>";
				echo "<td nowrap align='left'>$login</td>";
				echo "<td nowrap align='left'>$situacao</td>";
				echo "</tr>";
			}

			fputs ($fp,"<tr class='Conteudo'>");
			fputs ($fp,"<td bgcolor='$cor' >$sua_os</td>");
			fputs ($fp,"<td bgcolor='$cor' >$data_geracao</td>");
			fputs ($fp,"<td bgcolor='$cor' >$codigo_posto</td>");
			fputs ($fp,"<td bgcolor='$cor' >$nome_posto</td>");
			fputs ($fp,"<td bgcolor='$cor' >$consumidor_revenda</td>");
			fputs ($fp,"<td bgcolor='$cor' >$produto_referencia</td>");
			fputs ($fp,"<td bgcolor='$cor' >$produto_descricao</td>");
			if($login_fabrica==3){
				fputs ($fp,"<td bgcolor='$cor' >$serie</td>");
			}
			fputs ($fp,"<td bgcolor='$cor'>R$".number_format($mao_de_obra,2,',','.')."</td>");
			fputs ($fp,"<td bgcolor='$cor'>$observacao</td>");
			fputs ($fp,"<td bgcolor='$cor'>$data_digitacao</td>");
			fputs ($fp,"<td bgcolor='$cor'>$data_liberacao</td>");
			fputs ($fp,"<td bgcolor='$cor'>$login</td>");
			fputs ($fp,"<td bgcolor='$cor'>$situacao</td>");
			fputs ($fp,"</tr>");
		}
		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose($fp);

		echo `rm /www/assist/www/admin/xls/relatorio_auditoria_previa-$login_fabrica-$login_admin.xls`;
		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_auditoria_previa-$login_fabrica-$login_admin.xls /tmp/assist/relatorio_auditoria_previa-$login_fabrica-$login_admin.htm`;

		if ($login_fabrica == 3){
			# HD 33792 - zipando o arquivo
			$arqdir = "/www/assist/www/admin/xls/";
			$arqnome = "relatorio_auditoria_previa-$login_fabrica-$login_admin";

			echo `cd $arqdir; rm -rf $arqnome.zip; zip -o $arqnome.zip $arqnome.xls > /dev/null ; mv  $arqnome.zip $arqdir `; 
		}

		#Não mostra quando tem mais de 500 linhas  - Fabio
		if ($qtde_registros < $numero_max_registros){
			echo "</table>";
		}
		echo "<script language='javascript'>";
		echo "document.getElementById('id_download').style.display='block';";
		echo "</script>";
		flush();
		echo "<br>";
	}

	if ($qtde_registros > 0){
		echo "<br>";
		echo "<font size='2'>Total de registros: <b>$qtde_registros</b></font>";
	}
}

echo "<br>";

include "rodape.php";
?>
