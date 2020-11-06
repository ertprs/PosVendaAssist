<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$os       = $_GET['os'];

if (strlen($os) > 0) {

    if ($login_fabrica == 203) {
        $sql = "SELECT  tbl_os.os,
                        tbl_os.data_digitacao,
                        tbl_os.data_abertura,
                        tbl_os.data_fechamento,
                        tbl_os.posto,
                        tbl_os.consumidor_nome           AS nome_razao,
                        tbl_os.consumidor_cpf            AS consumidor_cpf_cnpj,
                        tbl_os.consumidor_celular        AS consumidor_telefone1,
                        tbl_os.consumidor_fone_comercial AS consumidor_telefone2,
                        tbl_os.consumidor_endereco,
                        tbl_os.consumidor_complemento,
                        tbl_os.consumidor_cep,
                        tbl_os.consumidor_bairro,
                        tbl_os.revenda_cnpj,
                        tbl_os.serie,
                        tbl_os.defeito_reclamado,
                        tbl_os.defeito_constatado,
                        tbl_os.aparencia_produto AS aparencia,
                        tbl_revenda.nome        AS revenda_nome,
                        tbl_revenda.cep         AS revenda_cep,
                        tbl_revenda.endereco    AS revenda_endereco,
                        tbl_revenda.numero      AS revenda_numero,
                        tbl_revenda.complemento AS revenda_complemento,
                        tbl_revenda.bairro      AS revenda_bairro,
                        tbl_produto.referencia  AS produto_codigo,
                        tbl_produto.descricao   AS produto_descricao,
                        tbl_servico.descricao AS servico
                    FROM tbl_os 
                    LEFT JOIN tbl_revenda       ON tbl_revenda.revenda      = tbl_os.revenda 
                    LEFT JOIN tbl_os_produto    ON tbl_os_produto.os        = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
                    LEFT JOIN tbl_produto       ON tbl_produto.produto      = tbl_os_produto.produto
                    LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_os.posto AND tbl_posto_fabrica.fabrica = 167
                    LEFT JOIN tbl_posto         ON tbl_posto.posto          = tbl_posto_fabrica.posto
                    LEFT JOIN tbl_servico       ON tbl_servico.servico      = tbl_os_produto.servico AND tbl_os_produto.os = tbl_os.os
                WHERE tbl_os.os = '$os'
                AND tbl_os.fabrica = 167";
    } else {
    $sql = "SELECT  tbl_mondial_os.os,
                        tbl_mondial_os.data_digitacao,
                        tbl_mondial_os.data_abertura,
                        tbl_mondial_os.data_fechamento,
                        tbl_mondial_os.status,
                        tbl_mondial_os.sigla,
                        tbl_mondial_os.necessita_peca,
                        tbl_mondial_os.posto,
                        tbl_mondial_os.nome_razao,
                        tbl_mondial_os.consumidor_cpf_cnpj,
                        tbl_mondial_os.consumidor_telefone1,
                        tbl_mondial_os.consumidor_telefone2,
                        tbl_mondial_os.consumidor_endereco,
                        tbl_mondial_os.consumidor_complemento,
                        tbl_mondial_os.consumidor_cep,
                        tbl_mondial_os.consumidor_bairro,
                        tbl_mondial_os.consumidor_cidade,
                        tbl_mondial_os.consumidor_uf,
                        tbl_mondial_os.revenda_cnpj,
                        tbl_mondial_os.revenda_nome,
                        tbl_mondial_os.revenda_cep,
                        tbl_mondial_os.revenda_endereco,
                        tbl_mondial_os.revenda_numero,
                        tbl_mondial_os.revenda_complemento,
                        tbl_mondial_os.revenda_bairro,
                        tbl_mondial_os.revenda_cidade,
                        tbl_mondial_os.revenda_uf,
                        tbl_mondial_os.cor_pai_dsc,
                        tbl_mondial_os_produto.produto_item,
                        tbl_mondial_os_produto.produto_codigo,
                        tbl_mondial_os_produto.produto_descricao,
                        tbl_mondial_os_produto.nota_fiscal,
                        tbl_mondial_os_produto.data_compra,
                        tbl_mondial_os_produto.aparencia,
                        tbl_mondial_os_produto.serie,
                        tbl_mondial_os_produto.defeito_reclamado,
                        tbl_mondial_os_produto.defeito_constatado,
                        tbl_mondial_os_produto.data_inclusao,
                        tbl_mondial_os_produto.servico
                    FROM tbl_mondial_os 
                    LEFT JOIN tbl_mondial_os_produto ON tbl_mondial_os_produto.os = tbl_mondial_os.os
                WHERE tbl_mondial_os.os='$os'";
//                echo nl2br($sql);die;
    }
    $resMondial = pg_query($con,$sql);
}

$layout_menu = "callcenter";
$title = "DETALHE DA ORDEM DE SERVIÇO";
include_once "cabecalho_new.php";

    function nao_null($el) {
        if (strtoupper($el) == 'NULL')
            return  '';
        return $el;
    }
    
    if (pg_num_rows($resMondial) > 0) {
        $row = pg_fetch_assoc($resMondial);
        $row = array_map('utf8_decode', $row);
        $row = array_map('nao_null',$row);

        if (!empty($row['data_digitacao'])) {
            $data_digitacao   = explode(" ", $row['data_digitacao']);
            list($y_d,$m_d,$d_d) = explode("-", $data_digitacao[0]);
            $data_digitacao      = "$d_d/$m_d/$y_d";
        } else {
            $data_digitacao = '';
        }

        if (!empty($row['data_abertura'])) {
            $data_abertura   = explode(" ", $row['data_abertura']);
            list($y_a,$m_a,$d_a) = explode("-", $data_abertura[0]);
            $data_abertura      = "$d_a/$m_a/$y_a";
        } else {
            $data_abertura = '';
        }

        if (!empty($row['data_fechamento'])) {
            $data_fechamento   = explode(" ", $row['data_fechamento']);
            list($y_f,$m_f,$d_f) = explode("-", $data_fechamento[0]);
            $data_fechamento      = "$d_f/$m_f/$y_f";
        } else {
            $data_fechamento = '';
        }

        $os                 = $row['os'];

        $endereco           = $row['consumidor_endereco'] . " " . $row['consumidor_complemento'];
?>
<style type="text/css">
    .titulo_coluna{
        background-color: #FFFFFF;
        color: #000000;
    }
    .txt_center{
        text-align: center !important;
    }
</style>
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Ordem de Serviço</td>
            </tr>
            <tr>
                <td width="150" class="tac" style="font-size:26px; font-weight:bold; color:orange;">
                    <?php echo $os;?>
                </td>
                <td class='titulo_coluna' width="100">Data Digitação</td>
                <td><?php echo $data_digitacao;?></td>
                <td class='titulo_coluna' width="100">Data Abertura</td>
                <td><?php echo $data_abertura;?></td>
                <td class='titulo_coluna' width="100">Data Fechamento</td>
                <td><?php echo $data_fechamento;?></td>
            </tr> 
            <tr>
                <td class='titulo_coluna' width="100">Necessita de Peça</td>
                <td colspan="2"><?php echo $row['necessita_peca'];?></td>
                <td class='titulo_coluna' width="100">Status</td>
                <td><?php echo $row['status'];?></td>
                <td class='titulo_coluna' width="100">Sigla</td>
                <td><?php echo $row['sigla'];?></td>
            </tr> 
        </table>
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Posto</td>
            </tr>
            <tr>
                <td class='titulo_coluna'>Posto</td>
                <td nowrap><?php echo $row['posto'];?></td>
                <td class='titulo_coluna'>Posto Filial</td>
                <td nowrap><?php echo $row['posto_filial'];?></td>      
            </tr>
        </table>
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações da Revenda</td>
            </tr>
             <tr>
                <td class='titulo_coluna'>Revenda</td>
                <td nowrap><?php echo $row['revenda_nome'];?></td>
                <td class='titulo_coluna'>CNPJ</td>
                <td><?php echo $row['revenda_cnpj'];?></td>           
            </tr>
            <tr>    
                <td class='titulo_coluna'>Endereço</td>
                <td ><?php echo $row['revenda_endereco'] . ', ' . $row['revenda_numero'] . ' - ' . $row['revenda_complemento'];?></td>          
                <td class='titulo_coluna'>CEP</td>
                <td colspan='4'><?php echo $row['revenda_cep'];?></td>               
            </tr>
            <tr>    
                <td class='titulo_coluna'>Bairro</td>
                <td ><?php echo $row['revenda_bairro'];?></td>           
                <td class='titulo_coluna'>Cidade</td>
                <td><?php echo $row['revenda_cidade'];?></td>    
                <td class='titulo_coluna'>Estado</td>
                <td><?php echo $row['revenda_uf'];?></td>            
            </tr>
            <tr>
                <td colspan="6"><?php echo $row['cor_pai_dsc'];?></td>
            </tr>
        </table>
        <?php



        if ($login_fabrica == 203) {
            $sqlMondialProdutos = "SELECT  
                                tbl_produto.produto AS produto_item,
                                tbl_produto.referencia AS produto_codigo,
                                tbl_produto.descricao AS produto_descricao,
                                tbl_os.nota_fiscal,
                                tbl_os.aparencia_produto AS aparencia,
                                tbl_os_produto.serie,
                                tbl_os_produto.defeito_reclamado,
                                tbl_os_produto.defeito_constatado,
                                tbl_os_produto.data_input AS data_inclusao,
                                tbl_servico.descricao AS servico
                        FROM tbl_os 
                        LEFT JOIN tbl_os_produto    ON tbl_os_produto.os        = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
                        LEFT JOIN tbl_produto       ON tbl_produto.produto      = tbl_os_produto.produto
                        LEFT JOIN tbl_servico       ON tbl_servico.servico      = tbl_os_produto.servico AND tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os.os = {$row['os']}
                    AND tbl_os.fabrica = 167";
        } else {
        $sqlMondialProdutos = "SELECT  
                                produto_item,
                                produto_codigo,
                                produto_descricao,
                                nota_fiscal,
                                data_compra,
                                aparencia,
                                serie,
                                defeito_reclamado,
                                defeito_constatado,
                                data_inclusao,
                                servico
                        FROM tbl_mondial_os_produto 
                    WHERE os='".$row['os']."'";
                    //echo nl2br($sqlMondialProdutos);die;
        }
        $resMondialProdutos = pg_query($con,$sqlMondialProdutos);

        if (pg_num_rows($resMondialProdutos) > 0) {
            while ($rowProduto = pg_fetch_array($resMondialProdutos)) {
                $rowProduto = array_map('utf8_decode', $rowProduto);
                $rowProduto = array_map('nao_null',$rowProduto);

                if (!empty($rowProduto['data_compra'])) {
                    $data_compra   = explode(" ", $rowProduto['data_compra']);
                    list($y,$m,$d) = explode("-", $data_compra[0]);
                    $data_compra      = "$d/$m/$y";
                } else {
                    $data_compra = '';
                }

                if (!empty($rowProduto['data_inclusao'])) {
                    $data_inclusao = explode(" ", $rowProduto['data_inclusao']);
                    list($y,$m,$d) = explode("-", $data_inclusao[0]);
                    $data_inclusao      = "$d/$m/$y";
                } else {
                    $data_inclusao = '';
                }

                $produto = $rowProduto['produto_codigo'] . " - " . $rowProduto['produto_descricao'];
        ?>            

        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
            </tr>
            <tr>
                <td class='titulo_coluna'>Item Produto</td>
                <td><?php echo $rowProduto['produto_item'];?></td>
                <td class='titulo_coluna'>Produto</td>
                <td colspan='5'><?php echo $produto;?></td>
            </tr>
            <tr>
                <td class='titulo_coluna' nowrap>Nota Fiscal</td>
                <td><?php echo $rowProduto['nota_fiscal'];?></td> 
                <td class='titulo_coluna'>Série</td>
                <td><?php echo $rowProduto['serie'];?></td>
                <td class='titulo_coluna' nowrap>Data da Compra</td>
                <td><?php echo $data_compra?></td>          
                <td class='titulo_coluna'>Data da Inclusão</td>
                <td ><?php echo $data_inclusao;?></td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Aparência</td>
                <td colspan='7'><?php echo $rowProduto['aparencia'];?></td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Serviço</td>
                <td colspan='7'><?php echo $rowProduto['servico'];?></td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Extra 1</td>
                <td colspan='7'><?php echo $rowProduto['field13'];?></td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Extra 2</td>
                <td colspan='7'><?php echo $rowProduto['field14'];?></td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Defeito Reclamado</td>
                <td colspan='7'><?php echo $rowProduto['defeito_reclamado'];?></td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Defeito Constatado</td>
                <td colspan='7'><?php echo $rowProduto['defeito_constatado'];?></td>
            </tr>
        </table>
        <?php }}?>
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
            </tr>
            <tr>
                <td class='titulo_coluna'>Cliente</td>
                <td nowrap><?php echo $row['nome_razao'];?></td>
                <td class='titulo_coluna'>CPF/CNPJ</td>
                <td><?php echo $row['consumidor_cpf_cnpj'];?></td>           
            </tr>
            <tr>
                <td class='titulo_coluna'>E-mail</td>
                <td nowrap><?php echo $row['consumidor_email'];?></td>
                <td class='titulo_coluna'>Telefone 1</td>
                <td nowrap colspan='3'><?php echo $row['consumidor_telefone1'];?></td>
                <td class='titulo_coluna'>Telefone 2</td>
                <td nowrap><?php echo $row['consumidor_telefone2'];?></td>
            </tr>
            <tr>    
                <td class='titulo_coluna'>Endereço</td>
                <td ><?php echo $endereco;?></td>          
                <td class='titulo_coluna'>CEP</td>
                <td colspan="3"><?php echo $row['consumidor_cep'];?></td>               
                <td class='titulo_coluna'>Bairro</td>
                <td><?php echo $row['consumidor_bairro'];?></td>           
            </tr>
            <tr>    
                <td class='titulo_coluna'>Cidade</td>
                <td><?php echo $row['consumidor_cidade'];?></td>    
                <td class='titulo_coluna'>Estado</td>
                <td colspan="3"><?php echo $row['consumidor_uf'];?></td>            
                <td class='titulo_coluna'>Pais</td>
                <td><?php echo $row['consumidor_pais'];?></td>            
            </tr>
        </table>
        
    <?php
        if ($login_fabrica == 203) {
            $sqlItem = "SELECT
                             tbl_produto.produto,
                             tbl_peca.peca,
                             tbl_peca.referencia AS codigo_peca,
                             tbl_peca.descricao AS descricao_peca,
                             tbl_os_item.qtde,
                             tbl_os_item.defeito_descricao AS descricao_defeito,
                             tbl_produto.descricao AS produto_descricao
                        FROM tbl_os
                        LEFT JOIN tbl_revenda       ON tbl_revenda.revenda      = tbl_os.revenda 
                        LEFT JOIN tbl_os_produto    ON tbl_os_produto.os        = tbl_os.os AND tbl_os_produto.produto = tbl_os.produto
                        LEFT JOIN tbl_os_item       ON tbl_os_item.os_produto   = tbl_os_produto.os_produto
                        LEFT JOIN tbl_peca          ON tbl_peca.peca            = tbl_os_item.peca AND tbl_peca.fabrica IN (203,167)
                        LEFT JOIN tbl_produto       ON tbl_produto.produto      = tbl_os_produto.produto
                        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_os.posto AND tbl_posto_fabrica.fabrica = 167
                        LEFT JOIN tbl_posto         ON tbl_posto.posto          = tbl_posto_fabrica.posto
                        LEFT JOIN tbl_servico       ON tbl_servico.servico      = tbl_os_produto.servico AND tbl_os_produto.os = tbl_os.os
                        WHERE tbl_os.os    = '$os'
                        AND tbl_os.fabrica = 167";
        } else {
        $sqlItem = "SELECT   tbl_mondial_os_item.produto,
                             tbl_mondial_os_item.peca,
                             tbl_mondial_os_item.codigo_peca,
                             tbl_mondial_os_item.descricao_peca,
                             tbl_mondial_os_item.qtde,
                             tbl_mondial_os_item.descricao_defeito,
                             tbl_mondial_os_item.data_inclusao,
                             tbl_mondial_os_item.data_transmissao,
                             tbl_mondial_os_item.status,
                             tbl_mondial_os_produto.produto_descricao
                    FROM tbl_mondial_os_item 
                    LEFT JOIN tbl_mondial_os_produto ON 
                    tbl_mondial_os_item.produto::integer=tbl_mondial_os_produto.produto_item
                    AND
                    tbl_mondial_os_item.os=tbl_mondial_os_produto.os
                    WHERE tbl_mondial_os_item.os='$os'";
                   #echo nl2br($sqlItem);die;
        }
        $resItemOS = pg_query($con,$sqlItem);

        if(pg_num_rows($res) > 0){

            echo "<table align='center' class='table table-bordered table-large' >
                    <tr>
                        <td class='titulo_tabela tac' colspan='100%'>Peças Solicitadas</td>
                    </tr>
                    <tr>
                        <td class='titulo_coluna txt_center' width='70'>Data Inc.</td>
                        <td class='titulo_coluna txt_center' width='70'>Data Trans.</td>
                        <td class='titulo_coluna'>Produto</td>
                        <td class='titulo_coluna txt_center' width='80'>Cód. Peça</td>
                        <td class='titulo_coluna'>Descrição da Peça</td>
                        <td class='titulo_coluna'>Defeito</td>
                        <td class='titulo_coluna txt_center' width='40'>Peça</td>
                        <td class='titulo_coluna txt_center' width='40'>Qtde</td>
                        <td class='titulo_coluna txt_center' width='80'>Status</td>
                    </tr>";

                while($rowItem = pg_fetch_assoc($resItemOS)) {
                $rowItem = array_map('nao_null',$rowItem);
                
                if (!empty($rowItem['data_inclusao'])) {
                    $data_inclusao = explode(" ", $rowItem['data_inclusao']);
                    list($y,$m,$d) = explode("-", $data_inclusao[0]);
                    $data_inclusao      = "$d/$m/$y";
                } else {
                    $data_inclusao = '';
                }

                if (!empty($rowItem['data_transmissao'])) {
                    $data_transmissao = explode(" ", $rowItem['data_transmissao']);
                    list($y,$m,$d) = explode("-", $data_transmissao[0]);
                    $data_transmissao      = "$d/$m/$y";
                } else {
                    $data_transmissao = '';
                }

                echo "<tr>
                        <td class='txt_center'>".$data_inclusao."</td>
                        <td class='txt_center'>".$data_transmissao."</td>
                        <td>".utf8_decode($rowItem['produto_descricao'])."</td>
                        <td class='txt_center'>".$rowItem['codigo_peca']."</td>
                        <td>".utf8_decode($rowItem['descricao_peca'])."</td>
                        <td>".utf8_decode($rowItem['descricao_defeito'])."</td>
                        <td class='txt_center'>".$rowItem['peca']."</td>
                        <td class='txt_center'>".$rowItem['qtde']."</td>
                        <td class='txt_center'>".$rowItem['status']."</td>
                      </tr>";
            }
            echo "</table>";
        }
    }


/* Rodapé */
    include 'rodape.php';
?>
