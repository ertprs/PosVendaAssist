<?php
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $msg_erro = "";
    $fabrica = "3";
    $origem  = "/www/cgi-bin/britania/entrada";
    //$origem  = "/home/felipe/public_html";
    $data    = date('Y-m-d-H');
    $arquivo = $origem."/faturamento_sem_pedidos.csv";
    $bkp_arquivo = "/tmp/britania/faturamento_sem_pedido-" . $data . ".txt.bkp";

    $msn_men        = "";
    $vet["fabrica"] = "britania";
    $vet["tipo"]    = "faturamento_sem_pedido";
    $vet["dest"][0] = "felipe.marttos@telecontrol.com.br";
    $vet["log"]     = 2;

    function msgErro($msg = "") {
        $hoje = date("d/m/Y - H:i:s");
        if (!empty($msg)) {
            $retorno .= "(" . $hoje . ")  " . $msg . "<br />";
            return $retorno;
        }
    }

    function trataData($data) {
        list($dia, $mes, $ano) = explode("/", $data);
        return $ano.'-'.$mes.'-'.$dia;
    }
    if (file_exists($arquivo)) {

        copy($arquivo, $bkp_arquivo);
        pg_query($con,"DROP TABLE IF EXISTS tmp_britania_fat_sem_pedido");
        $sql = "CREATE TEMP TABLE 
                    tmp_britania_fat_sem_pedido
                    (
                        posto integer, 
                        nome_posto varchar(100),
                        data_nota date,
                        estab varchar(20),
                        serie_saida varchar(20),
                        numero_nota_saida varchar(20),
                        cfop varchar(20),
                        peca integer,
                        descricao_item varchar(100),
                        qtde_saida double precision,
                        valor_devolucao double precision
                    );";

        $res = pg_query($con, $sql);
        $msn_men .= "criando temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        $conteudo = file_get_contents($arquivo);
        $conteudo_array = explode("\n", $conteudo);

        $i = 0;
        foreach ($conteudo_array as $linha) {
            $linha = trim($linha);
            if (!empty($linha)) {
                $colunas = explode("\t", $linha);
                $count   = count($colunas);
                if ($count > 2) {
                    list($codigo_posto, $nome_abv_posto, $nome_posto, $data_nota, $estab, $serie_saida,$numero_nota_saida, $cfop, $codigo_item, $descricao_item, $quantidade_saida, $numero_devolucao, $serie_devolucao, $data_devolucao, $quantidade_devolucao, $saldo, $valor_devolucao, $status, $classificacao_posto, $tipo_item) = explode("\t", $linha);


                    $data_nota = trataData(trim($data_nota));
                    $valor     = str_replace(",",".",$valor_devolucao);
                    $qtde      = str_replace(",",".",$quantidade_saida);

                    $sqlPosto = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '{$codigo_posto}' AND fabrica = {$fabrica}";
                    $msn_men .= "consulta posto fabrica: " . $sqlPosto ."<br /><br />";

                    $resPosto = pg_query($con, $sqlPosto);

                    if (strlen(pg_last_error()) > 0) {
                        $msg_erro .= msgErro(pg_last_error());
                    }

                    if (pg_num_rows($resPosto) > 0) {
                        $posto = pg_fetch_result($resPosto, 0, 'posto');
                    } else {
                        $msn_men .= "continue posto: " . $sqlPosto ."<br /><br />";

                        continue;
                    }

                    $sqlPD = "SELECT peca FROM tbl_peca  WHERE referencia = '{$codigo_item}' AND fabrica = {$fabrica}";
                    $msn_men .= "buca peca ou produto: " . $sqlPD ." --> $tipo_item<br /><br />";

                    $resPD = pg_query($con, $sqlPD);
                    if (strlen(pg_last_error($con)) > 0) {
                        $msg_erro .= msgErro(pg_last_error($con));
                    }

                    if (pg_num_rows($resPD) > 0) {
                        $peca = pg_fetch_result($resPD, 0, 'peca');
                    } else {
                        $msn_men .= "continue peca ou produto: " . $sqlPD ."<br /><br />";

                        continue;
                    }

                    if (strlen($posto) > 0 && strlen($data_nota) > 0 && strlen($estab) > 0 && strlen($serie_saida) > 0 && strlen($numero_nota_saida) > 0 && strlen($cfop) > 0 && strlen($peca) > 0 && strlen($qtde) > 0 && strlen($valor) > 0) {
                        $sql = "INSERT INTO
                                    tmp_britania_fat_sem_pedido
                                    (
                                        posto,
                                        nome_posto,
                                        data_nota,
                                        estab,
                                        serie_saida,
                                        numero_nota_saida,
                                        cfop,
                                        peca,
                                        descricao_item,
                                        qtde_saida,
                                        valor_devolucao
                                    ) VALUES (
                                        $posto,
                                        '$nome_posto',
                                        '$data_nota',
                                        '$estab',
                                        '$serie_saida',
                                        '$numero_nota_saida',
                                        '$cfop',
                                        '$peca',
                                        '$descricao_item',
                                        '$qtde',
                                        '$valor'
                                    )";
                        $msn_men .= "insert na temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";

                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            $msg_erro .= msgErro("Linha $i - Erro inesperado na importação!");
                        }

                    } else {
                        $msg_erro .= msgErro("Linha $i - Layout Incorreto, não importada!");
                    }

                } else {
                    $msg_erro .= msgErro("Linha $i - Layout Incorreto, não importada!");
                }
            }
            $i++;
        }

        $sql = "ALTER TABLE tmp_britania_fat_sem_pedido ADD COLUMN faturamento int4";
        $msn_men .= "ALTERa TABLE na temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        //Abastece a coluna faturamento 
        $sql = "UPDATE tmp_britania_fat_sem_pedido
                   SET faturamento = tbl_faturamento.faturamento 
                  FROM tbl_faturamento
                 WHERE tbl_faturamento.nota_fiscal = tmp_britania_fat_sem_pedido.numero_nota_saida
                   AND tbl_faturamento.serie   = tmp_britania_fat_sem_pedido.serie_saida
                   AND tbl_faturamento.fabrica = $fabrica";
        $msn_men .= "UPDATEs na temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        //Exclui itens que já estão faturados:
        $sql = "DELETE FROM tmp_britania_fat_sem_pedido USING tbl_faturamento_item
                      WHERE tbl_faturamento_item.faturamento = tmp_britania_fat_sem_pedido.faturamento
                        AND tbl_faturamento_item.peca        = tmp_britania_fat_sem_pedido.peca
                        AND tbl_faturamento_item.qtde        = tmp_britania_fat_sem_pedido.qtde_saida;";
        $msn_men .= "DELETEs na temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";
        
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        //inclui novos itens em faturamentos já existentes:
        $sql = "INSERT INTO tbl_faturamento_item(
                                                    faturamento,
                                                    peca,
                                                    qtde,
                                                    preco,
                                                    devolucao_obrig
                                                ) SELECT 
                                                    faturamento,
                                                    peca,
                                                    qtde_saida,
                                                    valor_devolucao,
                                                    't' 
                                                  FROM tmp_britania_fat_sem_pedido 
                                                 WHERE faturamento IS NOT NULL;";
        $msn_men .= "INSERTs na tbl_faturamento_item: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
           $msn_men .= ">>>tbl_faturamento_item: " . msgErro(pg_last_error()) ."<br /><br />";
      }

        //deixa apenas na tabela temporária os novos faturamentos:
        $sql = "DELETE FROM tmp_britania_fat_sem_pedido WHERE faturamento IS NOT NULL;";
        $msn_men .= "DELETEs na temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        //Inclui os novos faturamentos:
        $sql = "INSERT INTO tbl_faturamento(
                                                fabrica,
                                                emissao,
                                                saida,
                                                total_nota,
                                                posto,
                                                cfop,
                                                nota_fiscal,
                                                serie,
                                                filial
                                            ) SELECT 
                                                $fabrica,
                                                data_nota,
                                                data_nota,
                                                sum(valor_devolucao),
                                                posto,
                                                cfop,
                                                numero_nota_saida,
                                                serie_saida,
                                                estab 
                                              FROM tmp_britania_fat_sem_pedido 
                                          GROUP BY 
                                                data_nota,
                                                data_nota,
                                                posto,
                                                numero_nota_saida,
                                                cfop,
                                                estab,
                                                serie_saida;";
        $msn_men .= "INSERTs na temp tbl_faturamento: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        //Atualiza o campo faturamento da temp novamente:
        $sql = "UPDATE tmp_britania_fat_sem_pedido 
                   SET faturamento = tbl_faturamento.faturamento 
                  FROM tbl_faturamento
                 WHERE tbl_faturamento.nota_fiscal = tmp_britania_fat_sem_pedido.numero_nota_saida
                   AND tbl_faturamento.serie       = tmp_britania_fat_sem_pedido.serie_saida
                   AND tbl_faturamento.fabrica     = $fabrica;";
        $msn_men .= "UPDATEs na temp tmp_britania_fat_sem_pedido: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
        }

        //Inseri os itens para os novos faturamentos
        $sql = "INSERT INTO tbl_faturamento_item(
                                                    faturamento,
                                                    peca,
                                                    qtde,
                                                    preco,
                                                    devolucao_obrig
                                                ) 
                                                SELECT 
                                                    faturamento,
                                                    peca,
                                                    qtde_saida,
                                                    valor_devolucao,
                                                    true 
                                                FROM tmp_britania_fat_sem_pedido 
                                                WHERE faturamento IS NOT NULL;";
        $msn_men .= "INSERTs na tbl_faturamento_item: " . $sql ."<br /><br />";

        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
            $msg_erro .= msgErro(pg_last_error());
           $msn_men .= ">>><<<tbl_faturamento_item: " . msgErro(pg_last_error()) ."<br /><br />";
        }

    
    }
    if (strlen($msg_erro) > 0) {
        Log::envia_email($vet, "BRITÂNIA - importação de faturamento sem pedido", $msn_men);
    }

    if (strlen($msg_erro) > 0) {
        Log::envia_email($vet, "BRITÂNIA - Erro na importação de faturamento sem pedido", $msg_erro);
    }

