<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$cachebypass=md5(time());

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>3){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

$layout_menu = "gerencia";
$title = "GERÊNCIA - RELATÓRIO DE ORDENS DE SERVIÇOS SEM TROCA DE PEÇA";

include 'cabecalho.php';
?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<?include "javascript_pesquisas.php"; ?>

<? include "javascript_calendario.php"; ?>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<script language="JavaScript">
function fnc_pesquisa_produto (campo, campo2, tipo) {

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}else {
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}
</script>

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



<script language="javascript">

function chamaAjax(linha,data_inicial,data_final,posto,produto,cache) {
	if (document.getElementById('div_sinal_' + linha).innerHTML == '+') {
	
		/* HD 353066 Alterado o caminho para versão de teste! */
		requisicaoHTTP('GET','mostra_os_sem_troca_ajax.php?linha='+linha+'&data_inicial='+data_inicial+'&data_final='+data_final+'&posto='+posto+'&produto='+produto+'&cachebypass='+cache, true , 'div_detalhe_carrega');
	}
	else
	{
		document.getElementById('div_detalhe_' + linha).innerHTML = "";
		document.getElementById('div_sinal_' + linha).innerHTML = '+';
	}

}

function load(linha) {
	document.getElementById('div_detalhe_' + linha).innerHTML = "<img src='a_imagens/ajax-loader.gif'>";
}

function div_detalhe_carrega (campos) {
	campos_array = campos.split("|");
	linha = campos_array [0];
	document.getElementById('div_detalhe_' + linha).innerHTML = campos_array[1];
	document.getElementById('div_sinal_' + linha).innerHTML = '-';
}
</script>

<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<?
$data_inicial = $_REQUEST["data_inicial"];
$data_final = $_REQUEST["data_final"];

$btn_acao = $_POST['acao'];

if(strlen($btn_acao)>0) {

	if (strlen($msg_erro) == 0) {
	
 		if(empty($data_inicial) OR empty($data_final)){
        	$msg_erro = "Data Inválida";
    	}

    	if(strlen($msg_erro)==0){
       		list($di, $mi, $yi) = explode("/", $data_inicial);
        	if(!checkdate($mi,$di,$yi)) 
            	$msg_erro = "Data Inválida";
   		}
   		
    	if(strlen($msg_erro)==0){
        	list($df, $mf, $yf) = explode("/", $data_final);
        	if(!checkdate($mf,$df,$yf)) 
            	$msg_erro = "Data Inválida";
    	}

    	if(strlen($msg_erro)==0){
        	$aux_data_inicial = "$yi-$mi-$di";
        	$aux_data_final = "$yf-$mf-$df";
    	}
    	
    	if(strlen($msg_erro)==0){
        	if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            	$msg_erro = "Data Inválida";
        	}
        }
        
        $referencia = $_POST['referencia'];
	    $descricao = $_POST['descricao'];

	    $codigo_posto = $_POST['codigo_posto'];
	    $posto_nome   = $_POST['posto_nome'];
		
	    $cond_2 = " 1=1 ";
	    if(strlen($referencia)>0 and strlen($msg_erro)==0){
		    $sql = "select produto from tbl_produto join tbl_linha using(linha) where tbl_produto.referencia='$referencia' and tbl_linha.fabrica = $login_fabrica";
		    $res = pg_exec($con,$sql);

		    if(pg_numrows($res)>0){
		     $produto = pg_result($res,0,0);
		     $cond_2 = " tbl_os.produto = $produto ";
		    }else{
		        $msg_erro .= "Produto não Encontrado";
		    }
		}    
		
		$cond_1 = " 1=1 ";
	    if(strlen($codigo_posto)>0 and strlen($msg_erro)==0){
		    $sql = "SELECT posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
		    $res = pg_exec($con,$sql);
		    if(pg_numrows($res)>0){
		    	$posto = pg_result($res,0,posto);
		    	$cond_1 = " tbl_os.posto = $posto ";
		    }else{
		        $msg_erro = "Posto não Encontrado";
		    }
		}    
	}

	if (strlen($msg_erro) > 0) {?>
		<table width="700" border="0" cellpadding="0" cellspacing="0" align='center'>
			<tr>
				<td align="center" class='msg_erro'>
					<? echo $msg_erro ?>
				</td>
			</tr>
		</table>
<?
	}
}
?>
	
<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class='formulario'>
	<tr class="titulo_tabela">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
			<td width='12%'></td>
			<td width='20%' align='left'>Data Inicial</td>
			<td width='60%' align='left'>Data Final</td>
			<td width='8%'></td>
	</tr>

	<tr>
		<td width='10%'></td>
		<TD ><INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>"></TD>
		<TD><INPUT size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>"></TD>
		<td width='10%'></td>
	</tr>

	<tr>
		<td width='10%'></td>
		<td>Código Produto</td>
		<td>Descrição Produto</td>
		<td width='10%'></td>
	</tr>
	<tr>
		<td width='10%'></td>
		<td>
		<input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="12" maxlength="20"><a href="javascript: fnc_pesquisa_produto (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'referencia')"><IMG SRC="imagens/lupa.png" style="cursor: pointer;" ></a></td>
		<td><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50"><a href="javascript: fnc_pesquisa_produto (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'descricao')"><IMG SRC="imagens/lupa.png" style="cursor: pointer;" ></a></td>
		<td width='10%'></td>
	</tr>
	<tr >
		<td width='10%'></td>
		<td>Código Posto</td>
		<td>Nome Posto</td>
		<td width='10%'></td>
	</tr>
	<tr>
		<td width='10%'></td>
		<td>
			<input type='text' name='codigo_posto' id='codigo_posto' size='12' value='<? echo $codigo_posto ?>' class='frm'>
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td>
			<input type='text' name='posto_nome' id='posto_nome' size='50' value='<? echo $posto_nome ?>' class='frm'>
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td width='10%'></td>
	</tr>
	<tr>
			<td colspan="4" align="center"><input value='Pequisar' type='button' onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

<?
if(strlen($btn_acao)>0 and strlen($msg_erro)==0) {
	flush();
	
	$sql = "select tbl_posto_fabrica.codigo_posto, tbl_posto.nome as nome_posto,
				count(*) as qtde
				from tbl_extrato
				join tbl_os_extra          on tbl_extrato.extrato                    = tbl_os_extra.extrato 
				join tbl_os                on tbl_os.os                              = tbl_os_extra.os
				join tbl_posto             on tbl_posto.posto                        = tbl_os.posto
				join tbl_posto_fabrica     on tbl_posto_fabrica.posto                = tbl_posto.posto and
											  tbl_posto_fabrica.fabrica=$login_fabrica 
				where tbl_extrato.fabrica=$login_fabrica
				and $cond_1 
				and $cond_2
				and tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' 
                and tbl_os_extra.os in     ( select tbl_os_produto.os  from tbl_os_produto
                                               join tbl_os_item           on tbl_os_item.os_produto =                                         tbl_os_produto.os_produto
                                               join tbl_servico_realizado on                                                 tbl_servico_realizado.servico_realizado =                        tbl_os_item.servico_realizado and
                                                            tbl_servico_realizado.troca_de_peca = 'f'
                                              where  tbl_os_produto.os = tbl_os_extra.os) 
                and tbl_os_extra.os not in ( select tbl_os_produto.os  from tbl_os_produto
                                               join tbl_os_item        on tbl_os_item.os_produto =                                         tbl_os_produto.os_produto
                                               join tbl_servico_realizado on                                                 tbl_servico_realizado.servico_realizado =                        tbl_os_item.servico_realizado and
                                                            tbl_servico_realizado.troca_de_peca = 't'
                                             where  tbl_os_produto.os = tbl_os_extra.os) 
				group by tbl_posto_fabrica.codigo_posto, tbl_posto.nome
				order by qtde desc";
				

	//echo nl2br($sql);
	//exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<BR><BR>";
		echo "<table class='tabela' border='0' cellpadding='1' cellspacing='1' align='center' width='700' >";
		echo "<tr>";
		echo "<td class='titulo_tabela' colspan='4'>";
			echo "$referencia - $descricao ";
			echo $data_inicial; 
			echo " até ";
			echo $data_final; 
		echo "</td>";
		echo "</tr>";
		
		echo "<tr class='titulo_coluna'>";
		echo "<td></td>";
		echo "<td>Código</td>";
		echo "<td>Posto</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		
		if ($produto == '') {
				$produto = "0";
		}

		for ($i=0; $i<pg_numrows($res); $i++){

			$nome                    = trim(pg_result($res,$i,nome_posto));
			$codigo_posto            = trim(pg_result($res,$i,codigo_posto));
			$qtde                    = trim(pg_result($res,$i,qtde));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			$total_pecas = $total_pecas + $qtde;
			echo "<tr>";

			echo "<td onMouseOver='this.style.cursor=\"pointer\" ; this.style.background=\"#cccccc\"'  onMouseOut='this.style.backgroundColor=\"#ffffff\" ' onClick=\"load($i);chamaAjax($i,'$aux_data_inicial','$aux_data_final',$codigo_posto,$produto,'$cachebypass')\" ><div id=div_sinal_$i>+</div></td>";
			echo "<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>";
			echo "<td bgcolor='$cor' align='left' nowrap>$nome</td>";
			echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
			echo "</tr>";
			echo "<tr><td colspan='4'>";
			echo "<div id='div_detalhe_$i'></div>";
			echo "</td></tr>";
		}
		
		echo "<tr class='titulo_coluna'>";
		echo "<td colspan='3'><B>Total</b></td>";
		echo "<td >$total_pecas</td>";
		echo "</tr>";
		echo "</table>";
	}else{
	    echo "<br><center>Nenhum resultado encontrado</center>";
	}

}
include "rodape.php" ;
?>
