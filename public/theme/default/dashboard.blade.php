<!DOCTYPE html>
<html>
<head>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>每日一句</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .content {
            text-align: center;
        }
        .image {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .chinese, .english {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .english {
            font-style: italic;
            color: #555;
        }
        .audio-player {
            margin-top: 20px;
            text-align: center;
        }
        .update-time {
            font-size: 14px;
            color: #888;
            margin-top: 20px;
            text-align: center;
        }
        .loading {
            text-align: center;
            font-size: 16px;
            color: #666;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>每日一句</h1>
        <div class="content">
            <div class="loading">加载中...</div>
            <img id="daily-image" class="image" alt="每日一句图片">
            <p id="chinese" class="chinese"></p>
            <p id="english" class="english"></p>
            <div class="audio-player">
                <audio id="audio-player" controls>
                    您的浏览器不支持音频播放。
                </audio>
            </div>
            <div id="update-time" class="update-time"></div>
        </div>
    </div>

    <script>
        // API URL
        const apiUrl = 'https://api.fenx.top/api/meiriyiju';

        // 获取DOM元素
        const loadingText = document.querySelector('.loading');
        const imageElement = document.getElementById('daily-image');
        const chineseElement = document.getElementById('chinese');
        const englishElement = document.getElementById('english');
        const audioElement = document.getElementById('audio-player');
        const updateTimeElement = document.getElementById('update-time');

        // 获取API数据
        async function fetchDailyQuote() {
            try {
                loadingText.style.display = 'block'; // 显示加载中
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error('网络响应失败');
                }
                const data = await response.text(); // API返回的是纯文本

                // 解析文本数据
                const imgMatch = data.match(/±img=(.*?)±/);
                const chineseMatch = data.match(/中文：(.*?)\n/);
                const englishMatch = data.match(/英文：(.*?)\n/);
                const audioMatch = data.match(/语音：(.*?)\n/);
                const updateTimeMatch = data.match(/更新时间：(.*?)\n/);

                // 更新页面内容
                if (imgMatch) {
                    imageElement.src = imgMatch[1];
                }
                if (chineseMatch) {
                    chineseElement.textContent = chineseMatch[1];
                }
                if (englishMatch) {
                    englishElement.textContent = englishMatch[1];
                }
                if (audioMatch) {
                    audioElement.src = audioMatch[1];
                }
                if (updateTimeMatch) {
                    updateTimeElement.textContent = `更新时间：${updateTimeMatch[1]}`;
                }
            } catch (error) {
                console.error('获取每日一句失败:', error);
                chineseElement.textContent = '获取数据失败，请稍后再试。';
            } finally {
                loadingText.style.display = 'none'; // 隐藏加载中
            }
        }

        // 页面加载完成后获取每日一句
        window.addEventListener('load', fetchDailyQuote);
    </script>
</body>
</html>
</head>
<body>
</body>
</html>