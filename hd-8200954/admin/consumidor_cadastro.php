<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

if( strlen($_POST['cliente']) > 0 ) $cliente = trim($_POST['cliente']);
if( strlen($_GET['cliente']) > 0  ) $cliente = trim($_GET['cliente']);

$msg_sucesso = $_GET[ 'msg' ];
$msg_erro    = "";


##AJAX##
if ($_REQUEST['ajax'] == 'true'){

	if ($_REQUEST['action'] == 'mostra_cidades'){

		$uf = $_REQUEST['uf'];

		$sql = "SELECT cidade from tbl_ibge where estado = '$uf' order by cidade";
		$res = pg_query($con,$sql);

		for ($i=0; $i < pg_num_rows($res); $i++) {
			$cidade_ibge = pg_fetch_result($res, $i, 'cidade');
			echo "<option value='$cidade_ibge'> $cidade_ibge </option>";
		}

	}
	exit;

}

if( $btn_acao == "gravar" ){

	$nome             = trim($_POST['nome']);
	$cpf              = str_replace("-", "", trim($_POST['cpf']));
	$cpf              = str_replace(".", "", $cpf);
	$cpf              = str_replace("/", "", $cpf);
	$cpf              = str_replace(" ", "", $cpf);
	$rg               = trim($_POST['rg']);
	$endereco         = trim($_POST['endereco']);
	$numero           = trim($_POST['numero']);
	$complemento      = trim($_POST['complemento']);
	$bairro           = trim($_POST['bairro']);
	$cep              = trim($_POST['cep']);
	$cidade           = trim($_POST['consumidor_cidade']);
	$estado           = trim($_POST['estado']);
	$fone             = trim($_POST['fone']);
	$contrato         = trim($_POST['contrato']);
	$consumidor_final = trim($_POST['consumidor_final']);
	$contrato_numero  = trim($_POST['contrato_numero']);
	$campo_ext = "";
	$valor_ext = "";
	$campo_up    = "";

	if ($login_fabrica == 42) {

		$email  = trim($_POST['email']);
		if(strlen($cliente) > 0 ) {
			$campo_up = ", email='".$email."', fabrica=".$login_fabrica;
		} else {
			$campo_ext = ",email,fabrica";
			$valor_ext = ",'".$email."',".$login_fabrica;
		}
		if(strlen($cliente) == 0 ) {
			$sql = "SELECT cpf FROM tbl_cliente WHERE cpf ILIKE '%".$cpf."%' AND fabrica=".$login_fabrica;
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$msg_erro = "CPF/CNPJ já está cadastrado.";
			} 
		}
	}

	if (strlen($estado) == 0)
		$msg_erro = "Selecione o estado do Consumidor. ";
	else
		$xestado = "'".$estado."'";

	if (strlen($cidade) == 0)
		$msg_erro = "Digite a cidade do Consumidor. ";
	else
		$xcidade = "'".$cidade."'";

	if (strlen($nome) == 0)
		$msg_erro = "Digite o nome do Consumidor. ";
	else
		$xnome = "'".$nome."'";

	if (strlen($cpf) == 0)
		//$msg_erro = "Digite o CPF/CNPJ do Consumidor.";
		$xcpf = "null";
	else
		$xcpf = "'".$cpf."'";

	if (strlen($rg) == 0)
		$xrg = 'null';
	else
		$xrg = "'".$rg."'";

	if (strlen($contrato) == 0)
		$msg_erro = "Selecione se o consumidor possui contrato";
	else
		$xcontrato = "'".$contrato."'";

	if (strlen($consumidor_final) == 0)
		$msg_erro = "Selecione se consumidor é final ou não.";
	else
		$xconsumidor_final = "'".$consumidor_final."'";

	if (strlen($contrato_numero) == 0)
		$xcontrato_numero = 'null';
	else
		$xcontrato_numero = "'".$contrato_numero."'";

	if (strlen($endereco) == 0)
		$xendereco = 'null';
	else
		$xendereco = "'".$endereco."'";

	if (strlen($numero) == 0)
		$xnumero = 'null';
	else
		$xnumero = "'".$numero."'";

	if (strlen($complemento) == 0)
		$xcomplemento = 'null';
	else
		$xcomplemento = "'".$complemento."'";

	if (strlen($bairro) == 0)
		$xbairro = 'null';
	else
		$xbairro = "'".$bairro."'";

	if (strlen($cep) == 0)
		$xcep = 'null';
	else
		$xcep = "'".str_replace(".", "", str_replace("-", "", $cep))."'";

	if (strlen($fone) == 0)
		$xfone = 'null';
	else
		$xfone = "'".$fone."'";

	$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(fn_retira_especiais(nome), 'LATIN9')) = UPPER(TO_ASCII(fn_retira_especiais('{$cidade}'), 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$xcidade = pg_fetch_result($res, 0, "cidade");
	} else {
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(fn_retira_especiais(nome), 'LATIN9')) = UPPER(TO_ASCII(fn_retira_especiais('{$cidade}'), 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$xcidade = pg_fetch_result($res, 0, "cidade");
		} else {
			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(TO_ASCII(fn_retira_especiais(cidade), 'LATIN9')) = UPPER(TO_ASCII(fn_retira_especiais('{$cidade}'), 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
				$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

				$sql = "INSERT INTO tbl_cidade (
							nome, estado
						) VALUES (
							'{$cidade_ibge}', '{$cidade_estado_ibge}'
						) RETURNING cidade";
				$res = pg_query($con, $sql);

				$xcidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$msg_erro .= "Cidade não encontrada";
			}
		}
	}

	if( strlen($msg_erro) == 0 ){
		$res = pg_exec($con, "BEGIN TRANSACTION");

		if( strlen ($cliente) == 0 ){
			/*================ INSERE NOVO CLIENTE =========================*/
			$sql = "INSERT INTO tbl_cliente (
						nome            ,
						endereco        ,
						numero          ,
						complemento     ,
						bairro          ,
						cep             ,
						cidade          ,
						fone            ,
						cpf             ,
						contrato        ,
						rg              ,
						consumidor_final,
						contrato_numero
						{$campo_ext}
					) VALUES (
						$xnome            ,
						$xendereco        ,
						$xnumero          ,
						$xcomplemento     ,
						$xbairro          ,
						$xcep             ,
						$xcidade          ,
						$xfone            ,
						$xcpf             ,
						$xcontrato        ,
						$xrg              ,
						$xconsumidor_final,
						$xcontrato_numero
						{$valor_ext}
					)";
		}else{
			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_cliente SET
						nome             = $xnome            ,
						endereco         = $xendereco        ,
						numero           = $xnumero          ,
						complemento      = $xcomplemento     ,
						bairro           = $xbairro          ,
						cep              = $xcep             ,
						cidade           = $xcidade          ,
						fone             = $xfone            ,
						cpf              = $xcpf             ,
						contrato         = $xcontrato        ,
						rg               = $xrg              ,
						consumidor_final = $xconsumidor_final,
						contrato_numero  = $xcontrato_numero
						{$campo_up}
					WHERE cliente = '$cliente'";
		}
		$res = @pg_exec($con, $sql);
		//$msg_sucesso = 'Gravado com Sucesso!';
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro, 6);
	}

	if( strpos($msg_erro,"duplicate key value violates unique constraint \"tbl_cliente_cpf\"") )
		$msg_erro = "Este CPF/CNPJ já esta cadastrado.";

	if( strlen($msg_erro) == 0 ){
		$res = pg_exec($con, "COMMIT TRANSACTION");
		//header ("Location: menu_cadastro.php");
		header("Location: $PHP_SELF?msg=Gravado com Sucesso");
		exit;
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

/*================ LE OS DA BASE DE DADOS =========================*/
$cliente = filter_input(INPUT_GET,'cliente');

if( strlen($cliente) > 0 ){

	$sql = "SELECT tbl_cliente.*,
					tbl_cidade.nome AS cidade_nome,
					tbl_cidade.estado AS estado
			FROM tbl_cliente
			JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
			WHERE cliente = $cliente";
	$res = pg_exec ($con, $sql);

	if( pg_numrows ($res) == 1 ){
		$cliente          = pg_result($res, 0, cliente);
		$nome             = pg_result($res, 0, nome);
		$endereco         = pg_result($res, 0, endereco);
		$numero           = pg_result($res, 0, numero);
		$complemento      = pg_result($res, 0, complemento);
		$bairro           = pg_result($res, 0, bairro);
		$cep              = pg_result($res, 0, cep);
		$cidade           = pg_result($res, 0, cidade_nome);
		$estado           = pg_result($res, 0, estado);
		$fone             = pg_result($res, 0, fone);
		$cpf              = pg_result($res, 0, cpf);
		$contrato         = pg_result($res, 0, contrato);
		$rg               = pg_result($res, 0, rg);
		$consumidor_final = pg_result($res, 0, consumidor_final);
		$contrato_numero  = pg_result($res, 0, contrato_numero);

		if ($login_fabrica == 42) {
			$email  = pg_result($res, 0, email);
		}
	}
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/
if( strlen($msg_erro) > 0 ){
	$cliente		  = $_POST['cliente'];
	$nome			  = $_POST['nome'];
	$endereco		  = $_POST['endereco'];
	$numero			  = $_POST['numero'];
	$complemento	  = $_POST['complemento'];
	$bairro			  = $_POST['bairro'];
	$cep			  = $_POST['cep'];
	$cidade			  = $_POST['cidade'];
	$estado			  = $_POST['estado'];
	$fone			  = $_POST['fone'];
	$cpf			  = $_POST['cpf'];
	$contrato		  = $_POST['contrato'];
	$rg				  = $_POST['rg'];
	$consumidor_final = $_POST['consumidor_final'];
	$contrato_numero  = $_POST['contrato_numero'];
	if ($login_fabrica == 42) {
		$email  = $_POST['email'];
	}
}

if (strlen($msg_erro) > 0) {
		$controlgrup = "control-group error";
	}else{
		$controlgrup = "control-group";
	}

$title       = "CADASTRO DE CONSUMIDORES";
$layout_menu = 'cadastro';

include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"maskedinput",
	"dataTable",
	"alphanumeric"
);

include("plugin_loader.php");
?>

<!--=============== <FUNÇÕES> ================================!-->





<script type='text/javascript'>
	$(function(){
		// $("#posto_cidade").alpha({allow:" "});


		$("#cep").mask("99.999-999");
		//$("input[@name=]").alpha({allow:"., "});
		$("#consumidor_cidade").alpha();
		$('#rg').numeric({allow:"."});
// 		$.autocomplete('#consumidor_cidade','#consumidor_estado');

		$.autocompleteLoad(Array( "consumidor"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_consumidor(retorno){
			$("#cliente").val(retorno.cliente);
			$("#cpf").val(retorno.cpf);
			$("#nome").val(retorno.nome);
			$("#consumidor_cidade").val(retorno.consumidor_cidade);
			$("#endereco").val(retorno.endereco);
			$("#rg").val(retorno.rg);
			$("#numero").val(retorno.numero);
			$("#complemento").val(retorno.complemento);
			$("#bairro").val(retorno.bairro);
			$("#consumidor_estado").val(retorno.estado);
			$("#fone").val(retorno.fone);
			$("#cep").val(retorno.cep);
			$("#contrato_numero").val(retorno.contrato_numero);
			$("#email").val(retorno.email);

			if (retorno.contrato == 't'){
				$('#contratoSim').prop('checked', true);
				$('#contratoNao').prop('checked', false);
			}else{
				$('#contratoNao').prop('checked', true);
				$('#contratoSim').prop('checked', false);
			}

			if (retorno.consumidor_final == 't'){
				$('#consumidorSim').prop('checked', true);
				$('#consumidorNao').prop('checked', false);
			}else{
				$('#consumidorSim').prop('checked', false);
				$('#consumidorNao').prop('checked', true);
			}

			$("#contrato").val(retorno.contrato);
			$("#consumidor_final").val(retorno.consumidor_final);
			console.log(retorno.contrato);
			console.log(retorno.consumidor_final);
	}
</script>

<?php if(strlen($msg_erro)>0) { ?>
<div class='alert alert-error'>
	<h4><? echo $msg_erro; ?></h4>
</div>
<?
}

if( strlen( $msg_sucesso ) > 0 )
{
?>
<div class="alert alert-success">
	<h4><? echo $msg; ?></h4>
</div>

<?
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

	<!-- ------------- Formulário ----------------- -->
	<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>" class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Cadastro</div>
	<input class="frm" type="hidden" name="cliente" id="cliente" value="<?php echo $cliente; ?>">
	<br />


	<div class='row-fluid'>
		<div class='span2'></div>
			<div class="span4">
				<div class="<? echo $controlgrup?>">
					<label class='control-label' for='nome'>Nome Consumidor</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="nome" id="nome" class="span12" value="<? echo $nome ?>" >
							<span class="add-on" rel="lupa"><i class="icon-search" ></i>
							<input type="hidden" name="lupa_config" tipo="consumidor" parametro="nome_consumidor">
							</span>
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='cpf'>CPF/CNPJ Consumidor</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<input type="text" name="cpf" id="cpf" class='span12' value="<? echo $cpf ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i>
							<input type="hidden" name="lupa_config" tipo="consumidor" parametro="cnpj">
							</span>
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>

		<div class="span4">
			<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='rg'>RG/IE Consumidor</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<input type="text" name="rg" id="rg" class='span12' value="<? echo $rg ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='cep'>CEP</label>
				<div class='controls controls-row'>
					<div class='span12'>

						<input class="span12 addressZip" id='cep' type="text" name="cep" size="17" maxlength="8" value="<? echo $cep ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>

			<div class="span2">
				<div class="<? echo $controlgrup?>">
					<label class='control-label' for='consumidor_estado'>Estado</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<select  id="consumidor_estado" name="estado" class="span12 addressState">
								<option selected> </option>
								<?php
									$sql = "SELECT * FROM tbl_estado where pais = 'BR' AND estado <> 'EX' ORDER BY estado";
									$res = pg_exec($con, $sql);
									for( $i = 0 ; $i < pg_numrows($res); $i++ ){
										echo "<option ";
										if( $estado == pg_result ($res, $i, estado) )
											echo " selected " ;
										echo " value='" . pg_result ($res, $i, estado) . "'>";
										echo pg_result ($res,$i,nome);
										echo "</option>";
									}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class="<? echo $controlgrup?>">
					<label class='control-label' for='consumidor_cidade'>Cidade</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<h5 class='asteristico'>*</h5>
							<select id="consumidor_cidade" name="consumidor_cidade" class="span12 addressCity">
	                            <option value="" >Selecione</option>
	                            <?php
	                                if (strlen($estado) > 0) {
	                                    $sql = "SELECT DISTINCT * FROM (
	                                            SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
	                                                UNION (
	                                                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
	                                                )
	                                            ) AS cidade
	                                            ORDER BY cidade ASC";
	                                    $res = pg_query($con, $sql);

	                                    if (pg_num_rows($res) > 0) {
	                                        while ($result = pg_fetch_object($res)) {
	                                            $selected  = (trim($result->cidade) == $cidade) ? "SELECTED" : "";

	                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
	                                        }
	                                    }
	                                }
	                            ?>
	                        </select>
						</div>
					</div>
				</div>
			</div>

			<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='bairro'>Bairro</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="bairro" id="bairro" class='span12 addressDistrict' value="<? echo $bairro ?>" >
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>

			<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='endereco'>Endereço</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" name="endereco" id="endereco" class='span12 address' value="<? echo $endereco ?>" >
                        </div>
                    </div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='numero'>Número</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="numero" id="numero" class='span12' value="<? echo $numero ?>" >
						</div>
					</div>
				</div>
			</div>

			<div class="span2">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='complemento'>Complemento</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input type="text" name="complemento" id="complemento" class='span12' value="<? echo $complemento ?>" >
						</div>
					</div>
				</div>
			</div>

		<div class='span2'></div>
	</div>
	<?php if ($login_fabrica == 42) {?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class="span8">
			<div class='control-group <?=(in_array("email", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='email'>E-mail</label>
				<div class='controls controls-row'>
					<div class='span12'>
						<input class="span12" id='email' type="email" name="email" value="<?php echo $email;?>">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php }?>
	<div class='row-fluid'>
		<div class='span2'></div>

		<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='consumidor_final'>Consumidor Final? </label>
					<div class='controls controls-row'>
						<div class='span12'>
							<label class="radio">
        					<input type="radio" name="consumidor_final" id="consumidorSim" value="t">
        					Sim
    					</label>
    					<label class="radio">
        					<input type="radio" name="consumidor_final" id="consumidorNao" value="f" checked="">
        					Não
    					</label>
						</div>
					</div>
				</div>
			</div>


			<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='fone'>Telefone</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input  class="span12" type="text" name="fone" id="fone" size="17" maxlength="15" value="<?php echo $fone; ?>" onblur="this.className='span12'; displayText('&nbsp;');" onfocus="this.className='span12'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
						</div>
					</div>
				</div>
			</div>


		<div class='span2'></div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>

		<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='contrato'>Contrato? </label>
					<div class='controls controls-row'>
						<div class='span12'>
							<label class="radio">
        					<input type="radio" name="contrato" id="contratoSim" value="t" >
        					Sim
    					</label>
    					<label class="radio">
        					<input type="radio" name="contrato" id="contratoNao" value="f" checked="">
        					Não
    					</label>
						</div>
					</div>
				</div>
			</div>


			<div class="span4">
				<div class='control-group <?=(in_array("consumidor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='contrato_numero'>Nº do Contrato</label>
					<div class='controls controls-row'>
						<div class='span12'>
							<input class="span12" type="text" name="contrato_numero" id="contrato_numero" size="17" maxlength="10" value="<? echo $contrato_numero ?>" onblur="this.className='span12'; displayText('&nbsp;');" onfocus="this.className='span12'; displayText('&nbsp;Informe o número do contrato do consumidor.');">
						</div>
					</div>
				</div>
			</div>


		<div class='span2'></div>
	</div>

	<p>
  	<br />
  	<input type='hidden' name='btn_acao'>
		<button class="btn" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar dado consumidor" border='0' style='cursor: pointer'>Gravar</button>
 		<?php if ($login_fabrica == 42) {?> 
 			<a href="consumidor.php" class="btn btn-primary">Listar consumidores cadastrados</a>
 		<?php }?>
	
 	</p>
 	<br />
	</form>
<script type='text/javascript' src='address_components.js'></script>
<?php include "rodape.php"; ?>
