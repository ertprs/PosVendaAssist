<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$q = strtolower($_GET["q"]);
$gerar_xls = $_REQUEST['gerar_xls'];

if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.posto,tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$posto = trim(pg_result($res,$i,posto));
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				/*Retira todos usu?rios do TIME*/
				$sql = "SELECT *
						FROM  tbl_empresa_cliente
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;
				$sql = "SELECT *
						FROM  tbl_empresa_fornecedor
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;

				$sql = "SELECT *
						FROM  tbl_erp_login
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;

					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$condicao = "";

if(isset($_POST["codigo_posto"]) && isset($_POST["descricao_posto"])){
	$codigo_posto    = $_POST["codigo_posto"];
	$descricao_posto = $_POST["descricao_posto"];
    $condicao = '';

    if (strlen($codigo_posto)) {
        $condicao .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
    }

	if(!empty($descricao_posto)){
		$condicao .= " AND UPPER(tbl_posto.nome) like UPPER('%$descricao_posto%') ";
    }
}

$lista_todos = $_GET["lista_todos"];

if ($_POST || (isset($_GET["lista_todos"]) && $lista_todos == 't')) {

	if(isset($_POST["peca_referencia"]) && isset($_POST["peca_descricao"])){

		$peca_referencia = $_POST["peca_referencia"];
		$peca_descricao  = $_POST["peca_descricao"];
	    $condicaopeca    = '';
	    $condicao_peca   = '';

	    if (!empty($peca_referencia)) {
	        $condicaopeca .= " AND referencia = '$peca_referencia'";
	    } else {
			$condicaopeca .= " AND UPPER(descricao) = UPPER('%$peca_descricao%') ";
	    }

	    if (!empty($condicaopeca)) {

	    	$sqlPeca = "SELECT peca FROM tbl_peca WHERE fabrica={$login_fabrica} $condicaopeca";
			$resPeca = pg_query($con, $sqlPeca);

			if (pg_num_rows($resPeca) > 0) {
				$peca = pg_fetch_result($resPeca, 0, peca);
				$condicao_peca = " AND tbl_peca.peca={$peca}";
			}
		}
	}

	$sql = "SELECT tbl_posto.posto,
               tbl_posto.nome,
               tbl_posto_fabrica.codigo_posto,
               SUM(tbl_os_item.qtde) AS total_peca
          FROM tbl_faturamento
          JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
          JOIN tbl_posto            ON tbl_posto.posto           = tbl_faturamento.posto
          JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
                                   AND tbl_posto_fabrica.fabrica = $login_fabrica
          JOIN tbl_os_item          ON tbl_os_item.pedido       = tbl_faturamento_item.pedido AND tbl_os_item.peca = tbl_faturamento_item.peca
          JOIN tbl_peca             ON tbl_peca.peca             = tbl_os_item.peca
                                   AND tbl_peca.fabrica          = $login_fabrica
         WHERE tbl_faturamento.fabrica   IN (10, $login_fabrica)
           AND tbl_faturamento.cfop       ~ E'^[56]9'
           AND tbl_faturamento.emissao   >= '2010-01-01'
           AND tbl_faturamento.cancelada IS NULL
           AND tbl_faturamento_item.extrato_devolucao IS NULL
           AND tbl_os_item.peca_obrigatoria
          $condicao_peca
          $condicao
      GROUP BY tbl_posto.posto,
               tbl_posto.nome,
               tbl_posto_fabrica.codigo_posto
      ORDER BY tbl_posto_fabrica.codigo_posto";

$resPendencia = pg_query($con,$sql);
$resPendenciaExcel = pg_query($con,$sql);
}
$title       = "RELATÓRIO DE PEÇAS PENDENTES PARA LGR";
$cabecalho   = $title;
$layout_menu = "cadastro";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"shadowbox"
);

include("plugin_loader.php");
?>
<style type="text/css">
	.table  tr > td {
		text-align: center;
	}

	.titulo_coluna_peca {
	    background-color: #596d9b !important;
	    font: bold 11px "Arial";
	    color: #FFFFFF;
	    text-align: center;
	    padding: 5px 0 0 0;
	}
</style>
<script type="text/javascript">
	var windowParams = "toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0";
	var selfLocation = window.location.pathname;
	$().ready(function(){

		$.autocompleteLoad(Array("posto","peca"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("span[rel=lupa_peca]").click(function () {
			$.lupa($(this));
		});

		function fnc_pesquisa_codigo_posto (codigo, nome) {
		    var url = "";
		    if (codigo != "" && nome == "") {
		        url = "pesquisa_posto.php?codigo=" + codigo;
		        janela = window.open(url,"janela",windowParams);
		        janela.focus();
		    }
		}

		function fnc_pesquisa_nome_posto (codigo, nome) {
		    var url = "";
		    if (codigo == "" && nome != "") {
		        url = "pesquisa_posto.php?nome=" + nome;
		        janela = window.open(url,"janela",windowParams);
		        janela.focus();
		    }
		}

		function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
			if (tipo == "nome" ) {
				var xcampo = campo;
			}

			if (tipo == "cnpj" ) {
				var xcampo = campo2;
			}

			if (tipo == "codigo" ) {
				var xcampo = campo3;
			}

			if (xcampo.value != "") {
				var url = "";
				url = "posto_pesquisa_credenciamento.php?campo=" + xcampo.value + "&tipo=" + tipo ;
				janela = window.open(url, "janela", windowParams.replace('600', '500').replace('18',''));
				janela.retorno = selfLocation;
				janela.nome    = campo;
				janela.posto   = campo2;
				janela.codigo  = campo3;
				janela.focus();
			} else {
				alert("<?php echo utf8_decode('Preencha toda ou parte da informação para realizar a pesquisa!' ) ; ?>");
				return false;
			}
		}

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		function formatResult(row) {
			return row[0];
		}

		$("#codigo").autocomplete("?tipo_busca=posto&busca=codigo", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#nome").autocomplete("?tipo_busca=posto&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("button[name^=faturamento_]").on("click",function(){
			var faturamento = this.id;
			var btn_faturamento = $("#btn_faturamento_"+faturamento).val();
			var icon = $(this).find('i');

			if(btn_faturamento == "hidden"){
				$(this).removeClass("btn-primary");
				$(this).addClass("btn-danger");
				$("#table_posto_"+faturamento).show();
				$("#btn_faturamento_"+faturamento).val("show");
				icon.removeClass('icon-resize-full')
					.addClass('icon-resize-small');
			}else{
				$(this).removeClass("btn-danger");
				$(this).addClass("btn-primary");
				$("#table_posto_"+faturamento).hide();
				$("#btn_faturamento_"+faturamento).val("hidden");
				icon.removeClass('icon-resize-small')
					.addClass('icon-resize-full');
			}
		});

	});

	function retorna_posto(retorno){
	    $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	$(function(){

		$("#codigo_posto, #descricao_posto").keyup(function(){
			var total = $(this).val();
			if (total == '') {
				$(".required_peca").html("*");
			} else {
				$("#msg_error").hide();
				$("#msg_error").html('');
				$(".required_peca").html("");
				$(".erro_posto").removeClass("error");
				$(".erro_peca").removeClass("error");
			}
		});

		$("#peca_referencia, #peca_descricao").keyup(function(){
			var total = $(this).val();
			if (total == '') {
				$(".required_posto").html("*");
			} else {
				$("#msg_error").hide();
				$("#msg_error").html('');
				$(".required_posto").html("");
				$(".erro_posto").removeClass("error");
				$(".erro_peca").removeClass("error");
			}
		});

		$("#btn_gravar").click(function(){

			var peca_referencia    = $("#peca_referencia").val();
			var peca_descricao    = $("#peca_descricao").val();
			var codigo_posto    = $("#codigo_posto").val();
			var descricao_posto = $("#descricao_posto").val();

			if ((codigo_posto == '' && descricao_posto == '') && (peca_referencia == '' && peca_descricao == '')) {
				$("#msg_error").show();
				$("#msg_error").html('<h4>Preencha os campos obrigatórios</h4>');
				$(".erro_posto").addClass("error");
				$(".erro_peca").addClass("error");
				return false;
			} else {
				$("#msg_error").hide();
				$("#msg_error").html('');
				$(".erro_posto").removeClass("error");
				$(".erro_peca").removeClass("error");
			}

			$("#frm_relatorio_peca_pendente_lgr").submit();
		});

	});

	function retorna_peca(retorno) {
		$("#peca_referencia").val(retorno.referencia);
		$("#peca_descricao").val(retorno.descricao);
	}

</script>
<?php

if ((!empty($codigo_posto) || !empty($descricao_posto)) && (empty($peca_referencia) && empty($peca_descricao))) {
	$asteristicoPosto = false;
	$asteristicoPeca  = true;
} elseif ((empty($codigo_posto) && empty($descricao_posto)) && (!empty($peca_referencia) || !empty($peca_descricao))) {
	$asteristicoPosto = true;
	$asteristicoPeca  = false;
} else {
	$asteristicoPosto = false;
	$asteristicoPeca  = false;
}

?>
<div id="msg_error" class="alert alert-error" style="display: none;">
	
</div>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form id="frm_relatorio_peca_pendente_lgr" method="POST" class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span3">
			<div class="control-group erro_posto">
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<h5 class="asteristico required_posto"><?php echo ($asteristicoPosto) ? "" : "*";?></h5>
					<div class='input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span10' value="<?=$codigo_posto?>" />
						<span class='add-on' rel="lupa">
							<i class='icon-search' ></i>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>
		<div class="span4">
			<div class="control-group erro_posto">
				<label class='control-label' for='descricao_posto'>Razão Social</label>
				<div class='controls controls-row'>
					<h5 class="asteristico required_posto"><?php echo ($asteristicoPosto) ? "" : "*";?></h5>
					<div class='input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<?=$descricao_posto?>" />&nbsp;
						<span class='add-on' rel="lupa">
							<i class='icon-search' ></i>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</span>
					</div>

				</div>
			</div>
		</div>
	</div>
	<br/>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span3">
			<div class="control-group erro_peca">
				<label class='control-label' for='peca_referencia'>Código Peça</label>
				<div class='controls controls-row'>
					<h5 class="asteristico required_peca"><?php echo ($asteristicoPeca) ? "" : "*";?></h5>
					<div class='input-append'>
						<input type="text" name="peca_referencia" id="peca_referencia" class='span10' value="<?=$peca_referencia?>" />
						<span class='add-on' rel="lupa_peca">
							<i class='icon-search' ></i>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="referencia" preco="false" />
						</span>
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>
		<div class="span4">
			<div class="control-group erro_peca">
				<label class='control-label' for='peca_descricao'>Descrição</label>
				<div class='controls controls-row'>
					<h5 class="asteristico required_peca">
						<?php echo ($asteristicoPeca) ? "" : "*";?>
					</h5>
					<div class='input-append'>
						<input type="text" name="peca_descricao" id="peca_descricao" class='span12' value="<?=$peca_descricao?>" />&nbsp;
						<span class='add-on' rel="lupa_peca">
							<i class='icon-search' ></i>
							<input type="hidden" name="lupa_config" tipo="peca" parametro="descricao"  preco="false" />
						</span>
					</div>

				</div>
			</div>
		</div>
	</div>
	<br/>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<div class="control-group">
				<div class="controls controls-row">
					<div class="span12 tac">
						<button type="button" id="btn_gravar" class="btn">Pesquisar</button>
						<input type="hidden" id="btn_acao" name="btn_acao" value="pesquisar" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4"></div>
	</div>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span4">
			<div class="control-group">
				<div class="controls controls-row">
					<div class="span12 tac">
					<a href="relatorio_peca_pendente_lgr.php?lista_todos=t" class="btn btn-primary">Listar todas as pendências</a>
					</div>
				</div>
			</div>
		</div>
		<div class="span4"></div>
	</div>
</form>
<?php
flush();
if($_REQUEST){
	if (pg_num_rows($resPendencia) > 0) {?>
	<table id="table_pendencia" class='table table-striped table-bordered table-fixed' >
		<thead class="titulo_coluna">
			<tr>
				<td nowrap>Posto</td>
				<td nowrap>Razão Social</td>
				<td nowrap>Quantidade</td>
			</tr>
		</thead>
		<tbody>
<?php
		$queryStmt = pg_prepare(
			$con, 'LgrItens',
			" SELECT tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_os_item.qtde,
			tbl_os.os,
			tbl_os.sua_os,
			tbl_faturamento_item.preco
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING (faturamento)

			JOIN tbl_os_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_os_item.peca = tbl_faturamento_item.peca
			JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_os on tbl_os.os = tbl_os_produto.os
			JOIN tbl_peca                ON tbl_peca.peca    = tbl_os_item.peca
			AND tbl_peca.fabrica = $login_fabrica
			WHERE tbl_faturamento.posto    = $1
			AND tbl_faturamento.fabrica IN (10, $login_fabrica)
			AND tbl_faturamento.emissao >= '2010-01-01'
			AND tbl_faturamento.cancelada IS NULL
			AND tbl_faturamento_item.extrato_devolucao IS NULL
			AND tbl_os_item.peca_obrigatoria
			AND tbl_faturamento.cfop ~ E'^[56]9'
	{$condicao_peca}
	ORDER BY tbl_peca.referencia, qtde"
);

	if (!is_resource($queryStmt))
		die("ERRO: ".pg_last_error($con));

	while ($objeto_peca_pendente = pg_fetch_object($resPendencia)) { ?>
			<tr>
				<td class="tal"><?=$objeto_peca_pendente->codigo_posto?></td>
				<td class="tal"><?=$objeto_peca_pendente->nome?></td>
				<td>
					<button id="<?=$objeto_peca_pendente->posto?>" name="faturamento_<?=$objeto_peca_pendente->posto?>" class="btn btn-primary">
						<?=$objeto_peca_pendente->total_peca?>&nbsp;
						<i class="icon-resize-full icon-white"></i>
					</button>
					<input type="hidden" id="btn_faturamento_<?=$objeto_peca_pendente->posto?>" value="hidden"/>
				</td>
			</tr>
			<tr id="table_posto_<?=$objeto_peca_pendente->posto?>" style="display:none">
				<td colspan="3">
<?php
		$resPeca = pg_execute($con, 'LgrItens', array($objeto_peca_pendente->posto));

	if (pg_num_rows($resPeca) > 0) { ?>
					<table id="table_peca_posto" class='table table-striped table-bordered table-fixed' >
						<thead>
							<tr>
								<td class="titulo_coluna_peca" nowrap>Peça</td>
								<td class="titulo_coluna_peca" nowrap>Quantidade</td>
								<td class="titulo_coluna_peca" nowrap>Valor</td>
								<td class="titulo_coluna_peca" nowrap>OS</td>
							</tr>
						</thead>
						<tbody>
<?  while ($objeto_peca = pg_fetch_object($resPeca)) { 
?>
							<tr>
								<td class="tal"><?=$objeto_peca->referencia?> - <?=$objeto_peca->descricao?></td>
								<td><?=$objeto_peca->qtde?></td>
								<td class="tar"><?=number_format($objeto_peca->preco, 2, ",", ".")?></td>
								<td>
									<a href="os_press.php?os=<?=$objeto_peca->os?>" target="_blank"><?=$objeto_peca->sua_os?></a>
								</td>
							</tr>
<?php
	flush();
} ?>
						</tbody>
					</table>
<?php
	} else {
		if ($_serverEnvironment == 'development' and $pgerr = pg_last_error($con))
			die("ERRO NO BANCO: $pgerr\n<br />\n<pre><?=$sql?></pre>");
?>
					<div class="alert alert-error">
						<h4>Erro ao buscar as peças pendentes do posto</h4>
					</div>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php

		if (pg_num_rows($resPendenciaExcel) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_peca_pendente_lgr_{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");


			$thead = '	
				<table border="1">
				<thead>
				<tr>
				<th>Posto</th>
				<th>Razão Social</th>
				<th>Quantidade</th>
				</tr>
				</thead>
				<tbody>';
			fwrite($file, $thead);

			while ($rowsPecas = pg_fetch_array($resPendenciaExcel)) { 
				$conteudo = '
					<tr>
					<td align="center">' . $rowsPecas['codigo_posto'] . '</td>
					<td>' . $rowsPecas['nome'] . '</td>
					<td align="center">' . $rowsPecas['total_peca'] . '</td>
					</tr>
					<tr>
					<td colspan="3">';
				$resPeca = pg_execute($con, 'LgrItens', array($rowsPecas['posto'] ));

				if (pg_num_rows($resPeca) > 0) { 
					$conteudo .= '<table>
						<thead>
						<tr>
						<th>Peça</th>
						<th>Quantidade</th>
						<th>Valor</th>
						<th>OS</th>
						</tr>
						</thead>
						<tbody>';

					while ($objeto_peca = pg_fetch_object($resPeca)) { 
						$valorPeca = number_format($objeto_peca->preco, 2);
						$conteudo .= "
							<tr>
							<td> $objeto_peca->referencia - $objeto_peca->descricao</td>
							<td align='center'>$objeto_peca->qtde</td>
							<td align='center'>$valorPeca</td>
							<td align='center'><b>$objeto_peca->sua_os </b></td>
							</tr>";

					} 
					$conteudo .= '	
						</tbody>
						</table>';
				}
				$conteudo .= '
					</td>
					<tr>';
				fwrite($file, $conteudo);
			}

			$conteudo = '
				</tbody>
				</table>';
			fwrite($file, $conteudo);
			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

			}
		}

		echo "
			<a href='xls/{$fileName}' target='_blank'>
			<div class='btn_excel'>
			<span>
			<img src='imagens/excel.png' />
			</span>
			<span class='txt'>Download em Excel</span>
			</div>
			</a><br />";

	} else {
		echo '<div class="alert">
			<h4>Nenhum registro encontrado.</h4>
			</div>';
	}
}
include "rodape.php";

