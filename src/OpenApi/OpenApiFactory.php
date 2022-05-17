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
        $schemas['cookieAuth'] = new \ArrayObject([
            "type" => "apiKey",
            "in" => "cookie",
            "name" => "PHPSESSID"
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
                        "description" => "Utilisateur connecté",
                        "content" => [
                            "application/json" => [
                                "schema" => [
                                    '$ref' => "#/components/schemas/User-read.User"
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