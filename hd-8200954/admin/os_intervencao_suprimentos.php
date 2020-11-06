<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

if ( !in_array($login_fabrica, array(1,10,11,172)) ){
	header("Location: menu_callcenter.php");
	exit();
}

$msg = "";
$msg_erro = "";

if ($login_fabrica==11) {
	$id_servico_realizado = "61";
	$id_servico_realizado_ajuste = "498";
}
if ($login_fabrica==3) {
	$id_servico_realizado = "20";
	$id_servico_realizado_ajuste = "96";
}
if ($login_fabrica==1) {
	$id_servico_realizado = "62,90";
	$id_servico_realizado_ajuste = "64";
}

if(strlen($id_servico_realizado)==0){ # padrao LENOXX
	$id_servico_realizado = "61";
	$id_servico_realizado_ajuste = "498";
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

function converte_data($date){
	//$date = explode("-", ereg_replace('/', '-', $date));
	$date = explode("-", preg_replace('/\//', '-', $date));
	if (sizeof($date)==3)	return ''.$date[2].'/'.$date[1].'/'.$date[0];
	else					return false;
}

if (isset($_GET['os']) && strlen($_GET['os'])>0)				$os=$_GET['os'];
if (isset($_GET['msg']) && strlen($_GET['msg'])>0)				$msg=$_GET['msg'];
if (isset($_GET['msg_erro']) && strlen($_GET['msg_erro'])>0)	$msg_erro=$_GET['msg_erro'];


if (strlen(trim($_POST['atualizar'])) > 0)	$atualizar = trim($_POST['atualizar']);
else                                  		$atualizar = trim($_GET["atualizar"]);

if (strlen(trim($_POST['btnacao'])) > 0)	$btnacao = trim($_POST['btnacao']);
else                                  		$btnacao = trim($_GET["btnacao"]);


if ($btnacao == 'filtrar'  ) {

	if (strlen($_POST['posto_codigo']) > 0) $posto_codigo	= $_POST['posto_codigo'];
	else									$posto_codigo	= $_GET["posto_codigo"];

	if (strlen($_POST['posto_nome']) > 0) 	$posto_nome		= $_POST['posto_nome'];
	else									$posto_nome		= strtoupper($_GET["posto_nome"]);

	if (strlen($_POST['produto']) > 0) 		$produto		= $_POST['produto'];
	else									$produto		= $_GET["produto"];

	if (strlen($_POST['referencia']) > 0) 	$referencia		= $_POST['referencia'];
	else									$referencia		= $_GET["referencia"];

	if (strlen($_POST['descricao']) > 0)	$descricao		= $_POST['descricao'];
	else									$descricao		= $_GET["descricao"];

	$os_sem_peca = trim($_POST['os_sem_peca']);
	//echo "teste=".$posto_codigo;
	if(strlen($posto_codigo) > 0){
		$sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '$posto_codigo' AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) == 0){
			$msg_erro = "Posto não Encontrado";
		}
	}

	if ((strlen($posto_codigo)>0 OR strlen($posto_nome)>0 OR strlen($produto)>0 OR strlen($referencia)>0) and strlen($msg_erro == 0)){

		if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){
			if (strlen($posto_codigo)>0){
				$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
				$join_sql_adicional = "JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica=$login_fabrica";
			}
			else{
				$sql_adicional = " AND tbl_posto.nome like '%$posto_nome%' ";
				$join_sql_adicional = "JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto";
			}
		}

		$filtro = "<b style='font-size:14px;padding-right:15px'>Pesquisando por:";

		if (strlen($sql_adicional)>0) {
			$filtro .= "<br>Posto: <i>$posto_codigo - $posto_nome</i>";
		}
		if (strlen($sql_adicional_2)>0){
			$filtro .= "<br>Produto: <i>$referencia - $descricao </i>";
		}
		$filtro .= "</b> (<a href='$PHP_SELF' style='font-size:12px;'>Mostrar Todos</a>)";
	}
}


if (strlen($atualizar) > 0) {

	$os=trim($_POST['os']);

	if (strlen($_GET['btnacao']) > 0) {
		$btnacao = $_GET['btnacao'];
	}else{
		$btnacao = $_POST["btnacao"];
	}

	if (strlen($_GET['posto_codigo']) > 0) {
		$posto_codigo = $_GET['posto_codigo'];
	}else{
		$posto_codigo = $_POST["posto_codigo"];
	}

	if (strlen($_GET['posto_nome']) > 0){
		$posto_nome = $_GET['posto_nome'];
	}else{
		$posto_nome = strtoupper($_POST["posto_nome"]);
	}

	$justificativa = trim($_POST["justificativa"]);
	if (strlen($justificativa)>0){
		$justificativa = "Justificativa: $justificativa";
	}


	if (strlen($os)>0 AND strlen($msg_erro)==0){

		$sql = "SELECT posto,sua_os,finalizada FROM tbl_os where os=$os";
		$res = pg_exec($con,$sql);

		$posto         = trim(pg_result($res,0,posto));
		$sua_os        = trim(pg_result($res,0,sua_os));
		$o_finalizadas = trim(pg_result($res,0,finalizada));

		if(strlen($os_sem_peca)>0 AND trim($os_sem_peca)==$os){
			$res = @pg_exec($con,"BEGIN TRANSACTION");
			$sql = "DELETE FROM tbl_os_status
					WHERE os_status= (SELECT os_status FROM tbl_os_status WHERE status_os IN (87,88) AND os=$os ORDER BY data DESC LIMIT 1)";

			$sql = "INSERT INTO tbl_os_status
					(os,status_os,data,observacao,admin)
					VALUES ($os,88,current_timestamp,'OS liberada',$login_admin)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (strlen($msg_erro)>0){
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
			else {
				$res = @pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "A OS $sua_os foi liberada para o posto";
			}
			header("Location: $PHP_SELF?msg=$msg&msg_erro=$msg_erro");
			exit(); # caso onde o ADMIN altera o cadastro da peça setando ela em nao intervencao
		}

		$sql_peca = "SELECT     tbl_os_item.os_item AS item,
						tbl_peca.troca_obrigatoria AS troca_obrigatoria,
						tbl_peca.retorna_conserto AS retorna_conserto,
						tbl_peca.bloqueada_garantia As bloqueada_garantia,
						tbl_peca.peca_critica As peca_critica,
						tbl_peca.referencia AS referencia,
						tbl_peca.descricao AS descricao,
						tbl_peca.peca AS peca
					FROM tbl_os_produto
					JOIN tbl_os_item USING(os_produto)
					JOIN tbl_peca USING(peca)
					WHERE tbl_os_produto.os           = $os
					AND tbl_os_item.servico_realizado IN ($id_servico_realizado)
					AND tbl_os_item.pedido IS NULL ";
		if ($login_fabrica<>1){
			$sql_peca .= " AND tbl_peca.peca_critica IS TRUE";
		}
		$res_peca  = pg_exec($con,$sql_peca);
		$resultado = pg_numrows($res_peca);
		if ($resultado>0){

			$res = pg_exec($con,"BEGIN TRANSACTION");

			if (strlen($o_finalizadas)>0){
				$sql = "UPDATE tbl_os SET finalizada=null WHERE os=$os";
				$res = pg_exec($con,$sql);
			}

			$qtde_cancelado=0;
			for($j=0;$j<$resultado;$j++){
				$item             = trim(pg_result($res_peca,$j,item));
				$peca_referencia  = trim(pg_result($res_peca,$j,referencia));
				$peca_descricao   = trim(pg_result($res_peca,$j,descricao));
				$bloqueada_garant = trim(pg_result($res_peca,$j,bloqueada_garantia));
				$peca_critica     = trim(pg_result($res_peca,$j,peca_critica));
				$item_alterar     = trim($_POST["alterar_$item"]);

				if (strlen($item_alterar)>0){
					if ($item_alterar=='cancelar'){

						$sql =  "UPDATE tbl_os_item
								SET servico_realizado     = $id_servico_realizado_ajuste,
									liberacao_pedido = FALSE,
									admin                 = $login_admin,
									liberacao_pedido_analisado      = FALSE,
									data_liberacao_pedido = null
								WHERE os_item = $item";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (strlen($msg_erro)==0){
							$qtde_cancelado++;
							$sql = "INSERT INTO tbl_comunicado (
										descricao              ,
										mensagem               ,
										tipo                   ,
										fabrica                ,
										obrigatorio_os_produto ,
										obrigatorio_site       ,
										posto                  ,
										ativo
									) VALUES (
										'Pedido de Peças CANCELADO'           ,
										'Seu pedido da peça $peca_referencia - $peca_descricao referente a OS $sua_os foi <b>cancelado</b> pela fábrica. $justificativa',
										'Pedido de Peças',
										$login_fabrica,
										'f' ,
										't',
										$posto,
										't'
									);";
							if ($login_fabrica==1){
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
					}
					if ($item_alterar=='autorizar'){
						$sql =  "UPDATE tbl_os_item
								SET admin=$login_admin
								WHERE os_item = $item";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
					if ($item_alterar=='excluir'){
						$item_excluido = "sim";
						$sql =  "DELETE FROM tbl_os_item WHERE os_item = $item";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "INSERT INTO tbl_comunicado (
									descricao              ,
									mensagem               ,
									tipo                   ,
									fabrica                ,
									obrigatorio_os_produto ,
									obrigatorio_site       ,
									posto                  ,
									ativo
								) VALUES (
									'Pedido de Peças CANCELADO'           ,
									'Seu pedido da peça $peca_referencia - $peca_descricao referente a OS $sua_os foi <b>excluída</b> da O.S. pela fábrica. $justificativa',
									'Pedido de Peças',
									$login_fabrica,
									'f' ,
									't',
									$posto,
									't'
								);";
						if ($login_fabrica==1){
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}
			if (strlen($msg_erro)==0){
				if ($qtde_cancelado>0){
					if ($qtde_cancelado==$resultado OR $item_excluido=="sim"){
						$msg_posto="Pedido de Peças Cancelado Pela Fábrica.";
					}else{
						$msg_posto="Pedido de Peças Autorizado Parcialmente Pela Fábrica.";
					}
				}else{
					$msg_posto="Pedido de Peças Autorizado Pela Fábrica.";
				}
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao,admin)
						VALUES ($os,88,current_timestamp,'$msg_posto $justificativa',$login_admin)";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro)==0 AND strlen($o_finalizadas)>0){
					$sql = "UPDATE tbl_os SET finalizada='$o_finalizadas' WHERE os=$os";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro)>0){
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
			else {
				$res = @pg_exec ($con,"COMMIT TRANSACTION");
				$msg = "$msg_posto A OS foi liberada para o posto";
			}
			header("Location: $PHP_SELF?msg=$msg_posto&msg_erro=$msg_erro&btnacao=$btnacao&posto_codigo=$posto_codigo&posto_nome=$posto_nome");
			exit();
		}
	}
}

$trocar = trim($_GET['trocar']);

# Desabiliado - troca da B&D é pelo processo de troca
if ($login_fabrica==1 AND strlen($trocar) > 0 && strlen($os) > 0 AND 1==2) {
	$sua_os=trim($_GET['trocar']);
	header("Location: os_cadastro.php?os=$os");
	exit();
}

if ($login_fabrica==1 AND $trocar == "1"){
	$os     = trim($_GET['os']);
	$sua_os = trim($_GET['sua_os']);
	if (strlen($os)>0){

		$res = pg_exec($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_os SET
					tipo_atendimento =
					troca
				WHERE os=$os
				AND fabrica = $login_fabrica";
		//$res = pg_exec($con,$sql);
		//$msg_erro = pg_errormessage($con);

		#Cancela as peças trocando o serviço realizado
		$sql =  "UPDATE tbl_os_item
				SET servico_realizado     = $id_servico_realizado_ajuste,
					admin                 = $login_admin,
					liberacao_pedido_analisado = FALSE,
					liberacao_pedido = FALSE,
					data_liberacao_pedido = null
				WHERE pedido     IS NULL
				AND   os_produto IN ( SELECT os_produto FROM tbl_os_produto WHERE os=$os )";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		/* Comentado por Fabio - Quando a OS for para a Troca, manter ela em Intervencao, para o posto nao pedir peça novamente - HD 5876
		$sql = "INSERT INTO tbl_os_status
				(os,status_os,data,observacao,admin)
				VALUES ($os,88,current_timestamp,'OS liberada',$login_admin)";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		*/

		$sql = "SELECT tbl_produto.produto,
						tbl_produto.valor_troca,
						tbl_os.tipo_atendimento
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE tbl_os.os      = $os
				AND   tbl_os.fabrica = $login_fabrica
				";
		$res_os = pg_exec($con,$sql);
		if (pg_numrows($res_os)>0){
			$produto          = trim(pg_result($res_os,0,produto));
			$valor_troca      = trim(pg_result($res_os,0,valor_troca));
			$tipo_atendimento = trim(pg_result($res_os,0,tipo_atendimento));
		}else{
			$msg_erro .= "Não encontrado!";
		}

		if (strlen($valor_troca)==0){
			$valor_troca = " NULL ";
		}

		if (strlen($tipo_atendimento)==0){
			$tipo_atendimento = "17";
		}

		if ($tipo_atendimento == "17"){
			$valor_troca = " 0";
		}

		if ($login_fabrica==1){
			$sql = "UPDATE tbl_os SET
						tipo_atendimento = $tipo_atendimento
					WHERE os      = $os
					AND   fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		$sql = "SELECT tbl_os_troca.os_troca
				FROM tbl_os_troca
				WHERE tbl_os_troca.os = $os";
		$res_os_troca = pg_exec($con,$sql);
		if (pg_numrows($res_os_troca)==0){
			$sql = "INSERT INTO tbl_os_troca
						(situacao_atendimento,os,produto,observacao,total_troca, admin)
						VALUES ($tipo_atendimento,$os,$produto,'OS com origem de Intervenção',$valor_troca, $login_admin)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}else{
			$sql = "UPDATE tbl_os_troca SET
						admin = NULL
					WHERE os = $os";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro)>0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}else{
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			//$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg = "A OS foi para a análise de troca.";
		}
	}
}


$layout_menu = "callcenter";
$title = "OS DE INTERVENÇÃO SUPRIMENTOS";
include "cabecalho.php";
?>

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



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}


.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border:1px solid #596d9b;
}

</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language="javascript">
function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
 	}
}

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_consulta.produto;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function justificar_cancelameto(){
	var justificativa= prompt('Informe a justificativa do cancelamento.', '');

	if ( (justificativa=='') || (justificativa==null) || justificativa.length==0){
		alert('Justificativa não informada!\n\nPedido de peça não foi cancelado.');
		return false;
	}
	else{
		return justificativa;
	}
}

function verificar(botao,formulario,sua_os){
	//eval("var form = document."+formulario+".");
	eval("var form = document."+formulario);
	var cancelar=0;
	var autorizar=0;

	for( var i = 0 ; i < form.length; i++ ){
		if (form.elements[ i ].type=='select-one'){
			if (form.elements[ i ].value=='cancelar'){
				cancelar++;
			}
			if (form.elements[ i ].value=='autorizar'){
				autorizar++;
			}

			if (form.elements[ i ].value==''){
				alert('Selecione uma opção para a peça');
				return false;
			}
		}
	}

	<?
	if ($login_fabrica==1){
	?>
	if (cancelar>0){
		var just = prompt('Informe a justificativa do cancelamento','');
		if ( (just=='') || (just==' ') || (just==null) || just.length==0){
			alert('A justificativa é obrigatória');
			return false;
		}
		form.justificativa.value=just;
	}
	<? } ?>

	if (confirm('Deseja continuar?\n\nOS: '+sua_os)){
		botao.alt='Aguarde';
		form.submit();
	}
}

function verificar_antigo(botao,formulario,sua_os){
	//eval("var form = document."+formulario+".");
	eval("var form = document."+formulario);
	var cancelar=0;
	for( var i = 0 ; i < form.length; i++ ){
		if (form.elements[ i ].type=='select-one'){
			if (form.elements[ i ].value=='cancelar'){
				cancelar++;
			}
			if (form.elements[ i ].value==''){
				alert('Selecione uma opção para a peça');
				return false;
			}
		}
	}
	if (cancelar>0){
		var just = prompt('Informe a justificativa do cancelamento','');
		if ( (just=='') || (just==' ') || (just==null) || just.length==0){
			alert('A justificativa é obrigatória');
			return false;
		}
		form.justificativa.value=just;
	}
	if (confirm('Deseja continuar?\n\nOS: '+sua_os))
		botao.alt='Aguarde';
		form.submit();
}


</script>
<br>
<?
	#HD 14331
	echo "<div class='texto_avulso' style='width:700px;'>";
	echo "<p style='text-align:left;padding:0px;'><b>ATENÇÃO: </b>As OSs em intervenção serão desconsideradas da INTERVENÇÃO automaticamente pelo sistema se não forem analisadas no prazo de 5 dias! O objetivo desta rotina é que o fabricante ajude o posto autorizado, e se isto não acontecer a OS sai da intervenção.</p>";
	echo "<p style='text-align:left'>TELECONTROL</p>";
	echo "</div>";
	echo "<br>";

?>

<?
if(strlen($msg_erro)>0){
	echo "<table width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td  class='msg_erro'> $msg_erro</td>";
	echo "</tr>";
	echo "</table>";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}


echo "<FORM METHOD='POST' NAME='frm_consulta' ACTION=\"$PHP_SELF\">";
?>
<input type='hidden' name='btnacao'>
<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center' class='formulario'>
	<tr >
		<td class="titulo_tabela" height='20'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td>
			<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='2' class='formulario'>
					<tr><td colspan='2'>&nbsp;</td></tr>
					<tr align='left'>
						<td align='right' style='padding:0 0 0 100px;'>Posto&nbsp;</td>
						<td><input type="text" name="posto_codigo" size="16" value="<? echo $posto_codigo ?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')"></td>
					</tr>
					<tr align='left'>
						<td align='right'>Nome do Posto&nbsp;</td>
						<td>
							<input type="text" name="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
						</td>
					</tr>
					<tr><td colspan='2' bgcolor="#D9E2EF" align='center' style='padding:10px 0 10px 0;'><input type='button' value='Filtrar' onclick="javascript: document.frm_consulta.btnacao.value='filtrar' ; document.frm_consulta.submit() " ALT="Consultar OS's" border='0' style="cursor:pointer;"><br></td></tr>
			</TABLE>
		</td>
	</tr>
	<tr><td>
		</td>
	</tr>

</table>
</form>
<br />
<?

if ($btnacao=='filtrar' and strlen($msg_erro)==0){

	$sql = "SELECT interv.os
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (87,88) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (87,88)) ultima
			) interv
			WHERE interv.ultimo_status = 87";

	$res_status = pg_exec($con,$sql);
	$os_array = array();
	$total=pg_numrows($res_status);
	for ($t = 0 ; $t < $total ; $t++) {
		array_push($os_array,pg_result($res_status,$t,os));
	}

	$os_array = array_unique($os_array);

	if (count($os_array)>0){
		$os_array = "AND OS IN (".implode(',',$os_array).")";
	}else{
		$os_array = "";
	}

	$sql =  "
			SELECT tbl_os.os                                                        ,
				tbl_os.sua_os                                                     ,
				LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
				tbl_os.data_abertura                         AS abertura2         ,
				tbl_os.data_abertura   AS abertura_os       ,
				tbl_os.serie                                                      ,
				tbl_os.consumidor_nome                                            ,
				tbl_os.obs                                            ,
				tbl_os.admin                                                      ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')  as data_nf,
				tbl_posto_fabrica.codigo_posto                                    ,
				tbl_posto.nome                              AS posto_nome         ,
				tbl_posto.fone                              AS posto_fone         ,
				tbl_produto.referencia                      AS produto_referencia ,
				tbl_produto.descricao                       AS produto_descricao  ,
				tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (87,88) ORDER BY data DESC LIMIT 1) AS status_os,
				(SELECT descricao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (87,88) ORDER BY data DESC LIMIT 1) AS status_descricao,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (87,88) ORDER BY data DESC LIMIT 1) AS status_observacao
			FROM tbl_os
			JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_os.excluida IS NOT TRUE
			$os_array
			$sql_adicional
			$sql_adicional_2
			ORDER BY abertura_os ASC";
//echo nl2br($sql);

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";
//		exit;
		// definicoes de variaveis
		$max_links = 11;	// máximo de links à serem exibidos
		$max_res   = 30;	// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		if (strlen($os_array)>0){
			$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
		}
		// ##### PAGINACAO ##### //

		//echo "<br>";
		echo "<center><table border='0' cellpadding='2' cellspacing='1' class='tabela' width='98%'>";
		echo "<tr class='titulo_coluna' height='25' >";
		echo "<td width='70'>OS</td>";
		echo "<td>Abertura</td>";
		echo "<td>Pedido</td>";
		echo "<td>Data NF</td>";
		echo "<td>Posto</td>";
		echo "<td width='75'>Fone Posto</td>";
//		echo "<td>CONSUMIDOR</td>";
		echo "<td width='75'>Produto</td>";
		if ($login_fabrica==1){
			echo "<td>Ações</td>";
		}
		echo "</tr>";

		if (strlen($os_array)>0){
			$total=pg_numrows($res);
		}else{
			$total = 0;
		}
		$achou=0;
		$cores=0;

		for ($i = 0 ; $i < $total ; $i++) {
			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$abertura2          = trim(pg_result($res,$i,abertura2));
			$obs                = trim(pg_result($res,$i,obs));
			$serie              = trim(pg_result($res,$i,serie));
			$data_nf            = trim(pg_result($res,$i,data_nf));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome_bd      = trim(pg_result($res,$i,posto_nome));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_troca_obrigatoria   = trim(pg_result($res,$i,troca_obrigatoria));
			$posto_fone         = substr(trim(pg_result($res,$i,posto_fone)),0,17);
			$status_os          = trim(pg_result($res,$i,status_os));
			$status_descricao   = trim(pg_result($res,$i,status_descricao));
			$status_observacao  = trim(pg_result($res,$i,status_observacao));

			if ($login_fabrica==1){
				$sua_os = $codigo_posto.$sua_os;
			}

			$consumidor_nome	= substr($consumidor_nome,0,15);
			$produto			= substr($produto_referencia . " - " . $produto_descricao,0,40);

			$sql_status  = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data,
										data AS data_pedido_bd
							FROM tbl_os_status
							WHERE tbl_os_status.os = $os
							AND status_os IN (87,88)
							ORDER BY data DESC
							LIMIT 1";
			$res_status = pg_exec($con,$sql_status);
			if(pg_num_rows($res_status)) {
				$data_pedido    = trim(pg_result($res_status,0,0));
				$data_pedido_bd = trim(pg_result($res_status,0,1));
			}
			$data_pedido_bd_aux = substr($posto_nome_bd,0,20);

			$sql = "SELECT tbl_os_troca.os_troca
					FROM tbl_os_troca
					WHERE tbl_os_troca.os = $os";
			$res_os_troca = pg_exec($con,$sql);
			if (pg_numrows($res_os_troca)>0){
				$os_troca = 1;
			}else{
				$os_troca = 0;
			}

			if ($cores++ % 2 == 0)	$cor   = "#F1F4FA";
			else 					$cor   = "#FFF5F0";

			echo "<FORM METHOD='POST' NAME='frm_atualizar_$os' ACTION=\"$PHP_SELF?btnacao=$btnacao&posto_codigo=$posto_codigo&posto_nome=$posto_nome\"  style='display:none'>";
			echo "<input type='hidden' name='os' value='$os'>";
			echo "<input type='hidden' name='atualizar' value='$os'>";
			echo "<input type='hidden' name='justificativa' value=''>";

			echo "<tr class='Conteudo' height='20' bgcolor='$cor' bordercolor='#E6E8FA' align='left'  >";
			echo "<td nowrap><a href='os_press.php?os=$os' target='_blank' style='font-size:13px'>$sua_os</a></td>";
			echo "<td nowrap >$abertura</td>";
			echo "<td nowrap >$data_pedido</td>";
			echo "<td nowrap >$data_nf</td>";
			echo "<td nowrap title='$codigo_posto - $posto_nome_bd'>".$codigo_posto." - ".$data_pedido_bd_aux."</td>";
			echo "<td nowrap>$posto_fone</td>";
			//echo "<td nowrap title='$consumidor_nome'>".$consumidor_nome."</td>";
			echo "<td nowrap title='Referência: $produto_referencia \nDescrição: $produto_descricao'>".$produto."</td>";

			if ($login_fabrica==1){
				echo "<td align='center' style='font-size:9px' nowrap >";
				if ($os_troca==0){
					echo "<input type='button' value='Trocar' ALT='Efetuar a troca do Produto' border='0' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será enviada ao departamento responsável para aprovar a troca.'))  document.location='$PHP_SELF?os=$os&trocar=1&sua_os=$sua_os';\">&nbsp;&nbsp;";
				}else{
					echo "OS encaminhada para TROCA";
				}
				echo "</td>\n";
			}
			echo "</tr>";

			$pecas="";
			$sql_peca = "	SELECT  tbl_os_item.os_item AS item,
									tbl_peca.troca_obrigatoria AS troca_obrigatoria,
									tbl_peca.retorna_conserto AS retorna_conserto,
									tbl_peca.peca_critica AS peca_critica,
									tbl_peca.referencia AS referencia,
									tbl_peca.descricao AS descricao,
									tbl_peca.peca AS peca,
									tbl_peca.bloqueada_garantia,
									tbl_os_item.digitacao_item
							FROM tbl_os_produto
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_peca USING(peca)
							WHERE tbl_os_produto.os=$os
							AND tbl_os_item.servico_realizado IN ($id_servico_realizado)
							AND tbl_os_item.pedido IS NULL ";
			if ($login_fabrica<>1){
				$sql_peca .= " AND tbl_peca.peca_critica IS TRUE";
			}
			$res_peca = pg_exec($con,$sql_peca);
			$resultado = pg_numrows($res_peca);
			if(strlen($data_pedido)>0 && strlen($abertura2) > 0) {
				$sqld = "SELECT '$data_pedido_bd'::date - '$abertura2'::date";
				$resd = pg_exec($con,$sqld);
				$retorno = pg_result($resd,0,0);
			}
			$entrou = 0;
			if ($resultado>0){
				for($j=0;$j<$resultado;$j++){
					$item               = trim(pg_result($res_peca,$j,item));
					$peca_referencia    = trim(pg_result($res_peca,$j,referencia));
					$peca_descricao     = trim(pg_result($res_peca,$j,descricao));
					$bloqueada_garantia = trim(pg_result($res_peca,$j,bloqueada_garantia));
					$peca_critica       = trim(pg_result($res_peca,$j,peca_critica));
					$digitacao_item     = trim(pg_result($res_peca,$j,digitacao_item));
					$peca               = trim(pg_result($res_peca,$j,peca));

					if ($peca_critica == 't' OR $login_fabrica==1) {
						$achou=1;

						echo "<tr class='Conteudo' height='20' bgcolor='$cor' align='left' valign='top'>\n";

						if ($entrou==0){
							//echo "<td align='right' rowspan='$resultado' valign='middle'><b style='color:gray;font-weight:normal' >Peças Solicitada</b></td>\n";
						}

						if ($login_fabrica==1){
							$col_span="6";
						}else{
							$col_span="5";
						}

						echo "<td colspan='$col_span' align='right' nowrap><a href='peca_cadastro.php?peca=$peca' target='_blank'>".substr("$peca_referencia - $peca_descricao",0,40)."</a></td>";
						echo "<td align='left' nowrap>";
							echo "<select name='alterar_$item' style='font-weight:bold;font-size:12px'>";
							if ($peca_critica != 't'){ # caso a peça deixe de ser critica
								echo "<option value='autorizar' style='color:blue; font-weight:bold;' SELECTED >Autorizar</option>";
							}else{
								echo "<option value='' size='20'></option>";
								echo "<option value='autorizar' style='color:blue; font-weight:bold;'>Autorizar</option>";
								echo "<option value='cancelar'  style='color:red; font-weight:bold;'>Cancelar</option>";
								if ($login_fabrica==1){
									//echo "<option value='excluir'  style='color:#FA9C1F; font-weight:bold;'>Excluir</option>";
								}
							}
							echo "</select>";
						echo "</td>\n";

						if ($entrou==0 AND $os_troca==0){ # nao entra aki se a OS for de TROCA
							echo "<td align='center' rowspan='$resultado' valign='middle'>\n";
							echo "<input type='button' value='Gravar' onClick=\"javascript:if (this.alt=='Aguarde'){alert('Aguarde submissão'); return false;}  verificar(this,'frm_atualizar_$os','$sua_os');\" >\n";
							echo "</td>\n";
						}
						$entrou++;
						echo "</tr>\n";
					}
				}
			}
			if ($entrou==0){
					echo "<tr class='Conteudo' height='20' bgcolor='$cor' align='left' valign='top'>";
					echo "<td align='center' colspan='6'>";
					echo "<input type='hidden' name='os_sem_peca' value='$os'>";
					if ($login_fabrica==3){
						echo "<b style='color:red;font-weight:normal'>Esta OS não possui mais peças bloqueadas para garantia. ADMIN alterou a OS ou cadastro da peça foi alterado.</b>";
					}
					if ($login_fabrica==11){
						echo "<b style='color:red;font-weight:normal'>$status_observacao</b>";
					}
					echo "</td>\n";
					echo "<td align='center' valign='middle'>";
					if ($os_troca==0){
						echo "<a href=\"javascript:if (this.title=='Aguarde'){alert('Aguarde submissão');} else{ this.title='Aguarde'; document.frm_atualizar_$os.submit(); }\" title='Liberar'>LIBERAR OS</a>";
					}
					echo "</td>";
					echo "</tr>";
			}
			echo "<tr  bgcolor='#596D9B'  height='3'>";
			echo "<td colspan='9'>";
			echo "</td>";
			echo "</tr>";
			echo "</form>";

		}
		if ($total==0){
			echo "<tr class='Conteudo' height='20' bgcolor='#FFFFCC' align='left'>
				<td colspan='12' style='padding:10px' align='center'>NENHUMA OS COM INTERVENÇÃO DE SUPRIMENTO</td>
				</tr>";
			echo "</table></center>";
		}
		else{
			echo "</table></center>";
			// ##### PAGINACAO ##### //
			// links da paginacao
			echo "<br>";
			echo "<div>";

			if($pagina < $max_links) {
				$paginacao = pagina + 1;
			}else{
				$paginacao = pagina;
			}

			// paginacao com restricao de links da paginacao
			// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
			$todos_links = $mult_pag->Construir_Links("strings", "sim");

			// função que limita a quantidade de links no rodape
			$links_limitados = $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

			for ($n = 0; $n < count($links_limitados); $n++) {
				echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
			}

			echo "</div>";

			$resultado_inicial = ($pagina * $max_res) + 1;
			$resultado_final   = $max_res + ( $pagina * $max_res);
			$registros         = $mult_pag->Retorna_Resultado();

			$valor_pagina   = $pagina + 1;
			$numero_paginas = intval(($registros / $max_res) + 1);

			if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

			if ($registros > 0){
				echo "<br>";
				echo "<div>";
				echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
				echo "<font color='#cccccc' size='1'>";
				echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
				echo "</font>";
				echo "</div>";
			}
			// ##### PAGINACAO ##### //
		}
	//}
}
?>
<br><br>
<?
include "rodape.php"

?>
