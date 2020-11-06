<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

// HD 65694 função apenas para gravar nota que está faltando de 18/04/2008 até 31/03/2009, depois precisa excluir essa função.
if (isset($_GET['gravarNotaFalta']) AND isset($_GET['ositem'])){

	$gravarNotaFalta = trim($_GET['gravarNotaFalta']);
	$ositem = trim($_GET['ositem']);
	$linha = $_GET['linha'];
	if (strlen($ositem)>0){
		if (strlen($gravarNotaFalta) > 0){
			if (strlen($erro) == 0) {
				$sql = "SELECT os_item FROM tbl_os_item_nf where os_item = $ositem";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) == 0) {
					$sqlx = "INSERT INTO tbl_os_item_nf(os_item,qtde_nf,nota_fiscal)values($ositem,1,$gravarNotaFalta) ;";
					$sqlx.= "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(tbl_os.os) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os_item = $ositem AND tbl_os_produto.os = tbl_os.os" ;
					$resx = @pg_query($con,$sqlx);
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro)==0) {
						echo 'Gravou|'.$linha;
					} else {
						echo 'Não Gravou|'.$linha;
					}
				}else{
					$sqlx = "UPDATE tbl_os_item_nf set nota_fiscal = $gravarNotaFalta WHERE os_item = $ositem;";
					$sqlx.= "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(tbl_os.os) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os_item = $ositem AND tbl_os_produto.os = tbl_os.os" ;
					$resx = @pg_query($con,$sqlx);
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro)==0) {
						echo 'Gravou|'.$linha;
					} else {
						echo 'Não Gravou|'.$linha;
					}

				}
			} else {
				echo $erro;
			}
		}
	}
	exit;
}
// HD 65694 função apenas para gravar nota que está faltando de 18/04/2008 até 31/03/2009, depois precisa excluir essa função.

if (isset($_GET['gravarDataNfFalta']) AND isset($_GET['ositem'])){
	$gravarDataNfFalta = trim($_GET['gravarDataNfFalta']);
	$ositem = trim($_GET['ositem']);
	$linha = $_GET['linha'];
	if (strlen($ositem)>0){
		if (strlen($gravarDataNfFalta) > 0){
			$gravarDataNfFalta = fnc_formata_data_pg($gravarDataNfFalta);
		}else{
			$gravarDataNfFalta = "";
		}
		if(strlen($gravarDataNfFalta) >0) {
			$sql = "SELECT os_item FROM tbl_os_item_nf where os_item = $ositem";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0) {
				echo "Por favor, preencher primeiro a nota fiscal";
			}else{
				$sqlx = "UPDATE tbl_os_item_nf set data_nf = $gravarDataNfFalta WHERE os_item = $ositem";
				$resx = @pg_query($con,$sqlx);
				$msg_erro = pg_errormessage($con);
				if (strlen($msg_erro)==0) {
					echo 'Gravou|'.$linha;
				} else {
					echo 'Não Gravou';
				}
			}
		}else{
			echo "Por favor, digite a data da nota";
		}
	}
	exit;
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj         = trim(pg_fetch_result($res,$i,cnpj));
				$nome         = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$os   = $_GET["os"];
$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if (isset($_POST['gravar_aud']) && empty($select_acao)) {
	$msg_erro = "Selecione a ação para as OSs marcadas";
}

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include_once('../anexaNF_inc.php');

if(strlen($btn_acao)>0 AND strlen($select_acao)>0 AND $select_acao != "gravar_nf_envio"){

	$qtde_os     = trim($_POST["qtde_os"]);
	$observacao  = trim($_POST["observacao"]);

	for ($x=0;$x<$qtde_os;$x++){

		$xxos = trim($_POST["check_".$x]);

		if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){
			$res_os = pg_query($con,"BEGIN TRANSACTION");

			# Retirar a OS de intervenção - Fabio - HD 5876
			$sql = "SELECT status_os
					FROM tbl_os_status
					WHERE status_os IN (87,88)
					AND os=$xxos
					ORDER BY data DESC LIMIT 1";
			$res_os = pg_query($con,$sql);
			if (pg_num_rows($res_os)>0){
				$status_da_os = trim(pg_fetch_result($res_os,0,status_os));
				if ($status_da_os == 87){
					$sql = "INSERT INTO tbl_os_status
							(os,status_os,data,observacao,admin)
							VALUES ($xxos,88,current_timestamp,'OS liberada',$login_admin)";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}

			### RECUSADA --------------------------------------------
			if($select_acao == "13"){
				if(strlen($observacao) > 0){

					if ($login_fabrica == 1 && trim($interno_posto) == 'interno') {
						$campoInsert = ", campos_adicionais";
                        $valorInsert = ["oculta_historio_posto"=>true];
                        $valorInsert = ", '".json_encode($valorInsert)."'";
					}

					$sql="UPDATE tbl_os_troca set status_os = 13 where os = $xxos;";
					$res= pg_query($con, $sql);
					$observacao = str_replace("'","\'",$observacao);

					$sql = "INSERT INTO tbl_os_status (
										os        ,
										status_os ,
										observacao,
										admin,
										status_os_troca
										$campoInsert
									) VALUES (
										'$xxos'      ,
										'13'         ,
										E'$observacao',
										$login_admin,
											't'
										$valorInsert
									);";
					$res = pg_query ($con,$sql);

					if ($login_fabrica == '1') {
              if (!empty($_POST['sem_nf'])) {
                  $url = temNF($xxos, 'url');

                  if (array_key_exists(0, $url)) {
                      $tmp = explode('?', $url[0]);
                      $link = str_replace('http://', '', $tmp[0]);
                      $path = explode('/', $link);
                      $shift = array_shift($path);
                      $arquivoNF = implode('/', $path);

                      excluirNF($arquivoNF);
                  }

              }
          }
				}else{
					$msg_erro .= "Por favor preencha o motivo da recusa.";
				}
			}
			## EXCLUIDA--------------------------------------------
			if($select_acao=="15"){
				if(strlen($observacao) > 0){
					$sql="UPDATE tbl_os_troca set status_os = 15 where os = $xxos; ";
					$res= pg_query($con, $sql);
					$observacao = str_replace("'","\'",$observacao);

					$sql = "INSERT INTO tbl_os_status (
										os        ,
										status_os ,
										observacao,
											admin,
										status_os_troca
									) VALUES (
										'$xxos'      ,
										'15'         ,
										E'$observacao',
										$login_admin,
											't'
									);";
					$res = pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (strlen($msg_erro) == 0) {
						// hd18827
						$sql = "UPDATE tbl_os SET excluida = true,admin_excluida=$login_admin
									WHERE  tbl_os.os           = $xxos
									AND    tbl_os.fabrica      = $login_fabrica;";
						$res = pg_query($con,$sql);

						$sql = "SELECT fn_os_excluida($xxos,$login_fabrica,$login_admin);";
						$res = @pg_query ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}else{
					$msg_erro .= "Por favor preencha o motivo da exclusão.";
				}
			}
			## APROVADA --------------------------------------------
			if($select_acao=="19"){
				if(strlen($observacao) == 0){
                    $sqlVerifica = "SELECT  tbl_os.admin
			    	    FROM    tbl_os
				    JOIN tbl_admin ON tbl_os.admin = tbl_admin.admin AND tbl_admin.fabrica = $login_fabrica
				    WHERE   os = $xxos
				    AND     admin_sap IS TRUE
                    ";
					$resVerifica = pg_query($con,$sqlVerifica);
					$temAdmin="";
					if(pg_num_rows($resVerifica) > 0){
	                    $temAdmin = pg_fetch_result($resVerifica,0,admin);
					}

                    if(strlen($temAdmin) == 0){
                        $sql="UPDATE tbl_os_troca set status_os = 19 where os = $xxos; ";
                        $res= pg_query($con, $sql);
                    }

					$sql = "INSERT INTO tbl_os_status (
										os        ,
										status_os ,
										observacao,
										admin,
										status_os_troca
									) VALUES (
										'$xxos'      ,
										'19'         ,
										'OS Aprovada pelo Fabricante',
										$login_admin,
										't'
									);";
					$res = pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);

					// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
					$sql = "SELECT os_produto FROM tbl_os_produto WHERE os=$xxos";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) > 0){
						$os_produto = pg_fetch_result($res, 0, 0);

						$sql = " UPDATE tbl_os_item SET
									admin=$login_admin
								WHERE
								os_produto=$os_produto ";
						$res = pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);
					}else{
							# Recupera informações do produto para cadastrar como PEÇA para gerar PEDIDO - HD 10513
						#HD 15401
						$sql = "SELECT	tbl_produto.produto,
										tbl_produto.referencia,
										tbl_produto.referencia_fabrica,
										tbl_produto.descricao,
										tbl_produto.ipi
								FROM tbl_os
								JOIN tbl_produto USING(produto)
								WHERE    os = $xxos
								AND fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res)>0){
							$produto            = trim(pg_fetch_result($res,0,produto));
							$produto_os         = trim(pg_fetch_result($res,0,produto));
							$produto_referencia = trim(pg_fetch_result($res,0,referencia));
							$referencia_fabrica = trim(pg_fetch_result($res,0,referencia_fabrica));
							$produto_descricao  = trim(pg_fetch_result($res,0,descricao));
							$produto_ipi        = trim(pg_fetch_result($res,0,ipi));
						}else{
							$msg_erro .= "Erro inesperado. Tente novamente.";
						}

						//hd 21461 - se escolheu produto opcional

						if (strlen($msg_erro) == 0) {
							$sql = "SELECT	tbl_produto.produto,
											tbl_produto.referencia,
											tbl_produto.referencia_fabrica,
											tbl_produto.descricao,
											tbl_produto.ipi
									FROM tbl_os_troca
									JOIN tbl_produto USING(produto)
									WHERE    os = $xxos
									AND fabric  = $login_fabrica";
							$res = pg_query($con,$sql);
							if (pg_num_rows($res) > 0) {
								$produto            = trim(pg_fetch_result($res,0,produto));
								$produto_referencia = trim(pg_fetch_result($res,0,referencia));
								$referencia_fabrica = trim(pg_fetch_result($res,0,referencia_fabrica));
								$produto_descricao  = trim(pg_fetch_result($res,0,descricao));
								$produto_ipi        = trim(pg_fetch_result($res,0,ipi));
							}
						}

						$sql = "SELECT peca, referencia
								FROM tbl_peca
								WHERE referencia = '$referencia_fabrica'
								AND   fabrica     = $login_fabrica ; ";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (pg_num_rows($res) == 0) {
							if (strlen ($produto_ipi) == 0){
								$produto_ipi = "0";
							}

							$sql = "SELECT peca
									FROM tbl_peca
									WHERE fabrica    = $login_fabrica
									AND   referencia = '$referencia_fabrica'
									LIMIT 1;";
							$res = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							if (pg_num_rows($res) > 0) {
								$peca = pg_fetch_result($res,0,0);

								$sql = "UPDATE tbl_peca SET
											ipi = $produto_ipi
										WHERE fabrica = $login_fabrica
										AND   peca    = $peca" ;
								$res = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);

							}else{
								$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, ipi, origem, produto_acabado) VALUES ($login_fabrica, '$referencia_fabrica', '$produto_descricao' , $produto_ipi , 'NAC','t')" ;
								$res = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);

								$sql = "SELECT CURRVAL ('seq_peca')";
								$res = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);
								$peca = pg_fetch_result($res,0,0);
							}
						}else{
							$peca = pg_fetch_result($res,0,peca);
						}

						$sql = "SELECT lista_basica
								FROM tbl_lista_basica
								WHERE peca  = $peca
								AND produto = $produto_os;" ;
						$res = @pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (strlen($msg_erro)==0 and pg_num_rows($res)==0)
							{
							$sql = "INSERT INTO tbl_lista_basica (fabrica, produto,peca,qtde) VALUES ($login_fabrica, $produto_os, $peca, 1);" ;
							$res = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
						$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $xxos";
						$res = @pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (pg_num_rows($res) == 0) {
							$sql = "INSERT INTO tbl_os_produto (os, produto) VALUES ($xxos, $produto_os);";
							$res = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT CURRVAL ('seq_os_produto')";
							$res = @pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$os_produto = @pg_fetch_result($res,0,0);
						}else{
							$os_produto = @pg_fetch_result($res,0,0);
						}

						$sql = "SELECT servico_realizado
								FROM tbl_servico_realizado
								WHERE troca_produto
								AND fabrica = $login_fabrica" ;
						$res = @pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if(@pg_num_rows($res) > 0){
							$servico_realizado = pg_fetch_result($res,0,0);
						}

						if(strlen($servico_realizado)==0) {
							$msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";
						}

						if(strlen($msg_erro)==0){
							$sql = "SELECT os_produto
									FROM tbl_os_item
									WHERE os_produto = $os_produto
									AND   peca = $peca";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0){
								$sql = "INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado, admin) VALUES ($os_produto, $peca, 1,$servico_realizado, $login_admin)";
								$res = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
					}

					$sql_2 = "UPDATE tbl_os SET
								solucao_os         = 111,
								defeito_constatado = 1506,
								defeito_reclamado  = 876
							WHERE os = $xxos ; ";
					$res_2 = pg_query($con,$sql_2);

					$sql_2 = "UPDATE tbl_os SET
								solucao_os         = 117
							WHERE os = $xxos
							AND troca_faturada IS TRUE";
					$res_2 = pg_query($con,$sql_2);

					// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
					// VÁRIAS LINHAS ABAIXO FORAM APAGADAS POIS ESTÃO SENDO EXECUTADAS EM os_cadastro_troca.php
					// EM CASO DE NECESSIDADE, VERIFICAR ARQUIVO EM NAO_SYNC DE 20091005 OU ANTERIOR
					// **LINHAS APAGADAS: hd 21461
					// **LINHAS APAGADAS: Recupera informações do produto para cadastrar como PEÇA para gerar PEDIDO - HD 10513
					// **LINHAS APAGADAS: HD 15401
				}else{
					$msg_erro .= "Para aprovação não precisa ser preenchido o motivo.";
				}
			}

			## VOLTAR PARA APROVAÇÃO hd 16334 ----------------------------------------
			if($select_acao == "volta_aprovacao"){
				$sql="UPDATE tbl_os_troca set status_os = null where os = $xxos;";
				$res= pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen($msg_erro)==0){

				$res = pg_query($con,"COMMIT TRANSACTION");

			}else{

				$res = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

# Coloquei gravar_nf_envio para saber que o ADMIN está gravando o número da nota de envio, valor, etc...
# HD 7474 - Fabio
if(strlen($btn_acao)>0 AND $select_acao == "gravar_nf_envio")	{
	$qtde_os     = $_POST["qtde_os"];
	$select_acao = $_POST["select_acao"];
	$observacao  = trim($_POST["observacao"]);

	for ($x=0;$x<$qtde_os;$x++){
		$xxos = $_POST["check_".$x];

		if (strlen($xxos)>0 AND strlen($msg_erro) == 0){
			$sql = "SELECT  tbl_os.posto                    ,
								tbl_os.sua_os               ,
								tbl_os.serie                ,
								tbl_os_troca.total_troca    ,
								tbl_os_troca.ri             ,
								tbl_produto.descricao       ,
								tbl_produto.referencia      ,
								tipo_atendimento
							FROM tbl_os_troca
							JOIN tbl_os      ON tbl_os.os = tbl_os_troca.os AND tbl_os.fabrica = $login_fabrica
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_os.os = '$xxos'
						AND tbl_os.fabrica = '$login_fabrica';";
			$res = pg_query($con, $sql);

			$sua_os_posto       = pg_fetch_result($res,0,sua_os);
			$login_posto        = pg_fetch_result($res,0,posto);
			$valor_pago         = pg_fetch_result($res,0,total_troca);
			$pedido             = pg_fetch_result($res,0,ri);
			$produto_referencia = trim(pg_fetch_result($res,0,referencia));
			$produto_descricao  = trim(pg_fetch_result($res,0,descricao));
			$tipo_atendimento   = pg_fetch_result($res,0,tipo_atendimento);


			$valor_total = trim($_POST["valor_".$x]);
			// HD 18475
			if($tipo_atendimento == 17 or $tipo_atendimento == 35){ $valor_total = 0; }
//			if(strlen($valor_total) == 0){$valor_total = '2,50';}
//			if($valor_total == 0 AND strlen($valor_pago) == 0 AND $tipo_atendimento == 18) {
//				$msg_erro = 'Não foi digitado o valor total.';
//			}

			$valor_total = str_replace(",",".",$valor_total);
			$data_envio = $_POST["data_envio_".$x];

//GRAVA PEDIDO NA OS.
			$pedido = trim($_POST["pedido_".$x]);
			if(strlen($msg_erro) == 0 AND strlen($pedido) > 0){
				$sql_2 = "UPDATE tbl_os_troca SET ri = '$pedido' WHERE os = '$xxos'; ";
				$res_2 = pg_query($con,$sql_2);
			}

//GRAVA VALOR NA OS.
			if(strlen($msg_erro) == 0 AND $valor_pago == 0){
//				$valor_total = '2.00';
				if(strlen($valor_total) > 0){
					$sql_2 = "UPDATE tbl_os_troca SET total_troca = $valor_total WHERE os = '$xxos'; ";
					$res_2 = pg_query($con,$sql_2);
				}
//				else{
//					$msg_erro = "Valor já definido anteriormente.";
//				}
			}

//GRAVA NOTA FISCAL E DATA NA OS
			$nf_os = $_POST["nf_".$x];

			if(strlen($nf_os) > 0) {
				$xdata_envio = fnc_formata_data_pg($data_envio);

				$sql_2 = "UPDATE tbl_os SET
							nota_fiscal_saida  = '$nf_os'
						WHERE os = '$xxos' ; ";
				$res_2 = pg_query($con,$sql_2);
			}

			if(strlen($data_envio) > 0) {
				$xdata_envio = fnc_formata_data_pg($data_envio);

				echo $sql_2 = "UPDATE tbl_os SET
								data_nf_saida      = $xdata_envio
						WHERE os = '$xxos' ; ";
				$res_2 = pg_query($con,$sql_2);

				// HD 30781
				$sql_3= "UPDATE tbl_pedido_item
							SET qtde_faturada = 1
						FROM tbl_os_troca
						WHERE tbl_os_troca.pedido_item=tbl_pedido_item.pedido_item
						AND   tbl_os_troca.os= '$xxos' ";
				$res_3 = pg_query($con,$sql_3);
			}
			$valor_pago = '0';

			if ($login_fabrica == 1 && ($_POST["ad_bd_".$x] != "" || $_POST["coleta_bd_".$x] != "") && strlen(trim($msg_erro)) == 0) {
				$cp = [];
				$ad_bd     = $_POST["ad_bd_".$x];
				$coleta_bd = $_POST["coleta_bd_".$x];

				$sql_extra = "SELECT campos_adicionais AS cp FROM tbl_os_campo_extra WHERE os = $xxos AND fabrica = $login_fabrica";
				$res_extra = pg_query($con, $sql_extra);
				if (pg_num_rows($res_extra) > 0) {
					$cp = json_decode(pg_fetch_result($res_extra, 0, 'cp'), true);
					if (!empty($ad_bd)) {
						$cp['numero_ad'] = $ad_bd;
					}
					if (!empty($coleta_bd)) {
						$cp['numero_coleta'] = $coleta_bd;
					}

				} else {
					if (!empty($ad_bd)) {
						$cp['numero_ad'] = $ad_bd;
					}
					if (!empty($coleta_bd)) {
						$cp['numero_coleta'] = $coleta_bd;
					}					
				}
				
				$cp = json_encode($cp);
				$sql_extra_up = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$cp' WHERE os = $xxos AND fabrica = $login_fabrica";
				$res_extra_up = pg_query($con, $sql_extra_up);
			}
		}
	}
}

$layout_menu = "auditoria";
$title = "APROVAÇÃO ORDEM DE SERVIÇO DE TROCA";

include "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>

<script language="JavaScript">

function mostra_filtro(){
	var check = document.getElementById('aprova').value;

	if(check.length>0){
		document.getElementById('mostrar_filtro').style.display='block';
	}else{
		document.getElementById('mostrar_filtro').style.display='none';
	}
}



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

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;
	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
				}
				cont++;
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+cont)) {
					document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
				}
				cont++;
			}
		}


	}
}

$(document).on("click",".btn-ver-anexo",function(){
	var links = $(this).data("links");

	$.each(links, function (key, value) {
		window.open(value, '_blank');
	});
});

function setCheck(theCheckbox,mudarcor,cor){
	if (document.getElementById(theCheckbox)) {
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

function verificarAcao(combo){
	if (document.getElementById('observacao')){
		if (combo.value == '19'){
			document.getElementById('observacao').disabled = true;
            <?if ($login_fabrica == 1){ ?>
			document.getElementById('observacao').value = "";
            document.getElementById('div_sem_nf').style.display = "none";
            document.getElementById('sem_nf').checked = "";
            <? } ?>
		}else if(combo.value == '13'){
            document.getElementById('observacao').disabled = false;
            <?if ($login_fabrica == 1){ ?>
							var obs_recusa = $("#observacao").val();

							if(obs_recusa == ''){
								$("#observacao").focus();
						        return;
							}
							//document.getElementById('observacao').value = "";
        			//document.getElementById('div_sem_nf').style.display = "inline";

            <? } ?>
        }else{
			document.getElementById('observacao').disabled = false;
            <?if ($login_fabrica == 1){ ?>
			document.getElementById('observacao').value = "";
            document.getElementById('div_sem_nf').style.display = "none";
            document.getElementById('sem_nf').checked = "";
            <? } ?>
		}
	}
}
<?
if ($login_fabrica == 1){
?>
function marcarSemNota(check){
    if(check == true){
        //document.getElementById('observacao').readOnly = true;
        document.getElementById('observacao').value = "SEM NOTA FISCAL";
    }else{
        document.getElementById('observacao').readOnly = false;
        document.getElementById('observacao').value = "";
    }
}
$(function(){ 
	$(".ad").mask("SS999999");
});

function valida_ad(ad) {
	var valor_ad = $("input[name="+ad+"]").val()
	if (valor_ad != undefined && valor_ad.length > 0) {
		if (valor_ad.length != 8) {
			alert("Número de AD Inválido");
			$("input[name="+ad+"]").val("").focus();
		}
	}
}

<?
}
?>
</script>

<script language="JavaScript">




function atualizaDatanf(data,linha,ositem){
 	if (data != '__/__/____') {
		var end = 'aprova_os_troca.php?gravarDataNfFalta='+data+'&ositem='+ositem+'&linha='+linha;
		$('#div_msg_'+linha).html('Aguarde...');
		$('#div_msg_'+linha).show();
		requisicaoHTTP('GET',end, true , 'retorno_atu');
	}  else {
		alert('Para Atualizar a data é necessário digitar uma data válida');
	}
}

function retorno_atu(campos){
	campos_array = campos.split("|");
	linha = campos_array[1];
	if (campos_array[0]=='Gravou') {
		$('#div_msg_'+linha).html('Atualizada com Sucesso');
	} else {
		$('#div_msg_'+linha).html('Erro ao Gravar');
	}
}


function atualizaNf(nota,linha,ositem){
	if (nota.length > 0) {
		var end = 'aprova_os_troca.php?gravarNotaFalta='+nota+'&ositem='+ositem+'&linha='+linha;
		$('#div_msg2_'+linha).html('Aguarde...');
		$('#div_msg2_'+linha).show();
		requisicaoHTTP('GET',end, true , 'retorno_atu2');
	}  else {
		alert('Para Atualizar a data é necessário digitar uma data válida');
	}
}

function retorno_atu2(campos){
	campos_array = campos.split("|");
	linha = campos_array[1];
	if (campos_array[0]=='Gravou') {
		$('#div_msg2_'+linha).html('Atualizada com Sucesso');
	} else {
		$('#div_msg2_'+linha).html('Erro ao Gravar');
	}

	setTimeout('$("#div_msg2_'+linha+'").fadeOut()',3000);
}

function verificaChecked() {
	var isChecked = false;

	$("input[name^=check_]").each(function() {
		if ($(this).is(":checked")) {
			isChecked = true;
		}
	});

	if (isChecked) {
		$("#frm_pesquisa2").prop("disabled", false);
	} else {
		$("#frm_pesquisa2").prop("disabled", true);
	}
}

$().ready(function() {
	verificaChecked()

	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("input[name^=check_]").click(function() {
		verificaChecked();
	});

	$(".btn-alterar").click(function(){

		var os_troca = $(this).attr("os_troca");

	    Shadowbox.open({
            content: "os_cadastro_troca_black.php?os="+os_troca+"&shadowbox=t",
            player: "iframe",
            width: 1500,
            height: 1000
        });
	});
});


function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}
</script>

<script>
function abreObs(os,codigo_posto,sua_os){

	Shadowbox.open({
        content: "obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,
        player: "iframe",
        title: "Observação do Posto",
        enableKeys: false,
        width: 500,
        height: 300
    });

}
</script>

<?

$btn_acao       = $_POST['btn_acao'];

if($btn_acao == 'Pesquisar'){
	$pedido              = trim($_POST['pedido']);
	$data_inicial        = $_POST['data_inicial'];
	$data_final          = $_POST['data_final'];
	$aprovacao           = $_POST['aprova'];
	$troca               = $_POST['troca'];
	$posto_codigo        = $_POST['posto_codigo'];
	$os_troca_especifica = trim($_POST['os_troca_especifica']);
	$modelo_produto      = trim($_POST['modelo_produto']);
	$aprova              = $_POST['aprova'];
	$interno_posto       = $_POST['interno_posto'];
	//$callcenter        	 = $_POST['callcenter'];
	$os_com_anexo        = $_POST['os_com_anexo'];

	if (strlen($pedido) == 0){
		if(empty($os_troca_especifica)) {
			if(empty($aprova) ) {
				$msg_erro = "Selecione o Status da OS para pesquisar";
			}
		}
		if(strlen($os_troca_especifica) > 0 AND strlen($os_troca_especifica) <= 10){

			$msg_erro = "Informe o número completo da Ordem de Serviço";

		}

		if((strlen($data_inicial) == 0 or strlen($data_final) == 0) and strlen($os_troca_especifica) == 0 && ($login_fabrica == 1 && $aprova != "aprovacao")) {
			$msg_erro = "Data Inválida";
			$erro = "data";
		}

		if(strlen($data_inicial) > 0 and strlen($data_final) > 0 and strlen($os_troca_especifica) == 0) {

			if(strlen($msg_erro)==0){
				list($di, $mi, $yi) = explode("/", $data_inicial);
				if(!checkdate($mi,$di,$yi)){
					$msg_erro = "Data Inválida";
					$erro = "data";
				}
			}

			if(strlen($msg_erro)==0){
				list($df, $mf, $yf) = explode("/", $data_final);
				if(!checkdate($mf,$df,$yf)) {
					$msg_erro = "Data Inválida";
					$erro = "data";
				}
			}

			if(strlen($msg_erro)==0){
				$aux_data_inicial = "$yi-$mi-$di";
				$aux_data_final = "$yf-$mf-$df";
			}
			if(strlen($msg_erro)==0){
				if(strtotime($aux_data_final) < strtotime($aux_data_inicial)
				or strtotime($aux_data_final) > strtotime('today')){
					$msg_erro = "Data Inválida.";
					$erro = "data";
				}
			}

			if(strlen($msg_erro)==0){
				if (strtotime($aux_data_inicial.'+3 month') < strtotime($aux_data_final) ) {
						$msg_erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
						$erro = "data";
				}
			}

			$xdata_inicial = formata_data ($data_inicial);
			$xdata_inicial = $xdata_inicial." 00:00:00";

			$xdata_final = formata_data ($data_final);
			$xdata_final = $xdata_final." 23:59:59";
		}
	}

	if(strlen($posto_codigo) > 0){
		$sql = "SELECT posto
			 FROM tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
			AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$posto = pg_fetch_result($res,0,'posto');
			$cond_posto = " AND tbl_os.posto = $posto ";

		}else{
			$msg_erro = "Posto informado nao encontrado";
		}
	}else if(strlen($posto_codigo) == 0 AND strlen($os_troca_especifica) > 0 and empty($msg_erro) ){

		$posto_codigo = substr($os_troca_especifica, 0, 5);
		$sql = "SELECT posto
			 FROM tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
			AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0) {
			$posto = pg_fetch_result($res,0,'posto');
			$cond_posto = " AND tbl_os.posto = $posto ";

		}else{

			$msg_erro = "Informe o número completo da Ordem de Serviço";

		}
	}
}

if($btn_acao == 'Listar todas') {
	$aprova = 'aprovacao';
}

if(strlen($msg_erro) > 0){
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$x = explode('ERROR: ',$msg_erro);
		$msg_erro = $x[1];
	}
?>
	<div class="alert alert-danger">
		<h4><?= $msg_erro ?></h4>
	</div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_pesquisa" class='form-search form-inline tc_formulario' method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<div class="titulo_tabela">Parâmetros de Pesquisa</div>
<br />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=($erro == 'data') ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" class="span12" value="<? echo $data_inicial ?>" />
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=($erro == 'data') ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='span4'>
						<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="span12" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>O.S Troca Específica</label>
					<div class='controls controls-row'>
						<div class='span4'>
								<input type="text" name="os_troca_especifica" id="os_troca_especifica" size="13" value="<?echo $os_troca_especifica?>" class="frm">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Modelo Produto</label>
				<div class='controls controls-row'>
					<div class='span4'>
							<input type="text" name="modelo_produto" id="modelo_produto" size="35" value="<?echo $modelo_produto?>" class="frm">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="posto_codigo" id="codigo_posto" size="9" value="<?echo $posto_codigo?>" class="span12">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<<input type="text" name="posto_nome" id="descricao_posto" size="31" value="<?echo $posto_nome?>" class="frm">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Número do Pedido</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="pedido" id="pedido" size="15" value="<?echo $pedido?>" class="frm">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<br />
	<div class="titulo_tabela">Tipo de Troca</div>
	<br />
	<?php if ($login_fabrica == 1) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span2'>
					 <label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='interno' <? if(trim($interno_posto) == 'interno') echo "checked='checked'"; ?>>
				        Troca Interna
				    </label>
				</div>
				<div class='span2'>
				    <label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='posto' <? if(trim($interno_posto) == 'posto' or empty($interno_posto)) echo "checked='checked'"; ?>>
				        Troca de Posto
				    </label>
				</div>
				<div class='span2'>
					<label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='callcenter' <? if(trim($interno_posto) == 'callcenter') echo "checked='checked'"; ?>>
				        Troca Call-Center
				    </label>
				</div>
				<div class='span3'>
					<label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='satisfacao' <? if(trim($interno_posto) == 'satisfacao') echo "checked='checked'"; ?>>
				        Troca Satisfação 90 Dias
				    </label>
				</div>
			</div>
	<?php } else { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span3'>
					 <label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='interno' <? if(trim($interno_posto) == 'interno') echo "checked='checked'"; ?>>
				        Troca Interna
				    </label>
				</div>
				<div class='span3'>
				    <label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='posto' <? if(trim($interno_posto) == 'posto' or empty($interno_posto)) echo "checked='checked'"; ?>>
				        Troca de Posto
				    </label>
				</div>
				<div class='span3'>
					<label class="radio">
				        <INPUT TYPE="radio" NAME="interno_posto" value='callcenter' <? if(trim($interno_posto) == 'callcenter') echo "checked='checked'"; ?>>
				        Troca Call-Center
				    </label>
				</div>
				<div class='span2'></div>
			</div>
	<?php } ?>
	<div class="titulo_tabela">Status da OS</div>
	<br />
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span2'>
			 <label class="radio">
		        <INPUT TYPE="radio" NAME="aprova" ID="aprova" value='aprovacao'  <?=(trim($aprova) == 'aprovacao' || empty($aprova)) ? "checked='checked'" : ""; ?>>Em aprovação:
		    </label>
		</div>
		<div class='span2'>
		    <label class="radio">
		        <INPUT TYPE="radio" NAME="troca" value='faturada' <? if(trim($troca) == 'faturada') echo "checked='checked'"; ?>>Faturadas
		    </label>
		</div>
		<div class='span2'>
		    <label class="radio">
		        <INPUT TYPE="radio" NAME="troca" value='garantia' <? if(trim($troca) == 'garantia') echo "checked='checked'"; ?>>Garantias
		    </label>
		</div>
		<div class='span2'>
		    <label class="radio">
		        <INPUT TYPE="radio" NAME="troca" value='cortesia' <? if(trim($troca) == 'cortesia') echo "checked='checked'"; ?>>Cortesias
		    </label>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span3'>
			 <label class="radio">
		        <INPUT TYPE="radio" NAME="aprova" value='aprovadas' <?
						if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovada com Pedido
		    </label>
		</div>
		<div class='span3'>
			 <label class="radio">
		        <INPUT TYPE="radio" NAME="aprova" value='aprovadas_sem_pedido' <? if(trim($aprova) == 'aprovadas_sem_pedido') echo "checked='checked'"; ?>>Aprovada sem Pedido
		    </label>
		</div>
		<div class='span3'>
			 <label class="radio">
		        <INPUT TYPE="radio" NAME="aprova" value='aprovadas_com_nf' <?
						if(trim($aprova) == 'aprovadas_com_nf') echo "checked='checked'"; ?>>Aprovada com Número de NF
		    </label>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span1'></div>
		<div class='span3'>
			 <label class="radio">
		        <INPUT TYPE="radio" NAME="aprova" value='excluida' <?
						if(trim($aprova) == 'excluida') echo "checked='checked'"; ?>>Excluída
		    </label>
		</div>
		<div class='span3'>
			 <label class="radio">
		        <INPUT TYPE="radio" NAME="aprova" value='recusada' <?
						if(trim($aprova) == 'recusada') echo "checked='checked'"; ?>>Recusada
		    </label>
		</div>
		<div class='span3'>
			<INPUT TYPE="checkbox" NAME="os_com_anexo" value='os_com_anexo' id='os_com_anexo' <?
						if(strlen($os_com_anexo) > 0 ) echo "checked='checked'"; ?>><label for='os_com_anexo'>OS com anexo</a>
		</div>
		<div class='span2'></div>
	</div>
	<?php if ($login_fabrica == 1) { ?>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class='span5'>
				 <label class="radio">
			        <INPUT TYPE="radio" NAME="aprova" value='aprovadas_com_nf_satisfacao' <?
							if(trim($aprova) == 'aprovadas_com_nf_satisfacao') echo "checked='checked'"; ?>>Aprovada com número de NF (Satisfação 90 dias)
			    </label>
			</div>
		</div>
	<?php } ?>
	<div style="width: 75%;position: relative;left: 12.5%;">
		<hr />
	</div>
	<input type="submit" class="btn btn-default" name="btn_acao" value="Pesquisar">
	<input type="submit" class="btn btn-primary" name="btn_acao" value="Listar todas">
	<br /><br />
</form>
<?

## Aqui começa o PROCESSO DE PESQUISA
if (($btn_acao == 'Pesquisar' and strlen($msg_erro)==0) || isset($_POST['gravar_aud']) || $btn_acao =='Listar todas' ) {
	$codigo_posto = $_POST['posto_codigo'];

	//HD 222050: Para a Black existe a possibilidade de trocar um produto por vários
	//com isto duplicou as linhas no relatório. Para resolver foi necessário
	//separar as colunas da seleção que geravam duplicações na sql anterior, mesmo
	//com DISTINCT

	if($login_fabrica == 1){
		$os_campo_extra = " left join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and tbl_os.fabrica = $login_fabrica "; 
		$campo_valores_adicionais = " tbl_os_campo_extra.campos_adicionais, ";
	}

	$sql="
            SELECT  DISTINCT
                    tbl_os.os,
					tbl_os.sua_os                                               ,
					tbl_os.os_reincidente                  AS reincidencia      ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_revenda                                   ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao    ,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.revenda_nome                                         ,
					tbl_os.nota_fiscal_saida                                    ,
					tbl_os_troca.situacao_atendimento AS tipo_atendimento      ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_os_troca.total_troca                                    ,
					tbl_os_troca.ri                                             ,
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.descricao              AS produto_descricao     ,
					tbl_produto.voltagem                                        ,
					(SELECT tbl_admin.login FROM tbl_os_status LEFT JOIN tbl_admin ON tbl_admin.admin= tbl_os_status.admin WHERE os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1)      AS admin_nome,
					tbl_os_troca.data                                           ,
					tbl_os_troca.observacao                                     ,
					(SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data FROM tbl_os_status WHERE os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1)      AS data_avaliacao,
					ADM.login                                    AS admin_digitou,
					tbl_os.excluida                                              ,
					tbl_os_troca.produto                AS produto_troca         ,
					$campo_valores_adicionais
					tbl_os.defeito_reclamado_descricao                           ,
					tbl_os.nf_os
            FROM    tbl_os_troca
            JOIN    tbl_os                  ON  tbl_os.os                               = tbl_os_troca.os
       $os_campo_extra
       LEFT JOIN    tbl_os_produto          ON  tbl_os_produto.os                       = tbl_os.os
       LEFT JOIN    tbl_os_item             ON  tbl_os_item.os_produto                  = tbl_os_produto.os_produto
       LEFT JOIN    tbl_os_item_nf          ON  tbl_os_item_nf.os_item                  = tbl_os_item.os_item
       LEFT JOIN    tbl_pedido              ON  tbl_pedido.pedido                       = tbl_os_item.pedido
                                            AND tbl_pedido.fabrica                      = $login_fabrica
            JOIN    tbl_produto             ON  tbl_produto.produto                     = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
            JOIN    tbl_posto               ON  tbl_os.posto                            = tbl_posto.posto
            JOIN    tbl_posto_fabrica       ON  tbl_posto.posto                         = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica               = $login_fabrica AND credenciamento <> 'DESCREDENCIADO'
       LEFT JOIN    tbl_status_os           ON  tbl_status_os.status_os                 = tbl_os_troca.status_os
       LEFT JOIN    tbl_admin ADM           ON  ADM.admin                               = tbl_os.admin
            WHERE   tbl_os_troca.fabric = $login_fabrica
            AND     tbl_os.fabrica      = $login_fabrica
				$cond_posto
				";

	if (strlen($pedido) == 0){
		if($aprova <> "excluida"){
			$sql .= " AND   tbl_os.excluida IS NOT TRUE ";
		}

		if($aprova == "aprovadas"){
			$sql .=" AND tbl_os_troca.status_os = 19
					 AND (tbl_os_troca.ri IS NOT NULL OR tbl_os_item.pedido IS NOT NULL)
					 AND (
						(tbl_os.nota_fiscal_saida IS NULL OR tbl_os.data_nf_saida IS NULL)
						AND
						(tbl_os_item_nf.nota_fiscal IS NULL OR tbl_os_item_nf.data_nf IS NULL )
						)";
		}

		if($aprova == "aprovadas_sem_pedido"){
			$sql .=" AND tbl_os_troca.status_os = 19
					 AND tbl_os_troca.ri    IS NULL
					 AND tbl_os_item.pedido IS NULL
					 AND ((tbl_os.nota_fiscal_saida IS NULL) OR (tbl_os.data_nf_saida IS NULL))";
		}

		if($aprova =="aprovacao"){
			$sql .=" AND    tbl_os_troca.status_os IS NULL
                     AND    (
                                tbl_os.status_os_ultimo <> 19
                            OR  tbl_os.status_os_ultimo isnull
                            )
					 AND ( ((tbl_os.nota_fiscal_saida IS NULL) OR (tbl_os.data_nf_saida IS NULL))
							AND
							( tbl_os_item_nf.nota_fiscal IS NULL OR tbl_os_item_nf.data_nf IS NULL )
						)
            ";
		}

		if($aprova =="aprovacao" AND $troca =="garantia"){ //HD 75737
			$sql .=" AND tbl_os.tipo_atendimento = 17";
		}

		if($aprova =="aprovacao" AND $troca =="faturada"){ //HD 75737
			$sql .=" AND tbl_os.tipo_atendimento = 18";
		}

		## Tratamento do ítem CORTESIA
		if($aprova =="aprovacao" AND $troca =="cortesia"){ //HD 177963
			$sql .=" AND tbl_os.tipo_atendimento = 35";
		}

		if($aprova == "aprovadas_com_nf"){
			$sql.=" AND tbl_os_troca.status_os = 19
					AND (
						(tbl_os_troca.ri IS NOT NULL AND tbl_os.nota_fiscal_saida IS NOT NULL
						AND tbl_os.data_nf_saida IS NOT NULL
						) OR
						(tbl_os_item_nf.nota_fiscal IS NOT NULL AND tbl_os_item_nf.data_nf IS NOT NULL )
					)
					 ";
		}

		if ($aprova == "aprovadas_com_nf_satisfacao") {
			$sql .= " AND tbl_os.satisfacao IS TRUE
					  AND tbl_os_campo_extra.os_troca_origem NOTNULL";
		}

		//hd 45281, incluído os excluida is true
		if($aprova == "excluida"){
			//status_os =15 OS excluída pelo fabricante
			//status_os =96 OS excluída pelo posto
			$sql .=" AND (tbl_os_troca.status_os = 15 or tbl_os_troca.status_os=96 or tbl_os.excluida IS TRUE)";
		}

		if($aprova == "recusada"){
			$sql .=" AND tbl_os_troca.status_os = 13";
		}

		# HD 64445 - Se for pelo nº da OS busca independente de ser interna ou não
		// if($interno_posto == 'interno' AND $callcenter == 'callcenter'){
		// 	$sql .= " AND tbl_os.admin IS NOT NULL ";
		// }elseif($interno_posto == 'interno' and strlen($callcenter) == 0){
		// 	$sql .= " AND ADM.admin_sap IS TRUE ";
		// }elseif($interno_posto <> 'interno' and strlen($callcenter) > 0){
		// 	$sql .= " AND ADM.admin_sap IS FALSE ";
		// }elseif($interno_posto <> 'interno' and strlen($os_troca_especifica) == 0 and strlen($callcenter) == 0){
		// 	$sql .= " AND tbl_os.admin IS NULL ";
		// }

		switch ($interno_posto) {
			case 'interno':
				$sql .= " AND tbl_os.admin IS NOT NULL ";
				$sql .= " AND ADM.fale_conosco is not true";
				//não pode ser sac
				break;
			case 'callcenter':
				$sql .= " AND tbl_os.admin IS NOT NULL ";
				$sql .= " AND ADM.fale_conosco IS true ";
				//todo que é SAC
				break;
			case 'posto':
				//$sql .= " AND ADM.admin_sap IS FALSE ";
				//if (strlen(trim($os_troca_especifica)) == 0 ){
				$sql .= " AND tbl_os.admin IS NULL ";
				//}
				break;
			case 'satisfacao':
				$sql .= " AND tbl_os.satisfacao IS TRUE ";
				$sql .= " AND tbl_os_campo_extra.os_troca_origem NOTNULL ";
				break;
		}


		if(strlen($os_troca_especifica) > 0){
			if ($login_fabrica == 1) {
				$pos = strpos($os_troca_especifica, "-");
				if ($pos === false) {
					$pos = strlen($os_troca_especifica) - 5;
				}else{
					$pos = $pos - 5;
				}
				$query_os_troca_especifica = substr($os_troca_especifica, $pos,strlen($os_troca_especifica));

				$query_os_troca_especifica = trim (strtoupper ($query_os_troca_especifica));
			}else{
				$query_os_troca_especifica = trim (strtoupper ($os_troca_especifica));
		        }


			$sql .= " AND tbl_os.sua_os LIKE '%$query_os_troca_especifica'";
		}

		if(strlen($modelo_produto) > 0){
			$sql .= " AND tbl_produto.referencia = '$modelo_produto'";
		}

		if(strlen($os_com_anexo) > 0 ) {
			$sql.= " AND tbl_os.nf_os IS NOT NULL AND tbl_os.nf_os IS TRUE  ";
		}

		if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final' ";
		}
	}else{
		$sql.= " AND (tbl_pedido.pedido_cliente='".$pedido."' OR substr(tbl_pedido.seu_pedido,4) = '$pedido' OR tbl_pedido.seu_pedido = '$pedido') ";
	}

	$sql .= " ORDER BY tbl_posto_fabrica.codigo_posto asc,tbl_os.os asc ";
	
	$res		= pg_query($con,$sql);

	$qtde_os	= pg_num_rows($res);
	if($qtde_os>0){

	//LEGENDAS hd 14631

	echo "<table>";
	echo "<tr height='18'>";
	echo "<td nowrap width='30' >";
	echo "<span style='background-color:#FDEBD0;color:#FDEBD0;border:1px solid #cccccc'>_____</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp;  OS com origem da Intervenção</b></font></td>";
	echo "</tr>";
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#99FF66;color:#00FF00;border:1px solid #cccccc'>_____</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp;  OS com Observação</b></font></td>";
	if($login_fabrica == 1){
		echo "</tr>";
		echo "<tr height='18'>";
		echo "<td nowrap width='18' >";
		echo "<span style='background-color:yellow;color:yellow;border:1px solid #cccccc'>_____</span></td>";
		echo "<td align='left'><font size='1'><b>&nbsp;  OS Faturada sem NF</b></font></td>";
		echo "</tr>";
	}
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#CCFFFF; color:#D7FFE1;border:1px solid #cccccc'>_____</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
	echo "</tr>";
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#FFCCFF; color:#D7FFE1;border:1px solid #cccccc'>_____</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp; Reincidências com mesmo produto e nota</b></font></td>";
	echo "</tr>";

	if($login_fabrica == 1){
		echo "</tr>";
			echo "<tr height='18'>";
			echo "<td nowrap width='18' >";
				echo "<span style='background-color:#EC644B;color:#EC644B;border:1px solid #cccccc'>_____</span></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  Reincidências de Postos diferentes</b></font></td><BR>";
		echo "</tr>";
		echo "</tr>";
			echo "<tr height='18'>";
			echo "<td nowrap width='18' >";
				echo "<span style='background-color:#FABE58;color:#FABE58;border:1px solid #cccccc'>_____</span></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  Reincidências de Nota Fiscal e Revenda</b></font></td><BR>";
		echo "</tr>";
		echo "</tr>";
			echo "<tr height='18'>";
			echo "<td nowrap width='18' >";
				echo "<span style='background-color:#AEA8D3;color:#AEA8D3;border:1px solid #cccccc'>_____</span></td>";
			echo "<td align='left'><font size='1'><b>&nbsp;  Reincidências por CPF do Consumidor</b></font></td><BR>";
		echo "</tr>";
		echo "</tr>";
			echo "<tr height='18'>";
			echo "<td nowrap width='18' >";
				echo "<span style='background-color:#cccccc;color:#cccccc;border:1px solid #cccccc'>_____</span></td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Produto Descontinuado</b></font></td><BR>";
		echo "</tr>";

	}
	echo "</table>";

	//----------------------
	echo "<BR><BR>
	<FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";
	/* HD 7474 - Fabio - Para que seja pesquisado novamente quando o ADMIN faz alguma ação*/
	echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
	echo "<input type='hidden' name='data_final'   value='$data_final'>";
	echo "<input type='hidden' name='aprova'       value='$aprova'>";
	echo "<input type='hidden' name='interno'      value='$interno'>";
	echo "<input type='hidden' name='troca'        value='$troca'>";
	echo "<input type='hidden' name='interno_posto' value='$interno_posto'>"; //hd 14005 19/2/2008
	//echo "<input type='hidden' name='callcenter' value='$callcenter'>"; //hd 14005 19/2/2008
	echo "<input type='hidden' name='posto_codigo' value='$posto_codigo'>";
	echo "<input type='hidden' name='os_troca_especifica' value='$os_troca_especifica'>";
	echo "<input type='hidden' name='posto_nome'   value='$posto_nome'>";
	echo "</div>";
	echo "<table class='table table-bordered table-fixed'>";
	echo "<tr class='titulo_coluna'>";

	// HD 18838
	if($aprova !='aprovadas_com_nf' and $aprova <> 'excluida'){
		echo "<th><a href='#' onclick='javascript: checkaTodos()' style='color:#FFFFFF;'>Todas</a></th>";
	}
		echo "<th>OS</th>";
		echo "<th>Consumidor</th>";
		if($login_fabrica==1){
			echo "<th>Revenda</th>";
		}
		echo "<th>Código Posto</th>";
		echo "<th>Razão Social</th>";
		echo "<th>UF</th>";
		echo "<th>Admin</th>";
		echo "<th>Produto</th>";
		echo "<th>Volt.</th>";
		echo "<th>Trocar por:</th>";
	if(trim($aprova) == 'aprovacao'){
			echo "<th>Valor total</th>";
	}
			echo "<th>Clas. da OS:</th>";
	// if(trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' OR trim($aprova) == 'aprovadas_com_nf' or strlen($pedido) > 0 ){
			echo "<th>Valor total</th>";
			echo "<th>Pedido</th>";
			echo "<th>NF</th>";
			echo "<th>Data Envio</th>";
	// }

	echo "<th>Digitado Por:</th>";
	if(trim($aprova) =='excluida'){
		echo "<th>Excluído Por</th>";
	}
		echo "<th>Data Digitação</th>";
		echo "<th>Data Aprovação</th>";
		//hd 48647
		echo "<th>Defeito Constatado</th>";
	if ($aprova == "aprovadas_com_nf_satisfacao" && $login_fabrica == 1) {
		echo "<th>Número de AD<br>Ex:LLNNNNNN</th>";
		echo "<th>Número da Coleta</th>";
	}
		echo "<th>Obs. Posto</th>";
		echo ($login_fabrica == 1) ? "<th>Ações</th>" : "";
		echo "</tr>";

		$cor = ""; /* HD-3936882 É necessário limpar a variável $cor senão ela é fica com a informação do cabecalho_new.hp*/

		$cores = '';
		$qtde_intervencao = 0;

		$tab_order_nf = 1;
		$tab_order_nf_data = 2;

		for ($x=0; $x<$qtde_os;$x++){

			$os						= pg_fetch_result($res, $x, os);
			$sua_os					= pg_fetch_result($res, $x, sua_os);
			$reincidencia			= pg_fetch_result($res, $x, reincidencia);
			$codigo_posto			= pg_fetch_result($res, $x, codigo_posto);
			$consumidor_nome		= strtoupper(pg_fetch_result($res, $x, consumidor_nome));
			$consumidor_revenda		= strtoupper(pg_fetch_result($res,$x,consumidor_revenda));
			$revenda_nome			= strtoupper(pg_fetch_result($res, $x, revenda_nome));
			$data_digitacao			= pg_fetch_result($res, $x, digitacao);
			//			$atendimento_descricao	= pg_fetch_result($res, $x, atendimento_descricao);
			$tipo_atendimento		= pg_fetch_result($res, $x, tipo_atendimento);
			$valor_pago				= pg_fetch_result($res, $x, total_troca);
			$pedido					= pg_fetch_result($res, $x, ri);
			$nota_fiscal_saida		= pg_fetch_result($res, $x, nota_fiscal_saida);
			$data_nf_saida			= pg_fetch_result($res, $x, data_nf_saida);
			$produto_referencia		= pg_fetch_result($res, $x, produto_referencia);
			$produto_descricao		= pg_fetch_result($res, $x, produto_descricao);
			$produto_voltagem		= pg_fetch_result($res, $x, voltagem);
			$posto_nome				= pg_fetch_result($res, $x, posto_nome);
			$status_os				= 0;
			$admin_nome				= pg_fetch_result($res, $x, admin_nome);
			$aux_observacao			= pg_fetch_result($res, $x, observacao);
			$contato_estado			= pg_fetch_result($res, $x, contato_estado);
			$data_avaliacao			= pg_fetch_result($res, $x, data_avaliacao);
			$admin_digitou			= pg_fetch_result($res, $x, admin_digitou);
			$excluida				= pg_fetch_result($res, $x, excluida);
			$produto_troca			= pg_fetch_result($res, $x, produto_troca);
			$nf_os					= pg_fetch_result($res, $x, nf_os);

			if($login_fabrica == 1){
				$campos_adicionais = json_decode(pg_fetch_result($res, $x, 'campos_adicionais'), true);
			}

			//hd 48647
			$defeito_reclamado_descricao = pg_fetch_result($res, $x, defeito_reclamado_descricao);

			//HD 222050: Para a Black existe a possibilidade de trocar um produto por vários
			//com isto duplicou as linhas no relatório. Para resolver foi necessário
			//separar as colunas da seleção que geravam duplicações na sql anterior, mesmo
			//com DISTINCT
			$sql = "
			SELECT
					tbl_os_item_nf.nota_fiscal                    AS nota_fiscal,
					to_char(tbl_os_item_nf.data_nf,'DD/MM/YYYY')  AS data_nf    ,
					case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
					else
						lpad((tbl_pedido.pedido_blackedecker)::text,5,'0')
					end                                      AS pedido_os_item  ,
					tbl_pedido.seu_pedido                                       ,
					tbl_os_item.os_item
			FROM
			tbl_os
			JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
			LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
			LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item=tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido ON tbl_os_item.pedido=tbl_pedido.pedido

			WHERE tbl_os.fabrica = $login_fabrica AND
	          	      tbl_os.os = $os
			";

			$res2 = pg_query($con, $sql);
			if (pg_num_rows($res2)) {
				$pedido_os_item			= pg_fetch_result($res2, 0, pedido_os_item);
				$seu_pedido				= pg_fetch_result($res2, 0, seu_pedido);
				$nota_fiscal			= pg_fetch_result($res2, 0, nota_fiscal);
				$data_nf				= pg_fetch_result($res2, 0, data_nf);
				$os_item				= pg_fetch_result($res2, 0, os_item);
			}

			$cores++;
			$produto_referencia_troca = "";
			$produto_os_item_troca = "";
			$produto_voltagem_troca   = "";
			$cor = null;

			if(strlen($pedido==0) AND strlen($pedido_os_item>0)){//hd 21142 17/6/2008
				$pedido = $pedido_os_item;
				$pedido = fnc_so_numeros($seu_pedido);#HD49076
			}

			if(strlen($aux_observacao)> 0){
				$cor = "#99FF66";
			}

			if($login_fabrica == 1){
				$sql_snf = "SELECT os FROM tbl_os WHERE os = $os AND troca_faturada = 't' AND nota_fiscal IS NULL";
				$res_snf = pg_query($con, $sql_snf);

				if(pg_num_rows($res_snf) > 0){
					if(strlen($aux_observacao)> 0){
						$cor = "#99FF66";
					}else{
						$cor = "#FFFF00";
					}
				}else{ /* HD- 3936882*/
					if($qtde_os == 1){
					#	$cor = "#FFFF00";
					}
				}

			}

			if ($reincidencia =='t') $cor = "#CCFFFF";

			$sql_int = "SELECT status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (62,64,65,70,72,73,87,88,95,239)
						ORDER BY data DESC LIMIT 1";
			$resInt = pg_query($con,$sql_int);
			if (pg_num_rows($resInt)>0){
				$status_intervencao = pg_fetch_result($resInt, 0, status_os);

				# Se for 87, saiu da intervencao e veio para a TROCA
				if ($status_intervencao == "87" or $status_intervencao == "88"){
					$cor = "#FDEBD0";
					$qtde_intervencao++;
				}
				if ($status_intervencao == "95"){
					$cor = "#FFCCFF";
				}
				/**
				 * @since HD668859
				 */
				if ($status_intervencao == "239" and $login_fabrica == 1) {
					$cor = "#EC644B";
				}

				if($status_intervencao == "70" && $login_fabrica == 1){
					$cor = "#FABE58";
				}

				if($status_intervencao == "69" && $login_fabrica == 1){
					$cor = "#AEA8D3";
				}

			}

			if($excluida=='t' and trim($aprova) =='excluida'){
				$xsql = "SELECT tbl_admin.login from tbl_admin JOIN tbl_os ON tbl_os.admin_excluida=tbl_admin.admin where os=$os";

				$xres = pg_query($con,$xsql);
				if(pg_num_rows($xres)>0){
					$admin_excluida = pg_fetch_result($xres,0,0);
				}else{
					$admin_excluida="POSTO";
				}
			}

			if(strpos($revenda_nome,'DECKER')>0) {
				$style = "style='color: red;font-weight: bold;'";
			}

			if (strlen($consumidor_nome)>0) {
				if(strpos($revenda_nome,$consumidor_nome)!==FALSE) {
					$cor = "#FFCC00;'";
				}
			}

			if($login_fabrica == 1 and $campos_adicionais['produto_descontinuado'] == true){
				$cor = "#cccccc";
			}

			echo "<tr id='linha_$x' style='font-size: 9px; font-family: verdana; background-color:$cor'>";
				// HD 18838
			if($aprova !='aprovadas_com_nf' and $aprova <> 'excluida'){
				echo "<td class='tac' align='center' width='0'>";
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				echo "</td>";
			}
			echo "<td class='tac'>";
			if($aprova<>'excluida'){
				echo "<a href='os_press.php?os=$os'  target='_blank'>";
			}
			echo "$codigo_posto$sua_os";

			if($aprova<>'excluida'){
				echo "</a>";
			}
			$linkVerAnexosNF = "";
			$nf = null;
			$temNFs=null;
			if($consumidor_revenda == 'C') {
				$temNFs = temNF($os, 'count');
				$nf = $os;
			}else{
				$sql = "SELECT tbl_os_revenda.os_revenda
				FROM tbl_os
				JOIN tbl_os_revenda ON tbl_os.fabrica = tbl_os_revenda.fabrica and tbl_os.posto = tbl_os_revenda.posto
				JOIN tbl_os_revenda_item USING(os_revenda)
				WHERE tbl_os.fabrica = $login_fabrica
				AND os = $os
				AND (os_lote = $os or tbl_os_revenda.sua_os ~ tbl_os.os_numero::text )";
				$resn = pg_query($con, $sql);
				if (pg_num_rows($resn)> 0 ) {
					$os_revenda = pg_fetch_result($resn, 0, "os_revenda");

					if ( temNF($os_revenda, 'bool')) {
						$temNFs = temNF($os_revenda, 'count');
						$nf = $os_revenda;
					}else{
						if ( temNF($os, 'bool')) {
							$temNFs = temNF($os, 'count');
							$nf = $os;
						}
					}
				}else{
					if ( temNF($os, 'bool')) {
						$temNFs = temNF($os, 'count');
						$nf = $os;
					}

				}

			}
			if($temNFs) {
				if ($login_fabrica == 1) {
					if ($temNFs > 0) {
						$linkNF = temNF($nf, 'array');
						$arqExt = pathinfo($linkNF, PATHINFO_EXTENSION);
						$arqExt = preg_replace('/\?.+/','',$arqExt);

						$linkVerAnexosNF = $linkNF;
					}

					$links_json = json_encode($linkNF["arquivos"]);

					echo "<br /><a data-links='$links_json' target='_blank' class='btn btn-small btn-primary btn-ver-anexo'>Anexo</a>";
				} else {
					if ($temNFs == 1) {
						$linkNF = current(temNF($nf, 'url'));
						$arqExt = pathinfo($linkNF, PATHINFO_EXTENSION);
						$arqExt = preg_replace('/\?.+/','',$arqExt);
						switch($arqExt) {
							case 'gif':
							case 'jpg':
							case 'jpeg':
							case 'png':
								$linkVerAnexosNF = 'js/jpie/nf_digital_mlg_nf.php?os=' . $nf;
								break;

							case 'pdf':
							case 'doc':
							case 'docx':
								$linkVerAnexosNF = "http://docs.google.com/viewer?url=" . urlencode($linkNF);
								break;
							case 'xml';
								$linkVerAnexosNF = $linkNF;
								break;
							default:
								$linkVerAnexosNF = $linkNF;
							break;
						}

					}
					echo "<br /><a href='$linkVerAnexosNF' target='_blank' class='btn btn-small btn-primary'>Anexo</a>";
				}
			}
			echo "</td>";
			echo "<td align='left'>".strtoupper($consumidor_nome)."</td>";
			if($login_fabrica==1){
				echo "<td align='left'>".strtoupper($revenda_nome)."</td>";
			}
			echo "<td class='tac' nowrap>".$codigo_posto."</td>";
			echo "<td align='left'><acronym title='Posto: $posto_nome' style='cursor: help'>".$posto_nome."</acronym></td>";
			echo "<td>".$contato_estado. "</td>";
			echo "<td>"; if(strlen($admin_nome) > 0) {echo "$admin_nome";}else{echo "&nbsp;";} echo "</td>";
			echo "<td align='left'><acronym title='Produto: $produto_referencia - $produto_descricao' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td>$produto_voltagem</td>";
			echo "<td align='left' nowrap>";

			//HD 222050: Black: existe a possibilidade de trocar um produto por vários
			$produto_referencia_troca_array = array();
			$produto_voltagem_troca_array = array();
			$produto_descricao_troca_array = array();
			$produto_troca_array = array();

			if (strlen($produto_troca) == 0) {
				$produto_referencia_troca_array[0] = $produto_referencia;
				$produto_voltagem_troca_array[0] = $produto_voltagem;
			} else {
				if($consumidor_revenda=='C') {
					$sql_troca = "
					SELECT
					tbl_peca.referencia,
					tbl_peca.voltagem,
					tbl_os_item.os_item,
					tbl_os_item_nf.nota_fiscal,
					to_char(tbl_os_item_nf.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_peca.descricao

					FROM
					tbl_os_produto
					JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
					LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
					JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca

					WHERE
					tbl_os_produto.os=$os and obs is not null
					";
				}else{
					$sql_troca = "SELECT tbl_os_item.os_item,
						tbl_os_item_nf.nota_fiscal,
						to_char(tbl_os_item_nf.data_nf,'DD/MM/YYYY') as data_nf,
						referencia,descricao,voltagem
						FROM tbl_os_troca
						JOIN tbl_produto USING(produto)
						JOIN tbl_os_produto ON tbl_os_troca.os = tbl_os_produto.os
						JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
						LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
						WHERE tbl_os_troca.os = $os;	";
				}
				//echo nl2br($sql_troca);
				$res_troca = pg_query($con, $sql_troca);

				if (pg_num_rows($res_troca)) {
					for($t = 0; $t < pg_num_rows($res_troca); $t++) {
						$produto_referencia_troca_array[$t] = pg_fetch_result($res_troca, $t, referencia);
						$produto_os_item_troca_array[$t] = pg_fetch_result($res_troca, $t, os_item);
						$produto_nf_troca_array[$t] = pg_fetch_result($res_troca, $t, nota_fiscal);
						$produto_data_nf_troca_array[$t] = pg_fetch_result($res_troca, $t, data_nf);
						$produto_voltagem_troca_array[$t] = pg_fetch_result($res_troca, $t, voltagem);
						$produto_descricao_troca_array[$t] = pg_fetch_result($res_troca, $t, descricao);
					}
				}
				else {
					$produto_referencia_troca_array[0] = $produto_referencia;
					$produto_os_item_troca_array[0] = $os_item;
					$produto_nf_troca_array[0] = $nota_fiscal;
					$produto_data_nf_troca_array[0] = $data_nf;
					$produto_voltagem_troca_array[0] = $produto_voltagem;
					$produto_descricao_troca_array[0] = $produto_descricao;
				}
			}

			$total_produtos_trocados = count($produto_referencia_troca_array); #HD 388652
			foreach($produto_referencia_troca_array as $t => $produto_referencia_troca) {

				$num = $t + 1;
				$produto_voltagem_troca = $produto_voltagem_troca_array[$t];
				$produto_descricao_troca = $produto_descricao_troca_array[$t];
				$produto_troca_array[$t] = "<acronym title='Trocar por: $produto_referencia_troca - $produto_descricao_troca' style='cursor: help'>"." ". $num . " - " . $produto_referencia_troca . " - " . $produto_voltagem_troca . "</acronym>";

			}

			echo implode("<br>", $produto_troca_array);

			echo "</td>";

			if( trim($aprova) == 'aprovacao'){
				if($tipo_atendimento == 18){
					if(strlen($valor_pago) > 0) echo "<td align='right' nowrap>R$ ". number_format($valor_pago, 2, ',', ' ') ."&nbsp;</td>";
					else echo "<td align='right' nowrap>R$ 2,50 &nbsp;</td>";
				}else{
					echo "<td align='right' nowrap>R$ 0,00 &nbsp;</td>";
				}
			}

			echo "<td align='center' nowrap>";

			switch($tipo_atendimento) {
				case 17: {
					echo "Garantia";
					break;
				}
				case 18: {
					echo "Faturada";
					break;
				}
				case 35: {
					echo "Cortesia";
					break;
				}
				case 64: {
					echo "OS Geo";
					break;
				}
				case 65: {
					echo "OS Geo";
					break;
				}
				case 69: {
					echo "OS Geo";
					break;
				}
				default: {
					echo "-";
					break;
				}
			}

			echo "</td>";
			if( (trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' or trim($aprova) == 'aprovadas_com_nf' or strlen($pedido) >0 )){

				if($tipo_atendimento == 18){
					if(strlen($valor_pago) > 0) {
						echo "<td align='center' nowrap>R$". number_format($valor_pago, 2, ',', ' ') ." &nbsp;</td>";
					}else {
						echo "<td align='center' nowrap>R$ 2,50 &nbsp;</td>";
					}
				}else{
					echo "<td align='center' nowrap>R$0,00 &nbsp;</td>";
				}
				if(strlen($pedido) > 0 ) echo "<td align='center' nowrap>$pedido</td>";
				else					 echo "<td align='center' nowrap><INPUT size='8' TYPE=\"text\" NAME=\"pedido_$x\" class='frm'></td>";

				if(strlen($nota_fiscal_saida) > 0) {

					#HD 388652
					for ($y = 0 ; $y < $total_produtos_trocados ; $y++){
						$num = $y + 1;
						echo "<td align='center' nowrap> $num - $nota_fiscal_saida</td>";
					}

				}elseif(strlen($pedido_os_item) >0) {

						echo "<td align='center' nowrap>$nota_fiscal";


							for ($y = 0 ; $y < $total_produtos_trocados ; $y++)
							{
								$produto_os_item_troca = $produto_os_item_troca_array[$y];
								$produto_nf_troca = $produto_nf_troca_array[$y];



								$num = $y + 1;
								echo " $num - <input type='text' tabindex=$tab_order_nf name='nota_falta_$y' rel='nota_falta' size='8' value = '$produto_nf_troca' class='frm' alt='$produto_os_item_troca' onblur='atualizaNf(this.value,$x,$produto_os_item_troca)'><br />";


								$tab_order_nf_new = $tab_order_nf;

								$tab_order_nf = ($tab_order_nf_new == $tab_order_nf) ? $tab_order_nf + 2 : $tab_order_nf;
							}

							echo "<div id='div_msg2_$x' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:180px;'></div>";
						#HD 388652 -  FIM


					echo "</td>";

				}else{

					echo "<td align='center' nowrap><INPUT size='8' TYPE=\"text\" NAME=\"nf_$x\" class='frm'></td>";

				}


				if(strlen($data_nf_saida) > 0) {

					for ($y = 0 ; $y < $total_produtos_trocados ; $y++){
						$num = $y +1;
						echo " $num - <td align='center' nowrap>$data_nf_saida <br /></td>";
					}

				}elseif(strlen($pedido_os_item) >0) {

					echo "<td align='center' nowrap>$data_nf";

						for ($y = 0 ; $y < $total_produtos_trocados ; $y++){

							$produto_os_item_troca = $produto_os_item_troca_array[$y];

							$produto_data_nf_troca = $produto_data_nf_troca_array[$y];

							$num = $y +1;
							echo " $num - <input type='text' tabindex='$tab_order_nf_data' name='data_nf_falta_$y' rel='data_nf_falta' size='11' value = '$produto_data_nf_troca' class='frm' alt='$produto_os_item_troca' onblur='atualizaDatanf(this.value,$x,$produto_os_item_troca)'><br />";

							$tab_order_nf_data_new = $tab_order_nf_data;

							$tab_order_nf_data = ($tab_order_nf_data == $tab_order_nf_data_new) ? $tab_order_nf_data + 2 : $tab_order_nf_data;

						}
						echo "<div id='div_msg_$x' style='position:absolute; display:none; border: 1px solid #949494;background-color: #F1F0E7;width:180px;'></div>";

					echo "</td>";
				}else {
					echo "<td align='center' nowrap><INPUT size='12' TYPE=\"text\" NAME=\"data_envio_$x\" rel='data_nf' class='frm'></td>";
				}
			}else{

				?>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				<?
			}

			#HD 16097
			if (strlen($admin_digitou)==0){
				$admin_digitou = "POSTO";
			}

			echo "<td>".$admin_digitou. "</td>";
			if(trim($aprova) =='excluida'){
				echo "<td>".$admin_excluida. "</td>";
			}
			echo "<td>".$data_digitacao. "</td>";
			echo "<td>".$data_avaliacao. "</td>";

			//hd 48647
			echo "<td>$defeito_reclamado_descricao</td>";

			if ($aprova == "aprovadas_com_nf_satisfacao" && $login_fabrica == 1) {
				if ($campos_adicionais['numero_ad']) {
					$numero_ad = $campos_adicionais['numero_ad'];
					echo "<td>$numero_ad</td>";
				} else {
					echo "<td><input class='ad' type='text' name='ad_bd_$x' value='' maxlength='8' style='max-width: 75px;' onblur='javascript: valida_ad(name)'></td>";
				}

				if ($campos_adicionais['numero_coleta']) {
					$numero_coleta = $campos_adicionais['numero_coleta'];
					echo "<td>$numero_coleta</td>";
				} else {
					echo "<td><input class='coleta' type='text' name='coleta_bd_$x' value='' style='max-width: 160px;'></td>";
				}
				
			}
			
			echo "<td nowrap><a class='btn btn-primary' href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">Visualizar</a> </td>";

			if($login_fabrica == 1){
				echo " <td> <a os_troca='$os' target='_blank' class='btn btn-primary btn-alterar'> Alterar </a> </td> ";
			}

			echo "</tr>";
			$style = '';

			$tab_order_nf = $tab_order_nf  + 2;
			$tab_order_nf_data = $tab_order_nf_data + 2;

		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr style='background-color:#485989;color:white;font-weight:bold'>";
				// HD 18838

		if($aprova !='aprovadas_com_nf' and $aprova <> 'excluida'){
			echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; COM MARCADOS:&nbsp;";
			if(trim($aprova) == 'aprovacao'){
				echo "<select name='select_acao' id='select_acao' size='1' class='frm' onChange='verificarAcao(this)'>";
				echo "<option value=''></option>";
				echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADA PELO FABRICANTE</option>";
				echo "<option value='13'";  if ($_POST["select_acao"] == "13")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
				echo "<option value='15'";  if ($_POST["select_acao"] == "15")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";
				echo "</select>";
        if($login_fabrica == 1 AND 1 == 2){
            echo "&nbsp;&nbsp;<div id='div_sem_nf' style='";if($_POST["select_acao"] == "13"){ echo "display:inline;"; }else{ echo "display:none;";} echo "font-weight:bold;'>Sem NF: <input type='checkbox' class='frm' name='sem_nf' id='sem_nf' value='sem_nf' onchange='javascript:marcarSemNota(this.checked);' ></div>";
        }
				echo "&nbsp;&nbsp;Motivo: <input class='frm' type='text' name='observacao' id='observacao' size='50' maxlength='900' value=''  "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";

				echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
				echo "<input type='hidden' name='gravar_aud' value='gravar_aud'>";
				if($login_fabrica == 1){
					echo "&nbsp;&nbsp;<input disabled='disabled' type='submit' id='frm_pesquisa2' class='btn' value='Gravar' border='0'></td>";
				}else{
					echo "&nbsp;&nbsp;<input type='button' id='frm_pesquisa2' class='btn' value='Gravar' onclick='javascript: document.frm_pesquisa2.submit()' border='0'></td>";
				}
			}else if(trim($aprova) == 'recusada'){//hd 16334 Gustavo 28/3/2008
				echo "<select name='select_acao' size='1' class='frm' onChange='verificarAcao(this)'>";
				echo "<option value=''></option>";
				echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADA PELO FABRICANTE</option>";
				echo "<option value='volta_aprovacao'";  if ($_POST["select_acao"] == "volta_aprovacao")  echo " selected"; echo ">VOLTAR PARA APROVAÇÃO</option>";
				echo "</select>";
				echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
				echo "&nbsp;&nbsp;<input type='submit' style='background-image:url(imagens_admin/btn_gravar.gif);width:75px;cursor:pointer;' value='&nbsp;' border='0'></td>";
			}else {
				echo "<input type='hidden' name='btn_acao' value='Pesquisar2'>";
				echo "<input type='hidden' name='select_acao' value='gravar_nf_envio'>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				echo "&nbsp;&nbsp;<input type='submit' style='background-image:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;' value='&nbsp;' border='0'></td>";
			}
		}
		echo "</table>";
		echo "</form>";

		echo "<p>OS encontradas: $qtde_os</p>";

	}else{
		echo "<div class='alert alert-warning'><h4>Não foi encontrada OS de Troca.</h4></div>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
