<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
include "autentica_admin.php";
include 'funcoes.php';

$sql_tipo = " 120, 122, 123, 126 ";

# HD 56446
if (isset($_GET["status_os"])){
	$aprovacao = $_GET["status_os"];
}else{
	$aprovacao = " 120, 122 ";
}

$data_inicial = trim($_GET['data_inicial']);
$data_final   = trim($_GET['data_final']);

if (strlen($data_inicial) > 0) {
	$xdata_inicial = formata_data ($data_inicial);
	$xdata_inicial = $xdata_inicial." 00:00:00";
}

if (strlen($data_final) > 0) {
	$xdata_final = formata_data ($data_final);
	$xdata_final = $xdata_final." 23:59:59";
}

if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
	$condsql = "AND tmp_interv_data_$login_admin.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
}
#HD 100725 foi acrescentado o campo admin e data de auditoria para fabrica britanica
if ($login_fabrica==3){
	$sql = "
		SELECT
			interv.os
			INTO TEMP tmp_interv_$login_admin
		FROM (
			SELECT
			ultima.os,
			(
				SELECT status_os
				FROM tbl_os_status
				WHERE status_os IN ($sql_tipo)
					AND tbl_os_status.os = ultima.os
					AND tbl_os_status.fabrica_status = $login_fabrica
				ORDER BY data
				DESC LIMIT 1
			) AS ultimo_status
			FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE status_os IN ($sql_tipo)
					AND tbl_os_status.fabrica_status = $login_fabrica
			) ultima
		) interv
		WHERE interv.ultimo_status IN ($aprovacao)
		$Xos
		;

		CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

		SELECT  os, data
			INTO TEMP tmp_interv_data_$login_admin
			FROM tmp_interv_$login_admin
			JOIN tbl_os_status USING(os)
			WHERE status_os IN ($aprovacao)
			AND tbl_os_status.fabrica_status = $login_fabrica;


		SELECT  tbl_os.sua_os,
			tbl_posto.posto,
			tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_posto.estado,
			to_char(tbl_os.data_abertura, 'dd/mm/yyyy') as data_abertura,
			to_char(tbl_os.data_digitacao, 'dd/mm/yyyy hh24:mi') as data_digitacao,
			to_char(tbl_os.data_fechamento, 'dd/mm/yyyy') as data_fechamento,
			to_char(tbl_os.finalizada, 'dd/mm/yyyy hh24:mi') as finalizada,
			to_char(tbl_os.data_conserto, 'dd/mm/yyyy') as data_conserto,
			tbl_os.consumidor_nome,
			tbl_produto.referencia as produto_referencia,
			tbl_produto.descricao as produto_descricao,
			tbl_peca.referencia as peca_referencia,
			tbl_peca.descricao as peca_descricao,
			tbl_os_item.qtde,
			tbl_defeito_constatado.descricao as defeito_constatado_descricao,
			tbl_solucao.descricao  as solucao_descricao,
			tbl_servico_realizado.descricao as servico_realizado_descricao,
			tbl_os_item.pedido,
			to_char(tbl_os_item.digitacao_item, 'dd/mm/yyyy') as digitacao_item,
			tbl_os.nota_fiscal,
			tbl_os.data_nf,
			tbl_admin.nome_completo                                   AS nome_completo         ,
			TO_CHAR(tmp_interv_data_$login_admin.data,'DD/MM/YYYY')   AS data_auditada         ,
			(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo) 
			AND tbl_os_status.fabrica_status = $login_fabrica
			AND tbl_os.fabrica = $login_fabrica
			ORDER BY data DESC LIMIT 1) AS status_descricao
		FROM tmp_interv_$login_admin X
		JOIN tbl_os ON tbl_os.os = X.os
		LEFT JOIN tbl_admin USING (admin)
		JOIN tmp_interv_data_$login_admin ON tmp_interv_data_$login_admin.os = X.os
		JOIN tbl_posto using(posto)
		JOIN tbl_posto_fabrica           ON tbl_posto.posto           = tbl_posto_fabrica.posto                    AND tbl_posto_fabrica.fabrica = 3
		JOIN tbl_produto                 ON tbl_os.produto            = tbl_produto.produto
		LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado  AND tbl_defeito_constatado.fabrica = 3
		LEFT JOIN tbl_solucao            ON tbl_os.solucao_os         = tbl_solucao.solucao                        AND tbl_solucao.fabrica = 3
		LEFT JOIN tbl_os_produto         ON tbl_os.os                 = tbl_os_produto.os
		LEFT JOIN tbl_os_item            ON tbl_os_item.os_produto    = tbl_os_produto.os_produto
		LEFT JOIN tbl_peca               ON tbl_os_item.peca          = tbl_peca.peca                              AND tbl_peca.fabrica = 3
		LEFT JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
		WHERE tbl_os.fabrica = $login_fabrica
		AND data_abertura < (current_date - interval '90 days')
		AND excluida is not true
		AND finalizada is null
		$condsql
		ORDER BY tbl_posto.nome,
				 tbl_os.sua_os;";
	}else{
		$sql = "
			SELECT
				interv.os
				INTO TEMP tmp_interv_$login_admin
			FROM (
				SELECT
				ultima.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN ($sql_tipo)
					AND tbl_os_status.os = ultima.os
					AND tbl_os_status.fabrica_status = $login_fabrica
					ORDER BY data
					DESC LIMIT 1
				) AS ultimo_status
				FROM (
						SELECT DISTINCT os
						FROM tbl_os_status
						WHERE status_os IN ($sql_tipo)
						AND tbl_os_status.fabrica_status = $login_fabrica
				) ultima
			) interv
			WHERE interv.ultimo_status IN ($aprovacao)
			$Xos
			;

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			SELECT  os,	data
				INTO TEMP tmp_interv_data_$login_admin
				FROM tmp_interv_$login_admin
				JOIN tbl_os_status USING(os)
				WHERE status_os IN ($aprovacao)
				AND tbl_os_status.fabrica_status = $login_fabrica;

			SELECT  tbl_os.sua_os,
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_posto.estado,
				to_char(tbl_os.data_abertura, 'dd/mm/yyyy') as data_abertura,
				to_char(tbl_os.data_digitacao, 'dd/mm/yyyy hh24:mi') as data_digitacao,
				to_char(tbl_os.data_fechamento, 'dd/mm/yyyy') as data_fechamento,
				to_char(tbl_os.finalizada, 'dd/mm/yyyy hh24:mi') as finalizada,
				to_char(tbl_os.data_conserto, 'dd/mm/yyyy') as data_conserto,
				tbl_os.consumidor_nome,
				tbl_produto.referencia as produto_referencia,
				tbl_produto.descricao as produto_descricao,
				tbl_peca.referencia as peca_referencia,
				tbl_peca.descricao as peca_descricao,
				tbl_os_item.qtde,
				tbl_defeito_constatado.descricao as defeito_constatado_descricao,
				tbl_solucao.descricao  as solucao_descricao,
				tbl_servico_realizado.descricao as servico_realizado_descricao,
				tbl_os_item.pedido,
				to_char(tbl_os_item.digitacao_item, 'dd/mm/yyyy') as digitacao_item,
				tbl_os.nota_fiscal,
				tbl_os.data_nf,
				(SELECT tbl_status_os.descricao FROM tbl_os_status JOIN tbl_status_os USING(status_os) WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($sql_tipo)
				AND tbl_os_status.fabrica_status = $login_fabrica
				AND tbl_os.fabrica = $login_fabrica
				ORDER BY data DESC LIMIT 1) AS status_descricao
			FROM tmp_interv_$login_admin X
			JOIN tbl_os ON tbl_os.os = X.os
			JOIN tmp_interv_data_$login_admin ON tmp_interv_data_$login_admin.os = X.os
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica           ON tbl_posto.posto           = tbl_posto_fabrica.posto                    AND tbl_posto_fabrica.fabrica = 3
			JOIN tbl_produto                 ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado  AND tbl_defeito_constatado.fabrica = 3
			LEFT JOIN tbl_solucao            ON tbl_os.solucao_os         = tbl_solucao.solucao                        AND tbl_solucao.fabrica = 3
			LEFT JOIN tbl_os_produto         ON tbl_os.os                 = tbl_os_produto.os
			LEFT JOIN tbl_os_item            ON tbl_os_item.os_produto    = tbl_os_produto.os_produto
			LEFT JOIN tbl_peca               ON tbl_os_item.peca          = tbl_peca.peca                              AND tbl_peca.fabrica = 3
			LEFT JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
			WHERE tbl_os.fabrica = $login_fabrica
			AND data_abertura < (current_date - interval '90 days')
			AND excluida is not true
			AND finalizada is null
			$condsql
			ORDER BY tbl_posto.nome,
					 tbl_os.sua_os;";	 
	 }
#echo nl2br($sql);
#exit;

$res = pg_exec($con,$sql);

if (@pg_numrows($res) == 0) {
	echo "<h1>Nenhum resultado encontrado.</h1>";
}else{

	echo `rm /tmp/assist/auditoria_os_aberta_90.xls`;

	$fp = fopen ("/tmp/assist/auditoria_os_aberta_90.html","w");

	fputs ($fp,"<html>\n");
	fputs ($fp,"<head>\n");
	fputs ($fp,"<title>Auditoria de OS Aberta a mais de 90 dias\n");
	fputs ($fp,"</title>\n");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>\n");
	fputs ($fp,"</head>\n");
	fputs ($fp,"<body>\n\n");

	fputs ($fp,"<p>Ordens de Serviços abertas a mais de 90 dias</p>\n\n");

	fputs ($fp,"<TABLE width='750' border='1' align='center' cellspacing='1' cellpadding='1'>\n");
	fputs ($fp, "<TR bgcolor='#000000'>\n");
	fputs ($fp, "<TD><font color='#FFFFFF'>OS</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>CÓDIGO POSTO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>NOME POSTO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>ABERTURA</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>DIGITAÇÃO</font></TD>");
	if ($login_fabrica==3){
		fputs ($fp, "<TD><font color='#FFFFFF'>AUDITADA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>ADMIN</font></TD>");
	}
	fputs ($fp, "<TD><font color='#FFFFFF'>FECHAMENTO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>FINALIZADA</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>CONSERTO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>CONSUMIDOR</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>PRODUTO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>DESCRIÇÃO PRODUTO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>PEÇA</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>DESCRIÇÃO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>QTDE</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>DEFEITO CONSTATADO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>SOLUÇÃO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>PEDIDO</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>DIGITAÇÃO ITEM</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>NOTA FISCAL</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>DATA NF</font></TD>");
	fputs ($fp, "<TD><font color='#FFFFFF'>STATUS</font></TD>");


	fputs ($fp, "</TR>\n");

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$sua_os             = trim(pg_result ($res,$i,sua_os));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result ($res,$i,nome));
		$data_abertura      = trim(pg_result ($res,$i,data_abertura));
		$data_digitacao     = trim(pg_result ($res,$i,data_digitacao));
		$data_fechamento    = trim(pg_result ($res,$i,data_fechamento));
		$finalizada         = trim(pg_result ($res,$i,finalizada));
		$data_conserto      = trim(pg_result ($res,$i,data_conserto));
		$consumidor_nome    = trim(pg_result ($res,$i,consumidor_nome));

		$produto_referencia = trim(pg_result ($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result ($res,$i,produto_descricao));
		$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
		$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
		$qtde               = trim(pg_result ($res,$i,qtde));
		$defeito_constatado_descricao= trim(pg_result ($res,$i,defeito_constatado_descricao));
		$solucao_descricao     = trim(pg_result ($res,$i,solucao_descricao));
		$servico_realizado_descricao = trim(pg_result ($res,$i,servico_realizado_descricao));
		$pedido             = trim(pg_result ($res,$i,pedido));
		$digitacao_item     = trim(pg_result ($res,$i,digitacao_item));
		$nota_fiscal        = trim(pg_result ($res,$i,nota_fiscal));
		$data_nf            = trim(pg_result ($res,$i,data_nf));
		$status_descricao   = trim(pg_result ($res,$i,status_descricao));
		if ($login_fabrica==3){
			$admin			= trim(pg_result ($res, $i, nome_completo));
			$data_auditada	= trim(pg_result ($res, $i, data_auditada));
		}

		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}

		fputs ($fp,  "<TR class='table_line' style='background-color: $cor;'>\n");
		fputs ($fp,  "<TD nowrap align=right>".$sua_os."</a></TD>");
		fputs ($fp,  "<TD nowrap align=right>".$codigo_posto."</a></TD>");
		fputs ($fp,  "<TD nowrap>".$posto_nome."</TD>");
		fputs ($fp,  "<TD align='center'>".$data_abertura."</TD>");
		fputs ($fp,  "<TD align='center'>".$data_digitacao."</TD>");
		fputs ($fp,  "<TD align='center'>".$data_auditada."</TD>");
		fputs ($fp,  "<TD align='center'>".$admin."</TD>");
		fputs ($fp,  "<TD align='center'>".$data_fechamento."</TD>");
		fputs ($fp,  "<TD align='center'>".$finalizada."</TD>");
		fputs ($fp,  "<TD align='center'>".$data_conserto."</TD>");
		fputs ($fp,  "<TD nowrap>".$consumidor_nome."</TD>");
		fputs ($fp,  "<TD nowrap>".$produto_referencia."</TD>");
		fputs ($fp,  "<TD nowrap>".$produto_descricao."</TD>");
		fputs ($fp,  "<TD nowrap>".$peca_referencia."</TD>");
		fputs ($fp,  "<TD nowrap>".$peca_descricao."</TD>");
		fputs ($fp,  "<TD nowrap>".$qtde."</TD>");
		fputs ($fp,  "<TD nowrap>".$defeito_constatado_descricao."</TD>");
		fputs ($fp,  "<TD nowrap>".$servico_realizado_descricao	."</TD>");
		fputs ($fp,  "<TD nowrap>".$pedido."</TD>");
		fputs ($fp,  "<TD nowrap>".$digitacao_item."</TD>");
		fputs ($fp,  "<TD nowrap>".$nota_fiscal."</TD>");
		fputs ($fp,  "<TD nowrap>".$data_nf."</TD>");
		fputs ($fp,  "<TD nowrap>".$status_descricao."</TD>");
		fputs ($fp,  "</TR>\n");
	}
	fputs ($fp,"</table>\n\n");
	fputs ($fp,"</body>\n");
	fputs ($fp,"</html>\n");
	fclose ($fp);

	$arquivo = "/www/assist/www/admin/xls/auditoria_os_aberta_90.xls";

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo /tmp/assist/auditoria_os_aberta_90.html`;

	header("Content-type: application/save");
	header("Content-Length:".filesize($arquivo));
	header('Content-Disposition: attachment; filename="' . $arquivo . '"');
	header('Expires: 0');
	header('Pragma: no-cache');
	readfile("$arquivo");
	exit;
}

if (strlen($msg_erro)>0){
	echo "<p>".$msg_erro."</p>";
}
?>