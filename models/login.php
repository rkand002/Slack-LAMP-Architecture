<?php

  //include_once $_SESSION['basePath'].'errors.php';
  require_once $_SESSION['basePath'].'models/connect.php';

  class LoginModel {
    private $dbConVar;

    public function validMySQL($data) {
      $data = stripslashes($data);
      $data = htmlentities($data);
      $data = strip_tags($data);
      //$data = mysql_real_escape_string($data);
      return $data;
    }

    public function validateInputs($data) {
      $data = trim($data);
      $data = stripslashes($data);
      $data = htmlspecialchars($data);
      return $data;
    }

    public function verifyCredentials($userid, $password)
    {
        $isExists = NULL;
        $dbConVar = new dbConnect();
        $conn = $dbConVar->createConnectionObject();
        $profileInfo = array();
        $userid = mysqli_real_escape_string($conn, $userid);
        $password = mysqli_real_escape_string($conn, $password);
        $isUserExists = "SELECT user_id, password, role, email, two_factor, first_name, last_name
                         FROM user_info
                         where user_id = '$userid' AND password = '$password'";
        $result = mysqli_query($conn, $isUserExists);
        //var_dump($result);
        if (mysqli_num_rows($result) > 0) {
          while ($row = $result->fetch_assoc())
          {
            $row["isExists"] = true;
            array_push($profileInfo, $row);
          }
        } else {
          $row["isExists"] = false;
          array_push($profileInfo, $row);
        }
        $dbConVar->closeConnectionObject($conn);
        return $profileInfo;
    }

    public function checkUserExist($userId, $email)
    {
      //$userIdTmp = NULL;
      //$emailTmp = NULL
      $profile = array('user_id' => NULL, 'email' => NULL);
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();

      $isUserExists = "SELECT user_id, email
                       FROM user_info
                       WHERE user_id = '$userId'";
      $result = mysqli_query($conn, $isUserExists);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $profile['user_id'] = $row['user_id'];
        }
      }
      mysqli_free_result($result);

      $isEmailExists = "SELECT user_id, email
                       FROM user_info
                       WHERE email = '$email'";
      $result = mysqli_query($conn, $isEmailExists);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $profile['email'] = $row['email'];
        }
      }
      mysqli_free_result($result);
      return $profile;
    }

    public function updateTokenForUser($userID, $token, $expire_time, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userID = $this->validMySQL($userID);
      $stmt = NULL;
      $affectedRows = NULL;
      $getUser = "SELECT *
                  FROM token_table
                  WHERE user_id = '$userID' AND
                  user_id IN (
                    SELECT user_id
                    FROM workspace
                    WHERE user_id = '$userID' AND url='$workspaceUrl'
                  )";
      $result = mysqli_query($conn, $getUser);
      if (mysqli_num_rows($result) > 0)
      {
        $stmt = $conn->prepare("UPDATE token_table
                                SET token = ?, expire_time = ?
                                WHERE user_id = ?");
        $stmt->bind_param("sss", $token, $expire_time, $userID);
        $stmt->execute();
      } else {
        $stmt = $conn->prepare("INSERT INTO token_table(user_id, token, expire_time)
                                VALUES(?,?,?)
                              ");
        $stmt->bind_param("sss",$userID, $token, $expire_time);
        $stmt->execute();
      }
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function getTokenForUser($userID, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $tokenDetails = array();
      $getUser = "SELECT *
                  FROM token_table
                  WHERE user_id = '$userID' AND
                  user_id IN (
                    SELECT user_id
                    FROM workspace
                    WHERE user_id = '$userID' AND url='$workspaceUrl'
                  )";
      $result = mysqli_query($conn, $getUser);
      if (mysqli_num_rows($result) > 0) {
        while ($row = $result->fetch_assoc())
        {
          $row['token'] = $this->validateInputs($row['token']);
          $row['expire_time'] = $this->validateInputs($row['expire_time']);
          array_push($tokenDetails, $row);
        }
      }
      if (isset($result)) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $tokenDetails;
    }

    public function addUserToWorkspace($userId, $workspaceUrl, $createdBy)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userId = $this->validMySQL($userId);
      $workspaceUrl = $this->validMySQL($workspaceUrl);
      $createdBy = $this->validMySQL($createdBy);

      $stmt = $conn->prepare("INSERT INTO workspace (url, user_id, created_by)
                              VALUES (?,?,?)");
      $stmt->bind_param("sss", $workspaceUrl, $userId, $createdBy);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function addNewUser($userId, $email, $password, $firstName, $lastName, $avatar, $workspaceUrl)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $userId = $this->validMySQL($userId);
      $email = $this->validMySQL($email);
      $password = $this->validMySQL($password);
      $firstName = $this->validMySQL($firstName);
      $lastName = $this->validMySQL($lastName);
      $avatar = $this->validMySQL($avatar);
      $workspaceUrl = $this->validMySQL($workspaceUrl);

      $stmt = $conn->prepare("INSERT INTO user_info (user_id, password, first_name, last_name, email, avatar)
                              VALUES (?,?,?,?,?,?)");
      $stmt->bind_param("ssssss", $userId, $password, $firstName, $lastName, $email, $avatar);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      $createdBy = 0;
      $isAddedToWrk = $this->addUserToWorkspace($userId, $workspaceUrl, $createdBy);
      if ($isAddedToWrk < 1) {
        //MARK: if user not added in workspace the delete the user from db #consistency
      }
      $dbConVar->closeConnectionObject($conn);
      $response = array('userInsRows' => $affectedRows, 'workspaceInsRows' => $isAddedToWrk);
      return $response;
    }

  }
?>
