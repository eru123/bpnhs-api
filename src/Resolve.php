<?php

namespace Bpnhs;

class Resolve {
    public static function json(array $a) : void {
		header('Content-Type: application/json');
		echo json_encode($a);
    }
}