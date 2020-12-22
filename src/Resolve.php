<?php

namespace Bpnhs;

class Resolve {
    public static function json(array $a) : void {
        header("Access-Control-Allow-Origin: *");
		header('Content-Type: application/json');
		echo json_encode($a);
    }
}