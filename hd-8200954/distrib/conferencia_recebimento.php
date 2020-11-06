<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$login_fabrica_distrib = 10;
$numero_nf = "";
$status_nf = "";
if(filter_input(INPUT_POST, "status_nf")){
    $numero_nf = filter_input(INPUT_POST,'numero_nf',FILTER_SANITIZE_STRING);
    $status_nf = filter_input(INPUT_POST,'status_nf');
}

$condicao = "";
if(!empty($status_nf) || !empty($numero_nf)){

    if(!empty($status_nf)){
        switch ($status_nf) {
            case 1: $data_inicial = date('Y-m-d', strtotime('-360 days'));
                //$data_final   = date('Y-m-d');
                //$condicao     = " AND tbl_faturamento_item.qtde_quebrada IS NOT NULL AND emissao BETWEEN '$data_inicial' AND '$data_final' ";
                $condicao     = " AND tbl_faturamento_item.qtde_quebrada IS NOT NULL ";
                break;
            
            case 2: $condicao = " AND tbl_faturamento_item.qtde_quebrada IS NULL";
                break;
        }        
    }
 
    if (!empty($posto_codigo)) {
        $condicao .= " AND tbl_pedido.posto = $posto_codigo ";
    }

    if(!empty($numero_nf)) {
        $numero_nf = trim($numero_nf);
        $condicao .= " AND tbl_faturamento.nota_fiscal = '$numero_nf' ";
    }
    
    // $sql = "SELECT DISTINCT tbl_faturamento.faturamento,
    //         tbl_faturamento.nota_fiscal,
    //         tbl_faturamento.serie,
    //         tbl_faturamento.emissao,
    //         faturamento_total_peca.total_peca,
    //         faturamento_total_peca.total_faltante,
    //         tbl_tipo_pedido.garantia_antecipada,
    //         tbl_posto_fabrica.codigo_posto,
    //         tbl_posto.nome
    //     FROM tbl_faturamento_item
    //         INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
    //         INNER JOIN (SELECT sum(tbl_faturamento_item.qtde) AS total_peca, 
    //                 sum(tbl_faturamento_item.qtde_quebrada) AS total_faltante, 
    //                 tbl_faturamento_item.faturamento 
    //             FROM tbl_faturamento_item
    //             INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento 
    //             INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido
    //                 AND tbl_pedido.fabrica = {$login_fabrica_distrib}
    //             WHERE tbl_faturamento.fabrica = {$login_fabrica_distrib}
    //             $condicao
    //             GROUP BY tbl_faturamento_item.faturamento
    //         ) AS faturamento_total_peca ON faturamento_total_peca.faturamento = tbl_faturamento.faturamento
    //         INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido
    //             AND tbl_pedido.fabrica = {$login_fabrica_distrib}
    //         INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
    //             AND tbl_tipo_pedido.fabrica = {$login_fabrica_distrib}
    //          INNER JOIN tbl_posto ON tbl_faturamento.posto = tbl_posto.posto
    //         INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
    //         INNER JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
    //     WHERE tbl_faturamento.fabrica = {$login_fabrica_distrib} 
    //         AND tbl_pedido.posto <> 6359
    //         AND tbl_tipo_posto.distribuidor IS TRUE
    //         AND tbl_faturamento_item.qtde > 0
    //         {$condicao}            
    //     ORDER BY emissao DESC";

    $sql = "SELECT  DISTINCT tbl_peca.fabrica, 
                    tbl_faturamento.faturamento,
                    tbl_faturamento.nota_fiscal,
                    tbl_faturamento.serie,
                    tbl_faturamento.emissao,
                    tbl_tipo_pedido.garantia_antecipada,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_faturamento_item.nota_fiscal_origem,
                    tbl_faturamento_item.obs_conferencia,
                    sum(tbl_faturamento_item.qtde) as total_peca,
                    sum(tbl_faturamento_item.qtde_quebrada) as total_faltante
                FROM tbl_faturamento_item
                    INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                    INNER JOIN tbl_peca ON tbl_faturamento_item.peca= tbl_peca.peca
                    INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido
                    INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                    INNER JOIN tbl_posto ON tbl_faturamento.posto = tbl_posto.posto
                    INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = tbl_peca.fabrica
                    INNER JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                    INNER JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica and ativo_fabrica
                WHERE tbl_faturamento.fabrica = $login_fabrica_distrib
                    AND tbl_pedido.posto <> 6359
                    AND tbl_posto_fabrica.fabrica = 153
                    --fixo fabrica 153 positron
                    AND tbl_tipo_posto.distribuidor IS TRUE
                    AND tbl_faturamento_item.qtde > 0
                    $condicao
                GROUP BY tbl_faturamento.faturamento,
                    tbl_faturamento.nota_fiscal,
                    tbl_faturamento.serie,
                    tbl_faturamento.emissao,
                    tbl_tipo_pedido.garantia_antecipada,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_faturamento_item.nota_fiscal_origem,
                    tbl_faturamento_item.obs_conferencia,
                    tbl_peca.fabrica
                ORDER BY emissao DESC;";
    $resConferencia = pg_query($con, $sql);
    //echo nl2br($sql);
    if(empty($status_nf)){
        $total_faltante = pg_fetch_result($resConferencia, 0, "total_faltante");

        if($total_faltante == ""){
            $status_nf = 2;
        }else{
            $status_nf = 1;
        }
    }
}

if(isset($_POST["status_nf"]) && strlen($status_nf) == 0 && strlen($numero_nf) == 0){
    $msg_erro["msg"][] = "Selecione um status da Nota Fiscal";
}

$title = "Conferência de Recebimento";
$layout_menu = 'pedido';

include '../funcoes.php';

include "menu.php";

// $plugins = array( "nome_plugin" );

// $plugins = array(
//    "shadowbox",
//    "maskedinput",
//    "alphanumeric",
//    "ajaxform",
//    "fancyzoom",
//    "price_format"
// );

// include "../admin/plugin_loader.php";

?>

<style type="text/css">
.table td{
    text-align: center;
}

.table {
    width: 850px;
    margin: 0 auto;
}

input[class=qtde_conferencia] {
    width: 50px;
}

#mensagaem_conferencia {
    overflow: -moz-scrollbars-vertical;
    /*overflow: scroll;*/
}

td[class=qtde_peca]{
    width: 100px;
}
</style>


<link href="../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" />
<link href="../bootstrap/css/extra.css" type="text/css" rel="stylesheet" />
<link href="../css/tc_css.css" type="text/css" rel="stylesheet" />
<link href="../plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" >
<link href="../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" />
<link href='../plugins/shadowbox_lupa/shadowbox.css' type='text/css' rel='stylesheet' />


<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="../bootstrap/js/bootstrap.js"></script>
<script src='../plugins/jquery.alphanumeric.js'></script>
<script src='../plugins/jquery.maskedinput_new.js'></script>
<script src='../plugins/jquery.form.js'></script>
<script src='../plugins/FancyZoom/FancyZoom.js'></script>
<script src='../plugins/FancyZoom/FancyZoomHTML.js'></script>
<script src='../plugins/price_format/jquery.price_format.1.7.min.js'></script>
<script src='../plugins/price_format/config.js'></script>
<script src='../plugins/price_format/accounting.js'></script>
<script src='../plugins/shadowbox_lupa/shadowbox.js'></script>

<script type="text/javascript">
    $(function(){
        Shadowbox.init();

        //lupa posto
        $("span[rel=lupa]").click(function() {
            $.lupa($(this));
        });

        $("button[id^=btn_conferir_]").click(function(){
            var faturamento         = this.id.replace(/\D/g, "");
            var nota_fiscal         = $("#nf_"+faturamento).val();
            var serie               = $("#serie_"+faturamento).val();
            var garantia_antecipada = $("#garantia_antecipada_"+faturamento).val();

            $("input.faturamento").val(faturamento);
            
            Shadowbox.open({
                content: "conferencia_peca.php?faturamento="+faturamento+"&nf="+nota_fiscal+"&serie="+serie+"&garantia_antecipada="+garantia_antecipada,
                player: "iframe", 
                width: 900, 
                height: 500,

                options: {
                    enableKeys: false
                }
            });
        });

        //pop-up de conferido
        $(document).on("click","button[id^=btn_conferido_]",function(){
            var faturamento         = this.id.replace(/\D/g, "");
            var nota_fiscal         = $("#nf_"+faturamento).val();
            var serie               = $("#serie_"+faturamento).val();
            var garantia_antecipada = $("#garantia_antecipada_"+faturamento).val();
            var nota_fiscal_origem  = $("#nota_fiscal_origem_"+faturamento).val();
            var obs_conferencia     = $("#obs_conferencia_"+faturamento).val();

            console.log(nota_fiscal_origem);
            console.log(obs_conferencia);

            $("input.faturamento").val(faturamento);
            
            Shadowbox.open({
                content: "conferencia_peca.php?faturamento="+faturamento+"&nf="+nota_fiscal+"&serie="+serie+"&garantia_antecipada="+garantia_antecipada+"&nf_origem="+nota_fiscal_origem+"&obs_conf="+obs_conferencia+"&btn_conferido=ok",
                player: "iframe", 
                width: 900, 
                height: 500,

                options: {
                    enableKeys: false
                }
            });
        });
    });

    function conferencia_realizada(faturamento){
        $("#conferencia_faturamento_"+faturamento).html("");
        $("#conferencia_faturamento_"+faturamento).html("<button type='button' class='btn btn-small btn-info' data-loading-text='Aguarde...' id='btn_conferido_"+faturamento+"'>Conferido</button>");
    }

    function conferencia_tot_realizada(faturamento,conf_tot,nt_fiscal,obs_conf){
        console.log(nt_fiscal);
        console.log(obs_conf);
        $("#conferencia_tot_faturamento_"+faturamento).html("");
        $("#conferencia_tot_faturamento_"+faturamento).html(conf_tot);
        $("#nota_fiscal_origem_td_"+faturamento).html(nt_fiscal);        
        $("#nota_fiscal_origem_"+faturamento).val(nt_fiscal);        
        $("#obs_conferencia_"+faturamento).val(obs_conf);
    }

    function retorna_posto(retorno) {
        $("#codigo_posto").val(retorno.cnpj);
        $("#descricao_posto").val(retorno.nome);
        $("#posto_codigo").val(retorno.posto);
    }
</script>
<head>
<title>Conferência Recebimento</title>
</head>

<?php
if (count($msg_erro["msg"]) > 0) {
?><br/>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form id="fm_conferencia_recebimento" action="<?=$PHP_SELF?>" method="POST" class="form-search form-inline tc_formulario" >
    <input type="hidden" class="faturamento" value="" />
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<div class='container tc_container'>
	    <div class="row-fluid">
	        <div class="span2" ></div>

	        <div class="span4" >
	            <div class="control-group" >
	                <label class="control-label" for="numero_nf" >Nº Nota Fiscal</label>

	                <div class="controls controls-row" >
                        <input type="text" name="numero_nf" id="numero_nf" class="span5" value="<?=$numero_nf?>" />
	                </div>
	            </div>
	        </div>

	        <div class="span4" >
                <div class='control-group'>
                    <label class="control-label" for="descricao_posto" >Status da Nota Fiscal</label>

                    <div class="controls controls-row" >
                        <h5 class='asteristico'>*</h5>
                        <select id="status_nf" name="status_nf" class="span12">
                            <option value="" >Selecione</option>
                            <option value="1" <?php echo $status_nf == "1" ? "selected" : ""; ?>>Conferida</option>
                            <option value="2" <?php echo $status_nf == "2" ? "selected" : ""; ?>>Não Conferida</option>
                        </select>
                    </div>
                </div>
	        </div>
            <div class="span2"></div>
	    </div>
        <br>

        <div class="row-fluid">
            <div class="span2" ></div>
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" for="codigo_posto" >CNPJ Posto</label>

                    <div class="controls controls-row" >
                        <div class="span10 input-append" >
                            <input type="text" name="codigo_posto" id="codigo_posto" class="span12" value="<? echo $codigo_posto ?>" />
                            <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" for="descricao_posto" >Nome Posto</label>

                    <div class="controls controls-row" >
                        <div class="span11 input-append" >
                            <input type="text" name="descricao_posto" id="descricao_posto" class="span12" value="<? echo $descricao_posto ?>" />
                            <span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2">
                <input type="hidden" name="posto_codigo" id="posto_codigo" value="<? echo $posto_codigo ?>" />
            </div>
        </div>

	</div>
    <div class="row-fluid">
            <br/>
            <div class="span5" ></div>

            <div class="span4" >
                <div class="control-group" >
                    <div class="controls controls-row" >
                        <input type="submit" class="btn btn-primary" id="pesquisar_nota" value="Pesquisar"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php 
    if(pg_num_rows($resConferencia) > 0){ ?>
	<table id="resultado_pesquisa" class='table table-striped table-bordered table-large' >
		<thead>
			<tr class='titulo_coluna'>
                <th>Posto</th>
				<th>Nota Fiscal</th>
                <th>NF Devolução</th>
				<th>Série</th>
				<th>Data de Emissão</th>
                <th>Quantidade Faturada</th>
				<th>Quantidade Recebida</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				$count = pg_num_rows($resConferencia);
				for($i=0; $i < $count; $i++){
                    $nota_fiscal         = pg_fetch_result($resConferencia, $i, "nota_fiscal");
                    $nota_fiscal_origem  = pg_fetch_result($resConferencia, $i, "nota_fiscal_origem");
                    $obs_conferencia     = pg_fetch_result($resConferencia, $i, "obs_conferencia");
                    $serie               = pg_fetch_result($resConferencia, $i, "serie");
                    $data_emissao        = pg_fetch_result($resConferencia, $i, "emissao");
                    $total_peca          = pg_fetch_result($resConferencia, $i, "total_peca");
                    $total_faltante      = pg_fetch_result($resConferencia, $i, "total_faltante");
                    $faturamento         = pg_fetch_result($resConferencia, $i, "faturamento");
                    $garantia_antecipada = pg_fetch_result($resConferencia, $i, "garantia_antecipada");
                    $codigo_posto        = pg_fetch_result($resConferencia, $i, "codigo_posto");
                    $nome_posto          = pg_fetch_result($resConferencia, $i, "nome");

                    list($ano, $mes, $dia) = explode("-",$data_emissao);
                    $data_emissao          = $dia."/".$mes."/".$ano;
                    if (strlen($total_faltante) > 0) {
                        $total_faltante = $total_peca - $total_faltante;
                    }
                    
					?>
                    <tr>
                    <td><?=$codigo_posto.' - '. $nome_posto?></td>
					<td>
                        <?=$nota_fiscal?>
                        <input type="hidden" id="nf_<?=$faturamento?>" value="<?=$nota_fiscal?>">
                        <input type="hidden" id="serie_<?=$faturamento?>" value="<?=$serie?>">
                        <input type="hidden" id="garantia_antecipada_<?=$faturamento?>" value="<?=$garantia_antecipada?>">                        
                        <input type="hidden" id="nota_fiscal_origem_<?=$faturamento?>" value="<?=$nota_fiscal_origem?>">
                        <input type="hidden" id="obs_conferencia_<?=$faturamento?>" value="<?=$obs_conferencia?>">
                    </td>
                    <td id="nota_fiscal_origem_td_<?=$faturamento?>"><?=$nota_fiscal_origem?></td>
					<td><?=$serie?></td>
					<td><?=$data_emissao?></td>
                    <td><?=$total_peca?></td>
					<td id="conferencia_tot_faturamento_<?=$faturamento?>"><?=$total_faltante?></td>
					<td id="conferencia_faturamento_<?=$faturamento?>">
                    <?php if($status_nf == 2){
                    ?>
                        <button type="button" class="btn btn-small" data-loading-text="Aguarde..." id="btn_conferir_<?=$faturamento?>">Conferir</button>
                    <?php }else{ ?>
                        <button type="button" class="btn btn-small btn-info" data-loading-text="Aguarde..." id="btn_conferido_<?=$faturamento?>">Conferido</button>
                        <!-- <label class="label label-info">CONFERIDO</label> -->
                    <?php }
                    ?>
                    </td>
                    </tr>
					<?php
				}
			?>
		</tbody>
	</table>
<?php }else if(count($msg_erro["msg"]) == 0 && (!empty($status_nf) || !empty($numero_nf))){
        ?>
        <div class="container">
            <div class="alert alert-warning"><h4>Não foi encontrado nenhum resultado.</h4></div>
        </div>
        <?php
    } ?>
    <br>
</form>

<?php
//include "rodape.php"; 
?>
