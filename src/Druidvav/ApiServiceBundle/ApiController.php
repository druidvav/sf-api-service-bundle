<?php
namespace Druidvav\ApiServiceBundle;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{
    public function jsonRpcAction(Request $request, LoggerInterface $logger)
    {
        return $this->get(ApiServiceContainer::class)->handleRequest($request, $logger);
    }
}