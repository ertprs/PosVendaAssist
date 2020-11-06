<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";
include 'autentica_admin.php';

if (strlen($_GET['os']) > 0)  $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($_GET['os_item']) > 0) $item_os = trim($_GET['os_item']);
if (strlen($_GET['liberar']) > 0) $liberar = $_GET['liberar'];

$troca_faturada = trim($_POST['troca_faturada']);

if (strlen($item_os) > 0) {
	$sql = "SELECT *
			FROM   tbl_os_item
			WHERE  tbl_os_item.os_item = $item_os;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$os_pedido  = trim(pg_result($res,0,pedido));
		
		$sql      = "SELECT fn_exclui_item_os($os_pedido, $item_os, $login_fabrica)";
		$res      = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0) {
			header ("Location: $PHP_SELF?os=$os");
			exit;
		}
	}
}

if (strlen($liberar) > 0) {
	$sql = "UPDATE tbl_os_item SET
				liberacao_pedido      = 't'              ,
				data_liberacao_pedido = current_timestamp,
				admin                 = $login_admin     ,
				obs                   = '### PEÇA INFERIOR A 30% DO VALOR DE MÃO-DE-OBRA. LIBERADA PELO ADMIN. ###'
			where tbl_os_item.os_produto = tbl_os_produto.os_produto
			and   tbl_os_produto.os      = tbl_os.os
			and   tbl_os.os              = $os
			and   tbl_os.fabrica         = $login_fabrica
			and   tbl_os_item.os_item    = $liberar
			and   tbl_os_item.admin      is null;";
	$res = pg_exec ($con,$sql);
	
	header ("Location: $PHP_SELF?os=$os");
	exit;
}

if (strlen($os) > 0) {
	$sql = "SELECT tbl_os.fabrica FROM tbl_os WHERE tbl_os.os = $os";
	$res = @pg_exec ($con,$sql);
	
	if (pg_result ($res,0,fabrica) <> $login_fabrica ) {
		header ("Location: os_cadastro.php");
		exit;
	}
}

include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "gravar") {
	//if (strlen ($defeito_constatado) == 0) $defeito_constatado = "null";
	//if (strlen ($defeito_reclamado)  == 0) $defeito_reclamado  = "null";
		$data_fechamento = $_POST['data_fechamento'];
		if (strlen($data_fechamento) > 0){
			$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
			if($xdata_fechamento > "'".date("Y-m-d")."'") $msg_erro = "Fecha de cierre superior a la fecha de hoy.";
		}
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT tbl_os.posto
			FROM   tbl_os
			WHERE  tbl_os.os      = $os
			AND    tbl_os.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);

	if ($login_fabrica == 1) {
		$x_produto_type = $_POST['produto_type'];
		if (strlen ($x_produto_type) > 0) $x_produto_type = "'" . $x_produto_type . "'";
		else                              $x_produto_type = "null";

		$sql = "UPDATE tbl_os SET type = $x_produto_type
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_exec ($con,$sql);
	}

	$defeito_constatado = $_POST ['defeito_constatado'];
	if (strlen ($defeito_constatado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_exec ($con,$sql);
	}
	
	$defeito_reclamado = $_POST ['defeito_reclamado'];
	if (strlen ($defeito_reclamado) > 0) {
		$sql = "UPDATE tbl_os SET defeito_reclamado = $defeito_reclamado
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_exec ($con,$sql);
	}
	
	$causa_defeito = $_POST['causa_defeito'];
	if (strlen($causa_defeito) == 0) $causa_defeito = "null";
	else                             $causa_defeito = $causa_defeito;
	
	$sql = "UPDATE tbl_os SET causa_defeito = $causa_defeito
			WHERE  tbl_os.os    = $os
			AND    tbl_os.posto = $posto;";
	$res = @pg_exec ($con,$sql);
	
	$x_solucao_os = $_POST['solucao_os'];
	if (strlen($x_solucao_os) > 0) {
		$sql = "UPDATE tbl_os SET solucao_os = '$x_solucao_os'
				WHERE  tbl_os.os    = $os
				AND    tbl_os.posto = $posto;";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	$obs  = trim($_POST['obs']);
	if (strlen($obs) > 0) $obs = "'".$obs."'";
	else                   $obs = "null";

	$tecnico_nome = trim($_POST["tecnico_nome"]);
	if (strlen($tecnico_nome) > 0) $tecnico_nome = "'".$tecnico_nome."'";
	else                   $tecnico_nome = "null";

	$valores_adicionais = trim($_POST["valores_adicionais"]);
	$valores_adicionais = str_replace (",",".",$valores_adicionais);
	if (strlen($valores_adicionais) == 0) $valores_adicionais = "0";

	$justificativa_adicionais = trim($_POST["justificativa_adicionais"]);
	if (strlen($justificativa_adicionais) > 0) $justificativa_adicionais = "'".$justificativa_adicionais."'";
	else                   $justificativa_adicionais = "null";

	$qtde_km = trim($_POST["qtde_km"]);
	$qtde_km = str_replace (",",".",$qtde_km);
	if (strlen($qtde_km) == 0) $qtde_km = "0";


	
	if (strlen ($obs) > 0) {
	    $sql = "UPDATE  tbl_os SET obs = $obs, 
			    tecnico_nome = $tecnico_nome, 
			    qtde_km      = $qtde_km     ,
			    valores_adicionais = $valores_adicionais, 
			    justificativa_adicionais = $justificativa_adicionais
		    WHERE  tbl_os.os    = $os
		    AND    tbl_os.posto = $posto";
		$res = @pg_exec ($con,$sql);
	}
	
	$sql = "DELETE FROM tbl_os_produto
			WHERE  tbl_os_produto.os         = tbl_os.os
			AND    tbl_os_produto.os_produto = tbl_os_item.os_produto
			AND    tbl_os_item.pedido           IS NULL
			AND    tbl_os_item.liberacao_pedido IS NULL
			AND    tbl_os_produto.os = $os
			AND    tbl_os.fabrica    = $login_fabrica
			AND    tbl_os.posto      = $posto;";
	$res = @pg_exec ($con,$sql);

	##### É TROCA FATURADA #####
	if (strlen($troca_faturada) > 0) {
		$x_motivo_troca = trim($_POST['motivo_troca']);
		if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

		$resX = pg_exec ($con,"BEGIN TRANSACTION");

		$sql =	"UPDATE tbl_os SET
						motivo_troca  = $x_motivo_troca
				WHERE  tbl_os.os      = $os
				and    tbl_os.fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);

	##### NÃO É TROCA FATURADA #####
	}else{

		$qtde_item = $_POST['qtde_item'];

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$xos_item        = $_POST['os_item_'        . $i];
			$xpeca           = trim($_POST["peca_"           . $i]);
			$xposicao        = trim($_POST["posicao_"        . $i]);
			$xqtde           = trim($_POST["qtde_"           . $i]);
			$xdefeito        = trim($_POST["defeito_"        . $i]);
			$xpcausa_defeito = trim($_POST["pcausa_defeito_" . $i]);
			$xservico        = trim($_POST["servico_"        . $i]);
			$xadicional      = $_POST['adicional_peca_estoque_' . $i];
			
			$xadmin_peca      = $_POST["admin_peca_"     . $i]; //aqui
			if(strlen($xadmin_peca)==0) $xadmin_peca ="$login_admin"; //aqui
			if($xadmin_peca=="P")$xadmin_peca ="null"; //aqui
			
			if (strlen($xposicao) > 0) $xposicao = "'" . $xposicao . "'";
			else                       $xposicao = "null";

			if (strlen ($xqtde) == 0) $xqtde = "1";

			$xpeca    = str_replace ("." , "" , $xpeca);
			$xpeca    = str_replace ("-" , "" , $xpeca);
			$xpeca    = str_replace ("/" , "" , $xpeca);
			$xpeca    = str_replace (" " , "" , $xpeca);

    		if($login_fabrica == 20 AND $login_pais = 'CO'){
				echo $adicional[$i];
				if(strlen(trim($xadicional))>0)$xadicional = "'$xadicional'";
				else $xadicional = "'f'";
			}else $xadicional = 'null';


			if($login_fabrica == 20){
				$xdefeito = "141";  //Danificado
				$xservico = "258";  //Troca de Peça
			}

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
					$msg_erro = "Producto $produto no catastrado";
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
					$msg_erro = pg_errormessage($con);

					if (strlen ($msg_erro) > 0) {
						break ;
					}else{
						$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
						$os_produto  = pg_result ($res,0,0);
						$xpeca = strtoupper ($xpeca);

						if (strlen($xpeca) > 0 ) {
							if (strlen($xos_item) == 0){

							$sql = "SELECT tbl_peca.*
									FROM   tbl_peca
									WHERE  upper(tbl_peca.referencia_pesquisa) = upper('$xpeca')
									AND    tbl_peca.fabrica             = $login_fabrica;";
							$res = pg_exec ($con,$sql);

							if (pg_numrows ($res) == 0) {
								$msg_erro = "Repuesto $xpeca no catastrado";
								$linha_erro = $i;
							}else{
								$xpeca = pg_result ($res,0,peca);
							}

							if ($login_fabrica)
							if (strlen($xdefeito) == 0) $msg_erro = "Favor informar el defecto de repuesto"; #$defeito = "null";
							if (strlen($xservico) == 0) $msg_erro = "Favor informar el servicio realizado"; #$servico = "null";

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
											admin             ,
											adicional_peca_estoque
										)VALUES(
											$os_produto      ,
											$xpeca           ,
											$xposicao        ,
											$xqtde           ,
											$xdefeito        ,
											$xpcausa_defeito ,
											$xservico        ,
											$xadmin_peca     ,
											$xadicional
										)";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen ($msg_erro) > 0) {
									break ;
								}
							}
						}else{
							$sql = "UPDATE tbl_os_item SET
							os_produto        = $xos_produto    ,
    						posicao           = $xposicao       ,
							peca              = $xpeca          ,
							qtde              = $xqtde          ,
							defeito           = $xdefeito       ,
							causa_defeito     = $xpcausa_defeito,
							servico_realizado = $xservico       ,
							admin             = $admin_peca     ,
							adicional_peca_estoque = $xadicional
						    WHERE os_item = $xos_item;";
    						$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
					     }
						}
					}
				}
			}
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
					$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: os_press.php?os=$os");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($os) > 0) {
	#----------------- Le dados da OS --------------
	$sql = "SELECT  tbl_os.*,
			tbl_produto.referencia,
			tbl_produto.descricao ,
			tbl_produto.linha
		FROM    tbl_os
		LEFT JOIN tbl_produto USING (produto)
		WHERE   tbl_os.os = $os";
	$res = pg_exec ($con,$sql) ;

	$defeito_constatado = pg_result ($res,0,defeito_constatado);
	$causa_defeito      = pg_result ($res,0,causa_defeito);
	$linha              = pg_result ($res,0,linha);
	$consumidor_nome    = pg_result ($res,0,consumidor_nome);
	$sua_os             = pg_result ($res,0,sua_os);
	$produto_os         = pg_result ($res,0,produto);
	$produto_referencia = pg_result ($res,0,referencia);
	$produto_descricao  = pg_result ($res,0,descricao);
	$produto_serie      = pg_result ($res,0,serie);
	$qtde_produtos      = pg_result ($res,0,qtde_produtos);
	$posto              = pg_result ($res,0,posto);
	$obs                = pg_result ($res,0,obs);
	$solucao_os         = pg_result ($res,0,solucao_os);
}

#---------------- Carrega campos de configuração da Fabrica -------------
$sql = "SELECT  tbl_fabrica.os_item_subconjunto   ,
				tbl_fabrica.pergunta_qtde_os_item ,
				tbl_fabrica.os_item_serie         ,
				tbl_fabrica.os_item_aparencia     ,
				tbl_fabrica.qtde_item_os          
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$resX = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
	
	$pergunta_qtde_os_item = pg_result($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item) == 0) $pergunta_qtde_os_item = 'f';
	
	$os_item_serie = pg_result($resX,0,os_item_serie);
	if (strlen ($os_item_serie) == 0) $os_item_serie = 'f';
	
	$os_item_aparencia = pg_result($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia) == 0) $os_item_aparencia = 'f';
	
	$qtde_item = pg_result($resX,0,qtde_item_os);
	if (strlen ($qtde_item) == 0) $qtde_item = 5;
}

if (strlen($posto) > 0 ) {
	$resX = pg_exec ($con,"SELECT item_aparencia FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica");
} else {
	$msg_erro = 'Favor informe el codigo del servicio!';
}
if (pg_numrows($resX) > 0) $posto_item_aparencia = pg_result($resX,0,0);

$title = "Servicio Tecnico - Orden de Servicio";
$body_onload = "javascript: document.frm_os.defeito_constatado.focus()";

$layout_menu = 'callcenter';
include "cabecalho.php";

?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->
<?
#----------------- Le dados da OS --------------
if (strlen($os) > 0) {
	$sql = "SELECT  tbl_os.*                                      ,
			tbl_produto.referencia                        ,
			tbl_produto.descricao                         ,
			tbl_produto.voltagem                          ,
			tbl_produto.linha                             ,
			tbl_produto.familia                           ,
			tbl_os_extra.os_reincidente AS reincidente_os ,
			tbl_posto_fabrica.codigo_posto                ,
			tbl_posto_fabrica.reembolso_peca_estoque      
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_produto  USING (produto)
		JOIN    tbl_posto         USING (posto)
		JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
			  AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_os.os = $os";
	$res = @pg_exec ($con,$sql) ;
	
	if (@pg_numrows($res) > 0) {
		$login_posto            = pg_result($res,0,posto);
		$linha                  = pg_result($res,0,linha);
		$familia                = pg_result($res,0,familia);
		$consumidor_nome        = pg_result($res,0,consumidor_nome);
		$sua_os                 = pg_result($res,0,sua_os);
		$produto_os             = pg_result($res,0,produto);
		$produto_referencia     = pg_result($res,0,referencia);
		$produto_descricao      = pg_result($res,0,descricao);
		$produto_voltagem       = pg_result($res,0,voltagem);
		$produto_serie          = pg_result($res,0,serie);
		$qtde_produtos          = pg_result($res,0,qtde_produtos);
		$produto_type           = pg_result($res,0,type);
		$defeito_reclamado      = pg_result($res,0,defeito_reclamado);
		$defeito_constatado     = pg_result($res,0,defeito_constatado);
		$causa_defeito          = pg_result($res,0,causa_defeito);
		$posto                  = pg_result($res,0,posto);
		$obs                    = pg_result($res,0,obs);
		$os_reincidente         = pg_result($res,0,reincidente_os);
		$codigo_posto           = pg_result($res,0,codigo_posto);
		$reembolso_peca_estoque = pg_result($res,0,reembolso_peca_estoque);
		$consumidor_revenda     = pg_result($res,0,consumidor_revenda);
		$troca_faturada         = pg_result($res,0,troca_faturada);
		$motivo_troca           = pg_result($res,0,motivo_troca);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$tecnico_nome       	= pg_result ($res,0,tecnico_nome);
		$valores_adicionais 	= pg_result ($res,0,valores_adicionais);
		$justificativa_adicionais = pg_result ($res,0,justificativa_adicionais);
		$qtde_km            	= pg_result ($res,0,qtde_km);
		$produto_linha          = pg_result ($res,0,linha);
		$produto_familia        = pg_result ($res,0,familia);
		
		
		$sql_idioma = "SELECT * FROM tbl_produto_idioma JOIN tbl_produto USING(produto) WHERE referencia = '$produto_referencia' AND upper(idioma) = '$sistema_lingua'";
		
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}
	}

	if (strlen($os_reincidente) > 0) {
		$sql = "SELECT tbl_os.sua_os
				FROM   tbl_os
				WHERE  tbl_os.os      = $os_reincidente
				AND    tbl_os.fabrica = $login_fabrica
				AND    tbl_os.posto   = $login_posto;";
		$res = @pg_exec ($con,$sql) ;
		
		if (pg_numrows($res) > 0) $sua_os_reincidente = trim(pg_result($res,0,sua_os));
	}
}
?>

<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script language="JavaScript">
/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */
function fnc_pesquisa_peca_lista_intel (produto_referencia, peca_referencia, peca_descricao, peca_posicao, tipo) {
	var url = "";
	if (tipo == "tudo") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "referencia") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&peca=" + peca_referencia.value + "&tipo=" + tipo;
	}

	if (tipo == "descricao") {
		url = "peca_pesquisa_lista.php?produto=" + produto_referencia + "&descricao=" + peca_descricao.value + "&tipo=" + tipo;
	}
	if (peca_referencia.value.length >= 4 || peca_descricao.value.length >= 4) {
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
		janela.produto		= produto_referencia;
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.posicao		= peca_posicao;
		janela.focus();
	}else{
		alert("Digite al minus 4 caracteres!");
	}
}
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esto browser no tiene recursos para uso del Ajax"); ajax = null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes é o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Cargando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//após ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione el producto";//caso não seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o código do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaCombo(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//contéudo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Elija el defecto";
		//cria um novo option dinamicamente  
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Elija el defecto";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

function listaSolucao(defeito_constatado, produto_linha,defeito_reclamado, produto_familia) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");} 
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esto browser no tiene recursos para uso del Ajax"); ajax = null;}
	}
	}
//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros são excluídos
			document.forms[0].solucao_os.options.length = 1;
	//opcoes é o nome do campo combo
			idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_solucao.php?defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	
	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaComboSolucao(ajax.responseXML);//após ser processado-chama fun
		} else {idOpcao.innerHTML = "Elija el defecto constatado";//caso não seja um arquivo XML emite a mensagem abaixo
		}
		}
	}
	//passa o código do produto escolhido
			var params = "defeito_constatado="+defeito_constatado+"&defeito_reclamado="+defeito_reclamado+"&produto_linha="+produto_linha+"&produto_familia="+produto_familia;
	ajax.send(null);
		}
}

function montaComboSolucao(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//contéudo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					idOpcao.innerHTML = "";
		//cria um novo option dinamicamente  
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].solucao_os.options.add(novo);//adiciona o novo elemento
				}
			} else { idOpcao.innerHTML = "Ninguna solución encuentrada";//caso o XML volte vazio, printa a mensagem abaixo
			}
}
function listaConstatado(linha,familia, defeito_reclamado) {
//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
	catch(ex) { try {ajax = new XMLHttpRequest();}
		catch(exc) {alert("Esto browser no tiene recursos para uso del Ajax"); ajax = null;}
	}
	}

//se tiver suporte ajax
		if(ajax) {
	//deixa apenas o elemento 1 no option, os outros sï¿½ excluï¿½os
			document.forms[0].defeito_constatado.options.length = 1;
	//opcoes ï¿½o nome do campo combo
			idOpcao  = document.getElementById("opcoes2");
	//	 ajax.open("POST", "ajax_produto.php", true);
	
	ajax.open("GET","ajax_defeito_constatado.php?defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Cargando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {
			if(ajax.responseXML) {
				montaComboConstatado(ajax.responseXML);
			//apï¿½ ser processado-chama fun
			}
			else {
				idOpcao.innerHTML = "Elija el defecto reclamado";//caso nï¿½ seja um arquivo XML emite a mensagem abaixo
			}
		}
	}
	//passa o cï¿½igo do produto escolhido
	//var params ="defeito_reclamado="+defeito_reclamado+"&produto_familia="+familia+"&produto_linha="+linha";
	ajax.send(null);
		}
}

function montaComboConstatado(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
			if(dataArray.length > 0) {//total de elementos contidos na tag cidade
				for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
					var item = dataArray[i];
		//contï¿½do dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
					var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
					idOpcao.innerHTML = "Elija el defecto";
		//cria um novo option dinamicamente  
				var novo = document.createElement("option");
					novo.setAttribute("id", "opcoes2");//atribui um ID a esse elemento
							novo.value = codigo;		//atribui um valor
									novo.text  = nome;//atribui um texto
											document.forms[0].defeito_constatado.options.add(novo);//adiciona
//onovo elemento
				}
			} else { idOpcao.innerHTML = "Elija el defecto";//caso o XML volte vazio, printa a mensagem abaixo
			}
}


</script>

<p>

<?
if (strlen ($msg_erro) > 0) {

##### RECARREGA FORM EM CASO DE ERRO #####
	if (strlen($os) == 0) $os = $_POST["os"];
	$defeito_constatado = $_POST["defeito_constatado"];
	$defeito_reclamado  = $_POST["defeito_reclamado"];
	$causa_defeito      = $_POST["causa_defeito"];
	$obs                = $_POST["obs"];
	$solucao_os         = $_POST["solucao_os"];

	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
	if (strpos ($msg_erro," OS já finalizada. Não se pode alterar suas peças.") > 0) $msg_erro = " OS ya cerradas. No es posible cambiar sus piezas";
	if (strpos ($msg_erro,"pedido_fk") > 0) $msg_erro = "Esto item de la OS ya fue facturado. No puede ser borrado.";
?>
<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center">
		<font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<b>
<? 
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Fue detectado el seguiente error:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro; 
?>
		</b>
		</font>
	</td>
</tr>
</table>
<?
}

?>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>

	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="center">

		<!-- ------------- Formulário ----------------- -->
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type='hidden' name='voltagem' value='<? echo $voltagem ?>'>
		<input type='hidden' name='qtde_item' value='<? echo $qtde_item ?>'>
		<input type='hidden' name='produto_referencia' value='<? echo $produto_referencia ?>'>
		<p>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor </font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $consumidor_nome ?></b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Producto</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b>
				<?
				echo $produto_referencia . " - " . $produto_descricao;
				if (strlen($produto_voltagem) > 0) echo " - ".$produto_voltagem;
				?>
				</b>
				</font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
				<b><? echo $produto_serie ?></b>
				</font>
			</td>
		</tr>
		</table>

<?
if($login_fabrica==6 or $login_fabrica==3){
//relacionamento de integridade comeca aqui....
echo "<INPUT TYPE='hidden' name='xxproduto_linha' value='$produto_linha'>";
echo "<INPUT TYPE='hidden' name='xxproduto_familia' value='$produto_familia'>";


if(($login_fabrica==6 or $login_fabrica==3 OR $login_fabrica==24) and strlen($defeito_reclamado)>0){
//verifica se o defeito reclamado esta ativo, senao ele pede pra escolher de novo...acontece pq houve a mudança de tela.
	$sql = "SELECT ativo from tbl_defeito_reclamado where defeito_reclamado=$defeito_reclamado";
	$res = pg_exec($con,$sql);
	$xativo = @pg_result($res,0, ativo);
	
	if($xativo=='f'){
		$defeito_reclamado= "";
	}
	$sql = "SELECT defeito_reclamado 
			FROM tbl_diagnostico 
			WHERE fabrica=$login_fabrica 
			AND linha = $produto_linha 
			AND defeito_reclamado = $defeito_reclamado 
			AND familia = $produto_familia";
	$res = @pg_exec($con,$sql);
#if($ip=="201.43.11.131"){echo $sql;}
	$xativo = @pg_result($res,0, defeito_reclamado);
	if(strlen($xativo)==0){
		$defeito_reclamado= "";
	}
}

if ((strlen($defeito_reclamado)>0) and (($login_fabrica==3) or ($login_fabrica==6) or ($login_fabrica==15) or ($login_fabrica==11) or ($login_fabrica==24) or ($login_fabrica==5))){ 
	echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
	echo "<tr>";
	echo "<td>";
	echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defecto Reclamado</font><BR>";
	
	$sql = "SELECT 	defeito_reclamado, 
					descricao as defeito_reclamado_descricao 
			FROM tbl_defeito_reclamado 
			WHERE defeito_reclamado= $defeito_reclamado";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$xdefeito_reclamado = pg_result($res,0,defeito_reclamado);
		$xdefeito_reclamado_descricao = pg_result($res,0,defeito_reclamado_descricao);
		

	}
	echo "<INPUT TYPE='text' name='xxdefeito_reclamado' size='30' value='$xdefeito_reclamado - $xdefeito_reclamado_descricao' disabled>";

	echo "<INPUT TYPE='hidden' name='defeito_reclamado' value='$xdefeito_reclamado'>";
	echo "</td>";
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defecto Constatado</font><BR>";
	
	$sql = "SELECT 	distinct(tbl_diagnostico.defeito_constatado), 
					tbl_defeito_constatado.descricao 
			FROM tbl_diagnostico 
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado 
			WHERE tbl_diagnostico.linha = $produto_linha 
			AND tbl_diagnostico.defeito_reclamado = $defeito_reclamado 
			AND tbl_defeito_constatado.ativo='t' ";
	if (strlen($produto_familia)>0) $sql .=" AND tbl_diagnostico.familia=$produto_familia ";
	$sql.=" ORDER BY tbl_defeito_constatado.descricao";
 	$res = pg_exec($con,$sql);
 	
	echo "<select name='defeito_constatado' size='1' class='frm' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);'>";
	
	echo "<option value=''></option>";
	for ($y = 0 ; $y < pg_numrows ($res) ; $y++ ) {
		$xxdefeito_constatado = pg_result ($res,$y,defeito_constatado) ;
		$defeito_constatado_descricao = pg_result ($res,$y,descricao) ;
		
		echo "<option value='$xxdefeito_constatado'"; if($defeito_constatado==$xxdefeito_constatado) echo "selected"; echo ">$defeito_constatado_descricao</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solución</font><BR>";
	echo "<select name='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";

	$sql = "SELECT 	solucao, 
					descricao 
			FROM tbl_solucao 
			WHERE fabrica=$login_fabrica 
			AND solucao=$solucao_os";
	$res = pg_exec($con, $sql);
	$solucao_descricao = pg_result ($res,0,descricao);
	
	echo "<option id='opcoes' value='$solucao_os'>$solucao_descricao</option>";
	echo "</select>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<BR><BR>";
}
//FIM se tiver o defeito reclamado ativo
?>
		
<?
//caso nao achar defeito reclamado

if (strlen($defeito_reclamado)==0){
	echo "<table width='100%' border='0' cellspacing='5' cellpadding='0'>";
	echo "<tr>";
	echo "<td valign='top' align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defecto Reclamado</font><br>";
	echo "<select name='defeito_reclamado'  class='frm' style='width:220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
	echo "<option id='opcoes' value=''></option>";
	echo "</select>";
	echo "</td>";

	//CONSTATADO
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defecto Constatado</font><BR>";
	echo "<select name='defeito_constatado'  class='frm' style='width: 220px;' onfocus='listaConstatado(document.frm_os.xxproduto_linha.value, document.frm_os.xxproduto_familia.value,document.frm_os.defeito_reclamado.value);' >";
	echo "<option id='opcoes2' value=''></option>";
	echo "</select>";
	echo "</td>";
	//CONSTATADO
	//SOLUCAO
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Solución</font><BR>";
	echo "<select name='solucao_os' class='frm'  style='width:200px;' onfocus='listaSolucao(document.frm_os.defeito_constatado.value, document.frm_os.xxproduto_linha.value, document.frm_os.defeito_reclamado.value,  document.frm_os.xxproduto_familia.value);' >";
	echo "<option id='opcoes' value=''></option>";
	echo "</select>";
	echo "</td>";
	//SOLUCAO
	echo "</tr>";
	echo "</table>";
}
//fim caso nao achar defeito reclamado

//relacionamento de integridade termina aqui....

}
?>
<? if($login_fabrica<>6 and $login_fabrica<>3){ ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<? if ($login_fabrica != 5 and $login_fabrica<>20) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Reclamado</font>
				<br>
<?
		if ($login_fabrica != 1) {
			$sql = "SELECT *
					FROM   tbl_defeito_reclamado
					JOIN   tbl_linha USING (linha)
					WHERE  tbl_defeito_reclamado.linha = $linha
					AND    tbl_linha.fabrica           = $login_fabrica
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;

			if ($login_fabrica == 14) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_familia USING (familia)
						WHERE  tbl_defeito_reclamado.familia = $familia
						AND    tbl_familia.fabrica           = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql);
			}


			if (pg_numrows ($resD) == 0) {
				$sql = "SELECT *
						FROM   tbl_defeito_reclamado
						JOIN   tbl_linha USING (linha)
						WHERE  tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
			}
		}else{
			$sql = "SELECT  tbl_defeito_reclamado.defeito_reclamado ,
							tbl_defeito_reclamado.descricao         
					FROM    tbl_defeito_reclamado
					JOIN    tbl_linha   ON tbl_linha.linha     = tbl_defeito_reclamado.linha
					JOIN    tbl_familia ON tbl_familia.familia = tbl_defeito_reclamado.familia
					JOIN    tbl_produto ON tbl_produto.familia = tbl_familia.familia
					WHERE   tbl_defeito_reclamado.familia = tbl_familia.familia
					AND     tbl_familia.fabrica           = $login_fabrica
					AND     tbl_produto.produto           = $produto_os
					ORDER BY tbl_defeito_reclamado.descricao";
			$resD = pg_exec ($con,$sql);
		}

		if (@pg_numrows ($resD) > 0 AND $login_fabrica <> 5 and $login_fabrica <> 20) {
			echo "<select name='defeito_reclamado' size='1' class='frm'>";
			echo "<option value=''></option>";
			for ($i = 0 ; $i < pg_numrows ($resD) ; $i++ ) {
				echo "<option ";
				if ($defeito_reclamado == pg_result ($resD,$i,defeito_reclamado) ) echo " selected ";
				echo " value='" . pg_result ($resD,$i,defeito_reclamado) . "'>" ;
				echo pg_result ($resD,$i,descricao) ;
				echo "</option>";
			}
			echo "</select>";
		}else{
			echo $defeito_reclamado_descricao;
		}
?>
			</td>
			<? } ?>
			
			<?if($login_fabrica==20 AND ($tipo_atendimento==11 OR $tipo_atendimento==12)){}
			else{
				if ($pedir_defeito_constatado_os_item <> 'f') {?>

					<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Reparación</font>
					<br>
					<select name="defeito_constatado" size="1" class="frm">
					<option selected></option>
					<?
					$sql =	"SELECT defeito_constatado_por_familia,
									defeito_constatado_por_linha
							FROM tbl_fabrica
							WHERE fabrica = $login_fabrica;";
					$res = pg_exec ($con,$sql);
					$defeito_constatado_por_familia = pg_result ($res,0,0) ;
					$defeito_constatado_por_linha   = pg_result ($res,0,1) ;
//					echo "//////////// $defeito_constatado_por_linha ////////////\n";

					if ($defeito_constatado_por_familia == 't') {
						$sql = "SELECT tbl_defeito_constatado.*
								FROM   tbl_familia
								JOIN   tbl_familia_defeito_constatado USING(familia)
								JOIN   tbl_defeito_constatado         USING(defeito_constatado)
								WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
								AND    tbl_familia_defeito_constatado.familia = $familia";
						if ($consumidor_revenda == 'C' AND $login_fabrica == 1) {
							$sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
						}
					}else{
						if ($defeito_constatado_por_linha == 't') {
							$sql   = "SELECT linha FROM tbl_produto WHERE produto = $produto_os";
							$res   = pg_exec ($con,$sql);
							$linha = pg_result ($res,0,0) ;

							$sql = "SELECT tbl_defeito_constatado.*
									FROM   tbl_defeito_constatado
									JOIN   tbl_linha USING(linha)
									WHERE  tbl_defeito_constatado.fabrica         = $login_fabrica
									AND    tbl_linha.linha = $linha";
							if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
							$sql .= " ORDER BY tbl_defeito_constatado.descricao";
						}else{
							$sql = "SELECT tbl_defeito_constatado.*
									FROM   tbl_defeito_constatado
									WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
							if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> 1 ";
							$sql .= " ORDER BY tbl_defeito_constatado.codigo;";
						}
					}

					if($login_fabrica==15){
						$sql="select * from tbl_defeito_constatado where fabrica=$login_fabrica order by descricao";
					}

					if ($login_fabrica==20) {
						$sql = "SELECT tbl_defeito_constatado.* 
								FROM tbl_defeito_constatado 
								JOIN tbl_produto_defeito_constatado 
									ON  tbl_defeito_constatado.defeito_constatado = tbl_produto_defeito_constatado.defeito_constatado 
									AND tbl_produto_defeito_constatado.produto = $produto_os
								WHERE fabrica = $login_fabrica
								ORDER BY tbl_defeito_constatado.descricao";
						//echo $sql;
					}
				
					$res = pg_exec ($con,$sql) ;
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					
						$descricao_d = pg_result ($res,$i,descricao);

        $sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma
                        WHERE defeito_constatado = $id_dc
                        AND upper(idioma)        = 'ES'";
                        
        $res_idioma = @pg_exec($con,$sql_idioma);
          if (@pg_numrows($res_idioma) >0) {
            $defeito_constatado  = trim(@pg_result($res_idioma,0,descricao));
        }
        
						//--=== Tradução para outras linguas ============================= Raphael HD:1212
						$sql_idioma = "SELECT * FROM tbl_defeito_constatado_idioma WHERE defeito_constatado = ".pg_result ($res,$i,defeito_constatado)." AND upper(idioma) = 'ES'";
					
						$res_idioma = @pg_exec($con,$sql_idioma);
						if (@pg_numrows($res_idioma) >0) {
							$descricao_d  = trim(@pg_result($res_idioma,0,descricao));
						}
						//--=== Tradução para outras linguas ================================================

						echo "<option ";
						if ($defeito_constatado == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
						echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
						echo pg_result ($res,$i,codigo) ." - ". $descricao_d ;
						echo "</option>";
					}?>
					</select>
					</td>
			<?
				} 
			}
			?>
			
			<? if ($pedir_causa_defeito_os_item != "f" && $login_fabrica != 5) { ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defecto</font>
				<br>
				<select name="causa_defeito" size="1" class="frm">
					<option selected></option>
				<?
				$sql = "SELECT tbl_causa_defeito.*
						FROM   tbl_causa_defeito
						WHERE  tbl_causa_defeito.fabrica = $login_fabrica
						ORDER BY tbl_causa_defeito.codigo, tbl_causa_defeito.descricao;";
				$res = pg_exec ($con,$sql) ;
				
				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					$causa_defeitoRes	= pg_result($res,$i,causa_defeito);
					$descricaoRes = "";
					if (strlen (trim (pg_result ($res,$i,codigo))) > 0) $descricaoRes = pg_result ($res,$i,codigo) . " - ";
					$descricaoRes .= pg_result($res,$i,descricao);
					
	        $sql_idioma = " SELECT * FROM tbl_causa_defeito_idioma
                WHERE causa_defeito = $causa_defeitoRes
                AND upper(idioma)   = 'ES'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $descricaoRes  = trim(@pg_result($res_idioma,0,descricao));
        }
					if ($causa_defeito == $causa_defeitoRes) 
						$sel = " selected "; 
					else 
						$sel = "";
					echo "<option value='$causa_defeitoRes' $sel>$descricaoRes</option>";
				}
				?>
				</select>
			</td>
			<? } ?>
		</tr>
		</table>

		<?if ($pedir_solucao_os_item <> 'f') { 

		?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td align="left" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Identificación </font>
				<br>
				<select name="solucao_os" size="1" class="frm">
					<option value=""></option>
				<?
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

				if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
					$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
				}

				if ($login_fabrica == 1) {
					if ($reembolso_peca_estoque == 't') {
						$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
					}else{
						$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
						$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha is null) ";
					}
				}
				if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS NOT TRUE ";
				$sql .= " AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";

				$res = pg_exec ($con,$sql) ;

				if (pg_numrows($res) == 0) {
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

					if ($login_pede_peca_garantia == 't' AND $login_fabrica <> 1) {
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					}

					if ($login_fabrica == 1) {
						if ($reembolso_peca_estoque == 't') {
							$sql .= "AND (tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao ILIKE 'subst%') ";
						}else{
							$sql .= "AND (tbl_servico_realizado.descricao ILIKE 'troca%' ";
							$sql .= "OR tbl_servico_realizado.descricao NOT ILIKE 'subst%') ";
						}
					}
					$sql .=	" AND tbl_servico_realizado.linha IS NULL
							AND tbl_servico_realizado.ativo IS TRUE ORDER BY descricao ";
					$res = pg_exec ($con,$sql) ;
				}

				for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
				  $sr_descricao = pg_result ($res,$x,descricao);
	        
          $sql_idioma = " SELECT * FROM tbl_servico_realizado_idioma
                WHERE servico_realizado = ".pg_result ($res,$x,servico_realizado)."
                AND upper(idioma)   = 'ES'";
        $res_idioma = @pg_exec($con,$sql_idioma);
        if (@pg_numrows($res_idioma) >0) {
            $sr_descricao  = trim(@pg_result($res_idioma,0,descricao));
        }				
					echo "<option ";
					if ($solucao_os == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo $sr_descricao ;
					if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
					echo "</option>";
				}
				?>
				</select>
			</td>
		</tr>
		</table>



		<?
		}
		?>
<?
}




if (strlen($troca_faturada) == 0) {

		### LISTA ITENS DA OS QUE POSSUEM PEDIDOS
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                   ,
							tbl_os_item.pedido                                    ,
							tbl_os_item.qtde                                      ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                   ,
							tbl_peca.descricao                                    ,
							tbl_defeito.defeito                                   ,
							tbl_defeito.descricao AS defeito_descricao            ,
							tbl_produto.referencia AS subconjunto                 ,
							tbl_os_produto.produto                                ,
							tbl_os_produto.serie                                  ,
							tbl_servico_realizado.servico_realizado               ,
							tbl_servico_realizado.descricao AS servico_descricao  ,
							tbl_pedido.pedido_blackedecker  AS pedido_blackedecker,
							tbl_pedido.pedido_acessorio     AS pedido_acessorio   ,  
							tbl_os_item.adicional_peca_estoque
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					LEFT JOIN    tbl_pedido            ON tbl_pedido.pedido = tbl_os_item.pedido
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido NOTNULL
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_exec ($con,$sql) ;
			
			if(pg_numrows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				
				echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Piezas já facturadas</b></font></td>\n";
				
				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referencia</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descripción</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Ctd</b></font></td>\n";
				if($login_fabrica == 20  and $login_pais == 'CO'){
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Estoque</b></font></td>";
				}

				echo "</tr>\n";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$faturado      = pg_numrows($res);
						$fat_item      = pg_result($res,$i,os_item);
						$fat_pedido    = pg_result($res,$i,pedido);
						$fat_peca      = pg_result($res,$i,referencia);
						$fat_descricao = pg_result($res,$i,descricao);
						$fat_qtde      = pg_result($res,$i,qtde);
						$adicional     = pg_result($res,$i,adicional_peca_estoque);

						
						echo "<tr height='20' bgcolor='#FFFFFF'>";
						
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>";

						#------- Bloquado exclusão de item de OS pelo TULIO.... 14/02
						#------- Admin exclui e depois distribuidor fica reclamando que mudou numero do pedido
						#------- Caso do GRALA x BRITANIA 
						#------- Se for excluir, temos que mandar email pra todos os envolvidos... Posto, Distribuidor e ADMIN
						#------- Por enquanto, não excluir
						
						#------- Liberado de novo em 16/02
						#------- Herio disse que vai apagar no EMS os pedidos antigos
						echo "<img src='imagens/btn_x.gif' width='15' height='12' onclick=\"javascript: if(confirm('Deseja realmente excluir o item da OS?') == true){ window.location='$PHP_SELF?os_item=$fat_item&os=$os';}\" style='cursor:pointer;'>&nbsp;&nbsp;";

						if ($login_fabrica == 1) {
							$fat_pedido = trim(pg_result ($res,$i,pedido_blackedecker));
							$pedido_acessorio    = trim(pg_result ($res,$i,pedido_acessorio));
							if ($pedido_acessorio == 't') $fat_pedido = intval($pedido_blackedecker + 1000);
						}

						echo "$fat_pedido</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$fat_qtde</font></td>\n";
						if($login_fabrica == 20  and $login_pais == 'CO'){
							echo "<td><input name='adicional_peca_estoque_$i' value='t' class='frm' type='checkbox' ";
							if($adicional=='t') echo "checked";
							echo "> <font size='1'>Repuesto del estoque</font></td>";
						}
						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}
		
		### LISTA ITENS DA OS QUE ESTÃO COMO NÃO LIBERADAS PARA PEDIDO EM GARANTIA
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.porcentagem_garantia                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao,
							tbl_os_item.adicional_peca_estoque                                 
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.liberacao_pedido NOTNULL
					AND     tbl_os_item.liberacao_pedido IS FALSE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_exec ($con,$sql) ;
			
			if(pg_numrows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				
				if ($login_fabrica == 14) {
					echo "<td align='center' colspan='6'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia</b></font></td>\n";
				}else{
					if ($login_fabrica <> 6) {
						echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças que não irão gerar pedido em garantia</b></font></td>\n";
					}else{
						echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Piezas pendentes</b></font></td>\n";
					}
				}
				
				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				
				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Acciones</b></font></td>\n";
				}
				
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referencia</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descripción</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Ctq</b></font></td>\n";
				if($login_fabrica == 20  and $login_pais == 'CO'){
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Estoque</b></font></td>";
				}
				
				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Valor</b></font></td>\n";
				}
				
				echo "</tr>\n";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$recusado      = pg_numrows($res);
						$rec_item      = pg_result($res,$i,os_item);
						$rec_obs       = pg_result($res,$i,obs);
						$rec_peca      = pg_result($res,$i,referencia);
						$rec_descricao = pg_result($res,$i,descricao);
						$rec_qtde      = pg_result($res,$i,qtde);
						$rec_preco     = pg_result($res,$i,porcentagem_garantia);
						$adicional     = pg_result($res,$i,adicional_peca_estoque);
						
						echo "<tr height='20' bgcolor='#FFFFFF'>";
						
						if ($login_fabrica == 14) {
							echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'><a href='$PHP_SELF?liberar=$rec_item&os=$os'>LIBERAR ITEM</a></font></td>\n";
						}
						
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";
						if($login_fabrica == 20  and $login_pais == 'CO'){
							echo "<td><input name='adicional_peca_estoque_$i' value='t' class='frm' type='checkbox' ";
							if($adicional=='t') echo "checked";
							echo "> <font size='1'>Repuesto del estoque</font></td>";
						}
						
						if ($login_fabrica == 14) {
							echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>". str_replace(".",",",$rec_preco) ."</font></td>\n";
						}
						
						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}
		
		### LISTA ITENS DA OS FORAM LIBERADAS E AINDA NÃO POSSI PEDIDO
		if(strlen($os) > 0){
		//aqui
			$sql = "SELECT  tbl_os_item.os_item                                 ,
							tbl_os_item.obs                                     ,
							tbl_os_item.qtde                                    ,
							tbl_os_item.porcentagem_garantia                    ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.referencia                                 ,
							tbl_peca.descricao                                  ,
							tbl_defeito.defeito                                 ,
							tbl_defeito.descricao AS defeito_descricao          ,
							tbl_produto.referencia AS subconjunto               ,
							tbl_os_produto.produto                              ,
							tbl_os_produto.serie                                ,
							tbl_servico_realizado.servico_realizado             ,
							tbl_servico_realizado.descricao AS servico_descricao
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN    tbl_defeito           USING (defeito)
					LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido NOTNULL
					AND     tbl_os_item.liberacao_pedido IS TRUE
					ORDER BY tbl_os_item.os_item ASC;";
			$res = pg_exec ($con,$sql) ;
			
			if(pg_numrows($res) > 0) {
				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				
				if ($login_fabrica == 14) {
					echo "<td align='center' colspan='5'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peças aprovadas aguardando pedido</b></font></td>\n";
				}else{
					echo "<td align='center' colspan='4'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Repuesto aprobado aguardando pedido</b></font></td>\n";
				}
				
				echo "</tr>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Referencia</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descripción</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";
				
				if ($login_fabrica == 14) {
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Valor</b></font></td>\n";
				}
				
				echo "</tr>\n";
				
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$recusado      = pg_numrows($res);
						$rec_item      = pg_result($res,$i,os_item);
						$rec_obs       = pg_result($res,$i,obs);
						$rec_peca      = pg_result($res,$i,referencia);
						$rec_descricao = pg_result($res,$i,descricao);
						$rec_qtde      = pg_result($res,$i,qtde);
						$rec_preco     = pg_result($res,$i,porcentagem_garantia);
						
						echo "<tr height='20' bgcolor='#FFFFFF'>";
						
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_obs</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_peca</font></td>\n";
						echo "<td align='left'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$rec_qtde</font></td>\n";
						if($login_fabrica == 20  and $login_pais == 'CO'){
						echo "<td><input name='adicional_peca_estoque_$i' value='t' class='frm' type='checkbox' ";
						if($adicional=='t') echo "checked";
						echo "> <font size='1'>Repuesto del estoque</font></td>";
    					}

						
						if ($login_fabrica == 14) {
							echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>". str_replace(".",",",$rec_preco) ."</font></td>\n";
						}
						
						echo "</tr>\n";
				}
				echo "</table>\n";
			}
		}
		
		if(strlen($os) > 0 AND strlen ($msg_erro) == 0){
			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia;";
				$resX = @pg_exec($con,$sql);
				$inicio_itens = @pg_numrows($resX);
			}else{
				$inicio_itens = 0;
			}
//aqui
			$sql = "SELECT  tbl_os_item.pedido                                                 ,
							tbl_os_item.qtde                                                   ,
							tbl_os_item.liberacao_pedido                                       ,
							tbl_os_item.obs                                                    ,
							tbl_os_item.posicao                                                ,
							tbl_os_item.causa_defeito                                          ,
							tbl_os_item.admin  as admin_peca                    ,
							tbl_peca.peca                                       ,
							tbl_peca.referencia                                                ,
							tbl_peca.descricao                                                 ,
							tbl_defeito.defeito                                                ,
							tbl_defeito.descricao                   AS defeito_descricao       ,
							tbl_produto.referencia                  AS subconjunto             ,
							tbl_os_produto.produto                                             ,
							tbl_os_produto.serie                                               ,
							tbl_servico_realizado.servico_realizado                            ,
							tbl_servico_realizado.descricao         AS servico_descricao       ,
							tbl_causa_defeito.descricao             AS causa_defeito_descricao ,
							tbl_os_item.adicional_peca_estoque                                 
					FROM    tbl_os_item
					JOIN    tbl_os_produto             USING (os_produto)
					JOIN    tbl_produto                USING (produto)
					JOIN    tbl_os                     USING (os)
					JOIN    tbl_peca                   USING (peca)
					LEFT JOIN tbl_defeito              USING (defeito)
					LEFT JOIN tbl_servico_realizado    USING (servico_realizado)
					LEFT JOIN tbl_causa_defeito ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
					WHERE   tbl_os.os      = $os
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os_item.pedido           ISNULL
					AND     tbl_os_item.liberacao_pedido ISNULL
					ORDER BY tbl_os_item.os_item;";
			$res = pg_exec ($con,$sql) ;
			
			if (pg_numrows($res) > 0) {
				$fim_itens = $inicio_itens + pg_numrows($res);
				$i = 0;
				for ($k = $inicio_itens ; $k < $fim_itens ; $k++) {
					$pedido[$k]                  = pg_result($res,$i,pedido);
					$peca[$k]                    = pg_result($res,$i,referencia);
					$qtde[$k]                    = pg_result($res,$i,qtde);
					$posicao[$k]                 = pg_result($res,$i,posicao);
					$produto[$k]                 = pg_result($res,$i,subconjunto);
					$serie[$k]                   = pg_result($res,$i,serie);
					$descricao[$k]               = pg_result($res,$i,descricao);
					$defeito[$k]                 = pg_result($res,$i,defeito);
					$defeito_descricao[$k]       = pg_result($res,$i,defeito_descricao);
					$pcausa_defeito[$k]          = pg_result($res,$i,causa_defeito);
					$causa_defeito_descricao[$k] = pg_result($res,$i,causa_defeito_descricao);
					$servico[$k]                 = pg_result($res,$i,servico_realizado);
					$servico_descricao[$k]       = pg_result($res,$i,servico_descricao);
					$admin_peca[$k]              = pg_result($res,$i,admin_peca);//aqui
					$idioma_peca[$k]             = pg_result($res,$i,peca);
					$xadicional[$k]              = pg_result($res,$i,adicional_peca_estoque);

				$sql_idioma = " SELECT * FROM tbl_peca_idioma
            WHERE peca = $idioma_peca[$k] 
            AND upper(idioma)   = 'ES'";

        $res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
            $descricao[$i]  = trim(@pg_result($res_idioma,0,descricao));
        }
					if(strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }

					$i++;

				}
			}else{
				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$produto[$i]        = $_POST["produto_"        . $i];
					$serie[$i]          = $_POST["serie_"          . $i];
					$posicao[$i]        = $_POST["posicao_"        . $i];
					$peca[$i]           = $_POST["peca_"           . $i];
					$qtde[$i]           = $_POST["qtde_"           . $i];
					$defeito[$i]        = $_POST["defeito_"        . $i];
					$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
					$servico[$i]        = $_POST["servico_"        . $i];
					$admin_peca[$i]     = $_POST["admin_peca_"     . $i]; //aqui
					$xadicional[$i]     = $_POST['adicional_peca_estoque_' . $i];

					if (strlen($peca[$i]) > 0) {
						$sql = "SELECT  tbl_peca.referencia,
										tbl_peca.descricao
								FROM    tbl_peca
								WHERE   tbl_peca.fabrica    = $login_fabrica
								AND     tbl_peca.referencia = $peca[$i];";
						$resX = @pg_exec ($con,$sql) ;
						
						if (@pg_numrows($resX) > 0) {
							$descricao[$i] = trim(pg_result($resX,0,descricao));
						}
					}
				}
			}
		}else{
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$produto[$i]        = $_POST["produto_"        . $i];
				$serie[$i]          = $_POST["serie_"          . $i];
				$posicao[$i]        = $_POST["posicao_"        . $i];
				$peca[$i]           = $_POST["peca_"           . $i];
				$qtde[$i]           = $_POST["qtde_"           . $i];
				$defeito[$i]        = $_POST["defeito_"        . $i];
				$pcausa_defeito[$i] = $_POST["pcausa_defeito_" . $i];
				$servico[$i]        = $_POST["servico_"        . $i];
				$admin_peca[$i]     = $_POST["admin_peca_"     . $i];//aqui
				$xadicional[$i]     = $_POST['adicional_peca_estoque_' . $i];
			

				if (strlen($peca[$i]) > 0) {
					$sql = "SELECT  tbl_peca.peca,
                  tbl_peca.referencia,
									tbl_peca.descricao
							FROM    tbl_peca
							WHERE   tbl_peca.fabrica    = $login_fabrica
							AND     tbl_peca.referencia = '$peca[$i]';";
					$resX = @pg_exec ($con,$sql) ;
					
					if (@pg_numrows($resX) > 0) {
						$descricao[$i] = trim(pg_result($resX,0,descricao));
						$xxp[$i] = trim(pg_result($resX,0,descricao));

					}
				}
			}
		}
		
		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";
		
		if ($os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto</b></font></td>";
		}
		
		if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>N. Série</b></font></td>";
		}

		if ($login_fabrica == 14) echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";

		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Codigo</b></font>&nbsp;&nbsp;&nbsp;<a class='lnk' href='peca_consulta_por_produto.php?produto=$produto_os' target='_blank'><font color='#FFFFFF'>Lista Básica</font></a></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descripción</b></font></td>";
		
		if ($pergunta_qtde_os_item == 't')
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Ctd</b></font></td>";
		
		if($login_fabrica == 20  and $login_pais == 'CO'){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Estoque</b></font></td>";
		}
		
		echo "</tr>";
		
		$loop = $qtde_item;
#		if (strlen($faturado) > 0) $loop = $qtde_item - $faturado;

		if($login_fabrica == 6) $loop = $loop+5;

		$offset = 0;
		for ($i = 0 ; $i < $loop ; $i++) {
			echo "<tr>";
			
			echo "<input type='hidden' name='descricao'>";
			echo "<input type='hidden' name='preco'>";
			echo "<input type='hidden' name='admin_peca_$i' value='$admin_peca[$i]'>";//aqui
			if ($os_item_subconjunto == 'f') {
				echo "<input type='hidden' name='produto_$i' value='$produto_referencia'>";
			}else{
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='produto_$i'>";
				#echo "<option></option>";
				
				$sql = "SELECT  tbl_produto.produto   ,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM    tbl_subproduto
						JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
						WHERE   tbl_subproduto.produto_pai = $produto_os
						ORDER BY tbl_produto.referencia;";
				$resX = pg_exec ($con,$sql) ;
				
				echo "<option value='$produto_referencia' ";
				if ($produto[$i] == $produto_referencia) echo " selected ";
				echo " >$produto_descricao</option>";
				
				for ($x = 0 ; $x < pg_numrows ($resX) ; $x++ ) {
					echo "<option ";
					if (trim ($produto[$i]) == trim (pg_result ($resX,$x,referencia))) echo " selected ";
					echo " value='" . pg_result ($resX,$x,referencia) . "'>" ;
					echo pg_result ($resX,$x,referencia) . " - " . substr(pg_result ($resX,$x,descricao),0,15) ;
					echo "</option>";
				}
				
				echo "</select>";
				echo "</td>";
			}
			
			if ($os_item_subconjunto == 'f') {
				$xproduto = $produto[$i];
				echo "<input type='hidden' name='serie_$i'>";
			}else{
				if ($os_item_serie == 't') {
					echo "<td align='center'><input class='frm' type='text' name='serie_$i' size='9' value='$serie[$i]'></td>";
				}
			}

			if ($os_item_aparencia == 't' AND $posto_item_aparencia == 't' and $os_item_subconjunto == 'f') {
				$sql = "SELECT  tbl_peca.peca      ,
								tbl_peca.referencia,
								tbl_peca.descricao ,
								tbl_lista_basica.qtde
						FROM    tbl_peca
						JOIN    tbl_lista_basica USING (peca)
						JOIN    tbl_produto      USING (produto)
						WHERE   tbl_produto.produto     = $produto_os
						AND     tbl_peca.fabrica        = $login_fabrica
						AND     tbl_peca.item_aparencia = 't'
						ORDER BY tbl_peca.referencia
						LIMIT 1 OFFSET $offset;";
				$resX = @pg_exec ($con,$sql) ;

				if (@pg_numrows($resX) > 0) {
					$xpeca       = trim(pg_result($resX,0,peca));
					$xreferencia = trim(pg_result($resX,0,referencia));
					$xdescricao  = trim(pg_result($resX,0,descricao));
					$xqtde       = trim(pg_result($resX,0,qtde));
					


					if ($peca[$i] == $xreferencia) 
						$check = " checked ";
					else
						$check = "";
					
//					echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$xreferencia'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"referencia\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
//					echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$xdescricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , \"descricao\" )' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'></td>";
					
					echo "<td align='left'><input class='frm' type='checkbox' name='peca_$i' value='$xreferencia' $check>&nbsp;<font face='arial' size='-2' color='#000000'>$xreferencia</font></td>";
					echo "<td align='left'><font face='arial' size='-2' color='#000000'>$xdescricao</font></td>";
					echo "<td align='center'><font face='arial' size='-2' color='#000000'>$xqtde</font><input type='hidden' name='qtde_$i' value='$xqtde'></td>";
					if($login_fabrica == 20  and $login_pais == 'CO'){
						echo "<td><input name='adicional_peca_estoque_$i' value='t' class='frm' type='checkbox' ";
						if($adicional=='t') echo "checked";
						echo "> <font size='1'>Repuesto del estoque</font></td>";
					}

				}else{
					echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i, document.frm_os.preco , document.frm_os.voltagem, \"referencia\", document.frm_os.qtde_$i, $posto )' alt='Click para efetuar la busca' style='cursor:pointer;'></td>";
					echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\", document.frm_os.qtde_$i, $posto )' alt='Click para efetuar la busca' style='cursor:pointer;'></td>";
					if ($pergunta_qtde_os_item == 't') {
						echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";
					}
					if($login_fabrica == 20  and $login_pais == 'CO'){
						echo "<td><input name='adicional_peca_estoque_$i' value='t' class='frm' type='checkbox' ";
						if($adicional=='t') echo "checked";
						echo "> <font size='1'>Repuesto del estoque</font></td>";
					}

				}
			}else{
				if ($login_fabrica == 14) echo "<td align='center'><input class='frm' type='text' name='posicao_$i' size='5' maxlength='5' value='$posicao[$i]'></td>\n";

				echo "<td align='center'><input class='frm' type='text' name='peca_$i' size='15' value='$peca[$i]'";
			
				echo ">&nbsp;<a href='#'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"referencia\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"referencia\", document.frm_os.qtde_$i, $posto )'";
				echo " alt='Haga un click para efetuar la busca' style='cursor:pointer;'></a></td>";
				
				echo "<td align='center'><input class='frm' type='text' name='descricao_$i' size='25' value='$descricao[$i]'";
				if ($login_fabrica == 5) echo "onblur=\"javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, 'descricao', document.frm_os.qtde_$i, $posto)\"";
				echo ">&nbsp;<a href='#'><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'";
				if ($login_fabrica == 14) echo " onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i , document.frm_os.descricao_$i , document.frm_os.posicao_$i , \"descricao\")'";
				else echo " onclick='javascript: fnc_pesquisa_peca_lista (document.frm_os.produto_$i.value , document.frm_os.peca_$i, document.frm_os.descricao_$i , document.frm_os.preco , document.frm_os.voltagem, \"descricao\", document.frm_os.qtde_$i, $posto )'";
				echo " alt='Haga un click para efetuar la busca' style='cursor:pointer;'></a></td>";
				if ($pergunta_qtde_os_item == 't') {
					echo "<td align='center'><input class='frm' type='text' name='qtde_$i' size='3' value='$qtde[$i]'></td>";
				}
			}
			
			##### C A U S A   D O   D E F E I T O   D O   I T E M #####
			if ($pedir_causa_defeito_os_item == 't' AND $login_fabrica<>20) {
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='pcausa_defeito_$i'>";
				echo "<option selected></option>";
				
				$sql =	"SELECT *
						FROM tbl_causa_defeito
						WHERE fabrica = $login_fabrica
						ORDER BY codigo, descricao";
				$res = pg_exec ($con,$sql) ;
				
				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($pcausa_defeito[$i] == pg_result ($res,$x,causa_defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,causa_defeito) . "'>" ;
					echo pg_result ($res,$x,codigo) ;
					echo " - ";
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
				
				echo "</select>";
				echo "</td>\n";
			}
			
			##### D E F E I T O   D O   I T E M #####
			if ($login_fabrica<>20) {
				echo "<td align='center'>";
				echo "<select class='frm' size='1' name='defeito_$i'>";
				echo "<option selected></option>";

				$sql = "SELECT *
						FROM   tbl_defeito
						WHERE  tbl_defeito.fabrica = $login_fabrica
						AND    tbl_defeito.ativo IS TRUE
						ORDER BY descricao;";
				$res = pg_exec ($con,$sql) ;
				
				for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
					echo "<option ";
					if ($defeito[$i] == pg_result ($res,$x,defeito)) echo " selected ";
					echo " value='" . pg_result ($res,$x,defeito) . "'>" ;
					if (strlen(trim(pg_result($res,$x,codigo_defeito))) > 0) {
						echo pg_result($res,$x,codigo_defeito);
						echo " - " ;
					}
					echo pg_result($res,$x,descricao);
					echo "</option>";
				}
				
				echo "</select>";
				echo "</td>";

				echo "<td align='center'>";
	
				echo "<select class='frm' size='1' name='servico_$i' style='width:150px'>";
				echo "<option selected></option>";


				#### SERVIÇO REALIZADO #####
				$sql = "SELECT *
						FROM   tbl_servico_realizado
						WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";

				if (strlen($linha) > 0) $sql .= " AND (tbl_servico_realizado.linha = '$linha' OR tbl_servico_realizado.linha IS NULL) ";
				//(tbl_servico_realizado.linha = '203' OR tbl_servico_realizado.linha IS NULL)

				if ($login_fabrica == 1) {
					if ($reembolso_peca_estoque == 't') 
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
					else
						$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'subst%' ";
				}
				if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";

				$sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
				$sql .= "ORDER BY gera_pedido DESC, descricao ASC;";

				$res = pg_exec($con,$sql) ;
				
				if (pg_numrows($res) == 0) {
					$sql = "SELECT *
							FROM   tbl_servico_realizado
							WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
					
					if ($login_fabrica == 1) {
						if ($reembolso_peca_estoque == 't') 
							$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'troca%' ";
						else
							$sql .= "AND tbl_servico_realizado.descricao NOT ILIKE 'subst%' ";
					}
					
					$sql .= "AND tbl_servico_realizado.linha IS NULL ";
					$sql .= "AND tbl_servico_realizado.ativo   IS TRUE ";
					if($login_fabrica==20) $sql .=" AND tbl_servico_realizado.solucao IS TRUE ";
					$sql .= "ORDER BY gera_pedido DESC, descricao ASC;";

					$res = pg_exec($con,$sql) ;
				}
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++ ) {
					echo "<option ";
					if ($servico[$i] == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					if (pg_result ($res,$x,gera_pedido) == 't' AND $login_fabrica == 6) echo " - GERA PEDIDO DE PEÇA ";
					echo "</option>";
				}
				
				echo "</select>";
				echo "</td>";
			}
			echo "</tr>";
			
			$offset = $offset + 1;
		}
// 		echo "$teste<BR>$teste2";
		echo "</table>";
		?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>



<table width='650' align='center' border='0' cellspacing='0' cellpadding='5'>

<? 
$nosso_ip = include ('../nosso_ip.php');
if(($ip==$nosso_ip) or ($ip=="201.42.45.29") OR ($ip=="201.76.86.97") OR ($ip=="201.42.147.251")OR($ip=='201.43.245.148')){ 
 if($login_fabrica==15){ ?>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<table width='40%' align='center' border='0' cellspacing='0' cellpadding='3' bgcolor="#B63434">
			<tr>
			<td valign="middle" align="RIGHT">
			<FONT SIZE="1" color='#FFFFFF'>***Data Fechamento:   </FONT> 
			</td>
				<td valign="middle" align="LEFT">
			<INPUT TYPE="text" NAME="data_fechamento" value="<? echo $data_fechamento; ?>" size="12" maxlength="10" class="frm">
			<BR><font size='1' color='#FFFFFF'>dd/mm/aaaa</font>
			</td>
			</tr>
			</table>
	</td>
</tr>
<? } ?>
<? } ?>




<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<br>
		Observaciones: <INPUT TYPE="text" NAME="obs" value="<? echo $obs; ?>" size="70" maxlength="255" class="frm">
		<br><br>
	</td>
</tr>
</table>

<? }else{ ?>

<input type="hidden" name="troca_faturada" value="<?echo $troca_faturada?>">
<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td align="left" nowrap>
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

					$res = pg_exec ($con,$sql) ;
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						echo "<option ";
						if ($motivo_troca == pg_result ($res,$i,defeito_constatado) ) echo " selected ";
						echo " value='" . pg_result ($res,$i,defeito_constatado) . "'>" ;
						echo pg_result ($res,$i,descricao) ." - ". pg_result ($res,$i,codigo) ;
						echo "</option>\n";
					}
					?>
			</select>
		</td>
	</tr>
</table>
<? } ?>

<br>

<input type="hidden" name="btn_acao" value="">

<center><img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde sumisión') }" ALT="Guardar itenes de la orden de servicio" border='0' style="cursor:pointer;"></center>

</form>

<br>

<? include "rodape.php";?>
