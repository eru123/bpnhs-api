<?php

use \Bpnhs\Resolve;


$users = new \Bpnhs\User($this->pdo);

$q = $this->query->get('request',"user:!r pass:!r"); 

$res = ["error"=>"Invalid credentials"];

if($q!== FALSE) {
    $log = $users->login(@$q["user"],@$q["pass"]);
    if($log !== FALSE && is_string($log)) {
        unset($res["error"]);
        $res["token"] = $log;
    }
} else $res = ["error" => "Invalid request"];

Resolve::json($res);
