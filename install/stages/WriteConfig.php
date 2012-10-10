<?php
/**
 * Saves a configure JawsConfig.php
 *
 * @author Jon Wood <jon@substance-it.co.uk>
 * @access public
 */
class Installer_WriteConfig extends JawsInstallerStage
{
    /**
     * Sets up a JawsConfig
     *
     * @access public
     * @return string
     */
    function BuildConfig()
    {
        include_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('stages/WriteConfig/templates');
        $tpl->Load('JawsConfig.php', false, false);

        $tpl->SetBlock('JawsConfig');
        $tpl->SetVariable('db_driver',  $_SESSION['install']['Database']['driver']);
        $tpl->SetVariable('db_host',    $_SESSION['install']['Database']['host']);
        $tpl->setVariable('db_port',    $_SESSION['install']['Database']['port']);
        $tpl->SetVariable('db_user',    $_SESSION['install']['Database']['user']);
        $tpl->SetVariable('db_pass',    $_SESSION['install']['Database']['password']);
        $tpl->SetVariable('db_isdba',   $_SESSION['install']['Database']['isdba']);
        $tpl->SetVariable('db_path',    addslashes($_SESSION['install']['Database']['path']));
        $tpl->SetVariable('db_name',    $_SESSION['install']['Database']['name']);
        $tpl->SetVariable('db_prefix',  $_SESSION['install']['Database']['prefix']);
        $tpl->SetVariable('log_level',  '0');
        $tpl->ParseBlock('JawsConfig');

        return $tpl->Get();
    }

    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string      A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        _log(JAWS_LOG_DEBUG,"Preparing configuaration file");
        $tpl = new Jaws_Template('stages/WriteConfig/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('WriteConfig');

        $config_path = JAWS_PATH .'config'.DIRECTORY_SEPARATOR;
        $tpl->setVariable('lbl_info',                _t('INSTALL_CONFIG_INFO'));
        $tpl->setVariable('lbl_solution',            _t('INSTALL_CONFIG_SOLUTION'));
        $tpl->setVariable('lbl_solution_permission', _t('INSTALL_CONFIG_SOLUTION_PERMISSION', $config_path));
        $tpl->setVariable('lbl_solution_upload',     _t('INSTALL_CONFIG_SOLUTION_UPLOAD', $config_path. 'JawsConfig.php'));
        $tpl->SetVariable('next',                    _t('GLOBAL_NEXT'));

        $tpl->SetVariable('config', $this->BuildConfig());
        $tpl->ParseBlock('WriteConfig');
        return $tpl->Get();
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
        //config string
        $configString = $this->BuildConfig();

        // following what the web page says (choice 1) and assume that the user has created it already
        if (file_exists(JAWS_PATH . 'config/JawsConfig.php')) {
            $configMD5    = md5($configString);
            $existsConfig = file_get_contents(JAWS_PATH . 'config/JawsConfig.php');
            $existsMD5    = md5($existsConfig);
            if ($configMD5 == $existsMD5) {
                _log(JAWS_LOG_DEBUG,"Previous and new configuration files have the same content, everything is ok");
                return true;
            }
            _log(JAWS_LOG_DEBUG,"Previous and new configuration files have different content, trying to update content");
        }

        // create a new one if the dir is writeable
        if (Jaws_Utils::is_writable(JAWS_PATH . 'config/')) {
            $result = file_put_contents(JAWS_PATH . 'config/JawsConfig.php', $configString);
            if ($result) {
                _log(JAWS_LOG_DEBUG,"Configuration file has been created/updated");
                return true;
            }
            _log(JAWS_LOG_DEBUG,"Configuration file couldn't be created/updated");
            return new Jaws_Error(_t('INSTALL_CONFIG_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
        }
        
        return new Jaws_Error(_t('INSTALL_CONFIG_RESPONSE_MAKE_CONFIG', 'JawsConfig.php'), 0, JAWS_ERROR_WARNING);
  }
}