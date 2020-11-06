<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";

include 'autentica_admin.php';
include 'funcoes.php';

$os       = $_GET['os'];

if (strlen($os) > 0) {

    $sql = "SELECT  tbl_os_itatiaia.os,
                    tbl_os_itatiaia.dados,
                    tbl_os_itatiaia.km_qtde_calculada
            FROM tbl_os_itatiaia 
            WHERE tbl_os_itatiaia.os='$os'";
//                echo nl2br($sql);die;
    $resI = pg_query($con,$sql);
}

$layout_menu = "callcenter";
$title = "DETALHE DA ORDEM DE SERVIÇO";
include_once "cabecalho_new.php";

    function nao_null($el) {
        if (strtoupper($el) == 'NULL')
            return  '';
        return $el;
    }
    
if (pg_num_rows($resI) > 0) {
	$dados_os = json_decode(pg_fetch_result($resI,0,'dados'),true);
	$km_qtde_calculada = pg_fetch_result($resI,0,'km_qtde_calculada');
	extract($dados_os);
        
        if (!empty($data_atendimento)) {
            $data_abertura   = explode(" ", $data_atendimento);
            list($d_a,$m_a,$y_a) = explode("-", $data_abertura[0]);
            $data_abertura      = str_pad($d_a,2,'0',STR_PAD_LEFT)."/".str_pad($m_a,2,'0',STR_PAD_LEFT)."/".$y_a;
        } else {
            $data_abertura = '';
        }

        if (!empty($data_encerramento)) {
            $data_fechamento   = explode(" ", $data_encerramento);
            list($d_f,$m_f,$y_f) = explode("-", $data_fechamento[0]);
            $data_fechamento      = str_pad($d_f,2,'0',STR_PAD_LEFT)."/".str_pad($m_f,2,'0',STR_PAD_LEFT)."/".$y_f;
        } else {
            $data_fechamento = '';
        }
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
                <td class='titulo_tabela tac' colspan='100%' style="background-color: green !important;" >Quantidade de KM Calculado Ida / Volta</td>
            </tr>
            <tr>
                <td width="150" class="tac" style="font-size:26px; font-weight:bold; color:#000;">
                    <?php echo number_format($km_qtde_calculada,2,',','.');?>
                </td>
            </tr> 
	</table>

        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Ordem de Serviço</td>
            </tr>
            <tr>
                <td width="150" class="tac" style="font-size:26px; font-weight:bold; color:orange;">
                    <?php echo $os;?>
                </td>
                <td class='titulo_coluna' width="100">Data Abertura</td>
                <td><?php echo $data_abertura;?></td>
                <td class='titulo_coluna' width="100">Data Fechamento</td>
		<td><?php echo $data_fechamento;?></td>
		<td class='titulo_coluna' width="100">Status</td>
                <td><?php echo $status;?></td>
            </tr> 
	</table>

	<table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Posto</td>
            </tr>
	    <tr>
		<td class='titulo_coluna'>Código</td>
		<td nowrap><?php echo $codigo_posto;?></td>
                <td class='titulo_coluna'>Nome</td>
		<td nowrap><?php echo $posto;?></td>
		<td class='titulo_coluna'>Cidade</td>
		<td nowrap><?php echo $cidade_posto;?></td>
		<td class='titulo_coluna'>Estado</td>
		<td nowrap><?php echo $uf_posto;?></td>
	    </tr>
	    <tr>
		<td class='titulo_coluna'>CEP</td>
		<td nowrap><?php echo $cep_posto;?></td>
		<td class='titulo_coluna'>Endereço</td>
		<td nowrap colspan='5'><?php echo $endereco_posto;?></td>
	    </tr>
        </table>
               
        <table align="center" id="resultado_os" class='table table-bordered table-large' >
            <tr>
                <td class='titulo_tabela tac' colspan='100%'>Informações do Cliente</td>
            </tr>
            <tr>
                <td class='titulo_coluna'>Cliente</td>
                <td nowrap colspan='5'><?php echo $cliente;?></td>
            </tr>
            <tr>    
                <td class='titulo_coluna'>Endereço</td>
		<td ><?php echo $endereco_cliente;?></td>       
		<td class='titulo_coluna'>Número</td>
		<td ><?php echo $n_cliente;?></td> 
                <td class='titulo_coluna'>CEP</td>
                <td colspan="3"><?php echo $cep_cliente;?></td>               
            </tr>
	    <tr> 
		<td class='titulo_coluna'>Bairro</td>
                <td><?php echo $bairro_cliente;?></td>    
                <td class='titulo_coluna'>Cidade</td>
                <td><?php echo $cidade_cliente;?></td>    
                <td class='titulo_coluna'>Estado</td>
                <td colspan="3"><?php echo $uf_cliente;?></td>            
            </tr>
        </table>
        
    <?php 
    }

/* Rodapé */
    include 'rodape.php';
?>
