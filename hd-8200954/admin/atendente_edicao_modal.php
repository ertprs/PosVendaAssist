<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include 'autentica_admin.php';
include "funcoes.php";

?>

<!DOCTYPE html/>
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/resize.js"></script>
	</head>

<?php 

$atendente_id = 0;
if(isset($_GET["atendente_id"])){
	$atendente_id = $_GET["atendente_id"];
}

if(isset($_POST['btn_salvar'])){
	$msg_erro = "";
	$atendente = $_POST['atendente'];
	$postos = implode(',' , $_POST['postos_select']);
	$atendente_anterior = $_POST['atendente_anterior'];

    $res = pg_query($con, 'BEGIN TRANSACTION');
    
    if ($login_fabrica != 30) {
	    // transferir postos
	    $sqlTransf = "UPDATE tbl_posto_fabrica SET admin_sap=".$atendente." WHERE fabrica={$login_fabrica} AND posto IN ($postos);";
	    $resTransf = pg_query($con, $sqlTransf);
    } else {
    	foreach ($_POST['postos_select'] as $key => $value) {
    		
	    	$sql_atendente_estado = "	SELECT admin_atendente_estado 
	    								FROM tbl_admin_atendente_estado 
	    								WHERE admin = $atendente_anterior 
	    								AND posto_filial = $value 
	    								AND fabrica = $login_fabrica";
	    	$res_atendente_estado = pg_query($con, $sql_atendente_estado);
	    	
	    	if (pg_num_rows($res_atendente_estado) > 0) {
	    		$admin_atendente_estado = pg_fetch_result($res_atendente_estado, 0, 'admin_atendente_estado');
	    		$sqlTransfAtendente = "UPDATE tbl_admin_atendente_estado SET admin = $atendente WHERE admin_atendente_estado = $admin_atendente_estado";
	    	} else {
	    		$sqlTransfAtendente = "UPDATE tbl_posto_fabrica SET admin_sap = $atendente WHERE fabrica = $login_fabrica AND posto = $value";
	    	}
	    	
	    	$resTransfAtendente = pg_query($con, $sqlTransfAtendente);
	    	if (pg_last_error($con)) {
	    		$msg_erro = "erro";
	    	}
    	}
    }

    if (pg_last_error($con) || $msg_erro == "erro") {
        $res = pg_query($con,"ROLLBACK TRANSACTION");
	}else{
    	$res = pg_query($con,"COMMIT TRANSACTION;");
		echo '<script>$(function(){window.parent.redirect();});</script>';
	}
}
?>
	<body> 
		<div id="container" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
				<div class="alert alert-danger">
					Selecione o atendente que recebera os postos marcados.
				</div>
				<form action="<?=$_SERVER['PHP_SELF']?>" method='POST'>
					<table id="atendente_cadastrados" class='table table-striped table-bordered table-hover table-fixed' >
					    <thead>
					        <tr class="titulo_coluna" >
					            <th>Atendente</th>
					            <th>Posto</th>
					        </tr>
					    </thead>
					    <tbody>
					        <?php
					            $sql = "SELECT  tbl_posto_fabrica.admin_sap,
					                                    array_to_string(array_agg(tbl_posto.posto || '|' || tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome),';') AS nome_posto,
					                                    tbl_admin.nome_completo AS nome_atendente
					                            FROM    tbl_posto_fabrica
					                            JOIN    tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
					                            AND     tbl_posto_fabrica.fabrica={$login_fabrica}
					                            JOIN    tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
					                            WHERE   tbl_posto_fabrica.admin_sap = $atendente_id
  												GROUP BY tbl_posto_fabrica.admin_sap,
  														 tbl_admin.nome_completo";

  								  if ($login_fabrica == 30) {         
					                $sql = "SELECT  tbl_posto_fabrica.admin_sap,
			                                        array_to_string(array_agg(tbl_posto.posto || '|' || tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome),';') AS nome_posto,
			                                        tbl_admin.nome_completo AS nome_atendente
			                                FROM    tbl_posto_fabrica
			                                JOIN    tbl_posto ON tbl_posto.posto=tbl_posto_fabrica.posto
			                                AND     tbl_posto_fabrica.fabrica={$login_fabrica}
			                                JOIN    tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
			                                WHERE   tbl_posto_fabrica.fabrica = {$login_fabrica}
			                                AND     tbl_posto_fabrica.admin_sap IS NOT NULL
			                            GROUP BY    tbl_posto_fabrica.admin_sap,
			                                        tbl_admin.nome_completo
			                            UNION 
			                                SELECT  tbl_admin_atendente_estado.admin, 
			                                        array_to_string(array_agg(tbl_posto.posto || '|' || tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome),';') AS nome_posto, 
			                                        tbl_admin.nome_completo as nome_atendente 
			                                FROM    tbl_admin_atendente_estado 
			                                JOIN    tbl_posto ON tbl_admin_atendente_estado.posto_filial = tbl_posto.posto 
			                                JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}  
			                                JOIN    tbl_admin ON tbl_admin_atendente_estado.admin = tbl_admin.admin 
			                                WHERE   tbl_admin_atendente_estado.fabrica = {$login_fabrica} 
			                                AND     tbl_admin_atendente_estado.admin = {$atendente_id}
			                                AND     tbl_admin_atendente_estado.hd_classificacao NOTNULL 
			                                AND     tbl_admin_atendente_estado.posto_filial NOTNULL 
			                            GROUP BY    tbl_admin_atendente_estado.admin, 
			                                        tbl_admin.nome_completo";
					            }

					            $res = pg_query($con, $sql);

					            if (pg_num_rows($res) > 0) { 

				                    $nome_atendente = pg_fetch_result($res, 0, 'nome_atendente');
									$postos = pg_fetch_result($res, 0, 'nome_posto');
				                    $postos = explode(";",$postos);
				            	?>	
			                    <tr>
			                        <td>
			                            <table width="100%">
			                                <tr>
			                                    <select id='atendente' name='atendente' class='span12 select2'>
					                            <?php
					                                $sqlAdmin = "SELECT DISTINCT admin,
					                                            nome_completo
					                                          FROM tbl_admin
					                                         WHERE fabrica = {$login_fabrica}
					                                           AND admin_sap IS TRUE
					                                      ORDER BY nome_completo";
					                                $resAdmin = pg_query($con, $sqlAdmin);

					                                if (pg_num_rows($resAdmin) > 0) {
					                                    echo "<option value=''>Selecione ...</option>";
					                                    for ($i = 0 ; $i < pg_num_rows($resAdmin) ; $i++) {
					                                        $id   = pg_result ($resAdmin, $i, admin);
					                                        $nome = pg_result ($resAdmin, $i, nome_completo);
					                                        $selected = ($atendente_id == $id) ? "selected='selected'" : "";
					                                        $retorno .= "<option value='$id' {$selected}>$nome</option>";
					                                    }
					                                    echo $retorno;
					                                } else {
					                                    echo "<option value=''>Selecione ...</option>";
					                                }
					                            ?>
					                        	</select>
			                                </tr>
			                            </table>
			                        </td>
			                        <td>
		                            	<table width="100%">
			                            <?php foreach ($postos as $kPostos => $vPostos) {
									      $posto = explode("|",$vPostos);
									      echo "<tr><td><input name='postos_select[]' type='checkbox' value='{$posto[0]}' checked='checked'>";
									      echo "<span>{$posto[1]}</span></td></tr>";
		                                }?>
			                            </table>
			                        </td>
		                    	</tr>
		                    	<?php }?>	
					    </tbody>
					</table>
				<div class="row-fluid">
		        	<div class="span4"></div>
			        <div class="span4">
			            <div class="control-group">
			                <div class="controls controls-row tac">
			                    <button type="button" class="btn btn-danger" onclick="window.parent.Shadowbox.close();" value="cancelar">Cancelar</button>
			                    <button id="btn_salvar" class="btn btn-primary" type="button" onclick="
			                    $(this).parents('form').submit(); 
			                    ">Salvar</button>
			                    <input type='hidden' id="btn_click" name='btn_salvar' value='' />
			                    <input type='hidden' id="atendente_anterior" name='atendente_anterior' value='<?=$atendente_id?>' />
			                </div>
			            </div>  
			        </div>  
			    </div>  
			</form>
			</div>
		</div>
	</body>
</html>