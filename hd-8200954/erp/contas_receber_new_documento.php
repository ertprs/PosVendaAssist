<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'funcoes.php';

$msg_erro = "";

# função para buscar o fornecedor por AJAX
if (strlen($_GET['q'])>0){
	$string = trim($_GET['q']);

	if (strlen($string)>0){
		$sql= "SELECT pessoa, 
					nome,
					cidade 	
				FROM tbl_pessoa
				WHERE nome ilike '%$string%'
				AND empresa=$login_empresa
				ORDER BY nome ;";
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

$acao = trim($_POST['btn_acao']);

if (strlen($acao)>0){

	$conta_receber= trim($_POST["conta_receber"]);
	$sacadoID	= trim($_POST["sacadoID"]);//fornecedor
	$faturamento= trim($_POST["faturamento"]); //faturamento
	$documento	= trim($_POST["nf"]);
	$boleto		= trim($_POST["boleto"]);
	$valor		= trim($_POST["valor"]);
	$vencimento	= trim($_POST["vencimento"]);
	$obs		= trim($_POST["obs"]);
	$caixa_banco= trim($_POST["caixa_banco"]);
	$imprimir_boleto = trim($_POST["obs"]);
	$mensagem	= "";



#### CADASTRAR E ALTERAR
	if($acao=="cadastrar" OR $acao=="alterar"){

			if (strlen ($vencimento) == 0) {
				$msg_erro .= '<br>Digite a data de vencimento.';
			}else{
				$xvencimento = fnc_formata_data_pg($vencimento);
			}
			if(strlen($documento) == 0){
				$msg_erro .= 'Digite o número do documento.';
			}else{
				//$documento = "'".$documento."'";
				$documento = "'".$documento."-".$boleto."'";
			}
			if(strlen($documento)>10){
				$msg_erro .= 'Número do documento incorreto.';
			}
			if(strlen($sacadoID) == 0){
				$msg_erro .= "<br>Selecione o Sacado.";
			}

			if(strlen($faturamento) == 0){
				$faturamento = "NULL";
			}

			if (strlen($obs) == 0) {
				$obs = "NULL";
			}else{
				$obs = "'".str_replace("'","",$obs)."'";
			}

			if (strlen ($data_baixa) == 0) {
				$data_baixa = "NULL";
			}else{
				$data_baixa = "'" . substr ($data_baixa,6,4) . "-" . substr ($data_baixa,3,2) . "-" . substr ($data_baixa,0,2) . "'" ;
			}

			if (strlen ($valor_pago) == 0) {
				$valor_pago= "NULL";
			}else{
				$valor_pago = str_replace(",",".",$valor_pago);
				$valor_pago = trim(str_replace(".00","",$valor_pago));
			}


			if(strlen($valor) == 0){
				$msg_erro .= '<br>Digite o valor.';
			}else{
				$valor = str_replace(",",".",$valor);
				$valor = trim(str_replace(".00","",$valor));
			}


#### ATUALIZAR

		if($acao=="alterar"){
		
			if(strlen($msg_erro) == 0){
				$sql = "UPDATE tbl_contas_receber 
						SET 
							documento             = $documento          ,
							vencimento            = $xvencimento         ,
							valor                 = $valor              ,
							obs                   = $obs                ,
							cliente               = $sacadoID  ,
							caixa_banco           = $caixa_banco
							WHERE contas_receber = $conta_receber;";
				echo nl2br($sql);
				//$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(strlen($msg_erro)>0){
					$msg_erro.= "Erro ao executar a baixa!";
				}else{
					$msg .= "O documento nº <b>$documento</b> foi alterado com sucesso!";
				}
			}
		}

		//Inserir uma conta a pagar
		if($acao=="cadastrar"){

			if(strlen($msg_erro) == 0){

				$sql = "INSERT INTO tbl_contas_receber (
							documento                       ,
							emissao                         ,
							remessa                         ,
							vencimento                      ,
							valor                           ,
							obs                             ,
							posto                           ,
							distribuidor                    ,
							fabrica                         ,
							cliente                         ,
							caixa_banco
					) VALUES (
							$documento      ,
							current_date    ,
							0               ,
							$xvencimento     ,
							$valor          ,
							$obs            ,
							$login_loja     ,
							0               ,
							$login_empresa  ,
							$sacadoID       ,
							$caixa_banco
						);";
				//echo nl2br($sql);
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
				if(strlen($msg_erro)>0){
					$msg_erro .= "Erro: ao inserir conta a pagar!";
				}else{
					$msg .= "Cadastro efetuado com sucesso para o documento nº <b>$documento</b>";
				}

				if (strlen($msg_erro)==0){
					$res			= pg_exec($con,"SELECT currval('tbl_contas_receber_seq');");
					$conta_receber	= pg_result($res,0,0);
					$msg_erro		.=pg_errormessage($con);
				}
				if(strlen($conta_receber)>0 and strlen($imprimir_boleto)>0){
					if(strlen($caixa_banco)>0){
						$sql = "SELECT banco from tbl_caixa_banco where caixa_banco= $caixa_banco";
						$res = pg_exec($con,$sql);
						$xcaixa_banco	= pg_result($res,0,0);
						//echo "imprime boleto $xcaixa_banco";
						if($xcaixa_banco=="001"){
							$boleto = "boleto/boleto_bb.php?conta=$conta_receber";
						}
						if($xcaixa_banco=="237"){
							$boleto = "boleto/boleto_bradesco.php?conta=$conta_receber";
						}
						if($xcaixa_banco=="275"){
							$boleto = "boleto/boleto_real.php?conta=$conta_receber";
						}
						if($xcaixa_banco=="351"){
							$boleto = "boleto/boleto_santander_banespa.php?conta=$conta_receber";
						}
						//echo "<BR>$boleto";
						echo "<script language='javascript'>";
							echo "window.open('$boleto' ,'_blank', 'menubar=yes,scrollbars=yes');";
						echo "</script>";
					}else{
						$msg_erro.= "Não foi escolhido o banco para gerar o boleto";
					}
				/*	SAMUEL FALOU PARA DEIXAR FIXO APENAS ESSES BOLETOS POR ENQTO
				$caixa_banco
				bb == "001",
				bradesco 237
				cef 104
				hsbc 399,
				itau 341
				nossacaixa 151
				real 275 
				santander_banespa 351
				sudameris 347
				unibanco 409*/
				/*	echo "<script language="\javascript\">";
							echo "window.open(\"pagina2. html\" , \"Pagina2\");";
					echo "</script>";*/
				}
				$sacadoID     = '';
				$valor          = '';
				$documento      = '';
				$vencimento     = '';
				$valor_pago     = '';
				$obs			= '';
			}
		}
	}
}

$title = "Contas a Pagar - Documento";
?>
<html>
<head>
<title><?=$title;?></title>
<link type="text/css" rel="stylesheet" href="css/estilo.css">
<link type="text/css" rel="stylesheet" href="css/css.css">

<script type="text/javascript" src="jquery/jquery-latest.pack.js"></script>
<script src="jquery/jquery.form.js" type="text/javascript" language="javascript"></script>
<script type='text/javascript' src='jquery/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="jquery/jquery.autocomplete.css" />
<script src="jquery/jquery.maskedinput.js" type="text/javascript"></script>



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

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

</script>

<script language='javascript'>

	function findValue(li) {
		if( li == null ) return alert("Não encontrado");

		// if coming from an AJAX call, let's use the CityId as the value
		if( !!li.extra ) {
			var sValue = li.extra[0];
		}else{
			var sValue = li.selectValue;
		}
		$('#sacadoID').val(sValue);
	}

	$(document).ready(function() {
		$("#sacado_nome").autocomplete(
			"<? echo $PHP_SELF ?>",
			{
				delay:10,
				minChars:3,
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

<script type="text/javascript">
	jQuery(function($){
		$("#vencimento").maskedinput("99/99/9999");
		$("#data_baixa").maskedinput("99/99/9999");
	});

function bloqueia_campos(formu){
	eval("var formu = document."+formu+";");
	if (formu){
		for( var i = 0 ; i < formu.length; i++ ){
			if (formu.elements[i].type=='text' || formu.elements[i].type=='textarea'){
				if (formu.elements[i].name!="data_baixa" && formu.elements[i].name!="valor_pago"){
					formu.elements[i].className='bloqueiado';
					formu.elements[i].readOnly=true;
				}
			}
			if (formu.elements[i].type=='radio'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].disabled=true;
			}
			if (formu.elements[i].type=='checkbox'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].disabled=true;
			}
			if (formu.elements[i].type=='select-one'){
				formu.elements[i].className='bloqueiado';
				formu.elements[i].disabled=true;
			}
		}
	}
}

function desbloqueia_campos(formu){
	eval("var formu = document."+formu+";");
	for( var i = 0 ; i < formu.length; i++ ){
		if (formu.elements[i].type=='text' || formu.elements[i].type=='textarea'){
			formu.elements[i].className='frm';
			formu.elements[i].readOnly=false;
		}
		if (formu.elements[i].type=='radio'){
			formu.elements[i].disabled=false;
		}
		if (formu.elements[i].type=='checkbox'){
			formu.elements[i].disabled=false;
		}
		if (formu.elements[i].type=='select-one'){
			formu.elements[i].disabled=false;
		}
	}
}
</script>
<BODY>
<?

if (strlen($msg)>0){
	echo "<br><br><br><br><br>";
	echo "<h4>$msg</h4>";
	echo "<br><br>";
	echo "<a href='javascript:self.parent.tb_remove();'>Fechar Janela</a>";
	exit;
}
if (strlen($msg_erro)>0){
	echo "<center><h4>$msg_erro</h4></center>";
}

$conta_receber = $_GET['conta_receber'];
if (strlen($conta_receber)>0){
	$sql="SELECT 
					tbl_contas_receber.contas_receber,
					tbl_contas_receber.documento,
					to_char(tbl_contas_receber.emissao,'DD/MM/YYYY') AS emissao,
					to_char(tbl_contas_receber.vencimento,'DD/MM/YYYY') AS vencimento,
					tbl_contas_receber.vencimento as vencimento_bd,
					tbl_contas_receber.valor,
					tbl_contas_receber.valor_dias_atraso,
					TO_CHAR(tbl_contas_receber.recebimento,'DD/MM/YYYY') AS recebimento,
					tbl_contas_receber.valor_recebido,
					tbl_contas_receber.status,
					tbl_contas_receber.faturamento_fatura,
					tbl_contas_receber.obs,
					tbl_contas_receber.posto,
					tbl_contas_receber.distribuidor,
					tbl_contas_receber.fabrica,
					tbl_contas_receber.cliente,
					tbl_pessoa.nome
		FROM tbl_contas_receber
		JOIN tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
		LEFT JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_contas_receber.cliente
		WHERE tbl_contas_receber.loja   = $login_loja
		AND   tbl_pagar.empresa= $login_empresa";
	$res	= pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$documento				= trim(pg_result($res, 0,documento));
		$sacadoID				= trim(pg_result($res, 0,pessoa_fornecedor));
		$nome					= trim(pg_result($res, 0,nome));
		$digitacao				= trim(pg_result($res, 0,digitacao));
		$vencimento				= trim(pg_result($res, 0,vencimento));
		$pagamento				= trim(pg_result($res, 0,pagamento));
		$valor					= trim(pg_result($res, 0,valor));
		$valor_pago				= trim(pg_result($res, 0,valor_pago));
		$valor_multa			= trim(pg_result($res, 0,valor_multa));
		$valor_juros_dia		= trim(pg_result($res, 0,valor_juros_dia));
		$valor_desconto			= trim(pg_result($res, 0,valor_desconto));
		$desconto_pontualidade	= trim(pg_result($res, 0,desconto_pontualidade));
		$protesto				= trim(pg_result($res, 0,protesto));
		$valor_custas_cartorio	= trim(pg_result($res, 0,valor_custas_cartorio));
		$obs					= trim(pg_result($res, 0,obs));
		$dias_vencido			= trim(pg_result($res, 0,dias_vencido));
		$valor_reajustado		= $valor;

		if (strlen($valor_custas_cartorio)==0){
			$valor_custas_cartorio=0;
		}

		$mora_multa = 0;

		$mora_multa			= number_format($mora_multa,2,'.','');
		$valor_reajustado	= number_format($valor_reajustado,2,'.','');
		$valor				= number_format($valor,2,'.','');

		$valor_multa		= number_format($valor_multa,2,'.','');
		$valor_juros_dia	= number_format($valor_juros_dia,2,'.','');
		$valor_desconto		= number_format($valor_desconto,2,'.','');

		if(strpos($documento, "-")>0){
			$nf		= substr($documento, 0, strpos($documento, "-"));
			$boleto	= substr($documento, (strpos($documento, "-")+1), strlen($documento));
		}else{
			$nf= $documento;
		}
	}
}

	if (strlen($conta_receber)>0){
		$botao_baixar    = "style='display:inline;'";
		$botao_alterar   = "style='display:inline;'";
		$botao_cadastrar = "style='display:none;'";
		echo "
		<script language='javascript'>
			$(document).ready(function() { 
				bloqueia_campos('frm_pagar');
			});
		</script>";
	}else{
		$botao_baixar    = "style='display:none;'";
		$botao_alterar   = "style='display:none;'";
		$botao_cadastrar = "style='display:inline;'";
	}

	if (strlen($pagamento)>0){
		$botao_baixar    = "style='display:none;'";
		$botao_alterar   = "style='display:none;'";
		$botao_cadastrar = "style='display:none;'";
		$msg_documento_pago = "<h3 aling='center' style='text-align:center'>Documento pago em $pagamento</h4>";
	}

	?>


	<form method="POST" action="<? $PHP_SELF ?>" name='frm_pagar'  id='frm_pagar'>
	<input type='hidden' id='conta_receber' name='conta_receber' value='<? echo $conta_receber; ?>'>

	<table id='boleto' border="1" cellspacing="0" width="700" align='center' style="border-collapse: collapse; border: 1px solid #000000;">
	<tr>
		<td colspan="2" width="472">
		<strong><big>Contas Receber</big></strong>
		</td>

		<td colspan='5'>
		 </td>
	</tr>

	<tr>
		<td colspan="6" width="472">
			<span class='topo'>Local de Pagamento</span><br>
				<span class='campoL'>Pagável no local que consta no Boleto</span>
		</td>
		<td width="168">
			<span class='topo'>Vencimento</span><br>
			<span class='campo'>
			<input type='text' id='vencimento' name='vencimento' value="<? echo $vencimento ?>" size='12' maxlength='10' class="frm"> 
			</span>
		</td>
	</tr>

	<tr>
		<td width="472" colspan="6">
		<span class='topo'>Cedente</span><br>
			<span class='campoL'><? echo $login_loja_nome ?></span>

		</td>
		<td width="168">
			<span class='topo'>Agência/Código Cedente</span><br>
			<span class='campo'>
			<?	$sql = "SELECT tbl_caixa_banco.banco ,
								tbl_banco.nome,
								tbl_caixa_banco.conta,
								tbl_caixa_banco.caixa_banco
						FROM tbl_caixa_banco 
						JOIN tbl_banco on tbl_caixa_banco.banco = tbl_banco.codigo
						WHERE tbl_caixa_banco.empresa = $login_empresa";
					//	echo $sql;
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					echo "<select name='caixa_banco' size='1' style='width:180px'>";
					for($w=0;pg_numrows($res)>$w;$w++){
						$banco  = pg_result($res,$w,banco);
						$nome   = pg_result($res,$w,nome);
						$conta  = pg_result($res,$w,conta);
						$xcaixa_banco = pg_result($res,$w,caixa_banco);
						echo "<option value='$xcaixa_banco'>$conta - $nome</option>";
					}
					echo "</select>";
				}else{
					echo "Nenhuma conta cadastrada";
				}
			//	echo $sql;
				?></span>
		</td>
	</tr>
	<tr>
		<td width="95">
			<span class='topo'>Data Documento</span><br>
			<span class='campo'>&nbsp;<? echo $digitacao; ?>
		</td>
		<td width="134" colspan="2">
			<span class='topo'>Documento/Nota Fiscal</span><br>
			<span class='campo'>
					<input type="text" id="nf" name="nf" value='<? echo $nf; ?>' size="15" maxlength='7' class='frm' >
					<!--
					<script type="text/javascript">
						new CAPXOUS.AutoComplete("nf", function() {

							return "contas_pagar_retorna_nf_ajax.php?fornID=" + document.getElementById('fornID').value +"&typing=" + this.text.value;
						});
					</script>	
					-->
					
			</span>
		</td>
		<td width="80">
			<span class='topo'>Boleto</span><br>
			<span class='campo'>
				<input type='text' name='boleto' id='boleto' value='<? echo $boleto; ?>' size='8' maxlength='20' class="frm"> 	
			</span>
		</td>
		<td width="38">
			<span class='topo'>Aceite</span><br>
			<span class='campo'>&nbsp;</span>
		</td>
		<td width="109">
			<span class='topo'>Data Processamento</span><br>
			<span class='campo'>&nbsp;<span id='data_digitacao2'></span></span>
		</td>
		<td width="168">
			<span class='topo'>Nosso Número</span><br>
			<span class='campo'>
				<div id='doc_final' style='padding:4px; background-color:#ffffff; width:200px; height:20px;'></div>
			</span>
		</td>
	</tr>
	<tr>
		<td width="95">
			<span class='topo'>Uso do Banco</span><br>
			<span class='campo'>&nbsp;</span>
		</td>
		<td width="85">
			<span class='topo'>Carteira</span><BR>
			<span class='campo'></span>
		</td>
		<td width="29">
			<span class='topo'>Espécie</span><br>
			<span class='campo'>R$</span>
		</td>
		<td width="90" colspan="2">
			<span class='topo'>Quantidade</span><br>
			<span class='campo'>&nbsp;</span>
		</td>
		<td width="115">
			<span class='topo'>(x) Valor</span><br>
			<span class='campo'>&nbsp; <? echo $valor; ?></span>
		 </td>
		<td width="168">
			<span class='topo'>(=) Valor do Documento</span><br>
			<span class='campo'>
				<input type='text' name='valor' id='valor' value="<? echo $valor ?>" size='12' maxlength='30' class="frm" onblur="javascript:checarNumero(this)"> 
			</span>
		</td>
	</tr>
	<tr>
		<td width="472" colspan="6" rowspan="6" valign="top">
			<span class='topo'>Instruções (texto de responsabilidade do cedente)</span><br>


	<table cellspacing="5" cellpadding='5' border='0' style='font-size:10px'>
	<tr>
	<td valign='top'>
	<?
		$sql = "SELECT	multa,
						juros
		FROM tbl_loja_dados 
		WHERE empresa = $login_empresa
		AND   loja    = $login_loja";
		$res = pg_exec($sql);
		if(@pg_numrows($res)>0){
			$multa = trim(pg_result($res,0,multa));
			$juros = trim(pg_result($res,0,juros));
			if(strlen($multa)==0){$multa = "0";}
			if(strlen($juros)==0){$juros = "0";}
		}
	?>
		<div style='font-weight:normal;border-bottom:1px solid #D8D8D8;width:100%; font-size:10px'>
			Multa de <?echo $multa;?> % para pagamento após vencimento.<BR>
			Juros / Mora de <?echo $juros;?> % ao dia.

		</div>
	</td>
	</tr>
	<tr>
	<td colspan='3'>
					Observações<br>
					<TEXTAREA type='text' id='obs' COLS='60' ROWS='3' NAME="obs" value=''><?$obs?></TEXTAREA>
	</td>
	</tr>
	</table>


	</td>
	<td width="168">
		<span class='topo'>(-) Descontos/Abatimentos</span><br>
		<span class='campo' id='descontos_abatimentos'>&nbsp;</span>
	</td>
	</tr>

	<tr>
	<td width="168">
		<span class='topo'>(-) Outras Deduções</span><br>
		<span class='campo'>&nbsp;</span>
	</td>
	</tr>

	<tr>
	<td width="168">
		<span class='topo'>(+) Mora/Multa</span><br>
		<span class='campo'  id='mora_multa'>&nbsp;</span>
	</td>
	</tr>

	<tr>
	<td width="168">
		<span class='topo'>(+) Outros Acréscimos</span><br>
		<span class='campo'>&nbsp;</span>
	</td>
	</tr>

	<tr>
	<td width="168">
		<span class='topo'>(=) Valor Cobrado</span><br>
		<span class='campo' id='valor_cobrado'>&nbsp; <? echo $valor; ?></span>
	</td>
	</tr>

	<tr>
	<td width="168">
		<span class='topo'>(=) Valor Pago</span><br>
		<span class='campo' id='valor_pago_tela'>&nbsp; <? echo $valor_pago; ?></span>
	</td>
	</tr>

	<tr>
	<td width="640" colspan="7">

	<input type='hidden' id='faturamentoID' value='' >

	<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td width="8%" valign='top'><span class='topo'>Sacado</span></td>
		<td width="28%" colspan="2">
		<span class='campoL'>
			<input id="sacadoID" name="sacadoID" type="hidden" value='<? echo $sacado ?>'>
			<input type="text" id="sacado_nome" name="sacado_nome" value='<? echo $sacado_nome ?>' size="40" class='frm' >
		</span>
		
		</td>
		<td width="34%" colspan="2"></td>
	</tr>
	<tr>
		<td width="3%"></td>
		<td width="28%" colspan="2"><span class='campoL'></span></td>
		<td width="22%"><span class='campoL'></span></td>
		<td width="32%"></td>
	</tr>
	<tr>
		<td width="2%"></td>
		<td width="10%"><span class='campoL'></span></td>
		<td width="38%"><span class='campoL'></span></td>
		<td width="22%"><span class='campoL'></span></td>
		<td width="30%" nowrap></td>
	</tr>
	<tr>
		<td width="1%" colspan="2"><span class='topo'></span></td>
		<td width="38%"></td>
		<td width="22%"></td>
		<td width="32%"><span class='topo'></span></td>
	</tr>
	</table>

	</td>
	</tr>
	</table>

	<br><INPUT TYPE="checkbox" NAME="imprimir_boleto" value='true'> <font size='1'> Imprimir boleto</font>

<?
if (strlen($msg_documento_pago)>0 AND strlen($pagamento)>0){
	echo $msg_documento_pago;
}
?>



	<table id='tabela_baixar' style='font-size:12px;display:none' cellspacing='2' celpadding='3' align='center'>
		<tr>
		<td colspan='3' bgcolor='#D7E9FF' align='center'><b>Baixar Este Documento</b></td>
		</tr>

		<tr>
		<td>Data</td>
		<td><input type='text' name='data_baixa' id='data_baixa' value='<? echo Date("d/m/Y");?>' class='frm'></td>
		<td rowspan='2'>
			<input type='button' name='btn_baixar' value='Gravar' class='frm'
			onclick="javascript:this.form.btn_acao.value='baixar'; this.form.submit();">
		</td>
		</tr>

		<tr>
		<td>Valor Pago</td>
		<td><input type='text' name='valor_pago' value='' class='frm' onblur="javascript:checarNumero(this)"></td>
		</tr>
	</table>



	<table id='tabela_alterar' style='font-size:12px;display:none' align='center'>
		<tr>
		<td align='center'>
			<input type='button' name='btn_alterar' value='Gravar Alterações' class='frm'
			onclick="
				javascript:this.form.btn_acao.value='alterar';
				this.form.submit();
			"			
			>
		</td>
		</tr>
	</table>

	<h4 align='center'>
	<input type='hidden' name='btn_acao' value=''>
	<input type='button' name='btn_acao_alterar' id='btn_acao_alterar' value='Alterar este documento' onclick="
	
	document.getElementById('tabela_alterar').style.display='inline';
	document.getElementById('btn_acao_baixar').style.display='none';
	this.style.display='none';
	desbloqueia_campos('frm_pagar');"
	
	<? echo $botao_alterar ?> >&nbsp;
	<input type='button' name='btn_acao_baixar'  id='btn_acao_baixar'  value='Efetuar a Baixa deste Documento' onclick=
	"
	document.getElementById('tabela_baixar').style.display='inline';
	document.getElementById('btn_acao_alterar').style.display='none';
	this.style.display='none';

	"  <? echo $botao_alterar ?>>
	<input type='button' name='btn_cadastrar'    id='btn_cadastrar'    value='GRAVAR' <? echo $botao_cadastrar ?> onclick="javascript:this.form.btn_acao.value='cadastrar';this.form.submit();">
	</h4>
	</form>

<a href='<? echo $PHP_SELF?>?btn_acao=abrirDocumento&keepThis=true&TB_iframe=true&height=550&width=750' id='linkContaPagar' title='Contas a Pagar' class='thickbox'></a>

<!-- <a href='contas_pagar_cadastro_fornecedor.php?keepThis=true&TB_iframe=true&height=450&width=600' id='linkContaReceber' title='Contas a Pagar' class='thickbox'>Novo Fornecedor</a> -->
</BODY>
</HTML>