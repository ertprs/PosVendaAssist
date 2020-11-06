<script>
    $(function () {
        Shadowbox.init({
    // let's skip the automatic setup because we don't have any
    // properly configured link elements on the page
    skipSetup: true,
    modal: true
    });

    window.onload = function() {
        comunicado1();
        var mostrou = 0;
        function comunicado1(){
            Shadowbox.open({
            content:
            '<table align="center" width="650" border="0" cellpadding="0" cellspacing="0" style="border: 2px solid #434390;margin: 20px auto;">'+
            '<tbody>'+
            '<tr style="">'+
            '<td style="padding: 0 60px; margin: 0 auto;text-align: left;">'+
            '<p style="font-family:Arial, Helvetica; font-size: 15px;color: 808080; font-weight:normal; line-height: 30px;margin: 0;text-align: left;padding: 40px 0">'+
            'Comunicamos que durante os dias 16/12 e 17/12, nossos servidores serão atualizados, e o sistema poderá apresentar quedas e/ou instabilidade durante o processo de atualização. O inicio do processo será no <b>dia 16/12</b> às <b>15h00</b>. Nossa espectativa é que o sistema volte à operação normal às <b>00h01</b>, do <b>dia 18/12</b>.'+
            '<br><br>'+
            'Lamentamos quaisquer inconvenientes, e reiteramos nosso compromisso com a busca pela qualidade e satisfação de nossos clientes e parceiros.'+
            '<br>Equipe Telecontrol'+
            '</p>'+
            '</td>'+
            '</tr>'+
            '<tr style="text-align:center;">'+
            '<td style="padding-top:0;">'+
            '<table width="100%">'+
            '<tr>'+
            '<td width="25%"></td>'+
            '<td width="50%" style="padding-bottom:50px;text-align:center;"><a href="http://telecontrol.com.br" target="_blank"><img src="https://www.telecontrol.com.br/images/logo.png" alt="Telecontrol" style="border: 0; margin: 0;" width ="200"></a></td>'+
            '<td width="25%" style="text-align:center;"><span style="font-family:Arial;font-size:9px;color:#ccc;">Deus é o Provedor.</span></td>'+
            '</tr>'+
            '</table>'+
            '</td>'+
            '</tr>'+
            '</tbody>'+
            '</table>',
        player:     "html",
        title:      "Comunicado Importante",
        height:     450,
        width:      760
        })
        }
        
    };
});
</script>
