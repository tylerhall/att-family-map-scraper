<?php

error_reporting(E_ALL);
require_once "lib/fireeagle.php";

function main() {

    $cfn = dirname(__FILE__)."/lib/config.php";
    if (file_exists($cfn)) require_once($cfn);

    ob_start();
    session_start();

    if (@$_GET['f'] == 'start') {
        // get a request token + secret from FE and redirect to the authorization page
        // START step 1
        $fe = new FireEagle($fe_key, $fe_secret);
        $tok = $fe->getRequestToken();
        if (!isset($tok['oauth_token'])
            || !is_string($tok['oauth_token'])
            || !isset($tok['oauth_token_secret'])
            || !is_string($tok['oauth_token_secret'])) {
            echo "ERROR! FireEagle::getRequestToken() returned an invalid response.  Giving up.";
            exit;
        }
        $_SESSION['auth_state'] = "start";
        $_SESSION['request_token'] = $token = $tok['oauth_token'];
        $_SESSION['request_secret'] = $tok['oauth_token_secret'];
        header("Location: ".$fe->getAuthorizeURL($token));
        // END step 1
    } else if (@$_GET['f'] == 'callback') {
        // the user has authorized us at FE, so now we can pick up our access token + secret
        // START step 2
        if (@$_SESSION['auth_state'] != "start") {
            echo "Out of sequence.";
            exit;
        }
        if ($_GET['oauth_token'] != $_SESSION['request_token']) {
            echo "Token mismatch.";
            exit;
        }

        $fe = new FireEagle($fe_key, $fe_secret, $_SESSION['request_token'], $_SESSION['request_secret']);
        $tok = $fe->getAccessToken();
        if (!isset($tok['oauth_token']) || !is_string($tok['oauth_token'])
            || !isset($tok['oauth_token_secret']) || !is_string($tok['oauth_token_secret'])) {
            error_log("Bad token from FireEagle::getAccessToken(): ".var_export($tok, TRUE));
            echo "ERROR! FireEagle::getAccessToken() returned an invalid response.  Giving up.";
            exit;
        }

        $_SESSION['access_token'] = $tok['oauth_token'];
        $_SESSION['access_secret'] = $tok['oauth_token_secret'];
        $_SESSION['auth_state'] = "done";
        header("Location: ".$_SERVER['SCRIPT_NAME']);
        // END step 2
    } else if (@$_SESSION['auth_state'] == 'done') {
        // we have our access token + secret, so now we can actually *use* the api
        // START step 3
        $fe = new FireEagle($fe_key, $fe_secret, $_SESSION['access_token'], $_SESSION['access_secret']);

        // handle postback for location update
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            // we're updating the user's location.
            $where = array();
            foreach (array("lat", "lon", "q", "place_id") as $k) {
                if (!empty($_POST[$k])) $where[$k] = $_POST[$k];
            }
            switch (@$_POST['submit']) {
            case 'Move!':
                $r = $fe->update($where); // equivalent to $fe->call("update", $where)
                header("Location: ".$_SERVER['SCRIPT_NAME']);
                exit;
            case 'Lookup':
                echo "<p>Lookup results:</p><div><code>".nl2br(htmlspecialchars(var_export($fe->lookup($where), TRUE)))."</code></div>";
                break;
            }
        }

        ?><p>You are authenticated with <a href="<?php print htmlspecialchars(FireEagle::$FE_ROOT) ?>">Fire Eagle</a>!  (<a href="?f=start">Change settings</a>.)</p><p>Here are the settings you need to now paste into <code>config.php</code></p><p>access token: <?PHP echo $_SESSION['access_token']; ?><br/>access secret: <?PHP echo $_SESSION['access_secret']; ?></p><?php

        // END step 3
    } else {
        // not authenticated yet, so give a link to use to start authentication.
        ?><p>To setup, go to the <a href="http://fireeagle.yahoo.net/developer">Fire Eagle developer's area</a> and create a new "Auth for web-base services" application. Use <a href="<?PHP echo $_SERVER['PHP_SELF'];?>?f=callback">this URL</a> as the callback URL. Once your application has been created, paste the application keys into the <code>config.php</code> file.</p> <p>Once that is completed, <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>?f=start">click here to authenticate with Fire Eagle!</a></p><?php
    }
}

main();

?>