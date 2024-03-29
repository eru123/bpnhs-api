<?php 

class FileSystem
{
    public static function mkdir($dir, $m = 0700)
    {
        if (is_array($dir)) {
            foreach ($dir as $k => $v) {
                $dir[$k] = self::mkdir($v);
            }

            return $dir;
        } else {
            if (is_dir($dir)) {
                return false;
            }

            if (mkdir($dir, $m)) {
                return true;
            }
        }
    }
    public static function scandir($dir)
    {
        $path = rtrim($dir, '/') . '/';
        if (!is_dir($dir)) {
            return array();
        }

        $dir = scandir($dir);
        $res = array();
        $c = 0;
        for ($i = 2; $i < (count($dir)); $i++) {
            $res[$c] = $path . $dir[$i];
            $c++;
        }
        return $res;
    }
    public static function scandirTree(string $dir)
    {
        $base = self::scandir($dir);
        $tmp = $base;
        foreach ($base as $v) {
            if (is_dir($v)) {
                $a = self::scandirTree($v);
                foreach ($a as $vc) {
                    $tmp[] = $vc;
                }
            }
        }
        return $tmp;
    }
    public static function tree(string $dir)
    {
        $base = self::scandir($dir);
        $tmp = $base;
        foreach ($base as $k => $v) {
            if (is_dir($v)) {
                $tmp[$k] = [
                    "folder" => $v,
                    "childs" => self::tree($v),
                ];
            }
        }
        return $tmp;
    }
    public static function index(string $dir)
    {
        $base = self::scandir($dir);
        $tmp = $base;
        foreach ($base as $k => $v) {
            if (is_dir($v)) {
                $tmp[$k] = [
                    "type" => "folder",
                    "path" => $v,
                    "name" => basename($v),
                    "childs" => self::index($v),
                ];
            } elseif (is_file($v)) {
                $tmp[$k] = [
                    "type" => "file",
                    "path" => $v,
                    "name" => pathinfo(basename($v), PATHINFO_FILENAME),
                    "ext" => pathinfo(basename($v), PATHINFO_EXTENSION),
                    "size" => filesize($v),
                ];
            }
        }
        return $tmp;
    }
    public static function del($p)
    {
        if (is_array($p) && count($p) > 0) {
            foreach ($p as $key => $value) {
                $p[$key] = self::del($value);
            }

            return $p;
        } else {
            if (is_dir($p)) {
                $dir = self::scandir($p);
                foreach ($dir as $key => $value) {
                    self::del($value);
                }

                if (rmdir($p)) {
                    return true;
                }
            } elseif (file_exists($p)) {
                if (unlink($p)) {
                    return true;
                }
            }
        }
        return false;
    }
    public static function write(string $f, string $data = '', string $m = 'a'): bool
    {
        $m = trim(strtolower($m));
        if ($m == 'a') {
            if (file_exists($f)) {
                $handle = fopen($f, "a");
                $res = fwrite($handle, $data);
                fclose($handle);
                return $res;
            } else {
                return self::write($f, $data, 'w');
            }
        } elseif ($m == 'w') {
            if (!file_exists($f)) {
                touch($f);
            }

            $handle = fopen($f, "w");
            $res = fwrite($handle, $data);
            fclose($handle);
            return $res;
        } else {
            return self::write($f, $data, 'a');
        }
    }
    public static function fwrite(string $f, string $data = ''): bool
    {
        return self::write($f, $data, 'w');
    }
    public static function fappend(string $f, string $data = ''): bool
    {
        return self::write($f, $data, 'a');
    }
    public static function mime_content_type(string $filename)
    {
        $realpath = realpath($filename);

        if (!is_file($realpath)) {
            return false;
        }

        if (
            $realpath
            && function_exists('finfo_file')
            && function_exists('finfo_open')
            && defined('FILEINFO_MIME_TYPE')
        ) {
            return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $realpath);
        } elseif (function_exists('mime_content_type')) {
            return mime_content_type($realpath);
        }

        return false;
    }
    public static function copy(string $from, string $to, bool $debug = false)
    {
        $to = rtrim($to, "/") . "/";
        self::mkdir($to);
        $top = $to . basename($from);

        if (is_file($from)) {
            if ($debug === true) {
                echo "Copying $top... ";
            }

            $res = copy($from, $top);
            if ($debug === true) {
                echo ($res ? 'OK' : 'FAILED') . PHP_EOL;
            }

            return $res;
        } elseif (is_dir($from)) {
            if ($debug === true) {
                echo "Copying $top... ";
            }

            $res = self::mkdir($top);
            if ($debug === true) {
                echo ($res ? 'OK' : 'FAILED') . PHP_EOL;
            }

            foreach (self::scandir($from) as $frd) {
                self::copy($frd, $top, $debug);
            }

            return true;
        } else {
            if ($debug === true) {
                echo "Copying $from ... INVALID";
            }
        }
        return false;
    }
}
class Crypt
{
    public static function blow(string $str, string $key): string
    {
        for ($i = 0; $i < strlen($str); $i++) {
            $str[$i] = $str[$i] ^ $key[$i % strlen($key)];
        }
        return $str;
    }
    public static function encode(string $str, string $key): string
    {
        $hash = self::blow($str, $key);
        return base64_encode($hash);
    }
    public static function decode(string $encoded, string $key): string
    {
        $hash = base64_decode($encoded);
        return self::blow($hash, $key);
    }
}
class Compression
{
    public static function lzw_decompress($Ta)
    {

        $ec = 256;
        $Ua = 8;
        $nb = [];
        $Ug = 0;
        $Vg = 0;
        $tj = "";
        for ($s = 0; $s < strlen($Ta); $s++) {
            $Ug = ($Ug << 8) + ord($Ta[$s]);
            $Vg += 8;
            if ($Vg >= $Ua) {
                $Vg -= $Ua;
                $nb[] = $Ug >> $Vg;
                $Ug &= (1 << $Vg) - 1;
                $ec++;
                if ($ec >> $Ua) {
                    $Ua++;
                }
            }
        }

        $dc = range("\0", "\xFF");

        $H = "";

        foreach ($nb as $s => $mb) {
            $tc = $dc[$mb];
            if (!isset($tc)) {
                $tc = $tj . $tj[0];
            }

            $H .= $tc;
            if ($s) {
                $dc[] = $tj . $tc[0];
            }

            $tj = $tc;
        }

        return $H;
    }
    public static function lzw_compress($string)
    {

        // compression
        $dictionary = array_flip(range("\0", "\xFF"));
        $word = "";
        $codes = array();
        for ($i = 0; $i <= strlen($string); $i++) {
            $x = @$string[$i];
            if (strlen($x) && isset($dictionary[$word . $x])) {
                $word .= $x;
            } elseif ($i) {
                $codes[] = $dictionary[$word];
                $dictionary[$word . $x] = count($dictionary);
                $word = $x;
            }
        }
        // convert codes to binary string
        $dictionary_count = 256;
        $bits = 8;
        $return = "";
        $rest = 0;
        $rest_length = 0;
        foreach ($codes as $code) {
            $rest = ($rest << $bits) + $code;
            $rest_length += $bits;
            $dictionary_count++;
            if ($dictionary_count >> $bits) {
                $bits++;
            }
            while ($rest_length > 7) {
                $rest_length -= 8;
                $return .= chr($rest >> $rest_length);
                $rest &= (1 << $rest_length) - 1;
            }
        }
        return $return . ($rest_length ? chr($rest << (8 - $rest_length)) : "");
    }
}
class Keyval
{
    private $file;
    public function __construct(?string $file = null)
    {
        $this->file($file);
    }
    public function file(?string $file = null)
    {
        if ($file) {
            $this->file = $file;
            $this->file_init();
        }
    }
    public function clear()
    {
        FileSystem::fwrite($this->file, "<?php\n\$data = [];\n");
    }
    private function file_init()
    {
        if (!file_exists($this->file)) {
            $this->clear();
        }
    }
    private static function json_encode($arr)
    {
        $encoded = json_encode($arr);
        $encoded = str_replace('\\\'', '\'', $encoded);
        $encoded = str_replace('\'', '\\\'', $encoded);
        return $encoded;
    }
    private static function filter_key(string $key)
    {
        $key = str_replace('\\\'', '\'', $key);
        $key = str_replace('\'', '\\\'', $key);
        return $key;
    }
    public function set(string $key, $val)
    {
        $key = self::filter_key($key);
        switch (gettype($val)) {
            case 'string':
                $data = "\"$val\"";
                break;
            case 'object':
                $nObj = self::json_encode($val);
                $data = "json_decode('$nObj')";
                break;
            case 'array':
                $nObj = self::json_encode($val);
                $data = "json_decode('$nObj',true)";
                break;
            case 'boolean':
                $data = $val ? "true" : "false";
                break;
            case 'NULL':
                $data = "NULL";
                break;
            case 'null':
                $data = "NULL";
                break;
            default:
                $data = $val;
                break;
        }
        FileSystem::fappend($this->file, "\$data['$key'] = $data;\n");
    }
    public function get(string $key, $default = null)
    {
        $get = function (string $file, string $key, $default) {
            include $file;
            return isset($data) && isset($data[$key]) ? $data[$key] : $default;
        };
        return $get($this->file, $key, $default);
    }
    public function all($default = null)
    {
        $get = function (string $file, $default) {
            include $file;
            return isset($data) ? $data : $default;
        };
        return $get($this->file, $default);
    }
    public function del(string $key)
    {
        $key = self::filter_key($key);
        FileSystem::fappend($this->file, "unset(\$data['$key']);\n");
    }
}
class LPDO
{
    protected $pdo = null;
    protected $tb = null;
    protected $schema = null;

    public function __construct($config = null)
    {
        if (is_array($config)) {
            $this->connectByConfig($config);
        } elseif (is_object($config)) {
            $this->connectByApp($config);
        }

        if (isset($config["schema"]) && is_array($config["schema"]) && count($config["schema"])) {
            $schema = isset($config["schema_method"]) ? $config["schema_method"] : $schema = "dynamic";
            switch ($schema) {
                case 'normal':
                    $this->setupSchema($config["schema"]);
                    break;
                case 'force':
                    $this->forceSetupSchema($config["schema"]);
                    break;
                default:
                    $this->alteredSchema($config["schema"]);
                    break;
            }
        }
    }
    public function connect(string $user, string $pass, string $host, string $db): PDO
    {
        $dsn = "mysql:host=$host;dbname=$db";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
        return $pdo;
    }
    protected function connectByApp(object $config)
    {
        $user = $config->DB_USER ?? "";
        $pass = $config->DB_PASS ?? "";
        $host = $config->DB_HOST ?? "";
        $db = $config->DB_NAME ?? "";
        return $this->connect($user, $pass, $host, $db);
    }
    protected function connectByConfig(array $config)
    {
        $user = $config["user"] ?? "";
        $pass = $config["pass"] ?? "";
        $host = $config["host"] ?? "";
        $db = $config["db"] ?? "";
        return $this->connect($user, $pass, $host, $db);
    }
    public function columns(string $table)
    {
        $columns = [];
        try {
            $rs = $this->pdo->query("SELECT * FROM $table LIMIT 0");
            for ($i = 0; $i < $rs->columnCount(); $i++) {
                $col = $rs->getColumnMeta($i);
                $columns[] = $col['name'];
            }
        } catch (Exception $e) {
            //
        } catch (Error $e) {
            //
        }
        return $columns;
    }
    public function tables()
    {
        try {
            $tableList = array();
            $result = $this->pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tableList[] = $row[0];
            }
            return $tableList;
        } catch (PDOException $e) {
            return [];
        }
    }
    public function deleteAllTables()
    {
        $query = "";
        foreach ($this->tables() as $table) {
            $query .= "DROP TABLE IF EXISTS $table;";
        }
        if (!empty($query)) {
            return $this->pdo->exec($query) == 0 ? TRUE : FALSE;
        }
        return FALSE;
    }
    public function setupSchema(array $schema = []): bool
    {
        // SCHEMA - { table: [column,...]}
        $schema = $schema ?? $this->schema;
        $query = "";

        foreach ($schema as $table => $columns) {

            $cols = ""; // Columns - translated into SQL query

            foreach ($columns as $column) {
                if ($column === "id") {
                    // Primary key has a default max value of 11
                    $cols .= "id int(11) AUTO_INCREMENT PRIMARY KEY,";
                } else {
                    $cols .= "$column LONGTEXT NOT NULL,";
                }
            }

            $cols = rtrim($cols, ",");
            $query .= "CREATE TABLE IF NOT EXISTS $table($cols);";
        }

        ($this->pdo)->exec($query);
        return true;
    }
    public function forceSetupSchema(array $schema = []): bool
    {
        // SCHEMA - { table: [column,...]}

        $schema = $schema ?? $this->schema;
        $query = "";

        foreach ($schema as $table => $columns) {

            $primary_key = false; // DEFAULT - Automatically set to true if id column is exists;
            $cols = ""; // Columns - translated into SQL query

            foreach ($columns as $column) {
                if ($column === "id") {
                    // Primary key has a default max value of 11
                    $primary_key = true;
                    $cols .= "id int(11) AUTO_INCREMENT PRIMARY KEY,";
                } else {
                    $cols .= "$column LONGTEXT NOT NULL,";
                }
            }

            $cols = rtrim($cols, ",");
            $query .= "DROP TABLE IF EXISTS $table;";
            $query .= "CREATE TABLE $table($cols);";
        }

        ($this->pdo)->exec($query);
        return true;
    }
    public function alteredSchema(array $schema = [])
    {
        $u = [];
        $r = [];
        $query = "";

        foreach ($schema as $table => $columns) {
            if (!$this->is_table($table)) {
                $u[$table] = $columns;
            } else {
                $r[$table] = $columns;
            }
        }

        if (count($u) > 0) {
            $this->setupSchema($u);
        }

        if (count($r) > 0) {
            foreach ($r as $t => $c) {
                // delete - ALTER TABLE `module_column` DROP COLUMN `module_id`
                // add - ALTER TABLE emails ADD <column name> varchar(60)
                $cs = $this->columns($t);
                $primary_key = false; // DEFAULT - Automatically set to true if id column is exists;
                $add = "";
                $drop = "";
                foreach ($c as $cl) {
                    if (!in_array($cl, $cs)) {
                        if ($cl === "id") {
                            $primary_key = true;
                            $add .= "ADD id int(11) AUTO_INCREMENT PRIMARY KEY,";
                        } else {
                            $add .= "ADD $cl LONGTEXT NOT NULL,";
                        }
                    }
                }

                foreach ($cs as $c1) {
                    if (!in_array($c1, $c)) {
                        $drop .= "DROP COLUMN $c1,";
                    }
                }

                $add = rtrim($add, ",") . ",";
                $drop = rtrim($drop, ",");
                $data = rtrim("$add$drop", ",");
                if (strlen($data) > 0) {
                    $query .= "ALTER TABLE $t $data;";
                }
            }
            if (strlen($query) > 0) {
                ($this->pdo)->exec($query);
            }
        }
        return true;
    }
    public function is_table(string $table)
    {
        try {
            $this->pdo->query("SELECT 1 FROM $table");
        } catch (PDOException $e) {
            return false;
        }
        return true;
    }
    public function table(string $table): void
    {
        $this->tb = $table;
    }
    public function createData(array $data): bool
    {
        // DATA - {key: value}
        $tb = $this->tb;
        $keys = "";
        $pdo_values = "";
        $values = [];

        foreach ($data as $key => $value) {
            $keys .= $key . ",";
            $pdo_values .= "?,";
            $values[] = $value;
        }

        $keys = rtrim($keys, ",");
        $pdo_values = rtrim($pdo_values, ",");

        $query = "INSERT INTO $tb($keys)VALUE($pdo_values)";

        $q = ($this->pdo)->prepare($query);
        $q->execute($values);
        if ($q->rowCount() > 0) {
            return true;
        }
        return false;
    }
    public function createUniqueData($key, array $data): bool
    {
        if (is_string($key)) {
            if (isset($data[$key]) && count($this->readData([$key => $data[$key]])) > 0) {
                return false;
            }
        }

        return $this->createData($data);
    }
    public function readData(array $find, array $advance = []): array
    {

        // find - ["name" => "jericho"]
        $tb = $this->tb;

        $prep_bind = $order = $limit = $offset = "";
        $bind_data = [];

        foreach ($find as $key => $value) {
            $prep_bind .= "$key=?,";
            $bind_data[] = $value;
        }

        if (isset($advance["order"])) {
            $order = " ORDER BY $advance[order]";
        }
        // Advance - ["order" => "id ASC|DESC"]
        if (isset($advance["limit"])) {
            $limit = " LIMIT $advance[limit]";
        }
        // Advance - ["limit" => 3]
        if (isset($advance["offset"])) {
            $offset = " OFFSET $advance[offset]";
        }
        // Advance - ["offset" => 1]

        $bind = (strlen($prep_bind) > 0) ? " WHERE " . rtrim($prep_bind, ",") : "";
        $query = "SELECT * FROM $tb$bind$order$limit$offset";

        $q = ($this->pdo)->prepare($query);
        $q->execute($bind_data);

        $result = $q->fetchAll() ?? [];

        if (isset($advance["columns"])) {
            $cols = $advance["columns"];
            if (is_string($cols)) {
                $cols = explode(",", $cols);
                foreach ($cols as $k => $v) {
                    $cols[$k] = trim($v);
                }
            } else if (is_array($cols)) {
                foreach ($cols as $k => $v) {
                    if (!is_string($v)) {
                        unset($cols[$k]);
                    }
                }
            } else {
                return $result;
            }
            return self::columnizer($result, $cols);
        }
        return $result;
    }
    public function readAllData(array $advance = []): array
    {
        $tb = $this->tb;

        $order = $limit = $offset = "";

        if (isset($advance["order"])) {
            $order = " ORDER BY $advance[order]";
        }
        // Advance - ["order" => "id ASC|DESC"]
        if (isset($advance["limit"])) {
            $limit = " LIMIT $advance[limit]";
        }
        // Advance - ["limit" => 3]
        if (isset($advance["offset"])) {
            $offset = " OFFSET $advance[offset]";
        }
        // Advance - ["offset" => 1]

        $query = "SELECT * FROM $tb$order$limit$offset";

        $q = ($this->pdo)->prepare($query);
        $q->execute();

        $result = $q->fetchAll() ?? [];

        if (isset($advance["columns"])) {
            $cols = $advance["columns"];
            if (is_string($cols)) {
                $cols = explode(",", $cols);
                foreach ($cols as $k => $v) {
                    $cols[$k] = trim($v);
                }
            } else if (is_array($cols)) {
                foreach ($cols as $k => $v) {
                    if (!is_string($v)) {
                        unset($cols[$k]);
                    }
                }
            } else {
                return $result;
            }
            return self::columnizer($result, $cols);
        }
        return $result;
    }
    private static function columnizer(array $a, array $columns): array
    {
        $rows = [];
        foreach ($a as $k => $r) {
            if (is_array($r)) {
                $cols = [];
                foreach ($columns as $col) {
                    if (is_string($col)) {
                        $cols[$col] = $r[$col] ?? NULL;
                    }
                }
                $rows[$k] = $cols;
            }
        }
        return $rows;
    }
    public function updateData(array $find, array $data): bool
    {
        // Find - [key => value]
        // Data = [key => value]
        $tb = $this->tb;

        $prep_data = $prep_find = "";
        $new_data_find = [];

        foreach ($data as $data_key => $data_val) {
            $prep_data .= "$data_key=?,";
            $new_data_find[] = $data_val;
        }

        foreach ($find as $find_key => $find_val) {
            $prep_find .= "$find_key=?,";
            $new_data_find[] = $find_val;
        }

        $prep_data = rtrim($prep_data, ",");
        $prep_find = rtrim($prep_find, ",");

        $query = "UPDATE $tb SET $prep_data WHERE $prep_find";

        $q = ($this->pdo)->prepare($query);
        $q->execute($new_data_find);

        if ($q->rowCount() > 0) {
            return true;
        }

        return false;
    }
    public function deleteData(array $find): bool
    {
        $prep_find = "";
        foreach ($find as $key => $value) {
            $prep_find .= "$key=?,";
            $new_find[] = $value;
        }

        $prep_find = rtrim($prep_find, ",");

        $query = "DELETE FROM $this->tb WHERE $prep_find";
        $q = ($this->pdo)->prepare($query);
        $q->execute($new_find);

        if ($q->rowCount() > 0) {
            return true;
        }

        return false;
    }
    public function deleteAllData(): bool
    {
        try {
            $sql = 'DELETE FROM $this->tb';
            $this->pdo->exec($sql);
            return TRUE;
        } catch (Throwable $e) {
            return FALSE;
        }
    }
}
class LPDOModel
{
    protected $pdo;
    protected $tb;

    public function __construct(string $table, $pdo)
    {
        $this->tb = $table;
        $this->pdo = $pdo;
    }
    private function table()
    {
        $this->pdo->table($this->tb);
    }
    function new(array $data)
    {
        $this->table();
        return $this->pdo->createData($data);
    }
    public function unique(string $column, array $data)
    {
        $this->table();
        return $this->pdo->createUniqueData($column, $data);
    }
    public function column(string $column, array $find, array $advance = [])
    {
        $result = $this->row($find, $advance);
        return isset($result[$column]) ? $result[$column] : null;
    }
    public function columns($column, array $find, array $advance = [])
    {
        $columns = [];
        $fresult = [];

        if (is_string($column)) {
            foreach (explode(',', $column) as $col) {
                if (is_string($col) && trim($col) != "") {
                    $columns[] = trim($col);
                }
            }
        } elseif (is_array($column)) {
            foreach ($column as $col) {
                if (is_string($col) && trim($col) != "") {
                    $columns[] = trim($col);
                }
            }
        }

        $result = $this->row($find, $advance);

        foreach ($columns as $col) {
            $fresult[$col] = $result[$col] ?? null;
        }

        return $fresult;
    }
    public function row(array $find, array $advance = [])
    {
        $this->table();
        $advance["limit"] = 1;
        $advance["offset"] = 0;
        $result = $this->pdo->readData($find, $advance);
        return count($result) > 0 ? $result[0] : [];
    }
    public function rows(array $find, array $advance = [])
    {
        $this->table();
        $result = $this->pdo->readData($find, $advance);
        return $result;
    }
    public function all(array $advance = [])
    {
        $this->table();
        return $this->pdo->readAllData($advance);
    }
    public function update(array $find, array $data)
    {
        $this->table();
        return $this->pdo->updateData($find, $data);
    }
    public function delete(array $find)
    {
        $this->table();
        return $this->pdo->deleteData($find);
    }
    public function deleteAll()
    {
        $this->table();
        return $this->pdo->deleteAllData();
    }
}
class Query
{

    public static function get(string $method = "request", string $str, $callback = null)
    {
        $keys = explode(" ", trim($str));
        $fkey = [];
        foreach ($keys as $key) {
            if (strlen(trim($key)) > 0) {
                if (count(explode(":", $key)) == 2) {
                    $lkey = explode(":", $key);
                    $fkey[$lkey[0]] = urldecode($lkey[1]);
                } else {
                    $fkey[$key] = "";
                }
            }
        }

        $params = self::rmatch($fkey, $method) ? self::translate($fkey, $method) : false;

        if (gettype($callback) == "object" && $params !== false) {
            // try {
            return $callback($params) ?? $params;
            // } catch (Exception $e) {
            // var_dump($e->getMessage());
            // throw new Exception("Query: Callback is invalid! ");
            // }

            return $params;
        } elseif ($params !== false) {
            return $params;
        }

        return false;
    }
    private static function _request($method, array $default = [])
    {
        $method = trim(strtolower((string) $method));

        $REQ = $default;

        switch ($method) {
            case 'post':
                $REQ = $_POST ?? $REQ;
                break;
            case 'get':
                $REQ = $_GET ?? $REQ;
            case 'put':
                $REQ = $_PUT ?? $REQ;
                break;
            case 'delete':
                $REQ = $_DELETE ?? $REQ;
                break;
            case 'files':
                $REQ = $_FILES ?? $REQ;
                break;
            case 'server':
                $REQ = $_SERVER ?? $REQ;
                break;
            case 'env':
                $REQ = $_ENV ?? $REQ;
                break;
            default:
                $REQ = $_REQUEST ?? $REQ;
                break;
        }
        return $REQ;
    }
    private static function rmatch($arr, $method): bool
    {

        $REQ = self::_request($method, []);

        foreach ($arr as $k => $v) {
            if (strtolower($v) == "--r" || strtolower($v) == "-r" || strtolower($v) == "~r" || strtolower($v) == "!r") {
                if (isset($REQ[$k])) {
                    if (gettype($REQ[$k]) == "string" && strlen($REQ[$k]) <= 0) {
                        return false;
                    }
                } else {
                    return false;
                }
            } elseif (strlen(trim($v)) > 0) {
                if (isset($REQ[$k])) {
                    if ($REQ[$k] != $v) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }
        return true;
    }
    private static function translate(array $arr, string $method): array
    {
        $REQ = self::_request($method, []);
        $res = [];
        foreach ($arr as $k => $v) {
            if (!empty($REQ[$k]) && isset($REQ[$k])) {
                $res[$k] = $REQ[$k];
            } else {
                $res[$k] = null;
            }
        }
        return $res;
    }
}
class URI
{
    public static function getPath()
    {
        $path = $_SERVER["REQUEST_URI"] ?? "/";
        $queryPosition = strpos($path, "?");
        $path = $queryPosition ? substr($path, 0, $queryPosition) : $path;
        return "/" . trim($path, "/");
    }
    public static function getQueryPath()
    {
        $path = $_SERVER["REQUEST_URI"] ?? "/";

        $queryPosition = strpos($path, "?") ? strpos($path, "?") + 1 : false;
        $path = $queryPosition ? substr($path, $queryPosition) : "/";

        $andPosition = strpos($path, "&");
        $path = $andPosition ? substr($path, 0, $andPosition) : $path;

        return "/" . trim($path, "/");
    }
}
class Download
{
    public static function file(string $path, array $config = [])
    {
        if (file_exists($path)) {
            $description = $config["description"] ?? "File Download";
            $mime = $config["mime"] ?? "application/octet-stream";
            $ext = pathinfo(basename($path), PATHINFO_EXTENSION);
            $filename = isset($config["filename"]) ? rtrim($config["filename"], $ext) . "." . $ext : basename($path);
            $encoding = $config["encoding"] ?? "binary";
            $expires = $config["expires"] ?? 0;
            $cache_control = $config["cache"] ?? "must-revalidate, post-check=0, pre-check=0";
            $pragma = $config["pragma"] ?? "public";
            $filesize = filesize($path);
            header("Content-Description: $description");
            header("Content-Type: $mime");
            header("Content-Disposition: attachment; filename=$filename");
            header("Content-Transfer-Encoding: $encoding");
            header("Expires: $expires");
            header("Cache-Control: $cache_control");
            header("Pragma: $pragma");
            header("Content-Length: $filesize");
            ob_clean();
            flush();
            readfile($path);
            exit;
        } else {
            throw new Exception("File does not exists");
        }
    }
}
class Upload
{
    private $dir = "uploads";
    private $exts = "";
    private $max_size = 2.0; // Mega Bytes
    private $errors = [];
    private $ready = false;
    public function __construct(array $config = [])
    {
        $this->dir = $config["dir"] ?? $this->dir;
        $this->exts = $config["exts"] ?? $this->exts;
        $this->max_size = $config["max_size"] ?? $this->max_size;

        $this->dir = rtrim($this->dir, "/") . "/";
    }
    private function is_ext_allowed(string $filename): bool
    {
        $this->exts = trim($this->exts);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        foreach (explode(" ", $this->exts) as $val) {
            if ($val == $ext) {
                return true;
            }
        }

        $this->errors[] = "$filename - Invalid file extension (Allowed: " . implode(", ", explode(" ", $this->exts)) . ")";
        return false;
    }
    private function is_size_allowed(int $size, string $filename): bool
    {
        if ($this->max_size == 0 || $size / 1024 / 1204 <= $this->max_size) {
            return true;
        }
        $this->errors[] = "$filename - Invalid file size (Allowed: $this->max_size MB)";
        return false;
    }
    private function is_upload_error(string $key)
    {
        if (isset($_FILES[$key])) {
            switch ($_FILES[$key]["error"]) {
                case UPLOAD_ERR_OK:
                    return true;
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $this->errors[] = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->errors[] = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->errors[] = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->errors[] = "Missing a temporary folder.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->errors[] = "Failed to write file to disk.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $this->errors[] = "A PHP extension stopped the file upload";
                    break;
                default:
                    break;
            }
        }

        return false;
    }
    private function is_uploads_error(string $key)
    {
        if (isset($_FILES[$key])) {
            foreach ($_FILES[$key]["name"] as $k => $fname) {
                $fname = basename($fname);
                $fname = htmlentities($fname);
                switch ($_FILES[$key]["error"][$k]) {
                    case UPLOAD_ERR_OK:
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                        $this->errors[] = "$fname exceeds the upload_max_filesize directive in php.ini";
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->errors[] = "$fname exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->errors[] = "$fname was not uploaded";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->errors[] = "$fname is missing a temporary folder.";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $this->errors[] = "$fname failed to write to disk.";
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $this->errors[] = "$fname - A PHP extension stopped the file upload";
                        break;
                    default:
                        break;
                }
            }
        }

        return false;
    }
    public function file(string $key, ?string $name = null)
    {
        $ok = [];
        if (isset($_FILES[$key]) && !is_array($_FILES[$key]["name"])) {
            $fname = basename($_FILES[$key]["name"]);
            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            $size = $_FILES[$key]["size"];

            $this->is_ext_allowed($fname);
            $this->is_size_allowed($size, $fname);
            $this->is_upload_error($key);

            $filename = $name !== null ? $name . ($ext == "" ? $ext : ".$ext") : $fname;
            $path = $this->dir . $filename;

            if (file_exists($path)) {
                $this->errors[] = "$filename already exists";
            }

            $ok[$filename]["name"] = $_FILES[$key]["name"];
            $ok[$filename]["size"] = $size;
            $ok[$filename]["mime"] = $_FILES[$key]["type"];

            $ok[$filename]["path"] = count($this->errors) == 0 && move_uploaded_file($_FILES[$key]["tmp_name"], $path) ? $path : false;
            $ok[$filename]["errors"] = $this->errors;
        } else {
            return false;
        }

        return $ok;
    }
    public function files(string $key, ?object $name = null)
    {
        $ok = [];
        $err = [];
        if (isset($_FILES[$key]) && is_array($_FILES[$key]["name"])) {
            foreach ($_FILES[$key]["name"] as $k => $v) {

                $fname = basename($_FILES[$key]["name"][$k]);
                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $size = $_FILES[$key]["size"][$k];

                $this->is_ext_allowed($fname);
                $this->is_size_allowed($size, $fname);
                $this->is_uploads_error($key);

                try {
                    $filename = gettype($name) == "object" ? $name() . ($ext == "" ? $ext : ".$ext") : $fname;
                } catch (Exception $e) {
                    $filename = $fname;
                }

                $path = $this->dir . $filename;

                if (file_exists($path)) {
                    $this->errors[] = "$filename already exists";
                }

                $ok[$fname]["name"] = $_FILES[$key]["name"][$k];
                $ok[$fname]["size"] = $size;
                $ok[$fname]["mime"] = $_FILES[$key]["type"][$k];

                $ok[$fname]["path"] = count($this->errors) == 0 && move_uploaded_file($_FILES[$key]["tmp_name"][$k], $path) ? $path : false;
                $ok[$fname]["errors"] = $this->errors;

                $err = array_merge($err, $this->errors);
                $this->errors = [];
            }
        } else {
            return false;
        }

        return $ok;
    }
}
class Resolve
{
    public static function json($a): void
    {
        header('Content-Type: application/json');
        echo json_encode((array) $a);
        exit;
    }
}