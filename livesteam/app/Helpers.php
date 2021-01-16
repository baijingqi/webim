<?php

use Illuminate\Http\Response;

if (!function_exists('Z')) {
    /**
     * 单位反转
     *
     * @param $value
     * @param $type
     *
     * @return float
     */
    function Z($value, $type = FEN_TO_YUAN)
    {
        switch ($type) {
            //元到分
            case YUAN_TO_FEN:
                return (float)bcmul($value, 100, 5);
            //分到元
            case FEN_TO_YUAN:
                return (float)bcdiv($value, 100, 2);
            //计算百分比（10/100->0.10000）
            case PERCENTAGE_TO_DECIMAL:
                return (float)bcdiv($value, 100, 5);
            //计算小数（0.1->10/100）
            case DECIMAL_TO_PERCENTAGE:
                return (float)bcmul($value, 100, 2);
        }
    }
}
if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param array|string|null $key
     * @param mixed             $default
     *
     * @return mixed|\Illuminate\Session\Store|\Illuminate\Session\SessionManager
     */
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (!function_exists('toUtf8')) {
    /**
     * 将输入数据的编码统一转换成utf8
     *
     * @params 输入的参数
     */
    function toUtf8($params)
    {
        $utf8s = [];
        foreach ($params as $key => $value) {
            $utf8s[$key] = is_string($value) ? mb_convert_encoding($value, "utf8", "auto") : $value;
        }
        return $utf8s;
    }
}

if (!function_exists("gen_signature")) {
    /**
     * 计算参数签名
     * $params 请求参数
     * $secretKey secretKey
     */
    function gen_signature($secretKey, $params)
    {
        ksort($params);
        $buff = "";
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $buff .= $key;
                $buff .= $value;
            }
        }
        $buff .= $secretKey;
        return md5($buff);
    }
}

if (!function_exists("curl_post")) {
    /**
     * curl post请求
     *
     * @params 输入的参数
     */
    function curl_post($params, $url, $timout)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 设置超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timout);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:' . 'application/x-www-form-urlencoded; charset=UTF-8']);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}

if (!function_exists("msectime")) {
    //返回当前的毫秒时间戳
    function msectime()
    {
        [
            $msec,
            $sec
        ] = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
}

if (!function_exists('object_array')) {
    //PHP stdClass Object转array
    function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = object_array($value);
            }
        }
        return $array;
    }
}


/**
 * 自定义拼接查询sql
 * $customWhere = [
 * 'in'    => [
 * ['uid', [1, 2, 3]],
 * ['aims', [1, 2, 6]],
 * 'tableIds' => [6,7,8]
 * ],
 * 'notIn' => [
 * [
 * 'uid', [5, 6]
 * ],
 * [
 * 'uid', [7]
 * ],
 * ],
 * ['table_id', '>', 2],
 * ];
 */
if (!function_exists('customWhere')) {
    function customWhere($model, $customWhere)
    {
        foreach ($customWhere as $key => $value) {
            switch (strval($key)) {
                case 'in':
                    foreach ($value as $k => $v) {
                        if (is_int($k)) {
                            $model->whereIn($v[0], $v[1]);
                        } else {
                            $model->whereIn($k, $v);
                        }
                    }
                    break;
                case 'notIn':
                    foreach ($value as $k => $v) {
                        $model->whereNotIn($v[0], $v[1]);
                    }
                    break;
                default:
                    if (is_int($key)) {
                        call_user_func_array([
                            $model,
                            'where'
                        ], $value);
                    } else {
                        call_user_func_array([
                            $model,
                            'where'
                        ], [
                            $key,
                            $value
                        ]);
                    }
                    break;
            }
        }
        return $model;
    }
}

/**
 * 框架内部标准返回
 * 定义0 以下为失败，0以上未成功
 */
if (!function_exists('makeStdRes')) {
    function makeStdRes($status, $message, $data = [])
    {
        return [
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ];
    }
}
/**
 * 框架内部标准返回
 * 定义0 以下为失败，0以上未成功
 */
if (!function_exists('makeStdJson')) {
    function makeStdJson($data = [], int $code = Response::HTTP_OK, string $message = 'OK', array $headers = [], $options = 0)
    {
        return response()->json([
            'code'    => intval($code),
            'message' => $message,
            'data'    => $data
        ], Response::HTTP_OK, $headers, $options);
    }
}

/**
 * 生成缓存key
 */
if (!function_exists('makeCacheKey')) {
    function makeCacheKey($key, $params = [])
    {
        return vsprintf(config('cacheKey.' . $key), (array)$params);
    }
}

if (!function_exists('sprintfNum')) {
    function sprintfNum(int $num)
    {
        switch (true) {
            case $num <= 9999 && $num > 0:
                $result = $num . "人看过";
                break;
            case $num >= 10000 && $num <= 99999999 :
                $result = sprintf("%.2f", $num / 10000) . "万人看过";
                break;
            case $num >= 100000000:
                $result = sprintf("%.2f", $num / 100000000) . "亿人看过";
                break;
            default:
                $result = "快来看看吧";
                break;
        }
        return $result;
    }
}

/**
 * 后台记录操作日志
 */
if (!function_exists('addOperateLog')) {
    function addOperateLog(int $uid, int $operationType, int $relationType = 0, int $relationId = 0, string $remark = '')
    {
        return (new OperateLogService())->addLog($uid, $operationType, $relationType, $relationId, $remark);
    }
}

/**
 * 获取数据库查询标准对象
 */
if (!function_exists('getDbSelectObj')) {
    function getDbSelectObj(array $initParams = [])
    {
        return new GetDbSelectObj($initParams);
    }
}


if (!function_exists('dataChangeDesc')) {
    /**
     * 数据变更描述
     *
     * @param       $oldData
     * @param       $newData
     * @param array $fieldMeaningMap
     *
     * @return string
     */
    function dataChangeDesc($oldData, $newData, $fieldMeaningMap = [])
    {
        $str             = '';
        $fieldMeaningMap = array_merge([
            'title'      => '标题',
            'content'    => '内容',
            'remark'     => '备注',
            'createdAt'  => '创建时间',
            'created_at' => '创建时间',
            'updatedAt'  => '修改时间',
            'updated_at' => '修改时间',
            'wid'        => '作品id',
            'uid'        => '用户id',
            'status'     => '状态',
        ], $fieldMeaningMap);
        foreach ($oldData as $key => $value) {
            if (!isset($newData[$key])) {
                continue;
            }
            if ($value != $newData[$key]) {
                $keyMeaning = $fieldMeaningMap[$key] ?? $key;
                if (is_array($value)) {
                    if (!isset($value[0])) {
                        //视为关联数组
                        $str .= "【{$keyMeaning}】：【" . varExportArrayStr($value) . "】 => 【" . varExportArrayStr($newData[$key]) . "】; ";
                    } else {
                        //视为索引数组
                        $str .= "【{$keyMeaning}】：" . implode(',', $value) . " => " . implode(',', $newData[$key]) . "; ";
                    }
                } else {

                    if (($key == 'created_at' || $key == 'updated_at' || stristr($key, 'time') !== false) && is_numeric($value)) {
                        $value         = date(Y_M_D_H_I_S, $value);
                        $newData[$key] = date(Y_M_D_H_I_S, $newData[$key]);
                    }
                    $str .= "【{$keyMeaning}】：{$value} => {$newData[$key]}; ";
                }
            }
        }
        return $str;
    }
}

if (!function_exists('varExportArrayStr')) {
    /**
     * 提取 var_export 数组中的字符串
     *
     * @param array $arr
     *
     * @return string
     */
    function varExportArrayStr(array $arr)
    {
        if (empty($arr)) {
            return '';
        }
        $str = var_export($arr, true);
        preg_match("/array\s*\(\s*(.*?),\s*\)/isU", $str, $res);
        $search  = [
            " ",
            "　",
            "\n",
            "\r",
            "\t"
        ];
        $replace = [
            "",
            "",
            "",
            "",
            ""
        ];
        return str_replace($search, $replace, $res[1]);
    }
}


if (!function_exists('base64ImgToFile')) {
    /**
     * 图片base64文件生成
     *
     * @param        $base64ImageContent
     * @param string $path
     *
     * @return bool|string
     */
    function base64ImgToFile($base64ImageContent, $path = '')
    {
        if (empty($path)) {
            $path = base_path() . '/storage/tmp/';
        }
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64ImageContent, $result)) {
            $type = $result[2];

            if (!file_exists($path)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                mkdir($path, 0700);
            }
            $picName = mt_rand(0, 99) . time() . ".{$type}";
            $newFile = $path . $picName;
            if (file_put_contents($newFile, base64_decode(str_replace($result[1], '', $base64ImageContent)))) {
                return $newFile;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (!function_exists('getFileInfo')) {
    /**
     * 获取文件信息
     *
     * @param string $filePath
     *
     * @return array []
     */
    function getFileInfo(string $filePath)
    {

        if (!file_exists($filePath)) {
            return [];
        }

        $file   = new Symfony\Component\HttpFoundation\File\File($filePath);
        $result = [
            'width'     => 0,
            'height'    => 0,
            'size'      => $file->getSize(),
            'mimeType'  => $file->getMimeType(),
            'path'      => $file->getPath(),
            'basename'  => $file->getBasename(),
            'extension' => $file->getExtension(),
        ];

        if (strpos($result['mimeType'], 'image') !== false) {
            [
                $result['width'],
                $result['height']
            ] = getimagesize($file);
        }
        return $result;
    }
}


