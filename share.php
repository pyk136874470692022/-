<?php
require_once 'share_functions.php';

$shareId = $_GET['id'] ?? '';
$password = $_POST['password'] ?? '';

if (!$shareId) {
    $error = '无效的分享链接';
}

$share = getShare($shareId);
if (!$share) {
    $error = '分享不存在或已过期';
}

// 如果需要密码且未提供密码
$needPassword = $share && $share['password'] && !$password;
// 密码错误
$wrongPassword = $share && $share['password'] && $share['password'] !== $password;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件分享</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .share-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .file-icon {
            font-size: 48px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="share-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($needPassword): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">此文件需要密码访问</h5>
                    <form method="post">
                        <div class="mb-3">
                            <input type="password" class="form-control" name="password" placeholder="请输入访问密码" required>
                        </div>
                        <button type="submit" class="btn btn-primary">确认</button>
                    </form>
                </div>
            </div>
        <?php elseif ($wrongPassword): ?>
            <div class="alert alert-danger">密码错误</div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center">
                    <div class="file-icon mb-3">
                        <i class="fas fa-file"></i>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($share['filename']); ?></h5>
                    <p class="text-muted mb-4">
                        <?php echo formatFileSize(filesize("fs/{$share['filename']}")); ?>
                    </p>
                    <a href="fs/<?php echo urlencode($share['filename']); ?>" 
                       class="btn btn-primary btn-lg" download>
                        <i class="fas fa-download me-2"></i>下载文件
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 