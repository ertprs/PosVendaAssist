<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim(strtolower($_POST['btn_acao']));
$msg_erro = "";

/*
$cookget = @explode("?", $REQUEST_URI);		// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookget", $cookget[1]);			// expira qdo fecha o browser
*/

// recebe as variaveis
$os = $_REQUEST['os'];
if($_REQUEST["sua_os"])				$sua_os                = trim($_REQUEST["sua_os"]);


if($_REQUEST["codigo_posto"])			$codigo_posto          = trim($_REQUEST["codigo_posto"]);
if($_REQUEST["produto_referencia"])	    $produto_referencia    = trim($_REQUEST["produto_referencia"]);
if($_REQUEST["numero_serie"])			$numero_serie          = trim($_REQUEST["numero_serie"]);
if($_REQUEST["nota_fiscal"])			$nota_fical            = trim($_REQUEST["nota_fical"]);
if($_REQUEST["nome_consumidor"])		$nome_consumidor       = trim($_REQUEST["nome_consumidor"]);
if($_REQUEST["data_inicial_01"])		$data_inicial_abertura = trim($_REQUEST["data_inicial_01"]);
if($_REQUEST["data_final_01"])			$data_final_abertura   = trim($_REQUEST["data_final_01"]);
if ($_REQUEST['data_pesquisa'])         $tipo_pesquisa         = trim($_REQUEST["data_pesquisa"]);

//$chk_opt5   = trim($_GET["chk_opt5"]);
$chk_opt6   = trim($_REQUEST["chk_opt6"]);
$chk_opt7   = trim($_REQUEST["chk_opt7"]);
$chk_opt8   = trim($_REQUEST["chk_opt8"]);
$chk_opt9   = trim($_REQUEST["chk_opt9"]);
$chk_opt13  = trim($_REQUEST["chk_opt13"]);
$chk_opt14  = trim($_REQUEST["chk_opt14"]);

$valida_data = 1;
if( (!$data_inicial_abertura or !$data_final_abertura) and (empty($chk_opt13) and empty($sua_os) and empty($os) ) ){

	$msg_erro = "Data Obrigatória";
	$valida_data = 0;

}

if (!empty($chk_opt13) and !empty($sua_os)){
	$valida_data = 0;
}

//Início Validação de Datas
if(strlen($msg_erro)==0 and $valida_data == 1 and empty($sua_os) and empty($os)){
	$dat = explode ("/", $data_inicial_abertura );//tira a barra
		$di = $dat[0];
		$mi = $dat[1];
		$yi = $dat[2];
		if(!checkdate($mi, $di, $yi)) $msg_erro = "Data inicial inválida";
}
if(strlen($msg_erro)==0 and $valida_data == 1 and empty($sua_os) and empty($os)){
	$dat = explode ("/", $data_final_abertura );//tira a barra
		$df = $dat[0];
		$mf = $dat[1];
		$yf = $dat[2];
		if(!checkdate($mf, $df, $yf)) $msg_erro = "Data final inválida";
}
if(strlen($msg_erro)==0 and $valida_data == 1 and empty($sua_os) and empty($os)){
	$nova_data_inicial = "$yi-$mi-$di";
	$nova_data_final   = "$yf-$mf-$df";

	if($nova_data_final < $nova_data_inicial){
		$msg_erro = "Data inicial maior do que data final";
	}

	if(strlen($msg_erro)==0){
		if (strtotime($nova_data_inicial.'+3 month') < strtotime($nova_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
        }
    }

	//Fim Validação de Datas
}


$produto_referencia = str_replace ("." , "" , $produto_referencia);
$produto_referencia = str_replace ("-" , "" , $produto_referencia);
$produto_referencia = str_replace ("/" , "" , $produto_referencia);
$produto_referencia = str_replace (" " , "" , $produto_referencia);

//hd 47506
if ($login_fabrica == 1 and strlen($sua_os)>0 and $chk_opt13) {

	if (strlen($codigo_posto)==0) {
		$msg_erro = "Preencha o código do posto";
	} else {

		$sql = "SELECT posto,codigo_posto FROM tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica=$login_fabrica";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res)==0){

			$msg_erro = "Código da OS inválido";

		}else{

			$posto 		  = pg_fetch_result($res, 0, 'posto');
			$posto_codigo = pg_fetch_result($res, 0, 'codigo_posto');

		}
	}

	$xsua_os = $sua_os;

	$pos = strpos($sua_os, "-");

	if ($pos === false) {
		//hd 47506
		if(strlen ($sua_os) > 11){
			$pos = strlen($sua_os) - (strlen($sua_os)-5);
		} elseif(strlen ($sua_os) > 10) {
			$pos = strlen($sua_os) - (strlen($sua_os)-6);
		} elseif(strlen ($sua_os) > 9) {
			$pos = strlen($sua_os) - (strlen($sua_os)-5);
		}else{
			$pos = strlen($sua_os);
		}
	}else{

		//hd 47506
		if(strlen (substr($sua_os,0,$pos)) > 11){#47506
			$pos = $pos - 7;
		} else if(strlen (substr($sua_os,0,$pos)) > 10) {
			$pos = $pos - 6;
		} elseif(strlen ($sua_os) > 9) {
			$pos = $pos - 5;
		}
	}

	$sua_os = substr($sua_os, $pos,strlen($sua_os));
	$sua_os = strtoupper ($sua_os);

	$sqlOs = "SELECT os 
			FROM tbl_os 
			WHERE posto = $posto
			AND sua_os  = '$sua_os'";
	$resOs = pg_query($con, $sqlOs);

	if (pg_num_rows($resOs) == 0) {
		$sua_os = str_replace($posto_codigo, '', $xsua_os);
	}
}


$layout_menu = "callcenter";
$title = "RELAÇÃO DE ORDENS DE SERVIÇO EXCLUÍDAS";

include "cabecalho.php";?>

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

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style><?php

// BTN_NOVA BUSCA
echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	if (strlen($msg_erro) > 0) {
		echo "<tr class='msg_erro'><td>".$msg_erro."</td></tr>";
	}?>
	<TR class='table_line'>
		<td align='center' background='#D9E2EF'>
			<input type='button' style='background:url(imagens_admin/btn_nova_busca.gif); width:400px;cursor:pointer;' value='&nbsp;' onclick="javascript: window.location='os_parametros_excluida.php'">
		</td>
	</TR>
</TABLE><?php

if ( strlen($chk_opt6) > 0 OR strlen($chk_opt7) > 0 OR strlen($chk_opt8) > 0 OR strlen($chk_opt9) > 0 OR strlen($chk_opt13) > 0 OR strlen($chk_opt14) > 0 OR strlen($os) > 0 or ($data_inicial_abertura and $data_final_abertura) ) {

	if (strlen($msg_erro) == 0) {

		$cond_not_posto = ($_serverEnvironment == 'development') ? "" : " AND  tbl_os.posto NOT IN ( 6359 ) ";

		// INICIO DA SQL PADRAO PARA TODAS AS OPCOES
		$sql = "SELECT  distinct on (lpad (a.sua_os,10,'0')) *
				FROM (
					(
					SELECT	tbl_admin.login                                       AS admin_nome        ,
							tbl_os_excluida.fabrica                                                    ,
							tbl_os_excluida.os                                                         ,
							tbl_os_excluida.sua_os                                                     ,
							tbl_os_excluida.codigo_posto                                               ,
							tbl_posto.nome                                        AS posto_nome        ,
							tbl_os_excluida.referencia_produto                                         ,
							tbl_produto.descricao                                 AS produto_descricao ,
							to_char(tbl_os_excluida.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
							to_char(tbl_os_excluida.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
							tbl_os_excluida.data_abertura                         AS data_consulta     ,
							to_char(tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
							tbl_os_excluida.serie                                                      ,
							tbl_os_excluida.nota_fiscal                                                ,
							to_char(tbl_os_excluida.data_nf,'DD/MM/YYYY')         AS data_nf           ,
							tbl_os_excluida.consumidor_nome                                            ,
							tbl_os_excluida.consumidor_endereco                                        ,
							tbl_os_excluida.consumidor_numero                                          ,
							tbl_os_excluida.consumidor_fone                                            ,
							tbl_os_excluida.consumidor_bairro                                          ,
							tbl_os_excluida.consumidor_cidade                                          ,
							tbl_os_excluida.consumidor_estado                                          ,
							tbl_os_excluida.defeito_reclamado                                          ,
							tbl_os_excluida.defeito_reclamado_descricao                                ,
							tbl_os_excluida.defeito_constatado                                         ,
							tbl_os_excluida.revenda_cnpj                                               ,
							tbl_os_excluida.revenda_nome                                               ,
							tbl_os_excluida.motivo_exclusao                                               ,
							tbl_defeito_constatado.descricao                                          ,
							to_char(tbl_os_excluida.data_exclusao,'DD/MM/YYYY')   AS data_exclusao     ,
							(	select observacao
								from tbl_os_status
								where fabrica_status = $login_fabrica
								AND os        = tbl_os_excluida.os
								order by data desc limit 1
							)                                                AS status2

					FROM   tbl_os_excluida
					JOIN     tbl_posto USING (posto)
					LEFT JOIN      tbl_defeito_constatado USING(defeito_constatado)
					JOIN     tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
												AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto       ON  (tbl_produto.referencia    = tbl_os_excluida.referencia_produto or tbl_produto.produto = tbl_os_excluida.produto) AND tbl_produto.fabrica_i=tbl_os_excluida.fabrica
					LEFT JOIN tbl_admin         ON  tbl_admin.admin           = tbl_os_excluida.admin
					WHERE tbl_os_excluida.fabrica =  $login_fabrica
					 AND tbl_os_excluida.posto NOT IN ( 6359 )
				";

					if (strlen($data_inicial_abertura) > 0 AND strlen($data_final_abertura) > 0) {

						$tipo_data = ($tipo_pesquisa == 'abertura') ? "data_abertura" : "data_exclusao";

						$sql .= " AND (tbl_os_excluida.$tipo_data BETWEEN '$nova_data_inicial 00:00:00'  AND '$nova_data_final 23:59:59') ";
						$dt = 1;
						if ($login_fabrica == 50) {
							$msg .= "OS's Excluídas no Período de {$data_inicial_abertura} à {$data_final_abertura}.";
						}else{
							$msg .= " e datas de abertura de OSs excluídas entre os dias $data_inicial_abertura e $data_final_abertura ";
						}
					}

					if (strlen($chk_opt6) > 0) {

						if (strlen($codigo_posto) > 0) {
							$sql .= " AND  tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";

							$msg .= " e OS lançadas pelo posto $nome_posto";

						}

					}


					if (strlen($chk_opt7) > 0) {

						if (strlen($produto_referencia) > 0) {
							$sql .= " and tbl_produto.referencia = '".$produto_referencia."' ";
						}

					}

					if (strlen($chk_opt8) >0) {

						if (strlen($numero_serie) > 0) {
							$sql .= " and tbl_os_excluida.serie = '". $numero_serie."' ";
						}

					}

					if (strlen($chk_opt9) > 0) {

						if (strlen($nome_consumidor) > 0) {
							$sql .= " AND tbl_os_excluida.consumidor_nome LIKE '".$nome_consumidor."%' ";
						}

					}

					if (strlen($os) > 0) {
						$sql .= " AND tbl_os_excluida.os =$os";
					}

					if (strlen($chk_opt13) > 0) {

						if (strlen($sua_os) > 0) {

							$sql .= " AND tbl_os_excluida.sua_os = '".$sua_os."' ";

							if ($login_fabrica == 1) {
								$sql .= "AND  tbl_os_excluida.posto = ". $posto." ";
							}

						}

					}

					if (strlen($chk_opt14) > 0) {

						if (strlen($nota_fiscal) > 0) {
							$sql .= " AND tbl_os_excluida.nota_fiscal ilike '%".$nota_fiscal."%' ";
						}

					}

					$sql .=" ) UNION (

					SELECT	(	select tbl_admin.login
							from tbl_os_status
							JOIN tbl_admin USING(admin)
							where fabrica_status = $login_fabrica
							and os        = tbl_os.os
							and   status_os = 15
							order by data desc limit 1
						)  AS admin_nome         ,
							tbl_os.fabrica                                                     ,
							tbl_os.os                                                          ,
							tbl_os.sua_os                                                      ,
							tbl_posto_fabrica.codigo_posto                                     ,
							tbl_posto.nome                               AS posto_nome         ,
							tbl_produto.referencia                       AS referencia_produto ,
							tbl_produto.descricao                        AS produto_descricao  ,
							to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao     ,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
							tbl_os.data_abertura                         AS data_consulta      ,
							to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
							tbl_os.serie                                                       ,
							tbl_os.nota_fiscal                                                 ,
							to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
							tbl_os.consumidor_nome                                             ,
							tbl_os.consumidor_endereco                                        ,
							tbl_os.consumidor_numero                                          ,
							tbl_os.consumidor_fone                                            ,
							tbl_os.consumidor_bairro                                          ,
							tbl_os.consumidor_cidade                                          ,
							tbl_os.consumidor_estado                                          ,
							tbl_os.defeito_reclamado                                          ,
							tbl_os.defeito_reclamado_descricao                                ,
							tbl_os.defeito_constatado                                         ,
							tbl_os.revenda_cnpj                                               ,
							tbl_os.revenda_nome                                               ,
							tbl_os.obs                                               ,
							tbl_defeito_constatado.descricao                                 ,
							(	select to_char(data,'DD/MM/YYYY' )
								from tbl_os_status
								where fabrica_status = $login_fabrica
								and os        = tbl_os.os
								order by data desc limit 1
							)                                                AS data_exclusao     ,
							(	select observacao
								from tbl_os_status
								where fabrica_status = $login_fabrica
								and os        = tbl_os.os
								order by data desc limit 1
							)                                                AS status2
					FROM tbl_os
					JOIN tbl_posto USING (posto)
					LEFT JOIN    tbl_defeito_constatado USING(defeito_constatado)
					JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
												AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto       ON  tbl_produto.produto      = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_admin         ON  tbl_admin.admin          = tbl_os.admin AND tbl_admin.fabrica = $login_fabrica
					LEFT JOIN tbl_os_excluida   ON  tbl_admin.admin          = tbl_os_excluida.admin AND tbl_os_excluida.fabrica= tbl_os.fabrica
					WHERE tbl_os.excluida IS TRUE
					{$cond_not_posto}
					AND  tbl_os.fabrica =  $login_fabrica ";
                    if (strlen($os) > 0) {
						$sql .= " AND tbl_os.os =$os";
					}

					if($data_inicial_abertura <> 'dd/mm/aaaa' AND $data_final_abertura <> 'dd/mm/aaaa'){
						if(strlen($data_inicial_abertura) > 0 AND strlen($data_final_abertura) > 0){
						// entre datas
							$data_inicial     = $data_inicial_abertura;
							$data_final       = $data_final_abertura;

							$data_inicial = str_replace ("/","",$data_inicial);
							$data_inicial = str_replace ("-","",$data_inicial);
							$data_inicial = str_replace (".","",$data_inicial);
							$data_inicial = str_replace (" ","",$data_inicial);
							$data_inicial = substr ($data_inicial,4,4) . "-" . substr ($data_inicial,2,2) . "-" . substr ($data_inicial,0,2);

							$data_final = str_replace ("/","",$data_final);
							$data_final = str_replace ("-","",$data_final);
							$data_final = str_replace (".","",$data_final);
							$data_final = str_replace (" ","",$data_final);
							$data_final = substr ($data_final,4,4) . "-" . substr ($data_final,2,2) . "-" . substr ($data_final,0,2);

							$sql .= " AND (tbl_os.data_abertura BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59') ";
							$dt = 1;


						}
					}

					if(strlen($chk_opt6)>0){
						if (strlen($codigo_posto) > 0){
							$sql .= " AND  tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
						}

					}
					if(strlen($chk_opt7)>0){
						if (strlen($produto_referencia) > 0) {
							$sql .= " and tbl_produto.referencia = '".$produto_referencia."' ";
						}
					}
					if(strlen($chk_opt8)>0){
						if (strlen($numero_serie) > 0) {
							$sql .= " and tbl_os.serie = '". $numero_serie."' ";
						}
					}
					if(strlen($chk_opt9)>0){
						if (strlen($nome_consumidor) > 0){
							$sql .= " AND tbl_os.consumidor_nome LIKE '".$nome_consumidor."%' ";
						}
					}
					if(strlen($chk_opt13)>0){
						if (strlen($sua_os) > 0){

							$sql .= " AND tbl_os.sua_os = '".$sua_os."' ";
							if ($login_fabrica == 1) {
								$sql .= "AND  tbl_os.posto = ". $posto." ";
							}

						}
					}
					if(strlen($chk_opt14)>0){
						if (strlen($nota_fiscal) > 0){
							$sql .= "AND tbl_os.nota_fiscal ilike '%".$nota_fiscal."%' ";

						}
					}
					$sql .="
					)
				) AS a
				WHERE 	1=1 ";


			$sql .= " ORDER BY lpad (a.sua_os,10,'0') DESC";
		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

		if (pg_num_rows($res) == 0) {
			echo "<TABLE width='700' height='50' align='center'><TR><TD align='center'>Nenhum resultado encontrado.</TD></TR></TABLE>";
		} else {
			echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";

			echo "<TR class='msg_erro'>\n";
			echo "<CAPTION class='titulo_coluna'>$msg</CAPTION>\n";
			echo "</TR>\n";
			echo "<TR class='titulo_coluna'>\n";
			echo "<TD>OS</TD>\n";
			echo "<TD>Série</TD>\n";
			echo "<TD width='075'>Abertura</TD>\n";
			echo "<TD width='130'>Consumidor</TD>\n";
			echo "<TD width='130'>Endereço</TD>\n";
			if($login_fabrica == 30) {
				echo '<td>Telefone</td>';
			}
			echo "<TD width='130'>Bairro</TD>\n";
			echo "<TD width='130'>Cidade</TD>\n";
			echo "<TD width='130'>Estado</TD>\n";
			echo "<TD width='130'>Defeito Reclamado</TD>\n";
			echo "<TD width='130'>Defeito Constatado</TD>\n";
			echo "<TD width='130'>Revenda</TD>\n";
			echo "<TD width='130'>Posto</TD>\n";
			echo "<TD>Produto</TD>\n";
			echo "<TD>Nota Fiscal</TD>\n";
			echo "<TD colspan='2'>Excluída</TD>\n";
			echo ($login_fabrica >= 131 || $login_fabrica == 1 OR $login_fabrica == 74) ? "<td>Motivo Exclusão</td>" : "";
			echo ($login_fabrica == 1) ? "<TD colspan='2'>Ação</TD>" : "";
			echo "</TR>\n";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				//$os_excluida        = trim(pg_result($res,$i,os_excluida));
				$admin_nome         = trim(pg_result($res,$i,admin_nome));
				$tem_admin         = !empty($admin_nome) ? true : false;
				/*if (strlen($admin_nome) == 0) $admin_nome = "Posto";
				else                          $admin_nome = ucfirst($admin_nome);*/
				$sua_os             = trim(pg_result($res,$i,sua_os));
				$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
				$data_digitacao     = trim(pg_result($res,$i,data_digitacao));
				$data_abertura      = trim(pg_result($res,$i,data_abertura));
				$data_fechamento    = trim(pg_result($res,$i,data_fechamento));
				$posto_nome         = trim(pg_result($res,$i,posto_nome));
				$referencia_produto = trim(pg_result($res,$i,referencia_produto));
				$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
				$serie              = trim(pg_result($res,$i,serie));
				$nota_fiscal        = trim(pg_result($res,$i,nota_fiscal));
				$data_nf            = trim(pg_result($res,$i,data_nf));
				$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
				$data_exclusao      = trim(pg_result($res,$i,data_exclusao));
				$status            = trim(pg_result($res,$i,status2));
				$os                = trim(pg_result($res,$i,os));
				$consumidor_endereco = trim(pg_result($res,$i,consumidor_endereco));
				$consumidor_numero = trim(pg_result($res,$i,consumidor_numero));
				$consumidor_fone   = trim(pg_result($res,$i,consumidor_fone));
				$consumidor_cidade = trim(pg_result($res,$i,consumidor_cidade));
				$consumidor_bairro = trim(pg_result($res,$i,consumidor_bairro));
				$consumidor_estado = trim(pg_result($res,$i,consumidor_estado));
				$defeito_reclamado = trim(pg_result($res,$i,defeito_reclamado));
				$defeito_reclamado_descricao = trim(pg_result($res,$i,defeito_reclamado_descricao));
				$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
				$defeito_constatado_descricao = trim(pg_result($res,$i,descricao));
				$revenda_cnpj = trim(pg_result($res,$i,revenda_cnpj));
				$revenda_nome = trim(pg_result($res,$i,revenda_nome));
				$motivo_exclusao = trim(pg_result($res,$i,motivo_exclusao));

				if($login_fabrica == 74 AND strlen($defeito_reclamado_descricao) == 0){
					$sql_defeito = "SELECT descricao FROM tbl_defeito_reclamado WHERE fabrica = $login_fabrica AND defeito_reclamado = $defeito_reclamado";
					$res_defeito = pg_query($con, $sql_defeito);
					if(pg_num_rows($res_defeito) > 0){
						$defeito_reclamado_descricao = pg_fetch_result($res_defeito, 0, 'descricao');
					}
				}

				if($login_fabrica == 30 and !$tem_admin){
					$sql_automatico = "select automatico from tbl_os_status where os = $os and fabrica_status = $login_fabrica";
					$res_automatico = pg_query($con, $sql_automatico);
					if(pg_num_rows($res_automatico)>0){
						$automatico = pg_fetch_result($res_automatico, 0, 'automatico');
					}
				}

				// HD 339722 - Verificar se o admin que excluiu  OS é o mesmo que solicitou. Mostrar quem solicitou
				if ($login_fabrica == 1 ) {

					$sql_adm_excl = "SELECT login FROM tbl_os_status JOIN tbl_admin USING(admin) WHERE os=$os AND status_os=110";
					$res_adm_excl = pg_query($con, $sql_adm_excl);

					if (is_resource($res_adm_excl)) {
						if (pg_num_rows($res_adm_excl) >= 1) $admin_nome = "Aut.: ".ucfirst($admin_nome)."<br>Sol.: ".ucfirst(pg_fetch_result($res_adm_excl, 0, 'login'));
					}

				}

				$cor = "#F7F5F0";
				$btn = "amarelo";

				if ($i % 2 == 0) {
					$cor = "#F1F4FA";
					$btn = "azul";
				}

				if (strlen (trim ($sua_os)) == 0) $sua_os = $os;

				echo "<TR bgcolor='$cor'>\n";
				echo "<TD nowrap title='$os'>";

				if ($login_fabrica == 6 || $login_fabrica == 50 || $telecontrol_distrib || $interno_telecontrol) {//HD 10137
					echo "<a href='os_press.php?os=$os' target='blank'>";
				}

				echo ($login_fabrica ==1) ?$codigo_posto.$sua_os:$sua_os;

				if ($login_fabrica == 6) {//HD 10137
					echo "</a>";
				}

				echo "</TD>\n";
				echo "<TD nowrap>$serie</TD>\n";
				echo "<TD align='center'><ACRONYM TITLE=\"Digitação: $data_digitacao | Fechamento: $data_fechamento\">$data_abertura</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_nome\">".substr($consumidor_nome,0,40)."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM title='$consumidor_endereco $consumidor_numero'>".$consumidor_endereco;
				echo ($consumidor_numero != '') ? ", $consumidor_numero" : '';
				echo "</ACRONYM></TD>\n";

				if ($login_fabrica == 30) {
					echo '<td>'.$consumidor_fone.'</td>';
				}

				echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_bairro\">".$consumidor_bairro."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_cidade\">".$consumidor_cidade."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$consumidor_estado\">".$consumidor_estado."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$defeito_reclamado_descricao\">".$defeito_reclamado_descricao."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$defeito_constadado\">".$defeito_constatado_descricao."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$revenda_nome\">".$revenda_nome."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$codigo_posto - $posto_nome\">".substr($posto_nome,0,17)."</ACRONYM></TD>\n";
				echo "<TD nowrap><ACRONYM TITLE=\"$referencia_produto - $produto_descricao\">".substr($produto_descricao,0,17)."</ACRONYM></TD>\n";
				echo "<TD align='center' nowrap><ACRONYM TITLE=\"Data da NF: $data_nf\">$nota_fiscal</ACRONYM></TD>\n";
				echo "<TD align='center' nowrap>$data_exclusao</TD>\n";
				
				echo "<TD nowrap>";
					if($login_fabrica == 30){
						if($automatico == "t") {
							echo "Automático"; 	
						}elseif(strlen(trim($admin_nome))==0){
							echo "Posto";
						}else{
							 echo ucfirst($admin_nome);
						}
					}else{
						echo ucfirst($admin_nome);
					}					
				echo "</TD>\n";


				echo ($login_fabrica >= 131 || $login_fabrica == 1 OR $login_fabrica == 74) ? "<td>{$motivo_exclusao}</td>" : "";
				if ($login_fabrica == 1) { /*HD - 6068124*/ ?>
					<td>
						<button name="btn_imprimir_<?=$i;?>" id="btn_imprimir_<?=$i;?>" onclick="window.open('os_press.php?os=<?=$os;?>');">Imprimir</button>
					</td>
					<td>
						<button name="btn_reverter_<?=$i;?>" id="btn_reverter_<?=$i;?>" onclick="window.open('os_cadastro_troca_black.php?os=<?=$os;?>&acao=troca');">Reverter</button>
					</td>
				<?php }
				echo "</TR>\n";

				if (strlen($status) > 0) {
					echo "<TR class='table_line' style='background-color: $cor;'>\n";
					echo "<TD nowrap colspan=25><B>Tipo Auditoria:</b> $status</TD>\n";
					echo "</TR>\n";
				}

			}
		}

		echo "</TABLE>\n";?>

		<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>
			<TR class='table_line'>
				<td align='center' background='#D9E2EF'>
					<input type='button' style='background:url(imagens_admin/btn_nova_busca.gif); width:400px;cursor:pointer;' value='&nbsp;' onclick="window.location='os_parametros_excluida.php'">
				</td>
			</TR>
		</TABLE><?php

		echo "<br>";

		echo "<div>";

		if ($pagina < $max_links) {
			$paginacao = pagina + 1;
		} else {
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links     = $mult_pag->Construir_Links("strings", "sim");
		$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links); // função que limita a quantidade de links no rodape

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ($pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0) {
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}

	}

} else {

	echo "Selecione uma opção para realizar a consulta.";

}

echo "<br />";

include "rodape.php";?>
