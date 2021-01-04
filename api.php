<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

date_default_timezone_set("Asia/Manila");

$config = [
    "pdo" => [
        "use" => true,
        "model" => true,
        "models" => [
            "users",
            "class",
            "staff",
        ],
        "user" => "id15760282_bpnhs",
        "pass" => "liSKrf!rNtlPx]F4",
        "host" => "localhost",
        "db" => "id15760282_brookespoint_nhs",
        "schema" => [
            "users" => ["id", "fname", "mname", "lname", "address", "phone", "email", "user", "pass", "gender", "timestamp", "form"],
            "tokens" => ["id", "token", "user_id", "ip", "expiration_timestamp"],
            "applicants" => ["id", "user_id", "position", "level", "status"],
            "class" => ["id", "class_name", "creator_id", "SY_start", "SY_end"],
            "class_session" => ["id", "class_id", "user_id"],
            "announcements" => ["id", "title", "expiration_timestamp", "content", "date", "author_id"],
            "class_forum_posts" => ["id", "title", "content", "date", "class_id", "user_id"],
            "class_forum_comments" => ["id", "comment", "date", "forum_post_id", "user_id"],
            "articles" => ["id", "author_id", "title", "content", "date"],
            "article_comment" => ["id", "article_id", "author_id", "comment", "date"],
        ],
        "schema_method" => "normal",
    ],
];


require_once __DIR__."/classes/core.php";
require_once __DIR__."/classes/user.php";

$pdo = new LPDO($config["pdo"]);
$user = new User($pdo);
$token = new Token($pdo);
$application = new PositionApplication($pdo);
$admin = new Admin($pdo);
$log = new Log(__DIR__ . "/logs");
$log->visit();


function getPositions($q){
    global $token, $user,$application;
    $res = $token->verify_token($q['token']);
    if($res === TRUE){
        $user_id = $token->column('user_id',["token"=>$q['token']]);
        if($user_id != NULL){
            $user_id = (int) $user_id;
            $raw = $application->rows(['user_id'=>$user_id]);
            $approved = [];
            $pending = [];
            $rejected = [];
            $c = 0;
            foreach ($raw as $v) {
                $form = [
                    "id" => (int) $v["id"] ?? 0,
                    "level" => (int)$v["level"] ?? 0,
                ];
                $position = $v["position"] ?? "";
                $status = strtolower($v["status"]) ?? "";
                switch ($status) {
                    case 'approved':
                        $approved[$position][] = $form; 
                        break;
                    case 'pending':
                        $pending[$position][] = $form; 
                        break;
                    default:
                        $rejected[$position][] = $form; 
                        break;
                }
                $c++;
            }
            return [
                "approved_count" => count($approved),
                "pending_count" => count($pending),
                "rejected_count" => count($rejected),
                "approved" => $approved,
                "pending" => $pending,
                "rejected" => $rejected
            ];
        }
    }
    return FALSE;
}

// ADMIN
Query::get('request', 'p:admin token:!r r:!r', function ($q) {
    global $admin,$config;
    $s = FALSE;

    switch ($q['r']) {
        case 'resetdb':
            $s = $admin->resetdb($q["token"],$config);
            break;
        case 'is_admin':
            $s = $admin->isAdmin($q["token"]);
            break;
        case 'delete_all_applications':
            $s = $admin->deleteAllApplications($q["token"]);
            break;
        case 'delete_all_tokens':
            $s = $admin->deleteAllTokens($q["token"]);
            break;
        case 'delete_all_users':
            $s = $admin->deleteAllUsers($q["token"]);
            break;
        default:
            break;
    }

    Resolve::json(gettype($s) == "boolean" ? ["status"=>$s] : (array) $s);
});


// CRON JOBS

Query::get('request', 'p:cron r:logout_expired', function ($q) {
    global $token;
    Resolve::json($token->logout_expired());
});

Query::get('request', 'p:cron r:logout_deleted', function ($q) {
    global $user, $token;
    Resolve::json($token->logout_deleted($user));
});

// REQUESTS

Query::get('request', 'p:login user:!r pass:!r', function ($q) {
    global $user;
    $log = $user->login(@$q["user"], @$q["pass"]);
    Resolve::json($log !== false && is_string($log) ? ["token" => $log] : ["error" => "Invalid credential"]);
});

Query::get('request', 'p:register user:!r pass:!r fname:!r mname:!r lname:!r gender:!r email:!r position level phone:!r address:!r', function ($q) {
    global $user, $application;
    $reg = $user->register((array) $q);
    if ($reg == true) {
        $user_id = $user->getIdByUser($q['user']);
        if ($user_id != FALSE) {
            $application->apply($user_id, (string)@$q['position'], (int)@$q['level']);
        }
    }
    Resolve::json($reg === true ? ['status' => true] : ['errors' => $reg]);
});

Query::get('request', "p:account_info token:!r", function ($q) {
    global $user, $token;

    $data = [];

    $log = $token->verify_token($q["token"]);

    if ($log !== false) {
        $t = $q["token"];
        $user_id = (int) $token->column('user_id', ["token" => $t]);
        $data = $user->row(["id" => $user_id]);
        unset($data["pass"]);
        $data["id"] = (int) $data["id"];
        $data["timestamp"] = (int) $data["timestamp"];
        $token->update_token($t);
        $data["postitions"] = getPositions($q) ?? [];
        $data["token"] = $q["token"];
    }

    Resolve::json($log !== false ? ["data" => $data] : ["error" => "Invalid token"]);
});

Query::get('request', 'p:logout token:!r', function ($q) {
    global $token;
    Resolve::json(["status" => $token->verify_token($q['token']) ? $token->logout($q['token']) : false]);
});

Query::get('request', 'p:verify_token token:!r', function ($q) {
    global $token, $user;
    $res = $token->verify_token($q['token']);
    $user_id = (int) $token->column('user_id', ["token" => $q['token']]);
    if (count($user->rows(["id" => $user_id])) < 1) {
        $res = FALSE;
        $token->delete(["user_id" => $user_id]);
    }
    Resolve::json(["status" => $res]);
});

Query::get('request', 'p:user id:!r', function ($q) {
    global $user;
    $u = $user->row(["id" => $q["id"]]);
    if (count($u) > 0) {
        $u["id"] = (int) $u["id"];
        unset($u["pass"]);
        unset($u["timestamp"]);
        unset($u["phone"]);
        unset($u["email"]);
        unset($u["address"]);
        unset($u["level"]);
        Resolve::json(["data" => $u]);
    }
    Resolve::json(["error" => "Invalid user id"]);
});

Query::get('request', 'p:positions token:!r', function($q){
    return FALSE;
    Resolve::json(getPositions($q) ?? ["error" => "Invalid token"]);
});

// TESTING ONLY (REMOVE ON DEPLOYMENT else you are fu**ed up)
Query::get('request', 'p:resetdb', function ($q) {
    global $pdo, $config;
    $pdo->deleteAllTables();
    $pdo->setupSchema($config["pdo"]["schema"]);
    Resolve::json(TRUE);
});

Resolve::json(["error" => "Invalid request"]);