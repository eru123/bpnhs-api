<?php

use \Bpnhs\Tokens;
use \Bpnhs\Resolve;

$tokens = new Tokens($this->pdo);

$q = $this->query->get('post',"token:!r"); 

if($q!== FALSE) $token = $q["token"]; else $token = "";

Resolve::json(["valid"=>$tokens->verify_token($token)]);
