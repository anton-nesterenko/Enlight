<?php
/**
 * Enlight
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://enlight.de/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shopware.de so we can send you a copy immediately.
 *
 * @category   Enlight
 * @package    Enlight_Components_Snippet
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 * @version    $Id$
 * @author     Heiner Lohaus
 * @author     $Author$
 */

/**
 * @category   Enlight
 * @package    Enlight_Components_Snippet
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 */
class Enlight_Components_Snippet_Resource extends Smarty_Internal_Resource_Extends
{
    /**
     * @var     Enlight_Components_Snippet_Manager
     */
    protected $snippets;

    /**
     * Class constructor, sets snippet manager
     * @param $snippets Enlight_Components_Snippet_Manager
     */
    public function __construct(Enlight_Components_Snippet_Manager $snippets)
    {
        $this->snippets = $snippets;
    }

    /**
    * populate Source Object with meta data from Resource
    *
    * @param    Smarty_Template_Source   $source    source object
    * @param    Smarty_Internal_Template $_template template object
    */
    public function populate(Smarty_Template_Source $source, Smarty_Internal_Template $_template=null)
    {
        if(!isset($source->smarty->registered_plugins[Smarty::PLUGIN_BLOCK]['snippet'])) {
            $source->smarty->registerPlugin(Smarty::PLUGIN_BLOCK, 'snippet', array($this, 'compile'));
        }
        $source->smarty->default_resource_type = 'file';
        $default_resource = $source->smarty->default_resource_type;
        parent::populate($source, $_template);
        $source->smarty->default_resource_type = $default_resource;
    }

    /**
     * @param   $params
     * @param   $content
     * @param   Enlight_Template_Handler $template
     * @return  string
     */
    public function compile($params, $content, Enlight_Template_Handler $template)
    {
        if($content===null) {
            return '';
        }

        if(empty($content)) {
            $content = '#' . $params['name'] . '#';
        }

        if(!empty($params['class'])) {
            $params['class'] .= ' ' . str_replace('/', '_', $params['namespace']);
        } else {
            $params['class'] = str_replace('/', '_', $params['namespace']);
        }

        if(!empty($params['tag'])) {

            $params['tag'] = strtolower($params['tag']);

            if(!empty($params['class'])) {
                $params['class'] .= ' shopware_studio_snippet';
            } else {
                $params['class'] = 'shopware_studio_snippet';
            }

            $attr = '';
            foreach ($params as $key => $param) {
                if(in_array($key, array('name', 'tag', 'assign', 'name', 'namespace', 'default', 'force'))) {
                    continue;
                }
                $attr .= ' ' . $key . '="' . htmlentities($param, ENT_COMPAT, mb_internal_encoding(), false) . '"';
            }

            $content = htmlentities($content, ENT_COMPAT, mb_internal_encoding(), false);
            $content = "<{$params['tag']}$attr>" . $content . "</{$params['tag']}>";
        }

        if (isset($params['assign'])) {
           $template->assign($params['assign'], $content);
           return '';
        } else {
           return $content;
        }
    }

	/**
    * Load template's source from files into current template object
    *
    * @param Smarty_Template_Source $source source object
    * @return string template source
    * @throws SmartyException if source cannot be loaded
    */
    public function getContent(Smarty_Template_Source $source)
    {
        foreach ($source->components as $_component) {
            /** @var $content Smarty_Template_Source */
            $_component->content = $this->replaceSnippet($_component);
        }
        $this->snippets->write();
    	return parent::getContent($source);
    }

    /**
     * @throws SmartyException
     * @param Smarty_Template_Source $source
     * @return string
     */
    protected function replaceSnippet(Smarty_Template_Source $source)
    {
    	$_rdl = preg_quote($source->smarty->right_delimiter);
		$_ldl = preg_quote($source->smarty->left_delimiter);

		$_block_namespace = $this->getNamespace($source);

		$pattern = "!{$_ldl}s(e?)(\s.+?)?{$_rdl}(.*?){$_ldl}/se?{$_rdl}!msi";
		while (preg_match($pattern, $source->content, $_block_match, PREG_OFFSET_CAPTURE)) {
			$_block_editable = !empty($_block_match[1][0]);
			$_block_args = $_block_match[2][0];
			$_block_default = $_block_match[3][0];
			list($_block_tag, $_block_start) = $_block_match[0];
			$_block_length = strlen($_block_tag);
			if (!preg_match("!(.?)(name=)(.*?)(?=(\s|$))!", $_block_args, $_match) && empty($_block_default)) {
                throw new SmartyException("\"" . $_block_tag . "\" missing name attribute");
	        }
	        $_block_force = (bool) preg_match('#[\s]force#', $_block_args);
	        $_block_name = !empty($_match[3]) ? trim($_match[3], '\'"') : $_block_default;
	        if (preg_match("!(.?)(namespace=)(.*?)(?=(\s|$))!", $_block_args, $_match)) {
	            $_namespace = trim($_match[3], '\'"');
	        } else {
	        	$_namespace = $_block_namespace;
	        }
	        $_block_content = $this->getSnippet($_namespace, $_block_name, $_block_default, $_block_force);

	        if(!empty($_block_default)) {
	        	$_block_args .= ' default=' . var_export($_block_default, true);
	        }
	        if(!empty($_block_namespace)) {
	        	$_block_args .= ' namespace=' . var_export($_block_namespace, true);
	        }
	        if(!empty($_block_editable)) {
	        	$_block_args .= ' tag="span"';
            }

            $_rdl = $source->smarty->right_delimiter;
		    $_ldl = $source->smarty->left_delimiter;

	        $_block_content = "{$_ldl}snippet$_block_args{$_rdl}{$_block_content}{$_ldl}/snippet{$_rdl}";

	        $source->content = substr_replace($source->content, $_block_content, $_block_start, $_block_length);
		}

		return $source->content;
    }

    /**
     * @param $name
     * @param $namespace
     * @param $default
     * @param bool $force
     * @return mixed
     */
    protected function getSnippet($namespace, $name, $default, $force=false)
    {
    	if($this->snippets == null){
    		return $default;
    	}
    	$snippet = $this->snippets->getNamespace($namespace);
		$content = $snippet->get($name);
		if($content === null || $force) {
			$snippet->set($name, $default);
			return $default;
		} else {
			return $content;
		}
    }

    /**
     * @throws  Enlight_Exception
     * @param   Smarty_Template_Source $source
     * @return  null|string
     */
    protected function getNamespace(Smarty_Template_Source $source)
    {
        $_rdl = preg_quote($source->smarty->right_delimiter);
		$_ldl = preg_quote($source->smarty->left_delimiter);

        if(preg_match("!{$_ldl}namespace(\s.+?)?{$_rdl}!msi", $source->content, $_namespace_match)) {
			$source->content = str_replace($_namespace_match[0], '', $source->content);
			if (preg_match("!.?name=(.*?)(?=(\s|$))!", $_namespace_match[1], $_name_match)) {
	            return $_name_match[1];
	        } elseif (strpos($_namespace_match[1], 'ignore') !== false) {
	        	return null;
	        } else {
                throw new Enlight_Exception("Missing name attribute in namespace block");
            }
		} else {
			$path = realpath($source->filepath);
            foreach ($source->smarty->getTemplateDir() as $template_dir) {
                $template_dir = realpath($template_dir);
                if(strpos($path, $template_dir) === 0) {
                    $namespace = substr($path, strlen($template_dir));
                    $namespace = strtr($namespace, DIRECTORY_SEPARATOR, '/');
                    $namespace = dirname($namespace) . '/' . basename($namespace, '.tpl');
                    $namespace = trim($namespace, '/');
                    return $namespace;
                }
            }
		}
        
        return null;
    }
}