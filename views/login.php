<div>
    <?php require 'toolbar.php'; ?>

    <?php 
    if (!empty($login_errors)){
        echo $login_errors;
    }
    
    if(!isset($username)) { $username = 'username'; }
    ?>

    <form action="login.php" method="post">
        <label for="username">Username</label>
        <input type="text" name="username" value="<?= $username ?>">
        <label for="password">Password</label>
        <input type="password" name="password" value="">
        <input type="submit" value="Submit">
    </form>
    
</div>