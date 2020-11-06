<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../funcoes.php';

$admin_privilegios="gerencia,auditoria";
include '../autentica_admin.php';
include "../monitora.php";

include '../../fn_traducao.php';
function geraTimestamp($data) {
	$partes = explode('/', $data);
	return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
}

if ($login_fabrica == 117) {
    include_once('../carrega_macro_familia.php');
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$msg_erro = array();
$btn_acao				= $_POST['btn_acao'];
if ($btn_acao == 1) {
	$data_inicial		= $_POST['data_inicial'];
	$data_final			= $_POST['data_final'];
	$familia			= $_POST['familia'];
	$macro_linha		= $_POST['macro_linha'];
	$produto_referencia	= $_POST['produto_referencia'];
	$lupa_config		= $_POST['lupa_config'];
	$produto_descricao	= $_POST['produto_descricao'];
	$pais				= $_POST['pais'];
	$estado				= $_POST['estado'];
	$codigo_posto		= $_POST['codigo_posto'];
	$descricao_posto	= $_POST['descricao_posto'];
	$formato_arquivo	= $_POST['formato_arquivo'];

	if(isset($_POST["linha"])){
		if($login_fabrica == 86){
			if(count($linha)>0){
				$linha = $_POST["linha"];
			}
		}else{
			if (strlen($linha) == 0) {
				$linha = $_POST["linha"];
			}
		}
	}

	if($login_fabrica == 1){
		$tipo_posto = $_POST['tipo_posto'];
		$categoria_posto = $_POST['categoria_posto'];

		if(isset($_POST['tipo_posto'])){
			if(strlen($tipo_posto) == 0){
				$tipo_posto = $_POST['tipo_posto'];
			}
		}

		if(isset($_POST['categoria_posto'])){
			if(strlen($categoria_posto) == 0){
				$categoria_posto = $_POST['categoria_posto'];
			}
		}
	}
	if(strlen($_POST["estado"]) > 0){
		$estado = trim($_POST["estado"]);
		$mostraMsgEstado = "<br>".traduz("no ESTADO").$estado;
	}

	if($login_fabrica == 20 and $pais !='BR'){
		if(strlen($_POST["pais"]) > 0) $pais = trim($_POST["pais"]);
	}
	$tipo_os = trim($_POST['tipo_os']);

	$codigo_posto = "";
	if(strlen($_POST["codigo_posto"]) > 0) $codigo_posto = trim($_POST["codigo_posto"]);

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_descricao']) ;

	if(strlen($produto_referencia)>0 and strlen($produto_descricao)>0){
		$sql = "SELECT produto
				from tbl_produto
				join tbl_familia using(familia)
				where tbl_familia.fabrica = $login_fabrica
				and tbl_produto.referencia = '$produto_referencia'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$produto = pg_fetch_result($res,0,produto);
		}

	}
	if(strlen($data_inicial)==0 || strlen($data_final)==0){
		$msg_erro["msg"][] = traduz("Preencha os campos obrigatórios");
		$msg_erro["campos"][] = "data";
	}
	if (count($msg_erro) == 0) {
		$fnc = @pg_query($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro_db[] = pg_errormessage ($con) ;
			if(count($erro_db)>0){
				$msg_erro["msg"][] = traduz("Erro ao processar o relatório");
			}
		}

		if (count($erro_db) == 0){
			$aux_data_inicial = @pg_fetch_result ($fnc,0,0);
		}else{
			$msg_erro["msg"][] = traduz("Data inválida");
			$msg_erro["campos"][] = "data";
		}
	}

	if (count($msg_erro) == 0) {
		$fnc = @pg_query($con,"SELECT fnc_formata_data('$data_final')");
		if (strlen ( pg_errormessage ($con) ) > 0){
			$erro_db[] = pg_errormessage ($con) ;
			if(count($erro_db)>0){
				$msg_erro["msg"][] = traduz("Erro ao processar o relatório");
			}
		}
		if (count($erro_db) == 0) {
			$aux_data_final = @pg_fetch_result ($fnc,0,0);
		}else {
			$msg_erro["msg"][] = traduz("Erro ao processar o relatório");
		}
	}

	if (count($msg_erro) == 0) {
		if($aux_data_inicial > $aux_data_final)
			$msg_erro["msg"][] = traduz("Data Inválida");
	}
	if (count($msg_erro) == 0) {
		$listar = "ok";
	}

	if (count($msg_erro) > 0) {
		$data_inicial       = trim($_POST['data_inicial_01']);
		$data_final         = trim($_POST['data_final_01']);

		if(isset($_POST['linha'])){
			if($login_fabrica == 86){
				if(count($linha)>0){
					$linha = $_POST['linha'];
				}
			}else{
				if (strlen($linha) == 0) {
					$linha = $_POST['linha'];
				}
			}
		}

		$estado             = trim($_POST['estado']);
		$tipo_pesquisa      = trim($_POST['tipo_pesquisa']);
		$pais               = trim($_POST['pais']);
		$origem             = trim($_POST['origem']);
		$criterio           = trim($_POST['criterio']);
		$produto_referencia = trim($_POST['produto_referencia']); // HD 2003 TAKASHI
		$produto_descricao  = trim($_POST['produto_descricao']) ; // HD 2003 TAKASHI
		$tipo_os            = trim($_POST['tipo_os']);
        $status             = trim($_POST['status']);
        $utilizacao			= trim($_POST['utilizacao']);
		$marca              = $_POST['status'];

		//$msg_erro  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";

	}
	if ($listar == 'ok' && count($msg_erro)==0) {

		if(strlen($codigo_posto)>0){
			$sql = "SELECT  posto
					FROM    tbl_posto_fabrica
					WHERE   fabrica      = $login_fabrica
					AND     codigo_posto = '$codigo_posto';";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0){
				$posto = trim(pg_fetch_result($res,0,posto));
			}
		}


		if (strlen($linha) > 0 || count($linha) > 0 ) {

			if($login_fabrica == 86){
				$condJoinLinha = ' IN (';
				for($i = 0; $i < count($linha); $i++){
					if($i == count($linha)-1 ){
						$condJoinLinha .= $linha[$i].')';
					}else {
						$condJoinLinha .= $linha[$i].', ';
					}
				}
				$cond_1 =	" AND BI.linha {$condJoinLinha} ";

			}else{

				if(strlen($linha) > 0){
					$cond_1 = " AND   BI.linha   = $linha ";
				}

			}
		}

		if($login_fabrica == 1 OR $telecontrol_distrib){
			if(strlen($tipo_posto) > 0){
				$cond_11 = " AND PF.tipo_posto = $tipo_posto";
			}

			if(strlen($categoria_posto) > 0){
				$cond_12 = " AND PF.categoria = '$categoria_posto'";
			}
		}

		if( $telecontrol_distrib ){
			foreach($utilizacao as $k => $v) {
				if( $v == 'os' ){
					$cond .= " AND tbl_posto_fabrica.digita_os IS TRUE";
				}
				if( $v == 'pedidos_faturados' ){
					$cond .= " AND tbl_posto_fabrica.pedido_faturado IS TRUE";
				}
			} 
		}

	//inicio sql
		if (strlen ($estado)   > 0) $cond_2 = " AND   BI.estado  = '$estado' ";
		if (strlen ($posto)    > 0) $cond_3 = " AND   BI.posto   = $posto ";
		if (strlen ($produto)  > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 TAKASHI
		if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
		if (strlen($marca) > 0){
            $cond_7 = ($login_fabrica == 1) ? " AND   PR.marca   = $marca " : " AND   BI.marca   = $marca ";
        }
		if (strlen ($familia)  > 0) $cond_8 = " AND   BI.familia = $familia ";

		if (strlen($tipo_data) == 0 ) $tipo_data = 'data_fechamento';
		if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
			$cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
		}
		if (strlen($status) > 0 AND ($login_fabrica == 50 OR $telecontrol_distrib)) {
			$cond_10 = " AND   PF.credenciamento = '$status' ";
		}

		if(in_array($login_fabrica, array(152,180,181,182))) {
			$selec_dsu = "(SELECT current_date - MAX(tbl_os.data_digitacao)::date FROM tbl_os WHERE tbl_os.fabrica = {$login_fabrica} AND tbl_os.posto = PF.posto) AS dias_sem_uso,";
		}else{
			$selec_dsu = "";
		}

		if(!empty($macro_linha)) {
			$cond_macro_linha  = " AND LI.linha in (select linha from tbl_macro_linha_fabrica where macro_linha = $macro_linha and fabrica = $login_fabrica) "; 
		}

		$sql = "SELECT  PF.posto             ,
						PO.cnpj                              ,
						PF.codigo_posto     AS posto_codigo  ,
						PO.nome             AS posto_nome    ,
						PF.contato_cidade   AS posto_cidade  ,
						PF.contato_estado   AS posto_estado  ,
						PF.credenciamento   AS credenciamento,
						LI.linha                             ,
						LI.nome             AS linha_nome    ,
						COUNT(distinct BI.os)        AS ocorrencia    ,
						SUM(BI.mao_de_obra) AS mao_de_obra   ,
						SUM(BI.qtde_pecas)  AS qtde_pecas    ,
						$selec_dsu
						PF.contato_email,						
						PMA.qtde_finalizadas_30,						
						PMA.qtde_media						
			FROM      bi_os BI
			JOIN      tbl_posto         PO ON PO.posto   = BI.posto
			JOIN      tbl_posto_fabrica PF ON PF.posto   = BI.posto and PF.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto TP ON PF.fabrica = TP.fabrica and PF.tipo_posto = TP.tipo_posto AND TP.fabrica = $login_fabrica
			JOIN      tbl_linha         LI ON LI.linha   = BI.linha AND LI.fabrica = $login_fabrica
			JOIN      tbl_familia       FA ON FA.familia = BI.familia AND FA.fabrica = $login_fabrica
			LEFT JOIN tbl_marca         MA ON MA.marca   = BI.marca AND MA.fabrica = $login_fabrica
			LEFT JOIN tbl_posto_media_atendimento  PMA ON PMA.posto = BI.posto AND PMA.fabrica = $login_fabrica
			WHERE BI.fabrica = $login_fabrica
			AND   PF.fabrica = $login_fabrica
			 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12 $cond_13 $cond_macro_linha
			GROUP BY    posto_codigo     ,
						posto_nome       ,
						posto_cidade     ,
						posto_estado     ,
						linha_nome       ,
						cnpj             ,
						li.linha         ,
						PF.posto         ,
						credenciamento,
						PF.contato_email,
						PMA.qtde_finalizadas_30,
						PMA.qtde_media
			ORDER BY posto_nome,posto_codigo, linha_nome DESC ";

		$res_consulta = pg_query($con,$sql);

		$data = date('Y-m-d').'.'.date('H-i-s');
		$count = pg_num_rows($res_consulta);


		if($_POST['gerar_excel'] == 'true'){

			$formato_arquivo = $_POST['formato_arquivo'];

			if($formato_arquivo == 'CSV'){
				$csv .= traduz('Posto').";";
				$csv .= traduz('Nome do Posto').";";
                if ($login_fabrica == 15) {
                        $csv .= traduz('e-mail').";";
                }
                $csv .= traduz('Cidade').";";
                $csv .= traduz('Estado').";";

                if($login_fabrica == 86 && count($linha) > 0 ){

					$condJoinLinha = ' IN (';
					for($i = 0; $i < count($linha); $i++){
						if($i == count($linha)-1 ){
							$condJoinLinha .= $linha[$i].')';
						}else {
							$condJoinLinha .= $linha[$i].', ';
						}
					}
					$cond_linha =	" AND tbl_linha.linha {$condJoinLinha} ";

					$sql =	"SELECT linha, codigo_linha, nome
							FROM tbl_linha
							WHERE fabrica = $login_fabrica
							{$cond_linha}
							ORDER BY linha";
				}else{
					$sql =	"SELECT linha, codigo_linha, nome
							FROM tbl_linha LI
							WHERE fabrica = $login_fabrica
							$cond_macro_linha
							ORDER BY linha";
				}
                $res2 = pg_query($con,$sql);

                $array_linhas = array();
                for ($i = 0 ; $i < pg_num_rows($res2) ; $i++) {
                        $nome=  pg_fetch_result($res2, $i, 'nome');
                        $csv .= traduz("$nome; ;");
                        $array_linhas [$i][0] = pg_fetch_result($res2, $i, 'nome');
                        $array_linhas [$i][1] = 0;  # Qtde OS
                        $array_linhas [$i][2] = 0;  # Qtde Peças
                        $array_linhas [$i][3] = 0;  # Total OS
                        $array_linhas [$i][4] = 0;  # Total Peças
                }

				if ($login_fabrica == 42) {
					$csv .= traduz("Media OS finalizadas 6 meses anteriores").";";
					$csv .= traduz("Qtde OS finalizada Ultimo Mês").";";
					$csv .= traduz("Qtde OS aberta Mês atual").";";
                	$csv .= traduz("Total O.S 6 meses").";";
				} else {
                	$csv .= traduz("Total OS").";";
				}

                $csv .= traduz("Total Peças");

                if(in_array($login_fabrica, array(152,180,181,182))) {
                	$csv .= traduz("Média Abertura entre OS").";"; 
                	$csv .= ";Dias sem utilizar\n";                 	
            	}else{ 
            		$csv .= "\n";
            	}

                $qtde_linhas = $i ;

                $csv .=" ; ; ; ;";
                for ($i = 0 ; $i < $qtde_linhas ; $i++) {
                        $csv .=  traduz("Qtde OS").";";
                        $csv .=  traduz("Qtde Peças").";";
                }
                $csv .= "\n";
                $cor_linha = 0 ;
                $usaram = 0;
                $nao_usaram = 0;
                $posto_ant = "*";

                for ($i = 0 ; $i < pg_num_rows($res_consulta) + 1 ; $i++) {
					$posto = "#";
					if ($i < pg_num_rows($res_consulta))
						$posto = pg_fetch_result($res_consulta,$i,'posto');

					if ($posto_ant <> $posto) {
						if ($posto_ant <> "*") {
	
							$total = 0 ;

							for ($z = 0 ; $z < $qtde_linhas ; $z++) {
								$qtde = $array_linhas[$z][1];
								$total += $qtde;
							}

							if (($total < 1) AND ($credenciamento == 'CREDENCIADO') AND ($login_fabrica == 19)) {
								$credenciamento = 'DESCREDENCIADO';
							}
							if (($total > 0 )OR $credenciamento == 'CREDENCIADO') {
								$cor_linha++ ;

								if($login_fabrica == 19){
									$csv .=  "$cnpj;";
								}else{
									$csv .=  "$posto_codigo;";
								}

								$csv .=  "$posto_nome;";

								if ($login_fabrica == 15) {
									$csv .=  "$email;";
								}

								$csv .=  "$posto_cidade;";
								$csv .=  "$posto_estado;";
								$total_os = 0;
								$total_pecas = 0;
								for ($z = 0 ; $z < $qtde_linhas ; $z++) {
									$qtde  = $array_linhas [$z][1] ;
									$pecas = $array_linhas [$z][2] ;

									$array_linhas [$z][3] += $qtde  ;
									$array_linhas [$z][4] += $pecas ;

									$csv .=  "$qtde;";
									$csv .=  "$pecas;";

									$total_os    = $total_os + $array_linhas[$z][1];
									$total_pecas = $total_pecas + $array_linhas[$z][2];

									$array_linhas [$z][1] = 0 ;
									$array_linhas [$z][2] = 0 ;

								}

								if ($login_fabrica == 42){
									$qtde_finalizadas_30 = pg_fetch_result($res_consulta, ($i - 1), 'qtde_finalizadas_30');
									$qtde_media          = pg_fetch_result($res_consulta, ($i - 1), 'qtde_media');

									$sqlOsAbertas = "SELECT count(tbl_os.os) total
										               FROM tbl_os
										              WHERE tbl_os.fabrica={$login_fabrica}
										                AND tbl_os.posto={$posto_ant}
										                AND tbl_os.data_abertura BETWEEN '".date('Y-m-01')." 00:00:00' AND '".date('Y-m-t')." 23:59:59'
										                AND excluida IS NOT TRUE";
								    $resOsAbertas = pg_query($con, $sqlOsAbertas);
									$total_os_aberta_mes_atual   = pg_fetch_result($resOsAbertas, 0, 'total');

									$sqlTotalOS = "SELECT count(os) total_os
							                  FROM tbl_os
							                 WHERE fabrica={$login_fabrica}
							                   AND finalizada > current_date - interval '6 months'
							                   AND posto={$posto_ant}
							                   AND excluida IS NOT TRUE";
							        $resTotalOS = pg_query($con, $sqlTotalOS);

							        $totalOsSeisMeses = pg_fetch_result($resTotalOS, 0, total_os);

								}
								if ($login_fabrica == 42) {
									$csv .=  "$qtde_media;";
									$csv .=  "$qtde_finalizadas_30;";
									$csv .=  "$total_os_aberta_mes_atual;";
									$csv .=  "$totalOsSeisMeses;";
								} else {
									$csv .=  "$total_os;";
								}

								$csv .=  "$total_pecas";

								if(in_array($login_fabrica, array(152,180,181,182))) {

									$cond_3 = "AND   BI.posto   = $posto_ant  ";

									$sql_por_os = "SELECT TO_CHAR(BI.data_abertura , 'DD/MM/YYYY') AS data_abertura,
													BI.os
													FROM      bi_os BI
										JOIN      tbl_posto         PO ON PO.posto   = BI.posto
										JOIN      tbl_posto_fabrica PF ON PF.posto   = BI.posto
										JOIN      tbl_produto       PR ON PR.produto = BI.produto
										JOIN 		 tbl_tipo_posto TP ON PF.fabrica = TP.fabrica
										JOIN      tbl_linha         LI ON LI.linha   = BI.linha
										JOIN      tbl_familia       FA ON FA.familia = BI.familia
										LEFT JOIN tbl_marca         MA ON MA.marca   = BI.marca
										WHERE BI.fabrica = $login_fabrica
										AND   PF.fabrica = $login_fabrica
										 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12
										GROUP BY    BI.data_abertura ,
													BI.os
										ORDER BY BI.data_abertura ASC ";
									$res_por_os = pg_query($con,$sql_por_os);

									$result = pg_fetch_all($res_por_os);
									$count = 0;
									unset($dias);
									foreach ($result as $key => $value) {
										$data_inicial =  $value['data_abertura'];

										if(isset($result[$key+1])){
											$data_final =  $result[$key+1]['data_abertura'];

										}else{
											$count++;
											continue;
										}
										if($data_inicial == $data_final){
											$count++;
											continue;
										}
										$time_inicial = geraTimestamp($data_inicial);
										$time_final = geraTimestamp($data_final);
										$diferenca = $time_final - $time_inicial;
										$dias =   $dias + ($diferenca / (60 * 60 * 24));
										$count ++;
									}
									$media = ($dias/$count);

									$csv.= ";".number_format($media,2).";";
									
									$csv.= $dias_sem_uso.";";
									// var_dump(number_format($media,2));
								}
								$usaram++;
								$csv .=  "\n";

							}

							if ($total == 0 AND $credenciamento == "CREDENCIADO") $nao_usaram++ ;
						}
					}

					if ($i == pg_num_rows ($res_consulta) ) break ;

					$posto_codigo   = pg_fetch_result($res_consulta, $i, 'posto_codigo');
					$posto_ant      = $posto;
					$posto          = pg_fetch_result($res_consulta, $i, 'posto');
					$credenciamento = pg_fetch_result($res_consulta, $i, 'credenciamento');
					$posto_nome     = pg_fetch_result($res_consulta, $i, 'posto_nome');
					$posto_cidade   = pg_fetch_result($res_consulta, $i, 'posto_cidade');
					$posto_estado   = pg_fetch_result($res_consulta, $i, 'posto_estado');
					$linha          = pg_fetch_result($res_consulta, $i, 'linha_nome');
					$dias_sem_uso   = pg_fetch_result($res_consulta, $i, 'dias_sem_uso');
					$linha_id       = pg_fetch_result($res_consulta, $i, 'linha');
					$qtde           = pg_fetch_result($res_consulta, $i, 'ocorrencia');
					$pecas          = pg_fetch_result($res_consulta, $i, 'qtde_pecas');
					$cnpj           = pg_fetch_result($res_consulta, $i, 'cnpj');
					$email          = pg_fetch_result($res_consulta, $i, 'contato_email');

					if ($login_fabrica == 42){
						$qtde_finalizadas_30 = pg_fetch_result($res_consulta, $i, 'qtde_finalizadas_30');
						$qtde_media          = pg_fetch_result($res_consulta, $i, 'qtde_media');

						$sqlOsAbertas = "SELECT count(tbl_os.os) AS total
							               FROM tbl_os
							              WHERE tbl_os.fabrica={$login_fabrica}
							                AND tbl_os.posto={$posto}
							                AND tbl_os.data_abertura BETWEEN '".date('Y-m-01')." 00:00:00' AND '".date('Y-m-t')." 23:59:59'
							                AND excluida IS NOT TRUE";
					    $resOsAbertas = pg_query($con, $sqlOsAbertas);
						$total_os_aberta_mes_atual   = pg_fetch_result($resOsAbertas, 0, 'total');


						$sqlTotalOS = "SELECT count(os) AS total_os
				                  FROM tbl_os
				                 WHERE fabrica={$login_fabrica}
				                   AND finalizada > current_date - interval '6 months'
				                   AND posto={$posto}
				                   AND excluida IS NOT TRUE";
				        $resTotalOS = pg_query($con, $sqlTotalOS);

				        $totalOsSeisMeses = pg_fetch_result($resTotalOS, 0, total_os);

					}


					for ($z = 0 ; $z < $qtde_linhas ; $z++) {
						if ($array_linhas[$z][0] == $linha) {
							$array_linhas [$z][1] = $qtde ;
							$array_linhas [$z][2] = $pecas ;
						}
					}

                }
                if ($login_fabrica == 50) {
                        $csv .=  traduz("Total de postos: ");
                        $csv .=  pg_num_rows ($res);
                }
                $arquivo_nome     = "bi-postos-usando-$login_fabrica.$login_admin-".date('YmdHis').".csv";
				//$path             = "/www/assist/www/admin/xls/";
				$path 			  = "../xls/";
				$path_tmp         = "/tmp/";
				$arquivo_completo     = $path.$arquivo_nome;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome;
                $fp = fopen($arquivo_completo_tmp, "w");
                fwrite($fp, utf8_encode($csv));
                fclose($fp);

				if(file_exists($arquivo_completo_tmp)){
					system("mv $arquivo_completo_tmp $arquivo_completo");

					if(file_exists($arquivo_completo)){
						echo $arquivo_completo;
					}
				}
			}else{
				//var_dump($count);
				$arquivo_nome     = "bi-postos-usando-$login_fabrica.$login_admin."."xls";
				//$path             = "/www/assist/www/admin/xls/";
				$path 			  = "../xls/";
				$path_tmp         = "/tmp/";

                $conteudo = '<style>.text{mso-number-format:"\@";}</style>';

				$arquivo_completo     = $path.$arquivo_nome;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

				$fp = fopen($arquivo_completo_tmp, "w");
				$conteudo .= "<table align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>\n";

		        $conteudo .= "<tr class='titulo_coluna'>\n";
		        $conteudo .= "<td  nowrap rowspan='2'>".traduz("Posto")."</td>\n";
		        $conteudo .= "<td  nowrap rowspan='2'>".traduz("Nome do Posto")."</td>\n";
		        if ($login_fabrica == 15) {
		                $conteudo .= "<td  nowrap rowspan='2'>".traduz("e-mail")."</td>\n";
		        }
		        $conteudo .= "<td  nowrap rowspan='2'>".traduz("Cidade")."</td>\n";
		        $conteudo .= "<td  nowrap rowspan='2'>".traduz("Estado")."</td>\n";

		        if($login_fabrica == 86 && count($linha) > 0 ){

					$condJoinLinha = " IN (";
					for($i = 0; $i < count($linha); $i++){
						if($i == count($linha)-1 ){
							$condJoinLinha .= $linha[$i].")";
						}else {
							$condJoinLinha .= $linha[$i].", ";
						}
					}
					$cond_linha =	" AND tbl_linha.linha {$condJoinLinha} ";

					$sql =	"SELECT linha, codigo_linha, nome
							FROM tbl_linha
							WHERE fabrica = $login_fabrica
							{$cond_linha}
							ORDER BY linha";
				}else{

					$sql =	"SELECT linha, codigo_linha, nome
							FROM tbl_linha LI
							WHERE fabrica = $login_fabrica
							$cond_macro_linha
							ORDER BY linha";
				}
		        $res2 = pg_query($con,$sql);

		        $array_linhas = array();
		        for ($i = 0 ; $i < pg_num_rows($res2) ; $i++) {
		                $nome=  pg_fetch_result($res2, $i, nome);
		                $conteudo .= "<td  nowrap colspan='2' width='100' align='center' >$nome</td>\n";
		                $array_linhas [$i][0] = pg_fetch_result($res2, $i, nome) ;
		                $array_linhas [$i][1] = 0;  # Qtde OS
		                $array_linhas [$i][2] = 0;  # Qtde Peças
		                $array_linhas [$i][3] = 0;  # Total OS
		                $array_linhas [$i][4] = 0;  # Total Peças
		        }


				if ($login_fabrica == 42) {
						$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Media OS finalizadas 6 meses anteriores")."</td>\n";
						$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Qtde OS finalizada Ultimo Mês")."</td>\n";
						$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Qtde OS aberta Mês atual")."</td>\n";
		        		$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Total O.S 6 meses")."</td>\n";
				} else {
		        	$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Total OS")."</td>\n";
				}

		        $conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Total Peças")."</td>\n";
	        	if(in_array($login_fabrica, array(152,180,181,182))) {
	        		$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Média Abertura entre OS")."</td>\n"; 
	        		$conteudo .= "<td  nowrap align='center' rowspan='2'>".traduz("Dias sem utilizar")."</td>\n"; 
	        	}
		        $conteudo .= "</tr>\n";
		        $qtde_linhas = $i ;

		        $conteudo .= "<tr class='subtitulo'>\n";
		        for ($i = 0 ; $i < $qtde_linhas ; $i++) {
		                $conteudo .=  "<td  nowrap align='center'>".traduz("Qtde OS")."</td>\n";
		                $conteudo .=  "<td  nowrap align='center'>".traduz("Qtde Peças")."</td>\n";
		        }

		        $conteudo .=  "</tr>\n";

		        $cor_linha = 0 ;
		        $usaram = 0;
		        $nao_usaram = 0;
		        $posto_ant = "*";

		        fwrite($fp, $conteudo);
		        $conteudo = "";

		        for ($i = 0 ; $i < pg_num_rows($res_consulta) + 1 ; $i++) {
					$posto = "#";
					if ($i < pg_num_rows($res_consulta))
						$posto = pg_fetch_result($res_consulta,$i,'posto');
					if ($posto_ant <> $posto) {
						if ($posto_ant <> "*") {
							$total = 0 ;
							for ($z = 0 ; $z < $qtde_linhas ; $z++) {
								$qtde = $array_linhas[$z][1];
								$total += $qtde;
							}
							if (($total < 1) AND ($credenciamento == "CREDENCIADO") AND ($login_fabrica == 19)) {
								$credenciamento = "DESCREDENCIADO";
							}
							if (($total > 0 )OR $credenciamento == "CREDENCIADO") {
								$cor = '#fafafa';
								if (++$cor_linha % 2 == 0) $cor = '#F7F5F0';

								$conteudo .=  "<tr bgcolor='$cor' style='font-size: 10px'>\n";
								if($login_fabrica == 19){
									$conteudo .=  "<td align='right' nowrap class='text'>$cnpj</td>\n";
								}else{
									$conteudo .=  "<td align='right' nowrap class='text'>$posto_codigo</td>\n";
								}


								$conteudo .=  "<td align='left' nowrap>$posto_nome</td>\n";

								if ($login_fabrica == 15) {
									$conteudo .=  "<td align='left' nowrap>$email</td>\n";
								}

								$conteudo .=  "<td align='left' nowrap>$posto_cidade</td>\n";
								$conteudo .=  "<td align='left' nowrap>$posto_estado</td>\n";
								$total_os = 0;
								$total_pecas = 0;
								for ($z = 0 ; $z < $qtde_linhas ; $z++) {
									$qtde  = $array_linhas [$z][1] ;
									$pecas = $array_linhas [$z][2] ;

									$array_linhas [$z][3] += $qtde  ;
									$array_linhas [$z][4] += $pecas ;

									$conteudo .=  "<td align='right' nowrap >\n";
									$conteudo .=  "$qtde\n";
									$conteudo .=  "</td>\n";

									$conteudo .=  "<td align='right' nowrap >\n";
									$conteudo .=  "$pecas\n";
									$conteudo .=  "</td>\n";

									$total_os    = $total_os + $array_linhas[$z][1];
									$total_pecas = $total_pecas + $array_linhas[$z][2];

									$array_linhas [$z][1] = 0 ;
									$array_linhas [$z][2] = 0 ;

								}


								if ($login_fabrica == 42) {
									$conteudo .=  "<td>$qtde_media</td>\n";
									$conteudo .=  "<td>$qtde_finalizadas_30</td>\n";
									$conteudo .=  "<td>$total_os_aberta_mes_atual</td>\n";
									$conteudo .=  "<td>$totalOsSeisMeses</td>\n";
								} else {
									$conteudo .=  "<td>$total_os</td>\n";
								}

								$conteudo .=  "<td>$total_pecas</td>\n";


								if(in_array($login_fabrica, array(152,180,181,182))) {

									$cond_3 = "AND   BI.posto   = $posto_ant  ";

									$sql_por_os = "SELECT TO_CHAR(BI.data_abertura , 'DD/MM/YYYY') AS data_abertura,
													BI.os
													FROM      bi_os BI
										JOIN      tbl_posto         PO ON PO.posto   = BI.posto
										JOIN      tbl_posto_fabrica PF ON PF.posto   = BI.posto
										JOIN      tbl_produto       PR ON PR.produto = BI.produto
										JOIN 		 tbl_tipo_posto TP ON PF.fabrica = TP.fabrica
										JOIN      tbl_linha         LI ON LI.linha   = BI.linha
										JOIN      tbl_familia       FA ON FA.familia = BI.familia
										LEFT JOIN tbl_marca         MA ON MA.marca   = BI.marca
										WHERE BI.fabrica = $login_fabrica
										AND   PF.fabrica = $login_fabrica
										 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12
										GROUP BY    BI.data_abertura ,
													BI.os
										ORDER BY BI.data_abertura ASC ";
									$res_por_os = pg_query($con,$sql_por_os);

									// function geraTimestamp($data) {
									// 	$partes = explode('/', $data);
									// 	return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
									// }

									$result = pg_fetch_all($res_por_os);
									$count = 0;

									foreach ($result as $key => $value) {
										$data_inicial =  $value['data_abertura'];

										if(isset($result[$key+1])){
											$data_final =  $result[$key+1]['data_abertura'];

										}else{
											$count++;
											continue;
										}
										if($data_inicial == $data_final){
											$count++;
											continue;
										}
										$time_inicial = geraTimestamp($data_inicial);
										$time_final = geraTimestamp($data_final);

										$diferenca = $time_final - $time_inicial;
										$dias =   $dias + ($diferenca / (60 * 60 * 24));
										$count ++;
									}
									$media = ($dias/$count);

									$conteudo.= "<td>".number_format($media,2)."</td>\n";
									$conteudo.= "<td>".$dias_sem_uso."</td>\n";

								}

								$usaram++;
								$conteudo .=  "</tr>\n";
								fwrite($fp, $conteudo);
								$conteudo = "";

							}

							if ($total == 0 AND $credenciamento == 'CREDENCIADO') $nao_usaram++ ;
						}
					}

					if ($i == pg_num_rows ($res_consulta) ) break ;

					$posto_codigo   = pg_fetch_result($res_consulta, $i, 'posto_codigo');
					$posto          = pg_fetch_result($res_consulta, $i, 'posto');
					$posto_ant      = pg_fetch_result($res_consulta, $i, 'posto');
					$credenciamento = pg_fetch_result($res_consulta, $i, 'credenciamento');
					$posto_nome     = pg_fetch_result($res_consulta, $i, 'posto_nome');
					$posto_cidade   = pg_fetch_result($res_consulta, $i, 'posto_cidade');
					$posto_estado   = pg_fetch_result($res_consulta, $i, 'posto_estado');
					$linha          = pg_fetch_result($res_consulta, $i, 'linha_nome');
					$linha_id       = pg_fetch_result($res_consulta, $i, 'linha');
					$qtde           = pg_fetch_result($res_consulta, $i, 'ocorrencia');
					$pecas          = pg_fetch_result($res_consulta, $i, 'qtde_pecas');
					$cnpj           = pg_fetch_result($res_consulta, $i, 'cnpj');
					$dias_sem_uso   = pg_fetch_result($res_consulta, $i, 'dias_sem_uso');
					$email          = pg_fetch_result($res_consulta, $i, 'contato_email');

					if ($login_fabrica == 42){
						$qtde_finalizadas_30 = pg_fetch_result($res_consulta, $i, 'qtde_finalizadas_30');
						$qtde_media          = pg_fetch_result($res_consulta, $i, 'qtde_media');

						$sqlOsAbertas = "SELECT count(tbl_os.os) AS total
							               FROM tbl_os
							              WHERE tbl_os.fabrica={$login_fabrica}
							                AND tbl_os.posto={$posto}
							                AND tbl_os.data_abertura BETWEEN '".date('Y-m-01')." 00:00:00' AND '".date('Y-m-t')." 23:59:59'
							                AND excluida IS NOT TRUE";
					    $resOsAbertas = pg_query($con, $sqlOsAbertas);
						$total_os_aberta_mes_atual   = pg_fetch_result($resOsAbertas, 0, 'total');


						$sqlTotalOS = "SELECT count(os) AS total_os
				                  FROM tbl_os
				                 WHERE fabrica={$login_fabrica}
				                   AND finalizada > current_date - interval '6 months'
				                   AND posto={$posto}
				                   AND excluida IS NOT TRUE";
				        $resTotalOS = pg_query($con, $sqlTotalOS);

				        $totalOsSeisMeses = pg_fetch_result($resTotalOS, 0, total_os);

					}

					for ($z = 0 ; $z < $qtde_linhas ; $z++) {
						if ($array_linhas[$z][0] == $linha) {
							$array_linhas [$z][1] = $qtde ;
							$array_linhas [$z][2] = $pecas ;
						}
					}

		        }
		        if ($login_fabrica == 50) {
			        $conteudo .= "<tr>";
			        $conteudo .= "<td colspan='9' bgcolor='#596D9B'>";
			                $conteudo .=  traduz("Total de postos:");
			                $conteudo .=  pg_num_rows ($res_consulta);
			        $conteudo .= "</td>";
			        $conteudo .= "</tr>";
			        $conteudo .=  "</table>\n";
		        }else{
		        	$conteudo .=  "</table>";
		        }
		        fwrite($fp, $conteudo);
		        $conteudo = "";
		        fclose($fp);
					if(file_exists($arquivo_completo_tmp)){
						system("mv $arquivo_completo_tmp $arquivo_completo");

						if(file_exists($arquivo_completo)){
							echo $arquivo_completo;
						}
					}

			}
		 	exit;
		}


	}
}
$layout_menu = 'auditoria';
$title = traduz('RELATÓRIO - POSTOS USANDO');
$bi = 'sim';
require_once('../cabecalho_new.php');


$plugins = array(
	'autocomplete',
	'datepicker',
	'shadowbox',
	'mask',
	'dataTable',
	'select2',
	'multiselect'
);

include '../plugin_loader.php';
?>

<script language='JavaScript'>

	function AbrePeca(produto,data_inicial,data_final,linha,estado,posto,pais,marca,tipo_data,aux_data_inicial,aux_data_final){
		janela = window.open("fcr_os_item.php?produto=" + produto + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado +"&posto=" + posto +"&pais=" + pais +"&marca=" + marca + "&tipo_data=" + tipo_data +"&aux_data_inicial="+aux_data_inicial+"&aux_data_final="+aux_data_final,"produto",'resizable=1,scrollbars=yes,width=750,height=550,top=0,left=0');
		janela.focus();
	}

	var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

	function removerAcentos(string) { 
		return string.replace(/[\W\[\] ]/g,function(a) {
			return map[a]||a}) 
	};

	/** 
	 * select de provincias/estados 
	 */

	$(function() {

		$("#pais").change(function() {
			
		var pais = this.value;
		
		<?php if (in_array($login_fabrica,[180,181,182])) { ?>

			$("#estado optgroup").remove();
			$("#estado option").remove();

			$("#estado").append("<option value=''>PROVINCIAS</option>");

			if (pais == "CO") { 
                
                <?php 

                	$provincias_CO = getProvinciasExterior("CO");

                	foreach ($provincias_CO as $sigla => $provincia) { ?>

                    var provincia = '<?= $provincia ?>';
                
	                var sigla     = '<?= $sigla     ?>';

	                var option    = "<option value='" + sigla + "'>" + provincia + "</option>";

                    $("#estado").append(option);

                <?php } ?>

			} 

			if (pais == "PE") { 
                              
                <?php 

            	$provincias_PE = getProvinciasExterior("PE"); 

                foreach ($provincias_PE as $sigla => $provincia) { ?>

                    var provincia = '<?= $provincia ?>';
                
	                var sigla     = '<?= $sigla     ?>';

	                var option = "<option value='" + sigla + "'>" + provincia + "</option>";

                    $("#estado").append(option);

                <?php } ?>
			}

			if (pais == "AR") { 
                
                <?php 

                	$provincias_AR = getProvinciasExterior("AR"); 

                	foreach ($provincias_AR as $sigla => $provincia) { ?>

                    var provincia = '<?= $provincia ?>';
                
	                var sigla     = '<?= $sigla     ?>';

	                var option = "<option value='" + sigla + "'>" + provincia + "</option>";

                    $("#estado").append(option);

                <?php } ?>
			}

		<?php } ?>

      		if (pais == "BR") { 	

				$("#estado optgroup").remove();
				$("#estado option").remove();

				$("#estado").append("<option value=''>TODOS OS ESTADOS</option>");
                	
            	<?php 

            	$estados_BR = getEstadosNacional();
            	
            	foreach ($estados_BR as $sigla => $estado) { ?>

		            var estado = '<?= $estado ?>';
		            var sigla = '<?= $sigla ?>';

		            var option = "<option value='" + sigla + "'>" + estado + "</option>";

	                $("#estado").append(option);

            	<?php } ?>
			}
		});
	});

</script>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("#linha_famastil").multiselect({
        	selectedText: "selecionados # de #"
		});

		$("#utilizacao").select2();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this),Array('posicao'), "../");
		});
	});
	function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>


<?php

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?> </b>
</div>

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<form name="frm_pesquisa" method="POST" ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
<div class='titulo_tabela '> <?=traduz("Parâmetros de Pesquisa")?> </div>
		<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz("Data Inicial")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?=traduz("Data Final")?></label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span5'>
				<div class='control-group '>
					<label class='control-label' for='data_inicial'><?=traduz("Data de:")?></label>
					<div class='controls controls-row'>
						<input type='radio' name='tipo_data' value='data_digitacao' <?if($tipo_data=="data_digitacao") echo "CHECKED";?>> <?=traduz("Digitação")?>
						<input type='radio' name='tipo_data' value='data_abertura' <?if($tipo_data=="data_abertura") echo "CHECKED";?>> <?=traduz("Abertura")?>
						<input type='radio' name='tipo_data' value='data_fechamento' <?if($tipo_data=="data_fechamento") echo "CHECKED";?>> <?=traduz("Fechamento")?>
						<input type='radio' name='tipo_data' value='data_finalizada'<?if($tipo_data=="data_finalizada") echo "CHECKED";?>> <?=traduz("Finalizada")?>
					</div>
				</div>
			</div>

			<? if(in_array($login_fabrica,array(1,3,15))){ ?>
				<div class='span3'>
					<div class='control-group' >
						<label class='control-label' for='data_inicial'><?=traduz("Marca")?></label>
						<div class='controls controls-row'>
							<?
								$sql = "SELECT  *
										FROM    tbl_marca
										WHERE   tbl_marca.fabrica = $login_fabrica
										ORDER BY tbl_marca.nome;";
								$res = pg_query($con,$sql);

								if (pg_num_rows($res) > 0) {
									echo "<select name='marca' class='span10'>\n";
									echo "<option value=''>".traduz("ESCOLHA")."</option>\n";
									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_marca = trim(pg_fetch_result($res,$x,marca));
										$aux_nome  = trim(pg_fetch_result($res,$x,nome));

										echo "<option value='$aux_marca'";
										if ($marca == $aux_marca){
											echo " SELECTED ";
										}
										echo ">$aux_nome</option>\n";
									}
									echo "</select>\n&nbsp;";
								}
							?>
						</div>
					</div>
				</div>

		<? } ?>
		<div class='span2'></div>
	</div>

	<div class='row-fluid'>
			<div class='span2'></div>
			<?php
				if ($login_fabrica == 117) {
			?>
				<div class='span4'>
					<div class='control-group '>
						<label class='control-label' for='macro_linha'><?=traduz("Linha")?></label>
						<div class='controls controls-row'>
							<?
								$sql = "SELECT 
	                                        DISTINCT tbl_macro_linha.macro_linha, 
	                                        tbl_macro_linha.descricao
	                                    FROM tbl_macro_linha
	                                        JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
	                                    WHERE  tbl_macro_linha_fabrica.fabrica = {$login_fabrica}
	                                        AND     tbl_macro_linha.ativo = TRUE
	                                    ORDER BY tbl_macro_linha.descricao;";
	                            $res = pg_query ($con,$sql);

								if (pg_numrows($res) > 0) {
									echo "<select class='frm' style='width:200px;' name='macro_linha' id='macro_linha'>\n";
									echo "<option value=''>".traduz("ESCOLHA")."</option>\n";

									for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
										$aux_linha = trim(pg_fetch_result($res,$x,macro_linha));
										$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

										echo "<option value='$aux_linha'"; if ($macro_linha == $aux_linha) echo " SELECTED "; echo ">$aux_descricao</option>\n";
									}
									echo "</select>\n";
								}
							?>					
						</div>
					</div>
				</div>
			<?php
				}
            ?>
			<div class='span4'>
				<div class='control-group '>
					<?php
                    if ($login_fabrica == 117) {
                            /*$joinElgin = "JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                      JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha";*/
                            ?>
                            <label class='control-label' for='data_inicial'><?=traduz("Macro - Família<")?>/label>
                    <?php
                    } else { ?>
                            <label class='control-label' for='data_inicial'><?=traduz("Linha")?></label>
                    <?php
                    }
                    ?>
					<div class='controls controls-row'>
						<?
						$sql_linha = "SELECT DISTINCT
											tbl_linha.linha,
											tbl_linha.nome
									  FROM tbl_linha
									  WHERE tbl_linha.fabrica = $login_fabrica
									  ORDER BY tbl_linha.nome ";
						$res_linha = pg_query($con, $sql_linha); ?>
						<? if($login_fabrica != 86){ ?>
							<select name='linha' id='linha'> <?
								if($login_fabrica == 15){
									echo "<option value='LAVADORAS LE'>";
									echo "LAVADORAS LE</option>";
									echo "<option value='LAVADORAS LS'>";
									echo "LAVADORAS LS</option>";
									echo "<option value='LAVADORAS LX'>";
									echo "LAVADORAS LX</option>";
									echo "<option value='IMPORTAÇÃO DIRETA WAL-MART'>";
									echo "IMPORTAÇÃO DIRETA WAL-MART</option>";
									echo "<option value='Purificadores / Bebedouros - Eletrônicos'>";
									echo "Purificadores / Bebedouros - Eletrônicos</option>";
								}
							if (pg_num_rows($res_linha) > 0) { ?>
								<option value=''><?=traduz("ESCOLHA")?></option> <?

								for ($x = 0 ; $x < pg_num_rows($res_linha) ; $x++){
									$aux_linha = trim(pg_fetch_result($res_linha, $x, linha));
									$aux_nome = trim(pg_fetch_result($res_linha, $x, nome));
									if ($linha == $aux_linha) {
										$selected = "SELECTED";
									}
									else {
										$selected = "";
									}?>

									<option value='<?=$aux_linha?>' <?=$selected?>><?=$aux_nome?></option> <?
								}
							}
							else { ?>
								<option value=''><?=traduz("Não existem linhas cadastradas")?></option><?
							} ?>

							</select> <?
						}else { ?>
							<select name="linha[]" id="linha_famastil" multiple="multiple" class='span12'>
								<?php

								$selected_linha = array();
								foreach (pg_fetch_all($res_linha) as $key) {
									if(isset($linha)){
										foreach ($linha as $id) {
											if ( isset($linha) && ($id == $key['linha']) ){
												$selected_linha[] = $id;
											}
										}
									} ?>


									<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

										<?php echo $key['nome']?>

									</option>
						  <?php } ?>
							</select>

						<? } ?>
					</div>
				</div>
			</div>
			<? if ($login_fabrica != 117) {
			?>
			<div class='span4'>
					<div class='control-group '>
						<label class='control-label' for='data_inicial'><?=traduz("Familia")?></label>
						<div class='controls controls-row'>
						<?
							$sql = "SELECT  *
									FROM    tbl_familia
									WHERE   tbl_familia.fabrica = $login_fabrica
									ORDER BY tbl_familia.descricao;";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) > 0) {
								echo "<select name='familia' class='frm'>\n";
								echo "<option value=''>".traduz("ESCOLHA")."</option>\n";
								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_familia   = trim(pg_fetch_result($res,$x,familia));
									$aux_descricao = trim(pg_fetch_result($res,$x,descricao));

									echo "<option value='$aux_familia'";
									if ($familia == $aux_familia){
										echo " SELECTED ";
										$mostraMsgLinha = "<br>".traduz("da FAMÍLIA").$aux_descricao;
									}
									echo ">$aux_descricao</option>\n";
								}
								echo "</select>\n&nbsp;";
							}
						?>
						</div>
					</div>
			</div>
			<? } ?>
			<div class='span2'></div>
	</div>

	<!-- TIPO E CATEGORIA -->
	<?php if($login_fabrica == 1 OR $telecontrol_distrib){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='tipo_posto'><?=traduz("Tipo do Posto")?></label>
					<div class='controls controls-row'>
						<?php
						$sql_tipo = "SELECT descricao,tipo_posto
				            FROM   tbl_tipo_posto
		      		      WHERE  tbl_tipo_posto.fabrica = $login_fabrica
		            		AND tbl_tipo_posto.ativo = 't'
		            		ORDER BY tbl_tipo_posto.descricao";
		            $res_tipo = pg_query($con, $sql_tipo);
						?>
						<select name='tipo_posto' id='tipo_posto'>
							<?php
							if (pg_num_rows($res_tipo) > 0) {
							?>
							<option value=''>ESCOLHA</option> <?
								for ($x = 0 ; $x < pg_num_rows($res_tipo) ; $x++){
									$aux_tipo_posto_nome = trim(pg_fetch_result($res_tipo, $x, descricao));
									$aux_tipo_posto = trim(pg_fetch_result($res_tipo, $x, tipo_posto));
									if ($tipo_posto == $aux_tipo_posto) {
										$selected = "SELECTED";
									}else {
										$selected = "";
									}?>
									<option value='<?=$aux_tipo_posto?>' <?=$selected?>><?=$aux_tipo_posto_nome?></option>
							<?php
								}
							}
							?>
						</select>
					</div>
				</div>
			</div>

			<?php if( $telecontrol_distrib ) { ?>
				<div class='span4'>
					<div class='control-group '>
						<label class='control-label' for='categoria_posto'>Utilização</label>
						<div class='controls controls-row'>
							<select id='utilizacao' name="utilizacao[]" multiple='multiple' class="frm">
			                    <option value="">ESCOLHA</option>
			                    <option value="os" <?= $utilizacao == 'os' ? 'selected' : null ?>> OSs </option>
			                    <option value="pedidos_faturados" <?= $utilizacao == 'pedidos_faturados' ? 'selected' : null ?>> Pedidos Faturados </option>
	                 		</select>
						</div>
					</div>
				</div>
			<?php } ?>
					
			<?php if( !($telecontrol_distrib == "t") ) { ?>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='categoria_posto'><?=traduz("Categoria do Posto")?></label>
					<div class='controls controls-row'>
					<?php
						$checkedA  = (strtolower($categoria_posto) == 'autorizada')          ? "SELECTED" : "";
	               $checkedL  = (strtolower($categoria_posto) == 'locadora')            ? "SELECTED" : "";
	               $checkedAL = (strtolower($categoria_posto) == 'locadora autorizada') ? "SELECTED" : "";
	               $checkedPC = (strtolower($categoria_posto) == "pré cadastro")        ? "SELECTED" : "";
	               $checkedMP = (strtolower($categoria_posto) == "mega projeto")        ? "SELECTED" : "";
					?>
						<select name="categoria_posto" class="frm">
                     <option value="">ESCOLHA</option>
                     <option value="Autorizada" <?=$checkedA?>><?=traduz("Autorizada")?></option>
                     <option value="Locadora" <?=$checkedL?>><?=traduz("Locadora")?></option>
                     <option value="Locadora Autorizada" <?=$checkedAL?>><?=traduz("Locadora Autorizada")?></option>
                     <option value="Pr&eacute; Cadastro" <?=$checkedPC?>><?=traduz("Pré Cadastro")?></option>
                     <option value="mega projeto" <?=$checkedMP?>><?=traduz("Mega Projeto")?></option>
                 	</select>
					</div>
				</div>
			</div>
			<div class='span2'></div>
			<?php } ?>
		</div>
	<?php } ?>
	<!-- //////////////// -->
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='produto_referencia'><?=traduz("Ref. Produto")?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='produto_descricao'><?=traduz("Descrição Produto")?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='data_inicial'><?=traduz("País")?></label>
					<div class='controls controls-row'>
						<?
							$sql = "SELECT  *
									FROM    tbl_pais
									where america_latina is TRUE
									ORDER BY tbl_pais.nome;";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) > 0) {
								echo "<select id='pais' name='pais' class='frm'>\n
								<option value='' selected>TODOS OS PAÍSES</option>";

								for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
									$aux_pais  = trim(pg_fetch_result($res,$x,pais));
									$aux_nome  = trim(pg_fetch_result($res,$x,nome));

									echo "<option value='$aux_pais'";
									if ($pais == $aux_pais){
										echo " SELECTED ";
										$mostraMsgPais = "<br> do PAÍS $aux_nome";
									}
									echo ">$aux_nome</option>\n";
								}
								echo "</select>\n";
							}
						?>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='data_inicial'><?=traduz("Região")?></label>
					<div class='controls controls-row'>
						<select id="estado" name="estado" class='frm'>
						<?php if (isset($_POST['pais'])) { 
						 	
						 	$sigla = $_POST['estado'];
						 	$estado = $_POST['estado'];

						 	if ($_POST['pais'] == "BR") {

								$sigla = $_POST['estado'];
						 		$estado = $_POST['estado'];

						 		if ($_POST['pais'] == "BR") {

									$brasil = $array_estados();
									$estado = $brasil[$sigla];

									if (!isset($estado)) {
										$estado = $sigla;
									}
								} 
							} ?>
							<option value="<?= $sigla ?>"><?= $estado ?></option>
						<?php } ?>
						</select>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='codigo_posto'><?=traduz("Código Posto")?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group '>
					<label class='control-label' for='descricao_posto'><?=traduz("Nome Posto")?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php if ($login_fabrica == 50 OR $telecontrol_distrib) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group '>
						<label class='control-label' for='codigo_posto'>Status</label>
						<div class='controls controls-row'>
							<select name="status" class='frm'>
								<option value="CREDENCIADO" <?PHP if ($status == "CREDENCIADO") echo " selected ";?>>CREDENCIADO</option>
								<option value="DESCREDENCIADO" <?PHP if ($status == "DESCREDENCIADO") echo " selected ";?>>DESCREDENCIADO</option>
								<?php if( $telecontrol_distrib == 't' ) { ?>
									<option value="EM DESCREDENCIAMENTO" <?PHP if ($status == "EM DESCREDENCIAMENTO") echo " selected ";?>>EM DESCREDENCIAMENTO</option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<?php } ?>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group '>
						<label class='control-label' for='data_inicial'><?=traduz("Tipo de Arquivo para Download")?></label>
						<div class='controls controls-row'>
							<input type='radio' name='formato_arquivo' value='XLS' <?if($formato_arquivo=='XLS')echo "checked";?>> XLS
							<input type='radio' name='formato_arquivo' value='CSV' <?if($formato_arquivo!='XLS')echo "checked";?>> CSV
						</div>
					</div>
				</div>
			<div class='span2'></div>
		</div>
		<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
		<div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>

            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),'1');" ><?=traduz("Filtrar")?></button>
                    </div>
                </div>
            </div>

            <!-- margem -->
            <div class="span4"> </div>
        </div>
</form>
</div>

<? if ($listar == "ok" && count($msg_erro)==0) {

	//--== Montar arquivo ========--
	if($count > 0){
		$arquivo_nome     = "bi-postos-usando-$login_fabrica.$login_admin.".$formato_arquivo;
//		$path             = "/www/assist/www/admin/xls/";
		$path 			  = "../xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

?>
		<table id='tabela_listagem' class='table table-striped table-bordered table-hover table-large'>

		<thead>
			<tr class='titulo_coluna'>
				<th  rowspan='2'>#</th>
				<th  rowspan='2'><?=traduz("Posto")?></th>
				<th  rowspan='2'><?=traduz("Nome do Posto")?></th>
		<? if ($login_fabrica == 15) { ?>
				<th  nowrap rowspan='2'><?=traduz("e-mail")?></th>
		<? } ?>
				<th  nowrap rowspan='2'><?=traduz("Cidade")?></th>
				<th  nowrap rowspan='2'><?=traduz("Estado")?></th>
<?
		if($login_fabrica == 86 && count($linha) > 0 ){

			$condJoinLinha = " IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$condJoinLinha .= $linha[$i].")";
				}else {
					$condJoinLinha .= $linha[$i].", ";
				}
			}
			$cond_linha =	" AND tbl_linha.linha {$condJoinLinha} ";

			$sql =	"SELECT linha, codigo_linha, nome
					FROM tbl_linha
					WHERE fabrica = $login_fabrica
					{$cond_linha}
					ORDER BY linha";
		}else{
			$sql =	"SELECT linha, codigo_linha, nome
					FROM tbl_linha LI
					WHERE fabrica = $login_fabrica
					$cond_macro_linha
					ORDER BY linha";
		}

		$res2 = pg_query($con,$sql);

		$array_linhas = array();
		for ($i = 0 ; $i < pg_num_rows($res2) ; $i++) {
			$nome =  pg_fetch_result($res2, $i, nome); ?>
				<th  nowrap colspan='2' width='100' align='center' > <?=$nome?> </th>
			<?
			$array_linhas [$i][0] = pg_fetch_result($res2, $i, nome) ;
			$array_linhas [$i][1] = 0;  # Qtde OS
			$array_linhas [$i][2] = 0;  # Qtde Peças
			$array_linhas [$i][3] = 0;  # Total OS
			$array_linhas [$i][4] = 0;  # Total Peças
		} ?>

				<?php if ($login_fabrica == 42) { ?>
						<th  nowrap rowspan='2'><?=traduz("Media OS finalizadas 6 meses anteriores")?></th>
						<th  nowrap rowspan='2'><?=traduz("Qtde OS finalizada Ultimo Mês")?></th>
						<th  nowrap rowspan='2'><?=traduz("Qtde OS aberta Mês atual")?></th>
						<th  nowrap align='center' rowspan='2'><?=traduz("Total O.S 6 meses")?></th>
				<?php } else { ?>
					<th  nowrap align='center' rowspan='2'><?=traduz("Total OS")?></th>
				<?php } ?>
				<th  nowrap align='center' rowspan='2'><?=traduz("Total Peças")?></th>

				<?php if(in_array($login_fabrica, array(152,180,181,182))) {

					echo "<th  nowrap align='center' rowspan='2'>".traduz("Média Abertura entre OS")."</th>"; 
					echo "<th  nowrap align='center' rowspan='2'>".traduz("Dias sem utilizar")."</th>"; 
				} ?>
			</tr> <?
			$qtde_linhas = $i ;
		?>
		<tr class='subtitulo'> <?
		for ($i = 0 ; $i < $qtde_linhas ; $i++) { ?>
			<th  nowrap align='center'><?=traduz("Qtde OS")?></th>
			<th  nowrap align='center'><?=traduz("Qtde Peças")?></th> <?
		}
?>
			</tr>
		</thead><tbody><?

		$cor_linha = 0 ;
		$usaram = 0;
		$nao_usaram = 0;
		$posto_ant = "*";

		for ($i = 0 ; $i < $count + 1 ; $i++) {
			
			$posto = "#";
			if ($i < pg_num_rows ($res_consulta) ) {
				$posto = pg_fetch_result ($res_consulta,$i,"posto");
			}


			if ($posto_ant <> $posto) {
				if ($posto_ant <> "*") {
					$total = 0 ;
					for ($z = 0 ; $z < $qtde_linhas ; $z++) {
						$qtde = $array_linhas[$z][1];
						$total += $qtde;
					}
					if (($total < 1) AND ($credenciamento == "CREDENCIADO") AND ($login_fabrica == 19)) {
						$credenciamento = "DESCREDENCIADO";
					}
					if (($total > 0 )OR $credenciamento == "CREDENCIADO") {
						$cor_linha++ ;
						$cor = "#fafafa";
						if ($cor_linha % 2 == 0) $cor = "#F7F5F0"; ?>

							<tr bgcolor='$cor' style='font-size: 10px'>
						<? 
						if($login_fabrica == 19){?>
							<td align='left' nowrap><?=$cnpj?></td>
						<? 
						}else{?>
							<td align='left' nowrap><?=$i?></td>
							<td align='left' nowrap><?=$posto_codigo?></td>
						<?	
						}?>
						<td align='left' nowrap><?=$posto_nome?></td>
						<? 
						if ($login_fabrica == 15) {?>
							<td align='left' nowrap><?=$email?></td>
						<? 
						}?>
						<td align='left' nowrap><?=$posto_cidade?></td>
						<td align='left' nowrap><?=$posto_estado?></td>

						<?

						$total_os = 0;
						$total_pecas = 0;
						for ($z = 0 ; $z < $qtde_linhas ; $z++) {
							$qtde  = $array_linhas [$z][1] ;
							$pecas = $array_linhas [$z][2] ;

							$array_linhas [$z][3] += $qtde  ;
							$array_linhas [$z][4] += $pecas ;
							?>
							<td align='right' nowrap ><?=$qtde?></td>

							<td align='right' nowrap ><?=$pecas?></td> <?

							$total_os    = $total_os + $array_linhas[$z][1];
							$total_pecas = $total_pecas + $array_linhas[$z][2];

							$array_linhas [$z][1] = 0 ;
							$array_linhas [$z][2] = 0 ;

						}?>
							<?php if ($login_fabrica == 42) { ?>
								<td><?php echo $qtde_media;?></td>
								<td><?php echo $qtde_finalizadas_30;?></td>
								<td><?php echo $total_os_aberta_mes_atual ;?></td>
								<td><?=$totalOsSeisMeses;?></td>

							<?php } else { ?>
								<td><?=$total_os?></td>
							<?php } ?>

							<td><?=$total_pecas?></td>
							<?

							if(in_array($login_fabrica, array(152,180,181,182))) {

								$cond_3 = "AND   BI.posto   = $posto_ant  ";

								$sql_por_os = "SELECT TO_CHAR(BI.data_abertura , 'DD/MM/YYYY') AS data_abertura,
												BI.os
												FROM      bi_os BI
									JOIN      tbl_posto         PO ON PO.posto   = BI.posto
									JOIN      tbl_posto_fabrica PF ON PF.posto   = BI.posto
									JOIN      tbl_produto       PR ON PR.produto = BI.produto
									JOIN 		 tbl_tipo_posto TP ON PF.fabrica = TP.fabrica
									JOIN      tbl_linha         LI ON LI.linha   = BI.linha
									JOIN      tbl_familia       FA ON FA.familia = BI.familia
									LEFT JOIN tbl_marca         MA ON MA.marca   = BI.marca
									WHERE BI.fabrica = $login_fabrica
									AND   PF.fabrica = $login_fabrica
									 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 $cond_11 $cond_12
									GROUP BY    BI.data_abertura ,
												BI.os
									ORDER BY BI.data_abertura ASC ";
								$res_por_os = pg_query($con,$sql_por_os);

								$result = pg_fetch_all($res_por_os);
								$count_i = 0;

								foreach ($result as $key => $value) {
									$data_inicial =  $value['data_abertura'];

									if(isset($result[$key+1])){
										$data_final =  $result[$key+1]['data_abertura'];

									}else{
										$count_i++;
										continue;
									}
									if($data_inicial == $data_final){
										$count_i++;
										continue;
									}
									$time_inicial = geraTimestamp($data_inicial);
									$time_final = geraTimestamp($data_final);

									$diferenca = $time_final - $time_inicial;
									$dias =   $dias + ($diferenca / (60 * 60 * 24));
									$count_i ++;
								}
								$media = ($dias/$count_i);
								?>
									<td><?=number_format($media,2)?></td>
								<?

								//Dias Pendentes - Dias sem utilizar
								?>
									<td><?=$dias_sem_uso?></td>
								<?
							}

						$usaram++; ?>
						</tr>
					<?
					}

					if ($total == 0 AND $credenciamento == "CREDENCIADO") $nao_usaram++ ;
				}
			}

			if ($i == pg_num_rows ($res_consulta) ) {
				break ;
			}

			$posto_codigo   = pg_fetch_result($res_consulta, $i, 'posto_codigo');
			$posto          = pg_fetch_result($res_consulta, $i, 'posto');
			$posto_ant      = pg_fetch_result($res_consulta, $i, 'posto');
			$credenciamento = pg_fetch_result($res_consulta, $i, 'credenciamento');
			$posto_nome     = pg_fetch_result($res_consulta, $i, 'posto_nome');
			$posto_cidade   = pg_fetch_result($res_consulta, $i, 'posto_cidade');
			$posto_estado   = pg_fetch_result($res_consulta, $i, 'posto_estado');
			$linha          = pg_fetch_result($res_consulta, $i, 'linha_nome');
			$linha_id       = pg_fetch_result($res_consulta, $i, 'linha');
			$qtde           = pg_fetch_result($res_consulta, $i, 'ocorrencia');
			$dias_sem_uso   = pg_fetch_result($res_consulta, $i, 'dias_sem_uso');
			$pecas          = pg_fetch_result($res_consulta, $i, 'qtde_pecas');
			$cnpj           = pg_fetch_result($res_consulta, $i, 'cnpj');
			$email          = pg_fetch_result($res_consulta, $i, 'contato_email');

			if ($login_fabrica == 42){
				$qtde_finalizadas_30 = pg_fetch_result($res_consulta, $i, 'qtde_finalizadas_30');
				$qtde_media          = pg_fetch_result($res_consulta, $i, 'qtde_media');

				$sqlOsAbertas = "SELECT count(tbl_os.os) AS total
					               FROM tbl_os
					              WHERE tbl_os.fabrica={$login_fabrica}
					                AND tbl_os.posto={$posto}
					                AND tbl_os.data_abertura BETWEEN '".date('Y-m-01')." 00:00:00' AND '".date('Y-m-t')." 23:59:59'
					                AND excluida IS NOT TRUE";
			    $resOsAbertas = pg_query($con, $sqlOsAbertas);
				$total_os_aberta_mes_atual   = pg_fetch_result($resOsAbertas, 0, 'total');


				$sqlTotalOS = "SELECT count(os) AS total_os
		                  FROM tbl_os
		                 WHERE fabrica={$login_fabrica}
		                   AND finalizada > current_date - interval '6 months'
		                   AND posto={$posto}
		                   AND excluida IS NOT TRUE";
		        $resTotalOS = pg_query($con, $sqlTotalOS);

		        $totalOsSeisMeses = pg_fetch_result($resTotalOS, 0, total_os);

			}

			for ($z = 0 ; $z < $qtde_linhas ; $z++) {
				if ($array_linhas[$z][0] == $linha) {
					$array_linhas [$z][1] = $qtde ;
					$array_linhas [$z][2] = $pecas ;
				}
			}
		}

		if ($login_fabrica == 50) { ?>
				</tbody>
				<tfoot>
					<tr>
					<td colspan='9' bgcolor='#596D9B'>
					<?=traduz("Total de postos:")?>
					 <?=pg_num_rows($res_consulta)?>
					</td>
				</tr>
				</tfoot>
			</table><?
		}else{ ?>
			</tbody>
			</table> <?
		}

		if ($count > 50) {

			?>
				<script>
					$.dataTableLoad({ table: "#tabela_listagem" });
				</script>
		<? } ?>
			<br />


			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz("Gerar Arquivo Excel")?></span>
			</div>
<? }else{ ?>
		<div class="container">
			<div class="alert">
				    <h4><?=traduz("Nenhum resultado encontrado")?></h4>
			</div>
		</div>
	<? }

// echo "<input type='button' value='Fazer download do arquivo em  ".strtoupper($formato_arquivo)."' onclick=\"window.location='../xls/$arquivo_nome' \">";
} ?>

<? include "../rodape.php" ?>
