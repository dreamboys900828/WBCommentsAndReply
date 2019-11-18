<?php
/**
 * Created by PhpStorm.
 * User: lovelessjack
 * Date: 2018/8/22
 * Time: 14:04
 */

require 'apiTest.php';

$self = new Redirect();
$responseWeiboComments = $self->sWitchRest('comments/to_me'); // 获取本用户评论
if (!$responseWeiboComments) {
    return '没有数据了';
}
$self->replyToOther($responseWeiboComments); // 评论调起AICP接口
$self->sWitchRest('comments/reply'); // 回复用户消息

class Redirect
{
    private $app_id = 3127114891;
    private $app_secret = "xxxxxxxxxxxxxxxxxxxxxx";
    private $redirect = "https://xxxx.xxxx.cn/jingxin/weibo/redirect.php";
    private $access_token = null;
    private $uid = null;
    private $comments = [];
    private $ResponseApiData = [];

    public function __construct()
    {
        $access = json_decode(file_get_contents('./access_token.log'), true);

        if (empty($access['access_token'])) {
            $this->actionSina();
        }

        return $this->access_token = $access['access_token'];
    }

    public function actionSina()
    {
        $url = 'https://api.weibo.com/oauth2/authorize?client_id=' . $this->app_id . '&response_type=code&redirect_uri=' . $this->redirect . "&scope=statuses_to_me_read";
        if ($_GET['code']) {
            $code = $_GET['code'];
            $this->get_access_token($code);
        } else {
            header('Location:' . $url);
        }
    }

    public function get_access_token($code)
    {
        $url = 'https://api.weibo.com/oauth2/access_token?client_id=' . $this->app_id . '&client_secret=' . $this->app_secret . '&grant_type=authorization_code&code=' . $code . '&redirect_uri=' . $this->redirect;
        $data = array();
//        $object = $this->curl_post_content($url, $data);
        $object = self::post($url, $data);
        $array = (array)$object;
        file_put_contents('access_token.log', $array);
        $newArray = json_decode($array, true);
        $this->access_token = $newArray['access_token'];
        $this->uid = $newArray['uid'];
        return $this->access_token;
    }


    private function curl_post_content($url, $post_data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($post_data != '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }

    /** 判断想获取的接口
     * @param $case
     * @return mixed
     */
    public function sWitchRest($case)
    {
        switch ($case) {
            case 'comments/to_me':
                $result = $this->getUserList();
                break;
            case 'comments/reply':
                $result = $this->sendWeiboMessage();
                break;
        }
        return $result;

    }

    /** 获取本账号微博的评论 并得到本截取次数的最后一个SinceId
     * @return mixed
     */
    public function getUserList()
    {
        $url = "https://api.weibo.com/2/comments/to_me.json";
        $SinceId = file_get_contents('./sinceid.log');
        if (is_null($SinceId)) {
            $encodeStr = $url . "?access_token={$this->access_token}&count=50&filter_by_author=0&filter_by_source=0";
        } else {
            $encodeStr = $url . "?access_token={$this->access_token}&since_id={$SinceId}&count=50&filter_by_author=0&filter_by_source=0";
        }


        file_put_contents('./request.log', $encodeStr);
        $newArray = self::get($encodeStr);
        $newArray = json_decode($newArray, true);

        $arrayComment = $newArray['comments']; // 微博消息json数据 都在comments下

        if (!$arrayComment) {
            return null;
        }

        $comments = [];

        foreach ($arrayComment as $item) {
            $BoZhuUid = $item['user']['id'];
            $commentId = $item['id'];
            $weiboId = $item['status']['id'];
            $textCommentContent = $item['text'];
            $comments[] = [
                'bozhuuid' => $BoZhuUid,
                'commentId' => $commentId,
                'weiboId' => $weiboId,
                'textCommentContent' => $textCommentContent
            ];
        }

        $inSinceId = $comments[0]['commentId'];
        file_put_contents('./sinceid.log', $inSinceId);
        $this->comments = $comments;
        return $comments;
    }

    /** 发送微博消息
     *  $url 请求微博Url
     *  access_token
     *  评论ID $V['id']
     *  微博ID Status['id'] 更年期评论ID 4227221390222495 第二篇 ID 4276172047586002
     *  commentContent
     *  comment_ori 1 回复原微博
     * @return mixed|string
     */
    public function sendWeiboMessage()
    {
        $url = "https://api.weibo.com/2/comments/reply.json";

        $comments = $this->comments;
        $ResponseApiData = $this->ResponseApiData;

        foreach ($comments as $ck => $comment) {
            foreach ($ResponseApiData as $ak => $apiDatum) {

                if ($ck == $ak) {
                    $data['access_token'] = $this->access_token;
                    $data['cid'] = $comment['commentId'];
                    $data['id'] = $comment['weiboId'];
                    $data['comment'] = rand(0, 100) . $apiDatum . date('Y-m-d H:i:s', time());
                    $response = self::post($url, $data);
                }
            }
        }
        return $response;

    }


    /** 请求 AICP 获取数据 new apiTest($query_text)
     * @param $messageInfo
     * @return array
     */
    public function replyToOther($messageInfo)
    {
        foreach ($messageInfo as $item) {
            $query_text = $item['textCommentContent'];
            $api = new apiTest($query_text);
            $Message = (array)$api;
            if (is_array($Message)) {
                $returnApiMessage[] = array_values($Message)[1];
            }
        }
        $this->ResponseApiData = $returnApiMessage;
        return $returnApiMessage;
    }


    /**
     * 发送一个POST请求
     */
    public static function post($url, $params = [], $options = [])
    {
        $req = self::sendRequest($url, $params, 'POST', $options);
        return $req['ret'] ? $req['msg'] : '';
    }

    /**
     * 发送一个GET请求
     */
    public static function get($url, $params = [], $options = [])
    {
        $req = self::sendRequest($url, $params, 'GET', $options);
        return $req['ret'] ? $req['msg'] : '';
    }

    /**
     * CURL发送Request请求,含POST和REQUEST
     * @param string $url 请求的链接
     * @param mixed $params 传递的参数
     * @param string $method 请求的方法
     * @param mixed $options CURL的参数
     * @return array
     */
    public static function sendRequest($url, $params = [], $method = 'POST', $options = [])
    {
        $method = strtoupper($method);
        $protocol = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $geturl = $query_string ? $url . (stripos($url, "?") !== FALSE ? "&" : "?") . $query_string : $url;
            $defaults[CURLOPT_URL] = $geturl;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $query_string;
        }

        $defaults[CURLOPT_HEADER] = FALSE;
        $defaults[CURLOPT_USERAGENT] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = TRUE;
        $defaults[CURLOPT_RETURNTRANSFER] = TRUE;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 3;
        $defaults[CURLOPT_TIMEOUT] = 3;

        // disable 100-continue
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = FALSE;
            $defaults[CURLOPT_SSL_VERIFYHOST] = FALSE;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (FALSE === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret' => FALSE,
                'errno' => $errno,
                'msg' => $err,
                'info' => $info,
            ];
        }
        curl_close($ch);
        return [
            'ret' => TRUE,
            'msg' => $ret,
        ];
    }
}


