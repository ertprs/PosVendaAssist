<?php 

	include "dbconfig.php";
	include "includes/dbconnect-inc.php";
	include 'autentica_admin.php';	
	include 'funcoes.php';

	if(isset($_POST["btn_acao"])){
		$revenda_anterior = (strlen($_POST["revenda_anterior"]) > 0 ) ? $_POST["revenda_anterior"] : 'null';
		$data_nf_anterior = (strlen($_POST["data_nf_anterior"]) > 0 ) ? $_POST["data_nf_anterior"] : 'null';;
		$nota_fiscal_anterior = (strlen($_POST["nota_fiscal_anterior"]) > 0 ) ? $_POST["nota_fiscal_anterior"] : 'null';;
		$os = $_POST["os"];

		$sqlRev = "SELECT nome, cnpj FROM tbl_revenda WHERE revenda = $revenda_anterior";
		$resRev = pg_query($con, $sqlRev);
		if(pg_num_rows($resRev)>0){
			$nome_revenda = pg_fetch_result($resRev, 0, 'nome');
			$cnpj_revenda = pg_fetch_result($resRev, 0, 'cnpj');		

			$sql_upd = "UPDATE tbl_os SET  
							nota_fiscal = '$nota_fiscal_anterior', 
							revenda = $revenda_anterior, 
							data_nf = '$data_nf_anterior',
							revenda_nome = '$nome_revenda',
							revenda_cnpj = '$cnpj_revenda'
						WHERE os = $os 
						AND fabrica = $login_fabrica ";
			$res_upd = pg_query($con, $sql_upd);

			if(strlen(pg_last_error($con)) > 0 ){
				$msg_erro .= "Falha ao atualizar dados.";
			}else{
				$ok .= "Dados da O.S $os atualizados com sucesso.";
			}
		}else{
			$msg_erro .= "Falha ao atualizar dados.";
		}

		echo "<script>
			setTimeout(function(){ window.parent.Shadowbox.close(); }, 3000);
		</script>";

	}

	if(isset($_GET['os'])){
		$os = $_GET["os"];

		$sql_os = "SELECT tbl_os.os as os_atual, 
							tbl_os.nota_fiscal, 
							tbl_os.revenda,
							tbl_os.data_nf,
							tbl_os_extra.os_reincidente, 
							tbl_os.revenda_cnpj,
							tbl_os.revenda_nome,

							os_anterior.os as os_anterior,
							os_anterior.data_nf as data_nf_anterior,
							os_anterior.revenda as revenda_anterior,
							os_anterior.nota_fiscal as nota_fiscal_anterior,
							os_anterior.revenda_cnpj as revenda_cnpj_anterior,
							os_anterior.revenda_nome as revenda_nome_anterior
					FROM tbl_os  
					join tbl_os_extra on tbl_os_extra.os = tbl_os.os
					join tbl_os os_anterior on os_anterior.os = tbl_os_extra.os_reincidente
					WHERE tbl_os.os = $os 
					AND tbl_os.fabrica = $login_fabrica";
		$res_os = pg_query($con, $sql_os);

		if(strlen(pg_last_error($con))>0){
			$msg_erro .= "Falha ao buscar O.S ";
		}else{

			$os_atual = pg_fetch_result($res_os, 0, 'os_atual');
			$nota_fiscal = pg_fetch_result($res_os, 0, 'nota_fiscal');
			$revenda = pg_fetch_result($res_os, 0, 'revenda');
			$data_nf = mostra_data(pg_fetch_result($res_os, 0, 'data_nf'));

			$revenda_nome = pg_fetch_result($res_os, 0, 'revenda_nome');
			$revenda_cnpj = pg_fetch_result($res_os, 0, 'revenda_cnpj');

			$revenda_nome_anterior = pg_fetch_result($res_os, 0, 'revenda_nome_anterior');
			$revenda_cnpj_anterior = pg_fetch_result($res_os, 0, 'revenda_cnpj_anterior');

			$data_nf = mostra_data(pg_fetch_result($res_os, 0, 'data_nf'));

			$os_anterior = pg_fetch_result($res_os, 0, 'os_anterior');
			$nota_fiscal_anterior = pg_fetch_result($res_os, 0, 'nota_fiscal_anterior');
			$revenda_anterior = pg_fetch_result($res_os, 0, 'revenda_anterior');
			$data_nf_anterior = pg_fetch_result($res_os, 0, 'data_nf_anterior');
		}
	}
?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />
		<link href="plugins/font_awesome/css/font-awesome.css" type="text/css" rel="stylesheet" />
		<link href='plugins/select2/select2.css' type='text/css' rel='stylesheet' />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
	</head>
	<body style="background: #ffffff">
	 <?php if(strlen($msg_erro)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-danger"><?=$msg_erro?></div>
        </div>
    <?php }else if(strlen($ok)>0){ ?>
	    <div class="row-fluid">
	        <div class="alert alert-success"><?=$ok?></div>
	    </div>
    <?php }else{  ?>
		<form method="POST" action="">
			<table class="table table-striped table-bordered table-hover table-fixed">
	            <thead  class="titulo_coluna">
	                <tr>
	                    <th colspan="3">Dados O.S Reincidente</th>
	                </tr>
	                <tr>
	                	<th width="100">Dados</th>
	                    <th width="200">O.S Reincidente <?=$os_anterior?></th>
	                    <th width="200">O.S <?=$os_atual?></th>
	                </tr>
	            </thead>
	            <body>
	            	<tr>
	            		<td>Data da Compra</td>
	            		<td><?=mostra_data($data_nf_anterior)?></td>
	            		<td style="color: <?php if(strtotime( mostra_data($data_nf_anterior)) != strtotime($data_nf)){ echo "ff0000"; } ?>" ><?=$data_nf?></td>
	            	</tr>
	            	<tr>
	            		<td>Notal Fiscal</td>
	            		<td><?=$nota_fiscal_anterior?></td>
	            		<td style="color: <?php if( $nota_fiscal != $nota_fiscal_anterior ){ echo "ff0000"; } ?>" ><?=$nota_fiscal?></td>
	            	</tr>
	            	<tr>
	            		<td>Revenda</td>
	            		<td><?=$revenda_cnpj_anterior . " - ". $revenda_nome_anterior?></td>
	            		<td style="color: <?php if( $revenda != $revenda_anterior ){ echo "ff0000"; } ?>" ><?=$revenda_cnpj . " - ". $revenda_nome?></td>
	            	</tr>
	            	<tr>
	            		<td colspan="3" style='text-align: center'>
	            			<input type="hidden" name="data_nf_anterior" value="<?=$data_nf_anterior?>"> 
	            			<input type="hidden" name="revenda_anterior" value="<?=$revenda_anterior?>"> 
	            			<input type="hidden" name="nota_fiscal_anterior" value="<?=$nota_fiscal_anterior?>"> 
	            			<input type="hidden" name="os" value="<?=$os?>"> 
	            			<input type="submit" name="btn_acao" class="btn btn-primary" value="Atualizar">
	            		</td>
	            	</tr>
	            </body>
	        </table>   
	    </form>
    <?php } ?>
	</body>
</html>