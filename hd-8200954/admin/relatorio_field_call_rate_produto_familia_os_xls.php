<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

//if($login_fabrica<>19)$admin_privilegios="gerencia";

$msg = "";
if (false) { // 'true' para bloquear o programa, 'false' para deixar em produção
	include 'autentica_admin.php';
	include 'cabecalho.php';
	echo '<h2>Programa em manutenção.</h2></center>';
	include 'rodape.php';
	exit;
}

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0) $data_inicial = trim($_GET["data_inicial"]);

if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0) $data_final = trim($_GET["data_final"]);

if (strlen(trim($_POST["data_inicial_01"])) > 0) $data_inicial_01 = trim($_POST["data_inicial_01"]);
if (strlen(trim($_GET["data_inicial_01"])) > 0) $data_inicial_01 = trim($_GET["data_inicial_01"]);

if (strlen(trim($_POST["data_final_01"])) > 0) $data_final_01 = trim($_POST["data_final_01"]);
if (strlen(trim($_GET["data_final_01"])) > 0) $data_final_01 = trim($_GET["data_final_01"]);

if (strlen(trim($_POST["linha"])) > 0) $linha = trim($_POST["linha"]);
if (strlen(trim($_GET["linha"])) > 0) $linha = trim($_GET["linha"]);

if (strlen(trim($_POST["familia"])) > 0) $familia = trim($_POST["familia"]);
if (strlen(trim($_GET["familia"])) > 0) $familia = trim($_GET["familia"]);

if (strlen(trim($_POST["estado"])) > 0) $btn_acao = trim($_POST["estado"]);
if (strlen(trim($_GET["estado"])) > 0) $btn_acao = trim($_GET["estado"]);

if (strlen(trim($_POST["posto"])) > 0) $btn_acao = trim($_POST["posto"]);
if (strlen(trim($_GET["posto"])) > 0) $btn_acao = trim($_GET["posto"]);

if (strlen(trim($_POST["criterio"])) > 0) $btn_acao = trim($_POST["criterio"]);
if (strlen(trim($_GET["criterio"])) > 0) $btn_acao = trim($_GET["criterio"]);

if (strlen(trim($_POST["extrato_data_inicial"])) > 0) $extrato_data_inicial = trim($_POST["extrato_data_inicial"]);
if (strlen(trim($_GET["extrato_data_inicial"])) > 0) $extrato_data_inicial = trim($_GET["extrato_data_inicial"]);

if (strlen(trim($_POST["chk21"])) > 0) $chk21 = trim($_POST["chk21"]);
if (strlen(trim($_GET["chk21"])) > 0) $chk21 = trim($_GET["chk21"]);


if (strlen($data_inicial)==0 or strlen($data_final)==0){
	$data_inicial = $data_inicial_01;
	$data_final   = $data_final_01;
}

#Para a rotina automatica - Fabio - HD 17924
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

if (strlen($data_inicial)==0){
	include "gera_relatorio_pararelo_include.php";
}

if (strlen($data_inicial)>0 AND strlen($data_final)>0) {
	$btn_acao = "1";
}

if (strlen($data_inicial) > 0) {
	$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");
	
	if (strlen ( pg_last_error($con) ) > 0) $msg_erro = pg_last_error($con) ;
	
	if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
}

if (strlen($data_final) > 0) {
	$fnc            = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
	
	if (strlen ( pg_last_error($con) ) > 0) $msg_erro = pg_last_error($con) ;
	
	if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
}

if($login_fabrica==19) $layout_menu="gerencia";
else                   $layout_menu = "gerencia";
$title = "Relação de Ordens de Serviços Aprovadas";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

a.linkTitulo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	color: #ffffff
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

<br>

<?

##### OS aprovadas #####
if (strlen($chk21) > 0) {
	if ((strlen($extrato_data_inicial) == 10) AND (strlen($extrato_data_final) == 10)) {

		$x_extrato_data_inicial		= fnc_formata_data_pg($extrato_data_inicial);
		$x_extrato_data_final		= fnc_formata_data_pg($extrato_data_final);
		$x_extrato_data_inicial		= str_replace("'","",$x_extrato_data_inicial);
		$x_extrato_data_final		= str_replace("'","",$x_extrato_data_final);
		$dt = 1;

		$sqlX =	"SELECT extrato
				FROM    tbl_extrato
				WHERE   fabrica = $login_fabrica
				AND     aprovado BETWEEN '$x_extrato_data_inicial 00:00:00'  AND '$x_extrato_data_final 23:59:59'
				AND liberado IS NOT NULL";
		$resX = pg_query($con,$sqlX);
		$extratos = array();
		for ($i = 0 ; $i < pg_num_rows($resX) ; $i++){
			array_push($extratos,trim(pg_fetch_result($resX, $i, 'extrato')));
		}
		if (count($extratos)>0){
			$extratos = implode(",",$extratos);
			$join_extrato .= " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato IN ($extratos)";
			#$monta_sql .= " AND tbl_os_extra.extrato IN ($extratos)";
		}else{
			$monta_sql .= " AND 1 = 2 ";
		}
		if (strlen($msg) > 0) $msg .= " e ";
		$msg .= " Aprovadas entre os dias <i>$extrato_data_inicial</i> e <i>$extrato_data_final</i> ";
	}
	$qtde_chk++;
}

##### CONCATENA O SQL PADR?O #####
###  WHERE ###########
$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
if (strlen ($posto)  > 0)  $cond_3 = " tbl_posto.posto     = $posto ";

if (strlen($data_inicial)>0){
	$aux_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) . " 00:00:00";
}
if (strlen($data_final)>0){
	$aux_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) . " 23:59:59";
}

// hd 17410
if($login_fabrica == 14){
	$sql_servico=",tbl_servico_realizado.descricao                    AS servico_descricao";
}

if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($data_inicial)==0 OR strlen($data_final)==0){
	$msg_erro = "A Data é obrigatória!";
}

if (strlen($msg_erro)>0){
	echo "<p>".$msg_erro."</p>";
}

if (strlen($btn_acao)>0 AND strlen($msg_erro)==0){
	set_time_limit(1900);
	$sql =	"
		SELECT tbl_os_extra.os 
		INTO TEMP temp_fcrpfxlsoe_$login_admin
		FROM  tbl_os_extra
		JOIN  tbl_extrato USING (extrato)
		JOIN  tbl_posto using(posto)
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final'
		AND   tbl_extrato.liberado IS NOT NULL
		AND   $cond_3;

		CREATE INDEX temp_fcrpfxlsoe_OS_$login_admin ON temp_fcrpfxlsoe_$login_admin(OS);

		SELECT  lpad(tbl_os.sua_os,10,'0')                         AS ordem          ,
			tbl_os.os                                                            ,
			tbl_os.sua_os                                                        ,
			to_char(tbl_os.data_digitacao,'DD/MM/YYYY')        AS data           ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura       ,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento     ,
			to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada     ,
			tbl_os.data_digitacao                              AS data_consulta  ,
			tbl_os.serie                                                         ,
			tbl_os.excluida                                                      ,
			tbl_os.consumidor_nome                                               ,
			tbl_os.data_fechamento                                               ,
			tbl_os.nota_fiscal                                                   ,
			tbl_os.nota_fiscal_saida                                             ,
			tbl_os.consumidor_cpf                                                ,
			tbl_os.consumidor_cidade                                             ,
			tbl_os.consumidor_estado                                             ,
			tbl_os.consumidor_revenda                                            ,
			tbl_os.revenda_nome                                                  ,
			tbl_os.defeito_reclamado                                             ,
			tbl_os.defeito_constatado                                            ,
			tbl_os.qtde_produtos                                                 ,
			tbl_os.mao_de_obra                                                   ,
			tbl_posto.nome                                     AS posto_nome     ,
			tbl_posto.estado                                                     ,
			tbl_posto_fabrica.codigo_posto                     AS codigo_posto   ,
			tbl_produto.familia                                                  ,
			tbl_produto.referencia_pesquisa                    AS referencia     ,
			tbl_produto.descricao                                                ,
			'$login_login'                                     AS login_login    ,
			tbl_peca.referencia                                AS peca_referencia,
			tbl_peca.descricao                                 AS peca_descricao ,
			tbl_lista_basica.posicao                           AS peca_posicao   
			$sql_servico
		FROM      tbl_os
		JOIN       temp_fcrpfxlsoe_$login_admin somente_os ON tbl_os.os = somente_os.os
		JOIN       tbl_produto			ON  tbl_os.produto				= tbl_produto.produto
		JOIN       tbl_posto			ON  tbl_os.posto				= tbl_posto.posto
		JOIN       tbl_posto_fabrica	ON  tbl_posto.posto				= tbl_posto_fabrica.posto
										 AND tbl_posto_fabrica.fabrica	= $login_fabrica
		LEFT JOIN tbl_os_produto		ON tbl_os_produto.os			= tbl_os.os
		LEFT JOIN tbl_os_item			ON tbl_os_item.os_produto		= tbl_os_produto.os_produto
		LEFT JOIN tbl_peca				ON tbl_peca.peca				= tbl_os_item.peca
		LEFT JOIN tbl_lista_basica		ON tbl_lista_basica.peca		= tbl_peca.peca
										 AND tbl_lista_basica.produto	= tbl_os_produto.produto
										 AND tbl_lista_basica.fabrica	= tbl_posto_fabrica.fabrica
										 AND tbl_lista_basica.posicao	= tbl_os_item.posicao
		LEFT JOIN tbl_cliente			ON tbl_os.cliente				= tbl_cliente.cliente
		LEFT JOIN tbl_servico_realizado	ON tbl_os_item.servico_realizado= tbl_servico_realizado.servico_realizado
		WHERE tbl_os.excluida is not true
		AND   $cond_1
		AND   $cond_3
					";

	/*
		SELECT  lpad(tbl_os.sua_os,10,'0')                         AS ordem          ,
			tbl_os.os                                                            ,
			tbl_os.sua_os                                                        ,
			to_char(tbl_os.data_digitacao,'DD/MM/YYYY')        AS data           ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura       ,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento     ,
			to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada     ,
			tbl_os.data_digitacao                              AS data_consulta  ,
			tbl_os.serie                                                         ,
			tbl_os.excluida                                                      ,
			tbl_os.consumidor_nome                                               ,
			tbl_os.data_fechamento                                               ,
			tbl_os.nota_fiscal                                                   ,
			tbl_os.nota_fiscal_saida                                             ,
			tbl_os.consumidor_cpf                                                ,
			tbl_os.consumidor_cidade                                             ,
			tbl_os.consumidor_estado                                             ,
			tbl_os.consumidor_revenda                                            ,
			tbl_os.revenda_nome                                                  ,
			tbl_os.defeito_reclamado                                             ,
			tbl_os.defeito_constatado                                            ,
			tbl_os.qtde_produtos                                                 ,
			tbl_posto.nome                                     AS posto_nome     ,
			tbl_posto.estado                                                     ,
			tbl_posto_fabrica.codigo_posto                     AS codigo_posto   ,
			tbl_produto.familia                                                  ,
			tbl_produto.referencia_pesquisa                    AS referencia     ,
			tbl_produto.descricao                                                ,
			'$login_login'                                     AS login_login
		FROM		tbl_os
		JOIN temp_fcrpfxlsos_$login_admin somente_os ON tbl_os.os = somente_os.os
		JOIN		tbl_produto          ON  tbl_os.produto            = tbl_produto.produto
		JOIN		tbl_posto            ON  tbl_os.posto              = tbl_posto.posto
		JOIN		tbl_posto_fabrica    ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica  = $login_fabrica
		LEFT JOIN	tbl_cliente          ON  tbl_os.cliente            = tbl_cliente.cliente
		WHERE $cond_1
	*/
	#if (getenv("REMOTE_ADDR") == "201.42.109.153") echo nl2br($sql);
	#echo nl2br($sql); 
	#exit;
	flush();
		$res = pg_query($con,$sql);
		$numero_registros = @pg_num_rows($res);
	//echo $sql;
	# if ($ip == '201.0.9.216') { echo nl2br($sql); }

	if ($numero_registros > 0) {

		/*flush();
		echo "<script language='javascript'>";
		echo "document.getElementById('msg_carregando').style.visibility='hidden';";
		echo "</script>";
		echo "<br><br>";
		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'><span id='msg_carregando'><img src='imagens/carregar2.gif'><font face='Verdana' size='1' color=#FF0000><BR>Aguarde até o término do carregamento.</font></span></td>";
		echo "</tr>";
		echo "</table>";*/
		
		flush();
		
		$data = date ("d/m/Y H:i:s");


		$arquivo_nome     = "relatorio-consulta-os-$login_fabrica.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE ORDENS DE SERVIÇO APROVADAS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<table align='center' border='1' cellspacing='1' cellpadding='1'>\n");

		fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
		fputs ($fp, "<td colspan='10'><FONT  COLOR='#FFFFFF'>$msg</FONT></td>\n");
		fputs ($fp, "</tr>\n");

		fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OS</FONT></TD>\n");
		if($login_fabrica ==19 ) {
				fputs ($fp, "<TD nowrap>NF CLIENTE</TD>\n");
				fputs ($fp, "<TD nowrap>NF ORIGEM</TD>\n");
		}
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>SÉRIE</FONT></TD>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>ABERTURA</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>FECHAMENTO</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>CONSUMIDOR</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>REVENDA</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSTO</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PRODUTO</FONT></td>\n");
		
		if($login_fabrica ==19 ) {
			fputs ($fp, "<TD nowrap><FONT  COLOR='#FFFFFF'>QTDE</FONT></TD>\n");
		}
	
		if($login_fabrica ==14 ) {
			fputs ($fp, "<TD nowrap><FONT COLOR='#FFFFFF'>MÃO DE OBRA</FONT></TD>\n");
		}

		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>DEFEITO CONSTATADO</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PECA</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSICAO</FONT></td>\n");
		if($login_fabrica == 14) {
			fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>SERVIÇO REALIZADO</FONT></td>\n");
		}
		#if($login_fabrica==14)fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSI??O</FONT></td>\n");
		#fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PE?A</FONT></td>\n");
		fputs ($fp, "</tr>\n");

		for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
			$os                 = trim(pg_fetch_result($res, $i, 'os'));
			$data               = trim(pg_fetch_result($res, $i, 'data'));
			$abertura           = trim(pg_fetch_result($res, $i, 'abertura'));
			$fechamento         = trim(pg_fetch_result($res, $i, 'fechamento'));
			$finalizada         = trim(pg_fetch_result($res, $i, 'finalizada'));
			$sua_os             = trim(pg_fetch_result($res, $i, 'sua_os'));
			$serie              = trim(pg_fetch_result($res, $i, 'serie'));
			$consumidor_nome    = trim(pg_fetch_result($res, $i, 'consumidor_nome'));
			$revenda_nome       = trim(pg_fetch_result($res, $i, 'revenda_nome'));
			$nota_fiscal        = trim(pg_fetch_result($res, $i, 'nota_fiscal'));
			$nota_fiscal_saida   = trim(pg_fetch_result($res, $i, 'nota_fiscal_saida'));
			$posto_nome         = trim(pg_fetch_result($res, $i, 'posto_nome'));
			$posto_codigo       = trim(pg_fetch_result($res, $i, 'codigo_posto'));
			$posto_completo     = $posto_codigo . " - " . $posto_nome;
			$produto_nome       = trim(pg_fetch_result($res, $i, 'descricao'));
			$produto_referencia = trim(pg_fetch_result($res, $i, 'referencia'));
			$data_fechamento    = trim(pg_fetch_result($res, $i, 'data_fechamento'));
			$excluida           = trim(pg_fetch_result($res, $i, 'excluida'));
			$defeito_constatado = trim(pg_fetch_result($res, $i, 'defeito_constatado'));
			$qtde_produtos      = trim(pg_fetch_result($res, $i, 'qtde_produtos'));
			$mao_de_obra        = trim(pg_fetch_result($res, $i, 'mao_de_obra'));
			$peca_referencia    = trim(pg_fetch_result($res, $i, 'peca_referencia'));
			$peca_descricao     = trim(pg_fetch_result($res, $i, 'peca_descricao'));
			$peca_posicao       = trim(pg_fetch_result($res, $i, 'peca_posicao'));
		// HD 17410
			if($login_fabrica == 14){
				$servico_descricao       = trim(pg_fetch_result($res, $i, 'servico_descricao'));
			}

			if ($i==0)$os_armazena = $os ;
			else $os_armazena = $os_armazena .','. $os;
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			if (strlen(trim($sua_os)) == 0) $sua_os = $os;
			
			fputs ($fp, "<tr class='table_line' bgcolor='$cor;'>\n");

			if ($login_fabrica == 1) fputs ($fp, "<TD nowrap>$codigo_posto$sua_os</TD>\n");
			else                     fputs ($fp, "<TD nowrap>$sua_os</TD>\n");
			if($login_fabrica ==19 ) {
				fputs ($fp, "<TD nowrap>$nota_fiscal</TD>\n");
				fputs ($fp, "<TD nowrap>$nota_fiscal_saida</TD>\n");
			}
			fputs ($fp, "<td nowrap>$serie</td>\n");
			fputs ($fp, "<td align='center'><acronym title='Data Abertura Sistema: $abertura'  >$abertura</acronym></td>\n");
			fputs ($fp, "<td align='center'><acronym title='Data Fechamento Sistema: $finalizada'  >$fechamento</acronym></td>\n");
			fputs ($fp, "<td nowrap><acronym title='Consumidor: $consumidor_nome'  >" . substr($consumidor_nome,0,15) . "</acronym></td>\n");
			fputs ($fp, "<td nowrap><acronym title='Consumidor: $revenda_nome'  >" . substr($revenda_nome,0,15) . "</acronym></td>\n");
			fputs ($fp, "<td nowrap><acronym title='Código: $codigo_posto\nRazão Social: $posto_nome'  >" . substr($posto_completo,0,30) . "</acronym></td>\n");
			fputs ($fp, "<td nowrap>$produto_referencia - $produto_nome</td>\n");
			if($login_fabrica ==19 ) {
				fputs ($fp, "<TD nowrap>$qtde_produtos</TD>\n");
			}
			if($login_fabrica ==14) {
				fputs ($fp, "<TD nowrap>$mao_de_obra</TD>\n");
			}
			if(strlen($defeito_constatado)>0){
				$sql1 = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = $defeito_constatado";
				$res1 = pg_query($con,$sql1);
				//if (strlen($res1) > 0)
	//alterado takashi
				if (pg_num_rows($res1)>0)
					$defeito_constatado_descricao = trim(pg_fetch_result($res1, 0, 'descricao'));
				else $defeito_constatado_descricao = '';
			}
			fputs ($fp, "<td nowrap>$defeito_constatado_descricao</td>\n");
			fputs ($fp, "<td nowrap>$peca_referencia - $peca_descricao</td>\n");
			fputs ($fp, "<td nowrap>$peca_posicao</td>\n");
			if($login_fabrica == 14){
				fputs ($fp, "<td nowrap>$servico_descricao</td>\n");
			}
	/*
			$sql2 = "SELECT  
							tbl_peca.referencia             AS referencia_peca             ,
							tbl_peca.descricao              AS descricao_peca              ,
							tbl_os_item.posicao
					FROM	tbl_os_produto
					JOIN	tbl_os_item USING (os_produto)
					JOIN	tbl_produto USING (produto)
					JOIN	tbl_peca    USING (peca) 
					WHERE   tbl_os_produto.os = $os
					ORDER BY tbl_peca.descricao";
		//if ($ip == '201.0.9.216') echo $sql;
			$res2 = pg_query($con,$sql2);
			$total = pg_num_rows($res2);

			if (pg_num_rows($res2) > 0) {
				$referencia_peca           = trim(pg_fetch_result($res2, 0, 'referencia_peca'));
				$descricao_peca            = trim(pg_fetch_result($res2, 0, 'descricao_peca'));
				$posicao            = trim(pg_fetch_result($res2, 0, 'posicao'));
			}
			if($login_fabrica==14)fputs ($fp, "<td nowrap>$posicao</td>\n");
			fputs ($fp, "<td nowrap>$referencia_peca - $descricao_peca</td>\n");
	*/
			fputs ($fp, "</tr>\n");
		}
		fputs ($fp, "</table>\n");
		fputs ($fp, "<br>");
		fputs ($fp, "<table height='20'><tr class='menu_top'><td align='center'>Total de " . pg_num_rows($res) . " resultado(s) encontrado(s).</td></tr></table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		rename($arquivo_completo_tmp, $arquivo_completo);
//		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		exec("zip -quomT $path/$arquivo_nome.zip $arquivo_completo > null");
// 		echo ` cd $path; rm $arquivo_nome.zip; zip $arquivo_nome.zip $arquivo_nome > NULL`;

		echo"<table width='700' border='0' cellspacing='2' cellpadding='2' align='center' class='formulario'>";
		echo "<tr class='titulo_tabela'><td>Download do Relatório</td></tr>";
		echo"<tr>";
		echo "<td align='center'><input type='button' value='Download em Excel' onclick=\"window.location='xls/$arquivo_nome'\"></td>";
		echo "</tr>";
		echo "</table>";
	}

		$data = date("Y-m-d").".".date("H-i-s");

	if ($numero_registros == 0) {
		echo "<table width='700' height='50'><tr class='menu_top'><td align='center'>Nenhum resultado encontrado.</td></tr></table>";
	}

}

echo "<br><br>";
include "rodape.php"; 
exit;

//echo $os_armazena;
$sql2 = "SELECT
				tbl_os.sua_os                                                                     ,
				tbl_os.os                                                                         ,
				tbl_peca.referencia                                AS referencia_peca             ,
				tbl_peca.descricao                                 AS descricao_peca              ,
				tbl_posto.nome                                     AS posto_nome                  ,
				tbl_posto.estado                                                                  ,
				tbl_posto_fabrica.codigo_posto                     AS codigo_posto                ,
				tbl_produto.familia                                                               ,
				tbl_produto.referencia_pesquisa                    AS referencia                  ,
				tbl_produto.descricao                                                             ,
				tbl_os_item.posicao
		FROM	tbl_os_produto
		JOIN	tbl_os_item          USING (os_produto)
		JOIN	tbl_produto          USING (produto)
		JOIN	tbl_peca             USING (peca) 
		JOIN	tbl_os               ON tbl_os.os                  = tbl_os_produto.os
		JOIN	tbl_posto            ON  tbl_os.posto              = tbl_posto.posto
		JOIN	tbl_posto_fabrica    ON  tbl_posto.posto           = tbl_posto_fabrica.posto
		WHERE   tbl_os_produto.os IN ($os_armazena)
		ORDER BY tbl_peca.descricao";
// echo $sql2;
$total = 0;

$res2 = pg_query($con,$sql2);
if (is_resource($res2)) $total = pg_num_rows($res2);

if ($total == 0) {
	echo "<table width='700' height='50'><tr class='menu_top'><td align='center'>Nenhum resultado encontrado.</td></tr></table>";
}else{
		flush();
		
		echo "<br><br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
		
		flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/relatorio-consulta-os-peca-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELAT?RIO DE ORDENS DE SERVIÇO LANÇADAS - $data POR PEÇAS");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table align='center' border='1' cellspacing='1' cellpadding='1'>\n");

		fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
		fputs ($fp, "<td colspan='4'><FONT  COLOR='#FFFFFF'>$msg</FONT></td>\n");
		fputs ($fp, "</tr>\n");

		fputs ($fp, "<tr bgcolor='#0000FF' align='center'>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>OS</FONT></TD>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSTO</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PRODUTO</FONT></td>\n");
		if($login_fabrica=='14')fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>POSIÇÃO</FONT></td>\n");
		fputs ($fp, "<td><FONT  COLOR='#FFFFFF'>PEÇA</FONT></td>\n");
		fputs ($fp, "</tr>\n");

		for ($i = 0 ; $i < pg_numrows ($res2) ; $i++){
			$os                 = trim(pg_fetch_result($res2, $i, 'os'));
			$sua_os             = trim(pg_fetch_result($res2, $i, 'sua_os'));
			$posto_nome         = trim(pg_fetch_result($res2, $i, 'posto_nome'));
			$posto_codigo       = trim(pg_fetch_result($res2, $i, 'codigo_posto'));
			$posto_completo     = $posto_codigo . " - " . $posto_nome;
			$produto_nome       = trim(pg_fetch_result($res2, $i, 'descricao'));
			$produto_referencia = trim(pg_fetch_result($res2, $i, 'referencia'));
			$referencia_peca    = trim(pg_fetch_result($res2, $i, 'referencia_peca'));
			$descricao_peca     = trim(pg_fetch_result($res2, $i, 'descricao_peca'));
			$posicao            = trim(pg_fetch_result($res2, $i, 'posicao'));

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			if (strlen(trim($sua_os)) == 0) $sua_os = $os;

			fputs ($fp, "<tr class='table_line' bgcolor='$cor;'>\n");

			if ($login_fabrica == 1) fputs ($fp, "<TD nowrap>$codigo_posto$sua_os</TD>\n");
			else                     fputs ($fp, "<TD nowrap>$sua_os</TD>\n");

			fputs ($fp, "<td nowrap><acronym title='Código: $codigo_posto\nRazão Social: $posto_nome'  >" . substr($posto_completo,0,30) . "</acronym></td>\n");
			fputs ($fp, "<td nowrap>$produto_referencia - $produto_nome</td>\n");
			if($login_fabrica=='14')fputs ($fp, "<td nowrap>$posicao</td>\n");
			fputs ($fp, "<td nowrap>$referencia_peca - $descricao_peca</td>\n");

			fputs ($fp, "</tr>\n");

		}
		fputs ($fp, "</table>\n");
		fputs ($fp, "<br>");
		fputs ($fp, "<table height='20'><tr class='menu_top'><td align='center'>Total de " . pg_numrows($res2) . " resultado(s) encontrado(s).</td></tr></table>");

		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);


	$data = date("Y-m-d").".".date("H-i-s");

	rename("/tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html", "/www/assist/www/admin/xls/relatorio-consulta-os-peca-$login_fabrica.$data.xls");
//	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-consulta-os-peca-$login_fabrica.$data.xls /tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÒRIO POR PEÇA<BR>Clique aqui para fazer o </font><a href='xls/relatorio-consulta-os-peca-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";

}

echo "<br>";


##### BOT?O NOVA CONSULTA #####
echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<tr class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='relatorio_field_call_rate_produto_familia.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<br>";

include "rodape.php"; 
?>
