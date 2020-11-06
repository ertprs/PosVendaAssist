<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';
include "funcoes.php";

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$cnpj = trim(pg_fetch_result($res,$i,cnpj));
					$nome = trim(pg_fetch_result($res,$i,nome));
					$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$mostra='sim';
if(isset($_POST['btn_acao'])){
	$mostra='nao';
	$data_inicial = $_POST['data_inicial'];
	if (strlen($data_inicial)==0) $data_inicial = trim($_GET['data_inicial']);
	$data_final   = $_POST['data_final'];
	if (strlen($data_final)==0) $data_final = trim($_GET['data_final']);

	if(empty($data_inicial) || empty($data_final)){
        $mostra='sim';
		$msg_erro = "Informe a data inicial e final para pesquisa";
    }
	
	if(strlen($msg_erro)==0){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi)){
			$mostra='sim';
			$msg_erro = "Data inicial inválida";
		}
	}
	if(strlen($msg_erro)==0){
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf)){
			$mostra='sim';
			$msg_erro = "Data final inválida";
		}
	}

	if(strlen($msg_erro)==0){
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
	}
	if(strlen($msg_erro)==0){
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
		or strtotime($aux_data_inicial) > strtotime('today')){
			$mostra='sim';
			$msg_erro = "Data Inválida";
		}
	}

	if (strlen(trim($_POST["codigo_posto"])) > 0) $posto_codigo = trim($_POST["codigo_posto"]);
	if (strlen(trim($_GET["codigo_posto"])) > 0)  $posto_codigo = trim($_GET["codigo_posto"]);
	if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
	if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);

	if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto.posto                ,
						tbl_posto.nome                 ,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.contato,
						tbl_posto.fone,
						tbl_posto_fabrica.contato_endereco,
						tbl_posto_fabrica.contato_numero,
						tbl_posto_fabrica.contato_cidade,
						tbl_posto_fabrica.contato_estado,
						tbl_posto_fabrica.contato_email
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING (posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) == 1) {
			$posto        = trim(pg_fetch_result($res,0,posto));
			$posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
			$nome         = trim(pg_fetch_result($res,0,nome));
			$contato      = trim(pg_fetch_result($res,0,contato));
			$fone         = trim(pg_fetch_result($res,0,fone));
			$endereco     = trim(pg_fetch_result($res,0,contato_endereco));
			$numero       = trim(pg_fetch_result($res,0,contato_numero));
			$cidade       = trim(pg_fetch_result($res,0,contato_cidade));
			$estado       = trim(pg_fetch_result($res,0,contato_estado));
			$email        = trim(pg_fetch_result($res,0,contato_email));
		}else{
			$mostra='sim';
			$msg_erro = " Posto não encontrado. ";
		}
	}elseif(strlen($msg_erro)==0){
		$mostra='sim';
		$msg_erro = " Digite o código e o nome do posto.";
	}

	$sql = " SELECT nome_completo 
				FROM tbl_admin
				WHERE admin = $login_admin";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$nome_completo = pg_fetch_result($res,0,0);
	}
}

if(isset($_POST['btn_gravar'])) {
	$mostra='nao';
	$btn_gravar    = $_POST['btn_gravar'];
	$posto         = $_POST['posto'];
	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$aux_data_inicial  = $_POST['data_inicial'];
	$aux_data_final    = $_POST['data_final'];
	$comentario_1  = $_POST['comentario_1'];
	$comentario_2  = $_POST['comentario_2'];
	$comentario_3  = $_POST['comentario_3'];
	$comentario_4  = $_POST['comentario_4'];
	$comentario_5  = $_POST['comentario_5'];
	$conclusao     = $_POST['conclusao'];
	$visita_posto  = $_POST['visita_posto'];
	$data_visita   = $_POST['data_visita'];
	$data_pesquisa = $_POST['data_pesquisa'];

	$data_visita = fnc_formata_data_pg($data_visita);
	$data_visita = str_replace("'","",$data_visita);

	if(strlen($data_pesquisa) ==0) {
		$erro = "Data Inválida";
	}else{
		$data_pesquisa = fnc_formata_data_pg($data_pesquisa);
		$data_pesquisa = str_replace("'","",$data_pesquisa);
	
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql = " INSERT INTO tbl_auditoria_online (
					 posto                        ,
					 fabrica                      ,
					 data_pesquisa                ,
					 data_digitacao               ,
					 inspetor                     ,
					 responsavel_pa               ,
					 admin                        ,
					 conclusao_auditoria          ,
					 visita_posto                 ,
					 data_visita                  ,
					 comentario_qtde_os_atendida  ,
					 comentario_qtde_peca_trocada ,
					 comentario_qtde_os_revenda   ,
					 comentario_qtde_peca_revenda ,
					 comentario_qtde_sem_peca     
				) values(
					$posto,
					$login_fabrica,
					'$data_pesquisa',
					current_timestamp,
					$login_admin,
					'".substr($contato, 0, 20)."',
					$login_admin,
					'$conclusao',
					'$visita_posto',
					'$data_visita',
					'$comentario_1',
					'$comentario_2',
					'$comentario_3',
					'$comentario_4',
					'$comentario_5'
				)";
		$res = pg_query($con,$sql);
		$erro = pg_errormessage($con);

		if(strlen($erro) > 0) {
			$erro = "Erro ao tentar gravar o Relatório de auditoria online #1";
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}else{

			$sql = " SELECT currval('seq_auditoria_online'); ";
			$res = pg_query($con,$sql);

			$auditoria_online = pg_fetch_result($res,0,0);
			if (!empty($auditoria_online)) {
				$auditoria_online .= ',';
			}

			$sql = " INSERT INTO tbl_auditoria_online_item (
						 auditoria_online       ,
						 data_inicio           ,
						 data_final            ,
						 os                    ,
						 sua_os                ,
						 produto               ,
						 peca                  ,
						 valor_mo              ,
						 valor_peca            ,
						 nota_fiscal           ,
						 data_abertura         ,
						 data_fechamento       ,
						 defeito_reclamado     ,
						 defeito_constatado    ,
						 solucao               ,
						 servico_realizado     ,
						 consumidor_revenda
						)
						 SELECT $auditoria_online
							'$data_inicial'::date    ,
							'$data_final'::date      ,
							tbl_os.os                  ,
							tbl_os.sua_os              ,
							tbl_os.produto             ,
							tbl_os_item.peca           ,
							tbl_os.mao_de_obra         ,
							tbl_os.pecas               ,
							cast(fnc_so_numeros(tbl_os.nota_fiscal) as int)         ,
							tbl_os.data_abertura       ,
							tbl_os.data_fechamento     ,
							tbl_os.defeito_reclamado   ,
							tbl_os.defeito_constatado  ,
							tbl_os.solucao_os          ,
							servico_realizado          ,
							tbl_os.consumidor_revenda
					FROM tbl_os
					LEFT JOIN tbl_os_produto USING(os)
					LEFT JOIN tbl_os_item    USING(os_produto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.posto = $posto
					AND   tbl_os.finalizada between '$data_inicial 00:00:00' and '$data_final 23:59:59' ";
			$res = pg_query($con,$sql);
			$erro = pg_errormessage($con);

			if(strlen($erro) > 0) {
				$erro = "Erro ao tentar gravar o Relatório de auditoria online #2";
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}else{

				$sql = " INSERT INTO tbl_auditoria_online_item (
							 auditoria_online       ,
							 data_inicio           ,
							 data_final            ,
							 extrato               ,
							 data_geracao          ,
							 valor_mo              ,
							 valor_peca
							)
							 SELECT DISTINCT $auditoria_online
								'$data_inicial'::date    ,
								'$data_final'::date      ,
								extrato                  ,
								data_geracao::date              ,
								mao_de_obra         ,
								pecas
						FROM tbl_extrato
						JOIN tbl_extrato_lgr USING(extrato)
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.posto = $posto
						AND   tbl_extrato.data_geracao between '$data_inicial 00:00:00' and '$data_final 23:59:59'
						AND   (qtde_nf = 0 or qtde_nf IS NULL) ";
				$res = pg_query($con,$sql);
				$erro = pg_errormessage($con);

				if(strlen($erro) > 0) {
					$erro = "Erro ao tentar gravar o Relatório de auditoria online #3";
					$res = pg_query ($con,"ROLLBACK TRANSACTION");
				}else{

					$sql = " INSERT INTO tbl_auditoria_online_item (
								 auditoria_online       ,
								 data_inicio           ,
								 data_final            ,
								 os                    ,
								 sua_os                ,
								 produto               ,
								 valor_mo              ,
								 valor_peca            ,
								 nota_fiscal           ,
								 data_abertura         ,
								 data_fechamento       ,
								 defeito_reclamado     ,
								 defeito_constatado    ,
								 solucao               ,
								 consumidor_revenda    ,
								 os_recusada           ,
								 extrato
								)SELECT $auditoria_online
									'$data_inicial'::date    ,
									'$data_final'::date      ,
									tbl_os.os                  ,
									tbl_os.sua_os              ,
									tbl_os.produto             ,
									tbl_os.mao_de_obra         ,
									tbl_os.pecas               ,
									cast(fnc_so_numeros(tbl_os.nota_fiscal) as int)         ,
									tbl_os.data_abertura       ,
									tbl_os.data_fechamento     ,
									tbl_os.defeito_reclamado   ,
									tbl_os.defeito_constatado  ,
									tbl_os.solucao_os          ,
									tbl_os.consumidor_revenda  ,
									't'                        ,
									tbl_os_status.extrato
						FROM tbl_os
						JOIN tbl_os_status USING(os)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os_status.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND tbl_os_status.status_os = 13
						AND tbl_os_status.extrato IS NOT NULL
						ORDER BY tbl_os.os ";
					$res = pg_query($con,$sql);
					$erro = pg_errormessage($con);

					if(strlen($erro) > 0) {
						$erro = "Erro ao tentar gravar o Relatório de auditoria online #4";
						$res = pg_query ($con,"ROLLBACK TRANSACTION");
					}else{

						$sql = " INSERT INTO tbl_auditoria_online_item (
									 auditoria_online       ,
									 data_inicio           ,
									 data_final            ,
									 os                    ,
									 sua_os                ,
									 produto               ,
									 valor_mo              ,
									 valor_peca            ,
									 nota_fiscal           ,
									 data_abertura         ,
									 data_fechamento       ,
									 defeito_reclamado     ,
									 defeito_constatado    ,
									 solucao               ,
									 consumidor_revenda    ,
									 sem_peca
									)SELECT $auditoria_online
										'$data_inicial'::date    ,
										'$data_final'::date      ,
										tbl_os.os                  ,
										tbl_os.sua_os              ,
										tbl_os.produto             ,
										tbl_os.mao_de_obra         ,
										tbl_os.pecas               ,
										cast(fnc_so_numeros(tbl_os.nota_fiscal) as int)         ,
										tbl_os.data_abertura       ,
										tbl_os.data_fechamento     ,
										tbl_os.defeito_reclamado   ,
										tbl_os.defeito_constatado  ,
										tbl_os.solucao_os          ,
										tbl_os.consumidor_revenda  ,
										't'
							FROM tbl_os
							LEFT JOIN tbl_os_produto USING(os)
							LEFT JOIN tbl_os_item USING(os_produto)
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.posto = $posto
							AND   tbl_os.data_digitacao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
							AND tbl_os.data_digitacao < CURRENT_DATE - INTERVAL '30 DAYS'
							AND tbl_os_item.os_item IS NULL
							AND finalizada IS NULL
							ORDER BY tbl_os.os ";
						$res = pg_query($con,$sql);
						$erro = pg_errormessage($con);

						if(strlen($erro) > 0) {
							$erro = "Erro ao tentar gravar o Relatório de auditoria online #5";
							$res = pg_query ($con,"ROLLBACK TRANSACTION");
						}else{
							$res = pg_query ($con,"COMMIT TRANSACTION");
							$msg = "Cadastrado com sucesso";
							$mostra='sim';
						}
					}
				}
			}
		}
	}
}
$title = "RELATÓRIO DE AUDITORIA ONLINE";
?>
<? include "javascript_calendario.php"; ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script language="JavaScript">
	$().ready(function() {

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		/* Busca pelo Código */
		$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#codigo_posto").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		/* Busca pelo Nome */
		$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#codigo_posto").val(data[2]) ;
			//alert(data[2]);
		});

	});
	
</script>

<style type="text/css">
.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.border {
	border: 1px solid #ced7e7;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.espaco{
	padding-left:100px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 11px "Arial";
    text-align: center;
}

</style>

<?
$layout_menu = "auditoria";
include 'cabecalho.php';

if (strlen($msg_erro)>0 or strlen($erro) > 0 or strlen($msg) > 0) {
	echo '<div align="center">';
	echo "<div align='center' style='width:700px;margin-bottom: 5px;";
	echo (strlen($msg_erro)>0 or strlen($erro) > 0) ? "margin-right: 1px;'" : "'";
	echo (strlen($msg_erro)>0 or strlen($erro) > 0) ? "class='msg_erro'": "class='texto_avulso'";
	echo ">";
	echo "$msg_erro $erro $msg"; 
	echo "</div>";
	echo "</div>";
	echo "<br />";
}

?>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {

    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url="posto_pesquisa_2.php?campo="+xcampo.value+"&tipo="+tipo+"&os=t";
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;

        if ("<? echo $pedir_sua_os; ?>" == "t") {
            janela.proximo = document.frm_consulta.sua_os;
        }else{
            janela.proximo = document.frm_consulta.data_abertura;
        }
        janela.focus();
    }

    else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
}
</script>
<?php include "../js/js_css.php"; ?>
<script language="JavaScript">
	$(function(){
		$("#data_pesquisa").mask("99/99/9999");
		$('#data_pesquisa').datepick({startDate:'01/01/2000'});
		$("#fone").mask("(99) 9999-9999");
		$('#data_visita').datepick({startDate:'01/01/2000'});
		$("#data_visita").mask("99/99/9999");
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>
<form name="frm_consulta" method="post" action="<? echo $PHP_SELF ?>">
<?if($mostra =='sim') {?>
	<table class='formulario' width='700' border='0' align='center' style="margin-top:-19px;">
		<caption class='titulo_tabela' colspan='100%' align='center'>Parâmetros de Pesquisa</caption>
		<tbody>
			<tr>
				<td class="espaco">
					Data Inicial
					<br>
					<input type="text" name="data_inicial" id="data_inicial" size="15" maxlength="10" value="" class="frm">
				</td>
				<td >Data Final<br/>
					<input type="text" name="data_final" id="data_final" size="15" maxlength="10" value=""  class="frm">
				</td>
			</tr>
			<tr class="subtitulo"  align="center">
				<td colspan="2">
					Informações do Posto
				</td>
			</tr>
			<tr>
				<td class="espaco">
					Código Posto
					<br/>
					<input type='text' name='codigo_posto' id='codigo_posto' size='12' value='<? echo $posto_codigo ?>' class="frm">
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código"  onclick="fnc_pesquisa_posto2(document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
				</td>
				<td>
					Nome Posto
					<br>
					<input type='text' name='posto_nome' id='posto_nome' size='50' value='<? echo $posto_nome ?>' class="frm"> 
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto2(document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<td colspan='100%' align='center'>&nbsp;</td>
			</tr>
			<tr>
				<td colspan='100%' align='center'>
					<input type='submit' name='btn_acao' value='Pesquisar' >
				</td>
			</tr>
			<tr>
				<td colspan='100%' align='center'>&nbsp;</td>
			</tr>
		</tfoot>
	</table>
<?}?>

<?if((isset($_POST['btn_acao']) and empty($msg_erro)) or !empty($erro)) {?>

<input type="hidden" name="posto" value="<? echo $posto ?>">
<table class='formulario' width='700' border='0' align='center'>
	<tr>
		<td align="center">
			<table width="700" cellspacing="3" cellpadding="1" border="1" align="center" class="formulario">
				<tr>
					<td colspan="1" rowspan="5">
						<img src='/assist/logos/suggar.jpg' alt='$login_fabrica_site' border='0' height='40'>
					</td>
					<td colspan="3" rowspan="5" align="center">
						<font size='4'><b>Relatório De Auditoria Online</b></font>
					</td>
				</tr>
				<tr>
					<td><strong>Elaboração:</strong></td>
				</tr>
				<tr>
					<td><input type="text" name="nome_completo" class="frm" size="20" maxlength="40" value="<? echo $nome_completo; ?>" READONLY>&nbsp;</td>
				</tr>
				<tr>
					<td><strong>Data Pesquisa:</strong></td>
				</tr>
				<tr>
					<td><input type="text" name="data_pesquisa" class="frm" id="data_pesquisa" size="12" maxlength="10" value="<? echo date('d/m/Y'); ?>" ></td>
				</tr>
			</table>

			<input type='hidden' name='data_inicial' value='<?=$aux_data_inicial?>'>
			<input type='hidden' name='data_final' value='<?=$aux_data_final?>'>
			<input type='hidden' name='posto' value='<?=$posto?>'>

			<table align="center" class="formulario" width="700" border="0">
				<tr class="menu_top">
					<td align='left'>Posto Autorizado: </td>
					<td align='left'><input type="text" class="frm" name="nome" size="40" maxlength="60" value="<? echo $nome ?>"></td>
					<td align='left'>Contato: </td>
					<td align='left'><input type="text" class="frm" name="contato" size="20" maxlength="50" value="<? echo $contato ?>" style="width:200px"></td>
				</tr>
				<tr class="menu_top">
					<td align='left'>Endereço: </td>
					<td align='left'><input type="text" class="frm" name="endereco" size="40" maxlength="60" value="<? echo "$endereco &nbsp; $numero"; ?>">&nbsp;</td>
					<td align='left'>Telefone: </td>
					<td align='left'><input type="text" class="frm" name="fone" id="fone" size="20" maxlength="20" value="<? echo $fone ?>" style="width:200px"></td>
				</tr>
				<tr class="menu_top">
					<td align='left'>Cidade/Estado: </td>
					<td align="left"><input type="text" class="frm" name="cidade" size="40" maxlength="60" value="<? echo "$cidade  &nbsp; $estado"; ?>" ></td>
					<td align='left'>E-mail: </td>
					<td align='left'><input type="text" class="frm" name="email" size="20" maxlength="20" value="<? echo $email ?>" style="width:200px"></td>
				</tr>
			</table>
			<br>
			<table align="center" class="formulario" width="700" border="0">
				<tr>
					<td align='center' class="texto_avulso">
						&nbsp;&nbsp;A Suggar Eletrodomésticos em busca da melhoria da qualidade de seus serviços e 
						<BR>produtos, com uma meta de melhorar cada vez mais, busca seus comentarios, criticas e
						<BR>recomendações que são muito importantes para nós.
						
					</td>
				</tr>
			</table>
			<br/>

			<table align="center" class="formulario" width="700" border="0">
				<tr>
					<td align='center'><?=$data_inicial?> - <?=$data_final?></td>
				</tr>
			</table>

			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Quantidade de OS atendida por Cliente</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Produto</th>
						<th align="right">Quantidade</th>
						<th align="right">MO</th>
						<th align="right">Peças</th>
						<th align="right">Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;

				$sql = " SELECT count(tbl_os.os) as produto_qtde,
								SUM(tbl_os.mao_de_obra) as mao_de_obra,
								SUM(pecas) as pecas,
								SUM(tbl_os.mao_de_obra + pecas) as total,
								tbl_produto.referencia,
								tbl_produto.descricao 
						FROM tbl_os
						JOIN tbl_produto USING(produto)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os.finalizada between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND   tbl_os.consumidor_revenda = 'C'
						GROUP BY tbl_produto.referencia,
								tbl_produto.descricao 
						ORDER BY count(tbl_os.produto) DESC,tbl_produto.referencia ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['produto_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['produto_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;

				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>

			<h2 class="subtitulo">Comentário</h2>
			<textarea name="comentario_1" rows="6" cols="100"  class="frm"><?echo $comentario_1 ;?></textarea>
			<br>
			<br>
			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Quantidade de peças trocadas em garantia</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Peça</th>
						<th align="right">Quantidade</th>
						<th align="right">MO</th>
						<th align="right">Peças</th>
						<th align="right">Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;

				$sql = " SELECT count(tbl_os_item.peca) as peca_qtde,
								SUM(tbl_os.mao_de_obra) as mao_de_obra,
								SUM(pecas) as pecas,
								SUM(tbl_os.mao_de_obra + pecas) as total,
								tbl_peca.referencia,
								tbl_peca.descricao 
						FROM tbl_os
						JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
						JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_peca.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os.finalizada between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND   tbl_os_item.servico_realizado = 504
						AND   tbl_os.consumidor_revenda = 'C'
						GROUP BY tbl_peca.referencia,
								tbl_peca.descricao 
						ORDER BY count(tbl_os_item.peca) DESC ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['peca_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['peca_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;

				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>

			<h2 class="subtitulo">Comentário</h2>
			<textarea name="comentario_2" rows="6" cols="100" class="frm"><?echo $comentario_2 ;?></textarea>
			<br>
			<br>
			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Quantidade de OS atendida Revenda</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Produto</th>
						<th align="right">Quantidade</th>
						<th align="right">MO</th>
						<th align="right">Peças</th>
						<th align="right">Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;
				$total = 0;

				$sql = " SELECT count(tbl_os.os) as produto_qtde,
								SUM(tbl_os.mao_de_obra) as mao_de_obra,
								SUM(pecas) as pecas,
								SUM(tbl_os.mao_de_obra + pecas) as total,
								tbl_produto.referencia,
								tbl_produto.descricao 
						FROM tbl_os
						JOIN tbl_produto USING(produto)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os.finalizada between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND   tbl_os.consumidor_revenda = 'R'
						GROUP BY tbl_produto.referencia,
								tbl_produto.descricao 
						ORDER BY count(tbl_os.produto) DESC ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['produto_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['produto_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;
				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>

			<h2 class="subtitulo">Comentário</h2>
			<textarea name="comentario_3" class="frm" rows="6" cols="100"><?echo $comentario_3 ;?></textarea>
			<br>
			<br>

			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Quantidade de peças trocadas Revenda</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>Peça</th>
						<th align="right">Quantidade</th>
						<th align="right">MO</th>
						<th align="right">Peças</th>
						<th align="right">Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$total_mao_de_obra = 0 ;
				$total_pecas = 0 ;
				$total_qtde = 0 ;

				$sql = " SELECT count(tbl_os_item.peca) as peca_qtde,
								SUM(tbl_os.mao_de_obra) as mao_de_obra,
								SUM(pecas) as pecas,
								SUM(tbl_os.mao_de_obra + pecas) as total,
								tbl_peca.referencia,
								tbl_peca.descricao 
						FROM tbl_os
						JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
						JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
						JOIN    tbl_servico_realizado ON tbl_os_item.servico_realizado  = tbl_servico_realizado.servico_realizado
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_peca.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os.finalizada between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND   tbl_servico_realizado.gera_pedido
						AND   tbl_os.consumidor_revenda = 'R'
						GROUP BY tbl_peca.referencia,
								tbl_peca.descricao 
						ORDER BY count(tbl_os_item.peca) DESC ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td>".$resultado['referencia']."-".$resultado['descricao']."</td>";
						echo "<td align='right'>".$resultado['peca_qtde']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";

						$total_mao_de_obra += $resultado['mao_de_obra'];
						$total_pecas       += $resultado['pecas'] ;
						$total_qtde        += $resultado['peca_qtde'] ;
					}
					$total = $total_mao_de_obra + $total_pecas ;

				?>
				</tbody>
				<?
				echo "<tfoot>";
				echo "<tr class='titulo_coluna'>";
				
				echo "<td align='center'>Total";
				echo "</td>";

				echo "<td align='right'>";
				echo $total_qtde;
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_mao_de_obra,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total_pecas,2,",",".");
				echo "</td>";
				
				echo "<td align='right'>";
				echo number_format ($total,2,",",".");
				echo "</td>";
				echo "</tr>";
				echo "</tfoot>";
				}
				?>
			</table>

			<h2 class="subtitulo">Comentário</h2>
			<textarea name="comentario_4" rows="6" cols="100" class="frm"><?echo $comentario_4 ;?></textarea>
			<br>
			<br>

			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Relatório de OS sem peças trocadas</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Produto</th>
						<th>Abertura</th>
						<th>Fechamento</th>
						<th>Defeito Reclamado</th>
						<th>Defeito Constatado</th>
						<th>Solução</th>
					</tr>
				</thead>
				<tbody>
				<?

				$sql = " SELECT tbl_os.os,
								tbl_os.sua_os,
								tbl_produto.descricao,
								to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura,
								to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento,
								tbl_defeito_reclamado.descricao as defeito_reclamado,
								tbl_defeito_constatado.descricao as defeito_constatado,
								tbl_solucao.descricao as solucao
						FROM tbl_os
						JOIN tbl_produto USING(produto)
						JOIN tbl_defeito_reclamado  USING(defeito_reclamado)
						JOIN tbl_defeito_constatado  USING(defeito_constatado)
						JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os.finalizada between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						and tbl_os.os in ( select tbl_os_produto.os
											from tbl_os_produto
											join tbl_os_item  on tbl_os_item.os_produto =  tbl_os_produto.os_produto
											join tbl_servico_realizado on  tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca = 'f'
											where  tbl_os_produto.os = tbl_os.os
										 )
						and tbl_os.os not in ( select tbl_os_produto.os
													from tbl_os_produto
													join tbl_os_item  on tbl_os_item.os_produto = tbl_os_produto.os_produto
													join tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca = 't'
													where  tbl_os_produto.os = tbl_os.os
												) 
						ORDER BY tbl_os.os ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td><a href='os_press.php?os=".$resultado['os']."' target='_blank'>".$resultado['sua_os']."</a></td>";
						echo "<td align='left'>".$resultado['descricao']."</td>";
						echo "<td align='center'>".$resultado['data_abertura']."</td>";
						echo "<td align='center'>".$resultado['data_fechamento']."</td>";
						echo "<td align='center'>".$resultado['defeito_reclamado']."</td>";
						echo "<td align='center'>".$resultado['defeito_constatado']."</td>";
						echo "<td align='center'>".$resultado['solucao']."</td>";
						echo "</tr>";
					}

			?>
			</tbody>
			<? } ?>
			</table>

			<h2 class="subtitulo">Comentário</h2>
			<textarea name="comentario_5" rows="6" cols="100" class="frm"><?echo $comentario_5 ;?></textarea>
			<br>
			<br>

			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Relatório de Extratos que não efetuou devolução de peças</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th align="center">Extrato</th>
						<th align="right">Data Geração</th>
						<th align="right">Mão-de-Obra</th>
						<th align="right">Peças</th>
						<th align="right">Total</th>
					</tr>
				</thead>
				<tbody>
				<?
				$sql = " SELECT DISTINCT tbl_extrato.extrato,
								to_char(data_geracao,'DD/MM/YYYY') as data_geracao,
								mao_de_obra,
								pecas,
								total
						FROM tbl_extrato
						JOIN tbl_extrato_lgr USING(extrato)
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.posto = $posto
						AND   tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND   (qtde_nf = 0 or qtde_nf IS NULL)
						ORDER BY tbl_extrato.extrato ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td align='center'><a href='extrato_consulta_os.php?extrato=".$resultado['extrato']."' target='_blank'>".$resultado['extrato']."</a></td>";
						echo "<td align='right'>".$resultado['data_geracao']."</td>";
						echo "<td align='right'>".number_format($resultado['mao_de_obra'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['pecas'],2,",",".")."</td>";
						echo "<td align='right'>".number_format($resultado['total'],2,",",".")."</td>";
						echo "</tr>";
					}

				?>
				</tbody>
				<? } ?>
			</table>
			<br>

			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Relatório de OS recusada</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Produto</th>
						<th>Abertura</th>
						<th>Fechamento</th>
						<th>Extrato</th>
					</tr>
				</thead>
				<tbody>
				<?

				$sql = " SELECT tbl_os.os,
								tbl_os.sua_os,
								tbl_produto.descricao,
								to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura,
								to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento,
								tbl_os_status.extrato
						FROM tbl_os
						JOIN tbl_produto USING(produto)
						JOIN tbl_os_status USING(os)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os_status.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND tbl_os_status.status_os = 13
						AND tbl_os_status.extrato IS NOT NULL
						ORDER BY tbl_os.os ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td><a href='os_press.php?os=".$resultado['os']."' target='_blank'>".$resultado['sua_os']."</a></td>";
						echo "<td align='left'>".$resultado['descricao']."</td>";
						echo "<td align='center'>".$resultado['data_abertura']."</td>";
						echo "<td align='center'>".$resultado['data_fechamento']."</td>";
						echo "<td align='center'>".$resultado['extrato']."</td>";
						echo "</tr>";
					}

				?>
				</tbody>
				<? } ?>
			</table>
			<br>

			<table align="center" class="formulario" width="700" border="0">
				<caption class='titulo_coluna'>Relatório de OS aberta mais de 30 dias sem lançamento de peças</caption>
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Produto</th>
						<th>Abertura</th>
					</tr>
				</thead>
				<tbody>
				<?

				$sql = " SELECT tbl_os.os,
								tbl_os.sua_os,
								tbl_produto.descricao,
								to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura
						FROM tbl_os
						JOIN tbl_produto USING(produto)
						LEFT JOIN tbl_os_produto USING(os)
						LEFT JOIN tbl_os_item USING(os_produto)
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto = $posto
						AND   tbl_os.data_digitacao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
						AND tbl_os.data_digitacao < CURRENT_DATE - INTERVAL '30 DAYS'
						AND tbl_os_item.os_item IS NULL
						AND finalizada IS NULL
						ORDER BY tbl_os.os ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					$resultados = pg_fetch_all($res);
					foreach($resultados as $resultado){
						$cor = ($cor == '#F1F4FA') ? "#F7F5F0" : '#F1F4FA';

						echo "<tr class='table_line'  style='background-color: $cor;'>";
						echo "<td><a href='os_press.php?os=".$resultado['os']."' target='_blank'>".$resultado['sua_os']."</a></td>";
						echo "<td align='left'>".$resultado['descricao']."</td>";
						echo "<td align='center'>".$resultado['data_abertura']."</td>";
						echo "</tr>";
					}

				?>
				</tbody>
				<? } ?>
			</table>

			<h2 class="subtitulo">Conclusão</h2>
			<textarea name="conclusao" rows="6" cols="100" class="frm" ><?echo $conclusao ;?></textarea>

			<table align="center" class="formulario" width="700" border="0">
				<tr>
					<td nowrap >
						Será Necessário Visita ao Posto? 
						<label>
							<input type='radio' name='visita_posto' value='t' class='frm'>Sim
						</label>
						<label>
							<input type='radio' name='visita_posto' value='f' checked class='frm' >Não
						</label> 
					</td>
					<td>
						Data Visita
					</td>
					<td nowrap>
						<input type='text' name='data_visita' id='data_visita' size="12" maxlength="10" class='frm' value="<?echo date('d/m/Y');?>">
					</td>
				</tr>
			</table>
			<br>
			<center><input type='submit' name="btn_gravar" value="Gravar"></center>
			<br>
		</td>
	</tr>
</table>
<?}?>
</form>
<? include "rodape.php" ?>