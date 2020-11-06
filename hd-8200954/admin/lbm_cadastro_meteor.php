<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';


if ($login_fabrica_nome <> "Meteor") {
	header ("Location: lbm_cadastro.php");
	exit;
}


$msg_erro = "";
$referencia = trim ($_POST['referencia']);

if (strlen ($referencia) > 0) {
	$sql = "SELECT tbl_produto.produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_produto.referencia = '$referencia' AND tbl_linha.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) {
		$produto = pg_result ($res,0,0);

		$caixa_referencia = $_POST['caixa_referencia'];
		$caixa_descricao  = $_POST['caixa_descricao'];
		if (strlen ($caixa_referencia) > 0) {
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$caixa_referencia' AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {
				$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, origem) VALUES ($login_fabrica, '$caixa_referencia','$caixa_descricao','IMP')";
				$res = pg_exec ($con,$sql);
			}
		}

		$mecanica_referencia = $_POST ['mecanica_referencia'];
		$mecanica_descricao  = $_POST ['mecanica_descricao'];
		if (strlen ($caixa_referencia) > 0) {
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$mecanica_referencia' AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {
				$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, origem) VALUES ($login_fabrica, '$mecanica_referencia','$mecanica_descricao','IMP')";
				$res = pg_exec ($con,$sql);
			}
		}

		$pulseira_referencia = $_POST['pulseira_referencia'];
		$pulseira_descricao  = $_POST['pulseira_descricao'];
		if (strlen ($caixa_referencia) > 0) {
			$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$pulseira_referencia' AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0) {
				$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, origem) VALUES ($login_fabrica, '$pulseira_referencia','$pulseira_descricao','IMP')";
				$res = pg_exec ($con,$sql);
			}
		}

	}else{
		$msg_erro = "Produto não cadastrado";
	}

}
?>

<?
	$title='Cadastro de Lista Básica';
	$layout_menu = 'cadastro';
	include 'cabecalho.php';
?>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde 
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}
</script>

<script language="JavaScript">
function fnc_pesquisa_produto (campo,tipo) {
	var url = "";
	url = "produto_pesquisa_2.php?metodo=fill&campo=" + campo.value + "&tipo=" + tipo;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.referencia = document.frm_lbm.referencia;
	janela.descricao  = document.frm_lbm.descricao;
	janela.focus();
}

</script>

<div id="container" style="width: 500px;">
<p>
<form name="frm_lbm" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='produto' value='<? echo $produto ?>'>
	<div id="contentcenter" style="width: 500px;">
		<div id="contentleft2" style="width: 100px;">
			Referência
		</div>
		<div id="contentleft2" style="width: 82px;">
			&nbsp;
		</div>
		<div id="contentleft2" style="width: 100px;">
			Descrição
		</div>
		<div id="contentleft2" style="width: 20px;">
			&nbsp;
		</div>
	</div>

	<div id="contentcenter" style="width: 500px;">
		<div id="contentleft2" style="width: 100px;">
		<input class="frm" type="text" size="15" maxlength="20" name="referencia" value="<? echo $referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre aqui com a REFERÊNCIA ou parte dela. Em seguida clique com o ponteiro <br>do mouse sobre a lupa à direita do campo para abrir a janela de busca.');">
		</div>
		<div id="contentleft2" style="width: 30px; text-align: left;">
			<A HREF="#"><img src="imagens_admin/btn_buscar5.gif" onclick="javascript:fnc_pesquisa_produto(this,'referencia')"></A>
		</div>
		<div id="contentleft2" style="width: 290px;">
		<input class="frm" type="text" size="45" maxlength="45" name="descricao" value="<? echo $descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre aqui com a DESCRIÇÃO DO PRODUTO ou parte dela. Em seguida clique com o ponteiro <br>do mouse sobre a lupa à direita do campo para abrir a janela de busca.');">
		</div>
		<div id="contentleft2" style="width: 20px; text-align: left;">
			<A HREF="#"><img src="imagens_admin/btn_buscar5.gif" onclick=""></A>
		</div>

	</div>
</div>
<p>

<hr>

<DIV id="container" style="width: 500px;">

	<div id="leftCol" style="width: 100px;">
			&nbsp;
	</div>

	<div id="middleCol" style="width: 130px; background-color: #808080; text-align: center; color: #ffffff; margin-right: 10px;" >
			Código da Peça
	</div>

	<div id="middleCol" style="width: 130px; background-color: #808080; text-align: center; color: #ffffff; margin-right: 10px;">
			Descrição
	</div>

	<div id="middleCol" style="width: 70px; background-color: #808080; text-align: center; color: #ffffff; margin-right: 10px;">
			Valor
	</div>


</div>

<DIV id="container" style="width: 500px;">

	<div id="leftCol" style="text-align: left; width: 90px; background-color: #808080; text-align: center; color: #ffffff; margin-right: 10px;">
			Caixa
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" NAME="caixa_referencia" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" NAME="caixa_descricao" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" size="10" NAME="caixa_preco" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

</div>

<DIV id="container" style="width: 500px;">


	<div id="leftCol" style="text-align: left; width: 90px; background-color: #808080; text-align: center; color: #ffffff; margin-right: 10px;">
			Mecânica
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" NAME="mecanica_referencia" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" NAME="mecanica_descricao" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" size="10" NAME="mecanica_preco" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

</div>


<DIV id="container" style="width: 500px;">


	<div id="leftCol" style="text-align: left; width: 90px; background-color: #808080; text-align: center; color: #ffffff; margin-right: 10px;">
			Pulseira
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" NAME="pulseira_referencia" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" NAME="pulseira_descricao" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

	<div id="middleCol">
			<INPUT TYPE="text" class= "frm" size="10" NAME="pulseira_preco" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;AQUI VAI DIGITADO O TEXTO DE HELP DO CAMPO.');">
	</div>

</div>

<p>
<DIV id="container" style="width: 500px;">

		<A HREF="#"><IMG SRC="imagens_admin/btn_gravar.gif" ALT="Gravar" onclick="document.frm_lbm.submit()"></A>
		<A HREF="#"><IMG SRC="imagens_admin/btn_limpar.gif" ALT="Limpar" onclick=""></A>

</div>

<DIV id="container" style="width: 700px;">
	<div class="frm-on" id="displayArea">&nbsp;</div>
</div>
	</form>
</div>


	<div id="footer">
		<a  href="#">www.telecontrol.com.br</a></li>
		</div>
	</DIV>
	</div>
</div>
</body>
</html>