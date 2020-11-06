<?
//OBS: ESTE ARQUIVOS UTILIZA AJAX: nf_britania_ret_ajax.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0)
	$faturamento = $_GET["faturamento"];

$btn_acao		= $_POST["btn_acao"];

if(strlen($btn_acao)==0)
	$btn_acao = $_GET["btn_acao"];

$erro_msg_		= $_GET["erro_msg"];
//SE NAO FOR O POSTO DE TESTE OU O DISTRIB.
if(($login_posto <> 6359) and ($login_posto <> 4311)){
	echo "NÃO É PERMITIDO LANÇAR NOTA FISCAL - longin: $login_posto";
	exit;
}

if ($btn_acao == "Gravar") {

	$faturamento = trim($_POST['faturamento']);
	$condicao    = trim($_POST['condicao']);
	$transportadora= trim($_POST['transportadora']);
	$serie= 2;
	$qtde_volume = 0;
	$valor_frete = 0;
	if(strlen($faturamento)>0){
		$sql = "SELECT faturamento 
				FROM tbl_faturamento 
				WHERE faturamento= $faturamento;";
		$res = pg_exec ($con,$sql);
	}

	if(pg_numrows($res)>0){
		$faturamento = trim(pg_result($res,0,faturamento));
		header ("Location: nf_cadastro.php?faturamento=$faturamento&erro_msg=Já foi Cadastrado a NF:$nota_fiscal");
		exit;
	}

	if(strlen($transportadora)==0)    $erro_msg .= "Digite a Transportadora!<br>" ;

	if(strlen($erro_msg)==0 ){
		//$res = pg_exec ($con,"BEGIN TRANSACTION");

		$cfop       = "6949";
		$natureza   = "Devolução de peças em garantia";
		
		/*IGOR- Copiei do embarque_nota_fiscal.php - Para não gerar nota errada*/
		# Fabio Nowaki - 24/01/2008
		$sql = "SELECT MAX (nota_fiscal::integer) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = $login_posto AND nota_fiscal::integer < 111111 ";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$nota_fiscal = pg_result ($res,0,0);

		if (strlen ($nota_fiscal) == 0) {
			$nota_fiscal = "000000";
		}

		$nota_fiscal = $nota_fiscal + 1 ;
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);


		$qtde_volume = 0;

		$sql= "INSERT INTO tbl_faturamento (
					fabrica          ,
					emissao           ,
					conferencia       ,
					saida             ,
					distribuidor      ,
					posto             ,
					qtde_volume       ,
					total_nota        ,
					cfop              ,
					nota_fiscal       ,
					transportadora    ,
					natureza          ,
					condicao
					)
				VALUES (
					$login_fabrica,
					current_date,
					CURRENT_TIMESTAMP   ,
					current_date        ,
					$login_posto        ,
					13996               ,/*CODIGO DO POSTO DA BRITANIA*/
					$qtde_volume, 
					0                   ,
					$cfop               ,
					'$nota_fiscal'      ,
					$transportadora           ,
					'$natureza'         ,
					$condicao
				)
				;";
		echo "sql: $sql<br>";
		//$res = pg_exec ($con,$sql);

		if(pg_result_error($res)) {
			$res = pg_exec ($con,"ROLLBACK;");
			$erro_msg.="<br>Erro ao inserir a NF:$nota_fiscal";
		}else{
			//$res = pg_exec ($con,"SELECT CURRVAL ('seq_faturamento') as fat;");
			$faturamento =trim (pg_result($res, 0 , fat));

			for($i=0; $i< 20; $i++){
				$erro_item  = "" ;
				$referencia = $_POST["referencia_$i"];
				$qtde       = $_POST["qtde_$i"];

				$sql = "SELECT  peca,
								descricao 
						FROM   tbl_peca 
						WHERE  fabrica    IN (".implode(",", $fabricas).")
						AND    referencia = '$referencia';";
				echo "sql: $sql<br>";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0){
					$peca      = trim(pg_result($res,0,peca));

					$sql= "SELECT 
								tbl_faturamento_item.* 
							FROM tbl_faturamento 
							JOIN tbl_faturamento_item USING(faturamento) 
							WHERE fabrica IN (".implode(",", $fabricas).")
								AND distribuidor= $login_posto
								AND peca = $peca
							ORDER BY tbl_faturamento.faturamento DESC 
							LIMIT 1;";
					echo "sql: $sql<br>";
					$res = pg_exec ($con,$sql);
					if(pg_numrows($res)>0){
						$preco      = trim (pg_result($res, 0 , preco));
						$aliq_icms  = trim (pg_result($res, 0 , aliq_icms));
						$aliq_ipi   = trim (pg_result($res, 0 , aliq_ipi));
						$base_icms  = trim (pg_result($res, 0 , base_icms));
						$base_ipi   = trim (pg_result($res, 0 , base_ipi));
						$valor_ipi  = trim (pg_result($res, 0 , valor_ipi));
						$valor_icms = trim (pg_result($res, 0 , valor_icms));

						if(strlen($qtde)==0)  $erro_item.= "Digite a qtde<br>" ;
						if(strlen($preco)==0) $erro_item.= "Peça sem preço<br>";

						if(strlen($aliq_icms)==0)  $aliq_icms  = "0";
						if(strlen($aliq_ipi)==0)   $aliq_ipi   = "0";
						if(strlen($base_icms)==0)  $base_icms  = "0";
						if(strlen($valor_icms)==0) $valor_icms = "0";
						if(strlen($base_ipi)==0)   $base_ipi   = "0";
						if(strlen($valor_ipi)==0)  $valor_ipi  = "0";
						$base_icms  = str_replace(",",".",$base_icms);
						$valor_icms = str_replace(",",".",$valor_icms);
						$base_ipi   = str_replace(",",".",$base_ipi);
						$valor_ipi  = str_replace(",",".",$valor_ipi);


						if(strlen($erro_item)==0){
							$sql=  "INSERT INTO tbl_faturamento_item (
										faturamento,
										peca       ,
										qtde       ,
										preco      ,
										aliq_icms  ,
										aliq_ipi   ,
										base_icms  ,
										valor_icms ,
										base_ipi   ,
										valor_ipi
									)VALUES(
										$faturamento,
										$peca       ,
										$qtde       ,
										$preco      ,
										$aliq_icms  ,
										$aliq_ipi   ,
										$base_icms  ,
										$valor_icms ,
										$base_ipi   ,
										$valor_ipi
									)";
							echo "sql: $sql<br>";
							//$res = pg_exec ($con,$sql);	

							if(pg_result_error($res)){
								echo "<br>Erro ao inserir peça: $referencia";
								$erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia";
							}
						}else{
							$erro_msg .= $erro_item ;
							echo "<br>Erro na linha i: $i - ERROOOOOO: $erro_item";
						}
					}else{
						$erro_msg .= "<br>Não encontrado nenhuma peça no estoque com essa referencia!";
					}
				}


			}
			/*
			if(strlen($erro_msg)==0){
				$res = pg_exec ($con,"COMMIT");
			}else{
				$res = pg_exec ($con,"ROLLBACK;");
			}*/
		}
	}
}//FIM BTN: GRAVAR

?>

<html>

<title>Cadastro de Nota Fiscal</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<head>

<script type="text/javascript" src="js/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>
<?include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script>

	$(function(){
		$('#emissao').datePicker({startDate:'01/01/2000'});
		$('#saida').datePicker({startDate:'01/01/2000'});
		$("#emissao").maskedinput("99/99/9999");
		$("#saida").maskedinput("99/99/9999");
	});

$().ready(function() {

	function formatItem(row) {
		//alert(row);
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}


	/* Busca pelo Descricao */
	$("input[@rel='descricao']").autocomplete("nf_cadastro_ajax.php?tipo=produto&busca=descricao&fabrica=<?=$login_fabrica?>", {
		minChars: 0,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] + " - " + row[1];
		},
		formatResult: function(row) {
			$(this).focus();
			return row[1];
		}
	});


	$("input[@rel='descricao']").result(function(event, data, formatted) {
		$("input[@name="+$(this).attr("alt")+"]").val(data[0]) ;
		$(this).focus();
	});


	/* Busca pelo Referencia */
	$("input[@rel='referencia']").autocomplete("nf_cadastro_ajax.php?tipo=produto&busca=referencia&fabrica=<?=$login_fabrica?>", {
		minChars: 0,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] + " - " + row[1];
		},
		formatResult: function(row) {
			return row[0];
		}
	});

	$("input[@rel='referencia']").result(function(event, data, formatted) {
		$("input[@name="+$(this).attr("alt")+"]").val(data[1]) ;
		$(this).focus();
	});

});
</script>


<script language="JavaScript">
//FUNÇÃO PARA CARREGAR FATURAMENTO
function retornaFat(http,componente) {
	var com = document.getElementById('f2');
	if (http.readyState == 1) {
		com.style.display    ='inline';
		com.style.visibility = "visible"
		com.innerHTML        = "&nbsp;&nbsp;<font color='#333333'>Consultando...</font>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML = results[1];
					setTimeout('esconde_carregar()',3000);
				}else{
					com.innerHTML = "&nbsp;&nbsp;<font color='#0000ff'>Sem faturamentos para esse fornecedor</font>";

				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}
function exibirFat(componente,conta_pagar, documento, acao) {
	var nota_fiscal = document.getElementById('nota_fiscal').value;
	var fabrica     = document.getElementById('fabrica').value;
	url = "nf_cadastro_ajax?ajax=sim&nota_fiscal="+escape(nota_fiscal)+"&fabrica="+fabrica;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFat (http,componente,nota_fiscal) ; } ;
	http.send(null);
}
function esconde_carregar(componente_carregando) {
	document.getElementById('f2').style.visibility = "hidden";
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO DE CADA FORNECEDOR
function calc_base_icms(i){
	
	var base=0.0, aliq_icms=0.0, valor_icms=0.0, aliq_ipi=0.0, valor_ipi=0.0;;
	preco		= document.getElementById('preco_'+i).value;
	qtde		= document.getElementById('qtde_'+i).value;
	aliq_icms	= document.getElementById('aliq_icms_'+i).value;
	aliq_ipi	= document.getElementById('aliq_ipi_'+i).value;

/*
	preco		= preco.toString().replace( ".", "" );
	qtde		= qtde.toString().replace( ".", "" );
	aliq_icms	= aliq_icms.toString().replace( ".", "" );
	aliq_ipi	= aliq_ipi.toString().replace( ".", "" );
*/
	preco       = preco.toString().replace( ",", "." );
	qtde        = qtde.toString().replace( ",", "." );
	aliq_icms   = aliq_icms.toString().replace( ",", "." );
	aliq_ipi    = aliq_ipi.toString().replace( ",", "." );

	preco       = parseFloat(preco);
	qtde        = parseFloat(qtde);
	aliq_icms   = parseFloat(aliq_icms);
	aliq_ipi    = parseFloat(aliq_ipi);

	base        = parseFloat(preco * qtde);
	base        = base.toFixed(2);
	valor_icms  = ((base * aliq_icms)/100);
	valor_icms  = valor_icms.toFixed(2);
	valor_ipi   = ((base *  aliq_ipi)/100);
	valor_ipi   = valor_ipi.toFixed(2);

	if(aliq_icms > 0) {
		document.getElementById('base_icms_'+i).value = base.toString().replace( ".", "," );;;
		document.getElementById('valor_icms_'+i).value = valor_icms.toString().replace( ".", "," );;
	}else{
		document.getElementById('base_icms_'+i).value = '0';
		document.getElementById('valor_icms_'+i).value = '0';
	}

	if(aliq_ipi > 0) {
		document.getElementById('base_ipi_'+i).value = base.toString().replace( ".", "," );;;
		document.getElementById('valor_ipi_'+i).value = valor_ipi.toString().replace( ".", "," );;;
	}else{
		document.getElementById('base_ipi_'+i).value = '0';
		document.getElementById('valor_ipi_'+i).value = '0';
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
	}
}


</script>
<style type="text/css">
.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
.Carregar{
	background-color:#ffffff;
	filter: alpha(opacity=90);
	opacity: .90 ;
	width:350px;
	border-color:#cccccc;
	border:1px solid #bbbbbb;
	display:none; 

	position:absolute;
}
</style>
</head>

<body>

<? include 'menu.php';?>

<center><h1>Cadastro de Notas Fiscais - NF:<? echo $nota_fiscal ?></h1></center>
	
<p>
<form name='nf_britania' method="POST" action='<? echo $PHP_SELF?>'>
<table width='650' align='center'>
<tr>
<td colspan = '6' align='right' bgcolor='red'>
<?
$erro_msg_= $erro_msg;
echo "<center>" . $erro_msg_."</center>";?>
</td>
</tr>
<tr>
	<td colspan = '3' align='left'><b>Dados da Nota Fiscal</b>
	</td>
	<td colspan = '3' align='right'><a href='nf_devolucao_cadastro.php?novo=novo'>Nova Nota		Fiscal</a></td>
	</tr>
<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold' >
	<td align='center'>Fábrica</td>
</tr>
<?
if(strlen($faturamento) > 0 ) {
	$sql = "SELECT	tbl_faturamento.faturamento                                          ,
					tbl_fabrica.fabrica                                                  ,
					tbl_fabrica.nome                                   AS fabrica_nome   ,
					tbl_faturamento.nota_fiscal                                          ,
					TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')     AS emissao        ,
					TO_CHAR (tbl_faturamento.saida,'DD/MM/YYYY')       AS saida          ,
					TO_CHAR (tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia    ,
					TO_CHAR (tbl_faturamento.cancelada,'DD/MM/YYYY')   AS cancelada      ,
					tbl_faturamento.cfop                                                 ,
					tbl_faturamento.serie                                                ,
					tbl_faturamento.condicao                                             ,
					tbl_faturamento.transportadora                                       ,
					tbl_transportadora.nome                            AS transp_nome    ,
					tbl_transportadora.fantasia                        AS transp_fantasia,
					to_char (tbl_faturamento.total_nota,'999999.99')   AS total_nota
			FROM      tbl_faturamento
			JOIN      tbl_fabrica        USING (fabrica)
			LEFT JOIN tbl_transportadora USING (transportadora)
			WHERE   tbl_faturamento.posto       = $login_posto
			AND     tbl_faturamento.faturamento = $faturamento
			ORDER BY tbl_faturamento.emissao     DESC,
					 tbl_faturamento.nota_fiscal DESC";
	$res = pg_exec ($con,$sql);
	if(pg_result_error($res)) $erro_msg.= "<font color='#ff0000'>Erro ao consultar faturamento!</font>";
	if(pg_numrows($res)>0){
		$conferencia      = trim(pg_result($res,0,conferencia)) ;
		$faturamento      = trim(pg_result($res,0,faturamento)) ;
		$fabrica          = trim(pg_result($res,0,fabrica)) ;
		$fabrica_nome     = trim(pg_result($res,0,fabrica_nome)) ;
		$nota_fiscal      = trim(pg_result($res,0,nota_fiscal));
		$emissao          = trim(pg_result($res,0,emissao));
		$saida            = trim(pg_result($res,0,saida));
		$cancelada        = trim(pg_result($res,0,cancelada));
		$cfop             = trim(pg_result($res,0,cfop));
		$serie            = trim(pg_result($res,0,serie));
		$condicao         = trim(pg_result($res,0,condicao));
		$transportadora   = trim(pg_result($res,0,transportadora));
		$transp_nome      = trim(pg_result($res,0,transp_nome));
		$transp_fantasia  = trim(pg_result($res,0,transp_fantasia));
		$total_nota       = trim(pg_result($res,0,total_nota));
	}else{
		$faturamento="";
	}
}else{
	$nota_fiscal = trim($_POST['nota_fiscal']);
	$emissao     = $_POST["emissao"]          ;
	$saida       = $_POST['saida']            ;
	$total_nota  = $_POST['total_nota']       ;
	$cfop        = $_POST['cfop']             ;
	$serie       = $_POST['serie']            ;
	$transportadora= $_POST['transportadora'] ;
	$condicao    = $_POST['condicao']         ;
}
	if (strlen ($transp_nome) > 0)     $transp = $transp_nome;
	if (strlen ($transp_fantasia) > 0) $transp = $transp_fantasia;
	$transportadora = strtoupper ($transportadora);

	echo "<tr>";
	echo "<td align='left' nowrap>";
	echo "<select style='width:200px;' name='fabrica' id='fabrica' ";
	$fabrica = $login_fabrica;
	if(strlen($fabrica)>0) echo " disabled ";
	else echo "onChange='window.location=\"$PHP_SELF?fabrica=\"+this.value'";
	echo ">";
	echo "<option value=''>Selecionar</option>";
		$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN (".implode(",", $fabricas).") ORDER BY nome";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			for($x = 0; $x < pg_numrows($res);$x++) {
				$aux_fabrica = pg_result($res,$x,fabrica);
				$aux_nome    = pg_result($res,$x,nome);
				echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
			}
		}
	echo "</select>";
	if(strlen($fabrica)>0) echo "<input type='hidden' name='faturamento_fabrica' value='$fabrica'>";
	echo "</td>\n";
	echo "</tr>";

	echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold' >";
	echo "<td align='center'>Nota Fiscal</td>";
	echo "<td align='center'>Série</td>";
	echo "<td align='center'>Emissão</td>";
	echo "<td align='center'>Saida</td>";
	echo "<td align='center'>CFOP</td>";
	echo "<td align='center'>Natureza</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' size='6'  maxlength='8' onBlur=\"exibirFat('dados','','','alterar')\"><br><div name='f2' id='f2' class='carregar'></div></td>\n";
	echo "<td><input type='text' name='serie' id='serie' value='$serie' size='10'  maxlength='10' ></td>\n";
	echo "<td>".date('d/m/Y')."</td>\n";
//	echo "<td><input type='text' name='emissao' id='emissao' value='$emissao' size='10'  maxlength='10' ></td>\n";
	echo "<td>".date('d/m/Y')."</td>\n";
//	echo "<td><input type='text' name='saida' id='saida' value='$saida' size='10' maxlength='10' ></td>\n";
	echo "<td><input type='text' name='cfop' id='cfop' value='$cfop' size='8'  maxlength='8' ></td>\n";
	echo "<td><input type='text' name='natureza' id='natureza' value='$natureza' size='10'  maxlength='30' ></td>\n";
	echo "</tr>";

	echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>";
	echo "<td align='center' colspan='1'>Condição</td>";
	echo "<td align='center' colspan='3'>Transp.</td>";
	echo "<td align='center' colspan='2'>Total</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td align='left' nowrap>\n";
	if(strlen($fabrica)>0){
		$sql = "SELECT condicao,
					   descricao 
				FROM  tbl_condicao 
				WHERE fabrica = $fabrica;";
		$res = @pg_exec ($con,$sql);
		echo "<SELECT NAME='condicao'>";
		echo "<option value=''>Selecionar</option>";
		for ($i=0; $i < pg_numrows($res); $i++) {
			$cond     = trim(pg_result($res,$i,condicao));
			$cond_des = strtoupper(trim(pg_result($res,$i,descricao)));
			if($cond == $condicao)
				echo "<option value='$cond' selected>$cond_des</option>\n";
			else
				echo "<option value='$cond'>$cond_des</option>\n";
		}
		echo "</SELECT>";
	}else echo "Selecione a fábrica";
	echo "</td>\n";
	echo "<td align='center' colspan='3' nowrap>";
	$sql = "SELECT  transportadora,
					nome 
			FROM     tbl_transportadora
			ORDER BY nome;";
	$res = @pg_exec ($con,$sql);

	echo "<SELECT NAME='transportadora'>";
	echo "<option value=''>Selecionar</option>";
	for ($i=0; $i < pg_numrows($res); $i++) {
		$x_transportadora_= trim(pg_result($res,$i,transportadora));
		$transp_nome	= strtoupper(trim(pg_result($res,$i,nome)));
		if($transportadora == $x_transportadora)
			echo "<option value='$transportadora' selected>$transp_nome</option>\n";
		else
			echo "<option value='$transportadora'>$transp_nome</option>\n";
	}
	echo "</SELECT>";
	echo "</td>";
	echo "<td align='center' nowrap colspan='2'>
		<input type='text' name='total_nota' id='total_nota' value='$total_nota'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" ></td>\n";
	echo "<td align='right' nowrap></td>\n";
	echo "</tr>\n";
?>
<tr>
	<td colspan='6'>&nbsp;</td>
</tr>
<tr>
	<td colspan='6'>
		<table width='600' align='center'>
		<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>
			<td colspan='14'>Itens da Nota</td>
		</tr>
		<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>
			<td align='center'>#</td>
			<td align='center'>Peça</td>
			<td align='center'>Descrição</td>
			<td align='center'>Qtde</td>
			<?
			if(strlen($faturamento)>0)
				echo "<td align='center'>Subtotal</td>";
			?>
		</tr>
<?

//SE NAO EXISTIR FATURAMENTO ENTAO NAO MOSTRA OS ITENS DA NOTA FISCAL
if(strlen($faturamento)==0){
	for ($i = 0 ; $i < 20 ; $i++) {

		//INSERIR ITENS DA NOTA
		$referencia     = $_POST["referencia_$i"]  ;
		$qtde           = $_POST["qtde_$i"]        ;

		/*$pedido         = $_POST["pedido_$i"]      ;
		$sua_os         = trim($_POST["sua_os_$i"]);
		$aliq_icms      = $_POST["aliq_icms_$i"]   ;
		$aliq_ipi       = $_POST["aliq_ipi_$i"]    ;
		$base_icms      = $_OPST["base_icms_$i"]   ;
		$valor_icms     =$_POST["valor_icms_$i"]   ;
		$base_ipi       =$_POST["base_ipi_$i"]     ;
		$valor_ipi      =$_POST["valor_ipi_$i"]    ;
*/
		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = "#FFEECC";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap>".($i+1)."</td>\n";
		echo "<td align='right' nowrap><input type='text' class='frm' name='referencia_$i' id='referencia_$i' value='$referencia' size='10' maxlength='20' rel='referencia' alt='descricao_$i'></td>\n";
		echo "<td align='right' nowrap><input type='text' class='frm' name='descricao_$i' id='descricao_$i' alt='referencia_$i' value='$referencia' size='10' maxlength='20' rel='descricao'></td>\n";

		if ($qtde_estoque == 0)  $qtde_estoque  = "";
		if ($qtde_quebrada == 0) $qtde_quebrada = "";

		echo "<td align='right' nowrap><input class='frm' type='text' name='qtde_$i' id='qtde_$i' value='$qtde' size='5' maxlength='10'  onblur=\"checarNumero(this);\"></td>\n"; 
		//retirado: onKeyUp='calc_base_icms($i);'
		//echo "<td align='right' nowrap><input class='frm' type='text' name='preco_$i' id='preco_$i' value='$preco' size='5' maxlength='12'  onblur=\"checarNumero(this);\"></td>\n"; 
		//retirado: onKeyUp='calc_base_icms($i);'
/*		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_$i' id='aliq_icms_$i' value='$aliq_icms' size='5' maxlength='10' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_$i' id='aliq_ipi_$i' value='$aliq_ipi' size='5' maxlength='10' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='pedido_$i' id='pedido_$i' value='$pedido' size='7' maxlength='10' onKeyUp='calc_base_icms($i);' ></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='sua_os_$i' id='sua_os_$i' value='$sua_os' size='9' maxlength='10' ></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_icms_$i' id='base_icms_$i' value='$base_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_icms_$i'	id='valor_icms_$i'	value='$valor_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_ipi_$i'	id='base_ipi_$i'	value='$base_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_$i'	id='valor_ipi_$i'	value='$valor_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		*/
		echo "</tr>\n";
	}

}else{
	$sql= "SELECT tbl_faturamento_item.faturamento   ,
				tbl_faturamento_item.faturamento_item,
				tbl_faturamento_item.peca            ,
				tbl_faturamento_item.qtde            ,
				tbl_faturamento_item.preco           ,
				tbl_faturamento_item.pedido          ,
				tbl_faturamento_item.os              ,
				tbl_faturamento_item.aliq_icms       ,
				tbl_faturamento_item.aliq_ipi        ,
				tbl_faturamento_item.base_icms       ,
				tbl_faturamento_item.valor_icms      ,
				tbl_faturamento_item.base_ipi        ,
				tbl_faturamento_item.valor_ipi       ,
				tbl_peca.referencia                  ,
				tbl_peca.descricao                   ,
				tbl_os.sua_os
			FROM      tbl_faturamento_item 
			JOIN      tbl_peca             ON tbl_faturamento_item.peca  = tbl_peca.peca
			LEFT JOIN tbl_os               ON tbl_faturamento_item.os    = tbl_os.os
			WHERE faturamento = $faturamento;";

	$res = pg_exec ($con,$sql);

	$subtotal         = 0;
	$valor_total      = 0;
	$total_valor_ipi  = 0;
	$total_valor_icms = 0;

	for ($i = 0 ; $i < pg_numrows($res); $i++) {
		$faturamento_item = trim(pg_result($res,$i,faturamento_item)) ;
		$referencia       = trim(pg_result($res,$i,referencia)) ;
		$descricao        = trim(pg_result($res,$i,descricao));
		$qtde             = trim(pg_result($res,$i,qtde));
		$preco            = trim(pg_result($res,$i,preco));
		/*$pedido           = trim(pg_result($res,$i,pedido));
		$sua_os           = trim(pg_result($res,$i,sua_os));
		$aliq_icms        = trim(pg_result($res,$i,aliq_icms));
		$aliq_ipi         = trim(pg_result($res,$i,aliq_ipi));
		$base_icms        = trim(pg_result($res,$i,base_icms));
		$valor_icms       = trim(pg_result($res,$i,valor_icms));
		$base_ipi         = trim(pg_result($res,$i,base_ipi));
		$valor_ipi        = trim(pg_result($res,$i,valor_ipi));
*/
		//$subtotal         = $preco * $qtde;
		$valor_total      = $valor_total     + $subtotal;
		$total_valor_ipi  = $total_valor_ipi + $valor_ipi;
		$total_valor_icms = $total_valor_icms+ $valor_icms;

		$preco            = number_format ($preco,2,',','.');
		$aliq_icms        = number_format ($aliq_icms,2,',','-.');
		$aliq_ipi         = number_format ($aliq_ipi,2,',','.-');
		$base_icms        = number_format ($base_icms,2,',','.');
		$valor_icms       = number_format ($valor_icms,2,',','.');
		$base_ipi         = number_format ($base_ipi,2,',','.');
		$valor_ipi        = number_format ($valor_ipi,2,',','.');
		$subtotal         = number_format ($subtotal,2,',','.');

		$cor = "#F7F5F0";
		if ($i % 2 == 0) $cor = "#F1F4FA";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap>
		<input type='hidden' name='faturamento_item_$i' value='$faturamento_item'>
		<input type='hidden' name='peca_$i' value='$peca'>".($y+1)."</td>\n";
		echo "<td align='right' nowrap><input type='text' name='referencia_$i'  value='$referencia'  size='7'  maxlength='10' ></td>\n";
		echo "<td align='left' nowrap>$descricao</td>\n";
	#	$preco = number_format ($preco,2,',','.');
/*		if ($qtde_estoque == 0)  $qtde_estoque  = "";
		if ($qtde_quebrada == 0) $qtde_quebrada = "";
*/
		echo "<td align='right' nowrap><input class='frm' type='text' name='qtde_$i' id='qtde_$i' value='$qtde' size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap>$preco</td>\n";
		echo "<td align='right' nowrap>$subtotal</td>\n";
		/*
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_$i' id='aliq_icms_$i' value='$aliq_icms' size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_$i' id='aliq_ipi_$i' value='$aliq_ipi' size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='pedido_$i' id='pedido_$i' value='$pedido' size='7' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='sua_os_$i' id='sua_os_$i' value='$sua_os' size='9' maxlength='10' ></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_icms_$i' id='base_icms_$i' value='$base_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='alert();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_icms_$i' id='valor_icms_$i' value='$valor_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_ipi_$i' id='base_ipi_$i' value='$base_ipi' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_$i' id='valor_ipi_$i' value='$valor_ipi' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		*/

	echo "</tr>\n";
	}

	$valor_total      = number_format ($valor_total,2,',','.');
	$total_valor_icms = number_format ($total_valor_icms,2,',','.');
	$total_valor_ipi  = number_format ($total_valor_ipi,2,',','.');
	
		echo "<tr bgcolor='#d1d4eA' style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap colspan= '5'> Totais</td>\n";

		echo "<td align='right' nowrap>$valor_total</td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap>$total_valor_icms</td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap>$total_valor_ipi</td>\n";
		echo "</tr>\n";
		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap colspan='14'><b>Valor Total da Nota:$valor_total</b></td>\n";
		echo "</tr>\n";

	$total_nota = number_format ($total_nota,2,',','.');
	if($valor_total == $total_nota){
	}else{
			echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
			echo "<td align='right' nowrap colspan='14'><font color='red'><b>Valor Total da Nota:$valor_total está diferente de Total cadatrado:$total_nota</b></font></td>\n";
			echo "</tr>\n";
	}

}
echo "</table>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";
echo "</td>";
echo "</tr>";

if(strlen($faturamento)>0)
	$desc_bt="Alterar";
else	
	$desc_bt="Gravar";	

echo "<tr>";
echo "<td colspan='12' align='center'>";
echo "<input type='submit' name='btn_acao' value='$desc_bt'>";
echo "</td>";
echo "</tr>";


echo "</table>\n";
echo "</form>";
?>

<p>

<? #include "rodape.php"; ?>

</body>
</html>