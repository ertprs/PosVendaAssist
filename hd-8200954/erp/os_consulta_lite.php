<?
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include "autentica_usuario_empresa.php";
include "funcoes.php";

include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);



if (strlen($_GET['fabrica']) > 0) {
	$fabrica = trim($_GET['fabrica']);
}

if ($HTTP_COOKIE_VARS['cook_fabrica'] != $fabrica){
	if (strlen($fabrica)>0){
		$sql = "SELECT	tbl_posto_fabrica.fabrica,
						tbl_posto_fabrica.codigo_posto,
						tbl_fabrica.nome,
						tbl_posto_fabrica.oid as posto_fabrica
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND tbl_posto.posto = $login_posto";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			$msg_erro .= "Erro. Seu posto não está credenciado a esta Fábrica.";
			$fabrica= "";
		}else{
			$fabrica       = trim(pg_result ($res,0,fabrica));
			$nome_fabrica  = trim(pg_result ($res,0,nome));
			$posto_fabrica = trim(pg_result ($res,0,posto_fabrica));
			// setcookie ("cook_posto_fabrica",$posto_fabrica);
			// setcookie ("cook_posto",$login_posto);
			// setcookie ("cook_fabrica",$fabrica);

			add_cookie($cookie_login,"cook_posto_fabrica",$posto_fabrica);
			add_cookie($cookie_login,"cook_posto",$login_posto);
			add_cookie($cookie_login,"cook_fabrica",$fabrica);
			
			set_cookie_login($token_cookie,$cookie_login);
			
			echo "<script languague='javascript'>window.location='$PHP_SELF?fabrica=$fabrica'</script>";
			exit;
		}
	}
}

include 'autentica_usuario_assist.php';


/*
if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);
*/

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0) $btn_acao = strtoupper($_GET["btn_acao"]);

# ---- excluir ---- #
$os = $_GET['excluir'];

if (strlen ($os) > 0) {
	$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
}


# ---- fechar ---- #
$os = $_GET['fechar'];
if (strlen ($os) > 0) {
//	include "ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT status_os FROM tbl_os_status WHERE os = $os AND status_os IN (62,64,65,72,73) ORDER BY data DESC LIMIT 1";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		$status_os = trim(pg_result($res,0,status_os));
		if ($status_os=="72" || $status_os=="62"){
			$msg_erro .="OS com intervenção. Não pode ser fechada.";
		}
	}

	$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con) ;
		if (strlen ($msg_erro) == 0 and $login_fabrica==1) {
			$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		/* LENOXX VERIFICA SE TEM PEÇA PENDENTE E DISPARA EMAIL */
		/*
		if ($login_fabrica==11 and strlen ($msg_erro) == 0) {
			$sqlc = "SELECT tbl_posto.cnpj||' - '||tbl_posto.nome as posto,
							tbl_os.sua_os,
							to_char(tbl_os.finalizada::timestamp,'DD/MM/YYYY HH24:MI:SS') as finalizada,
							tbl_os_item.pedido,
							tbl_peca.referencia||' - '||tbl_peca.descricao as peca
					FROM tbl_os
					JOIN tbl_os_produto using(os)
					JOIN tbl_os_item using(os_produto)
					JOIN tbl_pedido_item using(pedido_item)
					JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca
					JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.os      = $os
					AND   tbl_os.finalizada notnull
					AND   tbl_os_item.peca = tbl_pedido_item.peca
					AND   tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada";
			$resc = pg_exec($con, $sqlc);

			if (pg_numrows($resc) > 0 and strlen(pg_result($resc,0,0)) > 0) {
				$sua_osc     = pg_result($resc,0,sua_os);
				$finalizadac = pg_result($resc,0,finalizada);
				$postoc      = pg_result($resc,0,posto);

				$remetente    = "TELECONTROL <wellington@telecontrol.com.br>";
				$destinatario = "wellington@telecontrol.com.br";
				$assunto      = "FECHAMENTO DE OS ($sua_osc) COM PEÇA PENDENTE";

				$info_pendencia = "";
				//pega as informações da pendencia
				for ($c=0; $c<pg_numrows($resc); $c++) {
					$pedidoc     = pg_result($resc,$c,pedido);
					$pecac       = pg_result($resc,$c,peca);

					$info_pendencia .= "peca $pecac no pedido $pedidoc<BR> Link: <a href='http://www.telecontrol.com.br/assist/admin/pedido_cadastro.php?pedido=$pedidoc'>http://www.telecontrol.com.br/assist/admin/pedido_cadastro.php?pedido=$pedidoc</a>.<BR><BR>";
				}

				$mensagem  = "Posto: $postoc<BR><BR>";
				$mensagem .= "OS $sua_osc FINALIZADA NO SITE em  $finalizadac, MAS CONTÉM A(S) SEGUINTE(S) PEÇA(S) PENDENTE(S):<BR><BR>".$info_pendencia;
				$mensagem .= "Acesse o link para abrir o pedido de peças e efetuar cancelamento ou alterações se necessário(é necessário estar logado no site como administrador antes de clicar no link).";
				$headers="Return-Path: <wellington@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";

				@mail($destinatario,$assunto,$mensagem,$headers);
			}
		}
		*/
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "ok;XX$os";
	}else{
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}
	flush();
	exit;
}


$msg = "";


if($sistema_lingua == 'ES') $meses = array(1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
else                        $meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");






if (strlen($btn_acao) > 0 ) {
	$os_off    = trim (strtoupper ($_POST['os_off']));
	$codigo_posto_off       = trim(strtoupper($_POST['codigo_posto_off']));
	$posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));






	$sua_os = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os) == 0)
		$sua_os    = trim (strtoupper ($_GET['sua_os']));


	if(strlen($sua_os)>0 AND strlen($sua_os)<4){
	if ($sistema_lingua=='ES')
		$msg="Favor digitar el minimo de 4 (cuatro) caracteres";
	else
		$msg="Favor digitar no minimo 4(quatro) caracteres";
	}
	$serie     = trim (strtoupper ($_POST['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	$consumidor_nome    = trim(strtoupper($_POST['consumidor_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	$os_aberta          = trim(strtoupper($_POST['os_aberta']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));

	if ($login_e_distribuidor <> 't') $codigo_posto = $login_codigo_posto ;

/*	if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 ) {
		$msg = "Especifique o nome";
	}
*/
	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
	if ($sistema_lingua=='ES')
			$msg = "Tamaño del CPF del consumidor invalido";
	else
			$msg = "Tamanho do CPF do consumidor inválido";
	}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
		if ($sistema_lingua=='ES')
			$msg = "Digite los 8 primeros dígitos del CNPJ";
	else
			$msg = "Digite os 8 primeiros dígitos do CNPJ";
	}

	if (strlen ($nf_compra) > 0 ) {
		$nf_compra = "000000" . $nf_compra;
		$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
	}

/*
	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) )  {
		$msg = "Digite o mês e o ano para fazer a pesquisa";
	}
*/

	if ( strlen ($sua_os) == 0 AND strlen ($serie) == 0 AND strlen ($nf_compra) == 0 AND strlen ($consumidor_cpf) == 0 AND  strlen ($mes) == 0 AND strlen ($ano) == 0 )  {
		if ($sistema_lingua=='ES')
			$msg = "Elija el mes y año para hacer la busca";
		else
			$msg = "Selecione o mês e o ano para fazer a pesquisa";
	}
/*
	if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($os_aberta) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) ) {
		$msg = "Especifique mais um campo para a pesquisa";
	}
*/
	if (strlen ($mes) == 0 AND strlen ($ano) > 0) {
		if ($sistema_lingua=='ES')
			$msg = "Elija el mes";
		else
			$msg = "Selecione o mês";
	}

	if ( strlen ($posto_nome) > 0 AND strlen ($posto_nome) < 5 ) {
		if ($sistema_lingua=='ES')
			$msg = "Digite el mínimo 5 letras para el nombre del servicio";
		else
			$msg = "Digite no mínimo 5 letras para o nome do posto";
	}

	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 5) {
		if ($sistema_lingua=='ES')
			$msg = "Digite el mínimo 5 letras para el nombre del consumidor ";
		else
			$msg = "Digite no mínimo 5 letras para o nome do consumidor";
	}

	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		if ($sistema_lingua=='ES')
			$msg = "Digite el mínimo 5 letras para el numero de serie Servicio no encuentrado";
		else
			$msg = "Digite no mínimo 5 letras para o número de série";
	}





	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

	if (strlen($msg) == 0 && strlen($opcao2) > 0) {
		if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
		if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
		if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
		if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
		if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto.posto                ,
							tbl_posto.nome                 ,
							tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = trim(pg_result($res,0,posto));
				$posto_codigo = trim(pg_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_result($res,0,nome));
			}else{
				$erro .= " Posto não encontrado. ";
			}
		}
	}
}

$layout_menu = "os";

if($sistema_lingua == 'ES') $title = "Seleción de Parámetros para Relación  de Órdenes de Servicio digitadas";
else                        $title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
include "menu.php";
?>

<style type="text/css">
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
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

<script language='javascript' src='ajax.js'></script>


<script language='javascript'>

function retornaFechamentoOS (http , sinal, excluir, lancar) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				if (results[0] == 'ok') {
					alert ('OS ' + results[0] + ' fechada com sucesso' );
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					lancar.src='/assist/imagens/pixel.gif';
				}else{
					if (http.responseText.indexOf ('de-obra para instala') > 0) {
						alert ('Esta OS não tem mão-de-obra para instalação');
					}else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
						alert ('Erro no Fechamento da OS. \nPor favor utilizar a tela de Fechamento de OS para informar a Nota Fiscal de Devolução.');
					}else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
						alert ('Esta OS não tem mão-de-obra para este atendimento');
					}else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
						alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, aparência e acessórios, na tela de lançamento de itens.');
					}else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
						alert ('Type informado para o produto não é válido');
					} else {
						alert ('Erro no Fechamento da OS. \nPor favor, verifique os dados digitados, defeito constatado e solução, na tela de lançamento de itens.');
					}
				}
			}else{
				alert ('Fechamento nao processado');
			}
		}
	}
}

function fechaOS (os , sinal , excluir , lancar ) {
	url = "<?= $PHP_SELF ?>?fechar=" + escape(os) ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
	http.send(null);
}

</script>


<br>




<?
if (strlen($msg) > 0) {
	echo "<h1>$msg</h1>";
}

if (strlen($msg_erro) > 0) {
	echo "<font face='arial' size='+1' color='#FF6633'><b>$msg_erro</b></font>";
}


if (strlen($btn_acao) > 0 AND strlen($msg) == 0) {

		if ($login_e_distribuidor <> 't') {
			$posto = $login_posto ;
		}

		$join_especifico = " FROM tbl_os ";
		$especifica_mais_1 = "1=1";
		$especifica_mais_2 = "1=1";

		if (strlen ($data_inicial) > 0) {
			if (strlen ($produto_referencia) > 0) {
				$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
				$resX = pg_exec ($con,$sqlX);
				$produto = pg_result ($resX,0,0);
				$especifica_mais_1 = "tbl_os.produto = $produto";
			}

			if (strlen($os_aberta) > 0) {
				$especifica_mais_2 = "tbl_os.os_fechada IS FALSE";
			}

			$join_especifico = "FROM (  SELECT os
										FROM tbl_os
										JOIN tbl_os_extra USING (os)
										JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
										JOIN (SELECT posto FROM tbl_posto_linha JOIN tbl_linha USING (linha)
												WHERE tbl_linha.fabrica = $login_fabrica
												AND tbl_posto_linha.distribuidor = $login_posto
												UNION
												SELECT $login_posto
										) posto ON tbl_os.posto = posto.posto
										WHERE fabrica = $login_fabrica
										AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
										AND   $especifica_mais_1
										AND   $especifica_mais_2
								) oss JOIN tbl_os ON tbl_os.os = oss.os";
		}

		// OS não excluída
		$sql =  "SELECT distinct tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						sua_os_offline                                                    ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.tecnico_nome                                               ,
						tbl_os.admin                                                      ,
						tbl_os.os_reincidente                      AS reincidencia        ,
						tbl_os.valores_adicionais                                         ,
						tbl_os.nota_fiscal_saida                                          ,
						tbl_tipo_atendimento.descricao                                    ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib     ,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os
				$join_especifico
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
				//colocado takashi
		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
//TULIO
		$sql .=	"
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_tipo_atendimento      ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				WHERE tbl_os.fabrica = $login_fabrica
				AND   (tbl_os.posto  = $login_posto OR (tbl_posto_linha.distribuidor = $login_posto AND tbl_posto_linha.distribuidor IS NOT NULL ))";
		if($login_fabrica <>3) $sql .=" AND   tbl_os.excluida IS NOT TRUE AND  (status_os NOT IN (13,15) OR status_os IS NULL)";

		$sql .= "AND  (status_os NOT IN (13,15) OR status_os IS NULL)";


#				AND   (tbl_os.posto   = $login_posto OR tbl_os_extra.distribuidor = $login_posto)

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}

		if (strlen($posto_nome) > 0) {
			$posto_nome = strtoupper ($posto_nome);
			$sql .= " AND tbl_posto.nome LIKE '$posto_nome%' ";
		}
		if (strlen($codigo_posto) > 0) {
			$sql .= " AND (tbl_posto_fabrica.codigo_posto = '$codigo_posto' OR (distrib.codigo_posto = '$codigo_posto' AND distrib.codigo_posto IS NOT NULL ))";
		}

		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
		}

		if (strlen($admin) > 0) {
			$sql .= " AND tbl_os.admin = '$admin' ";
		}

		if (strlen($sua_os) > 0) {
			if ($login_fabrica == 1) {
				$pos = strpos($sua_os, "-");
				if ($pos === false) {
					$pos = strlen($sua_os) - 5;
				}else{
					$pos = $pos - 5;
				}
				$sua_os = substr($sua_os, $pos,strlen($sua_os));
			}
			$sua_os = strtoupper ($sua_os);
#			$sql .= " AND tbl_os.sua_os LIKE '%$sua_os%'";
			#$sql .= " AND (tbl_os.sua_os LIKE '$sua_os%' OR tbl_os.sua_os LIKE '0$sua_os%' OR tbl_os.sua_os LIKE '00$sua_os%') ";
//coloquei "tbl_os.sua_os ILIKE '%$sua_os%' OR" pois não estava achando certas OS da britania HD 927 por takashi 21-12
#			$sql .= " AND (tbl_os.sua_os LIKE '$sua_os%' OR tbl_os.sua_os LIKE '0$sua_os%' OR tbl_os.sua_os LIKE '00$sua_os%' OR tbl_os.sua_os LIKE '000$sua_os%' OR tbl_os.sua_os LIKE '0000$sua_os%' OR tbl_os.sua_os LIKE '00000$sua_os%' OR tbl_os.sua_os LIKE '000000$sua_os%' OR tbl_os.sua_os LIKE '0000000$sua_os%' OR tbl_os.sua_os LIKE '00000000$sua_os%') ";
			$sql .= " AND (
				tbl_os.sua_os = '$sua_os' OR tbl_os.sua_os = '0$sua_os' OR tbl_os.sua_os = '00$sua_os' OR tbl_os.sua_os = '000$sua_os' OR tbl_os.sua_os = '0000$sua_os' OR tbl_os.sua_os = '00000$sua_os' OR tbl_os.sua_os = '000000$sua_os' OR tbl_os.sua_os = '0000000$sua_os' OR tbl_os.sua_os = '00000000$sua_os' OR ";

$sql .= "tbl_os.sua_os = '$sua_os-01' OR
		 tbl_os.sua_os = '$sua_os-02' OR
		 tbl_os.sua_os = '$sua_os-03' OR
		 tbl_os.sua_os = '$sua_os-04' OR
		 tbl_os.sua_os = '$sua_os-05' OR
		 tbl_os.sua_os = '$sua_os-06' OR
		 tbl_os.sua_os = '$sua_os-07' OR
		 tbl_os.sua_os = '$sua_os-08' OR
		 tbl_os.sua_os = '$sua_os-09' OR ";

//wellington 08/03/2007 - POSTOS LENOXX LANÇAM OS REVENDA ATE 300 PRODUTOS
$suas_oss = "";
for ($x=1;$x<=300;$x++) {
	$suas_oss .= "tbl_os.sua_os = '$sua_os-$x' OR ";
}
$sql .= $suas_oss;

$sql .= "tbl_os.sua_os = '0$sua_os-01' OR
		 tbl_os.sua_os = '0$sua_os-02' OR
		 tbl_os.sua_os = '0$sua_os-03' OR
		 tbl_os.sua_os = '0$sua_os-04' OR
		 tbl_os.sua_os = '0$sua_os-05' OR
		 tbl_os.sua_os = '0$sua_os-06' OR
		 tbl_os.sua_os = '0$sua_os-07' OR
		 tbl_os.sua_os = '0$sua_os-08' OR
		 tbl_os.sua_os = '0$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=40;$x++) {
	$suas_oss .= "tbl_os.sua_os = '0$sua_os-$x' OR ";
}
$sql .= $suas_oss;

$sql .= "tbl_os.sua_os = '00$sua_os-01' OR
		 tbl_os.sua_os = '00$sua_os-02' OR
		 tbl_os.sua_os = '00$sua_os-03' OR
		 tbl_os.sua_os = '00$sua_os-04' OR
		 tbl_os.sua_os = '00$sua_os-05' OR
		 tbl_os.sua_os = '00$sua_os-06' OR
		 tbl_os.sua_os = '00$sua_os-07' OR
		 tbl_os.sua_os = '00$sua_os-08' OR
		 tbl_os.sua_os = '00$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=40;$x++) {
	$suas_oss .= "tbl_os.sua_os = '00$sua_os-$x' OR ";
}
$sql .= $suas_oss;

$sql .= "tbl_os.sua_os = '000$sua_os-01' OR
		 tbl_os.sua_os = '000$sua_os-02' OR
		 tbl_os.sua_os = '000$sua_os-03' OR
		 tbl_os.sua_os = '000$sua_os-04' OR
		 tbl_os.sua_os = '000$sua_os-05' OR
		 tbl_os.sua_os = '000$sua_os-06' OR
		 tbl_os.sua_os = '000$sua_os-07' OR
		 tbl_os.sua_os = '000$sua_os-08' OR
		 tbl_os.sua_os = '000$sua_os-09' OR ";

$suas_oss = "";
for ($x=1;$x<=40;$x++) {
	$suas_oss .= "tbl_os.sua_os = '000$sua_os-$x' OR ";
}
$sql .= $suas_oss;

//apenas para terminar o OR
$sql .= "tbl_os.sua_os = '000$sua_os-40'";

		$sql .= ") ";
		}
		if (strlen($os_off) > 0) {
			$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%' OR tbl_os.sua_os_offline LIKE '0$os_off%' OR tbl_os.sua_os_offline LIKE '00$os_off%') ";
		}



		if (strlen($serie) > 0) {
			$sql .= " AND tbl_os.serie = '$serie'";
		}

		if (strlen($nf_compra) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
		}

		if (strlen($consumidor_nome) > 0) {
			$consumidor_nome = strtoupper ($consumidor_nome);
			$sql .= " AND tbl_os.consumidor_nome LIKE '$consumidor_nome%'";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
		}

		if (strlen($os_aberta) > 0) {
			$sql .= " AND tbl_os.os_fechada IS FALSE ";
		}

		if (strlen($revenda_cnpj) > 0) {
			$sql .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%') ";
		}

		if($login_fabrica==1){
			$sql .= " AND tbl_os.consumidor_revenda = 'C' AND tbl_os.cortesia is not true ";
		}

		$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC";

	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;
	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");


//if ($ip=="201.27.215.6") echo $sql ;

if ($login_admin == 19) { echo $sql ; exit ; }
flush();
//echo "<!-- $sql -->";
//if ($ip == "201.26.143.50") echo $sql;
	$res = pg_exec($con,$sql);
	$resultados = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		##### LEGENDAS - INÍCIO #####
		echo "<div align='left' style='position: relative; left: 25'>";
		echo "<table border='0' cellspacing='0' cellpadding='0'>";
		if ($excluida == "t") {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica != 1) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			if($sistema_lingua=='ES')echo "Reincidencia";else echo "Reincidências.";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";

			if($login_fabrica <> 14){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";

				if ($sistema_lingua == 'ES')
					echo "OS abierta a más de 25 días";
				else
					echo "OS aberta a mais de 25 dias.";

				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			if($login_fabrica == 15){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#999933'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";

				if ($sistema_lingua == 'ES')
					echo "OS digitada por administrador.";
				else
					echo "OS digitada por administrador.";

				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			if($login_fabrica == 3 OR $login_fabrica == 11){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFCCCC'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				echo "OS com Intervenção da Fábrica. Aguardando Liberação";
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";

				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFFF99'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				echo "OS com Intervenção da Fábrica. Reparo na Fábrica";
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";

				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#CCFFFF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				echo "OS Liberada Pela Fábrica";
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}
			if ($login_fabrica==3){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; Canceladas</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS aberta a mais de 25 dias.";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			if($sistema_lingua=='ES')echo "Reincidencia";else echo "Reincidências.";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 50 == 0) {
				echo "</table>";
				flush();
				echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='98%'>";
			}

			if ($i % 50 == 0) {
				echo "<tr class='Titulo' height='25' background='admin/imagens_admin/azul.gif'>";
				echo "<td width='100' nowrap>OS</td>";
				if($login_fabrica==19 OR $login_fabrica==10){
					echo "<td>OS OFF LINE</td>";
				}
				echo "<td width='150'>SÉRIE</td>";
				echo "<td>AB</td>";
				echo "<td><acronym title='Data de fechamento registrada pelo sistema' style='cursor:help;'>FC</a></td>";
				echo "<td>CONSUMIDOR</td>";
				echo "<td>";
				if($sistema_lingua=='ES')echo "PRODUCTO";else echo "PRODUTO";
				echo "</td>";
				if($login_fabrica==19){
					echo "<td>ATENDIMENTO</td>";
					echo "<td nowrap>TÉCNICO</td>";
					}
				echo "<td><img border='0' src='../imagens/img_impressora.gif' alt='Imprimir OS'></td>";
				if($login_fabrica == 1 ){
					echo "<td><img border='0' width='20' heigth='20' src='../imagens/envelope.png' alt='Carta Registrada'></td>";
				}
				if ($login_fabrica == 1) {
					echo "<td>Item</td>";
					$colspan = "8";
				}else{
					$colspan = "5";
				}
				echo "<td colspan='$colspan'>";
				if($sistema_lingua=='ES')echo "ACIONES";else echo "AÇÕES";
				echo "</td>";
			}


			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$finalizada         = trim(pg_result($res,$i,finalizada));
			$serie              = trim(pg_result($res,$i,serie));
			$excluida           = trim(pg_result($res,$i,excluida));
			$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
			$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$valores_adicionais = trim(pg_result($res,$i,valores_adicionais));	//
			$nota_fiscal_saida  = trim(pg_result($res,$i,nota_fiscal_saida));	//
			$reincidencia       = trim(pg_result($res,$i,reincidencia));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));	//
			$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($res,$i,descricao));
			$admin              = trim(pg_result($res,$i,admin));
			$sua_os_offline     = trim(pg_result($res,$i,sua_os_offline));
			$status_os          = trim(pg_result($res,$i,status_os));

			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####

			if($login_fabrica == 15 && strlen($fechamento) != 0 && $reincidencia != "t"){
			
				if (strlen($admin)>0)    $cor = "#999933";
			
			}
// and $status_os<>86 takashi HD 3343 -  qdo for reincidencia de produto em diferentes postos nao mostrar reincidencia.
			if ($reincidencia =='t' and $status_os<>86)     $cor = "#D7FFE1";
			if ($excluida == "t")        $cor = "#FF0000";

			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}

			if ($login_fabrica==3 OR $login_fabrica == 11){
				if ($status_os=="62") $cor="#E6E6FA";
				if ($status_os=="62") $cor="#FFCCCC";
				if ($status_os=="72") $cor="#FFCCCC";

				if (($status_os=="64" OR $status_os=="73") && strlen($fechamento)==0) $cor="#CCFFFF";
				if ($status_os=="64" && strlen($fechamento)==0) $cor="#CCFFFF";

				if ($status_os=="65") $cor="#FFFF99";
			}
//AQUI
			if($login_fabrica == 1){
				if(strlen($tipo_atendimento) > 0) $cor = "#FFCC66";
			}

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$data_hj_mais_5 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_exec($con,$sql);

				$itens = pg_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM


			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;


			echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
			echo "<td  width='60' nowrap>" ;
			if ($login_fabrica == 1){ echo $xsua_os; }else{ echo $sua_os;}
			echo "</td>";
			if($login_fabrica==19 OR $login_fabrica==10){
				echo "<td nowrap>" . $sua_os_offline . "</td>";
			}
			echo "<td width='55' nowrap>" . $serie . "</td>";

			if ($sistema_lingua=='ES') echo "<td nowrap ><acronym title='Fecha Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			else echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";



			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
if($sistema_lingua=='ES') echo "<td nowrap><acronym title='Fecha Cerramiento"; else echo "<td nowrap><acronym title='Data Fechamento: ";
echo "$aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td width='120' nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			$produto = $produto_referencia . " - " . $produto_descricao;
			echo "<td width='150' nowrap><acronym title='";
if($sistema_lingua=='ES')echo "Referencia"; else echo "Referência";
echo " : $produto_referencia ";
if($sistema_lingua=='ES')echo "Descripción"; else echo "Descrição";
echo " : $produto_descricao ";
if($sistema_lingua=='ES')echo "Voltaje"; else echo "Voltagem";
echo ": $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			if($login_fabrica==19){
				echo"<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
				echo"<td width='90' nowrap><acronym title='Nome do técnico: $tecnico_nome' style='cursor: help;'>" . substr($tecnico_nome,0,11) . "</acronym></td>";
				}


			##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
			echo "<td width='30' align='center'>";
			if (strlen($admin) > 0 and $login_fabrica == 19) echo "<img border='0' src='../imagens/img_sac_lorenzetti.gif' alt='OS lançada pelo SAC Lorenzetti'>";
			else if (strlen($impressa) > 0)                  echo "<img border='0' src='../imagens/img_ok.gif' alt='OS já foi impressa'>";
			else                                             echo "<img border='0' src='../imagens/img_impressora.gif' alt='Imprimir OS'>";
			echo "</td>";

			##### VERIFICAÇÃO SE A OS FOI ENVIADA CARTA REGISTRADA #####
			if($login_fabrica == 1 and $consumidor_revenda == 'C' ){
				echo "<td width='30' align='center'>";
				if(strlen($fechamento) == 0){
					$sql_sedex = "SELECT SUM(current_date - data_abertura)as final FROM tbl_os WHERE os=$os ;";
					$res_sedex = pg_exec($con,$sql_sedex);
					$sedex_dias = pg_result($res_sedex,0,final);
					if($sedex_dias > 15){
						$sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE sua_os_origem = $os AND fabrica = $login_fabrica";
						$res_sedex = pg_exec($con,$sql_sedex);
						if(pg_numrows($res_sedex) == 0){
							echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='../imagens/envelope.png' alt='Inserir informações da Carta Registrada'></a>";
						}else{
							echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='../imagens/img_ok.gif' alt='Visualizar as informações da Carta Registrada'></a>";
						}
					}
					echo "&nbsp;";
				}else{
					echo "&nbsp;";
				}
				echo "</td>";
			}

			##### VERIFICAÇÃO SE TEM ITEM NA OS PARA A FÁBRICA 1 #####
			if ($login_fabrica == 1) {
				echo "<td width='30' align='center'>";
				if ($qtde_item > 0) echo "<img border='0' src='../imagens/img_ok.gif' alt='OS com item'>";
				else                echo "&nbsp;";
				echo "</td>";
			}

			echo "<td width='60' align='center'>";
			if($sistema_lingua == "ES"){
				if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='../imagens/btn_busca.gif'></a>";
			}else{
				if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='../imagens/btn_consulta.gif'></a>";
			}
			//if($login_fabrica==3 and $excluida == 't')echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
			echo "</td>\n";

			echo "<td width='60' align='center'>";

			if ($excluida == "f" || strlen($excluida) == 0) {
				if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
					if($login_posto=="6359"){
						//echo "<a href='os_item.php?os=$os' target='_blank'>";
							echo "<a href='os_print.php?os=$os' target='_blank'>";
					}else{
						echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
					//takashi alterou 03/11
					}
				}else{
					echo "<a href='os_print.php?os=$os' target='_blank'>";
				}
				echo "<img border='0' src='../imagens/btn_imprime.gif'></a>";
			}
			echo "</td>\n";
//AQUI
			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
					if($tipo_atendimento <> 17 AND $tipo_atendimento <> 18 )
						echo "<a href='os_cadastro.php?os=$os'><img border='0' src='../imagens/btn_alterar_cinza.gif'></a>";
					else
						if(strlen($valores_adicionais) == 0 AND strlen($nota_fiscal_saida) == 0)
							echo "<a href='os_cadastro_troca.php?os=$os'><img border='0' src='../imagens/btn_alterar_cinza.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center' nowrap>";
			if ($troca_garantia == "t"  OR  ($status_os=="62" || $status_os=="65" || $status_os=="72")) {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='../imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						if($login_posto=="6359"){
							echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'>";
						}else{
							echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
						//takashi alterou 03/11
						}
					}else{
						echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'>";
					}//
					if($login_fabrica == 1 AND $tipo_atendimento <> 17 AND $tipo_atendimento <> 18)
						echo "<img id='lancar_$i' border='0' src='../imagens/btn_lanca.gif'></a>";
					else
						echo "<p id='lancar_$i' border='0'></p></a>";
				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
				echo "<a href='os_filizola_valores.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='../imagens/btn_lanca.gif'></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" OR strlen($excluida) == 0) {
					if ($login_fabrica == 1) {
						if($tipo_os_cortesia == "Compressor"){
							if($login_posto=="6359"){
								echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'>";
							}else{
								echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
							//takashi alterou 03/11
							}
						}
						if(strlen($tipo_atendimento) == 0){
							echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'>";
						}
					}else{
						//
						if($login_fabrica==19){
							if($consumidor_revenda<>'R'){
								echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'>";
								if($sistema_lingua == "ES"){
									echo "<img id='lancar_$i' border='0' src='../imagens/btn_lanzar.gif'></a>";
								}else{
									echo "<img id='lancar_$i' border='0' src='../imagens/btn_lanca.gif'></a>";
								}
							}
						}else{
							echo "<a href='os_cadastro_ajax.php?os=$os' target='_blank'>";
							if($sistema_lingua == "ES"){
								echo "<img id='lancar_$i' border='0' src='../imagens/btn_lanzar.gif'></a>";
							}else{
								echo "<img id='lancar_$i' border='0' src='../imagens/btn_lanca.gif'></a>";
							}
						}
						//
					}
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
						if($login_fabrica == 20){
							if($status_os<>'13')
								echo "<a href='os_cadastro.php?os=$os&reabrir=ok'><img border='0' src='../imagens/btn_reabriros.gif'></a>";
						}
						else if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)) echo "&nbsp;";
							else echo "<a href='os_cadastro_ajax.php?os=$os&reabrir=ok'><img border='0' src='../imagens/btn_reabriros.gif'></a>";
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (strlen($admin) == 0 AND strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
					echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><img border='0' src='../imagens/btn_motivo.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0 && $login_fabrica != 7) {
				if (($status_os!="62" && $status_os!="65" && $status_os!="72") ||($reincidencia=='t')){
					if ($excluida == "f" || strlen($excluida) == 0) {
						if (strlen ($admin) == 0) {
							if ($sistema_lingua=='ES'){
								echo "<a href=\"javascript: if (confirm('Desea realmente excluir la OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img id='excluir_$i' border='0' src='../imagens/btn_excluir.gif'></a>";
							}else{
								if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND $valores_adicionais > 0)
									echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><p id='excluir_$i' border='0'></p></a>";
								else
									echo "<a href=\"javascript: if (confirm('Deseja realmente excluir a OS $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img id='excluir_$i' border='0' src='../imagens/btn_excluir.gif'></a>";
							}
						}else{
							echo "<img id='excluir_$i' border='0' src='../imagens/pixel.gif'>";
						}
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";
			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 AND $status_os!="62" && $status_os!="65" && $status_os!="72") {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($sistema_lingua=='ES'){
						echo "<a href=\"javascript: if (confirm('Caso la fecha de entrega del producto para el usuário no sea hoy, la opción de cierre de OS para informar la fecha correcta. Confirma cierre de OS $sua_os com la fecha de hoy?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_cerrar.gif'></a>";
					}else{
						if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)){
							if($nota_fiscal_saida > 0 OR ($valores_adicionais == 0 AND $nota_fiscal_saida == 0))
								echo "<a href=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor não seja HOJE, utilize a opção de Fechamento de OS para informar a data correta! Confirma o fechamento da OS $sua_os com a data de HOJE?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
						}else{
							
							if($login_fabrica==19){
								if($consumidor_revenda<>'R'){
									echo "<a href=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor não seja HOJE, utilize a opção de Fechamento de OS para informar a data correta! Confirma o fechamento da OS $sua_os com a data de HOJE?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
								}
							}else{
								echo "<a href=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor não seja HOJE, utilize a opção de Fechamento de OS para informar a data correta! Confirma o fechamento da OS $sua_os com a data de HOJE?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";

							}
						}
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";
			if ($login_fabrica == 7) {
				echo "<td width='60' align='center'>";
				echo "<a href='os_matricial.php?os=$os' target='_blank'>Matricial</a>";
				echo "</td>\n";
			}

			echo "</tr>";
		}
		echo "</table>";
	}

/*	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####*/

	echo "<br><h1>Resultado: $resultados registro(s).</h1>";
}
?>


<?
	$sua_os             = trim (strtoupper ($_POST['sua_os']));
	$serie              = trim (strtoupper ($_POST['serie']));
	$nf_compra          = trim (strtoupper ($_POST['nf_compra']));
	$consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
	$produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
	$produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
	$posto_nome      = trim (strtoupper ($_POST['posto_nome']));
	$consumidor_nome = trim (strtoupper ($_POST['consumidor_nome']));
	$os_situacao     = trim (strtoupper ($_POST['os_situacao']));
?>


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">
		<?
		if($sistema_lingua == 'ES') echo "Elija los parámetros para la consulta";
		else                        echo "Selecione os parâmetros para a pesquisa.";
		?>
		</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Número da OS</td>
		<?
			if($login_fabrica==19 OR $login_fabrica==10){
				echo "<td>OS Off Line</td>";
			}
		?>
		<td>Número de Série</td>
		<td>
		<?
		if($sistema_lingua == 'ES') echo "Núm. Factura";
		else                        echo "NF. Compra";
		?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
		<? if($login_fabrica==19 OR $login_fabrica==10){ ?>
			<td><input type="text" name="os_off" size="10" value="<?echo $os_off?>" class="frm"></td>
		<? } ?>
		<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
		<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td >
		<?
		if($sistema_lingua == 'ES') echo "ID Consumidor";
		else                        echo "CPF Consumidor";
		?>
		</td>
		<? if($login_fabrica==19 OR $login_fabrica==10){ ?>
		<td></td>
		<? } ?>
		<td></td>
		<td></td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>" class="frm"></td>
		<? if($login_fabrica==19 OR $login_fabrica==10){ ?>
		<td></td>
		<? } ?>
		<td></td>
		<td></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='4' align='center'><br><input type="submit" name="btn_acao" value="<?if($sistema_lingua=='ES')echo "Consultar";else echo "Pesquisar";?>"></td>
	</tr>
</table>


<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'><? if($sistema_lingua=='ES') echo "Fecha referente a digitalización de la OS en el sitio (obligatório para busca)";else echo "Data referente à digitação da OS no site (obrigatório para a pesquisa)";?></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<?
		if($sistema_lingua == 'ES') echo "* Mes";
		else                        echo "* Mês";
		?>
		</td>
		<td>
		<?
		if($sistema_lingua == 'ES') echo "* Año";
		else                        echo "* Ano";
		?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			//for ($i = 2003 ; $i <= date("Y") ; $i++) {
			for($i = date("Y"); $i > 2003; $i--){
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>

			&nbsp;&nbsp;&nbsp;

			<?
			if($sistema_lingua == 'ES') echo "Sólo OS abiertas";
			else                        echo "Apenas OS em aberto ";
			?>

			<input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >

		</td>

	</tr>


	<?
	if ($login_e_distribuidor == 't' and $login_fabrica == 3) {
	?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<?
			if($sistema_lingua == 'ES') echo "Cód. del Servicio";
			else                        echo "Cód. Posto";
			?>
		</td>
		<td>
			<?
			if($sistema_lingua == 'ES') echo "Nombre del Servicio";
			else                        echo "Nome do Posto ";
			?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="../imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="<?if($sistema_lingua == 'ES') echo "click aquí para efetuar la busca";else echo "Clique aqui para pesquisar postos pelo código";?>" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="../imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="<?if($sistema_lingua == 'ES') echo "click aquí para efetuar la busca";else echo "Clique aqui para pesquisar postos pelo código";?>" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>
	<?
	}
	?>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td></td>
		<td>
			<?
			if($sistema_lingua == 'ES') echo "Nombre del usuario";
			else                        echo "Nome do Consumidor";
			?>
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td></td>
		<td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"></td>
	</tr>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<?
			if($sistema_lingua == 'ES') echo "Cód. Herramienta";
			else                        echo "Ref. Produto";
			?>
		</td>
		<td>

			<?
			if($sistema_lingua == 'ES') echo "Descripción Herramienta";
			else                        echo "Descrição Produto";
			?>
		</td>
	</tr>
<input type='hidden' name='voltagem' value=''>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
		&nbsp;
		<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia',document.frm_consulta.voltagem)">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/btn_lupa_novo.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao',document.frm_consulta.voltagem)">
	</tr>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>


	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <?if($sistema_lingua == "ES")echo "OS abiertas del distribuidor = ID";else echo "OS em aberto da Revenda = CNPJ ";?>
		<input class="frm" type="text" name="revenda_cnpj" size="8" value="<? echo $revenda_cnpj ?>" >
	<? if ($sistema_lingua<>'ES'){?>
		 /0001-00
<? } ?>
		</td>
	</tr>
</table>


<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="<?if($sistema_lingua=='ES')echo "Consultar";else echo "Pesquisar";?>"></td>
	</tr>
</table>




</table>


</form>

