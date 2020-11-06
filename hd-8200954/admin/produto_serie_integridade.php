<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$title = "Cadastro de Integridade de Produto e Série";
$layout_menu = "cadastro";

$msg_erro = array();


if (count($_POST)>0) {

	$produto_referencia = (trim($_POST['produto_referencia'])) ? trim($_POST['produto_referencia']) : '' ;
	$produto_descricao  = (trim($_POST['produto_descricao'])) ? trim($_POST['produto_descricao']) : '' ;
	$produto_id         = (trim($_POST['produto_id'])) ? trim($_POST['produto_id']) : '' ;

	if ($produto_id){
		$cond_produto = " AND tbl_serie_controle.produto = $produto_id ";
	}else{
		$cond_produto = '';
	}

	if ($_POST['ajax']){
		if ($_POST['excluir'] == 'true'){
			$id_excluir = $_POST['id'];
			$res = pg_query($con,"BEGIN TRANSACTION");

			$sql = "DELETE FROM tbl_serie_controle WHERE serie_controle=$id_excluir ";
			$res = pg_query($con,$sql);

			if (pg_last_error($con)) {
				$msg_erro[] = pg_last_error($con);
			}

			if (count($msg_erro)>0) {
				$res = pg_query($con,"ROLLBACK TRANSACTION");			
				echo "1|Erro";
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");
				echo "0|Ok";
			}
		}

		exit;
	}

}
include 'funcoes.php';
include 'cabecalho.php';
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

		$(".btn_ver_integridades").click(function() {
			var id = $(this).attr('rel');
			if ($('#tr_series_'+id).css('display') == 'none') {

				$('#tr_series_'+id).show();
				$(this).html('Esconder Séries <img src="imagens/barrow_up.png" alt="">');

			}else{

				$('#tr_series_'+id).hide();
				$(this).html('Ver Séries <img src="imagens/barrow_down.png" alt="">');

			};
		});

		$(".del_serie").click(function(){
			
			var id = $(this).attr('rel');
			$.ajax({

				type: "POST",
				url: "produto_serie_integridade.php",
				data: "ajax=true&excluir=true&id="+id,
				complete: function(http) {
					
					results = http.responseText;
					results = results.split('|');
					if (results[0] == '0'){

						$("#"+id).slideUp();

					}
				}

			});

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

</script>

<!-- FORM DE PESQUISA -->
<form action="<?=$PHP_SELF?>" method="POST" name="frm_pesquisa">
	<table class="formulario" width="700px" align="center" border="0" cellpadding="0" cellspacing="0" >
		<tr class="titulo_tabela">
			<th>Parâmetros de Pesquisa</th>
		</tr>
		
		<tr> <td>&nbsp;</td> </tr>

		<tr>
			<td>
				<table width="600px" align="center" border="0">
					<input type="hidden" name="produto_id" id="produto_id" value="<?=$produto_id?>" >
					<tr>
						<td>Produto Referência</td>
						<td>Produto Descrição</td>
					</tr>

					<tr>
						
						<td>
							<input type="text" name="produto_referencia" id="produto_referencia" class="frm" value="<?=$produto_referencia?>" />
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.frm_pesquisa.produto_referencia, 'referencia')" />
						</td>

						<td>
							<input type="text" name="produto_descricao" id="produto_descricao" class="frm" style="width:300px" value="<?=$produto_descricao?>" />
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisa" onclick="pesquisaProduto(document.frm_pesquisa.produto_descricao, 'descricao')" />
						</td>

					</tr>

				</table>
			</td>
		</tr>
		
		<tr> <td>&nbsp;</td> </tr>
		
		<tr>
			<td align="center">
				<input type="submit" value="Pesquisar" name="btn_submit">
			</td>
		</tr>
		
		<tr> <td>&nbsp;</td> </tr>

	</table>
</form>
<br>
<?php 

//PESQUISA PRINCIPAL
	$sql = " SELECT DISTINCT 
					tbl_produto.produto,
					tbl_produto.referencia,
			       	tbl_produto.descricao
			FROM tbl_serie_controle
			JOIN tbl_produto USING (produto)
			WHERE tbl_serie_controle.fabrica = $login_fabrica  
			$cond_produto
       ";

	$res = pg_query($con,$sql);

if ($cond_produto and pg_num_rows($res)==0) {
	?>
	<form action="produto_serie_integridade_cadastro.php" method="POST">

		<input type="hidden" name="produto_id" value="<?=$produto_id?>">
		<input type="hidden" name="produto_referencia" value="<?=$produto_referencia?>">
		<input type="hidden" name="produto_descricao" value="<?=$produto_descricao?>">
		
		<div class="formulario" style="margin:auto;width:700px">
			<p class="msg_erro">Não existe integridade cadastrada para o produto pesquisado.</p>
			
			<br>
			
			<p style="margin:auto;text-align:center;">
					<input type="submit" value="Clique Aqui para Cadastrar">
			</p>
			
		</div>

	</form>
	<?
}

if (pg_num_rows($res)>0) {

	?>
	<div class="titulo_tabela" style="width:700px;margin:auto;text-align:center">
		Relação de Produtos com Integridades Cadastradas
	</div>
	<table class="tabela" width="700px" align="center">
		<tr class="titulo_coluna">
			<th>Referência</th>
			<th>Descrição</th>
			<th>Açoes</th>
		</tr>
	<?

	for ($i=0; $i < pg_num_rows($res); $i++) { 
		
		$produto            = pg_fetch_result($res, $i, 'produto');
		$produto_referencia = pg_fetch_result($res, $i, 'referencia');
		$produto_descricao  = pg_fetch_result($res, $i, 'descricao');

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		?>
		<tr style="background-color:<?=$cor?>;font:bold 12px Arial" >
			<td><?php echo $produto_referencia; ?></td>
			<td><?php echo $produto_descricao; ?></td>
			<td>
				<button class="btn_ver_integridades" style="width:150px" rel="<?=$i?>">
					Ver Séries <img src="imagens/barrow_down.png" alt="">
				</button>
				<button>
					<a href="produto_serie_integridade_cadastro.php?produto_id=<?=$produto?>" style="font:12px Arial;color:#000">
						Adicionar Séries
					</a>
				</button>
			</td>
		</tr>

		<tr style="display:none" id="tr_series_<?=$i?>">
			<td colspan="3" style="background-color:#99B0FD">
				<table id="tbl_series_<?=$i?>" width="700px" align="center">
					<tr class='titulo_coluna'>
						<th>Série</th>
						<th>Data Digitação</th>
						<th>Ações</th>
					</tr>
					<?php  
					$sql = "SELECT 	serie_controle,
									serie,
									TO_CHAR(tbl_serie_controle.data_digitacao,'DD/MM/YYYY') as data_digitacao
							FROM tbl_serie_controle 
							WHERE fabrica=$login_fabrica 
							AND produto = $produto";
					$res_series = pg_query($con,$sql);
					if (pg_num_rows($res_series)>0) {
						
						for ($x=0; $x < pg_num_rows($res_series); $x++) { 
							$serie_controle = pg_fetch_result($res_series, $x, 'serie_controle');
							$serie = pg_fetch_result($res_series, $x, 'serie');
							$data_digitacao = pg_fetch_result($res_series, $x, 'data_digitacao');
							$cor2 = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							?>
							<tr id="<?=$serie_controle?>" style="background-color:<?=$cor2?>" >
								<td><?php echo $serie ?></td>
								<td><?php echo $data_digitacao ?></td>
								<td>
									<button class="del_serie" rel="<?=$serie_controle?>" >
										Excluir
									</button>
								</td>
							</tr>
							<?		
						
						}
					}
					?>
				</table>
			</td>
		</tr>
		<?

	}
	?>	
	</table>
	<?
}



include 'rodape.php';
