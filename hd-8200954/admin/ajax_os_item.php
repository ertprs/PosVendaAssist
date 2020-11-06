<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include '../ajax_cabecalho.php';
$admin_privilegios = "call_center,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';


$acao = $_GET["acao"];

if($acao == "pega_produto"){

	$os                 = $_GET["os"];
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

		if($linha == 382) $linha = 317;
		if($linha == 401) $linha = 307;
		if($linha == 390) $linha = 317;

		$resposta = "<u><i>$produto_referencia - $descricao</b></u>";
		$lista    = "<a class='lnk' href='peca_consulta_por_produto.php?produto=$produto' target='_blank'><font color='#FFFFFF'><u>Lista Básica</u></font></a>";

		echo  "ok|$produto|$linha|$familia|$resposta|$lista";

	}else{
		echo "1|Produto ainda não lançado ou não encontrado";
	}
	exit;
}


if($acao == "gravar"){

	$msg_erro = "";


	if (strlen($msg_erro) == 0){

		$posto_codigo           = trim($_GET['posto_codigo'])                     ;
		$data_abertura          = trim($_GET['data_abertura'])                    ;
		$data_fechamento        = trim($_GET['data_fechamento'])                  ;
		$nota_fiscal            = trim($_GET['nota_fiscal'])                      ;
		$data_nf                = trim($_GET['data_nf'])                          ;
		$admin_paga_mao_de_obra = trim($_GET['admin_paga_mao_de_obra'])           ;
		$produto_referencia     = strtoupper(trim($_GET['produto_referencia']))   ;
		$consumidor_nome        = str_replace ("'","",$_GET['consumidor_nome'])   ;
		$consumidor_fone        = strtoupper(trim($_GET['consumidor_fone']))      ;
		$consumidor_estado      = strtoupper(trim($_GET['consumidor_estado']))    ;
		$consumidor_endereco    = strtoupper(trim($_GET['consumidor_endereco']))  ;
		$consumidor_numero      = strtoupper(trim($_GET['consumidor_numero']))     ;
		$consumidor_complemento = strtoupper(trim($_GET['consumidor_complemento']));
		$consumidor_estado      = strtoupper(trim($_GET['consumidor_estado']))     ;
		$consumidor_cidade      = strtoupper(trim($_GET['consumidor_cidade']))     ;
		$consumidor_revenda     = strtoupper(trim($_GET['consumidor_revenda']))    ;
		$consumidor_cep         = strtoupper(trim($_GET['consumidor_cep']))        ;
		$revenda_nome           = strtoupper(trim($_GET['revenda_nome']))          ;

		if($login_fabrica != 15){
			if (strlen (trim ($sua_os)) == 0) $msg_erro .= " Digite o número da OSs Fabricante.<br>";
			else                              $sua_os    = "'" . $sua_os . "'" ;
		}
		// explode a sua_os
		$fOsRevenda = 0;
		$expSua_os = explode("-",$sua_os);
		$sql = "SELECT sua_os
			FROM   tbl_os_revenda
			WHERE  sua_os  = $expSua_os[0]
			AND    fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		if (@pg_numrows ($res) != 0) $fOsRevenda = 1;


			if (strlen($produto_referencia) == 0 ) $msg_erro .= " Digite o produto.<br>"                ;
		if($login_admin <> 510) { // HD 39916
			if (strlen($produto_serie)      == 0 AND $login_fabrica <> 15 ) $msg_erro .= " Digite o número de série. <br>"       ;
			if (strlen($consumidor_nome)    == 0 ) $msg_erro .= " Digite o nome do consumidor. <br>"    ;
	//		if (strlen($cosnumidor_cep)     == 0 ) $msg_erro .= " Digite o CEP do consumidor.<br>"      ;
			if (strlen($consumidor_estado)  == 0 ) $msg_erro .= " Digite o estado do consumidor. <br>"  ;
			if (strlen($consumidor_cidade)  == 0 ) $msg_erro .= " Digite a cidade do consumidor. <br>"  ;
			if (strlen($revenda_nome)       == 0 ) $msg_erro .= " Digite a Revenda. <br>"               ;
		}else { // HD 39916
			if (strlen($produto_serie)      == 0)  $produto_serie     = 'null';
			if (strlen($consumidor_nome)    == 0 ) $consumidor_nome   = 'null';
			if (strlen($consumidor_estado)  == 0 ) $consumidor_estado = "";
			if (strlen($consumidor_cidade)  == 0 ) $consumidor_cidade = 'null';
			if (strlen($revenda_nome)       == 0 ) $revenda_nome      = 'null';
		}

		$xconsumidor_cpf     = 'null';
		$cep                 = 'null';
		$xrevenda_cnpj       = 'null';
		$tipo_atendimento    = 'null';
		$xtroca_faturada     = 'null';
		$xquem_abriu_chamado = 'null';
		$xobs                = 'null';
		$xtipo_os_compressor = 'null';
		$qtde_produtos       = '1'   ;
		$consumidor_revenda  = 'C'   ;
		$os_reincidente      = "'f'" ;


		if (strlen($admin_paga_mao_de_obra) == 0) {
			$admin_paga_mao_de_obra = 'f';
		}else{
			$admin_paga_mao_de_obra = 't';
		}


		//--===== Valida Posto =============================================================
		$posto_codigo = str_replace ("-","",$posto_codigo);
		$posto_codigo = str_replace (".","",$posto_codigo);
		$posto_codigo = str_replace ("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);

		$sql = "SELECT tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		$res   = pg_exec ($con,$sql);
		$posto = @pg_result ($res,0,0);
		//--===== FIM - Valida Posto =======================================================

		//--===== Valida Produto ===========================================================
		$produto_referencia = str_replace ("-","",$produto_referencia);
		$produto_referencia = str_replace (" ","",$produto_referencia);
		$produto_referencia = str_replace ("/","",$produto_referencia);
		$produto_referencia = str_replace (".","",$produto_referencia);

		$produto = 0;
		$sql = "SELECT tbl_produto.produto
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$produto_referencia')
				AND    tbl_linha.fabrica      = $login_fabrica";
//				AND    tbl_produto.ativo IS TRUE";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) $msg_erro = "Produto $produto_referencia não cadastrado";
		$produto = @pg_result ($res,0,0);
		//--===== FIM - Valida Produto =====================================================

		//--===== Validações de datas ========================================================
		if(strlen($msg_erro)==0){
			$data_abertura   = fnc_formata_data_pg($data_abertura);
			$data_fechamento = fnc_formata_data_pg($data_fechamento);
			$data_nf         = fnc_formata_data_pg($data_nf)      ;


			if ($data_abertura == 'null')                 $msg_erro .= " Digite a data de abertura da OS.<br>";
			if($login_admin <> 510) {
				// if ($data_fechamento == 'null')         $msg_erro .= " Digite a data de fechamento da OS.<br>";
				if ($data_nf == 'null')                 $msg_erro .= " Digite a data de compra.<br>"        ;
			}

			if((strlen($data_abertura) > 0 AND strlen($data_fechamento) > 0 AND trim($data_fechamento) <> "null") ) {
				$sql = "SELECT ((DATE $data_fechamento - DATE $data_abertura) > 90) AS intervalo";
				$resx = @pg_exec($con, $sql);
				//$msg_erro = "$sql".pg_errormessage($con);
				if(@pg_numrows($resx)>0){
					$intervalo = @pg_result($resx,0,intervalo);

					if($intervalo == "t"){
						$msg_erro .= " OS aberta a mais de 90 dias.<br>";
					}
				}
			}

		}

		//--===== FIM - Validação de datas ====================================================

		//--===== Validações específicas para OS Revenda/Consumidor ==========================
		if ($fOsRevenda == 1){ //Revenda
			if (strlen ($nota_fiscal) == 0)       $nota_fiscal = "null";
			else                                  $nota_fiscal = "'" . $nota_fiscal . "'" ;

			if (strlen ($aparencia_produto) == 0) $aparencia_produto  = "null";
			else                                  $aparencia_produto  = "'" . $aparencia_produto . "'" ;

			if (strlen ($acessorios) == 0)        $acessorios = "null";
			else                                  $acessorios = "'" . $acessorios . "'" ;

			if (strlen($consumidor_revenda) == 0) $msg_erro .= " Selecione consumidor ou revenda.<br>";
			else                                  $xconsumidor_revenda = "'".$consumidor_revenda."'";

			if (strlen ($orientacao_sac) == 0)    $orientacao_sac  = "null";
			else                                  $orientacao_sac  = "'" . $orientacao_sac . "'" ;
		}else{ //Consumidor
			if($login_admin <> 510) {
				if (strlen ($nota_fiscal) == 0)  $msg_erro .= "Entre com o número da Nota Fiscal<br>";
				else                             $nota_fiscal = "'" . $nota_fiscal . "'" ;
			}else                                   $nota_fiscal = "'" . $nota_fiscal . "'" ;

			if (strlen ($aparencia_produto) == 0)  $aparencia_produto  = "null";
			else                                   $aparencia_produto  = "'" . $aparencia_produto . "'" ;

			if (strlen ($acessorios) == 0)         $acessorios = "null";
			else                                   $acessorios = "'" . $acessorios . "'" ;

			if (strlen($consumidor_revenda) == 0)  $msg_erro .= " Selecione consumidor ou revenda.<br>";
			else                                   $xconsumidor_revenda = "'".$consumidor_revenda."'";

			if (strlen ($orientacao_sac) == 0)     $orientacao_sac  = "null";
			else                                   $orientacao_sac  = "'" . $orientacao_sac . "'" ;

			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
			$res = @pg_exec ($con,$sql);

			if (@pg_numrows ($res) == 0) $msg_erro = "Produto $produto_referencia sem garantia";

			$garantia = trim(@pg_result($res,0,garantia));

			$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval))::date;";
			$res = @pg_exec ($con,$sql);

			if (@pg_numrows ($res) > 0) $data_final_garantia = trim(pg_result($res,0,0));

			if ($data_final_garantia < $cdata_abertura) $msg_erro = "[ $data_nf ] - [ $data_final_garantia ] = [ $cdata_abertura ] Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
		}
		//--===== FIM - Validações específicas para OS Revenda/Consumidor ====================
	}

//CARTÃO CLUBE - LATINATEC
	$cartao_clube = trim($_POST['cartao_clube']);
	$cc = 0;
	if($login_fabrica == 15 AND strlen($cartao_clube) > 0 AND strlen($msg_erro) == 0){
		$sql_5 = "SELECT cartao_clube      ,
						dt_nota_fiscal   ,
						dt_garantia
					FROM tbl_cartao_clube
					WHERE cartao_clube = '$cartao_clube'
					AND produto = '$produto' ; ";
		$res_5 = pg_exec($con,$sql_5);
		if(pg_numrows($res_5) > 0){
			$cc = "OK";
		}else{
			$msg_erro = "Verifique o produto do Cartão Clube com o da OS.";
		}
	}


	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen ($msg_erro) == 0) {
		if (strlen ($os) == 0) {
		/*================ INSERE NOVA OS =========================*/
			$sql = "INSERT INTO tbl_os (
						tipo_atendimento      ,
						posto                 ,
						admin                 ,
						fabrica               ,";
			if($login_fabrica != 15){
				$sql .= "
						sua_os                ,";
			}

			$sql .= "
						data_abertura         ,
						cliente               ,
						revenda               ,
						consumidor_nome       ,
						consumidor_cpf        ,
						consumidor_cep        ,
						consumidor_endereco   ,
						consumidor_numero     ,
						consumidor_complemento,
						consumidor_bairro     ,
						consumidor_cidade     ,
						consumidor_estado     ,
						consumidor_fone       ,
						revenda_cnpj          ,
						revenda_nome          ,
						nota_fiscal           ,
						data_nf               ,
						produto               ,
						serie                 ,
						qtde_produtos         ,
						aparencia_produto     ,
						acessorios            ,
						obs                   ,
						quem_abriu_chamado    ,
						consumidor_revenda    ,
						troca_faturada       ,
						os_reincidente ";

			if ($login_fabrica == 1) {
				$sql .=	",codigo_fabricacao ,
						satisfacao          ,
						tipo_os             ,
						laudo_tecnico       ";
			}

			$sql .= ") VALUES (
						$tipo_atendimento                                               ,
						$posto                                                          ,
						$login_admin                                                    ,
						$login_fabrica                                                  ,";

			if($login_fabrica != 15){
				$sql .="	trim ($sua_os)                                                  ,";
			}
			$sql .="	$data_abertura                                                  ,
						(SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf) ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)   ,
						trim ('$consumidor_nome')                                       ,
						trim ('$consumidor_cpf')                                        ,
						trim ('$consumidor_cep')                                        ,
						trim ('$consumidor_endereco')                                   ,
						trim ('$consumidor_numero')                                     ,
						trim ('$consumidor_complemento')                                ,
						trim ('$consumidor_bairro')                                     ,
						trim ('$consumidor_cidade')                                     ,
						trim ('$consumidor_estado')                                     ,
						trim ('$consumidor_fone')                                       ,
						trim ('$revenda_cnpj')                                          ,
						trim ('$revenda_nome')                                          ,
						trim ($nota_fiscal)                                             ,
						$data_nf                                                        ,
						$produto                                                        ,
						'$produto_serie'                                                ,
						$qtde_produtos                                                  ,
						trim ($aparencia_produto)                                       ,
						trim ($acessorios)                                              ,
						$xobs                                                           ,
						$xquem_abriu_chamado                                            ,
						'$consumidor_revenda'                                           ,
						$xtroca_faturada                                                ,
						$os_reincidente ";

			if ($login_fabrica == 1) {
				$sql .= ", $codigo_fabricacao ,
						'$satisfacao'         ,
						$xtipo_os_compressor  ,
						$laudo_tecnico        ";
			}

			$sql .= ");";
			$insere = 'ok';


		}else{
			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_os SET
						tipo_atendimento   = $tipo_atendimento           ,
						posto              = $posto                      ,";
			if($login_fabrica<>6 and $login_fabrica<>11)$sql .="        admin              = $login_admin                ,";
				$sql .="        fabrica            = $login_fabrica              ,

				";
				if($login_fabrica != 15){
				$sql .="
						sua_os             = trim($sua_os)               ,";
				}

				$sql .="
						data_abertura      = $data_abertura              ,
						consumidor_nome    = trim('$consumidor_nome')    ,
						consumidor_cpf     = trim('$consumidor_cpf')     ,
						consumidor_fone    = trim('$consumidor_fone')    ,
						consumidor_cep     = trim('$consumidor_cep')     ,
						consumidor_endereco= trim('$consumidor_endereco'),
						consumidor_numero  = trim('$consumidor_numero')    ,
						consumidor_complemento = trim('$consumidor_complemento')    ,
						consumidor_bairro  = trim('$consumidor_bairro')    ,
						consumidor_estado  = trim('$consumidor_estado')  ,
						consumidor_cidade  = trim ('$consumidor_cidade') ,
						revenda_cnpj       = trim('$revenda_cnpj')       ,
						revenda_nome       = trim('$revenda_nome')       ,
						nota_fiscal        = trim($nota_fiscal)          ,
						data_nf            = $data_nf                    ,
						produto            = $produto                    ,
						serie              = '$produto_serie'            ,
						qtde_produtos      = $qtde_produtos              ,
						aparencia_produto  = trim($aparencia_produto)    ,
						acessorios         = trim($acessorios)           ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                       ,
						consumidor_revenda = '$consumidor_revenda'       ,
						troca_faturada     = $xtroca_faturada            ,
						os_reincidente     = $os_reincidente ";

			if ($login_fabrica == 1) {
				$sql .=	", codigo_fabricacao = $codigo_fabricacao ,
						satisfacao           = '$satisfacao'      ,
						tipo_os              = $xtipo_os_compressor,
						laudo_tecnico        = $laudo_tecnico     ";
			}

			$sql .= "WHERE os      = $os
					AND   fabrica = $login_fabrica";
		}

		// echo nl2br($sql); exit;


		$res = @pg_exec ($con,$sql);
		$aux_msg_erro = pg_errormessage($con);
		$msg_erro    .= substr($aux_msg_erro,6);

		if(strlen($_GET['solucao_os']) == 0) {
			if($login_fabrica != 15){
				$msg_erro .= "<br>Por favor, selecione a solução.";
			}else{
				 if(!empty($_GET['peca_0'])){
				 	$msg_erro .= "<br>Por favor, selecione a solução.";
				 }else{
				 	unset($msg_erro);
				 }
			}
		}

		if(strlen($_GET['defeito_constatado']) == 0) {
			if($login_fabrica != 15){
				$msg_erro .= "<br>Por favor, selecione o defeito constatado.";
			}else{
				 if(!empty($_GET['peca_0'])){
				 	$msg_erro .= "<br>Por favor, selecione o defeito constatado.";
				 }else{
				 	unset($msg_erro);
				 }
			}

		}



		if(strlen($_GET['defeito_reclamado_descricao']) == 0) {
			if($login_fabrica != 15){
				$msg_erro .= "<br>Por favor, digite o defeito reclamado.";
			}else{
				 if(!empty($_GET['peca_0'])){
				 	$msg_erro .= "<br>Por favor, digite o defeito reclamado.";
				 }else{
				 	unset($msg_erro);
				 }
			}

		}


		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {

				$res = pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);
				$msgOS = $os;
				if($login_fabrica == 15 && !empty($os)){

					$sqlUpdate = "UPDATE tbl_os SET sua_os = '".$os."' WHERE os = '".$os."';";
				}

				$sql = "UPDATE tbl_os SET consumidor_nome = tbl_cliente.nome FROM tbl_cliente WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.cliente = tbl_cliente.cliente";
				$res = @pg_exec ($con,$sql);

				$sql = "UPDATE tbl_os SET consumidor_cidade = tbl_cidade.nome , consumidor_estado = tbl_cidade.estado FROM tbl_cidade, tbl_cliente WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.consumidor_cidade IS NULL AND tbl_os.cliente = tbl_cliente.cliente AND tbl_cliente.cidade = tbl_cidade.cidade";
				$res = pg_exec ($con,$sql);

				if (strlen ($consumidor_endereco)    == 0) $consumidor_endereco    = "null" ; else $consumidor_endereco    = "'" . $consumidor_endereco    . "'" ;
				if (strlen ($consumidor_numero)      == 0) $consumidor_numero      = "null" ; else $consumidor_numero      = "'" . $consumidor_numero      . "'" ;
				if (strlen ($consumidor_complemento) == 0) $consumidor_complemento = "null" ; else $consumidor_complemento = "'" . $consumidor_complemento . "'" ;
				if (strlen ($consumidor_bairro)      == 0) $consumidor_bairro      = "null" ; else $consumidor_bairro      = "'" . $consumidor_bairro      . "'" ;
				if (strlen ($consumidor_cep)         == 0) $consumidor_cep         = "null" ; else $consumidor_cep         = "'" . $consumidor_cep         . "'" ;
				if (strlen ($consumidor_cidade)      == 0) $consumidor_cidade      = "null" ; else $consumidor_cidade      = "'" . $consumidor_cidade      . "'" ;
				if (strlen ($consumidor_estado)      == 0) $consumidor_estado      = "null" ; else $consumidor_estado      = "'" . $consumidor_estado      . "'" ;

				$sql = "UPDATE tbl_os SET
							consumidor_endereco    = $consumidor_endereco       ,
							consumidor_numero      = $consumidor_numero         ,
							consumidor_complemento = $consumidor_complemento    ,
							consumidor_bairro      = $consumidor_bairro         ,
							consumidor_cep         = $consumidor_cep            ,
							consumidor_cidade      = $consumidor_cidade         ,
							consumidor_estado      = $consumidor_estado
						WHERE tbl_os.os = $os ";
				$res = pg_exec ($con,$sql);
			}


			//--===== Análise da OS ===============================================================
			$data_fechamento    = $_GET['data_fechamento']    ;
			$defeito_constatado = $_GET['defeito_constatado'] ;
			$defeito_reclamado  = $_GET['defeito_reclamado']  ;
			$causa_defeito      = $_GET['causa_defeito']      ;
			$x_solucao_os       = $_GET['solucao_os']         ;

			$defeito_reclamado_descricao      = trim($_GET['defeito_reclamado_descricao'])        ;



			if (strlen($data_fechamento) > 0){
				$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
				if($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro = "Data de fechamento maior que a data de hoje.";
			}


			if (strlen ($defeito_constatado) > 0) {
				$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
						WHERE  tbl_os.os    = $os
						AND    tbl_os.posto = $posto;";
				$res = @pg_exec ($con,$sql);
			}

			if (strlen ($defeito_reclamado) > 0) {
				$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado
						WHERE  tbl_os.os    = $os
						AND    tbl_os.posto = $posto;";
				$res = pg_exec ($con,$sql);
			}
			if($login_fabrica == 15 ){
				if (strlen ($defeito_reclamado_descricao) > 0) {
					$sql = "UPDATE tbl_os SET defeito_reclamado_descricao = '$defeito_reclamado_descricao'
							WHERE  tbl_os.os    = $os
							AND    tbl_os.posto = $posto;";
					$res = pg_exec ($con,$sql);
				}
			}

			if (strlen($causa_defeito) == 0) $causa_defeito = "null";
			else                             $causa_defeito = $causa_defeito;

			$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito
					WHERE  tbl_os.os    = $os
					AND    tbl_os.posto = $posto;";
			$res = @pg_exec ($con,$sql);

			if (strlen($x_solucao_os) > 0) {
				$sql = "UPDATE tbl_os SET solucao_os = '$x_solucao_os'
						WHERE  tbl_os.os    = $os
						AND    tbl_os.posto = $posto;";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			//--===== FIM - Análise da OS =========================================================


			//--===== Peças da OS =================================================================
			$obs  = trim($_GET['obs']);
			if (strlen($obs) > 0) $obs = "'".$obs."'";
			else                   $obs = "null";

			$sql = "DELETE FROM tbl_os_produto
					USING  tbl_os, tbl_os_item
					WHERE  tbl_os_produto.os         = tbl_os.os
					AND    tbl_os_produto.os_produto = tbl_os_item.os_produto
					AND    tbl_os_item.pedido                     IS NULL
					AND    tbl_os_item.liberacao_pedido_analisado IS FALSE
					AND    tbl_os_produto.os = $os
					AND    tbl_os.fabrica    = $login_fabrica
					AND    tbl_os.posto      = $posto;";
			$res = @pg_exec ($con,$sql);

			##### É TROCA FATURADA #####
			if (strlen($troca_faturada) > 0) {
				$x_motivo_troca = trim($_GET['motivo_troca']);
				if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

				$resX = pg_exec ($con,"BEGIN TRANSACTION");

				$sql =	"UPDATE tbl_os SET
								motivo_troca  = $x_motivo_troca
					WHERE  tbl_os.os      = $os
					and    tbl_os.fabrica = $login_fabrica;";
				$res = @pg_exec ($con,$sql);

			##### NÃO É TROCA FATURADA #####
			}else{

				$qtde_item = $_GET['qtde_item'];

				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$xpeca           = trim($_GET["peca_"           . $i]);
					$xposicao        = trim($_GET["posicao_"        . $i]);
					$xqtde           = trim($_GET["qtde_"           . $i]);
					$xdefeito        = trim($_GET["defeito_"        . $i]);
					$xpcausa_defeito = trim($_GET["pcausa_defeito_" . $i]);
					$xservico        = trim($_GET["servico_"        . $i]);

					$admin_peca      = $_GET["admin_peca_"     . $i]; //aqui
					if(strlen($admin_peca)==0) $admin_peca ="$login_admin"; //aqui
					if($admin_peca=="P")$admin_peca ="null"; //aqui

					if (strlen($xposicao) > 0) $xposicao = "'" . $xposicao . "'";
					else                       $xposicao = "null";

					if (strlen ($xqtde) == 0) $xqtde = "1";

					$xpeca    = str_replace ("." , "" , $xpeca);
					$xpeca    = str_replace ("-" , "" , $xpeca);
					$xpeca    = str_replace ("/" , "" , $xpeca);
					$xpeca    = str_replace (" " , "" , $xpeca);

					if (strlen($xpeca) > 0) {
						$xpeca    = strtoupper ($xpeca);

						$sql = "SELECT tbl_produto.produto
								FROM   tbl_produto
								JOIN   tbl_linha USING (linha)
								JOIN   tbl_os    USING (produto)
								WHERE  tbl_os.os = $os
								AND    tbl_linha.fabrica = $login_fabrica;";
						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) == 0) {
							$msg_erro .= "Produto $produto não cadastrado";
							$linha_erro = $i;
						}else{
							$produto = pg_result ($res,0,produto);
						}

						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_produto (
										os     ,
										produto,
										serie
									)VALUES(
										$os     ,
										$produto,
										'$serie'
									);";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							if (strlen ($msg_erro) > 0) {
								break ;
							}else{
								$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
								$os_produto  = pg_result ($res,0,0);
								$xpeca = strtoupper ($xpeca);

								if (strlen($xpeca) > 0) {
									$sql = "SELECT tbl_peca.*
										FROM   tbl_peca
										WHERE  upper(tbl_peca.referencia_pesquisa) = upper('$xpeca')
										AND    tbl_peca.fabrica             = $login_fabrica;";
									$res = pg_exec ($con,$sql);

									if (pg_numrows ($res) == 0) {
										$msg_erro = "Peça $xpeca não cadastrada";
										$linha_erro = $i;
									}else{
										$xpeca = pg_result ($res,0,peca);
									}

									if (strlen($xdefeito) == 0) $msg_erro = "Favor informar o defeito da peça";
									if (strlen($xservico) == 0) $msg_erro = "Favor informar o serviço realizado";

									if(strlen($xpcausa_defeito) == 0) $xpcausa_defeito = "null";

									if (strlen ($msg_erro) == 0) {
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
													$os_produto      ,
													$xpeca           ,
													$xposicao        ,
													$xqtde           ,
													$xdefeito        ,
													$xpcausa_defeito ,
													$xservico        ,
													$admin_peca
												)";
										$res = @pg_exec ($con,$sql);
										$msg_erro = pg_errormessage($con);

										if (strlen ($msg_erro) > 0) {
											break ;
										}
									}
								}
							}
						}
					}
				}
			}

			//CARTAO CLUBE - LATINATEC
			if($login_fabrica == 15 AND $cc == "OK"){
				$sql_cc = "UPDATE tbl_cartao_clube SET os = $os WHERE cartao_clube = '$cartao_clube' ";
				$res = pg_exec($con,$sql_cc);
			}

			//Para a latinatec sempre que a solução for para trocar peça (troca_peca is true)
			// deve ser especificado a peças a ser trocada.HD3549
			if($login_fabrica == 15){

				if(!empty($x_solucao_os)){
					$sql_t = "SELECT troca_peca FROM tbl_solucao where solucao = $x_solucao_os AND fabrica = $login_fabrica AND troca_peca IS TRUE; ";
				}else{
					$sql_t = "SELECT troca_peca FROM tbl_solucao where fabrica = $login_fabrica AND troca_peca IS TRUE; ";
				}
				$debugSqlError = $sql_t;

				$res_t = pg_exec($con,$sql_t);
				
				if(pg_numrows($res_t) > 0){

					$sql_t = "SELECT COUNT(*)
									FROM tbl_os_item
									JOIN tbl_os_produto USING(os_produto)
									JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
								WHERE os = $os
								AND tbl_servico_realizado.troca_de_peca IS TRUE ;";
					$res_t = pg_exec($con,$sql_t);

					if(pg_result($res_t,0,0) == 0){
						if($login_fabrica != 15 && !empty($_GET['peca_0'])){
							$msg_erro = "Para a solução escolhida, é necessário especificar a peça a ser trocada.";
						}
					}
				}
			}


			if(strlen($msg_erro) == 0){
				$sql      = "SELECT fn_valida_os($os, $login_fabrica)";
				$res      = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
				if (strpos($msg_erro,"ERROR: ") !== false) {  
					$erro = "Foi detectado o seguinte erro:<br>";
					$msg_erro = substr($msg_erro, 6);
				}
				
				// retira CONTEXT:
				if (strpos($msg_erro,"CONTEXT:")) {
					$x = explode('CONTEXT:',$msg_erro);
					$msg_erro.= $x[0];
				}
				
			}

			if (strlen ($msg_erro) == 0) {
				$res      = @pg_exec ($con,"SELECT fn_valida_os_item($os, $login_fabrica)");
				$msg_erro = pg_errormessage($con);
				if (strlen($data_fechamento) > 0){
					if (strlen ($msg_erro) == 0) {
							$sql = "UPDATE tbl_os SET data_fechamento   = $xdata_fechamento
									WHERE  tbl_os.os    = $os
									AND    tbl_os.posto = $posto;";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
					}
				}
			}
			//--===== FIM - Peças da OS ===========================================================

//

			
			if (strlen ($msg_erro) == 0) {
			
				$sql = "UPDATE  tbl_os_extra SET
								admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra'
							WHERE tbl_os_extra.os = $os;";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				
			}

		}
	}

	if (strlen ($msg_erro) > 0) {
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro";
	}else{
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$sua_os = str_replace ("'","",$sua_os);
		echo "ok|<font color='#009900'><b>OS $msgOS gravada com sucesso</b></font><br><a href='os_cadastro.php'>Abrir Nova OS</a>&nbsp;&nbsp;&nbsp;<a href='os_press.php?os=$os' target='_blank'>Consultar OS</a>";
	}
	exit;

}













if($acao=='integridade'){

	$linha   = $_GET["linha"];
	$familia = $_GET["familia"];
	$defeito_reclamado = $_GET["defeito_reclamado"];

		$sqldefeito_reclamado = "SELECT
						defeito_reclamado,
						descricao
					FROM tbl_defeito_reclamado
					WHERE defeito_reclamado IN (
						SELECT DISTINCT(defeito_reclamado)
						FROM tbl_diagnostico
						WHERE fabrica = $login_fabrica
						AND   linha   = $linha
						AND   familia = $familia
						and   defeito_reclamado = $defeito_reclamado
						AND ativo='t'
					)
					ORDER BY descricao";
			$resdefeito_reclamado = pg_exec ($con,$sqldefeito_reclamado);
			for ($w = 0 ; $w < pg_numrows($resdefeito_reclamado) ; $w++){
				$defeito_reclamado  = trim(pg_result($resdefeito_reclamado,$w,defeito_reclamado));
				$descricao_defeito_reclamado = trim(pg_result($resdefeito_reclamado,$w,descricao));
}
$resposta .= "<table width='400' border='0' cellspacing='1' bgcolor='#485989' cellpadding='3' align='center' style='font-family: verdana; font-size: 10px'>";
$resposta .= "<TR>";
$resposta .= "<TD align='center' colspan='5'><font color='#FFFFFF'><b>Diagnósticos Cadastrados - $descricao_defeito_reclamado</b></font></td>";
$resposta .= "</TR>";
$resposta .= "<TR  bgcolor='#f4f7fb'>";
$resposta .= "<TD align='center' width='150'>Defeito Constatado</td>";
$resposta .= "<TD align='center' width='200'>Solução</td>";

$resposta .= "</TR>";

#DEFEITO_CONSTATADO
				$sqldefeito_constatado ="SELECT defeito_constatado,
								descricao
							FROM tbl_defeito_constatado
							WHERE defeito_constatado IN (
								SELECT DISTINCT(defeito_constatado)
								FROM tbl_diagnostico
								WHERE fabrica         = $login_fabrica
								AND linha             = $linha
								AND familia           = $familia
								AND defeito_reclamado = $defeito_reclamado
								AND ativo='t'
							)
							ORDER BY descricao";
				$resdefeito_constatado = pg_exec ($con,$sqldefeito_constatado);

				for ($z = 0 ; $z < pg_numrows($resdefeito_constatado) ; $z++){
					$defeito_constatado           = trim(pg_result($resdefeito_constatado,$z,defeito_constatado));
					$descricao_defeito_constatado = trim(pg_result($resdefeito_constatado,$z,descricao));
					$resposta .= "<tr>";

					$resposta .= "<td align='left' bgcolor='#819CB4' ><font color='#ffffff'><B>$descricao_defeito_constatado</B></td>";
					//echo "<td bgcolor='#819CB4'> &nbsp;</td>";
					$resposta .= "</tr>";
#SOLUCAO
					$sqlsolucao ="SELECT solucao,
								descricao
							FROM tbl_solucao
							WHERE solucao IN (
								SELECT DISTINCT(solucao)
								FROM tbl_diagnostico
								WHERE fabrica=$login_fabrica
								AND linha=$linha
								AND familia=$familia
								AND defeito_reclamado=$defeito_reclamado
								AND defeito_constatado=$defeito_constatado
								AND ativo='t'
							)
							ORDER BY descricao";
					$ressolucao = pg_exec ($con,$sqlsolucao);
					for ($k = 0 ; $k < pg_numrows($ressolucao) ; $k++){
						$solucao          = trim(pg_result($ressolucao,$k,solucao));
						$descricao_solucao = trim(pg_result($ressolucao,$k,descricao));
						$sqldiagnostico="SELECT diagnostico from tbl_diagnostico where fabrica=$login_fabrica and linha=$linha and familia=$familia and defeito_reclamado=$defeito_reclamado and defeito_constatado=$defeito_constatado and solucao=$solucao";
						$resdiagnostico=@pg_exec($con,$sqldiagnostico);
						$diagnostico          = trim(pg_result($resdiagnostico,0,diagnostico));
						$resposta .= "<tr>";
						$resposta .= "<td bgcolor='#ced7e7'> &nbsp;</td>";
						$resposta .= "<td align='left' bgcolor='#D6DFF0'><font color='#000000'><B>$descricao_solucao</B></td>";
						$resposta .= "</tr>";
					}
#SOLUCAO
				}
#DEFEITO_CONSTATADO

#DEFEITO_RECLAMADO

$resposta .= "</TABLE>";


}

echo "ok|$resposta";






?>
