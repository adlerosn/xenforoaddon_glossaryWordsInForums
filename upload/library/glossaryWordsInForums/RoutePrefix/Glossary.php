<?php

class glossaryWordsInForums_RoutePrefix_Glossary implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('glossaryWordsInForums_ControllerPublic_Glossary', 'index', 'kiror-glossary');
	}
}
