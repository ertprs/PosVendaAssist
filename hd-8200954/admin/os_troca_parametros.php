<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,auditoria";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$layout_menu = "auditoria";
$title = "RELAÇÃO DE ORDENS DE SERVIÇOS DE TROCA ";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");

?>


<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
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

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>





<script type="text/javascript" charset="utf-8">
	$(function(){
		$.datepickerLoad(Array("data_final", "data_inicial"));


		Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });
		/*$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");*/
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function retorna_produto (retorno) {
        $("#produto_id").val(retorno.produto);
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);
        if (typeof retorno.serie_produto != "undefined") {
            $("#produto_serie").val(retorno.serie_produto);
        }
    }
</script>

<br>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="os_troca_consulta.php" class='form-search form-inline tc_formulario' >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data_final", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?= $produto_referencia ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                            <input type="hidden" name="produto" id="produto_id" value="<?=$produto?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?= $produto_descricao; ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
        	<div class='control-group <?=(in_array("tipo_atendimento", $msg_erro["campos"])) ? "error" : ""?>'>
                <div class='controls controls-row'>
                	<div class='row-fluid'>
                		<!-- <div class='span1'>
                			<INPUT TYPE="checkbox" NAME="chk_opt3" value="1"></div>
                		<div class='span2'>
                			
                		</div> -->
                		<div class='span6'>
                            <label class='control-label' for='codigo_posto'>Tipo Atendimento</label>
	                        <select name="tipo_atendimento">
								<option></option>
								<?
								$sql = "SELECT *
										FROM tbl_tipo_atendimento
										WHERE fabrica = $login_fabrica
										AND   tipo_atendimento IN (17,18,35)
										ORDER BY tipo_atendimento";

								$res = pg_query ($con,$sql) ;
								for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

									echo "<option ";
									if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) echo " selected ";
									echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'>" ;
									echo pg_fetch_result ($res,$i,descricao) ;
									echo "</option>";
								}
								?>
							</select>
	                    </div>
                        <div class='span6'>
                            <label class='control-label' for='codigo_posto'>Autorização</label>
                            <select name="admin_autoriza" >
                            <option selected></option>
                            <?
                            $sql = "SELECT admin,nome_completo
                                    FROM tbl_admin
                                    WHERE fabrica = $login_fabrica
                                    AND admin in(112,257,626)
                                    ORDER BY nome_completo";
                            $res = pg_query ($con,$sql) ;
                            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                                echo "<option ";
                                if ($admin_autoriza == pg_fetch_result ($res,$i,admin) ) echo " selected ";
                                echo " value='" . pg_fetch_result ($res,$i,admin) . "'>" ;
                                echo pg_fetch_result ($res,$i,nome_completo) ;
                                echo "</option>";
                            }
                            ?>
                            </select>
                        </div>
                	</div>                    
                </div>
            </div>        	
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
        	<div class='control-group <?=(in_array("autorizacao", $msg_erro["campos"])) ? "error" : ""?>'>
                <div class='controls controls-row'>
                	<div class='row-fluid'>
                		<!-- <div class='span1'>
                			<INPUT TYPE="checkbox" NAME="chk_opt5" value="1">
                		</div>
                		<div class='span2'>
                			<label class='control-label' for='codigo_posto'>Motivo da Troca</label>
                		</div> -->
                		<div class='span6'>
                            <label class='control-label' for='codigo_posto'>Motivo da Troca</label>
	                        <select name="causa_troca" onchange='mostraObs(this)' >
								<option selected></option>
								<?
								$sql = "SELECT causa_troca,descricao
										FROM tbl_causa_troca
										WHERE fabrica = $login_fabrica
										ORDER BY descricao";
								$res = pg_query ($con,$sql) ;
								for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

									echo "<option ";
									if ($causa_troca == pg_fetch_result ($res,$i,causa_troca) ) echo " selected ";
									echo " value='" . pg_fetch_result ($res,$i,causa_troca) . "'>" ;
									echo pg_fetch_result ($res,$i,descricao) ;
									echo "</option>";
								}
								?>
							</select>
	                    </div>
                        <div class='span5'>
                            <label class='control-label' for='codigo_posto'>Admin</label>
                            <select name="admin" >
                            <option selected></option>
                                <?
                                $sql = "SELECT admin,nome_completo
                                        FROM tbl_admin
                                        WHERE fabrica = $login_fabrica
                                        AND ativo
                                        ORDER BY nome_completo";
                                $res = pg_query ($con,$sql) ;
                                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                                    echo "<option ";
                                    if ($admin == pg_fetch_result ($res,$i,admin) ) echo " selected ";
                                    echo " value='" . pg_fetch_result ($res,$i,admin) . "'>" ;
                                    echo pg_fetch_result ($res,$i,nome_completo) ;
                                    echo "</option>";
                                }
                                ?>
                            </select>
                        </div>
                	</div>                    
                </div>
            </div>        	
        </div>
        <div class='span2'></div>
    </div>
    
    <p><br/>
        <button class="btn btn-primary" type="button" width:400px;cursor:pointer;" value="&nbsp;" onClick="document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar">Pesquisar
        	</button>

        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</FORM>
<BR>

<? include "rodape.php" ?>
