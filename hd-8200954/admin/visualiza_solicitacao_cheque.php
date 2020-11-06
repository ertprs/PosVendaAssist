<?php
$admin_privilegios = "financeiro,call_center";
$layout_menu       = "financeiro";

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';
include_once 'funcoes.php';
include_once '../class/tdocs.class.php';

$codigo = $_GET['codigo'];

$sql = "SELECT
            tbl_solicitacao_cheque.solicitacao_cheque,
            tbl_solicitacao_cheque.fabrica,
            tbl_solicitacao_cheque.admin,
            tbl_admin.nome_completo AS nome_completo,
            tbl_solicitacao_cheque.posto,
            tbl_solicitacao_cheque.fornecedor,
            tbl_solicitacao_cheque.tipo_solicitacao,
            tbl_tipo_solicitacao.descricao as descricao_tipo,
            tbl_solicitacao_cheque.componente_solicitante,
            tbl_solicitacao_cheque.vencimento,
            tbl_solicitacao_cheque.valor_liquido,
            tbl_solicitacao_cheque.valor_liquido_extenso,
            tbl_solicitacao_cheque.historico,
            tbl_solicitacao_cheque.data_input,
            tbl_solicitacao_cheque_acao.admin_acao,
            tbl_solicitacao_cheque_acao.tipo_acao,
            tbl_solicitacao_cheque_acao.data_input AS data_input_acao,
            tbl_admin2.nome_completo AS nome_completo2,
            tbl_solicitacao_cheque.numero_solicitacao,
            CASE WHEN tbl_solicitacao_cheque.posto IS NOT NULL
                THEN
					(SELECT
                        tbl_posto.nome||'@@'||
                        tbl_posto.endereco||'@@'||
                        tbl_posto.numero||'@@'||
                        tbl_posto.bairro||'@@'||
                        CASE WHEN tbl_posto.complemento IS NOT NULL
                            THEN
                                tbl_posto.complemento
                            ELSE
                                ''
                        END||'@@'||
                        tbl_posto.cidade||'@@'||
                        CASE WHEN tbl_posto.ie IS NOT NULL
                            THEN
                                tbl_posto.ie
                            ELSE
                                ''
                        END||'@@'||
                        tbl_posto.cep||'@@'||
                        tbl_posto.cnpj||'@@'||
                        tbl_posto.estado||'@@'||
						tbl_posto_fabrica.codigo_posto||'@@'||
                        1
                    FROM tbl_posto JOIN tbl_posto_fabrica ON(tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica)  WHERE tbl_posto.posto = tbl_solicitacao_cheque.posto)
            ELSE
				(SELECT
                    tbl_fornecedor.nome||'@@'||
                    tbl_fornecedor.endereco||'@@'||
                    tbl_fornecedor.numero||'@@'||
                    tbl_fornecedor.bairro||'@@'||
                    CASE WHEN tbl_fornecedor.complemento IS NOT NULL
                        THEN
                            tbl_fornecedor.complemento
                        ELSE
                            ''
                    END||'@@'||
                    tbl_cidade.nome||'@@'||
                    CASE WHEN tbl_fornecedor.ie IS NOT NULL
                        THEN
                            tbl_fornecedor.ie
                        ELSE
                            ''
                    END||'@@'||
                    tbl_fornecedor.cep||'@@'||
                    tbl_fornecedor.cnpj||'@@'||
                    tbl_cidade.estado||'@@'||
					tbl_fornecedor.fornecedor||'@@'||
                    2
                FROM tbl_fornecedor JOIN tbl_cidade USING(cidade) WHERE fornecedor = tbl_solicitacao_cheque.fornecedor)
            END AS fornecedor_posto
        FROM tbl_solicitacao_cheque
            JOIN tbl_admin ON(tbl_admin.admin = tbl_solicitacao_cheque.admin AND tbl_admin.fabrica = {$login_fabrica})
            LEFT JOIN tbl_solicitacao_cheque_acao USING(solicitacao_cheque)
            LEFT JOIN tbl_tipo_solicitacao ON tbl_solicitacao_cheque.tipo_solicitacao = tbl_tipo_solicitacao.tipo_solicitacao
            LEFT JOIN tbl_admin AS tbl_admin2 ON(tbl_admin2.admin = tbl_solicitacao_cheque_acao.admin_acao AND tbl_admin2.fabrica = {$login_fabrica})
        WHERE solicitacao_cheque = $codigo ORDER BY tbl_solicitacao_cheque_acao.data_input DESC LIMIT 1";
$res = pg_query($con, $sql);

extract(pg_fetch_all($res)[0]);

$fornecedor_posto    = explode('@@', $fornecedor_posto);
$imprimir_fornecedor = ($fornecedor_posto[count($fornecedor_posto) - 1] == 2) ? true : false;
$tipo_solicitacao    = trim(pg_fetch_result($res, 0, 'descricao_tipo'));
$tipo_solicitacao_id    = trim(pg_fetch_result($res, 0, 'tipo_solicitacao'));


?>
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <style type="text/css">
            .conteudo{
                border: 1px black solid;
            }
            .logo_fabrica{
                max-height:70px;
                max-width:210px;
            }
            .table-filha tr td{
                padding: 8px;
            }
            .table-fornecedor tr td{
                padding: 3px;
            }
            body{
                font-size: 9px;
            }

            .titulo_tipo_anexo{
                text-align: center; 
                font-size: 16px; 
                font-weight: bold; 
                background-color:#596d9b; 
                color:#fff; 
                width: 800px; 
                margin: 0 auto 10px auto;
            }

            .anexos_barra{
                text-align: center; 
                font-size: 16px; 
                font-weight: bold; 
                background-color:#596d9b; 
                color:#fff; 
                width: 800px; 
                margin: 0 auto; 
            }

                @media print {
                    body{
                        margin: 0 auto;
                    }

                    img{
                        width: 100%;
                    }
                }

                .titulo_tipo_anexo, .anexos_barra{color:#000000;}

            }


        </style>
    </head>
    <body>
<?php
$data_input      = explode(' ', $data_input)[0];
$data_input      = implode('/',array_reverse(explode('-', $data_input)));
$data_input_acao = explode(' ', $data_input_acao)[0];
$data_input_acao = implode('/',array_reverse(explode('-', $data_input_acao)));
$vencimento      = implode('/',array_reverse(explode('-', $vencimento)));

if ($tipo_acao == 'aprovado') {
    $tdocs          = new TDocs($con, $login_fabrica, 'assinatura');
    $img_assinatura = $tdocs->getDocumentsByRef($admin_acao)->url;
}

$sql_assinatura_responsavel = "SELECT admin FROM tbl_admin WHERE nome_completo = '$nome_completo' AND fabrica = $login_fabrica";
$res_assinatura_responsavel = pg_query($con, $sql_assinatura_responsavel);
if (pg_num_rows($res_assinatura_responsavel) > 0) {
    $admin_responsavel = pg_fetch_result($res_assinatura_responsavel, 0, 'admin');
    $tdocs          = new TDocs($con, $login_fabrica, 'assinatura');
    $img_assinatura_responsavel = $tdocs->getDocumentsByRef($admin_responsavel)->url;    
}

$sql = "SELECT
            numero,
            valor_bruto,
            valor_liquido,
            conta_ger,
            conta_sub,
            conta_comp,
            valor,
            tipo,
            observacao,
            data_input
        FROM tbl_solicitacao_cheque_item
		WHERE solicitacao_cheque = $codigo
		order by solicitacao_cheque_item";
$res = pg_query($con, $sql);

if (empty($numero_solicitacao)) {
    $numero_solicitacao = $solicitacao_cheque;
}

$solicitacao = false;

if (isset($_GET['solicitacao_cheque']) && $_GET['solicitacao_cheque'] == 'sim') {
    $solicitacao = true;
    $contexto = "cheque";
    $condigo_anexo = $_GET['codigo'];

    $tipo_anexo_arr = array("Calculo", "NF", "Ticket", "Outros Anexos 1", "Outros Anexos 2");
    $qtde_anexo = 5; 
}

if ($solicitacao == true) {

    ?>
    <div style="page-break-after: always">
        <table style="width: 100%;">
            <tr>
                <td colspan="100%">
                    <table style="width: 100%;">
                        <tr>
                            <td width="75%"><img class='logo_fabrica' src="../logos/logo_black_2016.png" alt="http://www.blackedecker.com.br" border="0"></td>
                            <td class="conteudo" style="text-align: center;">
                                <label>CONTAS A PAGAR - UBERABA</label><br />
                                <label>SOLICITAÇÃO DE CHEQUE</label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="100%" style="text-align: right; font-size: 15px;">
                                <b><?=str_pad($numero_solicitacao, 6, '0', STR_PAD_LEFT)?></b>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td></td>
                            <td><b><?='Impressão: '.date('d/m/Y')?></b></td>
                        </tr>
                        <tr>
                            <td>
                                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                                    <tr>
                                        <td style="width: 20%;">
                                            <label>Componente Solicitante</label><br />
                                            <label><b><?=$componente_solicitante?></b></label>
                                        </td>
                                        <td style="width: 40%">
                                            <label>Local</label><br />
                                            <label><b></b></label>
                                        </td>
                                        <td>
                                            <label>Emissão</label><br />
                                            <label><b><?=$data_input?></b></label>
                                        </td>
                                        <td>
                                            <label>Vencimento</label><br />
                                            <label><b><?=$vencimento?></b></label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td rowspan="2" valign="top" style="padding-left: 2px;">
                                <table class="table-filha" style="width: 100%; height: 120px;" border="1" cellspacing="0">
                                    <tr valign="top">
                                        <td>Recepção Contas a Pagar</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                                    <tr>
                                        <td width="30%">
                                            <label>Favorecido do Cheque</label><br />
                                            <label><input type="checkbox" />Fornecedor</label><br />
                                            <label><input type="checkbox" />Banco</label>
                                        </td>
                                        <td>
                                            <label>Nome do favorecido</label><br />
                                            <label><b></b></label>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td>
                                <label>Código Fornecedor</label><br />
    							<label><b><?=$fornecedor_posto[10]?></b></label>
                            </td>
                            <td colspan="3">
                                <label>Nome do Fornecedor</label><br />
                                <label><b><?=$fornecedor_posto[0]?></b></label>
                            </td>
                            <td>
                                <label>C.P.F/C.G.C</label><br />
                                <label><b><?=$fornecedor_posto[8]?></b></label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label>Endereço</label><br />
                                <label><b><?=$fornecedor_posto[1]?></b></label>
                            </td>
                            <td>
                                <label>Número</label><br />
                                <label><b><?=$fornecedor_posto[2]?></b></label>
                            </td>
                            <td>
                                <label>Bairro</label><br />
                                <label><b><?=$fornecedor_posto[3]?></b></label>
                            </td>
                            <td>
                                <label>Cidade</label><br />
                                <label><b><?=$fornecedor_posto[5]?></b></label>
                            </td>
                            <td>
                                <label>Estado</label><br />
                                <label><b><?=$fornecedor_posto[9]?></b></label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <label>Inscrição Municipal: </label>
                                <label><b></b></label>
                            </td>
                            <td colspan="100%">
                                <label>Inscrição INPS: </label>
                                <label><b></b></label>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td>
                                <label>Valor Líquido</label><br />
                                <label><b><?=priceFormat($valor_liquido)?></b></label>
                            </td>
                            <td colspan="100%">
                                <label>Valor Líquido por Extenso</label><br />
                                <label><b><?=$valor_liquido_extenso?></b></label>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td>
                                <label>IT</label>
                            </td>
                            <td colspan="2">
                                <label>Documento</label>
                            </td>
                            <td rowspan="2" valign="top">
                                <label>Valor Bruto</label>
                            </td>
                            <td colspan="2">
                                <label>Abatimento/Credito</label>
                            </td>
                            <td rowspan="2" valign="top">
                                <label>Valor Líquido</label>
                            </td>
                            <td rowspan="2" valign="top">
                                <label>Observação</label>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>Número</td>
                            <td>Ordem</td>
                            <td>1-AB<br />2-CR</td>
                            <td>Valor</td>
                        </tr>
                        <?php for ($i = 0; $i < pg_num_rows($res); $i++) {
                            $numero = pg_fetch_result($res, $i, 'numero');
                            $valor_bruto = pg_fetch_result($res, $i, 'valor_bruto');
                            $valor_liquido = pg_fetch_result($res, $i, 'valor_liquido');
                            $observacao = pg_fetch_result($res, $i, 'observacao');
                            $tipo = pg_fetch_result($res, $i, 'tipo');
                            if ($tipo !== 'valor_1') {
                                continue;
                            }
                        ?>
                        <tr>
                            <td><b><?=$i + 1?></b></td>
                            <td><b><?=$numero?></b></td>
                            <td></td>
                            <td><b><?=priceFormat($valor_bruto)?></b></td>
                            <td></td>
                            <td></td>
                            <td><b><?=priceFormat($valor_liquido)?></b></td>
                            <td><b><?=$observacao?></b></td>
                        </tr>
                        <?php } ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td>
                                <label>Histórico</label><br />
                                <label><b><?=$historico?></b></label>
                            </td>
                        </tr>
                        <tr>
                            <td align="center"><b>Quando existir mais de um documento relacionado acima a contabilidade será indicada por documento.</b></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td>
                                <label>IT</label>
                            </td>
                            <td>
                                <label>Documento</label>
                            </td>
                            <td colspan="3" style="text-align: center;">
                                <label>Conta</label>
                            </td>
                            <td>
                                <label>D=1</label>
                            </td>
                            <td rowspan="2" valign="top">
                                <label>Valor em (R$)</label>
                            </td>
                            <td rowspan="2" valign="top">
                                <label>Valor Tributável em (R$)</label>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>Número</td>
                            <td>
                                <label>Ger</label>
                            </td>
                            <td>
                                <label>Sub</label>
                            </td>
                            <td>
                                <label>Comp</label>
                            </td>
                            <td>
                                <label>C=2</label>
                            </td>
                        </tr>
    					<?php
    					$j = 0 ; 
    				  	for ($i = 0; $i < pg_num_rows($res); $i++) {
                            $numero = pg_fetch_result($res, $i, 'numero');
                            $conta_ger = pg_fetch_result($res, $i, 'conta_ger');
                            $conta_sub = pg_fetch_result($res, $i, 'conta_sub');
                            $conta_comp = pg_fetch_result($res, $i, 'conta_comp');
                            $valor = pg_fetch_result($res, $i, 'valor');
                            $tipo = pg_fetch_result($res, $i, 'tipo');
                            if ($tipo !== 'valor_2') {
                                continue;
    						}else{
    							$j++;
    						}
    						
                        ?>
                        <tr>
                            <td><b><?=$j?></b></td>
                            <td><b><?=$numero?></b></td>
                            <td><b><?=$conta_ger?></b></td>
                            <td><b><?=$conta_sub?></b></td>
                            <td><b><?=$conta_comp?></b></td>
                            <td></td>
                            <td><b><?=priceFormat($valor)?></b></td>
                            <td></td>
                        </tr>
                        <?php } ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td width="8%"></td>
                            <td width="15%"><label>Solicitante</label></td>
                            <td width="15%"><label>Aprovações</label></td>
                            <td><label>Conferência Contas a Pagar</label></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="100%">
                    <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                        <tr>
                            <td width="8%"><label>Assin.</label></td>
                            <?php
                            if (strtolower($tipo_solicitacao) == 'guia judicial' || strtolower($tipo_solicitacao) == 'pagamento de honorários') {
                                ?>
                                <td width="15%" style="background-color: white !important; height: 50px;"><img src="<?=$img_assinatura_responsavel?>" height="96" alt=""></td>
                                <td width="15%" style="background-color: white !important;"><img src="<?=$img_assinatura?>" height="96" alt=""></td>
                                <?php
                            } else { ?>
                                <td width="15%" style="background-color: white !important; height: 50px;"><img src="<?=$img_assinatura_responsavel?>" height="96" alt=""></td>
                                <td width="15%" style="background-color: white !important;"><img src="<?=$img_assinatura?>" height="96" alt=""></td>
                            <?php
                            }
                            ?>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><label>Nome</label></td>
                            <?php
                            if (strtolower($tipo_solicitacao) == 'guia judicial' || strtolower($tipo_solicitacao) == 'pagamento de honorários') {
                                ?>
                                <td><b><label>ADVOGADO</label></b></td>
                                <?php
                            } else {
                            ?>
                            <td><b><label><?=$nome_completo?></label></b></td>
                            <?php
                            } ?>
                            <td><b><label><?=($tipo_acao == 'aprovado') ? $nome_completo2 : 'NOME APROVADOR'?></label></b></td>
                            <?php
                            if (strtolower($tipo_solicitacao) == 'guia judicial' || strtolower($tipo_solicitacao) == 'pagamento de honorários') {
                                ?>
                                <td><b><label>FERNANDO BORTOLOZZO</label></b></td>
                                <?php
                            }
                            ?>
                            <td><label>Firmas</label></td>
                            <td><label>Preparação</label></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <br />
        <div align="center" style="font-size: 12px;">
            <label><b>1 Via - Contas a Pagar</b></label>
            <label style="margin-left: 15px;"><b>2 Via - Requisitante (Protocolo)</b></label>
            <label style="margin-left: 15px;"><b>3 Via - Planejamento Financeiro</b></label>
        </div>
        <br /><br /><br /><br /><br /><br /><br /><br />
    </div>
<?php
}

$ficha = false;

if (isset($_GET['ficha_cadastral']) && $_GET['ficha_cadastral'] == 'sim') {
    $ficha = true;
    $contexto = "fornecedor";
    $condigo_anexo = $fornecedor; 

    $tipo_anexo_arr = array("CPF_CNPJ", 'SINTEGRA');
    $qtde_anexo = 2; 
} 

if ($imprimir_fornecedor && $ficha == true) {
?>
<div style="page-break-after: always">
    <table style="width: 100%;">
        <tr>
            <td colspan="100%">
                <table style="width: 100%;">
                    <tr>
                        <td width="50%"><img class='logo_fabrica' src="../logos/logo_black_2016.png" alt="http://www.blackedecker.com.br" border="0"></td>
                        <td class="conteudo" style="text-align: center;">
                            <label>FICHA CADASTRAL - FORNECEDORES</label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td valign="top" width="35%">
                            <label>Código</label>
                        </td>
                        <td colspan="100%">
                            <label>Tipo de Cadastramento <strong>*</strong></label><br />
                            <input type="checkbox"> Efetivo
                            <input type="checkbox" checked> Temporário
                            <input type="checkbox"> Atualização
                            <input type="checkbox"> Bloqueio
                            <input type="checkbox"> Reativação
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td colspan="100%">
                            <label>Razão Social</label><br />
                            <label><b><?=$fornecedor_posto[0]?></b></label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td width="40%">
                            <label>Endereço</label><br />
                            <label><b><?echo$fornecedor_posto[1]. " " .$fornecedor_posto[2]?></b></label>
                        </td>
                        <td colspan="100%">
                            <label>Complemento</label><br />
                            <label><b><?=$fornecedor_posto[4]?></b></label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td width="40%">
                            <label>Bairro</label><br />
                            <label><b><?=$fornecedor_posto[3]?></b></label>
                        </td>
                        <td>
                            <label>Cidade</label><br />
                            <label><b><?=$fornecedor_posto[5]?></b></label>
                        </td>
                        <td colspan="100%">
                            <label>UF</label><br />
                            <label><b><?=$fornecedor_posto[9]?></b></label>
                        </td>
                        <td colspan="100%">
                            <label>Emissor de NFe</label><br />
                            <input type="checkbox"> Sim
                            <input type="checkbox" checked> Não
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td>
                            <label>CEP</label><br />
                            <label><b><?=$fornecedor_posto[7]?></b></label>
                        </td>
                        <td>
                            <label>Pais</label><br />
                            <label><b>BRASIL</b></label>
                        </td>
                        <td colspan="100%">
                            <label>Frete:</label>
                            <input type="checkbox"> CIF
                            <input type="checkbox" checked> FOB
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table style="width: 100%;" cellspacing="0">
                    <tr>
                        <td valign="top" width="50%">
                            <table class="table-fornecedor" style="width: 100%;" border="1" cellspacing="0">
                                <tr>
                                    <td valign="top" style="height: 41px;">
                                        <label>Nome Classificação <strong>*</strong></label><br />
                                        <label><b></b></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="height: 44px;">
                                        <label>Condições de Pagamentos <strong>*</strong></label><br /><br /><br />
                                        <label><b>PAGAMENTO A VISTA</b></label>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td valign="top" style="padding-left: 2px; padding-right: 0px; margin-right: 0px;">
                            <table class="table-fornecedor" style="width: 100%;" border="1" cellspacing="0">
                                <tr valign="top">
                                    <td colspan="2">
                                        <label>Tipo Fornecedor <strong>*</strong></label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table class="table-filha" style="width: 100%; height: 100%;" border="0" cellspacing="0">
                                            <tr>
                                                <td>
                                                    <input type="checkbox"> Matéria-Prima
                                                </td>
                                                <td>
                                                    <input type="checkbox"> Matéria-Prima e Serviços
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="checkbox"> Matéria-Prima e Diversos
                                                </td>
                                                <td>
                                                    <input type="checkbox"> Serviços e Diversos
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" checked> Diversos
                                                </td>
                                                <td>
                                                    <input type="checkbox"> Serviços
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td colspan="100%" style="text-align: center;"><label style="font-size: 14px;"><b>Contatos</b></label></td>
                    </tr>
                    <tr>
                        <td width="10%"></td>
                        <td>Nome</td>
                        <td>Telefone / Celular</td>
                        <td>Fax</td>
                    </tr>
                    <tr>
                        <td>Vendas</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Financeiro</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td colspan="100%" style="text-align: center;"><label style="font-size: 14px;"><b>Dados Fiscais</b></label></td>
                    </tr>
                    <tr>
                        <td>
                            <label>CNPJ / CPF</label><br />
                            <label><b><?=$fornecedor_posto[8]?></b></label>
                        </td>
                        <td colspan="100%" valign="top">
                            <label>Inscrição Estadual</label><br />
                            <label><b><?=$fornecedor_posto[6]?></b></label>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" height="20px;">
                            <label>Inscrição Municipal</label><br />
                            <label><b></b></label>
                        </td>
                        <td colspan="100%">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%; height: 90px;" border="1" cellspacing="0">
                    <tr>
                        <td colspan="100%" style="text-align: center;"><label style="font-size: 14px;"><b>Assinaturas</b></label></td>
                    </tr>
                    <tr>
                        <td rowspan="2" valign="top"><label>Fornecedor (Carimbo e Assinatura)</label></td>
						<td rowspan="2" valign="top"><label>Comprador / Contratante <strong>*</strong></label><br/>
                        <img src="<?=$img_assinatura_responsavel?>" height="96">
						</td>
                        <td valign="top">
                            <label>Gerência Responsável <strong>*</strong></label><br />
                            <img src="<?=$img_assinatura?>" height="96">
                        </td>                        
                        <td valign="top" style="height: 40px;">
                            <label>Usuário responsável <strong>*</strong></label><br />
                            <p><b><?=$nome_completo?></b></p>
                        </td>                        
                        <td valign="top" style="height: 40px;"><label>Assuntos Fiscais <strong>*</strong></label></td>                        
                    </tr>
                    <tr>
                        <td valign="top" style="height: 40px;">
                            <label>Data de aprovação  <strong>*</strong></label><br />
                            <p><b><?=$data_input_acao?></b></p>
                        </td>
                        <td valign="top" style="height: 40px;">
                            <label>Data de emissão <strong>*</strong></label><br />
                            <p><b><?=$data_input?></b></p>
                        </td>                        
                        <td valign="top" style="height: 40px;"><label>Contas a Pagar <strong>*</strong></label></td>
                    
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td colspan="100%">
                            <label>Observações</label><br /><br />
                            <label>- Favor anexar copia do Contrato Social, Cartão CNPJ e Inscrição Estadual</label><br />
                            <label><b>* Campos exclusivos de uso da Black & Decker do Brasil Ltda.</b></label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="100%">
                <table class="table-filha" style="width: 100%;" border="1" cellspacing="0">
                    <tr>
                        <td colspan="100%">
                            <label>Sistema de Qualidade - Compras Produtivas <strong>*</strong></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>QAF</label>
                            <input type="checkbox"> Sim
                            <input type="checkbox"> Não
                        </td>
                        <td colspan="100%">
                            <label>ISO 9000</label>
                            <input type="checkbox"> Sim
                            <input type="checkbox"> Não
                        </td>
                    </tr>
                    <tr>
                        <td valign="bottom" colspan="100%" height="20px;">
                            <label>Assinatura responsavel _________________________________________________________________________________________ (Comprador do Item)</label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
<?php

}
if( ($solicitacao == true AND $tipo_solicitacao_id == 47) OR $ficha == true OR $login_fabrica == 1) {

    $tdocs = new TDocs($con, $login_fabrica, $contexto);
    $ret = $tdocs->getDocumentsByRef($condigo_anexo); 
    
    if (count($ret->attachListInfo)) {

                foreach ($ret->attachListInfo as $array_file) {

                    $key_tipo_anexo = array_search($array_file['extra']['tipo_anexo'] ,$tipo_anexo_arr);

                    if($login_fabrica == 1 && !$key_tipo_anexo){

                         $anexos_arr[] = array(
                            'anexo_imagem' => $array_file['link'],
                            'tipo_anexo'   => $array_file['extra']['tipo_anexo'],
                            'tdocs_id'     => $array_file['tdocs_id']
                        );
                    }else{

                        $anexos_arr[$key_tipo_anexo] = array(
                            'anexo_imagem' => $array_file['link'],
                            'tipo_anexo'   => $array_file['extra']['tipo_anexo'],
                            'tdocs_id'     => $array_file['tdocs_id']
                        );
                    }     
                }

                if(count($anexos_arr)){
                    ?>
                    <div class="anexos_barra">Anexos</div>
                    <div class="container-fluid" >
                        <div id="listagem-imagem" class="row-fluid" style="padding-top: 5px;">
                        <div class="span1"></div>
                    <?
                }

                if (!file_exists('xls/cheque_black')) {
                    system( 'mkdir xls/cheque_black' ); 
                }

                for($a = 0; $a<$qtde_anexo; $a++){

                    if(count(array_filter($anexos_arr[$a]))==0){
                        continue;
                    }
                    $link = $anexos_arr[$a]['anexo_imagem']; 
                    $tipo_anexo = $anexos_arr[$a]['tipo_anexo'];

                    $arq_tipo_anexo = str_replace(" ", "", $tipo_anexo);

                    $ext = strtolower(preg_replace("/.+\./", "", basename($link)));                    
        ?>
                    <div>
                        <?php if ($ficha == true) {
                            if ($tipo_anexo == "CPF_CNPJ") {
                                if (strlen($fornecedor_posto[8]) > 11) { 
                                    $tipo_anexo = "CNPJ";
                                } else {
                                    $tipo_anexo = "CPF";
                                }
                            }

                        } else {
                            $tipo_anexo = ($tipo_anexo == 'Calculo')? "Cálculo": $tipo_anexo; 
                        }

                        if (in_array($ext, array('jpg', 'jpeg', 'png'))) { ?>
                            <div class='titulo_tipo_anexo' ><?=   $tipo_anexo ?></div>
                            <div class="thumbnail" style="text-align: center !important;">
                                <img style="max-width: 550px; max-height: 800px" data-src='<?=$link?>' src="<?=$link?>" ><br />
                                
                                <div style="page-break-after: always;">

                            </div>
                        <?php } else {

                            $extensao = pathinfo($link, PATHINFO_EXTENSION);

                            if(in_array($extensao, ['doc', 'docx'])){ ?>

                                <div class='iframe' name="iframe-doc" style="text-align: center">
                                    <div class='titulo_tipo_anexo' ><?=$tipo_anexo?></div>
                                    <iframe src='https://view.officeapps.live.com/op/embed.aspx?src=<?=$link?>' width='600' height='800' frameborder='0'></iframe>
                                    <br />
                                    <div style="page-break-after: always;"></div>
                                </div>  

                            <? }else {

                                $dados = file_get_contents( str_replace(" ", "_", $link));
                                $novo_arquivo = 'xls/cheque_black/' . $codigo. "-$a" . "_" .$codigo. "-" . $arq_tipo_anexo .'.pdf';
                                file_put_contents($novo_arquivo, $dados);
                                chmod($novo_arquivo, 0777);

                                $image = new Imagick($novo_arquivo);
                                $count = $image->getNumberImages();

                                for ($x = 0;$x < $count; $x++) {
                                    $image->readImage("{$novo_arquivo}[{$x}]");
                                    $image->setImageFormat('jpg');
                                    $arquivo = $image->getImageBlob(); ?>
                                    <div class='iframe' style="text-align: center">
                                        <div class='titulo_tipo_anexo' ><?=$tipo_anexo?></div>
                                        <img width="550" height="800" src="data:image/jpg;base64,<?= base64_encode($arquivo); ?>" />
                                        <br />
                                        <div style="page-break-after: always;"></div>
                                    </div>
                                <?php }
                            }
                        
		                } ?>
                    </div>
        <?php
                
                }
        ?>
                
            </div>
        </div>
    <?php } 

}
    ?>



    </body>
</html>
<script>
window.print();
</script>
