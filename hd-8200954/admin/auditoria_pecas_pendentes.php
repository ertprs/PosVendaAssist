<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$layout_menu = "auditoria";
if($login_fabrica == 24) $title = "PEDIDOS COM PEÇAS NÃO ATENDIDAS";
else $title = "Auditoria -  OSs com peças não atendidas";

$janela = $_GET["janela"];
$posto  = $_GET["posto"];
$todos  = trim($_GET['todos']);
$btn_acao = $_POST['btn_acao'];


if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$peca_referencia    = $_POST['peca_referencia'];
	$peca_descricao     = $_POST['peca_descricao'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$xdata_inicial = "{$yi}-{$mi}-{$di}";
			$xdata_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($xdata_final) < strtotime($xdata_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	##Fim Validação de Datas
		if(count($msg_erro["msg"]) == 0) {
			$sql = "SELECT '$xdata_final'::date - INTERVAL '1 MONTH' > '$xdata_inicial'::date ";
			$res = pg_query ($con,$sql);
			if (pg_fetch_result($res,0,0) == 't' && !in_array($login_fabrica, array(11,172)) ) {
				$msg_erro["msg"][]    = "Intervalo de data maior que 1 mês";
				$msg_erro["campos"][] = "data";
			}
		}
}

if( in_array($login_fabrica, array(11,172)) ) {
	$cond_1 = " tbl_pedido.data > '2006-12-01' ";
	$cond_2 = " AND tbl_os_item.faturamento_item is null";
	#$cond_3 = " AND (tbl_pedido.pedido_blackedecker NOTNULL OR tbl_pedido.posto not IN(14301,20321,6359)) ";
	$cond_finalizado = " AND tbl_os.finalizada IS NULL AND tbl_os.data_fechamento IS NULL";
}

include 'cabecalho_new.php';

if(strlen($posto) > 0 AND $janela=="abrir"){

	$sql = "SELECT tbl_posto.nome         ,
				   tbl_posto_fabrica.codigo_posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto ";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$codigo_posto = trim(pg_fetch_result($res,0,codigo_posto));
		$nome         = trim(pg_fetch_result($res,0,nome))        ;

		echo '<table align="center" width="700" cellspacing="1" class="tabela">';
		echo "<tr class='titulo_coluna'>";
		echo "<td colspan='7'>Total de OS's com Peças Pendentes (Não Faturadas Pelo Fabricante)</td>";
		echo "</tr>";
		echo "<tr class='titulo_coluna'>";
		echo "<td colspan='7' height='20'><font size='2'>$codigo_posto - $nome</font></td>";
		echo "</tr>";

		$sql = "SELECT distinct
					tbl_os.os                                             ,
					tbl_os.sua_os                                         ,
					tbl_os.data_abertura                                  ,
					to_char(tbl_os.data_abertura,'dd/mm/yyyy') AS abertura,
					tbl_pedido_item.pedido AS pedido_telecontrol          ,
					tbl_pedido.pedido_blackedecker AS pedido_logix        ,
					tbl_peca.referencia                                   ,
					tbl_peca.descricao                                    ,
					tbl_pedido_item.qtde                                  ,
					tbl_pedido_item.qtde_faturada                         ,
					tbl_os_item.digitacao_item::date                      ,
					tbl_pedido.exportado::date
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item USING(os_produto)
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
									AND tbl_pedido_item.peca = tbl_os_item.peca
									AND tbl_pedido_item.qtde_faturada + qtde_cancelada < tbl_pedido_item.qtde
				JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
				WHERE tbl_pedido.pedido in (
						SELECT pedido
						FROM tbl_pedido
						WHERE fabrica     = $login_fabrica
						AND posto         = $posto
						AND status_pedido in (1,2,5)
						$cond_3
				)
				AND $cond_1 $cond_2 $cond_finalizado
				ORDER BY tbl_os.data_abertura,
						 tbl_peca.referencia";
		$res = pg_query ($con,$sql);
		//echo $sql;exit;
		if (pg_num_rows($res) > 0) {
			echo "<tr class='subtitulo'>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "<td >Peça</td>";
			echo "<td >Qtde.        </td>";
			echo "<td >Qtde. Faurada</td>";
			echo "<td >Pedido</td>";
			echo "<td >Pedido Fabricante</td>";
			echo "</tr>";

			$total = pg_num_rows($res);

			for ($i=0; $i<pg_num_rows($res); $i++){

				$os                  = trim(pg_fetch_result($res,$i,os));
				$sua_os              = trim(pg_fetch_result($res,$i,sua_os));
				$abertura            = trim(pg_fetch_result($res,$i,abertura));
				$referencia          = trim(pg_fetch_result($res,$i,referencia));
				$descricao           = trim(pg_fetch_result($res,$i,descricao));
				$qtde                = trim(pg_fetch_result($res,$i,qtde));
				$qtde_faturada       = trim(pg_fetch_result($res,$i,qtde_faturada));
				$pedido              = trim(pg_fetch_result($res,$i,pedido_telecontrol));
				$pedido_fabricante   = trim(pg_fetch_result($res,$i,pedido_logix));

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr align='center' bgcolor='$cor'>";
				echo "<td nowrap><a href='os_press.php?os=$os'>$sua_os&nbsp;</a></td>";
				echo "<td>$abertura&nbsp;</td>";
				echo "<td align='left' nowrap>$referencia - $descricao&nbsp;</td>";
				echo "<td nowrap>$qtde&nbsp;</td>";
				echo "<td nowrap>$qtde_faturada&nbsp;</td>";

				echo "<td><a href=pedido_admin_consulta.php?pedido=$pedido target=_blank>$pedido&nbsp;</a></td>";
				echo "<td>$pedido_fabricante&nbsp;</td>";
				echo "</tr>";
			}
			echo "</table>";
		 } else {
			echo "Nenhum resultado encontrado";
		 }
	}
	exit;
}

if(strlen($btn_acao) > 0) {
	//hd 14690
	if( !in_array($login_fabrica, array(11,172)) ){
		if(strlen($mes)==0 OR strlen($ano)==0) $msg_erro = "É obrigatório o preenchimento do MÊS e ANO";
	}
	//if(strlen($codigo_posto)==0) $msg_erro .= "<br>É obrigatório digitar o posto";
}

	$referencia_produto = trim($_POST['referencia_produto']);
	if(strlen($referencia_produto) > 0){
		$sql ="SELECT produto,referencia,descricao
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE referencia = '$referencia_produto'
				AND   fabrica    = $login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$produto             = trim(pg_fetch_result($res,0,produto))        ;
			$referencia_produto  = trim(pg_fetch_result($res,0,referencia));
			$descricao_produto   = trim(pg_fetch_result($res,0,descricao))        ;
		}

	}

	$referencia = trim($_POST['referencia_peca']);
	if(strlen($referencia) > 0){
		$sql ="SELECT peca,referencia,descricao
				FROM tbl_peca
				WHERE referencia = '$referencia'
				AND   fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$peca        = trim(pg_fetch_result($res,0,peca))        ;
			$referencia  = trim(pg_fetch_result($res,0,referencia));
			$descricao   = trim(pg_fetch_result($res,0,descricao))        ;
		}

	}
	$tipo_pedido = $_POST['tipo_pedido'];
	$tipo_os	 = $_POST['tipo_os'];
	$tipo_data   = $_POST['tipo_data'];
	$cancelado_troca = $_POST['cancelado_troca'];


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>
<style type="text/css">

	.tb_n {
	    display: table;
	    border-collapse: separate;
	    border-spacing: 1px;
	    border-color: white;
	}
</style>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});

	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_peca(retorno){
        $("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
    }

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>
<style>
    fieldset
	{
		border: 1px solid black !important;
		margin: 10
		xmin-width: 0;
		padding: 10px;
		position: relative;
		border-radius:4px;
		padding-left:10px!important;
	}

	legend
	{
		font-size:14px;
		font-weight:bold;
		margin-bottom: 0px;
		width: 35%;
		border: 1px solid ;
		border-radius: 4px;
		padding: 5px 5px 5px 10px;
	}
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class='alert'>O tempo de geração deste relatório pode variar de acordo com a quantidade de pedidos. Após clicar no botão de pesquisa, não atualize a página até a conclusão do processamento...</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<? if($login_fabrica == 24) {?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'>Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='peca_referencia'>Ref. Peças</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="peca_referencia" name="peca_referencia" class='span12' maxlength="20" value="<? echo $peca_referencia ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" />
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
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<? } ?>
<?	if( in_array($login_fabrica, array(11,172)) ) { // HD 65209 ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("tipo_pedido", $msg_erro["campos"])) ? "error" : ""?>'>
				<fieldset class="col-md-2">
					<legend>Tipo Pedido</legend>
					<div class="panel panel-default">
						<div class="panel-body">
						<label>
							<input type="radio" name="tipo_pedido" value="garantia" <?if($tipo_pedido=='garantia' or strlen($tipo_pedido) == 0) echo "checked"; ?> > Garantia
						</label>
							 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
						<label>
							<input type="radio" name="tipo_pedido" value="venda" <?if($tipo_pedido=='venda') echo "checked"; ?> > Venda
						</label>
						</div>
					</div>
				</fieldset>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("tipo_os", $msg_erro["campos"])) ? "error" : ""?>'>
				<fieldset class="col-md-2">
					<legend>Tipo de OS</legend>
					<div class="panel panel-default">
						<div class="panel-body">
						<label>
							<input type="radio" name="tipo_os" value="C" <?if($tipo_os=='C') echo "checked"; ?> class="frm"> Consumidor
						</label>
							 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
						<label>
							<input type="radio" name="tipo_os" value="R" <?if($tipo_os=='R') echo "checked"; ?> class="frm"> Revenda
						</label>
						</div>
					</div>
				</fieldset>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group <?=(in_array("data_tipo", $msg_erro["campos"])) ? "error" : ""?>'>
				<fieldset class="col-md-2">
					<legend>Data</legend>
					<div class="panel panel-default">
						<div class="panel-body">
							<label>
								<input type="radio" name="tipo_data" value="os" <?if($tipo_data=='os' or strlen($tipo_data) == 0) echo "checked"; ?> class="frm"> OS
							</label>
							 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
							<label>
								<input type="radio" name="tipo_data" value="pedido" <?if($tipo_data=='pedido') echo "checked"; ?> class="frm"> Pedido
							</label>
						</div>
					</div>
				</fieldset>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("check", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='cancelado_troca'>Cancelado para Troca</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="checkbox" name="cancelado_troca" id='cancelado_troca' value="t" <?if($cancelado_troca=='t') echo "checked"; ?> > 
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	<? } ?>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/><br>
</form>
<?
flush();
if(strlen($btn_acao) > 0 AND strlen($msg_erro)==0){
	if(strlen($codigo_posto) > 0){
		$sql = "SELECT  tbl_posto.posto,
						tbl_posto.nome         ,
						tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE fabrica      = $login_fabrica
				AND   codigo_posto = '$codigo_posto' ";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$posto        = trim(pg_fetch_result($res,0,posto))        ;
			$codigo_posto = trim(pg_fetch_result($res,0,codigo_posto));
			$nome         = trim(pg_fetch_result($res,0,nome))        ;
		}
	}

	if ( in_array($login_fabrica, array(11,172)) ) { // HD 65209
		if ($tipo_data == 'os') {
			$cond_data = " AND data_digitacao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
		}else{
			$cond_data = " AND data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
			$sql_join_pedido = " JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
									AND tbl_pedido_item.peca = tbl_os_item.peca
				JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $login_fabrica ";
		}
	}else{
		$cond_data = " AND data_abertura BETWEEN '$xdata_inicial' AND '$xdata_final' ";
	}


		if($login_fabrica==24){ // HD 42336
			$cond_4 = " AND tbl_pedido.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
			$sql_order= " ORDER BY tbl_posto.nome, tbl_peca.referencia";
		}

		if (strlen($posto) > 0) {
			$cond_5 = " AND tbl_pedido.posto = $posto ";
		}
		if(strlen($peca) > 0 ){
			$cond_6 = " AND tbl_pedido_item.peca = $peca ";
		}
		if(strlen($produto ) > 0 ){
			$cond_7 = " AND tbl_pedido_item.peca in (SELECT peca FROM tbl_lista_basica where fabrica = $login_fabrica AND produto = $produto ) ";
		}

		if(strlen($tipo_pedido) >0){
			if($tipo_pedido=='garantia'){
				$cond_8 = " AND tbl_pedido.tipo_pedido in (84,393)";
			}
			if($tipo_pedido=='venda'){
				$cond_8 = " AND tbl_pedido.tipo_pedido in (85, 392)";
				$cond_4 = " AND tbl_pedido.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
			}
		}


		if( in_array($login_fabrica, array(11,172)) && $tipo_pedido<>'venda'){
			if(!empty($cancelado_troca)){

				$sql_join_pedido = " JOIN tbl_pedido_cancelado ON tbl_os_item.pedido = tbl_pedido_cancelado.pedido AND tbl_pedido_cancelado.motivo = 'enviado para troca'
								LEFT JOIN tbl_os_troca ON tbl_pedido_cancelado.os = tbl_os_troca.os";
				$cond_10	= " AND tbl_os_troca.os isnull ";
				$novo_status = ",14";
				$distinct = " distinct ";
				$sinal = ">=";

				$cond_data = " AND tbl_pedido_cancelado.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";

			}

			$sql_temp = " SELECT tbl_os.os,tbl_os.posto,sua_os,data_abertura,tbl_os.fabrica,data_digitacao,tbl_os.consumidor_revenda,tbl_os_item.peca,digitacao_item,tbl_os_item.pedido_item,faturamento_item, tbl_os.produto
						INTO TEMP TABLE tmp_audi_pendente
						FROM tbl_os
						JOIN tbl_os_produto    USING(os)
						JOIN tbl_os_item       ON tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os_item.fabrica_i = $login_fabrica
						$sql_join_pedido
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.excluida IS NOT TRUE
						$cond_data
						$cond_finalizado
						$cond_10";
			if (strlen($posto) > 0) {
				$sql_temp .= " AND tbl_os.posto = $posto ";
			}



			$sql_temp .= " AND   tbl_os_item.faturamento_item    IS NULL;
						   create index tmp_audi_pendente_pedido_item ON tmp_audi_pendente (pedido_item);
						   create index tmp_audi_pendente_peca        ON tmp_audi_pendente (peca); ";
			$sql_join = " JOIN tmp_audi_pendente  ON tbl_pedido_item.pedido_item   = tmp_audi_pendente.pedido_item AND tbl_pedido_item.peca = tmp_audi_pendente.peca ";
			$sql_valor = " ,tmp_audi_pendente.os                          ,
					tmp_audi_pendente.sua_os                             ,
					tmp_audi_pendente.data_abertura                      ,
					tmp_audi_pendente.digitacao_item::date               ,
					to_char(tmp_audi_pendente.data_abertura,'dd/mm/yyyy') AS abertura ";

			$order_valor = " ,tmp_audi_pendente.os  ,
					tmp_audi_pendente.sua_os        ,
					tmp_audi_pendente.data_abertura ,
					tmp_audi_pendente.digitacao_item,
					tmp_audi_pendente.data_abertura  ";

			$sql_order= " ORDER BY tbl_posto.nome, tmp_audi_pendente.data_abertura,
						 tbl_peca.referencia";

			if(!empty($tipo_os) && $tipo_pedido == "garantia"){
				$cond_9 = " AND tmp_audi_pendente.consumidor_revenda = '$tipo_os' ";
			}


		}

		if ( in_array($login_fabrica, array(11,172)) ) { // HD 65209
			if ($tipo_data == 'os' AND $tipo_pedido<>'venda') {
				$sql_order = "  ORDER BY tmp_audi_pendente.data_abertura ASC ";
			}else{
				$sql_order = "  ORDER BY pedido_data ASC,
								pedido_telecontrol,
								pedido_logix,
								tbl_peca.referencia,
								tbl_peca.descricao,
								tbl_pedido_item.qtde,
								tbl_pedido_item.qtde_faturada,
								tbl_pedido.exportado,
								tbl_posto_fabrica.codigo_posto,
								nome_posto
								";

			}
			$distinct = " ";
		}else{
			$distinct = " distinct ";
		}

		if(!empty($cancelado_troca)){

			$sql_pedido = " JOIN tbl_pedido_cancelado ON tbl_pedido.pedido = tbl_pedido_cancelado.pedido AND tbl_pedido_cancelado.motivo = 'enviado para troca'
							LEFT JOIN tbl_os_troca ON tbl_pedido_cancelado.os = tbl_os_troca.os";
			$cond_10	= " AND tbl_os_troca.os isnull ";
			$novo_status = ",14";
			$distinct = " distinct ";
			$sinal = ">=";

			$cond_data = " AND tbl_pedido_cancelado.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";

		}else{
			$sinal = ">";
		}

		$sql = " $sql_temp
				 SELECT $distinct
					tbl_pedido_item.pedido                                AS pedido_telecontrol,
					tbl_pedido.pedido_blackedecker                        AS pedido_logix      ,
					tbl_peca.referencia                                                        ,
					tbl_peca.descricao                                                         ,
					tbl_pedido_item.qtde                                                       ,
					tbl_pedido_item.qtde_faturada                                              ,
					tbl_pedido.exportado::date                                                 ,
					tbl_posto_fabrica.codigo_posto                                             ,
					tbl_posto.nome                                        AS nome_posto        ,
					to_char(tbl_pedido.data,'dd/mm/yyyy') as pedido_data
					$sql_valor

				FROM tbl_pedido_item
				$sql_join
				JOIN tbl_pedido         ON tbl_pedido.pedido             = tbl_pedido_item.pedido
				JOIN tbl_peca           ON tbl_pedido_item.peca          = tbl_peca.peca
				JOIN tbl_posto_fabrica  ON tbl_pedido.posto              = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto          ON tbl_posto_fabrica.posto       = tbl_posto.posto
				$sql_pedido
				WHERE tbl_pedido.status_pedido     IN (2,5 $novo_status)
				AND   tbl_pedido_item.qtde $sinal (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)
				AND   tbl_peca.fabrica     = $login_fabrica
				$cond_data
				$cond_3
				$cond_4
				$cond_5
				$cond_6
				$cond_7
				$cond_8
				$cond_9
				$cond_10
				$sql_order ";

		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {

			if( in_array($login_fabrica, array(11,172)) && $tipo_pedido <> 'venda' ){
				$colspan = 11;
			}elseif( in_array($login_fabrica, array(11,172)) ){
				$colspan = 10;
			}elseif($login_fabrica == 24){
				$colspan = 7;
			}else{
				$colspan = 6;
			}

			$tb = "table";

			if ($login_fabrica == 11 && $_POST['cancelado_troca'] == 't') {
				$tb = "tb_n";
			}

			echo "<table class='$tb table-striped table-bordered table-hover table-large' id='resultado_tabela'>";
			echo "<thead>";
			echo "<tr class='titulo_tabela'>";
			echo "<td colspan='$colspan'>";
			if($login_fabrica == 24) {
				echo "Total de Pedidos com Peças Pendentes (Não Faturadas Pelo Fabricante)";
			}else{
				if($tipo_pedido=='garantia')
					echo "Total de OS's com Peças Pendentes (Não Faturadas Pelo Fabricante)";
				else
					echo "Total de Pedidos com Peças Pendentes (Não Faturadas Pelo Fabricante)";
			}
			echo "</td>";

			if ($login_fabrica == 11 && $_POST['cancelado_troca'] == 't') {
				echo "<td></td>";
			}

			echo "</tr>";

			if(strlen(trim($codigo_posto)) > 0){
				echo "<tr class='titulo_tabela'>";
				echo "<td colspan='$colspan'>$codigo_posto - $nome</td>";
				echo "</tr>";
			}
			echo "<tr class='titulo_tabela'>";
			if( in_array($login_fabrica, array(11,172)) && $tipo_pedido <> 'venda' ){
				echo "<td >OS</td>";
			}
			echo "<td >Cod. Posto</td>";
			echo "<td >Nome Posto</td>";
			if( in_array($login_fabrica, array(11,172)) ){
				echo "<td>Abertura</td>";
			}elseif($login_fabrica == 24){
				echo "<td>Data do pedido</td>";
			}

			if ($login_fabrica == 11 && $_POST['cancelado_troca'] == 't') {
				echo "<td >Referência</td>";
			}

			if( in_array($login_fabrica, array(11,172)) ) {
				echo "<td>Código</td>";
				echo "<td> Descrição</td>";
			}else{
				echo "<td>Peça</td>";
			}
			echo "<td>Qtde.        </td>";
			echo "<td>Qtde. Faurada</td>";
			echo "<td>Pedido</td>";
			if( in_array($login_fabrica, array(11,172)) ){ // HD 65209
				echo "<td>Data Pedido</td>";
				echo "<td>Pedido Fabricante</td>";
			}
			echo "</tr></thead>";

			$total = pg_num_rows($res);

            if( in_array($login_fabrica, array(11,172)) ){
                $repeteOs       = "";
            }
			for ($i=0; $i<pg_num_rows($res); $i++){
				if( in_array($login_fabrica, array(11,172)) && $tipo_pedido<>'venda') {
					$os                  = trim(pg_fetch_result($res,$i,os));
					$sua_os              = trim(pg_fetch_result($res,$i,sua_os));
					$abertura            = trim(pg_fetch_result($res,$i,abertura));
				}
				$produto          	 = trim(pg_fetch_result($res,$i,produto));
				$referencia          = trim(pg_fetch_result($res,$i,referencia));
				$descricao           = trim(pg_fetch_result($res,$i,descricao));
				$qtde                = trim(pg_fetch_result($res,$i,qtde));
				$qtde_faturada       = trim(pg_fetch_result($res,$i,qtde_faturada));
				$pedido              = trim(pg_fetch_result($res,$i,pedido_telecontrol));
				$pedido_fabricante   = trim(pg_fetch_result($res,$i,pedido_logix));
				$xcodigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
				$nome_posto          = trim(pg_fetch_result($res,$i,nome_posto));
				$pedido_data         = trim(pg_fetch_result($res,$i,pedido_data));
				if($tipo_pedido =='venda') $abertura = $pedido_data;

				echo "<tr class='tac'>";
				if( in_array($login_fabrica, array(11,172)) && $tipo_pedido <> 'venda'){
                     $mostra_os = $sua_os;
					echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$mostra_os&nbsp;</a></td>";
				}

                $mostraCodigo = $xcodigo_posto;
				echo "<td nowrap>$mostraCodigo&nbsp;</td>";


                   $mostraPosto = $nome_posto;
				echo "<td align='left'>$mostraPosto&nbsp;</td>";
				if( in_array($login_fabrica, array(11,172)) ){
                    $mostraAbertura = $abertura;
					echo "<td nowrap>$mostraAbertura&nbsp;</td>";
				}elseif($login_fabrica == 24){
					echo "<td nowrap>$pedido_data&nbsp;</td>";
				}

				if ($login_fabrica == 11 && $_POST['cancelado_troca'] == 't') {

					$getProdutoDaOs = "SELECT tbl_produto.referencia as produto 
									   FROM tbl_os 
									   JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
							           WHERE os = $os";

					$resGetProduto = pg_query($con, $getProdutoDaOs);

					$produto = pg_fetch_result($resGetProduto,0,produto);

					echo "<td >" . $produto . "</td>";
				}
				if( in_array($login_fabrica, array(11,172)) ) {
					echo "<td align='left' nowrap>$referencia</td>";
					echo "<td align='left' >$descricao</td>";
				}else{
					echo "<td align='left' nowrap>$referencia - $descricao&nbsp;</td>";
				}
				echo "<td align='right'>$qtde&nbsp;</td>";
				echo "<td align='right'>$qtde_faturada&nbsp;</td>";

				echo "<td ><a href=pedido_admin_consulta.php?pedido=$pedido target=_blank>$pedido&nbsp;</a></td>";
				if( in_array($login_fabrica, array(11,172)) ){
					echo "<td nowrap>$pedido_data&nbsp;</td>";
					echo "<td >$pedido_fabricante&nbsp;</td>";
				}
				echo "</tr>";
                if( in_array($login_fabrica, array(11,172)) ){
                    $repeteOs = $sua_os;
                }
			}
			echo "</table>";
			if ($total > 50) {
				echo '<script>
					$.dataTableLoad({ table: "#resultado_tabela" });
				</script>';
			}

			if( in_array($login_fabrica, array(11,172)) ) {// HD 50555
				echo "<br>";
				flush();
				$data = date ("d/m/Y H:i:s");

				$arquivo_nome     = "auditoria-pecas-pendentes-$login_fabrica.xls";
				$path             = "/www/assist/www/admin/xls/";
				$path_tmp         = "/tmp/";

				$arquivo_completo     = $path.$arquivo_nome;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

				flush();

				echo `rm $arquivo_completo_tmp `;
				echo `rm $arquivo_completo `;

				flush();

				$fp = fopen ($arquivo_completo_tmp,"w");

				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>Auditoria - OSs com peças não atendidas - $data");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");

				fputs ($fp,"<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>");
				fputs ($fp,"<caption class='Titulo'>");
				if($tipo_pedido =='garantia') {
					fputs ($fp,"TOTAL DE OS'S COM PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)");
				}else{
					fputs ($fp,"TOTAL DE PEDIDOS COM PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)");
				}
				fputs ($fp,"</caption>");
				if(strlen($codigo_posto) > 0){
					fputs ($fp,"<tr class='Titulo'>");
					fputs ($fp,"<td colspan='7' height='20'><font size='2'>$codigo_posto - $nome</font></td>");
					fputs ($fp,"</tr>");
				}
				fputs ($fp,"<tr class='Titulo'>");
				if($tipo_pedido <> 'venda' ){
					fputs ($fp,"<td >OS</td>");
				}
				fputs ($fp,"<td >Cod. Posto</td>");
				fputs ($fp,"<td >Nome Posto</td>");
				fputs ($fp,"<td >Abertura</td>");

				if ($login_fabrica == 11 && $_POST['cancelado_troca'] == 't') {
					fputs ($fp,"<td >Referência</td>");
				}

				fputs ($fp,"<td >Código</td>");
				fputs ($fp,"<td >Descrição</td>");
				fputs ($fp,"<td >Qtde.        </td>");
				fputs ($fp,"<td >Qtde. Faurada</td>");
				fputs ($fp,"<td >Pedido</td>");
				fputs ($fp,"<td >Data Pedido</td>");
				fputs ($fp,"<td >Pedido Fabricante</td>");
				fputs ($fp,"</tr>");

                $repeteOs       = "";

				for($i=0;$i<pg_num_rows($res);$i++){
					if( in_array($login_fabrica, array(11,172)) && $tipo_pedido<>'venda') {
						$os                  = trim(pg_fetch_result($res,$i,os));
						$sua_os              = trim(pg_fetch_result($res,$i,sua_os));
						$abertura            = trim(pg_fetch_result($res,$i,abertura));
					}
					$referencia          = trim(pg_fetch_result($res,$i,referencia));
					$descricao           = trim(pg_fetch_result($res,$i,descricao));
					$qtde                = trim(pg_fetch_result($res,$i,qtde));
					$qtde_faturada       = trim(pg_fetch_result($res,$i,qtde_faturada));
					$pedido              = trim(pg_fetch_result($res,$i,pedido_telecontrol));
					$pedido_fabricante   = trim(pg_fetch_result($res,$i,pedido_logix));
					$codigo_posto        = trim(pg_fetch_result($res,$i,codigo_posto));
					$nome_posto          = trim(pg_fetch_result($res,$i,nome_posto));
					$pedido_data         = trim(pg_fetch_result($res,$i,pedido_data));
					if($tipo_pedido=='venda') $abertura = $pedido_data;

					if($cor=="#F1F4FA")$cor = '#F7F5F0';
					else               $cor = '#F1F4FA';

					fputs ($fp,"<tr class='Conteudo'align='center'>");
					if($tipo_pedido <> 'venda'){
                        if($sua_os == $repeteOs){
                            $mostra_os = "&nbsp;";
                        }else{
                            $mostra_os = $sua_os;
                        }
						fputs ($fp,"<td nowrap>$mostra_os&nbsp;</td>");
					}
					if( $sua_os == $repeteOs){
                        $mostraCodigo = "&nbsp;";
                    }else{
                        $mostraCodigo = $xcodigo_posto;
                    }
					fputs ($fp,"<td nowrap>$mostraCodigo</td>");
					if($sua_os == $repeteOs){
                        $mostraPosto = "&nbsp;";
                    }else{
                        $mostraPosto = $nome_posto;
                    }
					fputs ($fp,"<td nowrap>$mostraPosto</td>");
					if($sua_os == $repeteOs){
                        $mostraAbertura = "&nbsp;";
                    }else{
                        $mostraAbertura = $abertura;
                    }
					fputs ($fp,"<td nowrap>$mostraAbertura</td>");

					if ($login_fabrica == 11 && $_POST['cancelado_troca'] == 't') {

						$getProdutoDaOs = "SELECT tbl_produto.referencia as produto 
									       FROM tbl_os 
									       JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
							               WHERE os = $os";

						$resGetProduto = pg_query($con, $getProdutoDaOs);

						$produto = pg_fetch_result($resGetProduto,0,produto);

						fputs ($fp,"<td nowrap>$produto</td>");
					}

					fputs ($fp,"<td align='left' nowrap>$referencia</td>");
					fputs ($fp,"<td align='left' nowrap>$descricao</td>");
					fputs ($fp,"<td nowrap>$qtde</td>");
					fputs ($fp,"<td nowrap>$qtde_faturada</td>");
					fputs ($fp,"<td bgcolor='$cor'>$pedido</td>");
					fputs ($fp,"<td bgcolor='$cor'>$pedido_data</td>");
					fputs ($fp,"<td bgcolor='$cor'>$pedido_fabricante</td>");
					fputs ($fp,"</tr>");
					if( in_array($login_fabrica, array(11,172)) ){
                        $repeteOs = $sua_os;
                    }
				}

				fputs ($fp,"</table>");

				fputs ($fp,"</body>");
				fputs ($fp,"</html>");
				fclose ($fp);

				echo ` cp $arquivo_completo_tmp $path `;
				$data = date("Y-m-d").".".date("H-i-s");

				echo "<div  class='btn_excel'><a href='xls/auditoria-pecas-pendentes-$login_fabrica.xls'>
				<span><img src='imagens/excel.png' /></span>
				<span class='txt'>Gerar Arquivo Excel</span></a>
			</div>";

			}

		 } else {
			echo "<div class='alert'>Não foram Encontrados Resultados para esta Pesquisa</div>";
		 }

}else{
	if ($todos==1){

		$sql_posto = "SELECT DISTINCT
						tbl_posto.posto,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
					FROM tbl_posto_fabrica
					JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_posto.nome ASC";
		$res_posto = pg_query ($con,$sql_posto);
		$qtde_postos = pg_num_rows($res_posto);

		if ($qtde_postos > 0) {

			echo "<br><br>";
			echo '<table align="center" width="700" cellspacing="1" class="tabela">';
			echo "<tr class='titulo_coluna'>";
			echo '<td>Clique sobre o código do posto para listar apenas as suas pendências</td>';
			echo '</tr>';
			echo '</table>';

			echo '<table align="center" width="700" cellspacing="1" class="tabela">';
			echo "<tr class='titulo_coluna'>";
			echo "<td colspan='5'>Total de OS's com Peças Pendentes (Não Faturadas Pelo Fabricante)</td>";
			echo "</tr>";

			echo "<tr class='subtitulo'>";
			echo "<td >CÓDIGO DO POSTO</td>";
			echo "<td >NOME DO POSTO</td>";
			echo "<td >TOTAL</td>";
			echo "</tr>";
			for ($j=0; $j<$qtde_postos; $j++){
				$posto			= trim(pg_fetch_result($res_posto,$j,posto));
				$codigo_posto	= trim(pg_fetch_result($res_posto,$j,codigo_posto));
				$nome			= trim(pg_fetch_result($res_posto,$j,nome));

				$sql = "SELECT count(distinct os) as total
						FROM ( SELECT pedido
								FROM tbl_pedido
								WHERE fabrica     = $login_fabrica
								AND status_pedido = 2
								AND $cond_1
								AND posto=$posto
								AND (pedido_blackedecker NOTNULL OR posto IN(14301,20321,6359))
						) t_pedido
						JOIN tbl_pedido_item ON tbl_pedido_item.pedido      = t_pedido.pedido AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
						JOIN tbl_os_item     ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item AND tbl_pedido_item.peca = tbl_os_item.peca
						JOIN tbl_os_produto USING(os_produto)
						WHERE 1=1 $cond_2";
				//echo $sql;exit;
				$res = pg_query ($con,$sql);
				if (pg_num_rows($res) > 0) {
					for ($i=0; $i<pg_num_rows($res); $i++){
						$total                   = trim(pg_fetch_result($res,$i,total))       ;

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

						echo "<tr class='Conteudo'align='center'>";
						echo "<td ><a href='javascript: fnc_ver_posto($posto);'>$codigo_posto</a></td>";
						echo "<td align='left'>$nome</td>";
						echo "<td >$total</td>";
						$total_geral = $total + $total_geral;
						echo "</tr>";
						flush();
					}
				}
			}
			echo "<tr><td colspan='2'> Total</td><td>$total_geral</td></tr>";
			echo "</table>";
		}
	}
}

include "rodape.php" ;
?>
