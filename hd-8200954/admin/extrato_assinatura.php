<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/communicator.class.php';
$mailer = new TcComm($externalId);

if($login_fabrica == 1){
    $layout_menu = 'gerencia';
    $title       = "EXTRATO";
}else{
    $layout_menu = 'financeiro';
    $title       = "LIBERAÇÃO EXTRATO - ASSINATURA";
}
if (filter_input(INPUT_POST,"ajax")) {
    $acao       = filter_input(INPUT_POST,"acao");
    $motivo     = filter_input(INPUT_POST,"motivo",FILTER_SANITIZE_STRING);
    $extratos   = filter_input(INPUT_POST,"enviaExtrato",FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    if ($acao == "recusar" && empty($motivo)) {
        echo "erro";
        exit;
    }

    pg_query($con,"BEGIN TRANSACTION");

    switch ($acao) {
        case "aprovar":
            $sql = "
                UPDATE  tbl_extrato_status
                SET     pendente        = FALSE,
                        conferido       = CURRENT_TIMESTAMP,
                        admin_conferiu  = $login_admin
                WHERE   extrato IN (".implode(',',$extratos).")
                AND     obs ILIKE 'Aguardando aprova%'
            ";
            break;
        case "recusar":
            $sql = "
                UPDATE  tbl_extrato_status
                SET     pendente        = TRUE,
                        conferido       = CURRENT_TIMESTAMP,
                        admin_conferiu  = $login_admin,
                        arquivo         = '$motivo'
                WHERE   extrato IN (".implode(',',$extratos).")
                AND     obs ILIKE 'Aguardando aprova%'
            ";
            break;
    }

    $res = pg_query($con,$sql);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");

        exit;
    }
    //pg_query($con,"ROLLBACK TRANSACTION");
    pg_query($con,"COMMIT TRANSACTION");

    if ($acao == "aprovar") {
        $sqlMail = "
            SELECT  email
            FROM    tbl_admin
            WHERE   fabrica = $login_fabrica
            AND     JSON_FIELD('pagamento_garantia',parametros_adicionais) = 't';
        ";
        $resMail = pg_query($con,$sqlMail);
        $emails = pg_fetch_all_columns($resMail,0);

        $sqlAdmin = "
            SELECT  nome_completo
            FROM    tbl_admin
            WHERE   admin = $login_admin
        ";
        $resAdmin = pg_query($con,$sqlAdmin);
        $nomeAdmin = pg_fetch_result($resAdmin,0,nome_completo);

        $listMail = implode(",",$emails);

        $listExtratos = implode(", ",$extratos);

        $sqlProtocolos = "SELECT protocolo FROM tbl_extrato WHERE extrato IN ($listExtratos)";
        $resProtocolos = pg_query($con,$sqlProtocolos);
        $listaProtocolos = pg_fetch_all_columns($resProtocolos,0);

        $protocolos = implode(", ",$listaProtocolos);

        $body = "Os extratos abaixo foram liberados por ".$nomeAdmin.": ".$protocolos.".";

        foreach($emails as $email) {
            if (in_array($login_fabrica, array(169,170))){
                if (!$mailer->sendMail("$email","EXTRATOS LIBERADOS",$body,"naorespondablueservice@carrier.com.br")) {
                    echo "erro";
                    exit;
                }
            }else{
                if (!$mailer->sendMail("$email","EXTRATOS LIBERADOS",$body,"noreply@telecontrol.com.br")) {
                    echo "erro";
                    exit;
                }
            }
        }
    }
    echo json_encode(array("ok"=>true,"acao"=>$acao));
    exit;
}
if (filter_input(INPUT_POST,"btn_acao")) {
    $data_inicial       = filter_input(INPUT_POST,"data_inicial");
    $data_final         = filter_input(INPUT_POST,"data_final");
    $anos               = filter_input(INPUT_POST,"anos",FILTER_VALIDATE_INT);
    $codigo_posto       = filter_input(INPUT_POST,"codigo_posto");
    $descricao_posto    = filter_input(INPUT_POST,"descricao_posto");
    $extrato            = filter_input(INPUT_POST,"extrato",FILTER_VALIDATE_INT);
    $regiao             = filter_input(INPUT_POST,"regiao");

	if (!empty($data_inicial) && !empty($data_final) && empty($anos)) {

        list($di,$mi,$ai) = explode("/",$data_inicial);
        list($df,$mf,$af) = explode("/",$data_final);

        if(!checkdate($mi,$di,$ai) || !checkdate($mf,$df,$af)){
            $msg_erro["msg"]["obrigatorio"] = "Data Inválida.";
            $msg_erro["campos"][] = "data";
        } else {
            $xdata_inicial  = $ai."-".$mi."-".$di;
            $xdata_final    = $af."-".$mf."-".$df;

            if($xdata_inicial > $xdata_final) {
                $msg_erro["msg"][]    ="Data Inicial maior que final";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (count($msg_erro) == 0) {

        if (!empty($data_inicial) && !empty($data_final) && empty($anos)) {
            $condData = "AND tbl_extrato.data_geracao::DATE BETWEEN '".$xdata_inicial."' AND '".$xdata_final."'\n";
        } else if (!empty($anos)) {
            $condData = "AND tbl_extrato.data_geracao::DATE BETWEEN '".$anos."-01-01' AND '".$anos."-12-31'\n";
        }

        if (!empty($extrato)) {
            $conExtrato = "AND tbl_extrato.protocolo = '$extrato'\n";
        }

        if (!empty($codigo_posto)) {
            $condPosto = "AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'\n";
        }

        if (!empty($regiao)) {
            if (in_array($regiao,array("NORTE","NORDESTE","SUL","SUDESTE","CENTRO-OESTE"))) {
                $condRegiao = "AND tbl_posto.estado IN (SELECT estado FROM tbl_estado WHERE regiao = '$regiao')";
            } else {
                $condRegiao = "AND tbl_posto.estado = ".$regiao."\n";
            }
        }

        $sqlExtrato = "
            SELECT  tbl_posto.posto ,
                    tbl_posto.nome ,
                    tbl_posto.cnpj ,
                    tbl_posto_fabrica.codigo_posto ,
                    tbl_tipo_posto.descricao AS tipo_posto ,
					tbl_extrato.extrato ,
					tbl_extrato.total,
                    LPAD(tbl_extrato.protocolo,6,'0') AS protocolo ,
                    TO_CHAR(tbl_extrato.data_geracao,'dd/mm/yyyy') AS data_geracao
            FROM    tbl_extrato
            JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_extrato.posto
            JOIN    tbl_posto_fabrica   ON  tbl_extrato.posto           = tbl_posto_fabrica.posto
                                        AND tbl_extrato.fabrica         = tbl_posto_fabrica.fabrica
                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
            JOIN    tbl_tipo_posto      ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
            WHERE   tbl_extrato.fabrica = $login_fabrica
            AND     tbl_extrato.extrato IN (
                        SELECT  extrato
                        FROM    tbl_extrato_status
                        WHERE   fabrica     = $login_fabrica
                        AND     obs         ILIKE 'Aguardando aprova%'
                        AND     pendencia   IS NULL
                        AND     pendente    IS NULL
                    )
            $condData
            $conExtrato
            $condPosto
			$condRegiao
      ORDER BY      tbl_extrato.total desc
        ";
        $resExtrato = pg_query($con,$sqlExtrato);
		if(pg_num_rows($resExtrato) == 0) {
			$msg_erro['msg'][] ='Nenhum resultado encontrado'; 
		}
    }
}

include 'cabecalho_new.php';
$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "alphanumeric"
);
include("plugin_loader.php");
?>

<script type="text/javascript">
$(function() {
    $.autocompleteLoad(Array( "posto"));
    Shadowbox.init();
    $("#data_inicial").datepicker({
        minDate:"-360d",
        maxDate:"0"
    });
    $("#data_final").datepicker({
        minDate:"-360d",
        maxDate:"0"
    });

    $("#extrato").numeric();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });


    $("#marcar_todas").click(function(){
        if ($(this).is(":checked")) {
            $(".liberar").prop("checked","checked");
        } else {
            $(".liberar").prop("checked","");
        }
    });

    $(".acao").click(function(e){
        e.preventDefault();
        var extratos        = $(".liberar").serializeArray();
        var acao            = $(this).attr("id");
        var enviaExtrato    = [];
        var motivo;

        if (acao == "recusar") {
            motivo = prompt("Motivo da recusa do(s) extrato(s)");
        }

        $.each(extratos,function(k,v){
            enviaExtrato.push(v.value);
        });

        $.ajax({
            url:"extrato_assinatura.php",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                acao:acao,
                motivo:motivo,
                enviaExtrato
            },
            beforeSend:function(jqXHR){
                if (enviaExtrato.length == 0) {
                    alert("Por favor, escolha um extrato para fazer a ação");
                    jqXHR.abort();
                    return false;
                }
            }
        })
        .done(function(data){
            if (data.ok) {
                alert("Extratos "+((data.acao == "aprovar") ? "liberados" : "recusados")+" com sucesso.");
                location.reload();
            }
        })
        .fail(function(){
            alert("Não foi possível realizar a gravação.");
        });
    });
});

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
    $("#descricao_posto").val(retorno.nome);
}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>



<form name="frm_ext_assinatura" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_inicial'>Data Inicial</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
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
                <label class='control-label' for='extrato'>Extrato</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <input type="text" name="extrato" id="extrato" size="12" maxlength="10" class='span12' value="<?=$extrato?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <label class='control-label' for='regiao'>Região/Estado</label>
            <div class='controls controls-row'>
                <div class='span4'>
                    <select name="regiao">
                        <option value="">SELECIONE</option>
                        <optgroup label="REGIÕES" />
<?php
$sqlRegioes = "
    SELECT  DISTINCT
            regiao
    FROM    tbl_estado
    WHERE   visivel IS TRUE
";
$resRegioes = pg_query($con,$sqlRegioes);
while ($regioes = pg_fetch_object($resRegioes)) {
?>
                        <option value="<?=$regioes->regiao?>" <?=($regioes->regiao == $regiao) ? "selected" : ""?>><?=$regioes->regiao?></option>
<?php
}
?>
                        <optgroup label="ESTADOS" />
<?php
$sqlEstados = "
    SELECT  estado AS uf,
            nome
    FROM    tbl_estado
    WHERE   visivel IS TRUE
ORDER BY    estado
";
$resEstados = pg_query($con,$sqlEstados);
while ($estado = pg_fetch_object($resEstados)) {
?>
                        <option value="<?=$estado->uf?>"  <?=($estado->uf == $regiao) ? "selected" : ""?>><?=$estado->nome?></option>
<?php
}
?>
                    </select>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
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
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>
<?php
if (pg_num_rows($resExtrato) > 0) {
?>
<table id="extrato_assinatura" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <tr class='titulo_coluna'>
            <th>Todas<br /><input type="checkbox" name="marcar_todas" id="marcar_todas" value="t" /></th>
            <th>Código</th>
            <th>Nome Posto</th>
            <th>Tipo</th>
            <th>Protocolo</th>
            <th>Data</th>
            <th>Qtde OS</th>
            <th>Total Peça</th>
            <th nowrap>Total<br />Mão-de-Obra</th>
            <th>Total Avulso</th>
            <th>Total Geral</th>
            <th>Impressão</th>
        </tr>
    </thead>
    <tbody>
<?php
    $cabecalho = "
        <thead>
        <tr>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Código</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Nome Posto</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Tipo</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Protocolo</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Data</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Qtde OS</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Total Peça</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Total Mão-de-Obra</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Total Avulso</font></th>
            <th bgcolor='#596d9b'><font color='#FFFFFF'>Total Geral</font></th>
        </tr>
    </thead>
    ";
    while ($result = pg_fetch_object($resExtrato)) {
        $total_total[] = $result->total;
?>
        <tr>
            <td style="text-align:center">
                <input type="checkbox" name="extratos" value="<?=$result->extrato?>" class="liberar" />
            </td>
            <td><?=$result->codigo_posto?></td>
            <td nowrap><?=$result->nome?></td>
            <td style="text-align:center"><?=$result->tipo_posto?></td>
            <td style="text-align:center"><a href="extrato_consulta_os.php?extrato=<?=$result->extrato?>" target="_blank"><?=$result->protocolo?></a></td>
            <td><?=$result->data_geracao?></td>
<?php
        $sqlConta =  "
            SELECT  COUNT(tbl_os_extra.os)  AS qtde_os          ,
                    SUM(tbl_os.pecas)       AS total_pecas     ,
                    SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
					tbl_extrato.avulso      AS total_avulso
					FROM    tbl_os
            JOIN    tbl_os_extra    USING (os)
            JOIN    tbl_extrato     USING(extrato)
            WHERE   tbl_os_extra.extrato = ".$result->extrato."
      GROUP BY      tbl_extrato.avulso";
        $resConta = pg_query($con,$sqlConta);
        $valores = pg_fetch_object($resConta);
?>
            <td style="text-align:right"><?=$valores->qtde_os?></td>
            <td nowrap style="text-align:right;">R$ <?=number_format($valores->total_pecas,2,',','.')?></td>
            <td nowrap style="text-align:right;">R$ <?=number_format($valores->total_maodeobra,2,',','.')?></td>
            <td nowrap style="text-align:right;">R$ <?=number_format($valores->total_avulso,2,',','.')?></td>
            <td nowrap style="text-align:right;">R$ <?=number_format($result->total,2,',','.')?></td>
            <td nowrap style="text-align:center;">
                <a class="btn btn-small btn-primary" href="os_extrato_print_blackedecker.php?extrato=<?=$result->extrato?>" target="_blank" role="button">Simplificado</a>
                <a class="btn btn-small btn-primary" href="os_extrato_detalhe_print_blackedecker.php?extrato=<?=$result->extrato?>" target="_blank" role="button">Detalhado</a>
                <a class="btn btn-small btn-primary" href="extrato_consulta_os_print.php?extrato=<?=$result->extrato?>" target="_blank" role="button">Imprimir</a>
            </td>
        </tr>
<?php
        $conteudo .= "
            <tr>
                <td>".$result->codigo_posto."</td>
                <td>".$result->nome."</td>
                <td>".$result->tipo_posto."</td>
                <td>".$result->protocolo."</td>
                <td>".$result->data_geracao."</td>
                <td>".$valores->qtde_os."</td>
                <td>R$ ".number_format($valores->total_pecas,2,',','.')."</td>
                <td>R$ ".number_format($valores->total_maodeobra,2,',','.')."</td>
                <td>R$ ".number_format($valores->total_avulso,2,',','.')."</td>
                <td>R$ ".number_format($result->total,2,',','.')."</td>
            </tr>
        ";
    }
    $total = array_sum($total_total);

    $arquivo = "<table  border='1'>";
    $arquivo .= $cabecalho;
    $arquivo .= "<tbody>";
    $arquivo .= $conteudo;
    $arquivo .= "<tr>";
    $arquivo .= "<td colspan='9' align='right'>TOTAL: </td>";
    $arquivo .= "<td>R$ ".number_format($total,2,',','.')."</td>";
    $arquivo .= "</tr>";
    $arquivo .= "</tbody>";
    $arquivo .= "</table>";

    $caminho = "xls/relatorio-extrato-assinatura-$login_fabrica-".date('Y-m-d').".xls";
    $fp = fopen($caminho,"w");
    fwrite($fp,$arquivo);
    fclose($fp);

?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="10" style="text-align:right;font-weight:bold;">TOTAL: </td>
            <td>R$ <?=number_format($total,2,",",".")?></td>
            <td colspan="100%">&nbsp;</td>
        <tr/>
        <tr>
            <td colspan="2">Com Marcados: </td>
            <td colspan="2">
                <button class="btn btn-small btn-success acao" id="aprovar" type="button">Aprovar</button>
                <button class="btn btn-small btn-danger acao"  id="recusar" type="button">Recusar</button>
            </td>
            <td colspan="100%">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="100%" style="text-align:center;">
                <a class="btn btn-success" href="<?=$caminho?>" role="button">Gerar Planilha</a>
            </td>
        </tr>
    </tfoot>
</table>
<?php
}
include "rodape.php";
?>
