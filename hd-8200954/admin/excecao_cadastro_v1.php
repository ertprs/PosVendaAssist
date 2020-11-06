<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_GET["excecao_mobra"]) > 0) {//url
	$excecao_mobra = trim($_GET["excecao_mobra"]);
}

if (strlen($_POST["excecao_mobra"]) > 0) {//formulario
	$excecao_mobra = trim($_POST["excecao_mobra"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($excecao_mobra) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	
	$sql = "DELETE FROM tbl_excecao_mobra
			WHERE  tbl_excecao_mobra.fabrica       = $login_fabrica
			AND    tbl_excecao_mobra.excecao_mobra = $excecao_mobra";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btnacao == "gravar") {

	$posto      = $_POST["posto"];
	$posto_cnpj = $_POST["posto_cnpj"];
	$posto_cnpj = str_replace (".","",$posto_cnpj);
	$posto_cnpj = str_replace ("-","",$posto_cnpj);
	$posto_cnpj = str_replace ("/","",$posto_cnpj);
	$posto_cnpj = str_replace (" ","",$posto_cnpj);

	$posto_nome = $_POST["posto_nome"];

	$produto          = $_POST["produto"];
	$linha            = $_POST["linha"];
	$referencia       = $_POST["referencia"];
	$descricao        = $_POST["descricao"];
	$mobra            = $_POST["mobra"];
	$adicional_mobra  = $_POST["adicional_mobra"];
	$percentual_mobra = $_POST["percentual_mobra"];

	if (strlen($_POST["posto_cnpj"]) > 0) {
		$aux_posto_cnpj = "'". trim($_POST["posto_cnpj"]) ."'";
	}else{
		$msg_erro = "Digite o CNPJ do Posto.";
	}
	
	if (strlen($mobra) > 0 and strlen($adicional_mobra) > 0 and strlen($percentual_mobra) == 0) {
		$msg_erro = "É necessário optar por apenas uma das opções: Mão-de-obra, Adicional de mão-de-obra ou Percentual de mão-de-obra.";
	}
	
	if (strlen($mobra) > 0 and strlen($adicional_mobra) == 0 and strlen($percentual_mobra) > 0) {
		$msg_erro = "É necessário optar por apenas uma das opções: Mão-de-obra, Adicional de mão-de-obra ou Percentual de mão-de-obra.";
	}
	
	if (strlen($mobra) == 0 and strlen($adicional_mobra) > 0 and strlen($percentual_mobra) > 0) {
		$msg_erro = "É necessário optar por apenas uma das opções: Mão-de-obra ou Adicional de mão-de-obra ou Percentual de mão-de-obra.";
	}
	
	if (strlen($mobra) > 0 and strlen($adicional_mobra) > 0 and strlen($percentual_mobra) == 0) {
		$msg_erro = "É necessário optar por apenas uma das opções: Mão-de-obra, Adicional de mão-de-obra ou Percentual de mão-de-obra.";
	}
	
	if (strlen($mobra) == 0 and strlen($adicional_mobra) == 0 and strlen($percentual_mobra) == 0) {
		$msg_erro = "É necessário optar por uma das opções: Mão-de-obra, Adicional de mão-de-obra ou Percentual de mão-de-obra.";
	}
	
	if (strlen($produto) == 0) {
		$aux_produto = "null";
	}else{
		$aux_produto = "'$produto'";
	}
	
	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = "'$linha'";
	}
	
	if (strlen($mobra) == 0) {
		$aux_mobra = "null";
	}else{
		$aux_mobra = "'$mobra'";
	}
	
	if (strlen($adicional_mobra) == 0) {
		$aux_adicional_mobra = "null";
	}else{
		$aux_adicional_mobra = "'$adicional_mobra'";
	}
	
	if (strlen($percentual_mobra) == 0) {
		$aux_percentual_mobra = "null";
	}else{
		$aux_percentual_mobra = "'$percentual_mobra'";
	}
	
	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		if (strlen($referencia) > 0) {
		// produto
			$sql = "SELECT tbl_produto.produto
					FROM   tbl_produto
					JOIN   tbl_linha USING (linha)
					WHERE  UPPER (tbl_produto.referencia) = UPPER ('$referencia')
					AND    tbl_linha.fabrica      = $login_fabrica";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows ($res) == 0) {
				$msg_erro = "Produto $referencia não cadastrado";
			}else{
				$aux_produto = pg_result ($res,0,0);
			}
		}
		
		if (strlen($msg_erro) == 0) {
			// posto
			$sql = "SELECT tbl_posto.posto
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica USING (posto)
					WHERE  tbl_posto.cnpj            = '$posto_cnpj'
					AND    tbl_posto_fabrica.fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			
			if (@pg_numrows ($res) == 0) {
				$msg_erro = " Posto $posto_cnpj não cadastrado";
			}else{
				$posto = @pg_result ($res,0,0);
			}
		}
		
		if (strlen ($msg_erro) == 0) {
			if (strlen($excecao_mobra) == 0) {
				###INSERE NOVO REGISTRO
				$sql = "INSERT INTO tbl_excecao_mobra (
							fabrica              ,
							posto                ,
							produto              ,
							linha                ,
							mao_de_obra          ,
							adicional_mao_de_obra,
							percentual_mao_de_obra
						) VALUES (
							$login_fabrica                                ,
							$posto                                        ,
							$aux_produto                                  ,
							$aux_linha                                    ,
							(SELECT fnc_limpa_moeda($aux_mobra))          ,
							(SELECT fnc_limpa_moeda($aux_adicional_mobra)),
							(SELECT fnc_limpa_moeda($aux_percentual_mobra))
						);";
			}else{
				###ALTERA REGISTRO
				$sql = "UPDATE  tbl_excecao_mobra SET
								posto                  = $posto      ,
								produto                = $aux_produto,
								linha                  = $aux_linha  ,
								mao_de_obra            = (SELECT fnc_limpa_moeda($aux_mobra)),
								adicional_mao_de_obra  = (SELECT fnc_limpa_moeda($aux_adicional_mobra)),
								percentual_mao_de_obra = (SELECT fnc_limpa_moeda($aux_percentual_mobra))
						WHERE   tbl_excecao_mobra.fabrica       = $login_fabrica
						AND     tbl_excecao_mobra.excecao_mobra = $excecao_mobra;";
			}
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_exec ($con,"COMMIT");
			
			header ("Location: $PHP_SELF");
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			
			if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_excecao_mobra_unico\"") > 0)
				$msg_erro = "Esta exceção já esta cadastrada e não pode ser duplicada.";

			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


###CARREGA REGISTRO
if (strlen($excecao_mobra) > 0) {
	$sql = "SELECT  tbl_posto.posto              ,
					tbl_posto.cnpj               ,
					tbl_posto.nome               ,
					tbl_produto.produto          ,
					tbl_produto.referencia       ,
					tbl_produto.descricao        ,
					tbl_excecao_mobra.mao_de_obra,
					tbl_excecao_mobra.adicional_mao_de_obra,
					tbl_excecao_mobra.percentual_mao_de_obra
			FROM    tbl_excecao_mobra
			JOIN    tbl_posto     ON tbl_posto.posto     = tbl_excecao_mobra.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_excecao_mobra.produto
			LEFT JOIN tbl_linha   ON tbl_linha.linha     = tbl_excecao_mobra.linha
			WHERE   tbl_excecao_mobra.fabrica            = $login_fabrica
			AND     tbl_excecao_mobra.excecao_mobra      = $excecao_mobra;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$posto      = trim(pg_result($res,0,posto));
		$posto_cnpj = trim(pg_result($res,0,cnpj));
		$posto_cnpj = substr($posto_cnpj,0,2) .".". substr($posto_cnpj,2,3) .".". substr($posto_cnpj,5,3) ."/". substr($posto_cnpj,8,4) ."-". substr($posto_cnpj,12,2);
		$posto_nome = trim(pg_result($res,0,nome));
		$produto    = trim(pg_result($res,0,produto));
		$referencia = trim(pg_result($res,0,referencia));
		$descricao  = trim(pg_result($res,0,descricao));
		$mobra      = trim(pg_result($res,0,mao_de_obra));
		$mobra      = str_replace(".",",",$mobra);
		$adicional_mobra = trim(pg_result($res,0,adicional_mao_de_obra));
		$adicional_mobra = str_replace(".",",",$adicional_mobra);
		$percentual_mobra = trim(pg_result($res,0,percentual_mao_de_obra));
		$percentual_mobra = str_replace(".",",",$percentual_mobra);
	}
}

	$layout_menu = 'cadastro';
	$title = "Cadastramento de Exceções de Mão-de-Obra";
	include 'cabecalho.php';
?>

<script language="JavaScript">

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "cnpj" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.cnpj	= campo;
		janela.nome	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_produto (campo3, campo4, tipo) {
	if (tipo == "referencia" ) {
		var xxcampo = campo3;
	}

	if (tipo == "descricao" ) {
		var xxcampo = campo4;
	}

	if (xxcampo.value != "") {
		var url = "";
		url = "produto_excecao_pesquisa.php?campo=" + xxcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia	= campo3;
		janela.descricao	= campo4;
		janela.focus();
	}
}
</script>

<div id="wrapper">
	<form name="frm_excecao" method="post" action="<? $PHP_SELF ?>">
	<input type="hidden" name="excecao_mobra" value="<? echo $excecao_mobra ?>">
	<input type="hidden" name="posto" value="<? echo $posto ?>">
	<input type="hidden" name="produto" value="<? echo $produto ?>">


	<? if (strlen($msg_erro) > 0) { ?>

	<div class='error'>
		<? echo $msg_erro; ?>
	</div>

	<? } ?>

<table width="600" border="0" align='center' cellpadding="2" cellspacing="1">
		<tr  bgcolor="#D9E2EF">
			<td align="left">
				CNPJ Posto (*)
			</td>			
			<td>	Nome Posto (*)	
			</td>
		</tr>
		<tr>
			<td nowrap>
			<input type="text" class="frm" name="posto_cnpj" value="<? echo $posto_cnpj ?>" size="20" maxlength="20">
				<img src='imagens_admin/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_excecao.posto_cnpj, document.frm_excecao.posto_nome, 'cnpj')">
			</td>
			<td align="left" colspan="2" nowrap>
				<input type="text" class="frm" name="posto_nome" value="<? echo $posto_nome ?>" size="50" maxlength="50">
				<img src='imagens_admin/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_excecao.posto_cnpj, document.frm_excecao.posto_nome, 'nome')">
			</td>
		</tr>

	<tr  bgcolor="#D9E2EF">
		<td align="left">
			Produto</td>
		<td align="left" colspan="2">
		Descrição</td>
			
		</td>
	</tr>
	<tr>
	<td><input type="text" class="frm" name="referencia" value="<? echo $referencia ?>" size="20" maxlength="20">
			<img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_produto (document.frm_excecao.referencia, document.frm_excecao.descricao, 'referencia')">
	</td>
	<td><input type="text" class="frm" name="descricao" value="<? echo $descricao ?>" size="50" maxlength="50">
			<img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_produto (document.frm_excecao.referencia, document.frm_excecao.descricao, 'descricao')">
		</td>
	</tr>

	<tr  bgcolor="#D9E2EF">
		<td align="left" >
			Linha</td>
		<td align="left">
			Mão-de-obra (*) 
			Necessário se for apenas por este tópico
		</td>
	</tr>
	<tr>
	<td>
	<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select class='frm' name='linha'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
	</td>
	<td align="left" >
			<input type="text" class="frm" name="mobra" value="<? echo $mobra ?>" size="20" maxlength="20">
		</td>
	</tr>

	<tr bgcolor="#D9E2EF">
		<td align="left">
			Adicional de Mão-de-obra (*)<br>
			Necessário se for apenas por este tópico<br>
		</td>
		<td align="left" colspan="2">
			Percentual de Mão-de-obra (*)<br>
			Necessário se for apenas por este tópico<br>
		</td>
	</tr>
	<TR>  
		<td align='left'>
		<input type="text" class="frm" name="adicional_mobra" value="<? echo $adicional_mobra ?>" size="20" maxlength="20">
		</td>
		<td align='left'>
				<input type="text" class="frm" name="percentual_mobra" value="<? echo $percentual_mobra ?>" size="20" maxlength="20">
		</td>
	</tr>
</table>

<br><br>

<center>
<input type='hidden' name='btnacao' value=''>
<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_excecao.btnacao.value == '' ) { document.frm_excecao.btnacao.value='gravar' ; document.frm_excecao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_excecao.btnacao.value == '' ) { document.frm_excecao.btnacao.value='deletar' ; document.frm_excecao.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0' style="cursor:pointer;">
<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_excecao.btnacao.value == '' ) { document.frm_excecao.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
</center>

<p>

<h3>Para pesquisar, informe parte da descrição ou da referência e clique na lupa ao lado do campo. <br>Os campos com esta marcação (*) não poder ser nulos. </h3>

<p>

<h3><a href='<?echo $PHP_SELF?>?listar=ok'>Para pesquisar as exceções, aqui</a>.</h3>

<p>

<?
$sql = "SELECT  *
		FROM    tbl_excecao_mobra
		JOIN    tbl_produto ON tbl_produto.produto = tbl_excecao_mobra.produto
		JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
		JOIN    tbl_posto   ON tbl_posto.posto     = tbl_excecao_mobra.posto
		WHERE   tbl_linha.fabrica = $login_fabrica;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

?>

<hr>
<div id='subBanner'>
	<h1>.:: Relação de Exceções de Mão-de-Obra ::.</h1>
	<h2>Para efetuar alterações, clique na descrição do produto.</h2>
</div>

<? } ?>


<?
$sql = "SELECT      DISTINCT
					tbl_excecao_mobra.produto
		FROM        tbl_excecao_mobra
		JOIN        tbl_produto ON tbl_produto.produto = tbl_excecao_mobra.produto
		JOIN        tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
		JOIN        tbl_posto   ON tbl_posto.posto     = tbl_excecao_mobra.posto
		WHERE       tbl_linha.fabrica = $login_fabrica
		ORDER BY    tbl_excecao_mobra.produto;";
$res = pg_exec ($con,$sql);

for ($x = 0 ; $x < pg_numrows($res) ; $x++){
	$div = false;
	
	$produto = trim(pg_result($res,$x,produto));
	
	$sql = "SELECT      tbl_excecao_mobra.excecao_mobra ,
						tbl_posto.cnpj                  ,
						tbl_posto.nome                  ,
						tbl_produto.referencia          ,
						tbl_produto.descricao           ,
						tbl_excecao_mobra.mao_de_obra
			FROM        tbl_excecao_mobra
			JOIN        tbl_produto ON  tbl_produto.produto = tbl_excecao_mobra.produto
			JOIN        tbl_linha   ON  tbl_linha.linha     = tbl_produto.linha
			JOIN        tbl_posto   ON  tbl_posto.posto     = tbl_excecao_mobra.posto
			WHERE       tbl_linha.fabrica         = $login_fabrica
			AND         tbl_excecao_mobra.produto = $produto
			ORDER BY    tbl_produto.descricao;";
	$res0 = pg_exec ($con,$sql);
	
	if (pg_numrows($res0) > 0) {
		$div = true;
	}
	
	if ($div == true) {
		#echo "<div id=\"wrapper\">\n";
	}
	
	for ($y = 0 ; $y < pg_numrows($res0) ; $y++){
		$excecao_mobra  = trim(pg_result($res0,$y,excecao_mobra));
		$posto_cnpj     = trim(pg_result($res0,$y,cnpj));
		$fposto_cnpj    = substr($posto_cnpj,0,2) .".". substr($posto_cnpj,2,3) .".". substr($posto_cnpj,5,3) ."/". substr($posto_cnpj,8,4) ."-". substr($posto_cnpj,12,2);
		$posto_nome     = trim(pg_result($res0,$y,nome));
		$referencia     = trim(pg_result($res0,$y,referencia));
		$descricao      = trim(pg_result($res0,$y,descricao));
		$mobra          = trim(pg_result($res0,$y,mao_de_obra));
		
		if ($posto_cnpj <> $posto_cnpj_anterior) {
			echo "<hr>\n";
			
			echo "<div id='middleCol'>\n";
			echo "    <h1>« $fposto_cnpj - $posto_nome »</h1>\n";
			echo "</div>\n";
			
			$quebra = true;
		}else{
			$quebra = false;
			echo "<div id='wrapper'>\n";
				echo "<div id='middleCol'>\n";
					echo "    $referencia - <a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>$descricao</a> - ". number_format($mobra,2,",",".") ."\n";
				echo "</div>\n";
			echo "</div>\n";

		}
		
		if ($quebra == true) {
			echo "<div id='wrapper'>\n";
				echo "<div id='middleCol'>\n";
					echo "    $referencia - <a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>$descricao</a> - ". number_format($mobra,2,",",".") ."\n";
				echo "</div>\n";
			echo "</div>\n";
		}
		
		$posto_cnpj_anterior = trim(pg_result($res0,$y,cnpj));
	}
	
	if ($div == true) {
		#echo "</div>\n";
	}
}
?>
</form>
</div>


<?
if ($_GET['listar'] == 'ok') {
	$sql = "SELECT  tbl_excecao_mobra.excecao_mobra         ,
					tbl_posto_fabrica.codigo_posto          ,
					tbl_posto.cnpj                          ,
					tbl_posto.nome                          ,
					tbl_produto.referencia                  ,
					tbl_produto.descricao                   ,
					tbl_linha.nome              AS linha    ,
					tbl_excecao_mobra.mao_de_obra           ,
					tbl_excecao_mobra.adicional_mao_de_obra ,
					tbl_excecao_mobra.percentual_mao_de_obra
			FROM    tbl_excecao_mobra
			JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_excecao_mobra.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_posto            ON tbl_posto.posto           = tbl_posto_fabrica.posto
			LEFT JOIN tbl_produto        ON tbl_produto.produto       = tbl_excecao_mobra.produto
			LEFT JOIN tbl_linha AS l1    ON l1.linha                  = tbl_produto.linha
										AND l1.fabrica                = $login_fabrica
			LEFT JOIN tbl_linha          ON tbl_linha.linha           = tbl_excecao_mobra.linha
										AND tbl_linha.fabrica         = $login_fabrica
			WHERE   tbl_excecao_mobra.fabrica = $login_fabrica
			ORDER BY tbl_posto_fabrica.codigo_posto;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='100%'  align='center' border='1' cellspacing='1' cellpadding='1'>";
		echo "<tr>";
		
		echo "<td bgcolor='#00324A' align='left'><font size='2' color='#FFFFFF' face='Verdana, Arial, Helvetica, san-serif'>POSTO</font></td>";
		echo "<td bgcolor='#00324A' align='center'><font size='2' color='#FFFFFF' face='Verdana, Arial, Helvetica, san-serif'>PRODUTO</font></td>";
		echo "<td bgcolor='#00324A' align='center'><font size='2' color='#FFFFFF' face='Verdana, Arial, Helvetica, san-serif'>LINHA</font></td>";
		echo "<td bgcolor='#00324A' align='center'><font size='2' color='#FFFFFF' face='Verdana, Arial, Helvetica, san-serif'>MÃO-DE-OBRA</font></td>";
		echo "<td bgcolor='#00324A' align='center'><font size='2' color='#FFFFFF' face='Verdana, Arial, Helvetica, san-serif'>ADICIONAL</font></td>";
		echo "<td bgcolor='#00324A' align='center'><font size='2' color='#FFFFFF' face='Verdana, Arial, Helvetica, san-serif'>PERCENTUAL</font></td>";
		
		echo "</tr>";
		
		for ($z = 0 ; $z < pg_numrows($res) ; $z++){
			$cor = '#E2E9F5';
			if ($z % 2 == 0){
				$cor = '#F1F4FA';
			}
			
			$excecao_mobra    = trim(pg_result($res,$z,excecao_mobra));
			$cnpj             = trim(pg_result($res,$z,cnpj));
			$cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
			$codigo_posto     = trim(pg_result($res,$z,codigo_posto));
			$posto            = trim(pg_result($res,$z,nome));
			$produto          = trim(pg_result($res,$z,referencia)) ."-". trim(pg_result($res,$z,descricao));
			if (strlen($referencia) == 0) $produto = "TODOS";
			$linha            = trim(pg_result($res,$z,linha));
			if (strlen($linha) == 0) $linha = "TODAS";
			$mobra            = trim(pg_result($res,$z,mao_de_obra));
			$adicional_mobra  = trim(pg_result($res,$z,adicional_mao_de_obra));
			$percentual_mobra = trim(pg_result($res,$z,percentual_mao_de_obra));
			
			echo "<tr>";
			
			echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>$codigo_posto - $posto</a></font></td>";
			echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$produto</font></td>";
			echo "<td align='left' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$linha</font></td>";
			echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($mobra,2,",",".") ."</font></td>";
			echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($adicional_mobra,2,",",".") ."</font></td>";
			echo "<td align='right' bgcolor='$cor'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>". number_format($percentual_mobra,2,",",".") ."</font></td>";
			
			echo "</tr>";
		}
		echo "</table>";
	}
}


include "rodape.php";
?>

</body>
</html>