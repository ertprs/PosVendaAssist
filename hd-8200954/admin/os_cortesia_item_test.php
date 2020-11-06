<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$qtde_itens = 30;
$qtde_visita=4;

if (strlen($_POST['os'])>0) $os = trim($_POST['os']);
else                        $os = trim($_GET['os']);

//VERIFICA SE É COMPRESSOR - TAKASHI 26/10 HD 257239
if ($login_fabrica == 1 AND strlen($os)>0) {
	$sql =	"SELECT tipo_os_cortesia, tipo_os, tipo_atendimento, os_numero
			FROM  tbl_os
			WHERE fabrica = $login_fabrica
			AND   os = $os;";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) == 1) {
		$tipo_os_cortesia = pg_fetch_result($res,0,tipo_os_cortesia);
		$tipo_os          = pg_fetch_result($res,0,tipo_os);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);
		$os_numero        = pg_result($res,0,os_numero);

		if ($tipo_os_cortesia == "Compressor" OR $tipo_os==10) {
			$compressor='t';
		}
	}
}

//VERIFICA SE É COMPRESSOR - - TAKASHI 26/10
$os_produto = trim($_POST['os_produto']);

$troca_faturada = trim($_POST['troca_faturada']);

$btn_acao = $_POST['btn_acao'];

$msg_erro = "";

if ($btn_acao == "gravar") {

	##### NÃO É TROCA FATURADA #####
	if (strlen($troca_faturada) == 0) {

		for ($i = 0 ; $i < $qtde_itens ; $i++) {
			$qtde = trim($_POST["peca_qtde_".$i]);
			$peca_referencia = trim($_POST["peca_referencia_".$i]);

			if (strlen($peca_referencia) > 0 AND strlen($qtde) > 0) $msg_erro_pq = "QP";
			if (strlen($peca_referencia) > 0 AND strlen($qtde) == 0) $msg_erro_q = "Q";

		}

		//if ($msg_erro_pq <> "QP" AND $tipo_os_cortesia <> 'Promotor') $msg_erro .= " Digite a peça e a quantidade.";
		//if ($msg_erro_q == "Q") $msg_erro .= " Digite a quantidade da peça.";

		if (strlen($msg_erro) == 0) {

			$res = pg_query ($con,"BEGIN TRANSACTION");

			$produto_referencia = trim($_POST['produto_referencia']);
			$produto_referencia = str_replace("-","",$produto_referencia);
			$produto_referencia = str_replace(" ","",$produto_referencia);
			$produto_referencia = str_replace("/","",$produto_referencia);
			$produto_referencia = str_replace(".","",$produto_referencia);

			$produto_voltagem   = trim($_POST['produto_voltagem']);

			if (strlen($produto_referencia) > 0) {
				$sql =	"SELECT tbl_produto.produto
						FROM tbl_produto
						JOIN tbl_linha USING (linha)
						WHERE UPPER(trim(tbl_produto.referencia_pesquisa)) = UPPER(trim('$produto_referencia'))
						AND UPPER(trim(tbl_produto.voltagem)) = UPPER(trim('$produto_voltagem'))
						AND tbl_linha.fabrica = $login_fabrica;";
				$res      = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) $produto = pg_fetch_result($res,0,produto);
			}

			if (strlen($os_produto) > 0) {
				for ($i = 0 ; $i < $qtde_itens ; $i++) {
					$peca_referencia = trim($_POST["peca_referencia_".$i]);
					$peca_referencia = str_replace("." , "" , $peca_referencia);
					$peca_referencia = str_replace("-" , "" , $peca_referencia);
					$peca_referencia = str_replace("/" , "" , $peca_referencia);
					$peca_referencia = str_replace(" " , "" , $peca_referencia);
					$qtde            = trim($_POST["peca_qtde_".$i]);
					$defeito         = trim($_POST["defeito_".$i]);
					$servico         = trim($_POST["servico_".$i]);

					if (strlen($peca_referencia) > 0) {
						$sql =	"SELECT tbl_peca.peca
								FROM  tbl_peca
								JOIN  tbl_lista_basica USING (peca)
								WHERE UPPER(tbl_peca.referencia_pesquisa) = UPPER('$peca_referencia')
								AND   tbl_lista_basica.produto   = $produto
								AND   tbl_lista_basica.fabrica   = $login_fabrica";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) {
							$peca = pg_fetch_result ($res,0,0);
						}else{
							$msg_erro .= " Peça ".trim($_POST["peca_referencia_".$i])." não está na lista básica do produto ".trim($_POST["produto_referencia"]).".";
						}
					}

					if (strlen($msg_erro) > 0) break;
				}
			}

			if (strlen($msg_erro) == 0) {
				for ($i = 0 ; $i < $qtde_itens ; $i++) {
					$os_item         = trim($_POST["os_item_".$i]);
					$peca_referencia = trim($_POST["peca_referencia_".$i]);
					$peca_referencia = str_replace("." , "" , $peca_referencia);
					$peca_referencia = str_replace("-" , "" , $peca_referencia);
					$peca_referencia = str_replace("/" , "" , $peca_referencia);
					$peca_referencia = str_replace(" " , "" , $peca_referencia);
					$qtde            = trim($_POST["peca_qtde_".$i]);
					$defeito         = trim($_POST["defeito_".$i]);
					$servico         = trim($_POST["servico_".$i]);

					if (strlen($defeito) == 0) $defeito = 'null';
					if (strlen($servico) == 0) $servico = 'null';

					if (strlen($peca_referencia) > 0) {
						$sql =	"SELECT tbl_peca.peca
								FROM  tbl_peca
								JOIN  tbl_lista_basica USING (peca)
								WHERE UPPER(trim(tbl_peca.referencia_pesquisa)) = UPPER(trim('$peca_referencia'))
								AND   tbl_lista_basica.produto   = $produto
								AND   tbl_lista_basica.fabrica   = $login_fabrica";
						$res = pg_query ($con,$sql);

						if (pg_num_rows($res) > 0) {
							$peca = pg_fetch_result($res,0,0);
						}else{
							$msg_erro .= " Peça $peca_referencia não cadastrada.";
						}
					}

					if (strlen($msg_erro) > 0) break;

					if (strlen($os_item) > 0 AND strlen($peca_referencia) == 0 AND strlen($msg_erro) == 0) {
						$sql = "DELETE FROM tbl_os_item
								WHERE os_item = $os_item
								AND   os_produto = $os_produto";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
						$msg_erro = substr($msg_erro,6);
					}

					if (strlen($msg_erro) > 0) break;

					if (strlen($peca) > 0 AND strlen($qtde) > 0 AND strlen($msg_erro) == 0) {
						if (strlen($os_item) == 0) {
							//hd 48676 - colocado para gravar admin
							$sql =	"INSERT INTO tbl_os_item (
											os_produto        ,
											peca              ,
											qtde              ,
											defeito           ,
											servico_realizado ,
											admin              
										) VALUES (
											$os_produto ,
											$peca       ,
											$qtde       ,
											$defeito    ,
											$servico    ,
											$login_admin
										);";
						}else{
							$sql =	"UPDATE tbl_os_item SET
											peca              = $peca    ,
											qtde              = $qtde    ,
											defeito           = $defeito ,
											servico_realizado = $servico
									WHERE os_item    = $os_item
									AND   os_produto = $os_produto";
						}

						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);
						$msg_erro = substr($msg_erro,6);
					}

					if (strlen($msg_erro) > 0) break;

					$os_item = '';
					$peca_referencia = '';
					$peca = '';
					$qtde = '';
					$defeito = '';
					$servico = '';
				}
			}
			//HD 212718: Código movido! Estava validando a OS antes de atualizar todos os campos. Absurdo

			$x_solucao_os = $_POST['solucao_os'];
			if (strlen ($msg_erro) == 0 and strlen($x_solucao_os) > 0) {
				$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
						WHERE  tbl_os.os    = $os";
				$res = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			
			$x_defeito_constatado = $_POST['defeito_constatado'];
			if (strlen ($msg_erro) == 0 and strlen($x_defeito_constatado) > 0) {
				$sql = "UPDATE tbl_os SET defeito_constatado = $x_defeito_constatado
						WHERE  tbl_os.os    = $os";
				$res = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if(isset($_POST ['tipo_atendimento'])) $tipo_atendimento = $_POST ['tipo_atendimento'];
			if (strlen ($tipo_atendimento) > 0) {
				$sql = "UPDATE tbl_os SET tipo_atendimento = $tipo_atendimento
						WHERE  tbl_os.os    = $os ";
				$res = pg_query ($con,$sql);
			}

//visita compressores takashi
//HD 212718: Quando der erro, não pode entrar, senão dá mensagem de erro na tela
if($login_fabrica==1 and ($compressor=='t' or $tipo_atendimento == 64 or $tipo_atendimento == 65 or $tipo_atendimento == 69) and strlen($msg_erro) == 0){
	$sqlOS = "SELECT  tbl_os.data_abertura AS data_abertura,
					os_numero,
					tipo_atendimento
			FROM  tbl_os
			WHERE tbl_os.os = $os";
	$resOS = pg_query ($con,$sqlOS) ;

	$data_abertura    = pg_fetch_result($resOS,0,data_abertura);
	$os_numero        = pg_fetch_result($resOS,0,os_numero);
	$tipo_atendimento = pg_fetch_result($resOS,0,tipo_atendimento);

	for ( $i = 0 ; $i < $qtde_visita ; $i++ ) {
		$xos_visita               = trim($_POST['os_visita_'. $i]);
		$xdata                    = fnc_formata_data_pg(trim($_POST['visita_data_'. $i]));
		$xxdata                   = str_replace("'","",$xdata);
		$xhora_chegada_cliente    = trim($_POST['visita_hr_inicio_'. $i]);
		$xhora_saida_cliente      = trim($_POST['visita_hr_fim_'. $i]);
		$xkm_chegada_cliente      = trim($_POST['visita_km_'. $i]);
		$xkm_chegada_cliente      = str_replace (",",".",$xkm_chegada_cliente);
		$valores_adicionais       = trim($_POST['valores_adicionais_'. $i]);
		$justificativa_adicionais = trim($_POST['justificativa_adicionais_'. $i]);

		$xxkm_chegada_cliente = number_format($xkm_chegada_cliente,1,'.','');
		$xkm_chegada_cliente  = number_format($xkm_chegada_cliente,2,'.','');
		$km_conferencia = number_format($_POST['km_conferencia_'.$i],1,'.','') ;
		if($xxkm_chegada_cliente <> $km_conferencia and $xxkm_chegada_cliente > ($km_conferencia* 1.1) and $km_conferencia > 0) {
			$msg_erro = "Fizemos a verificação de deslocamento ida e volta (endereço do posto até o cliente) e encontramos ". str_replace (".",",",$km_conferencia) ."KM de deslocamento. Por isso faremos a correção para prosseguir com a conclusão da OS. Em caso de dúvida gentileza entrar em contato com o seu suporte.";
			$visita_km_erro= $km_conferencia * 1.1;
		}

		if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";
		$valores_adicionais = str_replace (",",".",$valores_adicionais);

		if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
		else                                       $justificativa_adicionais = "null";

		if($tipo_atendimento == 65 or $tipo_atendimento == 59){
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
		}

		if($xxdata < $data_abertura){
			$msg_erro .= "Data de abertura é maior que a data da visita.<BR>";
		}

		if($xxdata <> "null" and $xxdata > date('Y-m-d')) {
			$msg_erro .= "Data de visita futura (maior que a data de hoje).<BR> ";
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

		if(strlen($xhora_chegada_cliente)==0) $xhora_chegada_cliente = "null";
		if(strlen($xhora_saida_cliente)==0)   $xhora_saida_cliente   = "null";

		#echo "$i data:$xxdata,inicio $xhora_chegada_cliente,fim $xhora_saida_cliente, km: $xkm_chegada_cliente os $xos_visita<BR>";

		if(strlen($xqtde_produto_atendido)== 0 ) {
			$xqtde_produto_atendido = " 1 ";
		}

		if($xxdata <>'null' and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)==0) and (strlen($msg_erro)==0)){
			$sql = "INSERT INTO tbl_os_visita (
								os                   ,
								data                 ,
								hora_chegada_cliente ,
								hora_saida_cliente   ,
								km_chegada_cliente   ,
								hora_chegada_sede    ,
								hora_saida_sede      ,
								valor_adicional      ,
								justificativa_valor_adicional
							) VALUES (
								$os                    ,
								$xdata                 ,
								$xhora_chegada_cliente ,
								$xhora_saida_cliente   ,
								$xkm_chegada_cliente   ,
								current_timestamp      ,
								current_timestamp      ,
								$valores_adicionais    ,
								$justificativa_adicionais
							)";
			$res = @pg_query ($con,$sql);
			#echo "inseriu $sql<BR>";
		}

		if((strlen($xxdata)>0) and (strlen($xkm_chegada_cliente)>0) and (strlen($xos_visita)>0) and (strlen($msg_erro)==0)){
			$sql = "UPDATE tbl_os_visita set
							data                 = $xdata                 ,
							hora_chegada_cliente = $xhora_chegada_cliente ,
							hora_saida_cliente   = $xhora_saida_cliente   ,
							km_chegada_cliente   = $xkm_chegada_cliente   ,
							valor_adicional      = $valores_adicionais    ,
							justificativa_valor_adicional = $justificativa_adicionais
						WHERE os = $os
						AND   os_visita = $xos_visita";
			#echo "atualiza $sql";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if((strlen($xos_visita)>0) and ($xxdata=="null")){
			$sql = "DELETE FROM tbl_os_visita
							WHERE  tbl_os_visita.os        = $os
							AND    tbl_os_visita.os_visita = $xos_visita;";
			#echo "apaga: $sql";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		
		}
	}

	//*coloquei 24-01*//
	$tecnico = trim($_POST['tecnico']);
	if (strlen ($tecnico) > 0) $tecnico = "'".$tecnico."'";
		else   $msg_erro .= "Relatório técnico obrigatório";

		if(strlen($msg_erro)==0){
			$sql = "UPDATE tbl_os_extra set 
										valor_por_km=0.65,
										valor_total_hora_tecnica=0.4,
										tecnico    = $tecnico
							WHERE os=$os";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
//*coloquei 24-01*//
//if($ip=="201.42.91.92") echo $sql;
}
//visita compressores takashi


			//HD 212718: Código movido! Estava validando a OS antes de atualizar todos os campos. Absurdo
			if (strlen ($msg_erro) == 0) {
				if (strlen($os) == 0) {
					$res = pg_query ($con,"SELECT CURRVAL ('seq_os')");
					$os  = pg_fetch_result ($res,0,0);
				}
				$res      = pg_query ($con,"SELECT fn_valida_os($os, $login_fabrica)");
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				//hd 48676
				if (strlen ($msg_erro) == 0) {
					$res      = @pg_query ($con,"SELECT fn_valida_os_item($os, $login_fabrica)");
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}
			}



			if (strlen ($msg_erro) == 0) {
				$res = pg_query($con,"COMMIT TRANSACTION");
				header ("Location: os_finalizada.php?os=$os");
				exit;
			}else{
				$context = strpos($msg_erro, "CONTEXT");
				$msg_erro .= substr($msg_erro, 0, $context);
				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}
	}else{
		##### É TROCA FATURADA #####

		$x_motivo_troca = trim ($_POST['motivo_troca']);
		if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

		$resX = pg_query ($con,"BEGIN TRANSACTION");

		$sql =	"UPDATE tbl_os SET
						motivo_troca  = $x_motivo_troca
				WHERE  tbl_os.os      = $os
				and    tbl_os.fabrica = $login_fabrica;";
		$res = @pg_query ($con,$sql);

		if (strlen (pg_errormessage($con)) > 0) {
			$res = pg_query($con,"ROLLBACK TRANSACTION");
			$msg_erro = pg_errormessage ($con);
		}

		if (strlen($msg_erro) == 0) {
				$resX = pg_query ($con,"COMMIT TRANSACTION");
				header ("Location: os_finalizada.php?os=$os");
				exit;
		}
	}
}

//HD 212718: A variável $os já é carregada com o GET anteriormente, precisa puxar os dados
//			 sempre que a variável $os vier preenchida
if (strlen($os) > 0) {
	$sql =	"SELECT tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.posto                                                ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os.fabrica                                              ,
					tbl_os.admin                                                ,
					tbl_os.produto                                              ,
					tbl_os.serie                                                ,
					tbl_os.codigo_fabricacao                                    ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_cpf                                       ,
					tbl_os.nota_fiscal                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_os.tipo_os_cortesia                                     ,
					tbl_os.troca_faturada                                       ,
					tbl_os.motivo_troca                                         ,
					tbl_os.solucao_os                                           ,
					tbl_os.defeito_constatado                                   ,
					tbl_os_produto.os_produto                                   ,
					tbl_os_produto.versao                                       ,
					tbl_produto.referencia                                      ,
					tbl_produto.descricao                                       ,
					tbl_produto.linha                                           ,
					tbl_produto.voltagem                                        ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.reembolso_peca_estoque                    
			FROM	tbl_os
			JOIN	tbl_os_produto USING (os)
			JOIN	tbl_produto ON tbl_os.produto  = tbl_produto.produto
			JOIN	tbl_posto   ON tbl_posto.posto = tbl_os.posto
			JOIN	tbl_posto_fabrica	ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$os                 = pg_fetch_result($res,0,os);
		$sua_os             = pg_fetch_result($res,0,sua_os);
		$sua_os             = substr($sua_os, strlen($sua_os)-5, strlen($sua_os));
		$posto              = pg_fetch_result($res,0,posto);
		$data_abertura      = pg_fetch_result($res,0,data_abertura);
		$fabrica            = pg_fetch_result($res,0,fabrica);
		$admin              = pg_fetch_result($res,0,admin);
		$produto            = pg_fetch_result($res,0,produto);
		$produto_serie      = pg_fetch_result($res,0,serie);
		$codigo_fabricacao  = pg_fetch_result($res,0,codigo_fabricacao);
		$consumidor_nome    = pg_fetch_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_fetch_result($res,0,consumidor_cpf);
		$nota_fiscal        = pg_fetch_result($res,0,nota_fiscal);
		$data_nf            = pg_fetch_result($res,0,data_nf);
		$os_produto         = pg_fetch_result($res,0,os_produto);
		$tipo_os_cortesia   = pg_fetch_result($res,0,tipo_os_cortesia);
		$troca_faturada     = pg_fetch_result($res,0,troca_faturada);
		$motivo_troca       = pg_fetch_result($res,0,motivo_troca);
		$solucao_os         = pg_fetch_result($res,0,solucao_os);
		$defeito_constatado = pg_fetch_result($res,0,defeito_constatado);
		$produto_referencia = pg_fetch_result($res,0,referencia);
		$produto_descricao  = pg_fetch_result($res,0,descricao);
		$produto_voltagem   = pg_fetch_result($res,0,voltagem);
		$posto_codigo       = pg_fetch_result($res,0,codigo_posto);
		$linha_solucao      = pg_fetch_result($res,0,linha);
		$produto_type       = pg_fetch_result($res,0,versao);
		$login_reembolso_peca_estoque = trim (pg_fetch_result ($res,0,reembolso_peca_estoque));
	}
}

if (strlen($msg_erro) > 0) {
	$motivo_troca = trim($_POST["motivo_troca"]);
	$solucao_os   = trim($_POST["solucao_os"]);
	$defeito_constatado = trim($_POST["defeito_constatado"]);
}

$title = "Cadastro de Ordem de Serviço do Tipo Cortesia - ADMIN";
$layout_menu = 'callcenter';
include "cabecalho.php";
?>
<script language='javascript' src='js/jquery.js'></script>

<script>
function mostra_horas(){
$('td.mostra').css('display','table-cell');
}
function esconde_horas(){
$('td.mostra').css('display','none');
}

function mostrar_qtde(){
$('td.mostra_qtde').css('display','table-cell');
}
function esconde_qtde(){
$('td.mostra_qtde').css('display','none');
}

function MudaCampo(campo){
	if (campo.value=='65' || campo.value=='69') {
		document.getElementById('mostra_visita').style.display='inline';
		mostrar_qtde();
	}else{
		document.getElementById('mostra_visita').style.display='none';
		esconde_qtde();
	}

	if (campo.value!='65' && campo.value!='69') {
		mostra_horas();
	}else{
		esconde_horas();
	}
}

function fnc_pesquisa_peca_lista (produto_referencia, peca_referencia, peca_descricao, peca_preco, tipo) {
	var url = "";

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&voltagem=" + document.frm_os.produto_voltagem.value + "&tipo=" + tipo ;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&voltagem=" + document.frm_os.produto_voltagem.value + "&tipo=" + tipo ;
	}

	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=502, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.preco		= peca_preco;
		janela.focus();
	}else{
		alert("Digite pelo menos 4 caracteres!");
	}
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

function formata_cnpj(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_hr_inicio_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + ':';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	
}
function formata_cnpj2(cnpj, form, posicao){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "visita_hr_fim_" + posicao;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + ':';
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

<? if (strlen ($msg_erro) > 0) { ?>
<br>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	// Retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$msg_erro = substr($msg_erro, 6);
	}

	echo "Foi detectado o seguinte erro:<br>".$msg_erro;
?>
	</td>
</tr>
</table>
<? } ?>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="os" value="<? echo $os; ?>">
<input type="hidden" name="os_produto" value="<? echo $os_produto; ?>">
<input type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
<input type="hidden" name="produto_voltagem" value="<? echo $produto_voltagem; ?>">
<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="top" align="left">
<? if (strlen($os) > 0) { ?>
		<td>
			<input type="hidden" name="sua_os" value="<? echo $sua_os; ?>">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $posto_codigo.$sua_os; ?></B></font>
		</td>
<? } ?>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $posto_codigo ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data de Abertura</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo da OS cortesia</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $tipo_os_cortesia; ?></B></font>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="top" align="left">
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $produto_referencia." - ".$produto_descricao; ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem do Produto</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $produto_voltagem ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $produto_type; ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nº de Série</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $produto_serie ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código fabricação</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $codigo_fabricacao ?></B></font>
		</td>
	</tr>
</table>

<br>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="top" align="left">
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $consumidor_nome ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ Consumidor</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $consumidor_cpf ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $nota_fiscal ?></B></font>
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
			<br>
			<font size="2" face="Geneva, Arial, Helvetica, san-serif"><B><? echo $data_nf ?></B></font>
		</td>
	</tr>
</table>


<table align="center" width="750" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td align="left" nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Solução</font>
		<br>
		<select name="solucao_os" size="1" class="frm">
			<option value=""></option>
		<?
		if($login_fabrica == 1){

			$sql = "SELECT 	solucao,
					descricao
				FROM tbl_solucao
				JOIN tbl_linha_solucao USING(solucao)
				WHERE fabrica = $login_fabrica 
				AND   ativo IS TRUE
				AND   tbl_linha_solucao.linha= $linha_solucao
				ORDER BY descricao";
			$res = pg_query($con, $sql);
			
			for ($x = 0 ; $x < pg_num_rows($res) ; $x++ ) {
				$aux_solucao_os    = pg_fetch_result ($res,$x,solucao);
				$solucao_descricao = pg_fetch_result ($res,$x,descricao);
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
				if ($login_reembolso_peca_estoque == 't') {
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
					if ($login_reembolso_peca_estoque == 't') {
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
				if ($solucao_os == pg_fetch_result ($res,$x,servico_realizado)) echo " selected ";
				echo " value='" . pg_fetch_result ($res,$x,servico_realizado) . "'>" ;
				echo pg_fetch_result ($res,$x,descricao) ;
				if (pg_fetch_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
				echo "</option>";
			}
		}
		?>

		</select>
	</td>
</tr>
</table>
<table align="center" width="750" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td align="left" nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Constatado</font>
		<br>
		<select name="defeito_constatado" size="1" class="frm">
			<option value=""></option>
			<?
			$sql = "SELECT defeito_constatado_por_familia, defeito_constatado_por_linha FROM tbl_fabrica WHERE fabrica = $login_fabrica";
# if ($ip == '201.0.9.216') echo "<br>".nl2br($sql)."<br>";
				$res = pg_query ($con,$sql);
				$defeito_constatado_por_familia = pg_fetch_result ($res,0,0) ;
				$defeito_constatado_por_linha   = pg_fetch_result ($res,0,1) ;

				if ($defeito_constatado_por_familia == 't') {
					$sql = "SELECT familia FROM tbl_produto WHERE produto = $produto";
# if ($ip == '201.0.9.216') echo "<br>".nl2br($sql)."<br>";
					$res = pg_query ($con,$sql);
					$familia = pg_fetch_result ($res,0,0) ;

					if ($login_fabrica == 1){

						$sql = "SELECT tbl_defeito_constatado.* FROM tbl_familia  JOIN   tbl_familia_defeito_constatado USING(familia) JOIN   tbl_defeito_constatado USING(defeito_constatado) ";
						if ($linha == 198) $sql .= " JOIN tbl_produto_defeito_constatado USING(defeito_constatado) ";
						$sql .= " WHERE  tbl_defeito_constatado.fabrica = $login_fabrica AND tbl_familia_defeito_constatado.familia = $familia";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						if ($linha == 198) $sql .= " AND tbl_produto_defeito_constatado.produto = $produto ";
						$sql .= " AND tbl_defeito_constatado.ativo IS TRUE ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}else{
						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_familia
								JOIN   tbl_familia_defeito_constatado USING(familia)
								JOIN   tbl_defeito_constatado         USING(defeito_constatado)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
								AND    tbl_familia_defeito_constatado.familia = $familia";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}
				}else{

					if ($defeito_constatado_por_linha == 't') {
						$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto";
						$res   = pg_query ($con,$sql);
						$linha = pg_fetch_result ($res,0,0) ;

						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_defeito_constatado
								JOIN   tbl_linha USING(linha)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
								AND    tbl_linha.linha = $linha";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " AND tbl_defeito_constatado.ativo IS TRUE ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}else{
						$sql = "SELECT tbl_defeito_constatado.*
							FROM   tbl_defeito_constatado
							WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						$sql .= " AND tbl_defeito_constatado.ativo IS TRUE ";
						$sql .= " ORDER BY tbl_defeito_constatado.descricao";
					}
				}

				$res = pg_query ($con,$sql) ;
				for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
					echo "<option ";
					if ($defeito_constatado == pg_fetch_result ($res,$i,defeito_constatado) ) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$i,defeito_constatado) . "'>" ;
					echo pg_fetch_result ($res,$i,codigo) ." - ". pg_fetch_result ($res,$i,descricao) ;
					echo "</option>";
				}
				?>
		</select>
	</td>
	<?
	if($login_fabrica == 1 AND ($tipo_atendimento == 64 or $tipo_atendimento == 65 or $tipo_atendimento == 69) AND strlen($os)>0) {
		$sql = " SELECT tipo_atendimento FROM tbl_os where os = $os";
		$res = pg_query($con,$sql);
		$tipo_atendimento = pg_fetch_result($res,0,0);
		echo "<td align='left'>";
		echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Tipo Atendimento</font><br>";
		echo "<select name='tipo_atendimento' id='tipo_atendimento' style='width:230px;' onChange='MudaCampo(this)' >";

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

<br>

<? if (strlen($troca_faturada) == 0) { ?>

<table border="0" cellpadding="2" cellspacing="2" align="center">
	<tr bgcolor="#CCCCCC">
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Código</b></font> <a href="http://www.telecontrol.com.br/assist/admin/peca_consulta_por_produto.php?produto=<?echo $produto?>" target="_black"><font size="1" face="Geneva, Arial, Helvetica, san-serif"><b>Lista Básica</b></font></a></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Descrição</b></font></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Qtde</b></font></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Defeito</b></font></td>
		<td align="center"><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Serviço</b></font></td>
	</tr>
<?
if (strlen($_GET['os']) > 0) {
	$sql =	"SELECT tbl_os_item.os_item                              ,
					tbl_os_item.peca                                 ,
					tbl_os_item.qtde                                 ,
					tbl_os_item.defeito                              ,
					tbl_os_item.servico_realizado                    ,
					tbl_peca.referencia           AS peca_referencia ,
					tbl_peca.descricao            AS peca_descricao
			FROM  tbl_os_item
			JOIN  tbl_peca       ON tbl_os_item.peca       = tbl_peca.peca
			JOIN  tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN  tbl_os         ON tbl_os_produto.os      = tbl_os.os
			WHERE tbl_os_produto.produto    = $produto
			AND   tbl_os_produto.os_produto = $os_produto
			AND   tbl_os.fabrica            = $login_fabrica
			ORDER BY tbl_peca.referencia";
# echo nl2br($sql);
	$res = @pg_query($con,$sql);
	$num_linhas = @pg_num_rows($res);
}

for ($i = 0 ; $i < $qtde_itens ; $i++) {

	$os_item         = '';
	$peca            = '';
	$peca_referencia = '';
	$peca_descricao  = '';
	$peca_qtde       = '';
	$defeito         = '';
	$servico         = '';

	if ($i < $num_linhas) {
		$os_item         = @pg_fetch_result($res,$i,os_item);
		$peca            = @pg_fetch_result($res,$i,peca);
		$peca_referencia = @pg_fetch_result($res,$i,peca_referencia);
		$peca_descricao  = @pg_fetch_result($res,$i,peca_descricao);
		$peca_qtde       = @pg_fetch_result($res,$i,qtde);
		$defeito         = @pg_fetch_result($res,$i,defeito);
		$servico         = @pg_fetch_result($res,$i,servico_realizado);
	}

	if (strlen($msg_erro) > 0) {
		$os_item         = trim($_POST["os_item_".$i]);
		$peca            = trim($_POST["peca_".$i]);
		$peca_referencia = trim($_POST["peca_referencia_".$i]);
		$peca_descricao  = trim($_POST["peca_descricao_".$i]);
		$peca_qtde       = trim($_POST["peca_qtde_".$i]);
		$defeito         = trim($_POST["defeito_".$i]);
		$servico         = trim($_POST["servico_".$i]);
	}
?>
	<tr>
		<td>
			<input type="hidden" name="os_item_<? echo $i ?>" value="<? echo $os_item ?>">
			<input type="hidden" name="peca_<? echo $i ?>" value="<? echo $peca ?>">
			<input type="hidden" name="produto">
			<input type="hidden" name="preco">
			<input class="frm" type="text" name="peca_referencia_<? echo $i ?>" size="15" value="<? echo $peca_referencia ?>">
			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_referencia.value , document.frm_os.peca_referencia_<? echo $i ?> , document.frm_os.peca_descricao_<? echo $i ?>, document.frm_os.preco , 'referencia')" alt="Clique para efetuar a pesquisa" style='cursor:pointer;'>
		</td>
		<td>
			<input class="frm" type="text" name="peca_descricao_<? echo $i ?>" size="25" value="<? echo $peca_descricao ?>">
			<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_referencia.value , document.frm_os.peca_referencia_<? echo $i ?> , document.frm_os.peca_descricao_<? echo $i ?>, document.frm_os.preco , 'descricao')" alt="Clique para efetuar a pesquisa" style='cursor:pointer;'>
		</td>
		<td>
			<input class="frm" type="text" name="peca_qtde_<? echo $i ?>" size="3" value="<? echo $peca_qtde ?>">
		</td>
		<td>
			<select class='frm' size='1' name='defeito_<? echo $i ?>'>
				<option></option>
				<?
				$sqlD = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica;";
				$resD = pg_query ($con,$sqlD) ;
				for ($x = 0 ; $x < pg_num_rows($resD) ; $x++ ) {
					echo "<option ";
					if ($defeito == pg_fetch_result($resD,$x,defeito)) echo " selected ";
					echo " value='" . pg_fetch_result($resD,$x,defeito) . "'>" ;
					echo pg_fetch_result($resD,$x,descricao) ;
					echo "</option>";
				}
				?>
			</select>
		</td>
		<td>
			<select class='frm' size='1' name='servico_<? echo $i ?>'>
				<option></option>
<?
				$sqlS = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

				if (strlen($linha) > 0) {
					$sqlS .= " AND tbl_servico_realizado.linha = '$linha' ";
				}

				if ($login_pede_peca_garantia == 't') {
					$sqlS .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				}

				if ($login_fabrica == 1) {
					if ($login_reembolso_peca_estoque == 't') {
						$sqlS .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						$sqlS .= "OR tbl_servico_realizado.descricao ILIKE '%pedido%') ";
					}else{
						$sqlS .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
						$sqlS .= "OR tbl_servico_realizado.descricao NOT ILIKE '%pedido%') ";
					}
				}

				$sqlS .= "AND tbl_servico_realizado.ativo = 't' ORDER BY descricao ";
				$resS = pg_query ($con,$sqlS) ;

				for ($x = 0 ; $x < pg_num_rows($resS) ; $x++ ) {
					echo "<option ";
					if ($servico == pg_fetch_result($resS,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_fetch_result($resS,$x,servico_realizado) . "'>" ;
					echo pg_fetch_result($resS,$x,descricao) ;
					echo "</option>";
				}
				?>
		</td>
	</tr>
<?
}
?>
</table>

<? }else{ ?>
<input type="hidden" name="troca_faturada" value="<?echo $troca_faturada?>">
<table border="0" cellpadding="2" cellspacing="0" align="center" width="750">
	<tr valign="top" align="left">
		<td>
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
						if ($motivo_troca == pg_fetch_result ($res,$i,defeito_constatado) ) echo " selected ";
						echo " value='" . pg_fetch_result ($res,$i,defeito_constatado) . "'>" ;
						echo pg_fetch_result ($res,$i,codigo) ." - ". pg_fetch_result ($res,$i,descricao) ;
						echo "</option>\n";
					}
					?>
			</select>
		</td>
	</tr>
</table>
<? } ?>

<br>
<?

//if($compressor=='t' or $tipo_atendimento == 65 or $tipo_atendimento == 69){

//*coloquei 24-01*//
	// por km é 0,40 centavos
	// por hora é 24 reais, 0,40 por minuto
	//COMPRESSOR TEM UM DIFERENCIAL
#TAKASHI LIBERAR PARA SILVANIA DA BLACK OS COMPRESSOR
#TAKASHI LIBERAR PARA SILVANIA DA BLACK OS COMPRESSOR	

$mostrar = ($compressor=='t' or $tipo_atendimento == 65 or $tipo_atendimento == 69) ? "display:inline;":"display:none;";

echo "<div id='mostra_visita' style='$mostrar'>";
	echo "<table width='600' border='1' align='center'  cellpadding='1' cellspacing='3 class='border'>";
		echo "<tr>";
		echo "<td nowrap colspan='6' class='menu_top'><B><font size='2' face='Geneva, Arial, Helvetica, san-serif'>OUTRAS DESPESAS</font></b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td nowrap class='menu_top' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";

		if($tipo_atendimento != 65 AND $tipo_atendimento != 69){
		echo "<td nowrap class='menu_top mostra' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
		echo "<td nowrap class='menu_top mostra' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
		}

		echo "<td nowrap class='menu_top' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
		if(($tipo_atendimento == 65 OR $tipo_atendimento == 69) AND strlen($os)>0){
			echo "<td nowrap class='menu_top mostra_qtde' rowspan='2'>
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
				tbl_os_visita.valor_adicional
			FROM    tbl_os_visita
			WHERE   tbl_os_visita.os = $os
			ORDER BY tbl_os_visita.os_visita;";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		
		for ($y=0;$qtde_visita>$y;$y++){
			$os_visita            = trim(@pg_fetch_result($res,$y,os_visita));
			$visita_data          = trim(@pg_fetch_result($res,$y,data));
			$hr_inicio            = trim(@pg_fetch_result($res,$y,hora_chegada_cliente));
			$hr_fim               = trim(@pg_fetch_result($res,$y,hora_saida_cliente));
			$visita_km            = trim(@pg_fetch_result($res,$y,km_chegada_cliente));
			$justificativa_adicionais   = trim(@pg_fetch_result($res,$y,justificativa_valor_adicional));
			$valores_adicionais         = trim(@pg_fetch_result($res,$y,valor_adicional));

			if (strlen($visita_data) == 0) {
				$visita_data = $_POST["visita_data_$y"];
			}

			if (strlen($hr_inicio) == 0) {
				$hr_inicio = $_POST["visita_hr_inicio_$y"];
			}

			if (strlen($hr_fim) == 0) {
				$hr_fim = $_POST["visita_hr_fim_$y"];
			}

			if (strlen($visita_km) == 0) {
				$visita_km = $_POST["visita_km_$y"];
			}

			if (strlen($justificativa_adicionais) == 0) {
				$justificativa_adicionais = $_POST["justificativa_adicionais_$y"];
			}

			if (strlen($valores_adicionais) == 0) {
				$valores_adicionais = $_POST["valores_adicionais_$y"];
			}

			echo "<tr>";
			echo "<td nowrap align='center' width='200'>";
			echo "<INPUT TYPE='text' NAME='visita_data_$y' value='$visita_data' size='12' maxlength='10' class='frm' onKeyUp=\"formata_data_visita(this.value, 'frm_os', $y)\";>"; 
			echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>dd/mm/aaaa</font>";
			echo "</td>";

			if($tipo_atendimento != 65 AND $tipo_atendimento != 69){
			echo "<td nowrap align='center' class='mostra'>";
			echo "<INPUT TYPE='text' NAME='visita_hr_inicio_$y' value='$hr_inicio' size='5' maxlength='5' class='frm' onKeyUp=\"formata_cnpj(this.value, 'frm_os', $y)\";>";
			echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>09:23</font>";
			echo " </td>";

			echo "<td nowrap align='center' class='mostra'>";
			echo "<INPUT TYPE='text' NAME='visita_hr_fim_$y' value='$hr_fim' size='5' maxlength='5' class='frm' onKeyUp=\"formata_cnpj2(this.value, 'frm_os', $y)\";>";
			echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>14:51</font>";
			echo "</td>";
			}

			echo "<td nowrap align='center'>";
			echo "<INPUT TYPE='text' NAME='visita_km_$y' value='$visita_km' size='4' maxlength='4' class='frm'>";
			echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Km</font>";
			echo "</td>";

			if($tipo_atendimento == 65 OR $tipo_atendimento == 69 AND strlen($os_numero)>0){
				$sql_count = "SELECT count(*) AS qtde_itens_geo
							  FROM tbl_os
							  WHERE tbl_os.fabrica   = $login_fabrica
							  AND   tbl_os.os_numero = $os_numero
							  AND   tbl_os.sua_os LIKE '$os_numero-%';"; 
				$res_count = pg_query ($con,$sql_count) ;

				$qtde_itens_geo = pg_fetch_result($res_count,0,qtde_itens_geo);

				if($y==0 and strlen($qtde_produto_atendido)==0){
					$qtde_produto_atendido = $qtde_itens_geo;
				}
			}

			if($tipo_atendimento == 65 OR $tipo_atendimento == 69){
				echo "<td nowrap align='center' class='mostra_qtde'>";
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
			echo "</td>";

			echo "<input type='hidden' name='os_visita_$y' value='$os_visita'>";
			echo "</tr>";
		}
	echo "</table>";
echo "</div>";
//}

if($compressor=='t' or $tipo_atendimento == 64 or $tipo_atendimento == 65 or $tipo_atendimento == 69){

if ($_POST["tecnico"]) {
	$tecnico = $_POST["tecnico"];
}

if(strlen($os)>0){
	$sql_tec = "SELECT tecnico from tbl_os_extra where os=$os";
	$res_tec = pg_query($con,$sql_tec);
	$tecnico = trim(@pg_fetch_result($res_tec,0,tecnico));
}

echo "<BR><table class='border' width='620' align='center' border='1' cellpadding='1' cellspacing='3'>";
	echo "<tr>";
		echo "<td class='menu_top'>Relatório do Técnico</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<TD class='table_line'><TEXTAREA NAME='tecnico' ROWS='5' COLS='85'>$tecnico </TEXTAREA></TD>";
	echo "</tr>";
echo "</table>";
echo "<br>";
}
//TAKASHI 26/10
?>
<input type="hidden" name="btn_acao" value="">

<center><img border="0" src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_os.btn_acao.value =='') { document.frm_os.btn_acao.value='gravar'; document.frm_os.submit() }else{ alert('Aguarde submissão') }" ALT="Gravar" style="cursor:pointer;"></center>

</form>


<? include "rodape.php";?>
