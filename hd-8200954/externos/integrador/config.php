<?php
@session_start();

include_once 'src/config.php';
//include_once 'src/function/validate.php';
$error = Array();

// Valida UPLOAD
if(!empty($_POST)){
	$_SESSION['header'] = $_POST;
	header("Location: client.php");
}

$client            = null;
$client_code       = null;
$client_secret_key = null;
$ip_address        = null;
$version           = null;
$environment       = null;
$response_format   = null;
$accept_enconding  = null;
$module            = null;

if(!empty($_SESSION['header'])){
	$client            = $_SESSION['header']['client'];
	$client_code       = $_SESSION['header']['client_code'];
	$client_secret_key = $_SESSION['header']['client_secret_key'];
	$ip_address        = $_SESSION['header']['ip_address'];
	$version           = $_SESSION['header']['version'];
	$environment       = $_SESSION['header']['environment'];
	$response_format   = $_SESSION['header']['response_format'];
	$accept_enconding  = $_SESSION['header']['accept_enconding'];
}

include_once 'inc/header.php'; ?>


<div class="span10">
	<h2 id="titulo-pagina">Parâmetros Header</h2>

	<form id='form' class="form-horizontal" action="" enctype="multipart/form-data" method="POST">				
		<div  class="row-fluid line-form">
			<?php msgError($error); ?>

			<div class="span3">           				
				<label>Cliente</label>				
				<input class="input-file" value="<?php echo $client; ?>" id='client' name='client' type="text" pattern="^[a-zA-Z0-9{_}]+$" required />				
			</div>

			<div class="span2">           				
				<label>Cliente Código</label>	
				<input class="span9" value="<?php echo $client_code; ?>" name='client_code' type="text" pattern="\d+" required />
			</div>
			<div class="span4">           				
				<label>IP</label>	
				<input value="<?php echo $ip_address; ?>" name='ip_address' type="text" pattern="(\d{1,3}\.){3}\d{1,3}" required />
			</div>

		</div>
		<div  class="row-fluid line-form">
			<div class="span5">
				<label>Key</label>
				<input class="span11" value="<?php echo $client_secret_key; ?>" name='client_secret_key' type="text" required />
			</div>

			<div class="span5">
				<label>Version</label>				
				<input class="input-file" value="<?php echo $version; ?>"name='version' type="text" pattern="\d{4}\-\d{2}\-\d{2}" required />
			</div>
		</div>

		<div  class="row-fluid line-form">
			<div class="span5">
				<label>Ambiente</label>
				<select name="environment">
						<option value='production' <?php if($environment == 'production') echo " selected='selected' "?> >Produção</option>
						<option value='test' <?php if($environment == 'test') echo " selected='selected' "?>>Teste</option>
				</select>
			</div>

			<div class="span5">
				<label>Reposta Formato</label>	
				<select name="response_format">
					<option value='json' <?php if($response_format == 'json') echo " selected='selected' "?>>JSON</option>
					<option value='xml' <?php if($response_format == 'xml') echo " selected='selected' disabled "?>>XML</option>
				</select>
			</div>			
		</div>

		<div  class="row-fluid line-form">
			<div class="span5">
				<input type="hidden" name="accept_enconding" value="deflate" />
				<button type="submit" class="btn btn-primary">Gravar</button>
			</div>
			
		</div>
</form>

<?php include_once 'inc/footer.php'; ?>
