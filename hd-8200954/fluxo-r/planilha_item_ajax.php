<?
if ($acao == "gravar" AND $ajax == "sim") {

	$produto_rg_item = $_POST["produto_rg_item"];
	$produto         = $_POST["produto"];
	$os              = $_POST["os"];
	$peca            = $_POST["peca"];
	$referencia      = $_POST["referencia"];
	$descricao       = $_POST["descricao"];
	$tipo            = $_POST["tipo"];

	if(strlen($peca) == 0)   $msg_erro = "Digite a peça a ser inserida na OS<br>";
	if(strlen($tipo)== 0)    $msg_erro = "Escolha se a peça é de estoque está sendo aguardada<br>";

	if (strlen($defeito) == 0)       $defeito       = "null";
	if (strlen($servico) == 0)       $servico       = "null";
	if (strlen($causa_defeito) == 0) $causa_defeito = "null";
	if (strlen($posicao) == 0)       $posicao       = "null";
	if (strlen($qtde) == 0)          $qtde          = "1";

	//--== Peça de Estoque ==--
	if($tipo=="estoque"){
		
	}

	//--== Peça sendo aguardada - fazer pedido de peça ==--
	if($tipo=="aguardando"){
		$sql = "SELECT servico_realizado 
				FROM  tbl_servico_realizado
				WHERE fabrica = $fabrica
				AND   troca_de_peca IS TRUE";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$servico = pg_result($res,0,0);
		}
	}

	$sql = "
		SELECT tbl_peca.peca
		FROM   tbl_peca
		WHERE  tbl_peca.peca            = $peca
		AND    tbl_peca.fabrica         = $fabrica
		AND    tbl_peca.produto_acabado IS NOT TRUE;";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) $msg_erro .= "Peça $referencia - $descricao não cadastrada";

	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen($os_item) == 0){

			$sql = "INSERT INTO tbl_os_produto (
						os       ,
						produto  ,
						serie
					)VALUES(
						$os      ,
						$produto ,
						$serie
				);";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
			$os_produto  = @pg_result ($res,0,0);
			if (strlen ($msg_erro) == 0){
				$sql = "INSERT INTO tbl_os_item (
							os_produto        ,
							posicao           ,
							peca              ,
							qtde              ,
							defeito           ,
							causa_defeito     ,
							servico_realizado
						)VALUES(
							$os_produto      ,
							$posicao         ,
							$peca            ,
							$qtde            ,
							$defeito         ,
							$causa_defeito   ,
							$servico
						);";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}else{
			$sql = "UPDATE tbl_os_item SET
					os_produto        = $os_produto    ,
					posicao           = $posicao       ,
					peca              = $peca          ,
					qtde              = $qtde          ,
					defeito           = $defeito       ,
					causa_defeito     = $causa_defeito,
					servico_realizado = $servico
				WHERE os_item = $os_item;";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso|$tecnico";
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro|$msg_erro_linha";
	}
	exit;
}
?>