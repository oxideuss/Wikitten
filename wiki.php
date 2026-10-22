<?php
if (!defined('APP_STARTED')) {
    die('Forbidden!');
}

class Wiki
{
    protected array $_renderers = [
        'md' => 'Markdown',
        'markdown' => 'Markdown',
        'mdown' => 'Markdown',
        'htm' => 'HTML',
        'html' => 'HTML'
    ];
    protected string $_ignore = '/^\..*|^CVS$/'; // Match dotfiles and CVS
    protected bool|string $_force_unignore = false; // always show these files (false to disable)

    protected ?string $_action = null;

    protected array $_default_page_data = [
        'title' => false, // will use APP_NAME by default
        'description' => 'Wikitten is a small, fast, PHP wiki.',
        'tags' => ['wikitten', 'wiki'],
        'page' => ''
    ];

    /**
     * @param string $extension
     * @return string|callable
     */
    protected function _getRenderer(string $extension): string|false
    {
        $extension = strtolower($extension);
        if (!isset($this->_renderers[$extension])) {
            return false;
        }

        $renderer = $this->_renderers[$extension];

        require_once __DIR__ . DIRECTORY_SEPARATOR . 'renderers' . DIRECTORY_SEPARATOR . "$renderer.php";

        return $renderer;
    }

    protected function _render(string $page)
    {
        $fullPath = LIBRARY . DIRECTORY_SEPARATOR . $page;
        $path = realpath(LIBRARY . DIRECTORY_SEPARATOR . $page);
        $parts = explode('/', $page);

        $not_found = function () use ($page) {
            $page = htmlspecialchars($page, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            throw new Exception("Page '$page' was not found");
        };

        if (!$this->_pathIsSafe($fullPath)) {
            $not_found();
        }

        // Handle directories by showing a neat listing of its
        // contents
        if (is_dir($path)) {
            if (!file_exists($path)) {
                $not_found();
            }

            if (file_exists($path . DIRECTORY_SEPARATOR . 'index.md')) {
                $indexPage = trim($page, '/');
                return $this->_render(($indexPage === '' ? '' : $indexPage . '/') . 'index.md');
            }

            // Get a printable version of the actual folder name:
            $dirName = htmlspecialchars((string) end($parts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if ($dirName === '') {
                $dirName = htmlspecialchars(APP_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            // Get a printable version of the rest of the path,
            // so that we can display it with a different appearance:
            $restParts = array_slice($parts, 0, count($parts) - 1);
            $restParts = htmlspecialchars(implode('/', $restParts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Pass this to the render view, cleverly disguised as just
            // another page, so we can make use of the tree, breadcrumb,
            // etc.
            $pageData = $this->_default_page_data;
            $pageData['title'] = 'Listing: ' . $dirName;
            $pageData['file'] = $page;

            $files = scandir($path);
            if ($files === false) {
                $not_found();
            }
            $list = "<h2>Folder: {$dirName}</h2><p>This folder is empty.</p>\n";
            if (2 < count($files)) {
                $list = "<h2>Folder: {$dirName}</h2><p>Contents:</p><ul>\n";
                foreach ($files as $file) {
                    if (preg_match('/^\..*$/', $file)) {
                        continue;
                    }
                    $href = htmlspecialchars(rtrim(BASE_URL . '/' . implode('/', array_map('rawurlencode', $parts)), '/') . '/' . rawurlencode($file), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $label = htmlspecialchars($file, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $list .= "<li><a href=\"{$href}\">{$label}</a></li>\n";
                }
                $list .= "</ul>\n";
            }

            $this->_view('render', [
                'parts' => $parts,
                'page' => $pageData,
                'html' => $list,
                'is_dir' => true,
                'csrf_token' => ENABLE_EDITING ? $this->_csrfToken() : '',
                'use_pastebin' => $this->_usePasteBin()
            ]);
            return;
        }

        if (ENABLE_EDITING) {
            $extension = substr($fullPath, strrpos($fullPath, '.') + 1, 20);
            if (false === $extension || false === $this->_getRenderer($extension)) {
                $not_found();
            } elseif (!file_exists($fullPath)) {
                // Pass this to the render view, cleverly disguised as just
                // another page, so we can make use of the tree, breadcrumb,
                // etc.
                $_page              = htmlspecialchars($page, ENT_QUOTES);
                $page_data          = $this->_default_page_data;
                $page_data['title'] = 'Page not found: ' . $_page;
                $missingParts = explode('/', str_replace('\\', '/', $page));
                $missingName = (string) array_pop($missingParts);

                return $this->_view('render', [
                    'parts'     => $parts,
                    'page'      => $page_data,
                    'missing_page' => $page,
                    'missing_parent' => implode('/', $missingParts),
                    'missing_name' => $missingName,
                    'csrf_token' => ENABLE_EDITING ? $this->_csrfToken() : '',
                    'use_pastebin' => false,
                    'html'      =>
                          "<h3>Page '$_page' not found</h3>"
                        . "<br/>"
                    ,
                    'is_dir'    => false
                ]);
            }
        } else {
            $not_found();
        }

        $finfo = finfo_open(FILEINFO_MIME);
        if ($finfo === false) {
            $not_found();
        }
        $mime_type = finfo_file($finfo, $path);
        finfo_close($finfo);
        $mime_type = is_string($mime_type) ? trim($mime_type) : 'application/octet-stream';
        if (!str_starts_with($mime_type, 'text/')
            && !str_starts_with($mime_type, 'inode/x-empty')
        ) {
            // not an ASCII file, send it directly to the browser
            $file = fopen($path, 'rb');
            if ($file === false) {
                $not_found();
            }

            header("Content-Type: $mime_type");
            $fileSize = filesize($path);
            if ($fileSize !== false) {
                header('Content-Length: ' . $fileSize);
            }

            fpassthru($file);
            exit();
        }

        $source = file_get_contents($path);
        if ($source === false) {
            $not_found();
        }
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $renderer = $this->_getRenderer($extension);
        $page_data = $this->_default_page_data;

        // Extract the JSON header, if the feature is enabled:
        if (USE_PAGE_METADATA) {
            [$source, $meta_data] = $this->_extractJsonFrontMatter($source);
            $page_data = array_merge($page_data, $meta_data);
            $tags = is_array($page_data['tags'] ?? null) ? $page_data['tags'] : [];
            $page_data['tags'] = array_values(array_filter($tags, 'is_scalar'));
        }

        // We need to know the source file in case editing is enabled:
        $page_data['file'] = $page;

        $html = false;
        if ($renderer === 'HTML') {
            $html = $renderer($source);
        }
        if ($renderer === 'Markdown') {
            $html = \Wikitten\MarkdownExtra::defaultTransform($source);
        }

        if ($html === false || trim($html) === '') {
            $html = "<h1>This page is empty</h1>\n";
            $source = $parts[0];
        }

        $this->_view('render', [
            'html' => $html,
            'source' => $source,
            'extension' => $extension,
            'parts' => $parts,
            'page' => $page_data,
            'is_dir' => false,
            'csrf_token' => ENABLE_EDITING ? $this->_csrfToken() : '',
            'use_pastebin' => $this->_usePasteBin()
        ]);
    }

    protected function _usePasteBin(): bool
    {
        return defined('ENABLE_PASTEBIN') && ENABLE_PASTEBIN && defined('PASTEBIN_API_KEY') && PASTEBIN_API_KEY;
    }

    /**
     * Given a file path, verifies if the file is safe to touch,
     * given permissions, if it's within the library, etc.
     *
     * @param  string $path
     * @return bool
     */
    protected function _pathIsSafe(string|false $path): bool
    {
        $libraryPath = realpath(LIBRARY);
        if ($path === false || $libraryPath === false) {
            return false;
        }

        $libraryPrefix = rtrim($libraryPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $isInsideLibrary = static fn (string $candidate): bool =>
            $candidate === $libraryPath
            || str_starts_with($candidate, $libraryPrefix);

        if (($resolvedPath = realpath($path)) !== false) {
            return $isInsideLibrary($resolvedPath);
        }

        // Resolve the nearest existing ancestor so missing pages cannot
        // be created through a symlink outside the library.
        $ancestor = $path;
        while (!file_exists($ancestor) && !is_link($ancestor)) {
            $parent = dirname($ancestor);
            if ($parent === $ancestor) {
                return false;
            }
            $ancestor = $parent;
        }

        $resolvedAncestor = realpath($ancestor);
        return $resolvedAncestor !== false && $isInsideLibrary($resolvedAncestor);
    }

    /**
     * Given a string with a page's source, attempts to locate a
     * section of JSON Front Matter in the heading, and returns
     * the remaining source, and an array of extracted meta data.
     *
     * JSON Front Matter will only be considered when present
     * within two lines consisting of three dashes:
     *
     * ---
     * { "title": "hello world" }
     * ---
     *
     * Additionally, the opening and closing brackets may be dropped,
     * and this method will still interpret the content as a hash:
     *
     * ---
     * "title": "hello, world",
     * "tags":  ["hello", "world"]
     * ---
     *
     * @param  string $source
     * @return array  array($remaining_source, $meta_data)
     */
    protected function _extractJsonFrontMatter(string $source): array
    {
        static $front_matter_regex = "/^---[\r\n](.*)[\r\n]---[\r\n](.*)/s";

        $source = ltrim($source);
        $meta_data = [];

        if (preg_match($front_matter_regex, $source, $matches)) {
            $json = trim($matches[1]);
            $source = trim($matches[2]);

            // Locate or append starting and ending brackets,
            // if necessary. I lazily only check the first
            // character for a bracket, so that it'll work
            // even if the user includes a hash in the last
            // line:
            if ($json === '' || $json[0] !== '{') {
                $json = '{' . $json . '}';
            }

            // Decode & validate the JSON payload:
            $meta_data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            // Check for errors:
            if (!is_array($meta_data)) {
                throw new RuntimeException('JSON Front Matter must decode to an object or array.');
            }
        }

        return [$source, $meta_data];
    }

    protected function _view(string $view, array $variables = []): void
    {
        extract($variables, EXTR_SKIP);

        $content = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . "$view.php";

        if (!isset($layout)) {
            $layout = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'layout.php';
        }

        if (file_exists($content)) {
            ob_start();

            include $content;
            $content = ob_get_clean();

            if ($layout) {
                include $layout;
            } else {
                echo $content;
            }
        } else {
            throw new Exception("View $view not found");
        }
    }

    protected function _getTree(string $dir = LIBRARY): array
    {
        $return = ['directories' => [], 'files' => []];

        $items = scandir($dir);
        if ($items === false) {
            return [];
        }
        foreach ($items as $item) {
            if (preg_match($this->_ignore, $item)) {
                if ($this->_force_unignore === false || !preg_match($this->_force_unignore, $item)) {
                    continue;
                }
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (!$this->_pathIsSafe($path)) {
                continue;
            }

            if (is_dir($path)) {
                $return['directories'][$item] = $this->_getTree($path);
                continue;
            }

            $return['files'][$item] = $item;
        }

        uksort($return['directories'], "strnatcasecmp");
        uksort($return['files'], "strnatcasecmp");

        return $return['directories'] + $return['files'];
    }

    public function dispatch(): void
    {
        if (!function_exists("finfo_open")) {
            die("<p>Please enable the PHP Extension <code style='background-color: #eee; border: 1px solid #ccc; padding: 3px; border-radius: 3px; line-height: 1;'>FileInfo.dll</code> by uncommenting or adding the following line:</p><pre style='background-color: #eee; border: 1px solid #ccc; padding: 5px; border-radius: 3px;'><code><span style='color: #999;'>;</span>extension=php_fileinfo.dll <span style='color: #999; margin-left: 25px;'># You can just uncomment by removing the semicolon (;) in the front.</span></code></pre>");
        }
        $action = $this->_getAction();
        $actionMethod = "{$action}Action";

        if ($action === null || !method_exists($this, $actionMethod)) {
            $this->_404();
        }

        $this->$actionMethod();
    }

    protected function _getAction(): ?string
    {
        if (isset($_REQUEST['a']) && is_string($_REQUEST['a'])) {
            $action = $_REQUEST['a'];

            if (in_array("{$action}Action", get_class_methods($this), true)) {
                $this->_action = $action;
            }
        } else {
            $this->_action = 'index';
        }
        return $this->_action;
    }

    protected function _json(mixed $data = []): never
    {
        header('Content-Type: application/json; charset=utf-8');
        echo is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR);
        exit();
    }

    protected function _csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'httponly' => true,
                'secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        if (!isset($_SESSION['WIKITTEN_CSRF']) || !is_string($_SESSION['WIKITTEN_CSRF'])) {
            $_SESSION['WIKITTEN_CSRF'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['WIKITTEN_CSRF'];
    }

    protected function _validLibraryRelativePath(string $relative): bool
    {
        if ($relative === '' || str_starts_with($relative, '/') || str_contains($relative, "\0")) {
            return false;
        }

        $parts = preg_split('~[\\\\/]+~', $relative);
        if ($parts === false) {
            return false;
        }

        foreach ($parts as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                return false;
            }
        }

        return true;
    }

    protected function _removeLibraryTree(string $path): bool
    {
        if (is_link($path)) {
            return unlink($path);
        }
        if (!is_dir($path)) {
            return is_file($path) && unlink($path);
        }

        $items = scandir($path);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (!$this->_removeLibraryTree($path . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($path);
    }

    protected function _isXMLHttpRequest(): bool
    {
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? null) === 'XMLHttpRequest') {
            return true;
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (($headers['HTTP_X_REQUESTED_WITH'] ?? null) === 'XMLHttpRequest') {
                return true;
            }
        }

        return false;
    }

    protected function _404(string $message = 'Page not found.'): never
    {
        http_response_code(404);
        $page_data = $this->_default_page_data;
        $page_data['title'] = 'Not Found';

        $this->_view('uhoh', [
            'error' => $message,
            'parts' => ['Uh-oh'],
            'page' => $page_data
        ]);

        exit;
    }

    public function indexAction(): void
    {
        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $page = ltrim(rawurldecode(substr($requestPath, strlen(APP_DIR))), '/');

        if (!$page) {
            if (file_exists(LIBRARY . DIRECTORY_SEPARATOR . DEFAULT_FILE)) {
                $this->_render(DEFAULT_FILE);
                return;
            }

            $this->_view('index', [
                'page' => $this->_default_page_data,
                'csrf_token' => ENABLE_EDITING ? $this->_csrfToken() : ''
            ]);
            return;
        }

        try {
            $this->_render($page);
        } catch (Exception $e) {
            $this->_404($e->getMessage());
        }
    }

    /**
     * /?a=edit
     * If ENABLE_EDITING is true, handles file editing through
     * the web interface.
     */
    public function editAction(): never
    {
        // Bail out early if editing isn't even enabled, or
        // we don't get the right request method && params
        // NOTE: $_POST['source'] may be empty if the user just deletes
        // everything, but it should always be set.
        if (!ENABLE_EDITING || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || empty($_POST['ref']) || !isset($_POST['source'])
            || !is_string($_POST['ref']) || !is_string($_POST['source'])
            || !isset($_POST['csrf_token']) || !is_string($_POST['csrf_token'])
            || !hash_equals($this->_csrfToken(), $_POST['csrf_token'])
        ) {
            $this->_404();
        }

        $ref = $_POST['ref'];
        $source = $_POST['source'];
        $file = base64_decode($ref, true);
        if ($file === false) {
            $this->_404();
        }
        $path = realpath(LIBRARY . DIRECTORY_SEPARATOR . $file);

        // Check if the file is safe to work with, otherwise just
        // give back a generic 404 aswell, so we don't allow blind
        // scanning of files:
        // @todo: we CAN give back a more informative error message
        // for files that aren't writable...
        if (!$this->_pathIsSafe($path) || !is_file($path) || !is_writable($path)) {
            $this->_404();
        }

        // Check if empty
        if (trim($source) !== '') {
            // Save the changes, and redirect back to the same page
            if (file_put_contents($path, $source) === false) {
                $this->_404();
            }
        } else {
            // Delete file and redirect too (but it will return 404)
            unlink($path);
        }

        $redirectPath = implode('/', array_map('rawurlencode', explode('/', $file)));
        $redirect_url = BASE_URL . '/' . $redirectPath;
        http_response_code(302);
        header("Location: $redirect_url");

        exit();
    }

    /**
     * Handle createion of PasteBin pastes
     * @return string JSON response
     */
    public function createPasteBinAction(): never
    {
        if (!$this->_usePasteBin()) {
            $this->_404();
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (isset($_POST['ref']) && is_string($_POST['ref'])) {
                $file = base64_decode($_POST['ref'], true);
                $path = realpath(LIBRARY . DIRECTORY_SEPARATOR . $file);

                if ($file === false || !$this->_pathIsSafe($path) || !is_file($path) || !is_readable($path)) {
                    $this->_404();
                } else {
                    $content = file_get_contents($path);
                    if ($content === false) {
                        $this->_404();
                    }
                    $name = pathinfo($path, PATHINFO_BASENAME);

                    require_once PLUGINS . DIRECTORY_SEPARATOR . 'PasteBin.php';

                    $response = [];

                    $pastebin = new PasteBin(PASTEBIN_API_KEY);

                    /**
                     * @todo Add/improve autodetection of file format
                     */

                    $url = $pastebin->createPaste($content, PasteBin::PASTE_PRIVACY_PUBLIC, $name, PasteBin::PASTE_EXPIRE_1W);
                    if ($url) {
                        $response['status'] = 'ok';
                        $response['url'] = $url;
                    } else {
                        $response['status'] = 'fail';
                        $response['error'] = $pastebin->getError();
                    }

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($response, JSON_THROW_ON_ERROR);
                    exit();
                }
            }
        }

        exit();
    }

    /**
     * Singleton
     * @return Wiki
     */
    public static function instance(): self
    {
        static $instance;
        if (!($instance instanceof self)) {
            $instance = new self();
        }
        return $instance;
    }

    public function manageAction(): never
    {
        if (!ENABLE_EDITING || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || !isset($_POST['csrf_token'], $_POST['operation'], $_POST['parent'])
            || !is_string($_POST['csrf_token']) || !is_string($_POST['operation'])
            || !is_string($_POST['parent'])
            || !hash_equals($this->_csrfToken(), $_POST['csrf_token'])
        ) {
            $this->_404();
        }

        $parent = trim(str_replace('\\', '/', $_POST['parent']), '/');
        if ($parent !== '' && !$this->_validLibraryRelativePath($parent)) {
            $this->_404();
        }
        $parentPath = LIBRARY . ($parent === '' ? '' : DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $parent));
        if (!$this->_pathIsSafe($parentPath) || !is_dir($parentPath)) {
            $this->_404();
        }

        $operation = $_POST['operation'];
        if ($operation === 'create_folder' || $operation === 'create_note') {
            $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
            if ($name === '' || $name === '.' || $name === '..' || str_starts_with($name, '.')
                || preg_match('~[\\\\/\x00-\x1F\x7F]~', $name)
            ) {
                $this->_404('Please enter a valid name.');
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $renderer = $operation === 'create_note' ? $this->_getRenderer($extension) : false;
            if ($operation === 'create_note' && $renderer === false) {
                $name .= '.md';
                $extension = 'md';
                $renderer = $this->_getRenderer($extension);
            }
            $target = $parentPath . DIRECTORY_SEPARATOR . $name;
            if (!$this->_pathIsSafe($target) || file_exists($target) || is_link($target)) {
                $this->_404('That name is unavailable.');
            }

            if ($operation === 'create_folder') {
                $created = mkdir($target, 0755);
            } else {
                $starterContent = $renderer === 'Markdown'
                    ? '# ' . pathinfo($name, PATHINFO_FILENAME) . "\n\n"
                    : '';
                $created = file_put_contents($target, $starterContent) !== false;
            }
            if (!$created) {
                $this->_404('Could not create that item.');
            }
            $relative = ($parent === '' ? '' : $parent . '/') . $name;
            $redirect = BASE_URL . '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
            header('Location: ' . $redirect, true, 303);
            exit;
        }

        $item = isset($_POST['item']) && is_string($_POST['item']) ? str_replace('\\', '/', $_POST['item']) : '';
        $item = trim($item, '/');
        if (!$this->_validLibraryRelativePath($item)) {
            $this->_404();
        }
        $itemPath = LIBRARY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item);
        if (!$this->_pathIsSafe($itemPath) || !file_exists($itemPath) || is_link($itemPath)) {
            $this->_404();
        }
        $expectedParent = dirname($item);
        if ($expectedParent === '.') {
            $expectedParent = '';
        }
        if ($expectedParent !== $parent) {
            $this->_404();
        }

        if ($operation === 'rename') {
            $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
            if ($name === '' || $name === '.' || $name === '..' || str_starts_with($name, '.')
                || preg_match('~[\\\\/\x00-\x1F\x7F]~', $name)
            ) {
                $this->_404('Please enter a valid name.');
            }
            if (is_file($itemPath) && pathinfo($name, PATHINFO_EXTENSION) === '') {
                $name .= '.' . pathinfo($itemPath, PATHINFO_EXTENSION);
            }
            $target = $parentPath . DIRECTORY_SEPARATOR . $name;
            if (!$this->_pathIsSafe($target) || file_exists($target) || is_link($target)
                || !rename($itemPath, $target)
            ) {
                $this->_404('Could not rename that item.');
            }
            $relative = ($parent === '' ? '' : $parent . '/') . $name;
            $redirect = BASE_URL . '/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
            header('Location: ' . $redirect, true, 303);
            exit;
        }

        if ($operation !== 'delete' || !$this->_removeLibraryTree($itemPath)) {
            $this->_404('Could not delete that item.');
        }

        $redirect = $parent === ''
            ? BASE_URL
            : BASE_URL . '/' . implode('/', array_map('rawurlencode', explode('/', $parent)));
        header('Location: ' . $redirect, true, 303);
        exit;
    }
}
