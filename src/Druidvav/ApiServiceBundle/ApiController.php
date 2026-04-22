<?php

namespace Druidvav\ApiServiceBundle;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{
    private ApiServiceContainer $apiServiceContainer;

    public function __construct(ApiServiceContainer $apiServiceContainer)
    {
        $this->apiServiceContainer = $apiServiceContainer;
    }

    public function jsonRpcAction(Request $request, LoggerInterface $logger): JsonResponse
    {
        return $this->apiServiceContainer->handleRequest($request, $logger);
    }
}
