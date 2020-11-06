<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$treinamento = $_GET['treinamento'];
$tecnico = $_GET['tecnico'];

$sql = "SELECT 
            tbl_resposta.txt_resposta
        FROM tbl_treinamento_posto 
        JOIN tbl_tecnico USING(tecnico)
        JOIN tbl_pesquisa ON tbl_pesquisa.treinamento = tbl_treinamento_posto.treinamento AND tbl_pesquisa.fabrica = $login_fabrica
        JOIN tbl_resposta ON tbl_resposta.pesquisa = tbl_pesquisa.pesquisa AND tbl_resposta.tecnico = tbl_tecnico.tecnico
        WHERE tbl_treinamento_posto.treinamento = $treinamento 
        AND tbl_treinamento_posto.tecnico = $tecnico 
        AND tbl_tecnico.fabrica = $login_fabrica";
$res = pg_query($con, $sql);
if (pg_num_rows($res) > 0){
    $res_treinamento_posto = pg_fetch_result($res, 0, 'txt_resposta');
    $result = json_decode($res_treinamento_posto, true);
}
?>

<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/bootstrap.css" />
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/extra.css" />
<link media="screen" type="text/css" rel="stylesheet" href="css/tc_css.css" />
<link media="screen" type="text/css" rel="stylesheet" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link media="screen" type="text/css" rel="stylesheet" href="bootstrap/css/ajuste.css" />

<link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">

<body>
    <input type="hidden" name="treinamento" id="treinamento" value="<?=$res_treinamento['treinamento']?>">
    <input type="hidden" name="tecnico" id="tecnico" value="<?=$res_treinamento_posto['tecnico']?>">
    <input type="hidden" name="pesquisa" id="pesquisa" value="<?=$res_pesquisa['pesquisa']?>">

    <div class="container-fluid">
        <?php
        if (pg_num_rows($res) > 0){
            foreach ($result as $key => $value) {
            ?>
                <div class="row-fluid env-item">
                    <div class="span12">
                        <h4 class="main_title"><?=utf8_decode($value['main_title'])?></h4>
                        <div class="itens">
                            <div class="item-header">
                        <?php
                        if($value['itens'][0]['ask'] != "open_text_area"){
                        ?>
                                <div class="span6">
                                <?php
                                if($value['question'] != ""){
                                ?>
                                    <h5 class="question"><?=utf8_decode($value['question'])?></h5>
                                <?php
                                }
                                ?>
                            </div>
                            <div class="span6 tac">
                                <div class="btn-group table-legenda">
                                    <button class="btn-peso-1 btn " disabled=""><i class="fa fa-frown"></i><br>1</button>
                                    <button class="btn-peso-2 btn no-icon" disabled="">2</button>
                                    <button class="btn-peso-3 btn " disabled=""><i class="fa fa-meh"></i><br>3</button>
                                    <button class="btn-peso-4 btn no-icon" disabled="">4</button>
                                    <button class="btn-peso-5 btn " disabled=""><i class="fa fa-smile"></i><br>5</button>
                                </div>
                            </div>
                          <?php
                        }
                        ?>
                    </div>

                    <?php
                        foreach ($value['itens'] as $item => $value_item) {
                            if($value_item['ask'] == "open_text_area"){
                    ?>
                                <div class="item">
                                    <div class="span12">
                                        <textarea  class="text-area" placeholder="Seu texto" disabled="true" style="width: 100%" rows="3">
                                            <?=utf8_decode($value_item['val'])?>
                                        </textarea>
                                    </div>
                                </div>
                          <?php
                        }else{

                                unset($class_rim);
                                unset($class_medio2);
                                unset($class_medio3);
                                unset($class_medio4);
                                unset($class_bom);

                                if ($value_item["val"] == 1){
                                    $class_rim = "style='background: #ff0000;'";
                                }else if ($value_item["val"] == 2){
                                    $class_medio2 = "style='background: #ffff99;'";
                                }else if ($value_item["val"] == 3){
                                    $class_medio3 = "style='background: #ffff66;'";
                                }else if ($value_item["val"] == 4){
                                    $class_medio4 = "style='background: #ffcc00;'";
                                }else{
                                    $class_bom = "style='background: #009933;'";
                                }

                    ?>
                                <div class="item">
                                    <div class="span6">
                                        <p class="item_question"><?=utf8_decode($value_item['ask'])?></p>
                                    </div>
                                    <div class="span6">
                                        <div class="answer tac">
                                            <div class="btn-group">
                                                <button class="btn btn-note" disabled="true" data-peso="1" <?=$class_rim?> >&nbsp</button>
                                                <button class="btn btn-note" disabled="true" data-peso="2" <?=$class_medio2?> >&nbsp</button>
                                                <button class="btn btn-note" disabled="true" data-peso="3" <?=$class_medio3?> >&nbsp</button>
                                                <button class="btn btn-note" disabled="true" data-peso="4" <?=$class_medio4?> >&nbsp</button>
                                                <button class="btn btn-note" disabled="true" data-peso="5" <?=$class_bom?> >&nbsp</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                          <?php
                            }
                        }
                    ?>
                    </div>
                </div>
            </div>
              <?php
            }
        }else{
            echo "
                <div class='alert alert-danger'>
                    <h4>Nenhum resultado encontrado</h4>
                </div>
            ";

        }
        ?>
        <?php
      ?>
    </div>
</div>

<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<style type="text/css">
  .env-certificate{
    display: none;
  }

  .env-img > img{
    display: block;
    margin: 0 auto;
    max-height: 235px;
  }

  .env-img{
    width: 50%;
    display: block;
    float: left;
    text-align: center;
  }

  .item-error{
        background: #f1d8d8 !important;
  }

  .main_title{
    background: #e2e2e2;
    padding: 8px

  }

  .btn-note:hover{
    background: #e2e2e2
  }

  .btn-note-choose{
    background: #b4daf3 !important;
  }

  .question{
    border-bottom: 1px solid #e2e2e2;
  }

  .itens{
    width: 100%;
    float: left;
  }

  .item{
    float: left;
    width: 100%;
    padding: 5px 0 5px 0;
    border-bottom: 1px solid #f5f3f3;
  }

  .item-header{
    float: left;
    width: 100%;
    padding: 5px 0 5px 0;
    border-bottom: 1px solid #f5f3f3;
  }

  .item > .span6:first-child > p{
    margin-top: 10px
  }

  .pagination {
     margin: 0px;
     text-align: center;
  }

  .table-legenda > button{
    height: 42px;
  }

  .table-legenda > .no-icon{
    padding-top: 19px
  }

  .answer > .btn-group > button{
    width: 38px;
    height: 38px;
  }

  .btn-peso-hover{
      background: #333 !important;
      color: #fff !important;
  }
</style>

<?php
// foreach ($result as $key => $value) {
//     echo "<pre>";
//     print_r($value);
//     echo "--------------------------<br/>";
//}

?>