<?php 

$sql_dados_posto = "SELECT 
                                tbl_posto_fabrica.codigo_posto AS posto_codigo,
                                tbl_posto.nome AS posto_nome,
                                tbl_posto.cnpj AS posto_cnpj,
                                tbl_posto_fabrica.contato_endereco AS posto_endereco,
                                tbl_posto_fabrica.contato_numero AS posto_numero,
                                tbl_posto.contato,
                                tbl_posto_fabrica.contato_cep AS posto_cep,
                                tbl_posto_fabrica.contato_bairro AS posto_bairro,
                                tbl_posto_fabrica.contato_cidade AS posto_cidade,
                                tbl_posto_fabrica.contato_estado AS posto_estado 
                            FROM tbl_posto 
                            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_posto.posto 
                            WHERE 
                                tbl_posto.posto = {$posto}";
        $res_dados_posto = pg_query($con, $sql_dados_posto);
        
        $posto_codigo   = pg_fetch_result($res_dados_posto, 0, "posto_codigo");
        $posto_nome     = pg_fetch_result($res_dados_posto, 0, "posto_nome");
        $contato     = pg_fetch_result($res_dados_posto, 0, "contato");
        $posto_cnpj     = pg_fetch_result($res_dados_posto, 0, "posto_cnpj");
        $posto_endereco = pg_fetch_result($res_dados_posto, 0, "posto_endereco");
        $posto_numero   = pg_fetch_result($res_dados_posto, 0, "posto_numero");
        $posto_cep      = pg_fetch_result($res_dados_posto, 0, "posto_cep");
        $posto_bairro   = pg_fetch_result($res_dados_posto, 0, "posto_bairro");
        $posto_cidade   = pg_fetch_result($res_dados_posto, 0, "posto_cidade");
        $posto_estado   = pg_fetch_result($res_dados_posto, 0, "posto_estado");

        $posto_endereco_completo = "";

        if(strlen($posto_endereco) > 0){
            $posto_endereco_completo .= $posto_endereco;
        }

        if(strlen($posto_numero) > 0){
            $posto_endereco_completo .= ", ".$posto_numero;
        }

        if(strlen($posto_cep) > 0){
            $posto_endereco_completo .= ", ".$posto_cep;
        }

        if(strlen($posto_bairro) > 0){
            $posto_endereco_completo .= ", ".$posto_bairro;
        }

        if(strlen($posto_cidade) > 0){
            $posto_endereco_completo .= ", ".$posto_cidade;
        }

        if(strlen($posto_estado) > 0){
            $posto_endereco_completo .= ", ".$posto_estado;
        }

        $sql_motivo = "SELECT tbl_credenciamento.texto 
                       FROM tbl_credenciamento 
                       JOIN tbl_posto ON tbl_credenciamento.posto = tbl_posto.posto 
                       JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
                       WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
                       AND tbl_posto.posto = $posto 
					   and tbl_credenciamento.fabrica = $login_fabrica
                       AND tbl_credenciamento.status ILIKE '%descredenciamento em aprova%' 
                       ORDER BY tbl_credenciamento.data DESC LIMIT 1";
        $res_motivo = pg_query($con,$sql_motivo);
        $motivo = pg_fetch_result($res_motivo, 0, texto);
        if (mb_check_encoding($motivo, 'UTF-8')) {
            $motivo = utf8_decode($motivo);      
        }

$conteudo_cabecalho = "
    <div class='cabecalho-contrato'>
        <div class='logo-contrato'>
            <img src='logos/logo_black_2016.png' style='width: 65%; padding-top: 4px;' />
        </div>
        <div class='info-contrato' style='font-family: arial, verdana; font-size: 8x !important;'>
            Black & Decker do Brasil Ltda. <br />
            Rod. BR 050 - Km 167 - Lote 05 Parte - Quadra 01 <br />
            Distrito Industrial II <br />
            38.064-750 - Uberaba - MG <br />
            Fone: 55 34 3318-3922 <br />
            Fax: 55 34 3318-3018 <br />
            53.296.273/001-91
        </div>
    </div>
";
/* hd-6070407
$conteudo_rodape = "
    <div style='text-align: center; font-family: arial, verdana; font-size: 8px !important;'>
        INSTRUMENTO PARTICULAR DE ACORDO DE PRESTAÇÃO DE SERVIÇOS CELEBRADO ENTRE BLACK & DECKER DO BRASIL LTDA., E <strong>{$posto_nome}</strong>. 
        <br />
        <strong> Página {PAGENO} de 3 </strong>
    </div>
";*/

$conteudo = "
        <!DOCTYPE html>
        <html>

            <head>

                <style>
                    .corpo-contrato{
                        font-family: arial, verdana;
                        font-size: 9px !important;
                        color: #444;
                        width: 100%;
                        background-color: #fff;
                        text-align: justify;
                    }
                    .obs_vermelho{
                        color:#ff0000;
                        text-decoration: underline;
                    }

                    .cabecalho-contrato, .rodape-contrato{
                        width: 100%;
                    }
                    .logo-contrato, .info-contrato, .assinaturas, .testemunhas{
                        width: 45%;
                        float: left;
                        padding-top: 10px;
                        padding-bottom: 10px;
                    }
                    .logo-contrato, assinaturas{
                        padding-right: 15px;
                    }
                    .info-contrato, .testemunhas{
                        padding-left: 30px;
                    }
                    .logo-contrato{
                        text-align: center;
                    }
                    .logo-contrato img{
                        width: 350px;
                    }
                    .info-contrato{
                        border-left: 1px solid #e74c3c;
                        padding-left: 20px;
                    }
                    .conteudo-contrato h3{
                        text-align: center;
                        font-size: 12px !important;
                    }
                    .conteudo-contrato strong{
                        text-transform: uppercase;
                    }
                    .escpaco-clausula{
                        padding-left: 30px;
                    }
                    .escpaco-clausula-2{
                        padding-left: 60px;
                    }
                    .text-upper{
                        text-transform: uppercase;
                    }

                    .posto_assinatura_posto{
                        margin-top:20px;
                        width:180px;
                        float:right;
                        text-align:center;
                        padding:20px;
                        border:1px #cccccc solid
                    }

                    .posto_assinatura_fabrica{
                        margin-top:20px;
                        width:180px;
                        float:left;
                        text-align:center;
                        padding:20px;
                        border:1px #cccccc solid
                    }
                    
                </style>
 
            </head>

            <body>
                
                <div class='corpo-contrato'>
                    <div class='conteudo-contrato'>

                        <div class='dados_posto'>
                            <b>$posto_nome </b>
                            <br><br>
                            <b>CÓDIGO: $codigo_posto </b> <br>
                            <b>$posto_cnpj </b> <Br>
                            <b>$posto_endereco $posto_numero </b><Br>
                            <b>$posto_cidade/$posto_estado </b><br>
                            <b>CEP:$posto_cep </b>

                        </div>

                        <h3>Cancelamento de Posto de Serviço</h3>

                        <strong> Prezado Senhor $contato </strong>
   
                        <p>
                            Conforme entendimentos verbais, vimos com o presente confirmar o cancelamento da autorização de posto de serviços <b>$posto_nome</b> pelo motivo: <span class='obs_vermelho'> $motivo </span>
                        </p>
                        <p>
                            Ficam, portanto, definidas as prerrogativas de posto em descredenciamento, quais sejam:
                        </p>

                        <p class='escpaco-clausula'> 
                            1. Não usar o nome, o monograma e marcas Stanley Black & Decker em seus documentos e veículos publicitários;
                        </p>
                        <p class='escpaco-clausula'>
                            2. Dar assistência técnica aos aparelhos de fabricação Stanley Black & Decker, durante trinta (30) dias a contar do recebimento desta. Dentro deste prazo, V.S.ª deverão liquidar suas pendências com os nossos clientes, revendedores e com a Stanley Black & Decker; após este prazo, é proibido o recebimento de produtos Stanley Black & Decker.
                        </p>
                        <p class='escpaco-clausula'>
                            3. Completar reparo em aparelhos já em seu poder, devolvendo-os aos seus respectivos clientes ou revendedores;    
                        </p>
                        <p class='escpaco-clausula'>
                            4. Eliminar documentos, placas, veículos, nome ou monogramas da Stanley Black & Decker.
                        </p>

                        <p>
                            Sem mais para o momento, aproveitamos o ensejo para agradecer os relevantes serviços profissionais prestados por V.S.ª e enviar lhe nossas cordiais saudações. 
Atenciosamente,
                        </p>

                        <div class='rodape'>
                        <div class='posto_assinatura_fabrica'>";
                        
                        if($assinado) {
                            $conteudo .= "<img src='../image/contratos/ass_black.jpg' width='180px' alt='Ass'> <br>";
                        }else{
                            $conteudo .= "<br><br><br>";
                        }
                        
                        $conteudo .= "

                                    _____________________________<BR>
                                    <strong>BLACK & DECKER DO BRASIL LTDA.</strong> <br />
                            SILVANIA SILVA - GERENTE DE PRODUTOS E SERVIÇOS <br />

                            </div>
                            <div class='posto_assinatura_posto'>
                                    _____________________________<BR>
                                    POSTO AUTORIZADO<br>
                                    <b>$posto_nome</b>

                            </div>
                        </div>
                    </div>
                </div>
            </body>
        </html>";

//echo $conteudo ; 

    include "../classes/mpdf61/mpdf.php";
    
    $arquivo = "/tmp/contrato_cancelamento_servico_{$posto}.pdf";
    
    $mpdf = new mPDF("", "A4", "", "", "15", "15", "32", "22"); 
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->forcePortraitHeaders = true;
    $mpdf->charset_in = 'windows-1252';
    $mpdf->SetHTMLHeader($conteudo_cabecalho);
    //$mpdf->SetHTMLFooter(utf8_encode($conteudo_rodape));
    $mpdf->WriteHTML($conteudo);
    $mpdf->Output($arquivo, "F");   

?>

