if ($ == undefined) {
    console.log('Esta rotina precisa do jQuery para rodar');
} else {

    /**
     * A imagem a clicar vai ser o 'X' vermelho (imagens/cross.png),
     * e vair ter o attr('name') com o path do arquivo a ser exclu�do.
     *
     * O retorno da exclus�o deve conter a nova tabela com os anexos
     * que restaram. Se n�o, era bom retornar uma mensagem informando
     * que n�o h� anexos, ou que foram exclu�dos todos os anexos.
     *
     * Em caso de erro, seria legal informar ao usu�rio do motivo.
     **/
    $().ready(function() {

        var program_self = window.location.pathname;
        var blocoNF;
        $('#anexos img.excluir_NF').click(function() {

            blocoNF = $('table#anexos');

            var nota = $(this).attr('name');
            // Se n�o estiver certinho, que deveria, limpa a string
            nota = nota.replace(/^http:\/\/[a-z0-9.-]+\//, '')
            if (nota.indexOf('?')>-1) nota = nota.substr(0, nota.indexOf('?'));

            var excluir_str = 'Confirma a exclus�o do arquivo "' + nota + '" ?';
            if (confirm(excluir_str) == false) return false;

            $.post(program_self, {
                'excluir_nf': nota,
                'ajax':       'excluir_nf'
            },
            function(data) {
                var r = data.split('|');
                //console.log("'" + r[0] + "'\n" + r[2]);
                if (r[0] == 'ok') {
                    alert('Imagem exclu�da com �xito');
                    if (r[1].indexOf('<tr')>0) blocoNF.html(r[1]); // S� se vier uma outra tabela!
                    if (r[1] == '')            blocoNF.remove();
                } else {
                    alert('Erro ao excluir o arquivo. '+r[1]);
                }
            });

        });
    });

}
