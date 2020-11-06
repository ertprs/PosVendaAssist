<?php
include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_usuario.php';

$numero_nf = "";
$status_nf = "";
if(filter_input(INPUT_POST, "status_nf")){
    $numero_nf = filter_input(INPUT_POST,'numero_nf',FILTER_SANITIZE_STRING);
    $status_nf = filter_input(INPUT_POST,'status_nf');
}

if($_POST['buscaCorreios']){
    $objeto = $_POST['objeto'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://correiosrastrear.com/{$objeto}");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    $resultado = curl_exec($ch);
    curl_close($ch);
    echo utf8_decode($resultado);
    exit;
}

$condicao = "";

    if(!empty($status_nf)){
        switch ($status_nf) {
            case 1: $data_inicial = date('Y-m-d', strtotime('-30 days'));
                $data_final   = date('Y-m-d');
                $condicao     = " AND tbl_faturamento_item.qtde_quebrada IS NOT NULL AND emissao BETWEEN '$data_inicial' AND '$data_final' ";
                break;
            
            case 2: $condicao = " AND tbl_faturamento_item.qtde_quebrada IS NULL";
                break;
        }
    } else {
        $condicao = " AND tbl_faturamento_item.qtde_quebrada IS NULL";
    }

    if(!empty($numero_nf)) {
        $numero_nf = trim($numero_nf);
        $condicao .= " AND tbl_faturamento.nota_fiscal = '$numero_nf' ";
    }

    if ($login_fabrica == 160 or $replica_einhell) {
        $condicaoTelecontrol = " OR tbl_faturamento.fabrica = 10";
    }

    if ($login_fabrica == 164) {
        $join_tbl_os = " INNER JOIN tbl_os ON tbl_os.os = tbl_faturamento_item.os AND tbl_os.fabrica = 164 ";
        $cond_tbl_os = " AND tbl_os.data_abertura >= '2019-11-01' ";
    }
    
    $sql = "SELECT DISTINCT 
            tbl_faturamento.faturamento,
            tbl_faturamento.nota_fiscal,
            tbl_faturamento.serie,
            tbl_faturamento.emissao,
            tbl_faturamento.conferencia as data_recebimento,
            tbl_faturamento.conhecimento as codigo_rastreio,
            faturamento_total_peca.total_peca,
            faturamento_total_peca.total_faltante,
            tbl_tipo_pedido.garantia_antecipada,
            (
                SELECT situacao FROM tbl_faturamento_correio 
                WHERE tbl_faturamento_correio.faturamento = tbl_faturamento.faturamento
                ORDER BY tbl_faturamento_correio.data_input DESC
                LIMIT 1
            ) as situacao_correio
        FROM tbl_faturamento_item
            INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
            INNER JOIN (SELECT sum(tbl_faturamento_item.qtde) AS total_peca, 
                    sum(tbl_faturamento_item.qtde_quebrada) AS total_faltante, 
                    tbl_faturamento_item.faturamento 
                FROM tbl_faturamento_item
                INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento 
                WHERE (tbl_faturamento.fabrica = {$login_fabrica} $condicaoTelecontrol)
				AND tbl_faturamento.posto = $login_posto
                $condicao
                GROUP BY tbl_faturamento_item.faturamento
            ) AS faturamento_total_peca ON faturamento_total_peca.faturamento = tbl_faturamento.faturamento
            INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido
                AND tbl_pedido.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                AND tbl_tipo_pedido.fabrica = {$login_fabrica}
            {$join_tbl_os}
        WHERE (tbl_faturamento.fabrica = {$login_fabrica} $condicaoTelecontrol) AND tbl_pedido.posto = {$login_posto}
            AND tbl_faturamento_item.qtde > 0
            {$condicao}
            {$cond_tbl_os}
        ORDER BY emissao DESC";
    $resConferencia = pg_query($con, $sql);

    if(empty($status_nf)){
        $total_faltante = pg_fetch_result($resConferencia, 0, "total_faltante");

        if($total_faltante == ""){
            $status_nf = 2;
        }else{
            $status_nf = 1;
        }
    }

if(isset($_POST["status_nf"]) && strlen($status_nf) == 0 && strlen($numero_nf) == 0){
    $msg_erro["msg"][] = "Selecione um status da Nota Fiscal";
}

$title = "Conferência de Recebimento";
$layout_menu = 'pedido';

include __DIR__.'/funcoes.php';

include "cabecalho_new.php";

$plugins = array( "nome_plugin" );

$plugins = array(
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format"
);

include __DIR__."/admin/plugin_loader.php";

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

<script type="text/javascript">
    $(function(){
        Shadowbox.init();

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

        $(".rastreio").click(function(){
            var obj = $(this).data("codigo");
            $("#historicoCorreios").load("conferencia_recebimento.php .listEvent",{"buscaCorreios":true,"objeto":obj}, function(){
                if($(".listEvent").length){
                    Shadowbox.init();
                    Shadowbox.open({
                        content: "<div style='background-color:#FFF'>"+$("#historicoCorreios").html()+"</div>",
                        player: "html",
                        title:  "Histórico Correios",
                        width:  800,
                        height: 500
                    });
                }else{
                    alert("Não foram encontradas informações sobre esse código.");
                }
            });
        });
    });

    function conferencia_realizada(faturamento){
        $("#conferencia_faturamento_"+faturamento).html("");
        $("#conferencia_faturamento_"+faturamento).html("<label class='label label-info'>CONFERIDO</label>");
    }
</script>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form id="fm_conferencia_recebimento" action="<?=$PHP_SELF?>" method="POST" class="form-search form-inline tc_formulario" >
    <input type="hidden" class="faturamento" value="" />
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<div class='container tc_container'>
	    <div class="row-fluid">
	        <br/>
	        <div class="span2" ></div>

	        <div class="span4" >
	            <div class="control-group" >
	                <label class="control-label" for="numero_nf" >Nº Nota Fiscal</label>

	                <div class="controls controls-row" >
                        <input type="text" name="numero_nf" id="numero_nf" class="span4" value="<?=$numero_nf?>" />
	                </div>
	            </div>
	        </div>

	        <div class="span4" >
                <div class='control-group'>
                    <label class="control-label" for="descricao_posto" >Status da Nota Fiscal</label>

                    <div class="controls controls-row" >
                        <h5 class='asteristico'>*</h5>
                        <select id="status_nf" name="status_nf">
                            <option value="" >Selecione</option>
                            <option value="1" <?php echo $status_nf == "1" ? "selected" : ""; ?>>Conferida</option>
                            <option value="2" <?php echo $status_nf == "2" ? "selected" : ""; ?>>Não Conferida</option>
                        </select>
                    </div>
                </div>
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
    if(pg_num_rows($resConferencia) > 0) { ?>
	<table id="resultado_pesquisa" class='table table-striped table-bordered table-large' style="height: 0px;">
		<thead>
			<tr class='titulo_coluna'>
				<th>Nota Fiscal</th>
				<th>Série</th>
				<th>Data de Emissão</th>
                <th>Quantidade Faturada</th>
				<th>Quantidade Recebida</th>
                <?php 
                    if ((in_array($login_fabrica, array(160)) or $replica_einhell)) {
                ?>
                        <th>Status Correios</th>
                        <th>Código de Rastreio</th>
                        <th>Data de Entrega</th>
                <?php
                    }
                ?>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				$count = pg_num_rows($resConferencia);
				for($i=0; $i < $count; $i++){
                    $nota_fiscal         = pg_fetch_result($resConferencia, $i, "nota_fiscal");
                    $serie               = pg_fetch_result($resConferencia, $i, "serie");
                    $data_emissao        = pg_fetch_result($resConferencia, $i, "emissao");
                    $total_peca          = pg_fetch_result($resConferencia, $i, "total_peca");
                    $total_faltante      = pg_fetch_result($resConferencia, $i, "total_faltante");
                    $faturamento         = pg_fetch_result($resConferencia, $i, "faturamento");
                    $garantia_antecipada = pg_fetch_result($resConferencia, $i, "garantia_antecipada");
                    $data_recebimento    = pg_fetch_result($resConferencia, $i, "data_recebimento");
                    $situacao_correio    = pg_fetch_result($resConferencia, $i, "situacao_correio");
                    $codigo_rastreio     = pg_fetch_result($resConferencia, $i, "codigo_rastreio");

                    list($ano, $mes, $dia) = explode("-",$data_emissao);
                    $data_emissao          = $dia."/".$mes."/".$ano;
                    $total_faltante = (isset($total_faltante)) ? $total_peca - $total_faltante : 0;
					?>
                    <tr>
					<td>
                        <?=$nota_fiscal?>
                        <input type="hidden" id="nf_<?=$faturamento?>" value="<?=$nota_fiscal?>">
                        <input type="hidden" id="serie_<?=$faturamento?>" value="<?=$serie?>">
                        <input type="hidden" id="garantia_antecipada_<?=$faturamento?>" value="<?=$garantia_antecipada?>">
                    </td>
					<td><?=$serie?></td>
					<td><?=$data_emissao?></td>
                    <td><?=$total_peca?></td>
					<td><?=$total_faltante?></td>
                    <?php 
                    if ((in_array($login_fabrica, array(160)) or $replica_einhell)) {

                        $label_class = (strpos($situacao_correio, 'Objeto entregue') !== false) ? "success" : "warning";
                    ?>
                        <td>
                            <label class="label label-<?= $label_class ?>"><?= (empty($situacao_correio)) ? "Aguardando status do correios" : $situacao_correio ?></label>
                        </td>
                        <td><a class="rastreio" data-codigo="<?= $codigo_rastreio ?>"><?= $codigo_rastreio ?></a></td>
                        <td><?= mostra_data($data_recebimento) ?></td>
                    <?php 
                    }
                    ?>
					<td id="conferencia_faturamento_<?=$faturamento?>">
                    <?php 
                    if($status_nf == 2){
                        if ((in_array($login_fabrica, array(160)) or $replica_einhell) && empty($data_recebimento)) {
                        ?>
                            <label class="label label-info">Aguardando Recebimento</label>
                        <?php
                        } else {
                        ?>
                            <button type="button" class="btn btn-small" data-loading-text="Aguarde..." id="btn_conferir_<?=$faturamento?>">Conferir</button>
                        <?php
                        }

                    }else{ ?>
                        <label class="label label-info">CONFERIDO</label>
                    <?php 
                    }
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
            <div class="alert alert-warning"><h4>Nenhum resultado encontrado</h4></div>
        </div>
        <?php
    } ?>
</form>
<div id='historicoCorreios' style='display:none;'></div>
<?php
include "rodape.php"; 
?>
