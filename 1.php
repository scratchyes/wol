<?php
// 配置跨域，确保前端能正常请求
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 仅响应GET请求（模拟WOL服务器的/wol接口）
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '仅支持GET请求'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 提取请求参数（MAC地址、时间戳、Token，仅做格式校验不做真实验证）
$mac = isset($_GET['mac']) ? trim($_GET['mac']) : '';
$time = isset($_GET['time']) ? trim($_GET['time']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// 模拟参数校验（仅判断MAC地址是否存在，不做真实有效性校验）
if (empty($mac)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'MAC地址不能为空'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 模拟“唤醒请求发送成功”的响应（返回标准格式数据，适配前端解析逻辑）
$response = [
    'success' => true,
    'message' => 'WOL唤醒数据包已发送',
    'data' => [
        'mac' => $mac,          // 回显请求的MAC地址
        'requestTime' => date('Y-m-d H:i:s', $time ? $time : time()), // 回显请求时间（或当前时间）
        'status' => 'waiting',  // 模拟设备唤醒中状态
        'tips' => '若设备未唤醒，请检查MAC地址是否正确、设备是否支持WOL功能'
    ],
    'code' => 200
];

// 输出响应（模拟真实API返回）
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>
