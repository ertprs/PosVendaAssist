<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'ajax_cabecalho.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';


function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

$acao = $_GET["acao"];

if($acao == "pega_produto"){

	$produto_referencia = $_GET["produto_referencia"];
	
	$sql = "SELECT  tbl_produto.produto  ,
			tbl_produto.descricao,
			tbl_linha.linha      ,
			tbl_familia.familia
		FROM  tbl_produto
		JOIN  tbl_linha   USING(linha)
		JOIN  tbl_familia USING(familia)
		WHERE referencia        = '$produto_referencia'
		AND   tbl_linha.fabrica = $login_fabrica ";
	$res = pg_exec ($con,$sql);
	
	if(pg_numrows($res)>0){

		$produto    = pg_result($res,0,produto)  ;
		$linha      = pg_result($res,0,linha)    ;
		$familia    = pg_result($res,0,familia)  ;
		$descricao  = pg_result($res,0,descricao);

		$resposta = "<u><i>$produto_referencia - $descricao</b></u>";
		$lista    = "<a class='lnk' href='peca_consulta_por_produto.php?produto=$produto' target='_blank'><font color='#FFFFFF'><u>Lista Básica</u></font></a>";

		echo  "ok|$produto|$linha|$familia|$resposta|$lista";

	}else{
		echo "1|Produto ainda não lançado ou não encontrado";
	}
	exit;
}



//--==== Gravar OS ===============================================================
if($acao == "gravar"){

	$msg_erro      = "";
	$nao_permitido = array("-"," ","/",".");

	if (strlen($msg_erro) == 0){

		$orcamento              = strtoupper(trim($_POST['orcamento']));
		$tipo_orcamento         = strtoupper(trim($_POST['tipo_orcamento']));

//		$vendedor               = strtoupper(trim($_POST['vendedor']));
		$vendedor               = $login_empregado;

		$aprovacao              = strtoupper(trim($_POST['aprovacao']));

		$ja_aprovado            = strtoupper(trim($_POST['ja_aprovado']));


		$aprovacao_data       = "NULL";
		$aprovacao_tipo       = "NULL";
		$reprovacao_data      = "NULL";
		$reprovacao_motivo    = "NULL";
		$aprovado             = "NULL";
		$aprovacao_responsavel= "NULL";
		$valor_total_orcamento=0;
		$status="NULL";
		$status_os="NULL";


		if ($aprovacao=='APROVAR'){
			$aprovacao_tipo        = strtoupper(trim($_POST['txt_tipo_aprovado']));
			$aprovacao_responsavel = strtoupper(trim($_POST['txt_quem_aprovou']));
			$aprovacao_data        = "'".date("Y-m-d H:i:s")."'";
			$aprovado="'t'";
			$msg_aprovacao = "Orçamento aprovado em ".date("d/m/Y")." por $aprovacao_responsavel via $aprovacao_tipo";
			$aprovacao_tipo        ="'$aprovacao_tipo'";
			$aprovacao_responsavel ="'$aprovacao_responsavel'";
			$status = 28;
			$status_os = 80;
		}
		if ($aprovacao=='REPROVAR'){
			$reprovacao_motivo     = strtoupper(trim($_POST['txt_motivo_reprova']));
			$aprovacao_responsavel = strtoupper(trim($_POST['txt_quem_reprovou']));
			$reprovacao_data       = "'".date("Y-m-d H:i:s")."'";
			$aprovado="'f'";
			$msg_aprovacao = "Orçamento REPROVADO em ".date("d/m/Y")." por $aprovacao_responsavel pelo motivo $reprovacao_motivo";

			$reprovacao_motivo      = "'$reprovacao_motivo'";
			$aprovacao_responsavel  = "'$aprovacao_responsavel'";
			$status=31;
			$status_os = 81;
		}

		$cliente_cliente         = strtoupper(trim($_POST['cliente_cliente']));
		$cliente_nome            = strtoupper(trim($_POST['cliente_nome']));
		$cliente_cnpj            = strtoupper(trim($_POST['cliente_cnpj']));
		$cliente_endereco        = strtoupper(trim($_POST['cliente_endereco']));
		$cliente_numero          = strtoupper(trim($_POST['cliente_numero']));
		$cliente_complemento     = strtoupper(trim($_POST['cliente_complemento']));
		$cliente_bairro          = strtoupper(trim($_POST['cliente_bairro']));
		$cliente_cidade          = strtoupper(trim($_POST['cliente_cidade']));
		$cliente_estado          = strtoupper(trim($_POST['cliente_estado']));
		$cliente_cep             = strtoupper(trim($_POST['cliente_cep']));
		$cliente_fone_residencial= strtoupper(trim($_POST['cliente_fone_residencial']));
		$cliente_fone_comercial  = strtoupper(trim($_POST['cliente_fone_comercial']));
		$cliente_fone_celular    = strtoupper(trim($_POST['cliente_fone_celular']));
		$cliente_fone_fax        = strtoupper(trim($_POST['cliente_fone_fax']));
		$cliente_email           = strtoupper(trim($_POST['cliente_email']));

		
		$marca                  = strtoupper(trim($_POST['marca']));
		$fabricante_nome        = strtoupper(trim($_POST['fabricante_nome']));
		
		$produto                = strtoupper(trim($_POST['produto']));
		$produto_referencia     = strtoupper(trim($_POST['produto_referencia']));
		$produto_descricao      = strtoupper(trim($_POST['produto_descricao']));
		$produto_serie          = strtoupper(trim($_POST['produto_serie']));
		$produto_aparencia      = strtoupper(trim($_POST['produto_aparencia']));
		$produto_acessorios     = strtoupper(trim($_POST['produto_acessorios']));
#		$revenda_nome           = strtoupper(trim($_POST['revenda_nome']));
#		$nota_fiscal            = strtoupper(trim($_POST['nota_fiscal']));
#		$data_nf                = strtoupper(trim($_POST['data_nf']));

		$defeito_constatado     = strtoupper(trim($_POST['defeito_constatado']));
		$defeito_reclamado      = strtoupper(trim($_POST['defeito_reclamado'] ));
		$solucao                = strtoupper(trim($_POST['solucao_os']        ));

		$fazer_visita           = strtoupper(trim($_POST['fazer_visita']));
		$data_visita            = trim($_POST['txt_data_visita']);
		$data_visita_novo       = trim($_POST['txt_data_visita']);
		$horario_visita         = trim($_POST['txt_horario_visita']);

		$data_visita_anterior   = trim($_POST['txt_data_visita_anterior']);
		$horario_visita_anterior= trim($_POST['txt_horario_visita_anterior']);
		$visita_status          = trim($_POST['visita_status']);
		$efetuar_visita         = trim($_POST['efetuar_visita']);

		$valores_descontos      = trim($_POST['valores_descontos']);
		$valores_acrescimos     = trim($_POST['valores_acrescimos']);

		$condicao_pagamento     = trim($_POST['condicao_pagamento']);

		$data_previsao           = trim($_POST['data_previsao']);
		$data_previsao_ok        = strtoupper(trim($_POST['data_previsao_ok']));
		$prateleira_box          = strtoupper(trim($_POST['prateleira_box']));
		$obs                     = strtoupper(trim($_POST['obs']));

		if (strlen($condicao_pagamento)==0) $condicao_pagamento="NULL";

		if (strlen($valores_descontos)==0){
			$valores_acrescimos = "NULL";
		}
		if (strlen($valores_acrescimos)==0){
			$valores_acrescimos = "NULL";
		}

		
		if (strlen($data_previsao)==0){
			$data_previsao="NULL";
		}else{
			if (converte_data($data_previsao)){
				$data_previsao_text = $data_previsao;
				$data_previsao = converte_data($data_previsao);
				$data_previsao = "'$data_previsao'";
			}else{
				$msg_erro .="Data da previsão incorreta!";
			}

		}

		if ($fazer_visita=='SIM'){
			if (strlen($data_visita)==0){
				 $msg_erro .= " Preencha a data da visita<br>";
			}
			if (converte_data($data_visita) AND strlen($msg_erro)==0){
				$data_visita = converte_data($data_visita);
			}
			if (strlen($horario_visita)==0){
				 $msg_erro .= " Preencha o horário da visita<br>";
			}
			if (strlen($msg_erro)==0){
				$timestamp_visita = "'$data_visita $horario_visita:00'";
			}
			if ($efetuar_visita=='SIM'){
				$visita_status ="Executada";
			}
		}else{
			$timestamp_visita       = "NULL";
			$data_visita            = "";
			$data_visita_novo       = "";
			$horario_visita         = "";
			$visita_status          = "NULL";
		}




		//--===== Campos Obrigatórios no Orçamento =========================================
		
		if (strlen($tipo_orcamento)  == 0) $msg_erro .= " Escolha o tipo de orçamento<br>";
		if (strlen($vendedor)        == 0) $msg_erro .= " Selecione o vendedor. <br>";
		if (strlen($cliente_nome) == 0)    $msg_erro .= " Digite o nome do cliente. <br>";
//		if (strlen($cliente_fone_residencial) == 0) $msg_erro .= " Digite o telefone do cliente. <br>";

		if(strlen ($cliente_fone_residencial)== 0) $cliente_fone_residencial = "NULL" ;
		else                                       $cliente_fone_residencial = "'$cliente_fone_residencial'" ;

		$cliente_nome = "'$cliente_nome'" ;


		################ Valida Cliente ############################################################
		if (strlen($cliente_cliente)==0 AND strlen($cliente_cnpj)>0 ){
			$sql = "SELECT tbl_pessoa.pessoa FROM tbl_pessoa JOIN tbl_pessoa_cliente USING(pessoa) WHERE tbl_pessoa_cliente.empresa=$login_empresa AND cnpj = '$cliente_cnpj'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) >0){
				$cliente_cliente   = pg_result ($res,0,0);
			}
		}

		## RETIRADO A VALIDACAO TEMPORARIAMENTE
		if (strlen($cliente_cnpj) == 0 AND strlen($aprovado) == "APROVAR" AND $aprovado <> "NULL" AND $tipo_orcamento<>"VENDA"){
			$msg_erro .= " Digite o CPF/CNPJ do cliente.<br>";
		}


		################### FORA DE GARANTIA ##################################################
		if($tipo_orcamento == 'FORA_GARANTIA'){

			if (strlen($marca)>0){
				$sql_m = "SELECT fabrica,nome
						FROM   tbl_marca
						WHERE  marca=$marca";
				$res_m = pg_exec ($con,$sql_m);
				if (pg_numrows ($res_m) >0){
					$fabricaX = pg_result ($res_m,0,0);
					$fabrica_nome  = pg_result ($res_m,0,1);
					if ($fabricaX=="0"){
						$fabricaX="";
						$produto = "null";
					}
				}
			}

			if (strlen($marca)==0){
				$msg_erro="Selecione a marca do produto";
			}

			if (strlen($produto_referencia) == 0 AND strlen($produto_descricao) == 0)
				$msg_erro .= " Digite o produto.<br>"       ;

			$produto_descricao = "'" . $produto_descricao . "'";

			if (strlen($marca)==0 AND strlen($fabricante_nome)==0){
				$msg_erro .= " Digita marca do produto.<br>";
				$produto = "null";
			}
			if (strlen($fabricante_nome)>0){
				$fabricante_nome = "'$fabricante_nome'";
			}else{
				$fabricante_nome="NULL";
			}

			if(strlen($produto_referencia) >0){
				$produto_referencia = str_replace ($nao_permitido,"",$produto_referencia);
				if (strlen($fabricaX)>0){
					$sql = "SELECT tbl_produto.produto
							FROM   tbl_produto
							JOIN   tbl_linha USING (linha)
							WHERE  UPPER(tbl_produto.referencia_pesquisa) = '$produto_referencia'
							AND    tbl_linha.fabrica                      = $fabricaX
							AND    tbl_produto.ativo IS TRUE";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 0) {
						//$msg_erro .= "Produto $produto_referencia não cadastrado para a $fabrica_nome";
						$produto = "null";
					}
					else $produto   = pg_result ($res,0,0);	
				}
			}else{
				$produto = "null";
			}
			if(strlen($msg_erro) == 0){
				$data_nf = fnc_formata_data_pg($data_nf);
//				if($data_nf == 'null') $msg_erro .= " Digite a data de compra.<br>"; // nao é preciso validar
			}

			if (strlen($produto)==0){
				$produto="NULL";
			}

			if(strlen($nf_posto)>0){
				$sql = "SELECT orcamento
						FROM tbl_orcamento
						JOIN tbl_orcamento_os USING(orcamento)
						WHERE empresa       = $login_empresa
						AND   nf_saida      = '$nf_posto'
						AND   data_nf_saida > current_date - interval '90 days'
						AND   fechamento    IS NOT NULL";
				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) == 0) {
					$orcamento_garantia = "null";
				}else $orcamento_garantia   = pg_result ($res,0,0);

			}else $orcamento_garantia = "null";

			if (strlen ($produto_serie)   == 0)   $produto_serie      = "null"                         ;
			else                                  $produto_serie      = "'" . $produto_serie . "'"     ;
			if (strlen ($revenda)           == 0) $revenda            = "null"                         ;
			else                                  $revenda            = "'" . $revenda . "'"           ;
			if (strlen ($produto_aparencia) == 0) $produto_aparencia  = "null"                         ;
			else                                  $produto_aparencia  = "'" . $produto_aparencia . "'" ;
			if (strlen ($produto_acessorios)== 0) $produto_acessorios = "null"                         ;
			else                                  $produto_acessorios = "'" . $produto_acessorios . "'";
			if (strlen ($nota_fiscal)       == 0) $nota_fiscal        = "null"                         ;
			else                                  $nota_fiscal        = "'" . $nota_fiscal . "'"       ;
			if (strlen ($defeito_reclamado) == 0) $defeito_reclamado  = "null"                         ;
			else                                  $defeito_reclamado  = "'" . $defeito_reclamado. "'" ;
			if (strlen ($defeito_constatado)== 0) $defeito_constatado = "null"                         ;
			else                                  $defeito_constatado = "'" . $defeito_constatado. "'";
			if (strlen ($solucao)           == 0) $solucao            = "null"                         ;
			else                                  $solucao            = "'" . $solucao. "'"           ;
			if (strlen ($tecnico)   == 0)         $tecnico            = "null"                         ;
			else                                  $tecnico            = "'" . $tecnico . "'"           ;
		}


##############################################################################################################
######################################## INICIO DA GRAVAÇÃO ##################################################
##############################################################################################################
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if ($tipo_orcamento=="VENDA"){
			$aprovacao_tipo  = "'Automático'";
			$aprovacao_data  = "'".date("Y-m-d H:i:s")."'";
			$aprovado        = "'t'";
			$msg_aprovacao   = "Venda aprovado em ".date("d/m/Y")." automaticamente";
			$status = 28;
			$status_os = 80;
		}

		if (strlen ($msg_erro) == 0) {
			if (strlen ($orcamento) == 0) {
			/*================ INSERE ORCAMENTO =======================*/
				$sql = " 
					INSERT INTO tbl_orcamento (
						consumidor_nome     ,
						consumidor_fone     ,
						vendedor            ,
						empresa             ,
						loja                ,
						aprovado            ,
						data_aprovacao      ,
						tipo_aprovacao      ,
						data_reprovacao     ,
						motivo_reprovacao   ,
						empregado_aprovacao ,
						aprovacao_responsavel,
						data_previsao       ,
						condicao_pagamento  ,
						desconto            ,
						acrescimo           ,
						status
					) VALUES (
						$cliente_nome       ,
						$cliente_fone_residencial,
						$vendedor           ,
						$login_empresa      ,
						$login_loja         ,
						$aprovado           ,
						$aprovacao_data     ,
						$aprovacao_tipo     ,
						$reprovacao_data    ,
						$reprovacao_motivo  ,
						$login_empregado    ,
						$aprovacao_responsavel,
						$data_previsao      ,
						$condicao_pagamento ,
						$valores_descontos  ,
						$valores_acrescimos ,
						$status
					)
					";
				$insere = 'ok';
			}else{
				/*================ ALTERA ORCAMENTO ==================*/
				 $sql = "
					UPDATE tbl_orcamento SET
						consumidor_nome      = $cliente_nome             ,
						consumidor_fone      = $cliente_fone_residencial ,
						data_previsao        = $data_previsao            ,
						condicao_pagamento   = $condicao_pagamento       ,";

				if ($ja_aprovado<>"SIM"){
					$sql .= "
							aprovado             = $aprovado             ,
							data_aprovacao       = $aprovacao_data       ,
							tipo_aprovacao       = $aprovacao_tipo       ,
							data_reprovacao      = $reprovacao_data      ,
							motivo_reprovacao    = $reprovacao_motivo    ,
							aprovacao_responsavel= $aprovacao_responsavel,
							empregado_aprovacao  = $login_empregado      ,
								";
				}
				if ($status!="NULL") $sql .= "status    = $status,";

				$sql .= "
						desconto  = $valores_descontos,
						acrescimo = $valores_acrescimos
					WHERE orcamento = $orcamento
					AND   empresa   = $login_empresa
					AND   loja      = $login_loja
				";
			}
			$res = pg_exec ($con,$sql);
			$aux_msg_erro = pg_errormessage($con);
			$msg_erro    .= substr($aux_msg_erro,6);
			if (strlen ($msg_erro) == 0) {
				if (strlen($orcamento) == 0) {
					$res = pg_exec ($con,"SELECT CURRVAL ('tbl_orcamento_orcamento_seq')");
					$orcamento  = pg_result ($res,0,0);
					if ($tipo_orcamento=="FORA_GARANTIA" || $tipo_orcamento=="GARANTIA"){
						$tipo="Serviço";
					}else{
						$tipo="Venda";
					}
					$sql = "INSERT INTO tbl_hd_chamado (orcamento,fabrica_responsavel,fabrica,posto,titulo,status,empregado,categoria) values ($orcamento,$login_empresa,$login_empresa,$login_loja,'Orçamento $orcamento','Novo',$login_empregado,'$tipo')";
					$res = pg_exec ($con,$sql);

					$res = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
					$hd_chamado  = pg_result ($res,0,0);

					if($tipo_orcamento == 'ORCA_VENDA'){
						$res = pg_exec ($con,"INSERT INTO tbl_orcamento_venda (orcamento) values ($orcamento)");
						$msg_erro .= pg_errormessage($con);
					}

				}else{
					$res = pg_exec ($con,"SELECT hd_chamado FROM tbl_hd_chamado WHERE orcamento=$orcamento");
					$hd_chamado  = pg_result ($res,0,0);
				}

				######################################################################################
				##################  VERIFICAÇÕES PARA INCLUIR NO HELP DESK ###########################
				######################################################################################

				if ($data_previsao_ok<>"SIM" AND strlen($data_previsao_text)>0){
					$msg_at_previsao= "<br> Data de previsão do produto é para $data_previsao_text";
				}
				if (strlen($aprovacao)>0 AND $tipo_orcamento<>"VENDA"){ // soh entra aqui se ele aprovou ou reprovou
					$msg_at_aprovacao ="<br> $msg_aprovacao";
				}
				if ($efetuar_visita=="SIM"){
					$msg_at_visitado="<br> Visita foi executada";
				}

				$msg_at_visita="";
				if ($data_visita_novo <> $data_visita_anterior OR $horario_visita<>$horario_visita_anterior){ // Verifica se mudou a data
					if ($data_visita_novo=="" || $fazer_visita==""){
						$msg_at_visita="<br>A visita foi cancelada.";
						$visita_status="Cancelada";
					}else{
						$msg_at_visita="<br>A vista marcada para $data_visita_anterior às $horario_visita_anterior foi remarcada para $data_visita_novo as $horario_visita";
						$visita_status="Pendente";
					}
				}
				if ($fazer_visita=="SIM" AND strlen($data_visita_anterior)==0){
					$msg_at_visita = "<br>Vista marcada para $data_visita_novo as $horario_visita";
				}

				if ($insere=="ok"){
					$msg_at_novo="Orçamento criado";
				}else{
					$msg_at_novo="Orçamento alterado";
				}


				$sql = "INSERT INTO tbl_hd_chamado_item 
							(hd_chamado,empregado,posto,comentario)
							VALUES ($hd_chamado,$login_empregado,$login_loja,
							'$msg_at_novo $msg_at_aprovacao $msg_at_previsao $msg_at_visita $msg_at_visitado '
						)";
				$res = pg_exec ($con,$sql);

				if ($visita_status<>"NULL") $visita_status = "'$visita_status'";

				######################################################################################
				##################                   SERVIÇO             #############################
				######################################################################################
				$valor_total_mo=0;
				if($tipo_orcamento == 'FORA_GARANTIA'){
					$sql = "SELECT * FROM tbl_orcamento_os WHERE orcamento = $orcamento";
					$res = pg_exec ($con,$sql);

					if(pg_numrows($res)==0){
						$sql = "
								INSERT INTO tbl_orcamento_os(
									orcamento         ,
									tecnico           ,
									fabrica           ,
									fabricante_nome   ,
									abertura          ,
									defeito_reclamado ,
									defeito_constatado,
									solucao           ,
									marca             ,
									produto           ,
									produto_descricao ,
									serie             ,
									aparencia         ,
									acessorios        ,
									revenda           ,
									data_nf           ,
									nf                ,
									garantia          ,
									data_visita       ,
									status            ,
									prateleira_box    ,
									obs               ,
									orcamento_garantia
								)VALUES(
									$orcamento         ,
									$tecnico           ,
									$login_empresa     ,
									$fabricante_nome   ,
									current_date       ,
									$defeito_reclamado ,
									$defeito_constatado,
									$solucao           ,
									$marca             ,
									$produto           ,
									$produto_descricao ,
									$produto_serie     ,
									$produto_aparencia ,
									$produto_acessorios,
									$revenda           ,
									$data_nf           ,
									$nota_fiscal       ,
									0                  ,
									$timestamp_visita  ,
									$status_os         ,
									'$prateleira_box'  ,
									'$obs'             ,
									$orcamento_garantia
								)";
					}else{
						$sql = "UPDATE tbl_orcamento_os SET
									tecnico            = $tecnico           ,
									marca              = $marca             ,
									produto            = $produto           ,
									produto_descricao  = $produto_descricao ,
									defeito_reclamado  = $defeito_reclamado ,
									defeito_constatado = $defeito_constatado,
									solucao            = $solucao           ,
									serie              = $produto_serie     ,
									aparencia          = $produto_aparencia ,
									acessorios         = $produto_acessorios,
									revenda            = $revenda           ,
									data_nf            = $data_nf           ,
									nf                 = $nota_fiscal       ,
									data_visita        = $timestamp_visita  ,
									status_visita      = $visita_status     ,
									prateleira_box     = '$prateleira_box'  ,
									obs                = '$obs'             ,
									orcamento_garantia = $orcamento_garantia";

						if ($ja_aprovado<>"SIM") $sql .= ",status   =   $status_os";

						$sql .= " WHERE orcamento = $orcamento";


					}

					$res = pg_exec ($con,$sql);
					$aux_msg_erro = pg_errormessage($con);
					$msg_erro    .= substr($aux_msg_erro,6);

				}

				######################################################################################
				##################                   PEÇAS               #############################
				######################################################################################

				$qtde_item = $_POST['qtde_item'];
				$qtde_item = 20; //PROVISORIO
				$valor_total_pecas=0;

				if (strlen ($msg_erro) == 0) {
					$sql = "DELETE FROM tbl_orcamento_item
							WHERE orcamento=$orcamento";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

			//$array_peca_nao_cadastrada=array();
				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$xorcamento_item = trim($_POST["orcamento_item_$i"]);
					$xpeca           = trim($_POST["peca_$i"]);
					$xpeca_referencia= trim($_POST["referencia_peca_$i"]);
					$xpeca_descricao = trim($_POST["descricao_peca_$i"]);
					$xqtde           = trim($_POST["peca_qtde_$i"]);
					$xpreco          = trim($_POST["peca_preco_$i"]);
					$xdefeito        = trim($_POST["defeito_$i"]);
#					$xpcausa_defeito = trim($_POST["causa_defeito_$i"]);
#					$xservico        = trim($_POST["peca_servico_realizado_$i"]);

					if (strlen($xpeca_referencia)==0 AND strlen($xpeca)==0 AND strlen($xpeca_descricao)==0) continue; //pula as peças que estão em branco
		
					$xdescricao      = trim($_POST["descricao_". $i]);
					
					if(strlen($xpreco)==0) $xpreco="0.00";
// validacao por referencia if (strlen($xpeca_referencia)==0) $msg_erro .= "Informe a peça<br>";

					if (strlen ($xqtde) == 0)		$xqtde = 1;
					if ($xqtde==0)					$msg_erro .= "Informe a quantidade do produto!<br>";
					if(strlen($xpeca_referencia) > 0) {
						$sql="SELECT tbl_estoque.qtde from tbl_estoque join tbl_peca using (peca )where referencia='$xpeca_referencia'";
						$res=pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$qtde_estoque = 0;
						if(pg_numrows($res)>0){
							$qtde_estoque=pg_result($res,0,qtde);
						}
						
					}

					//$xpeca_referencia = str_replace ($nao_permitido , "" , $xpeca_referencia);

					if (strlen($xpeca) == 0 OR strlen($xpeca_referencia) == 0 OR ($qtde_estoque - $xqtde < 0)) {
						if (strlen($fabricaX)==0){
							$fabricaX = "NULL";
						}
						$sql = "SELECT *
									FROM   tbl_peca
									WHERE  tbl_peca.referencia = '$xpeca_referencia'
									AND (tbl_peca.fabrica = $fabricaX OR tbl_peca.fabrica = $login_empresa)";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$peca_existe = pg_numrows ($res);

						if ($peca_existe == 0 OR strlen($xpeca_referencia) == 0 OR ($qtde_estoque - $xqtde < 0)) {
							//HD 6118 PAULO COLOCOU OR $tipo_orcamento== 'VENDA' 
							//if($tipo_orcamento == 'ORCA_VENDA' OR $tipo_orcamento== 'VENDA'){

								$sql =" SELECT requisicao 
											FROM tbl_requisicao 
											WHERE orcamento = $orcamento 
											AND   empresa   =$login_empresa ";
								$res = pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage($con);
								if(pg_numrows($res)==0){
									$sql = "
											INSERT INTO tbl_requisicao (
												data             ,
												hora             ,
												empregado          ,
												empresa          ,
												orcamento        ,
												status
											) VALUES (
												current_date     ,
												current_time     ,
												$login_empregado ,
												$login_empresa   ,
												$orcamento       ,
												'aberto' 
											)";
									$res = pg_exec ($con,$sql);
									$msg_erro .= pg_errormessage($con);
									$sql= " SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao";
									$res= pg_exec($con, $sql);
									$requisicao=trim(pg_result($res,0,requisicao));
		
								}else {
									$requisicao=trim(pg_result($res,0,requisicao));
									$sql ="	UPDATE tbl_requisicao SET
												data          =current_date         ,
												hora          =current_time         ,
												empregado       =$login_empregado     ,
												status        ='aberto'             
											WHERE requisicao = $requisicao";
									$res = pg_exec ($con,$sql);
									$msg_erro .= pg_errormessage($con);
								}
								$sql="SELECT peca from tbl_peca 
										WHERE referencia = '$xpeca_referencia'
										AND   fabrica = $login_empresa";
								$res=pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
								if(pg_numrows($res)==0){
									$nao_exite_peca = "sim";
								}
								if ($peca_existe == 0 or $nao_exite_peca == "sim" OR strlen($xpeca_referencia) == 0){
										if($login_fabrica != 10){
										$sql = "INSERT INTO tbl_peca (
													referencia    ,
													descricao     ,
													origem        ,
													ativo         ,
													fabrica
												)VALUES (
													'$xpeca_referencia'   ,
													'$xpeca_descricao'    ,
													'nacional'            ,
													't'                   ,
													$login_empresa
												)";
										$res = pg_exec ($con,$sql);
										$msg_erro .= pg_errormessage($con);

										$sql= " SELECT CURRVAL ('seq_peca') as peca";
										$res= pg_exec($con, $sql);
										$id_peca = pg_result ($res,0,0);
										$peca = $id_peca;
										$xpeca =$id_peca;
									
										//Hd 5941 PARA QTD SOLICITADA MAIOR QUE QTD NO ESTOQUE.
										if( $qtde_estoque-$xqtde < 0 ) {

										}
																			
										$sql = "INSERT INTO tbl_peca_item (
													familia                 ,
													linha                   ,
													peca                    
													)VALUES(
													767                     ,
													447                     ,
													$id_peca                
													)";
										$res= pg_exec($con, $sql);
										$msg_erro .= pg_errormessage($con);
										}
									}
								


								if( $qtde_estoque-$xqtde < 0 ) {
									$sql="SELECT peca from tbl_peca 
											WHERE referencia = '$xpeca_referencia'
											AND   fabrica = $login_empresa";
									$res=pg_exec($con,$sql);
									$msg_erro .= pg_errormessage($con);
									if(pg_numrows($res)>0){
										$aux_peca = pg_result ($res,0,peca);
										$xpeca =$aux_peca;
										$aux_qtde=$xqtde-$qtde_estoque;
									}
								} else {
									$aux_qtde=$xqtde;
								}
							
								$sql= "INSERT INTO tbl_requisicao_item(
											requisicao   ,
											peca         ,
											quantidade   ,
											status
											) Values (
											$requisicao  ,
											$xpeca        ,
											$aux_qtde       ,
											'aberto' 
											)";
								$res= pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);

								if($peca_existe == 0 OR strlen($xpeca_referencia) == 0 ) {
									$sql = "SELECT tbl_estoque.peca
											FROM tbl_estoque
											WHERE tbl_estoque.peca = $id_peca";
									$res = pg_exec($con, $sql);

									if(pg_numrows($res)==0){
										$sql = "INSERT INTO tbl_estoque(peca,qtde)values($id_peca,0)";
										$res = pg_exec($con, $sql);
										$msg_erro .= pg_errormessage($con);

										$sql = "INSERT INTO tbl_estoque_extra(peca,data_atualizacao)values($id_peca,current_date)";
										$res = pg_exec($con, $sql);
										$msg_erro .= pg_errormessage($con);
									}
								}

							//}else{
							//	$msg_erro .= "Peça $xpeca_referencia não cadastrada ou sem estoque.<br>";
							//	$linha_erro = $i;
							//}
						}else{
							$xpeca      = pg_result ($res,0,peca);
						}
						
					}
							
					if(strlen($xpeca)>0 or  $qtde_estoque-$xqtde < 0){
						$xdescricao = "'".$xdescricao."'";
						$R = $i+1;

						if(strlen($msg_erro)==0){
							 $sql = "
									INSERT INTO tbl_orcamento_item (
										orcamento         ,
										peca              ,
										preco             ,
										qtde              ,
										descricao         
									)VALUES(
										$orcamento       ,
										$xpeca           ,
										$xpreco          ,
										$xqtde           ,
										'$xpeca_descricao' 
									)
									";
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							$valor_total_pecas += $xpreco * $xqtde;
							if (strlen ($msg_erro) > 0) break ;
						}
					}
				}
			
				######################################################################################
				##################               MAO DE OBRA             #############################
				######################################################################################
				$qtde_mo = $_POST['qtde_mo'];
				$qtde_mo = 20; //PROVISORIO

				if (strlen ($msg_erro) == 0) {
					$sql = "DELETE FROM tbl_orcamento_mao_de_obra
							WHERE orcamento=$orcamento";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$xservico      = trim($_POST["servico_$i"]);
					$xmao_valor    = trim($_POST["mao_valor_$i"]);
					$xmao_qtde     = trim($_POST["mao_qtde_$i"]);

					if (strlen($xservico)==0 AND strlen($xmao_valor)==0) continue; //pula as peças que estão em branco

					if (strlen ($xmao_qtde) == 0)
						$xmao_qtde = 1;

					$sql = "SELECT servico, descricao,valor
							FROM   tbl_servico
							WHERE  servico=$xservico
							AND    fabrica = $login_empresa
							AND    ativo IS TRUE";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if (pg_numrows ($res) == 0) {
						$msg_erro .= "Mão de Obra $xservico não cadastrada ou Inativa<br>";
						break;
					}else{
						$xservico   = pg_result ($res,0,servico);
						$xdescricao = pg_result ($res,0,descricao);
						$xvalor     = pg_result ($res,0,valor);
					}

					if ($xmao_valor<$xvalor){
						$msg_erro .= "Mão de Obra com valor inferior ao valor padrão.<br>";
						break;
					}
		
					if(strlen($xservico)>0){
						if(strlen($msg_erro)==0){
							$sql = "
									INSERT INTO tbl_orcamento_mao_de_obra (
										orcamento         ,
										servico           ,
										data_lancamento   ,
										descricao         ,
										qtde              ,
										valor
									)VALUES(
										$orcamento       ,
										$xservico        ,
										current_timestamp,
										'$xdescricao'    ,
										$xmao_qtde       ,
										$xmao_valor       
									)
									";
							$res = pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
							$valor_total_mo += $xmao_qtde * $xmao_valor;
							if (strlen ($msg_erro) > 0) break ;
						}
					}
				}
			}
			if (strlen($valores_descontos)==0) $valores_descontos=0;
			if (strlen($valores_acrescimos)==0) $valores_acrescimos=0;
			$total=0;
			if (strlen($orcamento) > 0) {
				$sql=" SELECT tbl_pedido.cotacao_fornecedor ,
							  tbl_pedido.total              ,
							  tbl_cotacao_fornecedor.cotacao,
							  tbl_cotacao.orcamento
						FROM tbl_cotacao
						JOIN tbl_cotacao_fornecedor ON tbl_cotacao_fornecedor.cotacao = tbl_cotacao.cotacao
						JOIN tbl_pedido ON tbl_cotacao_fornecedor.cotacao_fornecedor = tbl_pedido.cotacao_fornecedor
						WHERE orcamento = $orcamento";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (pg_numrows($res) > 0) {
					$total          = trim(pg_result($res,0,total));
				}
			}

			if (strlen ($msg_erro) == 0){
					$sql = "UPDATE tbl_orcamento
							SET total_mao_de_obra = $valor_total_mo,
								total_pecas       = $valor_total_pecas,
								total             = $valor_total_mo+$valor_total_pecas-$valores_descontos+$valores_acrescimos-$total
						WHERE orcamento = $orcamento";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
			}

		}
		######################################################################################
		##################         CLIENTE - CONSUMIDOR          #############################
		######################################################################################
		//--===== Cliente do Orçamento ========================================================
			if(strlen ($cliente_endereco)    == 0) $cliente_endereco    = "NULL" ;
			else                                   $cliente_endereco    = "'$cliente_endereco'" ;
			if(strlen ($cliente_numero)      == 0) $cliente_numero      = "NULL" ;
			else                                   $cliente_numero      = "'$cliente_numero'" ;
			if(strlen ($cliente_complemento) == 0) $cliente_complemento = "NULL" ;
			else                                   $cliente_complemento = "'$cliente_complemento'" ;
			if(strlen ($cliente_bairro)      == 0) $cliente_bairro      = "NULL" ;
			else                                   $cliente_bairro      = "'$cliente_bairro'" ;
			if(strlen ($cliente_cep)         == 0) $cliente_cep         = "NULL" ;
			else                                   $cliente_cep         = "'$cliente_cep'" ;
			if(strlen ($cliente_cidade)      == 0) $cliente_cidade      = "NULL" ;
			else                                   $cliente_cidade      = "'$cliente_cidade'" ;
			if(strlen ($cliente_estado)      == 0) $cliente_estado      = "NULL" ;
			else                                   $cliente_estado      = "'$cliente_estado'" ;

			if(strlen ($cliente_fone_comercial)  == 0) $cliente_fone_comercial   = "NULL" ;
			else                                       $cliente_fone_comercial   = "'$cliente_fone_comercial'" ;
			if(strlen ($cliente_fone_celular)    == 0) $cliente_fone_celular     = "NULL" ;
			else                                       $cliente_fone_celular     = "'$cliente_fone_celular'" ;
			if(strlen ($cliente_fone_fax)        == 0) $cliente_fone_fax         = "NULL" ;
			else                                       $cliente_fone_fax         = "'$cliente_fone_fax'" ;
			if(strlen ($cliente_email)           == 0) $cliente_email            = "NULL" ;
			else                                       $cliente_email            = "'$cliente_email'" ;

			$cliente_cep = str_replace("-","",$cliente_cep);
			$cliente_cep = str_replace(".","",$cliente_cep);
			$cliente_cep = str_replace("/","",$cliente_cep);

		if(strlen($cliente_cliente)==0  AND strlen ($msg_erro)==0 AND $tipo_orcamento != 'VENDA'){
			$sql = "INSERT INTO tbl_pessoa (
					nome            ,
					endereco        ,
					numero          ,
					complemento     ,
					bairro          ,
					cidade          ,
					estado          ,
					cep             ,
					fone_residencial,
					fone_comercial  ,
					cel             ,
					fax             ,
					cnpj            ,
					email           ,
					empresa
				) VALUES (
					$cliente_nome  ,
					$cliente_endereco ,
					$cliente_numero,
					$cliente_complemento,
					$cliente_bairro,
					$cliente_cidade,
					$cliente_estado,
					$cliente_cep,
					$cliente_fone_residencial,
					$cliente_fone_comercial,
					$cliente_fone_celular,
					$cliente_fone_fax,
					'$cliente_cnpj' ,
					$cliente_email,
					$login_empresa
				)";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$sql = "SELECT currval ('tbl_pessoa_pessoa_seq')";
			$res = pg_exec ($con,$sql);
			$cliente_cliente = pg_result ($res,0,0);
			$sql = "INSERT INTO tbl_pessoa_cliente (
					pessoa            ,
					empresa           ,
					ativo
				) VALUES (
					$cliente_cliente  ,
					$login_empresa ,
					't'
				)";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###
### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###
### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###
		if(strlen($cliente_cliente)==0 AND strlen ($msg_erro)==0 AND $tipo_orcamento == 'VENDA'){
			$cliente_cliente = "3";
		}
### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###
### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###
### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###### ARRUMAR ###

		if (strlen($cliente_cliente)==0){
			$msg_erro .= "Informações não são suficientes para o cadastro deste cliente.";
		}

		if (strlen ($msg_erro)==0){
			$sql = "UPDATE tbl_orcamento
					SET cliente = $cliente_cliente
				WHERE orcamento = $orcamento";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
		//--===== FIM - Cliente do Orçamento ==================================================

		if (strlen ($msg_erro) == 0){
			$res = pg_exec ($con,"COMMIT TRANSACTION");
		}
		else {
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}


		if (strlen ($msg_erro) > 0) {
			if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
			$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";
		
			if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "1|$msg_erro";
		}else{
			$sua_os = str_replace ("'","",$orcamento);
			echo "ok|$orcamento|<font color='#009900' size='1'><b>Orçamento $orcamento gravado com sucesso</b></font>
			<br>Aguarde enquanto é redirecionado</a>";
		}

	}
	exit;
}

//--==== Gravar OS ===============================================================
$resposta = "";
if($acao == "parcelamento"){
	$condicao		= strtoupper(trim($_POST['condicao']));
	$data_abertura	= strtoupper(trim($_POST['data_abertura']));
	$valor_total	= trim($_POST['valor_total']);

	if (strlen($condicao)==0) $msg_erro = "Selecione a forma de pagamento";

	if (strlen($msg_erro)==0){
		
		$sql = "SELECT condicao,codigo_condicao,descricao,parcelas
				FROM tbl_condicao
				WHERE fabrica=$login_empresa
				AND visivel IS TRUE
				AND condicao = $condicao";
		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res) > 0) {
			$condicao      = trim(pg_result($res,$k,condicao));
			$codigo        = trim(pg_result($res,$k,codigo_condicao));
			$parcelas      = trim(pg_result($res,$k,parcelas));

			$parcelas = explode("|",$parcelas);

			$data_abertura = converte_data($data_abertura);

			if (!$data_abertura){
				echo "1|Data de abertura inválida!!";
				exit();
			}
			$valor_parcela=number_format($valor_total/count($parcelas),2,".","");

			$parc = array();
			for ($i=0;$i<count($parcelas);$i++){
				$data_tmp = date("d/m/Y",strtotime($data_abertura)+$parcelas[$i]*60*60*24);
				array_push($parc,$data_tmp);
			}
			for ($i=1;$i<=count($parc);$i++){
				$resposta .= $i."ª Parc. ".$parc[$i-1]." - R$ $valor_parcela<br>";
			}
		}
	}
}

echo "ok|$resposta";






?>
