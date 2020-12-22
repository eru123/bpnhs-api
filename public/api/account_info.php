<?php

use \Bpnhs\Resolve;
use \Bpnhs\User;
use \Bpnhs\Tokens;


$users = new User($this->pdo);
$tokens = new Tokens($this->pdo);

$q = $this->query->get('request',"token:!r"); 

$res = ["error"=>"Invalid token"];

if($q!== FALSE) {
    $log = $tokens->verify_token(@$q["token"]);
    if($log !== FALSE) {
        $token = @$q["token"];
        $user_id = (int)$tokens->column('user_id',["token"=>$token]);
        unset($res["error"]);
        $data = $users->row(["id"=>$user_id]);
        unset($data["pass"]);
        $data["id"] = (int)$data["id"];
        $data["level"] = (int)$data["level"];
        $data["timestamp"] = (int)$data["timestamp"];
        $res["data"] = $data;
        $tokens->update_token($token);
    }
} else $res = ["error" => "Invalid request"];

Resolve::json($res);
