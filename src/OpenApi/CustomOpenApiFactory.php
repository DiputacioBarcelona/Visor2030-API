<?php
namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\OpenApi;

class CustomOpenApiFactory implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $openApi = $openApi->withInfo(
            new Info(
                'API del Visor 2030 de la Diputació de Barcelona',
                $openApi->getInfo()->getVersion(),
                $openApi->getInfo()->getDescription()
            )
        );

        return $openApi;
    }
}
