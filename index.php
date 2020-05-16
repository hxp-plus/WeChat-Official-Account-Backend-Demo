<?php

/* A simple wechat subscription backend written in php 7.0 */
// author : hxp<hxp201406@gmail.com
/* required module:
 * php7.0-xml
 * php-curl
 * Use apt or yum to install them
 */

/* Define your token, appid, appsecret here */
define("TOKEN", "yourtoken");
define("APPID", "yourappid");
define("APPSECRET", "yourappsecret");

session_start();

$wechatObj = new wechatCallbackapiTest();

if (!isset($_GET['echostr'])) {
    if (isset($_GET['getUser'])) {
        $wechatObj->getUserOpenId(); // Get all users, visit http://<your_server_domain>/index.php?getUser to see.
    } else {
        $wechatObj->responseMsg(); // Entry point of your program
    }
} else {
    $wechatObj->isValid(); // Server verifying, will only be executed once.
}

class wechatCallbackapiTest
{
    private $access_token;

    public function __construct() {
        // Init access token
        $this->access_token = $this->getAccessToken();
    }

    public function getAccessToken() {
        // Check if the token is expired
        if ($_SESSION['access_token'] && $_SESSION['expire_time'] > time()) {
            return $_SESSION['access_token'];
        } else {
            // Update token
            $appid = APPID;
            $appsecret = APPSECRET;
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
            $res = $this->https_request($url, 'get', 'json', '');
            $access_token = $res['access_token'];
            $_SESSION['access_token'] = $access_token;
            $_SESSION['expire_time'] = time() + 7200;
            return $access_token;
        }
    }

    public function https_request($url, $type, $res, $arr) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($type == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);
        }

        $output = curl_exec($ch);
        curl_close($ch);

        if ($res == 'json') {
            return json_decode($output, true);
        }
    }

    public function isValid() {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserOpenId() {
        // This Should be working, but I'm not sure because I don't have an account with WeChat Verification.
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=" . $this->access_token;
        $res = $this->https_request($url, 'get', 'json', '');
        $userlist = ($res['data']['openid']);
        var_dump($userlist);
    }

    public function responseMsg() {
        $postArr = file_get_contents("php://input");
        $postObj = simplexml_load_string($postArr);

        // Send user a message if he subscribes
        if (strtolower($postObj->MsgType) == 'event') {
            if (strtolower($postObj->Event) == 'subscribe') {
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = 'text';
                $content = 'Hello, world!';
                $template = "<xml>
                             <ToUserName><![CDATA[%s]]></ToUserName>
                             <FromUserName><![CDATA[%s]]></FromUserName>
                             <CreateTime>%s</CreateTime>
                             <MsgType><![CDATA[%s]]></MsgType>
                             <Content><![CDATA[%s]]></Content>
                             </xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                echo $info;
            }
        }
    }
}
