<?php

//     ▄████████   ▄█     █▄      ▄████████  
//    ███    ███  ███     ███    ███    ███  
//    ███    █▀   ███     ███    ███    █▀   
//    ███         ███     ███    ███         
//  ▀███████████  ███     ███  ▀███████████  
//           ███  ███     ███           ███  
//     ▄█    ███  ███ ▄█▄ ███     ▄█    ███  
//   ▄████████▀    ▀███▀███▀    ▄████████▀   

// definitions

define("_SALT", "(((Emily)))");
define("_HASH", hash("sha256", _SALT));
define("_SANDBOX", FALSE);
define("_USEDB", FALSE);
define("_FROZEN", FALSE);
define("_CACHE", FALSE);

define("_DOMAIN", "");
define("_EMAIL", "");

//  ████████▄   ▀█████████▄   
//  ███   ▀███    ███    ███  
//  ███    ███    ███    ███  
//  ███    ███   ▄███▄▄▄██▀   
//  ███    ███  ▀▀███▀▀▀██▄   
//  ███    ███    ███    ██▄  
//  ███   ▄███    ███    ███  
//  ████████▀   ▄█████████▀   

class D {
	public static $db;

	public static function setup() {
		self::$db = new PDO("sqlite:./../database.db");
	}

	public static function dispense($table) {
		$sql = "create table if not exists {$table} (id int primary key not null);";
		$ret = self::$db->exec($sql);

		if (!$ret) {
			return NULL;
		}
		else {
			$obj = new stdClass();
			$obj->_table = $table;
			return $obj;
		}
	}

	public static function store($obj) {
		$table = $obj->_table;

		if (!$table) {
			return FALSE;
		}

		if (!_FROZEN) {
			$sql = "pragma table_info({$table});";
			$ret = self::$db->exec($sql);
			$cols = $ret->fetchArray(SQLITE3_ASSOC);
			$new = [];

			foreach ($obj as $col => $val) {
				if ($field != "_table") {
					if (!in_array($col, $cols)) {
						$new[] = [$col => $val];
					}
				}
			}

			if (count($new) > 0) {
				$sql = "alter table {$table} ";

				foreach ($new as $col => $val) {
					$type = NULL;
					switch (gettype($val)) {
						case "integer":
							$type = "integer";
							break;
						case "double":
							$type = "real";
							break;
						case "string":
							$type = "text";
							break;
					}

					if ($type) {
						$sql .= "add {$col} {$type},";
					}
				}

				$sql = substr_replace($sql, ";", -1);
				$ret = self::$db->exec($sql);
			}
		}
		if (isset($obj->id)) {
			// update
			$id = $obj->id;
			$sql = "update {$table} set ";

			foreach ($obj as $col => $val) {
				if ($field != "_table") {
					$sql .= "{$col} = {$val},";
				}
			}

			$sql = rtrim($sql, ",") . " where id = {$id};";
			$ret = self::$db->exec($sql);
		}
		else {
			// insert
			$sql = "insert into {$table} (";

			foreach ($obj as $col => $val) {
				if ($field != "_table") {
					$sql .= "{$col},";
				}
			}

			$sql = rtrim($sql, ",") . ") values (";

			switch (gettype($val)) {
				case "integer":
					$sql .= "{$val},";
					break;
				case "double":
					$sql .= "{$val},";
					break;
				case "string":
					$sql .= "'{$val}',";
					break;
			}

			$sql = rtrim($sql, ",") . ");";
			$ret = self::$db->exec($sql);
		}

		if (!$ret) {
			return self::$db->lastErrorMsg();
		}
		else {
			return TRUE;
		}
	}

	public static function find($table, $query, $params = [], $num = 0) {
		$query = strtr($query, $params);
		$sql = "select * from {$table} where {$query}";
		$ret = self::$db->query($sql . ($num > 0) ? " limit {$num};" : ";");

		if (!$ret) {
			return self::$db->lastErrorMsg();
		}
		else {
			$arr = [];
			$rows = $ret->fetchArray(SQLITE3_ASSOC);

			foreach ($rows as $row) {
				$obj = new stdClass();
				$obj->_table = $table;

				foreach ($row as $col => $val) {
					$obj->$col = $val;
				}
				$arr[] = $obj;
			}

			return $arr;
		}
	}

	public static function trash($obj) {
		$table = $obj->_table;

		if (!$table) {
			return FALSE;
		}

		$id = $obj->id;
		$sql = "delete from {$table} where id = {$id};";
		$ret = self::$db->exec($sql);
		
		if (!$ret) {
			return self::$db->lastErrorMsg();
		}
		else {
			return TRUE;
		}
	}
}

// create db if required

if (_USEDB) {
	D::setup();
}

//     ▄████████     ▄███████▄   ▄█   
//    ███    ███    ███    ███  ███   
//    ███    ███    ███    ███  ███▌  
//    ███    ███    ███    ███  ███▌  
//  ▀███████████  ▀█████████▀   ███▌  
//    ███    ███    ███         ███   
//    ███    ███    ███         ███   
//    ███    █▀    ▄████▀       █▀    

// content api

function _api($request, $paypal = false) {
	$default = [
		"domain" => $_SERVER["HTTP_HOST"],
		"path" => $_GET["path"],
		"ip" => $_SERVER["REMOTE_ADDR"],
		"ua" => $_SERVER["HTTP_USER_AGENT"],
		"referer" => $_SERVER["HTTP_REFERER"]
	];

	$fields = array_merge($default, $request);

	$query = ($paypal) ? "?paypal=yes" : "";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, _API . $query);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, ["data" => json_encode($fields)]);
	
	$response = curl_exec($ch);
	curl_close($ch);

	$json = json_decode($response);

	if (!$paypal) {
		// not paypal
		$content = base64_decode($json->content);
	   
		if ($json->debug == "0") {
			// not debug
			switch ($json->status) {
				case "200":
					header("Status: 200 OK", true, 200);
					break;
				case "404":
					header("Status: 404 Not Found", true, 404);
					break;
				case "500":
					header("Status: 500 Server Error", true, 500);
					break;
			}

			return $content;
		}
		else {
			// debug output
			return "status: {$json->status}\nmime: {$json->mime}\ncontent:\n\n{$content}";
		}
	}
}

//   ▄█    █▄      ▄████████     ▄████████     ▄████████  
//  ███    ███    ███    ███    ███    ███    ███    ███  
//  ███    ███    ███    ███    ███    ███    ███    █▀   
//  ███    ███    ███    ███   ▄███▄▄▄▄██▀    ███         
//  ███    ███  ▀███████████  ▀▀███▀▀▀▀▀    ▀███████████  
//  ▀██    ███    ███    ███  ▀███████████           ███  
//   ▀██  ██▀     ███    ███    ███    ███     ▄█    ███  
//    ▀████▀      ███    █▀     ███    ███   ▄████████▀   

$GLOBALS["code"] = "200";

$mimes = [
	"js" => "application/javascript",
	"ico" => "image/x-icon",
	"svg" => "image/svg+xml",
	"jpg" => "image/jpeg",
	"png" => "image/png",
	"gif" => "image/gif",
	"css" => "text/css",
	"txt" => "text/plain",
	"html" => "text/html",
	"ttf" => "font/truetype",
	"otf" => "font/opentype",
	"eot" => "application/vnd.ms-fontobject",
	"woff" => "application/font-woff",
	"woff2" => "font/woff2"
];

$values = [];

$fields = [
	"name",
	"email_address",
	"phone_number",
	"text"
];

//     ▄████████  ███    █▄   ███▄▄▄▄▄     ▄████████     ▄████████  
//    ███    ███  ███    ███  ███▀▀▀▀██▄  ███    ███    ███    ███  
//    ███    █▀   ███    ███  ███    ███  ███    █▀     ███    █▀   
//   ▄███▄▄▄      ███    ███  ███    ███  ███           ███         
//  ▀▀███▀▀▀      ███    ███  ███    ███  ███         ▀███████████  
//    ███         ███    ███  ███    ███  ███    █▄            ███  
//    ███         ███    ███  ███    ███  ███    ███     ▄█    ███  
//    ███         ████████▀    ▀█    █▀   ████████▀    ▄████████▀   

function _out($status, $mime, $content) {
	switch ($status) {
		case "200":
			header("Status: 200 OK", TRUE, 200);
			break;
		case "404":
			header("Status: 404 Not Found", TRUE, 404);
			break;
		case "500":
			header("Status: 500 Server Error", TRUE, 500);
			break;
	}

	header("Content-Type: " . $mimes[$mime]);
	header("Content-Length: " . strlen($content));
	echo $content;
}

function _get($sql) {
	return (isset($values[$field])) ? $values[$field] : FALSE;
}

function _set($field, $value) {
	$values[$field] = $value;
}

function _four04() {
	$GLOBALS["code"] = "404";
	$file = "./html/404.html";
	$html = "<html><body><h1>404</h1><p>Page not found :(</p></body></html>";

	return (_exists($file)) ? file_get_contents($file) : $html;
}

function _exists($filename) {
	return file_exists($filename);
}

function _key() {
	return hash("sha256", $_SERVER['HTTP_HOST'] . "-" . _SALT);
}

function _ispaypal() {
	return (isset($_POST["verify_sign"]) && isset($_POST["txn_id"])) ? TRUE : FALSE;
}

function _isform() {
	return (isset($_POST["key"]) && $_POST["key"] == _key()) ? TRUE : FALSE;
}

function _cc() {
	if (isset($_GET["cc"]) && ($_GET["cc"] === "yes")) {
		$files = glob("./cache/*.html");

		if (count($files) > 0) {
			foreach ($files as $file) {
				unlink($file);
			}
		}

		return TRUE;
	}
}

//     ███        ▄█    █▄       ▄████████   ▄▄▄▄███▄▄▄▄      ▄████████ 
// ▀█████████▄   ███    ███     ███    ███ ▄██▀▀▀███▀▀▀██▄   ███    ███ 
//    ▀███▀▀██   ███    ███     ███    █▀  ███   ███   ███   ███    █▀  
//     ███   ▀  ▄███▄▄▄▄███▄▄  ▄███▄▄▄     ███   ███   ███  ▄███▄▄▄     
//     ███     ▀▀███▀▀▀▀███▀  ▀▀███▀▀▀     ███   ███   ███ ▀▀███▀▀▀     
//     ███       ███    ███     ███    █▄  ███   ███   ███   ███    █▄  
//     ███       ███    ███     ███    ███ ███   ███   ███   ███    ███ 
//    ▄████▀     ███    █▀      ██████████  ▀█   ███   █▀    ██████████


function _theme($params, $html = "") {
	$root = __DIR__;

	if (isset($params["url"])) {
		$page = $params["url"];
		$filename = "{$root}/html/{$page}.html";

		if (_exists($filename)) {
			$html = file_get_contents($filename);
		}
		else {
			$html = _four04();
		}

		unset($params["url"]);
	}

	$array = [];
	preg_match_all("/\[\[(.+?)\]\]/", $html, $array);
	$tags = (isset($array[0])) ? $array[0] : [];
	$cmds = (isset($array[1])) ? $array[1] : [];
	$count = count($cmds);

	if ($count > 0) {
		for ($i = 0; $i < $count; $i++) {
			$cmd = explode(":", $cmds[$i]);

			switch ($cmd[0]) {
				case "domain": {
					$html = str_replace(
						$tags[$i],
						_DOMAIN,
						$html
					);

					break;
				}
				case "host": {
					$html = str_replace(
						$tags[$i],
						$_SERVER['HTTP_HOST'],
						$html
					);

					break;
				}
				case "title": {
					$html = str_replace(
						$tags[$i],
						ucfirst(substr($page, 1)),
						$html
					);

					break;
				}
				case "description": {
					$html = str_replace(
						$tags[$i],
						"site description meta tags",
						$html
					);

					break;
				}
				case "keywords": {
					$html = str_replace($tags[$i],
						"site keywords meta tags",
						$html
					);

					break;
				}
				case "year": {
					$html = str_replace(
						$tags[$i],
						date("Y"),
						$html
					);

					break;
				}
				case "key": {
					$html = str_replace(
						$tags[$i],
						_key(),
						$html
					);

					break;
				}
				case "nav": {
					//
					// TODO: make recursive nav function
					//
					$menu = "";
					$data = "[]";
					$items = json_decode($data, TRUE);

					$file = "{$root}/html/_nav.html";
					$nav = (_exists($file)) ? file_get_contents($file) : "";
					if ($nav != "") {
					    $params["files"][] = $file;
					}

					$file = "{$root}/html/_n1p.html";
					$n1p = (_exists($file)) ? file_get_contents($file) : "";
					if ($n1p != "") {
					    $params["files"][] = $file;
					}

					$file = "{$root}/html/_n1c.html";
					$n1c = (_exists($file)) ? file_get_contents($file) : "";
					if ($n1c != "") {
					    $params["files"][] = $file;
					}

					$file = "{$root}/html/_n2c.html";
					$n2c = (_exists($file)) ? file_get_contents($file) : "";
					if ($n2c != "") {
					    $params["files"][] = $file;
					}

					$first1 = TRUE;

					foreach ($items as $item) {
						$type_1 = $item["name"][0];

						if ($item["name"] == '/') {
							$url_1 = "/";
							$text_1 = "Home";
						}
						else {
							$url_1 = substr($item["name"], 1);
							$text_1 = ucwords(preg_replace("/[\-_]/", " ", $url_1));
						}

						switch ($type_1) {
							case '/': {
								$nl1 = (!$first1) ? "\n" : "";

								$menu .= $nl1 . str_replace(
									"[[url]]",
									$url_1,
									str_replace(
										"[[text]]",
										$text_1,
										$n1c
									)
								);

								$first1 = FALSE;

								break;
							}
							case '#': {
								$kids = "";
								$first2 = TRUE;

								foreach ($item["children"] as $child) {
									$nl2 = (!$first2) ? "\n" : "";
									$type_2 = $child["name"][0];
									$url_2 = substr($child["name"], 1);
									$text_2 = ucwords(preg_replace("/[\-_]/", " ", $url_2));

									$kids .= $nl2 . str_replace(
										"[[url]]",
										$url_2,
										str_replace(
											"[[text]]",
											$text_2,
											$n2c
										)
									);

									$first2 = FALSE;
								}

								$menu .= "\n" . str_replace(
									"[[items]]",
									$kids,
									str_replace(
										"[[text]]",
										$text_1,
										$n1p
									)
								);

								break;
							}
						}
					}
					$html = str_replace(
						$tags[$i],
						str_replace(
							"[[items]]",
							$menu,
							$nav
						),
						$html
					);

					break;
				}
				case "data": {
					$data = "";
					$html = str_replace($tags[$i], $data, $html);

					break;
				}
				case "file": {
					$file = "{$root}/html/{$cmd[1]}.html";
					$content = (_exists($file)) ? file_get_contents($file) : "";

					$html = _theme(
						$params,
						str_replace(
							$tags[$i],
							$content,
							$html
						)
					);

					break;
				}
				case "loop": {
					$loop = "";
					$data = "[]";
					$items = json_decode($data, TRUE);
					$file = "{$root}/html/{$cmd[3]}.html";
					$block = (_exists($file)) ? file_get_contents($file) : "";
					$first = TRUE;

					foreach ($items as $content) {
						$nl = (!$first) ? "\n" : "";

						if ($cmd[1] == 'yes') {
							$content = strip_tags($content);
						}

						$loop .= $nl . str_replace(
							"[[content]]",
							$content,
							$block
						);

						$first = FALSE;
					}

					$html = _theme(
						$params,
						str_replace(
							$tags[$i],
							$loop,
							$html
						)
					);

					break;
				}
				case "value": {
					$html = str_replace(
						$tags[$i],
						_get($cmd[1]),
						$html
					);

					break;
				}
				case "random": {
					$range = explode("-", $cmd[1]);
					$number = rand($range[0], $range[1]);
					$value = (isset($cmd[2]) ? str_pad($number, $cmd[2], "0", STR_PAD_LEFT) : $number);
					$html = str_replace(
						$tags[$i],
						$value,
						$html
					);

					break;
				}
				case "paypal": {
					$value = "<!-- PAYPAL FORM -->\n";
					$html = str_replace($tags[$i], $value, $html);

					break;
				}
				case "product": {
					$product = "PRODUCT " . $cmd[1];

					if ($cmd[2] == "image") {
						$width = (isset($cmd[3]) ? " width=\"" . $cmd[3] . "\"" : "");
						$html = str_replace($tags[$i], "<img src=\"img/{$img}\"{$width}>", $html);
					}
					else {
						$html = str_replace($tags[$i], $product->{$cmd[2]}, $html);
					}

					break;
				}
			}
		}
	}
	return $html;
}

//    ▄████████  ▄██████▄  ███    █▄      ███        ▄████████    ▄████████ 
//   ███    ███ ███    ███ ███    ███ ▀█████████▄   ███    ███   ███    ███ 
//   ███    ███ ███    ███ ███    ███    ▀███▀▀██   ███    █▀    ███    ███ 
//  ▄███▄▄▄▄██▀ ███    ███ ███    ███     ███   ▀  ▄███▄▄▄      ▄███▄▄▄▄██▀ 
// ▀▀███▀▀▀▀▀   ███    ███ ███    ███     ███     ▀▀███▀▀▀     ▀▀███▀▀▀▀▀   
// ▀███████████ ███    ███ ███    ███     ███       ███    █▄  ▀███████████ 
//   ███    ███ ███    ███ ███    ███     ███       ███    ███   ███    ███ 
//   ███    ███  ▀██████▀  ████████▀     ▄████▀     ██████████   ███    ███

// get/post request router

function _router($get, $post) {
	if (!empty($post)) {
		// post request
		$data = (object)$post;
		// handle post stuff here
	}
	else {
		// get request
		$data = (object)$get;
	}

	$url = (ltrim($data->path, "/")) ?: "index";
	$parts = explode("/", $url);
	$parsed = parse_url($url);
	$info = pathinfo($parsed["path"]);
	$ext = $info["extension"];
	$file = "./cache/" . $info["filename"] . ".html";

	if (_exists($file) && _CACHE) {
		$html = file_get_contents($file);
	}
	else {
		$html = _theme([
			"page" => $info["filename"] . $ext,
			"url" => "/" . $url
		]);
		if (($GLOBALS["code"] == 200) && _CACHE) {
			file_put_contents($file, $html);
		}
	}
	
	_out($GLOBALS["code"], "text/html", $html);
}

//     ▄██████▄    ▄██████▄   
//    ███    ███  ███    ███  
//    ███    █▀   ███    ███  
//   ▄███         ███    ███  
//  ▀▀███ ████▄   ███    ███  
//    ███    ███  ███    ███  
//    ███    ███  ███    ███  
//    ████████▀    ▀██████▀   

// do stuff

if (_ispaypal()) {
	$paypal_url = (_SANDBOX) ? "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr" : "https://ipnpb.paypal.com/cgi-bin/webscr";
	
	if (!count($_POST)) {
		die("no post data");
	}
	
	$raw_post_data = file_get_contents("php://input");
	$raw_post_array = explode("&", $raw_post_data);
	$post = [];

	foreach ($raw_post_array as $keyval) {
		$keyval = explode("=", $keyval);
		if (count($keyval) == 2) {
			if ($keyval[0] === "payment_date") {
				if (substr_count($keyval[1], "+") === 1) {
					$keyval[1] = str_replace("+", "%2B", $keyval[1]);
				}
			}
			$post[$keyval[0]] = urldecode($keyval[1]);
		}
	}
	
	// get txn_id and custom_id and set status as "processing"
	
	$req = "cmd=_notify-validate";
	$get_magic_quotes_exists = false;
	
	if (function_exists("get_magic_quotes_gpc")) {
		$get_magic_quotes_exists = true;
	}
	
	foreach ($post as $key => $value) {
		if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
			$value = urlencode(stripslashes($value));
		}
		else {
			$value = urlencode($value);
		}
		$req .= "&{$key}={$value}";
	}
	
	$ch = curl_init($paypal_url);
	
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSLVERSION, 6);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	
	if (_CERTS) {
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/cacert.pem");
	}
	
	curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ["Connection: Close"]);
	
	$res = curl_exec($ch);
	
	if (!($res)) {
		$errno = curl_errno($ch);
		$errstr = curl_error($ch);
		curl_close($ch);
		die("curl error: {$errno} : {$errstr}");
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info["http_code"];
	
	if ($http_code != 200) {
		die("PayPal responded with http code {$http_code}");
	}
	
	curl_close($ch);
	
	if ($res == "VERIFIED") {
		// payment verified ok
	}
	else {
		// problem with payment
	}
}
else if (_isform()) {
	// contact form post
	$message = "Website Form Submission from " . _DOMAIN . "\n\n";

	foreach ($fields as $field) {
		$title = ucwords(str_replace("_", " ", $field));
		$value = (isset($_POST[$field])) ? $_POST[$field] : "No {$title} Supplied";
		$message .= "{$title}: {$value}\n";
	}

	$ok = mail(
		_EMAIL,
		"Website Contact Form",
		$message,
		["From" => "no-reply@" . _DOMAIN],
		"-fno-reply@" . _DOMAIN
	);

	header("Location: /thanks?{$ok}");
}
elseif (_cc()) {
	// clear cache request
	header("Location: /?done");
}
else {
	// normal web request
	_router($_GET, $_POST);
}

// EOF