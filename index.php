<?php
require 'settings.php';
require 'functions.php';

session_start();
if (preg_match('/\.\w+$/', $_SERVER['REQUEST_URI'])) {
    if (file_exists($_SERVER['REQUEST_URI'])) {
        return false;
    } else {
        http_response_code(404);
        exit();
    }
}

$route_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

$redirect_url = null;
$lang_slug = null;

$browser_lang = prefered_language($languages, $_SERVER['HTTP_ACCEPT_LANGUAGE']);
if (in_array($browser_lang, $languages)) {
    $lang = $browser_lang;
} else {
    $lang = $languages[0];
}

if (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
}
if (in_array($route_parts[0], $languages)) {
    $_SESSION['lang'] = $route_parts[0];
    $lang = $route_parts[0];
    $lang_slug = $route_parts[0];
    array_shift($route_parts);
}

$slug = implode('/', $route_parts);
if ($slug == "") {
    $slug = "/";
}

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die('Error de conexión: ' . $mysqli->connect_error);
}

//REDIRECTION ENGINE
$query = $mysqli->prepare("SELECT * FROM redirections WHERE path = ?");
$query->bind_param('s', $slug);
$query->execute();
$result = $query->get_result();
if ($result->num_rows > 0) {
    $redir = $result->fetch_assoc();
    $mysqli->close();
    header("Location: ".$redir['destination'], true, $redir['code']);
    exit();
}

//PAGE ENGINE
$query = $mysqli->prepare("SELECT * FROM pages WHERE path = ? OR main_path = ?");
$query->bind_param('ss', $slug, $slug);
$query->execute();
$result = $query->get_result();
$mysqli->close();
if ($result->num_rows > 0) {
    while ($page = $result->fetch_assoc()) {
        if ($lang == $page['language'] && $lang_slug == $languages[0] && $slug == "/") {
            $redirect_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/";
            break;
        } elseif ($lang == $page['language'] && $slug == $page['path'] && $lang == $languages[0]) {
            if ($slug != "/") {
                $redirect_url = "/" . $slug . "/";
            }
            break;
        } elseif ($lang == $page['language'] && $slug == $page['path'] && $lang != $languages[0]) {
            if ($slug != "/") {
                $redirect_url = "/" . $lang_slug . "/" . $slug . "/";
            } else {
                $redirect_url = "/" . $lang_slug . "/";
            }
            break;
        } elseif ($lang != $page['language'] && $slug == $page['path'] && $lang == $languages[0]) {
            $redirect_url = "/" . $page['main_path'] . "/";
            break;
        } elseif ($lang == $page['language'] && $slug == $page['main_path'] && $lang != $languages[0]) {
            $redirect_url = "/" . $lang_slug . "/" . $page['path'] . "/";
            break;
        }
    }
} else {
    http_response_code(404);
    echo "pagina no encontrada";
    exit();
}

if ($redirect_url !== null && $redirect_url != $_SERVER['REQUEST_URI']) {
    header("Location: $redirect_url", true, 301);
    exit();
}

if (isset($page['content'])) { echo $page['content']; }

