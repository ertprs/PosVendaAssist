
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

    <!--[if lt IE 10]>
  	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
	<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
	<![endif]-->
	
    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
  

<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST["btn_acao"])) {
	$admin = $_POST['admin'];
	
	if (count($admin) == 0 or count($admin) == 1){
		$msg_erro = "Preencha no minimo dois Admins";
	}else{

		$total_admins = count($admin);
		$admins = implode(",", $admin);

		$sql = "SELECT email FROM tbl_admin WHERE admin IN($admins) AND email != ''";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == $total_admins){

			$sql = "UPDATE tbl_admin SET responsabilidade = 'integracoes' WHERE admin IN($admins)";
			$res = pg_query($con, $sql);

			if(pg_affected_rows($res) > 0){
				$sucesso = "Admins Cadastrado com Sucesso!";
			}

		}else{
			$msg_erro = "Existem Admins sem email, por favor confira!";
		}
		
	}
}


$sql = "SELECT  tbl_admin.admin
		FROM    tbl_posto_fabrica
		WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
		  AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
	$res = pg_query($con,$sql);

$plugins = array(
	"multiselect"
);

include("plugin_loader.php");
?>
	
	<style type="text/css">
	
	#window_box {
	    display: block;
	    background-color: #ffffff;
	    position: relative;
		text-align: left;
	    top:  0;
	    left: 0;
        padding: 32px 0 1em 1em;
		margin: 20px auto;
	    border: 2px solid #68769f;
        border-radius: 8px;
        -moz-border-radius: 8px;
		box-shadow: 3px 3px 3px #ccc;
		-moz-box-shadow: 3px 3px 3px #ccc;
        overflow: hidden;
		z-index: 450;
		width:785px;
		*width:800px;
		_width:800px;
		_margin: 1em 15%;
		*margin: 1em 15%;
	}
	#window_box:hover {
		box-shadow: 5px 4px 5px grey;
		-moz-box-shadow: 5px 4px 5px grey;
	}
	#window_box #ei_container p {
		font-size: 14px;
	    padding: .5ex 1ex;
		overflow-y:auto;
	}
	#window_box #ei_header {
		position: absolute;
		top:	0;
		left:	0;
		margin:	0;
		width: 100%;
		_width: 798px;
		*width: 798px;
		height:28px;
		border-radius: 7px 7px 0 0 ;
        -moz-border-radius: 7px 7px 0 0 ;
        -webkit-border-radius: 7px 7px 0 0;
		background-image: url('/assist/admin/imagens_admin/azul.gif');    /* IE, Opera 11- */
		background-image: linear-gradient(top        , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -o-linear-gradient(top     , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -ms-linear-gradient(top    , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -moz-linear-gradient(top   , #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -webkit-linear-gradient(top, #b4bbce, #68769f 6px, #68769f 15px, #7889bb);
		background-image: -webkit-gradient(linear,  0 0, 0 100%,
											from(#b4bbce),
												color-stop(0.07,#68769f),
												color-stop(0.20,#68769f),
											to(#7889bb));
	    padding: 2px 1em;
	    color: white;
	    font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
		cursor: move;
	}
	#window_box #ei_container {
        background-color: #fdfdfd;
		margin: auto;
		overflow-y: auto;
	    height: 550px;
		font-size: 15px;
		text-align:justify;
		color: #313452;
		width: 96%;
		position:relative;
	}

	
	#window_box h3 {
		text-align: center;
		font-size: 1.5em;
		text-shadow: 2px 2px 4px #666;
	}
	#window_box div#footer {
		margin-top: 60px;
		width: 96%;
	}
	</style>

<!--[if IE]>
  	<style type="text/css">
		#ei_container #msgErro {
			color: #900;
			background-color: #ff8080;
		}
	</style>
<![endif]-->

    <script type="text/javascript">
	$(function() {
		
		$("#admin").multiselect();

		if($('.alert-success').is(':visible')){
			setTimeout(function(){
				window.location.href = 'menu_cadastro.php';
			}, 2000);
		}

	});

	// function setCookie(c_name,value,domain,path,expiredays) {
	// 	var exdate=new Date();
	// 		exdate.setDate(exdate.getDate()+expiredays);
	// 	var c_domain   = (domain == null || domain == "") ? "" : ";domain="+domain;
	// 	var c_path     = (path == null || path == "") ? "" : ";path="+path;
	// 	var expireDate = (expiredays==null) ? "" : ";expires="+exdate.toUTCString();
	// 	document.cookie=c_name+ "=" +escape(value)+c_domain+c_path+expireDate;
	// }

	// function desfazerNuvem() {
	// 	/*
	// 	SE FECHAR A JANELA SETAR O COOKIE tc_mostra_comunicado_integracao = false
	// 	*/
	// 	setCookie('HDComunicadoJanela','ja_li');
	// 	document.getElementById('window_box').style.display = 'none';
	// }
    </script>

	 <div id="window_box" style="";>
		<div id="ei_header">
			<img src='imagens/tc_2009.ico' style='padding: 4px 1ex 0 0;width:16px;height:16px' />&nbsp;Comunicado Telecontrol
		</div><br />
	 	
	 	<div id="ei_container">
	 		<div class="span9">
		 		<div class="row-fluid">
			 		<?
						if(count($msg_erro) > 0){
					?>
					<div class="row-fluid">
						<div class="span12">	          
			        		<div class="alert alert-error">                
			            		<h4><? echo $msg_erro; ?></h4>
			        		</div>
			    		</div>
			    	</div>
			    
					<?
						}
					?>

					<?
						if(strlen($sucesso) > 0){
					?>
					<div class="row-fluid">
						<div class="span12">	          
			        		<div class="alert alert-success">                
			            		<h4><? echo $sucesso; ?></h4>
			        		</div>
			    		</div>
			    	</div>
			    
					<?
						}
					?>
					
			    </div>

				<!-- <h3>NOVO PROTOCOLO DE LOGIN</h3> -->
				<p>

					Prezado Usuário. <br /> <br />
					
					Favor selecionar os usuários que irão receber os logs de integração entre 
					Telecontrol x fábrica, esses logs serão enviados por email e ajudarão para sempre 
					que tiver problemas na importação ou exportação de arquivo, ou também quando 
					um arquivo for importado corretamente. <br /> <br />

					É importante que o email do usuário selecionado esteja correto.  <br /> <br />

					É importante selecionar mais de um usuário, para evitar problemas na ausência de 
					um funcionário.  <br /> <br />

					Atenciosamente,  <br />
					<strong>Equipe Telecontrol</strong>

				</p>

				<br />
		
				<form name='admin' METHOD='POST' ACTION='<?=$_SERVER['PHP_SELF']?>'>
					<div class="row-fluid">	
						<div class="span12">
							<div class="inptc1"></div>
							<div class='span6'>
								<select name="admin[]" id="admin" multiple="multiple" >
									<?php
									$sql = "SELECT admin , nome_completo , ativo, email
											FROM tbl_admin
											WHERE fabrica = $login_fabrica
											AND ativo = 't' AND email != ''
											ORDER BY nome_completo ASC";
									$res = pg_query($con,$sql);

									foreach (pg_fetch_all($res) as $key) {
										$selected_admin = ( isset($admin) and ($admin == $key['admin']) ) ? "SELECTED" : '' ;

									?>
										<option value="<?php echo $key['admin']?>" <?php echo $selected_admin ?> >

											<?php echo $key['nome_completo']?><br />
											<?php echo $key['email']?>

										</option>
									<?php
									}
									?>
								</select>

							</div>	
							<!-- <div class="span2">
								<input class="span12" type="text" value="<?php echo $key['email']?>"></input>
							</div> -->
							<div class="span4 pull-right">Caso admin não esteja nessa lista</div>
						</div>
					</div>
					<div class="row-fluid">
						<div class="span9"></div>
						<div class="span3">
							<a class="btn" href="./admin_senha_n.php" target="_blank">Adicionar Admin</a>
						</div>
					</div>

					<div id='footer'>
						<div class="span2" style="margin-top:88px;">
							<input class="btn btn-success" id="btn_acao" name="btn_acao" type="submit" value="Salvar">
						</div>
						<div class="span3" style="margin-top:88px;">
							<a class="btn btn-primary" href="./menu_cadastro.php">Acessar Sistema</a>
						</div>
						<p align="right"style="color:#2B2B4D;">
						Atenciosamente,<br /><strong>Equipe Telecontrol</strong></p>
						<p align="right"style="color:#2B2B4D;">
							<img style='float:right;margin-left: 0.5em; width:200px;'
								   alt='TelecontrolCloud'
		                           src='../logos/logo_telecontrol_2013.png' height='' />
						</p>
					</div>
				</form>
			</div>
		</div>
	</div>
	
