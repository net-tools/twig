<?php


namespace Nettools\Twig;



/**
 * Class for a Twig template creator
 *
 * Registers :
 * - StringExtension to use |u filter
 * - chr function to create string from ascii int values
 */
class TemplateCreator {

    protected $_template;
		
		
		
	/**
	 * Constructor
	 *
	 * @param string $loadfilepath Path to search twig files into
	 * @param string $twigfile Filename of template to load 
	 * @param string|bool $twigcache
	 */
	public function __construct($loadfilepath, $twigfile, $twigcache)
	{
        $loader = new \Twig\Loader\FilesystemLoader($loadfilepath);
        $twig = new \Twig\Environment($loader, array(
            'cache' => $twigcache,
            'strict_variables' => true,
            'auto_reload'=>true
        ));
		$twig->addExtension(new \Twig\Extra\String\StringExtension());
        $twig->addFunction(new \Twig\TwigFunction('chr', function ($i) {
            return chr($i);
        }));       
		
		$this->_template = $twig->load($twigfile);
	}
    
}


?>