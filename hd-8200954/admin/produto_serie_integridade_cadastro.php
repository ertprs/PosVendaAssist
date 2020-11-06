<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "Cadastro de Integridade de Produto e Série";
$layout_menu = "cadastro";

$msg_erro = array();

include 'cabecalho.php';

$produto_id         = (trim($_REQUEST['produto_id'])) ? trim($_REQUEST['produto_id']) : '' ;

if ($produto_id){

	$cond_produto = " AND tbl_serie_controle.produto = $produto_id ";
	$sql = "SELECT referencia,
			       descricao
			FROM tbl_produto
			WHERE produto = $produto_id";
	$res = pg_query($con,$sql);
	$produto_referencia = pg_fetch_result($res, 0, 'referencia');
	$produto_descricao  = pg_fetch_result($res, 0, 'descricao');

}else{
	$cond_produto = '';
}


if (count($_POST)>0) {

	if (isset($_POST['qtde_itens']) and $_POST['qtde_itens'] != '0' ) {
		
		$qtde_itens = $_POST['qtde_itens'];
		for ($i=0; $i < $qtde_itens; $i++) { 
			$serie = trim($_POST['serie_'.$i]);
			$sql = "INSERT INTO tbl_serie_controle (
					fabrica,
					produto,
					serie,
					quantidade_produzida
					)VALUES(
					$login_fabrica,
					$produto_id,
					'$serie',
					0
					)";

			$res = pg_query($con,$sql);
			if (pg_last_error($con)) {
				$msg_erro[] = pg_last_error($con);
			}
		}

		if (count($msg_erro) == 0) {
			$sucesso = "Gravado com Sucesso";
		}

	}

}

?>
<style type="text/css">

	table.tabela tr td{
		font-family: verdana;
		font-size: 10px;
		border-collapse: collapse;
		border:1px solid #121768;
	}

	table.tabela tr th{
		font-family: verdana;
		font-size: 10px;
		border-collapse: collapse;
		border:1px solid #121768;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial" !important;
		color:#FFFFFF;
		text-align:center;
	}

	.sucesso{
	    background-color:#008000;
	    font: bold 14px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	    margin:auto;
	    width:700px;
	}

	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
		margin: auto;
		width: 700px;
	}

	.formulario{
	    background-color:#D9E2EF;
	    font:11px Arial;
	    text-align:left;
	}

	.showTable{
		display:block;
		width:700px;
		margin:auto
	}

	.hideTable{
		display: none !important;
		width:700px;
		margin:auto
	}

</style>

<script src="js/jquery-1.8.3.min.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript">
	$(function() {
		Shadowbox.init();

		$("#produto_referencia, #produto_descricao").blur(function() {
			if ($(this).val() == ''){
				$("#produto_id").val('');
			}
		});

		$("input[name=btn_submit]").click(function() {
			if ($('#nro_serie').val() == ''){
				alert("Insira uma Série para Gravar");
				return false;
			}
			adicionaIntegridade();
			
		});

	});

	function pesquisaProduto(campo,tipo){

	    if (jQuery.trim(campo.value).length > 2){
	        Shadowbox.open({
	            content:	"produto_pesquisa_2_nv.php?"+tipo+"="+campo.value,
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
	    gravaDados('produto_referencia',referencia);
	    gravaDados('produto_descricao',descricao);
	    gravaDados('produto_id',produto);
	}

	function gravaDados(name, valor){
	    try{
	        $("input[name="+name+"]").val(valor);
	    } catch(err){
	        return false;
	    }
	}

	function adicionaIntegridade() {

		var tbl = document.getElementById('table_serie_contents');
		var lastRow = tbl.rows.length;
		var qtde = $('#qtde_itens').val();

		if (lastRow>0){
			$('#tbl_series_a_gravar').addClass("showTable");
			$('#tbl_series_a_gravar').removeClass("hideTable");
		}

		var linha = document.createElement('tr');

		// COLUNA 1 - SERIE
		var celula = criaCelula($('#nro_serie').val());
		celula.style.cssText = 'text-align: left; color: #000000;font-size:12px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'serie_' + qtde);
		el.setAttribute('id', 'serie_' + qtde);
		el.setAttribute('value',$('#nro_serie').val());
		celula.appendChild(el);

		linha.appendChild(celula);

		// coluna 6 - botao acao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		tbl.appendChild(tbody);

		qtde++;
		$("#qtde_itens").val(qtde);
		$('#nro_serie').val('');

	}

	function removerIntegridade(iidd){
		var tbl = document.getElementById('table_serie_contents');
		tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);
		var qtde = $('#qtde_itens').val();
		qtde -= 1;
		$("#qtde_itens").val(qtde);

		var lastRow = tbl.rows.length;

		if (lastRow==1){
			$('#tbl_series_a_gravar').addClass("hideTable");
			$('#tbl_series_a_gravar').removeClass("showTable");
		}
	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}


</script>


<?php if ($sucesso): ?>
	<div class="sucesso" style="width:700px;margin:auto">
		<?php echo $sucesso ?>
	</div>
<?php endif ?>
<!-- FORM DE CADASTRO -->

<table class="formulario" width="700px" align="center" border="0" cellpadding="0" cellspacing="0" >
	<tr class="titulo_tabela">
		<th>Parâmetros de Cadastro</th>
	</tr>
	
	<tr> <td>&nbsp;</td> </tr>

	<tr>
		<td>
			<table width="600px" align="center" border="0">
				<input type="hidden" name="produto_id" id="produto_id" value="<?=$produto_id?>" >
				<input type="hidden" name="produto_referencia" id="produto_referencia" value="<?=$produto_referencia?>" >
				<input type="hidden" name="produto_descricao" id="produto_descricao" value="<?=$produto_descricao?>" >
				<tr>
					<td>Produto Referência</td>
					<td>Produto Descrição</td>
				</tr>

				<tr>
					
					<td>
						<label for="">
							<?php echo $produto_referencia ?>
						</label>
					</td>

					<td>
						<label for="">
							<?php echo $produto_descricao ?>
						</label>
					</td>

				</tr>

				<tr>
					<td>&nbsp;</td>
				</tr>

				<!-- input nro_serie -->
				<tr>
					<td>Número de Série</td>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>
						<input type="text" name="nro_serie" id="nro_serie" class="frm" maxlength="30" >
					</td>
					<td>&nbsp;</td>
				</tr>

			</table>
		</td>
	</tr>
	
	<tr> <td>&nbsp;</td> </tr>
	
	<tr>
		<td align="center">
			<input type="button" value="Inserir" name="btn_submit">
			<a href="produto_serie_integridade.php"><input type="button" value="Voltar para a Pesquisa"></a>
		</td>
	</tr>
	
	<tr> <td>&nbsp;</td> </tr>

</table>

<br>

<!-- TABELA E FORM QUE VAO FAZER O INSERT -->
<div id="tbl_series_a_gravar" class="hideTable">
	
<form action="<?=$PHP_SELF?>" method="POST" name="frm_cadastro" >
	<input type="hidden" name="produto_id" id="produto_id" value="<?=$produto_id?>" >
	<input type="hidden" name="produto_referencia" id="produto_referencia" value="<?=$produto_referencia?>" >
	<input type="hidden" name="produto_descricao" id="produto_descricao" value="<?=$produto_descricao?>" >
	<input type="hidden" name="qtde_itens" id="qtde_itens" value="0" >
	<table class="formulario" width="700px" align="center">
		
		<tr class='titulo_tabela'>
			<td>
				Séries a Gravar:
			</td>
		</tr>

		<tr>
			<td>
				<table id="table_serie_contents" class="tabela" width="600px" align="center" >
					<tr class="titulo_coluna">
						<th>Série:</th>
						<th>Ação</th>
					</tr>
				</table>
			</td>
		</tr>

		<tr>
			<td align="center">
				<input type="submit" value="Gravar Séries">
			</td>
		</tr>

	</table>
</form>
<br>
</div>
<?
$sql = " SELECT tbl_serie_controle.serie,
			       TO_CHAR(tbl_serie_controle.data_digitacao,'DD/MM/YYYY') as data_digitacao
			FROM tbl_serie_controle
			WHERE tbl_serie_controle.fabrica = $login_fabrica  
			$cond_produto
    ";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0){
?>
<div class="titulo_tabela" style="width:700px;margin:auto;">
	Relação de Séries Gravadas
</div>
<table class="tabela" width="700px" align="center" border="0" >
	<tr class="titulo_coluna">
		<th width="50%">
			Série
		</th>
		<th>
			Data de Digitação
		</th>
	</tr>
<?php
	
		for ($i=0; $i < pg_num_rows($res); $i++) { 
			$serie = pg_fetch_result($res, $i, 'serie');
			$data_digitacao = pg_fetch_result($res, $i, 'data_digitacao');
			?>
			<tr>
				<td>
					<?php echo $serie ?>
				</td>
				<td>
					<?php echo $data_digitacao ?>
				</td>
			</tr>
			<?
		}
	

?>	
</table>
<?
	}

include 'rodape.php';
