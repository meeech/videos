<div class="toolbar">

<?php
//if(!isset($page)) { $page = 'home'; }
$pageTitle = 'Videos';
$homeButton = false;
$backButton = false;

switch ($page) {
    case 'login':
        $homeButton = true;
        $pageTitle = 'Login';
    break;
    case 'list':
        $pageTitle = 'Videos - Path';
        $homeButton = true;
        $backButton = true;
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
