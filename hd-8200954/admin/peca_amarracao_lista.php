<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
$btnacao = $_POST['btnacao'];
if(strlen($btnacao)>0){
	$referencia  = trim($_POST['referencia']);
	$descricao   = trim($_POST['descricao' ]);

	$referencia_2  = trim($_POST['referencia_2']);
	$descricao_2   = trim($_POST['descricao_2' ]);
//	$qtde  = trim($_POST['qtde']);

	if(strlen($referencia)==0)   $msg_erro .= "Por favor insira a peça Pai<BR>";
	if(strlen($referencia_2)==0) $msg_erro .= "Por favor insira a peça Filha<BR>";
//	if(strlen($qtde)==0)         $msg_erro .= "Por favor insira a qtde<BR>";

	if(strlen($msg_erro)==0){
		$sql = "SELECT tbl_peca.peca from tbl_peca where referencia='$referencia' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
	//	echo "$sql<BR>";
		if(pg_numrows($res)>0){
			$peca_pai = pg_result($res,0,0);
		}else{
			 $msg_erro .= "Peça Pai não encontrada<BR>";
		}

		$sql = "SELECT tbl_peca.peca from tbl_peca where referencia='$referencia_2' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$peca_filha = pg_result($res,0,0);
	//		echo "$sql<BR>";
		}else{
			 $msg_erro .= "Peça Filha não encontrada<BR>";
		}
	}
	if(strlen($msg_erro)==0){
		$sql = "SELECT tbl_lista_peca.peca_pai
				from tbl_lista_peca 
				where tbl_lista_peca.peca_pai = $peca_pai
				AND tbl_lista_peca.peca_filha = $peca_filha";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$msg_erro .= "Cadastro já existente<BR>";
				}
	}

	if(strlen($msg_erro)==0){//so entra se tiver as 2 pecas e qtde
			$sql = "INSERT INTO tbl_lista_peca(peca_pai, peca_filha)values($peca_pai, $peca_filha)";
			$res = pg_exec($con,$sql);
		//	echo "$sql<BR>";
			header("Location:$PHP_SELF");
	}

}
$apagar = $_GET['apagar'];
if(strlen($apagar)>0){

	$peca_pai = $_GET['peca_pai'];
	$peca_filha = $_GET['peca_filha'];
	if(strlen($peca_pai)==0 or strlen($peca_filha)==0) $msg_erro = "Erro";
	if(strlen($msg_erro)==0){
		$sql = "SELECT tbl_lista_peca.peca_pai
				from tbl_lista_peca 
				where tbl_lista_peca.peca_pai = $peca_pai
				AND tbl_lista_peca.peca_filha = $peca_filha";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$sql = "DELETE FROM tbl_lista_peca 
					where tbl_lista_peca.peca_pai = $peca_pai
					AND tbl_lista_peca.peca_filha = $peca_filha";
			$res = pg_exec($con,$sql);
		}
		header("Location:$PHP_SELF");
	}
}

$title = "Lista de peças X peças";
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
<style type="text/css">

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	color: #485989;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}
.Tabela{
/*	border:1px solid #d2e4fc; */
/*	background-color:#485989;*/
	}
.Div{
	BORDER-RIGHT:     #6699CC 1px solid; 
	BORDER-TOP:       #6699CC 1px solid; 
	BORDER-LEFT:      #6699CC 1px solid; 
	BORDER-BOTTOM:    #6699CC 1px solid; 
	FONT:             10pt Arial ;
	COLOR:            #000;
	BACKGROUND-COLOR: #FfFfFF;
}

</style>
</head>
<?
/*$peca = $_GET['peca'];
if(strlen($peca)>0){
	$sql = "select referencia, descricao from tbl_peca where peca = $peca and fabrica=$login_fabrica and ativo='t'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$referencia = pg_result($res,0,referencia);
		$descricao = pg_result($res,0,descricao);
	
	}
}*/
?>
<body>

<div id="wrapper">
<form name="frm_peca" method="post" action="<? $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<? echo $peca ?>">

<!-- formatando as mensagens de erro -->
<?
if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}

?>

<div class='error'>
	<? echo $msg_erro; ?>
</div>

<? } ?>

	<table width='400' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
	<tr>
		<td bgcolor='#D9E2EF' align='center' colspan=3><b>Peça Pai</b></td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF'><b>Referência</b> (*)</td>
		<td bgcolor='#D9E2EF'><b>Descrição</b> (*)</td>
	</tr>
	<tr>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' align='center' colspan=3><b>Peça Filha</b></td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF'><b>Referência</b> (*)</td>
		<td bgcolor='#D9E2EF' ><b>Descrição</b> (*)</td>
	</tr>
	<tr>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="referencia_2" value="<? echo $referencia_2 ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia_2,document.frm_peca.descricao_2,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="descricao_2" value="<? echo $descricao_2 ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia_2,document.frm_peca.descricao_2,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
	</tr>
</table><!-- ---------------------------------- Botoes de Acao ---------------------- -->

<div id="wrapper">
	<input type='hidden' name='btnacao' value=''>
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='gravar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
	<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='deletar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0' style="cursor:pointer;">
	<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
</div>
</form>
<p>


<? 
$sql = "SELECT distinct peca_pai, tbl_peca.referencia, tbl_peca.descricao
		from tbl_lista_peca
		join tbl_peca on tbl_peca.peca = tbl_lista_peca.peca_pai 
		where tbl_peca.fabrica = $login_fabrica order by tbl_peca.referencia";
$res = pg_exec($con,$sql);
//echo $sql;
flush;
//exit;
if(pg_numrows($res)>0){
	?>
<table  border = "0" cellspacing = "0" cellpadding = "0" width = "500" style="font-size:10px; font-face:verdana">
<tr>
<td>Todas listas de peças existentes</td>
</tr>
</table>
<table  border = "0" cellspacing = "2" cellpadding = "1" width = "500" style="font-size:10px; font-face:verdana border-collapse: collapse'">
<?
//	echo $sql;
 for($i=0;$i<pg_numrows($res);$i++){
	 $peca_pai = pg_result($res,$i,peca_pai);
	 $referencia       = pg_result($res,$i,referencia);
	 $descricao        = pg_result($res,$i,descricao);
	 echo "<tr><td align='center' colspan='3'  bgcolor='#485989'><font color='#FFFFFF'><B>$referencia - $descricao</b></font></td></tr>";

	$sqll = "SELECT tbl_lista_peca.peca_filha,tbl_peca.referencia, tbl_peca.descricao
			from tbl_lista_peca 
			join tbl_peca on tbl_lista_peca.peca_filha = tbl_peca.peca
			where tbl_peca.fabrica = $login_fabrica
			and tbl_lista_peca.peca_pai = $peca_pai order by tbl_peca.referencia";
	$ress = pg_exec($con,$sqll);
		//echo $sqll;
	if(pg_numrows($ress)>0){
		 for($j=0;$j<pg_numrows($ress);$j++){
			 $peca_filha              = pg_result($ress,$j,peca_filha);
			 $xreferencia       = pg_result($ress,$j,referencia);
			 $xdescricao        = pg_result($ress,$j,descricao);
		//	 $qtde              = pg_result($ress,$j,qtde);
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'>$xreferencia - $xdescricao</td>";
			echo "<td>$qtde</td>";
			echo "<td><a href='$PHP_SELF?apagar=true&peca_pai=$peca_pai&peca_filha=$peca_filha'>APAGAR</a></td>";
			echo "</tr>";

		 }
	}

 }?>
</table>

<?}?>



<? include "rodape.php"; ?>
