<?php
session_start();

// Distrugge tutte le variabili di sessione
$_SESSION = array();

// Se si usa anche una sessione basata su cookie, è necessario distruggerlo.
// Questo distruggerà il cookie di sessione.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, distrugge la sessione
session_destroy();

// Reindirizza l'utente alla pagina principale (index.php)
header("Location: index.php");
exit();
?>