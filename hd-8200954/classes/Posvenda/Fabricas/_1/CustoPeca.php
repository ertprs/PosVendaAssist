<?php 

class CustoPeca
{
    private $os;
    private $fabrica;
    private $posto;
    private $conn;

    function __construct($os, $fabrica, $posto, $con){

        $this->os = $os;
        $this->fabrica = $fabrica;
        $this->posto = $posto;
        $this->conn = $con;        
    }

    function getMObra($os, $troca = false){
        $sql_os = "SELECT tbl_os.produto, solucao_os, tbl_produto.linha 
                    FROM tbl_os 
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto 
                    WHERE os = $os ";
        $res_os = pg_query($this->conn, $sql_os);
        if(pg_num_rows($res_os)>0){
            $produto = pg_fetch_result($res_os, 0, produto);
            $linha = pg_fetch_result($res_os, 0, linha);
            if($troca == true){
                $solucao_os = 111;
            }else{
                $solucao_os = pg_fetch_result($res_os, 0, solucao_os);
            }

            $sql_excecao = "SELECT mao_de_obra 
                            FROM tbl_excecao_mobra 
                            where fabrica = $this->fabrica 
                            AND solucao =$solucao_os
                            AND linha = $linha
                            AND posto = $this->posto ";
            $res_excecao = pg_query($this->conn, $sql_excecao);
            if(pg_num_rows($res_excecao) == 0){
                $sql_excecao = "SELECT mao_de_obra
                            FROM tbl_excecao_mobra 
                            where fabrica = $this->fabrica 
                            AND solucao =$solucao_os
                            AND linha = $linha
                            AND posto is null ";
                $res_excecao = pg_query($this->conn, $sql_excecao);
            }
            if(pg_num_rows($res_excecao) > 0){
                $mao_de_obra        = pg_fetch_result($res_excecao, 0, "mao_de_obra");
            }else{
                $sql = "SELECT mao_de_obra FROM tbl_produto WHERE produto = $produto AND fabrica_i = $this->fabrica";
                $res = pg_query($this->conn, $sql);
                if(pg_num_rows($res)>0){
                    $mao_de_obra        = pg_fetch_result($res, 0, mao_de_obra);
                }else{
                    $mao_de_obra        = 0;
                }
            }

            $sqlTx = "SELECT tx_administrativa from tbl_excecao_mobra WHERE posto = $this->posto and fabrica = $this->fabrica AND mao_de_obra is null and linha is null and produto is null";
            $resTx = pg_query($this->conn, $sqlTx);

            if(pg_num_rows($resTx)> 0){
                $tx_administrativa  = pg_fetch_result($resTx, 0, 'tx_administrativa');
            }else{
                $tx_administrativa  = 0;    
            }
        }

        $dados['mao_de_obra'] = $mao_de_obra;
        $dados['tx_administrativa'] = $tx_administrativa;

        return $dados ; 
    }

    function getCustoPeca(){

        $sql_itens = "SELECT tbl_os.os, tbl_produto.produto, tbl_posto_fabrica.reembolso_peca_estoque,tbl_tabela_item.preco as custo_peca, tbl_os_item.qtde, tbl_produto.linha, tbl_produto.valores_adicionais
            FROM tbl_os
            inner join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $this->fabrica 
            inner join tbl_produto ON tbl_produto.produto = tbl_os.produto
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto 
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
            
            INNER JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca                 AND tbl_tabela_item.tabela = 1053

            WHERE tbl_os.os = $this->os and tbl_os.fabrica = $this->fabrica 
            AND tbl_os_item.servico_realizado in (62,90)";
        $res_itens = pg_query($this->conn, $sql_itens);
		if(pg_num_rows($res_itens) > 0) {
        $custo_peca_total = 0;
        for($i=0; $i<pg_num_rows($res_itens); $i++){
            $custo_peca = pg_fetch_result($res_itens, $i, custo_peca);
            $qtde      = pg_fetch_result($res_itens, $i, qtde);
            $linha      = pg_fetch_result($res_itens, $i, linha);
            $produto    = pg_fetch_result($res_itens, $i, produto);
            $valores_adicionais = json_decode(pg_fetch_result($res_itens, $i, valores_adicionais), true);
            $reembolso_peca_estoque = pg_fetch_result($res_itens, $i, reembolso_peca_estoque);

            $custo_peca_total += $custo_peca * $qtde;
        }

            $dadosMobra = $this->getMObra($this->os);
            $txAdm = $dadosMobra['tx_administrativa'];
            $Mobra = $dadosMobra['mao_de_obra'];

            $total_custo    = $custo_peca_total + $Mobra + $txAdm;
            $total_custo    = number_format($total_custo, 2, '.', '');
            $medioCr        = number_format($valores_adicionais['medioCR'], 2, '.', '');

            $dados['txAdm'] = $txAdm;
            $dados['Mobra'] = $Mobra;
            $dados['valor_pecas'] = $custo_peca_total;
            $dados['medioCr'] = $medioCr;
            $dados['custo_pecas'] = $total_custo;
            $dados['linha'] = $linha;
            $dados['reembolso_peca_estoque'] = $reembolso_peca_estoque;
            $dados['produto'] = $produto;

			return $dados;
		}else{
			return false;
		}

    }

    function GravarCampoExtra($dadosCusto, $custo_produto){

        $sql_verifica_campos_extra = "SELECT os FROM tbl_os_campo_extra WHERE os = ". $this->os ." and fabrica = ". $this->fabrica;
        $res_verifica_campos_extra = pg_query($this->conn, $sql_verifica_campos_extra);
        if(pg_num_rows($res_verifica_campos_extra)> 0){

            $campos_adicionais = json_decode(pg_fetch_result($res_verifica_campos_extra, 0, "campos_adicionais"), true);
            $campos_adicionais["total_custo_peca"] = number_format($dadosCusto['custo_pecas'], 2, '.', '');
            $campos_adicionais["total_produto"] = number_format($custo_produto['total_produto'], 2, '.', '');
            $campos_adicionais["multiplicador"] = $custo_produto['multiplicador'];
            $campos_adicionais["Mobra_produto"] = number_format($custo_produto['Mobra_produto'], 2, '.', '');
            $campos_adicionais["txAdm"] = number_format($dadosCusto['txAdm'], 2, '.', '');
            $campos_adicionais["Mobra"] = number_format($dadosCusto['Mobra'], 2, '.', '');
            $campos_adicionais["medioCr"] = number_format($dadosCusto['medioCr'], 2, '.', '');
            $campos_adicionais["valor_pecas"] = number_format($dadosCusto['valor_pecas'], 2, '.', '');
            $campos_adicionais = json_encode($campos_adicionais);

            $sql_campo_extra = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais' WHERE os = ". $this->os ." and fabrica = ". $this->fabrica;
        }else{            
            $campos_adicionais["total_custo_peca"]  = number_format($dadosCusto['custo_pecas'], 2, '.', '');
            $campos_adicionais["total_produto"]     = number_format($custo_produto['total_produto'], 2, '.', '');
            $campos_adicionais["txAdm"]             = number_format($dadosCusto['txAdm'], 2, '.', '');
            $campos_adicionais["Mobra"]             = number_format($dadosCusto['Mobra'], 2, '.', '');
            $campos_adicionais["medioCr"]           = number_format($dadosCusto['medioCr'], 2, '.', '');
            $campos_adicionais["multiplicador"] = $custo_produto['multiplicador'];
            $campos_adicionais["Mobra_produto"] = number_format($custo_produto['Mobra_produto'], 2, '.', '');
            $campos_adicionais["valor_pecas"] = number_format($dadosCusto['valor_pecas'], 2, '.', '');

            $campos_adicionais                      = json_encode($campos_adicionais);

            $sql_campo_extra = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES (".$this->os.", ". $this->fabrica .", '$campos_adicionais')";
        }

        $res_campo_extra = pg_query($this->conn, $sql_campo_extra);

        if(strlen(pg_last_error($this->conn))>0){
            $erro = pg_last_error($this->conn);
        }
        return $erro;
    }

    function GravarAuditoria($os, $custo_pecas){

        $sql_verifica = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = ". $os ." and fabrica = ". $this->fabrica;
        $res_verifica = pg_query($this->conn, $sql_verifica);
        if(pg_num_rows($res_verifica) > 0){
            $campos_adicionais = json_decode(pg_fetch_result($res_verifica, 0, "campos_adicionais"), true);
            $campos_adicionais["total_custo_peca"] = number_format($custo, 2, '.', '');
        }

        if($custo_pecas > $campos_adicionais["total_custo_peca"]){
            $sql = "SELECT auditoria_os from tbl_auditoria_os where os = $os AND auditoria_status = 4 and liberada is null and reprovada is null and cancelada is null";
            $res = pg_query($this->conn, $sql);
            if(pg_num_rows($res)==0){
                $sql_auditoria = "INSERT INTO tbl_auditoria_os(os, auditoria_status, observacao, bloqueio_pedido) VALUES ($os, 4, 'OS em auditoria de peça Valor Reparo X Troca', 't') ";
            }          
            $res_auditoria = pg_query($this->conn, $sql_auditoria);
            if(strlen(pg_last_error($this->conn))>0){
                $erro = pg_last_error($this->conn);
            }                
        }            
        return $erro;
    }

    function RetiraAuditoria($os){
        $sql = "DELETE FROM tbl_auditoria_os 
                WHERE os = $os 
                AND auditoria_status = 4 ";
        $res = pg_query($this->conn, $sql);
        if(strlen(pg_last_error($this->conn))>0){
            $erro = pg_last_error($this->conn);
        } 
        return $erro;
    }
}

?>
