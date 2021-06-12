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
					  return str_replace(['<b>', '<strong>', '</b>', '</strong>', '<u>', '</u>'], [chr(27).'E1', chr(27).'E1', chr(27).'E0', chr(27).'E0', chr(27).'-1', chr(27).'-0'], strip_tags($string, '<b><strong><u>'));
				})
			);
		
		
		// register filter to output BOLD text
		$twig->addFilter(
				new \Twig\TwigFilter('bold', function ($string) {
					  return chr(27).'E1' . $string . chr(27).'E0';
				})
			);

		// register filter to output UNDERLINED text
		$twig->addFilter(
				new \Twig\TwigFilter('underline', function ($string) {
					  return chr(27).'-1' . $string . chr(27).'-0';
				})
			);
		
		// register filter to output HEAVY UNDERLINED text
		$twig->addFilter(
				new \Twig\TwigFilter('underlineheavy', function ($string) {
					  return chr(27).'-2' . $string . chr(27).'-0';
				})
			);
		
		// register filter to output CENTERED text
		$twig->addFilter(
				new \Twig\TwigFilter('center', function ($string) {
					  return chr(27).'a1' . $string . "\n" . chr(27).'a0';
				})
			);
		
		// register filter to output RIGHT-ALIGNED text
		$twig->addFilter(
				new \Twig\TwigFilter('right', function ($string) {
					  return chr(27).'a2' . $string . "\n" . chr(27).'a0';
				})
			);
		
		// register filter to output FONTB text (reverting to FONTA at the end of output)
		$twig->addFilter(
				new \Twig\TwigFilter('fontB', function ($string) {
					  return chr(27).'M1' . $string . "\n" . chr(27).'M0';
				})
			);
	}
    
    
}


?>