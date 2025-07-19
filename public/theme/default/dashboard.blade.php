<!DOCTYPE html>
<html>

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

            <p id="chinese" class="chinese">虽然很辛苦，有些路也只能一个人走。难过的时候记得晒晒太阳，失落的时候和风击个掌。</p>
            <p id="english" class="english"></p>

            <div id="update-time" class="update-time"></div>
        </div>
    </div>

</body>
</html>
</html>
