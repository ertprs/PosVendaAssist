<?php
    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    $admin_privilegios="financeiro,gerencia,call_center";
    include 'autentica_admin.php';
    include 'funcoes.php';

    if(isset($_GET["os"]) ){
        $posto = $_GET['posto'];
        $pesquisa_os  = $_GET["os"];
        $mes_pesquisa = $_GET["mes_pesquisa"];

        $data_consulta_final = date('Y-m-d',$mes_pesquisa);

        $data_consulta_inicial = strtotime($data_consulta_final." -2 months");
        $data_consulta_inicial = date('Y-m-d',$data_consulta_inicial);
        $cond_data = "AND data_geracao BETWEEN '$data_consulta_inicial 00:00:00' AND '$data_consulta_final 23:59:59' ORDER BY data_abertura;";


        $sql1 = "SELECT extrato_programado - interval '3 months' as extrato_programado, posto,codigo_posto, nome
                    FROM tbl_posto_fabrica
                        JOIN tbl_posto using(posto)
                    WHERE fabrica = $login_fabrica
                        AND CREDENCIAMENTO <> 'DESCREDENCIADO'
                        AND tbl_posto_fabrica.posto = '$posto'
                        AND extrato_programado IS NOT NULL;";
        $res1 = pg_query($con,$sql1);
        $extrato_programado = pg_fetch_result($res1,0,'extrato_programado');

        //echo $sql1;

        $sql = "SELECT      os,
                            data_fechamento,
                            data_abertura,
                            tbl_os.os_reincidente,
                            tbl_os.posto,
                            tbl_os.fabrica,
                            (data_fechamento - data_abertura) as dias,
                            tbl_os.tipo_atendimento,
                            tbl_produto.produto,
                            tbl_produto.referencia,
							tbl_produto.descricao
							,tbl_os_troca.os_troca
                into temp tmp_ex_$posto
                FROM tbl_os
                JOIN tbl_os_extra USING(OS)
                JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				JOIn tbl_extrato USING (extrato,fabrica,posto)
				left join tbl_os_troca using(Os)
				where tbl_os.fabrica = $login_fabrica
				and tbl_os.posto = $posto
				$cond_data
                create index tmp_ex_os_$posto on tmp_ex_$posto(os);";
        switch ($pesquisa_os) {
            case 'os':
                    $sql .= " SELECT    os,
                                        dias,
                                        TO_CHAR(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                        TO_CHAR(data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                        produto,
                                        referencia,
										descricao,
										os_troca
                                FROM tmp_ex_$posto
                                WHERE tipo_atendimento <> 243;";
                break;
            case 'osdias':
                    $sql .= " SELECT    os,
                                        dias,
                                        TO_CHAR(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                        TO_CHAR(data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                        produto,
                                        referencia,
										descricao,
										os_troca
                                FROM tmp_ex_$posto;";
                break;

            case 'ostroca':
                    $sql .= "SELECT tmp_ex_$posto.os,
                                    tmp_ex_$posto.dias,
                                    TO_CHAR(tmp_ex_$posto.data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                    TO_CHAR(tmp_ex_$posto.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                    tmp_ex_$posto.produto,
                                    tmp_ex_$posto.referencia,
                                    tmp_ex_$posto.descricao
                                FROM tbl_os_troca
                                    --join tbl_os using(os)
                                    join tmp_ex_$posto using(os)
                                where posto = $posto
                                and fabrica = $login_fabrica
                                and causa_troca = 382 ;";
                break;

            case 'osreincidente':
                    $sql .= "SELECT tmp_ex_$posto.os,
                                    tmp_ex_$posto.dias,
                                    TO_CHAR(tmp_ex_$posto.data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                    TO_CHAR(tmp_ex_$posto.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                    tmp_ex_$posto.produto,
                                    tmp_ex_$posto.referencia,
                                    tmp_ex_$posto.descricao
                                FROM  tmp_ex_$posto  where os_reincidente;";
                break;

            case 'osreprovada':
                    $sql .= "SELECT os,
                                    dias,
                                    TO_CHAR(data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                    TO_CHAR(data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                    produto,
                                    referencia,
                                    descricao
                                FROM tbl_auditoria_os
                                    JOIN tmp_ex_$posto using(os)
                                    JOIN tbl_admin ON tbl_admin.admin = tbl_auditoria_os.admin
                                where (
                                    tbl_auditoria_os.reprovada::date between '$extrato_programado'::date and current_date
                                    or tbl_auditoria_os.cancelada::date between '$extrato_programado'::date and current_date
                                    )
                                    AND posto = $posto
                                    AND tbl_admin.fabrica = $login_fabrica;";
                break;

            case 'oscomponente':
                    $sql .= "select count(1) as itens,
                                    sum(
                                        case
                                            when gera_pedido and acessorio then 1
                                            when gera_pedido is false then 1
                                            else 0
                                        end
                                    ) as acessorio,
                                    tmp_ex_$posto.os,
                                    tmp_ex_$posto.dias,
                                    TO_CHAR(tmp_ex_$posto.data_abertura,'DD/MM/YYYY')   AS data_abertura,
                                    TO_CHAR(tmp_ex_$posto.data_fechamento,'DD/MM/YYYY')   AS data_fechamento,
                                    tmp_ex_$posto.produto,
                                    tmp_ex_$posto.referencia,
                                    tmp_ex_$posto.descricao
                        from tbl_os_item
                        join tbl_os_produto using(os_produto)
                        join tmp_ex_$posto using(os)
                        join tbl_peca using(peca)
                        join tbl_servico_realizado using(servico_realizado)
                        where (tbl_os_item.parametros_adicionais !~* 'recall' or tbl_os_item.parametros_adicionais isnull)
                        group by    tmp_ex_$posto.os,
                                    tmp_ex_$posto.dias,
                                    data_abertura,
                                    data_fechamento,
                                    tmp_ex_$posto.produto,
                                    tmp_ex_$posto.referencia,
                                    tmp_ex_$posto.descricao;";
                break;

            // default:
            //     # code...
            //     break;
        }
        $res = pg_query($con,$sql);
        //echo $sql;
            ?>

            <!DOCTYPE html>
            <html>
                <head>
                    <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
                    <link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

                    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
                    <script src="bootstrap/js/bootstrap.js"></script>
                    <script src="plugins/dataTable.js"></script>
                    <script src="plugins/resize.js"></script>

                </head>
                <body>
                    <div style="overflow-y:scroll;height:570px;">
                        <br />
                        <?php
                        if(pg_num_rows($res) > 0){
                            if ($pesquisa_os != 'osdias' AND $pesquisa_os != 'oscomponente') {?>
                                <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
                                    <thead>
                                        <tr class='titulo_coluna' >
                                            <th>OS</th>
                                            <th>Data Abertura</th>
                                            <th>Data Fechamento</th>
                                            <th>Referencia do Produto</th>
                                            <th>Descrição do Produto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            <?php
                            }
                                $count = 0;
                                $cabecalho = true;
                                for ($i = 0; $i < pg_num_rows($res); $i++) {

                                    $os              = pg_fetch_result($res,$i,'os');
                                    $data_abertura   = pg_fetch_result($res,$i,'data_abertura');
                                    $data_fechamento = pg_fetch_result($res,$i,'data_fechamento');
                                    $ref_prod        = pg_fetch_result($res,$i,'referencia');
                                    $desc_prod       = pg_fetch_result($res,$i,'descricao');
                                    $dias            = pg_fetch_result($res,$i,'dias');
                                    $os_troca        = pg_fetch_result($res,$i,'os_troca');

                                    if ($pesquisa_os == 'osdias') {
										if(empty($os_troca)) {
                                        $sql_os = "SELECT (emissao - digitacao_item::date) as dias_item
                                                    FROM tbl_os_item
                                                    JOIN tbl_os_produto USING(os_produto)
                                                    JOIN tbl_faturamento_item using(peca, pedido,os_item)
                                                    JOIN tbl_faturamento using(faturamento)
                                                    where tbl_os_produto.os = $os";
                                        $res_os = pg_query($con,$sql_os);
                                        if(pg_num_rows($res_os) > 0) {
                                            $dias_item = pg_fetch_result($res_os,0,'dias_item');
                                            if(($dias - ($dias_item + 10)) > 20) {
                                                $count++;
                                                if ($cabecalho == true) {
                                                    $cabecalho = false;
                                                    ?>
                                                    <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
                                                        <thead>
                                                            <tr class='titulo_coluna' >
                                                                <th>OS</th>
                                                                <th>Data Abertura</th>
                                                                <th>Data Fechamento</th>
                                                                <th>Referencia do Produto</th>
                                                                <th>Descrição do Produto</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                <?php
                                                }?>
                                                <tr>
													<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a></td>
                                                    <td><?=$data_abertura?></td>
                                                    <td><?=$data_fechamento?></td>
                                                    <td><?=$ref_prod?></td>
                                                    <td><?=$desc_prod?></td>
                                                </tr>
                                            <?php
                                            }
                                        }elseif($dias > 20){
                                            $count++;
                                            if ($cabecalho == true) {
                                                $cabecalho = false;
                                                ?>
                                                <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
                                                    <thead>
                                                        <tr class='titulo_coluna' >
                                                            <th>OS</th>
                                                            <th>Data Abertura</th>
                                                            <th>Data Fechamento</th>
                                                            <th>Referencia do Produto</th>
                                                            <th>Descrição do Produto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                            <?php
                                            }?>
                                            <tr>
												<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a></td>
                                                <td><?=$data_abertura?></td>
                                                <td><?=$data_fechamento?></td>
                                                <td><?=$ref_prod?></td>
                                                <td><?=$desc_prod?></td>
                                            </tr>
                                        <?php
                                        }
									   }
                                    }elseif ($pesquisa_os == 'oscomponente') {
                                        $itens = pg_fetch_result($res,$i,'itens');
                                        $acessorio = pg_fetch_result($res,$i,'acessorio');
                                        if($itens == $acessorio) {
                                            $count++;
                                            if ($cabecalho == true) {
                                                $cabecalho = false;
                                                ?>
                                                <table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-large' >
                                                    <thead>
                                                        <tr class='titulo_coluna' >
                                                            <th>OS</th>
                                                            <th>Data Abertura</th>
                                                            <th>Data Fechamento</th>
                                                            <th>Referencia do Produto</th>
                                                            <th>Descrição do Produto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                            <?php
                                            }?>
                                            <tr>
												<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a></td>
                                                <td><?=$data_abertura?></td>
                                                <td><?=$data_fechamento?></td>
                                                <td><?=$ref_prod?></td>
                                                <td><?=$desc_prod?></td>
                                            </tr>
                                        <?php
                                        }
                                    }else{
                                        $count++;
                                        ?>
                                        <tr>
											<td><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a></td>
                                            <td><?=$data_abertura?></td>
                                            <td><?=$data_fechamento?></td>
                                            <td><?=$ref_prod?></td>
                                            <td><?=$desc_prod?></td>
                                        </tr>
                                    <?php
                                    }
                                }
                                if ($pesquisa_os != 'osdias' AND $pesquisa_os != 'oscomponente') {?>
                                    </tbody>
                                </table>
                                <?php
                                }
                                if ($pesquisa_os == 'osdias' AND $pesquisa_os == 'oscomponente' AND $cabecalho == false) {?>
                                    </tbody>
                                </table>
                                <?php
                                }

                                if ($pesquisa_os == 'osdias' AND $pesquisa_os == 'oscomponente' AND $cabecalho == true) {?>
                                    <div class="container">
                                        <div class="alert">
                                            <h4>Nenhum resultado encontrado</h4>
                                        </div>
                                    </div>
                                <?php
                                }
                                ?>

                        <?php
                        if ($count > 20) {?>
                            <script>
                                $.dataTableLoad({ table: "#resultado_os_atendimento" });
                            </script>
                        <?php
                        }?>
                        <br />
                    <?php
                    }else{?>
                        <div class="container">
                            <div class="alert">
                                <h4>Nenhum resultado encontrado</h4>
                            </div>
                        </div>
                    <?php
                    }?>
                    </div>
    </body>
</html>
<?php
}
?>
