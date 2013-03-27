<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Installer
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Zikula\Core\Event\GenericEvent;
use Symfony\Component\Yaml\Yaml;

ini_set('memory_limit', '84M');
ini_set('max_execution_time', 300);

function install(Zikula_Core $core)
{
    define('_ZINSTALLVER', Zikula_Core::VERSION_NUM);

    ZLoader::addPrefix('ZikulaUsersModule', 'system');

    $serviceManager = $core->getContainer();
    $eventManager = $core->getDispatcher();

    // Lazy load DB connection to avoid testing DSNs that are not yet valid (e.g. no DB created yet)
    $dbEvent = new GenericEvent(null, array('lazy' => true));
    $eventManager->dispatch('doctrine.init_connection', $dbEvent);

    $core->init(Zikula_Core::STAGE_ALL & ~Zikula_Core::STAGE_THEME & ~Zikula_Core::STAGE_MODS & ~Zikula_Core::STAGE_LANGS & ~Zikula_Core::STAGE_DECODEURLS & ~Zikula_Core::STAGE_SESSIONS);

    // Power users might have moved the temp folder out of the root and changed the config.php
    // accordingly. Make sure we respect this security related settings
    $tempDir = (isset($GLOBALS['ZConfig']['System']['temp']) ? $GLOBALS['ZConfig']['System']['temp'] : 'ztemp');

    // define our smarty object
    $smarty = new Smarty();
    $smarty->caching = false;
    $smarty->compile_check = true;
    $smarty->left_delimiter = '{';
    $smarty->right_delimiter = '}';
    $smarty->compile_dir = $tempDir . '/view_compiled';
    $smarty->template_dir = 'install/templates';
    $smarty->plugins_dir = array(
            'plugins',
            'install/templates/plugins',
            );
    $smarty->clear_compiled_tpl();
    file_put_contents("$tempDir/view_compiled/index.html", '');

    $lang = FormUtil::getPassedValue('lang', '', 'GETPOST');
    $dbhost = FormUtil::getPassedValue('dbhost', '', 'GETPOST');
    $dbusername = FormUtil::getPassedValue('dbusername', '', 'GETPOST');
    $dbpassword = FormUtil::getPassedValue('dbpassword', '', 'GETPOST');
    $dbname = FormUtil::getPassedValue('dbname', '', 'GETPOST');
    $dbprefix = '';
    $dbdriver = FormUtil::getPassedValue('dbdriver', '', 'GETPOST');
    $dbtabletype = FormUtil::getPassedValue('dbtabletype', '', 'GETPOST');
    $username = FormUtil::getPassedValue('username', '', 'POST');
    $password = FormUtil::getPassedValue('password', '', 'POST');
    $repeatpassword = FormUtil::getPassedValue('repeatpassword', '', 'POST');
    $email = FormUtil::getPassedValue('email', '', 'GETPOST');
    $action = FormUtil::getPassedValue('action', '', 'GETPOST');

    $notinstalled = isset($_GET['notinstalled']);
    $installedState = (isset($GLOBALS['ZConfig']['System']['installed']) ? $GLOBALS['ZConfig']['System']['installed'] : 0);

    // if the system is already installed, halt.
    if ($GLOBALS['ZConfig']['System']['installed']) {
        _installer_alreadyinstalled($smarty);
    }

    // If somehow we are browsing the not installed page but installed, redirect back to homepage
    if ($installedState && $notinstalled) {
        $response = new RedirectResponse(System::getHomepageUrl());
        $response->send();
        return;
    }

    // see if the language was already selected
    $languageAlreadySelected = ($lang) ? true : false;
    if (!$notinstalled && $languageAlreadySelected && empty($action)) {
        $response = new RedirectResponse(System::getBaseUri() . "/install.php?action=requirements&lang=$lang");
        $response->send();
        return;
    }

    // load the installer language files
    if (empty($lang)) {
        if (is_readable('config/installer.ini')) {
            $test = parse_ini_file('config/installer.ini');
            $lang = isset($test['language']) ? $test['language'] : 'en';
        } else {
            $available = ZLanguage::getInstalledLanguages();
            $detector = new ZLanguageBrowser($available);
            $lang = $detector->discover();
        }
        $lang = DataUtil::formatForDisplay($lang);
    }

    // setup multilingual
    $GLOBALS['ZConfig']['System']['language_i18n'] = $lang;
    $GLOBALS['ZConfig']['System']['multilingual'] = true;
    $GLOBALS['ZConfig']['System']['languageurl'] = true;
    $GLOBALS['ZConfig']['System']['language_detect'] = false;
    $serviceManager->loadArguments($GLOBALS['ZConfig']['System']);

    $_lang = ZLanguage::getInstance();
    $request = $core->getContainer()->get('request');
    $_lang->setup($request);

    $lang = ZLanguage::getLanguageCode();

    $installbySQL = (file_exists("install/sql/custom-$lang.sql") ? "install/sql/custom-$lang.sql" : false);

    $smarty->assign('lang', $lang);
    $smarty->assign('installbySQL', $installbySQL);
    $smarty->assign('langdirection', ZLanguage::getDirection());
    $smarty->assign('charset', ZLanguage::getEncoding());

    // show not installed case
    if ($notinstalled) {
        header('HTTP/1.1 503 Service Unavailable');
        $smarty->display('notinstalled.tpl');
        $smarty->clear_compiled_tpl();
        file_put_contents("$tempDir/view_compiled/index.html", '');
        exit;
    }

    // assign the values from config.php
    $smarty->assign($GLOBALS['ZConfig']['System']);

    // check for an empty action - if so then show the first installer page
    if (empty($action)) {
        $action = 'lang';
    }

    // perform tasks based on our action
    switch ($action) {
        case 'processBDInfo':
            $dbname = trim($dbname);
            $dbusername = trim($dbusername);
            if (empty($dbname) || empty($dbusername)) {
                $action = 'dbinformation';
                $smarty->assign('dbconnectmissing', true);
            } elseif (!preg_match('/^[\w-]*$/', $dbname) ||
                    strlen($dbname) > 64) {
                $action = 'dbinformation';
                $smarty->assign('dbinvalidname', true);
            } else {
                update_config_php($dbhost, $dbusername, $dbpassword, $dbname, $dbdriver, $dbtabletype);
                update_installed_status(0);
                try {
                    $dbh = new PDO("$dbdriver:host=$dbhost;dbname=$dbname", $dbusername, $dbpassword);
                } catch (PDOException $e) {
                    $action = 'dbinformation';
                    $smarty->assign('reason', $e->getMessage());
                    $smarty->assign('dbconnectfailed', true);
                }
            }
            if ($action != 'dbinformation') {
                $action = 'createadmin';
            }
            break;

        case 'finish':
            if ((!$username) || preg_match('/[^\p{L}\p{N}_\.\-]/u', $username)) {
                $action = 'createadmin';
                $smarty->assign('uservalidatefailed', true);
                $smarty->assign(array(
                        'username' => $username,
                        'password' => $password,
                        'repeatpassword' => $repeatpassword,
                        'email' => $email));
            } elseif (mb_strlen($password) < 7) {
                $action = 'createadmin';
                $smarty->assign('badpassword', true);
                $smarty->assign(array(
                        'username' => $username,
                        'password' => $password,
                        'repeatpassword' => $repeatpassword,
                        'email' => $email));
            } elseif ($password !== $repeatpassword) {
                $action = 'createadmin';
                $smarty->assign('passwordcomparefailed', true);
                $smarty->assign(array(
                        'username' => $username,
                        'password' => $password,
                        'repeatpassword' => $repeatpassword,
                        'email' => $email));
            } elseif (!validateMail($email)) {
                $action = 'createadmin';
                $smarty->assign('emailvalidatefailed', true);
                $smarty->assign(array(
                        'username' => $username,
                        'password' => $password,
                        'repeatpassword' => $repeatpassword,
                        'email' => $email));
            } else {
                $installedOk = false;
                // if it is the distribution and the process have not failed in a previous step
                if ($installbySQL) {
                    // checks if exists a previous installation with the same prefix
                    $proceed = true;
                    $dbnameConfig = $GLOBALS['ZConfig']['DBInfo']['databases']['default']['dbname'];
                    $exec = ($dbdriver == 'mysql' || $dbdriver == 'mysqli') ?
                            "SHOW TABLES FROM `$dbnameConfig` LIKE '%'" :
                            "SHOW TABLES FROM $dbnameConfig LIKE '%'";
                    $tables = DBUtil::executeSQL($exec);
                    if ($tables->rowCount() > 0) {
                        $proceed = false;
                        $action = 'dbinformation';
                        $smarty->assign('dbexists', true);
                    }
                    if ($proceed) {
                        // checks if file exists
                        if (!file_exists($installbySQL)) {
                            $action = 'dbinformation';
                            $smarty->assign('dbdumpfailed', true);
                        } else {
                            // execute the SQL dump
                            $lines = file($installbySQL);
                            $exec = '';
                            foreach ($lines as $line_num => $line) {
                                $line = trim($line);
                                if (empty($line) || strpos($line, '--') === 0)
                                        continue;
                                $exec .= $line;
                                if (strrpos($line, ';') === strlen($line) - 1) {
                                    if (!DBUtil::executeSQL($exec)) {
                                        $action = 'dbinformation';
                                        $smarty->assign('dbdumpfailed', true);
                                        break;
                                    }
                                    $exec = '';
                                }
                            }
                            ModUtil::dbInfoLoad('ZikulaUsersModule', 'ZikulaUsersModule');
                            ModUtil::dbInfoLoad('ZikulaExtensionsModule', 'ZikulaExtensionsModule');
                            ModUtil::initCoreVars(true);
                            createuser($username, $password, $email);
                            $installedOk = true;
                        }
                    }
                } else {
                    installmodules($lang);
                    createuser($username, $password, $email);
                    $installedOk = true;
                }

                if ($installedOk) {

                    // create our new site admin
                    // TODO: Email username/password to administrator email address.  Cannot use ModUtil::apiFunc for this.
                    $serviceManager->get('session')->start();

                    $authenticationInfo = array(
                        'login_id'  => $username,
                        'pass'      => $password
                    );
                    $authenticationMethod = array(
                        'modname'   => 'ZikulaUsersModule',
                        'method'    => 'uname',
                    );
                    UserUtil::loginUsing($authenticationMethod, $authenticationInfo);

                    // add admin email as site email
                    System::setVar('adminmail', $email);

                    if (!$installbySQL) {
                        Zikula\Module\ThemeModule\Util::regenerate();
                    }

                    // set site status as installed and protect config.php file
                    update_installed_status(1);
                    @chmod('config/config.php', 0400);
                    if (!is_readable('config/config.php')) {
                        @chmod('config/config.php', 0440);
                        if (!is_readable('config/config.php')) {
                            @chmod('config/config.php', 0444);
                        }
                    }
                    // install all plugins
                    $systemPlugins = PluginUtil::loadAllSystemPlugins();
                    foreach ($systemPlugins as $plugin) {
                        PluginUtil::install($plugin);
                    }

                    LogUtil::registerStatus(__('Congratulations! Zikula has been successfully installed.'));
                    System::setInstalling(false);
                    $response = new RedirectResponse(ModUtil::url('ZikulaAdminModule', 'admin', 'adminpanel'));
                    $response->send();
                    exit;
                }
            }
            break;

        case 'requirements':
            $checks = _check_requirements();
            $ok = true;

            foreach ($checks as $check) {
                if (!$check) {
                    $ok = false;
                    break;
                }
            }

            foreach ($checks['files'] as $check) {
                if (!$check['writable']) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $response = new RedirectResponse(System::getBaseUri() . "/install.php?action=dbinformation&lang=$lang");
                $response->send();
                exit;
            }

            $smarty->assign('checks', $checks);

            break;
    }

    // check our action template exists
    $action = DataUtil::formatForOS($action);
    if ($smarty->template_exists("installer_$action.tpl")) {
        $smarty->assign('action', $action);
        $templateName = "installer_$action.tpl";
    } else {
        $smarty->assign('action', 'error');
        $templateName = 'installer_error.tpl';
    }

    $smarty->assign('maincontent', $smarty->fetch($templateName));
    $smarty->display('installer_page.tpl');
    $smarty->clear_compiled_tpl();
    file_put_contents("$tempDir/view_compiled/index.html", '');
}

/**
 * This function inserts the default data on new installs
 */
function createuser($username, $password, $email)
{
    if (!class_exists('Users_Constant')) {
        require_once 'system/Zikula/Module/UsersModule/Constant.php';
    }
    $connection = Doctrine_Manager::connection();

    // get the database connection
    ModUtil::dbInfoLoad('ZikulaUsersModule', 'ZikulaUsersModule');
    ModUtil::dbInfoLoad('ZikulaExtensionsModule', 'ZikulaExtensionsModule');
    $dbtables = DBUtil::getTables();

    // create the password hash
    $password = UserUtil::getHashedPassword($password);

    // prepare the data
    $username = mb_strtolower(DataUtil::formatForStore($username));
    $password = DataUtil::formatForStore($password);
    $email = mb_strtolower(DataUtil::formatForStore($email));

    $nowUTC = new DateTime(null, new DateTimeZone('UTC'));
    $nowUTCStr = $nowUTC->format(Users_Constant::DATETIME_FORMAT);

    // create the admin user
    $sql = "UPDATE {$dbtables['users']}
            SET   uname        = '{$username}',
                  email        = '{$email}',
                  pass         = '{$password}',
                  activated    = 1,
                  user_regdate = '{$nowUTCStr}',
                  lastlogin    = '{$nowUTCStr}'
            WHERE uid   = 2";

    $result = DBUtil::executeSQL($sql);

    return ($result) ? true : false;
}

function installmodules($lang = 'en')
{
    // create a result set
    $results = array();

    $sm = ServiceUtil::getManager();
    $kernel = $sm->get('kernel');

    $coremodules = array(
        'ZikulaExtensionsModule',
        'ZikulaSettingsModule',
        'ZikulaThemeModule',
        'ZikulaAdminModule',
        'ZikulaPermissionsModule',
        'ZikulaGroupsModule',
        'ZikulaBlocksModule',
        'ZikulaUsersModule',
        'ZikulaSecurityCenterModule',
        'ZikulaCategoriesModule',
        'ZikulaMailerModule',
        'ZikulaSearchModule',
        'ZikulaErrorsModule',
    );

    // manually install the modules module
    foreach ($coremodules as $coremodule) {
        $className = null;
        $module = null;
        try {
            $module = $kernel->getModule($coremodule);
            $className = $module->getInstallerClass();
            $bootstrap = $module->getPath().'/bootstrap.php';
            if (file_exists($bootstrap)) {
                include_once $bootstrap;
            }
        } catch (\InvalidArgumentException $e) {
        }
//        $bootstrap = "$modpath/$coremodule/bootstrap.php";
//        if (file_exists($bootstrap)) {
//            include_once $bootstrap;
//        }

        ModUtil::dbInfoLoad($coremodule, $coremodule);
        if (null === $className) {
            if (is_dir("system/$coremodule")) {
                ZLoader::addAutoloader($coremodule, 'system');
                ZLoader::addPrefix($coremodule, 'system');
            }
            $className = "{$coremodule}\\{$coremodule}Installer";
            $classNameOld = "{$coremodule}_Installer";
            $className = class_exists($className) ? $className : $classNameOld;
        }
        $instance = new $className($sm, $module);
        if ($instance->install()) {
            $results[$coremodule] = true;
        }
    }

    // regenerate modules list
    $modApi = new Zikula\Module\ExtensionsModule\Api\AdminApi($sm, new \Zikula\Module\ExtensionsModule\ZikulaExtensionsModule());
    $modApi->regenerate(array('filemodules' => $modApi->getfilemodules()));

    // set each of the core modules to active
    reset($coremodules);
    foreach ($coremodules as $coremodule) {
        $mid = ModUtil::getIdFromName($coremodule, true);
        $modApi->setstate(array('id' => $mid,
                                'state' => ModUtil::STATE_INACTIVE));
        $modApi->setstate(array('id' => $mid,
                                'state' => ModUtil::STATE_ACTIVE));
    }
    // Add them to the appropriate category
    reset($coremodules);
    $coremodscat = array('ZikulaExtensionsModule' => __('System'),
            'ZikulaPermissionsModule' => __('Users'),
            'ZikulaGroupsModule' => __('Users'),
            'ZikulaBlocksModule' => __('Layout'),
            'ZikulaUsersModule' => __('Users'),
            'ZikulaThemeModule' => __('Layout'),
            'ZikulaSecurityCenterModule' => __('Security'),
            'ZikulaCategoriesModule' => __('Content'),
            'ZikulaMailerModule' => __('System'),
            'ZikulaErrorsModule' => __('System'),
            'ZikulaSearchModule' => __('Content'),
            'ZikulaAdminModule' => __('System'),
            'ZikulaSettingsModule' => __('System'));

    $categories = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getall');
    $modscat = array();
    foreach ($categories as $category) {
        $modscat[$category['name']] = $category['cid'];
    }
    foreach ($coremodules as $coremodule) {
        $category = $coremodscat[$coremodule];
        ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'addmodtocategory',
                        array('module' => $coremodule,
                                'category' => $modscat[$category]));
    }
    // create the default blocks.
    $blockInstance = new Zikula\Module\BlocksModule\BlocksModuleInstaller($sm, $kernel->getModule('ZikulaBlocksModule'));
    $blockInstance->defaultdata();

    System::setVar('language_i18n', $lang);

    return $results;
}

function _installer_alreadyinstalled(Smarty $smarty)
{
    header('HTTP/1.1 500 Internal Server Error');
    $smarty->display('installer_alreadyinstalled.tpl');
    System::shutDown();
    exit;
}

function validateMail($mail)
{
    if (!preg_match('/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui', $mail)) {
        return false;
    }

    return true;
}

function _check_requirements()
{
    $results = array();

    $x = explode('.', str_replace('-', '.', phpversion()));
    $phpVersion = "$x[0].$x[1].$x[2]";
    $results['phpsatisfied'] = version_compare($phpVersion, Zikula_Core::PHP_MINIMUM_VERSION, ">=");
    $results['datetimezone'] = ini_get('date.timezone');

    $results['pdo'] = extension_loaded('pdo');
    $results['register_globals'] = !ini_get('register_globals');
    $results['magic_quotes_gpc'] = !ini_get('magic_quotes_gpc');
    $results['phptokens'] = function_exists('token_get_all');
    $results['mbstring'] = function_exists('mb_get_info');
    $isEnabled = @preg_match('/^\p{L}+$/u', 'TheseAreLetters');
    $results['pcreUnicodePropertiesEnabled'] = (isset($isEnabled) && (bool)$isEnabled);
    $results['json_encode'] = function_exists('json_encode');
    $temp = (isset($GLOBALS['ZConfig']['System']['temp']) ? $GLOBALS['ZConfig']['System']['temp'] : 'ztemp');
    $datadir = (isset($GLOBALS['ZConfig']['System']['datadir']) ? $GLOBALS['ZConfig']['System']['datadir'] : 'data');
    $results['config_personal_config_php'] = !is_writable('config/personal_config.php');
    $files = array('config/config.php', "$datadir/", "$temp/", "$temp/error_logs/", "$temp/view_compiled/",
            "$temp/view_cache/", "$temp/Theme_compiled/", "$temp/Theme_cache/", "$temp/Theme_Config/");
    $results['files'] = array();
    foreach ($files as $file) {
        $results['files'][] = array('filename' => $file, 'writable' => is_writable($file));
    }

    return $results;
}

function _installer_replace_keys($searchKey, $replaceWith, $string)
{
    $search = array("#\['$searchKey'\]\s*=\s*('|\")(.*)('|\")\s*;#", "#\['$searchKey'\]\s*=\s*(\d)\s*;#");
    $replace = array("['$searchKey'] = '$replaceWith';", "['$searchKey'] = $replaceWith;");
    return preg_replace($search, $replace, $string);
}

function update_config_php($dbhost, $dbusername, $dbpassword, $dbname, $dbdriver, $dbtabletype)
{
    $file = file_get_contents('config/config.php');
    $file = _installer_replace_keys('dbname', $dbname, $file);
    $file = _installer_replace_keys('dbdriver', $dbdriver, $file);
    $file = _installer_replace_keys('dbtabletype', $dbtabletype, $file);
    $file = _installer_replace_keys('user', $dbusername, $file);
    $file = _installer_replace_keys('password', $dbpassword, $file);
    $file = _installer_replace_keys('host', $dbhost, $file);
    $file = _installer_replace_keys('dbname', $dbname, $file);
    file_put_contents('config/config.php', $file);

    $array = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__.'/../app/config/parameters.yml'));
    $array['parameters']['database_driver'] = 'pdo_'.$dbdriver;
    $array['parameters']['database_host'] = $dbhost;
    $array['parameters']['database_name'] = $dbname;
    $array['parameters']['database_user'] = $dbusername;
    $array['parameters']['database_password'] = $dbpassword;
    file_put_contents(__DIR__.'/../app/config/parameters.yml', Yaml::dump($array));
}

function update_installed_status($state)
{
    $file = _installer_replace_keys('installed', $state, file_get_contents('config/config.php'));
    file_put_contents('config/config.php', $file);
}
