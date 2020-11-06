<?php

error_reporting(E_ALL ^ E_NOTICE);

$fabrica_nome = "rinnai";

define('APP', 'Exporta Pedido - '.$fabrica_nome);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $login_fabrica = 129;
    $vet['fabrica'] = 'rinnai';
    $vet['tipo']    = 'exporta-pedido';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 1;

    $sql = "SELECT  trim(tbl_pedido.pedido::text)   AS pedido          ,
                    trim(tbl_pedido.pedido_cliente)    AS pedido_cliente  ,
                    '1'::char(1)                       AS p1              ,
                    '0'::char(1)                       AS p2              ,
                    to_char(current_date,'DD-MM-YYYY') AS dt1             ,
                    to_char(current_date,'DD-MM-YYYY') AS dt2             ,
                    replace(tbl_pedido.obs,E'\r\n','')    AS obs          ,
                    tbl_posto_fabrica.codigo_posto     AS posto           ,
                    tbl_posto_fabrica.codigo_posto     AS posto_original  ,
                    tbl_posto.nome                     AS nome            ,
                    upper (tbl_posto.cidade)           AS cidade          ,
                    upper (tbl_posto.estado)           AS estado          ,
                    trim(tbl_tabela.sigla_tabela)      AS tabela          ,
                    trim(tbl_condicao.codigo_condicao::text) AS cond_pagto,
                    ''::char(1)                        AS nome_repr       ,
                    to_char(current_date,'DD-MM-YYYY') AS dt3             ,
                    ''::char(1)                        AS ped_gelopar     ,
                    ''::char(1)                        AS descr_cond_pagto,
                    '001'::char(3)                     AS empresa         ,
                    tbl_pedido.tipo_frete              AS tipo_trans      ,
                    '1'                        AS cod_trans       ,
                    tbl_tipo_pedido.descricao          AS tipo_pedido     ,
                    tbl_tipo_pedido.codigo             AS codigo     ,
                    tbl_pedido.tipo_pedido             AS id_tipo_pedido,
                    tbl_pedido.garantia_antecipada                        ,
                    tbl_posto.suframa                  AS suframa         ,
                    '001'::char(3)                        AS estabelecimento ,
					''::char(1)                        AS agente_venda,
					tbl_pedido.troca
                  FROM      tbl_pedido
                  JOIN      tbl_tipo_pedido      ON tbl_pedido.tipo_pedido   = tbl_tipo_pedido.tipo_pedido
                  JOIN      tbl_posto            ON tbl_pedido.posto         = tbl_posto.posto
                  LEFT JOIN tbl_posto_fabrica    ON tbl_posto.posto          = tbl_posto_fabrica.posto
                    AND   tbl_posto_fabrica.fabrica    = $login_fabrica
                  LEFT JOIN tbl_transportadora_fabrica ON tbl_pedido.transportadora = tbl_transportadora_fabrica.transportadora
                    AND  tbl_transportadora_fabrica.fabrica = $login_fabrica
                   JOIN      tbl_tabela           ON tbl_pedido.tabela        = tbl_tabela.tabela
                  JOIN      tbl_condicao         ON tbl_pedido.condicao      = tbl_condicao.condicao
                  WHERE     tbl_pedido.fabrica                  = $login_fabrica
                  $cond
                  AND       tbl_pedido.finalizado               IS NOT NULL
                  AND       tbl_pedido.posto <> 6359
                  AND       ( (tbl_pedido.status_pedido            = 1
                        AND       tbl_pedido.exportado                IS NULL
                  ) OR (
                    tbl_pedido.exportado                IS NOT NULL
                    AND       tbl_pedido.data >= CURRENT_TIMESTAMP - INTERVAL '7 DAYS'
                  ))
                  ORDER BY  tbl_pedido.pedido ";

    $res      = pg_query($con, $sql);
    $numrows  = pg_num_rows($res);
    $msg_erro = pg_errormessage($con);
    $data     = date('Y-m-d');

    if (!empty($msg_erro)) {
        throw new Exception($msg_erro);
    }

    if ($numrows) {

        $footer_pedido_qtde_item = 0;
        $footer_pedido_qtde_peca = 0;
        $footer_pedido_pedido    = "0";
        $footer_pedido_posto     = "0";

        $footer_qtde_pedido = $numrows;
        $footer_qtde_item   = 0;
        $footer_qtde_peca   = 0;

        $dir = "/tmp/$fabrica_nome/posvenda/pedidos";

        if (!is_dir($dir)) {

            if (!mkdir($dir)) {
                throw new Exception('Erro ao criar diretório do fabricante.'."\n");
            }

            if (!chmod($dir, 0777)) {
                throw new Exception('Erro ao dar permissão ao diretório.'."\n");
            }

        }

        $file = $dir.'/pedido.txt';
        $fp   = fopen($file, 'w');

        if (!is_resource($fp)) {
            throw new Exception('Erro ao criar arquivo de exportação.'."\n");
        }

        for ($i = 0; $i < $numrows; $i++) {

            $pedido                     = pg_fetch_result($res, $i,'pedido');
            $pedido_cliente             = pg_fetch_result($res, $i,'pedido_cliente');
            $p1                         = pg_fetch_result($res, $i,'p1');
            $p2                         = pg_fetch_result($res, $i,'p2');
            $dt1                        = pg_fetch_result($res, $i,'dt1');
            $dt2                        = pg_fetch_result($res, $i,'dt2');
            $obs                        = pg_fetch_result($res, $i,'obs');
            $posto                      = pg_fetch_result($res, $i,'posto');
            $posto_original             = pg_fetch_result($res, $i,'posto_original');
            $nome                       = pg_fetch_result($res, $i,'nome');
            $cidade                     = pg_fetch_result($res, $i,'cidade');
            $uf                         = pg_fetch_result($res, $i,'estado');
            $tabela                     = pg_fetch_result($res, $i,'tabela');
            $cond_pagto                 = pg_fetch_result($res, $i,'cond_pagto');
            $nome_repr                  = pg_fetch_result($res, $i,'nome_repr');
            $dt3                        = pg_fetch_result($res, $i,'dt3');
            $ped_gelopar                = pg_fetch_result($res, $i,'ped_gelopar');
            $descr_cond_pagto           = pg_fetch_result($res, $i,'descr_cond_pagto');
            $empresa                    = pg_fetch_result($res, $i,'empresa');
            $tipo_trans                 = pg_fetch_result($res, $i,'tipo_trans');
            $cod_trans                  = pg_fetch_result($res, $i,'cod_trans');
            $tipo_pedido                = pg_fetch_result($res, $i,'tipo_pedido');
            $codigo                     = pg_fetch_result($res, $i,'codigo');
            $id_tipo_pedido             = pg_fetch_result($res, $i,'id_tipo_pedido');
            $suframa                    = pg_fetch_result($res, $i,'suframa');
            $garantia_antecipada        = pg_fetch_result($res, $i,'garantia_antecipada');
            $cod_canal_venda            = pg_fetch_result($res, $i,'cod_canal_venda');
            $estabelecimento            = pg_fetch_result($res, $i,'estabelecimento');
            $agente_venda               = pg_fetch_result($res, $i,'agente_venda');
            $troca                      = pg_fetch_result($res, $i,'troca');


            if (strlen ($uf) == 0) {
              $uf = " ";
            }

            $preco = "0";
			
            #----- Pedidos em Garantia via DISTRIBUIDOR ---------#
            $tabela_distribuidor = "";

            $sql = "SELECT  tbl_posto_fabrica.codigo_posto AS posto,
                tbl_posto.nome,
                tbl_posto.cidade,
                tbl_posto.estado AS uf ,
                tbl_tabela.sigla_tabela AS tabela_distribuidor
              FROM tbl_pedido
              JOIN tbl_posto            ON tbl_pedido.distribuidor = tbl_posto.posto
              JOIN tbl_posto_fabrica    ON tbl_pedido.distribuidor = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              LEFT JOIN tbl_posto_linha ON tbl_pedido.distribuidor = tbl_posto_linha.posto   AND tbl_pedido.linha          = tbl_posto_linha.linha
              LEFT JOIN tbl_tabela      ON tbl_posto_linha.tabela  = tbl_tabela.tabela
              WHERE tbl_pedido.pedido      = $pedido
              AND   tbl_pedido.fabrica     = $login_fabrica
              AND   tbl_pedido.tipo_pedido = $id_tipo_pedido";
            $res0 = pg_query($con, $sql);

            $msg_erro  = pg_errormessage($con);

            if (pg_num_rows($res0) > 0) {
              $posto                = pg_fetch_result($res0, 0, 'posto');
              $nome                 = pg_fetch_result($res0, 0, 'nome');
              $cidade               = pg_fetch_result($res0, 0, 'cidade');
              $uf                   = pg_fetch_result($res0, 0, 'uf');
              $tabela_distribuidor  = pg_fetch_result($res0, 0, 'tabela_distribuidor');
              $footer_pedido_posto  = $posto;
            }

            $sql_up = "UPDATE tbl_pedido
                              SET exportado     = CURRENT_TIMESTAMP,
                                  status_pedido = 2
                            WHERE pedido        = $pedido
                              AND fabrica       = $login_fabrica
                              AND exportado     IS NULL ";

            $res_up   = pg_query($con, $sql_up);
            $msg_erro = pg_errormessage($con);

            $sql_pecas = "SELECT  trim (tbl_peca.referencia)      AS peca              ,
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

            $res_pecas = pg_query($con, $sql_pecas);
            $tot_pecas = pg_num_rows($res_pecas);
            $msg_erro  = pg_errormessage($con);

            if (!empty($msg_erro)) {
                throw new Exception($msg_erro);
            }

			#---------- Rodape do Pedido ------------
            if ($footer_pedido_pedido == 0) {
              $footer_pedido_pedido = $pedido;
              $footer_pedido_posto  = $posto;
            }

            if ($pedido != $footer_pedido_pedido) {
              fwrite($fp, '#;');
              fwrite($fp, $footer_pedido_pedido);
              fwrite($fp, ';');
              fwrite($fp, $footer_pedido_qtde_item) ;
              fwrite($fp, ';');
              fwrite($fp, $footer_pedido_qtde_peca) ;
              fwrite($fp, ';');
              fwrite($fp, $footer_pedido_posto) ;
              fwrite($fp, "\n");

              $footer_pedido_pedido    = $pedido ;
              $footer_pedido_posto     = $posto ;
              $footer_pedido_qtde_item = 0 ;
              $footer_pedido_qtde_peca = 0 ;
            }


            for ($x = 0; $x < $tot_pecas; $x++) {

                $peca                  = pg_fetch_result($res_pecas, $x,'peca');
                $num_ped_gelopar       = pg_fetch_result($res_pecas, $x,'pedido_item');
                $qtde                  = pg_fetch_result($res_pecas, $x,'qtde');
                $preco                 = pg_fetch_result($res_pecas, $x,'preco');
                $sua_os                = pg_fetch_result($res_pecas, $x,'sua_os');
                $produto               = pg_fetch_result($res_pecas, $x,'produto');
                $serie                 = pg_fetch_result($res_pecas, $x,'serie');
                $abertura              = pg_fetch_result($res_pecas, $x,'abertura');
                $consumidor_revenda    = pg_fetch_result($res_pecas, $x,'consumidor_revenda');
                $devolucao_obrigatoria = pg_fetch_result($res_pecas, $x,'devolucao_obrigatoria');
                $tabela_item           = pg_fetch_result($res_pecas, $x,'tabela_item');
                $desconto              = pg_fetch_result($res_pecas, $x,'desconto');
                $voltagem              = pg_fetch_result($res_pecas, $x,'voltagem');

                if (strlen($tabela_item)>0) {
                  $tabela = $tabela_item;
                }

                if ( ! $consumidor_revenda) {
                  $consumidor_revenda = "C";
                }

                if ($consumidor_revenda == 'R') {
                  $c_r = 'REVENDA';
                }else{
                  $c_r = 'NORMAL';
                }

                fwrite($fp,$pedido . ";"); #c-nr-pedcli
                if ($p1 == '1') { fwrite($fp,$p1 . ";"); } else { fwrite($fp,";"); }#c-operacao - Não sei o que é mas deve ser alguma coisa interan da Telecontrol
                if ($p2 == '0') { fwrite($fp,$p2 . ";"); } else { fwrite($fp,";"); }#c-desc-padrao       - ???, esta vindo com valor ZERO, apesar de ser um campo STRING
                fwrite($fp,$dt1              . ";"); #c-dt-implantacao    - data implantação do pedido
                fwrite($fp,$dt2              . ";"); #c-dt-entrega        - data entrega (pode ser igual ao da implantação
                fwrite($fp,$obs              . ";"); #c-observacao
                fwrite($fp,$posto            . ";"); #i-cod-emitente      - codigo do post
                fwrite($fp,$nome             . ";"); #c-nome-posto
                fwrite($fp,$cod_repr         . ";"); #c-cod-rep           - codigo representante, pode ser sempre ZERO

                #NÃO ENVIAR TABELA DE PREÇO SE FOR SERVIÇO
                if ($peca == '9909505') {
                  fwrite($fp, " ;");
                }else{
                  fwrite($fp,$tabela           . ";"); #c-nr-tabpre         - tabela de preço
                }

                fwrite($fp,$cond_pagto       . ";"); #c-cod-cond-pag      - condição de pagamento
                fwrite($fp,$nome_repr        . ";"); #c-nome-repres       - nome representante, pode vir vazio
                fwrite($fp,$dt3              . ";"); #c-dt-emissao        - data emissão
                fwrite($fp,$ped_gelopar      . ";"); #c-nr-pedido         -  esta vindo vazio
                fwrite($fp,$descr_cond_pagto . ";"); #c-desc-condicao     - vazio
                fwrite($fp,$empresa          . ";"); #c-cod-empresa       -

                if($tipo_trans == 'CIF'){
                  fwrite($fp,"1;"); #c-tp-transp         - 1-cif 2-fob defaulf cif
                }

                if($tipo_trans == 'FOB'){
                  fwrite($fp,"2;"); #c-tp-transp         - 1-cif 2-fob defaulf cif
                }

                if($tipo_trans == ''){
                  fwrite($fp," ;"); #c-tp-transp         - 1-cif 2-fob defaulf cif
                }


                fwrite($fp,$cod_trans .       ";"); #c-cod-transp        -
                fwrite($fp,$num_ped_gelopar . ";"); #c-nr-pedido1        - tbl_pedido_item.pedido_item
                fwrite($fp,$peca             . ";"); #c-it-codigo         - codigo do item
                fwrite($fp,$qtde             . ";"); #de-quantidade       - quantidade
                fwrite($fp," ;");                    #c-natur-tc          - ?

                fwrite($fp,$preco . ";");  #de-valor            - valor

                if ($tipo_pedido) {                   #c-tab-finan         - tabela financiamento
                  if ($tipo_pedido == "Faturado") {
                    fwrite($fp,";");
                  }else{
                    fwrite($fp,";");
                  }

                  if ($tipo_pedido == "Garantia") {
                    if($devolucao_obrigatoria == "YES"){
                      $tipo_pedido = "Faturado"; # combinado com o Alessandor Baccin para imprimir NF com outro CFOP difenten de garantia sem devolução obrigatória (enviado no email do Samuel)
                                  # Troca é garantia com retorno obrigatorio para GELOPAR
                      fwrite($fp,$tipo_pedido . ";");
					}else{
						if ($troca == 't') {
								$tipo_pedido = "Faturado";
						}
                      fwrite($fp,$tipo_pedido . ";"); #c-fatura            - fatura, garantia ou troca
                    }
                  } else {
                      fwrite($fp,$tipo_pedido . ";"); #c-fatura            - fatura, garantia ou troca
                  }

                }else {
                  fwrite($fp,";");
                  fwrite($fp,";");
                }
                fwrite($fp,$c_r                 . ";"); #c-revenda           -
                fwrite($fp,$sua_os              . ";"); #c-OS                    - O.S.
                fwrite($fp,$produto             . ";"); #c-item-pai        - ?
                fwrite($fp,$serie               . ";"); #c-nr-serie          -
                fwrite($fp,$abertura            . ";"); #c-data-os           - Data da OS
                fwrite($fp,$posto_original      . ";"); #i-cod-posto-origem
                #print ARQUIVO $tabela_distribuidor . ";"; #c-branco
                fwrite($fp,$voltagem            . ";"); #c-branco
                if ($devolucao_obrigatoria) {
                  fwrite($fp,$devolucao_obrigatoria . ";"); #c-retorno
                }else{
                  fwrite($fp,"NO" . ";");
                }
                fwrite($fp,$desconto. ";"); #c-branco
                fwrite($fp,$cod_canal_venda. ";"); #
                fwrite($fp,$estabelecimento. ";"); #
                fwrite($fp,$agente_venda. ";"); #

                $footer_qtde_item += 1 ;
                $footer_qtde_peca += $qtde ;

                $footer_pedido_qtde_item += 1 ;
                $footer_pedido_qtde_peca += $qtde ;

                fwrite($fp,"\n");

            }

        }

        #------------- Footer do Arquivo ------------------

        fwrite($fp,"#;");
        fwrite($fp,$footer_pedido_pedido);
        fwrite($fp,";");
        fwrite($fp,$footer_pedido_qtde_item);
        fwrite($fp,";");
        fwrite($fp,$footer_pedido_qtde_peca);
        fwrite($fp,";");
        fwrite($fp,$footer_pedido_posto);
        fwrite($fp,"\n");

        fwrite($fp,"*;");
        fwrite($fp,$footer_qtde_pedido);
        fwrite($fp,";");
        fwrite($fp,$footer_qtde_item);
        fwrite($fp,";");
        fwrite($fp,$footer_qtde_peca);
        fwrite($fp,"\n");

        if (!empty($msg_erro)) {

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);

        } else {

            Log::log2($vet, APP . ' - Executado com Sucesso - ' . date('d-m-Y H:i:s'));

        }

	fclose($fp);
	$sql    = "SELECT to_char(current_timestamp, 'YYYY-MM-DD-HH24-MI')";
	$result = pg_query($con,$sql);
	$data= pg_fetch_result($result,0,0);

	if (file_exists($file) and (filesize($file) > 0)) {

		date_default_timezone_set('America/Sao_Paulo');
		$data_arquivo = date('dmyHi');

		$destino = "/home/rinnai/posvenda/telecontrol-$fabrica_nome/pedido-assist-$data.sdf";

		copy($file, $dir . '/pedido-assist' . $data_arquivo . '.txt');
		rename($file, $destino);

	}

    }

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

