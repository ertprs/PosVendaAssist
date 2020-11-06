<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios = "financeiro";
include_once "funcoes.php";

$layout_menu = "financeiro";
$title = "RELATÓRIO DE ORDENS DE SERVIÇO CONFERIDAS POR LINHA";

include_once "cabecalho_new.php";

$inputs = array(
	# A key será o name e id do input
	"input1" => array(
		# Tamanho da div do input, caso for usar a nossa class de tamanho mude span para inptc
		"span"      => 4,
		# Label do input
		"label"     => "Input",
		# Type (input/type, select, checkbox, radio)
		"type"      => "input/text",
		# Tamanho do input
		"width"     => 5,
		# Obrigatoriedade faz aparecer o * ao lado esquerdo do input
		"required"  => true,
		# Attr's extras key = nome do attr, value = valor do attr
		"extra" => array("attr" => "value"),
		"readonly"  => true,
		"maxlength" => 10
	),
	"select1" => array(
		"span"      => 4,
		"label"     => "Select",
		"type"      => "select",
		"width"     => 5,
		"required"  => true,
		# Options do select key = value do option, value = Texto(label) do option
		"options"  => array(
			"t" => "True",
			"f" => "False"
		),
	),
	"checkbox1" => array(
		"span"      => 4,
		"label"     => "Checkbox",
		"type"      => "checkbox",
		# Opções do checkbox key = value do check, value = Label do check
		"checks"  => array(
			"1" => "Check1",
			"2" => "Check2",
			"3" => "Check3"
		),
	),
	"radio1" => array(
		"span"      => 4,
		"label"     => "Radio",
		"type"      => "radio",
		# Opções do radio key = value do radio, value = Label do radio
		"radios"  => array(
			"1" => "Radio1",
			"2" => "Radio2",
			"3" => "Radio3"
		),
	)
);
$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
);

include("plugin_loader.php");
?>

<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_inicial", "data_final"));
        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

    });
    
    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function mostraLancamento(lancamento){
        $('#sem_'+lancamento).toggle();
    }

    function mostraLancamento2(lancamento){
        $('#com_'+lancamento).toggle();
    }
    
</script>