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
	
	if(strlen($data_inicial) > 0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
    if(strlen($data_final) > 0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $x_data_inicial = "$yi-$mi-$di 00:00:00";
        $x_data_final = "$yf-$mf-$df 23:59:59";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($x_data_final) < strtotime($x_data_inicial)){
            $msg_erro = "Data Inválida.";
        }
    }

	

	if(strlen($msg_erro)==0){
		
		# HD 33792 - Francisco Ambrozio
		#   Aumentado período para 180 dias para a Britânia
		if ($login_fabrica == 3){
			if (strtotime($x_data_inicial) < strtotime($x_data_final . ' -6 month')) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 6 meses.';
			}
		}else{
			if (strtotime($x_data_inicial) < strtotime($x_data_final . ' -1 month')) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
			}
		}
	}
}


$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS QUE SOFRERAM AUDITORIA PRÉVIA";

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
	border-collapse: collapse;
	border:1px solid #596d9b;
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

<? 
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}
?>


<? if (strlen($msg_erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>


<form name='frm_relatorio' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='700' align='center' border='0' cellspacing='1' cellpadding='0' class='formulario'>
<tr>
	<td colspan='4' class="titulo_tabela">Parâmetros de Pesquisa</td>
</tr>
<tr><td>&nbsp;</td></tr>

<TR>
    <TD style="width: 10px">&nbsp;</TD>
	<TD align='left' width="200px" >Data Inicial</TD>
    <TD align='left'>Data Final</TD>

  </TR>
  	<tr class="menu_top">
		<td width="10">&nbsp;</td>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); ?>" class="frm">
		</td>
		<td>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); ?>"  class="frm">
		</td>

	</tr>

<tr>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left'>Código do Posto</td>
	<td align='left'>Nome do Posto</td>
</tr>
<tr>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left'><input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="13" value="<? echo $posto_codigo ?>"><img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'codigo')">
	</td>
	<td align='left'><input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>" ><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;">
	</td>
</tr>
<tr>
<TD style="width: 10px">&nbsp;</TD>
	<td align='left' colspan="2">
		<fieldset style="width:300px;">
			<legend>Tipo</legend>
			<input type='radio' name='tipo' <?if (strlen($tipo)==0 or $tipo=='auditoria') echo 'CHECKED';?> value='auditoria'>Auditoria &nbsp;&nbsp;
			<input type='radio' name='tipo' <?if ($tipo=='intervencao') echo 'CHECKED';?> value='intervencao'>Intervenção&nbsp;&nbsp;
			<input type='radio' name='tipo' <?if ($tipo=='ambas') echo 'CHECKED';?> value='ambas'>Ambas
		</fieldset>
	</td>
</tr>

<TR>
    <TD style="width: 25%">&nbsp;</TD>
	<TD align='left' colspan="2">
		<fieldset style="width:300px">
			<legend>Filtrar por</legend>
			<input type='radio' name='data_tipo' value='digitacao' <?if(strlen($data_tipo)==0 or $data_tipo =='digitacao') echo "CHECKED"; ?>>Data Digitação&nbsp;&nbsp;&nbsp;
			<input type='radio' name='data_tipo' value='auditoria' <?if($data_tipo =='auditoria') echo "CHECKED"; ?>>Data Auditoria(Liberada)	
		</fieldset>
	</TD>

  </TR>

<tr>
<TD colspan='4' align="center" style="padding:10px 0 10px;">
	<input type="button" style="cursor:pointer" onclick="javascript: if (document.frm_relatorio.btn_acao.value == '' ) { document.frm_relatorio.btn_acao.value='continuar' ; document.frm_relatorio.submit() } else { alert ('Aguarde submissão') }" value="Pesquisar" />
</TD>
</tr>
</table>

</form>

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
//	echo nl2br($sql);

	$res                  = pg_query($con,$sql);
	$qtde_registros       = pg_num_rows($res);
	$numero_max_registros = 500;

	if ($qtde_registros > 0) {

		$data = date ("dmY");

		if ($login_fabrica == 3){
			$extensao = "zip";
		}else{
			$extensao = "xls";
		}

		echo "<p id='id_download' style='display:none'><img src='imagens/excell.gif'> 
		<input type='button' value='Download em Excel' onclick=\"window.location='xls/relatorio_auditoria_previa-$login_fabrica-$login_admin.$extensao'\"
		</p>";

		#Não mostra quando tem mais de 500 linhas  - Fabio
		if ($qtde_registros < $numero_max_registros){
			echo "<table width='700' align='center'  border='0' cellspacing='1' cellpadding='0' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td nowrap>OS</td>";
			echo "<td nowrap>Extrato</td>";
			echo "<td nowrap>Código Posto</td>";
			echo "<td nowrap align='left'>Nome Posto</td>";/*hd 4790 011007 takashi*/
			echo "<td nowrap>Tipo</td>";
			echo "<td nowrap>Referência</td>";/*hd 4790 011007 takashi*/
			echo "<td nowrap align='left'>Descrição</td>";/*hd 4790 011007 takashi*/
			if($login_fabrica==3){
				echo "<td nowrap>Série</td>";
			}
			echo "<td nowrap>Mão-De-Obra</td>";/*hd 4790 011007 takashi*/
			echo "<td nowrap>Tipo Auditoria</td>";
			echo "<td nowrap>Data Digitação</td>";
			echo "<td nowrap>Data Auditoria(Liberada)</td>";
			echo "<td nowrap>Admin</td>";
			echo "<td nowrap align='left'>Situação</td>";
			echo "</tr>";
		}else{
			echo "Relatório Gerado em Excel devido ao retorno de linhas ser superior ao funcionamento estável do navegador!";
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

			// HD 190664 quando o valor de tbl_os.mao_de_obra estar nulo, tambem tem que ver se tbl_os_extra.mao_de_obra estar nulo ou não, a função fn_auditoria_previa_admin na os_press.php quando faz, seta valor na tbl_os_extra.mao_de_obra_desconto, então quando a situação é OS Aprovada sem Mão-de-obra, esse campo não pode ser nulo.

			//IGOR HD 3612- estava com 	if($status_os==19 and $temp==0) 
			// Quando o desconto era de 20 e a mao de obra era 10, aparecia errado pois estava negativo
			// Quando o desconto era 10 e a mao de obra é 0, entao temp fica como 10 - ERRADO pois não entra na condição
			if($status_os==19 and $mao_de_obra <=0){
				$situacao = "OS Aprovada sem Mão-de-obra";
			}else{
				if($status_os==19 and $temp<=0) {
					$situacao = "OS Aprovada sem Mão-de-obra ";
				}
			}

			//echo "<br>1º OS: $sua_os - mao_de_obra_desconto :$mao_de_obra_desconto - mao_de_obra:$mao_de_obra- status_os:$status_os - temp:$temp - situacao:$situacao";


			if($status_os == '' and strlen($data_liberacao)>0 )     $situacao = "OS Aprovada";
			if($os_cancelada == 't') $situacao = "OS Cancelada";
			//$situacao.= " - STATUS: $status_os -mo: $mao_de_obra - desc: mao_de_obra_desconto - tmp: $temp";
			if($situacao <> 'OS Aprovada') {
				$mao_de_obra = 0;
			}

			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			//echo "<br> 2º OS: $sua_os - status_os:$status_os - temp:$temp - situacao:$situacao <br>";

			#Não mostra quando tem mais de 500 linhas  - Fabio
			if ($qtde_registros < $numero_max_registros){
				echo "<tr bgcolor='$cor'>";
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
		echo "<font size='2'>Total de registros: <b>$qtde_registros</b></font>";
	}
}

echo "<br>";

include "rodape.php";
?>
