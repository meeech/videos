<div class="toolbar">
<?php
//if(!isset($page)) { $page = 'home'; }
$pageTitle = 'Videos';
$homeButton = true;
$backButton = true;

switch ($page) {
    case 'login':
        $homeButton = false;
        $backButton = false;
        $pageTitle = 'Login';
    break;
    case 'list':
        $pageTitle = 'Videos - Path';
    break;
    case 'home':
        $homeButton = false;
        $backButton = false;
    break;
}

?>

    <h1><?= $pageTitle ?></h1>

    <?php if ($homeButton): ?>
        <a class="button slideup" href="#home">Home</a>
    <?php endif ?>

    <?php if ($backButton): ?>
        <a class="button back slidedown" href="#">Back</a>
    <?php endif ?>
</div>
