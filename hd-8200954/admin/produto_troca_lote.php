<?php  
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (isset($_POST) && count($_POST)>0){

	//RECEBE PARAMETROS PARA PESQUISA
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = trim($_POST['codigo_posto']);
	$posto_nome         = trim($_POST['posto_nome']);
	$posto              = $_POST['posto'];
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_descricao']);
	$produto_id         = $_POST['produto_id'];
	$ordena             = $_POST['ordena'];

	list($di, $mi, $yi) = explode("/", $data_inicial);
    list($df, $mf, $yf) = explode("/", $data_final);
	$aux_data_inicial = "$yi-$mi-$di";
	$aux_data_final   = "$yf-$mf-$df";

	$condPosto = (!empty($posto)) ? " AND tbl_os.posto = $posto " : '' ;
	$condProduto = (!empty($produto_id)) ? " AND tbl_os.produto = $produto_id " : '' ;

	$condOrderBy = ($ordena == 'ordena_posto') ? "tbl_posto_fabrica.nome_fantasia" : "tbl_os.data_abertura desc" ;

}


$layout_menu = "callcenter";
$title = "Troca em Lote de Produtos";
include "cabecalho.php";
?>
<style type="text/css">
	@import "../plugins/jquery/datepick/telecontrol.datepick.css";
	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	    margin:auto;
	    width:700px;
	}

	.msg_erro{
	    background-color:#FF0000;
	    font: bold 16px "Arial";
	    color:#FFFFFF;
	    width:700px;
	    margin:auto;
	    text-align:center;
	}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    width:700px;
	    margin:auto;
	    text-align:center;
	}

	.titulo_tabela{
	    background-color:#596d9b;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	.titulo_coluna{
	    background-color:#596d9b !important;
	    font: bold 11px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	table.tabela{
		width:1200px;
		margin:auto;
		background-color: #F7F5F0;
	}

	table.tabela tr td{
	    font-family: verdana;
	    font-size: 11px;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}

	.hideTr{
		display:none;
	}

	div.legendas span{
		padding:5px 10px 5px 10px;
	}

	.os_com_troca{
		background-color:#e3df73 !important;
	}

	#tbl_relatorio thead tr th {

		cursor: pointer;

	}
	
</style>

<script src="js/jquery-1.8.3.min.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>

<script type="text/javascript">
	$(function() {

		Shadowbox.init();
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$.tablesorter.defaults.widgets = ['zebra'];
		$("#tbl_relatorio").tablesorter();

		/* Busca AutoComplete pelo Código do posto */
		$("#codigo_posto").autocomplete("produto_troca_lote_ajax.php?ajax=true&tipo_busca=posto&busca=codigo", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#codigo_posto").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
			$("#posto").val(data[3]) ;
		});

		/* Busca AutoComplete pelo Nome do posto */
		$("#posto_nome").autocomplete("produto_troca_lote_ajax.php?ajax=true&tipo_busca=posto&busca=nome", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#codigo_posto").val(data[2]) ;
			$("#posto").val(data[3]) ;
		});

		//Quando der blur no codigo ou nome do posto e o valor for vazio, deverá zerar o valor do hidden
		$('#posto_nome, #codigo_posto').blur(function() {
			if ($(this).val() == '') {
				$('#posto').val('');
			};
		});

		//Quando der blur na referência ou nome do produto e o valor for vazio, deverá zerar o valor do hidden
		$('#produto_referencia, #produto_descricao').blur(function() {
			if ($(this).val() == '') {
				$('#produto_id').val('');
			};
		});

		$('#produto_referencia_trocar, #produto_descricao_trocar').blur(function() {
			if ($(this).val() == '') {
				$('#produto_id_trocar').val('');
			};
		});

		//ENVIA PARA O PROGRAMA DO AJAX VALIDAR O FORM
		$('#btn_pesquisa').click(function(){

			$.ajax({

				type: "GET",
				url: "produto_troca_lote_ajax.php",
				data: "ajax=true&validar=true&"+$('form[name=frm_pesquisa]').find('input').serialize(),
				complete: function(http) {
					
					results = http.responseText;
					results = results.split('|');
					if (results[0] == 1){

						$('div.msg_erro').html(results[1]);

					}else{
						$('form[name=frm_pesquisa]').submit();
					}
				}

			});

		});

		$('.btn_cancelar').click(function() {
			var os_cancelar = $(this).attr('rel');
			var sua_os_cancelar = $('#'+os_cancelar).attr('rel');
			if (confirm('Deseja Realmente Cancelar a OS '+sua_os_cancelar+' ?')) {

				$.ajax({

					type: "GET",
					url: "produto_troca_lote_ajax.php",
					data: "ajax=true&cancelar=true&os="+os_cancelar,
					complete: function(http) {
						
						results = http.responseText;
						results = results.split('|');
						if (results[0] == 1){

							alert(results[1]);

						}else{
							
							$('.btn_trocar_os_'+os_cancelar).hide();
							$('#btn_cancelar_os_'+os_cancelar).hide();
							$('#'+os_cancelar).hide();
							alert('OS Cancelada com Sucesso');

						}

					}

				});

			}

		});

		// SELECIONAR TODAS AS OS's
		$('.selecionar_todos').click(function() {
			if ($(this).is(":checked")) {
				$('.check_os').attr('checked',true);
			}else{
				$('.check_os').attr('checked',false);
			};
		});

		//change do <select> de ação com varias OS's
		$('select[name=trocar_por]').change(function(){
			if ($(this).val() == 'trocar_por') {
				$(".produto_trocar").show();
			}else{
				$(".produto_trocar").hide();
			}
		});

		$('input[name=btn_trocar_varias]').click(function() {
			
			var input_checked = $('input[name^=check]:checked').serialize()
			var tipo_troca = $('#trocar_por').val();
			var produto = $("#produto_id_trocar").val();
			
			if (input_checked.length == 0){
				alert("Para este tipo de troca selecione ao menos uma OS");
				return false;
			};

			if (tipo_troca == 'trocar_por' && produto.length == 0) {
				alert("Para este tipo de troca informe o produto que deseja trocar");
				return false;
			};

			if (confirm("Deseja realmente efetuar a troca em lote das OS's selecionadas?")) {

				$.ajax({

					type: "POST",
					url: "produto_troca_lote_ajax.php",
					data: "ajax=true&trocar_varios=true&produto="+produto+"&tipo_troca="+tipo_troca+"&"+input_checked,
					complete: function(http) {
						
						results = http.responseText;
						results = results.split('|');
						if (results[0] == 1){

							alert(results[1]);

						}else{
							
							alert('TROCA EM LOTE EFETUADA COM SUCESSO');
							var os_trocadas = results[2];
							
							os_trocadas = os_trocadas.split(',');
							for (var i in os_trocadas){
								var os = os_trocadas[i];
								$('#'+os).hide();
								$('input[name^=check]:checked').attr('checked',false);
							}

						}

					}

				});

			};

		});

	});

	function pesquisaProduto(campo,tipo,posicao){

	    if (jQuery.trim(campo.value).length > 2){
	        Shadowbox.open({
	            content:	"produto_pesquisa_2_nv.php?posicao="+posicao+"&"+tipo+"="+campo.value,
	            player:	    "iframe",
	            title:		"Pesquisa Produto",
	            width:	    800,
	            height:	    500
	        });
	    }else{
	        alert("Informar toda ou parte da informação para realizar a pesquisa!");
	        campo.focus();
	    }
	}

	function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){
	    if (posicao == 2) {

	    	gravaDados('produto_referencia_trocar',referencia);
		    gravaDados('produto_descricao_trocar',descricao);
		    gravaDados('produto_id_trocar',produto);

	    }else{

		    gravaDados('produto_referencia',referencia);
		    gravaDados('produto_descricao',descricao);
		    gravaDados('produto_id',produto);

	    };
	}

	function gravaDados(name, valor){
	    try{
	        $("input[name="+name+"]").val(valor);
	    } catch(err){
	        return false;
	    }
	}

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
</script>


<div id="msg_erro" class="msg_erro" ></div>
<div id="sucesso"  class="sucesso" ></div>

<form action="" method="post" name="frm_pesquisa">
	<input type="hidden" name="posto" id="posto" value="<?=$posto?>">
	<div class="titulo_tabela" style="width:700px;margin:auto">Parâmetros de Pesquisa</div>
	<table class="formulario" width="700px" align="center" >
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>
				<table width="600px" align="center">
					<tr>
						<td>Código Posto</td>
						<td>Nome Posto</td>
					</tr>
					<tr>
						<td>
							<input type="text" name="codigo_posto" id="codigo_posto" class="frm" value="<?=$codigo_posto?>" >
						</td>
						<td>
							<input type="text" name="posto_nome" id="posto_nome" class="frm" value="<?=$posto_nome?>" >
						</td>
					</tr>
					<tr>
						<td>Produto Referência</td>
						<td>Produto Descrição</td>
					</tr>

					<tr>

						<td>
							
							<input type="hidden" name="produto_id" id="produto_id" value="<?=$produto_id?>" >
							<input type="text" name="produto_referencia" id="produto_referencia" class="frm" value="<?=$produto_referencia?>" />
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.frm_pesquisa.produto_referencia, 'referencia')" />
						
						</td>

						<td>

							<input type="text" name="produto_descricao" id="produto_descricao" class="frm" style="width:300px" value="<?=$produto_descricao?>" />
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.frm_pesquisa.produto_descricao, 'descricao')" />
					
						</td>

					</tr>

					<tr>
						<td>Data Inicial:</td>
						<td>Data Final:</td>
					</tr>
					<tr>
						<td>
							<input type="text" name="data_inicial" id="data_inicial" class='frm' size="12" value="<?=$data_inicial?>">
						</td>
						<td>
							<input type="text" name="data_final" id="data_final" class='frm' size="12" value="<?=$data_final?>">
						</td>
					</tr>
					<tr>
						<td>
							<fieldset>
							
								<legend>Ordenar por:</legend>
								<?php  
								$check_ordena_posto = '';
								$check_ordena_data  = '';
								if ($ordena == 'ordena_posto'){
									$check_ordena_posto = 'CHECKED';
								}else{
									$check_ordena_data = 'CHECKED';
								}
								?>

								<input type="radio" name="ordena" id="ordena_posto" value="ordena_posto" <?=$check_ordena_posto?> > 
								<label for="ordena_posto">Nome do Posto</label>
								<br>
								
								<input type="radio" name="ordena" id="ordena_abertura" value="ordena_data" <?=$check_ordena_data?> > 
								<label for="ordena_abertura">Data de Abertura da OS</label>
							
							</fieldset>
						</td>
						<td></td>
					</tr>
				</table>
			</td>
		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr>

		<tr>
			<td align="center">
				<input type="button" value="Pesquisar" id="btn_pesquisa" name="btn_pesquisa" >
			</td>
		</tr>

	</table>
</form>

<br>
<?


if (isset($_POST) && count($_POST)>0){

	$sql = "SELECT 	tbl_os.os,
					tbl_os.sua_os,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura,
					tbl_posto_fabrica.nome_fantasia,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.contato_fone_comercial,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_produto.produto_critico,
					tbl_os_troca.os as os_troca

			FROM tbl_os 

			JOIN tbl_posto_fabrica using (posto,fabrica) 
			JOIN tbl_produto using (produto)
			LEFT JOIN tbl_os_troca on (tbl_os.os = tbl_os_troca.os)

			WHERE tbl_os.fabrica 			= $login_fabrica 
			AND   tbl_posto_fabrica.fabrica = $login_fabrica 
			AND   tbl_os.excluida 			is false 
			AND   tbl_os.finalizada 		is null 
			AND   tbl_os.data_fechamento 	is null
			AND   tbl_os_troca.pedido 		is null
			AND   tbl_produto.produto_critico is true 
			AND   tbl_os.data_abertura between '$aux_data_inicial' and '$aux_data_final' 
			AND   tbl_os.status_os_ultimo = 62
			$condPosto
			$condProduto

			ORDER BY $condOrderBy
			";
	// echo nl2br($sql);
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0) {
		?>

		<div class="titulo_tabela" style="width:1200px;margin:auto">Relação de Ordens de Serviço</div>
		
		<table class="tabela" id="tbl_relatorio">
			<thead>
					
				<tr class="titulo_coluna">
					<th>
						<input type="checkbox" name="selecionar_todos" class="selecionar_todos" title="Selecionar todas">
					</th>
					<th>OS</th>
					<th>Abertura</th>
					<th>Posto Referência</th>
					<th>Posto Nome</th>
					<th>Fone Posto</th>
					<th>Produto</th>
					<th>Ações</th>
				</tr>
			</thead>

		<?	
		foreach (pg_fetch_all($res) as $id => $field) {
			
			$os                 = $field['os'];
			$sua_os             = $field['sua_os'];
			$data_abertura      = $field['data_abertura'];
			$nome_fantasia      = $field['nome_fantasia'];
			$codigo_posto       = $field['codigo_posto'];
			$fone_posto         = $field['contato_fone_comercial'];
			$produto_referencia = $field['referencia'];
			$produto_descricao  = $field['descricao'];
			$produto_critico  	= $field['produto_critico'];
			$os_troca           = $field['os_troca'];

			$cor = ($id % 2) ? "#F7F5F0" : "#F1F4FA";

			$class_troca = ($os_troca) ? 'os_com_troca' : '' ;

			?>
			<tr style="background-color:<?=$cor?>" class='<?=$class_troca?>' id="<?=$os?>" rel="<?=$sua_os?>"  >
				<td>
					<input type="checkbox" name="check[<?=$id?>]" id="check_<?=$id?>" class='check_os' value="<?=$os?>" >
				</td>
				<td>
					<a href="os_press.php?os=<?=$os?>" target="_blank" > <?php echo $sua_os ?> </a>
				</td>
				<td>
					<?php echo $data_abertura ?>
				</td>
				<td>
					<?php echo $codigo_posto ?> 
				</td>
				<td>
					<?php echo $nome_fantasia ?>
				</td>
				<td>
					<?php echo $fone_posto ?>
				</td>
				<td>
					<?php echo $produto_referencia." - ".$produto_descricao ?>
				</td>
				<td>
					<button class='btn_trocar_os_<?=$os?>' onclick='window.open ("os_cadastro.php?os=<?=$os?>&osacao=trocar");' style="cursor:pointer" >
						Trocar
					</button>
					<button style="font:12px Arial;color:#000" class="btn_cancelar" id="btn_cancelar_os_<?=$os?>" rel="<?=$os?>" >
						Cancelar
					</button>
				</td>
			</tr>

			<?
		}
		?>
		
		</table>

		<table class='tabela'>
			<tr class="titulo_coluna">
			<td colspan="3">
				<input type="checkbox" name="selecionar_todos" class="selecionar_todos" title="Selecionar todas">
				Selecionar Todas
			</td>
			<td>
				Com selecionadas: 
				<select name="trocar_por" id="trocar_por">
					<option value="trocar_por">Trocar Por</option>
					<option value="ressarcimento">Ressarcimento</option>
				</select>
			</td>
			<td colspan="2">
				<div class="produto_trocar">
					<div style="float:left;text-align:left">
						Produto Referência
						<br>
						<input type="hidden" name="produto_id_trocar" id="produto_id_trocar" value="" >
						<input type="text" name="produto_referencia_trocar" id="produto_referencia_trocar" class="frm" value="" />
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.getElementById('produto_referencia_trocar'), 'referencia','2')" />
					</div>
						
					<div style="text-align:left">
						
						Produto Descrição
						<br>
						<input type="text" name="produto_descricao_trocar" id="produto_descricao_trocar" class="frm" style="width:300px" />
						<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.getElementById('produto_descricao_trocar'), 'descricao','2')" />
					
						
					</div>
				</div>
			</td>
			<td>
				<input type="button" value="Trocar" name="btn_trocar_varias" >
			</td>
		</tr>
		</table>

		<?

	}else{
		?>
		<div class="titulo_tabela" style="width:700px;margin:auto" > Não foram encontrados resultados para esta pesquisa</div>
		<?php
	}

}

include "rodape.php";
