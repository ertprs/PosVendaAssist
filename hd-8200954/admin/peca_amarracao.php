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
	$qtde  = trim($_POST['qtde']);

	if(strlen($referencia)==0)   $msg_erro .= "Por favor insira a peça Pai<BR>";
	if(strlen($referencia_2)==0) $msg_erro .= "Por favor insira a peça Amarrada<BR>";
	if(strlen($qtde)==0)         $msg_erro .= "Por favor insira a qtde<BR>";

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
			$peca_amarra = pg_result($res,0,0);
	//		echo "$sql<BR>";
			
		}else{
			 $msg_erro .= "Peça Amarrada não encontrada<BR>";
		}
	}

	if(strlen($msg_erro)==0){//so entra se tiver as 2 pecas e qtde
		$sql = "SELECT peca_obrigatoria from tbl_peca_obrigatoria where peca_obrigatoria = $peca_pai";
		$res = pg_exec($con,$sql);
	//	echo "$sql<BR>";
		if(pg_numrows($res)==0){
			$sql = "INSERT INTO tbl_peca_obrigatoria(peca_obrigatoria)values($peca_pai)";
			if(pg_exec($con,$sql))
				$msg = "Gravado com Sucesso!";
			$sql = "SELECT	peca_obrigatoria   ,
							peca               ,
							qtde 
					from tbl_peca_obrigatoria_item 
					where peca_obrigatoria=$peca_pai 
					and peca = $peca_amarra";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res)==0){
				$sql = "INSERT INTO tbl_peca_obrigatoria_item (peca_obrigatoria,peca,qtde)
												values ($peca_pai,$peca_amarra,$qtde)";
				if(pg_exec($con,$sql))
				    $msg = "Gravado com Sucesso!";
			}else{
				$sql = "UPDATE tbl_peca_obrigatoria_item set qtde=$qtde where peca_obrigatoria = $peca_pai and peca = $peca_amarra";
				if(pg_exec($con,$sql))
				    $msg = "Gravado com Sucesso!";
			}
			header("Location:$PHP_SELF?msg={$msg}");
		}else{
			$sql = "SELECT	peca_obrigatoria   ,
							peca               ,
							qtde 
					from tbl_peca_obrigatoria_item 
					where peca_obrigatoria=$peca_pai 
					and peca = $peca_amarra";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==0){
				$sql = "INSERT INTO tbl_peca_obrigatoria_item (peca_obrigatoria,peca,qtde)
												values ($peca_pai,$peca_amarra,$qtde)";
				if(pg_exec($con,$sql))
				    $msg = "Gravado com Sucesso!";
			}else{
				$sql = "UPDATE tbl_peca_obrigatoria_item set qtde=$qtde where peca_obrigatoria = $peca_pai and peca = $peca_amarra";
				if(pg_exec($con,$sql))
				    $msg = "Gravado com Sucesso!";
			}
			header("Location:$PHP_SELF?msg={$msg}");
		}
	}

}
$apagar = $_REQUEST['apagar'];
if(strlen($apagar)>0){

	$peca_pai = $_GET['peca_pai'];
	$peca_amarrada = $_GET['peca_amarrada'];
	if(strlen($peca_pai)==0 or strlen($peca_amarrada)==0) $msg_erro = "Erro";
	if(strlen($msg_erro)==0){
		$sql = "SELECT peca from tbl_peca_obrigatoria_item where peca_obrigatoria = $peca_pai and peca = $peca_amarrada";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$sql = "DELETE FROM tbl_peca_obrigatoria_item where peca_obrigatoria = $peca_pai and peca = $peca_amarrada";
				if(pg_exec($con,$sql))
                    $msg='Excluído com Sucesso!';

			$sql = "SELECT peca from tbl_peca_obrigatoria_item where peca_obrigatoria = $peca_pai";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)==0){
				$sql = "DELETE FROM tbl_peca_obrigatoria where peca_obrigatoria = $peca_pai";
			    if(pg_exec($con,$sql))
                    $msg='Excluído com Sucesso!';
			}
			header("Location:$PHP_SELF?msg={$msg}");
		}else{
			$sql = "DELETE FROM tbl_peca_obrigatoria where peca_obrigatoria = $peca_pai";
			if(pg_exec($con,$sql))
			    $msg='Excluído com Sucesso!';
			    
			header("Location:$PHP_SELF?msg={$msg}");
		}

	}
}

$title = "AMARRAÇÃO DE PEÇAS OBRIGATÓRIAS";
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
	} else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
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
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
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

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}


.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
    padding: 5px 0;
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
<?php
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
        
    echo "<div class='msg_erro' style='width:700px;margin: 0 auto;'>{$msg_erro}</div>";
} 

$msg = $_REQUEST['msg'];
if (strlen($msg) > 0 )
    echo "<div class='sucesso' style='width:700px;margin: 0 auto;'>{$msg}</div>";


?>
	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  class='formulario'>
	<tr class='titulo_tabela'>
		<td align='center' colspan="5">Peça Única</td>

	</tr>
	
	<tr>
		<td width="30">&nbsp;</td>
		
		<td>Referência *</td>
		<td colspan=2>Descrição *</td>
		
		<td width="30">&nbsp;</td>
	</tr>
	
	<tr>
		<td width="30">&nbsp;</td>
		
		<td ><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="../imagens/lupa.png" ></a></td>
		<td  colspan=2><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="45" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="../imagens/lupa.png" ></a></td>
		
		<td width="30">&nbsp;</td>
	</tr>
	
	<tr>
		<td>&nbsp;</td>
	</tr>
	
	<tr class='titulo_tabela'>
		<td align='center' colspan='5'>Peça Amarrada</td>
	</tr>
	
	<tr>
		<td width="30">&nbsp;</td>
		
		<td style='width:140px'>Referência *</td>
		<td style='width:320px'>Descrição *</td>
		<td  >Qtde *</td>
	
		<td width="30">&nbsp;</td>
	</tr>
	
	<tr>
		<td width="30">&nbsp;</td>
		
		<td ><input class='frm' type="text" name="referencia_2" value="<? echo $referencia_2 ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia_2,document.frm_peca.descricao_2,'referencia')"><IMG SRC="../imagens/lupa.png" ></a></td>
		<td ><input class='frm' type="text" name="descricao_2" value="<? echo $descricao_2 ?>" size="45" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia_2,document.frm_peca.descricao_2,'descricao')"><IMG SRC="../imagens/lupa.png" ></a></td>
		<td ><input class='frm' type="text" name="qtde" value="<? echo $qtde ?>" size="3" maxlength="3"></td>
		
		<td width="30">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan='4' align='center'>
		<br>
		<div id="wrapper">

	<input type='hidden' name='btnacao' value=''>
	
	<!-- HD 353066
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='gravar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;"> 
	-->
	
	<input type="button" value="Gravar" style="cursor:pointer;font:12px Arial;" onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='gravar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" title='Clique Para Gravar'>
	
	<!-- HD 353066
	<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='deletar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0' style="cursor:pointer;">
	-->
	
	<!--<input type="button" value="Apagar" style="cursor:pointer;font:12px Arial;" onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='deletar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" title='Clique Para Apagar'>-->
	
	<!-- HD 353066
	<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
	-->
	<input type="button" value="Limpar" style="cursor:pointer;font:12px Arial;" onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" title='Clique Para Limpar o Formulário'>
	
</div>
		</td>
	</tr>
</table><!-- ---------------------------------- Botoes de Acao ---------------------- -->
<br>

</form>
<p>


<? 
$sql = "SELECT peca_obrigatoria, tbl_peca.referencia, tbl_peca.descricao
		from tbl_peca_obrigatoria 
		join tbl_peca on tbl_peca.peca = tbl_peca_obrigatoria.peca_obrigatoria 
		where tbl_peca.fabrica = $login_fabrica order by tbl_peca.referencia";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	?>
<table  border="0" cellspacing="0" align="center" cellpadding="0" width="700" style="font-size:10px; font-face:verdana">
<tr class="titulo_tabela">	
<td>Todas amarrações existentes</td>
</tr>
</table>
<table  border="0" cellspacing="1" cellpadding="2" align="center" width="700" class='tabela'>
<?
//	echo $sql;
 for($i=0;$i<pg_numrows($res);$i++){
	 $peca_obrigatoria = pg_result($res,$i,peca_obrigatoria);
	 $referencia       = pg_result($res,$i,referencia);
	 $descricao        = pg_result($res,$i,descricao);
		 echo "
			 <tr class='titulo_coluna'>
				<td align='center'>
					$referencia - $descricao
				</td>
				
				<td >
					Qtd.
				</td>
				<td>
					Ações
				</td>
			</tr>
		";
	$sqll = "SELECT tbl_peca_obrigatoria_item.peca,tbl_peca.referencia, tbl_peca.descricao, tbl_peca_obrigatoria_item.qtde 
			from tbl_peca_obrigatoria_item 
			join tbl_peca on tbl_peca_obrigatoria_item.peca = tbl_peca.peca
			where tbl_peca.fabrica = $login_fabrica
			and tbl_peca_obrigatoria_item.peca_obrigatoria = $peca_obrigatoria order by tbl_peca.referencia";
	$ress = pg_exec($con,$sqll);
//		echo $sqll;
	if(pg_numrows($ress)>0){
		 for($j=0;$j<pg_numrows($ress);$j++){
			 $peca              = pg_result($ress,$j,peca);
			 $xreferencia       = pg_result($ress,$j,referencia);
			 $xdescricao        = pg_result($ress,$j,descricao);
			 $qtde              = pg_result($ress,$j,qtde);
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'>$xreferencia - $xdescricao</td>";
			echo "<td>$qtde</td>";
			# HD 353066 - Alteraçao do link para botão
			echo "<td>	
			<input type='button' style='cursor:pointer;font:12px Arial' value='Apagar' onclick=\"window.location='$PHP_SELF?apagar=true&peca_pai=$peca_obrigatoria&peca_amarrada=$peca'\">
			</td>";
			echo "</tr>";

		 }
	}

 }?>
</table>

<?}?>



<? include "rodape.php"; ?>
