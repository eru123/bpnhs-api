<?php

use \Bpnhs\User;

$user = new User($this->pdo);


// var_dump($user->register("lighty262","pass"));
var_dump($user->login("lighty262","pass"));