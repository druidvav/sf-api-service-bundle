<?php
namespace Druidvav\ApiServiceBundle;

use Druidvav\EssentialsBundle\Controller;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{
    public function jsonRpcAction(Request $request, LoggerInterface $logger)
    {
        return $this->get(ApiServiceContainer::class)->handleRequest($request, $logger);
    }
}