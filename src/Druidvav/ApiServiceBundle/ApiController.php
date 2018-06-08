<?php
namespace Druidvav\ApiServiceBundle;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{
    public function jsonRpcAction(Request $request)
    {
        return $this->get(ApiServiceContainer::class)->handleRequest($request);
    }
}