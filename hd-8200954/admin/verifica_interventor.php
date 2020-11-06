<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$abre_os_mondial    = $_REQUEST["abre_os_mondial"];
$cpf_cnpj    = $_REQUEST["cpf_cnpj"];
$atendimento = $_REQUEST["atendimento"];
$os_troca_subconjunto = $_REQUEST["os_troca_subconjunto"];
$os_troca = $_REQUEST["os_troca"];
$permissao_supervisor = $_REQUEST["permissao_supervisor"];

$plugins = array(    
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",    
    "dataTable",
	"alphanumeric"
);

include("plugin_loader.php");


$callcenter 			= $_GET['callcenter'];
$produto 				= $_GET['produto'];
$abre_os_mondial 		= $_GET['abre_os_mondial'];
$cpf_cnpj				= $_GET['cpf_cnpj'];
$os_troca_subconjunto	= $_GET['os_troca_subconjunto'];
$serie                  = $_GET['serie'];
$hdchamado              = $_GET["hdchamado"];
$gerar_voucher			= $_GET['gerar_voucher'];

?>

<script>

	$(function(){
		$("#data_final_01").datepicker().mask("99/99/9999");
	});		

	function gerarPDF(){
		var x_data_final = document.getElementById("data_final_01").value;	
		var data_atual = $.datepicker.formatDate('dd/mm/yy', new Date());

		var data_atual_i 	= parseInt(data_atual.split("/")[2] + data_atual.split("/")[1] + data_atual.split("/")[0]);
		var x_data_final_i 	= parseInt(x_data_final.split("/")[2] + x_data_final.split("/")[1] + x_data_final.split("/")[0]);		

		if(x_data_final_i < data_atual_i){
			alert("Data da garantia não pode ser menor que a data atual");
		} else if(x_data_final != null && x_data_final != ""){
			$.ajax({
                url: 'gerar_garantia_estendida_mondial.php?dt_final=' + $("#data_final_01").val() + '&callcenter=<?=$callcenter?>&produto=<?=$produto?>&admin=<?=$admin?>',
                type: 'GET',
                complete: function(data){ 
                	alert("Sucesso ao gerar Garania Estendida!");
                	window.parent.Shadowbox.close();                    	         	                
                }
            });
        } else {
        	alert("É necessário digitar o campo Data Final");
        }        
	}	

</script>

<?

if($_POST["enviar"]){
	
	$senha = md5($_POST["senha"]);
	$admin = $_POST["admin"];
	$senha2 = $_POST['senha']; 

	if (in_array($login_fabrica, array(169, 170, 183))) {
		$cond = "callcenter_supervisor IS TRUE";
	} else {
		$cond = "intervensor IS TRUE";
	}

	$resultado = false;

	if($login_fabrica == 183) {

		
		require_once( '../classes/Posvenda/GoogleAuthenticator.php' );

		$autenticador = new GoogleAuthenticator();

		$sql = "select responsabilidade from tbl_admin where fabrica = $login_fabrica and admin = $admin and responsabilidade is not null";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res)>0) {
			$token = trim(pg_result($res,0,responsabilidade));
			$resultado = $autenticador->verifyCode( $token, $senha2, 0 );
		}
	}
	
	if($resultado == false) {

			
		$sql = "SELECT admin FROM tbl_admin WHERE {$cond} and md5(senha) = '{$senha}' and fabrica = {$login_fabrica} and admin = {$admin}";
		$res = pg_query($con, $sql);
	
		if (pg_num_rows($res) > 0) {
			$desbloqueado = true;
		} else {
			$msg_erro = "Senha inválida";
		}
	} else {
		$desbloqueado = true;
	}

	if ($login_fabrica == 30 && $desbloqueado) {
		$callcenter_hd   = $_POST["callcenter_hd"];
		$hdchamado       = $_POST["hdchamado"];
		$serie           = $_POST["serie"];
		$novo_comentario = '';

		if (!empty($callcenter_hd)) {
			$comentario = 'Atendimento com mesmo CPF e NF liberado pelo interventor';
			if (!empty($serie)) {
				$comentario = 'Atendimento com mesma Série liberado pelo interventor';
			}

			$status = "Aberto";
			if (!empty($hdchamado) && $hdchamado != 'novo') {
				$sqlStatus = " SELECT status FROM tbl_hd_chamado WHERE hd_chamado = $hdchamado AND fabrica = $login_fabrica";
				$resStatus = pg_query($con, $sqlStatus);
				if (pg_num_rows($resStatus) > 0) {
					$status = pg_fetch_result($resStatus, 0, "status");
				}
				
				$sqlInteracao = " INSERT INTO tbl_hd_chamado_item (hd_chamado, data, comentario, admin, interno, status_item) VALUES ($hdchamado, CURRENT_TIMESTAMP, '$comentario', $admin, TRUE, '$status') ";
				$resInteracao = pg_query($con, $sqlInteracao);
			} else {
				$novo_comentario = $comentario;
			}
		}
	} 
}

if($desbloqueado == true){
	if($login_fabrica == 151 AND $abre_os_mondial != 't' AND empty($cpf_cnpj) AND empty($os_troca_subconjunto) AND $gerar_voucher != 't'){ ?>
		<div class="row-fluid" >
			<div class='span1'></div>
			<div class='span10'>
				<div class='control-group' >
					<br />
	 				<strong>Para gerar a Garantia Estendida é necessário informar a data final da garantia</strong><br /><br />		 				
					<div class='control-group' id="campo_data_final">
						<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span4'>										
								<div id="click_final" style="display:inline-block; position:relative;">
									<input class="span12"  maxlength="10" type="text" name="data_final_01" id="data_final_01">								
								</div>
							</div>
						</div>
					</div>
	 				<br />
	 				<input type="button" name="btn_gerar" id="btn_gerar" value="Gerar" class="btn btn-success" onclick="gerarPDF();" />
	 				<input type="button" name="btn_cancelar" id="btn_cancelar" value="Cancelar" class="btn btn-danger" onclick="window.parent.Shadowbox.close();" />
	 			</div>
	 		</div>
	 		<div class='span1'></div>	 	
	 	</div>
	<?	exit;
	}
}

?>

<!-- <link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script> -->

<script>

	$(function() {
		var abre_os_mondial = "";
		if ($("#abre_os_mondial").val() != '') {
			abre_os_mondial = 't';
		}
		var os_troca_subconjunto = $("#os_troca_subconjunto").val();
		$("#senha").focus();

		$('#senha').on( 'keyup', function( e ) {
		    if( e.which == 9 ) {
		    	$("#senha").focus();
		    }
		});

		$("button.close").click(function(){
			$(".alert-error").hide();
		});

		$("button.cancelar_acao").click(function () {
			var os = $("#os_troca").val();
			//var os_troca_subconjunto = $("#os_troca_subconjunto").val();
			//console.log(os_troca_subconjunto);
			if (os_troca_subconjunto == 'TRUE') {
				window.parent.location = 'os_press.php?os='+os;
			} else if (abre_os_mondial == 't') {
				window.parent.retorna_permissao_abre_os("cancelar");
				window.parent.Shadowbox.close();
			}else{
				<?php if(in_array($login_fabrica, array(169,170)) AND $permissao_supervisor == "true"){ ?>
					window.parent.retorna_permissao_supervisor("cancelar_acao");
				<?php }else if ($login_fabrica == 183){ ?>
					window.parent.retorna_permissao_supervisor("cancelar_acao");
				<?php }else if ($login_fabrica == 151 AND empty($os_troca_subconjunto)){ ?>
					window.parent.retorna_permissao_supervisor_voucher("cancelar_acao");
				<?php }else{ ?>
					window.parent.location = 'callcenter_interativo_new.php';
				<?php } ?>
			}
		});

		<?php
		if (!empty($msg_erro) AND $login_fabrica != 183) {
		?>
			alert("<?=$msg_erro?>");
		<?php
		}

		if ($desbloqueado === true) {
		?>
			window.parent.document.getElementById('autoriza_gravacao').value = "desbloqueado";
			<?php 
				if ($login_fabrica == 30) { 
					if (!empty($novo_comentario)) {
			?>
						window.parent.document.getElementById('autoriza_gravacao_esmaltec').value = '<?=$novo_comentario?>';
						window.parent.document.getElementById('autoriza_admin_esmaltec').value = '<?=$admin?>';
			<?php   
					} else { 
				?>
						window.parent.document.getElementById('autoriza_gravacao_esmaltec').value = '';
						window.parent.document.getElementById('autoriza_admin_esmaltec').value = '';
			<?php 
					}
				}
			 ?>
			if (os_troca_subconjunto == 'TRUE') {
				window.parent.retorna_interventor(<?=$admin?>);
			}

			<?php if(in_array($login_fabrica, array(169,170)) AND $permissao_supervisor == "true"){ ?>
				window.parent.retorna_permissao_supervisor("login_supervisor");
			<?php }else if ($login_fabrica == 151 AND empty($os_troca_subconjunto)){ ?>
					window.parent.retorna_permissao_supervisor_voucher("login_supervisor");
			<?php }else{ ?>
				window.parent.Shadowbox.close();
			<?php } ?>

		<?php
		}
		?>
	});
</script>

<body class="container" style="background-color: #FFFFFF; overflow: hidden; padding: 10px 20px;" >
	<form method="post" >
		<?php
			if(in_array($login_fabrica, array(169,170))){
				if($permissao_supervisor == "true"){
					echo "<h4>Para prosseguir é necessário a senha de um Interventor:</h4>";
				}else{
					echo "<h4>Já existe um atendimento aberto com o CPF/CNPJ <br/>informado, favor informar a senha do supervisor para <br/>continuar</h4>";
				}
			}else if ($login_fabrica == 183){
				echo "<h4>Para prosseguir é necessário a senha de um supervisor:</h4>";
			}else{
				echo "<h4>Para prosseguir é necessário a senha de um Interventor:</h4>";
			}
		?>
		<?php if (!empty($msg_erro) AND $login_fabrica == 183){ ?>
			<div class="alert alert-error" style="width: 610px; !important">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<h4><?=$msg_erro?></h4>
			</div>
		<?php } ?>
		<div class="row-fluid" >
			<div class='span4' >
				<div class='control-group' >
					<label class='control-label' for='admin' ><?=($login_fabrica == 183) ? "Supervisor" : "Interventor"?></label>
					<div class='controls controls-row' >
						<div class='span12' >
							<select class="span12" id="admin" name="admin" required="required" >
								<option></option>
								<?php
								if (in_array($login_fabrica, array(169, 170, 183))) {
									$cond = "AND callcenter_supervisor IS TRUE";
								} else {
									$cond = "AND intervensor IS TRUE";
								}

								$sql = "SELECT tbl_admin.admin, tbl_admin.nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} {$cond} AND ativo IS TRUE ";
								$res = pg_query($con , $sql);

								if(pg_num_rows($res) > 0 ){
									for ($i=0; $i < pg_num_rows($res) ; $i++) {
										$admin = pg_fetch_result($res, $i, "admin");
										$nome_completo = pg_fetch_result($res, $i, "nome_completo");

										echo "<option value='{$admin}'>{$nome_completo}</option>";
									}
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row-fluid" >
			<div class='span4' >
				<div class='control-group' >
					<label class='control-label' for='senha' >Senha</label>
					<div class='controls controls-row' >
						<div class='span12' >
							<input type="hidden" id="atendimento" name="senha" value="<?=$atendimento?>" />
		      				<input type="password" class="span12" id="senha" name="senha" required="required" />
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="control-group-inline" >
		    <div class="controls" >
		    	<input type="hidden" id="abre_os_mondial" name="abre_os_mondial" value="<?=$abre_os_mondial?>" />
		    	<input type="hidden" id="os_troca_subconjunto" name="os_troca_subconjunto" value="<?=$os_troca_subconjunto?>" />
		    	<input type="hidden" name="callcenter_hd" value="<?=$atendimento?>">
		    	<input type="hidden" name="hdchamado" value="<?=$hdchamado?>">
		    	<input type="hidden" name="serie" value="<?=$serie?>">
		    	<input type="hidden" id="os_troca" name="os_troca" value="<?=$os_troca?>" />
		      <!-- <button type="submit" class="btn btn-primary btn-small desbloquear" name="enviar" data-loading-text="Desbloqueando..." onclick="this.button('loading');" value="desbloqueia" >Desbloquear</button> -->
		      	<button type="submit" class="btn btn-primary btn-small desbloquear" name="enviar" data-loading-text="Desbloqueando..." value="desbloqueia" >Desbloquear</button>
				<button type="button" class="btn btn-danger btn-small cancelar_acao" name="cancelar" >Cancelar</button>
	  	  </div>
	 	</div>
 	</form>
</body>
