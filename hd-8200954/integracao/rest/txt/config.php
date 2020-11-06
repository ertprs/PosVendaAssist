<?php
@session_start();

include_once 'src/config.php';
include_once 'src/function/validate.php';
$error = Array();

// Valida UPLOAD
if(!empty($_POST)){
	$_SESSION['header'] = $_POST;
	header("Location: peca.php");
}

$client = null;
$client_code = null;
$client_secret_key = null;
$ip_address = null;
$version = null;
$environment = null;
$response_format = null;
$accept_enconding = null;

if(!empty($_SESSION['header'])){
	$client = $_SESSION['header']['client'];
	$client_code = $_SESSION['header']['client_code'];
	$client_secret_key = $_SESSION['header']['client_secret_key'];
	$ip_address = $_SESSION['header']['ip_address'];
	$version = $_SESSION['header']['version'];
	$environment = $_SESSION['header']['environment'];
	$response_format = $_SESSION['header']['response_format'];
	$accept_enconding = $_SESSION['header']['accept_enconding'];
}
?>

<?php include_once 'inc/header.php'; ?>

<form id='form' class="form-horizontal" action=""
	enctype="multipart/form-data" method="POST">
	<fieldset>
		<div id="legend">
			<legend>Parametros Header</legend>
		</div>

		<?php msgError($error); ?>

		<div class="control-group">
			<label class="control-label">Cliente</label>
			<div class="controls">
				<input class="input-file" value="<?php echo $client; ?>" id='client' name='client' type="text"
					pattern="^[a-zA-Z0-9{_}]+$" required />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">Cliente Código</label>
			<div class="controls">
				<input class="input-file" value="<?php echo $client_code; ?>" name='client_code' type="text"
					pattern="\d+" required />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">Key</label>
			<div class="controls">
				<input class="input-file" value="<?php echo $client_secret_key; ?>" name='client_secret_key' type="text"
					required />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">IP</label>
			<div class="controls">
				<input class="input-file" value="<?php echo $ip_address; ?>" name='ip_address' type="text"
					pattern="(\d{1,3}\.){3}\d{1,3}" required />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">Version</label>
			<div class="controls">
				<input class="input-file" value="<?php echo $version; ?>"name='version' type="text"
					pattern="\d{4}\-\d{2}\-\d{2}" required />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">Ambiente</label>
			<div class="controls">
				<select name="environment">
					<option value='production' <?php if($environment == 'production') echo " selected='selected' "?> >Produção</option>
					<option value='testing' <?php if($environment == 'testing') echo " selected='selected' "?>>Teste</option>
				</select>
			</div>
		</div>

		<div class="control-group">
			<label class="control-label">Reposta Formato</label>
			<div class="controls">
				<select name="response_format">
					<option value='json' <?php if($response_format == 'json') echo " selected='selected' "?>>JSON</option>
					<option value='xml' <?php if($response_format == 'xml') echo " selected='selected' "?>>XML</option>
				</select>
			</div>
		</div>

		<div class="control-group">
			<div class="controls">
				<input type="hidden" name="accept_enconding" value="deflate" />
				<button type="submit" class="btn btn-primary">Gravar</button>
			</div>
		</div>
	</fieldset>
</form>

<script>
  	$(document).ready(function(){
  		$("#form").validate({
			rules: {
		  		client: {
		      		required: true,
		      		number: true
		    	}
		  	}
		});
  	});
</script>
<?php include_once 'inc/footer.php'; ?>