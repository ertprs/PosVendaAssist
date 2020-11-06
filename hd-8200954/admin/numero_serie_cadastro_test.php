<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';

$admin_privilegios = "cadastro";

# Pesquisa pelo AutoComplete AJAX

$q = strtolower($_GET["q"]);

if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	if (strlen($q)>3){
		$sql = "SELECT  tbl_produto.produto,
						tbl_produto.referencia,
						tbl_produto.descricao
					FROM tbl_produto
						JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";
		if ($busca == "codigo"){
			$sql .= " AND tbl_produto.referencia ilike '%$q%' ";
		}else{
			$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
		}
		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$produto            = trim(pg_fetch_result($res,$i,produto));
				$referencia         = trim(pg_fetch_result($res,$i,referencia));
				$descricao          = trim(pg_fetch_result($res,$i,descricao));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	}
	exit;
}

if(isset($_POST['btn_acao'])) {
	$referencia = $_POST['produto_referencia'];
	$serie      = trim($_POST['serie']);
	if(strlen($serie) == 0) {
		$msg_erro = "Por favor, informe a regra de número de série";			
	}
	if(strlen($referencia) == 0 ) {
		$msg_erro = "Por favor, informe o produto para cadastrar";	
	}
	if(strlen($referencia) > 0){
		$sql = "SELECT produto
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE fabrica    = $login_fabrica
				AND   referencia = '$referencia'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$produto = pg_fetch_result($res,0,produto);
		}else{
			$msg_erro = "Produto não encontrado";
		}
	}else{
		$produto = 'null';
	}
	if(strlen($msg_erro) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");
		$sql = "INSERT INTO tbl_numero_serie (
					fabrica,
					serie  ,
					produto
				) values (
					$login_fabrica,
					'$serie'      ,
					$produto      
				)";
		$res      = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if(strlen($msg_erro)==0){
			$res = pg_query($con,"COMMIT TRANSACTION");
			header("Location: $PHP_SELF");
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
}

$layout_menu = "cadastro";
$title       = "Cadastro de Número de Série";

include "cabecalho.php";?>

<style>
	.Titulo {
		text-align                         : center;
		font-family                        : Arial;
		font-size                          : 10px;
		font-weight                        : bold;
		color                              : #FFFFFF;
		background-color                   : #485989;
		background-image                   : url(imagens_admin/azul.gif);
	}
	.Conteudo {
		font-family                        : Arial;
		font-size                          : 12px;
		font-weight                        : normal;
	}
	.botao {
		background                         : #FFFFFF ;
		display                            : inline-block;
		padding                            : 4px 9px 6px;
		color                              : #000000 important;
		-moz-border-radius                 : 2px 8px / 2px 10px;
		-webkit-border-top-right-radius    : 8px;
		border-bottom-left-radius          : 8px / 10px;
		-opera-border-bottom-left-radius   : 10px;
		-webkit-border-bottom-left-radius  : 8px;
		-moz-box-shadow                    : 0 1px 3px rgba(0,0,0,0.5);
		-webkit-box-shadow                 : 0 1px 3px rgba(0,0,0,0.5);
		text-shadow                        : 0 -1px 1px rgba(0,0,0,0.25);
		-moz-text-shadow                   : 0 -1px 1px rgba(0,0,0,0.25);
		border-bottom                      : 1px solid rgba(0,0,0,0.25);
		cursor                             : pointer;
		border                             : 0;
	}
	.legenda{
		text-align                         : left;
	}

</style>

<? include "javascript_pesquisas.php" ?>
<? include "javascript_calendario_new.php";?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
	$().ready(function() {
		function formatItem(row) {
			return row[2] + " - " + row[1];
		}
		$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});
		$("#produto_descricao").result(function(event, data, formatted) {
			$("#produto_referencia").val(data[2]) ;
			$("#produto").val(data[0]) ;
		});
		/* Busca pelo Nome */
		$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});
		$("#produto_referencia").result(function(event, data, formatted) {
			$("#produto_descricao").val(data[1]) ;
			$("#produto").val(data[0]) ;
		});
	});
</script>

<br />
<?if(strlen($msg_erro) > 0) { 
	echo "<div style='background-color:red;'>";
		echo "$msg_erro";
	echo "</div>";
}?>

<center>
	<div style="text-align:center">
		Regra para cadastrar Número de Série
		<ol type="1" start="1" style="text-align:center">
			<li>Letras Maiusculas - Aceita apenas letras cadastradas</li>
			<li>Números Maiusculos - Aceita apenas números cadastrados</li>
			<li>Letra l(Minúscula) - Aceita qualquer letra</li>
			<li>Letra n(Minúscula) - Aceita qualquer número</li>
		</ol>
	</div>
</center>

<br />

<form name="frm_cadastro" method="POST" action="<? echo $PHP_SELF ?>">
	<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center' >
		<tr>
			<td class='Titulo' >
				<?=$title?>
			</td>
		</tr>
		<tr>
			<td bgcolor='#DBE5F5' valign='bottom'>
				<table width='100%' border='0' cellspacing='1' cellpadding='2' >
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td nowrap="" >
							<label for='produto_referencia'>
								Referência
							</label>
							<br/>
							<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
							<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_cadastro.produto_referencia, document.frm_cadastro.produto_descricao,'referencia')" />
						</td>
						<td nowrap="" >
							<label for='produto_descricao'>
								Descrição do Produto
							</label>
							<br />
							<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" />
							<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_cadastro.produto_referencia, document.frm_cadastro.produto_descricao,'descricao')" />
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td colspan="100%">
						</td>
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td colspan="2">
								<label for="serie">
									Número de Série
								</label>
								<input id="serie" class="frm" type="text" name="serie" value="<?=$serie?>" size="20" /> 
									<br/>
									Ex:OU9nnnnnnnll
							</td>
						</tr>
					</tr>
				</table>
				<br />
				<input type='submit' name='btn_acao' class="botao" value='Gravar' />
			</td>
		</tr>
	</table>
</form>

<? include "rodape.php" ?>
