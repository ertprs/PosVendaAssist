<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "Relatório Evolução Contratual";

include "cabecalho.php";

?>

<style>
	.msg_erro{
		background-color: #ff0000;
		color: #fff;
		font-weight: bold;
	}
</style>



<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery_1.js"></script>
<script type="text/javascript" src="js/grafico/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>

<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>


<script type="text/javascript">

	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

	function mostrarPorDR() {
		$('#mostrar_por_dr').val('true');
		$('#frm_relatorio').submit();
	}

</script>

<?php

$btn_acao = '';
$google_table = '';
$tempo_solucao = '';
$tempo_falhas = '';
$grafico_dr_title = '';
$mostrar_por_dr = 'false';
$tms = array();
$tmf = array();

if (!empty($_POST['btn_acao'])) {
    $btn_acao = $_POST['btn_acao'];   
}
elseif (!empty($_POST['mostrar_por_dr'])) {
    $mostrar_por_dr = $_POST['mostrar_por_dr'];
}

if ($btn_acao == 'Pesquisar') {
    date_default_timezone_set('America/Sao_Paulo');
    $date = new DateTime();
    $data_final = $date->format('Y-m-d');
    $date->sub(new DateInterVal('P11M'));
    $data_inicial = $date->format('Y-m-01');

	$cliente_admin = (int) $_POST['cliente_admin'];

    if (!empty($cliente_admin)) {
        $and_aux = " and tbl_cliente_admin.cliente_admin = $cliente_admin ";

        $sql_cliente_admin_nome = "select nome from tbl_cliente_admin where fabrica = $login_fabrica and cliente_admin = $cliente_admin";
        $res_cliente_admin_nome = pg_query($con, $sql_cliente_admin_nome);
        $grafico_dr_title = 'DR: ' . pg_fetch_result($res_cliente_admin_nome, 0, 'nome');
    } else {
        $and_aux = "";
        $grafico_dr_title = 'Geral';
    }

    $sql1 = "select 0 as total,
                    0 as dentro_prazo,
                    0 as fora_prazo,
                    extract(month from to_char(('$data_final'::date - interval '11 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,
                    extract(year from to_char(('$data_final'::date - interval '11 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano
                into temp tmp_base_res_0x00
                from generate_series(0, 11) as s";

    $sql2 = "select 0 as dentro_prazo,
                    0 as fora_prazo,
                    count(tbl_hd_chamado.hd_chamado) as total,
                    extract (month from tbl_hd_chamado.data) as mes,
                    extract (year from tbl_hd_chamado.data) as ano
                into temp tmp_base_res_0x01
                from tbl_hd_chamado
                left join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado 
                left join tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade 
                left join tbl_cidade_sla on tbl_cidade.nome = tbl_cidade_sla.cidade 
                left join tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin 
                where tbl_hd_chamado.cliente_admin is not null 
                and tbl_hd_chamado.fabrica = $login_fabrica 
                and tbl_hd_chamado.data is not null 
                and tbl_hd_chamado.resolvido is not null 
                and tbl_hd_chamado.data between '$data_inicial 00:00:00' and '$data_final 23:59:00'
                $and_aux
                group by mes, ano order by ano, mes";

    $update = "update tmp_base_res_0x00 
                set total = tmp_base_res_0x01.total 
                from tmp_base_res_0x01 
                where tmp_base_res_0x00.ano = tmp_base_res_0x01.ano 
                and tmp_base_res_0x00.mes = tmp_base_res_0x01.mes";

    $res = pg_query($con, $sql1 . ' ; ' . $sql2);

    $tr = pg_query($con, "BEGIN");
    $qry = pg_query($con, $update);

    if (pg_affected_rows($qry) > 12) {
        $tr = pg_query($con, "ROLLBACK");
        exit('Erro ao gerar resultado. Código de erro: 0x01');
    } else {
        $tr = pg_query($con, "COMMIT");
    }

    $res = pg_query($con, "select mes, ano from tmp_base_res_0x00 where total <> 0");

    if (pg_num_rows($res) > 0) {
        $statmt = 'select tbl_cliente_admin.nome,
                        tbl_hd_chamado.hd_chamado,
                        tbl_hd_chamado.data,
                        tbl_hd_chamado.resolvido,
                        tbl_cidade.nome, 
                        case when tbl_cidade_sla.hora is null then 20 else tbl_cidade_sla.hora end,
                        tbl_os.finalizada
                    from tbl_hd_chamado
                    left join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado 
                    left join tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade 
                    left join tbl_cidade_sla on tbl_cidade.nome = tbl_cidade_sla.cidade 
                    left join tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin 
                    left join tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
                    where tbl_hd_chamado.cliente_admin is not null 
                    and tbl_hd_chamado.fabrica = ' . $login_fabrica . '
                    and tbl_hd_chamado.data is not null 
                    and tbl_hd_chamado.resolvido is not null 
                    and tbl_hd_chamado.data between $1 and $2 ' . $and_aux;
        $prepare = pg_prepare($con, "query", $statmt);

        if (!is_resource($prepare)) {
            exit('Erro ao gerar resultado. Código de erro: 0x02');
        }

        while ($fetch = pg_fetch_assoc($res)) {
            $mes = sprintf('%02d', $fetch['mes']);
            $ano = $fetch['ano'];

            $tms["$mes/$ano"] = array('chamados' => 0, 'minutos' => 0);

            $prox_mes = sprintf('%02d', $mes + 1);

            if ($prox_mes > 12) {
                $prox_mes = '01';
                $prox_ano = $ano + 1;
            } else {
                $prox_ano = $ano;
            }

            $inicial = $ano . '-' . $mes . '-01 00:00:00';
            $final = $prox_ano . '-' . $prox_mes . '-01 00:00:00';

            $exec = pg_execute($con, "query", array($inicial, $final));

            if (pg_num_rows($exec) > 0) {
                $dentroprazo = 0;
                $foraprazo = 0;
                $linhas = pg_num_rows($exec);

                $tms["$mes/$ano"]['chamados'] = $linhas;
                $tmf["$mes/$ano"]['chamados'] = $linhas;
                
                for ($i=0; $i<$linhas; $i++) {
                    $data = pg_fetch_result($exec, $i, 'data');
                    $resolvido = pg_fetch_result($exec, $i, 'resolvido');
                    $horas_sla = pg_fetch_result($exec, $i, 'hora');
                    $finalizada = pg_fetch_result($exec, $i, 'finalizada');

                    $time_inicial = $data;
                    $resx = "";
                    $horax = "";
                    $sair = "";
                    $qtd_horas = 0;

                    $hora_i = $data;
                    $hora_f = $resolvido;
                    $sair = 'nao';

                    $minutos_total = calculaMinutos($data, $resolvido);

                    if (!empty($finalizada)) {
                        $entre_falhas = calculaMinutos($data, $finalizada);
                    } else {
                        $entre_falhas = 0;
                    }

                    $tms["$mes/$ano"]['minutos']+= $minutos_total;
                    $tmf["$mes/$ano"]['minutos']+= $entre_falhas;

                    $qtd_horas = (int) ($minutos_total/60);

                    $total += 1;
                    
                    if($qtd_horas > $horas_sla){
                        $foraprazo+= 1;
                    } else {
                        $dentroprazo+= 1;
                    }
                }

                $tr = pg_query($con, "BEGIN");
                $up = 'UPDATE tmp_base_res_0x00 SET dentro_prazo = ' . $dentroprazo . ', fora_prazo = ' . $foraprazo . '
                        WHERE mes = ' . $fetch['mes'] . ' AND ano = ' . $fetch['ano'];
                $up = pg_query($con, $up);

                if (pg_affected_rows($up > 1)) {
                    $tr = pg_query($con, "ROLLBACK");
                } else {
                    $tr = pg_query($con, "COMMIT");
                }
            }

        }

        $sql = "SELECT * FROM tmp_base_res_0x00 ORDER BY ano, mes";
        $res = pg_query($con, $sql);

        while ($fetch = pg_fetch_assoc($res)) {
            $mes = sprintf('%02d', $fetch['mes']) . '/' . $fetch['ano'];
            $total = (int) $fetch['total'];
            $dentro_prazo = (int) $fetch['dentro_prazo'];
            $fora_prazo = (int) $fetch['fora_prazo'];

            $percent_dp = round(($dentro_prazo/$total) * 100, 2);
            $percent_fp = round(($fora_prazo/$total) * 100, 2);
            
            $google_table.= "['$mes', $percent_dp, $percent_fp],";

            if (array_key_exists($mes, $tms)) {
                $tms_mins = $tms[$mes]['minutos']/$tms[$mes]['chamados'];
                $tms_h = (int) ($tms_mins/60);
                $tms_m = $tms_mins%60;
                $tempo_solucao.= "['$mes', $tms_h.$tms_m],";
            } else {
                $tempo_solucao.= "['$mes', 0],";
            }

            if (array_key_exists($mes, $tmf)) {
                $tmf_mins = $tmf[$mes]['minutos']/$tmf[$mes]['chamados'];
                $tmf_h = (int) ($tmf_mins/60);
                $tmf_m = $tmf_mins%60;
                $tempo_falhas.= "['$mes', $tmf_h.$tmf_m],";
            } else {
                $tempo_falhas.= "['$mes', 0],";
            }
        }
     
    }
}
elseif ($mostrar_por_dr == 'true') {
    $sql_cliente_admin = "select cliente_admin, nome from tbl_cliente_admin where fabrica = $login_fabrica";
    $res_cliente_admin = pg_query($con, $sql_cliente_admin);
    $num_cliente_admin = pg_fetch_row($res_cliente_admin);

    date_default_timezone_set('America/Sao_Paulo');
    $date = new DateTime();
    $data_final = $date->format('Y-m-d');
    $date->sub(new DateInterVal('P11M'));
    $data_inicial = $date->format('Y-m-01');

    $google_table = array();
    $tempo_solucao = array();
    $tempo_falhas = array();

    if ($num_cliente_admin > 0) {
        $inc = 0;
        while ($fetch_cliente_admin = pg_fetch_assoc($res_cliente_admin)) {
            $cliente_admin = (int) $fetch_cliente_admin['cliente_admin'];
            $nome = $fetch_cliente_admin['nome'];

            $and_aux = " and tbl_cliente_admin.cliente_admin = $cliente_admin ";

            $sql1 = "select 0 as total,
                            0 as dentro_prazo,
                            0 as fora_prazo,
                            extract(month from to_char(('$data_final'::date - interval '11 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as mes,
                            extract(year from to_char(('$data_final'::date - interval '11 month'), 'YYYY-MM-DD')::date + s * interval '1 month') as ano
                        into temp tmp_base_res_0x00
                        from generate_series(0, 11) as s";

            $sql2 = "select 0 as dentro_prazo,
                            0 as fora_prazo,
                            count(tbl_hd_chamado.hd_chamado) as total,
                            extract (month from tbl_hd_chamado.data) as mes,
                            extract (year from tbl_hd_chamado.data) as ano
                        into temp tmp_base_res_0x01
                        from tbl_hd_chamado
                        left join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado 
                        left join tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade 
                        left join tbl_cidade_sla on tbl_cidade.nome = tbl_cidade_sla.cidade 
                        left join tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin 
                        where tbl_hd_chamado.cliente_admin is not null 
                        and tbl_hd_chamado.fabrica = $login_fabrica 
                        and tbl_hd_chamado.data is not null 
                        and tbl_hd_chamado.resolvido is not null 
                        and tbl_hd_chamado.data between '$data_inicial 00:00:00' and '$data_final 23:59:00'
                        $and_aux
                        group by mes, ano order by ano, mes";

            $update = "update tmp_base_res_0x00 
                        set total = tmp_base_res_0x01.total 
                        from tmp_base_res_0x01 
                        where tmp_base_res_0x00.ano = tmp_base_res_0x01.ano 
                        and tmp_base_res_0x00.mes = tmp_base_res_0x01.mes";

            $res = pg_query($con, $sql1 . ' ; ' . $sql2);

            $tr = pg_query($con, "BEGIN");
            $qry = pg_query($con, $update);

            if (pg_affected_rows($qry) > 12) {
                $tr = pg_query($con, "ROLLBACK");
                exit('Erro ao gerar resultado. Código de erro: 0x03');
            } else {
                $tr = pg_query($con, "COMMIT");
            }

            $res = pg_query($con, "select mes, ano from tmp_base_res_0x00 where total <> 0");

            if (pg_num_rows($res) > 0) {
                $google_table[$inc] = array(
                    'titulo' => 'Relatório de Evolução Contratual - ' . $nome,
                    'dados' => '',
                );

                $tempo_solucao[$inc] = array(
                    'titulo' => 'Tempo Médio Solução - ' . $nome,
                    'dados' => '',
                );

                $tempo_falhas[$inc] = array(
                    'titulo' => 'Tempo Médio entre Falhas - ' . $nome,
                    'dados' => '',
                );

                $statmt = 'select tbl_cliente_admin.nome,
                                tbl_hd_chamado.hd_chamado,
                                tbl_hd_chamado.data,
                                tbl_hd_chamado.resolvido,
                                tbl_cidade.nome,
                                case when tbl_cidade_sla.hora is null then 20 else tbl_cidade_sla.hora end,
                                tbl_os.finalizada
                            from tbl_hd_chamado
                            left join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado 
                            left join tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade 
                            left join tbl_cidade_sla on tbl_cidade.nome = tbl_cidade_sla.cidade 
                            left join tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin 
                            left join tbl_os on tbl_hd_chamado_extra.os = tbl_os.os
                            where tbl_hd_chamado.cliente_admin is not null 
                            and tbl_hd_chamado.fabrica = ' . $login_fabrica . '
                            and tbl_hd_chamado.data is not null 
                            and tbl_hd_chamado.resolvido is not null 
                            and tbl_hd_chamado.data between $1 and $2 ' . $and_aux;
                $query_nam = 'query_' . $inc;
                $prepare = pg_prepare($con, $query_nam, $statmt);

                if (!is_resource($prepare)) {
                    exit('Erro ao gerar resultado. Código de erro: 0x04');
                }

                while ($fetch = pg_fetch_assoc($res)) {
                    $mes = sprintf('%02d', $fetch['mes']);
                    $ano = $fetch['ano'];

                    $prox_mes = sprintf('%02d', $mes + 1);

                    if ($prox_mes > 12) {
                        $prox_mes = '01';
                        $prox_ano = $ano + 1;
                    } else {
                        $prox_ano = $ano;
                    }

                    $inicial = $ano . '-' . $mes . '-01 00:00:00';
                    $final = $prox_ano . '-' . $prox_mes . '-01 00:00:00';

                    $exec = pg_execute($con, $query_nam, array($inicial, $final));

                    if (pg_num_rows($exec) > 0) {
                        $dentroprazo = 0;
                        $foraprazo = 0;
                        $linhas = pg_num_rows($exec);

                        $tms["$mes/$ano"]['chamados'] = $linhas;
                        $tmf["$mes/$ano"]['chamados'] = $linhas;
                        
                        for ($i=0; $i<$linhas; $i++) {
                            $data = pg_fetch_result($exec, $i, 'data');
                            $resolvido = pg_fetch_result($exec, $i, 'resolvido');
                            $horas_sla = pg_fetch_result($exec, $i, 'hora');
                            $finalizada = pg_fetch_result($exec, $i, 'finalizada');

                            $time_inicial = $data;
                            $resx = "";
                            $horax = "";
                            $sair = "";
                            $qtd_horas = 0;

                            $hora_i = $data;
                            $hora_f = $resolvido;
                            $sair = 'nao';

                            $minutos_total = calculaMinutos($data, $resolvido);

                            if (!empty($finalizada)) {
                                $entre_falhas = calculaMinutos($data, $finalizada);
                            } else {
                                $entre_falhas = 0;
                            }

                            $tms["$mes/$ano"]['minutos']+= $minutos_total;
                            $tmf["$mes/$ano"]['minutos']+= $entre_falhas;

                            $qtd_horas = (int) ($minutos_total/60);

                            $total += 1;
                            
                            if($qtd_horas > $horas_sla){
                                $foraprazo+= 1;
                            } else {
                                $dentroprazo+= 1;
                            }
                        }

                        $tr = pg_query($con, "BEGIN");
                        $up = 'UPDATE tmp_base_res_0x00 SET dentro_prazo = ' . $dentroprazo . ', fora_prazo = ' . $foraprazo . '
                                WHERE mes = ' . $fetch['mes'] . ' AND ano = ' . $fetch['ano'];
                        $up = pg_query($con, $up);

                        if (pg_affected_rows($up > 1)) {
                            $tr = pg_query($con, "ROLLBACK");
                        } else {
                            $tr = pg_query($con, "COMMIT");
                        }
                    }
                }

                $sql = "SELECT * FROM tmp_base_res_0x00 ORDER BY ano, mes";
                $res = pg_query($con, $sql);

                while ($fetch = pg_fetch_assoc($res)) {
                    $mes = sprintf('%02d', $fetch['mes']) . '/' . $fetch['ano'];
                    $total = (int) $fetch['total'];
                    $dentro_prazo = (int) $fetch['dentro_prazo'];
                    $fora_prazo = (int) $fetch['fora_prazo'];

                    $percent_dp = round(($dentro_prazo/$total) * 100, 2);
                    $percent_fp = round(($fora_prazo/$total) * 100, 2);
                    
                    $google_table[$inc]['dados'].= "['$mes', $percent_dp, $percent_fp],";

                    if (array_key_exists($mes, $tms)) {
                        $tms_mins = $tms[$mes]['minutos']/$tms[$mes]['chamados'];
                        $tms_h = (int) ($tms_mins/60);
                        $tms_m = $tms_mins%60;
                        $tempo_solucao[$inc]['dados'].= "['$mes', $tms_h.$tms_m],";
                    } else {
                        $tempo_solucao[$inc]['dados'].= "['$mes', 0],";
                    }

                    if (array_key_exists($mes, $tmf)) {
                        $tmf_mins = $tmf[$mes]['minutos']/$tmf[$mes]['chamados'];
                        $tmf_h = (int) ($tmf_mins/60);
                        $tmf_m = $tmf_mins%60;
                        $tempo_falhas[$inc]['dados'].= "['$mes', $tmf_h.$tmf_m],";
                    } else {
                        $tempo_falhas[$inc]['dados'].= "['$mes', 0],";
                    }

                }
               
            }

            $inc++;

            $drops = pg_query($con, "DROP TABLE tmp_base_res_0x00; DROP TABLE tmp_base_res_0x01;");
        }

    }

    $cliente_admin = 0;

}

/* select dr's */

$sql = "select cliente_admin,nome from tbl_cliente_admin where fabrica = $login_fabrica";
$res_cliente_admin = pg_exec($sql);
if (pg_num_rows($res_cliente_admin)>0) {
	$linhas = pg_num_rows($res_cliente_admin);
	for($i=0;$i<$linhas;$i++){
		$xcliente_admin = pg_result($res_cliente_admin,$i,cliente_admin);
		$xnome = pg_result($res_cliente_admin,$i,nome);
		if($cliente_admin == $xcliente_admin){
			$selected = "selected";
		}else{
			$selected = "";
		}
		$option_drs .= "<option $selected value='$xcliente_admin'>$xnome</option>";
	}
} else {
	$option_drs = "";
}

/*-------------*/

?>

<FORM id="frm_relatorio" name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<? if(strlen($msg_erro)>0){ ?>
		<tr class='msg_erro'><td><? echo $msg_erro ?></td></tr>
	<? } ?>
	<tr class='titulo_tabela'>
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>

				<tr>
					<td style="width: 350px; text-align: right;">
                        DR's
						<select style='width:180px' name="cliente_admin">
							<option>Geral</option>
							<?php
							echo $option_drs;
							?>
						</select>
					</td>
					<td style="width: 350px;">
                        <div style="margin-left: 40px;">
                        	<?php
                        	if ($grafico_dr_title == 'Geral') {
                        		echo '<input type="hidden" id="mostrar_por_dr" name="mostrar_por_dr" value="" />';
                        	}
                        	?>
                            <input type='submit' style="cursor:pointer" name='btn_acao' value='Pesquisar'>
                        </div>
                    </td>
				</tr>
			</table><br>
		</td>
	</tr>
</table>
</FORM>

<br />

<div id="container" style="width: 700px; margin: 0 auto">
	<?php
if (!empty($google_table)) {
	$google_table = preg_replace('/,$/','',$google_table);
        if (is_array($google_table)) {
            ?>
            <script type="text/javascript" src="https://www.google.com/jsapi"></script>
                <script type="text/javascript">
                  google.load('visualization', '1', {packages: ['corechart']});
                </script>
                <script type="text/javascript">
                <?php
                    foreach ($google_table as $key => $value) {
                    ?>
                    function drawVisualization<?php echo $key ?>() {
                        var formatter = new google.visualization.NumberFormat({ suffix: "%"}); 

                        // Some raw data (not necessarily accurate)
                        var data = google.visualization.arrayToDataTable([
                            ['Mês', 'Dentro do Prazo', 'Fora do Prazo'],
                            <?php echo $value['dados']; ?>
                        ]);

                        formatter.format(data, 1);
                        formatter.format(data, 2);

                        var options = {
                            title : '<?php echo $value['titulo'] ?>',
                            width: 1000
                        };

                        var chart = new google.visualization.LineChart(document.getElementById('div_grafico_<?php echo $key ?>'));
                        chart.draw(data, options);
                      }
                      google.setOnLoadCallback(drawVisualization<?php echo $key ?>);
                    <?php
                    }

                    foreach ($tempo_solucao as $key => $value) {
                    ?>
                    function drawVisualizationTMS<?php echo $key ?>() {
                        var data = new google.visualization.DataTable();
                        var formatter = new google.visualization.NumberFormat({ decimalSymbol: ","}); 

                        data.addColumn('string', 'Mês');
                        data.addColumn('number', 'Tempo Médio Solução');

                        data.addRows(
                        [
                            <?php echo $value['dados']; ?>
                        ]);

                        formatter.format(data, 1);

                        var options = {
                           title : '<?php echo $value['titulo'] ?> - Em horas',
                           width: 1000,
                        };

                        var chart = new google.visualization.LineChart(document.getElementById('div_grafico_tms_<?php echo $key ?>'));
                        chart.draw(data, options);

                      }
                      google.setOnLoadCallback(drawVisualizationTMS<?php echo $key ?>);
                    <?php
                    }

                    foreach ($tempo_falhas as $key => $value) {
                        ?>
                        function drawVisualizationTMF<?php echo $key ?>() {
                            var data = new google.visualization.DataTable();
                            var formatter = new google.visualization.NumberFormat({ decimalSymbol: ","}); 

                            data.addColumn('string', 'Mês');
                            data.addColumn('number', 'Tempo Médio entre Falhas');

                            data.addRows(
                            [
                                <?php echo $value['dados']; ?>
                            ]);

                            formatter.format(data, 1);

                            var options = {
                               title : '<?php echo $value['titulo'] ?> - Em horas',
                               width: 1000,
                            };

                            var chart = new google.visualization.LineChart(document.getElementById('div_grafico_tmf_<?php echo $key ?>'));
                            chart.draw(data, options);

                          }
                          google.setOnLoadCallback(drawVisualizationTMF<?php echo $key ?>);
                        <?php
                    }
                ?>
                </script>
            <?php
        } else {

    		?>

    	    <!-- Grafico -->
    		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    	    <script type="text/javascript">
    	      google.load('visualization', '1', {packages: ['corechart']});
    	    </script>
    	    <script type="text/javascript">
    	      function drawVisualization() {
                var formatter = new google.visualization.NumberFormat({ suffix: "%"}); 

    	        // Some raw data (not necessarily accurate)
    	        var data = google.visualization.arrayToDataTable([
    	        	['Mês', 'Dentro do Prazo', 'Fora do Prazo'],
    	          	<?php echo $google_table; ?>
    	        ]);

               formatter.format(data, 1);
               formatter.format(data, 2);

    	        var options = {
    	           title : 'Relatório de Evolução Contratual - <?php echo $grafico_dr_title ?> - %',
    	           width: 1000
    	        };

    	        var chart = new google.visualization.LineChart(document.getElementById('div_grafico'));
    	        chart.draw(data, options);
    	      }

              function drawVisualizationTMS() {
                // Some raw data (not necessarily accurate)
                var data = new google.visualization.DataTable();
                var formatter = new google.visualization.NumberFormat({ decimalSymbol: ","}); 

                data.addColumn('string', 'Mês');
                data.addColumn('number', 'Tempo Médio Solução');

                data.addRows(
                [
                    <?php echo $tempo_solucao; ?>
                ]);

                formatter.format(data, 1);

                var options = {
                   title : 'Tempo Médio Solução - Em horas',
                   width: 1000,
                };

                var chart = new google.visualization.LineChart(document.getElementById('div_grafico_tms'));
                chart.draw(data, options);
              }

              function drawVisualizationTMF() {
                // Some raw data (not necessarily accurate)
                var data = new google.visualization.DataTable();
                var formatter = new google.visualization.NumberFormat({ decimalSymbol: ","}); 

                data.addColumn('string', 'Mês');
                data.addColumn('number', 'Tempo Médio entre Falhas');

                data.addRows(
                [
                    <?php echo $tempo_falhas; ?>
                ]);

                formatter.format(data, 1);

                var options = {
                   title : 'Tempo Médio entre Falhas',
                   width: 1000,
                };

                var chart = new google.visualization.LineChart(document.getElementById('div_grafico_tmf'));
                chart.draw(data, options);
              }

    	      google.setOnLoadCallback(drawVisualization);
              google.setOnLoadCallback(drawVisualizationTMS);
              google.setOnLoadCallback(drawVisualizationTMF);
    	    </script>



    		<?php
        }

	} else {
		if($btn_acao != ""){
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
	?>

    <?php
     if (!empty($google_table)) {
	$google_table = preg_replace('/,$/','',$google_table);
        if (is_array($google_table)) {
            $end = count($google_table);
            for ($i=0; $i < $end; $i++) { 
                echo '<div id="div_grafico_' . $i . '" style="width: 1050px; margin-left: -150px;"></div><br/>';
                echo '<div id="div_grafico_tms_' . $i . '" style="width: 1050px; margin-left: -150px;"></div><br/>';
                echo '<div id="div_grafico_tmf_' . $i . '" style="width: 1050px; margin-left: -150px;"></div><br/>';
            }
        } else {
            ?>
            <div id="div_grafico" style="width: 1050px; margin-left: -150px;"></div>
            <div id="div_grafico_tms" style="width: 1050px; margin-left: -150px;"></div>
            <div id="div_grafico_tmf" style="width: 1050px; margin-left: -150px;"></div>
            <?php
        }
    }

	if ($grafico_dr_title == 'Geral') {
		echo '<br/><span style="font-weight: bold; cursor: pointer;" onClick="mostrarPorDR()">Mostrar por DR</span>';
	}
	?>
</div>

<?php

include "rodape.php";

#Função de Calculo de Horas

function calculaMinutos($datainicial,$datafinal){


	$arr_datainicial = getdate(strtotime($datainicial));
	$arr_datafinal = getdate(strtotime($datafinal));


	$inicial_ob = new DateTime($arr_datainicial['year'].'-'.$arr_datainicial['mon'].'-'.$arr_datainicial['mday'].' '.$arr_datainicial['hours'].':'.$arr_datainicial['minutes']);

	$final_ob = new DateTime($arr_datafinal['year'].'-'.$arr_datafinal['mon'].'-'.$arr_datafinal['mday'].' '.$arr_datafinal['hours'].':'.$arr_datafinal['minutes']);

	$dia_ini = $inicial_ob->format('d');
	$hora_ini = $inicial_ob->format('H');
	$minuto_ini = $inicial_ob->format('i');

	$dia_fim = $final_ob->format('d');
	$hora_fim = $final_ob->format('H');
	$minuto_fim = $final_ob->format('i');



	if($inicial_ob->format('H') <= 7 and $inicial_ob->format('i') < 30){
		$inicial_ob->setTime(7,30);
	}

	if($final_ob->format('D') == 'Sat'){
		if($final_ob->format('H') > 16 and $final_ob->format('i') > 00){
			$final_ob->setTime(16,00);
		}
	}else{
		if($final_ob->format('H') > 20 and $final_ob->format('i') >= 00){
			$final_ob->setTime(20,00);
		}
	}

	// echo $inicial_ob->format('d-m-y H:i')."<br>";
	// echo $final_ob->format('d-m-y H:i')."<br><br>";
	$ano_ok = false;
	$mes_ok = false;
	$dia_ok = false;
	$minutos = 0;
	$minutos_aux = 0;
	if($inicial_ob->format('d') != $final_ob->format('d')){
		if($inicial_ob->format('D') == 'Sat'){
			$hora_final_diaria = 16;
		}else{
			$hora_final_diaria = 20;
		}

		$minuto_final_diario = 00;
	}else{
		$hora_final_diaria = $final_ob->format('H');
		$minuto_final_diario = $final_ob->format('i');
	}

	$dias_validos = 0;
	$minutos_validos = 0;
	$minutos_validos_sabado = 0;

	$hora_final_diaria = 24;
	$minuto_final_diario = 61;
	if($inicial_ob->format('m') != $final_ob->format('m')){
		$mf = $final_ob->format('m');
		for($m = $inicial_ob->format('m');$m<=$mf;$m++){
			if($inicial_ob->format('m') != $final_ob->format('m')){
				$df = date("d",mktime(0, 0, 0, ($inicial_ob->format('m') + 1), 0, $inicial_ob->format('Y')));
			}else{
				$df = $final_ob->format('d');
			}
			// echo "df $df <br>";
			//$df = $final_ob->format('d');
			for($d = $inicial_ob->format('d');$d<=$df;$d++){
				// echo "*".$d."<br>";
				for($h = $inicial_ob->format('H');$h<=$hora_final_diaria;$h++){
					// echo $h."-(";
					if($h == $hora_final_diaria){
						$xminuto_final_diario = $minuto_final_diario;
					}else{
						$xminuto_final_diario = 61;
					}
					for($i=$inicial_ob->format('i');$i<$xminuto_final_diario;$i++){
						$minutos += 1;
						$minutos_aux +=1;
						//$inicial_ob->add(new DateInterVal('PT1M'));
						if($inicial_ob->format('D') != 'Sun'){
							#diff de sabado
							if($inicial_ob->format('D') != 'Sat'){
								#se hora atual > 7 e < 20 (periodo de trabalho)
								if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') < 20){
									#se dia atual == dia de inicio ou dia de fim
									if($inicial_ob->format('d') == $dia_ini || $inicial_ob->format('d') == $dia_fim){
										#se hora atual >= hora inicial e <= a hora final
										if($inicial_ob->format('d') == $dia_ini){
											if($inicial_ob->format('H') == $hora_ini ){
												if($inicial_ob->format('i')>=$minuto_ini){
													// echo $i."a ";
													$minutos_validos +=1;
												}
											}else{
												// echo $i."b ";
												$minutos_validos +=1;
											}
										}
										if($inicial_ob->format('d') == $dia_fim){
											if($inicial_ob->format('H') == $hora_fim ){
												if($inicial_ob->format('i')<=$minuto_fim){
													// echo $i."c' ";
													$minutos_validos +=1;
												}
											}else{
												// echo $i."d ";
												$minutos_validos +=1;
											}
										}
									}else{
										if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
											// echo $i."e ";
											$minutos_validos +=1;
										}elseif ($inicial_ob->format('H') > 7 and $inicial_ob->format('H') < 20 ) {
											// echo $i."f ";
											$minutos_validos +=1;
										}
									}
								}
							}else{
								if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') <= 16){
									if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
										// echo $i."g ";
										$minutos_validos_sabado +=1;
									}elseif ($inicial_ob->format('H') > 7) {
										// echo $i."h ";
										$minutos_validos_sabado +=1;
									}
								}
							}
							$dias_validos +=1;
						}
						$inicial_ob->add(new DateInterVal('PT1M'));
					}
						// echo ") <br>";
				}
			}
		}
	}else{
		$df = $final_ob->format('d');
		$hora_final_diaria = 24;
		$minuto_final_diario = 61;
		for($d = $inicial_ob->format('d');$d<=$df;$d++){
			// echo "*".$d."<br>";
			for($h = $inicial_ob->format('H');$h<=$hora_final_diaria;$h++){
				// echo $h."-(";
				if($h == $hora_final_diaria){
					$xminuto_final_diario = $minuto_final_diario;
				}else{
					$xminuto_final_diario = 61;
				}
				for($i=$inicial_ob->format('i');$i<$xminuto_final_diario;$i++){
					$minutos += 1;
					$minutos_aux +=1;
					// $inicial_ob->add(new DateInterVal('PT1M'));
					if($inicial_ob->format('D') != 'Sun'){
							#diff de sabado
						if($inicial_ob->format('D') != 'Sat'){
								#se hora atual > 7 e < 20 (periodo de trabalho)
							if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') < 20){
								if($dia_ini != $dia_fim){
										#se dia atual == dia de inicio ou dia de fim
									if($inicial_ob->format('d') == $dia_ini || $inicial_ob->format('d') == $dia_fim){
											#se hora atual >= hora inicial e <= a hora final
										if($inicial_ob->format('d') == $dia_ini){
											if($inicial_ob->format('H') == $hora_ini ){
												if($inicial_ob->format('i')>=$minuto_ini){
													// echo $i."a ";
													$minutos_validos +=1;
												}
											}else{
												// echo $i."b ";
												$minutos_validos +=1;
											}
										}
										if($inicial_ob->format('d') == $dia_fim){
											if($inicial_ob->format('H') == 7){
												if($inicial_ob->format('i')>=30){
													// echo $i."c1' ";
													$minutos_validos +=1;
												}
											}elseif($inicial_ob->format('H') == $hora_fim ){
												if($inicial_ob->format('i')<=$minuto_fim){
													// echo $i."c' ";
													$minutos_validos +=1;
												}
											}else{
												if($inicial_ob->format('H') < $hora_fim){
													// echo $i."d ";
													$minutos_validos +=1;
												}
											}
										}
									}else{
										if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
											// echo $i."e ";
											$minutos_validos +=1;
										}elseif ($inicial_ob->format('H') > 7 and $inicial_ob->format('H') < 20 ) {
											// echo $i."f ";
											$minutos_validos +=1;
										}
									}
								}else{
									if($inicial_ob->format('H') == $hora_ini ){
										if($inicial_ob->format('i')>=$minuto_ini){
											// echo $i."i ";
											$minutos_validos +=1;
										}
									}elseif($inicial_ob->format('H') == $hora_fim ){
										if($inicial_ob->format('i')<=$minuto_fim){
											// echo $i."k' ";
											$minutos_validos +=1;
										}
									}else{
										if($inicial_ob->format('H') >= $hora_ini and $inicial_ob->format('H') <= $hora_fim){
											// echo $i."m ";
											$minutos_validos +=1;
										}

									}
								}
							}

						}
					}else{
						if($inicial_ob->format('H') >= 7 and $inicial_ob->format('H') <= 16){
							if($inicial_ob->format('H') == 7 and $inicial_ob->format('i') >= 30){
								// echo $i."g ";
								$minutos_validos_sabado +=1;
							}elseif ($inicial_ob->format('H') > 7) {
								// echo $i."h ";
								$minutos_validos_sabado +=1;
							}
						}
					}
					$dias_validos +=1;
					$inicial_ob->add(new DateInterVal('PT1M'));
				}
				// $inicial_ob->add(new DateInterVal('PT1M'));
				// echo ") <br>";
			}
		}
	}

	return $minutos_validos+$minutos_validos_sabado;

}

?>
