<?php

namespace Bpnhs;

class User extends \Linker\PDO\Model {

    public static $max_user_char = 36;
    public static $min_user_char = 3;
    public static $invalid_user_regex = "/[^a-zA-Z0-9_]/";
    public static $max_pass_char = 1024;
    public static $min_pass_char = 4;
    public static $max_name_char = 36;
    public static $min_name_char = 2;
    public static $invalid_name_regex = "/[^a-zA-Z -.]/";

    private \Bpnhs\Tokens $token;

    public function __construct(\Linker\Database\PDO $pdo){
        parent::__construct("users",$pdo);
        $this->token = new \Bpnhs\Tokens($pdo);
    }
    public function register(array $form) : mixed {
        $result = [
            "user" => TRUE,
            "pass" => TRUE,
            "fname" => TRUE,
            "lname" => TRUE,
            "mname" => TRUE,
            "email" => TRUE,
        ];
        $errors = 0;

        $user = $form["user"] ?? "";
        $pass = $form["pass"] ?? "";
        $fname = $form["fname"] ?? "";
        $lname = $form["lname"] ?? "";
        $mname = $form["mname"] ?? "";
        $gender = $form["gender"] ?? "other";
        $email = $form["email"] ?? "";
        $gender = $form["gender"] ?? "";
        $position = $form["position"] ?? "";
        $level = (int) ($form["level"] ?? "");
        
        // Validation

        // User
        if(preg_match(self::$invalid_user_regex, $user)) $result["user"] = "Username format is invalid, use ".self::$invalid_user_regex." format";
        if(strlen($user) < self::$min_user_char) $result["user"] = "Username must be atleast ".self::$min_user_char." characters";
        if(strlen($user) > self::$max_user_char) $result["user"] = "Username must not exceed ".self::$max_user_char." characters";
        if($this->exists($user)) $result["user"] = "User already exists";
        
        // Pass
        if(strlen($pass) < self::$min_pass_char) $result["pass"] = "Password must be atleast ".self::$min_pass_char." characters";
        if(strlen($pass) > self::$max_pass_char) $result["pass"] = "Password must not exceed ".self::$max_pass_char." characters";

        // Firstname
        if(strlen($fname) < self::$min_name_char) $result["fname"] = "First name must be atleast ".self::$min_name_char." characters";
        if(strlen($fname) > self::$max_name_char) $result["fname"] = "First name must not exceed ".self::$max_name_char." characters"; 
        if(preg_match(self::$invalid_name_regex, $fname)) $result["fname"] = "First name format is invalid, use ".self::$invalid_name_regex." format";

        // Middlename
        if(strlen($mname) < self::$min_name_char) $result["mname"] = "Middle name must be atleast ".self::$min_name_char." characters";
        if(strlen($mname) > self::$max_name_char) $result["mname"] = "Middle name must not exceed ".self::$max_name_char." characters"; 
        if(preg_match(self::$invalid_name_regex, $mname)) $result["mname"] = "Middle name format is invalid, use ".self::$invalid_name_regex." format";

        // Lastname
        if(strlen($lname) < self::$min_name_char) $result["lname"] = "Last name must be atleast ".self::$min_name_char." characters";
        if(strlen($lname) > self::$max_name_char) $result["lname"] = "Last name must not exceed ".self::$max_name_char." characters"; 
        if(preg_match(self::$invalid_name_regex, $lname)) $result["lname"] = "Last name format is invalid, use ".self::$invalid_name_regex." format";

        // Email 
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $result["email"] = "Invalid email format, use user@email.com format"; 

        switch(strtolower($gender)){
            case 'm':
                $gender = "male";
                break;
            case 'male':
                $gender = "male";
                break;
            case 'f':
                $gender = "female";
                break;
            case 'female':
                $gender = "female";
                break;
            default:
                $gender = "other";
                break;
        }

        switch(strtolower($position)){
            case 'teacher':
                $position = "teacher";
                break;
            case 'staff':
                $position = "staff";
                break;
            case 'admin':
                $position = "admin";
                break;
            default:
                $position = "student";
                break;
        }

        foreach($result as $k => $v) if($v !== TRUE) $errors++;

        return $errors > 0 ? $result : $this->unique("user",[
            "user"=>$user,
            "pass"=>password_hash($pass, PASSWORD_DEFAULT),
            "fname"=>$fname,
            "lname"=>$lname,
            "mname"=>$mname,
            "email"=>$email,
            "gender"=>$gender,
            "timestamp"=>time(),
            "position"=>$position,
            "level"=>$level
        ]);
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