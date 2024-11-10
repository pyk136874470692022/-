<?php
// 检查是否已经初始化
if (file_exists('password.json') && file_exists('initialized.flag')) {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统初始化</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .agreement {
            height: 300px;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background: #f8f9fa;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h2 class="text-center mb-4">网盘系统初始化</h2>
        
        <!-- 步骤1：用户协议 -->
        <div class="step active" id="step1">
            <h4 class="mb-3">用户协议</h4>
            <div class="agreement">
                <h5>用户协议和隐私政策</h5>
                <p>欢迎使用我们的网盘系统。在使用本系统之前，请仔细阅读以下协议：</p>
                
                <h6>1. 服务条款</h6>
                <p>本系统提供文件存储和分享服务，用户需要遵守相关法律法规。</p>
                
                <h6>2. 用户责任</h6>
                <p>用户需要对上传的内容负责，不得上传违法或侵权内容。</p>
                
                <h6>3. 隐私保护</h6>
                <p>我们会保护用户的隐私信息，不会将用户数据用于其他用途。</p>
                
                <!-- 可以添加更多协议内容 -->
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="agreeCheck">
                <label class="form-check-label" for="agreeCheck">
                    我已阅读并同意用户协议和隐私政策
                </label>
            </div>
            <button class="btn btn-primary" onclick="nextStep(1)" id="nextBtn" disabled>下一步</button>
        </div>
        
        <!-- 步骤2：设置密码 -->
        <div class="step" id="step2">
            <h4 class="mb-3">设置管理员密码</h4>
            <form id="setupForm">
                <div class="mb-3">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" class="form-control" id="password" required>
                    <div class="form-text">请设置一个安全的密码，至少包含6个字符。</div>
                </div>
                <div class="mb-4">
                    <label for="confirmPassword" class="form-label">确认密码</label>
                    <input type="password" class="form-control" id="confirmPassword" required>
                </div>
                <button type="submit" class="btn btn-primary">完成设置</button>
            </form>
        </div>
        
        <!-- 步骤3：初始化完成 -->
        <div class="step" id="step3">
            <div class="text-center">
                <h4 class="mb-3">初始化完成</h4>
                <p class="mb-4">系统已经成功初始化，现在可以开始使用了。</p>
                <a href="index.html" class="btn btn-primary">开始使用</a>
            </div>
        </div>
    </div>

    <script>
        // 处理协议同意
        document.getElementById('agreeCheck').addEventListener('change', function() {
            document.getElementById('nextBtn').disabled = !this.checked;
        });

        // 切换步骤
        function nextStep(currentStep) {
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + (currentStep + 1)).classList.add('active');
        }

        // 处理密码设置
        document.getElementById('setupForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password.length < 6) {
                alert('密码长度至少为6个字符！');
                return;
            }
            
            if (password !== confirmPassword) {
                alert('两次输入的密码不一致！');
                return;
            }
            
            try {
                const response = await fetch('setup_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ password: password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    nextStep(2);
                } else {
                    alert(data.error || '初始化失败，请重试！');
                }
            } catch (error) {
                console.error('初始化失败:', error);
                alert('初始化失败，请重试！');
            }
        });
    </script>
</body>
</html> 