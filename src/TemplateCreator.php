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
		
		
		// register StringExtension to expose |u filter (which provides wordwrap function, among others)
		$twig->addExtension(new \Twig\Extra\String\StringExtension());
		
		
		// register 'chr' function
        $twig->addFunction(new \Twig\TwigFunction('chr', function ($i) {
            	return chr($i);
        	}));    
        
        
		// register filter to convert html to escpos (handles B, STRONG and U tags)
		$twig->addFilter(
				new \Twig\TwigFilter('html2escpos', function ($string) {
					  return str_replace(['<b>', '<strong>', '</b>', '</strong>', '<u>', '</u>'], [chr(27).'E1', chr(27).'E1', chr(27).'E0', chr(27).'E0', chr(27).'-1', chr(27).'-0'], strip_tags($string, '<b><strong><u>'));
				})
			);


		// loading template
		$this->_template = $twig->load($twigfile);
	}
    
}


?>