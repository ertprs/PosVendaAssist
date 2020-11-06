<?php
$admin_privilegios="cadastros";
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

if (isset($_REQUEST['ajax'])) {

	if (in_array($_REQUEST['ajax'] , ['cadastro_filha', 'excluir_filha'])) {

		$referencia_mae = $_REQUEST['referencia_mae'];
		$referencia_filha = $_REQUEST['referencia_filha'];

		$sqlMae = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia_mae' AND fabrica = $login_fabrica ";
		$resMae = pg_query($con, $sqlMae);
		$pecaMae = pg_fetch_result($resMae, 0, 'peca');

		$sqlFilha = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia_filha' AND fabrica = $login_fabrica ";
		$resFilha = pg_query($con, $sqlFilha);
		$pecaFilha = pg_fetch_result($resFilha, 0, 'peca'	);
	}	

	if ($_REQUEST['ajax'] == 'cadastro_filha') {
		$sql = "INSERT INTO tbl_peca_container (fabrica, peca_mae, peca_filha) VALUES ('$login_fabrica', '$pecaMae', '$pecaFilha');";
	}	

	if ($_REQUEST['ajax'] == 'excluir_filha') {
		$sql = "DELETE FROM tbl_peca_container WHERE fabrica = $login_fabrica AND peca_mae = $pecaMae AND peca_filha = $pecaFilha ; ";
	}

	pg_query($con, $sql);
	if (pg_last_error() == 0) {
		 echo "ok";
	}
	exit;
}

if (isset($_REQUEST['peca_referencia'])) {
	$referencia = $_REQUEST['peca_referencia'];
	$sqlConsulta = "SELECT 
						(SELECT referencia FROM tbl_peca rf WHERE tbl_peca_container.peca_filha = rf.peca AND fabrica = $login_fabrica ) as referencia_filha, 
						(SELECT descricao FROM tbl_peca rd WHERE tbl_peca_container.peca_filha = rd.peca AND fabrica = $login_fabrica ) as descricao_filha 
					FROM tbl_peca_container 
						JOIN tbl_peca ON tbl_peca.peca = peca_mae 
					WHERE referencia = '$referencia' 
					AND tbl_peca_container.fabrica = $login_fabrica ";
	$resConsulta = pg_query($con, $sqlConsulta);
}
$layout_menu = "cadastro";
$title = "CADASTRO DE VINCULO ENTRE PEÇAS";
include "cabecalho_new.php";
$plugins = array(
    "autocomplete",
    "shadowbox",
    "alphanumeric",
    "price_format",
    "multiselect"
);

include("plugin_loader.php");
?>
<script language="javascript">

    $(function() {
        Shadowbox.init();
        $(document).on("click", "span[rel=lupa]", function () {
            $.lupa($(this),['posicao']);
        });
        $(document).on("click","button[id^=gravar_linha_]",function(){
            var linha = $(this).parents("tr");
        	var posicao = $(this).data('posicao');
        	var referencia = $("#peca_filha_" + posicao).val();

            if(referencia != ""){
                $.ajax({
                    url: "cadastro_vinculo_pecas.php",
                    type: "POST",
                    data: {
                        ajax: 'cadastro_filha',
                        referencia_filha: referencia,
                        referencia_mae: $("#peca_referencia").val(),
                    },
                })
                .done(function(data){
                    data = data.split('|');
                    if(data[0] == "ok"){
                        $(linha).find("button[id^=gravar_linha_]").hide();

                        $(linha).find(".alert-success").show();
                        setTimeout(function(){
                            $(linha).find(".alert-success").hide();
                            $(linha).find("button[id^=remove_linha_]").show();
                        },1000);
                    } else {
                        alert(data[1]);
                    }
                });
            }
            adicionaLinha();
        });
        $(document).on("click","button[id^=remove_linha_]",function(){
        	var posicao = $(this).data('posicao');
        	var referencia = $("#peca_filha_" + posicao).val();
        	var linha = $(this).parents("tr");

            $.ajax({
                url: "cadastro_vinculo_pecas.php",
                type: "POST",
                data: {
                    ajax: 'excluir_filha',
                    referencia_filha: referencia,
                    referencia_mae: $("#peca_referencia").val(),
                },
            })
            .done(function(data){
                console.log(data);
                if(data == "ok"){
                    $(linha).html("<td colspan='100%' class='tac'><div class='alert-success'>Item excluído com sucesso</div></td>");
                    setTimeout(function(){
                        $(linha).hide();
                    },1000);
                }
            });
        });

    });
    function retorna_peca(retorno){
        var posicao = retorno.posicao;
        if (posicao == null || posicao == ''){
        	$("#peca_referencia").val(retorno.referencia);
	        $("#peca_descricao").val(retorno.descricao); 
        } else {
	        $("#peca_filha_"+posicao).val(retorno.referencia);
	        $("#descricao_filha_"+posicao).val(retorno.descricao);        	
        }
    }

    function adicionaLinha () {
        var qtde_linhas = $("table.pecas > tbody > tr[id!=linhaModelo]").length;
        qtde_linhas++;
        var newTr = $("#linhaModelo").clone();
        $("table.pecas > tbody > tr[id!=linhaModelo]:last").after("<tr>"+$(newTr).html().replace(/modelo/g, qtde_linhas)+"</tr>");
    }
</script>
<style type="text/css">
    #linhaModelo{
        display: none;
    }
</style>
<div class="alert alert-success" id="exclui" style="display:none;">
	<h4>Excluído com sucesso</h4>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_cadastro' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' id="form_pesquisa">
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class="row-fluid">

		<div class="span2"></div>

		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peça Principal</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $_POST['peca_referencia'] ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia"  />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça Principal</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $_POST['peca_descricao'] ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao"  />
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>

	</div>
	<p>
		<br/>
		<button class='btn' id="btn_acao" type="submit" >Pesquisar</button>
		<a href='?acao=listarTodos' class='btn btn-primary'>Listar Todas</a>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p>

	<br />
</form>
<?php if ($_REQUEST['peca_referencia']) {  ?>
    <table class='table table-striped table-bordered table-hover table-<?=$table_large_fixed;?> pecas'>
    	<thead>
    		<tr class='titulo_tabela'>
    			<th width="20%">Peça</th>
    			<th>Descrição</th>
    			<th width="10%">Ação</th>
    		</tr>
    	</thead>
    	<tbody>
			<?php 
				$i = 0;
				$total_itens = pg_num_rows($resConsulta);
				if (pg_num_rows($resConsulta) > 0 ) { 
				for($i = 0; $i < $total_itens; $i++){
					$referencia = pg_fetch_result($resConsulta, $i, 'referencia_filha');
					$descricao = pg_fetch_result($resConsulta, $i, 'descricao_filha');
			?>
	    		<tr>
	    			<td>
	    				 <div class='input-append'>
                            <input type="text" id="peca_filha_<?=$i?>" name="peca_filha_<?=$i?>" class='span2 inp-peca' maxlength="20" value="<? echo $referencia;?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="referencia" />
                        </div>
	    			</td>
	    			<td>
	    				 <div class='input-append'>
                            <input type="text" id="descricao_filha_<?=$i?>" name="descricao_filha_<?=$i?>" class='span6 inp-descricao' value="<? echo $descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="descricao"  />
                        </div>
	    			</td>
	    			<td>
	    				<button class='btn btn-danger btn-small' id="remove_linha_<?=$i?>" data-posicao="<?=$i?>" type="button" >Excluir</button>
	    			</td>
	    		</tr>
    		<?php } 
    		}
    		$i++
    		?>
    		<tr>
    			<td>
    				 <div class='input-append'>
                        <input type="text" id="peca_filha_<?=$i?>" name="peca_filha_<?=$i?>" class='span2 inp-peca' maxlength="20" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="referencia" />
                    </div>
    			</td>
    			<td>
    				 <div class='input-append'>
                        <input type="text" id="descricao_filha_<?=$i?>" name="descricao_filha_<?=$i?>" class='span6 inp-descricao' >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="descricao"  />
                    </div>
    			</td>
    			<td>
    				<button class='btn btn-small' id="gravar_linha_<?=$i?>" rel="" data-posicao="<?=$i?>" type="button" >Gravar</button>
                    <button class='btn btn-danger btn-small' id="remove_linha_<?=$i?>" type="button" data-posicao="<?=$i?>" style="display:none;">Excluir</button>
    			</td>
    		</tr>
    		<tr id="linhaModelo">
    			<td>
    				 <div class='input-append'>
                        <input type="text" id="peca_filha_modelo" name="peca_filha_modelo" class='span2 inp-peca' maxlength="20" >
                        <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" posicao="modelo" parametro="referencia" />
                    </div>
    			</td>
    			<td>
    				 <div class='input-append'>
                        <input type="text" id="descricao_filha_modelo" name="descricao_filha_modelo" class='span6 inp-descricao' >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="peca" posicao="modelo" parametro="descricao"  />
                    </div>
    			</td>
    			<td>
    				<button class='btn btn-small' id="gravar_linha_modelo" data-posicao="modelo" type="button" >Gravar</button>
                    <button class='btn btn-danger btn-small' id="remove_linha_modelo" type="button" data-posicao="<?=$i?>" style="display:none;">Excluir</button>
    			</td>
    		</tr>
    	</tbody>
	</table>
<?php } ?>

<?php if ($_GET['acao']=='listarTodos') {  



	$sql = "SELECT distinct tbl_peca.referencia,
			tbl_peca.descricao
			FROM tbl_peca_container
			JOIN tbl_peca ON tbl_peca.peca = tbl_peca_container.peca_mae and tbl_peca.fabrica = $login_fabrica
			WHERE tbl_peca_container.fabrica = $login_fabrica
			AND tbl_peca.ativo is true
	


";

//	echo nl2br($sql);

	$res = pg_query($con,$sql);


	if (pg_num_rows($res)>0) {


?>
    <table class='table table-striped table-bordered table-hover table-<?=$table_large_fixed;?> pecas'>
    	<thead>
    		<tr class='titulo_tabela'>
    			<th width="20%">Peça Principal</th>
    			<th>Descrição</th>
    			<th width="10%">Ação</th>
    		</tr>
    	</thead>
    	<tbody>

	<?php

		for($i=0;$i<pg_num_rows($res);$i++) {

			$referencia = pg_result($res,$i,referencia);
			$descricao  = pg_result($res,$i,descricao);


	?>
	    		<tr>
	    			<td>
                            		<?=$referencia; ?>
	    			</td>
	    			<td>
                            		<?=$descricao?>
	    			</td>
	    			
				<td>
	    				<a href='?peca_referencia=<?=$referencia?>' class='btn btn-success btn-small'>Pesquisar</a>
	    			</td>
	    		</tr>
	<?php
		}
	?>
    	</tbody>
	</table>
<?php }

 } ?>
