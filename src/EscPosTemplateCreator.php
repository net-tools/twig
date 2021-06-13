<?php


namespace Nettools\Twig;



/**
 * Class for a Twig template creator
 *
 * Registers :
 * - StringExtension to use |u filter
 * - chr function to create string from ascii int values
 */
class EscPosTemplateCreator extends TemplateCreator {

	protected $_driver;
	protected $_codepage;
	
	
	const IMAGE_TAG = '///IMG///';
	
	
	
	
	/**
	 * Constructor
	 *
	 * @param string $loadfilepath Path to search twig files into
	 * @param string $twigfile Filename of template to load 
	 * @param string|bool $twigcache
	 */
	public function __construct($loadfilepath, $twigfile, $twigcache, \Nettools\EscPos\Drivers\Driver $driver, $codepage = 'cp858')
	{
		parent::__construct($loadfilepath, $twigfile, $twigcache);
		
		$this->_driver = $driver;
		$this->_codepage = $codepage;
	}
	
	
	
	/**
	 * Render the Twig template and perform iconv to the target codepage
	 * 
	 * @param array $args Array of vars to be used in twig template
	 * @return string
	 */
	public function render($args)
	{
		$txt = parent::render($args);
		
		// convert text to target codepage
		$txt = iconv('utf8', $this->_codepage . '//IGNORE', $txt);
		
		// handle special sections
		return preg_replace_callback('#///IMG///\(([^)]+)\)#', function(array $matches){ return base64_decode($matches[1]); }, $txt);
	}
	
    
    
	/**
	 * Register globals
	 *
	 * @param \Twig\Environment $twig
	 */
	public function registerGlobals(\Twig\Environment $twig)
	{
		$twig->addGlobal('bold', chr(27).'E1');
		$twig->addGlobal('nobold', chr(27).'E0');
		$twig->addGlobal('underline', chr(27).'-1');
		$twig->addGlobal('underlineheavy', chr(27).'-2');
		$twig->addGlobal('nounderline', chr(27).'-0');
		$twig->addGlobal('center', chr(27).'a1');
		$twig->addGlobal('right', chr(27).'a2');
		$twig->addGlobal('left', chr(27).'a0');
		$twig->addGlobal('fontA', chr(27).'M0');
		$twig->addGlobal('fontB', chr(27).'M1');
	}
    
    
    
	/**
	 * Register filters
	 *
	 * @param \Twig\Environment $twig
	 */
	public function registerFilters(\Twig\Environment $twig)
	{
		parent::registerFilters($twig);
		
		
		
		// register filter to convert html to escpos (handles B, STRONG and U tags)
		$twig->addFilter(
				new \Twig\TwigFilter('html2escpos', function ($string) {
					  return str_replace(['<b>', '<strong>', '</b>', '</strong>', '<u>', '</u>'], [chr(27).'E1', chr(27).'E1', chr(27).'E0', chr(27).'E0', chr(27).'-1', chr(27).'-0'], strip_tags(str_replace("\r\n", "\n", $string), '<b><strong><u>'));
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter to output BOLD text
		$twig->addFilter(
				new \Twig\TwigFilter('bold', function ($string) {
					  return chr(27).'E1' . $string . chr(27).'E0';
				}, ['is_safe' => ['all']])
			);

		
		// register filter to output UNDERLINED text
		$twig->addFilter(
				new \Twig\TwigFilter('underline', function ($string) {
					  return chr(27).'-1' . $string . chr(27).'-0';
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter to output HEAVY UNDERLINED text
		$twig->addFilter(
				new \Twig\TwigFilter('underlineheavy', function ($string) {
					  return chr(27).'-2' . $string . chr(27).'-0';
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter to output CENTERED text
		$twig->addFilter(
				new \Twig\TwigFilter('center', function ($string) {
					  return chr(27).'a1' . $string . "\n" . chr(27).'a0';
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter to output RIGHT-ALIGNED text
		$twig->addFilter(
				new \Twig\TwigFilter('right', function ($string) {
					  return chr(27).'a2' . $string . "\n" . chr(27).'a0';
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter to output FONTB text (reverting to FONTA at the end of output)
		$twig->addFilter(
				new \Twig\TwigFilter('fontB', function ($string) {
					  return chr(27).'M1' . $string . "\n" . chr(27).'M0';
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter for barcode output
		$twig->addFilter(
				new \Twig\TwigFilter('barcode', function ($string, $kind) {
					  return $this->_driver->barcode($string, $kind);
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter for qrcode output
		$twig->addFilter(
				new \Twig\TwigFilter('qrcode', function ($string, $kind, $size = 3, $ec = NULL) {
					  return $this->_driver->qrcode($string, $kind, $size, $ec);
				}, ['is_safe' => ['all']])
			);
		
		
		// register filter for image output
		$twig->addFilter(
				new \Twig\TwigFilter('image', function (\Twig\Environment $env, $string, $dither = 0.8) {
					
                    // search image in Twig FilesystemLoader
                    $loader = $env->getLoader();
                    if ( $loader instanceof \Twig\Loader\FilesystemLoader )
                    {
                        // search all paths registered in Twig FilesystemLoader
                        foreach ( $loader->getPaths() as $path )
                            if ( file_exists($path . '/' . $string) )
                            {
                                if ( strpos($string, '.png') > 0 )
                                    $img = $this->_driver->image(imagecreatefrompng($path . '/' . $string), $dither);
                                else
                                    $img = $this->_driver->image(imagecreatefromjpeg($path . '/' . $string), $dither);
								
								
								// encode image so that iconv process to be done later won't mess with binary image data
								return self::IMAGE_TAG . '(' . base64_encode($img) . ')';
                            }
                        
                        return "** Image not found in Twig FilesystemLoader **";
                    }
                    else
                        return "** Twig loader unsupported **";       
				}, ['needs_environment' => true, 'is_safe' => ['all']])
			);
		
		
		// register filter for black & white image output (no dithering)
		$twig->addFilter(
				new \Twig\TwigFilter('bwimage', function (\Twig\Environment $env, $string) {
					
                    // search image in Twig FilesystemLoader
                    $loader = $env->getLoader();
                    if ( $loader instanceof \Twig\Loader\FilesystemLoader )
                    {
                        // search all paths registered in Twig FilesystemLoader
                        foreach ( $loader->getPaths() as $path )
                            if ( file_exists($path . '/' . $string) )
                            {
                                if ( strpos($string, '.png') > 0 )
                                    $img = $this->_driver->bwimage(imagecreatefrompng($path . '/' . $string));
                                else
                                    $img = $this->_driver->bwimage(imagecreatefromjpeg($path . '/' . $string));
								
								
								// encode image so that iconv process to be done later won't mess with binary image data
								return self::IMAGE_TAG . '(' . base64_encode($img) . ')';
                            }
                        
                        return "** Image not found in Twig FilesystemLoader **";
                    }
                    else
                        return "** Twig loader unsupported **";       
				}, ['needs_environment' => true, 'is_safe' => ['all']])
			);
	}
    
    
}


?>