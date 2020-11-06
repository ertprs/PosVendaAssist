<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "autentica_admin.php";

	if(isset($_POST['acao'])) {
		$produto = $_POST['produto'];
		$peca    = $_POST['peca'];
		$qtde    = (int) $_POST['qtde'];
		$qtde    = empty($qtde) ? 0 : $qtde;

		if($_POST['acao'] == 'BuscaPeca'){
			$sql = "	SELECT 
						peca,
						referencia,
						descricao
					FROM tbl_peca
					WHERE 
						referencia='$peca' 
						AND fabrica = $login_fabrica;";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) == 1){
					$peca   = pg_fetch_result($res,0,'peca');
					$referencia   = pg_fetch_result($res,0,'referencia');
					$descricao   = pg_fetch_result($res,0,'descricao');

					echo "$referencia|$descricao|$peca";
				}else{
					echo 0;
				}
			exit;
		}

		if(!empty($produto) and !empty($peca)) {
			//echo "VALOR 1 =".$produto."<BR>";
			//echo "VALOR 2 =".$peca."<BR>";
			if($acao =='cadastrar') {
			
				$sql = "SELECT DISTINCT $login_fabrica,
						 'SEM SERIE',
						 tbl_peca.referencia,
						 tbl_numero_serie.numero_serie,
						 tbl_peca.peca,
						 $qtde
						 FROM tbl_produto
						 JOIN tbl_numero_serie USING(produto)
						 LEFT JOIN tbl_peca ON (tbl_peca.peca IN ($peca))
						 WHERE tbl_produto.produto = $produto
						 AND   tbl_peca.fabrica = $login_fabrica
						 AND   tbl_numero_serie.fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				
				if (pg_num_rows($res) ) {
					$sql = " INSERT INTO tbl_numero_serie_peca(
								fabrica,
								serie_peca,
								referencia_peca,
								numero_serie,
								peca,
								qtde
							)
							 SELECT DISTINCT $login_fabrica,
							 'SEM SERIE',
							 tbl_peca.referencia,
							 tbl_numero_serie.numero_serie,
							 tbl_peca.peca,
							 $qtde
							 FROM tbl_produto
							 JOIN tbl_numero_serie USING(produto)
							 LEFT JOIN tbl_peca ON (tbl_peca.peca IN ($peca))
							 WHERE tbl_produto.produto = $produto
							 AND   tbl_peca.fabrica = $login_fabrica
							 AND   tbl_numero_serie.fabrica = $login_fabrica
						"; 
				}
				else
					$msg_erro = 'Numero de série não cadastrado no produto';
					
				$tipo = 'cadastrados';
			} else if ($acao == 'alterar') {
				$sql = "
					UPDATE tbl_numero_serie_peca
					SET qtde = $qtde
					FROM tbl_numero_serie
						WHERE tbl_numero_serie.fabrica = $login_fabrica
						AND   tbl_numero_serie_peca.fabrica = $login_fabrica
						AND   tbl_numero_serie.numero_serie = tbl_numero_serie_peca.numero_serie
						AND   tbl_numero_serie.produto = $produto
						AND   tbl_numero_serie_peca.peca    = $peca;
				"; 
				$tipo = 'cadastrados';
			}			
			else{
				$sql = " DELETE FROM tbl_numero_serie_peca
						USING tbl_numero_serie
						WHERE tbl_numero_serie.fabrica = $login_fabrica
						AND   tbl_numero_serie_peca.fabrica = $login_fabrica
						AND   tbl_numero_serie.numero_serie = tbl_numero_serie_peca.numero_serie
						AND   tbl_numero_serie.produto = $produto
						AND   tbl_numero_serie_peca.peca    = $peca";
				$tipo = 'excluidos';
			}
	
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			$total = pg_affected_rows($res);

			if(strlen($msg_erro) == 0){
				if($tipo == 'cadastrados')
					echo "ok|<a href='javascript:manutencaoSerie($produto,$peca,\"excluir\")'>Excluir</a>&nbsp; <a href='javascript:manutencaoSerie($produto,$peca,\"alterar\")'>Alterar</a>";
				else
					echo "ok|<a href='javascript:manutencaoSerie($produto,$peca,\"cadastrar\")'>Cadastrar</a>";
			}else{
				if ($msg_erro == 'Numero de série não cadastrado no produto')
					echo "erro|Erro: Produto sem Num. de S&eacute;rie";
				else
					echo  "erro|Erro ao $acao essa pe&ccedil;a.";
			}
		}
		exit;
	}

	$layout_menu = "cadastro";
	$title = "MANUTENÇÃO DE NÚMERO DE SÉRIE PEÇA";
	include "cabecalho.php";

	if(isset($_POST['btn_acao'])) {
		$produto_referencia	= $_POST['produto_referencia'];
		$produto_descricao	= $_POST['produto_descricao'];
		
		$peca_referencia		= $_POST['peca_referencia_multi'];
		$peca_referencia_multi	= $_POST['peca_multi'];

		$peca_descricao_multi	= $_POST['peca_descricao_multi'];

		if(strlen($produto_referencia) == 0) {
			$msg_erro = "Por favor, informe um produto";
		}

		if(count($peca_referencia_multi) == 0 AND strlen($msg_erro) == 0) {
			$msg_erro = "Nenhuma peça foi adicionada";
		}

		if($_POST['btn_acao'] == 'Pesquisar' and strlen($msg_erro) == 0) {
			
			$sql = " SELECT descricao,produto
					FROM tbl_produto
					JOIN tbl_linha USING(linha)
					WHERE tbl_linha.fabrica = $login_fabrica
					AND   referencia='$produto_referencia'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$produto   = pg_fetch_result($res,0,'produto');
				$descricao = pg_fetch_result($res,0,'descricao');
			}else{
				$msg_erro = "Produto $produto_referencia não encontrado";
			}
		
			if(strlen($msg_erro) == 0){
				foreach($peca_referencia_multi as $peca_referencia){
					$sql = " SELECT peca,descricao
							FROM tbl_peca
							WHERE referencia='$peca_referencia'	";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$peca = pg_fetch_result($res,0,'peca');
						$peca_descricao = pg_fetch_result($res,0,'descricao');
						$sql = " SELECT DISTINCT tbl_peca.descricao, tbl_numero_serie_peca.qtde
								FROM tbl_numero_serie
								JOIN tbl_numero_serie_peca USING(numero_serie)
								JOIN tbl_peca              USING(peca)
								WHERE produto = $produto
								AND   tbl_numero_serie.fabrica = $login_fabrica
								AND   peca =$peca";
						$res = pg_query($con,$sql);
						$cor = ($cor =='#F1F4FA') ? "#F7F5F0" : "#F1F4FA";
						$resultado .= "	<tr bgcolor='$cor'>
											<td>$produto_referencia - $descricao</td>
											<td>$peca_referencia - $peca_descricao</td>";
								
						if(pg_num_rows($res) > 0){
							if ($login_fabrica == 95) {
								$resultado .= '<td><input name="qtde_'.$peca.'" size="2" value="'.pg_result($res,0,'qtde').'" /></td>';
								$resultado .="	<td id='$peca'><a href='javascript:manutencaoSerie($produto,$peca,\"excluir\")'>Excluir</a>&nbsp; <a href='javascript:manutencaoSerie($produto,$peca,\"alterar\")'>Alterar</a></td>";
							}
							else
								$resultado .="	<td id='$peca'><a href='javascript:manutencaoSerie($produto,$peca,\"excluir\")'>Excluir</a></td>";
						}else{
							if ($login_fabrica == 95) {
								$resultado .= '<td>
														<input name="qtde_'.$peca.'" value="'.$qtde.'" size="2" />
													</td>';
							}
							$resultado .="	<td id='$peca'><a href='javascript:manutencaoSerie($produto,$peca,\"cadastrar\")' >Cadastrar</a></td>";
						}
						$resultado .="</tr>";
					}else{
						$msg_erro = "Peça $peca_referencia não encontrada";
						break;
					}
				}
			}
		}
	}
?>
<script src="../js/jquery-1.3.2.js" type="text/javascript"></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		padding: 5px 0;
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.texto_avulso{
		font: 14px Arial; 
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
	}
</style>

<script type="text/javascript">
	$(document).ready(function() {
		Shadowbox.init();
		
	});

	function pesquisaProduto(produto,tipo){

		if (jQuery.trim(produto.value).length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_nv.php?"+tipo+"="+produto.value,
				player:	"iframe",
				title:		"Produto",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			produto.focus();
		}
	}

	function pesquisaPeca(peca,tipo){

		if (jQuery.trim(peca.value).length > 2){
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?"+tipo+"="+peca.value,
				player:	"iframe",
				title:		"Peça",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			peca.focus();
		}

	}

	function retorna_produto(produto,referencia,descricao, numero_serie, posicao){
		gravaDados("produto_referencia_"+posicao,referencia);
		gravaDados("produto_descricao_"+posicao,descricao);
		gravaDados("produto_serie_"+posicao,numero_serie);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	try{
		xmlhttp = new XMLHttpRequest();
	}catch(ee){
		try{
			xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
		}catch(e){
			try{
				xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
			}catch(E){
				xmlhttp = false;
			}
		}
	}

	function fnFeaturesInit(){
		$('ul.limit_length>li').each( function(i) {
			if ( i > 10 ) {
				this.style.display = 'none';
			}
		});
		
		$('ul.limit_length').append('<li class="css_link">Show more<\/li>' );
		$('ul.limit_length li.css_link').click( function () {
			$('ul.limit_length li').each( function(i) {
				if ( i > 5 ) {
					this.style.display = 'list-item';
				}
			} );
			$('ul.limit_length li.css_link').css( 'display', 'none' );
		});
	}

	function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada){
		gravaDados('produto_referencia',referencia);
		gravaDados('produto_descricao',descricao);
	}

	function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo){
		gravaDados('peca_referencia_multi',referencia);
		gravaDados('peca_descricao_multi',descricao);
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function addItPeca() {
		var peca_referencia = jQuery.trim($('#peca_referencia_multi').val());

		if(peca_referencia.length > 0){

			if($("#id_"+peca_referencia).length > 0){
				alert("Peça já inserida!");
				$('#peca_referencia_multi').val('');
				$('#peca_descricao_multi').val('');
				$('#peca_referencia_multi').focus();
				return false;
			}

			$('#adicionar_peca').val('Aguarde');
			$('#adicionar_peca').attr('disabled', true);

			var acao = "BuscaPeca";
			$.post(
				'<?=$PHP_SELF?>',
				{peca:peca_referencia, acao:acao},
				function(resposta){
					$('#adicionar_peca').val('Adicionar');
					$('#adicionar_peca').attr('disabled', false);
					if(resposta == 0){
						alert('Peça Inválida!');
						$('#peca_referencia_multi').focus();
					}else{
						resposta = resposta.split("|");
						$('#peca_multi').append("<option id='id_"+resposta[0]+"' value='"+resposta[0]+"'>"+resposta[0]+ ' - ' + resposta[1] +"</option>");
						$('#peca_referencia_multi').val('');
						$('#peca_descricao_multi').val('');
						$('#peca_referencia_multi').focus();
					}
				}
			)
		}else{
			alert("Informar toda ou parte da informação para ser adicionada!");
			$('#peca_referencia_multi').focus();
		}
	}

	function delItPeca() {
		$('#peca_multi option:selected').remove();
		

		if($('.select').length ==0) {
			$('#peca_multi').addClass('select');
		}
	}

	$(function(){
		$('form').submit(function(){
			$('#peca_multi option').attr('selected','selected');
		})

		$('input:button[rel=acao]').click(
			function(){
				$('#btn_acao').val(this.value);
				$('form:first').submit();
			}	
		)
	});

	function manutencaoSerie(produto,peca,acao){
		if (confirm('Deseja realmente '+acao+' essa peça?') == true) {
			qtd = $("input[name=qtde_"+peca+"]").val();
			
			$('#'+peca).html('Carregando..');
			
			$.post(
				'<?=$PHP_SELF?>',
				{
					produto:produto,
					peca:peca,
					acao:acao,
					qtde:qtd					
				},
				function(resposta){
					resposta = resposta.split("|")
					if (resposta[0]=='ok'){
						//$('#'+peca).addClass('sucesso').html(resposta[1]);
						$('#'+peca).html(resposta[1]);
					}else{
						//$('#'+peca).addClass('msg_erro').html(resposta[1]);
						$('#'+peca).html(resposta[1]);
					}
					
				}
			)
		}
	}
</script>
<?php if(strlen($msg_erro) > 0){?>
	<div class="msg_erro" style="width:700px; margin:auto;"><?php echo $msg_erro;?></div>
<?php }?>
<div class="formulario" style="width:700px; margin:auto;" >
	<div class="titulo_tabela">Manutenção</div>
	
	<form action="<?=$PHP_SELF?>" method="POST" name="frm_num_serie">
		<input type="hidden" name="num_serie" value="<?=isset($num_serie)?$num_serie : ''?>" />
			<table width="700px">
				<tr>
					<td>
						&nbsp;
					</td>		
				</tr>
			</table>

			<table align="center" width="700px">
				<tr>
					<td width="100px">
						&nbsp;
					</td>
					<td width="300px">
						Referência Produto:<br><input type="text" name="produto_referencia" id="produto_referencia" value="<?php echo $produto_referencia;?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_num_serie.produto_referencia,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'>
						&nbsp;&nbsp;&nbsp;
					</td>

					<td width="300px">
						Descrição Produto:&nbsp;<br><input type="text" name="produto_descricao" id="produto_descricao" value="<?php echo $produto_descricao;?>" size="30" maxlength="50" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_num_serie.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>
					</td>
				</tr>
			</table>
			<table align="center" width="700px">
				<tr>
					<td width="100px">
						&nbsp;
					</td>
					<td width="300px">
						Referência Peça:&nbsp;<br><input class='frm' type="text" name="peca_referencia_multi"  id="peca_referencia_multi" value="<?php echo $peca_referencia;?>" size="15" maxlength="20">&nbsp;<IMG src='../imagens/lupa.png' onClick="javascript: pesquisaPeca (document.frm_num_serie.peca_referencia_multi,'referencia')"  style='cursor:pointer;'>
						&nbsp;&nbsp;&nbsp;
					</td>

					<td width="300px">
						Descrição Peça:&nbsp;<br><input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="<?php echo $peca_descricao_multi;?>" size="30" maxlength="50">&nbsp;<IMG src='../imagens/lupa.png' onClick="javascript: pesquisaPeca(document.frm_num_serie.peca_descricao_multi,'descricao')"  style='cursor:pointer;' align='absmiddle'>
					<br>
				</tr>
			</table>
			

			<table align="center" width="700px">
				<tr>
					<td width="300px">
						&nbsp;
					</td>
					<br>
					<td width="400px">
						<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onclick='addItPeca();'><br>
						<b style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</b>
					</td>
				</tr>
			</table>

			<table align="center" width="700px">
				<tr>
					<td width="50px">
						&nbsp;
					</td>
 
					<td width="650px">
						<SELECT MULTIPLE SIZE='7' style="width:80%" ID="peca_multi" NAME="peca_multi[]" class='frm'>
						<?
						foreach($peca_referencia_multi as $peca_referencia){
							$sql = "SELECT 
									peca,
									descricao
								FROM tbl_peca
								WHERE referencia='$peca_referencia'	";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0){
								$peca = pg_fetch_result($res,0,'peca');
								$peca_descricao = pg_fetch_result($res,0,'descricao');
								$variavel_select .= "<option id='id_".$peca_referencia."' value='".$peca_referencia."'>".$peca_referencia." - ".$peca_descricao."</option>";
							}		
						}
						echo $variavel_select;
						if (count($lista_pecas)>0){
							for ($i=0; $i<count($lista_pecas); $i++){
								$linha_pecas = $lista_pecas[$i];
								echo "<option value=''>".$resultado."</option>";
							}
						}
						?>
						</SELECT>
						<input TYPE="BUTTON" VALUE="Remover" ONCLICK="delItPeca();" class='frm'></input>
					</td>
				</tr>
			</table>

			<table align="center" width="700px">
				<tr>
					<td colspan="2" style="padding:5px 0;" align="center">
						<input type='hidden' name='btn_acao' id='btn_acao'>
						<input type="button" value="Pesquisar" id='pesquisar' rel='acao' /> &nbsp;
					</td>
				</tr>
			</table>
	</form>
</div>
<br />

<?php
	if(isset($resultado)) {
		echo '<table class="tabela" width="700" align="center" cellspacing="1">
				<thead>
					<tr class="titulo_coluna">
						<th>Produto</th>
						<th>Peça</th>';
						 if ($login_fabrica == 95 ) { 
							echo '<th>Qtde</th>';
						 }
		echo '		<th>Ação</th>
					</tr>
				</thead>
				<tbody>';
		echo $resultado;
		echo '</tbody></table>';
	}

?>
<?php include 'rodape.php'; ?>