<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';

$menu_os[]['link'] = 'linha_de_separação';

$layout_menu = "os";
$title = traduz('menu.de.ordens.de.servico', $con);

include 'cabecalho.php';
?>

<style type="text/css">
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
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.subtitulo{

    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
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
.informacao{
    font: 14px Arial; color:rgb(89, 109, 155);
    background-color: #C7FBB5;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.espaco{
    padding-left:80px;
    width: 220px;
}

.sem_pagamento{
    font-family: bold small Verdana, Arial, Helvetica, sans-serif;
    color: #888888;
    font-weight: bold;

}
</style>
<style type="text/css">
    @import "plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<link href="plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src='plugins/jquery.maskedinput_new.js'></script>

<?php if ($login_fabrica == 151) { /*HD - 6185214*/?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#data_inicial').datepick({startdate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $("#data_inicial").mask("99/99/9999");
        $("#data_final").mask("99/99/9999");

        $("#btn_pesquisar").click(function(){
            if ($("#nota_retorno_obrigatorio").is(':checked') == false 
                && $("#nota_retorno_nao_obrigatorio").is(':checked') == false
                && $("#nota_recebimento_pendente").is(':checked') == false 
                && $("#notas_recebidas").is(':checked') == false 
                && $("#nota_devolucao").val() == "" 
                && $("#data_inicial").val() == "" 
                && $("#data_final").val() == "") {
                alert("Por favor informar ao menos um parâmetro para pesquisa");
            }else{
                $("#frm_os_pesquisa").submit();
            }
        });
    });
</script>
<?php } ?>

<?php

$data_atual = date("Y-m-d");

if(strtotime($data_atual) > strtotime('2017-08-31')){
	$data_90    = date('Y-m-d', strtotime("-90 days",strtotime($data_atual)));
}else{
	$data_90 = '2017-04-01';
}
    if ($login_fabrica == 151) {
        $campoPostagem = " , JSON_FIELD('numero_postagem',tbl_os_item.parametros_adicionais) ";
        $asPostagem = "  AS numero_postagem";

        $campoBloqueio = ", tbl_os_item.parametros_adicionais::JSONB->'bloqueio'";
        $asBloqueio = " AS bloqueio";
        $cond = "
            AND     tbl_os.finalizada IS NULL
            AND     tbl_os.data_fechamento IS NULL
            AND     JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais)::BOOL IS NOT NULL ";

        $cond2 = " AND     JSON_FIELD('bloqueio',tbl_os_item.parametros_adicionais)::BOOL IS NOT NULL ";

    } else {
        $cond = " AND     tbl_os.finalizada IS NOT NULL
                 AND     tbl_os.finalizada > '2017-03-01 00:00'
        ";
        $cond2 = " AND tbl_os.finalizada IS NOT NULL";
    }

    $where_coalesce            = " AND COALESCE(tbl_faturamento_item.qtde_inspecionada, 0) < tbl_os_item.qtde::integer ";
    $where_data                = "";
    $join_tbl_faturamento_ofti = "";
    $join_tbl_faturamento_tf   = "";


    if ($_POST["pesquisar"] == "submit" && $login_fabrica == 151) { /*HD - 6185214*/
        $data_inicial                 = $_POST["data_inicial"];
        $data_final                   = $_POST["data_final"];
        $nota_devolucao               = $_POST["nota_devolucao"];
        $nota_retorno_obrigatorio     = $_POST["nota_retorno_obrigatorio"];
        $nota_recebimento_pendente    = $_POST["nota_recebimento_pendente"];
        $nota_retorno_nao_obrigatorio = $_POST["nota_retorno_nao_obrigatorio"];
        $notas_recebidas              = $_POST["notas_recebidas"];
        
        if (strlen($data_inicial) > 0 && strlen($data_final) == 0) {
            $msg_erro .= traduz("favor.informar.a.data.final") . "<br>";
        } elseif (strlen($data_final) > 0 && strlen($data_inicial) == 0) {
            $msg_erro .= traduz("favor.informar.a.data.inicial") . "<br>";
        } elseif (strlen($data_inicial) > 0
            && strlen($data_final) > 0 
            && strlen($nota_retorno_obrigatorio) == 0 
            && strlen($nota_recebimento_pendente) == 0 
            && strlen($nota_retorno_nao_obrigatorio) == 0 
            && strlen($notas_recebidas) == 0) {
            $msg_erro .= traduz("favor.selecionar.ao.menos.um.filtro.de.nota") . "<br>";
        } elseif ((strlen($nota_retorno_obrigatorio) > 0
            || strlen($nota_recebimento_pendente) > 0
            || strlen($nota_retorno_nao_obrigatorio) > 0
            || strlen($notas_recebidas) > 0)
            && (strlen($data_inicial) == 0 && strlen($data_final) == 0)) {
            $msg_erro .= traduz("favor.informar.a.data.inicial.e.data.final") . "<br>";
        }

        if (strlen($msg_erro) == 0) {
            $where_coalesce                     = "";
            $where_nota_devolucao               = "";
            $where_nota_retorno_obrigatorio     = "";
            $where_nota_retorno_nao_obrigatorio = "";
            $where_nota_recebimento_pendente    = "";
            $where_notas_recebidas              = "";
            $cond                               = "";
            $cond2                              = "";
            $join_tbl_faturamento_ofti          = " JOIN tbl_faturamento_item tfi ON tfi.os = tbl_os.os and tfi.peca = tbl_os_item.peca ";
            $join_tbl_faturamento_tf            = " JOIN tbl_faturamento tf ON tf.fabrica = $login_fabrica AND tf.faturamento = tfi.faturamento ";

            if (strlen($nota_devolucao) > 0) {
                $where_nota_devolucao = " AND tf.nota_fiscal = '$nota_devolucao' ";
            } else {
                if (strlen($data_inicial) > 0 && strlen($data_inicial) > 0) {
                    list($di, $mi, $yi) = explode("/", $data_inicial);
                    list($df, $mf, $yf) = explode("/", $data_final);

                    if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
                        $msg_erro .= traduz("data.invalida") . "<br>";
                    } else {
                        $aux_data_inicial = "{$yi}-{$mi}-{$di}";
                        $aux_data_final   = "{$yf}-{$mf}-{$df}";

                        if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                            $msg_erro .= traduz("data.final.nao.pode.ser.menor.que.a.data.inicial") . "<br>";
                        } elseif (strtotime($aux_data_inicial) < strtotime($aux_data_final . " -6 month")) {
                            $msg_erro .= traduz("o.intervalo.da.pesquisa.nao.pode.ser.maior.do.que.6.meses") . "<br>";
                        } else {
                            $where_data = " AND tf.emissao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";

                            if (strlen($nota_retorno_obrigatorio) > 0) {
                                $where_nota_retorno_obrigatorio = " AND tf.movimento = 'RETORNAVEL' ";
                            }

                            if (strlen($nota_retorno_nao_obrigatorio) > 0) {
                                $where_nota_retorno_nao_obrigatorio = " AND tf.movimento = 'NAO_RETOR' ";
                            }

                            if (strlen($nota_recebimento_pendente) > 0) {
                                $where_nota_recebimento_pendente = " AND tf.conferencia IS NULL ";
                            }

                            if (strlen($notas_recebidas) > 0) {
                                $where_notas_recebidas = " AND tf.conferencia IS NOT NULL ";
                            }
                        }
                    }
                }
            }
        }
    }

    $sql_temp = "
        SELECT  tbl_os.os,
                tbl_os.sua_os,
                tbl_faturamento_item.faturamento,
                tbl_faturamento_item.faturamento_item ,
                COALESCE(tbl_faturamento_item.qtde_inspecionada,0) AS qtde_inspecionada,
                tbl_os_item.os_item,
                tbl_os_campo_extra.os_bloqueada
   INTO TEMP    LGR_colormaq_$login_posto
        FROM    tbl_os
        JOIN    tbl_os_produto          ON  tbl_os.os                   = tbl_os_produto.os
        JOIN    tbl_os_item             ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
        JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tbl_os.os
        JOIN    tbl_faturamento_item    ON  (
                                                tbl_os_item.os_item     = tbl_faturamento_item.os_item
                                            OR  tbl_os_item.pedido      = tbl_faturamento_item.pedido
                                            )
                                        AND (tbl_os_item.peca            = tbl_faturamento_item.peca OR tbl_os_item.peca = tbl_faturamento_item.peca_pedida)
        $join_tbl_faturamento_ofti
        $join_tbl_faturamento_tf
        LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
        WHERE   tbl_os_item.peca_obrigatoria    = 't'
        AND     tbl_os.fabrica                  = $login_fabrica
        AND     tbl_os.posto                    = $login_posto
        $where_data
        $where_coalesce
        $where_nota_devolucao
        $where_nota_retorno_obrigatorio
        $where_nota_retorno_nao_obrigatorio
        $where_nota_recebimento_pendente
        $where_notas_recebidas
        $cond
  GROUP BY      tbl_os.os,
                tbl_os.sua_os,
                tbl_faturamento_item.faturamento,
                tbl_faturamento_item.faturamento_item,
                tbl_faturamento_item.qtde_inspecionada,
                tbl_os_item.os_item,
                tbl_os_campo_extra.os_bloqueada
  ORDER BY      tbl_os.os
            ";
// echo nl2br($sql_temp);
    $res_temp = pg_query($con, $sql_temp);

    $sql = "SELECT  tbl_os.os,
                    tbl_os.sua_os,
                    ARRAY_TO_STRING(ARRAY_AGG(tbl_peca.referencia ||' - ' || tbl_peca.descricao || '|' || tbl_os_item.qtde || ' | ' || tbl_os_item.os_item ),' DIV ') AS pecas_qtde,
                    COALESCE(tfi.qtde_inspecionada,0) AS qtde_inspecionada, tbl_os_campo_extra.os_bloqueada
                    $campoPostagem $asPostagem
                    $campoBloqueio $asBloqueio
            FROM    tbl_os
            JOIN    tbl_os_produto          ON  tbl_os.os                       = tbl_os_produto.os
            JOIN    tbl_os_item             ON  tbl_os_produto.os_produto       = tbl_os_item.os_produto
            JOIN    tbl_servico_realizado   ON  tbl_os_item.servico_realizado   = tbl_servico_realizado.servico_realizado
            JOIN    tbl_peca                ON  tbl_peca.peca                   = tbl_os_item.peca
                                            AND tbl_peca.fabrica                = $login_fabrica
            JOIN    tbl_os_extra            ON  tbl_os_extra.os                 = tbl_os.os
            JOIN    tbl_faturamento_item    ON  (
                                                    tbl_faturamento_item.pedido     = tbl_os_item.pedido
                                                OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
                                                )
                                            AND (
                                                    tbl_os_item.peca = tbl_faturamento_item.peca
                                                OR  tbl_os_item.peca = tbl_faturamento_item.peca_pedida
                                                )
            JOIN    tbl_faturamento         ON  (
                                                    tbl_faturamento.faturamento     = tbl_faturamento_item.faturamento
                                                OR  tbl_faturamento.distribuidor    = $login_posto
                                                )
                                            AND tbl_faturamento.fabrica         = $login_fabrica
                                            AND tbl_faturamento.posto           = tbl_os.posto
            LEFT JOIN tbl_faturamento_item tfi ON tfi.os = tbl_os.os and tfi.peca = tbl_os_item.peca
            LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os
            WHERE   tbl_os_item.peca_obrigatoria = 't'
            $cond2
            AND     tbl_os.os           IN (SELECT os FROM LGR_colormaq_$login_posto )

            AND     tbl_os.fabrica = $login_fabrica
            AND     tbl_servico_realizado.troca_de_peca
            AND     data_abertura > '2017-04-01'
            AND     tbl_os.posto = $login_posto
      GROUP BY      tbl_os.os,
                    tbl_os.sua_os,
                    tfi.qtde_inspecionada,
                    tbl_os_campo_extra.os_bloqueada
                    $campoPostagem
                    $campoBloqueio
      ORDER BY      tbl_os.os;";
// echo nl2br($sql);
            $res = pg_query($con, $sql);
$fetch = pg_fetch_all($res);

if (strlen($msg_erro) > 0) { ?>
    <div align="center">
        <div width="700" style="width:700px" class="error">
            <?php echo $msg_erro; ?>
        </div>
    </div>
<?php }

$contOSCallcenter = pg_num_rows($res);
if ($contOSCallcenter > 0) {
    $colspan = ($login_fabrica == 151) ? 8 : 7;

    if ($login_fabrica == 151) { /*HD - 6185214*/ ?>
        <form id='frm_os_pesquisa' name='frm_os_pesquisa' method='POST' action='os_pendente_pagamento.php'>
            <table border="0" cellspacing="0" cellpadding="2" width="700" class="formulario">
                <caption class="titulo_tabela">Parâmetros de Pesquisa</caption>
                <tbody>
                    <tr>
                        <td width="150px">&nbsp;</td>
                        <td width="200px">&nbsp;</td>
                        <td width="200px">&nbsp;</td>
                        <td width="150px">&nbsp;</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            Nota de Devolução<br>
                            <input type="text" name="nota_devolucao" id="nota_devolucao" value="" tabindex="3" value="<?=$data_inicial;?>">
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            Data Inicial<br>
                            <input size="12" maxlength="10" type="text" name="data_inicial" id="data_inicial" autocomplete="off" value="<?=$data_inicial;?>">
                        </td>
                        <td>
                            Data Final<br>
                            <input size="12" maxlength="10" type="text" name="data_final" id="data_final" autocomplete="off" value="<?=$data_final;?>">
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <label for="nota_retorno_obrigatorio">
                                <input type="checkbox" <?php if (strlen($_POST["nota_retorno_obrigatorio"]) > 0) echo "CHECKED"; ?> name="nota_retorno_obrigatorio" id="nota_retorno_obrigatorio" value="checked">
                                Notas de Retorno Obrigatório
                            </label>
                        </td>
                        <td>
                            <label for="nota_recebimento_pendente">
                                <input type="checkbox" <?php if (strlen($_POST["nota_recebimento_pendente"]) > 0) echo "CHECKED"; ?> name="nota_recebimento_pendente" id="nota_recebimento_pendente" value="checked">
                                Notas Com Recebimento Pendente
                            </label>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <label for="nota_retorno_nao_obrigatorio">
                                <input type="checkbox" <?php if (strlen($_POST["nota_retorno_nao_obrigatorio"]) > 0) echo "CHECKED"; ?> name="nota_retorno_nao_obrigatorio" id="nota_retorno_nao_obrigatorio" value="checked">
                                Notas de Retorno Não Obrigatório    
                            </label>
                        </td>
                        <td>
                            <label for="notas_recebidas">
                                <input type="checkbox" <?php if (strlen($_POST["notas_recebidas"]) > 0) echo "CHECKED"; ?> name="notas_recebidas" id="notas_recebidas" value="checked">
                                Notas Recebidas
                            </label>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align:center" valign="middle" nowrap="">
                            <br>
                            <input type="hidden" name="pesquisar" value="submit">
                            <button type="button" id="btn_pesquisar" name="btn_pesquisar">Pesquisar</button>
                            <br><br>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <br>
    <?php }

    echo '<br/>';
    echo "<table width='700'>";
        echo "<tr>";
            echo "<td width='60' bgcolor='#98FB98'>&nbsp;</td>";
            echo "<td style='font-size:12px'>Nota já enviada.</td>";
        echo "</tr>";
    echo "</table>";

    if ($login_fabrica == 151) { ?>
        <table width='700'>
            <tr>
                <td width='60' bgcolor='#E0123F'>&nbsp;</td>
                <td style='font-size:12px'>Peças que precisam ser devolvidas.</td>
            </tr>
        </table>
    <?php }

    echo "<form id='frm_os_pendente_pagamento' name='frm_os_pendente_pagamento' method='POST' action='os_posto_lgr.php'>";

    echo '<table border="0" cellspacing="0" cellpadding="2" width="700" class="tabela">';
    echo '<thead>';
        echo '<tr class="titulo_tabela">';
            echo '<th colspan="'.$colspan.'">OSs Pendentes de Pagamento</th>';
        echo '</tr>';
        echo '<tr class="titulo_coluna">';
            echo '<th></th>';
            echo '<th>OS</th>';
            echo '<th>Pedido</th>';
            echo '<th>Peças</th>';
            echo '<th>Qtde Pendente</th>';
            echo '<th>Qtde Conferida</th>';
            echo '<th>Nota Fiscal</th>';
            if ($login_fabrica == 151) {
            echo '<th>Código de Devolução</th>';

            }
        echo '</tr>';
    echo '</thead>';

    echo '<tbody>';
    $a=1;
    //while ($fetch = pg_fetch_assoc($res)) {
    foreach ($fetch as $linha) {
        if ($linha['qtde_inspecionada'] != 0) {
            continue;
        }

        $os_item            = $linha['os_item'];
        $os                 = $linha['os'];
        $sua_os             = $linha['sua_os'];
        $data_abertura      = $linha['data_abertura'];
        $pedido             = $linha['pedido'];
        $pecas_qtde         = $linha['pecas_qtde'];
        $qtde_inspecionada  = $linha['qtde_inspecionada'];
        $os_bloqueada       = $linha['os_bloqueada'];

		$pecas_qtde = explode(" DIV ", $pecas_qtde);

		$value_array = explode("-", $pecas_qtde[0]);

        $cond = "";
		if ($login_fabrica == 151) {
            $numero_postagem    = $linha['numero_postagem'];
            $bloqueio           = $linha['bloqueio'];

            if ($os_bloqueada == "t" || ($bloqueio == "true" && $numero_postagem != "") ) {
                continue;
            }
            $cond .= "AND tbl_faturamento.distribuidor = $login_posto";
		}

		$sql_faturamento = "SELECT tbl_faturamento_item.faturamento, tbl_faturamento.nota_fiscal
			FROM tbl_faturamento_item
			JOIN tbl_faturamento on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			JOIN tbl_peca on tbl_peca.peca = tbl_faturamento_item.peca
			WHERE tbl_faturamento_item.os = $os
			$cond
			";
		$res_faturamento = pg_query($con, $sql_faturamento);

		if (pg_num_rows($res_faturamento) > 0) {
			$lgr = true;
			$nota_fiscal = pg_fetch_result($res_faturamento, 0, 'nota_fiscal');
			$faturamento = pg_fetch_result($res_faturamento, 0, 'faturamento');
		} else {
			$lgr = false;
			$nota_fiscal = "";
			if ($login_fabrica == 151 && empty($numero_postagem)) {
                $lgr = true;
			}
		}

		$bgcolor = ($lgr == true && !($login_fabrica == 151 && empty($numero_postagem)))? "bgcolor='#98FB98'" : "";

        if ($login_fabrica == 151 && $lgr === false) { /*HD - 6185214*/
            $bgcolor = "bgcolor='#E0123F'";
        }

		echo "<tr class='Conteudo' style='text-align:center;' $bgcolor >";
		if( $lgr == true){
			echo "<td></td>";
		} else {
			echo "<td><input type='checkbox' class='os_check_$a' name='os_check[]' value='$os'></td>";
		}
		echo '<td><a target="_blank" href="os_press.php?os='.$os.'">'.$sua_os.'</a></td>';
		echo '<td>'.$pedido.'</td>';

		$i =0;
		foreach ($pecas_qtde as $value) {
			$value_array = explode("|", $value);
			$referencia_peca = explode("-",$value);
			$sql_faturamento = "SELECT tbl_faturamento_item.faturamento, tbl_faturamento.nota_fiscal
				FROM tbl_faturamento_item
				INNER JOIN tbl_faturamento on tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				inner join tbl_peca on tbl_peca.peca = tbl_faturamento_item.peca
				where os = $os and tbl_peca.referencia = '".trim($referencia_peca[0])."'";
			$res_faturamento = pg_query($con, $sql_faturamento);
			if(pg_num_rows($res_faturamento)> 0){
				$nota_fiscal = pg_fetch_result($res_faturamento, 0, 'nota_fiscal');
				$faturamento = pg_fetch_result($res_faturamento, 0, 'faturamento');
			}
			if($i == 0){
				echo "<td align='left'>".$value_array[0]."</td>";
				echo "<td>".$value_array[1]."</td>";
				echo "<td>$qtde_inspecionada</td>";
			}else{
				echo "</tr>";
				echo "<tr class='Conteudo' style='text-align:center;'  $bgcolor  >";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td align='left'>".$value_array[0]."</td>";
				echo "<td>".$value_array[1]."</td>";
				echo "<td>$qtde_inspecionada</td>";
			}

			echo ($lgr == true) ? "<td><a target='_blank' href='espelho_lgr.php?nota_fiscal=$faturamento'>$nota_fiscal</td>" : "<td>$nota_fiscal</td>";
			if ($login_fabrica == 151) {
                echo "<td>$numero_postagem</td>";
			}
			$i++;
		}
		if ($a % 2 == 0) {
			$bgcolor = '#FFFFFF';
		} else {
			$bgcolor = '#EAEAEA';
		}

		echo '</tr>';
		$a++  ;
    }

    echo '</tbody>';
    echo '</table>';
    //echo '<br/>';
    echo '<table border="0" cellspacing="0" cellpadding="2" width="700">';
        echo "<tr>";
            echo "<td align='center'>";
            echo " <input type='hidden' name='btnacao' value='gravar'>
                    <input type='hidden' id='qtde_os' name='qtde' value='$contOSCallcenter'>
            <button type='button' id='btn_gravar' name='btnacao'>Gravar Nota Fiscal</button></td>";
        echo "</tr>";
    echo "</table>";
    echo "</form>";
}else{
    echo "<br><br>";
    echo '<table border="0" cellspacing="0" cellpadding="2" width="700">';
    echo "<tr>";
        echo "<td align='center' class='sem_pagamento'>NÃO EXISTE OS PENDENTE DE PAGAMENTO</td>";
    echo "</tr>";
    
    if ($login_fabrica == 151) { /*HD - 6185214*/
        echo '
            <tr>
                <td align="center">
                    <button onclick="javascript: window.location.replace(\'os_pendente_pagamento.php\');">Voltar</button>
                </td>
            </tr>
        ';
    }
    echo "</table>";
}
?>
<script type="text/javascript">
    $(function() {
        $("#btn_gravar").click(function(){

            if ($("input[class^='os_check_']").is(':checked')) {
                $("#frm_os_pendente_pagamento").submit();
            }else{
                alert("Favor selecionar uma OS");
            }
        });
    });
</script>

<?php
include "rodape.php";

