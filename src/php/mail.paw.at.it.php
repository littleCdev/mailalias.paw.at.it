<?php
/*
 * Database config
 */
class DB {
    const       User    = "mailUser"    ;
    const       Pw      = "MyEmailAlias";
    const       Host    = "localhost"   ;
    const       Db      = "mail"        ;
}

/**
 * Class mailAliasPawAtIt
 * errors ->lastError();
 * usermessages ->getUserMsg();
 */
class mailAliasPawAtIt{
    const       InsertsPerDay   = 3     ;   // maximum inserts an ip is allowed to do per day

    private $aErrors    = [];
    private $sUserMsg   = "";

    /**
     * @name        \addError
     * @function    adds an error-string to internal array
     * @param       string $sError
     */
    private function addError( $sError ) {
        array_push($this->aErrors,$sError);
    }

    /**
     * @name        \lastError
     * @function    returns the last occurred error
     * @return      string
     */
    public function lastError( ) {
        return end( $this->aErrors );
    }

    /**
     * @name        \setUserMsg
     * @function    sets a user-message
     * @param       string $sUserMsg
     */
    public function setUserMsg( $sUserMsg ) {
        $this->sUserMsg = $sUserMsg;
    }

    /**
     * @name        \getUserMsg
     * @functions   returns "error"-message for the user
     * @return string
     */
    public function getUserMsg() {
        return $this->sUserMsg;
    }

    /**
     * @name        \chkName
     * @function    checks the usersname
     * @param       string $sName
     * @return      bool
     */
    public function chkName ( $sName ){
        if( !preg_match("/^[a-zA-Z0-9_.]{3,120}$/", $sName) ){
            $this->setUserMsg( "Invalid Username only letters, underscore and dots" );

            return false;
        }

        if( !preg_match("/^[a-zA-Z]{2,}$/", $sName) ){
            $this->setUserMsg( "Your alias must include two letters at least" );

            return false;
        }

        $sQuery = "select id from postfix_alias where alias='".$sName."'";
        $oDb = a9Db::getInstance();

        if( !$oDb->query( $sQuery ) ){
            $this->setUserMsg( "server error" );

            return false;
        }

        if( $oDb->iRows > 0){
            $this->setUserMsg( "Name is already taken" );

            return false;
        }

        return true;
    }


    /**
     * @name        \chkMail
     * @function    checks if the email exists
     * @param       string $sMail
     * @return      bool
     */
    public function chkMail ( $sMail ){
        $oVerify = new verifyEmail();

        if( !$oVerify->check($sMail) && !$oVerify->isValid( $sMail) ){
            $this->setUserMsg( "invalid email");

            return false;
        }

        $oDb = a9Db::getInstance();
        $sQuery = "select id from postfix_alias where destination ='".$sMail."'";
        if( !$oDb->query( $sQuery ) ){
            $this->setUserMsg( "server error" );

            return false;
        }

        if( $oDb->iRows > 0 ){
            $this->setUserMsg( "your email is already in the database" );

            return false;
        }

        return true;
    }

    /**
     * @name        addAlias
     * @function    add the alias with email to the database
     * @param       string $sName
     * @param       string $sEmail
     * @return      bool
     */
    public function addAlias ( $sName, $sEmail ){

        if( !$this->chkName( $sName ) )
            return false;

        if( !$this->chkMail( $sEmail ) )
            return false;

        $sQuery = "select id from postfix_alias where ip='".$_SERVER['REMOTE_ADDR']."' and addedon between '".date("Y-m-d H:i:s", strtotime('-1 day'))."' AND '".date('Y-m-d H:i:s')."';";

        $oDb = a9Db::getInstance();

        if( !$oDb->query( $sQuery ) ){
            $this->setUserMsg( "server error" );

            return false;
        }

        if( $oDb->iRows > mailAliasPawAtIt::InsertsPerDay ){
            $this->setUserMsg( "you made too many requests, i said one per user" );

            return false;
        }

        $sAuthcode = substr(md5( rand() +42 ), 0, 7); // !RANDOM+42

        $sQuery = "insert into postfix_alias (
                                                alias,
                                                destination,
                                                ip,
                                                addedon,
                                                authcode
                                                )values (
                                              '".$sName."',
                                              '".$sEmail."',
                                              '".$_SERVER['REMOTE_ADDR']."',
                                              '".date("Y-m-d H:i:s")."',
                                              '".$sAuthcode."'
                                              )";

        if( !$oDb->query( $sQuery ) ){
            $this->setUserMsg( "server error" );

            return false;
        }

        $subject = 'paw.at.it EmailAlias-activation';
        $message = "
You requested the emailalias ".$sName."@paw.at.it to this email.
Click on the link to activate it. http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/?c=".$sAuthcode."

This request came from ".$_SERVER['REMOTE_ADDR']." if you didn't request this please send an email to hostmaster@paw.at.it";

        $headers = 'From: hostmaster@paw.at.it' . "\r\n" .
            'Reply-To: hostmaster@paw.at.it' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail($sEmail, $subject, $message, $headers);

        $this->setUserMsg( "An activationlink has been send to your email" );

        return true;
    }


    /**
     * @name        \activate
     * @function    activates the alias with the given code
     * @param       string $sCode
     * @return      bool
     */
    public function activate ( $sCode ){

        if( !preg_match("/^[a-f0-9]{7}$/",$sCode) ){
            header("location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/");
            die();
        }

        $oDb = a9Db::getInstance();
        $sQuery = "update postfix_alias set active=1 where authcode='".$sCode."'";

        if( !$oDb->query( $sQuery ) ){
            $this->setUserMsg( "Server error" );

            return false;
        }
        if( $oDb->iRows != 1 ){
            header("location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/");
            die();
        }

        $sQuery = "select alias from postfix_alias where authcode='".$sCode."'";

        if( !$oDb->query( $sQuery ) ){
            $this->setUserMsg( "Server error" );

            return false;
        }

        $row = mysqli_fetch_object( $oDb->result );

        $this->setUserMsg("Your email ".$row->alias."@paw.at.it has been activated");

        return true;
    }

}

/**
 * Class to check up e-mail
 *
 * @author Konstantin Granin <kostya@granin.me>
 * @copyright Copyright (c) 2010, Konstantin Granin
 */
class verifyEmail {

    /**
     * User name
     * @var string
     */
    private $_fromName;

    /**
     * Domain name
     * @var string
     */
    private $_fromDomain;

    /**
     * SMTP port number
     * @var int
     */
    private $_port;

    /**
     * The connection timeout, in seconds.
     * @var int
     */
    private $_maxConnectionTimeout;

    /**
     * The timeout on socket connection
     * @var int
     */
    private $_maxStreamTimeout;

    public function __construct() {
        $this->_fromName = 'noreply';
        $this->_fromDomain = 'paw.at.it';
        $this->_port = 25;
        $this->_maxConnectionTimeout = 30;
        $this->_maxStreamTimeout = 5;
    }

    /**
     * Set email address for SMTP request
     * @param string $email Email address
     */
    public function setEmailFrom($email) {
        list($this->_fromName, $this->_fromDomain) = $this->_parseEmail($email);
    }

    /**
     * Set connection timeout, in seconds.
     * @param int $seconds
     */
    public function setConnectionTimeout($seconds) {
        $this->_maxConnectionTimeout = $seconds;
    }

    /**
     * Set the timeout on socket connection
     * @param int $seconds
     */
    public function setStreamTimeout($seconds) {
        $this->_maxStreamTimeout = $seconds;
    }

    /**
     * Validate email address.
     * @param string $email
     * @return boolean  True if valid.
     */
    public function isValid($email) {
        return (false !== filter_var($email, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Get array of MX records for host. Sort by weight information.
     * @param string $hostname The Internet host name.
     * @return array Array of the MX records found.
     */
    public function getMXrecords($hostname) {
        $mxhosts = array();
        $mxweights = array();
        if (getmxrr($hostname, $mxhosts, $mxweights)) {
            array_multisort($mxweights, $mxhosts);
        }

        /**
         * Add A-record as last chance (e.g. if no MX record is there).
         * Thanks Nicht Lieb.
         */
        $mxhosts[] = $hostname;
        return $mxhosts;
    }

    /**
     * check up e-mail
     * @param string $email Email address
     * @return boolean True if the valid email also exist
     */
    public function check($email) {
        $result = false;
        if ($this->isValid($email)) {
            list($user, $domain) = $this->_parseEmail($email);
            $mxs = $this->getMXrecords($domain);
            $fp = false;
            $timeout = ceil($this->_maxConnectionTimeout / count($mxs));
            foreach ($mxs as $host) {
//                if ($fp = @fsockopen($host, $this->_port, $errno, $errstr, $timeout)) {
                if ($fp = @stream_socket_client("tcp://" . $host . ":" . $this->_port, $errno, $errstr, $timeout)) {
                    stream_set_timeout($fp, $this->_maxStreamTimeout);
                    stream_set_blocking($fp, 1);
//                    stream_set_blocking($fp, 0);
                    $code = $this->_fsockGetResponseCode($fp);
                    if ($code == '220') {
                        break;
                    } else {
                        fclose($fp);
                        $fp = false;
                    }
                }
            }
            if ($fp) {
                $this->_fsockquery($fp, "HELO " . $this->_fromDomain);
                //$this->_fsockquery($fp, "VRFY " . $email);
                $this->_fsockquery($fp, "MAIL FROM: <" . $this->_fromName . '@' . $this->_fromDomain . ">");
                $code = $this->_fsockquery($fp, "RCPT TO: <" . $user . '@' . $domain . ">");
                $this->_fsockquery($fp, "RSET");
                $this->_fsockquery($fp, "QUIT");
                fclose($fp);
                if ($code == '250') {
                    /**
                     * http://www.ietf.org/rfc/rfc0821.txt
                     * 250 Requested mail action okay, completed
                     * email address was accepted
                     */
                    $result = true;
                } elseif ($code == '450' || $code == '451' || $code == '452') {
                    /**
                     * http://www.ietf.org/rfc/rfc0821.txt
                     * 450 Requested action not taken: the remote mail server
                     *     does not want to accept mail from your server for
                     *     some reason (IP address, blacklisting, etc..)
                     *     Thanks Nicht Lieb.
                     * 451 Requested action aborted: local error in processing
                     * 452 Requested action not taken: insufficient system storage
                     * email address was greylisted (or some temporary error occured on the MTA)
                     * i believe that e-mail exists
                     */
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * Parses input string to array(0=>user, 1=>domain)
     * @param string $email
     * @return array
     * @access private
     */
    private function _parseEmail(&$email) {
        return sscanf($email, "%[^@]@%s");
    }

    /**
     * writes the contents of string to the file stream pointed to by handle $fp
     * @access private
     * @param resource $fp
     * @param string $string The string that is to be written
     * @return string Returns a string of up to length - 1 bytes read from the file pointed to by handle.
     * If an error occurs, returns FALSE.
     */
    private function _fsockquery(&$fp, $query) {
        stream_socket_sendto($fp, $query . "\r\n");
        return $this->_fsockGetResponseCode($fp);
    }

    /**
     * Reads all the line long the answer and analyze it.
     * @access private
     * @param resource $fp
     * @return string Response code
     * If an error occurs, returns FALSE
     */
    private function _fsockGetResponseCode(&$fp) {
        do {
            $reply = stream_get_line($fp, 1024, "\r\n");
            $status = stream_get_meta_data($fp);
        } while (($reply[3] != ' ') && ($status['timed_out'] === FALSE));

        preg_match('/^(?<code>[0-9]{3}) (.*)$/ims', $reply, $matches);
        $code = isset($matches['code']) ? $matches['code'] : false;
        return $code;
    }

}

class a9Db {

    // status variables for connection and so
    private			$bConnection	= false ;

    // result and numrows etc
    public 			$result			= null	;	// mysql-result for selects
    public          $iLastId        = -1    ;   // last insert id
    private			$bResult		= false	;	// status of the last query
    public 			$iRows			= -1	;	// selected rows or count of deleted/insert/updated rows

    // mysqli connector
    private         $oMysql                 ;
    // for single instance
    private static 	$oInstance 		= null	;
    // array with error-measges
    private         $aErrors        = array();

    /**
     * @name 	    a9Db\__construct
     */
    public function __construct() {

    }

    /**
     * @name        a9Db\__destruct
     * @function    disconnects from mysql on exit
     */
    public function __destruct() {
        $this->disconnect();
    }

    /**
     * @name	    a9Db\getInstance
     * @function    gets an existing instance of the class or creates a new if there is no actual instance
     * @return      a9Db object
     */
    public static function getInstance() {
        if ( self::$oInstance === null ) {
            self::$oInstance = new a9Db();
        }
        return self::$oInstance;
    }

    /**
     * @name        a9Db\addError
     * @function    adds an error-string to internal array
     * @param       $sError
     */
    private function addError( $sError ) {
        array_push($this->aErrors,$sError);
    }

    /**
     * @name        a9Db\lastError
     * @function    returns the last occurred error
     * @return      string
     */
    public function lastError( ) {
        return end( $this->aErrors );
    }

    /**
     * @name 	    a9Db\getStatus
     * @function    returns the status of the last query (bool)
     * @return      bool
     */
    public function getStatus () {
        return $this->bResult;
    }

    /**
     * @name    	a9Db\clear()
     * @function    clears/resets the result-variables of the object
     * @return      bool
     */
    public 	function	clear() 	{
        $this->result	= null	;
        $this->bResult	= false	;
        $this->iRows	= -1	;
        $this->iLastId  = -1    ;

        return true;
    }

    /**
     * @name        a9Db\connect
     * @function    tries to connect to mysql
     * @return      bool
     */
    public function connect(){

        if( !class_exists("mysqli") ) {
            $this->addError("class mysqli does not exist");

            return false;
        }
        $this->oMysql  = @new mysqli( DB::Host, DB::User, DB::Pw, DB::Db );

        // error while connecting to database
        if ( mysqli_connect_errno() != 0 ){
            $this->addError( mysqli_connect_error() );
            $this->bConnection = false;

            return false;
        }

        // set charset to UTF8
        $this->oMysql->set_charset("utf8");

        $this->bConnection = true ;
        return true;
    }

    /**
     * @name        a9Db\disconnect
     * @function    disconnects from mysql
     */
    public function disconnect(){
        if( $this->bConnection )
            $this->oMysql->close( );
    }


    /**
     * @name        a9Db\query
     * @function    queries the database and stores the result in $this->result
     * @param       $sQuery
     * @return      bool
     */
    public function query( $sQuery ){
        // clear the result things first
        $this->clear();

        // connect if there is no sqlconnection
        if( !$this->bConnection ){
            if( !$this->connect() ) {
                $this->addError( "tried to connect but failed won't do the query" );
                $this->bResult = false;

                return false;
            }
        }

        // do the query
        $this->result = $this->oMysql->query ( $sQuery );

        if( $this->oMysql->errno != 0 ) {
            $this->addError( $this->oMysql->error ) ;
            $this->bResult = false ;

            return false;
        }

        $this->iRows    = $this->oMysql->affected_rows  ;
        $this->iLastId  = $this->oMysql->insert_id      ;
        $this->bResult  = true;

        return true;
    }
}
