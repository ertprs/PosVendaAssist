<?php

try {
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    include_once '../../class/communicator.class.php';
    $mailer = new TcComm("noreply@tc");

    $fabrica = 104;
    $fabrica_nome = 'vonder';
    $env = 'producao';

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    if (file_exists(__DIR__ . '/.env')) {
        include_once __DIR__ . '/.env';
    }

    $origem = "/var/www/cgi-bin/vonder/entrada";
    $destino = "/home/vonder/vonder-telecontrol/bkp";
    $arquivos = "/tmp/vonder/faturamento";

    if ($env == 'dev') {
        $origem = __DIR__ . "/entrada";
        $destino = __DIR__ . "/bkp";
        $arquivos = $origem . "/arq";

        system("mkdir -p $destino $arquivos");
    }

    $arquivo = "{$origem}/faturamento.txt";
    $arquivo_item = "{$origem}/faturamento_item.txt";
    $arquivo_cancelado =  "{$origem}/faturamento_cancelado.txt";

    if (!file_exists($arquivo) or filesize($arquivo) == 0) {
        $phpCron->termino();
        exit(0);
    }

    if (!file_exists($arquivo_item) or filesize($arquivo_item) == 0) {
        $phpCron->termino();
        exit(0);
    }

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $sql = "DROP TABLE IF EXISTS vonder_nf;
            DROP TABLE IF EXISTS vonder_nf_item;
            DROP TABLE vonder_nf_canc ";
    $drop = pg_query($con, $drop);

    $sql = "CREATE TABLE vonder_nf
            (
                txt_cnpj     text,
                nota_fiscal  text,
                serie        text,
                txt_emissao  text,
                cfop         text,
                txt_total    text,
                txt_ipi      text,
                txt_icms     text,
                transp       text,
                txt_natureza text,
                total        float,
                posto        int4
            );

            CREATE TABLE vonder_nf_item
            (
                txt_cnpj              text,
                nota_fiscal           text,
                serie                 text,
                referencia_solicitada text,
                txt_pedido            text,
                txt_pedido_item       text,
                txt_qtde              text,
                txt_unitario          text,
                txt_aliq_ipi          text,
                txt_aliq_icms         text,
                txt_valor_ipi         text,
                txt_valor_icms        text,
                txt_valor_sub_icms    text,
                txt_base_ipi          text,
                txt_base_icms         text,
                txt_base_sub_icms     text,
                referencia_atendida   text,
                txt_valor_impostos    text,
                posto                 INT4,
                peca                  INT4,
                qtde                  FLOAT,
                pedido                INT4,
                pedido_item           INT4,
                unitario              FLOAT,
                aliq_ipi              FLOAT,
                valor_ipi             FLOAT,
                valor_icms            FLOAT,
                base_icms             FLOAT,
                aliq_icms             FLOAT,
                valor_impostos        FLOAT ,
                faturamento           INT4               
            );

            CREATE TABLE vonder_nf_canc
            (
                cnpj            text,
                nota_fiscal     text,
                serie           text,
                txt_cancelada   text,
                vazio           text,
                posto           INT4,
                cancelada       DATE,
                faturamento     INT4
            )";
    //die(nl2br($sql));
    $create = pg_query($con, $sql);
    
    $conteudo = file_get_contents($arquivo);
    $conteudo = explode("\n", $conteudo);

    foreach ($conteudo as $linha) {
        if (!empty($linha)) {
            list (
                $txt_cnpj,
                $nota_fiscal,
                $serie,
                $txt_emissao,
                $cfop,
                $txt_total,
                $txt_icms,
                $transp,
                $txt_natureza
            ) = explode (";", $linha);

            $ins = "INSERT INTO vonder_nf VALUES (
                    trim('$txt_cnpj'),
                    trim('$nota_fiscal'),
                    trim('$serie'),
                    trim('$txt_emissao'),
                    trim('$cfop'),
                    trim('$txt_total'),
                    trim('$txt_icms'),
                    trim('$transp'),
                    trim('$txt_natureza')
                )";
            $qry = pg_query($con, $ins);
        }
    }

    /*$sql = "ALTER TABLE vonder_nf ADD COLUMN total FLOAT;
            ALTER TABLE vonder_nf ADD COLUMN posto INT4";
    $qry = pg_query($con, $sql);*/

    $sql = "UPDATE vonder_nf SET total = REPLACE($txt_total,',','.')::numeric";
    $qry = pg_query($con, $sql);

    $sql = "UPDATE vonder_nf SET posto =
            (
                SELECT tbl_posto.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica
                    ON tbl_posto.posto = tbl_posto_fabrica.posto
                    AND   tbl_posto_fabrica.fabrica = $fabrica
                WHERE vonder_nf.txt_cnpj = tbl_posto.cnpj
            )";
    $qry = pg_query($con, $sql);

    $sql = "DROP TABLE IF EXISTS vonder_nf_sem_posto";
    $qry = pg_query($con, $sql);

    $sql = "SELECT * INTO vonder_nf_sem_posto
            FROM vonder_nf
            WHERE posto IS NULL";
    $qry = pg_query($con, $sql);

    $sql = "DELETE FROM vonder_nf WHERE posto IS NULL";
    $qry = pg_query($con, $sql);

    $conteudo_item = file_get_contents($arquivo_item);
    $conteudo_item = explode("\n", $conteudo_item);

    foreach ($conteudo_item as $linha_item) {
        if (!empty($linha_item)) {
             list (
                $txt_cnpj,
                $nota_fiscal,
                $serie,
                $referencia_solicitada,
                $txt_pedido,
                $txt_pedido_item,
                $txt_qtde,
                $txt_unitario,
                $txt_aliq_ipi,
                $txt_aliq_icms,
                $txt_valor_ipi,
                $txt_valor_icms,
                $txt_valor_sub_icms,
                $txt_base_ipi,
                $txt_base_icms,
                $txt_base_sub_icm,
                $referencia_atendida,
                $txt_valor_impostos
            ) = explode (";", $linha_item);

            $ins = "INSERT INTO vonder_nf_item VALUES (
                        trim('$txt_cnpj'),
                        trim('$nota_fiscal'),
                        trim('$serie'),
                        trim('$referencia_solicitada'),
                        trim('$txt_pedido'),
                        trim('$txt_pedido_item'),
                        trim('$txt_qtde'),
                        trim('$txt_unitario'),
                        trim('$txt_aliq_ipi'),
                        trim('$txt_aliq_icms'),
                        trim('$txt_valor_ipi'),
                        trim('$txt_valor_icms'),
                        trim('$txt_valor_sub_icms'),
                        trim('$txt_base_ipi'),
                        trim('$txt_base_icms'),
                        trim('$txt_base_sub_icm'),
                        trim('$referencia_atendida'),
                        trim('$txt_valor_impostos')
                    )";
            $qry = pg_query($con, $ins);
        }
    }

    /*$sql = "ALTER TABLE vonder_nf_item ADD COLUMN posto INT4;
            ALTER TABLE vonder_nf_item ADD COLUMN peca INT4;
            ALTER TABLE vonder_nf_item ADD COLUMN qtde FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN pedido INT4;
            ALTER TABLE vonder_nf_item ADD COLUMN pedido_item INT4;
            ALTER TABLE vonder_nf_item ADD COLUMN unitario FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN aliq_ipi FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN valor_ipi FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN valor_icms FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN base_icms FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN aliq_icms FLOAT;
            ALTER TABLE vonder_nf_item ADD COLUMN valor_impostos FLOAT";
    $qry = pg_query($con, $sql);*/

    $sql = "UPDATE vonder_nf_item SET
                qtde       = txt_qtde::numeric                        ,
                unitario   = REPLACE(case when length(txt_unitario)    = 0 then '0' else txt_unitario end   ,',','.')::numeric   ,
                aliq_ipi   = REPLACE(case when length(txt_aliq_ipi )   = 0 then '0' else txt_aliq_ipi end   ,',','.')::numeric   ,
                valor_ipi  = REPLACE(case when length(txt_valor_ipi )  = 0 then '0' else txt_valor_ipi end  ,',','.')::numeric  ,
                valor_icms = REPLACE(case when length(txt_valor_icms ) = 0 then '0' else txt_valor_icms end ,',','.')::numeric ,
                base_icms  = REPLACE(case when length(txt_base_icms )  = 0 then '0' else txt_base_icms end  ,',','.')::numeric ,
                aliq_icms  = REPLACE(case when length(txt_aliq_icms )  = 0 then '0' else txt_aliq_icms end  ,',','.')::numeric ,
                valor_impostos  = REPLACE(case when length(txt_valor_impostos )  = 0 then '0' else txt_valor_impostos end  ,',','.')::numeric";
    $qry = pg_query($con, $sql);

    $sql = "UPDATE vonder_nf_item SET posto = (
                SELECT tbl_posto.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica
                    ON tbl_posto.posto = tbl_posto_fabrica.posto
                    AND   tbl_posto_fabrica.fabrica = $fabrica
                WHERE vonder_nf_item.txt_cnpj = tbl_posto.cnpj
            )";
    $qry = pg_query($con, $sql);

    $sql = "UPDATE vonder_nf_item
                SET pedido = tbl_pedido.pedido
            FROM tbl_pedido
            WHERE vonder_nf_item.txt_pedido::numeric = tbl_pedido.pedido
            AND tbl_pedido.fabrica = $fabrica
            AND (txt_pedido is not null and length(trim (txt_pedido))> 0)";
    $qry = pg_query($con, $sql);

    $sql = "UPDATE vonder_nf_item
                SET pedido_item = tbl_pedido_item.pedido_item
            FROM tbl_pedido_item
            WHERE vonder_nf_item.txt_pedido_item::numeric = tbl_pedido_item.pedido_item
            AND (txt_pedido_item is not null and length(trim (txt_pedido_item))> 0)";
    $qry = pg_query($con, $sql);

    $sql = "UPDATE vonder_nf_item
                SET peca = tbl_peca.peca
            FROM  tbl_peca
            WHERE vonder_nf_item.referencia_atendida = tbl_peca.referencia
            AND tbl_peca.fabrica = $fabrica";
    $qry = pg_query($con, $sql);

    $sql = "SELECT fn_atualiza_pedido_item_peca ($fabrica,pedido_item,peca,referencia_solicitada ||' alterada para ' || referencia_atendida)
            FROM vonder_nf_item
            WHERE referencia_solicitada <> referencia_atendida
            AND peca NOTNULL";
    $qry = pg_query($con, $sql);

    if (file_exists($arquivo_cancelado) and filesize($arquivo_cancelado) > 0) {
        $conteudo_cancelado = file_get_contents($arquivo_cancelado);
        $conteudo_cancelado = explode("\n", $conteudo_cancelado);

        foreach ($conteudo_cancelado as $linha_cancelado) {
            if (!empty($linha_cancelado)) {
                list (
                    $cnpj,
                    $nota_fiscal,
                    $serie,
                    $txt_cancelada,
                    $vazio
                ) = explode (";", $linha_cancelado);

                $ins = "INSERT INTO vonder_nf_canc VALUES (
                    trim('$cnpj'),
                    trim('$nota_fiscal'),
                    trim('$serie'),
                    trim('$txt_cancelada'),
                    trim('$vazio')
                )";
                $qry = pg_query($con, $ins);
            }
        }

        /*$sql = "ALTER TABLE vonder_nf_canc ADD COLUMN posto INT4;
                ALTER TABLE vonder_nf_canc ADD COLUMN cancelada DATE";
        $qry = pg_query($con, $sql);*/

        $sql = "UPDATE vonder_nf_canc SET cancelada = $txt_cancelada::date";
        $qry = pg_query($con, $sql);

        $sql = "UPDATE vonder_nf_canc SET posto = tbl_posto.posto
                FROM   tbl_posto
                JOIN   tbl_posto_fabrica USING (posto)
                WHERE vonder_nf_canc.cnpj = tbl_posto.cnpj
                AND tbl_posto_fabrica.fabrica = $fabrica";
        $qry = pg_query($con, $sql);
    }

    $sql = "DELETE FROM vonder_nf_item WHERE pedido is null;

            DELETE FROM vonder_nf
            USING tbl_faturamento
            WHERE vonder_nf.nota_fiscal    = tbl_faturamento.nota_fiscal
            AND   vonder_nf.serie          = tbl_faturamento.serie
            AND   tbl_faturamento.fabrica = $fabrica;

            DELETE FROM vonder_nf_item
            USING tbl_faturamento
            JOIN tbl_faturamento_item USING(faturamento)
            WHERE vonder_nf_item.nota_fiscal = tbl_faturamento.nota_fiscal
            AND   vonder_nf_item.serie       = tbl_faturamento.serie
            AND   vonder_nf_item.peca       = tbl_faturamento_item.peca
            AND   vonder_nf_item.pedido       = tbl_faturamento_item.pedido
            AND   tbl_faturamento.fabrica   = $fabrica";
    $deletes = pg_query($con, $sql);

    $sql = "DROP TABLE IF EXISTS vonder_nf_sem_itens;

            SELECT vonder_nf.* INTO vonder_nf_sem_itens
            FROM vonder_nf
            LEFT JOIN vonder_nf_item ON vonder_nf.nota_fiscal = vonder_nf_item.nota_fiscal
            WHERE vonder_nf_item.nota_fiscal IS NULL;

            DELETE FROM vonder_nf
            USING vonder_nf_sem_itens
            WHERE vonder_nf.nota_fiscal = vonder_nf_sem_itens.nota_fiscal
            AND   vonder_nf.serie       = vonder_nf_sem_itens.serie";
    $qry = pg_query($con, $sql);

    $sql = "INSERT INTO tbl_faturamento
            (
                fabrica     ,
                emissao     ,
                saida       ,
                transp      ,
                posto       ,
                total_nota  ,
                cfop        ,
                nota_fiscal ,
                serie
            )
                SELECT  $fabrica,
                        vonder_nf.txt_emissao::date         ,
                        vonder_nf.txt_emissao::date         ,
                        substring(vonder_nf.transp, 1,30),
                        vonder_nf.posto           ,
                        vonder_nf.total           ,
                        vonder_nf.cfop        ,
                        vonder_nf.nota_fiscal ,
                        vonder_nf.serie
                FROM vonder_nf
                LEFT JOIN tbl_faturamento ON  vonder_nf.nota_fiscal   = tbl_faturamento.nota_fiscal
                                            AND  vonder_nf.serie         = tbl_faturamento.serie
                                            AND  tbl_faturamento.fabrica      = $fabrica
                                            AND  tbl_faturamento.distribuidor IS NULL
                WHERE tbl_faturamento.faturamento IS NULL";
    $qry = pg_query($con, $sql);

    /*$sql = "ALTER TABLE vonder_nf_item ADD COLUMN faturamento INT4;
            ALTER TABLE vonder_nf_canc ADD COLUMN faturamento INT4";
    $qry = pg_query($con, $sql);*/

    $sql = "UPDATE vonder_nf_item
                SET faturamento = tbl_faturamento.faturamento
            FROM tbl_faturamento
            WHERE tbl_faturamento.fabrica     = $fabrica
            AND   tbl_faturamento.nota_fiscal = vonder_nf_item.nota_fiscal
            AND   tbl_faturamento.serie       = vonder_nf_item.serie";
    $qry = pg_query($con, $sql);

    $sql = "DELETE FROM vonder_nf_canc WHERE posto ISNULL;

            UPDATE vonder_nf_canc SET faturamento = tbl_faturamento.faturamento
                FROM   tbl_faturamento
            WHERE tbl_faturamento.fabrica = $fabrica
            AND   tbl_faturamento.nota_fiscal = vonder_nf_canc.nota_fiscal
            AND   tbl_faturamento.serie = vonder_nf_canc.serie
            AND   tbl_faturamento.posto = vonder_nf_canc.posto
            AND   tbl_faturamento.distribuidor IS NULL ";
    $qry = pg_query($con, $sql);

    $sql = "DELETE FROM vonder_nf_item WHERE faturamento IS NULL;
            DELETE FROM vonder_nf_canc WHERE faturamento IS NULL";
    $qry = pg_query($con, $sql);

    $sql = "DROP TABLE vonder_nf_item_sem_peca;

            SELECT * INTO vonder_nf_item_sem_peca
                FROM vonder_nf_item
            WHERE peca IS NULL;

            DELETE FROM vonder_nf_item WHERE peca IS NULL";
    $qry = pg_query($con, $sql);

    $sql = "UPDATE tbl_faturamento SET cancelada = vonder_nf_canc.cancelada
                FROM   vonder_nf_canc
            WHERE tbl_faturamento.faturamento = vonder_nf_canc.faturamento";
    $qry = pg_query($con, $sql);

    $sql = "SELECT DISTINCT faturamento,
                pedido     ,
                pedido_item,
                peca       ,
                qtde as qtde_fat,
                unitario   ,
                aliq_ipi   ,
                aliq_icms  ,
                valor_ipi  ,
                valor_icms ,
                base_icms  ,
                referencia_atendida,
                valor_impostos
        FROM vonder_nf_item";
    $qry = pg_query($con, $sql);

    $os_faturadas = array();

    while ($row = pg_fetch_assoc($qry)) {
        $pedido          = $row['pedido'];
        $pedido_item     = $row['pedido_item'];
        $faturamento     = $row['faturamento'];
        $peca            = $row['peca'];
        $qtde_fat        = $row['qtde_fat'];
        $unitario        = $row['unitario'];
        $aliq_ipi        = $row['aliq_ipi'];
        $valor_ipi       = $row['valor_ipi'];
        $valor_icms      = $row['valor_icms'];
        $base_icms       = $row['base_icms'];
        $aliq_icms       = $row['aliq_icms'];
        $txt_referencia  = $row['txt_referencia'];
        $valor_impostos  = $row['valor_impostos'];

        $sql = "INSERT INTO tbl_faturamento_item
                (
                    faturamento,
                    pedido     ,
                    pedido_item ,
                    peca       ,
                    qtde       ,
                    preco      ,
                    aliq_ipi   ,
                    valor_ipi  ,
                    valor_icms ,
                    aliq_icms  ,
                    base_icms  ,
                    valor_impostos
                )
                VALUES(
                    $faturamento,
                    $pedido     ,
                    $pedido_item,
                    $peca       ,
                    $qtde_fat   ,
                    $unitario   ,
                    $aliq_ipi   ,
                    $valor_ipi  ,
                    $valor_icms ,
                    $aliq_icms  ,
                    $base_icms  ,
                    $valor_impostos
                )";
        $ins_faturamento_item = pg_query($con, $sql);

        $sql_os = "SELECT os, referencia, descricao, nome
                    FROM tbl_os_item
                    JOIN tbl_peca using(peca)
                    JOIN tbl_os_produto using(os_produto)
                    JOIN tbl_os using(os)
                    JOIN tbl_posto using(posto)
                    WHERE pedido_item = $pedido_item
                    AND peca = $peca";
        $qry_os = pg_query($con, $sql_os);

        if (pg_num_rows($qry_os) > 0) {
            $os_faturada_os = pg_fetch_result($qry_os, 0, 'os');
            $os_faturada_peca = pg_fetch_result($qry_os, 0, 'referencia') . ' - ' . pg_fetch_result($qry_os, 0, 'descricao');
            $os_faturada_posto = pg_fetch_result($qry_os, 0, 'nome');

            $os_faturadas[$os_faturada_os]['posto'] = $os_faturada_posto;
            $os_faturadas[$os_faturada_os]['pecas'][] = $os_faturada_peca;
        }

        $sql = "SELECT qtde as qtde_pedido,
                        pedido_item
                FROM tbl_pedido_item
                WHERE pedido = $pedido
                    AND pedido_item  = $pedido_item
                    AND qtde > qtde_faturada
                LIMIT 1";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            while ($fetch = pg_fetch_assoc($res)) {
                $pedido_item = $fetch['pedido_item'];
                $qtde_pedido = $fetch['qtde_pedido'];

                $sql = "SELECT fn_atualiza_pedido_item(
                            $peca,
                            $pedido,
                            $pedido_item,
                            $qtde_fat
                        );";
                $qry_atualiza_pedido_item = pg_query($con, $sql);
            }
        }

        $sql = "SELECT fn_atualiza_status_pedido($fabrica, $pedido)";
        $qry_atualiza_status_pedido = pg_query($con, $sql);

        $sql_pedido_parcial = "SELECT pedido, posto
                        FROM tbl_pedido
                        JOIN tbl_tipo_pedido USING(tipo_pedido,fabrica)
                        WHERE tbl_pedido.pedido = {$pedido}
                        AND tbl_pedido.status_pedido = 5
                        AND tbl_tipo_pedido.pedido_faturado IS TRUE;";
       
        $res_pedido_parcial = pg_query($con, $sql_pedido_parcial);
        
        if(pg_num_rows($res_pedido_parcial) > 0){
            $pedido = pg_fetch_result($res_pedido_parcial, "pedido");
            $posto = pg_fetch_result($res_pedido_parcial, "posto");
            
            $titulo = '';
            $msg = '';

            $titulo .= "Pedido {$pedido} parcialmente Faturado.";
            $msg .= "Pedido {$pedido} parcialmente Faturado";
            $msg .= "<br>";
            $msg .= "<br>";
            $msg .= "Caso necessite dos itens com urgência, favor entrar em
            contato para obter informações da data de recebimento
            dos itens";
            $msg .= "<br>";
            $msg .= "<br>";
            $msg .= "Atenciosamente Grupo OVD";

            $paramentros_adicionais['pedido'] = $pedido;
            $paramentros_adicionais = json_encode($paramentros_adicionais);
            $insert_comunicado = "INSERT INTO tbl_comunicado(fabrica,posto,tipo,mensagem,parametros_adicionais,obrigatorio_site, ativo) VALUES($fabrica,$posto,'pedido_faturado_parcial','{$msg}','$paramentros_adicionais',TRUE, TRUE);";
            $res_comunicado = pg_query($con, $insert_comunicado);
            
            $sql_contato = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = $posto and fabrica = $fabrica";
            $res_contato = pg_query($con, $sql_contato);

            $contato_email = pg_fetch_result($res_contato, "contato_email");
            

            $res = $mailer->sendMail(
                $contato_email,
                $titulo,
                utf8_encode($msg),
                'noreply@tc.id'
            );
        }

        copy($arquivo, "{$arquivos}/faturamento_{$now}.txt");
        copy($arquivo_item, "{$arquivos}/faturamento_item_{$now}.txt");
        rename($arquivo, "{$destino}/faturamento_{$now}.txt");
        rename($arquivo_item, "{$destino}/faturamento_item_{$now}.txt");

        if (file_exists($arquivo_cancelado)) {
            copy($arquivo_cancelado, "{$arquivos}/faturamento_cancelado_{$now}.txt");
            rename($arquivo_cancelado, "{$destino}/faturamento_cancelado_{$now}.txt");
        }
    }

    if (!empty($os_faturadas)) {
        foreach ($os_faturadas as $key => $value) {
            $helper = new \Posvenda\Helpers\Os();

            $os = $key;

            $sql_contatos_consumidor = "
                SELECT consumidor_email,
                    consumidor_celular,
                    referencia,
                    descricao
                FROM tbl_os
                JOIN tbl_produto USING(produto)
                JOIN tbl_posto USING(posto)
                WHERE os = $os";
            $qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

            $consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
            $consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
            $produto = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');

            $pecas = implode(", ", $value["pecas"]);
            $posto = $value["posto"];

            $msg_consumidor = "Produto Vonder. Informamos que a(s) peÃ§a(s) $pecas para conserto do produto $produto foram enviada(s) para o posto $posto. Favor Aguardar.";

            if (!empty($consumidor_email)) {
                $helper->comunicaConsumidor($consumidor_email, $msg_consumidor);
            }

            if (!empty($consumidor_celular)) {
                $helper->comunicaConsumidor($consumidor_celular, $msg_consumidor, $fabrica);
            }
        }
    }

    $phpCron->termino();
} catch (Exception $e) {
    echo $e->getMessage();
}

