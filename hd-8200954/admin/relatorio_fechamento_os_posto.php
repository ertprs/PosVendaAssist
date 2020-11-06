<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico') {
    include "autentica_admin.php";
}

if ($_POST['ajax'] == 'excluir' and $_POST['excluir']=='arquivo') {
    $nome_arquivo = $_POST['nome'];
    if (!file_exists($nome_arquivo)) die('nofile');
    if (file_exists($nome_arquivo)) $deu_certo = unlink($nome_arquivo);
    $ret = ($deu_certo) ? 'ok' : 'ko';
    exit($ret);
}

//include "gera_relatorio_pararelo_include.php";
include 'funcoes.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CONTROLE DE FECHAMENTO O.S";

if (count($_POST)>1) {
    $data_ini     = $_POST['data_ini'];
    $data_fim     = $_POST['data_fim'];
    $codigo_posto = $_POST['codigo_posto'];

    if (strlen($data_ini) > 0 && strlen($data_fim) > 0) {
        
        list($di, $mi, $yi) = explode("/", $data_ini);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";

        list($df, $mf, $yf) = explode("/", $data_fim);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
        
        if(strlen($msg_erro)==0){
            $xdata_ini = "$yi-$mi-$di";
            $xdata_fim = "$yf-$mf-$df";
        }
        if(strlen($msg_erro)==0){
            if(strtotime($xdata_fim) < strtotime($xdata_ini)){
                $msg_erro = "Data Inválida.";
            }
        }

        if(strlen($msg_erro)==0){
            $sql = "SELECT '$xdata_ini'::date >= '$xdata_fim'::date - INTERVAL '3 month'";
            $res = pg_query($con,$sql);
            if (pg_fetch_result($res,0,0) == 'f') {
                $msg_erro = 'O intervalo entre as datas não pode ser <b>maior que 3 mês.</b>';
            }
         }
    } else {
        if (!empty($_POST) and !isset($_POST['include'])) {
            $msg_erro = 'O campo <b>Data Inicial</b> e <b>Data Final</b> é Obrigatório!';
        }
    }

/*   if (!empty($_POST) && $data_ini && $data_fim && strlen($msg_erro) == 0) {
        include "gera_relatorio_pararelo.php";
    }

    if (strlen($posto) == 0) {
        if ($gera_automatico != 'automatico' and strlen($msg_erro)== 0) {
            include "gera_relatorio_pararelo_verifica.php";
        }
    }*/

    
    $sql = "SELECT tbl_os.os,
		tbl_os.data_abertura,
		tbl_os.data_digitacao,
		tbl_os.data_conserto,
		tbl_os.data_fechamento,
		tbl_os.finalizada,
		tbl_posto.posto,
		tbl_posto_fabrica.codigo_posto,
		tbl_posto_fabrica.contato_estado as estado,
		tbl_posto.nome
	    INTO TEMP tmp_{$login_admin}_os_finalizada
	    FROM tbl_os
	    JOIN tbl_posto USING(posto)
	    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
	    WHERE tbl_os.fabrica = {$login_fabrica} 
	    AND (tbl_os.excluida IS NULL OR tbl_os.excluida = 'f') ";
   
    if (strlen($codigo_posto) > 0) {
	$sql .= "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
    }

    if ($login_fabrica == 35) {
	$cond = " AND tbl_os.data_conserto BETWEEN '$xdata_ini 00:00:00' AND '$xdata_fim 23:59:59';";
    }else{
	$cond = " AND tbl_os.finalizada BETWEEN '$xdata_ini 00:00:00' AND '$xdata_fim 23:59:59';";
    }

    $sql .= $cond;

    $sql .= "SELECT COUNT(os) AS total,
                   posto,
                   codigo_posto,
                   estado,
                   nome
              FROM tmp_{$login_admin}_os_finalizada
             GROUP BY codigo_posto, nome, posto,estado
             ORDER BY total DESC;";
    
    $resDados = pg_query($con, $sql);

    if($_POST['gerar_excel']){

        $tot = pg_num_rows($resDados);

        if ($tot > 0) {

            $dias = 5;

            $filename = "relatorio-fechamento-os-posto-".date('Ydm').".csv";
            $file     = fopen("/tmp/{$filename}", "w");


            $head = "Posto;Nome Posto;";

            if($login_fabrica == 35){
                $head .= "Estado;";
            }

            for ($x = $dias; $x <= ($dias * 6); $x += $dias) {
                $head .= " $x dias;";
            }

            $head .= "+ ".($x - $dias)." dias ;Total;";

            if ($login_fabrica == 35) {
                $head .= "Tempo Médio Conserto dias;";    
                $head .= "Tempo Médio de Fechamento OS;";
            }else{
                $head .= "Média dias;";
            }

            fwrite($file, "$head\n");
            $body = "";

        
            for ($i = 0; $i < $tot; $i++) {
                $total       = trim(pg_fetch_result($resDados, $i, 'total'));
                $posto = trim(pg_fetch_result($resDados, $i, 'posto'));
                $estado      = trim(pg_fetch_result($resDados, $i, 'estado'));
                $codigo_posto= trim(pg_fetch_result($resDados, $i, 'codigo_posto'));
                $descricao_posto  = trim(pg_fetch_result($resDados, $i, 'nome'));
                $subtotal += $total;
                
                $body .= "$codigo_posto;$descricao_posto;";

                if($login_fabrica == 35){
                    $body .= "$estado;";
                }
                if ($login_fabrica == 35) {
                    $campoTotal = "(data_conserto::DATE - data_abertura) AS total";
                } else {
                    $campoTotal = "(data_fechamento - data_abertura) AS total";
                }
                $sql_total = "SELECT {$campoTotal}
                                FROM tmp_{$login_admin}_os_finalizada
                               WHERE posto = {$posto};";
                $res_total = pg_query($con, $sql_total);

                $vet = array();

                while ($linha = pg_fetch_assoc($res_total)) {

                    if ($linha['total'] <= 5) {
                        $vet[$dias * 1]++;
                    } else if ($linha['total'] > 5  && $linha['total'] <= 10) {
                        $vet[$dias * 2]++;
                    } else if ($linha['total'] > 10 && $linha['total'] <= 15) {
                        $vet[$dias * 3]++;
                    } else if ($linha['total'] > 15 && $linha['total'] <= 20) {
                        $vet[$dias * 4]++;
                    } else if ($linha['total'] > 20 && $linha['total'] <= 25) {
                        $vet[$dias * 5]++;
                    } else if ($linha['total'] > 25 && $linha['total'] <= 30) {
                        $vet[$dias * 6]++;
                    } else if ($linha['total'] > 30) {
                        $vet[$dias * 7]++;
                    }

                }

                for ($x = $dias; $x <= ($dias * 7); $x += $dias) {
                    $body .= abs($vet[$x]).";";
                    $media_dias[$x] += $vet[$x];
                }

                $sql_media = "SELECT SUM(data_fechamento - data_abertura) / count(1) as media
                                FROM tmp_{$login_admin}_os_finalizada
                                WHERE posto = $posto;";
                $res_media = pg_query($con, $sql_media);

                $media = pg_fetch_result($res_media, 0, 'media');
                $submedia += $media;

                $body .= "$total;";

                if ($login_fabrica == 35) {

			$sqlmediaconserto = "SELECT SUM(data_conserto::DATE - data_abertura) / count(1) as media, 
						    SUM(data_conserto::DATE - data_abertura) as total_dias
                        	            FROM tmp_{$login_admin}_os_finalizada
                                	    WHERE posto = $posto;";
                    $resmediaconserto = pg_query($con, $sqlmediaconserto);

                    $mediaconserto = pg_fetch_result($resmediaconserto, 0, 'media');
                    $total_dias = pg_fetch_result($resmediaconserto, 0, 'total_dias');
                    $submediaconserto += $total_dias;

                    //echo "<td class='tac'>".$media."</td>";   
                    $body .= "$mediaconserto;";   
                } else {
                    $body .= "$media;";   
                }
                $body .= "\r\n";
            }
            
        }

        $body .= ";;;";
                    
        for ($x = $dias; $x <= ($dias * 7); $x += $dias) {
            $body .= "".abs($media_dias[$x]).";";
        }
        $body .= "$subtotal;";

	if ($login_fabrica == 35) {

		$sqlmediaconserto = "SELECT SUM(data_conserto::DATE - data_abertura) / $subtotal as media_geral                                                                                            
                                     FROM tmp_{$login_admin}_os_finalizada";
                $resmediaconserto = pg_query($con, $sqlmediaconserto);

                $tmp_fechamento_os = pg_fetch_result($resmediaconserto, 0, 'media_geral');

            $tempo_excel = number_format($submediaconserto/$i,0);
            $body .= "".number_format($submediaconserto/$i,0).";";
            $body .= "".$tmp_fechamento_os.";";
        } else {
            $body .= "".number_format($submedia/$i,0).";";
        }   

        $body .= "\r\n";
        //total
        fwrite($file, $body);

        fclose($file);

        if (file_exists("/tmp/{$filename}")) {
            system("mv /tmp/{$filename} xls/{$filename}");

            echo "xls/{$filename}";
        }


        exit;
    }


    if (!is_resource($resDados)) die(pg_last_error($con) . "<br><br>$sql");
    
}
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "alphanumeric"
);

include("plugin_loader.php");
?>

<style type="text/css">

    .titulo_tabela{
        background-color:#596d9b !important;
        color:#FFFFFF !important;
    }


    .msg_erro{
        background-color:#FF0000;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
    .sucesso{
        background-color:green;
        font: bold 16px "Arial";
        color:#FFFFFF;
        text-align:center;
    }

    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
    }

    .subtitulo{
        color: #7092BE
    }

    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }

</style>

<script type="text/javascript" charset="utf-8">
    $(function() {
        $("#data_ini").datepicker().mask("99/99/9999");
        $("#data_fim").datepicker().mask("99/99/9999");
        $("span[rel=lupa]").click(function () {
                    $.lupa($(this));
        });
        Shadowbox.init();
        $.autocompleteLoad(Array("posto"));

        $('button').click(function() {
            botao = $(this);
            nome_arquivo = botao.val();
            $.post(location.pathname,
                    {'ajax': 'excluir',
                     'excluir': 'arquivo',
                     'nome': nome_arquivo
                     },
                     function(data) {
                        if (data == 'ok') {
                            alert('Arquivo Excluído!');
                            botao.remove();
                        } else if (data == 'nofile') {
                            alert('O arquivo não existe!');
                            botao.remove();
                        } else {
                            //alert('Erro ao excluir o arquivo');
                        }
            });
        });

    });
    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function limpaCampos(){
        document.frm_rel.data_ini.value="";
        document.frm_rel.data_fim.value="";
        document.frm_rel.codigo_posto.value="";
        document.frm_rel.descricao_posto.value="";
    }
</script>

<div class='alert alert-info'>
    <?php if ($login_fabrica == 35) {?>
        Este Relatório considera a <b>  Data de Conserto  </b> da OS
    <?php } else {?>
        Este Relatório considera a <b>Data de <acronym style='cursor:help' title='Esta data é gravada automáticamente pelo sistema quando o posto fecha a OS'>Finalização</acronym></b> da OS
    <?php }?>
</div>
<?php if (strlen($msg_erro) > 0) {?>
    <div class='alert alert-danger'><?=$msg_erro?></div>
<?php }?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form class='form-search form-inline tc_formulario' name="frm_rel" method="POST" action="relatorio_fechamento_os_posto.php">
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class="row-fluid">
        <div class="span2"></div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input maxlength="10" type="text" name="data_ini" id="data_ini" value="<?php echo $data_ini;?>" />
                        </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group'>
                    <label class='control-label' for='data_final'>Data Final</label>
                        <div class='controls controls-row'>
                            <h5 class='asteristico'>*</h5>
                            <input maxlength="10" type="text" name="data_fim" id="data_fim" value="<?php echo $data_fim;?>" />
                        </div>
                </div>
            </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">Código do Posto</label>
                <div class="controls controls-row">
                    <div class="input-append">
                        <INPUT TYPE="text" class="frm" NAME="codigo_posto" value="<?=$codigo_posto?>" id="codigo_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">Posto Autorizado</label>
                <div class="controls controls-row">
                    <div class="input-append">
                        <INPUT TYPE="text" class="frm" NAME="descricao_posto" value="<?=$descricao_posto?>" id="descricao_posto">
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2"></div>
    </div><br />
    <input class="btn btn-primary" type="submit" value="Pesquisar">
    <?php  
        if (isset($relatorio_anterior)) {
            echo "<button  type='button' class='btn' type='button' value='$relatorio_anterior'  onclick=\"window.location.reload();\">Limpar Relatório Gerado</button>";
        }
    ?>
    <input type='hidden' name='btnacao' value=''>
    <br /><br />
</form>

<?php
if ($data_ini && $data_fim && strlen($msg_erro) == 0) {

    echo '<br />';

    $tot = pg_num_rows($resDados);

    if ($tot > 0) {

        $dias = 5;
    ?>
    </div>
    <table class='table table-striped table-bordered table-hover table-fixed' id='tabela'>
       <thead>
            <tr class='titulo_coluna'>
                <th>Posto</th>
                <th>Nome Posto</th>
                <th>Estado</th>
                <?for ($x = $dias; $x <= ($dias * 6); $x += $dias) {?>
                    <th><?=$x?> dias</th>
                <?}?>
                <th>+ <?=($x - $dias)?> dias</th>
                <th>Total</th>
                <?php if ($login_fabrica == 35) {?>
                <!--<th>Tempo Médio <br /> Fechamento dias</th>-->
                <th>Tempo Médio <br />Conserto dias</th>
                <?php } else {?>
                <th>Média dias</th>
                <?php }?>
           </tr>
       </thead>
       <tbody>
        <?php
            for ($i = 0; $i < $tot; $i++) {
                $total       = trim(pg_fetch_result($resDados, $i, 'total'));
                $posto = trim(pg_fetch_result($resDados, $i, 'posto'));
                $estado      = trim(pg_fetch_result($resDados, $i, 'estado'));
                $codigo_posto= trim(pg_fetch_result($resDados, $i, 'codigo_posto'));
                $descricao_posto  = trim(pg_fetch_result($resDados, $i, 'nome'));
                $subtotal += $total;


                echo "<tr>";
                echo "<td align='left'>$codigo_posto</td>";
                echo "<td nowrap='nowrap' align='left'>$descricao_posto</td>";
                echo "<td nowrap='nowrap'><center>$estado</center></td>";


                if ($login_fabrica == 35) {
                    $campoTotal = "(data_conserto::DATE - data_abertura) AS total";
                } else {
                    $campoTotal = "(data_fechamento - data_abertura) AS total";
                }
                $sql_total = "SELECT {$campoTotal}
                                FROM tmp_{$login_admin}_os_finalizada
                               WHERE posto = $posto;";
                $res_total = pg_query($con, $sql_total);

                $vet = array();

                while ($linha = pg_fetch_assoc($res_total)) {

                    if ($linha['total'] <= 5) {
                        $vet[$dias * 1]++;
                    } else if ($linha['total'] > 5  && $linha['total'] <= 10) {
                        $vet[$dias * 2]++;
                    } else if ($linha['total'] > 10 && $linha['total'] <= 15) {
                        $vet[$dias * 3]++;
                    } else if ($linha['total'] > 15 && $linha['total'] <= 20) {
                        $vet[$dias * 4]++;
                    } else if ($linha['total'] > 20 && $linha['total'] <= 25) {
                        $vet[$dias * 5]++;
                    } else if ($linha['total'] > 25 && $linha['total'] <= 30) {
                        $vet[$dias * 6]++;
                    } else if ($linha['total'] > 30) {
                        $vet[$dias * 7]++;
                    }

                }

                for ($x = $dias; $x <= ($dias * 7); $x += $dias) {
                    echo "<td class='tac'>".abs($vet[$x])."</td>";
                    $media_dias[$x] += $vet[$x];
                }

                $sql_media = "SELECT SUM(data_fechamento - data_abertura) / count(1) as media
                                FROM tmp_{$login_admin}_os_finalizada
                                WHERE posto = $posto;";
                $res_media = pg_query($con, $sql_media);

                $media = pg_fetch_result($res_media, 0, 'media');
                $submedia += $media;

                echo "<td class='tac'>$total</td>";
                if ($login_fabrica == 35) {

                    $sqlmediaconserto = "SELECT SUM(data_conserto::DATE - data_abertura) / count(1) as media,  SUM(data_conserto::DATE - data_abertura) as dias_total
                                    FROM tmp_{$login_admin}_os_finalizada
                                    WHERE posto = $posto;";
                    $resmediaconserto = pg_query($con, $sqlmediaconserto);

                    $mediaconserto = pg_fetch_result($resmediaconserto, 0, 'media');
                    $total_dias = pg_fetch_result($resmediaconserto, 0, 'dias_total');
		    $submediaconserto += $total_dias;

                    echo "<td class='tac'>".$mediaconserto."</td>";   
                } else {
                    echo "<td class='tac'>".$media."</td>";   
                }
                echo "</tr>";
            }
            echo "</tbody><tfoot><tr class='titulo_tabela'>";
                echo "<td colspan='2'>Total</td>";                
                echo "<td></td>";
                for ($x = $dias; $x <= ($dias * 7); $x += $dias) {
                    echo "<td class='tac'>".abs($media_dias[$x])."</td>";                    
                }
                echo "<td class='tac'>$subtotal</td>";

		if ($login_fabrica == 35) {
			$sbmconserto = number_format($submediaconserto/$i,0);
			echo "<td class='tac'>".$sbmconserto."</td>"; 

		    $sqlmediaconserto = "SELECT SUM(data_conserto::DATE - data_abertura) / $subtotal as media_geral
                                         FROM tmp_{$login_admin}_os_finalizada";
		    $resmediaconserto = pg_query($con, $sqlmediaconserto);

	    	    $tmp_fechamento_os = pg_fetch_result($resmediaconserto, 0, 'media_geral');

		    echo "</tr><tr class='titulo_tabela'><td colspan='2'>Tempo Médio de Fechamento OS</td>";
		    $sqlMediaFechamentoGeral = "";
                    echo "<td colspan='1' class='tac'>".$tmp_fechamento_os."</td>";
                    
                } else {
                    echo "<td class='tac'>".number_format($submedia/$i,0)."</td>";
                }            
            echo "</tr></tfoot>";

        echo "</table>";

        $jsonPOST = excelPostToJson($_POST);

        ?>

        <br />

        <div id='gerar_excel' class="btn_excel">
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
            <span><img src='imagens/excel.png' /></span>
            <span class="txt">Gerar Arquivo Excel</span>
        </div>


        <?php

    } else {
        echo "<div class='alert'><b>Nenhum registro encontrado!<b></div>";

    }

}
echo "<br />";

include "rodape.php";

?>
