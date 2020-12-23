<?php

use \Bpnhs\Tokens;
use \Bpnhs\Resolve;

$tokens = new Tokens($this->pdo);

$q = $this->query->get('request',"token:!r"); 

if($q!== FALSE) $token = $q["token"]; else $token = "";


$status = $tokens->verify_token($token) ? $tokens->logout($token) : FALSE;

Resolve::json(["status"=>$status]);
