<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>每日一句</title>
    <style>
        /* 重置和基础样式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Microsoft YaHei', sans-serif; /* 添加中文字体 */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* 更美观的背景 */
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        /* 容器样式 */
        .container {
            background-color: rgba(255, 255, 255, 0.95); /* 半透明背景，更融合 */
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            width: 100%;
            backdrop-filter: blur(10px); /* 毛玻璃效果 (现代浏览器) */
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* 标题样式 */
        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2.2em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header p {
            color: #7f8c8d;
            font-size: 1em;
        }

        /* 内容区域 */
        .content {
            text-align: center;
            opacity: 0; /* 初始隐藏，加载成功后显示 */
            transition: opacity 0.5s ease-in-out; /* 淡入效果 */
        }

        /* 图片样式 */
        .quote-image {
            width: 100%;
            max-width: 400px;
            height: auto;
            margin: 0 auto 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            object-fit: cover; /* 保持图片比例填充 */
            aspect-ratio: 4 / 3; /* 设置宽高比，避免布局偏移 */
            background-color: #f0f0f0; /* 占位背景色 */
        }

        /* 文本样式 */
        .quote-text {
            margin-bottom: 20px;
            line-height: 1.8;
        }

        .chinese {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .english {
            font-size: 1.1em;
            font-style: italic;
            color: #555;
            line-height: 1.6;
        }

        /* 音频播放器 */
        .audio-container {
            margin: 25px 0 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .audio-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        audio {
            width: 100%;
        }

        /* 状态信息 */
        .status {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 15px;
        }

        .error {
            color: #e74c3c;
            font-weight: bold;
        }

        /* 加载状态 */
        .loading {
            text-align: center;
            font-size: 1.2em;
            color: #3498db;
            margin: 40px 0;
            font-weight: 500;
        }
        .loading::after {
            content: '...';
            animation: dots 1.5s steps(5, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        /* 平板和手机适配 */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            .header h1 {
                font-size: 1.8em;
            }
            .chinese {
                font-size: 1.2em;
            }
            .english {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>每日一句</h1>
            <p>Learn a new phrase every day</p>
        </div>
        <div id="content-area">
            <!-- 加载状态 -->
            <div id="loading" class="loading">加载中</div>
            <!-- 错误状态 -->
            <div id="error" class="status error" style="display: none;"></div>
            <!-- 主要内容 (初始隐藏) -->
            <div id="main-content" class="content" style="display: none;">
                <img id="daily-image" class="quote-image" alt="每日一句配图" src="">
                <div class="quote-text">
                    <p id="chinese" class="chinese"></p>
                    <p id="english" class="english"></p>
                </div>
                <div class="audio-container">
                    <label for="audio-player">语音朗读:</label>
                    <audio id="audio-player" controls>
                        您的浏览器不支持音频播放。
                    </audio>
                </div>
                <div id="update-time" class="status"></div>
            </div>
        </div>
    </div>

    <script>
        // API URL
        const apiUrl = 'https://api.fenx.top/api/meiriyiju';

        // 获取DOM元素
        const loadingElement = document.getElementById('loading');
        const errorElement = document.getElementById('error');
        const mainContentElement = document.getElementById('main-content');
        const imageElement = document.getElementById('daily-image');
        const chineseElement = document.getElementById('chinese');
        const englishElement = document.getElementById('english');
        const audioElement = document.getElementById('audio-player');
        const updateTimeElement = document.getElementById('update-time');

        // 显示/隐藏元素的辅助函数
        function showElement(element) {
            element.style.display = 'block';
        }
        function hideElement(element) {
            element.style.display = 'none';
        }

        // 清除错误状态
        function clearError() {
            errorElement.textContent = '';
            hideElement(errorElement);
        }

        // 显示错误信息
        function showError(message) {
            errorElement.textContent = message;
            showElement(errorElement);
            hideElement(loadingElement);
            hideElement(mainContentElement);
        }

        // 获取API数据
        async function fetchDailyQuote() {
            try {
                clearError(); // 清除之前的错误
                showElement(loadingElement); // 显示加载中
                hideElement(mainContentElement); // 隐藏主要内容

                const response = await fetch(apiUrl, {
                    method: 'GET',
                    // 可以添加 headers 或 timeout 等选项
                    // headers: { 'Accept': 'text/plain' },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.text(); // API返回的是纯文本

                // 解析文本数据 (使用更精确的非贪婪匹配)
                const imgMatch = data.match(/±img=(.*?)±/);
                const chineseMatch = data.match(/中文：(.*?)\n/);
                const englishMatch = data.match(/英文：(.*?)\n/);
                const audioMatch = data.match(/语音：(.*?)\n/);
                const updateTimeMatch = data.match(/更新时间：(.*?)\n/);

                // 验证是否获取到必要数据
                if (!chineseMatch || !englishMatch) {
                    throw new Error('API返回的数据格式不正确，缺少必要信息。');
                }

                // 更新页面内容
                if (imgMatch && imgMatch[1]) {
                    imageElement.src = imgMatch[1];
                    // 图片加载成功/失败处理
                    imageElement.onload = () => {
                        // 图片加载成功，可以在这里做些处理
                    };
                    imageElement.onerror = () => {
                        console.warn('图片加载失败:', imgMatch[1]);
                        // 可以设置一个默认图片
                        // imageElement.src = 'path/to/default-image.jpg';
                    };
                } else {
                    // 如果没有图片，可以隐藏图片元素或设置默认图
                    imageElement.style.display = 'none'; // 或者设置默认图
                }

                chineseElement.textContent = chineseMatch[1].trim();
                englishElement.textContent = englishMatch[1].trim();

                if (audioMatch && audioMatch[1]) {
                    audioElement.src = audioMatch[1];
                    // 音频加载处理
                    audioElement.onloadeddata = () => {
                        // 音频元数据加载完成
                    };
                    audioElement.onerror = () => {
                        console.warn('音频加载失败:', audioMatch[1]);
                        // 可以禁用或隐藏音频控件
                    };
                } else {
                    // 如果没有音频，可以隐藏音频容器
                    document.querySelector('.audio-container').style.display = 'none';
                }

                if (updateTimeMatch && updateTimeMatch[1]) {
                    updateTimeElement.textContent = `更新时间：${updateTimeMatch[1].trim()}`;
                } else {
                    updateTimeElement.textContent = ''; // 或者显示当前时间
                }

                // 数据加载成功，显示主要内容，隐藏加载状态
                hideElement(loadingElement);
                showElement(mainContentElement);
                // 添加淡入效果
                mainContentElement.style.opacity = 1;

            } catch (error) {
                console.error('获取每日一句失败:', error);
                showError(`获取数据失败: ${error.message || '请稍后再试。'}`);
            }
        }

        // 页面加载完成后获取每日一句
        window.addEventListener('load', fetchDailyQuote);

        // 可选：添加手动刷新功能
        // document.addEventListener('keydown', (e) => {
        //     if (e.key === 'r' || e.key === 'R') {
        //         e.preventDefault();
        //         fetchDailyQuote();
        //     }
        // });
    </script>
</body>
</html>
