<?php

/**
 * @class Ftp
 * @author Manuel López <manuel.lopez@telecontrol.com.br>
 * 02 Dez 2015 - Agora mesmo, o comandos ftp implementados são:
 * connect (open), login (user), chdir, put, close, pasv
 * TODO: get files, cdup, etc.
 **/

class Ftp {

    private $conn;    // armazena o resource da conexão
    private $host  = '';
    private $pasv  = true; // TRUE passive mode ON. Defaults to Passive Mode ON
    private $user  = '';
    private $pwd   = '';
    private $port  = 21;
    private $error = '';
    private $startDir = '/';
    private $loggedIn = false;
    private $status = 'disconnected'; // connected logged disconnected

    private static $timeout = 60;

    function __construct($host, $username=null, $passwd=null, $psvmode=true, $port=null) {

        // é possível usar o 'padrão' W3C: [[p]ftp://]user:password@domain.name[/start directory]
        if (!$this->parseHost($host)) {
            throw new Exception('Invalid host name '.$host.'!');
        }

        if (!empty($username))
            $this->setUser($username);

        if (!empty($passwd))
            $this->setPassword($passwd);

        $this->pasv = ($psvmode === true or $psvmode[0] == 'p');

        if (is_int($port))
            $this->port = $port;

        // Tenta a conexão
        // Não valida a conexão: pode ser feita manualmente depois.
        // O método Ftp::connect() salva o estado da conexão e o resto
        // de métodos devem conferir sempre antes.
        $ftpConn = $this->connect($host);
    }

    /**
     * Magic getters
     **/
    public function __get($var) {
        switch ($var) {
            case 'loggedIn':
            case 'error':
            case 'status':
                return $this->$var;

        default:
            # Generate an error, throw an exception, or ...
            return null;
        }
    }

    /**
     * Magic methods para criar aliases dos comandos.
     * Também algum setter, mas continua sendo alias.
     */
    public function __call($fn, $fargs) {
        switch (strtolower($fn)) {
        // Setters...
        case 'sethost': return $this->setServer($fargs[0]); break;

        case 'pasv':
        case 'setmode':
        case 'setpasv':
        case 'set_pasv':
        case 'setpasvmode':
        case 'set_pasv_mode':
            return $this->setPasvMode($fargs[0]); break;

        case 'setusr':
        case 'setusername':
            return setUser($fargs[0]);
        break;

        case 'pwd':
        case 'passwd':
        case 'setpasswd':
        case 'setpassword':
            return setPwd($fargs[0]);
        break;

        // Actions
        case 'open':
        case 'init':
        case 'abrir':
        case 'inciar':
        case 'conectar':
            $this->connect($fargs[0], $fargs[1]); // host:port
        break;

        case 'log_in': case 'logon':
        case 'log_on': case 'logar':
        case 'enter':  case 'entrar':
            $this->login($fargs[0], $fargs[1]);
        break;

        case 'ch_dir':
        case 'cd':
            return $this->chDir($fargs[0]);
        break;

        case 'cdup':
            return $this->chDir('..');
        break;

        case 'put':
        case 'send':
        case 'enviar':
        case 'putfile':
        case 'put_file':
        case 'send_file':
            if (count($fargs) < 2)
                throw new Exception('Argument missing.');
            return $this->sendFile($fargs[0], $fargs[1], @$fargs[2]);
        break;

        case 'quit': case 'sair': case 'fechar':
        case 'desconectar': case 'encerrar': case 'logoff':
        case 'disconnect':  case 'closeconnection':
        case 'logout':      case 'close_connection':
            $this->close();
        break;

        default:
            return null;
            break;
        }
    }

    public function setServer($host) {
        $this->parseHost($host);

        if (!$this->host)
            throw New exception('Unable to parse host name "'.$host.'"');

        return $this;
    }

    public function setPort($port) {
        if (!is_int($port))
            throw new Exception ("Invalid value port connection port number $port.");

        $this->port = $post;

        return $this;
    }

    public function setPasvMode($mode) {
        if ($this->status == 'logged' and $this->loggedIn)
            ftp_pasv($this->conn, $mode);
        return $this;
    }

    public function setUser($username) {
        if (!is_string($username) or !preg_match('/[a-zA-Z0-9_+-]/'))
            throw new Exception ("Invalid username $useranme.");

        $this->user = $username;

        return $this;
    }

    public function setPwd($password) {
        $this->user = $password;
        return $this;
    }

    public function connect($hostName=null, $port=null) {
        $host = ($hostName) ? : $this->host;
        $port = ($port)     ? : $this->port;

        if (!$host) {
            $this->conn = false;
            $logOK = false;
            $this->error = 'FTP connection failed, no host';
            $this->status = 'disconnected';

            return false;
        }

        // é possível usar o 'padrão' W3C:
        // [ftp://][user[:password]@]domain.name[:port][/start directory]
        $this->parseHost($host);

        for ($i=0; $i < 3; $i++) {

            $ftpConn = ftp_connect(
                $this->host,
                $this->port, self::$timeout);

            if (!is_resource($ftpConn)) {
                sleep(1);
                continue;
            }
            $this->conn = $ftpConn;
        }

        if (!is_resource($ftpConn)) {
            $this->error = 'FTP connection failed';
            $this->status = 'disconnected';
            return $this;
        }

        $this->status = 'connected';

        if ($this->status == 'connected' and $this->user and $this->pwd)
            $this->login($this->user, $this->pwd);

        if ($this->startDir)
            $this->chDir($this->startDir);

        return $this;
    }

    /**
     * Os parâmetros são opcionais porque assim é possível
     * logar com os dados já coletados do $host ou dos
     * parâmetros passados ao constutor.
     **/
    public function login($usr=null, $pwd=null) {

        // se já está logado, não faz nada
        // se quiser logar de novo, primeiro tem que deslogar.
        if ($this->loggedIn) {
            return $this;
        }

        $usr = ($usr) ? : $this->user;
        $pwd = ($pwd) ? : $this->pwd;
        $logOK = false;

        if (!$usr or !$pwd) {
            return $this;
        }

        $this->user = $usr;
        $this->pwd  = $pwd;

        if (!is_resource($this->conn)) {
            return $this;
        }

        // echo "Trying to log in {$this->host} using USER: {$this->user} / ".$this->pwd.PHP_EOL;
        $logOK = ftp_login($this->conn, $this->user, $this->pwd);

        $this->loggedIn = $logOK;

        if ($logOK) {
            ftp_pasv($this->conn, $this->pasv);
        }

        $this->status = ($logOK) ? 'logged' : $this->status;

        return $this;
    }

    public function close() {

        if (!$this->loggedIn)
            return $this;

        if (ftp_close($this->conn)) {
            $this->conn = false;
            $this->loggedIn = false;
            $this->status = 'disconnected';
        }
    }

    // camelCase version
    public function isLogged() {
        return ($this->status == 'logged');
    }

    // camelCase version
    public function isConnected() {
        return ($this->status == 'connected' or $this->status == 'logged');
    }

    public function is_logged() {
        return ($this->status == 'logged');
    }

    public function is_connected() {
        return ($this->status == 'connected' or $this->status == 'logged');
    }

    public function chDir($newdir) {
        if ($this->status != 'logged') {
            return $this;
        }

        if (!ftp_chdir($this->conn, $newdir))
            $this->error = "CHDIR '$newdir' error.";

        return $this;
    }

    public function sendFile($src_file, $remote_file, $remote_dir=null) {

        if (!$this->loggedIn)
            return false;

        if (!is_readable($src_file)) {
            $this->error = 'Invalid local file.';
            return false;
        }

        if ($remote_dir) {
            // muda se não estiver já nesse diretório, economiza tempo?
            if (strpos(ftp_pwd($this->conn), $remote_dir) === false)
                $cdOK = $this->chDir($remote_dir);
            else
                $cdOK = true;
        } else $cdOK = true;

        if ($cdOK)
            if (!ftp_put($this->conn, $remote_file, $src_file, FTP_BINARY)) {
                $this->error = "File '$src_file' not uploaded as '$remote_file'!";
                return false;
                // throw new Exception($this->error);
            }
        return true;
    }

    private function parseHost($host) {
        if (preg_match(RE_URL, $host, $r)) {
            if (count($r) == 5 and $r['server'] == $host) {
                $this->host = $host;
                return true; // sai, é um simples server name
            }
        }

        $regex = '/^(?<mode>p)?(?<protocol>(?:http|ftp|file))(?<TLS>s?)(?::\/\/)?' . // protocol
                 '(?<usr>[^#@\/:]+):(?<pwd>.+)@' .  // login information
                 '(?<host>(?:[a-zA-Z0-9_-]+\.?)+)'. // server host name/ip address
                 '(?::(?<port>\d{2,3}))?'.          // connection port
                 '(?<dir>(?:\/[a-zA-Z0-9_-]+)+)$/'; // initial directory

        if (!preg_match($regex, $host, $result))
            return false;

        $this->host     = $result['host'];
        $this->user     = $result['usr'];
        $this->pwd      = $result['pwd'];
        $this->startDir = $result['dir'];
        $this->pasv     = ($result['mode'] == 'p');

        if ($result['port'])
            $this->port = $result['port'];

        return $this;
    }
}

