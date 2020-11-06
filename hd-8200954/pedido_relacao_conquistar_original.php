<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "funcoes.php";
// HD 14351
$pedido_obs=$_GET['pedido'];
if($_GET['obs']==1 and strlen($pedido_obs) >0){
	$sql="SELECT obs from tbl_pedido where pedido=$pedido_obs";
	$res=pg_exec($con,$sql);
	$pedido_obs = pg_result($res,0,obs);
	echo "<center>"; fecho("observacao.do.pedido:.%",$con,$cook_idioma,$pedido_obs); echo "<br> </center>";
	exit;
}


function traduz_status_pedido($status_pedido){

	global  $con;
	global  $cook_idioma;

	$sql = "SELECT msg_id FROM tbl_msg WHERE msg_text = '$status_pedido'::text limit 1;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$status_traduz = pg_result($res,0,0);
		$status_traduz = traduz("$status_traduz",$con,$cook_idioma);
		return $status_traduz;
	}else{
		$status_traduz = $status_pedido;
		return $status_traduz;
	}
}



$btn_gravar = $_POST['btn_gravar'];
if(strlen($btn_gravar)>0){
	$total_pedido = $_POST['total_pedido'];
	//echo "total: $total_pedido<BR>";
	if($total_pedido>0){
		for($x=0;$x<$total_pedido;$x++){
			$data_recebimento = $_POST['data_recebimento_'.$x];
			$pedido_recebimento = $_POST['pedido_recebimento_'.$x];
			$data_recebimento = fnc_formata_data_pg($data_recebimento);
			$data_recebimento = str_replace("'","",$data_recebimento);
		//	echo "$pedido_recebimento - $data_recebimento";
if(strlen($data_recebimento)>0 and $data_recebimento<>'null' and strlen($pedido_recebimento)>0){
			$sql = "UPDATE tbl_pedido set recebido_posto='$data_recebimento'
					where pedido=$pedido_recebimento
					and fabrica=$login_fabrica";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro)==0 and 1==2){
				$res = pg_exec ($con,"BEGIN TRANSACTION");
				$sql = "SELECT fn_estoque ($pedido_recebimento,$login_fabrica,'$data_recebimento')";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				//echo $sql;
				if(strlen($msg_erro)==0){
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				}
			}
//echo $sql;
}
		}
	}

}

$msg_erro="";
if (strlen($_GET["excluir"]) > 0) $excluir = $_GET["excluir"];
if (strlen($_GET["posto_senha"]) > 0) $posto_senha = trim($_GET["posto_senha"]); // HD 56032

if($login_fabrica== 3 and strlen($excluir) > 0) {
	if(strlen($posto_senha) > 0) {
		$sql = "SELECT senha FROM tbl_posto_fabrica WHERE posto= $login_posto AND fabrica = $login_fabrica";
		$res=pg_exec($con,$sql);
		$senha = pg_result($res,0,senha);
		if(md5($senha) <> $posto_senha) $msg_erro="Senha inválida";
	}else{
		$msg_erro="Digite a senha para exluir o pedido";
	}
}

if (strlen($excluir) > 0 and strlen($msg_erro) == 0) {
	$sql = "SELECT pedido,
					tipo_pedido,
					exportado
			FROM tbl_pedido
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.posto   = $login_posto
			AND   tbl_pedido.pedido  = $excluir
			AND   tbl_pedido.exportado IS NULL;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$tipo_pedido = trim(pg_result($res,0,tipo_pedido));
		$exportado   = trim(pg_result($res,0, exportado));

		//hd 17227 9/4/2008
		/*$sql =	"DELETE FROM tbl_pedido
				WHERE tbl_pedido.pedido  = $excluir
				AND   tbl_pedido.posto   = $login_posto
				AND   tbl_pedido.fabrica = $login_fabrica
				AND   tbl_pedido.exportado IS NULL;";*/

		//a pedido de Tulio nao deletar pq esta matando o banco, mover para fabrica 0 (zero)
		if (strlen($exportado)==0){
			$res = @pg_exec($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido_item
					set qtde_cancelada = tbl_pedido_item.qtde - tbl_pedido_item.qtde_faturada
					where pedido = $excluir;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sqlx = "SELECT tbl_pedido.posto            ,
						tbl_pedido.fabrica              ,
						tbl_pedido_item.pedido          ,
						tbl_pedido_item.qtde_cancelada  ,
						tbl_peca.peca                   ,
						tbl_os.os
						FROM    tbl_pedido
						JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido  = tbl_pedido.pedido
						JOIN    tbl_peca ON  tbl_peca.peca                  = tbl_pedido_item.peca
						LEFT JOIN    tbl_os_item ON  tbl_os_item.peca       = tbl_pedido_item.peca
						AND tbl_os_item.pedido =  tbl_pedido.pedido
						LEFT JOIN tbl_os_produto ON  tbl_os_produto.os_produto  = tbl_os_item.os_produto
						LEFT JOIN tbl_os ON  tbl_os.os                  = tbl_os_produto.os
						WHERE tbl_pedido_item.pedido = $excluir";
			$resx = pg_exec($con,$sqlx);

			for ($i = 0 ; $i < pg_numrows ($resx) ; $i++) {
					$posto   = pg_result ($resx,$i,posto);
					$fabrica = pg_result ($resx,$i,fabrica);
					$pedido  = pg_result ($resx,$i,pedido);
					$qtde    = pg_result ($resx,$i,qtde_cancelada);
					$peca    = pg_result ($resx,$i,peca);
					$os      = pg_result ($resx,$i,os);

				if(strlen($os)== 0) $os = "null";

				$sql = "INSERT INTO tbl_pedido_cancelado(
							pedido  ,
							posto   ,
							fabrica ,
							os      ,
							peca    ,
							qtde    ,
							motivo  ,
							data
							)values(
							'$pedido',
							'$posto',
							'$fabrica',
							$os,
							'$peca',
							'$qtde',
							'Pedido cancelado pelo posto em ('||current_timestamp||')',
							current_date
						);";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			$sql = "UPDATE tbl_pedido
					set status_pedido = '14'
					where pedido = $excluir;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen ($msg_erro) == 0) {
				$res = @pg_exec ($con,"COMMIT TRANSACTION");
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}

		}else{
			$sql =	"UPDATE tbl_pedido
					SET fabrica = 0
					WHERE tbl_pedido.pedido  = $excluir
					AND   tbl_pedido.posto   = $login_posto
					AND   tbl_pedido.fabrica = $login_fabrica
					AND   tbl_pedido.exportado IS NULL;";
			$res = @pg_exec($con,$sql);
		}

		# Rotina para voltar a peça para o estoque da peça para a Loja Virtual -- Fabio 13/09/2007
		if($login_fabrica==3 AND $tipo_pedido=='2'){

			if (strlen($msg_erro)==0){
				$sql = "UPDATE tbl_peca
						SET qtde_disponivel_site = qtde_disponivel_site + tbl_pedido_item.qtde
						FROM tbl_pedido
						JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
						WHERE tbl_pedido.pedido  = $excluir
						AND tbl_pedido_item.peca = tbl_peca.peca
						AND tbl_pedido.fabrica   = $login_fabrica
						AND tbl_pedido.posto     = $login_posto
						AND tbl_pedido.pedido_loja_virtual IS TRUE
						AND qtde_disponivel_site IS NOT NULL
						AND tbl_pedido.exportado IS NULL";
				$res = pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
			#outro jeito que eu fiz, mas o anterior é melhor
			if (1==2){
				$sql2 = "SELECT
							tbl_pedido_item.pedido_item,
							tbl_pedido_item.peca,
							tbl_pedido_item.qtde
					FROM  tbl_pedido
					JOIN  tbl_pedido_item USING (pedido)
					JOIN  tbl_peca        USING (peca)
					WHERE tbl_pedido.pedido_loja_virtual IS TRUE
					AND   tbl_pedido.exportado IS NULL
					AND   tbl_pedido.posto   = $login_posto
					AND   tbl_pedido.fabrica = $login_fabrica
					AND   tbl_pedido.pedido  =  $excluir
					ORDER BY tbl_pedido.pedido DESC";

				$res2 = pg_exec ($con,$sql2);
				if (pg_numrows($res2) > 0) {
					for($i=0; $i< pg_numrows($res2); $i++) {
						$pedido_item     = trim(pg_result($res2,$i,pedido_item));
						$peca            = trim(pg_result($res2,$i,peca));
						$qtde_remover    = trim(pg_result($res2,$i,qtde));
						if (strlen($msg_erro) == 0) {
							$sql3 = "UPDATE tbl_peca
									SET   qtde_disponivel_site = qtde_disponivel_site + $qtde_remover
									WHERE peca     = $peca
									AND   fabrica  = $login_fabrica";
							$res3 = pg_exec ($con,$sql3);
							$msg_erro = pg_errormessage($con);
						}
					}
				}
			}
		}

		$msg_erro = pg_errormessage($con);



		if (strlen($msg_erro) == 0) {
			header("Location: $PHP_SELF?listar=todas");
			exit;
		}
	}
}

$title = traduz("relacao.de.pedido.de.pecas",$con,$cook_idioma);
$layout_menu = traduz("pedido",$con,$cook_idioma);
include "cabecalho.php";

?>


<?
include "admin/javascript_calendario.php";
if(strlen($data_inicial)==0 AND strlen($data_final)==0){
	$fnc  = @pg_exec($con,"SELECT to_char(current_date - interval '30 days','DD/MM/YYYY');");
	$data_inicial = @pg_result ($fnc,0,0);

	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	$data_final = date("d/m/Y");
}
?>
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/md5.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

	function excluirPedido(pedido){
		var senha = prompt('Informe a senha para excluir o pedido.', '');
		if(senha.value!="") {
			window.location = "<?=$PHP_SELF?>?excluir="+pedido+"&posto_senha="+hex_md5(senha);
		}
	}

	function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla > 47 && tecla < 58)) return true;
    else{
    if (tecla != 8) return false;
    else return true;
    }
}
</script>
<style>
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Pesquisa{
	font-family: Verdana, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: none;
	color: #333333;
	border:#485989 1px solid;
	background-color: #EFF4FA;
}

.Pesquisa caption {
	font-size:14px;
	font-weight:bold;
	color: #FFFFFF;
	background-color: #596D9B;
	text-align:'left';
	text-transform:uppercase;
	padding:0px 5px;
}

.Pesquisa thead td{
	text-align: center;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Pesquisa tbody th{
	font-size: 12px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}
.Pesquisa tbody td{
	font-size: 10px;
	font-weight: none;
	text-align:'left';
	color: #333333;
}

.Pesquisa tfoot td{
	font-size:10px;
	font-weight:bold;
	color: #000000;
	text-align:'left';
	text-transform:uppercase;
	padding:0px 5px;
}

</style>
<p>

<? if ($login_fabrica == 1) { ?>
<? ##FRASE TRADUÇÂO ?>
<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>PREZADO ASSISTENTE: Quando existir um pedido feito pelo pessoal da Black & Decker,
irá aparecer na coluna Black o nome do usuário que o efetuou,
caso contrário foi um pedido feito pela própria Assistência.</b></font>
<br><br><br>
<font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#FF0000"><b>Pedidos não finalizados devem ser cancelados ou finalizados para que sejam faturados.
Estes pedidos não devem ficar em aberto no sistema, para evitarmos transtornos futuros.
Caso queria finalizar o pedido ou excluir o mesmo, clique no número do mesmo e delete ou finalize.</b></font>
<br><br><br>
<? } ?>

<? if($login_fabrica == 3) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
	<? echo $msg_erro;?>
	</td>
</tr>
</table>
<? } ?>
<table width="700" align="center" border="0" cellspacing="2" cellpadding="2" bgcolor='#D9E2EF' class='Pesquisa'>
<? if(strlen($msg_erro)>0) { ?>
<tr>
	<td valign="middle" align="center" class='error'>
	<? echo $msg_erro;?>
	</td>
</tr>

<? } ?>
<caption><? fecho("pesquisa.de.pedido",$con,$cook_idioma); ?></caption>
<form name='frm_pedido_consulta' action='<? echo $PHP_SELF; ?>' method='get'>
<input type='hidden' name='btn_acao_pesquisa' value=''>
<tr>
	<th nowrap><? fecho("numero.do.pedido.para.consulta",$con,$cook_idioma); ?></th>
	<td nowrap><input type='text' name='pedido' value='' tabindex='1' onkeypress='return SomenteNumero(event)'></td>
</tr>
<tr>
	<th nowrap><? fecho("consulta.pelo.codigo.da.peca",$con,$cook_idioma); ?></th>
	<td nowrap><input type='text' name='referencia' value='' tabindex='2' ></td>
</tr>
<tr>
	<th><? fecho("data.inicial",$con,$cook_idioma); ?></th>
	<td><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" tabindex='3'>
	</td>
</tr>
<tr>
	<th><? fecho("data.final",$con,$cook_idioma); ?></th>
	<td><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" tabindex='4'></TD>
</tr>

<? if($login_fabrica==51 or $login_fabrica==45){ //HD 49364 ?>
<tr>
	<th><? fecho("status.pedido",$con,$cook_idioma); ?></th>
	<td>
		<?
			if($login_fabrica==45){
				$cond_status = " status_pedido IN(1, 2, 3, 4, 5, 8, 9, 14) ";
			}else if($login_fabrica==51){
				$cond_status = " status_pedido IN(1, 2, 4, 5, 7, 8, 11, 12, 13, 14) ";
			}else{
				$cond_status = " 1=1 ";
			}

			$sqlS = "SELECT status_pedido,
							descricao
					 FROM tbl_status_pedido
					 WHERE $cond_status;";
			#echo $sqlS;
			$resS = pg_exec($con, $sqlS);

			if(pg_numrows($resS)>0){
				echo "<select name='status_pedido' tabindex='5'>";
					echo "<option value=''></option>";
				for($s=0; $s<pg_numrows($resS); $s++){
					$status_pedido    = pg_result($resS, $s, status_pedido);
					$status_descricao = pg_result($resS, $s, descricao);
					echo "<option value='$status_pedido'>$status_descricao</option>";
				}
				echo "</select>";
			}
		?>
	</td>
</tr>
<? } ?>

<tfoot>
<tr>
	<td colspan=2 style='text-align:center' valign='middle' nowrap><input type='image' src='admin/imagens_admin/btn_pesquisar_400.gif' tabindex='6' onclick="javascript: if (document.frm_pedido_consulta.btn_acao_pesquisa.value == '' ) { document.frm_pedido_consulta.btn_acao_pesquisa.value='continuar' ; document.frm_pedido_consulta.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar busca pelo Pedido" border='0' style='cursor: pointer'></td>

</tr>
</tfoot>
</form>
</table>

<?
$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];
if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = $_GET['btn_acao_pesquisa'];

$listar = $_POST['listar'];
if (strlen($_GET['listar']) > 0) $listar = $_GET['listar'];

$pedido = $_POST['pedido'];
if (strlen($_GET['pedido']) > 0) $pedido = $_GET['pedido'];

$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

$data_final = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

$referencia = $_POST['referencia'];
if (strlen($_GET['referencia']) > 0) $referencia = $_GET['referencia'];

$status_pedido = $_POST['status_pedido'];
if (strlen($_GET['status_pedido']) > 0) $status_pedido = $_GET['status_pedido'];


if (( (strlen($pedido) > 0 OR strlen($referencia) > 0 OR (strlen($data_inicial)>0 and strlen($data_final)>0)) AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0){
	if(strlen($pedido) > 0){
		echo "<br><center><b>"; fecho("voce.esta.pesquisando.o.pedido.%",$con,$cook_idioma,$pedido); echo "</b></center>";
	}
	/*HD 15618 - Alterar para data*/
	if(strlen($data_inicial)>0 and strlen($data_final)>0 and strlen($pedido) == 0) {

		$fnc  = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
		$erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);

		$fnc  = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		$erro = pg_errormessage ($con) ;

		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		$add_1 = " AND tbl_pedido.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";

		$sql = " SELECT '$aux_data_inicial'::date < '$aux_data_final'::date";
		$res = @pg_query($con,$sql);
		$erro = pg_last_error($con);

		if(!empty($erro)) {
			$erro = "Data Inválida";
		}elseif(pg_num_rows($res) > 0){
			if(pg_fetch_result($res,0,0) == 'f'){
				$erro = "A data inicial não pode ser maior que a data final";
			}
		}
		
	}


	if(empty($erro)) {
		$sql = "SELECT  tbl_pedido.pedido                                                  ,
					case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
					else
						lpad ((tbl_pedido.pedido_blackedecker)::text,5,'0')
					end                                          AS pedido_blackedecker,
					tbl_pedido.seu_pedido                                              ,
					tbl_pedido.data                                                    ,
					TO_CHAR(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizado          ,
					TO_CHAR(tbl_pedido.recebido_posto,'DD/MM/YYYY') AS  recebido_posto ,
					tbl_pedido.exportado                                               ,
					tbl_pedido.distribuidor                                            ,
					tbl_pedido.total                                                   ,
					tbl_pedido.pedido_sedex                                            ,
					tbl_pedido.pedido_loja_virtual                                     ,
					tbl_tipo_pedido.descricao AS tipo_pedido_descricao                 ,
					tbl_linha.nome			  AS linha_descricao                       ,
					NULL  AS  pedido_status                                            ,
					tbl_status_pedido.status_pedido AS id_status                       ,
					tbl_status_pedido.descricao AS xstatus_pedido                      ,
					tbl_pedido.obs                                                     ,
					tbl_pedido.permite_alteracao                                       , ";
		if ($login_fabrica <> 1 && $login_fabrica <> 24) $sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco * ((tbl_peca.ipi / 100)+1))::numeric,'999999990.99' )::float AS preco_ipi ";
		else					 $sql .= "to_char(SUM(tbl_pedido_item.qtde * tbl_pedido_item.preco)::numeric,'999999990.99' )::float  AS preco_ipi ";
			$sql .= "FROM    tbl_pedido
					JOIN    tbl_tipo_pedido     USING (tipo_pedido)
					JOIN    tbl_pedido_item     USING (pedido)
					JOIN    tbl_peca            USING (peca)
					LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
					LEFT JOIN tbl_linha         ON tbl_linha.linha = tbl_pedido.linha
					WHERE   tbl_pedido.posto   = $login_posto
					AND     tbl_pedido.fabrica = $login_fabrica
					$add_1";

			if ($login_fabrica == 1) $sql .= " AND tbl_pedido.pedido_acessorio IS FALSE ";

			if (strlen($pedido) > 0 AND $login_fabrica == 1) {
				//$sql .= "AND tbl_pedido.pedido_blackedecker::text LIKE '%$pedido%' ";
				#HD 34403
				$sql .= "AND (substr(tbl_pedido.seu_pedido,4) like '%$pedido' OR tbl_pedido.seu_pedido = '$pedido' ) ";
			}

			if (strlen($pedido) > 0 AND $login_fabrica <> 1) {
				$sql .= "AND tbl_pedido.pedido = $pedido ";
			}

			if (strlen($status_pedido) > 0 AND ($login_fabrica == 51 or $login_fabrica == 45)) {
				$sql .= "AND tbl_pedido.status_pedido = $status_pedido ";
			}


			# Troquei ILIKE por LIKE - Fabio- HD 14504
			if (strlen($referencia) > 0) $sql .= "AND tbl_peca.referencia LIKE '%$referencia%' ";

			$sql .= "GROUP BY tbl_pedido.pedido           ,
							tbl_pedido.pedido_blackedecker,
							tbl_pedido.seu_pedido         ,
							tbl_pedido.data               ,
							tbl_pedido.finalizado         ,
							tbl_pedido.recebido_posto,
							tbl_pedido.total              ,
							tbl_tipo_pedido.descricao     ,
							tbl_status_pedido.status_pedido,
							tbl_status_pedido.descricao   ,
							tbl_pedido.exportado          ,
							tbl_pedido.distribuidor       ,
							tbl_pedido.pedido_sedex       ,
							tbl_linha.nome,
							tbl_pedido.pedido_loja_virtual,
							tbl_pedido.obs                ,
							tbl_pedido.permite_alteracao
					ORDER BY tbl_pedido.data DESC";
			$res = pg_exec ($con,$sql);

		#echo nl2br($sql);
		#exit;

			$sqlCount  = "SELECT count(*) FROM (";
			$sqlCount .= $sql;
			$sqlCount .= ") AS count";

			// ##### PAGINACAO ##### //
			require "_class_paginacao.php";

			// definicoes de variaveis
			$max_links = 11;				// máximo de links à serem exibidos
			$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
			$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
			$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

			$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

			// ##### PAGINACAO ##### //

			if (@pg_numrows($res) > 0) {
		echo "<form name='frm_pedido_lista' method='post' action='$PHP_SELF'>";
				echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff'>";
				echo "<tr>";
				echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
				echo "<td valign='top' align='center'>";

				echo "<p>";

				if (strlen($referencia) > 0){
					echo "<table width='600' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#f1f1f1'>";
					echo "<tr height='25'>";
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("pedidos.com.a.peca",$con,$cook_idioma,$referencia); echo "</b></font></td>";
					echo "</tr>";
					echo "</table>";
				}
				echo "<p>";

				echo "<table width='670' border='0' cellspacing='5' cellpadding='0' align='center'>";
				echo "<tr height='20' class='Titulo' background='admin/imagens_admin/azul.gif'>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("pedido",$con,$cook_idioma); echo "</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("data",$con,$cook_idioma); echo"</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("finalizado",$con,$cook_idioma); echo"</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("status",$con,$cook_idioma); echo "</b></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("tipo.pedido",$con,$cook_idioma); echo "</b></font></td>";
				if ($login_fabrica <> 1){
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("linha",$con,$cook_idioma); echo "</b></font></td>";
				}
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("valor.total",$con,$cook_idioma); echo "</b></font></td>";
				if(($login_fabrica==24) OR ($login_fabrica==1 and $login_posto==5197) ){
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("data.recebimento",$con,$cook_idioma); echo "</b></font></td>";
				}
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("acao",$con,$cook_idioma); echo"</b></font></td>";
				//HD 14351
				if($login_fabrica==1){
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>"; fecho("obs",$con,$cook_idioma); echo "</b></font></td>";
				}
				echo "</tr>";

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
					$cor = "#FFFFFF";
					if ($i % 2 == 0) $cor = '#F1F4FA';
					
					$total                 = ($login_fabrica != 87) ? pg_result($res,$i,preco_ipi) : pg_result($res,$i,total);
					$pedido                = trim(pg_result($res,$i,pedido));
					$pedido_blackedecker   = trim(pg_result($res,$i,pedido_blackedecker));
					$seu_pedido            = trim(pg_result($res,$i,seu_pedido));
					$data                  = trim(pg_result($res,$i,data));
					$finalizado            = trim(pg_result($res,$i,finalizado));
					$pedido_sedex          = trim(pg_result($res,$i,pedido_sedex));
					$pedido_loja_virtual   = trim(pg_result($res,$i,pedido_loja_virtual));
					$id_status             = trim(pg_result($res,$i,id_status));
					if ($login_fabrica == 2)
						$pedido_status     = "OK";
					else
						$pedido_status     = trim(pg_result($res,$i,pedido_status));
					$status_pedido         = trim(pg_result($res,$i,xstatus_pedido));
					$tipo_pedido_descricao = trim(pg_result($res,$i,tipo_pedido_descricao));
					$linha                 = trim(pg_result($res,$i,linha_descricao));
					$exportado             = trim(pg_result($res,$i,exportado));
					$distribuidor          = trim(pg_result($res,$i,distribuidor));
					$recebido_posto        = trim(pg_result($res,$i,recebido_posto));
					$obs                   = trim(pg_result($res,$i,obs));
					$permite_alteracao     = trim(pg_result($res,$i,permite_alteracao));

					if (strlen($seu_pedido)>0){
						$pedido_blackedecker = fnc_so_numeros($seu_pedido);
					}


					echo "<tr bgcolor='$cor'>";
					if ($login_fabrica <> 1) {
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido</a></font></td>";
					}else{
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'><a href='pedido_finalizado.php?pedido=$pedido'>$pedido_blackedecker</a></font></td>";
					}
					echo "<td align='center' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>". mostra_data ($data) ."</font></td>";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>". $finalizado ."</font></td>";

					#hd 212245

					if ($login_fabrica == 14) {

						$sqldata = "SELECT CASE WHEN '$data' < '2009-08-27' THEN 'sim' ELSE 'nao' END";
						$resdata = pg_exec($con,$sqldata);
						
						$resposta = pg_result($resdata,0,0);

						if ($resposta == 'sim') {
							$status_pedido = '';
						}

					}

					if (strlen($pedido_status) > 0) {
						echo "<td nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; $status_pedido_traduzido = traduz_status_pedido($status_pedido); echo "$status_pedido_traduzido</font></td>";
					}else{
						/*if ($login_fabrica==1 AND $pedido_sedex=='f' AND $tipo_pedido_descricao=="FATURADO" AND $pedido>457066){
							echo "<td nowrap>-</td>";
						}else{*/
							echo "<td nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; $status_pedido_traduzido = traduz_status_pedido($status_pedido); echo "$status_pedido_traduzido</font></td>";
						//}
					}

					# Adicionado por Fábio - HD 4285
					if ($login_fabrica==3 AND $pedido_loja_virtual=='t'){
						$tipo_pedido_descricao = "Loja Virtual";
					}
					echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$tipo_pedido_descricao</font></td>";
					if ($login_fabrica <> 1 AND $login_fabrica <> 43 ){
						if(strlen($linha)==0){
							$sqll = "SELECT distinct tbl_linha.nome AS nome_linha
									FROM tbl_pedido_item
									JOIN tbl_lista_basica on tbl_lista_basica.peca = tbl_pedido_item.peca
									JOIN tbl_produto on tbl_produto.produto = tbl_lista_basica.produto
									JOIN tbl_linha on tbl_produto.linha = tbl_linha.linha
									WHERE pedido = $pedido LIMIT 1";
									//if($ip=='200.228.76.7') echo nl2br($sqll);
							$resl=pg_exec($con,$sqll);
							if(strlen(@pg_result($resl,0,nome_linha))>0){
								$linha = pg_result($resl,0,nome_linha);
							}
						}
						echo "<td nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$linha</font></td>";
					}
					echo "<td align='right'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total,2,",",".") ."</font></td>";
					if(($login_fabrica==24) OR ($login_fabrica==1 and $login_posto==5197)){
						echo "<td nowrap align='center'>";
						if(strlen($recebido_posto)==0){
							echo "<input type='hidden' name='pedido_recebimento_$i' value='$pedido'>";
							echo "<input type='text' name='data_recebimento_$i' value='' size='10' maxlength='10'>";
						}else{
							echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>$recebido_posto</font>";
						}
						echo "</td>";
					}

					echo "<td align='left'>";
					if (strlen ($exportado) == 0 AND strlen ($distribuidor) == 0 AND $id_status <> 14) {
						if($login_fabrica == 7 ){
							if($tipo_pedido_descricao== "Garantia" OR $tipo_pedido_descricao== "Consignação" OR $tipo_pedido_descricao== "Empréstimo" ){
								/*NÃO É PARA EXCLUIR PEDIDOS DE OS*/
							}else{
								echo "<a href='$PHP_SELF?excluir=$pedido'>";
								echo "<img border='0' src='imagens/btn_excluir.gif'></a>";
							}
						}else{
							if($login_fabrica == 3 ) { // HD56032
								 echo "<a href=\"javascript:excluirPedido('$pedido');\">";
							}else{
								if($login_fabrica <> 15) {#HD 236986
									echo "<a href='$PHP_SELF?excluir=$pedido'>";
								}
								
							}
							if($login_fabrica <> 15) { #HD 236986
								echo "<img border='0' src='imagens/btn_excluir.gif'></a>";
							}
						}
					}
					#HD 47695
					if ($login_fabrica == 7 AND strlen ($exportado) == 0 AND $permite_alteracao == 't' AND $id_status <> 14) {
						echo " <a href='pedido_cadastro.php?pedido=$pedido'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
					}
					if ($login_fabrica == 5 AND strlen ($exportado) == 0){
						echo " <a href='pedido_cadastro.php?pedido=$pedido'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
					}
					echo "</td>";
					// HD 14351
					if($login_fabrica==1){
						echo "<td>";
						if(strlen($obs) >0){
							echo "<a href=\"pedido_relacao.php?obs=1&pedido=$pedido&keepThis=trueTB_iframe=true&height=200&width=300\" title=\"Observação\" class=\"thickbox\"\">VER</a>";
						}
						echo "</td>";
					}
					echo "</tr>";
				}
		if(($login_fabrica==24) OR ($login_fabrica==1 and $login_posto==5197)){
		echo "<input type='hidden' name='btn_gravar' value=''>";
		echo "<input type='hidden' name='total_pedido' value='$i'>";
		echo "<tr>";
		echo "<td colspan='9' align='center'><img border='0' src='imagens/btn_gravar.gif' onClick=\"javascript: if (document.frm_pedido_lista.btn_gravar.value == '' ) { document.frm_pedido_lista.btn_gravar.value='gravar' ; document.frm_pedido_lista.submit(); } else { alert ('Aguarde submissão'); }\"></td>";
		echo "</tr>";

		}

				echo "</table>";
				echo "</form>";
				echo "</td>";
				echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";

				echo "</tr>";
				//echo "<tr>";

				//echo "<td height='27' valign='middle' align='center' colspan='3' bgcolor='#FFFFFF'>";
				//echo "<a href='pedido_cadastro.php'><img src='imagens/btn_lancarnovopedido.gif'></a>";
				//echo "</td>";

				//echo "</tr>";
				echo "</table>";

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
				$todos_links		= $mult_pag->Construir_Links("strings", "sim");

				// função que limita a quantidade de links no rodape
				$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

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
					fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
					echo "<font color='#cccccc' size='1'>";
					fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
					echo "</font>";
					echo "</div>";
				}
				// ##### PAGINACAO ##### //
			}else{
				echo "<p>";

				echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
				echo "<tr>";

				echo "<td valign='top' align='center'>";
				echo "<h4>"; fecho("nenhum.pedido.encontrado",$con,$cook_idioma); echo "</h4>";
				echo "</td>";

				echo "</tr>";
				echo "</table>";
			}
	}else{
		if(strpos($erro,"valid input syntax for type timestamp")) {
			$erro = "Data inválida para pesquisa";
		}

		echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
		echo "<tr>";
		echo "<td valign='middle' align='center' class='error'>";

			if (strpos($erro,"ERROR: ") !== false) {
				$erro = substr($erro, 6);
			}

			if (strpos($erro,"CONTEXT:")) {
				$x = explode('CONTEXT:',$erro);
				$erro = $x[0];
			}

			echo $erro . $msg_erro;

		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}
	
}
?>

<p>

<? include "rodape.php"; ?>
