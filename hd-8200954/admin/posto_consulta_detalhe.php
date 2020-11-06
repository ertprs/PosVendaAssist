<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios="call_center";
include 'autentica_admin.php';

if (strlen($_POST["posto"]) > 0) $posto = trim($_POST["posto"]);
if (strlen($_GET["posto"]) > 0)  $posto = trim($_GET["posto"]);


//HD 100300 - Pedido de promoção automatica
$abrir = fopen("../bloqueio_pedidos/libera_promocao_black.txt", "r");
$ler = fread($abrir, filesize("../bloqueio_pedidos/libera_promocao_black.txt"));
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


//hd 56366 alterado fone e fax para pegar da tbl_posto_fabrica
//hd 12301 18/1/2008
if (strlen($posto) > 0) {
	if(in_array($login_fabrica, array(74))){
		$campo_telefone = 'contato_telefones';
	}else{
		$campo_telefone = 'contato_fone_comercial';
	}

	$sql = "SELECT  tbl_posto_fabrica.posto               ,
					tbl_posto_fabrica.codigo_posto        ,
					tbl_posto_fabrica.tipo_posto          ,
					tbl_tipo_posto.descricao AS descricao_tipo_posto,
					tbl_posto_fabrica.transportadora_nome ,
					tbl_posto_fabrica.transportadora      ,
					tbl_posto_fabrica.cobranca_endereco   ,
					tbl_posto_fabrica.cobranca_numero     ,
					tbl_posto_fabrica.cobranca_complemento,
					tbl_posto_fabrica.atendimento         ,
					tbl_posto_fabrica.cobranca_bairro     ,
					tbl_posto_fabrica.cobranca_cep        ,
					tbl_posto_fabrica.cobranca_cidade     ,
					tbl_posto_fabrica.cobranca_estado     ,
					tbl_posto_fabrica.obs                 ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto_fabrica.contato_endereco    AS endereco   ,
					tbl_posto_fabrica.contato_numero      AS numero     ,
					tbl_posto_fabrica.contato_complemento AS complemento,
					tbl_posto_fabrica.contato_bairro      AS bairro     ,
					tbl_posto_fabrica.contato_cep         AS cep        ,
					tbl_posto_fabrica.contato_cidade      AS cidade     ,
					tbl_posto_fabrica.contato_estado      AS estado     ,
					tbl_posto_fabrica.contato_email       AS email      ,
					tbl_posto_fabrica.$campo_telefone     AS fone    ,
					tbl_posto_fabrica.contato_fax            AS fax     ,
					tbl_posto_fabrica.contato_nome        AS contato    ,
					tbl_posto.suframa                     ,
					tbl_posto.item_aparencia              ,
					tbl_posto.capital_interior            ,
					tbl_posto_fabrica.nome_fantasia       ,
					tbl_posto_fabrica.senha               ,
					tbl_posto_fabrica.desconto            ,
					tbl_posto_fabrica.pedido_em_garantia  ,
					tbl_posto_fabrica.pedido_faturado     ,
					tbl_posto_fabrica.digita_os           ,
					tbl_posto_fabrica.banco               ,
					tbl_posto_fabrica.agencia             ,
					tbl_posto_fabrica.conta               ,
					tbl_posto_fabrica.nomebanco           ,
					tbl_posto_fabrica.favorecido_conta    ,
					tbl_posto_fabrica.cpf_conta           ,
					tbl_posto_fabrica.tipo_conta          ,
					tbl_posto_fabrica.obs_conta           ,
					tbl_posto_fabrica.coleta_peca         ,
					tbl_posto_fabrica.reembolso_peca_estoque,
					tbl_posto_fabrica.prestacao_servico   ,
					tbl_posto_fabrica.prestacao_servico_sem_mo,
					tbl_posto_fabrica.atende_comgas       ,
					tbl_posto_fabrica.garantia_antecipada ,
					tbl_posto_fabrica.credenciamento      ,
					tbl_posto_fabrica.parametros_adicionais ,
					tbl_posto_fabrica.escolhe_condicao ,
					tbl_posto_fabrica.contato_telefones,
					tbl_posto_fabrica.contato_cel
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $posto ";

	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) > 0) {
		$posto            = trim(pg_fetch_result($res,0,posto));
		$codigo           = trim(pg_fetch_result($res,0,codigo_posto));
		$nome             = trim(pg_fetch_result($res,0,nome));
		$cnpj             = trim(pg_fetch_result($res,0,cnpj));
		$ie               = trim(pg_fetch_result($res,0,ie));
		if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		$endereco         = trim(pg_fetch_result($res,0,endereco));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_fetch_result($res,0,numero));
		$complemento      = trim(pg_fetch_result($res,0,complemento));
		$bairro           = trim(pg_fetch_result($res,0,bairro));
		$cep              = trim(pg_fetch_result($res,0,cep));
		$cidade           = trim(pg_fetch_result($res,0,cidade));
		$estado           = trim(pg_fetch_result($res,0,estado));
		$email            = trim(pg_fetch_result($res,0,email));
		$fax              = trim(pg_fetch_result($res,0,fax));
		$contato          = trim(pg_fetch_result($res,0,contato));
		$suframa          = trim(pg_fetch_result($res,0,suframa));
		$item_aparencia   = trim(pg_fetch_result($res,0,item_aparencia));
		$obs              = trim(pg_fetch_result($res,0,obs));
		$capital_interior = trim(pg_fetch_result($res,0,capital_interior));
		$tipo_posto       = trim(pg_fetch_result($res,0,tipo_posto));
		$descricao_tipo_posto   = trim(pg_fetch_result($res,0,descricao_tipo_posto));
		$senha            = trim(pg_fetch_result($res,0,senha));
		$desconto         = trim(pg_fetch_result($res,0,desconto));
		$nome_fantasia    = trim(pg_fetch_result($res,0,nome_fantasia));
		$transportadora   = trim(pg_fetch_result($res,0,transportadora));
		$coleta_peca   = trim(pg_fetch_result($res,0,coleta_peca));
		$reembolso_peca_estoque   = trim(pg_fetch_result($res,0,reembolso_peca_estoque));
		$prestacao_servico   = trim(pg_fetch_result($res,0,prestacao_servico));
		$prestacao_servico_sem_mo   = trim(pg_fetch_result($res,0,prestacao_servico_sem_mo));
		$atende_comgas   = trim(pg_fetch_result($res,0,atende_comgas));
		$garantia_antecipada   = trim(pg_fetch_result($res,0,garantia_antecipada));

		$cobranca_endereco    = trim(pg_fetch_result($res,0,cobranca_endereco));
		$cobranca_numero      = trim(pg_fetch_result($res,0,cobranca_numero));
		$cobranca_complemento = trim(pg_fetch_result($res,0,cobranca_complemento));
		//HD 110541
		$atendimento_lenoxx   = trim(pg_fetch_result($res,0,atendimento));
		$cobranca_bairro      = trim(pg_fetch_result($res,0,cobranca_bairro));
		$cobranca_cep         = trim(pg_fetch_result($res,0,cobranca_cep));
		$cobranca_cidade      = trim(pg_fetch_result($res,0,cobranca_cidade));
		$cobranca_estado      = trim(pg_fetch_result($res,0,cobranca_estado));
		$pedido_em_garantia   = trim(pg_fetch_result($res,0,pedido_em_garantia));
		$pedido_faturado      = trim(pg_fetch_result($res,0,pedido_faturado));
		$digita_os            = trim(pg_fetch_result($res,0,digita_os));
		$fone           	  = trim(pg_fetch_result($res,0, fone));
		$banco                = trim(pg_fetch_result($res,0,banco));
		$agencia              = trim(pg_fetch_result($res,0,agencia));
		$conta                = trim(pg_fetch_result($res,0,conta));
		$nomebanco            = trim(pg_fetch_result($res,0,nomebanco));
		$favorecido_conta     = trim(pg_fetch_result($res,0,favorecido_conta));
		$cpf_conta            = trim(pg_fetch_result($res,0,cpf_conta));
		$tipo_conta           = trim(pg_fetch_result($res,0,tipo_conta));
		$obs_conta            = trim(pg_fetch_result($res,0,obs_conta));
		$credenciamento       = trim(pg_fetch_result($res,0,credenciamento));
		$escolhe_condicao     = trim(pg_fetch_result($res,0,escolhe_condicao));
    	$contato_cel          = trim(pg_fetch_result($res,0, contato_cel));
        $chars_replace = array('{','}','"');
        $contato_telefones = str_replace($chars_replace, "", trim(pg_fetch_result($res,0,'contato_telefones')));

        if ($login_fabrica == 151) {
            $contato_telefones = trim(pg_fetch_result($res,0,'contato_telefones'));
            unset($fone);
 			
        	

            $fones_latina = array();
            $fones_latina = explode(',', $contato_telefones);

            if(strlen($fone)==0 and strlen($fones_latina[0])>0 ){
                $fone  = $fones_latina[0];   
            	$fone2 = $fones_latina[1];
            	$fone3 = $fones_latina[2];
        	}

        }

        if($login_fabrica == 151 && $fone == null){
            $fone = trim(pg_fetch_result($res,0, fone));
        }

		if($login_fabrica == 20){ //hd_chamado=2890291
			$xnomeacao_data = "";
			$xnome_propietario = "";
			$xcodigo_fornecedor = "";
			$parametros_adicionais = pg_fetch_result($res, $i,"parametros_adicionais");
            if(strlen($parametros_adicionais) > 0) {
                $parametros_adicionais 	= json_decode($parametros_adicionais, true);
                $xnomeacao_data        	= $parametros_adicionais['nomeacao_data'];
                $xnomeacao_data 		= str_replace(".", "/", $xnomeacao_data);
                $xnome_propietario      = $parametros_adicionais['nome_propietario'];
                $xcodigo_fornecedor     = $parametros_adicionais['codigo_fornecedor'];
            }
		}

		if($login_fabrica == 3){

			$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

			$bloqueado_pagamento = "f";

			if(strlen($parametros_adicionais) > 0){

				$pa_arr = json_decode($parametros_adicionais, true);

				if(isset($pa_arr["bloqueado_pagamento"]) && $pa_arr["bloqueado_pagamento"] == "t"){
					$bloqueado_pagamento = "t";
				}

			}

		}

		if(in_array($login_fabrica, array(74))){
			$fone = str_replace(array("{","}"), array("",""), $fone);

			$fone = explode(",", $fone);
			$idxRemover = array();
			foreach ($fone as $idx => $value) {
				if($value == '""'){
					$idxRemover[] = $idx;
				}
			}
			foreach ($idxRemover as $value) {
				unset($fone[$value]);
			}

			$fone = implode(", ", $fone);
			$fone = str_replace('"', "", $fone);

			if($fone == "NULL"){
				$fone = "";
			}
		}

			$sql_linha = "SELECT DISTINCT nome AS nome_linha
								from tbl_linha
						JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
						WHERE tbl_linha.fabrica = $login_fabrica
						AND   tbl_posto_linha.posto = $posto";
			$resl = pg_query($con, $sql_linha);

			if(pg_num_rows($resl)>0){
				$linhas = "";
				for($z=0; $z<pg_num_rows($resl);$z++){
					$nome_linha = pg_fetch_result($resl, $z, nome_linha);
					$linhas .= $nome_linha.",";
				}
				$xlinhas = substr($linhas,0,-1);
			}
	}

}


# HD 110541
if($login_fabrica==11){
	$sql_X = "select TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS dataa from tbl_credenciamento where fabrica=11 and posto=$posto order by data desc limit 1";
		$res_X = pg_query ($con,$sql_X);
		if (pg_num_rows ($res_X) > 0) {
				$data_credenciamento   = trim(pg_fetch_result($res_X,0,'dataa'));
		}
}

$visual_black = "manutencao-admin";

$title       = traduz("Cadastro de Postos Autorizados");
$cabecalho   = traduz("Cadastro de Postos Autorizados");
$layout_menu = "callcenter";
include 'cabecalho.php';

?>

<style type="text/css">


.border {
	border: 1px solid #596d9b;
}


.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
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

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
	text-align:left;
}
</style>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="posto" value="<? echo $posto ?>">

<?
if($login_fabrica == 11 OR $login_fabrica == 3 or $login_fabrica==1){ // HD 17304 // 90761
	echo "<TABLE width='650' align='center' border='0'>";
	echo "<TR>";
	echo "<TD align='left'><font size='2' face='verdana' ";
	if ($credenciamento == traduz('CREDENCIADO'))
		echo "color='#3300CC'";
	elseif ($credenciamento == traduz('DESCREDENCIADO'))
		echo "color='#F3274B'";
	elseif ($credenciamento == traduz('EM DESCREDENCIAMENTO'))
		echo "color='#FF9900'";
	elseif ($credenciamento == traduz('EM CREDENCIAMENTO'))
		echo "color='#006633'";
	echo "><B>	";

		# HD 110541
	if($login_fabrica==11 AND strlen($data_credenciamento)>0){
		if ($credenciamento == traduz('CREDENCIADO'))
			$show_date_credenciamento = "EM: $data_credenciamento";
		else if ($credenciamento == traduz('DESCREDENCIADO')){
			$sql_X = "select TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data from tbl_credenciamento where fabrica=11 and posto=$posto and status='CREDENCIADO'";
			$res_X = pg_query ($con,$sql_X);
			if (pg_num_rows ($res_X) > 0) {
					$data_credenciamento_2   = trim(pg_fetch_result($res_X,0,data));
					$show_date_credenciamento .= "CREDENCIADO EM: $data_credenciamento_2 E DESCREDENCIADO EM $data_credenciamento";
			}else{
				$show_date_credenciamento .= "DESCREDENCIADO EM $data_credenciamento";;
			}
		}
		else if ($credenciamento == 'EM DESCREDENCIAMENTO')
			$show_date_credenciamento = "DESDE: $data_credenciamento";
		else if ($credenciamento == 'EM CREDENCIAMENTO')
			$show_date_credenciamento = "DESDE: $data_credenciamento";
	}
	# HD 110541
	if($login_fabrica==11 AND $credenciamento == 'DESCREDENCIADO'){
		echo $show_date_credenciamento;
	}else{
		echo $credenciamento."  ".$show_date_credenciamento;
	}
	echo "</B></font></TD>";
	echo "<td align='right' nowrap>";
	if (strlen ($posto) > 0 ) {
		$resX = pg_query ("SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto FROM tbl_posto_fabrica JOIN tbl_posto ON tbl_posto_fabrica.distribuidor = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = $posto");
		$sqlX = "
SELECT tbl_posto_fabrica.posto, tbl_posto.nome
FROM tbl_posto_fabrica
JOIN tbl_posto_linha on tbl_posto_linha.posto = tbl_posto_fabrica.posto
JOIN tbl_linha on tbl_posto_linha.linha = tbl_linha.linha and tbl_linha.fabrica = $login_fabrica
JOIN tbl_posto on tbl_posto_linha.distribuidor = tbl_posto.posto
where tbl_posto_fabrica.fabrica = $login_fabrica
and tbl_posto_fabrica.posto = $posto limit 1";
		$resX = pg_query ($sqlX);



		if (pg_num_rows ($resX) > 0) {
			echo traduz("Distribuidor: ") . pg_fetch_result ($resX,0,codigo_posto) . " - " . pg_fetch_result ($resX,0,nome) ;
		}else{
			echo traduz("Atendimento direto");
		}
	}
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";
}
ob_start();
?>

<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
<?php 
	if($login_fabrica == 151){
        $sql_X = "SELECT TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data_geracao, 
                tbl_credenciamento.status
                FROM tbl_credenciamento
               WHERE tbl_credenciamento.fabrica = $login_fabrica
                 AND tbl_credenciamento.posto   = $posto
            ORDER BY tbl_credenciamento.data DESC
               LIMIT 1";
        $res_X = pg_query ($con,$sql_X);

        if (pg_num_rows ($res_X) > 0) {
            $data_geracao   = trim(pg_fetch_result($res_X,0,'data_geracao'));
            $credenciamento   		= trim(pg_fetch_result($res_X,0,'status'));

            if ($credenciamento == 'CREDENCIADO')
		        $colors = "color:#3300CC";
		    else if ($credenciamento == 'DESCREDENCIADO')
		        $colors = "color:#F3274B";
		    else if ($credenciamento == 'EM DESCREDENCIAMENTO')
		        $colors = "color:#FF9900";
		    else if ($credenciamento == 'EM CREDENCIAMENTO')
		        $colors = "color:#006633";
        }
        echo "<tr><td align='left' style=' $colors'><font size='2' face='verdana' > <b>$credenciamento</b> </font></td>";
        echo "<td> <b>".traduz("Data de Geração:")." $data_geracao</b></td></tr>";

    }
?>
	
	<tr class='titulo_tabela'>
		<?if($login_fabrica == 151){?>
			<td colspan="8">
				<?=traduz('Informações Cadastrais')?>
			</td>
		<?}else{?>
			<td colspan="100%">
				<?=traduz('Informações Cadastrais')?>
			</td>
		<?}?>
	</tr>
	<tr class='subtitulo'>
		<td><?=traduz('CNPJ/CPF')?></td>
		<td><?=traduz('I.E.')?></td>
		<td><?=traduz('FONE')?></td>
		<?php if (in_array($login_fabrica,[35,175])){ ?>
			<td><?=traduz('CELULAR')?></td>
		<?php } ?>
		<?if($login_fabrica == 151){?>
			<td><?=traduz('FONE 2')?></td>
			<td><?=traduz('FONE 3')?></td>
			<td><?=traduz('CELULAR')?></td>
			<td colspan="2">FAX</td>
		<?}else{?>
			<td><? echo (in_array($login_fabrica, array(11,172))) ? "FONE 2" : "FAX" ; ?> </td>
			<td><?=traduz('CONTATO')?></td>
		<?}?>
	</tr>
	<tr>
		<td class='border'><? echo $cnpj ?></td>
		<td class='border'><? echo $ie ?></td>
		<td class='border'><? echo $fone ?></td>
		<?php if (in_array($login_fabrica,[35,175])){ ?>
			<td class="border"><? echo ($login_fabrica == 175) ? $fax : $contato_cel; ?></td>
		<?php } ?>
		<?if($login_fabrica == 151){?>
			<td class='border'><? echo $fone2 ?></td>
			<td class='border'><? echo $fone3 ?></td>
			<td class='border'><? echo $contato_cel ?></td>
			<td colspan="2" class='border'><? echo $fax ?></td>
			<td>CONTATO</td>
		<?}else{?>
			<td class='border'><? echo $fax ?></td>
			<td class='border'><? echo $contato ?></td>
		<?}?>
	</tr>
	<tr class='subtitulo'>
		<td colspan="2"><?=traduz('CÓDIGO')?></td>
		<td colspan="5"><?=traduz('RAZÃO SOCIAL')?></td>
		<?if($login_fabrica == 151){?>
			<td colspan="3"><?=traduz('CONTATO')?></td>
		<?}?>

	</tr>
	<tr>
		<td colspan="2" class='border'><? echo $codigo ?></td>
		<td colspan="5" class='border'><? echo $nome ?></td>
		<?if($login_fabrica == 151){?>
			<td colspan="3" class='border'><? echo $contato ?></td>
		<?}?>
	</tr>
	<?php if($login_fabrica == 20){ //hd_chamado=2890291 ?>
		<tr class='subtitulo'>
			<td colspan=""><?=traduz('DATA NOMEAÇÃO')?></td>
			<td colspan="3"><?=traduz('NOME PROPRIETARIO')?></td>
			<td colspan=""><?=traduz('CÓDIGO DO FORNECEDOR')?></td>
		</tr>
		<tr>
			<td class='border'><?=$xnomeacao_data?></td>
			<td colspan="3" class='border'><?=utf8_decode($xnome_propietario)?></td>
			<td colspan="" class='border'><?=$xcodigo_fornecedor?></td>
		</tr>
	<?php } ?>
</table>

<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
	<tr class='subtitulo'>
		<td colspan="2"><?=traduz('ENDEREÇO')?></td>
		<td><?=traduz('NÚMERO')?></td>
		<td colspan="2"><?=traduz('COMPLEMENTO')?></td>
	</tr>
	<tr>
		<td colspan="2" class='border'><? echo $endereco ?></td>
		<td class='border'><? echo $numero ?></td>
		<td colspan="2" class='border'><? echo $complemento ?></td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="2"><?=traduz('BAIRRO')?></td>
		<td><?=traduz('CEP')?></td>
		<td><?=traduz('CIDADE')?></td>
		<td><?=traduz('ESTADO')?></td>
	</tr>
	<tr>
		<td colspan="2" class='border'><? echo $bairro ?></td>
		<td class='border'> <? echo $cep ?></td>
		<td class='border'><? echo $cidade ?></td>
		<td class='border'><? echo $estado ?></td>
	</tr>
</table>

<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
	<tr class='subtitulo'>
		<td>E-MAIL</td>
		<td><?=traduz('CAPITAL/INTERIOR')?></td>
		<td><?=traduz('TIPO DO POSTO')?></td>
		<td><?=traduz('DESCONTO')?></td>
		<?// HD 110541
		if($login_fabrica==11){?>
			<td width = '34%'><?=traduz('ATENDIMENTO')?></td>
		<? } ?>
	</tr>
	<tr>
		<td class='border'><? echo $email ?></td>
		<td class='border'><? echo $capital_interior ?> </td>
		<td class='border'><? echo $descricao_tipo_posto ?></td>
		<td class='border'><? echo $desconto ?>%</td>
		<?// HD 110541
		if($login_fabrica==11){?>
			<td class='border'><? if($atendimento_lenoxx=="b"){
						echo "BALCÃO";
					}elseif($atendimento_lenoxx=="r"){
						echo "REVENDA";
					}elseif($atendimento_lenoxx=="t"){
						echo "BALCÃO/REVENDA";
					}else echo ""; ?></td>
		<? } ?>
	</tr>
</table>

<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
	<tr class='subtitulo'>
		<? if ($login_fabrica != 117){?>
		<td><?=traduz('NOME FANTASIA')?></td>
		<? } ?>
		<td><?=traduz('SENHA')?></td>
		<td><?=traduz('TRANSPORTADORA')?></td>
		<td><?=traduz('REGIÃO SUFRAMA')?></td>
		<td><?=traduz('ITEM APARÊNCIA')?></td>
	</tr>
	<tr>
		<? if ($login_fabrica != 117){?>
		<td class='border'><? echo $nome_fantasia ?></td>
		<? } ?>
		<td class='border'><?
		if($login_fabrica == 11){
			echo '******';
		}else{
			echo $senha;
		}
		?></td>
		<td align='center' class='border'><?$transportadora?></td>
		<td class='border'>
		<?
			if($suframa == 't') echo 'SIM'; else echo 'NÃO';
		?>
		</td>
		<td class='border'>
		<?
			if ($item_aparencia == 't') echo 'SIM'; else echo 'NÃO';
		?>
		</td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="5"><?=traduz('Linhas')?></td>
	</tr>
	<tr>
		<td colspan="5" class='border'><? echo $xlinhas ?></td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="5"><?=traduz('Observações')?></td>
	</tr>
	<tr>
		<td colspan="5" class='border'><? echo $obs ?></td>
	</tr>
</table>

<br>

<? if ($login_fabrica <> 2) {?>
<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
	<tr class='titulo_tabela'>
		<td colspan='4'><?=traduz('Informações para Cobrança')?></td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="2"><?=traduz('ENDEREÇO')?></td>
		<td><?=traduz('NÚMERO')?></td>
		<td><?=traduz('COMPLEMENTO')?></td>
	</tr>
	<tr class="table_line">
		<td colspan="2" class='border'><? echo $cobranca_endereco ?></td>
		<td class='border'><? echo $cobranca_numero ?></td>
		<td class='border'><? echo $cobranca_complemento ?></td>
	</tr>
	<tr class='subtitulo'>
		<td><?=traduz('BAIRRO')?></td>
		<td><?=traduz('CEP')?></td>
		<td><?=traduz('CIDADE')?></td>
		<td><?=traduz('UF')?></td>
	</tr>
	<tr class="table_line">
		<td class='border'><? echo $cobranca_bairro ?></td>
		<td class='border'><? echo $cobranca_cep ?></td>
		<td class='border'><? echo $cobranca_cidade ?></td>
		<td class='border'><? echo $cobranca_estado ?></td>
	</tr>
</table>
<? } ?>

<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
	<tr class='titulo_tabela'>
		<td colspan='4'><?=traduz('Informações para Cobrança')?></td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="2"><?=traduz('BANCO')?></td>
		<td><?=traduz('AGÊNCIA')?></td>
		<td><?=traduz('CONTA')?></td>
	</tr>
	<tr>
		<td colspan="2" class='border'><? echo $banco ?></td>
		<td class='border'><? echo $agencia ?></td>
		<td class='border'><? echo $conta ?></td>
	</tr>
</table>

<table  width='700' align='center' border='0' cellpadding="1" cellspacing="3" class='formulario'>
	<tr class='titulo_tabela'><td colspan='3'><?=traduz('Informações Bancárias')?></td></tr>
	<tr class='subtitulo'>
		<td width = '33%'><?=traduz('CPF/CNPJ FAVORECIDO')?></td>
		<td colspan=2><?=traduz('NOME FAVORECIDO')?></td>
	</tr>
	<tr>
		<td width = '33%' class='border'><? echo $cpf_conta ?></td>
		<td colspan=2 class='border'><? echo $favorecido_conta ?></td>
	</tr>
	<tr class='subtitulo'>
		<td width = '33%'><?=traduz('CÓDIGO BANCO')?></td>
		<td colspan='2'><?=traduz('NOME DO BANCO')?></td>
	</tr>
	<tr>
		<td width = '33%' class='border'><? echo $banco ?></td>
		<td colspan='2' class='border'><? echo $nomebanco ?></td>
	</tr>
	<tr class='subtitulo'>
		<td width = '33%'><?=traduz('TIPO DE CONTA')?></td>
		<td width = '33%'><?=traduz('AGÊNCIA')?></td>
		<td width = '34%'><?=traduz('CONTA')?></td>
	</tr>
	<tr>
		<td width = '33%' class='border'><?  echo $tipo_conta?></td>
		<td width = '33%' class='border'><? echo $agencia ?></td>
		<td width = '34%' class='border'><? echo $conta ?></td>
	</tr>

	<tr class='subtitulo'>
		<td colspan="3"><?=traduz('Observações')?></td>
	</tr>
	<tr>
		<td colspan="3" class='border'><? echo nl2br($obs_conta); ?></td>
	</tr>
</table>

<?
	$excel = ob_get_contents();

?>


<?/******************************************************************************************/
	//hd 14835 29/2/2008
	if ($login_fabrica == 1) {
	if($posto){
		$sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = 62 AND posto = $posto";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0){
			$pedido_em_garantia_finalidades_diversas = pg_fetch_result ($res,0,visivel);

		}
	}
}
?>
<br>
<table  width='700' align='center' border='0' cellpadding="1" cellspacing="1" class='tabela'>
	<TR>
		<td style='border:0px;'>
			<TR class='titulo_tabela'>
				<td COLSPAN='6'><?=traduz('POSTO PODE DIGITAR')?></td>
			</tr>
			<TR bgcolor='#F7F5F0'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="pedido_faturado" value="t" <? if($pedido_faturado=="t") echo "checked"; ?> disabled > </TD>
				<TD > &nbsp; <?=traduz('PEDIDO FATURADO')?> </TD>
			</tr>
			<TR bgcolor='#F1F4FA'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="pedido_em_garantia" value="t" <? if($pedido_em_garantia=="t") echo "checked"; ?> disabled > </TD>
				<TD > &nbsp; <?=traduz('PEDIDO EM GARANTIA')?> </TD>
			</tr>
			<? if ($login_fabrica == 6 or $login_fabrica==24){ ?>
			<TR bgcolor='#F7F5F0'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="garantia_antecipada" value="t" <? if($garantia_antecipada=="t") echo "checked"; ?> disabled > </TD>
				<TD > &nbsp; <?=traduz('PEDIDO EM GARANTIA ANTECIPADA')?></TD>
			</TR>
			<? }?>
			<TR bgcolor='#F1F4FA'>
				<?if ($login_fabrica == 1) { ?>
				<TD width='30'><INPUT TYPE="checkbox" NAME="pedido_em_garantia_finalidades_diversas" value="t" <? if($pedido_em_garantia_finalidades_diversas=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('PEDIDO DE GARANTIA ( FINALIDADES DIVERSAS)')?></TD>
			</TR>
			<TR bgcolor='#F7F5F0'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="coleta_peca" value="t" <? if($coleta_peca=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('COLETA DE PEÇAS')?></TD>
			</TR>
			<TR bgcolor='#F1F4FA'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="reembolso_peca_estoque" value="t" <? if($reembolso_peca_estoque=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('REEMBOLSO DE PEÇA DO ESTOQUE (GARANTIA AUTOMÁTICA)')?></TD>
			</TR>
				<?}?>
			<TR bgcolor='#F7F5F0'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="digita_os" value="t" <? if($digita_os=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('DIGITA OS')?>
				<?
				//HD 110541
				if($login_fabrica==11 and strlen($posto)>0){
					if($digita_os<>"t"){
						echo "<font color='red'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".traduz("Posto Bloqueado Para digitar OS.")."</b></font>";
					}
				}
				?>
				</TD>
			</TR>
			<?
			if($login_fabrica <> 3){?>
			<TR bgcolor='#F1F4FA'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="prestacao_servico" value="t" <? if($prestacao_servico=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('PRESTAÇÃO DE SERVIÇO')?><br><font size='-2'>&nbsp;<?=traduz('Posto só recebe mão-de-obra. Peças são enviadas sem custo.')?></font></TD>
			</TR>
			<?php

			}
			if(in_array($login_fabrica,[169,170])){?>
			<TR bgcolor='#F1F4FA'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="prestacao_servico_sem_mo" value="t" <? if($prestacao_servico_sem_mo=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; Prestação de Serviço Isenta de M.O.</TD>
			</TR>

			<?}
				   if(strlen($posto) >0) {
					$sql = "SELECT		tbl_tipo_posto.distribuidor
							FROM		tbl_tipo_posto
							LEFT JOIN	tbl_posto_fabrica USING (tipo_posto)
							WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
							AND         tbl_posto_fabrica.posto = $posto;";
					$res = pg_query ($con,$sql);

					if (pg_num_rows ($res) > 0) {
						$pedido_via_distribuidor    = trim(pg_fetch_result($res,0,distribuidor));
					}
				}
				?>
				<? if ($login_fabrica <> 3 and $login_fabrica <> 6 and $login_fabrica <> 19){ ?>
			<TR bgcolor='#F7F5F0'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="pedido_via_distribuidor" value="t" <? if($pedido_via_distribuidor=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('PEDIDO VIA DISTRIBUIDOR')?></TD>
			</TR>
				<?}?>
			<? if ($login_fabrica == 19){ ?>
			<TR bgcolor='#F1F4FA'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="atende_comgas" value="t" <? if($atende_comgas=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('Atend.Comgás')?><br><font size='-2'>&nbsp;<?=traduz('Posto pode digitar OS Comgás.')?></font></TD>
			</TR>
			<? } ?>
			<? if ($login_fabrica == 1){ ?>
			<TR bgcolor='#F7F5F0'>
				<TD width='30'><INPUT TYPE="checkbox" NAME="escolhe_condicao" value="t" <? if($escolhe_condicao=="t") echo "checked"; ?> disabled > </TD>
				<TD> &nbsp; <?=traduz('ESCOLHE CONDIÇÃO DE PAGAMENTO')?></font></TD>
			</TR>
			<? } ?>
			<?php 
			if ($login_fabrica == 3){ 
			?>
			<tr bgcolor='#F1F4FA'>
		        <td width='30'><input type="checkbox" name="bloqueado_pagamento" value="t" <?php if ($bloqueado_pagamento == 't') echo "checked " ?> disabled ></td>
		        <td style="text-transform: uppercase;"> &nbsp; <?=traduz('Bloqueado para Pagamento')?></td>
		    </tr>
			<?php } ?>
		</td>
	</tr>
</table>
<br>


<? if($login_fabrica ==1) { // HD 46318
	echo "<br>";
	$sql = "SELECT tbl_black_posto_condicao.posto        ,
					tbl_condicao.descricao AS condicao   ,
					tbl_black_posto_condicao.id_condicao ,
					tbl_posto_fabrica.codigo_posto       ,
					tbl_condicao.codigo_condicao         ,
					tbl_condicao.promocao
			FROM tbl_black_posto_condicao
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
			JOIN tbl_condicao ON tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
			and tbl_posto_fabrica.fabrica = $login_fabrica
			where tbl_black_posto_condicao.posto = $posto ";
		if($promocao == 't'){
			$sql .= "UNION SELECT tbl_posto_fabrica.posto, tbl_condicao.descricao as condicao, tbl_condicao.condicao as id_condicao, tbl_posto_fabrica.codigo_posto, 					tbl_condicao.codigo_condicao         ,
			tbl_condicao.promocao
				FROM tbl_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_condicao.fabrica = $login_fabrica
				AND tbl_condicao.promocao is true ";
		}
		$sql .= "
			order by condicao,codigo_condicao";
	$res = pg_query($con,$sql);
//echo "$sql";
	if(pg_num_rows($res)>0){
		echo "<table class='border' width='650' align='center' border='1' cellpadding='1' cellspacing='1'>";
		echo "<TR class='menu_top'>";
		echo "<td COLSPAN='2'>".traduz("Condições do Posto")."</td>";
		echo "</tr>";
		echo "<TR class='menu_top'>\n";
		echo "<td>".traduz("Código")."</TD>\n";
		echo "<td>".traduz("Condição")."</TD>\n";
		echo "</TR>\n";
		for($x=0;pg_num_rows($res)>$x;$x++){
			$posto         = pg_fetch_result($res,$x,posto);
			$condicao      = pg_fetch_result($res,$x,condicao);
			$id_condicao   = pg_fetch_result($res,$x,id_condicao);
			$promocao   = pg_fetch_result($res,$x,promocao);
			$codigo_condicao  = pg_fetch_result($res,$x,codigo_condicao);
			if ($x % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef9';}
			echo "<TR bgcolor='$cor'>\n";
			echo "<TD align='center' nowrap>$codigo_condicao";
			if($promocao == 't') echo " automático";
			echo "</TD>\n";
			echo "<TD align='center' nowrap>$condicao</TD>\n";
		}
		echo "</table><br>";
	}

}
?>
<!--
<br>

<table  width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr >
		<td COLSPAN='4'>LINHAS E TABELAS</td>
	</TR>
	<?
	/*if (strlen($_GET["posto"]) > 0)  $posto = trim($_GET["posto"]);

	$sql = "SELECT	tbl_posto_linha.linha   ,
					tbl_posto_linha.tabela  ,
					tbl_linha.nome          ,
					tbl_tabela.sigla_tabela
			FROM tbl_posto_linha
			JOIN tbl_linha USING (linha)
			JOIN tbl_tabela USING (tabela)
			WHERE tbl_posto_linha.linha = tbl_linha.linha
			AND   tbl_linha.fabrica = $login_fabrica
			AND   tbl_posto_linha.tabela = tbl_tabela.tabela
			AND   tbl_tabela.fabrica = $login_fabrica
			AND   tbl_posto_linha.posto = $posto;";

	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {

		for($i = 0; $i < pg_num_rows($res); $i++){

		$linha          = trim(pg_fetch_result($res,$i,linha));
		$tabela         = trim(pg_fetch_result($res,$i,tabela));
		$nome           = trim(pg_fetch_result($res,$i,nome));
		$sigla_tabela   = trim(pg_fetch_result($res,$i,sigla_tabela));
		$prestacao_servico   = trim(pg_result($res,0,prestacao_servico));

		echo "<tr class='table_line'>";
		echo "<td width='20%' class='menu_top'>LINHA:</td>";
		echo "<td width='30%' class='table_line'>$nome</td>";
		echo "<td width='20%' class='menu_top'>TABELA:</td>";
		echo "<td width='30%' class='table_line'>$sigla_tabela</td>";
		echo "</TR>";
		}
	}*/
	?>

</table>
-->

<p>
<?
	$arquivo = "xls/informacoes-posto-$login_fabrica-".date('Y-m-d').".xls";
	$fp = fopen($arquivo,"w");
	fwrite($fp, $excel);
	fclose($fp);
?>

<table align='center'>
	<tr>
		<td></td>
		<?
			if($login_fabrica == 117){
				echo "<td>
						<input type='button' value='".traduz("Download Excel")."' onclick=\"window.open('$arquivo')\">
					  </td>";
			}
		?>
	</tr>
</table>

<? include "rodape.php"; ?>
