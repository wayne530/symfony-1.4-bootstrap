<?php

class sfRestfulActions extends sfActions {

    /** @var array  api definition */
    protected $_api = array();

    /** @var null|string  authentication key */
    protected $_key = null;

    /** @var string  key parameter name */
    protected $_keyParameterName = 'key';

    /**
     * set http response code
     *
     * @param int $code  http response code
     * @param bool $final  whether to terminate the request processing
     *
     * @return void
     */
    protected function respondWith($code, $final = false) {
        $this->getResponse()->setStatusCode($code);
        if ($final) {
            $this->getResponse()->sendHttpHeaders();
            throw new sfStopException();
        }
    }

    /** @override */
    public function execute($request) {
        $this->checkIsValidApiRequest($request);
        parent::execute($request);
        if (isset($this->results)) {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($this->results));
        } else {
            $this->respondWith(Http::NO_CONTENT);
            return sfView::NONE;
        }
    }

    /**
     * check if the api request given as actionName and method, are valid
     *
     * @param sfWebRequest $request  http request object
     *
     * @return void
     */
    protected function checkIsValidApiRequest($request) {
        $actionName = $this->getActionName();
        $method = $request->getMethod();
        if (! isset($this->_api[$actionName])) {
            $this->respondWith(Http::NOT_FOUND, true);
        } else if (! isset($this->_api[$actionName][$method])) {
            $this->respondWith(Http::METHOD_NOT_ALLOWED, true);
        } else if (isset($this->_api[$actionName][$method]['auth']) && $this->_api[$actionName][$method]['auth'] && ! is_null($this->_key) && ($this->_key !== $request->getGetParameter($this->_keyParameterName))) {
            $this->respondWith(Http::UNAUTHORIZED, true);
        } else if (isset($this->_api[$actionName][$method]['required'])) {
            $requiredParams = $this->_api[$actionName][$method]['required'];
            if (! is_array($requiredParams)) { $requiredParams = array($requiredParams); }
            foreach ($requiredParams as $requiredParam) {
                if (is_null($request->getParameter($requiredParam))) {
                    $this->respondWith(Http::BAD_REQUEST, true);
                }
            }
        }
    }

}