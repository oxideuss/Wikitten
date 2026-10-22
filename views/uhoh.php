<?php if (!defined('APP_STARTED')) {
    die('Forbidden!');
} ?>
<div class="breadcrumbs">

  <ul class="breadcrumb">
    <li>
      <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"><i class="glyphicon glyphicon-home glyphicon-white"></i> /wiki</a>
    </li>

    <li>
      <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>/404"><i class="glyphicon glyphicon-exclamation-sign glyphicon-white"></i> Error</a>
    </li>
  </ul>
</div>
<h1>Uh oh!</h1>
<p>
  An <strong>error</strong> occurred: <code><?php echo htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></code>
</p>
