<?php

// include 'token_cookie.php';


$token_cookie = $_COOKIE['sess'];

$cookie_login = get_cookie_login($token_cookie);

// MLG - Retirado, sobrescreve o que foi definido no autentica_usuario
//$cook_idioma = $_COOKIE['cook_idioma'];
// Bom para testes
$get_idioma  = $_GET['idioma'];

/*if ($login_fabrica == 87 and ($login_posto <> 120933)) {

	echo "<h1>Sistema em Manutenção!!!</h1>";
	die;
}*/

// Nem todas as telas o carregam...
include_once('funcoes.php');

if (strlen ($get_idioma) > 0) {
	setcookie ("cook_idioma",$get_idioma,time()+60*60*24*30);
	$cook_idioma = $get_idioma;
}

if ($_SERVER['PHP_SELF'] == "/index.php") {
	if (strlen ($get_idioma) == 0 AND strlen ($cook_idioma) == 0) {
		$cook_idioma = 'pt-br';
		setcookie ("cook_idioma",'pt-br',time()+60*60*24*30);
	}
}

define('TELA_MENU', (strpos($PHP_SELF, 'menu_')!==false));      // Define se a tela atual é algum menu
#$tira_adSense = in_array($login_fabrica, array(87,10,46,152)); // Adicionar os fabricantes que não querem o adSense nos menus. Também no admin.

if (strlen($cookie_login['cook_login_unico']) == 0) {

	if (in_array($login_fabrica, array(3,148))) {
		include "autentica_validade_senha.php";
	}
}

// Determina se o posto tem pendências com o Distribuidor TELECONTROL
$existe_pendencia_tc = ($telecontrol_distrib=='t' and $login_bloqueio_pedido == 't');

#Desabilitar para fabricantes suspensos.
#HBFlex
if (($login_fabrica == 25) and $login_posto <> 6359) {
	echo "<h1>Serviço desabilitado pelo fabricante</h1>";
	exit;
}

if ($login_fabrica == 24) {

	include "valida_os_procon.php";

}

if (!function_exists("codigo_visitar_loja")) {
	function codigo_visitar_loja($login, $is_lu=true, $fabrica='') { // BEGIN function codigo_visitar_loja
		$lu = ($is_lu) ? "1" : "0";
		$cp_len		= dechex(strlen($login));   // Comprimento do código_posto / login_unico, em hexa (até 15 chars)
		$ctrl_pos	= str_pad(4 + $cp_len,2, "0",STR_PAD_LEFT); // Posição do código de controle, 2 dígitos (até 255 chars... suficiente)
		$fabrica	= str_pad($fabrica,   2, "0",STR_PAD_LEFT);// Código da fábrica. '00' se é login_unico
		$controle	= ((date('d')*24) + date('h')) * 3600;    // Pega apenas dia do mês e hora, para
															// minimizar divergências se passarem vários minutos desde
															// que carregou a página até que clica em visitar loja...
		return $lu . $cp_len . $ctrl_pos . $fabrica . $login . $controle;
	} // END function codigo_visitar_loja
}
$sql = "SELECT digita_os, pedido_faturado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
$res = @pg_query($con, $sql);
if(pg_num_rows($res)) {
    $digita_os = pg_fetch_result($res, 0, 'digita_os');
    $pedido_faturado = pg_fetch_result($res, 0, 'pedido_faturado');
}

if(isset($_POST['ComunicadoGeralPosto'])){
	$ComunicadoGeralPosto = $_POST['ComunicadoGeralPosto'];
	$ComunicadoGeralPosto = $login_posto;

	setcookie("ComunicadoGeralPosto", $ComunicadoGeralPosto, time()+(3600*72));
	exit;
}

 if($login_fabrica == 1){
     if(!strstr($_SERVER['REQUEST_URI'],"opiniao_posto.php")){
         $sqlOpiniao = "SELECT * FROM tbl_opiniao_posto where fabrica = ".$login_fabrica." AND ativo is true";
         $resOpiniao = @pg_exec($con, $sqlOpiniao);
         if(pg_num_rows($resOpiniao) > 0){
             $resOpiniaoArray = pg_fetch_array($resOpiniao);
             $opiniao_posto = $resOpiniaoArray['opiniao_posto'];
             $sqlOpiniao = "select op.* from tbl_opiniao_posto_resposta opr join tbl_opiniao_posto_pergunta opp on opr.opiniao_posto_pergunta = opp.opiniao_posto_pergunta join tbl_opiniao_posto op on opp.opiniao_posto = op.opiniao_posto  where opr.posto = ".$login_posto." and op.fabrica = ".$login_fabrica." and opp.opiniao_posto = ".$opiniao_posto.";";

             $resVerificaResposta = @pg_exec($con,$sqlOpiniao);
             if(pg_num_rows($resVerificaResposta) == 0){
                 $redirecionar = "./opiniao_posto.php";
                 header("Location: $redirecionar");
             }
         }
     }
 }

if(in_array($login_fabrica, array(152,180,181,182))) { //hd_chamado=2824422
	$sql = "SELECT comunicado
		FROM tbl_comunicado
		WHERE tipo='Contrato'
		AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)> 0) {
		$sqlContrato = "SELECT tbl_comunicado_posto_blackedecker.posto
						FROM tbl_comunicado_posto_blackedecker
						JOIN tbl_comunicado ON tbl_comunicado.comunicado  = tbl_comunicado_posto_blackedecker.comunicado AND tbl_comunicado.fabrica = {$login_fabrica}
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_comunicado_posto_blackedecker.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
						WHERE tbl_comunicado_posto_blackedecker.posto = {$login_posto}
						AND tbl_comunicado_posto_blackedecker.fabrica = {$login_fabrica}
						AND tbl_posto_fabrica.credenciamento IN('CREDENCIADO','EM DESCREDENCIAMENTO')
						AND tbl_comunicado.tipo = 'Contrato'";
		$resContrato = pg_query($con, $sqlContrato);

		if(pg_num_rows($resContrato) == 0){
			header("Location: http://www.telecontrol.com.br/");
			exit;
		}
	}
}

//NOVO_MENU:
include_once 'novo_menu.php';

//if ($login_fabrica == 15 and intval(date('d')) >= 25) {
	//$sql_frm = "SELECT current_date - INTERVAL '1 Month', data::date <= current_date - INTERVAL '3 Month' AS intervalo FROM tbl_posto_atualizacao WHERE fabrica = $login_fabrica AND posto = $login_posto";
	//$res_frm = pg_query($con, $sql_frm);
	//if (@pg_num_rows($res_frm) == 0 or @pg_fetch_result($res_frm, 0, 'intervalo') == 't'){
	//	include 'posto_atualizacao_dados.php';
	//}
//}

// HD 352102 - FIM
?>
<center>
<script type="text/javascript">var idioma_verifica_servidor = "<?php echo $cook_idioma;?>"</script>

<?php
	if(in_array($login_fabrica, array(1))) {

		$link_programa =  $_SERVER['SCRIPT_NAME'];

		$sql = "SELECT contato_estado as estado, tipo_posto
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND   posto = $login_posto ";
		$res = pg_query($con,$sql);
		$estado     = pg_fetch_result($res, 0, 'estado');
		$tipo_posto = pg_fetch_result($res, 0, 'tipo_posto');

		$sql =	"SELECT  tbl_comunicado.comunicado                              ,
				tbl_comunicado.descricao                                        ,
				tbl_comunicado.mensagem                                         ,
				tbl_comunicado.extensao                                         ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
				tbl_comunicado.programa
				FROM tbl_comunicado
				WHERE tbl_comunicado.fabrica = $login_fabrica
				AND   tbl_comunicado.tipo = 'Comunicado por tela'
				AND   (tbl_comunicado.estado = '$estado' OR tbl_comunicado.estado ISNULL)
				AND   (tbl_comunicado.tipo_posto = $tipo_posto OR tbl_comunicado.tipo_posto ISNULL)
				AND tbl_comunicado.programa = '$link_programa'
				AND tbl_comunicado.ativo IS TRUE
				ORDER BY tbl_comunicado.data DESC LIMIT 1;";
		$res = pg_query($con,$sql);

		if(pg_numrows($res) > 0){ ?>
		    <br />

		    <table align='center' bgcolor='000000' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 12px;' width='650'>
			<tr bgcolor="#CC4444">
				<TD align='center' style='font-size: 14px; color: #FFFFFF'><B>Importante!</B></TD>
			</tr>

			<tr bgcolor='#FFCC99'>
				<td align='left' style='color: #330000;'>
				    <?php
				      if(strlen(pg_result($res,0,descricao)) > 0){
					echo "<center><span style='color: #330000; font-size:14px; font-weight:bold;'>".pg_result($res,0,descricao)."</span></center> <br />";
				}
				      echo pg_result($res,0,mensagem);
				    ?>

				    <? if(strlen(pg_result($res,0,extensao)) > 0){ ?>
					  <p align='center' style='margin:auto'>
						<a href="comunicados/<?=pg_result($res,0,comunicado).'.'.pg_result($res,0,extensao)?>" target="_blank" style="color:#FF0000">
						<u><?=traduz('veja.mais', $con)?></u>
						</a>
					  </p>
				    <? } ?>
				</td>
			</tr>
		    </table> <br />
		<?php
		}

		$sql = "SELECT *
				FROM tbl_comunicado
				WHERE tipo='Comunicado Inicial'
				AND fabrica =  $login_fabrica
				AND posto IS NULL
				AND ativo   IS TRUE
				AND  linha IS NULL
				ORDER BY comunicado DESC LIMIT 1";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) == 0) {
			$sql = "SELECT *
					FROM tbl_comunicado
					WHERE tipo='Comunicado Inicial'
					AND fabrica =  $login_fabrica
					AND posto   =  $login_posto
					AND ativo   IS TRUE
					ORDER BY comunicado DESC LIMIT 1";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) == 0) {
				$sql = "SELECT *
						FROM tbl_comunicado
						JOIN tbl_posto_linha ON tbl_comunicado.linha = tbl_posto_linha.linha
						AND tbl_posto_linha.posto = $login_posto AND tbl_posto_linha.ativo IS TRUE
						WHERE tipo='Comunicado Inicial'
						AND fabrica = $login_fabrica
						AND tbl_comunicado.posto IS NULL
						AND tbl_comunicado.ativo IS TRUE ORDER BY comunicado
						DESC LIMIT 1";
				$res = pg_exec ($con,$sql);
			}
		}

		if (pg_numrows($res) > 0) {
			include "dropdown_mensagem.php";
		}

		if($login_fabrica == 1){
			$complemento_black = " AND tbl_posto_bloqueio.pedido_faturado is false ";
		}

		$sql = "SELECT desbloqueio, observacao
						FROM tbl_posto_bloqueio
						WHERE posto = $login_posto
						AND fabrica = $login_fabrica
						$complemento_black
						AND extrato IS not TRUE
						ORDER BY data_input DESC
						LIMIT 1";
		$res = pg_query($con,$sql);

		$pagina_atual = filter_input(INPUT_SERVER,'SCRIPT_NAME');
        $pagina_atual = substr(strrchr($pagina_atual,'/'),1);
		if(pg_num_rows($res) > 0){
			$desb = pg_fetch_result($res, 0, 'desbloqueio');
			$observacao = pg_fetch_result($res, 0, 'observacao');

            $paginas_bloqueadas = array('os_cadastro.php','os_cadastro_troca.php','os_revenda.php');
            if(in_array($pagina_atual,$paginas_bloqueadas) AND $desb == 'f' and $observacao != "Posto com bloqueio por possuir extratos pendentes a mais de 60 dias" ) {
                echo "<br /><div style='font:bold 13px Arial;background-color: #d9e2ef;text-align: center;width:700px;margin: 0 auto;border-collapse: collapse;border:1px solid #596d9b;color: rgb(89, 109, 155);'>";
                echo "O SEU POSTO DE SERVIÇOS POSSUI OS'S SEM FECHAR HÁ MAIS DE 60 DIAS. SOLICITAMOS QUE REALIZE O FECHAMENTO DAS OS'S PENDENTES PARA QUE A SUA TELA DE DIGITAÇÃO DE OS'S SEJA LIBERADA. SE TIVER QUALQUER DÚVIDA ENTRE EM CONTATO COM O SEU SUPORTE. <a href='os_aberta_mais_180.php' style='color:#FF0000' target='_blank'>CLIQUE AQUI</a> PARA VERIFICAR AS OS'S. ";
                echo "</div>";
                include "rodape.php";
                exit;
            }

		}

		$sql = "SELECT desbloqueio, observacao
						FROM tbl_posto_bloqueio
						WHERE posto = $login_posto
						AND fabrica = $login_fabrica
						$complemento_black
						AND extrato IS TRUE
						ORDER BY data_input DESC
						LIMIT 1";
		$res = pg_query($con,$sql);

		$pagina_atual = filter_input(INPUT_SERVER,'SCRIPT_NAME');
        $pagina_atual = substr(strrchr($pagina_atual,'/'),1);
		if(pg_num_rows($res) > 0){
			$desb = pg_fetch_result($res, 0, 'desbloqueio');
			$observacao = pg_fetch_result($res, 0, 'observacao');

			if($observacao == "Posto com bloqueio por possuir extratos pendentes a mais de 60 dias" && $pagina_atual == "os_cadastro.php"){

				echo "
				<div style='color: #ff0000; font-size: 14px; width: 700px; margin-top: 100px; margin-bottom: 50px;'>
					O SEU POSTO DE SERVIÇOS POSSUI EXTRATO EM ABERTO HÁ MAIS DE 60 DIAS.<br>
					SOLICITAMOS QUE VERIFIQUE SUAS PENDÊNCIAS PARA QUE A SUA TELA DE DIGITAÇÃO DE OSs SEJA LIBERADA.<br>
					SE TIVER QUALQUER DÚVIDA ENTRE EM CONTATO COM O SEU SUPORTE.
				</div>";

				echo "<a href='os_extrato_blackedecker.php'>CLIQUE AQUI PARA VISUALIZAR ESSES EXTRATOS.</a>";

				echo "<br /> <br />";

				include "rodape.php";

				exit;

			}
		}
	}


