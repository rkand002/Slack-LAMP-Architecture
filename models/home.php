<?php
  //include_once $_SESSION['basePath'].'errors.php';
  require_once $_SESSION['basePath'].'models/connect.php';

  class HomeModel {
    private $dbConVar;
    private $channels = array();
    private $messages = array();

    public function validMySQL($conn, $data) {
      //$data = stripslashes($data);
      //$data = htmlentities($data);
      //$data = strip_tags($data);
      $data = mysql_real_escape_string($conn, $data);
      return $data;
    }

    public function validateInputs($data) {
      $data = trim($data);
      //$data = stripslashes($data);
      $data = htmlspecialchars($data);
      return $data;
    }

    public function getUserProfile($name, $workspace) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userProfile = array();
      $getProfile = "SELECT U.user_id as user_id, first_name, last_name, email, avatar, role, two_factor
                    FROM user_info AS U INNER JOIN workspace AS W ON U.user_id = W.user_id
                    WHERE U.user_id = '$name' AND W.url = '$workspace'";
      $result = mysqli_query($conn, $getProfile);
      if (mysqli_num_rows($result) > 0)
       {
         while ($row = $result->fetch_assoc())
         {
            $row["user_id"] = $this->validateInputs($row["user_id"]);
            $row["first_name"] = $this->validateInputs($row["first_name"]);
            $row["last_name"] = $this->validateInputs($row["last_name"]);
            $row["email"] = $this->validateInputs($row["email"]);
            $row["role"] = $this->validateInputs($row["role"]);
            $row["two_factor"] = $this->validateInputs($row["two_factor"]);
           array_push($userProfile, $row);
         }
       }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $userProfile;
    }

    public function getAdmins() {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $admins = array();
      $getAdmins = "SELECT user_id, first_name, last_name, display_name, email, avatar
                    FROM user_info
                    WHERE role = 'admin'";
      $result = mysqli_query($conn, $getAdmins);
      if (mysqli_num_rows($result) > 0)
       {
         while ($row = $result->fetch_assoc()) {
           $row["user_id"] = $this->validateInputs($row["user_id"]);
           $row["first_name"] = $this->validateInputs($row["first_name"]);
           $row["last_name"] = $this->validateInputs($row["last_name"]);
           $row["display_name"] = $this->validateInputs($row["display_name"]);
           $row["email"] = $this->validateInputs($row["email"]);
           array_push($admins, $row);
         }
       }
       if ($result) {
         mysqli_free_result($result);
       }
       $dbConVar->closeConnectionObject($conn);
       return $admins;
     }

     public function removeUserFromChannel($userID, $channelName, $workspaceUrl) {
       $dbConVar = new dbConnect();
       $conn = $dbConVar->createConnectionObject();
       $chId = $this->getChannelId($channelName, $workspaceUrl);
       $affectedRows = NULL;
       if ($chId != NULL && $chId > 0) {
          //$userID = $this->validMySQL($conn, $userID);
         $delUsrFrmChannel = "DELETE FROM inside_channel
                              WHERE user_id = ? AND channel_id = ?";
         $stmt = $conn->prepare($delUsrFrmChannel);
         $stmt->bind_param("ss", $userID, $chId);
         $stmt->execute();
         $affectedRows = $stmt->affected_rows;
         $stmt->close();
       }
       $dbConVar->closeConnectionObject($conn);
       return $affectedRows;
     }

     public function deletePostsFromChannel($msgID, $channelName, $workspaceUrl) {
       $dbConVar = new dbConnect();
       $conn = $dbConVar->createConnectionObject();
       $affectedRows = NULL;
       $chId = $this->getChannelId($channelName, $workspaceUrl);
       if ($chId != NULL && $chId > 0) {
         $delPosts = "DELETE FROM channel_messages
                      WHERE (msg_id = ? OR dependency = ?)
                      AND channel_id = ?";
        $stmt = $conn->prepare($delPosts);
        $stmt->bind_param("sss", $msgID, $msgID, $chId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
       }
       $dbConVar->closeConnectionObject($conn);
       return $affectedRows;
     }

     public function updateChannelStatus($channelName, $workspaceUrl, $status) {
       $dbConVar = new dbConnect();
       $conn = $dbConVar->createConnectionObject();
       $affectedRows = NULL;
       $chId = $this->getChannelId($channelName, $workspaceUrl);
       if ($chId != NULL && $chId > 0) {
         //$status = $this->validMySQL($conn, $status);
         $updateStatus = "UPDATE workspace_channels
                          SET status = ?
                          WHERE channel_id = ?";
         $stmt = $conn->prepare($updateStatus);
         $stmt->bind_param("ss", $status, $chId);
         $stmt->execute();
         $affectedRows = $stmt->affected_rows;
         $stmt->close();
       }
       $dbConVar->closeConnectionObject($conn);
       return $affectedRows;
     }

     public function getChannelStatus($channelName, $workspaceUrl) {
       $dbConVar = new dbConnect();
       $conn = $dbConVar->createConnectionObject();
       $statusString = NULL;
       $chId = $this->getChannelId($channelName, $workspaceUrl);
       if ($chId != NULL && $chId > 0) {
         $getChStatus = "SELECT status
                         FROM workspace_channels
                         WHERE channel_id = $chId";
         $result = mysqli_query($conn, $getChStatus);
         if (mysqli_num_rows($result) > 0)
          {
            while ($row = $result->fetch_assoc()) {

              $statusString = $this->validateInputs($row['status']);
            }
          }
       }
       if (isset($result)) {
       //if ($result) {
         mysqli_free_result($result);
       }
       $dbConVar->closeConnectionObject($conn);
       return $statusString;
     }

    public function getUserMembership($name, $workspace) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userMembership = array();
      $getMembership = "SELECT DISTINCT channel_name, W.type
                        FROM workspace_channels AS W INNER JOIN inside_channel AS I
                        ON W.channel_id = I.channel_id
                        WHERE I.user_id = '$name' AND W.url = '$workspace'";
      $result = mysqli_query($conn, $getMembership);
      if (mysqli_num_rows($result) > 0)
       {
         while ($row = $result->fetch_assoc())
         {
           $row['channel_name'] = $this->validateInputs($row['channel_name']);
           $row['type'] = $this->validateInputs($row['type']);
           array_push($userMembership, $row);
         }
       }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $userMembership;

    }

    public function retrieveUsersFromWrkspace($workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userList = array();
      $getUsers = "SELECT U.user_id AS user_id
                   FROM user_info AS U INNER JOIN workspace AS W
                   WHERE U.user_id = W.user_id AND W.url = '$workspaceUrl'";
      $result = mysqli_query($conn, $getUsers);
      if (mysqli_num_rows($result) > 0) {
        while ($row = $result->fetch_assoc())
        {
          $row['user_id'] = $this->validateInputs($row['user_id']);
          array_push($userList, $row['user_id']);
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $userList;
    }

    public function retrievePatternMatchedUsers($keyword, $workspace) {
      $dbConVar = new dbConnect();
      $pattern = "%".$keyword."%";
      $conn = $dbConVar->createConnectionObject();
      $userList = array();
      $retUsers = "SELECT U.user_id AS user_id, first_name, last_name, display_name
                   FROM user_info AS U INNER JOIN workspace AS W ON U.user_id = W.user_id
                   WHERE U.user_id LIKE '$pattern' AND W.url = '$workspace'";
      $result = mysqli_query($conn, $retUsers);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $row["user_id"] = $this->validateInputs($row["user_id"]);
          array_push($userList, $row);
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $userList;
    }

    public function retUsersFromChannel($channelName, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userList = array();
      $chId = $this->getChannelId($channelName, $workspaceUrl);
      if ($chId != NULL && $chId > 0) {
        $getUsersFromCh = "SELECT user_id
                           FROM inside_channel
                           WHERE channel_id = $chId";
        $result = mysqli_query($conn, $getUsersFromCh);
       if (mysqli_num_rows($result) > 0)
       {
         while ($row = $result->fetch_assoc())
         {
           $row["user_id"] = $this->validateInputs($row["user_id"]);
           array_push($userList, $row["user_id"]);
         }
       }
        mysqli_free_result($result);
      }

      $dbConVar->closeConnectionObject($conn);
      return $userList;
    }

    public function retInviteUsersForChannel($channelName, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userList = array();
      $chId = $this->getChannelId($channelName, $workspaceUrl);
      if ($chId != NULL && $chId > 0) {
        $getUsersFromCh = "SELECT U.user_id AS user_id
                           FROM user_info AS U INNER JOIN workspace AS W
                           WHERE U.user_id = W.user_id AND W.url = '$workspaceUrl'
                           AND U.user_id NOT IN (
                             SELECT user_id
                             FROM inside_channel
                             WHERE channel_id = $chId
                           )";
        $result = mysqli_query($conn, $getUsersFromCh);
       if (mysqli_num_rows($result) > 0)
       {
         while ($row = $result->fetch_assoc())
         {
           $row["user_id"] = $this->validateInputs($row["user_id"]);
           array_push($userList, $row["user_id"]);
         }
       }
        mysqli_free_result($result);
      }

      $dbConVar->closeConnectionObject($conn);
      return $userList;
    }

    public function retChannelsOfUser($userID, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $channelList = array();
      $getChannels = "SELECT channel_name
                      FROM inside_channel AS I INNER JOIN workspace_channels AS W
                      ON I.channel_id = W.channel_id
                      WHERE I.user_id = '$userID' AND W.url = '$workspaceUrl'";
      $result = mysqli_query($conn, $getChannels);
      if (mysqli_num_rows($result) > 0)
      {
       while ($row = $result->fetch_assoc())
       {
         $row['channel_name'] = $this->validateInputs($row['channel_name']);
         array_push($channelList, $row['channel_name']);
       }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $channelList;
    }

    public function retrieveRxnMetrics($userID, $workspace) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $rxnMetrics = array();
      $pattern = ";".$userID.";"; // ";".$_SESSION['userid'].";";
      $getRxnMetrics = "SELECT channel_name, B.emo_name AS emo_name, COUNT(B.emo_name) AS emo_count
                     FROM (
                     SELECT C.channel_id, C.user_id, C.msg_id, R.emo_id, emo_name
                     FROM emoticons INNER JOIN reactions AS R ON emoticons.emo_id = R.emo_id
                     INNER JOIN channel_messages AS C ON C.msg_id = R.msg_id
                     WHERE R.users LIKE '%{$pattern}%'
                     ) B INNER JOIN workspace_channels AS W ON B.channel_id = W.channel_id
                     AND W.url = '$workspace'
                     GROUP BY channel_name, B.emo_name";
      $result = mysqli_query($conn, $getRxnMetrics);
     if (mysqli_num_rows($result) > 0)
     {
       while ($row = $result->fetch_assoc())
       {
         $row['channel_name'] = $this->validateInputs($row['channel_name']);
         $row['emo_name'] = $this->validateInputs($row['emo_name']);
         $row['emo_count'] = $this->validateInputs($row['emo_count']);
         array_push($rxnMetrics, $row);
       }
     }
     mysqli_free_result($result);
     $dbConVar->closeConnectionObject($conn);
     return $rxnMetrics;
   }

   public function retrievePostMetrics($userID, $workspace) {
     $dbConVar = new dbConnect();
     $conn = $dbConVar->createConnectionObject();
     $postMetrics = array();
     $getPostMetrics = "SELECT channel_name, COUNT(G.msg_id) AS msg_count
                        FROM (
                        SELECT channel_name, M.channel_id, M.msg_id
                        FROM channel_messages AS M INNER JOIN workspace_channels AS W
                        ON M.channel_id = W.channel_id
                        WHERE M.user_id = '".$userID."' AND W.url = '$workspace'
                        ) G
                        GROUP BY channel_name";
     $result = mysqli_query($conn, $getPostMetrics);
     if (mysqli_num_rows($result) > 0)
     {
       while ($row = $result->fetch_assoc())
       {
         $row['channel_name'] = $this->validateInputs($row['channel_name']);
         $row['msg_count'] = $this->validateInputs($row['msg_count']);
         array_push($postMetrics, $row);
       }
     }
     mysqli_free_result($result);
     $dbConVar->closeConnectionObject($conn);
     return $postMetrics;

   }

   public function retrieveNoChMsgs($userID, $workspace) {
     $dbConVar = new dbConnect();
     $conn = $dbConVar->createConnectionObject();
     $totalMsgs = array();
     $getTotalMsgs = "SELECT channel_name, COUNT(msg_id) AS msg_count
                      FROM channel_messages AS M INNER JOIN workspace_channels AS W
                      ON M.channel_id = W.channel_id
                      WHERE W.url = '$workspace' AND M.channel_id IN (
                        SELECT channel_id
                        FROM inside_channel
                        WHERE user_id = '".$userID."')
                      GROUP BY channel_name";
    $result = mysqli_query($conn, $getTotalMsgs);
    if (mysqli_num_rows($result) > 0)
    {
      while ($row = $result->fetch_assoc())
      {
        array_push($totalMsgs, $row);
      }
    }
    mysqli_free_result($result);
    $dbConVar->closeConnectionObject($conn);
    return $totalMsgs;
   }

   public function retrieveNoChRxns($userID, $workspace) {
     $dbConVar = new dbConnect();
     $conn = $dbConVar->createConnectionObject();
     $pattern = ";".$userID.";";
     $totalRxns = array();
     $getTotalRxns = "SELECT W.channel_name, COUNT(R.emo_id) AS rxn_count
                      FROM reactions AS R INNER JOIN channel_messages AS M
                      ON R.msg_id = M.msg_id INNER JOIN workspace_channels
                      AS W ON M.channel_id = W.channel_id
                      WHERE W.url = '$workspace' AND (R.users IS NOT NULL AND R.users != '') AND M.channel_id IN (
                       SELECT channel_id
                       FROM inside_channel
                       WHERE user_id = '".$userID."')
                       GROUP BY W.channel_name";
     $result = mysqli_query($conn, $getTotalRxns);
     if (mysqli_num_rows($result) > 0)
     {
       while ($row = $result->fetch_assoc())
       {
         array_push($totalRxns, $row);
       }
     }
     mysqli_free_result($result);
     $dbConVar->closeConnectionObject($conn);
     return $totalRxns;
     }


     public function retrieveRelPostMetrics($userID, $workspace) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $relMsgs = array();
      $getRelMsgs = "SELECT channel_name, MAX(msg_count) AS max_count
                     FROM (
                     SELECT channel_name, M.user_id as user_id, COUNT(msg_id) AS msg_count
                     FROM channel_messages AS M INNER JOIN workspace_channels AS W
                     ON M.channel_id = W.channel_id
                     WHERE W.url = '$workspace' AND M.channel_id IN (
                        SELECT channel_id
                        FROM inside_channel
                        WHERE user_id = '".$userID."')
                     GROUP BY channel_name, M.user_id) TEMP
                     GROUP BY channel_name";
      $result = mysqli_query($conn, $getRelMsgs);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc()) {
          $row['channel_name'] = $this->validateInputs($row['channel_name']);
          $row['max_count'] = $this->validateInputs($row['max_count']);
          array_push($relMsgs, $row);
        }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $relMsgs;
    }

    public function retUserChannelRelRxnMetrics($userID, $channelName, $workspace) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $pattern = ";".$userID.";";
      //$relRxns = array();
      $rxnCount = NULL;
      $getRelMsgs = "SELECT channel_name, SUM(emo_count) AS emo_count
                     FROM (
                     SELECT channel_name, B.emo_name AS emo_name, COUNT(B.emo_name) AS emo_count
                     FROM (
                     SELECT C.channel_id, C.user_id, C.msg_id, R.emo_id, emo_name
                     FROM emoticons INNER JOIN reactions AS R ON emoticons.emo_id = R.emo_id
                     INNER JOIN channel_messages AS C ON C.msg_id = R.msg_id
                     WHERE R.users LIKE '%{$pattern}%'
                     ) B INNER JOIN workspace_channels AS W ON B.channel_id = W.channel_id
                     AND W.url = '$workspace' AND channel_name = '$channelName'
                     GROUP BY B.channel_id, B.emo_name) TEMP
                     GROUP BY channel_name";
      $result = mysqli_query($conn, $getRelMsgs);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc()) {
          //array_push($relRxns, $row);
          $rxnCount = $this->validateInputs($row['emo_count']);
        }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $rxnCount;
    }

    public function retrieveRelRxnMetrics($userID, $workspace) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $pattern = ";".$userID.";";
      $relRxns = array();
      // $getRelRxns = "     SELECT REF.user_id
      //                     FROM inside_channel AS REF
      //                     WHERE REF.channel_id IN (
      //                       SELECT * FROM (
      //                         SELECT W.channel_name, M.channel_id, M.user_id, R.msg_id, R.emo_id, R.users
      //                         FROM reactions AS R INNER JOIN channel_messages AS M
      //                         ON R.msg_id = M.msg_id INNER JOIN workspace_channels
      //                         AS W ON M.channel_id = W.channel_id
      //                         WHERE W.url = '$workspace' AND (R.users IS NOT NULL AND R.users != '') AND M.channel_id IN (
      //                             SELECT channel_id
      //                             FROM inside_channel
      //                             WHERE user_id = '".$userID."')
      //                       ) TEMP
      //                     )";
      $getRelRxns = "SELECT channel_name, MAX(rxn_count) AS max_rx_count
                     FROM (
                     SELECT W.channel_name, M.user_id, COUNT(R.emo_id) AS rxn_count
                     FROM reactions AS R INNER JOIN channel_messages AS M
                     ON R.msg_id = M.msg_id INNER JOIN workspace_channels
                     AS W ON M.channel_id = W.channel_id
                     WHERE W.url = '$workspace' AND (R.users IS NOT NULL AND R.users != '') AND M.channel_id IN (
                      SELECT channel_id
                      FROM inside_channel
                      WHERE user_id = '".$userID."')
                      GROUP BY W.channel_name, M.user_id) TEMP
                      GROUP BY channel_name";
      $result = mysqli_query($conn, $getRelRxns);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc()) {
          array_push($relRxns, $row);
        }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $relRxns;
    }

    public function retrieveChannels($workspaceUrl)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $this->channels = array();//('apple','baana'); //array(array("channel"=> NULL, "type"=>NULL));
      $channelType = array();//('Public','Private');
      $combinedArray = array();
      $retChannels = "SELECT channel_name, type, status
                      FROM workspace_channels
                      WHERE url = '$workspaceUrl' AND channel_id IN (
                      SELECT DISTINCT channel_id
                      FROM inside_channel
                      WHERE user_id = '".$_SESSION['userid']."')";
      $result = mysqli_query($conn, $retChannels);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
            //array_push($this->channels, $this->validateInputs($row['channel_name']));
            //array_push($this->channelType, $this->validateInputs($row['type']));
            $temp = array("channel"=>$this->validateInputs($row['channel_name']), "type"=>$this->validateInputs($row['type']),
                          "status"=>$this->validateInputs($row['status']));
            array_push($combinedArray, $temp);
        }

      }
      //$combinedArray = array_combine($this->channels, $channelType);
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $combinedArray;
    }

    public function retrieveMessages($channelName, $workspaceUrl,$retChannel)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $this->messages = array();
      $retMessages = "SELECT *
                      FROM (
                      SELECT channel_id, channel_messages.user_id AS userID, first_name, last_name, avatar, message,
                      image_path, file_path, snippet, msg_id, created_time, type
                      FROM channel_messages INNER JOIN user_info on channel_messages.user_id = user_info.user_id
                      WHERE (type = 1 OR type = 2) AND channel_id
                      IN (
                      SELECT channel_id
                      FROM workspace_channels
                      WHERE channel_name = '$channelName' AND url = '$workspaceUrl')
                      ORDER BY created_time DESC LIMIT $retChannel,10) TEMP
                      ORDER BY created_time ASC";
      $result = mysqli_query($conn, $retMessages);
      if (mysqli_num_rows($result) > 0)
      {
          while ($row = $result->fetch_assoc())
          {
            $row['message'] = $this->validateInputs($row['message']);
            $row['image_path'] = $this->validateInputs($row['image_path']);
            $row['file_path'] = $this->validateInputs($row['file_path']);
            $row['snippet'] = $this->validateInputs($row['snippet']);
            array_push($this->messages, $row);
          }
      }
      if ($result) {
      mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $this->messages;
      // return $retMessages;
    }

    public function getChannelId($channelName, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $chId = NULL;
      $getChannelId = "SELECT channel_id
                       FROM workspace_channels
                       WHERE channel_name = '$channelName' AND url = '$workspaceUrl'";
      $result = mysqli_query($conn, $getChannelId);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $chId = $row['channel_id'];
        }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $chId;
    }

    public function getChannelFromMsg($msgID) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $chName = NULL;
      $getChName = "SELECT channel_name
                    FROM channel_messages AS M INNER JOIN workspace_channels AS W
                    ON M.channel_id = W.channel_id
                    WHERE msg_id = $msgID";
      $result = mysqli_query($conn, $getChName);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $chName = $this->validateInputs($row['channel_name']);
        }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $chName;
    }

    public function insertMessage($channelName, $message, $imagePath, $filePath, $snippet, $threadId, $type, $workspaceUrl)
    {
      $temp=array();
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $affectedRows = NULL;
      $chId = $this->getChannelId($channelName, $workspaceUrl);
      if ($chId != NULL && $chId > 0)
      {
        $msgId = NULL;
        $getMsgId = "SELECT msg_id
                     FROM channel_messages
                     ORDER BY msg_id
                     DESC LIMIT 1";
        $result = mysqli_query($conn, $getMsgId);
        if (mysqli_num_rows($result) > 0)
        {
          while ($row = $result->fetch_assoc())
          {
            $msgId = $row['msg_id'];
          }
        } else {
          $msgId = 0;
        }
        if ($result) {
          mysqli_free_result($result);
        }
        if ($msgId >= 0) {
          $msgId++;
          $dependency = NULL;
          if ($threadId == NULL) {
            $dependency = $msgId;
          } else {
            $dependency = $threadId;
          }
          //$message = $this->validMySQL($conn, $message);
          //$imagePath = $this->validMySQL($conn, $imagePath);
          //$snippet = $this->validMySQL($conn, $snippet);
          $stmt = $conn->prepare("INSERT INTO channel_messages (channel_id, user_id, msg_id, message,
                                  image_path, file_path, snippet, type, dependency)
                                  VALUES (?,?,?,?,?,?,?,?,?)");
          $stmt->bind_param("sssssssss", $chId, $_SESSION['userid'], $msgId, $message, $imagePath, $filePath, $snippet, $type, $dependency);
          $stmt->execute();
          //if ($stmt->affected_rows > 0) {}
          $affectedRows = $stmt->affected_rows;
          $stmt->close();
        }
    }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function updateMessageType($threadId, $type) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $updateType = "UPDATE channel_messages
                      SET type = ?
                      WHERE msg_id = ?";
      $stmt = $conn->prepare($updateType);
      $stmt->bind_param("ss", $type, $threadId);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      return $affectedRows;
    }

    public function addToDirectMsgList($userID, $recipient) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $sql = "INSERT INTO inside_direct_msg (user_id, recipient)
              VALUES (?,?)";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $userID, $recipient);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      return $affectedRows;
    }

    public function addUserToChannel($userId, $channelName, $workspaceUrl)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $channelId = NULL;
      $affectedRows = 0;
      $channelId = $this->getChannelId($channelName, $workspaceUrl);
      if ($channelId != NULL && $channelId > 0) {
        //$userId = $this->validMySQL($conn,$userId);
        $stmt = $conn->prepare("INSERT INTO inside_channel (channel_id, user_id)
                                VALUES (?,?)");
        $stmt->bind_param("ss", $channelId, $userId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
      }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function checkChannelExists($channelName, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $isChannelExists = "SELECT channel_name
                          FROM workspace_channels
                          WHERE channel_name = '$channelName' AND url = '$workspaceUrl'";
      $result = mysqli_query($conn, $isChannelExists);
      $affectedRows = $conn->affected_rows;
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function createChannel($channelName, $purpose, $type, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $isUserAdded = 0;
      $channelId = NULL;
      $affectedRows = 0;
      $channelExists = $this->checkChannelExists($channelName, $workspaceUrl);
      if ($channelExists == 0) {
        //$channelName = $this->validMySQL($conn, $channelName);
        //$purpose = $this->validMySQL($conn, $purpose);
        $stmt = $conn->prepare("INSERT INTO workspace_channels (channel_id, channel_name, purpose, url, user_id, type)
                                VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $channelId, $channelName, $purpose, $workspaceUrl, $_SESSION['userid'], $type);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        // if ($affectedRows == 1) {
        //   $isUserAdded = $this->addUserToChannel($_SESSION['userid'], $channelName, $workspaceUrl);
        // }
      }
      $dbConVar->closeConnectionObject($conn);
      //$result = array("channelStatus" => $affectedRows, "userStatus" => $isUserAdded);
      //return $result;
      return $affectedRows;
    }

    public function retrieveReplies($threadId) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $this->replies = array();
      $getReplies = "SELECT channel_messages.user_id, first_name, last_name, avatar,image_path,file_path,
                     snippet, msg_id, message, created_time
                     FROM channel_messages INNER JOIN user_info on channel_messages.user_id = user_info.user_id
                     WHERE dependency = $threadId
                     ORDER BY created_time ASC";
      $result = mysqli_query($conn, $getReplies);
      if (mysqli_num_rows($result) > 0) {
        while ($row = $result->fetch_assoc())
        {
          $row['message'] = $this->validateInputs($row['message']);
          $row['first_name'] = $this->validateInputs($row['first_name']);
          $row['last_name'] = $this->validateInputs($row['last_name']);
          $row['image_path'] = $this->validateInputs($row['image_path']);
          $row['file_path'] = $this->validateInputs($row['file_path']);
          $row['snippet'] = $this->validateInputs($row['snippet']);
          array_push($this->replies, $row);
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $this->replies;
    }

    public function getInfoForMsgReaction($msgId, $emoId) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $info = array();
      $getUsers = "SELECT users, count
                   FROM reactions
                   WHERE msg_id = $msgId AND emo_id = $emoId";
      $result = mysqli_query($conn, $getUsers);
      if (mysqli_num_rows($result) == 1) {
        while ($row = $result->fetch_assoc()) {
          $row['users'] = $this->validateInputs($row['users']);
          $row['count'] = $this->validateInputs($row['count']);
          $info['users'] = $row['users'];
          $info['count'] = $row['count'];
          //array_push($info, $row);
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $info;
    }

    public function getEmoId($emoName) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $emoId = NULL;
      $getEmoId = "SELECT emo_id
                   FROM emoticons
                   WHERE emo_name = '$emoName'";
      $result = mysqli_query($conn, $getEmoId);
      if (mysqli_num_rows($result) == 1) {
        while ($row = $result->fetch_assoc()) {
          $emoId = $row['emo_id'];
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $emoId;
    }

    public function handleUserReaction($msgId, $emoId, $info, $isInsert, $isUpdate) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $users = NULL;
      $count = NULL;
      if ($info != NULL && $info['users'] != NULL && !empty($info['users'])) {
      $users = $info['users'];
      }
      if ($isInsert == "true") {
        $count = $info['count'] + 1;
      } else {
        if ($info['count'] > 0) {
          $count = $info['count'] - 1;
        } else {
          $count = 0;
        }
      }
      $affectedRows = NULL;
      if ($emoId > 0 && $count >= 0) {
        if($users != NULL && !empty($users)) {
            if ($isInsert == "true") {
              $users = $users.$_SESSION['userid'].";";
              //$users = "VVVVdeelled";
            } else {
              if ($info['count'] == 1) {
                $users = NULL;
              } else {
                $users = str_replace(";".$_SESSION['userid'].";", ";", $users);
                //$users = "deelled";
              }
            }
        } else {
            if ($isInsert == "true") {
              //$users = "dALASOOOO";
              $users = ";".$_SESSION['userid'].";";
            } else {
              $users = NULL;
            }
        }

        if ($isUpdate == "true") {
          $query = "UPDATE reactions
                   SET users=?, count=?
                   WHERE msg_id=? AND emo_id=?";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("ssss", $users, $count, $msgId, $emoId);
          $stmt->execute();
          $affectedRows = $stmt->affected_rows;
          $stmt->close();
        } else {
          $query = "INSERT INTO reactions (msg_id, emo_id, users, count)
                    VALUES (?,?,?,?)";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("ssss", $msgId, $emoId, $users, $count);
          $stmt->execute();
          $affectedRows = $stmt->affected_rows;
          $stmt->close();
        }
      }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function insertDirectMessage($fromID, $toID, $message, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $insDirectMsg = "INSERT INTO direct_message (user1, user2, direct_message, url)
                       VALUE (?,?,?,?)";
      $stmt = $conn->prepare($insDirectMsg);
      $stmt->bind_param("ssss", $fromID, $toID, $message, $workspaceUrl);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function updateTwoFactor($userID, $isEnable) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $update = "UPDATE user_info
                 SET two_factor = ?
                 WHERE user_id = ?";
      $stmt = $conn->prepare($update);
      $stmt->bind_param("ss", $isEnable, $userID);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }
  }

?>
