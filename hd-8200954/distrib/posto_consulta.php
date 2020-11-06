<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

#Alterado por Sono em 08/08/2006 "WHERE $condicao and tbl_posto_fabrica.fabrica <> 21"#
#Pois estava listando postos movidos para fabrica 21#

#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_POST['btn_altera']) > 0) {
	
	$posto       = trim($_POST['posto']);
	$endereco    = trim($_POST['endereco']);
	$numero      = trim($_POST['numero']);
	$complemento = trim($_POST['complemento']);
	$bairro      = trim($_POST['bairro']);
	$cep         = trim($_POST['cep']);
	$cidade      = trim($_POST['cidade']);
	$estado      = trim($_POST['estado']);
	$email       = trim($_POST['email']);
	$fone        = trim($_POST['fone']);
	$fax         = trim($_POST['fax']);
	$parcial     = trim($_POST['parcial']);

	$res = pg_query($con,"BEGIN TRANSACTION");
	
	$sql = "UPDATE tbl_posto SET
			endereco         = '$endereco'    ,
			numero           = '$numero'      ,
			complemento      = '$complemento' ,
			bairro           = '$bairro'      ,
			cep              = '$cep'         ,
			cidade           = '$cidade'      ,
			estado           = '$estado'      ,
			email            = '$email'       ,
			fone             = '$fone'        ,
			fax              = '$fax'         ,
			data_expira_sintegra  = current_date
			WHERE tbl_posto.posto = $posto";
	
	$res      = pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (!empty($parcial)) {
		# HD 221731
		$sql2 = "UPDATE tbl_posto_extra
					SET atende_pedido_faturado_parcial = '$parcial'
				  WHERE posto = $posto";
		
		$rs2      = pg_exec($con,$sql2);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
	} else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}

}

if (substr ($btn_acao,0,5) == "Logar" ) {
	$posto_codigo = $_POST['codigo_posto'];
	$fabrica      = $_POST['fabrica'];

	$sql = "SELECT codigo_posto, senha FROM tbl_posto_fabrica WHERE fabrica = $fabrica AND codigo_posto = '$posto_codigo'";

	$res = pg_exec ($con,$sql);

	$senha = pg_result ($res,0,senha);
	$posto_codigo = pg_result ($res,0,codigo_posto);

	echo "<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&redir=sim&acao=validar'>";
	echo "<input type='hidden' name='login'>";
	echo "<input type='hidden' name='senha'>";
	echo "<input type='hidden' name='btnAcao' value='Enviar'>";
	echo "</form>";

	echo "\n";
	echo "<script language='javascript'>\n";
	echo "document.write ('redirecionando') ; \n";
	echo "document.frm_login.login.value = '$posto_codigo' ; \n";
	echo "document.frm_login.senha.value = '$senha' ; \n";
	echo "document.frm_login.submit() ; \n";
	echo "document.location = '$PHP_SELF' ; \n";
	echo "</script>";
	echo "\n";

	exit ;

}

?>

<html>
<head>
<title>Consulta/Alteração Endereço de Postos no Cadastro Telecontrol (tbl_posto)<br>(*)Para alterar dados do Fabricante você têm que logar como fábrica para alterar!</title>
<link type="text/css" rel="stylesheet" href="css/css.css">

</head>
<script>
	function alteraDado(tipo,dado,posto){
		window.open('atualiza_posto.php?tipo=' + tipo+'&dado=' + dado +'&posto=' +posto,"janela","toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no,width=300, height=300, top=18, left=0");
	}
</script>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}
</style>
<body>

<?

include 'menu.php';

$codigo_posto = trim($_POST['codigo_posto']);
$nome         = trim($_POST['nome']);
$cidade       = trim($_POST['cidade']);

?>

<center><h1>Consulta Endereço de Postos</h1></center>

<p>

<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Código do Posto <input type='text' class='frm' size='10' name='codigo_posto' value='<?=$codigo_posto?>' />
Razão Social <input type='text' class='frm' size='25' name='nome' value='<?=$nome?>' />
Cidade <input type='text' class='frm' size='15' name='cidade' value='<?=$cidade?>' />

<br />

<input type='submit' name='btn_acao' value='Pesquisar' />

</form>
</center>

<?

if (strlen ($codigo_posto) > 1 OR strlen ($nome) > 2 OR strlen ($cidade) > 3 ) {
	if (strlen ($codigo_posto) > 1) {
		$condicao = " tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%' ";
	}

	if (strlen ($nome) > 1) {
		$condicao = " tbl_posto.nome ILIKE '%$nome%' ";
	}

	if (strlen ($cidade) > 1) {
		$condicao = " tbl_posto.cidade ILIKE '%$cidade%' ";
	}
#alterado por Samuel para listar somente postos Britânia
#		WHERE $condicao and tbl_posto_fabrica.fabrica <> 21
# HD 32665 ROnaldo quer consultar britania, Gama Italy , Htech
	$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.*
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica IN (SELECT DISTINCT fabrica FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE distribuidor = $login_posto )
		JOIN (SELECT DISTINCT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto) linha ON tbl_posto.posto = linha.posto
		WHERE $condicao and tbl_posto_fabrica.fabrica in ( SELECT DISTINCT fabrica FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE distribuidor = $login_posto )
		ORDER BY tbl_posto.nome ";
	$sql = "SELECT tbl_posto_fabrica.fabrica as fabrica,
				   tbl_fabrica.nome as nome_fabrica,
				   tbl_posto_fabrica.codigo_posto,
				   tbl_posto_fabrica.senha,
				   tbl_posto_fabrica.credenciamento,
				   tbl_posto.posto,
				   tbl_posto.nome,
				   tbl_posto.cnpj,
				   tbl_posto.ie,
				   tbl_posto_fabrica.contato_endereco as endereco,
				   tbl_posto_fabrica.contato_numero as numero,
				   tbl_posto_fabrica.contato_complemento as complemento,
				   tbl_posto_fabrica.contato_cep as cep,
				   tbl_posto_fabrica.contato_bairro as bairro,
				   tbl_posto_fabrica.contato_cidade as cidade,
				   tbl_posto_fabrica.contato_estado as estado,
				   tbl_posto_fabrica.contato_email as email,
				   tbl_posto_fabrica.contato_fone_comercial as fone,
				   tbl_posto_fabrica.contato_fax
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
		/* ronaldo quer alterar por causa da loja virtual qq fabrica AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).") */
		JOIN tbl_fabrica using(fabrica)
		WHERE $condicao
		/* ronaldo quer alterar por causa da loja virtual qq fabrica
		and tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).") */
		ORDER BY tbl_posto.nome ";
//echo nl2br($sql);
//exit ;

	$res = pg_exec($con,$sql);

	for ($i = 0; $i < pg_numrows($res); $i++) {

		$codigo_posto   = pg_result($res,$i,codigo_posto);
		$fabrica        = pg_result($res,$i,fabrica);
		$posto          = pg_result($res,$i,posto);
		$credenciamento = pg_result($res,$i,credenciamento);
		$senha          = pg_result($res,$i,senha);
		$nome           = pg_result($res,$i,nome);
		$cnpj           = pg_result($res,$i,cnpj);
		$ie             = pg_result($res,$i,ie);
		$endereco       = trim(pg_result($res,$i,endereco));
		$numero         = trim(pg_result($res,$i,numero));
		$complemento    = trim(pg_result($res,$i,complemento));
		$cep            = trim(pg_result($res,$i,cep));
		$bairro         = trim(pg_result($res,$i,bairro));
		$cidade         = trim(pg_result($res,$i,cidade));
		$estado         = trim(pg_result($res,$i,estado));
		$fone           = trim(pg_result($res,$i,fone));
		$fax            = trim(pg_result($res,$i,fax));
		$email          = trim(pg_result($res,$i,email));

		# HD 221731
		$sql2        = "SELECT * FROM tbl_posto_extra where posto = $posto";
		$rs2         = pg_exec($con,$sql2);
		$parcial[$i] = trim(pg_result($rs2,0,atende_pedido_faturado_parcial));

		echo "<form name='$i' method='POST' id='form'> ";
			echo "<table border='1' cellspacing='0' align='center' width='600'>";
				echo "<tr bgcolor='#eeeeee' align='center' style='font-weight:bold'>";
					echo "<td align='center' nowrap colspan='3'>";
					echo pg_result ($res,$i,nome_fabrica);
					echo " - ";
					echo pg_result ($res,$i,codigo_posto);
					echo " - ";
					echo "<a href =\"javascript: alteraDado('nome','$nome','$posto')\">";
					echo pg_result ($res,$i,nome);
					echo "</a>";
					$cor = "#596d9b";
					if ($credenciamento <> "CREDENCIADO") $cor = "RED";
					echo "<br><font color='$cor'><b>$credenciamento</b></font>";
					echo "</td>";
				echo "</tr>";
				echo "<tr bgcolor='#eeeeee' align='center' style='font-weight:bold'>";
					echo "<td align='center' nowrap colspan='3'>";
						echo "<a href =\"javascript: alteraDado('cnpj','$cnpj','$posto')\">CNPJ: ";
							echo pg_result ($res,$i,cnpj);
						echo "</a>";
						echo "<a href =\"javascript: alteraDado('ie','$ie','$posto')\">";
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;I.E.: ";
						echo pg_result ($res,$i,ie);
						echo "</a>";
					echo "</td>";
				echo "</tr>";
				echo "<tr class='menu_top' align='center'>";
					echo "<td>ENDEREÇO</td>";
					echo "<td>NÚMERO</td>";
					echo "<td>COMPLEMENTO</td>";
				echo "</tr>";
				echo "<tr bgcolor='#eeeeee' align='center'>";
					echo "<td><input type='text' name='endereco' size='39' maxlength='50' value='$endereco'></td>";
					echo "<td><input type='text' name='numero' size='10' maxlength='10' value='$numero'></td>";
					echo "<td><input type='text' name='complemento' size='25' maxlength='20' value='$complemento'></td>";
				echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' align='center' width='600'>";
				echo "<tr class='menu_top'>";
					echo "<td>CEP</td>";
					echo "<td>BAIRRO</td>";
					echo "<td>CIDADE</td>";
					echo "<td>ESTADO</td>";
				echo "</tr>";
				echo "<tr bgcolor='#eeeeee' align='center' >";
					echo "<td><input type='text' name='cep'    size='10' maxlength='8' value='$cep'></td>";
					echo "<td><input type='text' name='bairro' size='30' maxlength='40' value='$bairro '></td>";
					echo "<td><input type='text' name='cidade' size='30' maxlength='30' value='$cidade'></td>";
					echo "<td><input type='text' name='estado' size='2'  maxlength='2'  value='$estado'></td>";
				echo "</tr>";
			echo "</table>";

			echo "<table border='1' cellspacing='0' align='center' width='600'>";
				echo "<tr class='menu_top'>";
					echo "<td>TELEFONE</td>";
					echo "<td>FAX</td>";
					echo "<td>E-MAIL</td>";
					echo "<td>PEDIDO PARCIAL</td>";# HD 221731
				echo "</tr>";
				echo "<tr bgcolor='#eeeeee' align='center'>";
					echo "<td><input type='text' name='fone' size='12' maxlength='30' value='$fone'></td>";
					echo "<td><input type='text' name='fax' size='12' maxlength='30' value='$fax'></td>";
					echo "<td><input type='text' name='email' size='40' maxlength='50' value='$email'></td>";
					echo "<td>";
						echo "<select name='parcial'>";# HD 221731
							echo "<option value='f' ".($parcial[$i] == 'f' ? 'selected="selected"' : '').">Total</option>";
							echo "<option value='t' ".($parcial[$i] == 't' ? 'selected="selected"' : '').">Parcial</option>";
						echo "</select>";
					echo "</td>";
				echo "</tr>";
			echo "</table>";

			echo "<input type='hidden' name='codigo_posto' value='$codigo_posto'>";
			echo "<input type='hidden' name='fabrica' value='$fabrica'>";
			echo "<input type='hidden' name='posto' value='$posto'>";

			if(trim($senha)=="*" OR $credenciamento <> "CREDENCIADO"){
				echo"<center><input type='submit' name='btn_acao' disabled value='Descredenciado ou sem senha'></center>";
			}else{ ?>
			<center>
				<input type='submit' name='btn_acao' value='Logar como este posto'>
				<button type='button' name='etiqueta_end_posto'
					 onClick="window.open('posto_end_etiqueta.php?posto=<?=$posto?>','Imprimir','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=640,height=480,top=24,left=32');">Imprimir Etiqueta Endereço</button>
			</center>
			<?}
			echo "&nbsp;<input type='submit' name='btn_altera' value='Alterar'></center>";

		echo "</form>";

		echo "<hr />";

	}

}

#include "rodape.php"; ?>

</body>
</html>
