<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../ajax_cabecalho.php';
include 'autentica_usuario.php';
include '../funcoes.php';


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

		$orcamento              = strtoupper(trim($_GET['orcamento']))             ;
		$tipo_orcamento         = strtoupper(trim($_GET['tipo_orcamento']))        ;
		$vendedor               = strtoupper(trim($_GET['vendedor']))              ;
		$aprovado               = strtoupper(trim($_GET['aprovado']))              ;

		$consumidor_nome        = strtoupper(trim($_GET['consumidor_nome']))       ;
		$consumidor_fone        = strtoupper(trim($_GET['consumidor_cpf']))        ;
		$consumidor_fone        = strtoupper(trim($_GET['consumidor_fone']))       ;
		$consumidor_cep         = strtoupper(trim($_GET['consumidor_cep']))        ;
		$consumidor_fone        = strtoupper(trim($_GET['consumidor_fone']))       ;
		$consumidor_endereco    = strtoupper(trim($_GET['consumidor_endereco']))   ;
		$consumidor_numero      = strtoupper(trim($_GET['consumidor_numero']))     ;
		$consumidor_complemento = strtoupper(trim($_GET['consumidor_complemento']));
		$consumidor_bairro      = strtoupper(trim($_GET['consumidor_fone']))       ;
		$consumidor_estado      = strtoupper(trim($_GET['consumidor_estado']))     ;
		$consumidor_cidade      = strtoupper(trim($_GET['consumidor_cidade']))     ;

		$produto                = strtoupper(trim($_GET['produto']))               ;
		$produto_referencia     = strtoupper(trim($_GET['produto_referencia']))    ;
		$produto_descricao      = strtoupper(trim($_GET['produto_descricao']))     ;
		$produto_serie          = strtoupper(trim($_GET['produto_serie']))         ;
		$produto_aparencia      = strtoupper(trim($_GET['produto_aparencia']))     ;
		$produto_acessorios     = strtoupper(trim($_GET['produto_acessorios']))    ;
		$revenda_nome           = strtoupper(trim($_GET['revenda_nome']))          ;
		$nota_fiscal            = strtoupper(trim($_GET['nota_fiscal']))           ;
		$data_nf                = strtoupper(trim($_GET['data_nf']))               ;

		$defeito_constatado     = strtoupper(trim($_GET['defeito_constatado']))    ;
		$defeito_reclamado      = strtoupper(trim($_GET['defeito_reclamado'] ))    ;
		$solucao                = strtoupper(trim($_GET['solucao_os']        ))    ;

		//--===== Campos Obrigatórios no Orçamento =========================================
		
		if (strlen($tipo_orcamento)  == 0) $msg_erro .= " Escolha o tipo de orçamento: <u>Venda ou Reparação</u><br>";
		if (strlen($vendedor)        == 0) $msg_erro .= " Selecione o vendedor. <br>"                                ;
		if (strlen($consumidor_nome) == 0) $msg_erro .= " Digite o nome do consumidor. <br>"                         ;
		if (strlen($consumidor_fone) == 0) $msg_erro .= " Digite o telefone do consumidor. <br>"                     ;

		if (strlen($aprovado) > 0) $aprovado = "TRUE";
		else                       $aprovado = "FALSE";
		$consumidor_nome = "'" . $consumidor_nome . "'" ;
		$consumidor_fone = "'" . $consumidor_fone . "'" ;

		//--===== Validações de Reparação ==================================================
		if($tipo_orcamento == 'R'){
			if (strlen($consumidor_cpf)     == 0 AND strlen($aprovado)           > 0) $msg_erro .= " Digite o CPF do cliente.<br>";
			if (strlen($produto_referencia) == 0 AND strlen($produto_descricao) == 0) $msg_erro .= " Digite o produto.<br>"       ;

			//--===== Valida Cliente ===========================================================
			$sql = "SELECT cliente FROM tbl_cliente WHERE cpf = '$consumidor_cpf'";
			$res = pg_exec ($con,$sql);
			$cliente = @pg_result ($res,0,0);
			//--===== FIM - Valida Cliente =====================================================

			//--===== Valida Produto ===========================================================
			$produto_descricao = "'" . $produto_descricao . "'";
			if(strlen($produto_referencia) >0){

				
				$produto_referencia = str_replace ($nao_permitido,"",$produto_referencia);

				$sql = "SELECT tbl_produto.produto
						FROM   tbl_produto
						JOIN   tbl_linha USING (linha)
						WHERE  UPPER(tbl_produto.referencia_pesquisa) = '$produto_referencia'
						AND    tbl_linha.fabrica                      = $login_fabrica
						AND    tbl_produto.ativo IS TRUE";

				$res = pg_exec ($con,$sql);
				if (pg_numrows ($res) == 0) $msg_erro .= "Produto $produto_referencia não cadastrado";
				$produto = @pg_result ($res,0,0);
			}else $produto = "null";
			//--===== FIM - Valida Produto =====================================================


			//--===== Validações de datas ======================================================
			if(strlen($msg_erro) == 0){
				$data_nf = fnc_formata_data_pg($data_nf);
				if($data_nf == 'null') $msg_erro .= " Digite a data de compra.<br>";
			}
			//--===== FIM - Validação de datas =================================================

			if (strlen ($produto_serie)   == 0)   $produto_serie      = "null"                         ;
			else                                  $produto_serie      = "'" . $produto_serie . "'"     ;
			if (strlen ($fabricante_nome)   == 0) $fabricante_nome    = "null"                         ;
			else                                  $fabricante_nome    = "'" . $fabricante_nome . "'"   ;
			if (strlen ($revenda)           == 0) $revenda            = "null"                         ;
			else                                  $revenda            = "'" . $revenda . "'"           ;
			if (strlen ($produto_aparencia) == 0) $produto_aparencia  = "null"                         ;
			else                                  $produto_aparencia  = "'" . $produto_aparencia . "'" ;
			if (strlen ($produto_acessorios)== 0) $produto_acessorios = "null"                         ;
			else                                  $produto_acessorios = "'" . $produto_acessorios . "'";
			if (strlen ($nota_fiscal)       == 0) $nota_fiscal        = "null"                         ;
			else                                  $nota_fiscal        = "'" . $nota_fiscal . "'"       ;
			if (strlen ($defeito_reclamado) == 0) $defeito_reclamado  = "null"                         ;
			else                                  $defeito_reclamado  = "'" . $defeito_reclamado . "'" ;
			if (strlen ($defeito_constatado)== 0) $defeito_constatado = "null"                         ;
			else                                  $defeito_constatado = "'" . $defeito_constatado . "'";
			if (strlen ($solucao)           == 0) $solucao            = "null"                         ;
			else                                  $solucao            = "'" . $solucao . "'"           ;
			if (strlen ($tecnico)   == 0)         $tecnico            = "null"                         ;
			else                                  $tecnico            = "'" . $tecnico . "'"           ;
		}
		//--===== FIM - Validações de Reparação ============================================


		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($msg_erro) == 0) {
			if (strlen ($orcamento) == 0) {
			/*================ INSERE ORCAMENTO =======================*/
				$sql = "
					INSERT INTO tbl_orcamento (
						cliente             ,
						vendedor            ,
						empresa             ,
						loja                ,
						consumidor_nome     ,
						consumidor_fone     ,
						aprovado
					) VALUES (
						$cliente            ,
						$vendedor           ,
						$login_fabrica      ,
						$login_posto        ,
						$consumidor_nome    ,
						$consumidor_fone    ,
						$aprovado
					);
					";
				$insere = 'ok';
			}else{
				/*================ ALTERA ORCAMENTO ==================*/
				$sql = "
					UPDATE tbl_orcamento SET
						consumidor_nome     = $consumidor_nome  ,
						consumidor_fone     = $consumidor_fone  ,
						aprovado            = $aprovado
					WHERE orcamento = $orcamento
					AND   empresa   = $login_fabrica
					AND   loja      = $login_posto
				";
			}
			$res = @pg_exec ($con,$sql);
			$aux_msg_erro = pg_errormessage($con);
			$msg_erro    .= substr($aux_msg_erro,6);
			if (strlen ($msg_erro) == 0) {
				if (strlen($orcamento) == 0) {
					$res = pg_exec ($con,"SELECT CURRVAL ('tbl_orcamento_orcamento_seq')");
					$orcamento  = pg_result ($res,0,0);
				}

				//--===== Peças do Orçamento ==========================================================
				if($tipo_orcamento == 'R'){
					$sql = "SELECT * FROM tbl_orcamento_os WHERE orcamento = $orcamento";
					$res = @pg_exec ($con,$sql);

					if(pg_numrows($res)==0){
						$sql = "
								INSERT INTO tbl_orcamento_os(
									orcamento         ,
									tecnico           ,
									fabrica           ,
									fabricante_nome   ,
									defeito_reclamado ,
									defeito_constatado,
									solucao           ,
									produto           ,
									produto_descricao ,
									serie             ,
									aparencia         ,
									acessorios        ,
									revenda           ,
									data_nf           ,
									nf
								)VALUES(
									$orcamento         ,
									$tecnico           ,
									$login_fabrica     ,
									$fabricante_nome   ,
									$defeito_reclamado ,
									$defeito_constatado,
									$solucao           ,
									$produto           ,
									$produto_descricao ,
									$produto_serie     ,
									$produto_aparencia ,
									$produto_acessorios,
									$revenda           ,
									$data_nf           ,
									$nota_fiscal
								)";
					}else{
						$sql = "UPDATE tbl_orcamento_os SET
									tecnico            = $tecnico           ,
									defeito_reclamado  = $defeito_reclamado ,
									defeito_constatado = $defeito_constatado,
									solucao            = $solucao           ,
									serie              = $produto_serie     ,
									aparencia          = $produto_aparencia ,
									acessorios         = $produto_acessorios,
									revenda            = $revenda           ,
									data_nf            = $data_nf           ,
									nf                 = $nota_fiscal
								WHERE orcamento = $orcamento";
					}
					$res = @pg_exec ($con,$sql);
					$aux_msg_erro = pg_errormessage($con);
					$msg_erro    .= substr($aux_msg_erro,6);

				}
				
				//--===== FIM - Peças do Orçamento ====================================================

				//--===== Peças do Orçamento ==========================================================
				$qtde_item = $_GET['qtde_item'];

				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$xorcamento_item = trim($_GET["orcamento_item_" . $i]);
					$xpeca           = trim($_GET["peca_"           . $i]);
					$xqtde           = trim($_GET["qtde_"           . $i]);
					$xdefeito        = trim($_GET["defeito_"        . $i]);
					$xpcausa_defeito = trim($_GET["pcausa_defeito_" . $i]);
					$xservico        = trim($_GET["servico_"        . $i]);
					$xdescricao      = strtoupper(trim($_GET["descricao_"      . $i]));

					if (strlen ($xqtde) == 0) $xqtde = "1";
		
					$xpeca    = str_replace ($nao_permitido , "" , $xpeca);

					if (strlen($xpeca) > 0 AND strlen ($msg_erro) == 0) {

						$sql = "SELECT tbl_peca.*
								FROM   tbl_peca
								WHERE  upper(tbl_peca.referencia_pesquisa) = '$xpeca'
								AND    tbl_peca.fabrica                    = $login_fabrica;";
						$res = pg_exec ($con,$sql);

						if (pg_numrows ($res) == 0) {
							$msg_erro = "Peça $xpeca não cadastrada";
							$linha_erro = $i;
						}else{
							$xpeca = pg_result ($res,0,peca);
						}
					}else{
						$xpeca = "null";
					}
					if(strlen($xdescricao)>1){
						$xdescricao = "'".$xdescricao."'";
						$R = $i+1;
						if (strlen($xdefeito) == 0) $msg_erro .= "Favor informar o defeito da peça na linha $R <br>"; 
						if (strlen($xservico) == 0) $msg_erro .= "Favor informar o serviço realizado da peça na linha $R <br>"; 
	
						if(strlen($xpcausa_defeito) == 0) $xpcausa_defeito = "null";
	
						if (strlen ($msg_erro) == 0) {
							
	
							if(strlen($xorcamento_item)==0){
								$sql = "
									INSERT INTO tbl_orcamento_item (
										orcamento         ,
										peca              ,
										qtde              ,
										defeito           ,
										servico_realizado ,
										descricao
									)VALUES(
										$orcamento       ,
										$xpeca           ,
										$xqtde           ,
										$xdefeito        ,
										$xservico        ,
										$xdescricao
									)
									";
							}else{
								$sql = "UPDATE tbl_orcamento_item SET
										peca              = $xpeca   ,
										qtde              = $xqtde   ,
										defeito           = $xdefeito,
										servico_realizado = $xservico
									WHERE orcamento      = $orcamento
									AND   orcamento_item = $xorcamento_item";
							}
	
							$res = @pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);
							if (strlen ($msg_erro) > 0) break ;
						}
					}
				}
				//--===== FIM - Peças do Orçamento ====================================================




			}
			if (strlen ($msg_erro) == 0) $res = pg_exec ($con,"COMMIT TRANSACTION");
		}



		//--===== Cliente do Orçamento ========================================================
		if(strlen($consumidor_cpf)>0){

			$sql = "SELECT * FROM tbl_cliente WHERE cpf = $cpf";
			$res = @pg_exec ($con,$sql);

			if(@pg_numrows($res)==0){

				if(strlen ($consumidor_endereco)    == 0) $consumidor_endereco    = "null" ;
				else                                      $consumidor_endereco    = "'" . $consumidor_endereco    . "'" ;
				if(strlen ($consumidor_numero)      == 0) $consumidor_numero      = "null" ;
				else                                      $consumidor_numero      = "'" . $consumidor_numero      . "'" ;
				if(strlen ($consumidor_complemento) == 0) $consumidor_complemento = "null" ;
				else                                      $consumidor_complemento = "'" . $consumidor_complemento . "'" ;
				if(strlen ($consumidor_bairro)      == 0) $consumidor_bairro      = "null" ;
				else                                      $consumidor_bairro      = "'" . $consumidor_bairro      . "'" ;
				if(strlen ($consumidor_cep)         == 0) $consumidor_cep         = "null" ;
				else                                      $consumidor_cep         = "'" . $consumidor_cep         . "'" ;
				if(strlen ($consumidor_cidade)      == 0) $consumidor_cidade      = "null" ;
				else                                      $consumidor_cidade      = "'" . $consumidor_cidade      . "'" ;
				if(strlen ($consumidor_estado)      == 0) $consumidor_estado      = "null" ;
				else                                      $consumidor_estado      = "'" . $consumidor_estado      . "'" ;

				if (strlen ($cidade) > 0 AND strlen ($estado) > 0) {
					$sql = "SELECT * FROM tbl_cidade WHERE nome = '$consumidor_cidade' AND estado = '$consumidor_estado'";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 1) {
						$xcidade = pg_result ($res,0,cidade);
					}else{
						$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$consumidor_cidade','$consumidor_estado')";
						$res = pg_exec ($con,$sql);
						$sql = "SELECT currval ('seq_cidade')";
						$res = pg_exec ($con,$sql);
						$xcidade = pg_result ($res,0,0);
					}
				}else $xcidade = 'null';

				$sql = "INSERT INTO tbl_cliente (
						nome            ,
						endereco        ,
						numero          ,
						complemento     ,
						bairro          ,
						cep             ,
						cidade          ,
						fone            ,
						cpf             ,
					) VALUES (
						$xnome            ,
						'$endereco'       ,
						'$numero'         ,
						'$complemento'    ,
						'$bairro'         ,
						'$cep'            ,
						$xcidade          ,
						'$fone'           ,
						$xcpf             ,
					)";
			}
		}
		//--===== FIM - Cliente do Orçamento ==================================================


		if (strlen ($msg_erro) > 0) {
			if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
			$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";
		
			if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "1|$msg_erro";
		}else{
			$sua_os = str_replace ("'","",$orcamento);
			echo "ok|<font color='#009900' size='1'><b>Orçamento $orcamento gravado com sucesso</b></font><br><a href='orcamento_cadastro.php'>Novo Orçamento</a>&nbsp;&nbsp;&nbsp;<a href='orcamento_print.php?os=$orcamento' target='_blank'>Imprimir Orçamento</a>";
		}

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
