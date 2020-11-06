<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

#pesquisa pelo AJAX

$q = trim($_GET["q"]);

if(isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if(strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";
			
			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if (strlen($_GET["produto"]) > 0) $produto = trim($_GET["produto"]);
if (strlen($_GET["subproduto"]) > 0) $subproduto = trim($_GET["subproduto"]);


if (strlen($_POST["btn_acao"]) > 0) {
	$btnacao = trim($_POST["btn_acao"]);
}



if ($btnacao == "deletar") {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
 
 	$sql = "DELETE FROM tbl_subproduto
				 	WHERE  tbl_subproduto.subproduto = $subproduto
					";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=Excluido com sucesso!");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$referencia_pai   = $_POST["referencia_pai"];
		$produto_filho    = $_POST["produto_filho"];
		$descricao_filho  = $_POST["descricao_pai"];
		$referencia_filho = $_POST["referencia_filho"];

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if($btnacao == "gravar") {
	$msg_erro = array();

	$referencia_pai 	= $_POST["referencia_pai"];
	$descricao_pai 		= $_POST["descricao_pai"];
	$referencia_filho = $_POST["referencia_filho"];
	$descricao_filho 	= $_POST["descricao_filho"];

	if(strlen($referencia_pai) > 0 or strlen($descricao_pai) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$referencia_pai}'))
                    OR
                    (UPPER(descricao) = UPPER('{$descricao_pai}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto_pai";
		} else {
			$produto_pai = pg_fetch_result($res, 0, "produto");
		}
	}

	if (!strlen($referencia_pai) or !strlen($descricao_pai)) {
		$msg_erro["msg"][]    = "Digite o produto pai";
		$msg_erro["campos"][] = "produto_pai";
	}

	if(strlen($referencia_filho) > 0 or strlen($descricao_filho) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				WHERE fabrica_i = {$login_fabrica}
				AND (
                  	(UPPER(referencia) = UPPER('{$referencia_filho}'))
                    OR
                    (UPPER(descricao) = UPPER('{$descricao_filho}'))
                )";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Produto não encontrado";
			$msg_erro["campos"][] = "produto_filho";

		} else {
			$produto_filho = pg_fetch_result($res, 0, "produto");
		}
	}
	
	if (!strlen($referencia_filho) or !strlen($descricao_filho)) {
		$msg_erro["msg"][]    = "Digite o produto filho";
		$msg_erro["campos"][] = "produto_filho";

	}
		

	if(strlen($msg_erro) == 0){
		if($referencia_pai == $referencia_filho){
			$msg_erro["msg"][] = "Um Produto não Pode ser Subproduto Dele Próprio.";
		}
	}
	

	if(count($msg_erro["msg"]) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		if(strlen($subproduto) == 0){
			###INSERE NOVO REGISTRO
			$sql =	"INSERT INTO tbl_subproduto (
						produto_pai,
						produto_filho
					) VALUES (
						$produto_pai,
						$produto_filho
					);";
		}else{
			###ALTERA REGISTRO
			// echo $produto_pai.'<br />';
			// echo $produto_filho.'<br />'; 
			// echo $subproduto;exit;

			$sql = "UPDATE tbl_subproduto SET
							produto_pai   = $produto_pai,
							produto_filho = $produto_filho
							FROM tbl_produto, tbl_linha
							WHERE   tbl_subproduto.produto_pai = tbl_produto.produto
							AND     tbl_produto.linha          = tbl_linha.linha
							AND     tbl_linha.fabrica          = $login_fabrica
							AND     tbl_subproduto.subproduto  = $subproduto;";
		}
		#echo $sql;exit;
		$res = @pg_exec($con,$sql);

		#$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro["msg"])>0){
			$msg_erro["msg"] = "Este produto já está cadastrado!";
		}
		if (strlen($msg_erro["msg"]) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT TRANSACTION");

			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

			$referencia_pai      = $_POST["referencia_pai"];
			$descricao_pai    	 = $_POST["descricao_pai"];
			$descricao_filho  	 = $_POST["descricao_filho"];
			$referencia_filho 	 = $_POST["referencia_filho"];

			if (strpos ($msg_erro["msg"],"duplicate key violates unique constraint \"tbl_subproduto_unico\"") > 0)
				$msg_erro["msg"] = "Subconjunto já cadastrado para este produto.";

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}// fim if msg_erro[] 
	//print_r($msg_erro);exit;
}

###CARREGA REGISTRO
if(strlen($subproduto) > 0) {
	$sql = "SELECT  tbl_subproduto.produto_pai  ,
					tbl_subproduto.produto_filho
			FROM    tbl_subproduto
			JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
			JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			WHERE   tbl_linha.fabrica         = $login_fabrica
			AND     tbl_subproduto.subproduto = $subproduto;";
	$res = pg_exec ($con,$sql);
	
	if(pg_numrows($res) > 0) {
		$produto_pai   = trim(pg_result($res,0,produto_pai));
		$produto_filho = trim(pg_result($res,0,produto_filho));
		
		$sql = "SELECT  tbl_produto.referencia AS referencia_pai,
						tbl_produto.descricao  AS descricao_pai
				FROM    tbl_subproduto
				JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
				JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
				WHERE   tbl_linha.fabrica          = $login_fabrica
				AND     tbl_subproduto.produto_pai = $produto_pai;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$referencia_pai = trim(pg_result($res,0,referencia_pai));
			$descricao_pai  = trim(pg_result($res,0,descricao_pai));
		}
		
		$sql = "SELECT  tbl_produto.referencia AS referencia_filho,
						tbl_produto.descricao  AS descricao_filho
				FROM    tbl_subproduto
				JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
				JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
				WHERE   tbl_linha.fabrica            = $login_fabrica
				AND     tbl_subproduto.produto_filho = $produto_filho;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$referencia_filho = trim(pg_result($res,0,referencia_filho));
			$descricao_filho  = trim(pg_result($res,0,descricao_filho));
		}
	}
}

$layout_menu = 'cadastro';
$title = "CADASTRAMENTO DE SUBCONJUNTO";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>


<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	Shadowbox.init();
	$(function() {
		
		$("span[rel=lupa]").click(function () {
			$.lupa($(this), ['pai','filho']);
		});
	});

	function retorna_produto (retorno) {
		
		if(retorno.pai != undefined && retorno.pai == true){
			$("#referencia_pai").val(retorno.referencia);
			$("#descricao_pai").val(retorno.descricao);
		}

		if(retorno.filho != undefined && retorno.filho == true){
			$("#referencia_filho").val(retorno.referencia);
			$("#descricao_filho").val(retorno.descricao);
		}
			
	}



</script>


<?php
if (strlen($msg) > 0) {
?>
    <div class="alert alert-success">
			<h4><? echo $msg; ?></h4>
    </div>
<?php
}
?>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">* Campos obrigatórios </b>
</div>

<form name="frm_subproduto" method="post" action="<? $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	<input type="hidden" name="subproduto"	value="<? echo $subproduto ?>">
	<input type="hidden" id="produto_pai"	name="produto_pai"	 value="<? echo $produto_pai ?>">
	<input type="hidden" id="produto_filho"	name="produto_filho" value="<? echo $produto_filho ?>">

	<div class='titulo_tabela '>Cadastro de Subconjunto</div>
	<br/>

	<!-- produto pai -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto_pai", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='referencia_pai'>Ref. Produto Pai</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="referencia_pai" name="referencia_pai" class='span12' maxlength="20" value="<? echo $referencia_pai ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" pai="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto_pai", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_pai'>Descrição Produto Pai</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="descricao_pai" name="descricao_pai" class='span12' value="<? echo $descricao_pai ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" pai="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- produto filho -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto_filho", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='referencia_filho'>Ref. Produto Filho</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="referencia_filho" name="referencia_filho" class='span12' maxlength="20" value="<? echo $referencia_filho ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" filho="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto_filho", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_filho'>Descrição Produto Filho</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="descricao_filho" name="descricao_filho" class='span12' value="<? echo $descricao_filho ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" filho="true" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- botoes -->
	<p><br/>
		<button class='btn btn-success' type="button"  onclick="submitForm($(this).parents('form'),'gravar');">Gravar</button>
		<?php
			if(strlen($subproduto) > 0){ ?>
			<button type="button" class="btn btn-danger"  onclick="submitForm($(this).parents('form'),'deletar');" alt="Apagar subproduto" >Excluir</button>
		<?php
			}
		?>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
		<input type="button" class="btn" value="Limpar" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" style="cursor:pointer;">
	</p><br />
	<!-- #### -->
</form>

<?php
	if(strlen($produto) == 0){
		$sql = "SELECT  DISTINCT
					tbl_subproduto.produto_pai,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_subproduto
			JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
			JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			ORDER BY tbl_produto.descricao;";
		$res = pg_exec($con,$sql);

		if(pg_num_rows($res) > 0){ ?>
			<div class="row-fluid">
				<div class="span12">
					<p class="text-info tac">Clique no Produto para listar os Sub-Produtos</p>
				</div>
			</div>
			<table class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class="titulo_tabela">
						<th colspan='2'>Relação dos Produtos</th>
					</tr>
					<tr class='titulo_coluna'>
						<th>Referência</th>
						<th>Descrição</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
							$produto    = trim(pg_result($res,$i,produto_pai));
							$referencia = trim(pg_result($res,$i,referencia));
							$descricao  = trim(pg_result($res,$i,descricao));

							echo "<tr>";
								echo "<td><a href='$PHP_SELF?produto=$produto'>$referencia</a></td>";
								echo "<td><a href='$PHP_SELF?produto=$produto'>$descricao</a></td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>

<?php
		}
	}else{
		$sql =	"SELECT x.subproduto                                    ,
										x.referencia           AS subproduto_referencia ,
										x.descricao            AS subproduto_descricao  ,
										x.produto_pai          AS produto               ,
										x.ativo                                         ,
										tbl_produto.referencia AS produto_referencia    ,
										tbl_produto.descricao  AS produto_descricao     
						FROM (
							SELECT tbl_subproduto.subproduto  ,
									tbl_subproduto.produto_pai ,
									tbl_produto.referencia     ,
									tbl_produto.descricao      ,
									tbl_produto.ativo          
							FROM    tbl_subproduto
							JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
							JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
							WHERE   tbl_linha.fabrica          = $login_fabrica
							AND     tbl_subproduto.produto_pai = $produto
							ORDER BY tbl_produto.descricao
						) AS x
						JOIN tbl_produto ON tbl_produto.produto = x.produto_pai;";
		$res = pg_exec($con,$sql);

		if(pg_num_rows($res) > 0){ ?>
			<div class="row-fluid">	
				<div class="span12">
					<h4 class="tac">Relação dos Sub-Produtos</h4>
					<p class="text-info tac">Clique no Sub-Produto para efetuar alterações</p>
				</div>
			</div>
			<table class='table table-striped table-bordered table-hover table-fixed'>
				<thead>
					<tr class='titulo_tabela'>
						<th colspan='3'> Produto: <i><?php echo trim(pg_result($res,0,produto_referencia))?> - <?php echo trim(pg_result($res,0,produto_descricao))?></i></th>
					</tr>
					<tr class='titulo_coluna'>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
							$subproduto    = trim(pg_result($res,$i,subproduto));
							$referencia = trim(pg_result($res,$i,subproduto_referencia));
							$descricao  = trim(pg_result($res,$i,subproduto_descricao));
							$ativo      = trim(pg_result($res,$i,ativo));

							if($ativo=='t') $ativo = "<img src='imagens/status_verde.png'>";
							else            $ativo = "<img src='imagens/status_vermelho.png'>";

							echo "<tr>";
							echo "<td><a href='$PHP_SELF?produto=$produto&subproduto=$subproduto'>$referencia</a></td>";
							echo "<td><a href='$PHP_SELF?produto=$produto&subproduto=$subproduto'>$descricao</a></td>";
							echo "<td class='tac'>$ativo</td>";
							echo "</tr>";
						}
					?>
				</tbody>	
			</table>
			<br />
			<div class="row-fluid">
				<div class="span12">
					<p class="tac">
						<button class="btn" ONCLICK="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" style="cursor:pointer;">Voltar</button>
					</p>
				</div>
			</div>
			
<?php
		}
	}

?>
<?php include 'rodape.php';?>