<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

if (strlen($_GET["extrato"]) > 0)  $extrato = trim($_GET["extrato"]);

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços";

?>

<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
	background-color: #ffffff
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid;
	background-color: #ffffff;
}

.quebrapagina {
   page-break-before: always;
}
</style>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

<?
$sql = "SELECT	lpad (tbl_os.sua_os::text,'10','0')                  AS ordem          ,
				tbl_os.os                                                      ,
				tbl_os.sua_os                                                  ,
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data           ,
				to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura       ,
				tbl_os.serie                                                   ,
				tbl_os.consumidor_nome                                         ,
				tbl_os.revenda_nome                                            ,
				tbl_os.consumidor_revenda                                      ,
				to_char (tbl_os.mao_de_obra,'999,990.00') AS mao_de_obra       ,
				to_char (tbl_os.pecas,'999,990.00') AS pecas                   ,
				tbl_os.data_fechamento                                         ,
				tbl_produto.referencia                                         ,
				tbl_produto_idioma.descricao                                   ,
				tbl_os_extra.extrato                                           ,
				to_char (tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
				to_char (tbl_extrato_extra.baixado,'DD/MM/YYYY') AS baixado    ,
				tbl_extrato_extra.obs                                          ,
				to_char (tbl_extrato.total,'999,990.00') AS total              ,
				tbl_extrato.protocolo                                          ,
				to_char (tbl_extrato.mao_de_obra,'999,990.00') AS t_mao_de_obra,
				to_char (tbl_extrato.pecas,'999,990.00') AS t_pecas            ,
				tbl_posto.nome AS nome_posto                                   ,
				tbl_posto_fabrica.codigo_posto AS codigo_posto                 ,
				tbl_extrato_pagamento.extrato_pagamento                                         ,
				tbl_extrato_pagamento.valor_total                                               ,
				tbl_extrato_pagamento.acrescimo                                                 ,
				tbl_extrato_pagamento.desconto                                                  ,
				tbl_extrato_pagamento.valor_liquido                                             ,
				tbl_extrato_pagamento.nf_autorizacao                                            ,
				TO_CHAR(tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
				tbl_extrato_pagamento.autorizacao_pagto

		FROM		tbl_os
		JOIN		tbl_produto       USING (produto)
		LEFT JOIN	tbl_produto_idioma on tbl_produto.produto =  tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES'
		JOIN		tbl_posto         USING (posto)
		JOIN		tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN		tbl_os_extra USING (os)
		JOIN		tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.os = tbl_os.os
		JOIN		tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
		LEFT JOIN	tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
		WHERE		tbl_os.fabrica       = $login_fabrica 
		AND			tbl_os_extra.extrato = $extrato
		ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-'))::text,20,'0')               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-'))::text,20,'0'),'-','') ASC";
$res = pg_exec ($con,$sql);
//if($ip=="201.68.13.36"){ echo $sql; exit; }
if (@pg_numrows($res) == 0) {
	echo "<TABLE width='665' height='50'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
}else{
/*	echo "<TABLE width='666' border='1' cellspacing='0' cellpadding='0'>";
	echo "<TR>";
	echo "<TD>";
*/
	if ($login_fabrica==11){
		$os_array = array();
		$sql =  "SELECT 
					intervencao.os
					FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os = 65 ) intervencao
					JOIN tbl_os ON tbl_os.os = intervencao.os AND tbl_os.fabrica=$login_fabrica
					JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.extrato=$extrato
					WHERE tbl_os.fabrica =$login_fabrica";
		$res_status = pg_exec($con,$sql);
		$os_array = array();
		$total=pg_numrows($res_status);
		for ($t = 0 ; $t < $total ; $t++) {
			array_push($os_array,pg_result($res_status,$t,os));
		}
	}

	echo "<TABLE width='665' border='0' cellspacing='0' cellpadding='1'>";
	echo "<TR>";
	echo "<TD class='menu_top' align='center' rowspan='2'><IMG SRC='/assist/logos/$login_fabrica_logo' border='0'></TD>";
	echo "<TD class='menu_top' align='left' colspan='4'> Servicio: <B>" . pg_result ($res,0,codigo_posto) . " - " . pg_result($res,0,nome_posto) . "</B>&nbsp;</TD>";
	echo "</TR>";

	echo "<TR>";
	echo "<TD class='menu_top' align='left'> Extracto: <B>$extrato</B> </TD>";
	echo "<TD class='menu_top' align='left'> Fecha: <B>" . pg_result ($res,0,data_geracao) . "</B>&nbsp;</TD>";
	echo "<TD class='menu_top' align='left'> Ctd. OS: <B>". pg_numrows ($res) ."</B>&nbsp;</TD>";
	echo "<TD class='menu_top' align='left'> Total: <B>R$ " . pg_result ($res,0,total) . "</B>&nbsp;</TD>";
	echo "</TR>";
	echo "</TABLE>";

	echo "<TABLE width='665' border='0' cellspacing='0' cellpadding='1'>\n";
	echo "<TR>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>SERIE</B></TD>\n";
	echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>PRODUCTO</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>MAO OBRA</B></TD>\n";
	echo "<TD class='menu_top' align='center'><B>VALOR PIEZAS</B></TD>\n";
	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		flush();
		$os                 = trim(pg_result ($res,$i,os));
		$data               = trim(pg_result ($res,$i,data));
		$abertura           = trim(pg_result ($res,$i,abertura));
		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$serie              = trim(pg_result ($res,$i,serie));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));
		$produto_nome       = trim(pg_result ($res,$i,descricao));
		if(strlen($produto_nome) == 0){
			$produto_nome = "No Traducion";
		}
		//$produto_es         = trim(pg_result ($res,$i,descricao_es));
		$produto_referencia = trim(pg_result ($res,$i,referencia));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$mao_de_obra        = trim(pg_result ($res,$i,mao_de_obra));		
		$pecas              = trim(pg_result ($res,$i,pecas));		
		$t_mao_de_obra     = trim(pg_result ($res,$i,t_mao_de_obra));		
		$t_pecas           = trim(pg_result ($res,$i,t_pecas));		
		$extrato_pagamento = trim(pg_result ($res,0,extrato_pagamento)) ;
		$valor_total       = trim(pg_result ($res,0,valor_total)) ;
		$acrescimo         = trim(pg_result ($res,0,acrescimo)) ;
		$desconto          = trim(pg_result ($res,0,desconto)) ;
		$valor_liquido     = trim(pg_result ($res,0,valor_liquido)) ;
		$nf_autorizacao    = trim(pg_result ($res,0,nf_autorizacao)) ;
		$data_vencimento   = trim(pg_result ($res,0,data_vencimento)) ;
		$autorizacao_pagto = trim(pg_result ($res,0,autorizacao_pagto)) ;
		$revenda_nome      = trim(pg_result ($res,$i,revenda_nome)) ;
		$consumidor_revenda= trim(pg_result ($res,$i,consumidor_revenda)) ;
if($consumidor_revenda=="R" and $login_fabrica==6) { $consumidor_nome=$revenda_nome;}
		// FAZ A QUEBRA DE PAGINAS DO RELATORIO, PODE-SE MUDAR O NUMERO DE LINHAS
		if (($i%42==0)&&($i!=0)){

			echo "<TR>\n";
			echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)."&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".$produto_referencia	." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$mao_de_obra</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			echo "</TR>\n";

		   //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
 		    echo "<TR class='quebrapagina'>\n";
			echo "<TD class='menu_top' align='center' colspan='4' height='30'><B>SERVICIO: " . pg_result ($res,0,codigo_posto) . " - " . pg_result ($res,0,nome_posto) . "</B></TD>\n";
			echo "<TD class='menu_top' align='center' ><B>Fecha:" . pg_result ($res,0,data_geracao) . "</B></TD>\n";
			echo "<TD class='menu_top' align='center' valign='center' colspan='2'><B>Extracto:$extrato</B></TD>\n";
			echo "</TR>\n";
			//FIM DO CABEÇALHO

			//INICIO
			echo "<TR>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>OS</B></TD>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>SERIE</B></TD>\n";
			echo "<TD class='menu_top' align='center' width='75'><B>ABERTURA</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>CONSUMIDOR</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>PRODUCTO</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>MAO OBRA</B></TD>\n";
			echo "<TD class='menu_top' align='center'><B>VALOR PIEZAS</B></TD>\n";
			echo "</TR>\n";
		}else{
			echo "<TR>\n";
			echo "<TD class='table_line' nowrap>$sua_os&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>$serie&nbsp;</TD>\n";
			echo "<TD class='table_line' align='center'>$abertura&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".substr($consumidor_nome,0,18)."&nbsp;</TD>\n";
			echo "<TD class='table_line' nowrap>".$produto_referencia	." - ".substr($produto_nome,0,17)."&nbsp;</TD>\n";

			$mao_de_obra_reduzida="";
			if ($login_fabrica==11 AND count($os_array)>0){
				if (in_array($os,$os_array)){
					$mao_de_obra_reduzida = " <b>*</b>";
				}
			}
			
			echo "<TD class='table_line' align='center' nowrap>$mao_de_obra $mao_de_obra_reduzida</TD>\n";
			echo "<TD class='table_line' align='center' nowrap>$pecas</TD>\n";
			echo "</TR>\n";
		}
	}//FIM FOR

	echo "<TR >\n";	
	echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	echo "<TD class='table_line' align='center'>&nbsp;</TD>\n";
	echo "<TD class='table_line' nowrap>&nbsp;</TD>\n";
	echo "<TD class='menu_top' align='right' nowrap>Subtotal:&nbsp;</TD>\n";
	echo "<TD class='table_line' align='center' nowrap>$t_mao_de_obra&nbsp;</TD>\n";
	echo "<TD class='table_line' align='center' nowrap>$t_pecas&nbsp;</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
//	echo "</TD>";
//	echo "</TR>";
}//FIM ELSE

if ($login_fabrica==11 AND count($os_array)>0){
	echo "<br><p>(*) Produtos que foram consertadas pela Fábrica terão a mão de obra reduzida.</p>";
}

	##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.automatico ,
					tbl_extrato_lancamento.valor     
			FROM tbl_extrato_lancamento
			JOIN tbl_lancamento USING (lancamento)
			WHERE tbl_extrato_lancamento.extrato = $extrato
			AND   tbl_lancamento.fabrica = $login_fabrica";
	$res_avulso = pg_exec($con,$sql);

	if (pg_numrows($res_avulso) > 0) {
		echo "<br></br>";
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td class='table_line' nowrap colspan='4'><B>LANCAMIENTO DE EXTRACTO AVULSO<B></td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td class='menu_top' nowrap nowrap><B>DESCRIPCIÓN</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>HISTÓRICO</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>VALOR</B></td>\n";
		echo "<td class='menu_top' align='center' nowrap><B>AUTOMATICO</B></td>\n";
		echo "</tr>\n";
		for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td class='table_line' width='45%'>" . pg_result($res_avulso, $j, descricao) . "&nbsp;</td>";
			echo "<td class='table_line' width='45%'>" . pg_result($res_avulso, $j, historico) . "&nbsp;</td>";
			echo "<td class='table_line' width='10%' align='right'> " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "&nbsp;</td>";
			echo "<td class='table_line' width='10%' align='right'>" ;
			if (pg_result($res_avulso, $j, automatico) == 't') {
				echo "S";
			}else{
				echo "&nbsp;";
			}
			echo "</td>";
			echo "</tr>";
		}
			echo "</table>\n";
	}
	##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####


	if($login_fabrica==11){
		echo "<BR></BR><BR></BR><BR></BR>\n";
		echo "<table class='quebrapagina'><TR><TD>&nbsp;</TD></TR></table>";
	   //MONTA O CABEÇALHO DEPOIS DA QUEBRA DE PAGINA
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "<TR>\n";
		echo "<TD class='menu_top' align='center' colspan='4' height='30'><B>POSTO: " . pg_result ($res,0,codigo_posto) . " - " . pg_result ($res,0,nome_posto) . "</B></TD>\n";
		echo "<TD class='menu_top' align='center' ><B>Data:" . pg_result ($res,0,data_geracao) . "</B></TD>\n";
		echo "<TD class='menu_top' align='center' valign='center' colspan='2'><B>Extrato:$extrato</B></TD>\n";
		echo "</TR>\n";
		echo "</table>";
			//FIM DO CABEÇALHO
		echo "<BR><BR><BR>\n";

	}else{
			echo "<BR><BR>\n";
	}

	##### RESUMO DO EXTRATO - INÍCIO #####
	$sql =	"SELECT tbl_extrato.mao_de_obra ,
					tbl_extrato.pecas ,
					tbl_extrato.avulso,
					tbl_extrato.total
			FROM tbl_extrato
			WHERE tbl_extrato.extrato = $extrato
			AND   tbl_extrato.fabrica = $login_fabrica";
	$res_extrato = pg_exec($con,$sql);

	
	if (pg_numrows($res_extrato) > 0) {

		echo "<br><br><table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>\n";
		echo "<tr>\n";
		echo "<td width='30%'  align='left' nowrap><B>Total Mao Obra:</B></td>\n";
		echo "<td align='left' nowrap><B>".
				number_format( pg_result($res_extrato, 0, mao_de_obra), 2, ',', '.')
			 ."</B></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='left' nowrap><B>Total Piezas:</B></td>\n";
		echo "<td align='left' nowrap><B>".
				number_format( pg_result($res_extrato, 0, pecas), 2, ',', '.')
			 ."&nbsp;</B></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='left' nowrap><B>Total Avulsos:</B></td>\n";
		echo "<td align='left' nowrap><B>".
				number_format( pg_result($res_extrato, 0, avulso), 2, ',', '.')
			 ."&nbsp;</B></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td align='left' nowrap><B>TOTAL:</B></td>\n";
		echo "<td align='left' nowrap><B>".
				number_format( pg_result($res_extrato, 0, total), 2, ',', '.')
			 ."&nbsp;</B></td>\n";
		echo "</tr>\n";

		echo "</table>\n";
		echo "<br></br>\n";
	}
	##### RESUMO DO EXTRATO - FIM #####



	//SE FOR FÁBRICA 11, ENTAO DEVE IMPRIMIR UM AVISO
	if($login_fabrica==11){
		echo "<BR></BR><BR>\n";
		echo "<table width='665' align='left' border='0' cellspacing='0' cellpadding='0'>";
		echo "<TR>";
		echo "<td align='left' ><br><br><B>EMITIR NOTA FISCAL:</B><BR>
				Aulik  Industria e Comercio Ltda.<BR>
				Rua Carlos Alberto Santos, s/nr. - QD. “D” – LT. 20/21 - Miragem<BR>
				Lauro de Freitas / BA.<BR>
				CNPJ: 05.256.426/0001-24 <BR>
				INSCR.EST. : 62.942.325
				<BR><BR>
				<B>ENVIAR PARA:</b><BR>
				Aulik  Industria e Comercio Ltda.<BR>
				Rua Bela Cintra, 986 – 3 andar – Bela Vista<BR>
				São Paulo / SP. – CEP: 01415-000</td>
			</tr>";
		echo "</TABLE>";
	}else{
		echo "<br></br>";
	}
	
	echo "</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
?>

</body>

<script>
	window.print();
</script>