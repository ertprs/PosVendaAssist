<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$admin_privilegios="auditoria,gerencia";

$layout_menu = "auditoria";
$title = "Auditoria -  Peças Pendentes por Estoque";
$btn_acao = $_POST['acao'];
$data_inicial 	= $_POST['data_inicial_01'];
$data_final 	= $_POST['data_final_01'];
$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";
$msgErrorPattern02 = "A data de consulta deve ser no máximo de 6 meses.";

if(strlen($btn_acao)>0){

	if(!$data_inicial OR !$data_final) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}

	##TIRA A BARRA
	if(count($msg_erro["msg"]) == 0) {
		$dat = explode ("/", $data_inicial );
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(count($msg_erro["msg"]) == 0) {
		$dat = explode ("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}
	}

	if(count($msg_erro["msg"]) == 0) {
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$aux_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$d_fim = explode ("/", $data_final);//tira a barra
		$aux_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($aux_data_final < $aux_data_inicial) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}

		##Fim Validação de Datas
		if(count($msg_erro["msg"]) == 0) {
			$sql = "SELECT '$aux_data_final'::date - INTERVAL '6 MONTHS' > '$aux_data_inicial'::date ";
			$res = pg_query ($con,$sql);
			if (pg_fetch_result($res,0,0) == 't') {
				$msg_erro["msg"][]    = $msgErrorPattern02;
				$msg_erro["campos"][] = "data";
			}
		}
	}


	if(count($msg_erro["msg"]) == 0) {
		$referencia = $_POST['peca_referencia'];
		$descricao = $_POST['peca_descricao'];

		$cond_1 = " 1=1 ";
		$sqlp = "select peca from tbl_peca where referencia='$referencia' and fabrica = $login_fabrica";
		$resp = pg_query($con,$sqlp);

		if(pg_num_rows($resp)>0){
			$peca = pg_fetch_result($resp,0,0);
			$cond_1 = " tbl_pedido_item.peca = $peca ";
		}

		if($login_fabrica == 11){
			$cond_lenoxx = " AND tbl_pedido.distribuidor IS NOT NULL ";
		}

		$sql = "select tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_peca.peca,
			count(tbl_pedido_item.peca) as qtde
			from tbl_pedido_item
			JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca
			JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
			where tbl_pedido.data > '2007-01-01 00:00:00'
			and $cond_1
			AND tbl_pedido.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'";

		if($login_fabrica == 11) {
			$sql .= " AND tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde ";
		} else {
			$sql .= " AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde ";
		}
			
		$sql .= " AND tbl_pedido.fabrica = $login_fabrica
			$cond_lenoxx
			GROUP BY
			tbl_peca.referencia,
			tbl_peca.descricao,tbl_peca.peca
			order by tbl_peca.referencia";
		
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$resultado .="<table class='table table-striped table-bordered table-fixed'>";
			$resultado .="<thead>";
			$resultado .="<tr class='titulo_tabela'>";
			$resultado .="<td colspan='3'>TOTAL DE PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)</td>";
			$resultado .="</tr>";

			$resultado .="<tr class='titulo_tabela'>";
			$resultado .="<td >Código</td>";
			$resultado .="<td >Descrição</td>";
			$resultado .="<td >Qtde</td>";
			$resultado .="</tr></thead>";
			$resultado .="<tbody>";

			$total = pg_num_rows($res);
			$total_pecas = 0;
			for ($i=0; $i<pg_num_rows($res); $i++){

				$referencia          = trim(pg_fetch_result($res,$i,referencia));
				$descricao           = trim(pg_fetch_result($res,$i,descricao));
				$peca           = trim(pg_fetch_result($res,$i,peca));
				$qtde                = trim(pg_fetch_result($res,$i,qtde));
				$total_pecas = $total_pecas + $qtde;

				$resultado .="<tr class='Conteudo'align='center'>";
				$resultado .="<td  align='center' nowrap><a href='$PHP_SELF?peca=$peca&xdata_inicial=$aux_data_inicial&xdata_final=$aux_data_final'>$referencia</a></td>";
				$resultado .="<td  align='left' nowrap><a href='$PHP_SELF?peca=$peca&xdata_inicial=$aux_data_inicial&xdata_final=$aux_data_final'>$descricao</a></td>";
				$resultado .="<td  nowrap>$qtde&nbsp;</td>";
				$resultado .="</tr>";
			}
			$resultado .="</tbody><tr>";
			$resultado .="<td colspan='2'><B>Total</b></td>";
			$resultado .="<td >$total_pecas</td>";
			$resultado .="</tr>";
			$resultado .="</table>";

			if($_POST['gerar_excel']) {
				$data = date('dmYHi');
				$filename = "auditoria_pecas_pendentes_pecas-$login_fabrica-$data.xls";
				$fp = fopen ("/tmp/$filename","w");
				fwrite($fp, $resultado);
				fclose($fp);

				if (file_exists("/tmp/{$filename}")) {
					system("mv /tmp/{$filename} xls/{$filename}");
					echo "xls/{$filename}";
				}
				exit;
			}
		}
	}
}

include 'cabecalho_new.php';
$plugins = array(
	"autocomplete",
	"shadowbox",
	"datepicker",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();
    var login_fabrica = <?=$login_fabrica?>;
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array( "peca"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }

	});

	function retorna_peca(retorno){
		$("#peca").val(retorno.peca);
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

    function set_peca_input(peca, referencia){
    	$("#peca_referencia").val(peca);
    	$("#peca_descricao").val(referenciac);

    	 $('html, body').animate({
	     	scrollTop: $("#form_pesquisa").offset().top
	     }, 500);

    }

</script>

<style>
	.desc_peca{
		text-transform: uppercase;
	}
</style>
<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>


<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' id="form_pesquisa">
	<input type="hidden" name="acao"  value="pesquisar">
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_inicial_01" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
						<input type="text" name="data_final_01" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>


	<div class="row-fluid">
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_referencia'>Ref. Peças</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" pesquisa_produto_acabado="true" sem-de-para="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='peca_descricao'>Descrição Peça</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="peca_descricao" name="peca_descricao" class='span12' value="<? echo $peca_descricao ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" pesquisa_produto_acabado="true" sem-de-para="true" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>

	</div>

	<p>
		<br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p>

	<br />
</form>

<?

if(count($msg_erro["msg"]) == 0 and $_POST) {

	if ($total > 0) {
		echo $resultado;
		$jsonPOST = excelPostToJson($_POST);
		echo "<br><div id='gerar_excel' class='btn_excel'>
				<input type='hidden' id='jsonPOST' value='$jsonPOST' />
				<span><img src='imagens/excel.png' /></span>
				<span class='txt'>Gerar Arquivo Excel</span>
			</div>";

	}else{
		echo "	<div class='alert'><h4>Nenhum resultado encontrado</h4></div>";
	}
}

$peca = $_GET['peca'];
$xdata_inicial = $_GET['xdata_inicial'];
$xdata_final =  $_GET['xdata_final'];

if($login_fabrica == 11){
	$cond_lenoxx = " AND tbl_pedido.distribuidor IS NOT NULL ";
}

if(strlen($peca)>0 and strlen($xdata_inicial)>0  and strlen($xdata_final)>0){
	$sql = "select	 tbl_pedido.pedido,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
					tbl_pedido.pedido_blackedecker as lenoxx,
					tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.peca,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_peca.retorna_conserto,
					tbl_peca.bloqueada_garantia,";
	if($login_fabrica == 11){
		$sql .= " sum (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada)) as pendente ";
	} else {
		$sql .= " sum (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) as pendente ";
	}		
	$sql .= "FROM tbl_pedido_item 
			JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca 
			JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido 
			JOIN tbl_posto on tbl_posto.posto = tbl_pedido.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_pedido.data > '2007-01-01 00:00:00' 
			AND tbl_pedido.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
			AND tbl_pedido_item.peca = $peca ";
	if($login_fabrica == 11){
		$sql .= " AND tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde ";
	} else {
		$sql .= " AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde ";
	}
			
	$sql .= " AND tbl_pedido.fabrica = $login_fabrica
			$cond_lenoxx
			GROUP BY tbl_pedido.pedido,
				tbl_pedido.data,
				tbl_pedido.pedido_blackedecker,
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.peca,
				tbl_posto.nome,
				tbl_peca.retorna_conserto,
				tbl_peca.bloqueada_garantia,
				tbl_posto_fabrica.codigo_posto
			order by tbl_pedido.data";
			
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$peca_referencia          = trim(pg_fetch_result($res,0,referencia));
		$peca_descricao           = trim(pg_fetch_result($res,0,descricao));

		echo "	<table class='table table-striped table-bordered table-fixed'>";
		echo "<thead>";
		echo "<tr class='titulo_tabela'>";
		echo "<td colspan='100%'>$peca_referencia - $peca_descricao</td>";
		echo "</tr>";

		echo "<tr class='titulo_tabela'>";
		echo "<td >Telecontrol</td>";
		echo "<td >Lenoxx</td>";
		echo "<td >Data</td>";
		echo "<td >Posto</td>";
		echo "<td >Qtde</td>";
		if ( in_array($login_fabrica, array(11,172)) ){
			echo "<td >Qtde Autorizada</td>";
		}
		echo "</tr></thead>";

		for($y=0;pg_num_rows($res)>$y;$y++){
			$pedido                   = trim(pg_fetch_result($res,$y,pedido));
			$lenoxx                   = trim(pg_fetch_result($res,$y,lenoxx));
			$data_pedido              = trim(pg_fetch_result($res,$y,data_pedido));
			$nome                     = trim(pg_fetch_result($res,$y,nome));
			$codigo_posto             = trim(pg_fetch_result($res,$y,codigo_posto));
			$pendente                 = trim(pg_fetch_result($res,$y,pendente));
			$peca                     = trim(pg_fetch_result($res,$y,peca));

			$retorna_conserto         = trim(pg_fetch_result($res,$y,retorna_conserto));
			$bloqueada_garantia       = trim(pg_fetch_result($res,$y,bloqueada_garantia));

			echo "<tr>";
			echo "<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='blank'>$pedido</a></td>";
			echo "<td>$lenoxx</td>";
			echo "<td>$data_pedido</td>";
			echo "<td align='left'>$codigo_posto - $nome</td>";
			echo "<td>$pendente</td>";

			if ( in_array($login_fabrica, array(11,172)) ){
				echo "<td>";
				if($retorna_conserto=='t' OR $bloqueada_garantia=='t') {
					$qtde_peca_autorizada = "";
					$sql2 = "SELECT count(*) as contador
						FROM tbl_os_item
						WHERE pedido = $pedido 
						AND peca = $peca
						AND admin IS NOT NULL";
					$res2 = pg_query ($con,$sql2);
					$qtde_peca_autorizada = pg_fetch_result($res2,0,contador);
					if ($qtde_peca_autorizada>0) {
						$sql2 = "SELECT count(*) as contador
							FROM tbl_os_status
							WHERE status_os in (64,73)
							AND os IN (
								SELECT os 
								FROM tbl_os_produto 
								JOIN tbl_os_item USING(os_produto)
								WHERE pedido = $pedido
								AND peca = $peca
							)
							";
						$res2 = pg_query ($con,$sql2);
						$qtde_peca_autorizada = pg_fetch_result($res2,0,contador);
						if ($qtde_peca_autorizada>0){
							$fonte = "style='color:blue'";
						}else{
							$fonte="";
							$qtde_peca_autorizada="-";
						}
					}else{
						$fonte="";
						$qtde_peca_autorizada = "-";
					}
					echo "<font $fonte>$qtde_peca_autorizada</fonte>";
				}
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
}

include "rodape.php" ;
?>
