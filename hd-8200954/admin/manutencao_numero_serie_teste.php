<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'funcoes.php';
	include "autentica_admin.php";

	$layout_menu = "cadastro";
	$title = "MANUTENÇÃO DE NÚMERO DE SÉRIE";
	include "cabecalho.php";

	$perm = array(90,85,72); //fabricas que podem usar o programa
	if( !in_array( $login_fabrica, $perm ) )
		die('Você não tem acesso à essa página.');
?>

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
</style>
<?php
	if ( isset( $_GET['serie']) && !empty($_GET['serie']) ) { //ao clicar em alterar

		$serie = $_GET['serie'];

		$sql = 'SELECT 
				serie, cnpj, referencia_produto, TO_CHAR (data_venda,\'DD/MM/YYYY\') as data_venda, TO_CHAR (data_fabricacao, \'DD/MM/YYYY\') as data_fabricacao, tbl_numero_serie.produto, numero_serie,
				tbl_produto.descricao as descricao_produto
				FROM tbl_numero_serie JOIN tbl_produto ON tbl_produto.referencia = referencia_produto
				WHERE fabrica = ' . $login_fabrica .'
				AND numero_serie = ' . $serie . '
				LIMIT 1';

		$query = pg_query($con,$sql);

		if (pg_numrows($query) == 0)
			$msg_erro = 'Nenhum Resultado Encontrado';

		else{

			$referencia			= trim(pg_result ($query,0,referencia_produto));
			$produto_descricao	= trim(pg_result ($query,0,descricao_produto));
			$serie				= trim(pg_result ($query,0,serie));
			$num_serie			= trim(pg_result ($query,0,numero_serie));
			$cnpj				= trim(pg_result ($query,0,cnpj));
			$data_fab			= trim(pg_result ($query,0,data_fabricacao));
			$data_venda			= trim(pg_result ($query,0,data_venda));

		}
	
	}

	function get_post_action($name) {
		$params = func_get_args();
		foreach ($params as $name) 
			if (isset($_POST[$name])) 
				return $name;
	}

	if( isset($_POST['gravar']) || isset($_POST['pesquisar']) ) { //requisicao post, pesquisar ou gravar

		$serie		= trim($_POST['serie']);
		$referencia = trim($_POST['produto_referencia']);
		$data_fab	= trim($_POST['data_fabricacao']);
		$data_venda	= trim($_POST['data_venda']);
		$cnpj		= preg_replace('/\D/', '', $_POST['cnpj']);

		$x_data_fab		= implode("/", array_reverse(explode("/", $data_fab)));
		$x_data_venda	= implode("/", array_reverse(explode("/", $data_venda)));
	
		unset($msg_erro);

		if (empty($data_venda))
			$x_data_venda = 'NULL';
		else
			$x_data_venda = "'".$x_data_venda."'";

		if( empty($referencia) && get_post_action('gravar') )
			$msg_erro = 'Escolha um Produto';

		if( empty($msg_erro) ) {

			$sql = 'SELECT produto, descricao
					FROM tbl_produto 
					JOIN tbl_linha USING (linha)
					WHERE referencia = \'' . $referencia . '\'
					AND fabrica = ' . $login_fabrica;
			$query = pg_query($con,$sql);

			//pesquisa por num. de serie nao precisa de produto
			if (pg_numrows($query) == 0 && get_post_action('gravar') ) // 
				$msg_erro = 'Produto não Encontrado';

			else {
				/* esconde erro por causa da pesquisa por num. de serie nao precisar de produto */
				$produto			= trim(@pg_result ($query,0,produto));
				$produto_descricao	= trim(@pg_result ($query,0,descricao));

				switch (get_post_action('gravar', 'pesquisar')) {

					case 'gravar':
						/* validações antes do update/insert */
						if ( empty( $serie ) ) {
							$msg_erro = 'Preencha o Número de Série';
							break;
						}
						if ( empty( $data_fab ) ) {
							$msg_erro = 'Preencha a Data de Fabricação';
							break;
						}
						else {
							list($di, $mi, $yi) = explode("/", $data_fab);
							if(!checkdate($mi,$di,$yi)) {
								$msg_erro = "Data Inválida";
								break;
							}
						}
						if(!empty($data_venda)) {
							list($di, $mi, $yi) = explode("/", $data_venda);
							if(!checkdate($mi,$di,$yi)) {
								$msg_erro = "Data Inválida";
								break;
							}
						}
						if (!empty($cnpj) && checa_cnpj($cnpj) == 1) {
							$msg_erro = 'CNPJ Inválido!';
							break;
						}
						/* verifica se ja existe num. de serie para esse produto */
						$sql = 'SELECT serie FROM tbl_numero_serie 
								WHERE produto = ' . $produto . '
								AND fabrica = ' . $login_fabrica . '
								AND serie = \'' . $serie . '\'';

						/* pega apenas se for diferente do que vai fazer update */
						if (!empty($_POST['num_serie']))
							$sql .= ' AND numero_serie <>' . $_POST['num_serie'];

						$query = pg_query ($con,$sql);

						if(pg_numrows($query) > 0 ){
							$msg_erro = 'Nº de Série Já Cadastrado';
							break;
						}
						/* fim validações */
						if(!empty($_POST['num_serie'])) { // faz update

							$num_serie = $_POST['num_serie'];

							$sql = 'UPDATE tbl_numero_serie
									SET serie = \''.$serie.'\', cnpj = \''.$cnpj.'\', 
									referencia_produto = \''.$referencia.'\', 
									data_venda = ' . $x_data_venda.',
									data_fabricacao = \''.$x_data_fab. '\'
									WHERE fabrica = '.$login_fabrica.'
									AND numero_serie = '.$num_serie;
							//echo nl2br ($sql);
							$upd = pg_exec($con,$sql);
							echo '<script>window.location=\'?serie='.$_POST['num_serie'].'&msg=Gravado Com Sucesso!\'</script>';
							
							break;
						
						}

						$sql = "INSERT INTO tbl_numero_serie 
								(fabrica, serie, cnpj,referencia_produto, data_venda, data_fabricacao, produto, admin) VALUES(".$login_fabrica.",'".$serie."','".$cnpj."','".$referencia."', ".$x_data_venda.",'".$x_data_fab."',".$produto.", ".$login_admin.")";

						//echo nl2br($sql);

						$ins = pg_exec($con,$sql);

						$msg = 'Gravado com Sucesso!';

						unset($num_serie, $data_fab, $data_venda, $serie, $cnpj);

						break;

					case 'pesquisar':
						if(!empty($produto))
							$cond = ' AND tbl_numero_serie.produto = ' . $produto ;
						if(!empty($serie))
							$cond .= ' AND serie LIKE \''.$serie.'\'';
						$sql	= 'SELECT 
								   referencia_produto, serie, numero_serie, cnpj, TO_CHAR (data_venda,\'DD/MM/YYYY\') as data_venda, TO_CHAR (data_fabricacao, \'DD/MM/YYYY\') as data_fabricacao, descricao
								   FROM tbl_numero_serie JOIN tbl_produto ON tbl_produto.referencia = referencia_produto
								   WHERE 
								   fabrica = ' . $login_fabrica. 
									$cond;

						$query	= pg_query($con, $sql);

						ob_start();

						if(pg_numrows($query) == 0) {
							echo 'Não Foram Encontrados Resultados para esta Pesquisa';
							$result = ob_get_contents();
							ob_end_clean();
							break;
						}

						echo '<table class="tabela" width="700" align="center" cellspacing="1">
								<thead>
									<tr class="titulo_coluna">
										<th>Cod. Produto</th>
										<th>Nº de Série</th>
										<th>Descrição</th>
										<th>CNPJ Cliente</th>
										<th>Fabricação</th>
										<th>Venda</th>
										<th>Ação</th>
									</tr>
								</thead>
								<tbody>';

						for($i=0; $i<pg_numrows($query);$i++) {
						
							$produto		= trim(pg_result ($query,$i,referencia_produto));
							$serie			= trim(pg_result ($query,$i,serie));
							$num_serie		= trim(pg_result ($query,$i,numero_serie));
							$cnpj			= trim(pg_result ($query,$i,cnpj));
							$data_fab		= trim(pg_result ($query,$i,data_fabricacao));
							$data_venda		= trim(pg_result ($query,$i,data_venda));
							$prod_descricao	= trim(pg_result ($query,0,descricao));

							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

							echo '<tr bgcolor="'.$cor.'">
									<td>'.$produto.'</td>
									<td>'.$serie.'</td>
									<td>'.$prod_descricao.'</td>
									<td>'.$cnpj.'&nbsp;</td>
									<td>'.$data_fab.'</td>
									<td>'.$data_venda.'&nbsp;</td>
									<td>
										<button onclick="window.location=\'?serie='.$num_serie.'\'">Alterar </button>
									</td>
								  </tr>';

						}

						echo '	</tbody>
							</table>' . PHP_EOL;

						/* para nao ficar valor nos campos quando pesquisar novamente */
						unset($data_fab, $data_venda, $cnpj, $num_serie); 

						$serie = $_POST['serie']; //armazena valor do post, para consulta por num. de serie

						$result = ob_get_contents();
						ob_end_clean();

						break;

				}
			}
		}
		if (isset($msg_erro))
			echo '<div class="msg_erro" id="erro" style="display:none;">'.$msg_erro.'</div>';
	}
	if (isset($msg) || isset($_GET['msg']) ) {
		$msg = isset($msg) ? $msg : $_GET['msg'];
		echo '<div id="sucesso" class="sucesso" style="display:none;">'. $msg .'</div>';
	}
?>

<?php include "javascript_calendario.php";?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script type="text/javascript">
	function fnc_pesquisa_produto (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}


		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia	= campo;
			janela.descricao	= campo2;
			janela.focus();
		}

		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa");
		}
	}
	$().ready(function(){
		//$( "#data_fabricacao" ).datePicker();
		$( "#data_fabricacao" ).maskedinput("99/99/9999");
		//$( "#data_venda" ).datePicker({startDate : "01/01/2000"});
		$( "#data_venda" ).maskedinput("99/99/9999");
		$("#cnpj").maskedinput("99.999.999/9999-99");
		<?php if (isset($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
		<?php } ?>
		<?php 
			if (isset($msg) || isset($_GET['msg']) ){
		?>
			$("#sucesso").appendTo("#msg").fadeIn("slow");
		<?php } ?>
	});
</script>

<div id="msg" style="width:700px; margin:auto;"></div>
<div class="formulario" style="width:700px; margin:auto;" >
	<div class="titulo_tabela">Manutenção</div>
	<table align="center" style="padding:10px 0 10px;">
	<form action="<?=$PHP_SELF?>" method="POST" name="frm_num_serie">
		<input type="hidden" name="num_serie" value="<?=isset($num_serie)?$num_serie : ''?>" />
		
			<tr>
				<td width="230px">
					<label for="produto_referencia">Referência *</label><br />
					<input type="text" name="produto_referencia" id="produto_referencia" class="frm" value="<?=isset($referencia)?$referencia:''?>" />
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_num_serie.produto_referencia, document.frm_num_serie.produto_descricao,'referencia')">
				</td>
				<td>
					<label for="produto_descricao">Descrição Produto</label><br />
					<input type="text" name="produto_descricao" id="produto_descricao"  class="frm" value="<?=isset($produto_descricao)?$produto_descricao:''?>" />
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_num_serie.produto_referencia, document.frm_num_serie.produto_descricao,'descricao')">
				</td>
			</tr>
			<tr>
				<td>
					<label for="data_fabricacao">Data de Fabricação *</label><br />
					<input type="text" name="data_fabricacao" id="data_fabricacao"  class="frm" value="<?=isset($data_fab) ? $data_fab :''?>" />
				</td>
				<td>
					<label for="data_venda">Data da Venda</label><br />
					<input type="text" name="data_venda" id="data_venda"  class="frm" value="<?=isset($data_venda)?$data_venda:''?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="serie">Nº de Série *</label><br />
					<input type="text" name="serie" id="serie" class="frm" value="<?=isset($serie)?$serie:''?>" maxlength="20" />
				</td>
				<td>
					<label for="cnpj">CNPJ do Cliente</label><br />
					<input type="text" name="cnpj" id="cnpj" class="frm" value="<?=isset($cnpj)?$cnpj:''?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" style="padding:5px 0;" align="center">
					<input type="submit" name="gravar" value="Gravar" />
					<input type="submit" name="pesquisar" value="Pesquisar" /> &nbsp;
				</td>
			</tr>
		</table>
	</form>
</div>
<br />
<?php echo isset($result)?$result : ''; ?>

<?php include 'rodape.php'; ?>