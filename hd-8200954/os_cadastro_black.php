<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'class/communicator.class.php'; //HD-3191657
include_once('funcoes.php');
//if ($login_fabrica == 1) {
//	echo "<H2>Sistema em manuten��o. Estar� dispon�vel em alguns instantes.</H2>";
//	exit;
//}
$bd_locacao = array(36,82,83,84,90);    // Tipo Posto loca��o para Black & Decker

if ($login_fabrica <> 1) {
	header ("Location: os_cadastro.php");
	exit;
}

if (in_array($login_tipo_posto, $bd_locacao)) {
	header ("Location: os_cadastro_locacao.php");
	exit;
}

// HD-6208393
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

if($_GET['monta_cidade'] == "sim"){ //hd_chamado=2909049
    $cnpj_revenda = $_GET['cnpj_revenda'];
    $sql = "SELECT tbl_cidade.nome,
                    tbl_cidade.cidade
                    FROM tbl_cidade
                    JOIN tbl_revenda ON tbl_revenda.cidade = tbl_cidade.cidade
                    WHERE tbl_revenda.cnpj = '$cnpj_revenda'";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $cidade_nome = pg_fetch_result($res, 0, 'nome');
        $option = "<option value='$cidade_nome'>$cidade_nome</option>";
    }
    echo "$option";
    exit;
}    

?>
<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript">

    function showModal() {

            Shadowbox.open({
                    content:"verifica_forma_extrato.php",
                    player: "iframe",
                    title:  "Gera��o de Extrato",
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
<?php 

$limite_anexos_nf = 5;
/*  MLG - 19/11/2009 - HD 171045 - Cont.
*	MLG - 12/01/2011 - HD 321132
*   	Inicializa o array, vari�veis e fun��es.
		Para saber se a f�brica pede imagem da NF, conferir a vari�vel (bool) '$anexaNotaFiscal'
		Para anexar uma imagem, chamar a fun��o anexaNF($os, $_FILES['foto_nf'])
		Para saber se tem anexo:temNF($os, 'bool');
		Para saber se 2� anexo: temNF($os, 'bool', 2);
		Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
								echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
								echo temNF($os, , 'link', 2); // Devolve um link da 2� imagem
*/
include_once('anexaNF_inc.php');


if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = (excluirNF($img_nf));
	$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

	if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	if (!$excluiu) $ret = 'ko|N�o foi poss�vel excluir o arquivo solicitado.';

	exit($ret);
}//	FIM	Excluir	imagem


$nf_obrigatoria = $fabricas_anexam_NF[$login_fabrica]['nf_obrigatoria'];

if($_GET['repOS']){
	$referencia_prod = $_GET['referencia_prod'];
	$voltagem = $_GET['voltagem'];

	if(!empty($voltagem)){
		$cond = " AND voltagem = '$voltagem' ";
	}

	$sql = "SELECT produto,referencia,descricao, voltagem
				FROM tbl_produto
				WHERE (referencia ilike '$referencia_prod%' or referencia_pesquisa  ilike '$referencia_prod%') AND fabrica_i = $login_fabrica
				$cond;";
	$res = pg_query($con,$sql);
	if (pg_numrows($res) == 1){
		$produto = pg_result($res,0,'produto');
		$referencia = pg_result($res,0,'referencia');
		$descricao = pg_result($res,0,'descricao');
		$voltagem = pg_result($res,0,'voltagem');
		$sqlC =	"SELECT count(tbl_comunicado.comunicado)
				FROM tbl_comunicado
				JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
				WHERE tbl_comunicado.fabrica = $login_fabrica
				AND   tbl_comunicado.produto = $produto
				AND   tbl_comunicado.obrigatorio_os_produto IS TRUE
				AND   tbl_comunicado.comunicado NOT IN(SELECT comunicado FROM tbl_comunicado_posto_blackedecker WHERE comunicado = tbl_comunicado.comunicado AND posto = $login_posto);";
		$resC = pg_query($con,$sqlC);
		$total = pg_result($resC,0,0);
		if($total > 0){
			echo "OK|$referencia|$descricao|$voltagem";
		} else {
			echo "NO";
		}
	}else {
		echo "NO";
	}
	exit;
}

#-------- Libera digita��o de OS pelo distribuidor ---------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	$distribuidor_digita = pg_fetch_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}
#----------------------------------------------------------------

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);

/*======= <PHP> FUN�OES DOS BOT�ES DE A��O =========*/

// fabio 19/11/2007 - Verifica se est� em Interven��o
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen($os)>0){
	$sqlInter ="SELECT  status_os
				FROM    tbl_os_status
				WHERE   os = $os
				AND status_os IN (62,64,65,72,73,87,88)
				ORDER BY data DESC
				LIMIT 1";
	$resInter = pg_query ($con,$sqlInter) ;
	if (@pg_num_rows($resInter) > 0) {
		$status = pg_fetch_result ($resInter,0,status_os);
		if ($status=='62' OR $status=='72' OR $status=='87') {
			header ("Location: os_finalizada.php?os=$os");
			exit;
		}
		if ($status=='65') {
			header ("Location: os_press.php?os=$os");
			exit;
		}
	}
}

$btn_acao = strtolower ($_POST['btn_acao']);

//  Valida��o de CNPJ/CPF
/* MLG HD 175044    */
/*  11/01/2011 - Adicionando valida��o de n� de s�rie */
if (!function_exists('checaCPF')) {
    function checaCPF ($cpf,$return_str = true) {
        global $con, $login_fabrica;// Para conectar com o banco...

	$cpf = preg_replace('/\D/','',$cpf);   // Limpa o CPF

        //  23/12/2009 HD 186382 - a fun��o pula as pr�-OS anteriores � hoje...
        if (($login_fabrica==52 or $login_fabrica == 30 or $login_fabrica == 88)  and
            strlen($_REQUEST['pre_os'])>0 and
            date_to_timestamp($_REQUEST['data_abertura']) < strtotime('2009-12-24')) return $cpf;
        if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) false;

		if(strlen($cpf) > 0){
			$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
			if ($res_cpf === false) {
				return ($return_str) ? pg_last_error($con) : false;
			}
		}
        return $cpf;

    }
}

$msg_erro = "";

if ($btn_acao == "continuar") {
	$os = $_POST['os'];
	$imprimir_os = $_POST["imprimir_os"];

	$sua_os_offline = $_POST['sua_os_offline'];
	if (strlen (trim ($sua_os_offline)) == 0) {
		$sua_os_offline = 'null';
	}else{
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}


	$sua_os = $_POST['sua_os'];
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o n�mero da OS Fabricante.";
		}
	}else{
		$sua_os = "'" . $sua_os . "'" ;
	}

	##### IN�CIO DA VALIDA��O DOS CAMPOS #####

	$locacao = trim($_POST["locacao"]);
	if (strlen($locacao) > 0) {
		$x_locacao = "7";
	}else{
		$x_locacao = "null";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

    if ($tipo_atendimento == 'null' && $garantia_pecas) {
        $sqlTipo = "
            SELECT  tipo_atendimento
            FROM    tbl_tipo_atendimento
            WHERE   fabrica = $login_fabrica
            AND     descricao ILIKE 'Devolu%o de Pe%as'
        ";
        $resTipo = pg_query($con,$sqlTipo);
        $tipo_atendimento = pg_fetch_result($resTipo,0,tipo_atendimento);
    }


	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(",","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);

	if (strlen($produto_referencia) == 0) {
		$produto_referencia = 'null';
		$msg_erro .= " Digite o produto.";
		$campos_erro[] = "produto";
	}else{
		$produto_referencia = "'".$produto_referencia."'" ;
	}

    $garantia_pecas = filter_input(INPUT_POST,'garantia_pecas');

	$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($xdata_abertura == 'null') $msg_erro .= " Digite a data de abertura da OS.";
	$cdata_abertura = str_replace("'","",$xdata_abertura);

	##############################################################
	# AVISO PARA POSTOS DA BLACK & DECKER
	# Verifica se data de abertura da OS � inferior a 01/09/2005
	//if($login_posto == 13853 OR $login_posto == 13854 OR $login_posto == 13855 OR $login_posto == 11847 OR $login_posto == 13856 OR $login_posto ==  1828 OR $login_posto ==  1292 OR $login_posto ==  1472 OR $login_posto ==  1396 OR $login_posto == 13857 OR $login_posto ==  1488 OR $login_posto == 13858 OR $login_posto == 13750 OR $login_posto == 13859 OR $login_posto == 13860 OR $login_posto == 13861 OR $login_posto == 13862 OR $login_posto == 13863 OR $login_posto == 13864 OR $login_posto == 13865 OR $login_posto == 5260 OR $login_posto == 2472 OR $login_posto == 5258 OR $login_posto == 5352){

	##############################################################
	if ($login_fabrica == 1) {
		$sdata_abertura = str_replace("-","",$cdata_abertura);

		// liberados pela Fabiola em 05/01/2006
		if($login_posto == 5089){ // liberados pela Fabiola em 20/03/2006
			if ($sdata_abertura < 20050101)
				$msg_erro = "Erro. Data de abertura inferior a 01/01/2005.<br>Lan�amento restrito �s OSs com data de lan�amento superior a 01/01/2005.";
		}elseif($login_posto == 5059 OR $login_posto == 5212){
			if ($sdata_abertura < 20050502)
				$msg_erro = "Erro. Data de abertura inferior a 02/05/2005.<br>Lan�amento restrito �s OSs com data de lan�amento superior a 01/05/2005.";
		}else{
			if ($sdata_abertura < 20050901){
				$msg_erro = "Erro. Data de abertura inferior a 01/09/2005.<br>OS deve ser lan�ada no sistema antigo at� 30/09. <br>";
				$campos_erro[] = "data_abertura";
			}
		}
	}
	##############################################################


	if (strlen($_POST['consumidor_revenda']) == 0) $msg_erro .= " Selecione consumidor ou revenda.";
	else           $xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";

	if (strlen(trim($_POST['consumidor_nome'])) == 0) {
		if($login_fabrica == 1) { //HD 17717
			$msg_erro .="Digite o nome do consumidor<BR>";
			$campos_erro[] = "consumidor_nome";
		}else {
			$xconsumidor_nome = 'null';
		}
	}else {
		$xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";
	}

	if (strlen(trim($_POST['fisica_juridica'])) == 0 AND $login_fabrica == 1) {
		$msg_erro .="Escolha o Tipo Consumidor<BR>";
		$campos_erro[] = "consumidor_tipo";
	}else{
		$xfisica_juridica = "'".$_POST['fisica_juridica']."'";
	}

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	if (strlen($consumidor_cpf)>0) {

		$validaCPF      = checaCPF($consumidor_cpf, true);
		if (!is_numeric($validaCPF)) {
			$msg_erro .= ($xfisica_juridica == "'F'") ? 'CPF inv�lido' : 'CNPJ inv�lido';
			$consumidor_cpf = 'null';
			$validaCPF = false;
		} else {
			$xconsumidor_cpf = "'$validaCPF'"; // A fun��o devolve o CPF ou CNPJ 'limpo'
			$validaCPF = true;
		}
	} else {
		$validaCPF = null;
		$xconsumidor_cpf = 'null';
	}



	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

	if (strlen(trim($_POST['consumidor_celular'])) == 0) $xconsumidor_celular = 'null';
	else             $xconsumidor_celular = "'".trim($_POST['consumidor_celular'])."'";

	if (!empty($xconsumidor_celular)) {
		$msg_erro .= valida_celular(trim($_POST['consumidor_celular']));
	}

		##takashi 02-09
		$xconsumidor_endereco	= trim ($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 OR ($login_fabrica == 1 AND $xconsumidor_revenda<>"'R'")) {
			if (strlen($xconsumidor_endereco) == 0) {
				$msg_erro .= " Digite o endere�o do consumidor. <br>";
				$campos_erro[] = "endereco_consumidor";
			}
		}
        $xconsumidor_numero      = filter_input(INPUT_POST,'consumidor_numero');
        $xconsumidor_complemento = filter_input(INPUT_POST,'consumidor_complemento') ;
        $xconsumidor_bairro      = filter_input(INPUT_POST,'consumidor_bairro') ;
        $xconsumidor_cep         = filter_input(INPUT_POST,'consumidor_cep') ;
        if(filter_input(INPUT_POST,"consumidor_email",FILTER_VALIDATE_EMAIL,FILTER_FLAG_EMAIL_UNICODE)){
            $consumidor_email = filter_input(INPUT_POST,"consumidor_email",FILTER_VALIDATE_EMAIL,FILTER_FLAG_EMAIL_UNICODE);
        } else {
            $msg_erro .= "<br>E-mail de contato obrigat�rio.<br>Caso n�o possuir endere�o eletr�nico, dever� ser informado o e-mail: 'nt@nt.com.br'";
            $campos_erro[] = "consumidor_email";
        }

        $consumidor_profissao = filter_input(INPUT_POST, 'consumidor_profissao');
        $consumidor_profissao = str_replace('"', '', $consumidor_profissao);
	$consumidor_profissao = str_replace("'", "", $consumidor_profissao);
	$consumidor_profissao = retira_acentos($consumidor_profissao);


		if ($login_fabrica == 1 AND $xconsumidor_revenda<>"'R'") {

			if (strlen($xconsumidor_numero) == 0) {
				$msg_erro .= "<br> Digite o n�mero do consumidor (endere�o). <br>";
				$campos_erro[] = "numero_consumidor";
			}
			if (strlen($xconsumidor_bairro) == 0) {
				$msg_erro .= " Digite o bairro do consumidor. <br>";
				$campos_erro[] = "bairro_consumidor";
			}
		}

		if (strlen($xconsumidor_complemento) == 0) $xconsumidor_complemento = "null";
		else                           $xconsumidor_complemento = "'" . $xconsumidor_complemento . "'";

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$xconsumidor_cep = preg_replace("/\D/","",$xconsumidor_cep);

		if (strlen($xconsumidor_cep) != 8) $xconsumidor_cep = 'null';
		else                   $xconsumidor_cep = "'$xconsumidor_cep'";
		##takashi 02-09


	if (strlen ($_POST['troca_faturada']) == 0){ $xtroca_faturada = 'null';
		$x_motivo_troca = "null";

	}else{        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";
	$x_motivo_troca = trim ($_POST['motivo_troca']);
	if (strlen($x_motivo_troca) == 0) $x_motivo_troca = "null";

	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";
	if (strlen(trim($_POST['revenda_nome'])) == 0) $xrevenda_nome = 'null';
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	$xrevenda_cep = preg_replace('/\D/', '', $_POST['revenda_cep']) ;
	/*takashi HD 931  21-12*/

	if (strlen($xrevenda_cep) != 8) $xrevenda_cep = "null";
	else $xrevenda_cep = "'$xrevenda_cep'";

	if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
	else $xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";

	if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
	else $xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";

	if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
	else $xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";

	if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
	else $xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";

if (strlen(trim($_POST['revenda_cidade'])) == 0) $xrevenda_cidade='null';
	else $xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";

	if (strlen(trim($_POST['revenda_estado'])) == 0) $xrevenda_estado = 'null';
	else $xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";

}

	$revenda_cnpj = preg_replace('/\D/', '', $_POST['revenda_cnpj']);

	if ($revenda_cnpj != '') {
		$valida_cnpj = checaCPF($revenda_cnpj, false);
		if (!$valida_cnpj) {
			$msg_erro .= "CNPJ da revenda inv�lida";
		}
	}
	/*Se o posto digitar a revenda Black & Decker - cnpj 53296273/0001-91 o sistema dever� apresentar uma mensagem de alerta: Produtos de estoque de revenda dever�o ser digitados na op��o CADASTRO DE ORDEM DE SERVI�O DE REVENDA. Se o produto em quest�o for de estoque de revenda, gentileza digitar nessa op��o. Pois em caso de digita��es incorretas, a B&D far� a exclus�o da OS.*/
	if($xtroca_faturada<>"'t'"){
	if($revenda_cnpj=="53296273000191" and 1==2){
		echo "<script>alert('"._("Produtos de estoque de revenda dever�o ser digitados na op��o CADASTRO DE ORDEM DE SERVI�O DE REVENDA. Se o produto em quest�o for de estoque de revenda, gentileza digitar nessa op��o. Pois em caso de digita��es incorretas, a B&D far� a exclus�o da OS.")."')</script>";

	}
	//if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inv�lido.<BR>";
	//if (($login_fabrica==11) and ($login_fabrica==6)){
	if ($login_fabrica==11){
		if (strlen($revenda_cnpj) == 0)  $msg_erro .= " Insira o CNPJ da Revenda.<BR>";
		else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

	}else{
	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";
	}
	if (strlen(trim($_POST['revenda_nome'])) == 0) {
		$msg_erro .= " Digite o nome da revenda. <br>";
		$campos_erro[] = "nome_revendo";
	}
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";


//=====================revenda
	$xrevenda_cep = trim ($_POST['revenda_cep']) ;
	$xrevenda_cep = str_replace (".","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("-","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("/","",$xrevenda_cep);
	$xrevenda_cep = str_replace (",","",$xrevenda_cep);
	$xrevenda_cep = str_replace (" ","",$xrevenda_cep);
	$xrevenda_cep = substr ($xrevenda_cep,0,8);
	/*takashi HD 931  21-12*/
	if (strlen ($_POST['revenda_cnpj']) == 0 and $login_fabrica==3) $msg_erro .= " Digite o CNPJ da 	Revenda.<BR>";
	if (strlen($xrevenda_cep) == 0) $xrevenda_cep = "null";
	else $xrevenda_cep = "'" . $xrevenda_cep . "'";

	//if (strlen(trim($_POST['revenda_cep'])) == 0) $xrevenda_cep = 'null';
	//else $xrevenda_cep = "'".str_replace("'","",trim($_POST['revenda_cep']))."'";

	if (strlen(trim($_POST['revenda_endereco'])) == 0) $xrevenda_endereco = 'null';
	else $xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";

	if (strlen(trim($_POST['revenda_numero'])) == 0) $xrevenda_numero = 'null';
	else $xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";

	if (strlen(trim($_POST['revenda_complemento'])) == 0) $xrevenda_complemento = 'null';
	else $xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";

	if (strlen(trim($_POST['revenda_bairro'])) == 0) $xrevenda_bairro = 'null';
	else $xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";

	if (strlen(trim($_POST['revenda_cidade'])) == 0) {
		$msg_erro .= " Digite a cidade da revenda. <br>";
		$campos_erro[] = "cidade_revenda";
	}
	else $xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";

	if (strlen(trim($_POST['revenda_estado'])) == 0){
		$msg_erro .= " Digite o estado da revenda. <br>";
		$campos_erro[] = "estado_revenda";
	}
	else $xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
//=====================revenda
}

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if (in_array($login_fabrica,array(14,6,24,11,1))) {
		if ($xnota_fiscal == 'null'){
			$msg_erro .= "Digite o n�mero da nota fiscal.<br />";
			$campos_erro[] = "Nota Fiscal";
		}
	}

	$xconsumidor_estado = $_POST['consumidor_estado'];
	$xconsumidor_cidade = $_POST['consumidor_cidade'];

	if(strlen(trim($xconsumidor_estado))==0){
		$msg_erro .= "Digite o estado do consumidor. <br>";
		$campos_erro[] = "consumidor estado";
	}else{
		$xconsumidor_estado = "'".trim($xconsumidor_estado)."'";
	}
	if(strlen(trim($xconsumidor_cidade))==0){
		$msg_erro .= "Digite a cidade do consumidor. <br>";
		$campos_erro[] = "consumidor cidade";
	}else{
		$xconsumidor_cidade = "'".trim($xconsumidor_cidade)."'";
	}



	$qtde_produtos = trim ($_POST['qtde_produtos']);
	if (strlen ($qtde_produtos) == 0) $qtde_produtos = "1";

    $data_nf = filter_input(INPUT_POST,'data_nf');
    if (strlen ($data_nf) == 0) {
        $msg_erro .= " Digite a data de compra.<br />";
        $campos_erro[] = "Data Compra";
    }

    $xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
    $cdata_nf = str_replace("'","",$xdata_nf);

    if ($garantia_pecas && !empty($data_abertura) && !empty($data_nf)) {
        $dataHoje           = new DateTime('now');
        $dataHoje2          = new DateTime($cdata_abertura);
        $dataAberturaTeste  = new DateTime($cdata_abertura);
        $dataNfTeste        = new DateTime($cdata_nf);

        $dataHoje->sub(new DateInterval('P7D'));
        $dataHoje2->sub(new DateInterval('P90D'));
        if ($dataHoje > $dataAberturaTeste) {

			$msg_erro .= "A data de abertura dessa ordem de servi�o poder� ser no m�ximo 7 dias anterior � data de digita��o.<br>";
            $campos_erro[] = "Data Abertura";
        }
        if ($dataHoje2 > $dataNfTeste) {
			$msg_erro .= "Pe�as com mais de 90 dias de compra n�o ser�o aceitas.<br>";
            $campos_erro[] = "Data Abertura";
        }


    }

//pedido por Leandrot tectoy, feito por takashi 04/08
	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.<br />";


	if($xdata_nf > $xdata_abertura and $xdata_nf <> 'null' and $xtroca_faturada <> 't') $msg_erro .= "A data da nota n�o pode ser maior que a data de abertura";

	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";

	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";
//pedido leandro tectoy
    if (strlen ($_POST['aparencia_produto']) == 0 && !$garantia_pecas && $tipo_atendimento != 334) {
        $msg_erro .= " Digite a aparencia do produto.<BR />";
        $campos_erro[] = "aparencia produto";
    }
//
	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";
//pedido leandro tectoy
	if($login_fabrica==6){
		if (strlen ($_POST['acessorios']) == 0) $msg_erro .= " Digite os acessorios do produto.<BR>";
	}

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0)
		$xdefeito_reclamado_descricao = 'null';
	else
		$xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";

	if (strlen(trim($_POST['obs'])) == 0) $xobs = 'null';
	else             $xobs = "'".trim($_POST['obs'])."'";

	if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) $xquem_abriu_chamado = 'null';
	else             $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";


	//if (strlen($_POST['type']) == 0) $xtype = 'null';
	//else             $xtype = "'".$_POST['type']."'";

	/*if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";*/

//takashi 22/12 HD 925
/*A satisfa��o 30 dias � gerada somente para consumidores. Em nenhuma hip�tese poder� ser permitido a digita��o do laudo t�cnico para OS's em que foi selecionada a op��o revenda.*/

	if($xconsumidor_revenda=="'R'" and $xsatisfacao=="'t'"){
		$msg_erro .= "Ordem de Servi�o de Revenda n�o pode ser 30 dias Satisfa��o DeWALT/Porter Cable.<Br>";
	}

	$defeito_reclamado_descricao = trim ($_POST['defeito_reclamado_descricao']);

	if (strlen($defeito_reclamado_descricao) == 0){
		$msg_erro .= "Digite um defeito reclamado.<br />";
		$campos_erro[] = "defeito_reclamado";
	}

	$defeito_reclamado = trim ($_POST['defeito_reclamado']);

	/*
	if ($defeito_reclamado_descricao == '0') {
	$msg_erro .= "Selecione o defeito reclamado.<BR>";}
	*/

	if ($login_fabrica == 14 ){
		if (strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
			$sql = "SELECT  tbl_produto.numero_serie_obrigatorio
					FROM    tbl_produto
					JOIN    tbl_linha on tbl_linha.linha = tbl_produto.linha
					WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)
					AND     tbl_linha.fabrica = $login_fabrica";
			$res = @pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$numero_serie_obrigatorio = trim(pg_fetch_result($res,0,numero_serie_obrigatorio));

				if ($numero_serie_obrigatorio == 't') {
					$msg_erro .= "<br>N� de S�rie $produto_referencia � obrigat�rio.<br />";
				}
			}
		}
	}

	##### FIM DA VALIDA��O DOS CAMPOS #####


	$os_reincidente = "'f'";

	##### Verifica��o se o n� de s�rie � reincidente para a Tectoy #####
	if ($login_fabrica == 6) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = @pg_query($con,$sqlX);
		$data_inicial = pg_fetch_result($resX,0,0);

		$sqlX = "SELECT to_char (current_date + INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = @pg_query($con,$sqlX);
		$data_final = pg_fetch_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao,
							tbl_os_extra.extrato
					FROM    tbl_os
					JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					WHERE   UPPER(tbl_os.serie)   = UPPER('$produto_serie')
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os.posto   = $posto
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$xxxos      = trim(pg_fetch_result($res,0,os));
				$xxxsua_os  = trim(pg_fetch_result($res,0,sua_os));
				$xxxextrato = trim(pg_fetch_result($res,0,extrato));

				if (strlen($xxxextrato) == 0) {
					$msg_erro .= "N� de S�rie $produto_serie digitado � reincidente.<br>
					Favor reabrir a ordem de servi�o $xxxsua_os e acrescentar itens.<br />";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}


	if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inv�lido';

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inv�lido';

	$produto = 0;

	if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
	else	$voltagem = "'". $_POST['produto_voltagem'] ."'";

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		if(strlen($voltagem_pesquisa) >0 and $voltagem_pesquisa != 'null'){
			$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
		}
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_query ($con,$sql);

	if (@pg_num_rows ($res) == 0) {
		$msg_erro .= " Produto ".($produto_referencia=='null' ? '' : $produto_referencia )." n�o cadastrado ou voltagem incorreta.<br />";
	}else{
		$produto = @pg_fetch_result ($res,0,produto);
		$linha   = @pg_fetch_result ($res,0,linha);
	}

//takashi 22-12 chamado 925
	if ($login_fabrica == 1) {
		if($xconsumidor_revenda=="'R'"){
		/*Quando o posto de servi�os marcar a op��o revenda poder� gravar somente uma OS com um mesmo n�mero de nota fiscal e CNPJ(revenda). Muitos postos est�o usando essa op��o para digitar notas fiscais de revenda com mais de um produto. Portanto, se o posto de servi�os escolher a op��o revenda, digitar uma OS e depois tentar digitar uma outra OS com o CNPJ e nota fiscal j� digitados anteriormente, o sistema dever� apresentar uma mensagem de erro: Dados de nota fiscal j� informados anteriormente na OS.........Sempre que a nota fiscal de estoque da revenda tiver mais que um produto da B&D discriminado, o posto de servi�os dever� fazer a digita��o na op��o: Cadastro OS revenda.*/
			$sql = "SELECT 	os,
							sua_os
						FROM tbl_os
						WHERE fabrica=$login_fabrica
						AND ltrim(nota_fiscal,0) = $xnota_fiscal
						AND revenda_cnpj = $xrevenda_cnpj
						AND excluida IS NOT TRUE
						order by os desc
						limit 1";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
				$os_revenda = trim(pg_fetch_result($res,0,os));
				$sua_os_revenda = trim(pg_fetch_result($res,0,sua_os));
				$msg_erro .= "Dados de nota fiscal j� informados anteriormente na OS $os_revenda. Sempre que a nota fiscal de estoque da revenda tiver mais que um produto da B&D discriminado, o posto de servi�os dever� fazer a digita��o na op��o: Cadastro OS revenda.<Br>";
			}
		}
		/*Alguns postos marcam a op��o consumidor, por�m digitam a revenda no campo nome consumidor, o que caracteriza tamb�m uma OS de revenda. Nesse caso, a Telecontrol dever� sempre comparar o campo o nome consumidor com o campo nome revenda e se nos dois campos os dados forem iguais dever� apresentar a mensagem de erro: Gentileza cadastrar esse produto na op��o CADASTRO DE ORDEM DE SERVI�O DE REVENDA, pois se trata de um produto de estoque da revenda.*/
		if(trim(strtoupper($xconsumidor_nome)) == trim(strtoupper($xrevenda_nome))){
			$msg_erro .= " Gentileza cadastrar esse produto na op��o CADASTRO DE ORDEM DE SERVI�O DE REVENDA, pois se trata de um produto de estoque da revenda.<BR/>";
		}
	}
//takashi 22-12 chamado 925

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = @pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			$xtipo_os_cortesia = "'Compressor'";
		}else{
			$xtipo_os_cortesia = 'null';
		}
	}else{
		$xtipo_os_cortesia = 'null';
	}



	#----------- OS digitada pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto n�o cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_fetch_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto n�o pertence a sua regi�o";
						$posto = $login_posto;
					}else{
						$posto = pg_fetch_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------


	$arr_tdocs = array();

	$ja_anexado = 0;

	if (!empty($os)) {
		$ja_anexado = temNF($os, 'count');
	}

	$limit_sql_docs = $limite_anexos_nf - $ja_anexado;


	$filesByImageUploader = 0;

	if (!empty($_POST['objectid'])) {
		$objectId = $_POST['objectid'];
		$sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs FROM tbl_tdocs WHERE referencia_id = 0 AND referencia = '$objectId' AND contexto = 'os' LIMIT $limit_sql_docs";
		$resDocs = pg_query($con,$sqlDocs);
		$filesByImageUploader = pg_num_rows($resDocs);

		while ($fetch = pg_fetch_assoc($resDocs)) {
			$arr_tdocs[] = $fetch['tdocs'];
		}
	}



	$res = @pg_query($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen ($os_offline) == 0) $os_offline = "null";



	if (strlen($msg_erro) == 0){

		if (strlen ($defeito_reclamado) == 0)
			$defeito_reclamado = "null";

		/*================ INSERE NOVA OS =========================*/

		if (strlen($os) == 0) {
			$sql =	"INSERT INTO tbl_os (
						tipo_atendimento                                               ,
						posto                                                          ,
						fabrica                                                        ,
						sua_os                                                         ,
						sua_os_offline                                                 ,
						data_abertura                                                  ,
						cliente                                                        ,
						revenda                                                        ,
						consumidor_nome                                                ,
						consumidor_cpf                                                 ,
						consumidor_fone                                                ,
						consumidor_celular                                             ,
						consumidor_endereco                                            ,
						consumidor_numero                                              ,
						consumidor_complemento                                         ,
						consumidor_bairro                                              ,
						consumidor_cep                                                 ,
						consumidor_cidade                                              ,
						consumidor_estado                                              ,
						revenda_cnpj                                                   ,
						revenda_nome                                                   ,
						revenda_fone                                                   ,
						nota_fiscal                                                    ,
						data_nf                                                        ,
						produto                                                        ,
						serie                                                          ,
						qtde_produtos                                                  ,
						codigo_fabricacao                                              ,
						aparencia_produto                                              ,
						acessorios                                                     ,
						defeito_reclamado_descricao                                    ,
						obs                                                            ,
						quem_abriu_chamado                                             ,
						consumidor_revenda                                             ,
						tipo_os_cortesia                                               ,
						troca_faturada                                                 ,
						os_offline                                                     ,
						os_reincidente                                                 ,
						digitacao_distribuidor                                         ,
						tipo_os                                                        ,
						defeito_reclamado                                              ,
						motivo_troca                                                   ,
						consumidor_email                                               ,
						fisica_juridica
					) VALUES (
						$tipo_atendimento                                              ,
						$posto                                                         ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$sua_os_offline                                                ,
						$xdata_abertura                                                ,
						null                                                           ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
						$xconsumidor_nome                                              ,
						$xconsumidor_cpf                                               ,
						$xconsumidor_fone                                              ,
						$xconsumidor_celular                                           ,
						'$xconsumidor_endereco'                                        ,
						'$xconsumidor_numero'                                          ,
						$xconsumidor_complemento                                       ,
						'$xconsumidor_bairro'                                          ,
						$xconsumidor_cep                                               ,
						$xconsumidor_cidade                                            ,
						$xconsumidor_estado                                            ,
						$xrevenda_cnpj                                                 ,
						$xrevenda_nome                                                 ,
						$xrevenda_fone                                                 ,
						$xnota_fiscal                                                  ,
						$xdata_nf                                                      ,
						$produto                                                       ,
						$xproduto_serie                                                ,
						$qtde_produtos                                                 ,
						$xcodigo_fabricacao                                            ,
						$xaparencia_produto                                            ,
						$xacessorios                                                   ,
						$xdefeito_reclamado_descricao                                  ,
						$xobs                                                          ,
						$xquem_abriu_chamado                                           ,
						$xconsumidor_revenda                                           ,
						$xtipo_os_cortesia                                             ,
						$xtroca_faturada                                               ,
						$os_offline                                                    ,
						$os_reincidente                                                ,
						$digitacao_distribuidor                                        ,
						$x_locacao                                                     ,
						$defeito_reclamado                                             ,
						$x_motivo_troca                                                ,
						'$consumidor_email'                                            ,
						$xfisica_juridica
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						tipo_atendimento            = $tipo_atendimento                 ,
						data_abertura               = $xdata_abertura                   ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						consumidor_celular          = $xconsumidor_celular              ,
						consumidor_endereco         = '$xconsumidor_endereco'           ,
						consumidor_numero           = '$xconsumidor_numero'             ,
						consumidor_complemento      = $xconsumidor_complemento          ,
						consumidor_bairro           = '$xconsumidor_bairro'             ,
						consumidor_cep              = $xconsumidor_cep                  ,
						consumidor_cidade           = $xconsumidor_cidade               ,
						consumidor_estado           = $xconsumidor_estado               ,
						revenda_cnpj                = $xrevenda_cnpj                    ,
						revenda_nome                = $xrevenda_nome                    ,
						revenda_fone                = $xrevenda_fone                    ,
						nota_fiscal                 = $xnota_fiscal                     ,
						data_nf                     = $xdata_nf                         ,
						serie                       = $xproduto_serie                   ,
						qtde_produtos               = $qtde_produtos                    ,
						codigo_fabricacao           = $xcodigo_fabricacao               ,
						aparencia_produto           = $xaparencia_produto               ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao     ,
						consumidor_revenda          = $xconsumidor_revenda              ,
						troca_faturada              = $xtroca_faturada                  ,
						tipo_os_cortesia            = $xtipo_os_cortesia                ,
						tipo_os                     = $x_locacao                        ,
						defeito_reclamado           = $defeito_reclamado                ,
						motivo_troca                = $x_motivo_troca                   ,
						consumidor_email            = '$consumidor_email'               ,
						fisica_juridica             = $xfisica_juridica
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
//if ($ip == '201.71.54.144') echo $sql;
// 		 echo "$sql";
		$sql_OS = $sql;
		$res = @pg_query ($con,$sql);
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}

		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_fetch_result ($res,0,0);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		if (strlen($os) == 0) {
			$res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
			$os  = pg_fetch_result ($res,0,0);
		}

		$res      = @pg_query ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
		}
		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {

            if (!empty($os) and !empty($consumidor_profissao)) {
                $sql_campos_adicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);

                if (pg_num_rows($qry_campos_adicionais) == 0) {
                    $json_campos_adicionais = json_encode(["consumidor_profissao" => utf8_encode($consumidor_profissao)]);

                    $sql_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '$json_campos_adicionais')";
                } else {
                    $arr_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);
                    $arr_campos_adicionais["consumidor_profissao"] = utf8_encode($consumidor_profissao);

                    $json_campos_adicionais = json_encode($arr_campos_adicionais);

                    $sql_campos_adicionais = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$json_campos_adicionais' WHERE os = $os";
                }

                $qry_campos_adicionais = pg_query($con, $sql_campos_adicionais);
            } else {
                $dataHoje   = new DateTime();
                $dataPrazo  = new DateTime('2018-06-01');
                if ($dataHoje->diff($dataPrazo)->format('%R%a') < 0) {
                    $msg_erro = "� Obrigat�rio o cadastro da profiss�o do consumidor.";
                    $campos_erro[] = "consumidor_profissao";
                }
            }

				//===============================REVENDAAAAAAA

//revenda_cnpj
if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 and strlen ($xrevenda_estado) > 0 ) {
//if (strlen($msg_erro) == 0 AND strlen ($xrevenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 and strlen ($xrevenda_estado) > 0 ) {
$sql = "SELECT fnc_qual_cidade ($xrevenda_cidade,$xrevenda_estado)";
$res = pg_query ($con,$sql);
$monta_sql .= "9: $sql<br>$msg_erro<br><br>";

$xrevenda_cidade = pg_fetch_result ($res,0,0);



$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
$res1 = pg_query ($con,$sql);

$monta_sql .= "10: $sql<br>$msg_erro<br><br>";

if (pg_num_rows($res1) > 0) {
	$revenda = pg_fetch_result ($res1,0,revenda);
	$sql = "UPDATE tbl_revenda SET
				nome		= $xrevenda_nome          ,
				cnpj		= $xrevenda_cnpj          ,
				fone		= $xrevenda_fone          ,
				endereco	= $xrevenda_endereco      ,
				numero		= $xrevenda_numero        ,
				complemento	= $xrevenda_complemento   ,
				bairro		= $xrevenda_bairro        ,
				cep			= $xrevenda_cep           ,
				cidade		= $xrevenda_cidade
			WHERE tbl_revenda.revenda = $revenda";
	$res3 = @pg_query ($con,$sql);

if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);
$monta_sql .= "11: $sql<br>$msg_erro<br><br>";
}else{
$sql = "INSERT INTO tbl_revenda (
			nome,
			cnpj,
			fone,
			endereco,
			numero,
			complemento,
			bairro,
			cep,
			cidade
		) VALUES (
			$xrevenda_nome ,
			$xrevenda_cnpj ,
			$xrevenda_fone ,
			$xrevenda_endereco ,
			$xrevenda_numero ,
			$xrevenda_complemento ,
			$xrevenda_bairro ,
			$xrevenda_cep ,
			$xrevenda_cidade
		)";
$res3 = @pg_query ($con,$sql);

if (strlen (pg_errormessage($con)) > 0) $msg_erro = pg_errormessage ($con);

$monta_sql .= "12: $sql<br>$msg_erro<br><br>";

$sql = "SELECT currval ('seq_revenda')";
$res3 = @pg_query ($con,$sql);
$revenda = @pg_fetch_result ($res3,0,0);
}

			$sql = "UPDATE tbl_os SET revenda = $revenda WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_query ($con,$sql);
$monta_sql .= "13: $sql<br>$msg_erro<br><br>";
//echo "$sql";
}

//===============================REVENDAAAA

            if ($garantia_pecas) {
                $sqlReincidente = "
                    SELECT  tbl_os.os
                    FROM    tbl_os
                    WHERE   fabrica = $login_fabrica
                    AND     nota_fiscal = '$nota_fiscal'
                    AND     (
                                tbl_os.consumidor_cpf   = $xconsumidor_cpf
                            OR  tbl_os.revenda_cnpj     = $xrevenda_cnpj
                            )
                ";
                $resReincidente = pg_query($con,$sqlReincidente);

                if (pg_num_rows($resReincidente) > 0) {
                    $os_reincidente = 't';
                    $xxxos = pg_fetch_result($resReincidente,0,os);

                }
            }

			$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
			$visita_por_km				= trim ($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim ($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim ($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));

			if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';

			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";

			if (str_replace("'","",$os_reincidente) == 't') $sql .= ", os_reincidente = $xxxos ";

			$sql .= "WHERE tbl_os_extra.os = $os";
#if ( $ip == '201.0.9.216' OR $login_posto == 14068) echo nl2br($sql)."<br><br>";
#if ( $ip == '201.0.9.216' OR $login_posto == 14068) flush();
			$res = @pg_query ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
			}

			include_once 'regras/envioObrigatorioNF.php';

			$obriga_anexo = EnvioObrigatorioNF($login_fabrica, $login_posto,'',$tipo_atendimento);

			if ( strlen($msg_erro) == 0 && $login_fabrica == 1) {
                $arr_anexou = array();
				$range_limit = 4 - $filesByImageUploader;

                foreach (range(0, $range_limit) as $idx) {
                    $file = array(
                        "name" => $_FILES["foto_nf"]["name"][$idx][0],
                        "type" => $_FILES["foto_nf"]["type"][$idx][0],
                        "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
                        "error" => $_FILES["foto_nf"]["error"][$idx][0],
                        "size" => $_FILES["foto_nf"]["size"][$idx][0]
                    );

                    if (!empty($file["size"])) {
                        $anexou = anexaNF( $os, $file);
                        if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' � que executou OK
                        $arr_anexou[$idx][0] = $anexou;
                    } else {
                        if ($obriga_anexo) {
                        	if (!temNF($os,'bool') and ($filesByImageUploader == 0)) {
	                            $tmp_erro = 'Anexo de NF obrigat�rio X';
	                            $campos_erro[] = "anexo";
	                        }
                        }
                    }
                }

                if (!empty($tmp_erro) and !in_array(0, $arr_anexou)) {

                    $msg_erro = $tmp_erro;

                }

				if (!empty($os) and !empty($arr_tdocs)) {
					$ids_tdocs = implode(', ', $arr_tdocs);
					$update_tdocs = "UPDATE tbl_tdocs set fabrica = $login_fabrica, referencia = 'os', referencia_id = $os WHERE tdocs IN ($ids_tdocs)";
					$res_tdocs = pg_query($con, $update_tdocs);

					if (pg_affected_rows($res_tdocs) > count($arr_tdocs)) {
						$msg_erro = "Ocorreu um erro ao salvar a OS";
					}
				}

			}


			/*if ( strlen($msg_erro) == 0 && $_POST['satisfacao'] == 't') {

				$anexou = anexaNF( $os, $_FILES['foto_laudo_tecnico']);
				if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' � que executou OK

				if ($_FILES['foto_laudo_tecnico']['size'] == 0 && $obriga_anexo) {

					$msg_erro = 'Para OS em satisfa��o de 90 dias � necess�rio anexar o comprovante de Laudo T�cnico';
					excluirNF($os);

				}

			}*/

			if (strlen ($msg_erro) == 0) {
				$res = @pg_query ($con,"COMMIT TRANSACTION");

				$os_antes = $_POST['os'];
				if(strlen(trim($os_antes)) == 0){
					if(strlen(trim($consumidor_email)) > 0){ //HD-3191657
						$sqlSuaOS = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto
										FROM tbl_os
										JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
										WHERE os = $os";
						$resSuaOS = pg_query($con, $sqlSuaOS);

						if(pg_num_rows($resSuaOS) > 0){
							$codPosto 	= pg_fetch_result($resSuaOS, 0, 'codigo_posto');
							$suaOS 		= pg_fetch_result($resSuaOS, 0, 'sua_os');

							$codPosto = str_replace (" ","",$codPosto);
							$codPosto = str_replace (".","",$codPosto);
							$codPosto = str_replace ("/","",$codPosto);
							$codPosto = str_replace ("-","",$codPosto);

							$osBlack = $codPosto.$suaOS;
						}

						$from_fabrica  = $consumidor_email;
						$from_fabrica_descricao = "Stanley Black&Decker - Ordem de Servi�o";
				        $assunto  = "Stanley Black&Decker - Ordem de Servi�o";
				        $email_admin = "no-reply@telecontrol.com.br";
				        $mensagem = '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_email_2017.png" alt="http://www.blackedecker.com.br" style="float:left;max-height:100px;max-width:310px;" border="0"><br/><br/>';
				        $mensagem .= "<strong>Prezado(a) consumidor(a),</strong><br><br>";
				        $mensagem .= "Foi registrada a ordem de servi�o n� ".$osBlack." para a f�brica, referente ao atendimento de seu produto. <br/><br/>";

				        $host = $_SERVER['HTTP_HOST'];
				        if(strstr($host, "devel.telecontrol") OR strstr($host, "homologacao.telecontrol")){
							$mensagem .= "Para acompanhar o status <a href='http://devel.telecontrol.com.br/~monteiro/telecontrol_teste/HD-3191657ATUALIZADO/externos/institucional/blackos.html'>CLIQUE AQUI</a> ou acesse nosso site comercial na aba servi�os / assist�ncia t�cnica. <br/><br/>";
				        }else{
							$mensagem .= "Para acompanhar o status <a href='https://posvenda.telecontrol.com.br/assist/externos/institucional/black_os.html'>CLIQUE AQUI</a> ou acesse nosso site comercial na aba servi�os / assist�ncia t�cnica. <br/><br/>";
				        }

				        $mensagem .= "***N�o responder este e-mail, pois ele � gerado automaticamente pelo sistema.<br/><br/>";
				        $mensagem .= "Atenciosamente,<br/> Stanley BLACK&DECKER <br/><br/><br/>";
				        $mensagem .= '<img src="https://posvenda.telecontrol.com.br/assist/imagens/logo_black_surv_email_2017.png" alt="http://www.blackedecker.com.br" style="float:left;max-height:100px;max-width:310px;" border="0"><br/><br/><br/>';

				        $headers  = "MIME-Version: 1.0 \r\n";
						$headers .= "Content-type: text/html \r\n";
						$headers .= "From: $from_fabrica_descricao <$email_admin> \r\n";

						$mailTc = new TcComm("smtp@posvenda");
						$res = $mailTc->sendMail(
							$from_fabrica,
							$assunto,
							$mensagem,
							$email_admin
						);
					}
				}
				//hd chamado 3371
				if ($login_fabrica == 1 and $pedir_sua_os == 'f') {
					$sua_os_repetiu = 't';
					while ($sua_os_repetiu == 't') {
						//veriica se esta sua_os � repetida
						$sql_sua_os = " SELECT sua_os
										FROM   tbl_os
										WHERE  fabrica =  $login_fabrica
										AND    posto   =  $login_posto
										AND    sua_os  =  (SELECT sua_os from tbl_os where os = $os)
										AND    os      <> $os";
						$res_sua_os = pg_query($con, $sql_sua_os);

							if (pg_num_rows($res_sua_os) > 0) {
							$sql_sua_os = " UPDATE tbl_posto_fabrica SET sua_os = (sua_os + 1)
											where  tbl_posto_fabrica.fabrica = $login_fabrica
											and    tbl_posto_fabrica.posto   = $login_posto";
							$res_sua_os = pg_query($con, $sql_sua_os);

							$sql_sua_os = " SELECT sua_os
											FROM tbl_posto_fabrica
											WHERE fabrica = $login_fabrica
											AND   posto   = $login_posto";
							$res_sua_os = pg_query($con, $sql_sua_os);
							$sua_os_atual = pg_fetch_result($res_sua_os,0,0);

							$sql_sua_os = " UPDATE tbl_os set sua_os = lpad ('$sua_os_atual',7,'0')
											WHERE  tbl_os.os      = $os
											and    tbl_os.fabrica = $login_fabrica";
							$res_sua_os = pg_query($con, $sql_sua_os);
						} else {
							$sua_os_repetiu = 'f';
						}
					}
				}
				//
				if ($imprimir_os == "imprimir") {
					header ("Location: os_item.php?os=$os&imprimir=1");
					exit;
				}else{
					if($xtroca_faturada=="'t'"){
						header ("Location: os_finalizada.php?os=$os");
						exit;
					}else{
						header ("Location: os_item.php?os=$os");
						exit;
					}
				}
			}else{
				$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Servi�o.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digita��o da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O N�mero da Ordem de Servi�o do fabricante j� esta cadastrado.";

		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}

}

/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen ($os) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_celular                                        ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.consumidor_endereco                                       ,
					tbl_os.consumidor_numero                                         ,
					tbl_os.consumidor_complemento                                    ,
					tbl_os.consumidor_bairro                                         ,
					tbl_os.consumidor_cep                                            ,
					tbl_os.consumidor_email                                          ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.revenda                                                   ,
					tbl_os.nota_fiscal                                               ,
					tbl_os.defeito_reclamado_descricao 	,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_os.type                                                      ,
					tbl_os.satisfacao                                                ,
					tbl_os.laudo_tecnico                                             ,
					tbl_os.tipo_os_cortesia                                          ,
					tbl_os.serie                                                     ,
					tbl_os.qtde_produtos                                             ,
					tbl_os.troca_faturada                                            ,
					tbl_os.acessorios                                                ,
					tbl_os.tipo_os                                                   ,
					tbl_os.fisica_juridica                                           ,
					tbl_os.tipo_atendimento                                          ,
					tbl_produto.referencia                     AS produto_referencia ,
					tbl_produto.descricao                      AS produto_descricao  ,
					tbl_produto.voltagem                       AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN      tbl_produto  ON tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
			LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";
	$res = @pg_query ($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$sua_os				= pg_fetch_result ($res,0,sua_os);
		$data_abertura		= pg_fetch_result ($res,0,data_abertura);
		$consumidor_nome	= pg_fetch_result ($res,0,consumidor_nome);
		$consumidor_cpf 	= pg_fetch_result ($res,0,consumidor_cpf);
		$consumidor_cidade	= pg_fetch_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_fetch_result ($res,0,consumidor_fone);
		$consumidor_celular	= pg_fetch_result ($res,0,consumidor_celular);
		$consumidor_estado	= pg_fetch_result ($res,0,consumidor_estado);
		//takashi 02-09
		$consumidor_endereco	= pg_fetch_result ($res,0,consumidor_endereco);
		$consumidor_numero	= pg_fetch_result ($res,0,consumidor_numero);
		$consumidor_complemento	= pg_fetch_result ($res,0,consumidor_complemento);
		$consumidor_bairro	= pg_fetch_result ($res,0,consumidor_bairro);
		$consumidor_cep		= pg_fetch_result ($res,0,consumidor_cep);
		$consumidor_email	= pg_fetch_result ($res,0,consumidor_email);
		$fisica_juridica	= pg_fetch_result ($res,0,fisica_juridica);
        $tipo_atendimento   = pg_fetch_result ($res,0,tipo_atendimento);
		//takashi 02-09
		$revenda_cnpj		= pg_fetch_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_fetch_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_fetch_result ($res,0,nota_fiscal);
		$defeito_reclamado_descricao = pg_fetch_result ($res,0,defeito_reclamado_descricao);
		$data_nf			= pg_fetch_result ($res,0,data_nf);
		$consumidor_revenda	= pg_fetch_result ($res,0,consumidor_revenda);
		$aparencia_produto	= pg_fetch_result ($res,0,aparencia_produto);
		$acessorios	= pg_fetch_result ($res,0,acessorios);
		$codigo_fabricacao	= pg_fetch_result ($res,0,codigo_fabricacao);
		$type				= pg_fetch_result ($res,0,type);
		$satisfacao			= pg_fetch_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_fetch_result ($res,0,laudo_tecnico);
		$tipo_os_cortesia	= pg_fetch_result ($res,0,tipo_os_cortesia);
		$produto_serie		= pg_fetch_result ($res,0,serie);
		$qtde_produtos		= pg_fetch_result ($res,0,qtde_produtos);
		$produto_referencia	= pg_fetch_result ($res,0,produto_referencia);
		$produto_descricao	= pg_fetch_result ($res,0,produto_descricao);
		$produto_voltagem	= pg_fetch_result ($res,0,produto_voltagem);
		$troca_faturada		= pg_fetch_result ($res,0,troca_faturada);
		$codigo_posto		= pg_fetch_result ($res,0,codigo_posto);
		$tipo_os		= pg_fetch_result ($res,0,tipo_os);
		$xxxrevenda = pg_fetch_result($res,0, revenda);
	if(strlen($xxxrevenda)>0){
		$xsql  = "SELECT tbl_revenda.revenda,
						tbl_revenda.nome,
						tbl_revenda.cnpj,
						tbl_revenda.fone,
						tbl_revenda.endereco,
						tbl_revenda.numero,
						tbl_revenda.complemento,
						tbl_revenda.bairro,
						tbl_revenda.cep,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado
						FROM tbl_revenda
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE tbl_revenda.revenda = $xxxrevenda";
		$res1 = pg_query ($con,$xsql);
		//echo "$xsql";
		if (pg_num_rows($res1) > 0) {
			$revenda_nome = pg_fetch_result ($res1,0,nome);
			$revenda_cnpj = pg_fetch_result ($res1,0,cnpj);
			$revenda_fone = pg_fetch_result ($res1,0,fone);
			$revenda_endereco = pg_fetch_result ($res1,0,endereco);
			$revenda_numero = pg_fetch_result ($res1,0,numero);
			$revenda_complemento = pg_fetch_result ($res1,0,complemento);
			$revenda_bairro = pg_fetch_result ($res1,0,bairro);
			$revenda_cep = pg_fetch_result ($res1,0,cep);
			$revenda_cidade = pg_fetch_result ($res1,0,cidade);
			$revenda_estado = pg_fetch_result ($res1,0,estado);
		}
	}


        $qry_campos_adicionais = pg_query(
            $con,
            "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os"
        );

        if (pg_num_rows($qry_campos_adicionais) > 0) {
            $os_campos_adicionais = json_decode(pg_fetch_result($qry_campos_adicionais, 0, 'campos_adicionais'), true);

            if (!empty($os_campos_adicionais) and  array_key_exists("consumidor_profissao", $os_campos_adicionais)) {
                $consumidor_profissao = utf8_decode($os_campos_adicionais["consumidor_profissao"]);
            }
        }
	}

}



/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$os					= $_POST['os'];
    $garantia_pecas     = $_POST['garantia_pecas'];
    $tipo_atendimento   = $_POST['tipo_atendimento'];
	$sua_os				= $_POST['sua_os'];
	$data_abertura		= $_POST['data_abertura'];
	$consumidor_nome	= $_POST['consumidor_nome'];
	$consumidor_cpf 	= $_POST['consumidor_cpf'];
	$consumidor_cidade	= $_POST['consumidor_cidade'];
	$consumidor_fone	= $_POST['consumidor_fone'];
	$consumidor_celular	= $_POST['consumidor_celular'];
    $consumidor_profissao = trim($_POST['consumidor_profissao']);

	$consumidor_estado	= $_POST['consumidor_estado'];
	//takashi 02-09
	$consumidor_endereco= $_POST['consumidor_endereco'];
	$consumidor_numero	= $_POST['consumidor_numero'];
	$consumidor_complemento	= $_POST['consumidor_complemento'];
	$consumidor_bairro	= $_POST['consumidor_bairro'];
	$consumidor_cep		= $_POST['consumidor_cep'];
	$consumidor_email	= $_POST['consumidor_email'];
	$fisica_juridica	= $_POST['fisica_juridica'];

	//takashi 02-09
	$revenda_cnpj		= $_POST['revenda_cnpj'];
	$revenda_nome		= $_POST['revenda_nome'];
	$nota_fiscal		= $_POST['nota_fiscal'];
	$data_nf			= $_POST['data_nf'];
	$produto_referencia	= $_POST['produto_referencia'];
	$produto_descricao	= $_POST['produto_descricao'];
	$produto_voltagem	= $_POST['produto_voltagem'];
	$produto_serie		= $_POST['produto_serie'];
	$qtde_produtos		= $_POST['qtde_produtos'];
	$cor				= $_POST['cor'];
	$consumidor_revenda	= $_POST['consumidor_revenda'];
	$acessorios	= $_POST['acessorios'];
	$type				= $_POST['type'];
	// $satisfacao			= $_POST['satisfacao'];
	// $laudo_tecnico		= $_POST['laudo_tecnico'];

	$obs				= $_POST['obs'];
//	$chamado			= $_POST['chamado'];
	$quem_abriu_chamado = $_POST['quem_abriu_chamado'];
	$taxa_visita				= $_POST['taxa_visita'];
	$visita_por_km				= $_POST['visita_por_km'];
	$hora_tecnica				= $_POST['hora_tecnica'];
	$regulagem_peso_padrao		= $_POST['regulagem_peso_padrao'];
	$certificado_conformidade	= $_POST['certificado_conformidade'];
	$valor_diaria				= $_POST['valor_diaria'];
	$codigo_posto				= $_POST['codigo_posto'];

	$locacao					= $_POST['locacao'];
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PAR�METRO PARA O CABE�ALHO (n�o esquecer ===========*/

/* $title = Aparece no sub-menu e no t�tulo do Browser ===== */
$title = "Cadastro de Ordem de Servi�o";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';
//echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$digita_os = pg_fetch_result ($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permiss�o de acesso.</H4>";
	exit;
}

?>

<!--=============== <FUN��ES> ================================!-->


<? include "javascript_pesquisas_novo.php" ?>
<script type="text/javascript" src='ajax.js'></script>
<script type="text/javascript" src='ajax_cep.js'></script>

<script type="text/javascript" src='js/jquery.modal.js'></script>
<script type="text/javascript" src="admin/js/jquery.corner.js"></script>
<script type="text/javascript" src="admin/js/thickbox.js"></script>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<!-- <script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script> -->
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script type="text/javascript">

	$(document).ready(function(){

	$('.black').css("height", "10%");
	$("input[name='data_abertura']").datepick({startdate:'01/01/2000'});
	$("input[name='data_nf']").datepick({startdate:'01/01/2000'});

		if (typeof f == 'undefined') var f = 0;
		var program_self = window.location.pathname;
		var blocoNF;
		$('#anexos img.excluir_NF').click(function() {

			blocoNF = (f == 20)  ? $("table#anexos") : $('table#anexos').parent();

			var nota = $(this).attr('name');

			if (!nota) {
				nota = $(this).attr('data-id');
			}

			// Se n�o estiver certinho, que deveria, limpa a string
			nota = nota.replace(/^https?:\/\/[a-z0-9.-]+\//, '')
			if (nota.indexOf('?')>-1) nota = nota.substr(0, nota.indexOf('?'));

			var	excluir_str	= 'Confirma a exclus�o do arquivo "' + nota + '" ?';
			if (confirm(excluir_str) ==	false) return false;

			$.post(program_self, {
				'excluir_nf': nota,
				'ajax':       'excluir_nf'
			},
			function(data) {
				var r = data.split('|');
				//console.log("'" + r[0] + "'\n" + r[2]);
				if (r[0] ==	'ok') {
					alert('Imagem exclu�da com �xito');
					$("#tc_img_uploader").show();
					//if (r[1].indexOf('<tr')>0) blocoNF.html(r[1]); // S� se vier uma outra tabela!
					//if (r[1] == '')            blocoNF.remove();
				} else {
					alert('Erro ao excluir o arquivo. '+r[1]);
				}
			});

		});


		$("input[name='consumidor_cep']").blur(function() {
			$("input[name='consumidor_numero']").focus();
		});
		$("input[name='revenda_cep']").blur(function() {
			$("input[name='revenda_numero']").focus();
		});


			function verificaLaudoTecnico() {
				if ( $("#satisfacao").is(':checked') ) {

					$("#anexaLaudo").show();

				} else {

					$("#anexaLaudo").hide();

				}

			}

			verificaLaudoTecnico();

			$("#satisfacao").live('click', function(){

				verificaLaudoTecnico();

			});


		Shadowbox.init({
			skipSetup: true,
			enableKeys: false,
				modal: true
		});

		$('#confirm').jqm({
			overlay: 60,
			overlayClass: 'overlay',
			modal: true,
			trigger: false
		});
		displayText('&nbsp;');
		$("input[rel='fone']").maskedinput("(99) 9999-9999");
		$("input[rel='celular']").maskedinput("(99) 99999-9999");
		$("input[rel='data']").maskedinput("99/99/9999");
		$('#revenda_cnpj').maskedinput('99.999.999/9999-99');
		$('input:text').not('input[rel=fone],input[rel=celular],input[rel=data],[name*=email],[name*=cnpj],[name*=cpf]').keyup(function() {
			return somenteMaiusculaSemAcento(this);
		});


        $("#consumidor_email").css("display","none");
        $("font.consumidor_email").css("display","none");

        $("input[name=consumidor_possui_email]").click(function(){
            var valor = $("input[name=consumidor_possui_email]:checked").val();

            if (valor == "sim") {
                $("#consumidor_email").css("display","block");
                $("font.consumidor_email").css("display","block");
                $("#consumidor_email").val("");
            } else if (valor == "nao") {
                $("#consumidor_email").css("display","none");
                $("font.consumidor_email").css("display","none");
                $("#consumidor_email").val("nt@nt.com.br");
            }
        });

	});

	function addAnexoUpload()
    {
        var tpl = $("#anexoTpl").html();
        var id = $("#qtde_anexos").val();

        if (id == "5") {
            return;
        }

//         console.log(tpl);
        var div = "<div id = anexo_"+id+">" + tpl.replace('@ID@', id) + "</div>";
        $("#qtde_anexos").val(parseInt(id) + 1);

        $("#input_anexos").append(div);

        if (id >= 1) {
            $("#anexo_"+id).find("label").css("display","none");
        }
    }

	function fnc_pesquisa_produto2 (descricao, referencia, posicao) {
		var descricao  = jQuery.trim(descricao.value);
		var referencia = jQuery.trim(referencia.value);

		if (descricao.length > 2 || referencia.length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia + "&posicao=" + posicao + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>",
				player:	"iframe",
				title:		"Pesquisa Produto",
				width:	800,
				height:	500,
				options:{
					onClose: function(){ setTimeout('checarComunicadoProduto()', 500); }
				}
			});
		}else{
			alert("Preencha toda ou parte da informa��o para realizar a pesquisa!");
		}

	}


	function retorna_numero_serie(produto,referencia,descricao,posicao,cnpj_revenda,nome_revenda,fone_revenda,email_revenda,serie,voltagem){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
		gravaDados("produto_voltagem",voltagem);
	}
	function retorna_numero_serie(produto,referencia,descricao,posicao,cnpj_revenda,nome_revenda,fone_revenda,email_revenda,serie,voltagem){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
		gravaDados("produto_voltagem",voltagem);
	}

	function checarComunicadoProduto(){
		var referencia = $("input[name=produto_referencia]").val();
		var voltagem = $("input[name=produto_voltagem]").val();
		var btn_acao = $("input[name=btn_acao]").val();
		var leitura = $("#leitura_comunicado").val();

		$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?repOS=1&referencia_prod="+referencia+"&voltagem="+voltagem,
			cache: false,
			success: function(data) {
				retorno = data.split('|');
				if(retorno[0] == "OK"){
					$("input[name=produto_referencia]").val(retorno[1]);
					$("input[name=produto_descricao]").val(retorno[2]);
					$("input[name=produto_voltagem]").val(retorno[3]);
					if (leitura != 1){
						Shadowbox.open({
							content:	"comunicado_produto_frame.php?referencia=" + referencia,
							player:	"iframe",
							title:	"Comunicado Produto",
							width:	490,
							/*options:
							{
								modal: true,
								onClose: function(){},
								onFinish: layout_bloqueado(),
							}*/
						});
					}
				}
			}
		});
	}

	function layout_bloqueado(){
		$('#sb-nav-close').attr('style','visibility:hidden');
	}

	function layout_desbloqueado(){
		$('#sb-nav-close').attr('style','visibility:visible');
	}


</script>

<div class="jqmConfirm" id="confirm">
	<div class="jqmConfirmWindow">
		<div class="jqmConfirmTitle clearfix">
			<a href="#" class="jqmClose"><em>Fechar</em></a>
			<h1>Aten��o!</h1>
		</div>
		<div class="jqmConfirmContent">
			<p class="jqmConfirmMsg"></p>
		</div>
		<input type="submit" value="Li e Confirmo" />
	</div>
</div>

<script type="text/javascript">

	/* Mensagem de aten��o para a Black&Decker !! */
	var text_atencao = '<p class=atencao><b>Aten��o:</b> A revenda informada nessa O.S. � a pr�pria B&D e nesse caso � necess�rio seguir as orienta��es abaixo:</p>'+
		'<p><b>1</b> - Se o produto informado for de estoque de revenda dever� ser digitado na OS de revenda. Portanto, n�o conclua esse cadastro e comece um novo cadastro no link Abertura de OS de revenda</p>'+
		'<p><b>2</b> - Se o produto for de locadora verificar se est� no prazo de 6 meses de garantia, pois n�o cobrimos a garantia ap�s esse prazo. As OS�s de locadora digitadas com produtos com mais de 6 meses de compra ser�o canceladas. Lembrando que, os produtos de locadoras s�o carimbados na f�brica com a especifica��o locadora e, portanto precisam estar com esse carimbo.</p>'+
		'<p><b>3</b> - Se houver uma outra situa��o diferente das mencionadas no item 1 e 2 gentileza informar o que houve na observa��o da ordem de servi�o.</p>';

	function confirme() {
		$.get("os_info_black2.php?sem_acao=true", function(data) {
			$('#confirm')
				.jqmShow()
				.find('p.jqmConfirmMsg')
				.html(data)
				.end()
				.find(':submit:visible')
				.click(function(){
					$('#confirm').jqmHide();
					if(this.value == 'Li e Confirmo'){
						document.frm_os.btn_acao.value='continuar' ;
						document.frm_os.submit();
					}
				});
		});
	}

	/* Fun��o que verifica a Revenda para mostrar uma mensagem - HD 7849 - Fabio Nowaki*/
	function verificarRevenda(){

		var cnpj_temp =  jQuery.trim($('#revenda_cnpj').val());

		cnpj_temp = ""+cnpj_temp+"";
		for (i=0;i<7;i++){
			cnpj_temp = cnpj_temp.replace(".","");
			cnpj_temp = cnpj_temp.replace(",","");
			cnpj_temp = cnpj_temp.replace("-","");
			cnpj_temp = cnpj_temp.replace(" ","");
			cnpj_temp = cnpj_temp.replace("/","");
		}

		var lista_cnpj = [
			'53296273000191',
			'53296273003298',
			'03997959000212',
			'03997959000301'
		];

		/* 53296273000191 */
		/*if ( cnpj_temp == "53296273000191" ) {*/
		if ($.inArray(cnpj_temp, lista_cnpj) >= 0) {
			confirmou = false;
			confirme();
		}else{
			document.frm_os.btn_acao.value='continuar' ;
			document.frm_os.submit();
		}
	}

	/* Fun��o para gravar a OS - Faz a verifica��o da Revenda para mostrar uma mensagem - HD 7849 - Fabio Nowaki*/
	function gravarOS(){
		if (document.frm_os.btn_acao.value == '' ) {
			verificarRevenda();
		} else {
				alert ('N�o clique no bot�o voltar do navegador, utilize somente os bot�es da tela')
		}
	}

	function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
		gravaDados("produto_voltagem",voltagem);
	}

	function retorna_peca(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email){
		gravaDados("revenda_nome",nome);
		gravaDados("revenda_cnpj",cnpj);
		gravaDados("revenda_fone",fone);
		gravaDados("revenda_cep",cep);
		gravaDados("revenda_endereco",endereco);
		gravaDados("revenda_numero",numero);
		gravaDados("revenda_complemento",complemento);
		gravaDados("revenda_bairro",bairro);
		gravaDados("revenda_cidade",nome_cidade);
		gravaDados("revenda_estado",estado);
		$("#revenda_estado").val(estado); //2914204

		monta_cidade(cnpj);
	}

	function monta_cidade(cnpj){//2914204
      if(cnpj.length > 0){
          $.ajax({
              url: "<?php echo $_SERVER['PHP_SELF']; ?>?monta_cidade=sim&cnpj_revenda="+cnpj,
              cache: false,
              success: function(data) {
                  retorno = data;
                  $("#revenda_cidade").html(retorno);
              }
          });
      }
    }
</script>


<style>
	@import "plugins/jquery/datepick/telecontrol.datepick.css";

	.overlay {
		background: #000;
	}
	div.jqmConfirm {
		display: none;
		position: fixed;
		top: 20%;
		left: 50%;
		width: 660px;
		margin-left: -330px;
		font-size:12px;
	}

	div.jqmConfirm p{
		color:#0C4489;
		font-size:12px;
	}

	div.jqmConfirm p.atencao{
		color:#F71128;
	}

	div.jqmConfirmWindow {
		height:auto;
		width: auto;
		margin: auto;
		max-width:520px;
		padding: 0 5px 5px 0px;
		background:#F0F5FF;
		border:3px solid #1C5AC4;
		text-align: center;
	}

	.jqmConfirmTitle {
		margin:1px 1px;
		height:16px;
		color:#0A3981;
		background:#FFFFFF;
	}

	.jqmConfirmTitle h1 {
		font-size: 16px;
		font-weight: bold;
		color:#F71128;
	}

	div.jqmConfirmContent {
		padding:2px;
		margin:2px;
		border:1px dotted #111111;
		letter-spacing:0px;
		background-color:#FFF;
		text-align:left;
	}
	div.jqmConfirmContent span {
		color:#800;
		vertical-align: 25px;
		padding: 0 0 0 0;
		font-weight: bold;
	}

	.jqmClose{
		margin-right: 17px;
		margin-left: -30px;
	}

	div.jqmConfirm .jqmClose em {
		/*display:none;*/
	}

	div.jqmConfirm .jqmClose {
		width:20px;
		height:20px;
		display:block;
		float:right;
		clear:right;
		/*background:transparent url(close_icon_double.png) 0 0 no-repeat;*/
	}

	div.jqmConfirm a.jqmClose:hover {
		background-position: 0 -20px;
	}
	div.jqmConfirm input {
		text-align: center;
		width: auto;
		font-size: 13px;
		background-color: #CBD9F8;
		color: #052D4E;
		font-weight: bold;
		border-width: 1px;
		margin-top: 5px;
		padding: 4px 10px 4px 10px;
	}

	* html div.jqmConfirm {
		 position: absolute;
		 top: expression((document.documentElement.scrollTop || document.body.scrollTop) + Math.round(10 * (document.documentElement.offsetHeight || document.body.clientHeight) / 100) + 'px');
	}

	.clearfix:after {
		content: ".";
		display: block;
		height: 0;
		clear: both;
		visibility: hidden;
	}

	.clearfix {
		display: inline-block;
	}

	* html .clearfix {
		height: 1%;
	}
	.clearfix {
		display: block;
	}

	.label_obrigatoria{
		color: rgb(168, 0, 0);
	}

	.frm_obrigatorio{
	    background-color: #FCC !important;
	    border: #888 1px solid ;
	    font:bold 8pt Verdana;
	}
</style>
<?php
// QRCode para Posto de testes. Retirar depois que validar.
?>
<style type ="text/css">
.mobile:hover {
  background: #5b5c8d;
}
.mobile:active{
  background: #373865;
}
.mobile{
  display: inline-flex;
  height: 45px;
  width: 190px;
  background: #373865;
  padding: 5px;
  border-radius: 10px;
  cursor: pointer;
}
.google_play{
  margin-left: 10%;
  display: inline-flex;
  height: 45px;
  padding: 5px;
  cursor: pointer;

}
.google_play > a >span{
  color: #373865;
}
.google_play:hover{
  background: #f3f3f3;
}
.mobile > span{
  font-size: 14px;
  float: right;
  margin-top: 14px;
  margin-right: 14px;
  color: #fac814;
}

.btn-danger{
    width: 58px;
    height: 25px;
    color: #ffffff;
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
    background-color: #da4f49;
    background-image: -moz-linear-gradient(top, #ee5f5b, #bd362f);
    background-image: -o-linear-gradient(top, #ee5f5b, #bd362f);
    background-image: linear-gradient(to bottom, #ee5f5b, #bd362f);
    background-repeat: repeat-x;
    border-color: #bd362f #bd362f #802420;
}
.env-code{
  width: 100%;
  border: solid 3px;
  border-color: #373866;
  width: 205px;
  border-radius: 7px;
  margin-top: 10px;
}

.env-img {
 /*   float: left;*/
    max-width: 150px;
    margin-left: 10px;
    margin-top: 10px;
    display: inline-block;
}

.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px 30px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:center;
}
.content h1 {
    color:black;
    font-size: 120%;
}

fieldset.valores {
    border:1px solid #4E4E4E;
}

fieldset.valores , fieldset.valores div{
    padding: 0.2em;
    font-size:10px;
    width:225px;
}

fieldset.valores label {
    float:left;
    width:43%;
    margin-right:0.2em;
    padding-top:0.2em;
    text-align:right;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

fieldset.valores span {
    font-size:11px;
    font-weight:bold;
}

.anexo_cortesia {
    display:none;
}
#env-images {margin-bottom: 1em}
</style>
<script language="JavaScript">

/* ============= Fun��o PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Fun��o : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.rg			= document.frm_os.consumidor_rg;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.celular  	= document.frm_os.consumidor_celular;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
}


function getHTTPObject() {
	var xmlhttp;
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}

	return xmlhttp;

}
function devolveCNPJ (http,nome,fone,cep,endereco,numero,complemento,bairro,cidade,estado,email) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if(http.responseText.length==0){
				//alert("takashi");
				document.frm_os.revenda_nome.disabled        = false;
				document.frm_os.revenda_fone.disabled        = false;
				document.frm_os.revenda_cidade.disabled      = false;
				document.frm_os.revenda_estado.disabled      = false;
				document.frm_os.revenda_endereco.disabled    = false;
				document.frm_os.revenda_numero.disabled      = false;
				document.frm_os.revenda_complemento.disabled = false;
				document.frm_os.revenda_bairro.disabled      = false;
				document.frm_os.revenda_cep.disabled         = false;
				document.frm_os.revenda_email.disabled       = false;

				document.frm_os.revenda_nome.value        = "";
				document.frm_os.revenda_fone.value        = "";
				document.frm_os.revenda_cidade.value      = "";
				document.frm_os.revenda_estado.value      = "";
				document.frm_os.revenda_endereco.value    = "";
				document.frm_os.revenda_numero.value      = "";
				document.frm_os.revenda_complemento.value = "";
				document.frm_os.revenda_bairro.value      = "";
				document.frm_os.revenda_cep.value         = "";
				document.frm_os.revenda_email.value       = "";
				document.frm_os.revenda_nome.focus();
			}

			if (typeof (results[0]) != 'undefined'){
				nome.value        = results[0];
			//	document.frm_os.revenda_nome.disabled        = true;
			}
			if (typeof (results[1]) != 'undefined'){
				fone.value        = results[1];
			//	document.frm_os.revenda_fone.disabled        = true;
			}
			if (typeof (results[2]) != 'undefined'){
				cep.value         = results[2];
			//	document.frm_os.revenda_cep.disabled         = true;
			}
			if (typeof (results[3]) != 'undefined'){
				endereco.value    = results[3];
			//	document.frm_os.revenda_endereco.disabled    = true;
			}
			if (typeof (results[4]) != 'undefined'){
				numero.value      = results[4];
			//	document.frm_os.revenda_numero.disabled      = true;
			}
			if (typeof (results[5]) != 'undefined'){
				complemento.value = results[5];
			//	document.frm_os.revenda_complemento.disabled = true;
			}
			if (typeof (results[6]) != 'undefined'){
				bairro.value      = results[6];
			//	document.frm_os.revenda_bairro.disabled      = true;
			}
			if (typeof (results[7]) != 'undefined'){
				cidade.value      = results[7];
			//	document.frm_os.revenda_cidade.disabled      = true;
			}
			if (typeof (results[8]) != 'undefined'){
				estado.value      = results[8];
			//	document.frm_os.revenda_estado.disabled      = true;
			}
			if (typeof (results[9]) != 'undefined'){
				email.value      = results[9];
			//	document.frm_os.revenda_email.disabled       = true;
			}
			if(http.responseText.length>0){
				//alert("takashi");
				document.frm_os.aparencia_produto.focus();
			//	alert ("nome "+document.frm_os.revenda_nome.value);
			}
		}
	}
}

function fnc_cnpj_revenda (cnpj,nome,fone,cep,endereco,numero,complemento,bairro,cidade,estado,email) {
//alert('takashi '+escape(cnpj));
var http = getHTTPObject(); // Criado objeto HTTP
	if (nome.value.length == 0 || 1==1) {
	var cnpj = cnpj;
		cnpj = cnpj.replace('.','');
		cnpj = cnpj.replace('.','');
		cnpj = cnpj.replace('-','');
		cnpj = cnpj.replace('/','');
		cnpj = cnpj.replace(' ','');
//alert('cnpj '+cnpj);
	http.open("GET", "ajax_cnpj.php?cnpj="+ escape(cnpj), true);
	http.onreadystatechange = function () {
			devolveCNPJ (http,nome,fone,cep,endereco,numero,complemento,bairro,cidade,estado,email) ;
	} ;
	http.send(null);
	}
}


/*takashi*/

/* ============= Fun��o FORMATA CNPJ =============================
Nome da Fun��o : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digita��o
		Par�m.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
	mycnpj = mycnpj + cnpj;
	myrecord = "revenda_cnpj";
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 6){
		mycnpj = mycnpj + '.';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 10){
		mycnpj = mycnpj + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 15){
		mycnpj = mycnpj + '-';
		window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
	}
}



/* ========== Fun��o AJUSTA CAMPO DE DATAS =========================
Nome da Fun��o : ajustar_data (input, evento)
		Ajusta a formata��o da M�scara de DATAS a medida que ocorre
		a digita��o do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8;
	var DEL=  46;
	var FRENTE=  39;
	var TRAS=  37;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true;
			}
		if ( tecla == 13) return false;
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla);
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}


/* ============= <PHP> VERIFICA SE H� COMUNICADOS =============
		VERIFICA SE TEM COMUNICADOS PARA ESTE PRODUTO E SE TIVER, RETORNA UM
		LINK PARA VISUALIZAR-LO
		F�bio 07/12/2006
=============================================================== */
function trim(str)
{  while(str.charAt(0) == (" ") )
  {  str = str.substring(1);
  }
  while(str.charAt(str.length-1) == " " )
  {  str = str.substring(0,str.length-1);
  }
  return str;
}

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

var http = new Array();
function checarComunicado(fabrica){
	var imagem = document.getElementById('img_comunicado');
	var ref = document.frm_os.produto_referencia.value;

	//imagem.style.visibility = "hidden";
	document.frm_os.link_comunicado.value="";
	imagem.title = "N�O H� COMUNICADO PARA ESTE PRODUTO";
	ref = trim(ref);

	if (ref.length>0){
		var curDateTime = new Date();
		http[curDateTime] = createRequestObject();
		url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
		http[curDateTime].open('get',url);
		http[curDateTime].onreadystatechange = function(){
			if (http[curDateTime].readyState == 4)
			{
				if (http[curDateTime].status == 200 || http[curDateTime].status == 304)
				{
					var response = http[curDateTime].responseText;
					if (response=="ok"){
						document.frm_os.link_comunicado.value="H� COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
						imagem.title = "H� COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
					}
					else {
						document.frm_os.link_comunicado.value="";
						imagem.title = "N�O H� COMUNICADO PARA ESTE PRODUTO";
					}
				}
			}
		}
		http[curDateTime].send(null);
	}
}

function abreComunicado(){
	var ref = document.frm_os.produto_referencia.value;
	var desc = document.frm_os.produto_descricao.value;
	if (document.frm_os.link_comunicado.value!=""){
		url = "pesquisa_comunicado.php?produto=" + ref +"&descricao="+desc;
		window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	}
}

//ajax defeito_reclamado
function listaDefeitos(valor) {
//verifica se o browser tem suporte a ajax
	try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) { try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser n�o tem recursos para uso do Ajax"); ajax = null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa apenas o elemento 1 no option, os outros s�o exclu�dos
	document.forms[0].defeito_reclamado.options.length = 1;
	//opcoes � o nome do campo combo
	idOpcao  = document.getElementById("opcoes");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_produto_antigo.php?produto_referencia="+valor, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange = function() {
		if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
		if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(ajax.responseXML);//ap�s ser processado-chama fun
			} else {idOpcao.innerHTML = "Selecione o produto";//caso n�o seja um arquivo XML emite a mensagem abaixo
					}
		}
	}
	//passa o c�digo do produto escolhido
	var params = "produto_referencia="+valor;
	ajax.send(null);
	}
}

function montaCombo(obj){
	var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
	if(dataArray.length > 0) {//total de elementos contidos na tag cidade
	for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
		 var item = dataArray[i];
		//cont�udo dos campos no arquivo XML
		var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
		var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
		idOpcao.innerHTML = "Selecione o defeito";
		//cria um novo option dinamicamente
		var novo = document.createElement("option");
		novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
		novo.value = codigo;		//atribui um valor
		novo.text  = nome;//atribui um texto
		document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
		}
	} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
	}
}

	window.onload = function(){
		$("#nota_fiscal").keypress(function(e) {
			var c = String.fromCharCode(e.which);
			var allowed = '1234567890';
			if ((e.keyCode != 9 && e.keyCode != 8) && allowed.indexOf(c) < 0) return false;
		});
	}
</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a exist�ncia de uma OS com o mesmo n�mero e em
		caso positivo passa a mensagem para o usu�rio.
=============================================================== -->
<?
//if ($ip == '201.0.9.216') echo $msg_erro;

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de servi�o j� foi cadastrada";
?>

<!-- ============= <HTML> COME�A FORMATA��O ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"� necess�rio informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto n�o � v�lido") !== false ) ) {
		$produto_referencia = trim($_POST["produto_referencia"]);
		$produto_voltagem   = trim($_POST["produto_voltagem"]);
		$sqlT =	"SELECT tbl_lista_basica.type
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
				AND   tbl_produto.voltagem = '$produto_voltagem'
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type
				ORDER BY tbl_lista_basica.type;";
		$resT = @pg_query ($con,$sqlT);
		if (pg_num_rows($resT) > 0) {
			$s = pg_num_rows($resT) - 1;
			for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
				$typeT = pg_fetch_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			$msg_erro .= "<br>Selecione o Type: $result_type";
		}
	}

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
	echo "<!-- ERRO INICIO -->";
	//echo $erro . $msg_erro . "<br><!-- " . $sql . "<br>" . $sql_OS . " -->";
	echo "
		<div class='alerts'>
			<div class=' danger margin-top'>
				<br />
				$erro $msg_erro
			</div>
		</div>
		<br />
	";
	if(trim($msg_erro) == "Favor preencher o c�digo de fabrica��o."){
		$campos_erro[] = "codigo_fabricacao";
	}
	if(trim($msg_erro) == "Favor informar o telefone do consumidor."){
		$campos_erro[] = "telefone_consumidor";
	}


	echo "<!-- ERRO FINAL -->";
?>
	</td>
</tr>
</table>

<? } ?>


<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = @pg_query ($con,$sql);
$hoje = @pg_fetch_result ($res,0,0);

if ($login_fabrica == 15) { ?>
	<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
	<tr>
		<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
			A partir de 01/01/2007, ser� obrigat�rio o CPF do cunsumidor no cadastro das Ordens de Servi�o.
		</td>
	</tr>
	</table>
<? } ?>


<?
//HD 12003
	echo "<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>";
		echo "<TR>";
			echo "<TD>";
				echo "<P align='justify'><FONT COLOR='#ff0000'><B>Importante:<BR>
				Para lan�amento de troca de produto (garantia ou faturada), criamos uma o.s de troca espec�fica. A troca de produto, s� ser� efetuada atrav�s desta nova o.s.
				Por gentileza, <A HREF='os_info_black.php' target='_blanck'>clique aqui</A> para obter informa��es sobre a nova sistem�tica de o.s de troca.</B></FONT></P>";
			echo "</TD>";
		echo "</TR>";
	echo "</TABLE>";
?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
    <tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="left">

		<? if ($login_fabrica == 1 and 1 == 2) { ?>
			<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>
			<tr>
			<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>
			<B>Conforme comunicado de 04/01/2006, as OS's abertas at� o dia 31/12/2005 poder�o ser digitadas at� o dia 31/01/2006.<br>Pedimos aten��o especial com rela��o a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>
			</td>
			</tr>
			</table>

<?
	if ($login_tipo_posto == 90 OR $login_tipo_posto == 36 OR $login_tipo_posto == 82 OR $login_tipo_posto == 83 OR $login_tipo_posto == 84 and 1 == 2) {
?>
			<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>
			<input type="hidden" name="btn_acao">
			<fieldset style="padding: 10;">
				<legend align="center"><font color="#000000" size="2">Loca��o</font></legend>
				<br>
				<center>
					<font color="#000000" size="2">N� de S�rie</font>
					<input class="frm" type="text" name="serie_locacao" size="15" maxlength="20" value="<? echo $serie_locacao; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o n�mero de s�rie Loca��o e clique no bot�o para efetuar a pesquisa.');">
					<img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_locacao.btn_acao.value='locacao'; document.frm_locacao.submit(); } else { alert('N�o clique no bot�o voltar do navegador, utilize somente os bot�es da tela'); }" style="cursor: hand" alt="Clique aqui p/ localizar o n�mero de s�rie">
				</center>
			</fieldset>
			</form>
<?
			}
			if ($tipo_os == "7" && strlen($os) > 0) {
				$sql =	"SELECT TO_CHAR(data_fabricacao,'DD/MM/YYYY') AS data_fabricacao ,
								pedido                                                   ,
								execucao
						FROM tbl_locacao
						WHERE serie       = '$produto_serie'
						AND   nota_fiscal = '$nota_fiscal';";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res) == 1) {
					$data_fabricacao    = trim(pg_fetch_result($res,0,data_fabricacao));
					$pedido             = trim(pg_fetch_result($res,0,pedido));
					$execucao           = trim(pg_fetch_result($res,0,execucao));
?>
				<table width="100%" border="0" cellspacing="5" cellpadding="0">
					<tr valign="top">
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Execu��o</font>
							<br>
							<input type="text" name="execucao" size="12" value="<? echo $execucao; ?>" class="frm" readonly>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Fabrica��o</font>
							<br>
							<input type="text" name="data_fabricacao" size="15" value="<? echo $data_fabricacao; ?>" class="frm" readonly>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Pedido</font>
							<br>
							<input type="text" name="pedido" size="12" value="<? echo $pedido; ?>" class="frm" readonly>
						</td>
					</tr>
				</table>
				<?
				}
			}
		}
		?>

		<!-- ------------- Formul�rio ----------------- -->

		<form enctype='multipart/form-data' id="frm_os" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os; ?>">

		<?
		if ($login_fabrica == 1 && $tipo_os == "7") {
			echo "<input type='hidden' name='locacao' value='$tipo_os'>";
		}
		?>

		<p>
		<? if ($distribuidor_digita == 't') { ?>
			<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr valign='top' style='font-size:12px'>
		<td valign='top'>
				Distribuidor pode digitar OS para seus postos.
				<br>
				Digite o c�digo do posto
				<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
				ou deixe em branco para suas pr�prias OS.
				</td>
			</tr>
			</table>
		<? } ?>

		<br>


		<table width="100%" border="0" cellspacing="5" cellpadding="2">
<?php
        if (empty($os)) {
?>
        <tr>
            <td colspan="100%" align="center">
                <input type="checkbox" name="garantia_pecas" id="garantia_pecas" value='t' <?=($garantia_pecas) ? "checked" : ""?> />
                <span style="font-family:Geneva, Arial, Helvetica, san-serif;">Devolu��o de Pe�as (90 dias de garantia)</span>
            </td>
        </tr>
        <tr>
            <td colspan="100%" align="center">
                <span style="font-family:Geneva, Arial, Helvetica, san-serif;font-size:14px;color:#F00;font-weight:bold;">
                    Selecione essa op��o apenas para realizar a devolu��o das pe�as <br />que apresentaram problema de fabrica��o dentro dos 90 dias ap�s a venda.
                </span>
            </td>
        </tr>
<?php
        } else {
?>
        <input type="hidden" name="tipo_atendimento" value="<?=$tipo_atendimento?>" />
<?php
        }
?>
		<tr>
		<? if ($pedir_sua_os == 't') { ?>
		<td nowrap  valing='top'>

				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o n�mero da OS do Fabricante.');">
				<?
				} else {
					echo "&nbsp;";
					echo "<input type='hidden' name='sua_os'>";

				?>
			</td>
			<?}?>
			<?
			if (trim (strlen ($data_abertura)) == 0 AND $login_fabrica == 7) {
				$data_abertura = $hoje;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
		<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. S�rie</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o n�mero de s�rie do aparelho.'); ">
				&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'')"  style='cursor: pointer'></A>
			</td>
			<? } ?>



			<? if ($login_fabrica == 19){ ?>
			<td nowrap align='center'  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde.Produtos</font>
				<br>
				<input class="frm" type="text" name="qtde_produtos" size="2" maxlength="3" value="<? echo $qtde_produtos ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Quantidade de produtos atendidos nesta O.S.'); ">
			</td>
			<? } ?>


<td nowrap  valign='top'>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>C�digo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif' class='label_obrigatoria'>Refer�ncia do Produto</font>";
				}
				// verifica se tem comunicado para este produto (s� entra aqui se for abrir a OS) - FN 07/12/2006
				$arquivo_comunicado="";
				if (strlen ($produto_referencia) >0) {
					$sql ="SELECT *
						FROM  tbl_comunicado JOIN tbl_produto USING(produto)
						WHERE tbl_produto.referencia = '$produto_referencia'
						AND tbl_comunicado.fabrica = $login_fabrica
						AND tbl_comunicado.ativo IS TRUE";
					$res = pg_query($con,$sql);
					if (pg_num_rows($res) > 0)
						$arquivo_comunicado= "H� ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
				}
				?>
				<br>
				<? if ($login_fabrica == 1 AND strlen($os) > 0) { ?>
				<input class=" <?php echo (in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?> " type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly>
				<? }else{ ?>
				<input type="hidden" id="leitura_comunicado">
				<input class="<?php echo (in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;'); checarComunicadoProduto();" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a refer�ncia do produto e clique na lupa para efetuar a pesquisa.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_descricao,document.frm_os.produto_referencia,'') " style='cursor: hand'>
				<? } ?>
				<img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0'
					align='absmiddle'  title="COMUNICADOS"
					onclick="javascript:abreComunicado()"
					style='cursor: pointer;'>
				<input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
			</td>
						<td nowrap  valign='top'>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif' class='label_obrigatoria'>Descri��o do Produto</font>";
				}
				?>
				<br>
				<? if ($login_fabrica == 1 && (strlen($os) > 0 || !empty($garantia_pecas))) { ?>
				<input class="<?=(in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="produto_descricao" size="30" value="<?=$produto_descricao?>" readonly>
				<? }else{ ?>
				<input class="<?=(in_array('produto', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="produto_descricao" size="30" value="<?=$produto_descricao?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto2 (document.frm_os.produto_descricao,document.frm_os.produto_referencia,'')"  style='cursor: pointer'></A>
				<? } ?>
			</td>
						<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem</font>
				<br>
				<input class="frm" type="text" name="produto_voltagem" onblur="checarComunicadoProduto();" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> >			</td>
						<td nowrap  valign='top'>
			<?
			if ($login_fabrica == 6){
				echo "				<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" color='#cc0000'>Data de entrada </font>";
			}else{
				echo "				<font size=\"1\" face=\"Geneva, Arial, Helvetica, san-serif\" class='label_obrigatoria'>Data Abertura </font>";
			} ?>

				<br>
<?
//				if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y");
?>

				<input name="data_abertura" size="12" maxlength="10" rel='data' value="<? echo $data_abertura; ?>" type="text" class="<?php echo (in_array('data_abertura', $campos_erro))? 'frm_obrigatorio' : "frm" ?>"
onfocus="this.className='frm';" onblur="this.className='frm'; displayText('&nbsp;'); checarComunicadoProduto();" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0" ><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<? if ($login_fabrica <> 6){ ?>
				<td nowrap  valign='top'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. S�rie</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="8" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o n�mero de s�rie do aparelho.'); "><br><font face='arial' size='1'><? if ($login_fabrica == 1) echo "(somente p/ linha DeWalt/Porter Cable)"; ?></font>
			</td>
			<? } ?>
		</tr>
		</table>

<? if ($login_fabrica == 1) { ?>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>C�digo Fabrica��o</font>
				<br>
				<input  name ="codigo_fabricacao" class ="<?php echo (in_array('codigo_fabricacao', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o n�mero do C�digo de Fabrica��o.');">
			</td>
			<td nowrap>
<!--
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Vers�o/Type</font>
				<br>
-->
<?
/*
				echo "<select name='type' class ='frm'>\n";
				echo "<option value=''></option>\n";
				echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo " >Tipo 1</option>\n";
				echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo " >Tipo 2</option>\n";
				echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo " >Tipo 3</option>\n";
				echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo " >Tipo 4</option>\n";
				echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo " >Tipo 5</option>\n";
				echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo " >Tipo 6</option>\n";
				echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo " >Tipo 7</option>\n";
				echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo " >Tipo 8</option>\n";
				echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo " >Tipo 9</option>\n";
				echo "<\select>&nbsp;";
*/
?>
			</td>
			<!-- <td nowrap><?// HD15589?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">90 dias Satisfa��o DeWALT/Porter Cable</font>
				<br>
				<input name ="satisfacao" class ="frm" id="satisfacao" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo t�cnico</font>
				<br>
				<input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo t�cnico.');">
			</td> -->
	</tr>
		</table>
		<? } ?>

		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr valign='top'>
		<td width='100' valign='top' nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif" class="label_obrigatoria">Nota Fiscal</font>
		<br>
		<input class="<?php echo (in_array('Nota Fiscal', $campos_erro))? 'frm_obrigatorio' : "frm-on" ?>" type="text" name="nota_fiscal"  size="10"  id="nota_fiscal" maxlength="20"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o n�mero da Nota Fiscal.');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
		</td>
		<td width='110' valign='top' nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif"  class="label_obrigatoria">Data Compra</font>
		<br>
		<input class="<?php echo (in_array('Data Compra', $campos_erro))? 'frm_obrigatorio' : "frm-on" ?>" type="text" name="data_nf"  rel='data'  size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto est� dentro do PRAZO DE GARANTIA.');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
		</td>
		<? if ($login_fabrica <> 5){ ?>
		<td valign='top' align='left'>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif" class="label_obrigatoria">Defeito Reclamado</font>
		<br>
		<input class="<?php echo (in_array('defeito_reclamado', $campos_erro))? 'frm_obrigatorio' : "frm-on" ?>" onfocus="this.className='frm-on'" type="text" name="defeito_reclamado_descricao"  size="70"  id="defeito_reclamado_descricao" maxlength="200"  value="<? echo $defeito_reclamado_descricao ?>" />

		<?php
		/*
		- HD Chamado 399969 - 30/03/2011 - Ederson Sandre
		echo "<select name='defeito_reclamado' class='frm' style='width: 300px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
		echo "<option id='opcoes' value='0'></option>";
		echo "</select>";
		*/
		echo "</td>";
		}
		?>
		<? if (($login_fabrica == 19) or ($login_fabrica == 20)) { ?>
		<td align='left'>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">
		Tipo de Atendimento</font><BR>
		<select name="tipo_atendimento" class='frm' style='width:220px;'>
		<option></option>
		<?
		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
	//	$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
		$res = pg_query ($con,$sql) ;
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				echo "<option ";
			if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) echo " selected ";
			echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'>" ;
			echo pg_fetch_result ($res,$i,tipo_atendimento) . " - " . pg_fetch_result ($res,$i,descricao) ;
			echo "</option>";
		}
		?>
		</select>
		</td>
		<? } ?>
		</tr>
		</table>


		<hr>
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">
						<!--
						<input type="hidden" name="consumidor_endereco">
						<input type="hidden" name="consumidor_numero">
						<input type="hidden" name="consumidor_complemento">
						<input type="hidden" name="consumidor_bairro">
						<input type="hidden" name="consumidor_cep">
						<input type="hidden" name="consumidor_cidade">
						<input type="hidden" name="consumidor_estado">
						-->
		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0" class="multiCep">
		<tr>
			<td colspan="2">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Nome Consumidor</font>
				<br>
				<input class="<?php echo (in_array('consumidor_nome', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_nome"   size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo Consumidor</font>
				<br>
				<select class='<?php echo (in_array('consumidor_tipo', $campos_erro))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm'"; name="fisica_juridica" id='tipo_consumidor'>
					<option></option>
					<OPTION VALUE="F" <?echo ($fisica_juridica == 'F')? " SELECTED ": "";?>>Pessoa F�sica</OPTION>
					<OPTION VALUE="J" <?echo ($fisica_juridica == 'J')? " SELECTED ": "";?>>Pessoa Jur�dica</OPTION>
				</select>
			</td>
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ do Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf" id="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e tra�os.');">
			</td>
			<td >
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" >Celular</font>
				<br>
				<input class="frm" type="text" name="consumidor_celular" rel="celular"  size="16" maxlength="20" value="<? echo $consumidor_celular ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o celular com o DDD. ex.: 14/98888-7766.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Fone</font>
				<br>
				<input class="<?php echo (in_array('telefone_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_fone" rel="fone"  size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
		</tr>
		<!-- </table> -->
		<!-- <table width='750' align='center' border='0' cellspacing='5' cellpadding='2'> -->
		<tr>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
			<br>
			<input class="frm addressZip" type="text" name="consumidor_cep"   size="12" maxlength="10" value="<? echo $consumidor_cep ?>" onblur="this.className='frm addressZip'; displayText('&nbsp;'); " onfocus="this.className='frm-on addressZip'; displayText('&nbsp;Digite o CEP do consumidor.');">
		</td>
		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif" class="label_obrigatoria">Estado</font>
			<BR>
			<select name="consumidor_estado" size="1" class="<?php echo (in_array('consumidor estado', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressState" onfocus="this.className='frm addressState' ">
				<option value="" >Selecione</option>
		        <?php
		        #O $array_estados est� no arquivo funcoes.php
		        foreach ($array_estados() as $sigla => $nome_estado) {
		            $selected = ($sigla == $consumidor_estado) ? "selected" : "";

		            echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
		        }
		        ?>
			</select>
			<!-- <input class="frm addressState" type="text" name="consumidor_estado"   size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');"> -->
		</td>
		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif" class="label_obrigatoria">Cidade</font>
			<BR>
			<select id="consumidor_cidade" name="consumidor_cidade" class="<?php echo (in_array('consumidor cidade', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressCity" onfocus="this.className='frm addressCity'"  style="width:100px">
			    <option value="" >Selecione</option>
			    <?php
			        if (strlen($consumidor_estado) > 0) {
			            $sql = "SELECT DISTINCT * FROM (
			                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
			                        UNION (
			                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
			                        )
			                    ) AS cidade
			                    ORDER BY cidade ASC";
			            $res = pg_query($con, $sql);

			            if (pg_num_rows($res) > 0) {
			                while ($result = pg_fetch_object($res)) {
			                    $selected  = (trim($result->cidade) == $consumidor_cidade) ? "SELECTED" : "";

			                    echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
			                }
			            }
			        }
			    ?>
			</select>
			<!-- <input class="frm addressCity" type="text" name="consumidor_cidade" size="12" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');"> -->
		</td>
		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Bairro</font>
			<BR>
			<input class="<?php echo (in_array('bairro_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressDistrict" type="text" name="consumidor_bairro"   size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='addressDistrict frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
		</td>
		<td align='left' nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Endere�o</font>
			<BR>
			<input class="<?php echo (in_array('endereco_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> address" type="text" name="consumidor_endereco"   size="25" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm address'; displayText('&nbsp;');" onfocus="this.className='address frm-on'; displayText('&nbsp;Digite o endere�o do consumidor.');">
		</td>

		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>N�mero</font>
			<BR>
			<input class="<?php echo (in_array('numero_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_numero"   size="5" maxlength="10" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o n�mero do endere�o do consumidor.');">
		</td>
		</tr>
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
				<BR>
				<input class="frm" type="text" name="consumidor_complemento"   size="10" maxlength="20" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere�o do consumidor.');">
			</td>
            <td valign='top' align='left' colspan="2">
                <font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Consumidor deseja receber novidades por e-mail?</font>
                <br>
                <input type="radio" name="consumidor_possui_email" id="consumidor_possui_email" value="sim" /><font size="1" face="Geneva, Arial, Helvetica, san-serif" >Sim</font>
                <input type="radio" name="consumidor_possui_email" id="consumidor_possui_email" value="nao" /><font size="1" face="Geneva, Arial, Helvetica, san-serif">N�o</font>
            </td>
            <td valign='top' align='left' colspan="2">
                <font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria consumidor_email'>Email de Contato</font>
                <br>
                <INPUT TYPE='text' name='consumidor_email' id='consumidor_email' class=' <?=(in_array('email_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?> ' onfocus="this.className='frm-on';" value="<? echo $consumidor_email; ?>" size='30' maxlength='50'>
            </td>
            <td valign='top' align='left'>
                <font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Profiss�o</font>
                <br>
                <input class="<?=(in_array('numero_consumidor', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="consumidor_profissao" id="consumidor_profissao" size="15" value="<?= $consumidor_profissao ?>" >
            </td>
		</tr>
		</table>
		<hr>

		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0" class="multiCep">
		<tr valign='top'>
			<td colspan="2">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Nome Revenda</font>
				<br>
				<input class="<?php echo (in_array('nome_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?>" type="text" name="revenda_nome"  size="30" maxlength="50" value="<? echo $revenda_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o n�mero no Cadastro Nacional de Pessoa Jur�dica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
			<br>
			<input class="frm" type="text" name="revenda_fone" rel="fone" size="15" maxlength="20" value="<? echo $revenda_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
			<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
			<br>
			<input class="frm addressZip_rev" type="text" name="revenda_cep" size="10" disable='true'  maxlength="10" value="<? echo $revenda_cep ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');">
			</td>
		</tr>
		<!-- </table>
		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0"> -->

		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Estado</font>
				<br>
				<select name="revenda_estado" id="revenda_estado" size="1" class="<?php echo (in_array('estado_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?>  addressState_rev" onfocus="this.className='frm addressState_rev'" >
					<option value="">Selecione</option>
			        <?php
			        #O $array_estados est� no arquivo funcoes.php
			        foreach ($array_estados() as $sigla => $nome_estado) {
			            $selected = ($sigla == $revenda_estado) ? "selected" : "";

			            echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
			        }
			        ?>
				</select>
				<!-- <input class="frm addressState" type="text" name="revenda_estado"  size="2"  maxlength="2" value="<? echo $revenda_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado da revenda.');"> -->
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif" class='label_obrigatoria'>Cidade</font>
				<br>
				<select id="revenda_cidade" name="revenda_cidade" class="<?php echo (in_array('cidade_revenda', $campos_erro))? 'frm_obrigatorio' : "frm" ?> addressCity_rev" style="width:100px" onfocus="this.className='frm addressCity_rev';">
				    <option value="" >Selecione</option>
				    <?php
				        if (strlen($revenda_estado) > 0) {
				            $sql = "SELECT DISTINCT * FROM (
				                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$revenda_estado."')
				                        UNION (
				                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$revenda_estado."')
				                        )
				                    ) AS cidade
				                    ORDER BY cidade ASC";
				            $res = pg_query($con, $sql);

				            if (pg_num_rows($res) > 0) {
				                while ($result = pg_fetch_object($res)) {
				                    $selected  = (trim($result->cidade) == $revenda_cidade) ? "SELECTED" : "";

				                    echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
				                }
				            }
				        }
				    ?>
				</select>
				<!-- <input class="frm addressCity" type="text" name="revenda_cidade"  size="15" maxlength="50" value="<? echo $revenda_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade da revenda.');"> -->
			</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Endere�o</font>
			<br>
			<input class="frm address_rev" type="text" name="revenda_endereco" size="30" maxlength="60" value="<? echo $revenda_endereco ?>" onblur="this.className='frm address_rev'; displayText('&nbsp;');" onfocus="this.className='frm-on address_rev'; displayText('&nbsp;Digite o endere�o da Revenda.');">
		</td>
		<td>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
			<br>
			<input class="frm addressDistrict_rev" type="text" name="revenda_bairro"  size="13" maxlength="30" value="<? echo $revenda_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro da revenda.');">
		</td>

		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">N�mero</font>
		<br>
		<input class="frm" type="text" name="revenda_numero"  size="5" maxlength="10" value="<? echo $revenda_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o n�mero do endere�o da revenda.');">
		</td>

		<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
		<br>
		<input class="frm" type="text" name="revenda_complemento" size="15" maxlength="30" value="<? echo $revenda_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere�o da revenda.');">
		</td>
		</tr>
		</table>
						<!--
						<input type='hidden' name = 'revenda_fone'>
						<input type='hidden' name = 'revenda_cep'>
						<input type='hidden' name = 'revenda_endereco'>
						<input type='hidden' name = 'revenda_numero'>
						<input type='hidden' name = 'revenda_complemento'>
						<input type='hidden' name = 'revenda_bairro'>
						<input type='hidden' name = 'revenda_cidade'>
						<input type='hidden' name = 'revenda_estado'>
						-->
						<input type='hidden' name = 'revenda_email'>
		<?
		if ($login_fabrica == 7) {
#			echo " -->";
		}
		?>

		<hr>

		<table width="750" align='center' border="0" cellspacing="5" cellpadding="0">
		<tr>
			<?
			if ($login_fabrica <> 19 and $login_fabrica <> 1) {
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo "Consumidor</font>&nbsp;";
				echo "<input type='radio' name='consumidor_revenda' value='C' " ;
				if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked";
				echo "></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>ou</font></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Revenda</font>&nbsp;";
				echo "<input type='radio' name='consumidor_revenda' value='R' ";
				if ($consumidor_revenda == 'R') echo " checked";
				echo ">&nbsp;&nbsp;</td>";
			}else{
					echo "<input type='hidden' name='consumidor_revenda' value='C'>";
			}
			?>
			<td>
				<?
				if($login_fabrica == 11){
					//NAO IMPRIME NADA
					echo "<td width='440px'>&nbsp;";
				}else{
					echo "<td>";
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif' class='label_obrigatoria'>";
					echo "Apar�ncia do Produto";
					echo "</font>";
				}
				?>

				<br>
				<? if ($login_fabrica == 20) {
					echo "<select class='frm' name='aparencia_produto' size='1'>";
					echo "<option value=''></option>";

					echo "<option value='NEW' ";
					if ($aparencia_produto == "NEW") echo " selected ";
					echo "> Bom Estado </option>";

					echo "<option value='USL' ";
					if ($aparencia_produto == "USL") echo " selected ";
					echo "> Uso intenso </option>";

					echo "<option value='USN' ";
					if ($aparencia_produto == "USN") echo " selected ";
					echo "> Uso Normal </option>";

					echo "<option value='USH' ";
					if ($aparencia_produto == "USH") echo " selected ";
					echo "> Uso Pesado </option>";

					echo "<option value='ABU' ";
					if ($aparencia_produto == "ABU") echo " selected ";
					echo "> Uso Abusivo </option>";

					echo "<option value='ORI' ";
					if ($aparencia_produto == "ORI") echo " selected ";
					echo "> Original, sem uso </option>";

					echo "<option value='PCK' ";
					if ($aparencia_produto == "PCK") echo " selected ";
					echo "> Embalagem </option>";

					echo "</select>";
				}else{
					if($login_fabrica==11){
						echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
					}else{
						$class_aparencia = (in_array('aparencia produto', $campos_erro))? 'frm_obrigatorio' : "frm-on";
						echo "<input class='$class_aparencia' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar�ncia externa do aparelho deixado no balc�o.');\">";
					}
				}
				?>

			</td>


			<td align="left">
                <?php

                $inputNotaFiscalTpl = str_replace('foto_nf', 'foto_nf[@ID@]', $inputNotaFiscal);
                echo '<div id="input_anexos" class="label_obrigatoria">';
                echo '<div>' . str_replace('@ID@', '0', $inputNotaFiscalTpl) . '</div>';
                echo '</div>';

                $anexoTpl = '<div id="anexoTpl" style="display: none">' . $inputNotaFiscalTpl . '<div>';
                echo '<input type="hidden" id="qtde_anexos" name="qtde_anexos" value="1" />';
                echo '<div style="margin-top: 5px;"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>';
                echo $anexoTpl;
                ?>
			</td>

			<td id="anexaLaudo" style="display:none;">
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Anexar Laudo T�cnico</font><br />
				<input type="file" name="foto_laudo_tecnico" id="laudo_tecnico" />
			</td>

<? if ($login_fabrica <> 1) {
	if($login_fabrica == 11){
		//nao mostra acess�rios
	}else{ ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acess�rios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess�rios deixados junto ao produto.');">
			</td>
	<?}
 } ?>

		<?
		// OR $login_fabrica == 3
		//conforme e-mail de Samuel (sirlei) a partir de 21/08 nao tem troca de produto para britania, somente ressarcimento financeiro
		if ($login_fabrica == 1 AND 1==2) {
			#Desabilitado por Fabio - A pedido da Lilian. Chamado 10511
		?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br>
				<input class="frm" type="checkbox" name="troca_faturada" onClick=" if(this.checked){document.getElementById('troca_faturada_div').style.display='block';}else{document.getElementById('troca_faturada_div').style.display='none';};" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
			</td>
		<? } ?>
		</tr>

		</table>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<div id="troca_faturada_div" style='display:<? if(strlen($troca_faturada)>0){ echo "block"; }else{ echo "none";} ?>'>
<table width='750' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class="top">
	<td  class="top"><font size='1'>Informa��es sobre a Troca Faturada</font></td>
</tr>
<tr class="top">
	<td class="txt"><font size='1'>Motivo Troca</font></td>
</tr>
<tr>
	<td class="txt1">
		<select name="motivo_troca" class='frm' size="1" style='width:550px'>
		<option value=""></option>
		<?
		$sql = "SELECT tbl_defeito_constatado.*
				FROM   tbl_defeito_constatado
				WHERE  tbl_defeito_constatado.fabrica = $login_fabrica";
		if ($consumidor_revenda == 'C' AND $login_fabrica == 1) $sql .= " AND tbl_defeito_constatado.codigo <> '1' ";
		$sql .= " ORDER BY tbl_defeito_constatado.descricao";

		$res = pg_query ($con,$sql) ;
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
			echo "<option ";
			if ($motivo_troca == pg_fetch_result ($res,$i,defeito_constatado) ) echo " selected ";
			echo " value='" . pg_fetch_result ($res,$i,defeito_constatado) . "'>" ;
			echo pg_fetch_result ($res,$i,codigo) ." - ". pg_fetch_result ($res,$i,descricao) ;
			echo "</option>";
		}
		?>
		</select>
	</td>
</tr>
</table>
</div>

<hr width='700'>
<?php
    $temImg = temNF($os, 'count');

    if($temImg) {
        echo temNF($os, 'linkEx', '', false);
    }

	$display_mobile = '';

	if ($temImg >= $limite_anexos_nf) {
		$display_mobile = '; display:none ';
	}

	// QRCode para Posto de testes. Retirar depois que validar.
?>
  <div id="env-qrcode" style="display:none">
    <div class='env-code'>
      <img style="width: 200px;" src="">
    </div>
  </div>
  <!-- <img id="btn-qrcode-request" src="imagens/btn_imageuploader.gif" onclick="getQrCode()" alt="Fazer Upload via Image Uploader" border="0" style="cursor: pointer;border: 1px solid #888;">-->
  <div id="tc_img_uploader" style="width:920px;text-align:center;margin-bottom:1em <?= $display_mobile ?>">
    <span class="mobile" id="btn-qrcode-request" onclick="getQrCode()">
    <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_mobile.png">
    <span>Anexar via Mobile</span>
    </span>
    <span class="google_play" id="btn-google-play">
      <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">
        <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_google_play.png">
        <span style="margin-top: 17px;float: left;font-size: 12px; color: #373865;">Baixar Aplicativo Image Uploader</span>
      </a>
    </span>
  </div>
  <div id="env-images"></div>
<?php
  #color: #373865
  echo $include_imgZoom;
?>
<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>

	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<input type="hidden" name="efetuar_conserto" id='efetuar_conserto' value="">
		<?

		if ($login_fabrica == 1) { ?>
			<img src='imagens/btn_continuar.gif' name="sem_submit" onclick="javascript: gravarOS()" class="verifica_servidor"  alt="Continuar com Ordem de Servi�o" border='0' style='cursor:pointer;'>
		<? }else { ?>
			<input type='checkbox' name='imprimir_os' id='imprimir_os' value='imprimir'>
				<label for='imprimir_os' style='font-size:10px;font-family:Geneva, Arial, Helvetica, sans-serif'>Imprimir OS</label>
			<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submiss�o') }" alt="Continuar com Ordem de Servi�o" border='0' style='cursor: pointer'>
		<? } ?>
	</td>
</tr>

<?php
  if($_POST['objectid'] == ""){
      $objectId = md5($login_fabrica.$login_posto.date('dmyhis').rand(1,10000));
  }else{
      $objectId = $_POST['objectid'];
  }
  ?>
  <input type="hidden" id="objectid"  name="objectid" value="<?php echo $objectId; ?>">
</form>
</table>

</div>
<?php
// QRCode para Posto de testes. Retirar depois que validar.
 ?>
<script>
  var setIntervalRunning = false;
  function getQrCode() {
    $("#btn-qrcode-request").fadeOut(1000);
    $("#btn-google-play").fadeOut(1000);
    $.ajax("controllers/QrCode.php", {
      method: "POST",
      data: {
        "ajax": "requireQrCode",
        "options": ["notafiscal"],
        "title": "Upload de Nota Fiscal",
        "objectId": $("#objectid").val()
      }
   }).done(function(response) {
      response = JSON.parse(response);
      console.log(response);

      $("#env-qrcode").find("img").attr("src",response.qrcode)
      $("#env-qrcode").fadeIn(1000);

      if (setIntervalRunning==false) {
        setIntervalHandler = setInterval(function() {
          verifyObjectId($("#objectid").val());
        },5000);
      }
   });
  }

  function verifyObjectId(objectId) {
    $.ajax("controllers/TDocs.php", {
      method: "POST",
      data:{
      "ajax": "verifyObjectId",
        "objectId": objectId,
        "context": "os"
        }
      }).done(function(response) {
        response = JSON.parse(response);

        if (response.exception == undefined) {
          $(response).each(function(idx,elem) {
            if ($("#"+elem.tdocs_id).length == 0) {
              var img = $("<div class='env-img'><a href='https://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button class='btn-danger' data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");

              $(img).find("img").attr("src","https://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg");
              $(img).find("button").click(function(){
                $.ajax("controllers/TDocs.php", {
                  method: "POST",
                  data: {
                  "ajax": "removeImage",
                    "objectId": elem.tdocs_id,
                    "context": "os"
                    }
                  }).done(function(response) {
                    response = JSON.parse(response);
                    console.log(response);
                    if (response.res == 'ok') {
                      $("#"+elem.tdocs_id).parents(".env-img").fadeOut(1000);
                      }else{
                        alert("N�o foi poss�vel excluir o anexo, por favor tente novamente");
                      }
                  });
              });

              $("#env-images").append(img);
              setupZoom();
              console.log(elem.tdocs_id);
            }
          });
        }
      });
  }

  <?php if ($filesByImageUploader > 0): ?>
	$(function() {
		getQrCode();
	});
  <?php endif ?>
</script>
<script language='javascript' src='admin/address_components.js'></script>
<? include "rodape.php";?>
