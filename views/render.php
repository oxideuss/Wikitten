<?php if (!defined('APP_STARTED')) {
    die('Forbidden!');
} ?>
<div class="breadcrumbs">
    <div class="pull-right">
        <?php if ($html && isset($source)): ?>
            <a href="javascript:;" class="btn-black" id="toggle">Toggle source</a>
        <?php endif ?>
        <?php if ($use_pastebin): ?>
            <a href="javascript:;" class="btn-black" id="create-pastebin" title="Create public Paste on PasteBin">Create public Paste</a>
        <?php endif; ?>
    </div>

    <?php $path = []; ?>

    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
              <i class="fas fa-home"></i> wiki
            </a>
        </li>
        <?php $i = 0; ?>
        <?php foreach ($parts as $part): ?>
            <?php $path[] = $part; ?>
            <?php $url = BASE_URL . '/' . implode('/', array_map('rawurlencode', $path)); ?>
            <?php $i++; ?>
            <li class="breadcrumb-item <?php echo ($i == count($parts) ? 'active' : '')?>">
                <a href="<?php echo htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    <?php if ($i == count($parts) && !$is_dir): ?>
                        <i class="far fa-file"></i>
                    <?php else: ?>
                        <i class="far fa-folder"></i>
                    <?php endif ?>
                    <?php echo htmlspecialchars((string) $part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </a>
            </li>
        <?php endforeach ?>
      </ol>
    </nav>

</div>

<?php if (ENABLE_EDITING && isset($page['file'])): ?>
    <?php
        $manageItem = str_replace('\\', '/', (string) $page['file']);
        $manageParent = $is_dir ? trim($manageItem, '/') : trim(dirname($manageItem), './');
        $itemParent = trim(dirname($manageItem), './');
        $itemName = basename($manageItem);
    ?>
    <details class="wiki-management">
        <summary>Manage notes and folders</summary>
        <div class="wiki-management-actions">
            <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                <input type="hidden" name="operation" value="create_note">
                <input type="hidden" name="parent" value="<?php echo e($manageParent); ?>">
                <label for="new-note-name">New note</label>
                <input class="form-control" id="new-note-name" name="name" placeholder="Note name (adds .md)" required>
                <button class="btn btn-primary btn-sm" type="submit">Create note</button>
            </form>
            <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                <input type="hidden" name="operation" value="create_folder">
                <input type="hidden" name="parent" value="<?php echo e($manageParent); ?>">
                <label for="new-folder-name">New folder</label>
                <input class="form-control" id="new-folder-name" name="name" placeholder="Folder name" required>
                <button class="btn btn-default btn-sm" type="submit">Create folder</button>
            </form>
            <?php if ($manageItem !== ''): ?>
                <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="operation" value="rename">
                    <input type="hidden" name="parent" value="<?php echo e($itemParent); ?>">
                    <input type="hidden" name="item" value="<?php echo e($manageItem); ?>">
                    <label for="rename-item">Rename this <?php echo $is_dir ? 'folder' : 'note'; ?></label>
                    <input class="form-control" id="rename-item" name="name" value="<?php echo e($itemName); ?>" required>
                    <button class="btn btn-warning btn-sm" type="submit">Rename</button>
                </form>
                <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form" onsubmit="return confirm('Delete this <?php echo $is_dir ? 'folder and everything inside it' : 'note'; ?>?');">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="operation" value="delete">
                    <input type="hidden" name="parent" value="<?php echo e($itemParent); ?>">
                    <input type="hidden" name="item" value="<?php echo e($manageItem); ?>">
                    <button class="btn btn-danger btn-sm" type="submit">Delete this <?php echo $is_dir ? 'folder' : 'note'; ?></button>
                </form>
            <?php endif; ?>
        </div>
    </details>
<?php endif; ?>

<?php if (ENABLE_EDITING && isset($missing_page)): ?>
    <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
        <input type="hidden" name="operation" value="create_note">
        <input type="hidden" name="parent" value="<?php echo e($missing_parent); ?>">
        <input type="hidden" name="name" value="<?php echo e($missing_name); ?>">
        <button class="btn btn-primary" type="submit">Create this note</button>
    </form>
<?php endif; ?>

<?php if ($html): ?>
    <?php if ($use_pastebin): ?>
    <div id="pastebin-notification" class="alert" style="display:none;"></div>
    <?php endif; ?>
    <div id="render">
        <?php echo $html; ?>
    </div>
    <script>
        $('#render pre').addClass('prettyprint linenums');
        prettyPrint();

        $('#render a[href^="#"]').click(function(event) {
            event.preventDefault();
            document.location.hash = $(this).attr('href').replace('#', '');
        });
    </script>
<?php endif ?>

<?php if (isset($source)): ?>
    <?php if ($use_pastebin): ?>
    <div id="pastebin-notification" class="alert" style="display:none;"></div>
    <?php endif; ?>
    <div id="source">
        <?php if (ENABLE_EDITING): ?>
            <div class="alert alert-info">
                <i class="glyphicon glyphicon-pencil"></i> <strong>Editing is enabled</strong>. Use the "Save changes" button below the editor to commit modifications to this file.
            </div>
        <?php endif ?>

        <form method="POST" action="<?php echo htmlspecialchars(BASE_URL . '/?a=edit', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <input type="hidden" name="ref" value="<?php echo base64_encode($page['file']) ?>">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
            <textarea id="editor" name="source" class="form-control" rows="<?php echo substr_count($source, "\n") + 1; ?>"><?php echo htmlspecialchars($source, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>

            <?php if (ENABLE_EDITING): ?>
                <div class="form-actions">
                    <input type="submit" class="btn btn-warning btn-sm" id="submit-edits" value="Save Changes">
                </div>
            <?php endif ?>
        </form>
    </div>

    <script>
        <?php if ($html): ?>
            CodeMirror.defineInitHook(function () {
                $('#source').hide();
            });
        <?php endif ?>

        const modes = {
            'md': 'markdown',
            'markdown': 'markdown',
            'mdown': 'markdown',
            'js': 'javascript',
            'php': 'php',
            'sql': 'text/x-sql',
            'py': 'python',
            'scm': 'scheme',
            'clj': 'clojure',
            'rb': 'ruby',
            'css': 'css',
            'hs': 'haskell',
            'lsh': 'haskell',
            'pl': 'perl',
            'r': 'r',
            'scss': 'sass',
            'sh': 'shell',
            'xml': 'xml',
            'html': 'htmlmixed',
            'htm': 'htmlmixed'
        };
        const extension = <?php echo json_encode($extension, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const mode = modes[extension] ?? false;

        const editor = CodeMirror.fromTextArea(document.getElementById('editor'), {
            lineNumbers: true,
            lineWrapping: true,
            <?php if (USE_DARK_THEME): ?>
            theme: 'tomorrow-night-bright',
            <?php else: ?>
            theme: 'default',
            <?php endif; ?>
            mode: mode
            <?php if (!ENABLE_EDITING): ?>
            ,readOnly: true
            <?php endif ?>
        });

        $('#toggle').on('click', (event) => {
            event.preventDefault();
            $('#render').toggle();
            $('#source').toggle();
            if ($('#source').is(':visible')) {
                editor.refresh();
            }

        });

        <?php if ($use_pastebin): ?>
        $('#create-pastebin').on('click', function (event) {
            event.preventDefault();

            $(this).addClass('disabled');

            const notification = $('#pastebin-notification');
            notification.removeClass('alert-info alert-error').html('').hide();

            $.ajax({
                type: 'POST',
                url: <?php echo json_encode(BASE_URL . '/?a=createPasteBin', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                data: { ref: '<?php echo base64_encode($page['file']); ?>' },
                context: $(this)
            }).done(function(response) {
                $(this).removeClass('disabled');

                if (response.status === 'ok') {
                    notification.addClass('alert-info').text('Paste URL: ' + response.url).show();
                } else {
                    notification.addClass('alert-error').text('Error: ' + response.error).show();
                }
            });
        });
        <?php endif; ?>
    </script>
<?php endif ?>
