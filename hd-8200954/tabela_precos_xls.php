<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$hoje = date('Y-m-d');
$data_corte = "2012-06-21";

if (strlen($_GET["tabela"]) > 0)$tabela = $_GET["tabela"];

if (strlen($tabela) == 0 AND $login_fabrica==1){
	$sql = "SELECT tabela FROM tbl_tabela WHERE fabrica  = $login_fabrica AND sigla_tabela = 'BASE7'";
	$res = pg_query($con,$sql);
	$tabela = pg_result($res,0,'tabela');
}

if($tabela == 54) {
	$join_tabela = "JOIN tbl_tabela_item_erp ON tbl_tabela_item_erp.tabela = $tabela and tbl_tabela_item.peca = tbl_tabela_item_erp.peca and  tbl_tabela_item_erp.estado = '$login_contato_estado' "; 
}
$liberar_preco = true ;
if ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560))
	$liberar_preco = false;

if ($login_fabrica == 1) {

	$sqlP_adicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$login_posto} AND fabrica = {$login_fabrica}";
	$resP_adicionais = pg_query($con, $sqlP_adicionais);

	if (pg_num_rows($resP_adicionais) > 0) {
	    $parametrosAdicionais = json_decode(pg_fetch_result($resP_adicionais, 0, "parametros_adicionais"), true);
	    extract($parametrosAdicionais);

	    $tipo_contribuinte = utf8_decode($tipo_contribuinte);

	    if(!empty($tipo_contribuinte) and $tipo_contribuinte <> 't'){
	    	$tipo_contribuinte = 'f';
	    }
	}else{
		$tipo_contribuinte = ' ';
	}


	if(strtotime($hoje) > strtotime($data_corte)){
		  $sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
					tbl_tipo_posto.acrescimo_tabela_base        ,
					tbl_tipo_posto.acrescimo_tabela_base_venda  ,
					tbl_tipo_posto.tx_administrativa            ,
					tbl_tipo_posto.desconto_5estrela            ,
					tbl_tipo_posto.descontos[1] AS desconto1    ,
					tbl_tipo_posto.descontos[2] AS desconto2    ,
					tbl_condicao.acrescimo_financeiro           ,
					case when tbl_tipo_posto.tipo_posto = 36 then ((100 - 18) / 100::float)
					else (100 - tbl_icms.indice)/100 end AS icms     ,
					tbl_icms.indice,
					tbl_peca_icms.indice as icms_peca,
					tbl_posto_fabrica.pedido_em_garantia        ,
					tbl_posto_fabrica.pedido_faturado           ,
					tbl_posto_fabrica.contato_estado           ,
					tbl_tipo_posto.tipo_posto
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
										and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
										and tbl_condicao.condicao     = 50
			JOIN    tbl_icms             on tbl_icms.estado_destino   =  tbl_posto_fabrica.contato_estado
			LEFT JOIN tbl_peca_icms      on tbl_peca_icms.estado_destino = tbl_posto_fabrica.contato_estado
			WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
			AND     tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
	}else{
		$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
					tmp_tipo_posto_black.acrescimo_tabela_base        ,
					tmp_tipo_posto_black.acrescimo_tabela_base_venda  ,
					tmp_tipo_posto_black.tx_administrativa            ,
					tmp_tipo_posto_black.desconto_5estrela            ,
					tbl_condicao.acrescimo_financeiro           ,
					case when tmp_tipo_posto_black.tipo_posto = 36 then ((100 - 18) / 100::float)
					else ((100 - tbl_icms.indice) / 100) end AS icms     ,
					tbl_icms.indice,
					tbl_peca_icms.indice as icms_peca,
					tbl_posto_fabrica.pedido_em_garantia        ,
					tbl_posto_fabrica.contato_estado        ,
					tmp_tipo_posto_black.tipo_posto
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
										and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			JOIN    tmp_tipo_posto_black       on tmp_tipo_posto_black.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN    tbl_tipo_posto  on tmp_tipo_posto_black.tipo_posto = tbl_tipo_posto.tipo_posto
			JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
										and tbl_condicao.condicao     = 50
			JOIN    tbl_icms             on tbl_icms.estado_destino   =  tbl_posto_fabrica.contato_estado
			LEFT JOIN tbl_peca_icms      on tbl_peca_icms.estado_destino = tbl_posto_fabrica.contato_estado
			WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
			AND     tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";

	}
	
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		# HD 219253 ICMS para Locadoras
		$descricao                   = pg_fetch_result($res, 0, descricao);
		$acrescimo_tabela_base       = pg_fetch_result($res, 0, acrescimo_tabela_base);
		$acrescimo_tabela_base_venda = pg_fetch_result($res, 0, acrescimo_tabela_base_venda);
		$acrescimo_financeiro        = pg_fetch_result($res, 0, acrescimo_financeiro);
		$pedido_em_garantia          = pg_fetch_result($res, 0, pedido_em_garantia);
		$icms                        = pg_fetch_result($res, 0, icms);
		$indice_icms                 = pg_fetch_result($res, 0, indice);
		$icms_peca                   = pg_fetch_result($res, 0, icms_peca);
		$desconto_5estrela           = pg_fetch_result($res, 0, desconto_5estrela);
		$desconto1           		 = pg_fetch_result($res, 0, desconto1);
		$desconto2           		 = pg_fetch_result($res, 0, desconto2);
		$estado_posto          		 = pg_fetch_result($res, 0,'contato_estado');

		if(strlen($desconto_5estrela)==0 ){
			$desconto_5estrela = 1;
		}

		if(strlen($desconto1)==0 ){
			$desconto1 = 1;
		}

		if(strlen($desconto2)==0 ){
			$desconto2 = 1;
		}

		if(strtotime($hoje) > strtotime($data_corte)){
			$pedido_faturado = pg_fetch_result($res, 0, pedido_faturado);
		}
	}
}
$title = "Tabela de Preços";

$layout_menu = 'preco';
include "cabecalho.php";

?>

<style>
.letras {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: bold;
	border: 0px solid;
	color:#007711;
	background-color: #ffffff
}

.lista {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 13px;
	font-weight: normal;
	border: 0px solid;
	color:#000000;
}
</style>

<table width="500" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>
</tr>

<?
	$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$nome_fabrica = str_replace(strtolower(pg_fetch_result ($res,0,nome))," ","");

	$data = date ("d-m-Y-H-i-s-u");

	echo `mkdir /tmp/assist`;
	echo `chmod 777 /tmp/assist`;
	//echo `rm -f /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip`;
	//echo `rm -f /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.txt`;
	$diretorio = dirname(__FILE__);
	echo `rm -f $diretorio/download/tabela-$data-$nome_fabrica-$login_posto.zip`;
	echo `rm -f $diretorio/download/tabela-$data-$nome_fabrica-$login_posto.txt`;
	$fp = fopen ("/tmp/assist/tabela-$data-$nome_fabrica.html","w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>TABELA - $data - $nome_fabrica");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");

	fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
	fputs ($fp,"<tr>");
	fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>CÓDIGO</td>");
	fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>DESCRIÇÃO</td>");

	if ($login_fabrica == 1) {
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>ORIGEM</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>CÓDIGO ORIGEM</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>LINHA</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>STATUS</td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>MUDOU PARA</td>");
	}else{
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>UNID</td>");
	}

	if ($liberar_preco) {
		if ($login_fabrica == 1) {
			switch ( substr($descricao,0,3) ) {
				case "Dis" :
				case "DIS" :
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Compra<br>sem IPI</td>");
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Distribuição<br>com IPI</td>");
					if($tabela != 54) { fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Preço<br>sugerido<br>com IPI</td>"); }
				break;
				case "TOP" :
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Compra<br>sem IPI</td>");
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Distribuição<br>com IPI</td>");
					if($tabela != 54) { fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Preço<br>sugerido<br>com IPI</td>"); }
				break;
				case "5SA" :
				case "5SB" :
				case "5SC" :
				case "VIP" :
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Compra<br>sem IPI</td>");
					if($tabela != 54) { fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Preço<br>sugerido<br>com IPI</td>"); }
				break;
				case "AUT" :
					if($pedido_faturado == 't'){
						fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>IPI</td>");
						fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Compra<br>sem IPI</td>");
					}else{
						fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Compra<br>com IPI</td>");
					}
					if($tabela != 54) { fputs ($fp, "<td bgcolor='#E9F3F3' align='center'>Preço<br>sugerido<br>com IPI</td>"); }
				break;
				case "Vip" :
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Sem IPI</td>");
					if($tabela != 54) { fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>PREÇO SUGERIDO COM IPI</td>"); }
				break;
				case "Loc" :
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Sem IPI</td>");
				break;
				default :
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Com IPI</td>");
					if($tabela != 54) { fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>PREÇO SUGERIDO Com IPI</td>"); }
				break;
			}
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>QTD. MÚLTIPLA</td>");
            fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>NCM</td>");
            fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>Caixa<br>Unitário</td>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>Caixa<br>Coletiva</td>");
		}else{

			$preco_ipi = "PREÇO SEM IPI";

			if ($login_fabrica == 175) {

				$preco_ipi = "PREÇO SEM IMPOSTOS";
			}

			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>" . $preco_ipi . "</td>");

			if ($login_fabrica != 175) {

				fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
			}
		}
	}

	if($login_fabrica == 11){
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>ESTOQUE</td>");
	}
	fputs ($fp,"</tr>");


	#HD 17663 - somente peças de linha ativa
	if ($login_fabrica ==50) {
		$where_produto = '';
		$join_produto  = '';
		if (!empty($ref_prod)) {
			$where_produto = " AND tbl_produto.referencia = '$ref_prod'";
		}elseif (!empty($ref_peca)) {
			$join_produto  = " JOIN tbl_peca USING(peca)";
			$where_produto = " AND upper(tbl_peca.referencia) = upper('$ref_peca')";
		}

		$sql = "SELECT DISTINCT tbl_lista_basica.peca
				INTO TEMP tmp_pecas_ativas_$login_fabrica$login_posto
				FROM tbl_lista_basica
				JOIN tbl_produto USING(produto)
				JOIN tbl_linha USING(linha) $join_produto
				WHERE tbl_linha.fabrica = $login_fabrica $where_produto
				AND tbl_linha.ativo IS TRUE;

				CREATE INDEX tmp_pecas_ativas_peca ON tmp_pecas_ativas_$login_fabrica$login_posto(peca);
				";
		$res = pg_query ($con,$sql);
		$join_linhas_ativas = " JOIN tmp_pecas_ativas_$login_fabrica$login_posto USING(peca) ";
	}

	$tipo_posto_sigla = substr($descricao,0,3);
	$sql = "SELECT  tbl_peca.referencia                 ,
					tbl_peca.peca                       ,
					tbl_peca.descricao                  ,
					tbl_peca.ipi                        ,
					tbl_peca.unidade                    ,
					tbl_peca.multiplo                   ,
					tbl_peca.origem                     ,
					tbl_peca.classificacao_fiscal       ,
					tbl_peca.linha_peca                 ,
					tbl_peca.parametros_adicionais      ,
					tbl_depara.para                     ,
					tbl_peca_fora_linha.peca_fora_linha ,
					tbl_tabela_item.preco,
					tbl_peca.ncm ";

	$join_para = "";
	if ($login_fabrica == 138) {
		$join_para = "LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca";
		$sql = "SELECT	CASE WHEN peca_para NOTNULL THEN para.referencia ELSE tbl_peca.referencia END AS referencia ,
						CASE WHEN peca_para NOTNULL THEN peca_para ELSE tbl_peca.peca END AS peca                   ,
						CASE WHEN peca_para NOTNULL THEN para.descricao ELSE tbl_peca.descricao END AS descricao    ,
						CASE WHEN peca_para NOTNULL THEN para.ipi ELSE tbl_peca.ipi END AS ipi                      ,
						CASE WHEN peca_para NOTNULL THEN para.unidade ELSE tbl_peca.unidade END AS unidade          ,
						CASE WHEN peca_para NOTNULL THEN para.multiplo ELSE tbl_peca.multiplo END AS multiplo       ,
						CASE WHEN peca_para NOTNULL THEN para.origem ELSE tbl_peca.origem END AS origem             ,
				        CASE WHEN peca_para NOTNULL THEN para.classificacao_fiscal ELSE tbl_peca.classificacao_fiscal END AS classificacao_fiscal ,		                     
						CASE WHEN peca_para NOTNULL THEN para.linha_peca ELSE tbl_peca.linha_peca END AS linha_peca ,
						CASE WHEN peca_para NOTNULL THEN para.parametros_adicionais ELSE tbl_peca.parametros_adicionais END AS parametros_adicionais ,
						tbl_depara.para                     ,
						tbl_peca_fora_linha.peca_fora_linha ,
						CASE WHEN peca_para NOTNULL THEN tbl_tabela_item.preco ELSE tbl_tabela_item.preco END AS preco   ,
						CASE WHEN peca_para NOTNULL THEN para.ncm ELSE tbl_peca.ncm END AS ncm ";
	}

				if($login_fabrica == 11){
					$sql .=" ,CASE
								WHEN tbl_peca.localizacao <> '' THEN
								'DISPONÍVEL'
								ELSE
								'INDISPONÍVEL'
								END AS estoque ";
				}

				if($login_fabrica == 1){
					$sql .=",
						CASE
						WHEN tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA' THEN
						(tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6)
						WHEN '$tipo_posto_sigla' = 'AUT' AND '$pedido_faturado' <> 't' AND (tbl_peca.origem = 'FAB/SUB' OR tbl_peca.origem = 'TER/SUB' or tbl_peca.origem = 'IMP/SUB') THEN
							(tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)) * (1 + (tbl_peca.ipi / 100))
						WHEN tbl_peca.origem = 'FAB/SUB' OR tbl_peca.origem = 'TER/SUB' or tbl_peca.origem = 'IMP/SUB' THEN
							(tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)) ";
				}

	if ($login_fabrica == 1) {
		if($hoje < $data_corte){

			/*IGOR HD: 21333 - NOVA REGRA PARA TAB. BASE2 (108)*/
			$sql_venda = "CASE
							WHEN tbl_peca.origem = 'FAB/SUB' OR tbl_peca.origem = 'TER/SUB' or tbl_peca.origem = 'IMP/SUB' THEN
								(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100)))
							when tbl_peca.origem in ('FAB/SA','IMP/SA') then
							  (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100))) 
							ELSE
								(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7
						  END AS  venda ";


			switch ( substr($descricao,0,3) ) {
				case "Dis" :
				case "DIS" :

					//hd 17399 - Pa Top Service tem Preço de compra diferenciado conforme chamado
					if ($login_posto == 5355) {
						/*HD: 126046*/
						//$sql .= "((tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)))     AS preco  ,";
						/*VOLTANDO 20/07/2009 - IGOR*/
						$sql .= " ELSE ((tbl_tabela_item.preco / $icms) * 1.51 * 0.6) END AS preco1 ,";
					} else {
						if(in_array($login_posto,array(160346,94695,356664))){

							$sql .= " ELSE ((tbl_tabela_item.preco / ((1 - CASE
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
								WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
								ELSE $indice_icms)/100) END) * 1.59 * 0.6) END AS preco1 ,";
						}else{

							$sql .= " ELSE (tbl_tabela_item.preco / ((1- CASE
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20 
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
								WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
								ELSE $icms END)/100)* $desconto_5estrela) END AS preco1 ,";
						}
					}

					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,";

				break;
				case "Vip" :
					$sql .= " ELSE (tbl_tabela_item.preco * $acrescimo_tabela_base / ((1- CASE
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
								WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
								ELSE $indice_icms END)/100) * $desconto_5estrela) END  AS preco1,";
				break;
				case "Loc" :
					$sql .= " ELSE (tbl_tabela_item.preco * $acrescimo_tabela_base / ((1- CASE
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
								WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
								ELSE $indice_icms END)/100)* $desconto_5estrela) END AS preco1, ";

				break;
				default :
					$sql .= "(tbl_tabela_item.preco / ((1- CASE
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
								WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
								ELSE $indice_icms)/100) END* $desconto_5estrela) AS preco ,
							(tbl_tabela_item.preco / ((1- CASE
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
								WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
								WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
								ELSE $icms END)/100)) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro             AS compra, ";
				break;
			}
		} else {

			if(substr($descricao,0,3) == "AUT" AND $pedido_faturado != 't'){
				$sql .= " ELSE (tbl_tabela_item.preco/(1-(9.25 + $indice_icms)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2 * (1 + (tbl_peca.ipi / 100))) END  AS preco1, ";
			}else if(substr($descricao,0,3) == "TMI"){
				$sql .= " ELSE (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + $indice_icms) /100 )/ 0.9/ 0.7/ 0.7 * $desconto_5estrela * $desconto1 * $desconto2) END AS preco1, ";
			}else if($tabela == 54){
				$sql .= " ELSE (tbl_tabela_item_erp.preco) END  AS preco1, ";
			}else{
				$sql .= " ELSE (tbl_tabela_item.preco/(1-(9.25 + CASE
							WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
							WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
							ELSE tbl_peca_icms.indice END)/100)/0.9/0.7/0.7 * $desconto_5estrela* $desconto1 * $desconto2) END AS preco1, ";
			}

			$sql .= " CASE 
					WHEN tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA' THEN
					(tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6)	
					WHEN tbl_peca.origem = 'FAB/SUB' OR tbl_peca.origem = 'TER/SUB' or tbl_peca.origem = 'IMP/SUB' THEN tbl_tabela_item.preco/(1-(9.25 + 7 )/100) ELSE	(tbl_tabela_item.preco/(1-(9.25 + CASE
						WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' = 'RJ' THEN 20
						WHEN '$tipo_contribuinte' = 'f' AND '$estado_posto' <> 'RJ' THEN 18
						WHEN tbl_peca_icms.indice IS NOT NULL AND '$pedido_faturado' = 't' THEN tbl_peca_icms.indice
						ELSE $indice_icms END)/100)/0.9/0.7/0.7 * $desconto_5estrela * $desconto1 * $desconto2 * $acrescimo_tabela_base * $acrescimo_financeiro) END AS compra, ";

			$sql_venda = "CASE
							WHEN tbl_peca.origem = 'FAB/SUB' OR tbl_peca.origem = 'TER/SUB' or tbl_peca.origem = 'IMP/SUB' THEN
								(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100)))
							when tbl_peca.origem in ('FAB/SA','IMP/SA') then
							  (tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * (1 + (tbl_peca.ipi/100)))
							ELSE
								(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100)/0.9/0.7/0.7 * $acrescimo_tabela_base_venda * $acrescimo_financeiro * (1 + (tbl_peca.ipi/100)))
						  END AS venda ";

			if( substr($descricao,0,3) == "DIS" OR substr($descricao,0,3) == "TOP") {
				$sqlPreco = "SELECT desconto_5estrela,
									descontos[1] AS desconto1,
									descontos[2] AS desconto2
							FROM tbl_tipo_posto
							WHERE fabrica = $login_fabrica
							AND descricao = 'AUT'";
				$resPreco = pg_query($con,$sqlPreco);
				$desconto_5estrela_aux = pg_fetch_result($resPreco, 0, 'desconto_5estrela');
				$desconto1_aux = pg_fetch_result($resPreco, 0, 'desconto1');
				$desconto2_aux = pg_fetch_result($resPreco, 0, 'desconto2');

				$desconto_5estrela_aux = ($desconto_5estrela_aux == "") ? 1 : $desconto_5estrela_aux;
				$desconto1_aux = ($desconto1_aux == "") ? 1 : $desconto1_aux;
				$desconto2_aux = ($desconto2_aux == "") ? 1 : $desconto2_aux;

				$sql .= "CASE
							WHEN tbl_peca.origem = 'FAB/SUB' OR tbl_peca.origem = 'TER/SUB' or tbl_peca.origem = 'IMP/SUB' THEN
								(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100) * (1 + (tbl_peca.ipi/100)))
							ELSE
								(tbl_tabela_item.preco/(1-(9.25 + $indice_icms )/100)/0.9/0.7/0.7 * 0.7 * (1+(tbl_peca.ipi / 100)) )
						END AS distrib,";
			}

		}

		 $sql .= $sql_venda;
	}


    $cond_peca_fora_linha = '';

    if ($login_fabrica == 91) {
        $cond_peca_fora_linha = 'AND tbl_peca_fora_linha.peca IS NULL';
    }

	$sql .= " FROM    tbl_tabela
			JOIN    tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela
			".$join_linhas_ativas."
			JOIN    tbl_peca ON tbl_peca.peca = tbl_tabela_item.peca and tbl_peca.ativo
			$join_tabela
			LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca
			$join_para
			LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = tbl_peca.peca
			LEFT JOIN tbl_peca_icms        ON tbl_peca_icms.peca           = tbl_peca.peca
											  AND tbl_peca_icms.codigo         = tbl_peca.classificacao_fiscal
											  AND tbl_peca_icms.estado_destino = '$estado_posto'
			WHERE   tbl_tabela.fabrica = $login_fabrica
			AND     tbl_peca.fabrica   = $login_fabrica AND tbl_tabela.ativa $cond_peca_fora_linha";

	if (strlen($tabela) > 0) {
		$sql .= " AND tbl_tabela.tabela = $tabela ";
	}else{
		if ($login_fabrica == 1) {
			$sql .= "AND tbl_tabela.tabela = 619";
		}
		if ($login_fabrica == 5) {
			$sql .= "AND tbl_tabela.tabela = 23";
		}
		if ($login_fabrica == 2) {
			$sql .= " AND tbl_tabela.tabela = 236 ";
		}

	}

	if($login_fabrica == '6'){
		$sql .= "GROUP BY tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi,
						tbl_peca.unidade, tbl_peca.multiplo, tbl_peca.origem,
						tbl_peca.linha_peca, tbl_depara.para, tbl_peca_fora_linha.peca_fora_linha,
						tbl_tabela_item.preco ";
	}

	$sql .= " ORDER BY tbl_peca.descricao, tbl_peca.referencia ASC";

$res = pg_query ($con,$sql);


	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
		$referencia  = pg_fetch_result ($res,$i,referencia);
		$peca        = pg_fetch_result ($res,$i,peca);
		$descricao_  = pg_fetch_result ($res,$i,descricao);
		$unidade     = pg_fetch_result ($res,$i,unidade);
		$multiplo    = pg_fetch_result ($res,$i,multiplo);
		$origem      = pg_fetch_result ($res,$i,origem);
		$codigo_origem     = trim(pg_fetch_result($res, $i, 'classificacao_fiscal'));
		$linha_peca  = pg_fetch_result ($res,$i,linha_peca);
		$para            = pg_fetch_result ($res,$i,para);
		$peca_fora_linha = pg_fetch_result ($res,$i,peca_fora_linha);
        $ncm             = pg_fetch_result ($res,$i,ncm);
		$parametros_adicionais  = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);

        $unitario = $parametros_adicionais["caixa_unitario"];
        $coletiva = $parametros_adicionais["caixa_coletiva"];

		if($login_fabrica == 11){
			$estoque = pg_fetch_result ($res,$i,estoque);
		}

		if ($multiplo < 2) $multiplo = '1';


		if (strtoupper($origem) == 'IMP') $origem = "IMPORTADO";
		if (strtoupper($origem) == 'NAC') $origem = "NACIONAL";
		if (strtoupper($origem) == 'TER') $origem = "TERCEIRIZADO";
		if (strtoupper($origem) == 'FAB/SUB') $origem = "Fabricação Subsidiado";
		if (strtoupper($origem) == 'TER/SUB') $origem = "Terceirizada Subsidiado";
		if (strtoupper($origem) == 'IMP/SUB') $origem = "Importada Subsidiado";

		if ($linha_peca == 198)       $linha = "Ferramenta DEWALT";
		if ($linha_peca == 199)       $linha = "ELETRO";
		if ($linha_peca == 200)       $linha = "Ferramenta BD";
		if ($linha_peca == 500)       $linha = "Metais e Fechaduras";
		//retirado Wellington HD 1826
		//if (strlen($linha_peca) == 0) $linha = "COMPRESSOR";

		//hd 6521
		//if (strlen($linha_peca) == 0) $linha = "";
		if (strlen($linha_peca)==0 and $login_fabrica==1) {
			$linha = "";

			$sqlx = "SELECT tbl_produto.descricao
					FROM tbl_lista_basica
					JOIN tbl_produto USING(produto)
					WHERE peca = $peca
					AND   tbl_produto.descricao ilike '%COMPRESSOR%'";
			$resx = pg_query($con, $sqlx);

			if (pg_num_rows($resx) > 0) {
				$linha = "Compressor";
			}
		}

		fputs ($fp,"<tr>");
		fputs ($fp,"<td align='left'>" . $referencia . "</td>");
		fputs ($fp,"<td align='left' nowrap>" . $descricao_ . "</td>");
		if ($login_fabrica == 1){
			fputs ($fp,"<td align='center'>" . $origem . "</td>");
			fputs ($fp,"<td align='center'>" . $codigo_origem . "</td>");
			fputs ($fp,"<td align='center'>" . $linha . "</td>");
			if ( strlen($para) > 0 ) {
				fputs ($fp,"<td align='center'>SUBST</td>");
			}elseif ( strlen($peca_fora_linha) > 0 ) {
				fputs ($fp,"<td align='center'>OBSOLETO</td>");
			}else{
				fputs ($fp,"<td align='center'>&nbsp;</td>");
			}
			if ( strlen($para) > 0 ) {
				fputs ($fp,"<td align='center'>" . $para . "</td>");
			}else{
				fputs ($fp,"<td align='center'>&nbsp;</td>");
			}

		}else{
			fputs ($fp,"<td align='center'>" . $unidade . "</td>");
		}

		if ($liberar_preco) {
			$ipi        = pg_fetch_result ($res,$i,ipi);

			if ($login_fabrica == 1) {
				switch ( substr($descricao,0,3) ) {
					case "Dis" :
					case "DIS" :
					case "TOP" :
						$preco            = pg_fetch_result($res, $i, preco1);
						$preco_distrib    = pg_fetch_result($res, $i, distrib);
						$preco_venda      = pg_fetch_result($res, $i, venda);
					break;
					case "Vip" :
						$preco            = pg_fetch_result($res, $i, preco1);
						$preco_venda      = pg_fetch_result($res, $i, venda);
					break;
					case "Loc" :
						$preco            = @pg_fetch_result($res, $i, preco1);
					break;
					default :
						$preco            = pg_fetch_result($res, $i, preco1);
						$preco_compra     = pg_fetch_result($res, $i, compra);
						$preco_venda      = pg_fetch_result($res, $i, venda);
					break;
				}

			}else{
				$preco      = pg_fetch_result ($res,$i,preco);
			}

			if ($login_fabrica == 1) {
					switch ( substr($descricao,0,3) ) {
						case "Dis" :
						case "DIS" :
							fputs ($fp,"<td align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_distrib,2,',','.') . "</td>");
							if($tabela != 54) { fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>"); }
						break;
						case "TOP" :
							fputs ($fp,"<td align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_distrib,2,',','.') . "</td>");
							if($tabela != 54) { fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>"); }
						break;
						case "5SA" :
						case "5SB" :
						case "5SC" :
						case "VIP" :
							fputs ($fp,"<td align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
							if($tabela != 54) { fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>"); }
						break;
						case "AUT" :
						      if($pedido_faturado == 't'){
							      fputs ($fp,"<td align='right' nowrap>" . $ipi . "</td>");
						      }
						      fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
						      if($tabela != 54) { fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>"); }
						break;
						case "Vip" :
							fputs ($fp,"<td align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
							if($tabela != 54) { fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>"); }
						break;
						case "Loc" :
							fputs ($fp,"<td align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
						break;
						default :
							fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_compra,2,',','.') . "</td>");
							if($tabela != 54){ fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>"); }
						break;
					}
					fputs ($fp,"<td align='right' nowrap>" . $multiplo . "</td>");
                    fputs ($fp,"<td align='right' nowrap>" . $ncm . "</td>");
                    fputs ($fp,"<td align='right' nowrap>" . $unitario . "</td>");
					fputs ($fp,"<td align='right' nowrap>" . $coletiva . "</td>");
			}else{

				fputs ($fp,"<td align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
				
				if ($login_fabrica != 175) {
					fputs ($fp,"<td align='center'>" . $ipi . " % </td>");
				}
			}
		}
			if($login_fabrica == 11){
				fputs ($fp,"<td align='center'>" . $estoque . " </td>");
			}
		fputs ($fp,"</tr>");
	}

	fputs ($fp,"</table>");

	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);


	//alterado por Wellington, 20/09/2006 zipando o arquivo pois o arquivo da black esta muito grande

	//gera o xls
//	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /tmp/assist/tabela-$data-$nome_fabrica-$login_posto.xls /tmp/assist/tabela-$data-$nome_fabrica.html`;
	rename("/tmp/assist/tabela-$data-$nome_fabrica.html", "/tmp/assist/tabela-$data-$nome_fabrica-$login_posto.xls");
	if (file_exists("/var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip")) {
		unlink("/var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip");
	}
	//gera o zip
	echo `zip -jquomT $diretorio/download/tabela-$data-$nome_fabrica-$login_posto.zip /tmp/assist/tabela-$data-$nome_fabrica-$login_posto.xls > /dev/null`;


	//move o zip para "/var/www/assist/www/download/"
//	echo `mv  /tmp/assist/tabela-$data-$nome_fabrica-$login_posto.zip /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip`;

	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";

	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
	if ($tabela == 54)
		echo "<a target='_blank' href='download/tabela-$data-$nome_fabrica-$login_posto.zip'>";
	else
		echo "<a target='_blank' href='download/tabela-$data-$nome_fabrica-$login_posto.zip'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em XLS</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.<br>(Arquivo está em formato zip, utilize um descompactador de arquivos para visualizar.)</font></td>";
	echo "</tr>";


	echo "</table>";

	if ($tabela != 54) {
		// para TXT
		$fp = fopen ("/tmp/assist/tabela-$data-$nome_fabrica.txt","w");

		if ($liberar_preco) {
			if ($login_fabrica == 1) {
				fputs ($fp,"REFERÊNCIA");
				fputs ($fp,"\t");
				fputs ($fp,"DESCRIÇÃO");
				fputs ($fp,"\t");
				if ($login_fabrica == 1){
					fputs ($fp,"ORIGEM");
					fputs ($fp,"\t");
					fputs ($fp,"CÓDIGO ORIGEM");
					fputs ($fp,"\t");
					fputs ($fp,"LINHA");
					fputs ($fp,"\t");
					fputs ($fp,"STATUS");
					fputs ($fp,"\t");
					fputs ($fp,"MUDOU PARA");
					fputs ($fp,"\t");
				}else{
					fputs ($fp,"UN");
					fputs ($fp,"\t");
				}

				switch ( substr($descricao,0,3) ) {
					case "Dis" :
					case "DIS" :
					case "TOP" :
						fputs ($fp,"IPI");
						fputs ($fp,"\t");
						fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						fputs ($fp,"DISTRIBUIÇÃO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"PREÇO SUGERIDO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\t");
						fputs ($fp,"NCM");
						fputs ($fp,"\n");
					break;
					case "5SA" :
					case "5SB" :
					case "5SC" :
					case "VIP" :
						fputs ($fp,"IPI");
						fputs ($fp,"\t");
						fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						fputs ($fp,"PREÇO SUGERIDO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\t");
						fputs ($fp,"NCM");
						fputs ($fp,"\n");
					break;
					case "AUT" :
						if($pedido_faturado == 't'){
							fputs ($fp,"IPI");
							fputs ($fp,"\t");
							fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						}else{
							fputs ($fp,"COMPRA com IPI");
							fputs ($fp,"\t");
						}
						fputs ($fp,"PREÇO SUGERIDO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\t");
						fputs ($fp,"NCM");
						fputs ($fp,"\n");
					break;
					case "Vip" :
						fputs ($fp,"IPI");
						fputs ($fp,"\t");
						fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						fputs ($fp,"PREÇO SUGERIDO COM IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\t");
						fputs ($fp,"NCM");
						fputs ($fp,"\n");
					break;
					case "Loc" :
						fputs ($fp,"IPI");
						fputs ($fp,"\t");
						fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\t");
						fputs ($fp,"NCM");
						fputs ($fp,"\n");
					break;
					default :
						fputs ($fp,"COMPRA SEM IPI");
						fputs ($fp,"\t");
						fputs ($fp,"PREÇO SUGERIDO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\t");
						fputs ($fp,"NCM");
						fputs ($fp,"\n");
					break;
				}
                fputs ($fp,"CAIXA UNITÁRIO");
                fputs ($fp,"\n");
				fputs ($fp,"CAIXA COLETIVA");
                fputs ($fp,"\n");
			}
		}

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$referencia = pg_fetch_result ($res,$i,referencia);
			$peca       = pg_fetch_result ($res,$i,peca);
			$descricao_ = pg_fetch_result ($res,$i,descricao);
			$unidade    = pg_fetch_result ($res,$i,unidade);
			$multiplo   = pg_fetch_result ($res,$i,multiplo);
			$origem     = pg_fetch_result ($res,$i,origem);
			$codigo_origem     = pg_fetch_result ($res,$i,classificacao_fiscal);
			$linha_peca = pg_fetch_result ($res,$i,linha_peca);
			$para       = pg_fetch_result ($res,$i,para);
			$peca_fora_linha = pg_fetch_result ($res,$i,peca_fora_linha);
			$ncm = pg_fetch_result ($res,$i,ncm);
			$estoque = pg_fetch_result($res, $i, estoque);
			$parametros_adicionais  = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);

            $unitario = $parametros_adicionais["caixa_unitario"];
            $coletiva = $parametros_adicionais["caixa_coletiva"];

			if ($multiplo < 2) $multiplo = '1';

			if (strtoupper($origem) == 'IMP') $origem = "IMPORTADO";
			if (strtoupper($origem) == 'NAC') $origem = "NACIONAL";
			if (strtoupper($origem) == 'TER') $origem = "TERCEIRIZADO";

			if ($linha_peca == 198)       $linha = "Ferramenta DEWALT";
			if ($linha_peca == 199)       $linha = "ELETRO";
			if ($linha_peca == 200)       $linha = "Ferramenta BD";
			if ($linha_peca == 500)       $linha = "Metais e Fechaduras";
			//retirado Wellington HD 1826
			//if (strlen($linha_peca) == 0) $linha = "COMPRESSOR";

			//hd 6521
			//if (strlen($linha_peca) == 0) $linha = "";
			if (strlen($linha_peca)==0 and $login_fabrica==1) {
				$linha = "";

				$sqlx = "SELECT tbl_produto.descricao
						FROM tbl_lista_basica
						JOIN tbl_produto USING(produto)
						WHERE peca = $peca
						AND   tbl_produto.descricao ilike '%COMPRESSOR%'";
				$resx = pg_query($con, $sqlx);

				if (pg_num_rows($resx) > 0) {
					$linha = "Compressor";
				}
			}


			fputs ($fp,$referencia);
			fputs ($fp,"\t");

			fputs ($fp,$descricao_);
			fputs ($fp,"\t");

			if ($login_fabrica == 1){
				fputs ($fp,$origem);
				fputs ($fp,"\t");
				fputs ($fp,$codigo_origem);
				fputs ($fp,"\t");
				fputs ($fp,$linha);
				fputs ($fp,"\t");
				if ( strlen($para) > 0 ) {
					fputs ($fp,"SUBST");
					fputs ($fp,"\t");
				} elseif ( strlen($peca_fora_linha) > 0 ) {
					fputs ($fp,"OBSOLETO");
					fputs ($fp,"\t");
				}else{
					fputs ($fp,"");
					fputs ($fp,"\t");
				}
				if ( strlen($para) > 0 ) {
					fputs ($fp,$para);
					fputs ($fp,"\t");
				}else{
					#fputs ($fp,"");
					fputs ($fp,"\t");
				}
			}else{
				fputs ($fp,$unidade);
				fputs ($fp,"\t");
			}

			if ($liberar_preco) {

				$ipi        = pg_fetch_result ($res,$i,ipi);

				if ($login_fabrica == 1) {

					switch ( substr($descricao,0,3) ) {
						case "Dis" :
						case 'DIS' :
						case 'TOP' :
							$preco            = pg_fetch_result($res, $i, preco1);
							$preco_distrib    = pg_fetch_result($res, $i, distrib);
							$preco_venda      = pg_fetch_result($res, $i, venda);

							fputs ($fp,$ipi);
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco,2,",","."));
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco_distrib,2,",","."));
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco_venda,2,",","."));
						break;
						case "5SA" :
						case "5SB" :
						case "5SC" :
						case "VIP" :
							$preco            = pg_fetch_result($res, $i, preco1);
							$preco_venda      = pg_fetch_result($res, $i, venda);
							fputs ($fp,$ipi);
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco,2,",","."));
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco_venda,2,",","."));
						break;
						case "AUT" :
							$preco            = pg_fetch_result($res, $i, preco1);
							$preco_venda      = pg_fetch_result($res, $i, venda);
						      if($pedido_faturado == 't'){
							      fputs ($fp,$ipi);
							      fputs ($fp,"\t");
						      }
						      fputs ($fp,number_format($preco,2,",","."));
						      fputs ($fp,"\t");
							fputs ($fp,number_format($preco_venda,2,",","."));
						break;
						case "Vip" :
							$preco            = pg_fetch_result($res, $i, preco1);
							$preco_venda      = pg_fetch_result($res, $i, venda);

							fputs ($fp,$ipi);
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco,2,",","."));
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco_venda,2,",","."));
						break;
						case "Loc" :
							$preco            = @pg_fetch_result($res, $i, preco1);

							fputs ($fp,$ipi);
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco,2,",","."));
						break;
						default :
							$preco            = pg_fetch_result($res, $i, preco1);
							$preco_compra     = pg_fetch_result($res, $i, compra);
							$preco_venda      = pg_fetch_result($res, $i, venda);

							fputs ($fp,number_format($preco_compra,2,",","."));
							fputs ($fp,"\t");
							fputs ($fp,number_format($preco_venda,2,",","."));
						break;
					}
					fputs ($fp,"\t");
					fputs ($fp,$multiplo);
					fputs ($fp,"\t");
					fputs ($fp,$ncm);
				}else{
					$preco      = pg_fetch_result ($res,$i,preco);
					fputs ($fp,number_format($preco,2,",","."));
					fputs ($fp,"\t");
					fputs ($fp,$ipi);
					if($login_fabrica == 11){
						fputs ($fp,"\t");
						fputs ($fp,$estoque);

					}
				}
                fputs ($fp,$unitario);
                fputs ($fp,"\t");
                fputs ($fp,$coletiva);
                fputs ($fp,"\t");
				fputs ($fp,"\n");
			}
		}

		fclose ($fp);

		//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/tabela-$data-$nome_fabrica.txt /tmp/assist/tabela-$data-$nome_fabrica.html`;
		flush();


		echo `cp  /tmp/assist/tabela-$data-$nome_fabrica.txt $diretorio/download/tabela-$data-$nome_fabrica.txt`;

		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a target='_blank' href='download/tabela-$data-$nome_fabrica.txt'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em TXT</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='center'>";
		echo "<a href='tabela_precos.php'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>Nova consulta</font></a>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
?>
<p>

<? include "rodape.php"; ?>
