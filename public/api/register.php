<?php

use \Bpnhs\Resolve;


$users = new \Bpnhs\User($this->pdo);

$q = $this->query->get('post',"user:!r pass:!r fname:!r mname:!r lname:!r gender:!r email:!r"); 

$res = ["status"=>FALSE];

if($q!== FALSE) {
    $reg = $users->register((array)$q);
    if($reg !== TRUE) $res["errors"] = $reg; else $res["status"] = TRUE;
} else $res = ["error" => "Invalid request"];

Resolve::json($res);
