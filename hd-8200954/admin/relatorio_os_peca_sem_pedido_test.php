<!-- RELATÓRIO CRIADO EM 22/01/2010 (ATENDENDO HD 188352) (EDUARDO)-->
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
		if (pg_numrows ($res) > 0){
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
$title = "Gerência -  Relatório de Ordens de Serviços Sem Pedido Gerado";

include 'cabecalho.php';?>

<style type="text/css">
.Titulo{
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}
.ConteudoBranco{
	font-family: Arial;
	font-size: 11px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>

<?include "javascript_pesquisas.php"; ?>

<? include "javascript_calendario.php"; ?>

<script type="text/javascript" src="js/jquery.tablesorter.pack.js">
</script> 

<script>
	$(document).ready(function(){
		$.tablesorter.defaults.widgets = ['zebra'];
		$("#relatorio").tablesorter();
	});
	function antecipaPedido(linha){
		var cache = new Date();
		cache = cache.getTime();
		var os = $('#selecao_'+linha).val();
//		if ($('#selecao_'+linha).attr('checked')){
			requisicaoHTTP('GET','relatorio_os_peca_sem_pedido_ajax.php?linha='+linha+'&os='+os+'&cachebypass='+cache, true , 'antecipa');
//		}else{
//			alert('Para antecipar marque a OS: ' + os);
//		}
	}

	function antecipa(campos){
		if (campos == 'ok'){
			alert('OS sera gerada antecipadamente');
			document.getElementById('btn_antecipar').disabled = true;
		}
	}
</script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'>
</script>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script type='text/javascript' src='js/jquery.bgiframe.min.js'>
</script>

<script type='text/javascript' src='js/dimensions.js'>
</script>

<script language="javascript">
	function chamaAjax(linha,data_inicial,data_final,posto,produto,cache){
		if (document.getElementById('div_sinal_' + linha).innerHTML == '+'){
			requisicaoHTTP('GET','mostra_os_peca_sem_pedido_ajax.php?linha='+linha+'&data_inicial='+data_inicial+'&data_final='+data_final+'&posto='+posto+'&produto='+produto+'&cachebypass='+cache, true , 'div_detalhe_carrega');
		}else{
			document.getElementById('div_detalhe_' + linha).innerHTML = "";
			document.getElementById('div_sinal_' + linha).innerHTML = '+';
		}
	}
	function load(linha){
		document.getElementById('div_detalhe_' + linha).innerHTML = "<img src='a_imagens/ajax-loader.gif'>";
	}
	function div_detalhe_carrega (campos){
		campos_array = campos.split("|");
		linha = campos_array [0];
		document.getElementById('div_detalhe_' + linha).innerHTML = campos_array[1];
		document.getElementById('div_sinal_' + linha).innerHTML = '-';
	}
</script>

<script language='javascript' src='ajax.js'>
</script>

<script type="text/javascript" src="js/bibliotecaAJAX.js">
</script>

<?
	$data_inicial = $_POST['data_inicial_01'];
	$data_final   = $_POST['data_final_01'];
?>

<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
	<input type="hidden" name="acao">
	<table width="500px" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Titulo">
			<td colspan="4">
				Preencha os campos para realizar a pesquisa.
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'>
			</td>
			<td width='40%' align='left'>
				Data Inicial
			</td>
			<td width='40%' align='left'>
				Data Final
			</td>
			<td width='10%'>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'>
			</td>
			<td width='40%' align='left'>
				<input size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
			</td>
			<td width='40%' align='left'>
				<input size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
			</td>
			<td width='10%'>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'>
			</td>
			<td width='40%' align='left' >
				Código Posto
			</td>
			<td width='40%' align='left' >
				Nome Posto
			</td>
			<td width='10%'>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td width='10%'>
			</td>
			<td width='40%' align='left'>
				<input type='text' name='codigo_posto' id='codigo_posto' size='15' value='<? echo $codigo_posto ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
			</td>
			<td width='40%' align='left'>
				<input type='text' name='posto_nome' id='posto_nome' size='25' value='<? echo $posto_nome ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
			</td>
			<td width='10%'>
			</td>
		</tr>
		<tr bgcolor="#D9E2EF">
			<td colspan="4" align="center">
				<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
			</td>
		</tr>
	</table>

<?
$btn_acao = $_POST['acao'];
if(strlen($btn_acao)>0) {
	flush();
	$referencia		= $_POST['referencia'];
	$descricao		= $_POST['descricao'];
	if (strlen($erro) == 0){
		if (strlen($_POST["data_inicial_01"]) == 0 or $_POST["data_inicial_01"]=='dd/mm/aaaa') {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		if (strlen($erro) == 0){
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0){
				$erro = pg_errormessage ($con) ;
			}
			if (strlen($erro) == 0){
				$aux_data_inicial = @pg_result ($fnc,0,0);
			}
		}
	}
	if (strlen($erro) == 0){
		if (strlen($_POST["data_final_01"]) == 0 or $_POST["data_final_01"] == 'dd/mm/aaaa'){
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		if (strlen($erro) == 0){
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			if (strlen ( pg_errormessage ($con) ) > 0){
				$erro = pg_errormessage ($con) ;
			}
			if (strlen($erro) == 0){
				$aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}
	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome   = $_POST['posto_nome'];
	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond_1 = "tbl_os.posto = $posto ";
		}
	}
	$cond_2 = " 1=1 ";
	if(strlen($referencia)>0){
		$sql = "select produto from tbl_produto join tbl_linha using(linha) where tbl_produto.referencia='$referencia' and tbl_linha.fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
		 $produto = pg_result($res,0,0);
		 $cond_2 = "tbl_os.produto = $produto ";
		}
	}
	if (strlen($erro) == 0) {
		$sql = "SELECT
					tbl_posto_fabrica.codigo_posto					  ,
					tbl_posto.fone 					as 		fone_posto,
					tbl_posto.nome 					as 		nome_posto,
					count(*) 						as 		qtde
				FROM tbl_os
					JOIN tbl_os_produto			on tbl_os.os 					= tbl_os_produto.os
					JOIN tbl_os_item 			on tbl_os_produto.os_produto 	= tbl_os_item.os_produto
					JOIN tbl_produto 			on tbl_os.produto 				= tbl_produto.produto
					JOIN tbl_peca 				on tbl_os_item.peca 			= tbl_peca.peca
					JOIN tbl_posto 				on tbl_os.posto 				= tbl_posto.posto
					JOIN tbl_servico_realizado 	on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca = 'f' and tbl_servico_realizado.gera_pedido = 't'
					JOIN tbl_posto_fabrica 	on tbl_posto_fabrica.posto 		= tbl_posto.posto and tbl_posto_fabrica.fabrica=$login_fabrica 
				Where tbl_os_item.pedido is null 
					and tbl_os.fabrica = $login_fabrica
					and $cond_1 
					and $cond_2 
					and tbl_os.data_abertura > '2009-01-01' 
					and tbl_os.data_abertura between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' 
					group by tbl_posto_fabrica.codigo_posto, tbl_posto.fone, tbl_posto.nome 
					order by qtde desc";

		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0){
			echo "<BR><BR><font size='1' face='verdana'>$referencia - $descricao ";
			echo $_POST["data_inicial_01"]; 
			echo " até ";
			echo $_POST["data_final_01"]; 
			echo "</font><BR><table border='1' cellpadding='1' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
			echo "<tr class='Titulo'>";
			echo "<td ></td>";
			echo "<td >Código</td>";
			echo "<td >Posto</td>";
			if ($login_fabrica == 81) {
				echo "<td>Fone</td>";
			}
			echo "<td >Qtde</td>";
			echo "</tr>";
			$total = pg_numrows($res);
			$total_pecas = 0;
			if ($produto == ''){
				$produto = "0";
			}
			for ($i=0; $i<pg_numrows($res); $i++){
				$nome						= trim(pg_result($res,$i,nome_posto));
				$codigo_posto				= trim(pg_result($res,$i,codigo_posto));
				$fone						= trim(pg_result($res,$i,fone_posto));
				$qtde						= trim(pg_result($res,$i,qtde));
				if($cor=="#F1F4FA")
					$cor = '#F7F5F0';
				else
					$cor = '#F1F4FA';
				$total_pecas	= $total_pecas + $qtde;
				echo "<tr>";
				echo "<td onMouseOver='this.style.cursor=\"pointer\" ; this.style.background=\"#cccccc\"'  onMouseOut='this.style.backgroundColor=\"#ffffff\" ' onClick=\"load($i);chamaAjax($i,'$aux_data_inicial','$aux_data_final','$codigo_posto',$produto,'$cachebypass')\" ><div id=div_sinal_$i>+</div></td>";
				echo "<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>";
				echo "<td bgcolor='$cor' align='left' nowrap>$nome</td>";
				if ($login_fabrica == 81){
					echo "<td bgcolor='$cor' nowrap>$fone_posto</td>";
				}
				echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
				echo "</tr>";
				echo "<tr><td colspan='4'>";
				echo "<div id='div_detalhe_$i'></div>";
				echo "</td></tr>";
			}
			echo "<tr class='Conteudo'>";
			echo "<td colspan='3'><B>Total</b></td>";
			echo "<td >$total_pecas</td>";
			echo "</tr>";
			echo "</table>";
		}else{
			echo "<br><center>Nenhum resultado encontrado</center>";
		}
	}
}

if (strlen($erro) > 0){?>
	<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
		<tr>
			<td align="center" class='error'>
				<? echo $erro ?>
			</td>
		</tr>
	</table>
	<?
}
include "rodape.php" ;
?>