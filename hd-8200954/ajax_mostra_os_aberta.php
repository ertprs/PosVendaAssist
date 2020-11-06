<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<?

$status = $_GET['status'];

switch ($status) {
	
	case 'vermelho':

	$sql = "SELECT	DISTINCT os,
								sua_os,
								data_digitacao,
								to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
								tbl_os.os_reincidente,
								tbl_os.serie,
								excluida,
								motivo_atraso,
								tipo_os_cortesia,
								tbl_os.consumidor_revenda,
								tbl_os.consumidor_nome,
								tbl_os.revenda_nome,
								impressa,
								tbl_os.nota_fiscal,
								tbl_os.nota_fiscal_saida,
								tbl_produto.referencia,
								tbl_produto.descricao,
								tbl_produto.voltagem,
								tipo_atendimento,
								tecnico_nome,
								tbl_os.admin,
								sua_os_offline,
								status_os,
								rg_produto,
								tbl_produto.linha,
								data_conserto,
								tbl_marca.marca,
								tbl_marca.nome as marca_nome,
								consumidor_email
					FROM tbl_os
			JOIN tbl_os_extra USING(os)
			JOIN tbl_produto USING(produto)
			LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			WHERE tbl_os.defeito_constatado is null
			AND	  tbl_os.solucao_os is null
			AND tbl_os.posto = $login_posto
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.finalizada is NULL
			AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')";
	break;
	case 'amarelo':

		$sql = "SELECT DISTINCT os,
								sua_os,
								data_digitacao,
								to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
								tbl_os.os_reincidente,
								tbl_os.serie,
								excluida,
								motivo_atraso,
								tipo_os_cortesia,
								tbl_os.consumidor_revenda,
								tbl_os.consumidor_nome,
								tbl_os.revenda_nome,
								impressa,
								tbl_os.nota_fiscal,
								tbl_os.nota_fiscal_saida,
								tbl_produto.referencia,
								tbl_produto.descricao,
								tbl_produto.voltagem,
								tipo_atendimento,
								tecnico_nome,
								tbl_os.admin,
								sua_os_offline,
								status_os,
								rg_produto,
								tbl_produto.linha,
								data_conserto,
								tbl_marca.marca,
								tbl_marca.nome as marca_nome,
								consumidor_email
			FROM tbl_os
			JOIN tbl_os_extra USING(os)
			JOIN tbl_os_produto using (os)
			JOIN tbl_os_item USING (os_produto)
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
			LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE
			tbl_os.posto = $login_posto
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.finalizada is NULL
			AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
			AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)";
	break;

	case 'rosa':

		$sqlTemp = "SELECT DISTINCT os
			INTO TEMP tmp_os_$login_posto
			FROM tbl_os
			JOIN tbl_os_extra USING(os)
			JOIN tbl_os_produto using (os)
			JOIN tbl_os_item USING (os_produto)
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
			LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE
			tbl_os.posto = $login_posto
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.finalizada is NULL
			AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f')
			AND tbl_os_item.peca not in (select peca from tbl_faturamento_item where tbl_faturamento_item.pedido = tbl_os_item.pedido)";
		$resTemp = pg_exec($con,$sqlTemp);

		$sql = "SELECT DISTINCT os,
								sua_os,
								data_digitacao,
								to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
								tbl_os.os_reincidente,
								tbl_os.serie,
								excluida,
								motivo_atraso,
								tipo_os_cortesia,
								tbl_os.consumidor_revenda,
								tbl_os.consumidor_nome,
								tbl_os.revenda_nome,
								impressa,
								tbl_os.nota_fiscal,
								tbl_os.nota_fiscal_saida,
								tbl_produto.referencia,
								tbl_produto.descricao,
								tbl_produto.voltagem,
								tipo_atendimento,
								tecnico_nome,
								tbl_os.admin,
								sua_os_offline,
								status_os,
								rg_produto,
								tbl_produto.linha,
								data_conserto,
								tbl_marca.marca,
								tbl_marca.nome as marca_nome,
								consumidor_email
							FROM tbl_os
							JOIN tbl_os_extra USING(os)
							JOIN tbl_produto USING(produto)
							LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
							WHERE tbl_os.defeito_constatado is not null
							AND   tbl_os.solucao_os is not null
							AND   posto = $login_posto
							AND   tbl_os.fabrica = $login_fabrica
							AND   finalizada is NULL
							AND   (excluida IS NULL OR excluida = 'f')
							AND os not in (SELECT os from  tmp_os_$login_posto)" ;
	break;
	case 'azul':
		$sql = "SELECT		os,
							sua_os,
							data_digitacao,
							to_char(data_abertura,'DD/MM/YYYY') as data_abertura,
							tbl_os.os_reincidente,
							tbl_os.serie,
							excluida,
							motivo_atraso,
							tipo_os_cortesia,
							tbl_os.consumidor_revenda,
							tbl_os.consumidor_nome,
							tbl_os.revenda_nome,
							impressa,
							tbl_os.nota_fiscal,
							tbl_os.nota_fiscal_saida,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_produto.voltagem,
							tipo_atendimento,
							tecnico_nome,
							tbl_os.admin,
							sua_os_offline,
							status_os,
							rg_produto,
							tbl_produto.linha,
							data_conserto,
							tbl_marca.marca,
							tbl_marca.nome as marca_nome,
							consumidor_email
							FROM tbl_os
							JOIN tbl_os_extra USING(os)
							JOIN tbl_produto USING(produto)
							LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
							WHERE posto = $login_posto
							AND tbl_os.	fabrica = $login_fabrica
							AND finalizada is NULL
							AND data_conserto is not null
							AND (excluida IS NULL OR excluida = 'f')";
	break;
}
//	echo nl2br($sql);
	$res = pg_exec($con,$sql);
	/*
	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	#require "_class_paginacao_teste.php";
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 100;				// máximo de links à serem exibidos
	$max_res   = 12;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag= new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->Executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	##### PAGINAÇÃO - FIM #####
	*/

	if (pg_num_rows($res)>0) {
			for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

			if ($i % 50 == 0) {
				$html .= "</table>";
				flush();
				$html .= "<table border=\"1\" cellpadding=\"2\" cellspacing=\"0\" style=\"border-collapse: collapse\" bordercolor=\"#000000\" width=\"900\">";
			}

			if ($i % 15 == 0) {
				$html .= "<tr class=\"Titulo\" height=\"25\" background=\"admin/imagens_admin/azul.gif\">";
				$html .= "<td class=\"table_line\"><b>OS</td>";
				$html .= "<td  nowrap><b>SÉRIE</td>";
				$html .= "<td nowrap><b>NF</td>";
				$html .= "</td>";
				$html .= "<td><b>AB</td>";
				//HD 14927
				$html .= "<td><b><acronym title=\"".traduz("data.de.conserto.do.produto",$con,$cook_idioma)."\" style=\"cursor:help;\"><b>DC</a></td>";
				$html .= "<td><acronym title=\"".traduz("data.de.fechamento.registrada.pelo.sistema",$con,$cook_idioma)."\" style=\"cursor:help;\"><b>".traduz("fc",$con,$cook_idioma)."</a></td>";
				$html .= "<td><b>".strtoupper(traduz("consumidor",$con,$cook_idioma))."</td>";
				$html .= "<td><b>".strtoupper(traduz("marca",$con,$cook_idioma))."</td>";
				$html .= "<td><b>";
				$html .= strtoupper(traduz("produto",$con,$cook_idioma));
				$html .= "</td>";
				$html .= "<td><img border=\"0\" src=\"imagens/img_impressora.gif\" alt=\"Imprimir OS\"></td>";	
				$colspan = "6";
				$html .= "<td colspan=\"$colspan\"><b>";
				$html .= strtoupper(traduz("acoes",$con,$cook_idioma));
				$html .= "</td>";
			}

				$os                 = trim(pg_fetch_result($res,$i,os));
				$sua_os             = trim(pg_fetch_result($res,$i,sua_os));
				$digitacao          = trim(pg_fetch_result($res,$i,data_digitacao));
				$abertura           = trim(pg_fetch_result($res,$i,data_abertura));
				$serie              = trim(pg_fetch_result($res,$i,serie));
				$excluida           = trim(pg_fetch_result($res,$i,excluida));
				$motivo_atraso      = trim(pg_fetch_result($res,$i,motivo_atraso));
				$tipo_os_cortesia   = trim(pg_fetch_result($res,$i,tipo_os_cortesia));
				$consumidor_revenda = trim(pg_fetch_result($res,$i,consumidor_revenda));
				$consumidor_nome    = trim(pg_fetch_result($res,$i,consumidor_nome));
				$revenda_nome       = trim(pg_fetch_result($res,$i,revenda_nome));
				$impressa           = trim(pg_fetch_result($res,$i,impressa));
				$nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));//hd 12737 31/1/2008
				$nota_fiscal_saida  = trim(pg_fetch_result($res,$i,nota_fiscal_saida));	//
				$reincidencia       = trim(pg_fetch_result($res,$i,os_reincidente));
				$produto_referencia = trim(pg_fetch_result($res,$i,referencia));
				$produto_descricao  = trim(pg_fetch_result($res,$i,descricao));
				$produto_voltagem   = trim(pg_fetch_result($res,$i,voltagem));
				$tecnico_nome       = trim(pg_fetch_result($res,$i,tecnico_nome));
				$admin              = trim(pg_fetch_result($res,$i,admin));
				$sua_os_offline     = trim(pg_fetch_result($res,$i,sua_os_offline));
				$status_os          = trim(pg_fetch_result($res,$i,status_os));
				$rg_produto         = trim(pg_fetch_result($res,$i,rg_produto));
				$linha              = trim(pg_fetch_result($res,$i,linha));
				$marca     = trim(pg_fetch_result($res,$i,marca));
				$marca_nome= trim(pg_fetch_result($res,$i,marca_nome));
				$data_conserto=trim(pg_fetch_result($res,$i,data_conserto));
				$consumidor_email   = trim(pg_fetch_result($res,$i,consumidor_email));
			
			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;
			
			$html .= "<tr class=\"Conteudo\" height=\"15\" bgcolor=\"$cor\" align=\"left\">";
			$html .= "<td  width=\"60\" nowrap>" ;
			$html .= $sua_os;
			$html .= "</td>";
			$html .= "<td width=\"55\" nowrap>" . $serie . "</td>";
			$html .= "<td nowrap>" ;
			$html .= $nota_fiscal;
			$html .= "</td>";

			$html .= "<td nowrap ><acronym title=\"".traduz("data.abertura",$con,$cook_idioma).": $abertura\" style=\"cursor: help;\">" . substr($abertura,0,5) . "</acronym></td>";

			$html .= "<td nowrap ><acronym title=\"".traduz("data.do.conserto",$con,$cook_idioma).": $data_conserto\" style=\"cursor: help;\">" . substr($data_conserto,0,5) . "</acronym></td>";
			$aux_fechamento = $fechamento;
			$html .= "<td nowrap><acronym title=\"".traduz("data.fechamento",$con,$cook_idioma).": ";

			$html .= "<td>$aux_fechamento\" style=\"cursor: help;\">" . substr($aux_fechamento,0,5) . "</acronym></td>";

			$html .= "<td width=\"120\" nowrap><acronym title=\"".traduz("consumidor",$con,$cook_idioma).": $consumidor_nome\" style=\"cursor: help;\">" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$html .= "<td nowrap>$marca_nome</td>";
			$produto = $produto_referencia . " - " . $produto_descricao;

			$html .= "<td width=\"150\" nowrap>". substr($produto,0,20) . "</td>";
			
			##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
			$html .= "<td width=\"30\" align=\"center\">";
			if (strlen($admin) > 0 and $login_fabrica == 19) $html .= "<img border=\"0\" src=\"imagens/img_sac_lorenzetti.gif\" alt=\"OS lançada pelo SAC Lorenzetti\">";
			else if (strlen($impressa) > 0)                  $html .= "<img border=\"0\" src=\"imagens/img_ok.gif\" alt=\"OS já foi impressa\">";
			else                                             $html .= "<img border=\"0\" src=\"imagens/img_impressora.gif\" alt=\"Imprimir OS\">";
			$html .= "</td>";

			$html .= "<td width=\"60\" align=\"center\">";
				 $html .= "<a href=\"os_press.php?os=$os\" target=\"_blank\"><img border=\"0\" src=\"imagens/btn_consulta.gif\"></a>";
			$html .= "</td>";

			$html .= "<td width=\"60\" align=\"center\">";

			if ($excluida == "f" || strlen($excluida) == 0 and strlen($btn_acao_pre_os)==0) {
				$html .= "<img border=\"0\" src=\"imagens/btn_imprime.gif\"></a>";
			}
			$html .= "</td>";


			$sql_critico = "select produto_critico from tbl_produto where referencia = '$produto_referencia'";
			$res_critico = pg_query($con,$sql_critico);

			if (pg_num_rows($res_critico)>0) {
				$produto_critico = pg_fetch_result($res_critico,0,produto_critico);
			}

			$html .= "<td width=\"60\" align=\"center\" nowrap>";
			if ($troca_garantia == "t" OR (($status_os=="62" and $produto_critico <> 't') || $status_os=="65" || $status_os=="72" || $status_os=="87" || $status_os=="116" || $status_os=="120" || $status_os=="122" || $status_os=="126")) {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\"><img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanca.gif\"></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						if($login_posto=="6359"){
							$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\">";
						}else{
							$html .= "<a href=\"os_print_blackedecker_compressor.php?os=$os\" target=\"_blank\">";
						//takashi alterou 03/11
						}
					}else{
						$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\">";
					}//
					if($login_fabrica == 1 AND $tipo_atendimento <> 17 AND $tipo_atendimento <> 18)
						$html .= "<img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanca.gif\"></a>";
					else
						$html .= "<p id=\"lancar_$i\" border=\"0\"></p></a>";
				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
				$html .= "<a href=\"os_filizola_valores.php?os=$os\" target=\"_blank\"><img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanca.gif\"></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" OR strlen($excluida) == 0) {
					if ($login_fabrica == 1) {
						if($tipo_os_cortesia == "Compressor"){
							if($login_posto=="6359"){
								$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\">";
							}else{
								$html .= "<a href=\"os_print_blackedecker_compressor.php?os=$os\" target=\"_blank\">";
							//takashi alterou 03/11
							}
						}
						if(strlen($tipo_atendimento) == 0){
							$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\">";
						}
					}else{
						//
						if($login_fabrica==19){
							if($consumidor_revenda<>'R'){
								$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\">";
								if($sistema_lingua == "ES"){
									$html .= "<img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanzar.gif\"></a>";
								}else{
									$html .= "<img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanca.gif\"></a>";
								}
							}
						}else{
							$html .= "<a href=\"os_item.php?os=$os\" target=\"_blank\">";
							if($sistema_lingua == "ES"){
								$html .= "<img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanzar.gif\">";
							}else{
								// $data_conserto > "03/11/2008" HD 50435
								$xdata_conserto = fnc_formata_data_pg($data_conserto);

								$sqlDC = "SELECT $xdata_conserto::date > \"2008-11-03\"::date AS data_anterior";
								#$html .= $sqlDC;
								$resDC = pg_query($con, $sqlDC);
								if(pg_num_rows($resDC)>0) $data_anterior = pg_fetch_result($resDC, 0, 0);

								if($login_fabrica==11 AND strlen($data_conserto)>0 AND $data_anterior == 't'){
									$html .= "";
								}else{
									$html .= "<img id=\"lancar_$i\" border=\"0\" src=\"imagens/btn_lanca.gif\">";
								}
							}
							$html .= "</a>";
						}
						//
					}
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0 AND strlen($rg_produto)==0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
						if($login_fabrica == 20){
							/*if($status_os<>\"13\" AND ($tipo_atendimento<>13 and $tipo_atendimento <> 66))
								$html .= "<a href=\"os_cadastro.php?os=$os&reabrir=ok\"><img border=\"0\" src=\"imagens/btn_reabriros.gif\"></a>";*/
							// HD 61323
						}
						else if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)) $html .= "&nbsp;";
							else{
								//HD 15368 - Raphael, se a os for troca não pode irá reabrir
								$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
								$resX = @pg_query($con,$sqlX);
								if(@pg_num_rows($resX)==0) {
									if($login_fabrica <>11){ // HD 45935
										$html .= "<a href=\"os_item.php?os=$os&reabrir=ok\"><img border=\"0\" src=\"imagens/btn_reabriros.gif\"></a>";
									}else{
										$html .= "&nbsp;";
									}
								}
							}
					}
				}
			}else{
				$html .= "&nbsp;";
			}
			$html .= "</td>";


			$html .= "<td width=\"60\" align=\"center\">";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0) {
				if (($status_os!="62" && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" ) ||($reincidencia=='t')){
					if ($excluida == "f" || strlen($excluida) == 0) {
						if (strlen ($admin) == 0) {
							if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND $valores_adicionais > 0)
								$html .= "<a href=\"javascript: if (confirm(\"".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?\") == true) { window.location=\"$PHP_SELF?excluir=$os\"; }\"><p id=\"excluir_$i\" border=\"0\"></p></a>";
							else
								$html .= "<a href=\"javascript: if (confirm(\"".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?\") == true) { if(disp_prompt($os, \"$sua_os\") == true){window.location=\"$PHP_SELF?excluir=$os\";} }\"><img id=\"excluir_$i\" border=\"0\" src=\"imagens/btn_excluir.gif\"></a>";

						}else{
							if($login_fabrica == 20) { # 148322
								$html .= "<a href=\"javascript: if (confirm(\"".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?\") == true) { if(disp_prompt($os, \"$sua_os\") == true){window.location=\"$PHP_SELF?excluir=$os\";} }\"><img id=\"excluir_$i\" border=\"0\" src=\"imagens/btn_excluir.gif\"></a>";
							}else{
								$html .= "<img id=\"excluir_$i\" border=\"0\" src=\"imagens/pixel.gif\">";
							}
						}
					}
				}
			}else{
				$html .= "&nbsp;";
			}
			$html .= "</td>";
			$html .=  "<td width=\"60\" align=\"center\">";
			if (strlen($fechamento) == 0 AND $status_os!="62"  && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os != "98") {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)){
						if($nota_fiscal_saida > 0 OR ($valores_adicionais == 0 AND $nota_fiscal_saida == 0))
							$html .=  "<a href=\"javascript: if (confirm(\"".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?\") == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
					}else{
						if($login_fabrica==19){
							if($consumidor_revenda<>'R'){
								$html .=  "<a href=\"javascript: if (confirm(\"".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?\") == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
							}
						}else{
							if($login_fabrica<>15){
								if($login_fabrica==11 and strlen($consumidor_email)>0 and $login_posto==14301){
									$html .=  "<a href=\"javascript: if(confirm(\"".traduz("esta.os.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."\") == true) {window.location=\"os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar\";}\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
								}else{
									if ($login_fabrica == 20 and $login_posto == 6359) {
										$html .=  "<a href=\"#\" onclick=\"fechaOSnovo($i);data_fechamento_$i.focus();\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
									}
									else {
										//$html .=  $consumidor_revenda;
										if($consumidor_revenda=='R' and $login_fabrica == 11){
											#HD 111421 ----->
											$sua_os_x = $sua_os;
											$ache = "-";
											$posicao = strpos($sua_os_x,$ache);
											$sua_os_x = substr($sua_os_x,0,$posicao);
											#--------------->
											$html .=  "<a href=\"javascript: if(confirm(\"".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."\") == true) {window.location=\"os_fechamento.php?sua_os=$sua_os_x&btn_acao_pesquisa=continuar\";}\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
										}else{
												$html .=  "<a href=\"javascript: if (confirm(\"".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?\") == true) { fechaOS ($os,sinal_$i,excluir_$i, document.getElementById(\"lancar\")) ; }\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
										}
									}
								}
							}else{
								if($consumidor_revenda<>'R'){

									$html .=  "<a href=\"javascript: if (confirm(\"".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?\") == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
								}else{
									$html .=  "<a href=\"javascript: if(confirm(\"".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."\") == true) {window.location=\"os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar\";}\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
								}
							}
						}
					}
				}
			}else{
				if ($login_fabrica == 51 AND $status_os =='62') {
					$html .=  "<a href=\"javascript: if (confirm(\"".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?\") == true) { fechaOS ($os,sinal_$i,\"\", \"\") ; }\"><img id=\"sinal_$i\" border=\"0\" src=\"/assist/imagens/btn_fecha.gif\"></a>";
				}else{
					$html .=  "&nbsp;";
				}
			}
		
				$html .=  "</td>";
		
			
			if ($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 3){ //HD 13239
				$html .=  "<td width=\"60\" align=\"center\">";
				//HD:44202
				if($login_fabrica == 3 AND ($status_os=="120" || $status_os=="122" || $status_os=="126" )){
					$html .=  "&nbsp;";
				}else{
					$os_troca = false;

					if ( (strlen($data_conserto) ==0) ) {

						$botao_consertado =  "<a href=\"javascript: if (confirm(\"".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!\") == true) { consertadoOS ($os,consertado_$i) ; }\"><img id=\"consertado_$i\" border=\"0\" src=\"/assist/imagens/btn_consertado.gif\"></a>";

						if ($login_fabrica == 11){
							$sqlX ="SELECT os_troca,ressarcimento 
									FROM tbl_os_troca 
									WHERE os = $os";
							$resX = pg_query($con,$sqlX);
							if(pg_num_rows($resX)==1){
								$os_troca = true;
							}
							if ($os_troca == false){
								$html .=  $botao_consertado;
							}
						}else{
							$html .=  $botao_consertado;
						}
					}
				}

				$html .=  "</td>";
			}
			}
		$html .=  "</tr>";
		$html .=  "<td colspan=\"16\" align=\"center\"><input type=\"button\" name=\"fecharlayer\" value=\"fechar\" onclick=\"javascript:fecharlayer()\"></td>";
		$html .=  "</tr>";
		$html .=  "</table>";
		$html .=  "</form>";

	/*
	##### PAGINAÇÃO - INÍCIO #####

	$html .=  "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	if (strlen($btn_acao_pre_os) ==0) {
		$todos_links = $mult_pag->Construir_Links("strings", "sim");
	}
	// função que limita a quantidade de links no rodape
	if (strlen($btn_acao_pre_os) ==0) {
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
	}
	for ($n = 0; $n < count($links_limitados); $n++) {
		$html .=  "<font color=\"#DDDDDD\">".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	$html .=  "</div>";
	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	if (strlen($btn_acao_pre_os) ==0) {
		$registros         = $mult_pag->Retorna_Resultado();
	}

	$valor_pagina   = $pagina + 1;
	if (strlen($btn_acao_pre_os) ==0) {
		$numero_paginas = intval(($registros / $max_res) + 1);
	}
	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;
	if ($registros > 0){
		$html .=  "<div>";
		$html .=  "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		$html .=  "<font color=\"#cccccc\" size=\"1\">";
		$html .=  " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		$html .=  "</font>";
		$html .=  "</div>";
	}
	*/
	echo $html;


	
	}

?>