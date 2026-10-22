<?php
if (!defined('APP_STARTED')) {
    die('Forbidden!');
}

/**
 * This class is only instantiated if the ACCESS_USER and ACCESS_PASSWORD constants are defined
 *
 * @todo Add log for the login attempts and limit as soon as possible.
 */
class Login
{
    /**
     * Constructor
     */
    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'httponly' => true,
                'secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        if (($_GET['action'] ?? null) === 'logout') {
            $this->doLogout();

            header("location: " . BASE_URL);
            exit;
        }
    }

    /**
     * Check if the user is logged
     * @return boolean
     */
    private function isLogged(): bool
    {
        return ($_SESSION['AUTHENTICATED'] ?? false) === true
            && isset($_SESSION['ACCESS_USER'])
            && hash_equals((string) ACCESS_USER, (string) $_SESSION['ACCESS_USER']);
    }

    /**
     * Do the login
     * @param  string $ip       IP address
     * @param  string $username Username
     * @param  string $password Password
     * @return boolean
     */
    private function doLogin(string $username, string $password): bool
    {
        // Check credentials
        if (!hash_equals((string) ACCESS_USER, $username)
            || !hash_equals((string) ACCESS_PASSWORD, $password)
        ) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['AUTHENTICATED'] = true;
        $_SESSION['ACCESS_USER'] = $username;

        return true;
    }

    /**
     * Logout from the password protected area
     * @return boolean Always true
     */
    private function doLogout(): bool
    {
        $_SESSION = [];
        session_regenerate_id(true);
        return true;
    }

    /**
     * Render the login page, if the authentication is not performed
     */
    public function dispatch(): bool
    {
        // Stop if user is aready logged in (exception from negative first)
        if ($this->isLogged()) {
            return true;
        }

        $username = isset($_POST['username']) && is_string($_POST['username']) ? $_POST['username'] : null;
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : null;

        if (isset($_POST['login'])) {
            if (!$username || !$password) {
                $error = 'Please complete both fields.';
            } else {
                if (!$this->doLogin($username, $password)) {
                    $error = "Wrong password.";
                } else {
                    return true;
                }
            }
        }

        // Show the login layout and stop
        $page = [
            'title' => false,
            'description' => '',
            'tags' => [],
        ];
        $layout = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'login.php';
        include $layout;

        exit;
    }

    /**
     * Singleton
     * @return Login
     */
    public static function instance(): self
    {
        static $instance;
        if (!($instance instanceof self)) {
            $instance = new self();
        }
        return $instance;
    }
}
