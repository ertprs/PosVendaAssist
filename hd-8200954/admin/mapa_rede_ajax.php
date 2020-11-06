<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include '../helpdesk/mlg_funciones.php';
include "funcoes.php";

use Posvenda\TcMaps;

#error_reporting(E_ALL);
if (isset($_GET['latlon'])) {
	$latlonIndex =  $_GET['latlon'];
	$callcenter = $_GET['callcenter'];
	$relatorio_posto = $_GET['relatorio_posto'];

	if (!function_exists('anti_injection')) {
		function anti_injection($string) {
			$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
			return strtr(strip_tags(trim($string)), $a_limpa);
		}
	}

	$cidade     = anti_injection($_GET['cidade']);
	$estado     = anti_injection($_GET['estado']);
	$bairro     = anti_injection($_GET['bairro']);
	$pais       = anti_injection($_GET['pais']);
	$cep_orig   = anti_injection($_GET['cep']);
	$linha      = anti_injection($_GET['linha']);
	$produto    = anti_injection($_GET['produto']);
	$consumidor = anti_injection($_GET['consumidor']);
	$estado     = ((!$estado or $estado == '00') and $consumidor) ? substr($consumidor, -2) : $estado;
	$nome_cliente = anti_injection($_GET['nome']);

	$hd_chamado = anti_injection($_GET['hd_chamado']);

	if($login_fabrica == 189){
		$tipo_cliente = anti_injection($_GET["tipo_cliente"]);
		$tipo_posto   = anti_injection(mb_strtolower($_GET["tipo_posto"]));

		$sqlValidaTipoPosto = "SELECT * FROM tbl_tipo_posto WHERE fabrica={$login_fabrica} AND tipo_posto=".$tipo_posto;
		$resValidaTipoPosto = pg_query($con, $sqlValidaTipoPosto);
		if (pg_num_rows($resValidaTipoPosto) > 0) {

			$tipo_posto = pg_fetch_result($resValidaTipoPosto, 0, 'tipo_posto');
			$tipo_posto_descricao = pg_fetch_result($resValidaTipoPosto, 0, 'descricao');
		}
	}

	if (in_array($login_fabrica, array(169,170,183))) {

		$extra = $_GET["extra"];
		$cep_orig = formatCEP($cep_orig);

		$sql_blacklist = "
			SELECT *
			FROM tbl_posto_cep_atendimento pca
			INNER JOIN tbl_fabrica f ON f.fabrica = pca.fabrica AND f.fabrica = $login_fabrica
			WHERE pca.fabrica = $login_fabrica
			AND pca.cep_inicial = '$cep_orig'
			AND pca.blacklist IS TRUE
			AND pca.posto = f.posto_fabrica;
		";
		$res_blacklist = pg_query($con, $sql_blacklist);

		if (pg_num_rows($res_blacklist) > 0) {
			$join_blacklist = "	JOIN tbl_posto_cep_atendimento ON tbl_posto_cep_atendimento.posto = tbl_posto.posto AND  tbl_posto_cep_atendimento.fabrica = {$login_fabrica} ";
			$cond_blacklist = " AND tbl_posto_cep_atendimento.cep_inicial = '{$cep_orig}' ";
		}
	}

    $linhas = implode(",",json_decode($linha,true));
	if(empty($linhas)) $linhas = $linha;

	if($login_fabrica == 1){
        $status_posto = filter_input(INPUT_GET,'status_posto');
        $codigo_posto = filter_input(INPUT_GET,'codigo_posto');
        $cep_posto    = filter_input(INPUT_GET,'cep_posto');

        $status = str_replace("\\", "", $status_posto);
        $status_posto = json_decode($status, true);

		$count = count($status_posto);
		$aspas = "";
		for ($i=0; $i < $count; $i++) {
			if($i > 0){
				$aspas .=($i < $count) ? "," : "";
				$aspas .= "'".$status_posto[$i]."'";
			}else{
				$aspas .= "'".$status_posto[$i]."'";
			}
		}
		$status_posto = $aspas;
	}

	if($login_fabrica == 74){
		$complemento_sql_columns = "tbl_posto_fabrica.contato_telefones AS telefone";

		$sql_linha = "SELECT codigo_linha FROM tbl_linha WHERE linha IN ($linhas) AND fabrica = $login_fabrica ";
		$res_linha = pg_query($con, $sql_linha);
		if(pg_num_rows($res_linha)> 0){
			$codigo_linha = pg_fetch_result($res_linha, 0, 'codigo_linha');
		}

		if($codigo_linha == "02"){
			$cond_divulga = " AND JSON_FIELD('divulgar_consumidor_callcenter_portateis', parametros_adicionais) = 't'";
		}
		if($codigo_linha == "01"){
			$cond_divulga = " AND JSON_FIELD('divulgar_consumidor_callcenter_fogo', parametros_adicionais) = 't'";
		}

	}else{
		$complemento_sql_columns = "tbl_posto_fabrica.contato_fone_comercial AS telefone";
	}

	$sql_columns = array(
		"tbl_posto.posto",
		"tbl_posto_fabrica.codigo_posto",
		"tbl_posto_fabrica.credenciamento",
		"tbl_posto_fabrica.tipo_atende",
		"UPPER(TRIM(tbl_posto.nome)) AS nome",
		"tbl_posto_fabrica.nome_fantasia",
		"UPPER(TRIM(tbl_posto_fabrica.contato_endereco)) AS endereco",
		"tbl_posto_fabrica.contato_numero AS numero",
		"UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro",
		"UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade",
		"tbl_posto_fabrica.contato_cidade",
		"tbl_posto_fabrica.contato_cep",
		"tbl_posto_fabrica.contato_estado AS estado",
		"tbl_posto_fabrica.contato_pais AS pais",
		"replace(tbl_posto_fabrica.parametros_adicionais,'*','') AS parametros_adicionais",
		"tbl_posto_fabrica.contato_cep AS cep",
		"LOWER(TRIM(tbl_posto_fabrica.contato_email)) AS email",
		$complemento_sql_columns
	);

	if ($login_fabrica == 151) {
		$sql_columns[] = 'tbl_posto_fabrica.contato_telefones AS tel';
		$sql_columns[] = 'tbl_posto_fabrica.contato_cel AS cel';
	}

	$sql_columns[] = "tbl_posto_fabrica.latitude AS lat";
	$sql_columns[] = "tbl_posto_fabrica.longitude AS lng";

	if($login_fabrica == 189){
		$sql_columns[] = " tbl_posto_fabrica.parametros_adicionais::JSON->>'tipo_cliente' as tipo_cliente ";
	}

	if ($callcenter) {
        $latlon = preg_replace("/\(|\)/", "", $latlonIndex);
        list($lat, $lng) = explode(",", $latlon);
        $lat = substr(trim($lat),0,7);
        $lng = substr(trim($lng),0,7);
        $sql_columns[] = "
            (111.045 * DEGREES(ACOS(COS(RADIANS({$lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$lng})) + SIN(RADIANS({$lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance
		";
	}

	$sql_join = array(
		"JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica",
	);

	$sql_where = array(
		"tbl_posto_fabrica.divulgar_consumidor IS TRUE",
		"AND tbl_posto.posto <> 6359",
	);

	if($login_fabrica == 74){
		$sql_where[] = "$cond_divulga";
	}

	if(in_array($login_fabrica, array(169,170,183))){
		$sql_join[]  = $join_blacklist;
		$sql_where[] = $cond_blacklist;
	}

	$sql_orderby = array(
		"tbl_posto_fabrica.contato_pais",
		"tbl_posto_fabrica.contato_estado",
		"tbl_posto_fabrica.contato_cidade",
		"tbl_posto_fabrica.contato_cep",
	);

	if ($callcenter) {
		$sql_orderby = array("distance");
	}

	$sql_limit = null;

	if ($login_fabrica == 1) {
        $quantidade_posto_cidade = 500;
    } else if(in_array($login_fabrica, array(169,170))){
    	if($extra == "true"){
    		$quantidade_posto_cidade = 5;
    	}else{
    		$quantidade_posto_cidade = 3;
    	}
    }else {
        $quantidade_posto_cidade = 5;
    }

	if ($callcenter) {
		$sql_limit = "LIMIT $quantidade_posto_cidade";
	}

	if($login_fabrica == 30 && $_GET['relatorio_posto'] != true){
		array_push($sql_where, "AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'");
	}

	if ($login_fabrica == 74) {
        $cidade = filter_input(INPUT_GET,'consumidor_cidade');
        $bairro = filter_input(INPUT_GET,'consumidor_bairro');
        $estado = filter_input(INPUT_GET,'consumidor_estado');

        array_push($sql_columns,"tbl_posto_fabrica_ibge.bairro AS bairro_atende");
        array_push($sql_join,"JOIN tbl_posto_fabrica_ibge    ON  tbl_posto_fabrica.fabrica       = tbl_posto_fabrica_ibge.fabrica
                                                            AND tbl_posto_fabrica.posto         = tbl_posto_fabrica_ibge.posto
                             JOIN tbl_cidade                ON  tbl_posto_fabrica_ibge.cod_ibge = tbl_cidade.cod_ibge
															AND ((tbl_cidade.nome = UPPER('{$cidade}') and tbl_cidade.estado = '$estado' and tbl_posto_fabrica_ibge.bairro isnull)
															OR (
																tbl_posto_fabrica_ibge.bairro ILIKE '%$bairro%'
																AND tbl_cidade.nome=UPPER('{$cidade}')

																)
															)  "
        );
	}

	if ($login_fabrica == 1 && $_GET['relatorio_posto'] == true) {
        $getLinhas = filter_input(INPUT_GET,'linha');
        $getLinhas =  json_decode($getLinhas,true);
        $linhas = implode(",",$getLinhas);
		array_push($sql_join, "JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto AND tbl_posto_linha.linha IN ($linhas)");
		array_push($sql_where, "AND tbl_posto_fabrica.categoria IN ('Autorizada', 'Locadora Autorizada') AND tbl_posto_fabrica.fabrica = $login_fabrica");
	}

	if (!in_array($login_fabrica, array(30,43)) && $_GET['relatorio_posto'] != true) {
		array_push($sql_where, "AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'");
	}


	if($login_fabrica == 189){
    	array_push($sql_join," JOIN tbl_tipo_posto USING(fabrica,tipo_posto)");
    	if ($tipo_posto_descricao == "Representante") {
			array_push($sql_where, " AND strpos(tbl_posto_fabrica.parametros_adicionais::JSON->>'tipo_cliente', '$tipo_cliente') > 0");
    	}

    	array_push($sql_where, " AND tbl_tipo_posto.tipo_posto = {$tipo_posto}");

		$sqlGetProduto = "SELECT produto FROM tbl_produto WHERE referencia = '{$produto}'";
        $resGetProduto = pg_query($con, $sqlGetProduto);
        $produto_id    = (pg_num_rows($resGetProduto) > 0) ? pg_fetch_result($resGetProduto, 0, 'produto') : false;

       	if ($produto_id) {
       		$in_postos = []; // PRODUTO PRA TESTE DEVEL #V0210685 6 MESES
       		$sqlBuscaPostos = "SELECT p.posto,
   							(111.045 * DEGREES(ACOS(COS(RADIANS(-22.217)) * COS(RADIANS(pf.latitude)) * COS(RADIANS(pf.longitude) - RADIANS(-49.950)) + SIN(RADIANS(-22.217)) * SIN(RADIANS(pf.latitude))))) AS distance
       						FROM tbl_pedido              AS p
       						INNER JOIN tbl_posto_fabrica AS pf ON pf.posto  = p.posto AND pf.fabrica = {$login_fabrica}
   							INNER JOIN tbl_pedido_item   AS pi ON pi.pedido = p.pedido 
   							INNER JOIN tbl_peca          AS pc ON pi.peca   = pc.peca AND pc.referencia = '{$produto}' AND pc.fabrica = {$login_fabrica}
							AND p.data::DATE BETWEEN CURRENT_DATE - '2 MONTHS'::interval AND CURRENT_DATE
							GROUP BY pf.latitude, pf.longitude, p.posto
							ORDER BY distance 
							LIMIT 5";
			$resBuscaPosto  = pg_query($con, $sqlBuscaPostos);
			$rowBuscaPosto  = pg_num_rows($resBuscaPosto);
			
			if ($rowBuscaPosto > 0) {
				for ($iBuscaPosto = 0; $iBuscaPosto < $rowBuscaPosto; $iBuscaPosto++) {
					$in_postos[] = pg_fetch_result($resBuscaPosto, $iBuscaPosto, 'posto');
				}
			}

			if (count($in_postos) > 0) {
				$in_postos = implode(',', $in_postos);
				array_push($sql_where, "AND tbl_posto_fabrica.posto IN ($in_postos)");
			}
        }
	}

	if (strlen($pais) > 0) {
		array_push($sql_where, "AND tbl_posto_fabrica.contato_pais = '$pais'");
	}

	if ($login_fabrica == 1 && empty($cidade) && empty($bairro) && empty($estado)) {
        $cidade = filter_input(INPUT_GET,'consumidor_cidade');
        $bairro = filter_input(INPUT_GET,'consumidor_bairro');
        $estado = filter_input(INPUT_GET,'consumidor_estado');
	}

	if (strlen($estado) > 0) {
		if($estado == "00"){
			$estado = "";
		}else if($estado == "BR-CO"){
			$estado = " IN ('GO','MS','MT','DF') ";
		}else if($estado == "BR-NE"){
			$estado = " IN('SE','AL','RN','MA','PE','PB','CE','PI','BA') ";
		}else if($estado == "BR-N"){
			$estado = " IN('TO','PA','AP','RR','AM','AC','RO') ";
		}else if($estado == "BR-SUL"){
			$estado = " IN('PR','SC','RS') ";
		}else if($estado == "SP-CAPITAL"){
			$estado = " IN('SP') ";
		}else if($estado == "SP-INTERIOR"){
			$estado = " IN('SP') ";
		}else {
			$estado = " = '$estado' ";
		}

	}

	if (strlen($cidade) > 0) {

		/*HD-4132482*/
		if ($login_fabrica == 1) {
			$buscar_cidade = true;
		} else {
			$buscar_cidade = false;
		}
		
		$cidade = acentos($cidade);
		$info   .= "&nbsp;&nbsp;<b>Cidade:</b> $cidade";
        if ($login_fabrica != 74) {
            array_push($sql_where, "AND TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN1') ~* TO_ASCII('^$cidade$', 'LATIN1')");
        }
	}

	if (strlen($linha) > 0 AND $login_fabrica != 117) {
		$sql = "SELECT nome
				FROM tbl_linha
				WHERE
					tbl_linha.fabrica = $login_fabrica
					AND tbl_linha.linha   IN ($linhas)
				ORDER BY
					tbl_linha.nome";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$aux_nome = pg_fetch_result($res, 0, 'nome');
			$info = "<br /><b>Linha: </b>$aux_nome\n";
		}
		if ($login_fabrica <> 189) {

			array_push($sql_where, "AND tbl_posto.posto IN (SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto) WHERE linha IN ($linhas) AND  fabrica = $login_fabrica)");
		} else {
			array_push($sql_join, "JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha IN ($linhas)");
		}

		if ( $login_fabrica == 24 ){
			array_push($sql_columns, "tbl_posto_linha.divulgar_consumidor");
			array_push($sql_join, "JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha IN ($linhas)");
			array_push($sql_where, "AND tbl_posto_linha.divulgar_consumidor IS TRUE");
		}
	}else{
		/*$sql_m =  "SELECT linha FROM tbl_macro_linha_fabrica
						WHERE fabrica = $login_fabrica
						AND macro_linha IN ($linhas);";
		$res_m = pg_query($con,$sql_m);

		if(pg_num_rows($res_m) > 0){
			for ($x=0; $x < pg_num_rows($res_m) ; $x++) {
				$linhas_macro = pg_fetch_result($res_m, $x, linha);
				$linhas_macro_all[] = 	$linhas_macro;
			}
			$linhas_macro_all = implode(',',$linhas_macro_all);
		}*/

		$sql = "SELECT nome
				FROM tbl_linha
				WHERE
					tbl_linha.fabrica = $login_fabrica
					AND tbl_linha.linha   in  ($linha)
				ORDER BY
					tbl_linha.nome";

		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			for ($y=0; $y <  pg_num_rows($res); $y++) {
				$aux_nome = pg_fetch_result($res, $y, 'nome');
				$info = "<br /><b>Linha: </b>$aux_nome\n";
			}

		}

		array_push($sql_where, "AND tbl_posto.posto IN (SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto) WHERE linha in ($linha) AND  fabrica = $login_fabrica)");

	}

	if($login_fabrica == 1){
        array_push($sql_columns," tbl_tipo_posto.descricao AS tipo_posto");
        array_push($sql_join,"LEFT JOIN tbl_tipo_posto USING(fabrica,tipo_posto)");
		if(strlen($status_posto) > 0){
			array_push($sql_where, "AND tbl_posto_fabrica.credenciamento IN ($status_posto)");
		}
	}

	if($login_fabrica == 30){
		array_push($sql_columns, "tbl_posto_fabrica.contato_fone_residencial AS telefone2");
		array_push($sql_join,"LEFT JOIN tbl_tipo_posto USING(fabrica,tipo_posto)");
		array_push($sql_columns, "tbl_posto_fabrica.contato_cel AS telefone3");
		array_push($sql_columns, "tbl_posto.cnpj AS cnpj_posto");
		array_push($sql_columns, "
			CASE
				WHEN tbl_tipo_posto.descricao = 'T�cnico'
				THEN 1
				ELSE 2
			END as ordenar_por_tipo
		");
	}

	if (in_array($login_fabrica, array(169,170)) && !empty($hd_chamado)) {
		$sqlPostoNotIn = "SELECT posto FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
		$resPostoNotIn = pg_query($con,$sqlPostoNotIn);
		if (pg_num_rows($resPostoNotIn) > 0) {
			$postoAntes = pg_fetch_result($resPostoNotIn, 0, posto);
			if (!empty($postoAntes)) {
				array_push($sql_where, "AND tbl_posto.posto != {$postoAntes}");
			}
		}
	}
	//die("teste");
	if ($login_fabrica == 30) {
		$sql = "SELECT * FROM (
					SELECT DISTINCT
						" . implode(", \n", $sql_columns) . "
					FROM tbl_posto
						" . implode(" \n", $sql_join) . "
					WHERE
						" . implode(" \n", $sql_where) . "
					ORDER BY
					" . implode(", \n", $sql_orderby)." $sql_limit 
				) dados
				ORDER BY dados.ordenar_por_tipo DESC";

	} else {
		$sql = "SELECT DISTINCT
				" . implode(", \n", $sql_columns) . "
			FROM tbl_posto
				" . implode(" \n", $sql_join) . "
			WHERE
				" . implode(" \n", $sql_where) . "
			ORDER BY
			" . implode(", \n", $sql_orderby)." $sql_limit ";
	}
} else if (isset($_GET['pais'])) {
	$pais = $_GET['pais'];
	$estado = $_GET['estado'];
	$cidade = $_GET['cidade'];
	$cidade = ($cidade == "t_cidades") ? "" : $cidade;
	$linha   = ($login_fabrica == 117) ? anti_injection($_GET['linha']) : "";

    $linhas = (is_array($linha)) ? implode(",",$linha) : $linha;

	if(isset($_GET['relatorio_posto']) && $_GET['relatorio_posto'] == "semCep"){
		$relatorio_posto = $_GET['relatorio_posto'];
		$estadoRelatorio = $_GET['estado'];
	}

	$fabrica = $login_fabrica;

	if($estado == "00"){
		$estado = "";
	}else if($estado == "BR-CO"){
		$estado = " IN ('GO','MS','MT','DF') ";
	}else if($estado == "BR-NE"){
		$estado = " IN('SE','AL','RN','MA','PE','PB','CE','PI','BA') ";
	}else if($estado == "BR-N"){
		$estado = " IN('TO','PA','AP','RR','AM','AC','RO') ";
	}else if($estado == "BR-SUL"){
		$estado = " IN('PR','SC','RS') ";
	}else if($estado == "SP-CAPITAL"){
		$estado = " IN('SP') ";
	}else if($estado == "SP-INTERIOR"){
		$estado = " IN('SP') ";
	}else {
		$estado = " = '$estado' ";
	}

	$sql_fone_adicional = "";
	if($login_fabrica == 30){
		$sql_fone_adicional = 'tbl_posto_fabrica.contato_fone_residencial AS telefone2,
				   			   tbl_posto_fabrica.contato_cel AS telefone3,';

		$group_by_fone_adicional = ',tbl_posto_fabrica.contato_fone_residencial,
										tbl_posto_fabrica.contato_cel';
	}

	if ($login_fabrica == 151) {
		$campos_fones = 'tbl_posto_fabrica.contato_telefones AS tel, tbl_posto_fabrica.contato_cel AS cel,';
	}else{
		$campos_fones = '';
	}

	$sql = "SELECT  tbl_posto.posto AS posto,
				   UPPER(TRIM (tbl_posto.nome)) AS nome,
				   UPPER(TRIM(tbl_posto_fabrica.nome_fantasia)) AS nome_fantasia,
				   UPPER( TRIM (tbl_posto_fabrica.contato_endereco)) AS endereco,
				   tbl_posto_fabrica.contato_numero AS numero,
				   $campos_fones
				   LOWER( TRIM(tbl_posto_fabrica.contato_email)) AS email,
				   tbl_posto_fabrica.contato_fone_comercial AS telefone,
				   $sql_fone_adicional
				   UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro,
				   UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade,
				   tbl_posto_fabrica.contato_cep AS cep,
				   tbl_posto_fabrica.contato_estado AS estado,
				   tbl_posto_fabrica.latitude AS lat,
				   tbl_posto_fabrica.longitude AS lng
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica USING (posto)"; 

	if ($login_fabrica == 117 AND strlen($linha) > 0) {
		/*$sql_m =  "SELECT linha FROM tbl_macro_linha_fabrica
						WHERE fabrica = $login_fabrica
						AND macro_linha IN ($linhas);";
		$res_m = pg_query($con,$sql_m);

		if(pg_num_rows($res_m) > 0){
			for ($x=0; $x < pg_num_rows($res_m) ; $x++) {
				$linhas_macro = pg_fetch_result($res_m, $x, linha);
				$linhas_macro_all[] = 	$linhas_macro;
			}
			$linhas_macro_all = implode(',',$linhas_macro_all);
		}
		$sql .= " JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto AND tbl_posto_linha.linha in ($linhas_macro_all) ";*/
		$sql .= " JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto AND tbl_posto_linha.linha in ($linha) ";
	}

	$sql .= "WHERE credenciamento = 'CREDENCIADO'
			   AND tbl_posto.posto NOT IN(6359,20462)
			   AND tipo_posto <> 163
			   AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			   AND fabrica = $fabrica
			   AND tbl_posto.pais = '$pais' ";
	$sql .= ($estado == "")	? $estado : " AND tbl_posto_fabrica.contato_estado $estado";
	$sql.= ($cidade == "") ? $cidade : "AND UPPER(TO_ASCII(trim(tbl_posto_fabrica.contato_cidade), 'LATIN9')) = UPPER(TO_ASCII('".$cidade."', 'LATIN9')) ";

	if ($login_fabrica == 151) {
		$campos_fone = ',tbl_posto_fabrica.contato_telefones, tbl_posto_fabrica.contato_cel';
	}else{
		$campos_fone = '';
	}

	$sql.= "GROUP BY tbl_posto.posto,
						tbl_posto.nome,
						tbl_posto_fabrica.nome_fantasia,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero,
						tbl_posto_fabrica.contato_email,
						tbl_posto_fabrica.contato_fone_comercial,
						tbl_posto_fabrica.contato_bairro,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_cep,
						tbl_posto_fabrica.contato_estado,
						tbl_posto_fabrica.latitude,
						tbl_posto_fabrica.longitude,
						tbl_posto_fabrica.contato_estado,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_cep
						$campos_fone
						$group_by_fone_adicional
			 ORDER BY tbl_posto.nome";
}

function acentos ($string) {
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	$string = str_replace($array1, $array2, $string);


	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$string = str_replace($array1, $array2, $string);

	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	$string = str_replace($array1, $array2, $string);

	return $string;
}

function formatCEP($cepString){
	$cepString = str_replace("-", "", $cepString);
	$cepString = str_replace(".", "", $cepString);
	return $cepString;
}

function formatEndereco($end){
	$end = str_replace("R. ", "", $end);
	$end = str_replace(",,", "+", $end);
	$end = str_replace(", ,", "+", $end);
	$end = str_replace(" ", "+", $end);
	return $end;
}

//function getLatLonConsumidor($address,$cep){
function getLatLonConsumidor($logradouro = null, $bairro = null, $cidade, $estado, $pais, $cep = null){
	global $con;
	$oTcMaps = new TcMaps($login_fabrica, $con);

	try{
		$retorno = $oTcMaps->geocode($logradouro, null, $bairro, $cidade, $estado, $pais, $cep);
		return $retorno['latitude']."@".$retorno['longitude'];
	}catch(Exception $e){
		return false;
	}


	/*$geocode = file_get_contents("http://api2.telecontrol.com.br/maps/geocoding/query/{$address}");
	$r = json_decode($geocode, true);

	if (count($r[0]["features"]) == 0) {
		return false;
	} else {
		list($lng, $lat) = $r[0]["features"][0]["geometry"]["coordinates"];
		return $lat."@".$lng;
	}*/

}

function getPostoMaisProximo($arr = array()){
	$posto = 100000;
	$mais_proximo = "";
	foreach ($arr as $key => $value) {
		$parte = explode('|', $value);
		if($parte[0] < $posto){
			$posto = $parte[0];
			$mais_proximo = $parte[1];
		}
	}
	return $mais_proximo;
}

if(!$consumidor == ""){
	$local = formatEndereco($consumidor);
}else{
	$local = formatCEP($cep);
}

$latlonIndex = str_replace("(", "", $latlonIndex);
$latlonIndex = str_replace(")", "", $latlonIndex);
$parte = explode(',', $latlonIndex);
// print_r($parte);
$from_lat = trim($parte[0]);
$from_lon = trim($parte[1]);
// echo "||".$from_lat." <> ".$from_lon;
if($relatorio_posto) {
	if($relatorio_posto == "semCep"){
		//$localRelatorio = $cidade.",".$estadoRelatorio.",Brasil";
		//$latLonConsumidor = getLatLonEnderecoSemCEP($localRelatorio, 1);
		$cidade = $_GET['consumidor_cidade'];
		$estadoRelatorio = $_GET['consumidor_estado'];

		$latLonConsumidor = getLatLonConsumidor(null, null, $cidade, $estadoRelatorio, "Brasil");
	} else {
        $cep = (empty($cep_orig)) ? $cep_posto : $cep_orig;

        try {
            $soapClient = new SoapClient("https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl");
            $address = $soapClient->consultaCEP(array('cep'=>$cep));
            if (is_object($address)) {
                $cidade = $address->return->cidade;
                $estado = $address->return->uf;
                $logradouro = $address->return->end;
                $bairro = $address->return->bairro;

                $cidade = str_replace("'", "", $cidade);
                $bairro = str_replace("'","",$bairro);
            } else {
                throw new Exception("Error Processing Request");
            }
        } catch (Exception $e) {
            $sqlCep = "SELECT * FROM tbl_cep WHERE cep = '".formatCEP($cep)."'";
            $resCep = pg_query($con,$sqlCep);
            //echo pg_num_rows($resCep)."\n";
            if(pg_num_rows($resCep) > 0){
                $cidade = pg_fetch_result($resCep,0,'cidade');
                $estado = pg_fetch_result($resCep,0,'estado');
                $bairro = pg_fetch_result($resCep,0,'bairro');
                $logradouro = pg_fetch_result($resCep,0,'logradouro');
                $cidade = str_replace("'", "", $cidade);
                $bairro = str_replace("'","",$bairro);
				$estado = utf8_decode($estado);
				$cidade = utf8_decode($cidade);
            } else {
                echo "CEP NAO ENCONTRADO";
            }
        }

		if (in_array($login_fabrica, [1])) {
			$latLonConsumidor = getLatLonConsumidor($logradouro, $bairro, $cidade, $estado, 'Brasil', $cep);
		} else {
			$latLonConsumidor = getLatLonConsumidor($logradouro, $bairro, $cidade, $estado, 'Brasil');
		}
	}
	$parte = explode('@', $latLonConsumidor);
	if (in_array($login_fabrica, [1])) {
		$from_lat = $parte[0];
		$from_lon = $parte[1];
	}
}

/* Calcula a distancia entre pontos */
function compute_distance($from_lat, $from_lon, $to_lat, $to_lon, $units = 'K'){
	$oTcMaps = new TcMaps($login_fabrica, $con);

	$response = $oTcMaps->route("{$from_lat},{$from_lon}", "{$to_lat},{$to_lon}");

	return array("cost" => $response["total_km"], "route" => $response["rota"]);
}
$res = pg_query($con, $sql);

if ($login_fabrica == 1) {

	/*HD-4132482*/
	if ($buscar_cidade === true) {

		$sql_cidade = "SELECT DISTINCT
				" . implode(", \n", $sql_columns) . "
			FROM tbl_posto
				" . implode(" \n", $sql_join) . "
			WHERE
				" . implode(" \n", $sql_where) . "
			ORDER BY
			" . implode(", \n", $sql_orderby)." LIMIT 20";

		$auxiliar   = pg_query($con, $sql_cidade);
		$res_cidade = pg_fetch_all($auxiliar);

		foreach ($res_cidade as $posto) {
			$not_in[] = "'".$posto['posto']."'";
		}

		$total      = pg_num_rows($auxiliar);
		$resto      = 20 - $total;
		$aux_res    = array();

		if ($resto > 0) {

			/*Pesquisa novamente porém sem a cidade, para chegar ao nº mínimo de postos*/
			unset($sql_where[3]);
			if(count($not_in) > 0) {
				array_push($sql_where, " AND tbl_posto_fabrica.posto NOT IN (". implode(", ", $not_in) .") ");
			}

			$sql = "SELECT DISTINCT
				" . implode(", \n", $sql_columns) . "
			FROM tbl_posto
				" . implode(" \n", $sql_join) . "
			WHERE
				" . implode(" \n", $sql_where) . "
			ORDER BY
			" . implode(", \n", $sql_orderby)." LIMIT $resto";

			$auxiliar = pg_query($con, $sql);
			$aux_res  = pg_fetch_all($auxiliar);
			$total2    = pg_num_rows($auxiliar);
		} 

		if ($total2 > 0) {
			if(is_array($res_cidade) and count($res_cidade) > 0) {
				$res = (object) array_merge($res_cidade, $aux_res);
			}else{
				$res = $aux_res;
			}
		} else if ($total > 0) {
			$res = $res_cidade;
		} else {
			$buscar_sem_cidade = true;
		}
		unset($sql_cidade, $auxiliar, $total, $auxiliar, $aux_res, $total2);
	} elseif(pg_num_rows($res) == 0) {
		$buscar_sem_cidade = true;
	}


	if ($buscar_sem_cidade === true) {
		
		unset($sql_where[3]);

		$sql = "SELECT DISTINCT
					" . implode(", \n", $sql_columns) . "
				FROM tbl_posto
					" . implode(" \n", $sql_join) . "
				WHERE
					" . implode(" \n", $sql_where) . "
				ORDER BY
				" . implode(", \n", $sql_orderby)." LIMIT 20";
		$res      = pg_query($con, $sql);
	}
}

if ($relatorio_posto == true) {
    if($login_fabrica == 1){
        $status_posto = $_GET['status_posto'];
        $status = str_replace("\\", "", $status_posto);
        $status_posto = json_decode($status, true);

        $count = count($status_posto);
        $aspas = "";
        for ($i=0; $i < $count; $i++) {
            if($i > 0){
                $aspas .=($i < $count) ? "," : "";
                $aspas .= "'".$status_posto[$i]."'";
            }else{
                $aspas .= "'".$status_posto[$i]."'";
            }
        }
        $status_posto = $aspas;

        if(strlen($status_posto) > 0){
            $cond_status_posto = " AND tbl_posto_fabrica.credenciamento IN ($status_posto)";
        }
    }

    $sqlRelatorio = "
        SELECT  COUNT(tbl_posto.posto) AS total_posto
        FROM    tbl_posto
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
        JOIN    tbl_posto_linha     ON  tbl_posto_linha.posto       = tbl_posto.posto
                                    AND tbl_posto_linha.linha       IN ($linhas)
        WHERE   tbl_posto_fabrica.divulgar_consumidor IS TRUE
        AND     tbl_posto.posto <> 6359
        AND     tbl_posto_fabrica.categoria IN ('Autorizada', 'Locadora Autorizada')
        AND     tbl_posto.posto IN (
                    SELECT  DISTINCT
                            posto
                    FROM    tbl_posto_fabrica
                    JOIN    tbl_posto_linha USING(posto)
                    WHERE   linha   IN($linhas)
                    AND     fabrica = $login_fabrica
                )
        AND     UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) = '".$_GET['consumidor_cidade']."'
        $cond_status_posto
        ";
    $resRelatorioTotal = pg_query($con, $sqlRelatorio);

    if (pg_num_rows($resRelatorioTotal) > 0) {
        $quantidade_posto_cidade = pg_fetch_result($resRelatorioTotal,0,total_posto);
        $relatorio_distancia_maxima = 0;

        if ($login_fabrica == 1) {
            // if ($quantidade_posto_cidade == 0 || $quantidade_posto_cidade < 500) { -- bloqueando resultados de sp
                $relatorio_distancia_maxima = 5000;
            // }
        } else {
            if ($quantidade_posto_cidade == 0 || $quantidade_posto_cidade < 5) {
                $relatorio_distancia_maxima = 5000;
            }
        }
        if ($login_fabrica == 1) {
            if ($quantidade_posto_cidade < 500) {
                $quantidade_posto_cidade = 500;
            }
        } else {
            if ($quantidade_posto_cidade < 5) {
                $quantidade_posto_cidade = 5;
            }
        }

    } else {
        if ($login_fabrica == 1) {
            $quantidade_posto_cidade = 500;
        } else {
            $quantidade_posto_cidade = 5;
        }
    }
}

$bubble_sort = array();
$bubble_data = array();
$distacia_sort = array();

if($res){

	$rotas = array();

	if ($login_fabrica == 1 && ($buscar_cidade === true or $buscar_sem_cidade === true)) {
		$resultAll = (object) $res;
	} else {
		$resultAll = pg_fetch_all($res);
	}

	// Regra para ordenar os posto por KM, mantendo sempre os tecnicos primeiro
	if ($login_fabrica == 30) {
		$array_ordenado = [];
		$array_ordenado_tecnico = [];
		$p = 0;
		foreach ($resultAll as $res_ordenar) {

			unset($xdistacia_cliente);
			
			if(strlen($res_ordenar['lat']) > 0 and strlen($res_ordenar['lng']) > 0 && $callcenter) {
				$xdistacia_cliente = compute_distance($from_lat, $from_lon, $res_ordenar['lat'], $res_ordenar['lng']);				
				$rotas[$res_ordenar['posto']] = $xdistacia_cliente["route"];
				$xdistacia_cliente = $xdistacia_cliente["cost"];
			}

			if ($res_ordenar['ordenar_por_tipo'] == 1) {
				$array_ordenado_tecnico[$p] = $res_ordenar;
				$array_ordenado_tecnico[$p]['km'] = $xdistacia_cliente;
			} else {
				$array_ordenado[$p] = $res_ordenar;
				$array_ordenado[$p]['km'] = $xdistacia_cliente; 
			}

			$p++;
		}
		
		function cmp($a,$b) {
			return $a['km'] > $b['km'];
		}

		unset($resultAll);

		if (count($array_ordenado) > 0) {
			usort($array_ordenado, 'cmp');

			foreach ($array_ordenado as $ar) {
				$resultAll[] = $ar;
			}
		}
		
		if (count($array_ordenado_tecnico) > 0) {
			usort($array_ordenado_tecnico, 'cmp');

			foreach ($array_ordenado_tecnico as $ar) {
				$resultAll[] = $ar;
			}
		}
	}

	foreach ($resultAll as $result) {

		//if ($login_fabrica == 1 && $buscar_cidade === true) {
		$result = (object) $result;
		//}

		$nomeTitle = $result->nome;
		if(strlen($result->nome) > 27){
			$result->nome = substr($result->nome, 0, 25)."...";
		}

		$endereco = "";
		if($result->endereco != ""){ $endereco .= $result->endereco; }
		if($result->numero != ""){ $endereco .= " ".$result->numero; }
		if($result->bairro != ""){ $endereco .= ", ".$result->bairro; }
		if($result->cidade != ""){ $endereco .= ", ".$result->cidade; }
		if($result->estado != ""){ $endereco .= ", ".$result->estado; }

		$endereco = str_replace("?", "", $endereco);
		$endereco = str_replace(".", "", $endereco);
		$endereco = str_replace("+", "", $endereco);
		$endereco = str_replace("-", "", $endereco);

		if(isset($relatorio_posto) && $relatorio_posto == true){
			$result->enderecoRelatorio = $result->endereco;
		}

		$enderecoTitle = $result->endereco." ".$result->numero;
		$endereco_original  = $result->endereco;
		if (strlen($result->endereco) > 27 && !in_array($login_fabrica, array(140))) {
			$result->endereco = substr($result->endereco, 0, 25)."...";
			$result->endereco = $result->endereco." ".$result->numero;
		} else {
			$result->endereco = $result->endereco." ".$result->numero;
		}

		unset($distacia_cliente);

		if(strlen($result->lat) > 0 and strlen($result->lng) > 0 && $callcenter) {
			$distacia_cliente = compute_distance($from_lat, $from_lon, $result->lat, $result->lng);
			$rotas[$result->posto] = $distacia_cliente["route"];
			$distacia_cliente = $distacia_cliente["cost"];
		}
		$fone  = "";   
        $fone2 = "";
        $fone3 = "";
        $contato_telefones = "";
        $contato_cel = "";
        
        if (in_array($login_fabrica, array(169,170))){
        	$sql_agenda = "
        			SELECT TO_CHAR(x.datas_bloqueio, 'DD/MM/YYYY') AS datas_bloqueio, x.descricao_bloqueio, x.tecnico_agenda_bloqueio
					FROM (
						SELECT
							generate_series(ab.data_inicio, ab.data_final, '1 day')::date AS datas_bloqueio,
							ab.descricao AS descricao_bloqueio,
							ab.tecnico_agenda_bloqueio
						FROM tbl_tecnico_agenda_bloqueio ab
						LEFT JOIN tbl_posto_fabrica pf ON pf.posto = ".$result->posto." AND pf.fabrica = $login_fabrica
						LEFT JOIN tbl_posto p ON p.posto = pf.posto
						WHERE ab.fabrica = $login_fabrica
						AND (
							ab.data_inicio::date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '2 MONTHS'
							OR ab.data_final::date BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '2 MONTHS'
						)
						AND (
							ab.posto = p.posto
							OR ab.estado = p.estado
							OR ab.cidade = (
								SELECT cidade
								FROM tbl_cidade
								WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais(p.cidade))
								AND UPPER(estado) = UPPER(p.estado)
						)
						OR (
							ab.posto IS NULL AND ab.estado IS NULL AND ab.cidade IS NULL)
						)
					)x
					WHERE x.datas_bloqueio >= current_date ORDER BY x.tecnico_agenda_bloqueio DESC";
			$res_agenda = pg_query($con, $sql_agenda);
        
			$array_bloqueio = array();
			$array_mostra_bloqueio = array();
			if (pg_num_rows($res_agenda) > 0){
				
				for ($z=0; $z < pg_num_rows($res_agenda); $z++) { 
					$datas_bloqueio = pg_fetch_result($res_agenda, $z, 'datas_bloqueio');
					$descricao_bloqueio = pg_fetch_result($res_agenda, $z, 'descricao_bloqueio');
					
					$array_bloqueio[$datas_bloqueio][] = $descricao_bloqueio;
				}
				ksort($array_bloqueio);
				$contador = 0;
				foreach ($array_bloqueio as $key => $value) {
					if ($contador <= 6){
						$array_mostra_bloqueio[] = array("data" => $key, "descricao" => $value[0]);
						#$array_mostra_bloqueio[] = $key.' '.utf8_decode($value[0]);
					}
					$contador++;
				}
				#$mostra_data_bloqueio = implode("<br/>", $array_mostra_bloqueio);
			}
        }
        
        if ($login_fabrica == 151) {
			if($result->tel != "") {
            	$contato_telefones = $result->tel;	
 			}
 			if($result->cel != "") {
 				$contato_cel = $result->cel;
        	}
            $chars_replace = array('{','}','"');
            $contato_telefones = str_replace($chars_replace, "", $result->tel);

            $fones_latina = array();
            $fones_latina = explode(',', $contato_telefones);

			$fone = $result->telefone;
			$fone2 = (array_key_exists(1, $fones_latina) and !empty($fones_latina[1])) ? $fones_latina[1] : '&nbsp;';
			$fone3 = (array_key_exists(2, $fones_latina) and !empty($fones_latina[2])) ? $fones_latina[2] : '&nbsp;';

            /*if(strlen($fone)==0 and strlen($fones_latina[0])>0 ){
                $fone  = $fones_latina[0];   
            	$fone2 = $fones_latina[1];
            	$fone3 = $fones_latina[2];
        	}*/

        }
		if ($callcenter) {

			if(in_array($login_fabrica, array(152, 161))){
				$distancia_km = 300;
			} else if(in_array($login_fabrica, array(125, 171, 174, 175, 177))){
				$distancia_km = 5000;
			} else if($login_fabrica == 74){
				$distancia_km = 2000;
			} else if($login_fabrica == 30){
				$distancia_km = 200;
			}else if(in_array($login_fabrica, array(176,183,189,190))){
				$distancia_km = 3000000000;
			}else if($login_fabrica == 186){
				$distancia_km = 50;
			} else if(in_array($login_fabrica, array(169,170))){
				if($extra == "true"){
					$distancia_km = 200;
				}else{
					$distancia_km = 20;
				}

			}else {
				$distancia_km = 100;
			}

			if(isset($relatorio_posto) && $relatorio_posto == true && $relatorio_distancia_maxima > 0){
				$distancia_km = $relatorio_distancia_maxima;
			}

			/* Distância menor que 100km */

			if($distacia_cliente != "" and $distacia_cliente < $distancia_km){
				$bubble_sort[] = $distacia_cliente;

				if(!isset($relatorio_posto) || $relatorio_posto != true){
					$distacia_sort[] = $distacia_cliente."|".$result->lat.",".$result->lng;
				}

				$distancia_total = number_format($distacia_cliente, 2, '.', '');

				$unit = ($distancia_total >= 1) ? "KM" : "Metros";

				$enderecoPosto = "";
				$enderecoPosto = $result->endereco.", ".$result->bairro.", ".$result->cidade.", ".$result->estado;
				if(isset($relatorio_posto) && $relatorio_posto == true){
					if($result->lat == "" || $result->lng == ""){
						$latlng = getLatLonEnderecoSemCEP(ucwords(strtolower(acentos($result->endereco))).",".ucwords(strtolower($result->cidade)).",".$result->estado.",Brasil",2);
						$distacia_sort[] = $distacia_cliente."|".$latlng;
						$latlng = explode(",",$latlng);
						$result->lat = $latlng[0];
						$result->lng = $latlng[1];
					}else{
						$distacia_sort[] = $distacia_cliente."|".$result->lat.",".$result->lng;
					}

					$distancia_total = number_format($distacia_cliente, 2, '.', '');

					$enderecoPostoCEP = ucwords(strtolower($result->endereco))."+".ucwords(strtolower($result->cidade))."+".$result->estado."|Brasil";

					if(!empty($cep_orig) && empty($codigo_posto)){
						require_once '../classes/cep.php';

						try {
							$retorno = CEP::consulta($cep_orig);
							$retorno = array_map(utf8_encode, $retorno);
						} catch(Exception $e) {
							$retorno = array("error" => utf8_encode($e->getMessage()));
						}
						$retorno["cidade"] = strtr( utf8_decode($retorno["cidade"]), array ('à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
					      'ç' => 'c', 'è' =>
					      'e', 'é' => 'e', 'ê' => 'e',
					      'ì' => 'i', 'í' => 'i',
					      'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o','ö' => 'o',
					      'ù' => 'u', 'ú' => 'u', 'û' => 'u'
					    ));

					    $endereco_rota = "";

						if($retorno["end"] != ""){

							$enderecoExplode = "";
							$enderecoFormatado = "";
							$enderecoExplode = explode(" ",$retorno["end"]);

							for($cont=0;isset($enderecoExplode[$cont]); $cont++){
								if($cont > 0){
									$enderecoFormatado .= " ";
								}
								$enderecoFormatado .= strtr( utf8_decode($enderecoExplode[$cont]), array ('à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
							      'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ì' => 'i', 'í' => 'i',
							      'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o','ö' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u'
							    ));
							}
							$endereco_rota = utf8_decode($enderecoFormatado)."+".$retorno["cidade"]."+".$retorno["uf"]."|Brasil";
						}else{
							$endereco_rota = $cep_orig."+".$retorno["cidade"]."+".$retorno["uf"]."|Brasil";
						}
					} else {
                        require_once '../classes/cep.php';

                        try {
							$retorno = CEP::consulta($cep_posto);
							$retorno = array_map(utf8_encode, $retorno);
						} catch(Exception $e) {
							$retorno = array("error" => utf8_encode($e->getMessage()));
						}
						$retorno["cidade"] = strtr( utf8_decode($retorno["cidade"]), array ('à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
					      'ç' => 'c', 'è' =>
					      'e', 'é' => 'e', 'ê' => 'e',
					      'ì' => 'i', 'í' => 'i',
					      'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o','ö' => 'o',
					      'ù' => 'u', 'ú' => 'u', 'û' => 'u'
					    ));

					    $endereco_rota = "";

						if($retorno["end"] != ""){

							$enderecoExplode = "";
							$enderecoFormatado = "";
							$enderecoExplode = explode(" ",$retorno["end"]);

							for($cont=0;isset($enderecoExplode[$cont]); $cont++){
								if($cont > 0){
									$enderecoFormatado .= " ";
								}
								$enderecoFormatado .= strtr( utf8_decode($enderecoExplode[$cont]), array ('à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
							      'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ì' => 'i', 'í' => 'i',
							      'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o','ö' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u'
							    ));
							}
							$endereco_rota = utf8_decode($enderecoFormatado)."+".$retorno["cidade"]."+".$retorno["uf"]."|Brasil";
						} else {
							$endereco_rota = $cep_posto."+".$retorno["cidade"]."+".$retorno["uf"]."|Brasil";
						}
					}

					$dados_posto[] = $result;

					$table_color = "#FFFFFF";
					if($result->credenciamento != "CREDENCIADO"){
						$table_color = "#F78181";
					}else if($result->tipo_atende == "t"){
						$table_color = "#F3F781";
					}

					$cel_color = "";
					$kmConsumidorPosto = "";
					if($kmConsumidorPosto != "" && $kmConsumidorPosto > 30.0){
						$cel_color = "#F78181";
					}else if($result->credenciamento != "CREDENCIADO"){
						$cel_color = "#F3F781";
					}else if($result->tipo_atende == "t"){
						$cel_color = "#9FF781";
					}

					if($cep_orig != ""){
						$kmConsumidorPosto .= " KM";
						$rotaRelatorio = "<td nowrap style='background-color: $table_color' rel='rota' data-id='{$result->posto}'>
								<a href=\"#\" class='rota' >Rota</a>
							</td>";
					}else{
						$kmConsumidorPosto = "------";
						$rotaRelatorio = "<td></td>";
					}

					$relatorio_id_posto[] = array("idposto" => $result->posto,
							"lat" => $result->lat,
							"lng" => $result->lng,
							"credenciamento" => $result->credenciamento,
							"atendimento" => $result->tipo_atende,
						);


                    if ($login_fabrica == 1) {
                        $obs_posto  = $result->parametros_adicionais;
                        $tipo_posto = $result->tipo_posto;
                        $mostraTipoPostoIco = (!in_array($tipo_posto,array('5SA','5SB','5SC')))
                            ? "<td style='$bold background-color: $table_color' rel='codigo_posto'>&nbsp;</td>"
                            : "<td style='$bold background-color: $table_color' rel='codigo_posto'><img src='imagens/ico_posto5S.png' style='max-width:55px;' /></td>";
						$decode_obs = json_decode($obs_posto,true);
						$obs_posto_cadastrado = preg_replace('/\s+/',' ',$decode_obs['obs_posto_cadastrado']);
                        $bold = (strlen($obs_posto_cadastrado) > 0) ? "font-weight:bold;" : "";

						$posto_linha = $result->posto;

						$sqlLinha = " SELECT tbl_linha.nome
										FROM tbl_posto_linha
										JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto
										JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
										WHERE tbl_posto_linha.posto = $posto_linha
										AND tbl_posto_fabrica.fabrica = $login_fabrica
										AND tbl_linha.ativo IS TRUE
										/*AND tbl_linha.nome LIKE '%BD'*/
										";
						$resLinha = pg_query($con, $sqlLinha);

						if (pg_num_rows($resLinha) > 0) {
							$nome_linhas = pg_fetch_all($resLinha);
							$array = array();
							foreach ($nome_linhas as $value) {
								$array[] = $value['nome'];
							}
							$nome_linhas = implode(',', $array);
						}
						$linha_tabela = "<td nowrap style='background-color: $table_color' rel='linhas'>
								<span class='label label-info' id='tooltipee' data-toggle='popover' rel='popover' data-placement='top' data-trigger='hover' data-delay='500' title='Linhas Atendidas' data-content='$nome_linhas'>Linhas</span>
							</td>";
					} else {
						$linha_tabela = "<td nowrap></td>";
					}

					if ($relatorio_posto != "semCep") {
						$km = $distacia_cliente;
					}
					if ($relatorio_posto != "semCep") {
						$distancia_cliente = compute_distance($from_lat, $from_lon, $result->lat, $result->lng);
						$km = $distancia_cliente["cost"];
		

					}		

					$relatorio_table[] = "<tr id='$result->posto' class='posto' >
							<td nowrap style='$bold background-color: $table_color' rel='codigo_posto'>
								".$result->codigo_posto."
							</td>
							".$mostraTipoPostoIco."
							<td nowrap style='$bold background-color: $table_color' rel='nome_posto'>
								".$result->nome."
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='nome_posto'>
                                                                ".$result->nome_fantasia."
                                                        </td>
							<td nowrap style='$bold background-color: $table_color' rel='credenciamento'>
								".$result->credenciamento."
							</td>
							$linha_tabela;
							<td nowrap style='$bold background-color: $table_color; text-align: left;' rel='endereco'>
								<span title='".$enderecoTitle."'>".$result->endereco."</span>
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='bairro'>
								".$result->bairro."
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='cidade'>
								".$result->cidade."
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='estado'>
								".$result->estado."
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='cep'>
								".$result->cep."
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='email'>
								".$result->email."
							</td>
							<td nowrap style='$bold background-color: $table_color' rel='telefone'>
								".$result->telefone."
							</td>
							<td nowrap style='$bold background-color: $cel_color' align='center'  rel='km' >{$km}</td>
							<td  nowrap style='$bold background-color: $table_color' rel='localizacao' >
								<a href=\"javascript: localizar('$result->lat', '$result->lng', '$endereco')\" >Localizar</a>
							</td>
							".$rotaRelatorio."
							<td style='text-align: left; display:none;' rel='nome_posto'>
								<input type='hidden' name='lat' value='$result->lat' />
								<input type='hidden' name='lng' value='$result->lng' />
								<input type='hidden' name='distacia_cliente' value='$distacia_cliente' />
							</td>

							<td nowrap rel='observacao' style='display:none;'>
								". utf8_decode($obs_posto_cadastrado)."
							</td>
					</tr>";
				}

				if($login_fabrica == 74){
					$telefones = str_replace(array('{', '}', '"'),  "", $result->telefone);
					$telefones = str_replace(',',  "<br>", $telefones);

				}elseif($login_fabrica == 30){
					$telefones = $result->telefone;
					if($result->telefone2 != "") {
						$telefones .= "\n\n".$result->telefone2;
					}
					if($result->telefone3 != "") {
						$telefones .= "\n\n".$result->telefone3;
					}
				}else{
					$telefones = $result->telefone;
				}

				if(in_array($login_fabrica,array(30,74,156,183))){
					$endereco_posto = ",  \"$endereco_original\" ";
					$endereco_posto .= ", \"$result->numero\"  ";
					$endereco_posto .= ", \"$result->cidade\" ";
					$endereco_posto .= ", \"$result->estado\" ";
				}

				$html = "";

				if (in_array($login_fabrica, array(169,170))){
					if (!empty($array_mostra_bloqueio)){
						$color_red = "style='color: red;'";
					}else{
						$color_red = "";
					}
				}else{
					$color_red = "";
				}
				if ($login_fabrica == 140) {
					$html .= "<tr id='$result->posto' class='posto' >
						<td style='text-align: left;' rel='nome_posto'>
							<input type='hidden' name='lat' value='$result->lat' />
							<input type='hidden' name='lng' value='$result->lng' />
							<input type='hidden' name='distacia_cliente' value='$distacia_cliente' />

							<a href='#' onclick='window.opener.informacoesPosto($result->posto, \"$result->cidade\");self.close();' title='".$nomeTitle."'>".$result->nome."</a>


						</td>
						<td style='text-align: left;' rel='nome_posto'>
							".$result->nome_fantasia."
						</td>";

						if ($callcenter) {
							$html .= "<td style='text-align: left;' rel='endereco'>
								<span title='".$enderecoTitle."'>".utf8_decode(mb_strtoupper(utf8_encode($result->endereco),'UTF-8'))."</span>
								, ".utf8_decode(mb_strtoupper(utf8_encode($result->bairro),'UTF-8'))."
								, ".utf8_decode(mb_strtoupper(utf8_encode($result->cidade),'UTF-8'))."
								, ".$result->estado."
							</td>";
						} else {
							$html .= "<td style='text-align: left;' rel='endereco'>
								<span title='".$enderecoTitle."'>".$result->endereco."</span>
							</td>
							<td rel='bairro'>
								".$result->bairro."
							</td>
							<td rel='cidade'>
								".$result->cidade."
							</td>
							<td rel='estado'>
								".$result->estado."
							</td>";
						}

						$html .= "<td rel='cep'>
							".$result->cep."
						</td>
						<td rel='email'>
							".$result->email."
						</td>
						<td rel='telefone'>
							".$telefones."
						</td>
						<td>
							".number_format($distacia_cliente, 2, '.', '')."
						</td>
						<td>
							<a href=\"javascript: localizar('$result->lat', '$result->lng', '$endereco')\" >Localizar</a>
						</td>
						<td data-id='{$result->posto}' rel='rota' >
							<a href=\"javascript: rota($result->posto)\" >Rota</a>
						</td>
					</tr>";
				} else {
					$html .= "<tr id='$result->posto' class='posto'".$color_red.">
						<td style='text-align: left;' rel='nome_posto'>
							<input type='hidden' name='lat' value='$result->lat' />
							<input type='hidden' name='lng' value='$result->lng' />
							<input type='hidden' name='distacia_cliente' value='$distacia_cliente' />

							<a href='#' onclick='window.parent.informacoesPosto($result->posto, \"$result->cidade\" $endereco_posto);window.parent.Shadowbox.close();' title='".$nomeTitle."'>".$result->nome."</a>

						</td>
						<td style='text-align: left;' rel='nome_posto'>
							".$result->nome_fantasia."
						</td>";

						if($login_fabrica == 30) {
							$html .= "<td style='text-align: left;' rel='nome_posto'>
								".$result->cnpj_posto."
							</td>";
						}

					if ($callcenter) {
						$html .= "<td style='text-align: left;' rel='endereco'>
								<span title='".$enderecoTitle."'>".$result->endereco."</span>
								, ".$result->bairro."
								, ".$result->cidade."
								, ".$result->estado."
							</td>";
					} else {
						$html .= "<td style='text-align: left;' rel='endereco'>
								<span title='".$enderecoTitle."'>".$result->endereco."</span>
							</td>
							<td rel='bairro'>
								".$result->bairro."
							</td>
							<td rel='cidade'>
								".$result->cidade."
							</td>
							<td rel='estado'>
								".$result->estado."
							</td>";
					}

					if (!isset($_GET['km_format']) || (isset($_GET['km_format']) && $_GET['km_format'] !== 'false')) {
						$distacia_cliente = number_format($distacia_cliente, 2, ',', '.').' km';
					}

					$html .= "<td rel='cep'>
							".$result->cep."
						</td>
						<td rel='email'>
							".$result->email."
						</td>";

					if ($login_fabrica == 151) {
					$html .= "<td rel='telefone'>
							".$fone."
						</td>
						<td rel='telefone2'>
							".$fone2."
						</td>
						<td rel='telefone3'>
							".$fone3."
						</td>
						<td rel='celular'>
							".$contato_cel."
						</td>";
					} else {
						$html .= "<td rel='telefone' style='width: 15%;'>
							".$telefones."
						</td>";
					}

					$html .= "
						<td style='text-align: right;'>
							$distacia_cliente
						</td>
						<td>
							<a href=\"javascript: localizar('$result->lat', '$result->lng', '$endereco')\" >Localizar</a>
						</td>
						<td data-id='{$result->posto}' rel='rota' >
							<a href=\"javascript: rota($result->posto)\" >Rota</a>
						</td>";

					if (in_array($login_fabrica, array(169,170))){
						$html .= "<td>";
							foreach ($array_mostra_bloqueio as $key => $value) {
								$html .= "<p title='".$value['descricao']."'>".$value["data"].'</p>';
							}

						$html .="</td>";
					}

					$html .="</tr>";
				}

				$bubble_data[] = $html;
			}

		}else{

			$id_posto = ($result->lat == "" || $result->lng == "") ? $result->posto : "''";

			if($login_fabrica == 30){
				$telefones = $result->telefone;
				if($result->telefone2 != "") {
					$telefones .= "\n\n".$result->telefone2;
				}
				if($result->telefone3 != "") {
					$telefones .= "\n\n".$result->telefone3;
				}
			}else{
				$telefones = $result->telefone;
			}

			$html = "";

			$html .= "<tr id='$result->posto' class='posto' >
				<td style='text-align: left;' rel='nome_posto' title='".$nomeTitle."'>
					<input type='hidden' name='lat' value='$result->lat' />
					<input type='hidden' name='lng' value='$result->lng' />
					<input type='hidden' name='distacia_cliente' value='$distacia_cliente' title='".$nomeTitle."' />
					".$result->nome."
				</td>
				<td style='text-align: left;' rel='endereco'>
					".$result->nome_fantasia."
				</td>";

				if ($callcenter) {
					$html .= "<td style='text-align: left;' rel='endereco'>
							<span title='".$enderecoTitle."'>".$result->endereco."</span>
							, ".$result->bairro."
							, ".$result->cidade."
							, ".$result->estado."
						</td>";
				} else {
					$html .= "<td style='text-align: left;' rel='endereco'>
							<span title='".$enderecoTitle."'>".$result->endereco."</span>
						</td>
						<td rel='bairro'>
							".$result->bairro."
						</td>
						<td rel='cidade'>
							".$result->cidade."
						</td>
						<td rel='estado'>
							".$result->estado."
						</td>";
				}

			if ($login_fabrica == 151) {
				$html .= "<td rel='cep'>
						".$result->cep."
					</td>
					<td rel='email'>
						".$result->email."
					</td>
					<td rel='telefone'>
						".$fone."
					</td>
					<td rel='telefone2'>
						".$fone2."
					</td>
					<td rel='telefone3'>
						".$fone3."
					</td>
					<td rel='celular'>
						".$contato_cel."
					</td>
					<td>
						<a href='#' onclick=\"localizar('$result->lat', '$result->lng', '$endereco', $id_posto)\" >Localizar</a>
					</td>
				</tr>";
			}else{
				$html .= "<td rel='cep'>
						".$result->cep."
					</td>
					<td rel='email'>
						".$result->email."
					</td>
					<td rel='telefone'>
						".$telefones."
					</td>
					<td>
						<a href='#' onclick=\"localizar('$result->lat', '$result->lng', '$endereco', $id_posto)\" >Localizar</a>
					</td>
				</tr>";
			}
			$bubble_data[] = $html;

		}

	}
}else{
	//echo "Nenhum posto localizado!";

}

if($callcenter){

	/* Orderna os valores mantendo a associa��o entre valores e chaves */
	if($login_fabrica != 30) {
		asort($bubble_sort);
    }

	if(isset($_GET['relatorio_posto'])){
		asort($dados_posto);
// 		echo json_encode($dados_posto)."*";
	}
// var_dump($bubble_sort);
	/* Define a variavel com a latitude e longitude do posto mais proximo */
	$posto_mais_proximo = getPostoMaisProximo($distacia_sort);
	echo $posto_mais_proximo."*";
// exit;
	// Lista os Postos

	$cont = 1;

	// Tabela Excel

	$table_excel = "";

	$table_excel .= "
			<table>
				<thead>
					<tr>
						<td colspan='9' align='center'><strong>CIDADES MAIS PRÓXIMAS</strong></td>
					</tr>
					<tr>
						<th>
							Nome do Posto
						</th>
						<th>
							Nome Fantasia
						</th>
						<th>
							Endereço
						</th>
						<th>
							Bairro
						</th>
						<th>
							Cidade
						</th>
						<th>
							Estado
						</th>
						<th>
							CEP
						</th>
						<th>
							Email
						</th>
						<th>
							Telefone
						</th>
						<th>
							Distância
						</th>
					</tr>
				</thead>
				<tbody>";

	$aux_cont = 0;

	if($relatorio_posto == true){
		$latLngArray = array();
		foreach ($bubble_sort as $key => $value) {
			if($cont <= $quantidade_posto_cidade){
				if($aux_cont == 1){
					echo ",";
				}else{
					echo '{"posto":[';
					// echo '[';
				}

				$latLngArray[] = $dados_posto[$key]->lat.";".$dados_posto[$key]->lng.";".$dados_posto[$key]->nome;
				echo json_encode($dados_posto[$key]);

				if($aux_cont == 0){
					$aux_cont++;
				}
			}
			$cont++;
		}
		echo "]}*";
		$cont=1;
		foreach ($bubble_sort as $key => $value) {
			if($cont <= $quantidade_posto_cidade){
				echo $relatorio_table[$key];
			}
			$cont++;
		}
		echo "*";
		$cont=1;
		foreach ($bubble_sort as $key => $value) {
			if($cont <= $quantidade_posto_cidade){
				$relatorio_id[] = $relatorio_id_posto[$key];
			}
			$cont++;
		}
		echo json_encode($relatorio_id);
		echo "*";
		$cont=1;
		foreach ($bubble_sort as $key => $value) {
			if($cont <= $quantidade_posto_cidade){
				echo $bubble_data[$key];
			}
			$cont++;
		}
	}

	foreach ($bubble_sort as $key => $value) {
		if($callcenter == "true"){
			//Traz os 5 postos mais proximos
				if($cont <= 5){
					echo $bubble_data[$key];
					if (in_array($login_fabrica, array(169,170,176,189,190)) && $extra == "true") {
						break;
					}
					$table_excel .= $bubble_data[$key];
				}
			$cont++;
		}else{
			if($relatorio_posto == true){
				if($cont <= $quantidade_posto_cidade){
					echo $relatorio_table[$key];
					$table_excel .= $bubble_data[$key];
				}
				$cont++;
			}else{
				echo $bubble_data[$key];
			}
		}
	}

	$table_excel .= "
				</tbody>
			</table>";

	if(in_array($login_fabrica,array(52,74))){

		$table_excel = "";

		/* Começo */
		$table_excel .= "<table>";

		/* Monta Cabeçalho */
		$table_excel .= "
		<tr>
			<td colspan='10' align='center'><strong>CIDADES MAIS PRÓXIMAS</strong></td>
		</tr>
		<tr class='titulo_tabela' style='margin-top:20px;'>
			<td>Nome do Posto</td>
			<td>Nome Fantasia</td>
			<td>Endereço</td>
			<td>Bairro</td>
			<td>Cidade</td>
			<td>Estado</td>
			<td>CEP</td>
			<td>Email</td>
			<td>Fone</td>";
        $table_excel .= ($login_fabrica == 52) ? "<td>KM</td>": "";

		$table_excel .=  "</tr>
		";

		$consumidor_estado = $_GET['consumidor_estado'];
		$consumidor_cidade = $_GET['consumidor_cidade'];
		$consumidor_bairro = $_GET['consumidor_bairro'];

        $join = "AND tbl_cidade.nome=UPPER('{$consumidor_cidade}')";

		if ($login_fabrica == 74) {
            $join = " AND ((tbl_cidade.nome=UPPER('{$consumidor_cidade}') and tbl_posto_fabrica_ibge.bairro isnull)
                        OR (
                            tbl_posto_fabrica_ibge.bairro ILIKE '%$consumidor_bairro%'
                            AND tbl_cidade.nome=UPPER('{$consumidor_cidade}')

                            )
                        )
            ";
		}

		$sql = "SELECT DISTINCT
				tbl_posto_fabrica.codigo_posto,
				UPPER(TRIM(tbl_posto_fabrica.contato_endereco)) AS endereco,
				UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro,
				UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade,
				tbl_posto_fabrica.contato_estado AS estado,
				tbl_posto_fabrica.contato_cep AS cep,
				LOWER(TRIM(tbl_posto_fabrica.contato_email)) as email,
				tbl_posto_fabrica.nome_fantasia,
			   	tbl_posto_fabrica_ibge.km,
			   	tbl_posto.nome AS nome,
				tbl_posto_fabrica.contato_fone_comercial AS fone


				FROM
				tbl_posto_fabrica
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.fabrica=tbl_posto_fabrica_ibge.fabrica

				AND
				tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
				JOIN tbl_cidade ON tbl_posto_fabrica_ibge.cod_ibge = tbl_cidade.cod_ibge
                $join
				WHERE
				tbl_posto_fabrica.fabrica={$login_fabrica}
				AND tbl_cidade.estado=UPPER('{$consumidor_estado}')
				AND tbl_posto_fabrica.divulgar_consumidor IS TRUE


				ORDER BY
				tbl_posto_fabrica_ibge.km";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0){

			$i = 0;
			while ($resultado = pg_fetch_array($res)) {
				$bgcolor = $i % 2 == 0 ? "#eeeeff" : "#ffffff" ;

				$cep_atendidas = $resultado['cep'];
				$cep_atendidas = preg_replace('/(\d{2})(\d{3})(\d{3})/','$1$2-$3',$cep_atendidas);
				$table_excel .= "
				<tr bgcolor='{$bgcolor}' style='height:22px; font-size: 10px' >
					<td>
						<a class='km_distancia'
							km='{$resultado['km']}'
							cod_posto='{$resultado['codigo_posto']}'
							nome_posto='{$resultado['nome']}'
							email_posto='{$resultado['email']}'
							fone_posto= '{$resultado['fone']}'
							href'#'>{$resultado['nome']}
						</a>
					</td>

					<td>{$resultado['nome_fantasia']}</td>
					<td>{$resultado['endereco']}</td>
					<td>{$resultado['bairro']}</td>
					<td>{$resultado['cidade']}</td>
					<td align='center'>{$resultado['estado']}</td>
					<td>{$cep_atendidas}</td>
					<td align='right'>{$resultado['email']}</td>
					<td>{$resultado['fone']}</td>";
					if ($login_fabrica == 52) {
                        $table_excel .= "<td>
                            <a class='km_distancia'
                                km='{$resultado['km']}'
                                cod_posto='{$resultado['codigo_posto']}'
                                nome_posto='{$resultado['nome']}'
                                email_posto='{$resultado['email']}'
                                fone_posto= '{$resultado['fone']}'
                                href'#'>{$resultado['km']}
                            </a>
                        </td>
                        ";
					}
				$table_excel .= "</tr>
				";
				$i++;
			}
		}

		/* Fim */
		$table_excel .= "</table>";
	}

	/* Gera o Excel */
	$caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";
	$fp 	 = fopen ($caminho,"w");

	$fabricas_geram_excel = array(86, 81, 114);
	// Inicializa o arquivo XLS e Grava
	if(in_array($login_fabrica, $fabricas_geram_excel)){
		fwrite($fp, $table_excel);
		fclose($fp);
	}

	echo "*".json_encode($rotas);

	echo "*";
	echo json_encode($latLngArray);
	if ($relatorio_posto) {
		echo "*";
		echo $from_lat.",".$from_lon;
	}

} else {

	// Tabela Excel

	$table_excel = "";

	$table_excel .= "
			<table>
				<thead>
					<tr>
						<td colspan='9' align='center'><strong>CIDADES MAIS PRÓXIMAS</strong></td>
					</tr>
					<tr>
						<th>
							Nome do Posto
						</th>
						<th>
							Nome Fantasia
						</th>
						<th>
							Endereço
						</th>
						<th>
							Bairro
						</th>
						<th>
							Cidade
						</th>
						<th>
							Estado
						</th>
						<th>
							CEP
						</th>
						<th>
							Email
						</th>
						<th>
							Telefone
						</th>
					</tr>
				</thead>
				<tbody>";

	foreach ($bubble_data as $key => $value) {
		echo $value;
		$table_excel .= $value;
	}

	$table_excel .= "</table>";

	/* Gera o Excel */
	$caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";
	$fp 	 = fopen ($caminho,"w");

	$fabricas_geram_excel = array(86, 81, 114);

	// Inicializa o arquivo XLS e Grava
	if(in_array($login_fabrica, $fabricas_geram_excel)){
		fwrite($fp, $table_excel);
		fclose($fp);
	}

}


?>
