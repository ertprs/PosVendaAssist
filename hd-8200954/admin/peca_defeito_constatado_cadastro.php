<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";


if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {
	$qtde_peca = $_POST ['qtde_peca'];
	$qtde_defeito_constatado = $_POST ['qtde_defeito_constatado'];
	for ($i = 0 ; $i < $qtde_peca ; $i++) {
		$peca = $_POST ['peca$i'];
		for ($x = 0 ; $x < $qtde_defeito_constatado ; $x++){
			$defeito_constatado = $_POST ['defeito_constatado$i$x'];
			$paga_deslocamento = $_POST ['paga_deslocamento$i$x'];
			$insert = $_POST ['insert$i$x'];
			if ($insert == 't'){
				if($paga_deslocamento == 't' ){
					$sql = "INSERT INTO tbl_peca_deslocamento (
								peca            ,
								defeito_constatado          ,
								paga_deslocamento
							) VALUES (
								$peca            ,
								$defeito_constatado          ,
								$paga_deslocamento
							)";
					$res = pg_exec ($con,$sql);
					if (pg_errormessage ($con) > 0) $msg_erro .= pg_errormessage ($con);
				}
			}else{
				$sql = "UPDATE tbl_peca_deslocamento SET
							paga_deslocamento = $paga_deslocamento
						WHERE peca  = $peca
						AND defeito_constatado = $defeito_constatado";
				$res = pg_exec ($con,$sql);
				if (pg_errormessage ($con) > 0) $msg_erro .= pg_errormessage ($con);
			}
		}
	}
	if (strlen ($msg_erro) == 0) {
		echo "<script language='JavaScript'>
				alert('Gravado com sucesso!');
			<\script>";
	}
}
?>

<style type="text/css">

.text_curto {
	text-align: center;
	font-weight: bold;
	color: #000;
	background-color: #FF6666;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

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
<?
$visual_black = "manutencao-admin";

$title       = "pecas Autorizados X Deslocamentos";
$cabecalho   = "pecas Autorizados X Deslocamentos";
$layout_menu = "cadastro";
include 'cabecalho.php';
?>

<form name="frm_peca" method="post" action="<? echo $PHP_SELF ?>">

<p>
<?
$sql = "SELECT	tbl_peca.peca                  ,
				tbl_peca.referencia            ,
				tbl_peca.descricao                      
		FROM	tbl_peca
		WHERE tbl_peca.fabrica = $login_fabrica
		ORDER BY tbl_peca.descricao";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table align='center' border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<input type='hidden' name='qtde_peca' value=".pg_numrows ($res).">";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$peca = pg_result($res,$i,peca);


		if ($i % 20 == 0) {
			flush();
			echo "<tr class='Titulo'>";
			echo "<td>NOME</td>";

			$sql = "SELECT  tbl_defeito_constatado.defeito_constatado          ,
							tbl_defeito_constatado.descricao        ,
							tbl_defeito_constatado.codigo   ,
							tbl_defeito_constatado.ativo
					FROM    tbl_defeito_constatado
					WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
					ORDER BY tbl_defeito_constatado.codigo;";
			$res3 = pg_exec ($con,$sql);
			if (pg_numrows($res3) > 0) {
				echo "<input type='hidden' name='qtde_defeito_constatado' value=".pg_numrows ($res3).">";
				for ($x = 0 ; $x < pg_numrows($res3) ; $x++){
					$defeito_constatado        = trim(pg_result($res3,$x,defeito_constatado));
					$descricao      = trim(pg_result($res3,$x,descricao));
					$codigo_defeito_constatado = trim(pg_result($res3,$x,codigo));
					$ativo          = trim(pg_result($res3,$x,ativo));
					if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'>";
					else            $ativo = "<img src='imagens/status_vermelho.gif'>";
					echo "<td align='center'>$codigo_defeito_constatado<br>$descricao<br>$ativo</td>";
				}
				echo "</tr>\n";
			}
		}
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td nowrap align='left'><input type='hidden' name='peca$i' value=$peca>".pg_result($res,$i,descricao)."</td>";
		$sql = "SELECT tbl_defeito_constatado.defeito_constatado
					FROM    tbl_defeito_constatado
					WHERE   tbl_defeito_constatado.fabrica = $login_fabrica
					ORDER BY tbl_defeito_constatado.codigo;";
		$res3 = pg_exec ($con,$sql);
		if (pg_numrows($res3) > 0) {
			for ($x = 0 ; $x < pg_numrows($res3) ; $x++){
				$defeito_constatado = trim(pg_result($res3,$x,defeito_constatado));
				
				echo "<td align='center'>";
				echo "<input type='hidden' name='defeito_constatado$i$x' value=$defeito_constatado>";

				echo "</td>";
			}
			echo "</tr>\n";
		}
	}
	echo "</table>";
}

?>

<p>
<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_peca.btn_acao.value == '' ) { document.frm_peca.btn_acao.value='gravar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<p>

<? include "rodape.php"; ?>
