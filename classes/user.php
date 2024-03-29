<?php 


class Token extends LPDOModel
{

    public static $MAX_TIME = 604800; // 7 days

    public function __construct($pdo)
    {
        parent::__construct("tokens", $pdo);
    }
    public static function id(bool $hash = false)
    {
        $id = rand(1000, 9999) . "_" . time() . "_" . rand(100000000, 999999999);
        return $hash === true ? md5($id) : $id;
    }
    public function verify_token(string $token): bool
    {
        if (!$this->check($token)) {
            $ts = $this->rows(["token" => $token]);
            return (count($ts) === 1 && isset($ts[0]["ip"]) && $ts[0]["ip"] == self::get_ip()) ? true : false;
        }
        return false;
    }
    public function user_id(string $token)
    {
        if (!$this->check($token)) {
            $ts = $this->rows(["token" => $token]);
            return (count($ts) === 1 && isset($ts[0]["ip"]) && $ts[0]["ip"] == self::get_ip()) ? $ts[0]["user_id"] : false;
        }
        return false;
    }
    public function create(int $id)
    {
        $exp = time() + self::$MAX_TIME;
        $token = self::id(true);
        return $this->unique('token', ["token" => $token, "expiration_timestamp" => $exp, "user_id" => (int) $id, "ip" => self::get_ip()]) ? $token : false;
    }
    public function logout(string $token)
    {
        return (bool) $this->delete(["token" => $token]);
    }
    public function check(string $token)
    {
        $data = $this->row(["token" => $token]);
        return ((int) @$data["expiration_timestamp"] < time()) ? $this->logout($token) : false;
    }
    public function logout_expired(): array
    {
        $active = 0;
        $expired = 0;
        $tokens = $this->all();
        foreach ($tokens as $token) {
            if ($this->check((string) @$token["token"])) {
                $expired++;
            } else {
                $active++;
            }
        }
        return ["active" => $active, "expired" => $expired];
    }
    public function active_users(): array
    {
        $active = [];
        $tokens = $this->all();
        foreach ($tokens as $token) {
            if (isset($token["user_id"]) && !in_array($token["user_id"], $active)) {
                $active[] = $token["user_id"];
            }
        }
        return $active;
    }
    public function logout_deleted($user): array
    { // $user - User Class
        $active = $this->active_users();
        $logout = 0;
        foreach ($active as $user_id) {
            if (count($user->rows(["id" => $user_id])) < 1) {
                $this->delete(["user_id" => $user_id]);
                $logout++;
            }
        }
        return ["active" => count($active) - $logout, "logout" => $logout];
    }
    public function tokens_byUserId($id): array
    {
        return $this->rows(["user_id" => $id]);
    }
    public function tokens_byUserName($username, $userClass): array
    {
        $user = $userClass->row(["user" => $username]);
        if (count($user) < 1 || !isset($user["id"])) return [];
        return $this->rows(["user_id" => $user["id"]]);
    }
    public function update_token(string $token): bool
    {
        $exp = time() + self::$MAX_TIME;
        return $this->update(["token" => $token], ["expiration_timestamp" => $exp]);
    }
    public static function get_ip()
    {
        // check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && self::validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // check for IPs passing through proxies
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // check if multiple ips exist in var
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
                $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                foreach ($iplist as $ip) {
                    if (self::validate_ip($ip)) {
                        return $ip;
                    }
                }
            } else {
                if (self::validate_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            }
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED']) && self::validate_ip($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }

        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && self::validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && self::validate_ip($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['HTTP_FORWARDED']) && self::validate_ip($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        }

        // return unreliable ip since all else failed
        return $_SERVER['REMOTE_ADDR'];
    }
    public static function validate_ip($ip)
    {
        if (strtolower($ip) === 'unknown') {
            return false;
        }

        // generate ipv4 network address
        $ip = ip2long($ip);

        // if the ip is set and not equivalent to 255.255.255.255
        if ($ip !== false && $ip !== -1) {
            // make sure to get unsigned long representation of ip
            // due to discrepancies between 32 and 64 bit OSes and
            // signed numbers (ints default to signed in PHP)
            $ip = sprintf('%u', $ip);
            // do private network range checking
            if ($ip >= 0 && $ip <= 50331647) {
                return false;
            }

            if ($ip >= 167772160 && $ip <= 184549375) {
                return false;
            }

            if ($ip >= 2130706432 && $ip <= 2147483647) {
                return false;
            }

            if ($ip >= 2851995648 && $ip <= 2852061183) {
                return false;
            }

            if ($ip >= 2886729728 && $ip <= 2887778303) {
                return false;
            }

            if ($ip >= 3221225984 && $ip <= 3221226239) {
                return false;
            }

            if ($ip >= 3232235520 && $ip <= 3232301055) {
                return false;
            }

            if ($ip >= 4294967040) {
                return false;
            }
        }
        return true;
    }
}
class User extends LPDOModel
{

    public static $max_user_char = 36;
    public static $min_user_char = 3;
    public static $invalid_user_regex = "/[^a-zA-Z0-9_]/";
    public static $max_pass_char = 1024;
    public static $min_pass_char = 4;
    public static $max_name_char = 36;
    public static $min_name_char = 2;
    public static $invalid_name_regex = "/[^a-zA-Z -.]/";

    private $token;

    public function __construct($pdo)
    {
        parent::__construct("users", $pdo);
        $this->token = new Token($pdo);
    }
    public function register(array $form)
    {
        $result = [
            "user" => true,
            "pass" => true,
            "fname" => true,
            "lname" => true,
            "mname" => true,
            "email" => true,
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
        $address = $form["address"] ?? "";
        $phone = $form["phone"] ?? "";

        // Validation

        // User
        if (preg_match(self::$invalid_user_regex, $user)) {
            $result["user"] = "Username format is invalid, use " . self::$invalid_user_regex . " format";
        }

        if (strlen($user) < self::$min_user_char) {
            $result["user"] = "Username must be atleast " . self::$min_user_char . " characters";
        }

        if (strlen($user) > self::$max_user_char) {
            $result["user"] = "Username must not exceed " . self::$max_user_char . " characters";
        }

        if ($this->exists($user)) {
            $result["user"] = "User already exists";
        }

        // Pass
        if (strlen($pass) < self::$min_pass_char) {
            $result["pass"] = "Password must be atleast " . self::$min_pass_char . " characters";
        }

        if (strlen($pass) > self::$max_pass_char) {
            $result["pass"] = "Password must not exceed " . self::$max_pass_char . " characters";
        }

        // Firstname
        if (strlen($fname) < self::$min_name_char) {
            $result["fname"] = "First name must be atleast " . self::$min_name_char . " characters";
        }

        if (strlen($fname) > self::$max_name_char) {
            $result["fname"] = "First name must not exceed " . self::$max_name_char . " characters";
        }

        if (preg_match(self::$invalid_name_regex, $fname)) {
            $result["fname"] = "First name format is invalid, use " . self::$invalid_name_regex . " format";
        }

        // Middlename
        if (strlen($mname) < self::$min_name_char) {
            $result["mname"] = "Middle name must be atleast " . self::$min_name_char . " characters";
        }

        if (strlen($mname) > self::$max_name_char) {
            $result["mname"] = "Middle name must not exceed " . self::$max_name_char . " characters";
        }

        if (preg_match(self::$invalid_name_regex, $mname)) {
            $result["mname"] = "Middle name format is invalid, use " . self::$invalid_name_regex . " format";
        }

        // Lastname
        if (strlen($lname) < self::$min_name_char) {
            $result["lname"] = "Last name must be atleast " . self::$min_name_char . " characters";
        }

        if (strlen($lname) > self::$max_name_char) {
            $result["lname"] = "Last name must not exceed " . self::$max_name_char . " characters";
        }

        if (preg_match(self::$invalid_name_regex, $lname)) {
            $result["lname"] = "Last name format is invalid, use " . self::$invalid_name_regex . " format";
        }

        // Email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result["email"] = "Invalid email format, use user@email.com format";
        }

        switch (strtolower($gender)) {
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



        foreach ($result as $v) {
            if ($v !== true) {
                $errors++;
            }
        }

        return $errors > 0 ? $result : $this->unique("user", [
            "user" => $user,
            "pass" => password_hash($pass, PASSWORD_DEFAULT),
            "fname" => ucwords(strtolower($fname)),
            "lname" => ucwords(strtolower($lname)),
            "mname" => ucwords(strtolower($mname)),
            "email" => $email,
            "gender" => $gender,
            "timestamp" => time(),
            "address" => $address,
            "phone" => $phone,
        ]);
    }
    public function login(string $user, string $pass)
    {
        $data = $this->row(["user" => $user]);
        if (isset($data["pass"]) && is_string($data["pass"])) {
            return password_verify($pass, (string) $data["pass"]) ?
                $this->token->create((int) $data["id"]) :
                false;
        }

        return false;
    }
    public function exists(string $user): bool
    {
        return count($this->rows(["user" => $user])) > 0 ? true : false;
    }
    public function existsById(string $id): bool
    {
        return count($this->rows(["id" => $id])) > 0 ? true : false;
    }
    public function getIdByUser(string $user)
    {
        $rows = $this->rows(["user" => $user]);
        if (count($rows) > 0) {
            return (isset($rows[0]["id"]) ? (int)$rows[0]["id"] : FALSE);
        }
        return FALSE;
    }
}
class PositionApplication extends LPDOModel
{
    public function __construct($pdo)
    {
        parent::__construct("applicants", $pdo);
    }
    private function admins(int $level)
    {
        return $this->rows(["position" => "admin"]);
    }
    private function acceptExemption($user_id, $position, $level)
    {
        return $this->new(["user_id" => $user_id, "position" => $position, "level" => $level, "status" => "approved"]);
    }
    public function apply($user_id, $position, $level)
    {
        switch (strtolower($position)) {
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

        $level = (string)((int) $level);

        if ($position == "admin" && $level == 1 && count($this->admins(1) ?? []) == 0) {
            return $this->acceptExemption($user_id, $position, $level);
        }
        return $this->new(["user_id" => $user_id, "position" => $position, "level" => $level, "status" => "pending"]);
    }
    public function acceptByUserId($id)
    {
        return $this->update(["user_id" => $id], ["status" => "approved"]);
    }
    public function acceptByApplicationId($id)
    {
        return $this->update(["id" => $id], ["status" => "approved"]);
    }
    public function rejectByUserId($id)
    {
        return $this->update(["user_id" => $id], ["status" => "reject"]);
    }
    public function rejectByApplicationId($id)
    {
        return $this->update(["id" => $id], ["status" => "reject"]);
    }
    public function deleteByUserId($id)
    {
        return $this->delete(["user_id" => $id]);
    }
    public function deleteByApplicationId($id)
    {
        return $this->delete(["id" => $id]);
    }
}
class Section extends LPDOModel {
    public function __construct($pdo)
    {
        parent::__construct("section", $pdo);
    }
    public function validateCreateSectionQuery(array $a) : array {
        $result = [];
        $result["name"] = isset($a["name"]) && strlen($a["name"]) >= 2 || strlen($a["name"]) <= 128 ? TRUE : FALSE;
        $result["level"] = isset($a["level"]);
        $result["adviser_id"] = isset($a["adviser_id"]);
        return $result;
    }
    public function validateUpdateSectionQuery(array $a) : bool {
        if(isset($a["name"])){
            if(strlen($a["name"]) < 2 || strlen($a["name"]) > 128) {
                return FALSE;
            }
        }
        return TRUE;
    }
    public function filterCreateSectionQuery(array $a,string $dateFormat = "m d, Y") : array {
        $timestamp = time();
        $year = 31622400; // seconds
        $result = [];
        $result["status"] = "pending";
        $result["name"] = $a["name"] ?? "";
        $result["description"] = $a["description"] ?? "";
        $result["level"] = (int) $a["level"] ?? 0;
        $result["adviser_id"] = (int) $a["adviser_id"] ?? 0;
        $result["timestamp"] = $timestamp;
        $result["expiration_timestamp"] = $timestamp + $year;
        $result["date"] = date($dateFormat,$result["timestamp"]);
        $result["expiration_date"] = date($dateFormat,$result["expiration_timestamp"]);
        return $result;
    }
    public function filterUpdateSectionQuery(array $a) : array {
        $result = [];
        $valid = ["status","name","description","level","adviser_id","timestamp","expiration_timestamp","date","expiration_date"];
        foreach ($valid as $key) {
            if(isset($a[$key])) {
                $result[$key] = $a[$key] ?? "";
            }
        }
        return $result;
    }
    public function createSection(array $a) : bool {
        $validate = $this->validateCreateSectionQuery($a);
        if($validate["name"] === TRUE && $validate["level"] === TRUE && $validate["adviser_id"] === TRUE) {
            $filtered = $this->filterCreateSectionQuery($a);
            return $this->new($filtered) ? TRUE : FALSE;
        }
        return FALSE;
    }
    public function updateSection(int $section_id, array $data) : bool {
        $validate = $this->validateUpdateSectionQuery($data);
        if($validate === TRUE) {
            $filtered = $this->filterUpdateSectionQuery($data);
            return $this->update(["id"=>$section_id],$filtered) ? TRUE : FALSE;
        }
        return FALSE;
    }
    public function updateSectionStatus(int $id,string $status) : bool {

        return $this->updateSection($id,["status"=>$status]);
    }
    public function deleteSection(int $id) : bool {

        return $this->delete(["id"=>$id]);
    }
    public function verifySectionAdviser(int $section_id,int $adviser_id) : bool {

        return count($this->rows(["id"=>$section_id,"adviser_id"=>$adviser_id])) == 1;
    }
}
class Course extends LPDOModel {
    public function __construct($pdo)
    {

        parent::__construct("course", $pdo);
    }
    public function validateCreateCourseQuery(array $a) : array {
        $params = [
            "teacher_id",
            "section_id",
            "name"
        ];
        $result = [];
        foreach ($params as $v) {
            $result[$v] = isset($a[$v]);
        }
        if(strlen($a["name"]) < 2 || strlen($a["name"]) > 128) $result["name"] = FALSE;
        return $result;
    }
    public function validateUpdateCourseQuery(array $a) : bool {
        if(isset($a["name"])){
            if(strlen($a["name"]) < 2 || strlen($a["name"]) > 128) {
                return FALSE;
            }
        }
        return TRUE;
    }
    public function filterCreateCourseQuery(array $a) : array {
        $timestamp = time();
        $year = 31622400; // seconds
        $result = [];
        $result["name"] = $a["name"] ?? "";
        $result["description"] = $a["description"] ?? "";
        $result["status"] = "pending";
        $result["section_id"] = (int) $a["section_id"] ?? 0;
        $result["teacher_id"] = (int) $a["teacher_id"] ?? 0;
        $result["timestamp"] = $timestamp;
        $result["expiration_timestamp"] = $timestamp + $year;
        $result["date"] = date($dateFormat,$result["timestamp"]);
        $result["expiration_date"] = date($dateFormat,$result["expiration_timestamp"]);
        return $result;
    }
    public function filterUpdateCourseQuery(array $a) : array {
        $result = [];
        $valid =  ["name","section_id","teacher_id","expiration_timestamp","date","timestamp","description","status"];
        foreach ($valid as $key) {
            if(isset($a[$key])) {
                $result[$key] = $a[$key] ?? "";
            }
        }
        return $result;
    }
    public function createCourse(array $a) : bool {
        $validate = $this->filterCreateCourseQuery($a);
        if($validate["name"] === TRUE && $validate["teacher_id"] === TRUE && $validate["section_id"] === TRUE) {
            $filtered = $this->filterCreateSectionQuery($a);
            return $this->new($filtered) ? TRUE : FALSE;
        }
        return FALSE;
    }
    public function updateCourse(int $course_id, array $data) : bool {
        $validate = $this->validateUpdateCourseQuery($data);
        if($validate === TRUE) {
            $filtered = $this->filterUpdateCourseQuery($data);
            return $this->update(["id"=>$course_id],$filtered) ? TRUE : FALSE;
        }
        return FALSE;
    }
    public function updateCourseStatus(int $id,string $status) : bool {

        return $this->updateSection($id,["status"=>$status]);
    }
    public function deleteCourse(int $id) : bool {

        return $this->delete(["id"=>$id]);
    }
    public function verifyCourseTeacher(int $course_id,int $teacher_id) : bool {

        return count($this->rows(["id"=>$course_id,"teacher_id"=>$teacher_id])) == 1;
    }
}
class Log
{
    private $dir;
    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, "/") . "/";
        FileSystem::mkdir($this->dir);
    }

    public function visit()
    {
        $kv = new Keyval($this->dir . "visitors.php");
        $log_id = Token::id();
        return $kv->set($log_id, [
            "page" => $_REQUEST["p"] ?? NULL,
            "ip" => Token::get_ip(),
            "uri" => $_SERVER["REQUEST_URI"] ?? '/',
            "timestamp" => time(),
            "date" => date("M d, Y h:i:s A"),
        ]);
    }
    public function visitors()
    {
        $kv = new Keyval($this->dir . "visitors.php");
        $kv->all();
    }
}
class Admin
{
    private $token;
    private $user;
    private $application;

    public function __construct($pdo)
    {   
        $this->pdo = $pdo;
        $this->token = new Token($pdo);
        $this->user = new User($pdo);
        $this->application = new PositionApplication($pdo);
    }
    public function isAdmin(string $token, int $level)
    {
        $user_id = $this->token->user_id($token);
        if ($user_id !== FALSE) {
            if ($this->user->existsById($user_id)) {
                $rows = $this->application->rows(["postition" => "admin", "status" => "approved"], ["columns" => "level,user_id"]);
                $pos =  count($rows) > 0 ? $rows : FALSE;
                if (is_array($pos)) {
                    foreach ($pos as $ls) {
                        $l = $ls["level"] ?? NULL;
                        if ($l !== NULL) {
                            $l = (int) $l;
                            if ($l === $level || ($l <= $level && $level > 0)) {
                                return TRUE;
                            }
                        }
                    }
                }
            }
        }
        return FALSE;
    }
    public function isAdminById(int $id,int $level = 1)
    {
        return count($this->application->rows(["postition" => "admin", "status" => "approved","user_id" => $id,"level" => $level])) > 0 ? TRUE : FALSE;
    }
    public function deleteAllApplications($token)
    {
        if ($this->isAdmin($token, 1)) {
            return $this->application->deleteAll();
        }
        return FALSE;
    }
    public function deleteAllTokens($token)
    {
        if ($this->isAdmin($token, 1)) {
            return $this->token->deleteAll();
        }
        return FALSE;
    }
    public function deleteAllUsers($token)
    {
        if ($this->isAdmin($token, 1)) {
            return $this->user->deleteAll();
        }
        return FALSE;
    }
    public function resetdb($token,$config)
    {
        if ($this->isAdmin($token, 1)) {
            $this->pdo->deleteAllTables();
            return $pdo->setupSchema($config["pdo"]["schema"]);
        }
        return FALSE;
    }
}
class Teacher
{   
    private $valid = false;
    private $teacher_id = 0;
    private $application;
    private $token;
    private $admin;
    private $section;
    private $course;
    public function __construct($pdo)
    {
        $this->application = new PositionApplication($pdo);
        $this->token = new Token($pdo);
        $this->admin = new Admin($pdo);
        $this->section = new Section($pdo);
        $this->course = new Course($pdo);
    }
    public function validByToken(string $token) : bool {
        $user_id = $this->token->user_id($token);
        $this->valid = ($user_id === FALSE) ? FALSE : $this->validById((int) $user_id);
        return $this->valid;
    }
    public function validById(int $id) : bool {
        $this->valid = count($this->$application->rows(["user_id"=>$id,"position"=>"teacher","status"=>"approved"])) > 0 ? true : false;
        return $this->valid;
    }
    public function validByAdminToken(string $token) : bool {
        $this->valid = $this->admin->isAdmin($token,1);
        return $this->valid;
    }
    public function validByAdminId(int $id) : bool {
        $this->valid = $this->admin->isAdminById($id,1);
        return $this->valid;
    }
    public function createSection(array $a) : array {
        return $this->valid === TRUE ? $this->section->createSection($a) : FALSE;
    }
    public function createCourse(array $a) : array {
        return $this->valid === TRUE ? $this->course->createCourse($a) : FALSE;
    }
}
class Staff 
{
    public function __construct($class)
    {
        
    }
}
class Student
{
    public function __construct($class)
    {
        
    }
}