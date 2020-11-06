<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
$ajax = $_GET['ajax'];
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");

//HD 100300 - Pedido de promoção automatica
$abrir = fopen("/www/assist/www/libera_promocao_black.txt", "r");
$ler = fread($abrir, filesize("/www/assist/www/libera_promocao_black.txt"));
fclose($abrir);
$conteudo_p = explode(";;", $ler);
$data_inicio_p = $conteudo_p[0];
$data_fim_p    = $conteudo_p[1];
$comentario_p  = $conteudo_p[2];
$promocao = "f";
if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) { // DATA DO BLOQUEIO
		$promocao = "t";
	}
}
//echo "promocao $promocao";
//HD 100300 pedido de promocao automatico.

$aux_codigo_posto = $_POST['codigo_posto'];
$aux_tipo_posto   = $_POST['tipo_posto'];
$aux_nome_posto   = $_POST['nome_posto'];
$aux_condicao     = $_POST['condicao'];


if(strlen($ajax)>0){
	$cond  = " 1=1 ";
	$codigo_posto = $_GET['codigo_posto'];
	
		$sql = "SELECT posto 
				from tbl_posto_fabrica 
				where fabrica = $login_fabrica 
				and codigo_posto = '$codigo_posto'";
		$res = pg_exec($con,$sql);
		//echo $sql;
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);	
			$cond  = " tbl_black_posto_condicao.posto =  $posto ";
		}
		$sql = "SELECT	tbl_black_posto_condicao.posto    , 
						tbl_black_posto_condicao.condicao , 
						tbl_black_posto_condicao.id_condicao ,
						tbl_posto_fabrica.codigo_posto       ,
						tbl_promocao.promocao
				FROM tbl_black_posto_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
				and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_condicao on tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
				and tbl_condicao.fabrica = $login_fabrica 
				where $cond ";
		if($promocao == 't'){
			$sql .= "UNION SELECT tbl_posto_fabrica.posto, tbl_condicao.descricao as condicao, tbl_condicao.condicao as id_condicao, tbl_posto_fabrica.codigo_posto, tbl_condicao.promocao
				FROM tbl_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_condicao.fabrica = $login_fabrica
				AND tbl_condicao.promocao is true ";
		}
		$sql .= "order by posto,condicao";
		$res = pg_exec($con,$sql);
		//echo "<BR>$sql";
		if(pg_numrows($res)>0){
			echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
			echo "<TR>\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Posto</TD>\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Condição</TD>\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Ação</TD>\n";
			echo "</TR>\n";
			for($x=0;pg_numrows($res)>$x;$x++){
				$posto         = pg_result($res,$x,posto);
				$condicao      = pg_result($res,$x,condicao);
				$id_condicao   = pg_result($res,$x,id_condicao);
				$codigo_posto  = pg_result($res,$x,codigo_posto);
				$tbl_promocao  = pg_result($res,$x,promocao);
				if ($x % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='center' nowrap>$codigo_posto</TD>\n";
				echo "<TD align='left' nowrap>$condicao</TD>\n";
				if($promocao == 't' and $tbl_promocao == 't'){
					echo "<TD align='center' nowrap title='HD 100300 - Quando liberar a promoção automaticamente todas as condições de promoção serão mostradas automaticamente na tela de pedido do posto!' >Automático</td>";
				}else{
					echo "<TD align='center' nowrap><a href=\"javascript:if (confirm('Deseja excluir esta Condição?')) window.location='?apagar=$id_condicao&posto=$posto'\"><img src='../erp/imagens/cancel.png' width='12px' alt='Excluir Condição' /></TD>\n";
				}
			}
			echo "</table>";
		}
	
	
exit;
}
$apagar = $_GET['apagar'];
if(strlen($apagar)>0){
	$posto  = $_GET['posto'];
	$sql = "DELETE FROM tbl_black_posto_condicao where posto = $posto and id_condicao = $apagar";
	$res = pg_exec($con,$sql);

	$sql = "DELETE FROM tbl_posto_condicao where posto = $posto and condicao = $apagar and tabela = 31";
	$res = pg_exec($con,$sql);
//echo $sql;

}
$btn_acao = $_POST['btn_acao'];
if($btn_acao == 'Gravar'){
	$codigo_posto  = $_POST['codigo_posto'];
	$posto_nome    = $_POST['posto_nome'];
	$condicao      = $_POST['condicao'];
	$tipo_posto    = $_POST['tipo_posto'];
	if(strlen($condicao)==0){
		$msg_erro .= "Escolha a condição";
	}
	
	if(strlen($codigo_posto)>0){
		if(strlen($msg_erro)==0){
			$sql = "SELECT posto 
					from tbl_posto_fabrica 
					where fabrica = $login_fabrica
					and codigo_posto = '$codigo_posto'";
			$res = pg_exec($con,$sql);
			//echo "<BR>$sql";
			if(pg_numrows($res)>0){
				$posto = pg_result($res,0,0);
			}else{
				$msg_erro .= "Posto não encontrado";
			}
		}
		if(strlen($msg_erro)==0){
			$sql = "SELECT condicao, descricao from tbl_condicao where condicao = $condicao";
			$res = pg_exec($con,$sql);
			//echo "<BR>$sql";
			if(pg_numrows($res)>0){
				$condicao           = pg_result($res,0,condicao);
				$condicao_descricao = pg_result($res,0,descricao);
			}else{
				$msg_erro .= "Condição não encontrada";
			}
		}

		if(strlen($msg_erro)==0){
			$sql = "SELECT	posto    , 
							data     ,  
							condicao , 
							id_condicao 
					FROM tbl_black_posto_condicao
					where posto=$posto
					and id_condicao = $condicao";
			$res = pg_exec($con,$sql);
			//echo "<BR>$sql";
			if(pg_numrows($res)>0){
				$msg_erro .= "Condição já cadastrada para este posto";
			}else{
				if(strlen($msg_erro)==0){
					$sql= "INSERT INTO tbl_black_posto_condicao(
									posto, 
									data, 
									condicao, 
									id_condicao
							)values(
									$posto, 
									current_timestamp, 
									'$condicao_descricao', 
									$condicao);";
					$res = pg_exec ($con,$sql);
					if (strlen (pg_errormessage($con)) > 0 ) {
						$msg_erro .= pg_errormessage($con);
						$msg_erro .= substr($msg_erro,6);
					}

					$sql = "SELECT condicao FROM tbl_posto_condicao WHERE posto = $posto AND condicao = $condicao; ";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res) == 0){
						$sql= "INSERT INTO tbl_posto_condicao(
										posto, 
										condicao, 
										tabela
								)values(
										$posto, 
										$condicao, 
										'31');";
						$res = pg_exec ($con,$sql);
						if (strlen (pg_errormessage($con)) > 0 ) {
							$msg_erro .= pg_errormessage($con);
							$msg_erro .= substr($msg_erro,6);
						}
					}
				}
			}
		}
		if(strlen($msg_erro)==0){
			$msg_erro = "Cadastrado com Sucesso!";
		}
	}
	if(strlen($codigo_posto)==0 and strlen($tipo_posto)>0){
		$sql = "SELECT condicao, descricao from tbl_condicao where condicao = $condicao";
		$res = pg_exec($con,$sql);
		//echo "<BR>$sql";
		if(pg_numrows($res)>0){
			$condicao           = pg_result($res,0,condicao);
			$condicao_descricao = pg_result($res,0,descricao);
		}else{
			$msg_erro .= "Condição não encontrada";
		}
		if(strlen($msg_erro)==0){
			$sql = "DELETE FROM tbl_black_posto_condicao 
					where posto in(SELECT posto from tbl_posto_fabrica where fabrica = $login_fabrica and tipo_posto = $tipo_posto)
					and id_condicao = $condicao;";
	//		echo "$sql<BR>";
			$res = pg_exec($con,$sql);
			$sql= "INSERT INTO tbl_black_posto_condicao(
										posto, 
										data, 
										condicao, 
										id_condicao
								)
									SELECT posto, 
									current_timestamp,
									'$condicao_descricao',
									$condicao
									from tbl_posto_fabrica 
									where fabrica = $login_fabrica 
									and tipo_posto = $tipo_posto";
			$res = pg_exec ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro .= pg_errormessage($con);
				$msg_erro .= substr($msg_erro,6);
			}
		//	$res = pg_exec ($con,$sql);
		}
		if(strlen($msg_erro)==0){
			$msg_erro = "Cadastrado com Sucesso!";
		}
	}
}



include "cabecalho.php";
?>

<!--=============== <FUNÇÕES> ================================!-->

<? include "javascript_pesquisas.php" ?>
<script language="JavaScript">
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
	
function consultaPosto(){
	if (document.getElementById('consulta_dados')){
		var style2 = document.getElementById('consulta_dados'); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaConsulta()
		}
	}
}
var http3 = new Array();
function retornaConsulta(){
	var codigo_posto = document.getElementById('codigo_posto').value;
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "<?echo $PHP_SELF;?>?ajax=true&codigo_posto="+ codigo_posto;
	http3[curDateTime].open('get',url);
	
	var campo = document.getElementById('consulta_dados');

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}

</script>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}
</style>

<table width="600" border="0" cellspacing="1" cellpadding="5" align='center' style='font-size:10px' bgcolor='#8C9DB0'>
	<tr>
		<td bgcolor='#E6E9EC'>Para efetuar o cadastro de condições de pagamento para o Posto Autorizado basta selecionar<BR> o posto ou o tipo do posto, selecionar a condição de pagamento. <BR>* Ao selecionar o tipo do posto, todos os postos que estiverem cadastrados<BR> nesta linha irão receber essa condição de pagamento</td>
	</tr>
</table>
<BR><BR>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	</tr>
	<? if (strlen($msg_erro)>0){ ?>
	<tr>
	<td><font color='red'><? echo $msg_erro ?></font></td>
	</tr>
	<?}?>
	<tr>	
		<td valign="top" align="left">
		<!-- ------------- Formulário ----------------- -->
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_pesquisa" method="post" action="<? echo $PHP_SELF ?>">

		<table width="100%" border="0" cellspacing="0" cellpadding="5">
		<tr>
		<TD class="menu_top" colspan='2'>Cadastro de Condição de Pagamento X Posto</TD>
		</tr>
		<TR>
			<TD width="180" class="table_line">Código do Posto</TD>
			<TD width="180" class="table_line">Nome do Posto</TD>
		</TR>
		<TR>
		<TD class="table_line" align="center" nowrap>
			<INPUT TYPE="text" NAME="codigo_posto" id="codigo_posto" SIZE="8" value='<? echo $codigo_posto; ?>'>
			<IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" >
		</TD>
		<TD class="table_line" align="center"  nowrap>
			<INPUT TYPE="text" NAME="nome_posto" id="nome_posto" size="15" value='<? echo $nome_posto; ?>'> 
			<IMG src="imagens_admin/btn_lupa.gif" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.nome_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')">
<?//			<IMG src="../imagens_admin/btn_list.gif" style="cursor:pointer" align='absmiddle' alt="Consultar Condições Cadastradas" onclick='javascript:consultaPosto();'> ?>
		</TD>
	</TR>
	<TR>
			<TD class="table_line" colspan='2' align="center" >Tipo Posto</TD>
	</TR>
	<TR>
			<TD class="table_line" colspan='2' align="center" >
			<SELECT NAME="tipo_posto">
			<option></option>
			<?
			$sql = "SELECT tipo_posto, descricao 
					FROM tbl_tipo_posto
					WHERE fabrica = $login_fabrica 
					order by descricao;";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($i=0;pg_numrows($res)>$i;$i++){
					$tipo_posto = pg_result($res,$i,tipo_posto);
					$descricao  = pg_result($res,$i,descricao);
					echo "<option value='$tipo_posto'"; if($aux_tipo_posto == $tipo_posto){ echo "selected";} echo ">$descricao</option>";
				}
			}
			?>
			</select>
			</TD>
		</TR>
	<tr>
		<td class="table_line" colspan='2'>

		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Condição de Pagamento</font>
		</td>
	</tr>
	<tr>
		<td class="table_line" colspan='2' align='center'>
		<SELECT NAME="condicao">
		<?
		echo "<option ></option>";
			$sql = "SELECT  tbl_condicao.condicao       ,
					tbl_condicao.codigo_condicao,
					tbl_condicao.descricao
			FROM    tbl_condicao
			WHERE   tbl_condicao.fabrica = $login_fabrica
			ORDER BY lpad(codigo_condicao::char(10),10,'0');";

			$res = @pg_exec ($con,$sql);
			
			for ($i=0; $i < pg_numrows($res); $i++) {
				$xcondicao			= trim(pg_result($res,$i,condicao));
				$codigo_condicao	= trim(pg_result($res,$i,codigo_condicao));
				$descricao			= trim(pg_result($res,$i,descricao));
				echo "<option value='$xcondicao'"; if($aux_condicao == $xcondicao){ echo "selected";} echo ">$codigo_condicao - $descricao</option>\n";
			}

		?>
		</SELECT>
		</td>
		</tr>

		<tr>
			<td class="table_line" colspan='2' align='center'>
				<INPUT TYPE="submit" name="btn_acao" size="50" value="Gravar">
				<INPUT TYPE="submit" name="btn_acao" size="50" value="Pesquisar">
			</td>
		</tr>
		</table>
	  </form>
	</td>
</tr>
</table>
<BR><BR>

<div id='consulta_dados' style='position:relative; display:block;width:700px;margin:auto;text-align:center'>
</div>

<?
if($btn_acao == 'Pesquisar'){

	$cond1  = " 1=1 ";
	$cond2  = "AND 1=1 ";
	$cond3  = "AND 1=1 ";
	$aux_codigo_posto = $_POST['codigo_posto'];
	$aux_tipo_posto   = $_POST['tipo_posto'];
	$aux_condicao     = $_POST['condicao'];

	$sql = "SELECT posto 
			from tbl_posto_fabrica 
			where fabrica = $login_fabrica 
			and codigo_posto = '$aux_codigo_posto'";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$posto = pg_result($res,0,posto);	
		$cond1  = " tbl_black_posto_condicao.posto =  $posto ";
	}

	if(strlen($aux_tipo_posto) > 0){
		$cond2 = " AND tbl_posto_fabrica.tipo_posto = $aux_tipo_posto ";
	}

	if(strlen($aux_condicao) > 0){
		$cond3 = " AND tbl_black_posto_condicao.id_condicao = $aux_condicao ";
	}

	$sql = "SELECT tbl_black_posto_condicao.posto        ,
					tbl_condicao.descricao AS condicao   ,
					tbl_black_posto_condicao.id_condicao ,
					tbl_posto_fabrica.codigo_posto       ,
					tbl_condicao.promocao
			FROM tbl_black_posto_condicao
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
			JOIN tbl_condicao ON tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
			and tbl_posto_fabrica.fabrica = $login_fabrica
			where $cond1
			$cond2
			$cond3 ";
	if($promocao == 't'){
		$sql .= "UNION SELECT tbl_posto_fabrica.posto, tbl_condicao.descricao as condicao, tbl_condicao.condicao as id_condicao, tbl_posto_fabrica.codigo_posto, tbl_condicao.promocao
			FROM tbl_condicao
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_condicao.fabrica = $login_fabrica
			AND tbl_condicao.promocao is true ";
	}
	$sql .= " order by posto,condicao";
	echo $sql;
	$res = pg_exec($con,$sql);
	//echo nl2br($sql);
	if(pg_numrows($res)>0){
		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
		echo "<TR>\n";
		echo "<td class='menu_top' background='imagens_admin/azul.gif'>Posto</TD>\n";
		echo "<td class='menu_top' background='imagens_admin/azul.gif'>Condição</TD>\n";
		echo "<td class='menu_top' background='imagens_admin/azul.gif'>Ação</TD>\n";
		echo "</TR>\n";
		for($x=0;pg_numrows($res)>$x;$x++){
			$posto         = pg_result($res,$x,posto);
			$condicao      = pg_result($res,$x,condicao);
			$id_condicao   = pg_result($res,$x,id_condicao);
			$codigo_posto  = pg_result($res,$x,codigo_posto);
			$tbl_promocao  = pg_result($res,$x,promocao);
			if ($x % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
			echo "<TR bgcolor='$cor'>\n";
			echo "<TD align='center' nowrap>$codigo_posto</TD>\n";
			echo "<TD align='left' nowrap>$condicao</TD>\n";
			if($promocao == 't' and $tbl_promocao == 't'){
				echo "<TD align='center' nowrap title='HD 100300 - Quando liberar a promoção automaticamente todas as condições de promoção serão mostradas automaticamente na tela de pedido do posto!' >Automático</td>";
			}else{
				echo "<TD align='center' nowrap><a href=\"javascript:if (confirm('Deseja excluir esta Condição?')) window.location='?apagar=$id_condicao&posto=$posto'\"><img src='../erp/imagens/cancel.png' width='12px' alt='Excluir Condição' /></TD>\n";
			}
		}
		echo "</table>";
	}

}
?>

<? include "rodape.php";?>
