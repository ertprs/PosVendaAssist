<?php
$plugins     = array('shadowbox');
$layout_menu = 'os';

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';

if (isset($_GET['mensagem'])) {
    if ($_GET['mensagem'] == 'confirmada') {
        $nf = $_GET['value'];

        fecho("prezada.autorizada", $con, $cook_idioma);
        echo "<br/><br/>";
        fecho ("a.nf.de.devolucao.%.nao.foram.recebida.pela.fabrica", $con, $cook_idioma, $nf);

        echo "<br>";
        fecho ("favor.entrar.em.contato.urgente.com.o.%.para.sua.regularizacao", $con, $cook_idioma, 'Fabricante');
    }elseif ($_GET['mensagem'] == 'anterior') {
        $extrato = $_GET['value'];

        fecho ("a.nota.fiscal.de.devolucao.das.pecas.em.garantia.do.extrato.%.nao.foram.preenchidas.por.favor.acesse.o.link.de.pecas.retornaveis.para.o.preenchimento",$con, $cook_idioma, $extrato);
?>        

        <br/><br/>
        <a onclick='preencherNf()' href="#" title='<?=traduz("clique.aqui.para.preencher.a.nota.fiscal.de.devolucao..apos.a.devolucao.da.nf,.podera.ser.visualizado.a.mao.de.obra", $con, $cook_idioma);?>'>
        <?=traduz("clique.aqui.para.preencher.a.nf", $con, $cook_idioma); ?></a>
        <script type="text/javascript">
            function preencherNf(){
                var extrato = <?=$extrato?>;
                window.parent.location.href = 'extrato_posto_devolucao_lgr_novo_lgr.php?extrato='+extrato;
                window.parent.Shadowbox.close();
            }
        </script>
<?php
    }
    exit;
}

function personaliza_titulo_tabela($titulo){
    if ($titulo == 'geracao') {
        return "<th>".ucfirst(strtolower(str_replace(array('Ç','Ã'), array('ç', 'ã'), traduz('geracao'))))."</th>";
    }elseif ($titulo == 'avulso') {
        return "<th>+".traduz($titulo)."</th>";
    }elseif ($titulo == 'previsao') {
        return "<th>(*)".traduz($titulo)."</th>";
    }else{
        return "<th>".ucfirst(traduz($titulo))."</th>";
    }
}

function carrega_verificacoes(){
    global $con, $login_fabrica, $login_posto;

    pg_prepare($con, 'verif_anterior_liberado',
        "SELECT
            extrato,
            admin_lgr
        FROM tbl_extrato
        WHERE fabrica   = {$login_fabrica}
            AND posto   = {$login_posto}
            AND extrato < $1
            AND liberado IS NOT NULL
            AND data_geracao > '2009-10-01'
        ORDER BY data_geracao DESC LIMIT 1");

    pg_prepare($con, 'verif_devolucao',
        "SELECT
            faturamento
        FROM tbl_faturamento
            JOIN tbl_faturamento_item USING(faturamento)
            JOIN tbl_peca             USING(peca)
            LEFT JOIN tbl_os_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND (tbl_faturamento_item.pedido_item = tbl_os_item.pedido_item OR tbl_faturamento_item.os_item = tbl_os_item.os_item)
        WHERE tbl_faturamento.fabrica = {$login_fabrica}
            AND tbl_faturamento.posto = {$login_posto}
            AND tbl_faturamento_item.extrato_devolucao = $1
            AND (tbl_peca.produto_acabado IS TRUE OR tbl_os_item.peca_obrigatoria)");

    pg_prepare($con, 'verif_ja_preencheu',
        "SELECT DISTINCT faturamento,
            nota_fiscal,
            emissao - CURRENT_DATE AS dias_emitido,
            conferencia,
            movimento,
            devolucao_concluida
        FROM tbl_faturamento_item
            JOIN tbl_faturamento USING(faturamento)
        WHERE fabrica = {$login_fabrica}
            AND distribuidor = {$login_posto}
            AND tbl_faturamento_item.extrato_devolucao = $1
            AND posto IS NOT NULL");
}

function verifica_bloqueio($extrato, $atual = null, $num_rows = 0){
    global $con, $cook_idioma, $array_bloqueio;

    $resConf = pg_execute($con, 'verif_anterior_liberado', array($extrato));
    if (pg_num_rows($resConf) > 0 || $atual){
        $lgr_extrato = pg_fetch_result($resConf, 0, 'extrato');
        $admin_lgr   = pg_fetch_result($resConf, 0, 'admin_lgr');

        if ($atual) {
            $lgr_extrato = $extrato;
        }

        $resConf = pg_execute($con, 'verif_devolucao', array($lgr_extrato));
        if (pg_num_rows($resConf) > 0){
            $resConf = pg_execute($con, 'verif_ja_preencheu', array($lgr_extrato));
            if (pg_num_rows($resConf) > 0){
                if (strlen($admin_lgr) == 0) {
                    $devolucao_concluida = pg_fetch_result($resConf, 0, 'devolucao_concluida');

                    if ($devolucao_concluida == 'f' || empty($devolucao_concluida)) {
                        $array_bloqueio[$extrato] = true;
                        $array_bloqueio['value']  = pg_fetch_result($resConf, 0, 'nota_fiscal');
                        $array_bloqueio['msg'] = "confirmada";
                    }
                }
            }else{
                $array_bloqueio[$extrato] = true;
                $array_bloqueio['value']  = $lgr_extrato;
                $array_bloqueio['msg']    = "anterior";
            }
        }
    }
}

$msg_erro = '';
if (isset($_POST['btn_acao']) && $_POST['btn_acao'] == 'listar' && isset($_POST['buscarOS']) && empty($_POST['buscarOS'])) {
    $erro_campo[] = 'buscarOS';
    $msg_erro     = traduz('preencha.todos.os.campos.obrigatorios');
}

if (empty($msg_erro)) {
    if(isset($_POST['buscarOS']) && !empty($_POST['buscarOS'])){
        $join  .= " JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato JOIN tbl_os ON tbl_os.os = tbl_os_extra.os ";
        $where .= " AND tbl_os_extra.os = {$buscarOS} ";
    }
    if ($tipo_posto == "P"){
        $where .= "AND tbl_extrato.posto = {$login_posto} ";
    }else{
        $where .= "AND (tbl_posto_fabrica.distribuidor = {$login_posto} OR tbl_posto_fabrica.posto = {$login_posto}) ";
    }
    $where .= " AND tbl_extrato.posto = {$login_posto} AND tbl_extrato.aprovado IS NOT NULL";

    /* LISTAGEM DE EXTRATOS */
    $sql = "
        SELECT
            tbl_extrato.extrato,
            tbl_posto_fabrica.codigo_posto,
            tbl_extrato.valor_adicional,
            tbl_posto.nome,
            TO_CHAR(tbl_extrato.data_geracao, 'YYYY-MM-DD')            AS data_extrato,
            TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY')            AS data_geracao,
            TO_CHAR(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY') AS data_pagamento,
            tbl_extrato.mao_de_obra,
            tbl_extrato.mao_de_obra_postos,
            TO_CHAR(tbl_extrato.aprovado, 'DD/MM/YYYY')                AS aprovado,
            tbl_extrato.pecas,
            tbl_extrato.avulso,
            tbl_extrato.admin_lgr,
            tbl_extrato.total
        FROM tbl_extrato
            JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
            LEFT JOIN tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
            LEFT JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
	    $join
        WHERE tbl_extrato.fabrica = {$login_fabrica} {$where}
        ORDER BY data_extrato DESC, extrato DESC";
    $res_lista_extrato = pg_query($con, $sql);
}

$title       = traduz("consulta.de.extratos");
$error_alert = true;
include_once 'cabecalho_new.php';

?>
<br />
<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz('campos.obrigatorios')?></b>
</div>
<form name='frm_posto' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' enctype="multipart/form-data" >
    <div class='titulo_tabela '><?=traduz("pesquisar")?></div>
    <br/>
    <div class="row-fluid">
        <div class="span3"></div>
        <div class='span8'>
            <div class='control-group <?=(in_array('buscarOS', $erro_campo)) ? 'error' : ''?>'>
                <label class='control-label' for='buscarOS'><?=traduz("numero.da.os");?></label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" name="buscarOS" class='span12' value="<?=$buscarOS?>" >
                    </div>
                    <div class="span8">
                        <button class="btn" name="btn_pesquisar" type="button"><?=traduz("pesquisar")?></button>
                        <button class="btn" name="btn_listar_todos" type="button"><?=traduz("listar.todos.extratos")?></button>
                        <input type="hidden" name="btn_acao" value="">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br/>
</form>
<?php
if (pg_num_rows($res_lista_extrato) > 0) {
    $array_tabela = array(
        'extrato',
        'aprovado',
        'geracao',
        'mao.de.obra',
        'pecas',
        'total',
        'avulso',
        'previsao',
        'acoes'
    );

    pg_prepare($con, 'previsao_financeiro', "SELECT TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') FROM tbl_extrato_financeiro WHERE extrato = $1");
    pg_prepare($con, 'existe_lancamento', "SELECT count(*) as existe FROM tbl_extrato_lancamento WHERE extrato = $1 AND posto = $login_posto AND fabrica = $login_fabrica");
?>
    <table id="resultado_extratos" class='table table-striped table-bordered table-hover table-large'>
        <thead>
            <tr class='titulo_coluna' >
                <?php
                    $titulo_tabela = '';
                    foreach ($array_tabela as $titulo) {
                        $titulo_tabela .= personaliza_titulo_tabela($titulo);
                    }
                    echo $titulo_tabela;
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $num_rows      = pg_num_rows($res_lista_extrato);
            $table_extrato = '';

            /* CARREGA TODOS OS BLOQUEIOS DE EXTRATO */
            carrega_verificacoes();

            if ($num_rows > 1) {
                $array_bloqueio = array();
                for ($i = 0; $i < $num_rows; $i++) {
                    verifica_bloqueio(pg_fetch_result($res_lista_extrato, $i,'extrato'));
                }
            }
            /* FIM */

            for ($i = 0; $i < $num_rows; $i++) {
                $xmao_de_obra                = 0;
                $extrato                     = trim(pg_fetch_result($res_lista_extrato,$i,'extrato'));
                $data_geracao                = trim(pg_fetch_result($res_lista_extrato,$i,'data_geracao'));
                $mao_de_obra                 = trim(pg_fetch_result($res_lista_extrato,$i,'mao_de_obra'));
                $mao_de_obra_postos          = trim(pg_fetch_result($res_lista_extrato,$i,'mao_de_obra_postos'));
                $pecas                       = trim(pg_fetch_result($res_lista_extrato,$i,'pecas'));
                $avulso                      = trim(pg_fetch_result($res_lista_extrato,$i,'avulso'));
                $admin_lgr                   = trim(pg_fetch_result($res_lista_extrato,$i,'admin_lgr'));
                $data_pagamento              = trim(pg_fetch_result($res_lista_extrato,$i,'data_pagamento'));
                $valor_adicional             = trim(pg_fetch_result($res_lista_extrato,$i,'valor_adicional'));
                $data_aprovado               = trim(pg_fetch_result($res_lista_extrato,$i,'aprovado'));

                $rexX     = pg_execute($con, 'previsao_financeiro', array($extrato));
                $previsao = trim(pg_fetch_result($resX,0,0));

                /* SOMA VALORES */
                if ($tipo_posto == "P") {
                    $xmao_de_obra += $mao_de_obra_postos;
                    $xvrmao_obra   = $mao_de_obra_postos;
                }else{
                    $xmao_de_obra += $mao_de_obra;
                    $xvrmao_obra   = $mao_de_obra;
                }

                if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
                if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;

                $total = $xmao_de_obra + $pecas ;

                if (strlen($data_pagamento) == 0){
                    $total_pendencia += $total;
                }

                if (strlen($extrato) > 0) {
                    $res_avulso = pg_execute($con, 'existe_lancamento', array($extrato));

                    if (pg_num_rows($res_avulso) > 0 && pg_fetch_result($res_avulso, 0, 'existe') > 0) {
                        $cor = "class='warning'";
                    }
                }
                $table_extrato .= "<tr {$cor}>";

                $table_extrato .="
                        <td class='tac'>{$extrato}</td>
                        <td class='tac'>{$data_aprovado}</td>
                        <td class='tac'>{$data_geracao}</td>";

                if (isset($array_bloqueio[$extrato])) {
                    $table_extrato .= "<td colspan='6' class='tac'><button class='bloqueado btn btn-small btn-warning' data-msg='".$array_bloqueio['msg']."' data-val='".$array_bloqueio['value']."'>".traduz("extrato.bloqueado")."</button></td>";
                }else{
                    $bloqueio = verifica_bloqueio($extrato, true, $num_rows);

                    if (isset($array_bloqueio[$extrato])) {
                        if ($array_bloqueio['msg'] == 'confirmada') {
                            $table_extrato .= "<td colspan='6' class='tac'><button class='bloqueado btn btn-small btn-warning' data-msg='confirmada' data-val='".$array_bloqueio['value']."'>".traduz("extrato.bloqueado")."</button></td>";
                        }else{
                            $table_extrato .= "<td colspan='6' class='tac'><a href='extrato_posto_devolucao_lgr_novo_lgr.php?extrato=$extrato' title='Clique aqui para preencher a nota fiscal de devolução. Após a devolução da NF, poderão ser visualizado a Mão de Obra'>Preencha a NF<br>Clique Aqui</a></td>\n";
                        }
                    }else{
                        $table_extrato .= "
                            <td style='text-align: right'>".number_format($xvrmao_obra, 2, ".", "")."</td>
                            <td style='text-align: right'>".number_format($pecas, 2, ".", "")."</td>
                            <td style='text-align: right'>".number_format(($total + $valor_adicional), 2, ".", "")."</td>
                            <td style='text-align: right'>".number_format($avulso, 2, ".", "")."</td>
                            <td>{$previsao}</td>
                            <td class='tac' width='25%'>
                                <button class='btn btn-small' name='btn_detalhado' data-extrato='$extrato'>".traduz('detalhado')."</button>
                                <button class='btn btn-small' name='btn_retornaveis' data-extrato='$extrato'>".traduz('retornaveis')."</button>
                            </td>";
                    }
                }

                $table_extrato .= "</tr>";
            }

            echo $table_extrato;
            ?>
        </tbody>
    </table>
<?php } elseif (empty($msg_erro)) { ?>
    <div class="alert alert-warning"><h4><?=traduz('nenhum.extrato.foi.encontrado')?></h4></div>
<?php } ?>
<script type="text/javascript">
    $(function(){
        Shadowbox.init();

        $('button[name=btn_detalhado]').on('click', function(){
            var posto = <?=$login_posto?>;
            var extrato = $(this).data('extrato');

            window.open('os_extrato_detalhe.php?extrato='+extrato+'&posto='+posto);
        });

        $('button[name=btn_retornaveis]').on('click', function(){
            var posto = <?=$login_posto?>;
            var extrato = $(this).data('extrato');

            window.open('extrato_posto_lgr_itens_novo.php?extrato='+extrato);
        });

        $('button[name=btn_pesquisar]').on('click', function(){
            $('input[name=btn_acao]').val('listar');
            $(this).parents('form').submit();
        });

        $('button[name=btn_listar_todos]').on('click', function(){
            $('input[name=buscarOS]').val('');
            $('input[name=btn_acao]').val('listar_tudo');
            $(this).parents('form').submit();
        });

        $(document).on('click', '.bloqueado', function(){
            var msg = $(this).data('msg');
            var value = $(this).data('val');

            Shadowbox.open({
                content: window.location.href+"?mensagem="+msg+"&value="+value,
                player: "iframe",
                height: 150,
                width: 800
            });
        });
    });
</script>
<br />
<? include "rodape.php"; ?>
