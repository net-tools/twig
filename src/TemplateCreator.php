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
		
		
		// register functions and filters
		$this->registerFunctions($twig);
		$this->registerFilters($twig);
        $this->registerGlobals($twig);

		// loading template
		$this->_template = $twig->load($twigfile);
	}
	
	
	
	/**
	 * Render the Twig template
	 * 
	 * @param array $args Array of vars to be used in twig template
	 * @return string
	 */
	public function render($args)
	{
		return $this->getTemplate()->render($args);
	}
	
    
    
    /** 
     * Get Twig template
     *
     * @return \Twig\Twig\TemplateWrapper
     */
    public function getTemplate()
    {
        return $this->_template;
    }
	
    
	
	/**
	 * Register functions
	 *
	 * @param \Twig\Environment $twig
	 */
	public function registerFunctions(\Twig\Environment $twig)
	{
		// register 'chr' function
        $twig->addFunction(new \Twig\TwigFunction('chr', function ($i) {
            	return chr($i);
        	})); 
	}
    
	
	
	/**
	 * Register filters
	 *
	 * @param \Twig\Environment $twig
	 */
	public function registerFilters(\Twig\Environment $twig)
	{
		
	}
    
	
	
	/**
	 * Register globals
	 *
	 * @param \Twig\Environment $twig
	 */
	public function registerGlobals(\Twig\Environment $twig)
	{
		
	}
    
}


?>