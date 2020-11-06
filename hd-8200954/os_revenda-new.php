<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
if($login_fabrica == 1){
    require "classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "classes/form/GeraComboType.php";
} 
$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);

$msg_erro = "";
$qtde_item = 20;

if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);


/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica
				AND    tbl_os_revenda.posto      = $login_posto";
		$res = pg_exec ($con,$sql);
		
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		
		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btn_acao == "gravar")
{
	if (strlen($_POST['sua_os']) > 0){
		$xsua_os = $_POST['sua_os'] ;
		$xsua_os = "000000" . trim ($xsua_os);
		$xsua_os = substr ($xsua_os, strlen ($xsua_os) - 6 , 6) ;
		$xsua_os = "'". $xsua_os ."'";
	}else{
		$xsua_os = "null";
	}

	$xdata_abertura = fnc_formata_data_pg($_POST['data_abertura']);
	$xdata_nf       = fnc_formata_data_pg($_POST['data_nf']);

	
	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	}else{
		$nota_fiscal = trim ($nota_fiscal);
		$nota_fiscal = str_replace (".","",$nota_fiscal);
		$nota_fiscal = str_replace (" ","",$nota_fiscal);
		$nota_fiscal = str_replace ("-","",$nota_fiscal);
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-6,6);
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;
	}
	
	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace (".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace (" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	}else{
		$xrevenda_cnpj = "null";
	}

	if (strlen($_POST['taxa_visita']) > 0)
		$xtaxa_visita = "'". $_POST['taxa_visita'] ."'";
	else
		$xtaxa_visita = "null";

	if (strlen($_POST['regulagem_peso_padrao']) > 0)
		$xregulagem_peso_padrao = "'". $_POST['regulagem_peso_padrao'] ."'";
	else
		$xregulagem_peso_padrao = "null";

	if (strlen($_POST['certificado_conformidade']) > 0)
		$xcertificado_conformidade = "'". $_POST['certificado_conformidade'] ."'";
	else
		$xcertificado_conformidade = "null";

	// Verificação se o nº de série é reincidente
	if ($login_fabrica == 6) {

		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0)." 00:00:00";

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0)." 23:59:59";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto_serie = $_POST["produto_serie_".$i];

			if (strlen($produto_serie) > 0) {
				$sql =	"SELECT os, sua_os, data_digitacao
						FROM    tbl_os
						WHERE   serie = '$produto_serie'
						AND     fabrica = $login_fabrica
						AND     data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>Em caso de dúvida, entre em contato com a Fábrica.";
				}
			}
		}
	}

	if ($login_fabrica == 3) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);
		
		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);
		
		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto_serie = $_POST["produto_serie_".$i];
			
			if (strlen($produto_serie) > 0) {
				$sql = "SELECT  tbl_os.os            ,
								tbl_os.sua_os        ,
								tbl_os.data_digitacao
						FROM    tbl_os
						JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
						WHERE   tbl_os.serie   = '$produto_serie'
						AND     tbl_os.fabrica = $login_fabrica
						AND     tbl_produto.numero_serie_obrigatorio IS TRUE
						AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows($res) > 0) {
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>Em caso de dúvida, entre em contato com a Fábrica.";
				}
			}
		}
	}

	if ($xrevenda_cnpj <> "null") {
		$sql =	"SELECT *
				FROM    tbl_revenda
				WHERE   cnpj = $xrevenda_cnpj";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0){
			$msg_erro = "CNPJ da revenda não cadastrado";
		}else{
			$revenda		= trim(pg_result($res,0,revenda));
			$nome			= trim(pg_result($res,0,nome));
			$endereco		= trim(pg_result($res,0,endereco));
			$numero			= trim(pg_result($res,0,numero));
			$complemento	= trim(pg_result($res,0,complemento));
			$bairro			= trim(pg_result($res,0,bairro));
			$cep			= trim(pg_result($res,0,cep));
			$cidade			= trim(pg_result($res,0,cidade));
			$fone			= trim(pg_result($res,0,fone));
			$cnpj			= trim(pg_result($res,0,cnpj));
			
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
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) == 0){
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

				$res     = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
		
				if (strlen($msg_erro) == 0 and strlen($cliente) == 0) {
					$res     = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) == 0) $cliente = pg_result ($res,0,0);
				}

			}else{
				// pega valor de cliente
				$cliente = pg_result($res,0,cliente);
			}
		}
	}else{
		$msg_erro = "CNPJ não informado";
	}

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	}else{
		$xrevenda_fone = "null";
	}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	}else{
		$xrevenda_email = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen ($os_revenda) == 0) {

			#-------------- insere ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica      ,
						sua_os       ,
						data_abertura,
						data_nf      ,
						nota_fiscal  ,
						cliente      ,
						revenda      ,
						obs          ,
						digitacao    ,
						posto        ,
						contrato     
					) VALUES (
						$login_fabrica                    ,
						$xsua_os                          ,
						$xdata_abertura                   ,
						$xdata_nf                         ,
						$xnota_fiscal                     ,
						$cliente                          ,
						$revenda                          ,
						$xobs                             ,
						current_timestamp                 ,
						$login_posto                      ,
						$xcontrato                        
					)";
		}else{

			$sql = "UPDATE tbl_os_revenda SET
						fabrica       = $login_fabrica                   ,
						sua_os        = $xsua_os                         ,
						data_abertura = $xdata_abertura                  ,
						data_nf       = $xdata_nf                        ,
						nota_fiscal   = $xnota_fiscal                    ,
						cliente       = $cliente                         ,
						revenda       = $revenda                         ,
						obs           = $xobs                            ,
						posto         = $login_posto                     ,
						contrato      = $xcontrato                       
					WHERE os_revenda  = $os_revenda
					AND	 posto        = $login_posto
					AND	 fabrica      = $login_fabrica ";
		}
$msg_debug = $sql."<br>";

		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result ($res,0,0);
			$msg_erro   = pg_errormessage($con);

			// se nao foi cadastrado número da OS Fabricante (Sua_OS)
			if ($xsua_os == 'null' AND strlen($msg_erro) == 0 and strlen($os_revenda) <> 0) {
				$sql = "UPDATE tbl_os_revenda SET
							sua_os        = '$os_revenda'
						WHERE os_revenda  = $os_revenda
						AND	 posto        = $login_posto
						AND	 fabrica      = $login_fabrica ";
$msg_debug .= $sql."<br>";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET
							contrato = $xcontrato
						WHERE cliente  = $revenda";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) > 0) {
				break ;
			}
		}

		if (strlen($msg_erro) == 0) {
			$qtde_item = $_POST['qtde_item'];
			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$referencia         = $_POST["produto_referencia_".$i];
				$codigo_fabricacao  = $_POST["codigo_fabricacao_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];

				if (strlen($embalagem_original) == 0) $embalagem_original = "f";
				if (strlen($sinal_de_uso) == 0)       $sinal_de_uso = "f";

				if (strlen($serie) == 0) {
					$serie = "null";
				}else{
					$serie = "'". $serie ."'";
				}

				if (strlen($type) == 0)
					$type = "null";
				else
					$type = "'". $type ."'";

				if (strlen($msg_erro) == 0) {
					if (strlen ($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace ("-","",$referencia);
						$referencia = str_replace (".","",$referencia);
						$referencia = str_replace ("/","",$referencia);
						$referencia = str_replace (" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql = "SELECT  produto
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   upper(referencia_pesquisa) = $referencia
								AND     tbl_linha.fabrica = $login_fabrica";

						$res = pg_exec ($con,$sql);
						if (pg_numrows ($res) == 0) {
							$msg_erro = "Produto $referencia não cadastrado";
							$linha_erro = $i;
						}else{
							$produto   = pg_result ($res,0,produto);
						}

						if (strlen($capacidade) == 0) {
							$xcapacidade = 'null';
						}else{
							$xcapacidade = "'".$capacidade."'";
						}

						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_revenda_item (
										os_revenda ,
										produto    ,
										serie      ,
										codigo_fabricacao,
										nota_fiscal,
										data_nf    ,
										capacidade ,
										type               ,
										embalagem_original ,
										sinal_de_uso       
									) VALUES (
										$os_revenda           ,
										$produto              ,
										$serie                ,
										$codigo_fabricacao    ,
										$xnota_fiscal         ,
										$xdata_nf             ,
										$xcapacidade          ,
										$type                 ,
										'$embalagem_original' ,
										'$sinal_de_uso'       
									)";
							$res = pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);
							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}
			
			$sql = "SELECT fn_valida_os_revenda($os_revenda,$login_posto,$login_fabrica)";
//echo $sql;
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	}else{
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";
		
		$os_revenda = '';
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if((strlen($msg_erro) == 0) && (strlen($os_revenda) > 0)){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_revenda.nome  AS revenda_nome                                    ,
					tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					tbl_revenda.fone  AS revenda_fone                                    ,
					tbl_revenda.email AS revenda_email                                   
			FROM	tbl_os_revenda
			JOIN	tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN	tbl_fabrica USING (fabrica)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.posto      = $login_posto
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os         = pg_result($res,0,sua_os);
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_nf        = pg_result($res,0,data_nf);
		$nota_fiscal    = pg_result($res,0,nota_fiscal);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_result($res,0,revenda_fone);
		$revenda_email  = pg_result($res,0,revenda_email);
		$obs            = pg_result($res,0,obs);
		$contrato       = pg_result($res,0,contrato);
		
		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'";
		$resX = pg_exec($con, $sql);
		
		if (pg_numrows($resX) == 0) $exclui = 1;
		
		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.posto      = $login_posto
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);
		
		if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda"; 
$layout_menu	= 'os';

include "cabecalho.php";

include "javascript_pesquisas.php" 

?>

<script language="JavaScript">

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

</script>


<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
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

</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
		<? echo $msg_erro ?>
		</font></b>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug;
?>
<? 
if ($ip <> "201.0.9.216" and $ip <> "200.140.205.237" and 1==2) { 
?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> A PÁGINA FOI RETIRADA DO AR PARA QUE POSSAMOS MELHORAR A PERFORMANCE DE LANÇAMENTO.</font></td>
	</tr>
</table>

<? exit; ?>

<? } ?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr class="menu_top">
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> AS ORDENS DE SERVIÇO DIGITADAS NESTE MÓDULO SÓ SERÃO VÁLIDAS APÓS O CLIQUE EM GRAVAR E DEPOIS EM EXPLODIR.</font></td>
	</tr>
</table>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr >
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">

			<!--------------- Formulário ------------------->
			<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
			<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
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
						<input name="sua_os" class="frm" type="text" size="10" maxlength="10" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
					</td>
					<? } ?>
					<td nowrap align='center'>
						<input name="data_abertura" size="12" maxlength="10" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" type="text" class="frm" tabindex="0" <? if ($login_fabrica == 1) echo " readonly";?> > <font face='arial' size='1'> Ex.: <? echo date("d/m/Y"); ?></font>
					</td>
					<td nowrap align='center'>
						<input name="nota_fiscal" size="6" maxlength="6"value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
					</td>
					<td nowrap align='center'>
						<input name="data_nf" size="12" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 25/10/2004</font>
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
						<input class="frm" type="text" name="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
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
<?
	if($login_fabrica == 7){
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Contrato</font>
					</td>
<?
}
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Linhas</font>
					</td>
				</tr>
				<tr>
<?
	if($login_fabrica == 7){
?>
					<td align='center'>
						<input type="checkbox" name="contrato" value="t" <? if ($contrato == 't') echo " checked"?>>
					</td>
<?
}
?>
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
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec ($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

for ($i=0; $i<$qtde_item; $i++) {

	$novo               = 't';
	$os_revenda_item    = "";
	$referencia_produto = "";
	$serie              = "";
	$produto_descricao  = "";
	$capacidade         = "";
	$type               = "";
	$embalagem_original = "";
	$sinal_de_uso       = "";
	$codigo_fabricacao  = "";

	if ($i % 20 == 0) {
		#if ($i > 0) {
		#	echo "<tr>";
		#	echo "<td colspan='5' align='center'>";
		#	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";
			
		#	if (strlen ($os_revenda) > 0 AND strlen($exclui) > 0) {
		#		echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
		#	}
			
		#	echo "</td>";
		#	echo "</tr>";
		#	echo "</table>";
		#}
		
		echo "<table width='650' border='0' cellpadding='0' cellspacing='2' align='center' bgcolor='#ffffff'>";
		echo "<tr class='menu_top'>";
		if ($login_fabrica == 1 ) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cod. Fabricacao</font></td>\n";
		}
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número de série</font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>";
		
		if ($login_fabrica == 7) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Capacidade - Kg</font></td>";
		}

		if ($login_fabrica == 1 ) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Type</font></td>\n";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Embalagem Original</font></td>\n";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Sinal de Uso</font></td>\n";
		}

		echo "</tr>";
	}
	
	if (strlen($os_revenda) > 0){
		if (@pg_numrows($res_os) > 0) {
			$produto = trim(@pg_result($res_os,$i,produto));
		}
		
		if(strlen($produto) > 0){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item ,
							 tbl_os_revenda_item.serie           ,
							 tbl_os_revenda_item.capacidade      ,
							 tbl_os_revenda_item.codigo_fabricacao ,
							 tbl_os_revenda_item.type              ,
							 tbl_os_revenda_item.embalagem_original,
							 tbl_os_revenda_item.sinal_de_uso      ,
							 tbl_produto.referencia              ,
							 tbl_produto.descricao
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda";
//echo $sql;
			$res = pg_exec($con, $sql);
			
			if (@pg_numrows($res) == 0) {
				$novo               = 't';
				$os_revenda_item    = $_POST["item_".$i];
				$referencia_produto = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$produto_descricao  = $_POST["produto_descricao_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
				$codigo_fabricacao  = $_POST["codigo_fabricacao_".$i];
			}else{
				$novo               = 'f';
				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$serie              = pg_result($res,$i,serie);
				$capacidade         = pg_result($res,$i,capacidade);
				$type               = pg_result($res,$i,type);
				$embalagem_original = pg_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
				$codigo_fabricacao  = pg_result($res,$i,codigo_fabricacao);
			}
		}else{
			$novo               = 't';
		}
	}else{
		$novo               = 't';
		$os_revenda_item    = $_POST["item_".$i];
		$referencia_produto = $_POST["produto_referencia_".$i];
		$serie              = $_POST["produto_serie_".$i];
		$produto_descricao  = $_POST["produto_descricao_".$i];
		$capacidade         = $_POST["produto_capacidade_".$i];
		$type               = $_POST["type_".$i];
		$embalagem_original = $_POST["embalagem_original_".$i];
		$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
		$codigo_fabricacao  = $_POST["codigo_fabricacao_".$i];
	}
	
	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

	echo "<tr "; if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
	if ($login_fabrica == 1) echo "<td align='center'><input class='frm' type='text' name='codigo_fabricacao_$i' size='9' maxlength='20' value='$codigo_fabricacao'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20'  value='$serie'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\" style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\")' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' size='50' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\")' style='cursor:pointer;'></td>\n";
	
	if ($login_fabrica == 7) {
		echo "<td align='center'><input class='frm' type='text' name='produto_capacidade_$i' size='9' maxlength='20' value='$capacidade'></td>\n";
	}

	if ($login_fabrica == 1) {
	?>
		<td align='center' nowrap>
		&nbsp;
		    <? 
		     GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("class"=>"frm", "index"=>$i));
      		     echo GeraComboType::getElement();
		    ?>
	
		&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="t" <? if ($embalagem_original == 't' OR strlen($embalagem_original) == 0) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</b></font>
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="f" <? if ($embalagem_original == 'f') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</b></font>
			&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="sinal_de_uso_<? echo $i ?>" value="t" <? if ($sinal_de_uso == 't') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</font>
			<input class='frm' type="radio" name="sinal_de_uso_<? echo $i ?>" value="f" <? if ($sinal_de_uso == 'f'  OR strlen($sinal_de_uso) == 0) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</font>
			&nbsp;
		</td>
	<?
	}

	echo "</tr>\n";

	// limpa as variaveis
	$novo               = '';
	$os_revenda_item    = '';
	$referencia_produto = '';
	$serie              = '';
	$produto_descricao  = '';
	$capacidade         = '';

}

echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<br>";
//echo "<input type='hidden' name='btn_acao' value=''>";
echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";


if (strlen ($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
?>
</form>

<br>

<? include "rodape.php";?>
