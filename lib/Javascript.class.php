<?php

class Javascript {

    /** jquery version string */
    const JQUERY_VERSION = '1.6.2';

    /** jquery ui version string */
    const JQUERY_UI_VERSION = '1.8.16.custom';

    /** @var array  map of factory methods to css selectors as keys */
    protected static $_factoryMethodToSelectorMap = array();

    /** @var array  map of Javascript module to array of dependencies */
    protected static $_dependencies = array();

    /** @var sfWebResponse  response object */
    protected static $_response = null;

    /**
     * fetch the current context's response object
     *
     * @static
     * @return sfWebResponse  response object
     */
    protected static function _getContextResponse() {
        if (is_null(self::$_response)) {
            self::$_response = sfContext::getInstance()->getResponse();
        }
        return self::$_response;
    }

    /**
     * request to load the specified module, along with dependencies if defined
     *
     * @static
     * @param string $module  module to load
     *
     * @return void
     */
    public static function load($module) {
        // load dependencies first
        if (isset(self::$_dependencies[$module])) {
            foreach (self::$_dependencies[$module] as $depModule) {
                // recursive, but without cycle detection
                self::load($depModule);
            }
        }
        self::_getContextResponse()->addJavascript($module);
    }

    /**
     * load jquery module, optionally load jquery ui as well
     *
     * @static
     * @param bool $withUi  whether to load jquery ui
     *
     * @return void
     */
    public static function loadJquery($withUi = false) {
        $jqueryModule = 'jquery-' . self::JQUERY_VERSION;
        self::load($jqueryModule);
        if ($withUi) {
            $jqueryUiModule = 'jquery-ui-' . self::JQUERY_UI_VERSION;
            self::load($jqueryUiModule);
        }
    }

    /**
     * Require a Javascript module and bind it to the specified selector, if any
     *
     * @static
     * @param string $factoryMethod  a Javascript module's factory method
     * @param string|null $selector  a CSS selector for nodes that should be bound
     *
     * @return void
     */
    public static function bind($factoryMethod, $selector = null) {
        self::loadJquery(false /** with jquery ui */);
        if (! isset(self::$_factoryMethodToSelectorMap[$factoryMethod])) {
            self::$_factoryMethodToSelectorMap[$factoryMethod] = array();
            self::load($factoryMethod);
        }
        self::$_factoryMethodToSelectorMap[$factoryMethod][$selector] = true;
    }

    /**
     * Generate the Javascript code to execute factory methods, optionally binding them with specified CSS selectors
     *
     * @static
     * @return string  javascript code
     */
    public static function getBindCode() {
        if (count(self::$_factoryMethodToSelectorMap) > 0) {
            $bindCode = '$(document).ready(function() {';
            foreach (self::$_factoryMethodToSelectorMap as $factoryMethod => $selectors) {
                foreach (array_keys($selectors) as $selector) {
                    if (! is_null($selector) && (strlen($selector) > 0)) {
                        $bindCode .= '$(\'' . $selector . '\').each(function() { ' . $factoryMethod . '(this); });';
                    } else {
                        $bindCode .= $factoryMethod . '();';
                    }
                }
            }
            $bindCode .= '});';
        } else {
            $bindCode = '';
        }
        return $bindCode;
    }

}