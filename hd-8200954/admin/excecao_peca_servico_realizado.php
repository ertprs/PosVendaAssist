<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["servico_realizado"]) > 0) $servico_realizado = trim($_GET["servico_realizado"]);
if (strlen($_POST["servico_realizado"]) > 0) $servico_realizado = trim($_POST["servico_realizado"]);
if (strlen($_POST["btnacao"]) > 0) $btnacao = trim($_POST["btnacao"]);
$msg_erro ="";


if ($btnacao == "gravar") {

	// Pe�a
	if (strlen($_POST["referencia"]) > 0){
		$referencia= trim($_POST["referencia"]);
	}else{
		$msg_erro = "Favor Selecionar a Pe�a.";
	}

	$sql = "SELECT  peca 
			FROM    tbl_peca 
			WHERE   fabrica = $login_fabrica
			AND     referencia= '$referencia';";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$peca = trim(pg_result($res,0,peca));
	}else{
		$msg_erro = "Pe�a n�o encontrada.";
	}

	if (strlen($servico_realizado) == 0) {
		$msg_erro = "Favor Selecionar o Servi�o Realizado.";
	}

	// Mao de Obra
	if (strlen($_POST["mao_de_obra"]) > 0){
		$mao_de_obra = trim($_POST["mao_de_obra"]);

		$mao_de_obra = str_replace( '.', '', $mao_de_obra);
		$mao_de_obra = str_replace( ',', '.', $mao_de_obra);
		$mao_de_obra = number_format($mao_de_obra, 2, '.','');

	}else{
		$msg_erro = "Favor digitar a m�o de obra.";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($servico_realizado) >0) {
			###INSERE NOVO REGISTRO
			$sql = "INSERT INTO 
					tbl_peca_servico_realizado (
						peca, 
						servico_realizado, 
						mao_de_obra
					) VALUES( 
						$peca, 
						$servico_realizado, 
						$mao_de_obra
					);";

			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro)>0){
				$msg_erro .= "<br>sql: $sql";
			}

			$res = @pg_exec ($con,"SELECT CURRVAL ('tbl_peca_servico_realizado_peca_servico_realizado_seq')");
			$x_servico_realizado  = pg_result ($res,0,0);
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E SUBMETE
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$msg= "Cadastro Realizado com Sucesso.";
/*		header ("Location: $PHP_SELF");
		exit;*/
	}else{
		###ABORTA OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E RECARREGA CAMPOS
		
		$descricao = $_POST["descricao"];
		$ativo     = $_POST["ativo"];
		$linha     = $_POST["linha"];
		$solucao    = $_POST["solucao"];
		
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($_GET["acao"] == "excluir") {
	
	$peca_servico_realizado = $_GET["peca_servico_realizado"];
	
	if (strlen($peca_servico_realizado) > 0) {
		$sql = "SELECT  peca_servico_realizado
				FROM    tbl_peca_servico_realizado 
				WHERE   peca_servico_realizado = $peca_servico_realizado";

		$res = pg_exec ($con,$sql);
		//echo "sql: $sql";

		if (pg_numrows($res) > 0) {
			$peca_servico_realizado = trim(pg_result($res,0,peca_servico_realizado));
		}else{
			$msg_erro = "Registro de exce��o n�o encontrada.";
		}
	}else{
		$msg_erro = "Registro de exce��o n�o encontrada.";
	}


	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN;");
		
		if (strlen($peca_servico_realizado) >0) {
			###EXCLUI
			$sql = "DELETE FROM tbl_peca_servico_realizado
					WHERE   peca_servico_realizado = $peca_servico_realizado";
			$res = pg_exec ($con,$sql);

			$msg_erro = pg_errormessage($con);
		}else{
			$msg_erro = "N�o tem a sele��o de pe�a x servi�o realizado.";
		}
	}
	
	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E SUBMETE
		$res = pg_exec ($con,"COMMIT ;");
		$msg= "Exclus�o Realizada com Sucesso.";
		//echo "sql: $sql";
/*		header ("Location: $PHP_SELF");
		exit;*/
	}else{
		###ABORTA OPERA��O DE INCLUS�O/EXLUS�O/ALTERA��O E RECARREGA CAMPOS
		$res = pg_exec ($con,"ROLLBACK; ");
	}
}



###CARREGA REGISTRO
if (strlen($servico_realizado) > 0) {
	$sql = "SELECT  descricao, ativo, linha, ressarcimento,troca_produto,solucao
			FROM    tbl_servico_realizado
			WHERE   fabrica = $login_fabrica
			AND     servico_realizado = $servico_realizado;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$descricao_servico     = trim(pg_result($res,0,descricao));
		$ativo         = trim(pg_result($res,0,ativo));
		$ressarcimento = trim(pg_result($res,0,ressarcimento));
		$troca_produto = trim(pg_result($res,0,troca_produto));
		$linha         = trim(pg_result($res,0,linha));
		$solucao       = trim(pg_result($res,0,solucao));

		$sql2 = "SELECT  descricao
			FROM    tbl_servico_realizado_idioma
			WHERE   servico_realizado = $servico_realizado
			AND     idioma = 'ES'  ";
		$res2 = @pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) $descricao_es   = trim(pg_result($res2,0,descricao));

	}
}

$layout_menu = "cadastro";
$title = "Cadastro de Exce��o: Pe�a x Servi�o Realizado";
include 'cabecalho.php';
?>
<script language="JavaScript">

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
</style>
<form name="frm_pec_ser_real" method="post" action="<? $PHP_SELF ?>">
<input type="hidden" name="servico_realizado" value="<? echo $servico_realizado ?>">

<? 

if(strlen($msg)>0){
	echo "<font color='blue'>$msg</font>";
}


if (strlen($msg_erro) > 0) { 
	echo  "<div class='error'>";
	echo $msg_erro; 
	echo "</div>";
} 

echo "<table width='600' border='0' bgcolor='#D9E2EF'  align='center' cellpadding='3' cellspacing='0' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td align='center' colspan='6' bgcolor='#596D9B' background='imagens_admin/azul.gif'>
		<font color='#FFFFFF'><B>Exce��o: Pe�a x Servi�o Realizado</B></font>
	</td>";
echo "</tr>";
echo "<tr>";
echo "<td align='center' colspan='1' >
		<font color='#000000'><B>Servi�o Realizado</B></font>
	</td>";
echo "<td align='center' colspan='2' >
		<font color='#000000'><B>Pe�a</B></font>
	</td>";
echo "<td align='center' colspan='3' >
		<font color='#000000'><B></B></font>
	</td>";

echo "</tr>";
echo "<tr>";
echo "<td align='left'>Descri��o Servi�o Realizado<br>
           <input class='frm' type='text' name='descricao_servico' value='$descricao_servico' size='30' maxlength='100'>
      </td>";
?>
	<td nowrap>
	Refer�ncia<br>
	  <input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20">
	    <a href="javascript: fnc_pesquisa_peca (document.frm_pec_ser_real.referencia,document.frm_pec_ser_real.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" >
		</a>
	</td>
	<td nowrap>
	Descri��o<br>
	  <input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50">
	    <a href="javascript: fnc_pesquisa_peca (document.frm_pec_ser_real.referencia,document.frm_pec_ser_real.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" >
		</a>
	</td>
<?

echo "<td align='left'>Vlr M�o de Obra<br>
           <input class='frm' type='text' name='mao_de_obra' value='' size='15' maxlength='100'>
      </td>";

echo "<td align='center' colspan='1'> <BR>";
echo "<input type='hidden' name='btnacao' value=''>";
echo "<IMG SRC='imagens_admin/btn_gravar.gif' ONCLICK=\"javascript: 
		if (document.frm_pec_ser_real.btnacao.value == '' ) { 
			document.frm_pec_ser_real.btnacao.value='gravar' ; 
			document.frm_pec_ser_real.submit() 
		} else { 
			alert ('Aguarde submiss�o') 
		}\" ALT='Gravar formul�rio' border='0' style='cursor:pointer;'>";
echo "</td>";
echo "<td align='center' colspan='1'><BR>";
echo "<IMG SRC='imagens_admin/btn_limpar.gif' ONCLICK=\"javascript: if (document.frm_pec_ser_real.btnacao.value == '' ) { document.frm_pec_ser_real.btnacao.value='reset' ; window.location='$PHP_SELF' } else { alert ('Aguarde submiss�o') }\" ALT='Limpar campos' border='0' style='cursor:pointer;'>";
echo "</td>";
echo "</tr>";
if($login_fabrica==20){
	echo "<tr>";
	echo "<td></td><td align='left'>Descri��o Espanhol(*)<BR><input type='text' name='descricao_es' value='$descricao_es' size='40' maxlength='50' class='frm'></td>";
	echo "</tr>";
}
echo "</table>";
echo "</form>";

	echo "<center><font size='2'><b>Rela��o de Servi�os Realizados </b><BR>

	</center>";

	echo "<center><font size='2' color='blue'><b>Clique no Servi�os Realizados para Cadastrar.</b></font><BR>

	</center>";

$sql =	"SELECT DISTINCT
				tbl_linha.linha ,
				tbl_linha.nome  
		FROM     tbl_linha
		WHERE    tbl_linha.fabrica = $login_fabrica
		ORDER BY tbl_linha.nome;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$linha      = trim(pg_result($res,$i,linha));
		$linha_nome = trim(pg_result($res,$i,nome));

		$sql2 =	"SELECT  servico_realizado ,
						 descricao         ,
						 ativo             
				FROM     tbl_servico_realizado
				WHERE    fabrica = $login_fabrica
				AND      linha = $linha
				ORDER BY descricao;";
		$res2 = pg_exec ($con,$sql2);

		if (pg_numrows($res2) > 0) {
			for ($j = 0 ; $j < pg_numrows($res2) ; $j++) {
				$servico_realizado = trim(pg_result($res2,$j,servico_realizado));
				$descricao         = trim(pg_result($res2,$j,descricao));
				$ativo             = trim(pg_result($res2,$j,ativo));

				if ($linha_nome <> $linha_nome_anterior) {
					echo "<table width='500' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
					echo "<tr bgcolor='#D9E2EF'>\n";
					echo "<td colspan='2'><b>$linha_nome</b></td>\n";
					echo "</tr>\n";
				}

				$cor = ($j % 2 == 0) ? "#F1F4FA": '#FFFFFF';

				echo "<tr bgcolor='$cor'>\n";
				echo "<td align='left'>";
				echo "<a href='$PHP_SELF?servico_realizado=$servico_realizado'>$descricao</a>";
				echo "</td>\n";
				echo "<td>";
				if ($ativo == 't') echo "Ativo";
				else               echo "Inativo";
				echo "</td>\n";
				echo "</tr>\n";

				$linha_nome = $linha_nome_anterior;
			}
			echo "</table>\n";
			echo "<br>\n";
		}
	}
}

$sql = "SELECT  servico_realizado ,
				descricao         ,
				ativo             ,
				solucao
		FROM    tbl_servico_realizado
		WHERE   fabrica = $login_fabrica
		AND      linha IS NULL
		ORDER BY descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<table width='500' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
	echo "<tr bgcolor='#D9E2EF'>\n";
	echo "<td colspan='2'><b>Geral</b></td>\n";
	if($login_fabrica==20){
		echo "<td align='left'>Solu��o</td>";
		echo "<td align='left'>Espanhol</td>";
	}
	echo "</tr>\n";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++){
		$servico_realizado = trim(pg_result($res,$i,servico_realizado));
		$descricao         = trim(pg_result($res,$i,descricao));
		$ativo             = trim(pg_result($res,$i,ativo));
		$solucao           = trim(pg_result($res,$i,solucao));

		$cor = ($i % 2 == 0) ? '#F1F4FA' : '#FFFFFF';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?servico_realizado=$servico_realizado'>$descricao</a>";
		echo "</td>\n";
		echo "<td>";
		if ($ativo == 't') echo "Ativo";
		else               echo "Inativo";
		echo "</td>\n";
		if($login_fabrica==20){
			echo "<td>";
			if ($solucao == 't') echo "Solu��o";
			else                 echo "&nbsp;";
			echo "</td>\n";
			
		
			$sql2 = "SELECT  descricao
				FROM    tbl_servico_realizado_idioma
				WHERE   servico_realizado = $servico_realizado
				AND     idioma = 'ES'  ";
			$res2 = @pg_exec ($con,$sql2);
	
			if (pg_numrows($res2) > 0)  echo "<td align='left'>".trim(pg_result($res2,0,descricao))."</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}

echo "<a href='$PHP_SELF?mostrar=todos'>Mostrar Todas as Exce��es</a>";
$mostrar= $_GET["mostrar"];
$servico_realizado= $_GET["servico_realizado"];
$whr= "";
if($mostrar == "todos" or strlen($servico_realizado)>0) { 
	if($mostrar == "todos"){
		$descricao_servico = "Todos os Servi�os Realizados";
		//n�o tem restri��o na busca
	}else{
		if(strlen($servico_realizado)>0){
			$whr = "and tbl_servico_realizado.servico_realizado = $servico_realizado";
		}

	}

$sql = "SELECT  tbl_servico_realizado.servico_realizado ,
				tbl_servico_realizado.descricao as desc_servico,
				tbl_servico_realizado.ativo             ,
				tbl_peca_servico_realizado.peca_servico_realizado,
				tbl_peca.descricao as desc_peca,
				tbl_peca.referencia,
				tbl_peca_servico_realizado.mao_de_obra

		FROM    tbl_servico_realizado
		JOIN tbl_peca_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_peca_servico_realizado.servico_realizado
		JOIN tbl_peca                   on tbl_peca.peca = tbl_peca_servico_realizado.peca
		WHERE   tbl_servico_realizado.fabrica = $login_fabrica
		$whr
		ORDER BY tbl_servico_realizado.descricao;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	if($mostrar <> "todos" and strlen($servico_realizado)>0){
		$descricao_servico = trim(pg_result($res,0,desc_servico));
	}
	
	echo "<table width='500' border='0' class='conteudo' cellpadding='2' cellspacing='1' align='center'>\n";
	echo "<tr bgcolor='#D9E2EF'>\n";
	echo "<td colspan='6'><b>M�o de Obra diferenciada: Pe�a X Servi�o Realizado <br>Busca por:$descricao_servico</b></td>\n";
	if($login_fabrica==20){
		echo "<td align='left'>Solu��o</td>";
		echo "<td align='left'>Espanhol</td>";
	}
	echo "</tr>\n";

	echo "<tr bgcolor='#c9d2EF'>\n";
	echo "<td nowrap><b>Descri��o Servi�o Realizado</b></td>\n";
	echo "<td nowrap><b>Pe�a</b></td>\n";
	echo "<td nowrap><b>Descri��o Pe�a</b></td>\n";
	echo "<td nowrap><b>M�o de Obra</b></td>\n";
	echo "<td nowrap><b>A��o</b></td>\n";
	echo "</tr>\n";

	for ($i = 0 ; $i < pg_numrows($res) ; $i++){

		$peca_servico_realizado= trim(pg_result($res,$i,peca_servico_realizado));
		$servico_realizado = trim(pg_result($res,$i,servico_realizado));
		$desc_servico      = trim(pg_result($res,$i,desc_servico));
		$ativo             = trim(pg_result($res,$i,ativo));
		$ref_peca         = trim(pg_result($res,$i,referencia));
		$desc_peca         = trim(pg_result($res,$i,desc_peca));
		$mao_de_obra       = trim(pg_result($res,$i,mao_de_obra));


		$cor = ($i % 2 == 0) ? '#F1F4FA' : '#FFFFFF';

		echo "<tr bgcolor='$cor'>\n";
		echo "<td>$desc_servico</td>\n";
		echo "<td>$ref_peca</td>\n";
		echo "<td>$desc_peca</td>\n";
		echo "<td>$mao_de_obra</td>\n";
		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?acao=excluir&peca_servico_realizado=$peca_servico_realizado'><font color='red'>excluir</font></a>";
		echo "</td>\n";
		if($login_fabrica==20){
			echo "<td>";
			if ($solucao == 't') echo "Solu��o";
			else                 echo "&nbsp;";
			echo "</td>\n";
			
		
			$sql2 = "SELECT  descricao
				FROM    tbl_servico_realizado_idioma
				WHERE   servico_realizado = $servico_realizado
				AND     idioma = 'ES'  ";
			$res2 = @pg_exec ($con,$sql2);
	
			if (pg_numrows($res2) > 0)  echo "<td align='left'>".trim(pg_result($res2,0,descricao))."</td>";
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
	echo "<br>\n";
}
}

include "rodape.php";
?>