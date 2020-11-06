<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

if (strlen($_GET["tabela"]) > 0)$tabela = $_GET["tabela"];

if (strlen($tabela) == 0 AND $login_fabrica==1) $tabela = "108";

$liberar_preco = true ;
if ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) 
	$liberar_preco = false;

if ($login_fabrica == 1) {
	$sql ="SELECT   trim(tbl_tipo_posto.descricao)  AS descricao,
					tbl_tipo_posto.acrescimo_tabela_base        ,
					tbl_tipo_posto.acrescimo_tabela_base_venda  ,
					tbl_condicao.acrescimo_financeiro           ,
					case when tbl_tipo_posto.tipo_posto = 36 then ((100 - 18) / 100::float)
					else ((100 - tbl_icms.indice) / 100) end AS icms     ,
					tbl_posto_fabrica.pedido_em_garantia        
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
										and tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_fabrica          on tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
			JOIN    tbl_tipo_posto       on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN    tbl_condicao         on tbl_condicao.fabrica      = $login_fabrica
										and tbl_condicao.condicao     = 50
			JOIN    tbl_icms             on tbl_icms.estado_destino   = tbl_posto_fabrica.contato_estado
			WHERE   tbl_fabrica.estado        = tbl_icms.estado_origem
			AND     tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
	$res = pg_query($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		# HD 219253 ICMS para Locadoras
		$descricao                   = pg_fetch_result($res, 0, descricao);
		$acrescimo_tabela_base       = pg_fetch_result($res, 0, acrescimo_tabela_base);
		$acrescimo_tabela_base_venda = pg_fetch_result($res, 0, acrescimo_tabela_base_venda);
		$acrescimo_financeiro        = pg_fetch_result($res, 0, acrescimo_financeiro);
		$pedido_em_garantia          = pg_fetch_result($res, 0, pedido_em_garantia);
		$icms                        = pg_fetch_result($res, 0, icms);
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
	
	$data = date ("d-m-Y-H-i");
	
	echo `mkdir /tmp/assist`;
	echo `chmod 777 /tmp/assist`;
	echo `rm /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip`;
	echo `rm /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.txt`;
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
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Sem IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>DISTRIBUIÇÃO Com IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>PREÇO SUGERIDO Com IPI</td>");
				break;
				case "Vip" :
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Sem IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>PREÇO SUGERIDO COM IPI</td>");
				break;
				case "Loc" :
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Sem IPI</td>");
				break;
				default :
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>COMPRA Com IPI</td>");
					fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>PREÇO SUGERIDO Com IPI</td>");
				break;
			}
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>QTD. MÚLTIPLA</td>");
		}else{
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>Preço Sem IPI</td>");
			fputs ($fp,"<td bgcolor='#E9F3F3' align='center'>IPI</td>");
		}
	}

	fputs ($fp,"</tr>");
		

	#HD 17663 - somente peças de linha ativa
	if ($login_fabrica ==50) {
		$sql = "SELECT DISTINCT peca 
				INTO TEMP tmp_pecas_ativas_$login_fabrica$login_posto
				FROM tbl_lista_basica 
				JOIN tbl_produto USING(produto)
				JOIN tbl_linha USING(linha)
				WHERE tbl_linha.fabrica = $login_fabrica
				AND tbl_linha.ativo IS TRUE;
				
				CREATE INDEX tmp_pecas_ativas_peca ON tmp_pecas_ativas_$login_fabrica$login_posto(peca);
				";
		$res = pg_query ($con,$sql);
		$join_linhas_ativas = " JOIN tmp_pecas_ativas_$login_fabrica$login_posto USING(peca) ";
	}

	$sql = "SELECT  tbl_peca.referencia      ,
					tbl_peca.peca            ,
					tbl_peca.descricao       ,
					tbl_peca.ipi             ,
					tbl_peca.unidade         ,
					tbl_peca.multiplo        ,
					tbl_peca.origem          ,
					tbl_peca.linha_peca      ,
					tbl_depara.para                    ,
					tbl_peca_fora_linha.peca_fora_linha,
					tbl_tabela_item.preco	";
	
	if ($login_fabrica == 1) {
		//Vírgula só para Fábrica 1 28/11/2008 09:24:48 Manuel
		$sql .= ", ";
		//FOI ALTERADO O CALCULO PARA FICAR IGUAL A TELA, ESTAVA DIVERGENTE A TELA COM O XLS - HD 52734 19/11/2008
		/*IGOR HD: 21333 - NOVA REGRA PARA TAB. BASE2 (108)*/
		if ($tabela == 108 AND (
			($descricao == "DistribSS5ESTRELAS") OR
			($descricao == "DistribMG5ESTRELAS") OR
			($descricao == "VipNNECO5ESTRELAS") OR
			($descricao == "VipSS5ESTRELAS") OR
			($descricao == "VipMG5ESTRELAS") OR
			($descricao == "DistribNNECO5ESTRELAS")
		)) {
			if($descricao == "DistribSS5ESTRELAS"){
				$sql .= "(tbl_tabela_item.preco /0.88 * 0.97 )                     AS preco1  ,";
				$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
						(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

			}elseif($descricao == "DistribMG5ESTRELAS"){
				$sql .= "(tbl_tabela_item.preco /0.82 * 0.97 )                     AS preco1  ,";
				$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
						(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
			}elseif($descricao == "VipNNECO5ESTRELAS"){
				$sql .= "(tbl_tabela_item.preco /0.93*1.1*0.97 )                     AS preco1  ,";
				$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

			}elseif($descricao == "VipSS5ESTRELAS"){
				$sql .= "(tbl_tabela_item.preco /0.88 * 1.1 * 0.97 )                     AS preco1  ,";
				$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

			}elseif($descricao == "VipMG5ESTRELAS"){
				$sql .= "(tbl_tabela_item.preco /0.82 * 1.1 * 0.97)                     AS preco1  ,";
				$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";

			}elseif($descricao == "DistribNNECO5ESTRELAS"){
				$sql .= "(tbl_tabela_item.preco /0.93 * 0.97 )                     AS preco1  ,";
				$sql .= "(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
						(tbl_tabela_item.preco /$icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
			}
		}else{
			switch ( substr($descricao,0,3) ) {
				case "Dis" :
					//hd 17399 - Pa Top Service tem Preço de compra diferenciado conforme chamado
					if ($login_posto == 5355) {
						$sql .= "((tbl_tabela_item.preco / $icms) * 1.51 * 0.6)                                                                           AS preco1  ,";
					} else {
						$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco1  ,";
					}

					$sql .= "(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro       AS distrib,
							 (tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
				case "Vip" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms)                                                                 AS preco1 ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;
				case "Loc" :
					$sql .= "(tbl_tabela_item.preco * $acrescimo_tabela_base / $icms) AS preco1 ";
				break;
				default :
					$sql .= "(tbl_tabela_item.preco / $icms)                                                                                          AS preco1 ,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base * $acrescimo_financeiro             AS compra,
							(tbl_tabela_item.preco / $icms) * (1 + (tbl_peca.ipi / 100)) * $acrescimo_tabela_base_venda * $acrescimo_financeiro / 0.7 AS venda ";
				break;

			}
		}
	}
	
	$sql .= "FROM    tbl_tabela
			JOIN    tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela
			".$join_linhas_ativas."
			JOIN    tbl_peca ON tbl_peca.peca = tbl_tabela_item.peca and tbl_peca.ativo
			LEFT JOIN tbl_depara           ON tbl_depara.peca_de       = tbl_peca.peca
			LEFT JOIN tbl_peca_fora_linha  ON tbl_peca_fora_linha.peca = tbl_peca.peca
			WHERE   tbl_tabela.fabrica = $login_fabrica 
			AND     tbl_peca.fabrica   = $login_fabrica AND tbl_tabela.ativa";
		
	if (strlen($tabela) > 0) {
		$sql .= " AND tbl_tabela.tabela = $tabela ";
	}else{
		if ($login_fabrica == 1) {
			$sql .= "AND tbl_tabela.tabela = 108";
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
		$linha_peca  = pg_fetch_result ($res,$i,linha_peca);
		$para            = pg_fetch_result ($res,$i,para);
		$peca_fora_linha = pg_fetch_result ($res,$i,peca_fora_linha);
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
		
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='$cor' align='left'>" . $referencia . "</td>");
		fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . $descricao_ . "</td>");
		if ($login_fabrica == 1){
			fputs ($fp,"<td bgcolor='$cor' align='center'>" . $origem . "</td>");
			fputs ($fp,"<td bgcolor='$cor' align='center'>" . $linha . "</td>");
			if ( strlen($para) > 0 ) {
				fputs ($fp,"<td bgcolor='$cor' align='center'>SUBST</td>");
			}elseif ( strlen($peca_fora_linha) > 0 ) {
				fputs ($fp,"<td bgcolor='$cor' align='center'>OBSOLETO</td>");
			}else{
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;</td>");
			}
			if ( strlen($para) > 0 ) {
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . $para . "</td>");
			}else{
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;</td>");
			}

		}else{
			fputs ($fp,"<td bgcolor='$cor' align='center'>" . $unidade . "</td>");
		}

		if ($liberar_preco) {
			$ipi        = pg_fetch_result ($res,$i,ipi);
			
			if ($login_fabrica == 1) {
				switch ( substr($descricao,0,3) ) {
					case "Dis" :
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
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco_distrib,2,',','.') . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>");
						break;
						case "Vip" :
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>");
						break;
						case "Loc" :
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>" . $ipi . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
						break;
						default :
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco_compra,2,',','.') . "</td>");
							fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco_venda,2,',','.') . "</td>");
						break;
					}
					fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>" . $multiplo . "</td>");
			}else{
				fputs ($fp,"<td bgcolor='$cor' align='right' nowrap>R$ " . number_format($preco,2,',','.') . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . $ipi . " % </td>");
			}
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
	echo `zip -jquomT /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip /tmp/assist/tabela-$data-$nome_fabrica-$login_posto.xls > /dev/null`;
	
	//move o zip para "/var/www/assist/www/download/"
//	echo `mv  /tmp/assist/tabela-$data-$nome_fabrica-$login_posto.zip /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.zip`;

	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font>";
	if ($tabela == 54)
		echo "<a href='download/tabela-$data-$nome_fabrica-$login_posto.zip'>";
	else
		echo "<a href='download/tabela-$data-$nome_fabrica-$login_posto.zip'>";
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
						fputs ($fp,"IPI");
						fputs ($fp,"\t");
						fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						fputs ($fp,"DISTRIBUIÇÃO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"PREÇO SUGERIDO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
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
						fputs ($fp,"\n");
					break;
					case "Loc" :
						fputs ($fp,"IPI");
						fputs ($fp,"\t");
						fputs ($fp,"COMPRA Sem IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\n");
					break;
					default :
						fputs ($fp,"COMPRA Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"PREÇO SUGERIDO Com IPI");
						fputs ($fp,"\t");
						fputs ($fp,"QUANTIDADE MÚLTIPLA");
						fputs ($fp,"\n");
					break;
				}
			}
		}
		
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$referencia = pg_fetch_result ($res,$i,referencia);
			$peca       = pg_fetch_result ($res,$i,peca);
			$descricao_ = pg_fetch_result ($res,$i,descricao);
			$unidade    = pg_fetch_result ($res,$i,unidade);
			$multiplo   = pg_fetch_result ($res,$i,multiplo);
			$origem     = pg_fetch_result ($res,$i,origem);
			$linha_peca = pg_fetch_result ($res,$i,linha_peca);
			$para       = pg_fetch_result ($res,$i,para);
			$peca_fora_linha = pg_fetch_result ($res,$i,peca_fora_linha);
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
				}else{
					$preco      = pg_fetch_result ($res,$i,preco);
					fputs ($fp,number_format($preco,2,",","."));
					fputs ($fp,"\t");
					fputs ($fp,$ipi);
				}
				
				fputs ($fp,"\n");
			}
		}
		
		fclose ($fp);
	
		//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /var/www/assist/www/download/tabela-$data-$nome_fabrica.txt /tmp/assist/tabela-$data-$nome_fabrica.html`;
		flush();
		

		echo `mv  /tmp/assist/tabela-$data-$nome_fabrica.txt /var/www/assist/www/download/tabela-$data-$nome_fabrica.txt`;
		
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='download/tabela-$data-$nome_fabrica.txt'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em TXT</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
	}
?>

<p>

<? include "rodape.php"; ?>
