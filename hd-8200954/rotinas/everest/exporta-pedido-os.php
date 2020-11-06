<?php
/**
 *
 * exporta-pedido-os.php
 *
 * Exportação de pedidos
 *
 * @author  Thiago Tobias
 * @version 2017.07.28
 *
*/
error_reporting(E_ALL ^ E_NOTICE);

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

try {
    
    /*
    * Definições
    */
    $fabrica_nome   = "everest";
    $fabrica        = 94;

    $tipo_pedido_garantia = "211";
    $tipo_pedido_faturado = "207";
    $data    = date('Y-m-d-H-i');
    $env     = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

    $dir = "/tmp/everest/pedidos";

    if (!is_dir($dir)) {
      if (!mkdir($dir)) {
        throw new Exception('Erro ao criar diretório do fabricante.'."\n");
      }
      if (!chmod($dir, 0777)) {
        throw new Exception('Erro ao dar permissão ao diretório.'."\n");
      }
    }

    //system ("mkdir -m 777 $arquivos 2> /dev/null ; ");    

    $arquivo = "$dir/pedido-assist-$data.sdf";
    $erro = "$dir/exporta-pedido.erro";
    
    $fpArquivo = fopen($arquivo, 'w');

    if (!is_resource($fpArquivo)) {
        throw new Exception('Erro ao criar arquivo de exportação.'."\n");
    }

    $fpErro = fopen($erro, 'w');

    if (!is_resource($fpErro)) {
        throw new Exception('Erro ao criar arquivo de exportação.'."\n");
    }

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");    
    } else {
        $logClass->adicionaEmail("thiago.tobias@telecontrol.com.br");
    }

    $posto = $argv[1];
    if(strlen($posto) > 0){
        $cond = " AND tbl_posto.posto = $posto";
    }else{
        $cond = "";
    }

    //$res = pg_query($con, "BEGIN TRANSACTION");

    $sql = "SELECT fn_pedido_finaliza(tbl_pedido.pedido, $fabrica)
                FROM      tbl_pedido
                    JOIN      tbl_tipo_pedido      ON tbl_pedido.tipo_pedido   = tbl_tipo_pedido.tipo_pedido
                    JOIN      tbl_posto            ON tbl_pedido.posto         = tbl_posto.posto
                WHERE     tbl_pedido.fabrica                  = $fabrica
                    $cond
                    AND tbl_pedido.finalizado IS NOT NULL
                    AND tbl_pedido.tabela isnull
                    AND (
                            (
                                tbl_pedido.status_pedido = 1 
                                AND tbl_pedido.exportado IS NULL
                            ) OR (
                                tbl_pedido.exportado IS NOT NULL 
                                AND tbl_pedido.exportado >= CURRENT_TIMESTAMP - INTERVAL '7 DAYS'
                            )
                        );";
    $res = pg_query($con, $sql);

    $sql = "SELECT  trim(tbl_pedido.pedido::text) AS pedido,
                    trim(tbl_pedido.pedido_cliente) AS pedido_cliente,
                    '1'::char(1) AS p1,
                    '0'::char(1) AS p2,
                    to_char(current_date,'DD/MM/YYYY') AS dt1,
                    --date(now()) AS dt1,
                    to_char(current_date,'DD/MM/YYYY') AS dt2,
                    --date(now()) AS dt2,
                    replace(tbl_pedido.obs,E'\r\n','') AS obs,
                    tbl_posto_fabrica.codigo_posto AS posto,
                    tbl_posto_fabrica.codigo_posto AS posto_original,
                    tbl_posto_consumidor.codigo AS consumidor_codigo,
                    tbl_posto_consumidor.codigo AS consumidor_codigo_original,
                    tbl_posto.nome AS nome,
                    upper (tbl_posto.cidade) AS cidade,
                    upper (tbl_posto.estado) AS estado,
                    CASE WHEN tbl_pedido.representante IS NOT NULL 
                        THEN tbl_representante.codigo
                        ELSE '0'
                    END AS cod_repr,
                    trim(tbl_tabela.sigla_tabela) AS tabela,
                    trim(tbl_condicao.codigo_condicao::text) AS cond_pagto,
                    ''::char(1) AS nome_repr,
                    to_char(current_date,'DD/MM/YYYY') AS dt3,
                    --date(now()) AS dt3,
                    ''::char(1) AS ped_everest,
                    ''::char(1) AS descr_cond_pagto,
                    '001'::char(3) AS empresa,
                    tbl_pedido.tipo_frete AS tipo_trans,
                    CASE WHEN tbl_pedido.aprovacao_tipo IS NOT NULL 
                        THEN tbl_pedido.aprovacao_tipo::integer
                        ELSE tbl_transportadora_fabrica.codigo_interno::integer
                    END AS cod_trans,
                    --tbl_transportadora_fabrica.codigo_interno::integer AS cod_trans,
                    tbl_tipo_pedido.descricao AS tipo_pedido,
                    tbl_pedido.garantia_antecipada,
                    tbl_posto.suframa AS suframa,
                    CASE WHEN tbl_pedido.canal_venda IS NOT NULL 
                        THEN tbl_canal_venda.codigo  
                        ELSE ''
                    END AS cod_canal_venda,
                    ''::char(1) AS estabelecimento,
                    ''::char(1) AS agente_venda
                FROM tbl_pedido
                    JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
                    JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
                    LEFT JOIN tbl_posto_consumidor ON tbl_posto.posto = tbl_posto_consumidor.posto
                        AND   tbl_posto_consumidor.fabrica = $fabrica
                    LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                        AND   tbl_posto_fabrica.fabrica    = $fabrica
                    LEFT JOIN tbl_transportadora_fabrica ON tbl_pedido.transportadora = tbl_transportadora_fabrica.transportadora
                        AND  tbl_transportadora_fabrica.fabrica = $fabrica
                    LEFT JOIN tbl_representante ON tbl_pedido.representante = tbl_representante.representante
                    LEFT JOIN tbl_canal_venda ON tbl_pedido.canal_venda = tbl_canal_venda.canal_venda
                    JOIN tbl_tabela ON tbl_pedido.tabela = tbl_tabela.tabela
                    JOIN tbl_condicao  ON tbl_pedido.condicao = tbl_condicao.condicao
                WHERE tbl_pedido.fabrica = $fabrica
                    $cond
                    AND tbl_pedido.finalizado IS NOT NULL
                    AND (
                            (
                                tbl_pedido.status_pedido = 1
                                AND tbl_pedido.exportado IS NULL
                            ) OR (
                                tbl_pedido.exportado                IS NOT NULL
                                AND tbl_pedido.exportado >= CURRENT_TIMESTAMP - INTERVAL '7 DAYS'
                            )
                        )
                ORDER BY  tbl_pedido.pedido ";
    $res = pg_query($con, $sql);

    if (pg_last_error($con)) {
        
        fwrite($fpErro, "\r\n");
        fwrite($fpErro, "$sql");
        fwrite($fpErro, pg_last_error($con));
        fwrite($fpErro, "\r\n---------------------------------------------\r\n\r\n");
    }

    $footer_pedido_qtde_item = 0;
    $footer_pedido_qtde_peca = 0;
    $footer_pedido_pedido    = "0";
    $footer_pedido_posto     = "0";

    $footer_qtde_pedido =  pg_num_rows($res);
    $footer_qtde_item   = 0;
    $footer_qtde_peca   = 0;

    if (pg_num_rows($res) > 0) {

        for ($i=0; $i < $footer_qtde_pedido; $i++) { 

            $pedido                     = pg_fetch_result($res, $i, 'pedido');
            $pedido_cliente             = pg_fetch_result($res, $i, 'pedido_cliente');
            $p1                         = pg_fetch_result($res, $i, 'p1');
            $p2                         = pg_fetch_result($res, $i, 'p2');
            $dt1                        = pg_fetch_result($res, $i, 'dt1');
            $dt2                        = pg_fetch_result($res, $i, 'dt2');
            $obs                        = pg_fetch_result($res, $i, 'obs');
            $posto                      = pg_fetch_result($res, $i, 'posto');
            $posto_original             = pg_fetch_result($res, $i, 'posto_original');
            $consumidor_codigo          = pg_fetch_result($res, $i, 'consumidor_codigo');
            $consumidor_codigo_original = pg_fetch_result($res, $i, 'consumidor_codigo_original');
            $nome                       = pg_fetch_result($res, $i, 'nome');
            $cidade                     = pg_fetch_result($res, $i, 'cidade');
            $uf                         = pg_fetch_result($res, $i, 'estado');
            $cod_repr                   = pg_fetch_result($res, $i, 'cod_repr');
            $tabela                     = pg_fetch_result($res, $i, 'tabela');
            $cond_pagto                 = pg_fetch_result($res, $i, 'cond_pagto');
            $nome_repr                  = pg_fetch_result($res, $i, 'nome_repr');
            $dt3                        = pg_fetch_result($res, $i, 'dt3');
            $ped_everest                = pg_fetch_result($res, $i, 'ped_everest');
            $descr_cond_pagto           = pg_fetch_result($res, $i, 'descr_cond_pagto');
            $empresa                    = pg_fetch_result($res, $i, 'empresa');
            $tipo_trans                 = pg_fetch_result($res, $i, 'tipo_trans');
            $cod_trans                  = pg_fetch_result($res, $i, 'cod_trans');
            $tipo_pedido                = pg_fetch_result($res, $i, 'tipo_pedido');
            $suframa                    = pg_fetch_result($res, $i, 'suframa');
            $garantia_antecipada        = pg_fetch_result($res, $i, 'garantia_antecipada');
            $cod_canal_venda            = pg_fetch_result($res, $i, 'cod_canal_venda');
            $estabelecimento            = pg_fetch_result($res, $i, 'estabelecimento');
            $agente_venda               = pg_fetch_result($res, $i, 'agente_venda');

            if (strlen($posto) == 0) {
                $posto          = $consumidor_codigo;
                $posto_original = $consumidor_codigo_original;
            }

            if (strlen($uf) == 0) {
                $uf = " ";
            }

            #---------- Rodape do Pedido ------------
            if ($footer_pedido_pedido == 0) {
                $footer_pedido_pedido = $pedido;
                $footer_pedido_posto  = $posto;
            }

            if ($pedido != $footer_pedido_pedido) {
                fwrite($fpArquivo, "#;");

                if ($tipo_pedido == "Garantia" ) {
                    fwrite($fpArquivo, $footer_pedido_pedido);
                } else {                    
                    fwrite($fpArquivo, $footer_pedido_pedido);
                }

                fwrite($fpArquivo, ";");
                fwrite($fpArquivo, $footer_pedido_qtde_item);
                fwrite($fpArquivo, ";");
                fwrite($fpArquivo, $footer_pedido_qtde_peca);                
                fwrite($fpArquivo, ";");
                fwrite($fpArquivo, $footer_pedido_posto);
                fwrite($fpArquivo, "\r\n");

                $footer_pedido_pedido    = $pedido ;
                $footer_pedido_posto     = $posto ;
                $footer_pedido_qtde_item = 0 ;
                $footer_pedido_qtde_peca = 0 ;
            }

            $preco = "0";

            $sql = "SELECT  trim (tbl_peca.referencia)      AS peca              ,
                        tbl_pedido_item.qtde            AS qtde              ,
                        REPLACE(TO_CHAR(tbl_pedido_item.preco, '999999D999'),',','') AS preco ,
                        ' '::char(1)                    AS sua_os            ,
                        ' '::char(1)                    AS abertura          ,
                        ' '::char(1)                    AS serie             ,
                        ' '::char(1)                    AS produto           ,
                        ' '::char(1)                    AS consumidor_revenda,
                        tbl_pedido_item.pedido_item     AS pedido_item       ,
                        CASE WHEN tbl_peca.devolucao_obrigatoria IS TRUE THEN 'YES'
                        ELSE 'NO'
                        END                             AS devolucao_obrigatoria,
                        tbl_tabela.sigla_tabela         AS tabela_item,
                        REPLACE(TO_CHAR(tbl_pedido_item.acrescimo_tabela_base, '99999D99'),',',',') AS desconto,
                        tbl_peca.voltagem                                     
                FROM    tbl_pedido_item 
                JOIN    tbl_peca     ON tbl_pedido_item.peca   = tbl_peca.peca
                LEFT JOIN tbl_tabela ON tbl_pedido_item.tabela = tbl_tabela.tabela
                WHERE   tbl_pedido_item.pedido        = $pedido
                AND     tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada
                ORDER BY tbl_peca.referencia";
            $resItem = pg_query($con, $sql);

            if (pg_last_error($con)) {
                fwrite($fpErro, "\r\n");
                fwrite($fpErro, "$sql");
                fwrite($fpErro, pg_last_error($con));
                fwrite($fpErro, "\r\n---------------------------------------------\r\n\r\n");
            }

            $sql = "UPDATE tbl_pedido SET status_pedido = 2
                    WHERE  tbl_pedido.pedido       = $pedido
                    AND    tbl_pedido.distribuidor IS NULL;";
            $res0 = pg_query($con, $sql);

            if (pg_last_error($con)) {
                fwrite($fpErro, "\r\n");
                fwrite($fpErro, "$sql");
                fwrite($fpErro, pg_last_error($con));
                fwrite($fpErro, "\r\n---------------------------------------------\r\n\r\n");
            }

            $sql = "UPDATE tbl_pedido 
                    SET exportado = current_timestamp
                    WHERE  tbl_pedido.pedido = $pedido 
                        AND    tbl_pedido.exportado IS NULL 
                    ";
            $res0 = pg_query($con, $sql);
            #print "sql: $sql";
            if (pg_last_error($con)) {
                fwrite($fpErro, "\r\n");
                fwrite($fpErro, "$sql");
                fwrite($fpErro, pg_last_error($con));
                fwrite($fpErro, "\r\n---------------------------------------------\r\n\r\n");
            }
            
            #----- Pedidos em Garantia via DISTRIBUIDOR ---------#
            $tabela_distribuidor = "";

            $sql = "SELECT  tbl_posto_fabrica.codigo_posto AS posto,
                    tbl_posto.nome,
                    tbl_posto.cidade,
                    tbl_posto.estado AS uf ,
                    tbl_tabela.sigla_tabela AS tabela_distribuidor
                FROM tbl_pedido
                JOIN tbl_posto            ON tbl_pedido.distribuidor = tbl_posto.posto
                JOIN tbl_posto_fabrica    ON tbl_pedido.distribuidor = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
                LEFT JOIN tbl_posto_linha ON tbl_pedido.distribuidor = tbl_posto_linha.posto   AND tbl_pedido.linha          = tbl_posto_linha.linha
                LEFT JOIN tbl_tabela      ON tbl_posto_linha.tabela  = tbl_tabela.tabela
                WHERE tbl_pedido.pedido      = $pedido
                AND   tbl_pedido.fabrica     = $fabrica
                AND   tbl_pedido.tipo_pedido = $tipo_pedido_garantia";
            $res0 = pg_query($con, $sql);

            if (pg_last_error($con)) {
                fwrite($fpErro, "\r\n");
                fwrite($fpErro, "$sql");
                fwrite($fpErro, pg_last_error($con));
                fwrite($fpErro, "\r\n---------------------------------------------\r\n\r\n");
            }

            if (pg_num_rows($res0) > 0) {

                $posto = pg_fetch_result($res0, 0, posto);
                $nome = pg_fetch_result($res0, 0, nome);
                $cidade = pg_fetch_result($res0, 0, cidade);
                $uf = pg_fetch_result($res0, 0, uf);
                $tabela_distribuidor = pg_fetch_result($res0, 0, tabela_distribuidor);

                $footer_pedido_posto = $posto;
            }

            for ($j=0; $j < pg_num_rows($resItem); $j++) { 
                $peca                  = pg_fetch_result($resItem, $j, 'peca');
                $num_ped_everest       = pg_fetch_result($resItem, $j, 'pedido_item');
                $qtde                  = pg_fetch_result($resItem, $j, 'qtde');
                $preco                 = pg_fetch_result($resItem, $j, 'preco');
                $sua_os                = pg_fetch_result($resItem, $j, 'sua_os');
                $produto               = pg_fetch_result($resItem, $j, 'produto');
                $serie                 = pg_fetch_result($resItem, $j, 'serie');
                $abertura              = pg_fetch_result($resItem, $j, 'abertura');
                $consumidor_revenda    = pg_fetch_result($resItem, $j, 'consumidor_revenda');
                $devolucao_obrigatoria = pg_fetch_result($resItem, $j, 'devolucao_obrigatoria');
                $tabela_item           = pg_fetch_result($resItem, $j, 'tabela_item');
                $desconto              = pg_fetch_result($resItem, $j, 'desconto');
                $voltagem              = pg_fetch_result($resItem, $j, 'voltagem');

                if ($desconto < 0) {
                    $desconto = $desconto *-1;
                }

                #HD 40324
                if (strlen($tabela_item)>0) {
                    $tabela = $tabela_item;
                }

                if ( empty($consumidor_revenda)) {
                    $consumidor_revenda = "C";
                }
                
                if ($consumidor_revenda == 'R') {
                    $c_r = 'REVENDA';
                }else{
                    $c_r = 'NORMAL';
                }
                fwrite($fpArquivo, "$pedido;"); #c-nr-pedcli
                if ($p1 == '1') {
                    fwrite($fpArquivo, "$p1;");                    
                } else { 
                    fwrite($fpArquivo, ";");
                }#c-operacao - Não sei o que é mas deve ser alguma coisa interan da Telecontrol

                if ($p2 == '0') { 
                    fwrite($fpArquivo, "$p2;");
                } else { 
                    fwrite($fpArquivo, ";");
                }#c-desc-padrao       - ???, esta vindo com valor ZERO, apesar de ser um campo STRING
                
                fwrite($fpArquivo, "$dt1;");#c-dt-implantacao    - data implantação do pedido
                fwrite($fpArquivo, "$dt2;");#c-dt-entrega        - data entrega (pode ser igual ao da implantação
                fwrite($fpArquivo, "$obs;");#c-observacao
                fwrite($fpArquivo, "$posto;");#i-cod-emitente      - codigo do post
                fwrite($fpArquivo, "$nome;");#c-nome-posto
                fwrite($fpArquivo, "$cod_repr;");#c-cod-rep           - codigo representante, pode ser sempre ZERO

                #NÃO ENVIAR TABELA DE PREÇO SE FOR SERVIÇO
                fwrite($fpArquivo, "$tabela;");#c-nr-tabpre         - tabela de preço

                fwrite($fpArquivo, "$cond_pagto;");#c-cod-cond-pag      - condição de pagamento
                fwrite($fpArquivo, "$nome_repr;");#c-nome-repres       - nome representante, pode vir vazio
                fwrite($fpArquivo, "$dt3;");#c-dt-emissao        - data emissão
                fwrite($fpArquivo, "$ped_everest;"); #c-nr-pedido         -  esta vindo vazio
                fwrite($fpArquivo, "$descr_cond_pagto;");#c-desc-condicao     - vazio
                fwrite($fpArquivo, "$empresa;");#c-cod-empresa       -
                
                if($tipo_trans == 'CIF'){
                    fwrite($fpArquivo, "1;");#c-tp-transp         - 1-cif 2-fob defaulf cif
                }

                if($tipo_trans == 'FOB'){
                    fwrite($fpArquivo, "2;");#c-tp-transp         - 1-cif 2-fob defaulf cif
                }

                if($tipo_trans == ''){
                    fwrite($fpArquivo, " ;");#c-tp-transp         - 1-cif 2-fob defaulf cif
                }

                if ($cod_trans == '') {
                    $cod_trans = '';
                }

                fwrite($fpArquivo, "$cod_trans;");#c-cod-transp        -
                fwrite($fpArquivo, "$num_ped_everest;");#c-nr-pedido1        - tbl_pedido_item.pedido_item
                fwrite($fpArquivo, "$peca;");#c-it-codigo         - codigo do item
                fwrite($fpArquivo, "$qtde;");#de-quantidade       - quantidade
                fwrite($fpArquivo, " ;");#c-natur-tc          - ? 

                fwrite($fpArquivo, "$preco;");#de-valor            - valor

                if ($tipo_pedido) {                   #c-tab-finan         - tabela financiamento
                    if ($tipo_pedido == "Faturado") {
                        fwrite($fpArquivo, ";");
                    }else{
                        fwrite($fpArquivo, ";");
                    }
                    
                    if ($tipo_pedido == "Garantia") {
                        if($devolucao_obrigatoria == "YES"){
                            $tipo_pedido = "Garantia"; # combinado com o Alessandor Baccin para imprimir NF com outro CFOP difenten de garantia sem devolução obrigatória (enviado no email do Samuel)
                                                    # Troca é garantia com retorno obrigatorio para everest
                            fwrite($fpArquivo, "$tipo_pedido;");
                        }else{
                            fwrite($fpArquivo, "$tipo_pedido;");#c-fatura            - fatura, garantia ou troca
                        }
                    } else {
                        fwrite($fpArquivo, "$tipo_pedido;");#c-fatura            - fatura, garantia ou troca
                    }

                }else {
                    fwrite($fpArquivo, ";");
                    fwrite($fpArquivo, ";");
                }

                fwrite($fpArquivo, "$c_r;");#c-revenda           -
                fwrite($fpArquivo, "$sua_os;");#c-OS                    - O.S.
                fwrite($fpArquivo, "$produto;");#c-item-pai        - ?
                fwrite($fpArquivo, "$serie;");#c-nr-serie          -
                fwrite($fpArquivo, "$abertura;");#c-data-os           - Data da OS
                fwrite($fpArquivo, "$posto_original;");#i-cod-posto-origem
                #print ARQUIVO $tabela_distribuidor . ";"; #c-branco
                fwrite($fpArquivo, "$voltagem;");#c-branco
                if ($devolucao_obrigatoria) {
                    fwrite($fpArquivo, "$devolucao_obrigatoria;");#c-retorno
                }else{
                    fwrite($fpArquivo, "NO;");
                }
                fwrite($fpArquivo, "$desconto;");#c-branco
                fwrite($fpArquivo, "$cod_canal_venda;");#
                fwrite($fpArquivo, "$estabelecimento;");#
                fwrite($fpArquivo, "$agente_venda;");#

                $footer_qtde_item += 1 ;
                $footer_qtde_peca += $qtde ;

                $footer_pedido_qtde_item += 1 ;
                $footer_pedido_qtde_peca += $qtde ;

                fwrite($fpArquivo, "\r\n");
            }
        }
    }

    #------------- Footer do Arquivo ------------------

    fwrite($fpArquivo, "#;");
    fwrite($fpArquivo, "$footer_pedido_pedido;");
    fwrite($fpArquivo, "$footer_pedido_qtde_item;");
    fwrite($fpArquivo, "$footer_pedido_qtde_peca;");
    fwrite($fpArquivo, "$footer_pedido_posto");
    fwrite($fpArquivo, "\r\n");

    fwrite($fpArquivo, "*;");
    fwrite($fpArquivo, "$footer_qtde_pedido;");
    fwrite($fpArquivo, "$footer_qtde_item;");
    fwrite($fpArquivo, "$footer_qtde_peca");
    fwrite($fpArquivo, "\r\n");

    fclose($fpArquivo);
    fclose($fpErro);

     #########################################################################
    #  Copia para area de FTP da everest somente se o arquivo tiver conteudo  #
     #########################################################################
    if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
        copy($arquivo, "/home/everest/telecontrol-everest/pedido-assist-$data.sdf");
        copy($arquivo, "/home/everest/telecontrol-everest/bkp/pedido-assist-$data.sdf");
        system ("chown everest:clientes /home/everest/telecontrol-everest/pedido-assist-$data.sdf");
    }

    ##########################################################
    #               Gerando email de erros                   #
    ##########################################################
    if (file_exists($erro) and (filesize($erro) > 0)) {

        $msg_erro = "Não foi possível gerar a exportacao de pedidos. \n\n\n Favor verificar URGENTE \n";        

        $logClass->adicionaLog(array("titulo" => 'EVEREST - Falha na exportação dos pedidos ' . ucfirst($fabrica))); // Titulo
        $logClass->adicionaLog($msg_erro);

        if ($logClass->enviaEmails() == "199") {
          echo "Log de erro enviado com Sucesso!";
        } else {
          echo $logClass->enviaEmails();
        }
    }
    //$res = pg_query($con, "ROLLBACK TRANSACTION");
    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}