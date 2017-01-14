<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ExtensionsModule\Api;

use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\ExtensionsModule\Entity\ExtensionVarEntity;
use Zikula\ExtensionsModule\Entity\RepositoryInterface\ExtensionVarRepositoryInterface;

/**
 * Class VariableApi
 *
 * This class manages the storage and retrieval of extension variables and is the intended replacement
 * for ModUtil::* methods (getVar, setVar, etc) as well as similar functionality in System:: and ThemeUtil::
 */
class VariableApi
{
    const CONFIG = 'ZConfig';

    private $isInitialized = false;

    /**
     * @var boolean Site is installed or not
     */
    private $installed;

    /**
     * @var ExtensionVarRepositoryInterface
     */
    private $repository;

    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $protectedSystemVars;

    /**
     * @var array
     */
    private $variables;

    /**
     * VariableApi constructor.
     *
     * @param $installed
     * @param ExtensionVarRepositoryInterface $repository
     * @param ZikulaHttpKernelInterface $kernel
     * @param array $multisitesParameters
     */
    public function __construct(
        $installed,
        ExtensionVarRepositoryInterface $repository,
        ZikulaHttpKernelInterface $kernel,
        array $multisitesParameters
    ) {
        $this->installed = $installed;
        $this->repository = $repository;
        $this->kernel = $kernel;
        $this->protectedSystemVars = $multisitesParameters['protected.systemvars'];
    }

    /**
     * Loads extension vars for all extensions to reduce sql statements.
     *
     * @return void
     */
    private function initialize()
    {
        if (!$this->installed) {
            return;
        }

        // The empty arrays for handlers and settings are required to prevent messages with E_ALL error reporting
        $this->variables = [
            \EventUtil::HANDLERS => [],
            \ServiceUtil::HANDLERS => [],
            'ZikulaSettingsModule' => [],
            self::CONFIG => [],
        ];

        // Load all variables into the variables property.
        /** @var ExtensionVarEntity[] $vars */
        $vars = $this->repository->findAll();
        foreach ($vars as $var) {
            if (!array_key_exists($var->getModname(), $this->variables)) {
                $this->variables[$var->getModname()] = [];
            }
            $this->variables[$var->getModname()][$var->getName()] = $var->getValue();
        }

        // Pre-load the variables array with empty arrays for known extensions that do not define any variables to
        // prevent unnecessary queries to the database.
        foreach ($this->kernel->getBundles() as $bundle) {
            if (!array_key_exists($bundle->getName(), $this->variables)) {
                $this->variables[$bundle->getName()] = [];
            }
        }
        // set default values for localized variables
        $this->localizeVariables('en');

        $this->isInitialized = true;
    }

    /**
     * Replace specified variable values with their localized value
     * @see \Zikula\SettingsModule\Listener\LocalizedVariableListener
     * @param $lang
     */
    public function localizeVariables($lang)
    {
        $items = ['sitename', 'slogan', 'metakeywords', 'defaultpagetitle', 'defaultmetadescription'];
        foreach ($items as $item) {
            if (isset($this->variables[self::CONFIG][$item . '_en'])) {
                $this->variables[self::CONFIG][$item] = !empty($this->variables[self::CONFIG][$item . '_' . $lang]) ? $this->variables[self::CONFIG][$item . '_' . $lang] : $this->variables[self::CONFIG][$item . '_en'];
            }
        }
    }

    /**
     * Checks to see if an extension variable is set.
     * @api Core-2.0
     *
     * @param string $extensionName The name of the extension
     * @param string $variableName The name of the variable
     *
     * @return boolean True if the variable exists in the database, false if not
     */
    public function has($extensionName, $variableName)
    {
        if (empty($extensionName) || !is_string($extensionName) || empty($variableName) || !is_string($variableName)) {
            throw new \InvalidArgumentException();
        }
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return isset($this->variables[$extensionName]) && array_key_exists($variableName, $this->variables[$extensionName]);
    }

    /**
     * Get an extension variable.
     * @api Core-2.0
     *
     * @param string $extensionName The name of the extension or pseudo-extension (e.g., 'ZikulaUsersModule', 'ZConfig', '/EventHandlers')
     * @param string $variableName The name of the variable
     * @param mixed $default The value to return if the requested var is not set
     *
     * @return mixed - extension variable value
     */
    public function get($extensionName, $variableName, $default = false)
    {
        if (empty($extensionName) || !is_string($extensionName) || empty($variableName) || !is_string($variableName)) {
            throw new \InvalidArgumentException();
        }
        if (!$this->isInitialized) {
            $this->initialize();
        }

        if (isset($this->variables[$extensionName]) && array_key_exists($variableName, $this->variables[$extensionName])) {
            return $this->variables[$extensionName][$variableName];
        }

        return $default;
    }

    /**
     * Get a system variable.
     * @api Core-2.0
     *
     * @param string $variableName The name of the variable
     * @param mixed $default The value to return if the requested var is not set
     *
     * @return mixed - extension variable value
     */
    public function getSystemVar($variableName, $default = false)
    {
        return $this->get(self::CONFIG, $variableName, $default);
    }

    /**
     * Get all the variables for an extension.
     * @api Core-2.0
     *
     * @param $extensionName
     * @return array
     */
    public function getAll($extensionName)
    {
        if (empty($extensionName) || !is_string($extensionName)) {
            throw new \InvalidArgumentException();
        }
        if (!$this->isInitialized) {
            $this->initialize();
        }

        return isset($this->variables[$extensionName]) ? $this->variables[$extensionName] : [];
    }

    /**
     * Set an extension variable.
     * @api Core-2.0
     *
     * @param string $extensionName The name of the extension
     * @param string $variableName The name of the variable
     * @param string $value The value of the variable
     *
     * @return boolean True if successful, false otherwise
     */
    public function set($extensionName, $variableName, $value = '')
    {
        if (empty($extensionName) || !is_string($extensionName) || empty($variableName) || !is_string($variableName)) {
            throw new \InvalidArgumentException();
        }
        if (!$this->isInitialized) {
            $this->initialize();
        }
        if ($extensionName == self::CONFIG && in_array($variableName, $this->protectedSystemVars)) {
            return false;
        }

        $entities = $this->repository->findBy(['modname' => $extensionName, 'name' => $variableName]);
        if (count($entities) > 1 || count($entities) == 0) {
            foreach ($entities as $entity) {
                // possible duplicates exist. remove all (refs #2385)
                $this->repository->remove($entity);
            }
            $entity = new ExtensionVarEntity();
            $entity->setModname($extensionName);
            $entity->setName($variableName);
        } else {
            $entity = $entities[0];
        }
        $entity->setValue($value);
        $this->repository->persistAndFlush($entity);
        $this->variables[$extensionName][$variableName] = $value;

        return true;
    }

    /**
     * The setAll method sets multiple extension variables.
     * @api Core-2.0
     *
     * @param string $extensionName The name of the extension
     * @param array $vars An associative array of varnames/varvalues
     *
     * @return boolean True if successful, false otherwise
     */
    public function setAll($extensionName, array $vars)
    {
        $ok = true;
        foreach ($vars as $var => $value) {
            $ok = $ok && $this->set($extensionName, $var, $value);
        }

        return $ok;
    }

    /**
     * Delete an extension variable.
     * @api Core-2.0
     *
     * @param string $extensionName The name of the extension
     * @param string $variableName The name of the variable
     *
     * @return boolean True if successful (or var didn't exist), false otherwise
     */
    public function del($extensionName, $variableName)
    {
        if (empty($extensionName) || !is_string($extensionName) || empty($variableName) || !is_string($variableName)) {
            throw new \InvalidArgumentException();
        }
        if (!$this->isInitialized) {
            $this->initialize();
        }

        if (!isset($this->variables[$extensionName])) {
            return true;
        }
        if (array_key_exists($variableName, $this->variables[$extensionName])) {
            unset($this->variables[$extensionName][$variableName]);
        }

        return $this->repository->deleteByExtensionAndName($extensionName, $variableName);
    }

    /**
     * Delete all variables for one extension.
     * @api Core-2.0
     *
     * @param $extensionName
     * @return bool
     */
    public function delAll($extensionName)
    {
        if (empty($extensionName) || !is_string($extensionName)) {
            throw new \InvalidArgumentException();
        }
        if (!$this->isInitialized) {
            $this->initialize();
        }

        if (array_key_exists($extensionName, $this->variables)) {
            unset($this->variables[$extensionName]);
        }

        return $this->repository->deleteByExtension($extensionName);
    }
}
