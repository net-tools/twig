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
	}
    
    
}


?>