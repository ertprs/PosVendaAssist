if (!window.Notification) {
  console.log("Este navegador não tem suporte a Notificações.");
}
else {
  var NotificationTC = {
    // attributes
    status: Notification.permission === 'granted'?true: Notification.permission==='denied'?false:null, // NULL: unititialized, FALSE: denied, TRUE granted
    muted: localStorage.getItem('NoTC') === 'off',
    notificacao: null,   // Notification object.
    timeInterval: 180000,
    intProc: null,     // ID do Interval, quando estiver ativo
    intCheckMuted: null,
    permission: Notification.permission,
    // properties
    icons: {
      main: "imagens/imagens_admin/tc_logo.png",
      // on: 'glyphicon-bullhorn',
      // off: 'glyphicon-volume-off',
      // none: 'glyphicon-stop'
      on: 'glyphicon-play',
      off: 'glyphicon-pause',
      none: 'glyphicon-question-sign'
    },
    ajaxURL: 'notificacao_inicio_trabalho.php',
    // methods
    checkMuted: function() {
      var anterior = this.muted;
      this.muted = localStorage.getItem('NoTC') === 'off';
      if (this.muted !== anterior) {
        this.updateIcon();
      }
    },
    dispatch: function(nome, mensagem, tag) {
      var tag = tag || "inicio_trabalho";

      if (this.status === null && this.permission === 'default') {
        return this;
      }

      if (this.muted)
        return this;

      this.notificacao = new Notification(nome, {
        icon: "imagens/imagens_admin/tc_logo.png",
        body: mensagem,
        tag: tag
      });
      this.notificacao.addEventListener('error', function(Err) {
        console.log(Err);
      });
      return this;
    },
    askPermission: function(action) {
      if (this.permission === 'default' && this.status === null) {
        var self = this;
        Notification.requestPermission().then(function(response) {
          self.permission = response;
          self.status = response === 'granted' ? true : response === 'denied' ? false : null;

          if (action == 'start')
            self.start();
          return self;
        });
        localStorage.setItem('NoTC', self.status == null ? 'undefined' : 'off');
      }
      if (action == 'start')
        this.start();
      return this;
    },
    verificaInicioTrabalho: function() {
      var self = this;
      if (typeof $ != "undefined" && ("Notification" in window)) {
        this.permission = Notification.permission;

        if (this.status !== true) {
          return this.stop();
        }
        this.updateIcon();

        $.ajax({
          url: self.ajaxURL,
          type: "post",
          data: { ajax_verifica_inicio_trabalho: true },
          complete: function(data) {
            data = JSON.parse(data.responseText);
            if (data.trabalho === false) {
              self.dispatch(data.nome, data.mensagem);
            }
          }
        });
      }
      return this;
    },
    start: function(tInt) {
      if (tInt !== undefined && parseInt(tInt, 10) !== NaN) {
        this.timeInterval = tInt < 1000 ? tInt*1000 : tInt;
      }

      if (this.status === true && this.intProc === null) {
        this.intProc = setInterval(function() {
            NotificationTC.verificaInicioTrabalho();
          }, this.timeInterval);
        this.muted = false;
      }
      this.updateIcon();
      return this;
    },
    stop: function() {
      if  (this.intProc !== null) {
        clearInterval(this.intProc);
        this.intProc = null;
        this.muted = true;
      }
      this.updateIcon();
      return this;
    },
    restart: function(tInt) {
      this.stop();this.start(tInt);
      return this;
    },
    toggleNotifications: function() {
      if (this.permission === null) {
        this.askPermission('start');
      }
      this.muted ? this.start() : this.stop();
    },
    updateIcon: function() {
      if (this.permission === 'default') {
        $("#notIcon").removeClass(this.icons.on).removeClass(this.icons.off).addClass(this.icons.none);
        localStorage.setItem('NoTC', 'undefined');
      }
      if (this.muted) {
        $("#notIcon").removeClass(this.icons.none).removeClass(this.icons.on).addClass(this.icons.off);
        localStorage.setItem('NoTC', 'off');
      } else {
        $("#notIcon").removeClass(this.icons.none).removeClass(this.icons.off).addClass(this.icons.on);
        localStorage.setItem('NoTC', 'on');
      }
      return this;
    }
  };

  // Confere se alguma outra tela mudou o status
  NotificationTC.intCheckMuted = window.setInterval(function() {NotificationTC.checkMuted();}, 2000);

  $(function() {
    if (NotificationTC.permission === 'granted') {
      var userPermission = localStorage.getItem('NoTC') || 'undefined';
      userPermission == 'off' ? NotificationTC.stop() : NotificationTC.askPermission('start');
    } else if (NotificationTC.permission === 'default') {
      NotificationTC.askPermission('start');
    } else {
      NotificationTC.updateIcon();
    }
    // if ($("#notIcon").length === 1) {
    //   $("#notIcon").click(function() {
    //     NotificationTC.toggleNotifications();
    //   });
    // }
  });
}

