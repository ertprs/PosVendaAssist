<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

function validaRegramakita($condicao,$referencia,$posto,$linha) {
	$_GET['condicao'] = $condicao;
	$_GET['produto_referencia'] = $referencia;
	$_GET['posto'] = $posto;
	$_GET['linha_form'] = $linha;
	ob_start();
	include "makita_valida_regras.php";
	ob_get_clean();
	$makita_preco = number_format ($makita_preco,2,".",".");
	return $makita_preco;
}

$login_bloqueio_pedido = $_COOKIE['cook_bloqueio_pedido'];

$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_result ($res,0,0) == 'f') {
	include "cabecalho.php";
	echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
	include "rodape.php";
	exit;
}

#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;
$limit_pedidos = 2;

/* Suggar liberou até 4 pedido em garantia - HD 22397 23862*/ // HD 33373 // HD 60077
$limite_posto = array(720,20235,476);

if($login_posto==2474){
	$limit_pedidos = 4;
}

if($login_posto==19566){
	$limit_pedidos = 99;
}


/*
if( $_GET[ 'delete' ] )
{
	setcookie ('cook_pedido', "", time() - 3600);
	unset( $cook_pedido );
	header("Location : $PHP_SELF");
}
*/




$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";
$qtde_item = 40;

if($login_posto==2474){
	$qtde_item = 70;
}



if( !$_GET[ 'delete' ]) {
 $sql = "SELECT  tbl_pedido.pedido                                              ,
				tbl_pedido.pedido_blackedecker                                 ,
				tbl_pedido.condicao                                            ,
				tbl_pedido.pedido_cliente                                      ,
				tbl_pedido.tipo_pedido                                         ,
				tbl_tipo_pedido.descricao as tipo_pedido_descricao             ,
				tbl_condicao.descricao                                         ,
				tbl_pedido.seu_pedido
		FROM    tbl_pedido
		JOIN    tbl_condicao USING(condicao)
		JOIN    tbl_tipo_pedido USING(tipo_pedido)
		WHERE   tbl_pedido.exportado           IS NULL
		AND     tbl_pedido.admin               IS NULL
		AND     tbl_pedido.posto             = $login_posto
		AND     tbl_pedido.fabrica           = $login_fabrica
		AND     tbl_pedido.finalizado          IS NULL
		AND (tbl_pedido.status_pedido is null or tbl_pedido.status_pedido <>14);";

//echo nl2br($sql);
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
	$cook_pedido           = trim(pg_fetch_result($res,0,pedido));
	$condicao              = trim(pg_fetch_result($res,0,condicao));
	$descricao_condicao    = trim(pg_fetch_result($res,0,descricao));
	$pedido_cliente        = trim(pg_fetch_result($res,0,pedido_cliente));
	$tipo_pedido           = trim(pg_fetch_result($res,0,tipo_pedido));
	$tipo_pedido_descricao = trim(pg_fetch_result($res,0,tipo_pedido_descricao));
}
	
}


if ($btn_acao == "gravar"){



if ( strlen( $cook_pedido ) ==0 ){
	unset( $cook_pedido );
	setcookie ('cook_pedido');
	$cook_pedido = "";
}
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];
	$qtde_item         = $_POST['qtde_item'];

	/*
	 * 
	 * Essa rotina foi incluida nesse ponto do script, para nao pertmitir que seja 
	 * criado um cookie com conteúdo vazio, caso seja selecionada a opcao 'gravar' sem o preenchimento de nenhum item.
	 * 
	*/

		for( $i = 0; $i < 30 ; $i++ )
			{
				$peca_descricao[$i] = trim( $_POST[ "peca_ref_descricao" ] );
				echo $peca_descricao[$i];
			}
		
		if( empty( $peca_descricao ) )
		{
		//	$msg_erro = "Não foi digitada a descrição ou referência do produto";
		}
		$qtde = trim( $_POST[ 'qtde_0' ] );
				if (strlen ($qtde) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) )	{
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
			}
	$aux_condicao = (strlen($condicao) == 0) ? "null" : $condicao ;
	if(strlen($condicao) == 0){
		$msg_erro = "Favor digitar a condição de pagamento!";
	}
	$aux_pedido_cliente = (strlen($pedido_cliente) == 0) ? "null" : "'". $pedido_cliente ."'";
	$aux_transportadora = (strlen($transportadora) == 0) ? "null" : $transportadora ;
	$aux_observacao_pedido = (strlen($observacao_pedido) == 0) ? "null" : "'$observacao_pedido'" ;

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	}else{
		$sql = "SELECT	tipo_pedido
				FROM	tbl_tipo_pedido
				WHERE	descricao IN ('Faturado','Venda')
				AND		fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$aux_tipo_pedido = "'". pg_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}

	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";

	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_exec($con,$sql);
			if (pg_numrows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}

	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if (strlen ($pedido) == 0 and strlen($cook_pedido)==0) {
			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						fabrica        ,
						condicao       ,
						pedido_cliente ,
						transportadora ,
						linha          ,
						tipo_pedido    ,
						digitacao_distribuidor,
						obs
						$sql_campo
					) VALUES (
						$posto              ,
						$login_fabrica      ,
						$aux_condicao       ,
						$aux_pedido_cliente ,
						$aux_transportadora ,
						$aux_linha          ,
						$aux_tipo_pedido    ,
						$digitacao_distribuidor,
						$aux_observacao_pedido
						$sql_valor
					)";
	//if($ip=='201.76.78.194') echo nl2br($sql);
	
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			
			if (strlen($msg_erro) == 0){
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
				$cook_pedido = pg_fetch_result ($res,0,0);
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						condicao       = $aux_condicao       ,
						pedido_cliente = $aux_pedido_cliente ,
						transportadora = $aux_transportadora ,
						linha          = $aux_linha          ,
						tipo_pedido    = $aux_tipo_pedido
					WHERE pedido  = $cook_pedido
					AND   posto   = $login_posto
					AND   fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	if (strlen ($msg_erro) == 0) {

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$pedido_item     = trim($_POST['pedido_item_'     . $i]);
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$peca_descricao = trim($_POST['peca_descricao_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			$preco           = trim($_POST['preco_'           . $i]);
			

			
			if (strlen($peca_referencia)>0) {
				$peca_referencia;
				$preco = str_replace('.','',$preco); 
				$makita_preco =  validaRegramakita($condicao,$peca_referencia,$login_posto,$i);
			}
			if ($peca_descricao == 'Não encontrado') {
				$peca_referencia = '';
			}
			
			
			if (strlen ($peca_referencia) > 0 AND (strlen($preco)==0 or $preco == '0,00')){
				$msg_erro = "Não foi encontrado preço para a Peça $peca_referencia ($preco). Favor pesquisar e colocar a quantidade novamente, caso persistir, entrar em contato com a Makita!";
				$linha_erro = $i;
				break;
			}

			$qtde_anterior = 0;
			$peca_anterior = "";
			if (strlen($pedido_item) > 0 AND $login_fabrica==3){
				$sql = "SELECT peca,qtde
						FROM tbl_pedido_item
						WHERE pedido_item = $pedido_item";
				$res = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (pg_numrows ($res) > 0){
					$peca_anterior = pg_result($res,0,peca);
					$qtde_anterior = pg_result($res,0,qtde);
				}
			}

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0 || $_GET[ 'delete' ] ){
				//var_dump($pedido_item . '--' . $cook_pedido);
				//return;
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $pedido_item
						AND		pedido = $cook_pedido";
				$res = pg_exec ($con,$sql);

				$sql = "SELECT pedido from tbl_pedido_item where pedido = $cook_pedido";
				$res = pg_exec ($con,$sql);

				if (pg_num_rows($res)==0) {
					$sql = "DELETE	FROM	tbl_pedido
						WHERE	pedido = $cook_pedido";
						$res = pg_exec ($con,$sql);
				}

				setcookie ($cook_pedido, "", time() - 3600);
				unset($cook_pedido);	
				header( "Location : $PHP_SELF" );
			}

			if (strlen ($peca_referencia) > 0) {
				$peca_referencia = trim (strtoupper ($peca_referencia));
				$peca_referencia = str_replace ("-","",$peca_referencia);
				$peca_referencia = str_replace (".","",$peca_referencia);
				$peca_referencia = str_replace ("/","",$peca_referencia);
				$peca_referencia = str_replace (" ","",$peca_referencia);

				$sql = "SELECT  tbl_peca.peca   ,
								tbl_peca.origem ,
								tbl_peca.promocao_site,
								tbl_peca.qtde_disponivel_site ,
								tbl_peca.qtde_max_site,
								tbl_peca.multiplo_site
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_exec ($con,$sql);
				$peca          = pg_result ($res,0,peca);
				$promocao_site = pg_result ($res,0,promocao_site);
				$qtde_disp     = pg_result ($res,0,qtde_disponivel_site);
				$qtde_max      = pg_result ($res,0,qtde_max_site);
				$qtde_multi    = pg_result ($res,0,multiplo_site);

				if (pg_numrows ($res) == 0) {
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca   = pg_result ($res,0,peca);
					$origem = trim(pg_result ($res,0,origem));
				}

				if (strlen ($preco) == 0) $preco = "null";
				$preco = str_replace (",",".",$preco);

				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {
					if (strlen($pedido_item) == 0){
						 $sql = "INSERT INTO tbl_pedido_item (
									pedido ,
									peca   ,
									qtde   ,
									preco
								) VALUES (
									$cook_pedido ,
									$peca   ,
									$qtde   ,
									$preco
								)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde
								WHERE pedido_item = $pedido_item";
					}
	//if($ip=='201.76.78.194') echo nl2br($sql);
	
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($cook_pedido,$peca,$login_fabrica)";
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

	if (strlen ($msg_erro) == 0) { 
		//$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ( $_GET[ 'delete' ] ){
				$pedido = $_GET[ 'pedido' ];
				$pedido_item = $_GET[ 'delete' ];
				//var_dump($pedido . '--' . $pedido_item);
				//return;
				 $sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido  = $pedido
						AND pedido_item = $pedido_item";
				$res = pg_exec ($con,$sql);
				
				$sql = "SELECT pedido from tbl_pedido_item where pedido = $pedido";
				$res = pg_exec ($con,$sql);

				if (pg_num_rows($res)==0) {
					 $sql = "UPDATE tbl_pedido set fabrica = 0 WHERE pedido = $pedido";
					$res = pg_exec ($con,$sql);
				}
				
				setcookie ($cook_pedido, "", time() - 3600);
				unset($cook_pedido);			
				echo "<script>window.location.href='$PHP_SELF'</script>";				
}


$btn_acao = $_GET['btn_acao'];

if (strlen($btn_acao=='Finalizar')) {
	
	if (strlen ($cook_pedido) > 0) { 
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: pedido_finalizado.php?pedido=$cook_pedido&loc=1");
		}
	}
}

#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT	TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.obs                                                    ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.total_original                                         ,
					tbl_pedido.permite_alteracao
			FROM	tbl_pedido
			LEFT JOIN tbl_transportadora USING (transportadora)
			left JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto        USING (produto)
			WHERE	tbl_pedido.pedido   = $pedido
			AND		tbl_pedido.posto    = $login_posto
			AND		tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$data                  = trim(pg_result ($res,0,data));
		$transportadora        = trim(pg_result ($res,0,transportadora));
		$transportadora_cnpj   = trim(pg_result ($res,0,transportadora_cnpj));
		$transportadora_codigo = trim(pg_result ($res,0,transportadora_codigo));
		$transportadora_nome   = trim(pg_result ($res,0,transportadora_nome));
		$pedido_cliente        = trim(pg_result ($res,0,pedido_cliente));
		$tipo_pedido           = trim(pg_result ($res,0,tipo_pedido));
		$produto               = trim(pg_result ($res,0,produto));
		$produto_referencia    = trim(pg_result ($res,0,produto_referencia));
		$produto_descricao     = trim(pg_result ($res,0,produto_descricao));
		$linha                 = trim(pg_result ($res,0,linha));
		$condicao              = trim(pg_result ($res,0,condicao));
		$exportado             = trim(pg_result ($res,0,exportado));
		$total_original        = trim(pg_result ($res,0,total_original));
		$permite_alteracao     = trim(pg_result ($res,0,permite_alteracao));
		$observacao_pedido     = @pg_result ($res,0,obs);
	}
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
}



$title       = "CADASTRO DE PEDIDOS DE PEÇAS";
$layout_menu = 'pedido';

if(!empty($cook_pedido)) {
	
	$sql = "SELECT pedido
			FROM tbl_pedido
			WHERE pedido = $cook_pedido
			AND   fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) == 0){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
}




include "cabecalho.php";

?>
<style type="text/css">
	.menu_top { 
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; 
		font-size: 10px; 
		font-weight: bold; 
		border: 0px solid;
		color:'#ffffff';
		background-color: '#596D9B';
	}
	.table_line1 { 
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; 
		font-size: 11px;
		font-weight: normal;
		border: 0px solid;
	}
</style>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/php.default.min.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<SCRIPT LANGUAGE="JavaScript">




function autocompletar_item(campo1,campo2,conteudo,linha) {
	var hora = new Date();
	var engana = hora.getTime();

//	alert("item_pesquisa_ajax_makita_test.php?q=" + conteudo + "&cache_bypass=$cache_bypass");
	$('#'+campo1).autocomplete("item_pesquisa_ajax_makita.php?q=" + conteudo + "&cache_bypass="+engana, {
		minChars: 3,
		delay: 150,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[3]},
		formatResult: function(row)  {return row[2];}
	});

	    $('#'+campo1).result(function(event, data, formatted) {
		$('#'+campo2).val(data[0]);
		$('#peca_referencia_'+linha).val(data[1]);
		$('#qtde_'+linha).focus();
	});
}


function exibeTipo(){
	f = document.frm_pedido;
	if(f.linha.value == 3){
		f.tipo_pedido.disabled = false;
	}else{
		f.tipo_pedido.selectedIndex = 0;
		f.tipo_pedido.disabled = true;
	}
}

function confirmaCondicao(condicao) {
	var valida = $('#validacondicao');
	var condicaoanterior = $('#condicaoanterior');
	if(confirm('Atenção a condição de pagamento pode influenciar no preço das peças, tem certeza que deseja a condicao '+condicao+ '? Caso corfime se precisar alterar os dados digitados serão perdidos')==true) {
		if (valida.val()=='sim') {
			var qtde = $('#qtde_item').val();
			for (i=0;i<qtde;i++) {
				$('#preco_'+i).val(' ');
				$('#sub_total_'+i).val(' ');
			}
		}
		valida.val('sim');
		condicaoanterior.val($('#condicao').val());
	} else {
		if (valida.val()=='sim') {
			valida.val('nao');
			var seleciona = "option[value="+"'"+condicaoanterior.val()+"']";~
			$("#condicao "+seleciona).attr('selected', 'selected');
		} else {
			valida.val('nao');
		}
	}
}

function adicionarLinha(linha) {
	linha = parseInt(linha) + 1;
		/*se ainda na criou a linha de item */
		if (!document.getElementById('peca_referencia_'+linha)) {
			var tbl = document.getElementById('tabela_itens');
			//var lastRow = tbl.rows.length;
			//var iteration = lastRow;

			//Atualiza a qtde de linhas
//			$('#qtde_produto').val(linha);

			/*Criar TR - Linha*/
			var nova_linha = document.createElement('tr');
			nova_linha.setAttribute('rel', linha);

			/********************* COLUNA 1 ****************************/
			/*Cria TD */
			var celula = criaCelula('');
			celula.style.cssText = 'text-align: left;';

			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('name', 'peca_ref_descricao_' + linha);
			el.setAttribute('id', 'peca_ref_descricao_' + linha);
			el.setAttribute('size', '40');
			el.onfocus = function(){
				autocompletar_item('peca_ref_descricao_'+linha,'peca_referencia_'+linha,this.value,linha);
			};
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			var el = document.createElement('input');
			el.setAttribute('type', 'hidden');
			el.setAttribute('name', 'peca_referencia_' + linha);
			el.setAttribute('id', 'peca_referencia_' + linha);
			el.setAttribute('size', '15');
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/*Cria TD */
			var celula = criaCelula('');
			celula.style.cssText = 'text-align: left;';

			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('name', 'qtde_' + linha);
			el.setAttribute('id', 'qtde_' + linha);
			el.setAttribute('size', '5');
			el.onblur = function() {
				fnc_makita_preco (linha);
				adicionarLinha(linha);
			}
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/*Cria TD */
			var celula = criaCelula('');
			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('style', 'text-align: right;');
			el.setAttribute('name', 'preco_' + linha);
			el.setAttribute('id', 'preco_' + linha);
			el.setAttribute('size', '10');
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/*Cria TD */
			var celula = criaCelula('');
			var el = document.createElement('input');
			el.setAttribute('type', 'text');
			el.setAttribute('style', 'text-align: right;');
			el.setAttribute('name', 'sub_total_' + linha);
			el.setAttribute('id', 'sub_total_' + linha);
			el.setAttribute('rel', 'total_pecas');
			el.setAttribute('size', '10');
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/************ FINALIZA LINHA DA TABELA ***********/
			var tbody = document.createElement('TBODY');
			tbody.appendChild(nova_linha);
			tbl.appendChild(tbody);

			$('#qtde_item').val(linha);

			adicionarLinha2(linha);
		
		};
}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}


function adicionarLinha2(linha) {

	linha = parseInt(linha);
		/*se ainda na criou a linha de item */
			var tbl = document.getElementById('tabela_itens');
			//var lastRow = tbl.rows.length;
			//var iteration = lastRow;

			//Atualiza a qtde de linhas
//			$('#qtde_produto').val(linha);

			/*Criar TR - Linha*/
			var nova_linha = document.createElement('tr');

			/********************* COLUNA 1 ****************************/

			/*Cria TD */
			var celula = criaCelula('');
			celula.setAttribute('colspan','7');
			celula.style.cssText = 'text-align: left;';

			var el = document.createElement('div');
			el.setAttribute('name', 'mudou_' + linha);
			el.setAttribute('id', 'mudou_' + linha);
			celula.appendChild(el);

			nova_linha.appendChild(celula);

			/************ FINALIZA LINHA DA TABELA ***********/
			var tbody = document.createElement('TBODY');
			tbody.appendChild(nova_linha);
			tbl.appendChild(tbody);
}

function Trim(s){
		var l=0;
		var r=s.length -1;

		while(l < s.length && s[l] == ' '){
			l++;
		}
		while(r > l && s[r] == ' '){
			r-=1;
		}
		return s.substring(l, r+1);
	}

function importarPecas(campo) {

	var lote_pecas = campo.value;
	var condicao = window.document.frm_pedido.condicao.value ;
	var posto    = <?= $login_posto ?>;
	var array_lote = new Array();
	array_lote = lote_pecas.split("\n");
	var erros = '';
	
	if (condicao.length==0) {
		alert('Selecione uma condição para fazer o upload');
		document.getElementById('divAguarde').style.display='none';
	} else {
		for (i = 0; i < array_lote.length ; i++){

			var array_peca = new Array();
			array_peca = array_lote[i].split("\t");
			
			var referencia = array_peca[0] ;
			referencia = Trim(referencia);
			var qtde       = array_peca[1];

			adicionarLinha(i-1);

			linha = parseFloat(i);

			url = 'makita_valida_regras.php?linha_form=' + linha + '&posto=<?= $login_posto ?>&produto_referencia=' + referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';
			
			 var campos = $.ajax({
							type: "GET",
							url: url,
							cache: false,
							async: false
			 }).responseText;

			campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
			campos = campos.substring (0,campos.indexOf('</preco>'));
			campos_array = campos.split("|");

			preco      = campos_array[0] ;
			linha_form = campos_array[1] ;
			descricao  = campos_array[2] ;
			mudou      = campos_array[3] ;
			de         = campos_array[4] ;
			referencia = campos_array[5] ;

			if (mudou == 'SIM') {
				$('#linhadiv_'+linha_form).css('display','block');
				$('#mudou_'+linha_form).css('display','block');
				$('#mudou_'+linha_form).css('background-color','red');
				$('#mudou_'+linha_form).html('A peça acima entrou no lugar desta '+de +' que foi enviada no upload');
			}

			if (descricao.length>0) {
				$('#peca_referencia_'+i).val(referencia);
				$('#qtde_'+i).val(qtde);
				//('#peca_descricao_'+i).val(descricao);
				$('#peca_ref_descricao_'+i).val(referencia+'-'+descricao);
			} else {
				var erros = erros +" Peça não encontrada:  " + referencia +" ; ";
				$('#peca_referencia_'+i).val(referencia);
				$('#qtde_'+i).val(qtde);
				$('#peca_descricao_'+i).val('Não encontrado');
				$('#peca_referencia_'+i).parent().css('background-color','red');
			}

			campo_preco = 'preco_' + linha_form;
			if (descricao.length>0) {
				document.getElementById(campo_preco).value = preco;
				fnc_calcula_total(linha_form);
			} else {
				document.getElementById(campo_preco).value = ''
				$('#sub_total_'+linha_form).val('');
			}
		}
			if (erros.length>0) {
				alert(erros+' Caso não altere estas peças serão retiradas do pedido');
			}
	}
	document.getElementById('divAguarde').style.display='none';
}
</SCRIPT>

<style type="text/css">
body {
	font: 80% Verdana,Arial,sans-serif;
	/* An explicit background color needed for the Safari browser. */
	/* Without this, Safari users will see black in the corners. */
	background: #FFF;
}

/* The styles below are NOT needed to make .corner() work in your page. */
/*

h1 {
	font: bold 150% Verdana,Arial,sans-serif;
	margin: 0 0 0.25em;
	padding: 0;
	color: #009;
}
h2 {
	font: bold 100% Verdana,Arial,sans-serif;
	margin: 0.75em 0 0.25em;
	padding: 0;
	color: #006;
}
ul {
	margin-top: 0.25em;
	padding-top: 0;
}
code {
	font: 90% Courier New,monospace;
	color: #33a;
	font-weight: bold;
}
#demo {

}*/


.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 1px 1px; /* padding greater than corner height|width */
/*	margin: 1em 0.25em;*/
	font-size:12px;
	color:#FFFFFF;
}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 10px auto;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}

.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:black;
}

.content h1 {
	color:black;
	font-size: 120%;
}

.extra {
	background:#BFDCFB;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.extra span {
	color:#FF0D13;
	font-size:14px;
	font-weight:bold;
	padding-left:30px;
}

.error {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}
.error h1 {
	color:#FFFFFF;
	font-size:14px;
	font-size:normal;
	text-transform: capitalize;
}

.inicio {
	background:#8BBEF8;
	width: 600px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.inicio h1 {
	color:white;
	font-size: 105%;
	font-weight:bold;
}

.subinicio {
	background:#E1EEFD;
	width: 550px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.subinicio h1 {
	color:white;
	font-size: 105%;
}

#tabela {
	font-size:12px;
}
#tabela td{
	font-weight:bold;
}

.xTabela{
	font-family: Verdana, Arial, Sans-serif;
	font-size:12px;
	padding:3px;
}

.xTabela td{
	/*border-bottom:2px solid #9E9E9E;*/
}

</style>

<style type="text/css">

#layout{
	width: 700px;
	margin:0 auto;
}

ul#split, ul#split li{
	margin:50px;
	margin:0 auto;
	padding:0;
	width:700px;
	list-style:none
}

ul#split li{
	float:left;
	width:700px;
	margin:0 10px 10px 0
}

ul#split h3{
	font-size:14px;
	margin:0px;
	padding: 5px 0 0;
	text-align:center;
	font-weight:bold;
	color:white;
}
ul#split h4{
	font-size:90%
	margin:0px;
	padding-top: 1px;
	padding-bottom: 1px;
	text-align:center;
	font-weight:bold;
	color:white;
}

ul#split p{
	margin:0;
	padding:5px 8px 2px;

}
p{

	background-color:# D9E2EF;
}
ul#split div{
	background: #D9E2EF;
}

li#one{
	text-align:left;

}

li#one div{
	border:1px solid #D9E2EF
}
li#one h3{
	background: #D9E2EF
}

li#one h4{
	background: #D9E2EF
}

.coluna1{
	width:150px;
	font-weight:bold;
	font-size:11px;
	display: inline;
	float:left;

}
.titulo_tabela{
background-color:#596d9b;
font: bold 14px "Arial";
color:#FFFFFF;
text-align:center;
}


.titulo_coluna{
background-color:#596d9b;
font: bold 11px "Arial";
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
	   border:1px solid #596d9b;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
text-align:center;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
}
</style>

<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript">
/*
	$(document).ready(function(){
		$(".titulo").corner("round");
		$(".subtitulo").corner("round");
		$(".content").corner("round 10px");
		$(".error").corner("dog2 10px");
		$(".extra").corner("dog");
		$(".inicio").corner("round");
		$(".subinicio").corner("round");

	});
*/
</script>

<!-- Bordas Arredondadas para a NIFTY -->
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>


<? include "javascript_pesquisas.php" ?>


<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script type="text/javascript">
function fnc_makita_preco (linha_form) {
	condicao = window.document.frm_pedido.condicao.value ;
	posto    = <?= $login_posto ?>;
	
	if ((condicao.length)==0){
		alert("Por favor escolha uma condição de pagamento");
		return false;
	}
	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = "";

	peca_referencia = 'peca_referencia_' + linha_form;
	peca_referencia = document.getElementById(peca_referencia).value;

	url = 'makita_valida_regras.php?linha_form=' + linha_form + '&posto=<?= $login_posto ?>&produto_referencia=' + peca_referencia + '&condicao=' + condicao + '&cache_bypass=<?= $cache_bypass ?>';
	requisicaoHTTP ('GET', url , true , 'fnc_makita_responde_preco');

}

function fnc_makita_responde_preco (campos) {
	campos = campos.substring (campos.indexOf('<preco>')+7,campos.length);
	campos = campos.substring (0,campos.indexOf('</preco>'));
	campos_array = campos.split("|");

	preco      = campos_array[0] ;
	linha_form = campos_array[1] ;
	campo_preco = 'preco_' + linha_form;
	document.getElementById(campo_preco).value = preco;
	fnc_calcula_total(linha_form);
}

function fnc_calcula_total (linha_form) {
	var total = 0;
	preco = document.getElementById('preco_'+linha_form).value;
	qtde = document.getElementById('qtde_'+linha_form).value;
	preco = preco.replace(',','.');
	//preco = preco.replace('.',',');
	var preco =  parseFloat(preco);
	if (qtde && preco) {
		total = qtde * preco;
		total = total.toFixed(2);
		total = total.replace( '.' , ',' );
	}

	document.getElementById('sub_total_'+linha_form).value = total;

	//Totalizador
	var total_pecas = 0;
	$("input[rel='total_pecas']").each(function(){
		if ($(this).val()){
			tot = $(this).val();
			tot = tot.replace( ',' , '.' );
			tot = parseFloat (tot);
			total_pecas += tot;
			//total_pecas = number_format( total_pecas, 2 , ',' );
			
		}
	});
	
	

	<?if (!in_array($login_fabrica,array(24,30))) { ?>
	var total_pecas_aux = document.getElementById('total_pecas').value;
		total_pecas_aux = total_pecas_aux.replace( '.' , '' );
		total_pecas_aux = total_pecas_aux.replace( ',' , '' );
		total_pecas_aux = number_format( total_pecas, 2 , ',' ); //total_pecas.toFixed(2);
		document.getElementById('total_pecas').value = total_pecas_aux;
	<?}?>
}

function atualiza_proxima_linha(linha_form){
	var produto_referencia = document.getElementById('produto_referencia_'+linha_form).value;
	var produto_descricao  = document.getElementById('produto_descricao_'+linha_form).value;

	var proxima_linha = linha_form + 1;

	if (document.getElementById('produto_descricao_'+proxima_linha)){
		if (! document.getElementById('produto_descricao_'+proxima_linha).value){
			document.getElementById('produto_referencia_'+proxima_linha).value = produto_referencia;
			document.getElementById('produto_descricao_'+proxima_linha).value = produto_descricao;
		}
	}


}

</script>

<!--  Mensagem de Erro-->
<?
if (strlen ($msg_erro) > 0) {
	
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
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
	?>
	<div id="layout">
	<div class="msg_erro">
	<? echo $erro . $msg_erro; ?>
	</div>
	</div>
<? } ?>

<?


/*
 * Comentado, pois a variavel sql abaixo estava sendo sobrescrita logo abaixo.
$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
*/
$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		AND     ( 
					(tbl_condicao.codigo_condicao = 'OUT' and tbl_posto_fabrica.tipo_posto = 236)
						OR
					(tbl_condicao.codigo_condicao <> 'OUT' and tbl_posto_fabrica.tipo_posto <> 236)
				)
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$frase = "Preencha seu Pedido de Compra/Garantia";
}else{
	$frase = "Preencha seu Pedido de Compra";
}
?>

<br>

<!-- OBSERVAÇÕES -->
<div id="layout">
<? if ($login_fabrica<>30) {   //HD 70768-1 - Retirar mensagem na Esmaltec  ?>

	<div class="texto_avulso" style='width: 700px;'>
	<?
		echo "Pedidos a Prazo Dependerão de Análise do Departamento de Crédito. <br />";
   }    // fim HD 70768-1 ?>
	</div>
</div>

<form name="frm_pedido" method="post" action="">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido; ?>">
<input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">

<center>
<p>
<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr valign='top' style='font-size:12px'>
		<td nowrap align='center'>
		Distribuidor pode digitar pedidos para seus postos.
		<br>
		Digite o código do posto
		<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
		ou deixe em branco para seus próprios pedidos.
		</td>
	</tr>
	</table>
<? } ?>

<br>

<!-- INICIA DIVISÃO -->

<ul id="split"  style="width:750px;" bgcolor="#D9E2EF" style="margin-left:50px;">
<li id="one">

<div class='formulario' style="margin-left:0px;width:100%">
	<table width='700' class='formulario' align='center'>
		<tr>
			<td class="titulo_tabela" width="100%">Cadastro de Pedido</td>
		</tr>
		<tr class='subtitulo'>
			<td align="center"><? echo $frase; ?> </td>
		</tr>
	</table>

	<p><span class='coluna1'>Ordem de Compra</span>
	<input class="frm" type="text" name="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
	</p>
	<?
	$res = pg_exec ("SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

	#permite_alteracao - HD 47695
	if (pg_result ($res,0,0) == 'f' OR $permite_alteracao == 't') {
		echo "<input type='hidden' name='condicao' value=''>";
	} else { ?>
		
	<p><span class='coluna1'>Condição Pagamento</span>
		<input type='hidden' id='validacondicao' name='validacondicao' value=''>
		<input type='hidden' id='condicaoanterior' name='condicaoanterior' value=''>
		<select size='1' id='condicao' name='condicao' class='frm'  onchange='confirmaCondicao(this.options[this.selectedIndex].text)' >
			<option value="">- selecione</option>
		<?
			//echo "<option value=''></option>";
			 if (strlen($cook_pedido)==0) {
				 $sql1 = "SELECT  tbl_condicao.*
						FROM    tbl_condicao
						JOIN    tbl_posto_condicao USING (condicao)
						JOIN    tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE   tbl_posto_condicao.posto = $login_posto
						AND     tbl_condicao.fabrica     = $login_fabrica
						AND     tbl_condicao.visivel IS TRUE
						AND     tbl_condicao.descricao ilike '%garantia%'
						ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";

				$res = pg_exec ($con,$sql1);

				if (pg_numrows ($res) == 0 ) {
					$sql = "SELECT tbl_condicao.*
							FROM tbl_condicao 
							WHERE tbl_condicao.fabrica = $login_fabrica
							AND tbl_condicao.visivel IS TRUE 
							AND (
								(tbl_condicao.codigo_condicao = 'OUT' and $login_posto in (
									select posto 
									from tbl_posto_fabrica 
									where tipo_posto = 236 and tbl_posto.posto = $login_posto 
									and tbl_posto_fabrica.fabrica = $login_fabrica) )
								or
								(tbl_condicao.codigo_condicao != 'OUT')
							)
							ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0')";
					$res = pg_exec ($con,$sql);
				}

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option value='" . pg_result ($res,$i,condicao) . "'";
					if (pg_result ($res,$i,condicao) == $condicao) echo " selected";
					echo ">" . pg_result ($res,$i,descricao) . "</option>";
				}
			} else {
					echo "<option value='$condicao' selected>$descricao_condicao</option>";
			}
		?>
		</select>
	</p>

<? } ?>

		<?
		//VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
		$sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica
				WHERE fabrica=$login_fabrica AND posto=$login_posto";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$garantia_antecipada = pg_result($res,0,0);
			if($garantia_antecipada <> "t") {
				$garantia_antecipada ="f";
			}
		}
		?>
		
		<p><span class='coluna1'>Tipo de Pedido</span>
		<?
		// se posto pode escolher tipo_pedido

		if (strlen($cook_pedido)==0) {
			$sql = "SELECT   *
					FROM     tbl_posto_fabrica
					WHERE    tbl_posto_fabrica.posto   = $login_posto
					AND      tbl_posto_fabrica.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);


			if ($login_tipo_posto == 269) {
				$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (187,136)";
			}else{
				$cond_locadora = "AND tbl_tipo_pedido.tipo_pedido in (136)";
			}

			if (pg_numrows($res) > 0) {
				echo "<select size='1' name='tipo_pedido' class='frm'>";
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica 
						$cond_locadora";
				$sql .= " ORDER BY tipo_pedido DESC ";
				$res = pg_exec ($con,$sql);

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
					if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
						echo " selected";
					}
					echo ">" . pg_result($res,$i,descricao) . "</option>";
				}
				echo "</select>";
			}else{
				echo "<select size='1' name='tipo_pedido' class='frm' ";
				if ($login_fabrica == 3) {
					echo "disabled";
				}
				echo ">";
				$sql = "SELECT   *
						FROM    tbl_tipo_pedido
						WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
						   OR	tbl_tipo_pedido.descricao ILIKE '%Venda%')
						AND     tbl_tipo_pedido.fabrica = $login_fabrica
						AND     (garantia_antecipada is false or garantia_antecipada is null)
						ORDER BY tipo_pedido;";

				#HD 47695
				if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica ";
					if (strlen($tipo_pedido)>0){
						$sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
					}
					$sql .= " ORDER BY tipo_pedido;";
				}

				$res = pg_exec ($con,$sql);

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
					if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
					echo ">" . pg_result($res,$i,descricao) . "</option>";
				}
				if($garantia_antecipada=="t"){
					$sql = "SELECT   *
							FROM     tbl_tipo_pedido
							WHERE    fabrica = $login_fabrica
							AND garantia_antecipada is true
							ORDER BY tipo_pedido ";
					$res = pg_exec ($con,$sql);

					for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
						if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
							echo " selected";
						}
						echo ">" . pg_result($res,$i,descricao) . "</option>";
					}
				}
				echo "</select>";
			}
		} else {
			echo "<select size='1' name='tipo_pedido' class='frm'>";
			echo "<option value='$tipo_pedido'>$tipo_pedido_descricao</option>";
			echo "</select>";
		}
		?>
		</p>

		<?#-------------------- Transportadora -------------------

		#HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
		if ($permite_alteracao != 't'){
			$sql = "SELECT	tbl_transportadora.transportadora        ,
							tbl_transportadora.cnpj                  ,
							tbl_transportadora.nome                  ,
							tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING(transportadora)
					JOIN	tbl_fabrica USING(fabrica)
					WHERE	tbl_transportadora_fabrica.fabrica        = $login_fabrica
					AND		tbl_transportadora_fabrica.ativo          = 't'
					AND		tbl_fabrica.pedido_escolhe_transportadora = 't'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
			?>
				<p><span class='coluna1'>Transportadora</span>
				<?
					if (pg_numrows ($res) <= 20) {
						echo "<select name='transportadora' class='frm'>";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='".pg_result($res,$i,transportadora)."' ";
							if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
							echo ">";
							echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
					}else{
						echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
						echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

						echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

						echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
					}
					?>
				</p>
		<? }
		}?>

		<?#-------------------- Linha do pedido -------------------

		#HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
		if ($permite_alteracao == 't' and strlen($linha)>0){
			?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
		}else{
			$sql = "SELECT	tbl_linha.linha            ,
							tbl_linha.nome
					FROM	tbl_linha
					JOIN	tbl_fabrica USING(fabrica)
					JOIN	tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
											AND tbl_posto_linha.linha = tbl_linha.linha
					WHERE	tbl_fabrica.linha_pedido is true
					AND     tbl_linha.fabrica = $login_fabrica ";

			#permite_alteracao - HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
				$sql .= " AND tbl_linha.linha = $linha ";
			}
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
			?>
				<p><span class='coluna1'>Linha</span>
						<?
						echo "<select name='linha' class='frm' ";
						echo ">";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='".pg_result($res,$i,linha)."' ";
							if ($linha == pg_result($res,$i,linha) ) echo " selected";
							echo ">";
							echo pg_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
						?>
				</p>
			<?
			}
		}
		?>
		
		<table class='formulario' width='700px' align='center'>
			<tr class='subtitulo'>
				<td align="center"> Peças </td>
			</tr>
		</table>
		<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">

		<!-- Peças -->
		<p class='formulario'>
		<table border="0" width='650' cellspacing="0" cellpadding="2" align="center" class='formulario'  name="tabela_itens" id="tabela_itens">
			<tr height="20" class='titulo_coluna' nowrap>
				<td align='left'>Ref. Componente/Descricao Componente</td>
				<td align='center'>Qtde</td>
				<td align='center'>Preço Unit.</td>
				<td align='center'>Total</td>
			</tr>

			<?
			$total_geral = 0;

			echo "<input type='hidden' name='qtde_item' value='$qtde_item' id='qtde_item'>";
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				
					/*
						Esse script inserido trabalhalha com os campos das peças, ele apaga todos os campos 
						quando a descrição não está inserida, não deixa multiplicar a quantidade por preço caso a quantidade não seja
						digitada e limpa todos os campos caso seja apagada a descrição da peça.
					*/			
					echo "<script>
								$( document ).ready( function(){
									
									$( '#total_pecas' ).each( function(){
										var total_pecas = $( '#total_pecas' ).val( total_pecas );
													total_pecas = total_pecas.replace( '.' , ',' ); 
													$( '#total_pecas' ).val( total_pecas );
									
									} )

									$('#qtde_$i').numeric();
										$( '#qtde_$i' ).blur( function(e){
											if( $( '#qtde_$i' ).val() == '' || $( '#qtde_$i' ).val() == null || $( '#qtde_$i' ).val() == 0 )
											{
												if( $( '#peca_ref_descricao_$i' ).val() != '' && $( '#peca_ref_descricao_$i' ).val() != null  && e.which  != 8 && e.which != 46 )
												{	
													$( '#qtde_$i' ).val( 1 );
													
												}
											}
										
										} );

								$( '#peca_ref_descricao_$i' ).blur( function(){
								
									if( $( '#peca_ref_descricao_$i' ).val() == '' )	{
										
										$( '#qtde_$i' ).val( '' );
										$( '#preco_$i' ).val( '' ) ;
										fnc_calcula_total($i);
										$( '#sub_total_$i' ).val( '' );
										$( '#produto_referencia_$i' ).val( '' );
										$( '#peca_referencia_$i' ).val( '' );
									
									}
									
																	
								} )

							} );
					 </script>";
				
				if (strlen($pedido) > 0){	// AND strlen ($msg_erro) == 0
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";

					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						$pedido_item     = trim(@pg_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_result($res,$i,descricao));
						$qtde            = trim(@pg_result($res,$i,qtde));
						$preco           = trim(@pg_result($res,$i,preco));
						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');

						$produto_referencia = '';
						$produto_descricao  = '';
					}else{
						$produto_referencia= $_POST["produto_referencia_"     . $i];
						$produto_descricao = $_POST["produto_descricao_" . $i];
						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}else{
					$produto_referencia= $_POST["produto_referencia_"     . $i];
					$produto_descricao = $_POST["produto_descricao_" . $i];
					$pedido_item     = $_POST["pedido_item_"     . $i];
					$peca_referencia = $_POST["peca_referencia_" . $i];
					$peca_descricao  = $_POST["peca_descricao_"  . $i];
					$qtde            = $_POST["qtde_"            . $i];
					$preco           = $_POST["preco_"           . $i];
				}

				$peca_referencia = trim ($peca_referencia);

				#--------------- Valida Peças em DE-PARA -----------------#
				$tem_obs = false;
				$linha_obs = "";

				$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
				$resX = pg_exec ($con,$sql);

				if (pg_numrows ($resX) > 0) {
					$linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
					$peca_referencia = pg_result ($resX,0,0);
					$tem_obs = true;
				}

				#--------------- Valida Peças Fora de Linha -----------------#
				$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";

				$resX = pg_exec ($con,$sql);
				if (pg_numrows ($resX) > 0) {
					$libera_garantia = pg_result ($resX,0,libera_garantia);
					$linha_obs .= "Peça acima está fora de linha <br>&nbsp;";
					$tem_obs = true;
				}

				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_exec ($con,$sql);
					if (pg_numrows ($resX) > 0) {
						$peca_descricao = pg_result ($resX,0,0);
					}
				}

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($tem_obs) $cor='#FFCC33';
			?>
				<tr bgcolor="<? echo $cor ?>" nowrap>

					<td align='left'>
						<input type="hidden" name="pedido_item_<? echo $i ?>" size="15" value="<? echo $pedido_item; ?>">
						
						<?php echo "<input class='frm' type='text' id='peca_ref_descricao_$i' name='peca_ref_descricao_$i' size='60' onfocus='autocompletar_item(\"peca_ref_descricao_$i\",\"peca_referencia_$i\",this.value,$i)' value='$peca_descricao'>";
						?>

						<input class="frm" type="hidden" name="peca_referencia_<?=$i?>" id="peca_referencia_<?=$i?>" size="15" value="<? echo $peca_referencia; ?>">
						<input type="hidden" name="posicao">
						<input class="frm" type="hidden" id="peca_descricao_<? echo $i ?>" name="peca_descricao_<? echo $i ?>" size="20" value="<? echo $peca_descricao ?>">
					</td>
					<td align='center'>
						<?if ($i> 38) {
							$comando = "adicionarLinha($i)";
						}?>
						<input class="frm" type="text" name="qtde_<? echo $i ?>" id="qtde_<? echo $i ?>" size="5" maxlength='5' value="<? echo $qtde ?>"
						<? echo "onblur='javascript: fnc_makita_preco ($i);$comando'"; ?> >
					</td>

					<td align='center'>
						<input class="frm" id="preco_<? echo $i ?>" type="text" name="preco_<? echo $i ?>" size="10"  value="<? echo $preco ?>" readonly  style='text-align:right; color:#000;' >
					</td>
					
					<td align='center'>
					
						<input class="frm" name="sub_total_<? echo $i ?>" id="sub_total_<? echo $i ?>" type="text" size="10" rel='total_pecas' readonly  style='text-align:right; color:#000;' value='<?
								
								if ($qtde &&  $preco) { 
									if( $preco == '' || $preco == 0 || $preco == null )
									{
										$preco = 1;
									}
									$preco = str_replace(',','.',$preco);
									$total_geral += $preco * $qtde; 
									
									$preco = $preco * $qtde;
									$preco = number_format($preco,2,',','.');
									echo $preco;
								}
							?>'>
							<?php ?>
					</td>

				</tr>

				<?
				if ($tem_obs) {
					echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
					echo "<td colspan='4'>$linha_obs</td>";
					echo "</tr>";
				}
				echo "<tr>";
				echo "<td  colspan='7'><div id='mudou_$i' style='display: none; width=700'></div></td>";
				echo "</tr>";
				?>

			<?
			}
			?>
			</table>
			<?
				echo "<table border='0' cellspacing='0' cellpadding='2' align='center' class='xTabela' width='640px'>";
				echo "<tr style='font-size:12px' align='right'>";
				echo "<td colspan='7' allign='right'><b>Total</b>: <INPUT TYPE='text' size='10' style='text-align:right' id='total_pecas'";
					if(strlen($total_geral) > 0)
					{
						$total_geral = number_format($total_geral,2,',','.');
					    echo " value='$total_geral'";
					} 
				echo "></td>";
				echo "</tr>";
				echo "</table>";
			?>
		</p>
		<p><center>
		<table>
			<tr>
				<td>
					<input type='button' value='Importa do Excel' onclick="javascript: div_importa_excel.style.display='block' ; frm_pedido.lote_pecas.value='' ; frm_pedido.lote_pecas.focus()">
				</td>
			</tr>
		</table>
<br>


		<!-- ------------ DIV Importa Peças do EXCEL ---------------------- -->
		<div id='div_importa_excel' style='display: none ; position: absolute ; top: 300px ; left: 10px ; background-color:#D9E2EF ; width: 600px ; border:solid 1px #330099 ' onkeypress="if(event.keyCode==27){div_importa_excel.style.display='none' ; frm_pedido.item_descricao0.focus()}">
			<div id="div_lanca_peca_fecha" style="float:right ; align:center ; width:20px ; background-color:#FFFFFF " onclick="div_importa_excel.style.display='none' ; frm_pedido.item_descricao0.focus()" onmouseover="this.style.cursor='pointer'">
				<center><b>X</b></center>
			</div>
			<br>
			<b>Importa Peças do Excel</b>
			<br>
			<font size='-1'>
			Para importar peças do Excel, formate uma planilha apenas com 2 colunas (código da peça e quantidade). Copíe e cole estas colunas no campo abaixo (não copie a linha de cabeçalho).
			</font>
			<br>
			<textarea name='lote_pecas' id='lote_pecas' cols='25' rows='10'></textarea>
			<br>
			<script language='javascript'>
			function ebano(){
				
				
				return true;
			}
			</script>
			<input type='button' value='Importar' onclick="javascript: document.getElementById('divAguarde').style.display='block'; setTimeout('importarPecas(frm_pedido.lote_pecas);', 2000); div_importa_excel.style.display='none';">
		</div>
		<input type="hidden" name="btn_acao" value="">
		<input type='button' value='Gravar' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; alert('Após inserir todos os Ítens desejados clique em Finalizar'); document.frm_pedido.submit() } else { alert ('Aguarde submissão') } " border='0' style='cursor: pointer'>
		</center>
		</p>
		</div>
		</li>
		</ul>
<!-- Fecha Divisão-->
</form>
<div id='divAguarde' style='position:absolute; display:none; top:500px; left:350px; background-color: #99CCFF; width: 300px; height:100px;'>
<center>
Aguarde Carregando...<br>
<img src='imagens/ajax-azul.gif'>
</center>
</div>
<br clear='both'>
<p>

<?
	$pedido = $cook_pedido;
	if($login_fabrica == 3) $pedido = $_GET["pedido"];
	$sql = "SELECT	a.oid    ,
					a.*      ,
					referencia,
					descricao
			FROM	tbl_peca
			JOIN	(
						SELECT	tbl_pedido_item.oid,tbl_pedido_item.*
						FROM	tbl_pedido_item
						JOIN    tbl_pedido USING(pedido)
						WHERE	pedido = $pedido
						AND     fabrica = $login_fabrica
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";

	$res = @pg_query ($con,$sql);
	$total = 0;
	if( @pg_num_rows( $res ) > 0 )
	{


if (strlen ($cook_pedido) > 0 /*OR strlen($pedido)>0 */ ) {
?>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center" class='texto_avulso'>
<tr>
	<td align="center">
		<p>Ao terminar de inserir itens no pedido clique em finalizar, após finalizar será necessário abri um novo Pedido para os novos itens</p>
		<p>Para inserir novos itens neste pedido, basta gravar e em seguida inserir os demais itens desejados</p>
	</td>
</tr>

</form>
</table>



<br>
<table width="700" border="0" cellpadding="3" class='tabela' cellspacing="1" align="center">
<tr>
	<td colspan="5" align="center" class='titulo_tabela'>
		Resumo do Pedido
	</td>
</tr>

<tr class='titulo_coluna'>
	
	<td width="25%" align='center'>
		Referência
	</td>
	<td width="40%" align='center'>
		Descrição
	</td>
	<td width="15%" align='center'>
		Quantidade
	</td>
	<td width="10%" align='center'>
		Preço
	</td>
	<td width="10%" align='center'>
		Ação
	</td>
</tr>

<?php
//var_dump($sql);
	for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";
		
		echo "<td width='25%'>";
		echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left'>";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' align='center'>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right'>";
		$preco = number_format (pg_fetch_result ($res,$i,preco),2,",",".");
		//$preco = str_replace('.',',',$preco);
		echo $preco;
		echo "</td>";
		
		echo "<td width='10%' align='center' nowrap>";
		echo "<input type='button' value='Excluir' onclick=\"javascript:window.location='$PHP_SELF?delete=". pg_fetch_result ($res,$i,pedido_item)."&pedido=$pedido'\"/>";
		echo "</td>";

		echo "</tr>";

		$total = $total + (pg_fetch_result ($res,$i,preco) * pg_fetch_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="4">
		T O T A L
	</td>
	<td align='right' style='text-align:right'>
		<b>
		<?php 
			$total = number_format ($total,2,".",",");
			$total = str_replace('.',',',$total);
			echo $total;
		?>
		</b>
	</td>
</tr>
</table>
<?php
}
?>


<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
<?
	$link = "$PHP_SELF?btn_acao=Finalizar";

?>
		<br><input type="button" value="Finalizar" onclick="javascript: window.location='<?php echo $link; ?>'"><br><br>
		
	</td>
</tr>

</table>

<?
} //var_dump($cook_pedido);

?>


<? include "rodape.php"; ?>
