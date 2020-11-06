<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'funcoes.php';
include 'menu.php';
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'compra') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

include "javascript_pesquisas.php";

$sql = "SELECT * 
		FROM tbl_loja_dados 
		WHERE empresa = $login_empresa
		AND   loja    = $login_loja";

$res = pg_exec($sql);

if(@pg_numrows($res)==1){
	$empresa                  = pg_result($res,0,empresa);
	$despesas_administrativas = pg_result($res,0,despesas_administrativas);
	$marketing                = pg_result($res,0,marketing);
	$perdas                   = pg_result($res,0,perdas);
	$comissao_venda           = pg_result($res,0,comissao_venda);
	$regime_tributario        = pg_result($res,0,regime_tributario);
	$simples_federal          = pg_result($res,0,simples_federal);
	$simples_estadual         = pg_result($res,0,simples_estadual);
	$pis                      = pg_result($res,0,pis);
	$cofins                   = pg_result($res,0,cofins);
	$irpj                     = pg_result($res,0,irpj);
	$contribuicao_social      = pg_result($res,0,contribuicao_social);
	$iss                      = pg_result($res,0,iss);
}else{
	echo "Opção não disponível, por favor preencha os dados da empresa!";
}


$btn_acao  = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$linha     = $_POST['linha'];
	$tabelas   = $_POST['coluna'];
	$qtde_peca = $_POST['qtde_peca'];
	$cotacao_item = $_POST['cotacao_item_'.$linha];
	$preco_custo  = $_POST['custo_real_'.$linha];
	$peca         = $_POST['peca_'.$linha];
	if(strlen($cotacao_item)>0){
		$sql = " SELECT peca 
				FROM tbl_cotacao_item 
				WHERE cotacao_item = $cotacao_item 
				AND tbl_cotacao_item.formacao_preco IS NULL";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca = pg_result($res,0,0);
		}
	}
		
			
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	for($y=0; $y < $tabelas; $y++){
		$cod_tabela                = $_POST['tabela_'                   .$linha.'_'.$y];
		$percentual_administrativo = $_POST['percentual_administrativo_'.$linha.'_'.$y];
		$percentual_comissao       = $_POST['percentual_comissao_'      .$linha.'_'.$y];
		$percentual_marketing      = $_POST['percentual_marketing_'     .$linha.'_'.$y];
		$percentual_perdas         = $_POST['percentual_perdas_'        .$linha.'_'.$y];
		$percentual_lucro          = $_POST['percentual_lucro_'         .$linha.'_'.$y];
		$preco_sugerido            = $_POST['preco_sugerido_'           .$linha.'_'.$y];
		$data                      = $_POST['data_'                     .$linha.'_'.$y];

		if(strlen($percentual_administrativo)==0)$percentual_administrativo = 0;
		if(strlen($percentual_comissao)      ==0)$percentual_comissao       = 0;
		if(strlen($percentual_marketing)     ==0)$percentual_marketing      = 0;
		if(strlen($percentual_perdas)        ==0)$percentual_perdas         = 0;
		if(strlen($percentual_lucro)         ==0)$percentual_lucro          = 0;
		if(strlen($preco_sugerido)           ==0){$preco_sugerido            = 0;}else{$preco_sugerido = number_format($preco_sugerido,3,'.','');}
		if(strlen($data)         ==0){$xdata          = current_timestamp;}else{
			$xdata = str_replace("/","",$data);
			$xdata =  "'" . substr($xdata,4,4) . "-" . substr($xdata,2,2) . "-" . substr($xdata,0,2) . "'";
		}
		

		$sql = "SELECT	tabela_item_erp           ,
						tabela                    ,
						preco                     ,
						peca                      ,
						data_vigencia             ,
						termino_vigencia          ,
						percentual_marketing      ,
						percentual_lucro          ,
						percentual_vendas         ,
						percentual_administrativo ,
						percentual_comissao       ,
						percentual_perdas
				FROM tbl_tabela_item_erp 
				WHERE tabela = $cod_tabela
				AND peca = $peca
				AND termino_vigencia is null
				ORDER BY tabela_item_erp desc
				LIMIT 1;";
		$res = pg_exec($con,$sql);
//		echo $sql;
		$msg_erro .= pg_errormessage($con);    
		if(strlen($msg_erro)==0){
			if(pg_num_rows($res)>0){
				$tabela_item_erp = pg_result($res,0,tabela_item_erp);
				$sql = "UPDATE tbl_tabela_item_erp set termino_vigencia = $xdata
						where tabela = $cod_tabela
						AND peca = $peca
						AND tabela_item_erp = $tabela_item_erp";
				$res = pg_exec($con,$sql);
//				echo $sql;
//mudar current_timestamp  Pauroooo
				$msg_erro .= pg_errormessage($con);    
			}
		}
		if(strlen($msg_erro)==0){
			$sql = "INSERT INTO tbl_tabela_item_erp(
								tabela                  , 
								preco                   , 
								peca                    , 
								data_vigencia           , 
								percentual_marketing    , 
								percentual_lucro        ,
								percentual_administrativo,
								percentual_comissao     ,
								percentual_perdas,
								preco_custo
							)values(
								$cod_tabela                ,
								$preco_sugerido            ,
								$peca                      ,
								$xdata                     ,
								$percentual_marketing      ,
								$percentual_lucro          ,
								$percentual_administrativo ,
								$percentual_comissao       , 
								$percentual_perdas         ,
								$preco_custo
							)";
			$res = pg_exec($con,$sql);
		//	echo $sql;
			//mudar current_timestamp  Pauroooo
			$msg_erro .= pg_errormessage($con);    
		}
	}
	if(strlen($msg_erro)==0 and strlen($cotacao_item)>0){//atualiza cotacao
		$sql = "UPDATE tbl_cotacao_item set formacao_preco = $xdata
				where cotacao_item = $cotacao_item 
				AND tbl_cotacao_item.formacao_preco IS NULL";	
		$res = pg_exec($con,$sql);
//		echo $sql;
		$msg_erro .= pg_errormessage($con);
	//	$msg_erro = "1";
	}
	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


?>
<script language='javascript'>
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function recalcular(valor1,valor2,c,valor_porcentagem){

	var total1 = parseFloat(document.getElementById(valor1).value);
	var total2 = parseFloat(document.getElementById(valor2).value);
	var coefic = parseFloat(document.getElementById(c).value);

	valor_total_geral = ( ( (parseFloat(total1) * parseFloat(coefic)) - parseFloat(total2) ) / parseFloat(total2) ) * 100;

	if (valor_total_geral=='NaN') {
		valor_total_geral =0;
	}else{
		valor_total_geral = parseFloat(valor_total_geral).toFixed(2);
	}

	document.getElementById(valor_porcentagem).value =  Math.round(valor_total_geral,2);

}

function porcentagem(ipi,icms,custo_real,percentual_administrativo,percentual_comissao,percentual_marketing,percentual_perdas,percentual_lucro,/*percentual_vendas,*/preco){

	var xipi                       = parseFloat(document.getElementById(ipi).value);
	var xicms                      = parseFloat(document.getElementById(icms).value);
	var xpercentual_administrativo = parseFloat(document.getElementById(percentual_administrativo).value);
	var xpercentual_comissao       = parseFloat(document.getElementById(percentual_comissao).value);
	var xpercentual_marketing      = parseFloat(document.getElementById(percentual_marketing).value);
	var xpercentual_perdas         = parseFloat(document.getElementById(percentual_perdas).value);
	var xpercentual_lucro          = parseFloat(document.getElementById(percentual_lucro).value);
	var xpreco                     = parseFloat(document.getElementById(preco).value);
	var xcusto_real                = parseFloat(document.getElementById(custo_real).value);

	var xpis                       = parseFloat(document.getElementById('pis').value);
	var xcofins                    = parseFloat(document.getElementById('cofins').value);
	var xirpj                      = parseFloat(document.getElementById('irpj').value);
	var xcontribuicao_social       = parseFloat(document.getElementById('contribuicao_social').value);

	if(xipi                  =='NaN')      xipi   = 0;
	if(xicms                 =='NaN')      xicms  = 0;
	if(xpercentual_administrativo =='NaN') xpercentual_administrativo = 0;
	if(xpercentual_comissao  =='NaN')      xpercentual_comissao  = 0;
	if(xpercentual_marketing =='NaN')      xpercentual_marketing = 0;
	if(xpercentual_perdas    =='NaN')      xpercentual_perdas    = 0;
	if(xpercentual_lucro     =='NaN')      xpercentual_lucro     = 0;
	if(xpreco                =='NaN')      xpreco                = 0;
	if(xcusto_real           =='NaN')      xcusto_real           = 0;

	if(xpis                   =='NaN')      xpis                 = 0;
	if(xcofins                =='NaN')      xcofins              = 0;
	if(xirpj                  =='NaN')      xirpj                = 0;
	if(xcontribuicao_social   =='NaN')      xcontribuicao_social = 0;

	vsugerido = xcusto_real/(100-(xpercentual_administrativo + xpercentual_comissao + xpercentual_marketing + xpercentual_perdas + xpercentual_lucro + xpis + xcofins + xirpj + xcontribuicao_social))*100;
	//if(vsugerido   =='NaN') vsugerido='';
	document.getElementById(preco).value = parseFloat(vsugerido).toFixed(3);
	//alert(vpercentual_administrativo);
/* calculo anterior
	vpercentual_administrativo = xcusto_real * (xpercentual_administrativo/100);
	vpercentual_comissao       = xcusto_real * (xpercentual_comissao      /100);
	vpercentual_marketing      = xcusto_real * (xpercentual_marketing     /100);
	vpercentual_perdas         = xcusto_real * (xpercentual_perdas        /100);
	vpercentual_lucro          = xcusto_real * (xpercentual_lucro         /100);
	margem_contribuicao  = xcusto_real + vpercentual_administrativo + vpercentual_comissao + vpercentual_marketing + vpercentual_perdas + vpercentual_lucro;

	vicms_venda          = margem_contribuicao * (xicms/100);
	vpis                 = margem_contribuicao * (xpis/100);
	vcofins              = margem_contribuicao * (xcofins/100);
	virpj                = margem_contribuicao * (xirpj/100);
	vcontribuicao_social = margem_contribuicao * (xcontribuicao_social/100);
	somatorio            = margem_contribuicao + vicms_venda + vpis + vcofins + virpj + vcontribuicao_social;

	vinversa  =  100 - (xicms + xpis + xcofins + xirpj + xcontribuicao_social);
	vsugerido = margem_contribuicao / (vinversa/100);


	document.getElementById(preco).value = parseFloat(vsugerido).toFixed(3);*/

}
function porcentagem_negativa(ipi,icms,custo_real,percentual_administrativo,percentual_comissao,percentual_marketing,percentual_perdas,percentual_lucro,preco_sugerido){
	
	var xipi                       = parseFloat(document.getElementById(ipi).value);
	var xicms                      = parseFloat(document.getElementById(icms).value);
	var xpercentual_administrativo = parseFloat(document.getElementById(percentual_administrativo).value);
	var xpercentual_comissao       = parseFloat(document.getElementById(percentual_comissao).value);
	var xpercentual_marketing      = parseFloat(document.getElementById(percentual_marketing).value);
	var xpercentual_perdas         = parseFloat(document.getElementById(percentual_perdas).value);
	var xpercentual_lucro          = parseFloat(document.getElementById(percentual_lucro).value);
	var xpreco_sugerido            = parseFloat(document.getElementById(preco_sugerido).value);
	var xcusto_real                = parseFloat(document.getElementById(custo_real).value);

	var xpis                       = parseFloat(document.getElementById('pis').value);
	var xcofins                    = parseFloat(document.getElementById('cofins').value);
	var xirpj                      = parseFloat(document.getElementById('irpj').value);
	var xcontribuicao_social       = parseFloat(document.getElementById('contribuicao_social').value);

	if(xipi                  =='NaN')      xipi   = 0;
	if(xicms                 =='NaN')      xicms  = 0;
	if(xpercentual_administrativo =='NaN') xpercentual_administrativo = 0;
	if(xpercentual_comissao  =='NaN')      xpercentual_comissao  = 0;
	if(xpercentual_marketing =='NaN')      xpercentual_marketing = 0;
	if(xpercentual_perdas    =='NaN')      xpercentual_perdas    = 0;
	if(xpercentual_lucro     =='NaN')      xpercentual_lucro     = 0;
	if(xpreco_sugerido       =='NaN')      xpreco_sugerido                = 0;
	if(xcusto_real           =='NaN')      xcusto_real           = 0;

	if(xpis                   =='NaN')      xpis                 = 0;
	if(xcofins                =='NaN')      xcofins              = 0;
	if(xirpj                  =='NaN')      xirpj                = 0;
	if(xcontribuicao_social   =='NaN')      xcontribuicao_social = 0;
	
	vpercentual_administrativo = xpreco_sugerido * (xpercentual_administrativo/100);
	vpercentual_comissao       = xpreco_sugerido * (xpercentual_comissao      /100);
	vpercentual_marketing      = xpreco_sugerido * (xpercentual_marketing     /100);
	vpercentual_perdas         = xpreco_sugerido * (xpercentual_perdas        /100);

	vpis                   = xpreco_sugerido * (xpis                        /100);
	vcofins                = xpreco_sugerido * (xcofins                     /100);
	virpj                  = xpreco_sugerido * (xirpj                       /100);
	vcontribuicao_social   = xpreco_sugerido * (xcontribuicao_social        /100);

	vpercentual_lucro          = xpreco_sugerido - xcusto_real - vpercentual_administrativo - vpercentual_comissao - vpercentual_marketing - vpercentual_perdas - vpis - vcofins - virpj - vcontribuicao_social;
	
	apercentual_lucro = (vpercentual_lucro/xpreco_sugerido)*100;

/*	if(apercentual_lucro =='-Infinity') apercentual_lucro ='0';
	if(apercentual_lucro =='NaN') apercentual_lucro ='0';*/
	document.getElementById(percentual_lucro).value = parseFloat(apercentual_lucro).toFixed(2);


}
function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.frm_procura.peca_descricao;
		janela.referencia= document.frm_procura.peca_referencia;
		//janela.linha     = document.frm_produto.linha;
		//janela.familia   = document.frm_produto.familia;
		janela.focus();
	}
}
</script>
<style>

.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 12px;
	
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#FFF;
}
.Titulo_Colunas{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}



caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}

</style>

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='400' border='0' class='tabela'>
	<tr height='20' bgcolor='#7392BF'>
		<td class='Titulo_Tabela' align='center' colspan='6'>FORMAÇÃO DE PREÇO</td>
	</tr>
	<tr height='10'>
		<td  align='center' colspan='6'></td>
	</tr>
	<tr>
	<td class='Label'>
	<div id="container-Principal">
		<div id="tab1Procurar">
			<form name="frm_procura" method="post" action="<? echo $PHP_SELF ?>">
			<table align='left' width='100%' border='0' class='tabela' cellpadding='2' cellspacing='2' >
					<tr>
						<td class='Label' colspan='2' align='center'>Referência</td>
						<td align='left'  align='center'>
						<input class="Caixa" type="text" name="peca_referencia" size="10" maxlength="10" value="<? echo $peca_referencia; ?>" >
						<img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm_procura.peca_referencia, 'referencia')"  style='cursor: pointer'>
						</td>

						<td class='Label' align='center'>Descrição</td>
						<td align='left'  colspan='2'><input class="Caixa" type="text" name="peca_descricao" size="10" maxlength="10" value="<? echo $data_final; ?>">
						<img src="imagens/lupa.png" border='0' onclick="javascript: fnc_pesquisa_produto (document.frm_procura.peca_descricao, 'descricao')"  style='cursor: pointer'>
						</td>
					</tr>
					<tr><td width='10px'>&nbsp;</td>

						<td class='Label' align='center' colspan='4'>Família

						<select name='familia'>
						<option value=''></option>
						<?	
							$sql = "SELECT familia,descricao
									FROM tbl_familia
									WHERE fabrica = $login_empresa
									ORDER BY descricao ASC";
							$res = pg_exec($con,$sql);
							if(pg_numrows($res)>0){
								for($i=0;pg_numrows($res)>$i;$i++){
									$familia = pg_result($res,$i,familia);
									$descricao = pg_result($res,$i,descricao);
									echo "<option value='$familia'>$descricao</option>";
								}
							}
	
						?>
						</select>
						</td>
						<td width='10px'>&nbsp;</td>
					</tr>
				<tr>
						<td colspan='6' align='center'>
							<br>
							<input name='btn_pesquisar' type='hidden'>
							<input name='pesquisar' type='button' class='botao' 
							onclick="document.frm_procura.btn_pesquisar.value='pesquisar';document.frm_procura.submit();" value='Pesquisar'>
						</td>
					</tr>
			</table>
			</form>
		</div>
	</div>
	</td>
	</tr>
	<tr height='20'>
		<td  align='center' colspan='6'></td>
	</tr>
</table>



<?
if(strlen($msg_erro)>0){
echo "<table border='0' width='100%' cellpadding='0' cellspacing='2' align='center'  bgcolor='#FF3300'>";
echo "<tr>";
echo "<td align='center'>$msg_erro</td>";
echo "</tr>";
echo "</table>";

}
//alterado Gustavo HD 6070 (colocado filtro nas buscas)
$btn_pesquisar = $_POST['btn_pesquisar'];
if(strlen($btn_pesquisar)>0){
$familia           = $_POST['familia'];
$peca_referencia   = trim($_POST['peca_referencia']);
$peca_descricao    = trim($_POST['peca_descricao']);
	if(strlen($peca_referencia)>0){
		$sql = "select peca 
				from tbl_peca 
				where referencia = '$peca_referencia' 
				and fabrica=$login_empresa";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca = pg_result($res,0,0);
	//fazer sql para buscar as peças
			$cond_01 = "tbl_peca.peca = $peca ";
		}
	}

	if(strlen($familia) > 0){
		$cond_01 .= "tbl_peca_item.familia = $familia ";
	}
}
	$sql = "
				SELECT	tbl_cotacao_item.cotacao_item                   ,
						tbl_peca.descricao as peca_descricao            ,
						tbl_peca.peca                                   ,
						tbl_peca.referencia as peca_referencia          ,
						tbl_pedido.pedido                               ,
						tbl_pedido.data    as data_pedido               ,
						tbl_pedido.valor_frete                          ,
						tbl_pedido.total                                ,
						tbl_pedido_item.preco     as preco_compra       ,
						tbl_pedido_item.qtde      as qtde_compra        ,
						tbl_estoque.qtde          as qtde_estoque       ,
						tbl_estoque_extra.quantidade_entregar           ,
						tbl_pedido_item.ipi                             ,
						tbl_pedido_item.icms                            ,
						tbl_peca_item.valor_custo_medio                 
				FROM tbl_cotacao_fornecedor_item
				JOIN tbl_cotacao_fornecedor on tbl_cotacao_fornecedor.cotacao_fornecedor = tbl_cotacao_fornecedor_item.cotacao_fornecedor
				JOIN tbl_pedido             on tbl_pedido.cotacao_fornecedor             = tbl_cotacao_fornecedor.cotacao_fornecedor and tbl_pedido.cotacao_fornecedor is not null
				JOIN tbl_pedido_item        on tbl_pedido.pedido                         = tbl_pedido_item.pedido and tbl_pedido_item.peca = tbl_cotacao_fornecedor_item.peca
				JOIN tbl_pessoa_fornecedor  on tbl_pessoa_fornecedor.pessoa              = tbl_cotacao_fornecedor.pessoa_fornecedor
				JOIN tbl_cotacao            on tbl_cotacao.cotacao                       = tbl_cotacao_fornecedor.cotacao
				JOIN tbl_cotacao_item       on (tbl_cotacao_item.cotacao                 = tbl_cotacao.cotacao
										AND tbl_cotacao_item.peca                        = tbl_cotacao_fornecedor_item.peca)
				JOIN tbl_peca               on tbl_peca.peca                             = tbl_cotacao_item.peca
				JOIN tbl_peca_item          on tbl_peca.peca                             = tbl_peca_item.peca
				JOIN tbl_estoque            on tbl_estoque.peca                          = tbl_peca.peca
				JOIN tbl_estoque_extra      on tbl_estoque_extra.peca                    = tbl_estoque.peca
				WHERE tbl_cotacao.empresa           = $login_empresa
				AND   tbl_cotacao_fornecedor.status = 'cotada' 
				AND   tbl_cotacao_item.status       = 'comprado'
				AND   tbl_cotacao_item.formacao_preco IS NULL";
				if(strlen($cond_01) > 0){
					$sql.=" and ";
				}
				$sql .="$cond_01
				ORDER BY	tbl_peca.descricao, 
							tbl_pedido.data";

	$res = pg_exec ($con,$sql) ;
//echo nl2br($sql);
	if (pg_numrows($res) == 0) {
	$sql = "
				SELECT	'' as cotacao_item                   ,
						tbl_peca.descricao as peca_descricao        ,
						tbl_peca.fabrica                            ,
						tbl_peca.peca                               ,
						tbl_peca.referencia as peca_referencia      ,
						'' as  pedido                               ,
						'' as data_pedido               ,
						'' as  preco_compra       ,
						'' as  qtde_compra        ,
						'' as  qtde_estoque       ,
						'' as  quantidade_entregar           ,
						'' as ipi                            ,
						'' as icms                           ,
						'' as valor_frete                    ,
						'' as total                          ,
						tbl_peca_item.valor_custo_medio      ,
						tbl_peca_item.familia
				FROM tbl_peca 
				JOIN tbl_peca_item          on tbl_peca.peca           = tbl_peca_item.peca
				left JOIN tbl_estoque       on tbl_estoque.peca        = tbl_peca.peca
				left JOIN tbl_estoque_extra on tbl_estoque_extra.peca  = tbl_estoque.peca
				where $cond_01 
				";
	$res = @pg_exec ($con,$sql) ;
	}
	//echo $sql;
//echo "<BR>numero:".pg_numrows($res);
	if (@pg_numrows($res) > 0) {
		echo "<br>";
		echo "<form name='frm' method='post' action='$PHP_SELF'>";
		/*echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";*/
		echo "<input id='pis' name='pis' value='$pis' type='hidden'>";
		echo "<input id='cofins' name='cofins' value='$cofins' type='hidden'>";
		echo "<input id='irpj' name='irpj' value='$irpj' type='hidden'>";
		echo "<input id='contribuicao_social' name='contribuicao_social' value='$contribuicao_social' type='hidden'>";
		echo "<table border='1' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='#052756' align='center'>";
		echo "<tr class='Titulo_Tabela' bgcolor='#7392BF'>";
		echo "<td colspan='12' align='center'>Formação de Preço do Produto</td>";

		$xsql = "SELECT	tbl_tabela.tabela,
						tbl_tabela.sigla_tabela,
						tbl_tabela.descricao
				FROM tbl_tabela
				WHERE tbl_tabela.fabrica = $login_empresa
				AND tbl_tabela.ativa is true
				ORDER BY sigla_tabela";
		$xres = pg_exec($con,$xsql);
		
		if(pg_numrows($xres)>0){
			for($x=0;pg_numrows($xres)>$x;$x++){
				$tabela       = pg_result($xres,$x,tabela);
				$sigla_tabela = pg_result($xres,$x,sigla_tabela);
				$descricao    = pg_result($xres,$x,descricao);
				$cor = array("#9999CC","#489D15","#49188f","#0042A6","#A9AC6F");
				echo "<td colspan='8' class='Titulo_Tabela' bgcolor='$cor[$x]' align='center'>$sigla_tabela - $descricao</td>";
			}
		//"#FFEAC0","#DDF8CC","#E3D6F8","#E1EDFF","#99CCCC"
		}
		echo "</tr>";

		echo "<tr height='20' bgcolor='#7392BF' class='Titulo_Tabela_Pequeno'>";
		echo "<td align='center' >Referência</td>";
		echo "<td align='center' >Descrição</td>";
		echo "<td align='center' >Estoque</td>";
		echo "<td align='center' >Qtde<br> Entregar</td>";
		echo "<td align='center' >Pedido</td>";
		echo "<td align='center' >Qtde</td>";
		echo "<td align='center' >Preço<BR>NF</td>";
		echo "<td align='center' >% ICMS</td>";
		echo "<td align='center' >IPI</td>";
		echo "<td align='center' ><a href='#' title='Preço de compra - ICMS'  class='text_curto'>";
		echo "<span> ";
		echo "<font color='#FFFFFF'>Custo<br>Real</FONT></a></span>";
		echo "</td>"; //Preço de compra - icms
		echo "<td align='center' >Média</td>";
		echo "<td align='center' >Frete</td>";
	/*colocar aqui*/
		$xsql = "SELECT	tbl_tabela.tabela,
						tbl_tabela.sigla_tabela,
						tbl_tabela.descricao
				FROM tbl_tabela
				WHERE tbl_tabela.fabrica = $login_empresa
				AND tbl_tabela.ativa is true
				ORDER BY sigla_tabela";
		$xres = pg_exec($con,$xsql);
		
		if(pg_numrows($xres)>0){
			for($x=0;pg_numrows($xres)>$x;$x++){
				$tabela       = pg_result($xres,$x,tabela);
				$sigla_tabela = pg_result($xres,$x,sigla_tabela);
				$descricao    = pg_result($xres,$x,descricao);
				$cor = array("#9999CC","#489D15","#49188f","#0042A6","#A9AC6F");
			
				echo "<td align='center' bgcolor='$cor[$x]'>% Desp. Adm </td>";
				echo "<td align='center' bgcolor='$cor[$x]'>% Comissão </td>";
				echo "<td align='center' bgcolor='$cor[$x]'>% Marketing </td>";
				echo "<td align='center' bgcolor='$cor[$x]'>% Perda </td>";
				echo "<td align='center' bgcolor='$cor[$x]'>% Lucro </td>";
				echo "<td align='center' bgcolor='$cor[$x]'>Sugerido</td>";
				echo "<td align='center' bgcolor='$cor[$x]'>Data</td>";
				echo "<td align='center' bgcolor='$cor[$x]'>Atual</td>";
			}
		}

		
		echo "<td align='center' >Ação</td>";
		echo "</tr>";

		for ($i = 0; $i <pg_numrows($res) ; $i++) {

			$peca              = trim(pg_result($res,$i,peca));
			$peca_referencia   = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao    = trim(pg_result($res,$i,peca_descricao));
			// retirado por Igor conforme solicitação do Samuel.
			//$peca_descricao    = substr($peca_descricao,0,20);
			$pedido            = trim(pg_result($res,$i,pedido));
			$preco_compra      = trim(pg_result($res,$i,preco_compra));
			$preco_compra      = number_format($preco_compra,3,'.','.');
			$qtde_compra       = trim(pg_result($res,$i,qtde_compra));
			$estoque           = trim(pg_result($res,$i,qtde_estoque));
			$qtde_entregar     = trim(pg_result($res,$i,quantidade_entregar));
			$icms              = trim(pg_result($res,$i,icms));
			$ipi               = trim(pg_result($res,$i,ipi));
			$cotacao_item      = trim(pg_result($res,$i,cotacao_item));
			$valor_custo_medio = trim(pg_result($res,$i,valor_custo_medio));
			$valor_custo_medio = number_format($valor_custo_medio,3,'.','.');
			$preco_custo       = number_format($preco_compra -($preco_compra *($icms/100)),3,'.','.');
			$valor_frete       = trim(pg_result($res,$i,valor_frete));
			$total             = trim(pg_result($res,$i,total));
//Igor criou a formula para HD 4744, Paulo colocou

			$subtotal=$preco_compra*$qtde_compra;
			if(strlen($total)>0){
				$totalporcentagem=($subtotal * 100)/$total;
				$valor_frete_peca = (( $valor_frete * $totalporcentagem)/100)/$qtde_compra;
				$valor_frete_peca = number_format($valor_frete_peca,2,'.','.');
			}

			if($i%2==0)$cor = '#ECF3FF';
			else       $cor = '#FFFFFF';

			echo "<tr bgcolor='$cor' class='Conteudo'>";
			echo "<td align='center'> $peca_referencia</td>";
			echo "<td align='left' nowrap >$peca_descricao</td>";
			echo "<td align='center'>$estoque</td>";
			echo "<td align='center'>$qtde_entregar</td>";
			echo "<td align='left'  >$pedido</td>";
			echo "<td align='center'>$qtde_compra</td>";
			echo "<td align='center' >$preco_compra</td>";

			echo "<td align='center'><input id='icms_$i' name='icms_$i' value='$icms' type='text' size='2'"; 
			if(strlen($icms)>0) echo "readOnly";
			echo " style='text-align:center'></td>";
			echo "<td align='right'><input id='ipi_$i' name='ipi_$i' value='$ipi' type='text' size='2'"; 
			if(strlen($ipi)>0) echo "readOnly";
			echo " style='text-align:center'></td>";
			echo "<td align='right'>"; /*$preco_custo*/
			echo "<input id='custo_real_$i' onblur='checarNumero(this);' name='custo_real_$i' value='$preco_custo' type='text' size='2'"; 
			if($preco_custo<>"0.000") echo "readOnly";
			echo " style='text-align:center'>";
			echo "<input id='cotacao_item_$i' name='cotacao_item_$i' value='$cotacao_item' type='hidden'>
			<input id='peca_$i' name='peca_$i' value='$peca' type='hidden'>";
			echo "</td>";
			echo "<td align='right' >$valor_custo_medio</td>";
			echo "<td align='right' >$valor_frete_peca</td>";
			/*colocar aqui*/
			/* peças */
				$xsql = "SELECT	tbl_tabela.tabela       ,
								tbl_tabela.sigla_tabela ,
								tbl_tabela.descricao
						FROM tbl_tabela
						WHERE tbl_tabela.fabrica = $login_empresa
						AND tbl_tabela.ativa is true
						ORDER BY sigla_tabela";
				$xres = pg_exec($con,$xsql);
				if(pg_numrows($xres)>0){
					for($x=0;pg_numrows($xres)>$x;$x++){
						$tabela       = pg_result($xres,$x,tabela);
						$sigla_tabela = pg_result($xres,$x,sigla_tabela);
						$descricao    = pg_result($xres,$x,descricao);
						$cor = array("#E4E4F1","#DDF8CC","#E3D6F8","#E1EDFF","#DBDCC2");

						$xxsql = "SELECT	peca                                                    ,
											tabela                                                  ,
											CASE WHEN  preco  is null then '0'
											ELSE preco end as preco                                 ,
											data_vigencia                                           ,
											termino_vigencia                                        ,
											CASE WHEN  percentual_marketing  is null then '0'
											ELSE percentual_marketing end as percentual_marketing   ,
											CASE WHEN  percentual_lucro  is null then '0'
											ELSE percentual_lucro end as percentual_lucro           ,
											CASE WHEN  percentual_vendas  is null then '0'
											ELSE percentual_vendas end as percentual_vendas         ,
											CASE WHEN  percentual_administrativo  is null then '0'
											ELSE percentual_administrativo end as percentual_administrativo,
											CASE WHEN  percentual_comissao  is null then '0'
											ELSE percentual_comissao end as percentual_comissao      ,
											CASE WHEN  percentual_perdas  is null then '0'
											ELSE percentual_perdas end as percentual_perdas
								FROM tbl_tabela_item_erp
								WHERE tbl_tabela_item_erp.tabela = $tabela
								AND   tbl_tabela_item_erp.peca   = $peca
								and   tbl_tabela_item_erp.termino_vigencia is null
								ORDER BY tbl_tabela_item_erp.data_vigencia desc
								LIMIT 1";

							$xxres = pg_exec($con,$xxsql);
							if(pg_numrows($xxres)>0){

								for($w=0;pg_numrows($xxres)>$w;$w++){
									$percentual_marketing      = pg_result($xxres,$w,percentual_marketing);
									$percentual_lucro          = pg_result($xxres,$w,percentual_lucro);
									$percentual_vendas         = pg_result($xxres,$w,percentual_vendas);
									$percentual_administrativo = pg_result($xxres,$w,percentual_administrativo);
									$percentual_comissao       = pg_result($xxres,$w,percentual_comissao);
									$percentual_perdas         = pg_result($xxres,$w,percentual_perdas);
									$preco                     = pg_result($xxres,$w,preco);

									
									/*calculo do preco sugerido //ATENCAO SE ALTERAR REGRA DE CALCULO, TEM QUE ALTERAR O JAVASCRIPT*/
									
									$preco_sugerido = $preco_custo/(100-($percentual_administrativo + $percentual_comissao + $percentual_marketing
										+ $percentual_perdas + $percentual_lucro + $pis + $cofins + $irpj + $contribuicao_social))*100;
									$preco_sugerido = number_format($preco_sugerido,3,'.','.');
									
									/*ALTERADO
									$vpercentual_administrativo = $preco_custo * ($percentual_marketing /100);
									$vpercentual_comissao       = $preco_custo * ($percentual_comissao  /100);
									$vpercentual_marketing      = $preco_custo * ($percentual_marketing /100);
									$vpercentual_perdas         = $preco_custo * ($percentual_perdas    /100);
									$vpercentual_lucro          = $preco_custo * ($percentual_lucro     /100);

									$margem_contribuicao  = $preco_custo + $vpercentual_administrativo + $vpercentual_comissao + $vpercentual_marketing + $vpercentual_perdas + $vpercentual_lucro;

									$vicms_venda          = $margem_contribuicao * ($icms/100);
									$vpis                 = $margem_contribuicao * ($pis/100);
									$vcofins              = $margem_contribuicao * ($cofins/100);
									$virpj                = $margem_contribuicao * ($irpj/100);
									$vcontribuicao_social = $margem_contribuicao * ($contribuicao_social/100);
									$somatorio            = $margem_contribuicao + $vicms_venda + $vpis + $vcofins + $virpj + $vcontribuicao_social;

									$vinversa  =  100 - ($icms + $pis + $cofins + $irpj + $contribuicao_social);
									$preco_sugerido = $margem_contribuicao / ($vinversa/100);
									$preco_sugerido = number_format($preco_sugerido,3,'.','.');
									*/
									/*calculo do preco sugerido*/

									echo "<input id='peca_$i'   name='peca_$i'    value='$peca'    type='hidden'>";//mudar para hidden
									

									echo "<td align='center' bgcolor='$cor[$x]'>";//adm
									echo "<input id='tabela_$i"."_"."$x' name='tabela_$i"."_"."$x'  value='$tabela'  type='hidden'>";//mudar para hidden
									echo "<input type='text' name='percentual_administrativo_$i"."_"."$x'
											id='percentual_administrativo_$i"."_"."$x' 
											value='$percentual_administrativo' 
											onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
											size='3' style='text-align:right'>";

									//echo "$percentual_administrativo &nbsp;";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//comissao
									echo "<input type='text' name='percentual_comissao_$i"."_"."$x'
											value='$percentual_comissao' 
											id='percentual_comissao_$i"."_"."$x' 
											onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
											/*	\"percentual_vendas_$i"."_"."$x\",*/
												\"preco_sugerido_$i"."_"."$x\"
											)'   
											size='3' style='text-align:right'>";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//mkt
									echo "<input type='text' name='percentual_marketing_$i"."_"."$x'
										value='$percentual_marketing' 
										id='percentual_marketing_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
										size='3' style='text-align:right'>";
									/*echo "$percentual_marketing &nbsp;";*/
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//perdas
									echo "<input type='text' name='percentual_perdas_$i"."_"."$x'
										value='$percentual_perdas' 
										id='percentual_perdas_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
										size='3' style='text-align:right'>";
									/*echo "$percentual_perdas &nbsp;";*/
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//lucro
									echo "<input type='text' name='percentual_lucro_$i"."_"."$x'
										value='$percentual_lucro' 
										id='percentual_lucro_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
										size='3' style='text-align:right'>";
								//echo "$percentual_lucro&nbsp;";
									echo "</td>";

									echo "<td align='center' bgcolor='$cor[$x]'>";//sugerido
									echo "<input type='text' name='preco_sugerido_$i"."_"."$x'
										value='$preco_sugerido' 
										id='preco_sugerido_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem_negativa(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)'
										size='3' style='text-align:right'>";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//data
									echo "<input type='text' name='data_$i"."_"."$x'
										value='' 
										id='data_$i"."_"."$x' 
										size='3'  maxlength='10' onKeyUp=\"formata_data(this.value,'frm', 'data_$i"."_"."$x')\">";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//preco atual
									echo "<input type='text' name='preco_$i"."_"."$x'
										value='$preco' 
										id='preco_$i"."_"."$x' 
										size='3' style='text-align:right'>";
									echo "</td>";
								}
							
							}else{
								$percentual_administrativo = "";
								$percentual_comissao       = "";
								$percentual_lucro          = "";
								$percentual_marketing      = "";
								$percentual_perdas         = "";
								//produto inicialmente as porcentagens no produto, se nao tiver procura na familia, se nao tiver procura nos dados da empresa
								$asql = "SELECT	percentual_comissao        ,
												percentual_administrativo  ,
												percentual_lucro           ,
												percentual_marketing       ,
												percentual_perdas
										FROM tbl_peca_item
										WHERE tbl_peca_item.peca = $peca
										AND percentual_comissao       NOTNULL
										AND	percentual_administrativo NOTNULL
										AND	percentual_lucro          NOTNULL
										AND	percentual_marketing      NOTNULL
										AND	percentual_perdas         NOTNULL";
								$ares = pg_exec($con,$asql);
								if(pg_numrows($ares)>0){
									$percentual_administrativo  = pg_result($ares,0,percentual_administrativo);
									$percentual_comissao        = pg_result($ares,0,percentual_comissao);
									$percentual_lucro           = pg_result($ares,0,percentual_lucro);
									$percentual_marketing       = pg_result($ares,0,percentual_marketing);
									$percentual_perdas          = pg_result($ares,0,percentual_perdas);
								}else{
									$asql = "SELECT	tbl_familia.percentual_comissao        ,
													tbl_familia.percentual_administrativo  ,
													tbl_familia.percentual_lucro           ,
													tbl_familia.percentual_marketing       ,
													tbl_familia.percentual_perdas
											FROM tbl_familia
											JOIN tbl_peca_item on tbl_peca_item.familia = tbl_familia.familia
											WHERE tbl_peca_item.peca = $peca
											AND tbl_familia.percentual_comissao       NOTNULL
											AND	tbl_familia.percentual_administrativo NOTNULL
											AND	tbl_familia.percentual_lucro          NOTNULL
											AND	tbl_familia.percentual_marketing      NOTNULL
											AND	tbl_familia.percentual_perdas         NOTNULL";
									$ares = pg_exec($con,$asql);
									if(pg_numrows($ares)>0){
										$percentual_administrativo  = pg_result($ares,0,percentual_administrativo);
										$percentual_comissao        = pg_result($ares,0,percentual_comissao);
										$percentual_lucro           = pg_result($ares,0,percentual_lucro);
										$percentual_marketing       = pg_result($ares,0,percentual_marketing);
										$percentual_perdas          = pg_result($ares,0,percentual_perdas);
									}else{
										$asql = "SELECT	tbl_loja_dados.despesas_administrativas        ,
														tbl_loja_dados.comissao_venda  ,
														tbl_loja_dados.percentual_lucro           ,
														tbl_loja_dados.marketing       ,
														tbl_loja_dados.perdas
												FROM tbl_loja_dados
												WHERE tbl_loja_dados.empresa = $login_empresa
												AND despesas_administrativas  NOTNULL
												AND	comissao_venda            NOTNULL
												AND	percentual_lucro          NOTNULL
												AND	marketing                 NOTNULL
												AND	perdas                    NOTNULL";
										$ares = pg_exec($con,$asql);
										if(pg_numrows($ares)>0){
											$percentual_administrativo  = pg_result($ares,0,despesas_administrativas);
											$percentual_comissao        = pg_result($ares,0,comissao_venda);
											$percentual_lucro           = pg_result($ares,0,percentual_lucro);
											$percentual_marketing       = pg_result($ares,0,marketing);
											$percentual_perdas          = pg_result($ares,0,perdas);
										}
									}						
								}
													//echo "$sql<BR>$xsql<BR>$ysql";
								if(strlen($percentual_administrativo)==0)$percentual_administrativo = 0;
								if(strlen($percentual_comissao)      ==0)$percentual_comissao       = 0;
								if(strlen($percentual_lucro)         ==0)$percentual_lucro          = 0;
								if(strlen($percentual_marketing)     ==0)$percentual_marketing      = 0;
								if(strlen($percentual_perdas)        ==0)$percentual_perdas         = 0;

								/*calculo do preco sugerido //ATENCAO SE ALTERAR REGRA DE CALCULO, TEM QUE ALTERAR O JAVASCRIPT*/
								
								$preco_sugerido = $preco_custo/(100-($percentual_administrativo + $percentual_comissao + $percentual_marketing
									+ $percentual_perdas + $percentual_lucro + $pis + $cofins + $irpj + $contribuicao_social))*100;
								$preco_sugerido = number_format($preco_sugerido,3,'.','.');
							


									echo "<td align='center' bgcolor='$cor[$x]'>";
									echo "<input id='tabela_$i"."_"."$x' name='tabela_$i"."_"."$x'  value='$tabela'  type='hidden'>";//mudar para hidden
									echo "<input type='text' name='percentual_administrativo_$i"."_"."$x'
											id='percentual_administrativo_$i"."_"."$x' 
											value='$percentual_administrativo' 
											onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
											size='3' style='text-align:right'>";


									//echo "$percentual_administrativo &nbsp;";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//comissao
									echo "<input type='text' name='percentual_comissao_$i"."_"."$x'
											value='$percentual_comissao' 
											id='percentual_comissao_$i"."_"."$x' 
											onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)'   
											size='3' style='text-align:right'>";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//mkt
									echo "<input type='text' name='percentual_marketing_$i"."_"."$x'
										value='$percentual_marketing' 
										id='percentual_marketing_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
										size='3' style='text-align:right'>";
									/*echo "$percentual_marketing &nbsp;";*/
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//perdas
									echo "<input type='text' name='percentual_perdas_$i"."_"."$x'
										value='$percentual_perdas' 
										id='percentual_perdas_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
										size='3' style='text-align:right'>";
									/*echo "$percentual_perdas &nbsp;";*/
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//lucro
									echo "<input type='text' name='percentual_lucro_$i"."_"."$x'
										value='$percentual_lucro' 
										id='percentual_lucro_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)' 
										size='3' style='text-align:right'>";
								//echo "$percentual_lucro&nbsp;";
									echo "</td>";						
									echo "<td align='center' bgcolor='$cor[$x]'>";//sugerido
									echo "<input type='text' name='preco_sugerido_$i"."_"."$x'
										value='$preco_sugerido' 
										id='preco_sugerido_$i"."_"."$x' 
										onblur='
											checarNumero(this);
											porcentagem_negativa(\"ipi_$i\",\"icms_$i\",\"custo_real_$i\",
												\"percentual_administrativo_$i"."_"."$x\",
												\"percentual_comissao_$i"."_"."$x\",
												\"percentual_marketing_$i"."_"."$x\",
												\"percentual_perdas_$i"."_"."$x\",
												\"percentual_lucro_$i"."_"."$x\",
												\"preco_sugerido_$i"."_"."$x\"
											)'
										size='3' style='text-align:right'>";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//data
									echo "<input type='text' name='data_$i"."_"."$x'
										value=''
										id='data_$i"."_"."$x' 
										size='3' maxlength='10' onKeyUp=\"formata_data(this.value,'frm', 'data_$i"."_"."$x')\">";
									echo "</td>";
									echo "<td align='center' bgcolor='$cor[$x]'>";//preco atual
									echo "<input type='text' name='preco_$i"."_"."$x'
										value='$valor_custo_medio' 
										id='preco_$i"."_"."$x' 
										size='3' style='text-align:right'>";
									echo "</td>";


							}
					}
				}

			echo "<td align='right'>";
			echo "<input type='button' value='Gravar'
			onclick=\"javascript:if(document.frm.btn_acao.value!=''){
				alert('Aguarde Submissão'); 
			}else{
				document.frm.btn_acao.value='Gravar';
				document.frm.linha.value='$i';
				document.frm.submit();
			}\">";
			echo "</td>";
			echo "</tr>";


		}
		echo "</table>";
			echo "<input type='hidden' name='linha'  value=''>";
			echo "<input type='hidden' name='coluna'  value='$x'>";
			echo "<input type='hidden' name='btn_acao'  value=''>";
			echo "<input id='qtde_peca'  name='qtde_peca'  value='$i'  type='hidden'>"; //mudar para hidden
			echo "</form>";
	}else{
		echo "<br><p>Nenhuma produto encontrado</p>";
	}

include 'rodape.php'
?>