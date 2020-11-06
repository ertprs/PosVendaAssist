<?php

if($_GET['btngerar']){
	$data_inicial = $_GET['datainicial'];
	$data_final = $_GET['datafinal'];

	if(!$data_inicial OR !$data_final)
		$erro = "Data Inválida";
//Início Validação de Datas
	if($data_inicial){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if($data_final){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if(strlen($erro)==0){
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final);//tira a barra
		$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($nova_data_final < $nova_data_inicial){
			$erro = "Data Inválida.";
		}

		//Fim Validação de Datas
	}
}

$admin_privilegios="gerencia";
$layout_menu = "gerencia";
$title = "RELATÓRIO DE ACESSO :: USUÁRIOS ADMIN";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario.php";

?>
<style>
	
	.rellinha0
	{
		background-color: #F1F4FA;
		border: solid 1px #d9e2ef;
	}

	.rellinha1
	{
		background-color: #F7F5F0;
		border: solid 1px #d9e2ef;
	}

	
	.rellink
	{
		text-decoration: none;
		font-weigth: normal;
		color: #000000;
	}

	/*Novo Estilo*/

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
}

.subtitulo{

color: #7092BE
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}
</style>

<script language="javascript">

$(function(){
	$('#datainicial').datePicker({startDate:'01/01/2000'});
	$('#datafinal').datePicker({startDate:'01/01/2000'});
	$("#datainicial").maskedinput("99/99/9999");
	$("#datafinal").maskedinput("99/99/9999");
});

</script>

	<form method='get' name='frm_relatorio'>
	<table class='formulario' align='center' width="700" border="0">
		<? if(strlen($erro)>0){ ?>
			<tr class="msg_erro"><td colspan="4"><? echo $erro; ?></td></tr>
		<? } ?>
		<tr>
			<td class='titulo_tabela' colspan='4'>Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr >
			<td align=right  >
				Data Inicial: 
			</td>
			<td width="120">
				<input type='text' id='datainicial' name='datainicial' class='frm' size=10 value=<?php echo $_GET["datainicial"];?>>
			</td>
			<td align=right width="70">
				Data Final: 
			</td>
			<td>
				<input type=text id='datafinal' name='datafinal' class='frm' size=10 value=<?php echo $_GET["datafinal"];?>>
			</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td></tr>
		</tr>
		<tr>
			<td colspan='4'>
				<input type='submit' value='Pesquisar' id='btngerar' name='btngerar'>
			</td>
		</tr>
	</table>
	<br>
	
	</form>

<?php
if (($_GET["datainicial"]) && ($_GET["datafinal"]))
{
	$data_inicial = implode("-", array_reverse(explode("/", $_GET["datainicial"])));
	$data_final = implode("-", array_reverse(explode("/", $_GET["datafinal"])));

	//SELECIONANDO ACESSOS NO PERÍODO
	$sql = "
	SELECT
	tbl_admin.admin,
	tbl_admin.login,
	COUNT(log_programa.programa) AS acessos
	FROM
	tbl_admin
	JOIN log_programa ON tbl_admin.admin=log_programa.admin
	WHERE
	tbl_admin.fabrica=$login_fabrica
	AND log_programa.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
	GROUP BY
	tbl_admin.admin,
	tbl_admin.login
	ORDER BY
	tbl_admin.login	";

	@$res = pg_exec($con, $sql);

	//************************* HTML DA TELA *************************//
	$colunas = 4;
	
	if ($res)
	{
		echo "
		<table class='tabela' border='0' align='center' width='700'>
			<tr class='titulo_coluna'>
				<td width=100>
					Usuário
				</td>
				<td width=70>
					Acessos
				</td>
				<td width=100>
					Último Acesso
				</td>
				<td width=380>
					Último Link Acessado
				</td>
			</tr>";

		for($i = 0; $i < pg_numrows($res); $i++)
		{
			$linha = $i % 2;

			//SELECIONANDO ÚLTIMO LINK E ÚLTIMO ACESSO
			$admin = pg_result($res, $i, admin);
			$login = pg_result($res, $i, login);
			$acessos = pg_result($res, $i, acessos);

			$sql = "SELECT MAX(data) FROM log_programa WHERE admin=$admin";
			$res_ultimo = pg_exec($con, $sql);
			$ultima_data = pg_result($res_ultimo, 0, 0);

			$sql = "
			SELECT
			programa
			FROM
			log_programa
			WHERE
			admin=$admin
			AND data='$ultima_data'
			";
			$res_ultimo = pg_exec($con, $sql);
			$ultimo_programa = pg_result($res_ultimo, 0, 0);
			$parts = explode(" ", $ultima_data);
			$parts[0] = implode("/", array_reverse(explode("-", $parts[0])));
			$parts[1] = explode(".", $parts[1]);
			$parts[1] = $parts[1][0];
			$ultima_data = $parts[0] . " " . $parts[1];

			echo "
			<tr class=rellinha$linha>
				<td>
					$login
				</td>
				<td>
					$acessos
				</td>
				<td>
					$ultima_data
				</td>
				<td>
					$ultimo_programa
				</td>
			</tr>";
		}

		echo "
		</table>";

		//************************* FIM HTML DA TELA *************************//
	}
	else
	{
		echo "
		<div class=relerro>
		Nenhuma acesso encontrado para as datas informadas
		</div>";
	}
}

?>


<? include "rodape.php" ?>