<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';




# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	
	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}


if(strlen($btn_pedido)>0){
	$sql = "SELECT  PE.pedido                                  ,
					PE.total                                   ,
					to_char(PE.data,'DD/MM/YYYY') AS data      ,
					LI.nome                       AS linha_nome
			FROM      tbl_pedido PE
			LEFT JOIN tbl_linha  LI ON PE.linha = LI.linha
			WHERE     PE.fabrica = $login_fabrica
			AND       PE.posto   = $posto
			AND       PE.linha   = $linha
			AND       PE.pedido_loja_virtual IS TRUE
			AND       PE.finalizado          IS NULL";
	$res = pg_exec($con,$sql);
	if (pg_numrows ($res) == 0) {
		$sql = "SELECT	tipo_pedido
				FROM	tbl_tipo_pedido
				WHERE	descricao IN ('Faturado','Venda','ACESSORIOS')
				AND		fabrica = $login_fabrica
				ORDER BY descricao LIMIT 1";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0 ) $aux_tipo_pedido = pg_result($res,0,tipo_pedido);
		else                       $aux_tipo_pedido = " null ";

		if($login_fabrica == 1) $pedido_acessorio = "TRUE";
		else                    $pedido_acessorio = "FALSE";

		$sql = "INSERT INTO tbl_pedido (
					posto          ,
					fabrica        ,
					condicao       ,
					tipo_pedido    ,
					pedido_loja_virtual,
					linha              ,
					pedido_acessorio   ,
					admin              
				) VALUES (
					$posto              ,
					$login_fabrica      ,
					NULL                ,
					$aux_tipo_pedido    ,
					TRUE,
					$linha,
					$pedido_acessorio   ,
					$login_admin
				)";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro) == 0){
			$res = pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido  = pg_result ($res,0,0);




			$sql = "SELECT codigo_posto, senha FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo'";

			$res = pg_exec ($con,$sql);

			$senha = pg_result ($res,0,senha);
			$posto_codigo = pg_result ($res,0,codigo_posto);

			echo "<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>";
			echo "<input type='hidden' name='login'>";
			echo "<input type='hidden' name='senha'>";
			echo "<input type='hidden' name='login_admin'>";
			echo "<input type='hidden' name='pedido'>";
			echo "<input type='hidden' name='btnAcao' value='Enviar'>";
			echo "</form>";
			
			echo "\n";
			echo "<script language='javascript'>\n";
			echo "document.write ('redirecionando') ; \n";
			echo "document.frm_login.login.value = '$posto_codigo' ; \n";
			echo "document.frm_login.senha.value = '$senha' ; \n";
			echo "document.frm_login.login_admin.value = '$login_admin' ; \n";
			echo "document.frm_login.pedido.value = '$pedido' ; \n";
			echo "document.frm_login.submit() ; \n";
			echo "document.location = '$PHP_SELF' ; \n";
			echo "</script>";
			echo "\n";

			exit ;

		}
	}else{
		$linha_nome = pg_result($res,0,linha_nome);
		$msg_erro = "Já existe um pedido em aberto para esta linha $linha_nome";
	}
}

if(strlen($_GET["pedido"]>0)){
			$sql = "SELECT codigo_posto, senha FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = '$posto'";

			$res = pg_exec ($con,$sql);

			$senha = pg_result ($res,0,senha);
			$posto_codigo = pg_result ($res,0,codigo_posto);

			echo "<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>";
			echo "<input type='hidden' name='login'>";
			echo "<input type='hidden' name='senha'>";
			echo "<input type='hidden' name='login_admin'>";
			echo "<input type='hidden' name='pedido'>";
			echo "<input type='hidden' name='btnAcao' value='Enviar'>";
			echo "</form>";
			
			echo "\n";
			echo "<script language='javascript'>\n";
			echo "document.write ('redirecionando') ; \n";
			echo "document.frm_login.login.value = '$posto_codigo' ; \n";
			echo "document.frm_login.senha.value = '$senha' ; \n";
			echo "document.frm_login.login_admin.value = '$login_admin' ; \n";
			echo "document.frm_login.pedido.value = '$pedido' ; \n";
			echo "document.frm_login.submit() ; \n";
			echo "document.location = '$PHP_SELF' ; \n";
			echo "</script>";
			echo "\n";

			exit ;

}
$title       = "Pedidos da Loja Virtual";
$cabecalho   = "Pedidos da Loja Virtual";
$layout_menu = "callcenter";

include 'cabecalho.php';

?>

<script language="JavaScript">
function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (tipo == "codigo" ) {
		var xcampo = campo3;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_credenciamento.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome		= campo;
		janela.posto	= campo2;
		janela.codigo	= campo3;
		janela.focus();
	}
}

</script>



<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#codigo").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo").result(function(event, data, formatted) {
		$("#nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome").result(function(event, data, formatted) {
		$("#codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<style type="text/css">

#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}

#Relatorio{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #E7EDF5;
}
#Relatorio thead{
	text-align: left;
	font-weight: bold;
	color:#FFFFFF;
	background-color: #385887;
}
#Relatorio tbody td{
	text-align: left;
}
#Relatorio tfoot td{
	text-align: left;
}
#Relatorio caption{
	color:#000048;
	text-align: center;
	font-weight: bold;
	font-size: 12pt;
}

</style>

<? 
if($msg_erro){
?>
	<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
	<tr align='center'>
		<td class='error'>
			<? echo $msg_erro; ?>
		</td>
	</tr>
	</table>
<?
} 
?> 
<p>

<form name="frm" method="POST" action="<? echo $PHP_SELF; ?>">
<input type="hidden" name="credenciamento" value="<? echo $credenciamento ?>">
<input type='hidden' name='btn_acao' value=''>

<table class="border" width='650' align='center' border='0' cellpadding="0" cellspacing="1" id='Formulario'>
<caption>Pedido da Loja Virtual</caption>
<tbody>
	<tr><td colspan='4'>&nbsp;</td></tr>
	<tr>
		<th>Código</th>
		<td><input type="text" name="codigo" id="codigo" size="14" maxlength="14" value="<? echo $codigo ?>" style="width:150px">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm.nome,document.frm.posto,document.frm.codigo,'codigo')"></td>
		<th>Razão Social</th>
		<td><input type="text" name="nome" id="nome" size="25" maxlength="60" value="<? echo $nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm.nome,document.frm.posto,document.frm.codigo,'nome')"></td>
	</tr>
	<tr><td colspan='4'>&nbsp;</td></tr>
</tbody>
<tfoot>
	<tr>
		<td colspan='4'><a href='javascript:document.frm.submit();'><img src="imagens_admin/btn_listar.gif"></a><input type="hidden" name="posto" value="<? echo $posto?>"></td>
	</tr>
</tfoot>
</table>


</form>

<?
if (strlen($codigo) > 0 and strlen($nome) > 0) $listar = 1;

?>


<?

if ($listar == 1){
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto='$codigo' AND fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$posto      = pg_result($res,0,posto);

	$sql = "SELECT  PE.pedido                                  ,
					PE.total                                   ,
					to_char(PE.data,'DD/MM/YYYY') AS data      ,
					LI.nome                       AS linha_nome,
					(SELECT login FROM tbl_admin WHERE tbl_admin.admin = PE.admin)           AS admin_pedido
			FROM      tbl_pedido PE
			LEFT JOIN tbl_linha  LI ON PE.linha = LI.linha
			WHERE     PE.fabrica = $login_fabrica
			AND       PE.posto   = $posto
			AND       PE.pedido_loja_virtual IS TRUE
			AND       PE.finalizado          IS NULL
			AND       PE.exportado           IS NULL; ";
	$res = pg_exec($con,$sql);
	echo "<table width='650' align='center' border='0' cellpadding='0' cellspacing='4' id='Relatorio'>";
	if (pg_numrows($res) > 0){
		echo "<caption>PEDIDOS DA LOJA VIRTUAL EM ABERTO</caption>";
		echo "</tr>";
		echo "<thead>";
		echo "<tr>";
		echo "<td width='25%'>PEDIDO</td>";
		echo "<td width='15%'>LINHA</td>";
		echo "<td width='20%'>DATA DE GERAÇÃO</td>";
		echo "<td width='20%'>RESPONSÁVEL</td>";
		echo "<td width='40%'>TOTAL</td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$pedido      = pg_result($res,$i,pedido);
			$total       = pg_result($res,$i,total);
			$linha_nome  = pg_result($res,$i,linha_nome);
			$data_geracao= pg_result($res,$i,data);
			$admin_pedido= pg_result($res,$i,admin_pedido);
		
			echo "<tr>";
			echo "<td>$pedido</td>";
			echo "<td>$linha_nome</td>";
			echo "<td>$data_geracao</td>";
			echo "<td><a href='$PHP_SELF?pedido=$pedido&posto=$posto&admin=$login_admin'>$admin_pedido</a></td>";
			echo "<td align='right'>$total</td>";
			echo "</tr>";
		}
		
		echo "<tr>";
		echo "<td colspan='5'><hr></td>";
		echo "</tr>";
		echo "</body>";
		echo "<br>";
	}else{
		echo "<caption>Não existe nenhum pedido para a loja virtual em aberto pelo posto.<br>Deseja fazer um pedido para este posto?<caption>";
	}
		echo "<tfoot>";

			echo "<form name='frm_pedido' method='POST' action='$PHP_SELF'>";
			echo "<input type='hidden' name='posto' value='$posto'>";
			echo "<input type='hidden' name='codigo' value='$codigo'>";
			echo "<input type='hidden' name='nome' value='$nome'>";
			echo "<tr>";
			echo "<th colspan='5'>Para criar um novo pedido selecione a linha</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<th>ADMIN:</th>";
			echo "<td>$login_login</td>";
			echo "<TH>Linha</TH>";
			echo "<TD>";
			$sql = "SELECT  *
					FROM    tbl_linha
					JOIN    tbl_posto_linha USING(linha)
					WHERE   tbl_linha.fabrica     = $login_fabrica 
					AND     tbl_posto_linha.posto = $posto
					ORDER BY tbl_linha.nome;";

			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='linha' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));
					echo "<option value='$aux_linha'"; 
					if ($linha == $aux_linha){
						echo " SELECTED "; 
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
			echo "</TD>";
			echo "</tr>";

			echo "<tr>";
			echo "<th colspan='5'><input type='submit' name='btn_pedido' value='Criar Pedido'></th>";
			echo "<TD>";
			echo "</tr>";
		echo "</form>";
		echo "</tfoot>";
		echo "</table>";

}
?>
<p>


<? include "rodape.php"; ?>
<?
/*$sql = "UPDATE tbl_posto_fabrica SET
				credenciamento = 'CREDENCIADO'
		WHERE  posto = 550
		AND    fabrica = 3";
$res = pg_exec($con,$sql);*/
?>