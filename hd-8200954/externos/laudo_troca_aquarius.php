<?php
    header('Content-type: text/html; charset=UTF-8');
    include __DIR__.'/../dbconfig.php';
    include __DIR__.'/../includes/dbconnect-inc.php';
    include __DIR__.'/../includes/funcoes.php';

    if (!isset($_GET['hd_chamado']) || $_GET['hd_chamado'] == "" || !strlen($_GET['hd_chamado']) > 0)
    {
        $msg_erro = "Atendimento inválido";
    }

$title  = "Autorização de Troca de Produto";

// validação do atendimento
    $hd_chamado = addslashes($_GET['hd_chamado']);
    $sql = "
        SELECT 
            hce.nome, hce.cpf, p.referencia, p.descricao,  hce.nota_fiscal, hce.revenda_nome, TO_CHAR(hc.data_aprovacao, 'DD/MM/YYYY') AS data_aprovacao  
        FROM tbl_hd_chamado hc 
        INNER JOIN tbl_hd_chamado_extra hce ON hce.hd_chamado = hc.hd_chamado 
        INNER JOIN tbl_produto p ON p.produto = hce.produto AND p.fabrica_i = 174 
        WHERE hc.fabrica = 174 AND hc.fabrica_responsavel = 174 AND hc.hd_chamado = {$hd_chamado} AND hc.data_aprovacao IS NOT NULL"; 
    $query     = pg_query($con,$sql);
    $nums      = pg_num_rows($query);
    
    if ($nums > 0)
    {
        $retorno        = pg_fetch_object($query);
        $nome           = $retorno->nome;
        $cpf            = $retorno->cpf;
        $p_refer        = $retorno->referencia;
        $p_desc         = $retorno->descricao;
        $produto        = $p_refer.' - '.$p_desc;
        $nota_fiscal    = $retorno->nota_fiscal;
        $revenda_nome   = $retorno->revenda_nome;
        $data_aprovacao = $retorno->data_aprovacao;
        $nova_data      = explode("/", $data_aprovacao);
        switch ($nova_data[1]){
            case 1: $nova_data[1] = "Janeiro"; break;
            case 2: $nova_data[1] = "Fevereiro"; break;
            case 3: $nova_data[1] = "Março"; break;
            case 4: $nova_data[1] = "Abril"; break;
            case 5: $nova_data[1] = "Maio"; break;
            case 6: $nova_data[1] = "Junho"; break;
            case 7: $nova_data[1] = "Julho"; break;
            case 8: $nova_data[1] = "Agosto"; break;
            case 9: $nova_data[1] = "Setembro"; break;
            case 10: $nova_data[1] = "Outubro"; break;
            case 11: $nova_data[1] = "Novembro"; break;
            case 12: $nova_data[1] = "Dezembro"; break;
        }
        
    }else
    {
        $msg_erro = "Atendimento inválido";
    }
    
    // conteudo
?>
    <link href="https://fonts.googleapis.com/css?family=Montserrat" rel="stylesheet">
    <style type="text/css">
        body{
            font-family: 'Montserrat', sans-serif;
        }
        .underline{ text-decoration: underline; }
        #meio{
            height: 50%;
            border: 1px solid black;
            padding-top: 130px;
        }
        #meio p{
          font-size: 19px;
          padding-right: 5px;
          padding-left: 5px;
        }
        #meio p#data{
            text-align: left !important;
            padding-left: 66px;
        }
        p#content{ padding-top: 66px; }
        #meio #data{ padding-top: 100px; }
        #logo{
            padding-top: 20px;
            padding-bottom: 111px; 
        }
        #rodape{ padding-top: 88px; }
        @media print{
           button { display: none; }
        }
        @media screen{
           button { display: block; }
        }
    </style>
    <div id="conteudo">
        <div id="logo" align="center">
           <a href="http://www.aquariusbrasil.com" rel="nozoom"><img src="../logos/logo_aquarius.png" alt="http:/www.aquariusbrasil.com" border="0" width="350px;"></a>   
        </div>
        <div id="meio" align="center">
            <?php 
                if ($msg_erro != "")
                {
                    echo '<h1><center>'.$msg_erro.'</center></h1>';
                }else
                {
            ?>
                <h1><center>Autorização de Troca</center></h1>
                <p id='content'>
                    <label>
                        <strong>Aquarius Brasil</strong>, autoriza o(a) Sr(a).
                        <span class='underline'> &nbsp;&nbsp;<?= $nome ?>&nbsp;&nbsp; </span>,
                        inscrito no CPF<span class='underline'> &nbsp;&nbsp;<?= $cpf ?>&nbsp;&nbsp; </span>, 
                        a efetuar a troca do produto MTC<span class='underline'> &nbsp;&nbsp;<?= $produto ?>&nbsp;&nbsp;  </span>,
                        referente a nota fiscal<span class='underline'> &nbsp;&nbsp;<?= $nota_fiscal ?>&nbsp;&nbsp; </span>
                        &nbsp;junto ao lojista<span class='underline'> &nbsp;&nbsp; <?= $revenda_nome ?>&nbsp;&nbsp; </span>.
                    </label>
                </p>
                <br /><br />
                <p id='data'>
                    Rio de Janeiro, <span class='underline'>&nbsp;&nbsp;<?= $nova_data[0]; ?>&nbsp;&nbsp;</span> de <span class='underline'>&nbsp;&nbsp;<?= $nova_data[1]; ?>&nbsp;&nbsp;</span> de <span class='underline'>&nbsp;&nbsp;<?= $nova_data[2]; ?>&nbsp;&nbsp;</span>
                </p>
            <?php 
                }
            ?>
        </div>
        <div id="rodape" align="center">
            <footer>
                <p><strong>Aquarius Brasil</strong> - <a href="http://www.aquariusbrasil.com">www.aquariusbrasil.com</a> - Aquarius Brasil Indústria e Comércio Ltda.</p>
                <p>Rua José Augusto Rodrigues, 174 - CEP: 22.775.047 - Jacarepaguá - RJ - Tel: 55 21 3539-9339</p>
            </footer>
        </div>
        <center><button type='button' id='imprimir'>Imprimir</button></center>
        <script src="../js/jquery-1.8.3.min.js"></script>
        <script language="JavaScript">
          $('#imprimir').click(function(){   
                window.print();
            });
        </script>
    </div>