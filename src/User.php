<?php

namespace Bpnhs;

class User extends \Linker\PDO\Model {

    public static $ERR_USER_EXISTS = "USER_EUE0";
    public static $ERR_USER_MIN_CHAR = "USER_EUMC0";
    public static $ERR_USER_MAX_CHAR = "USER_EUMC1";
    public static $ERR_USER_FORMAT = "USER_EUF0";
    public static $ERR_PASS_MIN_CHAR = "USER_UPMC0";
    public static $ERR_PASS_MAX_CAHR = "USER_UPMC1";

    public static $MAX_USER_CHAR = 36;
    public static $MIN_USER_CHAR = 3;
    public static $INVALID_USER_REGEX = "/[^a-zA-Z0-9_]/";
    public static $MAX_PASS_CHAR = 1024;
    public static $MIN_PASS_CHAR = 4;

    private \Bpnhs\Tokens $token;

    public function __construct(\Linker\Database\PDO $pdo){
        parent::__construct("users",$pdo);
        $this->token = new \Bpnhs\Tokens($pdo);
    }
    public function register(string $user, string $pass) : mixed {
        $errors = []; 

        if($this->exists($user)) $errors[self::$ERR_USER_EXISTS] = "User already exists";
        if(strlen($user) < self::$MIN_USER_CHAR) $errors[self::$ERR_USER_MIN_CHAR] = "Username must be atleast ".self::$MIN_USER_CHAR." characters";
        if(strlen($user) > self::$MAX_USER_CHAR) $errors[self::$ERR_USER_MAX_CHAR] = "Username must not exceed ".self::$MAX_USER_CHAR." characters";
        if(preg_match(self::$INVALID_USER_REGEX, $user)) $errors[self::$ERR_USER_FORMAT] = "Username format is invalid, use ".self::$INVALID_USER_REGEX." format";
        if(strlen($pass) < self::$MIN_PASS_CHAR) $errors[self::$ERR_PASS_MIN_CHAR] = "Password must be atleast ".self::$MIN_PASS_CHAR." characters";
        if(strlen($pass) > self::$MAX_PASS_CHAR) $errors[self::$ERR_PASS_MAX_CHAR] = "Password must not exceed ".self::$MAX_PASS_CHAR." characters";

        return count($errors) > 0 ? $errors : $this->unique("user",["user"=>$user,"pass"=>password_hash($pass, PASSWORD_DEFAULT)]);
    }
    public function login(string $user, string $pass) : mixed {
        $data = $this->row(["user"=>$user]);
        return password_verify($pass, (string)$data["pass"])  ? 
            $this->token->create((int) $data["id"]) : 
            FALSE;
    }
    public function exists(string $user) : bool {
        return count($this->rows(["user" => $user])) > 0 ? TRUE : FALSE;
    }
}