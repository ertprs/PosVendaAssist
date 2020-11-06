<!DOCTYPE html>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");



if(isset($_POST['btn_gravar'])) {
	
	$sql = "SELECT * from tbl_fale_conosco ORDER BY ordem desc limit 1";
	$res = pg_query($con,$sql);
	
	$resx = pg_query($con,"BEGIN TRANSACTION");
	
	$ordem = pg_fetch_result($res,0,ordem);
	
	if(strlen(trim($_POST['texto_novo'])) > 0) 
	{
		$ordem_novo = $ordem+1;
		$texto_novo = $_POST['texto_novo'];
		$sqlx = "INSERT INTO tbl_fale_conosco(ordem,descricao)values($ordem_novo,'$texto_novo');";
		$resx = pg_query($con,$sqlx);
		
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		$ok = "ok";

	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}

}

if(isset($_POST['btn_editar'])) {
	$fale_conosco = $_POST['fale_conosco'];
	$resx = pg_query($con,"BEGIN TRANSACTION");
	$descricao = $_POST["texto_novo"];
	$descricao = str_replace('"', "'", $descricao);
	$descricao = pg_escape_string($descricao);
	$sqlx = "UPDATE tbl_fale_conosco SET descricao ='$descricao'
				WHERE fale_conosco=$fale_conosco";
	$resx = pg_query($con,$sqlx);
	$msg_erro = pg_last_error($con);
	
	
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		$ok = "ok";
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

?>

<html lang="en">
<head>
	<!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>-->
	 <script type="text/javascript" src="js/jquery.js"></script>
	 <script type="text/javascript" src="js/fckeditor/fckeditor.js"></script>
	 
	<?
		if ($ok == "ok"){
			?>
			<script type="text/javascript">parent.window.location.href="fale_conosco_cadastro.php"</script>
			<?
		}
	?>
	<script type="text/javascript">
		$(function(){
			var oFCKeditor = new FCKeditor('texto_novo') ;
				oFCKeditor.BasePath = "js/fckeditor/" ;
				oFCKeditor.ToolbarSet = 'Basic' ;
				oFCKeditor.ReplaceTextarea() ;
				
		});
		
		/*HD-3951025 O programa informava um erro que não existia*/
		alert = function(){};
	
	</script>
	
	<style type="text/css">
		body{
			margin:0;
			padding:0;
		}
		
		.corpo{
			width:90%;
			height: 336px;
			background: #fff;
		}
		
		.titulo_tabela{
			background-color:#596d9b;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		textarea{
			background-color:GhostWhite;
			font: 12px Arial;
			border: solid 1px #000;
		}
	</style>
</head>

<body>
<form action="<? echo $PHP_SELF ?>" method="post" name="frm_fale_conosco">
<?
	$acao_editar = $_GET['editar'];
	$acao_novo = $_GET['novo'];
	if (strlen($acao_editar)>0)
	{
	$fale_conosco = $_GET['id'];
	$ordem = $_GET['ordem'];
	$sql = "Select descricao from tbl_fale_conosco where  ordem=$ordem and fale_conosco=$fale_conosco";
	$res = pg_query($con,$sql);
	$descricao = pg_fetch_result($res,0,descricao);
?>
	<table class="corpo" align="center" valign="top">
		<tr class="titulo_tabela">
			<th>Editar Cadastro do Fale Conosco</th>
		</tr>
		<tr>
			<td valign="top" align="center">
				<textarea name="texto_novo" id="texto_novo" style="width:100%;height:500px"><?=$descricao?></textarea>
			</td>
		</tr>
		<tr>
			<td align="center">
				<input type="hidden" value="<?=$fale_conosco?>" name="fale_conosco" id="fale_conosco" />
				<input type="submit" value="Gravar" name="btn_editar" id="btn_editar" />
			</td>
		</tr>
	</table>

<?
	}

	if (strlen($acao_novo)>0)
	{
?>

	<table class="corpo" align="center" valign="top">
		<tr class="titulo_tabela">
			<th>Cadastrar novo Registro do Fale Conosco</th>
		</tr>
		<tr>
			<td valign="top" align="center">
				<textarea name="texto_novo" id="texto_novo" style="width:100%;height:500px"></textarea>
			</td>
		</tr>
		
		<tr>
			<td align="center">
				<input type="submit" value="Gravar" name="btn_gravar" id="btn_gravar" />
			</td>
		</tr>
		
	</table>
<?
	}
?>	
</form>


</body>

</html>