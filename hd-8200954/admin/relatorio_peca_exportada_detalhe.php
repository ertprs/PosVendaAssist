<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include "funcoes.php";

# Os dados vêm do programa anterior - relatorio_exportacao.php
$data_inicial       = filter_input(INPUT_GET,'data_inicial');
$data_final         = filter_input(INPUT_GET,'data_final');
$codigo_posto       = filter_input(INPUT_GET,'codigo_posto');
$peca_referencia    = filter_input(INPUT_GET,'peca_referencia');

if ((strlen($data_inicial) > 0) and (strlen($data_final) > 0)){
	if (strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa") {
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    } else {
        $msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_inicial";
    }

    if (strlen($data_final)>0 and $data_final <> "dd/mm/aaaa") {
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    } else {
        $msg_erro["msg"][]    ="Data Inválida";
        $msg_erro["campos"][] = "data_final";
    }

	$msg_erro = "";
	# Novamente, só por garantia, conferir intervalo
	$sql_data_intervalo="SELECT '$xdata_final'::date - interval '31 days' > '$xdata_inicial'::date AS extrapola";
	$resDI = pg_exec($con,$sql_data_intervalo);
	$extrapolou = pg_result ($resDI,0,0);

	if($extrapolou=='f'){
		# Se foi passado o posto, pesquisa específica
		if (strlen($codigo_posto)>0){
			$sql_posto="SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto'";
			$resP = pg_exec($con,$sql_posto);
			$postoid = pg_result ($resP,0,0);
			$cond = " AND tbl_pedido.posto = $postoid";
        }
        $sqlDetalhe = "
            SELECT  tbl_posto_fabrica.codigo_posto  ,
                    tbl_posto.nome  ,
                    tbl_pedido.posto    ,
                    TO_CHAR(tbl_pedido.exportado,'DD/MM/YYYY')       AS exportado   ,
                    tbl_pedido_item.pedido  ,
                    tbl_pedido_item.peca    ,
                    tbl_pedido_item.qtde
            FROM    tbl_pedido
            JOIN    tbl_pedido_item     USING (pedido)
            JOIN    tbl_peca            USING (peca)
            JOIN    tbl_posto           USING (posto)
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_pedido.posto
                                        AND tbl_posto_fabrica.fabrica   = tbl_pedido.fabrica
            WHERE   tbl_pedido.fabrica          = $login_fabrica
            AND     tbl_pedido.status_pedido    NOT IN (14)
            AND     exportado                   BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
            AND     tbl_peca.referencia         = '$peca_referencia'
            $cond
        ";

        $resDetalhe = pg_query($con,$sqlDetalhe);


        $sqlPeca = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
        $resPeca = pg_query($con,$sqlPeca);
        $pecas = pg_fetch_object($resPeca);

        if (pg_numrows($resDetalhe) > 0){
            $fp = fopen ("/tmp/relatorio_peca_exportada_detalhe-$login_fabrica.html","w");

            fputs($fp,"<table>");
            fputs($fp,"<thead>");
            fputs($fp,"<tr>");
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

	</head>
	<body>
<table id="relatorio_peca_exportada_detalhe" class='table table-striped table-bordered table-hover table-fixed'>
    <thead>
        <tr class='titulo_coluna'>
<?php
            if (strlen($codigo_posto)>0){

?>
        <th colspan='4'>Relatório de exportação da peça <?=$peca_referencia." - ".$pecas->descricao?> <br />de <?=$data_inicial?> a <?=$data_final?> do posto <?=$posto_nome?></th>
<?php
                fputs($fp,"<th colspan='4'>Relatório de exportação da peça $peca_referencia - $pecas->descricao <br />de $data_inicial a $data_final do posto $posto_nome</th>");
            }else{
?>
        <th colspan='4'>Relatório de exportação da peça <?=$peca_referencia." - ".$pecas->descricao?> <br />de <?=$data_inicial?> a <?=$data_final?> de todos os postos</th>
<?php
                fputs($fp,"<th colspan='4'>Relatório de exportação da peça $peca_referencia - $pecas->descricao <br />de $data_inicial a $data_final de todos os postos</th>");
            }

?>
        </tr>
        <tr class='titulo_coluna'>
            <th>Pedido</th>
            <th>Posto</th>
            <th>Exportado</th>
            <th>Qtde</th>
        </tr>
    </thead>
    <tbody>
<?php
                fputs($fp,"</tr>");
                fputs($fp,"<tr>");
                fputs($fp,"<th>Pedido</th>");
                fputs($fp,"<th>Posto</th>");
                fputs($fp,"<th>Exportado</th>");
                fputs($fp,"<th>Qtde</th>");
                fputs($fp,"</tr>");
                fputs($fp,"</thead>");
                fputs($fp,"<tbody>");
            while ($result = pg_fetch_object($resDetalhe)) {
?>
        <tr>
            <td nowrap><a href="pedido_admin_consulta.php?pedido=<?=$result->pedido?>" target="_blank"><?=$result->pedido?></a></td>
            <td nowrap><?=$result->codigo_posto." - ".$result->nome?></td>
            <td nowrap><?=$result->exportado?></td>
            <td nowrap><?=$result->qtde?></td>
        </tr>
<?php
                fputs($fp,"<tr>");
                fputs($fp,"<td nowrap>".$result->pedido."</td>");
                fputs($fp,"<td nowrap>".$result->codigo_posto." - ".$result->nome."</td>");
                fputs($fp,"<td nowrap>".$result->exportado."</td>");
                fputs($fp,"<td nowrap>".$result->qtde."</td>");
                fputs($fp,"</tr>");
            }
            fputs($fp,"</tbody>");
            fputs($fp,"</table>");

            fclose($fp);

            $data = date("Y-m-d").".".date("H-i-s");
            rename("/tmp/relatorio_peca_exportada_detalhe-$login_fabrica.html","xls/relatorio_peca_exportada_detalhe_$login_fabrica.$data.xls");
?>
    </tbody>
</table>

<p style="text-align:center">
    <a href="xls/relatorio_peca_exportada_detalhe_<?=$login_fabrica.".".$data?>.xls" class="btn btn-success" target="_blank" role="button"><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a>
</p>
<script type="text/javascript">
$.dataTableLoad({
    table: "#relatorio_peca_exportada_detalhe",
    type: "basic"
});
</script>
<?php
		}
	}else{
		$msg_erro = "O intervalo de pesquisa não pode exceder a 31 dias!";
	}
}else{
	$msg_erro = "$title - exibe resultados baseados em uma tela anterior.";
}
	if (strlen($msg_erro)>0){ ?>
			<br>
			<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
			<tr>
				<td><?echo $msg_erro?></td>
			</tr>
			<? echo "</table>";
		}

?>
</body>
</html>
