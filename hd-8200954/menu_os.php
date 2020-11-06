<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'funcoes.php';

if ($usaPreOS OR $fabrica_pre_os) {
  $fabrica_pre_os = $login_fabrica;
}

if (isFabrica(24)) {
    if ($login_posto_interno == "t") {
        header('Location: menu_devolucao.php');
    }

    $cond_data = " AND tbl_os.data_abertura >= '2013-09-30' AND data_digitacao > '2013-09-30 00:00:00' ";
}

$digita_os = true;

if(isFabrica(87,154)) {
    if (isFabrica(87)) {
        if ($login_posto_digita_os == 'f' ) {
            $desabilita_tela = traduz('sem.permissao.de.acesso');
        }
    }else{
        $digita_os = $login_posto_digita_os;
    }
}

//Módulo de Lote de Revenda
$sql = "SELECT * FROM tbl_revenda_posto WHERE posto = $login_posto AND fabrica = $login_fabrica AND ativo IS TRUE";
$res = pg_query($con,$sql);
$usa_lote_revenda = (pg_num_rows($res)>0);

$tipo_posto = $login_tipo_posto;

if (in_array($tipo_posto, array(36, 82, 83, 84))) {
    header("Location: login.php");
    exit;
}

if (isFabrica(151, 156)) {
    if ($TipoPosto->tipo_revenda or $TipoPosto->posto_interno) {
        if (isFabrica(156)) {
            $mostra_revenda = $login_fabrica;
        }
    }else{
        $mostra_revenda = $login_fabrica;
    }

}
/* a linha de informatica na britania habilita mais 2 opções de menu de os */
$sqllinha = "SELECT tbl_linha.informatica
               FROM tbl_posto_linha
               JOIN tbl_linha USING (linha)
              WHERE tbl_posto_linha.posto = $login_posto
                AND tbl_linha.informatica = 't'
                AND tbl_linha.fabrica     = $login_fabrica";
$reslinha = pg_query($con,$sqllinha);

if (pg_num_rows($reslinha) > 0) {
    $linhainf = trim(pg_fetch_result($reslinha,0,informatica)); //linha informatica para britania
}

// Fabrica consulta PreOS
$fabrica_usa_preOS = ($linhainf=='t' or
                    in_array($login_fabrica,array(2,11,24,40,50,59,72,74,81,85,89,91,90,96,99,114,134,136,132,138,139,172,$fabrica_pre_os)));
if (isset($usaPreOs)) {
    $fabrica_usa_preOS = true;
}

/*****************************************************
 * Define os postos que podem fazer Upload de OS     *
 * Próximamente será via web service, e não há novos *
 * postos que possam usar o serviço, que não é mais  *
 * oferecido.                                        *
 *                                                   *
 * Para melhorar a portabilidade da configuração...  *
 * O array $postosFazemUploadOS está definido (fixo) *
 * no autentica_usuario.php                          *
 *****************************************************/
if (isset($postosFazemUploadOS[$login_fabrica])) {
    $UploadOSHabilitado = in_array($login_posto, $postosFazemUploadOS[$login_fabrica]);
}else{
    $UploadOSHabilitado = false;
}

//takashi 12/12 email samuel
if($login_fabrica==1) {
    $sql = " SELECT linha
            FROM tbl_posto_linha
            WHERE posto = $login_posto
            AND   linha = 494 ";
    $res = @pg_query($con,$sql);
    if(@pg_num_rows($res) > 0) {
        $linha_metais = pg_fetch_result($res,0,linha);
    }
}

$extrato_fechamento = array(20);

$sql = "SELECT posto
          FROM tbl_tipo_gera_extrato
          JOIN tbl_intervalo_extrato USING(intervalo_extrato, fabrica)
         WHERE posto   = $login_posto
           AND fabrica = $login_fabrica
           AND automatico";

$res = pg_query($con,$sql);
if (pg_num_rows($res)) {
    $extrato_fechamento[] = 1;
}

if (in_array($login_fabrica, array(163))) {
    $res = pg_query($con, $sql);
    for ($i=0; $i < pg_num_rows($res); $i++) {
        if ($login_posto == pg_fetch_result($res, $i, 'posto')) {
            array_push($array_menus_os, 30);
            $bloqCadastroOs = 30;
            break;
        }
    }
}

if (isFabrica(35, 163)) {
    if (!$TipoPosto->tipo_revenda and !$TipoPosto->posto_interno and $TipoPosto->ativo) {
        $os_fechamento[] = $login_fabrica;
    }
}

ob_start();
?>
<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<script type="text/javascript">
    $(function() {
        $("#inspecao").click(function(){
        if($(".inspecao").is(":visible")){
            $(".inspecao").hide();
        }else{
            $(".inspecao").show();
        }
    });
    });

</script>
<?php
$headerHTML = ob_get_clean();

$layout_menu ='os';
$title  = traduz('menu.de.ordens.de.servico');

include 'cabecalho.php';
include_once 'regras/menu_posto/oss_em_atraso.php';
echo $cabecalho->menu(include(MENU_DIR . 'menu_os.php'));

include "rodape.php";

