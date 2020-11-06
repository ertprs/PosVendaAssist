<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';		
//include ("erro.php");
$botao		=$_POST["botao"];
$data_atual	=date('Y-m-d');

/*
// PARA EXECUTAR UPDATE DOS PEDIDOS SEM TOTAL
$sql= "	select pedido
			from tbl_pedido 
			where fabrica = 27 limit 100;";
	$res= pg_exec($con, $sql);

for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
	$pedido=trim(pg_result($res,$i,pedido));
	$sql= "	SELECT sum(qtde * preco) as tot
			FROM tbl_pedido_item where pedido= $pedido";
	echo "sql1: $sql <br>";
	$res_x= pg_exec($con, $sql);

	$tot =trim(pg_result($res_x,0,tot));
if(strlen($tot)==0) $tot=0;

	$sql= "	UPDATE tbl_pedido
			SET total = $tot
			WHERE fabrica = 27 and pedido = $pedido";
	echo "<br>sql:". $sql;
	$res_t= pg_exec($con, $sql);
}
*/


function atualiza_qtde_entregar($con){
	//ATUALIZAR ESTOQUE - QTDE_ENTREGAR

	$sql= "	UPDATE tbl_estoque_extra
			SET quantidade_entregar =0;";
	$res_upd= pg_exec($con, $sql);		

	//ESTA ROTINA DEVERÁ SE REALIZADA A CADA CADASTRO DE PEDIDO DE COMPRA
	$sql= "	SELECT tbl_pedido_item.peca,
				sum(tbl_pedido_item.qtde) AS qtde_entregar 
			FROM tbl_pedido 
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido 
			WHERE tbl_pedido.fabrica = 27 
				AND tbl_pedido.status_pedido = 16
			GROUP BY tbl_pedido_item.peca";
	$res= pg_exec($con, $sql);
	//echo "sql: $sql";
	if(pg_numrows($res) > 0){
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$peca	=trim(pg_result($res,$i,peca));
			$qtde	=trim(pg_result($res,$i,qtde_entregar));
			$sql= "	UPDATE tbl_estoque_extra
					SET quantidade_entregar =$qtde
					WHERE peca = $peca;";
			$res_upd= pg_exec($con, $sql);		
		}
	}
	return;
}

function calcula_custo_medio($con,$peca,$preco_entrada,$qtde_entrada){
	$sql = "SELECT valor_custo_medio,
						valor_compra
				FROM tbl_peca_item
				where peca = $peca";
	$yres = pg_exec($con,$sql);
	$estoque_custo_medio  = pg_result($yres,0,valor_custo_medio);
	$estoque_valor_compra = pg_result($yres,0,valor_compra);

	if($estoque_custo_medio>0){
			$novo_custo_medio = ($estoque_custo_medio + $preco_entrada) / 2;
	}else{
			$novo_custo_medio = $preco_entrada;
	}

	$sql= "UPDATE tbl_peca_item
			SET 
				valor_compra = $preco_entrada
			WHERE
			peca = $peca";
	$res = pg_exec($con, $sql);	

	$sql= "UPDATE tbl_peca_item
			SET 
				valor_custo_medio = $novo_custo_medio
			WHERE
			peca = $peca";
	$res = pg_exec($con, $sql);	
}


$cotacao	= $_POST["cotacao"];
if (strlen($cotacao)==0) 
	$cotacao= $_GET["cotacao"];

if ($cotacao > 0) {
	$sql= "SELECT status
			FROM tbl_cotacao
			WHERE cotacao = $cotacao";

	$res= pg_exec($con, $sql);

	if(@pg_numrows($res)>0)
		$finalizada = pg_result($res, 0, status);
	
	if($finalizada == "finalizada")
		$finalizada = "true";
	else
		$finalizada = "";
}

$erro="";
if(strlen($_POST["salvar"])>0){
	//$res= pg_exec($con, "begin;");
	//$qtd_forn	= count($_POST["fornecedores"]);
	
	

	for($i=0; $i<count($_POST["peca"]); $i++) {
		$erro="";
		$bloq	= $_POST["bloquear_$i"];
		$peca	= $_POST["peca"][$i];
		$qtd_c	= $_POST["qtd_c"][$i];
		//echo "<br>$i  bloquear: $bloq / peca: $peca / qtd_c: $qtd_c ";

		if (strlen($bloq)>0){

			$sql= "UPDATE tbl_cotacao_item
					SET status = 'inativo'
					WHERE cotacao= $cotacao 
						and peca= $bloq";
			//echo "<br>UPDATE1: ".$sql;
			$res= pg_exec($con, $sql);
			if(pg_result_error($res)){
				$erro .= pg_result_error($res);
			}
			//VERIFICAR SE VAI REALMENTE INATIVAR AS PEÇAS??
			$sql= "	UPDATE tbl_peca_item
					SET status = 'inativo'
					WHERE peca = $bloq";
			$res= pg_exec($con, $sql);
			if(pg_result_error($res)){
				$erro .= pg_result_error($res);
			}
			//echo "<br>UPDATE2: ".$sql;



		}else{
			$tipo		 = "";
			$item		 = "";

			$radio_preco = $_POST["tipo_forn".$i];
			$tipo		 = substr($radio_preco,0,1);
			$pos_item	 = strrpos($radio_preco,"Pr");
			$item		 = substr($radio_preco, 1, $pos_item-1);
			$strpos		 = strrpos($radio_preco, "Pr")+1;
			$preco		 = substr($radio_preco,($strpos+1),strlen($radio_preco));

			if(strlen($qtd_c)==0){
				$erro .= "<br>É necessário informar a quantidade para o produto: $peca";
			}
			if(strlen($item)==0){
				$erro .= "<br>O Item está vazio para o produto: $peca - $radio_preco";
			}
			if(strlen($tipo)==0){
				$erro .= "<br>O tipo está vazio para o produto: $peca - $radio_preco";
			}

			if(strlen($erro)==0) {
				//echo "RADIO PRECO";
				/*echo "<br> cotacao:". $cotacao;
				echo "<br> valor do radio:". $_POST['tipo_forn'.$i];
				echo "<br> Tipo:". $tipo;
				echo "<br> Cod. item_cotac_forn:". $item;
				echo "<br> Cod. Prod:". $peca;
				*/
				$sql= "UPDATE tbl_cotacao_item
						SET item_selecionado	= $item,
						TIPO_ITEM_SELECIONADO	= '$tipo',
						QUANTIDADE_COMPRAR		= $qtd_c,
						STATUS					= 'a comprar'
						WHERE COTACAO= $cotacao AND peca= $peca";
				//ECHO "SQL: $sql";
				$res= pg_exec($con, $sql);
			}
		}
	}
/*	if(strlen($erro)==0){
		$res= pg_exec($con, "rollback;");
	}else{
		$res= pg_exec($con, "commit;");
	}*/
}else{

	if(strlen($_POST["gerar_pedido"]) >0){
		//$qtd_forn	= count($_POST["fornecedores"]);
		$res= pg_exec($con, "begin;");

		for($i=0; $i<count($_POST["peca"]); $i++) {
			$bloq	= $_POST["bloquear_$i"];
			$peca	= $_POST["peca"][$i];
			$qtd_c	= $_POST["qtd_c"][$i];

			if (strlen($bloq)>0){

				$sql= "	UPDATE TBL_cotacao_item
						SET 
							status	= 'inativo'
						WHERE	
							cotacao = $cotacao AND 
							peca	= $bloq";

				//echo "<br>UPDATE1: ".$sql;
				$res= pg_exec($con, $sql);

				$sql= "	UPDATE TBL_peca_item
						SET 
							status		= 'inativo'
						WHERE 
							peca		= $bloq	AND 
							empresa	= $login_empresa";
				$res= pg_exec($con, $sql);

				//echo " sql: $sql";
			}else{

				$radio_preco			 = $_POST["tipo_forn".$i];
				$tipo					 = substr($radio_preco,0,1);
				$pos_item				 = strrpos($radio_preco,"Pr");
				$cotacao_fornecedor_item = substr($radio_preco, 1, $pos_item-1);
				$strpos					 = strrpos($radio_preco, "Pr")+1;
				$preco					 = substr($radio_preco,($strpos+1),strlen($radio_preco));

				$res="";
				if(strlen($radio_preco)>0){
					$sql= " SELECT * 
							FROM tbl_cotacao_fornecedor
							JOIN tbl_cotacao_fornecedor_item USING(cotacao_fornecedor)
							WHERE cotacao_fornecedor_item=$cotacao_fornecedor_item";

					//echo "<br>sql:".$sql;
					$res= pg_exec($con, $sql);
				}

				if(@pg_numrows($res)>0){
					$cotacao_fornecedor =trim(pg_result($res, 0, cotacao_fornecedor));

					
					if(strlen($cotacao_fornecedor) > 0 and strlen($peca) > 0) {

						$sql = "SELECT pedido
								FROM tbl_pedido
								JOIN tbl_pedido_item using(pedido)
								WHERE fabrica				= $login_empresa
									AND cotacao_fornecedor	= $cotacao_fornecedor 
									AND peca				= $peca";
						//echo "sql: $sql";
						$res_ped= pg_exec($con, $sql);
					}
					//ATENÇÃO: o $res_ped só serve para o if!
					if(@pg_numrows($res_ped)>0){
						$erro = "Pedido já Cadastrado!";
					}else{
						if(($preco > 0) and ($preco <> "-")){
							$cotacao_fornecedor	= trim(pg_result($res, 0, cotacao_fornecedor));
							$prazo_entrega		= trim(pg_result($res, 0, prazo_entrega));
							$condicao			= trim(pg_result($res, 0, condicao_pagamento));
							$forma_pagamento	= trim(pg_result($res, 0, forma_pagamento));
							$fornecedor			= trim(pg_result($res, 0, pessoa_fornecedor));
							//$natureza			= trim(pg_result($res, 0, natureza));
							$status				= trim(pg_result($res, 0, status));
							$tipo_frete			= trim(pg_result($res, 0, tipo_frete));
							$valor_frete		= trim(pg_result($res, 0, valor_frete));

							if(strlen($prazo_entrega)==0) {
								$erro .= "É necessário inserir prazo de entrega na cotação do fornecedor.";
							}
							if(strlen($condicao)==0) 
								$erro .= "É necessário inserir condição de pagamento na cotação da cotacao_fornecedor: $cotacao_fornecedor";
							if(strlen($forma_pagamento)==0) 
								$erro .= "É necessário inserir a forma de pagamento na cotação da cotacao_fornecedor: $cotacao_fornecedor";
/*							if(strlen($natureza)==0) 
								$erro .= "É necessário inserir a Natureza de Operação na cotação da cotacao_fornecedor: $cotacao_fornecedor";*/
							if($status<>"cotada") 
								$erro .= "Está faltando Dados na Cotação do Fornecedor!";

							if($tipo == "v")
								$preco		= trim(pg_result($res, 0, preco_avista));
							else
								$preco		= trim(pg_result($res, 0, preco_aprazo));

							//TESTA SE JA EXISTE O PEDIDO PARA ESTA COTACAO_FORNECEDOR
							if(strlen($pedido[$cotacao_fornecedor])==0 and strlen($erro)==0){
								// SE FOR O PRIMEIRO ITEM, ENTAO DEVE INSERIR O PEDIDO

								//ESSE SQL É USADO PARA GERAR O CODIGO DO PEDIDO DE ACORDO COM OS PEDIDO GERADOS EX: 17-1, 17-2, 17-3
								$sql = " SELECT count(pedido ) as cont_ped
										 FROM tbl_pedido 
										 WHERE cotacao_fornecedor in 
											(
											SELECT cotacao_fornecedor 
											FROM tbl_cotacao_fornecedor 
											WHERE cotacao = $cotacao and pessoa_fornecedor = $fornecedor
											);";
								
								$res_cont= pg_exec($con, $sql);

								if(@pg_numrows($res_cont)>0){
									$cont_ped	=trim(pg_result($res_cont, 0, cont_ped));
								}

								if($cont_ped > 0){
									$cont_ped++;
								}else{
									$cont_ped= 1;
								}
								$pedido_cliente = "$requisicao_lista - $cont_ped";

								// EXCLUIDOS E REVER DEPOIS:  forma_pagamento, prazo_entrega

								//status_pedido | 9
								//descricao     | Aguardando Confirmacao

								//VERIFICAR COMO QUE VAI SER O TIPO DE PEDIDO
								$sql= "
								  INSERT INTO 
									  tbl_pedido (
										cotacao_fornecedor, 
										data, 
										entrega, 
										condicao,
										posto,
									    tipo_pedido,
									    status_pedido,
										pedido_cliente,
									    fabrica,
										tipo_frete,
										valor_frete
									  )
								  VALUES (
										$cotacao_fornecedor, 
										CURRENT_DATE, 
										(interval'$prazo_entrega day'+CURRENT_DATE)::date , 
										$condicao, 
									    $login_loja,
									    2,
									    16,
										(select requisicao_lista ||' - $cont_ped' from tbl_cotacao where cotacao= $cotacao),
									    $login_empresa,
										'$tipo_frete',
										$valor_frete
									  );";
								  //echo "sql: $sql";

								$sql.= " SELECT CURRVAL ('seq_pedido') as pedido;";

								//echo "<br>iseriuuuuuuuuuuuuuu sql:". $sql;

								$res	= pg_exec($con, $sql);

								if(pg_numrows($res)>0){
									$pedido[$cotacao_fornecedor]= trim(pg_result($res, 0, pedido));
								}else{
									$erro .= "ERRO AO INSERIR UM PEDIDO";
								}
							}

							if((strlen($erro) == 0) AND (strlen($peca) > 0) AND (strlen($qtd_c) > 0)) {

							
								$sql = "SELECT ipi, icms
										FROM tbl_cotacao_fornecedor_item
										WHERE cotacao_fornecedor = $cotacao_fornecedor 	AND		
											  peca				 = $peca";
								//echo "sql: $sql";
								$res_ped_item= pg_exec($con, $sql);

								if(@pg_numrows($res_ped_item)>0){
									$ipi	= trim(pg_result($res_ped_item, 0, ipi));
									$icms	= trim(pg_result($res_ped_item, 0, icms));	
								}
								if(strlen($ipi)==0)		$ipi=0;
								if(strlen($icms)==0)	$icms=0;
			
								//INSERE ITEM DO PEDIDO
								$sql= "INSERT INTO
											tbl_pedido_item
												(
												pedido, 
												qtde, 
												preco, 
												peca,
												ipi,
												icms	
												)
									   VALUES
												(
												$pedido[$cotacao_fornecedor], 
												$qtd_c, 
												$preco, 
												$peca,
												$ipi,
												$icms
												);";
								//echo "<br>sql:". $sql;
								$res	= pg_exec($con, $sql);
								
								//ATUALIZA NO ITEM DA COTACAO A PECA COMPRADA
								$sql= "	UPDATE tbl_cotacao_item
										SET 
											item_selecionado		= $cotacao_fornecedor_item,
											tipo_item_selecionado	= '$tipo',
											quantidade_comprar		= $qtd_c,
											status					= 'comprado'
										WHERE cotacao = $cotacao 
											  AND peca= $peca";
								//echo "<br>sql:". $sql;
								$res= pg_exec($con, $sql);

								$sql = "SELECT momento_custo_medio 
										FROM tbl_loja_dados 
										WHERE empresa=$login_empresa;";
								$res = pg_exec($con,$sql);
								if(pg_numrows($res)>0){
									if(pg_result($res,0,0)=="pedido"){
										calcula_custo_medio($con,$peca,$preco,$qtd_c);
									}
								}
								//ATUALIZA NO ITEM DA COTACAO A PECA COMPRADA
								$sql= "	UPDATE tbl_pedido
										SET 
											total = (select sum(qtde * preco) as tot
													from tbl_pedido_item where pedido= $pedido[$cotacao_fornecedor])
										WHERE fabrica = $login_empresa and pedido = $pedido[$cotacao_fornecedor]";
								//echo "<br>sql:". $sql;
								$res= pg_exec($con, $sql);


							}else{
								echo "<font color='red'> Erro: $erro</font>";
							}
						}else{
							$erro ="ERRO: O PREÇO ESTÁ VAZIO!";
						}
					}
				}
			}
		}
		
		if((strlen(pg_errormessage($con)) == 0) and (strlen($erro)==0)) {

			$sql= "	
				SELECT cotacao_item
				FROM tbl_cotacao_item
				WHERE cotacao= $cotacao 
					AND status='a comprar';";
			$res= pg_exec($con, $sql);

			//NAO UTILIZA MAIS ESTE TRECHO POR NAO TER O BOTAO FINALIZAR
/*			$sql= "	
				UPDATE tbl_cotacao_item
				SET status = 'não comprado'
				WHERE cotacao= $cotacao AND status='a comprar';";
			$res= pg_exec($con, $sql);
*/
			if(pg_numrows($res) == 0) {
				$sql= "	
					UPDATE tbl_cotacao
					SET status			= 'finalizada',
						data_fechamento = current_date
					WHERE cotacao	= $cotacao;";

				//echo "<br>sql:". $sql;
				$res= pg_exec($con, $sql);

				if(strlen(pg_errormessage($con))>0 or strlen($erro)>0) {
					$res= pg_exec($con, "rollback;");
					echo "<font color='red'> Erro: $erro".pg_errormessage($con)."</font>";
				}else{
					$res= pg_exec($con, "commit;");
					//redirecionar com javascript
					echo "<script language='JavaScript'>
						window.location= 'cotacao_mapa.php?cotacao=$cotacao&msg=Fechada com sucesso!';
					</script>";
				}
			}else{
				if(strlen(pg_errormessage($con))>0 or strlen($erro)>0) {
					$res= pg_exec($con, "rollback;");
					echo "<font color='red'> Erro: $erro".pg_errormessage($con)."</font>";
				}else{
					$res= pg_exec($con, "commit;");
					atualiza_qtde_entregar($con);
					echo "<font color='blue'> Ok, Cadastrado com Sucesso!</font>";
				}
			}
		}else{
			$res= pg_exec($con, "rollback;");
			echo "<font color='red'> Erro: $erro".pg_errormessage($con)." - </font>";
		}
		
		//print_r ($pedido);
		if(strlen($pedido[$cotacao_fornecedor])>0){
			//echo "print aqui1111::".$pedido[$cotacao_fornecedor];
			echo "<script language='JavaScript'>
				function redirecionar() {
					  window.location='pedido.php?pedido=$pedido[$cotacao_fornecedor]';
				}
				//redirecionar();
				</script>";
		}else{
			//echo "print aqui::22222";
			print_r ($pedido);
		}
		//echo "print aqui::33333333";
	}else{
		if(strlen($_POST["filtrar"])>0){

			if(count($_POST['familia'])>0){
				$array_familia  ="";
				$sql_familia	="and(";
				for($i=0; $i<count($_POST['familia']); $i++){
					if($i>0) $sql_familia.=" or ";

					if(strlen($_POST['familia'][$i])>0){
						$fam				= $_POST['familia'][$i];
						$sql_familia	   .= "tbl_peca_item.linha=". $fam;
						$array_familia[$fam]="checked";

					}else{
						$sql_familia.= "tbl_peca_item.linha= null";
					}
					

				}
				$sql_familia.=")";
			}


			if(count($_POST['tipo'])>0){
				$array_tipo="";
				$sql_tipo="and(";
				for($i=0; $i<count($_POST['tipo']); $i++){
					if($i>0) $sql_tipo.=" or ";

					if(strlen($_POST['tipo'][$i])>0){
						$tip			 = $_POST['tipo'][$i];					
						$sql_tipo		.= "tbl_peca_item.familia=". $tip;
						$array_tipo[$tip]="checked";
					}else{
						$sql_tipo.= "tbl_peca_item.familia= null";
					}
				}
				$sql_tipo.=")";
			}


			if(count($_POST['especie'])>0){
				$array_especie="";
				$sql_especie="and(";
				for($i=0; $i<count($_POST['especie']); $i++){
					if($i>0) $sql_especie.=" or ";

					if(strlen($_POST['especie'][$i])>0){
						$esp				= $_POST['especie'][$i];
						$sql_especie       .= "tbl_peca_item.modelo=". $esp;
						$array_especie[$esp]="checked";
					}else{
						$sql_especie.= "tbl_peca_item.modelo= null";
					}
				}
				$sql_especie.=")";
			}


			if(count($_POST['marca'])>0){
				$array_marca="checked";
				$sql_marca="and(";
				for($i=0; $i<count($_POST['marca']); $i++){
					if($i>0) $sql_marca.=" or ";

					if(strlen($_POST['marca'][$i])>0){
						$marc				=$_POST['marca'][$i];
						$sql_marca		   .= "tbl_peca_item.marca=". $_POST['marca'][$i];
						$array_marca[$marc]	="checked";
					}else{
						$sql_marca.= "tbl_peca_item.marca= null";
					}
				}
				$sql_marca.=")";
			}

		}

		//echo "PASSOU NO ELSE ---------GERAR PEDIDO";
	}
}
//ECHO "<font color='red'>$erro</font>";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

<head>

	<title>MAPA DE COMPRAS</title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<link type="text/css" rel="stylesheet" href="css/css.css">

	
	<link rel="stylesheet" href="css/autocompletar.css" />
	<script src="js/events.js"></script>
	<script src="js/xmlhttp.js"></script>
	<script src="js/autocompletar.js"></script>
</head>

<body bgcolor='#ffffff' marginwidth='2' marginheight='2' topmargin='2' leftmargin='2' >


<style type="text/css">
.completeaqui,div#completando{
        width:300px;
}

#caixa {
background: url(caixa_top.jpg) no-repeat top;
width: 257px;
}

#caixaMeio {
background: url(caixa_meio.jpg) repeat-y;
top: 25px;
position: relative;
}
#caixaBase {
background: url(caixa_base.jpg) no-repeat bottom;
height:35px;
}
#caixa p {
font: 11px Verdana, Arial, Helvetica, sans-serif;
color: #FFFFFF;
margin: 0px;
padding-right: 20px;
padding-left: 20px;
}

.cssbutton {
	background: #ddddff;
	
	font-size: x-small;
	
	border : 1px solid #aaa;

   border-top-color:#696;
   border-left-color:#696;
   border-right-color:#363;
   border-bottom-color:#363;
}

.cssbutton2 {
	background: #ccccee;
	
	font-size: x-small;
	
	border : 1px solid #aaa;

   border-top-color:#ffffff;
   border-left-color:#ffffff;
   border-right-color:#ffffff;
   border-bottom-color:#ffffff;
}



.menu_top {
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
.normal{
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.sublinhado{
	font-size: 10px;
	background: #fafafa;
}

.imagem{
font-size: 10px;	
background-image: url(imagens/selecao.gif);
background-repeat:repeat-x;
}

.normal2{
	font-size: 10px;
	border:none;
}

</style>
<script language="javascript">

//muda cor de botao
function hov(loc,cls){
   if(loc.className)
      loc.className=cls;
}

function marcar_linha(id){
	var elemento = document.getElementById(id);
	if (elemento.className=='normal'){
		elemento.className='imagem';
	}
	else{
		elemento.className='normal';
	}

}

//FUNÇÃO PARA INATIVAR UM peca DE APARECER NAS PROXIMAS COTACOES
function inativar(objeto, linha){ 
	var nome= objeto.name;
	if(objeto.checked){
		document.getElementById('tipo_forn'+linha).checked='true';
	}else{
		//alert('nao checked:'+objeto.name) 
	}
} 

//FUNÇÃO USADA PARA MOSTRAR OS PREÇOS PAGOS EM COMPRAS ANTERIORES DOS pecaS
function fnc_pesquisa_produto (produto) {
	if (produto != "") {
		var url = "";
		url = "produto_preco.php?produto=" + produto;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
		//janela.retorno = "<? echo $PHP_SELF ?>";
	}
}

// MOSTRA OCULTA UM BLOCO DE HTML
function mostra_oculta(itemID, itemID2){
  // Toggle visibility between none and inline
  if ((document.getElementById(itemID).style.display == 'none')){
	document.getElementById(itemID).style.display = 'block';
	document.getElementById(itemID2).innerHTML= '-&nbsp;';
  }else{
	document.getElementById(itemID).style.display = 'none';
	document.getElementById(itemID2).innerHTML= '+&nbsp;';
  }
}


//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO DE CADA FORNECEDOR
function calcula_total_selecionado(forn, tot){
alert('calcula_total_selecionado');
	var cont_forn= document.getElementById('cont_fornecedor').value;
	var forn=0, lenPr = 0, len=0, somav = 0, somap = 0,testav=0, testap=0, conti=0;

	for (f=0; f<cont_forn;f++) { 
		if(document.getElementById('fornecedor_'+f).value==''){
			alert('PARA TUDOOOOOOOOOOO');
			return false;
		}else{
			somav=0;
			somap=0;
			forn= document.getElementById('fornecedor_'+f).value;
			for (i=0; i<(tot);i++) { 
				testav= document.getElementById('v_'+forn+'_'+i).value;
				testap= document.getElementById('p_'+forn+'_'+i).value;
				testav= parseInt(testav);
				testap= parseInt(testap);

				//SOMA VALOR A VISTA
				if(document.getElementById('radio_v_'+forn+'_'+i).checked == true){
					if(testav>0)
						somav +=parseInt(testav);
					
				}
				//SOMA VALOR A PRAZO
				if(document.getElementById('radio_p_'+forn+'_'+i).checked == true){
					if(testap > 0)	
						somap +=parseInt(testap);
					
				}
			}
			document.getElementById('resultado_v'+f).value= somav;
			document.getElementById('resultado_p'+f).value= somap;
		}
	}
}


//CALCULAR O TEMPO DE DURAÇÃO, A ATUALIZAR TODOS OS PREÇOS DO PRODUTO EM CADA UM DOS FORNECEDORES
function Calcula_TmpDuracao_eValores(qd, qe, media4, i){
//alert('calcula_tmpDuracao_e valores:');
//alert('qd:'+qd+' qe:'+qe+' media4:'+media4+' i:' +i);
	var forn=0, lenPr = 0, len=0, somav = 0, somap = 0,testav=0, testap=0, conti=0, qtd=0, vu=0, pu=0;

	soma = 0;
	var qc=parseFloat(document.getElementById('id_qtd_comp_'+i).value);
	//alert('id_qtd_comp_'+i+' qc:'+qc)
	if(qc== null){
		//alert('qc eh null');
		qc=0;
	}else{
		//alert('nao eh null');
	}
	if(media4==0){
		document.getElementById('duracao_'+i).value= "0";
	}else{
		soma += parseFloat(qd+qe+qc);
		var qtd_dia=parseFloat(media4/30);
		var duracao= parseInt(soma/qtd_dia);

		document.getElementById('duracao_'+i).value= duracao;
	}
	
	var cont_forn= document.getElementById('cont_fornecedor').value;

	//alert('qd:'+qd+' qe:'+qe+' media4:-'+media4+'- i:' +i+' qc:'+qc+' soma:'+soma+' qtd_dia:'+qtd_dia+ ' duracao:'+duracao +' cont forn:'+cont_forn);
	for (f=0; f<cont_forn;f++) { 
		if(document.getElementById('fornecedor_'+f).value==''){
			alert('PARA TUDOOOOOOOOOOO');
			return false;
		}else{
			forn= document.getElementById('fornecedor_'+f).value;
			qtd	= parseInt(document.getElementById('id_qtd_comp_'+i).value);//Qtd comprar
			//alert('VU:vu_'+forn+'_'+i);
			vu	= document.getElementById('vu_'+forn+'_'+i).value;//vlr a vista Unitário
			pu	= document.getElementById('pu_'+forn+'_'+i).value;//vlr a prazo Unitário
				
			if(vu=='-'){
				//alert('vazio VU qtd:'+qtd + ' vu:' + vu + ' pu:'+pu +' I:'+i);
			}else{
				vu	= parseFloat(document.getElementById('vu_'+forn+'_'+i).value);//vlr a vista Unitário
				//alert('valr a vista:'+vu);
				document.getElementById('v_'+forn+'_'+i).value	= format_number((qtd*vu),2);

			}

			if(pu=='-'){
				//alert('vazio PU qtd:'+qtd + ' vu:'+vu + ' pu:'+pu +' I:'+i);
			}else{
				pu	= parseFloat(document.getElementById('pu_'+forn+'_'+i).value);//vlr a vista Unitário
				document.getElementById('p_'+forn+'_'+i).value = format_number((qtd*pu),2);//(qtd*pu);
			}
		}
	}
}

//CALCULA VALOR TOTAL DE CADA FORNECEDOR SE FOSSE COMPRAR APENAS DELE
function Calcula_valor_vlrTotal(forn, tot){
//alert('calcula_vlrTotal');
	var cont_forn= document.getElementById('cont_fornecedor').value;
	var forn=0, lenPr = 0, len=0, somav = 0, somap = 0,testav=0, testap=0, conti=0, qtd=0, vu=0, pu=0, i=0;

	for (f=0; f<cont_forn;f++) { 
		if(document.getElementById('fornecedor_'+f).value==''){
			alert('PARA TUDOOOOOOOOOOO');
			return false;
		}else{
			forn= document.getElementById('fornecedor_'+f).value;
			for (i=0; i<(tot);i++) {
				//alert('forn_elem:fornecedor_'+f+ ' tot:' + tot + ' forn:' + forn);
				qtd	= parseInt(document.getElementById('id_qtd_comp_'+i).value);//Qtd comprar
				//alert('qtd:'+qtd);				
				vu	= document.getElementById('vu_'+forn+'_'+i).value;//vlr a vista Unitário
				pu	= document.getElementById('pu_'+forn+'_'+i).value;//vlr a prazo Unitário
					
				if(vu=='-'){
					//alert('vazio VU qtd:'+qtd + ' vu:' + vu + ' pu:'+pu +' I:'+i);
				}else{
					vu	= parseInt(document.getElementById('vu_'+forn+'_'+i).value);//vlr a vista Unitário
					document.getElementById('v_'+forn+'_'+i).value	= format_number((qtd*vu),2);
				}

				if(pu=='-'){
					//alert('vazio PU qtd:'+qtd + ' vu:'+vu + ' pu:'+pu +' I:'+i);
				}else{
					pu	= parseInt(document.getElementById('pu_'+forn+'_'+i).value);//vlr a vista Unitário
					document.getElementById('p_'+forn+'_'+i).value	= format_number((qtd*pu),2);//(qtd*pu);
				}
					
				//testav	= parseInt(testav);
				//testap	= parseInt(testap);

				//SOMA VALOR A VISTA
				//if(document.getElementById('radio_v_'+forn+'_'+i).checked == true){
/*				if(testav>0)
						somav +=parseInt(testav);
					
				}
				//SOMA VALOR A PRAZO
				if(document.getElementById('radio_p_'+forn+'_'+i).checked == true){
					if(testap > 0)	
						somap +=parseInt(testap);
					
				}*/
			}
			//document.getElementById('resultado_v'+f).value= somav;
			//document.getElementById('resultado_p'+f).value= somap;
		}
	}
}

// FUNÇÃO PARA FORMATAR O NUMERO PARA DECIMAL COM A QTD DE CASAS DESEJADA
function format_number(pnumber,decimals){ 
    if (isNaN(pnumber)) { return 0}; 
    if (pnumber=='') { return 0}; 
     
    var snum = new String(pnumber); 
    var sec = snum.split('.'); 
    var whole = parseFloat(sec[0]); 
    var result = ''; 
     
    if(sec.length > 1){ 
        var dec = new String(sec[1]); 
        dec = String(parseFloat(sec[1])/Math.pow(10,(dec.length - decimals))); 
        dec = String(whole + Math.round(parseFloat(dec))/Math.pow(10,decimals)); 
        var dot = dec.indexOf('.'); 
        if(dot == -1){ 
            dec += '.'; 
            dot = dec.indexOf('.'); 
        } 
        while(dec.length <= dot + decimals) { dec += '0'; } 
        result = dec; 
    } else{ 
        var dot; 
        var dec = new String(whole); 
        dec += '.'; 
        dot = dec.indexOf('.');         
        while(dec.length <= dot + decimals) { dec += '0'; } 
        result = dec.replace(".", ","); 
    }     
    return result; 	
} 


function abreFornecedor(cotacao,incluir){
	janela = window.open("fornecedor_permissao.php?cotacao=" + cotacao + "&incluir=" + incluir,"cotacao",'resizable=1,scrollbars=yes,width=650,height=450,top=0,left=0');
	janela.focus();
}

</script>




<table align='center' border='0'  cellspacing='1' cellpadding='2' width='800'>
 <FORM class='autocompletar'  name="busca" id="busca" ACTION='cotacao_mapa.php' METHOD='POST'> 
  <?  

//ESTE SELECT EH PARA MOSTRAR TODOS OS FORNECEDORES QUE TRABALHAM COM OS PRODUTOS DESSA COTAÇÃO
$sql= "
	SELECT distinct tbl_pessoa_fornecedor.pessoa as fornecedor, 
			case WHEN char_length(tbl_pessoa.nome) > 35
				THEN substring(tbl_pessoa.nome from 0 for 35) || '...'
				ELSE substring(tbl_pessoa.nome from 0 for 35)
			END  as nome
	FROM tbl_cotacao_item 
	JOIN tbl_peca				on (tbl_cotacao_item.peca			= tbl_peca.peca)
	JOIN tbl_peca_item			on (tbl_peca.peca					= tbl_peca_item.peca)
	JOIN tbl_linha				on (tbl_peca_item.linha				= tbl_linha.linha)
	JOIN tbl_familia			on (tbl_peca_item.familia			= tbl_familia.familia)
	JOIN tbl_modelo				on (tbl_peca_item.modelo			= tbl_modelo.modelo)
	JOIN tbl_marca				on (tbl_peca_item.marca				= tbl_marca.marca)
	JOIN tbl_fornecedor_linha	on (tbl_fornecedor_linha.linha		= tbl_linha.linha)
	JOIN tbl_fornecedor_familia on (tbl_fornecedor_familia.familia	= tbl_familia.familia)
	JOIN tbl_fornecedor_modelo	on (tbl_fornecedor_modelo.modelo	= tbl_modelo.modelo)
	JOIN tbl_fornecedor_marca	on (tbl_fornecedor_marca.marca		= tbl_marca.marca)
	JOIN tbl_pessoa_fornecedor on (
		tbl_fornecedor_linha.pessoa_fornecedor	= tbl_pessoa_fornecedor.pessoa and 
		tbl_fornecedor_familia.pessoa_fornecedor= tbl_pessoa_fornecedor.pessoa and 
		tbl_fornecedor_modelo.pessoa_fornecedor	= tbl_pessoa_fornecedor.pessoa and 
		tbl_fornecedor_marca.pessoa_fornecedor	= tbl_pessoa_fornecedor.pessoa)
	JOIN tbl_pessoa				on (tbl_pessoa_fornecedor.pessoa	= tbl_pessoa.pessoa)
	WHERE tbl_cotacao_item.cotacao	= $cotacao
	ORDER BY nome";
//echo "<br>---sql:$sql<br>....................<br>";
$for= pg_exec($con, $sql);


//SELECIONA OS FORNECEDORES QUE JA TEM UMA "LISTA DE COTAÇÃO" CRIADA PARA CONTAR AS COLUNAS!
$sql= "	SELECT pessoa_fornecedor
		FROM tbl_cotacao_fornecedor 
		WHERE status='cotada' AND cotacao= $cotacao";
$forn= pg_exec($con, $sql);
$colspan= ((pg_numrows($forn) *6 ) +15);

//SELECIONA OS FORNECEDORES QUE JA TEM UMA "LISTA DE COTAÇÃO" CRIADA !
$sql= "	SELECT 
			tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor, 
			tbl_cotacao_fornecedor.status                         ,
			tbl_cotacao_fornecedor.cotacao_fornecedor             ,
			case WHEN char_length(tbl_pessoa.nome) > 35
				THEN substring(tbl_pessoa.nome from 0 for 35) || '...'
				ELSE substring(tbl_pessoa.nome from 0 for 35)
			END  as nome                                           ,
			tbl_cotacao.data_fechamento
		FROM tbl_cotacao
		JOIN tbl_cotacao_fornecedor on tbl_cotacao_fornecedor.cotacao = tbl_cotacao.cotacao
		JOIN tbl_pessoa_fornecedor  on tbl_pessoa_fornecedor.pessoa	  = tbl_cotacao_fornecedor.pessoa_fornecedor
		JOIN tbl_pessoa				on tbl_pessoa.pessoa			  = tbl_pessoa_fornecedor.pessoa	  
		WHERE tbl_cotacao.cotacao= $cotacao
		ORDER BY tbl_pessoa_fornecedor.pessoa";
//echo "sql forn: $sql";
$forn= pg_exec($con, $sql);

echo "<tr class='titulo' >";
echo "<td class='menu_top' colspan='$colspan' align='left'>
	  <span class='A1'>";
echo "<a href='#' onClick=\"mostra_oculta('f1','ddd')\">
		<b id='ddd' style='padding-left:12px; font-size=12px;'>+&nbsp;
		</b>
		Ver todos fornecedores
	  </a></span>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='$colspan' align='left'>";
// RETIRADO DO DIV
// background-image: url(imagens/fundo_div.gif); background-repeat:no-repeat; 
echo "<div name='div01' id='f1' style='padding:10px; filter: alpha(opacity=90); opacity: .90; display:none; width:480px; height:270px; position:absolute; left:10px; top:100px;'>";

echo "<div name='div02' style='padding:10px; background-color: #EEEEFF; width:450px; height:260px; overflow:auto;'>";


echo "<table bordercolor='#ccccdd' bgcolor='#ddddee' align='left' size='460' border='1'  cellspacing='0' cellpadding='0' name='tb_forn2' '>";
/*#bonzai {
width: 350px;
background-image: url(bonzai2.gif);
background-repeat: no-repeat;
-moz-opacity: .5;
height: 433px;
position: absolute;
left: 85px;
top: 190px;
}*/

for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
	$cont_fornecedor	=@pg_numrows ($forn);
	$fornecedor			=trim(pg_result($forn, $f, fornecedor));
	$nome_fornecedor	=trim(utf8_decode(pg_result($forn, $f, nome)));
	$cotacao_fornecedor	=trim(pg_result($forn, $f, cotacao_fornecedor));
	$status				=trim(pg_result($forn, $f, status));
	$data_fechamento	=trim(pg_result($forn, $f, data_fechamento));
	$array_cotacao_fornecedor[$fornecedor]= $cotacao_fornecedor;

	if($status=="cotada")
		$array_status[$fornecedor]= $status;


/*		if($status!='cotada') $status= "não cotou";
	if(strlen($nome_fornecedor)>25)
		$nome_fornecedor= substr($nome_fornecedor, 0,20) . "...";
	echo "<tr bgcolor='#fafafa'>";
	echo "<td class='menu_top' colspan='1' width='60' align='center'>";
	echo "<a href='cotacao_fornecedor.php?cotacao_fornecedor=$cotacao_fornecedor'>
		$fornecedor</a>";
	echo "</td>";
	echo "<td class='menu_top' colspan='2' width='300' align='left'>";
	echo "<a href='cotacao_fornecedor.php?cotacao_fornecedor=$cotacao_fornecedor'>
		$nome_fornecedor</a>";
	echo "</td>";
	echo "<td class='menu_top' colspan='$colspan' width='100' align='left'>";
	echo "<a href='cotacao_fornecedor.php?cotacao_fornecedor=$cotacao_fornecedor'>
		$status</a>";
	echo "</td>";
	echo "</tr>";
	*/
}

if(@pg_numrows($for)>0){

	echo "<tr colspan='2' class='menu_top'>";
	echo "<td bgcolor='#ddddff' align='center'>";
	echo "<font color='#666699' ><b>Cód.</b></font>";
	echo "</td>";
	echo "<td bgcolor='#ddddff' align='center'>";
	echo "<font color='#666699' ><b>Fornecedor</b></font>";
	echo "</td>";
	echo "<td bgcolor='#ddddff' align='center'>";
	echo "<font color='#666699' ><b>Status</b></font>";
	echo "</td>";
	echo "<td bgcolor='#ddddff' align='center'>";
	echo "<font color='#666699' ><b>Email</b></font>";
	echo "</td>";
	echo "<td bgcolor='#ddddff' align='center'>";
	echo "<font color='#666699' ><b>Cotação</b></font>";
	echo "</td>";

	echo "</tr>";
	for ( $i = 0 ; $i < @pg_numrows ($for) ; $i++ ) {
		$fornecedor		= trim(pg_result($for,$i, fornecedor));	
		$nome_fornecedor= trim(utf8_decode(pg_result($for,$i,nome)));	
		//$nome_fornecedor= trim(utf8_decode(pg_result($forn, $f, nome)));
		$cot_forn = $array_cotacao_fornecedor[$fornecedor];

		if(strlen($array_status[$fornecedor])>0){
			$status="cotada";
		}else{
			$status="não cotada";
		}
		//$status			= trim(pg_result($for,$i,status));	
		echo "<tr class='table_line' >";
		//alterado HD 3392 - Gustavo
		if(strlen($cot_forn) > 0){
			echo "<td colspan='1' width='60' align='center'>";
			echo "<font color='#000000' >
						$cot_forn - $fornecedor
					</font>";
			echo "</td>";
			echo "<td colspan='1' width='260' align='left'>";
			echo "<font color='#000000' >
						$nome_fornecedor 
					</font>";
			echo "</td>";
			echo "<td colspan='1' width='60' align='center'>";
			echo "<font color='#000000' >
						$status
					</font>";
			echo "</td>";
			echo "<td colspan='1' width='90' align='center'>";
				if(strlen($data_fechamento) == 0){
				echo "<input type='button' value='Enviar Email' onclick=window.open('envia_email_cotacao.php?fornecedor=$fornecedor');>";
				}else{
					echo "<FONT COLOR='#FF0000'>Cotação Finalizada!</FONT>";
				}
			echo "</td>";
			echo "<td colspan='1' width='80' align='center'>";
			echo "<input type='button' value='Abrir cotação' onclick=window.location='cotacao_fornecedor.php?cotacao_fornecedor=$cot_forn';>";
			echo "</td>";

		}else{
			echo "<td colspan='1' width='60' align='center'>";
			echo "<a href='cotacao_fornecedor.php?nova=nova&fornecedor=$fornecedor&cotacao=$cotacao'>
					<font color='#000000' >$fornecedor</font>
				  </a>";
			echo "</td>";
			echo "<td colspan='1' width='260' align='left'>";
			echo "<a href='cotacao_fornecedor.php?nova=nova&fornecedor=$fornecedor&cotacao=$cotacao'>
					<font color='#000000' >
						$nome_fornecedor
					</font>
				  </a>";
			echo "</td>";
			echo "<td colspan='1' width='60' align='center'>";
			echo "<font color='#000000' >$status</font>";
			echo "</td>";
			echo "<td colspan='1' width='90' align='center'>";
				if(strlen($data_fechamento) == 0){
				echo "<input type='button' value='Enviar Email' onclick=window.open('envia_email_cotacao.php?fornecedor=$fornecedor');>";
				}
			echo "</td>";
			echo "<td colspan='1' width='80' align='center'>";
			echo "<input type='button' value='Abrir cotação' onclick=\"window.location='cotacao_fornecedor.php?nova=nova&fornecedor=$fornecedor&cotacao=$cotacao'\";>";
			echo "</td>";
		}

		echo "</tr>";
	}
}//FIM DA SELEÇÃO
echo "</table>";
echo "</div>";
echo "<table align='right'>
		<tr>
			<td><input type='button' class='cssbutton' name='incluir' value='Incluir novo fornecedor' onClick=\"javascript:abreFornecedor('$cotacao','incluir')\">
			</td>
			<td>
				<input type='button' class='cssbutton' onmouseover=\"hov(this,'cssbutton2')\" onmouseout=\"hov(this,'cssbutton')\" name='Fechar' value='Fechar' onClick=\"mostra_oculta('f1','ddd')\"> 
			</td>
		</tr>
	  </table>";
echo "</div>";
echo "</td>";
echo "</tr>";

/*##########################################################################################
ESTE SELECT EH PARA MOSTRAR OS FORNECEDORES QUE TRABALHAM COM OS PRODUTOS DESSA COTAÇÃO
##########################################################################################*/
$sql= "
select 
	tbl_linha.nome as descricao1, 
	tbl_linha.linha as produto1,
	count(peca)
from tbl_cotacao
join tbl_cotacao_item using(cotacao)
join tbl_peca using(peca)
join tbl_peca_item using(peca)
join tbl_linha on(tbl_peca_item.linha = tbl_linha.linha)
where cotacao=$cotacao
group by tbl_linha.linha, tbl_linha.nome
	order by 
		tbl_linha.nome";
//echo "sql:$sql";
$res_produto1= pg_exec($con, $sql);

$sql= "
select 
	tbl_familia.descricao as descricao2, 
	tbl_familia.familia as produto2,
	count(peca)
from tbl_cotacao
join tbl_cotacao_item using(cotacao)
join tbl_peca using(peca)
join tbl_peca_item using(peca)
join tbl_familia on(tbl_peca_item.familia= tbl_familia.familia)
where cotacao=$cotacao
group by tbl_familia.familia, tbl_familia.descricao
	order by 
		tbl_familia.descricao";
//echo "sql: $sql";
$res_produto2= pg_exec($con, $sql);

$sql= "
select 		
	tbl_modelo.nome as descricao3, 
	tbl_modelo.modelo as produto3,
	count(peca)
from tbl_cotacao
join tbl_cotacao_item using(cotacao)
join tbl_peca using(peca)
join tbl_peca_item using(peca)
join tbl_modelo on(tbl_peca_item.modelo = tbl_modelo.modelo)
where cotacao=$cotacao
group by tbl_modelo.modelo, tbl_modelo.nome
	order by 
		tbl_modelo.nome";

$res_produto3= pg_exec($con, $sql);

$sql= "
select 
	tbl_marca.nome as descricao4, 
	tbl_marca.marca as produto4,
	count(peca)
from tbl_cotacao
join tbl_cotacao_item using(cotacao)
join tbl_peca using(peca)
join tbl_peca_item using(peca)
join tbl_marca on(tbl_peca_item.marca = tbl_marca.marca)
where cotacao=$cotacao
group by tbl_marca.marca, tbl_marca.nome
	order by 
		tbl_marca.nome";

$res_produto4= pg_exec($con, $sql);

if(@pg_numrows($res_produto1) >0)
	$c_p1= @pg_numrows($res_produto1) ;
if(@pg_numrows($res_produto2) >0)
	$c_p2= @pg_numrows($res_produto2) ;
if(@pg_numrows($res_produto3) >0)
	$c_p3= @pg_numrows($res_produto3) ;
if(@pg_numrows($res_produto4) >0)
	$c_p4= @pg_numrows($res_produto4) ;

$maior_c_p=0;

if($c_p1 > $maior_c_p)
	$maior_c_p= $c_p1;

if($c_p2 > $maior_c_p)
	$maior_c_p= $c_p2;

if($c_p3 > $maior_c_p)
	$maior_c_p= $c_p3;

if($c_p4 > $maior_c_p)
	$maior_c_p= $c_p4;

echo "<tr class='titulo' >";
echo "<td class='menu_top' colspan='$colspan' align='left'><span class='A1'>\n";
echo "<a href='#' onClick=\"mostra_oculta('filtro', 'link_filtro')\"> <b id='link_filtro' style='padding-left:12px; font-size=12px;'>+&nbsp;</b>Filtrar por: Linha, Família, Marca ou Modelo</a></span>";

echo "<tr>";
echo "<td colspan='$colspan' align='left'>";

echo "<div name='div04' id='filtro' style='padding:10px; filter: alpha(opacity=90); opacity: .90 ; display:none;width:480px; height:270px; position:absolute; left:10px; top:120px;'>";

echo "<div name='div05' style='padding:10px; background-color: #EEEEFF; width:460px;height:258px; overflow:auto '>";

echo "<div name='div06' style='padding:10px; background-color: #EEEEFF; width:430px;height:50px; overflow:auto '>";
echo "<table align='right'>";
echo "<tr>";
echo "<td align='right' colspan='4' >
		<input class='cssbutton' type='submit' name='filtrar'  value='Filtrar'  onmouseover=\"hov(this,'cssbutton2')\" onmouseout=\"hov(this,'cssbutton')\" >
	 </td>";

echo "<td>
		<input class='cssbutton' type='button' name='Fechar' value='Fechar' onmouseover=\"hov(this,'cssbutton2')\" onmouseout=\"hov(this,'cssbutton')\" onClick=\"mostra_oculta('filtro','link_filtro')\"> 
	  </td>";

echo "</tr>";
echo "</table>";
echo "</div>";

echo "<table align='left' size='460' border='1' bordercolor='#ccccdd' bgcolor='#ddddee' cellspacing='0' cellpadding='0' name='tb_filtro' >";		

echo "<tr class='menu_top'>";
echo "<td bgcolor='#ddddff' width='100' align='center'><font color='#666699' ><b>Linha</b></font></td>";
echo "<td bgcolor='#ddddff' width='100' align='center'> <font color='#666699' ><b>Família</b></font></td>";
echo "<td bgcolor='#ddddff' width='100' align='center'><font color='#666699'><b>Marca</b></font></td>";
echo "<td bgcolor='#ddddff' width='100' align='center'><font color='red'><b>Modelo</b></font></td>";
echo "</tr>";

for ( $i = 0 ; $i < $maior_c_p ; $i++ ){
	$produto1 ="";
	$produto2 ="";
	$produto3 ="";
	$produto4 ="";

	$descricao1="";
	$descricao2="";
	$descricao3="";
	$descricao4="";
	
	$familia ="";
	$tipo	="";
	$especie="";
	$marca	="";
	
	if($i<$c_p1){
		$produto1	=trim(pg_result($res_produto1, $i, produto1));
		$descricao1	=trim(utf8_decode(pg_result($res_produto1, $i, descricao1)));
		$familia	= "<input type='checkbox' name='familia[]' value='$produto1' ".$array_familia[$produto1].">$descricao1";
	}else $familia	= "&nbsp;";
	if($i<$c_p2){
		$produto2	=trim(pg_result($res_produto2, $i, produto2));
		$descricao2	=trim(utf8_decode(pg_result($res_produto2, $i, descricao2)));
		$tipo		= "<input type='checkbox' name='tipo[]' value='$produto2' ".$array_tipo[$produto2].">$descricao2";
	}else $tipo		= "&nbsp;";
	if($i<$c_p3){
		$produto3	=trim(pg_result($res_produto3, $i, produto3));
		$descricao3	=trim(utf8_decode(pg_result($res_produto3, $i, descricao3)));
		$especie	= "<input type='checkbox' name='especie[]' value='$produto3' ".$array_especie[$produto3].">$descricao3";
	}else $especie	= "&nbsp;";
	if($i<$c_p4){
		$produto4	=trim(pg_result($res_produto4, $i, produto4));
		$descricao4	=trim(utf8_decode(pg_result($res_produto4, $i, descricao4)));
		$marca		= "<input type='checkbox' name='marca[]' value='$produto4' ".$array_marca[$produto4].">$descricao4";
	}else $marca	= "&nbsp;";

	echo "<tr class='table_line' >";
	echo "<td align='left'>$familia</td>";
	echo "<td align='left'>$tipo</td>";
	echo "<td align='left'>$especie</td>";
	echo "<td align='left'>$marca</td>";
	echo "</tr>";
}


echo "</table>";
echo "</div>";
echo "</div>";
echo "</td>";
echo "</tr>";
?>
    
  <tr >
	<td class='menu_top' colspan='<?echo $colspan;?>' align='left' background='imagens/azul.gif'><font size='3'><?if($finalizada) echo "Produtos não Comprados"; else echo "Produto a Comprar";?></font></td>
  </tr>
  
  <tr bgcolor='#596D9B'>
	<td class='menu_top' colspan='<?echo $colspan?>' width='100%' align='left'>

<?	

$sql= "	SELECT 
			case when char_length(tbl_pessoa.nome) > 35
				then substring(tbl_pessoa.nome from 0 for 35) || '...'
				else substring(tbl_pessoa.nome from 0 for 35)
			end  as nome,
			tbl_condicao.descricao,
			to_char(tbl_cotacao.data_abertura,'dd/mm/yyyy') as data_abertura,
			to_char(tbl_cotacao.data_fechamento,'dd/mm/yyyy') as data_fechamento,	
			tbl_cotacao.status,
			tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor , 
			tbl_cotacao_fornecedor.cotacao_fornecedor,
			tbl_cotacao_fornecedor.prazo_entrega,
			tbl_cotacao_fornecedor.valor_frete,
			tbl_cotacao_fornecedor.observacao,
			tbl_cotacao.requisicao_lista
		FROM tbl_cotacao
		JOIN tbl_cotacao_fornecedor using(cotacao)
		JOIN tbl_condicao			on tbl_cotacao_fornecedor.condicao_pagamento = tbl_condicao.condicao
		JOIN tbl_pessoa_fornecedor  on tbl_pessoa_fornecedor.pessoa				 = tbl_cotacao_fornecedor.pessoa_fornecedor
		JOIN tbl_pessoa				on tbl_pessoa.pessoa						 = tbl_pessoa_fornecedor.pessoa
		WHERE tbl_cotacao_fornecedor.status='cotada' and tbl_cotacao.cotacao= $cotacao
		ORDER BY tbl_pessoa_fornecedor.pessoa";
	
$forn= pg_exec($con, $sql);
if(@pg_numrows($forn)>0){
	$data_abertura	 = trim(pg_result($forn, 0, data_abertura));
	$data_fechamento = trim(pg_result($forn, 0, data_fechamento));
	$status			 = trim(pg_result($forn, 0, status));
	$xrequisicao_lista= trim(pg_result($forn, 0, requisicao_lista));
}

echo "<tr >";
echo "<td class='titulo' colspan='$colspan' align='left' >";
echo "<font color='#6666ff' >
				Cotação nº <b>";
		if(strlen($xrequisicao_lista)>0){ echo "$xrequisicao_lista";
		}else{$cotacao;}
		echo "</b> 
	</font>
	<font color='#000000' >
 | Data de Abertura: $data_abertura | Data de Fechamento: <input type='text' name='data_fechamento' value='$data_fechamento'> | Status: <b>$status</b> </font>";	
echo "</td>";
echo "</tr>";
echo "
  <tr >
	<td background='imagens/azul.gif' class='menu_top' nowrap colspan='3' align='center' >PEÇA</td>
	<td background='imagens/azul.gif' class='menu_top' nowrap colspan='3' align='center' >MÉDIA CONSUMO</td>
	<td background='imagens/azul.gif' class='menu_top' nowrap colspan='5' align='center' >QUANTIDADE</td>
	<td background='imagens/azul.gif' class='menu_top' nowrap colspan='4' align='center' >&nbsp;</td>
";
	$tb2 ="";
//	$tb2 .="<tr bgcolor='#eeeeff' style='font-size: 10px'>";
	$tb2 .="<tr bgcolor='#fafafa' style='font-size: 10px'>";

	$cc=0;

	$cor1=array("#ddddee","#eedede","#ddeedd","#ededee");
	$cor2=array("#EEEEFF","#FFEEEE","#EEFFEE","#FEFEEE");

	for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		$cont_fornecedor	= @pg_numrows ($forn);
		$fornecedor			= trim(pg_result($forn, $f, fornecedor));
		$nome_fornecedor	= trim(utf8_decode(pg_result($forn, $f, nome)));
		$cotacao_fornecedor = trim(pg_result($forn, $f, cotacao_fornecedor));
		$valor_frete		= trim(pg_result($forn, $f, valor_frete));
		$observacao			= trim(pg_result($forn, $f, observacao));
				
		if(strlen($observacao)>0){
			$observacao = "<img src='imagens/info.png' border='0' style='cursor: pointer;' alt='$observacao'>";
		}
		$cor_f[1][$fornecedor]= $cor1[$cc];
		$cor_f[2][$fornecedor]= $cor2[$cc];
		
		if(strlen($nome_fornecedor)>25)
			$nome_fornecedor= substr($nome_fornecedor, 0,20) . "...";
		echo "<td background='imagens/azul.gif' class='menu_top' colspan='6' align='center'>";
		echo "$observacao <a href='cotacao_fornecedor.php?cotacao_fornecedor=$cotacao_fornecedor'>
			<font style='font-family: Arial; font-size: 9px; color:#ffffff;' >$fornecedor-$nome_fornecedor / Val. Fret: R$ $valor_frete</font></a>";
		echo "<input type='hidden' id='fornecedor_$f' name='fornecedor_$f' value='$fornecedor'>";
		echo "<input type='hidden' id='cont_fornecedor' name='cont_f' value='$cont_fornecedor'>";
		echo "</td>";

		$cc++;
		if($cc >3) $cc=0;
	}
	
  echo "</tr>";
  echo "<tr bgcolor='#596D9B'>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Código</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Cód.Fab.</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' width='200' nowrap rowspan='2' align='center'>Nome do peca</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2'	align='center'>Ult. <br>40 d</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Ult. <br>20 d</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Ult. <br>7 d</td>";

  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2'	align='center'>Qtde <br>Disp.</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Qtde <br>Entr.</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Qtde <br>Cotar</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Qtde <br>Comprar</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Qtde <br>Durac</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Bloq<br>Cotaç.</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'> %RMA<br></td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Não<br>Sel.</td>";
  echo "<td class='menu_top' background='imagens/azul.gif' rowspan='2' align='center'>Prazo<br>de Entr.</td>";


  for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		$fornecedor=trim(pg_result($forn, $f, fornecedor));
		$nome_fornecedor=trim(utf8_decode(pg_result($forn, $f, nome)));
		$desc_cond_pagamento= trim(utf8_decode(pg_result($forn, $f, descricao)));
		$prazo_entrega		= trim(pg_result($forn, $f, prazo_entrega));
		echo "<td class='menu_top' background='imagens/azul.gif' colspan='6' nowrap align='center'>Forma Pag: $desc_cond_pagamento| Entrega:$prazo_entrega dias</td>";		
  }
  echo "</tr>";
  echo "<tr bgcolor='#596D9B'>";
  for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		//$fornecedor=trim(pg_result($forn, $f, fornecedor));
		//$nome_fornecedor=trim(pg_result($forn, $f, nome));
	  echo "<td class='menu_top' background='imagens/azul.gif' colspan='3' align='center'>Vlr vista</td>";
	  echo "<td class='menu_top' background='imagens/azul.gif' colspan='3' align='center'>Vlr prazo</td>";
  }

  
  echo "</tr>";

  if($status == "finalizada"){
	$status_cotacao= "finalizada";
	$status_cotacao_item= "não comprado"  ;
  }else {
	$status_cotacao= "aberta";
	$status_cotacao_item= utf8_encode("a comprar")  ;
  }

  $sql= "SELECT COUNT (cotacao_item) as cont_itens
  FROM TBL_COTACAO
  JOIN TBL_cotacao_item USING(COTACAO)
  WHERE COTACAO= $cotacao  and tbl_cotacao_item.status = 'a comprar'";
  
  $res= pg_exec($con, $sql);

  $cont_itens= trim(pg_result($res,0,cont_itens));
  
$sql= "
	SELECT 
		tbl_cotacao.cotacao, 
		tbl_cotacao_fornecedor.cotacao_fornecedor,
		tbl_cotacao_fornecedor.prazo_entrega,
		tbl_cotacao_fornecedor.observacao,
		tbl_pessoa_fornecedor.pessoa as fornecedor,
		case when char_length(tbl_peca.descricao) > 35
			then substring(tbl_peca.descricao from 0 for 35) || '...'
			else substring(tbl_peca.descricao from 0 for 35)
		end  as nome,
		tbl_peca.peca,
		tbl_peca.referencia as codigo_fabrica,
		tbl_cotacao_fornecedor_item.cotacao_fornecedor_item,
		cast(tbl_cotacao_fornecedor_item.preco_avista as numeric(12,2)) as preco_avista,
		cast(tbl_cotacao_fornecedor_item.preco_aprazo as numeric(12,2)) as preco_aprazo,
		tbl_cotacao_fornecedor_item.tipo_preco,
		tbl_cotacao_fornecedor_item.prazo_entrega as item_prazo_entrega,
		tbl_cotacao_item.media_7 as media_7,
		tbl_cotacao_item.media_20 as media_20,
		tbl_cotacao_item.media_40 as media_40,
		tbl_cotacao_item.media4 as media4,
		tbl_cotacao_item.quantidade_disponivel as qd,
		tbl_cotacao_item.quantidade_entregar as qe,
		tbl_cotacao_item.quantidade_acotar as qs,
		tbl_cotacao_item.quantidade_comprar as qc,
		tbl_cotacao_item.cotacao_item,
		tbl_cotacao_item.item_selecionado,
		tbl_cotacao_item.tipo_item_selecionado
	FROM tbl_cotacao_fornecedor_item
	JOIN tbl_cotacao_fornecedor on tbl_cotacao_fornecedor.cotacao_fornecedor = tbl_cotacao_fornecedor_item.cotacao_fornecedor
	JOIN tbl_pessoa_fornecedor	on tbl_pessoa_fornecedor.pessoa				 = tbl_cotacao_fornecedor.pessoa_fornecedor
	JOIN tbl_cotacao			on tbl_cotacao.cotacao						 = tbl_cotacao_fornecedor.cotacao
	JOIN tbl_cotacao_item		on (tbl_cotacao_item.cotacao				 = tbl_cotacao.cotacao 
								AND tbl_cotacao_item.peca					 = tbl_cotacao_fornecedor_item.peca)
	JOIN tbl_peca				on tbl_peca.peca							 = tbl_cotacao_item.peca
	JOIN tbl_peca_item			on tbl_peca.peca							 = tbl_peca_item.peca
	WHERE tbl_cotacao_fornecedor.status = 'cotada'  
		AND tbl_cotacao.cotacao		    = $cotacao 
		AND tbl_cotacao_item.status		= '$status_cotacao_item'
$sql_familia
$sql_tipo
$sql_especie
$sql_marca
ORDER BY tbl_peca.descricao, tbl_pessoa_fornecedor.pessoa";
//echo "SQL: $sql";
$total_preco_v= "";
$total_preco_p= "";
$total_preco_v_item_selecionado= "";
$total_preco_p_item_selecionado= "";

//echo "sql: $sql";
$res= pg_exec($con, $sql);


// - - - - - - - - - - - BUSCAR MENOR PREÇO DA COTAÇAO - - - - - - - -- - - - - -//
$sql= "SELECT 			
		cast(MIN(preco_avista) as numeric(12,2)) as menor,
		peca
	FROM TBL_COTACAO_FORNECEDOR
	JOIN TBL_cotacao_fornecedor_item USING(COTACAO_FORNECEDOR)
	WHERE COTACAO=$cotacao AND (PRECO_AVISTA >0 AND PRECO_AVISTA IS NOT NULL)
	GROUP BY peca
	ORDER BY peca";

$res_preco= pg_exec($con, $sql);

if(@pg_numrows ($res_preco)>0){
	for ( $i = 0 ; $i < @pg_numrows ($res_preco) ; $i++ ) {
		$peca =trim(pg_result($res_preco,$i,peca));
		$menor=trim(pg_result($res_preco,$i,menor));

		$preco_menor[$peca] = $menor;
	}
}else{
		$preco_menor="";
}
// FIM - - - - - - - - - - - BUSCAR MENOR PREÇO DA COTAÇAO - - - - - - - -- - - - - -//
$max_forn=@pg_numrows ($forn);
$cont_forn=1;
//IMPORTANTE: a linha começa com valor negativo pois é incrementada antes da 1º impressao completa
$c=1;
$linha=0;
$cc=0;
$total_avista=0;
for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

	$radio_preco= "preco".$i;
	
	if($cont_forn==1){

		if ($c==2){
			$tb2 .="<tr bgcolor='#ebebeb' class = 'normal' id='tr_$linha' >";
			$c=1;
		}else {
			$tb2 .="<tr bgcolor='#fafafa' class = 'normal' id='tr_$linha' >";
			$c++;
		}

		$peca=trim(pg_result($res,$i,peca));
		$codigo_fabrica=trim(pg_result($res,$i,codigo_fabrica));
		$nome	=trim(utf8_decode(pg_result($res,$i,nome)));
		$media_7	=trim(pg_result($res,$i,media_7));
		$media_20	=trim(pg_result($res,$i,media_20));
		$media_40	=trim(pg_result($res,$i,media_40));
		$media4	=trim(pg_result($res,$i,media4));
		$qd		=trim(pg_result($res,$i,qd));
		$qe		=trim(pg_result($res,$i,qe));
		$qs		=trim(pg_result($res,$i,qs));
		$qc		=trim(pg_result($res,$i,qc));
		$cotacao_item			=trim(pg_result($res,$i,cotacao_item));
		$item_selecionado		=trim(pg_result($res,$i,item_selecionado));
		$tipo_item_selecionado	=trim(pg_result($res,$i,tipo_item_selecionado));
		$item_prazo_entrega	=trim(pg_result($res,$i,item_prazo_entrega));

/*	if(($media_7 +$media_20 + $media_40)==0){
			$medias=1;
		}
		/*
	if(strlen($qd)==0)
		$qd="0.01";
	if(strlen($qe)==0)
		$qe="0.01";
	*/
		//$media_7 = ($media_7/7);
		//$media4= $media_7;
/*		$media4= ($medias/3);
		if($media4==0) $media4 = 0.01;
/*
		$qtdes= ($qd+$qe+$qc);
		if($qtdes==0) $qtdes=1;

		$vl_duracao=(($qtdes)/($media4/7));
		$vl_duracao= sprintf("%01.2f", $vl_duracao);

*/

//echo "<BR>(qd :$qd +qe: $qe + qc: $qc)";
if($media4==0){
	$media4 = $media_7;
}
$qtdes= ($qd+$qe+$qc);
if($qtdes==0) $qtdes=0.11;
//echo "<BR>(qtdes: $qtdes / media-7: - $media_7 - )";
if((strlen($media_7)==0) or $media_7==0){
	$media_7=0;
}else{
	//echo  "qtdes: $qtdes - media_7:$media_7";
	$vl_duracao = ($qtdes /$media_7);

	$vl_duracao= sprintf("%01.2f", $vl_duracao);
}
//$media_7 = ($media_7/7);
//if($media_7==0) $media_7=1;



		$tb2 .="<td nowrap align='center' onclick='javascript:marcar_linha(\"tr_$linha\")' valign='top'>
			$linha - $peca 
				<img src=\"imagens/mais.gif\" border='0' height='18' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_produto($peca)\" align='middle'>\n
			<input type='hidden' name='peca[]' value='$peca'>\n
		</td>\n";
		$tb2 .="<td nowrap align='left' onclick='javascript:marcar_linha(\"tr_$linha\")' >$codigo_fabrica</td>\n";
		$tb2 .="<td nowrap align='left' onclick='javascript:marcar_linha(\"tr_$linha\")' >$nome</td>\n";
		$tb2 .="<td align='center'>$media_40</td>\n";
		$tb2 .="<td align='center'>$media_20</td>\n";
		$tb2 .="<td align='center'>$media_7</td>\n";
		$tb2 .="<td align='center'>$qd</td>\n";
		$tb2 .="<td align='center'>$qe</td>\n";
		$tb2 .="<td align='center'>$qs</td>\n";
		$tb2 .=" <td align='center'>\n";
		if($finalizada){
			$tb2 .="$qc";
		}else{
			$tb2 .="<input type='text' id='id_qtd_comp_$linha' name='qtd_c[]' value='$qc' size='2' maxlength='10' onKeyUp='Calcula_TmpDuracao_eValores($qd, $qe, $media4, $linha);'>";
		
		}
		
		$tb2 .="</td>";
		$tb2 .="<td>\n
			<input type='text' id='duracao_$linha' name='duracao' value='$vl_duracao' size='2' maxlength='10' disabled>\n";
		$tb2 .="</td>\n";

		$tb2 .="<td align='center' >\n
			<input type='checkbox' name='bloquear_$linha' value='$peca' $check>\n
		</td>\n";
		$tb2 .="<td align='center' >\n
		0
		</td>\n";


		$valor	= "v"."0"."Pr0";

		$tb2 .="<td align='center'>\n";
		$tb2 .= "<input type='radio' name='tipo_forn$linha' value='$valor' $vchecked>\n";
		$tb2 .="</td>\n";
		
		//HD 6268 Igor pediu colocar prazo de entrega para todos os itens
		$tb2 .="<td align='center'>\n";
		$tb2 .= "$item_prazo_entrega";
		$tb2 .="</td>\n";

	}
	
	$peca				= trim(pg_result($res,$i, peca));
	$fornecedor			= trim(pg_result($res,$i, fornecedor));
	$cotacao_fornecedor	= trim(pg_result($res,$i, cotacao_fornecedor));
	$item				= trim(pg_result($res,$i, cotacao_fornecedor_item));
	$preco_avista		= trim(pg_result($res,$i, preco_avista));
	$preco_aprazo		= trim(pg_result($res,$i, preco_aprazo));
	$tipo_preco			= trim(pg_result($res,$i, tipo_preco));
	
	if($preco_avista==0) $preco_avista="-";

	if($preco_aprazo==0) $preco_aprazo="-";

	$vchecked="";
	$pchecked="";

	//TESTA QUAL EH O ITEM SELECIONADO (COMPRADO)
	if($item_selecionado==$item){
		if($tipo_item_selecionado=="v"){
			$vchecked="checked";
			$vartxt= "vchecked";
		}else{
			if($tipo_item_selecionado=="p"){
				$pchecked="checked";
				$vartxt= "pchecked";
			}else	
				echo "<font color='#ff0000'>ERRO EM VCHECKED E PCHECKED </font>";
		}
	}else{
		$vartxt= "else";
	}

	$ccc=$cor_f[$c][$fornecedor];
	if($preco_menor[$peca]== $preco_avista){
		//aqui o preco eh menor
//		$ccc="#FF6666";
		$cor_fonte_v="#ff0000";

	}else{
		$cor_fonte_v="#000000";
	}

	if($preco_avista==0){
		$preco_avista="-";
		$soma_preco_v="-";
	}else{
		$soma_preco_v= ($qc* $preco_avista);
		$soma_preco_v= number_format(str_replace( '.', '', $soma_preco_v), 2, ',','');
		$total_preco_v[$fornecedor]= ($total_preco_v[$fornecedor]+$soma_preco_v);
	}

	if($preco_aprazo==0){
		$preco_aprazo="-";
		$soma_preco_p="-";
	}else{
		$soma_preco_p= ($qc* $preco_aprazo);
		$soma_preco_p= number_format(str_replace( '.', '', $soma_preco_p), 2, ',','');
		$total_preco_p[$fornecedor]= ($total_preco_p[$fornecedor]+$soma_preco_p);
	}

	//$preco_avista = str_replace( '.', ',', $preco_avista);
	//$preco_aprazo = str_replace( '.', ',', $preco_aprazo);

// ########################################### TESTAR FUNÇOES  ################################################# //
//	$tb2 .="<td align='right' nowrap > 
//		<input type='radio' id='val_$linha' name='teste_$linha' onClick='calcula_total_selecionado($fornecedor, 50);' value='$preco_avista' $vchecked>";
//	$tb2 .="</td>";
// FIM ########################################### TESTAR FUNÇOES  ################################################# //
	
	
#INI - ###############################################  PREÇO_AVISTA  ############################################# //

	//input valor Unitario "VALOR_AVISTA"
	$id_vu_v= "vu_".$fornecedor."_".$linha;// id do input text>>> valor unitario

	$tb2 .="<td bgcolor='$ccc' align='right' width='35' valign='top'>\n
		<input type='text' id='$id_vu_v' size='4' value='$preco_avista' class='input_cotacao' style='background-color: $ccc; color:$cor_fonte_v;' readonly>\n
	</td>\n";

	//input subtotal de "valor_avista"(quantidade_comprar x valor_unitario)
	$id_s_v= "v_".$fornecedor."_".$linha; //id do input text>>> soma do valor a vista(subtotal)

	$tb2 .="<td bgcolor='$ccc' align='right' width='35' valign='top' >\n
		<input type='text' id='$id_s_v' size='4' value='$soma_preco_v' class='input_cotacao' style='background-color: $ccc; color:$cor_fonte_v;' readonly >\n
	</td>\n";
	
	// INI -----------------------  RADIO PREÇO A VISTA ----------------------------/
	$id		= "radio_v_".$fornecedor."_".$linha;
	$valor	= "v".$item."Pr".$preco_avista;

	$tb2 .= "<td bgcolor='$ccc' align='right'>\n";
	$tb2 .= "<input type='radio' id='$id' name='tipo_forn$linha' value='$valor' $vchecked>\n";
	$tb2 .= "</td>\n";
	// FIM -----------------------  RADIO PREÇO A VISTA ----------------------------/
#FIM - ###############################################  PREÇO_AVISTA  ############################################# //


#INI - ###############################################  PREÇO_APRAZO  ############################################# //

	//input valor Unitario "VALOR_APRAZO"
	$id_vu_p= "pu_".$fornecedor."_".$linha;// id do input text>>> valor unitario

	$tb2 .="<td bgcolor='$ccc' align='right' width='35' valign='top'>\n
		<input type='text' id='$id_vu_p' size='4' value='$preco_aprazo' class='input_cotacao' style='background-color: $ccc; color:$cor_fonte_p;' readonly>\n
	</td>\n";
	
	$id_s_p= "p_".$fornecedor."_".$linha;
	$tb2 .= "<td bgcolor='$ccc' align='right' width='35' valign='top'>\n
		<input type='text' id='$id_s_p' size='4' value='$soma_preco_p' class='input_cotacao' style='background-color: $ccc; color:$cor_fonte_p;' readonly>\n
	</td>\n";

	// INI -----------------------  RADIO PREÇO A PRAZO ----------------------------/

	$id="radio_p_".$fornecedor."_".$linha;
	$valor= "p".$item."Pr".$preco_aprazo;

	$tb2 .="<td bgcolor='$ccc' align='right'>\n
			<input type='radio' id='$id' name='tipo_forn$linha' value='$valor' $pchecked>\n";
	$tb2 .="</td>\n";
	// FIM -----------------------  RADIO PREÇO A PRAZO ----------------------------/
#INI - ###############################################  PREÇO_APRAZO  ############################################# //

	$x++;
	if($cont_forn==$max_forn){	
		$tb2 .="</tr>";
		$cont_forn=1;
		$linha++;
	}else{
		$cont_forn++;
	}
	//$c++;
}
echo "$tb2";

########################################################################################################
####################### ATENÇÃO: MUDAR AS CONDIÇÕES DO WHERE CONFORME A PESQUISA #######################
############# FAMILIA, TIPO, MARCA, E COM OS ITENS NAO COTADOS E COM "a comprar" ETC. ##################
########################################################################################################
	$sql= "SELECT 
				pessoa_fornecedor as fornecedor,  
				SUM(preco_avista*quantidade) AS soma_avista, 
				SUM(preco_aprazo*quantidade) AS soma_aprazo
		   FROM tbl_cotacao_fornecedor
		   JOIN tbl_cotacao_fornecedor_item using(cotacao_fornecedor)
		   WHERE cotacao= $cotacao 
			   AND tbl_cotacao_fornecedor.status = 'cotada'
			   GROUP BY pessoa_fornecedor
			   ORDER BY pessoa_fornecedor";

	$res= pg_exec($con, $sql);
?>
  <tr  bgcolor='#cccccc' style='font-size: 10px'>
	<td align='right' colspan='14' align='right' nowrap>	  
	Valor Total dos Preços:<input type='button' name='salvar' value='Calcular' onClick='Calcula_valor_vlrTotal(1,<?echo $cont_itens;?>)'> </td>
<?

	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ){
		$fornecedor=	trim(pg_result($res,$i, fornecedor));

	  echo "
	  <td >&nbsp;</td>
	  <td align='right' ><input type='text' id='total_v$f' name='total_v$f' size='4' class='input_cotacao' style='background-color: #cccccc;' value='".$total_preco_v[$fornecedor]."' readonly></td>
	  <td ></td>
	  <td ></td>  
	  <td align='right' nowrap ><input type='text' id='total_p$f' name='total_p$f' size='4' class='input_cotacao' 
		  style='background-color: #cccccc;' value='".$total_preco_p[$fornecedor]."'></td>
	  <td ></td>";
   }
?>
  </tr>
  <tr bgcolor='#cccccc' style='font-size: 10px'>
	<td align='right' colspan='5' align='right' nowrap>	  
	  <input type='hidden' name='cotacao' value='<?echo $cotacao;?>'> 
   	  <input type='hidden' name='cotacao_fornecedor' value='<?echo $cotacao_fornecedor;?>'> 
<?
	if($finalizada)
		echo "&nbsp;";
	else{
		echo "<input type='submit' name='salvar' value='Gravar'>";
		echo "<input type='submit' name='gerar_pedido' value='Fechar Cotação'>";
	}
?>	</td>
	<td align='right' colspan='9' align='right' nowrap>	  
		Valor Total Comprado:<input type='button' name='salvar' value='Calcular' onClick='calcula_total_selecionado(1,<?echo $cont_itens;?> )'> </td>

<?
	for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
	    echo "
		<td align='right' nowrap ></td>
		<td align='right' nowrap >
			<input type='text' id='resultado_v$f' name='resultado_v$f' class='input_cotacao' size='4' value='0'> 	
		</td>
		<td align='right' nowrap ></td>
		<td align='right' nowrap ></td>
		<td align='right' nowrap >
			<input type='text' id='resultado_p$f' name='resultado_p$f' class='input_cotacao' size='4' value='0'> 	
		</td>
		<td align='right' nowrap ></td>";
	}
?>
  </tr>
  <tr style='font-size: 10px'>
    <td align='right' colspan='6' nowrap>&nbsp;</td>

  </tr>

</table>



















<?//IMPRIME OS pecaS QUE JÁ FORAM COMPRADOS?>

<table align='center' border='0' cellspacing='1' cellpadding='2' width='800'>
  <tr bgcolor='#5b9d6e'>
	<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='<?echo $colspan;?>' width='100%' align='left'>
		<font color='#ffffff' size='3'>Produto Compradas</font>
	</td>
  </tr>
  <tr bgcolor='#5b9d6e'>
	<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='3' align='center' >PEÇA</td>
	<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='3' align='center' >MÉDIA CONSUMO</td>
	<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='5' align='center' >QUANTIDADE</td>
	<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='1' align='center' >&nbsp;</td>
<?
	$sql= "	SELECT 
				case when char_length(tbl_pessoa.nome) > 35
					then substring(tbl_pessoa.nome from 0 for 35) || '...'
					else substring(tbl_pessoa.nome from 0 for 35)
				end  as nome,
				tbl_condicao.descricao,
				tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor, 
				tbl_cotacao_fornecedor.cotacao_fornecedor,
				tbl_cotacao_fornecedor.prazo_entrega
			FROM tbl_cotacao
			JOIN tbl_cotacao_fornecedor using(cotacao)
			JOIN tbl_condicao		   on tbl_condicao.condicao		   = tbl_cotacao_fornecedor.condicao_pagamento
			JOIN tbl_pessoa_fornecedor on tbl_pessoa_fornecedor.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
			JOIN tbl_pessoa			   on tbl_pessoa.pessoa			   = tbl_pessoa_fornecedor.pessoa
			WHERE tbl_cotacao_fornecedor.status='cotada' and tbl_cotacao.cotacao= $cotacao
			ORDER BY tbl_pessoa_fornecedor.pessoa";
				
	$forn= pg_exec($con, $sql);
	$tb2 ="";
//	$tb2 .="<tr bgcolor='#eeeeff' style='font-size: 10px'>";
	$tb2 .="<tr bgcolor='#fafafa' style='font-size: 10px'>";

	$cc=0;

	$cor1=array("#ddddee","#eedede","#ddeedd","#ededee");
	$cor2=array("#EEEEFF","#FFEEEE","#EEFFEE","#FEFEEE");

	for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		$cont_fornecedor	= @pg_numrows ($forn);
		$fornecedor			= trim(pg_result($forn, $f, fornecedor));
		$nome_fornecedor	= trim(utf8_decode(pg_result($forn, $f, nome)));
		$cotacao_fornecedor = trim(pg_result($forn, $f, cotacao_fornecedor));

		$cor_f[1][$fornecedor]= $cor1[$cc];
		$cor_f[2][$fornecedor]= $cor2[$cc];
		
		if(strlen($nome_fornecedor)>25)
			$nome_fornecedor= substr($nome_fornecedor, 0,20) . "...";
		echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='6' align='center'  style='font-size: 8px'>";
		echo "<a href='cotacao_fornecedor.php?cotacao_fornecedor=$cotacao_fornecedor'>
			<font color='#ffffff'>$fornecedor-$nome_fornecedor</font></a>";
		//echo "<input type='hidden' id='fornecedor_$f' name='fornecedor_$f' value='$fornecedor'>";
		//echo "<input type='hidden' id='cont_fornecedor' name='cont_f' value='$cont_fornecedor'>";
		echo "</td>";

		$cc++;
		if($cc >3) $cc=0;
//		echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap colspan='5' align='center'>$fornecedor-$nome_fornecedor</td>";
	}
	
  echo "</tr>";
  echo "<tr bgcolor='#5b9d6e'>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Código</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Cód.Fab.</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' width='200' nowrap rowspan='2' align='center'>Nome do peca</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2'	align='center'>Ult. <br>40 d</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Ult. <br>20 d</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Ult. <br>7 d</td>";

  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2'	align='center'>Qtde <br>Disp.</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Qtde <br>Entr.</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Qtde <br>Cotar</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Qtde <br>Comprar</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Qtde <br>Durac</td>";
  echo "<td class='menu_top' background='imagens/verde_escuro.gif' nowrap rowspan='2' align='center'>Prazo <br>de Entr.</td>";


  for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		$fornecedor=trim(pg_result($forn, $f, fornecedor));
		$nome_fornecedor=trim(utf8_decode(pg_result($forn, $f, nome)));
		$desc_cond_pagamento= trim(utf8_decode(pg_result($forn, $f, descricao)));
		$prazo_entrega		= trim(pg_result($forn, $f, prazo_entrega));
		echo "<td class='menu_top' background='imagens/verde_escuro.gif' colspan='6' nowrap align='center'>Forma Pag: $desc_cond_pagamento| Ent.$prazo_entrega dias</td>";		
  }
  echo "</tr>";
  echo "<tr >";
  for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		//$fornecedor=trim(pg_result($forn, $f, fornecedor));
		//$nome_fornecedor=trim(pg_result($forn, $f, nome));
	  echo "<td class='menu_top' background='imagens/verde_escuro.gif' colspan='3' nowrap align='center'>Vlr vista</td>";
	  echo "<td class='menu_top' background='imagens/verde_escuro.gif' colspan='3' nowrap align='center'>Vlr prazo</td>";
  }

  echo "</tr>";

  $sql= "SELECT COUNT (cotacao_item) as cont_itens
  FROM TBL_COTACAO
  JOIN TBL_cotacao_item USING(COTACAO)
  WHERE COTACAO= $cotacao";
  
  $res= pg_exec($con, $sql);

  $cont_itens= trim(pg_result($res,0,cont_itens));
  
  
  $sql= "
	SELECT 
		tbl_cotacao.cotacao, 
		tbl_cotacao_fornecedor.cotacao_fornecedor,
		tbl_cotacao_fornecedor.prazo_entrega,
		tbl_cotacao_fornecedor.observacao,
		tbl_pessoa_fornecedor.pessoa as fornecedor,
		case when char_length(tbl_peca.descricao) > 35
			then substring(tbl_peca.descricao from 0 for 35) || '...'
			else substring(tbl_peca.descricao from 0 for 35)
		end  as nome,
		tbl_peca.peca,
		tbl_peca.referencia as codigo_fabrica,
		tbl_cotacao_fornecedor_item.cotacao_fornecedor_item,
		cast(tbl_cotacao_fornecedor_item.preco_avista as numeric(12,2)) as preco_avista,
		cast(tbl_cotacao_fornecedor_item.preco_aprazo as numeric(12,2)) as preco_aprazo,
		tbl_cotacao_fornecedor_item.tipo_preco,
		tbl_cotacao_fornecedor_item.prazo_entrega as item_prazo_entrega,
		tbl_cotacao_item.media_7 as media_7,
		tbl_cotacao_item.media_20 as media_20,
		tbl_cotacao_item.media_40 as media_40,
		tbl_cotacao_item.media4 as media4,
		tbl_cotacao_item.quantidade_disponivel as qd,
		tbl_cotacao_item.quantidade_entregar as qe,
		tbl_cotacao_item.quantidade_acotar as qs,
		tbl_cotacao_item.quantidade_comprar as qc,
		tbl_cotacao_item.cotacao_item,
		tbl_cotacao_item.item_selecionado,
		tbl_cotacao_item.tipo_item_selecionado
	FROM tbl_cotacao_fornecedor_item
	JOIN tbl_cotacao_fornecedor on tbl_cotacao_fornecedor.cotacao_fornecedor = tbl_cotacao_fornecedor_item.cotacao_fornecedor
	JOIN tbl_pessoa_fornecedor	on tbl_pessoa_fornecedor.pessoa				 = tbl_cotacao_fornecedor.pessoa_fornecedor
	JOIN tbl_cotacao			on tbl_cotacao.cotacao						 = tbl_cotacao_fornecedor.cotacao
	JOIN tbl_cotacao_item		on (tbl_cotacao_item.cotacao				 = tbl_cotacao.cotacao 
								AND tbl_cotacao_item.peca					 = tbl_cotacao_fornecedor_item.peca)
	JOIN tbl_peca				on tbl_peca.peca							 = tbl_cotacao_item.peca
	JOIN tbl_peca_item			on tbl_peca.peca							 = tbl_peca_item.peca
	WHERE tbl_cotacao_fornecedor.status='cotada'  
		AND tbl_cotacao.cotacao= $cotacao 
		AND tbl_cotacao_item.status='comprado'
		ORDER BY tbl_peca.descricao, tbl_pessoa_fornecedor.pessoa";

$res= pg_exec($con, $sql);

$total_preco_v= "";
$total_preco_p= "";


  // - - - - - - - - - - - BUSCAR ITENS QUE FORAM SELECIONADOS - - - - - - - -- - - - - -//
  $sql= "SELECT 
			peca,
			item_selecionado,
			tipo_item_selecionado,
			tbl_cotacao_item.status
		FROM tbl_cotacao_item
		JOIN tbl_peca USING(peca)
		JOIN tbl_peca_item USING(peca)
		WHERE cotacao=$cotacao
		ORDER BY tbl_cotacao_item.STATUS, tbl_peca.descricao";

  $res_item= pg_exec($con, $sql);		

	if(@pg_numrows ($res_item)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res_item) ; $i++ ) {
			$peca			   = trim(pg_result($res_item,$i, peca));
			$item_selecionado	   = trim(pg_result($res_item,$i, item_selecionado));
			$tipo_item_selecionado = trim(pg_result($res_item,$i, tipo_item_selecionado));
			$status				   = trim(pg_result($res_item,$i, status));
			$vet_item_selecionado[$peca][0]= $item_selecionado;
			$vet_item_selecionado[$peca][1]= $tipo_item_selecionado;
			$vet_item_selecionado[$peca][2]= $status;
		}
	}else{
			$vet_item_selecionado	="";
	}
	// FIM - - - - - - - - - - - BUSCAR ITENS QUE FORAM SELECIONADOS - - - - - - - -- - - - - -//



	// - - - - - - - - - - - BUSCAR MENOR PREÇO DA COTAÇAO - - - - - - - -- - - - - -//
	$sql= "SELECT 
			cast(MIN(preco_avista) as numeric(12,2)) as menor,
			peca
		FROM tbl_cotacao_fornecedor
		JOIN tbl_cotacao_fornecedor_item USING(cotacao_fornecedor)
		WHERE cotacao=$cotacao 
			AND (preco_avista > 0 and preco_avista IS NOT NULL)
		GROUP BY peca
		ORDER BY peca";

	$res_preco= pg_exec($con, $sql);

	if(@pg_numrows ($res_preco)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res_preco) ; $i++ ) {
			$peca =trim(pg_result($res_preco,$i,peca));
			$menor=trim(pg_result($res_preco,$i,menor));
			$preco_menor[$peca] = $menor;
		}
	}else{
			$preco_menor="";
	}
	// FIM - - - - - - - - - - - BUSCAR MENOR PREÇO DA COTAÇAO - - - - - - - -- - - - - -//



$c=1;
$max_forn=@pg_numrows ($forn);
$cont_forn=1;
$linha=0;
$cc=0;
$total_avista=0;
for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

	$radio_preco= "preco".$i;
	
	if($cont_forn==1){
		if ($c==2){
			$tb2 .="<tr bgcolor='#ebebeb' style='font-size: 10px'>";
			$c=1;
		}else {
			$tb2 .="<tr bgcolor='#fafafa' style='font-size: 10px'>";
			$c++;
		}
			
		$peca=trim(pg_result($res,$i,peca));
		$codigo_fabrica=trim(pg_result($res,$i,codigo_fabrica));
		$nome	=trim(utf8_decode(pg_result($res,$i,nome)));
		$media_7	=trim(pg_result($res,$i,media_7));
		$media_20	=trim(pg_result($res,$i,media_20));
		$media_40	=trim(pg_result($res,$i,media_40));
		$media4	=trim(pg_result($res,$i,media4));
		$qd		=trim(pg_result($res,$i,qd));
		$qe		=trim(pg_result($res,$i,qe));
		$qs		=trim(pg_result($res,$i,qc));
		$qc		=trim(pg_result($res,$i,qc));

		$item_prazo_entrega	=trim(pg_result($res,$i,item_prazo_entrega));

/*		if(($media_7 +$media_20 + $media_40)==0){
			$medias=1;
		}
		$media4= ($medias/3);
		if($media4==0) $media4=1;
		$qtdes= ($qd+$qe+$qc);
		if($qtdes==0) $qtdes=1;

		$vl_duracao=(($qtdes)/($media4/7));
		$vl_duracao= sprintf("%01.2f", $vl_duracao);*/
		
		
		if($media4==0){
			$media4 = $media_7;
		}
		$qtdes= ($qd+$qe+$qc);
		if($qtdes==0) $qtdes=0.11;
		
		if((strlen($media_7)==0) or $media_7==0){
			$media_7=0;
		}else{
			$vl_duracao = ($qtdes /$media_7);
			$vl_duracao= sprintf("%01.2f", $vl_duracao);
		}

		$tb2 .="<td nowrap align='center'>$peca <img src=\"imagens/mais.gif\" border='0'  style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_produto($peca)\">
			</td>";
		$tb2 .="<td nowrap align='left'>$codigo_fabrica</td>";
		$tb2 .="<td nowrap align='left'>$nome</td>";
		$tb2 .="<td nowrap align='center'>$media_40</td>";
		$tb2 .="<td nowrap align='center'>$media_20</td>";
		$tb2 .="<td nowrap align='center'>$media_7</td>";
		$tb2 .="<td nowrap align='center'>$qd </td>";
		$tb2 .="<td nowrap align='center'>$qe</td>";
		$tb2 .="<td nowrap align='center'>$qs</td>";
		$tb2 .="<td align='center' nowrap>$qc</td>";
		$tb2 .="<td nowrap align='center'>$vl_duracao</td>";
		$tb2 .="<td nowrap align='center'>$item_prazo_entrega</td>";
	}
	
	$peca				= trim(pg_result($res,$i, peca));
	$cotacao_fornecedor	= trim(pg_result($res,$i, cotacao_fornecedor));
	$item				= trim(pg_result($res,$i, cotacao_fornecedor_item));
	$preco_avista		= trim(pg_result($res,$i, preco_avista));
	$preco_aprazo		= trim(pg_result($res,$i, preco_aprazo));
	$tipo_preco			= trim(pg_result($res,$i, tipo_preco));

	if($preco_avista==0) $preco_avista="-";
	if($preco_aprazo==0) $preco_aprazo="-";

	$vchecked="";
	$pchecked="";

	$item_selecionado		= $vet_item_selecionado[$peca][0];
	$tipo_item_selecionado	= $vet_item_selecionado[$peca][1];

	//TESTA QUAL EH O ITEM SELECIONADO (COMPRADO)
	if($item_selecionado==$item){
		if($tipo_item_selecionado=="v"){
			$vchecked="checked";
			$vartxt= "vchecked";
		}else{
			if($tipo_item_selecionado=="p"){
				$pchecked="checked";
				$vartxt= "pchecked";
			}else	
				echo "<font color='#ff0000'>ERRO EM VCHECKED E PCHECKED </font>";
		}
	}else{
		$vartxt= "else";
	}

	$ccc=$cor_f[$c][$fornecedor];

	if($preco_menor[$peca]== $preco_avista){
		//aqui o preco eh menor
	
		$cor_fonte_v="#ff0000";
	}else{
		$cor_fonte_v="#000000";
	}

	if($preco_avista==0){
		$preco_avista="-";
		$soma_preco_v="-";
	}else{
		$soma_preco_v= ($qc* $preco_avista);
		$soma_preco_v= number_format(str_replace( '.', '', $soma_preco_v), 2, ',','');
		$total_preco_v[$fornecedor]= ($total_preco_v[$fornecedor]+$soma_preco_v);
	}

	if($preco_aprazo==0){
		$preco_aprazo="-";
		$soma_preco_p="-";
	}else{
		$soma_preco_p= ($qc* $preco_aprazo);
		$soma_preco_p= number_format(str_replace( '.', '', $soma_preco_p), 2, ',','');
		$total_preco_p[$fornecedor]= ($total_preco_p[$fornecedor]+$soma_preco_p);
	}
	//PREÇO A VISTA
	$tb2 .="<td bgcolor='$ccc' align='right' width='35' nowrap valign='top'><font color='$cor_fonte_v'>$preco_avista</font></td>";
	$tb2 .="<td bgcolor='$ccc' align='right' width='35' nowrap valign='top'><font color='$cor_fonte_v'>$soma_preco_v</font></td>";

	$tb2 .= "<td bgcolor='$ccc' align='right' nowrap>";
	$tb2 .= "<input type='radio' name='radio_comprado$linha' $vchecked></td>";

	//PREÇO A PRAZO
	$tb2 .= "<td bgcolor='$ccc' align='right' width='35' nowrap valign='top'>$preco_aprazo</td>";
	$tb2 .= "<td bgcolor='$ccc' align='right' width='35' nowrap valign='top'>$soma_preco_p</td>";

	$tb2 .="<td bgcolor='$ccc' align='right' nowrap >";
	$tb2 .="<input type='radio' name='radio_comprado$linha' $pchecked></td>";

	$x++;
	if($cont_forn==$max_forn){	
		$tb2 .="</tr>";
		$cont_forn=1;
		$linha++;
	}else{
		$cont_forn++;
	}
	//$c++;
}
echo "$tb2";

	
$sql= " SELECT pessoa_fornecedor
		FROM tbl_cotacao_fornecedor
		JOIN tbl_cotacao_fornecedor_item USING(cotacao_fornecedor)
		WHERE cotacao= $cotacao
			AND tbl_cotacao_fornecedor.status = 'cotada'
		GROUP BY pessoa_fornecedor 
		ORDER BY pessoa_fornecedor";

	$res= pg_exec($con, $sql);
?>
  <tr bgcolor='#cccccc' style='font-size:10px'>
	<td align='right' colspan='11' align='right' nowrap>
		Valor Total dos Preços:
	</td>
<?
	for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ){
		$fornecedor=	trim(pg_result($res,$i, pessoa_fornecedor));
		echo "
		<td align='right' nowrap ></td>
		<td align='right' nowrap >$total_preco_v[$fornecedor]</td>
		<td align='right' nowrap ></td>
		<td align='right' nowrap ></td>  
		<td align='right' nowrap >$total_preco_p[$fornecedor]</td>
		<td align='right' nowrap ></td>  ";
   }
?>
  </tr>
  <tr  bgcolor='#cccccc' style='font-size: 10px'>
	<td align='right' colspan='11' align='right' nowrap>Valor Total Comprado:</td>

<?
	for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
	    echo "
		<td align='right' nowrap ></td>
		<td align='right' nowrap >0</td>
		<td align='right' nowrap ></td>
		<td align='right' nowrap ></td>
		<td align='right' nowrap >0</td>
		<td align='right' nowrap ></td>
			";
	}
?>
  </tr>

</table>


























<?	//IMPRIME OS pecaS QUE JÁ FORAM INATIVADOS	?>
<table align='center' border='0' cellspacing='1' cellpadding='2' width='800'>
  <tr bgcolor='#dc9966'>
	<td class='menu_top' background='imagens/vermelho.gif' nowrap colspan='<?echo $colspan;?>' width='100%' align='left'>
		<font size='3' color='#ffffff'>Produtos Bloqueadas para Compra</font>
	</td>
  </tr>
  <tr bgcolor='#dc9966'>
	<td class='menu_top' background='imagens/vermelho.gif' nowrap colspan='3' align='center' >PEÇA</td>
	<td class='menu_top' background='imagens/vermelho.gif' nowrap colspan='3' align='center' >MÉDIA CONSUMO</td>
	<td class='menu_top' background='imagens/vermelho.gif' nowrap colspan='5' align='center' >QUANTIDADE</td>

<?
	$sql= "	SELECT 
				case when char_length(tbl_pessoa.nome) > 35
					then substring(tbl_pessoa.nome from 0 for 35) || '...'
					else substring(tbl_pessoa.nome from 0 for 35)
				end  as nome,
				tbl_condicao.descricao,
				tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor, 
				tbl_cotacao_fornecedor.cotacao_fornecedor,
				tbl_cotacao_fornecedor.prazo_entrega
			FROM tbl_cotacao
			JOIN tbl_cotacao_fornecedor using(cotacao)
			JOIN tbl_condicao			on tbl_condicao.condicao		= tbl_cotacao_fornecedor.condicao_pagamento
			JOIN tbl_pessoa_fornecedor  on tbl_pessoa_fornecedor.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
			JOIN tbl_pessoa				on tbl_pessoa.pessoa			= tbl_pessoa_fornecedor.pessoa 
			WHERE tbl_cotacao_fornecedor.status='cotada' and tbl_cotacao.cotacao= $cotacao
			ORDER BY tbl_pessoa_fornecedor.pessoa";
				
	$forn= pg_exec($con, $sql);
	$tb2 ="";
	$tb2 .="<tr bgcolor='#fafafa' style='font-size: 10px'>";

	$cc=0;

	$cor1=array("#DDDDEE","#EEDEDE","#DDEEDD","#EDEDEE");
	$cor2=array("#EEEEFF","#FFEEEE","#EEFFEE","#FEFEEE");

	for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		$cont_fornecedor	= @pg_numrows ($forn);
		$fornecedor			= trim(pg_result($forn, $f, fornecedor));
		$nome_fornecedor	= trim(utf8_decode(pg_result($forn, $f, nome)));
		$cotacao_fornecedor = trim(pg_result($forn, $f, cotacao_fornecedor));

		$cor_f[1][$fornecedor]= $cor1[$cc];
		$cor_f[2][$fornecedor]= $cor2[$cc];
		
		if(strlen($nome_fornecedor)>25)
			$nome_fornecedor= substr($nome_fornecedor, 0,20) . "...";
		echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap colspan='6' align='center'  style='font-size: 8px'>";
		echo "<a href='cotacao_fornecedor.php?cotacao_fornecedor=$cotacao_fornecedor'>
			<font color='#ffffff'>$fornecedor-$nome_fornecedor</font></a>";
		//echo "<input type='hidden' id='fornecedor_$f' name='fornecedor_$f' value='$fornecedor'>";
		//echo "<input type='hidden' id='cont_fornecedor' name='cont_f' value='$cont_fornecedor'>";
		echo "</td>";

		$cc++;
		if($cc >3) $cc=0;
//		echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap colspan='5' align='center'>$fornecedor-$nome_fornecedor</td>";
	}
	
  echo "</tr>";
  echo "<tr bgcolor='#dc9966'>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Código</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Cód.Fab.</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' width='200' nowrap rowspan='2' align='center'>Nome do peca</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2'	align='center'>Ult. <br>40 d</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Ult. <br>20 d</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Ult. <br>7 d</td>";

  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2'	align='center'>Qtde <br>Disp.</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Qtde <br>Entr.</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Qtde <br>Cotar</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Qtde <br>Comprar</td>";
  echo "<td class='menu_top' background='imagens/vermelho.gif' nowrap rowspan='2' align='center'>Qtde <br>Durac</td>";

  for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		$fornecedor			= trim(pg_result($forn, $f, fornecedor));
		$nome_fornecedor	= trim(pg_result($forn, $f, nome));
		$desc_cond_pagamento= trim(pg_result($forn, $f, descricao));
		$prazo_entrega		= trim(pg_result($forn, $f, prazo_entrega));
		echo "<td class='menu_top' background='imagens/vermelho.gif' colspan='6' nowrap align='center'>Forma Pag: $desc_cond_pagamento| Ent.$prazo_entrega dias</td>";		
  }
  echo "</tr>";
  echo "<tr bgcolor='#dc9966'>";
  for ( $f = 0 ; $f < @pg_numrows ($forn) ; $f++ ){
		//$fornecedor=trim(pg_result($forn, $f, fornecedor));
		//$nome_fornecedor=trim(pg_result($forn, $f, nome));
	  echo "<td class='menu_top' background='imagens/vermelho.gif' background='imagens/vermelho.gif' colspan='3' nowrap align='center'>Vlr vista</td>";
	  echo "<td class='menu_top' background='imagens/vermelho.gif' colspan='3' nowrap align='center'>Vlr prazo</td>";
  }
  echo "</tr>";

  $sql= "	SELECT COUNT (cotacao_item) as cont_itens
			FROM tbl_cotacao
			JOIN tbl_cotacao_item USING(cotacao)
			WHERE cotacao = $cotacao";
  
  $res= pg_exec($con, $sql);

  $cont_itens= trim(pg_result($res,0,cont_itens));
  
$sql= "
	SELECT 
		tbl_cotacao.cotacao, 
		tbl_cotacao_fornecedor.cotacao_fornecedor,
		tbl_cotacao_fornecedor.prazo_entrega,
		tbl_cotacao_fornecedor.observacao,
		tbl_pessoa_fornecedor.pessoa as fornecedor,
		case when char_length(tbl_peca.descricao) > 35
			then substring(tbl_peca.descricao from 0 for 35) || '...'
			else substring(tbl_peca.descricao from 0 for 35)
		end  as nome,
		tbl_peca.peca,
		tbl_peca.referencia as codigo_fabrica,
		tbl_cotacao_fornecedor_item.cotacao_fornecedor_item,
		replace(cast(cast(tbl_cotacao_fornecedor_item.preco_avista as numeric(12,2)) as varchar(14)),'.', ',') as preco_avista,
		replace(cast(cast(tbl_cotacao_fornecedor_item.preco_aprazo as numeric(12,2)) as varchar(14)),'.', ',') as preco_aprazo,
		tbl_cotacao_fornecedor_item.tipo_preco,
		tbl_cotacao_item.media_7 as media_7,
		tbl_cotacao_item.media_20 as media_20,
		tbl_cotacao_item.media_40 as media_40,
		tbl_cotacao_item.media4 as media4,
		tbl_cotacao_item.quantidade_disponivel as qd,
		tbl_cotacao_item.quantidade_entregar as qe,
		tbl_cotacao_item.quantidade_acotar as qs,
		tbl_cotacao_item.quantidade_comprar as qc,
		tbl_cotacao_item.cotacao_item,
		tbl_cotacao_item.item_selecionado,
		tbl_cotacao_item.tipo_item_selecionado
	FROM tbl_cotacao_fornecedor_item
	JOIN tbl_cotacao_fornecedor on tbl_cotacao_fornecedor.cotacao_fornecedor = tbl_cotacao_fornecedor_item.cotacao_fornecedor
	JOIN tbl_pessoa_fornecedor	on tbl_pessoa_fornecedor.pessoa				 = tbl_cotacao_fornecedor.pessoa_fornecedor
	JOIN tbl_cotacao			on tbl_cotacao.cotacao						 = tbl_cotacao_fornecedor.cotacao
	JOIN tbl_cotacao_item		on (tbl_cotacao_item.cotacao				 = tbl_cotacao.cotacao 
								AND tbl_cotacao_item.peca					 = tbl_cotacao_fornecedor_item.peca)
	JOIN tbl_peca				on tbl_peca.peca							 = tbl_cotacao_item.peca
	JOIN tbl_peca_item			on tbl_peca.peca							 = tbl_peca_item.peca
	WHERE tbl_cotacao_fornecedor.status='cotada'  
			AND tbl_cotacao.cotacao= $cotacao 
			AND tbl_cotacao_item.status='inativo'
		ORDER BY tbl_peca.descricao, tbl_pessoa_fornecedor.pessoa";

  $res= pg_exec($con, $sql);


  // - - - - - - - - - - - BUSCAR ITENS QUE FORAM SELECIONADOS - - - - - - - -- - - - - -//
  $sql= "SELECT 
			peca,
			item_selecionado,
			tipo_item_selecionado,
			tbl_cotacao_item.status
		FROM tbl_cotacao_item
		JOIN tbl_peca USING(peca)
		JOIN tbl_peca_item USING(peca)
		WHERE cotacao = $cotacao
		ORDER BY tbl_cotacao_item.status, tbl_peca.descricao";


  $res_item= pg_exec($con, $sql);		

	if(@pg_numrows ($res_item)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res_item) ; $i++ ) {
			$peca			   = trim(pg_result($res_item,$i, peca));
			$item_selecionado	   = trim(pg_result($res_item,$i, item_selecionado));
			$tipo_item_selecionado = trim(pg_result($res_item,$i, tipo_item_selecionado));
			$status				   = trim(pg_result($res_item,$i, status));
			$vet_item_selecionado[$peca][0]= $item_selecionado;
			$vet_item_selecionado[$peca][1]= $tipo_item_selecionado;
			$vet_item_selecionado[$peca][2]= $status;
		}
	}else{
			$vet_item_selecionado	="";
	}
	// FIM - - - - - - - - - - - BUSCAR ITENS QUE FORAM SELECIONADOS - - - - - - - -- - - - - -//

	// - - - - - - - - - - - BUSCAR MENOR PREÇO DA COTAÇAO - - - - - - - -- - - - - -//
	$sql= "	SELECT 
				cast(MIN(preco_avista) as numeric(12,2)) as menor,
				peca
			FROM tbl_cotacao_fornecedor
			JOIN tbl_cotacao_fornecedor_item USING(cotacao_fornecedor)
			WHERE cotacao=$cotacao AND (preco_avista > 0 
				AND preco_avista IS NOT NULL)
			GROUP BY peca
			ORDER BY peca";

	$res_preco= pg_exec($con, $sql);

	if(@pg_numrows ($res_preco)>0){
		for ( $i = 0 ; $i < @pg_numrows ($res_preco) ; $i++ ) {
			$peca =trim(pg_result($res_preco,$i,peca));
			$menor=trim(pg_result($res_preco,$i,menor));
			$preco_menor[$peca] = $menor;
		}
	}else{
			$preco_menor="";
	}
	// FIM - - - - - - - - - - - BUSCAR MENOR PREÇO DA COTAÇAO - - - - - - - -- - - - - -//

$max_forn=@pg_numrows ($forn);
$cont_forn=1;
$linha=0;
$c=1;
$cc=0;
$total_avista=0;
for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {

	$radio_preco= "preco".$i;
	
	if($cont_forn==1){
		if ($c==2){
			$tb2 .="<tr bgcolor='#ebebeb' style='font-size: 10px'>";
			$c=1;
		}else {
			$tb2 .="<tr bgcolor='#fafafa' style='font-size: 10px'>";
			$c++;
		}
	
		$peca			=trim(pg_result($res,$i,peca));
		$codigo_fabrica	=trim(pg_result($res,$i,codigo_fabrica));
		$nome			=trim(pg_result($res,$i,nome));
		$media_7		=trim(pg_result($res,$i,media_7));
		$media_20		=trim(pg_result($res,$i,media_20));
		$media_40		=trim(pg_result($res,$i,media_40));
		$media4			=trim(pg_result($res,$i,media4));
		$qd				=trim(pg_result($res,$i,qd));
		$qe				=trim(pg_result($res,$i,qe));
		$qs				=trim(pg_result($res,$i,qc));
		$qc				=trim(pg_result($res,$i,qc));

		if(($media_7 +$media_20 + $media_40)==0){
			$medias=1;
		}
		$media4= ($medias/3);
		if($media4==0) $media4=1;
		$qtdes= ($qd+$qe+$qc);
		if($qtdes==0) $qtdes=1;

		$vl_duracao=(($qtdes)/($media4/7));
		$vl_duracao= sprintf("%01.2f", $vl_duracao);

		$tb2 .="<td nowrap align='center'>$linha - $peca <img src=\"imagens/bt_more.gif\" border='0'  style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_produto($peca)\">

			</td>";
		$tb2 .="<td nowrap align='left'>$codigo_fabrica</td>";
		$tb2 .="<td nowrap align='left'>$nome</td>";
		$tb2 .="<td nowrap align='center'>$media_40</td>";
		$tb2 .="<td nowrap align='center'>$media_20</td>";
		$tb2 .="<td nowrap align='center'>$media_7</td>";
		$tb2 .="<td nowrap align='center'>$qd </td>";
		$tb2 .="<td nowrap align='center'>$qe</td>";
		$tb2 .="<td nowrap align='center'>$qs</td>";
		$tb2 .=" <td align='center' nowrap >$qc</td>";
		$tb2 .="<td nowrap align='center'>$vl_duracao</td>";
	}
	
	$peca				= trim(pg_result($res,$i, peca));
	$fornecedor			= trim(pg_result($res,$i, fornecedor));
	$cotacao_fornecedor	= trim(pg_result($res,$i, cotacao_fornecedor));
	$item				= trim(pg_result($res,$i, cotacao_fornecedor_item));
	$preco_avista		= trim(pg_result($res,$i, preco_avista));
	$preco_aprazo		= trim(pg_result($res,$i, preco_aprazo));
	$tipo_preco			= trim(pg_result($res,$i, tipo_preco));

	if($preco_avista==0) $preco_avista = "-";
	if($preco_aprazo==0) $preco_aprazo = "-";

	$vchecked="";
	$pchecked="";
	
	$item_selecionado		= $vet_item_selecionado[$peca][0];
	$tipo_item_selecionado	= $vet_item_selecionado[$peca][1];

	//TESTA QUAL EH O ITEM SELECIONADO (COMPRADO)
	if($item_selecionado==$item){
		if($tipo_item_selecionado=="v"){
			$vchecked="checked";
			$vartxt= "vchecked";
		}else{
			if($tipo_item_selecionado=="p"){
				$pchecked="checked";
				$vartxt= "pchecked";
			}else	
				echo "<font color='#ff0000'>ERRO EM VCHECKED E PCHECKED </font>";
		}
	}else{
		$vartxt= "else";
	}

	if($preco_menor[$peca]== $preco_avista){
		//aqui o preco eh menor
		$ccc=$cor_f[$c][$fornecedor];
		$cor_fonte_v= "#ff0000";

	}else{
		$ccc=$cor_f[$c][$fornecedor];
		$cor_fonte_v= "#000000";
	}

	if($preco_avista==0){
		$preco_avista="-";
		$soma_preco_v="-";
	}else{
		$soma_preco_v= ($qc* $preco_avista);
		$soma_preco_v= number_format(str_replace( '.', '', $soma_preco_v), 2, ',','');
	}

	if($preco_aprazo==0){
		$preco_aprazo="-";
		$soma_preco_p="-";
	}else{
		$soma_preco_p= ($qc* $preco_aprazo);
		$soma_preco_p= number_format(str_replace( '.', '', $soma_preco_p), 2, ',','');
	}
	//PREÇO A VISTA
	$tb2 .="<td bgcolor='$ccc' align='right' width='35' nowrap ><font color='$cor_fonte_v'> $preco_avista </font></td>";
	$tb2 .="<td bgcolor='$ccc' align='right' width='35' nowrap ><font color='$cor_fonte_v'>$soma_preco_v</font></td>";

	$tb2 .= "<td bgcolor='$ccc' align='center' width='10' nowrap>";
	$tb2 .= "<input type='radio' name='radio_comprado$linha' $vchecked disabled></td>";

	//PREÇO A PRAZO
	$tb2 .= "<td bgcolor='$ccc' align='right' width='35' nowrap >$preco_aprazo</td>";
	$tb2 .= "<td bgcolor='$ccc' align='right' width='35' nowrap >$soma_preco_p</td>";

	$tb2 .="<td bgcolor='$ccc' align='center' nowrap >";
	$tb2 .="<input type='radio' name='radio_comprado$linha' $pchecked disabled></td>";

	$x++;
	if($cont_forn==$max_forn){	
		$tb2 .="</tr>";
		$cont_forn=1;
		$linha++;
	}else{
		$cont_forn++;
	}
}
echo "$tb2";
?>

</form>
</table>




