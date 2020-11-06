<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$msg_erro = "";


# função para buscar o fornecedor por AJAX
if (strlen($_GET['q'])>0){
	$string = trim($_GET['q']);

	if (strlen($string)>0){
		$sql= "SELECT	tbl_pessoa.pessoa, 
						tbl_pessoa.nome,
						tbl_pessoa.cidade 	
				FROM tbl_pessoa
				WHERE tbl_pessoa.nome ilike '%$string%'
				AND   tbl_pessoa.empresa=$login_empresa
				ORDER BY tbl_pessoa.nome ;";
		$res = pg_exec ($con,$sql);
		$numero = pg_numrows ($res);
		for ( $i = 0 ; $i < $numero ; $i++ ) {
			$pessoa	= trim(pg_result($res,$i,pessoa));
			$nome	= trim(pg_result($res,$i,nome));
			echo "$nome|$pessoa\n";
		}
	}
	exit;
}


if(strlen($_POST["btn_acao"]) > 0 AND $_POST["btn_acao"]=='BAIXAR_LOTE') {
	$cont_itens= count($_POST["pagar"]);
	$data_baixa= $_POST["data_baixa"];

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	if(strlen($data_baixa)==0){
		$msg_erro = "Informe a data da baixa!";
	}

	if(strlen($msg_erro)==0){
		$data_baixa = "'" . substr ($data_baixa,6,4) . "-" . substr ($data_baixa,3,2) . "-" . substr ($data_baixa,0,2) . "'" ;
		for($i=0 ; $i< $cont_itens; $i++){
			$ct_pagar= $_POST["pagar"][$i];
			if(strlen($ct_pagar)>0){
				$sql = "UPDATE tbl_pagar SET 
							pagamento	= $data_baixa,
							valor_pago	= valor
						WHERE pagar = $ct_pagar
						AND empresa = $login_empresa
						AND loja = $login_loja;";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}
	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Os documentos selecionados foram baixados com sucesso!";
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_erro .= "Erro ao executar a baixa! Tente novamente!";
	}
}

if($_POST['btn_acao']=='pesquisar' AND $_GET['pesquisar']=='sim') {

	$cliente		= trim($_POST["cliente"]);
	$documento		= trim($_POST["documento"]);
	$vencimento		= trim($_POST["vencimento"]);
	$valor			= trim($_POST["valor"]);
	$filtro			= trim($_POST["filtro"]);
	$dias			= trim($_POST["dias"]);

	if (strlen($dias)==0){
		$dias = 5;
	}

	$resposta .="<form name='baixar_selecao' method='post' action='$PHP_SELF'>";
	$resposta .="<table border='0' cellpadding='10' cellspacing='0' style='' class='table_line' bordercolor='#d2e4fc'  align='center' width='750'>";
	$resposta .="<thead>";
	$resposta .="<tr >";
	$resposta .="<td colspan='9' align='center' ><font size='2' color='#000000'><b>CONTAS A PAGAR</b></font></td>";
	$resposta .="</tr>";
	$resposta .="<tr class='Titulo2'>";
	$resposta .="<td><b>Baixa</b></td>";
	$resposta .="<td><b>Fornecedor</b></td>";
	$resposta .="<td><b>Documento</b></td>";
	$resposta .="<td align='center'><b>Faturamento</b></td>";
	$resposta .="<td><b>Vencimento</b></td>";
	$resposta .="<td align='center'><b>Valor</b></td>";
	$resposta .="<td align='center'><b>Valor Hoje</b></td>";
	$resposta .="<td align='center'><b>Ações</b></td>";
	$resposta .="<td align='center'><b>Status</b></td>";
	$resposta .="</tr>";
	$resposta .="</head>";
	
	$sql="SELECT pagar,
				documento,
				faturamento,
				vencimento,
				TO_CHAR(vencimento,'dd/mm/yyyy') as vencimento_,
				case when current_date - vencimento >0 then current_date - vencimento  else 0 end as dias_vencido,
				replace(cast(cast(valor as numeric(12,2)) as varchar(14)),'.', ',') as valor,
				valor as valor2,
				tbl_pagar.pessoa_fornecedor,
				tbl_pessoa.nome,
				valor_multa,
				valor_juros_dia,
				valor_desconto,
				desconto_pontualidade,
				case when protesto is null then 'nao' else 'sim' end as protesto,
				case when current_date >= protesto then 'protestado' else 'aindanao' end as protesto2,
				valor_custas_cartorio,
				current_date - vencimento as dias_vencido
		FROM tbl_pagar
		join tbl_pessoa ON tbl_pessoa.pessoa = tbl_pagar.pessoa_fornecedor
		WHERE tbl_pagar.loja = $login_loja
		AND tbl_pagar.empresa=$login_empresa
		AND tbl_pagar.valor_pago IS NULL

		";

	if (strlen($cliente)>0){
		$sql .= " AND tbl_pessoa.nome like upper('%$cliente%') ";
	}

	if (strlen($documento)>0){
		$sql .= " AND tbl_pagar.documento like '%$documento%'";
	}

	if (strlen($vencimento)>0){
		$sql .= " AND to_char(tbl_pagar.vencimento,'DD/MM/YYYY') = '$vencimento'";
	}

	if (strlen ($valor) > 0) {
		$valor_busca = str_replace(",",".",$valor);
		$sql .= " AND tbl_pagar.valor = $valor";
	}

	if (strlen($filtro)>0){
		if ($filtro=="todos"){
			#nao faz filtro
			$sql .= " ";
		}
		if ($filtro=="vencidos"){
			$sql .= " AND tbl_pagar.vencimento < CURRENT_DATE ";
		}
		if ($filtro=="vencer3"){
			$sql .= " AND CURRENT_DATE + INTERVAL '$dias day' > tbl_pagar.vencimento::date";
		}
		if ($filtro=="vencer5"){
			$sql .= " AND CURRENT_DATE + INTERVAL '$dias day' > tbl_pagar.vencimento::date ";
		}
	}

	$sql .= " ORDER BY tbl_pagar.vencimento ASC";

	$res = pg_exec($con,$sql);
	$cont_itens = pg_numrows($res);

	if($cont_itens>0){
		for ( $i = 0 ; $i < $cont_itens ; $i++ ) {
			$pagar			= trim(pg_result($res, $i, pagar));
			$documento		= trim(pg_result($res, $i, documento));
			$faturamento	= trim(pg_result($res, $i, faturamento));
			$fornecedor		= trim(pg_result($res, $i, pessoa_fornecedor));
			$nome			= trim(pg_result($res, $i, nome));
			$vencimento		= trim(pg_result($res, $i, vencimento));
			$vencimento_	= trim(pg_result($res, $i, vencimento_));
			$valor			= trim(pg_result($res, $i, valor));
			$valor2			= trim(pg_result($res, $i, valor2));

			$valor_multa	= trim(pg_result($res, $i, valor_multa));
			$valor_juros_dia= trim(pg_result($res, $i, valor_juros_dia));
			$valor_desconto	= trim(pg_result($res, $i, valor_desconto));
			$desconto_pontualidade	= trim(pg_result($res, $i, desconto_pontualidade));

			$protesto		= trim(pg_result($res, $i, protesto));
			$protesto_aux	= trim(pg_result($res, $i, protesto2));
			$valor_custas_cartorio	= trim(pg_result($res, $i, valor_custas_cartorio));

			$dias_vencido	= trim(pg_result($res, $i, dias_vencido));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			if ($dias_vencido>0 AND $protesto=='SIM'){
				$cor ='#FFD700';
				if ($protesto_aux=='PROTESTADO'){
					$cor = '#EE2C2C';
				}
			}

			if (strlen($valor_custas_cartorio)==0){
				$valor_custas_cartorio=0;
			}

			// para calcular a quantidade a pagar com juros e multa

			$valor_reajustado = $valor2;

			if ($desconto_pontualidade<>'t'){
				$valor_reajustado -= $valor_desconto;
			}
			if ($dias_vencido<=0 AND $desconto_pontualidade=='t'){
				$valor_reajustado -= $valor_desconto;
			}
			if ($dias_vencido>0){
				$valor_reajustado += $valor_multa;
				$valor_reajustado += $valor_juros_dia*$dias_vencido;
				$valor_reajustado += $valor_custas_cartorio;
			}
			$valor_reajustado = number_format($valor_reajustado,2, ',', '');


			$valor_aux = str_replace(",",".",$valor);
	
			$resposta .="<tr bgcolor='$cor' class='linha' id='linha_$i'>";

			$resposta .="<td nowrap align='center'><input type='checkbox' name='pagar[]' id='pagar$i' value='$pagar' onClick='calcula_total_selecionado($cont_itens)' class='check_normal'></td>";
			
			if (strlen($nome)>29) $nome = substr($nome,0,29)."...";
			
			$resposta .="<td nowrap align='left'>$fornecedor - $nome</td>";

			$resposta .= "<td nowrap>"; 
			$resposta .= "<a href=\"javascript:abrirConta('$pagar','$nome')\">$documento</a>";
			$resposta .= "</td>";

			$resposta .="<td nowrap  align='center'>";

			if(strlen($faturamento) > 0)	$resposta .= "$faturamento";
			else							$resposta .= "-";

			$resposta .="</td>";
			$resposta .="<td nowrap>$vencimento_</td>";
			$resposta .="<td nowrap align='right' onclick=\"javascript:selecionarLinha($i,'$cor')\" style='cursor:pointer'>";
			$resposta .="<input type='hidden' name='pagar_$i' id='pagar_$i' value='$valor_aux' align='right'>R$ $valor</td>";
			$resposta .="<td nowrap align='right'>R$ $valor_reajustado</td>";

			$resposta .="<td nowrap align='center'>";
			$resposta .= "<a href=\"javascript:abrirConta('$pagar','$nome')\">Ver</a>";
			$resposta .= "</td>";

			if($vencimento < date('Y-m-d')) $st="<font color='red'>vencido</font>";
			else							$st="a vencer";

			$resposta .="<td nowrap align='center'> $st</td>";
			$resposta .="</tr>";
		}
		$resposta .="</tbody>";
		$resposta .="<foot>";
		$resposta .="<tr>";
		$resposta .="<td colspan='9' align='right'>";
		$resposta .="<b>Total:</b>";
		$resposta .="<input type='hidden' id='cont_itens' name='cont_itens' value='$cont_itens' size='4'> ";
  		$resposta .="<input type='text' id='resultado' name='resultado' size='10' value='0' class='frm' read-only> ";
		$resposta .="</td>";
		$resposta .="</tr>";

		$resposta .="<tr class='Titulo3'>";
		$resposta .="<td colspan='4' align='left'>";
		$resposta .="Data da Baixa:<input type='text' name='data_baixa' value='".date('d/m/Y')."' size='12' class='frm'> ";
		$resposta .="</td>";
		$resposta .="<td colspan='5' align='right'>";
		$resposta .="<input type='hidden' name='btn_acao' value=''> ";
		$resposta .="<input type='button' name='baixar_sel' value='Baixar Selecionados' class='frm' onclick=\"document.baixar_selecao.btn_acao.value='BAIXAR_LOTE';document.baixar_selecao.submit(); \"> ";
		$resposta .="</td>";
		$resposta .="</tr>";

//		$resposta .="<tr>";
//		$resposta .="<td colspan='9' align='right'>";
//		$resposta .="<input type='submit' name='baixar_sel' value='Baixar Selecionados' class='frm'> ";
//		$resposta .="</td>";
//		$resposta .="</tr>";
		$resposta .="</tfoot>";
	}else{
		$resposta .="<tr bgcolor='#F7F5F0'><td colspan='10' align='center'><b>Sem Contas a Pagar Pendentes!&nbsp;</b></td></tr>";
	}
	$resposta .="</table>";
	$resposta .="</form>";
	
	echo $resposta;
	exit;
}

$title = "Contas a Pagar";
include 'menu.php';
//ACESSO RESTRITO AO USUARIO MASTER 
if (strpos ($login_privilegios,'financeiro') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}


?>

<script type="text/javascript">
	$(function(){
		$('div.demo').each(function() {
			 eval($('p', this).text());
		});
		$('#main p').wrap("<code></code>");
	});
</script>

	<style type="text/css">
	.Conteudo2 {
			font:12px "Segoe UI", Tahoma;	
	}
	h3 {
		font-size:16px;
		font-weight:bold;
	}

	input.botao {
		background:#ced7e7;
		color:#000000;
		border:2px solid #ffffff;
	}
	.borda {
		border-width: 2px;
		border-style: dotted;
		border-color: #000000;
	}
	.Titulo2 {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color:#6C87B7;
		border: 0px;
	}
	.Titulo3{
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #000000;
		background-color:#ABBAD6;
	}
	.table_line {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		color: #000000;
		border: 0px;
	}
	.border {
		border: 1px solid #ced7e7;
	}

	#boleto .topo{
		font-size:10px;
	/*	float:left;
		position:relative;
		font-size:10px; */
	}
	#boleto .campo{
		font-size:14px;
		font-weight:bold;
		text-align:right;
		float:right;
	}
	#boleto .campoL{
		font-size:14px;
		font-weight:bold;
	}

	.bloqueiado {
		border-color:#FFFFFF;
		background-color:#FFFFFF;
		color:#000000;
		font-size:12px;
		font-weight:bold;
	}

	input {
		BORDER-RIGHT: #888888 1px solid; 
		BORDER-TOP: #888888 1px solid; 
		FONT-WEIGHT: bold; 
		FONT-SIZE: 8pt; 
		BORDER-LEFT: #888888 1px solid; 
		BORDER-BOTTOM: #888888 1px solid; 
		FONT-FAMILY: Verdana, Arial, Helvetica, sans-serif; 
		BACKGROUND-COLOR: #f0f0f0
	}
	.check_normal{
		border:none;
	}
	tr.linha td {
		border-bottom: 1px solid #c0c0c0; 
		border-top: none; 
		border-right: none; 
		border-left: none; 
	}
	.demo{
		width:700px;
		background-color:#E2ECFE;
	}
</style>

<script language='javascript'>

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function selecionarLinha(id,cor){
	var com = document.getElementById('pagar'+id);
	var lin = document.getElementById('linha_'+id);
	if (com){
		if (com.checked==true){
			com.checked=false;
			lin.bgColor = cor;
		}
		else{
			com.checked=true;
			lin.bgColor = "#D3E9FE";
		}
	}
	calcula_total_selecionado(999);
}


// FUNÇÃO PARA FORMATAR O NUMERO PARA DECIMAL COM A QTD DE CASAS DESEJADA
function format_number(pnumber,decimals){ 
	if (isNaN(pnumber)) { return 0}; 
	if (pnumber=='') { return 0}; 
	 
	var snum = new String(pnumber); 
	var sec = snum.split('.'); 
	var whole = parseFloat(sec[0]); 
	var result = ''; 
	 
	if(sec.length > 1){ 
		var dec = new String(sec[1]); 
		dec = String(parseFloat(sec[1])/Math.pow(10,(dec.length - decimals))); 
		dec = String(whole + Math.round(parseFloat(dec))/Math.pow(10,decimals)); 
		var dot = dec.indexOf('.'); 
		if(dot == -1){ 
			dec += '.'; 
			dot = dec.indexOf('.'); 
		} 
		while(dec.length <= dot + decimals) { dec += '0'; } 
		result = dec; 
	} else{ 
		var dot; 
		var dec = new String(whole); 
		dec += '.'; 
		dot = dec.indexOf('.');         
		while(dec.length <= dot + decimals) { dec += '0'; } 
		result = dec.replace(".", ","); 
	}     
	return result; 	
} 

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO 
function calcula_total_selecionado(tot){
	//alert('passou aqui');
	var forn=0, lenPr = 0, len=0, soma = 0.0, somap = 0,testav=0, testap=0, conti=0;
	var cont_itens= document.getElementById('cont_itens').value;
	//alert(cont_itens);
	for (f=0; f<cont_itens;f++) { 
		if(document.getElementById('pagar_'+f).value==''){
			
		}else{
			if(document.getElementById('pagar'+f).checked == true){
				valor= parseFloat(document.getElementById('pagar_'+f).value);
				//SOMA VALOR 
				soma += valor; //format_number(valor,2);
			}
		}
	}
	soma = format_number(soma,2);
	soma = soma.toString().replace( ".", "," );
	document.getElementById('resultado').value= soma;
}

</script>

<script language='javascript'>

	function abrirConta(pagar,nome){
		$('#linkContaPagar').attr("href","contas_pagar_new_documento.php?btn_acao=abrirDocumento&conta_pagar="+pagar+"&keepThis=true&TB_iframe=true&height=550&width=750");
		$('#linkContaPagar').attr("title","Contas a Pagar do Cliente "+nome);
		$('#linkContaPagar').click();
	}

	$(document).ready(function() { 
		$('#frm_pesquisa').ajaxForm({ 
			target: '#dados',
			beforeSubmit: function(){
				document.getElementById('dados').innerHTML = "Carregando...<br><img src='imagens/carregar_os.gif' >";
					},
			success: function() { 
				$('#dados').fadeIn('slow'); 
				//alert('Pesquisa realizada!');
			}
		});
	});
</script>

<script language='javascript'>

	function findValue(li) {
		if( li == null ) return alert("Não encontrado");

		// if coming from an AJAX call, let's use the CityId as the value
		if( !!li.extra ) {
			var sValue = li.extra[1];
		}else{
			var sValue = li.selectValue;
		}
		$('#cliente').val(sValue);
	}

	$(document).ready(function() {
		$("#cliente").autocomplete(
			"<? echo $PHP_SELF ?>",
			{
				delay:10,
				minChars:4,
				matchSubset:1,
				matchContains:1,
				cacheLength:10,
				onItemSelect:function(li){findValue(li)},
				onFindValue:findValue,
				formatItem:function(row){return row[0]},
				autoFill:true
			}
		);
	});
</script>

<?
	if (strlen($msg)>0){
		echo "<center><h4>$msg</h4></center><br>";
	}
	if (strlen($msg_erro)>0){
		echo "<center><h4>$msg_erro</h4></center><br>";
	}
?>

<div class="demo" id='pesquisa'>
	<h1><img src='imagens/moedas.gif' alt='Adicionar Contas a Pagar' border='0' align='absmiddle'> Contas a Pagar</h1>
		<form method='POST' name='frm_pesquisa' id='frm_pesquisa' action='<? echo $PHP_SELF ?>?pesquisar=sim'>

		<table border='0' cellpadding='0' cellspacing='2'  bordercolor='##555555'  align='center' width='600px' class='table_line'>

				<tr>
				<td><b>Cliente</b></td>
				<td colspan='4'><input type='text' name='cliente' id='cliente' size='40' value=''></td>
				</tr>

				<tr>
				<td><b>Documento</b></td>
				<td colspan='4'><input type='text' name='documento' size='20' value=''></td>
				</tr>

				<tr>
				<td><b>Data Vencimento</b></td>
				<td colspan='4'><input type='text' name='vencimento' id='vencimento' size='11' maxlength='10' value=''></td>
				</tr>

				<tr>
				<td><b>Valor</b></td>
				<td colspan='4'><input type='text' name='valor' size='20' value=''></td>
				</tr>

				<tr>
				<td><input type='radio' name='filtro' value='todos'>Todos</td>
				<td><input type='radio' name='filtro' value='vencidos'>Vencidos</td>
				<td><input type='radio' name='filtro' value='vencer5'>A Vencer em 5 dias</td>
				<td><input type='text' name='dias' value='' size='5'>Dias</td>
				</tr>

				<tr>
				<td>&nbsp;</td>
				</tr>

				<tr>
					<td colspan='5' align='center'>
						<input type='hidden' name='btn_acao' value='pesquisar'>
						<input type='submit' name='btn_filtrar' value='Pesquisar'>
					</td>
				</tr>

		</table>
		</form>
	<p style='display:none'>$(this).corner("15px");</p>
</div>

<a href='contas_pagar_new_documento?btn_acao=abrirDocumento&keepThis=true&TB_iframe=true&height=550&width=750' id='linkContaPagar' title='Contas a Pagar' class='thickbox'></a>
<br>

<a href='contas_pagar_new_documento?btn_acao=abrirDocumento&keepThis=true&TB_iframe=true&height=540&width=750' title='Contas a Pagar' class='thickbox' style='font-size:14px'><img src='imagens/add.png' alt='Adicionar Contas a Pagar' border='0' align='absmiddle'> Novo Pagamento </a>

<!-- <a href='contas_pagar_cadastro_fornecedor.php?keepThis=true&TB_iframe=true&height=450&width=600' id='linkContaReceber' title='Contas a Pagar' class='thickbox'>Novo Fornecedor</a> -->

<br>
<br>

<DIV class='exibe' id='dados' align='center'></DIV>
</BODY>
</HTML>