<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
//include "autentica_usuario_financeiro.php";

if ($login_fabrica <> 1) {
	header ("Location: os_extrato.php");
	exit;
}

$ajax         = $_GET['ajax'];
$protocolo         = $_GET['protocolo'];

if(strlen($ajax)>0){

	$mensagem_alert = "Prezado Assistente,<BR><BR>Recebemos a documentação referente ao extrato $protocolo, porém temos extrato(s) gerado(s) anteriormente para o seu posto que ainda estão em aberto no sistema. Gentileza entrar no link extrato e verificar os extratos com o status Pendente / Aguardando documentação.
	<BR><BR>
	O extrato $protocolo será bloqueado até recebermos um posicionamento sobre os outros extratos em aberto. <BR><BR>
	Obrigado,
	<BR>
	Departamento de Pagamento em Garantia <BR>
	Stanley Black & Decker <BR>
	Telefone: (34) 3318-3921 <BR>
	E-mail: pagamento.garantia@sbdbrasil.com.br <BR>";
	echo "<BR><font size='2' face='verdana'>$mensagem_alert</font>";
	exit;
}


$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

$pendencia= $_GET['pendencia'];
$liberado= $_POST['liberado'];

include "cabecalho.php";

?>

<p>
<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>

<?php


    $sql = "SELECT  posto,
                    intervalo_extrato
            FROM    tbl_tipo_gera_extrato
            WHERE   posto   = $login_posto
            AND     fabrica = $login_fabrica
            AND     tipo_envio_nf IS NOT NULL
            ";

        $res = pg_query($con,$sql);
        $total = pg_num_rows($res);

        if ( $total > 0 || !empty($cook_admin) ) {
                $botao_fechar_modal = !empty($cook_admin) ? '' : "onFinish: $('#sb-nav-close').attr('style','visibility:hidden'),";
                $intervalo_extrato = pg_fetch_result($res, 0, 'intervalo_extrato');
        }

?>

<script type="text/javascript">

        function showModal() {

                Shadowbox.open({
                        content:"verifica_forma_extrato.php",
                        player: "iframe",
                        title:  "Geração de Extrato",
                        width:  800,
                        <?=$botao_fechar_modal?>
                        height: 600
                });

        }

        window.onload = function(){

            Shadowbox.init( {
                    skipSetup: true,
                    modal: true,
            } );

            <?php if ($total <= 0 ) : ?>
                    showModal();
            <?php endif; ?>

        };

        $().ready(function () {
            $("body").keydown(function(e){
                if (e.which == 27) {

                    e.preventDefault();
                    return false;
                }

            });
        });

</script>

<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.comunic_titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #CC3333;
}
.comunic_linha {
	text-align: justify;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	border: 0px solid;
	background-color: #FE918D;
}
</style>
<script language="JavaScript">

function MostraObs(dados){
//alert(dados);
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
function AbrirJanelaObs (extrato,tipo) {
	var largura  = 550;
	var tamanho  = 550;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status_aprovado.php?extrato=" + extrato + "&tipo=" + tipo;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<?
# HD 55829 188625
$mes = date('m');
$ano = date('Y');

if ($login_fabrica == 1 and date('Y-m-d') <'2010-02-01'){
	echo "<table width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
	echo "<tr><td class='comunic_titulo'>";
	echo "<div align='center' style='font-size:16px'><strong>ATENÇÃO!</strong></div>";
	echo "</td></tr></table>";
	echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
	echo "<tr class='comunic_linha'>";
	echo "<td style='padding: 5 10px;'>Devido ao nosso fechamento anual, faremos uma manutenção no sistema de pagamento de garantias. Por isso, não haverá aprovação de extratos de 04/01/2010 a 24/01/2010. A aprovação de extratos ficará suspensa nesse período, retornando em 25/01/2010.
	<br/><br/>
	Nesse período, continuaremos fazendo a conferência das documentações e envio dos extratos para o financeiro. Além disso, o posto poderá efetuar o lançamento e fechamento das OS's normalmente.</td>";
	echo "</tr></table>";
	echo "<br/>";
}
/*
if(strlen($msg)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg</td>";
	echo "</tr>";
	echo "</table><br>";
	echo "<a href='os_extrato_senha.php?acao=alterar'>Alterar senha</a>";
	echo "&nbsp;&nbsp;<a href='os_extrato_senha.php?acao=libera'>Liberar tela</a>";
}else{
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='os_extrato_senha.php?acao=inserir' >Esta area não está protegida por senha! <br>Para inserir senha para Restrição do Extrato, clique aqui e saiba mais! </a></td>";
	echo "</tr>";
	echo "</table><br>";
}*/

 $sql =	"SELECT DISTINCT
				tbl_extrato.extrato                                            ,
				tbl_extrato.protocolo                                          ,
				tbl_extrato.data_geracao                       AS ordem        ,
				TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao ,
				tbl_extrato.mao_de_obra                                        ,
				tbl_extrato.mao_de_obra_postos                                 ,
				tbl_extrato.pecas                                              ,
				tbl_extrato.total                                              ,
				tbl_extrato.aprovado                                           ,
				tbl_extrato.posto                                              ,
				tbl_extrato.bloqueado                                          ,
				tbl_posto_fabrica.codigo_posto                                 ,
				tbl_posto.nome                                                 ,
				TO_CHAR(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_envio,
                tbl_extrato_extra.obs
		FROM      tbl_extrato
		JOIN      tbl_posto              ON tbl_posto.posto                = tbl_extrato.posto
		JOIN      tbl_posto_fabrica      ON tbl_posto_fabrica.posto        = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica      = $login_fabrica
		LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
		LEFT JOIN tbl_extrato_status     ON tbl_extrato_status.extrato     = tbl_extrato.extrato
        JOIN tbl_extrato_extra           ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato.posto   = $login_posto
		AND   tbl_extrato.aprovado NOTNULL
		GROUP BY tbl_extrato.extrato               ,
				 tbl_extrato.protocolo             ,
				 tbl_extrato.data_geracao          ,
				 tbl_extrato.mao_de_obra           ,
				 tbl_extrato.mao_de_obra_postos    ,
				 tbl_extrato.pecas                 ,
				 tbl_extrato.total                 ,
				 tbl_extrato.aprovado              ,
				 tbl_extrato.posto                 ,
				tbl_extrato.bloqueado              ,
				 tbl_posto_fabrica.codigo_posto    ,
				 tbl_posto.nome                    ,
				 tbl_extrato_financeiro.data_envio ,
                 tbl_extrato_extra.obs
		ORDER BY ordem DESC";
//if ($ip == '201.43.11.216') { echo nl2br($sql); exit; }
$res = pg_exec($con,$sql);

// echo nl2br($sql) . "<br>" . pg_numrows($res);

echo "<table width='700' height='16' border='0' cellspacing='0' cellpadding='0' align='center'>";
echo "<tr>";
echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
echo "</tr>";
if($login_fabrica == 1){//hd 4307 takashi 07/11/07
	echo "<tr>";
	echo "<td align='center' width='16' bgcolor='#FF9E5E'>&nbsp;</td>";
	echo "<td align='left'><font size=1><b>&nbsp; Extrato Bloqueado</b></font></td>";
	echo "</tr>";

    echo "<tr>";
    echo "<td align='center' width='16' bgcolor='#fa8989'>&nbsp;</td>";
    echo "<td align='left'><font size=1><b>&nbsp; Pendente</b></font></td>";
    echo "</tr>";
    $cor_pendente = "#fa8989";

}
echo "</table>";
echo "<br>";

//alteracao Gustavo 22/11/2007 HD 8069
/*
Comunicado trocado
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Devido a exigências do nosso departamento fiscal, informamos que para cada extrato aprovado pela B&D é necessária a emissão de uma nota fiscal de mão-de-obra.<br>
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Dessa forma, a partir de hoje (15/05/2007) não deverá ser emitida uma nota fiscal apenas para mais de um extrato, mas sim uma nota fiscal para cada extrato.<br>
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Lembrando que, de acordo com o nosso procedimento de garantia, a documentação deverá ser enviada logo após a aprovação do extrato.

//alteracao Gustavo 11/2/2008 HD 13635
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Prezado Assistente,<BR>
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Devido ao nosso fechamento anual, acontecerá uma manutenção no sistema de pagamento de garantias. Dessa forma, não faremos aprovação de extratos de 17/12/2007 a 06/01/2008. A aprovação de extratos ficará suspensa nesse período, retornando em 07/01/2008.<BR>
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nesse período continuaremos atendendo às outras funções referentes ao pagamento, tais como: Abertura, lançamento de itens e fechamento das OS’s no sistema, envio de documentação ao financeiro, atendimento via telefone e e-mail, entre outros.<BR>
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Obrigada pela compreensão.
*/

if($login_fabrica == 1 && 1==2){
	$mensagem = "Devido ao nosso fechamento anual, interromperemos o processo conferência das documentações de extratos de serviços de 27/12/10 a 16/01/2011. Portanto, as documentações que receberemos nesse período serão conferidas a partir de 17/01/2011.

Durante esse período, continuaremos fazendo as aprovações de extratos normalmente. Além disso, o posto poderá efetuar o lançamento e fechamento das OS's da mesma forma.

Qualquer dúvida, entrar em contato com a Ellen Batista.";

	$mensagem = nl2br($mensagem);

	echo "<table width='700' bgcolor='#D8E0FC' align='center'>
	<tr><td align='center' style='font-size: 14px'><b>Comunicado</b></td>
	<tr><td>
	<p align='justify' style='font-size: 12px; font-family: verdana;'>
		Prezado Assistente,<BR>
		<br>
		$mensagem
	</p></td></tr></table>";
}
	echo "<h3><center><b>Obs.: Após o envio do extrato ao financeiro, o prazo para pagamento é de aproximadamente 15 dias.</b></center></h3>";

echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
if (pg_numrows($res) > 0) {
	echo "<tr class='menu_top'>\n";
	echo "<td>EXTRATO</td>\n";
	//echo "<td>POSTO</td>\n";
	echo "<td>DATA GERAÇÃO</td>\n";
	echo "<td>TOTAL</td>\n";
	echo "<td>Pendência</td>\n";
	echo "<td>STATUS</td>\n";
    echo "<td>Conferido </td>";

	echo "<td nowrap>ENVIADO AO<br>FINANCEIRO</td>\n";
//	echo "<td>TOTAL + AVULSO</td>\n";

	echo "<td nowrap>Programação do depósito</td>\n";
	echo "<td nowrap>Tipo Geração Extrato</td>\n";
	echo "<td nowrap>Tipo Envio NF</td>\n";
	echo "<td>AÇÕES</td>\n";
	echo "</tr>\n";

	$sql = "SELECT posto, tipo_envio_nf_ant, data_envio_ant
			FROM tbl_tipo_gera_extrato
			WHERE fabrica = $login_fabrica
			AND posto = $login_posto
			AND envio_online
			AND tipo_envio_nf = 'online_possui_nfe'";

	$res2 = pg_query($con, $sql);

	$anexaNFServicos = pg_num_rows($res2);

	$sql = "SELECT posto, tipo_envio_nf_ant, data_envio_ant,data_atualizacao,data_input
			FROM tbl_tipo_gera_extrato
			WHERE fabrica = $login_fabrica
			AND posto = $login_posto";

	$res3 = pg_query($con, $sql);
	if(pg_num_rows($res3) > 0){
		$tipo_envio_nf_ant = pg_fetch_result($res3, 0, 'tipo_envio_nf_ant');
		$data_envio_ant    = pg_fetch_result($res3, 0, 'data_envio_ant');
		$data_atualizacao  = pg_fetch_result($res3, 0, 'data_atualizacao');
		$data_atualizacao  = empty($data_atualizacao) ? pg_fetch_result($res3, 0, 'data_input') : $data_atualizacao;
	}

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {

		$xmao_de_obra       = 0;
		$posto              = trim(pg_result($res,$i,posto));
		$posto_codigo       = trim(pg_result($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result($res,$i,nome));
		$extrato            = trim(pg_result($res,$i,extrato));
		$data_geracao       = trim(pg_result($res,$i,data_geracao));
		$mao_de_obra        = trim(pg_result($res,$i,mao_de_obra));
		$mao_de_obra_postos = trim(pg_result($res,$i,mao_de_obra_postos));
		$pecas              = trim(pg_result($res,$i,pecas));
		$extrato            = trim(pg_result($res,$i,extrato));
		$total_avulso       = trim(pg_result($res,$i,total));
		$protocolo          = trim(pg_result($res,$i,protocolo));
		$data_envio         = trim(pg_result($res,$i,data_envio));
	/*	$obs                = trim(pg_result($res,$i,obs));*/
		$aprovado           = trim(pg_result($res,$i,aprovado));
		$bloqueado           = trim(pg_result($res,$i,bloqueado));
		$ordem              = trim(pg_result($res,$i,ordem));
        $obs_geracao_extrato                = pg_result($res,$i,"obs");

	/*	$pendente           = trim(pg_result($res,$i,pendente));
		$confirmacao_pendente  = trim(pg_result($res,$i,confirmacao_pendente));*/

		if((!empty($data_envio_ant) and strtotime($data_envio_ant) >= strtotime($ordem))){
			$anexaNFServicos = "";
		}else{
			$anexaNFServicos = pg_num_rows($res2);
		}

		if (strlen($aprovado) > 0 AND strlen($data_envio) == 0) $status = "Aguardando documentação";
		/*HD 1163*/
		/*if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 and $pendente=='t' AND $confirmacao_pendente<>'t') $status = "Pendente, vide observação";*/

		if (strlen($aprovado) > 0 AND strlen($data_envio)  > 0) $status = "Enviado para o financeiro";


		# soma valores
		$xmao_de_obra += $mao_de_obra_postos;
		$xvrmao_obra   = $mao_de_obra_postos;

		if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
		if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;

		$total = $xmao_de_obra + $pecas;

		if ($i % 2 == 0) {
			$cor = "#F1F4FA";
			$btn = "azul";
		}else{
			$cor = "#F7F5F0";
			$btn = "amarelo";
		}

		##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
		if (strlen($extrato) > 0) {
			$sql = "SELECT COUNT(*) AS existe
					FROM tbl_extrato_lancamento
					WHERE extrato = $extrato
					AND   posto   = $login_posto
					AND   fabrica = $login_fabrica";
			$res_avulso = pg_exec($con,$sql);
			if (@pg_numrows($res_avulso) > 0) {
				if (@pg_result($res_avulso,0,existe) > 0) $cor = "#FFE1E1";
			}
		}
		##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####
		$ysql = "SELECT tbl_extrato_status.obs                 ,
						tbl_extrato_status.pendente            ,
						tbl_extrato_status.confirmacao_pendente,
						tbl_extrato_status.advertencia         ,
						tbl_extrato_status.pendente,
						tbl_extrato_status.pendencia,
						CASE WHEN tbl_extrato_status.obs 
						ILIKE '%Li e Confirmo:%'
						THEN 't' ELSE 'f'
						END AS li_confirmo
				FROM tbl_extrato_status
				WHERE tbl_extrato_status.extrato = $extrato
				AND fabrica = $login_fabrica
				ORDER BY data DESC
				LIMIT 1";


//if($ip=="201.27.30.194"){echo "$ysql";}
		$yres = pg_exec($con,$ysql);
            $conferido = "";
                //verifica se admin conferiu

                $verificaConferido = "SELECT conferido
                                      FROM tbl_extrato_status
                                      WHERE extrato = {$extrato} AND conferido is not null AND
                                            length(trim(conferido::text)) > 0";
                $resVerificaConferido = pg_query($con,$verificaConferido);


                if(pg_num_rows($resVerificaConferido) > 0){

                    $conferido = true;


                }else{
                    $conferido = false;
                }

		if(pg_numrows($yres)>0){

			$pendente 		= trim(pg_result($yres,0,'pendente'));
			$pendencia 		= trim(pg_result($yres,0,'pendencia'));
			$advertencia 	= pg_result($yres,0,'advertencia');
			$obs 			= pg_result($yres,0,'obs');
			$li_confirmo    = pg_fetch_result($yres, 0, 'li_confirmo');

                //var_dump($extrato."-".$conferido);
                # verifica tipo de envio de extrato
                $sqlTpoEnvio = "SELECT obs
                                FROM tbl_extrato_extra
                                JOIN tbl_extrato USING(extrato)
                                WHERE fabrica={$login_fabrica} AND
                                extrato = {$extrato}";

                $resTpoEnvio = pg_query($con,$sqlTpoEnvio);

                if(pg_num_rows($resTpoEnvio) > 0){
                    $obs = pg_fetch_result($resTpoEnvio,0,"obs");
                    $obsJson = json_decode($obs);

                    $tpoEnvio = $obsJson->tipo_de_envio;
                }

                $sqlNfAnexada = "SELECT referencia_id 
                				 FROM tbl_tdocs 
                				 WHERE fabrica = $login_fabrica
                				 AND referencia_id = $extrato
                				 AND referencia = 'osextrato' 
                				 AND situacao = 'ativo'";
                $resNfAnexada = pg_query($con, $sqlNfAnexada);

			if ( strlen($aprovado) > 0 AND strlen($data_envio) == 0 && $anexaNFServicos && ($pendencia != 't' || ($pendencia == 't' && $li_confirmo == 't'))) {
				if (pg_num_rows($resNfAnexada) > 0) {
					$status = "Aguardando envio para o financeiro";
				} else {
					$status = "Aguardando NF de serviços";
				}

			}

			else if (strlen($aprovado) > 0 AND strlen($data_envio) == 0 && $obs != 'Aguardando NF de serviços' && $li_confirmo == 't') {
				$status = "Pendente";
			}


            if($status == "Pendente"){
                $cor = $cor_pendente;
            }
		}
		$sql = "SELECT sum(tbl_os.pecas * (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float -1))) 
			from tbl_os
			join tbl_os_extra using(os)
			join tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os and campos_adicionais ~'TxAdm'
			where tbl_os_extra.extrato = $extrato
			and tbl_os.pecas > 0
			and (((regexp_replace(campos_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::jsonb->>'TxAdmGrad')::float)) > 0 ";
		$resX = pg_query($con, $sql);
		$totalTx = pg_fetch_result($resX,0, 0); 
		if($totalTx > 0) {
			$total_avulso += $totalTx;
		}
		echo "<tr class='table_line' style='background-color: $cor;'>\n";
		echo "<td align='center'>$protocolo</td>\n";
	/*	echo "<td nowrap><acronym title='POSTO: $posto_codigo\nRAZÃO SOCIAL: $posto_nome' style='cursor: help;'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";*/
		echo "<td align='center'>$data_geracao</td>\n";
		echo "<td align='right' nowrap> R$ ". number_format($total_avulso,2,",",".") ."</td>\n";

		echo "<td align='center' nowrap>";


		if(pg_numrows($yres)>0){

			echo "<a href='javascript: AbrirJanelaObs($extrato,\"pendencia\");'>Abrir Pendência</a>";

			if($advertencia == 't'){
				$status = "Alerta";
			} else if ( strlen($aprovado) > 0 && $pendente == 't' && $pendencia == 't' && $anexaNFServicos && strtotime($ordem) >= strtotime($data_atualizacao)) {
				if ($status != 'Pendente' && $li_confirmo != 't')
					$status = "Aguardando NF de serviços";
				echo " | <a href='javascript: AbrirJanelaObs($extrato,\"pendencia\");'>Inserir NF de Serviços</a>";

			} else if ($li_confirmo == 't' && $anexaNFServicos) {
				echo " | <a href='javascript: AbrirJanelaObs($extrato,\"pendencia\");'>Inserir NF de Serviços</a>";
			}

		}else if ( strlen($aprovado) > 0 &&  $anexaNFServicos && strtotime($ordem) >= strtotime($data_atualizacao)) {
				if ($status != 'Pendente')
					$status = "Aguardando NF de serviços";
				echo "  <a href='javascript: AbrirJanelaObs($extrato,\"pendencia\");'>Inserir NF de Serviços</a>";

			}


		echo "</td>\n";
		echo "<td align='center'";
		if($bloqueado=="t" and strlen($data_envio)==0){ //hd 4307 takashi 07/11/07
			echo " bgcolor='#FF9E5E' ";
		}
		echo "nowrap>";
		if($bloqueado=="t" and strlen($data_envio)==0){
			$status = "Extrato Bloqueado";
			echo "<a href=\"$PHP_SELF?ajax=true&protocolo=$protocolo&keepThis=trueTB_iframe=true&height=340&width=420\"  title=\"Comunidado enviado por e-mail\" class=\"thickbox\">";
		}
		echo $status;
		if($bloqueado=="t"){
			echo "</a>";
		}
		echo "</td>\n";
        if($conferido){
            echo "<td style='text-align:center;'>Conferido</td>";
        }else{
            echo "<td style='text-align:center;'> - </td>";
        }

		echo "<td align='center'>$data_envio</td>\n";
//		echo "<td align='right' nowrap> R$ ". number_format($total,2,",",".") ."</td>\n";


		echo "<td align='center' ";
		$ysql = "SELECT tbl_extrato_status.obs                   ,
						tbl_extrato_status.pendente              ,
						tbl_extrato_status.confirmacao_pendente
		FROM tbl_extrato_status
		WHERE tbl_extrato_status.extrato = $extrato
		AND confirmacao_pendente IS NULL AND pendente is null and pendencia is false";
//echo "$ysql";
		$yres = pg_exec($con,$ysql);
		if(pg_numrows($yres)>0){
			$obs_extrato = pg_result($yres,0,obs);
			if (strlen($obs_extrato) < 30) echo "nowrap>";
			else echo ">";

			echo "$obs_extrato";
			//echo "<a href='javascript: AbrirJanelaObs($extrato,\"obs\");'>Abrir</a>";
		} else {
			echo ">";
		}
		echo "</td>\n";


        if( strlen($obs_geracao_extrato) > 0 ){
            $dadosGeracao = json_decode(utf8_encode($obs_geracao_extrato));
        }


		echo "<td align='center'>";


        if(isset($dadosGeracao->tipo_de_geracao) && strlen($dadosGeracao->tipo_de_geracao) > 0){

            echo utf8_decode($dadosGeracao->tipo_de_geracao);
	    }else{

			switch ($intervalo_extrato) {
				case 1:
					echo "Autom&aacute;tico";
					break;
				case 2:
					echo "Semanal";
					break;
				case 3:
					echo "Quinzenal";
					break;
				case 4:
					echo "Mensal";
					break;
			}
        }
		echo "</td>";

        echo "<td align='center'>";
        if(isset($dadosGeracao->tipo_de_envio) && strlen($dadosGeracao->tipo_de_envio) > 0){
            echo utf8_decode("{$dadosGeracao->tipo_de_envio}");
	}else{
		$sql = "SELECT tipo_envio_nf
			FROM tbl_tipo_gera_extrato
			WHERE fabrica = $login_fabrica
			AND posto = $login_posto";
		$resTEnv = pg_query($con,$sql);

		if(pg_num_rows($resTEnv) > 0){
			$tipo_envio = str_replace("_"," ",pg_fetch_result($resTEnv, 0, 'tipo_envio_nf'));
			echo strtoupper($tipo_envio);
		}
	}
        echo "</td>";
		echo "<td><a href='os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato' target='_blank'><img src='imagens/btn_imprimir.gif' ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></a></td>\n";
		echo "</tr>\n";
	}
}else{
	echo "<tr class='table_line'>\n";
	echo "<td align='center'>NENHUM EXTRATO FOI ENCONTRADO</td>\n";
	echo "</tr>\n";
}
echo "</table>\n";

echo "<br>";

include "rodape.php";


?>
