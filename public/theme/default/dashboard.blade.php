<?php
// 检查访问权限
if (!hasAccess()) {
    // 返回403禁止访问状态码
    http_response_code(403);
    echo "<h1>403 禁止访问</h1>";
    echo "<p>您没有权限访问此页面。</p>";
    exit;
}
?>
