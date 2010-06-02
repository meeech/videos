<div id="login">
    <?php require 'toolbar.php'; ?>

    <?php 
    if (!empty($login_errors)){
        echo $login_errors;
    }
    //@debug remove my dev credentials
    if(!isset($username)) { $username = 'mitch'; }
    ?>

    <form class="jqt" action="login.php" method="post">
        <label for="username">Username</label>
        <input type="text" name="username" value="<?= $username ?>">
        <label for="password">Password</label>
        <input type="password" name="password" value="1234">
        <input type="submit" value="Submit" class="submit">
    </form>
    
</div>