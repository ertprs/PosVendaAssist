<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';

$os       = $_GET['os'];

if (strlen($os) > 0) {

	$sql = "SELECT tbl_os.os, 
		tbl_os.sua_os,
		TO_CHAR(tbl_os.data_digitacao,  'DD/MM/YYYY HH24:MI:SS') AS data_digitacao,
		TO_CHAR(tbl_os.data_abertura,   'DD/MM/YYYY') AS data_abertura,
		TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
		TO_CHAR(tbl_os.finalizada,      'DD/MM/YYYY') AS finalizada,
		tbl_os.tecnico,
		tbl_tipo_atendimento.codigo                   AS codigo_atendimento,
		tbl_tipo_atendimento.descricao                AS nome_atendimento,
		tbl_os.consumidor_nome,
		tbl_os.consumidor_fone,
		tbl_os.consumidor_celular,
		tbl_os.consumidor_fone_comercial,
		tbl_os.consumidor_endereco,
		tbl_os.consumidor_numero,
		tbl_os.consumidor_complemento,
		tbl_os.consumidor_bairro,
		tbl_os.consumidor_cep,
		tbl_os.consumidor_cidade,
		tbl_os.consumidor_estado,
		tbl_os.consumidor_cpf,
		tbl_os.consumidor_email,
		tbl_os.consumidor_fone_recado,
		tbl_os.nota_fiscal,
		tbl_os.revenda_nome,
		tbl_os.revenda_cnpj,
		tbl_os.os_reincidente                         AS reincidencia,
		tbl_os.motivo_atraso,
		TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY')         AS data_nf,
		tbl_defeito_reclamado.descricao               AS defeito_reclamado,
		tbl_os.defeito_reclamado_descricao,
		tbl_defeito_constatado.descricao              AS defeito_constatado,
		tbl_defeito_constatado_grupo.descricao        AS defeito_constatado_grupo,
		tbl_os.defeito_constatado_grupo        AS id_defeito_constatado_grupo,
		tbl_defeito_constatado.codigo                 AS defeito_constatado_codigo,
		tbl_os.aparencia_produto,
		tbl_os.acessorios,
		tbl_os.consumidor_revenda,
		tbl_os.obs,
		tbl_os.observacao AS obs_callcenter,
		tbl_os.excluida ,
		tbl_produto.referencia                                            ,
		tbl_produto.referencia_fabrica                                    ,
		tbl_produto.descricao                                             ,
		tbl_produto.voltagem                                              ,
		tbl_produto.valor_troca                                           ,
		tbl_produto.parametros_adicionais AS produto_parametros_adicionais ,
		tbl_os.justificativa_adicionais                                   ,
		tbl_posto_fabrica.codigo_posto               AS posto_codigo      ,
		tbl_posto_fabrica.reembolso_peca_estoque                          ,
		tbl_posto.nome                               AS posto_nome        ,
		tbl_posto.posto                              AS codigo_posto      ,
		tbl_os_extra.os_reincidente                                       ,            
		tbl_os.obs_reincidencia,
		TO_CHAR(tbl_os.data_nf_saida, 'DD/MM/YYYY')                 AS data_nf_saida,
		TO_CHAR(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI')         AS data_conserto,
		tbl_os.troca_faturada,
		tbl_os_extra.tipo_troca,
		tbl_os.os_posto,
		TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY HH24:MI')            AS data_ressarcimento,
		tbl_os.qtde_km,
		tbl_os.os_numero,
		tbl_os_troca.observacao   AS observacao_troca,
		tbl_os.valores_adicionais,
		tbl_os.cortesia,
		tbl_os_campo_extra.campos_adicionais AS os_campos_adicionais,
		tbl_os_campo_extra.valores_adicionais AS os_valores_adicionais,
		tbl_status_checkpoint.descricao AS status_checkpoint
	   FROM tbl_os
	   JOIN tbl_os_produto              ON tbl_os.os = tbl_os_produto.os 
	   JOIN tbl_produto                 ON tbl_os_produto.produto = tbl_produto.produto 
           JOIN tbl_posto                   ON tbl_posto.posto               = tbl_os.posto
	   JOIN tbl_posto_fabrica           ON tbl_posto_fabrica.posto       = tbl_os.posto
           AND tbl_posto_fabrica.fabrica     = {$login_fabrica}
	   JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
	   LEFT JOIN tbl_os_extra           ON tbl_os.os                     = tbl_os_extra.os
	   LEFT JOIN tbl_os_campo_extra ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
	   LEFT JOIN tbl_os_troca           ON tbl_os.os                     = tbl_os_troca.os

	   LEFT JOIN tbl_admin              ON (tbl_os.admin                 = tbl_admin.admin)
	   LEFT JOIN tbl_admin troca_admin  ON tbl_os.troca_garantia_admin   = troca_admin.admin
	   LEFT JOIN tbl_defeito_reclamado  ON tbl_os.defeito_reclamado      = tbl_defeito_reclamado.defeito_reclamado
	   LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado     = tbl_defeito_constatado.defeito_constatado
	   LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_defeito_constatado.defeito_constatado_grupo
	   LEFT JOIN tbl_motivo_reincidencia USING (motivo_reincidencia)
	   LEFT JOIN tbl_tipo_atendimento       ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
	   WHERE   tbl_os.os = {$os}
	   AND     tbl_os.fabrica = {$login_fabrica}";
    $resOS = pg_query($con,$sql);
}

$layout_menu = "call_center";
$title = "DETALHE DA ORDEM DE SERVIÇO";
include 'funcoes.php';

if ($moduloGestaoContrato) {
    include_once "cabecalho_novo.php";
} else {
    include_once "cabecalho_new.php";
}

    function nao_null($el) {
        if (strtoupper($el) == 'NULL')
            return  '';
        return $el;
    }
    
    if (pg_num_rows($resOS) > 0) {
        $row = pg_fetch_assoc($resOS);
        $os                 = $row['os'];
        $endereco           = $row['consumidor_endereco'] . " " . $row['consumidor_complemento'];

        $sql = "SELECT tbl_defeito_constatado.descricao 
                FROM tbl_os_defeito_reclamado_constatado
                JOIN tbl_defeito_constatado USING(defeito_constatado,fabrica)
                WHERE tbl_os_defeito_reclamado_constatado.os = {$os}";
        $resDC = pg_query($con,$sql);


        $sql = "SELECT tbl_solucao.descricao 
                FROM tbl_os_defeito_reclamado_constatado
                JOIN tbl_solucao USING(solucao,fabrica)
                WHERE tbl_os_defeito_reclamado_constatado.os = {$os}";
        $resSL = pg_query($con,$sql);

?>
    <link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="all" />
    <link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="all" />

<style type="text/css">
    .titulo_coluna{
        background-color: #FFFFFF;
        color: #000000;
    }
    .txt_center{
        text-align: center !important;
    }
    .color_os{
        font-size:26px; font-weight:bold; color:orange !important;
    }
    body {-webkit-print-color-adjust: exact !important}
    @media print {
        .rodape-no{
            margin-top: -250px;
        }
        .imprimir{
            margin-top: -250px;
        }
        .no-print{
            visibility: hidden !important;
        }
        .titulo_coluna{
            background-color: #FFFFFF !important;
            -webkit-print-color-adjust: exact !important; 
            color: #000000 !important;
        }
        .titulo_tabela {
            background-color: #596d9b !important;
            -webkit-print-color-adjust: exact !important;
            color: #fff !important;
        }

        .table-bordered td.tar {
            background-color: #D9E2EF !important;
            -webkit-print-color-adjust: exact !important;
        }   
        .table-itens .titulo_itens th{
            background-color: #D9E2EF !important;
            -webkit-print-color-adjust: exact !important;
        } 
        .table-bordered th, .table-bordered td {
            border-color:#ccc !important;
        }
        .color_os{
            font-size:26px; font-weight:bold; color:orange !important;
        }
    }

</style>

<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript">
    $(function(){
        Shadowbox.init();
    });
</script>
    <div class="imprimir">
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Posto Autorizado</td>
            </tr>
            <tr>
                <td nowrap><?=$row['posto_codigo']?> - <?=$row['posto_nome']?></td>
            </tr>
        </table>

        <table align="center" id="resultado_os" class='table table-bordered table-large' >
           
            <tr>
                <td rowspan="3" width="250" class="tac" >
                    <p style="font-size: 10px;"><b>OS FABRICANTE</b></p>
                    <span class="color_os"><?=$row['sua_os']?></span>
                </td>   
            </tr> 
             <tr>
                <td class='titulo_tabela tac' colspan='4'>Datas da Ordem de Serviço</td>
            </tr>
            <tr>                
                <td class='titulo_coluna'>Data Digitação</td>
                <td><?php echo $row['data_digitacao'];?></td>
                <td class='titulo_coluna'>Finalizada</td>
                <td><?php echo $row['finalizada'];?></td>
            </tr>
            <tr>
                <td class='titulo_coluna tac'><?=$row['status_checkpoint']?></td>
                <td class='titulo_coluna'>Data Abertura</td>
                <td><?php echo $row['data_abertura'];?></td>
                <td class='titulo_coluna'>Data Fechamento</td>
                <td><?php echo $row['data_fechamento'];?></td>
            </tr> 
        </table>
        
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Produto</td>
            </tr>
            <tr>
                <td class='titulo_coluna'>Referência</td>
                <td><?php echo $row['referencia'];?></td>
                <td class='titulo_coluna'>Descrição</td>
                <td colspan='5'><?php echo $row['descricao'];?></td>
                <td class='titulo_coluna'>Número de Série</td>
                <td colspan='5'><?php echo $row['serie'];?></td>
            </tr>
        </table>
        
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Diagnóstico do Produto</td>
            </tr>
            <tr>
                <td class='titulo_coluna' nowrap>Defeito Reclamado</td>
                <td><?php echo $row['defeito_reclamado'];?></td>
            </tr>
            <tr>
                <td class='titulo_coluna' nowrap>Defeitos Constatados</td>
                <td>
                    <?php 
                    while($rowDC = pg_fetch_assoc($resDC)) {
                        echo "- ".$rowDC['descricao'] . "<br>";
                    }
                    ?>
                        
                </td>
            </tr>
            <tr>
                <td class='titulo_coluna' nowrap>Serviços Realizados</td>
                <td>
                    <?php 
                    while($rowSL = pg_fetch_assoc($resSL)) {
                        echo "- ".$rowSL['descricao'] . "<br>";
                    }
                    ?>
                        
                </td>
            </tr>
        </table>
        
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
            </tr>
            <tr>
                <td class='titulo_coluna'>Cliente</td>
                <td nowrap><?php echo $row['consumidor_nome'];?></td>
                <td class='titulo_coluna'>CPF/CNPJ</td>
                <td><?php echo $row['consumidor_cpf'];?></td>    
                <td class='titulo_coluna'>E-mail</td>
                <td nowrap><?php echo $row['consumidor_email'];?></td>       
            </tr>

            <tr>                
                <td class='titulo_coluna'>Telefone</td>
                <td nowrap ><?php echo $row['consumidor_fone'];?></td>
                <td class='titulo_coluna'>Celular</td>
                <td nowrap><?php echo $row['consumidor_celular'];?></td>
                <td class='titulo_coluna'>CEP</td>
                <td colspan="3"><?php echo $row['consumidor_cep'];?></td>
            </tr>

            <tr>                    
                <td class='titulo_coluna'>Endereço</td>
                <td ><?php echo $endereco;?></td>   
                <td class='titulo_coluna'>Bairro</td>
                <td><?php echo $row['consumidor_bairro'];?></td>   
                <td class='titulo_coluna'>Cidade</td>
                <td><?php echo $row['consumidor_cidade'];?> / <?php echo $row['consumidor_estado'];?></td>
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
                <td class='titulo_coluna'>NF Número</td>
                <td nowrap><?php echo $row['nota_fiscal'];?></td>
                <td class='titulo_coluna'>Data NF</td>
                <td><?php echo $row['data_nf'];?></td>           
            </tr>

        </table>
        
    <?php
        $sqlItem = "SELECT  tbl_peca.referencia,
                            tbl_peca.descricao,
                            tbl_os_item.qtde,
                            to_char(tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
                            tbl_servico_realizado.descricao AS servico,
                            tbl_os_item.pedido,
                            tbl_faturamento.nota_fiscal,
                            to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
                    FROM tbl_os_produto
                    JOIN tbl_os_item USING(os_produto)
                    JOIN tbl_peca USING(peca)
                    JOIN tbl_servico_realizado USING(servico_realizado)
                    LEFT JOIN tbl_faturamento_item ON tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item
                    LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                    WHERE tbl_os_produto.os = {$os}";
                   #echo nl2br($sqlItem);die;
        $resItemOS = pg_query($con,$sqlItem);

        if(pg_num_rows($res) > 0){
        ?>
            <table align='center' class='table table-bordered table-large' >
                    <tr>
                        <td class='titulo_tabela tac' colspan='100%'>DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS</td>
                    </tr>
                    <tr>
                        <td class='titulo_coluna txt_center'>COMPONENTE</td>
                        <td class='titulo_coluna'>QTDE</td>
                        <td class='titulo_coluna'>DIGIT.</td>
                        <td class='titulo_coluna txt_center'>SERVIÇO</td>
                        <td class='titulo_coluna txt_center'>PEDIDO</td>
                        <td class='titulo_coluna txt_center'>NOTA FISCAL</td>
                        <td class='titulo_coluna txt_center'>EMISSÃO</td>
                    </tr>
        <?php
                while($rowItem = pg_fetch_assoc($resItemOS)) {
                $rowItem = array_map('nao_null',$rowItem);
                
                echo "<tr>
                        <td>".$rowItem['referencia']." - ".$rowItem['descricao']."</td>
                        <td class='txt_center'>".$rowItem['qtde']."</td>
                        <td>".$rowItem['digitacao_item']."</td>
                        <td>".$rowItem['servico']."</td>
                        <td class='txt_center'>".$rowItem['pedido']."</td>
                        <td class='txt_center'>".$rowItem['nota_fiscal']."</td>
                        <td class='txt_center'>".$rowItem['emissao']."</td>
                      </tr>";
            }
            echo "</table>";
        }

        if(strlen($row['obs']) > 0){
        ?>
            <table align="center" id="resultado_os" class='table table-bordered table-large' >
                <tr>
                    <td class='titulo_tabela tac' colspan='100%'>Observação</td>
                </tr>
                <tr>
                    <td nowrap><?php echo $row['obs'];?></td>          
                </tr>

            </table>
        <?php
        }

        if ($fabricaFileUploadOS) {
        ?>
         <table align="center" id="resultado_os" class='table table-bordered table-large no-print' >
            <tr>
                <td>
                    <?php

                        $tempUniqueId = $row['os'];
                        $boxUploader = array(
                            "div_id" => "div_anexos",
                            "prepend" => $anexo_prepend,
                            "context" => "os",
                            "unique_id" => $tempUniqueId,
                            "hash_temp" => $anexoNoHash,
                            "bootstrap" => false,
                            "hidden_button" =>  true
                        );
                        include "box_uploader.php";
                    ?>
                </td>
            </tr>
        </table>
        <?php 
        } 
    }

?>
</div>
 <div class="row-fluid no-print">
    <div class="span12 tac">
        <button type="button" onclick="window.print();" class="btn btn-info btn-print-nova btn-xlarger"><i class="icon-print icon-white"></i> Imprimir</button>
    </div>
</div>
 <div class="rodape-no no-print">

<?php 
/* Rodapé */
    include 'rodape.php';
?>
</div>
