<?php


namespace Nettools\Twig;



/**
 * Class for a Twig template creator for an escpos document
 *
 * Registers :
 * - globals for bold, underline, alignements and fonts begin and end escpos commands
 * - filters to tag bold, underline, alignment and font text ; barcode and qrcodes ; image, bwimage, b64image and b64bwimage for image output
 */
class EscPosTemplateCreator extends TemplateCreator {

	protected $_driver;
	protected $_printer;
	protected $_codepage;
	
	
	const IMAGE_TAG = '///IMG///';
	
	
	
	
	/**
	 * Constructor
	 *
	 * @param string $loadfilepath Path to search twig files into
	 * @param string $twigfile Filename of template to load 
	 * @param string|bool $twigcache
	 * @param \Nettools\EscPos\Drivers\Driver $driver Driver object to output through ; mainly used for graphics
	 * @param string $codepage Supported printer codepage to convert utf8 rendered template to
	 */
	public function __construct($loadfilepath, $twigfile, $twigcache, \Nettools\EscPos\Drivers\Driver $driver, $codepage = 'cp858')
	{
		parent::__construct($loadfilepath, $twigfile, $twigcache);
		
		$this->_driver = $driver;
		$this->_printer = new \Nettools\EscPos\Printer($this->_driver);
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
		
		
		// register filter for inline base64 image output
		$twig->addFilter(
				new \Twig\TwigFilter('b64image', function (\Twig\Environment $env, $string, $dither = 0.8) {

					// decode inline base64image
					$imgdec = imagecreatefromstring(base64_decode($string));
					if ( !$imgdec )
						return "Base64-encoded image can't be read";
					
					
					// get image as escpos, loading it from file with appropriate function
					$img = $this->_printer->image($imgdec, $dither);

					// encode image so that iconv process to be done later won't mess with binary image data
					return self::IMAGE_TAG . '(' . base64_encode($img) . ')';
				}, ['needs_environment' => true, 'is_safe' => ['all']])
			);
		
		
		// register filter for inline base64 black & white image output
		$twig->addFilter(
				new \Twig\TwigFilter('b64bwimage', function (\Twig\Environment $env, $string) {

					// decode inline base64image
					$imgdec = imagecreatefromstring(base64_decode($string));
					if ( !$imgdec )
						return "Base64-encoded black & white image can't be read";
					
					
					// get image as escpos, loading it from file with appropriate function
					$img = $this->_printer->bwimage($imgdec);

					// encode image so that iconv process to be done later won't mess with binary image data
					return self::IMAGE_TAG . '(' . base64_encode($img) . ')';
				}, ['needs_environment' => true, 'is_safe' => ['all']])
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
								// get image as escpos, loading it from file with appropriate function
								$img = $this->_printer->imageFile($path . '/' . $string, $dither);
								
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
								// get black & white image as escpos, loading it from file with appropriate function
								$img = $this->_printer->bwimageFile($path . '/' . $string);
								
								
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