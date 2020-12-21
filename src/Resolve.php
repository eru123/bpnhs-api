<?php

namespace Bpnhs;

class Resolve {
    public static $cors = "*/*";

    public static function json(array $a) : void {
        header("Access-Control-Allow-Origin: ".self::$cors."");
		header('Content-Type: application/json');
		echo json_encode($a);
    }
}