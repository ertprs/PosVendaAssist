<?php
echo '<meta http-equiv="X-UA-Compatible" content="IE=8" />';
include "dbconfig.php";
include "includes/dbconnect-inc.php";

//if($login_fabrica<>19)$admin_privilegios="gerencia";

include "autentica_admin.php";

include "funcoes.php";

if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

if (!function_exists('getPost')) {
	function getPost($param,$get_first = false) {
		if ($get_first) {
			if (isset($_GET[$param])  and $_GET[$param] != '') return anti_injection($_GET[$param]);
			if (isset($_POST[$param]) and $_POST[$param]!= '') return anti_injection($_POST[$param]);
		} else {
			if (isset($_POST[$param]) and $_POST[$param]!= '') return anti_injection($_POST[$param]);
			if (isset($_GET[$param])  and $_GET[$param] != '') return anti_injection($_GET[$param]);
		}
		return null;
	}
}

$msg = "";

$chk1  = getPost('chk_opt1');
$chk2  = getPost('chk_opt2');
$chk3  = getPost('chk_opt3');
$chk4  = getPost('chk_opt4');
$chk5  = getPost('chk_opt5');
$chk6  = getPost('chk_opt6');
$chk7  = getPost('chk_opt7');
$chk8  = getPost('chk_opt8');
$chk9  = getPost('chk_opt9');
$chk10 = getPost('chk_opt10');
$chk11 = getPost('chk_opt11');
$chk12 = getPost('chk_opt12');
$chk13 = getPost('chk_opt13');
$chk14 = getPost('chk_opt14');
$chk15 = getPost('chk_opt15');
$chk16 = getPost('chk_opt16');
$chk17 = getPost('chk_opt17');
$chk18 = getPost('chk_opt18');
$chk19 = getPost('chk_opt19');
$chk20 = getPost('chk_opt20');
$chk21 = getPost('chk_opt21');
$chk22 = getPost('chk_opt22');

$consumidor_revenda = getPost('consumidor_revenda');
$situacao			= getPost('situacao');
$dia_em_aberto		= getPost('dia_em_aberto');
$data_inicial		= getPost('data_inicial');
$data_final			= getPost('data_final');
$codigo_posto		= getPost('codigo_posto');
$nome_posto			= getPost('nome_posto');
$estado_posto		= getPost('estado_posto');
$produto_referencia	= getPost('produto_referencia');
$produto_nome		= getPost('produto_nome');
$servico_realizado	= getPost('servico_realizado');
$defeito			= getPost('defeito');
$defeito_reclamado	= getPost('defeito_reclamado');
$defeito_constatado	= getPost('defeito_constatado');
$familia			= getPost('familia');
$familia_serie		= getPost('familia_serie');
$numero_serie		= getPost('numero_serie');
$nome_consumidor	= getPost('nome_consumidor');
$cidade				= getPost('cidade');
$estado				= getPost('estado');
$numero_os			= getPost('numero_os');
$numero_nf			= getPost('numero_nf');
$checklist			= getPost('checklist');

//HD 227132
$tipo_atendimento	= getPost('tipo_atendimento');

# data da aprovação adicionado por Fábio a pedido da Honorato HD 3096 - 13/07/2007
$extrato_data_inicial = getPost('extrato_data_inicial');
$extrato_data_final	  = getPost('extrato_data_final');


if($login_fabrica==19) $layout_menu = "callcenter";
else                   $layout_menu = "gerencia";
$title = "RELAÇÃO DE ORDENS DE SERVIÇOS LANÇADAS";

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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border:1px solid #ACACAC;
	border-collapse: collapse;
}
table.tabela{
	empty-cells:show;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>
<!--[if lt IE 8]>
<style>
table.tabela2{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 2px;
}
</style>
<![endif]-->
<br />

<?
##### BOTÃO NOVA CONSULTA #####
echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
    echo "<tr class='table_line'>";
        echo "<td align='center' background='#D9E2EF'>";
            echo "<a href='defeito_os_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
        echo "</td>";
    echo "</tr>";
echo "</table>";

echo "<br />";

#### WHERE ############
$qtde_chk = 0;
##### OS Lançadas Hoje #####
if (strlen($chk1) > 0) {
    $monta_sql  .= " AND data_digitacao BETWEEN CURRENT_DATE AND CURRENT_TIMESTAMP"; // Não adianta colocar 23:59:59 pq não é possível digitar com hora futura...
    $monta_sql2 .= " AND tbl_os_excluida.data_digitacao BETWEEN CURRENT_DATE AND CURRENT_TIMESTAMP";
    $dt = 1;
    $msg .= " OS Lançadas Hoje ";
}

##### OS Lançadas Ontem #####
if (strlen($chk2) > 0) {
    $monta_sql  .= " AND data_digitacao BETWEEN DATE 'Yesterday' AND CURRENT_DATE - INTERVAL '1s' "; // Começo e final do dia anterior
    $monta_sql2 .= " AND tbl_os_excluida.data_digitacao BETWEEN DATE 'Yesterday' AND CURRENT_DATE - INTERVAL '1s' ";

	$dt = 1;
    if (strlen($msg) > 0) $msg .= " e ";
    $msg .= " OS Lançadas Ontem ";
}

##### OS Lançadas Nesta Semana #####
if (strlen($chk3) > 0) {
	// DATE_TRUNC('week',current_date) = SEGUNDA-FEIRA DA SEMANA ATUAL
    $monta_sql  .= " AND data_digitacao BETWEEN DATE_TRUNC('week',current_date) AND CURRENT_TIMESTAMP ";
    $monta_sql2 .= " AND tbl_os_excluida.data_digitacao BETWEEN  DATE_TRUNC('week',current_date) AND CURRENT_TIMESTAMP ";

    $dt = 1;

    if (strlen($msg) > 0) $msg .= " e ";
    $msg .= " OS Lançadas nesta Semana ";
}

##### OS Lançadas Neste Mês #####
if (strlen($chk4) > 0) {
    $monta_sql  .= " AND data_digitacao BETWEEN DATE_TRUNC('month',current_date) AND CURRENT_TIMESTAMP ";
    $monta_sql2 .= " AND tbl_os_excluida.data_digitacao BETWEEN  DATE_TRUNC('month',current_date) AND CURRENT_TIMESTAMP ";

    $dt = 1;

    if (strlen($msg) > 0) $msg .= " e ";
    $msg .= " OS Lançadas neste Mês ";
}

##### Situação da OS #####
if (strlen($chk5) > 0) {

	if (strtotime($data_inicial_aux." +".$dia_em_aberto." days") > strtotime($data_final_aux) ) {
		$msg_erro = 'A quantidade de dias não pode ultrapassar a data inicial';
	}
	if(empty($msg_erro)){
		if (is_numeric($dia_em_aberto)) {
			$monta_sql  .= " AND (tbl_os.data_digitacao < CURRENT_DATE - $dia_em_aberto AND tbl_os.data_fechamento IS NULL) ";
			$monta_sql2 .= " AND (tbl_os_excluida.data_digitacao < CURRENT_DATE - $dia_em_aberto AND tbl_os_excluida.data_fechamento IS NULL) ";

			$dt = 1;

			if (strlen($msg) > 0) $msg .= " e ";
			$msg .= " OS lançadas em aberto no período de <i>$dia_em_aberto</i> dias ";
		}
	}
	$qtde_chk++;
}

	##### Entre Datas #####
if (strlen($chk6) > 0) {
    if ((strlen($data_inicial) == 10) AND (strlen($data_final) == 10)) {
		list($dia, $mes, $ano) = explode('/',$data_inicial);
		if (!checkdate($mes, $dia, $ano)) $msg_erro = 'Data inválida!';
		$xdata_inicial = "$ano-$mes-$dia";

		list($dia, $mes, $ano) = explode('/', $data_final);
		if (!checkdate($mes, $dia, $ano)) $msg_erro = 'Data inválida!';
		$xdata_final	  = "$ano-$mes-$dia";


		if (strtotime($xdata_inicial) > strtotime($xdata_final) ) {
			$msg_erro = 'A data inicial não pode ser maior do que a data final';
		}

		if (strtotime($xdata_inicial.'+3 month') < strtotime($xdata_final) ) {
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
		}

		if (!$msg_erro) {
	        $monta_sql  .= " AND (tbl_os.data_digitacao BETWEEN '$xdata_inicial'  AND DATE '$xdata_final' + INTERVAL '1 day - 1s') ";
	        $monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN $xdata_inicial AND DATE $xdata_final + INTERVAL '1 day - 1s') ";

	        $dt = 1;
	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas entre os dias <i>$data_inicial</i> e <i>$data_final</i> ";
		}
    } else {
        $msg_erro .= " Data inválida!";
    }
	$qtde_chk++;
}

	##### OS aprovadas #####
	if (strlen($chk21) > 0) {
	    if ((strlen($extrato_data_inicial) == 10) AND (strlen($extrato_data_final) == 10)) {
			list($dia, $mes, $ano) = preg_split('/[\-|\/|.]/', $extrato_data_inicial);
			if (!checkdate($mes, $dia, $ano)) $msg_erro = 'Data inválida!';
			$x_extrato_data_inicial = "$ano-$mes-$dia";

			list($dia, $mes, $ano) = preg_split('/[\-|\/|.]/', $extrato_data_final);
			if (!checkdate($mes, $dia, $ano)) $msg_erro = 'Data inválida!';
			$x_extrato_data_final	  = "$ano-$mes-$dia";

			if (strtotime($x_extrato_data_inicial) > strtotime($x_extrato_data_final) ) {
				$msg_erro = 'A data inicial do extrato não pode ser maior do que a data final';
			}

			if (strtotime($x_extrato_data_inicial.'+3 month') < strtotime($x_extrato_data_final) ) {
				$msg_erro = 'O intervalo entre as datas do extrato não pode ser maior que 3 meses';
			}

	        $dt = 1;
			if(strlen($msg_erro)==0){
				$sqlX =    "SELECT extrato
						FROM    tbl_extrato
						WHERE   fabrica = $login_fabrica
						AND     aprovado BETWEEN '$x_extrato_data_inicial 00:00:00'  AND '$x_extrato_data_final 23:59:59'
						AND liberado IS NOT NULL";
				$resX = pg_query($con,$sqlX);
				$extratos = array();

				if (is_resource($resX)) $tmp_tot_extratos = pg_num_rows($resX);
				if ($tmp_tot_extratos) {
					$tmp_extratos = pg_fetch_all($resX);
					foreach ($tmp_extratos as $tmp_extrato) {
						$extratos[] = $tmp_extratos;
					}
				}

				if (count($extratos) > 0) {
					$extratos = implode(",",$extratos);
					$join_extrato .= " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato IN ($extratos)";
					#$monta_sql .= " AND tbl_os_extra.extrato IN ($extratos)";
				}

				if (strlen($msg) > 0) $msg .= " e ";
				$msg .= " Aprovadas entre os dias <i>$extrato_data_inicial</i> e <i>$extrato_data_final</i> ";
			}
	    }
	    $qtde_chk++;
	}


	##### Posto #####
	if (strlen($chk7) > 0) {

	    if (strlen($codigo_posto) > 0) {

	         $xsql = " AND ";

	        $monta_sql  .= " $xsql tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	        $monta_sql2 .= " $xsql tbl_os_excluida.codigo_posto   = '$codigo_posto' ";

	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas pelo posto <i>$nome_posto</i> ";

	    }

	    if (strlen($estado_posto) > 0) {
			$campo_estado = 'contato_estado';
			$campo_cidade = 'contato_cidade';
			switch ($estado_posto) {
				case 'centro-oeste':$cond_estado_cidade.= " AND $campo_estado IN('GO','MT','MS','DF') ";						 break;
				case 'nordeste':    $cond_estado_cidade.= " AND $campo_estado IN('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";break;
				case 'norte':		$cond_estado_cidade.= " AND $campo_estado IN('AC','AM','RR','RO','PA','AP','TO') ";			 break;
				case 'sudeste':		$cond_estado_cidade.= " AND $campo_estado IN('MG','ES','RJ','SP') ";						 break;
				case 'sul':			$cond_estado_cidade.= " AND $campo_estado IN('PR','SC','RS') ";								 break;
				case 'SP-capital':
	                $cond_estado_cidade .= " AND tbl_os.posto IN(
										SELECT posto
										  FROM tbl_posto_fabrica
										 WHERE fabrica        = $login_fabrica
										   AND $campo_estado = 'SP'
										   AND UPPER($campo_cidade)  ~ 'S.O PAULO|S.O BERNARDO DO CAMPO|S.O CAETANO DO SUL|SANTO ANDR.|GUARULHOS') ";
                	break;
				case 'SP-interior':
	                $cond_estado_cidade .= " AND tbl_os.posto IN(
										SELECT posto
										  FROM tbl_posto_fabrica
										 WHERE fabrica        = $login_fabrica
										   AND $campo_estado = 'SP'
										   AND UPPER($campo_cidade)  !~ 'S.O PAULO|S.O BERNARDO DO CAMPO|S.O CAETANO DO SUL|SANTO ANDR.|GUARULHOS') ";
                	break;
				default:			$cond_estado_cidade.= " AND $campo_estado = '$estado_posto' ";									 break;
            }

	        $monta_sql	.= $cond_estado_cidade;
	        $monta_sql2	.= $cond_estado_cidade;
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas pelo posto do estado <i>$estado_posto</i> ";

	    }

	    $qtde_chk++;

	}

	##### Produto #####
	if (strlen($chk8) > 0) {
	    $x_produto_referencia = str_replace(".", "", $produto_referencia);
	    $x_produto_referencia = str_replace("-", "", $x_produto_referencia);
	    $x_produto_referencia = str_replace("/", "", $x_produto_referencia);
	    $x_produto_referencia = str_replace(" ", "", $x_produto_referencia);

	    if ($x_produto_referencia) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql  .= " $xsql upper(referencia) = upper('$x_produto_referencia') ";
	        $monta_sql2 .= " $xsql upper(referencia) = upper('$x_produto_referencia') ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas contendo o produto <i>$produto_referencia</i> ";
	    }
	$qtde_chk++;
	}

	$monta_sql2 = "";
	##### Serviço Realizado #####
	if (strlen($chk9) > 0) {
	    if ($servico_realizado) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

			$monta_sql2 = " LEFT JOIN   tbl_os_produto ON tbl_os.os = tbl_os_produto.os
							LEFT JOIN   tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND         tbl_os.fabrica = tbl_os_item.fabrica_i ";

	        $monta_sql .= " $xsql servico_realizado = '$servico_realizado' ";
	        $dt = 1;

	        $sqlX =    "SELECT descricao
	                FROM    tbl_servico_realizado
	                WHERE   fabrica = $login_fabrica
	                AND     servico_realizado = $servico_realizado;";
	        $resX = pg_query($con,$sqlX);

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas contendo peças com serviço realizado <i>" . pg_fetch_result($resX,0,0) . "</i> ";
	    }
	$qtde_chk++;
	}

	##### Defeito em Peça #####
	if (strlen($chk10) > 0) {
	    if ($defeito) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql .= " $xsql defeito = '$defeito' ";
	        $dt = 1;

	        $sqlX =    "SELECT descricao
	                FROM    tbl_defeito
	                WHERE   fabrica = $login_fabrica
	                AND     defeito = $defeito;";
	        $resX = pg_query($con,$sqlX);

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas contendo peças com defeito <i>" . pg_fetch_result($resX,0,0) . "</i> ";
	    }
	$qtde_chk++;
	}

	##### Defeito Reclamado #####
	if (strlen($chk11) > 0) {
	    if ($defeito_reclamado) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql .= " $xsql defeito_reclamado = '$defeito_reclamado' ";
	        $dt = 1;

	        $sqlX =    "SELECT tbl_defeito_reclamado.descricao
	                FROM    tbl_defeito_reclamado
	                JOIN    tbl_familia USING (familia)
	                WHERE   tbl_familia.fabrica = $login_fabrica
	                AND     tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado;";
	        $resX = pg_query($con,$sqlX);

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas contendo produtos com defeito reclamado <i>" . pg_fetch_result($resX,0,0) . "</i> ";
	    }
	$qtde_chk++;
	}

	##### Defeito Constatado #####
	if (strlen($chk12) > 0) {
	    if ($defeito_constatado) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql .= "$xsql defeito_constatado = '$defeito_constatado' ";
	        $dt = 1;

	        $sqlX =    "SELECT descricao
	                FROM    tbl_defeito_constatado
	                WHERE   defeito_constatado = $defeito_constatado
	                AND     fabrica            = $login_fabrica;";
	        $resX = pg_query($con,$sqlX);

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas contendo produtos com defeito constatado <i>" . pg_fetch_result($resX,0,0) ."</i> ";
	    }
	$qtde_chk++;
	}

	##### Família #####
	if (strlen($chk13) > 0) {
	    if (strlen($familia) > 0) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql .= " $xsql tbl_produto.familia = $familia ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas contendo produtos com família ";
	    }
	$qtde_chk++;
	}

	##### Número Série #####
	if (strlen($chk14) > 0) {
	    if ($dt == 1) $xsql = " AND ";
	    else          $xsql = " AND ";

	    if (strlen($familia_serie) > 0) $x_numero_serie = $familia_serie;

	    $x_data = fnc_formata_data_pg($data_inicial);
	    if ($x_data != "'aaaa-mm-dd'") {
	        $x_data = str_replace("'", "", $x_data);
	        $x_data = str_replace("-", "", $x_data);
	        $x_numero_serie .= substr($x_data,2,2).substr($x_data,4,2).substr($x_data,6,2);
	    }

	    $x_numero_serie .= $numero_serie;

	    $monta_sql .= " $xsql upper(serie) LIKE upper('$x_numero_serie%') ";
	    $monta_sql2 .= " $xsql upper(serie) LIKE upper('$x_numero_serie%') ";
	    $dt = 1;

	    if (strlen($msg) > 0) $msg .= " e ";
	    $msg .= " OS lançadas contendo produtos com número de série <i>$numero_serie</i> ";
	$qtde_chk++;
	}

	##### Nome do Consumidor #####
	if (strlen($chk15) > 0) {
	    if ($nome_consumidor) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql  .= "$xsql upper(consumidor_nome) LIKE upper('$nome_consumidor%') ";
	        $monta_sql2 .= "$xsql upper(consumidor_nome) LIKE upper('$nome_consumidor%') ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas para o consumidor <i>$nome_consumidor</i>";
	$qtde_chk++;
	    }
	}

	##### CPF/CNPJ do Consumidor #####
	if (strlen($chk16) > 0) {
	    $x_cpf_consumidor = preg_replace('/\D/', '', $cpf_consumidor);

	    if ($cpf_consumidor) {
	        $monta_sql .= " AND consumidor_cpf LIKE '%$x_cpf_consumidor%' ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas para o consumidor com CPF/CNPJ <i>$cpf_consumidor</i>";
	    }
	$qtde_chk++;
	}

	##### Cidade #####
	if (strlen($chk17) > 0) {
	    if ($cidade) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql .= " $xsql upper(consumidor_cidade) = upper('$cidade') ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas para a cidade <i>$cidade</i>";
	    }
	$qtde_chk++;
	}

	##### Estado #####
	if (strlen($chk18) > 0) {
	    if ($estado) {
	        //NESSAS FABRICAS ELES BUSCAM PELO ENDEREÇO DO POSTO - HD 243867
	        if ($login_fabrica == 14 or $login_fabrica == 43 or $login_fabrica == 66) {
				$cons_posto   = 'p';
				$campo_estado = 'tbl_posto_fabrica.contato_estado';
				$campo_cidade = 'contato_cidade';
			} else {
				$cons_posto   = 'c';
				$campo_estado = 'tbl_os.consumidor_estado';
				$campo_cidade = 'consumidor_cidade';
			}
			if (strlen($estado)==2)  {
				$monta_sql .= " AND UPPER($campo_estado) = UPPER('$estado') ";
			} else {
				switch ($estado) {
					case 'centro-oeste':$monta_sql .= " AND $campo_estado IN('GO','MT','MS','DF') ";						 break;
					case 'nordeste':    $monta_sql .= " AND $campo_estado IN('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";break;
					case 'norte':		$monta_sql .= " AND $campo_estado IN('AC','AM','RR','RO','PA','AP','TO') ";			 break;
					case 'sudeste':		$monta_sql .= " AND $campo_estado IN('MG','ES','RJ','SP') ";						 break;
					case 'sul':			$monta_sql .= " AND $campo_estado IN('PR','SC','RS') ";								 break;
					case 'SP-capital':
						if ($cons_posto == 'p') {
			                $monta_sql .= " AND tbl_os.posto IN(
												SELECT posto
                                                  FROM tbl_posto_fabrica
                                                 WHERE fabrica        = $login_fabrica
                                                   AND $campo_estado = 'SP'
                                                   AND UPPER($campo_cidade)  ~ 'S.O PAULO|S.O BERNARDO DO CAMPO|S.O CAETANO DO SUL|SANTO ANDR.|GUARULHOS') ";
						} else {
			                $monta_sql .= " AND $campo_estado = 'SP'
			                                AND UPPER($campo_cidade) ~ 'S.O PAULO|S.O BERNARDO DO CAMPO|S.O CAETANO DO SUL|SANTO ANDR.|GUARULHOS'";
						}
	                	break;
					case 'SP-interior':
						if ($cons_posto == 'p') {
			                $monta_sql .= " AND tbl_os.posto IN(
												SELECT posto
                                                  FROM tbl_posto_fabrica
                                                 WHERE fabrica        = $login_fabrica
                                                   AND $campo_estado = 'SP'
                                                   AND UPPER($campo_cidade)  !~ 'S.O PAULO|S.O BERNARDO DO CAMPO|S.O CAETANO DO SUL|SANTO ANDR.|GUARULHOS') ";
						} else {
			                $monta_sql .= " AND $campo_estado = 'SP'
			                                AND UPPER($campo_cidade) !~ 'S.O PAULO|S.O BERNARDO DO CAMPO|S.O CAETANO DO SUL|SANTO ANDR.|GUARULHOS'";
						}
	                	break;
	            }
			}

	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas para o estado <i>$estado</i>";

	    }

	    $qtde_chk++;

	}

	##### Número da OS #####
	if (strlen($chk19) > 0) {
	    if ($numero_os) {
	        $monta_sql .= " AND (tbl_os.sua_os ~ '0{0,10}$numero_os' OR
	                             tbl_os.sua_os ~ '$numero_os-[0-4][0-9]') ";
	        $monta_sql2.= " AND (tbl_os.sua_os ~ '0{,10}$numero_os' OR
	                             tbl_os.sua_os ~ '$numero_os-[0-4][0-9]') ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas com nº <i>$numero_os</i>";
	    }
	$qtde_chk++;
	}

	##### Número da NF de Compra #####
	if (strlen($chk20) > 0) {
	    if ($numero_nf) {
	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";

	        $monta_sql .= " $xsql nota_fiscal = '$numero_nf' ";
	        $monta_sql2 .= " $xsql nota_fiscal = '$numero_nf' ";
	        $dt = 1;

	        if (strlen($msg) > 0) $msg .= " e ";
	        $msg .= " OS lançadas com Nº NF $numero_nf";
	    }
	$qtde_chk++;
	}

	if (strlen($tipo_atendimento) > 0) {//HD 227132

	    $xsql = " AND ";
	    $monta_sql .= " $xsql tbl_os.tipo_atendimento = $tipo_atendimento ";
	    $dt = 1;
	    $qtde_chk++;

	}

	##### Checklist #####
	if (strlen($chk22) > 0 && strlen($checklist) > 0) {


        $sqlX = "SELECT checklist_fabrica
                   FROM tbl_checklist_fabrica
                  WHERE fabrica = $login_fabrica
                    AND codigo = '$checklist';";
        $resX = pg_query($con,$sqlX);

        if (pg_num_rows($resX) > 0) {
        	$checklist_fabrica = [];
        	for ($i=0; $i < pg_num_rows($resX); $i++) { 
        		$checklist_fabrica[$i] = pg_fetch_result($resX, $i, 'checklist_fabrica');
        	}
        	$xchecklist_fabrica = implode(",", $checklist_fabrica);
        }

        if (count($checklist_fabrica) > 0) {

	        if ($dt == 1) $xsql = " AND ";
	        else          $xsql = " AND ";
			$monta_sql2 = " JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os AND tbl_os_defeito_reclamado_constatado.fabrica = tbl_os.fabrica";
	        $monta_sql .= " $xsql tbl_os_defeito_reclamado_constatado.checklist_fabrica IN($xchecklist_fabrica) ";
	        $dt = 1;
			$qtde_chk++;
        }

	}


	if (strlen($situacao) > 0) {
	    if ($dt == 1) $xsql = " AND ";
	    else          $xsql = " AND ";

	    $monta_sql  .= " $xsql data_fechamento $situacao ";
	    $monta_sql2 .= " $xsql data_fechamento $situacao ";
	    $dt = 1;
	$qtde_chk++;
	}

	if (strlen($consumidor_revenda) > 0 AND ($consumidor_revenda == "R" OR $consumidor_revenda == "C")) {
	    if ($dt == 1) $xsql = " AND ";
	    else          $xsql = " AND ";

	    $monta_sql .= " $xsql consumidor_revenda = '$consumidor_revenda' ";
	    $dt = 1;

	    if (strlen($msg) > 0) $msg .= " e ";
	    if($consumidor_revenda == "R") $msg .= " de revendas ";
	    if($consumidor_revenda == "C") $msg .= " de consumidores ";
	$qtde_chk++;
	}

	##### CONCATENA O SQL PADRÃO #####
	###  WHERE ###########

	$sql =    "SELECT      DISTINCT lpad(tbl_os.sua_os,10,'0')                         AS ordem      ,
	                            tbl_os.os                                                            ,
	                            tbl_os.sua_os                                                        ,
	                            to_char(tbl_os.data_digitacao,'DD/MM/YYYY')        AS data           ,
	                            to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura       ,
	                            to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento     ,
	                            to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI:SS') AS finalizada     ,
	                            to_char(tbl_os.data_nf,'DD/MM/YYYY')    AS data_nf        ,
	                            tbl_os.data_digitacao                              AS data_consulta  ,
	                            tbl_os.serie                                                         ,
				    tbl_os.posto                                                         ,
				    tbl_os.produto                                                       ,
	                            tbl_os.excluida                                                      ,
	                            tbl_os.consumidor_nome                                               ,
	                            tbl_os.data_fechamento                                               ,
	                            tbl_os.nota_fiscal                                                   ,
	                            tbl_os.nota_fiscal_saida                                             ,
	                            tbl_os.consumidor_cpf                                                ,
	                            tbl_os.consumidor_bairro                                             ,
	                            tbl_os.consumidor_cidade                                             ,
	                            tbl_os.consumidor_estado                                             ,
	                            tbl_os.consumidor_revenda                                            ,
	                            tbl_os.revenda_nome                                                  ,
	                            tbl_os.defeito_reclamado                                             ,
	                            tbl_os.defeito_reclamado_descricao                                   ,
	                            tbl_os.defeito_constatado                                            ,
	                            tbl_os.observacao                                                    ,
	                            tbl_os.qtde_produtos                                                 ,
	                            tbl_tipo_os.descricao                           AS tipo_os_descricao ,
	                            tbl_posto.cnpj                                     AS cnpj_posto     ,
	                            tbl_posto.nome                                     AS posto_nome     ,
	                            tbl_posto.cidade                                   AS posto_cidade   ,
	                            tbl_posto_fabrica.contato_estado                   AS estado         ,
	                            tbl_posto_fabrica.codigo_posto                     AS codigo_posto   ,
	                            tbl_produto.familia                                                  ,
	                            tbl_produto.referencia_pesquisa                    AS referencia     ,
	                            tbl_produto.descricao                                                ,
	                            '$login_login'                                     AS login_login    ,
	                            tbl_tipo_atendimento.descricao                     AS descricao_tipo_atendimento
	                INTO       TEMP tmp_os_$login_admin
			FROM       tbl_os
	                JOIN       tbl_produto ON tbl_os.produto=tbl_produto.produto AND tbl_os.fabrica=tbl_produto.fabrica_i
	                JOIN       tbl_posto            ON  tbl_os.posto              = tbl_posto.posto
	                JOIN       tbl_posto_fabrica    ON  tbl_posto.posto           = tbl_posto_fabrica.posto
	                                               AND tbl_posto_fabrica.fabrica  = $login_fabrica
	                LEFT JOIN   tbl_os_status        ON (tbl_os_status.os    = tbl_os.os AND (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
	                AND          tbl_os_status.fabrica_status=$login_fabrica)
	                $join_extrato
	                LEFT JOIN   tbl_tipo_os          ON tbl_tipo_os.tipo_os  = tbl_os.tipo_os
	                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento";

				$sql .= $monta_sql2;

				$sql .= " WHERE tbl_os.fabrica = $login_fabrica AND NOT(tbl_os.excluida = TRUE)";

				$sql .= $monta_sql;

			$sql .="; CREATE INDEX tmp_os_primeiro_os_$login_admin ON tmp_os_$login_admin(os);";

		$sql .= "SELECT * FROM tmp_os_$login_admin ";


		if (strlen($_GET['order']) > 0){
	    switch ($_GET['order']){
	        case 'os':         $order_by = ""; break;
	        case 'serie':      $order_by = "tmp_os_$login_admin.serie DESC,"; break;
	        case 'abertura':   $order_by = "tmp_os_$login_admin.data_abertura DESC,"; break;
	        case 'fechamento': $order_by = "tmp_os_$login_admin.data_fechamento DESC,"; break;
	        case 'consumidor': $order_by = "tmp_os_$login_admin.consumidor_nome ASC, tbl_posto.nome ASC,"; break;
	        case 'posto':      $order_by = "tmp_os_$login_admin.nome ASC,"; break;
	        case 'produto':    $order_by = "tmp_os_$login_admin.descricao ASC,"; break;
	    }

	    $sql .= " ORDER BY $order_by lpad (tmp_os_$login_admin.sua_os,10,'0') DESC, lpad (tmp_os_$login_admin.os::text,10,'0') DESC;";
	} else {
	    $sql .= " ORDER BY lpad (tmp_os_$login_admin.sua_os,10,'0') DESC, lpad (tmp_os_$login_admin.os::text,10,'0') DESC;";
	}

//	echo nl2br($sql); exit;

	if ($login_admin == '1375'){
// 	exit(nl2br($sql));
	}


	//HD 106972
	if ($qtde_chk < 3 and empty($chk1) and empty($chk2) and empty($chk3) and empty($chk4)) {
	    $msg_erro =  "Por favor, escolha pelo menos 3 filtros para a pesquisa.";
	}
if(empty($msg_erro)){
	$res = pg_exec($con, $sql);
	$total_os = pg_num_rows($res);
} else {
	echo "<center>$msg_erro</center>";
}

#if (pg_num_rows($res)>0) {
#	$num = pg_num_rows($res);
#	echo "<h1>Total: $num</h1>";
#}

if ($total_os == 0) {?>
<center>
<table width='700' height='50' align='center'>
	<tr class='menu_top'>
		<td align='center'>Nenhum resultado encontrado.</td>
	</tr>
</table>
</center><?
} else {
    flush();

    $vet_os = null;

   # while ($row = pg_fetch_assoc($res)) {
   #     $vet_os[] = trim($row['os']);
   # }

   # $vet = implode(',',$vet_os);

    if (pg_num_rows($res)>0) {
        $sql2 = "SELECT tbl_os_defeito_reclamado_constatado.os,
				COUNT(tbl_os_defeito_reclamado_constatado.defeito_constatado) as total
                   FROM tbl_os_defeito_reclamado_constatado
				   JOIN tmp_os_$login_admin USING(os)
					GROUP BY tbl_os_defeito_reclamado_constatado.os
                  ORDER BY total desc;";

        $result = pg_exec($con,$sql2);

        if (pg_num_rows($result) > 0) {

            $vet_constatado = trim(pg_result($result,0,total));

        } else {

            $vet_constatado = 0;

        }

    } else {

        $vet_constatado = 0;

    }



    flush();

    $data = date ("d/m/Y H:i:s");

    echo `rm /tmp/assist/relatorio-consulta-os-$login_fabrica.xls`;

    $fp = fopen ("/tmp/assist/relatorio-consulta-os-$login_fabrica.html","w");

	$xls_head = "<html>
<head>
<title>RELATÓRIO DE ORDENS DE SERVIÇO LANÇADAS - $data</title>
<meta name='Author' content='TELECONTROL NETWORKING LTDA'>
</head>
<body>
<table align='center' border='0' cellspacing='0' cellpadding='2' class='tabela'>
	<tr class='titulo_tabela'><td colspan='100%'>$msg</td></tr>

	<tr class='titulo_coluna'>
		<th>OS</th>\n";

    if ($login_fabrica == 19) {
        $xls_head.= "<th nowrap>NF Cliente</th>\n";
        $xls_head.= "<th nowrap>Data NF</th>\n";
        $xls_head.= "<th nowrap>NF Origem</th>\n";
        $xls_head.= "<th nowrap>Motivo</th>\n";
    }

	if ($login_fabrica == 43) {
        $xls_head.= "<th nowrap>NF</th>\n";
        $xls_head.= "<th nowrap>Data NF</th>\n";

    }

    $xls_head.= "<th>Série</th>\n";
    $xls_head.= "<th>Abertura</th>\n";

    if ($login_fabrica == 19) {
        $xls_head.= "<th>Digitação</th>\n";
    }

    $xls_head.= "<th>Fechamento</th>\n";

    if ($login_fabrica == 19) {
        $xls_head.= "<th>Tipo de Atendimento</th>\n";
    }

    $xls_head.= "<th>Consumidor</th>\n";
    $xls_head.= "<th>Bairro Consumidor</th>\n";
    $xls_head.= "<th>Cidade Consumidor</th>\n";
    $xls_head.= "<th>Estado Consumidor</th>\n";
    $xls_head.= "<th>Revenda</th>\n";

    if ($login_fabrica == 19) {
        $xls_head.= "<th>CNPJ Posto</th>\n";
    }

    $xls_head.= "<th>Posto</th>\n";

    if ($login_fabrica == 43) {
        $xls_head.= "<th nowrap>Referência</th>\n";
        $xls_head.= "<th nowrap>Produto</th>\n";
        $xls_head.= "<th nowrap>Defeito Reclamado</th>\n";
    } else {
        $xls_head.= "<th>Produto</th>\n";
    }

    if ($login_fabrica == 19) {
        $xls_head.= "<th nowrap>Qtde</th>\n";
    }

    if ($login_fabrica == 14) {
        $xls_head.= "<th>Posição</th>\n";
    }

    $xls_head.= "<th>Peça</th>\n";

    if ($login_fabrica == 14) {
        $xls_head.= "<th>Observação</th>\n";
    }

    /*HD: 123136*/

    if ($login_fabrica == 43) {
        $xls_head.= "<th>Cidade</th>\n";
        $xls_head.= "<th>Estado</th>\n";
    }

    for ($x = 0; $x < $vet_constatado; $x++) {
        $xls_head.= "<th nowrap>DEFEITO CONSTATADO ".($x+1)."</th>\n";
    }

    $xls_head.= "</tr>\n";
	fputs($fp, $xls_head);  // Grava o cabeçalho da tabela

	$sql3 = "SELECT tbl_os_produto.os,
					tbl_peca.referencia      AS referencia_peca,
					tbl_peca.descricao       AS descricao_peca,
					tbl_os_item.posicao
			INTO    TEMP tmp_os_peca_$login_admin
			FROM    tbl_os_produto
			JOIN    tbl_os_item USING (os_produto)
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_peca    USING (peca)
			JOIN    tmp_os_$login_admin on tmp_os_$login_admin.os = tbl_os_produto.os;

			CREATE INDEX tmp_os_peca_$login_admin_os ON tmp_os_peca_$login_admin(os);";
	#echo nl2br($sql3);
	$res3  = pg_exec($con, $sql3);


    for ($i = 0; $i < $total_os; $i++) {
        $os                     = trim(pg_result($res,$i,os));
        $data                   = trim(pg_result($res,$i,data));
        $abertura               = trim(pg_result($res,$i,abertura));
        $fechamento             = trim(pg_result($res,$i,fechamento));
        $finalizada             = trim(pg_result($res,$i,finalizada));
        $sua_os                 = trim(pg_result($res,$i,sua_os));
        $serie                  = trim(pg_result($res,$i,serie));
        $consumidor_nome        = trim(pg_result($res,$i,consumidor_nome));
        $consumidor_bairro      = trim(pg_result($res,$i,consumidor_bairro));
        $consumidor_cidade      = trim(pg_result($res,$i,consumidor_cidade));
        $posto_cidade           = trim(pg_result($res,$i,posto_cidade));
        $consumidor_estado      = trim(pg_result($res,$i,consumidor_estado));
        $contato_estado         = trim(pg_result($res,$i,estado));
        $revenda_nome           = trim(pg_result($res,$i,revenda_nome));
        $nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
        $nota_fiscal_saida      = trim(pg_result($res,$i,nota_fiscal_saida));
        $data_nf                = trim(pg_result($res,$i,data_nf));
        $posto_nome             = trim(pg_result($res,$i,posto_nome));
        $posto_codigo           = trim(pg_result($res,$i,codigo_posto));
        $posto_completo         = $posto_codigo . " - " . $posto_nome;
        $produto_nome           = trim(pg_result($res,$i,descricao));
        $produto_referencia     = trim(pg_result($res,$i,referencia));
        $data_fechamento        = trim(pg_result($res,$i,data_fechamento));
        $excluida               = trim(pg_result($res,$i,excluida));
        $defeito_constatado     = trim(pg_result($res,$i,defeito_constatado));
        $qtde_produtos          = trim(pg_result($res,$i,qtde_produtos));
        $cnpj_posto             = trim(pg_result($res,$i,cnpj_posto));
        $observacao             = trim(pg_result($res,$i,observacao));
        $tipo_os_descricao      = trim(pg_result($res,$i,tipo_os_descricao));
        $defeito_reclamado_desc = trim(pg_result($res,$i,defeito_reclamado_descricao));
        $descricao_tipo_atendimento = trim(pg_result($res,$i,descricao_tipo_atendimento));

        if ($login_fabrica == 19) $consumidor_nome = strtoupper($consumidor_nome);

        #if ($i == 0) $os_armazena = $os;
        #else $os_armazena = $os_armazena .','. $os;

        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

        if (strlen(trim($sua_os)) == 0) $sua_os = $os;

        $xls_row = "<tr bgcolor='$cor'>\n";

        if ($login_fabrica == 1)  {
			$xls_row.= "<TD nowrap>$codigo_posto$sua_os</TD>\n";
		}else {
			$xls_row.= "<TD nowrap><a href='os_press.php?os=$os' target='_blank'> $sua_os </a> </TD>\n";
		}



        if ($login_fabrica == 19) {
            $xls_row.= "<TD nowrap>$nota_fiscal</TD>\n";
            $xls_row.= "<TD nowrap>$data_nf</TD>\n";
            $xls_row.= "<TD nowrap>$nota_fiscal_saida</TD>\n";
            $xls_row.= "<TD nowrap>$tipo_os_descricao</TD>\n";
        }

		if ($login_fabrica == 43) {
			$xls_row.= "<TD nowrap>$nota_fiscal</TD>\n";
			$xls_row.= "<TD nowrap>$data_nf</TD>\n";
		}

        $xls_row.= "<td nowrap>$serie</td>\n";
        $xls_row.= "<td align='center' title='Data Abertura Sistema: $abertura'>$abertura</td>\n";

        if ($login_fabrica == 19) {
            $xls_row.= "<td align='center' title='Data Abertura Sistema: $data'>$data</td>\n";
        }

        $xls_row.= "<td align='center' title='Data Fechamento Sistema: $finalizada'>$fechamento</td>\n";

        if ($login_fabrica == 19) {
	        $xls_row.= "<td align='center'>$descricao_tipo_atendimento</td>\n";
	    }

        $xls_row.= "<td nowrap title='Consumidor: $consumidor_nome'>" . substr($consumidor_nome,0,15) . "</td>\n";
        $xls_row.= "<td nowrap title='Consumidor: $consumidor_bairro'>" . substr($consumidor_bairro,0,15) . "</td>\n";
        $xls_row.= "<td nowrap title='Consumidor: $consumidor_cidade'>" . substr($consumidor_cidade,0,15) . "</td>\n";
        $xls_row.= "<td nowrap title='Consumidor: $consumidor_estado'>" . substr($consumidor_estado,0,15) . "</td>\n";
        $xls_row.= "<td nowrap title='Consumidor: $revenda_nome'>" . substr($revenda_nome,0,15) . "</td>\n";

        if ($login_fabrica == 19) {
            $xls_row.= "<td nowrap title='CNPJ: $cnpj_posto'>$cnpj_posto&nbsp;</td>\n";
        }

        $xls_row.= "<td nowrap title='Código: $codigo_posto\nRazão Social: $posto_nome'>" . substr($posto_completo,0,30) . "</td>\n";

        if ($login_fabrica == 43) {
            $xls_row.= "<td nowrap>$produto_referencia</td>\n";
            $xls_row.= "<td nowrap>$produto_nome</td>\n";
            $xls_row.= "<td nowrap>$defeito_reclamado_desc</td>\n";
        } else {
            $xls_row.= "<td nowrap>$produto_referencia - $produto_nome</td>\n";
        }

        if ($login_fabrica == 19) {
            $xls_row.= "<TD nowrap>$qtde_produtos</TD>\n";
        }

#        if(strlen($defeito_constatado)>0){
#            $sql1 = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = $defeito_constatado";
#            $res1 = pg_exec($con,$sql1);
#            if (pg_numrows($res1)>0)
#                $defeito_constatado_descricao = trim(pg_fetch_result($res1,0,descricao));
#            else $defeito_constatado_descricao = '';
#        }

		$sql2 ="SELECT  tmp_os_peca_$login_admin.referencia_peca,
						tmp_os_peca_$login_admin.descricao_peca,
						tmp_os_peca_$login_admin.posicao
					FROM    tmp_os_peca_$login_admin
					WHERE   tmp_os_peca_$login_admin.os = $os
					ORDER BY tmp_os_peca_$login_admin.descricao_peca;";

        $res2  = pg_exec($con, $sql2);
        $total = pg_num_rows($res2);
        unset($pecas);

        if ($total > 0) {
            for ($t = 0; $t < $total; $t++) {
                $referencia_peca = trim(pg_result($res2,$t,referencia_peca));
                $descricao_peca  = trim(pg_result($res2,$t,descricao_peca));
                $posicao[]         = trim(pg_result($res2,$t,posicao));

                $pecas[] = $referencia_peca." - ".$descricao_peca;
            }
        } else {
            $referencia_peca = "";
            $descricao_peca  = "";
            $posicao         = "";
        }

        if ($login_fabrica == 14) {
            $xls_row.= "<td nowrap>".implode(",",$posicao)."</td>\n";
        }

        if (is_array($pecas)) {
        	$xls_row.= "<td nowrap>".implode("<br>",$pecas)."</td>\n";
        } else {
        	$xls_row.= "<td nowrap></td>\n";
        }
        

        if ($login_fabrica == 14) {
            $xls_row.= "<td nowrap>$observacao</td>\n";
        }

        /*HD: 123136*/

        if ($login_fabrica == 43) {
            $xls_row.= "<TD nowrap>$posto_cidade</TD>\n";
            $xls_row.= "<TD nowrap>$contato_estado</TD>\n";
        }

        if (strlen($defeito_constatado) > 0) {

            $sql_defeito = "select tbl_os_defeito_reclamado_constatado.defeito_constatado as defeito_constatado,
                        tbl_defeito_constatado.descricao as descricao
                        from tbl_os_defeito_reclamado_constatado
                        join tbl_defeito_constatado using (defeito_constatado)
                        where tbl_os_defeito_reclamado_constatado.os=$os";

			#echo nl2br($sql_defeito);

            $res_defeito = pg_exec($con,$sql_defeito);

            $num_defeito = pg_num_rows($res_defeito);

            for ($w = 0; $w < $vet_constatado; $w++) {
                if ($w < $num_defeito) {
                    $defeito_descricao = pg_result($res_defeito,$w,'descricao');
                    $xls_row.= "<td nowrap>$defeito_descricao</td>\n";
                } else {
                    $xls_row.= "<td nowrap>&nbsp;</td>\n";
                }

            }

        }
		else{
			$xls_row.= "<td nowrap>&nbsp;</td>\n";
			$xls_row.= "<td nowrap>&nbsp;</td>\n";
		}
        $xls_row.= "</tr>\n";
		fputs($fp, $xls_row);   //  grava o registro no arquivo.
    }

    $xls_foot = "</table>\n";
    $xls_foot.= "<br />";
	$xls_foot.= "<table height='20' align='center'><tr><td align='center' colspan='3'>Total de $total_os resultado(s) encontrado(s).</td></tr></table>";

    fputs($fp, $xls_foot . '</body></html>');
    fclose($fp);

    $data = date("Y-m-d").".".date("H-i-s");
	$arquivo_xls = "xls/relatorio-consulta-os-$login_fabrica.$data.xls";
	rename("/tmp/assist/relatorio-consulta-os-$login_fabrica.html", $arquivo_xls);
//echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_xls /tmp/assist/relatorio-consulta-os-$login_fabrica.html`;

//  Para mostrar o relatório em tela, só descomentar:
	if ($total_os <= 250) {
		readfile($arquivo_xls);
	}

?>
<div style='width:600px;margin:auto;text-align:centerfont-size:13px'>
	<p style='font-size:14pxfont-weight:bold'>RELATÓRIO POR OS</p>
	<input type='button' value='Download em Excel' onclick="window.location='xls/relatorio-consulta-os-<?=$login_fabrica .'.' . $data?>.xls'">

</div>
<?}

echo "<br />";

if (strlen($total_os) > 0) {
	$sql2 = "SELECT tmp_os_$login_admin.sua_os,
					tmp_os_$login_admin.os,
					tmp_os_$login_admin.serie,
					tmp_os_$login_admin.observacao,
					tbl_posto.nome      AS posto_nome,
					tbl_posto.estado,
					tbl_posto_fabrica.codigo_posto AS codigo_posto,
					tbl_produto.familia,
					tbl_produto.referencia_pesquisa   AS referencia,
					tbl_produto.descricao
			INTO    TEMP tmp_relatorio_peca_$login_admin
			FROM    tmp_os_$login_admin
			JOIN    tbl_posto            ON  tmp_os_$login_admin.posto = tbl_posto.posto
			JOIN    tbl_posto_fabrica    ON  tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_produto          USING (produto);

			CREATE  INDEX tmp_peca_$login_admin_os ON tmp_relatorio_peca_$login_admin(os);

			SELECT  tmp_relatorio_peca_$login_admin.sua_os,
					tmp_relatorio_peca_$login_admin.os,
					tmp_relatorio_peca_$login_admin.serie,
					tmp_relatorio_peca_$login_admin.observacao,
					tmp_relatorio_peca_$login_admin.posto_nome,
					tmp_relatorio_peca_$login_admin.estado,
					tmp_relatorio_peca_$login_admin.codigo_posto,
					tmp_relatorio_peca_$login_admin.familia,
					tmp_relatorio_peca_$login_admin.referencia,
					tmp_relatorio_peca_$login_admin.descricao
			FROM    tmp_relatorio_peca_$login_admin
			JOIN    tmp_os_peca_$login_admin USING(os)
			ORDER BY tmp_os_peca_$login_admin.descricao_peca;
			";

	#echo nl2br($sql2);

    $res2  = @pg_exec($con,$sql2);  // Ao criar temporária e Índice, se não tiver nenhum resultado, dá erro
    $total = @pg_num_rows($res2);
} else {
    $total = 0;
}

#if (pg_num_rows($res2)>0) {
#	$num = pg_num_rows($res2);
#	echo "<h1>Total peças: $num</h1>";
#}

if ($total == 0) {?>
<table width='700' height='50' align='center'>
	<tr class='menu_top'>
		<td align='center'>Nenhum resultado encontrado.</td>
	</tr>
</table>
<?
} else {
    flush();



    flush();

    $data = date ("d/m/Y H:i:s");

    echo `rm /tmp/assist/relatorio-consulta-os-peca-$login_fabrica.xls`;
    $fp = fopen ("/tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html","w");

    $xls_head = "<html>";
    $xls_head.= "<head>";
    $xls_head.= "<title>RELATÓRIO DE ORDENS DE SERVIÇO LANÇADAS - $data POR PEÇAS";
    $xls_head.= "</title>";
    $xls_head.= "<meta name='Author' content='TELECONTROL NETWORKING LTDA'>";
    $xls_head.= "</head>";
    $xls_head.= "<body>";

    $xls_head.= "<table align='center' border='1' cellspacing='1' cellpadding='1'>\n";

    $xls_head.= "<caption class='table_line' color='white' align='center'>\n" . $msg . "</caption>\n";

    $xls_head.= "<thead color:white' align='center'>".
				"<tr align='center' class='table_line'>\n".
				"<th>OS</th>\n".
				"<th>POSTO</th>\n";

    if (in_array($login_fabrica,array(14,43,66))) {
        $xls_head.= "<th>SÉRIE</th>\n";
    }

    if ($login_fabrica == 43) {
        $xls_head.= "<th nowrap>REFERÊNCIA</th>\n";
        $xls_head.= "<th nowrap>PRODUTO</th>\n";
        $xls_head.= "<th nowrap>DEFEITO RECLAMADO</th>\n";
    } else {
        $xls_head.= "<th>PRODUTO</th>\n";
    }

    if ($login_fabrica == '14') $xls_head.= "<th>POSIÇÃO</th>\n";

    $xls_head.= "<th>PEÇA</th>\n";

    if ($login_fabrica == 14) {
        $xls_head.= "<th>OBSERVAÇÃO</th>\n";
    }
    $xls_head.= "</tr></thead>\n";
	fputs($fp, $xls_head);  // Grava o cabeçalho

    for ($i = 0; $i < $total; $i++) {
        $os                     = trim(pg_result($res2,$i,os));
        $sua_os                 = trim(pg_result($res2,$i,sua_os));
        $posto_nome             = trim(pg_result($res2,$i,posto_nome));
        $posto_codigo           = trim(pg_result($res2,$i,codigo_posto));
        $posto_completo         = $posto_codigo . " - " . $posto_nome;
        $serie                  = trim(pg_result($res2,$i,serie));
        $produto_nome           = trim(pg_result($res2,$i,descricao));
        $produto_referencia     = trim(pg_result($res2,$i,referencia));
        $observacao             = trim(pg_result($res2,$i,observacao));

		#$xls_row = "";

        $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

        if (strlen(trim($sua_os)) == 0) $sua_os = $os;

        $xls_row = "<tr class='table_line' bgcolor='$cor;'>\n";

        if ($login_fabrica == 1) $xls_row.= "<TD nowrap>$codigo_posto$sua_os</TD>\n";
        else                     $xls_row.= "<TD nowrap>$sua_os</TD>\n";

        $xls_row.= "<td nowrap title='Código: $codigo_posto\nRazão Social: $posto_nome'  >" . substr($posto_completo,0,30) . "</td>\n";

        if ($login_fabrica == 14 or $login_fabrica == 66 or $login_fabrica == 43) {
            $xls_row.= "<td nowrap>$serie</td>\n";
        }

        if ($login_fabrica == 43) {
            $xls_row.= "<td nowrap>$produto_referencia</td>\n";
            $xls_row.= "<td nowrap>$produto_nome</td>\n";
            $xls_row.= "<td nowrap>$defeito_reclamado_desc</td>\n";
        } else {
            $xls_row.= "<td nowrap>$produto_referencia - $produto_nome</td>\n";
        }


        $sqlPecas = "SELECT tmp_os_peca_$login_admin.referencia_peca,
                            tmp_os_peca_$login_admin.descricao_peca,
                            tmp_os_peca_$login_admin.posicao
                    FROM    tmp_relatorio_peca_$login_admin
                    JOIN    tmp_os_peca_$login_admin USING(os)
                    WHERE   os = $os
              ORDER BY      tmp_os_peca_$login_admin.descricao_peca;
        ";
        $resPecas = pg_query($con,$sqlPecas);
        unset($pecasXls);
        unset($posicao);

        while ($result = pg_fetch_object($resPecas)) {
            $pecasXls[] = $result->referencia_peca." - ".$result->descricao_peca;
            $posicao[] = $result->posicao;
        }

        if ($login_fabrica == 14) {
            $xls_row.= "<td nowrap>".implode(", ",$posicao)."</td>\n";
        }

        $xls_row.= "<td nowrap>".implode("<br>",$pecasXls)."</td>\n";

        if ($login_fabrica == 14) {
            $xls_row.= "<td nowrap>$observacao</td>\n";
        }

        $xls_row.= "</tr>\n";

		fputs($fp, $xls_row);   // Grava o registro no arquivo
    }

    $xls_foot = "</table>\n";
    $xls_foot.= "<br />";
    $xls_foot.= "<table height='20' align='center'><tr class='menu_top'><td align='center' colspan='3'>Total de $total resultado(s) encontrado(s).</td></tr></table>";



    fputs($fp, $xls_foot . '</body></html>');  //  Finaliza a tabela e grava no arquivo
    fclose($fp);

    $data = date("Y-m-d").".".date("H-i-s");
	$arquivo_xls = "xls/relatorio-consulta-os-peca-$login_fabrica.$data.xls";
	rename("/tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html", $arquivo_xls);
//    echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_xls /tmp/assist/relatorio-consulta-os-peca-$login_fabrica.html`;

//  Para mostrar o relatório em tela, só descomentar e alterar a condição, se precisar:
	#if ($total <= 250) readfile($arquivo_xls);

?>
<div style='width:600px;margin:auto;text-align:centerfont-size:13px'>
	<p style='font-size:14pxfont-weight:bold'>RELATÓRIO POR PEÇA</p>
	<input type='button' value='Download em Excel' onclick="window.location='xls/relatorio-consulta-os-peca-<?=$login_fabrica . '.' . $data?>.xls'">

</div>
<?}

echo "<br />";

##### BOTÃO NOVA CONSULTA #####
echo "<table width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
    echo "<tr class='table_line'>";
        echo "<td align='center' background='#D9E2EF'>";
            echo "<a href='defeito_os_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
        echo "</td>";
    echo "</tr>";
echo "</table>";

echo "<br />";

include "rodape.php";

?>
