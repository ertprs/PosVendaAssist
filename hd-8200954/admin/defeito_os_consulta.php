<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

//$gera_automatico = trim($_GET["gera_automatico"]);

//if ($gera_automatico != 'automatico'){
    include "autentica_admin.php";
//}

//include "gera_relatorio_pararelo_include.php";

include "funcoes.php";
$msg = "";

if ($_POST["chk_opt1"])  $chk1  = $_POST["chk_opt1"];
if ($_POST["chk_opt2"])  $chk2  = $_POST["chk_opt2"];
if ($_POST["chk_opt3"])  $chk3  = $_POST["chk_opt3"];
if ($_POST["chk_opt4"])  $chk4  = $_POST["chk_opt4"];
if ($_POST["chk_opt5"])  $chk5  = $_POST["chk_opt5"];
if ($_POST["chk_opt6"])  $chk6  = $_POST["chk_opt6"];
if ($_POST["chk_opt7"])  $chk7  = $_POST["chk_opt7"];
if ($_POST["chk_opt8"])  $chk8  = $_POST["chk_opt8"];
if ($_POST["chk_opt9"])  $chk9  = $_POST["chk_opt9"];
if ($_POST["chk_opt10"]) $chk10 = $_POST["chk_opt10"];
if ($_POST["chk_opt11"]) $chk11 = $_POST["chk_opt11"];
if ($_POST["chk_opt12"]) $chk12 = $_POST["chk_opt12"];
if ($_POST["chk_opt13"]) $chk13 = $_POST["chk_opt13"];
if ($_POST["chk_opt14"]) $chk14 = $_POST["chk_opt14"];
if ($_POST["chk_opt15"]) $chk15 = $_POST["chk_opt15"];
if ($_POST["chk_opt16"]) $chk16 = $_POST["chk_opt16"];
if ($_POST["chk_opt17"]) $chk17 = $_POST["chk_opt17"];
if ($_POST["chk_opt18"]) $chk18 = $_POST["chk_opt18"];
if ($_POST["chk_opt19"]) $chk19 = $_POST["chk_opt19"];
if ($_POST["chk_opt20"]) $chk20 = $_POST["chk_opt20"];
if ($_POST["chk_opt21"]) $chk21 = $_POST["chk_opt21"];

if ($_GET["chk_opt1"])  $chk1  = $_GET["chk_opt1"];
if ($_GET["chk_opt2"])  $chk2  = $_GET["chk_opt2"];
if ($_GET["chk_opt3"])  $chk3  = $_GET["chk_opt3"];
if ($_GET["chk_opt4"])  $chk4  = $_GET["chk_opt4"];
if ($_GET["chk_opt5"])  $chk5  = $_GET["chk_opt5"];
if ($_GET["chk_opt6"])  $chk6  = $_GET["chk_opt6"];
if ($_GET["chk_opt7"])  $chk7  = $_GET["chk_opt7"];
if ($_GET["chk_opt8"])  $chk8  = $_GET["chk_opt8"];
if ($_GET["chk_opt9"])  $chk9  = $_GET["chk_opt9"];
if ($_GET["chk_opt10"]) $chk10 = $_GET["chk_opt10"];
if ($_GET["chk_opt11"]) $chk11 = $_GET["chk_opt11"];
if ($_GET["chk_opt12"]) $chk12 = $_GET["chk_opt12"];
if ($_GET["chk_opt13"]) $chk13 = $_GET["chk_opt13"];
if ($_GET["chk_opt14"]) $chk14 = $_GET["chk_opt14"];
if ($_GET["chk_opt15"]) $chk15 = $_GET["chk_opt15"];
if ($_GET["chk_opt16"]) $chk16 = $_GET["chk_opt16"];
if ($_GET["chk_opt17"]) $chk17 = $_GET["chk_opt17"];
if ($_GET["chk_opt18"]) $chk18 = $_GET["chk_opt18"];
if ($_GET["chk_opt19"]) $chk19 = $_GET["chk_opt19"];
if ($_GET["chk_opt20"]) $chk20 = $_GET["chk_opt20"];
if ($_GET["chk_opt21"]) $chk21 = $_GET["chk_opt21"];

if ($_POST["consumidor_revenda"]) $consumidor_revenda = trim($_POST["consumidor_revenda"]);
if ($_POST["situacao"])           $situacao           = trim($_POST["situacao"]);
if ($_POST["dia_em_aberto"])      $dia_em_aberto      = trim($_POST["dia_em_aberto"]);
if ($_POST["data_inicial"])       $data_inicial       = trim($_POST["data_inicial"]);
if ($_POST["data_final"])         $data_final         = trim($_POST["data_final"]);
if ($_POST["codigo_posto"])       $codigo_posto       = trim($_POST["codigo_posto"]);
if ($_POST["nome_posto"])         $nome_posto         = trim($_POST["nome_posto"]);
if ($_POST["estado_posto"])       $estado_posto       = trim($_POST["estado_posto"]);
if ($_POST["produto_referencia"]) $produto_referencia = trim($_POST["produto_referencia"]);
if ($_POST["produto_nome"])       $produto_nome       = trim($_POST["produto_nome"]);
#if ($_POST["servico_realizado"])  $servico_realizado  = trim($_POST["servico_realizado"]);
#if ($_POST["defeito"])            $defeito            = trim($_POST["defeito"]);
if ($_POST["defeito_reclamado"])  $defeito_reclamado  = trim($_POST["defeito_reclamado"]);
if ($_POST["defeito_constatado"]) $defeito_constatado = trim($_POST["defeito_constatado"]);
if ($_POST["familia"])            $familia            = trim($_POST["familia"]);
if ($_POST["familia_serie"])      $familia_serie      = trim($_POST["familia_serie"]);
if ($_POST["numero_serie"])       $numero_serie       = trim($_POST["numero_serie"]);
if ($_POST["nome_consumidor"])    $nome_consumidor    = trim($_POST["nome_consumidor"]);
if ($_POST["cidade"])             $cidade             = trim($_POST["cidade"]);
if ($_POST["estado"])             $estado             = trim($_POST["estado"]);
if ($_POST["numero_os"])          $numero_os          = trim($_POST["numero_os"]);
if ($_POST["numero_nf"])          $numero_nf          = trim($_POST["numero_nf"]);
if ($_POST["btn_acao"])           $btn_acao           = trim($_POST["btn_acao"]);

# data da aprovação adicionado por Fábio a pedido da Honorato HD 3096 - 13/07/2007
if ($_POST["extrato_data_inicial"]) $extrato_data_inicial = trim($_POST["extrato_data_inicial"]);
if ($_POST["extrato_data_final"])   $extrato_data_final   = trim($_POST["extrato_data_final"]);

//HD 227132
if ($_POST["tipo_atendimento"]) $tipo_atendimento = trim($_POST["tipo_atendimento"]);

if ($_GET["consumidor_revenda"]) $consumidor_revenda = trim($_GET["consumidor_revenda"]);
if ($_GET["situacao"])           $situacao           = trim($_GET["situacao"]);
if ($_GET["dia_em_aberto"])      $dia_em_aberto      = trim($_GET["dia_em_aberto"]);
if ($_GET["data_inicial"])       $data_inicial       = trim($_GET["data_inicial"]);
if ($_GET["data_final"])         $data_final         = trim($_GET["data_final"]);
if ($_GET["codigo_posto"])       $codigo_posto       = trim($_GET["codigo_posto"]);
if ($_GET["nome_posto"])         $nome_posto         = trim($_GET["nome_posto"]);
if ($_GET["estado_posto"])       $estado_posto       = trim($_GET["estado_posto"]);
if ($_GET["produto_referencia"]) $produto_referencia = trim($_GET["produto_referencia"]);
if ($_GET["produto_nome"])       $produto_nome       = trim($_GET["produto_nome"]);
#if ($_GET["servico_realizado"])  $servico_realizado  = trim($_GET["servico_realizado"]);
#if ($_GET["defeito"])            $defeito            = trim($_GET["defeito"]);
if ($_GET["defeito_reclamado"])  $defeito_reclamado  = trim($_GET["defeito_reclamado"]);
if ($_GET["defeito_constatado"]) $defeito_constatado = trim($_GET["defeito_constatado"]);
if ($_GET["familia"])            $familia            = trim($_GET["familia"]);
if ($_GET["familia_serie"])      $familia_serie      = trim($_GET["familia_serie"]);
if ($_GET["numero_serie"])       $numero_serie       = trim($_GET["numero_serie"]);
if ($_GET["nome_consumidor"])    $nome_consumidor    = trim($_GET["nome_consumidor"]);
if ($_GET["cidade"])             $cidade             = trim($_GET["cidade"]);
if ($_GET["estado"])             $estado             = trim($_GET["estado"]);
if ($_GET["numero_os"])          $numero_os          = trim($_GET["numero_os"]);
if ($_GET["numero_nf"])          $numero_nf          = trim($_GET["numero_nf"]);
if ($_GET["btn_acao"])           $btn_acao           = trim($_GET["btn_acao"]);

# data da aprovação adicionado por Fábio a pedido da Honorato HD 3096 - 13/07/2007
if ($_GET["extrato_data_inicial"]) $extrato_data_inicial = trim($_GET["extrato_data_inicial"]);
if ($_GET["extrato_data_final"])   $extrato_data_final   = trim($_GET["extrato_data_final"]);

if ($_GET["tipo_atendimento"]) $tipo_atendimento = trim($_GET["tipo_atendimento"]);

if( empty($chk1) AND empty($chk2) AND empty($chk3) AND empty($chk4) ){
	
	if(!empty($chk6)){
		if(!$data_inicial or !$data_final)
			$msg_erro = "Data Obrigatória";
		//Início Validação de Datas
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$di = $dat[0];
				$mi = $dat[1];
				$yi = $dat[2];
				if(!checkdate($mi, $di, $yi)) $msg_erro = "Data inicial inválida";
		}
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_final );//tira a barra
				$df = $dat[0];
				$mf = $dat[1];
				$yf = $dat[2];
				if(!checkdate($mf, $df, $yf)) $msg_erro = "Data final inválida";
		}
		if(strlen($msg_erro)==0){
			$data_inicial_aux = "$yi-$mi-$di";
			$data_final_aux   = "$yf-$mf-$df";

			if($data_final_aux < $data_inicial_aux){
				$msg_erro = "Data inicial maior do que data final";
			}

			if(strlen($msg_erro)==0){
				if (strtotime($data_inicial_aux.'+3 month') < strtotime($data_final_aux) ) {
					$msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
				}
			}

			//Fim Validação de Datas
		}
	} else {
		$msg_erro = "Data Obrigatória";
	}

}

if ($login_fabrica == 19) $layout_menu = "callcenter";
else                      $layout_menu = "gerencia";

$title = "Relação de Ordens de Serviços Lançadas";

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

</style>

<br />

<?php
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
    
    $sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    
    $dia_hoje_inicio = pg_result($resX,0,0) ;
    $dia_hoje_final  = pg_result($resX,0,0) ;
    $dia_hoje_inicio = pg_result($resX,0,0);
    $dia_hoje_final  = pg_result($resX,0,0);

    $monta_sql  .= " AND (data_digitacao BETWEEN '$dia_hoje_inicio 00:00:00' AND '$dia_hoje_final 23:59:59') ";
    $monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$dia_hoje_inicio 00:00:00' AND '$dia_hoje_final 23:59:59') ";    
    
    $dt = 1;

    $msg .= " OS lançadas hoje ";

}

##### OS Lançadas Ontem #####
if (strlen($chk2) > 0) {
    
    $sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);

    $dia_ontem_inicial = pg_result($resX,0,0);
    $dia_ontem_final   = pg_result($resX,0,0);
    $dia_ontem_inicial = pg_result($resX,0,0);
    $dia_ontem_final   = pg_result($resX,0,0);

    $monta_sql  .=" AND (tbl_os.data_digitacao BETWEEN '$dia_ontem_inicial 00:00:00' AND '$dia_ontem_final 23:59:59') ";
    $monta_sql2 .=" AND (tbl_os_excluida.data_digitacao BETWEEN '$dia_ontem_inicial 00:00:00' AND '$dia_ontem_final 23:59:59') ";

    $dt = 1;

    if (strlen($msg) > 0) $msg .= " e ";
    $msg .= " OS lançados ontem ";

}

##### OS Lançadas Nesta Semana #####
if (strlen($chk3) > 0) {
    
    $sqlX = "SELECT to_char (current_date , 'D')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_hoje = pg_result($resX,0,0) - 1 ;

    $sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_inicial = pg_result($resX,0,0);

    $dia_semana_inicial = pg_result($resX,0,0);

    $sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
    $resX = pg_exec ($con,$sqlX);
    $dia_semana_final = pg_result ($resX,0,0);

    $dia_semana_final = pg_result ($resX,0,0);

    $monta_sql  .= " AND (tbl_os.data_digitacao BETWEEN '$dia_semana_inicial 00:00:00' AND '$dia_semana_final 23:59:59') ";
    $monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$dia_semana_inicial 00:00:00' AND '$dia_semana_final 23:59:59') ";
    
    $dt = 1;

    if (strlen($msg) > 0) $msg .= " e ";
    $msg .= " OS lançadas nesta semana ";

}

##### OS Lançadas Neste Mês #####
if (strlen($chk4) > 0) {
    
    $mes_inicial = trim(date("Y")."-".date("m")."-01");
    $mes_final   = trim(date("Y")."-".date("m")."-".date("d"));

    $monta_sql  .= " AND (tbl_os.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
    $monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";

    $dt = 1;

    if (strlen($msg) > 0) $msg .= " e ";
    $msg .= " OS lançadas neste mês ";  

}

##### Situação da OS #####
if (strlen($chk5) > 0) {
    
	if (strtotime($data_inicial_aux." +".$dia_em_aberto." days") > strtotime($data_final_aux) ) {
		$msg_erro = 'A quantidade de dias não pode ultrapassar a data inicial';
	}
	if(empty($msg_erro)){
		if (strlen($dia_em_aberto) > 0) {
			
			$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
			$resX = pg_exec($con,$sqlX);
			$dia_hoje = pg_result($resX,0,0);
			
			$sqlX = "SELECT to_char ('$dia_hoje'::date - INTERVAL '$dia_em_aberto days', 'YYYY-MM-DD')";
			$resX = pg_exec($con,$sqlX);
			$dia_aberto = pg_result($resX,0,0);

			$monta_sql  .= " AND (tbl_os.data_digitacao < '$dia_aberto 00:00:00' AND tbl_os.data_fechamento IS NULL) ";
			$monta_sql2 .= " AND (tbl_os_excluida.data_digitacao < '$dia_aberto 00:00:00' AND tbl_os_excluida.data_fechamento IS NULL) ";
			
			$dt = 1;

			if (strlen($msg) > 0) $msg .= " e ";
			$msg .= " OS lançadas em aberto no período de <i>$dia_em_aberto</i> dias ";
		
		} else {
			$monta_sql  .= " AND tbl_os.data_fechamento IS NULL ";
		}
	}
    
    $qtde_chk++;

}

##### Entre Datas #####
if (strlen($chk6) > 0) {
    
    if ((strlen($data_inicial) == 10) AND (strlen($data_final) == 10)) {

		list($dia, $mes, $ano) = preg_split('/[\-|\/|.]/', $data_inicial);
		if (!checkdate($mes, $dia, $ano)) $msg_erro = 'Data inválida!';
		$xdata_inicial = "$ano-$mes-$dia";

		list($dia, $mes, $ano) = preg_split('/[\-|\/|.]/', $data_final);
		if (!checkdate($mes, $dia, $ano)) $msg_erro = 'Data inválida!';
		$xdata_final	  = "$ano-$mes-$dia";
		
		if (strtotime($xdata_inicial) > strtotime($xdata_final) ) {
			$msg_erro = 'A data inicial não pode ser maior do que a data final';
		}

		if (strtotime($xdata_inicial.'+3 month') < strtotime($xdata_final) ) {
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
		}

        $monta_sql  .= " AND (tbl_os.data_digitacao BETWEEN '$xdata_inicial 00:00:00'  AND '$xdata_final 23:59:59') ";
        $monta_sql2 .= " AND (tbl_os_excluida.data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59') ";
        
        $dt = 1;

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas entre os dias <i>$data_inicial</i> e <i>$data_final</i> ";
    
    } else {
        $msg_erro .= " Favor lançar a data inicial/final!";
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

		if(strlen($x_extrato_data_inicial)>0 AND strlen($x_extrato_data_final)>0){
			$sql = "select '$x_extrato_data_final'::date - '$x_extrato_data_inicial'::date ";
			$res = pg_exec($con,$sql);
#			if(pg_result($res,0,0)>31)$msg_erro .= "Período não pode ser maior que 31 dias";
		}

        $sqlX =    "SELECT extrato
                FROM    tbl_extrato
                WHERE   fabrica = $login_fabrica
                AND     aprovado BETWEEN '$x_extrato_data_inicial 00:00:00'  AND '$x_extrato_data_final 23:59:59'
                AND liberado IS NOT NULL";
        
        $resX = pg_exec($con,$sqlX);
        $extratos = array();
        
        for ($i = 0 ; $i < pg_numrows ($resX) ; $i++){
            array_push($extratos,trim(pg_result ($resX,$i,extrato)));
        }

        if (count($extratos) > 0) {
            $extratos = implode(",",$extratos);
            $join_extrato .= " JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato IN ($extratos)";
            #$monta_sql .= " AND tbl_os_extra.extrato IN ($extratos)";
        } else {
            $monta_sql .= " AND 1 = 2 ";
        }
        
        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " Aprovadas entre os dias <i>$extrato_data_inicial</i> e <i>$extrato_data_final</i> ";
    
    }
    
    $qtde_chk++;

}

##### Posto #####
if (strlen($chk7) > 0) {
    
    if (strlen($codigo_posto) > 0) {

         $xsql = " AND ";

        $monta_sql  .= " $xsql tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
        $monta_sql2 .= " $xsql tbl_os_excluida.codigo_posto = '$codigo_posto' ";
    
        $dt = 1;

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas pelo posto <i>$nome_posto</i> ";
    
    }

    if (strlen($estado_posto) > 0) {
        
        $xsql = " AND ";

        if ($estado_posto == "centro-oeste") $monta_sql .= " $xsql contato_estado in ('GO','MT','MS','DF') ";
        if ($estado_posto == "nordeste")     $monta_sql .= " $xsql contato_estado in ('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
        if ($estado_posto == "norte")        $monta_sql .= " $xsql contato_estado in ('AC','AM','RR','RO','PA','AP','TO') ";
        if ($estado_posto == "sudeste")      $monta_sql .= " $xsql contato_estado in ('MG','ES','RJ','SP') ";
        if ($estado_posto == "sul")          $monta_sql .= " $xsql contato_estado in ('PR','SC','RS') ";
        if (strlen($estado_posto) == 2)      $monta_sql .= " $xsql upper(contato_estado) = upper('$estado_posto') ";
        if ($estado_posto == "SP-capital") {
            $monta_sql .= " AND tbl_os.posto in(SELECT posto
                                                  FROM tbl_posto_fabrica
                                                  WHERE fabrica        = $login_fabrica
                                                    AND contato_estado = 'SP'
                                                    AND (contato_cidade ~* 's.o paulo'             OR
                                                         contato_cidade ~* 's.o bernardo do campo' OR
                                                         contato_cidade ~* 'S.o Caetano do Sul'    OR
                                                         contato_cidade ~* 'Guarulhos'             OR
                                                         contato_cidade ~* 'Santo Andr.')
                                            )";
        }
        if ($estado_posto == "SP-interior") {
            $monta_sql .= " AND tbl_os.posto in(SELECT posto
                                                  FROM tbl_posto_fabrica
                                                 WHERE fabrica        = $login_fabrica
                                                   AND contato_estado = 'SP'
                                                   AND contato_cidade !~* 's.o paulo'
                                                   AND contato_cidade !~* 's.o bernardo do campo'
                                                   AND contato_cidade !~* 'S.o Caetano do Sul'
                                                   AND contato_cidade !~* 'Guarulhos'
                                                   AND contato_cidade !~* 'Santo Andr.')";
        }

        $monta_sql2 .= $monta_sql;
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

##### Serviço Realizado #####
if (strlen($chk9) > 0) {
    
    if ($servico_realizado) {
        
        if ($dt == 1) $xsql = " AND ";
        else          $xsql = " AND ";

        $monta_sql .= " $xsql servico_realizado = '$servico_realizado' ";
        
        $dt = 1;

        $sqlX =    "SELECT descricao
                FROM    tbl_servico_realizado
                WHERE   fabrica = $login_fabrica
                AND     servico_realizado = $servico_realizado;";
        
        $resX = pg_exec($con,$sqlX);

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas contendo peças com serviço realizado <i>" . pg_result($resX,0,0) . "</i> ";
    
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
        
        $resX = pg_exec($con,$sqlX);

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas contendo peças com defeito <i>" . pg_result($resX,0,0) . "</i> ";
    
    }

    $qtde_chk++;

}

##### Defeito Reclamado #####
if (strlen($chk11) > 0) {
    
    if ($defeito_reclamado) {
        
        if ($dt == 1) $xsql = " AND ";
        else          $xsql = " AND ";

        $monta_sql .= " $xsql tbl_os.defeito_reclamado = '$defeito_reclamado' ";
        
        $dt = 1;

        if ($login_fabrica == 43) {
            $sqlX =    "SELECT tbl_defeito_reclamado.descricao
                    FROM    tbl_defeito_reclamado
                    WHERE   tbl_defeito_reclamado.fabrica = $login_fabrica
                    AND     tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado;";
        } else {
            $sqlX =    "SELECT tbl_defeito_reclamado.descricao
                    FROM    tbl_defeito_reclamado
                    JOIN    tbl_familia USING (familia)
                    WHERE   tbl_familia.fabrica = $login_fabrica
                    AND     tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado;";
        }

        $resX = @pg_exec($con,$sqlX);

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas contendo produtos com defeito reclamado <i>" . @pg_result($resX,0,0) . "</i> ";
    
    }

    $qtde_chk++;

}

##### Defeito Constatado #####
if (strlen($chk12) > 0) {
    
    if ($defeito_constatado) {
        
        if ($dt == 1) $xsql = " AND ";
        else          $xsql = " AND ";

        $monta_sql .= "$xsql tbl_os.defeito_constatado = '$defeito_constatado' ";
        
        $dt = 1;

        $sqlX =    "SELECT descricao
                FROM    tbl_defeito_constatado
                WHERE   defeito_constatado = $defeito_constatado
                AND     fabrica            = $login_fabrica;";
        $resX = pg_exec($con,$sqlX);

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas contendo produtos com defeito constatado <i>" . pg_result($resX,0,0) ."</i> ";
    
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

    $monta_sql  .= " $xsql upper(tbl_os.serie) LIKE upper('%$x_numero_serie%') ";
    $monta_sql2 .= " $xsql upper(tbl_os.serie) LIKE upper('%$x_numero_serie%') ";
    
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

        $monta_sql  .= "$xsql upper(consumidor_nome) LIKE upper('%$nome_consumidor%') ";
        $monta_sql2 .= "$xsql upper(consumidor_nome) LIKE upper('%$nome_consumidor%') ";
        
        $dt = 1;

        if (strlen($msg) > 0) $msg .= " e ";
        $msg .= " OS lançadas para o consumidor <i>$nome_consumidor</i>";
        
        $qtde_chk++;
    
    }

}

##### CPF/CNPJ do Consumidor #####
if (strlen($chk16) > 0) {
    
    $x_cpf_consumidor = str_replace(".", "", $cpf_consumidor);
    $x_cpf_consumidor = str_replace("-", "", $x_cpf_consumidor);
    $x_cpf_consumidor = str_replace("/", "", $x_cpf_consumidor);
    $x_cpf_consumidor = str_replace(" ", "", $x_cpf_consumidor);

    if ($cpf_consumidor) {
    
        if ($dt == 1) $xsql = " AND ";
        else          $xsql = " AND ";

        $monta_sql .= " $xsql consumidor_cpf LIKE '%$x_cpf_consumidor%' ";
        
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

        $xsql = " AND ";

        //NESSAS FABRICAS ELES BUSCAM PELO ENDEREÇO DO POSTO - HD 243867
        if ($login_fabrica == 14 or $login_fabrica == 43 or $login_fabrica == 66) {

            if ($estado == "centro-oeste") $monta_sql .= " $xsql tbl_posto_fabrica.contato_estado in('GO','MT','MS','DF') ";
            if ($estado == "nordeste")     $monta_sql .= " $xsql tbl_posto_fabrica.contato_estado in('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
            if ($estado == "norte")        $monta_sql .= " $xsql tbl_posto_fabrica.contato_estado in('AC','AM','RR','RO','PA','AP','TO') ";
            if ($estado == "sudeste")      $monta_sql .= " $xsql tbl_posto_fabrica.contato_estado in('MG','ES','RJ','SP') ";
            if ($estado == "sul")          $monta_sql .= " $xsql tbl_posto_fabrica.contato_estado in('PR','SC','RS') ";
            if (strlen($estado) == 2)      $monta_sql .= " $xsql upper(tbl_posto_fabrica.contato_estado) = upper('$estado') ";
            if ($estado == "SP-capital") {
                $monta_sql .= " AND tbl_os.posto in(SELECT posto
                                                      FROM tbl_posto_fabrica
                                                     WHERE fabrica        = $login_fabrica
                                                       AND contato_estado = 'SP'
                                                       AND (contato_cidade ~* 's.o paulo'             OR
                                                            contato_cidade ~* 's.o bernardo do campo' OR
                                                            contato_cidade ~* 'S.o Caetano do Sul'    OR
                                                            contato_cidade ~* 'Guarulhos'             OR
                                                            contato_cidade ~* 'Santo Andr.')
                                                   )";
            }
            if ($estado == "SP-interior") {
                $monta_sql .= " AND tbl_os.posto in(SELECT posto
                                                      FROM tbl_posto_fabrica
                                                     WHERE fabrica        = $login_fabrica
                                                       AND contato_estado = 'SP'
                                                       AND contato_cidade !~* 's.o paulo'
                                                       AND contato_cidade !~* 's.o bernardo do campo'
                                                       AND contato_cidade !~* 'S.o Caetano do Sul'
                                                       AND contato_cidade !~* 'Guarulhos'
                                                       AND contato_cidade !~* 'Santo Andr.')";
            }

        } else {

            if ($estado == "centro-oeste") $monta_sql .= " $xsql tbl_os.consumidor_estado in('GO','MT','MS','DF') ";
            if ($estado == "nordeste")     $monta_sql .= " $xsql tbl_os.consumidor_estado in('MA','PI','CE','RN','PB','PE','AL','SE','BA') ";
            if ($estado == "norte")        $monta_sql .= " $xsql tbl_os.consumidor_estado in('AC','AM','RR','RO','PA','AP','TO') ";
            if ($estado == "sudeste")      $monta_sql .= " $xsql tbl_os.consumidor_estado in('MG','ES','RJ','SP') ";
            if ($estado == "sul")          $monta_sql .= " $xsql tbl_os.consumidor_estado in('PR','SC','RS') ";
            if (strlen($estado) == 2)      $monta_sql .= " $xsql upper(tbl_os.consumidor_estado) = upper('$estado') ";
            if ($estado == "SP-capital") {
                $monta_sql .= " AND tbl_os.consumidor_estado = 'SP'
                                AND (tbl_os.consumidor_cidade ~* 's.o paulo'             OR
                                     tbl_os.consumidor_cidade ~* 's.o bernardo do campo' OR
                                     tbl_os.consumidor_cidade ~* 'S.o Caetano do Sul'    OR
                                     tbl_os.consumidor_cidade ~* 'Guarulhos'             OR
                                     tbl_os.consumidor_cidade ~* 'Santo Andr.')";
            }
            if ($estado == "SP-interior") {
                $monta_sql .= " AND tbl_os.consumidor_estado = 'SP'
                                AND (tbl_os.consumidor_cidade !~* 's.o paulo'             AND
                                     tbl_os.consumidor_cidade !~* 's.o bernardo do campo' AND
                                     tbl_os.consumidor_cidade !~* 'S.o Caetano do Sul'    AND
                                     tbl_os.consumidor_cidade !~* 'Guarulhos'             AND
                                     tbl_os.consumidor_cidade !~* 'Santo Andr.')";
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
        
        if ($dt == 1) $xsql = " AND ";
        else          $xsql = " AND ";

        $monta_sql .= " $xsql (tbl_os.sua_os = '$numero_os' OR 
                                tbl_os.sua_os = '0$numero_os' OR 
                                tbl_os.sua_os = '00$numero_os' OR 
                                tbl_os.sua_os = '000$numero_os' OR 
                                tbl_os.sua_os = '0000$numero_os' OR 
                                tbl_os.sua_os = '00000$numero_os' OR 
                                tbl_os.sua_os = '000000$numero_os' OR 
                                tbl_os.sua_os = '0000000$numero_os' OR 
                                tbl_os.sua_os = '00000000$numero_os' OR
                                tbl_os.sua_os = '000000000$numero_os' OR
                                tbl_os.sua_os = '0000000000$numero_os' OR
                                tbl_os.sua_os = '$numero_os-01' OR
                                tbl_os.sua_os = '$numero_os-02' OR
                                tbl_os.sua_os = '$numero_os-03' OR
                                tbl_os.sua_os = '$numero_os-04' OR
                                tbl_os.sua_os = '$numero_os-05' OR
                                tbl_os.sua_os = '$numero_os-06' OR
                                tbl_os.sua_os = '$numero_os-07' OR
                                tbl_os.sua_os = '$numero_os-08' OR
                                tbl_os.sua_os = '$numero_os-09')";
        $monta_sql2 .= " $xsql (tbl_os_excluida.sua_os = '$numero_os' OR 
                                tbl_os_excluida.sua_os = '0$numero_os' OR 
                                tbl_os_excluida.sua_os = '00$numero_os' OR 
                                tbl_os_excluida.sua_os = '000$numero_os' OR 
                                tbl_os_excluida.sua_os = '0000$numero_os' OR 
                                tbl_os_excluida.sua_os = '00000$numero_os' OR 
                                tbl_os_excluida.sua_os = '000000$numero_os' OR 
                                tbl_os_excluida.sua_os = '0000000$numero_os' OR 
                                tbl_os_excluida.sua_os = '00000000$numero_os' OR
                                tbl_os_excluida.sua_os = '000000000$numero_os' OR
                                tbl_os_excluida.sua_os = '0000000000$numero_os' OR
                                tbl_os_excluida.sua_os = '$numero_os-01' OR
                                tbl_os_excluida.sua_os = '$numero_os-02' OR
                                tbl_os_excluida.sua_os = '$numero_os-03' OR
                                tbl_os_excluida.sua_os = '$numero_os-04' OR
                                tbl_os_excluida.sua_os = '$numero_os-05' OR
                                tbl_os_excluida.sua_os = '$numero_os-06' OR
                                tbl_os_excluida.sua_os = '$numero_os-07' OR
                                tbl_os_excluida.sua_os = '$numero_os-08' OR
                                tbl_os_excluida.sua_os = '$numero_os-09') ";
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

if (strlen($situacao) > 0) {
    
    if ($dt == 1) $xsql = " AND ";
    else          $xsql = " AND ";

    $monta_sql  .= " $xsql data_fechamento $situacao ";
    $monta_sql2 .= " $xsql data_fechamento $situacao ";

    $dt = 1;

    $qtde_chk++;

}

if (strlen($tipo_atendimento) > 0) {//HD 227132

    $xsql = " AND ";
    $monta_sql .= " $xsql tbl_os.tipo_atendimento = $tipo_atendimento ";
    $dt = 1;
    $qtde_chk++;

}

if (strlen($consumidor_revenda) > 0 AND ($consumidor_revenda == "R" OR $consumidor_revenda == "C")) {
    
    if ($dt == 1) $xsql = " AND ";
    else          $xsql = " AND ";

    $monta_sql .= " $xsql consumidor_revenda = '$consumidor_revenda' ";
    $dt = 1;

    if (strlen($msg) > 0) $msg .= " e ";
    if ($consumidor_revenda == "R") $msg .= " de revendas ";
    if ($consumidor_revenda == "C") $msg .= " de consumidores ";
    
    $qtde_chk++;

}

##### CONCATENA O SQL PADRÃO #####
###  WHERE ###########

//$sql =    "SELECT * FROM (
$sql =    "     SELECT      DISTINCT lpad(tbl_os.sua_os,10,'0')                         AS ordem ,
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
                            tbl_os.defeito_reclamado_descricao                                   ,
                            tbl_os.defeito_constatado                                            ,
                            tbl_os.qtde_produtos                                                 ,
                            tbl_tipo_os.descricao                           AS tipo_os_descricao ,
                            tbl_os.observacao,
                            tbl_os.obs,
                            tbl_posto.cnpj                                     AS cnpj_posto     ,
                            tbl_posto.nome                                     AS posto_nome     ,
                            tbl_posto_fabrica.contato_estado                   AS estado         ,
                            tbl_posto_fabrica.codigo_posto                     AS codigo_posto   ,
                            tbl_produto.familia                                                  ,
                            tbl_produto.referencia_pesquisa                    AS referencia     ,
                            tbl_produto.descricao                                                ,
                            '$login_login'                                     AS login_login
                FROM        tbl_os
                JOIN        tbl_produto          ON  tbl_os.produto            = tbl_produto.produto
                JOIN        tbl_posto            ON  tbl_os.posto              = tbl_posto.posto
                JOIN        tbl_posto_fabrica    ON  tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
                LEFT JOIN   tbl_os_status        ON (tbl_os_status.os          = tbl_os.os AND (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
                AND         tbl_os_status.fabrica_status=$login_fabrica)
                $join_extrato
                LEFT JOIN   tbl_tipo_os          ON tbl_tipo_os.tipo_os  = tbl_os.tipo_os
                WHERE       tbl_os.fabrica = $login_fabrica
                AND         tbl_os.posto <> 6359
                AND         tbl_os.excluida IS NOT TRUE";
            $sql .= $monta_sql;
flush();

if ($qtde_chk < 3 and empty($chk1) and empty($chk2) and empty($chk3) and empty($chk4)) {
    $msg_erro .= "<p style='font-size: 12px; font-family: verdana;'> Por favor, escolha pelo menos 3 filtros para a pesquisa.</p>";
}

//if (strlen($btn_acao) > 0 and strlen($msg_erro) == 0) {
//    include "gera_relatorio_pararelo.php";
//}

//if ($gera_automatico != 'automatico' and strlen($msg_erro) == 0) {
//    include "gera_relatorio_pararelo_verifica.php";
//}

if (strlen($msg_erro) > 0) {?>
    <table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
        <tr>
            <td align="center" class='error'><?=$msg_erro?></td>
        </tr>
    </table>
    <br /><?php
}

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {

    echo "<p>Relatório gerado em ".date("d/m/Y")." às ".date("H:i")."</p><br />";
    
#    echo nl2br($sql);
    //exit;

    #$res = pg_exec($con,$sql);
	echo "Ronald";
	echo "<table border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='defeito_os_consulta-xls.php?chk_opt1=$chk1&chk_opt2=$chk2&chk_opt3=$chk3&chk_opt4=$chk4&chk_opt5=$chk5&chk_opt6=$chk6&chk_opt7=$chk7&chk_opt8=$chk8&chk_opt9=$chk9&chk_opt10=$chk10&chk_opt11=$chk11&chk_opt12=$chk12&chk_opt13=$chk13&chk_opt14=$chk14&chk_opt15=$chk15&chk_opt16=$chk16&chk_opt17=$chk17&chk_opt18=$chk18&chk_opt19=$chk19&chk_opt20=$chk20&chk_opt21=$chk21&tipo_atendimento=$tipo_atendimento&consumidor_revenda=$consumidor_revenda&situacao=$situacao&dia_em_aberto=$dia_em_aberto&data_inicial=$data_inicial&data_final=$data_final&codigo_posto=$codigo_posto&nome_posto=$nome_posto&estado_posto=$estado_posto&produto_referencia=$produto_referencia&produto_nome=$produto_nome&produto_voltagem=$produto_voltagem&servico_realizado=$servico_realizado&defeito=$defeito&reclamado_familia=$reclamado_familia&constatado_familia=$constatado_familia&familia=$familia&numero_serie=$numero_serie&familia_serie=$familia_serie&nome_consumidor=$nome_consumidor&cpf_consumidor=$cpf_consumidor&cidade=$cidade&estado=$estado&numero_os=$numero_os&numero_nf=$numero_nf&extrato_data_inicial=$extrato_data_inicial&extrato_data_final=$extrato_data_final' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br /><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
	echo "</table>";

    if (pg_numrows($res) == 0) {
		#Não precisa executar esse select a tela estava sendo executada duas vezes para gerar o mesmo relatório
        #echo "<table width='700' height='50'><tr class='menu_top'><td align='center'>Nenhum resultado encontrado.</td></tr></table>";

    } else {

        $vet_os = null;

        while ($row = pg_fetch_assoc($res)) {
            $vet_os[] = trim($row['os']);
        }

        $vet = implode(',',$vet_os);
        
        if (strlen($vet) > 0) {
            
            $sql2 = "SELECT COUNT(defeito_constatado) as total
                       FROM tbl_os_defeito_reclamado_constatado
                      WHERE os in($vet)
                      GROUP BY os
                      ORDER BY total desc;";

//			echo nl2br($sql2);
//			exit;
            
//            $result = pg_exec($con,$sql2);
            
            if (pg_numrows($result) > 0) {
                
                $vet_constatado = trim(pg_result($result,0,total));
            
            } else {
            
                $vet_constatado = 0;

            }

        } else {
            
            $vet_constatado = 0;

        }

   /* COMENTADO NO HD 283996
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

        echo "<tr class='menu_top'>\n";
            echo "<td colspan='15'>$msg</td>\n";
        echo "</tr>\n";

        echo "<tr class='menu_top'>\n";
            echo "<td>OS</TD>\n";
            
            if ($login_fabrica == 19) {
                echo "<TD nowrap>NF CLIENTE</TD>\n";
                echo "<TD nowrap>NF ORIGEM</TD>\n";
                echo "<TD nowrap>MOTIVO</TD>\n";
            }
            
            echo "<td>SÉRIE</TD>\n";
            echo "<td>ABERTURA</td>\n";
            
            if ($login_fabrica == 19) {
                echo "<td>DIGITAÇÃO</td>\n";    
            }
            
            echo "<td>FECHAMENTO</td>\n";
            echo "<td>CONSUMIDOR</td>\n";
            echo "<td>REVENDA</td>\n";
            
            if ($login_fabrica == 19) {
                echo "<td>CNPJ POSTO</td>\n";
            }
            
            echo "<td>POSTO</td>\n";
            
            if ($login_fabrica == 43) {
                echo "<td nowrap>REFERÊNCIA</td>\n";
                echo "<td nowrap>PRODUTO</td>\n";
                echo "<td nowrap>DEFEITO RECLAMADO</td>\n";
            } else {
                echo "<td>PRODUTO</td>\n";
            }
            
            if ($login_fabrica == 19) {
                echo "<TD nowrap>QTDE</TD>\n";
            }
            
            for ($x = 0; $x < $vet_constatado; $x++) {
                echo "<td nowrap>DEFEITO CONSTATADO ".($x+1)."</td>\n";
            }

            if ($login_fabrica == 14) {
                echo "<td>OBSERVAÇÕES</td>\n";
            }

        echo "</tr>\n";
        
        for ($i = 0; $i < pg_numrows($res); $i++) {
            $os                     = trim(pg_result($res,$i,os));
            $data                   = trim(pg_result($res,$i,data));
            $abertura               = trim(pg_result($res,$i,abertura));
            $fechamento             = trim(pg_result($res,$i,fechamento));
            $finalizada             = trim(pg_result($res,$i,finalizada));
            $sua_os                 = trim(pg_result($res,$i,sua_os));
            $serie                  = trim(pg_result($res,$i,serie));
            $consumidor_nome        = trim(pg_result($res,$i,consumidor_nome));
            $revenda_nome           = trim(pg_result($res,$i,revenda_nome));
            $posto_nome             = trim(pg_result($res,$i,posto_nome));
            $nota_fiscal            = trim(pg_result($res,$i,nota_fiscal));
            $nota_fiscal_saida      = trim(pg_result($res,$i,nota_fiscal_saida));
            $posto_codigo           = trim(pg_result($res,$i,codigo_posto));
            $posto_completo         = $posto_codigo . " - " . $posto_nome;
            $produto_nome           = trim(pg_result($res,$i,descricao));
            $produto_referencia     = trim(pg_result($res,$i,referencia));
            $data_fechamento        = trim(pg_result($res,$i,data_fechamento));
            $excluida               = trim(pg_result($res,$i,excluida));
            $defeito_constatado     = trim(pg_result($res,$i,defeito_constatado));
            $defeito_reclamado_desc = trim(pg_result($res,$i,defeito_reclamado_descricao));
            $qtde_produtos          = trim(pg_result($res,$i,qtde_produtos));
            $cnpj_posto             = trim(pg_result($res,$i,cnpj_posto));
            $tipo_os_descricao      = trim(pg_result($res,$i,tipo_os_descricao));
            $observacao             = trim(pg_result($res,$i,observacao));
            $obs                    = trim(pg_result($res,$i,obs));

            $cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
            
            if ($login_fabrica == 19) $consumidor_nome = strtoupper ($consumidor_nome);
            if (strlen(trim($sua_os)) == 0) $sua_os = $os;

            echo "<tr class='table_line' style='background-color: $cor;'>\n";

            if ($login_fabrica == 1) echo "<TD nowrap>$codigo_posto$sua_os</TD>\n";
            else                     echo "<TD nowrap>$sua_os</TD>\n";

            if ($login_fabrica == 19) {
                echo "<TD nowrap>$nota_fiscal</TD>\n";
                echo "<TD nowrap>$nota_fiscal_saida</TD>\n";
                echo "<TD nowrap>$tipo_os_descricao</td>";
            }

            echo "<td nowrap>$serie</td>\n";
            echo "<td align='center'><acronym title='Data Abertura Sistema: $abertura' style='cursor: help;'>$abertura</acronym></td>\n";
            
            if ($login_fabrica == 19) {
                echo "<td align='center'><acronym title='Data Digitação Sistema: $data' style='cursor: help;'>$data</acronym></td>\n";
            }

            echo "<td align='center'><acronym title='Data Fechamento Sistema: $finalizada' style='cursor: help;'>$fechamento</acronym></td>\n";
            echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>\n";
            echo "<td nowrap><acronym title='Consumidor: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,15) . "</acronym></td>\n";
            
            if ($login_fabrica == 19) {
                echo "<td nowrap><acronym title='CNPJ: $cnpj_posto style='cursor: help;'>$cnpj_posto</acronym></td>\n";
            }
            
            echo "<td nowrap><acronym title='Código: $codigo_posto\nRazão Social: $posto_nome' style='cursor: help;'>" . substr($posto_completo,0,30) . "</acronym></td>\n";
            
            if ($login_fabrica == 43) {
                echo "<td nowrap>$produto_referencia</td>\n";
                echo "<td nowrap>$produto_nome</td>\n";
                echo "<td nowrap><acronym title='Defeito Reclamado: $defeito_reclamado_desc' style='cursor: help;'>" . substr($defeito_reclamado_desc,0,30) . "</acronym></td>\n";
            } else {
                echo "<td nowrap>$produto_referencia - $produto_nome</td>\n";
            }

            if ($login_fabrica == 19) {
                echo "<TD nowrap>$qtde_produtos</TD>\n";
            }

            if (strlen($defeito_constatado) > 0) {
                $sql1 = "SELECT descricao from tbl_defeito_constatado where defeito_constatado = $defeito_constatado";
                $res1 = pg_exec($con,$sql1);
                if (pg_numrows($res1) > 0)
                    $defeito_constatado_descricao = trim(pg_result ($res1,0,descricao));
                else
                    $defeito_constatado_descricao = '';
            }

            $sql_defeito = "select tbl_os_defeito_reclamado_constatado.defeito_constatado,
                            tbl_defeito_constatado.descricao
                            from tbl_os_defeito_reclamado_constatado
                            join tbl_defeito_constatado using (defeito_constatado)
                            where os=$os";

            $res_defeito = pg_exec($con,$sql_defeito);
            
            $num_defeito = pg_numrows($res_defeito);

            for ($w = 0; $w < $vet_constatado; $w++) {
                
                if ($w < $num_defeito) {
                    $defeito_descricao = pg_result($res_defeito,$w,'descricao');
                    echo "<td nowrap><acronym title='Defeito Constatado: $defeito_descricao' style='cursor: help;'>" . substr($defeito_descricao,0,30) . "</acronym></td>\n";
                } else {
                    echo "<td nowrap></td>\n";
                }

            }

            if ($login_fabrica == 14) {
                echo "<td nowrap>$obs</td>\n";
            }

            echo "</tr>\n";

        }

        echo "</table>\n";
        
        echo "<br />"; 
        
        echo "<table width='700' height='20' align='center'>
                <tr class='menu_top'>
                    <td align='center'>Total de " . pg_numrows($res) . " resultado(s) encontrado(s).</td>
                </tr>
            </table>"; */
	echo "<br>";
    }

}

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
