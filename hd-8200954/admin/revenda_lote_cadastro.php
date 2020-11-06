<center>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$msg_erro = "";

function converte_data($date){
	$date = explode("/", $date);
	$date2 = $date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if (strlen($_POST["acao"]) > 0) $acao = trim( $_POST["acao"]);
if (strlen($_POST["ajax"]) > 0) $ajax = trim($_POST["ajax"]);

##### G R A V A R   P E D I D O #####
if ($acao == "gravar" AND $ajax == "sim") {

	$xcodigo_posto = trim($_POST['codigo_posto']);
	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);
	$qtde_item     = trim($_POST['qtde_item']);
	$lote          = trim($_POST['lote']);
	$responsavel   = trim($_POST['responsavel']);
	$nota_fiscal   = trim($_POST['nota_fiscal']);
	$data_nf       = trim($_POST['data_nf']);

	if(strlen($responsavel)==0) $msg_erro .= "<br>Digite o nome do responsável";
	if(strlen($lote)       ==0) $msg_erro .= "<br>Digite o número do lote";

	if (strlen($xcodigo_posto) > 0 ) {
		$sql =	"SELECT tbl_posto.posto
				FROM	tbl_posto
				JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.codigo_posto = '$xcodigo_posto' ";

		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$posto = "'".pg_result($res,0,0)."'";
		}else{
			$posto = "null";
			$msg_erro .= " Favor informe o posto correto. ";
		}
	}

	if (strlen($xrevenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
			 FROM  tbl_revenda
			 WHERE tbl_revenda.cnpj = '$xrevenda_cnpj' ";

		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$revenda = "'".pg_result($res,0,0)."'";
		}else{
			$revenda = "null";
			$msg_erro .= " Favor informe a revenda correta. ";
		}
	}

	$data_nf = converte_data($data_nf);

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($lote_revenda) == 0) {
			########## I N S E R E ##########
			$sql =	"INSERT INTO tbl_lote_revenda (
						posto             ,
						revenda           ,
						fabrica           ,
						admin             ,
						lote              ,
						nota_fiscal       ,
						data_nf           ,
						responsavel
					) VALUES (
						$posto              ,
						$revenda            ,
						$login_fabrica      ,
						$login_admin        ,
						'$lote'             ,
						'$nota_fiscal'      ,
						'$data_nf'          ,
						'$responsavel'
					)";
		}else{
			########## A L T E R A ##########
			$sql =	"UPDATE tbl_lote_revenda SET
						posto             = $posto              ,
						responsavel       = '$responsavel'
					WHERE tbl_lote_revenda.lote_revenda  = $lote_revenda
					AND   tbl_lote_revenda.fabrica = $login_fabrica";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
			$res = @pg_exec($con,"SELECT CURRVAL ('tbl_lote_revenda_lote_revenda_seq')");
			$lote_revenda = pg_result($res,0,0);
			$msg_erro .= pg_errormessage($con);
		}

		if($qtde_item == 0) $msg_erro = "É necessário fazer o lançamento de produtos";
		if (strlen($msg_erro) == 0) {
			$qtde_item = $qtde_item+3;
			for ($i = 0 ; $i < $qtde_item ; $i++) {

				$produto_referencia = trim($_POST['referencia_produto_' . $i]);
				$produto_descricao  = trim($_POST['descricao_produto_'  . $i]);
				$produto_qtde       = trim($_POST['produto_qtde_'       . $i]);

				if (strlen($msg_erro) == 0) {

					if (strlen($produto_referencia) > 0 ) {
						$xproduto_referencia = strtoupper($produto_referencia);
						//$xproduto_referencia = str_replace("-","",$xproduto_referencia);
						//$xproduto_referencia = str_replace(".","",$xproduto_referencia);
						//$xproduto_referencia = str_replace("/","",$xproduto_referencia);
						//$xproduto_referencia = str_replace(" ","",$xproduto_referencia);

						if (strlen($xproduto_referencia)==0 ) continue;

						if (strlen ($produto_qtde) == 0) $produto_qtde = 1;
						if ($produto_qtde==0)            $msg_erro .= "Informe a quantidade do produto!<br>\n";

						$sql =	"
								SELECT tbl_produto.produto
								FROM    tbl_produto
								JOIN    tbl_linha USING(linha)
								WHERE   tbl_linha.fabrica = $login_fabrica
								AND tbl_produto.referencia = '$xproduto_referencia' ";
						$res = @pg_exec($con,$sql);

						if (@pg_numrows($res) == 1) {
							$produto = pg_result($res,0,produto);
						}else{
							$msg_erro .= "<br>Peça $produto_referencia não cadastrada.";
							$linha_erro = $i;
						}

						if (strlen($msg_erro) == 0) {
							$sql = "SELECT lote_revenda_item FROM tbl_lote_revenda_item WHERE produto = $produto AND lote_revenda=$lote_revenda";
							$res = @pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);

							if(pg_numrows($res)==0){
								$sql =	"INSERT INTO tbl_lote_revenda_item (
											lote_revenda ,
											produto      ,
											qtde
										) VALUES (
											$lote_revenda ,
											$produto      ,
											$produto_qtde
										)";
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);
							}else{
								$msg_erro .= "<br>Peça $produto_referencia não pode ser incluida mais de uma vez neste lote.";
								/*$sql = "UPDATE tbl_lote_revenda_item SET qtde = qtde + $produto_qtde WHERE produto = $produto AND lote_revenda=$lote_revenda";
								$res = @pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);*/
							}

							if (strlen ($msg_erro) > 0) {
								$linha_erro = $i;
								break;
							}
						}
					}
				}
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso";
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro";
	}

	exit;
}

$importa = $_POST["importar"];
if(strlen($importa) > 0){
	$caminho       = "/www/cgi-bin/revenda/entrada";
	$caminho_saida = "/www/cgi-bin/revenda/saida";

	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);
	$qtde_item     = trim($_POST['qtde_item']);
	$xcodigo_posto = trim($_POST['codigo_posto']);
	$xrevenda_cnpj = trim($_POST['revenda_cnpj']);
	$qtde_item     = trim($_POST['qtde_item']);
	$lote          = trim($_POST['lote']);
	$responsavel   = trim($_POST['responsavel']);
	$data_nf       = trim($_POST['data_nf']);
	$arquivo       = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	if(strlen($arquivo["tmp_name"])==0) $msg_erro = "Selecione um arquivo";
	if(strlen($responsavel)==0)         $msg_erro .= "<br>Digite o nome do responsável";
	if(strlen($lote)       ==0)         $msg_erro .= "<br>Digite o número do lote";

	if (strlen($xcodigo_posto) > 0 ) {
		$sql = "SELECT tbl_posto.posto
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE	tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_posto_fabrica.codigo_posto = '$xcodigo_posto' ";

		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) == 1) $posto = "'".pg_result($res,0,0)."'";
		else                       $posto = "null";
	}

	if (strlen($xrevenda_cnpj) > 0 ) {
		$sql =	"SELECT tbl_revenda.revenda
			FROM	tbl_revenda
			WHERE	tbl_revenda.cnpj = '$xrevenda_cnpj' ";

		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) $revenda = "'".pg_result($res,0,0)."'";
	}else $msg_erro .= " Favor informe a revenda correta. ";

	$aux_data_nf = converte_data($data_nf);

	if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none"){
		$config["tamanho"] = 2048000;

		if ($arquivo["type"] <> "text/plain") {
			$msg_erro = "Arquivo em formato inválido!";
		}else{
			if ($arquivo["size"] > $config["tamanho"]) $msg_erro = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
		}
		if (strlen($msg_erro) == 0) {
			$nome_arquivo = $caminho."/".$arquivo["name"];
			if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
				$msg_erro .= "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$f = fopen("$caminho/".$arquivo["name"], "r");
				$i=1;
				$sql4 = "DROP TABLE tmp_rlc_upload ";
				$res4 = @pg_exec($con,$sql4);
				$sql = "CREATE TABLE tmp_rlc_upload (produto int4,qtde int4, nf varchar(10));";
				$res = @pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				while (!feof($f)){
					$buffer = fgets($f, 4096);
					if($buffer <> "\n" and strlen(trim($buffer))>0){
						list($referencia, $qtde,$nf) = explode("\t", $buffer);
						$referencia   = trim($referencia);
						$qtde         = trim($qtde);
						$nf           = trim($nf);

						if(strlen($referencia)==0) $msg_erro = "Falta a referência do produto na Fábrica";
						if(strlen($qtde)      ==0) $msg_erro = "Falta a quantidade do produto na Fábrica";
						if(strlen($nf)        ==0) $msg_erro = "Falta a Nota Fiscal deste produto";
						$sql =	"
							SELECT tbl_produto.produto
							FROM    tbl_produto
							JOIN    tbl_linha USING(linha)
							WHERE   tbl_linha.fabrica = $login_fabrica
							AND tbl_produto.referencia = '$referencia' ";
						$res = pg_exec($con,$sql);

						if (pg_numrows($res) == 1) $produto = pg_result($res,0,produto);
						else{
							$sql = "SELECT  tbl_revenda_produto.produto
								FROM tbl_produto
								JOIN tbl_revenda_produto USING(produto)
								JOIN tbl_linha           USING(linha)
								WHERE tbl_linha.fabrica              = $login_fabrica
								AND   tbl_revenda_produto.revenda    = $revenda
								AND   tbl_revenda_produto.referencia = '$referencia'
								ORDER BY tbl_revenda_produto.descricao,tbl_produto.descricao";
							$res = pg_exec($con,$sql);
							if (@pg_numrows($res) == 1) {
								$produto = pg_result($res,0,produto);
							}else{
								$msg_erro .= "Produto $referencia não cadastrado.";
								$linha_erro = $i;
							}
						}


						if(strlen($msg_erro)>0)    $msg_erro = "Erro na linha $i:".$msg_erro;
						else{
							$sql = "SELECT tmp_rlc_upload FROM tmp_rlc_upload WHERE produto = $produto AND nf=trim('$nf')";
							$res = pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);

							if(pg_numrows($res)==0){
								$sql =	"INSERT INTO tmp_rlc_upload (
											produto      ,
											qtde         ,
											nf
										) VALUES (
											$produto      ,
											$qtde         ,
											'$nf'
										)";
								$res = pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);
							}else{
								$sql = "UPDATE tmp_rlc_upload SET qtde = qtde + $qtde WHERE produto = $produto ";
								$res = pg_exec($con,$sql);
								$msg_erro = pg_errormessage($con);
							}
						}
						if (strlen ($msg_erro) > 0) break;
					}
					$i++;
				}
				fclose($f);
			}
			if(strlen($msg_erro)==0){
				$sql = "SELECT * FROM tmp_rlc_upload ORDER BY nf ASC";
				$res = pg_exec ($con,$sql) ;
				if (pg_numrows($res) > 0) {
					$res2 = pg_exec ($con,"BEGIN TRANSACTION");
					$qtde_item = pg_numrows($res);
					for ($k = 0 ; $k <$qtde_item ; $k++) {
						$nf       = trim(pg_result($res,$k,nf));
						$produto  = trim(pg_result($res,$k,produto));
						$qtde     = trim(pg_result($res,$k,qtde));
						if($nf_anterior <> $nf){
							$sql2 =	"INSERT INTO tbl_lote_revenda (
										posto             ,
										revenda           ,
										fabrica           ,
										admin             ,
										lote              ,
										nota_fiscal       ,
										data_nf           ,
										responsavel
									) VALUES (
										$posto              ,
										$revenda            ,
										$login_fabrica      ,
										$login_admin        ,
										'$lote'             ,
										'$nf'               ,
										'$aux_data_nf'      ,
										'$responsavel'
									)";
							$res2 = pg_exec ($con,$sql2);
							$msg_erro .= pg_errormessage($con);
							if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
								$res3 = pg_exec($con,"SELECT CURRVAL ('tbl_lote_revenda_lote_revenda_seq')");
								$lote_revenda = pg_result($res3,0,0);
								$msg_erro .= pg_errormessage($con);
							}
						}

						$sql3 =	"INSERT INTO tbl_lote_revenda_item (
									lote_revenda ,
									produto      ,
									qtde
								) VALUES (
									$lote_revenda ,
									$produto      ,
									$qtde
								)";
						$res3 = @pg_exec($con,$sql3);
						$msg_erro .= pg_errormessage($con);
						$nf_anterior = $nf;
					}
				}
			}
			$sql4 = "DROP TABLE tmp_rlc_upload ";
			$res4 = @pg_exec($con,$sql4);
			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "Carga efetuada com sucesso.";
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				$msg = "Carga não foi efetuada.";
			}
		}
	}

	$nome_arquivo = $arquivo["name"];
	$dados = "importado".date("d-m-Y-his").".txt";
	exec ("mv $caminho/$nome_arquivo $caminho_saida/$dados");

}

$layout_menu = "callcenter";
$title       = "CADASTRO DE LOTE DE PRODUTO";
include "cabecalho.php";

?>
<? include "../js/js_css.php"; //adicionado por Fabio 27-09-2007 ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<!--
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
-->
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script language="JavaScript">


$().ready(function() {


	$("#data_nf").datepick({startDate:'01/01/2000'});
	$("#data_nf").mask("99/99/9999");
	$("#data_nf2").datepick({startDate:'01/01/2000'});
	$("#data_nf2").mask("99/99/9999");


	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	$("#codigo_posto").autocomplete("<?echo  'revenda_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#nome_posto").val(data[1]) ;
	});

	$("#nome_posto").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
	});

	$("#revenda_cnpj").autocomplete("<?echo  'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj").result(function(event, data, formatted) {
		$("#revenda_nome").val(data[1]) ;
	});

	$("#revenda_nome").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome").result(function(event, data, formatted) {
		$("#revenda_cnpj").val(data[0]) ;
	});

	$("#codigo_posto2").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto2").result(function(event, data, formatted) {
		$("#nome_posto2").val(data[1]) ;
	});

	$("#nome_posto2").autocomplete("<?echo 'revenda_consulta_ajax.php?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome_posto2").result(function(event, data, formatted) {
		$("#codigo_posto2").val(data[0]) ;
	});

	$("#revenda_cnpj2").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#revenda_cnpj2").result(function(event, data, formatted) {
		$("#revenda_nome2").val(data[1]) ;
	});

	$("#revenda_nome2").autocomplete("<?echo 'revenda_consulta_ajax.php?busca_revenda=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#revenda_nome2").result(function(event, data, formatted) {
		$("#revenda_cnpj2").val(data[0]) ;
	});

});
</SCRIPT>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>

<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs({fxSpeed: 'fast'} );

	});
</script>


<script language="JavaScript">

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}



function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
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
			janela.proximo		= document.frm_os.codigo_posto;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

function fnc_pesquisa_revenda2 (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_upload.revenda_nome;
			janela.cnpj			= document.frm_upload.revenda_cnpj;
			janela.fone			= document.frm_upload.revenda_fone;
			janela.cidade		= document.frm_upload.revenda_cidade;
			janela.estado		= document.frm_upload.revenda_estado;
			janela.endereco		= document.frm_upload.revenda_endereco;
			janela.numero		= document.frm_upload.revenda_numero;
			janela.complemento	= document.frm_upload.revenda_complemento;
			janela.bairro		= document.frm_upload.revenda_bairro;
			janela.cep			= document.frm_upload.revenda_cep;
			janela.email		= document.frm_upload.revenda_email;
			janela.proximo		= document.frm_upload.arquivo;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

</script>
<script language='javascript'>


function adiconarPecaTbl() {

	if (document.getElementById('add_qtde').value==''){
		alert('Informe a quantidade');
		return false;
	}

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	// inicio da tabela
	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// coluna 1 - codigo do item
	var celula = criaCelula(document.getElementById('add_referencia').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'lote_revenda_' + iteration);
	el.setAttribute('id', 'lote_revenda_' + iteration);
	el.setAttribute('value','');
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_' + iteration);
	el.setAttribute('id', 'produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'referencia_produto_' + iteration);
	el.setAttribute('id', 'referencia_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_referencia').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'descricao_produto_' + iteration);
	el.setAttribute('id', 'descricao_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca_descricao').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_qtde_' + iteration);
	el.setAttribute('id', 'produto_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('add_qtde').value);
	celula.appendChild(el);

	linha.appendChild(celula);


	// coluna 2 DESCRIÇÃO
	var celula = criaCelula(document.getElementById('add_peca_descricao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 3 QTDE
	var qtde = document.getElementById('add_qtde').value;
	var celula = criaCelula(qtde);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);


	var total_valor_peca = parseInt(qtde);


	// coluna 9 - ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerPeca(this,total_valor_peca);};
	celula.appendChild(el);

	// fim da linha
	linha.appendChild(celula);
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	//linha.style.cssText = 'color: #404e2a;';
	tbl.appendChild(tbody);

	// incrementa a qtde
	document.getElementById('qtde_item').value++;

	//limpa form de add mao de obra
	document.getElementById('add_referencia').value='';
	document.getElementById('add_peca_descricao').value='';
	document.getElementById('add_qtde').value='';


	// atualiza os totalizador
	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) + parseFloat(total_valor_peca);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);

	document.getElementById('add_referencia').focus();
}

function removerPeca(iidd,valor){
//	var tbl = document.getElementById('tbl_pecas');
//	var lastRow = tbl.rows.length;
//	if (lastRow > 2){
//		tbl.deleteRow(iidd.title);
//		document.getElementById('qtde_item').value--;
//	}
	var tbl = document.getElementById('tbl_pecas');
	var oRow = iidd.parentElement.parentElement;
	tbl.deleteRow(oRow.rowIndex);
	document.getElementById('qtde_item').value--;

	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) - parseFloat(valor);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);


}


function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.add_qtde;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http_forn = new Array();

function gravar(formulatio,redireciona,pagina,janela) {

	var acao = 'gravar';
	url = "<?=$PHP_SELF?>?ajax=sim&acao="+acao;
	parametros = "";
	for( var i = 0 ; i < formulatio.length; i++ ){
		if (formulatio.elements[i].type !='button'){
			//alert(formulatio.elements[i].name+' = '+formulatio.elements[i].value);
			if(formulatio.elements[i].type=='radio' || formulatio.elements[i].type=='checkbox'){

				if(formulatio.elements[i].checked == true){
//					alert(formulatio.elements[i].value);
					parametros = parametros+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
				}
			}else{
				parametros = parametros+"&"+formulatio.elements[i].name+"="+escape(formulatio.elements[i].value);
			}
		}
	}


	var com       = document.getElementById('erro');
	var saida     = document.getElementById('saida');

	com.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;<br><img src='../imagens/carregar2.gif' >\n";
	saida.innerHTML = "&nbsp;&nbsp;Aguarde...&nbsp;&nbsp;<br><img src='../imagens/carregar2.gif' >\n";

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('POST',url,true);

	http_forn[curDateTime].setRequestHeader("X-Requested-With","XMLHttpRequest");
	http_forn[curDateTime].setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	http_forn[curDateTime].setRequestHeader("CharSet", "ISO-8859-1");
	http_forn[curDateTime].setRequestHeader("Content-length", url.length);
	http_forn[curDateTime].setRequestHeader("Connection", "close");

	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4){
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304){

			var response = http_forn[curDateTime].responseText.split("|");
				if (response[0]=="debug"){
					alert(http_forn[curDateTime].responseText);
				}
				if (response[0]=="ok"){
					com.style.visibility = "hidden";
					com.innerHTML = response[1];
					saida.innerHTML = response[1];
					if (document.getElementById('btn_continuar')){
						document.getElementById('btn_continuar').style.display='inline';
					}
					formulatio.btn_acao.value='Gravar';
				}else{
					formulatio.btn_acao.value='Gravar';
				}
				if (response[0]=="1"){
					com.style.visibility = "visible";
					saida.innerHTML = "<font color='#990000'>Ocorreu um erro, verifique!</font>\n";
					alert('Erro: verifique as informações preenchidas!');
					com.innerHTML = response[1];
					formulatio.btn_acao.value='Gravar';
				}
			}
		}
	}
	http_forn[curDateTime].send(parametros);
}

function adiconarPecaTbl() {

	if (document.getElementById('add_qtde').value==''){
		alert('Informe a quantidade');
		return false;
	}

	var tbl = document.getElementById('tbl_pecas');
	var lastRow = tbl.rows.length;
	var iteration = lastRow;

	// inicio da tabela
	var linha = document.createElement('tr');
	linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

	// coluna 1 - codigo do item
	var celula = criaCelula(document.getElementById('add_referencia').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'lote_revenda_' + iteration);
	el.setAttribute('id', 'lote_revenda_' + iteration);
	el.setAttribute('value','');
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_' + iteration);
	el.setAttribute('id', 'produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'referencia_produto_' + iteration);
	el.setAttribute('id', 'referencia_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_referencia').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'descricao_produto_' + iteration);
	el.setAttribute('id', 'descricao_produto_' + iteration);
	el.setAttribute('value',document.getElementById('add_peca_descricao').value);
	celula.appendChild(el);

	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_qtde_' + iteration);
	el.setAttribute('id', 'produto_qtde_' + iteration);
	el.setAttribute('value',document.getElementById('add_qtde').value);
	celula.appendChild(el);

	linha.appendChild(celula);


	// coluna 2 DESCRIÇÃO
	var celula = criaCelula(document.getElementById('add_peca_descricao').value);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);

	// coluna 3 QTDE
	var qtde = document.getElementById('add_qtde').value;
	var celula = criaCelula(qtde);
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	linha.appendChild(celula);


	var total_valor_peca = parseInt(qtde);


	// coluna 9 - ações
	var celula = document.createElement('td');
	celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
	var el = document.createElement('input');
	el.setAttribute('type', 'button');
	el.setAttribute('value','Excluir');
	el.onclick=function(){removerPeca(this,total_valor_peca);};
	celula.appendChild(el);

	// fim da linha
	linha.appendChild(celula);
	var tbody = document.createElement('TBODY');
	tbody.appendChild(linha);
	//linha.style.cssText = 'color: #404e2a;';
	tbl.appendChild(tbody);

	// incrementa a qtde
	document.getElementById('qtde_item').value++;

	//limpa form de add mao de obra
	document.getElementById('add_referencia').value='';
	document.getElementById('add_peca_descricao').value='';
	document.getElementById('add_qtde').value='';


	// atualiza os totalizador
	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) + parseFloat(total_valor_peca);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);

	document.getElementById('add_referencia').focus();
}

function removerPeca(iidd,valor){
//	var tbl = document.getElementById('tbl_pecas');
//	var lastRow = tbl.rows.length;
//	if (lastRow > 2){
//		tbl.deleteRow(iidd.title);
//		document.getElementById('qtde_item').value--;
//	}
	var tbl = document.getElementById('tbl_pecas');
	var oRow = iidd.parentElement.parentElement;
	tbl.deleteRow(oRow.rowIndex);
	document.getElementById('qtde_item').value--;

	var aux_valor = document.getElementById('valor_total_itens').innerHTML;
	aux_valor = parseFloat(aux_valor) - parseFloat(valor);
	document.getElementById('valor_total_itens').innerHTML = parseInt(aux_valor);


}


function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}

function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.add_qtde;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

</script>
<style>
.Conteudo{
	font-family: Arial;
	font-size: 10px;
	color: #333333;
}
.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid;
	background-color: #990000;
}

#Propaganda{
	text-align: justify;
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
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<?
$cnpj = trim($_POST['revenda_cnpj']);
$nome = trim($_POST['revenda_nome']);

$qtde_item=0;
echo "<table  align='center' width='700' border='0' cellspacing='0' class='formulario'>\n";

echo "<tr class='titulo_tabela'>\n";
echo "<td colspan='3' align='right'><b>Cadastro de Produto</b></td>\n";
echo "<td align='right' style='font:bold 12px Arial; color:#FFFFFF;'><a href='revenda_inicial_test.php'>Menu de Revendas</a></td>\n";
echo "</tr>\n";
echo "<tr><td colspan='4'><br>\n";
$aba = 2;
include "revenda_cabecalho.php";
echo "</td></tr>";

echo "<tr><td colspan='4'>\n";
?>

<div id="container-Principal" class='formulario'>
	<ul>
		<li>
			<a href="#manual"><span><img src='imagens/rec_produto.png' width='10' align="absmiddle" alt='Reclamação Produto/Defeito' />Cadastro Manual</span></a>
		</li>
		<li>
			<a href="#auto"><span><img src='imagens/duv_produto.png' width='10' align=absmiddle />Upload.</span></a>
		</li>
	</ul>
	<div id="manual" class='formulario' style="width:700px">
<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
	<?
	echo "<table width='700' border='0' class='formulario'>\n";

	echo "<tr height='20' >\n";
	echo "<td align='right' ><b>Lote</b>&nbsp;</td>\n";
	echo "<td align='left' ><input type='text' name='lote' id='lote' value='$lote' class='frm'></td>\n";
	echo "<td align='right' ><b>Responsável</b>&nbsp;</td>\n";
	echo "<td align='left' ><input type='text' name='responsavel' id='responsavel' value='$responsavel' class='frm'></td>\n";
	echo "</tr>\n";

	echo "<tr height='20'>\n";
	echo "<td align='right' ><b>Nota Fiscal</b>&nbsp;</td>\n";
	echo "<td align='left' ><input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' class='frm'></td>\n";
	echo "<td align='right' ><b>Data</b>&nbsp;</td>\n";
	echo "<td align='left' ><input type='text' name='data_nf' id='data_nf' value='$data_nf' class='frm' onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf')\"></td>\n";
	echo "</tr>\n";

	echo "<tr height='20'>\n";
	echo "<td align='right' ><b>CNPJ da Revenda</b>&nbsp;</td>\n";
	echo "<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj' maxlength='18' value='$cnpj' class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle'></td>\n";
	echo "<td align='right' ><b>Nome da Revenda</b>&nbsp;</td>\n";
	echo "<td align='left'><input type='text' name='revenda_nome' id='revenda_nome' size='40' maxlength='60' value='$nome'class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')\"></td>\n";
	echo "</tr>\n";

	echo "<input type='hidden' name='revenda_fone'>\n";
	echo "<input type='hidden' name='revenda_cidade'>\n";
	echo "<input type='hidden' name='revenda_estado'>\n";
	echo "<input type='hidden' name='revenda_endereco'>\n";
	echo "<input type='hidden' name='revenda_numero'>\n";
	echo "<input type='hidden' name='revenda_complemento'>\n";
	echo "<input type='hidden' name='revenda_bairro'>\n";
	echo "<input type='hidden' name='revenda_cep'>\n";
	echo "<input type='hidden' name='revenda_email'>\n";

	echo "<tr height='20'>\n";
	echo "<td align='right' ><b>Codigo Posto</b>&nbsp;</td>\n";
	echo "<td align='left' ><input type='text' name='codigo_posto' id='codigo_posto' maxlength='14' value='$codigo_posto' class='frm' onFocus=\"nextfield ='nome_posto'\" >&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2 (document.frm_os.codigo_posto,document.frm_os.nome_posto,'codigo')\">
	</td>\n";
	echo "<td align='right' ><b>Nome</b>&nbsp;</td>\n";
	echo "<td align='left'><input type='text' name='nome_posto' id='nome_posto' size='40' maxlength='40' value='$nome_posto' class='frm' onFocus=\"nextfield ='condicao'\">&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto2(document.frm_os.codigo_posto,document.frm_os.nome_posto,'nome')\"> </td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td colspan='4'>\n";
		echo "<table border='0' cellspacing='0' width='100%' class='formulario'>\n";

		echo "<tr class='subtitulo'>\n";
		echo "<td align='left' ><b>Destino</td>\n";
		echo "</tr>\n";

		echo "<tr valign='top'>\n";
		echo "<td align='left' valign='top'>\n";
		echo "<input type='radio' name='tipo' value='0'   id='C'> Conserto/Reparação<br>\n";
		echo "<input type='radio' name='tipo' value='50'  id='T'> Troca<br>\n";
		echo "<input type='radio' name='tipo' value='100' id='D'> Devolução<br>\n";
		echo "</td>\n";
		echo "</tr>\n";

		echo "</table>\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr class='subtitulo'>\n";
	echo "<td align='left' colspan='4' ><b>Produto</td>\n";
	echo "</tr>\n";

	echo "<input type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>\n";
	echo "<input type='hidden' name='add_peca'           id='add_peca'>\n";

	echo "<tr><td colspan='4'>\n";

		echo "<table>\n";
		echo "<tr>\n";
		echo "<td align='center' width='200'>Código <input class='frm' type='text' name='add_referencia' id='add_referencia' size='8' value='' onblur=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'referencia'); \">\n";
		echo "<img src='../imagens/lupa.png' border='0' align='absmiddle'
		onclick=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'referencia'); \" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
		echo "</td>\n";
		echo "<td align='center' nowrap>Descrição <input class='frm' type='text' name='add_peca_descricao' id='add_peca_descricao' size='40' value='' >\n";
		echo "<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick=\" fnc_pesquisa_produto (window.document.frm_os.add_referencia, window.document.frm_os.add_peca_descricao, 'descricao'); \" alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>\n";
		echo "</td>\n";
		echo "<td align='center' nowrap>Qtde <input class='frm' type='text' name='add_qtde' id='add_qtde' size='2' maxlength='4' value='' >\n &nbsp;</td>\n";
		echo "<td	nowrap><input name='gravar_peca' id='gravar_peca' type='button' value='Adicionar' onClick='javascript:adiconarPecaTbl()'></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

	echo "</td>\n";
	echo "</tr>\n";
	echo "</table>\n";

	echo "<table class='formulario' align='center' width='100%' border='0' id='tbl_pecas'>\n";
	echo "<thead>\n";

	echo "<tr height='20' class='titulo_coluna'>\n";
	echo "<td align='center' class='Conteudo'><b>Código</b>&nbsp;&nbsp;&nbsp;<div id='lista_basica' style='display:inline;'></div></td>\n";
	echo "<td align='center' class='Conteudo'><b>Descrição</b>&nbsp;</td>\n";
	echo "<td align='center' class='Conteudo'><b>Qtde</b>&nbsp;</td>\n";
	echo "<td align='center' class='Conteudo'><b>Ações</b>&nbsp;</td>\n";
	echo "</tr>\n";

	echo "</thead>\n";

	echo "<tbody>\n";

	//MOSTRA OS ITESN SE JAH FORAM GRAVADOS NO ORCAMENTO

	$valor_total_itens=0;

	if(strlen($lote_revenda) > 0 AND strlen ($msg_erro) == 0){
		$sql = "SELECT
				tbl_lote_revenda_item.lote_revenda_item                            ,
				tbl_lote_revenda_item.qtde                                         ,
				tbl_produto.produto                                                ,
				tbl_produto.descricao                                              ,
				tbl_produto.referencia
			FROM      tbl_lote_revenda_item
			LEFT JOIN tbl_produto              USING (produto)
			WHERE   tbl_lote_revenda_item.lote_revenda = $lote_revenda
			ORDER BY tbl_lote_revenda_item.lote_revenda_item;";

		$res = pg_exec ($con,$sql) ;
		if (pg_numrows($res) > 0) {
			$qtde_item = pg_numrows($res);
			for ($k = 0 ; $k <$qtde_item ; $k++) {
				$item_lote_revenda[$k]       = trim(pg_result($res,$k,lote_revenda_item));
				$item_produto[$k]            = trim(pg_result($res,$k,produto));
				$item_referencia[$k]         = trim(pg_result($res,$k,referencia));
				$item_qtde[$k]               = trim(pg_result($res,$k,qtde));
				$item_descricao[$k]          = trim(pg_result($res,$k,descricao));

				if(strlen($descricao[$k])==0) $descricao[$k] = $item_descricao[$k];
			}
		}
	}

	if($qtde_item>0){
		for ($k=0;$k<$qtde_item;$k++){
			echo "<tr style='color: #000000; text-align: center; font-size:10px'>\n";
			echo "<td>$item_referencia[$k]";

			echo "<input type='hidden' name='lote_revenda_item_$k'  id='lote_revenda_item_$k'  value='$item_lote_revenda[$k]'>\n";
			echo "<input type='hidden' name='referencia_produto_$k' id='referencia_produto_$k' value='$item_referencia[$k]'>\n";
			echo "<input type='hidden' name='produto_qtde_$k'       id='produto_qtde_$k'       value='$item_qtde[$k]'>\n";
			echo "<input type='hidden' name='produto_$k'            id='produto_$k'            value='$item_peca[$k]'>\n";

			echo "</td>\n";
			echo "<td style=' text-align: left;'>$item_descricao[$k]</td>\n";
			echo "<td>$item_qtde[$k]</td>\n";

			$total_item = $item_qtde[$k];
			$valor_total_itens += $total_item;

			echo "<td><input type='button' onclick='javascript:removerPeca(this,$total_item);' value='Excluir' /></td>\n";
			echo "</tr>\n";
		}
	}
	echo "</tbody>\n";

	echo "<tfoot>\n";
	echo "<tr height='12' bgcolor='#BCCBE0'>\n";
	echo "<td align='center' class='Conteudo' colspan='2'><b>Total</b>&nbsp;</td>\n";
	echo "<td align='center' class='Conteudo'><span style='font-weight:bold' id='valor_total_itens'>$valor_total_itens</span></td>\n";
	echo "<td align='center' class='Conteudo' colspan='2'></td>\n";
	echo "</tr>\n";
	echo "</tfoot>\n";
	echo "</table>\n";

	$valores_sub_total   += $valor_total_itens;

	echo "<table class='formulario' align='center' width='100%' border='0'height='40'>\n";
	echo "<tr><td width='50' valign='middle'  align='LEFT' colspan='4'><input type='button' name='btn_acao'  value='Gravar' onClick=\"if (this.value!='Gravar'){ alert('Aguarde');}else {this.value='Gravando...'; gravar(this.form,'sim','$PHP_SELF','nao');}\" style=\"width: 150px;\"></td>\n";
	echo "<td width='300'><div id='saida' style='display:inline;'></div></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	?>
	<div id='erro' style='visibility:hidden;opacity:0.85' class='Erro'></div>
	</form>
	</div>

<div id='auto' class='formulario'>
	<div >
	<div  class='Erro'><?=$msg_erro?></div>
	O layout para UPLOAD de produtos do lote darevenda deve conter os seguintes campos:<br>
	- referência do produto da <b>fábrica<b/> ou da <b>revenda</b>;<br>
	- quantidade a ser enviada;<br>
	- nf a ser enviada referente ao produto;<br>
	Este arquivo poderá ser preenchido no excel. Depois salvar como, escolher o tipo de arquivo (Salvar como tipo) : Escolher Texto em unicode (*.txt) separado por TAB(/t)<br>
	OBS.: Neste arquivo tem que conter somente as informções, cabeçalhos ou outras informações deverão ser excluídas antes de salvar como txt.<br><br>
	<form name="frm_upload" method="post" action="<? echo "$PHP_SELF#auto" ?>" enctype='multipart/form-data'>
	<table class='Conteudo'>
		<tr height='20'>
			<td align='right' ><b>Lote</b>&nbsp;</td>
			<td align='left' ><input type='text' name='lote' id='lote' value='<? echo $lote ?>' class='frm'></td>
			<td align='right' ><b>Responsável</b>&nbsp;</td>
			<td align='left' ><input type='text' name='responsavel' id='responsavel' value='<? echo $responsavel ?>' class='frm'></td>
		</tr>
		<tr height='20'>
			<td align='right' ><b>Data</b>&nbsp;</td>
			<td align='left' ><input type='text' name='data_nf' id='data_nf2' value='<? echo $data_nf ?>' class='frm' onKeyUp="formata_data(this.value,'frm_upload', 'data_nf2')"></td>
		</tr>
		<tr height='20'>
			<td align='right' ><b>CNPJ da Revenda</b>&nbsp;</td>
			<td align='left'><input type='text' name='revenda_cnpj' id='revenda_cnpj2'  maxlength='18' value='<? echo $cnpj ?>' class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda2 (document.frm_upload.revenda_cnpj, 'cnpj')"></td>
			<td align='right' ><b>Nome da Revenda</b>&nbsp;</td>
			<td align='left'><input type='text' name='revenda_nome' id='revenda_nome2' size='40' maxlength='60' value='<? echo $nome ?>'class='frm' >&nbsp;<img src='../imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_revenda2 (document.frm_upload.revenda_nome, 'nome')"></td>
		</tr>

		<input type='hidden' name='revenda_fone'>
		<input type='hidden' name='revenda_cidade'>
		<input type='hidden' name='revenda_estado'>
		<input type='hidden' name='revenda_endereco'>
		<input type='hidden' name='revenda_numero'>
		<input type='hidden' name='revenda_complemento'>
		<input type='hidden' name='revenda_bairro'>
		<input type='hidden' name='revenda_cep'>
		<input type='hidden' name='revenda_email'>

		<tr height='20'>
			<td align='right' ><b>Codigo Posto</b>&nbsp;</td>
			<td align='left' ><input type='text' name='codigo_posto' id='codigo_posto2' maxlength='14' value='<? echo $codigo_posto ?>' class='frm' onFocus="nextfield ='nome_posto'" >&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_upload.codigo_posto,document.frm_upload.nome_posto,'codigo')">
			</td>
			<td align='right' ><b>Nome</b>&nbsp;</td>
			<td align='left'><input type='text' name='nome_posto' id='nome_posto2' size='40' maxlength='60' value='<? echo $nome_posto ?>' class='frm' onFocus="nextfield ='condicao'">&nbsp;<img src='../imagens/lupa.png' style='cursor: pointer;' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_upload.codigo_posto,document.frm_upload.nome_posto,'nome')"></td>
		</tr>

		<tr>
			<td align='right' ><b>Arquivo</b>&nbsp;</td>
			<td align='left' colspan='3'><b><input type='file' name='arquivo' size='30' class='frm'></td>
		</tr>
	</table>

	<table class="formulario" align='center' width='100%' border='0'height='40'>
		<tr>
			<td width='50' valign='middle'  align='LEFT' colspan='4'><input type='submit' name='importar' value='Importar'></td>
			<td ><div id='saida' style='display:inline;'><?=$msg_erro?><?=$msg?></div></td>
		</tr>
	</table>


	</form>
	</div>
</div>
</td></tr></table>
<br clear=both>

<? include "rodape.php"; ?>
