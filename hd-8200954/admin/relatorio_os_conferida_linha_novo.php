<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
    include_once "autentica_admin.php";
}

include "gera_relatorio_pararelo_include.php";
include_once "funcoes.php";
if ($_REQUEST["btn_acao"] == "submit") {

    $data_incf     = $_REQUEST["data_inicial"];
    $data_flcf     = $_REQUEST["data_final"];
    $nf            = $_REQUEST["nf"];
    $data_pesquisa = $_REQUEST["data_pesquisa"];
    $codigo_posto  = $_REQUEST["codigo_posto"];

    if (((strlen($data_incf) == 0) and (strlen($data_flcf) == 0)) and (strlen($nf) == 0)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios, Data e/ou Nota Fiscal";
        $msg_erro["campos"][] = "data";
        $msg_erro["campos"][] = "nf";
    }

    if (strlen($data_incf) > 0) {

        list($di, $mi, $yi) = explode("/", $data_incf);
        if (!checkdate($mi,$di,$yi)){
            $msg_erro["msg"][]    = "Data Inicial inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if (strlen($data_flcf) > 0) {

        list($df, $mf, $yf) = explode("/", $data_flcf);
        if (!checkdate($mf,$df,$yf)){
            $msg_erro["msg"][]    = "Data Final inválida";
            $msg_erro["campos"][] = "data";
        }

    }

    if (count($msg_erro['msg']) == 0 and strlen($data_incf) > 0 and strlen($data_flcf) > 0) {
        $aux_data_incf = "$yi-$mi-$di";
        $aux_data_flcf = "$yf-$mf-$df";
    }

   if (count($msg_erro['msg']) == 0 and strlen($data_incf) > 0 and strlen($data_flcf) > 0) {
        if (strtotime($aux_data_flcf) < strtotime($aux_data_incf)) {
            $msg_erro["msg"][]    = "Data Final menor do que a Data Inicial";
            $msg_erro["campos"][] = "data";
        }
    }

    if (count($msg_erro['msg']) == 0 and strlen($data_incf) > 0 and strlen($data_flcf)>  0 ){
        if (strtotime($aux_data_incf) < strtotime($aux_data_flcf . ' -31 days')) {
            $msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior do que 1 mês";
            $msg_erro["campos"][] = "data";
        }
    }

    if ((strlen($data_incf) > 0) and (strlen($data_flcf) >0)) {
        $data_inicialcf = str_replace("'","",fnc_formata_data_pg($data_incf));
        $data_finalcf   = str_replace("'","",fnc_formata_data_pg($data_flcf));
    }

    if (strlen($codigo_posto) > 0) {
        $sqlposto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
        $resposto = pg_query($con, $sqlposto);
        $postoid  = pg_fetch_result($resposto, 0, 0);
        $sqlcondposto = "AND tbl_extrato.posto = $postoid";
        $sqlcondlposto = "AND tbl_extrato_lancamento.posto = $postoid";
    }

    $tipo_data = ($data_pesquisa == 'data_conferencia') ? "data_conferencia" : "data_lancamento_nota";

    if ((strlen($nf) > 0) and ((strlen($data_incf) == 0) and (strlen($data_flcf) == 0))) {
        $sqlcondnf = "AND tbl_extrato_conferencia.nota_fiscal = '$nf'";
    }

    if ((strlen($nf) == 0) and ((strlen($data_incf) > 0) and (strlen($data_flcf) > 0))) {
        $sqlcondnf = "AND tbl_extrato_conferencia.$tipo_data BETWEEN '$data_inicialcf 00:00:00' and '$data_finalcf 23:59:59' ";
        $sqlcondna = "AND tbl_extrato_nota_avulsa.data_lancamento BETWEEN '$data_inicialcf 00:00:00' and '$data_finalcf 23:59:59'";
        $data_mes  = substr($data_inicialcf,0,7);
    }

    if ((strlen($nf) > 0) and ((strlen($data_incf) > 0) and (strlen($data_flcf) > 0))) {
        $sqlcondnf = "AND tbl_extrato_conferencia.$tipo_data BETWEEN '$data_inicialcf 00:00:00' and '$data_finalcf 23:59:59' ";
        $sqlcondna = "AND tbl_extrato_nota_avulsa.data_lancamento BETWEEN '$data_inicialcf' and '$data_finalcf' ";
        $data_mes  = substr($data_inicialcf,0,7);
        $sqlcondnf .= "AND tbl_extrato_conferencia.nota_fiscal = '$nf' ";
    }

    if (strlen($caixa) > 0) {
        $sqlcondcaixa = "AND tbl_extrato_conferencia.caixa = '$caixa'";
    }

    if ($_POST["gerar_excel"]) {

        if(count($msg_erro['msg'])== 0){
            $sqll = "SELECT DISTINCT tbl_posto.posto,
                            to_char (tbl_extrato_conferencia.data_conferencia, 'dd/mm/yyyy') AS data_conferencia   ,
                            to_char (tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao   ,
                            tbl_admin.nome_completo AS admin_nome,
                            tbl_extrato_conferencia.nota_fiscal,
                            to_char (tbl_extrato_conferencia.previsao_pagamento, 'dd/mm/yyyy') AS previsao_pagamento   ,
                            to_char (tbl_extrato_conferencia.data_lancamento_nota, 'dd/mm/yyyy') AS data_lancamento_nota ,
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_os.os,
                            tbl_os.sua_os,
                            OEX.os AS reincidente,
                            OEX.sua_os AS os_reincidente,
                            tbl_os.serie,
                            tbl_os.nota_fiscal AS nf_compra,
                            tbl_os.consumidor_revenda,
                            tbl_os.consumidor_nome,
                            tbl_os.revenda_nome,
                            tbl_produto.referencia,
                            tbl_produto.descricao,
                            case when tbl_os.sinalizador <> 3 or tbl_os.sinalizador isnull  then tbl_os_extra.mao_de_obra else 0 end as mao_de_obra,
                            to_char (tbl_os.data_digitacao, 'dd/mm/yyyy') AS data_digitacao,
                            to_char (tbl_os.data_abertura, 'dd/mm/yyyy') AS data_abertura,
                            to_char (tbl_os.data_fechamento, 'dd/mm/yyyy') AS data_fechamento,
                            to_char (tbl_os.finalizada, 'dd/mm/yyyy') AS finalizada,
                            tbl_linha.nome AS linha,
                            tbl_familia.descricao AS familia
                        FROM tbl_os
                        JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os AND tbl_os_extra.extrato notnull and i_fabrica = $login_fabrica
                        JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica
                        JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
                        JOIN tbl_admin ON tbl_extrato_conferencia.admin = tbl_admin.admin
                        JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
                        JOIN tbl_posto_fabrica ON tbl_posto.posto= tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
                        JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
                        JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
                        LEFT JOIN tbl_os OEX ON tbl_os_extra.os_reincidente = OEX.os and OEX.fabrica = $login_fabrica
                        WHERE tbl_os.fabrica = $login_fabrica
                        AND tbl_extrato_conferencia.cancelada IS NOT TRUE
			$sqlcondnf
			ORDER BY tbl_posto.posto";
            #echo nl2br($sqll);exit;
            $resl  = pg_query($con, $sqll);
            $total = pg_num_rows($resl);

            if ($total > 0) {
                $data = date("d-m-Y-H:i");
                $fileName = "relatorio_os_conferida-$data.xls";

                $file = fopen("/tmp/{$fileName}", "w");

                fwrite($file,"Cód. Posto\tPosto\tOS\tOS Reincidente\tSérie\tNF. Compra\tC/R\tConsumidor\tRevenda\tCód. Produto\tProduto\tOS (data) Digitação\tOS (data)Abertura\tOS (data) Fechamento\tOS (data) Finalizada\tData do Extrato\tM.O. do Produto\tNota Fiscal\tNF de Digitação\tPrevisão Pgto.\tLinha\tFamília\tAdmin\n");

		for($k=0;$k<pg_num_rows($resl);$k++){
			fwrite($file,pg_fetch_result($resl,$k,'codigo_posto')."\t".pg_fetch_result($resl,$k,'nome')."\t".pg_fetch_result($resl,$k,'sua_os')."\t".pg_fetch_result($resl,$k,'os_reincidente')."\t".pg_fetch_result($resl,$k,'serie')."\t".pg_fetch_result($resl,$k,'nf_compra')."\t".pg_fetch_result($resl,$k,'consumidor_revenda')."\t".pg_fetch_result($resl,$k,'consumidor_nome')."\t".pg_fetch_result($resl,$k,'revenda_nome')."\t".pg_fetch_result($resl,$k,'referencia')."\t".pg_fetch_result($resl,$k,'descricao')."\t".pg_fetch_result($resl,$k,'data_digitacao')."\t".pg_fetch_result($resl,$k,'data_abertura')."\t".pg_fetch_result($resl,$k,'data_fechamento')."\t".pg_fetch_result($resl,$k,'finalizada')."\t".pg_fetch_result($resl,$k,'data_geracao')."\t".pg_fetch_result($resl,$k,'mao_de_obra')."\t".pg_fetch_result($resl,$k,'nota_fiscal')."\t".pg_fetch_result($resl,$k,'data_lancamento_nota')."\t".pg_fetch_result($resl,$k,'previsao_pagamento')."\t".pg_fetch_result($resl,$k,'linha')."\t".pg_fetch_result($resl,$k,'familia')."\t".pg_fetch_result($resl,$k,'admin_nome')."\n");
	       	}
                    fclose($file);
                    if (file_exists("/tmp/{$fileName}")) {
                        system("mv /tmp/{$fileName} xls/{$fileName}");
                        echo "xls/{$fileName}";
                    }
            }
        }
        exit;
    }
}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE ORDENS DE SERVIÇO CONFERIDAS POR LINHA";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask"
);

include("plugin_loader.php");
?>

<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_inicial", "data_final"));
        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
	});

	$("#gera_excel").click(function(){
		var data_inicial = $("#data_inicial").val();
		var data_final = $("#data_final").val();
		var codigo_posto = $("#codigo_posto").val();
		var nf = $("#nf").val();
		var caixa = $("#caixa").val();
		var data_pesquisa = $("input[name=data_pesquisa]:checked").val();
		$.ajax({
			url: "relatorio_os_conferida_linha_novo.php",
			type: "POST",
			data: {data_inicial:data_inicial,data_final:data_final,codigo_posto:codigo_posto,nf:nf,caixa:caixa,data_pesquisa:data_pesquisa,gerar_excel:'true',btn_acao:'submit'},
			complete: function(data){
				window.open(data.responseText);
			}
		});
	
	}); 

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function mostraLancamento(lancamento){
        $('#sem_'+lancamento).toggle();
    }

    function mostraLancamento2(lancamento){
        $('#com_'+lancamento).toggle();
    }

    

</script>


<?php

if ($_POST["btn_acao"] == "submit" AND strlen($msg_erro['msg'][0]) == 0) {
    include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro['msg'][0]) == 0) {
   include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro["msg"][0]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
$checked =  ($data_pesquisa=='data_lancamento')?" CHECKED ":"";
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' method='post' class='form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
        <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
                    <div class='controls controls-row'>
                         <label class="radio">
                            <input type="radio" name="data_pesquisa" value= "data_conferencia" checked>
                            Data de Conferência
                        </label>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_pesquisa", $msg_erro["campos"])) ? "error" : ""?>'>
                <div class='controls controls-row'>
                    <label class="radio">
                        <input type="radio" name="data_pesquisa" value="data_lancamento" <?=$checked?> >
                        Data Lançamento Nota
                    </label>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
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
                        <h5 class='asteristico'>*</h5>
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

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("nf", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='nf'>Nota Fiscal</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="nf" id="nf" size="12" maxlength="10" class='span12' value= "<?=$nf?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("caixa", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='caixa'>Caixa</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="text" name="caixa" id="caixa" size="12" maxlength="10" class='span12' value="<?=$caixa?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));$(this).hide().parents('p').html('Aguarde Processamento')">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>

<br /><?php

if ($_REQUEST["btn_acao"] == "submit" AND count($msg_erro['msg']) == 0) {

    $jsonPOST = excelPostToJson($_POST);
    ?>

    <div id='gera_excel' class='btn_excel'>
        <span><img src='imagens/excel.png' /></span>
        <span class="txt">Excel OS conferidas</span>
    </div> <br />
<?php
    ob_start();//INICIA BUFFER

    $sql = "SELECT
                tbl_extrato.posto                              ,
                tbl_lancamento.descricao                       ,
                tbl_lancamento.lancamento                      ,
                tbl_extrato_lancamento.valor as total_valor    ,
                extrato_lancamento as total_lancamento         ,
                tbl_extrato_lancamento.data_lancamento         ,
                tbl_extrato_lancamento.valor                   ,
                tbl_extrato_lancamento.historico               ,
                tbl_extrato_lancamento.extrato                 ,
                tbl_extrato_lancamento.admin                   ,
                tbl_extrato_conferencia.nota_fiscal            ,
                tbl_extrato_conferencia.data_nf                ,
                tbl_extrato_conferencia.valor_nf               ,
                tbl_extrato_conferencia.caixa
            INTO TEMP tmp_lancamento_$login_admin
            FROM    tbl_extrato_lancamento
            JOIN    tbl_extrato USING(extrato)
            JOIN    tbl_extrato_conferencia using(extrato)
            JOIN    tbl_lancamento USING(lancamento)
            WHERE tbl_extrato_lancamento.fabrica = $login_fabrica
            AND   tbl_lancamento.fabrica = $login_fabrica
            AND   tbl_extrato.fabrica = $login_fabrica
			AND   tbl_lancamento.lancamento <> 153
            /*AND   tbl_lancamento.ativo */
            AND (tbl_extrato_lancamento.admin IS NOT NULL OR tbl_extrato_lancamento.lancamento in (103,104))
            and cancelada is not true
            $sqlcondposto
            $sqlcondnf
            ;

             SELECT
                tbl_extrato.posto                              ,
                tbl_lancamento.descricao                       ,
                tbl_lancamento.lancamento                      ,
                tbl_extrato_lancamento.valor as total_valor    ,
                extrato_lancamento as total_lancamento         ,
                tbl_extrato_lancamento.data_lancamento         ,
                tbl_extrato_lancamento.valor                   ,
                tbl_extrato_lancamento.historico               ,
                tbl_extrato_lancamento.extrato                 ,
                tbl_extrato_lancamento.admin                   ,
                tbl_extrato_conferencia.nota_fiscal            ,
                tbl_extrato_conferencia.data_nf                ,
                tbl_extrato_conferencia.valor_nf               ,
                tbl_extrato_conferencia.caixa
            INTO TEMP tmp_lancamento_nota_$login_admin
            FROM    tbl_extrato_lancamento
            JOIN    tbl_extrato USING(extrato)
            JOIN    tbl_extrato_conferencia using(extrato)
            JOIN    tbl_lancamento USING(lancamento)
            WHERE tbl_extrato_lancamento.fabrica = $login_fabrica
            AND   tbl_lancamento.fabrica = $login_fabrica
            AND   tbl_extrato.fabrica = $login_fabrica
			AND   tbl_lancamento.lancamento <> 153
            /*AND   tbl_lancamento.ativo */
            AND (tbl_extrato_lancamento.admin IS NOT NULL OR tbl_extrato_lancamento.lancamento in (103,104))
            AND cancelada is not true
            AND NOT (tbl_extrato_conferencia.nota_fiscal IS NULL)
            $sqlcondposto
            $sqlcondnf
            ;

            SELECT   tbl_extrato_nota_avulsa.data_lancamento     ,
                     tbl_extrato_nota_avulsa.nota_fiscal         ,
                     tbl_extrato_nota_avulsa.data_emissao        ,
                     tbl_extrato_nota_avulsa.valor_original      ,
                     tbl_extrato_nota_avulsa.previsao_pagamento  ,
                     tbl_admin.login                             ,
                     tbl_extrato_nota_avulsa.observacao          ,
                     tbl_posto_fabrica.codigo_posto              ,
                     tbl_posto.nome                              ,
                     tbl_extrato_conferencia.caixa               ,
                     to_char (tbl_extrato_conferencia.data_conferencia, 'dd/mm/yyyy') AS data_conferencia   ,
                     to_char (tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao
            INTO    TEMP tmp_avulsa_$login_admin
            FROM    tbl_extrato_nota_avulsa
            LEFT JOIN    tbl_extrato_conferencia using(extrato)
            JOIN    tbl_admin ON tbl_extrato_nota_avulsa.admin = tbl_admin.admin
            JOIN    tbl_extrato USING(extrato)
            JOIN    tbl_posto ON tbl_extrato.posto = tbl_posto.posto
            JOIN    tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE   tbl_extrato_conferencia.cancelada IS NOT TRUE
            AND     tbl_extrato.fabrica = $login_fabrica
            AND     tbl_extrato_nota_avulsa.fabrica = $login_fabrica
            $sqlcondposto
            $sqlcondpgto
            $sqlcondna
            $sqlcondcaixa
            ;

            SELECT  tbl_extrato_conferencia.extrato_conferencia                                         ,
                    tbl_extrato_conferencia.extrato                                                     ,
                    to_char (tbl_extrato_conferencia.data_conferencia, 'dd/mm/yyyy') AS dat_conferencia ,
                    to_char (tbl_extrato_conferencia.data_lancamento_nota, 'dd/mm/yyyy') AS dat_lancamento_nota ,
                    tbl_extrato_conferencia.nota_fiscal                                                 ,
                    to_char (tbl_extrato_conferencia.data_nf, 'dd/mm/yyyy') AS dat_nf                   ,
                    tbl_extrato_conferencia.valor_nf                                                    ,
                    tbl_extrato_conferencia.caixa                                                       ,
                    tbl_extrato_conferencia.obs_fabricante                                              ,
                    tbl_extrato_conferencia.obs_posto                                                   ,
                    tbl_extrato_conferencia.valor_nf_a_pagar AS valor_pagar                             ,
                    to_char (tbl_extrato_conferencia.previsao_pagamento, 'dd/mm/yyyy') AS previsao_pgto ,
                    tbl_extrato_conferencia_item.linha                                                  ,
                    tbl_extrato_conferencia_item.extrato_conferencia_item                               ,
                    tbl_extrato_conferencia_item.qtde_conferida                                         ,
                    tbl_extrato_conferencia_item.mao_de_obra_unitario                       as unitario,
                    tbl_extrato.posto                                                                   ,
                    to_char (tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao                    ,
                    tbl_linha.nome AS linha_nome                                                        ,
                    tbl_posto_fabrica.codigo_posto                                                      ,
                    tbl_posto.nome AS posto_nome                                                        ,
                    tbl_admin.nome_completo AS admin_nome
                INTO TEMP TABLE tmp_rel_os_conf_$login_admin
                FROM tbl_extrato_conferencia
                JOIN tbl_extrato        USING(extrato)
                JOIN tbl_posto          USING(posto)
                JOIN tbl_admin  ON tbl_admin.admin = tbl_extrato_conferencia.admin
                JOIN tbl_extrato_conferencia_item ON tbl_extrato_conferencia.extrato_conferencia = tbl_extrato_conferencia_item.extrato_conferencia
                JOIN tbl_linha ON tbl_extrato_conferencia_item.linha = tbl_linha.linha
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE tbl_extrato.fabrica = $login_fabrica
                AND   tbl_extrato_conferencia.cancelada IS NOT TRUE
                $sqlcondposto
                $sqlcondpgto
                $sqlcondnf
                $sqlcondcaixa;

            SELECT DISTINCT extrato                         ,
                        dat_conferencia                     ,
                        dat_lancamento_nota                 ,
                        nota_fiscal                         ,
                        dat_nf                              ,
                        valor_nf                            ,
                        caixa                               ,
                        obs_fabricante                      ,
                        obs_posto                           ,
                        previsao_pgto                       ,
                        linha                               ,
                        linha_nome                          ,
                        unitario                            ,
                        codigo_posto                        ,
                        valor_pagar                         ,
                        posto_nome                          ,
                        admin_nome                          ,
                        data_geracao                        ,
                        posto
                FROM tmp_rel_os_conf_$login_admin
                ORDER BY posto_nome, nota_fiscal, dat_nf,extrato";
    #echo nl2br($sql); exit;
    $res = pg_query($con, $sql);

    $sqlx = "SELECT TO_CHAR(data_lancamento,'DD/MM/YYYY')    AS data_lancamento   ,
                    nota_fiscal                                                   ,
                    TO_CHAR(data_emissao,'DD/MM/YYYY')       AS data_emissao      ,
                    valor_original                                                ,
                    TO_CHAR(previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento,
                    login                                                         ,
                    observacao                                                    ,
                    codigo_posto                                                  ,
                    nome                                                          ,
                    data_geracao                                                  ,
                    data_conferencia                                              ,
                    caixa
               FROM tmp_avulsa_$login_admin
              ORDER BY data_lancamento";

    $resx  = pg_query($con, $sqlx);
    $total = pg_num_rows($resx);

    if ($total > 0) {

        echo "<br />";
        echo "<table class='table table-striped table-bordered table-hover table-large' style='margin:0 auto;'>";
            echo "<thead>";
                echo "<tr class='titulo_coluna' >";
                    echo "<td nowrap class='tac' colspan='100%'>Nota Avulsa</td>";
                echo "</tr>";
                echo "<tr class='titulo_coluna'>";
                    echo "<td nowrap class='tac'><b>Cód. Posto</b></a></td>";
                    echo "<td nowrap class='tac'><b>Posto</b></a></td>";
                    echo "<td nowrap class='tac'><b>Linha</b></a></td>";
                    echo "<td nowrap class='tac'><b>M. O. Unit.</b></a></td>";
                    echo "<td nowrap class='tac'><b>OSs Conf.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Total OS</b></a></td>";
                    echo "<td nowrap class='tac'><b>Data Conf.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Nota Fiscal</b></a></td>";
                    echo "<td nowrap class='tac'><b>NF. Digit</b></a></td>";
                    echo "<td nowrap class='tac'><b>Data NF</b></a></td>";
                    echo "<td nowrap class='tac'><b>Valor NF</b></a></td>";
                    echo "<td nowrap class='tac'><b>Valor Total Linha</b></a></td>";
                    echo "<td nowrap class='tac'><b>Valor a Pagar</b></a></td>";
                    echo "<td nowrap class='tac'><b>Caixa</b></a></td>";
                    echo "<td nowrap class='tac'><b>Obs Britânia</b></a></td>";
                    echo "<td nowrap class='tac'><b>Obs Posto</b></a></td>";
                    echo "<td nowrap class='tac'><b>Previsão Pgto.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Data Extr.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Admin</b></a></td>";
                echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            for ($x = 0 ; $x < $total; $x++) {

                $cor = ($x % 2)? "#F7F5F0" : "#F1F4FA";

                $data_lancamento    = pg_fetch_result($resx, $x, 'data_lancamento');
                $nota_fiscal        = pg_fetch_result($resx, $x, 'nota_fiscal');
                $codigo_posto       = pg_fetch_result($resx, $x, 'codigo_posto');
                $data_emissao       = pg_fetch_result($resx, $x, 'data_emissao');
                $valor_original     = pg_fetch_result($resx, $x, 'valor_original');
                $previsao_pagamento = pg_fetch_result($resx, $x, 'previsao_pagamento');
                $login              = pg_fetch_result($resx, $x, 'login');
                $observacao         = trim(pg_fetch_result($resx, $x, 'observacao'));
                $nome               = trim(pg_fetch_result($resx, $x, 'nome'));
                $data_geracao       = pg_fetch_result($resx, $x, 'data_geracao');
                $dt_conferencia     = pg_fetch_result($resx, $x, 'data_conferencia');
                $caixa              = pg_fetch_result($resx, $x, 'caixa');

                echo "<tr>";
                    echo "<td nowrap class='tac'>$codigo_posto</td>";
                    echo "<td class='tac'>$nome</td>";
                    echo "<td class='tac'></td>";
                    echo "<td class='tac'>0.</td>";
                    echo "<td class='tac'>0.</td>";
                    echo "<td class='tac'>0.</td>";
                    echo "<td class='tac'>$dt_conferencia</td>";
                    echo "<td nowrap class='tac'>$nota_fiscal</td>";
                    echo "<td class='tac'>$data_lancamento</td>";
                    echo "<td nowrap class='tac'>$data_emissao</td>";
                    echo "<td nowrap class='tac'>".number_format($valor_original,2,",",".")."</td>";
                    echo "<td class='tac'>".number_format($valor_original,2,",",".")."</td>";
                    echo "<td class='tac'>".number_format($valor_original,2,",",".")."</td>";
                    echo "<td class='tac'>$caixa</td>";
                    echo "<td class='tac'>$observacao</td>";
                    echo "<td class='tac'></td>";
                    echo "<td nowrap class='tac'>$previsao_pagamento</td>";
                    echo "<td nowrap class='tac'>$data_geracao</td>";
                    echo "<td nowrap class='tac'>$login</td>";
                echo "</tr>";
            }

            echo "</tbody>";
        echo "</table>";

    }

    $sqlx = "SELECT lancamento                                  ,
                    descricao                                   ,
                    SUM(total_valor)        AS total_valor      ,
                    COUNT(total_lancamento) AS total_lancamento
               FROM tmp_lancamento_$login_admin
              GROUP BY descricao, lancamento
              ORDER BY descricao";

    $resx = pg_query($con, $sqlx);
    $totx = pg_num_rows($resx);

    if ($totx > 0) {

        echo "<br />";

        echo "<table class='table table-striped table-bordered table-hover table-large' style='margin:0 auto;'>";
            echo "<thead>";
            echo "<tr class='titulo_coluna' >";
                echo "<td nowrap class='tac' colspan='100%'>Lançamento Avulso</td>";
            echo "</tr>";
                echo "<tr class='titulo_coluna'>";
                    echo "<td nowrap class='tac'><b>Lançamento</b></td>";
                    echo "<td nowrap class='tac'><b>Qtde</b></td>";
                    echo "<td nowrap class='tac'><b>Valor</b></td>";
                echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            for ($x = 0 ; $x < $totx; $x++) {

                $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                $total_valor      = pg_fetch_result($resx, $x, 'total_valor');
                $total_lancamento = pg_fetch_result($resx, $x, 'total_lancamento');
                $descricao        = pg_fetch_result($resx, $x, 'descricao');
                $lancamento       = pg_fetch_result($resx, $x, 'lancamento');

                echo "<tr>";
                    echo "<td class='tac'><a href='javascript:mostraLancamento($lancamento)'>$descricao</a></td>";
                    echo "<td class='tac'>$total_lancamento</td>";
                    echo "<td nowrap class='tac'>".number_format($total_valor,2,",",".")."</td>";
                echo "</tr>";
                echo "<tr style='display:none' id='sem_$lancamento'>";
                    echo "<td colspan='100%'>";
                        echo "<center>";
                            echo "<div style='width:98%;'>";
                                echo "<table  class='table table-striped table-bordered table-hover table-large' >";
                                    echo "<thead>";
                                        echo "<tr class='titulo_coluna'>";
                                            echo "<td nowrap class='tac'><b>Cód. Posto</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Posto</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Linha</b></a></td>";
                                            echo "<td nowrap class='tac'><b>M. O. Unit.</b></a></td>";
                                            echo "<td nowrap class='tac'><b>OSs Conf.</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Total OS</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Data Lanc.</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Nota Fiscal</b></a></td>";
                                            echo "<td nowrap class='tac'><b>NF. Digit</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Data NF</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Valor NF</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Valor Total Linha</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Valor a Pagar</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Caixa</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Obs Britânia</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Obs Posto</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Previsão Pgto.</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Data Extr.</b></a></td>";
                                            echo "<td nowrap class='tac'><b>Admin</b></a></td>";
                                        echo "</tr>";
                                    echo "</thead>";
                                    echo "<tbody>";

                                    $sqll = "SELECT codigo_posto,
                                                    nome,
                                                    to_char(data_lancamento,'DD/MM/YYYY') AS data_lancamento,
                                                    valor,
                                                    historico,
                                                    to_char(data_geracao,'DD/MM/YYYY')    AS data_geracao,
                                                    nome_completo,
                                                    nota_fiscal            ,
                                                    to_char(data_nf,'DD/MM/YYYY') as  data_nf           ,
                                                    valor_nf               ,
                                                    caixa
                                               FROM tmp_lancamento_$login_admin
                                               JOIN tbl_extrato USING(extrato)
                                               JOIN tbl_posto         ON tbl_posto.posto = tbl_extrato.posto
                                               JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                          LEFT JOIN tbl_admin         ON tmp_lancamento_$login_admin.admin = tbl_admin.admin
                                            WHERE tbl_extrato.fabrica = $login_fabrica
                                              AND lancamento = $lancamento
                                            ORDER BY codigo_posto";

                                    $resl = pg_query($con, $sqll);
                                    $totl  = pg_num_rows($resl);

                                    if ($totl > 0) {

                                        for ($l = 0; $l < $totl; $l++) {

                                            $codigo_posto    = pg_fetch_result($resl, $l, 'codigo_posto');
                                            $nome            = pg_fetch_result($resl, $l, 'nome');
                                            $data_lancamento = pg_fetch_result($resl, $l, 'data_lancamento');
                                            $valor           = pg_fetch_result($resl, $l, 'valor');
                                            $historico       = pg_fetch_result($resl, $l, 'historico');
                                            $data_geracao    = pg_fetch_result($resl, $l, 'data_geracao');
                                            $nome_completo   = pg_fetch_result($resl, $l, 'nome_completo');
                                            $nota_fiscal     = pg_fetch_result($resl, $l, 'nota_fiscal');
                                            $data_nf         = pg_fetch_result($resl, $l, 'data_nf');
                                            $valor_nf        = pg_fetch_result($resl, $l, 'valor_nf');
                                            $caixa           = pg_fetch_result($resl, $l, 'caixa');

                                            $cor = ($l % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                                            echo "<tr>";
                                                echo "<td nowrap class='tac'>$codigo_posto</td>";
                                                echo "<td nowrap class='tac'>$nome</td>";
                                                echo "<td nowrap class='tac'></td>";
                                                echo "<td nowrap class='tac'>0.</td>";
                                                echo "<td nowrap class='tac'>0.</td>";
                                                echo "<td nowrap class='tac'>0.</td>";
                                                echo "<td nowrap class='tac'>$data_lancamento</td>";
                                                echo "<td nowrap class='tac'>$nota_fiscal</td>";
                                                echo "<td nowrap class='tac'></td>";
                                                echo "<td nowrap class='tac'>$data_nf</td>";
                                                echo "<td nowrap class='tac'>R$ ".number_format($valor_nf,2,",",".")."</td>";
                                                echo "<td nowrap class='tac'>R$ ".number_format($valor,2,",",".")."</td>";
                                                echo "<td nowrap class='tac'>R$ ".number_format($valor_nf,2,",",".")."</td>";
                                                echo "<td nowrap class='tac'>$caixa</td>";
                                                echo "<td nowrap class='tac'>$historico</td>";
                                                echo "<td nowrap class='tac'></td>";
                                                echo "<td nowrap class='tac'></td>";
                                                echo "<td nowrap class='tac'>$data_geracao</td>";
                                                echo "<td nowrap class='tac'>$nome_completo</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    echo "</tbody>";
                                echo "</table>";
                            echo "</div>";
                        echo "</center>";

                        echo "<br />";
                    echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
        echo "</table>";
    }

    $sqlx = "SELECT lancamento                                 ,
                    descricao                                  ,
                    SUM(total_valor)        AS total_valor     ,
                    COUNT(total_lancamento) AS total_lancamento
               FROM tmp_lancamento_nota_$login_admin
              GROUP BY descricao,lancamento
              ORDER BY descricao";

    $resx  = pg_query($con, $sqlx);
    $total = pg_num_rows($resx);

    if ($total > 0) {

        echo "<br />";

        echo "<table  class='table table-striped table-bordered table-hover table-large' style='margin:0 auto;'>";
            echo "<thead>";
                echo "<tr class='titulo_coluna' >";
                    echo "<td nowrap class='tac' colspan='100%'>Lançamento Avulso(Com Nota)</td>";
                echo "</tr>";
                echo "<tr class='titulo_tabela'>";
                    echo "<td nowrap class='tac'><b>Lançamento</b></td>";
                    echo "<td nowrap class='tac'><b>Qtde</b></td>";
                    echo "<td nowrap class='tac'><b>Valor</b></td>";
                echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            for ($x = 0 ; $x < $total; $x++) {

                $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                $total_valor      = pg_fetch_result($resx, $x, 'total_valor');
                $total_lancamento = pg_fetch_result($resx, $x, 'total_lancamento');
                $descricao        = pg_fetch_result($resx, $x, 'descricao');
                $lancamento       = pg_fetch_result($resx, $x, 'lancamento');

                echo "<tr>";
                    echo "<td class='tac'><a href='javascript:mostraLancamento2($lancamento)'>$descricao</a></td>";
                    echo "<td class='tac'>$total_lancamento</td>";
                    echo "<td nowrap class='tac'>".number_format($total_valor,2,",",".")."</td>";
                echo "</tr>";
                echo "<tr style='display:none' id='com_$lancamento'>";
                    echo "<td colspan='100%'>";
                            echo "<table class='table table-striped table-bordered table-hover table-large'>";
                                echo "<thead>";
                                    echo "<tr class='titulo_coluna' >";
                                        echo "<td nowrap class='tac'><b>Cód. Posto</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Posto</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Linha</b></a></td>";
                                        echo "<td nowrap class='tac'><b>M. O. Unit.</b></a></td>";
                                        echo "<td nowrap class='tac'><b>OSs Conf.</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Total OS</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Data Lanc.</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Nota Fiscal</b></a></td>";
                                        echo "<td nowrap class='tac'><b>NF. Digit</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Data NF</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Valor NF</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Valor Total Linha</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Valor a Pagar</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Caixa</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Obs Britânia</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Obs Posto</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Previsão Pgto.</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Data Extr.</b></a></td>";
                                        echo "<td nowrap class='tac'><b>Admin</b></a></td>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";

                                $sqll = "SELECT codigo_posto,
                                                nome,
                                                TO_CHAR(data_lancamento,'DD/MM/YYYY') AS data_lancamento,
                                                valor,
                                                historico,
                                                TO_CHAR(data_geracao,'DD/MM/YYYY')    AS data_geracao,
                                                nome_completo,
                                                nota_fiscal,
                                                TO_CHAR(data_nf,'DD/MM/YYYY')         AS  data_nf,
                                                valor_nf,
                                                caixa
                                           FROM tmp_lancamento_nota_$login_admin
                                           JOIN tbl_extrato USING(extrato)
                                           JOIN tbl_posto         ON tbl_posto.posto = tbl_extrato.posto
                                           JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                      LEFT JOIN tbl_admin         ON tmp_lancamento_nota_$login_admin.admin = tbl_admin.admin
                                          WHERE tbl_extrato.fabrica = $login_fabrica
                                            AND lancamento          = $lancamento
                                          ORDER BY codigo_posto";

                                $resl = pg_query($con, $sqll);
                                $tot  = pg_num_rows($resl);

                                if ($tot > 0) {

                                    for ($l = 0; $l < $tot; $l++) {

                                        $codigo_posto    = pg_fetch_result($resl, $l, 'codigo_posto');
                                        $nome            = pg_fetch_result($resl, $l, 'nome');
                                        $data_lancamento = pg_fetch_result($resl, $l, 'data_lancamento');
                                        $valor           = pg_fetch_result($resl, $l, 'valor');
                                        $historico       = pg_fetch_result($resl, $l, 'historico');
                                        $data_geracao    = pg_fetch_result($resl, $l, 'data_geracao');
                                        $nome_completo   = pg_fetch_result($resl, $l, 'nome_completo');
                                        $nota_fiscal     = pg_fetch_result($resl, $l, 'nota_fiscal');
                                        $data_nf         = pg_fetch_result($resl, $l, 'data_nf');
                                        $valor_nf        = pg_fetch_result($resl, $l, 'valor_nf');
                                        $caixa           = pg_fetch_result($resl, $l, 'caixa');

                                        $cor = ($l % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                                        echo "<tr>";
                                            echo "<td nowrap class='tac'>$codigo_posto</td>";
                                            echo "<td nowrap class='tac'>$nome</td>";
                                            echo "<td nowrap class='tac'></td>";
                                            echo "<td nowrap class='tac'>0.</td>";
                                            echo "<td nowrap class='tac'>0.</td>";
                                            echo "<td nowrap class='tac'>0.</td>";
                                            echo "<td nowrap class='tac'>$data_lancamento</td>";
                                            echo "<td nowrap class='tac'>$nota_fiscal</td>";
                                            echo "<td nowrap class='tac'></td>";
                                            echo "<td nowrap class='tac'>$data_nf</td>";
                                            echo "<td nowrap class='tac'>R$ ".number_format($valor_nf,2,",",".")."</td>";
                                            echo "<td nowrap class='tac'>R$ ".number_format($valor,2,",",".")."</td>";
                                            echo "<td nowrap class='tac'>R$ ".number_format($valor_nf,2,",",".")."</td>";
                                            echo "<td nowrap class='tac'>$caixa</td>";
                                            echo "<td nowrap class='tac'>$historico</td>";
                                            echo "<td nowrap class='tac'></td>";
                                            echo "<td nowrap class='tac'></td>";
                                            echo "<td nowrap class='tac'>$data_geracao</td>";
                                            echo "<td nowrap class='tac'>$nome_completo</td>";
                                        echo "</tr>";
                                    }
                                }
                                echo "</tbody>";
                            echo "</table>";
                        echo "<br/>";
                    echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
        echo "</table>";
    }

    if (strlen($data_inicialcf) > 0 AND strlen($data_finalcf) > 0) {

        $sqll = "SELECT DISTINCT tbl_extrato_lancamento.valor,
                        tbl_posto.posto,
                         to_char (tbl_extrato_conferencia.data_conferencia, 'dd/mm/yyyy') AS data_conferencia   ,
						 to_char (tbl_extrato.data_geracao, 'dd/mm/yyyy') AS data_geracao   ,
                        tbl_admin.nome_completo AS admin_nome,
						tbl_extrato_lancamento.admin,
                        tbl_extrato_lancamento.extrato,
						tbl_extrato_conferencia.nota_fiscal,
						tbl_extrato_conferencia.caixa,
                        tbl_posto.nome,
                        tbl_posto_fabrica.codigo_posto,
						array(SELECT extrato_lancamento
							FROM tbl_extrato_lancamento ex
							WHERE ex.extrato = tbl_extrato.extrato
							AND ex.lancamento = 153
							AND ex.historico like 'Regularização de OS%'
							AND ex.valor = tbl_extrato_lancamento.valor
							AND ex.admin = tbl_extrato_lancamento.admin
						) as lancamentos
                   FROM tbl_extrato_lancamento
				   JOIN tbl_extrato_conferencia USING(extrato)
				   JOIN tbl_extrato ON tbl_extrato_lancamento.extrato = tbl_extrato.extrato
                   JOIN tbl_admin ON tbl_extrato_lancamento.admin = tbl_admin.admin
                   JOIN tbl_posto ON tbl_posto.posto = tbl_extrato_lancamento.posto
                   JOIN tbl_posto_fabrica ON tbl_posto.posto= tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                  WHERE tbl_extrato_conferencia.cancelada IS NOT TRUE
				  AND tbl_extrato_lancamento.lancamento = 153
					AND tbl_extrato_lancamento.historico like 'Regularização de OS%'
                    $sqlcondnf
                    $sqlcondlposto
		    ORDER BY tbl_posto.posto, tbl_extrato_lancamento.valor";
        $resl  = pg_query($con, $sqll);
        $total = pg_num_rows($resl);

        if ($total > 0) {

            echo "<br />";
            echo "<table class='table table-striped table-bordered table-hover table-large' style='margin:0 auto;'>";
                echo "<thead>";
                    echo "<tr class='titulo_coluna' >";
                        echo "<td nowrap class='tac' colspan='100%'><b>Ressarcimento de OS</b></a></td>";
                    echo "</tr>";
                    echo "<tr class='titulo_coluna' >";
                        echo "<td nowrap class='tac'><b>Cód. Posto</b></a></td>";
                        echo "<td nowrap class='tac'><b>Posto</b></a></td>";
                        echo "<td nowrap class='tac'><b>Linha</b></a></td>";
                        echo "<td nowrap class='tac'><b>M. O. Unit.</b></a></td>";
                        echo "<td nowrap class='tac'><b>OSs Conf.</b></a></td>";
                        echo "<td nowrap class='tac'><b>Total OS</b></a></td>";
                        echo "<td nowrap class='tac'><b>Data Conf.</b></a></td>";
                        echo "<td nowrap class='tac'><b>Nota Fiscal</b></a></td>";
                        echo "<td nowrap class='tac'><b>NF. Digit</b></a></td>";
                        echo "<td nowrap class='tac'><b>Data NF</b></a></td>";
                        echo "<td nowrap class='tac'><b>Valor NF</b></a></td>";
                        echo "<td nowrap class='tac'><b>Valor Total Linha</b></a></td>";
                        echo "<td nowrap class='tac'><b>Valor a Pagar</b></a></td>";
                        echo "<td nowrap class='tac'><b>Caixa</b></a></td>";
                        echo "<td nowrap class='tac'><b>Obs Britânia</b></a></td>";
                        echo "<td nowrap class='tac'><b>Obs Posto</b></a></td>";
                        echo "<td nowrap class='tac'><b>Previsão Pgto.</b></a></td>";
                        echo "<td nowrap class='tac'><b>Data Extr.</b></a></td>";
                        echo "<td nowrap class='tac'><b>Admin</b></a></td>";
                    echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                for ($j = 0; $j < $total; $j++) {

                    $lancamentos     = pg_fetch_result($resl, $j, 'lancamentos');
                    $valor           = pg_fetch_result($resl, $j, 'valor');
                    $data_conferencia= pg_fetch_result($resl, $j, 'data_conferencia');
                    $admin_nome      = pg_fetch_result($resl, $j, 'admin_nome');
                    $posto           = pg_fetch_result($resl, $j, 'posto');
                    $extrato         = pg_fetch_result($resl, $j, 'extrato');
                    $codigo_posto    = pg_fetch_result($resl, $j, 'codigo_posto');
                    $posto_nome      = pg_fetch_result($resl, $j, 'nome');
					$nota_fiscal     = pg_fetch_result($resl, $j, 'nota_fiscal');
					$caixa           = pg_fetch_result($resl, $j, 'caixa');
					$data_geracao    = pg_fetch_result($resl, $j, 'data_geracao');

                    $cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

					$lancamentos = implode(",",preg_replace('/\D/','', explode(",",$lancamentos)));
					$sqlh = " SELECT historico
							FROM tbl_extrato_lancamento
							WHERE extrato_lancamento in ($lancamentos)";
					$resh = pg_query($con,$sqlh);
					if(pg_num_rows($resh) > 0){
						$linhas = array();
						$historicos = array();
						for($h =0;$h<pg_num_rows($resh);$h++) {
							$historico = pg_fetch_result($resh,$h,'historico');
							$linha_nome = "";

							array_push($historicos,$historico);
							$historico2 = preg_replace('/.*\s(\d{10,15}).*(\d{2}\/\d{4}).*\s(\d{3,6})\.$/','$1,$2,$3',$historico);
							list($sua_os,$data,$caixa_historico) = explode(",",$historico2);
							$sqls = "SELECT tbl_linha.nome
									   FROM tbl_os
									   JOIN tbl_produto USING(produto)
									   JOIN tbl_linha USING(linha)
									  WHERE posto = $posto
										AND tbl_os.fabrica = $login_fabrica
										AND sua_os = '$sua_os'";
							$ress = pg_query($con, $sqls);

							if (pg_num_rows($ress) > 0) {
								$linha_nome = pg_fetch_result($ress, 0, 'nome');
							} else {

								$historico2 = preg_replace('/.*\s(\d{10,12}\-\d{1,3}).*(\d{2}\/\d{4}).*\s(\d{3,6})\.$/','$1,$2,$3',$historico);
								list($sua_os,$data,$caixa_historico) = explode(",",$historico2);
								$sqlxx = "SELECT tbl_linha.nome
											FROM tbl_os
											JOIN tbl_produto USING(produto)
											JOIN tbl_linha USING(linha)
										   WHERE posto = $posto
											 AND tbl_os.fabrica = $login_fabrica
											 AND sua_os = '$sua_os'";
								$resxx = pg_query($con, $sqlxx);

								if (pg_num_rows($resxx) > 0) {
									$linha_nome = pg_fetch_result($resxx, 0, 'nome');
								}
							}
							array_push($linhas,$linha_nome);
						}
					}
					$linhas = array_count_values($linhas);
					foreach($linhas as $linha_nome => $qtde_os){
							$total_linha = $qtde_os * $valor;
                            echo "<tr>";
							echo "<td nowrap class='tac'>$codigo_posto</td>";
							echo "<td nowrap class='tal'>$posto_nome</td>";
							echo "<td nowrap class='tal'>$linha_nome</td>";
							echo "<td nowrap class='tar'>".number_format($valor,2,",",".")."</td>";
							echo "<td nowrap class='tac'>$qtde_os</td>";
							echo "<td nowrap class='tac'>$qtde_extrato</td>";
							echo "<td nowrap class='tac'>$data_conferencia</td>";
							echo "<td nowrap class='tac'>$nota_fiscal</td>";
							echo "<td nowrap class='tac'></td>";
							echo "<td nowrap class='tac'></td>";
							echo "<td nowrap class='tar'></td>";
							echo "<td nowrap class='tar'>".number_format($total_linha,2,",",".")."</td>";
							echo "<td nowrap class='tar'>".number_format($total_linha,2,",",".")."</td>";
							echo "<td nowrap class='tac'>$caixa</td>";
							echo "<td nowrap class='tal'></td>";/*implode("<br>",$historicos)*/
							echo "<td nowrap class='tal'></td>";
							echo "<td nowrap class='tac'></td>";
							echo "<td nowrap class='tac'>$data_geracao</td>";
							echo "<td nowrap class='tac'>$admin_nome</td>";
							echo "</tr>";
					}
				}
                echo "</tbody>";
            echo "</table>";
        }
    }

    $total = pg_num_rows($res);

    if ($total > 0) {

        $arquivo_nome     = "relatorio-os-conferida-linha-$login_fabrica.$login_admin.xls";
        $path             = "/www/assist/www/admin/xls/";
        $path_tmp         = "/tmp/";

        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        $fp = fopen($arquivo_completo_tmp, "w");

        #HD 259744
		$sqlx = "SELECT distinct extrato into temp tmp_extrato_britania_$login_admin
				   FROM tmp_rel_os_conf_$login_admin;

				 SELECT distinct tbl_os_extra.os,
						tbl_os_extra.extrato,
						tbl_os_extra.linha,
						tbl_os_extra.mao_de_obra
				into temp tmp_os_extrato_$login_admin
				from  tbl_os_extra
				where tbl_os_extra.i_fabrica=$login_fabrica AND tbl_os_extra.extrato in (select extrato from tmp_extrato_britania_$login_admin);

				SELECT  tbl_os.os, extrato
					into  temp tmp_os_sinalizador_$login_admin
					from   tmp_os_extrato_$login_admin
					JOIN tbl_os USING(os)
					where   tbl_os.fabrica=$login_fabrica and tbl_os.sinalizador=1
					AND   tbl_os.finalizada is not null and tbl_os.excluida IS NOT TRUE  ";
				$resx = pg_query($con, $sqlx);

		$sql = "SELECT tmp_rel_os_conf_$login_admin.extrato,
			tmp_rel_os_conf_$login_admin.linha,
			tmp_rel_os_conf_$login_admin.linha_nome,
			tmp_rel_os_conf_$login_admin.unitario
			FROM tmp_rel_os_conf_$login_admin";
		$resx = pg_query($con, $sql);
		for($k = 0 ;$k < pg_num_rows($resx);$k++){
			$extrato = pg_fetch_result($resx,$k,'extrato');
			$linha = pg_fetch_result($resx,$k,'linha');
			$linha_nome = pg_fetch_result($resx,$k,'linha_nome');
			$unitario = pg_fetch_result($resx,$k,'unitario');

			if($k == 0 ) {
				$cond = " CREATE  TABLE tmp_rel_os_conf_2_$login_admin (linha_nome text, conferidas int4, unitario float ) ; ";
			}else{
				$cond = "";
			}
			$sql = "$cond

				SELECT count( tmp_os_sinalizador_$login_admin.os) as conferidas
				FROM  tmp_os_sinalizador_$login_admin
				JOIN  tmp_os_extrato_$login_admin USING(os)
				WHERE tmp_os_extrato_$login_admin.extrato = $extrato
				AND   tmp_os_extrato_$login_admin.linha = $linha
				AND   (tmp_os_extrato_$login_admin.mao_de_obra = $unitario or tmp_os_extrato_$login_admin.mao_de_obra::numeric = {$unitario}) ";
			$resk = pg_query($con,$sql);

			if(pg_num_rows($resk) > 0 ) {
				$conferidas = pg_fetch_result($resk,0,0);
				$sqli = "INSERT INTO tmp_rel_os_conf_2_$login_admin values('$linha_nome',$conferidas,$unitario) ;";
				$resi = pg_query($con,$sqli);
			}
		}
                
               $sqlx =" SELECT linha_nome,
                           SUM(conferidas) AS conferidas,
                           SUM(unitario * conferidas) AS valor
                      FROM tmp_rel_os_conf_2_$login_admin
                     GROUP BY linha_nome
			 ORDER BY linha_nome";

        $resx = pg_query($con, $sqlx);
        $totx = pg_num_rows($resx);

        if ($totx > 0) {

            echo "<br />";
            echo "<table class='table table-striped table-bordered table-hover table-large' style='margin:0 auto;'>";
                echo "<thead>";
                    echo "<tr class='titulo_coluna'>";
                        echo "<td nowrap class='tac'><b>Linha</b></td>";
                        echo "<td nowrap class='tac'><b>Qtde</b></td>";
                        echo "<td nowrap class='tac'><b>Valor a pagar por linha</b></td>";
                    echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                for ($x = 0; $x < $totx; $x++) {

                    $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                    $linha_nomex  = trim(pg_fetch_result($resx, $x, 'linha_nome'));
                    $conferidasx  = trim(pg_fetch_result($resx, $x, 'conferidas'));
                    $valor_pagarx = trim(pg_fetch_result($resx, $x, 'valor'));
                    $valor_pagarx = number_format($valor_pagarx,2,",",".");

                    echo "<tr>";
                        echo "<td nowrap class='tal'>$linha_nomex</td>";
                        echo "<td nowrap class='tar'>$conferidasx</td>";
                        echo "<td nowrap class='tar'>$valor_pagarx</td>";
                    echo "</tr>";

                }

                echo "</tbody>";
            echo "</table>";
        }

        echo "<br />";
        echo "<table  class='table table-striped table-bordered table-hover table-fixed' style='margin:0 auto;'>";
            echo "<thead>";
                echo "<tr class='titulo_coluna'>";
                    echo "<td nowrap class='tac'><b>Cód. Posto</b></a></td>";
                    echo "<td nowrap class='tac'><b>Posto</b></a></td>";
                    echo "<td nowrap class='tac'><b>Linha</b></a></td>";
                    echo "<td nowrap class='tac'><b>M. O. Unit.</b></a></td>";
                    echo "<td nowrap class='tac'><b>OSs Conf.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Total OS</b></a></td>";
                    echo "<td nowrap class='tac'><b>Data Conf.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Nota Fiscal</b></a></td>";
                    echo "<td nowrap class='tac'><b>NF. Digit</b></a></td>";
                    echo "<td nowrap class='tac'><b>Data NF</b></a></td>";
                    echo "<td nowrap class='tac'><b>Valor NF</b></a></td>";
                    echo "<td nowrap class='tac'><b>Valor Total Linha</b></a></td>";
                    echo "<td nowrap class='tac'><b>Valor a Pagar</b></a></td>";
                    echo "<td nowrap class='tac'><b>Caixa</b></a></td>";
                    echo "<td nowrap class='tac'><b>Obs Britânia</b></a></td>";
                    echo "<td nowrap class='tac'><b>Obs Posto</b></a></td>";
                    echo "<td nowrap class='tac'><b>Previsão Pgto.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Data Extr.</b></a></td>";
                    echo "<td nowrap class='tac'><b>Admin</b></a></td>";
                echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $posto_anterior   = "";
            $extrato_anterior = "";

            for ($i = 0 ; $i < $total; $i++) {

                $cor = ($x % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

                $posto                = trim(pg_fetch_result($res, $i, 'posto'));
                $codigo_posto         = trim(pg_fetch_result($res, $i, 'codigo_posto'));
                $posto_nome           = trim(pg_fetch_result($res, $i, 'posto_nome'));
                $linha                = trim(pg_fetch_result($res, $i, 'linha'));
                $linha_nome           = trim(pg_fetch_result($res, $i, 'linha_nome'));
                $unitario             = trim(pg_fetch_result($res, $i, 'unitario'));
                $data_conferencia     = trim(pg_fetch_result($res, $i, 'dat_conferencia'));
                $data_lancamento_nota = trim(pg_fetch_result($res, $i, 'dat_lancamento_nota'));
                $nota_fiscal          = trim(pg_fetch_result($res, $i, 'nota_fiscal'));
                $data_nf              = trim(pg_fetch_result($res, $i, 'dat_nf'));
                $valor_nf             = trim(pg_fetch_result($res, $i, 'valor_nf'));
                $extrato              = trim(pg_fetch_result($res, $i, 'extrato'));
                $valor_pagar          = trim(pg_fetch_result($res, $i, 'valor_pagar'));
                $caixa                = trim(pg_fetch_result($res, $i, 'caixa'));
                $obs_fabricante       = trim(pg_fetch_result($res, $i, 'obs_fabricante'));
                $obs_posto            = trim(pg_fetch_result($res, $i, 'obs_posto'));
                $previsao_pgto        = trim(pg_fetch_result($res, $i, 'previsao_pgto'));
                $admin_nome           = trim(pg_fetch_result($res, $i, 'admin_nome'));
                $data_geracao         = trim(pg_fetch_result($res, $i, 'data_geracao'));

                #HD 259744
                if (strlen($linha) > 0 AND strlen($extrato) > 0 AND strlen($unitario) > 0) {

                    $sqlq = "SELECT count(*) as conferidas
                               FROM tmp_os_extrato_$login_admin os_extra
                               JOIN tmp_os_sinalizador_$login_admin os ON os.os = os_extra.os 
                              WHERE os_extra.linha = $linha
                                AND os_extra.extrato = $extrato
                                AND (os_extra.mao_de_obra = '$unitario' or os_extra.mao_de_obra::numeric = '$unitario') ";

                    $resq = pg_query($con, $sqlq);

                    if (pg_num_rows($resq) > 0) {
                        $conferidas = pg_fetch_result($resq, 0, 'conferidas');
                    }

                }

                $valor_total_linha = $conferidas * $unitario;

                $unitario          = number_format($unitario,2,",",".");
                $valor_nf          = number_format($valor_nf,2,",",".");
                $valor_pagar       = number_format($valor_pagar,2,",",".");
                $valor_total_linha = number_format($valor_total_linha,2,",",".");

                if ($extrato_anterior != $extrato) {

                    $sqlc = "SELECT count(*) as qtde_extrato
                               FROM tmp_os_sinalizador_$login_admin
                          	WHERE extrato = $extrato";

                    $resc = pg_query($con, $sqlc);

                    if (pg_num_rows($resc) > 0) {
                        $qtde_extrato = pg_fetch_result($resc, 0, 'qtde_extrato');
                    }

                }

                echo "<tr>";
                    echo "<td nowrap class='tac'>$codigo_posto</td>";
                    echo "<td nowrap class='tal'>$posto_nome</td>";
                    echo "<td nowrap class='tal'>$linha_nome</td>";
                    echo "<td nowrap class='tar'>$unitario</td>";
                    echo "<td nowrap class='tac'>$conferidas</td>";
                    echo "<td nowrap class='tac'>$qtde_extrato</td>";
                    echo "<td nowrap class='tac'>$data_conferencia</td>";
                    echo "<td nowrap class='tac'>$nota_fiscal</td>";
                    echo "<td nowrap class='tac'>$data_lancamento_nota</td>";
                    echo "<td nowrap class='tac'>$data_nf</td>";
                    echo "<td nowrap class='tar'>$valor_nf</td>";
                    echo "<td nowrap class='tar'>$valor_total_linha</td>";
                    echo "<td nowrap class='tar'>$valor_pagar</td>";
                    echo "<td nowrap class='tac'>$caixa</td>";
                    echo "<td nowrap class='tal'>$obs_fabricante</td>";
                    echo "<td nowrap class='tal'>$obs_posto</td>";
                    echo "<td nowrap class='tac'>$previsao_pgto</td>";
                    echo "<td nowrap class='tac'>$data_geracao</td>";
                    echo "<td nowrap class='tac'>$admin_nome</td>";
                echo "</tr>";

                $posto_anterior   = $posto;
                $extrato_anterior = $extrato;

            }

            echo "</tbody>";
        echo "</table>";

        $conteudo = ob_get_contents();//PEGA O CONTEUDO EM BUFFER
        ob_end_clean();//LIMPA O BUFFER

        fwrite($fp,"<html>");
        fwrite($fp,"<body>");
        fwrite($fp,$conteudo);
        fwrite($fp,"</body>");
        fwrite($fp,"</html>");
        fclose($fp);

        system("cp $arquivo_completo_tmp $path");//COPIA ARQUIVO PARA DIR XLS
        echo "<div id='gerar_excel' class='btn_excel'>
            <span><img src='imagens/excel.png' onclick='window.open(\"xls/$arquivo_nome\")' /></span>
            <span class='txt' onclick='window.open(\"xls/$arquivo_nome\")'>Download do XLS</span>
        </div>";
        //echo "<p id='id_download' style='display:none'><a href='xls/$arquivo_nome' target='_blank'><img src='/assist/imagens/excel.gif'><br><font color='#3300CC'>Fazer download do arquivo em  XLS</font></a></p>";
        echo $conteudo;

        flush();

        echo "<br />";

        flush();

    } else {
        echo '
            <div class="container">
            <div class="alert">
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>';
    }

}
echo "<div class='container'>";
include_once "rodape.php";?>
