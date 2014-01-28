<?php
// no direct access
defined('_JEXEC') or die;


class  plgSystemLess extends JPlugin
{
	public function onBeforeCompileHead()
	{
		$app = JFactory::getApplication();

		if ($app->getName() != 'site' ) {
			return true;
		}
		if(JFactory::getConfig()->get('debug')){
			JFactory::getDocument()->addScriptDeclaration('less = {
			    env: "development",
			    async: false,
			    fileAsync: false,
			    poll: 1000,
			    functions: {},
			    dumpLineNumbers: "comments",
			    relativeUrls: false,
			    rootpath: ":/'.JUri::base(true).'/"
			  };');	
			JFactory::getDocument()->addCustomTag('<script src="//cdnjs.cloudflare.com/ajax/libs/less.js/'.$this->params->get('version').'/less.min.js"></script>');
		}
	}

	public function onAfterInitialise(){
		JHtml::_('register','stylesheet','plgSystemLess::stylesheet');
	}
	public static function stylesheet($file, $attribs = array(), $relative = false, $path_only = false, $detect_browser = true, $detect_debug = true){
		// Need to adjust for the change in API from 1.5 to 1.6.
		// Function stylesheet($filename, $path = 'media/system/css/', $attribs = array())
		if (is_string($attribs))
		{
			JLog::add('The used parameter set in JHtml::stylesheet() is deprecated.', JLog::WARNING, 'deprecated');
			// Assume this was the old $path variable.
			$file = $attribs . $file;
		}

		if (is_array($relative))
		{
			// Assume this was the old $attribs variable.
			$attribs = $relative;
			$relative = false;
		}


		$includes = self::includeRelativeFiles('css', $file, $relative, $detect_browser, $detect_debug);

		// If only path is required
		if ($path_only)
		{
			if (count($includes) == 0)
			{
				return null;
			}
			elseif (count($includes) == 1)
			{
				return $includes[0];
			}
			else
			{
				return $includes;
			}
		}
		// If inclusion is required
		else
		{
			$document = JFactory::getDocument();
			foreach ($includes as $include)
			{
				if(strpos($include, 'http') !== 0 && !strpos($include, '.less')){
					$include = $include.'?'.filemtime(str_replace(JURI::root(true), JPATH_ROOT, $include));
				}
				if(strpos($include, '.less')){
					$document->addStylesheet($include, 'text/less', null, $attribs);
				} else {
					$document->addStylesheet($include, 'text/css', null, $attribs);
				}
			}
		}

	}

	protected static function includeRelativeFiles($folder, $file, $relative, $detect_browser, $detect_debug)
	{
		// If http is present in filename
		if (strpos($file, 'http') === 0)
		{
			$includes = array($file);
		}
		else
		{
			// Extract extension and strip the file
			$strip		= JFile::stripExt($file);
			$ext		= JFile::getExt($file);

			// Detect browser and compute potential files
			if ($detect_browser)
			{
				$navigator = JBrowser::getInstance();
				$browser = $navigator->getBrowser();
				$major = $navigator->getMajor();
				$minor = $navigator->getMinor();

				// Try to include files named filename.ext, filename_browser.ext, filename_browser_major.ext, filename_browser_major_minor.ext
				// where major and minor are the browser version names
				$potential = array($strip, $strip . '_' . $browser,  $strip . '_' . $browser . '_' . $major,
					$strip . '_' . $browser . '_' . $major . '_' . $minor);
			}
			else
			{
				$potential = array($strip);
			}

			// If relative search in template directory or media directory
			if ($relative)
			{

				// Get the template
				$app = JFactory::getApplication();
				$template = $app->getTemplate();

				// Prepare array of files
				$includes = array();

				// For each potential files
				foreach ($potential as $strip)
				{
					$files = array();
					// Detect debug mode
					if ($detect_debug && JFactory::getConfig()->get('debug'))
					{
						$files[] = $strip . '-uncompressed.' . $ext;
						if($folder == 'css'){
							$files[] = $strip . '.less';
						}
					}
					$files[] = $strip . '.' . $ext;

					// Loop on 1 or 2 files and break on first found
					foreach ($files as $file)
					{
						// If the file is in the template folder
						if (file_exists(JPATH_THEMES . "/$template/$folder/$file"))
						{
							$includes[] = JURI::base(true) . "/templates/$template/$folder/$file";
							break;
						}
						else
						{
							// If the file contains any /: it can be in an media extension subfolder
							if (strpos($file, '/'))
							{
								// Divide the file extracting the extension as the first part before /
								list($extension, $file) = explode('/', $file, 2);

								// If the file yet contains any /: it can be a plugin
								if (strpos($file, '/'))
								{
									// Divide the file extracting the element as the first part before /
									list($element, $file) = explode('/', $file, 2);

									// Try to deal with plugins group in the media folder
									if (file_exists(JPATH_ROOT . "/media/$extension/$element/$folder/$file"))
									{
										$includes[] = JURI::root(true) . "/media/$extension/$element/$folder/$file";
										break;
									}
									// Try to deal with classical file in a a media subfolder called element
									elseif (file_exists(JPATH_ROOT . "/media/$extension/$folder/$element/$file"))
									{
										$includes[] = JURI::root(true) . "/media/$extension/$folder/$element/$file";
										break;
									}
									// Try to deal with system files in the template folder
									elseif (file_exists(JPATH_THEMES . "/$template/$folder/system/$element/$file"))
									{
										$includes[] = JURI::root(true) . "/templates/$template/$folder/system/$element/$file";
										break;
									}
									// Try to deal with system files in the media folder
									elseif (file_exists(JPATH_ROOT . "/media/system/$folder/$element/$file"))
									{
										$includes[] = JURI::root(true) . "/media/system/$folder/$element/$file";
										break;
									}
								}
								// Try to deals in the extension media folder
								elseif (file_exists(JPATH_ROOT . "/media/$extension/$folder/$file"))
								{
									$includes[] = JURI::root(true) . "/media/$extension/$folder/$file";
									break;
								}
								// Try to deal with system files in the template folder
								elseif (file_exists(JPATH_THEMES . "/$template/$folder/system/$file"))
								{
									$includes[] = JURI::root(true) . "/templates/$template/$folder/system/$file";
									break;
								}
								// Try to deal with system files in the media folder
								elseif (file_exists(JPATH_ROOT . "/media/system/$folder/$file"))
								{
									$includes[] = JURI::root(true) . "/media/system/$folder/$file";
									break;
								}
							}
							// Try to deal with system files in the media folder
							elseif (file_exists(JPATH_ROOT . "/media/system/$folder/$file"))
							{
								$includes[] = JURI::root(true) . "/media/system/$folder/$file";
								break;
							}
						}
					}
				}
			}
			// If not relative and http is not present in filename
			else
			{
				$includes = array();
				foreach ($potential as $strip)
				{
					// Detect debug mode
					if ($detect_debug && $folder == 'css' && JFactory::getConfig()->get('debug') && file_exists(JPATH_ROOT . "/$strip.less"))
					{
						$includes[] = JURI::root(true) . "/$strip.less";
					}elseif ($detect_debug && JFactory::getConfig()->get('debug') && file_exists(JPATH_ROOT . "/$strip-uncompressed.$ext"))
					{
						$includes[] = JURI::root(true) . "/$strip-uncompressed.$ext";
					}
					elseif (file_exists(JPATH_ROOT . "/$strip.$ext"))
					{
						$includes[] = JURI::root(true) . "/$strip.$ext";
					}
				}
			}
		}
		return $includes;
	}
}
