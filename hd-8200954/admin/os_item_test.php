<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

if($login_fabrica == 5){
	$os=$_GET['os'];
	header ("Location: os_item_new_mondial.php?os=$os&reabrir=$reabrir");
	exit;
}

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($_GET['os_item']) > 0) $item_os = trim($_GET['os_item']);
if (strlen($_GET['liberar']) > 0) $liberar = $_GET['liberar'];

if (strlen($_GET['imprimir']) > 0) $imprimir = $_GET['imprimir'];

$troca_faturada = trim($_POST['troca_faturada']);

if (strlen($item_os) > 0) {
	$sql = "SELECT *
			FROM   tbl_os_item
			WHERE  tbl_os_item.os_item = $item_os;";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$os_pedido  = trim(pg_result($res,0,pedido));

		$sql      = "SELECT fn_exclui_item_os($os_pedido, $item_os, $login_fabrica)";
		$res      = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			header ("Location: $PHP_SELF?os=$os");
			exit;
		}
	}
}

if ($login_fabrica == 7){
	header ("Location: os_filizola_valores.php?os=$os&imprimir=$imprimir");
	exit;
}

if(strlen($os)>0 AND strlen($defeito_reclamado)>0 AND $login_fabrica==19){
	$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql) ;
}


if (strlen($liberar) > 0) {
	$sql = "UPDATE tbl_os_item SET
				liberacao_pedido      = 't'              ,
				data_liberacao_pedido = current_timestamp,
				admin                 = $login_admin     ,
				obs                   = '### PEÇA INFERIOR A 30% DO VALOR DE MÃO-DE-OBRA. LIBERADA PELO ADMIN. ###'
			where tbl_os_item.os_produto = tbl_os_produto.os_produto
			and   tbl_os_produto.os      = tbl_os.os
			and   tbl_os.os              = $os
			and   tbl_os.fabrica         = $login_fabrica
			and   tbl_os_item.os_item    = $liberar
			and   tbl_os_item.admin      is null;";
	$res = pg_query ($con,$sql);

	header ("Location: $PHP_SELF?os=$os");
	exit;
}

if (strlen($os) > 0) {
	$sql = "SELECT tbl_os.fabrica,tipo_os FROM tbl_os WHERE tbl_os.os = $os";
	$res = @pg_query ($con,$sql);

	$tipo_os = pg_fetch_result($res,0,tipo_os);
	if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
		header ("Location: os_cadastro.php");
		exit;
	}
}


if ($login_fabrica == 1) {
	$sql =	"SELECT tipo_os_cortesia, tipo_os, os_numero
			FROM  tbl_os
			WHERE fabrica = $login_fabrica
			AND   os = $os;";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 1) {
		$tipo_os_cortesia = pg_fetch_result($res,0,tipo_os_cortesia);
		$tipo_os = pg_fetch_result($res,0,tipo_os);
		$os_numero= pg_fetch_result($res,0,os_numero);
		if ($tipo_os_cortesia == "Compressor" OR $tipo_os==10) {
			$compressor='t';
		}
		/*PARA OS GEO, USA A OS REVENDA NA GRAVAÇÃO DE OS VISITA*/
		if($tipo_os == 13){
			$sql_aux_os = " os_revenda ";
			$aux_os = $os_numero;
		}else{
			$sql_aux_os = " os ";
			$aux_os = $os;
		}
	}
	#HD 11906
	$sql = "SELECT os FROM tbl_os_troca WHERE os=$os";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		header ("Location: os_press.php?os=$os");
		exit;
	}
	$qtde_visita=4;
}

/*IGOR HD: 44202 - 16/10/2008*/
if ($login_fabrica == 3){
	$xos = $_GET['os'];
	if (strlen($xos) == 0) {
		$xos = $_POST['os'];
	}
	if (strlen($xos) > 0) {
		$status_os = "";
		$sql = "SELECT status_os
				FROM  tbl_os_status
				WHERE os=$xos
				AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
				ORDER BY data DESC LIMIT 1";
		$res_intervencao = pg_query($con, $sql);
		$msg_erro        = pg_errormessage($con);

		if (pg_num_rows ($res_intervencao) > 0 ){
			$status_os = pg_result($res_intervencao,0,status_os);
			#if ($status_os=="120" OR $status_os=="122" OR $status_os=="126"){ HD 56464
			if ($status_os == "122" || $status_os == "141") {
				header ("Location: os_press.php?os=$xos");
				exit;
			}
		}
	}
}

include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "gravar") {
	//if (strlen ($defeito_constatado) == 0) $defeito_constatado = "null";
	//if (strlen ($defeito_reclamado)  == 0) $defeito_reclamado  = "null";
		$data_fechamento = $_POST['data_fechamento'];
		if (strlen($data_fechamento) > 0){
			$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
			if($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro = "Data de fechamento maior que a data de hoje.";
		}
	$res = pg_query ($con,"BEGIN TRANSACTION");
	//hd17966
	if($login_fabrica==45){
		$sql = "SELECT finalizada,data_fechamento
				FROM   tbl_os
				JOIN   tbl_os_extra USING(os)
				WHERE  fabrica = $login_fabrica
				AND    os      = $os
				AND    extrato         IS     NULL
				AND    finalizada      IS NOT NULL
				AND    data_fechamento IS NOT NULL";
		$res = pg_query ($con,$sql);
		if(pg_num_rows($res)>0){
			$voltar_finalizada = pg_result($res,0,0);
			$voltar_fechamento = pg_result($res,0,1);
			$sql = "UPDATE tbl_os SET data_fechamento = NULL , finalizada = NULL
					WHERE os      = $os
					AND   fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
		}
	}

	$sql = "SELECT tbl_os.posto
			FROM   tbl_os
			WHERE  tbl_os.os      = $os
			AND    tbl_os.fabrica = $login_fabrica;";
	$res = pg_query ($con,$sql);
	$posto = pg_result ($res,0,0);

	if ($login_fabrica == 1) {
		$x_produto_type = $_POST['produto_type'];
		if (strlen ($x_produto_type) > 0) $x_produto_type = "'" . $x_produto_type . "'";
		else                              $x_produto_type = "null";

		$sql = "UPDATE tbl_os SET type = $x_produto_type
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_query ($con,$sql);
	}

	$defeito_reclamado_descricao = trim($_POST['defeito_reclamado_descricao']);

	if(strlen($os)>0 AND strlen($defeito_reclamado_descricao)>0 AND $login_fabrica==51){
		$sql = "UPDATE tbl_os SET defeito_reclamado_descricao = '$defeito_reclamado_descricao' WHERE os = $os AND fabrica = $login_fabrica AND defeito_reclamado_descricao IS NULL";
		$res = pg_query ($con,$sql) ;
		$msg_erro = pg_errormessage($con);
	}

	$defeito_constatado = $_POST ['defeito_constatado'];
	if (strlen ($defeito_constatado) > 0) {
		//hd 17863 Rotina de vários defeitos para uma única OS.
		if($login_fabrica==30 or $login_fabrica == 59 or $login_fabrica == 2){
			$numero_vezes = 100;
			$array_integridade = array();
			for ($i=0;$i<$numero_vezes;$i++){
				$int_constatado = trim($_POST["integridade_defeito_constatado_$i"]);
				$int_solucao    = trim($_POST["integridade_solucao_$i"]);
				echo $int_constatado." - ".$int_solucao."<br>";
				if (!isset($_POST["integridade_defeito_constatado_$i"])) continue;
				if (strlen($int_constatado)==0) continue;

				$aux_defeito_constatado = $int_constatado;
				$aux_solucao            = $int_solucao;

				array_push($array_integridade,$aux_defeito_constatado);

				$sql = "SELECT defeito_constatado_reclamado
						FROM tbl_os_defeito_reclamado_constatado
						WHERE os=$os
						AND   defeito_constatado = $aux_defeito_constatado";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(@pg_num_rows($res)==0){
					if($login_fabrica == 30) {
						$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
									os,
									defeito_constatado
								)VALUES(
									$os,
									$aux_defeito_constatado
								)	";
					}else{
						$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
									os,
									defeito_constatado,
									solucao
								)VALUES(
									$os,
									$aux_defeito_constatado,
									$aux_solucao
								)";
					}
					echo $sql;
					$res = pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}

			$lista_defeitos = implode($array_integridade,",");
			$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
					WHERE os = $os
					AND   defeito_constatado NOT IN ($lista_defeitos) ";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			//o defeito constatado recebe o primeiro defeito constatado.
			$defeito_constatado = $aux_defeito_constatado;
		}

		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  os    = $os
				AND    posto = $posto;";
		$res = @pg_query ($con,$sql);

		// aqui insere o processo para gravar automaticamente
		$sqlenvio =  "UPDATE tbl_os_retorno
				SET nota_fiscal_retorno				= '1000',
					data_nf_retorno					= current_date,
					numero_rastreamento_retorno		= '1000',
					retorno_chegada					= current_date,
					envio_chegada					= current_date,
					admin_recebeu					= $login_admin,
					admin_enviou					= $login_admin
				WHERE os=$os";
		$resenvio = pg_query($con,$sqlenvio);

		$sqllibera = "INSERT INTO tbl_os_status
				(os,status_os,data,observacao,admin)
				VALUES ($os,64,current_timestamp,'OS Liberada da Intervenção',$login_admin)";
		$reslibera = pg_query($con,$sqllibera);

	}

	if (strlen ($msg_erro) == 0) {
		if (strlen($defeito_constatado) == 0) $defeito_constatado = 'null';

		if($login_fabrica==19){
			$numero_vezes_i = 100;
			$numero_vezes_j = 100;
			#Apaga todos os defeitos reclamados e constatados
			$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado WHERE os=$os";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			for ($i=0;$i<$numero_vezes_i;$i++){
				$int_reclamado = trim($_POST["defeito_reclamado_$i"]);

				if (!isset($_POST["defeito_reclamado_$i"])) continue;
				if (strlen($int_reclamado)==0)              continue;

				//echo "<hr>";
				$aux_defeito_reclamado = $int_reclamado;
				if($aux_defeito_reclamado<>0){
					#Insere todos os defeitos reclamados
					$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
								os                    ,
								defeito_reclamado
							)VALUES(
								$os                   ,
								$aux_defeito_reclamado
							)";
					//echo "$sql<br>";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				for ($j=0;$j<$numero_vezes_j;$j++){
					$int_constatado = trim($_POST["i_defeito_constatado_".$i."_".$j]);

					if (!isset($_POST["i_defeito_constatado_".$i."_".$j])) continue;
					if (strlen($int_constatado)==0)                        continue;

					$aux_defeito_constatado = $int_constatado;
					$defeito_constatado = $int_defeito_constatado;
					if($aux_defeito_reclamado==0) $aux_defeito_reclamado = "NULL";
					$sql = "SELECT defeito_constatado_reclamado
							FROM tbl_os_defeito_reclamado_constatado
							WHERE os                 = $os
							AND   defeito_reclamado  = $aux_defeito_reclamado
							AND   defeito_constatado = $aux_defeito_constatado";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if(@pg_num_rows($res)==0){
						$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
									os,
									defeito_constatado,
									defeito_reclamado
								)VALUES(
									$os,
									$aux_defeito_constatado,
									$aux_defeito_reclamado

								)";
						//echo "$sql<br>";
						$res = @pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$sql = "DELETE FROM tbl_os_defeito_reclamado_constatado
								WHERE os                = $os
								AND   defeito_reclamado = $aux_defeito_reclamado
								AND   defeito_constatado IS NULL";
						//echo "$sql";
						$res = @pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}

		if($login_fabrica==19){
			$sqlta = "SELECT tipo_atendimento FROM tbl_os WHERE os = $os";
			$resta = pg_query ($con,$sqlta);
			if (pg_num_rows($resta)>0){
				$tipo_atendimento = pg_result($resta,0,0);
			}
			# HD 28155
			if ($tipo_atendimento <> 6){
				$sql = "SELECT defeito_constatado
						FROM tbl_os_defeito_reclamado_constatado
						WHERE os                 = $os LIMIT 1";
				$res = @pg_query ($con,$sql);
					if(pg_num_rows($res)>0){
						$defeito_constatado = pg_result($res,0,0);
					}else $msg_erro = "É necessário informar o defeito constatado";
			}
		}
	}
	if (strlen ($defeito_constatado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	$defeito_reclamado = $_POST ['defeito_reclamado'];
	if (strlen ($defeito_reclamado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = pg_query ($con,$sql);
	}

	if(isset($_POST ['tipo_atendimento'])) $tipo_atendimento = $_POST ['tipo_atendimento'];
	if (strlen ($tipo_atendimento) > 0) {
		$sql = "UPDATE tbl_os SET tipo_atendimento = $tipo_atendimento
				WHERE  tbl_os.os    = $os ";
		$res = pg_query ($con,$sql);
	}

	$causa_defeito = $_POST['causa_defeito'];
	if (strlen($causa_defeito) == 0) $causa_defeito = "null";
	else                             $causa_defeito = $causa_defeito;

	$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito
			WHERE  tbl_os.os    = $os
			AND    tbl_os.posto = $posto;";
	$res = @pg_query ($con,$sql);

	$x_solucao_os = $_POST['solucao_os'];
	if (strlen($x_solucao_os) > 0) {
		$sql = "UPDATE tbl_os SET solucao_os = '$x_solucao_os'
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	$x_solucao_os2 = trim($_POST['solucao_os2']);
	if(strlen($x_solucao_os2) > 0) {
		$sql = "INSERT INTO tbl_servico_realizado(fabrica,descricao,ativo,linha)values($login_fabrica,'$x_solucao_os2','f',549)";
		$res = pg_query($con,$sql);
		$sql = "SELECT currval ('seq_servico_realizado')";
		$res = pg_query($con,$sql);
		$x_solucao_os = pg_result($res,0,0);
		$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}


	$obs  = trim($_POST['obs']);
	if (strlen($obs) > 0) $obs = "'".$obs."'";
	else                   $obs = "null";

	$tecnico_nome = trim($_POST["tecnico_nome"]);
	if (strlen($tecnico_nome) > 0) $tecnico_nome = "'".$tecnico_nome."'";
	else                   $tecnico_nome = "null";

	if($login_fabrica==1 and ($compressor=='t' or $tipo_os == 13)){
		$sql = "SELECT  tbl_os.data_abertura   AS data_abertura,
						os_numero,
						tipo_atendimento
				FROM    tbl_os
				WHERE   tbl_os.os = $os";
		$res = pg_query ($con,$sql) ;

		$data_abertura = pg_fetch_result($res,0,data_abertura);
		$os_numero     = pg_fetch_result($res,0,os_numero);
		$tipo_atendimento= pg_fetch_result($res,0,tipo_atendimento);

		for ( $i = 0 ; $i < $qtde_visita ; $i++ ) {
			$xos_visita                 = trim($_POST['os_visita_'. $i]);
			$xdata                      = fnc_formata_data_pg(trim($_POST['visita_data_'. $i]));
			$xxdata                     = str_replace("'","",$xdata);
			$xhora_chegada_cliente      = trim($_POST['visita_hr_inicio_'. $i]);
			$xhora_saida_cliente        = trim($_POST['visita_hr_fim_'. $i]);
			$xkm_chegada_cliente        = trim($_POST['visita_km_'. $i]);
			$xkm_chegada_cliente        = str_replace (",",".",$xkm_chegada_cliente);
			$xqtde_produto_atendido     = trim($_POST['qtde_produto_atendido_'. $i]);
			$valores_adicionais         = trim($_POST['valores_adicionais_'. $i]);
			$justificativa_adicionais   = trim($_POST['justificativa_adicionais_'. $i]);

			$xxkm_chegada_cliente = number_format($xkm_chegada_cliente,1,'.','');
			$xkm_chegada_cliente = number_format($xkm_chegada_cliente,2,'.','');
			$km_conferencia = number_format($_POST['km_conferencia_'.$i],1,'.','') ;
			if($xxkm_chegada_cliente <> $km_conferencia and $xxkm_chegada_cliente > ($km_conferencia* 1.1) and $km_conferencia > 0) {
				$msg_erro = "Fizemos a verificação de deslocamento ida e volta (endereço do posto até o cliente) e encontramos ". str_replace (".",",",$km_conferencia) ."KM de deslocamento. Por isso faremos a correção para prosseguir com a conclusão da OS. Em caso de dúvida gentileza entrar em contato com o seu suporte.";
				$visita_km_erro= $km_conferencia * 1.1;
			}

			if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";
			
			$valores_adicionais = str_replace (",",".",$valores_adicionais);

			if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
			else                   $justificativa_adicionais = "null";

			if($tipo_os == 13){
				$sql = "SELECT  count(*) as count_visita
						FROM    tbl_os_visita
						WHERE   tbl_os_visita.os_revenda= $os_numero";
				$res_vis = @pg_query ($con,$sql) ;

				$count_visita= pg_fetch_result($res_vis,0,count_visita);
				if(strlen($count_visita)>0 and $count_visita>4){
					$msg_erro .= "Quantidade de visitas maior que o permitido: $count_visita.<BR>";
				}
				
				if($tipo_atendimento ==64 and $xkm_chegada_cliente > 0){
					$msg_erro .= "Não é permitido a digitação de quilometragem para OS Metais Sanitários Balcão.<BR>";
				}elseif($tipo_atendimento ==69 and $xkm_chegada_cliente > 100){
					$msg_erro .= "Tipo de atendimento incorreto, pois nesse caso trata-se de deslocamento superior a 100 Km, ou seja, fora da área de atuação. Gentileza corrigir";
				}elseif($tipo_atendimento ==65 and $xkm_chegada_cliente < 100 and $xkm_chegada_cliente > 0) {
					$msg_erro .= "Tipo de atendimento incorreto, pois nesse caso trata-se de deslocamento inferior a 100 Km, ou seja, dentro da área de atuação. Gentileza corrigir";
				}

				
				$sql = "
					SELECT  count(os_revenda_item) as qtde_itens_geo
					FROM    tbl_os_revenda_item
					WHERE   os_revenda= $os_numero ";
				$res = pg_query ($con,$sql) ;

				$qtde_itens_geo = pg_fetch_result($res,0,qtde_itens_geo);
				if($xqtde_produto_atendido > $qtde_itens_geo){
					$msg_erro .= "Quantidade de produtos digitados está maior que a quantidade de produtos da OS.<BR>";
				}
			}elseif($xkm_chegada_cliente > 100){
				$msg_erro .= "Quilometragem máxima permitida é de 100 Km.<BR>";
			}

			
			if($xxdata < $data_abertura){
				$msg_erro .= "Data de abertura é maior que a data da visita.<BR>";
			}

			if($xxdata <> "null" and $xxdata > date('Y-m-d')) {
				$msg_erro .= "Data de visita futura (maior que a data de hoje).<BR> ";
			}


			# HD 165538
			if($compressor=='t') {
				$hora_permitida = $xhora_saida_cliente - $xhora_chegada_cliente;
				if($hora_permitida > 4) {
					$msg_erro = "De acordo com nossa engenharia o prazo para conserto (desmontagem e montagem) de um compressor desse modelo é de 2 a 4 horas. Sendo que, em serviços menos complexos o prazo é menor. Para os casos em que utilizar mais de 4 horas para conserto entre em contato com o seu suporte para avaliação da situação.";
				}
			}
		
			if(strlen($xos_visita) > 0){
				$cond_os_visita = " AND os_visita< $xos_visita ";
			}

			if(strlen($xhora_chegada_cliente)>0 and strlen($xhora_saida_cliente)>0){
				$xhora_chegada_cliente = "'$xxdata ".$xhora_chegada_cliente."'";
				$xhora_saida_cliente   = "'$xxdata ".$xhora_saida_cliente."'";
				$sql = " SELECT $xhora_chegada_cliente::timestamp > $xhora_saida_cliente::timestamp";
				$res = pg_query($con,$sql);
				if(pg_fetch_result($res,0,0) == 't') {
					$msg_erro .= "Hora de início é maior que a hora de fim na visita técnica.<BR> ";
				}
			}

			if(strlen($xqtde_produto_atendido)== 0 ) {
				$xqtde_produto_atendido = " 1 ";
			}


			if($xxdata <>'null' and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)==0) and (strlen($msg_erro)==0)){

				$sql = "INSERT INTO tbl_os_visita (
									$sql_aux_os          ,
									data                 ,
									km_chegada_cliente   ,
									valor_adicional      ,
									justificativa_valor_adicional,
									qtde_produto_atendido
								) VALUES (
									$aux_os                ,
									$xdata                 ,
									$xkm_chegada_cliente   ,
									$valores_adicionais    ,
									$justificativa_adicionais,
									$xqtde_produto_atendido
								)";
				$res = @pg_query ($con,$sql);
				//echo "inseriu $sql<BR>";
			}
			if((strlen($xxdata)>0) and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)>0) and (strlen($msg_erro)==0)){
				$sql = "UPDATE tbl_os_visita set
								data                 = $xdata                 ,
								km_chegada_cliente   = $xkm_chegada_cliente   ,
								valor_adicional      = $valores_adicionais    ,
								justificativa_valor_adicional = $justificativa_adicionais,
								qtde_produto_atendido= $xqtde_produto_atendido
							WHERE $sql_aux_os = $aux_os 
							AND   os_visita = $xos_visita";
				//echo "atualiza $sql";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if((strlen($xos_visita)>0) and ($xxdata=="null")){
				$sql = "DELETE FROM tbl_os_visita
								WHERE  $sql_aux_os      = $aux_os 
								AND    tbl_os_visita.os_visita = $xos_visita;";
			//	echo "apaga: $sql";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}
		}
		/*hd: 83010*/
		if($tipo_os ==13){
			$sql = "
				SELECT  distinct km_chegada_cliente
				FROM    tbl_os_visita
				WHERE   os_revenda= $os_numero ";
			$res_visita = pg_query ($con,$sql) ;

			if(pg_num_rows($res_visita)> 1 ){
				$msg_erro .= "Não é permitido que cadastre km diferente para as visitas.<BR> ";
			}
		}

		//*coloquei 24-01*//
		$tecnico = trim($_POST['tecnico']);
		if (strlen ($tecnico) > 0) $tecnico = "'".$tecnico."'";
			else   $msg_erro .= "Relatório técnico obrigatório";
		if(strlen($msg_erro)==0){
			$sql = "UPDATE tbl_os_extra set
							valor_por_km = 0.65,
							valor_total_hora_tecnica = 0.4,
							tecnico    = $tecnico
					WHERE os=$os";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

	$valores_adicionais = trim($_POST["valores_adicionais"]);
	$valores_adicionais = str_replace (",",".",$valores_adicionais);
	if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

	$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);
	if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
	else                   $justificativa_adicionais = "null";

	$qtde_km = trim($_POST["qtde_km"]);
	$qtde_km = str_replace (",",".",$qtde_km);
	if (strlen($qtde_km) == 0) $qtde_km = "0";


	$peca_alterada = "";

	if (strlen ($obs) > 0) {
	    $sql = "UPDATE  tbl_os SET obs = $obs,
			    tecnico_nome = $tecnico_nome,
			    qtde_km      = $qtde_km     ,
			    valores_adicionais = $valores_adicionais,
			    justificativa_adicionais = $justificativa_adicionais
		    WHERE  tbl_os.os    = $os
		    AND    tbl_os.posto = $posto";
		$res = @pg_query ($con,$sql);
	}

	#HD 14504
	/*#HD 14504
	$sql = "DELETE FROM tbl_os_produto
			WHERE  tbl_os_produto.os         = tbl_os.os
			AND    tbl_os_produto.os_produto = tbl_os_item.os_produto
			AND    tbl_os_item.pedido           IS NULL
			AND    tbl_os_item.liberacao_pedido IS false
			AND    tbl_os_produto.os = $os
			AND    tbl_os.fabrica    = $login_fabrica
			AND    tbl_os.posto      = $posto;";
	#$res = @pg_query ($con,$sql);
	*/

	##### É TROCA FATURADA #####
	if (strlen($troca_faturada) > 0) {
		$x_motivo_troca = trim($_POST['motivo_troca']);
		if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

		$resX = pg_query ($con,"BEGIN TRANSACTION");

		$sql =	"UPDATE tbl_os SET
						motivo_troca  = $x_motivo_troca
				WHERE  tbl_os.os      = $os
				and    tbl_os.fabrica = $login_fabrica;";
		$res = @pg_query ($con,$sql);

	##### NÃO É TROCA FATURADA #####
	}else{

		$qtde_item = $_POST['qtde_item'];
		if($login_fabrica == 6) $qtde_item = $qtde_item + 5;//Mais itens para a Tectoy
		if($login_fabrica == 45) $qtde_item = $qtde_item + 7;

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$xos_item        = trim($_POST["os_item_"        . $i]);
			$xos_produto     = trim($_POST['os_produto_'     . $i]);
			$xpeca           = trim($_POST["peca_"           . $i]);
			$xposicao        = trim($_POST["posicao_"        . $i]);
			$xqtde           = trim($_POST["qtde_"           . $i]);
			$xdefeito        = trim($_POST["defeito_"        . $i]);
			$xpcausa_defeito = trim($_POST["pcausa_defeito_" . $i]);
			$xservico        = trim($_POST["servico_"        . $i]);

			$xadmin_peca      = $_POST["admin_peca_"     . $i]; //aqui
			if(strlen($xadmin_peca)==0) $xadmin_peca ="$login_admin"; //aqui
			if($xadmin_peca=="P")$xadmin_peca ="null"; //aqui

			/* HD 20065 15/5/2008*/
			if($login_fabrica==24  AND strlen($xos_item)>0 AND strlen($msg_erro)==0){
				$sqlP = "SELECT tbl_peca.peca
						FROM   tbl_peca
						WHERE  upper(tbl_peca.referencia_pesquisa) = upper('$xpeca')
						AND    tbl_peca.fabrica             = $login_fabrica;";
				#echo $sqlP;
				$resP = @pg_query ($con,$sqlP);
				$msg_erro .= pg_errormessage($con);
				if (@pg_num_rows ($resP) > 0) {
					$xpeca_admin = pg_result ($resP,0,peca);
				}

				$sqlA = "SELECT peca AS peca_admin                  ,
								defeito AS defeito_admin            ,
								causa_defeito AS causa_defeito_admin,
								servico_realizado AS servico_admin
						FROM tbl_os_item
						WHERE os_item = $xos_item";
				$resA = @pg_query($con, $sqlA);
				$msg_erro .= pg_errormessage($con);
				#echo $sqlA;
				if(@pg_num_rows($resA)>0){
					$peca_admin          = pg_result($resA,0,peca_admin);
					$defeito_admin       = pg_result($resA,0,defeito_admin);
					$causa_defeito_admin = pg_result($resA,0,causa_defeito_admin);
					$servico_admin       = pg_result($resA,0,servico_admin);

					if($peca_admin<>$xpeca_admin OR $xdefeito<>$defeito_admin OR $xpcausa_defeito<>$causa_defeito_admin OR $xservico<>$servico_admin){
						$xadmin_peca ="$login_admin";
					}
				}
			}

			if (strlen($xposicao) > 0) $xposicao = "'" . $xposicao . "'";
			else                       $xposicao = "null";

			if (strlen ($xqtde) == 0) $xqtde = "1";

			$xpeca    = str_replace ("." , "" , $xpeca);
			$xpeca    = str_replace ("-" , "" , $xpeca);
			$xpeca    = str_replace ("/" , "" , $xpeca);
			$xpeca    = str_replace (" " , "" , $xpeca);

			#HD 14504
			if (strlen ($xos_produto) > 0 AND strlen($xpeca) == 0 AND strlen($msg_erro) == 0) {
				if (strlen ($xos_produto) > 0){
					$sql = "DELETE FROM tbl_os_produto
							WHERE  tbl_os_produto.os         = $os
							AND    tbl_os_produto.os_produto = tbl_os_item.os_produto
							AND    tbl_os_item.pedido           IS NULL
							AND    tbl_os_item.liberacao_pedido IS false
							";
					$sql = "DELETE FROM tbl_os_produto
							WHERE  tbl_os_produto.os            = $os
							AND    tbl_os_produto.os_produto    = tbl_os_item.os_produto
							AND    tbl_os_produto.os_produto    = $xos_produto
							AND    tbl_os_item.pedido           IS NULL
							AND    tbl_os_item.liberacao_pedido IS false
							;";
					flush();
					#HD 15489
					$sql = "UPDATE tbl_os_produto SET
								os = 4836000
							WHERE  tbl_os_produto.os            = $os
							AND    tbl_os_produto.os_produto    = tbl_os_item.os_produto
							AND    tbl_os_produto.os_produto    = $xos_produto
							AND    tbl_os_item.pedido           IS NULL
							AND    tbl_os_item.liberacao_pedido IS false";
					$res = pg_query ($con,$sql);
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}else{
				if (strlen($xpeca) > 0 AND strlen($msg_erro)==0) {
					$xpeca    = strtoupper ($xpeca);
					if (strlen ($produto) == 0) {
						$sql = "SELECT tbl_os.produto
								FROM   tbl_os
								WHERE  tbl_os.os      = $os
								AND    tbl_os.fabrica = $login_fabrica;";
						$res = pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (pg_num_rows($res) > 0) {
							$produto = pg_result ($res,0,0);
						}
					}else{
						$sqlPr = "SELECT tbl_produto.produto
								FROM   tbl_produto
								JOIN   tbl_linha USING (linha)
								JOIN   tbl_os    USING (produto)
								WHERE  tbl_os.os = $os
								AND    tbl_linha.fabrica = $login_fabrica;";
						#echo nl2br($sql);
						$resPr     = @pg_query($con,$sqlPr);
						$msg_erro .= pg_errormessage($con);
						if(@pg_num_rows($resPr)>0){
							$produto = pg_result($resPr,0,produto);
						}else{
							$msg_erro   = "Produto $produto não cadastrado";
							$linha_erro = $i;
						}
					}

					if (strlen ($msg_erro) == 0) {
						if (strlen($xos_produto) == 0){
							$sql = "INSERT INTO tbl_os_produto (
										os     ,
										produto,
										serie
									)VALUES(
										$os     ,
										$produto,
										'$serie'
									);";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							$res = @pg_query ($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  = @pg_result ($res,0,0);
						}else{
							$sql = "UPDATE tbl_os_produto SET
										produto = $produto,
										serie   = '$serie'
									WHERE os_produto = $xos_produto;";
							//echo '1-'.$sql.'<br>';
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						$sql = "SELECT tbl_peca.*
								FROM   tbl_peca
								WHERE  upper(tbl_peca.referencia_pesquisa) = upper('$xpeca')
								AND    tbl_peca.fabrica             = $login_fabrica;";
						$res = @pg_query ($con,$sql);

						if (@pg_num_rows ($res) == 0) {
							$msg_erro.= "Peça $xpeca não cadastrada";
							$linha_erro = $i;
						}else{
							$xpeca = pg_result ($res,0,peca);
						}

						#HD 13433 - Só para ver se a peça gera pedido e atualizar o status de intervenção
						if (strlen($xservico)>0 AND $login_fabrica == 3){
							$sql = "SELECT servico_realizado
									FROM tbl_servico_realizado
									WHERE fabrica         = $login_fabrica
									AND servico_realizado = $xservico
									AND gera_pedido       IS TRUE
									AND troca_de_peca     IS TRUE;";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							if (@pg_num_rows ($res) > 0) {
								$peca_alterada = "sim";
							}
						}

						if (strlen($xdefeito) == 0) $msg_erro = "Favor informar o defeito da peça"; #$defeito = "null";
						if (strlen($xservico) == 0) $msg_erro = "Favor informar o serviço realizado"; #$servico = "null";

						if(strlen($xpcausa_defeito) == 0) $xpcausa_defeito = "null";

						if (strlen ($msg_erro) == 0) {
							if (strlen($xos_item) == 0){
								$sql = "INSERT INTO tbl_os_item (
											os_produto        ,
											peca              ,
											posicao           ,
											qtde              ,
											defeito           ,
											causa_defeito     ,
											servico_realizado ,
											admin
										)VALUES(
											$xos_produto      ,
											$xpeca           ,
											$xposicao        ,
											$xqtde           ,
											$xdefeito        ,
											$xpcausa_defeito ,
											$xservico        ,
											$xadmin_peca
										)";
								$res = @pg_query ($con,$sql);
								$msg_erro = pg_errormessage($con);
							}else{
								$sql = "UPDATE tbl_os_item SET
											os_produto        = $xos_produto    ,
											posicao           = $xposicao       ,
											peca              = $xpeca          ,
											qtde              = $xqtde          ,
											defeito           = $xdefeito       ,
											causa_defeito     = $xpcausa_defeito,
											servico_realizado = $xservico       ,
											admin             = $xadmin_peca
										WHERE os_item = $xos_item;";
								//echo '2-'.$sql.'<br>';
								$res = @pg_query ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}

							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}
		}
		if($login_fabrica == 6){ //HD 2599
			$pre_total = $_POST['pre_total'];

			for ($i = 0 ; $i < $pre_total ; $i++) {
			$pre_peca = $_POST['pre_peca_'.$i];
				if(strlen($pre_peca)>0){
				//echo "<BR>$pre_peca";
				$pre_defeito = $_POST['pre_defeito_'.$i];
				$pre_servico = $_POST['pre_servico_'.$i];
				$pre_qtde    = $_POST['pre_qtde_'   .$i];
				//echo "<BR>$pre_defeito";
				//echo "<BR>$pre_servico";
				if(strlen($pre_defeito)== 0)$msg_erro .= "Favor informar o defeito da peça<BR>";
				if(strlen($pre_servico)== 0)$msg_erro .= "Favor informar o serviço realizado<BR>";

				$sql = "select produto from tbl_os where os=$os and fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$pre_produto = pg_result($res,0,0);
				}
				if(strlen($msg_erro)==0){
						$sql = "INSERT INTO tbl_os_produto (
										os     ,
										produto
									)VALUES(
										$os     ,
										$pre_produto
								);";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							//echo "1- ".$sql; exit;
							$res = pg_query ($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  = pg_result ($res,0,0);
				}
				if (strlen ($msg_erro) == 0) {
						$sql = "INSERT INTO tbl_os_item (
									os_produto        ,
									peca              ,
									qtde              ,
									defeito           ,
									servico_realizado ,
									admin
								)VALUES(
									$xos_produto    ,
									$pre_peca       ,
									$pre_qtde       ,
									$pre_defeito    ,
									$pre_servico    ,
									$xadmin_peca
							);";
						$res = @pg_query ($con,$sql);
						$msg_erro .= pg_errormessage($con);
						//echo "2- ".$sql;
				}
				}
			}
		}//HD 2599


	}

	/* HD 35521 */
	if ($login_fabrica==3 AND $peca_alterada=='sim'){

		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (62,64,65,72,73,116,117)
				ORDER BY data DESC
				LIMIT 1";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$ultimo_status_os     = pg_result ($res,0,status_os);
			$ultimo_status_os_obs = pg_result ($res,0,observacao);

			if ( $ultimo_status_os == "62" OR $ultimo_status_os == "72" OR $ultimo_status_os == "116" ){

				$proximo_status_intervencao = "64";
				if ($ultimo_status_os == "72"){
					$proximo_status_intervencao = "73";
				}
				if ($ultimo_status_os == "116"){
					$proximo_status_intervencao = "117";
				}

				$sql = "INSERT INTO tbl_os_status
						(os,status_os,observacao,admin)
						VALUES
						($os,$proximo_status_intervencao,'Pedido das Peças Autorizado Pela Fábrica',$login_admin)";
				$res = pg_query ($con,$sql);
			}
		}
	}


	if (strlen ($msg_erro) == 0) {
		$res      = @pg_query ($con,"SELECT fn_valida_os_item($os, $login_fabrica)");
		$msg_erro = pg_errormessage($con);
		if (strlen($data_fechamento) > 0){
			if (strlen ($msg_erro) == 0) {
					$sql = "UPDATE tbl_os SET data_fechamento   = $xdata_fechamento
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $posto;";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
					$res = @pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
			}
		}
	}

		############# MARCA ESMALTEC HD 27561 #################
		if($login_fabrica==30){
			$fogao              = strtoupper(trim($_POST['fogao']));
			$marca_fogao        = strtoupper(trim($_POST['marca_fogao']));

			$refrigerador       = strtoupper(trim($_POST['refrigerador']));
			$marca_refrigerador = strtoupper(trim($_POST['marca_refrigerador']));

			$bebedouro          = strtoupper(trim($_POST['bebedouro']));
			$marca_bebedouro    = strtoupper(trim($_POST['marca_bebedouro']));

			$microondas         = strtoupper(trim($_POST['microondas']));
			$marca_microondas   = strtoupper(trim($_POST['marca_microondas']));

			$lavadoura          = strtoupper(trim($_POST['lavadoura']));
			$marca_lavadoura    = strtoupper(trim($_POST['marca_lavadoura']));

			$escolheu=0;

			if(strlen($fogao)>0 AND strlen($marca_fogao)==0){
				$msg_erro .= "Escolha a marca do fogão";
			}
			if(strlen($fogao)>0 AND strlen($marca_fogao)>0){$escolheu++;}


			if(strlen($refrigerador)>0 AND strlen($marca_refrigerador)==0){
				$msg_erro .= "Escolha a marca do refrigerador";
			}
			if(strlen($refrigerador)>0 AND strlen($marca_refrigerador)>0){$escolheu++;}


			if(strlen($bebedouro)>0 AND strlen($marca_bebedouro)==0){
				$msg_erro .= "Escolha a marca do bebedouro";
			}
			if(strlen($bebedouro)>0 AND strlen($marca_bebedouro)>0){$escolheu++;}


			if(strlen($microondas)>0 AND strlen($marca_microondas)==0){
				$msg_erro .= "Escolha a marca do microondas";
			}
			if(strlen($microondas)>0 AND strlen($marca_microondas)>0){$escolheu++;}


			if(strlen($lavadoura)>0 AND strlen($marca_lavadoura)==0){
				$msg_erro .= "Escolha a marca da lavadoura";
			}
			if(strlen($lavadoura)>0 AND strlen($marca_lavadoura)>0){$escolheu++;}


			if(strlen($msg_erro)==0 AND $escolheu > 0){
				$marcas = $fogao . ";" . $marca_fogao . ";" . $refrigerador . ";" . $marca_refrigerador . ";" . $bebedouro . ";" . $marca_bebedouro . ";" . $microondas . ";" . $marca_microondas . ";" . $lavadoura . ";" . $marca_lavadoura;

				$sqlm = " UPDATE tbl_os_extra SET
								 obs_adicionais = '$marcas'
							WHERE os = $os";
				$resm = pg_query ($con,$sqlm);
				$msg_erro .= pg_errormessage($con);
			}
		}
	#######################################################

	//hd 17966
	if($login_fabrica==45 and strlen($voltar_fechamento)>0 AND strlen($voltar_finalizada)>0) {
		$sql = "UPDATE tbl_os SET data_fechamento = '$voltar_fechamento' , finalizada = '$voltar_finalizada'
				WHERE os      = $os
				AND   fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: os_finalizada.php?os=$os");
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*,
			tbl_produto.referencia,
			tbl_produto.descricao ,
			tbl_produto.linha
		FROM    tbl_os
		LEFT JOIN tbl_produto USING (produto)
		WHERE   tbl_os.os = $os";
	$res = pg_query ($con,$sql) ;

	$defeito_constatado = pg_result ($res,0,defeito_constatado);
	$causa_defeito      = pg_result ($res,0,causa_defeito);
	$linha              = pg_result ($res,0,linha);
	$consumidor_nome    = pg_result ($res,0,consumidor_nome);
	$sua_os             = pg_result ($res,0,sua_os);
	$produto_os         = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia);
	$produto_descricao  = pg_result ($res,0,descricao);
	$produto_serie      = pg_result ($res,0,serie);
	$qtde_produtos      = pg_result ($res,0,qtde_produtos);
	$posto              = pg_result ($res,0,posto);
	$obs                = pg_result ($res,0,obs);
	$solucao_os         = pg_result ($res,0,solucao_os);
}

#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto   ,
				tbl_fabrica.pergunta_qtde_os_item ,
				tbl_fabrica.os_item_serie         ,
				tbl_fabrica.os_item_aparencia     ,
				tbl_fabrica.qtde_item_os
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_query ($con,$sql);

if (pg_num_rows($resX) > 0) {
	$os_item_subconjunto = pg_result($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';

	$pergunta_qtde_os_item = pg_result($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';

	$os_item_serie = pg_result($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';

	$os_item_aparencia = pg_result($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';

	$qtde_item = pg_result($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
}

if (strlen($posto) > 0 ) {
	$resX = pg_query ($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica");
} else {
	$msg_erro = 'Favor informe o código do posto!';
}
if (pg_num_rows($resX) > 0) $posto_item_aparencia = pg_result($resX,0,0);

$title = "Telecontrol - Assistência Técnica - Ordem de Serviço";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'callcenter';
include "cabecalho.php";

?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->
<?
#----------------- Le dados da OS --------------
if (strlen($os) > 0) {
	$sql = "SELECT  tbl_os.*                              ,
			tbl_produto.referencia                        ,
			tbl_produto.descricao                         ,
			tbl_produto.voltagem                          ,
			tbl_produto.linha                             ,
			tbl_produto.familia                           ,
			tbl_os_extra.os_reincidente AS reincidente_os ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_posto_fabrica.reembolso_peca_estoque      ,
			tbl_os_extra.obs_adicionais
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_produto  USING (produto)
		JOIN    tbl_posto         USING (posto)
		JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
			  AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_os.os = $os";
	$res = @pg_query ($con,$sql) ;

	if (@pg_num_rows($res) > 0) {
		$login_posto            = pg_result($res,0,posto);
		$linha                  = pg_result($res,0,linha);
		$familia                = pg_result($res,0,familia);
		$consumidor_nome        = pg_result($res,0,consumidor_nome);
		$sua_os                 = pg_result($res,0,sua_os);
		$produto_os             = pg_result($res,0,produto);
		$produto_referencia     = pg_result($res,0,referencia);
		$produto_descricao      = pg_result($res,0,descricao);
		$produto_voltagem       = pg_result($res,0,voltagem);
		$produto_serie          = pg_result($res,0,serie);
		$qtde_produtos          = pg_result($res,0,qtde_produtos);
		$produto_type           = pg_result($res,0,type);
		$defeito_reclamado      = pg_result($res,0,defeito_reclamado);
		$defeito_constatado     = pg_result($res,0,defeito_constatado);
		$causa_defeito          = pg_result($res,0,causa_defeito);
		$posto                  = pg_result($res,0,posto);
		$obs                    = pg_result($res,0,obs);
		$os_reincidente         = pg_result($res,0,reincidente_os);
		$codigo_posto           = pg_result($res,0,codigo_posto);
		$reembolso_peca_estoque = pg_result($res,0,reembolso_peca_estoque);
		$consumidor_revenda     = pg_result($res,0,consumidor_revenda);
		$troca_faturada         = pg_result($res,0,troca_faturada);
		$motivo_troca           = pg_result($res,0,motivo_troca);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$tecnico_nome       	= pg_result ($res,0,tecnico_nome);
		#$codigo_fabricacao      = pg_result ($res,0,codigo_fabricacao);
		$valores_adicionais 	= pg_result ($res,0,valores_adicionais);
		$justificativa_adicionais = pg_result ($res,0,justificativa_adicionais);
		$qtde_km            	= pg_result ($res,0,qtde_km);
		$produto_linha          = pg_result ($res,0,linha);
		$produto_familia        = pg_result ($res,0,familia);
		if($login_fabrica==19){//HD 48818
			$data_fechamento     = pg_result ($res,0,data_fechamento);
			if(strlen($data_fechamento)>0){
				$data_fechamento = explode("-", $data_fechamento);
				$data_fechamento = $data_fechamento[2]."/".$data_fechamento[1]."/".$data_fechamento[0];
			}
		}
		if($login_fabrica==30){//HD 27561
			$obs_adicionais = pg_result($res,0, obs_adicionais);

			$obs_adicionais = explode(";", $obs_adicionais);

			$fogao               = $obs_adicionais[0];
			$marca_fogao         = $obs_adicionais[1];
			$refrigerador        = $obs_adicionais[2];
			$marca_refrigerador  = $obs_adicionais[3];
			$bebedouro           = $obs_adicionais[4];
			$marca_bebedouro     = $obs_adicionais[5];
			$microondas          = $obs_adicionais[6];
			$marca_microondas    = $obs_adicionais[7];
			$lavadoura           = $obs_adicionais[8];
			$marca_lavadoura     = $obs_adicionais[9];
		}

		/*$sequencia = substr($codigo_fabricacao,6,2);
		$mes_ano = substr($codigo_fabricacao,0,6);
		$mes_ano = substr_replace($mes_ano,"/",2,0);*/

	}

	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_query ($con,$sql) ;

		if (pg_num_rows($res) > 0) $sua_os_reincidente = trim(pg_result($res,0,sua_os));
	}
}
?>

<? include "javascript_pesquisas.php" ?>
<? include "javascript_calendario.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script language="JavaScript">

/*$(function(){
		$("#mes_ano").maskedinput("99/9999");
	}); */

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo;
	}
	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite pelo menos 4 caracteres!");
	}
}
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes é o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//após ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o código do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaCombo(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//contéudo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {

//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
			document.forms[0].solucao_os.options.length = 1;
	//opcoes é o nome do campo combo
			idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);

	ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaComboSolucao(ajax.responseXML);//após ser processado-chama fun
		} else {idOpcao.innerHTML = "Selecione o defeito constatado";//caso não seja um arquivo XML emite a mensagem abaixo
		}
		}
	}
	//passa o código do produto escolhido
			var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
	ajax.send(null);
		}
}

function montaComboSolucao(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//contéudo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					idOpcao.innerHTML = "";
		//cria um novo option dinamicamente
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].solucao_os.options.add(novo);//adiciona o novo elemento
				}
			} else { idOpcao.innerHTML = "Nenhuma solução encontrada";//caso o XML volte vazio, printa a mensagem abaixo
			}
}
function listaConstatado(linha,familia, defeito_reclamado,defeito_constatado) {

//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}

//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros sï¿½ excluï¿½os

	defeito_constatado.options.length = 1;
	idOpcao  = document.getElementById("opcoes2");
	ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);

	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {
			if(ajax.responseXML) {
					montaComboConstatado(ajax.responseXML,defeito_constatado);
			//apï¿½ ser processado-chama fun
			}
			else {
				idOpcao.innerHTML = "Selecione o defeito reclamado";//caso nï¿½ seja um arquivo XML emite a mensagem abaixo
			}
		}
	}
	//passa o cï¿½igo do produto escolhido
	//var params ="defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha";
	ajax.send(null);
		}
}

function montaComboConstatado(obj,defeito_constatado){
	var dataArray   = obj.getElementsByTagName("produto");

	if(dataArray.length > 0) {
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes2");
			novo.value = codigo;
			novo.text  = nome  ;
			defeito_constatado.options.add(novo);//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";
	}
}

function defeitoLista(peca,linha,os) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
	if(peca.length > 0) {
		if(ajax) {
			var defeito = "defeito_"+linha;
			var op = "op_"+linha;
	//alert(defeito);
		//deixa apenas o elemento 1 no option, os outros sï¿½ excluï¿½os
				eval("document.forms[0]."+defeito+".options.length = 1;");
		//opcoes ï¿½o nome do campo combo
				idOpcao  = document.getElementById(op);
		//	 ajax.open("POST", "ajax_produto.php", true);
	//alert("tas "+idOpcao);
		ajax.open("GET","ajax_defeito2.php?peca="+peca+"&os="+os);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
			if(ajax.readyState == 4 ) {
				if(ajax.responseXML) {
					montaComboDefeito(ajax.responseXML,linha);
				//apï¿½ ser processado-chama fun
				}else {
					idOpcao.innerHTML = "Selecione a peça";//caso nï¿½ seja um arquivo XML emite a mensagem abaixo
				}
			}
		}
		//passa o cï¿½igo do produto escolhido
		//var params ="defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha";
		ajax.send(null);
		}
	}
}

function montaComboDefeito(obj,linha){
	var defeito = "defeito_"+linha;
	var op = "op_"+linha;
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto

	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];

			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";

			var novo = document.createElement("option");
				novo.setAttribute("id", op);//atribui um ID a esse elemento
				novo.value = codigo;		//atribui um valor
				novo.text  = nome;//atribui um texto
				eval("document.forms[0]."+defeito+".options.add(novo);");//adiciona
		}
	} else {
		idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

	function adicionaIntegridade() {

		//if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		if(document.getElementById('defeito_constatado').value==""){
			alert('Selecione o defeito constatado');
			return false;
		}
		//if(document.getElementById('solucao').value=="0")           { alert('Selecione a solução');           return false}

		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;


		if (iteration>0){
			document.getElementById('tbl_integridade').style.display = "inline";
		}


		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// COLUNA 1 - LINHA
		var celula = criaCelula(document.getElementById('defeito_constatado').options[document.getElementById('defeito_constatado').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_constatado').value);
		celula.appendChild(el);

		linha.appendChild(celula);



		<? if($login_fabrica == 59 or $login_fabrica == 2) { ?>
			var celula = criaCelula(document.getElementById('solucao').options[document.getElementById('solucao').selectedIndex].text );
			celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

			var el = document.createElement('input');
			el.setAttribute('type', 'hidden');
			el.setAttribute('name', 'integridade_solucao_' + iteration);
			el.setAttribute('id', 'integridade_solucao_' + iteration);
			el.setAttribute('value',document.getElementById('solucao').value);
			celula.appendChild(el);

			linha.appendChild(celula);
		<?}?>



		// coluna 6 - botacao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = tbl.getElementsByTagName("tbody")[0];
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

		//document.getElementById('solucao').selectedIndex=0;
	}

	function adicionaIntegridade2(indice,tabela,defeito_reclamado,defeito_reclamado_desc,defeito_constatado) {
		var parar = 0;
		//alert(defeito_reclamado.value);
		//alert(defeito_constatado.value);
		$("input[@rel='defeito_constatado_"+indice+"']").each(function (){
			//alert($(this).val() + '-'+ defeito_constatado.value);
			if ($(this).val() == defeito_constatado.value){
				parar++;
			}
		});

		if (parar>0){
			alert('Defeito constatado '+defeito_constatado.options[defeito_constatado.selectedIndex].text+' já inserido')
			return false;
		}

		//if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		var tbl       = document.getElementById(tabela);
		var lastRow   = tbl.rows.length;
		var iteration = lastRow;

		if (iteration>0){
			document.getElementById(tabela).style.display = "inline";
		}
		//Cria Linha
		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// Cria Coluna/
		var celula = document.createElement('td');
		var celula = criaCelula(defeito_constatado.options[defeito_constatado.selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000';
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'i_defeito_constatado_' +indice+'_'+ iteration);
		el.setAttribute('rel', 'defeito_constatado_' +indice);
		el.setAttribute('id', 'i_defeito_constatado_' +indice+'_'+ iteration);
		el.setAttribute('value',defeito_constatado.value);
		celula.appendChild(el);
		linha.appendChild(celula);


		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';
		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade2(this,tabela);};
		celula.appendChild(el);
		linha.appendChild(celula);


		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);
	}

	function removerIntegridade(iidd){
		var tbl = document.getElementById('tbl_integridade');
		tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

	}

	function removerIntegridade2(iidd,tabela){
		var tbl = document.getElementById(tabela);
		tbl.deleteRow(iidd.parentNode.parentNode.rowIndex);

	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}

function formata_data_visita(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_data_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}


function formata_valor(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "valores_adicionais_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}


}
function FormataValor(campo,tammax,teclapres)
{
    //uso:
    //<input type="Text" name="fat_vr_bruto" maxlength="17" onKeyDown="FormataValor(this,17,event)">

    var tecla = teclapres.keyCode;
    vr = campo.value;
    vr = vr.replace( "/", "" );
    vr = vr.replace( "/", "" );
    vr = vr.replace( ",", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    vr = vr.replace( ".", "" );
    tam = vr.length;

    if (tam<tammax && tecla != 8){ tam = vr.length + 1 ; }

    if (tecla == 8 ){    tam = tam - 1 ; }

    if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
        if ( tam <= 2 ){
             campo.value = vr ; }
         if ( (tam>2) && (tam <= 5) ){
             campo.value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 6) && (tam <= 8) ){
             campo.value = vr.substr( 0, tam - 5 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 9) && (tam <= 11) ){
             campo.value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 12) && (tam <= 14) ){
             campo.value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ; }
         if ( (tam >= 15) && (tam <= 17) ){
             campo.value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ) ;}
    }
}

</script>

<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

</style>

<p>

<?
echo '<pre>';
print_r($msg_erro);
echo '</pre>';
if (strlen ($msg_erro) > 0) {

##### RECARREGA FORM EM CASO DE ERRO #####
	if (strlen($os) == 0) $os = $_POST["os"];
	$defeito_constatado = $_POST["defeito_constatado"];
	$defeito_reclamado  = $_POST["defeito_reclamado"];
	$causa_defeito      = $_POST["causa_defeito"];
	$obs                = $_POST["obs"];
	$solucao_os         = $_POST["solucao_os"];

	$tecnico_nome       = $_POST["tecnico_nome"];
	/*$mes_ano            = $_POST["mes_ano"];
	$sequencia          = $_POST["sequencia"];*/

	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
	if (strpos ($msg_erro,"pedido_fk") > 0) $msg_erro = "Este item da OS já foi faturado. Não pode ser removido.";
?>
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center"><font face="Arial, Helvetica, sans-serif" color="#FF3333"><b>
		<?
		// retira palavra ERROR:
		if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}
		echo $erro . $msg_erro;
		?>
		</b></font>
	</td>
</tr>
</table>
<?
}

if (strlen($sua_os_reincidente) > 0 and $login_fabrica == 6) {
	echo "<br><br>";
	$sql = "select * from tbl_os_status where os=$os and status_os = 67";
	$res = pg_query($con,$sql);
	$sql = "select * from tbl_os where os=$os and os_reincidente = 't'"; /*Reincidencia de postos diferentes*/
	$res2 = pg_query($con,$sql);
	if(pg_num_rows($res)>0 and pg_num_rows($res2)>0){
		echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
		echo "<tr>";

		echo "<td valign='middle' align='center'>";
		echo "<font face='Verdana,Arial, Helvetica, sans-serif' color='#FF3333' size='2'><b>";
		echo "ESTA ORDEM DE SERVIÇO É REINCIDENTE MENOR QUE 90 DIAS.<br>
		ORDEM DE SERVIÇO ANTERIOR: $sua_os_reincidente.<br>
		NÃO SERÁ PAGO O VALOR DE MÃO-DE-OBRA PARA A ORDEM DE SERVIÇO ATUAL.<BR>
		ELA SERVIRÁ APENAS PARA PEDIDO DE PEÇAS.";
		echo "</b></font>";
		echo "</td>";

		echo "</tr>";
		echo "</table>";

		echo "<br><br>";
	}
}

if (strlen($sua_os_reincidente) > 0 and $login_fabrica == 6 and 1==2) {
	echo "<br><br>";

	echo "<table width='600' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffCCCC'>";
	echo "<tr>";

	echo "<td valign='middle' align='center'>";
	echo "<font face='Verdana,Arial, Helvetica, sans-serif' color='#FF3333' size='2'><b>";
	echo "ESTA ORDEM DE SERVIÇO É REINCIDENTE MENOR QUE 30 DIAS.<br>
	O NÚMERO DE SÉRIE É O MESMO UTILIZADO NA ORDEM DE SERVIÇO: $sua_os_reincidente.<br>
	NÃO SERÁ PAGO O VALOR DE MÃO-DE-OBRA PARA A ORDEM DE SERVIÇO ATUAL.<BR>
	ELA SERVIRÁ APENAS PARA PEDIDO DE PEÇAS.";
	echo "</b></font>";
	echo "</td>";

	echo "</tr>";
	echo "</table>";

	echo "<br><br>";
}
?>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="1000" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type='hidden' name='voltagem' value='<? echo $voltagem ?>'>
		<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
		<input type='hidden' name='produto_referencia' value='<? echo $produto_referencia ?>'>
		<p>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor </font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
			<? if ($login_fabrica == 19) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $qtde_produtos;
				?>
				</b>
				</font>
			</td>
			<? } ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $produto_referencia . " - " . $produto_descricao;
				if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem;
				?>
				</b>
				</font>
			</td>
			<? if ($login_fabrica == 1) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Versão/Type</font>
				<br>
				<select name="produto_type" size="1" class="frm">
				<option value='' selected></option>
				<?
				$types = array ("Tipo 1", "Tipo 2", "Tipo 3", "Tipo 4", "Tipo 5", "Tipo 6", "Tipo 7", "Tipo 8", "Tipo 9");
				for ($i = 0 ; $i < count($types) ; $i++) {
					echo "<option value='" . $types[$i] . "'";
					if ($produto_type == $types[$i]) echo " selected";
					echo ">" . $types[$i] . "</option>";
				}
				?>
				</select>
			</td>
			<? } ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					<?
					if($login_fabrica==35){
						echo "PO#";
					}else{
						echo "N. Série";
					}?>
				</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
		</tr>
		</table>

<?
if($login_fabrica==6 or $login_fabrica==3 or $login_fabrica==24  OR $login_fabrica==30 OR $login_fabrica==11 OR $login_fabrica==19 OR $login_fabrica==50 OR $login_fabrica==51 OR $login_fabrica==59 or $login_fabrica == 2){
//relacionamento de integridade comeca aqui....
echo "<INPUT TYPE='hidden' name='xxproduto_linha' value='$produto_linha'>";
echo "<INPUT TYPE='hidden' name='xxproduto_familia' value='$produto_familia'>";

if(($login_fabrica==6 or $login_fabrica==3 OR $login_fabrica==24 OR $login_fabrica==11 OR $login_fabrica==50) and strlen($defeito_reclamado)>0){
//verifica se o defeito reclamado esta ativo, senao ele pede pra escolher de novo...acontece pq houve a mudança de tela.
	$sql = "SELECT ativo from tbl_defeito_reclamado where defeito_reclamado=$defeito_reclamado";
	$res = pg_query($con,$sql);
	$xativo = @pg_result($res,0, ativo);

	if($xativo=='f'){
		$defeito_reclamado= "";
	}
	$sql = "SELECT defeito_reclamado
			FROM tbl_diagnostico
			WHERE fabrica=$login_fabrica
			AND linha = $produto_linha
			AND defeito_reclamado = $defeito_reclamado
			AND familia = $produto_familia";
	$res = @pg_query($con,$sql);
#if($ip=="201.43.11.131"){echo $sql;}
	$xativo = @pg_result($res,0, defeito_reclamado);
	if(strlen($xativo)==0){
		$defeito_reclamado= "";
	}
}

if ((strlen($defeito_reclamado)>0) and ( ($login_fabrica==3) or ($login_fabrica==15) or ($login_fabrica==11) or ($login_fabrica==24) or ($login_fabrica==5) or ($login_fabrica==30) or ($login_fabrica==50) or ($login_fabrica==51) or ($login_fabrica == 2) or ($login_fabrica==6)  or ($login_fabrica==59))){
		#echo "teste";
	echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
	echo "<tr>";
	echo "<td>";


	echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><BR>";

	$sql = "SELECT 	defeito_reclamado,
					descricao as defeito_reclamado_descricao
			FROM tbl_defeito_reclamado
			WHERE defeito_reclamado= $defeito_reclamado";

	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		$xdefeito_reclamado = pg_result($res,0,defeito_reclamado);
		$xdefeito_reclamado_descricao = pg_result($res,0,defeito_reclamado_descricao);
	}
	echo "<INPUT TYPE='text' name='xxdefeito_reclamado' size='30' value='$xdefeito_reclamado - $xdefeito_reclamado_descricao' disabled>";

	echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$xdefeito_reclamado'>";
	echo "</td>";

	if($login_fabrica<>19){

	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR>";

		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
				WHERE tbl_diagnostico.linha = $produto_linha
				AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado
				AND tbl_defeito_constatado.ativo='t' ";
		if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query($con,$sql);

		echo "<select name='defeito_constatado' id='defeito_constatado' size='1' class='frm' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);'>";

		echo "<option value=''></option>";
		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_result ($res,$y,descricao) ;

			echo "<option value='$xxdefeito_constatado'";
			if($defeito_constatado==$xxdefeito_constatado) echo "selected";
			echo ">$defeito_constatado_descricao</option>";
		}

		echo "</select>";
		echo "</td>";
		echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
		echo "<select name='solucao_os' class='frm'  style='width:250px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";

		$sql = "SELECT 	solucao,
						descricao
				FROM tbl_solucao
				WHERE fabrica=$login_fabrica
				AND solucao=$solucao_os";
		$res = pg_query($con, $sql);
		$solucao_descricao = pg_result ($res,0,descricao);

		echo "<option id='opcoes' value='$solucao_os'>$solucao_descricao</option>";
		echo "</select>";
	}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<BR><BR>";
}
//FIM se tiver o defeito reclamado ativo
?>

<?
//caso nao achar defeito reclamado

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query ($con,$sql);
$pedir_defeito_reclamado_descricao = pg_result ($res,0,pedir_defeito_reclamado_descricao);
if ((strlen($defeito_reclamado)==0 or $login_fabrica==6) AND $login_fabrica <> 19){
	echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
	echo "<tr>";
	//HD17683
	if($pedir_defeito_reclamado_descricao == 't'){
		if ($login_fabrica <> 2) {
		echo "<tr>";
		echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
		if(strpos($sua_os,'-') == FALSE){//SE FOR DE CONSUMIDOR
			if(strlen($defeito_reclamado_descricao) > 0){
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao</b></div>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
			}else{
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
				echo "<INPUT TYPE='text' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";

			}
		}else{//SE FOR DE REVENDA
			if(strlen($defeito_reclamado_descricao) == 0 ){
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao_os</b></div>";
				echo "<INPUT TYPE='text' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
			}else{
				echo "<div style='size=11px'><b>$defeito_reclamado_descricao</b></div>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado'>";
				echo "<INPUT TYPE='hidden' name='defeito_reclamado_descricao' value='$defeito_reclamado_descricao'>";
			}
		}
		echo "</td>";
	}else{

	echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";
	echo "<select name='defeito_reclamado' class='frm' style='width:220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);'";
	if($login_fabrica==19) echo "onchange='window.location=\"$PHP_SELF?os=$os&defeito_reclamado=\"+this.value'";
	echo ">";
	echo "<option id='opcoes' value=''></option>";
	if( $login_fabrica==6 or $login_fabrica ==2){
		$sql = "SELECT tbl_defeito_reclamado.defeito_reclamado,
						tbl_defeito_reclamado.descricao
				from tbl_defeito_reclamado
				where tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado";
		$res = pg_query($con, $sql);
		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$hdefeito_reclamado = pg_result ($res,$y,defeito_reclamado) ;
			$hdescricao         = pg_result ($res,$y,descricao) ;

			echo "<option value='$hdefeito_reclamado'"; if($defeito_reclamado==$hdefeito_reclamado) echo "selected"; echo ">$hdescricao</option>";
		}
	}
	echo "</select>";
	echo "</td>";
	}
	}
	if($login_fabrica == 11) { // HD 139620
		echo "<td nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Reclamado</font><br>";

		$sql = "SELECT 	DISTINCT(tbl_diagnostico.defeito_reclamado),
					tbl_defeito_reclamado.descricao
				FROM tbl_diagnostico
				JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
				JOIN tbl_produto ON tbl_diagnostico.linha = tbl_produto.linha AND tbl_diagnostico.familia = tbl_produto.familia
				JOIN tbl_os ON tbl_os.produto = tbl_produto.produto
				WHERE tbl_diagnostico.fabrica=$login_fabrica
				AND   tbl_defeito_reclamado.ativo='t' and tbl_diagnostico.ativo='t'
				AND   tbl_os.os = $os
				AND   tbl_os.fabrica = $login_fabrica";
		$resD = pg_query ($con,$sql);

		if (@pg_num_rows ($resD) > 0 ) {
			echo "<select name='defeito_reclamado' size='1' class='frm'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_num_rows ($resD) ; $i++ ) {
				echo "<option ";
				if ($defeito_reclamado == pg_result ($resD,$i,defeito_reclamado) ) echo " selected ";
				echo " value='" . pg_result ($resD,$i,defeito_reclamado) . "'>" ;
				echo pg_result ($resD,$i,descricao) ;
				echo "</option>";
			}
			echo "</select>";
		}
		echo "</td>";
	}
	//CONSTATADO
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito Constatado</font><BR>";
	echo "<select name='defeito_constatado' id='defeito_constatado'  class='frm' style='width: 220px;'";
	if($login_fabrica <> 30)
	    echo "onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado.value,this);' >";
	else
		echo "' >";
	if($login_fabrica==30){
		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao            ,
						tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
				WHERE tbl_diagnostico.linha = $produto_linha
				AND tbl_diagnostico.ativo='t' ";
		if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query($con,$sql);
		
		echo "<option value=''></option>";
		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_result ($res,$y,descricao) ;
			$defeito_constatado_codigo    = pg_result ($res,$y,codigo) ;

			echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_codigo - $defeito_constatado_descricao</option>";
		}
	}


	echo "<option id='opcoes2' value=''></option>";

	if( $login_fabrica==6){
		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
				WHERE tbl_diagnostico.linha = $produto_linha
				AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado
				AND tbl_defeito_constatado.ativo='t' ";
		if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query($con,$sql);
			for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_result ($res,$y,descricao) ;

			echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_descricao</option>";
		}
	}

	if($login_fabrica==50 OR $login_fabrica==51){
		$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado),
						tbl_defeito_constatado.descricao            ,
						tbl_defeito_constatado.codigo
				FROM tbl_diagnostico
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
				WHERE tbl_diagnostico.linha = $produto_linha
				AND tbl_diagnostico.ativo='t' ";
		//if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
		$sql.=" ORDER BY tbl_defeito_constatado.descricao";
		$res = pg_query($con,$sql);

		echo "<option value=''></option>";
		for ($y = 0 ; $y < pg_num_rows ($res) ; $y++ ) {
			$xxdefeito_constatado = pg_result ($res,$y,defeito_constatado) ;
			$defeito_constatado_descricao = pg_result ($res,$y,descricao) ;
			$defeito_constatado_codigo    = pg_result ($res,$y,codigo) ;

			echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_codigo - $defeito_constatado_descricao</option>";
		}
	}



	echo "</select>";
	echo "</td>";
	//CONSTATADO
	//SOLUCAO
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solução</font><BR>";
	echo "<select name='solucao_os' class='frm'  style='width:250px;' id='solucao' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
	//echo "<option id='opcoes' value=''></option>";

			$sql = "SELECT 	solucao,
						descricao
				FROM tbl_solucao
				WHERE fabrica=$login_fabrica
				AND solucao=$solucao_os";
		$res = pg_query($con, $sql);
		$solucao_descricao = pg_result ($res,0,descricao);
		echo "<option id='opcoes' value='$solucao_os'>$solucao_descricao</option>";


	//takashi 19-06 hd 2814
	echo "</select>";
	echo "</td>";
	//SOLUCAO
	echo "</tr>";
	echo "</table>";
}
//fim caso nao achar defeito reclamado
//HD17683

if($login_fabrica==30 or $login_fabrica==59 or $login_fabrica == 2){
	echo "<input type='button' onclick=\"javascript: adicionaIntegridade()\" value='Adicionar Defeito' name='btn_adicionar'><br>";
	echo "
	<table style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px;display:none' align='center' width='700' border='0' id='tbl_integridade' cellspacing='3' cellpadding='3'>
	<thead>
	<tr bgcolor='#596D9B' style='color:#FFFFFF;'>
	<td align='center'><b>Defeito Constatado</b></td>";
	if($login_fabrica==59 or $login_fabrica == 2){
		echo "<td align='center'><b>Solução</b></td>";
	}

	echo "<td align='center'><b>Ações</b></td>
	</tr>
	</thead>
	<tbody>";
	$sql_cons = "SELECT
					tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao         ,
					tbl_defeito_constatado.codigo,
					tbl_os_defeito_reclamado_constatado.solucao
			FROM tbl_os_defeito_reclamado_constatado
			JOIN tbl_defeito_constatado USING(defeito_constatado)
			WHERE os = $os";
	$res_dc = pg_query($con, $sql_cons);
	if(pg_num_rows($res_dc) > 0){
		for($x=0;$x<pg_num_rows($res_dc);$x++){
			$dc_defeito_constatado = pg_result($res_dc,$x,defeito_constatado);
			$dc_descricao = pg_result($res_dc,$x,descricao);
			$dc_solucao = pg_result($res_dc,$x,solucao);
			$dc_codigo = pg_result($res_dc,$x,codigo);
			$aa = $x+1;
			echo "<tr>";
			echo "<td align='left'><font size='1'><input type='hidden' name='integridade_defeito_constatado_$aa' value='$dc_defeito_constatado'>$dc_codigo-$dc_descricao</font></td>";

			if(($login_fabrica==59 or $login_fabrica == 2) and strlen($dc_solucao) > 0){
				$sql_solucao = "select descricao from tbl_solucao where solucao=$dc_solucao";
				$res_solucao = pg_query($con, $sql_solucao);

				$sl_descricao = pg_result($res_solucao,0,descricao);

				echo "<td align='left'><font size='1'><input type='hidden' name='integridade_solucao_$aa' value='$dc_solucao'>$sl_descricao</font></td>";
			}

			echo "<td align='right'><input type='button' onclick='removerIntegridade(this);' value='Excluir'></td>";
			echo "</tr>";
		}
		echo "<script>document.getElementById('tbl_integridade').style.display = \"inline\";</script>";
	}
	echo "</tbody></table>";
}

//HD 23041
if($login_fabrica==19){
	$sql = "SELECT defeito_reclamado
				FROM tbl_os_defeito_reclamado_constatado
				WHERE os                 = $os LIMIT 1";
		$res = @pg_query ($con,$sql);
		if(pg_num_rows($res)==0){
			$sql = "SELECT defeito_reclamado FROM tbl_os WHERE os=$os";
			$res = @pg_query ($con,$sql);
			if(pg_num_rows($res)>0){
				$aux_defeito_reclamado = pg_result($res,0,0);
				$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado(
					os,
					defeito_reclamado
				)VALUES(
					$os,
					$aux_defeito_reclamado
				)";
				$res = @pg_query ($con,$sql);
			}
		}

	echo "<table style=' border:#485989 1px solid; font-size:12px;' align='center' width='700' border='0' cellspacing='3' cellpadding='3' bgcolor='#e6eef7'>";
	echo "<thead>";
	echo "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
	echo "<td align='center'><b>Defeito Reclamado</b></td>";
	echo "<td align='center'><b>Defeito Constatado</b></td>";
	echo "<td align='center'><b>Adicionar</b></td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	$sql_cons = "SELECT DISTINCT
					DR.defeito_reclamado                  ,
					DR.descricao           AS dr_descricao
			FROM tbl_os_defeito_reclamado_constatado RC
			LEFT JOIN tbl_defeito_reclamado          DR ON DR.defeito_reclamado  = RC.defeito_reclamado
			WHERE RC.os = $os
			AND   RC.defeito_reclamado IS NOT NULL";
	$res_dr = pg_query($con, $sql_cons);
	if(pg_num_rows($res_dr) > 0){
		for($x=0;$x<pg_num_rows($res_dr);$x++){
			$dr_defeito_reclamado  = pg_result($res_dr,$x,defeito_reclamado);
			$dr_descricao          = pg_result($res_dr,$x,dr_descricao);

			$aa = $x+1;

			if($cor=="#FFFFFF") $cor = "#e6eef7";
			else                $cor = "#FFFFFF";

			echo "<tr bgcolor='$cor'>";
			echo "<td valign='top'>";
			echo "<input type='hidden' name='defeito_reclamado_$aa' id='defeito_reclamado_$aa' value='$dr_defeito_reclamado'>";
			echo "<input type='hidden' name='defeito_reclamado_descricao_$aa' id='defeito_reclamado_descricao_$aa' value='$dr_descricao'>";
			echo "$dr_descricao";
			echo "</td>";

			echo "<td>";
			//HD 27570 - 21/7/2008
			/*echo "<select name='defeito_constatado_$aa' id='defeito_constatado_$aa' class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado_$aa.value,this);' >";
			echo "<option id='opcoes2' value=''></option>";*/
			$sql_consx = "SELECT distinct(tbl_diagnostico.defeito_constatado) AS defeito_constatado,
			tbl_defeito_constatado.descricao                         AS dc_descricao
			FROM tbl_diagnostico
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado
			WHERE tbl_diagnostico.linha = $linha
			AND tbl_defeito_constatado.ativo='t'
			ORDER BY tbl_defeito_constatado.descricao";
			$res_consx = pg_query($con, $sql_consx);
			if(pg_num_rows($res_consx)>0){
			echo "<select name='defeito_constatado_$aa' id='defeito_constatado_$aa' class='frm' style='width: 220px;'>";
				echo "<option value=''></option>";
				for($w=0; $w<pg_num_rows($res_consx); $w++){
					$defeito_constatado = pg_result($res_consx, $w, defeito_constatado);
					$dc_descricao       = pg_result($res_consx, $w, dc_descricao);
					echo "<option value='$defeito_constatado'>$dc_descricao</option>";
				}
			echo "</select>";
			}

				echo "<br><table id='tab_defeitos_$aa' name='tab_defeitos_$aa' style='font-size:12px;display:none' width='100%'>";
				echo "<thead><tr><td></td></tr></thead>";
				echo "<tbody>";
				$sql_cons = "SELECT DISTINCT
								DC.defeito_constatado                 ,
								DC.descricao           AS dc_descricao
						FROM tbl_os_defeito_reclamado_constatado RC
						JOIN tbl_defeito_constatado              DC ON DC.defeito_constatado = RC.defeito_constatado
						WHERE RC.os = $os
						AND   RC.defeito_reclamado = $dr_defeito_reclamado
						AND   RC.defeito_constatado IS NOT NULL";

				$res_dc = pg_query($con, $sql_cons);
				if(pg_num_rows($res_dc) > 0){
					for($y=0;$y<pg_num_rows($res_dc);$y++){
						$dc_defeito_constatado = pg_result($res_dc,$y,defeito_constatado);
						$dc_descricao          = pg_result($res_dc,$y,dc_descricao);
						$bb = $y+1;
						echo "<tr>";
						echo "<td style='text-align: left; color: #000000;font-size:10px;border-bottom: thin dotted #FF0000'><font size='1'><input type='hidden' name=\"i_defeito_constatado_".$aa."_".$bb."\" id=\"i_defeito_constatado_".$aa."_".$bb."\" rel=\"defeito_constatado_".$aa."\" value='$dc_defeito_constatado'>$dc_descricao</font></td>";
						echo "<td align='right'><input type='button' onclick='removerIntegridade2(this,\"tab_defeitos_$aa\");' value='Excluir'></td>";
						echo "</tr>";
					}
					echo "<script>document.getElementById('tab_defeitos_$aa').style.display = \"inline\";</script>";
				}
				echo "</tbody>";
				echo "</table>";
			echo "</td>";
			echo "<td valign='top'>";
			echo "<input type='button' onclick=\"javascript: adicionaIntegridade2('$aa','tab_defeitos_$aa',
			document.frm_os.defeito_reclamado_$aa,
			document.frm_os.defeito_reclamado_descricao_$aa,
			document.frm_os.defeito_constatado_$aa)\" value='Adicionar Defeito' name='btn_adicionar'>";
			echo "</td>";

			echo "</tr>";
		}
		$aa++;
	}
	echo "</tbody></table>";
}

//relacionamento de integridade termina aqui....

}
?>
<? if(!in_array($login_fabrica,array(6,3,24,30,11,19,50,51,59,2))) { ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<? if ($login_fabrica != 5) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
				<br>
<?
		if ($login_fabrica != 1) {
			$sql = "SELECT *
					FROM   tbl_defeito_reclamado
					JOIN   tbl_linha USING (linha)
					WHERE  tbl_defeito_reclamado.linha = $linha
					AND    tbl_linha.fabrica           = $login_fabrica
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_query ($con,$sql) ;

			if ($login_fabrica == 14) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_familia USING (familia)
						WHERE  tbl_defeito_reclamado.familia = $familia
						AND    tbl_familia.fabrica           = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_query ($con,$sql);
			}

			if ($login_fabrica == 52) {
				if (strlen($defeito_reclamado)>0 ){
					$sql = "SELECT *
							FROM   tbl_defeito_reclamado
							WHERE  tbl_defeito_reclamado.defeito_reclamado = $defeito_reclamado 
							ORDER BY tbl_defeito_reclamado.descricao;";
					$resD = pg_query ($con,$sql);
				}
			}



		if (pg_num_rows ($resD) == 0) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_linha USING (linha)
						WHERE  tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_query ($con,$sql) ;
			}
		}else{
			$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado ,
							tbl_defeito_reclamado.descricao
					FROM    tbl_defeito_reclamado
					JOIN    tbl_linha   ON tbl_linha.linha     = tbl_defeito_reclamado.linha
					JOIN    tbl_familia ON tbl_familia.familia = tbl_defeito_reclamado.familia
					JOIN    tbl_produto ON tbl_produto.familia = tbl_familia.familia
					WHERE   tbl_defeito_reclamado.familia = tbl_familia.familia
					AND     tbl_familia.fabrica           = $login_fabrica
					AND     tbl_produto.produto           = $produto_os
					ORDER BY tbl_defeito_reclamado.descricao";
			$resD = pg_query ($con,$sql);
		}

		if (@pg_num_rows ($resD) > 0 AND $login_fabrica <> 5 AND $login_fabrica <> 30 AND $login_fabrica <> 51) {
			echo "<select name='defeito_reclamado' size='1' class='frm'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_num_rows ($resD) ; $i++ ) {
				echo "<option ";
				if ($defeito_reclamado == pg_result ($resD,$i,defeito_reclamado) ) echo " selected ";
				echo " value='" . pg_result ($resD,$i,defeito_reclamado) . "'>" ;
				echo pg_result ($resD,$i,descricao) ;
				echo "</option>";
			}
			echo "</select>";
		}else{
			echo $defeito_reclamado_descricao;
		}
?>
			</td>
			<? } ?>

			<? if ($pedir_defeito_constatado_os_item != "f") { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<? // HD 73709
				if ($login_fabrica == 20) echo "Reparo";
				else echo "Defeito Constatado";
				?></font>
				<br>
				<select name="defeito_constatado" size="1" class="frm">
					<option selected></option>
				<?
				$sql =	"SELECT defeito_constatado_por_familia,
								defeito_constatado_por_linha
						FROM tbl_fabrica
						WHERE fabrica = $login_fabrica;";
				$res = pg_query ($con,$sql);
				$defeito_constatado_por_familia = pg_result ($res,0,0) ;
				$defeito_constatado_por_linha   = pg_result ($res,0,1) ;
				echo "//////////// $defeito_constatado_por_linha ////////////\n";

				if ($defeito_constatado_por_familia == 't') {

					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_familia
							JOIN   tbl_familia_defeito_constatado USING(familia)
							JOIN   tbl_defeito_constatado         USING(defeito_constatado)
							WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
							AND    tbl_familia_defeito_constatado.familia = $familia
							AND    tbl_defeito_constatado.ativo IS TRUE
							";
					// Coloquei AND    tbl_defeito_constatado.ativo IS TRUE // Fabio - 28-12-2007
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
				}else{
					if ($defeito_constatado_por_linha == 't') {
						$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
						$res   = pg_query ($con,$sql);
						$linha = pg_result ($res,0,0) ;

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado";
								if ($login_fabrica <> 2) {
								$sql .= " JOIN   tbl_linha USING(linha) ";
								}
								$sql .= " WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica ";
								if ($login_fabrica <> 2) {
								$sql .="AND    tbl_linha.linha = $linha";
								}
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}else{
						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado
								WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.codigo;";
					}
				}
				if($login_fabrica==15 or $login_fabrica==35){
				$sql="select * from tbl_defeito_constatado where fabrica=$login_fabrica and ativo is true order by descricao";
				}
				if ($login_fabrica == "20") {
					$sql = "SELECT tbl_defeito_constatado.*
							FROM tbl_defeito_constatado
							JOIN tbl_produto_defeito_constatado
								ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado
								AND tbl_produto_defeito_constatado.produto = $produto_os
							WHERE fabrica = $login_fabrica
							ORDER BY tbl_defeito_constatado.descricao";
				}
				$res = pg_query ($con,$sql) ;
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option ";
					if ($defeito_constatado == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
					echo pg_result ($res,$i,descricao) ." - ". pg_result ($res,$i,codigo) ;
					echo "</option>";
				}
				?>
				</select>
			</td>

			<? } ?>

			<? if ($pedir_causa_defeito_os_item != "f" && $login_fabrica != 5) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Causa Defeito</font>
				<br>
				<select name="causa_defeito" size="1" class="frm">
					<option selected></option>
				<?
				$sql = "SELECT tbl_causa_defeito.*
						FROM   tbl_causa_defeito
						WHERE  tbl_causa_defeito.fabrica = $login_fabrica
						ORDER BY tbl_causa_defeito.codigo, tbl_causa_defeito.descricao;";
				$res = pg_query ($con,$sql) ;

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					$causa_defeitoRes	= pg_result($res,$i,causa_defeito);
					$descricaoRes = "";
					if (strlen (trim (pg_result ($res,$i,codigo))) > 0) $descricaoRes = pg_result ($res,$i,codigo) . " - ";
					$descricaoRes .= pg_result($res,$i,descricao);
					if ($causa_defeito == $causa_defeitoRes)
						$sel = " selected ";
					else
						$sel = "";
					echo "<option value='$causa_defeitoRes' $sel>$descricaoRes</option>";
				}
				?>
				</select>
			</td>
			<? } ?>
		</tr>
		</table>

		<?if ($pedir_solucao_os_item <> 'f') {

		?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Solução</font>
				<br>
				<?if($linha <> 549) { ?>
				<select name="solucao_os" size="1" class="frm">
					<option value=""></option>
				<?
				if($login_fabrica == 1 or $login_fabrica == 35 ){

					$sql = "SELECT 	tbl_solucao.solucao,
							tbl_solucao.descricao
						FROM tbl_solucao";
					if($login_fabrica == 1){
						$sql .= " JOIN tbl_linha_solucao ON tbl_solucao.solucao = tbl_linha_solucao.solucao AND tbl_linha_solucao.linha = $linha ";
					}

					$sql.=" WHERE fabrica = $login_fabrica
						AND   ativo IS TRUE
						ORDER BY descricao";
					$res = pg_query($con, $sql);

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
						$aux_solucao_os    = pg_result ($res,$x,solucao);
						$solucao_descricao = pg_result ($res,$x,descricao);
						echo "<option id='opcoes' value='$aux_solucao_os' "; if($aux_solucao_os == $solucao_os) echo " SELECTED"; echo ">$solucao_descricao</option>";
					}

				}else{
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}

					if ($login_fabrica == 1) {
						if ($reembolso_peca_estoque == 't') {
							//a pedido de Fabiola, bloquear apenas troca de peça
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca de peça%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
							if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
							if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
						}
					}
					if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS NOT TRUE ";
					$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";

					$res = pg_query ($con,$sql) ;

					if (pg_num_rows($res) == 0) {
						$sql = "SELECT *
								FROM   tbl_servico_realizado
								WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

						if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
							$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						}

						if ($login_fabrica == 1) {
							if ($reembolso_peca_estoque == 't') {
								$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
								$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
							}else{
								$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
								$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
							}
						}
						$sql .=	" AND tbl_servico_realizado.linha IS NULL
								AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
						$res = pg_query ($con,$sql) ;
					}

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
						echo "<option ";
						if ($solucao_os == pg_result ($res,$x,servico_realizado)) echo " selected ";
						echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
						echo pg_result ($res,$x,descricao) ;
						if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
						echo "</option>";
					}
				}
				?>
				</select>
				
				<?}else{?>
					<input type ='text' name='solucao_os2' maxlength='50' value='' size='30'  class='frm'>
				<?}?>
			</td>
			
			<?if($login_fabrica == 1 and  $tipo_os == 13) {
				$sql = " SELECT tipo_atendimento FROM tbl_os where os = $os";
				$res = pg_query($con,$sql);
				$tipo_atendimento = pg_fetch_result($res,0,0);
				echo "<td>";
				echo "<select name='tipo_atendimento' id='tipo_atendimento' style='width:230px;'>";

				$sql = "SELECT * 
						FROM tbl_tipo_atendimento 
						WHERE fabrica = $login_fabrica
						AND   ativo IS TRUE 
						AND   tipo_atendimento in(64,65,69) 
						ORDER BY tipo_atendimento ";
				$res = pg_query ($con,$sql) ;

				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option ";
					if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) {
						echo " selected ";
					}
					echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
					echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
					echo "</option>";
				}
				echo "</select>";
				echo "</td>";
			}
			?>
		</tr>
		</table>



		<?
		}
		?>
<?
}

// SOMENTE LORENZETTI
if ($login_fabrica == 19){
    echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
    echo "<tr>";
    echo "<td align='left' nowrap>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nome do Técnico</font>";
    echo "<br>";
    echo "<input type='text' name='tecnico_nome' size='20' maxlength='20' value='$tecnico_nome'>";
    echo "</td>";
    /*echo "<td align='left' nowrap>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Mês e Ano de Fabricação do Produto</font>";
    echo "<br>";
    echo "<input type='text' name='mes_ano' id='mes_ano' size='16' maxlength='20' value='$mes_ano'>";
    echo "</td>";
    echo "<td align='left' nowrap>";
    echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Sequência</font>";
    echo "<br>";
    echo "<input type='text' name='sequencia' size='20' maxlength='2' value='$sequencia'>";
    echo "</td>";*/
    echo "</tr>";
    echo "</table>";
}



if (strlen($troca_faturada) == 0) {

		### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                   ,
							tbl_os_item.pedido                                    ,
							tbl_os_item.qtde                                      ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                   ,
							tbl_peca.descricao                                    ,
							tbl_defeito.defeito                                   ,
							tbl_defeito.descricao AS defeito_descricao            ,
							tbl_produto.referencia AS subconjunto                 ,
							tbl_os_produto.produto                                ,
							tbl_os_produto.serie                                  ,
							tbl_servico_realizado.servico_realizado               ,
							tbl_servico_realizado.descricao AS servico_descricao  ,
							tbl_pedido.pedido_blackedecker  AS pedido_blackedecker,
							tbl_pedido.pedido_acessorio     AS pedido_acessorio
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					LEFT JOIN    tbl_pedido            ON tbl_pedido.pedido = tbl_os_item.pedido
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido NOTNULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que possuem pedidos</b></font></td>\n";

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$faturado      = pg_num_rows($res);
						$fat_item      = pg_result($res,$i,os_item);
						$fat_pedido    = pg_result($res,$i,pedido);
						$fat_peca      = pg_result($res,$i,referencia);
						$fat_descricao = pg_result($res,$i,descricao);
						$fat_qtde      = pg_result($res,$i,qtde);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";

						#------- Bloquado exclusão de item de OS pelo TULIO.... 14/02
						#------- Admin exclui e depois distribuidor fica reclamando que mudou numero do pedido
						#------- Caso do GRALA x BRITANIA
						#------- Se for excluir, temos que mandar email pra todos os envolvidos... Posto, Distribuidor e ADMIN
						#------- Por enquanto, não excluir

						#------- Liberado de novo em 16/02
						#------- Herio disse que vai apagar no EMS os pedidos antigos
						echo "<img src='imagens/btn_x.gif' width='15' height='12' onclick=\"javascript: if(confirm('Deseja realmente excluir o item da OS?') == true){ window.location='$PHP_SELF?os_item=$fat_item&os=$os';}\" style='cursor:pointer;'>&nbsp;&nbsp;";

						if ($login_fabrica == 1) {
							$fat_pedido = trim(pg_result ($res,$i,pedido_blackedecker));
							$pedido_acessorio    = trim(pg_result ($res,$i,pedido_acessorio));
							if ($pedido_acessorio == 't') $fat_pedido = intval($pedido_blackedecker + 1000);
						}

						echo "$fat_pedido</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>\n";

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS QUE ESTÃO COMO NÃO LIBERADAS PARA PEDIDO EM GARANTIA
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.porcentagem_garantia                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.liberacao_pedido IS FALSE
					AND     tbl_os_item.liberacao_pedido           IS FALSE
					AND     tbl_os_item.liberacao_pedido_analisado IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center' colspan='6'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia</b></font></td>\n";
				}else{
					if ($login_fabrica <> 6) {
						echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia ou com pedido bloqueado</b></font></td>\n";
					}else{
						echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças pendentes</b></font></td>\n";
					}
				}

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Ação</b></font></td>\n";
				}

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Valor</b></font></td>\n";
				}

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_result($res,$i,os_item);
						$rec_obs       = pg_result($res,$i,obs);
						$rec_peca      = pg_result($res,$i,referencia);
						$rec_descricao = pg_result($res,$i,descricao);
						$rec_qtde      = pg_result($res,$i,qtde);
						$rec_preco     = pg_result($res,$i,porcentagem_garantia);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						if ($login_fabrica == 14) {
							echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='$PHP_SELF?liberar=$rec_item&os=$os'>LIBERAR ITEM</a></font></td>\n";
						}

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						if ($login_fabrica == 14) {
							echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>". str_replace(".",",",$rec_preco) ."</font></td>\n";
						}

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}

		### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSI PEDIDO
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.porcentagem_garantia                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_query ($con,$sql) ;

			if(pg_num_rows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center' colspan='5'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";
				}else{
					echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";
				}

				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";

				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Valor</b></font></td>\n";
				}

				echo "</tr>\n";

				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
						$recusado      = pg_num_rows($res);
						$rec_item      = pg_result($res,$i,os_item);
						$rec_obs       = pg_result($res,$i,obs);
						$rec_peca      = pg_result($res,$i,referencia);
						$rec_descricao = pg_result($res,$i,descricao);
						$rec_qtde      = pg_result($res,$i,qtde);
						$rec_preco     = pg_result($res,$i,porcentagem_garantia);

						echo "<tr height='20' bgcolor='#FFFFFF'>";

						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";

						if ($login_fabrica == 14) {
							echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>". str_replace(".",",",$rec_preco) ."</font></td>\n";
						}

						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}
		if(strlen($os) > 0 and $login_fabrica == 6){
		/*HD 2599*/
	$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_lista_basica.qtde
			FROM  tbl_lista_basica
			JOIN  tbl_peca using(peca)
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND   tbl_lista_basica.produto = $produto_os
			AND   tbl_peca.item_aparencia  = 'f'
			AND   tbl_peca.pre_selecionada = 't'
			Order by tbl_peca.referencia";
//echo $sql;
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){
	echo "<table width='90%' border='0' cellspacing='2' cellpadding='0'>";
	echo "<tr height='20' bgcolor='#666666'>";
	echo "<td align='center' colspan='5'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças Pré-lançadas</b></font></td>";
	echo "</tr>";
	echo "<tr height='20' bgcolor='#666666'>";
	echo "<td align='center' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Código</b></font>&nbsp;&nbsp;&nbsp;<a class='lnk' href='peca_consulta_por_produto";
	if($login_fabrica==6)echo "_tectoy";
	echo ".php?produto=$produto_os";
		if($login_fabrica==6)echo "&os=$os";
		echo "' target='_blank'><font color='#FFFFFF'>Lista Básica</font></a></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
	echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";
	echo "</tr>";
		for($x=0;pg_num_rows($res)>$x;$x++){
			$ypeca_referencia = pg_result($res,$x,referencia);
			$ypeca_descricao  = pg_result($res,$x,descricao);
			$yqtde            = pg_result($res,$x,qtde);
			$ypeca            = pg_result($res,$x,peca);

			echo "<tr>";

			echo "<td align='center'><input class='frm' type='checkbox' name='pre_peca_$x' value='$ypeca'>&nbsp;<font face='arial' size='-2' color='#000000'> $ypeca_referencia </font> </td>";
		//	echo "<td width='60' align='center'></TD>";

			echo "<td align='left'><font face='arial' size='-2' color='#000000'>$ypeca_descricao</font></td>\n";

			echo "<td align='center'><font face='arial' size='-2' color='#000000'>$yqtde</font><input type='hidden' name='pre_qtde_$x' value='$yqtde'></td>\n";

			echo "<td>";
			echo "<select name='pre_defeito_$x'  class='frm' style='width:170px;'>";
			echo "<option></option>";
			$sql = "SELECT 	tbl_defeito.defeito,
							tbl_defeito.descricao
					FROM tbl_peca_defeito
					JOIN tbl_defeito using(defeito)
					WHERE peca = $ypeca
					AND tbl_peca_defeito.ativo = 't'
					ORDER BY tbl_defeito.descricao";
			$zres = pg_query($con,$sql);
			if(pg_num_rows($zres)>0){
				for($z=0;pg_num_rows($zres)>$z;$z++){
					$zdefeito   = pg_result($zres,$z,defeito);
					$zdescricao = pg_result($zres,$z,descricao);
					echo "<option value='$zdefeito'>$zdescricao</option>";
				}
			}
			echo "</select>";
			echo "</td>";

			echo "<td>";
			echo "<select class='frm' size='1' name='pre_servico_$x'  style='width:150px;'>";
			echo "<option></option>";
			$sql = "SELECT tbl_servico_realizado.servico_realizado, tbl_servico_realizado.descricao
					FROM tbl_peca_servico join tbl_servico_realizado using(servico_realizado)
					where tbl_peca_servico.ativo = 't'
					and tbl_peca_servico.peca = $ypeca
					order by tbl_servico_realizado.descricao";
			$zres = pg_query($con,$sql);
			if(pg_num_rows($zres)>0){
				for($z=0;pg_num_rows($zres)>$z;$z++){
					$zservico_realizado   = pg_result($zres,$z,servico_realizado);
					$zdescricao = pg_result($zres,$z,descricao);
					echo "<option value='$zservico_realizado'>$zdescricao</option>";
				}
			}
			echo "</select>";
			echo "</td>";

			echo "</tr>";
		}
		echo "<input type='hidden' name='pre_total' value='$x'>\n";
	echo "</table>";
	}
/*HD 2599*/
		}


		if(strlen($os) > 0 AND strlen ($msg_erro) == 0){
			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia;";
				$resX = @pg_query($con,$sql);
				$inicio_itens = @pg_num_rows($resX);
			}else{
				$inicio_itens = 0;
			}
//aqui
			$sql = "SELECT  tbl_os_item.os_item                                                ,
							tbl_os_item.os_produto                                             ,
							tbl_os_item.pedido                                                 ,
							tbl_os_item.qtde                                                   ,
							tbl_os_item.liberacao_pedido                                       ,
							tbl_os_item.obs                                                    ,
							tbl_os_item.posicao                                                ,
							tbl_os_item.causa_defeito                                          ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                                ,
							tbl_peca.descricao                                                 ,
							tbl_defeito.defeito                                                ,
							tbl_defeito.descricao                   AS defeito_descricao       ,
							tbl_produto.referencia                  AS subconjunto             ,
							tbl_os_produto.produto                                             ,
							tbl_os_produto.serie                                               ,
							tbl_servico_realizado.servico_realizado                            ,
							tbl_servico_realizado.descricao         AS servico_descricao       ,
							tbl_causa_defeito.descricao             AS causa_defeito_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN tbl_defeito              USING (defeito)
					LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
					LEFT JOIN tbl_causa_defeito ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido                     IS NULL
					AND     tbl_os_item.liberacao_pedido_analisado IS FALSE
					ORDER BY tbl_os_item.os_item;";
			$res = pg_query ($con,$sql) ;

			if (pg_num_rows($res) > 0) {
				$fim_itens = $inicio_itens + pg_num_rows($res);
				$i = 0;
				for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
					$os_item[$i]                 = pg_result($res,$i,os_item);
					$os_produto[$i]              = pg_result($res,$i,os_produto);
					$pedido[$k]                  = pg_result($res,$i,pedido);
					$peca[$k]                    = pg_result($res,$i,referencia);
					$qtde[$k]                    = pg_result($res,$i,qtde);
					$posicao[$k]                 = pg_result($res,$i,posicao);
					$produto[$k]                 = pg_result($res,$i,subconjunto);
					$serie[$k]                   = pg_result($res,$i,serie);
					$descricao[$k]               = pg_result($res,$i,descricao);
					$defeito[$k]                 = pg_result($res,$i,defeito);
					$defeito_descricao[$k]       = pg_result($res,$i,defeito_descricao);
					$pcausa_defeito[$k]          = pg_result($res,$i,causa_defeito);
					$causa_defeito_descricao[$k] = pg_result($res,$i,causa_defeito_descricao);
					$servico[$k]                 = pg_result($res,$i,servico_realizado);
					$servico_descricao[$k]       = pg_result($res,$i,servico_descricao);
					$admin_peca[$k]              = pg_result($res,$i,admin_peca);//aqui

					if(strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }

					$i++;

				}
			}else{
				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$os_item[$i]        = $_POST["os_item_"        . $i];
					$os_produto[$i]     = $_POST["os_produto_"     . $i];
					$produto[$i]        = $_POST["produto_"        . $i];
					$serie[$i]          = $_POST["serie_"          . $i];
					$posicao[$i]        = $_POST["posicao_"        . $i];
					$peca[$i]           = $_POST["peca_"           . $i];
					$qtde[$i]           = $_POST["qtde_"           . $i];
					$defeito[$i]        = $_POST["defeito_"        . $i];
					$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
					$servico[$i]        = $_POST["servico_"        . $i];
					$admin_peca[$i]     = $_POST["admin_peca_"     . $i]; //aqui

					if (strlen($peca[$i]) > 0) {
						$sql = "SELECT  tbl_peca.referencia,
										tbl_peca.descricao
								FROM    tbl_peca
								WHERE   tbl_peca.fabrica    = $login_fabrica
								AND     tbl_peca.referencia = $peca[$i];";
						$resX = @pg_query ($con,$sql) ;

						if (@pg_num_rows($resX) > 0) {
							$descricao[$i] = trim(pg_result($resX,0,descricao));
						}
					}
				}
			}
		}else{
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$os_item[$i]        = $_POST["os_item_"        . $i];
				$os_produto[$i]     = $_POST["os_produto_"     . $i];
				$produto[$i]        = $_POST["produto_"        . $i];
				$serie[$i]          = $_POST["serie_"          . $i];
				$posicao[$i]        = $_POST["posicao_"        . $i];
				$peca[$i]           = $_POST["peca_"           . $i];
				$qtde[$i]           = $_POST["qtde_"           . $i];
				$defeito[$i]        = $_POST["defeito_"        . $i];
				$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
				$servico[$i]        = $_POST["servico_"        . $i];
				$admin_peca[$i]     = $_POST["admin_peca_"     . $i];//aqui


				if (strlen($peca[$i]) > 0) {
					$sql = "SELECT  tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$peca[$i]';";
					$resX = @pg_query ($con,$sql) ;

					if (@pg_num_rows($resX) > 0) {
						$descricao[$i] = trim(pg_result($resX,0,descricao));
					}
				}
			}
		}

		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";

		if ($os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
		}

		if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
				if($login_fabrica==35){
					echo "PO#";
				}else{
					echo "N. Série";
				}
			echo "</b></font></td>";
		}

		if ($login_fabrica == 14) echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";

		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Código</b></font>&nbsp;&nbsp;&nbsp;<a class='lnk' href='peca_consulta_por_produto";
		if($login_fabrica==6)echo "_tectoy";
		echo ".php?produto=$produto_os";
		if($login_fabrica==6)echo "&os=$os";
		echo "' target='_blank'><font color='#FFFFFF'>Lista Básica</font></a></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";

		if ($pergunta_qtde_os_item == 't')
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";

		if ($pedir_causa_defeito_os_item == 't')
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Causa</b></font></td>";

		if($login_fabrica<>20){
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Defeito</b></font></td>";
		}
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Serviço</b></font></td>";

		echo "</tr>";


		$loop = $qtde_item;
#		if (strlen($faturado) > 0) $loop = $qtde_item - $faturado;

		if($login_fabrica == 6) $loop = $loop+5;

		$offset = 0;
		// HD 20655 21313
		if($login_fabrica== 45){
			$loop = $loop+7;
			/*$sql="SELECT qtde_os_item
					FROM tbl_os
					JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
					where os      = $os
					and   tbl_os.fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			$qtde_os_item=pg_result($res,0,qtde_os_item);
			$loop=$qtde_os_item;*/
		}
		for ($i = 0 ; $i < $loop ; $i++) {
			echo "<tr>";

			if ($os_item_subconjunto == 'f') {
				echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
				echo "<input type='hidden' name='descricao'>";
				echo "<input type='hidden' name='preco'>";
				echo "<input type='hidden' name='os_item_$i' value='$os_item[$i]'>";//aqui
				echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>";//aqui
				echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui
			}else{
				echo "<td align='center'>";

				echo "<input type='hidden' name='descricao'>";
				echo "<input type='hidden' name='preco'>";
				echo "<input type='hidden' name='os_item_$i' value='$os_item[$i]'>";//aqui
				echo "<input type='hidden' name='os_produto_$i' value='$os_produto[$i]'>";//aqui
				echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui
				echo "<select class='frm' size='1' name='produto_$i'>";
				#echo "<option></option>";

				$sql = "SELECT  tbl_produto.produto   ,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM    tbl_subproduto
						JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
						WHERE   tbl_subproduto.produto_pai = $produto_os
						ORDER BY tbl_produto.referencia;";
				$resX = pg_query ($con,$sql) ;

				echo "<option value='$produto_referencia' ";
				if ($produto[$i] == $produto_referencia) echo " selected ";
				echo " >$produto_descricao</option>";

				for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++ ) {
					echo "<option ";
					if (trim ($produto[$i]) == trim (pg_result ($resX,$x,referencia))) echo " selected ";
					echo " value='" . pg_result ($resX,$x,referencia) . "'>" ;
					echo pg_result ($resX,$x,referencia) . " - " . substr(pg_result ($resX,$x,descricao),0,15) ;
					echo "</option>";
				}

				echo "</select>";
				echo "</td>";
			}

			if ($os_item_subconjunto == 'f') {
				$xproduto = $produto[$i];
				echo "<input type='hidden' name='serie_$i'>";
			}else{
				if ($os_item_serie == 't') {
					echo "<td align='center'><input class='frm' type='text' name='serie_$i' size='9' value='$serie[$i]'></td>";
				}
			}

			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca      ,
								tbl_peca.referencia,
								tbl_peca.descricao ,
								tbl_lista_basica.qtde
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia
						LIMIT 1 OFFSET $offset;";
				$resX = @pg_query ($con,$sql) ;

				if (@pg_num_rows($resX) > 0) {
					$xpeca       = trim(pg_result($resX,0,peca));
					$xreferencia = trim(pg_result($resX,0,referencia));
					$xdescricao  = trim(pg_result($resX,0,descricao));
					$xqtde       = trim(pg_result($resX,0,qtde));

					if ($peca[$i] == $xreferencia)
						$check = " checked ";
					else
						$check = "";

//					echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$xreferencia'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
//					echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$xdescricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";

					echo "<td align='left'><input class='frm' type='checkbox' name='peca_$i' value='$xreferencia' $check>&nbsp;<font face='arial' size='-2' color='#000000'>$xreferencia</font></td>";
					echo "<td align='left'><font face='arial' size='-2' color='#000000'>$xdescricao</font></td>";
					echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xqtde</font><input type='hidden' name='qtde_$i' value='$xqtde'></td>";

					if ($login_fabrica == 6) {
					    if (strlen ($defeito[$i]) == 0) $defeito[$i] = 78 ;
					    if (strlen ($servico[$i]) == 0) $servico[$i] = 1 ;
					}
				}else{
					echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i, document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
					echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
					if ($pergunta_qtde_os_item == 't') {
						echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";
					}
				}
			}else{
				if ($login_fabrica == 14) echo "<td align='center'><input class='frm' type='text' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'></td>\n";

				echo "<td align='center' nowrap><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'";
				if ($login_fabrica == 5) echo "onblur=\"javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, 'referencia', document.frm_os.qtde_$i)\"";
				echo ">&nbsp;<a href='#'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\" )'";
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></a></td>";

				echo "<td align='center' nowrap><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'";
				if ($login_fabrica == 5) echo "onblur=\"javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, 'descricao')\"";
				echo ">&nbsp;<a href='#'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\" )'";
				echo " alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></a></td>";
				if ($pergunta_qtde_os_item == 't') {
					echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";
				}
			}

			##### C A U S A   D O   D E F E I T O   D O   I T E M #####
			if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='pcausa_defeito_$i'>";
				echo "<option selected></option>";

				$sql =	"SELECT *
						FROM tbl_causa_defeito
						WHERE fabrica = $login_fabrica
						ORDER BY codigo, descricao";
				$res = pg_query ($con,$sql) ;

				for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
					echo "<option ";
					if ($pcausa_defeito[$i] == pg_result ($res,$x,causa_defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,causa_defeito) . "'>" ;
					echo pg_result ($res,$x,codigo) ;
					echo " - ";
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}

				echo "</select>";
				echo "</td>\n";
			}

			##### D E F E I T O   D O   I T E M #####
			echo "<td align='center'>";
			echo "<select class='frm' size='1' style='width:250px' width='400' name='defeito_$i'";
			if ($login_fabrica == 50){
				$sql_cond = "AND tbl_defeito.defeito = $defeito[$i]";
				echo " style='width:150px;' onfocus='defeitoLista(document.frm_os.peca_$i.value,$i,$os);'";
			}
			echo " >";
			echo "<option ";
			if ($login_fabrica == 50){
				echo " id='op_$i' value=''";
			}else{
				echo " selected";
			}
			echo " ></option>";

			$sql = "SELECT *
					FROM   tbl_defeito
					WHERE  tbl_defeito.fabrica = $login_fabrica
					$sql_cond
					AND    tbl_defeito.ativo IS TRUE
					ORDER BY descricao;";
			$res = pg_query ($con,$sql) ;

			for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
				echo "<option ";
				if ($defeito[$i] == pg_result ($res,$x,defeito)) echo " selected ";
				echo " value='" . pg_result ($res,$x,defeito) . "'>" ;
				if (strlen(trim(pg_result($res,$x,codigo_defeito))) > 0) {
					echo pg_result($res,$x,codigo_defeito);
					echo " - " ;
				}
				echo pg_result($res,$x,descricao);
				echo "</option>";
			}

			echo "</select>";
			echo "</td>";

			echo "<td align='center'>";
			# HD 107402 aletrado o tamanho do combo para
			echo "<select class='frm' size='1' name='servico_$i' style='width:340px'>";
			echo "<option selected></option>";


 #### SERVIÇO REALIZADO #####
			$sql = "SELECT *
					FROM   tbl_servico_realizado
					WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

			if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha IS NULL) ";
			//(tbl_servico_realizado.linha = '203' OR tbl_servico_realizado.linha IS NULL)

			if ($login_fabrica == 1) {
				if ($reembolso_peca_estoque == 't')
					$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				else
					$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'subst%' ";
			}
			if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";

			$sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
			$sql .= "ORDER BY gera_pedido DESC, descricao ASC;";

			$res = pg_query($con,$sql) ;

			if (pg_num_rows($res) == 0) {
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

				if ($login_fabrica == 1) {
					if ($reembolso_peca_estoque == 't')
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					else
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'subst%' ";
				}

				$sql .= "AND tbl_servico_realizado.linha IS NULL ";
				$sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
				if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";
				$sql .= "ORDER BY gera_pedido DESC, descricao ASC;";

				$res = pg_query($con,$sql) ;
			}

			for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
				echo "<option ";
				if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " selected ";
				echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
				echo pg_result ($res,$x,descricao) ;
				if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
				echo "</option>";
			}

			echo "</select>";
			echo "</td>";

			echo "</tr>";

			$offset = $offset + 1;
		}
// 		echo "$teste<BR>$teste2";
		echo "</table>";
		?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>
<?
if($compressor=='t' or ($tipo_os == 13 )){

	$sql = "SELECT  tbl_os.tipo_atendimento
			FROM    tbl_os
			WHERE   tbl_os.os = $os";
	$res = pg_query ($con,$sql) ;

	if (@pg_num_rows($res) > 0) {
		$tipo_atendimento = pg_fetch_result($res,0,tipo_atendimento) ;
	}

	$sql_tec = "SELECT tecnico from tbl_os_extra where os=$os";
	$res_tec = pg_query($con,$sql_tec);
	$tecnico            = trim(@pg_fetch_result($res_tec,0,tecnico));

	if(strlen($msg_erro) >0) {
		$tecnico = $_POST['tecnico'];
	}

	if($compressor=='t' or ($tipo_os == 13 and ($tipo_atendimento == 65 or $tipo_atendimento == 69))) {
		$sql_posto = "SELECT contato_endereco AS endereco,
						contato_numero   AS numero  ,
						contato_bairro   AS bairro  ,
						contato_cidade   AS cidade  ,
						contato_estado   AS estado  ,
						contato_cep      AS cep     ,
						consumidor_endereco         ,
						consumidor_numero           ,
						consumidor_bairro           ,
						consumidor_cidade           ,
						consumidor_estado           ,
						consumidor_cep              
					FROM tbl_os
					JOIN tbl_posto_fabrica USING(posto)
					WHERE tbl_os.posto   = $login_posto
					AND   tbl_os.os = $os
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.fabrica = $login_fabrica";

		$res_posto = pg_query($con,$sql_posto);
		if(pg_num_rows($res_posto)>0) {
			$endereco_posto = pg_fetch_result($res_posto,0,endereco).', '.pg_fetch_result($res_posto,0,numero).' '.pg_fetch_result($res_posto,0,bairro).' '.pg_fetch_result($res_posto,0,cidade).' '.pg_fetch_result($res_posto,0,estado);
			$cep_posto = pg_fetch_result($res_posto,0,cep);
			$endereco_consumidor = pg_fetch_result($res_posto,0,consumidor_endereco).', '.pg_fetch_result($res_posto,0,consumidor_numero).' '.pg_fetch_result($res_posto,0,consumidor_bairro).' '.pg_fetch_result($res_posto,0,consumidor_cidade).' '.pg_fetch_result($res_posto,0,consumidor_estado);
			$cep_consumidor = pg_fetch_result($res_posto,0,consumidor_cep);
			if(strlen($distancia_km)==0) $distancia_km=0;
		}

		echo "<BR>";
		echo "<table width='600' border='1' align='center'  cellpadding='1' cellspacing='3 class='border'>";
			echo "<tr>";
			echo "<td nowrap colspan='100%' class='menu_top'><B><font size='2' face='Geneva, Arial, Helvetica, san-serif'>OUTRAS DESPESAS</font></b></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td nowrap class='menu_top' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
			echo "<td nowrap class='menu_top' rowspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
			if($tipo_os == 13){
				echo "<td nowrap class='menu_top' rowspan='2'>
					<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtd. Produto<br>Atendido</font></td>";
			}
			echo "<td nowrap class='menu_top' colspan='2'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td nowrap class='menu_top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
			echo "<td nowrap class='menu_top'>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
			echo "</tr>";
			$sql  = "SELECT tbl_os_visita.os_visita ,
					to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data             ,
					to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente ,
					to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente   ,
					tbl_os_visita.km_chegada_cliente                                               ,
					tbl_os_visita.justificativa_valor_adicional                                    ,
					tbl_os_visita.valor_adicional                                                  ,
					tbl_os_visita.qtde_produto_atendido
				FROM    tbl_os_visita
				WHERE   $sql_aux_os      = $aux_os 
				ORDER BY tbl_os_visita.os_visita;";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);


			for ($y=0;$qtde_visita>$y;$y++){
				$os_visita            = trim(@pg_fetch_result($res,$y,os_visita));
				$visita_data          = trim(@pg_fetch_result($res,$y,data));
				$hr_inicio            = trim(@pg_fetch_result($res,$y,hora_chegada_cliente));
				$hr_fim               = trim(@pg_fetch_result($res,$y,hora_saida_cliente));
				$visita_km            = trim(@pg_fetch_result($res,$y,km_chegada_cliente));
				$qtde_produto_atendido= trim(@pg_fetch_result($res,$y,qtde_produto_atendido));
				$justificativa_adicionais = trim(@pg_fetch_result($res,$y,justificativa_valor_adicional));
				$valores_adicionais       = trim(@pg_fetch_result($res,$y,valor_adicional));
				$qtde_produto_atendido= trim(@pg_fetch_result($res,$y,qtde_produto_atendido));
				
				if(!empty($msg_erro)) {
					$os_visita               = $_POST['os_visita_'.$y];
					$visita_data             = $_POST['visita_data_'.$y];
					$hr_inicio               = $_POST['visita_hr_inicio_'.$y];
					$hr_fim                  = $_POST['visita_hr_fim_'.$y];
					$visita_km               = $_POST['visita_km_'.$y];
					$qtde_produto_atendido   = $_POST['qtde_produto_atendido_'.$y];
					$valores_adicionais      = $_POST['valores_adicionais_'.$y];
					$justificativa_adicionais= $_POST['justificativa_adicionais_'.$y];
				}

				if(strlen($visita_km_erro) > 0) {
					if(strlen($_POST['visita_km_'.$y]) > 0) {
						$visita_km = $visita_km_erro;
					}
				}

				echo "<tr>";
				echo "<td nowrap align='center' width='200'>";
				echo "<INPUT TYPE='text' NAME='visita_data_$y' value='$visita_data' size='12' maxlength='10' class='frm' onKeyUp=\"formata_data_visita(this.value, 'frm_os', $y)\";>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>dd/mm/aaaa</font>";
				echo "</td>";

				echo "<td nowrap align='center'>";
				echo "<input type='hidden' name='km_conferencia_$y' id='km_conferencia_$y'>";
				echo "<INPUT TYPE='text' NAME='visita_km_$y' id='visita_km_$y' onfocus=\"initialize('','visita_km_$y','km_conferencia_$y')\" value='$visita_km' size='4' maxlength='4' class='frm'>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Km</font>";
				echo "</td>";
				if($tipo_os ==13){
					$sql = "
						SELECT  count(os_revenda_item) as qtde_itens_geo
						FROM    tbl_os_revenda_item
						WHERE  $sql_aux_os      = $aux_os ";
					$res_count = pg_query ($con,$sql) ;

					$qtde_itens_geo = pg_fetch_result($res_count,0,qtde_itens_geo);

					if($y == 0 and strlen($qtde_produto_atendido) ==0){
						$qtde_produto_atendido = $qtde_itens_geo;
					}

					echo "<td nowrap align='center'>";
					echo "<INPUT TYPE='text' NAME='qtde_produto_atendido_$y' value='$qtde_produto_atendido' size='4' maxlength='4' class='frm'>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'></font>";
					echo "</td>";
				}

				echo "<td nowrap align='center'>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>R$ </font>";
				echo "<INPUT TYPE='text' onKeyDown=\"FormataValor(this,11, event)\"; NAME='valores_adicionais_$y' value='$valores_adicionais' size='5' maxlength='5' class='frm'>";
				echo "</td>";

				echo "<td nowrap align='center'>";
				echo "<INPUT TYPE='text' NAME='justificativa_adicionais_$y' value='$justificativa_adicionais' size='10' maxlength='50' class='frm'>";
				echo "<input type='hidden' name='os_visita_$y' value='$os_visita'>";
				echo "</td>";
				echo "</tr>";
			}
		echo "</table> <BR>";
	}
?>
	<center>
	<div id="mapa3"></div><br>
	<div id="mapa2" style=" width:500px; height:10px;position:absolute;visibility:hidden;">
	<a href='javascript:escondermapa();'>Fechar Mapa</a>
	</div><br>
	<div id="mapa" style=" width:600px; height:400px;visibility:hidden;position:absolute;border: 1px #FF0000 solid; "></div>
	<div id="trajeto" style="width: 400px; text-align:right;position:static"></div>
	</center>
	<script src="http://maps.google.com/maps?file=api&amp;v=3&amp;key=ABQIAAAA4k5ZzVjDVAWrCyj3hmFzTxR_fGCUxdSNOqIGjCnpXy7SRGDdcRTb85b5W8d9rUg4N-hhOItnZScQwQ" type="text/javascript"></script>
	<script language="javascript">
	var map;
	function initialize(busca_por,visita,confere){
		// Carrega o Google Maps
		$('#trajeto').html('');
		var visita = document.getElementById(visita);
		var confere = document.getElementById(confere);
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("mapa"));
			map.setCenter(new GLatLng(-25.429722,-49.271944), 11)
			map.addControl(new GLargeMapControl3D());
			gdir = new GDirections(map, document.getElementById("trajeto"));

			 var dir = new GDirections(map);

			var pt1 = '<?=$cep_posto?>';
			var pt2 = '<?=$cep_consumidor?>';

			if (pt1.length != 8 || pt2.length !=8) {
				busca_por = 'endereco';
			}else{
				pt1 = pt1.substr(0,5) + '-' + pt1.substr(5,3);
				pt2 = pt2.substr(0,5) + '-' + pt2.substr(5,3);
			}


			if (busca_por == 'endereco'){
				var pt1 = '<?=$endereco_posto?>';
				var pt2 = '<?=$endereco_consumidor?>';
			}

			dir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
			GEvent.addListener(dir,"load", function() {
				for (var i=0; i<dir.getNumRoutes(); i++) {
						var route = dir.getRoute(i);
						var dist = route.getDistance()
						var x = dist.meters*2/1000;
						var y = x.toString().replace(".",",");
						var valor_calculado = parseFloat(x);
						
						if (valor_calculado==0 && busca_por != 'endereco'){
							initialize('endereco','','');
							return false;
						}
	
						if (valor_calculado==0 && busca_por == 'endereco'){
							$('#mapa3').html('');
							return false;
						}
						confere.value = x;
						$('#mapa3').html('Distância calculada <a href= "javascript:vermapa();">Ver mapa</a>').addClass('mensagem');
						setDirections(""+pt1, ""+pt2, "pt-br");
				 }
			});
			GEvent.addListener(dir,"error", function() {
				return false;
			});

		}
	}

	function vermapa(){
		document.getElementById("mapa").style.visibility="visible";
		document.getElementById("mapa2").style.visibility="visible";
	}
	function escondermapa(){
		document.getElementById("mapa").style.visibility="hidden";
		document.getElementById("mapa2").style.visibility="hidden";
	}

	function setDirections(fromAddress, toAddress, locale) {
		gdir.load("from: " + fromAddress + " to: " + toAddress,
		{ "locale": locale , "getSteps":true});
	}

	</script>
	
	
<?
	echo "<table class='border' width='620' align='center' border='1' cellpadding='1' cellspacing='3'>";
	echo "<tr>";
		echo "<td class='menu_top'>Relatório do Técnico</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<TD class='table_line'><TEXTAREA NAME='tecnico' ROWS='5' COLS='85'>$tecnico </TEXTAREA></TD>";
	echo "</tr>";
	echo "</table>";
	echo "<br/>";
}
?>
<table width='650' align='center' border='0' cellspacing='0' cellpadding='5'>
<? if ($login_fabrica == 19) { ?>
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Valores Adicionais:</FONT>
		<br>
		<FONT SIZE="1">R$ </FONT>
		<INPUT TYPE="text" NAME="valores_adicionais" value="<? echo $valores_adicionais ?>" size="10" maxlength="10" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Justificativa dos Valores Adicionais:</FONT>
		<br>
		<INPUT TYPE="text" NAME="justificativa_adicionais" value="<? echo $justificativa_adicionais ?>" size="30" maxlength="100" class="frm">
		<br><br>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<br>
		<FONT SIZE="1">Quilometragem:</FONT>
		<br>
		<INPUT TYPE="text" NAME="qtde_km" value="<? echo $qtde_km ?>" size="5" maxlength="10" class="frm">
		<br><br>
	</td>
</tr>
<? } ?>

<?
$nosso_ip = include ('../nosso_ip.php');
if(($ip==$nosso_ip) or ($ip=="201.42.45.29") OR ($ip=="201.76.86.97") OR ($ip=="201.42.147.251") OR ($ip=='201.43.245.148') OR ($login_admin == 665)){
if($login_fabrica==15){ ?>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#B63434">
			<tr>
			<td valign="middle" align="RIGHT">
			<FONT SIZE="1" color='#FFFFFF'>***Data Fechamento:   </FONT>
			</td>
				<td valign="middle" align="LEFT">
			<INPUT TYPE="text" NAME="data_fechamento" value="<? echo $data_fechamento; ?>" size="12" maxlength="10" class="frm">
			<BR><font size='1' color='#FFFFFF'>dd/mm/aaaa</font>
			</td>
			</tr>
			</table>
	</td>
</tr>
<? } ?>
<? } ?>

<?if($login_fabrica==19){ ?>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#B63434">
			<tr>
			<td valign="middle" align="RIGHT">
			<FONT SIZE="1" color='#FFFFFF'>***Data Fechamento:   </FONT>
			</td>
				<td valign="middle" align="LEFT">
			<INPUT TYPE="text" NAME="data_fechamento" value="<? echo $data_fechamento; ?>" size="12" maxlength="10" class="frm">
			<BR><font size='1' color='#FFFFFF'>dd/mm/aaaa</font>
			</td>
			</tr>
			</table>
			<font size='1'>***Ao inserir a data a OS será fechada</font>
	</td>
</tr>
<? } ?>

<!-- ********************************************************************************************* -->
<? if($login_fabrica==30){//HD 27561 ?>
<table width='650' align='center' border='0' cellspacing='0' cellpadding='5'>
	<TR>
		<TD style='font-size: 12px;'>Fogão</TD>
		<TD style='font-size: 12px;'>Marca</TD>
		<TD style='font-size: 12px;'>Refrigerador</TD>
		<TD style='font-size: 12px;'>Marca</TD>
		<TD style='font-size: 12px;'>Bebedouro</TD>
		<TD style='font-size: 12px;'>Marca</TD>
	</TR>
	<TR>
		<TD>
			<SELECT NAME='fogao'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='2Q' <? if($fogao == '2Q') echo 'SELECTED'; ?>>2Q</OPTION>
				<OPTION VALUE='4Q' <? if($fogao == '4Q') echo 'SELECTED'; ?>>4Q</OPTION>
				<OPTION VALUE='5Q' <? if($fogao == '5Q') echo 'SELECTED'; ?>>5Q</OPTION>
				<OPTION VALUE='6Q' <? if($fogao == '6Q') echo 'SELECTED'; ?>>6Q</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_fogao'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='ATLAS' <? if($marca_fogao == 'ATLAS') echo 'SELECTED'; ?>>ATLAS</OPTION>
				<OPTION VALUE='BOSCH' <? if($marca_fogao == 'BOSCH') echo 'SELECTED'; ?>>BOSCH</OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_fogao == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_fogao == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_fogao == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='DAKO' <? if($marca_fogao == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_fogao == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_fogao == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_fogao == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='refrigerador'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='1 Porta' <? if($refrigerador == '1 PORTA') echo 'SELECTED'; ?>>1 Porta</OPTION>
				<OPTION VALUE='2 Portas' <? if($refrigerador == '2 PORTAS') echo 'SELECTED'; ?>>2 Portas</OPTION>
				<OPTION VALUE='Frost Free' <? if($refrigerador == 'FROST FREE') echo 'SELECTED'; ?>>Frost Free</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_refrigerador'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_refrigerador == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_refrigerador == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_refrigerador == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='DAKO' <? if($marca_refrigerador == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_refrigerador == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_refrigerador == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_refrigerador == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='bebedouro'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='Coluna' <? if($bebedouro == 'COLUNA') echo 'SELECTED'; ?>>Coluna</OPTION>
				<OPTION VALUE='Mesa' <? if($bebedouro == 'MESA') echo 'SELECTED'; ?>>Mesa</OPTION>
				<OPTION VALUE='Suporte' <? if($bebedouro == 'SUPORTE') echo 'SELECTED'; ?>>Suporte</OPTION>
				<OPTION VALUE='Filtro' <? if($bebedouro == 'FILTRO') echo 'SELECTED'; ?>>Filtro</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_bebedouro'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_bebedouro == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_bebedouro == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
	</TR>
	<TR>
		<TD style='font-size: 12px;'>Microondas</TD>
		<TD style='font-size: 12px;'>Marca</TD>
		<TD style='font-size: 12px;'>Lavadoura</TD>
		<TD style='font-size: 12px;'>Marca</TD>
	</TR>
	<TR>
		<TD>
			<SELECT NAME='microondas'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='Pequeno' <? if($microondas == 'PEQUENO') echo 'SELECTED'; ?>>Pequeno</OPTION>
				<OPTION VALUE='Medio'  <? if($microondas == 'MEDIO') echo 'SELECTED'; ?>>Médio</OPTION>
				<OPTION VALUE='Grande'  <? if($microondas == 'GRANDE') echo 'SELECTED'; ?>>Grande</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_microondas'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='BOSCH' <? if($marca_microondas == 'BOSCH') echo 'SELECTED'; ?>>BOSCH</OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_microondas == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CCE' <? if($marca_microondas == 'CCE') echo 'SELECTED'; ?>>CCE</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_microondas == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_microondas == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_microondas == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_microondas == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='PANASONIC' <? if($marca_microondas == 'PANASONIC') echo 'SELECTED'; ?>>PANASONIC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_microondas == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='lavadoura'>
				<OPTION VALUE='' ></OPTION>
				<OPTION VALUE='Sim' <? if($lavadoura == 'SIM') echo 'SELECTED'; ?>>Sim</OPTION>
				<OPTION VALUE='Nao' <? if($lavadoura == 'NAO') echo 'SELECTED'; ?>>Não</OPTION>
			</SELECT>
		</TD>
		<TD>
			<SELECT NAME='marca_lavadoura'>
				<OPTION VALUE=''></OPTION>
				<OPTION VALUE='BRASTEMP' <? if($marca_lavadoura == 'BRASTEMP') echo 'SELECTED'; ?>>BRASTEMP</OPTION>
				<OPTION VALUE='CONSUL' <? if($marca_lavadoura == 'CONSUL') echo 'SELECTED'; ?>>CONSUL</OPTION>
				<OPTION VALUE='CONTINENTAL' <? if($marca_lavadoura == 'CONTINENTAL') echo 'SELECTED'; ?>>CONTINENTAL</OPTION>
				<OPTION VALUE='DAKO' <? if($marca_lavadoura == 'DAKO') echo 'SELECTED'; ?>>DAKO</OPTION>
				<OPTION VALUE='ELETROLUX' <? if($marca_lavadoura == 'ELETROLUX') echo 'SELECTED'; ?>>ELETROLUX</OPTION>
				<OPTION VALUE='ESMALTEC' <? if($marca_lavadoura == 'ESMALTEC') echo 'SELECTED'; ?>>ESMALTEC</OPTION>
				<OPTION VALUE='OUTROS' <? if($marca_lavadoura == 'OUTROS') echo 'SELECTED'; ?>>OUTROS</OPTION>
			</SELECT>
		</TD>
	</TR>
</TABLE>
<?}?>
<!-- ********************************************************************************************* -->

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<br>
		Observações: <INPUT TYPE="text" NAME="obs" value="<? echo $obs; ?>" size="70" maxlength="255" class="frm">
		<br><br>
	</td>
</tr>
</table>

<? }else{ ?>

<input type="hidden" name="troca_faturada" value="<?echo $troca_faturada?>">
<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td align="left" nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Motivo Troca</font>
			<br>
				<select name="motivo_troca" size="1" class="frm">
					<option value=""></option>
					<?
					$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
					if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
					$sql .= " ORDER BY tbl_defeito_constatado.descricao";

					$res = pg_query ($con,$sql) ;
					for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
						echo "<option ";
						if ($motivo_troca == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
						echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
						echo pg_result ($res,$i,descricao) ." - ". pg_result ($res,$i,codigo) ;
						echo "</option>\n";
					}
					?>
			</select>
		</td>
	</tr>
</table>
<? } ?>

<br>

<input type="hidden" name="btn_acao" value="">

<center><img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT="Gravar itens da Ordem de Serviço" border='0' style="cursor:pointer;"></center>

</form>

<br>

<? include "rodape.php";?>
