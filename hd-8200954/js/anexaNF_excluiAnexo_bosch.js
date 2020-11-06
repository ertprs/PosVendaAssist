if ($ == undefined) {
    console.log('Esta rotina precisa do jQuery para rodar');
} else {

    /**
     * A imagem a clicar vai ser o 'X' vermelho (imagens/cross.png),
     * e vair ter o attr('name') com o path do arquivo a ser excluído.
     *
     * O retorno da exclusão deve conter a nova tabela com os anexos
     * que restaram. Se não, era bom retornar uma mensagem informando
     * que não há anexos, ou que foram excluídos todos os anexos.
     *
     * Em caso de erro, seria legal informar ao usuário do motivo.
     **/
    $().ready(function() {

        var program_self = window.location.pathname;
        var blocoNF;
        $('#anexos img.excluir_NF').click(function() {

            blocoNF = $('table#anexos');

            var nota = $(this).attr('name');
            // Se não estiver certinho, que deveria, limpa a string
            nota = nota.replace(/^http:\/\/[a-z0-9.-]+\//, '')
            if (nota.indexOf('?')>-1) nota = nota.substr(0, nota.indexOf('?'));

            var excluir_str = 'Confirma a exclusão do arquivo "' + nota + '" ?';
            if (confirm(excluir_str) == false) return false;

            $.post(program_self, {
                'excluir_nf': nota,
                'ajax':       'excluir_nf'
            },
            function(data) {
                var r = data.split('|');
                //console.log("'" + r[0] + "'\n" + r[2]);
                if (r[0] == 'ok') {
                    alert('Imagem excluída com êxito');
                    if (r[1].indexOf('<tr')>0) blocoNF.html(r[1]); // Só se vier uma outra tabela!
                    if (r[1] == '')            blocoNF.remove();
                } else {
                    alert('Erro ao excluir o arquivo. '+r[1]);
                }
            });

        });
    });

}
