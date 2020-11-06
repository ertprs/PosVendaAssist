<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$programa_insert = $_SERVER['PHP_SELF'];

/*	HD 135436(+Mondial))
	Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
	na os_press, admin/os_press e na os_fechamento, sempre nesta função
*/
function usaDataConserto($posto, $fabrica) {
	if ($posto == '4311' or (($fabrica <> 11 and $fabrica<>1) and $posto==6359) or
		in_array($fabrica, array(3,5,7,11,14,15,20,43,45)) or $fabrica >50) {
		return true;
	}
	return false;
}

if (isset($_POST['gravarDataconserto']) AND isset($_POST['os'])){
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		if(strlen($gravarDataconserto ) > 0) {
			$data = $gravarDataconserto.":00 ";
			$aux_ano  = substr ($data,6,4);
			$aux_mes  = substr ($data,3,2);
			$aux_dia  = substr ($data,0,2);
			$aux_hora = substr ($data,11,5).":00";
			$gravarDataconserto ="'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";
		} else {
			$gravarDataconserto ='null';
		}

		$erro = "";

		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto > CURRENT_TIMESTAMP ";
			$res = pg_exec($con,$sql);
			if (pg_result($res,0,0) == 't'){
				$erro = "Data de conserto não pode ser superior a data atual.";
			}
		}

		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
			$res = pg_exec($con,$sql);
			if (pg_result($res,0,0) == 't'){
				$erro = "Data de conserto não pode ser anterior a data de abertura.";
			}
		}

		if (strlen($erro) == 0) {
			$sql = "UPDATE tbl_os
					SET data_conserto = $gravarDataconserto
					WHERE os=$os
					AND fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
		} else {
			echo $erro;
		}
	}
	exit;
}


if($login_fabrica == 6){ // HD-2389192
	if($_POST['btn_acao'] == "gravar_obs_os"){
		$os_id = $_POST['os_id'];
		$obs_os = $_POST['obs_os'];

		$sql = "SELECT obs FROM tbl_os WHERE os = $os_id AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);
		$obs_existe = pg_fetch_result($res, 0, 'obs');

		$observacao_os = "$obs_os<br />$obs_existe";

		$sql_up = "UPDATE tbl_os SET obs = '$observacao_os' WHERE os = $os_id AND fabrica = $login_fabrica";
		$res_up = pg_query($con, $sql_up);

		if(!pg_last_error($con)){
			echo "ok";
		}else{
			echo pg_last_error($con);
		}
		exit;
	}

}


$title = "Fechamento de Ordem de Serviço";
$layout_menu = 'callcenter';
include "cabecalho.php";


#------------ Fecha Ordem de Servico ------------#
$btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == 'continuar') {

	$data_fechamento = $_POST['data_fechamento'];
	$qtde_os         = $_POST['qtde_os'];

	if (strlen($data_fechamento) == 0){
		if($sistema_lingua == "ES") $msg_erro = "Digite la fecha de cierre";
		else                        $msg_erro = "Digite a data de fechamento.";
	}else{
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);

		if($xdata_fechamento > "'".date("Y-m-d")."'"){
			if($sistema_lingua == "ES") $msg_erro = "Fecha de cierre mayor que la frcha de hoy.";
 			else                        $msg_erro = "Data fechamento maior que a data de hoje";
		}


		//HD 9013
		if($login_fabrica==1){
			$conta_ativo=0;
			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$ativo_revenda      = trim($_POST['ativo_revenda_'. $i]);
				$consumidor_revenda = trim($_POST['consumidor_revenda_'. $i]);
				if($ativo_revenda=='t' and $consumidor_revenda=='R'){
					$conta_ativo++;
					$os            = trim($_POST['os_'. $i]);
				}
			}
			if($conta_ativo!=$qtde_os and strlen($os)>0){

				$sql="SELECT codigo_posto,tbl_os.sua_os, tbl_os.os_numero
						from tbl_os
						join tbl_posto_fabrica on tbl_posto_fabrica.posto=tbl_os.posto and tbl_posto_fabrica.fabrica=$login_fabrica
						where tbl_os.os=$os
						and tbl_os.consumidor_revenda='R'";
				$res = pg_exec($con,$sql);
				$codigo_posto  = pg_result($res,0,codigo_posto);
				$sua_os        = pg_result($res,0,sua_os);
				$os_numero     = pg_result($res,0,os_numero);
				$sua_os        = substr ($sua_os,0,5);

				$sql = "SELECT count(*) As qtde_os_revenda
						FROM tbl_os
						WHERE os_numero = $os_numero
						AND   posto     = $posto
						AND   fabrica   = $login_fabrica
						AND   consumidor_revenda ='R' ";
				$res = pg_exec($con,$sql);
				$qtde_os_revenda  = pg_result($res,0,qtde_os_revenda);

				if($qtde_os_revenda <> 1){
					$msg_erro="A O.S. DE REVENDA $codigo_posto$sua_os FOI EXPLODIDA PARA VÁRIOS PRODUTOS E O FECHAMENTO PODERÁ SER CONCLUÍDO SOMENTE QUANDO TODOS OS PRODUTOS DESSA O.S. FOREM ENTREGUES PARA O CLIENTE. NESSE CASO, SERÁ NECESSÁRIO EFETUAR O FECHAMENTO DE TODAS AS OS'S DE REVENDA COM ESSE MESMO NÚMERO.  ";
				}
			}
		}
		if (strlen($msg_erro) == 0){
			// HD  27468
			if($login_fabrica ==1){
				$res = pg_exec ($con,"BEGIN TRANSACTION");
			}
			for ($i = 0 ; $i < $qtde_os ; $i++) {
				$ativo             = trim($_POST['ativo_'. $i]);
				$os                = trim($_POST['os_' . $i]);
				$serie             = trim($_POST['serie_'. $i]);
				$serie_reoperado   = trim($_POST['serie_reoperado_'. $i]);
				$nota_fiscal_saida = trim($_POST['nota_fiscal_saida_'. $i]);
				$data_nf_saida     = trim($_POST['data_nf_saida_'. $i]);
				$motivo_fechamento = trim($_POST['motivo_fechamento_'. $i]);
				if($login_fabrica==1){
					$ativo_revenda             = trim($_POST['ativo_revenda_'. $i]);
				}


				//hd 24714
				if($ativo =='t'){
					$sql = "SELECT $xdata_fechamento < tbl_os.data_abertura FROM tbl_os where os=$os";
					$res = pg_exec($con,$sql);
					if (pg_result($res,0,0) == 't'){
						$msg_erro = "Data de fechamento não pode ser anterior a data de abertura.";
					}
				}

				if (strlen($msg_erro) == 0){
					if($login_fabrica == 3 AND $ativo == 't'){

						$sql = "SELECT tbl_os_item.os_item, tbl_os_item.pedido, tbl_os_item.peca, tbl_os_item.qtde
								FROM tbl_os_produto
								JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
								JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
								LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
								WHERE tbl_os_produto.os = $os
								AND tbl_servico_realizado.gera_pedido IS TRUE
								AND  tbl_faturamento_item.faturamento_item IS NULL
								LIMIT 1";
						$res = @pg_exec($con,$sql);

						$cancelado = "";

						//HD 6477
						if(pg_numrows($res)>0) {
							$xpedido = pg_result($res,0,pedido);
							$xpeca   = pg_result($res,0,peca);
							$xqtde   = pg_result($res,0,qtde);

							$sql = "SELECT os
									FROM tbl_pedido_cancelado
									WHERE pedido  = $xpedido
									AND   peca    = $xpeca
									AND   qtde    = $xqtde
									AND   os      = $os
									AND   fabrica = $login_fabrica";
							$res = pg_exec($con, $sql);

							if (pg_numrows($res) > 0) $cancelado = pg_result($res,0,0);
						}

						if(pg_numrows($res)>0 and strlen($cancelado)==0 and strlen($motivo_fechamento)==0){
							$erro .= "OS com peças pendentes, favor informar o motivo do fechamento<BR>";
							$xmotivo_fechamento = "null";
						}else{
							$xmotivo_fechamento = "'$motivo_fechamento'";
						}
					}else{
						$xmotivo_fechamento = "null";
					}

					$sql_data = "select to_char(current_date,'DD/MM/YYYY')";
					$res_data = pg_exec($con, $sql_data);
					$data_atual = pg_result($res_data,0,0);

					if ($login_fabrica == 45 and $xmotivo_fechamento <> 'null') {
						$xmotivo_fechamento .= " OS finalizada pelo admin $login_login em $data_atual";
					} elseif ($xmotivo_fechamento = "null") {
						$xmotivo_fechamento = "OS finalizada pelo admin $login_login em $data_atual";
					}

					if($login_fabrica==3 AND $posto==6359){// hd 16018
						$sql = "SELECT aprovado
								FROM tbl_os_atendimento_domicilio
								JOIN tbl_os USING(os)
								WHERE tbl_os.posto   = $posto
								AND   tbl_os.os      = $os";
						$res = pg_exec($con, $sql);
						//echo $sql;
						if(pg_numrows($res)>0){
							$aprovado = pg_result($res, 0, aprovado);
							if($aprovado=='f'){
								$erro = "OS com atendimento em domicilio, aguardando aprovação do fabricante.";
							}
						}
					}

					// Verifica se o status da OS for 62 (intervencao da fabrica) // Fábio 02/01/2007
					//Acrescentado $sua_os chamado= 2699 erro recebido no e-mail.
					if ( ($login_fabrica == 1 or $login_fabrica == 3 or $login_fabrica == 6  or $login_fabrica == 11 ) AND ($ativo == 't' or $ativo_revenda=='t')){
						$sql = "SELECT  status_os, sua_os, posto
								FROM    tbl_os_status
								JOIN tbl_os using(os)
								WHERE   os = $os
								AND tbl_os_status.status_os IN (72,73,62,64,65,87,88,116,117)
								ORDER BY tbl_os_status.data DESC
								LIMIT 1";
						$res = @pg_exec($con,$sql);
						if (pg_numrows($res) > 0) {
							$os_intervencao_fabrica = trim(pg_result($res,0,status_os));
							$sua_os                 = trim(pg_result($res,0,sua_os));
							$posto                  = trim(pg_result($res,0,posto));
							if ($login_fabrica==1){
								$sql2 =	"	SELECT codigo_posto
											FROM tbl_posto_fabrica
											WHERE posto = $posto
											AND fabrica = $login_fabrica";
								$res2 = @pg_exec($con,$sql2);
								if (pg_numrows($res2) > 0) {
									$cod_posto = trim(pg_result($res2,0,codigo_posto));
									$sua_os = $cod_posto.$sua_os;
								}
							}
							if ($os_intervencao_fabrica == '62' OR $os_intervencao_fabrica == '72' OR $os_intervencao_fabrica == '87' OR $os_intervencao_fabrica == '116') {
								$erro .= "OS $sua_os está em intervenção. Não pode ser fechada.";
							}
						}
					}

					if (strlen($data_nf_saida) == 0)
						$xdata_nf_saida = 'null';
					else
						$xdata_nf_saida    = fnc_formata_data_pg ($data_nf_saida) ;

					if (strlen($nota_fiscal_saida) == 0)
						$xnota_fiscal_saida = 'null';
					else
						$xnota_fiscal_saida = "'".$nota_fiscal_saida."'";

					if ($ativo == 't' or $ativo_revenda=='t'){
						$xserie_reoperado = "null";
						if($login_fabrica == 15){
							//7667 Gustavo 14/2/2008
							if (strlen($serie_reoperado) == 0)
								$xserie_reoperado = "null";
							else
								$xserie_reoperado = "'".$serie_reoperado."'";

							$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = $os ";
							$res = pg_exec($con,$sql);
							$con_rev = pg_result($res,0,consumidor_revenda);
							if($con_rev == 'R'){
								if($xnota_fiscal_saida == 'null'){
									$erro .= "Preencha o campo Nota Fiscal de Saída.";
								}
								if($xdata_nf_saida == 'null'){
									$erro .= " Preencha o campo Data da Nota Fiscal de Saída.";
								}
							}
						}

						$xserie= 'null';
						if($login_fabrica == 30){
							//11318 - Igor 15/2/2008
							if (strlen($serie) == 0){
								$erro .= "Preencha o Número de Série!";
							}else{
								$xserie= "'".$serie."'";
							}
						}

						//hd 6701 - nao deixar o posto 019876-IVO CARDOSO fechar sem lancar NF
						if($login_fabrica == 6 AND $posto == 4260){
							if($xnota_fiscal_saida == 'null' or strlen($xnota_fiscal_saida) == 0){
								$erro .= "Preencha o campo Nota Fiscal de Saída.";
							}
							if($xdata_nf_saida == 'null'){
								$erro .= " Preencha o campo Data da Nota Fiscal de Saída.";
							}
						}

						if ($login_fabrica == 1) {
							$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";

							$res = @pg_exec ($con,$sql);
							$erro .= pg_errormessage($con);
							# esta alteracao foi necessaria devido ao chamado 1419
							# Na verdade o valida os item deve ser realizado quando digitar o item, mas
							# quando a Fabiola/Silvania questionou sobre OS com item que não constavam na
							# lista básica, o Tulio começou a validar os itens no fechamento tambem.
							# começou a causar problemas com o Type, e substituição de peças.
							if (strpos ($erro,"na lista b") > 0 and strpos ($erro,"m o type est") > 0) $erro = '';
							if (strpos ($erro,"Referência") > 0 and strpos ($erro,"mudou para") > 0) $erro = '';
						}

						if (strlen ($erro) == 0) {
							// HD 27468
							if($login_fabrica <>1){
								$res = pg_exec ($con,"BEGIN TRANSACTION");
							}

							$upd_serie = "";
							if($login_fabrica == 30){
								$upd_serie = "serie = '$serie',";
							}

							if (strlen ($erro) == 0) {
								if ($login_fabrica==1){
									$sql = "UPDATE  tbl_os SET
													data_fechamento   = $xdata_fechamento
											WHERE   tbl_os.os         = $os";
								}else{
									$sql = "UPDATE  tbl_os SET
													data_fechamento   = $xdata_fechamento  ,
													$upd_serie
													serie_reoperado   = $xserie_reoperado   ,
													nota_fiscal_saida = $xnota_fiscal_saida,
													data_nf_saida     = $xdata_nf_saida
											WHERE   tbl_os.os         = $os";
								}
								//echo "$sql<BR>";

								$res  = @pg_exec ($con,$sql);
								$erro = pg_errormessage ($con);

								$sql = "UPDATE  tbl_os_extra SET
												obs_fechamento   = '$xmotivo_fechamento'
										WHERE   tbl_os_extra.os         = $os";
								$res  = @pg_exec ($con,$sql);
								$erro = pg_errormessage ($con);
							}

							if (strlen ($erro) == 0) {//HD14464 - Lembrar de tirar
								if($os == '4432808' OR $os == '4551521' OR $os == '4240719' OR $os == '4456040' OR $os == '4461314' OR $os == '4445295' OR $os == '4461997' OR $os == '4462135' OR $os == '4285485' OR $os == '4607692' OR $os == '4590143' OR $os == '4602655' OR $os == '4626403' OR $os == '4490396' OR $os == '4423530' OR $os == '4496698' OR $os == '4443820' OR $os == '4488199' OR $os == '4407731' OR $os == '4122317' OR $os == '4494669' OR $os == '4436836' OR $os == '4471521' OR $os == '4497613' OR $os == '4534621' OR $os == '4550614' OR $os == '4424900' OR $os == '4513872'){
									$sql = "SELECT fn_valida_os($os, $login_fabrica)";
									$res = @pg_exec ($con,$sql);
									$erro = pg_errormessage($con);
								}
							}

							if (strlen ($erro) == 0) {
								$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
								$res = @pg_exec ($con,$sql);
								$erro = pg_errormessage($con);
							}
							if (strlen ($erro) == 0 and ($login_fabrica==1 Or $login_fabrica==24)) {
								$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
								$res = @pg_exec ($con,$sql);
								$erro = pg_errormessage($con);
								//echo $sql;
							}

							//HD 11082 17347
							if(strlen($erro) ==0 and $login_fabrica==11 and $posto==14301){
								$observacao=$_POST['observacao_'.$i];
								$sql="INSERT INTO tbl_os_interacao (programa,os,comentario) values ('$programa_insert',$os,'$observacao')";
								$res=pg_exec($con,$sql);

								$sqlm="SELECT tbl_os.sua_os          ,
											 tbl_os.consumidor_email,
											 tbl_os.serie           ,
											 tbl_posto.nome         ,
											 tbl_produto.descricao  ,
											 to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
										from tbl_os
										join tbl_produto using(produto)
										join tbl_posto on tbl_os.posto = tbl_posto.posto
										where os=$os";
								$resm=pg_exec($con,$sqlm);
								$msg_erro .= pg_errormessage($con) ;

								$sua_osm           = trim(pg_result($resm,0,sua_os));
								$consumidor_emailm = trim(pg_result($resm,0,consumidor_email));
								$seriem            = trim(pg_result($resm,0,serie));
								$data_fechamentom  = trim(pg_result($resm,0,data_fechamento));
								$nomem             = trim(pg_result($resm,0,nome));
								$descricaom        = trim(pg_result($resm,0,descricao));

								if(strlen($consumidor_emailm) > 0){

									$nome         = "TELECONTROL";
									$email_from   = "suporte@telecontrol.com.br";
									$assunto      = "ORDEM DE SERVIÇO FECHADA";
									$destinatario = $consumidor_emailm;
									$boundary = "XYZ-" . date("dmYis") . "-ZYX";

									$mensagem = "A ORDEM DE SERVIÇO $sua_osm REFERENTE AO PRODUTO $descricaom COM NÚMERO DE SÉRIE $seriem FOI FECHADA PELO POSTO $nomem NO DIA $data_fechamentom.";

									$mensagem .= "<br>Observação do Posto: $observacao";


									$body_top = "--Message-Boundary\n";
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
									$body_top .= "Content-transfer-encoding: 7BIT\n";
									$body_top .= "Content-description: Mail message body\n\n";
									@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
								}
							}


							if (strlen ($erro) > 0) {
								//echo $erro;
								// HD 27468
								if($login_fabrica <> 1){
									$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
								}
								$linha_erro[$i] = 1;
								$msg_erro = $erro;
								$erro = '';
								break;
							}else{
								// HD 27468
								if($login_fabrica <> 1){
									$res = @pg_exec ($con,"COMMIT TRANSACTION");
								}
								//$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
								$data_fechamento   = "";
								$serie             = "";
								$serie_reoperado   = "";
								$nota_fiscal_saida = "";
								$data_nf_saida     = "";
								$msg_ok = 1;
								//$msg_ok = "<font size='2'><b>OS(s) fechada(s) com sucesso!!!</b></font>";
							}
						}
						else{
							$msg_erro = $erro;
						}
					}//fim if
				}
			}//for
			// HD 27468
			if ($login_fabrica ==1){
				if (strlen($msg_erro) >0 or strlen($erro) >0) {
					$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
				}else{
					$res = @pg_exec ($con,"COMMIT TRANSACTION");
				}
			}
		} // if msg_erro
	}//if
}
?>

<? include "javascript_pesquisas.php"; ?>

<script language="JavaScript">
var checkflag = "false";
var filtro_status = -1;
function SelecionaTodos(field) {

	if($(".main").is(":checked")){
		$("input[type=checkbox].os").attr('checked',false);
		if(filtro_status >= 0){
			$("tr[rel=status_"+filtro_status+"] input[type=checkbox].os").attr('checked','checked');
		}else{
			$("input[type=checkbox].os").attr("checked","checked");
		}
	}else{
		$("input[type=checkbox].os").attr("checked",false);
	}
}
</script>
 <link rel="stylesheet" href="js/jquery.tooltip.css" />
 <script src="js/jquery-1.3.2.js"></script>
 <script src="js/jquery.maskedinput.js"></script>
 <script src="js/jquery.tooltip.js"           type="text/javascript"></script>
 <script type="text/javascript" src="js/jquery.corner.js"></script>
 <script type="text/javascript">
 $(document).ready(function(){
   $(".tabela_resultado tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   //$(".tabela_resultado tr:even").addClass("alt");
   $(".tabela_resultado tr[@rel='sem_defeito']").addClass("sem_defeito");
   $(".tabela_resultado tr[@rel='mais_30']").addClass("mais_30");
   $(".tabela_resultado tr[@rel='erro_post']").addClass("erro_post");
   });

	$(document).ready(function(){
		$(".titulo").corner("round");
		$(".subtitulo").corner("round");
		$(".content").corner("dog 10px");

	});
	function formata_data(campo_data, form, campo){
	var mycnpj = '';
	mycnpj = mycnpj + campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}

}
function mostraDados(peca){
	if (document.getElementById('dados_'+peca)){
		var style2 = document.getElementById('dados_'+peca);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

 </script>
 <script type="text/javascript" src="js/niftycube.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[@rel='data_conserto']").maskedinput("99/99/9999 99:99");
		$("input[@rel='data']").maskedinput("99/99/9999");
	});
</script>

<script type="text/javascript">

//HD 234532
function filtrar(status){

	if(status >= 0){

		$("table.tabela_resultado tbody tr").hide();
		$("tr[rel=status_"+status+"]").show();

		if($(".main").is(":checked")){
			$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked',false);
			$("tr[rel=status_"+status+"] > td > input[type=checkbox]").attr('checked','checked');
		}

	}else{

		$("table.tabela_resultado tbody tr").show();

		if($(".main").is(":checked")){
			$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked','checked');
		}
	}

	filtro_status = status;

}

function gravar_obs(os){ //HD-2389192
	var obs_os = "";

	var os_id = os;
	var obs_os = $("input[name^=obs_fechamento_os_"+os+"]").val();
	$.ajax({
		async: false,
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		dataType: "JSON",
		data: { btn_acao: "gravar_obs_os",
            os_id: os,
            obs_os: obs_os
    },
		complete: function (data) {
      var retorno = data.responseText;

      if(retorno == "ok") {
        alert("Observação Gravada com Sucesso");
      }else{
      	alert(retorno);
      }

    }

	});
}


$().ready(function() {
	$("input[@rel='data_conserto']").blur(function(){
		var campo = $(this);


			$.post('<? echo $PHP_SELF; ?>',
				{
					gravarDataconserto : campo.val(),
					os: campo.attr("alt")
				},

				//24714
				function(resposta){
					if (resposta.length > 0){
						alert(resposta);
						campo.val('');
					}
				}
			);

	});
});

</script>


<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>
<style type="text/css">

	table.sample {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}

	table.sample th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	table.sample td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.sample tr.alt td {
		background: #ecf6fc;
	}

	table.sample tr.over td {
		background: #bcd4ec;
	}
	table.sample tr.clicado td {
		background: #FF9933;
	}
	table.sample tr.sem_defeito td {
		background: #FFCC66;
	}
	table.sample tr.mais_30 td {
		background: #FF0000;
	}
	table.sample tr.erro_post td {
		background: #99FFFF;
	}

.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 4px 4px; /* padding greater than corner height|width */
/*	margin: 1em 0.25em;*/
	font-size:12px;
	color:#FFFFFF;
}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 10px auto;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}

.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px 30px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.content h1 {
	color:black;
	font-size: 120%;
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.fechamento{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #C50A0A;
}
.fechamento_content{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	color: #FFFFFF;
	background-color: #F9DBD0;
}




	.Relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}

	.Relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	.Relatorio td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}







</style>

<?
	if($sistema_lingua ) $msg_erro = traducao_erro($msg_erro,$sistema_lingua);
if (strlen ($msg_erro) > 0) {
	//echo $msg_erro;
	if (strpos ($msg_erro,"Bad date external ") > 0) $msg_erro = "Data de fechamento inválida";
	if (strpos ($msg_erro,'"tbl_os" violates check constraint "data_fechamento"') > 0) $msg_erro = "Data de fechamento inválida";
	if (strpos ($msg_erro,"É necessário informar a solução na OS") > 0) $msg_solucao = 1;
	if (strpos ($msg_erro,"Para esta solução é necessário informar as peças trocadas") > 0) $msg_solucao = 1;
	// retira palavra ERROR:
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
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
<tr>
	<td height="27" valign="middle" align="center" class='error'>
		<?
		echo $msg_erro;
		?>
	</td>
</tr>
</table>
<br>
<? } ?>

<? if (strlen ($msg_ok) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#FFCC66">
<tr>
	<td height="27" valign="middle" align="center">
	<?	if ($sistema_lingua=='ES') {
			echo "<font size='2'><b>OS(s) cerrada(s) con exito!!!</b></font>";
		} else {
			echo "<font size='2'><b>OS(s) fechada(s) com sucesso!!!</b></font>";
		} ?>
	</td>
</tr>
</table>
<? } ?>

<br>

<?
if(strlen($msg_erro) > 0){

	echo "<BR>";
	echo "<div align='left' style='position: relative; left: 10'>";
	echo "<table width='700' height=15 border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<tr>";
	echo "<td align='center' width='15' bgcolor='#FF0000'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; ERRO NA OS</b></font></td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
	echo "<br>";
}

$sua_os       = trim($_POST['sua_os']);
$codigo_posto = $_POST['codigo_posto'];
if(strlen($sua_os ) == 0 AND $login_fabrica == 15){
	$sua_os       = trim($_GET['sua_os']);
}

?>

<table width="700" border="0" cellpadding="2" cellspacing="0" align="center">
<form name='frm_os_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<table width="400" align="center" border="0" cellspacing="2" cellpadding="2" bgcolor='#D9E2EF'>
	<tr class="Titulo" height="30">
		<td align="center" colspan='2'>
		<?
		if($sistema_lingua == 'ES') echo "Elija los parámetros para la consulta";
		else                        echo "Selecione os parâmetros para a pesquisa.";
		?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='left'><b><? if($sistema_lingua == 'ES') echo "Número da OS: ";else echo "Número da OS: ";?></b>
		<input type='text' name='sua_os' size='10' value='<? echo $sua_os ?>' class="frm"></td><td></td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Posto</td>
		<td>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td nowrap>
			<input type="text" name="codigo_posto" id="codigo_posto" size="8" onblur="javascript: fnc_pesquisa_posto (document.frm_os_pesquisa.codigo_posto_off, document.frm_os_pesquisa.posto_nome, 'codigo');" value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_os_pesquisa.codigo_posto, document.frm_os_pesquisa.posto_nome, 'codigo')">
		</td>
		<td nowrap>
			<input type="text" name="posto_nome" id="posto_nome" size="30" onblur="javascript: fnc_pesquisa_posto (document.frm_os_pesquisa.codigo_posto, document.frm_os_pesquisa.posto_nome, 'nome');" value="<?echo $posto_nome ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_os_pesquisa.codigo_posto, document.frm_os_pesquisa.posto_nome, 'nome')">
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center' colspan='2'><img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_os_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_os_pesquisa.submit() } else { alert ('Aguarde submissão') }"  border='0' style='cursor: pointer' colspan='2'></td>
	</tr>
</table>
<input type='hidden' name='btn_acao_pesquisa' value=''>


</form>
</table>

<?
$btn_acao_pesquisa = trim($_POST['btn_acao_pesquisa']);
$sua_os            = trim($_POST['sua_os']);
$codigo_posto      = trim($_POST['codigo_posto']);

if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = trim($_GET['btn_acao_pesquisa']);
if (strlen($_GET['sua_os']) > 0)            $sua_os            = trim($_GET['sua_os'])           ;
if (strlen($_GET['codigo_posto']) > 0)      $codigo_posto      = trim($_GET['codigo_posto'])     ;

if (strlen ($codigo_posto) > 0) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
} else {
	$msg_erro = "Informe o código do posto para pesquisar.";
}

if (usaDataConserto($posto, $login_fabrica)) {
	$sql_data_conserto=", to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' )	as data_conserto ";
}

if($login_fabrica==11 and $posto==14301){
	$sql_obs=" , tbl_os.consumidor_email ";
}

if ((strlen($sua_os) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen ($codigo_posto) > 0 AND $btn_acao_pesquisa == 'continuar' ) {

		if($login_fabrica == 3 or $posto == '4311'){
			$sql_add1 = "
				,(
					SELECT   OI.os_item
					FROM      tbl_os_produto        OP
					JOIN      tbl_os_item           OI ON OP.os_produto        = OI.os_produto
					JOIN      tbl_servico_realizado SR ON OI.servico_realizado = SR.servico_realizado
					LEFT JOIN tbl_faturamento_item  FI ON OI.peca              = FI.peca              AND OI.pedido = FI.pedido
					WHERE OP.os = tbl_os.os
					AND   SR.gera_pedido      IS TRUE
					AND   FI.faturamento_item IS NULL
					LIMIT 1
				) as os_item ";
		}
		if($posto == '4311'  or $posto == '6359' or $posto == '14301') {
			$sql_add2 =", tbl_os.prateleira_box ";
		}

		if($login_fabrica == 19) $sql_adiciona .= " AND tbl_os.consumidor_revenda = 'C' ";

		if($login_fabrica == '11' and $posto == '14301' or $posto == '6359'){
			if (strlen ($prateleira_box) > 0) {
				$sql_adiciona .= " AND tbl_os.prateleira_box = '$prateleira_box'";
			}
		}
		if ( strlen ($posto) > 0) {
			$sql_adiciona .= " AND tbl_os.posto = $posto ";
		}

		if( $login_fabrica == 24 ){
			$sql_adiciona .= " AND tbl_os_campo_extra.os_bloqueada != true AND tbl_os.admin_excluida IS NOT NULL";
			$join_os_campo_extra = "INNER JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = 24";
		}

		//HD 18229
		if (strlen($sua_os) > 0) {
			if ($login_fabrica == 1) {
				$pos = strpos($sua_os, "-");
				if ($pos === false) {
					$pos = strlen($sua_os) - 5;
				}else{
					$pos = $pos - 5;
				}
				$sua_os = substr($sua_os, $pos,strlen($sua_os));
			}
			$sua_os = strtoupper ($sua_os);

			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				if(!ctype_digit($sua_os)){
					$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					// 03/05/2018 - Lucas Bicalleto (Não estava retornando registros)
					if (in_array($login_fabrica, array(173)))
					{
						$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
					}else
					{
						$sql_adiciona .= " AND tbl_os.os_numero = '$sua_os' ";
					}
				}
			}else{
				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];
				if(!ctype_digit($os_sequencia)){
					$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					if($login_fabrica <>1){
						$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
					}else{
						//HD 9013 24484
						$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' ";
					}

				}
			}
		}
		if($login_fabrica==11 and $posto==6359 or $posto==14301){
			$sql_order .= "ORDER BY tbl_os.data_abertura ASC ;";
		}else if($login_fabrica==1 and $posto==6359){
			$sql_order .= "ORDER BY tbl_os.consumidor_revenda asc,lpad(tbl_os.sua_os::text,20,'0') DESC, lpad(tbl_os.os::text,20,'0') DESC ;";
		}else{
			$sql_order .= "ORDER BY lpad(tbl_os.sua_os,20,'0') DESC, lpad(tbl_os.os::text,20,'0') DESC ;";
		}

		$sql = "SELECT  tbl_os.os                                                  ,
						tbl_os.sua_os                                              ,
						tbl_os.serie                                               ,
							tbl_os.status_checkpoint                                               ,
						tbl_produto.produto                                        ,
						tbl_produto.descricao                                      ,
						tbl_produto.nome_comercial                                 ,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						tbl_os.consumidor_nome                                     ,
						tbl_os.consumidor_revenda                                  ,
						tbl_os.defeito_constatado                                  ,
						tbl_os.admin                                               ,
						tbl_os.tipo_atendimento
						$sql_add1
						$sql_add2
						$sql_data_conserto
						$sql_obs
				FROM    tbl_os
				JOIN    tbl_produto            USING (produto)
				JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				{$join_os_campo_extra}
				WHERE   tbl_os.fabrica = $login_fabrica
				AND     tbl_os.data_fechamento IS NULL
				AND    (tbl_os.excluida        IS NULL OR tbl_os.excluida IS FALSE )
				$sql_adiciona
				$sql_order";

				//exit( nl2br($sql) );
		
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage ($con);
		if (pg_numrows($res) > 0){

			echo "<div id='layout'>";
			echo "<div class='subtitulo'>";

			if($sistema_lingua == "ES") echo "Al cerrar la OS queda a disposición para recibir el importe correspondiente";
			else                        echo "Com o fechamento da OS você se habilita ao recebimento dos valores <br> que serão pagos no próximo Extrato.";

			echo "</div>";
			echo "</div>";

			echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
			echo "<tr>";
			echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
			echo "<td valign='top' align='center'>";

			if ($login_fabrica == 1){
				echo "<table width='700' border='0' cellspacing='2' cellpadding='0' align='center'>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, favor informar o \"Motivo\"</font></td>";
				echo "</tr>";
				echo "<tr height='4'><td colspan='2'></td></tr>";
				echo "<tr>";
				echo "<td align='center' width='18' height='18' bgcolor='#FFCC66'>&nbsp;</td>";
				echo "<td align='left'><font size=1>&nbsp; OSs sem defeito constatado</font></td>";
				echo "</tr>";
				if (strlen($msg_solucao) > 0){
					echo "<tr height='4'><td colspan='2'></td></tr>";
					echo "<tr>";
					echo "<td align='center' width='18' height='18' bgcolor='#99FFFF'>&nbsp;</td>";
					echo "<td align='left'><font size=1>&nbsp; OSs sem solução e sem itens lançados.</font></td>";
					echo "</tr>";
				}
				echo "</table>";
			}


//HD 4291 PAULO  HD 14121

		##### LEGENDAS - INÍCIO - HD 234532 #####
			/*
			0 | Aberta Call-Center               | #D6D6D6
			1 | Aguardando Analise               | #FF8282
			2 | Aguardando Peças                 | #FAFF73
			3 | Aguardando Conserto              | #EF5CFF
			4 | Aguardando Retirada              | #9E8FFF
			9 | Finalizada                       | #8DFF70
			*/

			#Se for Bosh Security modificar a condição para pegar outros status também.
			if ($login_fabrica == 96) {
				$condicao_status = '0,1,2,3,5,6,7';
			} else if ($login_fabrica == 1) {//HD 424292
				$condicao_status = '1,2,3,4';
			} else {
				$condicao_status = '0,1,2,3,4';
			}

			$sql_status = "SELECT status_checkpoint,descricao,cor FROm tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.")";
			$res_status = pg_query($con,$sql_status);
			$total_status = pg_num_rows($res_status);

			?>
			<style>
			.status_checkpoint{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
			.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;}
			</style>
			<div align='left' style='position: relative; left: 10'>
				<br>
				<table border='0' cellspacing='0' cellpadding='0'>
				<?php
				for($i=0;$i<$total_status;$i++){

					$id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
					$cor_status = pg_fetch_result($res_status,$i,'cor');
					$descricao_status = pg_fetch_result($res_status,$i,'descricao');

					#Array utilizado posteriormente para definir as cores dos status
					$array_cor_status[$id_status] = $cor_status;
					?>

					<tr height='18'>
						<td width='18' >
							<span class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</span>
						</td>
						<td align='left'>
							<font size='1'>
								<b>
									<a href="javascript:void(0)" onclick="filtrar(<?php echo $id_status;?>);">
										<?php echo $descricao_status;?>
									</a>
								</b>
							</font>
						</td>
					</tr>
				<?php }?>
				<tr height='18'>
					<td width='18' >
						<span class="status_checkpoint">&nbsp;</span>
					</td>
					<td align='left'>
						<font size='1'>
							<b>
								<a href="javascript:void(0)" onclick="filtrar(-1);">
									Listar Todos
								</a>
							</b>
						</font>
					</td>
				</tr>

				</table>
			</div>

		<?if($login_fabrica==11){
			//HD 13239
			$data_fechamento=date("d/m/Y");
		}


		?>

		<!-- ------------- Formulário ----------------- -->

		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type='hidden' name='qtde_os' value='<? echo pg_numrows ($res); ?>'>

		<input type='hidden' name='btn_acao_pesquisa' value='<? echo $btn_acao_pesquisa ?>'>
		<TABLE width="650" border="0" cellpadding="2" cellspacing="0" align="center">
		<tr>
		<TD width='120' class="fechamento"><b><?if($sistema_lingua == 'ES') echo "Cerrar Cierre";else echo "Data de Fechamento";?></TD>
		<TD nowrap  width='530' class="fechamento_content">&nbsp;&nbsp;&nbsp;&nbsp;
		<input class="frm" type='text' name='data_fechamento' rel='data' size='12' maxlength='10' value='<? echo $data_fechamento ?>' <?if($login_fabrica==11){
			echo "readonly='readonly'";
		}?> >
		</TD>
		</TR>
		</TABLE>
		<table width="650" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado Relatorio'>
		<!-- class='tabela_resultado sample'-->
		<?		//HD 9013
			if($login_fabrica==1){?>
		<caption colspan='100%' style='font-family: verdana; font-size: 20px'>OS de Consumidor</font><caption>
		<?}?>
		<thead>
		<tr height="20">
			<th nowrap>
				<input type='checkbox' class='frm main' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this.form.ativo);' style='cursor: hand;'>
			</th>
			<th nowrap><b>OS <?if($login_fabrica<>20){?>Fabricante<? } ?></b></th>
			<? //HD 23623 ?>
			<? if ($login_fabrica == 11 and $posto==14301){ ?>
				<th nowrap><b>Box/Prateleira </b></th>
			<?}?>
			<th nowrap><b><?if($sistema_lingua == 'ES') echo "Fecha Abertura";else echo "Data Abertura";?></b></th>

			<th nowrap><b><? if($sistema_lingua == 'ES') echo "Usuário";else echo "Consumidor";?></b></th>
			<th nowrap><b><? if($sistema_lingua == 'ES') echo "Producto";else echo "Produto";?></b></th>
			<? if ($login_fabrica == 15){ ?><th nowrap><b>N. Série Reoperado</b></th><?}?>
			<? if ($login_fabrica == 30){ ?><th nowrap><b>N. Série </b></th><?}?>
			<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
				<th nowrap><b>NF de Saída</b></th>
				<th nowrap><b>Data NF de Saída</b></th>
			<? }
				 if($login_fabrica == 6){ //HD-2389192
			?>
					<th nowrap colspan="2"><b>Observação</b></th>
			<?php
				 }

			?>
			<?if($login_fabrica==20){?>
				<th nowrap><b><? if($sistema_lingua=='ES')echo "Valor de Piezas";else echo "Valor das Peças";?>
				</b></th>
				<th nowrap><b><? if($sistema_lingua=='ES')echo "Mano de Obra";else echo "Mão-de-Obra";?>
				</b></th>
			<? } ?>
			<?

			if($posto=='4311' or $posto == '6359' ) {
				echo "<th nowrap><b> Box </b></th>";
			}
			//HD 12521       HD 13239    HD 14121
			if($login_fabrica == 3 or $posto == '4311' or (($login_fabrica <> 11 and $login_fabrica<>1) and $posto== 6359) or $login_fabrica ==15 or $login_fabrica ==45 ){
				echo "<th nowrap><b> Data de conserto </b></th>";
			} ?>
		</tr>
</thead>
<tbody>
<?
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$flag_cor = "";
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 //HD 9013
			 if(($consumidor_revenda=='C' and $login_fabrica==1) or ($login_fabrica<>1)){
			$os               = trim(pg_result ($res,$i,os));
			$sua_os           = trim(pg_result ($res,$i,sua_os));
			$admin            = trim(pg_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_result ($res,$i,tipo_atendimento));
			$produto          = trim(pg_result ($res,$i,produto));
			$status_checkpoint          = trim(pg_result ($res,$i,'status_checkpoint'));
			//HD 12521
			if($login_fabrica == 3 or $posto == '4311'){
				$os_item          = trim(pg_result ($res,$i,os_item));

			}
			//HD 13239
			if (usaDataConserto($posto, $login_fabrica)) {
				$data_conserto           = trim(pg_result ($res,$i,data_conserto));
			}
			//HD 4291 Paulo --- HD 23623 - acrescentado 14301
			if($posto=='4311' or $posto == 6359 or $posto==14301) {
				$prateleira_box          = trim(pg_result ($res,$i,prateleira_box));
			}
			if($login_fabrica==11 and $posto==14301){
				$consumidor_email        = trim(pg_result ($res,$i,consumidor_email));

			}
//			$leftpad = trim(pg_result ($res,$i,leftpad));
//if ($ip == '201.0.9.216') { echo $leftpad; }
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;


			 $defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ================================================


			if ($login_fabrica == 1) {
				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_exec($con, $sql);
				if (pg_numrows($resY) > 0) {
					$cor = "#FF0000";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

#				$resX = pg_exec($con,"SELECT to_char (current_date , 'YYYY-MM-DD')");
#				$data_atual = pg_result($resX,0,0);

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}
			}
			//HD 4291 Paulo verificar a peça pendente da os e mudar cor
			// HD 14121
			if($posto=='4311' or $posto=='6359' or $login_fabrica==15 or $login_fabrica ==45) {
				$bolinha="";

				$sqlcor="SELECT *
							FROM tbl_os
							WHERE defeito_constatado is null
							AND	  solucao_os is null
							AND	  os=$os";
				$rescor=pg_exec($con,$sqlcor);
				if(pg_numrows($rescor) > 0) {
					$bolinha="vermelho";
				} else {

					$sqlcor2 = "SELECT	tbl_os_item.pedido   ,
										tbl_os_item.peca                      ,
										tbl_pedido.distribuidor             ,
										tbl_os_item.faturamento_item       ";
					if(strlen($os_item)==0){
						$sqlcor2 .=", tbl_os_item.os_item ";
					}
					$sqlcor2 .=	"FROM    tbl_os_produto
								JOIN    tbl_os_item USING (os_produto)
								JOIN    tbl_produto USING (produto)
								JOIN    tbl_peca    USING (peca)
								LEFT JOIN tbl_defeito USING (defeito)
								LEFT JOIN tbl_servico_realizado USING (servico_realizado)
								LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
								LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
								LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
								LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
								WHERE   tbl_os_produto.os = $os";

					$rescor2 = pg_exec($con,$sqlcor2);

					if(pg_numrows($rescor2) > 0) {

						for ($j = 0 ; $j < pg_numrows ($rescor2) ; $j++) {
							$pedido               = trim(pg_result($rescor2,$j,pedido));
							$peca                 = trim(pg_result($rescor2,$j,peca));
							$distribuidor         = trim(pg_result($rescor2,$j,distribuidor));
							$faturamento_item     = trim(pg_result($rescor2,$j,faturamento_item));
							if(strlen($os_item) ==0)$os_item              = trim(pg_result($rescor2,$j,os_item));
							$bolinha="";
							if ($login_fabrica == 3) {
								if (strlen($pedido) > 0) {
										$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento_item.pedido  = $pedido
												AND     tbl_faturamento_item.peca    = $peca";
										if($distribuidor=='4311'){
											$sql .=" AND     tbl_faturamento_item.os_item = $os_item
													 AND     tbl_faturamento.posto        = $posto
													 AND     tbl_faturamento.distribuidor = 4311";
										}elseif(strlen($distribuidor)>0 ){
											$sql .=" tbl_faturamento.posto = $distribuidor ";
										}else{
											$sql .=" AND     (length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os = $os)
													 AND     tbl_faturamento.posto       = $posto";
											//hd 22576
											if($posto =='4311'){
												$sql .=" AND tbl_faturamento.nota_fiscal IS NOT NULL
														 AND tbl_faturamento.emissao IS NOT NULL ";
											}
										}
										$resx = pg_exec ($con,$sql);
										if (pg_numrows ($resx) == 0) {
											$bolinha="amarelo";
										}elseif ($posto =='4311' and pg_numrows($resx) >0) {
											//hd 22576
											$bolinha="amarelo";
										}


									$sql="SELECT count(os_item) as conta_item,
												 os as conta_os
											FROM tbl_os_produto
											JOIN tbl_os_item using(os_produto)
											WHERE os=$os
											GROUP BY os";
									$resX = pg_exec ($con,$sql);
									if (pg_numrows ($resX) > 0) {
										$conta_item=pg_result($resX,0,conta_item);
										$conta_os  =pg_result($resX,0,conta_os);
										if(strlen($conta_item) > 0){
											$sql = "SELECT	count(embarcado) as embarcado
													FROM tbl_embarque_item
													JOIN tbl_os_item ON tbl_os_item.os_item = tbl_embarque_item.os_item
													JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
													WHERE tbl_os_item.os_item in (SELECT os_item FROM tbl_os_produto JOIN tbl_os_item using(os_produto) WHERE os=$conta_os)	";
											$resX = pg_exec ($con,$sql);
											if (pg_numrows ($resX) > 0) {
												$embarcado      = trim(pg_result($resX,0,embarcado));
											}
											if($embarcado==$conta_item ){
												$bolinha="rosa";
											}
										}
									}
								}else {
									$bolinha="amarelo";
								}
							}elseif($login_fabrica==11){
								if (strlen($faturamento_item)>0){
									$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento.fabrica=$login_fabrica
												AND     tbl_faturamento_item.faturamento_item = $faturamento_item";

									$resx = pg_exec ($con,$sql);

									if (pg_numrows ($resx) == 0) {
										$bolinha="amarelo";
									}else {
										$nota_fiscal=pg_result($resx,0,nota_fiscal);
										if(strlen($nota_fiscal) > 0){
											$bolinha="rosa";
										}
									}
								}else{
									if (strlen($pedido) > 0) {
										$bolinha="amarelo";
									}
								}
							} else {
								if (strlen ($nota_fiscal) == 0) {
									if (strlen($pedido) > 0) {
										$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento.pedido    = $pedido
												AND     tbl_faturamento_item.peca = $peca;";
										$resx = pg_exec ($con,$sql);

										if (pg_numrows ($resx) == 0) {
											$condicao_01 = " 1=1 ";
											if (strlen ($distribuidor) > 0) {
												$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
											}
											$sql  = "SELECT *
													FROM    tbl_faturamento
													JOIN    tbl_faturamento_item USING (faturamento)
													WHERE   tbl_faturamento_item.pedido = $pedido
													AND     tbl_faturamento_item.peca   = $peca
													AND     $condicao_01 ";
											$resx = pg_exec ($con,$sql);

											if (pg_numrows ($resx) == 0) {
												if ($login_fabrica==1){
													$sql  = "SELECT *
																FROM    tbl_pendencia_bd_novo_nf
																WHERE   posto        = $posto
																AND     pedido_banco = $pedido
																AND     peca         = $peca";
													$resx = pg_exec ($con,$sql);

													if (pg_numrows ($resx) > 0) {
														$bolinha="amarelo";
													}
												}else{
													$bolinha="amarelo";
												}
											}else{
													$bolinha="rosa";
											}
										}
									}
								}
							}
						}
					}
				}

				if(strlen($data_conserto) > 0) {
					$bolinha="azul";
				}
			}
//HD 4291 Fim
			if (strlen($linha_erro[$i]) > 0) $cor = "#FF0000";

?>

		<tr bgcolor=<?=$cor;echo " rel='status_$status_checkpoint' ";?> >
			<td align="center">
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_result ($res,$i,os) ?>' >
			<?if($login_fabrica==1){?>
				<input type='hidden' name='conta_<? echo $i ?>' value='<? echo $i;?>'>
				<input type='hidden' name='consumidor_revenda_<? echo $i ?>' value='<? echo pg_result ($res,$i,consumidor_revenda)?>'>
			<?}?>
			<? if($login_fabrica == 3 and strlen($os_item)>0){?>
				<input type='hidden' name='os_item_<? echo $i ?>' value='<? echo "$os_item"; ?>'>
			<?}?>
			<? if (strlen($flag_bloqueio) == 0) { ?><input type="checkbox" class="frm os" name="ativo_<?echo $i?>" id="ativo" value="t" <?
					if($login_fabrica==3){	?>
						onClick='javascript:mostraDados(<?echo $i; ?>);'
					<? } ?>><?
			} ?></td>

			<?
			//HD 4291 Paulo
			if (($posto == '4311' or $posto == '6359' or $login_fabrica == 15 or $login_fabrica == 45) and strlen($bolinha) > 0) {

				//HD 234532
				if(strlen($status_checkpoint)> 0 ) {
					$cor_status_os = '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
				} else {
					$cor_status_os = '<span class="status_checkpoint_sem">&nbsp;</span>';
				}
			} else {
				$cor_status_os = '<span class="status_checkpoint_sem">&nbsp;</span>';
			}
			//Fim
			?>

	<td><?php echo $cor_status_os;?><a href='<? if ($cor == "#FFCC66" ) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $codigo_posto; echo $sua_os; ?></a></td>
			<? //HD 23623 ?>
			<? if($login_fabrica == 11 and $posto == 14301) echo "<td>$prateleira_box</td>"; ?>
			<td><? echo pg_result ($res,$i,data_abertura) ?></td>
			<td NOWRAP ><? echo substr (pg_result ($res,$i,consumidor_nome),0,10) ?></td>
			<? if($login_fabrica == 30){
			 $serie = pg_result ($res,$i,serie);
			?>
			<td NOWRAP><? echo substr ($descricao,0,15); ?></td>
			<? }else{ ?>
			<td NOWRAP><? echo pg_result ($res,$i,serie) . " - " . substr ($descricao,0,15) ?></td>
			<? } ?>
<? if ($login_fabrica <> 2 AND $login_fabrica <> 1 AND $login_fabrica<>20){ ?>
			<?
			# Lorenzetti - Quando OS aberta pelo SAC para atendimento em Domicilio, obrigatorio NF de Devolucao
			if ($consumidor_revenda == 'R' OR (1==2 AND $login_fabrica == 19 AND strlen ($admin) > 0 AND $tipo_atendimento == 2) ){
				if($login_fabrica == 15){
					echo "<td><input class='frm' type='text' name='serie_reoperado_$i' size='15' maxlength='20' value='$serie_reoperado'></td>";
				}
				if($login_fabrica == 30 and strlen($serie)==0){
					echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='20' value='$serie'></td>";
				}else if(strlen($serie)>0){
						echo "<td>$serie";
						echo "<input type='hidden' name='serie_$i' value='$serie'>";
						echo "</td>";
				}
				echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'></td>";
				echo "<td><input class='frm' type='text' name='data_nf_saida_$i' rel='data' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'></td>";

			}else{
				if($login_fabrica == 30 and strlen($serie)==0){
					echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='20' value='$serie'></td>";
				}else if(strlen($serie)>0){
					echo "<td>$serie";
					echo "<input type='hidden' name='serie_$i' value='$serie'>";
					echo "</td>";
				}

				echo "<td>&nbsp;</td>";
				echo "<td>&nbsp;</td>";
			}

			if($login_fabrica == 6){ // HD-2389192

				echo "<td>";
					echo "<input type='text' class='frm' size='15' name='obs_fechamento_os_$os' value=''>";
				echo "</td>";
				echo "<td>";
						echo "<input type='button' value='Gravar' rel='$os' onclick='javascript:gravar_obs(\"$os\");'>";
				echo "</td>";
			}

			?>
<? } ?>
<?
if ($login_fabrica == "20") {

    $pecas              = 0;
    $mao_de_obra        = 0;
    $tabela             = 0;
    $desconto           = 0;
    $desconto_acessorio = 0;

    $ysql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
    $yres = pg_exec ($con,$ysql);
    if (pg_numrows ($yres) == 1) {
        $mao_de_obra = pg_result ($yres,0,mao_de_obra);
    }

    $ysql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
    $yres = pg_exec ($con,$ysql);

    if (pg_numrows ($yres) == 1) {

        $tabela             = pg_result ($yres,0,tabela)            ;
        $desconto           = pg_result ($yres,0,desconto)          ;
        $desconto_acessorio = pg_result ($yres,0,desconto_acessorio);

    }
    if (strlen ($desconto) == 0) $desconto = "0";

    if (strlen ($tabela) > 0) {

        $ysql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
                FROM tbl_os
                JOIN tbl_os_produto USING (os)
                JOIN tbl_os_item    USING (os_produto)
                JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
                WHERE tbl_os.os = $os";
        $yres = pg_exec ($con,$ysql);

        if (pg_numrows ($yres) == 1) {
            $pecas = pg_result ($yres,0,0);
        }
    }else{
        $pecas = "0";
    }

    $valor_liquido = 0;

    if ($desconto > 0 and $pecas <> 0) {

        $ysql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
        $yres = pg_exec ($con,$ysql);
        if (pg_numrows ($res) == 1) {
            $produto = pg_result ($yres,0,0);
        }
        //echo 'peca'.$pecas;
        if( $produto == '20567' ){
            $desconto_acessorio = '0.2238';
            $valor_desconto = round ( (round ($pecas,2) * $desconto_acessorio ) ,2);

        }else{
            $valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
        }

        $valor_liquido = $pecas - $valor_desconto ;

    }
    $acrescimo = 0;
	if($login_pais<>"BR"){
		$ysql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$yres = pg_exec ($con,$ysql);

		if (pg_numrows ($yres) == 1) {
			$valor_liquido = pg_result ($yres,0,pecas);
			$mao_de_obra   = pg_result ($yres,0,mao_de_obra);
		}
		$ysql = "select imposto_al  from tbl_posto_fabrica where posto=$posto and fabrica=$login_fabrica";
		$yres = pg_exec ($con,$ysql);

		if (pg_numrows ($yres) == 1) {
			$imposto_al   = pg_result ($yres,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
	}


    $total = $valor_liquido + $mao_de_obra + $acrescimo;

    $total          = number_format ($total,2,",",".")         ;
    $mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
    $acrescimo      = number_format ($acrescimo ,2,",",".")    ;
    $valor_desconto = number_format ($valor_desconto,2,",",".");
    $valor_liquido  = number_format ($valor_liquido ,2,",",".");

//	$data_conserto = "";

    echo "<td align='center'>" ;
    echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$valor_liquido</font>" ;
    echo "</td>";
    echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$mao_de_obra</font></td>";

}

if($posto=='4311' or $posto == '6359') {
	echo "<td align='center'>$prateleira_box</td>";
}
//HD 12521 //HD13239 hd 14121
if (usaDataConserto($posto, $login_fabrica)) {
	echo "<td align='center'>";
		if($login_fabrica == 3 AND strlen($data_conserto)>0){
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' disabled>";
		}else{
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto'>";
		}
	echo "</td>";
	}


?>
		</tr>
<? if($login_fabrica == 3 and strlen($os_item)>0){?>
		<?  //HD 6477
		$sqlp = "SELECT peca, pedido, qtde FROM tbl_os_item WHERE os_item = $os_item;";
		$resp = pg_exec($con, $sqlp);

		if (pg_numrows($resp) > 0) {
			$pendente = "f";

			$pedido = pg_result($resp,0,pedido);
			$peca   = pg_result($resp,0,peca);
			$qtde   = pg_result($resp,0,qtde);

			if (strlen($pedido) > 0) {
				$sqlp = "SELECT os
						FROM tbl_pedido_cancelado
						WHERE pedido  = $pedido
						AND   peca    = $peca
						AND   qtde    = $qtde
						AND   os      = $os
						AND   fabrica = $login_fabrica";
				$resp = pg_exec($con, $sqlp);

				if (pg_numrows($resp) == 0) $pendente = "t";
			} else {
				$pendente = "t";
			}


			if ($pentende = "t") {?>
				<TR>
					<td colspan='7'>
						<div id='dados_<? echo $i; ?>' style='position:relative; display:none; border: 1px solid #FF6666;background-color: #FFCC99;width:100%; font-size:9px'>Esta OS que você está fechando tem peças <strong>pendentes</strong>! Motivo do Fechamento:
							<input class='frm' type='text' name='motivo_fechamento_<?echo$i;?>' size='30' maxlength='100' value=''>
						</div>
					</td>
				</tr>
			<?}
		}
}?>
<?if($login_fabrica==11 and $posto==14301 and strlen($consumidor_email) >0){ ?>
<TR bgcolor="<?echo $cor;?>"><td colspan="100%">Observação:  <input type="text" name="observacao_<?echo $i;?>" size="100" maxlength="200" value="" title="Esta informação será inserido na interação da OS e mandado junto com o email"></td></TR>
<?
}
			 }
}?>

</tbody>
		</table>
<br><br>

<?//HD 9013
	if($login_fabrica=='1'){ ?>
		<table width="650" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado sample'>
		<caption colspan='100%' style='font-family: verdana; font-size: 20px'>OS de Revenda</font><caption>
		<thead>
		<tr height="20">
			<th>
			<input type='checkbox' class='frm main' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='SelecionaTodos(this.form.ativo_revenda);' style='cursor: hand;'>
			</th>
			<th nowrap><b>OS Fabricante</b></th>
			<th nowrap><b>Data Abertura</b></th>
			<th nowrap><b>Consumidor</b></th>
			<th nowrap><b>Produto</b></th>
			<?
			if($posto=='4311' or $posto == '6359' ) {
				echo "<th nowrap><b> Box </b></th>";
			}
			//HD 12521       HD 13239    HD 14121
			if(($login_fabrica <> 11 and $login_fabrica <>1) and $posto== 6359) {
				echo "<th nowrap><b> Data de conserto </b></th>";
			} ?>
		</tr>
</thead>
<tbody>
<?
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$flag_cor = "";
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 if($consumidor_revenda=='R' and $login_fabrica==1){
			$os               = trim(pg_result ($res,$i,os));
			$sua_os           = trim(pg_result ($res,$i,sua_os));
			$admin            = trim(pg_result ($res,$i,admin));
			$tipo_atendimento = trim(pg_result ($res,$i,tipo_atendimento));
			$produto          = trim(pg_result ($res,$i,produto));
			//HD 13239
			if (usaDataConserto($posto, $login_fabrica)) {
				$data_conserto           = trim(pg_result ($res,$i,data_conserto));
			}
			//HD 4291 Paulo
			if($posto=='4311'or $posto == 6359) {
				$prateleira_box          = trim(pg_result ($res,$i,prateleira_box));
			}
			if (strlen($sua_os) == 0) $sua_os = $os;
			$descricao = pg_result ($res,$i,nome_comercial) ;
			if (strlen ($descricao) == 0) $descricao = pg_result ($res,$i,descricao) ;

			 $consumidor_revenda = trim(pg_result ($res,$i,consumidor_revenda));
			 $defeito_constatado = trim(pg_result ($res,$i,defeito_constatado));

				$sql = "SELECT os
						FROM   tbl_os
						WHERE  os = $os
						AND    motivo_atraso IS NULL
						AND    consumidor_revenda = 'C'
						AND    (data_abertura + INTERVAL '30 days')::date < current_date;";
				$resY = pg_exec($con, $sql);
				if (pg_numrows($resY) > 0) {
					$cor = "#FF0000";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

				if (strlen($defeito_constatado) == 0) {
					$cor = "#FFCC66";
					$flag_cor = "t";
					$flag_bloqueio = "t";
				}elseif ($flag_bloqueio == "t" AND strlen($defeito_constatado) <> 0){
					$flag_bloqueio = "t";
				}else{
					$flag_bloqueio = "";
				}

			//HD 4291 Paulo verificar a peça pendente da os e mudar cor
			// HD 14121
			if($posto=='4311' or $posto=='6359' ) {
				$bolinha="";

				$sqlcor="SELECT *
							FROM tbl_os
							WHERE defeito_constatado is null
							AND	  solucao_os is null
							AND	  os=$os";
				$rescor=pg_exec($con,$sqlcor);
				if(pg_numrows($rescor) > 0) {
					$bolinha="vermelho";
				} else {

					$sqlcor2 = "SELECT	tbl_os_item.pedido   ,
										tbl_os_item.peca                      ,
										tbl_pedido.distribuidor             ,
										tbl_os_item.faturamento_item       ";
					if(strlen($os_item)==0){
						$sqlcor2 .=", tbl_os_item.os_item ";
					}
					$sqlcor2 .=	"FROM    tbl_os_produto
								JOIN    tbl_os_item USING (os_produto)
								JOIN    tbl_produto USING (produto)
								JOIN    tbl_peca    USING (peca)
								LEFT JOIN tbl_defeito USING (defeito)
								LEFT JOIN tbl_servico_realizado USING (servico_realizado)
								LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
								LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
								LEFT JOIN tbl_pedido_item on tbl_pedido.pedido=tbl_pedido_item.pedido
								LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
								WHERE   tbl_os_produto.os = $os";

					$rescor2 = pg_exec($con,$sqlcor2);

					if(pg_numrows($rescor2) > 0) {

						for ($j = 0 ; $j < pg_numrows ($rescor2) ; $j++) {
							$pedido               = trim(pg_result($rescor2,$j,pedido));
							$peca                 = trim(pg_result($rescor2,$j,peca));
							$distribuidor         = trim(pg_result($rescor2,$j,distribuidor));
							$faturamento_item     = trim(pg_result($rescor2,$j,faturamento_item));
							if(strlen($os_item) ==0)$os_item              = trim(pg_result($rescor2,$j,os_item));
							$bolinha="";
							if ($login_fabrica == 3) {
								if (strlen($pedido) > 0) {
										$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento_item.pedido  = $pedido
												AND     tbl_faturamento_item.peca    = $peca";
										if($distribuidor=='4311'){
											$sql .=" AND     tbl_faturamento_item.os_item = $os_item
													 AND     tbl_faturamento.posto        = $posto
													 AND     tbl_faturamento.distribuidor = 4311";
										}elseif(strlen($distribuidor)>0 ){
											$sql .=" tbl_faturamento.posto = $distribuidor ";
										}else{
											$sql .=" AND     (length(tbl_faturamento_item.os) = 0 OR tbl_faturamento_item.os = $os)
													 AND     tbl_faturamento.posto       = $posto";
										}
										$resx = pg_exec ($con,$sql);
										if (pg_numrows ($resx) == 0) {
											$bolinha="amarelo";
										}
									$sql="SELECT count(os_item) as conta_item,
												 os as conta_os
											FROM tbl_os_produto
											JOIN tbl_os_item using(os_produto)
											WHERE os=$os
											GROUP BY os";
									$resX = pg_exec ($con,$sql);
									if (pg_numrows ($resX) > 0) {
										$conta_item=pg_result($resX,0,conta_item);
										$conta_os  =pg_result($resX,0,conta_os);
										if(strlen($conta_item) > 0){
											$sql = "SELECT	count(embarcado) as embarcado
													FROM tbl_embarque_item
													JOIN tbl_os_item ON tbl_os_item.os_item = tbl_embarque_item.os_item
													JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
													WHERE tbl_os_item.os_item in (SELECT os_item FROM tbl_os_produto JOIN tbl_os_item using(os_produto) WHERE os=$conta_os)	";
											$resX = pg_exec ($con,$sql);
											if (pg_numrows ($resX) > 0) {
												$embarcado      = trim(pg_result($resX,0,embarcado));
											}
											if($embarcado==$conta_item ){
												$bolinha="rosa";
											}
										}
									}
								}else {
									$bolinha="amarelo";
								}
							}elseif($login_fabrica==11){
								if (strlen($faturamento_item)>0){
									$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento.fabrica=$login_fabrica
												AND     tbl_faturamento_item.faturamento_item = $faturamento_item";

									$resx = pg_exec ($con,$sql);

									if (pg_numrows ($resx) == 0) {
										$bolinha="amarelo";
									}else {
										$nota_fiscal=pg_result($resx,0,nota_fiscal);
										if(strlen($nota_fiscal) > 0){
											$bolinha="rosa";
										}
									}
								}else{
									if (strlen($pedido) > 0) {
										$bolinha="amarelo";
									}
								}
							} else {
								if (strlen ($nota_fiscal) == 0) {
									if (strlen($pedido) > 0) {
										$sql  = "SELECT *
												FROM    tbl_faturamento
												JOIN    tbl_faturamento_item USING (faturamento)
												WHERE   tbl_faturamento.pedido    = $pedido
												AND     tbl_faturamento_item.peca = $peca;";
										$resx = pg_exec ($con,$sql);

										if (pg_numrows ($resx) == 0) {
											$condicao_01 = " 1=1 ";
											if (strlen ($distribuidor) > 0) {
												$condicao_01 = " tbl_faturamento.distribuidor = $distribuidor ";
											}
											$sql  = "SELECT *
													FROM    tbl_faturamento
													JOIN    tbl_faturamento_item USING (faturamento)
													WHERE   tbl_faturamento_item.pedido = $pedido
													AND     tbl_faturamento_item.peca   = $peca
													AND     $condicao_01 ";
											$resx = pg_exec ($con,$sql);

											if (pg_numrows ($resx) == 0) {
												if ($login_fabrica==1){
													$sql  = "SELECT *
																FROM    tbl_pendencia_bd_novo_nf
																WHERE   posto        = $posto
																AND     pedido_banco = $pedido
																AND     peca         = $peca";
													$resx = pg_exec ($con,$sql);

													if (pg_numrows ($resx) > 0) {
														$bolinha="amarelo";
													}
												}else{
													$bolinha="amarelo";
												}
											}
										}
									}
								}
							}
						}
					}
				}

				if(strlen($data_conserto) > 0) {
					$bolinha="azul";
				}
			}
//HD 4291 Fim
			if (strlen($linha_erro[$i]) > 0) $cor = "#99FFFF";

?>

		<tr bgcolor=<?
			echo $cor;
			echo " rel='status_$status_checkpoint' ";

			?> <? if ($linha_erro == $i and strlen ($msg_erro) > 0 )?>>
			<input type='hidden' name='os_<? echo $i ?>' value='<? echo pg_result ($res,$i,os) ?>' >
			<input type='hidden' name='conta_<? echo $i ?>' value='<? echo $i;?>'>
			<input type='hidden' name='consumidor_revenda_<? echo $i ?>' value='<? echo pg_result ($res,$i,consumidor_revenda)?>'>
			<td align="center">
			<? if (strlen($flag_bloqueio) == 0) { ?><input type="checkbox" class="frm" name="ativo_revenda_<?echo $i?>" id="ativo_revenda" value="t" ><? } ?></td>

			<?
			//HD 4291 Paulo
			if (($posto == '4311' or $posto == '6359' ) and strlen($bolinha) > 0) {
				$bolinha = "<img src='imagens/status_$bolinha' width='10' align='absmiddle'>";
			} else {
				$bolinha="";
			}
			//Fim
			?>

	<td><? if (($posto=='4311' or $posto=='6359' ) and strlen($bolinha) > 0) { echo $bolinha; } ?><a href='<? if ($cor == "#FFCC66" or ($login_fabrica==11 and $posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a></td>
			<td><? echo pg_result ($res,$i,data_abertura) ?></td>
			<td NOWRAP ><? echo substr (pg_result ($res,$i,consumidor_nome),0,10) ?></td>
			<td NOWRAP><? echo pg_result ($res,$i,serie) . " - " . substr ($descricao,0,15) ?></td>
<?


if($posto=='4311' or $posto == '6359') {
	echo "<td align='center'>$prateleira_box</td>";
}
//HD 12521 //HD13239 hd 14121
if (usaDataConserto($posto, $login_fabrica)) {
	echo "<td align='center'>";
		if(strlen($data_conserto)>0){
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' disabled>";
		}else{
			echo "<input class='frm' type='text' name='data_conserto_$i' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto'>";
		}
	echo "</td>";
	}

?>
	</tr>

<?}
			}?>
</tbody>
		</table>

<?}?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">

<?		if($sistema_lingua == "ES"){?>
		<img src='imagens/btn_cerrar_maior.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar con orden de servicio" border='0' style='cursor: pointer'>

		<? }else{ ?>
		<img src='imagens/btn_fechar_azul.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
		<? }?>
		</td>
</tr>

</form>

</table>
<?
		}else{
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="top" align="center">
		<h4>
		<?
		if($sistema_lingua == "ES") echo "No fue(ran) encuentrada(s) OS(s) cerrada(s)";
		else                        echo "Não foi(ram) encontrada(s) OS(s) não finalizada(s).";
		?>

		</h4>
	</td>
</tr>
</table>
<?
		}

	}
?>
<p>

<? include "rodape.php"; ?>
