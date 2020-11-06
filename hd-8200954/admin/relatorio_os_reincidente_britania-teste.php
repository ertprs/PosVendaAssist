<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include 'funcoes.php';
include 'progressbar.php';

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q) > 2) {

		if ($tipo_busca == 'posto') {
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			//echo nl2br($sql);
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$cnpj = trim(pg_fetch_result($res,$i,'cnpj'));
					$nome = trim(pg_fetch_result($res,$i,'nome'));
					$codigo_posto = trim(pg_fetch_result($res,$i,'codigo_posto'));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}

		}
	}
exit;
}

include "gera_relatorio_pararelo_include.php";

if (strlen($_POST["btn_acao"]) > 0 ) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET ["btn_acao"]) > 0 ) $btn_acao = strtoupper($_GET ["btn_acao"]);

$zdata_inicial = (isset($_POST['data_inicial'])) ? trim($_POST['data_inicial']):trim($_GET['data_inicial']);
$zdata_final   = (isset($_POST['data_final'])) ? trim($_POST['data_final']):trim($_GET['data_final']);
$zos           = (isset($_POST['os'])) ?trim($_POST['os']):trim($_GET['os']);
$zstatus_os    = (isset($_POST['status_os'])) ?trim($_POST['status_os']):trim($_GET['status_os']);
$zposto_codigo= (isset($_POST['posto_codigo'])) ? trim($_POST["posto_codigo"]):trim($_GET["posto_codigo"]);

$layout_menu = "auditoria";
$title = "Relatório de OSs reincidentes";

include "cabecalho.php";

?>

<div class="texto_avulso" style="width:700px;">
	As informações geradas neste relatório, são informações do dia anterior. <br />
	Portanto o relatório tem um dia de atraso!
</div>

<?
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
        include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
        include "gera_relatorio_pararelo_verifica.php";
}

if ($btn_acao == 'PESQUISAR'){

	if (strlen($zos)>0){
		$Xos = " AND bi_os.sua_os = '$zos' ";
	}

	if (strlen($zstatus_os)>0) {
		$sql_tipo = $zstatus_os;
	}
	else {
		$sql_tipo = "67,70";
	}
	
	if (strlen($zdata_inicial) > 0) {
		$ano_inicial = substr($zdata_inicial,6,4);
		$mes_inicial = substr($zdata_inicial,3,2);
		$dia_inicial = substr($zdata_inicial,0,2);

		$xdata_inicial = formata_data ($zdata_inicial);
		$xdata_inicial = $xdata_inicial;
	}else{
		$msg_erro = "Favor informar o intervalo de data para pesquisa";
	}

	if (strlen($zdata_final) > 0) {
		$ano_final = substr($zdata_final,6,4);
		$mes_final = substr($zdata_final,3,2);
		$dia_final = substr($zdata_final,0,2);

		$xdata_final = formata_data ($zdata_final);
		$xdata_final = $xdata_final;
	}else{
		$msg_erro = "Favor informar o intervalo de data para pesquisa";
	}



	$dia1 = mktime(0,0,0,$mes_inicial,$dia_inicial,$ano_inicial);
	$dia2 = mktime(0,0,0,$mes_final,$dia_final,$ano_final);
	$d3 = ($dia2-$dia1);
	$dias = round(($d3/60/60/24));

	if ($dias > 30) {
		$msg_erro = "O Intervalo entre as datas não pode ultrapassar 30 dias, foi selecionado: $dias dias";
	}


	if(strlen($msg_erro) > 0){
		echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
	}



	if(strlen($zposto_codigo)>0){
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$posto_codigo' AND fabrica=$login_fabrica";
		$res_posto = pg_query($con, $sql);

		if (pg_num_rows($res_posto) > 0) {
			$posto = pg_result($res_posto, 0, 0);
			$sql_add .= " AND bi_os.posto=$posto ";
		}
		else {
			$msg_erro .= "Posto não encontrado";
		}
	}

	if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){
		if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
			$sql_data = " AND bi_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'";
			$sql_data_status = " AND tbl_os_status.data > '$xdata_inicial 00:00:00'";
		}

		$sql = "
		select
		os1.os,
		os1.os_reincidente as r1,
		os2.os_reincidente as r2

		into temp
		tmp_os_reincidentes

		from
		bi_os
		/* Uma OS nunca vai estar nos status 67 e 70 ao mesmo tempo, por isso funciona assim mesmo, sem precisar ver quem é o último status */
		join tbl_os_status on bi_os.os=tbl_os_status.os and bi_os.fabrica=tbl_os_status.fabrica_status and tbl_os_status.status_os in (67,70)
		join bi_os as os1 on bi_os.os=os1.os
		left join bi_os as os2 on os2.os=os1.os_reincidente

		where
		bi_os.fabrica=$login_fabrica
		and bi_os.fabrica = tbl_os_status.fabrica_status
		and bi_os.excluida IS NOT TRUE
		$sql_data
		$sql_data_status
		$Xos
		$sql_add;";

		#echo nl2br($sql);

		$res = pg_query($con, $sql);

		for($i = 3; $i <= 10; $i++) {
			$anterior = $i - 1;

			$sql = "
			ALTER TABLE tmp_os_reincidentes ADD COLUMN r$i int;

			CREATE INDEX tmp_os_reincidentes_r{$anterior} ON tmp_os_reincidentes(r$anterior);

			UPDATE
			tmp_os_reincidentes

			SET
			r$i=bi_os.os_reincidente

			FROM
			bi_os

			WHERE
			bi_os.os=tmp_os_reincidentes.r$anterior
			AND tmp_os_reincidentes.r$anterior IN (select r$anterior from tmp_os_reincidentes WHERE r$anterior IS NOT NULL);";

			#echo nl2br($sql);

			$res = pg_query($con, $sql);
		}

		$sql = "
		CREATE INDEX tmp_os_reincidentes_r10 ON tmp_os_reincidentes(r10);

		create temp table tmp_os_reincidentes_relacao (
		os int,
		os_principal int,
                fabrica      int
		);

		insert into tmp_os_reincidentes_relacao select os, os, $login_fabrica from tmp_os_reincidentes;
		insert into tmp_os_reincidentes_relacao select r1, os, $login_fabrica from tmp_os_reincidentes where r1 is not null;
		insert into tmp_os_reincidentes_relacao select r2, os, $login_fabrica from tmp_os_reincidentes where r2 is not null;
		insert into tmp_os_reincidentes_relacao select r3, os, $login_fabrica from tmp_os_reincidentes where r3 is not null;
		insert into tmp_os_reincidentes_relacao select r4, os, $login_fabrica from tmp_os_reincidentes where r4 is not null;
		insert into tmp_os_reincidentes_relacao select r5, os, $login_fabrica from tmp_os_reincidentes where r5 is not null;
		insert into tmp_os_reincidentes_relacao select r6, os, $login_fabrica from tmp_os_reincidentes where r6 is not null;
		insert into tmp_os_reincidentes_relacao select r7, os, $login_fabrica from tmp_os_reincidentes where r7 is not null;
		insert into tmp_os_reincidentes_relacao select r8, os, $login_fabrica from tmp_os_reincidentes where r8 is not null;
		insert into tmp_os_reincidentes_relacao select r9, os, $login_fabrica from tmp_os_reincidentes where r9 is not null;
		insert into tmp_os_reincidentes_relacao select r10, os, $login_fabrica from tmp_os_reincidentes where r10 is not null;

		create index tmp_os_reincidentes_relacao_os on tmp_os_reincidentes_relacao(os);
		create index tmp_os_reincidentes_relacao_fabrica on tmp_os_reincidentes_relacao(fabrica);

		SELECT	bi_os.os                                                   ,
		tmp_os_reincidentes_relacao.os_principal,
		bi_os.serie                                                ,
		bi_os.sua_os                                               ,
		bi_os.mao_de_obra                                          ,
		bi_os.produto                                              ,
		bi_os.posto                                                ,
		TO_CHAR(bi_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
		TO_CHAR(bi_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
		TO_CHAR(bi_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao ,
		TO_CHAR(bi_os.data_finalizada,'DD/MM/YYYY') AS finalizada     ,
		TO_CHAR(bi_os.data_conserto,'DD/MM/YYYY')   AS data_conserto  ,
		TO_CHAR(bi_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
		bi_os.data_abertura - bi_os.data_nf         AS dias_uso       ,
		bi_os.nota_fiscal                                          ,
		bi_os.fabrica                                              ,
		tbl_posto.nome                     AS posto_nome            ,
		tbl_posto.estado                   AS posto_estado          ,
		tbl_posto_fabrica.codigo_posto                              ,
		tbl_posto_fabrica.contato_email       AS posto_email        ,
		tbl_produto.referencia             AS produto_referencia    ,
		tbl_produto.descricao              AS produto_descricao     ,
		(select nome FROM tbl_marca where tbl_produto.marca = tbl_marca.marca
		and tbl_marca.fabrica=$login_fabrica) AS marca              ,
		(select nome FROM tbl_linha where tbl_produto.linha = tbl_linha.linha
		and tbl_linha.fabrica=$login_fabrica) AS linha              ,
		(select descricao FROM tbl_familia where tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = $login_fabrica) AS familia  ,
		tbl_produto.voltagem                                        ,
		tbl_os_extra.os_reincidente                                 ,
		tbl_os_extra.status_os AS status_os2                        ,
		(SELECT descricao FROM tbl_defeito_constatado where bi_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado) as defeito_constatado
		INTO TEMP tmp_os_reincidentes_dados_parcial
		FROM tmp_os_reincidentes_relacao
		JOIN bi_os ON tmp_os_reincidentes_relacao.os=bi_os.os
		JOIN tbl_os_extra             ON bi_os.os           = tbl_os_extra.os AND tbl_os_extra.i_fabrica=$login_fabrica
		JOIN tbl_produto              ON tbl_produto.produto = bi_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		JOIN tbl_posto                ON bi_os.posto        = tbl_posto.posto
		JOIN tbl_posto_fabrica        ON bi_os.posto        = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE 
		bi_os.fabrica = $login_fabrica
		ORDER BY os_principal ASC, os DESC;

		create index tmp_os_reincidentes_dados_parcial_os on tmp_os_reincidentes_dados_parcial(os);
		create index tmp_os_reincidentes_dados_parcial_fabrica on tmp_os_reincidentes_dados_parcial(fabrica);

		SELECT
		tmp_os_reincidentes_dados_parcial.*,
		tbl_os.consumidor_nome,
		tbl_os.consumidor_revenda,
		tbl_os.revenda_cnpj, 
		tbl_os.revenda_nome,
		tbl_os.consumidor_fone,
		tbl_os.obs_reincidencia,
		tbl_os_status.status_os AS status_os,
		tbl_os_status.Observacao AS status_observacao,
		tbl_status_os.descricao AS status_descricao,
		tbl_tecnico.nome

		INTO TEMP
		tmp_os_reinc_$login_admin

		FROM 
		tmp_os_reincidentes_dados_parcial
		JOIN tbl_os ON tmp_os_reincidentes_dados_parcial.os=tbl_os.os
		/* Uma OS nunca vai estar nos status 67 e 70 ao mesmo tempo, por isso funciona assim mesmo, sem precisar ver quem é o último status */
		LEFT JOIN tbl_os_status ON tbl_os.os=tbl_os_status.os AND tbl_os_status.status_os IN (67, 70) AND tbl_os_status.fabrica_status = $login_fabrica
		LEFT JOIN tbl_status_os ON tbl_os_status.status_os=tbl_status_os.status_os
		LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_os.tecnico AND tbl_tecnico.posto = tbl_os.posto AND tbl_tecnico.fabrica = {$login_fabrica}
		WHERE     tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE;


		SELECT
		tmp_os_reincidentes_relacao.os,
		tbl_peca.referencia,
		tbl_peca.descricao,
		tbl_peca.fabrica,
		TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') as digitacao_item 

		INTO TEMP
		tmp_os_reincidentes_pecas

		FROM
		tmp_os_reincidentes_relacao
		JOIN tbl_os_produto ON tmp_os_reincidentes_relacao.os=tbl_os_produto.os
		JOIN tbl_os_item USING(os_produto)
		JOIN tbl_peca using(peca)
		WHERE tbl_peca.fabrica=$login_fabrica AND tbl_os_item.fabrica_i=$login_fabrica
		AND   tmp_os_reincidentes_relacao.fabrica=$login_fabrica;

		CREATE INDEX tmp_os_reincidentes_pecas_os ON tmp_os_reincidentes_pecas(os);
		CREATE INDEX tmp_os_reincidentes_pecas_fabrica ON tmp_os_reincidentes_pecas(fabrica);
		
		SELECT * FROM tmp_os_reinc_$login_admin;";

		#echo nl2br($sql);
			
		$res = pg_exec($con,$sql);

		if (pg_num_rows($res)>0) {
			$num = pg_num_rows($res);
			echo "<h1>Total: $num</h1>";
		}

file_put_contents("/tmp/log_os_reincidente_brit.out", $sql);
		
#		echo nl2br($sql);
#		die;
#include("rodape.php");
#die;
		$num_os = pg_numrows($res);
		if(pg_numrows($res)>0){
			$sql_muitas = "
			SELECT ARRAY_TO_STRING(ARRAY(
				SELECT sua_os FROM tbl_os WHERE os IN (
					SELECT
					os

					FROM
					tmp_os_reincidentes

					WHERE
					r10 IS NOT NULL
				)
			), ', ') AS os_r10";
			$res_muitas = pg_query($con, $sql_muitas);
			$muitas = pg_result($res_muitas, 0, 0);

			if (strlen($muitas) > 0) {
				echo "
				<div class='texto_avulso' style='width:700px;'>
					ATENÇÃO: As OS a seguir possuem mais de 10 reincidências. O relatório listará no máximo 10<br>
					<br>
					$muitas
				</div>";
			}

			echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
			echo "<input type='hidden' name='data_final'     value='$data_final'>";
			echo "<input type='hidden' name='aprova'         value='$aprova'>";
		
			echo `rm /tmp/assist/relatorio-os-reincidente-$login_fabrica.xls`;		
			
			$fp = fopen ("/tmp/assist/relatorio-os-reincidente-$login_fabrica.html","w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>RELATÓRIO DE ORDENS DE SERVIÇO REINCIDENTES - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");

			
			fputs ($fp,"<table width='800' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>\n");

			fputs($fp,"<tr>\n");

			fputs($fp,"<td bgcolor='#485989'width='80'></td>\n");
			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>OS</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>SÉRIE</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DIGITACAO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA <br>ABERTURA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA <br>FECHAMENTO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>FINALIZADA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA CONSERTO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA NOTA FISCAL</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>SÉRIE</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>NOTA FISCAL</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>POSTO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>ESTADO</B></font></td>\n");
			
			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>CONSUMIDOR</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>CNPJ REVENDA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>NOME REVENDA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>REFERÊNCIA</B></font></td>\n");		

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>PRODUTO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>MARCA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>LINHA</B></font></td>\n");
			
			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>FAMILIA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DEFEITO CONSTATADO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>REFERÊNCIA</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DESCRICAO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>DATA ITEM</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>STATUS</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>MOTIVO DO POSTO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>SITUACAO</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>CONSUMIDOR</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>TELEFONE</B></font></td>\n");

			fputs($fp,"<td bgcolor='#485989' style='font-size: 9px;'><font color='#FFFFFF'><B>TÉCNICO RESPONSÁVEL</B></font></td>\n");

			fputs($fp,"</tr>\n");
			
			$cores = '';
			$qtde_intervencao = 0;

			for ($x=0; $x<$num_os;$x++) {
				$os						= pg_result($res, $x, os);
				$os_principal			= pg_result($res, $x, os_principal);
				$os_reincidente			= pg_result($res, $x, os_reincidente);
				$serie					= pg_result($res, $x, serie);
				$data_abertura			= pg_result($res, $x, data_abertura);
				$data_fechamento		= pg_result($res, $x, data_fechamento);
				$data_digitacao			= pg_result($res, $x, data_digitacao);
				$data_conserto			= pg_result($res, $x, data_conserto);
				$data_nf				= pg_result($res, $x, data_nf);
				$dias_uso				= pg_result($res, $x, dias_uso);
				$finalizada				= pg_result($res, $x, finalizada);
				$sua_os					= pg_result($res, $x, sua_os);
				$mao_obra				= pg_result($res, $x, mao_de_obra);
				$codigo_posto			= pg_result($res, $x, codigo_posto);
				$posto_nome				= pg_result($res, $x, posto_nome);
				$posto_estado			= pg_result($res, $x, posto_estado);
				$posto_email			= pg_result($res, $x, posto_email);
				$nota_fiscal			= pg_result($res, $x, nota_fiscal);
				$data_nf				= pg_result($res, $x, data_nf);
				$produto_referencia		= pg_result($res, $x, produto_referencia);
				$produto_descricao		= pg_result($res, $x, produto_descricao);
				$marca			 		= pg_result($res, $x, marca);
				$linha			 		= pg_result($res, $x, linha);
				$familia		 		= pg_result($res, $x, familia);
				$produto_voltagem		= pg_result($res, $x, voltagem);
				$data_digitacao			= pg_result($res, $x, data_digitacao);
				$data_abertura			= pg_result($res, $x, data_abertura);
				$os_reincidente			= pg_result($res, $x, os_reincidente);
				$status_os2 			= pg_result($res, $x, status_os2);
				$defeito_constatado		= pg_result($res, $x, defeito_constatado);

				$consumidor_nome		= pg_result($res, $x, consumidor_nome);
				$consumidor_revenda     = pg_result($res, $x, consumidor_revenda);
				$revenda_cnpj           = pg_result($res, $x, revenda_cnpj);
				$revenda_nome           = pg_result($res, $x, revenda_nome);
				$consumidor_fone		= pg_result($res, $x, consumidor_fone);
				$obs_reincidencia		= pg_result($res, $x, obs_reincidencia);
				$status_os				= pg_result($res, $x, status_os);
				$status_observacao		= pg_result($res, $x, status_observacao);
				$status_descricao		= pg_result($res, $x, status_descricao);

				$tecnico				= pg_result($res, $x, nome);

				if(strlen($sua_os)==0)$sua_os=$os;

				$sql_os = "
				SELECT consumidor_nome,
				consumidor_revenda,
				revenda_cnpj, 
				revenda_nome,
				consumidor_fone,
				obs_reincidencia,
				tbl_os_status.status_os AS status_os,
				tbl_os_status.Observacao AS status_observacao,
				tbl_status_os.descricao AS status_descricao

				FROM 
				tbl_os
				/* Uma OS nunca vai estar nos status 67 e 70 ao mesmo tempo, por isso funciona assim mesmo, sem precisar ver quem é o último status */
				LEFT JOIN tbl_os_status ON tbl_os.os=tbl_os_status.os AND tbl_os_status.status_os IN (67, 70)
				LEFT JOIN tbl_status_os ON tbl_os_status.status_os=tbl_status_os.status_os

				WHERE
				tbl_os.os = $os
				";

//				$res_os = pg_exec($con,$sql_os);


				if($os == $os_principal){
					$tipo_os = "<td align='center' width='0'><b>Ultima Os</b></td>\n";
					$cores++;
					$cor = ($cores % 2 == 0) ? "#B1CED8": '#E8EBEE';
					$style_reincidencia = null;
					$os_principal_destaque = "font-weight: bold;";
				}
				else {
					$style_reincidencia = "style='color:#FF0000;'";
					$tipo_os = "<td align='center' width='0' > Reincide De </td>\n";
					$os_principal_destaque = "";
				}

				fputs($fp,"<tr bgcolor='$cor' $style_reincidencia>\n");

				fputs($fp, $tipo_os);

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana ' align='left'>$sua_os</a> </td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' align='left'>$serie</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$data_digitacao</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$data_abertura</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$data_fechamento</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$finalizada</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$data_conserto</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$data_nf</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$dias_uso</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$nota_fiscal</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$codigo_posto - $posto_nome</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$posto_estado</td>\n");
				

				

				if($status_os2==19 and $mao_de_obra <=0){
					$situacao = "OS Aprovada sem Mão-de-obra";
				}else{
					if($status_os2==19 and $temp<=0) {
						$situacao = "OS Aprovada sem Mão-de-obra ";
					}
				}
				if($status_os2 == '')     $situacao = "OS Aprovada";
			

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$consumidor_nome</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$revenda_cnpj</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$revenda_nome</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$produto_referencia</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$produto_descricao</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$marca</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$linha</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$familia</td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$defeito_constatado</td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' ></td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' ></td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' ></td>\n");

				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$status_descricao</td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$obs_reincidencia</td>\n");
				
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$situacao</td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$consumidor_revenda</td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$consumidor_fone</td>\n");
				
				fputs($fp,"<td style='font-size: 9px; $os_principal_destaque font-family: verdana' >$tecnico</td>\n");

				fputs($fp,"</tr>\n");

				$sql_peca = "
				SELECT
				*

				FROM
				tmp_os_reincidentes_pecas

				WHERE
				tmp_os_reincidentes_pecas.os=$os
				AND tmp_os_reincidentes_pecas.fabrica = $login_fabrica
				";

				$res_peca = pg_exec($con,$sql_peca);
				$sua_osp = $sua_os;

				if (pg_num_rows($res_peca)>0) {
					for ($w=0;$w<pg_num_rows($res_peca);$w++) {

						$peca_descricao  = pg_result($res_peca,$w,descricao);
						$peca_referencia = pg_result($res_peca,$w,referencia);
						$digitacao_item  = pg_result($res_peca,$w,digitacao_item);
						
						fputs($fp,"<tr bgcolor='$cor' id='linha_$x'>\n");
						fputs($fp,"<td style='font-size: 9px; font-family: verdana'>Peças ==></td>\n");
						fputs($fp,"<td style='font-size: 9px; font-weight: bold; font-family: verdana ' align='left'>$sua_osp</td>\n");
						fputs($fp,str_repeat("<td></td>\n",20));
						fputs($fp,"<td style='font-size: 9px; font-family: verdana' align='left'>".$peca_referencia. "</td>\n");
						fputs($fp,"<td style='font-size: 9px; font-family: verdana' align='left'>".$peca_descricao. "</td>\n");
						fputs($fp,"<td style='font-size: 9px; font-family: verdana' align='left'>".$digitacao_item. "</td>\n");
						fputs($fp,str_repeat("<td></td>\n",3));
						fputs($fp,"</tr>\n");
					}
				}

				if (strlen($ja_mostrei[0]>0)) {
				unset($ja_mostrei);
				}
				unset($achou);
				fputs($fp,"</tr>\n");
			}

			$data = date("Y-m-d").".".date("H-i-s");
			echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr height=80>";
			echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>RELATÓRIO DE OS REINCIDENTE<BR>Clique aqui para fazer o </font><a href='xls/relatorio-os-reincidente-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo "</tr>";
			echo "</table>";			
			fputs ($fp,"</table>\n");
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);

		echo `cp /tmp/assist/relatorio-os-reincidente-$login_fabrica.html xls/relatorio-os-reincidente-$login_fabrica.$data.xls`;

			echo "<input type='hidden' name='qtde_os' value='$x'>";
		}else{
			echo "<center>Nenhuma OS encontrada.</center>";
		}
		$msg_erro = '';
	}
	else{
		echo "MENSAGEM: $msg_erro";
	}

}

?>
<link type="text/css" rel="stylesheet" href="css/tooltips.css">
<style type="text/css" media="screen">

.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}	

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

#tooltip {
                
	background: #FF9999;
	border:2px solid #000;
	display:none;
	padding: 2px 4px;
	color: #003399;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>




<script language="JavaScript" type="text/javascript">
	window.onload = function(){

		tooltip.init();

	}
</script>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}


</script>


<script language="JavaScript">
var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}
	}
}

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}


</script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

	});
</script>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo&tipo_busca=posto'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome&tipo_busca=posto'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>


<div id="page-container">
<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<BR>
<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Relatório de OS reincidente</caption>

<TBODY>
<TR>

	<TD>Número da OS<br><input type="text" name="os" id="os" size="20" maxlength="20" value="<? echo $zos ?>" class="frm"></TD>
	<TD></TD>
</TR>
<TR>

	<TD>Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $zdata_inicial ?>" class="frm"></TD>
	<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $zdata_final ?>" class="frm"></TD>
</TR>
<TR>

	<TD>Código Posto<br><input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<? echo $zposto_codigo ?>" class="frm"></TD>
	<TD>Nome do Posto<br><input type="text" name="posto_nome" id="posto_nome" size="40"  value="<? echo $zposto_nome ?>" class="frm"></TD>
</TR>
<TR>
	<TD colspan=4>Status<br>
		<select class='frm' name='status_os'>
			<option> </option>
			<?php 
				
				$sql = "select * from tbl_status_os where status_os in(67,70)";
				$res = pg_exec($con,$sql);

				
				for ($i=0;$i<pg_numrows($res);$i++) {

					$status_os_x = pg_result($res,$i,status_os);
					$descricao = pg_result($res,$i,descricao);
					
					?>
					<option value="<? echo $status_os_x;?>" <? if ($zstatus_os == $status_os_x) echo "SELECTED";?>><?php echo $descricao;?></option>
			<?
				}
			?>
	
		
		</select>
	</TD>
</TR>
</tbody>
<TR>
	<TD colspan="2" align='center'>
		<br>
		<input type='hidden' name='btn_acao' value=''>
		<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>

<? include "javascript_pesquisas.php";


#echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
#echo "<tr class='table_line'>";
#echo "<td align='center' background='#D9E2EF'>";
#echo "<a href='relatorio_os_reincidente_britania_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
#echo "</td>";
#echo "</tr>";
#echo "</table>";

include "rodape.php" ?>
</div>
