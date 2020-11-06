<?
//OBS: ESTE ARQUIVOS UTILIZA AJAX: nf_saida_ret_ajax.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$faturamento	= $_POST["faturamento"];
if(strlen($faturamento)==0) $faturamento = $_GET["faturamento"];

$btn_acao = $_POST["btn_acao"];
if(strlen($btn_acao)==0) $btn_acao = $_GET["btn_acao"];

$alterar_faturamento= $_GET["alterar_faturamento"];

$erro_msg_		= $_GET["erro_msg"];

//SE NAO FOR O POSTO DE TESTE OU O DISTRIB.
if(($login_posto <> 6359) and ($login_posto <> 4311)){
	echo "NÃO É PERMITIDO LANÇAR NOTA FISCAL - longin: $login_posto";
	exit;
}

$faturamento_fabrica= trim($_GET['faturamento_fabrica']) ;
$nf                 = trim($_GET['nf']);
$posto_codigo       = trim($_GET["posto_codigo"]);
if(strlen($nf)>0 and strlen($faturamento_fabrica)>0 AND strlen($posto_codigo)>0) {

	$sql = "SELECT 
					posto
			FROM  tbl_posto 
			WHERE cnpj= '$posto_codigo'";
	$res = @pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		$posto = trim(pg_result($res,0,posto));

		$sql = "SELECT 
						faturamento
				FROM  tbl_faturamento
				WHERE fabrica = $faturamento_fabrica
					AND nota_fiscal ='$nf'
					AND posto = '$posto';";
					//echo "sql: $sql";
		$res = @pg_exec ($con,$sql);
		if(pg_numrows($res)>0){
			$faturamento= trim(pg_result($res,0,faturamento));
			header ("Location: nf_saida.php?faturamento=$faturamento");
			exit;
		}else{
			header ("Location: nf_saida.php?faturamento_fabrica=$faturamento_fabrica&posto_codigo=$posto_codigo&erro_msg=Não encontrou Nota fiscal para esse cliente.");

		}
	}else{
		echo "Cliente/Posto não encontrado!";
	}
}else{
	$faturamento_fabrica= trim($_GET['faturamento_fabrica']) ;
	$nf                 = trim($_GET['nf']);
	$posto_codigo       = trim($_GET["posto_codigo"]);
	//echo "nao encontrou nada- Fat_fabrica: $faturamento_fabrica - nf: $nf - posto_codigo: $posto_codigo";
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj,
					tbl_posto.nome
				FROM tbl_posto
				WHERE ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " tbl_posto.cnpj = '$q' ";
		}else{
			$sql .= " UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				//$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

if ($btn_acao == "Gravar") {
	#echo "passou em gravar";
	$faturamento_fabrica= trim($_POST['faturamento_fabrica']) ;
	$emissao     = trim($_POST["emissao"])    ;
	$saida       = trim($_POST['saida'])      ;
	$condicao    = trim($_POST['condicao'])   ;
	$cfop        = trim($_POST['cfop'])       ;
	$serie       = trim($_POST['serie'])      ;
	$natureza    = trim($_POST['natureza'])   ;
	$fat_base_icms   = trim($_POST['fat_base_icms']);
	$fat_valor_icms   = trim($_POST['fat_valor_icms']);
	$fat_valor_tot_ipi   = trim($_POST['fat_valor_tot_ipi']);
	$transportadora = trim($_POST['transportadora']);
	$valor_frete= trim($_POST['valor_frete']) ;
	$tipo_frete= trim($_POST['tipo_frete']) ;
	$qtde_volume= trim($_POST['qtde_volume']) ;
	$total_nota= trim($_POST['total_nota']) ;
	$obs       = trim($_POST['obs']) ;

	$posto_codigo= $_POST["posto_codigo"];
	if(strlen($faturamento_fabrica)==0){
		header ("Location: nf_saida.php?erro_msg=É necessário selecionar a fábrica");
		exit;
	}

	if(strlen($posto_codigo)>0){
		$sql = "SELECT 
						posto
				FROM  tbl_posto 
				WHERE cnpj= '$posto_codigo';";
		$res = @pg_exec ($con,$sql);
		if(pg_numrows($res)>0){
			$posto= trim(pg_result($res,0,posto));
			#echo "passou no Posto: $posto";
		}else{
			#echo "erro passou no Posto: $posto";
			header ("Location: nf_saida.php?erro_msg=É necessário selecionar o cliente.");
			exit;
		}
	}

	if(strlen($emissao)==0)     $erro_msg .= "Digite a data de emissão $emissao<br>" ;
	if(strlen($saida)==0)       $erro_msg .= "Digite a Data de Saida<br>" ;
	if(strlen($cfop)==0)        $erro_msg .= "Digite o Cfop<br>";
	if(strlen($serie)==0)       $erro_msg .= "Digite o Número da Série<br>" ;
	if(strlen($natureza)==0)    $erro_msg .= "Digite a natureza da operação<br>" ;
	if(strlen($transportadora)==0) $erro_msg .= "Selecione a Transportadora<br>" ;
	if(strlen($condicao)==0)       $erro_msg .= "Selecione a Condição<br>" ;
	if(strlen($valor_frete)==0)    $erro_msg .= "Digite o valor do Frete.<br>" ;
	if(strlen($tipo_frete)==0)     $erro_msg .= "Digite o tipo do Frete.<br>" ;
	if(strlen($qtde_volume)==0)    $erro_msg .= "Digite o Volume.<br>" ;
	if(strlen($total_nota)==0)    $erro_msg .= "Digite o Total Nota.<br>" ;
	if(strlen($fat_base_icms)==0)    $erro_msg .= "Digite a Base de ICMS.<br>" ;
	if(strlen($fat_valor_icms)==0)    $erro_msg .= "Digite o valor Total de ICMS.<br>" ;
	if(strlen($fat_valor_tot_ipi)==0)    $erro_msg .= "Digite o valor Total de IPI.<br>" ;

	$saida   =  substr ($saida,6,4) . "-" . substr ($saida,3,2) . "-" . substr ($saida,0,2) ;
	$emissao = substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2);

	$distribuidor =$login_posto;

	if(strlen($erro_msg)==0 ){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		#echo "BEGIN TRANSACTION";

		/*IGOR- Copiei do embarque_nota_fiscal.php - Para não gerar nota errada*/
		# Fabio Nowaki - 24/01/2008
		$sql = "SELECT MAX (nota_fiscal::integer) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = $login_posto AND nota_fiscal::integer < 111111 ";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$nota_fiscal = pg_result ($res,0,0);

		if (strlen ($nota_fiscal) == 0) {
			$nota_fiscal = "000000";
		}

		$nota_fiscal = $nota_fiscal + 1 ;
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);

		$sql= "INSERT INTO tbl_faturamento 
					(fabrica          ,
					emissao           ,
					conferencia       ,
					saida             ,
					distribuidor      ,
					posto             ,
					total_nota        ,
					cfop              ,
					nota_fiscal       ,
					serie             ,
					transportadora,
					natureza          ,
					valor_frete       ,
					tipo_frete        ,
					qtde_volume       ,
					condicao          ,
					base_icms         ,
					valor_icms        ,
					valor_ipi         ,
					obs
				)
			VALUES (
					$faturamento_fabrica,
					'$emissao'          ,
					CURRENT_TIMESTAMP   ,
					'$saida'            ,
					$distribuidor       ,
					$posto              ,
					$total_nota         ,
					$cfop               ,
					'$nota_fiscal'      ,
					'$serie'            ,
					$transportadora   ,
					'$natureza'         ,
					$valor_frete        ,
					$tipo_frete         ,
					$qtde_volume        ,
					$condicao           ,
					$fat_base_icms      ,
					$fat_valor_icms     ,
					$fat_valor_tot_ipi  ,
					'$obs'                
				);";
		//echo "sql: $sql<br>";
		$res = pg_exec ($con,$sql);

		if(strlen(pg_errormessage($con))>0) {
			$res = pg_exec ($con,"ROLLBACK;");
			$erro_msg.="<br>Erro ao inserir a NF:$nota_fiscal";
		}else{
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_faturamento') as fat;");
			$faturamento =trim (pg_result($res, 0 , fat));

			if(strlen($faturamento) > 0){
				for($i=0; $i< 50; $i++){
					$erro_item  = "" ;
					$referencia = $_POST["referencia_$i"];
					$qtde       = $_POST["qtde_$i"];
					$preco      = $_POST["preco_$i"]; 
					$aliq_icms  = $_POST["aliq_icms_$i"]; 
					$aliq_ipi   = $_POST["aliq_ipi_$i"]; 
					$base_icms  = $_POST["base_icms_$i"]; 
					$base_ipi   = $_POST["base_ipi_$i"]; 
					$valor_ipi  = $_POST["valor_ipi_$i"]; 
					$valor_icms = $_POST["valor_icms_$i"]; 

					if(strlen($referencia)>0){
						$sql = "SELECT  peca,
										descricao 
								FROM   tbl_peca 
								WHERE  fabrica    = $faturamento_fabrica 
								AND    referencia = '$referencia';";
						$res = pg_exec ($con,$sql);
						if(pg_numrows($res)>0){
							$peca      = trim(pg_result($res,0,peca));
							$descricao = trim(pg_result($res,0,descricao));
						}else{
							$erro_item .= "Peça não encontrada!<br>" ;
						}

						if(strlen($qtde)==0)  $erro_item.= "Digite a qtde<br>" ;
						if(strlen($preco)==0) $erro_item.= "Digite o preco<br>";

						if(strlen($aliq_icms)==0)  $aliq_icms  = "0";
						if(strlen($aliq_ipi)==0)   $aliq_ipi   = "0";
						if(strlen($base_icms)==0)  $base_icms  = "0";
						if(strlen($valor_icms)==0) $valor_icms = "0";
						if(strlen($base_ipi)==0)   $base_ipi   = "0";
						if(strlen($valor_ipi)==0)  $valor_ipi  = "0";
						$base_icms  = str_replace(",",".",$base_icms);
						$valor_icms = str_replace(",",".",$valor_icms);
						$base_ipi   = str_replace(",",".",$base_ipi);
						$valor_ipi  = str_replace(",",".",$valor_ipi);


						if(strlen($erro_item)==0){
							$sql=  "INSERT INTO tbl_faturamento_item (
										faturamento,
										peca       ,
										qtde       ,
										preco      ,
										aliq_icms  ,
										aliq_ipi   ,
										base_icms  ,
										valor_icms ,
										base_ipi   ,
										valor_ipi
									)VALUES(
										$faturamento,
										$peca       ,
										$qtde       ,
										$preco      ,
										$aliq_icms  ,
										$aliq_ipi   ,
										$base_icms  ,
										$valor_icms ,
										$base_ipi   ,
										$valor_ipi
									)";
							#echo "$sql<br>";
							$res = pg_exec ($con,$sql);	

							if(strlen(pg_errormessage($con))>0){
								#echo "<br>Erro ao inserir peça: $referencia";
								$erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia - ". pg_errormessage($con);
							}
						}else{
							$erro_msg .= $erro_item ;
							#echo "<br>Erro na linha i: $i - ERROOOOOO: $erro_item";
						}
					}
				}
			}else{
				$erro_msg .= " Não inseriu Faturamento!";
			}
			if(strlen($erro_msg)==0){
				#echo "passou em commit;";
				$res = pg_exec ($con,"COMMIT");
				#$res = pg_exec ($con,"ROLLBACK;");
			}else{
				#echo "passou em rollback";
				$res = pg_exec ($con,"ROLLBACK;");
			}
		}//else erro inserir faturamento
	}
}//FIM BTN: GRAVAR






if ($btn_acao == "Alterar") {
	echo "passou em gravar";
	$faturamento_fabrica= trim($_POST['faturamento_fabrica']) ;
	$emissao     =        trim($_POST["emissao"])    ;
	$saida       =        trim($_POST['saida'])      ;
	$condicao    =        trim($_POST['condicao'])   ;
	$cfop        =        trim($_POST['cfop'])       ;
	$serie       =        trim($_POST['serie'])      ;
	$natureza    =        trim($_POST['natureza'])   ;
	$fat_base_icms =      trim($_POST['fat_base_icms']);
	$fat_valor_icms =     trim($_POST['fat_valor_icms']);
	$fat_valor_tot_ipi=   trim($_POST['fat_valor_tot_ipi']);
	$transportadora =     trim($_POST['transportadora']);
	$valor_frete=         trim($_POST['valor_frete']) ;
	$tipo_frete=          trim($_POST['tipo_frete']) ;
	$qtde_volume=         trim($_POST['qtde_volume']) ;
	$total_nota=          trim($_POST['total_nota']) ;
	$obs       =          trim($_POST['obs']) ;

	$posto_codigo= $_POST["posto_codigo"];
	if(strlen($faturamento_fabrica)==0){
		header ("Location: nf_saida.php?erro_msg=É necessário selecionar a fábrica");
		exit;
	}

	if(strlen($posto_codigo)>0){
		$sql = "SELECT 
						posto
				FROM  tbl_posto 
				WHERE cnpj= '$posto_codigo';";
		$res = @pg_exec ($con,$sql);
		if(pg_numrows($res)>0){
			$posto= trim(pg_result($res,0,posto));
			#echo "passou no Posto: $posto";
		}else{
			#echo "erro passou no Posto: $posto";
			header ("Location: nf_saida.php?erro_msg=É necessário selecionar o cliente.");
			exit;
		}
	}

	if(strlen($emissao)==0)        $erro_msg .= "Digite a data de emissão $emissao<br>" ;
	if(strlen($saida)==0)          $erro_msg .= "Digite a Data de Saida<br>" ;
	if(strlen($cfop)==0)           $erro_msg .= "Digite o Cfop<br>";
	if(strlen($serie)==0)          $erro_msg .= "Digite o Número da Série<br>" ;
	if(strlen($natureza)==0)       $erro_msg .= "Digite a natureza da operação<br>" ;
	if(strlen($transportadora)==0) $erro_msg .= "Selecione a Transportadora<br>" ;
	if(strlen($condicao)==0)       $erro_msg .= "Selecione a Condição<br>" ;
	if(strlen($valor_frete)==0)    $erro_msg .= "Digite o valor do Frete.<br>" ;
	if(strlen($tipo_frete)==0)     $erro_msg .= "Digite o tipo do Frete.<br>" ;
	if(strlen($qtde_volume)==0)    $erro_msg .= "Digite o Volume.<br>" ;
	if(strlen($total_nota)==0)     $erro_msg .= "Digite o Total Nota.<br>" ;
	if(strlen($fat_base_icms)==0)  $erro_msg .= "Digite a Base de ICMS.<br>" ;
	if(strlen($fat_valor_icms)==0) $erro_msg .= "Digite o valor Total de ICMS.<br>" ;
	if(strlen($fat_valor_tot_ipi)==0)  $erro_msg .= "Digite o valor Total de IPI.<br>" ;

	$saida   =  substr ($saida,6,4) . "-" . substr ($saida,3,2) . "-" . substr ($saida,0,2) ;
	$emissao = substr ($emissao,6,4) . "-" . substr ($emissao,3,2) . "-" . substr ($emissao,0,2);

	$distribuidor =$login_posto;

	if(strlen($erro_msg)==0 ){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		#echo "BEGIN TRANSACTION";

		/*IGOR- Copiei do embarque_nota_fiscal.php - Para não gerar nota errada*/
		# Fabio Nowaki - 24/01/2008
/*		$sql = "SELECT MAX (nota_fiscal::integer) AS nota_fiscal FROM tbl_faturamento WHERE distribuidor = $login_posto AND nota_fiscal::integer < 111111 ";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$nota_fiscal = pg_result ($res,0,0);

		if (strlen ($nota_fiscal) == 0) {
			$nota_fiscal = "000000";
		}

		$nota_fiscal = $nota_fiscal + 1 ;
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);
*/
		$sql= "UPDATE tbl_faturamento 
					SET 
					emissao           ='$emissao', 
					conferencia       =CURRENT_TIMESTAMP   ,
					saida             ='$saida',
					total_nota        =$total_nota,
					cfop              =$cfop               ,
					serie             = '$serie',
					transportadora    =$transportadora     ,
					natureza          ='$natureza'         ,
					valor_frete       =$valor_frete        ,
					tipo_frete        =$tipo_frete         ,
					qtde_volume       =$qtde_volume        ,
					base_icms         =$fat_base_icms      ,
					valor_icms        =$fat_valor_icms     ,
					valor_ipi         =$fat_valor_tot_ipi  ,
					obs               ='$obs'              
				WHERE fabrica = $faturamento_fabrica
					and faturamento = $faturamento;";

		$res = pg_exec ($con,$sql);

		if(strlen(pg_errormessage($con))>0) {
			$erro_msg.="<br>Erro ao atualizar o faturamento." . pg_errormessage($con);
		}

		for($i=0; $i< 50; $i++){
			$erro_item  = "" ;
			$faturamento_item = $_POST["faturamento_item_$i"];
			$referencia = $_POST["referencia_$i"];
			$qtde       = $_POST["qtde_$i"];
			$preco      = $_POST["preco_$i"]; 
			$aliq_icms  = $_POST["aliq_icms_$i"]; 
			$aliq_ipi   = $_POST["aliq_ipi_$i"]; 
			$base_icms  = $_POST["base_icms_$i"]; 
			$base_ipi   = $_POST["base_ipi_$i"]; 
			$valor_ipi  = $_POST["valor_ipi_$i"]; 
			$valor_icms = $_POST["valor_icms_$i"]; 

			if(strlen($faturamento_item)>0){
				/*
				$sql = "SELECT  peca,
								descricao 
						FROM   tbl_peca 
						WHERE  fabrica    = $faturamento_fabrica 
						AND    referencia = '$referencia';";
				$res = pg_exec ($con,$sql);
				if(pg_numrows($res)>0){
					$peca      = trim(pg_result($res,0,peca));
					$descricao = trim(pg_result($res,0,descricao));
				}else{
					$erro_item .= "Peça não encontrada!<br>" ;
				}*/

				if(strlen($qtde)==0)  $erro_item.= "Digite a qtde<br>" ;
				if(strlen($preco)==0) $erro_item.= "Digite o preco<br>";

				if(strlen($aliq_icms)==0)  $aliq_icms  = "0";
				if(strlen($aliq_ipi)==0)   $aliq_ipi   = "0";
				if(strlen($base_icms)==0)  $base_icms  = "0";
				if(strlen($valor_icms)==0) $valor_icms = "0";
				if(strlen($base_ipi)==0)   $base_ipi   = "0";
				if(strlen($valor_ipi)==0)  $valor_ipi  = "0";

				$qtde= str_replace(",",".",$qtde);
				$preco= str_replace(",",".",$preco);
				$aliq_icms = str_replace(",",".",$aliq_icms );
				$aliq_ipi = str_replace(",",".",$aliq_ipi);
				$base_icms  = str_replace(",",".",$base_icms);
				$valor_icms = str_replace(",",".",$valor_icms);
				$base_ipi   = str_replace(",",".",$base_ipi);
				$valor_ipi  = str_replace(",",".",$valor_ipi);


				if(strlen($erro_item)==0){
					$sql=  "UPDATE tbl_faturamento_item 
								SET 
								qtde       =$qtde       ,
								preco      =$preco      ,
								aliq_icms  =$aliq_icms  ,
								aliq_ipi   =$aliq_ipi   ,
								base_icms  =$base_icms  ,
								valor_icms =$valor_icms ,
								base_ipi   =$base_ipi   ,
								valor_ipi  =$valor_ipi
							WHERE faturamento = $faturamento 
								AND faturamento_item= $faturamento_item;";
					echo "$sql<br>";
					$res = pg_exec ($con,$sql);	

					if(strlen(pg_errormessage($con))>0){
						echo "<br>Erro ao inserir peça: $referencia";
						$erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia - ". pg_errormessage($con);
					}
				}else{
					$erro_msg .= $erro_item ;
					#echo "<br>Erro na linha i: $i - ERROOOOOO: $erro_item";
				}
			}
		}

		if(strlen($erro_msg)==0){
			#echo "passou em commit;";
			$res = pg_exec ($con,"COMMIT");

			#$res = pg_exec ($con,"ROLLBACK;");
		}else{
			#echo "passou em rollback";
			$res = pg_exec ($con,"ROLLBACK;");
			$alterar_faturamento = "sim";
		}
	}
}//FIM BTN: ALTERAR


?>

<html>

<title>Cadastro de Nota Fiscal de Saída</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<head>

<script type="text/javascript" src="js/ajax_busca.js"></script>
<script language='javascript' src='../ajax.js'></script>
<?include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script>

	$(function(){
		$('#emissao').datePicker({startDate:'01/01/2000'});
		$('#saida').datePicker({startDate:'01/01/2000'});
		$("#emissao").maskedinput("99/99/9999");
		$("#saida").maskedinput("99/99/9999");
	});

$().ready(function() {

	function formatItem(row) {
		//alert(row);
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Descricao */
	$("input[@rel='descricao']").autocomplete("nf_cadastro_ajax.php?tipo=produto&busca=descricao&fabrica=<?=$_GET['faturamento_fabrica']?>", {
		minChars: 0,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] + " - " + row[1];
		},
		formatResult: function(row) {
			$(this).focus();
			return row[1];
		}
	});


	$("input[@rel='descricao']").result(function(event, data, formatted) {
		$("input[@name="+$(this).attr("alt")+"]").val(data[0]) ;
		$(this).focus();
	});


	/* Busca pelo Referencia */
	$("input[@rel='referencia']").autocomplete("nf_cadastro_ajax.php?tipo=produto&busca=referencia&fabrica=<?=$_GET['faturamento_fabrica']?>", {
		minChars: 0,
		delay: 0,
		width: 350,
		max:50,
		matchContains: true,
		formatItem: function(row, i, max) {
			return row[0] + " - " + row[1];
		},
		formatResult: function(row) {
			return row[0];
		}
	});

	$("input[@rel='referencia']").result(function(event, data, formatted) {
		$("input[@name="+$(this).attr("alt")+"]").val(data[1]) ;
		$(this).focus();
	});

});
</script>



<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
		var fabrica = document.getElementById('faturamento_fabrica').value;
		var posto_codigo= document.getElementById('posto_codigo').value;
		window.location="nf_saida.php?faturamento_fabrica="+fabrica+"&posto_codigo="+posto_codigo;
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
		$("#posto_codigo").val(data[0]) ;
		//alert(data[2]);
		var fabrica = document.getElementById('faturamento_fabrica').value;
		var posto_codigo= document.getElementById('posto_codigo').value;
		window.location="nf_saida.php?faturamento_fabrica="+fabrica+"&posto_codigo="+posto_codigo;
	});

});
</script>

<script language="JavaScript">
//FUNÇÃO PARA CARREGAR FATURAMENTO
function retornaFat(http,componente) {
	var com = document.getElementById('f2');
	if (http.readyState == 1) {
		com.style.display    ='inline';
		com.style.visibility = "visible"
		com.innerHTML        = "&nbsp;&nbsp;<font color='#333333'>Consultando...</font>";
	}
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					com.innerHTML = results[1];
					setTimeout('esconde_carregar()',3000);
				}else{
					com.innerHTML = "&nbsp;&nbsp;<font color='#0000ff'>Sem faturamentos para esse fornecedor</font>";
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}


//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO DE CADA FORNECEDOR
function calc_base_icms(i){
	var base=0.0, aliq_icms=0.0, valor_icms=0.0, aliq_ipi=0.0, valor_ipi=0.0;;
	preco		= document.getElementById('preco_'+i).value;
	qtde		= document.getElementById('qtde_'+i).value;
	aliq_icms	= document.getElementById('aliq_icms_'+i).value;
	aliq_ipi	= document.getElementById('aliq_ipi_'+i).value;

/*
	preco		= preco.toString().replace( ".", "" );
	qtde		= qtde.toString().replace( ".", "" );
	aliq_icms	= aliq_icms.toString().replace( ".", "" );
	aliq_ipi	= aliq_ipi.toString().replace( ".", "" );
*/
	preco       = preco.toString().replace( ",", "." );
	qtde        = qtde.toString().replace( ",", "." );
	aliq_icms   = aliq_icms.toString().replace( ",", "." );
	aliq_ipi    = aliq_ipi.toString().replace( ",", "." );

	preco       = parseFloat(preco);
	qtde        = parseFloat(qtde);
	aliq_icms   = parseFloat(aliq_icms);
	aliq_ipi    = parseFloat(aliq_ipi);

	base        = parseFloat(preco * qtde);
	base        = base.toFixed(2);
	valor_icms  = ((base * aliq_icms)/100);
	valor_icms  = valor_icms.toFixed(2);
	valor_ipi   = ((base *  aliq_ipi)/100);
	valor_ipi   = valor_ipi.toFixed(2);

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

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
	}
}

function enviarCliente() {
	var fabrica = document.getElementById('faturamento_fabrica').value;
	var posto_codigo= document.getElementById('posto_codigo').value;
	window.location="nf_saida.php?faturamento_fabrica="+fabrica+"&posto_codigo="+posto_codigo;
	//alert("passou"+fabrica + "posto: "+posto_codigo);
}

function consultaNota() {
	var fabrica = document.getElementById('faturamento_fabrica').value;
	var nota_fiscal= document.getElementById('nf').value;
	var posto_codigo= document.getElementById('posto_codigo').value;
	window.location="nf_saida.php?faturamento_fabrica="+fabrica+"&nf="+nota_fiscal+"&posto_codigo="+posto_codigo;
	//alert("passou"+fabrica + "posto: "+posto_codigo);
}

function imprimeNota() {
	var fabrica = document.getElementById('faturamento_fabrica').value;
	var nota_fiscal= document.getElementById('nf').value;
	window.location="nf_saida_imprime.php?faturamento_fabrica="+fabrica+"&nota_fiscal="+nota_fiscal;
}


function fnc_cadastra_cliente () {
	var url = "nf_saida_cadastro_posto.php";
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=750,height=450,top=18,left=0");
	janela.focus();
}


</script>
<style type="text/css">
.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}
.Carregar{
	background-color:#ffffff;
	filter: alpha(opacity=90);
	opacity: .90 ;
	width:350px;
	border-color:#cccccc;
	border:1px solid #bbbbbb;
	display:none; 

	position:absolute;
}
</style>
</head>

<body>

<? include 'menu.php';?>



<center><h1>Cadastro de Nota Fiscal de saída<? if(strlen($nota_fiscal)>0) echo " <BR> NF: ".$nota_fiscal ;?></h1></center>
	
<p>
<form name='nf_saida' method="POST" action='<? echo $PHP_SELF?>'>
<table width='700' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0'>
<tr>
<td colspan = '6' align='right' bgcolor='red'>
<?
$erro_msg_= $erro_msg;
echo "<center>" . $erro_msg_."</center>";
?>
</td>
</tr>
<tr>
	<td colspan = '3' align='left'><b>Dados da Nota Fiscal</b></td>
	<td colspan = '3' align='right'><a href='<?echo $PHP_SELF ;?>'>Nova Nota Fiscal</a></td>
	</tr>

<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold' >
	<td align='center' colspan='3'>Fábrica</td>
</tr>
<?
if(strlen($faturamento) > 0 ){
	$sql = "SELECT	tbl_faturamento.faturamento                                          ,
					tbl_fabrica.fabrica                                                  ,
					tbl_fabrica.nome                                   AS fabrica_nome   ,
					tbl_faturamento.nota_fiscal                                          ,
					TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')     AS emissao        ,
					TO_CHAR (tbl_faturamento.saida,'DD/MM/YYYY')       AS saida          ,
					TO_CHAR (tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia    ,
					TO_CHAR (tbl_faturamento.cancelada,'DD/MM/YYYY')   AS cancelada      ,
					tbl_faturamento.cfop                                                 ,
					tbl_faturamento.serie                                                ,
					tbl_faturamento.condicao                                             ,
					tbl_faturamento.transportadora                                            ,
					tbl_faturamento.obs                                                       ,
					tbl_transportadora.nome                            AS transp_nome    ,
					tbl_transportadora.fantasia                        AS transp_fantasia,
					tbl_faturamento.base_icms         ,
					tbl_faturamento.valor_icms        ,
					tbl_faturamento.valor_ipi         ,
					tbl_faturamento.total_nota        , 
					tbl_faturamento.valor_frete       ,
					tbl_faturamento.tipo_frete        ,
					tbl_faturamento.qtde_volume       ,
					tbl_faturamento.natureza          ,
					tbl_posto.cnpj                    ,
					tbl_posto.nome
			FROM      tbl_faturamento
			JOIN      tbl_fabrica        USING (fabrica)
			JOIN      tbl_posto on tbl_faturamento.posto = tbl_posto.posto
			LEFT JOIN tbl_transportadora USING (transportadora)
			
			WHERE   tbl_faturamento.faturamento = $faturamento
			ORDER BY tbl_faturamento.emissao     DESC,
					 tbl_faturamento.nota_fiscal DESC";
	$res = pg_exec ($con,$sql);
	//echo "sql: $sql";
	if(pg_numrows($res)>0){
		$conferencia      = trim(pg_result($res,0,conferencia)) ;
		$faturamento      = trim(pg_result($res,0,faturamento)) ;
		$faturamento_fabrica= trim(pg_result($res,0,fabrica)) ;
		$fabrica_nome     = trim(pg_result($res,0,fabrica_nome)) ;
		$nota_fiscal      = trim(pg_result($res,0,nota_fiscal));
		$emissao          = trim(pg_result($res,0,emissao));
		$saida            = trim(pg_result($res,0,saida));
		$cancelada        = trim(pg_result($res,0,cancelada));
		$cfop             = trim(pg_result($res,0,cfop));
		$serie            = trim(pg_result($res,0,serie));
		$condicao         = trim(pg_result($res,0,condicao));
		$transportadora   = trim(pg_result($res,0,transportadora));
		$transp_nome      = trim(pg_result($res,0,transp_nome));
		$transp_fantasia  = trim(pg_result($res,0,transp_fantasia));
		$fat_base_icms   = trim(pg_result($res,0,base_icms));
		$fat_valor_icms   = trim(pg_result($res,0,valor_icms));
		$fat_valor_tot_ipi   = trim(pg_result($res,0,valor_ipi));
		$total_nota       = trim(pg_result($res,0,total_nota));
		$valor_frete       = trim(pg_result($res,0,valor_frete));
		$tipo_frete       = trim(pg_result($res,0,tipo_frete));
		$qtde_volume       = trim(pg_result($res,0,qtde_volume));
		$natureza          = trim(pg_result($res,0,natureza));
		$nome              = trim(pg_result($res,0,nome));
		$cnpj              = trim(pg_result($res,0,cnpj));
		$posto_codigo = $cnpj;
		$obs                = trim(pg_result($res,0,obs));
	}else{
		$faturamento="";
	}
}else{
	$nota_fiscal = trim($_POST['nota_fiscal']);
	$emissao     = $_POST["emissao"]          ;
	$saida       = $_POST['saida']            ;
	$total_nota  = $_POST['total_nota']       ;
	$cfop        = $_POST['cfop']             ;
	$serie       = $_POST['serie']            ;
	$transportadora= $_POST['transportadora'];
	$condicao    = $_POST['condicao']        ;
	$valor_frete    = $_POST['valor_frete']  ;
	$tipo_frete    = $_POST['tipo_frete']    ;
	$qtde_volume    = $_POST['qtde_volume']  ;
	$fat_base_icms    = $_POST['fat_base_icms']  ;
	$fat_valor_icms   = $_POST['fat_valor_icms']  ;
	$fat_valor_tot_ipi= $_POST['fat_valor_tot_ipi']  ;
	$posto_codigo= $_POST['posto_codigo']  ;
	if(strlen($posto_codigo)==0){
		$posto_codigo= $_GET['posto_codigo']  ;
	}
	$obs         = $_POST['obs']  ;
}

echo "<tr>";
echo "<td align='left' nowrap colspan='3'>";
echo "<select style='width:200px;' name='faturamento_fabrica' id='faturamento_fabrica' class='frm'";
if(strlen($faturamento_fabrica)==0) $faturamento_fabrica = $_GET["faturamento_fabrica"];
if(strlen($faturamento_fabrica)>0) echo " disabled ";
else echo "onChange='window.location=\"$PHP_SELF?faturamento_fabrica=\"+this.value'";
echo ">";
echo "<option value=''>Selecionar</option>";
	$sql = "SELECT fabrica,nome FROM tbl_fabrica ORDER BY nome";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($x = 0; $x < pg_numrows($res);$x++) {
			$aux_fabrica = pg_result($res,$x,fabrica);
			$aux_nome    = pg_result($res,$x,nome);
			echo "<option value='$aux_fabrica'" ;if($faturamento_fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
		}
	}
echo "</select>";
if(strlen($faturamento_fabrica)>0) echo "<input type='hidden' name='faturamento_fabrica' value='$faturamento_fabrica'>";
echo "</td>\n";
echo "</tr>";


if(strlen($posto_codigo)>0){
	$sql = "SELECT 
					posto              ,
					nome               ,
					cnpj               ,
					endereco           ,
					numero             ,
					complemento        ,
					cep                ,
					cidade             ,
					estado             ,
					email              ,
					fone               ,
					contato            ,
					capital_interior   ,
					nome_fantasia      ,
					ie                 ,
					fantasia           ,
					bairro             ,
					cidade_pesquisa    
			FROM  tbl_posto 
			WHERE cnpj= '$posto_codigo';";
	$res = @pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$endereco         = trim(pg_result($res,0,endereco));
		$numero           = trim(pg_result($res,0,numero));
		$complemento      = trim(pg_result($res,0,complemento));
		$cep              = trim(pg_result($res,0,cep));
		$cidade           = trim(pg_result($res,0,cidade));
		$estado           = trim(pg_result($res,0,estado));
		$email            = trim(pg_result($res,0,email));
		$fone             = trim(pg_result($res,0,fone));
		$contato          = trim(pg_result($res,0,contato));
		$capital_interior = trim(pg_result($res,0,capital_interior));
		$nome_fantasia    = trim(pg_result($res,0,nome_fantasia));
		$ie               = trim(pg_result($res,0,ie));
		$fantasia         = trim(pg_result($res,0,fantasia));
		$bairro           = trim(pg_result($res,0,bairro));
		$cidade_pesquisa  = trim(pg_result($res,0,cidade_pesquisa));

		echo "<option value='$cond' selected>$cond_des</option>\n";
	}
}

echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold' >";
echo "<td align='left' colspan = '6'>Cliente/Posto</td>";
echo "</tr>";

echo "<TR >\n";
echo "	<TD COLSPAN='6' ALIGN='center' nowrap>";
echo "<B>CNPJ</B>";
echo "	<input type='text' name='posto_codigo' id='posto_codigo' size='18' value='$posto_codigo' class='frm'>&nbsp;";
echo "&nbsp;&nbsp;<B>Razão Social </B>";
echo "	<input type='text' name='posto_nome' id='posto_nome' size='45' value='$nome' class='frm'>&nbsp;";
?>
<!--<INPUT TYPE='button' name='bt' id='bt' value='Selecionar' class='frm' onClick='enviarCliente()'>-->
<INPUT TYPE='button' name='bt' id='bt' value='Cadastrar Novo' class='frm' onClick='fnc_cadastra_cliente ()'>



<?


echo "	</TD>";
echo "</TR>\n";

if(strlen($posto_codigo)>0){
	echo "<tr  font-weight:bold' >";
	echo "<td align='left' colspan='6'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Endereco:</b></b> $endereco, $numero $complemento
		&nbsp;&nbsp;&nbsp;&nbsp;<b>Bairro:</b>  $bairro
		&nbsp;&nbsp;&nbsp;&nbsp;<b>CEP:</b>  $cep </td>";
	echo "</tr>";
	echo "<tr  font-weight:bold' >";
	echo "<td align='left' colspan='6'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Cidade:</b>  $cidade &nbsp;&nbsp;&nbsp;&nbsp;
		<b>Fone: </b>  $fone&nbsp;&nbsp;&nbsp;&nbsp;
		<b>UF: </b>$estado</td>";
	echo "</tr>";

}

echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold' >";
echo "<td align='center'>Nota Fiscal</td>";
echo "<td align='center'>Série</td>";
echo "<td align='center'>Emissão</td>";
echo "<td align='center'>Saida</td>";
echo "<td align='center'>CFOP</td>";
echo "<td align='center'>Natureza</td>";
echo "</tr>";

echo "<tr>";
//echo "<td><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' size='6'  maxlength='8' onBlur=\"exibirFat('dados','','','alterar')\" class='frm'><br><div name='f2' id='f2' class='carregar' class='frm'></div></td>\n";
echo "<td colspan = '1' align='center'>
		<input type='text' name='nf' id='nf' size='10' value='$nota_fiscal' class='frm'>
		<input type='button' name='bt' id='bt' value='Consultar' class='frm' onClick='consultaNota()'>
	</td>";

echo "<td><input type='text' name='serie'    id='serie'    value='$serie'    size='10' maxlength='10' class='frm'></td>\n";
echo "<td><input type='text' name='emissao'  id='emissao'  value='$emissao'  size='10' maxlength='10' class='frm'></td>\n";
echo "<td><input type='text' name='saida'    id='saida'    value='$saida'    size='10' maxlength='10' class='frm'></td>\n";
echo "<td><input type='text' name='cfop'     id='cfop'     value='$cfop'     size='8'  maxlength='8' class='frm'></td>\n";
echo "<td><input type='text' name='natureza' id='natureza' value='$natureza' size='10' maxlength='30' class='frm'></td>\n";
echo "</tr>";

echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>";
echo "<td align='center' colspan='1'>Condição</td>";
echo "<td align='center' colspan='3'>Transp.</td>";
echo "<td align='center' colspan='2'>Tipo do Frete (1-Emitente 2-Destinatário)</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='left' nowrap>\n";
if(strlen($faturamento_fabrica)>0){
	$sql = "SELECT condicao,
				   descricao 
			FROM  tbl_condicao 
			WHERE fabrica = $faturamento_fabrica;";
	$res = @pg_exec ($con,$sql);
	echo "<SELECT NAME='condicao' class='frm'>";
	echo "<option value=''>Selecionar</option>";
	for ($i=0; $i < pg_numrows($res); $i++) {
		$cond     = trim(pg_result($res,$i,condicao));
		$cond_des = strtoupper(trim(pg_result($res,$i,descricao)));
		if($cond == $condicao)
			echo "<option value='$cond' selected>$cond_des</option>\n";
		else
			echo "<option value='$cond'>$cond_des</option>\n";
	}
	echo "</SELECT>";
}else echo "Selecione a fábrica";

echo "</td>\n";
echo "<td align='center' colspan='3' nowrap>";
$sql = "SELECT  transportadora,
				nome 
		FROM    tbl_transportadora
		ORDER BY nome;";
$res = @pg_exec ($con,$sql);

echo "<SELECT NAME='transportadora' class='frm'>";
echo "<option value=''>Selecionar</option>";
for ($i=0; $i < pg_numrows($res); $i++) {
	$x_transportadora= trim(pg_result($res,$i,transportadora));
	$transp_nome	= strtoupper(trim(pg_result($res,$i,nome)));
	if($x_transportadora== $transportadora)
		echo "<option value='$x_transportadora' selected>$transp_nome</option>\n";
	else
		echo "<option value='$x_transportadora'>$transp_nome</option>\n";
}
echo "</SELECT>";
echo "</td>";
echo "<td align='center' colspan='2' nowrap>	
		<input type='text' name='tipo_frete' id='tipo_frete' value='$tipo_frete'  size='10'  maxlength='12'  class='frm'>";
echo "</tr>\n";

echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>";
echo "<td align='center' colspan='1'>Qtde Volume</td>";
echo "<td align='center' colspan='1'>Valor do Frete</td>";
echo "<td align='center' colspan='1'>Base ICMS</td>";
echo "<td align='center' colspan='1'>Valor ICMS</td>";
echo "<td align='center' colspan='1'>Valor IPI</td>";
echo "<td align='center' colspan='1'>Valor Total</td>";
echo "</tr>";

echo "<tr>";
echo "<td align='center' nowrap colspan='1'><input type='text' name='qtde_volume' id='qtde_volume' value='$qtde_volume'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" class='frm'></td>\n";
echo "<td align='left' nowrap>\n<input type='text' name='valor_frete' id='valor_frete' value='$valor_frete'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" class='frm'></td>\n";
echo "<td align='center' nowrap colspan='1'><input type='text' name='fat_base_icms' id='fat_base_icms' value='$fat_base_icms'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" class='frm'></td>\n";
echo "<td align='center' nowrap colspan='1'><input type='text' name='fat_valor_icms' id='fat_valor_icms' value='$fat_valor_icms'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" class='frm'></td>\n";
echo "<td align='center' nowrap colspan='1'><input type='text' name='fat_valor_tot_ipi' id='fat_valor_tot_ipi' value='$fat_valor_tot_ipi'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" class='frm'></td>\n";
echo "<td align='center' nowrap colspan='1'>yy<input type='text' name='total_nota' id='total_nota' value='$total_nota'  size='10'  maxlength='12' onblur=\"checarNumero(this);\" class='frm'></td>\n";
echo "</tr>";

echo "<tr bgcolor='#596D9B' style='color:#ffffff ; font-weight:bold'>";
echo "<td align='center' colspan='6'>Observação</td>";
echo "</tr>";
echo "<tr>";
echo "<td align='center' nowrap colspan='6'>
	<input type='text' name='obs' id='obs' value='$obs'  size='100'  maxlength='300'  class='frm'></td>\n";
echo "</tr>";

?>
<tr>
	<td colspan='6'>&nbsp;</td>
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
			<td align='center'>Descrição</td>
			<td align='center'>Qtde</td>
			<td align='center'>Preço</td>
			<?
			if(strlen($faturamento)>0)
				echo "<td align='center'>Subtotal</td>";
			?>
			<td align='center'>Aliq. ICMS</td>
			<td align='center'>Aliq> IPI</td>
			<td align='center'>Base Icms</td>
			<td align='center'>Valor ICMS</td>
			<td align='center'>Base IPI</td>
			<td align='center'>valor IPI</td>
		</tr>
<?

//SE NAO EXISTIR FATURAMENTO ENTAO NAO MOSTRA OS ITENS DA NOTA FISCAL
if(strlen($faturamento)==0){
	for ($i = 0 ; $i < 50 ; $i++) {

		//INSERIR ITENS DA NOTA

		$referencia     = $_POST["referencia_$i"]  ;
		$descricao= $_POST["descricao_$i"]  ;
		$qtde           = $_POST["qtde_$i"]        ;
		$preco          = $_POST["preco_$i"]       ;
		$aliq_icms      = $_POST["aliq_icms_$i"]   ;
		$aliq_ipi       = $_POST["aliq_ipi_$i"]    ;
		$base_icms      = $_OPST["base_icms_$i"]   ;
		$valor_icms     =$_POST["valor_icms_$i"]   ;
		$base_ipi       =$_POST["base_ipi_$i"]     ;
		$valor_ipi      =$_POST["valor_ipi_$i"]    ;

		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = "#FFEECC";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap>".($i+1)."</td>\n";
		echo "<td align='right' nowrap><input type='text' class='frm' name='referencia_$i' id='referencia_$i' value='$referencia' size='10' maxlength='20' rel='referencia' alt='descricao_$i' class='frm'></td>\n";
		echo "<td align='right' nowrap><input type='text' class='frm' name='descricao_$i' id='descricao_$i' alt='referencia_$i' value='$descricao' size='10' maxlength='20' rel='descricao' class='frm'></td>\n";

		if ($qtde_estoque == 0)  $qtde_estoque  = "";
		if ($qtde_quebrada == 0) $qtde_quebrada = "";

		echo "<td align='right' nowrap><input class='frm' type='text' name='qtde_$i' id='qtde_$i' value='$qtde' size='5' maxlength='10' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\" class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='preco_$i' id='preco_$i' value='$preco' size='5' maxlength='12' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\" class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_$i' id='aliq_icms_$i' value='$aliq_icms' size='5' maxlength='10' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\" class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_$i' id='aliq_ipi_$i' value='$aliq_ipi' size='5' maxlength='10' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\" class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_icms_$i' id='base_icms_$i' value='$base_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_saida.referencia_".($i+1).".focus();' readonly class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_icms_$i'	id='valor_icms_$i'	value='$valor_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_saida.referencia_".($i+1).".focus();' readonly class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_ipi_$i'	id='base_ipi_$i'	value='$base_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_saida.referencia_".($i+1).".focus();' readonly class='frm'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_$i'	id='valor_ipi_$i'	value='$valor_ipi'	size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='nf_saida.referencia_".($i+1).".focus();' readonly class='frm'> </td>\n";
		echo "</tr>\n";
	}

}else{
	$sql= "SELECT tbl_faturamento_item.faturamento   ,
				tbl_faturamento_item.faturamento_item,
				tbl_faturamento_item.peca            ,
				tbl_faturamento_item.qtde            ,
				tbl_faturamento_item.preco           ,
				tbl_faturamento_item.aliq_icms       ,
				tbl_faturamento_item.aliq_ipi        ,
				tbl_faturamento_item.base_icms       ,
				tbl_faturamento_item.valor_icms      ,
				tbl_faturamento_item.base_ipi        ,
				tbl_faturamento_item.valor_ipi       ,
				tbl_peca.referencia                  ,
				tbl_peca.descricao                   
			FROM      tbl_faturamento_item 
			JOIN      tbl_peca             ON tbl_faturamento_item.peca  = tbl_peca.peca
			WHERE faturamento = $faturamento;";

	$res = pg_exec ($con,$sql);

	$subtotal         = 0;
	$valor_total      = 0;
	$total_valor_ipi  = 0;
	$total_valor_icms = 0;

	for ($i = 0 ; $i < pg_numrows($res); $i++) {
		$faturamento_item = trim(pg_result($res,$i,faturamento_item)) ;
		$referencia       = trim(pg_result($res,$i,referencia)) ;
		$descricao        = trim(pg_result($res,$i,descricao));
		$qtde             = trim(pg_result($res,$i,qtde));
		$preco            = trim(pg_result($res,$i,preco));
		$aliq_icms        = trim(pg_result($res,$i,aliq_icms));
		$aliq_ipi         = trim(pg_result($res,$i,aliq_ipi));
		$base_icms        = trim(pg_result($res,$i,base_icms));
		$valor_icms       = trim(pg_result($res,$i,valor_icms));
		$base_ipi         = trim(pg_result($res,$i,base_ipi));
		$valor_ipi        = trim(pg_result($res,$i,valor_ipi));

		$subtotal         = $preco * $qtde;
		$valor_total      = $valor_total     + $subtotal;
		$total_valor_ipi  = $total_valor_ipi + $valor_ipi;
		$total_valor_icms = $total_valor_icms+ $valor_icms;

		$preco            = number_format ($preco,2,',','.');
		$aliq_icms        = number_format ($aliq_icms,2,',','-.');
		$aliq_ipi         = number_format ($aliq_ipi,2,',','.-');
		$base_icms        = number_format ($base_icms,2,',','.');
		$valor_icms       = number_format ($valor_icms,2,',','.');
		$base_ipi         = number_format ($base_ipi,2,',','.');
		$valor_ipi        = number_format ($valor_ipi,2,',','.');
		$subtotal         = number_format ($subtotal,2,',','.');

		$cor = "#F7F5F0";
		if ($i % 2 == 0) $cor = "#F1F4FA";

		echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
		echo "<td align='right' nowrap>
		<input type='hidden' name='faturamento_item_$i' value='$faturamento_item'>
		<input type='hidden' name='peca_$i' value='$peca'>".($y+1)."</td>\n";
		echo "<td align='right' nowrap><input type='text' name='referencia_$i'  value='$referencia'  size='7'  maxlength='10' class='frm'></td>\n";
		echo "<td align='left' nowrap>$descricao</td>\n";
	#	$preco = number_format ($preco,2,',','.');
		if ($qtde_estoque == 0)  $qtde_estoque  = "";
		if ($qtde_quebrada == 0) $qtde_quebrada = "";

		echo "<td align='right' nowrap><input class='frm' type='text' name='qtde_$i' id='qtde_$i' value='$qtde' size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='preco_$i' id='preco_$i' value='$preco' size='5' maxlength='12' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap>$subtotal</td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_$i' id='aliq_icms_$i' value='$aliq_icms' size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_$i' id='aliq_ipi_$i' value='$aliq_ipi' size='5' maxlength='10' onKeyUp='calc_base_icms($i);'></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_icms_$i' id='base_icms_$i' value='$base_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' onfocus='alert();' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_icms_$i' id='valor_icms_$i' value='$valor_icms' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='base_ipi_$i' id='base_ipi_$i' value='$base_ipi' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";
		echo "<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_$i' id='valor_ipi_$i' value='$valor_ipi' size='5' maxlength='10' style='background-color: $cor; border: none;' readonly></td>\n";

		echo "</tr>\n";
	}

	$valor_total      = number_format ($valor_total,2,',','.');
	$total_valor_icms = number_format ($total_valor_icms,2,',','.');
	$total_valor_ipi  = number_format ($total_valor_ipi,2,',','.');

	echo "<tr bgcolor='#d1d4eA' style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='right' nowrap colspan= '5'> Totais</td>\n";

	echo "<td align='right' nowrap>$valor_total</td>\n";
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

	$total_nota = number_format ($total_nota,2,',','.');
	if($valor_total == $total_nota){
	}else{
			echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
			echo "<td align='right' nowrap colspan='14'><font color='red'><b>Valor Total da Nota:$valor_total está diferente de Total cadastrado:$total_nota</b></font></td>\n";
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

if(strlen($faturamento)>0 and strlen($alterar_faturamento)>0){
	$desc_bt="Alterar";
}else{
	if(strlen($faturamento)>0){
		$desc_bt="Imprimir";
	}else{
		$desc_bt="Gravar";	
	}
}


echo "<tr>";
echo "<td colspan='12' align='center'>";
if(strlen($faturamento)==0 or (strlen($faturamento)>0 and strlen($alterar_faturamento)>0))	echo "<input type='submit' name='btn_acao' value='$desc_bt'>";
else echo "	<INPUT TYPE='button' name='bt_imp' id='bt_imp' value='$desc_bt' class='frm' onClick='imprimeNota()'>";

echo "</td>";
echo "</tr>";
echo "</table>\n";
echo "</form>";
?>
<p>

<? #include "rodape.php"; ?>

</body>
</html>