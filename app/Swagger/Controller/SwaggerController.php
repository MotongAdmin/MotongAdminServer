<?php
/**
 * This file is part of Motong-Admin.
 *
 * @link     https://github.com/MotongAdmin
 * @document https://github.com/MotongAdmin
 * @contact  1003081775@qq.com
 * @author   zyvincent
 * @Company  Icodefuture Information Technology Co., Ltd.
 * @license  GPL
 */

declare(strict_types=1);

namespace App\Swagger\Controller;

use App\Swagger\Generator\SwaggerGenerator;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use ZYProsoft\Exception\HyperfCommonException;
use ZYProsoft\Constants\ErrorCode;

/**
 * @AutoController(prefix="/swagger")
 * Swagger 文档控制器
 */
class SwaggerController
{
    protected ContainerInterface $container;
    protected ResponseInterface $response;
    protected ConfigInterface $config;

    public function __construct(
        ContainerInterface $container,
        ResponseInterface $response,
        ConfigInterface $config
    ) {
        $this->container = $container;
        $this->response = $response;
        $this->config = $config;

        //如果不是开发环境不允许初始化Swagger控制器
        $env = $this->config->get('app_env');
        if ($env != 'development') {
            throw new HyperfCommonException(ErrorCode::PARAM_ERROR, 'swagger only available in development environment');
        }
    }

    /**
     * Swagger UI 页面
     */
    public function index(): PsrResponseInterface
    {
        if (!$this->config->get('swagger.enable', true)) {
            return $this->response->json([
                'code' => 403,
                'message' => 'Swagger 文档已禁用',
            ]);
        }

        $html = $this->getSwaggerUIHtml();
        return $this->response->raw($html)->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * 获取 Swagger JSON 文档
     */
    public function json(): PsrResponseInterface
    {
        if (!$this->config->get('swagger.enable', true)) {
            return $this->response->json([
                'code' => 403,
                'message' => 'Swagger 文档已禁用',
            ]);
        }

        // 尝试从文件读取
        $outputDir = $this->config->get('swagger.output', BASE_PATH . '/public/swagger');
        $jsonPath = $outputDir . '/swagger.json';

        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            return $this->response->raw($content)
                ->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        // 实时生成
        $generator = $this->container->get(SwaggerGenerator::class);
        $document = $generator->generate();

        return $this->response->json($document);
    }

    /**
     * 获取 Swagger UI HTML
     */
    protected function getSwaggerUIHtml(): string
    {
        $info = $this->config->get('swagger.info', []);
        $title = $info['title'] ?? 'Motong Server API';

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 20px 0; }
        .swagger-ui .info .title { font-size: 28px; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/swagger/json",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                docExpansion: "list",
                filter: true,
                showRequestHeaders: true,
                showCommonExtensions: true,
                persistAuthorization: true,
                displayRequestDuration: true,
            });
            window.ui = ui;
        };
    </script>
</body>
</html>
HTML;
    }
}
