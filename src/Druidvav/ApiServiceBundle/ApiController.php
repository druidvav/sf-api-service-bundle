<?php
namespace Druidvav\ApiServiceBundle;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{
    private $apiServiceContainer;

    public function __construct(ApiServiceContainer $apiServiceContainer)
    {
        $this->apiServiceContainer = $apiServiceContainer;
    }

    public function jsonRpcAction(Request $request, LoggerInterface $logger)
    {
        return $this->apiServiceContainer->handleRequest($request, $logger);
    }
}
