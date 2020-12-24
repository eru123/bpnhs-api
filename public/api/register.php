<?php

use \Bpnhs\Resolve;


$users = new \Bpnhs\User($this->pdo);

$q = $this->query->get('request',"user:!r pass:!r fname:!r mname:!r lname:!r gender:!r email:!r position level phone:!r address:!r"); 

$res = ["status"=>FALSE];

if($q!== FALSE) {
    $reg = $users->register((array)$q);
    if($reg !== TRUE) $res["errors"] = $reg; else $res["status"] = TRUE;
} else $res = ["error" => "Invalid request"];

$res["debug"] = $_REQUEST;
Resolve::json($res);
