<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

session_start();

if (strlen($_GET['os']) > 0) {
	$os   = $_GET['os'];
	$sql  = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
	$res1 = pg_query ($con,$sql);
	$sql  = "SELECT obs_reincidencia,os_reincidente FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res  = pg_query($con,$sql);
	$obs_reincidencia = pg_fetch_result($res,0,'obs_reincidencia');
	$os_reincidente   = pg_fetch_result($res,0,'os_reincidente');
	if ($os_reincidente == 't' AND strlen($obs_reincidencia) == 0) {
		$sql = "SELECT os from tbl_os_status where status_os = 67 and os = $os";
		$res = pg_exec($con,$sql);
		if (pg_num_rows($res) > 0) {
			header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
		}
	}
}

$sql = "SELECT  tbl_fabrica.os_item_subconjunto,
				pedir_defeito_reclamado_descricao
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result($res,0,'os_item_subconjunto');
	$pedir_defeito_reclamado_descricao = pg_result($res,0,'pedir_defeito_reclamado_descricao');
	if (strlen($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

#------------ Le OS da Base de dados ------------#
$os = $_GET['os'];

if (in_array($login_fabrica,array(6,46,120,201,122,126,131,134,136))) {
	header ("Location: os_press.php?os=$os");
}

if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.sua_os                                                     ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.revenda_nome                                               ,
					tbl_os.revenda_cnpj                                               ,
					tbl_os.nota_fiscal                                                ,
					tbl_os.obs                                                        ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_os.defeito_reclamado                     AS dr                ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_solucao.descricao                        AS solucao_os        ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_produto.produto                                               ,
					tbl_produto.referencia                                            ,
					tbl_produto.descricao                                             ,
					tbl_produto.voltagem                                              ,
					tbl_os.serie                                                      ,
					tbl_os.versao                                                     ,
					tbl_os.codigo_fabricacao                                          ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.tipo_os                                                    ,
					tbl_posto_fabrica.codigo_posto
			FROM    tbl_os
			LEFT JOIN    tbl_defeito_reclamado USING (defeito_reclamado)
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_solucao            ON tbl_os.solucao_os = tbl_solucao.solucao
			LEFT JOIN    tbl_produto USING (produto)
			JOIN         tbl_posto USING (posto)
			JOIN         tbl_posto_fabrica      ON  tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_os.os    = $os
			AND     tbl_os.posto = $login_posto";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$sua_os                      = pg_result($res,0,'sua_os');
		$data_abertura               = pg_result($res,0,'data_abertura');
		$data_fechamento             = pg_result($res,0,'data_fechamento');
		$consumidor_nome             = pg_result($res,0,'consumidor_nome');
		$consumidor_cidade           = pg_result($res,0,'consumidor_cidade');
		$consumidor_fone             = pg_result($res,0,'consumidor_fone');
		$consumidor_estado           = pg_result($res,0,'consumidor_estado');
		$revenda_cnpj                = pg_result($res,0,'revenda_cnpj');
		if($sistema_lingua<>"ES" AND $login_fabrica != 15)$revenda_cnpj                = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		$revenda_cnpj                = ($login_fabrica == 15) ? substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) : $revenda_cnpj;
		$revenda_nome                = pg_result($res,0,'revenda_nome');
		$nota_fiscal                 = pg_result($res,0,'nota_fiscal');
		$data_nf                     = pg_result($res,0,'data_nf');
		$defeito_reclamado           = pg_result($res,0,'defeito_reclamado');
		$aparencia_produto           = pg_result($res,0,'aparencia_produto');
		$acessorios                  = pg_result($res,0,'acessorios');
		$defeito_reclamado_descricao = pg_result($res,0,'defeito_reclamado_descricao');
		$defeito_constatado          = pg_result($res,0,'defeito_constatado');
		$solucao_os                  = pg_result($res,0,'solucao_os');
		$produto                     = pg_result($res,0,'produto');
		$produto_referencia          = pg_result($res,0,'referencia');
		$produto_descricao           = pg_result($res,0,'descricao');
		$produto_voltagem            = pg_result($res,0,'voltagem');
		$serie                       = pg_result($res,0,'serie');
		$versao                      = pg_result($res,0,'versao');
		$codigo_fabricacao           = pg_result($res,0,'codigo_fabricacao');
		$obs                         = pg_result($res,0,'obs');
		$codigo_posto                = pg_result($res,0,'codigo_posto');
		$consumidor_revenda          = pg_result($res,0,'consumidor_revenda');
		$tipo_os                     = pg_result($res,0,'tipo_os');
		$dr                          = pg_result($res,0,'dr');

		//$produto_descricao = '';
		$defeito_reclamado = '';

		if (strlen($produto_referencia) > 0) {
			$sql_idioma = " SELECT tbl_produto_idioma.* FROM tbl_produto_idioma
							JOIN    tbl_produto USING (produto)
							WHERE referencia     = '$produto_referencia'
							AND upper(idioma) = '$sistema_lingua'";
			$res_idioma = pg_exec($con,$sql_idioma);
			if (pg_numrows($res_idioma) > 0) {
				$produto_descricao  = trim(pg_result($res_idioma,0,descricao));
			}
		}

		if (strlen($dr) > 0) {
			$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
							WHERE defeito_reclamado = $dr
							AND upper(idioma)        = '$sistema_lingua'";
			$res_idioma = pg_exec($con,$sql_idioma);
			if (pg_numrows($res_idioma) > 0) {
				$defeito_reclamado  = trim(pg_result($res_idioma,0,'descricao'));
			}
		}

	}
	#HD 14830 - Fabrica 25
	#HD 13618 - Fabrica 45
	#HD 12657 - Fabrica 2
	//if ($login_fabrica == 1 OR $login_fabrica == 2 OR $login_fabrica == 3 OR $login_fabrica == 6 OR $login_fabrica == 11 OR $login_fabrica == 25 OR $login_fabrica == 45 OR $login_fabrica == 51 OR $login_fabrica == 35) {


	//HD-2303024

	if($login_fabrica == 104){
		$sql_inter_msg = "SELECT tbl_auditoria_os.os,
												tbl_auditoria_os.observacao
   	    							FROM tbl_auditoria_os
   	    							WHERE tbl_auditoria_os.os = $os
   	    							ORDER BY tbl_auditoria_os.data_input DESC LIMIT 1";
   	$res_inter_msg = pg_query($con, $sql_inter_msg);
   	if(pg_num_rows($res_inter_msg) > 0){
   		$auditoria_os = pg_fetch_result($res_inter_msg, 0, 'os');
   		$auditoria_observacao = pg_fetch_result($res_inter_msg, 0, 'observacao');

	 		// $msg_inter2 = "
	 		// <center>
		 	// 	<div style='font-family:verdana arial helvetica sans sans-serif;padding:10px;width:600px;$border align:center; border:1px dashed #666666;' align='center'>
		 	// 		<b style='font-size:14px;color:red'>ORDEM DE SERVIÇO SOB INTERVENÇÃO DA FÁBRICA</b>
		 	// 		<br /><br />
		 	// 		<b style='color:#000000;font-size:12px'>$auditoria_observacao</b>
		 	// 	</div>
		 	// </center>";

	 		$msg_inter = "<b style='color:#000000;font-size:12px'><br />$auditoria_observacao</b>";
	 	}
	}
	if (in_array($login_fabrica,array(1,2,3,6,11,25,35,45,51,72,104,105,117,120,201,122,172))){

		if (in_array($login_fabrica,array(104,105))){

			$sql = "SELECT status_os, observacao
					FROM tbl_os_status
					WHERE os = $os
					AND status_os IN (70,72,73,62,64,65,87,88,116,117,128)
					ORDER BY data DESC
					LIMIT 1";

		}else{

			$sql = "SELECT status_os, observacao
					FROM tbl_os_status
					WHERE os = $os
					AND status_os IN (72,73,62,64,65,87,88,116,117,128,171)
					ORDER BY data DESC
					LIMIT 1";

		}

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0 OR strlen($msg_inter) > 0) {

			$status_os  =  pg_result($res,0,'status_os');
			$observacao =  pg_result($res,0,'observacao');

			if (strlen($produto) > 0) {
				$sql = "SELECT troca_obrigatoria FROM tbl_produto WHERE produto = $produto;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) > 0) {
					$troca_obrigatoria = pg_result($res,0,'troca_obrigatoria');
				}
			}

			if ($status_os == "62" or ( in_array($login_fabrica,array(104,105)) and $status_os == 70 OR strlen($msg_inter) > 0) ) {
				$temp  = "<b style='font-size:14px;color:red'>";
				$temp .= ($login_fabrica == 51 or $login_fabrica == 104 or $login_fabrica == 105) ? "ORDEM DE SERVIÇO SOB INTERVENÇÃO DA FÁBRICA":"INTERVENÇÃO DA ASSISTÊNCIA TÉCNICA DA FÁBRICA"; //HD 425278 - MLG - 14-06-2011 - O Ronalo pediu para alterar o tí­tulo para a Ga.Ma Italy
				$temp .= "</b>";
				$temp .= "<br /><br />";
				#HD 14830 - Fabrica 25
				#HD 13618 - Fabrica 45

				if ($login_fabrica == 51 or $login_fabrica == 35) {

					$sql4 = "SELECT troca_obrigatoria
								FROM tbl_os
								LEFT JOIN tbl_os_produto USING(os)
								LEFT JOIN tbl_os_item    USING(os_produto)
								LEFT JOIN tbl_peca       USING(peca)
								WHERE os = $os
								AND  tbl_peca.troca_obrigatoria IS TRUE;";

					$res4 = pg_exec($con,$sql4);

					if (pg_numrows($res4) > 0) {
						$troca_obrigatoria_peca = pg_result($res4,0,'troca_obrigatoria');
					}

					if ($troca_obrigatoria == 't') {

						if ($login_fabrica == 51) {
							$temp .= "<b style='color:#000000;font-size:12px'>Caso o produto já tenha sido consertado a OS poderá ser fechada através da tela de \"Fechamento OS\". Caso a OS necessite da peça lançada para reparo do produto favor aguardar a liberação da OS pelo fabricante.<br>
							Qualquer dÃºvida favor entrar em contato com o fabricante através do telefone (11) 2062-7875.</b>";

						} else {

							$temp .= "<b style='color:#000000;font-size:12px'> O Produto desta O.S. necessita de troca.<br />Por favor, aguarde!</b>";

						}

					}

					if ($troca_obrigatoria_peca == 't') {
						$temp .= "<b style='color:#000000;font-size:12px'> Pela peça selecionada, esta OS estará agora sob intervenção da Assistência Técnica da Fábrica.</b>";

						if ($login_fabrica == 51 and $troca_obrigatoria != 't') { //HD 425278

							/*							$temp .= "<b style='color:#000000;font-size:12px'> Você poderá retirar da intervençao na consulta da OS caso tenha consertado o produto, ou entre em contato com o Gama Italy através do fone (11) 2940 7400 para receber orientações.</b>";*/
							$temp .= "<p style='color:#000000;font-size:12px;font-weight:bold'>Caso o produto já tenha sido consertado a OS poderá ser fechada através da tela de \"Fechamento OS\". Caso a OS necessite da peça lançada para reparo do produto favor aguardar a liberação da OS pelo fabricante.<br>
							Qualquer dúvida favor entrar em contato com o fabricante através do telefone (11) 2062-7875</p>"; //HD 425278 -  Sérgio pediu para não alterar
						} else {

							$temp .= "<b style='color:#000000;font-size:12px'> Ela não poderá ser alterada até sua liberação.<br />Aguarde a fábrica entrar em contato.</b>";

						}

					}

				} else if ($troca_obrigatoria == 't') {

					$temp .= "<b style='color:#000000;font-size:12px'> Este produto deve ser trocado.
							<br />Aguarde a fábrica entrar em contato.</b>";

				} else if (in_array($login_fabrica,[104,105,120,122,201])) {
					$temp .= "<b style='color:#000000;font-size:12px'> $observacao </b>";
				}else{

					$temp .= "<b style='color:#000000;font-size:12px'> Pela peça selecionada, esta OS estará agora sob intervenção da Assistência Técnica da Fábrica. Ela não poderá ser alterada até sua liberação.<br />Aguarde a fábrica entrar em contato.</b>";

				}

			}

			if ($status_os == "65") {
				$temp = "<b style='font-size:14px;color:red'>INTERVENÇÃO DA ASSISTÊNCIA TÉCNICA DA FÁBRICA</b><br /><br />
                         <b style='color:#000000;font-size:12px'>O produto desta OS deve ser reparado pela Assistência Técnica da Fábrica.<br />Se o produto ainda não foi enviado, por favor, enviar para o reparo.  <a href='os_devolucao_fabio.php?os=$os'>CLIQUE AQUI</a>.</b>";

			}

			if ($status_os == "72") {

				$temp = "<b style='font-size:14px;color:red'>INTERVENÇÃO DO SAP</b><br /><br />
							<b style='color:#000000;font-size:12px'>Pela peça selecionada, esta OS estará agora sob intervenção do SAP. <br />Aguarde a fábrica analisar a solicitação da peça.</b>";

			}

			if ($status_os == "87") {

				if ($login_fabrica == 1) {

					$temp = "<b style='font-size:16px;color:red'>OS EM INTERVENÇÃO</b><br /><br />
							<b style='color:#000000;font-size:14px'>Pela peça selecionada, esta OS estará agora sob intervenção do departamento de suprimentos. <br /><u style='color:#0767F8'>Gentileza entrar em contato com o Suporte de sua Região</u>.</b>";

				} else {

					$temp = "<b style='font-size:14px;color:red'>OS EM INTERVENÇÃO</b><br /><br />
							<b style='color:#000000;font-size:12px'>Pela peça selecionada, esta OS estará agora sob intervenção do departamento de suprimentos. <br />Aguarde a fábrica analisar a solicitação da peça.</b>";

				}

			}

			if ($status_os == "116") {

				$temp = "<b style='font-size:14px;color:red'>INTERVENÇÃO DE CARTEIRA</b><br /><br />
							<b style='color:#000000;font-size:12px'>Pela peça selecionada, esta OS estará agora sob intervenção. <br />Aguarde a fábrica analisar a solicitação da peça.</b>";

			}

			if ($status_os == "171") {

				$temp = "<b style='font-size:14px;color:red'>INTERVENÇÃO DE CUSTOS ADICIONAIS</b><br /><br />
							<b style='color:#000000;font-size:12px'>OS com custos adicionais, esta OS estará agora sob intervenção. <br />Aguarde a fábrica analisar a solicitação da peça.</b>";

			}
			if (strlen($temp) > 0 OR strlen($msg_inter) > 0) {
				$msg_intervencao  = "<center>";

				if ($login_fabrica == 1) {

					$msg_intervencao .= "<div style='font-family:verdana;border:3px solid #EF0C11;padding:10px;width:650px;align:center' align='center'>";

				} else {

					if ($login_fabrica == 51 or $login_fabrica == 35) {
						$bg_color = "background-color:#FCF0D8;";
						$border = "border:1px solid #808080;";
					} else {
						$border = "border:1px dashed #666666;";
					}

					$msg_intervencao .= "<div style='font-family:verdana arial helvetica sans sans-serif;padding:10px;width:750px;$border align:center;$bg_color' align='center'>";

				}
				$msg_intervencao .= $temp;
				$msg_intervencao .= $msg_inter;
				$msg_intervencao .= "</div></center>";

			}

		}

	}

}

if ($sistema_lingua == 'ES') $title = "Finalización de lanzamiento de itens en la Orden de Servicio ";
else                         $title = "Finalização de lançamento de itens na Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

if ($login_fabrica == 14) { // HD 35365
	$mostra = 0;
	$sql="SELECT sua_os from tbl_os where os=$os and fabrica=$login_fabrica and data_fechamento is not null";
	$res=pg_exec($con,$sql);
	if(pg_numrows($res) >0) $mostra = 1;
}

if ($mostra == 1) { ?>
<link rel="stylesheet" type="text/css" href="css/ext-all.css" />
<script type="text/javascript" src="js/ext-base.js"></script>
<script type="text/javascript" src="js/ext-Domhelp.js"></script>

<script language="JavaScript">

Ext.fechamento = function() {
    var msgCt;

    function createBox(t, s,os) {
        return ['<div class="msg">',
                '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
                '<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3>',t,os,'</h3>',s, '</div></div></div>',
                '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
                '</div>'].join('');
    }
    return {
        msg : function(title, format,os){
            if(!msgCt){
                msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
            }
            msgCt.alignTo(document, 't-t');
            var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
            var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s,os)}, true);
            m.slideIn('t',{
                        duration: .3,
                        easing: 'easeIn'}).pause(2).ghost("t", {remove:true});
        },

        init : function(){
            var t = Ext.get('exttheme');
            if(!t){ // run locally?
                return;
            }
            var theme = Cookies.get('exttheme') || 'aero';
            if(theme){
                t.dom.value = theme;
                Ext.getBody().addClass('x-'+theme);
            }
            t.on('change', function(){
                Cookies.set('exttheme', t.getValue());
                setTimeout(function(){
                    window.location.reload();
                }, 350);
            });

            var lb = Ext.get('lib-bar');
            if(lb){
                lb.show();
            }
        }
    };
}();

Ext.onReady(function() {
	Ext.fechamento.msg('A OS ', 'foi fechada com sucesso','<? echo $os; ?>');
});

</script>
<? } ?>
<link rel="stylesheet" type="text/css" href="css/reset.css" />
<link rel="stylesheet" type="text/css" href="css/fonts.css" />
<link rel="stylesheet" type="text/css" href="css/reset-fonts-grids.css" />

<style>
	/**
	 * Importado do css/css.css
	 */
	#container {
		width: 750px;
		border: 0px;
		padding:0px 0px 0px 0px;
		margin:10px 0px 0px 0px;
		background-color: white;
		font: bold x-small Verdana, Arial, Helvetica, sans-serif;
	}

	.page {
		width: 100%;
		border: 1px dotted #000000;
		text-align: left;
		}

	.contentleft {
		text-align: left;
		background: transparent;
		display: inline-block;
		color: #63798D;
		}

	.contentleft2 {
		padding-right: 5px;
		float:left;
		background: transparent;
		font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
		display: inline-block;
	}

	.contentcenter {
		padding-left: 5px;
		text-align: left;
		clear: both;
		width: 100%;
	}
	h1 {
		font: bold small Verdana, Arial, Helvetica, sans-serif;
		color: #888888;
		margin: 0px;
		padding: 0px;
		text-transform: uppercase;
		line-height: 1.6em;
	}

	h2 {
		background-color: #BBBBBB;
		text-align:left;
		color: #fff;
		font-weight: bold;
		font-size:    xx-small;
		}

	h3 {
		text-align: left;
		font: normal x-small Verdana, Arial, Helvetica, sans-serif;
		color: #63798D;

		
	}

	h4 {
		font: bold small Verdana, Arial, Helvetica, sans-serif;
		color: #63798D;
	}

	h5 {
		background-color: #BBBBBB;
		text-align:left;
		color: #fff;
		font-weight: BOLD;
		font-size:    xx-small;
	}

	img {
		border: 0px;
	}

	/* END */

	.msg .x-box-mc {
		font-size:14px;
	}
	#msg-div {
		position:absolute;
		left:35%;
		top:10px;
		width:250px;
		z-index:20000;
	}
	.x-grid3-row-body p {
		margin:5px 5px 10px 5px !important;
	}
</style><?php

if (strlen($msg_intervencao) > 0) {
	if ($login_fabrica == 1) {
		echo "<script language='JavaScript'>alert('OS em intervenção. Gentileza, entre em contato com o Suporte de sua região');</script>";
	}
 	echo "<br />$msg_intervencao<br />";
}

//HD4981
if ($login_fabrica == 15) {?>
	<br />
	<table width='600' border='0' align='center' cellpadding='3' cellspacing='5' align='center' bgcolor='#FFFFCC'>
		<tr>
			<td align='center' style='font-size: 10px'>
				<b>AVISO</b><br /><br />Para receber a mão-de-obra referente a esta Ordem de Serviço (OS) a mesma deverá ser <b>finalizada.</b><br />
				Para <b>finalizar</b> uma OS clique na aba 'O. Serviço' e acesse o link <b><a href='os_fechamento.php?sua_os=<? echo $sua_os; ?>&btn_acao_pesquisa=continuar'>Fechamento de Ordem de Serviço</a></b></p>
			</td>
		</tr>
	</table><?php
}

$sql = "SELECT os
		FROM tbl_os_troca_motivo
		WHERE os = $os ";
$res = pg_exec($con,$sql);

if ($login_fabrica == 20 AND pg_numrows($res) > 0) {

	$troca = true;

	$sql = "SELECT  tbl_os_status.observacao,
					tbl_admin.nome_completo ,
					TO_CHAR(data,'DD/MM/YYYY') AS data
			FROM tbl_os_status
			JOIN tbl_admin     USING(admin)
			WHERE os = $os
			AND status_os = 93";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$aprovado_observaocao = pg_result($res,0,0);
		$aprovado_admin       = pg_result($res,0,1);
		$aprovado_data        = pg_result($res,0,2);
		$troca_aprovada       = TRUE;
	}?>

	<style>
		.Tabela{
			border:1px solid #d2e4fc;
		/*	background-color:<?=$cor;?>;*/
		}
	</style>
	<table width="700" border="0" cellspacing="2" cellpadding="0" class='tabela' align='center'>
		<tr >
			<? if ($sistema_lingua=='ES') { ?>
				<td><img src="imagens/botoes/os.jpg" align='left'> <b><font size='2' color='FF9900'>OS <?=$os?> de cambio en garantí­a grabada, favor entrar en contacto con el fabricante para solicitar aprobación.</b></font></td>
			<? } else { ?>
				<td><img src="imagens/botoes/os.jpg" align='left'> <b><font size='2' color='FF9900'>
				<?
				if(isset($troca_aprovada)) echo "OS $os de troca de produto APROVADA. <br />Favor imprimir está folha e enviar com o produto.";
				else echo "OS $os de troca em garantia gravada, favor aguardar aprovação e orientação do fabricante.";
				?>
				</b></font></td>
			<? } ?>
		</tr>
		<tr >
			<? if ($sistema_lingua=='ES') { ?>
				<td><b><font size='1'>* Fue enviado un email para el fabricante su pedido</font></b></td>
			<? } else { ?>
				<td><b><font size='1'>* Foi enviado um e-mail para o responsável pela aprovação.</font></b></td>
			<? } ?>
		</tr>
	</table><?php
}

if ($login_fabrica == 14) {//HD 212179

	$sql_dez = "SELECT tbl_os_item.obs as obs
                  FROM tbl_os_item
                  JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                 WHERE tbl_os_produto.os = $os
                   AND tbl_os_item.obs   = '### PEÇA INFERIOR A 10% DO VALOR DE MÃO-DE-OBRA ###'";

	$res_dez = pg_exec($con, $sql_dez);

	if (pg_numrows($res_dez) > 0) {?>
		<div id="container">
			<font color="red"><b>Esta peça não será reposta em garantia conforme regra de reposição de peças</b></font>
		</div><?php
	}

}?>

<center>
<div id="container">
	<form name="frm_os" method="post" action="<? echo $_SERVER['PHP_SELF'] ?>">
	<input type="hidden" name="os" value="<? echo $_GET['os'] ?>">
	<div class="page">
		<h2><?php
		if($sistema_lingua=='ES')echo "Informaciones sobre la orden de servicio";
		else                     echo "Informações sobre a Ordem de Serviço";?>
		</h2>
		<div class="contentcenter">
			<div class="contentleft2" style="width: 150px; "><?php
				if ($sistema_lingua=='ES') echo "OS FABRICANTE";
				else                       echo "OS FABRICANTE";?>
			</div>
			<div class="contentleft2" style="width: 150px; "><?php
				if ($sistema_lingua=='ES') echo "FECHA DE ABERTURA";
				else                       echo "DATA DE ABERTURA";?>
			</div><?php
			if (isset($troca_aprovada)) {?>
				<div class="contentleft2" style="width: 160px; "><?php
					if ($sistema_lingua=='ES') echo "PROMOTOR/ APROVADOR";
					else                       echo "PROMOTOR/ APROVADOR";?>
				</div>
				<div class="contentleft2" style="width: 150px; "><?php
					if ($sistema_lingua=='ES') echo "FECHA DE APROBACIÓN";
					else                       echo "DATA DA APROVAÇÃO";?>
				</div><?php
			}?>
		</div>

		<div class="contentcenter">
			<div class="contentleft" style="width: 150px;font:75%">
				<? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?>
			</div>
			<div class="contentleft" style="width: 150px;font:75%">
				<? echo $data_abertura ?>
			</div>
			<?
			if(isset($troca_aprovada)){
			?>
			<div class="contentleft" style="width: 160px;font:75%">
				<? echo $aprovado_admin ?>
			</div>
			<div class="contentleft" style="width: 150px;font:75%">
				<? echo $aprovado_data ?>
			</div>
			<?
			}
			?>
		</div>
	</div>

</div>
<br />



<?
/*OS Metais - HD47045 */
if ($consumidor_revenda <> 'R' or ($login_fabrica == 1 and $tipo_os==13)) { ?>
<div id="container">
<div class="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Informaciones sobre el CLIENTE";
		else                     echo "Informações sobre o CONSUMIDOR";
		?>
	</h2>
	<div class="contentcenter">
		<div class="contentleft2" style="width: 250px; ">
			<?
				if($sistema_lingua=='ES')echo "NOMBRE DEL CLIENTE";
				else                     echo "NOME DO CONSUMIDOR";
			?>
		</div>
		<div class="contentleft2" style="width: 150px; ">
			<?
			if($sistema_lingua=='ES')echo "CIUDAD";
			else                     echo "CIDADE";
			?>

		</div>
		<div class="contentleft2" style="width: 80px; ">
			<?
				if($sistema_lingua=='ES')echo "PROVINCIA";
				else                     echo "ESTADO";
			?>
		</div>
		<div class="contentleft2" style="width: 130px; ">
			<?
				if($sistema_lingua=='ES')echo "TELÉFONO";
				else                     echo "FONE";
			?>
		</div>
	</div>

	<div class="contentcenter">
		<div class="contentleft" style="width: 250px;font:75%">
			<? echo (!empty($consumidor_nome)) ? $consumidor_nome : "&nbsp;"; ?>
		</div>
		<div class="contentleft" style="width: 150px;font:75%">
			<? echo (!empty($consumidor_cidade)) ? $consumidor_cidade : "&nbsp;"; ?>
		</div>
		<div class="contentleft" style="width: 80px;font:75%">
			<? echo (!empty($consumidor_estado)) ? $consumidor_estado : "&nbsp;"; ?>
		</div>
		<div class="contentleft" style="width: 130px;font:75%">
			<? echo (!empty($consumidor_fone)) ? $consumidor_fone : "&nbsp;"; ?>
		</div>
	</div>
</div>
</div>
<br />
<? } ?>



<div id="container">
<div class="page">
	<h2><?
		if($sistema_lingua=='ES')echo "Infomaciones sobre el DISTRIBUIDOR";
		else                     echo "Informações sobre a REVENDA";
		?>
	</h2>
	<div class="contentcenter">
		<div class="contentleft2" style="width: 150px; ">
			<?
				if($sistema_lingua=='ES')echo "IDENTIFICACIÓN DISTRIBUIDOR";
				else                     echo "CNPJ REVENDA";
			?>
		</div>
		<div class="contentleft2" style="width: 150px; ">
			<?
				if($sistema_lingua=='ES')echo "NOMBRE DEL DISTRIBUIDOR";
				else                     echo "NOME DA REVENDA";
			?>
		</div>
		<div class="contentleft2" style="width: 150px; ">
			<?
				if($sistema_lingua=='ES')echo "FACTURA COMERCIAL";
				else                     echo "NOTA FISCAL N.";
			?>
		</div>
		<div class="contentleft2" style="width: 130px; ">
			<?
				if($sistema_lingua=='ES')echo "FECHA DE LA FACTURA";
				else                     echo "DATA DA N.F.";
			?>
		</div>
	</div>
	<div class="contentcenter">
		<div class="contentleft" style="width: 150px;font:75%">
			<? echo $revenda_cnpj ?>
		</div>
		<div class="contentleft" style="width: 150px;font:75%">
			<? echo (!empty($revenda_nome)) ? $revenda_nome : "&nbsp;"; ?>
		</div>
		<div class="contentleft" style="width: 150px;font:75%">
			<? echo (!empty($nota_fiscal)) ? $nota_fiscal : "&nbsp;"; ?>
		</div>
		<div class="contentleft" style="width: 130px;font:75%">
			<? echo $data_nf ?>
		</div>
	</div>
</div>

</div>
<br />


<div id="container">
<div class="page">
	<h2><?php
		if ($sistema_lingua == 'ES') echo "Informaciones del PRODUCTO";
		else                         echo "Informações sobre o PRODUTO";?>
	</h2>
	<div class="contentcenter">
		<div class="contentleft2" style="width: 100px; "><?php
			if ($sistema_lingua == 'ES') echo "REFERENCIA";
			else                         echo "REFERÊNCIA";?>
		</div>
		<div class="contentleft2" style="width: 250px; "><?php
			if ($sistema_lingua == 'ES') echo "DESCRIPCIÓN ";
			else                         echo "DESCRIÇÃO";?>
		</div><?php
		if ($login_fabrica == 1) {?>
			<div class="contentleft2" style="width: 75px; ">
				VOLTAGEM
			</div>
			<div class="contentleft2" style="width: 125px; ">
				CÓD. FABRICAÇÃO
			</div><?php
		}?>
		<?php if($login_fabrica <> 127){  ?>
		<div class="contentleft2" style="width: 120px; "><?php
			if ($sistema_lingua == 'ES') echo "SERIE";
			else                         echo "SÉRIE";?>
		</div>
		<?php } ?>
		<?php if(in_array($login_fabrica, array(11,172))) { ?> <div class="contentleft2" style="width: 120px;"> CÓDIGO INTERNO </div> <?php } ?>
	</div>
	<div class="contentcenter">
		<div class="contentleft" style="width: 100px;font:75%">
			&nbsp;<? echo $produto_referencia ?>
		</div>
		<div class="contentleft" style="width: 250px;font:75%">
			&nbsp;<? echo $produto_descricao ?>
		</div>
		<? if ($login_fabrica == 1) { ?>
		<div class="contentleft" style="width: 75px;font:75%">
			&nbsp;<? echo $produto_voltagem ?>
		</div>
		<div class="contentleft" style="width: 125px;font:75%">
			&nbsp;<? echo $codigo_fabricacao ?>
		</div>
		<? } ?>
		<div class="contentleft" style="width: 120px; font:75%">
			<? echo $serie ?>
		</div>
		<?php if(in_array($login_fabrica, array(11,172))) { ?> <div class="contentleft" style="width: 120px;"> <?php echo $versao; ?> </div> <?php } ?>
	</div>
</div>

</div>
<br />
<?php
if (!isset($troca)) {?>
	<div id="container">
		<div class="page">
			<h2><?
				if($sistema_lingua=='ES')echo "Falla informada por el cliente";
				else {
					echo "Defeito Apresentado";
					if ($consumidor_revenda <> 'R') echo " pelo Cliente";
				}
				?>
			</h2>
			<div class="contentcenter">
				<div class="contentleft" style="width: 650px;font:75%">
			<?	if($pedir_defeito_reclamado_descricao == 't'){
					if(strlen($defeito_reclamado) == 0) {
						echo $defeito_reclamado_descricao;
					}else{
						echo $defeito_reclamado;
					}
				}else{
						echo $defeito_reclamado;
				}?>
				</div>
			</div>
		</div>
	</div><?php
	if ($login_fabrica == 15 or $login_fabrica == 74) {?>
		<div id="container">
			<div class="page">
			<? if($login_fabrica == 15){ ?>
				<h2>
					Defeito apresentado pelo Técnico
				</h2>
				<div class="contentcenter">
					<div class="contentleft" style="width: 200px; " nowrap>
						DEFEITO CONSTATADO
					</div>
			<? } ?>
					<div id="<?if($login_fabrica==74) echo 'page'; else echo 'contentleft2'; ?>" style="width: <?if($login_fabrica==74) echo '700px'; else echo '250px'; ?> ">
						<h2>SOLUÇÃO</h2>
					</div>
				</div>
				<div class="contentcenter">
				<? if($login_fabrica == 15){ ?>
					<div class="contentleft" style="width: 200px;font:75%">
						<? echo $defeito_constatado ?>
					</div>
				<? } ?>
					<div class="contentleft" style="width: 250px;font:75%">
						<? echo $solucao_os ?>
					</div>
				</div>
			</div>
		</div><?php
	}?>
	<div id="container">
		<div class="page">
			<h2><?php
				if ($sistema_lingua=='ES') echo "Aparencia del producto";
				else                       echo "Aparência Geral do Produto";?>
			</h2>
			<div class="contentcenter">
				<div class="contentleft" style="width: 650px;font:75%">
					<?php echo $aparencia_produto ?>
				</div>
			</div>
		</div>
	</div>
	<br />
	<div id="container">
		<div class="page">
			<h2><?php
			if ($sistema_lingua == 'ES') echo "Accesorios dejados por el cliente";
			else {
				echo "Acessórios Deixados";
				 if ($consumidor_revenda <> 'R') echo " pelo Cliente";
			}?>
			</h2>
			<div class="contentcenter">
				<div class="contentleft" style="width: 650px;font:75%">
					<? echo $acessorios; ?>
				</div>
			</div>
		</div>
	</div>
	<br /><?php
}

$sql = "SELECT os
		FROM tbl_os_troca_motivo
		WHERE os = $os ";

$res = pg_exec($con,$sql);

if ($login_fabrica == 20 AND pg_numrows($res) > 0) {
	$motivo1 = "Não são fornecidas peças de reposição para este produto";
	$motivo2 = "Há peça de reposição, mas está em falta";
	$motivo3 = "Vicio do produto";
	$motivo4 = "Divergência de voltagem entre embalagem e produto";
	$motivo5 = "Informações adicionais";
	$motivo6 = "Informações complementares";
	$troca = true;?>

<div id="container">
	<div class="page">
		<h2><?php

		if ($sistema_lingua=='ES') echo "Informações sobre o MOTIVO DA TROCA";
		else                       echo "Informações sobre o MOTIVO DA TROCA";

		$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
						tbl_causa_defeito.codigo        AS causa_codigo     ,
						tbl_causa_defeito.descricao     AS causa_defeito
				FROM   tbl_os_troca_motivo
				JOIN   tbl_servico_realizado USING(servico_realizado)
				JOIN   tbl_causa_defeito     USING(causa_defeito)
				WHERE os     = $os
				AND   motivo = '$motivo1'";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {

			$identificacao1 = pg_result($res,0,'servico_realizado');
			$causa_defeito1 = pg_result($res,0,'causa_codigo')." - ".pg_result($res,0,'causa_defeito');?>
		</h2>

				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						Data de entrada do produto na assistência técnica
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft" style="width: 200px;font:75%">
						<? echo $data_abertura ?>
					</div>
				</div>

				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						<br /><? echo $motivo1?>
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						Identificação do defeito
					</div>
					<div class="contentleft2" style="width: 250px; ">
						Defeito
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft" style="width: 200px;font:75%">
						<? echo $identificacao1 ?>
					</div>
					<div class="contentleft" style="width: 250px;font:75%">
						<? echo $causa_defeito1 ?>
					</div>
				</div><?php

			}

			$sql = "SELECT
							TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
							pedido                                              ,
							PE.referencia                     AS peca_referencia,
							PE.descricao                      AS peca_descricao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_peca            PE USING(peca)
					WHERE os     = $os
					AND   motivo = '$motivo2'";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res) == 1) {

				$peca_referencia = pg_result($res,0,peca_referencia);
				$peca_descricao  = pg_result($res,0,peca_descricao);
				$data_pedido     = pg_result($res,0,data_pedido);
				$pedido          = pg_result($res,0,pedido);?>

				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						<br /><? echo $motivo2?>
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						Código da Peça
					</div>
					<div class="contentleft2" style="width: 200px; ">
						Data do Pedido
					</div>
					<div class="contentleft2" style="width: 200px; ">
						Número do Pedido
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft" style="width: 200px;font:75%">
						<? echo $peca_referencia ."-". $peca_descricao ?>
					</div>
					<div class="contentleft" style="width: 200px;font:75%">
						<? echo $data_pedido ?>
					</div>
					<div class="contentleft" style="width: 200px;font:75%">
						<? echo $pedido ?>
					</div>
				</div>
				<br /><?php
			}

			$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
							tbl_causa_defeito.codigo        AS causa_codigo     ,
							tbl_causa_defeito.descricao     AS causa_defeito    ,
							observacao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_servico_realizado USING(servico_realizado)
					JOIN   tbl_causa_defeito     USING(causa_defeito)
					WHERE os     = $os
					AND   motivo = '$motivo3'";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res) == 1) {

				$identificacao2 = pg_result($res,0,'servico_realizado');
				$causa_defeito2 = pg_result($res,0,'causa_codigo')." - ".pg_result($res,0,'causa_defeito');
				$observacao1    = pg_result($res,0,'observacao');?>

				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						<br /><? echo $motivo3?>
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						Identificação do Defeito
					</div>
					<div class="contentleft2" style="width: 200px; ">
						Defeito
					</div>
					<div class="contentleft2" style="width: 200px; ">
						Quais as OS's deste produto:
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft" style="width: 200px;font:75%">
						<?php echo $identificacao2 ?>
					</div>
					<div class="contentleft" style="width: 200px;font:75%">
						<?php echo $causa_defeito2 ?>
					</div>
					<div class="contentleft" style="width: 200px;font:75%">
						<?php echo $observacao1 ?>
					</div>
				</div>
				<br /><?php

			}

			$sql = "SELECT observacao
					FROM   tbl_os_troca_motivo
					WHERE os     = $os
					AND   motivo = '$motivo4'";

			$res = pg_exec($con,$sql);

			if (pg_numrows($res) == 1) {
				$observacao2    = pg_result($res,0,'observacao');?>
				<div class="contentcenter">
					<div class="contentleft2" style="width: 200px; " nowrap>
						<br /><?php echo $motivo4?>
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft2" style="width: 650px; " nowrap>
						Qual a divergência:
					</div>
				</div>
				<div class="contentcenter">
					<div class="contentleft" style="width: 200px;font:75%">
						<?php echo $observacao2 ?>
					</div>
				</div>
				<br /><?php
			}?>
	</div>
</div><?php

	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo5'";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$observacao3 = pg_result($res,0,'observacao');?>
		<div id="container">
			<div class="page">
				<h2><?=$motivo5?></h2>
				<div class="contentcenter">
					<div class="contentleft" style="width: 650px;font:75%"><? echo $observacao3;?></div>
				</div>
			</div>
		</div>
		<br /><?php
	}

	/* HD 49816 - 5/11/2008 */
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo6'";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$observacao4    = pg_result($res,0,'observacao');
		$observacao4 = wordwrap($observacao4, 170, '<br/>', true);	
	?>
		<div id="container">
			<div class="page">
				<h2><?=$motivo6?></h2>
				<div class="contentcenter">
					<div class="contentleft" style="width: 650px;font:75%"><? echo $observacao4;?></div>
				</div>
			</div>
		</div><?php

	}

}

// ITENS
if (strlen ($os) > 0 and !isset($troca)) {
	echo "<div id=\"container\">
		<div id=\"page\">";
	$sql = "SELECT  tbl_os_produto.os_produto                                     ,
					tbl_os_item.qtde                                              ,
					tbl_os_item.peca_original                                     ,
					tbl_defeito.descricao AS defeito_descricao                    ,
					tbl_servico_realizado.servico_realizado                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_peca.referencia                                           ,
					tbl_peca.descricao                                            ,
					tbl_peca.peca_critica                                         ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao
			FROM	tbl_os_produto
			JOIN	tbl_os_item      ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN	tbl_peca         ON tbl_peca.peca             = tbl_os_item.peca
			JOIN	tbl_lista_basica ON  tbl_lista_basica.produto = tbl_os_produto.produto
									 AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN tbl_defeito           USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_produto      ON tbl_os_produto.produto    = tbl_produto.produto
			WHERE   tbl_os_produto.os = $os ";

	$sql = "(	SELECT  tbl_os_produto.os_produto                                              ,
						tbl_os_item.os_item                     AS item                           ,
						tbl_os_item.qtde                                                       ,
						NULL                                    AS preco                       ,
						tbl_os_item.peca_original                                              ,
						tbl_os_item.posicao                                                    ,
						tbl_os_item.pedido                                                     ,
						tbl_defeito.descricao                   AS defeito_descricao           ,
						tbl_servico_realizado.servico_realizado                                ,
						tbl_servico_realizado.descricao         AS servico_realizado_descricao ,
						tbl_servico_realizado.troca_de_peca                                    ,
						tbl_peca.peca                                                          ,
						tbl_peca.referencia                                                    ,
						tbl_peca.descricao                                                     ,
						tbl_peca.bloqueada_garantia                                            ,
						tbl_peca.peca_critica                                                  ,
						tbl_peca.devolucao_obrigatoria                                         ,
						tbl_produto.referencia                  AS subproduto_referencia       ,
						tbl_produto.descricao                   AS subproduto_descricao        ,
						tbl_pedido.pedido_blackedecker          AS pedido_blackedecker
				FROM	tbl_os_produto
				JOIN	tbl_os_item      USING (os_produto)
				JOIN    tbl_produto      USING (produto)
				JOIN    tbl_os USING(os)
				JOIN	tbl_peca         USING (peca)
				LEFT JOIN tbl_defeito           USING (defeito)
				LEFT JOIN tbl_servico_realizado USING (servico_realizado)
				LEFT JOIN tbl_pedido            ON tbl_pedido.pedido = tbl_os_item.pedido
				WHERE   tbl_os.fabrica = $login_fabrica
				  AND   tbl_os_produto.os = $os
				  ORDER BY os_item ASC
			) UNION (
				SELECT
					NULL AS os_produto                                                 ,
					tbl_orcamento_item.orcamento_item              AS item             ,
					tbl_orcamento_item.qtde                                            ,
					tbl_orcamento_item.preco                                           ,
					NULL AS peca_original                                              ,
					NULL AS posicao                                                    ,
					tbl_orcamento_item.pedido                                          ,
					tbl_defeito.descricao               AS defeito_descricao           ,
					tbl_servico_realizado.servico_realizado                            ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao     ,
					tbl_servico_realizado.troca_de_peca                                ,
					tbl_peca.peca                                                      ,
					tbl_peca.referencia                                                ,
					tbl_peca.descricao                                                 ,
					tbl_peca.bloqueada_garantia                                        ,
					tbl_peca.peca_critica                                              ,
					tbl_peca.devolucao_obrigatoria                                     ,
					tbl_produto.referencia AS subproduto_referencia                    ,
					tbl_produto.descricao AS subproduto_descricao                      ,
					tbl_pedido.pedido_blackedecker AS pedido_blackedecker
				FROM tbl_os JOIN tbl_orcamento ON tbl_orcamento.os = tbl_os.os
				JOIN tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_peca ON tbl_peca.peca = tbl_orcamento_item.peca
				LEFT JOIN tbl_defeito           USING (defeito)
				LEFT JOIN tbl_servico_realizado USING (servico_realizado)
				LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_orcamento_item.pedido
				WHERE tbl_os.fabrica = $login_fabrica
				  AND tbl_os.os = $os
				ORDER BY tbl_orcamento_item.orcamento_item ASC
			)
				";
	$res = pg_exec($con,$sql);

	if (pg_num_rows($res)) {
	echo "<table width='100%' border='0' cellspacing='0' cellspadding='0'>";
	echo "<tr bgcolor='#cccccc'>";
	if ($os_item_subconjunto == 't') {
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Subconjunto</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>Posição</font></b></td>";
	}
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
	if($sistema_lingua=='ES')echo "Referencia";
	else                     echo "Referência";
	echo "</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
	if($sistema_lingua=='ES')echo "Descripción";
	else                     echo "Descrição";
	echo "</font></b></td>";
	echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
	if($sistema_lingua=='ES')echo "Cant.";
	else                     echo "Qtde";
	echo "</font></b></td>";

	if ($login_fabrica <> 20) {

		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Defecto";
		else                     echo "Defeito";
		echo "</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Servicio";
		else                     echo "Serviço";
		echo "</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Pedido";
		else                     echo "Pedido";
		echo "</font></b></td>";
		echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
		if($sistema_lingua=='ES')echo "Precio";
		else                     echo "Preço";
		echo "</font></b></td>";
	}

    if (in_array($login_fabrica,array(20,51))) {
        echo "<td align='center'><b><font color='#ffffff' face='verdana' size='1'>";
        echo "Retornável";
        echo "</font></b></td>";
    }


	if (pg_numrows($res) > 0) {

		$exibe_legenda = 0;

		for ($i = 0; $i< pg_numrows($res); $i++) {

			$item                  = pg_result($res,$i,'item');
			$qtde                  = pg_result($res,$i,'qtde');
			$preco                 = pg_result($res,$i,'preco');
			$peca_original         = pg_result($res,$i,'peca_original');
			$peca                  = pg_result($res,$i,'peca');
			$referencia            = pg_result($res,$i,'referencia');
			$descricao             = pg_result($res,$i,'descricao');
			$defeito               = pg_result($res,$i,'defeito_descricao');
			$servico               = pg_result($res,$i,'servico_realizado_descricao');
			$cod_servico           = pg_result($res,$i,'servico_realizado');
			$subproduto_referencia = pg_result($res,$i,'subproduto_referencia');
			$subproduto_descricao  = pg_result($res,$i,'subproduto_descricao');
			$posicao               = pg_result($res,$i,'posicao');
			$pedido                = pg_result($res,$i,'pedido');
			$pedido_blackedecker   = pg_result($res,$i,'pedido_blackedecker');
			$bloqueada_garantia    = pg_result($res,$i,'bloqueada_garantia');
			$peca_critica          = pg_result($res,$i,'peca_critica');
			$troca_de_peca         = pg_result($res,$i,'troca_de_peca');
			$devolucao_obrigatoria = pg_result($res,$i,'devolucao_obrigatoria');

			if ($devolucao_obrigatoria == "t") {
				$exibe_legenda++;
				$devolucao_obrigatoria = "Sim";
				$msg_devolucao_obrigatoria = "ATENÇÃO!\\nExistem peças de retorno obrigatório nesta OS.\\nFavor separar estas peças pois elas serão solicitadas dentro do próximo extrato!";
			} else {
                if($login_fabrica == 20 && strlen($item) > 0){
                    $sqlVerificaDev = "
                        SELECT  COUNT(1) AS tem_dev
                        FROM    tbl_lgr_peca_devolucao
                        WHERE   tbl_lgr_peca_devolucao.os_item = $item
                    ";
                    $resVerificaDev = pg_query($con,$sqlVerificaDev);
                    $tem_dev = pg_fetch_result($resVerificaDev,0,tem_dev);

                    if($tem_dev > 0){
                        $exibe_legenda++;
                        $devolucao_obrigatoria = "Sim";
                        $msg_devolucao_obrigatoria = "Informamos que estamos analisando o item abaixo e que na ocorrência de uma substituição em garantia deste item o mesmo deve ser identificado com seu respectivo número de OS e aguardar coleta por parte dos Correios diretamente na sua assistência técnica.";
                    }else{
                        $devolucao_obrigatoria = "Não";
                    }
                }else{
                    $devolucao_obrigatoria = "Não";
				}
			}

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,'descricao'));
			}
			//--=== Tradução para outras linguas ===================================================================

			$cor = ($i % 2 == 0) ? '#f8f8f8' : '#ffffff';

			if ($login_fabrica == 1 AND $status_os == "87" AND $peca_critica == 't') {
				$cor="#FDC6C7";
			}

			if (in_array($login_fabrica,array(20,51)) and $devolucao_obrigatoria == "Sim") {
				$cor="#FFC0D0";
			}

			echo "<tr bgcolor='$cor'>";

			if ($os_item_subconjunto == 't') {
				echo "<td><font face='arial' size='-2'> $subproduto_referencia - $subproduto_descricao </font></td>";
				echo "<td><font face='arial' size='-2'> $posicao </font></td>";
			}

			echo "<td nowrap>";
				echo "<font face='verdana' size='1'>" . $referencia . "</font>";
			echo "</td>";

			echo "<td nowrap>";
				echo "<font face='verdana' size='1'>" . $descricao . "</font>";
			echo "</td>";

			echo "<td nowrap align='center'>";
				echo "<font face='verdana' size='1'>" . $qtde . "</font>";
			echo "</td>";

			if ($login_fabrica <> 20) {

				echo "<td nowrap align='center'>";
					echo "<font face='verdana' size='1'>" . $defeito . "</font>";
				echo "</td>";

				echo "<td>";
				echo "<font face='verdana' size='1'>" . $servico . "</font>";
				echo "</td>";

				echo "<td align='center'>";
				echo "<font face='verdana' size='1'>";
				if ($login_fabrica == 1) echo $pedido_blackedecker;
				else                     echo $pedido;
				echo "</font>";
				echo "</td>";

				echo "<td>";
				echo "<font face='verdana' size='1'>";
				if (strlen($preco) > 0) {
					$preco = number_format($preco,2,",",".");
				}
				echo $preco;
				echo "</font>";
				echo "</td>";
			}

            if (in_array($login_fabrica,array(20,51))) {
                echo "<td align='center'>";
                    echo "<font face='verdana' size='1'>".$devolucao_obrigatoria."</font>";
                echo "</td>";
            }


			echo "</tr>";

			if ($bloqueada_garantia == 't' AND $login_fabrica == 3 and $troca_de_peca == 't'){
				echo "<tr>\n";
					echo "<td colspan='5'>\n";
						echo "<font face='Arial, Verdana, Times, Sans' size='1' color='#000000'><b>";
						//echo "A peça $referencia necessita de autorização da BritÃ¢nia para atendimento em garantia. Para liberação desta peça, favor enviar e-mail para <a href=\"mailto:assistenciatecnica@britania.com.br\">assistenciatecnica@britania.com.br</A>, informando a OS e a justificativa.";
						//alterado por Fabio - 16/03/2007 - chamado 1392
						echo "A peça $referencia necessita de autorização da BritÃ¢nia para atendimento em garantia.";
						echo "</b></font>";
					echo "</td>\n";
				echo "</tr>\n";
			}

			if (strlen($peca_original) > 0) {

				$sql = "SELECT referencia from tbl_peca where peca = $peca_original and fabrica = $login_fabrica";
				$resOriginal = pg_exec($con,$sql);
				$referencia_original = pg_result($resOriginal,0,'referencia');

				echo "<tr bgcolor='$cor'>";
					echo "<td colspan='6'>";
						echo "<font face='Verdana' size='1' color='#CC0066'>";
							echo "A peça <B>$referencia_original</B> digitada pelo posto foi substituÃ­da automaticamente pela peça <B>$referencia</B>";
						echo "</font>";
					echo "</td>";
				echo "</tr>";

			}

			if ($cod_servico == "62" and $login_fabrica == 1 and strlen($pedido) == 0) {

				echo "<tr bgcolor='$cor'>";
					echo "<td colspan='7'>";
						echo "<font face='Verdana' size='2' color='0000ff'><b>";
							echo "O item acima, constará em um pedido de garantia. Toda segunda-feira e quinta-feira o site gera o pedido e envia para a fábrica no horário padrão das 11h30. Para saber o número do pedido que o site gerou e fazer o acompanhamento, clique no menu PEDIDOS e em seguida CONSULTA DE PEDIDOS e LISTAR TODOS OS PEDIDOS.";
						echo "</b></font>";
					echo "</td>";
				echo "</tr>";

			}

		}

	}

	echo "</table>";}?>
	</div><?php
	if (in_array($login_fabrica,array(20,51)) and $exibe_legenda > 0) {
		echo "<br />\n";
		echo "<TABLE width='700px' border='0' cellspacing='0' cellpadding='0' align='center'>\n";
		echo "<TR style='line-height: 16px'>\n";
		echo "<TD width='10' bgcolor='#FFC0D0'>&nbsp;</TD>\n";
		echo "<TD style='padding-left: 10px; font-size: 14px;'><strong>Peças de retorno obrigatório</strong></TD>\n";
		echo "</TR></TABLE>\n";
	}?>
</div>
<br /><?php
} // FIM lista peças

/* Fabio - 09/11/2007 - HD Chamado 7452 */
$sql="SELECT orcamento,
			total_mao_de_obra,
			total_pecas,
			aprovado,
			TO_CHAR(data_aprovacao,'DD/MM/YYYY') AS data_aprovacao,
			TO_CHAR(data_reprovacao,'DD/MM/YYYY') AS data_reprovacao,
			motivo_reprovacao
		FROM tbl_orcamento
		WHERE empresa=$login_fabrica
		AND os = $os";

$resOrca = pg_exec($con,$sql);

if (pg_numrows($resOrca) > 0) {

	$orcamento         = pg_result($resOrca,0,'orcamento');
	$total_mao_de_obra = pg_result($resOrca,0,'total_mao_de_obra');
	$total_pecas       = pg_result($resOrca,0,'total_pecas');
	$aprovado          = pg_result($resOrca,0,'aprovado');
	$data_aprovacao    = pg_result($resOrca,0,'data_aprovacao');
	$data_reprovacao   = pg_result($resOrca,0,'data_reprovacao');
	$motivo_reprovacao = pg_result($resOrca,0,'motivo_reprovacao');

	$total_mao_de_obra = number_format($total_mao_de_obra,2,",",".");
	$total_pecas       = number_format($total_pecas,2,",",".");

	if ($aprovado == 't') {
		$msg_orcamento = "Valor da Mão de Obra: $total_mao_de_obra <br />Valor de Peça: $total_pecas <br />Orçamento aprovado. <br />Data: $data_aprovacao";
	} else if ($aprovado == 'f') {
		$msg_orcamento = "Valor da Mão de Obra: $total_mao_de_obra  <br />Valor de Peça: $total_pecas <br />Orçamento REPROVADO. Motivo: $motivo_reprovacao <br />Data: $data_reprovacao";
	} else {
		$msg_orcamento = "Valor da Mão de Obra: $total_mao_de_obra  <br />Valor de Peça: $total_pecas <br />Orçamento aguardando aprovação.";
	}
}

if (strlen($orcamento) > 0 AND strlen($msg_orcamento) > 0) { ?>
	<div id="container">
		<div class="page">
			<h2>Orçamento</h2>
			<div class="contentcenter">
				<div class="contentleft" style="width: 650px;font:75%"><? echo $msg_orcamento;?></div>
			</div>
		</div>
	</div><?php
}

if (!isset($troca)) {?>
	<div id="container">
		<div class="page">
			<? if ($sistema_lingua=='ES') { ?>
				<h2>Observacion</h2>
			<? } else { ?>
				<h2>Observação</h2>
			<? } ?>
			<div class="contentcenter">
				<div class="contentleft" style="width: 650px;font:75%">
					<?php echo nl2br($obs); ?>
				</div>
			</div>
		</div>
	</div><?php
}

if ($login_fabrica == 19) {

	$sql = "SELECT tbl_laudo_tecnico_os.*
			FROM tbl_laudo_tecnico_os
			WHERE os = $os
			ORDER BY ordem, laudo_tecnico_os";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<div id='container'>";
			echo "<div id='page'>";
				echo "<h2><b>LAUDO TÉCNICO</b></h2>";
					echo "<div class='contentcenter' style=\"width: 650px;\">";
						echo "<div class='contentleft2' style=\"width: 200px;\">QUESTÃO</div>";
						echo "<div class='contentleft2' style=\"width: 100px;\">AFIRMAÇÃO</div>";
						echo "<div class='contentleft2' style=\"width: 310px;\">RESPOSTA</div>";
					echo "</div>";

				for ($i = 0; $i < pg_numrows($res); $i++) {
					echo "<div class='contentcenter' style=\"width: 650px;\">";

						echo "<div class='contentleft' style=\"width: 200px;font:75%\">";
							echo pg_result($res,$i,titulo);
						echo "</div>";

						echo "<div class='contentleft' style=\"width: 100px;font:75%\">";
							if (pg_result($res,$i,afirmativa) == 't')        echo "Sim";
							else if (pg_result($res,$i,afirmativa) == 'f')   echo "Não";
							else                                             echo "&nbsp;";
						echo "</div>";

						echo "<div class='contentleft' style=\"width: 310px;font:75%\">";
							echo pg_result($res,$i,observacao);
						echo "</div>";

					echo "</div>";
				}
			echo "</div>";
		echo "</div>";

		echo "<BR clear='both'>";

	}

}?>

</form>

</table>

</div>
<BR clear='both'>
<br />

<?php

if ($login_fabrica == "20" and !isset($troca)) {

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$sql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$mao_de_obra = pg_result($res,0,mao_de_obra);
	}

	$sql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {

		$tabela             = pg_result($res,0,'tabela');
		$desconto           = pg_result($res,0,'desconto');
		$desconto_acessorio = pg_result($res,0,'desconto_acessorio');

	}

	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$pecas = pg_result($res,0,0);
		}

	} else {
		$pecas = "0";
	}

	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if ($sistema_lingua=='ES') echo "Valor de Piezas"; else echo "Valor das Peças";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if ($sistema_lingua=='ES') echo "Mano de Obra"; else echo "Mão-de-Obra";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>";
	if ($sistema_lingua=='ES') echo "Impuesto IVA"; else echo "Imposto IVA";
	echo "</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {

		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$produto = pg_result($res,0,0);
		}

		//echo 'peca'.$pecas;
		if ($produto == '20567') {
			$desconto_acessorio = '0.2238';
			$valor_desconto = round( (round($pecas,2) * $desconto_acessorio ) ,2);

		} else {
			$valor_desconto = round( (round($pecas,2) * $desconto / 100) ,2);
		}

		$valor_liquido = $pecas - $valor_desconto ;

	}

	$acrescimo = 0;

	if ($login_pais <> "BR") {

		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$valor_liquido = pg_result($res,0,pecas);
			$mao_de_obra   = pg_result($res,0,mao_de_obra);
		}

		$sql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$imposto_al   = pg_result($res,0,'imposto_al');
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}

	}

	/*HD 9469 - Alteração no cálculo da BOSCH do Brasil*/
	if ($login_pais=="BR") {

		$sqlxx = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$resxx = pg_exec($con,$sqlxx);

		if (pg_numrows($resxx) == 1) {
			$valor_liquido = pg_result($resxx,0,'pecas');
			$mao_de_obra   = pg_result($resxx,0,'mao_de_obra');
		}

	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo;

	$total          = number_format($total,2,",",".")         ;
	$mao_de_obra    = number_format($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format($valor_desconto,2,",",".");
	$valor_liquido  = number_format($valor_liquido ,2,",",".");

		echo "<tr style='font-size: 12px ; color:#000000 '>";
			echo "<td align='right'>" ;
				echo "<font color='#333377'><b>$valor_liquido</b>" ;
			echo "</td>";
			echo "<td align='center'>$mao_de_obra</td>";
			echo "<td align='center'>+ $acrescimo</td>";
			echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
		echo "</tr>";

	echo "</table>";

}

if (isset($troca) AND isset($troca_aprovada)) {

    if($login_fabrica == 20){
        echo "<table width='700' style='font-size:10px;font-family:verdana,sans;'><tr><td>
	<b>PROCEDIMENTO PARA EMISSÃO DA NF DE ENVIO DO PRODUTO:<br /></b>
1. Imprima c&oacute;pia da OS para enviar com o produto.                                                  </br>
2. Natureza da opera&ccedil;&atilde;o: \"Remessa de Pe&ccedilas em Garantia\" / \"Outras Sa&iacutedas n&atilde;o Especificadas\".       </br>
3. \"CFOP\" 5.949 (dentro do estado SP) 6.949 demais estados (fora do estado de SP).                 </br>
4. Informar os 10 d&iacute;gitos (c&oacute;digo comercial) do produto, bem como sua denomina&ccedil;&atilde;o.                 </br>
5. Informar no corpo da Nota Fiscal o local de entrega: PT/SBZ-ASA - Ca 370 - Laborat&oacute;rio QMM.     </br>
6. O frete ser&aacute; pago pela Bosch, desde que solicitado coleta da mercadoria conforme Circular Informativa: \"CI_26_2013_Unifica&ccedil;&atilde;o no Processo de Coleta - Via Transportadora\", exclusivo para garantia. Mercadorias encaminhadas por outro meio com frete a cobrar ser&atilde;o devolvidas e consequentemente os custos ser&atilde;o por conta do solicitante. </br>
7. Utilize embalagem em bom estado.                                                                </br>
8. Valor do produto na Nota fiscal: verifique a regra vigente atrav&eacute;s das Circulares Informativas. </br>
	</td></tr></table>";
    }else{
        echo "<table width='700' style='font-size:10px;font-family:verdana,sans;'><tr><td>
	<b>PROCEDIMENTO PARA EMISSÃO DA NF DE ENVIO DO PRODUTO:<br /></b>
	1. Imprima cópia da OS para enviar com o produto.<br />
	2. Natureza da operação: \"Remessa de Peças em Garantia\" / \"Outras SaÃ­das não Especificadas\".<br />
	3. \"cairo_font_options_create(oid)\" 5.949 (dentro do estado SP) 6.949 demais estados. (fora do estado de SP)<br />
	4. Informar os 10 dÃ­gitos (código comercial) do produto que irá retornar, bem como sua denominação.<br />
	5. Destacar somente ICMS, apenas para MICRO EMPRESA \"ME\" não é necessário destacar o ICMS.<br />
	6. Não destacar IPI.<br />
	7. Informar no corpo da Nota Fiscal o local de entrega: PT-RLA/ASA1 - Ca370 - Laboratório QMM - Henrique / Gastão.<br />
	8. O frete será pago pela Bosch, desde que solicitado coleta da mercadoria através da DHL, telefone número 0800 701 47 02, mercadorias encaminhadas por outra transportadora/meio com frete a cobrar serão devolvidas e consequentemente os custos serão por conta do solicitante.<br />
	9. Utilize embalagem em bom estado. <br />
	10. Valor do produto na Nota fiscal: deverá ser colocado com o preço de lista menos 33%, ex: F 012 8003 AD R$ 160,00 (Preço de lista), valor a ser colocado na NF R$ 107,20. Mercadorias que vierem com preço diferente do especificado serão notificadas para providenciarem carta de correção atrasando o retorno.<br />
	</td></tr></table>
";
    }
}?>

<p>

<?php
if ($login_fabrica == "20" or $login_fabrica == "30" or $login_fabrica == "50") {

	$pecas              = 0;
	$mao_de_obra        = 0;
	$tabela             = 0;
	$desconto           = 0;
	$desconto_acessorio = 0;

	$sql = "SELECT mao_de_obra
			FROM tbl_produto_defeito_constatado
			WHERE produto = (
				SELECT produto
				FROM tbl_os
				WHERE os = $os
			)
			AND defeito_constatado = (
				SELECT defeito_constatado
				FROM tbl_os
				WHERE os = $os
			)";

	/* HD 19054 */
	if ($login_fabrica == 50 || $login_fabrica == 20) {
		$sql = "SELECT mao_de_obra
				FROM tbl_os
				WHERE os = $os
				AND fabrica = $login_fabrica";
	}

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$mao_de_obra = pg_result($res,0,'mao_de_obra');
	}

	$sql = "SELECT  tabela,
					desconto,
					desconto_acessorio
			FROM  tbl_posto_fabrica
			WHERE posto = $login_posto
			AND   fabrica = $login_fabrica";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 1) {
		$tabela             = pg_result($res,0,'tabela');
		$desconto           = pg_result($res,0,'desconto');
		$desconto_acessorio = pg_result($res,0,'desconto_acessorio');
	}

	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$pecas = pg_result($res,0,0);
		}

	} else {
		$pecas = "0";
	}

	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";

	if ($login_fabrica == 50 or $login_fabrica == 30) {

		/* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
			caso este for igual a 0*/
		$sql = "SELECT tbl_os.qtde_km_calculada
				FROM tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				WHERE tbl_os.os = $os
					AND tbl_os.fabrica = $login_fabrica";

		$res = pg_exec($con,$sql);
		$qte_km_vd = pg_result($res,0,'qtde_km_calculada');

		if ($qte_km_vd <> 0) {
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("valor.deslocamento",$con,$cook_idioma);
			echo "</b></td>";
		}

		if ($login_fabrica == 30) {
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("valor.das.pecas",$con,$cook_idioma);
			echo "</b></td>";
		}

		echo "<td align='center' colspan='2' bgcolor='#E1EAF1'><b>".traduz("mao.de.obra",$con,$cook_idioma)."</b></td>";
		echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";

	} else {

		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		fecho ("valor.das.pecas",$con,$cook_idioma);
		echo "</b></td>";
		echo "<td align='center' bgcolor='#E1EAF1'><b>";
		fecho ("mao.de.obra",$con,$cook_idioma);
		echo "</b></td>";

		if ($sistema_lingua == 'ES') {
			echo "<td align='center' bgcolor='#E1EAF1'><b>";
			fecho ("desconto.iva",$con,$cook_idioma);
			echo "</b></td>";
		}

		echo "<td align='center' bgcolor='#E1EAF1'><b>".traduz("total",$con,$cook_idioma)."</b></td>";

	}
	echo "</tr>";

	$valor_liquido = 0;

	if ($desconto > 0 and $pecas <> 0) {
		$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 1) {
			$produto = pg_result($res,0,0);
		}
		//echo 'peca'.$pecas;
		if( $produto == '20567' ){
			$desconto_acessorio = '0.2238';
			$valor_desconto = round((round($pecas,2) * $desconto_acessorio ) ,2);

		} else {
			$valor_desconto = round((round($pecas,2) * $desconto / 100) ,2);
		}

		$valor_liquido = $pecas - $valor_desconto ;

	}

	if ($login_fabrica == 20) {

		$sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) >0) {
			$sigla_pais = pg_result($res,0,pais);
		}

	}

	$acrescimo = 0;

	if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {

		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$valor_liquido = pg_result($res,0,pecas);
			$mao_de_obra   = pg_result($res,0,mao_de_obra);
		}

		$sql = "select imposto_al  from tbl_posto_fabrica where posto=$login_posto and fabrica=$login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$imposto_al = pg_result($res,0,imposto_al);
			$imposto_al = $imposto_al / 100;
			$acrescimo  = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}

	}

	//Foi comentado HD chamado 17175 4/4/2008

	//HD 9469 - Alteração no cálculo da BOSCH do Brasil
	if ($login_pais=="BR") {

		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$valor_liquido = pg_result($res,0,'pecas');
			//$mao_de_obra   = pg_result($res,0,mao_de_obra);
		}

	}


	if ($login_fabrica == 30) {

		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$valor_liquido = pg_result($res,0,pecas);
			$mao_de_obra   = pg_result($res,0,mao_de_obra);
		}

	}

	/* HD 19054 */
	$valor_km = 0;

	if ($login_fabrica == 50 or $login_fabrica == 30) {

		$sql = "SELECT	tbl_os.mao_de_obra,
						tbl_os.qtde_km_calculada,
						tbl_os_extra.extrato
				FROM tbl_os
				LEFT JOIN tbl_os_extra USING(os)
				WHERE tbl_os.os = $os
				AND   tbl_os.fabrica = $login_fabrica";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$mao_de_obra   = pg_result($res,0,'mao_de_obra');
			$valor_km      = pg_result($res,0,'qtde_km_calculada');
			$extrato       = pg_result($res,0,'extrato');
		}

	}

	$total = $valor_liquido + $mao_de_obra + $acrescimo + $valor_km;

	$total          = number_format($total,2,",",".")         ;
	$mao_de_obra    = number_format($mao_de_obra ,2,",",".")  ;
	$acrescimo      = number_format($acrescimo ,2,",",".")    ;
	$valor_desconto = number_format($valor_desconto,2,",",".");
	$valor_liquido  = number_format($valor_liquido ,2,",",".");
	$valor_km       = number_format($valor_km ,2,",",".");

	echo "<tr style='font-size: 12px ; color:#000000 '>";

	/* HD 19054 */
	if ($login_fabrica == 50 or $login_fabrica == 30) {

		/* HD 24461 - Francisco Ambrozio - ocultar campo Valor Deslocamento,
			caso este for igual a 0*/
		if ($valor_km <> 0) {
			echo "<td align='right'><font color='#333377'><b>$valor_km</b></td>";
		}

		if ($login_fabrica == 30) {
			echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
		}

		echo "<td align='center' colspan='2'>$mao_de_obra</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";

	} else {
		echo "<td align='right'><font color='#333377'><b>$valor_liquido</b></td>" ;
		echo "<td align='center'>$mao_de_obra</td>";
		if($sistema_lingua=='ES')echo "<td align='center'>+ $acrescimo</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	}

	echo "</tr>";

	/* HD 19054 */
	if ($login_fabrica==50 and strlen($extrato)==0){
		echo "<tr style='font-size: 12px ; color:#000000 '>";
		echo "<td colspan='3'>";
		echo "<font color='#757575'>".traduz("valores.sujeito.a.alteracao.ate.fechamento.do.extrato",$con,$cook_idioma) ;
		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	echo "<br />";

}?>

<TABLE cellpadding='5' cellspacing='5'>
	<TR><?php
		//OS METAIS
		if ($login_fabrica == 1 and $tipo_os == 13) {?>
			<TD><a href="os_cadastro_metais_sanitario_new.php"><img src="imagens/<?if($sistema_lingua=="ES")echo "es_";?>btn_lancanovaos.gif"></a></TD><?php
		} else {
            if($login_fabrica == 30){
                $sql = "
                    SELECT  tbl_posto_fabrica.contato_estado
                    FROM    tbl_posto_fabrica
                    WHERE   tbl_posto_fabrica.posto     = $login_posto
                    AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                ";

                $res = pg_query($con,$sql);
                $resultContatoEstado = pg_fetch_result($res,0,contato_estado);
                //$display = ($resultContatoEstado == 'CE') ? "style='display:none;'" : "";
                $display = (in_array($resultContatoEstado,array('CE','BA','RN','MA','SE','PE', 'PB', 'PI', 'AL'))) ? "style='display:none;'" : "";
            }
?>
			<TD><a href="os_cadastro.php" <?=$display?>><img src="imagens/<?if($sistema_lingua=="ES")echo "es_";?>btn_lancanovaos.gif"></a></TD><?php
		}

		//OS METAIS
		if ($login_fabrica == 1 and $tipo_os <> 13) {
			echo "<TD><a href='os_cadastro.php?os=$os'><img src='imagens/btn_alterarcinza.gif'></a></TD>";
		}?>

		<TD><a href="os_print.php?os=<? echo $os ?>" target="blank"><img src="imagens/btn_imprimir.gif"></a></TD><?php

		if (strlen($_SESSION["sua_os_explodida"]) > 0) {
			echo "<TD><a href='os_revenda_explodida_blackedecker.php?sua_os=".$_SESSION["sua_os_explodida"]."'><img src='imagens/";
			echo "btn_voltar.gif'></a></TD>";
			session_destroy();
		} else {
			if($login_fabrica <> 20){
				echo "<TD><a href='os_consulta_lite.php'><img src='imagens/";
				if ($sistema_lingua=="ES") echo "es_";
				echo "btn_voltarparaconsulta.gif'></a></TD>";
			}
		}
		/* hd_chamado=2843341
		if ($login_fabrica == 20) {
			echo "<TD><a href='os_comprovante_servico_print.php?os=$os'><img src='imagens/";
			if ($sistema_lingua=="ES") echo "es_";
			echo "btn_comprovante.gif'></a></TD>";
		}
		*/
		?>
	</TR>
</TABLE>

</center><?php

if ((strlen($msg_devolucao_obrigatoria) > 0) and (in_array($login_fabrica,array(20,51)))) { ?>
	<script language="Javascript">
		alert ("<?php echo $msg_devolucao_obrigatoria; ?>");
	</script><?php
}

include "rodape.php";

?>
