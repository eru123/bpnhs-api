<?php

use \Bpnhs\Tokens;
use \Bpnhs\Resolve;

$tokens = new Tokens($this->pdo);

$result = $tokens->logout_expired();

Resolve::json($result);
