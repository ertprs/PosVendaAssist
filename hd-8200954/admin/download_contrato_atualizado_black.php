<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";

	$posto = $_GET["posto_id"];
    $categoria = trim($_GET['categoria']);

    $sql_posto = " SELECT tbl_posto.posto, 
                    tbl_posto_fabrica.codigo_posto, 
                    tbl_posto.cnpj, 
                    tbl_posto.nome, 
                    tbl_posto_fabrica.categoria, 
                    tbl_posto_fabrica.contato_endereco, 
                    tbl_posto_fabrica.contato_numero, 
                    tbl_posto_fabrica.contato_complemento, 
                    tbl_posto_fabrica.contato_bairro, 
                    tbl_posto_fabrica.contato_cidade, 
                    tbl_posto_fabrica.contato_estado, 
                    tbl_posto_fabrica.contato_cep 
                    FROM tbl_posto_fabrica 
                    join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                    where tbl_posto.posto = $posto 
                    and tbl_posto_fabrica.fabrica = $login_fabrica";
    $res_posto = pg_query($con, $sql_posto);
    //echo nl2br($sql_posto);
    if(pg_num_rows($res_posto)>0){
        $login_posto = pg_fetch_result($res_posto, 0, 'posto');
        $posto_codigo = pg_fetch_result($res_posto, 0, 'codigo_posto');
        $posto_nome = pg_fetch_result($res_posto, 0, 'nome');
        $posto_cnpj  = pg_fetch_result($res_posto, 0, 'cnpj');
        if(strlen($categoria)>0){
            $posto_categoria = $categoria;
        }else{
            $posto_categoria   = pg_fetch_result($res_posto, 0, 'categoria');    
        }
        $contato_endereco  = pg_fetch_result($res_posto, 0, 'contato_endereco');
        $contato_numero     = pg_fetch_result($res_posto, 0, 'contato_numero');
        $contato_complemento = pg_fetch_result($res_posto, 0, 'contato_complemento');
        $contato_bairro = pg_fetch_result($res_posto, 0, 'contato_bairro');
        $contato_cidade = pg_fetch_result($res_posto, 0, 'contato_cidade');
        $contato_estado = pg_fetch_result($res_posto, 0, 'contato_estado');
        $contato_cep = pg_fetch_result($res_posto, 0, 'contato_cep');

        $posto_endereco_completo = "$contato_endereco, $contato_numero $contato_complemento $contato_cidade-$contato_estado $contato_bairro $contato_cep";
    }

    include_once "../gera_contrato_posto.php";
    include_once "../classes/mpdf61/mpdf.php";    

    $arquivo = "xls/contrato_servico_".str_replace(" ","_",$posto_categoria)."_{$login_posto}.pdf"; 

    $posto_nome = ($login_posto == 139472) ? $posto_nome ." - EM RECUPERA&Ccedil;&Atilde;O JUDICIAL" : $posto_nome;

    $conteudo_cabecalho = gerarCabecalho();
    $conteudo_rodape = gerarRodape($posto_nome,$posto_categoria);
    $conteudo = gerarCorpo($posto_codigo,$posto_nome,$posto_categoria,$posto_endereco_completo,$posto_cnpj,$con,$login_posto,$login_fabrica);

    //echo $conteudo_cabecalho. $conteudo. $conteudo_rodape;

    $mpdf = new mPDF("", "A4", "", "", "15", "15", "32", "22");
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->forcePortraitHeaders = true;
    $mpdf->charset_in = 'windows-1252';
    $mpdf->SetHTMLHeader($conteudo_cabecalho);
    $mpdf->SetHTMLFooter(utf8_encode($conteudo_rodape));
    $mpdf->WriteHTML($conteudo);
    $mpdf->Output($arquivo, "d");

?>
