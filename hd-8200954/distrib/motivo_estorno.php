<?php 
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';
include_once '../helpdesk/mlg_funciones.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

	$AuditorLog = new AuditorLog;
	$embarque = $_GET['embarque'];
	$fabrica = $_GET['fabrica'];

	if(isset($_POST['btnacao'])){		
		$embarque 	= $_POST['embarque_id'];
		$motivo 	= $_POST['motivo'];
		$fabrica 	= $_POST['fabrica_id'];

		if(strlen(trim($motivo))==0){
			$erro = "O campo motivo é obrigatório.<Br>";
		}else{

			$nome_usuario = "$login_unico - $login_unico_nome";
			$data = date("d-m-Y H:i:s");

			$AuditorLog->gravaLog('tbl_embarque', "$fabrica*$embarque", array("embarque" => $embarque, "motivo" => $motivo, 'data'=> $data, 'admin' => "$nome_usuario"));

			$sql = "select * from fn_cancela_embarque(array[$embarque]);";
			$res = pg_query($con, $sql);

			if(strlen(pg_last_error($con))==0){
				$ok = "Estorno realizado com sucesso.";
			}else{
				$erro = "Falha ao estornar embarque";
			}
		}
	}


?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
        <script type="text/javascript">            
            $(function(){
                
            });
        </script>
	</head>
	<body>
		<div class='container'>
			<?php if(strlen(trim($erro))>0){ ?>
			<div class="alert alert-error">                
                <h4><?=$erro ?></h4>
            </div>
            <?php } ?>
            <?php if(strlen(trim($ok))>0){ ?>
            <div class="alert alert-success">                
                <h4><?=$ok ?></h4>
            </div>
            <?php } ?>

			<form id="frm" name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'> 
	        <div id="frm_pesquisa_balanco" class="tc_formulario" >
	            <div class="titulo_tabela">Motivo Estorno - Embarque <?=$embarque?></div>
	              <br>
	              <div class="row-fluid">   
	                <div class="span2"></div> 
	                  <div class='span8'>
	                      <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
	                        <label class='control-label' for='data_inicial'>Motivo</label>
	                          <div class='controls controls-row'>
	                            <div class='span12'>
	                              <h5 class='asteristico'>*</h5>
	                                <textarea name='motivo' class="span12"></textarea>
	                            </div>
	                          </div>
	                      </div>
	                  </div>
	                  <div class="span2"></div> 
	                </div>
	            </div>
	            <br>
	            <div class="row-fluid">   
	                <div class="span2"></div> 
	                  <div class='span8'>	                        
	                      <div class='controls controls-row'>
	                        <div class='span12 tac'>
	                            <input type="submit" name="btnacao" class="btn btn-primary" value="Gravar">
	                            <input type="hidden" name="embarque_id" value="<?=$embarque?>">
	                            <input type="hidden" name="fabrica_id" value="<?=$fabrica?>">
	                        </div>
	                      </div>	                  
	                  </div>
	                  <div class="span2"></div> 
	                </div>
	            </div>
	        </form>
			
						
					
		</div>
	</body>
</html>