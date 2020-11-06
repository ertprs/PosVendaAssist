var span = "";
var subdiv = "";
var arrayText = "";
var FilaElementos = [];


$(document).ready(function() {

    if (window.File && window.FileReader && window.FileList && window.Blob) {

        document.getElementById('csv').addEventListener('change', changeFileSelect, false);
        $("#hasHeaders").click(function() {

            if ($(this).is(':checked')) {
                if (arrayText[0] != undefined) {
                    document.getElementById('layout').innerHTML = arrayText[0];
                    makeListNotepad(arrayText);
                }
            } else {
                document.getElementById('layout').innerHTML = "";
            }

        });
    } else {
        makeAlerta('env-alerta', "  Alguns Recursos de validação não são suportados pelo seu Navegador.");
    }

    $('.metodo-options').click(function() {
        $("#metodoSelecionado").html($(this).html());
    });




    $("#checkAll").click(function() {
        if ($(this).is(':checked')) {
            $(".check-line").attr('checked', '');
            $("#btn-acao-selecionados").fadeIn('200');
        } else {
            $(".check-line").removeAttr('checked');
            $("#btn-acao-selecionados").fadeOut('200');
        }
    });

    $(".check-line").click(function() {

        var qtdChecados = $('.check-line:checked').length;
        if (qtdChecados > 1) {
            $("#btn-acao-selecionados").fadeIn("200");
        } else {
            $("#btn-acao-selecionados").fadeOut("200");
        }
    });

    /* preparação para enviar uma linha independente */
    $(".btn-acao").click(function() {
        var line = $(this).parents('tr');
        var btnTD = $(this);

        var metodoSelecionado = $("#metodoSelecionado").html();
        if (metodoSelecionado == "") {

            makeAlerta("env-alerta-erro", '  Selecione uma ação', 'alert-error', 'Atenção!');
            timeout = setTimeout(function() {
                $("#btn-seleciona-acao").click();
                clearTimeout(timeout);


                timeout2 = setTimeout(function() {
                    $("#env-alerta-erro").fadeOut(1000, function() {
                        $(this).html("");
                        $(this).fadeIn();
                    })
                    clearTimeout(timeout2);
                }, 2000);


            }, 500);

            return false;
        }

        var record = {data: {0: JSON.parse($(this).parents('tr').find('input.data').val())}, action: metodoSelecionado};

        var loadImg = '<img src="' + urlBase + '/../theme/bootstrap/img/load1.gif" />'
        makeAlerta('env-alerta-erro', '    Aguarde, processando operação...', 'alert-info', loadImg);

        $(line).removeClass('success');
        $(line).removeClass('error');
        FilaElementos.push(line);
        modelModulo(record);
    });

    /* preparação para enviar várias linhas de uma só vez */
    $("#btn-acao-selecionados").click(function() {

        var metodoSelecionado = $("#metodoSelecionado").html();
        if (metodoSelecionado == "") {

            makeAlerta("env-alerta-erro", '  Selecione uma ação', 'alert-error', 'Atenção!');
            timeout = setTimeout(function() {
                $("#btn-seleciona-acao").click();
                clearTimeout(timeout);


                timeout2 = setTimeout(function() {
                    $("#env-alerta-erro").fadeOut(1000, function() {
                        $(this).html("");
                        $(this).fadeIn();
                    })
                    clearTimeout(timeout2);
                }, 2000);


            }, 500);

            return false;
        }

        var linhas = $(".check-line:checked");

        var i = 0;
        var record = {action: metodoSelecionado, data: []};
        for (var ind = 0, linha; linha = linhas[ind]; ind++) {
            (record.data).push(JSON.parse($(linha).parent().parent().find('input.data').val()));
        }

        $(linhas).parent().parent().removeClass('success');
        $(linhas).parent().parent().removeClass('error');

        var loadImg = '<img src="' + urlBase + '/../theme/bootstrap/img/load1.gif" />'
        makeAlerta('env-alerta-erro', '    Aguarde, processando operação...', 'alert-info', loadImg);

        FilaElementos.push(linhas);
        modelModulo(record);
    });

});

function makeAlerta(target, alerta, tipo, titulo) {
    if (titulo == "") {
        titulo = "Aviso! ";
    }
    document.getElementById(target).innerHTML = "<div class='alert " + tipo + "'><button type='button' class='close' data-dismiss='alert'>&times;</button><strong>" + titulo + "</strong>" + alerta + "</div>";
}

function makeListNotepad(arrayText) {
    cabecalho = arrayText[0];

    cabecalhoColunas = cabecalho.trim().split(";");

    line = arrayText[1];
    linhaColunas = line.trim().split(';');

    notepad = [];
    notepad.push('<p>Campos do arquivo, primeira linha</p>',
            '<p>' + arrayText.length + ' Linhas</p>',
            '<pre class="notepad_paper">');

    for (coluna = 0; coluna < linhaColunas.length; coluna++) {
        notepad.push("        " + linhaColunas[coluna] + "\n");

    }
    notepad.push('</pre>');

    document.getElementById('fileHeaders').innerHTML = notepad.join('');
    $("#fileHeaders").fadeIn('500');
}

function changeFileSelect(evento) {

    var files = evento.target.files;
    var output = [];

    for (var i = 0, f; f = files[i]; i++) {
        output.push(
                f.name + '(',
                f.type || 'n/a', ') - ',
                f.size, 'bytes, Ultima Modificação: ',
                f.lastModifiedDate.toLocaleDateString());

        var reader = new FileReader();

        reader.onload = (function(theFile) {
            return function(e) {
                //span = document.createElement('span');
                /*span = [e.target.result,
                 'title ', escape(theFile.name)].join('');*/
                resultText = e.target.result;
                arrayText = resultText.trim().split(/\r?\n|\r/m);
                if ($("#hasHeaders").is(':checked') == true) {
                    document.getElementById('layout').innerHTML = arrayText[0];
                    makeListNotepad(arrayText);
                }
            };
        })(f);

        //reader.readAsDataURL(f);
        reader.readAsText(f);
    }
    makeAlerta('env-alerta', output.join(''), 'alert-info', 'Arquivo: ');
}


/**	
 operacao: add,upd,del,get
 */
function modelModulo(data) {
    data.ajax2 = true;
    makeRequest(urlBase, data, 'post', retornoAjax);
}

function retornoAjax(retorno) {    
    var metodoSelecionado = $("#metodoSelecionado").html();
    retorno = JSON.parse(retorno);
    
    showRetornoApi(retorno);
    switch (metodoSelecionado) {
        case "POST":
            for (indice in retorno) {

                switch (retorno[indice].status_code) {
                    case 201:
                        console.log(retorno[indice].status_code);
                        console.log(retorno[indice].message.status);
                        makeAlerta('env-alerta-erro', 'Requisição Finalizada: '+retorno[indice].message.status, 'alert-success','Status '+retorno[indice].status_code+"  ");
                        break;
                    case 400:
                        console.log(retorno[indice].status_code);
                        console.log(retorno[indice].message.status_message);
                        makeAlerta('env-alerta-erro', 'Requisição Finalizada: '+retorno[indice].message.status_message, 'alert-error','Status '+retorno[indice].status_code+"  ");
                        break;
                }

                if (indice === 'error') {
                    console.log(retorno['error']);
                    for (indice_erro in retorno['error']) {
                        console.log("O campo " + indice_erro + " falhou gerou um erro: " + retorno['error'][indice_erro].rule);
                        makeAlerta('env-alerta-erro', "O campo " + indice_erro + " falhou gerou um erro: " + retorno['error'][indice_erro].rule , 'alert-error','Erro de Validação: ');
                    }
                }
            }
            break;
        case "PUT":
            for (indice in retorno) {
                if (retorno[indice].error !== undefined) {
                    console.log(retorno[indice].error);
                    makeAlerta('env-alerta-erro', 'Requisição Finalizada: '+retorno[indice].error, 'alert-error','');
                } else {
                    console.log(retorno[indice].status_code);
                    console.log(retorno[indice].status);
                    makeAlerta('env-alerta-erro', 'Requisição Finalizada: '+retorno[indice].status, 'alert-success','Status '+retorno[indice].status_code+"  ");
                }
            }
            break;
        case "DELETE":
            for (indice in retorno) {
                console.log(retorno[indice].status_code);
                console.log(retorno[indice].status);
                makeAlerta('env-alerta-erro', 'Requisição Finalizada: '+retorno[indice].status, 'alert-success','Status '+retorno[indice].status_code+"  ");
            }
            break;
        case "GET":

            break;
    }    
}

function showRetornoApi(message) {
    $("#retornoApi").html(JSON.stringify(message));
    makeAlerta('env-alerta-erro', 'Requisição Finalizada', 'alert-info','');
}