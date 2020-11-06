<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

// ###############################################################
// Funcao para calcular diferenca entre duas horas
// ###############################################################
function calcula_hora($hora_inicio, $hora_fim){
	// Explode
	$ehora_inicio = explode(":",$hora_inicio);
	$ehora_fim    = explode(":",$hora_fim);

	// Tranforma horas em minutos
	$mhora_inicio = ($ehora_inicio[0] * 60) + $ehora_inicio[1];
	$mhora_fim    = ($ehora_fim[0] * 60) + $ehora_fim[1];

	// Subtrai as horas
	$total_horas = ( $mhora_fim - $mhora_inicio );

	// Tranforma em horas
	$total_horas_div = $total_horas / 60;

	// Valor de horas inteiro
	$total_horas_int = intval($total_horas_div);

	// Resto da subtracao = pega minutos
	$total_horas_sub = $total_horas - ($total_horas_int * 60);

	// Horas trabalhadas
	if ($total_horas_sub < 10) $total_horas_sub = "0".$total_horas_sub;
	$horas_trabalhadas = $total_horas_int.":".$total_horas_sub;

	// Retorna valor
	return $horas_trabalhadas;
}

function calcula_hora_simples($hora){
	// Explode
	$ehora = explode(":",$hora);

	$total_horas   = $ehora[0] * 60; 	// Tranforma em minutos
	$total_minutos = $ehora[1];		 	// atribui minutos

	$total_horas_minutos = $total_horas + $total_minutos; // soma horas tranformadas em minutos e minutos

	$horas_trabalhadas = ( intval($total_horas_minutos) / 60); // transforma em decimais

	// Retorna valor
	return $horas_trabalhadas;
}
// ###############################################################

// ###############################################################

$msg_erro = "";

$qtde_visita = 3;

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($_GET['sua_os']) > 0)  $sua_os = $_GET['sua_os'];
if (strlen($_POST['sua_os']) > 0) $sua_os = $_POST['sua_os'];

if(strlen($_POST['btn_acao']) > 0) $btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == "gravar"){
	// verifica se foram setados os dados de cadastro
	$taxa_visita              = trim($_POST['taxa_visita']);
	$taxa_visita              = str_replace(",",".",$taxa_visita);
	$visita_por_km            = trim($_POST['visita_por_km']);
//	$hora_tecnica             = trim($_POST['hora_tecnica']);
//	$hora_tecnica             = str_replace(",",".",$hora_tecnica);
	$mao_de_obra              = trim($_POST['mao_de_obra']);
	$mao_de_obra              = str_replace(",",".",$mao_de_obra);
	$mao_de_obra_por_hora     = trim($_POST['mao_de_obra_por_hora']);
	$regulagem_peso_padrao    = trim($_POST['regulagem_peso_padrao']);
	$regulagem_peso_padrao    = str_replace(",",".",$regulagem_peso_padrao);
	$certificado_conformidade = trim($_POST['certificado_conformidade']);
	$certificado_conformidade = str_replace(",",".",$certificado_conformidade);
	$valor_diaria             = trim($_POST['valor_diaria']);
	$valor_diaria             = str_replace(",",".",$valor_diaria);
	$natureza_servico         = trim($_POST['natureza_servico']);
	$laudo_tecnico            = trim($_POST['laudo_tecnico']);
	$laudo_tecnico            = str_replace(",",".",$laudo_tecnico);
	$qtde_horas               = trim($_POST['qtde_horas']);
//	$valor_total_hora_tecnica = trim($_POST['valor_total_hora_tecnica']);
	$anormalidades            = trim($_POST['anormalidades']);
	$causas                   = trim($_POST['causas']);
	$medidas_corretivas       = trim($_POST['medidas_corretivas']);
	$recomendacoes            = trim($_POST['recomendacoes']);
	$obs                      = trim($_POST['obs']);
//	$faturamento_cliente_revenda = trim($_POST['faturamento_cliente_revenda']);
	$selo                     = trim($_POST['selo']);
	$lacre_encontrado         = trim($_POST['lacre_encontrado']);
	$lacre                    = trim($_POST['lacre']);
	$tecnico                  = trim($_POST['tecnico']);
	$classificacao_os         = trim($_POST['classificacao_os']);

	if(strlen($taxa_visita) > 0)
		$xtaxa_visita = "'".$taxa_visita."'";
	else
		$xtaxa_visita = 'null';

	if(strlen($visita_por_km) > 0)
		$xvisita_por_km = "'".$visita_por_km."'";
	else
		$xvisita_por_km = 'null';

/*
	if(strlen($hora_tecnica) > 0)
		$xhora_tecnica = "'".$hora_tecnica."'";
	else
		$xhora_tecnica = 'null';
*/
	if(strlen($mao_de_obra) > 0)
		$xmao_de_obra = "'".$mao_de_obra."'";
	else
		$xmao_de_obra = 'null';

	if(strlen($mao_de_obra_por_hora) > 0)
		$xmao_de_obra_por_hora = "'".$mao_de_obra_por_hora."'";
	else
		$xmao_de_obra_por_hora = "'f'";
	
	if(strlen($regulagem_peso_padrao) > 0)
		$xregulagem_peso_padrao = "'".$regulagem_peso_padrao."'";
	else
		$xregulagem_peso_padrao = 'null';
	
	if(strlen($certificado_conformidade) > 0)
		$xcertificado_conformidade = "'".$certificado_conformidade."'";
	else
		$xcertificado_conformidade = 'null';

	if(strlen($valor_diaria) > 0)
		$xvalor_diaria = "'".$valor_diaria."'";
	else
		$xvalor_diaria = 'null';
	
	if(strlen($laudo_tecnico) > 0)
		$xlaudo_tecnico = "'".$laudo_tecnico."'";
	else
		$xlaudo_tecnico = 'null';

	if(strlen($natureza_servico) > 0)
		$xnatureza_servico = "'".$natureza_servico."'";
	else
		$xnatureza_servico = 'null';
	
	if(strlen($qtde_horas) > 0)
		$xqtde_horas = "'".$qtde_horas."'";
	else
		$xqtde_horas = 'null';
/*
	if(strlen($valor_total_hora_tecnica) > 0)
		$xvalor_total_hora_tecnica = "'".$valor_total_hora_tecnica."'";
	else
		$xvalor_total_hora_tecnica = 'null';
*/
/*
	if(strlen($faturamento_cliente_revenda) > 0)
		$xfaturamento_cliente_revenda = "'".$faturamento_cliente_revenda."'";
	else
		$xfaturamento_cliente_revenda = 'null';
*/
	if(strlen($anormalidades) > 0)
		$xanormalidades = "'".$anormalidades."'";
	else
		$xanormalidades = 'null';
	
	if(strlen($causas) > 0)
		$xcausas = "'".$causas."'";
	else
		$xcausas = 'null';
	
	if(strlen($medidas_corretivas) > 0)
		$xmedidas_corretivas = "'".$medidas_corretivas."'";
	else
		$xmedidas_corretivas = 'null';
	
	if(strlen($recomendacoes) > 0)
		$xrecomendacoes = "'".$recomendacoes."'";
	else
		$xrecomendacoes = 'null';
	
	if(strlen($obs) > 0)
		$xobs = "'".$obs."'";
	else
		$xobs = 'null';

	if(strlen($selo) > 0)
		$xselo = "'".$selo."'";
	else
		$xselo = 'null';

	if(strlen($lacre_encontrado) > 0)
		$xlacre_encontrado = "'".$lacre_encontrado."'";
	else
		$xlacre_encontrado = 'null';

	if(strlen($lacre) > 0)
		$xlacre = "'".$lacre."'";
	else
		$xlacre = 'null';

	if(strlen($tecnico) > 0)
		$xtecnico = "'".$tecnico."'";
	else
		$xtecnico = 'null';

	if(strlen($classificacao_os) > 0)
		$xclassificacao_os = "'".$classificacao_os."'";
	else
		$xclassificacao_os = 'null';

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"BEGIN TRANSACTION");

		// insere novo produto na tbl_os
		$produto    = trim($_POST['produto']);
		$referencia = trim($_POST['produto_referencia']);
		$serie      = trim($_POST['serie']);

		if(strlen($serie) > 0)
			$xserie = "'".$serie."'";
		else
			$xserie = 'null';

		$sql = "SELECT produto 
				FROM tbl_produto
				WHERE referencia_pesquisa = '$referencia'";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (pg_numrows($res) > 0) $produto = pg_result($res,0,produto);

		$sql = "UPDATE tbl_os SET 
					produto = $produto,
					serie   = $xserie
				WHERE os = $os";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0){

			// insere em OS_VISITA
			for($i=0; $i < $qtde_visita; $i++){
				$novo                 = trim($_POST['novo_'. $i]);
				$os_visita            = trim($_POST['os_visita_'. $i]);
				$data                 = trim($_POST['data_'. $i]);
				$hora_saida_sede      = trim($_POST['hora_saida_sede_'. $i]);
				$km_saida_sede        = trim($_POST['km_saida_sede_'. $i]);
				$hora_chegada_cliente = trim($_POST['hora_chegada_cliente_'. $i]);
				$km_chegada_cliente   = trim($_POST['km_chegada_cliente_'. $i]);
				$hora_saida_almoco    = trim($_POST['hora_saida_almoco_'. $i]);
				$km_saida_almoco      = trim($_POST['km_saida_almoco_'. $i]);
				$hora_chegada_almoco  = trim($_POST['hora_chegada_almoco_'. $i]);
				$km_chegada_almoco    = trim($_POST['km_chegada_almoco_'. $i]);
				$hora_saida_cliente   = trim($_POST['hora_saida_cliente_'. $i]);
				$km_saida_cliente     = trim($_POST['km_saida_cliente_'. $i]);
				$hora_chegada_sede    = trim($_POST['hora_chegada_sede_'. $i]);
				$km_chegada_sede      = trim($_POST['km_chegada_sede_'. $i]);
				
				if (strlen($msg_erro) == 0){
					if(strlen($data) == 0) {
						if (strlen($os_visita) > 0 AND $novo == 'f') {
							$sql = "DELETE FROM tbl_os_visita
									WHERE  tbl_os_visita.os        = $os
									AND    tbl_os_visita.os_visita = $os_visita;";
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				}

				if (strlen($msg_erro) == 0) {
				
					if(strlen($data) > 0){
						$fnc   = @pg_exec($con,"SELECT fnc_formata_data('$data')");
						$xdata = "'". @pg_result ($fnc,0,0) ."'";
						$xxdata = @pg_result ($fnc,0,0);

						if (strlen($hora_saida_sede) < 4)      $msg_erro = "Digite a hora de saída da sede no formato hh:mm";
						if (strlen($hora_chegada_cliente) < 4) $msg_erro = "Digite a hora de chegada ao cliente no formato hh:mm";
						if (strlen($hora_saida_cliente) < 4)   $msg_erro = "Digite a hora de saída do cliente no formato hh:mm";
						if (strlen($hora_chegada_sede) < 4)    $msg_erro = "Digite a hora de chegada na sede no formato hh:mm";

						if(strlen($hora_saida_sede) > 0)
							$xhora_saida_sede = "'$xxdata ".$hora_saida_sede."'";
						else
							$msg_erro = "Hora Saída Sede deve ser preenchida.";
						
						if(strlen($km_saida_sede) > 0)
							$xkm_saida_sede = "'".$km_saida_sede."'";
						else
							$xkm_saida_sede = 'null';
						
						if(strlen($hora_chegada_cliente) > 0)
							$xhora_chegada_cliente = "'$xxdata ".$hora_chegada_cliente."'";
						else
							$xhora_chegada_cliente = 'null';
						
						if(strlen($km_chegada_cliente) > 0)
							$xkm_chegada_cliente = "'".$km_chegada_cliente."'";
						else
							$xkm_chegada_cliente = 'null';
						
						if(strlen($hora_saida_almoco) > 0)
							if (strlen($hora_saida_almoco) < 5) 
								$msg_erro = "Digite a hora de saída da sede no formato hh:mm";
							else
								$xhora_saida_almoco = "'$xxdata ".$hora_saida_almoco."'";
						else
							$xhora_saida_almoco = 'null';
						
						if(strlen($km_saida_almoco) > 0)
							$xkm_saida_almoco = "'".$km_saida_almoco."'";
						else
							$xkm_saida_almoco = 'null';
						
						if(strlen($hora_chegada_almoco) > 0)
							if (strlen($hora_chegada_almoco) < 5) 
								$msg_erro = "Digite a hora de chegada do almoço no formato hh:mm";
							else
								$xhora_chegada_almoco = "'$xxdata ".$hora_chegada_almoco."'";
						else
							$xhora_chegada_almoco = 'null';
						
						if(strlen($km_chegada_almoco) > 0)
							$xkm_chegada_almoco = "'".$km_chegada_almoco."'";
						else
							$xkm_chegada_almoco = 'null';

						if(strlen($hora_saida_cliente) > 0)
							$xhora_saida_cliente = "'$xxdata ".$hora_saida_cliente."'";
						else
							$msg_erro = "Digite a hora de saída do cliente.";
						
						if(strlen($km_saida_cliente) > 0)
							$xkm_saida_cliente = "'".$km_saida_cliente."'";
						else
							$msg_erro = "Digite o KM de saída do cliente.";
						
						if(strlen($hora_chegada_sede) > 0)
							$xhora_chegada_sede = "'$xxdata ".$hora_chegada_sede."'";
						else
							$msg_erro = "Digite a hora de chegada na sede.";
						
						if(strlen($km_chegada_sede) > 0)
							$xkm_chegada_sede = "'".$km_chegada_sede."'";
						else
							$msg_erro = "Digite o KM de chegada a sede.";
						
						if (strlen($xdata) > 0 AND strlen($msg_erro) ==0){

	################################################################################################
							if (strlen($hora_saida_sede) > 0 AND strlen($hora_chegada_cliente) > 0){
								$horas_1[$i] = calcula_hora($hora_saida_sede, $hora_chegada_cliente);
								
								if (strlen($km_saida_sede) > 0 AND strlen($km_chegada_cliente) > 0)
									$km_1[$i] = $km_chegada_cliente - $km_saida_sede;
								else
									$km_1[$i] = 0;
							}

							if (strlen($hora_chegada_cliente) > 0 AND strlen($hora_saida_almoco) > 0){
								$horas_2[$i] = calcula_hora($hora_chegada_cliente, $hora_saida_almoco);
								
								if (strlen($km_chegada_cliente) > 0 AND strlen($km_saida_almoco) > 0)
									$km_2[$i] = $km_saida_almoco - $km_chegada_cliente;
								else
									$km_2[$i] = 0;
							}else{
								$km_2[$i] = 0;
							}

							if (strlen($hora_saida_almoco) > 0 AND strlen($hora_chegada_almoco) > 0){
								$horas_3[$i] = calcula_hora($hora_saida_almoco, $hora_chegada_almoco);
								
								if (strlen($km_saida_almoco) > 0 AND strlen($km_chegada_almoco) > 0)
									$km_3[$i] = $km_chegada_almoco - $km_saida_almoco;
								else
									$km_3[$i] = 0;
							}else{
								$km_3[$i] = 0;
							}

							if (strlen($hora_chegada_almoco) > 0 AND strlen($hora_saida_cliente) > 0){
								$horas_4[$i] = calcula_hora($hora_chegada_almoco, $hora_saida_cliente);
								
								if (strlen($km_chegada_almoco) > 0 AND strlen($km_saida_cliente) > 0)
									$km_4[$i] = $km_saida_cliente - $km_chegada_almoco;
								else
									$km_4[$i] = 0;
							}else{
								$horas_4[$i] = calcula_hora($hora_chegada_cliente, $hora_saida_cliente);
								$km_4[$i] = 0;
							}

							if (strlen($hora_saida_cliente) > 0 AND strlen($hora_chegada_sede) > 0){
								$horas_5[$i] = calcula_hora($hora_saida_cliente, $hora_chegada_sede);
								
								if (strlen($hora_saida_cliente) > 0 AND strlen($hora_chegada_sede) > 0)
									$km_5[$i] = $km_chegada_sede - $km_saida_cliente;
								else
									$km_5[$i] = 0;
							}

							$km_geral = $km_geral + ($km_1[$i] + $km_2[$i] + $km_3[$i] + $km_4[$i] + $km_5[$i]);
							$hora_geral = (calcula_hora_simples($horas_2[$i]) + calcula_hora_simples($horas_4[$i]));
							$aux_hora_geral = $aux_hora_geral + $hora_geral;

							$valor_total_horas = $valor_total_horas + ($hora_geral * $hora_tecnica);
	################################################################################################

							if(strlen($os_visita) == 0 AND $novo = 't'){
								// insere
								$sql = "INSERT INTO tbl_os_visita (
											os                  ,
											data                ,
											hora_saida_sede     ,
											km_saida_sede       ,
											hora_chegada_cliente,
											km_chegada_cliente  ,
											hora_saida_almoco   ,
											km_saida_almoco     ,
											hora_chegada_almoco ,
											km_chegada_almoco   ,
											hora_saida_cliente  ,
											km_saida_cliente    ,
											hora_chegada_sede   ,
											km_chegada_sede
										) VALUES (
											$os                   ,
											$xdata                ,
											$xhora_saida_sede     ,
											$xkm_saida_sede       ,
											$xhora_chegada_cliente,
											$xkm_chegada_cliente  ,
											$xhora_saida_almoco   ,
											$xkm_saida_almoco     ,
											$xhora_chegada_almoco ,
											$xkm_chegada_almoco   ,
											$xhora_saida_cliente  ,
											$xkm_saida_cliente    ,
											$xhora_chegada_sede   ,
											$xkm_chegada_sede
										)";
							}else{
								// update
								$sql = "UPDATE tbl_os_visita set
											data                 = $xdata                ,
											hora_saida_sede      = $xhora_saida_sede     ,
											km_saida_sede        = $xkm_saida_sede       ,
											hora_chegada_cliente = $xhora_chegada_cliente,
											km_chegada_cliente   = $xkm_chegada_cliente  ,
											hora_saida_almoco    = $xhora_saida_almoco   ,
											km_saida_almoco      = $xkm_saida_almoco     ,
											hora_chegada_almoco  = $xhora_chegada_almoco ,
											km_chegada_almoco    = $xkm_chegada_almoco   ,
											hora_saida_cliente   = $xhora_saida_cliente  ,
											km_saida_cliente     = $xkm_saida_cliente    ,
											hora_chegada_sede    = $xhora_chegada_sede   ,
											km_chegada_sede      = $xkm_chegada_sede     
										WHERE os = $os 
										AND   os_visita = $os_visita";
							}
							$res = @pg_exec($con,$sql);
							$msg_erro = @pg_errormessage($con);
						}
					}
				}
			}
		}

		if (strlen ($km_geral) == 0) $km_geral = '0';
		if (strlen ($aux_hora_geral) == 0) $aux_hora_geral = '0';

		if (strlen($msg_erro) == 0){

			$sql = "SELECT *
					FROM	tbl_os_extra
					WHERE	os = $os";
			$res = @pg_exec($con,$sql);

			if (@pg_numrows($res) > 0){
				// update em OS_EXTRA
//						hora_tecnica                = $xhora_tecnica,
//						valor_total_hora_tecnica    = $xvalor_total_hora_tecnica,
//						faturamento_cliente_revenda = $xfaturamento_cliente_revenda,

				$sql = "UPDATE tbl_os_extra set
							taxa_visita                 = $xtaxa_visita,
							visita_por_km               = $xvisita_por_km,
							mao_de_obra                 = $xmao_de_obra,
							mao_de_obra_por_hora        = $xmao_de_obra_por_hora,
							regulagem_peso_padrao       = $xregulagem_peso_padrao,
							certificado_conformidade    = $xcertificado_conformidade,
							valor_diaria                = $xvalor_diaria,
							laudo_tecnico               = $xlaudo_tecnico,
							deslocamento_km             = $km_geral,
							qtde_horas                  = $aux_hora_geral,
							anormalidades               = $xanormalidades,
							causas                      = $xcausas,
							medidas_corretivas          = $xmedidas_corretivas,
							recomendacoes               = $xrecomendacoes,
							obs                         = $xobs,
							natureza_servico            = $xnatureza_servico,
							selo                        = $xselo,
							lacre_encontrado            = $xlacre_encontrado,
							lacre                       = $xlacre,
							tecnico                     = $xtecnico,
							classificacao_os            = $xclassificacao_os
						WHERE os = $os ";
#echo $sql;
				$res = @pg_exec($con,$sql);
				$msg_erro = @pg_errormessage($con);
			}else{
				$msg_erro = "Não existe registro com o Nº de OS : $os em OS Extra";
			}
		}
	}
	
	if (strlen($msg_erro) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_filizola_valores.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


// #################################################################################//
if (strlen($sua_os) > 0){
//					tbl_os_extra.hora_tecnica                                        ,
//					tbl_os_extra.valor_total_hora_tecnica                            ,
//					tbl_os_extra.faturamento_cliente_revenda                         ,

	$sql = "SELECT	tbl_os.serie                                                     ,
					tbl_os.os                                                        ,
					tbl_os.produto                                                   ,
					tbl_os.sua_os                                                    ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.capacidade                                                ,
					tbl_os_extra.taxa_visita                                         ,
					tbl_os_extra.visita_por_km                                       ,
					tbl_os_extra.mao_de_obra                                         ,
					tbl_os_extra.mao_de_obra_por_hora                                ,
					tbl_os_extra.regulagem_peso_padrao                               ,
					tbl_os_extra.certificado_conformidade                            ,
					tbl_os_extra.valor_diaria                                        ,
					tbl_os_extra.laudo_tecnico                                       ,
					tbl_os_extra.qtde_horas                                          ,
					tbl_os_extra.anormalidades                                       ,
					tbl_os_extra.causas                                              ,
					tbl_os_extra.medidas_corretivas                                  ,
					tbl_os_extra.recomendacoes                                       ,
					tbl_os_extra.obs                                                 ,
					tbl_os_extra.natureza_servico                                    ,
					tbl_os_extra.selo                                                ,
					tbl_os_extra.lacre_encontrado                                    ,
					tbl_os_extra.lacre                                               ,
					tbl_os_extra.tecnico                                             ,
					tbl_os_extra.classificacao_os                                    ,
					tbl_produto.referencia                                           
			FROM	tbl_os
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE	tbl_os.sua_os = '$sua_os'
			AND		tbl_os.posto  = $login_posto ";

	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$os                          = pg_result($res,0,os);
		$sua_os                      = pg_result($res,0,sua_os);
		$produto                     = pg_result($res,0,produto);
		$produto_referencia          = pg_result($res,0,referencia);
		$serie                       = pg_result($res,0,serie);
		$nota_fiscal                 = pg_result($res,0,nota_fiscal);
		$taxa_visita                 = pg_result($res,0,taxa_visita);
		$taxa_visita                 = number_format($taxa_visita, 2, '.', ' ');
		$visita_por_km               = pg_result($res,0,visita_por_km);
//		$hora_tecnica                = pg_result($res,0,hora_tecnica);
//		$hora_tecnica                = number_format($hora_tecnica, 2, '.', ' ');
		$mao_de_obra                 = pg_result($res,0,mao_de_obra);
		$mao_de_obra                 = number_format($mao_de_obra, 2, '.', ' ');
		$mao_de_obra_por_hora        = pg_result($res,0,mao_de_obra_por_hora);
		$regulagem_peso_padrao       = pg_result($res,0,regulagem_peso_padrao);
		$regulagem_peso_padrao       = number_format($regulagem_peso_padrao, 2, '.', ' ');
		$certificado_conformidade    = pg_result($res,0,certificado_conformidade);
		$certificado_conformidade    = number_format($certificado_conformidade, 2, '.', ' ');
		$valor_diaria                = pg_result($res,0,valor_diaria);
		$valor_diaria                = number_format($valor_diaria, 2, '.', ' ');
		$natureza_servico            = pg_result($res,0,natureza_servico);
		$laudo_tecnico               = pg_result($res,0,laudo_tecnico);
		$qtde_horas                  = pg_result($res,0,qtde_horas);
//		$valor_total_hora_tecnica    = pg_result($res,0,valor_total_hora_tecnica);
		$anormalidades               = pg_result($res,0,anormalidades);
		$causas                      = pg_result($res,0,causas);
		$medidas_corretivas          = pg_result($res,0,medidas_corretivas);
		$recomendacoes               = pg_result($res,0,recomendacoes);
		$obs                         = pg_result($res,0,obs);
//		$faturamento_cliente_revenda = pg_result($res,0,faturamento_cliente_revenda);
		$capacidade                  = pg_result($res,0,capacidade);
		$selo                        = pg_result($res,0,selo);
		$lacre_encontrado            = pg_result($res,0,lacre_encontrado);
		$lacre                       = pg_result($res,0,lacre);
		$tecnico                     = pg_result($res,0,tecnico);
		$classificacao_os            = pg_result($res,0,classificacao_os);
	}
}


// #################################################################################//
if (strlen ($sua_os) > 0) {
	$sql = "SELECT * 
			FROM   vw_os_print 
			WHERE  sua_os = '$sua_os' 
			AND    posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$os						= pg_result ($res,0,os);
		$sua_os					= pg_result ($res,0,sua_os);
		$data_abertura			= pg_result ($res,0,data_abertura);
		$quem_abriu_chamado		= pg_result ($res,0,quem_abriu_chamado);
		$obs_os					= pg_result ($res,0,obs);
		$produto_descricao		= pg_result ($res,0,descricao_equipamento);
		$nome_comercial			= pg_result ($res,0,nome_comercial);
		$defeito_reclamado		= pg_result ($res,0,defeito_reclamado);
		$cliente				= pg_result ($res,0,cliente);
		$cliente_nome			= pg_result ($res,0,cliente_nome);
		$cliente_cpf			= pg_result ($res,0,cliente_cpf);
		$cliente_rg 			= pg_result ($res,0,cliente_rg);
		$cliente_endereco		= pg_result ($res,0,cliente_endereco);
		$cliente_numero			= pg_result ($res,0,cliente_numero);
		$cliente_complemento	= pg_result ($res,0,cliente_complemento);
		$cliente_bairro			= pg_result ($res,0,cliente_bairro);
		$cliente_cep			= pg_result ($res,0,cliente_cep);
		$cliente_cidade			= pg_result ($res,0,cliente_cidade);
		$cliente_fone			= pg_result ($res,0,cliente_fone);
		$cliente_nome			= pg_result ($res,0,cliente_nome);
		$cliente_estado			= pg_result ($res,0,cliente_estado);
		$cliente_contrato		= pg_result ($res,0,cliente_contrato);
		$posto_endereco			= pg_result ($res,0,posto_endereco);
		$posto_numero			= pg_result ($res,0,posto_numero);
		$posto_cep				= pg_result ($res,0,posto_cep);
		$posto_cidade			= pg_result ($res,0,posto_cidade);
		$posto_estado			= pg_result ($res,0,posto_estado);
		$posto_fone				= pg_result ($res,0,posto_fone);
		$posto_cnpj				= pg_result ($res,0,posto_cnpj);
		$posto_ie				= pg_result ($res,0,posto_ie);
	}
}

$title = "Ordem de Serviço - Valores";
$layout_menu = "os";
include 'cabecalho.php';

?>

<? include "javascript_pesquisas.php" ?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

input {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE width='100%'>
<TR>
	<TD class='error'><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?
}
//echo $msg_debug;
?>

<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="post">
<input type="hidden" name="os"      value="<? echo $os; ?>">
<input type="hidden" name="sua_os"  value="<? echo $sua_os; ?>">

<?
///////// se nao foi setado valor da OS
if (strlen($os) == 0) {
?>
<table class="border" width='500' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td width="50%" class="menu_top">DIGITE O NÚMERO DA SUA OS:</td>
		<TD class="table_line2"><INPUT TYPE="text" NAME="sua_os"></TD>
	</tr>
</table>
<br>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_continuar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Abre OS" border='0'>

<BR>

<?
}else{
?>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
<?
	if (strlen (trim ($cliente_ie)) == 0) $cliente_ie = "&nbsp";

	switch (strlen (trim ($cliente_cpf))) {
		case 0:
			$cliente_cpf = "&nbsp";
		break;
		case 11:
			$cliente_cpf = substr ($cliente_cpf,0,3) . "." . substr ($cliente_cpf,3,3) . "." . substr ($cliente_cpf,6,3) . "-" . substr ($cliente_cpf,9,2);
		break;
		case 14:
			$cliente_cpf = substr ($cliente_cpf,0,2) . "." . substr ($cliente_cpf,2,3) . "." . substr ($cliente_cpf,5,3) . "/" . substr ($cliente_cpf,8,4) . "-" . substr ($cliente_cpf,12,2);
		break;
	}

?>
	<tr>
		<td class="menu_top">RAZÃO SOCIAL</td>
		<TD class="table_line2" nowrap colspan='2'><? echo $cliente_nome ?>&nbsp</TD>
		<td class="menu_top">CNPJ</td>
		<TD class="table_line2" nowrap><? echo $cliente_cpf ?>&nbsp</TD>
		<td class="menu_top">IE</td>
		<TD class="table_line2"><? echo $cliente_rg ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">ENDEREÇO</td>
		<TD class="table_line2" colspan='2'><? echo $cliente_endereco . ", " . $cliente_numero . " " . $cliente_complenento ?>&nbsp</TD>
		<td class="menu_top">CEP</td>
<?		$cliente_cep = substr ($cliente_cep,0,5) . "-" . substr ($cliente_cep,5,3); ?>
		<TD class="table_line2"><? echo $cliente_cep ?>&nbsp</TD>
		<td class="menu_top">TELEFONE</td>
		<TD class="table_line2"><? echo $cliente_fone ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">BAIRRO</td>
		<TD class="table_line2" colspan='2'><? echo $cliente_bairro ?>&nbsp</TD>
		<td class="menu_top">CIDADE</td>
		<TD class="table_line2"><? echo $cliente_cidade ?>&nbsp</TD>
		<td class="menu_top">ESTADO</td>
		<TD class="table_line2"><? echo $cliente_estado ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">DEFEITO</td>
		<TD class="table_line2" colspan='2'><? echo $defeito_reclamado ?>&nbsp</TD>
		<td class="menu_top">CONTATO</td>
		<TD class="table_line2" colspan="2"><? echo $quem_abriu_chamado ?>&nbsp</TD>
	</tr>
	<tr>
		<td class="menu_top">OBS</td>
		<TD class="table_line2" colspan='5'><? echo $obs_os ?>&nbsp</TD>
	</tr>
</table>

<br>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">SÉRIE</td>
		<TD class="table_line2"><INPUT TYPE="text" NAME="serie" size='6' value='<? echo $serie ?>'>&nbsp</TD>
		<td class="menu_top">CAPACIDADE</td>
		<TD class="table_line2" width='80'><? echo $capacidade ?>&nbsp</TD>
		<td class="menu_top">MODELO</td>
		<TD class="table_line2" colspan="2">
			<INPUT TYPE="hidden" name="produto" value="<? echo $produto; ?>">
			<INPUT TYPE="text" NAME="produto_referencia" size='10' maxlength="20" value='<? echo trim($produto_referencia); ?>'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia') " style='cursor: pointer'>
			<INPUT TYPE="text" NAME="produto_descricao" size='35' value='<? echo trim($produto_descricao); ?>'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao')"  style='cursor: pointer'>
		</TD>
	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan='6' class="menu_top">Valores Combinados na Abertura da OS</td>
	</tr>
	<tr>
		<td class="menu_top">Taxa Visita</td>
		<!-- <td class="menu_top">Hora técnica</td> -->
		<td class="menu_top">Mão-de-obra</td>
		<td class="menu_top">Reg. peso padrão</td>
		<td class="menu_top">Cert. conf.</td>
		<td class="menu_top">Diária</td>
		<!-- <td class="menu_top">Faturar para</td> -->
		<td class="menu_top">Nat. Serviço</td>
	</tr>
	<tr>
		<TD class='table_line'><INPUT TYPE="text" NAME="taxa_visita" VALUE="<? echo str_replace(".",",",$taxa_visita); ?>" SIZE='9' MAXLENGTH=''>&nbsp;&nbsp; <INPUT TYPE="checkbox" NAME="visita_por_km" VALUE='t' <? if($visita_por_km == 't') echo " checked "; ?>> por KM </TD>
		<!-- <TD><INPUT TYPE="text" NAME="hora_tecnica" VALUE="<? echo $hora_tecnica ?>" SIZE='9' MAXLENGTH=''></TD> -->
		<TD class='table_line'><INPUT TYPE="text" NAME="mao_de_obra" VALUE="<? echo str_replace(".",",",$mao_de_obra) ?>" SIZE='9' MAXLENGTH=''>&nbsp;&nbsp; <INPUT TYPE="checkbox" NAME="mao_de_obra_por_hora" VALUE='t' <? if($mao_de_obra_por_hora == 't') echo " checked "; ?>> por Hora </TD>
		<TD><INPUT TYPE="text" NAME="regulagem_peso_padrao" VALUE="<? echo str_replace(".",",",$regulagem_peso_padrao) ?>" SIZE='9' MAXLENGTH=''></TD>
		<TD><INPUT TYPE="text" NAME="certificado_conformidade" VALUE="<? echo str_replace(".",",",$certificado_conformidade) ?>" SIZE='9' MAXLENGTH=''></TD>
		<TD><INPUT TYPE="text" NAME="valor_diaria" VALUE="<? echo str_replace(".",",",$valor_diaria) ?>" SIZE='9' MAXLENGTH=''></TD>
<!-- 
		<TD>
			<?
				if ($faturamento_cliente_revenda == 'r')
					$chk_r = " checked ";
				else
					$chk_c = " checked ";
			?>
			<div class="table_line2">
				<INPUT TYPE="radio" NAME="faturamento_cliente_revenda" VALUE="c" <? echo $chk_c; ?>> Cliente &nbsp;&nbsp;
				<INPUT TYPE="radio" NAME="faturamento_cliente_revenda" VALUE="r" <? echo $chk_r; ?>> Revenda
			</div>
		</TD>
 -->
	<TD>
		<select name="natureza_servico">
			<option value="" selected></option>
			<option value="CONSERTO" <? if ($natureza_servico == "CONSERTO") echo " selected "; ?>>CONSERTO</option>
			<option value="CONTRATO" <? if ($natureza_servico == "CONTRATO") echo " selected "; ?>>CONTRATO</option>
			<option value="MONTAGEM" <? if ($natureza_servico == "MONTAGEM") echo " selected "; ?>>MONTAGEM</option>
			<option value="INSTALAÇÃO" <? if ($natureza_servico == "INSTALAÇÃO") echo " selected "; ?>>INSTALAÇÃO</option>
		</select>
	</TD>

	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">&nbsp;</td>
		<td class="menu_top" colspan="2">Saída</td>
		<td class="menu_top" colspan="2">Chegada</td>
		<td class="menu_top" colspan="2">Saída</td>
		<td class="menu_top" colspan="2">Chegada</td>
		<td class="menu_top" colspan="2">Saída</td>
		<td class="menu_top" colspan="2">Chegada</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="table_line" colspan="2">Sede</td>
		<td class="table_line" colspan="2">Cliente</td>
		<td class="table_line" colspan="2">Cliente</td>
		<td class="table_line" colspan="2">Sede</td>
		<td class="table_line" colspan="2">Almoço</td>
		<td class="table_line" colspan="2">Almoço</td>
	</tr>
	<tr>
		<td class="table_line">DATA</td>
		<td class="table_line">Hora</td>
		<td class="table_line">Km</td>
		<td class="table_line">Hora</td>
		<td class="table_line">Km</td>
		<td class="table_line">Hora</td>
		<td class="table_line">Km</td>
		<td class="table_line">Hora</td>
		<td class="table_line">Km</td>
		<td class="table_line">Hora</td>
		<td class="table_line">Km</td>
		<td class="table_line">Hora</td>
		<td class="table_line">Km</td>
	</tr>

<?
if (strlen($os) > 0) {
	$sql = "SELECT * FROM tbl_os_visita WHERE os = $os ORDER BY os_visita";
	$vis = @pg_exec ($con,$sql);
}

for($x=0; $x < $qtde_visita; $x++) {
	if (strlen($os) > 0 AND strlen($msg_erro) == 0) {
		if (@pg_numrows($vis) > 0) {
			$os_visita = trim(@pg_result($vis,$x,os_visita));
		}
		
		$sql  = "SELECT tbl_os_visita.os_visita                                                       ,
						to_char(tbl_os_visita.data,'DD/MM/YYYY')               AS data                ,
						to_char(tbl_os_visita.hora_saida_sede, 'HH24:MI')      AS hora_saida_sede     ,
						tbl_os_visita.km_saida_sede                                                   ,
						to_char(tbl_os_visita.hora_chegada_cliente, 'HH24:MI') AS hora_chegada_cliente,
						tbl_os_visita.km_chegada_cliente                                              ,
						to_char(tbl_os_visita.hora_saida_almoco, 'HH24:MI')    AS hora_saida_almoco   ,
						tbl_os_visita.km_saida_almoco                                                 ,
						to_char(tbl_os_visita.hora_chegada_almoco, 'HH24:MI')  AS hora_chegada_almoco ,
						tbl_os_visita.km_chegada_almoco                                               ,
						to_char(tbl_os_visita.hora_saida_cliente, 'HH24:MI')   AS hora_saida_cliente  ,
						tbl_os_visita.km_saida_cliente                                                ,
						to_char(tbl_os_visita.hora_chegada_sede, 'HH24:MI')    AS hora_chegada_sede   ,
						tbl_os_visita.km_chegada_sede
				FROM    tbl_os_visita
				WHERE   tbl_os_visita.os        = $os
				AND     tbl_os_visita.os_visita = $os_visita
				AND     tbl_os.posto            = $login_posto
				ORDER BY tbl_os_visita.os_visita;";
		$vis1 = @pg_exec ($con,$sql);
		
		if (@pg_numrows($vis1) == 0) {
			$novo                 = 't';
			$os_visita            = $_POST['os_visita_'.$x];
			$data                 = $_POST['data_'.$x];
			$hora_saida_sede      = $_POST['hora_saida_sede_'.$x];
			$km_saida_sede        = $_POST['km_saida_sede_'.$x];
			$hora_chegada_cliente = $_POST['hora_chegada_cliente_'.$x];
			$km_chegada_cliente   = $_POST['km_chegada_cliente_'.$x];
			$hora_saida_almoco    = $_POST['hora_saida_almoco_'.$x];
			$km_saida_almoco      = $_POST['km_saida_almoco_'.$x];
			$hora_chegada_almoco  = $_POST['hora_chegada_almoco_'.$x];
			$km_chegada_almoco    = $_POST['km_chegada_almoco_'.$x];
			$hora_saida_cliente   = $_POST['hora_saida_cliente_'.$x];
			$km_saida_cliente     = $_POST['km_saida_cliente_'.$x];
			$hora_chegada_sede    = $_POST['hora_chegada_sede_'.$x];
			$km_chegada_sede      = $_POST['km_chegada_sede_'.$x];
		}else{
			$novo                 = 'f';
			$os_visita            = trim(pg_result($vis1,0,os_visita));
			$data                 = trim(pg_result($vis1,0,data));
			$hora_saida_sede      = trim(pg_result($vis1,0,hora_saida_sede));
			$km_saida_sede        = trim(pg_result($vis1,0,km_saida_sede));
			$hora_chegada_cliente = trim(pg_result($vis1,0,hora_chegada_cliente));
			$km_chegada_cliente   = trim(pg_result($vis1,0,km_chegada_cliente));
			$hora_saida_almoco    = trim(pg_result($vis1,0,hora_saida_almoco));
			$km_saida_almoco      = trim(pg_result($vis1,0,km_saida_almoco));
			$hora_chegada_almoco  = trim(pg_result($vis1,0,hora_chegada_almoco));
			$km_chegada_almoco    = trim(pg_result($vis1,0,km_chegada_almoco));
			$hora_saida_cliente   = trim(pg_result($vis1,0,hora_saida_cliente));
			$km_saida_cliente     = trim(pg_result($vis1,0,km_saida_cliente));
			$hora_chegada_sede    = trim(pg_result($vis1,0,hora_chegada_sede));
			$km_chegada_sede      = trim(pg_result($vis1,0,km_chegada_sede));
		}
	}else{
		$novo                 = $_POST['novo_'.$x];
		$os_visita            = $_POST['os_visita_'.$x];
		$data                 = $_POST['data_'.$x];
		$hora_saida_sede      = $_POST['hora_saida_sede_'.$x];
		$km_saida_sede        = $_POST['km_saida_sede_'.$x];
		$hora_chegada_cliente = $_POST['hora_chegada_cliente_'.$x];
		$km_chegada_cliente   = $_POST['km_chegada_cliente_'.$x];
		$hora_saida_almoco    = $_POST['hora_saida_almoco_'.$x];
		$km_saida_almoco      = $_POST['km_saida_almoco_'.$x];
		$hora_chegada_almoco  = $_POST['hora_chegada_almoco_'.$x];
		$km_chegada_almoco    = $_POST['km_chegada_almoco_'.$x];
		$hora_saida_cliente   = $_POST['hora_saida_cliente_'.$x];
		$km_saida_cliente     = $_POST['km_saida_cliente_'.$x];
		$hora_chegada_sede    = $_POST['hora_chegada_sede_'.$x];
		$km_chegada_sede      = $_POST['km_chegada_sede_'.$x];
	}
	$bgcor = "#ffffff";

	echo "<TR>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='data_$x' value='$data' size='12' maxlength='10'></TD>\n";
	echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' NAME='hora_saida_sede_$x' value='$hora_saida_sede' size='06' maxlength='5'></TD>\n";
	echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' NAME='km_saida_sede_$x' value='$km_saida_sede' size='06'></TD>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='hora_chegada_cliente_$x' value='$hora_chegada_cliente' size='06' maxlength='5'></TD>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='km_chegada_cliente_$x' value='$km_chegada_cliente' size='06'></TD>\n";
	echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' NAME='hora_saida_cliente_$x' value='$hora_saida_cliente' size='06' maxlength='5'></TD>\n";
	echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' NAME='km_saida_cliente_$x' value='$km_saida_cliente' size='06'></TD>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='hora_chegada_sede_$x' value='$hora_chegada_sede' size='06' maxlength='5'></TD>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='km_chegada_sede_$x' value='$km_chegada_sede' size='06'></TD>\n";
	echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' NAME='hora_saida_almoco_$x' value='$hora_saida_almoco' size='06' maxlength='5'></TD>\n";
	echo "<TD bgcolor='#ffffff'><INPUT TYPE='text' NAME='km_saida_almoco_$x' value='$km_saida_almoco' size='06'></TD>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='hora_chegada_almoco_$x' value='$hora_chegada_almoco' size='06' maxlength='5'></TD>\n";
	echo "<TD bgcolor='#ced7e7'><INPUT TYPE='text' NAME='km_chegada_almoco_$x' value='$km_chegada_almoco' size='06'></TD>\n";
	echo "</TR>\n";
	
	echo "<input type='hidden' name='novo_$x' value='$novo'>\n";
	echo "<input type='hidden' name='os_visita_$x' value='$os_visita'>\n";
}
?>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Anormalidades encontradas</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="anormalidades" ROWS="2" COLS="122"><? echo $anormalidades; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Causa das anormalidades</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="causas" ROWS="2" COLS="122"><? echo $causas; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Medidas corretivas</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="medidas_corretivas" ROWS="2" COLS="122"><? echo $medidas_corretivas; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Recomendações aos clientes</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="recomendacoes" ROWS="2" COLS="122"><? echo $recomendacoes; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td class="menu_top">Observações</td>
	</tr>
	<tr>
		<TD class="table_line"><TEXTAREA NAME="obs" ROWS="2" COLS="122"><? echo $obs; ?></TEXTAREA></TD>
	</tr>
</table>

<BR>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<TD class='menu_top'>Selo</TD>
		<TD class='menu_top'>Lacre encontrado</TD>
		<TD class='menu_top'>Lacre</TD>
		<TD class='menu_top'>Técnico</TD>
	</tr>
	<tr>
		<TD class='table_line'><INPUT TYPE='text' NAME='selo' value='<? echo $selo; ?>' size='20' maxlength=''></TD>
		<TD class='table_line'><INPUT TYPE='text' NAME='lacre_encontrado' value='<? echo $lacre_encontrado; ?>' size='20' maxlength=''></TD>
		<TD class='table_line'><INPUT TYPE='text' NAME='lacre' value='<? echo $lacre; ?>' size='20' maxlength=''></TD>
		<TD class='table_line'><INPUT TYPE='text' NAME='tecnico' value='<? echo $tecnico; ?>' size='20' maxlength=''></TD>
	</tr>
	<tr>
		<TD class='menu_top' colspan='4'>Classificação da OS</TD>
	</tr>
	<tr>
		<TD class='table_line' colspan='4'>
			<select name='classificacao_os'>
				<option selected></option>
<?
	$sql = "SELECT	* 
			FROM	tbl_classificacao_os
			ORDER BY classificacao_os";
	$res = @pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		for($i=0; $i < pg_numrows($res); $i++){
			echo "				<option value='".pg_result($res,$i,classificacao_os)."'";
			if ($classificacao_os == pg_result($res,$i,classificacao_os)) echo " selected";
			echo ">".pg_result($res,$i,descricao)."</option>\n";
		}
	}
?>
			</select>
		</TD>
	</tr>
</table>
<BR>

<center>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<img src="imagens/btn_voltar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Voltar e digitar outra OS" border='0'>
</center>

<?
} // fim do if q verifica se OS foi setada
?>
<br>

</form>

<? 
include 'rodape.php';
?>