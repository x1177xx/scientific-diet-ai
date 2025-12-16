FROM php:7.4-apache

# 安装MySQL扩展
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 启用Apache重写模块
RUN a2enmod rewrite

# 创建健康检查文件
RUN echo '<?php header("Content-Type: application/json"); echo json_encode(["status"=>"healthy","timestamp"=>date("Y-m-d H:i:s")]); ?>' > /var/www/html/healthcheck.php

# 启动Apache
CMD ["apache2-foreground"]