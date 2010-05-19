<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Theme
 */

/**
 * display theme changing user interface
 */
function theme_user_main()
{
    // check if theme switching is allowed
    if (!System::getVar('theme_change')) {
        return LogUtil::registerError(__('Notice: Theme switching is currently disabled.'));
    }

    if (!SecurityUtil::checkPermission('Theme::', '::', ACCESS_COMMENT)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = Renderer::getInstance('Theme');

    // get some use information about our environment
    $currenttheme = ThemeUtil::getInfo(ThemeUtil::getIDFromName(pnUserGetTheme()));

    // get all themes in our environment
    $themes = ThemeUtil::getAllThemes(PNTHEME_FILTER_USER);

    $previewthemes = array();
    $currentthemepic = null;
    foreach ($themes as $themeinfo) {
        $themename = $themeinfo['name'];
        if (file_exists($themepic = 'themes/'.DataUtil::formatForOS($themeinfo['directory']).'/images/preview_medium.png')) {
            $themeinfo['previewImage'] = $themepic;
        }
        else {
            $themeinfo['previewImage'] = 'system/Theme/images/preview_medium.png';
        }
        $previewthemes[$themename] = $themeinfo;
        if ($themename == $currenttheme['name']) {
            $currentthemepic = $themepic;
        }
    }

    $pnRender->assign('currentthemepic', $currentthemepic);
    $pnRender->assign('currenttheme', $currenttheme);
    $pnRender->assign('themes', $previewthemes);
    $pnRender->assign('defaulttheme', ThemeUtil::getInfo(ThemeUtil::getIDFromName(System::getVar('Default_Theme'))));

    // Return the output that has been generated by this function
    return $pnRender->fetch('theme_user_main.htm');
}

/**
 * reset the current users theme to the site default
 */
function theme_user_resettodefault()
{
    ModUtil::apiFunc('Theme', 'user', 'resettodefault');
    LogUtil::registerStatus(__('Done! Theme has been reset to the default site theme.'));
    return System::redirect(ModUtil::url('Theme'));
}
