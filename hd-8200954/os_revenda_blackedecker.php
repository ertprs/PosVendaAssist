<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'helpdesk/mlg_funciones.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';

$limite_anexos_nf = 5;
include_once('anexaNF_inc.php');
if($login_fabrica == 1){
    require "classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "classes/form/GeraComboType.php";
}

$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);

$msg_erro = "";
//HD 9013
if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if(strlen($os_revenda)>0){
	$sql="SELECT count(*) as qtde_item from tbl_os_revenda_item where os_revenda=$os_revenda";
	$res=pg_exec($con,$sql);
	$qtde_item=pg_result($res,0,qtde_item);
}

if (strlen($_POST['qtde_item']) > 0) $qtde_item = $_POST['qtde_item'];

if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower($_POST['btn_acao']));


/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda_item USING tbl_os_revenda
				WHERE tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				AND   tbl_os_revenda_item.os_revenda = $os_revenda
				AND   explodida ISNULL;

				DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica
				AND    tbl_os_revenda.posto      = $login_posto";
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if(strpos($msg_erro,"still referenced")) {
			$msg_erro = "OS já explodida, não pode ser apagada";
		}

		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btn_acao == "gravar")
{

 	extract($_POST);

	if (strlen($sua_os) > 0){
		$xsua_os = $sua_os;
		$xsua_os = "00000" . trim ($xsua_os);
		$xsua_os = substr ($xsua_os, strlen ($xsua_os) - 5 , 5) ;
		$xsua_os = "'". $xsua_os ."'";
	}else{
		$xsua_os = "null";
	}

	if(empty($data_nf)) {
		$msg_erro = "Favor preencher a data da nota fiscal.";
	}

	$xdata_abertura = fnc_formata_data_pg($data_abertura);
	$xdata_nf       = fnc_formata_data_pg($data_nf);

	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	}else{
		$nota_fiscal = trim ($nota_fiscal);
		$nota_fiscal = str_replace (".","",$nota_fiscal);
		$nota_fiscal = str_replace (" ","",$nota_fiscal);
		$nota_fiscal = str_replace ("-","",$nota_fiscal);
		#$nota_fiscal = "000000" . $nota_fiscal;
		#$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-6,6);
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;
	}

	if (strlen($revenda_cnpj) > 0) {
		$revenda_cnpj  = str_replace (".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace (" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
		if($revenda_cnpj=='53296273000191'){
			$msg_erro="<u>A BLACK & DECKER É O FABRICANTE E VENDE OS PRODUTOS PARA AS REVENDAS CREDENCIADAS, PORÉM ESSA OS É PARA O LANÇAMENTO DE PRODUTOS DE ESTOQUE DA REVENDA. NESSE CASO, É NECESSÁRIO INFORMAR O CNPJ E NOME DA REVENDA.</u><br>";
		}
	}else{
		$xrevenda_cnpj = "null";
	}


	if ($xrevenda_cnpj <> "null") {
		$sql =	"SELECT *
				FROM    tbl_revenda
				WHERE   cnpj = $xrevenda_cnpj";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0){
			$msg_erro = "CNPJ da revenda não cadastrado";
		}else{
			$revenda		= trim(pg_result($res,0,revenda));
			$nome			= trim(pg_result($res,0,nome));
			$endereco		= trim(pg_result($res,0,endereco));
			$numero			= trim(pg_result($res,0,numero));
			$complemento	= trim(pg_result($res,0,complemento));
			$bairro			= trim(pg_result($res,0,bairro));
			$cep			= trim(pg_result($res,0,cep));
			$cidade			= trim(pg_result($res,0,cidade));
			$fone			= trim(pg_result($res,0,fone));
			$cnpj			= trim(pg_result($res,0,cnpj));

			if (strlen($revenda) > 0)
				$xrevenda = "'". $revenda ."'";
			else
				$xrevenda = "null";

			if (strlen($nome) > 0)
				$xnome = "'". $nome ."'";
			else
				$xnome = "null";

			if (strlen($endereco) > 0)
				$xendereco = "'". $endereco ."'";
			else
				$xendereco = "null";

			if (strlen($numero) > 0)
				$xnumero = "'". $numero ."'";
			else
				$xnumero = "null";

			if (strlen($complemento) > 0)
				$xcomplemento = "'". $complemento ."'";
			else
				$xcomplemento = "null";

			if (strlen($bairro) > 0)
				$xbairro = "'". $bairro ."'";
			else
				$xbairro = "null";

			if (strlen($cidade) > 0)
				$xcidade = "'". $cidade ."'";
			else
				$xcidade = "null";

			if (strlen($cep) > 0)
				$xcep = "'". $cep ."'";
			else
				$xcep = "null";

			if (strlen($fone) > 0)
				$xfone = "'". $fone ."'";
			else
				$xfone = "null";
			if (strlen($cnpj) > 0)
				$xcnpj = "'". $cnpj ."'";
			else
				$xcnpj = "null";

			$sql = "SELECT cliente
					FROM   tbl_cliente
					WHERE  cpf = $xrevenda_cnpj";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) == 0 and !empty($cidade)){
				// insere dados
				$sql = "INSERT INTO tbl_cliente (
							nome       ,
							endereco   ,
							numero     ,
							complemento,
							bairro     ,
							cep        ,
							cidade     ,
							fone       ,
							cpf
						)VALUES(
							$xnome       ,
							$xendereco   ,
							$xnumero     ,
							$xcomplemento,
							$xbairro     ,
							$xcep        ,
							$xcidade     ,
							$xfone       ,
							$xcnpj
						)";
				// pega valor de cliente

				$res     = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) == 0 and strlen($cliente) == 0) {
					$res     = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) == 0) $cliente = pg_result ($res,0,0);
				}

			}else{
				// pega valor de cliente
				$cliente = pg_result($res,0,cliente);
			}
		}
	}else{
		$msg_erro = "CNPJ não informado";
	}

	if (strlen($revenda_fone) > 0) {
		$xrevenda_fone = "'".$revenda_fone ."'";
	}else{
		$xrevenda_fone = "null";
	}
	if($xrevenda_fone == "null"){$msg_erro .="Insira o telefone da revenda.<BR>";}

	if (strlen($revenda_email) > 0) {
		$xrevenda_email = "'". $revenda_email ."'";
	}else{
		$xrevenda_email = "null";
	}

	if (strlen($obs) > 0) {
		$xobs = "'". $obs ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($contrato) > 0) {
		$xcontrato = "'". $contrato ."'";
	}else{
		$xcontrato = "'f'";
	}

	$consumidor_email = trim ($consumidor_email);
	// HD 18051
	// HD 3334608
	if(strlen($consumidor_email) == 0 ){
		$msg_erro .='E-mail de contato obrigatório.<br> 
					Caso não possuir endereço eletrônico, deverá ser informado o e-mail: "nt@nt.com.br". <br>';
	}else{
		/*if(!filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {*/
		if (!is_email($consumidor_email)){
			$msg_erro .='E-mail de contato obrigatório.<br> 
						Caso não possuir endereço eletrônico, deverá ser informado o e-mail: "nt@nt.com.br". <br>';
		}else{
			$consumidor_email = trim($_POST['consumidor_email']);
		}
	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($os_revenda) == 0) {
			#-------------- insere ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica                          ,
						sua_os                           ,
						data_abertura                    ,
						data_nf                          ,
						nota_fiscal                      ,
						cliente                          ,
						revenda                          ,
						obs                              ,
						digitacao                        ,
						posto                            ,
						contrato                         ,
						consumidor_email
					) VALUES (
						$login_fabrica                   ,
						$xsua_os                         ,
						$xdata_abertura                  ,
						$xdata_nf                        ,
						$xnota_fiscal                    ,
						$cliente                         ,
						$revenda                         ,
						$xobs                            ,
						current_timestamp                ,
						$login_posto                     ,
						$xcontrato                       ,
						'$consumidor_email'
					)";
		}else{
			$sql = "UPDATE tbl_os_revenda SET
						fabrica          = $login_fabrica                ,
						sua_os           = case when sua_os isnull then $xsua_os else sua_os end ,
						data_abertura    = $xdata_abertura               ,
						data_nf          = $xdata_nf                     ,
						nota_fiscal      = $xnota_fiscal                 ,
						cliente          = $cliente                      ,
						revenda          = $revenda                      ,
						obs              = $xobs                         ,
						posto            = $login_posto                  ,
						contrato         = $xcontrato                    ,
						consumidor_email = '$consumidor_email'
					WHERE os_revenda  = $os_revenda
					AND	 posto        = $login_posto
					AND	 fabrica      = $login_fabrica ";
		}
		$msg_debug = $sql."<br>";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		# echo $sql."<br><br>";

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result ($res,0,0);
			$msg_erro   = pg_errormessage($con);

			if (strlen ($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET contrato = $xcontrato
						WHERE  tbl_cliente.cliente  = $revenda";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}

		//HD 9013 56662
		if(strlen($os_revenda)>0 and strlen($msg_erro) == 0){
			$sql="SELECT tbl_os_revenda.sua_os,
						 tbl_posto_fabrica.codigo_posto
						 FROM tbl_os_revenda
						 	JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
							JOIN tbl_posto_fabrica on tbl_os_revenda.posto= tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
							WHERE tbl_os_revenda.nota_fiscal::float = (
										SELECT nota_fiscal::float
										FROM   tbl_os_revenda
										WHERE  os_revenda = $os_revenda
										and posto      = $login_posto
										and fabrica    = $login_fabrica
										)
							and   revenda       = (
										SELECT revenda
										FROM   tbl_os_revenda
										WHERE  os_revenda = $os_revenda
										and posto      = $login_posto
										and fabrica    = $login_fabrica
										)
							and tbl_os_revenda.os_revenda <>$os_revenda
							and tbl_os_revenda_item.tipo_atendimento IS NULL
							AND tbl_os_revenda.excluida IS NOT TRUE
							and tbl_posto_fabrica.posto      = $login_posto
							and tbl_os_revenda.fabrica    = $login_fabrica";
					$res=pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						$sua_os       = pg_result($res,0,sua_os);
						$codigo_posto = pg_result($res,0,codigo_posto);
						$msg_erro="Nota fiscal já foi informada na OS $codigo_posto$sua_os. O sistema permite a digitação de apenas uma OS de revenda para cada nota fiscal, pois é possível incluir na mesma OS a quantidade total de produtos que serão atendidos em garantia.";
			}
		}
		if (strlen($msg_erro) == 0) {


			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$referencia         = trim($produto_referencia[$i]);
				$codigo_fabricacao_post = trim($codigo_fabricacao[$i]);
				$serie              = trim($produto_serie[$i]);
				$capacidade         = trim($produto_capacidade[$i]);
				$voltagem           = trim($produto_voltagem[$i]);
				$type_post          = trim($type[$i]);

				$embalagem_original = trim($embalagem_original_hidden[$i]);
				$sinal_de_uso       = trim($sinal_de_uso_hidden[$i]);


			//	if (strlen($embalagem_original) == 0) $embalagem_original = "f";
			//	if (strlen($sinal_de_uso) == 0)       $sinal_de_uso = "f";

				if (strlen($type_post) == 0)
					$type_post = "''";
				else
					$type_post = "'". $type_post ."'";

				if (strlen($voltagem) == 0)
					$voltagem = "null";
				else
					$voltagem = "'". $voltagem ."'";

				if (strlen($msg_erro) == 0) {
					if (strlen ($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace ("-","",$referencia);
						$referencia = str_replace (".","",$referencia);
						$referencia = str_replace ("/","",$referencia);
						$referencia = str_replace (" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql =	"SELECT tbl_produto.produto, tbl_produto.numero_serie_obrigatorio, tbl_linha.linha
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   UPPER(tbl_produto.referencia_pesquisa) = UPPER($referencia)
								AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem)
								AND     tbl_linha.fabrica = $login_fabrica;";
						$res = pg_exec($con,$sql);

						if (pg_numrows($res) == 0) {
							$msg_erro = " Produto $referencia não cadastrado. <BR>";
							$linha_erro = $i;
						}else{
							$produto                  = pg_result($res,0,produto);
							$numero_serie_obrigatorio = pg_result($res,0,numero_serie_obrigatorio);
							$linha                    = pg_result($res,0,linha);
						}

						if (strlen($serie) == 0) {
							if ($linha == 198) {
								$msg_erro .= " Número de série do produto $referencia é obrigatório. <BR>";
								$linha_erro = $i;
							}else{
								$serie = 'null';
							}
						}else{
							if ($linha == 199 OR $linha == 200) {
								$msg_erro .= " Número de série do produto $referencia não pode ser preenchido. <BR>";
								$linha_erro = $i;
							}else{
								$serie = "'". $serie ."'";
							}
						}

						if (strlen($capacidade) == 0) {
							$xcapacidade = 'null';
						}else{
							$xcapacidade = "'".$capacidade."'";
						}

						if (strlen($codigo_fabricacao_post) == 0) {
							$msg_erro = "Digite o Código de fabricação.<BR>";
						}else{
							$codigo_fabricacao_post = "'". $codigo_fabricacao_post ."'";
						}

						if (strlen($embalagem_original) == 0){
								echo $embalagem_original;
								$msg_erro .= "Gentileza marcar opção sim ou não para os campos embalagem original e/ou sinal de uso.<BR>";
						}
						if (strlen($sinal_de_uso) == 0)  {
								$msg_erro .= "Gentileza marcar opção sim ou não para os campos embalagem original e/ou sinal de uso.<BR>";
						}

						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_revenda_item (
										os_revenda ,
										produto    ,
										serie      ,
										codigo_fabricacao,
										nota_fiscal,
										data_nf    ,
										capacidade ,
										type               ,
										embalagem_original ,
										sinal_de_uso
									) VALUES (
										$os_revenda           ,
										$produto              ,
										$serie                ,
										$codigo_fabricacao_post    ,
										$xnota_fiscal         ,
										$xdata_nf             ,
										$xcapacidade          ,
										$type_post                 ,
										'$embalagem_original' ,
										'$sinal_de_uso'
									)";
							$res = pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);

						if (strlen($msg_erro) == 0) {
							$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda_item')");
							$os_revenda_item = pg_result ($res,0,0);
							$msg_erro   = pg_errormessage($con);

							$conta_qtde++;

						}
							if (strlen ($msg_erro) == 0) {
								$sql = "SELECT fn_valida_os_item_revenda_black($os_revenda,$login_fabrica,$produto,$os_revenda_item)";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
							}

							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}

			if($qtde_item!=$conta_qtde and strlen($msg_erro) ==0){
				$msg_erro="FOI INFORMADO NO CAMPO QUANTIDADE DE PRODUTOS B&D/DW NESSA NOTA FISCAL $qtde_item E DETECTAMOS A DIGITAÇÃO DE QUANTIDADE INFERIOR À INFORMADA. GENTILEZA VERIFICAR.";
			}

			if (strlen ($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$login_posto,$login_fabrica)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}


  include_once 'regras/envioObrigatorioNF.php';

    $obriga_anexo = EnvioObrigatorioNF($login_fabrica, $login_posto);
	$objectId = $_POST['objectid'];

    if ($login_fabrica == 1) {
        $filesByImageUploader = 0;

        $sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs 
                      FROM tbl_tdocs 
                     WHERE referencia_id = 0 
                       AND referencia = '$objectId' 
                       AND contexto = 'os'";
        $resDocs = pg_query($con,$sqlDocs);
        $filesByImageUploader = pg_num_rows($resDocs);
    }

  if ( strlen($msg_erro) == 0 && !$filesByImageUploader) {
      $arr_anexou = array();

      foreach (range(0, 4) as $idx) {
          $file = array(
              "name" => $_FILES["foto_nf"]["name"][$idx][0],
              "type" => $_FILES["foto_nf"]["type"][$idx][0],
              "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
              "error" => $_FILES["foto_nf"]["error"][$idx][0],
              "size" => $_FILES["foto_nf"]["size"][$idx][0]
          );


          if (!empty($file["size"])) {
              $anexou = anexaNF( "r_" . $os_revenda, $file);
              if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
              $arr_anexou[$idx] = $anexou;
          } else {
              if ($obriga_anexo) {
                  $tmp_erro = 'Anexo de NF obrigatório';
              }
          }
      }

      if (!empty($tmp_erro) and !in_array(0, $arr_anexou)) {

          $msg_erro = $tmp_erro;

      }
  }

	$filesByImageUploader = 0;
	if ($login_fabrica == 1) {

		$sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs 
		              FROM tbl_tdocs 
		             WHERE referencia_id = 0 
		               AND referencia = '$objectId' 
		               AND contexto = 'os'";
		$resDocs = pg_query($con,$sqlDocs);
		$resDocs = pg_fetch_all($resDocs);

		if(count($resDocs)>0 && $resDocs != false){

			foreach ($resDocs as $key => $value) {

				$sqlUpate = "UPDATE tbl_tdocs 
				                SET fabrica = $login_fabrica, 
				                    referencia = 'revenda', 
				                    referencia_id = $os_revenda 
				              WHERE tdocs = ".$value['tdocs'];
				$res = pg_query($con, $sqlUpate);

				if(pg_last_error($con)){
					$msg_erro .= "<br>".pg_last_error($con);
				}

				$filesByImageUploader += 1;

			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	}else{
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já está cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		$os_revenda = trim($_POST['os_revenda']);

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}


}

if ((strlen($msg_erro) == 0) AND (strlen($os_revenda) > 0)){

	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_revenda.nome  AS revenda_nome                                    ,
					tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					tbl_revenda.fone  AS revenda_fone                                    ,
					tbl_revenda.email AS revenda_email                                   ,
					tbl_os_revenda.explodida                                             ,
					tbl_os_revenda.consumidor_email
			FROM tbl_os_revenda
			JOIN tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN tbl_fabrica USING (fabrica)
			JOIN tbl_posto USING (posto)
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_os_revenda.os_revenda = $os_revenda
      AND  tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			AND tbl_os_revenda.posto = $login_posto
			AND tbl_os_revenda.fabrica = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_nf          = pg_result($res,0,data_nf);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$contrato         = pg_result($res,0,contrato);
		$explodida        = pg_result($res,0,explodida);
		$consumidor_email = pg_result($res,0,consumidor_email);

		if (strlen($explodida) > 0){
			header("Location:os_revenda_parametros.php");
			exit;
		}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    posto   = $login_posto
				AND    fabrica = $login_fabrica";
		$resX = pg_exec($con, $sql);

   	if (pg_numrows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM tbl_os_revenda_item
				JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE tbl_os_revenda.os_revenda = $os_revenda
				AND tbl_os_revenda.posto      = $login_posto
				AND tbl_os_revenda.fabrica    = $login_fabrica
				AND tbl_os_revenda_item.nota_fiscal NOTNULL
				AND tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);

    if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda";
$layout_menu	= 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$digita_os = pg_result ($res,0,0);
if ($digita_os == 'f') {
        echo "<H4>"; fecho("sem.permissao.de.acesso",$con,$cook_idioma); echo "</H4>";
        exit;
}

include "javascript_pesquisas.php"

?>
<script type="text/javascript" src="admin/js/jquery-1.8.3.min.js"></script>

<!-- <script language='javascript' src='js/jquery.js'></script>
 -->
 <script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script language="JavaScript">

$(document).ready(function(){
	$("input[rel='fone']").maskedinput("(99) 9999-9999");
	$("#data_abertura").maskedinput("99/99/9999");
	$("#data_nf").maskedinput("99/99/9999");
	$("input[name='data_abertura']").datepick({startdate:'01/01/2000'});
	$("input[name='data_nf']").datepick({startdate:'01/01/2000'});

	//$(".content").corner("dog 10px");

	$('input').focus(function(){
        var cnpj = $('#revenda_cnpj').val();
        var lista_cnpj = [
            '53.296.273/0001-91',
            '53.296.273/0032-98',
            '03.997.959/0002-12',
            '03.997.959/0003-01'
        ];

        if ($.inArray(cnpj, lista_cnpj) >= 0 && $('#alerta').val() == '0') {
        	$('#alerta').val(1);
            janela=window.open("os_info_black2.php", "janela", "toolbar=no, location=no, status=no, scrollbars=no, directories=no, width=501, height=400, top=18, left=0");
            janela.focus();
        }
	});

	$("input[name^=embalagem_original_]").change(function(){
		var valor = $(this).val();
		$(this).parent().find("input[name^=embalagem_original_hidden]").val(valor);
	});

	$("input[name^=sinal_de_uso_]").change(function(){
		var valor = $(this).val();
		$(this).parent().find("input[name^=sinal_de_uso_hidden]").val(valor);
	});
    verifyObjectId($("#objectid").val());

});

function addAnexoUpload()
{
    var tpl = $("#anexoTpl").html();
    var id = $("#qtde_anexos").val();

    if (id == "5") {
        return;
    }

    var tr = '<tr>' + tpl.replace('@ID@', id) + '</tr>';
    $("#qtde_anexos").val(parseInt(id) + 1);

    $("#input_anexos").append(tr);
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
	$('#alerta').val(0);
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, campo3, tipo) {

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.voltagem		= campo3;
		janela.focus();
	}
}

function fnc_pesquisa_produto_serie (campo,campo2,campo3) {

  if (campo3.value != "") {
		var url = "";
		url = "produto_serie_pesquisa2.php?campo=" + campo3.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.serie	= campo3;
		janela.focus();
	}

}

function char(nota_fiscal){
	try{var element = nota_fiscal.which	}catch(er){};
	try{var element = event.keyCode	}catch(er){};
	if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
	return false
}

window.onload = function(){
	document.getElementById('nota_fiscal').onkeypress = char;
}
  function verifyObjectId(objectId){

    $.ajax("controllers/TDocs.php",{
            method: "POST",
            data:{
              "ajax": "verifyObjectId",
              "objectId": objectId,
              "context": "os"
            }
          }).done(function(response){
            response = JSON.parse(response);

            if(response.exception == undefined){
              $(response).each(function(idx,elem){

                if($("#"+elem.tdocs_id).length == 0){
                  //var img = $("<div class='env-img'><img id='"+elem.tdocs_id+"' style='width: 150px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //##var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //$(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id);


                  var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button class='btn-danger' data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");

                  $(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg");
                  $(img).find("button").click(function(){
                      $.ajax("controllers/TDocs.php",{
                        method: "POST",
                        data: {
                          "ajax": "removeImage",
                          "objectId": elem.tdocs_id,
                          "context": "os"
                        }
                      }).done(function(response){
                          response = JSON.parse(response);
                          console.log(response);
                          if(response.res == 'ok'){
                            $("#"+elem.tdocs_id).parents(".env-img").fadeOut(1000);
                          }else{
                            alert("Não foi possível excluir o anexo, por favor tente novamente");
                          }
                      });
                  });

                  $("#env-images").append(img);
                  setupZoom();
                  console.log(elem.tdocs_id);
                }
              });
            }
          });
  }
  setIntervalRunning = false;
  setIntervalHandler = null;

  function getQrCode(){
    $("#btn-qrcode-request").fadeOut(1000);
    $("#btn-google-play").fadeOut(1000);
    $.ajax("controllers/QrCode.php",{
      method: "POST",
      data: {
        "ajax": "requireQrCode",
        "options": [
          "notafiscal"
        ],
        "title": "Upload de Nota Fiscal",
        "objectId": $("#objectid").val()
      }
   }).done(function(response){

      response = JSON.parse(response);
      console.log(response);

      $("#env-qrcode").find("img").attr("src",response.qrcode)
      $("#env-qrcode").fadeIn(1000);

      if(setIntervalRunning==false){
        setIntervalHandler = setInterval(function(){
          console.log("buscando...");


          verifyObjectId($("#objectid").val());
        },5000);
      }
   });
  }

</script>


<style type="text/css">
	@import "plugins/jquery/datepick/telecontrol.datepick.css";
.mobile:hover {
  background: #5b5c8d;
}
.mobile:active{
  background: #373865;
}
.mobile{
  display: inline-flex;
  height: 45px;
  width: 190px;
  background: #373865;
  padding: 5px;
  border-radius: 10px;
  cursor: pointer;
}
.google_play{
  margin-left: 10%;
  display: inline-flex;
  height: 45px;
  padding: 5px;
  cursor: pointer;

}
.google_play > a >span{
  color: #373865;
}
.google_play:hover{
  background: #f3f3f3;
}
.mobile > span{
  font-size: 14px;
  float: right;
  margin-top: 14px;
  margin-right: 14px;
  color: #fac814;
}

.env-code{
  width: 100%;
  border: solid 3px;
  border-color: #373866;
  width: 205px;
  border-radius: 7px;
  margin-top: 10px;
}

.env-img {
 /*   float: left;*/
    max-width: 150px;
    margin-left: 10px;
    margin-top: 10px;
    display: inline-block;
}

.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px 30px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:center;
}
.content h1 {
    color:black;
    font-size: 120%;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.old_table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" width='700' align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" width='100%' align="center"
         style="padding:4px 8px;background:red;color: white;font-weight:bold">
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
		$sqlT =	"SELECT tbl_lista_basica.type, tbl_produto.referencia
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE tbl_produto.produto = $produto
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type, tbl_produto.referencia
				ORDER BY tbl_lista_basica.type;";
		$resT = pg_exec ($con,$sqlT);

		if (pg_numrows($resT) > 0) {
			$s = pg_numrows($resT) - 1;
			for ($t = 0 ; $t < pg_numrows($resT) ; $t++) {
				$typeT = pg_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			if (strpos($msg_erro,"É necessário informar o type para o produto") !== false) $msg_erro = "É necessário informar o type para o produto ".pg_result($resT,0,referencia).".<br>";
			if (strpos($msg_erro,"Type informado para o produto não é válido") !== false) $msg_erro = "Type informado para o produto ".pg_result($resT,0,referencia)." não é válido.<br>";
			$msg_erro .= "Selecione o Type: $result_type";
		}
	}

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
	</td>
</tr>
</table>
<?
}
//if ($ip == "201.0.9.216") echo $msg_debug;
?>
<?
if ($ip <> "201.0.9.216" and $ip <> "200.140.205.237" and 1==2) {
?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="old_table">
	<tr>
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> A PÁGINA FOI RETIRADA DO AR PARA QUE POSSAMOS MELHORAR A PERFORMANCE DE LANÇAMENTO.</font></td>
	</tr>
</table>

<? exit; ?>

<? } ?>

<?
//HD 12003
	echo "<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>";
		echo "<TR>";
			echo "<TD>";
				echo "<P align='justify'><FONT COLOR='#cc0000'><B>Importante:<BR>
				Para lançamento de troca de produto (garantia ou faturada), criamos uma o.s de troca específica. A troca de produto, só será efetuada através desta nova o.s.
				Por gentileza, <A HREF='os_info_black.php' target='_blanck'>clique aqui</A> para obter informações sobre a nova sistemática de o.s de troca.</B></FONT></P>";
			echo "</TD>";
		echo "</TR>";
	echo "</TABLE>";

	//hd 20428 30/5/2008 - Deixar por um mes ( retirar dia 02/07/2008 )
	$date = date('d/m/Y');
	if($date < "02/07/2008"){
		echo "<table width='700' border='0' cellspacing='2' cellpadding='5' align='center' bgcolor='#F1F4FA'>";
			echo "<TR align='center' bgcolor='#336699'>";
				echo "<TD height='25' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'>";
					echo "Comunicado";
				echo "</TD>";
			echo "</TR>";
			echo "<TR>";
				echo "<TD>";
					echo "<P>Prezado Assistente,</P>";
					echo "<P>Para facilitar o processo e controle dos postos autorizados e da Black & Decker implementaremos uma nova sistemática a partir do dia 02/06/2008 para a digitação de O.S de revenda.</P>";
						echo "<ul type='square'>";
							echo "<li><P  style='font-family: verdana, arial ; font-size: 11px; text-align=justify' >A OS de revenda terá um novo campo para o posto informar a quantidade de produtos da nota fiscal. Após informar e clicar em listar a quantidade digitada o sistema listará o número de linhas de acordo com a quantidade de produtos informada. Portanto, não será mais limitada a digitação de apenas 40 produtos por OS e sim a quantidade que for necessária.</P>";

							echo "<li><P  style='font-family: verdana, arial ; font-size: 11px; text-align=justify' >O sistema não permitirá que o posto abra uma nova OS de revenda com os mesmos dados de nota fiscal de uma OS anterior, com exceção apenas da OS de troca. Somente nessa OS permitiremos a digitação de nota fiscal em duplicidade com a OS de revenda comum.</p>";

							echo "<li><P  style='font-family: verdana, arial ; font-size: 11px; text-align=justify' >As OS's de revenda com um mesmo número deverão ser fechadas todas juntas. O sistema apresentará erro se o posto tentar fechar apenas algumas seqüências.</p>";

							echo "<li><P  style='font-family: verdana, arial ; font-size: 11px; text-align=justify' >Não será mais aceito digitar o CNPJ da B&D no campo destinado ao CNPJ da revenda. Muitos postos estavam digitando erroneamente e depois de explodir a OS colocavam o nome da revenda no campo nome do cliente ou às vezes deixavam apenas o fabricante.</p>";

							echo "<li><P  style='font-family: verdana, arial ; font-size: 11px; text-align=justify' >Na tela de fechamento, se o posto clicar na opção listar todas as OS's o sistema mostrará as OS's de revenda separadas das OS's de consumidor para facilitar o fechamento.</p>";
						echo "</ul>";
					echo "<P>Atenciosamente,<BR>";
					echo "Departamento de Assistência Técnica.<BR>";
					echo "Black & Decker do Brasil Ltda</P>";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";
	}
	//-----------------------------------------------------------------
?>
<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="old_table">
	<tr class="menu_top">
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> AS ORDENS DE SERVIÇO DIGITADAS NESTE MÓDULO SÓ SERÃO VÁLIDAS APÓS O CLIQUE EM GRAVAR E DEPOIS EM EXPLODIR.</font></td>
	</tr>
</table>

<br>
<input type="hidden" name="alerta" id="alerta" value="0">
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="old_table">
	<tr >
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">

			<!--------------- Formulário ----------------- -->
			<form name="frm_os" id="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
				<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
				<tr class="menu_top">
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
					</td>
					<? } ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
					</td>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Nota</font>
					</td>
				</tr>
				<tr>
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap align='center'>
						<input name="sua_os" class="frm" type="text" size="10" maxlength="10" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
					</td>
					<? } ?>
					<td nowrap align='center'>
						<!--<input name="data_abertura" size="12" maxlength="10" value="<? //if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" type="text" class="frm" tabindex="0"> <font face='arial' size='1'> Ex.: <?// echo date("d/m/Y"); ?></font>-->
						<!--takashi 22/12 HD-925-->
						<input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<? echo $data_abertura; ?>" type="text" class="frm" tabindex="0"> <font face='arial' size='1'> Ex.: <? echo date("d/m/Y"); ?></font>
					</td>
					<td nowrap align='center'>
						<input name="nota_fiscal" size="6" maxlength="20" id="nota_fiscal" value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
					</td>
					<td nowrap align='center'>
						<input name="data_nf" id="data_nf" size="12" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 14/06/2006</font>
					</td>
				</tr>
				<tr>
					<td colspan='4' class="table_line2" height='20'></td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone Revenda</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">e-Mail Revenda</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_fone" id="revenda_fone" rel='fone' size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_email" id="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>

<input type="hidden" name="revenda_cidade" id="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" id="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" id="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" id="revenda_cep" value="">
<input type="hidden" name="revenda_numero" id="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" id="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" id="revenda_bairro" value="">

<? if ($anexaNotaFiscal and $os == '') {
	$display = (!empty($qtde_item)) ? '' : ' style="display:none"';

	if(strlen($os_revenda) > 0){
		$temAnexos = 0;
	}
	if ($temAnexos)
	$anexos = "<tr><td align='center'>" . temNF("r_$os_revenda", 'link') . "</td></tr>\n";
	if (($anexa_duas_fotos and $temAnexos < LIMITE_ANEXOS) or $temAnexos == 0) { ?>
<table width="100%" border="0" cellspacing="3" <?=$display?> cellpadding="2" id="input_anexos">
	<tr class="menu_top">
		<td>Anexar Nota Fiscal<br /></td>
	</tr>
<?php if ($login_fabrica == 1) {?>
	<tr>
		<td align="center">
	    	<br>
			  <div id="env-qrcode" style="display:none;">
			    <div class='env-code'>
			      <img style="width: 200px;" src="">
			    </div>
			  </div>
  			  <!-- <img id="btn-qrcode-request" src="imagens/btn_imageuploader.gif" onclick="getQrCode()" alt="Fazer Upload via Image Uploader" border="0" style="cursor: pointer;border: 1px solid #888;">-->
			  <div style="width:920px;text-align:center">
			    <span class="mobile" id="btn-qrcode-request" onclick="getQrCode()">
			    <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_mobile.png">
			    <span>Anexar via Mobile</span>
			    </span>
			    <span class="google_play" id="btn-google-play">
			      <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">
			        <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_google_play.png">
			        <span style="margin-top: 17px;float: left;font-size: 12px; color: #373865;">Baixar Aplicativo Image Uploader</span>
			      </a>
			    </span>
			  </div>
			  <div id="env-images"></div>
			<?php
			  #color: #373865
			  echo $include_imgZoom;
			?>
			<br>
		</td>
	</tr>
	<?php }?>
	<?=$anexos?>
	<tr>
        <td align="center">
            <?php
            $inputNotaFiscalTpl = str_replace('foto_nf', 'foto_nf[@ID@]', $inputNotaFiscal);
            echo str_replace('@ID@', '0', $inputNotaFiscalTpl);

            $anexoTpl = '
                    <tr id="anexoTpl" style="display: none">
                        <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
                          ' . $inputNotaFiscalTpl . '
                        </td>
                    </tr>
                ';

            echo '<input type="hidden" id="qtde_anexos" name="qtde_anexos" value="1" />';
            ?>
        </td>
	</tr>
    <?php
    if (!empty($qtde_item)) {
        echo $anexoTpl;
    }
    ?>
</table>
<?php
    if (!empty($qtde_item)) {
        echo '<div align="center"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>';
    }
?>
<? }
} ?>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
<?
	if($login_fabrica == 7){
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Contrato</font>
					</td>
<?  } ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>

<!--				<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Linhas</font>
					</td>-->
				</tr>

				<tr>
<?
	if($login_fabrica == 7){
?>
					<td align='center'>
						<input type="checkbox" name="contrato" value="t" <? if ($contrato == 't') echo " checked"?>>
					</td>
<?
}
?>
					<td align='center'>
						<input class="frm" type="text" name="obs" size="68" value="<? echo $obs ?>">
					</td>
<!--				<td align='center'>
						<select size='1' class="frm" name='qtde_linhas' onChange="javascript: document.frm_os.submit(); ">
							<option value='20' <? if ($qtde_linhas == 20) echo 'selected'; ?>>20</option>
							<option value='30' <? if ($qtde_linhas == 30) echo 'selected'; ?>>30</option>
							<option value='40' <? if ($qtde_linhas == 40) echo 'selected'; ?>>40</option>
						</select>
					</td>-->
				</tr>
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Email de Contato</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="consumidor_email" size="68" value="<? echo $consumidor_email ?>">
					</td>
				</tr>
			</table>
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec ($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";
//HD 9013
if(($qtde_item==0 or strlen($qtde_item)==0) and strlen($os_revenda)==0){
	echo "<br>";
	echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff' class='old_table'>";
	echo "<caption class='menu_top'>ATENÇÃO:</caption>";
	echo "<td><P align='justify'><FONT COLOR='#000009'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><br>
		A digitação de NF de Revenda tem que ser na totalidade, ou seja, todos os produtos referentes a mesma nota fiscal devem ser digitados na mesma OS. Favor informar a quantidade de produtos existentes para que o programa insira a quantidade correta.<br><br>
	NOTA: Não será possível digitar novamente a mesma Nota Fiscal!
	</font></td></P>";
	echo "</tr>";
	echo "<tr><td>&nbsp;</td><tr>";
	echo "<tr>";
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'><b>QUANTIDADE DE PRODUTOS B&D/DW</b></font>&nbsp;&nbsp; <input type=text size=10 maxlength=10 name='qtde_linhas' >&nbsp;<input type='button' name='Listar' value='Listar quantidade digitada' onclick=\"javascript: document.frm_os.submit();\">";
	echo "</td></tr>";
	echo "</table>";
}else{

		for ($i = 0 ; $i < $qtde_item ; $i++) {

			$novo               = 't';

			if ($i % 20 == 0) {

        echo "<table width='98%' border='0' cellpadding='0' cellspacing='2' align='center' bgcolor='#ffffff'>";
				echo "<tr class='menu_top'>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cod. Fabricação</font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número de série</font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'> Voltagem </font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Type</font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Embalagem Original</font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Sinal de Uso</font></td>\n";
				echo "</tr>";
			}

			if (strlen($os_revenda) > 0){
				if (@pg_numrows($res_os) > 0) {
					$produto = trim(@pg_result($res_os,$i,produto));
				}

				if(strlen($produto) > 0){
         	// seleciona do banco de dados
					$sql = "SELECT   tbl_os_revenda_item.os_revenda_item    ,
									 tbl_os_revenda_item.serie              ,
									 tbl_os_revenda_item.capacidade         ,
									 tbl_os_revenda_item.codigo_fabricacao  ,
									 tbl_os_revenda_item.type               ,
									 tbl_os_revenda_item.embalagem_original ,
									 tbl_os_revenda_item.sinal_de_uso       ,
									 tbl_produto.referencia                 ,
									 tbl_produto.descricao                  ,
									 tbl_produto.voltagem
							FROM tbl_os_revenda
							JOIN tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
							JOIN tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
							WHERE tbl_os_revenda_item.os_revenda = $os_revenda";
					$res = pg_exec($con,$sql);

        	if (@pg_numrows($res) == 0) {

						$novo               = 't';
						$os_revenda_item_post    = $os_revenda_item[$i];
						$referencia_produto = $produto_referencia[$i];
						$serie              = $produto_serie[$i];
						$descricao_produto  = $produto_descricao[$i];
						$capacidade         = $produto_capacidade[$i];
						$type_post          = $type[$i];
						$embalagem_original = $embalagem_original[$i];
						$sinal_de_uso       = $sinal_de_uso[$i];
						$codigo_fabricacao_post  = $codigo_fabricacao[$i];
						$voltagem_produto   = $produto_voltagem[$i];

            // if(is_array($produto_descricao) && count($produto_descricao) > 0){
            //   $descricao_produto = $produto_descricao[$i];
            // }

            // if(is_array($produto_voltagem)&& count($produto_voltagem) > 0){
            //   $voltagem_produto = $produto_voltagem[$i];
            // }

					}else{

            $novo               = 'f';
						$os_revenda_item_post    = pg_result($res, $i, os_revenda_item);
						$referencia_produto = pg_result($res, $i, referencia);
						$descricao_produto  = pg_result($res, $i, descricao);
						$serie              = pg_result($res, $i, serie);
						$capacidade         = pg_result($res, $i, capacidade);
						$type_post          = pg_result($res, $i, type);
						$embalagem_original = pg_result($res, $i, embalagem_original);
						$sinal_de_uso       = pg_result($res, $i, sinal_de_uso);
						$codigo_fabricacao_post  = pg_result($res, $i, codigo_fabricacao);
						$voltagem_produto   = pg_result($res, $i, voltagem);

            // if(is_array($descricao_produto) && count($descricao_produto) > 0){
            //   $descricao_produto = $descricao_produto[$i];
            // }else{
            //   $descricao_produto = $descricao_produto;
            // }

            // if(is_array($voltagem_produto) && count($voltagem_produto) > 0){
            //   $voltagem_produto = $voltagem_produto[$i];
            // }else{
            //   $voltagem_produto = $voltagem_produto;
            // }

					}

				}else{
         	$novo               = 't';

          $os_revenda_item_post = $os_revenda_item[$i];
          $referencia_produto = $produto_referencia[$i];
          $serie              = $produto_serie[$i];
          $descricao_produto  = $produto_descricao[$i];
          $capacidade         = $produto_capacidade[$i];
          $type_post          = $type[$i];
          $embalagem_original = $embalagem_original_hidden[$i];
          $sinal_de_uso       = $sinal_de_uso_hidden[$i];
          $codigo_fabricacao_post  = $codigo_fabricacao[$i];
          $voltagem_produto   = $produto_voltagem[$i];
				}
			}else{
     		$novo               = 't';
				$os_revenda_item_post = $os_revenda_item[$i];
				$referencia_produto = $produto_referencia[$i];
				$serie              = $produto_serie[$i];
				$descricao_produto  = $produto_descricao[$i];
				$capacidade         = $produto_capacidade[$i];
				$type_post          = $type[$i];
				$embalagem_original = $embalagem_original_hidden[$i];
				$sinal_de_uso       = $sinal_de_uso_hidden[$i];
				$codigo_fabricacao_post  = $codigo_fabricacao[$i];
				$voltagem_produto   = $produto_voltagem[$i];

        // if(is_array($produto_descricao) && count($produto_descricao) > 0){
        //   $descricao_produto = $produto_descricao[$i];
        // }

			}


			echo "<input type='hidden' name='novo[]' value='$novo'>\n";
			echo "<input type='hidden' name='item[]' value='$os_revenda_item_post'>\n";

			echo "<tr "; if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
			echo "<td align='center'><input class='frm' type='text' name='codigo_fabricacao[]' size='9' maxlength='20' value='$codigo_fabricacao_post'></td>\n";

      echo "<td align='center'><input class='frm' type='text' name='produto_serie[]' id='produto_serie_$i' size='10'  maxlength='20' value='$serie'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto_serie (document.getElementById(\"produto_referencia_$i\"),document.getElementById(\"produto_descricao_$i\"),document.getElementById(\"produto_serie_$i\"))'\" style='cursor:pointer;'></td>\n";


			echo "<td align='center'><input class='frm' type='text' name='produto_referencia[]' id='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.getElementById(\"produto_referencia_$i\"),document.getElementById(\"produto_descricao_$i\"),document.getElementById(\"produto_voltagem_$i\"),\"referencia\")' style='cursor:pointer;'></td>\n";

			echo "<td align='center'><input class='frm' type='text' name='produto_descricao[]' id='produto_descricao_$i' size='30' maxlength='50' value='$descricao_produto'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.getElementById(\"produto_referencia_$i\"),document.getElementById(\"produto_descricao_$i\"),document.getElementById(\"produto_voltagem_$i\"),\"descricao\")' style='cursor:pointer;'></td>\n";
			echo "<td align='center'><input class='frm' type='text' name='produto_voltagem[]' size='5' id='produto_voltagem_$i' value='$voltagem_produto'></td>\n";

			?>
			<td align='center' nowrap>
			&nbsp;
			    <?
			     GeraComboType::makeComboType($parametrosAdicionaisObject,$type_post, "type[]", array("class"=>"frm"));
			     echo GeraComboType::getElement();
			    ?>

			&nbsp;
			</td>
			<td align='center' nowrap>
				&nbsp;
				<input class='frm' type="radio" name="embalagem_original_<?=$i?>" value="t" <? if ($embalagem_original == 't'/* OR strlen($embalagem_original) == 0*/) echo "checked"; ?>>
				<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</b></font>
				<input class='frm' type="radio" name="embalagem_original_<?=$i?>" value="f" <? if ($embalagem_original == 'f') echo "checked"; ?>>
				<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</b></font>
				&nbsp;
				<input type='hidden' name='embalagem_original_hidden[]' value='<?=$embalagem_original?>'>
			</td>
			<td align='center' nowrap>
				&nbsp;
				<input class='frm' type="radio" name="sinal_de_uso_<?=$i?>" value="t" <? if ($sinal_de_uso == 't') echo "checked"; ?>>
				<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</font>
				<input class='frm' type="radio" name="sinal_de_uso_<?=$i?>" value="f" <? if ($sinal_de_uso == 'f' /* OR strlen($sinal_de_uso) == 0*/) echo "checked"; ?>>
				<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</font>
				&nbsp;
				<input type='hidden' name='sinal_de_uso_hidden[]' value='<?=$sinal_de_uso?>'>
			</td>
			<?

			echo "</tr>\n";

			// limpa as variaveis
			// $novo               = '';
			// $os_revenda_item    = '';
			// $referencia_produto = '';
			// $serie              = '';
			// $descricao_produto  = '';
			// $capacidade         = '';

		}
}
echo "<tr>";
echo "<td colspan='8' align='center'>";
echo "<br>";
//echo "<input type='hidden' name='btn_acao' value=''>";
if($qtde_item != 0 ){
	echo "<img src='imagens/btn_gravar.gif' name='sem_submit' class='verifica_servidor' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";
}

if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
if(strlen($os_revenda) >0){
	echo "<tr>";
	echo "<td nowrap colspan='5' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'><b>ALTERAR QUANTIDADE DE PRODUTOS B&D/DW</b></font>&nbsp;&nbsp; <input type=text size=5 maxlength=10 name='qtde_linhas' >&nbsp;<input type='button' name='Listar' value='ALTERAR QTDE' onclick=\"javascript: document.frm_os.submit();\">";
	echo "</td></tr>";
}
echo "</table>";
?>
<?php
  if($_POST['objectid'] == ""){
      $objectId = $login_fabrica.$login_posto.date('dmyhis').rand(1,10000);
  }else{
      $objectId = $_POST['objectid'];
  }

  ?>
  <input type="hidden" id="objectid"  name="objectid" value="<?php echo $objectId; ?>">

</form>

<br>

<? include "rodape.php";?>
