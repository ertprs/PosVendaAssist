<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$obs = $_GET['motivo'];
$os = $_GET['os'];

	$sql="SELECT status_os
			FROM tbl_os_status
			WHERE status_os IN (110,111,112)
			AND os = $os
			ORDER BY data DESC
			LIMIT 1;";
	$res = pg_query($con,$sql);
	$status_os = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,0) : 0;

	$sql="select tbl_os_item.pedido_item
			from   tbl_os_item
			join   tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
			join   tbl_os         on tbl_os.os                 = tbl_os_produto.os
			where  tbl_os.os      = $os
			and    tbl_os.fabrica = $login_fabrica
			and    tbl_os_item.pedido notnull;";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0 and $status_os <> 111){
		$pedido_item = pg_fetch_result ($res,0,0);
		if(strlen($pedido_item) > 0) {
			$sqls ="select tbl_pedido_item.qtde
					from tbl_pedido_item
					where tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada > 0
					and tbl_pedido_item.pedido_item = $pedido_item;";
			$ress = pg_query($con,$sqls);
			if(pg_num_rows($ress) > 0){
				$msg_erro = "Ordem de Serviço já possui pedido lançado, não pode ser excluída";
			}
		}else{
			$msg_erro = "Ordem de Serviço já possui pedido lançado, não pode ser excluída";
		}
	}

	$sql = "select extrato
				from   tbl_os_extra
				join   tbl_os on tbl_os.os = tbl_os_extra.os
				where  tbl_os.os      = $os
				and    tbl_os.fabrica = $login_fabrica
				and    tbl_os_extra.extrato is not null";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$msg_erro = "Ordem de Serviço já consta em extrato, não pode ser excluída";
	}

	if(strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");
		
        if ($login_fabrica == 19) {
        	$sql = "SELECT  tbl_os.os,
                    tbl_os.os_numero,
                    tbl_os.os_sequencia,
                    tbl_tipo_atendimento.codigo as tipo_atendimento, 
	                    tbl_produto.linha
	            FROM tbl_os
	            JOIN tbl_produto
	                    ON tbl_produto.produto = tbl_os.produto
	            JOIN tbl_tipo_atendimento 
                    ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
	            WHERE os = '$os'";

    		$resSql = pg_query($con,$sql);
            $os_numero = pg_fetch_result($resSql, 0, os_numero);
            $os_sequencia = pg_fetch_result($resSql, 0, os_sequencia);
            $tipo_atendimento = pg_fetch_result($resSql, 0, tipo_atendimento);
            $linha = pg_fetch_result($resSql, 0, linha);

            if ($os_sequencia > 0 && $linha = 928 && in_array($tipo_atendimento, [15,16])){
                $sql_instalacao = "SELECT os FROM tbl_os WHERE os_numero = $os_numero and tipo_atendimento = 235 AND fabrica = $login_fabrica AND data_abertura = data_fechamento";
                $instalacao = pg_query ($con, $sql_instalacao);
                while ($fetch = pg_fetch_object($instalacao)) {
                	$sql = "SELECT fn_os_excluida($fetch->os,$login_fabrica,null);";
					$res = @pg_query ($con,$sql);
					$msg_erro = pg_errormessage($con);
                }
            } 
        } 

		$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao)
					VALUES ($os,15,current_timestamp,'OS Excluída pelo posto, motivo: $obs')";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if(strlen($msg_erro) == 0) {
			if($login_fabrica==50){//HD 37007 5/9/2008
				$sql = "UPDATE tbl_os SET excluida = 't' WHERE os = $os AND fabrica = $login_fabrica";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);

				#158147 Paulo/Waldir desmarcar se for reincidente
				$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
				$res = pg_query($con, $sql);
				$msg_erro = pg_errormessage($con);
			}else{
				$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
            if(in_array($login_fabrica,array(151,152,180,181,182))) {
                $sql = "UPDATE tbl_os_excluida set motivo_exclusao = '$obs' where os = $os and fabrica =$login_fabrica";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
            }

			if(strlen($msg_erro) == 0) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
	if ($login_fabrica == 24) {
            if (isset($_SESSION["os_pendente"])) {
                if (array_key_exists($os,$_SESSION["os_pendente"]["os_aberta_15"])) {
                    unset($_SESSION["os_pendente"]["os_aberta_15"][$os]);
                    $sql_f = "UPDATE tbl_os SET off_line_reservada = FALSE
                                WHERE os = $os
                                AND fabrica = $login_fabrica;";
                    $res_f = pg_query($con,$sql_f);
                    $msg_erro .= pg_errormessage($con);
                }
                if (array_key_exists($os,$_SESSION["os_pendente"]["os_lacamento_peca_15"])) {
                    unset($_SESSION["os_pendente"]["os_lacamento_peca_15"][$os]);
                    $sql_f = "UPDATE tbl_os SET off_line_reservada = FALSE
                                WHERE os = $os
                                AND fabrica = $login_fabrica;";
                    $res_f = pg_query($con,$sql_f);
                    $msg_erro .= pg_errormessage($con);
                }
                if (array_key_exists($os,$_SESSION["os_pendente"]["os_aberta_25"])) {
                    unset($_SESSION["os_pendente"]["os_aberta_25"][$os]);
                    $sql_f = "UPDATE tbl_os SET off_line_reservada = FALSE
                                WHERE os = $os
                                AND fabrica = $login_fabrica;";
                    $res_f = pg_query($con,$sql_f);
                    $msg_erro .= pg_errormessage($con);
                }
            }
        }

	echo (strlen($msg_erro) > 0) ? $msg_erro : "ok";
	exit;
?>
