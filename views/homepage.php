<?php
	session_start();
	$_SESSION['basePath'] = '../';
	//session_write_close();
	require_once $_SESSION['basePath'].'controllers/home.php';


  $homeControlVar = new HomeController();
  $channelName = NULL;
  $workspaceUrl = "musicf17.slack.com";


  if (isset($_POST["channel"])) {
    global $channelName;
    $channelName = $_POST["channel"];
  } else if (isset($_SESSION['channel'])) {
    global $channelName;
    $_POST["channel"] = $_SESSION['channel'];
    $channelName = $_POST["channel"];
    unset($_SESSION['channel']);
  }

  function displayChannels()
  {
    global $homeControlVar;
    global $workspaceUrl;
    global $channelName;
    $channelList = $homeControlVar->viewChannels($workspaceUrl);
    if (!isset($_SESSION['channel']) && !isset($_POST["channel"])) {
      $channelName = $channelList[0];
      $_POST["channel"] = $channelList[0];
    }
    foreach ($channelList as $value) {
      echo '<form method="post" action="home.php">
              <div class = "ChannelDisplay col-xs-12">
                <input type="hidden" name="channel" value="'.$value.'" />
                <input type="submit" class="SideBarButton" value="'.$value.'" />
              </div>
            </form>';
    }
  }

  function displayMessages() {
    global $homeControlVar;
    global $channelName;
    global $workspaceUrl;
    $channelMessages = $homeControlVar->viewMessages($channelName, $workspaceUrl);
    $i = 1;
    foreach ($channelMessages as $key => $value) {
      $CurrentTime = new DateTime($value["created_time"]);
      $strip = $CurrentTime->format('H:i @Y-m-d');
      $name = NULL;
      $msgId = $value['msg_id'];
      $msgIdRef = $msgId."action";
      $likeEmo = "like";
      $dislikeEmo = "dislike";
      //$likeCount = getReactionCount($msgId, $likeEmo);
      //$dislikeCount = getReactionCount($msgId, $dislikeEmo);
      $actionUrl = htmlspecialchars($_SERVER['PHP_SELF'].'#'.$msgIdRef);
      if (count($channelMessages) != $i) {
        $name = "<div id= ".$msgIdRef." class = 'EntireMessage'>
                  <strong class = 'UserName'>".$value["first_name"]."&nbsp &nbsp".$value["last_name"].
                  "</strong> &nbsp &nbsp &nbsp <span class = 'TimeStamp'>".$strip."</span>
                  <ul class = 'MessageUL'>
                    <li class = 'MessageLI'>".$value['message']."</li>
                  </ul>

                  <label class='like' name='like' id=".$msgId.">
                  <i class='fa fa-thumbs-o-up' aria-hidden='true'></i>
                   </label>&nbsp &nbsp
                  <span id = 'likeResponse".$msgId."'>        </span>
                  <label class='dislike' name='dislike' id=".$msgId.">
                  <i class='fa fa-thumbs-o-down' aria-hidden='true'></i>
                  </label> &nbsp &nbsp
                  <span id = 'dislikeResponse".$msgId."'>     </span>

                    <form method='post' class = 'replyForm' action=".$actionUrl." >
                      <input type='hidden' name='threadId' value=". $msgId." />
                      <input type='submit' class='treadIdSubmit' name='treadIdSubmit' value='reply' />
                    </form>

                </div>";

      }  else {
      $name = "<div id=".$msgIdRef." class = 'EntireMessage'>
              <p id='bottomMsg'></p>
              <strong class = 'UserName'>".$value["first_name"]."&nbsp &nbsp".$value["last_name"].
              "</strong> &nbsp &nbsp &nbsp <span class = 'TimeStamp'>".$strip."</span>
              <ul class = 'MessageUL'>
                <li class = 'MessageLI'>".$value['message']."</li>
              </ul>

              <label class='like' name='like' id=".$msgId.">
              <i class='fa fa-thumbs-o-up' aria-hidden='true'></i>
               </label>&nbsp &nbsp
              <span id = 'likeResponse".$msgId."'>        </span>
              <label class='dislike' name='dislike' id=".$msgId.">
              <i class='fa fa-thumbs-o-down' aria-hidden='true'></i>
              </label> &nbsp &nbsp
              <span id = 'dislikeResponse".$msgId."'>     </span>

                <form class = 'replyForm' method='post' action=".$actionUrl.">
                  <input type='hidden' name='threadId' value=".$msgId.">
                  <input type='hidden' name='channel' value= ".$_POST['channel'].">
                  <input type='submit' class='threadIdSubmit' name='threadIdSubmit' value='reply'>
                </form>

            </div>";
    }
      echo $name;
      $i++;
    }
  }

  function getReactionCount($msgId, $emoName) {
    global $homeControlVar;
    $info = array();
    $info = $homeControlVar->getReactionInfoForMsg($msgId, $emoName);
    return $info['count'];
  }
  //
  // function threadReply($treadsArr){
  //   $thread = NULL;
  //   $reply = NULL;
  //   foreach ($treadsArr as $key => $value) {
  //     $reply = $value["msg_id"];
  //     $thread = "<div class = 'threadDiv' col-xs-2>
  //                 <ul>
  //                   <li>".$reply."
  //                 </ul>
  //               </div>";
  //
  //   }
  //   echo $thread;
  // }

  ?>