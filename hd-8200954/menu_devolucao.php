<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';

if ($usaPreOS OR $fabrica_pre_os) {

  $fabrica_pre_os = $login_fabrica;
}

if ($login_fabrica == 24) {

    $sql = "SELECT atualizacao FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res)>0) {
        $atualizacao = pg_result($res,0,0);
    }

    $sql = "SELECT CASE WHEN '$atualizacao' <= '2010-06-09 09:36:39.548903' THEN 'sim' ELSE 'NAO' END";

    $res = pg_query($con,$sql);

    if(pg_num_rows($res)>0) {
        $resposta = pg_result($res,0,0);
    }

    if ($resposta == 'sim') {
        header('Location:posto_cadastro.php');
    }

    $cond_data = " AND tbl_os.data_abertura >= '2013-09-30' AND data_digitacao > '2013-09-30 00:00:00' ";

}


$menu_os[] = array (
    'disabled'  => $novaTelaOs,
    'icone'     => 'marca25.gif',
    'link'      => 'devolucao_cadastro.php',
    'titulo'    => traduz('cadastro.de.devolucao'),
    'descr'     => traduz('cadastrar.uma.nova.devolucao')
);

$menu_os[] = array (
    'disabled'  => $novaTelaOs,
    'icone'     => 'marca25.gif',
    'link'      => 'consulta_devolucoes.php',
    'titulo'    => traduz('consulta.de.devolucoes'),
    'descr'     => traduz('consultar.as.devolucoes.cadastradas')
);

$title = traduz('menu.de.ordens.de.servico', $con);

include 'cabecalho.php';


if ($login_unico)
    menu_item(array(
                'link'     => '#',
                'titulo'   => mb_strtoupper($title),
                'noexpand' => true
              ), null,
              'secao_admin');
menuTC($menu_os,null,'#fffafa','#fff5f5');
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
include "rodape.php";

