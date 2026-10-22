<?php if (!defined('APP_STARTED')) {
    die('Forbidden!');
} ?>
<h1>Welcome to Wikitten!</h1>
<p>
  You're looking at this page because you haven't created a <code><?php echo htmlspecialchars(LIBRARY . DIRECTORY_SEPARATOR . DEFAULT_FILE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></code> file yet.
</p>
<?php if (ENABLE_EDITING): ?>
  <details class="wiki-management" open>
    <summary>Create your first note or folder</summary>
    <div class="wiki-management-actions">
      <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
        <input type="hidden" name="operation" value="create_note">
        <input type="hidden" name="parent" value="">
        <label for="first-note-name">New note</label>
        <input class="form-control" id="first-note-name" name="name" placeholder="Note name (adds .md)" required>
        <button class="btn btn-primary btn-sm" type="submit">Create note</button>
      </form>
      <form method="POST" action="<?php echo e(BASE_URL . '/?a=manage'); ?>" class="wiki-management-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
        <input type="hidden" name="operation" value="create_folder">
        <input type="hidden" name="parent" value="">
        <label for="first-folder-name">New folder</label>
        <input class="form-control" id="first-folder-name" name="name" placeholder="Folder name" required>
        <button class="btn btn-default btn-sm" type="submit">Create folder</button>
      </form>
    </div>
  </details>
<?php endif; ?>
