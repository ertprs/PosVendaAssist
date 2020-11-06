<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
/*

// PARA EXECUTAR UPDATE DAS REFERENCIA DAS PECAS DA TECNOPLUS
$sql= "	select peca
			from tbl_peca
			where fabrica = 27 limit 100;";
	$res= pg_exec($con, $sql);

for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
	$peca=trim(pg_result($res,$i,peca));
	$sql= "	SELECT (codigo_produto +1 ) as cod
			FROM tbl_fabrica 
			WHERE fabrica = 27";
	echo "sql1: $sql <br>";
	$res_x= pg_exec($con, $sql);

	$cod =trim(pg_result($res_x,0,cod));
	if(strlen($cod)==0) $cod=1;

	$sql= "	UPDATE tbl_peca
			SET referencia = '$cod'
			WHERE fabrica = 27 and peca= $peca";
	echo "<br>sql:". $sql;
	$res_t= pg_exec($con, $sql);
	
	$sql= "	UPDATE tbl_fabrica
			SET codigo_produto = '$cod'
			WHERE fabrica = 27 ";
	echo "<br>sql:". $sql;
	$res_t= pg_exec($con, $sql);

}
*/


if(strlen($_GET["tipo"]) > 0) $tipo = $_GET["tipo"];
else                          $tipo = $_POST["tipo"];

if(strlen($_GET["peca"])>0) $peca = trim($_GET['peca']);
else                        $peca = trim($_POST['peca']);
    
$btn_acao = trim($_GET["btn_acao"]);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST["btn_acao"]);
}

if ($btn_acao=='pesquisar'){
	//campos da tabela peça
	$referencia            = trim($_POST['referencia']);
	$descricao             = trim($_POST['descricao']);
	$linha                 = trim($_POST['linha']);
	$marca                 = trim($_POST['marca']);
	$modelo                = trim($_POST['modelo']);

	$sql_adicional = "";
	if (strlen($referencia)>0) $sql_adicional  = "AND UPPER(tbl_peca.referencia)  = UPPER('$referencia') ";
	if (strlen($descricao) > 0)  $sql_adicional .= "AND UPPER(tbl_peca.descricao) like UPPER('%$descricao%')";
	if (strlen($linha)>0)      $sql_adicional .= "AND tbl_peca_item.linha  =  $linha ";
	if (strlen($marca)>0)      $sql_adicional .= "AND tbl_peca_item.marca  =  $marca ";
	if (strlen($modelo)>0)      $sql_adicional .= "AND tbl_peca_item.modelo  =  $modelo ";
}

## PESQUISA SIMPLES

#campo padrão
$campo_pesquisa = "nome";

if ($btn_acao=='pesquisar_simples'){
	
	$busca_por        = trim($_POST['busca_por']);
	$campo_pesquisa   = trim($_POST['campo_pesquisa']);

	$sql_adicional = "";

	if (strlen($busca_por)==0) $msg_erro  .= "Prencha o campo para a pesquisa";
	
	if ($campo_pesquisa=='codigo')
		$sql_adicional .= "AND UPPER(tbl_peca.referencia)   like UPPER('%$busca_por%')";

	if ($campo_pesquisa=='nome')
		$sql_adicional .= "AND UPPER(tbl_peca.descricao)   like UPPER('%$busca_por%')";
	
	if ($campo_pesquisa=='linha'){
		$sql = "SELECT linha FROM tbl_linha
				WHERE UPPER(nome) like UPPER('%$busca_por%')
				AND fabrica=$login_empresa";
		$res = pg_exec ($con,$sql) ;
		$arrays = array();
		if (pg_num_rows($res)>0){
			for ($i=0; $i<pg_num_rows($res); $i++){
				$lin = trim(pg_result($res,$i,linha));
				array_push($arrays,$lin);
			}
			$sql_adicional .= "AND tbl_peca_item.linha IN (".implode(',',$arrays).") ";
		}else{
			$msg_erro .= "Nenhuma linha encontrada com esta descrição.";
		}
	}

	if ($campo_pesquisa=='familia'){
		$sql = "SELECT familia FROM tbl_familia
				WHERE UPPER(descricao) like UPPER('%$busca_por%')
				AND fabrica=$login_empresa";
		$res = pg_exec ($con,$sql) ;
		$arrays = array();
		if (pg_num_rows($res)>0){
			for ($i=0; $i<pg_num_rows($res); $i++){
				$fam = trim(pg_result($res,$i,familia));
				array_push($arrays,$fam);
			}
			$sql_adicional .= "AND tbl_peca_item.familia IN (".implode(',',$arrays).") ";
		}else{
			$msg_erro .= "Nenhuma família encontrada com esta descrição.";
		}
	}

	if ($campo_pesquisa=='marca'){
		$sql = "SELECT marca FROM tbl_marca
				WHERE UPPER(nome) like UPPER('%$busca_por%')
				AND empresa=$login_empresa";
		$res = pg_exec ($con,$sql) ;
		$arrays = array();
		if (pg_num_rows($res)>0){
			for ($i=0; $i<pg_num_rows($res); $i++){
				$mar = trim(pg_result($res,$i,marca));
				array_push($arrays,$mar);
			}
			$sql_adicional .= "AND tbl_peca_item.marca IN (".implode(',',$arrays).") ";
		}else{
			$msg_erro .= "Nenhuma marca encontrada com esta descrição.";
		}
	}

	if ($campo_pesquisa=='modelo'){
		$sql = "SELECT modelo FROM tbl_modelo
				WHERE UPPER(nome) like UPPER('%$busca_por%')
				AND fabrica=$login_empresa";
		$res = pg_exec ($con,$sql) ;
		$arrays = array();
		if (pg_num_rows($res)>0){
			for ($i=0; $i<pg_num_rows($res); $i++){
				$mol = trim(pg_result($res,$i,modelo));
				array_push($arrays,$mol);
			}
			$sql_adicional .= "AND tbl_peca_item.modelo IN (".implode(',',$arrays).") ";
		}else{
			$msg_erro .= "Nenhum modelo encontrada com esta descrição.";
		}
	}

	if ($campo_pesquisa=='caracteristicas')
		$sql_adicional .= "AND tbl_peca_item.compatibilidade like '%$busca_por%'";

}

function reduz_imagem($img, $max_x, $max_y, $nome_foto) {

	//pega o tamanho da imagem ($original_x, $original_y)
	list($width, $height) = getimagesize($img);

	$original_x = $width;
	$original_y = $height;

	// se a largura for maior que altura
	if($original_x > $original_y) {
	   $porcentagem = (100 * $max_x) / $original_x;
	} 
	else {
	   $porcentagem = (100 * $max_y) / $original_y;
	}

	$tamanho_x = $original_x * ($porcentagem / 100);
	$tamanho_y = $original_y * ($porcentagem / 100);

	$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
	$image   = imagecreatefromjpeg($img);
	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);


	$teste_lixo = imagejpeg($image_p, $nome_foto, 65);

}
$excluir_foto = trim($_GET['excluir_foto']);

if (strlen($excluir_foto)>0){
	$foto_id = $excluir_foto;
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "SELECT peca FROM tbl_peca
			WHERE peca = $peca
			AND fabrica=$login_empresa";
	$res = pg_exec ($con,$sql) ;

	if (pg_num_rows($res)>0){
		$peca = trim(pg_result($res,0,peca));        
		$sql = "DELETE FROM tbl_peca_item_foto WHERE peca=$peca AND peca_item_foto=$foto_id";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);    
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg = "Foto excluida com sucesso!";
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "Gravar") {

	$id_peca          = trim($_POST['peca']);
	//campos da tabela peça
	$referencia            = trim($_POST['referencia']);
	$descricao             = trim($_POST['descricao']);
	$origem                = trim($_POST['origem']);
	$unidade               = trim($_POST['unidade']);
	$ativo                 = trim($_POST['ativo']);

	//campos da tabela peça_item
	$caracteristica_tecnica = trim($_POST['caracteristica_tecnica']);

	$altura                   = trim($_POST['altura']);
	$largura                  = trim($_POST['largura']);
	$comprimento              = trim($_POST['comprimento']);
	$peso                     = trim($_POST['peso']);

	$embalado_altura          = trim($_POST['embalado_altura']);
	$embalado_largura         = trim($_POST['embalado_largura']);
	$embalado_comprimento     = trim($_POST['embalado_comprimento']);
	$embalado_peso            = trim($_POST['embalado_peso']);

	$estoque_minimo           = trim($_POST['estoque_minimo']);
	$valor_compra             = trim($_POST['valor_compra']);
	$valor_venda              = trim($_POST['valor_venda']);

	$descricao_reduzida       = trim($_POST['descricao_reduzida']);
	$compatibilidade          = trim($_POST['compatibilidade']);
	$acessorios               = trim($_POST['acessorios']);
	$garantia                 = trim($_POST['garantia']);

	$controle_numero_serie    = trim($_POST['controle_numero_serie']);
	$numero_serie_obrigatorio = trim($_POST['numero_serie_obrigatorio']);

	$familia                  = trim($_POST['familia']);
	$linha                    = trim($_POST['linha']);

	$marca                    = trim($_POST['marca']);
	$modelo                   = trim($_POST['modelo']);

	if(trim($marca) == '-1'){
		$marca        = "";
		$nova_marca   = trim($_POST['nova_marca']);
	}
	
	if(trim($modelo) == '-1'){
		$modelo       = "";
		$novo_modelo  = trim($_POST['novo_modelo']);
	}

	$icms_produto             = trim($_POST['icms_produto']);

	$porcento_lucro_aprazo    = trim($_POST['porcento_lucro_aprazo']);
	$porcento_lucro_avista    = trim($_POST['porcento_lucro_avista']);
	$procento_lucro_atacado   = trim($_POST['procento_lucro_atacado']);
	$porcento_lucro_internet  = trim($_POST['porcento_lucro_internet']);

	$xpercentual_comissao        = trim($_POST['percentual_comissao']);
	$xpercentual_administrativos = trim($_POST['percentual_administrativos']);
	$xpercentual_vendas          = trim($_POST['percentual_vendas']);
	$xpercentual_lucro           = trim($_POST['percentual_lucro']);
	$xpercentual_marketing       = trim($_POST['percentual_marketing']);
	$xpercentual_perdas          = trim($_POST['percentual_perdas']);


	//VALIDAÇÕIES DE SEGURANÇA
	if(strlen($descricao) == 0 ) $msg_erro .= "Digite a descrição<br>";
	if(strlen($linha)     == 0 ) $msg_erro .= "Selecione a linha<br>";
	if(strlen($familia)   == 0 ) $msg_erro .= "Selecione a família<br>";
	if(strlen($marca)     == 0 AND strlen($nova_marca)==0  ) $msg_erro .= "Selecione a marca<br>";
	if(strlen($modelo)    == 0 AND strlen($novo_modelo)==0 ) $msg_erro .= "Selecione o modelo<br>";
	if(strlen($origem)    == 0 ) $msg_erro .= "Selecione a origem<br>";

	if (strlen($descricao_reduzida)>30) {
		$msg_erro .= "Nome reduzido deve ter no máximo 30 caracteres.";
	}

	//INFORMAÇÕES GERAIS
	if(strlen($referencia)              > 0) $xreferencia              = "'".$referencia."' ";
	else                                    $xreferencia              = "null";

	if(strlen($descricao)              > 0) $xdescricao              = "'".$descricao."'";
	else                                    $xdescricao              = "null";
	if(strlen($origem)                 > 0) $xorigem                 = "'".$origem."'";
	else                                    $xorigem                 = "null";
	if(strlen($unidade)                > 0) $xunidade                = "'".$unidade."'";
	else                                    $xunidade                = "null";

	if(strlen($descricao_reduzida)     > 0) $xdescricao_reduzida     = "'".$descricao_reduzida."'";
	else                                    $xdescricao_reduzida     = "null";    
	if(strlen($compatibilidade)        > 0) $xcompatibilidade        = "'".$compatibilidade."'";
	else                                    $xcompatibilidade        = "null";
	if(strlen($acessorios)             > 0) $xacessorios             = "'".$acessorios."'";
	else                                    $xacessorios             = "null";
	if(strlen($garantia)               > 0) $xgarantia               = $garantia;
	else                                    $xgarantia               = "null";
	if(strlen($controle_numero_serie)  > 0) $xcontrole_numero_serie  = "'t'";
	else                                    $xcontrole_numero_serie  = "null";

	if(strlen($numero_serie_obrigatorio)> 0)$xnumero_serie_obrigatorio= "'t'";
	else                                    $xnumero_serie_obrigatorio= "null";

	if(strlen($caracteristica_tecnica) > 0) $xcaracteristica_tecnica = "'".$caracteristica_tecnica."'"; 
	else                                    $xcaracteristica_tecnica = "null";
	if(strlen($valor_compra)           > 0) $xvalor_compra           = "'".$valor_compra."'";
	else                                    $xvalor_compra           = "null";
	if(strlen($valor_venda)            > 0) $xvalor_venda            = "'".$valor_venda."'";
	else                                    $xvalor_venda            = "null";

	if(strlen($altura)                 > 0) $xaltura                 = "'".$altura."'";
	else                                    $xaltura                 = "null";
	if(strlen($largura)                > 0) $xlargura                = "'".$largura."'";
	else                                    $xlargura                = "null";
	if(strlen($peso)                   > 0) $xpeso                   = "'".$peso."'";
	else                                    $xpeso                   = "null";
	if(strlen($comprimento)            > 0) $xcomprimento            = "'".$comprimento."'";
	else                                    $xcomprimento            = "null";

	if(strlen($embalado_altura)        > 0) $xembalado_altura        = "'".$embalado_altura."'";
	else                                    $xembalado_altura        = "null";
	if(strlen($embalado_largura)       > 0) $xembalado_largura       = "'".$embalado_largura."'";
	else                                    $xembalado_largura       = "null";
	if(strlen($embalado_peso)          > 0) $xembalado_peso          = "'".$embalado_peso."'";
	else                                    $xembalado_peso          = "null";
	if(strlen($embalado_comprimento)   > 0) $xembalado_comprimento   = "'".$embalado_comprimento."'";
	else                                    $xembalado_comprimento   = "null";

	if(strlen($estoque_minimo)         > 0) $xestoque_minimo         = "'".$estoque_minimo."'";
	else                                    $xestoque_minimo         = "null";

	if(strlen($linha)                  > 0) $xlinha                  = "'".$linha."'";
	else                                    $xlinha                  = "null";
	if(strlen($familia)                > 0) $xfamilia                = "'".$familia."'";
	else                                    $xfamilia                = "null";

	if(strlen($marca) > 0) {
		$xmarca = $marca;
	}else{
		if (strlen($nova_marca)==0){
			$msg_erro .= "Informe a marca do produto!";
		}
	}
	if(strlen($modelo) > 0) {
		$xmodelo = $modelo;
	}else{
		if (strlen($novo_modelo)==0){
			$msg_erro .= "Informe o modelo do produto!";
		}
	}

	if(strlen($icms_produto)           > 0) $xicms_produto           = "'".$icms_produto."'";
	else                                    $xicms_produto           = "null";
	if(strlen($ativo)                  > 0) $xativo                  = "'TRUE'";
	else                                    $xativo                  = "FALSE";

	if(strlen($porcento_lucro_aprazo)  > 0) $xporcento_lucro_aprazo  = "'".$porcento_lucro_aprazo."'";   
	else                                    $xporcento_lucro_aprazo  = "NULL";
	if(strlen($porcento_lucro_avista)  > 0) $xporcento_lucro_avista  = "'".$porcento_lucro_avista."'";   
	else                                    $xporcento_lucro_avista  = "NULL";
	if(strlen($porcento_lucro_atacado) > 0) $xporcento_lucro_atacado = "'".$porcento_lucro_atacado."'";  
	else                                    $xporcento_lucro_atacado = "NULL";
	if(strlen($porcento_lucro_internet)> 0) $xporcento_lucro_internet= "'".$porcento_lucro_internet."'"; 
	else                                    $xporcento_lucro_internet= "NULL";

	if(strlen($xpercentual_comissao)       > 0) $xpercentual_comissao        = "'".$xpercentual_comissao        ."'"; 
	else                                        $xpercentual_comissao        = "NULL";
	if(strlen($xpercentual_administrativos)> 0) $xpercentual_administrativos = "'".$xpercentual_administrativos ."'"; 
	else                                        $xpercentual_administrativos = "NULL";
	if(strlen($xpercentual_vendas)         > 0) $xpercentual_vendas          = "'".$xpercentual_vendas          ."'"; 
	else                                        $xpercentual_vendas          = "NULL";
	if(strlen($xpercentual_lucro)          > 0) $xpercentual_lucro           = "'".$xpercentual_lucro           ."'"; 
	else                                        $xpercentual_lucro           = "NULL";
	if(strlen($xpercentual_marketing)      > 0) $xpercentual_marketing       = "'".$xpercentual_marketing       ."'"; 
	else                                        $xpercentual_marketing       = "NULL";
	if(strlen($xpercentual_perdas)         > 0) $xpercentual_perdas          = "'".$xpercentual_perdas          ."'"; 
	else                                        $xpercentual_perdas          = "NULL";

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//--=== Cadastro de Principal ============================================================================
		if (strlen($id_peca)==0){

			$sql = "INSERT INTO tbl_peca (
					referencia    ,
					descricao     ,
					origem        ,
					unidade       ,
					ativo         ,
					fabrica
				)VALUES (
					$xreferencia  ,
					$xdescricao   ,
					$xorigem      ,
					$xunidade     ,
					$xativo       ,
					$login_empresa
				)";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$res     = pg_exec ($con,"SELECT CURRVAL ('seq_peca')");
			$id_peca = pg_result ($res,0,0);
			$peca = $id_peca;

			if ($xreferencia == 'null'){
				$sql = "update tbl_peca set referencia = '$peca' where peca = $peca";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			# só entra aqui se digitou a nova marca
			if(strlen($nova_marca) > 0){
				$sql = "SELECT marca
						FROM tbl_marca 
						WHERE nome ILIKE '$nova_marca'
						AND fabrica = $login_empresa";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)==0){
					$sql = "INSERT INTO tbl_marca (fabrica,nome,empresa)VALUES (0,'$nova_marca',$login_empresa)";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$res     = pg_exec ($con,"SELECT CURRVAL ('seq_marca')");
					$id_marca = pg_result ($res,0,0);
					$xmarca = $id_marca;
				}else{
					//$msg_erro .= "Marca já cadastrada"; Retirado por fabio HD 4480
					# pega a marca já cadastrada
					$xmarca =  pg_result ($res,0,0);
				}
			}

			# só entra aqui se digitou um novo modelo
			if(strlen($novo_modelo) > 0){
				$sql = "SELECT modelo
						FROM tbl_modelo 
						WHERE nome ILIKE '$novo_modelo'
						AND fabrica = $login_empresa";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)==0){
					$sql = "INSERT INTO tbl_modelo (fabrica,nome) VALUES ($login_empresa,'$novo_modelo')";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$res     = pg_exec ($con,"SELECT CURRVAL ('tbl_modelo_modelo_seq')");
					$id_modelo = pg_result ($res,0,0);
					$xmodelo = $id_modelo;
				}else{
					//$msg_erro .= "Marca já cadastrada"; Retirado por fabio HD 4480
					# pega a marca já cadastrada
					$xmodelo =  pg_result ($res,0,0);
				}
			}

			$sql = "INSERT INTO tbl_peca_item (
					caracteristica_tecnica  ,
					altura                  ,
					largura                 ,
					comprimento             ,
					peso                    ,
					embalado_altura         ,
					embalado_largura        ,
					embalado_comprimento    ,
					embalado_peso           ,
					estoque_minimo          ,
					valor_compra            ,
					valor_venda             ,
					familia                 ,
					linha                   ,
					marca                   ,
					modelo                  ,
					empresa                 ,
					empregado               ,
					peca                    ,
					descricao_reduzida      ,
					compatibilidade         ,
					acessorios              ,
					garantia                ,
					controle_numero_serie   ,
					numero_serie_obrigatorio,
					icms_produto            ,
					porcento_lucro_aprazo   ,
					porcento_lucro_avista   ,
					porcento_lucro_atacado  ,
					porcento_lucro_internet ,
					percentual_comissao     ,
					percentual_administrativo,
					percentual_vendas        ,
					percentual_lucro         ,
					percentual_marketing     ,
					percentual_perdas
				)VALUES(
					$xcaracteristica_tecnica,
					$xaltura                ,
					$xlargura               ,
					$xcomprimento           ,
					$xpeso                  ,
					$xembalado_altura       ,
					$xembalado_largura      ,
					$xembalado_comprimento  ,
					$xembalado_peso         ,
					$xestoque_minimo        ,
					$xvalor_compra          ,
					$xvalor_venda           ,
					$xfamilia               ,
					$xlinha                 ,
					$xmarca                 ,
					$xmodelo                ,
					$login_empresa          ,
					$login_empregado        ,
					$id_peca                ,
					$xdescricao_reduzida    ,
					$xcompatibilidade       ,
					$xacessorios            ,
					$xgarantia              ,
					$xcontrole_numero_serie ,
					$xnumero_serie_obrigatorio   ,
					$xicms_produto               ,
					$xporcento_lucro_aprazo      ,
					$xporcento_lucro_avista      ,
					$xporcento_lucro_atacado     ,
					$xporcento_lucro_internet    ,
					$xpercentual_comissao        ,
					$xpercentual_administrativos ,
					$xpercentual_vendas          ,
					$xpercentual_lucro           ,
					$xpercentual_marketing       ,
					$xpercentual_perdas
				)";

			$embalado_altura         = trim($_POST['embalado_altura']);
			$embalado_largura        = trim($_POST['embalado_largura']);
			$embalado_comprimento    = trim($_POST['embalado_comprimento']);
			$embalado_peso           = trim($_POST['embalado_peso']);

			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			$sql = "INSERT INTO tbl_estoque (peca,qtde) VALUES ($id_peca,0)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sql = "INSERT INTO tbl_estoque_extra (peca,media_7,media_20,media_40,data_atualizacao) VALUES ($id_peca,0,0,0,current_date)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			for($w=0;$w<10;$w++){
				$tabela_preco  = $_POST['tabela_preco_' .$w];
				$data_vigencia = $_POST['data_vigencia_'.$w];
				$valor_peca    = $_POST['valor_peca_'   .$w];
				if(strlen($tabela_preco)>0 and strlen($valor_peca)>0){
					$xdata_vigencia = str_replace("/","",$data_vigencia);
					$xdata_vigencia =  "'" . substr($xdata_vigencia,4,4) . "-" . substr($xdata_vigencia,2,2) . "-" . substr($xdata_vigencia,0,2) . "'";
					
					$ysql = "SELECT tbl_tabela_item_erp.tabela_item_erp
							FROM tbl_tabela_item_erp
							WHERE tbl_tabela_item_erp.tabela = $tabela_preco
							and tbl_tabela_item_erp.peca = $id_peca
							AND tbl_tabela_item_erp.termino_vigencia is null
							ORDER BY data_vigencia desc
							LIMIT 1";
					$yres = @pg_exec ($con,$ysql);
					$msg_erro = pg_errormessage($con);

					if(pg_numrows($yres)>0){
						$yytabela_item_erp = pg_result($yres,0,0);
						
						$yysql = "UPDATE tbl_tabela_item_erp set  
						termino_vigencia = $xdata_vigencia
						where  tabela_item_erp = $yytabela_item_erp";
						
						$yyres = @pg_exec ($con,$yysql);
						$msg_erro = pg_errormessage($con);
					
					}
//termino_vigencia = $xdata_vigencia

					$sql = "INSERT INTO tbl_tabela_item_erp
							(	tabela                     , 
								peca                       , 
								preco                      ,
								data_vigencia              ,
								percentual_comissao        ,
								percentual_administrativo  ,
								percentual_vendas          ,
								percentual_lucro           ,
								percentual_marketing       ,
								percentual_perdas
							)
							values
							(	$tabela_preco                ,
								$id_peca                     ,
								$valor_peca                  ,
								$xdata_vigencia              ,
								$xpercentual_comissao        ,
								$xpercentual_administrativos ,
								$xpercentual_vendas          ,
								$xpercentual_lucro           ,
								$xpercentual_marketing       ,
								$xpercentual_perdas
							);";
					$res = @pg_exec ($con,$sql);
				//	echo $sql;
					$msg_erro = pg_errormessage($con);
				}
			}
			
			/* INSERE O CODIGO_BARRA*/
			for($w=0;$w<10;$w++){
				$codigo_barra  = $_POST['codigo_barra_' .$w];
				if(strlen($codigo_barra)>0 ){
					
					$ysql = "
							SELECT peca
							FROM tbl_peca_item_codigo_barra
							WHERE tbl_peca_item_codigo_barra.codigo_barra = '$codigo_barra'
							LIMIT 1";
					$yres = @pg_exec ($con,$ysql);
					$msg_erro = pg_errormessage($con);

					if(pg_numrows($yres)==0){

						$sql = "INSERT INTO tbl_peca_item_codigo_barra 
										(peca, codigo_barra, fabrica ) 
								VALUES($id_peca, '$codigo_barra', $login_empresa);";
						$res = @pg_exec ($con,$sql);
					//	echo $sql;
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}else{
			$sql = "UPDATE tbl_peca SET
					descricao    = $xdescricao ,
					origem       = $xorigem    ,
					unidade      = $xunidade   ,
					ativo        = $xativo
				WHERE peca = $id_peca ";
			$res = pg_exec ($con,$sql);
//			echo nl2br($sql );
//			referencia   = '$referencia',
			$msg_erro = pg_errormessage($con);

			# só entra aqui se digitou a nova marca
			if(strlen($nova_marca) > 0){
				$sql = "SELECT marca
						FROM tbl_marca 
						WHERE nome ILIKE '$nova_marca'
						AND fabrica = $login_empresa";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)==0){
					$sql = "INSERT INTO tbl_marca (fabrica,nome,empresa)VALUES (0,'$nova_marca',$login_empresa)";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$res     = pg_exec ($con,"SELECT CURRVAL ('seq_marca')");
					$id_marca = pg_result ($res,0,0);
					$xmarca = $id_marca;
				}else{
					//$msg_erro .= "Marca já cadastrada"; Retirado por fabio HD 4480
					# pega a marca já cadastrada
					$xmarca =  pg_result ($res,0,0);
				}
			}

			# só entra aqui se digitou um novo modelo
			if(strlen($novo_modelo) > 0){
				$sql = "SELECT modelo
						FROM tbl_modelo 
						WHERE nome ILIKE '$novo_modelo'
						AND fabrica = $login_empresa";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)==0){
					$sql = "INSERT INTO tbl_modelo (fabrica,nome) VALUES ($login_empresa,'$novo_modelo')";
					$res = pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$res     = pg_exec ($con,"SELECT CURRVAL ('tbl_modelo_modelo_seq')");
					$id_modelo = pg_result ($res,0,0);
					$xmodelo = $id_modelo;
				}else{
					//$msg_erro .= "Marca já cadastrada"; Retirado por fabio HD 4480
					# pega a marca já cadastrada
					$xmodelo =  pg_result ($res,0,0);
				}
			}

			$sql = "UPDATE tbl_peca_item SET
					caracteristica_tecnica = $xcaracteristica_tecnica,
					altura                 = $xaltura                ,
					largura                = $xlargura               ,
					comprimento            = $xcomprimento           ,
					peso                   = $xpeso                  ,
					embalado_altura        = $xembalado_altura       ,
					embalado_largura       = $xembalado_largura      ,
					embalado_comprimento   = $xembalado_comprimento  ,
					embalado_peso          = $xembalado_peso         ,
					estoque_minimo         = $xestoque_minimo        ,
					valor_compra           = $xvalor_compra          ,
					valor_venda            = $xvalor_venda           ,
					familia                = $xfamilia               ,
					linha                  = $xlinha                 ,
					marca                  = $xmarca                 ,
					modelo                 = $xmodelo                ,
					ultimo_empregado       = $login_empregado        ,
					data_alteracao         = current_timestamp       ,
					descricao_reduzida     = $xdescricao_reduzida    ,
					compatibilidade        = $xcompatibilidade       ,
					acessorios             = $xacessorios            ,
					garantia               = $xgarantia              ,
					controle_numero_serie  = $xcontrole_numero_serie ,
					numero_serie_obrigatorio = $xnumero_serie_obrigatorio ,
					icms_produto             = $xicms_produto             ,
					porcento_lucro_aprazo    = $xporcento_lucro_aprazo    ,
					porcento_lucro_avista    = $xporcento_lucro_avista    ,
					porcento_lucro_atacado   = $xporcento_lucro_atacado    ,
					porcento_lucro_internet  = $xporcento_lucro_internet   ,
					percentual_comissao      = $xpercentual_comissao       ,
					percentual_administrativo= $xpercentual_administrativos,
					percentual_vendas        = $xpercentual_vendas         ,
					percentual_lucro         = $xpercentual_lucro          ,
					percentual_marketing     = $xpercentual_marketing      ,
					percentual_perdas        = $xpercentual_perdas
				WHERE peca = $id_peca";
			$res = pg_exec ($con,$sql);

			$msg_erro .= pg_errormessage($con);

			for($w=0;$w<10;$w++){
				$tabela_preco  = $_POST['tabela_preco_' .$w];
				$data_vigencia = $_POST['data_vigencia_'.$w];
				$valor_peca    = $_POST['valor_peca_'   .$w];
				if(strlen($tabela_preco)>0 and strlen($valor_peca)>0){
					$xdata_vigencia = str_replace("/","",$data_vigencia);
					$xdata_vigencia =  "'" . substr($xdata_vigencia,4,4) . "-" . substr($xdata_vigencia,2,2) . "-" . substr($xdata_vigencia,0,2) . "'";

					$ysql = "SELECT tbl_tabela_item_erp.tabela_item_erp
							FROM tbl_tabela_item_erp
							WHERE tbl_tabela_item_erp.tabela = $tabela_preco
							and tbl_tabela_item_erp.peca = $id_peca
							AND tbl_tabela_item_erp.termino_vigencia is null
							ORDER BY data_vigencia desc
							LIMIT 1";
					$yres = pg_exec ($con,$ysql);
					$msg_erro .= pg_errormessage($con);

					if(pg_numrows($yres)>0){
						$yytabela_item_erp = pg_result($yres,0,0);
						
						$yysql = "UPDATE tbl_tabela_item_erp set  
						termino_vigencia = $xdata_vigencia
						where  tabela_item_erp = $yytabela_item_erp";
						
						$yyres = pg_exec ($con,$yysql);
						$msg_erro .= pg_errormessage($con);
				//	echo $yysql;
					}
					$sql = "INSERT INTO tbl_tabela_item_erp(
								tabela                     ,
								peca                       , 
								preco                      ,
								data_vigencia              ,
								percentual_comissao        ,
								percentual_administrativo  ,
								percentual_vendas          ,
								percentual_lucro           ,
								percentual_marketing       ,
								percentual_perdas
								)values(
								$tabela_preco              ,
								$id_peca                   ,
								$valor_peca                ,
								$xdata_vigencia            ,
								$xpercentual_comissao        ,
								$xpercentual_administrativos ,
								$xpercentual_vendas          ,
								$xpercentual_lucro           ,
								$xpercentual_marketing       ,
								$xpercentual_perdas
								);";
							//	echo $sql;

					$res = pg_exec ($con,$sql);
//					echo $sql;
					$msg_erro .= pg_errormessage($con);
				}
			}


			/* INSERE O CODIGO_BARRA*/
			for($w=0;$w<10;$w++){
				$codigo_barra  = $_POST['codigo_barra_' .$w];
				if(strlen($codigo_barra)>0 ){
					
					$ysql = "
							SELECT peca
							FROM tbl_peca_item_codigo_barra
							WHERE tbl_peca_item_codigo_barra.codigo_barra = '$codigo_barra'
							LIMIT 1";
					$yres = pg_exec ($con,$ysql);
					$msg_erro = pg_errormessage($con);

					if(pg_numrows($yres)==0){

						$sql = "INSERT INTO tbl_peca_item_codigo_barra 
										(peca, codigo_barra, fabrica ) 
								VALUES($id_peca, '$codigo_barra', $login_empresa);";
						$res = @pg_exec ($con,$sql);
					//	echo $sql;
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}

		if (isset($_FILES['arquivos'])){

			$Destino = '/www/assist/www/erp/imagens/fotos/'; 

			$Fotos = $_FILES['arquivos'];

			for ($i=0; $i<6; $i++){
				$arquivo_foto = isset($Fotos['tmp_name'][$i]) ? $Fotos['tmp_name'][$i] : FALSE;
				if (!$arquivo_foto) continue;

				$Nome    = $Fotos['name'][$i]; 
				$Tamanho = $Fotos['size'][$i]; 
				$Tipo    = $Fotos['type'][$i]; 
				$Tmpname = $Fotos['tmp_name'][$i];

				if (strlen($Nome)==0) continue;

				if(preg_match('/^image\/(pjpeg|jpeg|png|gif|bmp)$/', $Tipo)){

					if(!is_uploaded_file($Tmpname)){
						$msg_erro .= "Não foi possível efetuar o upload.";
						break;
					}

					$nome_foto  = "imagem_$login_empresa-$login_loja-$id_peca-$i-$Nome";
					$nome_foto = str_replace(" ","_",$nome_foto);
					
					$nome_thumb = "imagem_$login_empresa-$login_loja-$id_peca-$i-thumb-$Nome";
					$nome_thumb = str_replace(" ","_",$nome_thumb);

					$Caminho_foto  = $Destino . $nome_foto;
					$Caminho_thumb = $Destino . $nome_thumb;

						//if(move_uploaded_file($Tmpname, $Caminho)){
						reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
						reduz_imagem($Tmpname, 120, 90,  $Caminho_thumb); 

					$sql = "INSERT INTO tbl_peca_item_foto         
								(descricao, caminho,caminho_thumb, peca)
								VALUES ('$foto_desc','$Caminho_foto','$Caminho_thumb',$id_peca)";
						$res = pg_exec ($con,$sql);
						$msg_erro .= pg_errormessage($con);
					//}
				//copy($_FILES['arquivos']['tmp_name'][0],"$pasta/$numeros2.jpg");          
				}else{
					$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
					
				}
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "<a href='$PHP_SELF'>Produto gravado com sucesso!</a>";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}

}

if (strlen($peca)>0) {

	$sql = "SELECT
			tbl_peca.peca,
			tbl_peca.referencia,
			tbl_peca.descricao ,
			tbl_peca.origem    ,
			tbl_peca.estoque   ,
			tbl_peca.unidade   ,
			tbl_peca.ativo     ,
			tbl_peca_item.*,
			to_char(tbl_peca_item.data,'DD/MM/YYYY') as data_cadastro,
			to_char(tbl_peca_item.data_alteracao,'DD/MM/YYYY') as data_alteracao2
		FROM tbl_peca
		LEFT JOIN tbl_peca_item USING(peca)
		WHERE fabrica = $login_empresa
		AND   peca    = $peca";
	$res = pg_exec ($con,$sql) ;

	$peca            = trim(pg_result($res,0,peca));    
	$referencia      = trim(pg_result($res,0,referencia));
	$descricao       = trim(pg_result($res,0,descricao));
	$estoque         = trim(pg_result($res,0,estoque));
	$origem          = strtoupper(trim(pg_result($res,0,origem)));
	$unidade         = trim(pg_result($res,0,unidade));
	$ativo           = trim(pg_result($res,0,ativo));

	$data_cadastro        = trim(pg_result($res,0,data_cadastro));
	$empregado            = trim(pg_result($res,0,empregado));
	$data_alteracao       = trim(pg_result($res,0,data_alteracao2));
	$ultimo_empregado     = trim(pg_result($res,0,ultimo_empregado));

	$descricao_reduzida       = trim(pg_result($res,0,descricao_reduzida));
	$compatibilidade          = trim(pg_result($res,0,compatibilidade));
	$acessorios               = trim(pg_result($res,0,acessorios));
	$garantia                 = trim(pg_result($res,0,garantia));
	$controle_numero_serie    = trim(pg_result($res,0,controle_numero_serie));
	$numero_serie_obrigatorio = trim(pg_result($res,0,numero_serie_obrigatorio));

	$caracteristica_tecnica = trim(pg_result($res,0,caracteristica_tecnica));
	$valor_compra           = trim(pg_result($res,0,valor_compra));
	$valor_frete            = trim(pg_result($res,0,valor_frete));
	$valor_custo            = trim(pg_result($res,0,valor_custo));
	$valor_custo_medio      = trim(pg_result($res,0,valor_custo_medio));
	$percento_desconto      = trim(pg_result($res,0,percento_desconto));
	$percento_lucro         = trim(pg_result($res,0,percento_lucro));
	$altura                 = trim(pg_result($res,0,altura));
	$largura                = trim(pg_result($res,0,largura));
	$comprimento            = trim(pg_result($res,0,comprimento));
	$peso                   = trim(pg_result($res,0,peso));
	$embalado_altura        = trim(pg_result($res,0,embalado_altura));
	$embalado_largura       = trim(pg_result($res,0,embalado_largura));
	$embalado_comprimento   = trim(pg_result($res,0,embalado_comprimento));
	$embalado_peso          = trim(pg_result($res,0,embalado_peso));
	$estoque_minimo         = trim(pg_result($res,0,estoque_minimo));
	$status                 = trim(pg_result($res,0,status));
	$familia                = trim(pg_result($res,0,familia));
	$linha                  = trim(pg_result($res,0,linha));
	$marca                  = trim(pg_result($res,0,marca));
	$modelo                 = trim(pg_result($res,0,modelo));
	$empresa                = trim(pg_result($res,0,empresa));
	$valor_venda            = trim(pg_result($res,0,valor_venda));
	$icms_produto           = trim(pg_result($res,0,icms_produto));

	$porcento_lucro_aprazo  = trim(pg_result($res,0,porcento_lucro_aprazo));
	$procento_lucro_avista  = trim(pg_result($res,0,porcento_lucro_avista));
	$porcento_lucro_atacado = trim(pg_result($res,0,porcento_lucro_atacado));
	$porcento_lucro_internet= trim(pg_result($res,0,porcento_lucro_internet));

	$percentual_administrativos = trim(pg_result($res,0,percentual_administrativo));
	$percentual_comissao        = trim(pg_result($res,0,percentual_comissao));
	$percentual_marketing       = trim(pg_result($res,0,percentual_marketing));
	$percentual_lucro           = trim(pg_result($res,0,percentual_lucro));
	$percentual_vendas          = trim(pg_result($res,0,percentual_vendas));
	$percentual_perdas          = trim(pg_result($res,0,percentual_perdas));

	if (strlen($familia)>0){
		$sql = "SELECT  familia,descricao
			FROM tbl_familia
			WHERE familia = $familia";
		$res = pg_exec ($con,$sql) ;
		$familia             = trim(pg_result($res,0,familia));
		$familia_descricao   = trim(pg_result($res,0,descricao));
	}

	if (strlen($empregado)>0){
		$sql_emp = "SELECT nome
				FROM tbl_pessoa
				JOIN tbl_empregado USING(pessoa)
				WHERE tbl_empregado.empresa = $login_empresa
				AND tbl_empregado.empregado = $empregado";
		$res_emp = pg_exec ($con,$sql_emp) ;
		if (pg_numrows($res_emp)>0){
			$empregado       = trim(pg_result($res_emp,0,nome));
		}
	}

	if (strlen($ultimo_empregado)>0){
		$sql_emp = "SELECT nome
				FROM tbl_pessoa
				JOIN tbl_empregado USING(pessoa)
				WHERE tbl_empregado.empresa = $login_empresa
				AND tbl_empregado.empregado = $ultimo_empregado";
		$res_emp = pg_exec ($con,$sql_emp) ;
		if (pg_numrows($res_emp)>0){
			$ultimo_empregado        = trim(pg_result($res_emp,0,nome));
		}
	}
	
	$sql = "SELECT  peca_item_foto,caminho,caminho_thumb,descricao
			FROM tbl_peca_item_foto
			WHERE peca = $peca";
	$res = pg_exec ($con,$sql) ;
	$fotos = array();
	$num_fotos = pg_num_rows($res);
	if ($num_fotos){
		for ($i=0; $i<$num_fotos; $i++){
			$caminho      = trim(pg_result($res,$i,caminho));
			$caminho_thum = trim(pg_result($res,$i,caminho_thumb));
			$foto_descricao    = trim(pg_result($res,$i,descricao));
			$foto_id      = trim(pg_result($res,$i,peca_item_foto));
			
			$caminho = str_replace("/www/assist/www/erp/","",$caminho);
			$caminho_thum = str_replace("/www/assist/www/erp/","",$caminho_thum);
			
			$aux=explode("|",$caminho."|".$caminho_thum."|".$foto_descricao."|".$foto_id);
			array_push($fotos,$aux);
		}
	}
}
include "menu.php";
//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'cadastros') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

?>



<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function checarNumeroInteiro(campo){
	campo.value = parseInt(campo.value);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
function limpar_form(formu){
	for( var i = 0 ; i < formu.length; i++ ){
		if (formu.elements[i].type !='button' && formu.elements[i].type !='submit'){
			if(formu.elements[i].type=='checkbox'){
				formu.elements[i].checked=false;
			}else{
				formu.elements[i].value='';
			}
		}
	}
}

function adicionaPreco() {

		if(document.getElementById('tabela_preco').value=="") { alert('Selecione a tabela de preço');   return false}
		if(document.getElementById('data_vigencia').value=="")   { alert('Informe a data vigencia');   return false}
		if(document.getElementById('valor_peca').value=="")           { alert('Informe o valor da peça'); return false}

		var tbl = document.getElementById('tbl_preco');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;

		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		//var celula = criaCelula(document.getElementById('tabela_preco').value);
//		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var celula = criaCelula(document.getElementById('tabela_preco').options[document.getElementById('tabela_preco').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'tabela_preco_' + iteration);
		el.setAttribute('id', 'tabela_preco_' + iteration);
		el.setAttribute('value',document.getElementById('tabela_preco').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'data_vigencia_' + iteration);
		el.setAttribute('id', 'data_vigencia_' + iteration);
		el.setAttribute('value',document.getElementById('data_vigencia').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'valor_peca_' + iteration);
		el.setAttribute('id', 'valor_peca_' + iteration);
		el.setAttribute('value',document.getElementById('valor_peca').value);
		celula.appendChild(el);

		linha.appendChild(celula);

		// coluna 2 - TELEFONE
		celula = criaCelula(document.getElementById('data_vigencia').value);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 3 - TIPO
		var celula = criaCelula(document.getElementById('valor_peca').value);
		celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 4 - Ações
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerPreco(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);


		//limpa form de add mao de obra
		document.getElementById('tabela_preco').selectedIndex=0;
		//document.getElementById('data_vigencia').value='';
//		document.getElementById('valor_peca').value=0;

		document.getElementById('tabela_preco').focus();

	}
	function removerPreco(iidd){
	var tbl = document.getElementById('tbl_preco');
	var oRow = iidd.parentElement.parentElement;
	tbl.deleteRow(oRow.rowIndex);
}


function adicionaCodigoBarra() {
	if(document.getElementById('codigo_barra').value=="")           { alert('Informe o Código de Barra'); return false}

	var tbl = document.getElementById('tbl_codigo_barra');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	//var celula = criaCelula(document.getElementById('tabela_preco').value);
//		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

	var celula = criaCelula(document.getElementById('codigo_barra').value);
	celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'codigo_barra_' + iteration);
	el.setAttribute('id', 'codigo_barra_' + iteration);
	el.setAttribute('value',document.getElementById('codigo_barra').value);
	celula.appendChild(el);

	linha.appendChild(celula);

	// coluna 4 - Ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerCodigoBarra(this);};
	celula.appendChild(el);
	linha.appendChild(celula);

	// finaliza linha da tabela
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	/*linha.style.cssText = 'color: #404e2a;';*/
	tbl.appendChild(tbody);


	//limpa form de add mao de obra
	document.getElementById('tabela_preco').selectedIndex=0;
	//document.getElementById('data_vigencia').value='';
//		document.getElementById('valor_peca').value=0;

	document.getElementById('codigo_barra').focus();
}


	
	function removerPreco(iidd){
	var tbl = document.getElementById('tbl_preco');
	var oRow = iidd.parentElement.parentElement;
	tbl.deleteRow(oRow.rowIndex);
}

	function removerCodigoBarra(iidd){
		var tbl = document.getElementById('tbl_codigo_barra');
		var oRow = iidd.parentElement.parentElement;
		tbl.deleteRow(oRow.rowIndex);
	}


	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}
</script>

<!--========================= AJAX ==================================.-->
<? include "javascript_pesquisas.php" ?>

<style>
a{
	font-family: Verdana;
	font-size: 10px;
	color:#3399FF;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.tabela{
	font-family: Verdana;
	font-size: 10px;
	
}
.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color:#FFF;
}
.Titulo_Colunas{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}

tr.linha td {
	border-bottom: 1px solid #CECECE; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}
img{
	border:0;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	padding:2px;
}

caption{
	BACKGROUND-COLOR: #FFF;
	font-size:12px;
	font-weight:bold;
	text-align:center;
}

.text_curto{
    font-size:10px;
    color:#808080;
}
.blur{
   background-color: #ccc; /*shadow color*/
   color: inherit;
   margin-left: 4px;
   margin-top: 4px;
   width: 224px;
}  
.Titulo_Tabela_Menor{
	background-color:#C7D3E2;
	border-bottom:1px solid #4A5E8A;
	font-weight:bold;
	font-size:10px;
}
tr.linha td {
	border-bottom: 1px solid #EDEDE9; 
	border-top: none; 
	border-right: none; 
	border-left: none; 
}
</style>
<script type="text/javascript">
	jQuery(function($){
		$("#data_vigencia").maskedinput("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript' >
	function listaFamilia(valor) {
	//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
		catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
			catch(ex) { try {ajax = new XMLHttpRequest();}
					catch(exc) {alert("Esse browser nao tem recursos para uso do Ajax"); ajax = null;}
			}
		}
	//se tiver suporte ajax

		if(ajax) {
			//deixa apenas o elemento 1 no option, os outros sÃ£o excluÃ­dos
			document.forms[0].familia.options.length = 1;
		
			//opcoes Ã© o nome do campo combo
			idOpcao  = document.getElementById("opcoes");

			ajax.open("GET", "ajax_linha.php?linha="+valor, true);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {
					idOpcao.innerHTML = "Carregando...!";
				}//enquanto estiver processando...emite a msg
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaCombo(ajax.responseXML);//apÃ³s ser processado-chama fun
					}else {
						idOpcao.innerHTML = "Selecione a linha";//caso nÃ£o seja um arquivo XML emite a mensagem abaixo
					}
				}
			}
		//passa o cÃ³digo do produto escolhido
		var params = "linha="+valor;
		ajax.send(null);
		}
	}
	
	function montaCombo(obj){
	
		var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
		if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			var item = dataArray[i];
			//contÃ©udo dos campos no arquivo XML
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione a família";
			//cria um novo option dinamicamente  
			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
			novo.value = codigo;		//atribui um valor
			novo.text  = nome;//atribui um texto
			document.forms[0].familia.options.add(novo);//adiciona o novo elemento
			}
		} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
		}
	}
</script>

<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxSpeed: 'fast'} );
		$('#container-1').tabs( { fxSpeed: 'fast'} );
		//fxAutoHeight: true,
	});

$(document).ready(
	function()
	{
		//$("#busca_por").focus(); // da erro qndo abre com outra TAB
		$("input.text, textarea.text").focusFields()
		//$('a.load-local').cluetip({local:true, cursor: 'pointer'});
	}
);
</script>

<? if (strlen($msg_erro)>0) {?>
<div class='error'>
	<? echo $msg_erro; ?>
</div>
<?}?>

<? if (strlen($ok)>0 OR strlen($msg)>0) {?>
<div class='ok'>
	<? echo $msg; ?>
</div>
<?}?>

<? if (strlen($peca)==0  && $btn_acao!='cadastrar' OR 1==1) { ?>

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Cadastro de Produto</td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab0Procurar"><span><img src='imagens/lupa.png' align=absmiddle> Busca</span></a></li>
						<li><a href="#tab1Procurar"><span><img src='imagens/lupa.png' align=absmiddle> Busca Avançada</span></a></li>
						<li><a href="#tab2Cadastrar"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle> Cadastro</span></a></li>
					</ul>
					<div id="tab0Procurar">
							<form name="frm_procura_simples" method="post" action="<? echo $PHP_SELF ?>">
							<table align='left' width='100%' border='0' class='tabela'>
									<tr>
										<td class='Label'>Busca por: </td>

									<td align='left' ><input class="Caixa" type="text" id="busca_por" name="busca_por" size="50" maxlength="250" value="<? echo $busca_por ?>" ></td>
									</tr>
									<tr>
										<td class='Label'>Campo: </td>
										<td colspan='5' align='left'>
											<br>
											<select class='Caixa' name='campo_pesquisa'>
												<option value='codigo' <? if ($campo_pesquisa=='codigo') echo "SELECTED"; ?>>Código</option>
												<option value='nome' <? if ($campo_pesquisa=='nome') echo "SELECTED";?>>Nome</option>
												<option value='linha' <? if ($campo_pesquisa=='linha') echo "SELECTED";?>>Linha</option>
												<option value='familia' <? if ($campo_pesquisa=='familia') echo "SELECTED";?>>Família</option>
												<option value='marca' <? if ($campo_pesquisa=='marca') echo "SELECTED";?>>Marca</option>
												<option value='modelo' <? if ($campo_pesquisa=='modelo') echo "SELECTED";?>>Modelo</option>
												<option value='caracteristicas' <? if ($campo_pesquisa=='caracteristicas') echo "SELECTED";?>>Características</option>
											</select>
										</td>
									</tr>
									<tr>
										<td colspan='6' align='center'>
											<br>
											<input name='btn_acao' type='hidden' value='pesquisar_simples'>
											<input name='pesquisar' type='submit' class='botao' value='Pesquisar'>
										</td>
									</tr>
							</table>
							</form>
					</div>
					<div id="tab1Procurar">
							<form name="frm_procura" method="post" action="<? echo $PHP_SELF ?>#tab1Procurar">
							<table align='left' width='100%' border='0' class='tabela'>
									<tr>
										<td class='Label' >Código</td>
										<td align='left' colspan='4' ><input class="Caixa" type="text" name="referencia" size="10" maxlength="10" value="<? echo $referencia ?>" ></td>

									</tr>
									<tr>
										<td class='Label'>Nome</td>
										<td colspan='4'><input class="Caixa" type="text" name="descricao" size="50" maxlength="50" value="<? echo $descricao ?>"></td>
									</tr>
									<tr>
										<td class='Label'>Linha</td>
										<td>
											<?
											##### INÍCIO LINHA #####
											$sql = "SELECT  *
													FROM    tbl_linha
													WHERE   tbl_linha.fabrica = $login_empresa
													ORDER BY tbl_linha.nome;";
											$res = pg_exec ($con,$sql);

											if (pg_numrows($res) > 0) {
												echo "<select class='Caixa' style='width: 280px;' name='linha' id='linha'>\n";
												echo "<option value=''></option>\n";

												for ($x = 0 ; $x < pg_numrows($res) ; $x++){
													$aux_linha = trim(pg_result($res,$x,linha));
													$aux_nome  = trim(pg_result($res,$x,nome));

													echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
												}
												echo "</select>\n";
											}
											?>
										</td>
									</tr>
									<tr>
										<td class='Label'>Marca</td>
										<td colspan='4'>
										<?
										##### INÍCIO LINHA #####
										$sql = "SELECT  *
												FROM    tbl_marca
												WHERE   tbl_marca.empresa = $login_empresa
												ORDER BY tbl_marca.nome;";
										$res = pg_exec ($con,$sql);

										if (pg_numrows($res) > 0) {
											echo "<select class='Caixa' style='width: 280px;' name='marca'>\n";
											echo "<option value=''></option>\n";

											for ($x = 0 ; $x < pg_numrows($res) ; $x++){
												$aux_marca = trim(pg_result($res,$x,marca));
												$aux_nome  = trim(pg_result($res,$x,nome));

												echo "<option value='$aux_marca'"; if ($marca == $aux_marca) echo " SELECTED "; echo ">$aux_nome</option>\n";
											}
											echo "</select>\n";
										}
										##### FIM LINHA #####
										?>
										</td>
									</tr>
									<tr>
										<td class='Label'>Modelo</td>
										<td colspan='4'>
										<?
										##### INÍCIO MODELO #####
										$sql = "SELECT  *
												FROM    tbl_modelo
												WHERE   tbl_modelo.fabrica = $login_empresa
												ORDER BY tbl_modelo.nome;";
										$res = pg_exec ($con,$sql);

										if (pg_numrows($res) > 0) {
											echo "<select class='Caixa' style='width: 280px;' name='modelo'>\n";
											echo "<option value=''></option>\n";

											for ($x = 0 ; $x < pg_numrows($res) ; $x++){
												$aux_modelo = trim(pg_result($res,$x,modelo));
												$aux_nome  = trim(pg_result($res,$x,nome));

												echo "<option value='$aux_modelo'"; if ($modelo == $aux_modelo) echo " SELECTED "; echo ">$aux_nome</option>\n";
											}
											echo "</select>\n";
										}
										##### FIM MODELO #####
										?>
										</td>
									</tr>
									<tr>
										<td colspan='5' align='center'>
											<br>
											<input name='btn_acao' type='hidden' value='pesquisar'>
											<input name='pesquisar' type='submit' class='botao' value='Pesquisar'>
										</td>
									</tr>
							</table>
							</form>
					</div>
					<div id="tab2Cadastrar">

						<form name="frm_cadastro" method="post" action="<? echo $PHP_SELF ?>#tab2Cadastrar" ENCTYPE="multipart/form-data">
						<input  type="hidden" name="peca" value="<? echo $peca ?>">

						<table style='background-color: #e6eef7' align='center' width='600' border='0' class='tabela'>
							<tr>
								<td class='Label' width='150px'>Código</td>
								<td align='left' width='450px' colspan='4'><input class="Caixa" type="text" name="referencia" size="10" maxlength="10"  value="<? echo $referencia ?>" ></td>
							</tr>
							<tr>
								<td class='Label'>Nome</td>
								<td colspan='4'><input class="Caixa" type="text" name="descricao" size="50" maxlength="50" value="<? echo $descricao ?>"></td>
							</tr>
							<tr>
								<td class='Label'>Nome Reduzido</td>
								<td colspan='4'><input class="Caixa" type="text" name="descricao_reduzida" size="40" maxlength="30" value="<? echo $descricao_reduzida ?>"
								rel='ajuda1' title='Nome reduzido do produto'> 
								
								<!--<span class='text_curto'>
								<a class="load-local" href="#loadme" rel="#loadme"> ? </a>
								<p id="loadme">Nome usado no Cupom Fiscal. Limite máximo de de 30 caracteres</p>
							-->
								</td>
							</tr>
							<tr>
								<td class='Label'>Linha</td>
								<td align='left' colspan='4'>
								<?
								##### INÍCIO LINHA #####
								$sql = "SELECT  *
										FROM    tbl_linha
										WHERE   tbl_linha.fabrica = $login_empresa
										ORDER BY tbl_linha.nome;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res) > 0) {
									echo "<select class='Caixa' style='width: 180px;' name='linha' id='linha' >\n";
									# onChange='listaFamilia(document.frm_cadastro.linha.value);'
									echo "<option value=''>ESCOLHA</option>\n";

									for ($x = 0 ; $x < pg_numrows($res) ; $x++){
										$aux_linha = trim(pg_result($res,$x,linha));
										$aux_nome  = trim(pg_result($res,$x,nome));

										echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
									}
									echo "</select>\n";
								}
								##### FIM LINHA #####
								?>
								</td>
							</tr>
							<tr>
								<td class='Label'>Família</td>
								<td align='left' colspan='4' >
										<?
										##### INÍCIO FAMILIA #####
										$sql = "SELECT  *
												FROM    tbl_familia
												WHERE   tbl_familia.fabrica = $login_empresa
												ORDER BY tbl_familia.descricao;";
										$res = pg_exec ($con,$sql);
										if (pg_numrows($res) > 0) {
											echo "<select class='Caixa' style='width: 180px;' name='familia' id='linha' >\n";
											echo "<option value=''>ESCOLHA</option>\n";

											for ($x = 0 ; $x < pg_numrows($res) ; $x++){
												$aux_familia    = trim(pg_result($res,$x,familia));
												$aux_descricao  = trim(pg_result($res,$x,descricao));

												echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
											}
											echo "</select>\n";
										}
										##### FIM FAMILIA #####
										?>
								</td>
							</tr>
							<tr>
								<td class='Label'>Marca</td>
								<td colspan='4'>
								<?
								##### INÍCIO LINHA #####
								$sql = "SELECT  *
										FROM    tbl_marca
										WHERE   tbl_marca.empresa = $login_empresa
										ORDER BY tbl_marca.nome;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res) > 0) {
									echo "<select class='Caixa' style='width: 180px;' name='marca' onChange=\"
									
									javascript:
									if ( this.value== -1 ) {
										document.getElementById('nova_marca').style.display='inline';
									}else{
										document.getElementById('nova_marca').style.display='none';
									}
									
									\"
									>\n";
									echo "<option value=''>ESCOLHA</option>\n";

									for ($x = 0 ; $x < pg_numrows($res) ; $x++){
										$aux_marca = trim(pg_result($res,$x,marca));
										$aux_nome  = trim(pg_result($res,$x,nome));

										echo "<option value='$aux_marca'"; if ($marca == $aux_marca) echo " SELECTED "; echo ">$aux_nome</option>\n";
									}
									echo "<option value='-1' style='color:blue'>* NOVA MARCA *</option>\n";
									echo "</select>\n";
								}
								##### FIM LINHA #####
								
								# Nova Marca
								?>
								<span id='nova_marca' style='display:none'>
									&nbsp;&nbsp;&nbsp;&nbsp;<img src='imagens/star.gif' align='absmiddle'> Nova Marca: 
									<input class="Caixa" type="text" name="nova_marca" size="20" maxlength="30" value="<? echo $nome_marca ?>">
								</span>
								</td>
							</tr>
							<tr>
								<td class='Label'>Modelo</td>
								<td colspan='4'>
								<?
								##### INÍCIO LINHA #####
								$sql = "SELECT  *
										FROM    tbl_modelo
										WHERE   tbl_modelo.fabrica = $login_empresa
										ORDER BY tbl_modelo.nome;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res) > 0) {
									echo "<select class='Caixa' style='width: 180px;' name='modelo' onChange=\"
									
									javascript:
									if ( this.value== -1 ) {
										document.getElementById('novo_modelo').style.display='inline';
									}else{
										document.getElementById('novo_modelo').style.display='none';
									}
									
									\"
									>>\n";
									echo "<option value=''>ESCOLHA</option>\n";

									for ($x = 0 ; $x < pg_numrows($res) ; $x++){
										$aux_modelo = trim(pg_result($res,$x,modelo));
										$aux_nome  = trim(pg_result($res,$x,nome));

										echo "<option value='$aux_modelo'"; if ($modelo == $aux_modelo) echo " SELECTED "; echo ">$aux_nome</option>\n";
									}
									echo "<option value='-1' style='color:blue'>* NOVO MODELO *</option>\n";
									echo "</select>\n";
								}
								##### FIM LINHA #####
								?>
								<span id='novo_modelo' style='display:none'>
									&nbsp;&nbsp;&nbsp;&nbsp;<img src='imagens/star.gif' align='absmiddle'> Novo Modelo: 
									<input class="Caixa" type="text" name="novo_modelo" size="20" maxlength="30" value="<? echo $novo_modelo ?>">
								</span>
								</td>
							</tr>
							<tr>
								<td class='Label'>Origem</td>
								<td colspan='4'>
								<select name="origem" class="Caixa">
									<option value="">ESCOLHA</option>
									<option value="NAC" <? if ($origem == "NAC") echo " SELECTED "; ?>>Nacional</option>
									<option value="IMP" <? if ($origem == "IMP") echo " SELECTED "; ?>>Importado</option>
								</select>
								</td>
							</tr>

					<!-- 		<tr>
								<td class='Label'>ICMS</td>
								<td colspan='4'>
								<?
								$sql = "SELECT  *
										FROM    tbl_icms_produto
										ORDER BY descricao;";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res) > 0) {
									echo "<select class='Caixa' style='width: 280px;' name='icms_produto'>\n";
									echo "<option value=''></option>\n";

									for ($x = 0 ; $x < pg_numrows($res) ; $x++){
										$aux_icms_produto = trim(pg_result($res,$x,icms_produto));
										$aux_descricao    = trim(pg_result($res,$x,descricao));

										echo "<option value='$aux_icms_produto'"; if ($icms_produto == $aux_icms_produto) echo " SELECTED "; echo ">$aux_descricao</option>\n";
									}
									echo "</select>\n";
								}

								?>
								</td>
							</tr>
					 -->
					<!--	
							<tr>
								<td class='Label'>Unidade</td>
								<td colspan='4'><input class="Caixa" type="text" name="unidade" size="10" maxlength="10" value="<? echo $unidade ?>"></td>
							</tr>
					-->        

							<tr>
								<td class='Label'>Estoque mínimo</td>
								<td><input class="Caixa" type="text" name="estoque_minimo"  align='right' size="10" style='text-align:right' maxlength="10" value="<? echo $estoque_minimo ?>" onblur="javascript:checarNumeroInteiro(this);"></td>
							</tr>

							<tr>
								<td class='Label'>Valor de Venda</td>
								<td colspan='4'><input class="Caixa" type="text" name="valor_venda" size="10"  style='text-align:right' maxlength="10" value="<? echo $valor_venda ?>" onblur="javascript:checarNumero(this);"></td>
							</tr>
							<tr>
								<td class='Label'>Ativo</td>
								<td colspan='4'><input class="Caixa" type="checkbox" name="ativo" value='t' <? if ($ativo=='t' || strlen($peca)==0)echo "CHECKED"; ?>></td>
							</tr>
							<tr>
								<td class='Label'>Controle N° Série</td>
								<td colspan='4'><input class="Caixa" type="checkbox" name="controle_numero_serie" value='t' <? if ($controle_numero_serie=='t' || strlen($peca)==0)echo "CHECKED"; ?>></td>
							</tr>
							<tr>
								<td class='Label'>N° Série Obrigatório</td>
								<td colspan='4'><input class="Caixa" type="checkbox" name="numero_serie_obrigatorio" value='t' <? if ($numero_serie_obrigatorio=='t' || strlen($peca)==0)echo "CHECKED"; ?>></td>
							</tr>
							<tr>
								<td class='Label'>Garantia (em meses)</td>
								<td><input class="Caixa" type="text" name="garantia" size="10" maxlength="10" value="<? echo $garantia ?>" onblur="javascript:checarNumeroInteiro(this);"></td>
							</tr>
							<!-- INICIO DA TAB -->
							<tr>
								<td class='Label' colspan="5">
								<br>
									<div id="container-1">
										<ul>
											<li><a href="#tab1"><span><img src='imagens/picture.png' align=absmiddle> Fotos</span></a></li>
											<li><a href="#tab2"><span><img src='imagens/document-blue.png' align=absmiddle> Caracteristicas</span></a></li>
											<!--<li><a href="#tab3"><span><img src='imagens/refresh.png' align=absmiddle> Dimenções</span></a></li>
											<li><a href="#tab4"><span><img src='imagens/lupa.png' align=absmiddle> Localização</span></a></li>-->
											<li><a href="#tab3"><span><img src='imagens/info.png' align=absmiddle> Info. Diversas</span></a></li>
											<li><a href="#tab4"><span><img src='imagens/briefcase.png' align=absmiddle> Tabela Preço</span></a></li>
											<li><a href="#tab5"><span><img src='imagens/briefcase.png' align=absmiddle> Código Barra</span></a></li>
										</ul>
										<div id="tab1">
											<p style='font-size:10px'>
											<?
											$num_fotos = count($fotos);
											if ($num_fotos>0){
												foreach($fotos as $foto) {
													$cam_foto   = $foto[0];
													$cam_foto_t = $foto[1];
													$desc_foto  = $foto[2];
													$foto_id    = $foto[3];

													//echo " <div class='contenedorfoto'><a href='?peca=$peca&excluir_foto=$foto_id'><img src='imagens/cancel.png' width='12px' alt='Excluir foto' style='margin-right:0;float:right;align:right' /></a><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='gallery-plants'><img src='$cam_foto_t' alt='$desc_foto' /><br /><span>$desc_foto</span></a></div>"; 
													echo " <div class='contenedorfoto'><a href='$cam_foto' title='$desc_foto' class='thickbox' rel='gallery-plants'><img src='$cam_foto_t' alt='$desc_foto' /><br /></a><a href=\"javascript:if (confirm('Deseja excluir esta foto?')) window.location='?peca=$peca&excluir_foto=$foto_id'\"><img src='imagens/cancel.png' width='12px' alt='Excluir foto' style='margin-right:0;float:right;align:right' /></a></div>"; 
												}
											}else{
												echo "Nenhuma foto cadastrada para este produto.";
											}
											?>
											</p>
											<br clear='both'>
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND>Inserir Fotos</LEGEND>
											<p style='font-size:10px'>
											<?php
												$fotos_restantes = 6-$num_fotos;
												if ($fotos_restantes>0) echo "Quantidade de fotos que podem se inseridas: $fotos_restantes";
												else                    echo "Limite máximo de fotos atingido. Exclua alguma foto para colocar mais.";
												if ($fotos_restantes>0){
													echo '<br><br>Fotos: <input type="file" value="Procurar foto" name="arquivos[]" class="multi {accept:\'jpg|gif|png\', max:'.$fotos_restantes.', STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}" />';
												}
											?>
											</FIELDSET>
											</p>
										</div>
										<div id="tab2">
												<table border="0">
														<tr>
															<td class='Label' valign='top'>Caracteristica técnica</td>
															<td colspan='4'><textarea class="Caixa" name="caracteristica_tecnica" rows='3' cols='80'><? echo $caracteristica_tecnica ?></textarea></td>
														</tr>
														<tr>
															<td class='Label' valign='top'>Pré-requisitos</td>
															<td colspan='4'><textarea class="Caixa" name="compatibilidade" rows='3' cols='80'><? echo $compatibilidade ?></textarea></td>
														</tr>
														<tr>
															<td class='Label' valign='top'>Acessórios</td>
															<td colspan='4'><textarea class="Caixa" name="acessorios" rows='3' cols='80'><? echo $acessorios ?></textarea></td>
														</tr>
												</table>

										</div>
										<div id="tab3">
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Dados Cadastrais</strong></LEGEND>
												<table border="0">                                          
												<tr>
													<td class='Label'>Data do Cadastro</td>
													<td colspan='4' class='Label'><b><? echo $data_cadastro ?></b></td>
													
												</tr>
												<tr>
													<td class='Label'>Usuário</td>
													<td colspan='4' class='Label'><b>
													<? if (strlen($empregado_login)>0)
															echo "$empregado ($empregado_login)";
												
													?></b></td>
													
												</tr>
												<tr height='3'>
													<td  colspan='4'>&nbsp;</td>
												</tr>
												<tr>
													<td class='Label'>Última Alteração</td>
													<td colspan='4' class='Label'><b>
													<? echo $data_alteracao ?></b></td>
												</tr>
												<tr>
													<td class='Label'>Usuário</td>
													<td colspan='4' class='Label'><b>
													<?
													if (strlen($ultimo_empregado_login)>0)
														echo "$ultimo_empregado ($ultimo_empregado_login)"
													?></b></td>
												</tr>
												</table>
											</FIELDSET>
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Dimensões</strong></LEGEND>
													<table border="0" >
														<tr>
															<td class='Label' style='border-bottom:1px solid #9F9F9F;' colspan='2'>Produto</td>
															<td width='50px'></td>
															<td class='Label' style='border-bottom:1px solid #9F9F9F;' colspan='2'>Produto Embalado</td>
														</tr>
														<tr>
															<td class='Label'>Altura</td>
															<td class='Label'><input class="Caixa" type="text" name="altura" size="10" maxlength="10" value="<? echo $altura ?>" onblur="javascript:checarNumero(this);"> mm</td>
															<td></td>
															<td class='Label'>Altura</td>
															<td class='Label'><input class="Caixa" type="text" name="embalado_altura" size="10" maxlength="10" value="<? echo $altura ?>" onblur="javascript:checarNumero(this);"> mm</td>
														</tr>
														<tr>
															<td class='Label'>Largura</td>
															<td class='Label'><input class="Caixa" type="text" name="largura" size="10" maxlength="10" value="<? echo $largura ?>" onblur="javascript:checarNumero(this);"> mm</td>
															<td></td>
															<td class='Label'>Largura</td>
															<td class='Label'><input class="Caixa" type="text" name="embalado_largura" size="10" maxlength="10" value="<? echo $largura ?>" onblur="javascript:checarNumero(this);"> mm</td>
														</tr>
														<tr>
															<td class='Label'>Comprimento</td>
															<td class='Label'><input class="Caixa" type="text" name="comprimento" size="10" maxlength="10" value="<? echo $comprimento ?>" onblur="javascript:checarNumero(this);"> mm</td>
															<td></td>
															<td class='Label'>Comprimento</td>
															<td class='Label'><input class="Caixa" type="text" name="embalado_comprimento" size="10" maxlength="10" value="<? echo $comprimento ?>" onblur="javascript:checarNumero(this);"> mm</td>
														</tr>
														<tr>
															<td class='Label'>Peso</td>
															<td class='Label'><input class="Caixa" type="text" name="peso" size="10" maxlength="10" value="<? echo $peso ?>" onblur="javascript:checarNumero(this);"></td>
															<td></td>
															<td class='Label'>Peso</td>
															<td class='Label'><input class="Caixa" type="text" name="embalado_peso" size="10" maxlength="10" value="<? echo $peso ?>" onblur="javascript:checarNumero(this);"></td>
														</tr>
													</table>
											</fieldset>
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Localização</strong></LEGEND>
												<table border="0">                                          
												<tr>
													<td class='Label'>Corredor</td>
													<td colspan='4' class='Label'><input class="Caixa" type="text" name="localizacao_corredor" size="10" maxlength="10" value="<? echo $localizacao_corredor ?>" ></td>
													
												</tr>
												<tr>
													<td class='Label'>Prateleira</td>
													<td colspan='4' class='Label'><input class="Caixa" type="text" name="localizacao_prateleira" size="10" maxlength="10" value="<? echo $localizacao_prateleira ?>" ></td>
													
												</tr>
												<tr>
													<td class='Label'>Gaveta</td>
													<td colspan='4' class='Label'><input class="Caixa" type="text" name="localizacao_gaveta" size="10" maxlength="10" value="<? echo $localizacao_gaveta ?>" ></td>
												</tr>
												<tr>
													<td class='Label'>Banjeda</td>
													<td colspan='4' class='Label'><input class="Caixa" type="text" name="localizacao_bandeja" size="10" maxlength="10" value="<? echo $localizacao_bandeja ?>" ></td>
												</tr>
												</table>
											</fieldset>
										</div>
										<div id="tab4">
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Percentuais Sobre o Preço</strong></LEGEND>
											<table border="0"  cellpadding="2" cellspacing="1"  width='80%'>
												<tr>
													<td class='Label'>Percentual Administrativo</td>
													<td class='Label' align='left' colspan='4'>
														<input class="CaixaValor" type="text" name="percentual_administrativos"   size="2" maxlength="5" value="<? echo $percentual_administrativos ?>" onblur="javascript:checarNumero(this)">%</td>
												</tr>
												<tr>
													<td class='Label'>Percentual da Comissão</td>
													<td class='Label' align='left' colspan='4'>
														<input class="CaixaValor" type="text" name="percentual_comissao"   size="2" maxlength="5" value="<? echo $percentual_comissao ?>" onblur="javascript:checarNumero(this)">%</td>
												</tr>
												<tr>
													<td class='Label' nowrap>Percentual do Marketing</td>
													<td class='Label' align='left' colspan='4'>
														<input class="CaixaValor" type="text" name="percentual_marketing"   size="2" maxlength="5" value="<? echo $percentual_marketing ?>"  onblur="javascript:checarNumero(this)">%</td>
												</tr>
												<tr>
													<td class='Label' nowrap>Percentual do Lucro</td>
													<td class='Label' align='left' colspan='4'>
														<input class="CaixaValor" type="text" name="percentual_lucro"   size="2" maxlength="5" value="<? echo $percentual_lucro ?>"  onblur="javascript:checarNumero(this)">%</td>
												</tr>
												<tr>
													<td class='Label' nowrap>Percentual do Venda</td>
													<td class='Label' align='left' colspan='4'>
														<input class="CaixaValor" type="text" name="percentual_vendas"   size="2" maxlength="5" value="<? echo $percentual_vendas ?>"  onblur="javascript:checarNumero(this)">%</td>
												</tr>
												<tr>
													<td class='Label' nowrap>Percentual de Perdas</td>
													<td class='Label' align='left' colspan='4'>
														<input class="CaixaValor" type="text" name="percentual_perdas"   size="2" maxlength="5" value="<? echo $percentual_perdas ?>"  onblur="javascript:checarNumero(this)">%</td>
												</tr>
											</table>
											</FIELDSET>



											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Inserir Novo Preço</strong></LEGEND>
											<table border="0"  cellpadding="2" cellspacing="1"  width='95%'>
											<tr>
												<td class='Label'>Tabela</td>
												<td class='Label'>
												
												<select name="tabela_preco" id="tabela_preco" class="Caixa">
												<?	$sql = "SELECT	tabela      ,
																	sigla_tabela,
																	descricao
															FROM  tbl_tabela
															WHERE fabrica = $login_empresa
															AND   ativa is true
															ORDER by descricao";
													$res = pg_exec($con,$sql);
													if(pg_numrows($res)>0){
														for($i=0;$i<pg_numrows($res);$i++){
															$tabela       = pg_result($res,$i,tabela);
															$sigla_tabela = pg_result($res,$i,sigla_tabela);
															$tabela_nome  = pg_result($res,$i,descricao);
															echo "<option value='$tabela' ";
																if ($tabela == $tabela_preco) echo " SELECTED ";
															echo ">$sigla_tabela $tabela_nome</option>";
														}
													}else{
														echo "<option value=''>Nenhum resultado encontrado</option>";
													}
												?>
												</select>
												</td>
												<td class='Label'>Data Vigência</td>
												<td class='Label'>
												<input class="CaixaValor" type="text" id="data_vigencia" name="data_vigencia" size="10" maxlength="10" value="<? echo date("d/m/Y"); ?>" >
												</td>
												<td class='Label'>Valor R$</td>
												<td class='Label'>
												<input class="CaixaValor" type="text" id="valor_peca" name="valor_peca" size="10" maxlength="10" onblur="javascript:checarNumero(this);">
												</td>
												<td class='Label'>
												<input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adicionaPreco()'>
												</td>
											</tr>
											</table>
											<BR>
											<table width='80%' id='tbl_preco' cellspacing='0' cellpadding='0'>
											<thead>
												<tr>
													<td class='Titulo_Tabela_Menor' width='50%'>Tabela</td>
													<td class='Titulo_Tabela_Menor' width='30%'>Data</td>
													<td class='Titulo_Tabela_Menor' width='20%'>Valor</td>
												</tr>
											</thead>
											<tbody>
											</tbody>
											</table>
											</FIELDSET>
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Preço(s) Anterior(es)</strong></LEGEND>
											<table border="1"  cellpadding="2" cellspacing="1">
											<?
											$sql = "SELECT	distinct tbl_tabela.tabela       ,
															tbl_tabela.sigla_tabela          ,
															tbl_tabela.fabrica               ,
															tbl_tabela.descricao
													FROM tbl_tabela
													JOIN tbl_tabela_item_erp on tbl_tabela_item_erp.tabela = tbl_tabela.tabela
													WHERE tbl_tabela.fabrica = $login_empresa
													order by tbl_tabela.descricao desc";
											$res = pg_exec($con,$sql);
											if(pg_numrows($res)>0){
												echo "<tr>";
												echo "<td class='Label'><B>Tabela</b></td>";
												echo "<td class='Label' colspan='4'><B>Últimas datas vigentes cadastradas e valores para este produto</b></td>";
												echo "</tr>";
												for($i=0;pg_numrows($res)>$i;$i++){
													$nome_tabela  = pg_result($res,$i,descricao);
													$tabela       = pg_result($res,$i,tabela);
													$sigla_tabela = pg_result($res,$i,sigla_tabela);
												echo "<tr>";
												echo "<td class='Label'>$nome_tabela</td>";
												if(strlen($peca)>0){
												$wsql = "SELECT to_char(data_vigencia,'DD/MM/YYYY') AS data_vigencia,
																preco                , 
																peca                 , 
																tabela
														FROM tbl_tabela_item_erp
														WHERE tabela = $tabela
															AND peca = $peca
														ORDER BY tabela_item_erp DESC, 
															data_vigencia DESC LIMIT 2;";
												$wres = pg_exec($con,$wsql);
												//echo $wsql;
												if(pg_numrows($wres)>0){
													for($w=0;pg_numrows($wres)>$w;$w++){
														$wdata_vigencia = pg_result($wres,$w,data_vigencia);
														$wpreco         = pg_result($wres,$w,preco);
														$wpeca          = pg_result($wres,$w,peca);
														$wtabela        = pg_result($wres,$w,tabela);
														
														if ($w % 2 == 0) $cor = '#FFFFFF';
														else             $cor = '#D8DDDE';

														echo "<td align='center' class='Label' bgcolor='$cor'>$wdata_vigencia</td>";
														echo "<td align='center' class='Label' bgcolor='$cor'>R$ $wpreco</td>";
													}
												}
												
													

												echo "</tr>";
												}
											}
											}
											?>
											</table>

											</FIELDSET>
										</div>
										<div id="tab5">

											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Inserir Novo Código de Barra ou Referências</strong></LEGEND>
											<table border="0"  cellpadding="2" cellspacing="1"  width='95%'>
											<tr>
												<td class='Label'>Código de Barra</td>
												<td class='Label'>
												<input class="CaixaValor" type="text" id="codigo_barra" name="codigo_barra" size="10" maxlength="10" value="" >
												</td>
												<td class='Label'>
												<input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adicionaCodigoBarra()'>
												</td>
											</tr>
											</table>
											<BR>
											<table width='80%' id='tbl_codigo_barra' cellspacing='0' cellpadding='0'>
											<thead>
												<tr>
													<td class='Titulo_Tabela_Menor' width='80%' align='center'>Código de Barra</td>
												</tr>
											</thead>
											<tbody>
											</tbody>
											</table>
											</FIELDSET>
											<FIELDSET style='padding:10px;font-size:10px'>
											<LEGEND><strong>Códigos de Barra Cadastrados</strong></LEGEND>
											<table border="1"  cellpadding="2" cellspacing="1">
											<?
											
											$sql = "SELECT	
														tbl_peca_item_codigo_barra.peca_item_codigo_barra ,
														tbl_peca_item_codigo_barra.codigo_barra,
														tbl_peca_item_codigo_barra.peca,
														tbl_peca.descricao
													FROM tbl_peca_item_codigo_barra
													JOIN tbl_peca on tbl_peca.peca = tbl_peca_item_codigo_barra.peca
													WHERE tbl_peca_item_codigo_barra.peca = $peca
													order by tbl_peca.descricao desc";
											$res = @pg_exec($con,$sql);
											if(@pg_numrows($res)>0){
												echo "<tr>";
												echo "<td class='Label'><B>Código de Barra</b></td>";
												echo "<td class='Label' colspan='4'><B>Produto</b></td>";
												echo "</tr>";
												for($i=0;pg_numrows($res)>$i;$i++){
													$codigo_barra= pg_result($res,$i,codigo_barra);
													$descricao= pg_result($res,$i,descricao);
													echo "<tr>";
													echo "<td class='Label'>$codigo_barra</td>";
													echo "<td align='center' class='Label' >$descricao</td>";
													echo "</tr>";
												}
											}
											?>
											</table>

											</FIELDSET>
										</div>
									</div>
								</td>
							</tr>
						<!-- FIM DA TAB -->

							<tr>
								<td class='Label' colspan='5' align='center'>
									<br>
									<?
										if (strlen($peca)>0) $btn_msg="Gravar Alterações";
										else                 $btn_msg="Gravar";
									?>
									<input class="botao" type="hidden" name="btn_acao"  value=''>
					<!--
									<a class="button" href="#" onclick="this.blur();"><span>Gravar (teste)</span></a>
					-->
									<input class="botao" type="button" name="bt"        value='<? echo $btn_msg ?>' onclick="javascript:if (this.form.btn_acao.value!='') alert('Aguarde Submissão'); else{this.form.btn_acao.value='Gravar';this.form.submit();}">
									<input class="botao" type="button" name="btn_cancelar" onclick='javascript:window.location="cadastro_produto.php"'  value='Cancelar' >
									<input class="botao" type="button" name="btn_limpar" onclick='limpar_form(this.form)'  value='Limpar' >
									<?
										if ($btn_acao=='Gravar'){
											echo "<input class='botao' type='button' name='btn_voltar' onclick=\"javascript:window.location='cadastro_produto.php'\"  value='Voltar ao Menu Produtos' >";
										}
									?>
								</td>
							</tr>
						</table>
						</form>
					</div>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'></td>
		</tr>
</table>
<? } ?>


<?

	
if ($btn_acao=='pesquisar' OR $btn_acao=='pesquisar_simples'){
	if(strlen ($msg_erro) == 0){
		$sql = "SELECT  tbl_peca.peca      ,
				tbl_peca.referencia,
				tbl_peca.descricao ,
				tbl_peca.origem    ,
				tbl_peca.estoque   ,
				tbl_peca.unidade   ,
				tbl_peca.ativo     ,
				tbl_peca_item.*    ,
				tbl_familia.descricao as familia_descricao,
				tbl_linha.nome        as linha_nome       ,
				tbl_marca.nome        as marca_nome       ,
				tbl_modelo.nome       as modelo_nome      
			FROM tbl_peca
			JOIN tbl_peca_item   USING(peca)
			JOIN tbl_familia   ON tbl_familia.familia = tbl_peca_item.familia
			JOIN tbl_linha     ON tbl_linha.linha     = tbl_peca_item.linha
			LEFT JOIN tbl_marca  ON tbl_peca_item.marca=tbl_marca.marca
			LEFT JOIN tbl_modelo ON tbl_peca_item.modelo=tbl_modelo.modelo
			WHERE tbl_peca.fabrica = $login_empresa
			$sql_adicional
			ORDER BY tbl_linha.nome        ,
					 tbl_familia.descricao ,
					 tbl_peca.descricao    ,
					 tbl_marca.nome        ,
					 tbl_modelo.nome       ASC";

		$res = pg_exec ($con,$sql) ;
//echo $sql;
		if (@pg_numrows($res) > 0) {

			echo "<br>";
			echo "<input type='hidden' name='qtde_item' value='$qtde_item'>";
			echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0' class='tabela'>";
			echo "<caption>";
			echo "Produtos";
			echo "</caption>";
			echo "<tr height='20' bgcolor='#7392BF'>";
			echo "<td align='center' class='Titulo_Tabela'><b>Linha</b></td>";
			echo "<td align='center' class='Titulo_Tabela'><b>Familia</b></td>";
			echo "<td align='center' class='Titulo_Tabela'><b>Referência</b></td>";
			echo "<td align='center' nowrap class='Titulo_Tabela'><b>Descrição</b></td>";
			echo "<td align='center' class='Titulo_Tabela'><b>Marca</b></td>";
			echo "<td align='center' class='Titulo_Tabela'><b>Modelo</b></td>";
			echo "<td align='center' class='Titulo_Tabela'><b>Ação</b></td>";
			echo "</tr>";	
			

			for ($k = 0; $k <pg_numrows($res) ; $k++) {
				
				$peca               = trim(pg_result($res,$k,peca));
				$referencia         = trim(pg_result($res,$k,referencia));
				$descricao          = trim(pg_result($res,$k,descricao));
				$familia_descricao  = trim(pg_result($res,$k,familia_descricao));
				$linha_nome         = trim(pg_result($res,$k,linha_nome));
				$marca_nome         = trim(pg_result($res,$k,marca_nome));
				$modelo_nome        = trim(pg_result($res,$k,modelo_nome));

				if($k%2==0)$cor = '#ECF3FF';
				else               $cor = '#FFFFFF';
				
				$sqlpro="SELECT tbl_pedido_item.pedido_item
							FROM tbl_pedido_item
							JOIN ( SELECT peca
									FROM tbl_peca_item
									WHERE linha = 447
									AND familia = 767) t_peca_item ON tbl_pedido_item.peca = t_peca_item.peca
							JOIN tbl_peca ON tbl_peca.peca = t_peca_item.peca
							WHERE tbl_peca.peca=$peca";
				$respro=pg_exec($con,$sqlpro);
				if(pg_numrows($respro) > 0) {
					$cor='#FFB0B0';
				}

				echo "<tr bgcolor='$cor' class='linha'>";
				echo "<td align='center'>$linha_nome</td>";
				echo "<td align='center'>$familia_descricao</td>";
				echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&peca=$peca#tab2Cadastrar'>$referencia</a></td>";
				echo "<td align='left'  >$descricao</td>";
				echo "<td align='center'>$marca_nome</td>";
				echo "<td align='center'>$modelo_nome</td>";
				echo "<td align='center'><a href='$PHP_SELF?btn_acao=alterar&peca=$peca#tab2Cadastrar'><img src='imagens/pencil.png'></a>";
				echo "</td>";

				echo "</tr>";

			}
			echo "</table>";
		}else{
			echo "<br><p>Nenhuma produto encontrado</p>";
		}
	}
}
?>


<?
 include "rodape.php";
 ?>
