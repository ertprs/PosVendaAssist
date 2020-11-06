<?
//OBS: ESTE ARQUIVOS UTILIZA AJAX: nf_britania_ret_ajax.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0)
	$faturamento = $_GET["faturamento"];

$btn_acao		= $_POST["btn_acao"];

if(strlen($btn_acao)==0)
	$btn_acao = $_GET["btn_acao"];

$erro_msg_		= $_GET["erro_msg"];
//SE NAO FOR O POSTO DE TESTE OU O DISTRIB.
if(($login_posto <> 6359) and ($login_posto <> 4311)){
	
	echo "NÃO É PERMITIDO LANÇAR NOTA FISCAL - longin: $login_posto";
	exit;
}

//$login_posto = 6359;
$fabrica= 3;

if ($btn_acao == "Gravar") {

	$nota_fiscal	= trim ($_POST['nota_fiscal']);

	$sql="select faturamento 
	  from tbl_faturamento 
	  where fabrica = 3 
		and posto = $login_posto
		and nota_fiscal='$nota_fiscal'";

	$res = pg_exec ($con,$sql);

	//SE JA EXISTIR O FATURAMENTO, REDIRECIONA PARA A TELA DA NOTA FISCAL
	if(pg_numrows($res)>0){
		$faturamento= trim(pg_result($res,0,faturamento));
		header ("Location: nf_britania.php?faturamento=$faturamento&erro_msg=Já foi Cadastrado a NF:$nota_fiscal");
		exit;
	}

	//INSERIR FATURAMENTO (NF)
	if(strlen($nota_fiscal)==0)
		$erro_msg  .= "Digite a Nota Fiscal" ;
	
	$emissao		=$_POST["emissao"];
	if(strlen($emissao)==0)
		$erro_msg  .= "Digite a data de emissão $emissao<br>" ;
	else
		$emissao	=  substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2);

	$saida=$_POST['saida']; 
	if(strlen($saida)==0)
		$erro_msg .= "Digite a Data de Saida<br>" ;
	else
		$saida =  substr ($saida,6,4) . "-" . substr ($saida,3,2) . "-" . substr ($saida,0,2) ;

	$total_nota=$_POST['total_nota']; 
	if(strlen($total_nota)==0)
		$erro_msg .= "Digite o Total da Nota<br>" ;
	else{
		$total_nota = str_replace(",",".",$total_nota);
		$total_nota = trim(str_replace(".00","",$total_nota));
	}

	$cfop=$_POST['cfop'];  
	if(strlen($cfop)==0)
		$erro_msg .= "Digite o Cfop<br>";

	$serie=$_POST['serie'];  
	if(strlen($serie)==0)
		$erro_msg .= "Digite o Número da Série<br>" ;

	$transp= substr($_POST['transportadora'],0,30);
	if(strlen($transp)==0)
		$erro_msg .= "Escolha a Transportadora<br>";

	$condicao=$_POST['condicao']; 
		if(strlen($condicao)==0)
		$erro_msg .= "Digite a Condição<br>" ;

	if(strlen($erro_msg)==0 ){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		//INSERIR FATURAMENTO
		$sql= "insert into tbl_faturamento 
					(fabrica, 
					emissao, 
					conferencia, 
					saida, 
					posto, 
					total_nota, 
					cfop, 
					nota_fiscal, 
					serie, 
					transp, 
					condicao)
				values
					($fabrica, 
					'$emissao', 
					current_timestamp,
					'$saida', 
					$login_posto, 
					$total_nota, 
					$cfop, 
					'$nota_fiscal', 
					$serie, 
					'$transp', 
					$condicao);";

		//echo "<br>sql: $sql";
		$res = pg_exec ($con,$sql);	

		if(pg_result_error($res)){
			$res = pg_exec ($con,"ROLLBACK;");
			$erro_msg.="<br>Erro ao inserir a NF:$nota_fiscal";
		}else{
			
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_faturamento') as fat;");
			$faturamento =trim (pg_result($res, 0 , fat));

			//INSERIR ITENS DA NOTA
			for($i=0; $i< 20; $i++){
				$erro_item	= "" ;
				$referencia =$_POST["referencia_$i"]; 
				if(strlen($referencia)>0){
					$sql= "select peca, descricao 
							from tbl_peca 
							where fabrica = 3 and referencia = '$referencia';";
					$res = pg_exec ($con,$sql);
					if(pg_numrows($res)>0){
						$peca		= trim(pg_result($res,0,peca));
						$descricao	= trim(pg_result($res,0,descricao));
					}else{
						$erro_item .= "Peça não encontrada!<br>" ;
					}

					$qtde	=$_POST["qtde_$i"];
					if(strlen($qtde)==0)
						$erro_item.= "Digite a qtde<br>" ;

					$preco=$_POST["preco_$i"]; 
					if(strlen($preco)==0){
						$erro_item.= "Digite o preco<br>";						
					}else{
						$preco = str_replace(",",".",$preco);
						$preco= trim(str_replace(".00","",$preco));
					}

					$pedido=$_POST["pedido_$i"]; 
					if(strlen($pedido)==0)
						$pedido	= "null" ;
					else{
						$sql= "select pedido 
								from tbl_pedido 
								where fabrica=3 
									and pedido= $pedido;";
						$res = pg_exec ($con,$sql);
						if(pg_numrows($res)>0){
							$pedido	= trim(pg_result($res,0,pedido));
						}else{
							$erro_item.= "Pedido não encontrado!-sqlPed: $sql<br>" ;
						}
					}
					
					$sua_os=trim($_POST["sua_os_$i"]); 
					if(strlen($sua_os)==0){
						$os = "null";
					}else{
						$sql= "select os 
								from tbl_os 
								where fabrica=3 
									and posto = $login_posto 
									and sua_os = '$sua_os';";
						$res = pg_exec ($con,$sql);
						if(pg_numrows($res)>0){
							$os	= trim(pg_result($res,0,os));
						}else{
							$erro_item.= "OS não encontrada! -sqlOS: $sql <br>" ;
						}
					}
						
					$aliq_icms=$_POST["aliq_icms_$i"]; 
					if(strlen($aliq_icms)==0)
						$aliq_icms ="0";
					else{
						$aliq_icms = str_replace(",",".",$aliq_icms);
						$aliq_icms = trim(str_replace(".00","",$aliq_icms));
					}

					$aliq_ipi=$_POST["aliq_ipi_$i"]; 
					if(strlen($aliq_ipi)==0)
						$aliq_ipi="0";
					else{
						$aliq_ipi = str_replace(",",".",$aliq_ipi);
						$aliq_ipi = trim(str_replace(".00","",$aliq_ipi));
					}
						
					$base_icms=$_POST["base_icms_$i"]; 
					if(strlen($base_icms)==0)
						$base_icms ="0";
					else{
						$base_icms = str_replace(",",".",$base_icms);
						$base_icms = trim(str_replace(".00","",$base_icms));
					}

					$valor_icms=$_POST["valor_icms_$i"]; 
					if(strlen($valor_icms)==0)
						$valor_icms ="0";
					else{
						$valor_icms = str_replace(",",".",$valor_icms);
						$valor_icms = trim(str_replace(".00","",$valor_icms));
					}

					$base_ipi=$_POST["base_ipi_$i"]; 
					if(strlen($base_ipi)==0)
						$base_ipi ="0";
					else{
						$base_ipi = str_replace(",",".",$base_ipi);
						$base_ipi = trim(str_replace(".00","",$base_ipi));
					}

					$valor_ipi=$_POST["valor_ipi_$i"]; 
					if(strlen($valor_ipi)==0)
						$valor_ipi ="0";
					else{
						$valor_ipi = str_replace(",",".",$valor_ipi);
						$valor_ipi = trim(str_replace(".00","",$valor_ipi));
					}

					if(strlen($erro_item)==0){
						$sql=  "insert into tbl_faturamento_item
									(faturamento, peca, qtde, preco, pedido, os, aliq_icms, aliq_ipi, base_icms, valor_icms, base_ipi, valor_ipi)
								values
									($faturamento,$peca,$qtde,$preco,$pedido,$os,$aliq_icms,$aliq_ipi,$base_icms,$valor_icms,$base_ipi,$valor_ipi)";

						$res = pg_exec ($con,$sql);	

						if(pg_result_error($res)){
							echo "<br>Erro ao inserir peça: $referencia";
							$erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia";
							//header ("Location: nf_britania.php?faturamento=$faturamento&erro_msg=Erro ao Cadastrar Item da NF:$nota_fiscal");
						}
					}else{
						$erro_msg .= $erro_item ;
						echo "<br>NAO INSERIUUUUUUUUUU nao inseriu i: $i - ERROOOOOO: $erro_item";
					}
				}else{
					//echo "<br>nao entrou referencia>>>>>>>>>>>>>>:".$referencia."<br>";
				}
			}//fim do for
			if(strlen($erro_msg)==0){
				//echo "<br>erro>>>>>$erro_msg";
				$res = pg_exec ($con,"COMMIT");
			}else{
				$res = pg_exec ($con,"ROLLBACK;");
			}
		}//else erro inserir faturamento
	}
}//FIM BTN: GRAVAR

//ALTERAR
if ($btn_acao == "Alterar") {
	if(strlen($faturamento)==0){
		header ("Location: nf_britania.php?faturamento=$faturamento&erro_msg=FATURAMENTO VAZIO!");
		exit;
	}
	
	$sql="select faturamento 
		  from tbl_faturamento 
		  where fabrica = 3 
			and posto = $login_posto
			and faturamento= $faturamento";

		$res = pg_exec ($con,$sql);

		//SE NAO EXISTIR O FATURAMENTO ENTAO IMPRIME ERRO
	if(pg_numrows($res)==0){
		header ("Location: nf_britania.php?faturamento=$faturamento&erro_msg=NÃO FOI ENCONTRADO O FATURAMENTO: $faturamento");
		exit;
	}
	
	$faturamento= trim(pg_result($res,0,faturamento));

	if(strlen($nota_fiscal)==0)
		$erro_msg  .= "Digite a Nota Fiscal" ;
	
	$emissao		=$_POST["emissao"];
	if(strlen($emissao)==0)
		$erro_msg  .= "Digite a data de emissão $emissao<br>" ;
	else
		$emissao	=  substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2);

	$saida=$_POST['saida']; 
	if(strlen($saida)==0)
		$erro_msg .= "Digite a Data de Saida<br>" ;
	else
		$saida =  substr ($saida,6,4) . "-" . substr ($saida,3,2) . "-" . substr ($saida,0,2) ;

	$total_nota=$_POST['total_nota']; 
	if(strlen($total_nota)==0)
		$erro_msg .= "Digite o Total da Nota<br>" ;
	else{
		$total_nota = str_replace(",",".",$total_nota);
		$total_nota = trim(str_replace(".00","",$total_nota));
	}

	$cfop=$_POST['cfop'];  
	if(strlen($cfop)==0)
		$erro_msg .= "Digite o Cfop<br>";

	$serie=$_POST['serie'];  
	if(strlen($serie)==0)
		$erro_msg .= "Digite o Número da Série<br>" ;

	$transp= substr($_POST['transportadora'],0,30);
	if(strlen($transp)==0)
		$erro_msg .= "Escolha a Transportadora<br>";

	$condicao=$_POST['condicao']; 
		if(strlen($condicao)==0)
		$erro_msg .= "Digite a Condição<br>" ;

	if(strlen($erro_msg)==0 ){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		//ALTERAR FATURAMENTO		
		
		$sql = "UPDATE tbl_faturamento
			set
				fabrica		=$fabrica, 
				emissao		='$emissao',  
				saida		='$saida', 
				posto		=$login_posto, 
				total_nota	=$total_nota, 
				cfop		=$cfop, 
				nota_fiscal	='$nota_fiscal', 
				serie		=$serie, 
				transp		='$transp', 
				condicao	=$condicao
				where fabrica = 3
					and faturamento = $faturamento;";

		//echo "<br>sql: $sql";
		$res = pg_exec ($con,$sql);

		if(pg_result_error($res)){
			$res = pg_exec ($con,"ROLLBACK;");
			$erro_msg.= "<br>Erro ao ALTERAR a NF:$nota_fiscal";
		}else{

			//UPDATE ITENS DA NOTA
			for($i=0; $i< 20; $i++){
				$erro_item	= "" ;

				$faturamento_item	=$_POST["faturamento_item_$i"];
				if(strlen($faturamento_item)==0)
					$erro_item.= "Erro no Item do Faturamento<br>" ;
				
				$referencia =$_POST["referencia_$i"];
				if(strlen($referencia)>0){
					$sql= "select peca, descricao 
							from tbl_peca 
							where referencia = '$referencia';";
					//echo "sql peça: $sql";
					$res = pg_exec ($con,$sql);
					if(pg_numrows($res)>0){
						$peca		= trim(pg_result($res,0,peca));
						$descricao	= trim(pg_result($res,0,descricao));
					}else{
						$erro_item .= "Peça não encontrada!<br>" ;
					}

					$qtde	=$_POST["qtde_$i"];
					if(strlen($qtde)==0)
						$erro_item.= "Digite a qtde<br>" ;

					$preco=$_POST["preco_$i"]; 
					if(strlen($preco)==0){
						$erro_item.= "Digite o preco<br>";						
					}else{
						$preco = str_replace(",",".",$preco);
						$preco= trim(str_replace(".00","",$preco));
					}

					$pedido=$_POST["pedido_$i"]; 
					if(strlen($pedido)==0)
						$pedido	= "null" ;
					else{
						$sql= "select pedido 
								from tbl_pedido 
								where fabrica=3 
									and pedido= $pedido;";
						$res = pg_exec ($con,$sql);
						if(pg_numrows($res)>0){
							$pedido	= trim(pg_result($res,0,pedido));
						}else{
							$erro_item.= "Pedido não encontrado!-sqlPed: $sql<br>" ;
						}
					}
					
					$sua_os=trim($_POST["sua_os_$i"]); 
					if(strlen($sua_os)==0){
						$os = "null";
					}else{
						$sql= "select os 
								from tbl_os 
								where fabrica=3 
									and posto = $login_posto 
									and sua_os = '$sua_os';";
						$res = pg_exec ($con,$sql);
						if(pg_numrows($res)>0){
							$os	= trim(pg_result($res,0,os));
						}else{
							$erro_item.= "OS não encontrada! -sqlOS: $sql <br>" ;
						}
					}
						
					$aliq_icms=$_POST["aliq_icms_$i"]; 
					if(strlen($aliq_icms)==0)
						$aliq_icms ="0";
					else{
						$aliq_icms = str_replace(",",".",$aliq_icms);
						$aliq_icms = trim(str_replace(".00","",$aliq_icms));
					}

					$aliq_ipi=$_POST["aliq_ipi_$i"]; 
					if(strlen($aliq_ipi)==0)
						$aliq_ipi="0";
					else{
						$aliq_ipi = str_replace(",",".",$aliq_ipi);
						$aliq_ipi = trim(str_replace(".00","",$aliq_ipi));
					}
						
					$base_icms=$_POST["base_icms_$i"]; 
					if(strlen($base_icms)==0)
						$base_icms ="0";
					else{
						$base_icms = str_replace(",",".",$base_icms);
						$base_icms = trim(str_replace(".00","",$base_icms));
					}

					$valor_icms=$_POST["valor_icms_$i"]; 
					if(strlen($valor_icms)==0)
						$valor_icms ="0";
					else{
						$valor_icms = str_replace(",",".",$valor_icms);
						$valor_icms = trim(str_replace(".00","",$valor_icms));
					}

					$base_ipi=$_POST["base_ipi_$i"]; 
					if(strlen($base_ipi)==0)
						$base_ipi ="0";
					else{
						$base_ipi = str_replace(",",".",$base_ipi);
						$base_ipi = trim(str_replace(".00","",$base_ipi));
					}

					$valor_ipi=$_POST["valor_ipi_$i"]; 
					if(strlen($valor_ipi)==0)
						$valor_ipi ="0";
					else{
						$valor_ipi = str_replace(",",".",$valor_ipi);
						$valor_ipi = trim(str_replace(".00","",$valor_ipi));
					}

					if(strlen($erro_item)==0){

						$sql=  "UPDATE tbl_faturamento_item
								SET 
									peca	  =$peca, 
									qtde	  =$qtde, 
									preco	  =$preco, 
									pedido	  =$pedido, 
									os		  =$os, 
									aliq_icms =$aliq_icms, 
									aliq_ipi  =$aliq_ipi, 
									base_icms =$base_icms, 
									valor_icms=$valor_icms, 
									base_ipi  =$base_ipi, 
									valor_ipi =$valor_ipi 
								WHERE faturamento_item = $faturamento_item;";
						//echo "<br> sql_item: $sql";
					
						$res = pg_exec ($con,$sql);	

						if(pg_result_error($res)){
							echo "<br>Erro ao inserir peça: $referencia";
							$erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia";
							//header ("Location: nf_britania.php?faturamento=$faturamento&erro_msg=Erro ao Cadastrar Item da NF:$nota_fiscal");
						}
					}else{
						$erro_msg .= $erro_item ;
						echo "<br>NAO INSERIUUUUUUUUUU nao inseriu i: $i - ERROOOOOO: $erro_item";
					}
				}else{
					//echo "<br>nao entrou referencia>>>>>>>>>>>>>>:".$referencia."<br>";
				}
			}//fim do for
			if(strlen($erro_msg)==0){
				//echo "<br>erro>>>>>$erro_msg";
				$res = pg_exec ($con,"COMMIT");
			}else{
				$res = pg_exec ($con,"ROLLBACK;");
			}
		}//else erro inserir faturamento
	}
}//FIM BTN: GRAVAR




?>

<html>

<title>Nota Fiscal Britania</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<head>

<script type="text/javascript" src="js/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>

<script language="JavaScript">

//funçao usada para carregar os faturamentoS
function retornaFat(http,componente) {
	var com5 = document.getElementById('f2');
	if (http.readyState == 1) {
		com5.style.display='inline';
		com5.innerHTML = "&nbsp;&nbsp;<font color='#0000ff'>Carregando...</font>&nbsp;&nbsp;<br><img src='../imagens/carregar_os.gif' >";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com5.innerHTML = results[1];

					//document.getElementById('msg_fat').innerHTML= "<font color='#ff0000'>existem faturamentos para esse fornecedor</font>";
					//document.getElementById('msg_fat').style.display='inline';
				}else{
					com5.innerHTML   = "&nbsp;&nbsp;<font color='#0000ff'>Sem faturamentos para esse fornecedor</font>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function exibirFat(componente,conta_pagar, documento, acao) {
	var nota_fiscal= document.getElementById('nota_fiscal').value;
	url = "nf_britania_ret_ajax?ajax=sim&nota_fiscal="+escape(nota_fiscal);
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFat (http,componente,nota_fiscal) ; } ;
	http.send(null);
}



function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "pesquisa_peca-igor.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.descricao = document.nf_britania.descricao; 
		janela.referencia= document.nf_britania.referencia;
		janela.focus();
	}
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO DE CADA FORNECEDOR
function calc_base_icms(i){
	var base=0.0, aliq_icms=0.0, valor_icms=0.0, aliq_ipi=0.0, valor_ipi=0.0;;
	preco		= document.getElementById('preco_'+i).value;
	qtde		= document.getElementById('qtde_'+i).value;
	aliq_icms	= document.getElementById('aliq_icms_'+i).value;
	aliq_ipi	= document.getElementById('aliq_ipi_'+i).value;


	preco		= preco.toString().replace( ".", "" );
//	alert(preco);
	qtde		= qtde.toString().replace( ".", "" );
	aliq_icms	= aliq_icms.toString().replace( ".", "" );
	aliq_ipi	= aliq_ipi.toString().replace( ".", "" );

	
	preco		= preco.toString().replace( ",", "." );
//	alert(preco);
	qtde		= qtde.toString().replace( ",", "." );
	aliq_icms	= aliq_icms.toString().replace( ",", "." );
	aliq_ipi	= aliq_ipi.toString().replace( ",", "." );

	preco		= parseFloat(preco);
	qtde		= parseFloat(qtde);
	aliq_icms	= parseFloat(aliq_icms);
	aliq_ipi	= parseFloat(aliq_ipi);

	base		= parseFloat(preco * qtde);
	base = base.toFixed(2);
	valor_icms	= ((base * aliq_icms)/100);
	valor_icms = valor_icms.toFixed(2);
	valor_ipi	= ((base *  aliq_ipi)/100);
	valor_ipi	= valor_ipi.toFixed(2);

	if(aliq_icms > 0) {
		document.getElementById('base_icms_'+i).value = base.toString().replace( ".", "," );;;
		document.getElementById('valor_icms_'+i).value = valor_icms.toString().replace( ".", "," );;
	}else{
		document.getElementById('base_icms_'+i).value = '0';
		document.getElementById('valor_icms_'+i).value = '0';
	}

	if(aliq_ipi > 0) {
		document.getElementById('base_ipi_'+i).value = base.toString().replace( ".", "," );;;
		document.getElementById('valor_ipi_'+i).value = valor_ipi.toString().replace( ".", "," );;;
	}else{
		document.getElementById('base_ipi_'+i).value = '0';
		document.getElementById('valor_ipi_'+i).value = '0';
	}
}


</script>
<style type="text/css">
.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
</style>
</head>

<body>

<? include 'menu.php';?>

<center><h1>Cadastro de Notas Fiscais Britania - NF:<? echo $nota_fiscal ?></h1></center>
	
<p>
<form name='nf_britania' method="POST" action='<? echo $PHP_SELF?>'>
<table width='650' align='center'>
<tr>
<td colspan = '6' align='right' bgcolor='red'>
<?
$erro_msg_= $erro_msg;
echo "<center>" . $erro_msg_."</center>";?>
</td>
</tr>
<tr>
	<td colspan = '3' align='left'><b>Dados da Nota Fiscal</b>
	<div name='f2' id='f2' style='padding:4px; background-color:#ffffff; filter: alpha(opacity=90); opacity: .90 border-color:#cccccc; border:1px solid #bbbbbb; display:none; width:120px; height:65px;  top: 10%; left: 50%; margin-left: -60px; position:absolute;'></div>
<?	

//echo "<INPUT TYPE='button' name='bt_alt'    id='bt_alt'  value='Alterar' onClick=\"exibirFat('dados','','','alterar')\">";
?>
	</td>
	<td colspan = '3' align='right'><a href='nf_britania.php?novo=novo'>Nova Nota Fiscal</a></td>
	</tr>
<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold' >
	<td align='center'>Fábrica</td>
	<td align='center'>Nota Fiscal</td>
	<td align='center'>Emissão</td>
	<td align='center'>Saida</td>
	<td align='center'>CFOP</td>
	<td align='center'>Série</td>
</tr>
<?
if(strlen($faturamento) > 0 ){
	$sql = "SELECT	tbl_faturamento.faturamento ,
					tbl_fabrica.nome AS fabrica_nome ,
					tbl_faturamento.nota_fiscal ,
					to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
					TO_CHAR (tbl_faturamento.saida,'DD/MM/YYYY') AS saida, 
					to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') as conferencia ,
					to_char (tbl_faturamento.cancelada,'DD/MM/YYYY') as cancelada ,
					tbl_faturamento.cfop ,
					tbl_faturamento.serie,
					tbl_faturamento.condicao,
					tbl_faturamento.transp ,
					tbl_transportadora.nome AS transp_nome ,
					tbl_transportadora.fantasia AS transp_fantasia ,
					to_char (tbl_faturamento.total_nota,'999999.99') as total_nota
			FROM    tbl_faturamento
			JOIN    tbl_fabrica USING (fabrica)
			LEFT JOIN tbl_transportadora USING (transportadora)
			WHERE   tbl_faturamento.posto = $login_posto
			AND     tbl_faturamento.faturamento= $faturamento
			ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC ";

	$res = pg_exec ($con,$sql);

	if(pg_result_error($res))
		$erro_msg.= "<font color='#ff0000'>Erro ao consultar faturamento!</font>";
	
	if(pg_numrows($res)>0){
		$conferencia      = trim(pg_result($res,0,conferencia)) ;
		$faturamento      = trim(pg_result($res,0,faturamento)) ;
		$fabrica_nome     = trim(pg_result($res,0,fabrica_nome)) ;
		$nota_fiscal      = trim(pg_result($res,0,nota_fiscal));
		$emissao          = trim(pg_result($res,0,emissao));
		$saida			  = trim(pg_result($res,0,saida));
		$cancelada        = trim(pg_result($res,0,cancelada));
		$cfop             = trim(pg_result($res,0,cfop));
		$serie			  = trim(pg_result($res,0,serie));
		$condicao		  = trim(pg_result($res,0,condicao));
		$transp           = trim(pg_result($res,0,transp));
		$transp_nome      = trim(pg_result($res,0,transp_nome));
		$transp_fantasia  = trim(pg_result($res,0,transp_fantasia));
		$total_nota       = trim(pg_result($res,0,total_nota));
	}else{
		$faturamento="";
	}
}else{
	$nota_fiscal= trim($_POST['nota_fiscal']);

	$emissao	=$_POST["emissao"];
	$saida		=$_POST['saida']; 
	$total_nota	=$_POST['total_nota']; 
	$cfop		=$_POST['cfop'];  
	$serie		=$_POST['serie'];  
	$transp		=$_POST['transportadora'];
	$condicao	=$_POST['condicao']; 
}
	if (strlen ($transp_nome) > 0) $transp = $transp_nome;
	if (strlen ($transp_fantasia) > 0) $transp = $transp_fantasia;
	$transp = strtoupper ($transp);

	echo "<tr>";
	echo "<td align='left' nowrap><input type='hidden' name='fabrica'  value='3'  size='30'> Britania </td>\n";
	echo "<td align='left' nowrap><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' size='6'  maxlength='8' onBlur=\"exibirFat('dados','','','alterar')\"></td>\n";
	echo "<td align='left' nowrap><input type='text' name='emissao'  value='$emissao'  size='10'  maxlength='10' ></td>\n";
	echo "<td align='left' nowrap><input type='text' name='saida'  value='$saida'  size='10'  maxlength='10' ></td>\n";
	echo "<td align='left' nowrap><input type='text' name='cfop'  value='$cfop'  size='8'  maxlength='8' ></td>\n";
	echo "<td align='left' nowrap><input type='text' name='serie'  value='$serie'  size='10'  maxlength='10' ></td>\n";
	echo "</tr>";
echo "
	<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>
	<td align='center' colspan='1'>Condição</td>
	<td align='center' colspan = '3'>Transp.</td>
	<td align='center' colspan='2'>Total</td>
</tr>";
	
	echo "<tr>";
	 
	echo "<td align='left' nowrap>\n";

	$sql = "select condicao,
				   descricao 
			from tbl_condicao 
			where fabrica=3;";
	$res = @pg_exec ($con,$sql);

	echo "<SELECT NAME='condicao'>";

	echo "<option value=''>Selecionar</option>";
	for ($i=0; $i < pg_numrows($res); $i++) {
		$cond		= trim(pg_result($res,$i,condicao));
		$cond_des= strtoupper(trim(pg_result($res,$i,descricao)));
		if($cond == $condicao)
			echo "<option value='$cond' selected>$cond_des</option>\n";
		else
			echo "<option value='$cond'>$cond_des</option>\n";
	}
	echo "</SELECT>";

	echo "</td>\n";

	echo "<td align='center' colspan='3' nowrap>";
	$sql = "select transportadora, nome 
	from tbl_transportadora
	order by nome;";
	$res = @pg_exec ($con,$sql);

	echo "<SELECT NAME='transportadora'>";

	echo "<option value=''>Selecionar</option>";
	for ($i=0; $i < pg_numrows($res); $i++) {
		$transportadora_= trim(pg_result($res,$i,transportadora));
		$transp_nome	= strtoupper(trim(pg_result($res,$i,nome)));
		if($transp_nome == $transp)
			echo "<option value='$transp_nome' selected>$transp_nome</option>\n";
		else
			echo "<option value='$transp_nome'>$transp_nome</option>\n";
	}
	echo "</SELECT>";
	echo "</td>";

	$total_nota = number_format ($total_nota,2,',','.');
	
	echo "<td align='center' nowrap colspan='2'>
		<input type='text' name='total_nota'  value='$total_nota'  size='10'  maxlength='12' ></td>\n";
	echo "<td align='right' nowrap></td>\n";
			  
	echo "</tr>\n";
?>
<tr>
	<td colspan='6'>
    </td>
</tr>
<tr>
	<td colspan='6'>
		<table width='600' align='center'>
		<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>
			<td colspan='14'>Itens da Nota</td>
		</tr>
		<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>
			<td align='center'>#</td>
			<td align='center'>Peça</td>
			<?
			if(strlen($faturamento)>0)
				echo "<td align='center'>Descrição</td>";
			?>
			<td align='center'>Qtde</td>
			<td align='center'>Preço</td>
			<?
			if(strlen($faturamento)>0)
				echo "<td align='center'>Subtotal</td>";
			?>
			<td align='center'>Aliq. ICMS</td>
			<td align='center'>Aliq> IPI</td>
			<td align='center'>Pedido</td>
			<td align='center'>OS</td>
			<td align='center'>Base Icms</td>
			<td align='center'>Valor ICMS</td>
			<td align='center'>Base IPI</td>
			<td align='center'>valor IPI</td>
		</tr>
<?

//SE NAO EXISTIR FATURAMENTO ENTAO NAO MOSTRA OS ITENS DA NOTA FISCAL
if(strlen($faturamento)==0){
	for ($i = 0 ; $i < 20 ; $i++) {

		//INSERIR ITENS DA NOTA

		$referencia		=$_POST["referencia_$i"]; 

		$qtde			=$_POST["qtde_$i"];

		$preco			=$_POST["preco_$i"]; 
		
		$pedido			=$_POST["pedido_$i"]; 
		
		$sua_os			=trim($_POST["sua_os_$i"]); 
				
		$aliq_icms		=$_POST["aliq_icms_$i"]; 
		
		$aliq_ipi		=$_POST["aliq_ipi_$i"]; 
				
		$base_icms		=$_OPST["base_icms_$i"]; 
		
		$valor_icms		=$_POST["valor_icms_$i"]; 
		
		$base_ipi		=$_POST["base_ipi_$i"]; 
		
		$valor_ipi		=$_POST["valor_ipi_$i"]; 
			

		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = "#FFEECC";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap>".($i+1)."</td>\n";
		echo "<td align='right' nowrap>
			 <input type='text' class='frm' name='referencia_$i' id='referencia_$i' value='$referencia' size='10' maxlength='20'>
			</td>\n";
			 /*
 				<a href='#'>
				<img src='../imagens/lupa.gif' border='0' onclick=\"javascript: fnc_pesquisa_produto (document.nf_britania.referencia_$i, 'referencia_$i')\">
				</a>	
			 */
	/*	echo "<td align='left' nowrap>
			 <input type='text' class='frm' name='descricao_$i' id='descricao_$i' value='$descricao' size='10' maxlength='20'>
			</td>\n";
*/
			 /*<a href='#'>
				<img src='../imagens/lupa.gif' border='0' onclick=\"javascript: fnc_pesquisa_produto (document.nf_britania.descricao_$i, 'descricao_$i')\">
			  </a>	*/

	#	$preco = number_format ($preco,2,',','.');
	#	echo "<td align='right' nowrap>$preco</td>\n";
		
		if ($qtde_estoque == 0) $qtde_estoque = "";
		if ($qtde_quebrada == 0) $qtde_quebrada = "";
		echo "<td align='right' nowrap><input class='frm' type='text' name='qtde_$i'		id='qtde_$i'		value='$qtde'		size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='preco_$i'		id='preco_$i'		value='$preco'		size='5' maxlength='12' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_$i'	id='aliq_icms_$i'	value='$aliq_icms'	size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_$i'	id='aliq_ipi_$i'	value='$aliq_ipi'	size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='pedido_$i'		id='pedido_$i'		value='$pedido'		size='7' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='sua_os_$i'		id='sua_os_$i'		value='$sua_os'		size='9' maxlength='10'								 ></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_icms_$i'	id='base_icms_$i'	value='$base_icms'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_icms_$i'	id='valor_icms_$i'	value='$valor_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_ipi_$i'	id='base_ipi_$i'	value='$base_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_$i'	id='valor_ipi_$i'	value='$valor_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_britania.referencia_".($i+1).".focus();' readonly></td>\n";
		echo "</tr>\n";
	}

}else{
	$sql= "SELECT tbl_faturamento_item.faturamento, 
				tbl_faturamento_item.faturamento_item, 
				tbl_faturamento_item.peca, 
				tbl_faturamento_item.qtde, 
				tbl_faturamento_item.preco, 
				tbl_faturamento_item.pedido, 
				tbl_faturamento_item.os, 
				tbl_faturamento_item.aliq_icms, 
				tbl_faturamento_item.aliq_ipi, 
				tbl_faturamento_item.base_icms, 
				tbl_faturamento_item.valor_icms, 
				tbl_faturamento_item.base_ipi, 
				tbl_faturamento_item.valor_ipi,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_os.sua_os
			FROM tbl_faturamento_item 
			JOIN tbl_peca ON tbl_faturamento_item.peca  = tbl_peca.peca
			LEFT JOIN tbl_os	  ON tbl_faturamento_item.os	= tbl_os.os		
			WHERE faturamento= $faturamento;";

//echo "sql: $sql";
/*	$sql = "SELECT tbl_peca.peca, 
			tbl_peca.referencia, 
			tbl_peca.descricao, 
			fat.qtde,
			fat.qtde_estoque, 
			tbl_posto_estoque_localizacao.localizacao, 
			fat.qtde_quebrada
		FROM (SELECT tbl_faturamento_item.peca, 
				SUM (tbl_faturamento_item.qtde) AS qtde, 
				SUM (tbl_faturamento_item.qtde_estoque) AS qtde_estoque, 
				SUM (tbl_faturamento_item.qtde_quebrada) AS qtde_quebrada
				FROM tbl_faturamento_item
				JOIN tbl_faturamento USING (faturamento)
				WHERE tbl_faturamento.faturamento IN ($faturamento)
					AND   tbl_faturamento.posto       = $login_posto
					AND   tbl_faturamento.fabrica     = $login_fabrica
				GROUP BY tbl_faturamento_item.peca
				) fat
		JOIN tbl_peca ON fat.peca = tbl_peca.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON fat.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		ORDER BY tbl_peca.referencia";
*/


//echo "sql: $sql";
	$res = pg_exec ($con,$sql);

	$subtotal			= 0;
	$valor_total		= 0;
	$total_valor_ipi	= 0;
	$total_valor_icms	= 0;

	for ($i = 0 ; $i < pg_numrows($res); $i++) {
		$faturamento_item = trim(pg_result($res,$i,faturamento_item)) ;
		$referencia       = trim(pg_result($res,$i,referencia)) ;
		$descricao        = trim(pg_result($res,$i,descricao));
		$qtde             = trim(pg_result($res,$i,qtde));
		$preco            = trim(pg_result($res,$i,preco));
		$pedido			  = trim(pg_result($res,$i,pedido));
		$sua_os				  = trim(pg_result($res,$i,sua_os));
		$aliq_icms		  = trim(pg_result($res,$i,aliq_icms));
		$aliq_ipi		  = trim(pg_result($res,$i,aliq_ipi));
		$base_icms		  = trim(pg_result($res,$i,base_icms));
		$valor_icms		  = trim(pg_result($res,$i,valor_icms));
		$base_ipi		  = trim(pg_result($res,$i,base_ipi));
		$valor_ipi		  = trim(pg_result($res,$i,valor_ipi));

		$subtotal			= $preco * $qtde;
		$valor_total		= $valor_total  + $subtotal; 
		$total_valor_ipi	= $total_valor_ipi + $valor_ipi; 
		$total_valor_icms	= $total_valor_icms	+ $valor_icms;		
		

		$preco            = number_format ($preco,2,',','.');
		$aliq_icms		  = number_format ($aliq_icms,2,',','-.');
		$aliq_ipi		  = number_format ($aliq_ipi,2,',','.-');
		$base_icms		  = number_format ($base_icms,2,',','.');
		$valor_icms		  = number_format ($valor_icms,2,',','.');
		$base_ipi		  = number_format ($base_ipi,2,',','.');
		$valor_ipi		  = number_format ($valor_ipi,2,',','.');

		$subtotal			= number_format ($subtotal,2,',','.');

		$cor = "#F7F5F0";
		if ($i % 2 == 0) $cor = "#F1F4FA";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap>
		<input type='hidden' name='faturamento_item_$i' value='$faturamento_item'>
		<input type='hidden' name='peca_$i' value='$peca'>".($y+1)."</td>\n";
		echo "<td align='right' nowrap><input type='text' name='referencia_$i'  value='$referencia'  size='7'  maxlength='10' ></td>\n";
		echo "<td align='left' nowrap>$descricao</td>\n";
	#	$preco = number_format ($preco,2,',','.');
		if ($qtde_estoque == 0) $qtde_estoque = "";
		if ($qtde_quebrada == 0) $qtde_quebrada = "";

		echo "<td align='right' nowrap><input class='frm' type='text' name='qtde_$i'		id='qtde_$i'		value='$qtde'		size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='preco_$i'		id='preco_$i'		value='$preco'		size='5' maxlength='12' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap>$subtotal</td>\n";		
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_$i'	id='aliq_icms_$i'	value='$aliq_icms'	size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_$i'	id='aliq_ipi_$i'	value='$aliq_ipi'	size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='pedido_$i'		id='pedido_$i'		value='$pedido'		size='7' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='sua_os_$i'		id='sua_os_$i'		value='$sua_os'		size='9' maxlength='10'								 ></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_icms_$i'	id='base_icms_$i'	value='$base_icms'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='alert();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_icms_$i'	id='valor_icms_$i'	value='$valor_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_ipi_$i'	id='base_ipi_$i'	value='$base_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_$i'	id='valor_ipi_$i'	value='$valor_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
	
	echo "</tr>\n";
	}

	$valor_total		= number_format ($valor_total,2,',','.');
	$total_valor_icms	= number_format ($total_valor_icms,2,',','.');
	$total_valor_ipi	= number_format ($total_valor_ipi,2,',','.');
	
		echo "<tr bgcolor='#d1d4eA' style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap colspan= '5'> Totais</td>\n";

		echo "<td align='right' nowrap>$valor_total</td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap>$total_valor_icms</td>\n";
		echo "<td align='right' nowrap></td>\n";
		echo "<td align='right' nowrap>$total_valor_ipi</td>\n";
		echo "</tr>\n";
		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap colspan='14'><b>Valor Total da Nota:$valor_total</b></td>\n";
		echo "</tr>\n";

	if($valor_total == $total_nota){
	}else{
			echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
			echo "<td align='right' nowrap colspan='14'><font color='red'><b>Valor Total da Nota:$valor_total está diferente de Total cadatrado:$total_nota</b></font></td>\n";
			echo "</tr>\n";
	}

}
echo "</table>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";
echo "</td>";
echo "</tr>";

if(strlen($faturamento)>0)
	$desc_bt="Alterar";
else	
	$desc_bt="Gravar";	

echo "<tr>";
echo "<td colspan='12' align='center'>";
echo "<input type='submit' name='btn_acao' value='$desc_bt'>";
echo "</td>";
echo "</tr>";


echo "</table>\n";
echo "</form>";
?>

<p>

<? #include "rodape.php"; ?>

</body>
</html>