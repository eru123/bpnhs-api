<?php

use \Bpnhs\Resolve;
use \Bpnhs\User;
use \Bpnhs\Tokens;


$users = new User($this->pdo);

$q = $this->query->get('request',"user:!r"); 

$res = ["status" => false];

if($q!== FALSE) {
   if(count($users->rows(['user'=>@$q["user"]])) > 0){
   	$res["status"] = true;
   }
} else $res = ["error" => "Invalid request"];

Resolve::json($res);
