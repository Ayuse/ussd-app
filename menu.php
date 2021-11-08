<?php

include_once "util.php";
include_once "user.php";
include_once "util.php";

class Menu
{
    protected $text;
    protected $sessionID;

    function  __construct()
    {
    }

    public function mainMenuRegistered($name)
    {
        $response = "Welcome  $name  Reply with\n";
        $response .= "1. Send money\n";
        $response .= "2. Withdraw\n";
        $response .= "3. Check balance\n";
        return  $response;
    }

    public function mainMenuUnRegistered()
    {
        $response = "CON Welcome to this app. Reply wth\n";
        $response .= "1. Register\n";
        echo $response;
    }

    public function registerMenu($textArray, $phoneNumber, $pdo)
    {
        $level = count($textArray);
        if ($level == 1) {
            echo "CON Please enter your your full name:";
        } else if ($level == 2) {
            echo "CON Please set your PIN:";
        } else if ($level == 3) {
            echo "CON Please confirm your PIN";
        } else if ($level == 4) {
            $name = $textArray[1];
            $pin  = $textArray[2];
            $confirmPin = $textArray[3];
            if ($pin != $confirmPin) {
                echo "END Your pins do not match, Please try again";
            } else {
                $user = new User($phoneNumber);
                $user->setName($name);
                $user->setPin($pin);
                $user->setBalance(Util::$USER_BALANCE);
                $user->register($pdo);
                echo "END You have been registered";
            }
        }
    }

    public function sendMoneyMenu($textArray)
    {
        $level = count($textArray);
        if ($level == 1) {
            $response = "CON Select Bank \n";
            $response .= "1. Gtbank\n";
            $response .= "2. First Bank\n";
            $response .= "3. Zenith Bank\n";
            $response .= "4. Access Bank\n";
            echo $response;
        } else if ($level == 2) {
            echo  "CON Enter Account Number";
        } else if ($level == 3) {
            echo "CON Enter Recharge Pin Number";
        } else if ($level == 4) {
            $response = "CON Send" . " N500 to " . $textArray[2] . "\n";
            $response .= "1. Confirm\n";
            $response .= "2. Cancel\n";
            $response .= Util::$GO_BACK . " Back\n";
            $response .= Util::$GO_to_MAIN_MENU . "Main menu\n";
            echo $response;
        } else if ($level == 5 && $textArray[4] == 1) {
            $response = "END Your request is being processed\n";

            $response .= "END successful\n";

            echo $response;
        } else if ($level == 5 && $textArray[4] == 2) {
            echo "END Thank you for using our service";
        } else if ($level == 5 && $textArray[4] == Util::$GO_BACK) {
            echo "END You have requested to go back";
        } else if ($level == 5 && $textArray[4] == Util::$GO_to_MAIN_MENU) {
            echo "END You have requested to back to the main menu";
        } else {
            echo "END Invalid entry";
        }
    }

    public function withdrawMoneyMenu($textArray)
    {
        $level = count($textArray);
        if ($level == 1) {
            echo "CON Enter agent number";
        } else if ($level == 2) {
            echo "CON Enter amount:";
        } else if ($level == 3) {
            echo "CON Enter your PIN:";
        } else if ($level == 4) {
            echo " CON Withdraw" . " N" .  $textArray[3] . " from Agent" . $textArray[1] . "\n 1. Confirm\n 2. Cancel\n";
        } else if ($level == 5 && $textArray[4] == 1) {
            //confirm
            echo "END Your request is being processed";
        } else if ($level == 5 && $textArray[4] == 2) {
            //cancel
            echo "END Thank you";
        } else {

            echo "END Invalid entry";
        }
    }

    public function checkBalanceMenu($textArray)
    {
        $level = count($textArray);
        if ($level == 1) {
            echo "CON Enter PIN";
        } else if ($level == 2) {
            echo "END We are processing your request and you will receive an SMS shortly";
        } else {
            echo "END Invalid entry";
        }
    }

    public function middleware($text, $user, $sessionId, $pdo)
    {
        //remove entries for going back or going to the main menu
        return $this->invalidEntry($this->goBack($this->gotoMainMenu($text)), $user, $sessionId, $pdo);
    }

    public function goBack($text)
    {
        $explodedText = explode("*", $text);
        while (array_search(Util::$GO_BACK, $explodedText) != false) {
            $firstIndex = array_search(Util::$GO_BACK, $explodedText);
            array_splice($explodedText, $firstIndex - 1, 2);
        }
        return join("*", $explodedText);
    }

    public function gotoMainMenu($text)
    {

        $explodedText = explode("*", $text);
        while (array_search(Util::$GO_to_MAIN_MENU, $explodedText) != false) {
            $firstIndex = array_search(Util::$GO_to_MAIN_MENU, $explodedText);
            $explodedText = array_slice($explodedText, $firstIndex + 1);
        }
        return join("*", $explodedText);
    }

    public function persistInvalidEntry($sessionId, $user, $ussdLevel, $pdo)
    {
        $stmt = $pdo->prepare("insert into ussdsession (sessionId, uid, ussdLevel) values (?,?,?)");
        $stmt->execute([$sessionId, $user->readUserId($pdo), $ussdLevel]);
        $stmt = null;
    }

    public function invalidEntry($ussdStr, $user, $sessionId, $pdo)
    {
        $stmt = $pdo->prepare("select ussdLevel from ussdsession where sessionId=? and uid=?");
        $stmt->execute([$sessionId, $user->readUserId($pdo)]);
        $result = $stmt->fetchAll();

        if (count($result) == 0) {
            return $ussdStr;
        }

        $strArray = explode("*", $ussdStr);

        foreach ($result as $value) {
            unset($strArray[$value['ussdLevel']]);
        }

        $strArray = array_values($strArray);

        return join("*", $strArray);
    }
}
