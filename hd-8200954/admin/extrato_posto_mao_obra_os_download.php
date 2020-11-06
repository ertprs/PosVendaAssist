<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
include "autentica_admin.php";
include 'funcoes.php';

$extrato = $_GET['extrato'];
if(strlen($extrato)==0){
	$extrato = $_POST['extrato'];
}

if(strlen($extrato)==0){
	$msg_erro .= "<p>Nenhum extrato selecionado</p>";
}

if(strlen($msg_erro)==0){

	$sql = "SELECT      lpad (tbl_os.sua_os,10,'0')                                  AS ordem           ,
					tbl_os.os                                                                       ,
					tbl_os.sua_os                                                                   ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data_digitacao  ,
					to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
					to_char (tbl_os.data_fechamento ,'DD/MM/YYYY')               AS data_fechamento ,
					to_char (tbl_os.data_nf ,'DD/MM/YYYY')                       AS data_nf ,
					to_char (tbl_os.finalizada,'DD/MM/YYYY')                     AS finalizada,
					tbl_os.consumidor_revenda                                                       ,
					tbl_os.serie                                                                    ,
					tbl_os.codigo_fabricacao                                                        ,
					tbl_os.consumidor_nome                                                          ,
					tbl_os.consumidor_fone                                                          ,
					tbl_os.revenda_nome                                                             ,
					(SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os) AS total_pecas  ,
					tbl_os.mao_de_obra                                           AS total_mo        ,
					tbl_os.cortesia                                                                 ,
					tbl_os.nota_fiscal                                                              ,
					tbl_os.posto                                                                    ,
					tbl_produto.referencia                                                          ,
					tbl_produto.descricao                                                           ,
					(tbl_os_extra.mao_de_obra-((CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN 0 ELSE mao_de_obra_desconto END))) AS  mao_de_obra_produto,
					tbl_os_extra.extrato                                                            ,
					tbl_os_extra.os_reincidente                                                     ,
					tbl_os.observacao                                                               ,
					tbl_os.motivo_atraso                                                            ,
					tbl_os_extra.motivo_atraso2                                                     ,
					tbl_os.obs_reincidencia                                                         ,
					to_char (tbl_extrato.data_geracao,'DD/MM/YYYY')              AS data_geracao    ,
					tbl_extrato.total                                            AS total           ,
					tbl_extrato.mao_de_obra                                      AS mao_de_obra     ,
					tbl_extrato.pecas                                            AS pecas           ,
					lpad (tbl_extrato.protocolo,5,'0')                           AS protocolo       ,
					tbl_posto.nome                                               AS nome_posto      ,
					tbl_posto_fabrica.codigo_posto                               AS codigo_posto    ,
					tbl_extrato_pagamento.valor_total                                               ,
					tbl_extrato_pagamento.acrescimo                                                 ,
					tbl_extrato_pagamento.desconto                                                  ,
					tbl_extrato_pagamento.valor_liquido                                             ,
					tbl_extrato_pagamento.nf_autorizacao                                            ,
					to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
					to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')  AS data_pagamento  ,
					tbl_extrato_pagamento.autorizacao_pagto                                         ,
					tbl_extrato_pagamento.obs                                                       ,
					tbl_extrato_pagamento.extrato_pagamento                                         ,
					tbl_os.data_fechamento - tbl_os.data_abertura  as intervalo
		FROM        tbl_os
		JOIN        tbl_os_extra        ON  tbl_os_extra.os                = tbl_os.os
		JOIN        tbl_extrato         ON  tbl_extrato.extrato            = tbl_os_extra.extrato
		LEFT JOIN tbl_extrato_pagamento ON  tbl_extrato_pagamento.extrato  = tbl_extrato.extrato
		JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
		JOIN      tbl_posto             ON  tbl_posto.posto                = tbl_os.posto
		JOIN      tbl_posto_fabrica     ON  tbl_posto.posto                = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		WHERE		tbl_extrato.fabrica = $login_fabrica
		AND         tbl_os_extra.extrato = $extrato
		ORDER BY    tbl_os_extra.os_reincidente,lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0') ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";

	$res = pg_exec($con,$sql);

	if (@pg_numrows($res) == 0) {
		echo "<h1>Nenhum resultado encontrado.</h1>";
	}else{

		echo `rm /tmp/assist/extrato-os-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/extrato-os-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>OS do Extrato $extrato");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<p>Ordens de Serviços do Extrato $extrato</p>");

		fputs ($fp,"<TABLE width='750' border='0' align='center' border='0' cellspacing='1' cellpadding='1'>");
		fputs ($fp, "<TR bgcolor='#000000'>");
		fputs ($fp, "<TD><font color='#FFFFFF'>OS</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>OS REINC.</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>SÉRIE</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>NF. COMPRA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>CONS/REV</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>CONSUMIDOR</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>REVENDA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>PRODUTO</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>DIGITAÇÃO</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>ABERTURA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>FECHAMENTO</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>FINALIZADA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>M.O. PRODUTO</font></TD>");
		fputs ($fp, "</TR>");

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$os                                = trim(pg_result ($res,$i,os));
			$os_reincidente             = trim(pg_result ($res,$i,os_reincidente));
			$sua_os                         = trim(pg_result ($res,$i,sua_os));
			$data_digitacao              = trim(pg_result ($res,$i,data_digitacao));
			$abertura                       = trim(pg_result ($res,$i,abertura));
			$sua_os                         = trim(pg_result ($res,$i,sua_os));
			$data_nf                         = trim(pg_result ($res,$i,data_nf));
			$finalizada                      = trim(pg_result ($res,$i,finalizada));
			$serie                              = trim(pg_result ($res,$i,serie));
			$codigo_fabricacao        = trim(pg_result ($res,$i,codigo_fabricacao));
			$consumidor_nome         = trim(pg_result ($res,$i,consumidor_nome));
			$consumidor_fone          = trim(pg_result ($res,$i,consumidor_fone));
			$revenda_nome              = trim(pg_result ($res,$i,revenda_nome));
			$produto_nome               = trim(pg_result ($res,$i,descricao));
			$produto_referencia       = trim(pg_result ($res,$i,referencia));
			$mao_de_obra_produto  = trim(pg_result ($res,$i,mao_de_obra_produto));
			$data_fechamento          = trim(pg_result ($res,$i,data_fechamento));
			$os_reincidente              = trim(pg_result ($res,$i,os_reincidente));
			$codigo_posto                = trim(pg_result ($res,$i,codigo_posto));
			$total_pecas                   = trim(pg_result ($res,$i,total_pecas));
			$total_mo                        = trim(pg_result ($res,$i,total_mo));
			$cortesia                        = trim(pg_result ($res,$i,cortesia));
			$motivo_atraso              = trim(pg_result ($res,$i,motivo_atraso));
			$motivo_atraso2            = trim(pg_result ($res,$i,motivo_atraso2));
			$obs_reincidencia         = trim(pg_result ($res,$i,obs_reincidencia));
			$nota_fiscal                   = trim(pg_result ($res,$i,nota_fiscal));
			$observacao                 = trim(pg_result ($res,$i,observacao));
			$consumidor_revenda   = trim(pg_result ($res,$i,consumidor_revenda));
			$intervalo                       = trim(pg_result ($res,$i,intervalo));

			$sua_os_reincidente="";
			if(strlen($os_reincidente)>0) {
				$sql2 = "SELECT  sua_os from tbl_os where fabrica = $login_fabrica and os = $os_reincidente";
				$res2 = pg_exec($con,$sql2);
				if(pg_numrows ($res2)>0){
					$sua_os_reincidente = trim(pg_result ($res2,0, sua_os));
				}

			}

			if ($i % 2 == 0) {
				$cor = "#F1F4FA";
				$btn = "azul";
			}else{
				$cor = "#F7F5F0";
				$btn = "amarelo";
			}

			fputs ($fp,  "<TR class='table_line' style='background-color: $cor;'>");
			fputs ($fp,  "<TD nowrap>".$sua_os."&nbsp;</a></TD>");
			fputs ($fp,  "<TD nowrap>".$sua_os_reincidente."&nbsp;</a></TD>");
			fputs ($fp,  "<TD nowrap>".$serie."</TD>");
			fputs ($fp,  "<TD align='center'>".$nota_fiscal."</TD>");
			fputs ($fp,  "<TD nowrap>".$consumidor_revenda."</TD>");
			fputs ($fp,  "<TD nowrap>".$consumidor_nome."</TD>");
			fputs ($fp,  "<TD nowrap>".$revenda_nome."</TD>");
			fputs ($fp,  "<TD nowrap>".$produto_nome."</TD>");
			fputs ($fp,  "<TD align='center'>".$data_digitacao."</TD>");
			fputs ($fp,  "<TD align='center'>".$abertura."</TD>");
			fputs ($fp,  "<TD align='center'>".$data_fechamento."</TD>");
			fputs ($fp,  "<TD align='center'>".$finalizada."</TD>");
			fputs ($fp,  "<TD nowrap align='right'>".number_format($mao_de_obra_produto,2,","," ")."</TD>"); //HD 74011
			fputs ($fp,  "</TR>");
		}
		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		$arquivo = "/www/assist/www/admin/xls/extrato-os-$login_fabrica.$login_admin.xls";

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo /tmp/assist/extrato-os-$login_fabrica.html`;

		header("Content-type: application/save");
		header("Content-Length:".filesize($arquivo));
		header('Content-Disposition: attachment; filename="' . $arquivo . '"');
		header('Expires: 0');
		header('Pragma: no-cache');
		readfile("$arquivo");
		exit;
	}
}
if (strlen($msg_erro)>0){
	echo "<p>".$msg_erro."</p>";
}
?>