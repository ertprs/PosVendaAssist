<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios='gerencia';
include 'autentica_admin.php';

$layout_menu = 'cadastro';
$title = 'CUSTO TEMPO POR PRODUTO';

include 'cabecalho.php';

?>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='js/jquery.js'></script>
<script language='javascript'>

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
		
	}
	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}


</script>
<style type="text/css">
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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco td{
	padding:10px 0 10px;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<div id="msg" style="width:700px;margin:auto;"></div>
<?
$familia = $_POST["familia"];
	echo "<FORM name='frm' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='700' class='formulario' cellpadding='0' cellspacing='1' align='center'>";
	echo "<tr >";
	echo "<td class='titulo_tabela' >Cadastro</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";
	
		echo "<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>";
	
		echo "<tr>";
		echo "<td colspan='4'>&nbsp;</td>";
		echo "</tr>";	

		echo "<tr width='100%' >";
		echo "<td colspan='2' align='right' height='20'>Referência Produto:&nbsp;</td>";
		echo "<td colspan='2' align='left'>";
		echo "<input class='frm' type='text' name='produto_referencia' size='15' maxlength='20' value='$produto_referencia'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_produto (document.frm.produto_referencia,document.frm.produto_descricao,'referencia')\">";

		echo "</td>";
		echo "</tr>";
		echo "<td colspan='2' align='right' height='20'>Descrição Produto:&nbsp;</td>";
		echo "<td colspan='2' align='left'>";
		echo "<input class='frm' type='text' name='produto_descricao' size='30' value='$produto_descricao'>&nbsp;<img src='imagens/lupa.png'  style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto (document.frm.produto_referencia,document.frm.produto_descricao,'descricao')\"></A>";

		echo "</td>";
		echo "</tr>";
		

	echo "</table>";
	echo "</td>";
	echo "</tr>";


//--====== VER CUSTO TEMPO CADASTRADO ==============================================================
$btn_cad_forn=$_GET['bt_cad_forn'];
if(strlen($btn_cad_forn) ==0) { $btn_cad_forn=$_POST['bt_cad_forn'];}
if(strlen($btn_cad_forn) > 0) {

	$referencia = $_GET["produto_referencia"];
	if(strlen($referencia) == 0) 	$referencia = $_POST["produto_referencia"];

	if(strlen($referencia)>0){
		$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
		$res = pg_exec ($con,$sql);
		if(pg_numrows($res) > 0) {
			$produto = trim(pg_result($res,0,produto));
	
			$sql  = "
				SELECT  tbl_defeito_constatado.defeito_constatado,
					tbl_defeito_constatado.descricao         ,
					tbl_defeito_constatado.codigo
				FROM tbl_defeito_constatado
				WHERE tbl_defeito_constatado.fabrica = $login_fabrica
				ORDER BY codigo";
			$res = pg_exec ($con,$sql);

			echo "<table width='700' class='tabela' cellpadding='0' cellspacing='1' align='center'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td align='left'>Descrição</td>";
			echo "<td width='100px'>Tempo</td>";
			echo "<td>Ação</td>";
			echo "</tr>";	
			for ($i=0; $i<pg_numrows($res); $i++){
		
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
				$descricao          = trim(pg_result($res,$i,descricao))         ;
				$codigo             = trim(pg_result($res,$i,codigo))            ;
		
				$sql2 = "SELECT	 tbl_produto_defeito_constatado.produto_defeito_constatado,
								 tbl_produto_defeito_constatado.mao_de_obra  ,
								 tbl_produto_defeito_constatado.unidade_tempo
							FROM tbl_produto_defeito_constatado
							WHERE defeito_constatado = $defeito_constatado
							AND   produto            = $produto";
				$res2 = pg_exec ($con,$sql2);
				if(pg_numrows($res2)>0){
					$produto_defeito_constatado  = trim(pg_result($res2,0,produto_defeito_constatado))     ;
					$unidade_tempo               = trim(pg_result($res2,0,unidade_tempo))     ;
					$mao_de_obra                 = trim(pg_result($res2,0,mao_de_obra))       ;
				}else{
					$unidade_tempo = '';
				}

				echo "<tr bgcolor='$cor'>";
				echo "<td align='left'><font size='2'>$codigo - $descricao&nbsp;</td>";
				echo "<td align='left'><input type='text' class='Caixa' size='5' name='$defeito_constatado' value='$unidade_tempo'><font size='2'>U.T.";
				echo "</td>";
				if(strlen($unidade_tempo) > 0 and is_numeric($unidade_tempo)) {
					echo "<td><input type=\"button\" onclick=\"window.location='$PHP_SELF?apagar=$produto_defeito_constatado&produto=$produto'\" value='Apagar' /></td>";
				}
				else echo "<td></td>";
				echo "</tr>";
			}
			echo "<BR>";
			echo "</table>";

		}else{
			$msg_erro = 1;
			echo "<div class='msg_erro' style='width:700px;margin:auto;'>Produto não encontrado</div>";
		}
	}
}
		if(strlen($referencia) ==0 || $msg_erro== 1) {
			echo "<td align='center'><INPUT TYPE='submit' name='bt_cad_forn' value='Consultar' onClick=\"if (this.value=='Consultando...'){ alert('Aguarde');}else {this.value='Consultando...'; }\"></td>";
		} else if(strlen($referencia)>0) {
			echo "<td align='center'><INPUT TYPE='submit' name='btn_acao' value='Gravar'></td>";
		}
		echo "</form>";
		echo "<tr>";
			echo "<td colspan='4'>&nbsp;</td>";
			echo "</tr>";
		echo "</table>";

$btn_acao=$_GET['btn_acao'];
if(strlen($btn_acao) == 0) $btn_acao=$_POST['btn_acao'];

if(strlen($btn_acao) > 0) {

	$referencia = $_GET["produto_referencia"];
if(strlen($referencia) == 0) 	$referencia = $_POST["produto_referencia"];

	if(strlen($referencia)>0){
		$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
		$res = pg_exec ($con,$sql);
		$produto = trim(pg_result($res,0,produto));

		$sql  = "
		SELECT  tbl_defeito_constatado.defeito_constatado,
			tbl_defeito_constatado.descricao         ,
			tbl_defeito_constatado.codigo
		FROM tbl_defeito_constatado
		WHERE tbl_defeito_constatado.fabrica = $login_fabrica
		ORDER BY codigo";
		$res = pg_exec ($con,$sql);

		for ($i=0; $i<pg_numrows($res); $i++){
			$ut          = '';
			$mao_de_obra = '';
			$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
		
			$ut .= trim($_POST["$defeito_constatado"]);


			if(strlen($ut) > 0 and is_numeric($ut)){

				$sql2 = "SELECT	 tbl_produto_defeito_constatado.produto_defeito_constatado,
								 tbl_produto_defeito_constatado.mao_de_obra  ,
								 tbl_produto_defeito_constatado.unidade_tempo
					FROM tbl_produto_defeito_constatado
					WHERE defeito_constatado = $defeito_constatado
					AND   produto            = $produto";

				$res2 = pg_exec ($con,$sql2);

				$mao_de_obra = $ut * 2.2;

				if(pg_numrows($res2)>0){

					$unidade_tempo              = trim(pg_result($res2,0,unidade_tempo));
					$produto_defeito_constatado = trim(pg_result($res2,0,produto_defeito_constatado));
					
					if($unidade_tempo <> $ut){
						$sql3 = "
						UPDATE tbl_produto_defeito_constatado SET 
								unidade_tempo = $ut         ,
								mao_de_obra   = $mao_de_obra
							WHERE defeito_constatado = $defeito_constatado 
							AND produto = $produto;";
						$res3 = pg_exec ($con,$sql3);
						$erro = pg_errormessage($con);

						$msg = '<div style="display:none;" id="msg_update" ';
						if(!empty($erro))
							$msg .= 'class="msg_erro">Falha ao gravar';
						else
							$msg .= 'class="sucesso">Gravado com Sucesso';
						$msg .= '</div>';
						echo $msg;
					}
				}else{
					
					$sql3 = "
						INSERT INTO tbl_produto_defeito_constatado 
							(produto,defeito_constatado,unidade_tempo,mao_de_obra)
						VALUES ($produto,$defeito_constatado,$ut,$mao_de_obra)
						";
					$res3 = pg_exec ($con,$sql3);

				}
			}
		}
	}
}

$apagar=$_GET['apagar'];
$produto=$_GET['produto'];
if(strlen($apagar) > 0 and strlen($produto) > 0) {
	$sql4 =" DELETE 
				FROM tbl_produto_defeito_constatado
				WHERE produto_defeito_constatado = $apagar;";
	$res=pg_exec($con,$sql4);
}
?>
	<script type="text/javascript">
		$("#msg_update").appendTo("#msg").fadeIn("slow");
	</script>
<?
include 'rodape.php';
?>
