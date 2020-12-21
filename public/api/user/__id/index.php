<?php

use \Bpnhs\Resolve;
use \Bpnhs\User;

$users = new User($this->pdo);

$res = ["error"=>"Invalid user"];

$user_id = (int)$this->route->params["id"];

$user = $users->row(["id"=>$user_id]);

if(count($user) > 0){
    $user["id"] = (int)$user["id"];

    unset($user["pass"]);
    unset($user["timestamp"]);
    unset($user["phone"]);
    unset($user["email"]);
    unset($user["address"]);
} else $user = $res;

Resolve::json((array)$user);