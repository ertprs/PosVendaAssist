<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';
include 'funcoes.php';
include_once('../anexaNF_inc.php');
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "../classes/form/GeraComboType.php";
}

$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result($res,0,pedir_sua_os);

$msg_erro  = "";
$qtde_item = 20;

if (strlen($_POST['qtde_item']) > 0)   $qtde_item = $_POST['qtde_item'];
if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower ($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if(strlen($os_revenda) > 0){
	$sql="SELECT tipo_atendimento from tbl_os_revenda where os_revenda=$os_revenda";
	$res=pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$tipo_atendimento=pg_fetch_result($res,0,tipo_atendimento);
		if(strlen($tipo_atendimento) >0 ){
			header("Location: os_revenda_troca.php?os_revenda=$os_revenda");
		}
	}
	$sql = "SELECT explodida FROM tbl_os_revenda WHERE os_revenda=$os_revenda AND explodida IS NOT NULL";
	$resX = pg_query($con, $sql);
	if (pg_num_rows($resX)) {
		echo "ESTA OS JÁ FOI EXPLODIDA E NÃO PODE SER ALTERADA";
		exit;
	}

}
if ($btn_acao == "gravar")
{
	if (strlen($_POST['sua_os']) > 0){
		$xsua_os = "'". $_POST['sua_os'] ."'";
	} else {
		$xsua_os = "null";
	}

	$xdata_abertura  = fnc_formata_data_pg($_POST['data_abertura']);
	$xdata_digitacao = fnc_formata_data_pg($_POST['data_digitacao']);
	$xdata_nf        = fnc_formata_data_pg($_POST['data_nf']);

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	} else {
		$nota_fiscal = trim($nota_fiscal);
		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(" ","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr($nota_fiscal,strlen($nota_fiscal)-6,6);
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;
	}

	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace(".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace(" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	} else {
		$xrevenda_cnpj = "null";
	}

	if ($xrevenda_cnpj <> "null") {
		$sql = "SELECT *
				FROM   tbl_revenda
				WHERE  cnpj = $xrevenda_cnpj";
		$res = pg_query($con,$sql);

		if (pg_num_rows ($res) == 0){
			$msg_erro = "CNPJ da revenda não cadastrado";
		} else {
			$revenda     = trim(pg_fetch_result($res,0,revenda));
			$nome        = trim(pg_fetch_result($res,0,nome));
			$endereco    = trim(pg_fetch_result($res,0,endereco));
			$numero      = trim(pg_fetch_result($res,0,numero));
			$complemento = trim(pg_fetch_result($res,0,complemento));
			$bairro      = trim(pg_fetch_result($res,0,bairro));
			$cep         = trim(pg_fetch_result($res,0,cep));
			$cidade      = trim(pg_fetch_result($res,0,cidade));
			$fone        = trim(pg_fetch_result($res,0,fone));
			$cnpj        = trim(pg_fetch_result($res,0,cnpj));

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
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0){
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

				$res     = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);

				if (strlen($msg_erro) == 0 and strlen($cliente) == 0) {
					$res     = pg_query($con,"SELECT CURRVAL ('seq_cliente')");
					$msg_erro = pg_last_error($con);
					if (strlen($msg_erro) == 0) $cliente = pg_fetch_result($res,0,0);
				}

			} else {
				// pega valor de cliente
				$cliente = pg_fetch_result($res,0,cliente);
			}
		}
	} else {
		$msg_erro = "CNPJ não informado";
	}

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	} else {
		$xrevenda_fone = "null";
	}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	} else {
		$xrevenda_email = "null";
	}

	if (strlen($_POST['posto_codigo']) > 0) {
		$posto_codigo = trim($_POST['posto_codigo']);
		$posto_codigo = str_replace("-","",$posto_codigo);
		$posto_codigo = str_replace(".","",$posto_codigo);
		$posto_codigo = str_replace("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);

		$sql = "
			SELECT posto
			FROM tbl_posto_fabrica
		    WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'
		    AND tbl_posto_fabrica.fabrica = $login_fabrica
		";

		$res = pg_query($con,$sql);
		$posto = pg_fetch_result($res,0,0);

	} else {
		$posto = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	} else {
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	} else {
		$xcontrato = "'f'";
	}

	if (strlen($msg_erro) == 0) {

		$res = pg_query($con,"BEGIN TRANSACTION");

		if (strlen($os_revenda) == 0) {

			#-------------- insere pedido ------------
			$sql =	"INSERT INTO tbl_os_revenda (
						fabrica      ,
						sua_os       ,
						data_abertura,
						cliente      ,
						revenda      ,
						obs          ,
						digitacao    ,
						posto        ,
						contrato
					) VALUES (
						$login_fabrica   ,
						$xsua_os         ,
						$xdata_abertura  ,
						$cliente         ,
						$revenda         ,
						$xobs            ,
						current_timestamp,
						$posto           ,
						$xcontrato
					)";
		} else {
			$sql = "UPDATE tbl_os_revenda SET
						fabrica       = $login_fabrica ,
						sua_os        = $xsua_os       ,
						data_abertura = $xdata_abertura,
						cliente       = $cliente       ,
						revenda       = $revenda       ,
						obs           = $xobs          ,
						posto         = $posto         ,
						contrato      = $xcontrato
					WHERE os_revenda = $os_revenda
					AND   fabrica    = $login_fabrica";
		}
		$res = @pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_query($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_fetch_result($res,0,0);
			$msg_erro   = pg_last_error($con);

			if (strlen($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET
							contrato = $xcontrato
						WHERE cliente  = $revenda";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}

// 			if (strlen($msg_erro) > 0) {
// 				break ;
// 			}
		}

		if (strlen($msg_erro) == 0) {
			//$qtde_item = $_POST['qtde_item'];

			for ($i = 0 ; $i < $qtde_item ; $i++)
			{
				$novo       = $_POST["novo_".$i];
				$item       = $_POST["item_".$i];

				$referencia         = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
				$voltagem           = $_POST["produto_voltagem_".$i];
				$xcodigo_fabricacao = $_POST["codigo_fabricacao_".$i];

				if (strlen($xcodigo_fabricacao) == 0) $xcodigo_fabricacao = "null";
				else                                  $xcodigo_fabricacao = "'". $xcodigo_fabricacao ."'";

				if (strlen($type) == 0) $type = "null";
				else                    $type = "'".$type."'";

				if (strlen($embalagem_original) == 0) $xembalagem_original = "null";
				else                                  $xembalagem_original = "'".$embalagem_original."'";

				if (strlen($sinal_de_uso) == 0) $xsinal_de_uso = "null";
				else                            $xsinal_de_uso = "'".$sinal_de_uso."'";

				if (strlen($voltagem) == 0) $voltagem = "null";
				else                        $voltagem = "'".$voltagem."'";

				if (strlen($item) > 0 AND $novo == 'f') {
					$sql = "DELETE FROM tbl_os_revenda_item
							WHERE  os_revenda = $os_revenda
							AND    os_revenda_item = $item";
					//$res = @pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}

				if (strlen($msg_erro) == 0) {
					if (strlen($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace("-","",$referencia);
						$referencia = str_replace(".","",$referencia);
						$referencia = str_replace("/","",$referencia);
						$referencia = str_replace(" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql =	"SELECT tbl_produto.produto, tbl_produto.numero_serie_obrigatorio
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   tbl_produto.referencia_pesquisa = UPPER($referencia)
								AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem)
								AND     tbl_linha.fabrica = $login_fabrica;";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) == 0) {
							$msg_erro = "Produto $referencia não cadastrado";
							$linha_erro = $i;
						} else {
							$produto   = pg_fetch_result($res,0,produto);
							$numero_serie_obrigatorio = pg_fetch_result($res,0,numero_serie_obrigatorio);
						}

						if (strlen($serie) == 0) {
							if ($numero_serie_obrigatorio == 't') {
								$msg_erro .= " Número de série do produto $referencia é obrigatório. ";
								$linha_erro = $i;
							} else {
								$serie = 'null';
							}
						} else {
							$serie = "'". $serie ."'";
						}

						if (strlen($capacidade) == 0) {
							$xcapacidade = 'null';
						} else {
							$xcapacidade = "'".$capacidade."'";
						}

						if (strlen($msg_erro) == 0) {
							if ((strlen($os_revenda) == 0) OR ($novo == 't')){
								$sql =	"INSERT INTO tbl_os_revenda_item (
											os_revenda         ,
											produto            ,
											nota_fiscal        ,
											data_nf            ,
											serie              ,
											type               ,
											embalagem_original ,
											sinal_de_uso       ,
											codigo_fabricacao
										) VALUES (
											$os_revenda           ,
											$produto              ,
											$xnota_fiscal         ,
											$xdata_nf             ,
											$serie                ,
											$type                 ,
											$xembalagem_original  ,
											$xsinal_de_uso        ,
											$xcodigo_fabricacao
										) RETURNING os_revenda_item";
							} else {
								$sql =	"UPDATE tbl_os_revenda_item SET
											produto            = $produto              ,
											nota_fiscal        = $xnota_fiscal         ,
											data_nf            = $xdata_nf             ,
											serie              = $serie                ,
											type               = $type                 ,
											embalagem_original = $xembalagem_original  ,
											sinal_de_uso       = $xsinal_de_uso        ,
											codigo_fabricacao  = $xcodigo_fabricacao
										WHERE  os_revenda      = $os_revenda
										AND    os_revenda_item = $item";
							}

							$res = @pg_query($con,$sql);
							$msg_erro = pg_last_error($con);

							if (strlen($msg_erro) == 0) {
								$os_revenda_item =  (empty($item)) ? pg_fetch_result($res,0,0) : $item;
								$sql = "SELECT fn_valida_os_item_revenda_black($os_revenda,$login_fabrica,$produto,$os_revenda_item)";
								$res = @pg_query($con,$sql);
								$msg_erro = pg_last_error($con);
							}

							if (strlen($msg_erro) > 0) {
								break;
							} else {
								if (strlen($msg_erro) > 0) {
									$linha_erro = $i;
									break;
								}
							}
						}
					}
				}
			}

			if (strlen($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
			}
		}
	}

	if ( strlen($msg_erro) == 0 && $login_fabrica == 1) {

		$anexou = anexaNF( "r_" . $os_revenda, $_FILES['foto_nf']);
		if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK

	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	} else {
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		$os_revenda = trim($_POST['os_revenda']);
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){

		$res = pg_query($con,"BEGIN TRANSACTION");

		$sql = "DELETE FROM tbl_os_revenda_item
				WHERE  tbl_os_revenda_item.os_revenda = $os_revenda ";
		$res = pg_query($con,$sql);

		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica";
		$res = pg_query($con,$sql);

		$msg_erro = pg_last_error($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen($msg_erro) == 0) {

			// Excluir a(s) imagem/ns em anexo, se tiver.
			if (temNF("r_$os_revenda", 'bool')) {
				$anexos = temNF("r_$os_revenda", 'url');

				foreach($anexos as $a) {
					excluirNF($a);
				}
			}
			$res = pg_query($con,"COMMIT TRANSACTION");
			header("Location: $PHP_SELF?msg=OS APAGADA COM SUCESSO");
			exit;
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
}

if((strlen($msg_erro) == 0) && (strlen($os_revenda) > 0)){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					tbl_revenda.nome  AS revenda_nome                                    ,
					tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					tbl_revenda.fone  AS revenda_fone                                    ,
					tbl_revenda.email AS revenda_email                                   ,
					tbl_posto_fabrica.codigo_posto AS posto_codigo                       ,
					tbl_posto.nome    AS posto_nome                                      ,
					tbl_posto.posto
			FROM	tbl_os_revenda
			JOIN	tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			LEFT JOIN tbl_posto USING (posto)
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os_revenda.os_revenda = $os_revenda
			AND   tbl_os_revenda.fabrica    = $login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$sua_os         = pg_fetch_result($res,0,sua_os);
		$data_abertura  = pg_fetch_result($res,0,data_abertura);
		$data_digitacao = pg_fetch_result($res,0,data_digitacao);
		$revenda_nome   = pg_fetch_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_fetch_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_fetch_result($res,0,revenda_fone);
		$revenda_email  = pg_fetch_result($res,0,revenda_email);
		$obs            = pg_fetch_result($res,0,obs);
		$posto_codigo   = pg_fetch_result($res,0,posto_codigo);
		$posto_nome     = pg_fetch_result($res,0,posto_nome);
		$contrato       = pg_fetch_result($res,0,contrato);
		$posto          = pg_fetch_result($res,0,posto);

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os LIKE '$sua_os-%'
				AND    fabrica = $login_fabrica
				AND     posto= $posto";

		$resX = pg_query($con, $sql);

		if (pg_num_rows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$nota_fiscal = pg_fetch_result($res,0,nota_fiscal);
			$data_nf     = pg_fetch_result($res,0,data_nf);
		}
	} else {
		header('Location: os_revenda.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda";
$layout_menu	= "callcenter";

include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario_new.php";
include_once '../js/js_css.php';


?>
<script type="text/javascript">
$(function(){
    $('#data_abertura').datepick({startdate:'01/01/2000'});
    $('#data_nf').datepick({startdate:'01/01/2000'});
    $("#data_abertura").mask("99/99/9999");
    $("#data_nf").mask("99/99/9999");
});
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
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&voltagem=t";
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


/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;

		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

//INICIO DA FUNCAO DATA
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

//Coloca NF
var ok = false;
function TodosNF() {
	f = document.frm_os;
	if (!ok) {
		for (i=0; i<<?echo $qtde_item?>; i++){
			myREF = "produto_referencia_" + i;
			myNF  = "produto_nf_0";
			myNFF = "produto_nf_" + i;
			if ((f.elements[myREF].type == "text") && (f.elements[myREF].value != "")){
				f.elements[myNFF].value = f.elements[myNF].value;
				//alert(i);
			}
			ok = true;
		}
	} else {
		for (i=1; i<<?echo $qtde_item?>; i++){
			myNFF = "produto_nf_" + i;
			f.elements[myNFF].value = "";
		}
		ok = false;
	}

}

function verificaSerie(){
	if ($('#qtde_item').val() > 0 && $('#qtde_item').length > 0) {
		for (var i =0;i <$('#qtde_item').val() ;i++ ){
			if ($('#produto_referencia_'+i).length == 0 && $('#produto_serie_'+i).length == 0) {
				document.frm_os.btn_acao.value='gravar'; document.frm_os.submit();
			} else {
				var resposta =$.ajax({
					url:'ajax_verifica_serie.php',
					data:'produto_referencia='+$('#produto_referencia_'+i).val()+'&produto_serie='+$('#produto_serie_'+i).val(),
					async:false,
					complete: function(respostas){
					}
				}).responseText;

				if (resposta == 'erro'){
					if (confirm('Esse número de série e produto('+$('#produto_serie_'+i).val()+' - ' +$('#produto_referencia_'+i).val()+') foi identificado em nosso arquivo de vendas para locadoras. As locadoras têm acesso à pedido em garantia através da Telecontrol. Esse atendimento poderá ser gravado, e irá para um relatório gerencial. Deseja prosseguir?') == true){
						$('#locacao_serie').val('sim');
						document.frm_os.btn_acao.value='gravar';
						document.frm_os.submit();
					} else {
						$('#produto_referencia_'+i).val(' ');
						$('#produto_descricao_'+i).val(' ');
						$('#produto_serie_'+i).val(' ');
						$('#codigo_fabricacao_'+i).val(' ');
						$('#produto_voltagem_'+i).val(' ');
						$('#type_'+i).val(' ');
						break;
						return false;
					}
				} else {
					document.frm_os.btn_acao.value='gravar'; document.frm_os.submit();
				}
			}
		}
	} else {
		document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit();
	}
}
</script>


<style type="text/css">

tr.trline{
	height: 105%;
	vertical-align: bottom;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B;
}

.table {
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

.sucess {
  color: white;
  text-align: center;
  font: bold 16px Verdana, Arial, Helvetica, sans-serif;
  background-color: green;
}

</style>

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>


<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<?
if (strlen($msg_erro) > 0) {

	if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
		$sqlT =	"SELECT tbl_lista_basica.type, tbl_produto.referencia
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE tbl_produto.produto = $produto
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type, tbl_produto.referencia
				ORDER BY tbl_lista_basica.type;";
		$resT = pg_query($con,$sqlT);
		if (pg_num_rows($resT) > 0) {
			$s = pg_num_rows($resT) - 1;
			for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
				$typeT = pg_fetch_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			if (strpos($msg_erro,"É necessário informar o type para o produto") !== false) $msg_erro = "É necessário informar o type para o produto ".pg_fetch_result($resT,0,referencia).".<br>";
			if (strpos($msg_erro,"Type informado para o produto não é válido") !== false) $msg_erro = "Type informado para o produto ".pg_fetch_result($resT,0,referencia)." não é válido.<br>";
			$msg_erro .= "Selecione o Type: $result_type";
		}
	}
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?
}
$msg = $_GET['msg'];
if (strlen($msg) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='sucess'>
		<? echo $msg?>
	</td>
</tr>
</table>
<?
}

?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td valign="top" align="left">
			<!-- ------------- Formulário ----------------- -->
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
			<input type='hidden' name='sua_os' value='<? echo $sua_os; ?>'>
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
						<input  name="sua_os" class="frm" type="text" size="10" maxlength="10" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
					</td>
					<? } ?>
					<td nowrap align='center'>
						<input name="data_abertura" id="data_abertura" size="12" maxlength="10"value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 25/10/2004</font>
					</td>
					<td nowrap align='center'>
						<input name="nota_fiscal" size="6" maxlength="6"value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
					</td>
					<td nowrap align='center'>
						<input name="data_nf" id="data_nf" size="12" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 25/10/2004</font>
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
						<input class="frm" type="text" name="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_fone" size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>

<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">


			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do posto</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do posto</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" style="cursor:pointer;"></A>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>Anexar Nota Fiscal<br /></td>
				</tr>
				<tr>
					<td align="center"><input type="file" name="foto_nf" id="foto_nf" /></td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Linhas</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="obs" size="68" value="<? echo $obs ?>">
					</td>
					<td align='center'>
						<select size='1' class="frm" name='qtde_linhas' onChange="javascript: document.frm_os.submit(); ">
							<option value='20' <? if ($qtde_linhas == 20) echo 'selected'; ?>>20</option>
							<option value='30' <? if ($qtde_linhas == 30) echo 'selected'; ?>>30</option>
							<option value='40' <? if ($qtde_linhas == 40) echo 'selected'; ?>>40</option>
						</select>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<br>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_query($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

for ($i=0; $i<$qtde_item; $i++) {
	if ($i % 20 == 0) {
		#if ($i > 0) {
		#	echo "<tr>";
		#	echo "<td colspan='5'>";
		#	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";

		#	if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
		#		echo "<img src='imagens_admin/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
		#	}

		#	echo "</td>";
		#	echo "</tr>";
		#	echo "</table>";
		#}

		echo "<table width='650' border='0' cellpadding='0' cellspacing='2' align='center' bgcolor='#ffffff'>";
		echo "<tr class='menu_top'>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cod. Fabricação</font></td>\n";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número de série</font></td>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>";
#		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da NF</font></td>";
#		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número da NF</font> <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosNF()\" ALT='Selecionar todas' style='cursor:pointer;'></td>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Voltagem</font></td>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Type</font></td>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Embalagem Original</font></td>";
		echo "<td align='center' rowspan><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Sinal de Uso</font></td>";
		echo "</tr>";
	}

	if (strlen($os_revenda) > 0){
		if (@pg_num_rows($res_os) > 0) {
			$produto = trim(@pg_fetch_result($res_os,$i,produto));
		}

		if(strlen($produto) > 0){
			// seleciona do banco de dados
			$sql =	"SELECT tbl_os_revenda_item.os_revenda_item                          ,
							tbl_os_revenda_item.serie                                    ,
							tbl_os_revenda_item.nota_fiscal                              ,
							to_char(tbl_os_revenda_item.data_nf,'DD/MM/YYYY') AS data_nf ,
							tbl_os_revenda_item.capacidade                               ,
							tbl_os_revenda_item.type                                     ,
							tbl_os_revenda_item.embalagem_original                       ,
							tbl_os_revenda_item.sinal_de_uso                             ,
							tbl_os_revenda_item.codigo_fabricacao                        ,
							tbl_produto.referencia                                       ,
							tbl_produto.descricao                                        ,
							tbl_produto.voltagem
					FROM	tbl_os_revenda
					JOIN	tbl_os_revenda_item
					ON		tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	tbl_produto
					ON		tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	tbl_os_revenda_item.os_revenda = $os_revenda";
			$res = pg_query($con, $sql);

			if (@pg_num_rows($res) == 0) {
				$novo               = 't';
				$os_revenda_item    = $_POST["item_".$i];
				$referencia_produto = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$produto_descricao  = $_POST["produto_descricao_".$i];
#				$nota_fiscal        = $_POST["produto_nf_".$i];
#				$data_nf            = $_POST["data_nf_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
				$produto_voltagem   = $_POST["produto_voltagem_".$i];
				$codigo_fabricacao  = $_POST["codigo_fabricacao_".$i];
			} else {
				$novo               = 'f';
				$os_revenda_item    = pg_fetch_result($res,$i,os_revenda_item);
				$referencia_produto = pg_fetch_result($res,$i,referencia);
				$produto_descricao  = pg_fetch_result($res,$i,descricao);
				$serie              = pg_fetch_result($res,$i,serie);
#				$nota_fiscal        = pg_fetch_result($res,$i,nota_fiscal);
#				$data_nf            = pg_fetch_result($res,$i,data_nf);
				$capacidade         = pg_fetch_result($res,$i,capacidade);
				$type               = pg_fetch_result($res,$i,type);
				$embalagem_original = pg_fetch_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_fetch_result($res,$i,sinal_de_uso);
				$produto_voltagem   = pg_fetch_result($res,$i,voltagem);
				$codigo_fabricacao  = pg_fetch_result($res,$i,codigo_fabricacao);
			}
		} else {
			$novo = 't';
			$os_revenda_item    = $_POST["item_".$i];
			$referencia_produto = $_POST["produto_referencia_".$i];
			$serie              = $_POST["produto_serie_".$i];
			$produto_descricao  = $_POST["produto_descricao_".$i];
#			$nota_fiscal        = $_POST["produto_nf_".$i];
#			$data_nf            = $_POST["data_nf_".$i];
			$capacidade         = $_POST["produto_capacidade_".$i];
			$type               = $_POST["type_".$i];
			$embalagem_original = $_POST["embalagem_original_".$i];
			$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
			$produto_voltagem   = $_POST["produto_voltagem_".$i];
			$codigo_fabricacao  = $_POST["codigo_fabricacao_".$i];
		}
	} else {
		$novo               = 't';
		$os_revenda_item    = $_POST["item_".$i];
		$referencia_produto = $_POST["produto_referencia_".$i];
		$serie              = $_POST["produto_serie_".$i];
		$produto_descricao  = $_POST["produto_descricao_".$i];
#		$nota_fiscal        = $_POST["produto_nf_".$i];
#		$data_nf            = $_POST["data_nf_".$i];
		$capacidade         = $_POST["produto_capacidade_".$i];
		$type               = $_POST["type_".$i];
		$embalagem_original = $_POST["embalagem_original_".$i];
		$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
		$produto_voltagem   = $_POST["produto_voltagem_".$i];
		$codigo_fabricacao  = $_POST["codigo_fabricacao_".$i];
	}

	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";
	//alteração de layout Leandro
	echo "<tr class='trline'"; if ($linha_erro == $i AND strlen($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
	echo "<td align='center' nowrap><input class='frm' type='text' name='codigo_fabricacao_$i' size='9' maxlength='20' value='$codigo_fabricacao'></td>\n";
	echo "<td align='center' nowrap><input class='frm' type='text' name='produto_serie_$i' id='produto_serie_$i' size='10'  maxlength='20'  value='$serie'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='top' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\" style='cursor:pointer;'></td>\n";
	echo "<td align='center' nowrap><input class='frm' type='text' name='produto_referencia_$i' id='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_voltagem_$i,\"referencia\")' style='cursor:pointer;'></td>\n";
	echo "<td align='center' nowrap><input class='frm' type='text' name='produto_descricao_$i' size='50' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_voltagem_$i,\"descricao\")' style='cursor:pointer;'></td>\n";
#	echo "<td align='center'><input class='frm' type='text' name='data_nf_$i'  size='12'  maxlength='10'  value='$data_nf'></td>";
#	echo "<td align='center'><input class='frm' type='text' name='produto_nf_$i' size='9' maxlength='20' value='$nota_fiscal'>";
	if ($login_fabrica == 1) {
		echo "<td align='center'><input class='frm' type='text' name='produto_voltagem_$i' size='5' value='$produto_voltagem'></td>\n";
		echo "<td align='center' nowrap>\n";

                GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null,array("class"=>"frm", "index"=>$i));
                echo GeraComboType::getElement();

                echo "&nbsp; ";
		echo "</td>\n";
		echo "<td align='center' nowrap>\n";
		echo " &nbsp; <input class='frm' type='radio' name='embalagem_original_$i' value='t'"; if ($embalagem_original == 't' OR strlen($embalagem_original) == 0) echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</b></font> ";
		echo "<input class='frm' type='radio' name='embalagem_original_$i' value='f'"; if ($embalagem_original == 'f') echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</b></font> &nbsp; ";
		echo "</td>\n";
		echo "<td align='center' nowrap>\n";
		echo " &nbsp; <input class='frm' type='radio' name='sinal_de_uso_$i' value='t'"; if ($sinal_de_uso == 't') echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</font> ";
		echo "<input class='frm' type='radio' name='sinal_de_uso_$i' value='f'"; if ($sinal_de_uso == 'f'  OR strlen($sinal_de_uso) == 0) echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</font> &nbsp; ";
		echo "</td>\n";
	}
	echo "</tr>\n";
}
echo "<tr>\n";
echo "</table>\n";
?>

<br>

<center>
<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { verificaSerie()} else { alert ('Aguarde submissão') }" ALT='Gravar' border='0' style='cursor:pointer;'>

<? if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) { ?>
	<img src='imagens_admin/btn_apagar.gif' style='cursor:pointer' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }" ALT='Apagar a Ordem de Serviço' border='0'>
<? } ?>
</center>

</table>

</form>

<br>

<? include 'rodape.php'; ?>
