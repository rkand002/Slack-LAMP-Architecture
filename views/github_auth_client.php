<?php
	session_start();
	$_SESSION['basePath'] = '../';
  require_once $_SESSION['basePath'].'controllers/github.php';

  $githubControlVar = new GithubAuth();
  $redirect_uri = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];

  if (isset($_GET['action']) && $_GET['action'] == "git_auth") {
    $_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
    unset($_SESSION['access_token']);
    $githubControlVar->sendRequestParams($_SESSION['state'], $redirect_uri, NULL);
    die();
  }

  if (isset($_GET['code']) && !empty($_GET['code'])) {
    if(!isset($_GET['state']) || $_SESSION['state'] != $_GET['state']) {
      $_SESSION['invalidCredentials'] = "true";
      $_SESSION['reason'] = "github-log";
      header("location:../index.php");
      die();
    }
    $githubControlVar->sendRequestParams($_SESSION['state'], $redirect_uri, $_GET['code']);
  } else {
    $_SESSION['invalidCredentials'] = "true";
    $_SESSION['reason'] = "github-log";
    header("location:../index.php");
  }

  if (isset($_SESSION['access_token'])) {
    $userDetails = $githubControlVar->apiRequest();
		$_SESSION['github_avatar'] = $userDetails->avatar_url;
    $workspaceUrl = "musicf17.slack.com";
    $result = $githubControlVar->processUser($userDetails, $workspaceUrl);
    if ($result == "true") {
      $githubControlVar->directToHome($userDetails);
    } else {
      $_SESSION['invalidCredentials'] = "true";
      $_SESSION['reason'] = "github-log";
      header("location:../index.php");
    }
  } else {
    $_SESSION['invalidCredentials'] = "true";
    $_SESSION['reason'] = "github-log";
    header("location:../index.php");
  }
  /* debug code
  $url = 'https://api.github.com/user';
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  $headers[] = 'Accept: application/json';
  $headers[] = 'Authorization: token ' . $_SESSION['access_token'];
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  //curl_setopt( $c, CURLOPT_SSL_VERIFYPEER, false );
  //curl_setopt( $c, CURLOPT_SSL_VERIFYHOST, false );
  $response = curl_exec($ch);
  */
?>
