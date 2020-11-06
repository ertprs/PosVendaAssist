<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_POST["os"]) > 0) {
	$os = trim($_POST["os"]);
}

if (strlen($_GET["os"]) > 0) {
	$os = trim($_GET["os"]);
}

if (strlen($_POST["produto"]) > 0) $produto = trim($_POST["produto"]);
if (strlen($_GET["produto"])  > 0) $produto = trim($_GET["produto"]);

$sql = "SELECT tbl_fabrica.contrato_manutencao
		FROM   tbl_fabrica
		WHERE  tbl_fabrica.fabrica = $login_fabrica
		AND    tbl_fabrica.contrato_manutencao is true;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$mostra_contrato = true;
}

$btn_acao = trim (strtoupper ($_POST['btn_acao']));

$msg_erro = "";

#------------ Grava dados Adidionais da OS ---------
if ($btn_acao == "CONTINUAR") {
	
	$defeito_reclamado = trim ($_POST['defeito_reclamado']);
	
//if ($ip == '201.0.9.216') echo "[ $defeito_reclamado ] e ".strlen($defeito_reclamado);
//	$os = $_POST['os'];
	
	if (strlen ($defeito_reclamado) == 0 AND $login_fabrica == 5) 
		$defeito_reclamado = "null";
	else if (strlen($defeito_reclamado) == 0 and $login_fabrica <> 5) 
		$msg_erro = "Selecione o defeito reclamado.";

	$x_motivo_troca = trim ($_POST['motivo_troca']);
	if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

	if (strlen($msg_erro) == 0){
		$resX = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os SET
						defeito_reclamado = $defeito_reclamado,
						motivo_troca      = $x_motivo_troca
				WHERE  tbl_os.os      = $os
				and    tbl_os.fabrica = $login_fabrica;";
		$res = @pg_exec ($con,$sql);

		if (strlen (pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage ($con);
		}
	}
$monta_sql .= "1: $sql<br>$msg_erro<br><br>";

	if (strlen($msg_erro) == 0){
		#------------ Atualiza Dados do Consumidor ----------
		$cidade = strtoupper(trim($_POST['consumidor_cidade']));
		$estado = strtoupper(trim($_POST['consumidor_estado']));

		if (strtoupper(trim($_POST['consumidor_revenda'])) == 'C') {
			if (strlen($estado) == 0) $msg_erro .= " Digite o estado do consumidor. <br>";
			if (strlen($cidade) == 0) $msg_erro .= " Digite a cidade do consumidor. <br>";
		}

		$nome	= trim ($_POST['consumidor_nome']) ;

		$cpf    = trim ($_POST['consumidor_cpf']) ;
		$cpf    = str_replace (".","",$cpf);
		$cpf    = str_replace ("-","",$cpf);
		$cpf    = str_replace ("/","",$cpf);
		$cpf    = str_replace (",","",$cpf);
		$cpf    = str_replace (" ","",$cpf);

		if (strlen($cpf) == 0) $xcpf = "null";
		else                   $xcpf = $cpf;

		if ($xcpf <> "null" and strlen($xcpf) <> 11 and strlen ($xcpf) <> 14) {
			$msg_erro = 'Tamanho do CPF/CNPJ do cliente inválido';
		}

		if (strlen($xcpf) > 0 and $xcpf <> "null") $xcpf = "'" . $xcpf . "'";

		$rg     = trim ($_POST['consumidor_rg']) ;

		if (strlen($rg) == 0) $rg = "null";
		else                  $rg = "'" . $rg . "'";

		$fone		= trim ($_POST['consumidor_fone']) ;
		$endereco	= trim ($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 || $login_fabrica == 1) {
			if (strlen($endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br>";
		}
		$numero      = trim ($_POST['consumidor_numero']);
		$complemento = trim ($_POST['consumidor_complemento']) ;
		$bairro      = trim ($_POST['consumidor_bairro']) ;
		$cep         = trim ($_POST['consumidor_cep']) ;

		if ($login_fabrica == 1) {
			if (strlen($numero) == 0) $msg_erro .= " Digite o número do consumidor. <br>";
			if (strlen($bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br>";
		}

		if (strlen($complemento) == 0) $complemento = "null";
		else                           $complemento = "'" . $complemento . "'";

//		if (strlen($cep) == 0) $cep = "null";
//		else                   $cep = "'" . $cep . "'";

		// verifica se está setado

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$cep = str_replace (".","",$cep);
		$cep = str_replace ("-","",$cep);
		$cep = str_replace ("/","",$cep);
		$cep = str_replace (",","",$cep);
		$cep = str_replace (" ","",$cep);
		$cep = substr ($cep,0,8);

		if (strlen($cep) == 0) $cep = "null";
		else                   $cep = "'" . $cep . "'";

		$sql = "UPDATE tbl_os SET
					consumidor_nome        = '$nome'     ,
					consumidor_cpf         = $xcpf       ,
					consumidor_cidade      = '$cidade'   ,
					consumidor_estado      = '$estado'   ,
					consumidor_fone        = '$fone'     ,
					consumidor_endereco    = '$endereco' ,
					consumidor_numero      = '$numero'   ,
					consumidor_cep         = $cep        ,
					consumidor_complemento = $complemento,
					consumidor_bairro      = '$bairro'
				WHERE  tbl_os.os      = $os
				AND    tbl_os.posto   = $login_posto
				AND    tbl_os.fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);
//echo "<!-- \n $sql \n -->";
$monta_sql .= "2: $sql<br>$msg_erro<br><br>";

		if ($login_fabrica == 1 AND strlen ($cpf) == 0) {
			$cpf = 'null';
		}

		//if (strlen($msg_erro) == 0 AND strlen($cpf) > 0 and strlen($cidade) > 0 and strlen($estado) > 0 ) {
		if (strlen($msg_erro) == 0 AND (strlen($xcpf) > 0 OR $xcpf = 'null') and strlen($cidade) > 0 and strlen($estado) > 0 ) {
			$sql = "SELECT fnc_qual_cidade ('$cidade','$estado')";
			$res = @pg_exec ($con,$sql);

			if (strlen (pg_errormessage($con)) > 0) {
				$msg_erro = pg_errormessage ($con);
				if (strpos($msg_erro, 'violates foreign key constraint "estado_fk"') > 0) $msg_erro = 'Estado do consumidor inválido.';
				
			} else {
				$cidade = pg_result ($res,0,0);
			}
			
			$monta_sql .= "3: $sql<br>$msg_erro<br><br>";
		}

		if (strlen($msg_erro) == 0) {
			#------------ Atualiza Dados da Revenda ----------
			$cidade = strtoupper(trim($_POST['revenda_cidade']));
			$estado = strtoupper(trim($_POST['revenda_estado']));

			if (strtoupper(trim($_POST['consumidor_revenda'])) == 'C' AND strtoupper(trim($_POST['consumidor_revenda'])) == 'R') {
				if (strlen($estado) == 0) $msg_erro .= " Digite o estado do consumidor. ";
				if (strlen($cidade) == 0) $msg_erro .= " Digite a cidade do consumidor. ";
			}

			$nome	= trim ($_POST['revenda_nome']) ;
			$cnpj	= trim ($_POST['revenda_cnpj']) ;
			$cnpj   = str_replace (".","",$cnpj);
			$cnpj   = str_replace ("-","",$cnpj);
			$cnpj   = str_replace ("/","",$cnpj);
			$cnpj   = str_replace (",","",$cnpj);
			$cnpj   = str_replace (" ","",$cnpj);

			$fone		= trim ($_POST['revenda_fone']) ;
			$endereco	= trim ($_POST['revenda_endereco']) ;
			$numero		= trim ($_POST['revenda_numero']) ;
			$complemento= trim ($_POST['revenda_complemento']) ;
			$bairro		= trim ($_POST['revenda_bairro']) ;
			$cep		= trim ($_POST['revenda_cep']) ;

			$cep = str_replace (".","",$cep);
			$cep = str_replace ("-","",$cep);
			$cep = str_replace ("/","",$cep);
			$cep = str_replace (",","",$cep);
			$cep = str_replace (" ","",$cep);
			$cep = substr ($cep,0,8);

			$sql = "UPDATE tbl_os SET revenda_nome = '$nome', revenda_cnpj = '$cnpj', revenda_fone = '$fone'
					WHERE  tbl_os.os    = $os
					AND    tbl_os.fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);

			if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);

	$monta_sql .= "8: $sql<br>$msg_erro<br><br>";

			if (strlen ($cnpj) <> 0 AND strlen ($cnpj) <> 14) $msg_erro = 'Tamanho do CNPJ da revenda inválido';

		}

		if (strlen($msg_erro) == 0 AND strlen ($cnpj) > 0 and strlen ($cidade) > 0 and strlen ($estado) > 0 ) {
			$sql = "SELECT fnc_qual_cidade ('$cidade','$estado')";
			$res = pg_exec ($con,$sql);
$monta_sql .= "9: $sql<br>$msg_erro<br><br>";

			$cidade = pg_result ($res,0,0);

			$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$cnpj' LIMIT 1";
			$res1 = pg_exec ($con,$sql);

$monta_sql .= "10: $sql<br>$msg_erro<br><br>";

			if (pg_numrows($res1) > 0) {
				$revenda = pg_result ($res1,0,revenda);
				$sql = "UPDATE tbl_revenda SET
							nome		= '$nome'     ,
							cnpj		= '$cnpj'     ,
							fone		= '$fone'     ,
							endereco	= '$endereco' ,
							numero		= '$numero'   ,
							complemento	= '$complemento' ,
							bairro		= '$bairro' ,
							cep			= '$cep' ,
							cidade		= $cidade
						WHERE tbl_revenda.revenda = $revenda";
				$res3 = @pg_exec ($con,$sql);

				if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);
$monta_sql .= "11: $sql<br>$msg_erro<br><br>";
			}else{
				$sql = "INSERT INTO tbl_revenda (
							nome,
							cnpj,
							fone,
							endereco,
							numero,
							complemento,
							bairro,
							cep,
							cidade
						) VALUES (
							'$nome' ,
							'$cnpj' ,
							'$fone' ,
							'$endereco' ,
							'$numero' ,
							'$complemento' ,
							'$bairro' ,
							'$cep' ,
							$cidade
						)";
				$res3 = @pg_exec ($con,$sql);

				if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);

$monta_sql .= "12: $sql<br>$msg_erro<br><br>";

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = @pg_exec ($con,$sql);
				$revenda = @pg_result ($res3,0,0);
			}

			$sql = "UPDATE tbl_os SET revenda = $revenda WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
$monta_sql .= "13: $sql<br>$msg_erro<br><br>";
		}
	}

	#---------------- Abre janela de Imprimir OS ----------------
	if (strlen ($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");

		$resX = pg_exec ($con,"SELECT os_defeito FROM tbl_fabrica WHERE fabrica = $login_fabrica");
		$os_defeito = pg_result ($resX,0,0);

		if ($os_defeito == 't') {
			header ("Location: os_defeito.php?os=$os&imprimir=1");
			exit;
		}else{

			// Verificação p/ BLACK & DECKER se o produto cadastrado é do tipo COMPRESSOR
          if($ip<>"201.26.145.177"){
			if ($login_fabrica == 1) {
				$sql =	"SELECT tipo_os_cortesia
						FROM  tbl_os
						WHERE fabrica = $login_fabrica
						AND   os = $os;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) == 1) {
					$tipo_os_cortesia = pg_result($res,0,tipo_os_cortesia);
					if ($tipo_os_cortesia == "Compressor") {
						if($login_posto==6359){
							header ("Location: os_item.php?os=$os");
							exit;
						}else{
							header ("Location: os_print_blackedecker_compressor.php?os=$os");
							exit;
						}
					}
				}
			}
          }
			$imprimir_os = $_POST ['imprimir_os'];
			if ($imprimir_os == "imprimir") {
				header ("Location: os_item.php?os=$os&imprimir=1");
				exit;
			}else{
				if ($_POST["troca_faturada"] == "t") {
					header ("Location: os_finalizada.php?os=$os");
					exit;
				}else{
					if($login_fabrica == 20 and $reabrir='ok'){
					header ("Location: os_item.php?os=$os&reabrir=ok");exit;
				}
					header ("Location: os_item.php?os=$os");
					exit;
				}
			}
		}
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


#------------ Le OS da Base de dados ------------#
if (strlen ($os) > 0) {
	
	$sql = "SELECT  tbl_os.sua_os                                                      ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
					tbl_os.serie                                                       ,
					tbl_os.codigo_fabricacao                                           ,
					tbl_os.consumidor_nome                                             ,
					tbl_os.consumidor_cpf                                              ,
					tbl_os.consumidor_fone                                             ,
					tbl_os.consumidor_endereco                                         ,
					tbl_os.consumidor_numero                                           ,
					tbl_os.consumidor_cep                                              ,
					tbl_os.consumidor_cidade                                           ,
					tbl_os.consumidor_estado                                           ,
					tbl_os.consumidor_complemento                                      ,
					tbl_os.consumidor_bairro                                           ,
					tbl_os.revenda_nome                                                ,
					tbl_os.revenda_cnpj                                                ,
					tbl_os.nota_fiscal                                                 ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
					tbl_os.aparencia_produto                                           ,
					tbl_os.acessorios                                                  ,
					tbl_os.defeito_reclamado_descricao                                 ,
					tbl_os.defeito_reclamado                                           ,
					tbl_os.consumidor_revenda                                          ,
					tbl_os.troca_faturada                                              ,
					tbl_os.motivo_troca                                                ,
					tbl_os.cliente                                                     ,
					tbl_produto.produto                                                ,
					tbl_produto.referencia                                             ,
					tbl_produto.linha                                                  ,
					tbl_produto.familia                                                ,
					tbl_produto.descricao                                              ,
					tbl_produto.troca_obrigatoria                                              ,
					tbl_posto_fabrica.codigo_posto                                     
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto_fabrica ON  tbl_os.posto              = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			WHERE   tbl_os.os      = $os 
			AND     tbl_os.posto   = $login_posto 
			AND     tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
//if ($ip == '201.42.90.148 ') echo "<br>".nl2br($sql)."<br>";

	if (pg_numrows($res) == 1) {
		$sua_os                      = pg_result ($res,0,sua_os);
		$data_abertura               = pg_result ($res,0,data_abertura);
		$serie                       = pg_result ($res,0,serie);
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao);
		$consumidor_nome             = pg_result ($res,0,consumidor_nome);
		$consumidor_cpf              = pg_result ($res,0,consumidor_cpf);
		$consumidor_fone             = pg_result ($res,0,consumidor_fone);
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_result ($res,0,consumidor_numero);
		$consumidor_cep              = pg_result ($res,0,consumidor_cep);
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_result ($res,0,consumidor_estado);
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj);
		$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_nome                = pg_result ($res,0,revenda_nome);
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal);
		$data_nf                     = pg_result ($res,0,data_nf);
		$aparencia_produto           = pg_result ($res,0,aparencia_produto);
		$acessorios                  = pg_result ($res,0,acessorios);
		$produto                     = pg_result ($res,0,produto);
		$referencia                  = pg_result ($res,0,referencia);
		$descricao                   = pg_result ($res,0,descricao);
		$troca_obrigatoria                = pg_result ($res,0,troca_obrigatoria);
		$linha                       = pg_result ($res,0,linha);
		$familia                     = pg_result ($res,0,familia);
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado);
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda);
		$troca_faturada              = pg_result ($res,0,troca_faturada);
		$motivo_troca                = pg_result ($res,0,motivo_troca);
		$codigo_posto                = pg_result ($res,0,codigo_posto);
		$cliente                     = pg_result ($res,0,cliente);

		if (strlen($familia) == 0 and $login_fabrica == 14) {
			$sql = "SELECT tbl_produto.familia
					FROM   tbl_subproduto
					JOIN   tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_pai
					WHERE  tbl_subproduto.produto_filho = $produto;";
			$resx = pg_exec ($con,$sql);

			if (pg_numrows($resx) > 0) {
				$familia = pg_result($resx,0,familia);
			}
		}
		#---------------- pesquisa se consumidor já tem cadastro ---------------#

		$cpf = $consumidor_cpf;
		$cpf = str_replace (".","",$cpf);
		$cpf = str_replace ("-","",$cpf);
		$cpf = str_replace ("/","",$cpf);
		$cpf = str_replace (",","",$cpf);
		$cpf = str_replace (" ","",$cpf);

		#---------------- pesquisa se Revenda já tem cadastro ---------------#
		$cnpj = $revenda_cnpj;
		$cnpj = str_replace (".","",$cnpj);
		$cnpj = str_replace ("-","",$cnpj);
		$cnpj = str_replace ("/","",$cnpj);
		$cnpj = str_replace (",","",$cnpj);
		$cnpj = str_replace (" ","",$cnpj);

		if (strlen($cnpj) > 0 OR strlen($revenda) > 0) {
			$sql = "SELECT tbl_revenda.revenda,
					tbl_revenda.nome,
					tbl_revenda.endereco,
					tbl_revenda.numero,
					tbl_revenda.complemento,
					tbl_revenda.bairro,
					tbl_revenda.cep,
					tbl_revenda.fone,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado
					FROM tbl_revenda
					LEFT JOIN tbl_cidade USING (cidade)
					WHERE 1 = 1";
		if (strlen($cnpj) > 0) $sql .= " AND tbl_revenda.cnpj = '$cnpj'";
		if (strlen($revenda) > 0) $sql .= " AND tbl_revenda.revenda = '$revenda'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$revenda_revenda	= trim (pg_result ($res,0,revenda));
				$revenda_nome		= trim (pg_result ($res,0,nome));
				$revenda_fone		= trim (pg_result ($res,0,fone));
				$revenda_endereco	= trim (pg_result ($res,0,endereco));
				$revenda_numero		= trim (pg_result ($res,0,numero));
				$revenda_complemento= trim (pg_result ($res,0,complemento));
				$revenda_bairro		= trim (pg_result ($res,0,bairro));
				$revenda_cep		= trim (pg_result ($res,0,cep));
				$revenda_cidade		= trim (pg_result ($res,0,cidade));
				$revenda_estado		= trim (pg_result ($res,0,estado));
			}
		}

		// Verifica se o status da OS for 62 (intervencao da fabrica) // fabio 02/01/2007
		if ($login_fabrica == 3 AND 1==1){
			$sql = "SELECT  status_os
					FROM    tbl_os_status
					WHERE   os = $os
					ORDER BY data DESC LIMIT 1";
			$res = @pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				$os_intervencao_fabrica = trim(pg_result($res,0,status_os));
				if ($os_intervencao_fabrica == '62') {
					$os_intervencao='t';
					$msg_intervencao = "<br>A produto $referencia necessita de troca.";
					header("Location: os_finalizada.php?os=$os");
					exit();
				}
				if ($os_intervencao_fabrica == '65') {
					$os_intervencao='t';
					$msg_intervencao = "<br>A produto $referencia necessita de troca.";
					header("Location: os_press.php?os=$os");
					exit();
				}
			}
		}

	}
}

$title = "Dados Adicionais da Ordem de Serviço";
if($sistema_lingua) $title="Datos adicionales de órdenes de servicio";
$layout_menu = 'os';
include "cabecalho.php";
?>

<? include "javascript_pesquisas.php" ?>

<script language='javascript'>

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;

		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

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

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	//janela.rg			= document.frm_os.consumidor_rg;
	janela.focus();
}


</script>


<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<style type="text/css">

.txt {
		font: x-small Arial, Verdana, Geneva, Helvetica, sans-serif;
		font-weight: bold;
		text-align: center;
		color: #000000;
}

.top {
		font: x-small Arial, Verdana, Geneva, Helvetica, sans-serif;
		font-weight: bold;
		text-align: center;
		background-color: #D9E2EF;
		color: #000000;
}

.txt1 {
		font: x-small Arial, Verdana, Geneva, Helvetica, sans-serif;
		font-weight: bold;
		text-align: center;
		color: #000000;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	font-weight: normal;
}
</style>

<?
##### COMUNICADOS - INÍCIO #####
$sql =	"SELECT tbl_comunicado.comunicado                                       ,
				tbl_comunicado.descricao                                        ,
				tbl_comunicado.mensagem                                         ,
				tbl_comunicado.extensao                                         ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
				tbl_comunicado.produto                                          ,
				tbl_produto.referencia                    AS produto_referencia ,
				tbl_produto.descricao                     AS produto_descricao
		FROM tbl_comunicado
		JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		JOIN tbl_os      ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_comunicado.fabrica = $login_fabrica
		AND   tbl_os.os = $os
		AND   tbl_comunicado.obrigatorio_os_produto IS TRUE
		ORDER BY tbl_comunicado.data DESC;";

$res_comun = pg_exec($con,$sql);

if (pg_numrows($res_comun) > 0){
	echo "<br>";
	echo "<div id='mainCol'>";
	echo "<div class='contentBlockLeft' style='background-color: #FFCC00;'>";
	echo "<table>";
	echo "<tr><td><img src='imagens/esclamachion1.gif'></td>";
	echo "<td align='center'><b>Comunicado referente ao produto<br>";
	echo pg_result($res_comun,0,produto_referencia) . " - " . pg_result($res_comun,0,produto_descricao) . "</b></td></tr>";
	echo "</table>";
	echo "<br>";
	echo "<table width='400' border='1' cellspadding='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo'>";
	echo "<td>Data</td>";
	echo "<td>Título</td>";
	echo "<td>Arquivo</td>";
	echo "</tr>";
	for ($k = 0 ; $k < pg_numrows($res_comun) ; $k++) {
		$cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td>" . pg_result($res_comun,$k,data) . "</td>";
		echo "<td><a href='comunicado_mostra.php?comunicado=" . pg_result($res_comun,$k,comunicado) . "' target='_blank'>" . pg_result($res_comun,$k,descricao) . "</a></td>";
		echo "<td align='center'>";
		if (strlen(pg_result($res_comun,$k,comunicado)) > 0 && strlen(pg_result($res_comun,$k,extensao)) > 0) echo "<a href='../comunicados/" . pg_result($res_comun,$k,comunicado) . "." . pg_result($res_comun,$k,extensao) . "' targer='_blank'>Abrir arquivo</a>";
		else "&nbsp;";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "</div>";
	echo "</div>";
	echo "<br>";
}
##### COMUNICADOS - FIM #####
?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
<?
if (strlen ($msg_erro) > 0) {
	echo $msg_erro;

	$consumidor_cidade		= $_POST['consumidor_cidade'];
	$consumidor_estado		= $_POST['consumidor_estado'];

	$consumidor_nome		= trim ($_POST['consumidor_nome']) ;
	$consumidor_fone		= trim ($_POST['consumidor_fone']) ;
	$consumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
	$consumidor_numero		= trim ($_POST['consumidor_numero']) ;
	$consumidor_complemento	= trim ($_POST['consumidor_complemento']) ;
	$consumidor_bairro		= trim ($_POST['consumidor_bairro']) ;
	$consumidor_cep			= trim ($_POST['consumidor_cep']) ;
	$consumidor_rg			= trim ($_POST['consumidor_rg']) ;
	$consumidor_cpf			= trim ($_POST['consumidor_cpf']) ;
	$revenda_nome			= trim ($_POST['revenda_nome']) ;
	$revenda_fone			= trim ($_POST['revenda_fone']) ;
	$revenda_endereco		= trim ($_POST['revenda_endereco']) ;
	$revenda_numero			= trim ($_POST['revenda_numero']) ;
	$revenda_complemento	= trim ($_POST['revenda_complemento']) ;
	$revenda_bairro			= trim ($_POST['revenda_bairro']) ;
	$revenda_cep			= trim ($_POST['revenda_cep']) ;
	$consumidor_contrato	= trim ($_POST['consumidor_contrato']) ;

	$troca_faturada			= trim ($_POST['troca_faturada']) ;
	$motivo_troca			= trim ($_POST['motivo_troca']) ;
	$defeito_reclamado 		= trim($_POST['defeito_reclamado']);
	
}
?>
		</font></b>
	</td>
</tr>
</table>

<!-- ------------- Formulário ----------------- -->
<!-- ------------- INFORMAÇÕES DA ORDEM DE SERVIÇO------------------ -->

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="os" value="<? echo $os ?>">
<input type="hidden" name="produto" value="<? echo $produto ?>">
<input type="hidden" name="cliente" value="<? echo $consumidor_cliente ?>">
<input type="hidden" name="revenda" value="<? echo $revenda_revenda ?>">
<input type="hidden" name="consumidor_revenda" value="<? echo $consumidor_revenda ?>">

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr bgcolor='#cccccc'>
	<td class="top"><? if($sistema_lingua) echo "Informaciones adicionales sobre las órdenes de servicio";else echo "Informações sobre a Ordem de Serviço";?></td>
</tr>
</table>


<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt"><? if($sistema_lingua) echo "OS Fabricante";else echo "OS Fabricante";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Abertura";else echo "Abertura";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Producto";else echo "Produto";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Nº Serie";else echo "Nº Série";?></td>

<? if ($login_fabrica == 1) { ?>
	<td class="txt">Cód. Fabricação</td>
<? } ?>

</tr>

<tr>
	<td class="txt1"><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; if($login_fabrica==20)echo $os; ?></td>

	<td  class="txt1"><? echo $data_abertura ?></td>

	<td  class="txt1"><? echo $referencia . " - " . substr ($descricao,0,15) ?></td>

	<td  class="txt1"><? echo $serie ?></td>

<? if ($login_fabrica == 1) { ?>
	<td  class="txt1"><? echo $codigo_fabricacao ?></td>
<? } ?>

</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt"><? if($sistema_lingua) echo "Aparencia del producto";else echo "Aparência do Produto";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Accesorios";else echo "Acessórios";?></td>

	<?
	$sql = "SELECT  defeito_constatado_por_familia,
					defeito_constatado_por_linha
			FROM    tbl_fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica";
	#if ($ip == '201.0.9.216') echo $sql;
	$res = pg_exec ($con,$sql);
	$defeito_constatado_por_familia = pg_result ($res,0,0) ;
	$defeito_constatado_por_linha   = pg_result ($res,0,1) ;
	
	$sql = "SELECT familia FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto WHERE tbl_os.os = $os";
	$resX = pg_exec ($con,$sql);
	$familia = @pg_result ($resX,0,0);
	if (strlen ($familia) == 0) $familia = "0";

	if ($login_fabrica <> 5) {
		$defeito_constatado_fabrica = "NAO";
		
		if ($defeito_constatado_por_familia == 't') {
			$defeito_constatado_fabrica = "SIM";
			
			if ($login_fabrica <> 19) {
				$sql = "SELECT   *
						FROM     tbl_defeito_reclamado
						JOIN     tbl_familia USING (familia)
						WHERE    tbl_defeito_reclamado.familia = $familia
						AND      tbl_familia.fabrica           = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
				
			
				if (pg_numrows ($resD) == 0) {
					$sql = "SELECT   *
							FROM     tbl_defeito_reclamado
							JOIN     tbl_familia USING (familia)
							WHERE    tbl_familia.fabrica = $login_fabrica
							ORDER BY tbl_defeito_reclamado.descricao;";
					$resD = pg_exec ($con,$sql) ;
				}
				if($login_fabrica == 15){//Modificado por Fernando chamado 1232
					$sql = "SELECT	tbl_defeito_reclamado.descricao, 
									tbl_defeito_reclamado.defeito_reclamado
							FROM tbl_produto
							JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia 
									AND tbl_familia.fabrica = $login_fabrica
							JOIN tbl_linha ON tbl_linha.linha=tbl_produto.linha 
									AND tbl_linha.fabrica = $login_fabrica
							JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.familia = tbl_familia.familia 
								AND tbl_produto.linha =tbl_defeito_reclamado.linha
							WHERE tbl_produto.produto=$produto; ";
					$resD = pg_exec ($con,$sql) ;
					if (pg_numrows ($resD) == 0) {
					$sql = "SELECT	tbl_defeito_reclamado.descricao, 
									tbl_defeito_reclamado.defeito_reclamado
							FROM tbl_produto
							JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia 
									AND tbl_familia.fabrica = $login_fabrica
							JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.familia = tbl_familia.familia 
							WHERE tbl_produto.produto = $produto; ";
					$resD = pg_exec ($con,$sql) ;
					}
				}
			}else{
				$sql = "SELECT   *
						FROM     tbl_familia_defeito_reclamado
						JOIN     tbl_defeito_reclamado   ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
						AND tbl_defeito_reclamado.fabrica           = $login_fabrica
						JOIN     tbl_familia     ON tbl_familia.familia   = tbl_familia_defeito_reclamado.familia
						AND tbl_familia.fabrica = $login_fabrica
						WHERE    tbl_familia.familia = $familia
						ORDER BY trim(tbl_defeito_reclamado.codigo)::numeric;";
				$resD = pg_exec ($con,$sql) ;
			}
		}
		
		if ($defeito_constatado_por_linha == 't') {
			$defeito_constatado_fabrica = "SIM";
			
			$sql = "SELECT   *
					FROM     tbl_defeito_reclamado
					JOIN     tbl_linha USING (linha)
					WHERE    tbl_defeito_reclamado.linha = $linha
					AND      tbl_linha.fabrica           = $login_fabrica
					ORDER BY tbl_defeito_reclamado.descricao;";
			$resD = pg_exec ($con,$sql) ;
			
			//takashi 31/07/2006 a pedido do leandro tectoy, somente defeitos constatados como RECLAMACAO deve aparecer
			if ($login_fabrica == 6) {
				$sql = "SELECT
					defeito_reclamado, 
					descricao 
					FROM tbl_defeito_reclamado 
					JOIN   tbl_linha USING (linha) 
					WHERE  tbl_defeito_reclamado.linha = $familia 
					AND duvida_reclamacao='RC'
					AND tbl_linha.fabrica = $login_fabrica 
					ORDER BY tbl_defeito_reclamado.descricao";
			$resD = pg_exec ($con,$sql);
			}
			//takashi 31/07/2006 a pedido do leandro tectoy, somente defeitos constatados como RECLAMACAO deve aparecer
			if (pg_numrows ($resD) == 0) {
				$sql = "SELECT   *
						FROM     tbl_defeito_reclamado
						JOIN     tbl_linha USING (linha)
						WHERE    tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_defeito_reclamado.descricao;";
				$resD = pg_exec ($con,$sql) ;
			}
		}
		
		if ($defeito_constatado_fabrica == "NAO") {
			$sql = "SELECT   *
					FROM     tbl_defeito_reclamado
					JOIN     tbl_linha using (linha)
					WHERE    tbl_linha.fabrica = $login_fabrica";
			
			//lenoxx não filtra por família
			if ($login_fabrica <> 11) { $sql = " AND      tbl_linha.linha   = $linha"; }
					//a pedido do leandro tectoy, aparecerá somente RECLAMACAO para posto - TAKASHI 31/7/2006
			if ($login_fabrica == 6) { $sql .= " AND duvida_reclamacao='RC'";}
			
			$sql .= " ORDER BY tbl_defeito_reclamado.descricao;";
					//a pedido do leandro tectoy, aparecerá somente RECLAMACAO para posto - TAKASHI 31/7/2006
			$resD = @pg_exec ($con,$sql) ;
		}
		//takashi 17/10
	/*	 if($login_fabrica==24){
			$sql = "SELECT   *
				FROM     tbl_defeito_reclamado
				WHERE    fabrica = $login_fabrica order by descricao";
						$resD = @pg_exec ($con,$sql) ;
}  */
  //takashi 17/10
 		echo "$sql";
		if (@pg_numrows ($resD) > 0) {
			echo "<td class='txt'>";
			if($sistema_lingua) echo "Falla reclamada";else echo "Defeito Reclamado";
			echo "</td>";
		}
	}else{
		echo "<td class='txt'>";
		 if($sistema_lingua) echo "Falla reclamada";else echo "Defeito Reclamado";
		echo "</td>";
	}
	?>

</tr>
		
<tr>
	<td class="txt1"><? echo $aparencia_produto ?></td>

	<td class="txt1"><? echo $acessorios ?></td>

	<?
	if (@pg_numrows ($resD) > 0 and $login_fabrica <> 5) {
		echo "<td class='txt1'><select name='defeito_reclamado' size='1' class='frm'>\n";
		echo "<option value='' selected></option>\n";
		for ($i = 0 ; $i < pg_numrows ($resD) ; $i++ ) {
			echo "<option ";
			if ($defeito_reclamado == pg_result ($resD,$i,defeito_reclamado) ) echo " selected ";
			echo " value='" . pg_result ($resD,$i,defeito_reclamado) . "'>" ;
			echo pg_result ($resD,$i,descricao) ;
			//takashi 31/07
// 			echo " - ";
// 			echo pg_result($resD, $i, duvida_reclamacao);
			//takashi 31/07
			echo "</option>\n";
		}
		echo "</select>\n";
	}else{
		echo "<td class='txt1'>";
		echo $defeito_reclamado_descricao;
	}
	?>
	</td>
</tr>

</table>

<p>

<? if (1 == 1) { ?>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="top"><? if($sistema_lingua) echo "Informaciones sobre el consumidor";else echo "Informações sobre o Consumidor";?></td>
</tr>
</table>


<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt"><? if($sistema_lingua) echo "Nombre";else echo "Nome";?></td>

	<td class="txt"><? if($sistema_lingua) echo "ID distribuidor";else echo "CPF/CNPJ";?></td>

<!--	<td class="txt">RG/IE</td>   -->

	<? if ($mostra_contrato == true) echo "<td class=\"txt\">Contrato</td>"; ?>

	<td class="txt"><? if($sistema_lingua) echo "Teléfono";else echo "Fone";?></td>

	<td class="txt"><? if($sistema_lingua) echo "<font size='-2'>AP. POSTAL</font>";else echo "CEP";?></td>
</tr>

<tr>
	<td class="txt1">
		<input type='hidden' name='consumidor_cliente' value = ''>
		<input class="frm" type="text" name="consumidor_nome" size="35" maxlength="50" value="<? echo $consumidor_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")' style="cursor:pointer;">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_cpf"   size="15" maxlength="18" value="<? echo $consumidor_cpf ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")' style="cursor:pointer;">
	</td>
<!--
	<td class="txt1">
		<input class="frm" type="text" name="consumidor_rg"   size="15" maxlength="30" value="<? echo $consumidor_rg ?>" >
	</td>
-->
	<?
	if ($mostra_contrato == true) {
		echo "<td class=\"txt1\">";
		echo "<input class=\"frm\" type=\"checkbox\" name=\"consumidor_contrato\" value=\"t\""; if ($consumidor_contrato == 't') echo " checked "; echo ">";
		echo "</td>";
	}
	?>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');">
	</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt"><? if($sistema_lingua) echo "Dirección";else echo "Endereço";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Número";else echo "Número";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Compl.";else echo "Compl.";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Barrio";else echo "Bairro";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Ciudad";else echo "Cidade";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Estado";else echo "Estado";?></td>
</tr>

<tr>
	<td class="txt1">
		<input class="frm" type="text" name="consumidor_endereco"   size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_numero"   size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_complemento"   size="15" maxlength="30" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_cidade"   size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
	</td>
</tr>

</table>

<p>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td  class="top"><? if($sistema_lingua) echo "Informaciones sobre el distribuidor";else echo "Informações sobre a Revenda";?></td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt"><? if($sistema_lingua) echo "ID distribuidor 1";else echo "Razão Social";?></td>

	<td class="txt"><? if($sistema_lingua) echo "ID distribuidor 2";else echo "CNPJ";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Teléfono";else echo "Fone";?></td>

	<td class="txt"><? if($sistema_lingua) echo "<font size='-2'>APARTADO POSTAL</font>";else echo "CEP";?></td>
</tr>

<tr>
	<td class="txt1">
			<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style="cursor:pointer;">
	</td>

	<td class="txt1">
			<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style="cursor:pointer;">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_fone"   size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_cep"   size="10" maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
	</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt"><? if($sistema_lingua) echo "Dirección";else echo "Endereço";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Número";else echo "Número";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Compl.";else echo "Compl.";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Barrio";else echo "Bairro";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Cuidad";else echo "Cidade";?></td>

	<td class="txt"><? if($sistema_lingua) echo "Estado";else echo "Estado";?></td>

</tr>

<tr>
	<td class="txt1">
		<input class="frm" type="text" name="revenda_endereco"   size="30" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_numero"   size="10" maxlength="20" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_complemento"   size="15" maxlength="30" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_bairro"   size="15" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_cidade"   size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');">
	</td>

	<td class="txt1">
		<input class="frm" type="text" name="revenda_estado"   size="2" maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');">
	</td>

</tr>

<input type="hidden" name="revenda_email" value="">

</table>

<? if (strlen($troca_faturada) > 0) { ?>

<p>
<input type="hidden" name="troca_faturada" value="<?echo $troca_faturada?>">

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td  class="top">Informações sobre a Troca Faturada</td>
</tr>
</table>

<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td class="txt">Motivo Troca</td>
</tr>
<tr>
	<td class="txt1">
		<select name="motivo_troca" size="1" style='width:550px'>
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
			echo pg_result ($res,$i,codigo) ." - ". pg_result ($res,$i,descricao) ;
			echo "</option>";
		}
		?>
		</select>
	</td>
</tr>
</table>

<? } ?>

<? } # Final do IF das Fabricas que pedem estes dados ?>


<p>

<input type='hidden' name='btn_acao' value=''>
<center>
<?
if ($login_fabrica != 1) {
	echo "<input type='checkbox' name='imprimir_os' value='imprimir'>";
	if($sistema_lingua) echo "Imprimir OS";else echo "Imprimir OS";
}else{
	echo "<img border='0' src='imagens/btn_voltar.gif' onclick=\"javascript: location.href='os_cadastro.php?os=$os';\" ALT='Voltar' style='cursor: hand;'>";
}
?>
&nbsp;&nbsp;&nbsp;&nbsp;
<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style="cursor:pointer;">
</center>

</form>

<p>

<p>
<? include "rodape.php";?>
