<?php


/****************** Helper Functions ******************************/

function clean($string){
    return htmlentities($string);

}

function redirect($location){

    return header("Location: {$location}");

}

function set_message($message){

    if (!empty($message)){

        $_SESSION['message'] = $message;

    } else{

        $message = "";
    }
}

function display_message(){

    if (isset($_SESSION['message'])){

        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}

function token_generator(){

    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));

    return $token;

}

function validation_errors($error_message){
    $error_message = <<<DELIMITER
             <div class="alert alert-danger">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <strong>Warning!</strong> $error_message
</div>'
DELIMITER;
    return $error_message;
}



function email_exists($email){

    $sql = "SELECT id FROM users WHERE email = '$email'";

    $result = query($sql);

    if (row_count($result) == 1){

        return true;

    }else {
        return false;
    }

}
function username_exists($username){

    $sql = "SELECT id FROM users WHERE username = '$username'";

    $result = query($sql);

    if (row_count($result) == 1){

        return true;

    }else {
        return false;
    }

}

function send_email($email,$subject,$msg,$headers){

return mail($email,$subject,$msg,$headers);
}


/****************** Validation Functions ******************************/

/* Validate the user registration  */

function validate_user_registration(){

    $errors     = [];

    $min        = 3;
    $max        = 30;

    if ($_SERVER['REQUEST_METHOD'] == "POST"){

        $firstName          = clean($_POST['firstName']);
        $lastName           = clean($_POST['lastName']);
        $username           = clean($_POST['username']);
        $email              = clean($_POST['email']);
        $password           = clean($_POST['password']);
        $confirm_password   = clean($_POST['confirm_password']);


        if (strlen($firstName) < $min){

            $errors[]   = "Your first name can not be less than {$min} characters <br>";
        }
        if (strlen($firstName) > $max){

            $errors[]   = "Your first name can not be more than {$max} characters <br>";
        }
        if (strlen($lastName) < $min){

            $errors[]   = "Your last name can not be less than {$min} characters <br>";
        }
        if (strlen($lastName) > $max){

            $errors[]   = "Your first name can not be more than {$max} characters <br>";
        }
        if (strlen($username) < $min){

            $errors[]   = "Your Username can not be less than {$min} characters <br>";
        }
        if (strlen($username) > $max){

            $errors[]   = "Your Username can not be more than {$max} characters <br>";
        }

        if (username_exists($username)){
            $errors[]   = "Sorry, your username is already registered <br>";

        }

        if (email_exists($email)){
            $errors[]   = "Sorry, your email address already exists <br>";

        }


        if (strlen($email) > $max){

            $errors[]   = "Your Email can not be more than {$max} characters <br>";
        }
        if ($password !== $confirm_password){

            $errors[]   = "Your passwords do not match <br>";
        }

        if (!empty($errors)){

            foreach ($errors as $error){

                echo validation_errors($error);

            }
        }else{

            if (register_user($firstName,$lastName,$username,$email,$password)){

                set_message("<p class='bg-success text-center'>Please check your email or spam folder for activation link</p>");

                redirect("index.php");

                echo "User Registered";
            }
        }
    }
}


/****************** Register User Functions ******************************/

function  register_user($firstName,$lastName,$username,$email,$password)
{

    $firstName = escape($firstName);
    $lastName = escape($lastName);
    $username = escape($username);
    $email = escape($email);
    $password = escape($password);


    if (email_exists($email)) {
        return false;

    } elseif (username_exists($username)) {
            return false;

    } else {

        $password = md5($password);

        $validation_code = md5($username . microtime());

        $sql = "INSERT INTO users (first_name,last_name,username,email,password,validation_code,active) ";
        $sql .= " VALUES('$firstName','$lastName','$username','$email','$password','$validation_code',0)";

        $result = query($sql);

        confirm($result);

        $subject    = "Activate Account";
        $msg        = "Welcome $firstName,
        Please click on the link below to activate your Account:        
        http://localhost/login_system/activate.php?email=$email&code=$validation_code
        ";
        $headers    = "From: noreply@mysite.com";

        send_email($email,$subject,$msg,$headers);

        return true;

    }
}

/****************** Activate User Functions ******************************/

function activate_user(){

    if ($_SERVER['REQUEST_METHOD'] == "GET"){

        if (isset($_GET['email'])){

            $email              = clean($_GET['email']);

            $activation_code    = clean($_GET['code']);

            $sql = "SELECT id FROM users WHERE email= '".escape($_GET['email'])."' AND validation_code='".escape($_GET['code'])."' ";

            $result = query($sql);

            confirm($result);

            if (row_count($result) == 1){

                $sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email= '".escape($email)."' AND validation_code='".escape($activation_code)."' ";

                $result2 = query($sql2);

                confirm($result2);

                set_message("<p class='bg-success'>Your account has been activated. Please login</p>");

                redirect(login.php);
            } else{

                set_message("<p class='bg-success'>Sorry Your account could not be activated. Please try later</p>");

                redirect(login.php);
            }

        }
    }

}

/****************** Validate User Login Functions ******************************/

function validate_user_login()
{

    $errors = [];

    $min = 3;
    $max = 30;

    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        $email              = clean($_POST['email']);
        $password           = clean($_POST['password']);
        $remember           = isset($_POST['remember']);


        if (empty($email)){

            $errors[] = "Email field can not be empty ";
        }
        if (empty($password)){

            $errors[] = "Password field can not be empty ";
        }

        if (!empty($errors)){

            foreach ($errors as $error){

                echo validation_errors($error);

            }
        }else{

            if (user_login($email,$password,$remember)){

                redirect("admin.php");

            }else{

                echo "Your credentials are invalid";
            }


        }
    }
} // FUNCTION


/****************** User Login Functions ******************************/

function user_login($email,$password,$remember){

    $sql = "SELECT password, id FROM users WHERE email = '".escape($email)."' AND active = 1 ";

    $result = query($sql);

    if (row_count($result) == 1){

        $row = fetch_array($result);

        $db_password = $row['password'];

        if (md5($password) === $db_password){

            if ($remember == "on"){

                setcookie('email',$email,time() + 86400);
            }

            $_SESSION['email'] = $email;

            return true;
        }else{

            return false;
        }

        return true;
    }else{

        return false;
    }

} // end of function

/****************** Logged_in Function ******************************/

function logged_in(){

    if (isset($_SESSION['email']) || isset($_COOKIE['email'])){

        return true;
    }else{
        return false;
    }

}  // Functions end


/****************** Reset Password Function ******************************/

    function recover_password(){


        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            if (isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){

                $email = clean($_POST['email']);

                if (email_exists($email)){

                    $validation_code = md5($email . microtime());
                    setcookie('temp_access_code',$validation_code,time() + 60);

                    $sql = "UPDATE users SET validation_code = '".$validation_code."' WHERE email='".escape($email)."'";
                    $result = query($sql);
                    confirm($result);

                    $subject = "reset your password";
                    $msg = "Here is your password reset code: {$validation_code}
                    
                    Click here to reset your password http://localhost/code.php?email=$email&code=$validation_code
                    ";

                    $header = "From: me@loanspur.com";

                    if (!send_email($email,$subject,$msg,$header)){

                        echo validation_errors("This email could not be sent");

                    }

                    set_message("<p class='bg-success text-center'>Please check your email for password reset code</p>");

                    redirect("index.php");


                } else{

                    echo validation_errors("This email does not exist");
                }


            }else{

                redirect("index.php");
            }


            // token checks


        }

    } // post request


/****************** Code Validation on reset Function ******************************/


    function  validate_code(){
        if (isset($_COOKIE['temp_access_code'])){

                if (!isset($_GET['email']) && !isset($_GET['code'])){

                    redirect("index.php");

                }elseif (empty($_GET['email']) || empty($_GET['code'])){

                    redirect("index.php");

                }else{


                    if (isset($_POST['code'])){

                        echo "getting post from form";
                    }
                }



        }else{
            set_message("<p class='bg-danger text-center'>Sorry your session has expired. Try again later.</p>");

            redirect("recover.php");

        }


    }


