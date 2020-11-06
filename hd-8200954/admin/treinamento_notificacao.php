<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include '../ajax_cabecalho.php';
	include 'funcoes.php';
	$admin_privilegios="info_tecnica,call_center";
	include 'autentica_admin.php';
	require_once __DIR__ . '/../class/communicator.class.php';

	if ($_SERVER['HTTP_HOST'] != 'devel.telecontrol.com.br') {
	    $tbl_auditor_black = "tbl_treinamento_notificacao_black";
	}else{
	    $tbl_auditor_black = "tbl_treinamento_notificacao_black_devel";
	}

	//Auditor log
	require __DIR__.'/../classes/api/Client.php';
	use api\Client;

	$treinamento = $_GET["treinamento"];
	$area = $_GET["area"];

	if($_POST['ajax'] == 'sim' and $_POST['acao'] == 'envia_notificacao'){
		$treinamento_id = $_POST['treinamento_id'];
		$notificacao_treinamento = $_POST['notificacao_treinamento'];

		//pegar todos os postos que estão cadastrados neste treinamento e enviar e-mail.
		$sql_p = "SELECT DISTINCT tbl_posto_fabrica.contato_email, tbl_treinamento.titulo
					FROM tbl_treinamento
						JOIN tbl_produto ON ( tbl_treinamento.linha = tbl_produto.linha OR tbl_treinamento.marca = tbl_produto.marca )
							AND tbl_produto.fabrica_i = {$login_fabrica}
						JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
						JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
							AND tbl_posto_fabrica.fabrica = {$login_fabrica}
							AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					WHERE tbl_treinamento.treinamento = {$treinamento_id};";

		if ($login_fabrica == 1) {
			$sql_posto = "SELECT tbl_treinamento_posto.posto 
                                FROM tbl_treinamento 
                                JOIN tbl_treinamento_posto ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento 
                                WHERE tbl_treinamento.treinamento = $treinamento_id
                                AND tbl_treinamento.fabrica = $login_fabrica
                                AND tbl_treinamento_posto.ativo IS TRUE";
                                
			$res_posto = pg_query($con, $sql_posto);
			if (pg_num_rows($res_posto) > 0) {
				for ($p=0; $p < pg_num_rows($res_posto); $p++) { 
					$postos[] = pg_fetch_result($res_posto, $p, 'posto'); 
				}
				$postos = implode(',', $postos);
			}

			if (empty($postos)) {
				$consulta_marca = "tbl_treinamento.parametros_adicionais->'marca' ? 'tbl_produto.marca'";
				$consulta_linha = "tbl_treinamento.parametros_adicionais->'linha' ? 'tbl_produto.linha'";
			
				$sql_add = "SELECT parametros_adicionais->'marca' AS marca,
								   parametros_adicionais->'linha' AS linha 
							FROM tbl_treinamento 
							WHERE treinamento = {$treinamento_id} 
							AND fabrica = {$login_fabrica}";
							
				$res_add = pg_query($con, $sql_add);
				if (pg_num_rows($res_add) > 0) {
					$marca_add = json_decode(pg_fetch_result($res_add, 0, 'marca'));
					$linha_add = json_decode(pg_fetch_result($res_add, 0, 'linha'));
					if (isset($marca_add) && !empty($marca_add)) {
	        			$consulta_marca_arr = [];
	        			foreach ($marca_add as $pam) {
							$consulta_marca_arr[] = "(tbl_treinamento.parametros_adicionais->'marca' ? '{$pam}')";
						}
	       	 			$consulta_marca = ' ((' . implode(' OR ', $consulta_marca_arr) . '))';	 			
	        		}

	        		if (isset($linha_add) && !empty($linha_add)) {
	        			$consulta_linha_arr = [];
	        			foreach ($linha_add as $pam) {
							$consulta_linha_arr[] = "(tbl_treinamento.parametros_adicionais->'linha' ? '{$pam}')";
						}
	       	 			$consulta_linha = ' ((' . implode(' OR ', $consulta_linha_arr) . '))';    	 			
	        		}

				}

				$sql_p = "SELECT DISTINCT tbl_posto_fabrica.contato_email, tbl_treinamento.titulo
					  FROM tbl_treinamento
					  JOIN tbl_produto ON ( $consulta_linha OR $consulta_marca)
					  AND tbl_produto.fabrica_i = {$login_fabrica}
					  JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
					  JOIN tbl_posto_fabrica ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
					  AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					  AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					  WHERE tbl_treinamento.treinamento = {$treinamento_id}";
			} else {
				$sql_p = "SELECT DISTINCT tbl_posto_fabrica.contato_email, tbl_treinamento.titulo
					  FROM tbl_treinamento
					  JOIN tbl_treinamento_posto ON tbl_treinamento.treinamento = tbl_treinamento_posto.treinamento
					  JOIN tbl_posto ON tbl_treinamento_posto.posto = tbl_posto.posto
					  JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
					  AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					  AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					  AND tbl_posto_fabrica.posto in ($postos)
					  WHERE tbl_treinamento.treinamento = {$treinamento_id}";
			}		
		}
		$res_p = pg_query($con,$sql_p);

		if (pg_num_rows($res_p) > 0) {
			$auditor_antes = array();
			$auditor_depois = array('notificacao_treinamento' => $notificacao_treinamento ,'admin'=> $login_admin, 'data' => date("Y-m-d H:i:s") );
			$nome_servidor = $_SERVER['SERVER_NAME'];
	        $nome_uri = $_SERVER['REQUEST_URI'];
	        $nome_url = $nome_servidor.$nome_uri;

			auditorLog($treinamento_id,$auditor_antes,$auditor_depois, $tbl_auditor_black, $nome_url, 'update');

			$mailer = new \TcComm("smtp@posvenda");
			for ($i=0; $i < pg_num_rows($res_p); $i++) {
				
				$titulo_treinamento = "Titulo: <strong>".pg_fetch_result($res_p, $i, titulo)."</strong><br><br>\n\nInformações adicionais: ";

				$corpo_email = $titulo_treinamento . $notificacao_treinamento;
				if ($_SERVER['HTTP_HOST'] != 'devel.telecontrol.com.br') {
				    $dest = pg_fetch_result($res_p, $i, contato_email);
				}else{
				    $dest = "joao.junior@telecontrol.com.br";
				    $mailer->sendMail( $dest, "Notificação do Treinamento Stanley Black&Decker", $corpo_email, "noreply@telecontrol.com.br" );
				}
				$mailer->sendMail( $dest, "Notificação do Treinamento Stanley Black&Decker", $corpo_email, "noreply@telecontrol.com.br" );
			}
		} else {
			unset($auditor_antes);
        	unset($auditor_depois);
			echo json_encode(array("error" => "error"));
			exit;
		}

        unset($auditor_antes);
        unset($auditor_depois);
        echo json_encode(array("success" => "ok"));
        exit;
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

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>
		<script>

			function envia_notificacao(){

				var treinamento_id = $("#treinamento").val();
				var notificacao_treinamento = $("#notificacao_treinamento").val();
				if (notificacao_treinamento != "") {
					$(".cadastra-notificao").text('Aguarde...');
					$.ajax({
			            method: "POST",
			            url: "treinamento_notificacao.php",
			            data: { ajax: 'sim', 
			            		acao: 'envia_notificacao', 
			            		'treinamento_id': treinamento_id,
			            		'notificacao_treinamento' : notificacao_treinamento
			            	},
			        }).done(function(data) {
			            data = JSON.parse(data);
			            if (data.success !== undefined) {
			            	$("#msg_envia_notificacao").html('<div class="alert alert-success" ><h4>Notificação enviada!</h4></div>');
			            }else if (data.error !== undefined) {
			                $("#msg_envia_notificacao").html('<div class="alert alert-error" ><h4>'+data.error+'</h4></div>');
			            }
			            $(".cadastra-notificao").text('Enviar Notificação');
		        	});
		    	}else{
		    		alert('Informe uma Notificação !');
		    	}
			}
		</script>
	</head>
	<body>
		<?php
		if ($area != 'posto') { ?>
			<div id="msg_envia_notificacao">
		        
		    </div>
			<div class="row">
		    	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
			</div>
			<form name="frm_treinamento_notificacao" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
			    <div class="container-fluid form_tc" style="overflow: auto;">
					<input type="hidden" id="treinamento" name="treinamento" value="<?=$treinamento?>">
					<div class="titulo_tabela">Notificação do Treinamento</div>
					<br>
					<div class='row-fluid'>
						<div class='span2'></div>
						<div class="span8">
							<div class="control-group" id="descricao_campo">
								<h5 class='asteristico'>*</h5>
								<label class='control-label'>Notificação</label>
								<textarea style="resize: none" name="notificacao_treinamento" rows="3" cols="30" id="notificacao_treinamento" class="span12" ></textarea>
							</div>
						</div>
						<div class='span2'></div>
					</div>
					<br />
					<p class="tac">
						<button type='button' class='btn cadastra-notificao' onclick="javascript:envia_notificacao()">Enviar Notificação</button>
					</p>
					<br />
				</div>
			</form>	
		<?php
		}	
        $client = Client::makeTelecontrolClient("auditor","auditor");
        $client->urlParams = array(
            "aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
            "table" =>"$tbl_auditor_black",
            "primaryKey" => $login_fabrica."*".$treinamento,
            "limit" => "50"
        );

        try{
            $res = $client->get();
            if(count($res)){ ?>
	            <table class='table table-striped table-bordered table-hover table-fixed'>
		            <thead>
		                <tr class='titulo_coluna'>
		                    <th>Admin</th>
		                    <th>Data</th>
		                    <th>Notificação</th>
		                </tr>
		            </thead>
		            <tbody>
		        	<?php
	                foreach ($res as $key => $value) { 
	                	$id_admin = $value['data']['content']['depois']['admin'];
	                	$data_envio = $value['data']['content']['depois']['data'];

	                	$data_envio = mostra_data_hora($data_envio);

	                	$sql_adm = "SELECT nome_completo FROM tbl_admin WHERE admin = {$id_admin};";
	                	$res_adm = pg_query($con,$sql_adm);

	                	if (pg_num_rows($res_adm)) {
	                		$nome_admin = pg_fetch_result($res_adm, 0, 0);
	                	}
	                	?>
	                	<tr>
		                    <td><?=$nome_admin?></td>
		                    <td><?=$data_envio?></td>
		                    <td><?=utf8_decode($value['data']['content']['depois']['notificacao_treinamento'])?></td>
		                </tr>
	                <?php
	                }
	                ?>
	                </tbody>
	            </table>
            <?php
            }else{
                $error = "Nenhum log encontrado";
            }
        }catch(Exception $ex){
            $error = $ex->getMessage(); 
            ?>
            <div id="msg_envia_notificacao">
		        <div class="alert alert-warning" ><h4><?=$error?>!</h4></div>
		    </div>
		<?php
        } ?>
	</body>
</html>
