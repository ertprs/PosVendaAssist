<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$sql = "SELECT * 
		FROM tbl_loja_dados 
		WHERE empresa = $login_empresa
		AND   loja    = $login_loja";
$res = pg_exec($sql);
if(pg_numrows($res)==0){
	$sql = "INSERT INTO tbl_loja_dados (empresa,loja) VALUES ($login_empresa,$login_loja);";
	$res= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);
}else{
	$empresa                     = pg_result($res,0,empresa);
	$despesas_administrativas    = pg_result($res,0,despesas_administrativas);
	$marketing                   = pg_result($res,0,marketing);
	$perdas                      = pg_result($res,0,perdas);
	$comissao_venda              = pg_result($res,0,comissao_venda);
	$regime_tributario           = pg_result($res,0,regime_tributario);
	$simples_federal             = pg_result($res,0,simples_federal);
	$simples_estadual            = pg_result($res,0,simples_estadual);
	$pis                         = pg_result($res,0,pis);
	$cofins                      = pg_result($res,0,cofins);
	$irpj                        = pg_result($res,0,irpj);
	$contribuicao_social         = pg_result($res,0,contribuicao_social);
	$iss                         = pg_result($res,0,iss);
	$multa                       = pg_result($res,0,multa);
	$juros                       = pg_result($res,0,juros);
	$numero_dias_carencia        = pg_result($res,0,numero_dias_carencia);
	$desconto_pagamento          = pg_result($res,0,desconto_pagamento);
	$perc_max_desc               = pg_result($res,0,perc_max_desc);
	$carta_cobranca1             = pg_result($res,0,carta_cobranca1);
	$carta_cobranca2             = pg_result($res,0,carta_cobranca2);
	$carta_venda                 = pg_result($res,0,carta_venda);
	$carta_assistencia           = pg_result($res,0,carta_assistencia);
	$email_cobranca              = pg_result($res,0,email_cobranca);
	$email_venda                 = pg_result($res,0,email_venda);
	$email_assistencia           = pg_result($res,0,email_assistencia);
	$percentual_vendas           = pg_result($res,0,percentual_vendas);
	$percentual_lucro            = pg_result($res,0,percentual_lucro);
	$calculo_custo_medio         = pg_result($res,0,momento_custo_medio);
	$compra_periodo              = pg_result($res,0,compra_periodo);
	$compra_dia                  = pg_result($res,0,compra_dia);
	$email_automatico_fornecedor = pg_result($res,0,email_automatico_fornecedor);
	//$compra_email_fornecedor     = pg_result($res,0,compra_email_fornecedor);
	$compra_fornecedor_filtro    = pg_result($res,0,compra_fornecedor_filtro);

	/*$venda_cartao             = pg_result($res,0,venda_cartao);
	$tabela_aprazo            = pg_result($res,0,tabela_aprazo);
	$tabela_avista            = pg_result($res,0,tabela_avista);
	$tabela_atacado           = pg_result($res,0,tabela_atacado);
	$tabela_internet          = pg_result($res,0,tabela_internet);*/

	//HD 6124 Igor pediu para tratar os numero para aparecer ao inves de ponto, virgula

	$despesas_administrativas	= number_format($despesas_administrativas, 2, ',', '');
	$comissao_venda             = number_format($comissao_venda, 2, ',', '');
	$marketing                  = number_format($marketing, 2, ',', '');
	$percentual_lucro           = number_format($percentual_lucro, 2, ',', '');
	$percentual_vendas          = number_format($percentual_vendas, 2, ',', '');
	$perdas                     = number_format($perdas, 2, ',','');
	$simples_federal            = number_format($simples_federal, 2, ',','');
	$simples_estadual           = number_format($simples_estadual, 2, ',','');
	$pis                        = number_format($pis, 2, ',','');
	$cofins                     = number_format($cofins, 2, ',','');
	$irpj                       = number_format($irpj, 2, ',','');
	$contribuicao_social        = number_format($contribuicao_social, 2, ',','');
	$iss                        = number_format($iss, 2, ',','');
	$multa                      = number_format($multa, 2, ',','');
	$juros                      = number_format($juros, 2, ',','');
	$numero_dias_carencia       = number_format($numero_dias_carencia, 2, ',',',');
	$desconto_pagamento         = number_format($desconto_pagamento, 2, ',','');
	$perc_max_desc              = number_format($perc_max_desc, 2, ',','');

}

$btn_acao                 = $_POST["btn_acao"];

if(strlen($btn_acao)>0){
	

	$empresa                     = $_POST["empresa"];
	$despesas_administrativas    = $_POST["despesas_administrativas"];
	$marketing                   = $_POST["marketing"];
	$perdas                      = $_POST["perdas"];
	$comissao_venda              = $_POST["comissao_venda"];
	$regime_tributario           = $_POST["regime_tributario"];
	$simples_federal             = $_POST["simples_federal"];
	$simples_estadual            = $_POST["simples_estadual"];
	$pis                         = $_POST["pis"];
	$cofins                      = $_POST["cofins"];
	$irpj                        = $_POST["irpj"];
	$contribuicao_social         = $_POST["contribuicao_social"];
	$iss                         = $_POST["iss"];
	$multa                       = $_POST["multa"];
	$juros                       = $_POST["juros"];
	$numero_dias_carencia        = $_POST["numero_dias_carencia"];
	$desconto_pagamento          = $_POST["desconto_pagamento"];
	$perc_max_desc               = $_POST["perc_max_desc"];
	$carta_cobranca1             = $_POST["carta_cobranca1"];
	$carta_cobranca2             = $_POST["carta_cobranca2"];
	$carta_venda                 = $_POST["carta_venda"];
	$carta_assistencia           = $_POST["carta_assistencia"];
	$email_cobranca              = $_POST["email_cobranca"];
	$email_venda                 = $_POST["email_venda"];
	$email_assistencia           = $_POST["email_assistencia"];
	$percentual_lucro            = $_POST["percentual_lucro"];
	$percentual_vendas           = $_POST["percentual_vendas"];
	$calculo_custo_medio         = $_POST["calculo_custo_medio"];

	//HD 6124 tratar o erro de não conseguir salvar numero com virgula

	$despesas_administrativas	= number_format(str_replace( ',', '.', $despesas_administrativas), 2, '.','');
	$comissao_venda             = number_format(str_replace( ',', '.', $comissao_venda), 2, '.','');
	$marketing                  = number_format(str_replace( ',', '.', $marketing), 2, '.','');
	$percentual_lucro           = number_format(str_replace( ',', '.', $percentual_lucro), 2, '.','');
	$percentual_vendas          = number_format(str_replace( ',', '.', $percentual_vendas), 2, '.','');
	$perdas                     = number_format(str_replace( ',', '.', $perdas), 2, '.','');
	$simples_federal            = number_format(str_replace( ',', '.', $simples_federal), 2, '.','');
	$simples_estadual           = number_format(str_replace( ',', '.', $simples_estadual), 2, '.','');
	$pis                        = number_format(str_replace( ',', '.', $pis), 2, '.','');
	$cofins                     = number_format(str_replace( ',', '.', $cofins), 2, '.','');
	$irpj                       = number_format(str_replace( ',', '.', $irpj), 2, '.','');
	$contribuicao_social        = number_format(str_replace( ',', '.', $contribuicao_social), 2, '.','');
	$iss                        = number_format(str_replace( ',', '.', $iss), 2, '.','');
	$multa                      = number_format(str_replace( ',', '.', $multa), 2, '.','');
	$juros                      = number_format(str_replace( ',', '.', $juros), 2, '.','');
	$numero_dias_carencia       = number_format(str_replace( ',', '.', $numero_dias_carencia), 2, '.',',');
	$desconto_pagamento         = number_format(str_replace( ',', '.', $desconto_pagamento), 2, '.','');
	$perc_max_desc              = number_format(str_replace( ',', '.', $perc_max_desc), 2, '.','');




	//novos campos
	if(strlen($_GET["compra_periodo"]) > 0)
		$compra_periodo = $_GET["compra_periodo"];
	else
		$compra_periodo = $_POST["compra_periodo"];

	if(strlen($_GET["compra_dia"]) > 0)
		$compra_dia = $_GET["compra_dia"];
	else
		$compra_dia = $_POST["compra_dia"];

	$email_automatico_fornecedor = $_POST["email_automatico_fornecedor"];

	if(strlen($_GET["compra_fornecedor_filtro"]) > 0)
		$compra_fornecedor_filtro = $_GET["compra_fornecedor_filtro"];
	else
		$compra_fornecedor_filtro = $_POST["compra_fornecedor_filtro"];
	//----------------

/*	$venda_cartao             = $_POST["venda_cartao"];
	$tabela_aprazo            = $_POST["tabela_aprazo"];
	$tabela_avista            = $_POST["tabela_avista"];
	$tabela_atacado           = $_POST["tabela_atacado"];
	$tabela_internet          = $_POST["tabela_internet"];*/

/*	venda_cartao 
	tabela_aprazo
	tabela_avista 
	tabela_internet 
	tabela_atacado
*/
	if ($email_automatico_fornecedor == 't'){
		$xemail_automatico_fornecedor="t";
	}
	else{
		$xemail_automatico_fornecedor="f";
	}

	/*if ($compra_email_fornecedor == 't'){
		$xcompra_email_fornecedor="t";
	}
	else{
		$xcompra_email_fornecedor="f";
	}*/

	if ($compra_periodo == 1){
		$xcompra_dia = "null";
	}
	else{
		 $xcompra_dia = $compra_dia;
	}

	if(strlen($compra_dia_s) > 0 and $compra_periodo == 2){
		$xcompra_dia = $compra_dia_s;
	}

	if(strlen($compra_dia_m) > 0 and $compra_periodo == 3){
		$xcompra_dia = $compra_dia_m;
	}

	$sql = "UPDATE tbl_loja_dados SET
				despesas_administrativas    = '$despesas_administrativas',
				marketing                   = '$marketing'               ,
				perdas                      = '$perdas'                  ,
				comissao_venda              = '$comissao_venda'          ,
				regime_tributario           = '$regime_tributario'       ,
				simples_federal             = '$simples_federal'         ,
				simples_estadual            = '$simples_estadual'        ,
				pis                         = '$pis'                     ,
				cofins                      = '$cofins'                  ,
				irpj                        = '$irpj'                    ,
				contribuicao_social         = '$contribuicao_social'     ,
				iss                         = '$iss'                     ,
				multa                       = '$multa'                   ,
				juros                       = '$juros'                   ,
				numero_dias_carencia        = '$numero_dias_carencia'    ,
				desconto_pagamento          = '$desconto_pagamento'      ,
				perc_max_desc               = '$perc_max_desc'           ,
				carta_cobranca1             = '$carta_cobranca1'         ,
				carta_cobranca2             = '$carta_cobranca2'         ,
				carta_venda                 = '$carta_venda'             ,
				carta_assistencia           = '$carta_assistencia'       ,
				email_cobranca              = '$email_cobranca'          ,
				email_venda                 = '$email_venda'             ,
				email_assistencia           = '$email_assistencia'       ,
				percentual_lucro            = '$percentual_lucro'        ,
				percentual_vendas           = '$percentual_vendas'       ,
				momento_custo_medio         = '$calculo_custo_medio'     ,
				compra_periodo              = '$compra_periodo'          ,
				compra_dia                  = $xcompra_dia             ,
				email_automatico_fornecedor = '$xemail_automatico_fornecedor',
				compra_fornecedor_filtro    = '$compra_fornecedor_filtro'
			WHERE empresa = $login_empresa
			AND   loja    = $login_loja";
				/*
				compra_email_fornecedor     = '$xcompra_email_fornecedor' ,
				,
				venda_cartao             = '$venda_cartao'            ,
				tabela_aprazo            = '$tabela_aprazo'           ,
				tabela_avista            = '$tabela_avista'           ,
				tabela_atacado           = '$tabela_atacado'          ,
				tabela_internet          = '$tabela_internet'
				*/
		$res = pg_exec($sql);
		//echo $sql;
}

include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'gerencial') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}


?>
<style>
.Label{
	font-family: Verdana;
	font-size: 10px;
}
</style>
<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxAutoHeight: true} );
		$('#container-1').tabs( { fxSpeed: 'fast'} );
	});

$(document).ready(
	function()
	{
		//$("a").ToolTipDemo("#FDFAC4", "#645C00");
		$("input.text, textarea.text").focusFields()
	}
	
);

/* Função mostra o campo quando muda o select(combo)*/
function MudaCampo(campo){
	if (campo.value== '2' ) {
		document.getElementById('compra_dia_s').style.display='inline';
	}else{
		document.getElementById('compra_dia_s').style.display='none';
	}

	if (campo.value== '3' ) {
		document.getElementById('compra_dia_m').style.display='inline';
	}else{
		document.getElementById('compra_dia_m').style.display='none';
	}
}
</script>
<? if (strlen($msg_erro)>0) echo "<div class='error'>$msg_erro</div>"; ?>

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' class='tabela'>
<form name="frm" method="post" action="<? echo $PHP_SELF ?>">
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Configuração de Dados da Empresa</td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>

				<div id="container-Principal">
					<ul>
						<!--<li><a href="#tab"><span><img src='imagens/people-alt2.png' align=absmiddle> Geral</span></a></li>-->
						<li><a href="#tab0"><span><img src='imagens/people-alt2.png' align=absmiddle> Tabela de Preço</span></a></li>
						<li><a href="#tab1"><span><img src='imagens/people-alt2.png' align=absmiddle> Tributacao</span></a></li>
						<li><a href="#tab2"><span><img src='imagens/people-alt2.png' align=absmiddle> Pagamento</span></a></li>
						<li><a href="#tab5"><span><img src='imagens/people-alt2.png' align=absmiddle> Compra</span></a></li>
					</ul>
					<!--<div id="tab">
						<table width='400'>
							<tr>
								<td colspan='2'><b>Características Gerais</b></td>
							</tr>
							<tr>
								<td class='Label'>Utilizar <i>cartão de venda</i></td>
								<td class='Label' align='left'><input type='radio' name='venda_cartao' value='t' <?if($venda_cartao=='t') echo "CHECKED";?>> Sim </td>
								<td class='Label' align='left'><input type='radio' name='venda_cartao' value='f' <?if($venda_cartao=='f') echo "CHECKED";?>> Não <span class='text_curto'> <a href='#' title='Selecione esta opção, quando a empresa for utilizar cartões de código de barra, para realizar uma venda<br> Ao selecionar esta opção se torna obrigatório informar o cartão para compras à vista, para poder assim efetuar o pagamento no caixa.' class='ajuda'>?</a></td>
							</tr>
							<tr>
								<td class='Label'></span>Utilizar tabela de preço <i>à prazo</i></td>
								<td class='Label' align='left'><input type='radio' name='tabela_aprazo' value='t' <?if($tabela_aprazo=='t') echo "CHECKED";?>> Sim </td>
								<td class='Label' align='left'><input type='radio' name='tabela_aprazo' value='f' <?if($tabela_aprazo=='f') echo "CHECKED";?>> Não </td>
							</tr>
							<tr> 
								<td class='Label'>Utilizar tabela de preço <i>à vista</i></td>
								<td class='Label' align='left'><input type='radio' name='tabela_avista' value='t' <?if($tabela_avista=='t') echo "CHECKED";?>> Sim </td>
								<td class='Label' align='left'><input type='radio' name='tabela_avista' value='f' <?if($tabela_avista=='f') echo "CHECKED";?>> Não </td>
							</tr>
							<tr>
								<td class='Label'>Utilizar tabela de preço de <i>atacado</i></td>
								<td class='Label' align='left'><input type='radio' name='tabela_atacado' value='t' <?if($tabela_internet=='t') echo "CHECKED";?>> Sim </td>
								<td class='Label' align='left'><input type='radio' name='tabela_atacado' value='f' <?if($tabela_internet=='f') echo "CHECKED";?>> Não </td>
							</tr>
							<tr>
								<td class='Label'>Utilizar tabela de preço de <i>internet</i></td>
								<td class='Label' align='left'><input type='radio' name='tabela_internet' value='t' <?if($tabela_internet=='t') echo "CHECKED";?>> Sim </td>
								<td class='Label' align='left'><input type='radio' name='tabela_internet' value='f' <?if($tabela_internet=='f') echo "CHECKED";?>> Não </td>
							</tr>
						</table>
					</div>-->
					<div id="tab0">
						<table>
							<tr>
								<td colspan='2'><b>Configurações gerais para formação de preço</b></td>
							</tr>
							<tr>
								<td class='Label'>Percentual Administrativo</td>
								<td class='Label' align='left'>
									<input class="CaixaValor" type="text" name="despesas_administrativas"   size="2" maxlength="20" value="<? echo $despesas_administrativas ?>" onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label'>Percentual da Comissão</td>
								<td class='Label' align='left' >
									<input class="CaixaValor" type="text" name="comissao_venda"   size="2" maxlength="20" value="<? echo $comissao_venda ?>" onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Percentual do Marketing</td>
								<td class='Label' align='left'>
									<input class="CaixaValor" type="text" name="marketing"   size="2" maxlength="20" value="<? echo $marketing ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Percentual do Lucro</td>
								<td class='Label' align='left'>
									<input class="CaixaValor" type="text" name="percentual_lucro"   size="2" maxlength="20" value="<? echo $percentual_lucro ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Percentual do Venda</td>
								<td class='Label' align='left'>
									<input class="CaixaValor" type="text" name="percentual_vendas"   size="2" maxlength="20" value="<? echo $percentual_vendas ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Perdas</td>
								<td class='Label' align='left'>
									<input class="CaixaValor" type="text" name="perdas"   size="2" maxlength="20" value="<? echo $perdas ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							
							<tr>
								<td class='Label' nowrap>Cálculo Custo Médio
								<span class='text_curto'> <a href='#' title='Momento que será atualizado o cálculo do custo médio do produto.' class='ajuda'>?</a></span>
								</td>

								<td class='Label' align='left'>
								<select class="CaixaValor" name='calculo_custo_medio'>
								<option value='pedido'<?if($calculo_custo_medio=="pedido") echo "SELECTED";?>>Finalização Pedido</option>
								<option value='recebimento'<?if($calculo_custo_medio=="recebimento") echo "SELECTED";?>>Recebimento Mercadoria</option>
								</select>
								</td>
									
							</tr>
							</table>
					</div>
					<div id="tab1">
						<table>
							<tr>
								<td class='Label' nowrap>Regime Tributário</td>
								<td align='left' colspan='4'>
									<select class='Caixa' name='regime_tributario' style='width:120px;'>
										<option value='Lucro Presumido' <? if ($regime_tributario =='Lucro Presumido') echo "SELECTED"; ?>>Lucro Presumido</option>
										<option value='Lucro Real' <? if ($regime_tributario=='Lucro Real') echo "SELECTED";?>>Lucro Real</option>
										<option value='Super Simples'  <? if ($regime_tributario=='Super Simples') echo "SELECTED";?>>Super Simples</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Simples Federal</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="simples_federal"   size="2" maxlength="20" value="<? echo $simples_federal ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Simples Estadual</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="simples_estadual"   size="2" maxlength="20" value="<? echo $simples_estadual ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Pis</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="pis"   size="2" maxlength="20" value="<? echo $pis ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Cofins</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="cofins"   size="2" maxlength="20" value="<? echo $cofins ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>IRPJ</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="irpj"   size="2" maxlength="20" value="<? echo $irpj ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Contribuição Social</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="contribuicao_social"   size="2" maxlength="20" value="<? echo $contribuicao_social ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>ISS</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="iss"   size="2" maxlength="20" value="<? echo $iss ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
						</table>
					</div>
					<div id="tab2">
						<table>
							<tr>
								<td class='Label' nowrap>Multa</td>
								<td align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="multa"   size="2" maxlength="20" value="<? echo $multa ?>"  onblur="javascript:checarNumero(this)"></td>
							</tr>
							<tr>
								<td class='Label' nowrap>Juros</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="juros"   size="2" maxlength="20" value="<? echo $juros ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Número de dias de carência</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="numero_dias_carencia"   size="2" maxlength="20" value="<? echo $numero_dias_carencia ?>"  onblur="javascript:checarNumero(this)"></td>
							</tr>
							<tr>
								<td class='Label' nowrap>Desconta até a data do pagamento</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="desconto_pagamento"   size="2" maxlength="20" value="<? echo $desconto_pagamento ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
							<tr>
								<td class='Label' nowrap>Percentual máximo de desconto</td>
								<td class='Label' align='left' colspan='4'>
									<input class="CaixaValor" type="text" name="perc_max_desc"   size="2" maxlength="20" value="<? echo $perc_max_desc ?>"  onblur="javascript:checarNumero(this)">%</td>
							</tr>
						</table>

					</div>

					<!-- ABA COMPRA -->
					<div id="tab5">
						<table border='0' cellspacing='2' cellpadding='2'>
							<tr>
								<td 'colspan='2'><b>Periodicidade de Compra</b></td>
							</tr>
							<tr>
								<td class='Label'>Período:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<select class='Caixa' name='compra_periodo' style='width:120px;' onChange="MudaCampo(this)">
										<option value='1' <? if ($compra_periodo == '1') echo "SELECTED"; ?>>Diario</option>
										<option value='2' <? if ($compra_periodo == '2') echo "SELECTED";?>>Semanal</option>
										<option value='3' <? if ($compra_periodo == '3') echo "SELECTED";?>>Mensal</option>
									</select>
								</td>
							</tr>
							<tr>
								<td class='Label'>
								<span id='compra_dia_s' style='display:<? if ($compra_periodo == '2') echo "inline"; else echo "none"; ?>'>
									Dia da semana:&nbsp;
									<select class='Caixa' name='compra_dia_s' style='width:120px;'>
										<option value='1' <? if($compra_dia_s == '1')  echo "SELECTED"; else if($compra_dia == '1')echo "SELECTED"; ?>>Segunda</option>
										<option value='2' <? if($compra_dia_s == '2')  echo "SELECTED"; else if($compra_dia == '2')echo "SELECTED"; ?>>Terça</option>
										<option value='3' <? if($compra_dia_s == '3')  echo "SELECTED"; else if($compra_dia == '3')echo "SELECTED"; ?>>Quarta</option>
										<option value='4' <? if($compra_dia_s == '4')  echo "SELECTED"; else if($compra_dia == '4')echo "SELECTED"; ?>>Quinta</option>
										<option value='5' <? if($compra_dia_s == '5')  echo "SELECTED"; else if($compra_dia == '5')echo "SELECTED"; ?>>Sexta</option>
										<option value='6' <? if($compra_dia_s == '6')  echo "SELECTED"; else if($compra_dia == '6')echo "SELECTED"; ?>>Sabado</option>
									</select>
									</span>
								
								<span id='compra_dia_m' style='display:<? if ($compra_periodo == '3') echo "inline"; else echo "none"; ?>'>
									Compra dia:&nbsp;
									<INPUT TYPE="text" NAME="compra_dia_m" size='2' maxlength='2' class='Caixa' value="<? if(strlen($compra_dia_m) > 0) echo $compra_dia_m; else echo $compra_dia; ?>" >
								</span>
								</td>
							</tr>
							<tr>
								<td class='Label' colspan='2'><BR>
								<INPUT TYPE="checkbox" NAME="email_automatico_fornecedor" value='t' <? if ($email_automatico_fornecedor=='t')echo "CHECKED"; ?>>&nbsp;Enviar email automatico para o fornecedor</td>
							</tr>
							<!-- <tr>
								<td class='Label' colspan='4'><BR>
									<INPUT TYPE="checkbox" NAME="compra_email_fornecedor" value='t' <? if ($compra_email_fornecedor=='t')echo "CHECKED"; if(strlen($compra_email_fornecedor)==0) echo "CHECKED";?>>Enviar email requisição de novos produtos para todos os fornecedores.
								</td>
							</tr> -->
							<tr>
								<td class='Label' colspan='2'><BR>Enviar email requisição de novos produtos para todos os fornecedores?&nbsp;</td>
							</tr>
							<tr>
								<td class='Label' colspan='2'>
											<select class='Caixa' name='compra_fornecedor_filtro' style='width:120px;'>
											<option value='1' <? if ($compra_fornecedor_filtro == '1') echo "SELECTED"; ?>>Todos com filtro</option>
											<option value='2' <? if ($compra_fornecedor_filtro == '2') echo "SELECTED"; ?>>Todos sem filtro</option>
											<option value='3' <? if ($compra_fornecedor_filtro == '3') echo "SELECTED"; ?>>Não enviar</option>
										</select>
								</td>
							</tr>
						</table>
					</div>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'><input type='submit' name='btn_acao' value = 'Gravar' class='botao'></td>
		</tr>
</form>
</table>


<?
/* 

						<li><a href="#tab3"><span><img src='imagens/mail-blue.png' align=absmiddle> Carta Cobrança</span></a></li>
						<li><a href="#tab4"><span><img src='imagens/mail-gold.png' align=absmiddle> Carta</span></a></li>

											<div id="tab3">
					
						<table>
							<tr>
								<td class='Label' width='40'>Email:</td>
								<td align='left' >
									<input class="Caixa" type="text" name="email_cobranca"   size="50" maxlength="80" value="<? echo $email_cobranca ?>" >
								</td>
							</tr>
							<tr>
								<td class='Label' colspan='2'>Carta 1:<br>
									<textarea class="Caixa" name="carta_cobranca1" rows='10' cols='60'><? echo $carta_cobranca1 </textarea></td>
								<td class='Label' colspan='2'>Carta 2:<br>
									<textarea class="Caixa" name="carta_cobranca2" rows='10' cols='60'><? echo $carta_cobranca2;  </textarea></td>
							</tr>
							<tr>
								<td class='Label' colspan='5' style='color:#797979'>
								[nome_cliente] = Nome do cliente<br>
								[msg_documentos] = Número de documentos vencidos<br>
								[email_cobranca] = E-mail do departamento de cobrança<br>
								(*) Essas TAGS serão subtituidas automatica pelo sistema e são obrigatórias.

								</td>
							</tr>
						</table>
					</div>
					<div id="tab4">
						<table>
							<tr>
								<td colspan='2'><b>Carta Venda</b></td>
								<td colspan='2'><b>Carta Assistência</b></td>
							</tr>
							<tr>
								<td class='Label' width='40'>Email:</td>
								<td align='left' >
									<input class="Caixa" type="text" name="email_venda"  size="50" maxlength="80" value="<? echo $email_venda ?>" >
								</td>
								<td class='Label' width='40'>Email:</td>
								<td align='left' >
									<input class="Caixa" type="text" name="email_assistencia"   size="50" maxlength="80" value="<? echo $email_assistencia ?>" >
								</td>
							</tr>
							<tr>
								<td class='Label' colspan='2'>Carta:<br>
									<textarea class="Caixa" name="carta_venda" rows='10' cols='60'><? echo $carta_venda ></textarea></td>
								<td class='Label' colspan='2'>Carta:<br>
									<textarea class="Caixa" name="carta_assistencia" rows='10' cols='60'><? echo $carta_assistencia ></textarea></td>
							</tr>
						</table>
					</div>
					*/ 
?>