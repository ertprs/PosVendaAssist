<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["ajax"]) > 0)  $ajax  = trim($_GET["ajax"]);
if(strlen($ajax)>0){
	$imagem         = $_GET['imagem'];

	echo"<center>
		<img src='../logos/$imagem' border='0'>
		</center>";
	exit;
}


if (strlen($_GET["marca"]) > 0) {
	$marca = trim($_GET["marca"]);
}

if (strlen($_POST["marca"]) > 0) {
	$marca = trim($_POST["marca"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
	list($width, $height) = getimagesize($img);
	$original_x = $width;
	$original_y = $height;

	if($original_x > $original_y) {
	   $porcentagem = (100 * $max_x) / $original_x;
	}
	else {
	   $porcentagem = (100 * $max_y) / $original_y;
	}

	$tamanho_x = $original_x * ($porcentagem / 100);
	$tamanho_y = $original_y * ($porcentagem / 100);

	$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
	$tmp = explode(".",$nome_foto);
	$ext = $tmp[count($tmp)-1];

	switch ($ext) {
		case 'gif':
			$image   = imagecreatefromgif($img);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);			
		break;
		case 'png':
			$image   = imagecreatefrompng($img);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);			
		break;
		case 'jpeg':
		case 'jpg':
			$image   = imagecreatefromjpeg($img);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);			
		break;
	}
	imagejpeg($image_p, $nome_foto, 65);
}

if ($btnacao == "gravar") {
	if (strlen($msg_erro) == 0) {
		if (strlen($_POST["nome"]) > 0) {
			$aux_nome = "'". trim(retira_acentos($_POST["nome"])) ."'";
		}else{
			$msg_erro = "Favor informar o nome da marca.";
		}
	}

	$codigo_marca = trim($_POST['codigo_marca']);

	if(strlen($ativo)==0)                $aux_ativo      = "'f'";
	else                                 	 $aux_ativo      = "'t'";
	if(strlen($indice)==0)                $aux_indice     = "'f'";
	else                                 	 $aux_indice      = "'t'";

	$ativo = ($_POST['ativo']);
	$indice = ($_POST['indice']);

	$codigo_marca = empty($codigo_marca) ? 'null' : $codigo_marca;

	if (isset($_FILES['arquivos']) and strlen($msg_erro)==0){

		$Destino = '../logos/';

		$Fotos = $_FILES['arquivos'];

		$qtde_de_fotos = $_POST['qtde_de_fotos'];

		for ($i=0; $i<$qtde_de_fotos; $i++){

			 // retorna qndo nw tiver foto
			if(!isset($Fotos['tmp_name'][$i])) {
				continue;
			}

			$Nome    = $Fotos['name'][$i];
			$Tamanho = $Fotos['size'][$i];
			$Tipo    = $Fotos['type'][$i];
			$Tmpname = $Fotos['tmp_name'][$i];

			// echo $Nome.'<br />';
			// echo $Tamanho.'<br />';
			// echo $Tipo.'<br />';
			// echo $Tmpname.'<br />'; exit;

			if (strlen($Nome)==0) continue;

			$Extensao = substr($Nome,strlen($Nome)-5,5);

			if(strlen($Extensao)>0){

				if(preg_match('/^image\/(pjpeg|jpeg|png|gif)$/', $Tipo)){
					if(!is_uploaded_file($Tmpname)){
						$msg_erro .= "Não foi possível efetuar o upload.";
						break;
					}

					$tmp = explode(".",$Nome);

					$ext = $tmp[count($tmp)-1];



					if (strlen($extensao)==0){
						$ext = $Extensao;
					}


					$nome_foto  = trim($_POST["nome"])."$ext";

					$nome_foto = str_replace(" ","_",$nome_foto);

					$Caminho_foto  = $Destino . $nome_foto;

					reduz_imagem($Tmpname, 140, 40, $Caminho_foto);

				}else{
					$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
				}
			}
		}
	}

	if(strlen($nome_foto)>0){$xnome_foto = "'".$nome_foto."'";}else{$xnome_foto="null";}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($marca) == 0) {

			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO tbl_marca (
						fabrica,
						nome,
						codigo_marca,
						logo,
						ativo,
						visivel
					) VALUES (
						$login_fabrica,
						$aux_nome,
						$codigo_marca,
						$xnome_foto,
						$aux_ativo,
						$aux_indice
					);";

		}else{

			###ALTERA REGISTRO
			$sql = "UPDATE tbl_marca SET
							nome = $aux_nome,
							codigo_marca = $codigo_marca,
							logo = $xnome_foto,
							ativo = $aux_ativo,
							visivel = $aux_indice
					WHERE  tbl_marca.fabrica = $login_fabrica
					AND    tbl_marca.marca   = $marca;";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		$msg_sucesso = 'Gravado com sucesso';

		header ("Location: $PHP_SELF?Message=$msg_sucesso");
		exit;
	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$marca 		= $_POST["marca"];
		$nome  		= $_POST["nome"];
		$ativo 	    = $_POST["ativo"];
		$indice     = $_POST["indice"];

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


	###CARREGA REGISTRO
	if (strlen($marca) > 0) {
		$sql = "SELECT  tbl_marca.marca,
						tbl_marca.nome,
						codigo_marca,
						logo,
						ativo,
						visivel
				FROM    tbl_marca
				WHERE   tbl_marca.fabrica = $login_fabrica
				AND     tbl_marca.marca   = $marca
				";
		$res = pg_exec ($con,$sql);
		//echo $sql;exit;
		if (pg_numrows($res) > 0) {
			$marca = trim(pg_result($res,0,marca));
			$nome  = trim(pg_result($res,0,nome));
			$logo  = trim(pg_result($res,0,logo));
			$codigo_marca  = trim(pg_result($res,0,codigo_marca));
			$ativo = pg_result($res,0,ativo);
			$indice = pg_result($res,0,visivel);
		}
	}

	$layout_menu = "cadastro";
	$title = "CADASTRAMENTO DE MARCA DE PRODUTO";

	include 'cabecalho_new.php';


	$plugins = array(
		"shadowbox"
	);

include("plugin_loader.php");
?>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />


<?php
if(strlen($msg_erro) > 0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<? } ?>

<?php
if(isset($_GET['Message'])){
?>
<div class="alert alert-success">
	<h4><? echo $_GET['Message']; ?></h4>
</div>
<?php
}
?>
<form name="frm_marca" method="post" action="<? $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario' enctype='multipart/form-data'>

	<input type="hidden" name="marca" value="<? echo $marca ?>">

	<div class='titulo_tabela '>Cadastro</div>
	<br/>
	<div class="container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='nome'>Nome da Marca</label>
					<div class='controls controls-row'>
						<div class='span7'>
							<input type="text" id="nome" name="nome" class='span12' maxlength="20" value="<? echo $nome ?>" >
						</div>
					</div>
				</div>
			</div>
			<?php if( !in_array($login_fabrica, array(169,170,176))){ ?>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='qtde_de_fotos'>Selecione a Imagem</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<?php
								$qtde_imagens = 1;
								echo "<input type='hidden' name='qtde_de_fotos' value='$qtde_imagens' >";
								echo ' <input type="file" value="Procurar foto" name="arquivos[]" class="multi {accept:\'jpg|gif|png\', max:'.$qtde_imagens.', STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}" size="14"/>';
								if(strlen($logo)>0){
									echo "<BR><a href='$PHP_SELF?ajax=true&imagem=$logo&keepThis=trueTB_iframe=true&height=340&width=420' title='Logo' class='thickbox'>Visualizar Imagem Cadastrada</a>";
								}
							?>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class='span2'></div>
		</div>
	</div>
	<? if ( in_array($login_fabrica, array(104,105, 158)) ) : // HD 806096 ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_marca'>Código</label>
				<div class='controls controls-row'>
					<div class='span7'>
						<input type="text" id="codigo_marca" name="codigo_marca" class='span12' maxlength="20" value="<? echo $codigo_marca ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'></div>
		<div class='span2'></div>
	</div>
	<? endif; ?>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group">
				<div class="controls controls-row">
					<input type='checkbox' name='ativo' id='ativo' value='TRUE' <?if($ativo == 't') echo "CHECKED";?> />
				  <label class="control-label" for="">Ativo</label>
				</div>
			</div>
			<?php
				if ($login_fabrica == 176)
				{
			?>	
				<div class="control-group">
					<div class="controls controls-row">
						<input type='checkbox' name='indice' id='indice' value='TRUE' <?if($indice == 't') echo "CHECKED";?> />
					  <label class="control-label" for="">Índice</label>
					</div>
				</div>
			<?php
				}
			?>
		</div>
		<div class="span4"></div>
		<div class="span2"></div>
	</div>

	<p><br/>
		<input type='hidden' name='btnacao' value=''>
		<input type='button' class="btn btn-success" value='Gravar' ONCLICK="javascript: if (document.frm_marca.btnacao.value == '' ) { document.frm_marca.btnacao.value='gravar' ; document.frm_marca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
		<input type='button' class="btn" value='Limpar' ONCLICK="javascript: if (document.frm_marca.btnacao.value == '' ) { document.frm_marca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
	</p><br/>

</form>


<table class='table table-striped table-bordered table-hover table-large'>

	<thead>
		<tr class='titulo_tabela'>
			<?php 
				$colspan = (in_array($login_fabrica, [158,176])) ? '3' : '2';
			?>
			<th colspan='<?= $colspan; ?>'>Relação de Marcas Cadastradas</th>
		</tr>


		<tr class='titulo_coluna'>
			<?php if($login_fabrica == 158){ ?>
				<th>Código</th>
			<?php } ?>
			<th>Marca</th>
			<th>Ativo</th>
			<?php
				if ($login_fabrica == 176){
					echo "<th>Índice</th>";
				}
			?>
		</tr>
	</thead>
	<tbody>
	<?
		$sql = "SELECT tbl_marca.marca,
						tbl_marca.nome,
						tbl_marca.ativo,
						tbl_marca.visivel,
						tbl_marca.codigo_marca
				FROM    tbl_marca
				WHERE   tbl_marca.fabrica = $login_fabrica
				ORDER BY tbl_marca.nome;";
		$res = pg_exec ($con,$sql);

		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$marca 		= trim(pg_result($res,$x,marca));
			$nome  		= trim(pg_result($res,$x,nome));
			$ativo  	= trim(pg_result($res,$x,ativo));
			$indice 	= trim(pg_result($res,$x,visivel));
			$codigo_marca = trim(pg_result($res,$x,codigo_marca));

			if($ativo=='t') $ativo = "<img title='Ativo' src='imagens/status_verde.png'>";
			else              $ativo = "<img title='Inativo' src='imagens/status_vermelho.png'>";
			if($indice=='t') $indice = "<img title='Ativo' src='imagens/status_verde.png'>";
			else              $indice = "<img title='Inativo' src='imagens/status_vermelho.png'>";
			echo "<tr>";

				if($login_fabrica = 158){
					echo "<td>";
						echo "<a href='$PHP_SELF?marca=$marca'>$codigo_marca</a>";
					echo "</td>";
				}


				echo "<td>";
					echo "<a href='$PHP_SELF?marca=$marca'>$nome</a>";
				echo "</td>";
				echo "<td class='tac'>$ativo</td>";
				if ($login_fabrica == 176)
				{
					echo "<td class='tac'>$indice</td>";
				}
			echo "</tr>";
		}
	?>
	</tbody>
</table>

</body>
</html>
