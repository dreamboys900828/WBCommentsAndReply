<?php

/** 获取百度AICP数据
 * Class apiTest
 */
class apiTest
{
    private $query_text = null;
    private $value = null;

    private function __construct($query_text = null)
    {
        if (is_null($query_text)) {
            echo json_encode('缺少必须参数，请检查doc文档！');
            die;
        }
        $this->query_text = $query_text;
        $this->Requestapi($this->query_text);
    }

    /**调起百度AICP智能TalkSystem
     * @param $query_text
     * @return null
     */
    public function Requestapi($query_text)
    {

        $url = "http://api.aicp.baidu.com/api/v1/core/query?version=20170407";
        $arr['query_text'] = '';
        $arr['test_console'] = true;
        $arr['test_mode'] = true;

        $data = json_encode($arr);

        $out_json = $this->getInfobyapi($url, $data);

        $otput = json_decode($out_json, true);

        if ($otput['code'] == 200 && $otput['msg'] == "ok") {
            $answer = $otput['data']['answer']['answer'];
            $value = $answer['value'];
            if (!$value) {
                $newarr['query_text'] = $query_text;
                $newarr['session_id'] = $otput['data']['session_id'];;
                $newarr['test_console'] = true;
                $newarr['test_mode'] = true;

                $newdata = json_encode($newarr);

                $return_values = $this->getInfobyapi($url, $newdata);
                $returnArray = json_decode($return_values, true);
                $this->value = $returnArray['data']['answer']['answer'][0]['value'];
                return $this->value;
            }
        }
    }


    /**
     * curl post请求api header中包含AICP平台auth验证token
     * @param $url
     * @param $data
     * @return mixed
     */


    public function getInfobyapi($url, $data)
    {
        $header = array(
            'Content-Type: application/json',
            'Authorization: AICP 2a2b4ab9-fb5a-41f9-b100-383288088e01');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//post传输的数据。

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}
