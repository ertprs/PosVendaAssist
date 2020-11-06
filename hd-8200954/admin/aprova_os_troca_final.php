<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';

// HD 65694 função apenas para gravar nota que está faltando de 18/04/2008 até 31/03/2009, depois precisa excluir essa função.
if (isset($_POST['gravarNotaFalta']) AND isset($_POST['os'])){
	$gravarNotaFalta = trim($_POST['gravarNotaFalta']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		if (strlen($gravarNotaFalta) > 0){
			if (strlen($erro) == 0) {
				$sql = "SELECT os_item FROM tbl_os_item_nf where os_item = $os";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) == 0) {
					$sqlx = "INSERT INTO tbl_os_item_nf(os_item,qtde_nf,nota_fiscal)values($os,1,$gravarNotaFalta);";
					$sqlx.= "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(tbl_os.os) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os_item = $os AND tbl_os_produto.os = tbl_os.os" ;
					$resx = @pg_query($con,$sqlx);
				}else{
					$sqlx = "UPDATE tbl_os_item_nf set nota_fiscal = $gravarNotaFalta WHERE os_item = $os;";
					$sqlx.= "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(tbl_os.os) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os_item = $os AND tbl_os_produto.os = tbl_os.os" ;
					$resx = @pg_query($con,$sqlx);
				}
			} else {
				echo $erro;
			}
		}
	}
	exit;
}
// HD 65694 função apenas para gravar nota que está faltando de 18/04/2008 até 31/03/2009, depois precisa excluir essa função.

if (isset($_POST['gravarDataNfFalta']) AND isset($_POST['os'])){
	$gravarDataNfFalta = trim($_POST['gravarDataNfFalta']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		if (strlen($gravarDataNfFalta) > 0){
			$gravarDataNfFalta = fnc_formata_data_pg($gravarDataNfFalta);
		}else{
			$gravarDataNfFalta = "";
		}
		if(strlen($gravarDataNfFalta) >0) {
			$sql = "SELECT os_item FROM tbl_os_item_nf where os_item = $os";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0) {
				echo "Por favor, preencher primeiro a nota fiscal";		
			}else{
				$sqlx = "UPDATE tbl_os_item_nf set data_nf = $gravarDataNfFalta WHERE os_item = $os";
				$resx = @pg_query($con,$sqlx);
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
					$sql="UPDATE tbl_os_troca set status_os = 13 where os = $xxos;";
					$res= pg_query($con, $sql);

					$sql = "INSERT INTO tbl_os_status (
										os        ,
										status_os ,
										observacao,
										admin,
										status_os_troca
									) VALUES (
										'$xxos'      ,
										'13'         ,
										'$observacao',
										$login_admin,
											't'
									);";
					$res = pg_query ($con,$sql);
				}else{
					$msg_erro .= "Por favor preencha o motivo da recusa.";
				}
			}
			## EXCLUIDA--------------------------------------------
			if($select_acao=="15"){
				if(strlen($observacao) > 0){
					$sql="UPDATE tbl_os_troca set status_os = 15 where os = $xxos; ";
					$res= pg_query($con, $sql);

					$sql = "INSERT INTO tbl_os_status (
										os        ,
										status_os ,
										observacao,
											admin,
										status_os_troca
									) VALUES (
										'$xxos'      ,
										'15'         ,
										'$observacao',
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
					$sql="UPDATE tbl_os_troca set status_os = 19 where os = $xxos; ";
					$res= pg_query($con, $sql);

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
							JOIN tbl_os      ON tbl_os.os = tbl_os_troca.os AND tbl_os.fabrica = 1
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

				$sql_2 = "UPDATE tbl_os SET
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
		}
	}
}

$layout_menu = "financeiro";
$title = "Aprovação Ordem de Serviço de Troca";

include "cabecalho.php";

?>

<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>


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
		}else{
			document.getElementById('observacao').disabled = false;
		}
	}
}

</script>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");

		$("input[rel='data_nf']").maskedinput("99/99/9999");
		$("input[rel='data_nf_falta']").maskedinput("99/99/9999");
	});

	$().ready(function() {
		$("input[rel='nota_falta']").blur(function(){
			var campo = $(this);
			$.post('<? echo $PHP_SELF; ?>',{
				gravarNotaFalta : campo.val(),
				os: campo.attr("alt")
			});
		});

		$("input[rel='data_nf_falta']").blur(function(){
			var campo = $(this);
			$.post('<? echo $PHP_SELF; ?>',{
				gravarDataNfFalta : campo.val(),
				os: campo.attr("alt")
			});
		});
	});
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<script>
function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}
</script>

<? include "javascript_pesquisas.php";

$btn_acao       = $_POST['btn_acao'];

if($btn_acao == 'Pesquisar'){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$aprovacao    = $_POST['aprova'];
	$troca        = $_POST['troca'];
	$posto_codigo = $_POST['posto_codigo'];
	$os_troca_especifica = $_POST['os_troca_especifica'];
	$modelo_produto = trim($_POST['modelo_produto']);

	if(strlen($data_inicial) == 0 and strlen($data_final) == 0 and strlen($os_troca_especifica) == 0) {
		$msg_erro = "Informe o campo Data Inicial e Data Final";
	}

	if(strlen($data_inicial) > 0 and strlen($data_final) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";

		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
}

$aprova        = $_POST['aprova'];
$interno_posto = $_POST['interno_posto'];

if(strlen($msg_erro) > 0){
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$x = explode('ERROR: ',$msg_erro);
		$msg_erro = $x[1];
	}

	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}
?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="20">
		<td align="center" background='imagens_admin/azul.gif'>Selecione os parâmetros para pesquisa.</td>
	</tr>
</table>

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
<tr>
	<td colspan='4' class="table_line" style="width: 10px">&nbsp;</td>
</tr>
<tr>
	<td class="table_line" style="width: 10px">&nbsp;</td>
	<td class="table_line">Data Inicial</td>
	<td class="table_line">Data Final</td>
	<td class="table_line" style="width: 10px">&nbsp;</td>
</tr>
<tr>
	<td class="table_line" style="width: 10px">&nbsp;</td>
	<TD class="table_line" style="width: 185px"><center><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
	<TD class="table_line" style="width: 185px"><center><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
	<td class="table_line" style="width: 10px">&nbsp;</td>
</tr>
</table>

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='4'>&nbsp;</td>
</tr>
<tr align='left'>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="interno_posto" value='interno' <? if(trim($interno_posto) == 'interno') echo "checked='checked'"; ?>>Troca Interna</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="interno_posto" value='posto' <? if(trim($interno_posto) == 'posto') echo "checked='checked'"; ?>>Troca de Posto</td>
	<td class="table_line">&nbsp;</td>
</tr>
<tr align='left'>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='left'>
		<INPUT TYPE="radio" NAME="aprova" ID="aprova" value='aprovacao'  <? if(trim($aprova) == 'aprovacao') echo "checked='checked'"; ?>>Em aprovação
		<div id='mostrar_filtro' align='center' style='display: block;'>
			<INPUT TYPE="radio" NAME="troca" value='faturada' <? if(trim($troca) == 'faturada') echo "checked='checked'"; ?>>Faturadas<BR>
			<INPUT TYPE="radio" NAME="troca" value='garantia' <? if(trim($troca) == 'garantia') echo "checked='checked'"; ?>>Garantias
		</div>
	</td>
	<td class="table_line" style="size: 10px" align='left'><INPUT TYPE="radio" NAME="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas(com pedido)</td>
	<td class="table_line">&nbsp;</td>
</tr>
<tr align='left'>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='center' ><INPUT TYPE="radio" NAME="aprova" value='aprovadas_sem_pedido' <? if(trim($aprova) == 'aprovadas_sem_pedido') echo "checked='checked'"; ?>>Aprovadas(sem pedido)</td>
	<td class="table_line" style="size: 10px" align='center' ><INPUT TYPE="radio" NAME="aprova" value='aprovadas_com_nf' <? if(trim($aprova) == 'aprovadas_com_nf') echo "checked='checked'"; ?>>Aprovadas(com número de NF)</td>
	<td class="table_line">&nbsp;</td>
</tr>
<tr align='left'>
	<td class="table_line">&nbsp;</td>
	<td class="table_line" style="size: 10px" align='center' ><INPUT TYPE="radio" NAME="aprova" value='excluida' <? if(trim($aprova) == 'excluida') echo "checked='checked'"; ?>>Excluída</td>
	<td class="table_line" style="size: 10px" align='center' ><INPUT TYPE="radio" NAME="aprova" value='recusada' <? if(trim($aprova) == 'recusada') echo "checked='checked'"; ?>>Recusada</td>
	<td class="table_line">&nbsp;</td>
</tr>
</table>

<table width="380" align="center" border="0" cellspacing="0" cellpadding="2">
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='4'>&nbsp;</td>
</tr>
<tr  class="Conteudo" bgcolor="#D9E2EF">
	<td>&nbsp;</td>
	<td align='center'>O.S Troca Específica<BR>
	<input type="text" name="os_troca_especifica" id="os_troca_especifica" size="13" value="<?echo $os_troca_especifica?>" class="frm">
	</td>
	<td align='left'>Modelo Produto<BR>
	<input type="text" name="modelo_produto" id="modelo_produto" size="20" value="<?echo $modelo_produto?>" class="frm">
	</td>
	<td>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF">
	<td>&nbsp;</td>
	<td>Código do Posto<br>
		<input type="text" name="posto_codigo" id="posto_codigo" size="10" value="<?echo $posto_codigo?>" class="frm">
		<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: hand;">
	</td>
	<td>Razão Social do Posto<br>
		<input type="text" name="posto_nome" id="posto_nome" size="25" value="<?echo $posto_nome?>" class="frm">
		<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo,document.frm_pesquisa.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: hand;">
	</td>
	<td>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='4'>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
		<td colspan='4' align='center'><input type="submit" name="btn_acao" value="Pesquisar"></td>
</tr>
</table>
</form>

<?
if ($btn_acao == 'Pesquisar' and strlen($msg_erro)==0) {
	$codigo_posto = $_POST['posto_codigo'];

	$sql="SELECT DISTINCT(tbl_os.os),
					tbl_os.sua_os                                               ,
					tbl_os.os_reincidente                  AS reincidencia      ,
					tbl_os.consumidor_nome                                      ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS digitacao    ,
					tbl_os.fabrica                                              ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.revenda_nome                                         ,
					tbl_os.nota_fiscal_saida                                    ,
					tbl_os_troca.situacao_atendimento AS tipo_atendimento      ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf_saida ,
					tbl_os_troca.total_troca                                    ,
					tbl_os_troca.ri                                             ,
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
					tbl_posto.nome                     AS posto_nome            ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_posto_fabrica.contato_estado                            ,
					tbl_produto.referencia             AS produto_referencia    ,
					tbl_produto.voltagem                                        ,
					(SELECT tbl_admin.login FROM tbl_os_status LEFT JOIN tbl_admin ON tbl_admin.admin= tbl_os_status.admin WHERE os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1)      AS admin_nome,
					tbl_os_troca.data                                           ,
					tbl_os_troca.observacao                                     ,
					(SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data FROM tbl_os_status WHERE os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1)      AS data_avaliacao,
					ADM.login                                    AS admin_digitou,
					tbl_os.excluida                                              ,
					tbl_os_troca.produto                AS produto_troca         ,
					tbl_os.defeito_reclamado_descricao                           
				FROM tbl_os_troca
				JOIN tbl_os              ON tbl_os.os              = tbl_os_troca.os
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os      = tbl_os.os
				LEFT JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				LEFT JOIN tbl_os_item_nf ON tbl_os_item_nf.os_item = tbl_os_item.os_item
				LEFT JOIN tbl_pedido     ON tbl_pedido.pedido      = tbl_os_item.pedido AND tbl_pedido.fabrica = $login_fabrica
				JOIN tbl_produto          ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_posto            ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica    ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os_troca.situacao_atendimento
				LEFT join tbl_status_os        ON tbl_status_os.status_os = tbl_os_troca.status_os
				LEFT JOIN tbl_admin ADM        ON ADM.admin       = tbl_os.admin
				WHERE tbl_os_troca.fabric = $login_fabrica
				AND   tbl_os.fabrica      = $login_fabrica
				";

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
		$sql .=" AND tbl_os_troca.status_os IS NULL
				 AND ( ((tbl_os.nota_fiscal_saida IS NULL) OR (tbl_os.data_nf_saida IS NULL))
						AND
						( tbl_os_item_nf.nota_fiscal IS NULL OR tbl_os_item_nf.data_nf IS NULL )
					)";
	}

	if($aprova =="aprovacao" AND $troca =="garantia"){ //HD 75737
		$sql .=" AND tbl_os.tipo_atendimento = 17";
	}

	if($aprova =="aprovacao" AND $troca =="faturada"){ //HD 75737
		$sql .=" AND tbl_os.tipo_atendimento = 18";
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
	if($interno_posto == 'interno'){
		$sql .= " AND tbl_os.admin IS NOT NULL ";
	}elseif($interno_posto <> 'interno' and strlen($os_troca_especifica) == 0){
		$sql .= " AND tbl_os.admin IS NULL ";
	}

	if(strlen($posto_codigo) > 0){
		$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'
				AND tbl_posto_fabrica.fabrica = $login_fabrica ";
	}else if(strlen($posto_codigo) == 0 AND strlen($os_troca_especifica) > 0 ){
		$posto_codigo = substr($os_troca_especifica, 0, 5);

		$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'
		AND tbl_posto_fabrica.fabrica = $login_fabrica ";
	}

	if(strlen($os_troca_especifica) > 0){
		if ($login_fabrica == 1) {
			$pos = strpos($os_troca_especifica, "-");
			if ($pos === false) {
				$pos = strlen($os_troca_especifica) - 5;
			}else{
				$pos = $pos - 5;
			}
			$os_troca_especifica = substr($os_troca_especifica, $pos,strlen($os_troca_especifica));
		}
		$os_troca_especifica = trim (strtoupper ($os_troca_especifica));

		$sql .= " AND tbl_os.sua_os LIKE '%$os_troca_especifica'";
	}

	if(strlen($modelo_produto) > 0){
		$sql .= " AND tbl_produto.referencia = '$modelo_produto'";
	}

	if(strlen($xdata_inicial) > 0 and strlen($xdata_final) > 0){
		$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
		ORDER BY tbl_posto_fabrica.codigo_posto asc,tbl_os.os asc ";
	}

	$res		= pg_query($con,$sql);
	$qtde_os	= pg_num_rows($res);
	if($qtde_os>0){

	//LEGENDAS hd 14631
	echo "<div align='center' style='position: relative; left: 10'>";
	echo "<table border='0' cellspacing='0' cellpadding='0'>";
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#FDEBD0;color:#FDEBD0;border:1px solid #F8B652'>__</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp;  OS com origem da Intervenção</b></font></td><BR>";
	echo "</tr>";
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#99FF66;color:#00FF00;border:1px solid #F8B652'>__</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp;  OS com Observação</b></font></td><BR>";
	echo "</tr>";
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#CCFFFF; color:#D7FFE1;border:1px solid #F8B652'>__</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td><BR>";
	echo "</tr>";
	echo "<tr height='18'>";
	echo "<td nowrap width='18' >";
	echo "<span style='background-color:#FFCCFF; color:#D7FFE1;border:1px solid #F8B652'>__</span></td>";
	echo "<td align='left'><font size='1'><b>&nbsp; Reincidências com mesmo produto e nota</b></font></td><BR>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	//----------------------

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		/* HD 7474 - Fabio - Para que seja pesquido novamente quando o ADMIN faz alguma ação*/
		echo "<input type='hidden' name='data_inicial' value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'   value='$data_final'>";
		echo "<input type='hidden' name='aprova'       value='$aprova'>";
		echo "<input type='hidden' name='interno'      value='$interno'>";
		echo "<input type='hidden' name='troca'        value='$troca'>";
		echo "<input type='hidden' name='interno_posto' value='$interno_posto'>"; //hd 14005 19/2/2008
		echo "<input type='hidden' name='posto_codigo' value='$posto_codigo'>";
		echo "<input type='hidden' name='os_troca_especifica' value='$os_troca_especifica'>";
		echo "<input type='hidden' name='posto_nome'   value='$posto_nome'>";


		echo "<table width='800' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
		echo "<tr>";
		// HD 18838
		if($aprova !='aprovadas_com_nf' and $aprova <> 'excluida'){
			echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
		}
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>OS</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>CONSUMIDOR</B></font></td>";
			if($login_fabrica==1){
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>REVENDA</B></font></td>";
			}
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Código do Posto</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Razão Social</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>UF</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Admin</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Produto</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Volt.</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Trocar por:</B></font></td>";
	if(trim($aprova) == 'aprovacao'){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Valor total</B></font></td>";
	}
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Clas. da OS:</B></font></td>";
	if(trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' OR trim($aprova) == 'aprovadas_com_nf'){
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Valor total</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Pedido</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>NF</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data Envio</B></font></td>";
	}

	echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DIGITADO POR</B></font></td>";
	if(trim($aprova) =='excluida'){
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>EXCLUÍDO POR</B></font></td>";
	}
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>DIGITAÇÃO</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>DATA <br>APROVAÇÃO</B></font></td>";
		//hd 48647
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Defeito <br>Constatado</B></font></td>";
		echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Obs. <br>Posto</B></font></td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<$qtde_os;$x++){

			$os						= pg_fetch_result($res, $x, os);
			$sua_os					= pg_fetch_result($res, $x, sua_os);
			$reincidencia			= pg_fetch_result($res, $x, reincidencia);
			$codigo_posto			= pg_fetch_result($res, $x, codigo_posto);
			$consumidor_nome		= strtoupper(pg_fetch_result($res, $x, consumidor_nome));
			$revenda_nome			= strtoupper(pg_fetch_result($res, $x, revenda_nome));
			$data_digitacao			= pg_fetch_result($res, $x, digitacao);
//			$atendimento_descricao	= pg_fetch_result($res, $x, atendimento_descricao);
			$tipo_atendimento		= pg_fetch_result($res, $x, tipo_atendimento);
			$valor_pago				= pg_fetch_result($res, $x, total_troca);
			$pedido					= pg_fetch_result($res, $x, ri);
			$pedido_os_item			= pg_fetch_result($res, $x, pedido_os_item);
			$seu_pedido				= pg_fetch_result($res, $x, seu_pedido);
			$nota_fiscal_saida		= pg_fetch_result($res, $x, nota_fiscal_saida);
			$data_nf_saida			= pg_fetch_result($res, $x, data_nf_saida);
			$nota_fiscal			= pg_fetch_result($res, $x, nota_fiscal);
			$data_nf				= pg_fetch_result($res, $x, data_nf);
			$produto_referencia		= pg_fetch_result($res, $x, produto_referencia);
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
			//hd 48647
			$defeito_reclamado_descricao = pg_fetch_result($res, $x, defeito_reclamado_descricao);
			$cores++;
			$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';
			$produto_referencia_troca = "";
			$produto_voltagem_troca   = "";

			if (strlen($produto_troca) == 0) {
				$produto_referencia_troca = $produto_referencia;
				$produto_voltagem_troca   = $produto_voltagem;
			} else {
				$sql_troca = "SELECT referencia,
									 voltagem
							FROM tbl_produto
							WHERE produto = $produto_troca";
				$res_troca = pg_query($con, $sql_troca);

				if (pg_num_rows($res_troca) > 0) {
					$produto_referencia_troca = pg_fetch_result($res_troca,0,referencia);
					$produto_voltagem_troca   = pg_fetch_result($res_troca,0,voltagem);
				}
			}

			if(strlen($pedido==0) AND strlen($pedido_os_item>0)){//hd 21142 17/6/2008
				$pedido = $pedido_os_item;
				$pedido = fnc_so_numeros($seu_pedido);#HD49076
			}

			if(strlen($aux_observacao)> 0){
				$cor = "#99FF66";
			}
			if ($reincidencia =='t') $cor = "#CCFFFF";

			$sql_int = "SELECT status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (62,64,65,72,73,87,88,95)
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
					$style = "style='background-color:#FFCC00;'";
				}
			}

			echo "<tr bgcolor=$cor id='linha_$x' $style>";
				// HD 18838
			if($aprova !='aprovadas_com_nf' and $aprova <> 'excluida'){
				echo "<td align='center' width='0'>";
					echo "<input type='checkbox' name='check_$x' id='check_$x' value='$os' onclick=\"setCheck('check_$x','linha_$x','$cor');\" ";
					if (strlen($msg_erro)>0){
						if (strlen($_POST["check_".$x])>0){
							echo " CHECKED ";
						}
					}
					echo ">";
				echo "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana' nowrap >";
			if($aprova<>'excluida'){
				echo "<a href='os_press.php?os=$os'  target='_blank'>";
			}
			echo "$codigo_posto$sua_os";
			if($aprova<>'excluida'){
				echo "</a>";
			}
			echo "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana'>".strtoupper($consumidor_nome)."</td>";
			if($login_fabrica==1){
				echo "<td align='left' style='font-size: 9px; font-family: verdana'>".strtoupper($revenda_nome)."</td>";
			}
			echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>".$codigo_posto."</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana'><acronym title='Posto: $posto_nome' style='cursor: help'>".$posto_nome."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$contato_estado. "</td>";
			echo "<td style='font-size: 9px; font-family: verdana'>"; if(strlen($admin_nome) > 0) {echo "$admin_nome";}else{echo "&nbsp;";} echo "</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana''><acronym title='Produto: $produto_referencia - ' style='cursor: help'>". $produto_referencia ."</acronym></td>";
			echo "<td style='font-size: 9px; font-family: verdana'>$produto_voltagem</td>";
			echo "<td align='left' style='font-size: 9px; font-family: verdana''nowrap><acronym title='Trocar por: $produto_referencia_troca - ' style='cursor: help'>". $produto_referencia_troca . " - " . $produto_voltagem_troca . "</acronym></td>";

			if(trim($aprova) == 'aprovacao'){
				if($tipo_atendimento == 18){
					if(strlen($valor_pago) > 0) echo "<td align='right' nowrap style='font-size: 9px'>R$ ". number_format($valor_pago, 2, ',', ' ') ."</td>";
					else echo "<td align='right' nowrap style='font-size: 9px'>R$ 2,50</td>";
				}else{
					echo "<td align='right' nowrap style='font-size: 9px;'>R$ 0,00</td>";
				}
			}

			echo "<td align='center' ; font-family: verdana' nowrap>";

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
			if(trim($aprova) == 'aprovadas' or trim($aprova) == 'aprovadas_sem_pedido' or trim($aprova) == 'aprovadas_com_nf'){
				if($tipo_atendimento == 18){
					if(strlen($valor_pago) > 0) {
						echo "<td align='center' nowrap style='font-size: 9px'>R$". number_format($valor_pago, 2, ',', ' ') ."</td>";
					}else {
						echo "<td align='center' nowrap style='font-size: 9px'>R$ 2,50</td>";
					}
				}else{
					echo "<td align='center' nowrap style='font-size: 9px;'>R$0,00</td>";
				}
				if(strlen($pedido) > 0 ) echo "<td align='center' nowrap style='font-size: 9px'>$pedido</td>";
				else					 echo "<td align='center' nowrap style='font-size: 9px'><INPUT size='8' TYPE=\"text\" NAME=\"pedido_$x\" class='frm'></td>";

				if(strlen($nota_fiscal_saida) > 0) {
					echo "<td align='center' nowrap style='font-size: 9px'>$nota_fiscal_saida</td>";
				}elseif(strlen($pedido_os_item) >0) {
					echo "<td align='center' nowrap style='font-size: 9px'>$nota_fiscal";
#					if(($login_admin ==245 or $login_admin == 822) and $aprova == "aprovadas" and $xdata_inicial=='2008-04-18 00:00:00' AND $xdata_final=='2009-03-31 23:59:59') {
#					Samuel liberou para Silvania e Lilian 14/07/2009
					if(($login_admin ==245 or $login_admin == 822) and $aprova == "aprovadas" ) {
						echo "<input type='text' name='nota_falta_$x' rel='nota_falta' size='8' value = '$nota_fiscal' class='frm'>";
					}
					echo "</td>";
				}else{
					echo "<td align='center' nowrap style='font-size: 9px'><INPUT size='8' TYPE=\"text\" NAME=\"nf_$x\" class='frm'></td>";
				}
				if(strlen($data_nf_saida) > 0) {
					echo "<td align='center' nowrap style='font-size: 9px'>$data_nf_saida</td>";
				}elseif(strlen($pedido_os_item) >0) {
					echo "<td align='center' nowrap style='font-size: 9px'>$data_nf";
#					if(($login_admin ==245 or $login_admin == 822) and $aprova == "aprovadas" and $xdata_inicial=='2008-04-18 00:00:00' AND $xdata_final=='2009-03-31 23:59:59') {
#					Samuel liberou para Silvania e Lilian 14/07/2009
					if(($login_admin ==245 or $login_admin == 822) and $aprova == "aprovadas" ) {
						echo "<input type='text' name='data_nf_falta_$x' rel='data_nf_falta' size='8' value = '$data_nf' class='frm'>";
					}
					echo "</td>";
				}else {
					echo "<td align='center' nowrap style='font-size: 9px'><INPUT size='12' TYPE=\"text\" NAME=\"data_envio_$x\" rel='data_nf' class='frm'></td>";
				}
			}

			#HD 16097
			if (strlen($admin_digitou)==0){
				$admin_digitou = "POSTO";
			}

			echo "<td style='font-size: 9px; font-family: verdana'>".$admin_digitou. "</td>";
			if(trim($aprova) =='excluida'){
				echo "<td style='font-size: 9px; font-family: verdana'>".$admin_excluida. "</td>";
			}
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_digitacao. "</td>";
			echo "<td style='font-size: 9px; font-family: verdana'>".$data_avaliacao. "</td>";
			//hd 48647
			echo "<td style='font-size: 9px; font-family: verdana'>$defeito_reclamado_descricao</td>";
			echo "<td style='font-size: 9px; font-family: verdana'><a href=\"javascript: abreObs('$os','$codigo_posto','$sua_os')\">VER</a></td>";
			echo "</tr>";
			$style = '';
		}
		echo "<input type='hidden' name='qtde_os' value='$x'>";
		echo "<tr>";
				// HD 18838
		if($aprova !='aprovadas_com_nf' and $aprova <> 'excluida'){
			echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			if(trim($aprova) == 'aprovacao'){
				echo "<select name='select_acao' size='1' class='frm' onChange='verificarAcao(this)'>";
				echo "<option value=''></option>";
				echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADA PELO FABRICANTE</option>";
				echo "<option value='13'";  if ($_POST["select_acao"] == "13")  echo " selected"; echo ">RECUSADO PELO FABRICANTE</option>";
				echo "<option value='15'";  if ($_POST["select_acao"] == "15")  echo " selected"; echo ">EXCLUÍDA PELO FABRICANTE</option>";
				echo "</select>";
				echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='50' maxlength='900' value=''  "; if ($_POST["select_acao"] == "19") echo " DISABLED "; echo ">";

				echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
				echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
			}else if(trim($aprova) == 'recusada'){//hd 16334 Gustavo 28/3/2008
				echo "<select name='select_acao' size='1' class='frm' onChange='verificarAcao(this)'>";
				echo "<option value=''></option>";
				echo "<option value='19'";  if ($_POST["select_acao"] == "19")  echo " selected"; echo ">APROVADA PELO FABRICANTE</option>";
				echo "<option value='volta_aprovacao'";  if ($_POST["select_acao"] == "volta_aprovacao")  echo " selected"; echo ">VOLTAR PARA APROVAÇÃO</option>";
				echo "</select>";

				echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
				echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
			}else {
				echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
				echo "<input type='hidden' name='select_acao' value='gravar_nf_envio'>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				echo "&nbsp;&nbsp;<img src='imagens/btn_gravar.gif' style='cursor:pointer' onclick='javascript: document.frm_pesquisa2.submit()' style='cursor: hand;' border='0'></td>";
			}
		}
		echo "</table>";
		echo "</form>";

		echo "<p>OS encontradas: $qtde_os</p>";

	}else{
		echo "<center><p>Não foi encontrada OS de Troca.</p></center><br>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
