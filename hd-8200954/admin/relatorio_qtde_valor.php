<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";

$admin_privilegios="gerencia";

$btn_acao = trim($_REQUEST['btn_acao']);

if (isset($argv[1])) {
    parse_str($argv[1], $get);
}


$title = "RELATÓRIO QUANTIDADE / VALOR DE OS POR TIPO ATENDIMENTO";
$layout_menu = "gerencia";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

if ($btn_acao == 'pesquisar' || $gera_automatico == "automatico") {

    $litar = '';
    $codigo_posto = trim($_REQUEST["codigo_posto"]);
    $tipo_data = trim($_REQUEST["tipo_data"]);
    $pais = trim($_REQUEST['pais']);

    if (count($msg_erro) == 0) {
        if (strlen($_REQUEST["data_inicial_01"]) == 0) {
            $msg_erro['msg'][] = "É necessário digitar uma data inicial";
            $msg_erro["campos"][] = "data_inicial_01";
        }

        if(count($msg_erro) == 0){
            list($d, $m, $y) = explode("/", $_REQUEST["data_inicial_01"]);
            if(!checkdate($m,$d,$y)) {
                $msg_erro['msg'][] = "Data Inválida";
                $msg_erro["campos"][] = "data_inicial_01";
            }
        }

        if (count($msg_erro) == 0) {
            $data_inicial = trim($_REQUEST["data_inicial_01"]);
            $fnc = pg_query($con,"SELECT fnc_formata_data('$data_inicial')");

            if (strlen(pg_errormessage($con)) > 0) {
                $msg_erro['msg'][] = pg_errormessage($con);
                $msg_erro["campos"][] = "data_inicial_01";
            }

            if (count($msg_erro) == 0) $aux_data_inicial = pg_fetch_result($fnc,0,0);
        }
    }

    if (count($msg_erro) == 0) {
        if (strlen($_REQUEST["data_final_01"]) == 0) {
            $msg_erro['msg'][] = "É necessário digitar uma data final";
            $msg_erro["campos"][] = "data_final_01";
        }

        if(count($msg_erro) == 0){
            list($d, $m, $y) = explode("/", $_REQUEST["data_final_01"]);
            if(!checkdate($m,$d,$y)) {
                $msg_erro['msg'][] = "Data Inválida";
                $msg_erro["campos"][] = "data_final_01";
            }
        }

        if (count($msg_erro) == 0) {
            $data_final   = trim($_REQUEST["data_final_01"]);
            $fnc            = pg_query($con,"SELECT fnc_formata_data('$data_final')");

            if (strlen(pg_errormessage($con)) > 0) {
                $msg_erro['msg'][] = pg_errormessage($con);
                $msg_erro["campos"][] = "data_final_01";
            }

            if (count($msg_erro) == 0) $aux_data_final = pg_fetch_result($fnc,0,0);
        }
    }

    if (count($msg_erro) == 0 && (strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0)) {
        $sqlPeriodo = "SELECT '$aux_data_inicial'::DATE > '$aux_data_final'::DATE";
        $resPeriodo = pg_query($con,$sqlPeriodo);
        if(pg_fetch_result($resPeriodo,0,0) == 't') {
            $msg_erro['msg'][] = "O período entre as datas não é válido";
            $msg_erro["campos"][] = "data_inicial_01";
            $msg_erro["campos"][] = "data_final_01";
        }
    }

    if (strlen($codigo_posto) == 0 && $gera_automatico != "automatico") {

        if(strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0){
             if (in_array($login_fabrica, array(20))) {
                $sql = "SELECT '$aux_data_inicial'::DATE + INTERVAL '12 months' > '$aux_data_final'::DATE";
            } else { 
                $sql = "SELECT '$aux_data_inicial'::DATE + INTERVAL '3 months' > '$aux_data_final'::DATE";
            }

            $res = pg_query($con,$sql);

            if(pg_fetch_result($res,0,0) == 'f') {
                //HD-2552976
                if (in_array($login_fabrica, array(20))) {
                    $msg_erro['msg'][] = "Periodo maior que 12 meses, digite um periodo menor que 3 meses ou informe o nome do posto.";
                } else {
                    $msg_erro['msg'][] = "Periodo maior que 3 meses, digite um periodo menor que 3 meses ou informe o nome do posto.";
                    // $agendar = 1;
                }
                /* $msg_erro["campos"][] = "data_inicial_01";
                $msg_erro["campos"][] = "data_final_01"; */
            }
        }
    }

    if (count($msg_erro) == 0) $listar = "ok";

    if (count($msg_erro) > 0) {
        $data_inicial = trim($_REQUEST["data_inicial_01"]);
        $data_final   = trim($_REQUEST["data_final_01"]);
        $tipo_data = trim($_REQUEST['tipo_data']);
        $pais = trim($_REQUEST['pais']);
        $listar = "";
    }

}
?>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $.datepickerLoad(Array("data_final_01", "data_inicial_01"));
        $.autocompleteLoad(Array("posto"));

        var table = new Object();
        table['table'] = '#relatorio_pesquisa';
        table['type'] = 'basic';
        $.dataTableLoad(table);

        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#posto_nome").val(retorno.nome);
    }
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
    ?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
    <?php
}

if(strlen($msg_sucesso) > 0){
    echo "<div class='alert alert-success'>{$msg_sucesso}</div>";
}
?>

<?php
if (strlen($msg_aguard) > 0 && $agendar != 1) {
    echo $msg_aguard;
}
?>

<form name="frm_pesquisa" method="POST" action="<? echo $PHP_SELF ?>" class="form-search form-vertical tc_formulario">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br />
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_inicial_01", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial_01'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final_01", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final_01'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span8'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='posto_nome'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <?php
    if ($login_fabrica == 20) {

        $sql   = 'SELECT pais,nome FROM tbl_pais where america_latina is TRUE';
        $res   = pg_query($con,$sql);
        $p_tot = pg_num_rows($res);

        for ($i = 0; $i < $p_tot; $i++) {
            list($p_code,$p_nome) = pg_fetch_row($res, $i);
            $sel_paises .= "\t\t\t\t<option value='$p_code'";
            $sel_paises .= ($pais==$p_code)?" selected":"";
            $sel_paises .= ">$p_nome</option>\n";
        }

        ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span8'>
                <div class='control-group'>
                    <label class='control-label' for='pais'>País</label>
                    <div class='controls controls-row'>
                        <select name='pais' size='1' onchange="javascript: if (this.value != 'BR' && this.value != '') {
                        document.getElementById('tipo_datae').disabled=true; document.getElementById('tipo_datae').checked=false; } else { document.getElementById('tipo_datae').disabled=false; }">
                        <option value=""></option>
                        <?echo $sel_paises;?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='tipo_data'>Tipo de Data</label>
                <div class='controls controls-row'>
                    <input type='radio' name='tipo_data' id='tipo_datae' value='exportacao' <? if ($tipo_data=='exportacao') echo " checked"; if ($pais != 'BR' && $pais != '') echo " disabled"; ?> />
                    Data Exportação
                    <span title="Data exportação do PA para Bosch" style="color:red;font-weight:bold"><img src="imagens/help.png" /></span>&nbsp;

                    <input type='radio' name='tipo_data' value='geracao'<? if($tipo_data=='geracao' or strlen($tipo_data)==0) echo " checked"; ?> />
                    Data Geração
                    <span title=" Data de criação de extrato" style="color:red;font-weight:bold"><img src="imagens/help.png" /></span>&nbsp;

                    <input type='radio' name='tipo_data' value='aprovacao'<? if($tipo_data=='aprovacao') echo " checked"; ?> />
                    Data Aprovação
                    <span title="Data de aprovação de extratos aprovados da OS" style="color:red;font-weight:bold"><img src="imagens/help.png" /></span>&nbsp;

                    <input type='radio' name='tipo_data' value='finalizada'<? if($tipo_data=='finalizada') echo " checked"; ?> />
                    Data Finalizada
                    <span title="OS's que foram fechadas dentro do período" style="color:red;font-weight:bold"><img src="imagens/help.png" /></span>&nbsp;
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
<p>
    <input type='hidden' name='btn_acao' />
    <input type="button" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '') { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit(); } else { alert('Aguarde o término da pesquisa...'); }" value="Pesquisar" class='btn btn-default' />
</p>
<br />
</form>

<?php

if($listar == 'ok' && $agendar != 1){
    if(strlen($codigo_posto) > 0){
        $sql = "SELECT  posto
        FROM    tbl_posto_fabrica
        WHERE fabrica = $login_fabrica
        AND codigo_posto = '$codigo_posto';";
//echo "sql: $sql";

        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $posto = trim(pg_fetch_result($res,0,posto));
        }
    }

	$datas = relatorio_data("$aux_data_inicial","$aux_data_final");

    $joinTblOs = "";


    if (strlen($pais) > 0) {
        $joinTblPosto = " JOIN tbl_posto ON tbl_posto.posto = tmp_valor_os_$login_admin.posto
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $cond4 = " AND tbl_posto.pais = UPPER('$pais')";
        $labelPais = "";
        $joinTblPostoPais = "";
        $groupBy = "GROUP BY tipo_atendimento";
        $labelResult = "";
        $orderByResult = " ORDER BY descricao";
    } else {
        $joinTblPosto = "";
        $cond4 = "";
        $labelPais = ", tbl_posto.pais";
        $joinTblPostoPais = " JOIN tbl_posto ON tbl_posto.posto = tmp_valor_os_$login_admin.posto
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $groupBy = "GROUP BY tipo_atendimento, tbl_posto.pais";
        $labelResult = ", tmp_valor_os_at_$login_admin.pais";
        $orderByResult = " ORDER BY tmp_valor_os_at_$login_admin.pais";
    }

    if(strlen($posto ) > 0){
        $cond1 = " AND tbl_extrato.posto = $posto";
        $cond2 = " AND posto = $posto";
    }else{
        $cond1 = " AND 1 = 1";
        $cond2 = " AND 1 = 1";

    }

	$conta = 0 ;
	foreach($datas as $data_pesquisa) {
		$aux_data_inicial = $data_pesquisa[0];
		$aux_data_final = $data_pesquisa[1];
		if (strlen($tipo_data) > 0) {

			switch ($tipo_data) {
				case 'exportacao':
				$cond3 = " AND tbl_os.posto = tbl_extrato.posto AND tbl_extrato_extra.exportado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
				break;

				case 'geracao':
				$cond3 = " AND tbl_os.posto = tbl_extrato.posto AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
				break;

				case 'aprovacao':
				$cond3 = " AND tbl_os.posto = tbl_extrato.posto AND tbl_extrato.aprovado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
				break;

				case 'finalizada':
				$cond3 = " AND tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
				break;

				default:
				$cond3 = " AND 1 = 1";
				break;
			}

		} else {
			$cond3 = " AND tbl_os.posto = tbl_extrato.posto AND tbl_extrato_extra.exportado BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
		}
		if($conta == 0) {
			$cria_temp = " CREATE TEMP TABLE tmp_valor_os_$login_admin (os int4, mao_de_obra float, pecas float,tipo_atendimento int4,posto int4);";
		}else{
			$cria_temp = "";
		}

		$sql = "$cria_temp 

				insert into tmp_valor_os_$login_admin(os, mao_de_obra, pecas,tipo_atendimento,posto)
				SELECT tbl_os_extra.os, tbl_os.mao_de_obra, tbl_os.pecas,tbl_os.tipo_atendimento,tbl_os.posto
				FROM tbl_os_extra
				JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = $login_fabrica
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_os.excluida is not true
				$cond3
				$cond1;";
		$res = pg_query($con,$sql);
		$conta++;
	}

    $sql = "

        CREATE INDEX tmp_valor_os_OS_$login_admin ON tmp_valor_os_$login_admin(os);

        SELECT tipo_atendimento,
        COUNT(*) AS qtde,
        sum(mao_de_obra) AS total_mao_de_obra,
        sum(pecas) AS total_pecas
        $labelPais
        INTO TEMP tmp_valor_os_at_$login_admin
		FROM tmp_valor_os_$login_admin
		$joinTblPosto
		$joinTblPostoPais
		WHERE 1 =1
		$cond2
		$cond4
        $groupBy;

        CREATE INDEX tmp_tipo_at ON tmp_valor_os_at_$login_admin(tipo_atendimento);

        SELECT tbl_tipo_atendimento.tipo_atendimento,
        tbl_tipo_atendimento.descricao,
        tmp_valor_os_at_$login_admin.qtde,
        tmp_valor_os_at_$login_admin.total_mao_de_obra,
        tmp_valor_os_at_$login_admin.total_pecas
        $labelResult
        FROM tbl_tipo_atendimento
        LEFT JOIN tmp_valor_os_at_$login_admin ON tmp_valor_os_at_$login_admin.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
        WHERE tbl_tipo_atendimento.fabrica = $login_fabrica 
        AND tbl_tipo_atendimento.descricao != 'Cortesia Técnica' 
        $orderByResult;";

    flush();

    $res = pg_query($con,$sql);

    if(pg_num_rows($res)>0){
        echo "<br /><table id='relatorio_pesquisa' class='table table-striped table-bordered table-hover table-large' style='width: 100%;'>";
        echo "<thead>";
        echo "<tr class='titulo_coluna'>";
        if (strlen($pais) == 0) {
            echo "<th>País</th>";
        }
        echo "<th>Descrição</th>";
        echo "<th>Qtde</th>";
        echo "<th>Total MO</th>";
        echo "<th>Total Peças</th'>";
        echo "</tr>";
        echo "</thead>";

        echo "<tbody>";
        for ($i = 0; $i < pg_num_rows($res); $i++){
            $descricao          = trim(pg_fetch_result($res,$i,descricao));
            $qtde               = trim(pg_fetch_result($res,$i,qtde));
            $total_mao_de_obra  = trim(pg_fetch_result($res,$i,total_mao_de_obra));
            $total_pecas        = trim(pg_fetch_result($res,$i,total_pecas));
            if (strlen($pais) == 0) {
                $xpais                   = trim(pg_fetch_result($res, $i, pais));
            }

            echo "<tr>";
            if (strlen($pais) == 0) {
                echo "<td align='left'>$xpais</td>";
            }
            echo "<td align='left'>$descricao</td>";
            echo "<td align='right'>";
            if(strlen($qtde)>0) echo $qtde; else echo "0";
            echo "</td>";
            echo "<td align='right'>".number_format($total_mao_de_obra,2,".",".")."</td>";
            echo "<td align='right'>".number_format($total_pecas,2,".",".")."</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo " </table>";
    }
}

include "rodape.php"

?>
