<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);
//include 'autentica_usuario_empresa.php';
//include 'menu.php';


$key        = $_GET['key'];
$fornecedor = $_GET['fornecedor'];
$cotacao    = $_GET['cotacao'];

$cotacao_fornecedor= $_GET["cotacao_fornecedor"];

if(strlen($cotacao_fornecedor)==0){
	$cotacao_fornecedor= $_POST["cotacao_fornecedor"];
}

$cook_key        = trim($HTTP_COOKIE_VARS['cook_temp_key']);
$cook_fornecedor = trim($HTTP_COOKIE_VARS['cook_temp_fornecedor']);
$cook_cotacao    = trim($HTTP_COOKIE_VARS['cook_temp_cotacao']);


if (strlen($cook_key)>0){
	$cotacao    = $cook_cotacao;
	$fornecedor = $cook_fornecedor;
	$key        = $cook_key;
}


if (strlen($key)>0){
	$sql = "SELECT	tbl_cotacao_fornecedor.pessoa_fornecedor,
					tbl_cotacao_fornecedor.cotacao,
					tbl_cotacao_fornecedor.cotacao_fornecedor,
					tbl_cotacao.empresa,
					tbl_fabrica.nome AS nome_empresa,
					tbl_pessoa.nome,
					tbl_pessoa.cnpj,
					tbl_pessoa.email
			FROM tbl_cotacao
			JOIN tbl_cotacao_fornecedor USING(cotacao)
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_cotacao.empresa
			JOIN tbl_pessoa ON tbl_pessoa.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
			WHERE tbl_cotacao_fornecedor.cotacao         = $cotacao
			AND tbl_cotacao_fornecedor.pessoa_fornecedor = $fornecedor";
	//echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 1) {
		$fornecedor   = pg_result ($res,0,pessoa_fornecedor);
		$cotacao      = pg_result ($res,0,cotacao);
		$cotacao_fornecedor  = pg_result ($res,0,cotacao_fornecedor);
		$empresa      = pg_result ($res,0,empresa);
		$nome_empresa = pg_result ($res,0,nome_empresa);

		$nome_nome    = pg_result ($res,0,nome);
		$nome_cnpj    = pg_result ($res,0,cnpj);
		$nome_email   = pg_result ($res,0,email);

		$login_fornecedor_nome  = $nome_nome;
		$login_fornecedor_cnpj  = $nome_cnpj;
		$login_fornecedor_email = $nome_email;

		$resulta_md5 = md5($fornecedor.$cotacao.$empresa);
		if ($resulta_md5 == $key){
			
			$login_empresa      = $empresa;
			$login_loja         = $loja;
			$login_fornecedor   = $fornecedor;
			$login_empresa_nome = $nome_empresa;

			$pessoa             = $fornecedor;
			$cotacao            = $cotacao;
			$cotacao_fornecedor = $cotacao_fornecedor;

			// setcookie ("cook_temp_empresa"   ,$login_empresa);
			// setcookie ("cook_temp_fornecedor",$login_fornecedor );
			// setcookie ("cook_temp_pessoa"    ,$login_fornecedor);
			// setcookie ("cook_temp_loja"      ,$loja);
			// setcookie ("cook_temp_key"       ,$key);
			// setcookie ("cook_temp_cotacao"   ,$cotacao);

			add_cookie($cookie_login,"cook_temp_empresa"   ,$login_empresa);
			add_cookie($cookie_login,"cook_temp_fornecedor",$login_fornecedor );
			add_cookie($cookie_login,"cook_temp_pessoa"    ,$login_fornecedor);
			add_cookie($cookie_login,"cook_temp_loja"      ,$loja);
			add_cookie($cookie_login,"cook_temp_key"       ,$key);
			add_cookie($cookie_login,"cook_temp_cotacao"   ,$cotacao);
			
			set_cookie_login($token_cookie,$cookie_login);

			include "menu_fornecedor.php";

		}else{
			echo "<h1> Cotação não existe. Chave incorreta.<h1>";
			exit;
		}
	}else{
		echo "<h1> Cotação não existe.<h1>";
		exit;
	}
}else{
	include 'autentica_usuario_empresa.php';
}



function formata_float_entrada($f, $casa_decimal){
	$f = str_replace( '.', '', $f);
	$f = str_replace( ',', '.', $f);
	$f = number_format($f, 2, '.', '');
	$f = trim(str_replace(".00","",$f));
	return($f);
}


if(strlen($_GET["nova"])>0){
	$fornecedor = $_GET["fornecedor"];
	$cotacao	= $_GET["cotacao"];
	if(strlen($fornecedor)>0 and strlen($cotacao)>0){
		$sql= "
			SELECT * 
			FROM tbl_cotacao_fornecedor 
			WHERE cotacao = $cotacao 
				AND pessoa_fornecedor = $fornecedor";

		$res= pg_exec($con, $sql);
		if(pg_numrows($res)>0){
			$cotacao_fornecedor= trim(pg_result($res,0,cotacao_fornecedor));
		}else{
			$sql="INSERT INTO tbl_cotacao_fornecedor 
					(pessoa_fornecedor, cotacao)
			  VALUES($fornecedor, $cotacao);";

			$res= pg_exec($con,$sql);

			$sql=" SELECT CURRVAL('tbl_cotacao_fornecedor_cotacao_fornecedor_seq') as cotacao_fornecedor;";
			$res= pg_exec($con, $sql);
			
			$cotacao_fornecedor= trim(pg_result($res,0,cotacao_fornecedor));

			$sql="INSERT INTO tbl_cotacao_fornecedor_item
					(
						cotacao_fornecedor, 
						quantidade, 
						peca, 
						status_item
					)
					(
					SELECT 
						$cotacao_fornecedor, 
						quantidade_comprar, 
						peca, 
						'nao cotado'
					FROM tbl_cotacao
					JOIN tbl_cotacao_item using(cotacao)
					WHERE cotacao = $cotacao
					);";
			$res= pg_exec($con, $sql);
			//echo "<FONT COLOR='#FF0000'>sql:".$sql."</FONT>";
		}
	}else{
		echo "passou no else do inserre cotação";
		if(strlen($cotacao_fornecedor)==0){
			echo "NÃO FOI POSSÍVEL ENCONTRAR A COTAÇÃO!";
			exit;
		}
	}
}

$sql= "SELECT 
		tbl_cotacao.cotacao, 
		tbl_cotacao_fornecedor.pessoa_fornecedor as fornecedor, 
		tbl_pessoa.nome, 
		to_char(tbl_cotacao.data_abertura,'dd/mm/yyyy') as data_abertura, 
		to_char(tbl_cotacao.data_fechamento,'dd/mm/yyyy') as data_fechamento, 
		tbl_cotacao_fornecedor.cotacao_fornecedor, 
		tbl_cotacao_fornecedor.status,
		tbl_cotacao_fornecedor.prazo_entrega,
		tbl_cotacao_fornecedor.observacao,
		tbl_cotacao_fornecedor.condicao_pagamento,
		tbl_cotacao_fornecedor.tipo_frete,
		tbl_cotacao_fornecedor.valor_frete,
		tbl_cotacao_fornecedor.forma_pagamento
	FROM tbl_cotacao_fornecedor
	JOIN tbl_cotacao using(cotacao)
	JOIN tbl_pessoa_fornecedor on tbl_pessoa_fornecedor.pessoa = tbl_cotacao_fornecedor.pessoa_fornecedor
	JOIN tbl_pessoa			   on tbl_pessoa.pessoa			   = tbl_pessoa_fornecedor.pessoa
	WHERE cotacao_fornecedor = $cotacao_fornecedor
	ORDER BY tbl_cotacao_fornecedor.status";

$res= pg_exec($con, $sql);

$fornecedor			= trim(pg_result($res,0,fornecedor));
$nome_fornecedor	= trim(pg_result($res,0,nome));		
$data_abertura		= trim(pg_result($res,0,data_abertura));		
$data_fechamento	= trim(pg_result($res,0,data_fechamento));		
$cotacao			= trim(pg_result($res,0,cotacao));
$status				= trim(pg_result($res,0,status));
$prazo_entrega		= trim(pg_result($res,0,prazo_entrega));
$observacao			= trim(pg_result($res,0,observacao));
$condicao_pagamento	= trim(pg_result($res,0,condicao_pagamento));
$tipo_frete			= trim(pg_result($res,0,tipo_frete));
$valor_frete		= trim(pg_result($res,0,valor_frete));
$forma_pagamento	= trim(pg_result($res,0,forma_pagamento));


if(@pg_numrows($res)==0){
//	echo "sql:".$sql;
}else{
	$erro="";
	if(strlen($_POST["botao"])>0){
		$prazo_entrega	= $_POST["prazo_entrega"];
		if(strlen($prazo_entrega)==0){
			$prazo_entrega="0";
			$erro .= "Prazo de entrega!<br>";
		}

		$observacao		= $_POST["observacao"];
		if(strlen($observacao)==0) {
			$observacao="";
		}
		$condicao_pagamento= $_POST["condicao_pagamento"];
		if(strlen($condicao_pagamento)==0) {
			$condicao_pagamento=0;
			$erro .= "Condição de pagamento!<br>";

		}
		$forma_pagamento= $_POST["forma_pagamento"];
		if(strlen($forma_pagamento)==0){
			$forma_pagamento=0;
			$erro .= "Forma de Pagamento!<br>";
		}

		$tipo_frete		= $_POST["tipo_frete"];
		$valor_frete	= $_POST["valor_frete"];

		//CIF:1 (FRETE A PAGAR)
		//FOB:2 (FRETE GRATIS)
		if($tipo_frete=="FOB"){
			 if(strlen($valor_frete)>0 AND (($valor_frete) > 0) ){
				 $valor_frete= formata_float_entrada($valor_frete, 2);			 
			 }else{
				$erro		.= "Valor do Frete Vazio!<br>";
			 }
		}else{

			$valor_frete = 0;		
		}

		if($erro){
			$sql= "
					UPDATE tbl_cotacao_fornecedor
					SET		
						prazo_entrega		= $prazo_entrega		,
						observacao			= '$observacao'			,
						condicao_pagamento	= $condicao_pagamento	,
						tipo_frete			= '$tipo_frete'			,
						forma_pagamento		= $forma_pagamento		,
						status				= 'não cotada'

					WHERE cotacao_fornecedor= $cotacao_fornecedor";
		}else{
			$sql= "	UPDATE tbl_cotacao_fornecedor
					SET 
						prazo_entrega		= $prazo_entrega		,
						observacao			= '$observacao'			,
						condicao_pagamento	= $condicao_pagamento	,
						tipo_frete			= '$tipo_frete'			,
						valor_frete			= $valor_frete			,
						forma_pagamento		= $forma_pagamento		,
						status				= 'cotada'
					WHERE cotacao_fornecedor= $cotacao_fornecedor";
		}
	//	echo "sql: $sql";

		$res= pg_exec($con, $sql);		

		if(@pg_errormessage($res)){
			$erro.= "<br>Erro do banco:".@pg_errormessage($res);
		}
		if(strlen($erro)>0){
			echo "<FONT COLOR='RED'> Erro: $erro</FONT>";
		}else{
			echo "<h1 class='ok'>Salvo com Sucesso</h1>";
			echo "<h3>Sua cotação foi salvo e foi enviada para nossa empresa. Caso sua cotação ganhar, você receberá um email com a confirmação. Você poderá reajustar os preços enquanto a cotação não vencer o prazo.</h3><br><br>";
		}
	
		for($i=0; $i<count($_POST['item_cot_forn']); $i++){
			//$item			= trim(pg_result($res,0,item_cotacao_fornecedor));

			$item_cot_forn= $_POST["item_cot_forn"][$i];
			$preco_avista	= $_POST["preco_avista"][$i];
			$preco_aprazo	= $_POST["preco_aprazo"][$i];
			$codigo_barra	= $_POST["codigo_barra"][$i];
			$ipi			= $_POST["ipi"][$i];
			$icms			= $_POST["icms"][$i];

			$item_prazo_entrega= $_POST["item_prazo_entrega"][$i];
			
			// TESTA CODIGO DE BARRA
			if(strlen($codigo_barra)==0)	$codigo_barra = "";


			// TESTA PREÇO_AVISTA
			if(strlen($preco_avista)>0){
				$preco_avista = str_replace( '.', '', $preco_avista );
				$preco_avista = str_replace( ',', '.', $preco_avista );
				$preco_avista = number_format( $preco_avista, 2, '.','');
			}else
				$preco_avista = "null";
			
			if(strlen($preco_aprazo)>0){
				$preco_aprazo = str_replace( '.', '', $preco_aprazo );
				$preco_aprazo = str_replace( ',', '.', $preco_aprazo  );
				$preco_aprazo = number_format( $preco_aprazo , 2, '.','');
			}else
				$preco_aprazo = "null";
			
			if(strlen($ipi)>0){
				$ipi = str_replace( '.', '', $ipi);
				$ipi = str_replace( ',', '.', $ipi);
				$ipi = number_format( $ipi , 2, '.','');
			}else{
				$ipi = "null";
			}

			if(strlen($icms)>0){
				$icms = str_replace( '.', '', $icms);
				$icms = str_replace( ',', '.', $icms);
				$icms = number_format( $icms , 2, '.','');
			}else{
				$icms = "null";
			}

			if(($preco_avista==0)and($preco_aprazo==0)){
				$status_item="nao cotado";
			}else{
				$status_item="cotado";
			}

			// HD 6268 inserir prazo de entrega para cada item
			if(strlen($item_prazo_entrega) == 0) {
				$item_prazo_entrega=$prazo_entrega;
			}

			//INSERIR CÓDIGO DE BARRA PARA UM PRODUTO
			if(strlen($codigo_barra)>0){
				$sql= "
					SELECT *
					FROM tbl_peca_item_codigo_barra
					WHERE peca =(select peca from tbl_cotacao_fornecedor_item WHERE cotacao_fornecedor_item= $item_cot_forn ) ";

				$res= pg_exec($con, $sql);		

				if(pg_numrows($res) >0){
					//Faz update 
					$peca_item_codigo_barra = trim(pg_result($res,0,peca_item_codigo_barra));
					$sql= "	UPDATE tbl_peca_item_codigo_barra
							set codigo_barra = '$codigo_barra'
							where peca_item_codigo_barra = $peca_item_codigo_barra;";
					$res= pg_exec($con, $sql);
				}else{
					$sql= "
						SELECT peca 
						FROM tbl_cotacao_fornecedor_item 
						WHERE cotacao_fornecedor_item= $item_cot_forn";

					$res= pg_exec($con, $sql);
					$peca	= trim(pg_result($res,0,peca));

					$sql= "	INSERT INTO tbl_peca_item_codigo_barra(peca, codigo_barra) values ($peca, '$codigo_barra');";
					$res= pg_exec($con, $sql);
				
				}

			}
			

			$sql= "
				UPDATE tbl_cotacao_fornecedor_item
					SET 
						preco_avista	= $preco_avista, 
						preco_aprazo	= $preco_aprazo, 
						ipi				= $ipi,
						icms			= $icms,
						status_item		= '$status_item',
						prazo_entrega	= $item_prazo_entrega,
						observacao		= '$observacao'
				 WHERE cotacao_fornecedor_item= $item_cot_forn";

			//echo $sql; 
			$res= pg_exec($con, $sql);		
		}
			//exit;
		//$item			= trim(pg_result($res,0,item_cotacao_fornecedor));
		$preco_avista	= $_POST["preco_avista"];
	//	if(strlen($preco_avista)==0) $preco_avista="""";
		$preco_aprazo	= $_POST["preco_aprazo"];
	//	if(strlen($preco_aprazo)==0) $preco_aprazo="""";
		//$condicao_pag	= trim(pg_result($res,0,condicao));
		$prazo_entrega	= $_POST["prazo_entrega"];
	//	if(strlen($prazo_entrega)==0) $preco_aprazo="""";

		$ipi			= $_POST["ipi"];
		$icms			= $_POST["icms"];
	//	if(strlen($ipi)==0) $ipi="""";
		$status			= $_POST["status"];
	//	if(strlen($status)==0) $status="""";
		$observacao		= $_POST["observacao"];
	//	if(strlen($observacao)==0) $observacao="''";

	/*

		$sql= "UPDATE TBL_ITEM_COTACAO_FORNECEDOR
			SET PRECO_AVISTA=$preco_avista, 
				PRECO_APRAZO=$preco_aprazo, 
				PRAZO_ENTREGA = $prazo_entrega, 
				IPI= $ipi,
				STATUS_ITEM='cotado',
				OBSERVACAO= $observacao
			 WHERE ITEM_COTACAO_FORNECEDOR= $item";
		
		$res= pg_exec($con, $sql);
		//$_SESSION["'".$item."'"]=$vet;		
		*/
	}
	$vet= $_SESSION["'".$item."'"];
}
?>


<script language="JavaScript">

function fnc_pesquisa_produto (produto) {
	if (produto != "") {
		var url = "";
		url = "produto_preco.php?produto=" + produto;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
		//janela.retorno = "<? echo $PHP_SELF ?>";
	}
}

function testa_campos_nulos(prazo_entrega, condicao_pagamento){ 
	//var nome= objeto.name;
	var pe			= prazo_entrega.value;
	var cond_pag	= condicao_pagamento.value;
	//alert('pe:'+pe);
	if(((pe==0) || (pe.length ==0)) ||(cond_pag.length==0)) {
		alert('Obrigatório preencher Prazo de Entrega e Condição de Pagamento!');
	}
} 



function mostra_oculta(itemID, itemID2){

  /*
	var teste1= document.getElementById(itemID).value;
	var teste2= document.getElementById(itemID2).value;
	
	if(document.getElementById(itemID).value==document.getElementById(itemID2).value){
		document.getElementById('inp_outra_forma').style.display = 'inline';
		document.getElementById('inp_outra_forma').value= '';
		document.getElementById('inp_outra_forma').focus();
    }else{
		document.getElementById('inp_outra_forma').style.display = 'none';
	}
    */
	//alert ('teste1:'+teste1 +' teste2:'+teste2);
}

function abreBanco(pessoa,alterar){
	janela = window.open("cadastro_banco.php?pessoa=" + pessoa + "&alterar=" + alterar,"bancos",'resizable=1,scrollbars=yes,width=650,height=450,top=0,left=0');
	janela.focus();
}


</script>

<style type="text/css">
.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}
.titulo {
	font-family: Arial;
	font-size: 10px;
	color: #000000;
	background: #ced7e7;
}
</style>

<table class='table_line' width='700' border='1' cellpadding="10" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
<FORM NAME ='forn_cot' ACTION='fornecedor_cotacao.php' METHOD='POST'>
  <tr bgcolor='#596D9B'>
	<td nowrap colspan='4' class='menu_top' align='left' background='imagens/azul.gif'>
	  <font size='3'>Cotação de Produto</font>
  	  <?
	  echo "<input type='hidden' name='cotacao_fornecedor' value='$cotacao_fornecedor'>";
	  ?>
	</td>
  </tr>
  <tr>
	<td nowrap colspan='4' bgcolor='#ced7e7' align='left'>
	  <font size='2'>COTAÇÃO Nº <b><?echo $cotacao;?></b></font> | 
  	  <?echo "Fornecedor:<b>".substr($nome_fornecedor,0,25)."</b> | ";?>
	  <font size='1'>Data Abertura: <b><?echo $data_abertura;?> </b> | Data Fechamento: <b><?echo $data_fechamento;?></b>
	</td>
  </tr>
  <tr bgcolor='#fafafa'>
	<td nowrap colspan='1' align='center'>Prazo de entrega<br>
		<input type='text' id='prazo_entrega' name='prazo_entrega' value='<?echo $prazo_entrega;?>' size=15 maxlength=30> dias
	</td>
	<td nowrap align='center'>Condição de Pag.<br>
<?
	$sql= "SELECT 
				CONDICAO, 
				parcelas
		   FROM TBL_CONDICAO
		   WHERE fabrica = $login_empresa
		   ORDER BY DESCRICAO";
	$res_sel= pg_exec($con, $sql);

	if(@pg_numrows($res_sel)>0){
		echo "<select id='cond_pg' name='condicao_pagamento' onChange=\"mostra_oculta('outra_forma', 'cond_pg');\"> >";
		echo "<option value=''>Selecionar";
		for ( $i = 0 ; $i < @pg_numrows ($res_sel) ; $i++ ) {
			$selected="";
			$cod_condicao_pagamento	= trim(pg_result($res_sel,$i,condicao));	
			$descricao				= str_replace("|","/",trim(pg_result($res_sel,$i,parcelas)));	
			if($descricao=="Outra Forma") $outra_forma= $cod_condicao_pagamento;

			if($condicao_pagamento==$cod_condicao_pagamento)
				$selected= "selected";
			echo "<option value='$cod_condicao_pagamento' $selected>$descricao";
		}
		echo "</select>";
		echo "<input type='hidden' id='outra_forma' value='$outra_forma'> ";
		
		echo "&nbsp;<input type='text' id='inp_outra_forma' name='outra_forma' value='' size=15 style='display:none;'>";		
	}
?>
	</td>
	<td align='center'>Forma de Pag.<br>
<?
	if($forma_pagamento==1){
		$f_pag1="selected";
		$f_pag2="";
	}else{
		$f_pag1="";
		$f_pag2="selected";	
	}
	echo "<select name='forma_pagamento'>
			<option value='1' $f_pag1>Boleto Bancário
			<option value='2' $f_pag2>Depósito em Conta
		  </select>";
?>
	</td>
	<td align='center'>
	<acronym 
title='
CIF: Frete Pagar. Custo, seguro e frete. Quando a mercadoria é colocada pelo exportador, livre de despesas, no porto de destino, incluindo o custo do seguro!
FOB: Frete Gratis. A mercadoria deve ser colocada pelo exportador/vendedor, livre de despesas, a bordo do navio. A partir daí as despesas são por conta do importador/comprador.'>Tipo de Frete</acronym>	
	<br>
<?
	//1 FRETE A PAGAR
	//2 FRETE GRATIS
	if($tipo_frete=="CIF"){
		$frete1="selected";
		$frete2="";
		$mostra_frete ="none";
	}else{
		$mostra_frete ="inline";
		$frete1="";
		$frete2="selected";	
	}
	echo "<select name='tipo_frete' onChange=\"
	var tip_f = document.getElementById('tipo_frete').value;
	if(tip_f =='FOB'){		
		document.getElementById('valor_frete').style.display = 'inline';
    }else{
		document.getElementById('valor_frete').style.display =  'none';
	};\" >
			<option value='CIF' $frete1>CIF</option>
			<option value='FOB' $frete2>FOB</option>
		  </select>";
?>
	</td>
  </tr>
  <tr bgcolor='#fafafa'>
	<td nowrap colspan='3' align='center'>Observação<br>
		<input type='text' name='observacao' value='<?echo $observacao;?>' size='100' maxlength='150'>
	</td>
	<td nowrap colspan='1' align='center'>Valor do Frete<br>
		<input type='text' style='display:<?echo $mostra_frete;?>' name='valor_frete' id='valor_frete' value='<?echo $valor_frete;?>' size='8' maxlength='14'>
	</td>
  </tr>
  <tr bgcolor='#fafafa'>
	
	<?
	$sql="SELECT tbl_pessoa_banco.pessoa_banco ,
				 tbl_pessoa_banco.banco        ,
				 tbl_pessoa_banco.nome         ,
				 tbl_pessoa_banco.agencia      ,
				 tbl_pessoa_banco.conta        ,
				 tbl_pessoa_banco.tipo_conta   ,
				 tbl_pessoa_fornecedor.pessoa
			FROM tbl_pessoa_banco
			JOIN tbl_pessoa_fornecedor ON tbl_pessoa_fornecedor.pessoa_banco = tbl_pessoa_banco.pessoa_banco
			LEFT JOIN tbl_cotacao_fornecedor ON tbl_cotacao_fornecedor.pessoa_fornecedor = tbl_pessoa_fornecedor.pessoa
			WHERE cotacao_fornecedor=$cotacao_fornecedor 
			AND tbl_pessoa_banco.pessoa_banco=tbl_pessoa_fornecedor.pessoa_banco ";
	
	//echo nl2br($sql); exit;
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) > 0) {
		$banco      =trim(pg_result($res,0,banco));
		$nome       =trim(pg_result($res,0,nome));
		$agencia    =trim(pg_result($res,0,agencia));
		$conta      =trim(pg_result($res,0,conta));
		$tipo_conta =trim(pg_result($res,0,tipo_conta));
		$pessoa     =trim(pg_result($res,0,pessoa));
	} else {
		$pessoa=$fornecedor;
	}
	

	echo "<td nowrap align='left' colspan='4'><b>Conta a ser depositada</b><br>Banco: $nome Agência: $agencia Conta: $conta Tipo Conta: $tipo_conta &nbsp; <a href=\"javascript: abreBanco('$pessoa','alterar')\" rel='ajuda' title='Clique aqui para selecionar outra conta'>( Alterar )</a></td>";
?>
  </tr>
  <tr>
  <td colspan='4'>&nbsp;</td>
  </tr>
  <tr>
    <td colspan='4'>

  <table width='100%' align='left' class='table_line'>
	<tr >	
	  <td nowrap class='titulo' width='5%' align='center'><b>#</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>+</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Cód.</td>
	  <td nowrap class='titulo' width='70%' align='center'><b>Itens da Cotação</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Cód Barra</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Quantidade</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Menor Lance</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Vlr Vista sem IPI</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Vlr Prazo sem IPI</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>% IPI</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Vlr IPI</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>% ICMS</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Prazo de Entrega</td>
	  <td nowrap class='titulo' width='5%' align='center'><b>Status</td>
	</tr>

<?

$sql= "SELECT 			
		REPLACE(CAST(CAST(MIN(PRECO_AVISTA) AS NUMERIC(12,2)) AS VARCHAR(14)),'.', ',') AS MENOR,
		peca
	FROM tbl_cotacao_fornecedor
	JOIN tbl_cotacao_fornecedor_item USING(cotacao_fornecedor)
	WHERE cotacao=$cotacao AND (preco_avista >0 AND preco_avista IS NOT NULL)
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


$sql= "
SELECT  tbl_cotacao_fornecedor_item.cotacao_fornecedor_item, 
		tbl_cotacao_fornecedor_item.cotacao_fornecedor, 	
		tbl_cotacao_fornecedor_item.quantidade, 	
		tbl_cotacao_fornecedor_item.status_item, 
		tbl_cotacao_fornecedor_item.preco_avista, 
		tbl_cotacao_fornecedor_item.preco_aprazo, 
		tbl_cotacao_fornecedor_item.ipi ,
		tbl_cotacao_fornecedor_item.icms,
		tbl_cotacao_fornecedor_item.peca,
		tbl_cotacao_fornecedor_item.prazo_entrega as item_prazo_entrega,
		tbl_peca_item_codigo_barra.codigo_barra,
		tbl_peca.descricao
FROM tbl_cotacao_fornecedor_item 
JOIN tbl_peca				ON tbl_cotacao_fornecedor_item.peca = tbl_peca.peca
JOIN tbl_peca_item			ON tbl_peca.peca = tbl_peca_item.peca
LEFT JOIN tbl_peca_item_codigo_barra ON tbl_peca_item_codigo_barra.peca = tbl_peca_item.peca 
WHERE tbl_cotacao_fornecedor_item.cotacao_fornecedor = $cotacao_fornecedor

AND (linha in 
( 
	select distinct linha
	from tbl_fornecedor_linha 
	where pessoa_fornecedor = $fornecedor
	and linha in
	( 
		select distinct linha
		from tbl_peca_item
		where 
		peca in
		( 
			select peca 
			from tbl_cotacao_item 
			where cotacao = $cotacao 
			and status = 'a comprar' 
		) 
	) 
) 
or familia in 
( 
	select distinct familia
	from tbl_fornecedor_familia 
	where pessoa_fornecedor = $fornecedor
	and familia in
	( 
		select distinct familia
		from tbl_peca_item
		where peca in
		( 
			select peca
			from tbl_cotacao_item 
			where cotacao = $cotacao
			and status = 'a comprar' 
		) 
	) 
) 
or tbl_peca_item.modelo in 
( 
	select distinct modelo
	from tbl_fornecedor_modelo 
	where pessoa_fornecedor = $fornecedor
	and modelo in
	( 
		select distinct modelo
		from tbl_peca_item 
		where peca in
		( 
			select peca
			from tbl_cotacao_item 
			where cotacao = $cotacao
			and status = 'a comprar' 
		) 
	) 
) 
or tbl_peca_item.marca in 
(  
	select distinct marca
	from tbl_fornecedor_marca 
	where pessoa_fornecedor = $fornecedor
	and marca in
	( 
		select distinct marca
		from tbl_peca_item
		where peca in
		( 
			select peca
			from tbl_cotacao_item 
			where cotacao = $cotacao
			and status = 'a comprar' 
		) 
	) 
))
ORDER BY STATUS_ITEM DESC, descricao";
		
//echo $sql;	
	/*TBL_COTACAO_FORNECEDOR.COTACAO_FORNECEDOR= $cotacao_fornecedor AND TBL_ITEM_COTACAO.STATUS = 'a comprar'
	ORDER BY STATUS_ITEM DESC, NOME";
*/
//echo nl2br($sql); exit;
$res= pg_exec($con, $sql);

if(pg_numrows ($res)>0){

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$item				=trim(pg_result($res,$i,cotacao_fornecedor_item));
		$nome_produto		=trim(pg_result($res,$i,descricao));
		$peca				=trim(pg_result($res,$i,peca));
		$codigo_barra		=trim(pg_result($res,$i,codigo_barra));
		$quantidade			=trim(pg_result($res,$i,quantidade));
		$status				=trim(pg_result($res,$i,status_item));
		$preco_avista		=trim(pg_result($res,$i,preco_avista));
		$preco_aprazo		=trim(pg_result($res,$i,preco_aprazo));
		$ipi				=trim(pg_result($res,$i,ipi));
		$icms				=trim(pg_result($res,$i,icms));
		$item_prazo_entrega	=trim(pg_result($res,$i,item_prazo_entrega));

		$vlr_ipi = ($preco_avista*$ipi);
		if(strlen($vlr_ipi)>0){
			$vlr_ipi = number_format(($vlr_ipi /100),2,',', '');
		}
		if ($cor=="#fafafa")	$cor="#eeeeff";
		else					$cor="#fafafa";

		echo "<tr bgcolor='$cor'>";;
		echo "<td nowrap align='center'>".($i+1)." </td>";
		echo "<td nowrap align='center'><img src=\"imagens/mais.gif\" border='0'  style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_produto($peca)\"></td>";
		echo "<td nowrap align='center'>$peca</td>";
		echo "<td nowrap align='left'> $nome_produto</td>";
		echo "<td><input type='text' size='10' maxlength='50' name='codigo_barra[]' value='$codigo_barra'></td>";
		echo "<td nowrap align='center' >$quantidade</td>";
		echo "<td nowrap align='center' >".$preco_menor[$peca]."</td>";
		echo "<td nowrap align='center' >";
		echo "<input type='text' size='4' maxlength='15' name='preco_avista[]' value='$preco_avista' onKeyDown='formataValor(this,13,event);' >";			
		echo "</td>";	  
		echo "<td nowrap align='center' >";
		echo "<input type='text' size='4' maxlength='15' name='preco_aprazo[]' value='$preco_aprazo' onKeyDown='formataValor(this,13,event);'>";
		echo "</td>";	  
		echo "<td nowrap align='center' >";
		echo "<input type='text' size='4' maxlength='15' name='ipi[]' value='$ipi' onKeyDown='formataValor(this,13,event);'>";
		echo "<input type='hidden' name='item_cot_forn[]' value='$item'>";
		echo "</td>";	  
		echo "<td nowrap align='left'> $vlr_ipi</td>";
		echo "<td nowrap align='center' >";
		echo "<input type='text' size='4' maxlength='15' name='icms[]' value='$icms' onKeyDown='formataValor(this,13,event);'>";
		echo "</td>";
		echo "<td nowrap align='center' >";
		echo "<input type='text' size='1' maxlength='4' name='item_prazo_entrega[]' value='$item_prazo_entrega' onKeyDown='formataValor(this,13,event);' >";
		echo "</td>";

		echo "<td nowrap align='center' >";
		if($status=="cotado")
		  echo "<font color='#0000ff'>$status</font>";	 
		else
		  echo "<font color='#ff0000'>não cotado</font>";	 
		echo "</td>";	  
		echo "</tr>";
	}
}else{
	echo "<tr>";
	echo "<td nowrap colspan='10' align='center'>";
	echo "<font color='#ff0000'>Cotação Finalizada!</font>";	 
	echo "</td>";	  
	echo "</tr>";
}
?>
</table>
  </td>
  </tr>
  <tr >	
    <td colspan='4' nowrap align='right'>
		<input type='submit' name='botao' value='Concluir Cotação'>
	</td>
  </tr>
  </FORM>
</table>
<br>
<br>
<br>
</body>
</html>
