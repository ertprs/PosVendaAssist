<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios='auditoria';
include 'autentica_admin.php';
include 'funcoes.php';
$programa_insert = $_SERVER['PHP_SELF'];


if ($_POST['btn_acao'] == 'submit') {
    $data_inicial    = $_POST['data_inicial'];
    $data_final      = $_POST['data_final'];
    $codigo_posto    = $_POST['codigo_posto'];
    $descricao_posto = $_POST['descricao_posto'];

    if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
        $sql = "SELECT tbl_posto_fabrica.posto
                  FROM tbl_posto
                  JOIN tbl_posto_fabrica USING(posto)
                 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                   AND (
                        (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('$codigo_posto'))
                        OR
                        (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('$descricao_posto'), 'LATIN-9'))
        )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro['msg'][]    = 'Posto nÃ£o encontrado';
            $msg_erro['campos'][] = 'posto';
        } else {
            $posto = pg_fetch_result($res, 0, 'posto');
        }
    }

    if (strlen($data_inicial) or strlen($data_final)) {
        list($di, $mi, $yi) = explode('/', $data_inicial);
        list($df, $mf, $yf) = explode('/', $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro['msg'][]    = 'Data InvÃ¡lida';
            $msg_erro['campos'][] = 'data';
        } else {
            $aux_data_inicial = formata_data($data_inicial);
            $aux_data_final   = formata_data($data_final);

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro['msg'][]    = 'Data Final nÃ£o pode ser menor que a Data Inicial';
                $msg_erro['campos'][] = 'data';
            }
        }
    }

    if(!empty($data_inicial) AND !empty($data_final)){
        $cond_data = " AND (tbl_hd_chamado_item.data between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' and tbl_os.finalizada between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59') ";
        $join_hd_chamado_item = " JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_item.status_item = 'Resolvido'";
    }

    if (!count($msg_erro["msg"])) {

        if (!empty($posto)) {
            $cond_posto = " AND tbl_os.posto = $posto ";
        }else{
            $cond_posto = " AND tbl_os.posto <> 6359 ";
        }

        $sql = "SELECT auditoria.os
            INTO TEMP tmp_auditoria_os_finalizadas
            FROM (
                SELECT ultimo_status.os, (
                    SELECT status_os
                      FROM tbl_os_status
                     WHERE tbl_os_status.os             = ultimo_status.os
                       AND tbl_os_status.fabrica_status = $login_fabrica
                       AND status_os IN (211,212)
                     ORDER BY os_status DESC LIMIT 1) AS ultima_os_status
                      FROM (
                        SELECT DISTINCT os
                          FROM tbl_os_status
                         WHERE tbl_os_status.fabrica_status = $login_fabrica
                           AND status_os IN (211,212)
                        ) ultimo_status) auditoria
                 WHERE auditoria.ultima_os_status IN (211);";
        // echo nl2br($sql);
        $res = pg_query($con, $sql);

        $sql = "SELECT  DISTINCT
                tbl_os.os,
                tbl_os.posto,
                tbl_os.consumidor_cidade,
                tbl_os.consumidor_estado,
                tbl_os.qtde_km_calculada,
                tbl_os.pedagio,
                tbl_os.defeito_constatado,
                tbl_os.pecas,
                tbl_os.mao_de_obra,
                tbl_os.obs,
                tbl_os.hd_chamado,
                JSON_FIELD('servicoDiferenciado',tbl_os_campo_extra.valores_adicionais) AS bonificacao,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome,
                tbl_posto.cidade,
                tbl_posto.estado,
                tbl_hd_chamado_extra.hd_chamado AS hd_chamado_extra
        FROM    tmp_auditoria_os_finalizadas
        JOIN    tbl_os                  ON tbl_os.os = tmp_auditoria_os_finalizadas.os
        JOIN    tbl_os_extra            ON  tbl_os_extra.os                 = tbl_os.os and tbl_os_extra.i_fabrica=$login_fabrica
   LEFT JOIN    tbl_os_campo_extra      ON  tbl_os_campo_extra.os           = tbl_os.os and tbl_os_campo_extra.fabrica=$login_fabrica
        JOIN    tbl_hd_chamado_extra    ON  tbl_hd_chamado_extra.os         = tbl_os.os
        JOIN    tbl_posto               ON  tbl_posto.posto                 = tbl_os.posto
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto         = tbl_os.posto
                                        AND tbl_posto_fabrica.fabrica       = $login_fabrica
        JOIN    tbl_hd_chamado          ON  tbl_hd_chamado.hd_chamado       = tbl_hd_chamado_extra.hd_chamado
                                        AND tbl_hd_chamado.status           = 'Resolvido'
        $join_hd_chamado_item
        WHERE   tbl_os.fabrica = $login_fabrica
        AND     tbl_os.excluida IS NOT TRUE
        AND     tbl_os.finalizada notnull
                $cond_data
                $cond_posto
            ";
        $resSubmit = pg_query($con, $sql);
    }
}

if ($_POST['btn_acao'] == 'liberar_os') {
    $comunicado = '';

    $adiciona_avulso = $_POST['adiciona_avulso'];
    $avulso          = $_POST['avulso'];
    $id_os           = $_POST['id_os'];
    $id_posto        = $_POST['id_posto'];
    $reprova_km      = $_POST['reprova_km'];
    $reprova_mo      = $_POST['reprova_mo'];
    $reprova_pedagio = $_POST['reprova_pedagio'];
    $text_avulso     = $_POST['text_avulso'];
    $text_km         = $_POST['text_km'];
    $text_mao_obra   = $_POST['text_mao_obra'];
    $text_pedagio    = $_POST['text_pedagio'];
    $km              = str_replace('.', '', $_POST['km']);
    $km_ant          = str_replace('.', '', $_POST['km_ant']);
    $mao_de_obra     = str_replace('.', '', $_POST['mao_de_obra']);
    $mao_de_obra_ant = str_replace('.', '', $_POST['mao_de_obra_ant']);
    $pedagio         = str_replace('.', '', $_POST['pedagio']);
    $pedagio_ant     = str_replace('.', '', $_POST['pedagio_ant']);
    $valor_avulso    = str_replace('.', '', $_POST['valor_avulso']);
    $bonificacao     = str_replace('.', '',$_POST['bonificacao']);
    $valor_total     = $_POST['valor_total'];

    $km              = str_replace(',', '.', $km);
    $km_ant          = str_replace(',', '.', $km_ant);
    $mao_de_obra     = str_replace(',', '.', $mao_de_obra);
    $mao_de_obra_ant = str_replace(',', '.', $mao_de_obra_ant);
    $pedagio         = str_replace(',', '.', $pedagio);
    $pedagio_ant     = str_replace(',', '.', $pedagio_ant);
    $valor_avulso    = str_replace(',', '.', $valor_avulso);
    $valor_total     = str_replace(',', '.', $valor_total);
    $bonificacao     = str_replace(',', '.', $bonificacao);

    $dados_avulso = explode("-", $avulso);

    $avulso = $dados_avulso[0];

    $debitoCredito = $dados_avulso[1];

    if($reprova_mo === 'true'){
        if(strlen($mao_de_obra) == ''){
            $msg_erro['msg'][] = 'Digite o valor da Mão de Obra Correta';
        }
        if(strlen($text_mao_obra) > 0){
            $comentarios['valor_mao_de_obra_ant'] = $mao_de_obra_ant;
            $comentarios['valor_mao_de_obra'] = $mao_de_obra_;
            $comentarios['mao_de_obra'] = $text_mao_obra;
            $comunicado .= 'Valor de Mão de Obra alterado de R$'.$_POST['mao_de_obra_ant'].' para R$'.$_POST['mao_de_obra'].'<br />';
            $comunicado .= 'MÃ£o de Obra: '.$text_mao_obra.'<br>';
        }else{
            $msg_erro['msg'][] = 'Digite motivo da alteração da Mão de Obra';
        }
    }

    if($reprova_km === 'true'){
        if(strlen($km) == ''){
            $msg_erro['msg'][] = 'Digite o valor do KM Correto';
        }

        if(strlen($text_km) > 0){
            $comentarios['km'] = $text_km;
            $comentarios['valor_km'] = $km;
            $comentarios['valor_km_ant'] = $km_ant;
            $comunicado .= 'Valor de KM alterado de R$'.$_POST['km_ant'].' para R$'.$_POST['km'].'<br />';
            $comunicado .= 'KM: '.$text_km.'<br>';
        }else{
            $msg_erro['msg'][] = 'Digite motivo da alteração do KM';
        }
    }

    if($reprova_pedagio === 'true'){
        if(strlen($pedagio) == ''){
            $msg_erro['msg'][] = 'Digite o valor do Pedágio Correto';
        }

        if(strlen($text_pedagio) > 0){
            $comentarios['valor_pedagio_ant'] = $pedagio_ant;
            $comentarios['valor_pedagio'] = $pedagio;
            $comentarios['pedagio'] = $text_pedagio;
            $comunicado .= 'Valor de Pedágio alterado de R$'.$_POST['pedagio_ant'].' para R$'.$_POST['pedagio'].'<br />';
            $comunicado .= 'Pedágio: '.$text_pedagio.'<br>';
        }else{
            $msg_erro['msg'][] = 'Digite motivo da alteração do Pedágio';
        }
    }

    if($adiciona_avulso === 'true'){
        if(strlen($avulso) == 0){
            $msg_erro['msg'][] = 'Escolhe a opção entre Crédito e Débito';
        }

        if(strlen($valor_avulso) == 0){
            $msg_erro['msg'][] = 'Digite o valor do Avulso';
        }

        if(strlen($text_avulso) > 0){
            $comentarios['avulso'] = $text_avulso;
            $comunicado .= 'Avulso: '.$text_avulso;
        } else {
            $msg_erro['msg'][] = 'Digite motivo do Avulso';
        }
    }

    $comentarios = json_encode($comentarios);

    if (!count($msg_erro["msg"])) {

        ###ALTERA REGISTRO
        $res = pg_query($con, 'BEGIN TRANSACTION');
        $msg_error[] = pg_last_error($con);

        $sql = "UPDATE tbl_os
                SET mao_de_obra       = ".(float)$mao_de_obra.",
                    qtde_km_calculada = ".(float)$km.",
                    pedagio           = ".(float)$pedagio."
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_os.os = $id_os";
        $res = pg_query($con,$sql);

        $msg_error[] = pg_last_error($con);

        if(!empty($avulso)) {
	    $sql_busca_lancamento = "SELECT descricao FROM tbl_lancamento WHERE lancamento = $avulso";
	    $res_busca_lancamento = pg_query ($con, $sql_busca_lancamento);
	    $descricao_lancamento = pg_fetch_result($res_busca_lancamento, $i, 'descricao');
	}
        // HD 2416981
        if (!empty($comentarios) and $comentarios != 'null' and $comentarios != '[null]') {
            $comunicado .= "<br /><strong>Valor Total OS: R$".$valor_total."</strong>";
            if($avulso > 0){
                $comunicado .= "<br /><strong>Adicionado a OS o valor avulso R$ ".$valor_avulso." referente a $descricao_lancamento </strong>";
            }

            $comentarios = "'" . utf8_decode($comentarios) . "'";
            $sql = "UPDATE tbl_os_extra
                    SET obs_adicionais = $comentarios
                    WHERE tbl_os_extra.os = $id_os";
            $res = pg_query($con,$sql);

            $msg_error[] = pg_last_error($con);
            $sql_i = "INSERT INTO tbl_os_interacao (
                    programa,
                    os,
                    comentario,
                    exigir_resposta,
                    interno,
                    admin
                )VALUES(
                    '$programa_insert',
                    $id_os,
                    '$comunicado',
                    'f',
                    'f',
                    $login_admin
                )";
            $res_i = pg_query($con,$sql_i);
            $msg_error[] = pg_last_error($con);

            $titulo_comunicado = "Auditoria OS finalizadas: OS-$id_os.";
            $sql_c = "INSERT INTO tbl_comunicado (
                descricao              ,
                mensagem               ,
                tipo                   ,
                fabrica                ,
                obrigatorio_os_produto ,
                obrigatorio_site       ,
                posto                  ,
                ativo                  ,
                digita_os              ,
                reembolso_peca_estoque
            ) VALUES (
                '$titulo_comunicado'      ,
                '$comunicado'             ,
                'Comunicado'              ,
                $login_fabrica            ,
                'f'                       ,
                't'                       ,
                $id_posto                 ,
                't'                       ,
                'f'                       ,
                'f')";
        }

        if (!empty($bonificacao) && $bonificacao != '0.00') {

            $comunicado = "<br /><strong>Adicionado a OS o valor avulso R$ ".number_format($bonificacao,2,',','.')." referente a bonificação por serviço diferenciado</strong>";

            $sql_c = "INSERT INTO tbl_comunicado (
                descricao              ,
                mensagem               ,
                tipo                   ,
                fabrica                ,
                obrigatorio_os_produto ,
                obrigatorio_site       ,
                posto                  ,
                ativo                  ,
                digita_os              ,
                reembolso_peca_estoque
            ) VALUES (
                'Bonificação por serviço diferenciado'      ,
                '$comunicado'             ,
                'Comunicado'              ,
                $login_fabrica            ,
                'f'                       ,
                't'                       ,
                $id_posto                 ,
                't'                       ,
                'f'                       ,
                'f');";
        }

        $res_c = pg_query($con,$sql_c);
	$msg_error[] = pg_last_error($con);

	$sql = "";

	$base_insert = "INSERT INTO tbl_extrato_lancamento (
                    posto,
                    fabrica,
                    lancamento,
                    descricao,
                    debito_credito,
                    valor,
                    admin,
                    os
		";

        if (!empty($valor_avulso)) {

            $lancamento = $avulso;

           

            $sql = $base_insert . "
                               ) VALUES (
                    $id_posto,
                    $login_fabrica,
                    $lancamento,
                    '".utf8_encode($text_avulso)."',
                    '$debitoCredito',
                    $valor_avulso,
                    $login_admin,
                    $id_os
		)";

	}

            if (!empty($bonificacao) && $bonificacao != '0.00') {
                $sqlLancamento = "
                    SELECT  lancamento,
                            debito_credito,
                            descricao
                    FROM    tbl_lancamento
                    WHERE   fabrica = $login_fabrica
                    AND     descricao ILIKE '%diferenciado'";
                $resLancamento = pg_query($con,$sqlLancamento);

                $lancamento = pg_fetch_result($resLancamento,0,lancamento);
                $debitoCredito = pg_fetch_result($resLancamento,0,debito_credito);
		$text_avulso = pg_fetch_result($resLancamento,0,descricao);

		if(strlen($sql) == 0){
			$sql = $base_insert . ") VALUES(";
		}else{
			$sql .= ",(";
		}

                $sql .= "
                
                    $id_posto,
                    $login_fabrica,
                    $lancamento,
                    '".utf8_encode($text_avulso)."',
                    '$debitoCredito',
                    $bonificacao,
                    $login_admin,
                    $id_os
                )";       

                if (empty($valor_avulso)) {
                    $valor_avulso = 0;
                }

                $sql_atualiza = "UPDATE tbl_os SET valores_adicionais = CASE WHEN valores_adicionais IS NULL THEN 0 ELSE valores_adicionais END + '{$valor_avulso}'
                                where os = $id_os ";
                $res_atualiza = pg_query($con, $sql_atualiza);
            }


            $res = pg_query($con,$sql);
            $msg_error[] = pg_last_error($con);
        
        $sql_insert = "INSERT INTO tbl_os_status(
                        os,
                        status_os,
                        observacao,
                        admin,
                        fabrica_status
                    )VALUES(
                        $id_os,
                        212,
                        'Os Liberada da Intervenção de OS Finalizada',
                        '$login_admin',
                        $login_fabrica
                    )";

        $resInsert = pg_query($con,$sql_insert);
        $msg_error[] = pg_last_error($con);
        $msg_error = implode('', $msg_error);

        if (strlen($msg_error)) {
            $res = pg_query($con, 'ROLLBACK TRANSACTION');
            echo $msg_error;
            die;
        } else {
            $sql = "COMMIT TRANSACTION";
            $res = pg_query($con, $sql);
            echo "ok";
            die;
        }

    } else {
        if (count($msg_erro['msg']) > 0) {
            echo implode('<br />', $msg_erro['msg']);
            die;
        }
    }
}

$layout_menu = 'auditoria';
$title = 'Auditoria de OS finalizadas';
include 'cabecalho_new.php';


$plugins = array(
  'autocomplete',
  'datepicker',
  'shadowbox',
  'mask',
  'dataTable',
  'price_format'
);

include('plugin_loader.php');


?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("input[name^=mostra_avulso_]").change(function(){
            if (this.checked) {
                $("div[id^=campo_avulso_]").css("display","block");
            } else {
                $("input[name^=valor_avulso_]").attr("checked", false);
                $("input[name^=valor_avulso_correto_]").val('');
                $("textarea[name^=text_avulso_]").val('');
                $("div[id^=campo_avulso_]").css("display","none");
            }
        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function expande(ordem) {
        var elemento = document.getElementById('completo_' + ordem);
        var display = elemento.style.display;

        if (display == "none") {
        elemento.style.display = "";
        $('#icone_expande_' + ordem ).removeClass('icon-plus').addClass('icon-minus');
        } else {
        elemento.style.display = "none";
        $('#icone_expande_' + ordem ).removeClass('icon-minus').addClass('icon-plus');
        }

    }

    function aprova_mao_obra(ordem) {
        $("#mao_de_obra_correta_"+ordem).prop('readonly', true);
        //$("#mao_de_obra_correta_"+ordem).val('');
        $("#text_mao_obra_"+ordem).val("");
        $("#comentario_mao_obra_"+ordem).css("display","none");
    }

    function reprova_mao_obra(ordem) {
        $("#mao_de_obra_correta_"+ordem).removeAttr('readonly');
        $("#comentario_mao_obra_"+ordem).css("display","block");
    }

    function aprova_km(ordem) {
        $("#valor_km_correto_"+ordem).prop('readonly', true);
        //$("#valor_km_correto_"+ordem).val('');
        $("#text_km_"+ordem).val("");
        $("#comentario_km_"+ordem).css("display","none");
    }

    function reprova_km(ordem) {
        $("#valor_km_correto_"+ordem).removeAttr('readonly');
        $("#comentario_km_"+ordem).css("display","block");
    }

    function aprova_pedagio(ordem) {
        $("#valor_pedagio_correto_"+ordem).prop('readonly', true);
        //$("#valor_pedagio_correto_"+ordem).val('');
        $("#text_pedagio_"+ordem).val("");
        $("#comentario_pedagio_"+ordem).css("display","none");
    }

    function reprova_pedagio(ordem) {
        $("#valor_pedagio_correto_"+ordem).removeAttr('readonly');
        $("#comentario_pedagio_"+ordem).css("display","block");
    }

    $(document).on("click","button[id^=liberar_os_]",function(){

        var linha         = $(this).parents("tr");
console.log(linha);
        var id_os         = $(linha).find("input[name^=id_os_]").val();
        var id_posto      = $(linha).find("input[name^=id_posto_]").val();

        var mao_de_obra_ant = $(linha).find("input[name^=mao_de_obra_]").val();
        var mao_de_obra   = $(linha).find("input[name^=mao_de_obra_correta_]").val();
        var text_mao_obra = $(linha).find("textarea[name^=text_mao_obra_]").val();

        var km_ant        = $(linha).find("input[name^=valor_km_]").val();
        var km            = $(linha).find("input[name^=valor_km_correto_]").val();
        var text_km       = $(linha).find("textarea[name^=text_km_]").val();

        var pedagio_ant   = $(linha).find("input[name^=valor_pedagio_]").val();
        var pedagio       = $(linha).find("input[name^=valor_pedagio_correto_]").val();
        var text_pedagio  = $(linha).find("textarea[name^=text_pedagio_]").val();

        var avulso        = $(linha).find("select[name^=tipo_avulso_]").val();
        var valor_avulso  = $(linha).find("input[name^=valor_avulso_correto_]").val();
        var text_avulso   = $(linha).find("textarea[name^=text_avulso_]").val();

        var bonificacao   = $(linha).find("input[name^=bonificacao_]").val();

        var valor_total   = $(linha).find("input[name^=valor_total_os_]").val();

        var alert_erro = '';

        if($("input[id^=aprovacao_mao_obra_]").is(':checked')){
            var reprova_mo = true;

            if(mao_de_obra == ''){
                alert_erro = 'Digite o valor correto da Mão de Obra';
                alert(alert_erro);
            }
            if(text_mao_obra == ''){
                alert_erro = 'Digite o motivo da alteração da Mão de Obra';
                alert(alert_erro);
            }
        }

        if($("input[id^=reprova_km_]").is(':checked')){
            var reprova_km = true;

            if(km == ''){
            alert_erro = "Digite o valor correto do KM";
            alert(alert_erro);
            }
            if(text_km == ''){
                alert_erro = 'Digite o motivo da alteração do KM';
                alert(alert_erro);
            }
        }

        if($("input[id^=reprova_pedagio_]").is(':checked')){
            var reprova_pedagio = true;

            if(pedagio == ''){
                alert_erro = "Digite o valor correto do Pedágio";
                alert(alert_erro);
            }
            if(text_pedagio == ''){
                alert_erro = "Digite o motivo da alteração do Pedágio";
                alert(alert_erro);
            }
        }

        if($(linha).find("input[name^=mostra_avulso_]").is(':checked')){
            var adiciona_avulso = true;
            if($(linha).find("select[name^=tipo_avulso_]").val().length > 0 ) {

                if(valor_avulso == ''){
                alert_erro = "Digite o valor Avulso.";
                alert(alert_erro);
                }

                if(text_avulso == ''){
                alert_erro = "Digite o motivo do valor Avulso";
                alert(alert_erro);
                }

            } else {
                alert_erro = "Selecione uma opção Crédito ou Débito";
                alert(alert_erro);
            }
        }

        if(alert_erro == ''){
            if (ajaxAction()) {

                $.ajax({
                    async: false,
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    dataType: "JSON",
                    data: {
                        btn_acao: "liberar_os",
                        mao_de_obra_ant: mao_de_obra_ant,
                        mao_de_obra: mao_de_obra,
                        text_mao_obra: text_mao_obra,
                        km_ant: km_ant,
                        km: km,
                        text_km: text_km,
                        pedagio_ant: pedagio_ant,
                        pedagio: pedagio,
                        text_pedagio: text_pedagio,
                        avulso: avulso,
                        valor_avulso: valor_avulso,
                        text_avulso: text_avulso,
                        reprova_mo: reprova_mo,
                        reprova_km: reprova_km,
                        reprova_pedagio: reprova_pedagio,
                        adiciona_avulso: adiciona_avulso,
                        id_os: id_os,
                        id_posto: id_posto,
                        bonificacao: bonificacao,
                        valor_total: valor_total
                    },

                    complete: function (data) {
                        var retorno = data.responseText;

                        if(retorno == "ok") {
                            alert("Os liberada da Auditoria");
                            $(linha).prev().remove();
                            $(linha).remove();
                        }else{
                            $(linha).find("[id^=erro_]").css("display","block");
                            $(linha).find("[id^=erro_]").text(retorno);
                        }

                    }
                });
            }
        }

    });

    $(function(){

        $("input.calcula_valor").blur(function() {
        var linha         = $(this).parents("tr");

        var inputTotal = $(linha).find("input[name^=valor_total_os_]");
        var inputsValores = $(linha).find("input[name^=mao_de_obra_correta_], input[name^=valor_km_correto_], input[name^=valor_pedagio_correto_], input[name^=valor_avulso_correto_], input[name^=bonificacao_]");

        var total = 0;

        $.each(inputsValores, function() {


            var valor = $(this).val().replace(/\./,'').replace(/\,/,'.');

            valor = parseFloat(valor);

            if (typeof valor == "undefined" || isNaN(valor)) {
            valor = 0;
            }

            total += valor;
        });

        $(inputTotal).val(total.toFixed(2));
        });

        $("input.calcula_valor").blur();
    });

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
<div class="row">
  <b class="obrigatorio pull-right">  * Para listar todas as OS clique apenas em pesquisar </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <!-- DATAS -->
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
    <!-- #### -->

    <!-- POSTO -->
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
    <!-- ##### -->
    <p><br/>
      <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
      <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>

<?php
if (isset($resSubmit)) {
    if (pg_num_rows($resSubmit) > 0) {
        echo "<br />";
        $count = pg_num_rows($resSubmit);
?>
    <table id="auditoria_os_finalizada" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class='titulo_coluna' >
                <th>Detalhe</th>
                <th>OS</th>
                <?php 
                if (in_array($login_fabrica, [85]))
                    echo "<th>Chamado</th>";
                    echo "<th>Nº de OS</th>";  
                ?>
                <th>Código Posto</th>
                <th>Nome Posto</th>
                <th>Cidade Posto</th>
                <th>Estado Posto</th>
                <th>Cidade Consumidor</th>
                <th>Estado Consumidor</th>
                <th>Peça</th>
                <?php
                if (!in_array($login_fabrica, [85]))
                    echo "<th>Defeito Constatado</th>";  
                ?>                
            </tr>
        </thead>
        <tbody>
<?php

        $sql = "select lancamento, descricao, debito_credito from tbl_lancamento where ativo is true and fabrica = $login_fabrica";
        $res = pg_query($con, $sql);
        for($i=0; $i<pg_num_rows($res); $i++){
            $lancamento   = pg_fetch_result($res, $i, 'lancamento');
            $descricao    = pg_fetch_result($res, $i, 'descricao');
            $debito_credito = pg_fetch_result($res, $i, 'debito_credito');

            $option .= '<option value='.$lancamento.'-'.$debito_credito.'>'.$descricao.'</option>';

        }

        for ($i = 0; $i < $count; $i++) {
            $os                 = pg_fetch_result($resSubmit, $i, 'os');
            $id_posto           = pg_fetch_result($resSubmit, $i, 'posto');
            $cidade_consumidor  = pg_fetch_result($resSubmit, $i, 'consumidor_cidade');
            $estado_consumidor  = pg_fetch_result($resSubmit, $i, 'consumidor_estado');
            $qtde_km            = pg_fetch_result($resSubmit, $i, 'qtde_km_calculada');
            $pedagio            = pg_fetch_result($resSubmit, $i, 'pedagio');
            $bonificacao        = pg_fetch_result($resSubmit, $i, 'bonificacao');
            $defeito_constatado = pg_fetch_result($resSubmit, $i, 'defeito_constatado');
            $pecas              = pg_fetch_result($resSubmit, $i, 'pecas');
            $mao_de_obra        = pg_fetch_result($resSubmit, $i, 'mao_de_obra');
            $observacao_os      = pg_fetch_result($resSubmit, $i, 'obs');
            $codigo_posto       = pg_fetch_result($resSubmit, $i, 'codigo_posto');
            $nome_posto         = pg_fetch_result($resSubmit, $i, 'nome');
            $cidade_posto       = pg_fetch_result($resSubmit, $i, 'cidade');
            $estado_posto       = pg_fetch_result($resSubmit, $i, 'estado');

            if(!empty($pecas)){
              $sqlPecas = "SELECT
                          tbl_peca.descricao AS peca_descricao
                          FROM tbl_peca
                          JOIN tbl_os ON tbl_peca.fabrica = tbl_os.fabrica
                          WHERE peca = $pecas
                          AND tbl_os.os = $os";
              $resPeca = pg_query($con, $sqlPecas);

              if(pg_num_rows($resPeca) > 0){
                $peca_descricao = pg_fetch_result($resPeca, 0, 'peca_descricao');
              }
            }else{
              $peca_descricao = 0;
            }

            if(strlen($defeito_constatado) > 0){

                $sqlDefeito = "
                    SELECT  tbl_defeito_constatado.descricao AS defeito_descricao
                    FROM    tbl_defeito_constatado
                    WHERE   defeito_constatado = $defeito_constatado
                    AND     tbl_defeito_constatado.fabrica = $login_fabrica";
              $resDefeito = pg_query($con, $sqlDefeito);

              if(pg_num_rows($resDefeito) > 0){
                $descricao_defeito = pg_fetch_result($resDefeito, 0, 'defeito_descricao');
              }

            }
            if(strlen($mao_de_obra) == ''){
              $mao_de_obra = 0;
              $mao_de_obra = number_format(trim($mao_de_obra),2,",",".");
            }else{
              $mao_de_obra = number_format(trim(pg_fetch_result($resSubmit, $i,'mao_de_obra')),2,",",".");
            }

            if(strlen($pedagio) == ''){
              $pedagio = 0;
              $pedagio = number_format(trim($pedagio),2,",",".");
            }else{
              $pedagio = number_format(trim(pg_fetch_result($resSubmit, $i, "pedagio")),2,",",".");
            }

            if (strlen($bonificacao) == '') {
                $bonificacao = 0;
            }
            $bonificacao = number_format($bonificacao,2,',','.');

            if(strlen($qtde_km) == ''){
              $qtde_km = 0;
              $qtde_km = number_format(trim($qtde_km),2,",",".");
            }else{
              $qtde_km = number_format(trim(pg_fetch_result($resSubmit, $i, "qtde_km_calculada")),2,",",".");
            }
?>
            <tr>
                <td class="tac" style="cursor: pointer;" onClick="expande('<?=$i?>')" ><i class="icon-plus" id="icone_expande_<?=$i?>"></i></td>
                <td class='tac'><a href='os_press.php?os=<?=$os?>' target='_blank' ><?=$os?></a></td>
                <?php
                if (in_array($login_fabrica, [85])){
                    $chamado = pg_fetch_result($resSubmit, $i, 'hd_chamado');
                    if (is_null($chamado)) {
                        $chamado = pg_fetch_result($resSubmit, $i, 'hd_chamado_extra');
                    }
                    $sqlProd = "SELECT os, produto, serie 
                                FROM tbl_os 
                                WHERE fabrica = 85 
                                AND os = $os";
                    $resProd = pg_query($con, $sqlProd);
                    $serie = pg_fetch_result($resProd, 0, 'serie');
                    $produto = pg_fetch_result($resProd, 0, 'produto');
                    $sqlOS = "  SELECT  COUNT(os) AS num_os 
                                FROM tbl_os 
                                WHERE produto = {$produto}
                                AND serie =  '{$serie}'
                                GROUP BY produto, serie ";
                    $resOs = pg_query($con, $sqlOS);
                    $num_os = pg_fetch_result($resOs, 0, 'num_os');
                    echo "<td class='tac'><a href='callcenter_interativo_new.php?callcenter={$chamado}' target='_blank' >{$chamado}</a></td>"; 
                    echo "<td class='tac'>{$num_os}</td>";
                }
                ?> 
                <td class='tac'><?=$codigo_posto?></td>
                <td><?=$nome_posto?></td>
                <td><?=$cidade_posto?></td>
                <td class='tac'><?=$estado_posto?></td>
                <td><?=$cidade_consumidor?></td>
                <td class='tac'><?=$estado_consumidor?></td>
                <td class='tac'><?=$peca_descricao?></td>
                <?php
                if (!in_array($login_fabrica, [85]))
                    echo "<td class='tac'><?=$descricao_defeito?></td>";  
                ?>                
            </tr>
            <tr id="completo_<?=$i?>" style="display: none;">
                <td colspan="10">
                    <div class="alert alert-error" id="erro_<?=$i?>" style="display:none"></div>
                    <div class="row">
                        <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
                    </div>
                    <form name="frm_relatorio<?=$i?>" METHOD="POST" ACTION="<?=$PHP_SELF?>" align="center" class="form-search form-inline tc_formulario">
                        <input type="hidden" name="id_os_<?=$i?>" value="<?=$os?>">
                        <input type="hidden" name="id_posto_<?=$i?>" value="<?=$id_posto?>">
                        <div class="titulo_tabela">Resultado Detalhado</div>
                        <br />
                        <div class="container">

                    <!--### Observação da OS ###-->

                            <div class="container">
                                <div class="row-fluid" id="observacao_os_<?=$i?>">
                                    <div class="span2"></div>
                                    <div class="span8">
                                        <div class="control-group">
                                            <label class="control-label" for="observacao_os_<?=$i?>">Observações da OS</label>
                                            <div class="controls controls-row">
                                                <h5 class="asteristico">*</h5>
                                                <textarea rows="4" name="observacao_os_<?=$i?>" id="observacao_os_<?=$i?>" style="width: 450px;" readonly><?=$observacao_os?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="span2"></div>
                                </div>
                            </div>
                            <br />


                    <!--### Mão de Obra ###-->
                            <div class="row-fluid">
                                <div class="span3">
                                    <label class="radio">
                                    <input type="radio" name="aprovacao_mao_obra_<?=$i?>" value="aprova_mao_obra" checked onClick="aprova_mao_obra('<?=$i?>')">
                                        Aprova Mão de Obra
                                    </label>
                                </div>
                                <div class="span3">
                                    <label class="radio">
                                        <input type="radio" id="aprovacao_mao_obra_<?=$i?>" name="aprovacao_mao_obra_<?=$i?>" value="reprova_mao_obra" onClick="reprova_mao_obra('<?=$i?>')">
                                        Reprova Mão de Obra
                                    </label>
                                </div>
                                <div class="span3">
                                    <div class="control-group">
                                        <label class="control-label" for="mao_de_obra_<?=$i?>">Mão de Obra</label>
                                        <div class="controls controls-row">
                                            <div class="span4">
                                                <input type="text" name="mao_de_obra_<?=$i?>" id="mao_de_obra_<?=$i?>" size="12" maxlength="10" class="span12" value="<?=$mao_de_obra?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span3">
                                    <div class="control-group">
                                    <label class="control-label" for="mao_de_obra_correta_<?=$i?>">Mão de Obra Correta</label>
                                    <div class="controls controls-row">
                                        <div class="span4">
                                        <h5 class="asteristico">*</h5>
                                        <input type="text" name="mao_de_obra_correta_<?=$i?>" id="mao_de_obra_correta_<?=$i?>" size="12" maxlength="10" class="span12 calcula_valor" price="true" value="<?=$mao_de_obra?>" readonly>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                            <div class="container">
                                <div class="row-fluid" style="display:none" id="comentario_mao_obra_<?=$i?>">
                                    <div class="span2"></div>
                                    <div class="span8">
                                        <div class="control-group">
                                            <label class="control-label" for="text_mao_obra_<?=$i?>">Comentário Mão de Obra</label>
                                            <div class="controls controls-row">
                                                <h5 class="asteristico">*</h5>
                                                <textarea rows="4" name="text_mao_obra_<?=$i?>" id="text_mao_obra_<?=$i?>" style="width: 450px;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="span2"></div>
                                </div>
                            </div>
                            <br />

                    <!--### Fim Mão de Obra ###-->

                    <!--### Aprovacao KM ###-->
                            <div class="row-fluid">
                                <div class="span3">
                                    <label class="radio">
                                        <input type="radio" name="aprovacao_km_<?=$i?>" value="aprova_km" checked onClick="aprova_km('<?=$i?>')">
                                        Aprova KM
                                    </label>
                                </div>
                                <div class="span3">
                                    <label class="radio">
                                        <input type="radio" name="aprovacao_km_<?=$i?>" id="reprova_km_<?=$i?>" value="reprova_km" onClick="reprova_km('<?=$i?>')">
                                        Reprova KM
                                    </label>
                                </div>
                                <div class="span3">
                                    <div class="control-group">
                                        <label class="control-label" for="valor_km_<?=$i?>">Valor KM</label>
                                        <div class="controls controls-row">
                                            <div class="span4">
                                                <input type="text" name="valor_km_<?=$i?>" id="valor_km_<?=$i?>" size="12" maxlength="10" class="span12" value="<?=$qtde_km?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span3">
                                    <div class="control-group">
                                        <label class="control-label" for="valor_km_correto_<?=$i?>">Valor KM Correto</label>
                                        <div class="controls controls-row">
                                            <div class="span4">
                                                <h5 class="asteristico">*</h5>
                                                <input type="text" name="valor_km_correto_<?=$i?>" id="valor_km_correto_<?=$i?>" size="12" maxlength="10" price="true" class="span12 calcula_valor" value="<?=$qtde_km?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="container">
                                <div class="row-fluid" style="display:none" id="comentario_km_<?=$i?>">
                                    <div class="span2"></div>
                                    <div class="span8">
                                    <div class="control-group">
                                        <label class="control-label" for="text_km_<?=$i?>">Comentário KM</label>
                                        <div class="controls controls-row">
                                            <h5 class="asteristico">*</h5>
                                            <textarea rows="4" name="text_km_<?=$i?>" id="text_km_<?=$i?>" style="width: 450px;"></textarea>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="span2"></div>
                                </div>
                            </div>
                            <br />

                <!--### Fim Aprovação KM ###-->

                <!--### Aprovacao Pedágio ###-->
                            <div class="row-fluid">
                                <div class="span3">
                                    <label class="radio">
                                        <input type="radio" name="aprovacao_pedagio_<?=$i?>" value="aprova_pedagio" checked onClick="aprova_pedagio('<?=$i?>')">
                                        Aprova Pedágio
                                    </label>
                                </div>
                                <div class="span3">
                                    <label class="radio">
                                        <input type="radio" name="aprovacao_pedagio_<?=$i?>" id="reprova_pedagio_<?=$i?>" value="reprova_pedagio" onClick="reprova_pedagio('<?=$i?>')">
                                        Reprova Pedágio
                                    </label>
                                </div>
                                <div class="span3">
                                    <div class="control-group">
                                        <label class="control-label" for="valor_pedagio_<?=$i?>">Valor Pedágio</label>
                                        <div class="controls controls-row">
                                            <div class="span4">
                                                <input type="text" name="valor_pedagio_<?=$i?>" id="valor_pedagio_<?=$i?>" size="12" maxlength="10" class="span12" value="<?=$pedagio?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span3">
                                    <div class="control-group">
                                        <label class="control-label" for="valor_pedagio_correto_<?=$i?>">Valor Pedágio Correto</label>
                                        <div class="controls controls-row">
                                            <div class="span4">
                                                <h5 class="asteristico">*</h5>
                                                <input type="text" name="valor_pedagio_correto_<?=$i?>" id="valor_pedagio_correto_<?=$i?>" size="12" maxlength="10" price="true" class="span12 calcula_valor" value="<?=$pedagio?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="container">
                                <div class="row-fluid" style="display:none" id="comentario_pedagio_<?=$i?>">
                                    <div class="span2"></div>
                                    <div class="span8">
                                        <div class="control-group">
                                            <label class="control-label" for="text_pedagio_<?=$i?>">Comentário Pedágio</label>
                                            <div class="controls controls-row">
                                                <h5 class="asteristico">*</h5>
                                                <textarea rows="4" name="text_pedagio_<?=$i?>" id="text_pedagio_<?=$i?>" style="width: 450px;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="span2"></div>
                                </div>
                            </div>
                            <br />
                <!--### Fim AprovaÃ§Ã£o Pedágio ###-->
                <!--### BONIFICAÃ‡ÃƒO ###-->
<?php
            if ($bonificacao != '0,00') {
?>
                            <div class="row-fluid">
                                <div class="span4">
                                    <div class="control-group">
                                        <label class="control-label" for="bonificacao_<?=$i?>">Bonificação por Serviço Diferenciado</label>
                                        <div class="controls controls-row">
                                            <div class="span4">
                                                <input type="text" name="bonificacao_<?=$i?>" id="bonificacao_<?=$i?>" size="12" maxlength="10" price="true" class="span12 calcula_valor" value="<?=$bonificacao?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
<?php
            }
?>
                <!--### FIM BONIFICAÃ‡ÃƒO ###-->
                <!--### Avulso ###-->
<?php
            if ($bonificacao != '0,00') {
                $disabled = "disabled";
            } else {
                $disabled = "";
            }
?>
                            <div class="row-fluid">
                                <div class="span3">
                                    <label class="radio">
                                        <input type="checkbox" name="mostra_avulso_<?=$i?>">
                                        Adicionar Avulso
                                    </label>
                                </div>
                                <div class="span9"></div>
                            </div>
                            <div id="campo_avulso_<?=$i?>" style="display:none;">
                                <div class="row-fluid">
                                    <div class="span6">
                                        <label class="control-label" for="tipo_avulso<?=$i?>">Lançamento</label>
                                        <div class="controls controls-row">
                                            <select name="tipo_avulso_<?=$i?>">
                                                <option value="">SELECIONE</option>
                                                <?=$option?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="span3">
                                        <div class="control-group">
                                            <label class="control-label" for="valor_avulso_<?=$i?>">Valor Avulso</label>
                                            <div class="controls controls-row">
                                                <div class="span4">
                                                    <h5 class="asteristico">*</h5>
                                                    <input type="text" name="valor_avulso_correto_<?=$i?>" id="valor_avulso_correto_<?=$i?>" size="12" maxlength="10" price="true" class="span12 calcula_valor" value="<?=$valor_avulso?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="span3"></div>
                                </div>

                                <div class="container">
                                    <div class="row-fluid" id="comentario_avulso_<?=$i?>">
                                        <div class="span2"></div>
                                        <div class="span8">
                                            <div class="control-group">
                                                <label class="control-label" for="text_avulso_<?=$i?>">Comentário Avulso</label>
                                                <div class="controls controls-row">
                                                    <h5 class="asteristico">*</h5>
                                                    <textarea rows="4" name="text_avulso_<?=$i?>" id="text_avulso_<?=$i?>" style="width: 450px;"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="span2"></div>
                                    </div>
                                </div>
                            </div>
                            <br />

                <!--### Fim Avulso ###-->

                            <div class="row-fluid">
                                <div class="span3">
                                    <div class="control-group">
                                        <label class="control-label">Valor Total OS:</label>
                                        <div class="controls controls-row">
                                        <div class="span8">
                                            <input type="text" name="valor_total_os_<?=$i?>" id="valor_total_os_<?=$i?>" size="12" maxlength="10" price="true" class="span12" value="" readonly style="text-align: right">
                                        </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="span9"></div>
                            </div>
                            <br/>

                            <p style="text-align: center;">
                                <button type="button" name="liberar_os" id="liberar_os_<?=$i?>" class="btn" >Liberar OS</button>
                            </p>
                        </div>
                    </form>
                </td>
            </tr>
<?php
        }
?>
        </tbody>
    </table>
    <?php
    } else {
?>
    <div class="container">
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    </div>
<?php
    }
}

include 'rodape.php';?>
