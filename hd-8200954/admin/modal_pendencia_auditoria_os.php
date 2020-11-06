<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios = "auditoria";
include_once "autentica_admin.php";
include_once __DIR__.'/funcoes.php';
include_once '../helpdesk/mlg_funciones.php';
?>


<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <style type="text/css" media="screen">

  .centro {
    text-align: center;
  }

  .div_container {
    background-color: #FAFAFA;
  }

  .par {
    background-color: #D0DFEA;
  }

  .impar {
    background-color: #F2F2F2;
  }

  .os:hover {
    background-color: #FFFFFF; 
    cursor: pointer;
  }

  .titulo {
    background-color: #7D8FBB;
    color: #ffffff;
  }

  </style>

  </head>
  <body>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  
    <script>
      $(function(){
        $('#btn_listar').click(function(){
          window.open('relatorio_auditoria_status.php?btn_listar_auditoria=Listar Todas&status_auditoria=4', '_blank');
          
        });
      });

      function abreGuia(os) {
        window.open('relatorio_auditoria_status.php?btn_acao=submit&os_pesquisa='+os+'&status_auditoria=4', '_blank');
      }

    </script>


    <?php
      $sql_qtde_os = "SELECT tbl_auditoria_os.os 
              FROM tbl_auditoria_os 
              JOIN tbl_os USING(os) 
              WHERE tbl_auditoria_os.liberada IS NULL 
              AND tbl_auditoria_os.cancelada IS NULL 
              AND tbl_auditoria_os.reprovada IS NULL
              AND tbl_auditoria_os.auditoria_status = 4
              AND UPPER (tbl_auditoria_os.observacao) ILIKE '%ACIMA DA QUANTIDADE PERMITIDA.%' 
              AND tbl_os.fabrica = $login_fabrica";
      $res_qtde_os = pg_query($con, $sql_qtde_os);      
    ?>

        <div class="container div_container">
        
        <div class='row titulo'>
            <div class="col-sm-12">
                <h5 class="centro">
                  <b>OS's em Auditoria de Peça</b>
                </h5>
            </div>
        </div>
        <br /><br />
        <div class='row centro'>
          <?php 
            $qtd = (pg_num_rows($res_qtde_os) > 5) ? 4 : pg_num_rows($res_qtde_os);
            for ($i = 0; $i < $qtd; $i++) {
              $os = pg_fetch_result($res_qtde_os, $i, 'os');
              if (($i % 2) == 0) {
                $class = 'par';
              } else {
                $class = 'impar';
              }
          ?>
              <div class="col-sm-12 <?=$class?> os">
                  <h6>
                      <b onclick="abreGuia(<?=$os?>)"><?=$os?></b>
                  </h6>
              </div>
          <?php
            }
          ?>
            
        </div>
        <br /><br />
        <div class='row centro footer'>
            <div class="col-sm-12 ">
              <button type="button" class="btn btn-primary" name="btn_listar" id="btn_listar">Listar Todas</button>
            </div>
        </div>
        
  </div>    
  </body>
</html>