<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'cabecalho_pop_postos.php';
include 'funcoes.php'; ?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> <?=traduz('Pesquisa Postos')?>... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

 <style type="text/css">
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
table.tabela tr td{
font-family: verdana;
font: bold 11px "Arial";
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>
</head>

<body onblur="" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<br>

<?
if ($login_fabrica == 42) {
	$tipo_atendimento = $_GET["tipo_atendimento"];
	$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
	$res = pg_query($con, $sql);
	$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
	if ($entrega_tecnica == "t") {
		$join_et  = " JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto ";
		$where_et = " AND (tbl_posto_fabrica.entrega_tecnica IS TRUE OR tbl_tipo_posto.tipo_revenda IS TRUE) ";
	} else {
		$join_et  = " JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto ";
		$where_et = " AND tbl_posto_fabrica.entrega_tecnica IS FALSE AND tbl_tipo_posto.tipo_revenda IS FALSE ";
	}
}
$os = trim (strtolower ($_GET['os']));
if (in_array($login_fabrica, array(7,169,170)) ){
	$cond_credenciado = " AND credenciamento <> 'DESCREDENCIADO' ";
}
if (in_array($login_fabrica, array(169,170))){
	$get_linha = trim ($_GET['linha']);
	$campo_linha = ', array_agg(tbl_posto_linha.linha) AS linhas_posto_tab';
	$campos_os_dealer = ",JSON_FIELD('abre_os_dealer',tbl_posto_fabrica.parametros_adicionais) AS abre_os_dealer ";
	
	$join_linha = "
		JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto
		JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
			AND tbl_linha.fabrica = tbl_posto_fabrica.fabrica
	";
	$group_by_linha = " GROUP BY tbl_posto.posto, tbl_posto.nome, tbl_posto.cnpj, tbl_posto.cidade,
		tbl_posto.estado, tbl_posto_fabrica.contato_email, tbl_posto.fone, tbl_posto_fabrica.codigo_posto,
		tbl_posto_fabrica.credenciamento, tbl_posto_fabrica.parametros_adicionais, contato_fone_comercial , contato_fax";
	if (strlen(trim($get_linha)) > 0){
		$cond_linha = " AND tbl_posto_linha.linha = $get_linha ";
	}
}
if($login_fabrica == 189){
	/*if($tipo != 'representante'){
		$tipo_cliente = mb_strtolower($_GET["tipo_cliente"]);
		$campo_tipo_cliente = " tbl_posto_fabrica.parametros_adicionais::JSON->>'tipo_cliente' as tipo_cliente, ";
		$cond_tipo_cliente = " and tbl_posto_fabrica.parametros_adicionais::JSON->>'tipo_cliente' = '$tipo_cliente' ";
	}*/
}
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "nome" OR $tipo == 'representante') {
	$nome = trim (strtoupper($_GET["campo"]));
	//echo "<h4>Pesquisando por <b>nome do posto</b>: <i>$nome</i></h4>";
	echo "<p>";
	$condCodigo = "";
	if($login_fabrica == 189){
		if($tipo == "representante" && strlen($_GET["tipo_posto"]) == 0){
			$condTipo_posto = " and tbl_tipo_posto.descricao = 'Representante' ";
			$condCodigo        = " AND tbl_posto_fabrica.parametros_adicionais::JSON->>'codigo_representante' IS NOT NULL";
		} elseif (isset($_GET["tipo_posto"]) && strlen($_GET["tipo_posto"]) > 0) {
			$sqlValidaTipo = "SELECT * FROM tbl_tipo_posto WHERE fabrica={$login_fabrica} AND tipo_posto=".$_GET["tipo_posto"];
			$resValidaTipo = pg_query($con, $sqlValidaTipo);
			$tipo_atendimento_desc = pg_fetch_result($resValidaTipo, 0, 'descricao');
			/*if ($tipo_atendimento_desc == "Representante") {
				$condCodigo        = " AND tbl_posto_fabrica.parametros_adicionais::JSONB->>'codigo_representante' IS NOT NULL ";
			}*/
			$condTipo =  " AND tbl_posto_tipo_posto.tipo_posto = ".$_GET["tipo_posto"];
		}
		$jointipo_posto    = " JOIN tbl_posto_tipo_posto on tbl_posto_fabrica.posto = tbl_posto_tipo_posto.posto AND tbl_posto_tipo_posto.fabrica={$login_fabrica} {$condTipo}";
		$jointipo_posto   .= " JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica={$login_fabrica}";
		$camposTipo_posto = "tbl_tipo_posto.descricao as descricao_tipo_posto , JSON_FIELD('codigo_representante',tbl_posto_fabrica.parametros_adicionais) AS codigo_representante,";
	}

	if (in_array($login_fabrica, [183,190])){
		if (in_array($login_fabrica, [183])){

			$sql_tipo_posto = "SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND codigo = 'Aut'";
			$res_tipo_posto = pg_query($con, $sql_tipo_posto);

			$id_tipo_posto = pg_fetch_result($res_tipo_posto, 0, "tipo_posto");
			$condTipo_posto = " AND tbl_posto_fabrica.tipo_posto = {$id_tipo_posto} "; 
		}
		$campos_select = "tbl_posto_fabrica.contato_cidade AS cidade,
			tbl_posto_fabrica.contato_estado AS estado,
			tbl_posto_fabrica.contato_endereco AS endereco_posto,
			tbl_posto_fabrica.contato_numero AS numero_posto,
			tbl_posto_fabrica.contato_bairro AS bairro_posto,
		";


	}else{
		$campos_select = "tbl_posto.cidade,
			tbl_posto.estado,
			tbl_posto.endereco AS endereco_posto,
			tbl_posto.numero AS numero_posto,
			tbl_posto.bairro AS bairro_posto,";
	}

	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto.nome,
				tbl_posto.cnpj,
				$campos_select
				tbl_posto.fone,
				tbl_posto_fabrica.contato_email AS email,
				tbl_posto_fabrica.contato_fone_comercial,
				tbl_posto_fabrica.contato_fax,
				tbl_posto_fabrica.codigo_posto,
				$campo_tipo_cliente
				$camposTipo_posto
				tbl_posto_fabrica.credenciamento
				$campo_linha
				$campos_os_dealer
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			$join_et
			$join_linha
			$jointipo_posto
			WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			$cond_credenciado
			$cond_linha
			$cond_tipo_cliente
			$condTipo_posto
			$where_et
			$condCodigo
			$group_by_linha
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		echo "<h1>".traduz('Nenhum Resultado Encontrado')."</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}
if ($tipo == "instalador" AND in_array($login_fabrica, array(169,170))) {
	$nome = trim (strtoupper($_GET["campo"]));
	//echo "<h4>Pesquisando por <b>nome do posto</b>: <i>$nome</i></h4>";
	echo "<p>";
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.credenciamento $campos_os_dealer
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			$join_et
			WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%' OR tbl_posto_fabrica.codigo_posto ilike '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			$where_et
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>".traduz('Nenhum Resultado Encontrado')."</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}
if ($tipo == "codigo") {
	$codigo_posto = trim (strtoupper($_GET["campo"]));
	$codigo_posto = str_replace (".","",$codigo_posto);
	$codigo_posto = str_replace (",","",$codigo_posto);
	$codigo_posto = str_replace ("-","",$codigo_posto);
	$codigo_posto = str_replace ("/","",$codigo_posto);
	$condCodigo = "tbl_posto_fabrica.codigo_posto ilike '%$codigo_posto%'";
	if($login_fabrica == 189){
		if($tipo == "representante" && strlen($_GET["tipo_posto"]) == 0){
			$condTipo_posto = " and tbl_tipo_posto.descricao = 'Representante' ";
			$condCodigo        = "tbl_posto_fabrica.parametros_adicionais::JSON->>'codigo_representante' = '{$codigo_posto}'";
		} elseif (isset($_GET["tipo_posto"]) && strlen($_GET["tipo_posto"]) > 0) {
			
			$sqlValidaTipo = "SELECT * FROM tbl_tipo_posto WHERE fabrica={$login_fabrica} AND tipo_posto=".$_GET["tipo_posto"];
			$resValidaTipo = pg_query($con, $sqlValidaTipo);
			$tipo_atendimento_desc = pg_fetch_result($resValidaTipo, 0, 'descricao');
			if ($tipo_atendimento_desc == "Representante") {
				$condCodigo = "tbl_posto_fabrica.parametros_adicionais::JSON->>'codigo_representante' = '{$codigo_posto}'";
			}
			$condTipo =  " AND tbl_posto_tipo_posto.tipo_posto = ".$_GET["tipo_posto"];
		}
		$jointipo_posto    = " JOIN tbl_posto_tipo_posto on tbl_posto_fabrica.posto = tbl_posto_tipo_posto.posto AND tbl_posto_tipo_posto.fabrica={$login_fabrica} {$condTipo}";
		$jointipo_posto   .= " JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_tipo_posto.tipo_posto  AND tbl_tipo_posto.fabrica={$login_fabrica}";
		$camposTipo_posto  = "tbl_tipo_posto.descricao as descricao_tipo_posto , JSON_FIELD('codigo_representante',tbl_posto_fabrica.parametros_adicionais) AS codigo_representante,";
	}
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>código do posto</b>: <i>$codigo_posto</i></font>";
	echo "<p>";

	if (in_array($login_fabrica, [183,190])){
		$campos_select = "tbl_posto_fabrica.contato_cidade AS cidade,
			tbl_posto_fabrica.contato_estado AS estado,
			tbl_posto_fabrica.contato_endereco AS endereco_posto,
			tbl_posto_fabrica.contato_numero AS numero_posto,
			tbl_posto_fabrica.contato_bairro AS bairro_posto,
		";
	}else{
		$campos_select = "tbl_posto.cidade,
			tbl_posto.estado,
			tbl_posto.endereco AS endereco_posto,
			tbl_posto.numero AS numero_posto,
			tbl_posto.bairro AS bairro_posto,";
	}

	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto.nome,
				tbl_posto.cnpj,
				tbl_posto_fabrica.contato_email AS email,
				tbl_posto.fone,
				$campos_select
				tbl_posto_fabrica.contato_fone_comercial,
				tbl_posto_fabrica.contato_fax,
				tbl_posto_fabrica.codigo_posto,
				$campo_tipo_cliente
				$camposTipo_posto 
				tbl_posto_fabrica.credenciamento
				$campo_linha
				$campos_os_dealer
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			$join_et
			$join_linha
			$jointipo_posto
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo_posto%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			$cond_credenciado
			$cond_linha
			$cond_tipo_cliente 
			$where_et
			$group_by_linha
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>".traduz('Nenhum Resultado Encontrado')."</h1>";
		echo "<script type='text/javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

/*
if (pg_numrows($res) == 1) {
	$credenciamento = pg_result($res,0,credenciamento);
	if ( ($credenciamento <> 'DESCREDENCIADO' AND $login_fabrica == 3 AND $os == 't') OR $login_fabrica <> 3 OR $os <> 't') {
		echo "<script type='text/javascript'>";
		echo "nome.value   = '".str_replace("'", "\'", str_replace ('"','',trim(pg_result($res,0,nome))))."';";
		echo "codigo.value = '".trim(pg_result($res,0,codigo_posto))."';";
		if ($login_fabrica != 94) {
			echo "cidade.value = '".trim(pg_result($res,0,cidade))."';";
			echo "estado.value = '".trim(pg_result($res,0,estado))."';";
		}
		if ($_GET["proximo"] == "t") echo "proximo.focus();";
		echo "window.close() ;";
		echo "</script>";
		exit;
	}
}
*/
	echo "<script type='text/javascript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
	if($tipo=="nome")
		echo "<tr class='titulo_tabela'><td colspan='6'><font style='font-size:14px;'>".traduz('Pesquisando por')." <b>".traduz('Nome do Posto')."</b>: <i>$nome</font></td></tr>";
	if($tipo=="codigo")
		echo "<tr class='titulo_tabela'><td colspan='6'><font style='font-size:14px;'>".traduz('Pesquisando por')." <b>".traduz('Código do Posto')."</b>: $codigo_posto</font></td></tr>";
	echo "<tr class='titulo_coluna'>";
	if (in_array($login_fabrica, array(169,170))){
		echo "<td>".traduz('CNPJ')."</td><td>".traduz('Código')."</td><td>".traduz('Nome')."</td><td>".traduz('Cidade')."</td><td>".traduz('UF')."</td><td>".traduz('Datas não atendidas')."</td>";
	}elseif (in_array($login_fabrica, array(189))){
		echo "<td>".traduz('CNPJ')."</td><td>".traduz('Nome')."</td><td>".traduz("Tipo Posto")."</td><td>".traduz("Cidade")."</td><td>".traduz("UF")."</td>";
	}else{
		echo "<td>".traduz('CNPJ')."</td><td>".traduz('Nome')."</td><td>".traduz('Cidade')."</td><td>".traduz('UF')."</td>";
	}
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$credenciamento = pg_result($res,$i,credenciamento);
		$codigo_posto 	  = trim(pg_result($res,$i,codigo_posto));
		$posto      	  = trim(pg_result($res,$i,posto));
		$nome       	  = trim(pg_result($res,$i,nome));
		$cnpj       	  = trim(pg_result($res,$i,cnpj));
		$cidade     	  = trim(pg_result($res,$i,cidade));
		$estado     	  = trim(pg_result($res,$i,estado));
		$nome 			  = str_replace("'", "\'", $nome);
		$email			  = pg_result($res, $i, email);
		$fone_comercial   = pg_fetch_result($res, $i, contato_fone_comercial);
		$contato_fax 	  = pg_fetch_result($res, $i, contato_fax);
		$fone			  = pg_result($res, $i, fone);
		$nome 			  = str_replace ('"','',$nome);
		$cnpj 			  = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$instalador_nome  = $codigo_posto.'-'.$nome;
		$linhas_posto_tab = pg_result($res, $i, linhas_posto_tab);
		$descricao_tipo_posto = pg_fetch_result($res, $i, descricao_tipo_posto);

		if($login_fabrica == 189) {
			if ($descricao_tipo_posto == "Representante") {
				$codigo_posto = "";
				$codigo_posto = pg_fetch_result($res, $i, codigo_representante);
			}
		}

		$endereco_posto       	  = trim(pg_result($res,$i,endereco_posto));
		$endereco_posto 		  = str_replace("'", "\'", $endereco_posto);
		$numero_posto       	  = trim(pg_result($res,$i,numero_posto));
		$bairro_posto 			  = trim(pg_result($res,$i,bairro_posto));
		$bairro_posto 		  	  = str_replace("'", "\'", $bairro_posto);
		
		if (in_array($login_fabrica, array(169,170))){
			$abre_os_dealer = pg_fetch_result($res, $i, abre_os_dealer);
		
			if (in_array($login_fabrica, array(169,170))){
	        	$sql_agenda = "
	        			SELECT TO_CHAR(x.datas_bloqueio, 'DD/MM/YYYY') AS datas_bloqueio, x.descricao_bloqueio, x.tecnico_agenda_bloqueio
						FROM (
							SELECT
								generate_series(ab.data_inicio, ab.data_final, '1 day')::date AS datas_bloqueio,
								ab.descricao AS descricao_bloqueio,
								ab.tecnico_agenda_bloqueio
							FROM tbl_tecnico_agenda_bloqueio ab
							LEFT JOIN tbl_posto_fabrica pf ON pf.posto = $posto AND pf.fabrica = $login_fabrica
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
				if (pg_num_rows($res_agenda) > 0){
					
					$array_bloqueio = array();
					for ($z=0; $z < pg_num_rows($res_agenda); $z++) { 
						$datas_bloqueio = pg_fetch_result($res_agenda, $z, 'datas_bloqueio');
						$descricao_bloqueio = pg_fetch_result($res_agenda, $z, 'descricao_bloqueio');
						
						$array_bloqueio[$datas_bloqueio][] = $descricao_bloqueio;
					}
					ksort($array_bloqueio);
					$contador = 0;
					$array_mostra_bloqueio = array();
					foreach ($array_bloqueio as $key => $value) {
						if ($contador <= 6){
							$array_mostra_bloqueio[] = $key.' - '.utf8_decode($value[0]);
						}
						$contador++;
					}
					$mostra_data_bloqueio = implode("<br/>", $array_mostra_bloqueio);
				}else{
					$mostra_data_bloqueio = traduz("Sem bloqueio de agendamento");
				}
	        }
		}

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>\n";
		echo "<td>\n";
		echo "$cnpj\n";
		echo "</td>\n";
		if ( in_array($login_fabrica, array(169,170))){
			echo "<td>\n";
			echo "$codigo_posto\n";
			echo "</td>\n";
		}
		$cidade_estado = ($login_fabrica == 15) ? "cidade.value='$cidade';estado.value='$estado';" : null ;

		if (in_array($login_fabrica, [178,183,184,190,191,193,200])){
			if (empty($fone_comercial)){
				$fone_comercial = $contato_fax;
			}
			$fone_email = "fone.value= '$fone_comercial';email.value='$email';"; 

			if (in_array($login_fabrica, [183,190])){
				$fone_email .= "posto_tab.value = $posto; instalador_id.value = null; instalador_nome.value = null; posto_contato_endereco.value = '$endereco_posto';posto_contato_numero.value='$numero_posto'; posto_contato_bairro.value='$bairro_posto'; posto_contato_cidade.value='$cidade'; posto_contato_estado.value='$estado'; ";
			}
		}
		echo "<td>\n";
		if (($credenciamento <> 'DESCREDENCIADO' AND $login_fabrica == 3 AND $os == "t") OR $login_fabrica <> 3  OR $os <> "t") {
			if (in_array($login_fabrica, array(169,170))){
				if ($tipo == 'instalador'){
						echo "
							<a href=\"javascript:
								instalador_id.value='$posto';
								instalador_nome.value='$instalador_nome';
								if ('{$credenciamento}' == 'CREDENCIADO') {
									nome.value = '$nome';
									codigo.value = '$codigo_posto';
									fone.value='$fone';
									email.value='$email';
									linha.value='$linha';
									posto_tab.value='$posto';
								}else{
									alert('Instalador descredenciado, informe o posto autorizado para o atendimento');
								}
								window.opener.valida_garantia();
						";
				}else{
						echo "<a href=\"javascript:
							nome.value = '$nome';
							codigo.value = '$codigo_posto';
							fone.value='$fone';
							email.value='$email';
							linha.value='$linha';
							posto_tab.value='$posto';
							linhas_posto_tab.value='$linhas_posto_tab';
						";
				}
				if ($abre_os_dealer != 't'){
					echo "window.opener.valida_distancia($posto);";
				}
			}else{
				if($login_fabrica == 189){
					if($tipo == "representante"){
							echo "<a href=\"javascript:
								nome.value = '$nome';
								fone.value = '$fone' ;";
					} else {
						echo "<a href=\"javascript:
								codigo.value = '$codigo_posto';
								nome.value = '$nome';
								fone.value = '$fone' ;
								email.value='$email' ; ";
					}
				}else{
					echo "<a href=\"javascript: nome.value = '$nome'; codigo.value = '$codigo_posto'; $cidade_estado $fone_email ";
				}
            }
			if ($_GET["callback"] == "t") {
                echo "callback();";
            }
            if ($login_fabrica == 1 && $_GET['cad_pedido_black'] == 't') {
                echo "window.opener.busca_condicao('{$codigo_posto}'); ";
            }
			if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
			echo "window.opener.retorna_tipo_posto('$descricao_tipo_posto');  ;window.close() ; \" >";
		}
		echo "$nome";
		if ($credenciamento == 'DESCREDENCIADO' AND $login_fabrica == 3 AND $os == 't') {
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#FF0000'><b> (DESCREDENCIADO)</b><font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>";
		}
		echo "</font>\n";
		if ( ($credenciamento <> 'DESCREDENCIADO' AND $login_fabrica == 3 AND $os == "t") OR $login_fabrica <> 3 OR $os <> "t") {
			echo "</a>\n";
		}
		echo "</td>\n";
		if (in_array($login_fabrica, array(189))){
			echo "<td>$descricao_tipo_posto</td>";
		}
		echo "</td>\n";
		echo "<td>\n";
		echo "$cidade\n";
		echo "</td>\n";
		echo "<td>\n";
		echo "$estado\n";
		echo "</td>\n";
		if (in_array($login_fabrica, array(169,170))){
			echo "<td>$mostra_data_bloqueio</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>
