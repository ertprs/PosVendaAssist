<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
//include 'funcoes.php';


if (strlen($_POST["btn_acao"]) > 0)  $btn_acao  = trim($_POST["btn_acao"]);

$deletar = $_GET['excluir'];

if (strlen ($deletar) > 0) {
	
	$sql = "DELETE FROM tbl_produto_fornecedor
			WHERE  produto_fornecedor = $deletar
			AND    fabrica             = $login_fabrica;";

	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) 
		$msg_erro = "Este forncedor não pode ser excluído porque existem produtos relacionados a ele";
	else{
		header("Location: $PHP_SELF?msg=Excluido com Sucesso!");
		exit;
	}
}


if ($btn_acao == "gravar") {
	$produto_fornecedor  = trim($_POST["produto_fornecedor"]);
	$codigo              = trim($_POST["codigo"]);
	$nome                = trim($_POST["nome"]);

	if(strlen($codigo)==0)   $msg_erro .= "Digite o código do fornecedor<br>";
	if(strlen($nome)==0)     $msg_erro .= "Digite o nome do fornecedor<br>";

	if(strlen($produto_fornecedor)==0){
		$sql = "SELECT produto_fornecedor
				FROM   tbl_produto_fornecedor
				WHERE  fabrica = $login_fabrica
				AND    codigo  = '$codigo'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0) $msg_erro .= "Já existe um fornecedor com o código $codigo<br>";
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($produto_fornecedor) == 0) {
			$sql = "INSERT INTO tbl_produto_fornecedor (
						fabrica ,
						codigo  ,
						nome
					) VALUES (
						$login_fabrica,
						'$codigo'     ,
						'$nome'
					);";
		}else{
			$sql = "UPDATE tbl_produto_fornecedor SET
						codigo = '$codigo',
						nome   = '$nome'
					WHERE  fabrica            = $login_fabrica
					AND    produto_fornecedor = $produto_fornecedor;";
		}
		
		$res = @pg_exec ($con,$sql);
		$msg_erro = @pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			if (strlen($produto_fornecedor) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_produto_fornecedor')");
				$produto_fornecedor = pg_result ($res,0,0);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$produto_fornecedor = $_GET["produto_fornecedor"];
if (strlen($produto_fornecedor) > 0) {
	$sql = "SELECT  produto_fornecedor,
					codigo             ,
					nome
			FROM    tbl_produto_fornecedor
			WHERE   produto_fornecedor = $produto_fornecedor
			AND     fabrica             = $login_fabrica;";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$produto_fornecedor = trim(pg_result($res,0,produto_fornecedor));
		$codigo             = trim(pg_result($res,0,codigo));
		$nome               = trim(pg_result($res,0,nome));
	}
}

$msg = $_GET['msg'];
$title = "CADASTRO DE FORNECEDORES DE PRODUTO";
$layout_menu = "cadastro";
//include 'cabecalho.php';
include 'cabecalho_new.php';

?>


<? 
	if($msg_erro){
?>

<div class='container'>
    <div class="alert alert-error">
        <h4><? echo $msg_erro; ?></h4>
    </div>  
</div>


<?}
	
?> 

<? 
	if($msg){
?>
<div class='container'>
    <div class="alert alert-success">
        <h4><? echo $msg; ?></h4>
    </div>  
</div>

<?}
	
?> 




<form name="frm_situacao" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">
<input type="hidden" name="produto_fornecedor" value="<? echo $produto_fornecedor ?>">


<div class="titulo_tabela">Cadastro</div>
<br>

<div class="row-fluid">
	<!-- Margem -->
	<div class="span2"></div>
	<div class="span4">
		<div class="control-group">
			<label class="control-label" for="">Código do Fornecedor </label>
			<div class="controls controls-row"> 			      
			      <input type='text' name='codigo' id='codigo' value='<?=$codigo?>' class='span12'>
		    </div>
		</div>
	</div>

	<div class="span4">
		<div class="control-group ">
			<label class="control-label" for="">Nome do Fornecedor</label>
			<div class="controls controls-row">				
				<input type='text' name='nome' id='nome' value='<?=$nome?>'  class='span12'>
			</div>
		</div>
	</div>	
	<!-- Margem -->
	<div class="span2"></div>
</div>
<br>
<div class="row-fluid">
	<div class="span4">
	</div>
	<div class="span4 tac">
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" class="btn center" value="Gravar" style="cursor: pointer;" onclick="javascript: if (document.frm_situacao.btn_acao.value == '' ) { document.frm_situacao.btn_acao.value='gravar' ; document.frm_situacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
	</div>
	<div class="span4">
	</div>
</div>

<!-- <table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario">
<tr>
	<td valign="top" align="left">
		<table align='center' width='700' border='0' class='formulario'>			
			
			<tr align='left'>
				<th colspan='4'>
				<?
				/*
					$sql = "SELECT  admin,
									login,
									nome_completo
							FROM tbl_admin
							WHERE fabrica = $login_fabrica
							AND   ativo   = TRUE
							ORDER BY login";
					$res = pg_exec($sql);
					if(pg_numrows($res)>0){
						echo "<table >";
						echo "<tbody>";
						for($i=0;$i<pg_numrows($res);$i++){
							$admin         = pg_result($res,$i,admin);
							$login         = pg_result($res,$i,login);
							$nome_completo = pg_result($res,$i,nome_completo);
							if(strlen($nome_completo)==0)$nome_completo = $login;
							$x = $i+1;
							if($x%2==0) echo "<tr>";
							echo "<td><input type='checkbox' name='admin[]' value='$admin'></td>";
							echo "<td>$nome_completo</td>";
							if($x%2==0) echo "</tr>";
						}
						echo "</tbody>";
						echo "</table>";
					}
				*/
				?>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td align="center" style='padding:10px 0 10px 0;'>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" value="Gravar" style="cursor: pointer;" onclick="javascript: if (document.frm_situacao.btn_acao.value == '' ) { document.frm_situacao.btn_acao.value='gravar' ; document.frm_situacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
	</td>
</tr>
</table> -->

<br>


</form>

<? 
$sql = "SELECT * 
		FROM tbl_produto_fornecedor
		WHERE fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	echo "<table class='table table-striped table-bordered table-hover' >";
	echo "<thead>";
	echo "<tr class='titulo_coluna'>";
	echo "<th>Código</th>";
	echo "<th style='width: 380px;'>Nome</th>";
	echo "<th>Ação</th>";
	echo "</tr>";

	echo "</thead>";
	echo "<tbody>";
	for($i=0;$i<pg_numrows($res);$i++){
		$produto_fornecedor  = pg_result($res,$i,produto_fornecedor);
		$codigo              = pg_result($res,$i,codigo);
		$nome                = pg_result($res,$i,nome);

		if($cor <>'#F7F5F0') $cor = '#F7F5F0';
		else                 $cor = '#F1F4FA';

		echo "<tr bgcolor='$cor'>";
		echo "<td>$codigo</td>";
		echo "<td>$nome</td>";
		echo "<td>
		<input type='button' class='btn' value='Cadastrar Admin' onclick=\"window.location='produto_fornecedor_cadastro_admin.php?produto_fornecedor=$produto_fornecedor&cadastrar=admin'\" style='margin-left: 5px'>
		<input type='button' class='btn' value='Alterar' onclick=\"window.location='$PHP_SELF?produto_fornecedor=$produto_fornecedor'\">
		<input type='button' class='btn btn-danger' value='Deletar' onclick=\"window.location='$PHP_SELF?excluir=$produto_fornecedor'\">
		</td>";
		echo "</tr>";
	}
	echo "</tbody>";
}
include "rodape.php";
?>