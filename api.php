<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只接受POST请求'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 固定的WOL服务器地址
define('WOL_SERVER', 'https://woltest-e8e7fmexe6hqfnc6.japaneast-01.azurewebsites.net/1.php');

// 验证必需参数
if (!isset($data['mac']) || empty($data['mac'])) {
    echo json_encode([
        'success' => false,
        'message' => 'MAC地址不能为空'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$mac = $data['mac'];
$key = isset($data['key']) ? $data['key'] : '';

// 使用服务器当前时间戳（而不是客户端传来的）
$time = time();

// 构建WOL请求URL
$wolUrl = WOL_SERVER . '/wol?mac=' . urlencode($mac);

// 如果提供了key，生成MD5 token
// token = MD5(key + mac + time)
if (!empty($key)) {
    $token = md5($key . $mac . $time);
    $wolUrl .= '&time=' . $time . '&token=' . $token;
}

// 记录日志
error_log('WOL请求: ' . $wolUrl);

// 使用file_get_contents发送GET请求（不需要curl扩展）
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$wolResponse = @file_get_contents($wolUrl, false, $context);

// 处理响应
if ($wolResponse === false) {
    $error = error_get_last();
    echo json_encode([
        'success' => false,
        'message' => 'WOL请求失败: ' . ($error['message'] ?? '无法连接到WOL服务器'),
        'requestUrl' => $wolUrl
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 获取HTTP状态码
$httpCode = 200;
if (isset($http_response_header)) {
    foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
            $httpCode = intval($matches[1]);
            break;
        }
    }
}

// 尝试解析JSON响应
$wolData = json_decode($wolResponse, true);

$response = [
    'success' => $httpCode === 200,
    'message' => $httpCode === 200 ? 'WOL唤醒请求已发送' : 'WOL请求返回错误',
    'httpCode' => $httpCode,
    'wolResponse' => $wolData ? $wolData : $wolResponse,
    'requestTime' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
