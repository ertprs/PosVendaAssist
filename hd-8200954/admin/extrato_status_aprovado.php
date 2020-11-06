<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

include_once '../anexaNF_inc.php';

include_once "funcoes.php";
include_once "../class/tdocs.class.php";

$erro = "";

//  Exclui a imagem da NF
if ($_POST['ajax'] == 'excluir_nf') {
    $img_nf = anti_injection($_POST['excluir_nf']);
    //$img_nf = basename($img_nf);

    $excluiu = (excluirNF($img_nf));
    $nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

    if ($login_fabrica == 42) {
        if ($excluiu)  $ret = "ok|" . temNFMakita($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
    }else{
        if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
    }   
    if (!$excluiu) $ret = 'ko|Não foi possível excluir o arquivo solicitado.';

    exit($ret);
}// FIM Excluir imagem

if(isset($_POST["setarConferido"]) && $_POST["setarConferido"] == "true"){
    $extrato = $_POST["extrato"];
    
    #verifica se tem interação de status
    $verificaStatus = "SELECT conferido 
                       FROM tbl_extrato_status 
                       WHERE extrato = $extrato";
    $resVerificaStatus = pg_query($con,$verificaStatus);

    #Se houver, atualiza como conferido
    if(pg_num_rows($resVerificaStatus) > 0 and 1==2){
    
        $updateConfere = "UPDATE tbl_extrato_status SET
                      conferido = now(), 
                      admin_conferiu  = {$login_admin} 
                      WHERE extrato = $extrato ";
        $resUpdateConfere = pg_query($con, $updateConfere);
    }else{
        # se não ouver, insere uma interação como Conferido
        $insertStatus = "INSERT INTO tbl_extrato_status(extrato, 
                                                        data, 
                                                        obs, 
                                                        pendente,
                                                        pendencia,
                                                        advertencia, 
                                                        fabrica, 
                                                        conferido, 
                                                        admin_conferiu)
                                               VALUES({$extrato},
                                                      now(),
                                                      'Conferido',
                                                      'f',
                                                      'f',
                                                      'f',
                                                      {$login_fabrica},
                                                      now(),
                                                      {$login_admin})";
        $resInsertStatus = pg_query($con, $insertStatus);
        if(strlen(pg_last_error($con)) > 0){
            echo "{\"success\":\"false\"}";exit;
        }
    }

    if($login_fabrica == 1){

        $sql = "SELECT posto FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        $posto = pg_fetch_result($res, 0, "posto");

        /* Verifica se o posto ainda tem extratos pendentes de conferência a mais de 60 dias */

        $sql = "SELECT
            tbl_extrato.extrato,
            tbl_extrato.posto 
        FROM tbl_extrato 
        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto 
        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}  
        LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato  
        LEFT JOIN tbl_extrato_status ON tbl_extrato_status.extrato = tbl_extrato.extrato  
        WHERE 	tbl_extrato.fabrica = {$login_fabrica}
		AND (tbl_extrato.data_geracao + INTERVAL '60 DAYS') <= CURRENT_DATE
		AND tbl_extrato.aprovado NOTNULL
		AND tbl_posto_fabrica.credenciamento != 'DESCREDENCIADO'
		AND tbl_extrato_financeiro.data_envio ISNULL
		AND tbl_extrato.extrato not in (select extrato from tbl_extrato_status where tbl_extrato_status.conferido notnull and fabrica = $login_fabrica)
		AND tbl_extrato.data_geracao >= '2015-01-01'
		AND tbl_posto.posto = {$posto}";
        $res = pg_query($con, $sql);

        /* Se não tiver extratos pendentes */
        if(pg_num_rows($res) == 0){

			$sqlP = "SELECT
							desbloqueio,
							admin,
							resolvido
							FROM tbl_posto_bloqueio
							WHERE
							fabrica = {$login_fabrica}
							AND pedido_faturado IS FALSE
							AND posto = {$posto}
							AND tbl_posto_bloqueio.extrato = TRUE
							ORDER BY data_input DESC LIMIT 1";
			$resP = pg_query($con, $sqlP);

			if(pg_num_rows($resP) > 0){
				$desb        = pg_fetch_result($resP, 0, "desbloqueio");
				$admin       = pg_fetch_result($resP, 0, "admin");
				$resolvido   = pg_fetch_result($resP, 0, "resolvido");

				if($desb == "f" and empty($admin)){

					$sqlB = "INSERT INTO tbl_posto_bloqueio(fabrica, posto, observacao,desbloqueio,extrato, admin ) VALUES ($login_fabrica, $posto,'Desbloqueio automatico por não possuir extrato pendente',true,true, $login_admin )";
					$resB = pg_query($con,$sqlB);
					$desbloqueio_automatico = true;
					if(strlen(pg_last_error($con)) > 0){

						$msg_erro[] = "Erro ao inserir o posto {$posto} para ser bloqueado. Erro (".pg_last_error($con).").";
						/* Posto bloquado: $posto */

					}
				}
			}

        }

    }

    if(strlen(pg_last_error($con)) == 0){
        $verificaAdmin = "SELECT login, conferido
                          FROM tbl_extrato_status
                          JOIN tbl_admin ON tbl_extrato_status.admin_conferiu = tbl_admin.admin
                          WHERE extrato = {$extrato} AND conferido is not null";
        $res = pg_query($con, $verificaAdmin);
        if(pg_num_rows($res) > 0){
            $admin = pg_fetch_result($res, 0, "login");
            $data_conferido = date("d/m/Y",strtotime(pg_fetch_result($res, 0, "conferido")));
            echo "{
                      \"success\":\"true\" , 
					  \"admin\":\"{$admin}\" , 
					  \"data_conferido\":\"{$data_conferido}\"
                 }";
        }else{
            echo "{\"success\":\"false\"}";
        }
    }else{
        echo "{\"success\":\"false\"}";
    }    
    exit;
}

if (strlen($_GET["resolvido"])  > 0){
$resolvido= $_GET["resolvido"];
$sql = "UPDATE tbl_extrato_status 
				set pendente = 'f',
					confirmacao_pendente = 'f'
		WHERE extrato=$resolvido 
		and pendente = 't' 
		and confirmacao_pendente = 't'";
$res = pg_exec($con,$sql);
$extrato=$resolvido;
}



if (strlen(trim($_GET["extrato"])) > 0)  $extrato = trim($_GET["extrato"]);
if (strlen(trim($_POST["extrato"])) > 0) $extrato = trim($_POST["extrato"]);
if (strlen(trim($_POST["acao"])) > 0)    $acao = trim($_POST["acao"]);
if (strlen(trim($_POST["ped_adv"])) > 0)  $ped_adv = trim($_POST["ped_adv"]);
//if (strlen(trim($_POST["arquivo"])) > 0)  $arquivo = trim($_POST["arquivo"]);



function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

if($ped_adv == 'pendencia'){
	$pendencia   = "'t'";
	$advertencia = "'f'";
}else{
	$pendencia   = "'f'";
	$advertencia = "'t'";
}


if ($acao == "ALTERAR") {
    unset($x_obs, $obs_aux, $obs);
    $arquivoi = $arquivo["name"];
    $x_obs = trim($_POST["obs"]);
    $obs_aux = pg_escape_string($x_obs);
    $pendente = "'t'";
    if (strlen($x_obs) == 0){
        $erro .= " Preencha o campo Observação. "; 
    }else{
    	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
    	$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes) 

    	if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
    		// Verifica o mime-type do arquivo
    		if (!preg_match("/\/(zip|x-zip|x-zip-compressed|x-compress|x-compressed|pdf|msword|doc|word|x-msw6|x-msword|pjpeg|jpeg|png|gif|bmp|msexcel|xls|vnd.ms-excel|richtext|plain|html)$/", $arquivo["type"])){
    			$msg_erro = "Arquivo em formato inválido!";
    		}else{ // Verifica tamanho do arquivo 
    			if ($arquivo["size"] > $config["tamanho"]) {
    				$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
    			}
    		}

    		if (strlen($msg_erro) == 0) {
    			// Pega extensão do arquivo
    			preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
    			$aux_extensao = "'".$ext[1]."'";
    						
    			$arquivo["name"]=retira_acentos($arquivo["name"]);
    			$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

                // se for usar anexo liberar e fazer insert de contexto
                // insert into tbl_anexo_contexto (nome) values ('documento') RETURNING anexo_contexto;
                // insert into tbl_anexo_tipo (anexo_contexto, nome, codigo, fabrica) values (anexo_contexto,'Documentos','documentos',1)
                // criar no $attTypes o contexto documento com ref tbl_extrato
                /*$upload = new TDocs($con, $login_fabrica, 'documento');
                $retorno = $upload->uploadFileS3($_FILES["arquivo"], $extrato, true, 'documento');*/
                $upload = new TDocs($con, $login_fabrica);
                $retorno = $upload->uploadFileS3($_FILES["arquivo"], $extrato, true, 'extratoaprovado');


                if ($retorno !== true) {
                    $msg_erro = "Arquivo não foi enviado, erro: ".$upload->error;
                    $erro = $msg_erro;
                }
                
    		}//fim da verificação de erro
    	}//fim da verificação de existencia no apache
    }
	
	if (strlen($erro) == 0) {
		$sql = "INSERT INTO tbl_extrato_status (
						extrato    ,
						fabrica    ,
						obs        ,
						data       ,
						pendente   ,
						pendencia  ,
						advertencia,
						arquivo    
					) VALUES (
						$extrato          ,
						$login_fabrica    ,
						'$obs_aux'        ,
						current_timestamp ,
						$pendente         ,
						$pendencia        ,
						$advertencia      ,
						'$arquivoi'
				);";
		//echo $sql;
		/*
		pendente = informa para o posto que esta pendente
		confirmacao_pendente = admin confirma que a pendecia esta resolvida
		*/
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);
			
		if (strlen($erro) == 0 and $pendente=="'t'") {
		
			$xsql = "SELECT tbl_posto_fabrica.contato_email as email,
							tbl_posto_fabrica.codigo_posto
					from tbl_posto 
					join tbl_extrato on tbl_posto.posto = tbl_extrato.posto 
					join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto
										  and tbl_posto_fabrica.fabrica = $login_fabrica
					where extrato=$extrato";
			$xres = pg_exec($con,$xsql);
			$xemail_posto  = pg_result($xres,0,email);
			$xcodigo_posto = pg_result($xres,0,codigo_posto);

			$xsql = "SELECT protocolo from tbl_extrato where extrato=$extrato";
			$xres = pg_exec($con,$xsql);
			$xprotocolo = pg_result($xres,0,protocolo);

			$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica limit 1";
			// echo "$xsql";
			$xres = @pg_exec($con,$xsql);
			$xnome_completo = @pg_result($xres,0,nome_completo);
			$xfone          = @pg_result($xres,0,fone);
			$xemail_admin   = @pg_result($xres,0,email);

            //hd 7548 - acrescentado o extrato e o posto no assunto
            //$remetente    = "Black&Decker <$xemail_admin>"; 
            if ($login_fabrica == 1) {
                $nome_fabrica = "Black&Decker";                
            }
            if ($login_fabrica == 42) {
                $nome_fabrica = "Makita";                
                $xprotocolo = $extrato;
            }
            $remetente = "$nome_fabrica <$xemail_admin>";
			
			$destinatario = "$xemail_posto"; 

			if($ped_adv == "pendencia"){
				$assunto      = "Pendência em extrato ($xprotocolo), posto $xcodigo_posto"; 
			}else{
				$assunto      = "Alerta em extrato ($xprotocolo), posto $xcodigo_posto"; 
			}
			$mensagem     = "Prezado Posto Autorizado,<BR><BR>Você tem uma";
			if($ped_adv == 'pendencia') { 
                $mensagem .= " pendência para enviar para a";
            }else{ 
                $mensagem .= " alerta da";
            }
			$mensagem .= " $nome_fabrica no extrato de número $xprotocolo.<BR><BR>";
			if($ped_adv == 'pendencia') { 
                $mensagem .= "<b>Pendência</b>";
            }else{ 
                $mensagem .= "<b>Alerta</b>";
            }

			$mensagem .= "<BR><BR><b>Mensagem do fabricante:</b> ".nl2br($x_obs)."<BR><BR>";
            
			if (strlen($arquivo) > 0) {
				$mensagem .= "Existe um anexo junto a";
				if($ped_adv == 'pendencia') { $mensagem .= "<b>Pendência</b>";}else{ $mensagem .= "<b>Alerta</b>";}
				$mensagem .= "<BR><BR>Para visualizar o anexo entre em:";
				$mensagem .= "../extrato_status_aprovado.php?extrato=$extrato&tipo=pendencia";
			}
			$headers="Return-Path: <$xemail_admin>\nFrom:".$remetente."\nBcc:$xemail_admin \nContent-type: text/html\n"; 
			
			if ( mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers) ) {
				/*echo "<script language='JavaScript'>\n";
				echo "window.close();";
				echo "</script>";		*/
			}else{
				echo "erro";
			}
		}else{
			$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica limit 1";
            // echo "$xsql";
			$xres = @pg_exec($con,$xsql);
			$xnome_completo = @pg_result($xres,0,nome_completo);
			$xfone          = @pg_result($xres,0,fone);
			$xemail_admin   = @pg_result($xres,0,email);

			$remetente    = "Telecontrol <suporte@telecontrol.com.br>"; 
			$destinatario = "$xemail_admin"; 

			$assunto      = "Erro ao cadastrar Alerta/Pendência"; 

			$mensagem     = "Ocorreu um erro ao gravar a seguinte Alerta/Pendência:<br><br>";
			$mensagem .= "<BR>".nl2br($x_obs)."<BR><BR>"; 

			$headers="Return-Path: <$xemail_admin>\nFrom:".$remetente."\nBcc:$xemail_admin \nContent-type: text/html\n"; 
			
			if ( mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers) ) {
				/*echo "<script language='JavaScript'>\n";
				echo "window.close();";
				echo "</script>";		*/
			}else{
				echo "erro";
			}
		}
	}
    header("location: extrato_status_aprovado.php?extrato=".$extrato);
}
?>

<html>

<head>

<title>Observação do Status do Extrato</title>

<style>
input {
	BORDER-RIGHT: #888888 1px solid;
	BORDER-TOP: #888888 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: #888888 1px solid;
	BORDER-BOTTOM: #888888 1px solid;
	FONT-FAMILY: Verdana;
	BACKGROUND-COLOR: #f0f0f0
}
.erro {
  color: white;
  text-align: center;
  font: bold 12px Verdana, Arial, Helvetica, sans-serif;
  background-color: #FF0000;
}
.tabela {
    font-family: Verdana, Tahoma, Arial;
    font-size: 10pt;
    text-align: center;
}
</style>
<link rel="stylesheet" href="../css/css.css" />
<script type='text/javascript' src='js/jquery-1.8.3.min.js'></script>
<script type="text/javascript" src="../js/anexaNF_excluiAnexo.js"></script>
<script language="JavaScript">
    function setarConferido(parent){
        $.ajax({
            url:'<?=$PHP_SELF?>',
            data:{
              setarConferido:'true',
              extrato:<?=$extrato?>
            },
            type:"POST",
            complete: function(data){
                var obJson = $.parseJSON(data.responseText);
                if(obJson.success == "true"){
                    $(parent).find("#btn_conferido").remove();
                    $(parent).append("<b>CONFERIDO</b>");
                    $("#td_conferido_dados").append(obJson.admin + " - " + obJson.data_conferido);
                }else{
                    alert("Erro ao conferir");
                }
            }
        });
    }
</script>
</head>

<body>

<?
// CARREGA DADOS DO EXTRATO
$sql =	"SELECT TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
				TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS data_aprovado ,
				tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
				tbl_posto.nome                                 AS posto_nome
		FROM tbl_extrato
		JOIN tbl_posto          ON  tbl_posto.posto           = tbl_extrato.posto
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$data_geracao   = trim(pg_result($res,0,data_geracao));
	$data_aprovado  = trim(pg_result($res,0,data_aprovado));
	$posto_codigo   = trim(pg_result($res,0,posto_codigo));
	$posto_nome     = trim(pg_result($res,0,posto_nome));
	$posto_completo = $posto_codigo . " - " . $posto_nome;
}

if (strlen($erro) > 0) {
	$obs = trim($_POST["obs"]);
	echo "<div class='erro'>$erro</div>";
}
$verificaAdmin = "SELECT login, conferido
 				 FROM tbl_extrato_status
				 JOIN tbl_admin ON tbl_extrato_status.admin_conferiu = tbl_admin.admin
				 WHERE extrato = {$extrato} 
				AND ((obs = 'Conferido' AND conferido is not null))
				ORDER BY data DESC LIMIT 1";
$resConferido = pg_query($con, $verificaAdmin);
//echo $verificaAdmin;
$numRowsConferido  = pg_num_rows($resConferido);
if($numRowsConferido > 0){
    $admin_conferido = pg_fetch_result($resConferido, 0, "login");
    $data_conferido = date("d/m/Y" , strtotime(pg_fetch_result($resConferido, 0, "conferido")));
}
?>

<form name="frm_extrato" method="post" action="<?echo $PHP_SELF?>" enctype="multipart/form-data">

<input type="hidden" name="extrato" value="<?echo $extrato?>">
<input type="hidden" name="acao">

<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	<tr>
		<td width='100%' colspan="3"><b>Posto</b></td>
	</tr>
	<tr>
		<td width='100%' colspan="3"><?echo substr($posto_completo,0,40)?></td>
	</tr>
	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
<? if(in_array($login_fabrica, array(1,42))) {?>
    <td id="td_conferido" >
<?     if($numRowsConferido > 0){ ?>
           <b>Conferido</b>
<?     }else{ ?>
           <button type="button" name="btn_conferido" id="btn_conferido" onclick="setarConferido($(this).parent())">Conferido</button>
<?     } ?>


    </td>

<? } ?>
		<td><b>Data Geração</b></td>
		<td><b>Data Aprovado</b></td>
	</tr>
	<tr>

        <td id="td_conferido_dados">
        <? if($numRowsConferido > 0){ ?>
               <?=$admin_conferido." - ".$data_conferido?>
        <? }else{ ?>
               &nbsp;
        <? } ?>
        </td>
		<td><?echo $data_geracao?></td>
		<td><?echo $data_aprovado?></td>
	</tr>
</table><BR>

<table width='300' border='0' cellspacing='0' cellpadding='0' class='tabela' align='center'>
	<tr>
		<td  width='50%'><b><INPUT TYPE="radio" NAME="ped_adv" value='pendencia' CHECKED>Pendência</b></td>
		<td width='50%'><b><INPUT TYPE="radio" NAME="ped_adv" value='advertencia'>Alerta</b></td>
	</tr>
</table><BR>


<?
$xsql = "SELECT 	tbl_extrato_status.obs                            ,
				to_char(tbl_extrato_status.data,'DD/MM/YYYY') as data ,
				tbl_extrato_status.pendente                           ,
				tbl_extrato_status.advertencia                        ,
				tbl_extrato_status.confirmacao_pendente               ,
				tbl_extrato_status.extrato                            ,
				tbl_extrato_status.arquivo
		FROM tbl_extrato_status 
		WHERE extrato = $extrato 
		and (pendente notnull OR advertencia notnull)
        ORDER BY tbl_extrato_status.data DESC ";
$xres = pg_exec($con,$xsql);
//echo "$xsql";
if(pg_numrows($xres)>0){
?>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'  bgcolor='#596D9B'>
<tr>
	<td width='80px' align='center'><font color='#FFFFFF'><b>Situação</b></FONT></td>
	<td><font color='#FFFFFF'><b>Pendência</b></FONT></td>
</tr>
<?	for($x=0;pg_numrows($xres)>$x;$x++){
		$xobs                  = pg_result($xres,$x,obs);
		$xdata                 = pg_result($xres,$x,data);
		$xpendente             = pg_result($xres,$x,pendente);
		$xadvertencia          = pg_result($xres,$x,advertencia);
		$xconfirmacao_pendente = pg_result($xres,$x,confirmacao_pendente);
		$xextrato              = pg_result($xres,$x,extrato);
		$xarquivo              = pg_result($xres,$x,arquivo);

		$xobs = stripcslashes($xobs);
        $xobs = (mb_check_encoding($xobs, "UTF-8")) ? utf8_decode($xobs) : $xobs;

if($xpendente=="t" and strlen($xconfirmacao_pendente)==0){$situacao = "Aguardando posto";}
if($xpendente=="f" and $xconfirmacao_pendente=="f"){$situacao = "Resolvido";}
if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
if(strlen($xpendente)==0 and strlen($xconfirmacao_pendente)==0){$situacao = "Observação";}
//if($xpendente=="t" and $xconfirmacao_pendente=="t"){$situacao = "Aguardando admin";}
if($xadvertencia=='t'){ $tipo_interacao = "Alerta"; }else{ $tipo_interacao = "Pendência"; }
		$cor = "#d0e0f6"; 
		if ($x % 2 == 0) $cor = '#efeeea';

		echo "<tr bgcolor='$cor'>";
		echo "<td align='center'><font size='1'>$xdata<BR><B>";
			echo "$situacao";
		echo "</b></font><br><FONT COLOR='#868686'>$tipo_interacao</FONT></td>";
		echo "<td ><font size='2'>".nl2br($xobs)."</font></td>";

		echo "</tr>";

	}

?>

</table>
<table width='100%' border='0' cellspacing='1' cellpadding='5' style='font-family: verdana; font-size: 11px'  >
<tr bgcolor='#596D9B' align="center">
	<td><font color='#FFFFFF'><b>Arquivo</b></FONT></td>
</tr>
<tr align="center"><?PHP
        $arquivo = "";
		$dir = "documentos/";
		$dh  = opendir($dir);
       
        if ($arquivo == "") {
            $sql = "SELECT tdocs FROM tbl_tdocs WHERE referencia_id = {$xextrato} AND situacao = 'ativo'";
            
            $res = pg_exec($con,$sql);
            if(pg_numrows($res)>0){
                $id_tdos  = pg_fetch_result($res, 0, 'tdocs');
                $tDocsobj = new TDocs($con, $login_fabrica);
                $url = $tDocsobj->getDocumentLocation($id_tdos);
                echo "<!--ARQUIVO-I-->&nbsp;&nbsp;<a href=$url target='blank'><img src='../helpdesk/imagem/clips.gif' border='0'>Baixar</a>&nbsp;&nbsp;<!--ARQUIVO-F-->";
            }
        }
		?>
</tr>
</table>
<? } 
if ($login_fabrica == 42) {?>
	<div id="DIVanexos">
		<?
        if ($login_fabrica == 42) {
            if (temNFMakita("e_$extrato", 'bool')) {
                echo temNFMakita("e_$extrato", 'linkEx');
                echo $include_imgZoom;
            }
        }else{
            if (temNF("e_$extrato", 'bool')) {
                echo temNF("e_$extrato", 'linkEx');
                echo $include_imgZoom;
            }
        }
		?>
	</div>
<?php
}
?>
<table width='100%' border='1' cellspacing='1' cellpadding='1' class='tabela'>

	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td width='25%' valign="top"><b>Obs.:</b></td>
		<td width='75%' colspan="2"><textarea name="obs" cols='80' rows='10' ><?echo stripcslashes($obs)?></textarea></td>
	</tr>
    <!-- Retirado Opção de anexar conforme conversado com joão HD-7607250 -->
	<!-- <tr>
		<td colspan='3' align='center' width='100%'>
			Arquivo
			<input type="file" name='arquivo' size='50'>
		</td>
	<tr> -->
</table>
<br>
<center>
<img border="0" id="btn_conf" src="imagens_admin/btn_confirmar.gif" style="cursor: hand;" onclick="javascript: if (document.frm_extrato.acao.value == '') { document.frm_extrato.acao.value='ALTERAR'; document.frm_extrato.submit(); document.getElementById('btn_conf').style.display = 'none'; document.getElementById('loadingConf').style.display = 'block'; }else{ alert('Aguarde Submissão...'); }" alt="Clique aqui para inserir a obs.">
<img id="loadingConf" src="../imagens/grid/loading.gif" style="display: none; width: 22px; vertical-align: middle;" >
</center>
</form>

</body>

</html>
