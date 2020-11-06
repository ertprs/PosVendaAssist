<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$enter = chr(13).chr(10);

$fabrica = trim($_POST['fabrica']);
$cnpj    = trim($_POST['cnpj']);


if (strlen($fabrica) > 0 AND strlen($cnpj) > 0) {
	$sql = "SELECT tbl_posto_fabrica.posto
			FROM   tbl_posto_fabrica
			JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE  tbl_posto.cnpj                   = '$cnpj'
			AND    tbl_posto_fabrica.fabrica        = '$fabrica'
			AND    tbl_posto_fabrica.credenciamento = 'CREDENCIADO'";
	$res  = @pg_exec ($con,$sql);
	$erro = pg_errormessage($con);
	
	if (strlen($erro) > 0) {
		$msg_erro  = "Foi detectado o seguinte erro:$enter";
		$msg_erro .= "$erro$enter";
	}
	
	if (strlen($msg_erro) == 0) {
		if (pg_numrows($res) == 0) {
			$sql = "SELECT * FROM
						(
							SELECT trim(tbl_posto.nome) AS nome_posto
							FROM   tbl_posto
							WHERE  tbl_posto.cnpj ='$cnpj'
						) AS a,
						(
							SELECT upper(trim(tbl_fabrica.nome)) AS nome_fabrica
							FROM   tbl_fabrica
							WHERE  tbl_fabrica.fabrica = '$fabrica'
						) AS b;";
			$res  = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if (strlen($msg_erro) == 0) {
				if (pg_numrows($res) > 0) {
					$nome_posto   = trim(pg_result($res,0,nome_posto));
					$nome_fabrica = trim(pg_result($res,0,nome_fabrica));
					
					$flag = "t";
					$msg_erro  = "Foi detectado o seguinte erro:$enter";
					$msg_erro .= "Posto $nome_posto não CREDENCIADO para o fabricante $nome_fabrica !!$enter";
				}else{
					$flag = "f";
					$msg_erro  = "Foi detectado o seguinte erro:$enter";
					$msg_erro .= "Posto informado não CREDENCIADO para este fabricante !!$enter";
				}
			}
		}else{
			$posto = trim(pg_result($res,0,posto));
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$pedido_offline = trim($_POST['pedido']);
		if (strlen($pedido_offline) == 0) {
			$aux_pedido_offline = "null";
		}else{
			$aux_pedido_offline = "'". $pedido_offline ."'";
		}
		
		$linha = trim($_POST['linha']);
		if (strlen($linha) == 0) {
			$aux_linha = "null";
		}else{
			$aux_linha = "'". $linha ."'";
		}
		
		$transportadora = trim($_POST['transportadora']);
		if (strlen($transportadora) == 0) {
			$aux_transportadora = "null";
		}else{
			$aux_transportadora = "'". $transportadora ."'";
		}
		
		$pedido_cliente = trim($_POST['pedido_cliente']);
		if (strlen($pedido_cliente) == 0) {
			$aux_pedido_cliente = "null";
		}else{
			$aux_pedido_cliente = "'". $pedido_cliente ."'";
		}
		
		$data_digitacao = trim($_POST['data_digitacao']);
		if (strlen($data_digitacao) == 0) {
			$aux_data_digitacao = "null";
		}else{
			$aux_data_digitacao = "'". $data_digitacao ."'";
		}
		
		$tipo_pedido = trim($_POST['tipo_pedido']);
		if (strlen($tipo_pedido) == 0) {
			$aux_tipo_pedido = "null";
		}else{
			$aux_tipo_pedido = "'". $tipo_pedido ."'";
		}
		
		$condicao = trim($_POST['condicao']);
		if (strlen($condicao) == 0) {
			$aux_condicao = "null";
		}else{
			$aux_condicao = $condicao;
		}
		
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($pedido_offline) > 0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
						posto               ,
						fabrica             ,
						condicao            ,
						pedido_cliente      ,
						pedido_offline      ,
						transportadora      ,
						linha               ,
						tipo_pedido
					) VALUES (
						$posto              ,
						$fabrica            ,
						$aux_condicao       ,
						$aux_pedido_cliente ,
						$aux_pedido_offline ,
						$aux_transportadora ,
						$aux_linha          ,
						$aux_tipo_pedido
					);";
			$res = @pg_exec ($con,$sql);
			$erro = pg_errormessage($con);
			
			if (strlen($erro) > 0) {
				$msg_erro  = "Foi detectado o seguinte erro:$enter";
				$msg_erro .= "$erro$enter";
			}
			
			if (strlen ($msg_erro) == 0) {
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
				$erro = pg_errormessage($con);
				
				if (strlen($erro) > 0) {
					$msg_erro  = "Foi detectado o seguinte erro:$enter";
					$msg_erro .= "$erro$enter";
				}else{
					$pedido_web = pg_result ($res,0,0);
				}
			}
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$qtde_item = trim($_POST['qtde_item']);
		
		$nacional  = 0;
		$importado = 0;
		
		for ($i = 1 ; $i <= $qtde_item ; $i++) {
			$peca  = $_POST['peca_'  . $i];
			$qtde  = $_POST['qtde_'  . $i];
			
			if (strlen ($peca) > 0 AND strlen($qtde) > 0) {
				$sql = "SELECT  tbl_peca.origem
						FROM    tbl_peca
						WHERE   tbl_peca.peca    = $peca
						AND     tbl_peca.fabrica = $fabrica;";
				$res = @pg_exec ($con,$sql);
				$erro = pg_errormessage($con);
				
				if (strlen($erro) > 0) {
					$msg_erro .= "Foi detectado o seguinte erro:$enter";
					$msg_erro .= "$erro (--$i)$enter";
				}
				
				if (pg_numrows($res) == 0) {
					if ($flag == "t") {
						$msg_erro .= "Foi detectado o seguinte erro:$enter";
						$msg_erro .= "Peça informada não encontrada o fabricante $nome_fabrica !! (--$i)$enter";
					}else{
						$msg_erro .= "Foi detectado o seguinte erro:$enter";
						$msg_erro .= "Peça informada não encontrada para este fabricante !! (--$i)$enter";
					}
				}else{
					$origem = trim(pg_result ($res,0,origem));
					
					if ($fabrica <> 1){
						if ($origem == "NAC" OR $origem == "1") $nacional  = $nacional  + 1;
						if ($origem == "IMP" OR $origem == "2") $importado = $importado + 1;
					}
					if ($nacional > 0 AND $importado > 0 AND ($fabrica <> 3)) {
						$msg_erro .= "Foi detectado o seguinte erro:$enter";
						$msg_erro .= "Não é permitido realizar um pedido com peça Nacional e Importada !! (--$i)$enter";
					}
				}
				
				if (strlen($msg_erro) == 0) {
					$sql = "INSERT INTO tbl_pedido_item (
								pedido     ,
								peca       ,
								qtde
							) VALUES (
								$pedido_web,
								$peca      ,
								$qtde
							)";
					$res = @pg_exec ($con,$sql);
					$erro = pg_errormessage($con);
					
					if (strlen($erro) > 0) {
						$msg_erro .= "Foi detectado o seguinte erro:$enter";
						$msg_erro .= "$erro (--$i)$enter";
					}
				}
				
				if (strlen($msg_erro) == 0) {
					$sql = "SELECT fn_valida_pedido_item ($pedido_web,$peca,$fabrica)";
					$res = @pg_exec ($con,$sql);
					$erro = pg_errormessage($con);
					
					if (strlen($erro) > 0) {
						$msg_erro .= "Foi detectado o seguinte erro:$enter";
						$msg_erro .= "$erro (--$i)";
					}
				}
			}else{
				$msg_erro .= "Foi detectado o seguinte erro:$enter";
				$msg_erro .= "É necessário informar peça + quantidade para um pedido !! (--$i) ($peca) ($qtde) $enter";
			}
		}
	}
	
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido_web,$fabrica)";
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);
		
		if (strlen($erro) > 0) {
			$msg_erro .= "Foi detectado o seguinte erro:$enter";
			$msg_erro .= "$erro$enter";
		}
	}
	
	if (strlen($msg_erro) == 0 and $fabrica == 1) {
		$sql = "SELECT fn_pedido_suframa ($pedido_web,$fabrica)";
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);
		
		if (strlen($erro) > 0) {
			$msg_erro .= "Foi detectado o seguinte erro:$enter";
			$msg_erro .= "$erro$enter";
		}
	}
	
	if (strlen ($msg_erro) > 0) {
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "<!--OFFLINE-I-->$msg_erro<!--OFFLINE-F-->";
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "<!--OFFLINE-I-->PEDIDO WEB-->$pedido_web<!--OFFLINE-F-->";
	}
}
?>