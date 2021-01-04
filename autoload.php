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

$teacher = new Teacher([
    "application" => $application,
    "user" => $user,
    "pdo" => $pdo,
    "token" => $token,
    "log" => $log
]);
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