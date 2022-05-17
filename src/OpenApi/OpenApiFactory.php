<?php
namespace App\OpenApi;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\Model\RequestBody;
use ApiPlatform\Core\OpenApi\OpenApi;
use http\Env\Request;

class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        foreach ($openApi->getPaths()->getPaths() as $key => $path) {
            if ($path->getGet() && $path->getGet()->getSummary === "hidden") {
                $openApi->getPaths()->addPath($key, $path->withGet(null));
            }
        }

        $openApi->getPaths()->addPath("/ping", new PathItem(null, "Ping", null,
            new Operation("ping-id", [], [], "Répond")));

        $schemas = $openApi->getComponents()->getSecuritySchemes();
        $schemas['bearerAuth'] = new \ArrayObject([
            "type" => "http",
            "schema" => "bearer",
            "bearerFormat" => "JWT"
        ]);

        $openApi->getComponents()->getSchemas();
        $schemas['Credentials'] = new \ArrayObject([
            "type" => "object",
            "properties" => [
                "username" => [
                    "type" => "string",
                    "example" => "admin@admin.com"
                ],
                "password" => [
                    "type" => "string",
                    "example" => "0000"
                ]
            ]
        ]);
        $schemas['Token'] = new \ArrayObject([
            "type" => "object",
            "properties" => [
                "token" => [
                    "type" => "string",
                    "readOnly" => true
                ]
            ]
        ]);

        $pathItem = new PathItem(
            post: new Operation(
                operationId: "postApiLogin",
                tags: ['Auth'],
                requestBody: new RequestBody(
                    content: new \ArrayObject([
                        "application/json" => [
                            "schema" => [
                                '$ref' =>"#/components/schemas/Credentials"
                            ]
                        ]
                    ])
                ),
                responses: [
                    "200" => [
                        "description" => "Token JWT (Vous êtes connecté)",
                        "content" => [
                            "application/json" => [
                                "schema" => [
                                    '$ref' => "#/components/schemas/Token"
                                ]
                            ]
                        ]
                    ]
                ]
            )
        );

        $openApi->getPaths()->addPath("/login", $pathItem);

        return $openApi;
    }
}