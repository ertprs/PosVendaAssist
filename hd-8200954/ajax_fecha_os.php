<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if ($_GET['fecharnovo']=='sim') {

		if ($login_fabrica ==20) {
			$data_fechamento = $_GET['data_fechamento'];

			$xxdata_fechamento_hora = explode(" ",$data_fechamento);
			$xdata_fechamento_hora  = $xxdata_fechamento_hora[0];
			$xdata_fechamento_hora  = explode("/",$xdata_fechamento_hora);
			$xdata_fechamento_hora  = $xdata_fechamento_hora[2] . "-" . $xdata_fechamento_hora[1] . "-" . $xdata_fechamento_hora[0];
			$data_fechamento_hora   = $xdata_fechamento_hora . " " . $xxdata_fechamento_hora[1];

			$os = $_GET['os'];

			$dia = substr($data_fechamento,0,2);
			$mes = substr($data_fechamento,3,2);
			$ano = substr($data_fechamento,6,4);
			$data_fechamento = $ano.'-'.$mes.'-'.$dia;


			$sql = "SELECT '$data_fechamento_hora' > CURRENT_TIMESTAMP AS data_maior";
			#echo nl2br($sql);
			$res = @pg_query($con,$sql);
			$msg_erro   = pg_errormessage($con);

			if(strpos($msg_erro,"out of range")>0){
				$msg_erro = "A hora está incorreta";
			}

			if(strlen($msg_erro)==0) $data_maior = pg_result($res,0,data_maior);

			if($data_maior=="t"){
				$msg_erro = "A data do fechamento não pode ser maior que a data atual";
			}

			$msg_erro = "";
			$res = pg_query ($con,"BEGIN TRANSACTION");
			if ($data_fechamento <= date('Y-m-d') AND strlen($msg_erro)==0) {
				$sql = "UPDATE tbl_os set data_fechamento      = '$data_fechamento',
										  data_hora_fechamento = '$data_fechamento_hora'
								WHERE
								fabrica = $login_fabrica
								AND os = $os";
				#echo nl2br($sql);
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con) ;
			}
			else
				$msg_erro = 'erro;Não foi possivel fechar OS: Data digitada maior que atual.;';

			if (strlen ($msg_erro) == 0) {

				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con) ;

			}

			if (empty($msg_erro)) {

				$sql = "SELECT tipo_atendimento from tbl_os where os=$os and fabrica=$login_fabrica";
				$res = pg_query($con,$sql);

				$tipo_atendimento = (pg_num_rows($res)>0) ? pg_fetch_result($res, 0, 0) : '';

				if ($tipo_atendimento == 12) {

					$sql_hora_abertura = "SELECT data_hora_abertura FROM tbl_os where os=$os";
					$res_hora_abertura = pg_query($con,$sql_hora_abertura);

					if (pg_num_rows($res_hora_abertura)>0) {
						$data_hora_abertura = pg_fetch_result($res_hora_abertura, 0, 0);

						if(strlen($data_hora_abertura) > 0){
							$sql_os_explodida = "SELECT os FROM tbl_os where data_hora_abertura='$data_hora_abertura' and posto = $login_posto and fabrica = $login_fabrica and tipo_atendimento = $tipo_atendimento ";
							$res_os_explodida = pg_query($con,$sql_os_explodida);

							if (pg_num_rows($res_os_explodida)>0) {

								for ($u=0; $u < pg_num_rows($res_os_explodida); $u++) {

									$os_explodida = pg_fetch_result($res_os_explodida, $u, 'os');
									$sql = "UPDATE tbl_os set 	data_fechamento      = '$data_fechamento',
												  				data_hora_fechamento = '$data_fechamento_hora'
											WHERE fabrica = $login_fabrica
											AND   os      = $os_explodida";

									$res = @pg_query($con,$sql);
									if (pg_last_error($con)) {

										$msg_erro = pg_last_error($con);

									}

									if (strlen ($msg_erro) == 0) {

										$sql = "SELECT fn_finaliza_os($os_explodida, $login_fabrica)";
										$res = @pg_query($con, $sql);
										if (pg_last_error($con)) {

											$msg_erro = pg_last_error($con);

										}

									}

								}

							}

						}

					}

				}

			}

			if (strlen ($msg_erro) == 0) {
				$res = @pg_query ($con,"COMMIT TRANSACTION");
				echo "ok;XX$os";
			}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			$erro = explode('"',$msg_erro);
			echo "erro;$erro[3] ";
			}
		}
	}

flush();

?>
