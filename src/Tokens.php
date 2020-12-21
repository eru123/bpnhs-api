<?php

namespace Bpnhs;

class Tokens extends \Linker\PDO\Model {

    public static $MAX_TIME = 604800; // 7 days

    public function __construct(\Linker\Database\PDO $pdo){
        parent::__construct("tokens",$pdo);
    }
    public function verify_token(string $token) : bool {
        $ts = $this->rows(["token"=>$token]);
        return (count($ts) === 1 && isset($ts[0]["ip"]) && $ts[0]["ip"] == self::get_ip()) ? TRUE:FALSE;
    }
    public function create(int $id) : mixed {
        $exp = time() + self::$MAX_TIME;
        $gen = time()."_".rand()."_".rand()."_".$exp;
        $token = md5($gen);
        return $this->unique('token',["token"=>$token,"expiration_timestamp"=>$exp,"user_id"=>(int)$id,"ip"=>self::get_ip()]) ? $token : FALSE;
    }
    public function logout(string $token){
        return (bool)$this->delete(["token"=>$token]);
    }
    public function check(string $token){
        $data = $this->row(["token"=>$token]);
        return ((int)$data["expiration_timestamp"] > time()) ? $this->logout($token) : FALSE;
    }
    public static function get_ip() {
		// check for shared internet/ISP IP
		if (!empty($_SERVER['HTTP_CLIENT_IP']) && valsidate_ip($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		}

		// check for IPs passing through proxies
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// check if multiple ips exist in var
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
				$iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				foreach ($iplist as $ip) {
					if (self::validate_ip($ip)) {
						return $ip;
					}
				}
			} else {
				if (self::validate_ip($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					return $_SERVER['HTTP_X_FORWARDED_FOR'];
				}

			}
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED']) && self::validate_ip($_SERVER['HTTP_X_FORWARDED'])) {
			return $_SERVER['HTTP_X_FORWARDED'];
		}

		if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && self::validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
			return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		}

		if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && self::validate_ip($_SERVER['HTTP_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_FORWARDED_FOR'];
		}

		if (!empty($_SERVER['HTTP_FORWARDED']) && self::validate_ip($_SERVER['HTTP_FORWARDED'])) {
			return $_SERVER['HTTP_FORWARDED'];
		}

		// return unreliable ip since all else failed
		return $_SERVER['REMOTE_ADDR'];
	}
	public static function validate_ip($ip) {
		if (strtolower($ip) === 'unknown') {
			return false;
		}

		// generate ipv4 network address
		$ip = ip2long($ip);

		// if the ip is set and not equivalent to 255.255.255.255
		if ($ip !== false && $ip !== -1) {
			// make sure to get unsigned long representation of ip
			// due to discrepancies between 32 and 64 bit OSes and
			// signed numbers (ints default to signed in PHP)
			$ip = sprintf('%u', $ip);
			// do private network range checking
			if ($ip >= 0 && $ip <= 50331647) {
				return false;
			}

			if ($ip >= 167772160 && $ip <= 184549375) {
				return false;
			}

			if ($ip >= 2130706432 && $ip <= 2147483647) {
				return false;
			}

			if ($ip >= 2851995648 && $ip <= 2852061183) {
				return false;
			}

			if ($ip >= 2886729728 && $ip <= 2887778303) {
				return false;
			}

			if ($ip >= 3221225984 && $ip <= 3221226239) {
				return false;
			}

			if ($ip >= 3232235520 && $ip <= 3232301055) {
				return false;
			}

			if ($ip >= 4294967040) {
				return false;
			}

		}
		return true;
	}
}