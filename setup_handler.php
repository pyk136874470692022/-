<?php
header('Content-Type: application/json');

// 检查是否已经初始化
if (file_exists('password.json') && file_exists('initialized.flag')) {
    echo json_encode(['error' => 'System already initialized']);
    exit;
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$password = $data['password'] ?? '';

if (empty($password)) {
    echo json_encode(['error' => 'Password is required']);
    exit;
}

try {
    // 创建必要的目录和文件
    if (!file_exists('fs')) {
        mkdir('fs', 0777, true);
    }
    
    // 保存密码
    $passwordData = ['password' => $password];
    file_put_contents('password.json', json_encode($passwordData));
    
    // 创建分享文件
    if (!file_exists('shares.json')) {
        file_put_contents('shares.json', json_encode([]));
    }
    
    // 创建初始化标记文件
    file_put_contents('initialized.flag', date('Y-m-d H:i:s'));
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 