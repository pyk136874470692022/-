<?php
// 开启错误报告但不显示在输出中
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'share_functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');

// 确保 fs 文件夹存在
$uploadDir = 'fs';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['error' => 'Failed to create upload directory']);
        exit;
    }
}

// 处理不同的请求方法
$method = $_SERVER['REQUEST_METHOD'];
try {
    switch ($method) {
        case 'GET':
            // 获取文件列表
            $path = isset($_GET['path']) ? $_GET['path'] : '';
            $currentDir = $path ? $uploadDir . '/' . $path : $uploadDir;
            
            $files = array();
            if (is_dir($currentDir)) {
                $dirContents = scandir($currentDir);
                if ($dirContents === false) {
                    throw new Exception('Failed to read directory');
                }
                foreach ($dirContents as $item) {
                    if ($item != '.' && $item != '..') {
                        $itemPath = $currentDir . '/' . $item;
                        $files[] = array(
                            'name' => $item,
                            'size' => filesize($itemPath),
                            'date' => date('Y-m-d H:i:s', filemtime($itemPath)),
                            'isFolder' => is_dir($itemPath)
                        );
                    }
                }
            }
            echo json_encode($files);
            break;

        case 'POST':
            if (isset($_FILES['files'])) {
                // 处理文件上传
                $response = array();
                $files = $_FILES['files'];
                $currentPath = isset($_POST['currentPath']) ? $_POST['currentPath'] : '';
                
                // 确定上传目录
                $targetDir = $uploadDir;
                if ($currentPath) {
                    $targetDir .= '/' . $currentPath;
                    // 确保目标目录存在
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                }
                
                // 如果是单个文件，转换为数组格式
                if (!is_array($files['name'])) {
                    $files = array(
                        'name' => array($files['name']),
                        'type' => array($files['type']),
                        'tmp_name' => array($files['tmp_name']),
                        'error' => array($files['error']),
                        'size' => array($files['size'])
                    );
                }
                
                // 处理每个文件
                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $files['tmp_name'][$key];
                        $fileName = $files['name'][$key];
                        $targetPath = $targetDir . '/' . $fileName;
                        
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $response[] = array(
                                'name' => $fileName,
                                'size' => filesize($targetPath),
                                'path' => $targetPath
                            );
                        }
                    }
                }
                
                if (count($response) > 0) {
                    echo json_encode(array('success' => true, 'files' => $response));
                } else {
                    http_response_code(500);
                    echo json_encode(array('success' => false, 'error' => 'Upload failed'));
                }
            } elseif (isset($_POST['action'])) {
                if ($_POST['action'] === 'createFolder') {
                    // 创建文件夹
                    $folderName = $_POST['name'];
                    $folderPath = $uploadDir . '/' . $folderName;
                    
                    if (!file_exists($folderPath)) {
                        if (mkdir($folderPath, 0777, true)) {
                            echo json_encode(array('success' => true));
                        } else {
                            throw new Exception('Failed to create folder');
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array('error' => 'Folder already exists'));
                    }
                } elseif ($_POST['action'] === 'getShare') {
                    $filename = $_POST['filename'];
                    $shares = getShares();
                    $share = null;
                    
                    foreach ($shares as $id => $shareInfo) {
                        if ($shareInfo['filename'] === $filename && $shareInfo['expires'] > time()) {
                            $share = array_merge(['id' => $id], $shareInfo);
                            break;
                        }
                    }
                    
                    echo json_encode(['share' => $share]);
                } elseif ($_POST['action'] === 'createShare') {
                    $filename = $_POST['filename'];
                    $password = $_POST['password'];
                    
                    // 生成分享ID
                    $shareId = bin2hex(random_bytes(16));
                    $shares = getShares();
                    $shares[$shareId] = [
                        'filename' => $filename,
                        'password' => $password,
                        'created' => time(),
                        'expires' => time() + (7 * 24 * 60 * 60)
                    ];
                    saveShares($shares);
                    
                    echo json_encode(['success' => true, 'shareId' => $shareId]);
                } elseif ($_POST['action'] === 'cancelShare') {
                    $filename = $_POST['filename'];
                    $shares = getShares();
                    
                    foreach ($shares as $id => $share) {
                        if ($share['filename'] === $filename) {
                            unset($shares[$id]);
                        }
                    }
                    
                    saveShares($shares);
                    echo json_encode(['success' => true]);
                } elseif ($_POST['action'] === 'updateSharePassword') {
                    $filename = $_POST['filename'];
                    $newPassword = $_POST['password'];
                    $shares = getShares();
                    $updated = false;
                    
                    foreach ($shares as $id => $share) {
                        if ($share['filename'] === $filename) {
                            $shares[$id]['password'] = $newPassword;
                            $updated = true;
                            break;
                        }
                    }
                    
                    if ($updated) {
                        saveShares($shares);
                        echo json_encode(['success' => true]);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Share not found']);
                    }
                } elseif ($_POST['action'] === 'changePassword') {
                    $currentPassword = $_POST['currentPassword'];
                    $newPassword = $_POST['newPassword'];
                    
                    // 读取当前密码
                    $passwordFile = 'password.json';
                    $passwordData = json_decode(file_get_contents($passwordFile), true);
                    
                    // 验证当前密码
                    if ($currentPassword === $passwordData['password']) {
                        // 更新密码
                        $passwordData['password'] = $newPassword;
                        file_put_contents($passwordFile, json_encode($passwordData));
                        echo json_encode(['success' => true]);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => '当前密码错误']);
                    }
                } elseif ($_POST['action'] === 'login') {
                    $username = $_POST['username'];
                    $password = $_POST['password'];
                    
                    // 读取密码文件
                    $passwordFile = 'password.json';
                    $passwordData = json_decode(file_get_contents($passwordFile), true);
                    
                    // 验证用户名和密码
                    if ($username === 'admin' && $password === $passwordData['password']) {
                        echo json_encode(['success' => true]);
                    } else {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
                    }
                }
            }
            break;

        case 'DELETE':
            // 删除文件
            parse_str(file_get_contents('php://input'), $params);
            $fileName = $params['filename'];
            $filePath = $uploadDir . '/' . $fileName;
            
            if (file_exists($filePath)) {
                if (is_dir($filePath)) {
                    if (rmdir($filePath)) {
                        echo json_encode(array('success' => true));
                    } else {
                        throw new Exception('Failed to delete folder');
                    }
                } else {
                    if (unlink($filePath)) {
                        echo json_encode(array('success' => true));
                    } else {
                        throw new Exception('Failed to delete file');
                    }
                }
            } else {
                http_response_code(404);
                echo json_encode(array('error' => 'File not found'));
            }
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 