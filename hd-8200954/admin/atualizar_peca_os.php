<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if ($btn_acao == "inserirPeca") {
    $item       = trim($_POST['item']);
    $custo_peca = trim($_POST['custo_peca']);

    $sql = "UPDATE tbl_os_item SET custo_peca=$custo_peca WHERE os_item=$item";
    pg_query($con, $sql);
	if (strlen(pg_last_error()) > 0) {
		$mensagem = (strpos(pg_last_error(), 'finalizada') !==false) ? " OS finalizada, não pode alterar mais valor." : "Erro ao atualizar custo da peça da os $os.";
		$mensagem = utf8_encode($mensagem) ; 
        $resposta = array("resultado" => false, "mensagem"=> $mensagem);
    } else {
        $resposta = array("resultado" => true);
    }
    echo json_encode($resposta); exit;
}


if ($btn_acao == "gerarCredito") {
    $os           = trim($_POST['os']);
    $posto        = trim($_POST['posto']);
    $total_custo  = $_POST['total_custo'];
    $auditoria_os = $_POST['auditoria_os'];

    $resposta = array();
    if (strlen($posto) > 0) {
        $sql = "SELECT tbl_posto_fabrica.posto
                  FROM tbl_posto
                  JOIN tbl_posto_fabrica USING(posto)
                 WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                   AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto}'))
                )";
        $res = pg_query($con ,$sql);
        if (!pg_num_rows($res)) {
            $resposta = array("resultado" => false, "mensagem" => "Erro: Posto não encontrado.");
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
        }
    }

    if(empty($resposta) && $total_custo == 0){
        $sqlTotalCusto = "SELECT sum(tbl_os_item.custo_peca) as total_custo
                            FROM tbl_os
                            inner join tbl_os_produto on tbl_os_produto.os = tbl_os.os
                            inner join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                            where tbl_os.os = $os ";
        $resTotalCurso = pg_query($con, $sqlTotalCusto);
        if(pg_num_rows($resTotalCurso)> 0){
            $total_custo = pg_fetch_result($resTotalCurso, 0, "total_custo");
        }
    }

    if (empty($resposta) && $total_custo > 0) {
        pg_query($con, "BEGIN");
        $sql   = "INSERT INTO tbl_extrato_lancamento (
                                                        posto,
                                                        fabrica,
                                                        lancamento,
                                                        historico,
                                                        debito_credito,
                                                        admin,
                                                        valor,
                                                        os
                                                    ) VALUES (
                                                        $posto,
                                                        $login_fabrica,
                                                        493,
                                                        'Crédito referente as peças da OS $os',
                                                        'C',
                                                        $login_admin,
                                                        $total_custo,
                                                        $os
                                                    )
                                                    ";

        $res = pg_query($con, $sql);

        $erro = false;
        if (strlen(pg_last_error()) > 0) {
            pg_query($con, "ROLLBACK");
            $resposta = array("resultado" => false, "mensagem" => utf8_encode("Erro ao gerar crédito."));
        } else {
            pg_query($con, "COMMIT");

            $sqlServ = "SELECT servico_realizado
                          FROM tbl_servico_realizado
                         WHERE fabrica = {$login_fabrica}
                           AND peca_estoque IS TRUE";
            $resServ = pg_query($con ,$sqlServ);
            if (pg_num_rows($resServ) > 0) {
                $servico_realizado = pg_fetch_result($resServ, 0, "servico_realizado");
            }

            $aprova_os = aprovaOS($os, $auditoria_os, true);

            $sql_up = "UPDATE tbl_os SET status_checkpoint = 3 WHERE os = $os AND fabrica = $login_fabrica";
            $res_up = pg_query($con, $sql_up);

            if (strlen(pg_last_error()) > 0) {
               $erro = true;
            }

			if (!empty($servico_realizado)) {
				$sqlup = "UPDATE tbl_os_item SET servico_realizado={$servico_realizado} WHERE tbl_os_item.os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE tbl_os_produto.os={$os})";
				$resup = pg_query($con, $sqlup);
			}

            if (strlen(pg_last_error()) > 0) {
               $erro = true;
            }

            if ($aprova_os['resultado'] && !$erro) {
                //pg_query($con, "COMMIT");
                $resposta = array("resultado" => true);
            } else {
                //pg_query($con, "ROLLBACK");
                $resposta = array("resultado" => false, "mensagem" => utf8_encode($aprova_os['mensagem']));
            }

        }

    }

    echo json_encode($resposta); exit;
}

function aprovaOS($os, $auditoria_os, $todas_os = false) {
    global $con,$login_fabrica,$login_admin;

    //pg_query($con, "BEGIN");

    $condT = " AND auditoria_os = $auditoria_os";
    if ($login_fabrica == 148 && $todas_os) {
        $condT = "";
    }

	if (empty($os)) {
		return array("resultado" => false, "mensagem" => "Erro ao aprovar a os $os.");
	}

    $sql = "UPDATE tbl_auditoria_os SET liberada = current_timestamp,
                admin = $login_admin
        WHERE tbl_auditoria_os.os = $os $condT;";
    pg_query($con, $sql);

    $sql = "SELECT posto FROM tbl_os WHERE os = {$os};";
    $resPosto = pg_query($con,$sql);

    if (in_array($login_fabrica, array(156))) {

        $sql = "SELECT
                oi.os_item,
                oi.qtde,
                p.referencia as peca_referencia,
                p.referencia||' - '||p.descricao as peca_descricao,
                oi.custo_peca
            FROM tbl_os_item oi
            JOIN tbl_peca p USING(peca)
            WHERE oi.fabrica_i = {$login_fabrica}
            AND oi.os_produto IN (SELECT
                            os_produto
                        FROM tbl_os_produto
                        WHERE os = {$os});
            ";

        $resPeca = pg_query($con,$sql);
        $count_k = pg_num_rows($resPeca);
    }



    $condA = " AND tbl_auditoria_os.auditoria_os = {$auditoria_os}";
    if ($login_fabrica == 148 && $todas_os) {
        $condA = "";
    }

    $sql = "SELECT
            tbl_auditoria_status.descricao
        FROM tbl_auditoria_os
        INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
        WHERE tbl_auditoria_os.os = {$os}
        $condA
        ;";

    $resAud = pg_query($con, $sql);

    if (pg_num_rows($resPosto) > 0) {
        $posto = pg_fetch_result($resPosto, 0, "posto");
        $auditoria_desc = pg_fetch_result($resAud, 0, "descricao");

        if ($count_k > 0) {
            $tablePecas = "<br /><br />";
            $tablePecas .= "<table border=1>";
            $tablePecas .= "<thead>";
            $tablePecas .= "<tr>";
            $tablePecas .= "<th><b>Código</b></th>";
            $tablePecas .= "<th><b>Descrição</b></th>";
            $tablePecas .= "<th><b>Qtde</b></th>";
            $tablePecas .= "<th><b>Valor</b></th>";
            $tablePecas .= "</tr>";
            $tablePecas .= "</thead>";
            $tablePecas .= "<tbody>";
            for ($k = 0; $k < $count_k; $k++) {
                $c_os_item                = pg_fetch_result($resPeca, $k, os_item);
                $c_peca_referencia    = pg_fetch_result($resPeca, $k, peca_referencia);
                $c_peca_descricao     = pg_fetch_result($resPeca, $k, peca_descricao);
                $c_qtde                       = pg_fetch_result($resPeca, $k, qtde);
                $c_custo_peca             = pg_fetch_result($resPeca, $k, custo_peca);
                $tablePecas .= "<tr>";
                $tablePecas .= "<td>".$c_peca_referencia."</td>";
                $tablePecas .= "<td>".$c_peca_descricao."</td>";
                $tablePecas .= "<td>".$c_peca_descricao."</td>";
                $tablePecas .= "<td>".$c_qtde."</td>";
                $tablePecas .= "<td>".$c_custo_peca."</td>";
                $tablePecas .= "</tr>";
            }
            $tablePecas .= "</tbody>";
                        $tablePecas .= "</table>";

                        $tablePecas = utf8_encode($tablePecas);
        }

        if($login_fabrica == 156){
            $sql = "INSERT INTO tbl_comunicado
                    (fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
            VALUES (
                    {$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
                    'Auditoria',
                    'O orçamento da OS {$os} foi aprovado e a OS poderá ser consertada')";

        } else {
            $sql = "INSERT INTO tbl_comunicado
                    (fabrica,posto, obrigatorio_site, tipo, ativo, descricao, mensagem)
            VALUES (
                    {$login_fabrica},{$posto}, true, 'Com. Unico Posto', true,
                    'Auditoria',
                    'OS {$os} foi aprovada na ".$auditoria_desc."{$tablePecas}');";
        }
        pg_query($con, $sql);
    }

    if (strlen(pg_last_error()) > 0) {
        //pg_query($con, "ROLLBACK");
        $resposta = array("resultado" => false, "mensagem" => "Erro ao aprovar a os $os.");
    } else {
        //pg_query($con, "COMMIT");
        if ($login_fabrica == 148) {
            try{
                if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
                    include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
                    $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
                    $classOs = new $className($login_fabrica, $os);
                } else {
                    $classOs = new \Posvenda\Os($login_fabrica, $os);
                }
                $classOs->VerificaIntervencao($con);
            }catch(Exception $ex){
                $nao_fecha_os = $ex;
            }

            if (empty($nao_fecha_os) && !is_object($nao_fecha_os)) {
                try{
                    $classOs->verificaSolucaoOs($con);
                    $classOs->calculaOs();
                    $sql = "SELECT  tbl_os_item.os_item ,
                                tbl_os_item.os_produto,
                                tbl_os_item.qtde,
                                tbl_os_item.peca,
                                tbl_servico_realizado.gera_pedido ,
                                tbl_servico_realizado.troca_de_peca ,
                                tbl_pedido_item.preco,
                                tbl_pedido_item.tabela,
                                tbl_peca.referencia
                            FROM tbl_os_item
                            INNER JOIN tbl_os_produto on tbl_os_item.os_produto = tbl_os_produto.os_produto
                            INNER JOIN tbl_os on tbl_os_produto.os = tbl_os.os
                            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                            LEFT JOIN tbl_pedido_item on tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                            INNER JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            WHERE tbl_os.os = {$os}
                                AND tbl_os.fabrica = {$login_fabrica} ";
                    $res = pg_query($con, $sql);
                    $itens = pg_fetch_all($res);

                    $preco_peca = \Posvenda\Regras::get("preco_peca", "mao_de_obra", $login_fabrica);

                    if (!empty($preco_peca)) {
                        $tabela_preco_peca = \Posvenda\Regras::get("tabela_preco_peca", "mao_de_obra", $login_fabrica);

                        if (!empty($tabela_preco_peca)) {
                            $sqlTabela = "
                                SELECT tabela FROM tbl_tabela WHERE fabrica = {$login_fabrica} AND LOWER(descricao) = LOWER('{$tabela_preco_peca}')
                            ";
                            $resTabela = pg_query($con, $sqlTabela);

                            if (pg_num_rows($resTabela) > 0) {
                                $tabela_preco_peca = pg_fetch_result($resTabela, 0, "tabela");
                            } else {
                                throw new \Exception("Erro ao atualizar valores das peças da OS {$os}");
                            }
                        }
                    }

                    foreach ($itens as $key => $item) {
                        if (!empty($tabela_preco_peca)) {
                            unset($updatePrecoPeca);

                            $sqlPrecoPeca = "
                                SELECT preco FROM tbl_tabela_item WHERE tabela = {$tabela_preco_peca} AND peca = {$item['peca']}
                            ";
                            $resPrecoPeca = pg_query($con, $sqlPrecoPeca);

                            if (!pg_num_rows($resPrecoPeca)) {
                                throw new \Exception(utf8_encode("Erro ao finalizar a OS {$os}, a peça {$item['referencia']} está sem preço"));
                            } else {
                                $preco_peca = pg_fetch_result($resPrecoPeca, 0, "preco");
                                $updatePrecoPeca = ", preco = {$preco_peca}";
                            }
                        }

                        if($item["gera_pedido"] == "t" and $item["troca_de_peca"] == "t"){
                            $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $login_fabrica);

                            if (!empty($adicional_preco_peca)) {
                                switch ($adicional_preco_peca["formula"]) {
                                    case "+%":
                                        if (!empty($adicional_preco_peca["valor"])) {
                                            $preco_porcentagem = $item['total_item'] / 100;
                                            $preco = $item['preco'] + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                                        }
                                    break;
                                }
                            }else{
                                $preco = $item['preco'] ;
                            }

                            if(empty($preco)) {
                                $preco = 0 ;
                            }

                            $sql = "UPDATE tbl_os_item  SET custo_peca = {$preco} {$updatePrecoPeca} WHERE os_item = {$item['os_item']}";
                            $res = pg_query($con,$sql);
                        } elseif ($item["troca_de_peca"] == "t" and $item["gera_pedido"] == "f") {

                            $sql = " SELECT tbl_produto.linha ,tbl_os.posto
                            from tbl_os_produto
                            INNER JOIN tbl_os on tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                            INNER JOIN tbl_produto on tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $login_fabrica
                            WHERE tbl_os_produto.os_produto = {$item['os_produto']} ";

                            $res = pg_query($con,$sql);

                            $linha = pg_fetch_result($res, 0, "linha");
                            $posto = pg_fetch_result($res, 0, "posto");

                            $sql = "SELECT tbl_posto_linha.tabela
                                        from tbl_posto_linha
                                        INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
                                        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                                        WHERE tbl_posto_linha.linha= {$linha} AND tbl_posto_fabrica.posto = {$posto}";
                            $res = pg_query($con,$sql);
                            $tabela = pg_fetch_result($res, 0, "tabela");

                            $sql = "SELECT tbl_tabela_item.preco
                                    FROM tbl_tabela_item
                                    INNER JOIN tbl_tabela on tbl_tabela.tabela = tbl_tabela_item.tabela and tbl_tabela.fabrica = {$login_fabrica}
                                    WHERE tbl_tabela_item.peca = {$item['peca']}
                                    AND tbl_tabela.tabela = {$tabela} ";
                            $res = pg_query($con,$sql);
                            $preco = pg_fetch_result($res, 0, "preco");

                            if(!empty($preco)) {

                                 $adicional_preco_peca = \Posvenda\Regras::get("adicional_preco_peca", "mao_de_obra", $login_fabrica);

                                if (!empty($adicional_preco_peca)) {
                                    switch ($adicional_preco_peca["formula"]) {
                                        case "+%":
                                            if (!empty($adicional_preco_peca["valor"])) {
                                                $preco_porcentagem = $preco / 100;
                                                $preco = $preco + ($preco_porcentagem * $adicional_preco_peca["valor"]);
                                            }
                                        break;
                                    }
                                }
                                $total = $preco;
                                if(empty($total)) {
                                    $total = 0 ;
                                }
                                $sql = "UPDATE tbl_os_item set custo_peca = {$total} {$updatePrecoPeca} where os_item ={$item['os_item']} ";
                                $res = pg_query($con,$sql);

                            }
                        }

                        if(strlen(pg_last_error()) > 0){
                            throw new \Exception("Erro ao atualizar mão de obra da OS : {$os} -- ".pg_last_error());
                        }
                    }

                    $updateOrigem = "";

                    if (!is_null($origem)) {
                        $updateOrigem = "UPDATE tbl_os_campo_extra SET origem_fechamento = '$origem' WHERE os = {$os};";
                    }

                    $sql = "
                        UPDATE tbl_os
                        SET data_fechamento = CURRENT_DATE, finalizada = CURRENT_TIMESTAMP
                        WHERE os = {$os} AND fabrica = {$login_fabrica};

                        {$updateOrigem}
                        ";
                    $res = pg_query($con, $sql);

                    if(!$res){
                        throw new \Exception("Ocorreu um erro ao tentar finalizar a OS");
                    }
                    //pg_query($con, "COMMIT");
                    $resposta = array("resultado" => true);
                }catch(Exception $err){
                    //pg_query($con, "ROLLBACK");
                    $resposta = array("resultado" => false, "mensagem" => "Erro ao aprovar a os $os. Erro: {$err}");
                }
            }else{
                //pg_query($con, "COMMIT");
                $resposta = array("resultado" => true);
            }
        }else{
            $resposta = array("resultado" => true);
        }
    }

    return $resposta;
}


$os    = $_GET['os'];
$auditoria_os    = $_GET['auditoria_os'];
$codigo_posto = $_GET['posto'];
if (!empty($codigo_posto)) {
    $sql = "SELECT tbl_posto_fabrica.posto
                      FROM tbl_posto
                      JOIN tbl_posto_fabrica USING(posto)
                     WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                       AND ((UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
                    )";
    $res = pg_query($con ,$sql);
    if (pg_num_rows($res) > 0) {
        $posto = pg_fetch_result($res, 0, "posto");
    }
}
$sql = "SELECT tbl_peca.referencia ,
                tbl_peca.descricao,
                tbl_peca.peca_critica,
                tbl_os_item.os_item,
                tbl_os_item.qtde,
                tbl_os_item.custo_peca,
                tbl_servico_realizado.descricao AS servico_realizado,
                tbl_os_item.preco
        FROM tbl_peca
            JOIN tbl_os_item USING (peca)
            JOIN tbl_os_produto USING (os_produto)
            LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
        WHERE tbl_os_produto.os = '$os'";
        //echo $sql;die;
$resPeca = pg_query($con,$sql);

if($login_fabrica == 148){
    $sql_auditoria = "SELECT auditoria_status from tbl_auditoria_os where auditoria_os = $auditoria_os";
    $res_auditoria = pg_query($con, $sql_auditoria);
    if(pg_num_rows($res_auditoria)> 0){
        $auditoria_status = pg_fetch_result($res_auditoria, 0, auditoria_status);
    }
}
?>
<!DOCTYPE html />
<html>
<head>
    <meta http-equiv=pragma content=no-cache />
    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
    <style>
        #div_peca{height: 600px;overflow-y: auto;}
    </style>
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>

    <script type="text/javascript">
        $(function () {

            $("input[name^=valor_peca]").priceFormat({
                prefix: '',
                thousandsSeparator: '',
                centsSeparator: '.',
                ca: 2
            });

             $('body').on('click', '.btnAddPecas', function() {
                $(this).button('loading');
                var novaPeca = $(this).parent().find('input').val();
                var item = $(this).data("item");
                var dataAjax = {
                    item: item,
                    custo_peca: novaPeca,
                    btn_acao: "inserirPeca"
                };
                $.ajax({
                    url: "<?= $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
                    type: "POST",
                    data: dataAjax,
                    success: function(data){
                        var mensagem;
                        data = JSON.parse(data);
                        if (data.resultado == false) {
                            $("body #mensagem_add_peca").show();
                            $("body #mensagem_add_peca").addClass("alert alert-error");
                            $("body #mensagem_add_peca").html('<h5>'+data.mensagem+'</h5>');
                            setTimeout(function(){
                                $("body #mensagem_add_peca").hide('slow');
                                $('.btnAddPecas').button('reset');
                            }, 2000);
                        } else {
                            $("body #mensagem_add_peca").show();
                            $("body #mensagem_add_peca").addClass("alert alert-success");
                            $("body #mensagem_add_peca").html("<h5>Custo da peça atualizado com sucesso!</h5>");
                            setTimeout(function(){
                                $("body #mensagem_add_peca").hide('slow');
                                $('.btnAddPecas').button('reset');
                            }, 2000);
                        }
                    }
                });
            });

            $('body').on('click', '#btn-gera-credito', function() {
                $(this).button('loading');
                var os    = $(this).data("os");
                var posto = $(this).data("posto");
                var total_custo = $(this).data("total");
                var auditoria_os = $(this).data("auditoriaos");

                var dataAjax = {
                    os: os,
                    posto: posto,
                    total_custo: total_custo,
                    auditoria_os: auditoria_os,
                    btn_acao: "gerarCredito"
                };
                $.ajax({
                    url: "<?= $_SERVER['PHP_SELF']; ?>",//'relatorio_auditoria_status.php',
                    type: "POST",
                    data: dataAjax,
                    success: function(data){
                        console.log(data)
                        var mensagem;
                        data = JSON.parse(data);
                        if (data.resultado == false) {
                            $("body #mensagem_add_peca").show();
                            $("body #mensagem_add_peca").addClass("alert alert-error");
                            $("body #mensagem_add_peca").html('<h5>'+data.mensagem+'</h5>');
                            setTimeout(function(){
                                $("body #mensagem_add_peca").hide('slow');
                                $('#btn-gera-credito').button('reset');
                            }, 2000);
                        } else {
                            $("body #mensagem_add_peca").show();
                            $("body #mensagem_add_peca").addClass("alert alert-success");
                            $("body #mensagem_add_peca").html("<h5>Crédito gerado com sucesso!</h5>");
                            setTimeout(function(){
                                $("body #mensagem_add_peca").hide('slow');
                                $('#btn-gera-credito').button('reset');
                                window.parent.location.reload();
                                window.parent.Shadowbox.close();
                            }, 2000);
                        }
                    }
                });
            });
        });
    </script>
</head>
<body>
<?php
    $plugins = array(
       "price_format"
    );

    include __DIR__."/plugin_loader.php";

    if (pg_num_rows($resPeca) > 0) {
    $count_k = pg_num_rows($resPeca);
?>
    <div id='div_peca'>
        <h4 style="margin: 10px;">OS: <?=$os?></h4>
        <table id="resultado_peca" class='table table-striped table-bordered table-hover table-fixed' style="margin: 10px; padding-right: 20px;">
            <thead>
                <tr class='titulo_coluna'>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th width="80">Peça Critica</th>
                    <th width="20">Qtde</th>
                    <th width="150">Custo da Peça</th>
                    <?php
                    if ($login_fabrica == 148) { ?>
                        <th>Preço Unitário</th>
                        <th>Preço Total</th>
                    <?php
                    }
                    ?>
                    <th width="100">Serviço Realizado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for($k=0; $k<$count_k; $k++) {
                    $codigo_peca       = pg_fetch_result($resPeca,$k,'referencia');
                    $descricao_peca    = pg_fetch_result($resPeca,$k,'descricao');
                    $peca_critica      = pg_fetch_result($resPeca,$k,'peca_critica');
                    $qtde              = pg_fetch_result($resPeca,$k,'qtde');
                    $custo_peca        = pg_fetch_result($resPeca,$k,'custo_peca');
                    $os_item           = pg_fetch_result($resPeca,$k,'os_item');
                    $preco             = pg_fetch_result($resPeca,$k, "preco");
                    $servico_realizado = pg_fetch_result($resPeca,$k,'servico_realizado');
                    $total_custo[] = $custo_peca*$qtde;
                    if ($peca_critica == 't') {
                        $peca_critica = "Sim";
                    } else {
                        $peca_critica = "Não";
                    }
                ?>
                <tr>
                    <td class="tac"><?=$codigo_peca?></td>
                    <td class="tac"><?=$descricao_peca?></td>
                    <td class="tac"><?=$peca_critica?></td>
                    <td class="tac"><?=$qtde?></td>
                    <td class="tac">
                        <input type="text" style=" width: 70px;height:30px;" name="valor_peca" class="valor_peca" value="<?=priceFormat($custo_peca);?>" />
                        <button type="button" class="btn btn-primary btn-small btnAddPecas" style="vertical-align: top; margin-top: 2px;" data-loading-text="Salvando..." data-item="<?=$os_item?>">Salvar</button>
                    </td>
                    <?php
                    if ($login_fabrica == 148) { ?>
                        <td class="tac"><?= number_format($preco, 2,",","."); ?></td>
                        <td class="tac"><?= number_format($preco * $qtde, 2,",","."); ?></td>
                    <?php
                    }
                    ?>
                    <td class="tac"><?=$servico_realizado?></td>
                </tr>
                <?php } //fecha for ?>
                <?php
                    if ($login_fabrica == 148 AND $auditoria_status == 6){
                        $sqlCredito = "SELECT os
                                     FROM tbl_extrato_lancamento
                                    WHERE tbl_extrato_lancamento.os = {$os}
                                      AND tbl_extrato_lancamento.fabrica = {$login_fabrica}
                                      AND tbl_extrato_lancamento.posto = {$posto}
                                      AND tbl_extrato_lancamento.lancamento = 493";

                        $resCredito = pg_query($con, $sqlCredito);
                        if (pg_num_rows($resCredito) == 0) {

                ?>
                <tr>
                    <td colspan="6" align="center" style="text-align:center;">

                        <button data-total="<?php echo array_sum($total_custo);?>" data-os="<?php echo $os;?>" data-posto="<?php echo $codigo_posto;?>" data-auditoriaos="<?php echo $auditoria_os;?>" type="button" id="btn-gera-credito" data-loading-text="Gerando..." class="btn btn-success">Gerar Crédito</button>
                    </td>
                </tr>
                <?php }}?>
                <tr><td colspan="6"><div id="mensagem_add_peca"></div></td></tr>
            </tbody>
        </table>
    </div>
<?php } ?>
