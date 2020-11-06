/**
 * verifica_posto_credenciamento.js
 */

function verificaPostoCredenciamento()
{
    var posto_codigo = $("input[name='posto_codigo']").val();

    if (!posto_codigo) {
        return false;
    }

    var conf = false;
    $("#confirmouPosto").val("0");

    $.ajax({
        url: "posto_verifica_credenciamento.php",
        data: "posto=" + posto_codigo,
        async: false,
        complete: function (resp) {
            var response = JSON.parse(resp.responseText);

            if (!response.credenciamento) {
                return true;
            }

            if (response.credenciamento == 'EM DESCREDENCIAMENTO') {
                conf = confirm("O posto informado encontra-se em processo de DESCREDENCIAMENTO. Deseja continuar?");

                if (conf == true) {
                    $("#confirmouPosto").val("1");
                } else {
                    $( "input[name='posto_codigo']" ).val("");
                    $( "input[name='posto_nome']" ).val("");
                }
            } else if (response.credenciamento == 'DESCREDENCIADO') {
                alert('Posto informado encontra-se DESCREDENCIADO');
                $("#confirmouPosto").val("2");
                conf = false;
            } else {
                conf = true;
            }

        }
    });

    return conf;
}

function verificaSubmit()
{
    var confirmouPosto = $("#confirmouPosto").val();
    var confirmacao = false;

    switch (confirmouPosto) {
        case "0":
            confirmacao = true;
            break;
        case "1":
            confirmacao = confirm('O Posto informado encontra-se em processo de DESCREDENCIAMENTO. Deseja gravar a OS?');
            break;
        case "2":
            alert('Posto informado encontra-se DESCREDENCIADO');
            break;
        default:
            confirmacao = verificaPostoCredenciamento();
    }

    if (false === confirmacao) {
        return;
    }
    
    document.frm_os.submit();
}

