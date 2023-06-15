<?php
/**
 * Copyright (C) 2023-2023 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    E-Com <e-com@presta.eu.org>
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2023 - 2023 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

use JSMin\JSMin;
use Thirtybees\Core\DependencyInjection\ServiceLocator;
use Thirtybees\Core\Error\ErrorUtils;

if (!defined('_TB_VERSION_')) {
    exit;
}

class TbMinifier extends Module
{
    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'tbminifier';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'thirty bees';
        $this->controllers = [];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('HTML, JS and CSS Minification');
        $this->description = $this->l('This module adds minification support');
        $this->need_instance = 0;
        $this->tb_versions_compliancy = '> 1.4.0';
        $this->tb_min_version = '1.5.0';
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install()
    {
        return (
            parent::install() &&
            $this->registerHook('actionMinifyHtml') &&
            $this->registerHook('actionMinifyCss') &&
            $this->registerHook('actionMinifyJs')
        );
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    public function hookActionMinifyHtml($params)
    {
        static::loadClasses();
        try {
            $input = (string)$params['html'];
            return Minify_HTML::minify($input, ['cssMinifier', 'jsMinifier']);
        } catch (Throwable $e) {
            $this->logError("HTML minification failed", $e);
            return null;
        }
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    public function hookActionMinifyJs($params)
    {
        static::loadClasses();
        try {
            $input = (string)$params['js'];
            return JSMin::minify($input);
        } catch (Throwable $e) {
            $this->logError("Javascript minification failed", $e);
            return null;
        }
    }

    /**
     * @param array $params
     *
     * @return string|null
     */
    public function hookActionMinifyCss($params)
    {
        static::loadClasses();
        try {
            $cssContent = (string)$params['css'];
            return Minify_CSSmin::minify($cssContent);
        } catch (Throwable $e) {
            $this->logError("CSS minification failed", $e);
            return null;
        }
    }

    /**
     * @return void
     */
    private static function loadClasses()
    {
        static $loaded = false;
        if (! $loaded) {
            require_once(__DIR__ . '/vendor/autoload.php');
            $loaded = true;
        }
    }


    /**
     * @param string $message
     * @param Throwable $e
     *
     * @return void
     */
    private function logError(string $message, Throwable $e)
    {
        $errorDescription = ErrorUtils::describeException($e);
        $errorDescription->setMessage($message . ': ' . $errorDescription->getMessage());
        $errorHandler = ServiceLocator::getInstance()->getErrorHandler();
        $errorHandler->logFatalError($errorDescription);
    }
}
