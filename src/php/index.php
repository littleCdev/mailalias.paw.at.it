<?php
include_once("mail.paw.at.it.php");

if( isset( $_POST["Username"],$_POST["Email"] ) ){
    $oForward = new mailAliasPawAtIt();
    $oForward->addAlias($_POST["Username"],$_POST["Email"]);
} else if( isset( $_GET["c"] ) ){
    $oForward = new mailAliasPawAtIt();
    if( !$oForward->activate( $_GET["c"] ) ){
        unset( $oForward );
    }
}

?>
<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">

    <title>your own paw.at.it emailalias</title>
    <meta name="description" content="paw.at.it emailalias">
    <meta name="author" content="littlecheetah">

    <link rel="stylesheet" href="css/normalize.css" />
    <link rel="stylesheet" href="css/paw.at.it.css" />

</head>

<body>
    <div id="content">
        <div class="mail">
            <span class="paw"></span>
            <form class="mail" method="post" action="">
                <ul>
                    <?php
                    if( isset( $oForward ) ){
                        echo '
                        <li>
                        <span class="msg">
                            '.$oForward->getUserMsg().'
                        </span>
                    <li class="everbreak">
                    ';}
                    ?>
                    <li>
                        <input id="Username" name="Username" placeholder="Your alias" title="Your alias Letters, number, underscors and dots are allowed min. 3 letter" pattern="[a-zA-Z0-9._]{3,120}" type="text" autocomplete="off" required>
                        <label for="Username">Your alias</label>
                    <li>
                        <span>@paw.at.it</span>
                        <p id="checkMail"></p>
                    </li>
                    <li class="everbreak">
                    <li>
                        <input id="Email" name="Email" placeholder="Your Email" title="Your Email" type="Email" autocomplete="off" required>
                        <label for="Email">Your Email</label>
                    <li class="everbreak">
                    <li>
                        <input id="submit" name="action" type="submit" value="Request alias">
                        <input id="reset" name="action" type="reset" value="Clear">
                </ul>
            </form>
        </div>
        <br>
        <div class="blabla">
            <p>
                This is only an email-alias, it forwards the email to you given email. It is free.
                I give no warranty for functionality. The only data i save is your email and the ip (because of spam),
                no data will be given to others. Only one per user!
                I created this because i can.
            </p>
        </div>
    </div>
    <!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <script src="js/paw.at.it.js"></script>
</body>
</html>
